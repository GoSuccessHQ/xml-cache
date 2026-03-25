<?php
/**
 * Cache delete (invalidation) endpoint.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository\API\V1\Admin\Endpoint\Cache;

use GoSuccess\XML_Cache\Base\API_Endpoint_Base;
use GoSuccess\XML_Cache\Model\API_Response;
use GoSuccess\XML_Cache\Repository\API\V1\Admin\API_Repository;
use GoSuccess\XML_Cache\Repository\XML_Sitemap_Repository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Handles sitemap cache invalidation via REST API.
 */
final class Delete extends API_Endpoint_Base {

	/**
	 * Register the endpoint.
	 *
	 * @return bool
	 */
	public function register(): bool {
		return register_rest_route(
			API_Repository::$namespace,
			Cache_Repository::$route,
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'callback' ),
				'args'                => array(),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	/**
	 * Invalidate the sitemap cache.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response {
		$api_response = new API_Response();

		XML_Sitemap_Repository::invalidate_cache();

		$api_response->set_success( true );

		return rest_ensure_response( $api_response->to_array() );
	}
}
