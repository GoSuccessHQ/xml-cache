<?php
/**
 * Admin API repository that registers endpoint groups.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository\API\V1\Admin;

defined( 'ABSPATH' ) || exit;

use GoSuccess\XML_Cache\Repository\API\V1\Admin\Endpoint\Cache\Cache_Repository;
use GoSuccess\XML_Cache\Repository\API\V1\Admin\Endpoint\Settings\Settings_Repository;
use GoSuccess\XML_Cache\Repository\API\V1\Admin\Endpoint\XML_Sitemap_URL\XML_Sitemap_URL_Repository;

/**
 * Class API_Repository
 *
 * Handles the API repository for the XML Cache plugin.
 */
final class API_Repository {

	/**
	 * API namespace.
	 *
	 * @var string
	 */
	public static string $namespace = 'xml-cache/v1/admin';

	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Register admin endpoints.
	 */
	public function register_endpoints(): void {
		new Cache_Repository();
		new Settings_Repository();
		new XML_Sitemap_URL_Repository();
	}
}
