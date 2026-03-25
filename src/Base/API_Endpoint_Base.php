<?php
/**
 * Base class for REST API endpoints in the XML Cache plugin.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Base;

defined( 'ABSPATH' ) || exit;

use GoSuccess\XML_Cache\Contracts\API_Endpoint_Contract;
use WP_REST_Request;

/**
 * Provides common behaviors for API endpoints.
 */
abstract class API_Endpoint_Base implements API_Endpoint_Contract {
	/**
	 * Construct the endpoint and register routes.
	 */
	public function __construct() {
		$this->register();
	}

	/**
	 * Default capability check for admin-only endpoints.
	 *
	 * @param WP_REST_Request $request Request instance.
	 * @return bool Whether the current user can manage options.
	 */
	public function permission_callback( WP_REST_Request $request ): bool { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		return current_user_can( 'manage_options' );
	}
}
