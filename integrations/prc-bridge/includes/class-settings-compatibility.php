<?php
/**
 * Read existing PRC settings without overwriting either plugin's options.
 *
 * @package PRC_Bridge
 */

namespace Agent_Ready_Content\PRC_Bridge;

use Agent_Ready_Content\Llms_Txt_Cache_Invalidator;

/**
 * Use legacy PRC options as defaults until the site saves native settings.
 */
class Settings_Compatibility {
	/** Register legacy option fallbacks and invalidation. */
	public function register(): void {
		add_filter( 'agent_ready_content_settings_defaults', array( $this, 'get_defaults' ) );
		add_filter( 'default_option_agent_ready_content_content_signal', array( $this, 'get_content_signal' ) );

		foreach ( array( 'added_option', 'updated_option', 'deleted_option' ) as $hook ) {
			add_action( $hook, array( $this, 'option_changed' ) );
		}
	}

	/**
	 * Map compatible saved PRC settings into native defaults.
	 *
	 * @param array<string, mixed> $defaults Native settings defaults.
	 * @return array<string, mixed>
	 */
	public function get_defaults( array $defaults ): array {
		$stored = get_option( 'prc_markdown_for_agents_settings', array() );
		if ( ! is_array( $stored ) ) {
			return $defaults;
		}

		if ( isset( $stored['featured_reports'] ) && ! isset( $stored['featured_posts'] ) ) {
			$stored['featured_posts'] = $stored['featured_reports'];
		}

		return array_merge( $defaults, array_intersect_key( $stored, $defaults ) );
	}

	/**
	 * Return the legacy Content-Signal only when the native option is absent.
	 *
	 * @param mixed $default_value Native default value.
	 * @return mixed
	 */
	public function get_content_signal( $default_value ) {
		return get_option( 'prc_markdown_for_agents_content_signal', $default_value );
	}

	/**
	 * Purge the index when an option used as a fallback changes.
	 *
	 * @param string $option Changed option name.
	 */
	public function option_changed( string $option ): void {
		if ( in_array( $option, array( 'prc_markdown_for_agents_settings', 'prc_markdown_for_agents_content_signal' ), true ) ) {
			Llms_Txt_Cache_Invalidator::purge_cache();
		}
	}
}
