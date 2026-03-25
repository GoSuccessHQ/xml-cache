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

		// Migrate renamed key from v1.x.
		if ( isset( $option['archives_enabled'] ) && ! isset( $option['date_archives_enabled'] ) ) {
			$option['date_archives_enabled'] = $option['archives_enabled'];
			unset( $option['archives_enabled'] );
		}

		// Fill missing keys with defaults for existing installations.
		$option = array_merge( Activation_Repository::get_default_settings(), $option );

		if ( ! empty( $option['posts_enabled'] ) ) {
			$this->get_post_urls();
		}

		if ( ! empty( $option['custom_post_types_enabled'] ) ) {
			$this->get_custom_post_type_urls();
		}

		if ( ! empty( $option['categories_enabled'] ) ) {
			$this->get_category_urls();
		}

		if ( ! empty( $option['custom_taxonomies_enabled'] ) ) {
			$this->get_custom_taxonomy_urls();
		}

		if ( ! empty( $option['tags_enabled'] ) ) {
			$this->get_tag_urls();
		}

		if ( ! empty( $option['authors_enabled'] ) ) {
			$this->get_author_urls();
		}

		if ( ! empty( $option['post_type_archives_enabled'] ) ) {
			$this->get_post_type_archive_urls();
		}

		if ( ! empty( $option['date_archives_enabled'] ) ) {
			$this->get_date_archive_urls();
		}

		if ( ! empty( $option['homepage_enabled'] ) ) {
			$this->get_homepage_url();
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
				'lang'        => '',
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

		$post_ids = get_posts(
			array(
				'numberposts' => -1,
				'fields'      => 'ids',
				'orderby'     => 'ID',
				'post_status' => 'publish',
				'post_type'   => $post_types,
				'lang'        => '',
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
				'lang'    => '',
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
				'lang'    => '',
			)
		);

		$this->get_urls( 'get_tag_link', $tag_ids );
	}

	/**
	 * Collect date-based archive URLs (yearly, monthly, daily).
	 */
	private function get_date_archive_urls(): void {
		global $wpdb;

		$dates = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			"SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAY(post_date) AS `day`
			FROM {$wpdb->posts}
			WHERE post_type = 'post' AND post_status = 'publish'
			ORDER BY post_date ASC"
		);

		if ( empty( $dates ) ) {
			return;
		}

		$years  = array();
		$months = array();

		foreach ( $dates as $row ) {
			$year  = (int) $row->year;
			$month = (int) $row->month;
			$day   = (int) $row->day;

			// Yearly archive (deduplicated).
			if ( ! isset( $years[ $year ] ) ) {
				$years[ $year ] = true;
				$this->sitemap_urls[] = get_year_link( $year );
			}

			// Monthly archive (deduplicated).
			$month_key = $year . '-' . $month;
			if ( ! isset( $months[ $month_key ] ) ) {
				$months[ $month_key ] = true;
				$this->sitemap_urls[] = get_month_link( $year, $month );
			}

			// Daily archive.
			$this->sitemap_urls[] = get_day_link( $year, $month, $day );
		}
	}

	/**
	 * Collect author archive URLs with pagination.
	 */
	private function get_author_urls(): void {
		$authors = get_users(
			array(
				'has_published_posts' => true,
				'fields'             => array( 'ID' ),
			)
		);

		if ( empty( $authors ) ) {
			return;
		}

		$permalinks_enabled = ! empty( get_option( 'permalink_structure' ) );
		$posts_per_page     = absint( get_option( 'posts_per_page' ) );

		foreach ( $authors as $author ) {
			$author_id = (int) $author->ID;
			$permalink = get_author_posts_url( $author_id );

			if ( empty( $permalink ) ) {
				continue;
			}

			$this->sitemap_urls[] = $permalink;

			// Pagination for author archives.
			$total_posts = (int) count_user_posts( $author_id, '', true );
			$numpage     = (int) ceil( $total_posts / max( 1, $posts_per_page ) );

			while ( $numpage > 1 ) {
				$this->sitemap_urls[] = $this->build_paginated_url( $permalink, $permalinks_enabled, $numpage, 'archive' );
				--$numpage;
			}
		}
	}

	/**
	 * Collect URLs for custom (non-builtin) taxonomy term archives with pagination.
	 */
	private function get_custom_taxonomy_urls(): void {
		$taxonomies = get_taxonomies(
			array(
				'public'   => true,
				'_builtin' => false,
			),
			'names'
		);

		if ( empty( $taxonomies ) ) {
			return;
		}

		foreach ( $taxonomies as $taxonomy ) {
			$term_ids = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'fields'     => 'ids',
					'hide_empty' => true,
					'lang'       => '',
				)
			);

			if ( empty( $term_ids ) || is_wp_error( $term_ids ) ) {
				continue;
			}

			$this->get_urls( 'get_term_link', $term_ids );
		}
	}

	/**
	 * Collect post type archive URLs with pagination.
	 */
	private function get_post_type_archive_urls(): void {
		$post_types = get_post_types(
			array(
				'public'      => true,
				'has_archive' => true,
			),
			'objects'
		);

		if ( empty( $post_types ) ) {
			return;
		}

		$permalinks_enabled = ! empty( get_option( 'permalink_structure' ) );
		$posts_per_page     = absint( get_option( 'posts_per_page' ) );

		foreach ( $post_types as $post_type ) {
			$permalink = get_post_type_archive_link( $post_type->name );

			if ( empty( $permalink ) ) {
				continue;
			}

			$this->sitemap_urls[] = $permalink;

			// Pagination for post type archives.
			$total_posts = (int) wp_count_posts( $post_type->name )->publish;
			$numpage     = (int) ceil( $total_posts / max( 1, $posts_per_page ) );

			while ( $numpage > 1 ) {
				$this->sitemap_urls[] = $this->build_paginated_url( $permalink, $permalinks_enabled, $numpage, 'archive' );
				--$numpage;
			}
		}
	}

	/**
	 * Collect the homepage URL when configured as "latest posts".
	 */
	private function get_homepage_url(): void {
		$this->sitemap_urls[] = home_url( '/' );

		// Include translated homepages when a multilingual plugin is active.
		if ( function_exists( 'pll_languages_list' ) && function_exists( 'pll_home_url' ) ) {
			foreach ( pll_languages_list() as $lang ) {
				$translated_home = pll_home_url( $lang );
				if ( ! empty( $translated_home ) && ! in_array( $translated_home, $this->sitemap_urls, true ) ) {
					$this->sitemap_urls[] = $translated_home;
				}
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

		$is_post_callable = 'get_permalink' === $permalink_callable;

		foreach ( $url_ids as $id ) {
			// Only check per-post opt-out for post permalinks, not for term links.
			if ( $is_post_callable ) {
				$is_post_cache_enabled = Meta_Box_Repository::is_post_cache_enabled( $id );

				if ( ! $is_post_cache_enabled ) {
					continue;
				}
			}

			$permalink = $permalink_callable( $id );

			if ( empty( $permalink ) || is_wp_error( $permalink ) ) {
				continue;
			}

			$this->sitemap_urls[] = $permalink;

			$numpage = 1;
			$context = 'archive';

			if ( $is_post_callable ) { // Singular or posts page.
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
				// Category, tag, or custom taxonomy archives — use term count.
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
