<?php
/**
 * Admin XML Sitemap URL read endpoint.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository\API\V1\Admin\Endpoint\XML_Sitemap_URL;

use Exception;
use GoSuccess\XML_Cache\Base\API_Endpoint_Base;
use GoSuccess\XML_Cache\Model\API_Response;
use GoSuccess\XML_Cache\Repository\API\V1\Admin\API_Repository;
use GoSuccess\XML_Cache\Repository\XML_Sitemap_Repository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Read
 *
 * Handles the read operation for the XML Sitemap URL endpoint.
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
			XML_Sitemap_URL_Repository::$route,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'callback' ),
				'args'                => array(),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	/**
	 * Callback for the ping endpoint.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response {
		$api_response = new API_Response();

		try {
			$home_url     = home_url( '/' );
			$pretty_links = (bool) get_option( 'permalink_structure' );
			$sitemap_url  = $pretty_links
				? (string) $home_url . ltrim( XML_Sitemap_Repository::SITEMAP_PATH, '/' )
				: add_query_arg( 'xml_cache', 'true', $home_url );

			$api_response->set_success( true );
			$api_response->set_data( array( 'sitemap_url' => $sitemap_url ) );
		} catch ( Exception $e ) {
			$api_response->set_success( false );
			$api_response->set_message( $e->getMessage() );
		}

		return \rest_ensure_response( $api_response->to_array() );
	}
}
