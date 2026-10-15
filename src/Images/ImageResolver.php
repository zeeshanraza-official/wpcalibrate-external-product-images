<?php
/**
 * Centralized Image Resolver Service.
 *
 * Implements strict rendering precedence across external, cached, local, and placeholder images.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Images;

use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolves product and variation image assets according to plugin precedence rules.
 */
class ImageResolver {

	/**
	 * Resolve the primary image for a WooCommerce product.
	 *
	 * Precedence:
	 * 1. Cached local attachment (if Local Cache Mode enabled and attachment exists)
	 * 2. External featured image URL
	 * 3. Native WooCommerce featured image attachment
	 * 4. Fallback placeholder
	 *
	 * @param int    $product_id Product post ID.
	 * @param string $size Requested image size name.
	 * @return array{
	 *     url: string,
	 *     attachment_id: int,
	 *     is_external: bool,
	 *     is_cached: bool,
	 *     alt: string,
	 *     source_type: string
	 * }
	 */
	public static function resolve_product_image( int $product_id, string $size = 'woocommerce_thumbnail' ): array {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		$featured_enabled = (bool) Settings::get( 'enable_featured', true );

		if ( ! $plugin_enabled || ! $featured_enabled ) {
			return self::resolve_native_local_or_fallback( $product_id, $size );
		}

		$cache_mode        = (string) Settings::get( 'cache_mode', 'direct' );
		$external_url      = ProductMetaRepository::get_featured_url( $product_id );
		$cached_attachment = ( 'local' === $cache_mode ) ? ProductMetaRepository::get_cached_featured_id( $product_id ) : 0;

		// 1. If Local Cache Mode is enabled and a valid cached attachment exists:
		if ( 'local' === $cache_mode && $cached_attachment > 0 && wp_attachment_is_image( $cached_attachment ) ) {
			$cached_src = wp_get_attachment_image_url( $cached_attachment, $size );
			if ( ! empty( $cached_src ) ) {
				$alt = self::resolve_alt_text( $product_id, ProductMetaRepository::get_featured_alt( $product_id ), $external_url );
				return [
					'url'           => $cached_src,
					'attachment_id' => $cached_attachment,
					'is_external'   => false,
					'is_cached'     => true,
					'alt'           => $alt,
					'source_type'   => 'cached_attachment',
				];
			}
		}

		// 2. Direct external image URL configured:
		if ( ! empty( $external_url ) ) {
			$alt = self::resolve_alt_text( $product_id, ProductMetaRepository::get_featured_alt( $product_id ), $external_url );
			return [
				'url'           => $external_url,
				'attachment_id' => 0,
				'is_external'   => true,
				'is_cached'     => false,
				'alt'           => $alt,
				'source_type'   => 'external_direct',
			];
		}

		// 3. Corresponding locally assigned WooCommerce image:
		return self::resolve_native_local_or_fallback( $product_id, $size );
	}

