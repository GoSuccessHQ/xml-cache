=== XML Cache ===

Contributors: gosuccess
Tags: xml, cache, sitemap, litespeed cache, pagespeed
Requires at least: 6.4
Tested up to: 7.0
Stable tag: 2.1.1
Requires PHP: 8.2
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Generates an XML sitemap for cache plugins.

== Description ==

This plugin creates an XML sitemap with all posts, pages, categories, archives and tag pages, regardless of whether they are excluded from search engine indexing or not.

The advantage over a normal sitemap is that no pages are excluded from the sitemap. You can specify the XML cache sitemap in your cache plugin, such as [LiteSpeed Cache](https://de.wordpress.org/plugins/litespeed-cache/), to fully warm up your website cache.

== Screenshots ==

1. XML Cache Menu & Settings.
2. Enable or disable XML cache for a specific post or page.

== Frequently Asked Questions ==

= What is the URL to the sitemap? =

The sitemap can be accessed at /cache.xml. You can also simply click on the "Open Sitemap" button in the settings to open the sitemap and copy the URL from the browser address bar.

= How can I exclude posts and pages from the sitemap? =

Open the post or page you want to exclude. You will find the option to disable the XML cache on the right.

= The XML Cache toggle does not appear for my custom post type. What can I do? =

The block editor requires the custom post type to declare support for `custom-fields`. If you registered the post type yourself, add it like this:

`'supports' => array( 'title', 'editor', 'custom-fields' )`

If the post type is registered by another plugin, you can add support in your theme's `functions.php`:

`add_post_type_support( 'your_post_type', 'custom-fields' );`

== Installation ==

The easiest way to install XML Cache is by visiting your **Plugins > Add New** menu. Search for "XML Cache" and install the first result you see there.

To install with the zip file downloaded from this page:

1. Login to your WordPress dashboard
2. Visit the **Plugins > Add New** menu
3. Click the **Upload Plugin** button at the top
4. In the upload form that appears, click the **Choose file** button and select the **xml-cache.zip** file you downloaded here
5. Click the **Install Now** button
6. Once the page reloads, click the blue **Activate** link
7. Done.

== Changelog ==

= 2.1.1 =
* Fixed TypeError in block editor when a custom post type does not support custom-fields.
* Added FAQ entry for custom post type custom-fields requirement.

= 2.1.0 =
* Multilingual support for Polylang, WPML, and TranslatePress.
* Admin bar integration with live URL count and quick actions.
* WP-CLI commands: status and flush.
* Site Health debug info section.
* Auto-regeneration on cache invalidation.
* Cache statistics on the admin settings page.
* 5 new URL types: categories, tags, archives, custom post types, and attachments.
* Per-request throttling to prevent redundant cache invalidations.
* Responsive admin UI with container queries and pill-style stats badge.
* Removed Symfony DI for a lighter footprint.
* Updated all dependencies to latest versions.
* Added direct file access protection to all PHP files.
* Simplified GitHub workflows using 10up deploy actions.
* Bumped minimum WordPress version to 6.4.
* Tested up to WordPress 7.0.

= 2.0.0 =
* Complete rewrite using PSR-4 autoloading and Composer.
* New React-based admin settings page.
* Custom post type support.
* REST API for settings and cache management.
* Refactored URL collection and sitemap pagination.
* WPCS compliance with strict types, docblocks, and sanitization.
* Requires PHP 8.2.

= 1.2.1 =
* Fixed "Undefined variable $numpage".

= 1.2.0 =
* XML cache now works if no permalinks are enabled.
* Paginated categories, tag pages, blog page and frontpage are now also included in the XML sitemap.
* Added blueprint for a live preview of the plugin.
* Various optimizations.

= 1.1.0 =
* Paginated posts and pages are now also included in the XML sitemap.

= 1.0.0 =
* Initial release.