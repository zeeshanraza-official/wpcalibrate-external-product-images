<?php
/**
 * WooCommerce Logging Service.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Support;

use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles error and diagnostic logging via WC_Logger.
 */
class Logger {

	public const SOURCE = 'wpcalibrate-external-product-images';

	/**
	 * Log error message.
	 *
	 * @param string               $message Error description.
	 * @param array<string, mixed> $context Contextual data.
	 * @return void
	 */
	public static function error( string $message, array $context = [] ): void {
		self::log( 'error', $message, $context );
	}

	/**
	 * Log warning message.
	 *
	 * @param string               $message Warning description.
	 * @param array<string, mixed> $context Contextual data.
	 * @return void
	 */
	public static function warning( string $message, array $context = [] ): void {
		self::log( 'warning', $message, $context );
	}

	/**
	 * Log info message.
	 *
	 * @param string               $message Info description.
	 * @param array<string, mixed> $context Contextual data.
	 * @return void
	 */
	public static function info( string $message, array $context = [] ): void {
		self::log( 'info', $message, $context );
	}

	/**
	 * Log debug message (only when debug logging enabled).
	 *
	 * @param string               $message Debug description.
	 * @param array<string, mixed> $context Contextual data.
	 * @return void
	 */
	public static function debug( string $message, array $context = [] ): void {
		if ( ! (bool) Settings::get( 'debug_logging', false ) ) {
			return;
		}
		self::log( 'debug', $message, $context );
	}

	/**
	 * Write to WooCommerce logger if available.
	 *
	 * @param string               $level Log level.
	 * @param string               $message Log message.
	 * @param array<string, mixed> $context Additional data.
	 * @return void
	 */
	private static function log( string $level, string $message, array $context ): void {
		if ( ! function_exists( 'wc_get_logger' ) ) {
			return;
		}

		$logger = wc_get_logger();
		if ( ! $logger ) {
			return;
		}

		// Sanitize context to remove any secrets, authorization headers, or private tokens.
		$clean_context = [];
		foreach ( $context as $key => $val ) {
			if ( preg_match( '/(key|secret|token|auth|password)/i', (string) $key ) ) {
				$clean_context[ $key ] = '***REDACTED***';
			} elseif ( is_scalar( $val ) ) {
				$clean_context[ $key ] = $val;
			}
		}

		$clean_context['source'] = self::SOURCE;

		$logger->log( $level, $message, $clean_context );
	}
}
