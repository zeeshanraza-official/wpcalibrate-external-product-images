<?php
/**
 * WooCommerce Cart, Mini-Cart, Checkout & Order Image Integration.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\WooCommerce;

use WPCalibrate\ExternalProductImages\Images\ImageResolver;
use WPCalibrate\ExternalProductImages\Images\ImageRenderer;
use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ensures cart and checkout item thumbnails display external images correctly.
 */
class CartIntegration {

	/**
	 * Initialize cart hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'woocommerce_cart_item_thumbnail', [ $this, 'filter_cart_item_thumbnail' ], 10, 3 );
		add_filter( 'woocommerce_order_item_thumbnail', [ $this, 'filter_order_item_thumbnail' ], 10, 2 );
	}

	/**
	 * Filter cart and mini-cart item thumbnail HTML.
	 *
	 * @param string               $thumbnail Existing HTML thumbnail.
	 * @param array<string, mixed> $cart_item Cart item data array.
	 * @param string               $cart_item_key Cart item key.
	 * @return string Filtered thumbnail HTML.
	 */
	public function filter_cart_item_thumbnail( string $thumbnail, array $cart_item, string $cart_item_key ): string {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		if ( ! $plugin_enabled ) {
			return $thumbnail;
		}

		$variation_id = ! empty( $cart_item['variation_id'] ) ? absint( $cart_item['variation_id'] ) : 0;
		$product_id   = ! empty( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;

		$resolved = null;
		if ( $variation_id > 0 ) {
			$resolved = ImageResolver::resolve_variation_image( $variation_id, $product_id, 'woocommerce_thumbnail' );
		} elseif ( $product_id > 0 ) {
			$resolved = ImageResolver::resolve_product_image( $product_id, 'woocommerce_thumbnail' );
		}

		if ( $resolved && ( 'external_direct' === $resolved['source_type'] || 'cached_attachment' === $resolved['source_type'] ) ) {
			return ImageRenderer::render_tag(
				$resolved,
				[
					'class' => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail wp-post-image',
				]
			);
		}

		return $thumbnail;
	}

	/**
	 * Filter order item thumbnail in customer thank-you and my-account views.
	 *
	 * @param string                $thumbnail Thumbnail HTML.
	 * @param \WC_Order_Item_Product $item Order item instance.
	 * @return string
	 */
	public function filter_order_item_thumbnail( string $thumbnail, $item ): string {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		if ( ! $plugin_enabled || ! is_object( $item ) || ! method_exists( $item, 'get_product_id' ) ) {
			return $thumbnail;
		}

		$product_id   = (int) $item->get_product_id();
		$variation_id = method_exists( $item, 'get_variation_id' ) ? (int) $item->get_variation_id() : 0;

		$resolved = null;
		if ( $variation_id > 0 ) {
			$resolved = ImageResolver::resolve_variation_image( $variation_id, $product_id, 'woocommerce_thumbnail' );
		} elseif ( $product_id > 0 ) {
			$resolved = ImageResolver::resolve_product_image( $product_id, 'woocommerce_thumbnail' );
		}

		if ( $resolved && ( 'external_direct' === $resolved['source_type'] || 'cached_attachment' === $resolved['source_type'] ) ) {
			return ImageRenderer::render_tag(
				$resolved,
				[
					'class' => 'attachment-woocommerce_thumbnail size-woocommerce_thumbnail wp-post-image',
				]
			);
		}

		return $thumbnail;
	}
}
