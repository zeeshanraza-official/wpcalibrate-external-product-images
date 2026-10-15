<?php
/**
 * External Product Gallery Manager.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Images;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles manipulation, reordering, and delimiter formatting for external galleries.
 */
class GalleryManager {

	/**
	 * Parse a delimited string of URLs (e.g. from CSV) into a normalized gallery list.
	 *
	 * @param string $delimited_string String of URLs.
	 * @param string $delimiter Delimiter character (default ',').
	 * @return array<int, array{url: string, alt: string, cached_attachment_id: int}>
	 */
	public static function parse_delimited_urls( string $delimited_string, string $delimiter = ',' ): array {
		if ( empty( trim( $delimited_string ) ) ) {
			return [];
		}

		$parts = explode( $delimiter, $delimited_string );
		$items = [];
		$seen  = [];

		foreach ( $parts as $part ) {
			$raw_url = trim( $part );
			if ( empty( $raw_url ) ) {
				continue;
			}

			$url = UrlValidator::sanitize_url( $raw_url );
			if ( ! empty( $url ) && ! isset( $seen[ $url ] ) ) {
				$seen[ $url ] = true;
				$items[] = [
					'url'                  => $url,
					'alt'                  => '',
					'cached_attachment_id' => 0,
				];
			}
		}

		return $items;
	}

	/**
	 * Convert gallery array into a delimited string for CSV export.
	 *
	 * @param array<int, array{url: string, alt?: string}> $items Gallery items.
	 * @param string                                       $delimiter Delimiter character.
	 * @return string
	 */
	public static function format_to_delimited_string( array $items, string $delimiter = ',' ): string {
		$urls = [];
		foreach ( $items as $item ) {
			if ( ! empty( $item['url'] ) ) {
				$urls[] = trim( $item['url'] );
			}
		}

		return implode( $delimiter . ' ', $urls );
	}
}
