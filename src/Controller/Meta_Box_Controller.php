<?php
/**
 * Meta box controller.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Controller;

defined( 'ABSPATH' ) || exit;

use GoSuccess\XML_Cache\Repository\Meta_Box_Repository;

/**
 * Wires meta box related hooks.
 */
final class Meta_Box_Controller {
	/**
	 * Constructor.
	 *
	 * @param Meta_Box_Repository $meta_box_repository Meta repository.
	 */
	public function __construct(
		private Meta_Box_Repository $meta_box_repository
	) {
		add_action(
			'init',
			array( $this->meta_box_repository, 'add_meta' )
		);

		add_action(
			'add_meta_boxes',
			array( $this->meta_box_repository, 'add_classic_meta_box' )
		);

		add_action(
			'save_post',
			array( $this->meta_box_repository, 'save_post' ),
			10,
			3
		);
	}
}
