<?php
/**
 * Cache endpoint repository.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository\API\V1\Admin\Endpoint\Cache;

/**
 * Registers cache-related REST API endpoints.
 */
final class Cache_Repository {

	/**
	 * The route for the cache endpoint.
	 *
	 * @var string
	 */
	public static string $route = 'cache';

	/**
	 * Constructor to initialize the cache endpoint.
	 */
	public function __construct() {
		new Delete();
	}
}
