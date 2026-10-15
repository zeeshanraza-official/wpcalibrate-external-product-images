<?php
/**
 * Dependency checker for WordPress, WooCommerce, and PHP runtime.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles dependency verification and failure notifications.
 */
class Dependencies {

	/**
	 * Check if all required environment dependencies are met.
	 *
	 * @return bool True if all dependencies are satisfied.
	 */
	public static function are_met(): bool {
		return self::is_php_compatible()
			&& self::is_wordpress_compatible()
			&& self::is_woocommerce_active()
			&& self::is_woocommerce_compatible();
	}

	/**
	 * Check PHP version compatibility.
	 *
	 * @return bool
	 */
	public static function is_php_compatible(): bool {
		return version_compare( PHP_VERSION, WPCALIBRATE_EPI_MIN_PHP_VERSION, '>=' );
	}

	/**
	 * Check WordPress version compatibility.
	 *
	 * @return bool
	 */
	public static function is_wordpress_compatible(): bool {
		global $wp_version;
		return version_compare( (string) $wp_version, WPCALIBRATE_EPI_MIN_WP_VERSION, '>=' );
	}

	/**
	 * Check if WooCommerce is installed and active.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_active(): bool {
		return class_exists( 'WooCommerce' ) || function_exists( 'WC' );
	}

	/**
	 * Check if WooCommerce meets minimum version requirement.
	 *
	 * @return bool
	 */
	public static function is_woocommerce_compatible(): bool {
		if ( ! self::is_woocommerce_active() ) {
			return false;
		}

		$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : ( function_exists( 'WC' ) ? WC()->version : '0.0.0' );
		return version_compare( (string) $wc_version, WPCALIBRATE_EPI_MIN_WC_VERSION, '>=' );
	}

	/**
	 * Register admin notices if dependencies are missing.
	 *
	 * @return void
	 */
	public static function register_notices(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_notices', [ __CLASS__, 'render_missing_dependency_notices' ] );
	}

	/**
	 * Render clear and actionable notices in the admin area.
	 *
	 * @return void
	 */
	public static function render_missing_dependency_notices(): void {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}

		if ( ! self::is_php_compatible() ) {
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'WPCalibrate External Product Images:', 'wpcalibrate-external-product-images' ); ?></strong>
					<?php
					printf(
						/* translators: 1: Required PHP version, 2: Current PHP version */
						esc_html__( 'Requires PHP %1$s or higher. Your server is currently running PHP %2$s. Please update PHP to use this plugin.', 'wpcalibrate-external-product-images' ),
						esc_html( WPCALIBRATE_EPI_MIN_PHP_VERSION ),
						esc_html( PHP_VERSION )
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( ! self::is_wordpress_compatible() ) {
			global $wp_version;
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'WPCalibrate External Product Images:', 'wpcalibrate-external-product-images' ); ?></strong>
					<?php
					printf(
						/* translators: 1: Required WordPress version, 2: Current WordPress version */
						esc_html__( 'Requires WordPress %1$s or higher. Your site is running WordPress %2$s.', 'wpcalibrate-external-product-images' ),
						esc_html( WPCALIBRATE_EPI_MIN_WP_VERSION ),
						esc_html( (string) $wp_version )
					);
					?>
				</p>
			</div>
			<?php
		}

		if ( ! self::is_woocommerce_active() ) {
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'WPCalibrate External Product Images:', 'wpcalibrate-external-product-images' ); ?></strong>
					<?php esc_html_e( 'Requires WooCommerce to be installed and activated. Please install or activate WooCommerce to enable external product image features.', 'wpcalibrate-external-product-images' ); ?>
				</p>
			</div>
			<?php
		} elseif ( ! self::is_woocommerce_compatible() ) {
			$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : 'unknown';
			?>
			<div class="notice notice-error">
				<p>
					<strong><?php esc_html_e( 'WPCalibrate External Product Images:', 'wpcalibrate-external-product-images' ); ?></strong>
					<?php
					printf(
						/* translators: 1: Required WooCommerce version, 2: Current WooCommerce version */
						esc_html__( 'Requires WooCommerce %1$s or higher. Your site is running WooCommerce %2$s.', 'wpcalibrate-external-product-images' ),
						esc_html( WPCALIBRATE_EPI_MIN_WC_VERSION ),
						esc_html( (string) $wc_version )
					);
					?>
				</p>
			</div>
			<?php
		}
	}
}
