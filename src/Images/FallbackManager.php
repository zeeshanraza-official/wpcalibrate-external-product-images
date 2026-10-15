<?php
/**
 * Fallback Image Manager.
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
 * Handles store-wide placeholder and fallback resolution.
 */
class FallbackManager {

	/**
	 * Get the fallback image URL based on plugin configuration.
	 *
	 * @param string $size Image size name (e.g. 'woocommerce_thumbnail', 'full').
	 * @return string Fallback image URL or empty string.
	 */
	public static function get_fallback_url( string $size = 'woocommerce_thumbnail' ): string {
		$mode = (string) Settings::get( 'default_fallback', 'woocommerce_placeholder' );

		if ( 'custom_attachment' === $mode ) {
			$attachment_id = absint( Settings::get( 'custom_fallback_id', 0 ) );
			if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
				$src = wp_get_attachment_image_url( $attachment_id, $size );
				if ( ! empty( $src ) ) {
					return $src;
				}
			}
		}

		if ( 'woocommerce_placeholder' === $mode ) {
			if ( function_exists( 'wc_placeholder_img_src' ) ) {
				return (string) wc_placeholder_img_src( $size );
			}
		}

		return '';
	}

	/**
	 * Get the fallback image HTML tag.
	 *
	 * @param string               $size Image size.
	 * @param array<string, mixed> $attr Attributes array.
	 * @return string HTML img tag.
	 */
	public static function get_fallback_html( string $size = 'woocommerce_thumbnail', array $attr = [] ): string {
		$mode = (string) Settings::get( 'default_fallback', 'woocommerce_placeholder' );

		if ( 'custom_attachment' === $mode ) {
			$attachment_id = absint( Settings::get( 'custom_fallback_id', 0 ) );
			if ( $attachment_id > 0 && wp_attachment_is_image( $attachment_id ) ) {
				return wp_get_attachment_image( $attachment_id, $size, false, $attr );
			}
		}

		if ( 'none' === $mode ) {
			return '';
		}

		if ( function_exists( 'wc_placeholder_img' ) ) {
			return (string) wc_placeholder_img( $size, $attr );
		}

		$fallback_url = self::get_fallback_url( $size );
		if ( empty( $fallback_url ) ) {
			return '';
		}

		return sprintf(
			'<img src="%s" alt="%s" class="%s" />',
			esc_url( $fallback_url ),
			esc_attr__( 'Placeholder', 'wpcalibrate-external-product-images' ),
			esc_attr( $attr['class'] ?? 'wpcalibrate-fallback-image' )
		);
	}
}
