<?php
/**
 * Product and Variation Metadata Repository.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Images;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Encapsulates read/write access for external product image metadata.
 */
class ProductMetaRepository {

	public const META_FEATURED_URL        = '_wpcalibrate_epi_featured_url';
	public const META_FEATURED_ALT        = '_wpcalibrate_epi_featured_alt';
	public const META_CACHED_FEATURED_ID  = '_wpcalibrate_epi_cached_featured_id';
	public const META_GALLERY              = '_wpcalibrate_epi_gallery';
	public const META_CACHE_MAP           = '_wpcalibrate_epi_cache_map';
	public const META_SOURCE_VERSION      = '_wpcalibrate_epi_source_version';

	public const META_VARIATION_URL       = '_wpcalibrate_epi_variation_url';
	public const META_VARIATION_ALT       = '_wpcalibrate_epi_variation_alt';
	public const META_VARIATION_CACHED_ID = '_wpcalibrate_epi_variation_cached_id';

	/**
	 * Get external featured image URL.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function get_featured_url( int $product_id ): string {
		$url = (string) get_post_meta( $product_id, self::META_FEATURED_URL, true );
		return UrlValidator::sanitize_url( $url );
	}

	/**
	 * Set external featured image URL.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $url URL to save.
	 * @return void
	 */
	public static function set_featured_url( int $product_id, string $url ): void {
		$sanitized = UrlValidator::sanitize_url( $url );
		if ( empty( $sanitized ) ) {
			delete_post_meta( $product_id, self::META_FEATURED_URL );
			delete_post_meta( $product_id, self::META_CACHED_FEATURED_ID );
		} else {
			$previous = self::get_featured_url( $product_id );
			if ( $previous !== $sanitized ) {
				// Invalidate previous cache pointer when source changes.
				delete_post_meta( $product_id, self::META_CACHED_FEATURED_ID );
				update_post_meta( $product_id, self::META_SOURCE_VERSION, time() );
			}
			update_post_meta( $product_id, self::META_FEATURED_URL, $sanitized );
		}
	}

	/**
	 * Get external featured image alt text override.
	 *
	 * @param int $product_id Product ID.
	 * @return string
	 */
	public static function get_featured_alt( int $product_id ): string {
		return (string) get_post_meta( $product_id, self::META_FEATURED_ALT, true );
	}

	/**
	 * Set external featured image alt text.
	 *
	 * @param int    $product_id Product ID.
	 * @param string $alt Alt text.
	 * @return void
	 */
	public static function set_featured_alt( int $product_id, string $alt ): void {
		$clean = sanitize_text_field( $alt );
		if ( empty( $clean ) ) {
			delete_post_meta( $product_id, self::META_FEATURED_ALT );
		} else {
			update_post_meta( $product_id, self::META_FEATURED_ALT, $clean );
		}
	}

	/**
	 * Get local cached attachment ID for featured image if exists.
	 *
	 * @param int $product_id Product ID.
	 * @return int Attachment ID or 0.
	 */
	public static function get_cached_featured_id( int $product_id ): int {
		$id = absint( get_post_meta( $product_id, self::META_CACHED_FEATURED_ID, true ) );
		if ( $id > 0 && ! wp_attachment_is_image( $id ) ) {
			// Cached attachment was deleted from media library; reset pointer.
			delete_post_meta( $product_id, self::META_CACHED_FEATURED_ID );
			return 0;
		}
		return $id;
	}

	/**
	 * Set local cached attachment ID for featured image.
	 *
	 * @param int $product_id Product ID.
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public static function set_cached_featured_id( int $product_id, int $attachment_id ): void {
		if ( $attachment_id > 0 ) {
			update_post_meta( $product_id, self::META_CACHED_FEATURED_ID, $attachment_id );
		} else {
			delete_post_meta( $product_id, self::META_CACHED_FEATURED_ID );
		}
	}

	/**
	 * Get external gallery items.
	 *
	 * @param int $product_id Product ID.
	 * @return array<int, array{url: string, alt: string, cached_attachment_id: int}>
	 */
	public static function get_gallery( int $product_id ): array {
		$raw = get_post_meta( $product_id, self::META_GALLERY, true );
		if ( ! is_array( $raw ) ) {
			return [];
		}

		$cleaned = [];
		foreach ( $raw as $item ) {
			if ( ! is_array( $item ) || empty( $item['url'] ) ) {
				continue;
			}
			$url = UrlValidator::sanitize_url( (string) $item['url'] );
			if ( empty( $url ) ) {
				continue;
			}

			$cached_id = isset( $item['cached_attachment_id'] ) ? absint( $item['cached_attachment_id'] ) : 0;
			if ( $cached_id > 0 && ! wp_attachment_is_image( $cached_id ) ) {
				$cached_id = 0;
			}

			$cleaned[] = [
				'url'                  => $url,
				'alt'                  => isset( $item['alt'] ) ? sanitize_text_field( (string) $item['alt'] ) : '',
				'cached_attachment_id' => $cached_id,
			];
		}

		return $cleaned;
	}

