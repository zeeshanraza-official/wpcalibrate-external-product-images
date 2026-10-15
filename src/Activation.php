<?php
/**
 * Plugin activation handler.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages;

use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles activation tasks, version stamping, and default configuration.
 */
class Activation {

	/**
	 * Run activation routine.
	 *
	 * @return void
	 */
	public static function activate(): void {
		// Populate initial default settings if option does not exist yet.
		$existing_settings = get_option( Settings::OPTION_NAME, null );
		if ( null === $existing_settings ) {
			add_option( Settings::OPTION_NAME, Settings::get_defaults(), '', false );
		}

		// Store schema and plugin version.
		update_option( 'wpcalibrate_epi_version', WPCALIBRATE_EPI_VERSION, false );

		// Flush rewrite rules safely if needed.
		set_transient( 'wpcalibrate_epi_activated', true, 60 );
	}
}
