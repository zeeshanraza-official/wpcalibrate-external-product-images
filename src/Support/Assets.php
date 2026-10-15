<?php
/**
 * Asset Manager for Stylesheets and JavaScript.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Support;

use WPCalibrate\ExternalProductImages\Admin\Menu;
use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles conditional enqueuing of admin and frontend assets.
 */
class Assets {

	/**
	 * Initialize asset hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_assets' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend_assets' ] );
	}

	/**
	 * Enqueue admin CSS and JS strictly on relevant screens.
	 *
	 * @param string $hook_suffix Current admin screen hook.
	 * @return void
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		$is_settings_page = (
			str_contains( $hook_suffix, Menu::PAGE_SLUG ) ||
			str_contains( $hook_suffix, Menu::PARENT_SLUG )
		);

		$is_product_screen = (
			$screen &&
			'product' === $screen->post_type &&
			in_array( $screen->base, [ 'post', 'edit' ], true )
		);

		// Do not load admin assets on unrelated admin screens!
		if ( ! $is_settings_page && ! $is_product_screen ) {
			return;
		}

		wp_enqueue_style(
			'wpcalibrate-epi-admin-css',
			WPCALIBRATE_EPI_URL . 'assets/css/admin.css',
			[ 'dashicons' ],
			WPCALIBRATE_EPI_VERSION
		);

		if ( $is_settings_page ) {
			wp_enqueue_media();
			wp_enqueue_script(
				'wpcalibrate-epi-admin-product-js',
				WPCALIBRATE_EPI_URL . 'assets/js/admin-product.js',
				[],
				WPCALIBRATE_EPI_VERSION,
				true
			);
		}

		if ( $is_product_screen && 'post' === ( $screen->base ?? '' ) ) {
			wp_enqueue_script(
				'wpcalibrate-epi-admin-product-js',
				WPCALIBRATE_EPI_URL . 'assets/js/admin-product.js',
				[],
				WPCALIBRATE_EPI_VERSION,
				true
			);

			wp_enqueue_script(
				'wpcalibrate-epi-admin-gallery-js',
				WPCALIBRATE_EPI_URL . 'assets/js/admin-gallery.js',
				[],
				WPCALIBRATE_EPI_VERSION,
				true
			);
		}
	}

	/**
	 * Enqueue frontend CSS and JS.
	 *
	 * @return void
	 */
	public function enqueue_frontend_assets(): void {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		if ( ! $plugin_enabled ) {
			return;
		}

		// Enqueue minimal CSS.
		wp_enqueue_style(
			'wpcalibrate-epi-frontend-css',
			WPCALIBRATE_EPI_URL . 'assets/css/frontend.css',
			[],
			WPCALIBRATE_EPI_VERSION
		);

		// Enqueue client fallback script only if client fallback is enabled.
		if ( (bool) Settings::get( 'client_fallback', true ) ) {
			wp_enqueue_script(
				'wpcalibrate-epi-frontend-js',
				WPCALIBRATE_EPI_URL . 'assets/js/frontend.js',
				[],
				WPCALIBRATE_EPI_VERSION,
				true
			);
		}
	}
}
