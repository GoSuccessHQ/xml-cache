<?php
/**
 * WP-CLI commands for XML Cache.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\CLI;

defined( 'ABSPATH' ) || exit;

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
		$cached    = get_transient( XML_Sitemap_Repository::TRANSIENT_KEY );
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
	 * Flush and regenerate the sitemap cache.
	 *
	 * ## EXAMPLES
	 *
	 *     wp xml-cache flush
	 *
	 * @subcommand flush
	 */
	public function flush(): void {
		XML_Sitemap_Repository::invalidate_cache();

		$cached = get_transient( XML_Sitemap_Repository::TRANSIENT_KEY );

		WP_CLI::success(
			sprintf( 'Sitemap cache flushed and regenerated with %s URLs.', number_format_i18n( is_array( $cached ) ? count( $cached ) : 0 ) )
		);
	}
}