	/**
	 * Set external gallery items.
	 *
	 * @param int                                                                     $product_id Product ID.
	 * @param array<int, array{url: string, alt?: string, cached_attachment_id?: int}> $items Array of gallery entries.
	 * @return void
	 */
	public static function set_gallery( int $product_id, array $items ): void {
		$clean = [];
		$seen_urls = [];

		foreach ( $items as $item ) {
			if ( ! is_array( $item ) || empty( $item['url'] ) ) {
				continue;
			}
			$url = UrlValidator::sanitize_url( (string) $item['url'] );
			if ( empty( $url ) || isset( $seen_urls[ $url ] ) ) {
				continue; // Avoid duplicate URLs within the same product gallery.
			}
			$seen_urls[ $url ] = true;

			$clean[] = [
				'url'                  => $url,
				'alt'                  => isset( $item['alt'] ) ? sanitize_text_field( (string) $item['alt'] ) : '',
				'cached_attachment_id' => isset( $item['cached_attachment_id'] ) ? absint( $item['cached_attachment_id'] ) : 0,
			];
		}

		if ( empty( $clean ) ) {
			delete_post_meta( $product_id, self::META_GALLERY );
		} else {
			update_post_meta( $product_id, self::META_GALLERY, $clean );
		}
	}

	/**
	 * Get external variation image URL.
	 *
	 * @param int $variation_id Variation post ID.
	 * @return string
	 */
	public static function get_variation_url( int $variation_id ): string {
		$url = (string) get_post_meta( $variation_id, self::META_VARIATION_URL, true );
		return UrlValidator::sanitize_url( $url );
	}

	/**
	 * Set external variation image URL.
	 *
	 * @param int    $variation_id Variation post ID.
	 * @param string $url URL to set.
	 * @return void
	 */
	public static function set_variation_url( int $variation_id, string $url ): void {
		$sanitized = UrlValidator::sanitize_url( $url );
		if ( empty( $sanitized ) ) {
			delete_post_meta( $variation_id, self::META_VARIATION_URL );
			delete_post_meta( $variation_id, self::META_VARIATION_CACHED_ID );
		} else {
			$previous = self::get_variation_url( $variation_id );
			if ( $previous !== $sanitized ) {
				delete_post_meta( $variation_id, self::META_VARIATION_CACHED_ID );
			}
			update_post_meta( $variation_id, self::META_VARIATION_URL, $sanitized );
		}
	}

	/**
	 * Get external variation alt text.
	 *
	 * @param int $variation_id Variation post ID.
	 * @return string
	 */
	public static function get_variation_alt( int $variation_id ): string {
		return (string) get_post_meta( $variation_id, self::META_VARIATION_ALT, true );
	}

	/**
	 * Set external variation alt text.
	 *
	 * @param int    $variation_id Variation post ID.
	 * @param string $alt Alt text.
	 * @return void
	 */
	public static function set_variation_alt( int $variation_id, string $alt ): void {
		$clean = sanitize_text_field( $alt );
		if ( empty( $clean ) ) {
			delete_post_meta( $variation_id, self::META_VARIATION_ALT );
		} else {
			update_post_meta( $variation_id, self::META_VARIATION_ALT, $clean );
		}
	}

	/**
	 * Get variation cached attachment ID.
	 *
	 * @param int $variation_id Variation ID.
	 * @return int
	 */
	public static function get_variation_cached_id( int $variation_id ): int {
		$id = absint( get_post_meta( $variation_id, self::META_VARIATION_CACHED_ID, true ) );
		if ( $id > 0 && ! wp_attachment_is_image( $id ) ) {
			delete_post_meta( $variation_id, self::META_VARIATION_CACHED_ID );
			return 0;
		}
		return $id;
	}

	/**
	 * Set variation cached attachment ID.
	 *
	 * @param int $variation_id Variation ID.
	 * @param int $attachment_id Attachment ID.
	 * @return void
	 */
	public static function set_variation_cached_id( int $variation_id, int $attachment_id ): void {
		if ( $attachment_id > 0 ) {
			update_post_meta( $variation_id, self::META_VARIATION_CACHED_ID, $attachment_id );
		} else {
			delete_post_meta( $variation_id, self::META_VARIATION_CACHED_ID );
		}
	}
}
