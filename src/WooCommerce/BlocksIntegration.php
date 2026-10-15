<?php
/**
 * WooCommerce Blocks & Store API Integration.
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
 * Extends WooCommerce Store API and Gutenberg Product Blocks with external image payloads.
 */
class BlocksIntegration {

	/**
	 * Initialize Store API and block filters.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'woocommerce_store_api_product_image_data', [ $this, 'filter_store_api_image_data' ], 10, 2 );
		add_filter( 'woocommerce_rest_prepare_product_object', [ $this, 'filter_rest_product_images' ], 10, 3 );
	}

	/**
	 * Filter image schema returned by the WooCommerce Store API for Cart/Checkout & Product Blocks.
	 *
	 * @param array       $image_data Current image array.
	 * @param \WC_Product $product Product instance.
	 * @return array
	 */
	public function filter_store_api_image_data( array $image_data, \WC_Product $product ): array {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		if ( ! $plugin_enabled ) {
			return $image_data;
		}

		$resolved = ImageResolver::resolve_product_image( $product->get_id(), 'woocommerce_thumbnail' );

		if ( 'external_direct' === $resolved['source_type'] || 'cached_attachment' === $resolved['source_type'] ) {
			$url = $resolved['url'];
			$alt = $resolved['alt'];

			$image_data['id']        = $resolved['attachment_id'] ?? 0;
			$image_data['src']       = $url;
			$image_data['thumbnail'] = $url;
			$image_data['name']      = $alt;
			$image_data['alt']       = $alt;
			$image_data['srcset']    = '';
			$image_data['sizes']     = '';
		}

		return $image_data;
	}

	/**
	 * Filter REST API response for products to expose external images.
	 *
	 * @param \WP_REST_Response $response Response object.
	 * @param \WC_Product       $product Product instance.
	 * @param \WP_REST_Request  $request Current REST request.
	 * @return \WP_REST_Response
	 */
	public function filter_rest_product_images( \WP_REST_Response $response, \WC_Product $product, \WP_REST_Request $request ): \WP_REST_Response {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		if ( ! $plugin_enabled ) {
			return $response;
		}

		$resolved = ImageResolver::resolve_product_image( $product->get_id(), 'full' );

		if ( 'external_direct' === $resolved['source_type'] || 'cached_attachment' === $resolved['source_type'] ) {
			$data = $response->get_data();
			$images = ! empty( $data['images'] ) && is_array( $data['images'] ) ? $data['images'] : [];

			// Prepend or update primary image.
			$primary = [
				'id'                => $resolved['attachment_id'] ?? 0,
				'src'               => $resolved['url'],
				'name'              => $resolved['alt'],
				'alt'               => $resolved['alt'],
				'date_created'      => '',
				'date_created_gmt'  => '',
				'date_modified'     => '',
				'date_modified_gmt' => '',
			];

			if ( empty( $images ) ) {
				$images[] = $primary;
			} else {
				$images[0] = $primary;
			}

			$data['images'] = $images;
			$response->set_data( $data );
		}

		return $response;
	}
}
