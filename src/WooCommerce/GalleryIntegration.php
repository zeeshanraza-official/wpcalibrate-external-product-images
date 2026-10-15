<?php
/**
 * WooCommerce Single Product Gallery & Thumbnail Integration.
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
 * Integrates external featured images and external galleries into the WooCommerce single product gallery.
 */
class GalleryIntegration {

	/**
	 * Initialize gallery hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Hook the single product main image slide.
		add_filter( 'woocommerce_single_product_image_thumbnail_html', [ $this, 'filter_single_product_main_image' ], 10, 2 );

		// Hook additional gallery thumbnail rendering.
		add_action( 'woocommerce_product_thumbnails', [ $this, 'render_external_gallery_slides' ], 25 );
	}

	/**
	 * Filter the main single product image HTML.
	 *
	 * @param string   $html Native thumbnail wrapper HTML.
	 * @param int|null $post_thumbnail_id Thumbnail ID.
	 * @return string Filtered slide HTML.
	 */
	public function filter_single_product_main_image( string $html, ?int $post_thumbnail_id = 0 ): string {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		if ( ! $plugin_enabled ) {
			return $html;
		}

		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return $html;
		}

		$resolved = ImageResolver::resolve_product_image( $product->get_id(), 'woocommerce_single' );

		if ( 'external_direct' !== $resolved['source_type'] && 'cached_attachment' !== $resolved['source_type'] ) {
			return $html;
		}

		$full_url = $resolved['url'];
		$img_html = ImageRenderer::render_tag(
			$resolved,
			[
				'class'              => 'wp-post-image',
				'data-large_image'   => $full_url,
				'data-large_image_width'  => '',
				'data-large_image_height' => '',
			]
		);

		return sprintf(
			'<div data-thumb="%s" data-thumb-alt="%s" class="woocommerce-product-gallery__image"><a href="%s">%s</a></div>',
			esc_url( $full_url ),
			esc_attr( $resolved['alt'] ),
			esc_url( $full_url ),
			$img_html
		);
	}

	/**
	 * Render external gallery slides inside the WooCommerce gallery wrapper.
	 *
	 * @return void
	 */
	public function render_external_gallery_slides(): void {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		$gallery_enabled = (bool) Settings::get( 'enable_gallery', true );

		if ( ! $plugin_enabled || ! $gallery_enabled ) {
			return;
		}

		global $product;
		if ( ! $product instanceof \WC_Product ) {
			return;
		}

		$gallery_items = ImageResolver::resolve_gallery( $product->get_id(), 'woocommerce_single' );
		if ( empty( $gallery_items ) ) {
			return;
		}

		foreach ( $gallery_items as $item ) {
			$thumb_url = $item['url'];
			$full_url  = $item['full_url'];
			$alt       = $item['alt'];

			$img_html = ImageRenderer::render_tag(
				[
					'url'         => $thumb_url,
					'alt'         => $alt,
					'is_external' => $item['is_external'],
					'is_cached'   => $item['is_cached'],
				],
				[
					'class'            => 'wp-post-image wpcalibrate-gallery-slide',
					'data-large_image' => $full_url,
				]
			);

			printf(
				'<div data-thumb="%s" data-thumb-alt="%s" class="woocommerce-product-gallery__image"><a href="%s">%s</a></div>',
				esc_url( $thumb_url ),
				esc_attr( $alt ),
				esc_url( $full_url ),
				$img_html // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			);
		}
	}
}
