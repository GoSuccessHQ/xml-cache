<?php
/**
 * Enqueue admin and editor assets.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache\Repository;

use GoSuccess\XML_Cache\Configuration\Plugin_Configuration;

/**
 * Repository to enqueue scripts/styles.
 */
final class Script_Repository {
	/**
	 * Constructor.
	 *
	 * @param Plugin_Configuration $plugin_configuration Plugin config.
	 * @param Menu_Repository      $menu_repository      Menu repository.
	 */
	public function __construct(
		private Plugin_Configuration $plugin_configuration,
		private Menu_Repository $menu_repository
	) {}

	/**
	 * Enqueue admin assets on plugin pages.
	 *
	 * @param string $hook_suffix Current admin page hook.
	 */
	public function admin_scripts( string $hook_suffix ): void {
		if ( $hook_suffix !== $this->menu_repository->hook_suffix ) {
			return;
		}

        wp_enqueue_style( 'wp-components' );
        wp_enqueue_style( 'wp-block-editor' );

		$asset_file = include $this->plugin_configuration->get_path() . 'assets/admin/index.asset.php';

		wp_enqueue_script(
			'xml-cache',
			$this->plugin_configuration->get_url() . 'assets/admin/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_set_script_translations( 'xml-cache', 'xml-cache' );

        wp_localize_script(
            'xml-cache',
            'xmlCache',
            [
                'restApiNamespace' => $this->plugin_configuration->get_rest_api_namespace(),
                'supportUrl' => $this->plugin_configuration->get_support_url(),
                'reviewUrl' => $this->plugin_configuration->get_review_url(),
                'githubUrl' => $this->plugin_configuration->get_github_url(),
            ]
        );
	}

	/**
	 * Enqueue block editor assets.
	 */
	public function block_editor_assets(): void {
		$asset_file = include $this->plugin_configuration->get_path() . 'assets/settings-panel/index.asset.php';

		wp_enqueue_script(
			'xml-cache-settings-panel',
			$this->plugin_configuration->get_url() . 'assets/settings-panel/index.js',
			$asset_file['dependencies'],
			$asset_file['version'],
			true
		);

		wp_set_script_translations( 'xml-cache-settings-panel', 'xml-cache' );
	}
}
