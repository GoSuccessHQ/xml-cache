<?php
/**
 * Rewrite rules repository for XML sitemap routing.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository;

use GoSuccess\XML_Cache\Configuration\Plugin_Configuration;

/**
 * Storage and helpers for sitemap rewrite rules and template selection.
 */
final class Rewrite_Rules_Repository {
	/**
	 * Constructor.
	 *
	 * @param Plugin_Configuration $plugin_configuration Plugin config.
	 */
	public function __construct(
		private Plugin_Configuration $plugin_configuration
	) {}

	/**
	 * Add rewrite rules for the sitemap endpoint.
	 */
	public function add_rewrite_rules(): void {
		add_rewrite_rule(
			'^cache\.xml$',
			'index.php?xml_cache=true',
			'top'
		);
	}

	/**
	 * Add custom query var used by the plugin.
	 *
	 * @param array $query_vars Query vars.
	 * @return array
	 */
	public function add_query_vars( array $query_vars ): array {
		$query_vars[] = 'xml_cache';
		return $query_vars;
	}

	/**
	 * Swap in the XML template when our query var is present.
	 *
	 * @param string $template Template path.
	 * @return string
	 */
	public function add_template( string $template ): string {
		$xml_cache = get_query_var( 'xml_cache' );

		if ( ! $xml_cache ) {
			return $template;
		}

		return $this->plugin_configuration->get_path() . 'src/Template/XML_Sitemap_Template.php';
	}

	/**
	 * Prevent canonical redirect when serving our sitemap.
	 *
	 * @param string $redirect_url Redirect target URL.
	 * @param string $request_url  Original request URL.
	 * @return string
	 */
	public function redirect( string $redirect_url, string $request_url ): string {
		$xml_cache = get_query_var( 'xml_cache' );

		if ( $xml_cache ) {
			return $request_url;
		}

		return $redirect_url;
	}
}
