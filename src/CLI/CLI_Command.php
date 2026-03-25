<?php
/**
 * WP-CLI commands for XML Cache.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\CLI;

use GoSuccess\XML_Cache\Repository\XML_Sitemap_Repository;
use WP_CLI;

/**
 * Manage the XML Cache sitemap.
 */
final class CLI_Command {

	/**
	 * Show sitemap cache status and URL count.
	 *
	 * ## EXAMPLES
	 *
	 *     wp xml-cache status
	 *
	 * @subcommand status
	 */
	public function status(): void {
		$cached    = get_transient( 'xml_cache_sitemap' );
		$is_cached = false !== $cached && is_array( $cached );

		if ( $is_cached ) {
			WP_CLI::success(
				sprintf( 'Sitemap is cached with %s URLs.', number_format_i18n( count( $cached ) ) )
			);
		} else {
			WP_CLI::log( 'Sitemap cache is empty. It will be generated on the next request.' );
		}
	}

	/**
	 * Regenerate the sitemap cache.
	 *
	 * Invalidates the current cache and rebuilds it immediately.
	 *
	 * ## EXAMPLES
	 *
	 *     wp xml-cache regenerate
	 *
	 * @subcommand regenerate
	 */
	public function regenerate(): void {
		XML_Sitemap_Repository::invalidate_cache();

		$sitemap = new XML_Sitemap_Repository();
		$sitemap->collect_urls();
		set_transient( 'xml_cache_sitemap', $sitemap->sitemap_urls );

		WP_CLI::success(
			sprintf( 'Sitemap regenerated with %s URLs.', number_format_i18n( count( $sitemap->sitemap_urls ) ) )
		);
	}

	/**
	 * Clear the sitemap cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp xml-cache flush
	 *
	 * @subcommand flush
	 */
	public function flush(): void {
		XML_Sitemap_Repository::invalidate_cache();
		WP_CLI::success( 'Sitemap cache flushed.' );
	}
}
