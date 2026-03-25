<?php
/**
 * Main plugin bootstrap class.
 *
 * @package xml-cache
 */

declare( strict_types=1 );

namespace GoSuccess\XML_Cache;

use GoSuccess\XML_Cache\CLI\CLI_Command;
use GoSuccess\XML_Cache\Configuration\Plugin_Configuration;
use GoSuccess\XML_Cache\Controller\Activation_Controller;
use GoSuccess\XML_Cache\Controller\API_Controller;
use GoSuccess\XML_Cache\Controller\Deactivation_Controller;
use GoSuccess\XML_Cache\Controller\Menu_Controller;
use GoSuccess\XML_Cache\Controller\Meta_Box_Controller;
use GoSuccess\XML_Cache\Controller\Rewrite_Rules_Controller;
use GoSuccess\XML_Cache\Controller\Script_Controller;
use GoSuccess\XML_Cache\Controller\Uninstall_Controller;
use GoSuccess\XML_Cache\Repository\Activation_Repository;
use GoSuccess\XML_Cache\Repository\API\V1\Admin\API_Repository;
use GoSuccess\XML_Cache\Repository\Deactivation_Repository;
use GoSuccess\XML_Cache\Repository\Menu_Repository;
use GoSuccess\XML_Cache\Repository\Meta_Box_Repository;
use GoSuccess\XML_Cache\Repository\Rewrite_Rules_Repository;
use GoSuccess\XML_Cache\Repository\Script_Repository;
use GoSuccess\XML_Cache\Repository\Site_Health_Repository;
use GoSuccess\XML_Cache\Repository\Uninstall_Repository;
use GoSuccess\XML_Cache\Repository\XML_Sitemap_Repository;

/**
 * Initializes the XML Cache plugin and registers all services.
 */
final class Plugin {

	/**
	 * Singleton instance reference.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Initializes the plugin and registers services.
	 */
	public function __construct() {
		$this->register_cache_invalidation_hooks();
		$this->register_cli_commands();
		$this->register_admin_bar();
		$this->register_site_health();

		$config = new Plugin_Configuration(
			file: XML_CACHE_FILE,
			slug: 'xml_cache',
			title: 'XML Cache',
			support_url: 'https://wordpress.org/support/plugin/xml-cache/',
			review_url: 'https://wordpress.org/support/plugin/xml-cache/reviews/#new-post',
			github_url: 'https://github.com/GoSuccess-GmbH/xml-cache',
			rest_api_namespace: API_Repository::$namespace,
		);

		// Repositories.
		$activation_repository    = new Activation_Repository();
		$deactivation_repository  = new Deactivation_Repository( $config );
		$uninstall_repository     = new Uninstall_Repository();
		$menu_repository          = new Menu_Repository( $config );
		$script_repository        = new Script_Repository( $config, $menu_repository );
		$rewrite_rules_repository = new Rewrite_Rules_Repository( $config );
		$meta_box_repository      = new Meta_Box_Repository();
		$xml_sitemap_repository   = new XML_Sitemap_Repository();
		$api_repository           = new API_Repository();

		// Controllers.
		new Activation_Controller( $config, $activation_repository );
		new Deactivation_Controller( $config, $deactivation_repository );
		new Uninstall_Controller( $config, $uninstall_repository );
		new Menu_Controller( $config, $menu_repository );
		new Script_Controller( $script_repository );
		new Rewrite_Rules_Controller( $rewrite_rules_repository );
		new Meta_Box_Controller( $meta_box_repository );
		new API_Controller( $api_repository );
	}

	/**
	 * Retrieve (and lazily create) singleton instance.
	 */
	public static function get_instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register hooks that invalidate the sitemap transient cache on content changes.
	 */
	private function register_cache_invalidation_hooks(): void {
		$invalidate = array( XML_Sitemap_Repository::class, 'invalidate_cache' );

		add_action( 'save_post', $invalidate );
		add_action( 'delete_post', $invalidate );
		add_action( 'created_term', $invalidate );
		add_action( 'edited_term', $invalidate );
		add_action( 'delete_term', $invalidate );
		add_action( 'activated_plugin', $invalidate );
		add_action( 'deactivated_plugin', $invalidate );
	}

	/**
	 * Register WP-CLI commands when running in CLI context.
	 */
	private function register_cli_commands(): void {
		if ( defined( 'WP_CLI' ) && \WP_CLI ) {
			\WP_CLI::add_command( 'xml-cache', CLI_Command::class );
		}
	}

