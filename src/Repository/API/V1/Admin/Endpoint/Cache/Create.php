<?php
/**
 * Cache generation (regenerate) endpoint.
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
 * Regenerates the sitemap cache via REST API.
 */
final class Create extends API_Endpoint_Base {

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
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'callback' ),
				'args'                => array(),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	/**
	 * Regenerate the sitemap cache and return updated stats.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response {
		$api_response = new API_Response();

		XML_Sitemap_Repository::invalidate_cache();

		$sitemap = new XML_Sitemap_Repository();
		$sitemap->collect_urls();
		set_transient( XML_Sitemap_Repository::TRANSIENT_KEY, $sitemap->sitemap_urls );

		$api_response->set_success( true );
		$api_response->set_data(
			array(
				'url_count' => count( $sitemap->sitemap_urls ),
				'is_cached' => true,
			)
		);

		return rest_ensure_response( $api_response->to_array() );
	}
}
