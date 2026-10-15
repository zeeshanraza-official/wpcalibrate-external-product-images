<?php
/**
 * Master Plugin Coordinator.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages;

use WPCalibrate\ExternalProductImages\Admin\Menu;
use WPCalibrate\ExternalProductImages\Admin\ProductFields;
use WPCalibrate\ExternalProductImages\Admin\VariationFields;
use WPCalibrate\ExternalProductImages\Admin\BulkEdit;
use WPCalibrate\ExternalProductImages\Background\CacheImageJob;
use WPCalibrate\ExternalProductImages\Support\Assets;
use WPCalibrate\ExternalProductImages\WooCommerce\ProductIntegration;
use WPCalibrate\ExternalProductImages\WooCommerce\GalleryIntegration;
use WPCalibrate\ExternalProductImages\WooCommerce\VariationIntegration;
use WPCalibrate\ExternalProductImages\WooCommerce\CartIntegration;
use WPCalibrate\ExternalProductImages\WooCommerce\BlocksIntegration;
use WPCalibrate\ExternalProductImages\WooCommerce\CsvImporter;
use WPCalibrate\ExternalProductImages\WooCommerce\CsvExporter;
use WPCalibrate\ExternalProductImages\WooCommerce\Compatibility;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Orchestrates plugin initialization, service wiring, and lifecycle execution.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * Tracks whether the plugin has already initialized.
	 *
	 * @var bool
	 */
	private bool $initialized = false;

	/**
	 * Get singleton instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {}

	/**
	 * Initialize plugin components after plugins are loaded.
	 *
	 * @return void
	 */
	public function init(): void {
		if ( $this->initialized ) {
			return;
		}
		$this->initialized = true;

		// 1. Verify runtime dependencies (PHP, WordPress, WooCommerce).
		if ( ! Dependencies::are_met() ) {
			Dependencies::register_notices();
			return;
		}

		// 2. Register WooCommerce HPOS & Blocks compatibility declarations.
		Compatibility::register();

		// 3. Initialize Admin Menu and UI services.
		( new Menu() )->init();
		( new ProductFields() )->init();
		( new VariationFields() )->init();
		( new BulkEdit() )->init();

		// 4. Initialize WooCommerce Storefront Integrations.
		( new ProductIntegration() )->init();
		( new GalleryIntegration() )->init();
		( new VariationIntegration() )->init();
		( new CartIntegration() )->init();
		( new BlocksIntegration() )->init();

		// 5. Initialize Native CSV Importer & Exporter integrations.
		( new CsvImporter() )->init();
		( new CsvExporter() )->init();

		// 6. Initialize Background Jobs (Action Scheduler).
		( new CacheImageJob() )->init();

		// 7. Initialize Assets Manager.
		( new Assets() )->init();
	}
}
