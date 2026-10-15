<?php
/**
 * WooCommerce Variable Product & Dynamic Variation Swapping Integration.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\WooCommerce;

use WPCalibrate\ExternalProductImages\Images\ImageResolver;
use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Injects external variation image data into WooCommerce's frontend variation payloads.
 */
class VariationIntegration {

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'woocommerce_available_variation', [ $this, 'filter_available_variation' ], 10, 3 );
	}

	/**
	 * Filter variation JSON payload sent to frontend for variation switching.
	 *
	 * @param array                  $variation_data Variation frontend data.
	 * @param \WC_Product            $product Parent variable product.
	 * @param \WC_Product_Variation  $variation Specific variation instance.
	 * @return array Modified variation data.
	 */
	public function filter_available_variation( array $variation_data, \WC_Product $product, \WC_Product_Variation $variation ): array {
		$plugin_enabled     = (bool) Settings::get( 'enabled', true );
		$variations_enabled = (bool) Settings::get( 'enable_variations', true );

		if ( ! $plugin_enabled || ! $variations_enabled ) {
			return $variation_data;
		}

		$resolved = ImageResolver::resolve_variation_image( $variation->get_id(), $product->get_id(), 'woocommerce_single' );

		if ( 'external_direct' === $resolved['source_type'] || 'cached_attachment' === $resolved['source_type'] ) {
			$img_url = $resolved['url'];
			$alt     = $resolved['alt'];

			$variation_data['image'] = [
				'title'                 => $alt,
				'caption'               => '',
				'url'                   => $img_url,
				'alt'                   => $alt,
				'src'                   => $img_url,
				'full_src'              => $img_url,
				'gallery_thumbnail_src' => $img_url,
				'thumb_src'             => $img_url,
				'srcset'                => '',
				'sizes'                 => '',
				'image_id'              => $resolved['attachment_id'] ?? 0,
			];
		}

		return $variation_data;
	}
}
