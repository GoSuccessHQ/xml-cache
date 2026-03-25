<?php
/**
 * API controller.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Controller;

defined( 'ABSPATH' ) || exit;

use GoSuccess\XML_Cache\Repository\API\V1\Admin\API_Repository;

/**
 * Wires REST API endpoints.
 */
final class API_Controller {

	/**
	 * Constructor.
	 *
	 * @param API_Repository $api_repository API repository.
	 */
	public function __construct(
		private API_Repository $api_repository
	) {
		add_action(
			'rest_api_init',
			array( $this->api_repository, 'register_endpoints' )
		);
	}
}
