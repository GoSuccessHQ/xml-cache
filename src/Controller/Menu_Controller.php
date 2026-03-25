<?php
/**
 * Admin menu controller.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Controller;

defined( 'ABSPATH' ) || exit;

use GoSuccess\XML_Cache\Configuration\Plugin_Configuration;
use GoSuccess\XML_Cache\Repository\Menu_Repository;

/**
 * Registers admin menus and plugin action links.
 */
final class Menu_Controller {
	/**
	 * Constructor.
	 *
	 * @param Plugin_Configuration $plugin_configuration Config.
	 * @param Menu_Repository      $menu_repository      Repo.
	 */
	public function __construct(
		private Plugin_Configuration $plugin_configuration,
		private Menu_Repository $menu_repository
	) {
		// Register the menu and action links.
		add_action(
			'admin_menu',
			array( $this->menu_repository, 'menu' )
		);

		// Add action links to the plugin's action links.
		add_filter(
			'plugin_action_links_' . $this->plugin_configuration->get_basename(),
			array( $this->menu_repository, 'add_action_links' ),
			10,
			4
		);
	}
}