	/**
	 * Register admin bar node with flame icon, URL count, and actions submenu.
	 */
	private function register_admin_bar(): void {
		$bar_data = null;

		add_action( 'admin_bar_menu', static function ( \WP_Admin_Bar $admin_bar ) use ( &$bar_data ): void {
			if ( ! current_user_can( 'manage_options' ) ) {
				return;
			}

			$settings = XML_Sitemap_Repository::resolve_settings();

			if ( empty( $settings['admin_bar_enabled'] ) ) {
				return;
			}

			$cached    = get_transient( XML_Sitemap_Repository::TRANSIENT_KEY );
			$is_cached = false !== $cached && is_array( $cached );
			$url_count = $is_cached ? count( $cached ) : 0;

			$icon  = '<img src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHZpZXdCb3g9IjY0IDI2IDM4NCAzODQiPjxwYXRoIGQ9Ik0zMjQuNjQgNzguMDQ0YzYyLjI0OCAyNy44MTcgMTAyLjUxNiA4OS45MTYgMTAyLjUxNiAxNTguMDk3IDAgOTQuOTU5LTc4LjEwOCAxNzMuMTExLTE3My4wNjYgMTczLjE2NWgtLjAxM2MtOTQuOTcyIDAtMTczLjEyMi03OC4xNTEtMTczLjEyMi0xNzMuMTIzIDAtNDQuNTcyIDE3LjIxMy04Ny40NjcgNDguMDI1LTExOS42NzQgMTYuMTQ1IDIyLjU2NyAzNy40NTkgNDAuOTQ1IDYyLjE1NiA1My41OTQuODc1LTU2LjEwNiAyNi43NTktMTA4Ljk4OSA3MC41MjktMTQ0LjEgMTYuNTA5IDIyLjE0MSAzOC4xMDIgMzkuOTkyIDYyLjk1NCA1Mi4wNDF6IiBmaWxsPSIjZjQzODAwIi8+PHBhdGggZD0iTTMwNC4yNjIgMTcwLjI1YzQ0LjI3OCAxOS43ODYgNzIuOTIxIDYzLjk1OCA3Mi45MjEgMTEyLjQ1NiAwIDY3LjU0NC01NS41NTkgMTIzLjEzNC0xMjMuMTAzIDEyMy4xNzJoLS4wMDljLTY3LjU1NCAwLTEyMy4xNDMtNTUuNTg5LTEyMy4xNDMtMTIzLjE0MyAwLTMxLjcwNCAxMi4yNDQtNjIuMjE1IDM0LjE2MS04NS4xMjUgMTEuNDg0IDE2LjA1MiAyNi42NDUgMjkuMTI1IDQ0LjIxMiAzOC4xMjIuNjIyLTM5LjkwOCAxOS4wMzMtNzcuNTI0IDUwLjE2Ny0xMDIuNDk5IDExLjc0MyAxNS43NDkgMjcuMTAyIDI4LjQ0NiA0NC43NzkgMzcuMDE3eiIgZmlsbD0iI2ZmNjQxMCIvPjxwYXRoIGQ9Ik0yNTMuOTcxIDQwMC43MzhoLjE2OWM0OS42MzIgMCA5MC40NzItNDAuODQxIDkwLjQ3Mi05MC40NzIgMC00NS4xOTYtMzMuODY4LTgzLjgwOC03OC42NzktODkuNy0yNC45MDcgMjIuMjg5LTQxLjMyNyA1Mi41NDQtNDYuNDQyIDg1LjU3NC0xOC41ODYtNC41NTQtMzYuMDgtMTIuNzY0LTUxLjQ2LTI0LjE0OS0zLjAwMiA5LjEyNC00LjUzMiAxOC42NjktNC41MzIgMjguMjc1IDAgNDkuNjMxIDQwLjg0IDkwLjQ3MiA5MC40NzIgOTAuNDcyeiIgZmlsbD0iI2ZmYjg1NSIvPjwvc3ZnPg==" style="height:13px;width:13px;vertical-align:middle;padding:6px 4px 6px 0" alt="">';
			$label = sprintf(
				/* translators: %s: number of URLs */
				__( '%s URLs', 'xml-cache' ),
				number_format_i18n( $url_count )
			);

			$sitemap_url = home_url( XML_Sitemap_Repository::SITEMAP_PATH );

			$admin_bar->add_node( array(
				'id'    => 'xml-cache',
				'title' => $icon . '<span class="ab-label">' . esc_html( $label ) . '</span>',
				'href'  => admin_url( 'tools.php?page=xml_cache' ),
				'meta'  => array( 'title' => __( 'XML Cache Settings', 'xml-cache' ) ),
			) );

			$admin_bar->add_node( array(
				'parent' => 'xml-cache',
				'id'     => 'xml-cache-open',
				'title'  => __( 'Open Sitemap', 'xml-cache' ),
				'href'   => $sitemap_url,
				'meta'   => array( 'target' => '_blank' ),
			) );

			$admin_bar->add_node( array(
				'parent' => 'xml-cache',
				'id'     => 'xml-cache-generate',
				'title'  => __( 'Generate Sitemap', 'xml-cache' ),
				'href'   => '#xml-cache-generate',
			) );

			$admin_bar->add_node( array(
				'parent' => 'xml-cache',
				'id'     => 'xml-cache-copy',
				'title'  => __( 'Copy Sitemap URL', 'xml-cache' ),
				'href'   => '#xml-cache-copy',
			) );

			$admin_bar->add_node( array(
				'parent' => 'xml-cache',
				'id'     => 'xml-cache-clear',
				'title'  => __( 'Clear Cache', 'xml-cache' ),
				'href'   => '#xml-cache-clear',
			) );

			$bar_data = array(
				'sitemap_url' => $sitemap_url,
				'rest_url'    => rest_url( API_Repository::$namespace . '/cache' ),
				'nonce'       => wp_create_nonce( 'wp_rest' ),
			);
		}, 100 );

		$print_script = static function () use ( &$bar_data ): void {
			if ( null === $bar_data ) {
				return;
			}
			?>
			<script>
			(function(){
				var c=document.getElementById('wp-admin-bar-xml-cache-copy');
				var d=document.getElementById('wp-admin-bar-xml-cache-clear');
				var g=document.getElementById('wp-admin-bar-xml-cache-generate');
				function updateLabel(el,txt,ms){var a=el.querySelector('a');if(a){var o=a.textContent;a.textContent=txt;setTimeout(function(){a.textContent=o},ms||2000)}}
				function updateCount(n){var l=document.querySelector('#wp-admin-bar-xml-cache .ab-label');if(l){l.textContent=<?php echo wp_json_encode( __( '%s URLs', 'xml-cache' ) ); ?>.replace('%s',n.toLocaleString())}}
				if(c){c.addEventListener('click',function(e){
					e.preventDefault();
					navigator.clipboard.writeText(<?php echo wp_json_encode( $bar_data['sitemap_url'] ); ?>).then(function(){
						updateLabel(c,<?php echo wp_json_encode( __( 'Copied!', 'xml-cache' ) ); ?>);
					});
				})}
				if(d){d.addEventListener('click',function(e){
					e.preventDefault();
					fetch(<?php echo wp_json_encode( $bar_data['rest_url'] ); ?>,{method:'DELETE',credentials:'same-origin',headers:{'X-WP-Nonce':<?php echo wp_json_encode( $bar_data['nonce'] ); ?>}}).then(function(r){return r.json()}).then(function(data){
						if(data.success){updateLabel(d,<?php echo wp_json_encode( __( 'Cleared!', 'xml-cache' ) ); ?>);updateCount(0)}
					});
				})}
				if(g){g.addEventListener('click',function(e){
					e.preventDefault();
					updateLabel(g,<?php echo wp_json_encode( __( 'Generating…', 'xml-cache' ) ); ?>,10000);
					fetch(<?php echo wp_json_encode( $bar_data['rest_url'] ); ?>,{method:'POST',credentials:'same-origin',headers:{'X-WP-Nonce':<?php echo wp_json_encode( $bar_data['nonce'] ); ?>}}).then(function(r){return r.json()}).then(function(data){
						if(data.success){updateLabel(g,<?php echo wp_json_encode( __( 'Done!', 'xml-cache' ) ); ?>);updateCount(data.data.url_count)}
					});
				})}
			})();
			</script>
			<?php
		};

		add_action( 'admin_footer', $print_script );
		add_action( 'wp_footer', $print_script );
	}

	/**
	 * Register Site Health debug information section.
	 */
	private function register_site_health(): void {
		$site_health = new Site_Health_Repository();

		add_filter( 'debug_information', array( $site_health, 'add_debug_information' ) );
	}
}
