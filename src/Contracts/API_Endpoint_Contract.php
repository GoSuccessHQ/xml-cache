<?php
/**
 * API Endpoint contracts for the XML Cache plugin.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Contracts;

defined( 'ABSPATH' ) || exit;

use WP_REST_Request;
use WP_REST_Response;

/**
 * Interface for REST API endpoints used by the XML Cache plugin.
 */
interface API_Endpoint_Contract {

	/**
	 * Initialize and register the endpoint.
	 */
	public function __construct();

	/**
	 * Register the endpoint with the WordPress REST API.
	 *
	 * @return bool True on success, false otherwise.
	 */
	public function register(): bool;

	/**
	 * Handle a request to the endpoint.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_REST_Response A REST response instance.
	 */
	public function callback( WP_REST_Request $request ): WP_REST_Response;

	/**
	 * Permission check for the endpoint.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return bool True if the request has access, false otherwise.
	 */
	public function permission_callback( WP_REST_Request $request ): bool;
}