	/**
	 * Resolve image for a specific WooCommerce product variation.
	 *
	 * Precedence:
	 * 1. Cached local attachment for variation
	 * 2. External variation image URL
	 * 3. Native variation image attachment
	 * 4. Fall back to parent product resolution
	 *
	 * @param int    $variation_id Variation post ID.
	 * @param int    $parent_id Parent product post ID.
	 * @param string $size Requested image size name.
	 * @return array{
	 *     url: string,
	 *     attachment_id: int,
	 *     is_external: bool,
	 *     is_cached: bool,
	 *     alt: string,
	 *     source_type: string
	 * }
	 */
	public static function resolve_variation_image( int $variation_id, int $parent_id, string $size = 'woocommerce_thumbnail' ): array {
		$plugin_enabled     = (bool) Settings::get( 'enabled', true );
		$variations_enabled = (bool) Settings::get( 'enable_variations', true );

		if ( ! $plugin_enabled || ! $variations_enabled ) {
			return self::resolve_native_variation_or_parent( $variation_id, $parent_id, $size );
		}

		$cache_mode        = (string) Settings::get( 'cache_mode', 'direct' );
		$external_url      = ProductMetaRepository::get_variation_url( $variation_id );
		$cached_attachment = ( 'local' === $cache_mode ) ? ProductMetaRepository::get_variation_cached_id( $variation_id ) : 0;

		// 1. Cached local attachment for variation:
		if ( 'local' === $cache_mode && $cached_attachment > 0 && wp_attachment_is_image( $cached_attachment ) ) {
			$cached_src = wp_get_attachment_image_url( $cached_attachment, $size );
			if ( ! empty( $cached_src ) ) {
				$alt = self::resolve_alt_text( $variation_id, ProductMetaRepository::get_variation_alt( $variation_id ), $external_url );
				return [
					'url'           => $cached_src,
					'attachment_id' => $cached_attachment,
					'is_external'   => false,
					'is_cached'     => true,
					'alt'           => $alt,
					'source_type'   => 'cached_attachment',
				];
			}
		}

		// 2. Direct external variation URL:
		if ( ! empty( $external_url ) ) {
			$alt = self::resolve_alt_text( $variation_id, ProductMetaRepository::get_variation_alt( $variation_id ), $external_url );
			return [
				'url'           => $external_url,
				'attachment_id' => 0,
				'is_external'   => true,
				'is_cached'     => false,
				'alt'           => $alt,
				'source_type'   => 'external_direct',
			];
		}

		// 3. Native variation image or fall back to parent:
		return self::resolve_native_variation_or_parent( $variation_id, $parent_id, $size );
	}

	/**
	 * Resolve external gallery items for a product.
	 *
	 * @param int    $product_id Product post ID.
	 * @param string $size Requested image size name.
	 * @return array<int, array{
	 *     url: string,
	 *     full_url: string,
	 *     attachment_id: int,
	 *     is_external: bool,
	 *     is_cached: bool,
	 *     alt: string
	 * }>
	 */
	public static function resolve_gallery( int $product_id, string $size = 'woocommerce_single' ): array {
		$plugin_enabled  = (bool) Settings::get( 'enabled', true );
		$gallery_enabled = (bool) Settings::get( 'enable_gallery', true );

		if ( ! $plugin_enabled || ! $gallery_enabled ) {
			return [];
		}

		$cache_mode = (string) Settings::get( 'cache_mode', 'direct' );
		$items      = ProductMetaRepository::get_gallery( $product_id );
		$resolved   = [];

		foreach ( $items as $item ) {
			$raw_url = $item['url'];
			$cached_id = ( 'local' === $cache_mode && ! empty( $item['cached_attachment_id'] ) ) ? absint( $item['cached_attachment_id'] ) : 0;

			if ( $cached_id > 0 && wp_attachment_is_image( $cached_id ) ) {
				$src = wp_get_attachment_image_url( $cached_id, $size );
				$full_src = wp_get_attachment_image_url( $cached_id, 'full' );
				$alt = self::resolve_alt_text( $product_id, $item['alt'] ?? '', $raw_url );

				$resolved[] = [
					'url'           => $src ?: $raw_url,
					'full_url'      => $full_src ?: $raw_url,
					'attachment_id' => $cached_id,
					'is_external'   => false,
					'is_cached'     => true,
					'alt'           => $alt,
				];
			} else {
				$alt = self::resolve_alt_text( $product_id, $item['alt'] ?? '', $raw_url );
				$resolved[] = [
					'url'           => $raw_url,
					'full_url'      => $raw_url,
					'attachment_id' => 0,
					'is_external'   => true,
					'is_cached'     => false,
					'alt'           => $alt,
				];
			}
		}

		return $resolved;
	}

