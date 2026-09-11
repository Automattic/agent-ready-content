<?php

/**
 * WordPress integration test bootstrap.
 *
 * @package Agent_Ready_Content
 */

require_once __DIR__ . '/../../vendor/autoload.php';

$agent_ready_content_tests_dir = (string) getenv( 'WP_TESTS_DIR' );
if ( '' === $agent_ready_content_tests_dir ) {
	$agent_ready_content_tests_dir = (string) getenv( 'WP_PHPUNIT__DIR' );
}

if ( ! file_exists( $agent_ready_content_tests_dir . '/includes/functions.php' ) ) {
	echo 'Could not find the WordPress test library.' . PHP_EOL; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	exit( 1 );
}

$agent_ready_content_polyfills = __DIR__ . '/../../vendor/yoast/phpunit-polyfills';
if ( ! defined( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH' ) && is_dir( $agent_ready_content_polyfills ) ) {
	// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- Required by the WordPress test library.
	define( 'WP_TESTS_PHPUNIT_POLYFILLS_PATH', $agent_ready_content_polyfills );
}

require_once $agent_ready_content_tests_dir . '/includes/functions.php';

tests_add_filter(
	'muplugins_loaded',
	static function (): void {
		require __DIR__ . '/../../agent-ready-content.php';
		require __DIR__ . '/../../integrations/prc-bridge/prc-bridge.php';

		add_action(
			'agent_ready_content_register_block_callbacks',
			static function (): void {
				\Agent_Ready_Content\Block_Markdown_Registry::register(
					'agent-ready-content/test-block',
					static function ( array $block ): string {
						return '**' . ( $block['attrs']['text'] ?? '' ) . '**';
					}
				);
			}
		);

		add_action(
			'prc_markdown_for_agents_register_block_callbacks',
			static function (): void {
				\PRC\Platform\Markdown_For_Agents\Block_Markdown_Registry::register(
					'prc-bridge/test-block',
					static function ( array $block ): string {
						return (string) ( $block['attrs']['text'] ?? '' );
					}
				);
			}
		);

		add_filter(
			'prc_markdown_for_agents_block_prc-bridge/test-block',
			static function ( string $markdown ): string {
				return '**' . $markdown . '**';
			}
		);
	}
);

require $agent_ready_content_tests_dir . '/includes/bootstrap.php';
