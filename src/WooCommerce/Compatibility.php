<?php
/**
 * WooCommerce HPOS & Feature Compatibility Declarations.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\WooCommerce;

use Automattic\WooCommerce\Utilities\FeaturesUtil;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles official WooCommerce feature compatibility registrations.
 */
class Compatibility {

	/**
	 * Register compatibility declarations.
	 *
	 * @return void
	 */
	public static function register(): void {
		add_action( 'before_woocommerce_init', [ __CLASS__, 'declare_features' ] );
	}

	/**
	 * Declare HPOS and Cart/Checkout Blocks compatibility.
	 *
	 * @return void
	 */
	public static function declare_features(): void {
		if ( class_exists( FeaturesUtil::class ) ) {
			// High-Performance Order Storage (HPOS) compatibility.
			FeaturesUtil::declare_compatibility( 'custom_order_tables', WPCALIBRATE_EPI_FILE, true );

			// WooCommerce Cart & Checkout Blocks compatibility.
			FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', WPCALIBRATE_EPI_FILE, true );
		}
	}
}
