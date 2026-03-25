<?php
/**
 * Collects and renders XML sitemap URLs for the plugin endpoint.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository;

/**
 * Repository to assemble sitemap URLs and render XML.
 */
final class XML_Sitemap_Repository {
	/**
	 * Collected sitemap URLs.
	 *
	 * @var array<int,string>
	 */
	public array $sitemap_urls = array();

	/**
	 * Constructor kept intentionally light; heavy lifting is deferred.
	 */
	public function __construct() {}

	/**
	 * Collect URLs based on saved settings. Call this at runtime, not on bootstrap.
	 */
	public function collect_urls(): void {
		$option = get_option( 'xml_cache_settings', false );

		if ( false === $option ) {
			return;
		}

		// Backwards compatibility: unwrap nested array from v1.x.
		if ( isset( $option[0] ) && is_array( $option[0] ) ) {
			$option = $option[0];
		}

		if ( ! empty( $option['posts_enabled'] ) ) {
			// Only core posts/pages; CPTs are handled via separate toggle below.
			$this->get_post_urls();
		}

		// Custom Post Types: default to enabled if not explicitly set (backwards compatibility).
		$cpt_enabled = isset( $option['custom_post_types_enabled'] ) ? ! empty( $option['custom_post_types_enabled'] ) : true;
		if ( $cpt_enabled ) {
			$this->get_custom_post_type_urls();
		}

		if ( ! empty( $option['categories_enabled'] ) ) {
			$this->get_category_urls();
		}

		if ( ! empty( $option['archives_enabled'] ) ) {
			$this->get_archive_urls();
		}

		if ( ! empty( $option['tags_enabled'] ) ) {
			$this->get_tag_urls();
		}
	}

	/**
	 * Collect post and page URLs.
	 */
	private function get_post_urls(): void {
		$post_ids = get_posts(
			array(
				'numberposts' => -1,
				'fields'      => 'ids',
				'orderby'     => 'ID',
				'post_status' => 'publish',
				'post_type'   => array( 'post', 'page' ),
			)
		);

		$this->get_urls( 'get_permalink', $post_ids );
	}

	/**
	 * Collect URLs for all public, non-builtin custom post types (CPTs).
	 *
	 * This method intentionally excludes core types like 'post' and 'page'.
	 * It reuses the general permalink + pagination logic in get_urls().
	 */
	private function get_custom_post_type_urls(): void {
		$post_types = get_post_types(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'names'
		);

		if ( empty( $post_types ) || ! is_array( $post_types ) ) {
			return;
		}

		// Exclude attachments defensively if registered as public by a plugin.
		$post_types = array_values( array_diff( $post_types, array( 'attachment' ) ) );

		if ( empty( $post_types ) ) {
			return;
		}

		$post_ids = get_posts(
			array(
				'numberposts' => -1,
				'fields'      => 'ids',
				'orderby'     => 'ID',
				'post_status' => 'publish',
				'post_type'   => $post_types,
			)
		);

		$this->get_urls( 'get_permalink', $post_ids );
	}

	/**
	 * Collect category URLs.
	 */
	private function get_category_urls(): void {
		$category_ids = get_categories(
			array(
				'fields'  => 'ids',
				'orderby' => 'id',
			)
		);

		$this->get_urls( 'get_category_link', $category_ids );
	}

	/**
	 * Collect tag URLs.
	 */
	private function get_tag_urls(): void {
		$tag_ids = get_tags(
			array(
				'fields'  => 'ids',
				'orderby' => 'term_id',
			)
		);

		$this->get_urls( 'get_tag_link', $tag_ids );
	}

