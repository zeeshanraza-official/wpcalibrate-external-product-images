<?php
/**
 * URL Validator and SSRF Protection Service.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Images;

use WPCalibrate\ExternalProductImages\Settings\Settings;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validates external image URLs and screens against SSRF vulnerabilities.
 */
class UrlValidator {

	/**
	 * Validate a URL for saving as an external product image.
	 *
	 * Checks syntax, protocol (HTTPS/HTTP), and prevents dangerous schemes (data:, javascript:).
	 *
	 * @param string $url Raw URL.
	 * @return bool True if valid for frontend storage/rendering.
	 */
	public static function is_valid_url( string $url ): bool {
		$url = trim( $url );
		if ( empty( $url ) ) {
			return false;
		}

		// Filter for malicious protocols.
		if ( preg_match( '/^(javascript|data|file|vbscript|ftp):/i', $url ) ) {
			return false;
		}

		// Validate structure.
		if ( false === filter_var( $url, FILTER_VALIDATE_URL ) ) {
			return false;
		}

		$parts = wp_parse_url( $url );
		if ( empty( $parts['scheme'] ) || empty( $parts['host'] ) ) {
			return false;
		}

		$scheme = strtolower( (string) $parts['scheme'] );
		$https_only = (bool) Settings::get( 'https_only', true );

		if ( $https_only && 'https' !== $scheme ) {
			return false;
		}

		if ( ! in_array( $scheme, [ 'http', 'https' ], true ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize an image URL before storage.
	 *
	 * @param string $url Raw URL string.
	 * @return string Sanitized URL or empty string if invalid.
	 */
	public static function sanitize_url( string $url ): string {
		$url = trim( $url );
		if ( empty( $url ) ) {
			return '';
		}

		// Strip whitespace, newlines, null bytes.
		$url = str_replace( [ "\0", "\r", "\n", "\t" ], '', $url );

		if ( ! self::is_valid_url( $url ) ) {
			return '';
		}

		// esc_url_raw safely encodes and strips disallowed protocols for database storage.
		return esc_url_raw( $url, [ 'http', 'https' ] );
	}

	/**
	 * Thoroughly check if a URL is safe for server-side HTTP fetching (anti-SSRF).
	 *
	 * Ensures the destination is not localhost, private IP, link-local, loopback, or cloud metadata IP.
	 *
	 * @param string $url URL to inspect.
	 * @return true|WP_Error True if safe, WP_Error with specific failure reason otherwise.
	 */
	public static function is_safe_for_server_fetch( string $url ): true|WP_Error {
		if ( ! self::is_valid_url( $url ) ) {
			return new WP_Error( 'invalid_url', __( 'The provided URL is not a valid HTTP/HTTPS web address.', 'wpcalibrate-external-product-images' ) );
		}

		$parts = wp_parse_url( $url );
		$host  = strtolower( (string) ( $parts['host'] ?? '' ) );

		if ( empty( $host ) ) {
			return new WP_Error( 'missing_host', __( 'The URL does not contain a valid host.', 'wpcalibrate-external-product-images' ) );
		}

		// Reject known localhost variants.
		$localhosts = [ 'localhost', '127.0.0.1', '::1', '0.0.0.0', 'metadata.google.internal' ];
		if ( in_array( $host, $localhosts, true ) || str_ends_with( $host, '.localhost' ) || str_ends_with( $host, '.local' ) ) {
			return new WP_Error( 'ssrf_blocked_host', __( 'Requests to local or private hostnames are prohibited.', 'wpcalibrate-external-product-images' ) );
		}

		// Resolve DNS to verify destination IP addresses.
		$ips = [];
		if ( filter_var( $host, FILTER_VALIDATE_IP ) ) {
			$ips[] = $host;
		} else {
			$dns_records = @dns_get_record( $host, DNS_A + DNS_AAAA );
			if ( ! empty( $dns_records ) && is_array( $dns_records ) ) {
				foreach ( $dns_records as $rec ) {
					if ( isset( $rec['ip'] ) ) {
						$ips[] = $rec['ip'];
					} elseif ( isset( $rec['ipv6'] ) ) {
						$ips[] = $rec['ipv6'];
					}
				}
			} else {
				// Fallback to gethostbynamel.
				$host_ips = @gethostbynamel( $host );
				if ( ! empty( $host_ips ) && is_array( $host_ips ) ) {
					$ips = array_merge( $ips, $host_ips );
				}
			}
		}

		if ( empty( $ips ) ) {
			return new WP_Error( 'dns_resolution_failed', __( 'Could not resolve the remote host IP address.', 'wpcalibrate-external-product-images' ) );
		}

		foreach ( $ips as $ip ) {
			if ( self::is_private_or_reserved_ip( $ip ) ) {
				return new WP_Error(
					'ssrf_private_ip',
					sprintf(
						/* translators: %s: Destination IP address */
						__( 'Access to private, link-local, or loopback network address (%s) is denied.', 'wpcalibrate-external-product-images' ),
						esc_html( $ip )
					)
				);
			}
		}

		return true;
	}

	/**
	 * Check if an IP address belongs to private, loopback, link-local, or cloud metadata ranges.
	 *
	 * @param string $ip IPv4 or IPv6 string.
	 * @return bool True if private or reserved.
	 */
	public static function is_private_or_reserved_ip( string $ip ): bool {
		// Native filter checking FILTER_FLAG_NO_PRIV_RANGE and FILTER_FLAG_NO_RES_RANGE.
		$flags = FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE;
		if ( false === filter_var( $ip, FILTER_VALIDATE_IP, $flags ) ) {
			return true;
		}

		// Extra safety check for cloud metadata service (169.254.169.254) and link-local.
		if ( str_starts_with( $ip, '169.254.' ) || str_starts_with( $ip, '127.' ) || '0.0.0.0' === $ip ) {
			return true;
		}

		// IPv6 loopback and link-local.
		if ( '::1' === $ip || str_starts_with( strtolower( $ip ), 'fe80:' ) || str_starts_with( strtolower( $ip ), 'fc00:' ) || str_starts_with( strtolower( $ip ), 'fd00:' ) ) {
			return true;
		}

		return false;
	}
}
