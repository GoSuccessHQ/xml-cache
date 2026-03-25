<?php
/**
 * Deactivation controller.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Controller;

defined( 'ABSPATH' ) || exit;

use GoSuccess\XML_Cache\Configuration\Plugin_Configuration;
use GoSuccess\XML_Cache\Repository\Deactivation_Repository;

/**
 * Handles plugin deactivation wiring.
 */
final class Deactivation_Controller {
	/**
	 * Constructor.
	 *
	 * @param Plugin_Configuration    $plugin_configuration Config.
	 * @param Deactivation_Repository $deactivation_repository Repository.
	 */
	public function __construct(
		private Plugin_Configuration $plugin_configuration,
		private Deactivation_Repository $deactivation_repository
	) {
		register_deactivation_hook(
			$this->plugin_configuration->get_file(),
			array( $this->deactivation_repository, 'deactivation' )
		);
	}
}
