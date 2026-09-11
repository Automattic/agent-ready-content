<?php
/**
 * PRC Bridge compatibility integration tests.
 *
 * @package PRC_Bridge
 */

namespace Agent_Ready_Content\Tests\Integration;

use Agent_Ready_Content\Markdown_Converter;
use Agent_Ready_Content\Settings;

/**
 * Exercises the compatibility contracts supplied by PRC Bridge.
 */
class PRCBridgeCompatibilityTest extends \WP_UnitTestCase {
	/**
	 * Remove options written by compatibility tests.
	 */
	public function tear_down(): void {
		delete_option( Settings::OPTION_KEY );
		delete_option( 'prc_markdown_for_agents_settings' );
		parent::tear_down();
	}

	/**
	 * Legacy class names resolve to the base implementation.
	 */
	public function test_legacy_registry_alias_uses_base_registry(): void {
		$this->assertTrue( class_exists( 'PRC\Platform\Markdown_For_Agents\Block_Markdown_Registry' ) );
		$this->assertTrue(
			\PRC\Platform\Markdown_For_Agents\Block_Markdown_Registry::has( 'prc-bridge/test-block' )
		);
	}

	/**
	 * Legacy block registration and per-block filters reach the base converter.
	 */
	public function test_legacy_block_callback_converts_post_content(): void {
		$post_id = self::factory()->post->create(
			array(
				'post_content' => '<!-- wp:prc-bridge/test-block {"text":"Legacy provider"} /-->',
				'post_status'  => 'draft',
			)
		);

		$markdown = ( new Markdown_Converter() )->post_to_markdown( $post_id );

		$this->assertSame( '**Legacy provider**', $markdown );
	}

	/**
	 * Legacy block.json metadata is copied to the native supports key.
	 */
	public function test_legacy_block_metadata_is_translated(): void {
		$metadata = apply_filters(
			'block_type_metadata',
			array(
				'name'                 => 'prc-bridge/metadata-test',
				'prcMarkdownForAgents' => array( 'mode' => 'children-only' ),
			)
		);

		$this->assertSame(
			array( 'mode' => 'children-only' ),
			$metadata['supports']['agentReadyContent']
		);
	}

	/**
	 * Legacy settings populate defaults without overwriting native saved values.
	 */
	public function test_legacy_settings_are_read_as_defaults(): void {
		update_option(
			'prc_markdown_for_agents_settings',
			array(
				'site_summary'     => 'Legacy summary',
				'featured_reports' => array(),
			)
		);

		$settings = Settings::get_settings();
		$this->assertSame( 'Legacy summary', $settings['site_summary'] );
		$this->assertSame( array(), $settings['featured_posts'] );

		update_option( Settings::OPTION_KEY, array( 'site_summary' => 'Native summary' ) );
		$this->assertSame( 'Native summary', Settings::get_settings()['site_summary'] );
	}
}
