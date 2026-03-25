<?php
/**
 * Site Health info provider.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository;

/**
 * Adds an XML Cache section to the Site Health Info page.
 */
final class Site_Health_Repository {

	/**
	 * Filter callback for 'debug_information'.
	 *
	 * @param array $info Existing debug information.
	 * @return array Modified debug information.
	 */
	public function add_debug_information( array $info ): array {
		$settings  = XML_Sitemap_Repository::resolve_settings();
		$transient = get_transient( XML_Sitemap_Repository::TRANSIENT_KEY );
		$is_cached = false !== $transient && is_array( $transient );
		$url_count = $is_cached ? count( $transient ) : 0;

		$fields = array(
			'sitemap_url' => array(
				'label' => __( 'Sitemap URL', 'xml-cache' ),
				'value' => home_url( XML_Sitemap_Repository::SITEMAP_PATH ),
			),
			'cache_status' => array(
				'label' => __( 'Cache status', 'xml-cache' ),
				'value' => $is_cached
					? sprintf( __( 'Cached (%s URLs)', 'xml-cache' ), number_format_i18n( $url_count ) )
					: __( 'Not cached', 'xml-cache' ),
			),
		);

		foreach ( $settings as $key => $enabled ) {
			$fields[ $key ] = array(
				'label' => $key,
				'value' => $enabled ? __( 'Enabled', 'xml-cache' ) : __( 'Disabled', 'xml-cache' ),
			);
		}

		$info['xml-cache'] = array(
			'label'  => 'XML Cache',
			'fields' => $fields,
		);

		return $info;
	}
}
