<?php
/**
 * Integration with the PRC PDF Extraction plugin.
 *
 * @package PRC_Bridge
 */

namespace Agent_Ready_Content\PRC_Bridge;

use PRC\Platform\PDF_Extraction\Content_Type;

/**
 * Add the published PDF extraction route to document frontmatter.
 */
class PDF_Extraction_Integration {
	/**
	 * Register the integration.
	 */
	public function register(): void {
		add_filter( 'prc_markdown_for_agents_frontmatter', array( $this, 'add_extraction_to_frontmatter' ), 10, 2 );
	}

	/**
	 * Add the extraction URL when a published extraction exists.
	 *
	 * @param array<string, mixed> $data Frontmatter data.
	 * @param \WP_Post             $post Post being converted.
	 * @return array<string, mixed>
	 */
	public function add_extraction_to_frontmatter( array $data, \WP_Post $post ): array {
		if ( ! class_exists( Content_Type::class ) ) {
			return $data;
		}

		$permalink = get_permalink( $post );
		if ( ! is_string( $permalink ) || '' === $permalink ) {
			return $data;
		}

		// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts -- Query is limited to one ID and keeps filters enabled.
		$extraction_ids = get_posts(
			array(
				'post_type'        => Content_Type::get_post_type(),
				'post_parent'      => $post->ID,
				'numberposts'      => 1,
				'post_status'      => 'publish',
				'fields'           => 'ids',
				'suppress_filters' => false,
			)
		);

		if ( empty( $extraction_ids ) ) {
			return $data;
		}

		$url_slug          = Content_Type::get_url_slug();
		$data[ $url_slug ] = untrailingslashit( $permalink ) . '/' . $url_slug;

		return $data;
	}
}
