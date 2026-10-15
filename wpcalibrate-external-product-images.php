<?php
/**
 * Plugin Name:       WPCalibrate External Product Images
 * Plugin URI:        https://wpcalibrate.com/products/external-product-images/
 * Description:       Displays high-performance external product images, galleries, and variations in WooCommerce without bloating the WordPress Media Library. Features optional local caching, broken image fallback, and native CSV import/export.
 * Version:           1.0.0
 * Requires at least: 6.5
 * Requires PHP:      8.2
 * Author:            WPCalibrate
 * Author URI:        https://wpcalibrate.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       wpcalibrate-external-product-images
 * Domain Path:       /languages
 * Update URI:        https://github.com/zeeshanraza-official/wpcalibrate-external-product-images
 * GitHub Plugin URI: https://github.com/zeeshanraza-official/wpcalibrate-external-product-images
 * Primary Branch:    main
 * WC requires at least: 8.5
 * WC tested up to:   11.2.1
 * Tested up to:      7.1.3
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants.
define( 'WPCALIBRATE_EPI_VERSION', '1.0.0' );
define( 'WPCALIBRATE_EPI_MIN_PHP_VERSION', '8.2' );
define( 'WPCALIBRATE_EPI_MIN_WP_VERSION', '6.5' );
define( 'WPCALIBRATE_EPI_MIN_WC_VERSION', '8.5' );
define( 'WPCALIBRATE_EPI_FILE', __FILE__ );
define( 'WPCALIBRATE_EPI_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPCALIBRATE_EPI_URL', plugin_dir_url( __FILE__ ) );
define( 'WPCALIBRATE_EPI_BASENAME', plugin_basename( __FILE__ ) );

/**
 * Autoloader for WPCalibrate\ExternalProductImages namespace.
 *
 * @param string $class Fully-qualified class name.
 * @return void
 */
spl_autoload_register(
	static function ( string $class ): void {
		$prefix = 'WPCalibrate\\ExternalProductImages\\';
		$len    = strlen( $prefix );

		if ( strncmp( $prefix, $class, $len ) !== 0 ) {
			return;
		}

		$relative_class = substr( $class, $len );
		$file           = WPCALIBRATE_EPI_DIR . 'src/' . str_replace( '\\', '/', $relative_class ) . '.php';

		if ( file_exists( $file ) ) {
			require_once $file;
		}
	}
);

/**
 * Plugin activation hook.
 */
register_activation_hook(
	__FILE__,
	static function (): void {
		Activation::activate();
	}
);

/**
 * Plugin deactivation hook.
 */
register_deactivation_hook(
	__FILE__,
	static function (): void {
		Deactivation::deactivate();
	}
);

/**
 * Bootstrap the plugin after plugins are loaded.
 */
add_action(
	'plugins_loaded',
	static function (): void {
		load_plugin_textdomain(
			'wpcalibrate-external-product-images',
			false,
			dirname( WPCALIBRATE_EPI_BASENAME ) . '/languages'
		);

		Plugin::instance()->init();
	},
	10
);
