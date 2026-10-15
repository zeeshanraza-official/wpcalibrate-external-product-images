<?php
/**
 * Standards-compliant Image HTML Markup Renderer.
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
 * Produces clean, escaped, and accessible HTML image markup.
 */
class ImageRenderer {

	/**
	 * Render an external or resolved image tag with accessible attributes and fallback metadata.
	 *
	 * @param array{
	 *     url: string,
	 *     alt: string,
	 *     is_external?: bool,
	 *     attachment_id?: int,
	 *     is_cached?: bool
	 * } $image_data Resolved image array.
	 * @param array<string, mixed> $custom_attrs Custom HTML attributes (class, loading, etc.).
	 * @return string HTML <img> markup.
	 */
	public static function render_tag( array $image_data, array $custom_attrs = [] ): string {
		$url = $image_data['url'] ?? '';
		if ( empty( $url ) ) {
			return FallbackManager::get_fallback_html( 'woocommerce_thumbnail', $custom_attrs );
		}

		$alt = $image_data['alt'] ?? '';

		$default_classes = [ 'wpcalibrate-external-image' ];
		if ( ! empty( $image_data['is_external'] ) ) {
			$default_classes[] = 'wpcalibrate-is-remote';
		}
		if ( ! empty( $image_data['is_cached'] ) ) {
			$default_classes[] = 'wpcalibrate-is-cached';
		}

		$class_string = trim( implode( ' ', $default_classes ) . ' ' . (string) ( $custom_attrs['class'] ?? '' ) );

		$attributes = [
			'src'      => esc_url( $url ),
			'alt'      => esc_attr( $alt ),
			'class'    => esc_attr( $class_string ),
			'decoding' => 'async',
		];

		// Respect or assign loading="lazy" if not explicitly specified as eager.
		if ( isset( $custom_attrs['loading'] ) ) {
			$attributes['loading'] = esc_attr( (string) $custom_attrs['loading'] );
		} else {
			$attributes['loading'] = 'lazy';
		}

		// Width and height if supplied.
		if ( ! empty( $custom_attrs['width'] ) ) {
			$attributes['width'] = absint( $custom_attrs['width'] );
		}
		if ( ! empty( $custom_attrs['height'] ) ) {
			$attributes['height'] = absint( $custom_attrs['height'] );
		}

		// Client-side fallback attributes.
		$client_fallback = (bool) Settings::get( 'client_fallback', true );
		if ( $client_fallback && ! empty( $image_data['is_external'] ) ) {
			$fallback_url = FallbackManager::get_fallback_url( 'woocommerce_thumbnail' );
			if ( ! empty( $fallback_url ) && $fallback_url !== $url ) {
				$attributes['data-wpcalibrate-fallback'] = esc_url( $fallback_url );
			}
		}

		// Merge data attributes or WooCommerce gallery zoom attributes.
		foreach ( $custom_attrs as $key => $val ) {
			if ( str_starts_with( $key, 'data-' ) ) {
				$attributes[ $key ] = esc_attr( (string) $val );
			}
		}

		$html = '<img';
		foreach ( $attributes as $name => $value ) {
			$html .= ' ' . $name . '="' . $value . '"';
		}
		$html .= ' />';

		return $html;
	}
}
