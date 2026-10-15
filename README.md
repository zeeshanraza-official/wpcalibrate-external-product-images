# WPCalibrate External Product Images

> **Deliver remote WooCommerce product images, galleries, and variation assets with lightning speed and zero media library bloat.**

[![WordPress](https://img.shields.io/badge/WordPress-6.5%20--%207.1.3-blue.svg)](https://wordpress.org)
[![WooCommerce](https://img.shields.io/badge/WooCommerce-8.5%20--%2011.2.1-96588a.svg)](https://woocommerce.com)
[![PHP](https://img.shields.io/badge/PHP-8.2%20--%208.3-777bb4.svg)](https://php.net)
[![HPOS](https://img.shields.io/badge/HPOS-Compatible-success.svg)](https://woocommerce.com)
[![Blocks](https://img.shields.io/badge/WooCommerce%20Blocks-Compatible-success.svg)](https://woocommerce.com)
[![License: GPL v2+](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)

---

## About

**WPCalibrate External Product Images** is a high-performance WooCommerce extension engineered to solve media storage bottlenecks for eCommerce stores. Rather than forcing every product photo and gallery asset into your WordPress Media Library, this plugin intercepts WooCommerce's native image rendering pipeline and streams images directly from external CDNs, Amazon S3, Cloudflare R2, Google Cloud Storage, or third-party supplier servers.

- **Zero Media Library Bloat**: Keep your `wp-content/uploads` lightweight and backups lightning-fast.
- **Direct Remote Delivery**: Zero disk space and zero local thumbnail generation overhead.
- **Enterprise-Grade Security**: Anti-SSRF protection, IP loopback/private range blocking, and MIME validation.
- **Seamless WooCommerce Native Feel**: Supports featured images, multi-image product galleries with drag-and-drop ordering, variable product switching, archive loops, cart thumbnails, and native WooCommerce CSV Import/Export.

---

## Key Features

### 1. External Featured Images
- Assign arbitrary HTTPS image URLs to any standard or custom product.
- Custom alt text controls with automated fallback to product title.
- Real-time admin preview box with status indicators.

### 2. Multi-Image External Galleries
- Unlimited gallery images per product without creating attachment posts.
- Drag-and-drop sortable gallery manager with keyboard-accessible Move Up / Move Down buttons.
- Fully compatible with WooCommerce single product gallery sliders and lightbox.

### 3. Variable Product Overrides
- Individual external image URLs per variation.
- Dynamic variation image switching when customers select attributes on the storefront.
- Full compatibility with Cart and Checkout line item thumbnails.

### 4. Dual Delivery Modes
- **Direct Remote Delivery (Default)**: Delivers remote image URLs straight to customer browsers. Consumes zero local disk storage.
- **Local Cache Mode**: Securely downloads remote images into the local Media Library using Action Scheduler background jobs, eliminating external HTTP requests for repeat views while maintaining hotlinking resilience.

### 5. Multi-Tier Fallback & Client-Side Recovery
- Resolution hierarchy: **Cached Local Media** &rarr; **Direct Remote URL** &rarr; **Native Local Media** &rarr; **Store Placeholder**.
- Browser-side capture observer catches broken remote URLs and silently swaps in your store placeholder with zero server-side HTTP stalls.

### 6. Native WooCommerce CSV Import & Export
- Integrates seamlessly with WooCommerce core **Products &rarr; Import** and **Products &rarr; Export**.
- Dedicated column headers:
  - `External Featured Image URL`
  - `External Featured Image Alt Text`
  - `External Gallery Images` (delimiter-separated)
  - `External Variation Image URL`
  - `External Variation Image Alt Text`

### 7. Modern SaaS Dashboard & Overview
- Clean, responsive dashboard with Overview, General, Image Handling, Cache, Import & Export, License, and Support tabs.
- Live diagnostic cards showing Engine Status, Delivery Mode, HPOS & Blocks status, and Server Environment.
- 3-step interactive Quick Start Guide.

---

## Requirements

| Requirement | Minimum | Tested Up To |
|-------------|---------|--------------|
| **WordPress** | 6.5 | 7.1.3 |
| **WooCommerce** | 8.5 | 11.2.1 |
| **PHP** | 8.2 | 8.3 |
| **HPOS** | Supported | Verified |
| **Cart & Checkout Blocks** | Supported | Verified |

---

## Installation & Quick Start

### Method 1: Upload via WordPress Admin
1. Download the latest `wpcalibrate-external-product-images-1.0.0.zip` from [Releases](https://github.com/zeeshanraza-official/wpcalibrate-external-product-images/releases).
2. Go to **Plugins &rarr; Add New &rarr; Upload Plugin** in your WordPress dashboard.
3. Select the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.

### Method 2: Manual FTP / SFTP Installation
1. Extract the release ZIP file.
2. Upload the `wpcalibrate-external-product-images` folder to your `/wp-content/plugins/` directory.
3. Activate the plugin through the **Plugins** menu in WordPress.

### 3-Step Quick Start
1. **Assign a Remote Image**: Edit any WooCommerce product. In the **External Product Image** metabox, paste your HTTPS image URL and custom alt text. Click **Update**.
2. **Build an External Gallery**: In the **External Product Gallery** box, click **Add Image URL** or **Add Multiple URLs**. Reorder images using drag-and-drop or the Move buttons.
3. **Bulk Update with CSV**: Navigate to **Products &rarr; Import** or **Products &rarr; Export** to manage entire catalogs with dedicated external image columns.

---

## Documentation & Settings

The plugin configuration is located under **WPCalibrate &rarr; External Images**:

- **Overview**: Plugin philosophy, live service metrics, 3-step usage guide, and technical architecture lifecycle.
- **General**: Master engine activation switch, HTTPS-only enforcement, default fallback mode, and data retention on uninstall policy.
- **Image Handling**: Custom placeholder URL picker, frontend broken-image observer toggle, and default dimensions.
- **Cache**: Delivery mode selector (Direct Remote vs. Local Cache), Action Scheduler batch size, and manual cache flushing.
- **Import & Export**: CSV column mapping reference and custom gallery delimiter configuration (comma, pipe, semicolon).
- **License**: Modular license provider status (default unconfigured state).
- **Support**: Direct contact links (Email, WhatsApp, Marketplace) and live server diagnostic telemetry.

---

## Technical Architecture & Developer Hooks

### Hook Pipeline
The plugin intercepts WooCommerce image output non-invasively via standard WordPress and WooCommerce filters:
- `woocommerce_product_get_image` &mdash; Archive loops, shortcodes, and product grids.
- `woocommerce_single_product_image_thumbnail_html` &mdash; Single product featured images and gallery slides.
- `woocommerce_available_variation` &mdash; Variable product JSON attribute payloads.
- `woocommerce_cart_item_thumbnail` &mdash; Cart and mini-cart line items.
- `woocommerce_order_item_thumbnail` &mdash; Checkout and order review items.
- `woocommerce_store_api_product_image_data` &mdash; Store API and Cart/Checkout Blocks.

### Custom Filters
Customize behavior in your child theme or custom plugin:
```php
// Filter resolved external image URL
add_filter( 'wpcalibrate_epi_resolved_image_url', function( $url, $product_id ) {
    return $url;
}, 10, 2 );

// Filter allowed external image protocols
add_filter( 'wpcalibrate_epi_allowed_protocols', function( $protocols ) {
    $protocols[] = 'https';
    return $protocols;
} );
```

---

## Changelog

### [1.0.2] - 2026-10-15
- **Fixed**: Replaced CSS mask hack on admin sidebar menu with direct integration of the authentic given logo `branding/icon-white.png`.
- **Enhanced**: Constrained menu icon to exact 20x20 sizing with flex centering and native WordPress opacity states (`0.7` idle, `1.0` hover/active).
- **Added**: GitHub Updater compatibility headers (`Update URI`, `GitHub Plugin URI`, `Primary Branch`).

### [1.0.1] - 2026-10-15
- **Added**: Overview tab featuring live metric cards, 3-step quick start guide, and technical lifecycle documentation.
- **Enhanced**: SaaS dashboard redesign with pill navigation, SVG tab icons, and pulsating live service status indicator.
- **Added**: Automated cURL FTP synchronization script for instant remote deployments.

### [1.0.0] - 2026-10-15
- **Initial Production Release**.
- External featured images, multi-image galleries, and variation images.
- Direct Remote delivery and Local Media Library cache mode.
- Non-blocking client-side broken image fallback observer.
- Native WooCommerce CSV Importer and Exporter integration.
- High-Performance Order Storage (HPOS) and Blocks compatibility declarations.
- Anti-SSRF private IP inspection and MIME validation.

---

## License

This plugin is open-source software licensed under the **GNU General Public License v2.0 or later** ([GPL-2.0-or-later](https://www.gnu.org/licenses/gpl-2.0.html)).

---

## Author & Support

- **Author**: WPCalibrate
- **Website**: [wpcalibrate.com](https://wpcalibrate.com)
- **Marketplace**: [marketplace.wpcalibrate.com](https://marketplace.wpcalibrate.com)
- **Support Email**: [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
- **WhatsApp Support**: [+447474795976](https://wa.me/447474795976)