	/**
	 * Helper: Resolve native local image attachment or fallback placeholder.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $size Requested size.
	 * @return array{url: string, attachment_id: int, is_external: bool, is_cached: bool, alt: string, source_type: string}
	 */
	private static function resolve_native_local_or_fallback( int $product_id, string $size ): array {
		$thumbnail_id = (int) get_post_thumbnail_id( $product_id );

		if ( $thumbnail_id > 0 && wp_attachment_is_image( $thumbnail_id ) ) {
			$src = wp_get_attachment_image_url( $thumbnail_id, $size );
			if ( ! empty( $src ) ) {
				$alt = (string) get_post_meta( $thumbnail_id, '_wp_attachment_image_alt', true );
				if ( empty( $alt ) ) {
					$alt = get_the_title( $product_id );
				}
				return [
					'url'           => $src,
					'attachment_id' => $thumbnail_id,
					'is_external'   => false,
					'is_cached'     => false,
					'alt'           => (string) $alt,
					'source_type'   => 'local_attachment',
				];
			}
		}

		// Fallback placeholder.
		$fallback_url = FallbackManager::get_fallback_url( $size );
		return [
			'url'           => $fallback_url,
			'attachment_id' => 0,
			'is_external'   => false,
			'is_cached'     => false,
			'alt'           => get_the_title( $product_id ),
			'source_type'   => ! empty( $fallback_url ) ? 'placeholder' : 'none',
		];
	}

	/**
	 * Helper: Resolve native variation thumbnail or parent product image.
	 *
	 * @param int    $variation_id Variation ID.
	 * @param int    $parent_id Parent product ID.
	 * @param string $size Requested size.
	 * @return array{url: string, attachment_id: int, is_external: bool, is_cached: bool, alt: string, source_type: string}
	 */
	private static function resolve_native_variation_or_parent( int $variation_id, int $parent_id, string $size ): array {
		$var_thumb_id = (int) get_post_thumbnail_id( $variation_id );

		if ( $var_thumb_id > 0 && wp_attachment_is_image( $var_thumb_id ) ) {
			$src = wp_get_attachment_image_url( $var_thumb_id, $size );
			if ( ! empty( $src ) ) {
				$alt = (string) get_post_meta( $var_thumb_id, '_wp_attachment_image_alt', true );
				return [
					'url'           => $src,
					'attachment_id' => $var_thumb_id,
					'is_external'   => false,
					'is_cached'     => false,
					'alt'           => ! empty( $alt ) ? $alt : get_the_title( $variation_id ),
					'source_type'   => 'local_attachment',
				];
			}
		}

		// Fall back to parent product resolution.
		if ( $parent_id > 0 ) {
			return self::resolve_product_image( $parent_id, $size );
		}

		return [
			'url'           => FallbackManager::get_fallback_url( $size ),
			'attachment_id' => 0,
			'is_external'   => false,
			'is_cached'     => false,
			'alt'           => get_the_title( $variation_id ),
			'source_type'   => 'placeholder',
		];
	}

	/**
	 * Helper to determine alt text using custom value or global strategy.
	 *
	 * @param int    $post_id Product or variation post ID.
	 * @param string $custom_alt Explicitly entered alt text.
	 * @param string $url Source image URL.
	 * @return string Resolved alt text.
	 */
	public static function resolve_alt_text( int $post_id, string $custom_alt, string $url ): string {
		$custom_alt = trim( $custom_alt );
		if ( ! empty( $custom_alt ) ) {
			return $custom_alt;
		}

		$strategy = (string) Settings::get( 'default_alt_mode', 'product_title' );

		if ( 'product_title' === $strategy ) {
			$title = get_the_title( $post_id );
			return ! empty( $title ) ? $title : __( 'Product Image', 'wpcalibrate-external-product-images' );
		}

		if ( 'filename' === $strategy && ! empty( $url ) ) {
			$path = wp_parse_url( $url, PHP_URL_PATH );
			if ( ! empty( $path ) ) {
				$filename = pathinfo( (string) $path, PATHINFO_FILENAME );
				// Clean filename separators to words.
				return sanitize_text_field( str_replace( [ '-', '_', '+' ], ' ', $filename ) );
			}
		}

		return '';
	}
}
