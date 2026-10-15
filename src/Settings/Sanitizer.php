<?php
/**
 * Settings input sanitizer and validator.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sanitizes and validates global settings with strict allowlists and type checking.
 */
class Sanitizer {

	/**
	 * Sanitize an entire settings payload.
	 *
	 * @param array<string, mixed> $input Raw input.
	 * @return array<string, mixed> Sanitized settings.
	 */
	public static function sanitize_settings( array $input ): array {
		$defaults = Settings::get_defaults();
		$clean    = [];

		// Boolean flags.
		$boolean_keys = [
			'enabled',
			'https_only',
			'enable_featured',
			'enable_gallery',
			'enable_variations',
			'client_fallback',
			'auto_cache_on_save',
			'delete_local_on_url_change',
			'delete_data_on_uninstall',
			'debug_logging',
		];

		foreach ( $boolean_keys as $key ) {
			if ( isset( $input[ $key ] ) ) {
				$clean[ $key ] = filter_var( $input[ $key ], FILTER_VALIDATE_BOOLEAN );
			} else {
				$clean[ $key ] = false;
			}
		}

		// Enumerations.
		$fallback_modes           = [ 'woocommerce_placeholder', 'custom_attachment', 'none' ];
		$clean['default_fallback'] = isset( $input['default_fallback'] ) && in_array( (string) $input['default_fallback'], $fallback_modes, true )
			? (string) $input['default_fallback']
			: $defaults['default_fallback'];

		$alt_modes                = [ 'product_title', 'filename', 'empty' ];
		$clean['default_alt_mode'] = isset( $input['default_alt_mode'] ) && in_array( (string) $input['default_alt_mode'], $alt_modes, true )
			? (string) $input['default_alt_mode']
			: $defaults['default_alt_mode'];

		$cache_modes         = [ 'direct', 'local' ];
		$clean['cache_mode'] = isset( $input['cache_mode'] ) && in_array( (string) $input['cache_mode'], $cache_modes, true )
			? (string) $input['cache_mode']
			: $defaults['cache_mode'];

		// Integers.
		$clean['custom_fallback_id'] = isset( $input['custom_fallback_id'] )
			? absint( $input['custom_fallback_id'] )
			: 0;

		$max_mb                          = isset( $input['max_download_size_mb'] ) ? absint( $input['max_download_size_mb'] ) : 10;
		$clean['max_download_size_mb']   = max( 1, min( 100, $max_mb ) );

		$lifetime_hours                  = isset( $input['health_check_lifetime_hours'] ) ? absint( $input['health_check_lifetime_hours'] ) : 24;
		$clean['health_check_lifetime_hours'] = max( 1, min( 168, $lifetime_hours ) );

		// Strings.
		$delimiter              = isset( $input['csv_delimiter'] ) ? sanitize_text_field( (string) $input['csv_delimiter'] ) : ',';
		$clean['csv_delimiter'] = in_array( $delimiter, [ ',', ';', '\t', '|' ], true ) ? $delimiter : ',';

		return $clean;
	}
}
