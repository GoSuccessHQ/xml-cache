<?php
/**
 * Uninstall controller.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Controller;

use GoSuccess\XML_Cache\Configuration\Plugin_Configuration;
use GoSuccess\XML_Cache\Repository\Uninstall_Repository;

/**
 * Wires uninstall operations.
 */
final class Uninstall_Controller {
	/**
	 * Constructor.
	 *
	 * @param Plugin_Configuration $plugin_configuration Config.
	 * @param Uninstall_Repository $uninstall_repository Repo.
	 */
	public function __construct(
		private Plugin_Configuration $plugin_configuration,
		private Uninstall_Repository $uninstall_repository
	) {
		register_uninstall_hook(
			$this->plugin_configuration->get_file(),
			array( Uninstall_Repository::class, 'uninstall' )
		);
	}
}
