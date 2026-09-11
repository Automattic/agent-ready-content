<?php
/**
 * Integration with the PRC Report Package plugin.
 *
 * @package PRC_Bridge
 */

namespace Agent_Ready_Content\PRC_Bridge;

/**
 * Add report contents and chapter navigation to served Markdown.
 */
class Report_Package_Integration {
	/**
	 * Register the integration.
	 */
	public function register(): void {
		add_filter( 'prc_markdown_for_agents_after_markdown', array( $this, 'append_report_navigation' ), 10, 2 );
	}

	/**
	 * Add a table of contents to report roots and a next link to chapters.
	 *
	 * @param string   $markdown_body Converted Markdown body.
	 * @param \WP_Post $post          Post being converted.
	 */
	public function append_report_navigation( string $markdown_body, \WP_Post $post ): string {
		if ( function_exists( 'PRC\Platform\Report_Package\is_report_package' )
			&& function_exists( 'PRC\Platform\Report_Package\get_package_materials' )
			&& \PRC\Platform\Report_Package\is_report_package( $post->ID ) ) {
			$materials      = (array) \PRC\Platform\Report_Package\get_package_materials( $post->ID );
			$real_materials = array_filter(
				$materials,
				static function ( $material ): bool {
					return is_array( $material ) && 'printEngineBeta' !== ( $material['type'] ?? '' );
				}
			);
			if ( ! empty( $real_materials ) ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Compatibility requires invoking the legacy PRC hook.
				$toc = apply_filters( 'prc_markdown_for_agents_toc_for_post', '', $post );
				if ( is_string( $toc ) && '' !== $toc ) {
					$markdown_body = $toc . "\n\n" . $markdown_body;
				}
			}
		}

		if ( ! function_exists( 'PRC\Platform\Report_Package\is_chapter_part_of_report_package' )
			|| ! function_exists( 'PRC\Platform\Report_Package\get_pagination' )
			|| ! \PRC\Platform\Report_Package\is_chapter_part_of_report_package( $post->ID ) ) {
			return $markdown_body;
		}

		$pagination = \PRC\Platform\Report_Package\get_pagination( $post->ID );
		$next_post  = is_array( $pagination ) ? ( $pagination['next_post'] ?? null ) : null;
		if ( ! is_array( $next_post ) || empty( $next_post['title'] ) || empty( $next_post['link'] ) ) {
			return $markdown_body;
		}

		$next_url = untrailingslashit( (string) $next_post['link'] ) . '.md';
		$link     = sprintf( '[%s](%s)', $next_post['title'], $next_url );

		return $markdown_body . "\n\n---\n\n**Next:** " . $link;
	}
}
