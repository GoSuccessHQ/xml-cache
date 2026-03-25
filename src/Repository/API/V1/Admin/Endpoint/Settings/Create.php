<?php
/**
 * Admin Settings create endpoint.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository\API\V1\Admin\Endpoint\Settings;

use Exception;
use GoSuccess\XML_Cache\Base\API_Endpoint_Base;
use GoSuccess\XML_Cache\Model\API_Response;
use GoSuccess\XML_Cache\Repository\API\V1\Admin\API_Repository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * Class Create
 *
 * Handles the create operation for the settings endpoint.
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
			Settings_Repository::$route,
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'callback' ),
				'permission_callback' => array( $this, 'permission_callback' ),
				'args'                => array(
					'posts_enabled'              => array(
						'type'              => 'boolean',
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'custom_post_types_enabled'  => array(
						'type'              => 'boolean',
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'categories_enabled'         => array(
						'type'              => 'boolean',
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'custom_taxonomies_enabled'  => array(
						'type'              => 'boolean',
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'tags_enabled'               => array(
						'type'              => 'boolean',
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'authors_enabled'            => array(
						'type'              => 'boolean',
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'post_type_archives_enabled' => array(
						'type'              => 'boolean',
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'date_archives_enabled'      => array(
						'type'              => 'boolean',
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
					'homepage_enabled'           => array(
						'type'              => 'boolean',
						'required'          => true,
						'sanitize_callback' => 'rest_sanitize_boolean',
					),
				),
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
			$options = array(
				'posts_enabled'              => $request->get_param( 'posts_enabled' ),
				'custom_post_types_enabled'  => $request->get_param( 'custom_post_types_enabled' ),
				'categories_enabled'         => $request->get_param( 'categories_enabled' ),
				'custom_taxonomies_enabled'  => $request->get_param( 'custom_taxonomies_enabled' ),
				'tags_enabled'               => $request->get_param( 'tags_enabled' ),
				'authors_enabled'            => $request->get_param( 'authors_enabled' ),
				'post_type_archives_enabled' => $request->get_param( 'post_type_archives_enabled' ),
				'date_archives_enabled'      => $request->get_param( 'date_archives_enabled' ),
				'homepage_enabled'           => $request->get_param( 'homepage_enabled' ),
			);
			update_option( 'xml_cache_settings', $options );

			\GoSuccess\XML_Cache\Repository\XML_Sitemap_Repository::invalidate_cache();

			$api_response->set_success( true );
			$api_response->set_data( $options );
		} catch ( Exception $e ) {
			$api_response->set_success( false );
			$api_response->set_message( $e->getMessage() );
		}

		return rest_ensure_response( $api_response->to_array() );
	}
}
