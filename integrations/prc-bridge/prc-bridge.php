<?php
/**
 * Plugin Name: PRC Bridge
 * Description: Connect existing PRC integrations to Agent Ready Content on WordPress VIP.
 * Version: 0.1.0
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Requires Plugins: agent-ready-content
 * Author: WPVIP
 * Author URI: https://wpvip.com
 * License: GPL-2.0-or-later
 * Text Domain: prc-bridge
 *
 * Includes adapters derived from PRC Markdown for Agents by Pew Research Center.
 * See NOTICE.md and LICENSE.
 *
 * @package PRC_Bridge
 */

namespace Agent_Ready_Content\PRC_Bridge;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Load the base, then this bridge, then PRC providers. Some providers check
// for the original classes while their main plugin file is being included.
if ( ! defined( 'AGENT_READY_CONTENT_FILE' ) || defined( 'PRC_MARKDOWN_FOR_AGENTS_FILE' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			// phpcs:ignore WordPress.WP.I18n.TextDomainMismatch -- This separately loaded plugin has its own text domain.
			echo '<div class="notice notice-error"><p>' . esc_html__( 'Load Agent Ready Content before PRC Bridge, and disable PRC Markdown for Agents.', 'prc-bridge' ) . '</p></div>';
		}
	);
	return;
}

spl_autoload_register(
	static function ( string $requested_class ): void {
		$namespace = __NAMESPACE__ . '\\';

		if ( ! str_starts_with( $requested_class, $namespace ) ) {
			return;
		}

		$class_name = substr( $requested_class, strlen( $namespace ) );
		if ( false === $class_name || str_contains( $class_name, '\\' ) ) {
			return;
		}

		$file_path = __DIR__ . '/includes/class-' . strtolower( str_replace( '_', '-', $class_name ) ) . '.php';
		if ( is_readable( $file_path ) ) {
			require_once $file_path;
		}
	}
);

( new Bridge() )->register_aliases();

// Register translations before the base plugin initializes at priority 10.
add_action(
	'plugins_loaded',
	static function (): void {
		( new Bridge() )->register();
	},
	5
);
