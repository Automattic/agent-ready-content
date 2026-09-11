<?php
/**
 * Integration with the PRC Datasets plugin.
 *
 * @package PRC_Bridge
 */

namespace Agent_Ready_Content\PRC_Bridge;

/**
 * Add assigned PRC datasets to document frontmatter.
 */
class Datasets_Integration {
	/**
	 * Register the integration.
	 */
	public function register(): void {
		add_filter( 'prc_markdown_for_agents_frontmatter', array( $this, 'add_datasets_to_frontmatter' ), 10, 2 );
	}

	/**
	 * Add dataset names and URLs when the post has dataset terms.
	 *
	 * @param array<string, mixed> $data Frontmatter data.
	 * @param \WP_Post             $post Post being converted.
	 * @return array<string, mixed>
	 */
	public function add_datasets_to_frontmatter( array $data, \WP_Post $post ): array {
		if ( ! taxonomy_exists( 'datasets' )
			|| ( ! function_exists( '\TDS\get_related_post' ) && ! function_exists( '\PRC\TDS\get_related_post' ) ) ) {
			return $data;
		}

		$terms = wp_get_post_terms( $post->ID, 'datasets' );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return $data;
		}

		$datasets = array();
		foreach ( $terms as $term ) {
			$url = get_term_link( $term );
			if ( is_wp_error( $url ) ) {
				continue;
			}
			$datasets[] = array(
				'name' => html_entity_decode( $term->name, ENT_QUOTES | ENT_HTML5, 'UTF-8' ),
				'url'  => $url,
			);
		}

		if ( ! empty( $datasets ) ) {
			$data['datasets'] = $datasets;
		}

		return $data;
	}
}
