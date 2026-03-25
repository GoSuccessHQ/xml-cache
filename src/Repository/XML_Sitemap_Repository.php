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
	 * Maximum number of URLs per sitemap (Google Sitemaps protocol limit).
	 */
	private const MAX_URLS_PER_SITEMAP = 50000;

	/**
	 * Transient key for cached sitemap data.
	 */
	private const TRANSIENT_KEY = 'xml_cache_sitemap';

	/**
	 * Transient expiration in seconds.
	 *
	 * Set to 0 (no expiration) because the cache is explicitly invalidated
	 * on every content change via save_post, delete_post, and term hooks.
	 */
	private const TRANSIENT_EXPIRATION = 0;

	/**
	 * Collected sitemap URL entries.
	 *
	 * Each entry is an array with keys 'loc' (string) and optionally 'lastmod' (string, W3C date).
	 *
	 * @var array<int,array{loc:string,lastmod?:string}>
	 */
	public array $sitemap_urls = array();

	/**
	 * Constructor kept intentionally light; heavy lifting is deferred.
	 */
	public function __construct() {}

	/**
	 * Resolve and normalize plugin settings from the database.
	 *
	 * Handles v1.x backwards compatibility (nested array unwrapping, key migration)
	 * and fills any missing keys with defaults.
	 *
	 * @return array<string,bool> Normalized settings array.
	 */
	public static function resolve_settings(): array {
		$option = get_option( 'xml_cache_settings', false );

		if ( false === $option ) {
			return Activation_Repository::get_default_settings();
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
		return array_merge( Activation_Repository::get_default_settings(), $option );
	}

	/**
	 * Collect URLs based on saved settings. Call this at runtime, not on bootstrap.
	 */
	public function collect_urls(): void {
		$option = self::resolve_settings();

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
		$posts = get_posts(
			array(
				'numberposts' => -1,
				'orderby'     => 'ID',
				'post_status' => 'publish',
				'post_type'   => array( 'post', 'page' ),
				'lang'        => '',
			)
		);

		$this->get_urls_from_posts( $posts );
	}

	/**
	 * Collect URLs for all public, non-builtin custom post types (CPTs).
	 *
	 * This method intentionally excludes core types like 'post' and 'page'.
	 * It reuses the general permalink + pagination logic in get_urls_from_posts().
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

		$posts = get_posts(
			array(
				'numberposts' => -1,
				'orderby'     => 'ID',
				'post_status' => 'publish',
				'post_type'   => $post_types,
				'lang'        => '',
			)
		);

		$this->get_urls_from_posts( $posts );
	}

	/**
	 * Collect category URLs.
	 */
	private function get_category_urls(): void {
		$categories = get_categories(
			array(
				'orderby' => 'id',
				'lang'    => '',
			)
		);

		$this->get_urls_from_terms( $categories );
	}

	/**
	 * Collect tag URLs.
	 */
	private function get_tag_urls(): void {
		$tags = get_tags(
			array(
				'orderby' => 'term_id',
				'lang'    => '',
			)
		);

		if ( ! is_array( $tags ) ) {
			return;
		}

		$this->get_urls_from_terms( $tags );
	}

	/**
	 * Collect date-based archive URLs (yearly, monthly, daily).
	 */
	private function get_date_archive_urls(): void {
		global $wpdb;

		$dates = $wpdb->get_results( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->prepare(
				"SELECT DISTINCT YEAR(post_date) AS `year`, MONTH(post_date) AS `month`, DAY(post_date) AS `day`
				FROM {$wpdb->posts}
				WHERE post_type = %s AND post_status = %s
				ORDER BY post_date ASC",
				'post',
				'publish'
			)
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
				$this->sitemap_urls[] = array( 'loc' => get_year_link( $year ) );
			}

			// Monthly archive (deduplicated).
			$month_key = $year . '-' . $month;
			if ( ! isset( $months[ $month_key ] ) ) {
				$months[ $month_key ] = true;
				$this->sitemap_urls[] = array( 'loc' => get_month_link( $year, $month ) );
			}

			// Daily archive.
			$this->sitemap_urls[] = array( 'loc' => get_day_link( $year, $month, $day ) );
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

			$this->sitemap_urls[] = array( 'loc' => $permalink );

			// Pagination for author archives.
			$total_posts = (int) count_user_posts( $author_id, '', true );
			$numpage     = (int) ceil( $total_posts / max( 1, $posts_per_page ) );

			while ( $numpage > 1 ) {
				$this->sitemap_urls[] = array( 'loc' => $this->build_paginated_url( $permalink, $permalinks_enabled, $numpage, 'archive' ) );
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
			$terms = get_terms(
				array(
					'taxonomy'   => $taxonomy,
					'hide_empty' => true,
					'lang'       => '',
				)
			);

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			$this->get_urls_from_terms( $terms );
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

			$this->sitemap_urls[] = array( 'loc' => $permalink );

			// Pagination for post type archives.
			$total_posts = (int) wp_count_posts( $post_type->name )->publish;
			$numpage     = (int) ceil( $total_posts / max( 1, $posts_per_page ) );

			while ( $numpage > 1 ) {
				$this->sitemap_urls[] = array( 'loc' => $this->build_paginated_url( $permalink, $permalinks_enabled, $numpage, 'archive' ) );
				--$numpage;
			}
		}
	}

	/**
	 * Collect the homepage URL and translated variants.
	 */
	private function get_homepage_url(): void {
		$this->sitemap_urls[] = array( 'loc' => home_url( '/' ) );

		// Include translated homepages when a multilingual plugin is active.
		if ( function_exists( 'pll_languages_list' ) && function_exists( 'pll_home_url' ) ) {
			$existing_locs = array_column( $this->sitemap_urls, 'loc' );
			foreach ( pll_languages_list() as $lang ) {
				$translated_home = pll_home_url( $lang );
				if ( ! empty( $translated_home ) && ! in_array( $translated_home, $existing_locs, true ) ) {
					$this->sitemap_urls[] = array( 'loc' => $translated_home );
					$existing_locs[]      = $translated_home;
				}
			}
		}
	}

	/**
	 * Resolve URLs from WP_Post objects with opt-out check, lastmod, and pagination.
	 *
	 * @param array $posts Array of WP_Post objects.
	 */
	private function get_urls_from_posts( array $posts ): void {
		if ( empty( $posts ) ) {
			return;
		}

		// Prime the metadata cache for all post IDs in a single query.
		$post_ids = wp_list_pluck( $posts, 'ID' );
		update_meta_cache( 'post', $post_ids );

		$permalinks_enabled = ! empty( get_option( 'permalink_structure' ) );
		$page_for_posts     = absint( get_option( 'page_for_posts' ) );
		$posts_per_page     = absint( get_option( 'posts_per_page' ) );

		foreach ( $posts as $post ) {
			$id = (int) $post->ID;

			if ( ! Meta_Box_Repository::is_post_cache_enabled( $id ) ) {
				continue;
			}

			$permalink = get_permalink( $post );

			if ( empty( $permalink ) || is_wp_error( $permalink ) ) {
				continue;
			}

			$lastmod = ! empty( $post->post_modified_gmt ) && '0000-00-00 00:00:00' !== $post->post_modified_gmt
				? wp_date( 'c', strtotime( $post->post_modified_gmt ) )
				: null;

			$entry = array( 'loc' => $permalink );
			if ( $lastmod ) {
				$entry['lastmod'] = $lastmod;
			}
			$this->sitemap_urls[] = $entry;

			$numpage = 1;
			$context = 'archive';

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

			while ( $numpage > 1 ) {
				$paged_entry = array( 'loc' => $this->build_paginated_url( (string) $permalink, (bool) $permalinks_enabled, (int) $numpage, (string) $context ) );
				if ( $lastmod ) {
					$paged_entry['lastmod'] = $lastmod;
				}
				$this->sitemap_urls[] = $paged_entry;
				--$numpage;
			}
		}
	}

	/**
	 * Resolve URLs from WP_Term objects with pagination.
	 *
	 * @param array $terms Array of WP_Term objects.
	 */
	private function get_urls_from_terms( array $terms ): void {
		if ( empty( $terms ) ) {
			return;
		}

		$permalinks_enabled = ! empty( get_option( 'permalink_structure' ) );
		$posts_per_page     = absint( get_option( 'posts_per_page' ) );

		foreach ( $terms as $term ) {
			if ( ! is_object( $term ) ) {
				continue;
			}

			$permalink = get_term_link( $term );

			if ( empty( $permalink ) || is_wp_error( $permalink ) ) {
				continue;
			}

			$this->sitemap_urls[] = array( 'loc' => $permalink );

			$numpage = (int) ceil( (int) $term->count / max( 1, $posts_per_page ) );

			while ( $numpage > 1 ) {
				$this->sitemap_urls[] = array( 'loc' => $this->build_paginated_url( (string) $permalink, (bool) $permalinks_enabled, (int) $numpage, 'archive' ) );
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
	 *
	 * Uses transient caching. For sites with >50,000 URLs, renders a sitemap index
	 * at /cache.xml with sub-sitemaps at /cache.xml?page=N.
	 */
	public static function render(): void {
		$page = isset( $_GET['page'] ) ? absint( $_GET['page'] ) : 0; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		$cached = get_transient( self::TRANSIENT_KEY );

		if ( false === $cached || ! is_array( $cached ) ) {
			$sitemap = new self();
			$sitemap->collect_urls();
			$cached = $sitemap->sitemap_urls;
			set_transient( self::TRANSIENT_KEY, $cached, self::TRANSIENT_EXPIRATION );
		}

		$total = count( $cached );
		$pages = (int) ceil( $total / self::MAX_URLS_PER_SITEMAP );

		if ( ! headers_sent() ) {
			header( 'Content-Type: application/xml; charset=UTF-8' );
			header( 'X-Robots-Tag: noindex, nofollow' );
		}

		// Large site: render sitemap index when no page param, or render specific page.
		if ( $pages > 1 && 0 === $page ) {
			echo self::build_sitemap_index( $pages ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			return;
		}

		// Single sitemap or specific page of a large sitemap.
		$slice = $cached;
		if ( $pages > 1 && $page > 0 ) {
			$offset = ( $page - 1 ) * self::MAX_URLS_PER_SITEMAP;
			$slice  = array_slice( $cached, $offset, self::MAX_URLS_PER_SITEMAP );
		}

		echo self::build_sitemap_xml( $slice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * Build a sitemap index XML string pointing to sub-sitemaps.
	 *
	 * @param int $pages Number of sub-sitemap pages.
	 * @return string XML string.
	 */
	private static function build_sitemap_index( int $pages ): string {
		$base_url = home_url( '/cache.xml' );

		if ( class_exists( '\\XMLWriter' ) ) {
			$writer = new \XMLWriter();
			$writer->openMemory();
			$writer->startDocument( '1.0', 'UTF-8' );
			$writer->startElement( 'sitemapindex' );
			$writer->writeAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );

			for ( $i = 1; $i <= $pages; $i++ ) {
				$writer->startElement( 'sitemap' );
				$writer->writeElement( 'loc', \esc_url_raw( add_query_arg( 'page', $i, $base_url ) ) );
				$writer->endElement();
			}

			$writer->endElement();
			return $writer->outputMemory();
		}

		$xml  = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
		for ( $i = 1; $i <= $pages; $i++ ) {
			$loc  = \esc_url_raw( add_query_arg( 'page', $i, $base_url ) );
			$xml .= '<sitemap><loc>' . htmlspecialchars( $loc, ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '</loc></sitemap>';
		}
		$xml .= '</sitemapindex>';

		return $xml;
	}

	/**
	 * Build a sitemap XML string from URL entries.
	 *
	 * @param array<int,array{loc:string,lastmod?:string}> $entries URL entries.
	 * @return string XML string.
	 */
	private static function build_sitemap_xml( array $entries ): string {
		if ( class_exists( '\\XMLWriter' ) ) {
			$writer = new \XMLWriter();
			$writer->openMemory();
			$writer->startDocument( '1.0', 'UTF-8' );
			$writer->startElement( 'urlset' );
			$writer->writeAttribute( 'xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9' );

			foreach ( $entries as $entry ) {
				$loc = is_array( $entry ) ? ( $entry['loc'] ?? '' ) : (string) $entry;
				if ( '' === $loc ) {
					continue;
				}

				$loc = \esc_url_raw( $loc );
				if ( '' === $loc ) {
					continue;
				}

				$writer->startElement( 'url' );
				$writer->writeElement( 'loc', $loc );

				if ( is_array( $entry ) && ! empty( $entry['lastmod'] ) ) {
					$writer->writeElement( 'lastmod', $entry['lastmod'] );
				}

				$writer->endElement();
			}

			$writer->endElement();
			return $writer->outputMemory();
		}

		// Fallback if ext-xmlwriter is not available.
		$xml  = '<?xml version="1.0" encoding="UTF-8"?>';
		$xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';

		foreach ( $entries as $entry ) {
			$loc = is_array( $entry ) ? ( $entry['loc'] ?? '' ) : (string) $entry;
			if ( '' === $loc ) {
				continue;
			}
			$loc = \esc_url_raw( $loc );
			if ( '' === $loc ) {
				continue;
			}

			$xml .= '<url><loc>' . htmlspecialchars( $loc, ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '</loc>';
			if ( is_array( $entry ) && ! empty( $entry['lastmod'] ) ) {
				$xml .= '<lastmod>' . htmlspecialchars( $entry['lastmod'], ENT_QUOTES | ENT_XML1, 'UTF-8' ) . '</lastmod>';
			}
			$xml .= '</url>';
		}

		$xml .= '</urlset>';
		return $xml;
	}

	/**
	 * Invalidate the cached sitemap. Should be called on content changes.
	 */
	public static function invalidate_cache(): void {
		delete_transient( self::TRANSIENT_KEY );
	}
}
