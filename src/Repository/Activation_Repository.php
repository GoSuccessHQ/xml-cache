<?php
/**
 * Activation tasks repository.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository;

defined( 'ABSPATH' ) || exit;

/**
 * Handles plugin activation logic.
 */
final class Activation_Repository {
	/**
	 * Constructor.
	 */
	public function __construct() {}

	/**
	 * Run on plugin activation.
	 */
	public function activation(): void {
		if ( Deactivation_Repository::is_running() ) {
			return;
		}

		add_option( 'xml_cache_settings', self::get_default_settings() );

		add_rewrite_rule( '^cache\.xml$', 'index.php?xml_cache=true', 'top' );
		flush_rewrite_rules();
	}

	/**
	 * Default plugin settings.
	 *
	 * @return array Default settings structure.
	 */
	public static function get_default_settings(): array {
		return array(
			'posts_enabled'              => true,
			'custom_post_types_enabled'  => true,
			'categories_enabled'         => true,
			'custom_taxonomies_enabled'  => true,
			'tags_enabled'               => true,
			'authors_enabled'            => true,
			'post_type_archives_enabled' => true,
			'date_archives_enabled'      => true,
			'homepage_enabled'           => true,
			'admin_bar_enabled'          => true,
		);
	}
}
