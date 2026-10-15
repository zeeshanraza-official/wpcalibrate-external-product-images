<?php
/**
 * Settings repository and access layer.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manages plugin configuration option retrieval and defaults.
 */
class Settings {

	/**
	 * Option name stored in WordPress options table.
	 */
	public const OPTION_NAME = 'wpcalibrate_epi_settings';

	/**
	 * Cache of loaded settings in memory.
	 *
	 * @var array<string, mixed>|null
	 */
	private static ?array $settings_cache = null;

	/**
	 * Return the full array of default plugin settings.
	 *
	 * @return array<string, mixed>
	 */
	public static function get_defaults(): array {
		return [
			// General.
			'enabled'                     => true,
			'https_only'                  => true,
			'default_fallback'            => 'woocommerce_placeholder',
			'custom_fallback_id'          => 0,

			// Image Handling.
			'enable_featured'             => true,
			'enable_gallery'              => true,
			'enable_variations'           => true,
			'client_fallback'             => true,
			'default_alt_mode'            => 'product_title',

			// Cache.
			'cache_mode'                  => 'direct', // 'direct' or 'local'.
			'auto_cache_on_save'          => false,
			'delete_local_on_url_change'  => false,
			'max_download_size_mb'        => 10,
			'health_check_lifetime_hours' => 24,

			// Import & Export.
			'csv_delimiter'               => ',',

			// Data retention.
			'delete_data_on_uninstall'    => false,

			// Logging.
			'debug_logging'               => false,
		];
	}

	/**
	 * Get all settings merged with defaults.
	 *
	 * @param bool $force_refresh Whether to bypass the in-memory cache.
	 * @return array<string, mixed>
	 */
	public static function get_all( bool $force_refresh = false ): array {
		if ( null !== self::$settings_cache && ! $force_refresh ) {
			return self::$settings_cache;
		}

		$raw = get_option( self::OPTION_NAME, [] );
		if ( ! is_array( $raw ) ) {
			$raw = [];
		}

		self::$settings_cache = wp_parse_args( $raw, self::get_defaults() );
		return self::$settings_cache;
	}

	/**
	 * Get a single setting value with fallback to default.
	 *
	 * @param string $key Setting key.
	 * @param mixed  $fallback Optional explicit fallback if not in defaults.
	 * @return mixed
	 */
	public static function get( string $key, mixed $fallback = null ): mixed {
		$all = self::get_all();

		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}

		$defaults = self::get_defaults();
		if ( array_key_exists( $key, $defaults ) ) {
			return $defaults[ $key ];
		}

		return $fallback;
	}

	/**
	 * Update settings.
	 *
	 * @param array<string, mixed> $new_settings Settings to update.
	 * @return bool
	 */
	public static function update_all( array $new_settings ): bool {
		$sanitized = Sanitizer::sanitize_settings( $new_settings );
		// When saving, store with autoload = false to prevent bloating every WP request.
		$updated = update_option( self::OPTION_NAME, $sanitized, false );
		self::$settings_cache = $sanitized;
		return $updated;
	}

	/**
	 * Clear memory cache.
	 *
	 * @return void
	 */
	public static function clear_cache(): void {
		self::$settings_cache = null;
	}
}
