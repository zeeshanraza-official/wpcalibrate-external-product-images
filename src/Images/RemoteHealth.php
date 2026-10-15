<?php
/**
 * Remote Image Health Check Service.
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
 * Checks remote image availability asynchronously or via cached diagnostics.
 */
class RemoteHealth {

	/**
	 * Check remote image availability, caching the result in a transient.
	 *
	 * @param string $url Image URL to inspect.
	 * @param bool   $force_refresh Whether to bypass transient cache.
	 * @return array{
	 *     available: bool,
	 *     status_code: int,
	 *     content_type: string,
	 *     checked_at: int
	 * }
	 */
	public static function check_url( string $url, bool $force_refresh = false ): array {
		$sanitized_url = UrlValidator::sanitize_url( $url );
		if ( empty( $sanitized_url ) ) {
			return [
				'available'    => false,
				'status_code'  => 0,
				'content_type' => '',
				'checked_at'   => time(),
			];
		}

		$transient_key = 'wpcalibrate_epi_health_' . md5( $sanitized_url );

		if ( ! $force_refresh ) {
			$cached = get_transient( $transient_key );
			if ( is_array( $cached ) ) {
				return $cached;
			}
		}

		// Perform safe HTTP check.
		$safety = UrlValidator::is_safe_for_server_fetch( $sanitized_url );
		if ( is_wp_error( $safety ) ) {
			$result = [
				'available'    => false,
				'status_code'  => 403,
				'content_type' => '',
				'checked_at'   => time(),
			];
			set_transient( $transient_key, $result, 3600 );
			return $result;
		}

		// Try HEAD request first.
		$response = wp_safe_remote_head(
			$sanitized_url,
			[
				'timeout'     => 5,
				'redirection' => 2,
				'sslverify'   => true,
			]
		);

		// If HEAD is 405 Method Not Allowed, fallback to minimal GET range.
		$status = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		if ( 405 === $status || 0 === $status ) {
			$response = wp_safe_remote_get(
				$sanitized_url,
				[
					'timeout'     => 5,
					'redirection' => 2,
					'sslverify'   => true,
					'headers'     => [
						'Range' => 'bytes=0-1024',
					],
				]
			);
			$status = is_wp_error( $response ) ? 0 : wp_remote_retrieve_response_code( $response );
		}

		$content_type = is_wp_error( $response ) ? '' : (string) wp_remote_retrieve_header( $response, 'content-type' );
		$is_image     = str_starts_with( strtolower( trim( $content_type ) ), 'image/' );
		$available    = ( 200 === $status || 206 === $status ) && $is_image;

		$result = [
			'available'    => $available,
			'status_code'  => $status,
			'content_type' => $content_type,
			'checked_at'   => time(),
		];

		$hours = (int) Settings::get( 'health_check_lifetime_hours', 24 );
		$ttl   = max( 1, $hours ) * 3600;

		set_transient( $transient_key, $result, $ttl );

		return $result;
	}
}
