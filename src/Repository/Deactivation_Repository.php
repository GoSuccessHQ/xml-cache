<?php
/**
 * Detects plugin deactivation context and performs cleanup tasks.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository;

defined( 'ABSPATH' ) || exit;

use GoSuccess\XML_Cache\Configuration\Plugin_Configuration;

/**
 * Repository for deactivation operations.
 */
final class Deactivation_Repository {
	/**
	 * Plugin basename used to match deactivation actions.
	 *
	 * @var string
	 */
	private static string $plugin_basename;

	/**
	 * Inject config and initialize the plugin basename.
	 *
	 * @param Plugin_Configuration $plugin_configuration Plugin configuration.
	 */
	public function __construct(
		private Plugin_Configuration $plugin_configuration
	) {
		self::$plugin_basename = $this->plugin_configuration->get_basename();
	}

	/**
	 * Flush rewrite rules on deactivation.
	 */
	public function deactivation(): void {
		flush_rewrite_rules();
	}

	/**
	 * Determine if the current request is deactivating this plugin.
	 *
	 * Note: Nonce verification for bulk/single deactivations is handled by core,
	 * this method only inspects request intent and compares plugin basenames.
	 *
	 * @return bool True if current action deactivates this plugin.
	 */
	public static function is_running(): bool {
		// Must be on the plugins screen.
		if ( ! isset( $GLOBALS['pagenow'] ) || 'plugins.php' !== $GLOBALS['pagenow'] ) {
			return false;
		}

		// WP can send the action in action or action2 (top/bottom bulk selectors) via POST or GET.
		$raw_action = filter_input( INPUT_POST, 'action' )
			?? filter_input( INPUT_POST, 'action2' )
			?? filter_input( INPUT_GET, 'action' )
			?? filter_input( INPUT_GET, 'action2' );
		$action     = is_string( $raw_action ) ? sanitize_text_field( $raw_action ) : '';

		// Single deactivation: ?action=deactivate&plugin=<basename>.
		if ( 'deactivate' === $action ) {
			$raw_plugin = filter_input( INPUT_GET, 'plugin' );
			if ( ! is_string( $raw_plugin ) ) {
				return false;
			}
			$plugin = urldecode( sanitize_text_field( $raw_plugin ) );

			return self::$plugin_basename === $plugin; // Yoda not required when comparing two variables.
		}

		// Bulk deactivation: ?action=deactivate-selected&checked[]=<basename>...
		if ( 'deactivate-selected' === $action ) {
			$raw_checked = filter_input( INPUT_POST, 'checked', FILTER_DEFAULT, FILTER_REQUIRE_ARRAY );
			$checked     = is_array( $raw_checked ) ? $raw_checked : array();

			foreach ( $checked as $p ) {
				if ( is_string( $p ) ) {
					$p = urldecode( sanitize_text_field( wp_unslash( $p ) ) );
					if ( self::$plugin_basename === $p ) {
						return true;
					}
				}
			}

			return false;
		}

		return false;
	}
}
