<?php
/**
 * Translate the extension contracts used by existing PRC providers.
 *
 * @package PRC_Bridge
 */

namespace Agent_Ready_Content\PRC_Bridge;

use Agent_Ready_Content\Block_Markdown_Registry;

/**
 * Registers legacy aliases, hook translations, and PRC-specific adapters.
 */
class Bridge {
	/** @var array<string, bool> Dynamic block filters already forwarded. */
	private array $block_filters = array();

	/**
	 * Establish aliases before PRC provider files are loaded.
	 */
	public function register_aliases(): void {
		$aliases = array(
			'Block_Markdown_Registry',
			'Block_Markdown_Resolver',
			'Markdown_Converter',
			'HTML_To_Markdown_Converter',
			'Frontmatter',
			'LLMs_Txt',
			'Markdown_Response',
			'Markdown_Cache_Invalidator',
			'Llms_Txt_Cache_Invalidator',
		);
		foreach ( $aliases as $name ) {
			$legacy = 'PRC\\Platform\\Markdown_For_Agents\\' . $name;
			if ( ! class_exists( $legacy, false ) ) {
				class_alias( 'Agent_Ready_Content\\' . $name, $legacy );
			}
		}
	}

	/**
	 * Register hook translations and PRC-specific adapters.
	 */
	public function register(): void {
		$filter_arguments = array(
			'pre_markdown'                => 2,
			'after_markdown'              => 2,
			'authors'                     => 2,
			'frontmatter'                 => 2,
			'llms_txt_sections'           => 1,
			'additional_resources_blocks' => 1,
		);
		foreach ( $filter_arguments as $suffix => $accepted_args ) {
			add_filter(
				'agent_ready_content_' . $suffix,
				static function ( $value, ...$arguments ) use ( $suffix ) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Compatibility requires invoking the legacy PRC hook.
					return apply_filters( 'prc_markdown_for_agents_' . $suffix, $value, ...$arguments );
				},
				10,
				$accepted_args
			);
		}

		add_action(
			'agent_ready_content_register_block_callbacks',
			static function (): void {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Compatibility requires invoking the legacy PRC hook.
				do_action( 'prc_markdown_for_agents_register_block_callbacks' );
			}
		);
		add_action( 'prc_markdown_for_agents_set_context', array( Block_Markdown_Registry::class, 'set_context' ) );
		add_action( 'prc_markdown_for_agents_clear_context', array( Block_Markdown_Registry::class, 'clear_context' ) );
		add_filter( 'block_type_metadata', array( $this, 'translate_block_metadata' ), 5 );
		add_filter( 'register_block_type_args', array( $this, 'translate_block_supports' ), 5 );
		add_action( 'registered_post_type', array( $this, 'translate_post_type_supports' ) );
		add_action( 'init', array( $this, 'translate_post_type_supports' ), PHP_INT_MAX );
		add_filter( 'agent_ready_content_pre_markdown', array( $this, 'prepare_conversion' ), 0 );
		add_action( 'init', array( $this, 'prepare_conversion' ), PHP_INT_MAX );
		add_filter( 'agent_ready_content_llms_txt_sections', array( $this, 'append_legacy_sections' ), 1000 );

		( new Staff_Bylines_Integration() )->register();
		( new Datasets_Integration() )->register();
		( new PDF_Extraction_Integration() )->register();
		( new Report_Package_Integration() )->register();
		( new Settings_Compatibility() )->register();
		( new PDF_Cache_Compatibility() )->register();
	}

	/**
	 * Copy legacy block metadata to the base plugin key.
	 *
	 * @param array<string, mixed> $metadata Raw block metadata.
	 * @return array<string, mixed>
	 */
	public function translate_block_metadata( array $metadata ): array {
		if ( ! isset( $metadata['agentReadyContent'] ) && isset( $metadata['prcMarkdownForAgents'] ) ) {
			$metadata['agentReadyContent'] = $metadata['prcMarkdownForAgents'];
		}
		return $this->translate_block_supports( $metadata );
	}

	/**
	 * Copy legacy block support to the base plugin key.
	 *
	 * @param array<string, mixed> $args Block registration arguments.
	 * @return array<string, mixed>
	 */
	public function translate_block_supports( array $args ): array {
		if ( ! isset( $args['supports']['agentReadyContent'] ) && isset( $args['supports']['prcMarkdownForAgents'] ) ) {
			$args['supports']['agentReadyContent'] = $args['supports']['prcMarkdownForAgents'];
		}
		return $args;
	}

	/**
	 * Copy legacy post type support flags to the base plugin flags.
	 */
	public function translate_post_type_supports(): void {
		foreach ( get_post_types() as $type ) {
			$supports = array(
				'prc-markdown-for-agents'          => 'agent-ready-content',
				'prc-markdown-for-agents-llms-txt' => 'agent-ready-content-llms-txt',
			);
			foreach ( $supports as $old => $new ) {
				if ( post_type_supports( $type, $old ) ) {
					add_post_type_support( $type, $new );
				}
			}
		}
	}

	/**
	 * Forward only registered per-block filters, without a global "all" hook.
	 *
	 * @param mixed $pre Existing pre-conversion value.
	 * @return mixed
	 */
	public function prepare_conversion( $pre = null ) {
		global $wp_filter;
		$prefix = 'prc_markdown_for_agents_block_';
		foreach ( array_keys( $wp_filter ) as $hook ) {
			if ( ! str_starts_with( $hook, $prefix ) || isset( $this->block_filters[ $hook ] ) ) {
				continue;
			}
			add_filter(
				'agent_ready_content_block_' . substr( $hook, strlen( $prefix ) ),
				static function ( $markdown, $block, $post ) use ( $hook ) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- The dynamic name is a discovered legacy PRC hook.
					return apply_filters( $hook, $markdown, $block, $post );
				},
				10,
				3
			);
			$this->block_filters[ $hook ] = true;
		}
		return $pre;
	}

	/**
	 * Preserve the original free-form index extension alongside typed sections.
	 *
	 * @param array<int, array<string, mixed>> $sections Base index sections.
	 * @return array<int, array<string, mixed>>
	 */
	public function append_legacy_sections( array $sections ): array {
		if ( ! has_filter( 'prc_llms_txt_sections' ) ) {
			return $sections;
		}
		$allowed      = array_fill_keys( array( 'code', 'em', 'strong', 'h2', 'h3', 'ul', 'ol', 'li', 'p' ), array() );
		$allowed['a'] = array_fill_keys( array( 'href', 'title', 'rel', 'target' ), true );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Compatibility requires invoking the legacy PRC hook.
		$body = trim( wp_kses( (string) apply_filters( 'prc_llms_txt_sections', '' ), $allowed ) );
		if ( '' === $body ) {
			return $sections;
		}
		foreach ( $sections as &$section ) {
			if ( 'additional-resources' === ( $section['slug'] ?? '' ) ) {
				$section['description'] = $body . "\n\n" . ( $section['description'] ?? '' );
				return $sections;
			}
		}
		unset( $section );
		$sections[] = array(
			'slug'        => 'additional-resources',
			'title'       => 'Additional Resources',
			'description' => $body,
			'links'       => array(),
		);
		return $sections;
	}
}