	/**
	 * Collect archive URLs.
	 */
	private function get_archive_urls(): void {
		global $wpdb;

		$months = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`
			FROM {$wpdb->posts}
			WHERE post_type = 'post' AND post_status = 'publish'
			ORDER BY post_date ASC"
		);

		if ( ! empty( $months ) ) {
			foreach ( $months as $row ) {
				$this->sitemap_urls[] = get_month_link( (int) $row->year, (int) $row->month );
			}
		}
	}

	/**
	 * Resolve URLs for given IDs using a permalink-like callable.
	 *
	 * @param callable $permalink_callable Function to resolve a URL by ID.
	 * @param array    $url_ids            IDs to resolve.
	 */
	private function get_urls( callable $permalink_callable, array $url_ids ): void {
		if ( ! is_callable( $permalink_callable ) || empty( $url_ids ) ) {
			return;
		}

		// Prime the metadata cache for all post IDs in a single query.
		if ( 'get_permalink' === $permalink_callable ) {
			update_meta_cache( 'post', $url_ids );
		}

		$permalinks_structure = get_option( 'permalink_structure' );
		$permalinks_enabled   = ! empty( $permalinks_structure );
		$page_for_posts     = absint( get_option( 'page_for_posts' ) );
		$posts_per_page     = absint( get_option( 'posts_per_page' ) );

		foreach ( $url_ids as $id ) {
			$is_post_cache_enabled = Meta_Box_Repository::is_post_cache_enabled( $id );

			if ( ! $is_post_cache_enabled ) {
				continue;
			}

			$permalink = $permalink_callable( $id );

			if ( ! empty( $permalink ) ) {
				$this->sitemap_urls[] = $permalink;

				$numpage = 1;
				$context = 'archive';

				if ( 'get_permalink' === $permalink_callable ) { // Singular or posts page.
					if ( $page_for_posts === $id ) {
						// Posts page behaves like an archive for pagination.
						$total_posts = (int) wp_count_posts( 'post' )->publish;
						$numpage     = (int) ceil( $total_posts / max( 1, $posts_per_page ) );
						$context     = 'archive';
					} else {
						// Multipage singular content.
						$postdata = generate_postdata( $id );
						if ( false !== $postdata && 1 === $postdata['multipage'] ) {
							$numpage = (int) $postdata['numpages'];
						}
						$context = 'singular';
					}
				} else {
					// Category or tag archives — use term count instead of fetching all posts.
					$term = get_term( $id );
					if ( $term && ! is_wp_error( $term ) ) {
						$numpage = (int) ceil( (int) $term->count / max( 1, $posts_per_page ) );
					}
					$context = 'archive';
				}

				while ( $numpage > 1 ) {
					$this->sitemap_urls[] = $this->build_paginated_url( (string) $permalink, (bool) $permalinks_enabled, (int) $numpage, (string) $context );
					--$numpage;
				}
			}
		}
	}

	/**
	 * Build a paginated URL based on permalink structure and context.
	 *
	 * @param string $permalink           Base permalink.
	 * @param bool   $permalinks_enabled  Whether pretty permalinks are enabled.
	 * @param int    $page                Page number.
	 * @param string $context             'singular' for posts/pages, 'archive' for blog/category/tag.
	 * @return string                     Final paginated URL.
	 */
	private function build_paginated_url( string $permalink, bool $permalinks_enabled, int $page, string $context ): string {
		if ( $permalinks_enabled ) {
			$base = trailingslashit( $permalink );
			if ( 'singular' === $context ) {
				// Singular multipage posts use /2/ style.
				return user_trailingslashit( (string) $base . $page );
			}
			// Archives (blog, category, tag) use /page/2/ style.
			return user_trailingslashit( (string) $base . 'page/' . $page );
		}

		// Fallback to query args when pretty permalinks are disabled.
		$param = ( 'singular' === $context ) ? 'page' : 'paged';
		return (string) add_query_arg( $param, $page, $permalink );
	}

	/**
	 * Render the XML sitemap and send appropriate headers.
	 */
	public static function render(): void {
		$sitemap = new self();
		$sitemap->collect_urls();
		$sitemap_urls = $sitemap->sitemap_urls;

		$xml    = '';
		$writer = null;

		if ( class_exists( '\\XMLWriter' ) ) {
			$writer = new \XMLWriter();
			$writer->openMemory();
			$writer->startDocument( '1.0', 'UTF-8' );
			$writer->startElement( 'urlset' );
			$writer->writeAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );

			if ( ! empty( $sitemap_urls ) ) {
				foreach ( $sitemap_urls as $url ) {
					if ( ! is_string( $url ) || '' === $url ) {
						continue;
					}

					$loc = \esc_url_raw( $url );
					if ( '' === $loc ) {
						continue;
					}

					$writer->startElement( 'url' );
					$writer->writeElement( 'loc', $loc );
					$writer->endElement(); // url.
				}
			}

			$writer->endElement(); // urlset.
			$xml = $writer->outputMemory();
		} else {
			// Fallback if ext-xmlwriter is not available.
			$xml  = '<?xml version="1.0" encoding="UTF-8"?>';
			$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

			if ( ! empty( $sitemap_urls ) ) {
				foreach ( $sitemap_urls as $url ) {
					if ( ! is_string( $url ) || '' === $url ) {
						continue;
					}
					$loc = \esc_url_raw( $url );
					if ( '' === $loc ) {
						continue;
					}
					$xml .= '<url><loc>' . htmlspecialchars( $loc, ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '</loc></url>';
				}
			}

			$xml .= '</urlset>';
		}

		if ( ! headers_sent() ) {
			header( 'Content-Type: application/xml; charset=UTF-8' );
			header( 'X-Robots-Tag: noindex, nofollow' );
		}

		echo $xml; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- XML is constructed safely above.
	}
}
