<?php
/**
 * Main plugin bootstrap class.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache;

use GoSuccess\XML_Cache\CLI\CLI_Command;
use GoSuccess\XML_Cache\Configuration\Plugin_Configuration;
use GoSuccess\XML_Cache\Controller\Activation_Controller;
use GoSuccess\XML_Cache\Controller\API_Controller;
use GoSuccess\XML_Cache\Controller\Deactivation_Controller;
use GoSuccess\XML_Cache\Controller\Menu_Controller;
use GoSuccess\XML_Cache\Controller\Meta_Box_Controller;
use GoSuccess\XML_Cache\Controller\Rewrite_Rules_Controller;
use GoSuccess\XML_Cache\Controller\Script_Controller;
use GoSuccess\XML_Cache\Controller\Uninstall_Controller;
use GoSuccess\XML_Cache\Repository\Activation_Repository;
use GoSuccess\XML_Cache\Repository\API\V1\Admin\API_Repository;
use GoSuccess\XML_Cache\Repository\Deactivation_Repository;
use GoSuccess\XML_Cache\Repository\Menu_Repository;
use GoSuccess\XML_Cache\Repository\Meta_Box_Repository;
use GoSuccess\XML_Cache\Repository\Rewrite_Rules_Repository;
use GoSuccess\XML_Cache\Repository\Script_Repository;
use GoSuccess\XML_Cache\Repository\Uninstall_Repository;
use GoSuccess\XML_Cache\Repository\XML_Sitemap_Repository;

/**
 * Initializes the XML Cache plugin and registers all services.
 */
final class Plugin {

	/**
	 * Singleton instance reference.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Initializes the plugin and registers services.
	 */
	public function __construct() {
		$this->register_cache_invalidation_hooks();
		$this->register_cli_commands();

		$config = new Plugin_Configuration(
			file: XML_CACHE_FILE,
			slug: 'xml_cache',
			title: 'XML Cache',
			support_url: 'https://wordpress.org/support/plugin/xml-cache/',
			review_url: 'https://wordpress.org/support/plugin/xml-cache/reviews/#new-post',
			github_url: 'https://github.com/GoSuccess-GmbH/xml-cache',
			rest_api_namespace: API_Repository::$namespace,
		);

		// Repositories.
		$activation_repository    = new Activation_Repository();
		$deactivation_repository  = new Deactivation_Repository( $config );
		$uninstall_repository     = new Uninstall_Repository();
		$menu_repository          = new Menu_Repository( $config );
		$script_repository        = new Script_Repository( $config, $menu_repository );
		$rewrite_rules_repository = new Rewrite_Rules_Repository( $config );
		$meta_box_repository      = new Meta_Box_Repository();
		$xml_sitemap_repository   = new XML_Sitemap_Repository();
		$api_repository           = new API_Repository();

		// Controllers.
		new Activation_Controller( $config, $activation_repository );
		new Deactivation_Controller( $config, $deactivation_repository );
		new Uninstall_Controller( $config, $uninstall_repository );
		new Menu_Controller( $config, $menu_repository );
		new Script_Controller( $script_repository );
		new Rewrite_Rules_Controller( $rewrite_rules_repository );
		new Meta_Box_Controller( $meta_box_repository );
		new API_Controller( $api_repository );
	}

	/**
	 * Retrieve (and lazily create) singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks that invalidate the sitemap transient cache on content changes.
	 */
	private function register_cache_invalidation_hooks(): void {
		$invalidate = array( XML_Sitemap_Repository::class, 'invalidate_cache' );

		add_action( 'save_post', $invalidate );
		add_action( 'delete_post', $invalidate );
		add_action( 'created_term', $invalidate );
		add_action( 'edited_term', $invalidate );
		add_action( 'delete_term', $invalidate );
		add_action( 'activated_plugin', $invalidate );
		add_action( 'deactivated_plugin', $invalidate );
	}

	/**
	 * Register WP-CLI commands when running in CLI context.
	 */
	private function register_cli_commands(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'xml-cache', CLI_Command::class );
		}
	}
}
