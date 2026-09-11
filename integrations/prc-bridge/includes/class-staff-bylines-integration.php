<?php
/**
 * Integration with the PRC Staff Bylines plugin.
 *
 * @package PRC_Bridge
 */

namespace Agent_Ready_Content\PRC_Bridge;

use PRC\Platform\Staff_Bylines\Bylines;

/**
 * Supply document authors from PRC Staff Bylines.
 */
class Staff_Bylines_Integration {
	/**
	 * Register the integration.
	 */
	public function register(): void {
		add_filter( 'prc_markdown_for_agents_authors', array( $this, 'get_authors_from_bylines' ), 10, 2 );
	}

	/**
	 * Return all assigned staff, former-staff, and guest bylines.
	 *
	 * @param array<int, array<string, string>> $authors Existing author entries.
	 * @param \WP_Post                         $post    Post being converted.
	 * @return array<int, array<string, string>>
	 */
	public function get_authors_from_bylines( array $authors, \WP_Post $post ): array {
		if ( ! class_exists( Bylines::class ) ) {
			return $authors;
		}

		$staff_data = ( new Bylines( $post->ID ) )->format( 'array' );
		if ( is_wp_error( $staff_data ) || ! is_array( $staff_data ) ) {
			return $authors;
		}

		$result = array();
		foreach ( $staff_data as $staff ) {
			if ( ! is_array( $staff ) || empty( $staff['name'] ) ) {
				continue;
			}

			$entry = array( 'name' => (string) $staff['name'] );
			if ( ! empty( $staff['job_title'] ) ) {
				$entry['job_title'] = (string) $staff['job_title'];
			}
			if ( ! empty( $staff['link'] ) ) {
				$entry['link'] = (string) $staff['link'];
			}
			$result[] = $entry;
		}

		return ! empty( $result ) ? $result : $authors;
	}
}
