<?php
/**
 * Handles uninstall cleanup for the plugin.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Uninstall repository.
 */
final class Uninstall_Repository {
	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Callback for uninstall hook.
	 */
	public static function uninstall(): void {
		if ( Deactivation_Repository::is_running() ) {
			return;
		}

		Meta_Box_Repository::delete_all();
	}
}
