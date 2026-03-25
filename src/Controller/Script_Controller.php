<?php
/**
 * Script controller.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Controller;

defined( 'ABSPATH' ) || exit;

use GoSuccess\XML_Cache\Repository\Script_Repository;

/**
 * Wires script enqueue hooks.
 */
final class Script_Controller {

	/**
	 * Constructor.
	 *
	 * @param Script_Repository $script_repository Script repository.
	 */
	public function __construct(
		private Script_Repository $script_repository
	) {
		add_action(
			'admin_enqueue_scripts',
			array( $this->script_repository, 'admin_scripts' )
		);

		add_action(
			'enqueue_block_editor_assets',
			array( $this->script_repository, 'block_editor_assets' )
		);
	}
}
