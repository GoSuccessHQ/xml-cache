<?php
/**
 * Cache statistics read endpoint.
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
 * Returns sitemap cache statistics (URL count, cache status).
 */
final class Read extends API_Endpoint_Base {

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
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'callback' ),
				'args'                => array(),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	/**
	 * Return cache statistics.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response {
		$api_response = new API_Response();

		$cached    = get_transient( XML_Sitemap_Repository::TRANSIENT_KEY );
		$is_cached = false !== $cached && is_array( $cached );
		$url_count = $is_cached ? count( $cached ) : 0;

		$api_response->set_success( true );
		$api_response->set_data(
			array(
				'url_count' => $url_count,
				'is_cached' => $is_cached,
			)
		);

		return rest_ensure_response( $api_response->to_array() );
	}
}
