# XML Cache

![XML Cache banner](./.wordpress-org/banner-772x250.png)

A lightweight WordPress plugin that generates an XML sitemap tailored for cache warming. Perfect for caching plugins like LiteSpeed Cache: the sitemap includes all relevant pages — including paginated archives — so your cache can warm up completely and fast.

## Features
- Single sitemap for cache warming at `/cache.xml`.
- Includes posts, pages, categories, tags, archives, and public custom post types — with pagination.
- Enable/disable each section (posts, pages, categories, tags, archives, CPTs) in the plugin settings.
- Per-post/page opt-out via meta box.
- Multilingual support for WPML, Polylang, and TranslatePress.
- Auto-regeneration: the sitemap rebuilds automatically whenever the cache is cleared.
- Admin bar with quick access to open, copy, and clear the sitemap.
- WP-CLI commands: `wp xml-cache status` and `wp xml-cache flush`.

## Requirements
- WordPress ≥ 6.4
- PHP ≥ 8.2

## Install & Use
- Install via "Plugins > Add New" or upload the ZIP and activate.
- Configure under "Settings > XML Cache"; open/copy the sitemap from there.
- The sitemap is available at `/cache.xml`.

## Screenshots

![Settings screen](./.wordpress-org/screenshot-1.png)

![Post meta box](./.wordpress-org/screenshot-2.png)

## Support & Feedback
Found a bug or have a feature request? Please open an issue on GitHub.

## License
GPL-3.0-or-later. See the license header in the plugin file.