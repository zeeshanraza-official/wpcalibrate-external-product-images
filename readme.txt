=== WPCalibrate External Product Images ===
Contributors: wpcalibrate
Tags: woocommerce, external product images, remote images, product gallery, variation images
Requires at least: 6.5
Tested up to: 7.1.3
Requires PHP: 8.2
WC requires at least: 8.5
WC tested up to: 11.2.1
Stable tag: 1.0.0
License: GPL-2.0-or-later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Display high-performance external product images, galleries, and variation assets in WooCommerce without bloating your WordPress Media Library.

== Description ==

WPCalibrate External Product Images allows WooCommerce store owners to use externally hosted image URLs for product featured images, multi-image product galleries, and variation images without requiring every image file to be downloaded into the WordPress Media Library.

The extension is designed from the ground up for stores with large catalogs, multi-channel dropshipping operations, CDN-hosted media libraries, and merchants seeking to keep their local server storage lightweight and responsive.

= Key Capabilities =

* **External Featured Images**: Specify an external HTTPS image URL on any simple or variable product. Displays seamlessly across shop archives, single product pages, search results, related products, upsells, cross-sells, widgets, and cart/checkout item thumbnails.
* **External Product Galleries**: Configure multiple external URLs for product galleries with drag-and-drop reordering, keyboard-accessible ordering controls, per-image alt text overrides, and bulk URL input.
* **External Variation Images**: Assign external image URLs to WooCommerce product variations with responsive variation switching on the customer storefront.
* **Direct Remote Mode (Default)**: Delivers image URLs directly to customer browsers without creating unnecessary Media Library attachments, saving gigabytes of server disk space.
* **Optional Local Cache Mode**: Allows store administrators to cache remote images locally into the WordPress Media Library on demand or automatically via background Action Scheduler jobs.
* **SSRF & Network Protection**: Built-in protections against Server-Side Request Forgery (SSRF), link-local IP abuse, private network destinations, and non-image payloads.
* **Browser-Side Broken Image Fallback**: Automatically swaps failing remote images to the configured store placeholder without blocking server rendering or generating infinite error loops.
* **WooCommerce CSV Import/Export**: Native integration with WooCommerce's built-in CSV product importer and exporter using dedicated external image columns.
* **HPOS & Blocks Compatibility**: Declared compatible with WooCommerce High-Performance Order Storage (HPOS) and WooCommerce Cart & Checkout Blocks.

== Requirements ==

* WordPress 6.5 or higher (tested up to 7.1.3)
* WooCommerce 8.5 or higher (tested up to 11.2.1)
* PHP 8.2 or 8.3 minimum (tested with PHP 8.3)
* PHP cURL extension

== Image Precedence & Delivery Modes ==

= Direct Remote Mode (Default) =
In Direct Remote Mode, images are served directly from the remote host URL configured on the product. No local WordPress Media Library attachments are created, and no synchronous server HTTP requests occur during customer storefront rendering.

= Optional Local Cache Mode =
When Local Cache Mode is enabled, external images can be imported into the local WordPress Media Library. The plugin preserves the original external URL in metadata, reuses existing cached attachments when available to prevent duplicate downloads, and enforces strict file size limits and MIME verification.

= Precedence Hierarchy =
1. Cached local attachment copy (when Local Cache Mode is active and attachment exists).
2. Valid external image URL configured for the product or variation.
3. Locally assigned native WooCommerce image attachment.
4. Store fallback image (custom Media Library attachment or standard WooCommerce placeholder).

== CSV Import & Export Format ==

When using WooCommerce's native CSV product tools (Products > Import / Export), the plugin adds the following dedicated columns:

* **External Featured Image**: Full HTTPS image URL for the product or variation.
* **External Image Alt**: Optional alt text override for the external featured image.
* **External Gallery Images**: Multiple external URLs separated by comma (default) or the configured delimiter.
* **External Variation Image**: HTTPS image URL for variation rows.
* **External Variation Image Alt**: Alt text for the variation image.

== Privacy & Data Stored ==

* **Metadata**: External image URLs, alt text overrides, and cached attachment IDs are stored as standard post metadata on products and variations.
* **Global Options**: Plugin configuration is stored in the `wpcalibrate_epi_settings` option.
* **Transients**: Remote host availability responses are cached temporarily in transients to avoid redundant health checks.
* **Uninstall**: By default, user configuration and product metadata are preserved upon plugin deletion. If the administrator enables "Delete plugin data on uninstall" in Settings, all plugin options and metadata are purged upon deletion. Local Media Library files are not deleted.

== Known Limitations ==

* Extremely restrictive remote servers that block hotlinking (referrer checking) may prevent remote images from loading directly in customer browsers unless Local Cache Mode is enabled.
* Direct Remote Mode relies on the remote host remaining online; enabling the Browser-Side Broken Image Fallback ensures broken remote images fall back gracefully to the WooCommerce placeholder.

== Support ==

For documentation, inquiries, or enterprise support:
* Email: support@wpcalibrate.com
* Website: https://wpcalibrate.com
* Marketplace: https://marketplace.wpcalibrate.com/
* Phone / WhatsApp: +44 7474 795976

== Installation ==

1. Upload the `wpcalibrate-external-product-images.zip` archive via Plugins > Add New > Upload Plugin in your WordPress dashboard.
2. Activate the plugin through the 'Plugins' menu.
3. Navigate to WPCalibrate > External Images to configure options.
4. Edit any product to assign external image URLs or import products with external images via WooCommerce CSV.

== Frequently Asked Questions ==

= Does this plugin modify core WooCommerce or WordPress files? =
No. All functionality is built cleanly on public WooCommerce hooks, filters, and Store API extension points without template overrides or core modifications.

= Can I use external images alongside existing local WooCommerce images? =
Yes. If a product does not have an external image configured, its local WooCommerce image and gallery continue to display normally.

= Are remote images downloaded on every page load? =
No. In Direct Remote Mode, images are rendered directly by the customer's browser. Normal storefront page rendering does not perform blocking remote server HTTP requests.

== Changelog ==

= 1.0.0 =
* Initial production release.
* Support for external featured images, multi-image external galleries, and variation images.
* Direct Remote Mode (default) and optional Local Cache Mode with Action Scheduler background jobs.
* SSRF validation and MIME verification for server-side image fetching.
* Browser-side broken image fallback handler.
* Native WooCommerce CSV product import and export integration.
* HPOS and Cart/Checkout Blocks compatibility.
* Isolated WPCalibrate dashboard menu and tabbed administrative interface.
