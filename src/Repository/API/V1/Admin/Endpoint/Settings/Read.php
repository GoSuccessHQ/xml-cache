<?php
/**
 * Admin Settings read endpoint.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository\API\V1\Admin\Endpoint\Settings;

defined( 'ABSPATH' ) || exit;

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
			Settings_Repository::$route,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'callback' ),
				'args'                => array(),
				'permission_callback' => array( $this, 'permission_callback' ),
			)
		);
	}

	/**
	 * Callback for the settings endpoint.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return WP_REST_Response
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response {
		$api_response = new API_Response();

		try {
			$options = XML_Sitemap_Repository::resolve_settings();

			$api_response->set_success( true );
			$api_response->set_data( $options );
		} catch ( Exception $e ) {
			$api_response->set_success( false );
			$api_response->set_message( $e->getMessage() );
		}

		return rest_ensure_response( $api_response->to_array() );
	}
}
