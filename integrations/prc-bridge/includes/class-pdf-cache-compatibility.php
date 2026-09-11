<?php
/**
 * Purge the parent-based PRC PDF routes in addition to document URLs.
 *
 * @package PRC_Bridge
 */

namespace Agent_Ready_Content\PRC_Bridge;

use Agent_Ready_Content\Markdown_Cache_Invalidator;
use PRC\Platform\PDF_Extraction\Content_Type;

/**
 * Keep PRC PDF extraction routes fresh when an extraction or parent changes.
 */
class PDF_Cache_Compatibility {
	/** Register post and extraction metadata invalidation. */
	public function register(): void {
		add_action( 'pre_post_update', array( $this, 'purge_routes' ) );
		add_action( 'before_delete_post', array( $this, 'purge_routes' ) );
		add_action( 'save_post', array( $this, 'purge_routes' ) );
		foreach ( array( 'added_post_meta', 'updated_post_meta', 'deleted_post_meta' ) as $hook ) {
			add_action( $hook, array( $this, 'extraction_meta_changed' ), 10, 3 );
		}
	}

	/**
	 * Invalidate routes when metadata exposed in extraction Markdown changes.
	 *
	 * @param mixed  $meta_id Metadata row ID or IDs.
	 * @param int    $post_id Post ID.
	 * @param string $key     Metadata key.
	 */
	public function extraction_meta_changed( $meta_id, int $post_id, string $key ): void {
		unset( $meta_id );
		$document_keys = array( '_ocr_provider_used', '_ocr_confidence_score', '_extraction_date', '_pdf_source_url' );
		if ( in_array( $key, $document_keys, true ) ) {
			$this->purge_routes( $post_id );
		}
	}

	/**
	 * Purge the extraction and text routes belonging to a PDF parent.
	 *
	 * @param int $post_id Changed post ID.
	 */
	public function purge_routes( int $post_id ): void {
		if ( ! class_exists( Content_Type::class ) || wp_is_post_revision( $post_id ) ) {
			return;
		}

		$post = get_post( $post_id );
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		if ( Content_Type::get_post_type() === $post->post_type ) {
			Markdown_Cache_Invalidator::invalidate_post( $post_id );
			$parent_id = (int) $post->post_parent;
		} else {
			// phpcs:ignore WordPressVIPMinimum.Functions.RestrictedFunctions.get_posts_get_posts -- Query is limited to one ID and keeps filters enabled.
			$children  = get_posts(
				array(
					'post_type'        => Content_Type::get_post_type(),
					'post_parent'      => $post_id,
					'post_status'      => 'any',
					'numberposts'      => 1,
					'fields'           => 'ids',
					'suppress_filters' => false,
				)
			);
			$parent_id = $children ? $post_id : 0;
		}

		if ( 0 === $parent_id ) {
			return;
		}

		$url = get_permalink( $parent_id );
		if ( ! is_string( $url ) || '' === $url ) {
			return;
		}

		foreach ( array( Content_Type::get_url_slug(), 'text' ) as $suffix ) {
			$route = trailingslashit( $url ) . $suffix;
			wpvip_purge_edge_cache_for_url( $route );
			wpvip_purge_edge_cache_for_url( trailingslashit( $route ) );
		}
	}
}
