<?php
/**
 * Plugin deactivation handler.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles deactivation tasks, cleans up pending scheduled actions, and stops background processing.
 */
class Deactivation {

	/**
	 * Run deactivation routine.
	 *
	 * @return void
	 */
	public static function deactivate(): void {
		// Cancel any scheduled Action Scheduler background jobs for this plugin.
		if ( function_exists( 'as_unschedule_all_actions' ) ) {
			as_unschedule_all_actions( '', [], 'wpcalibrate-external-product-images' );
		}

		delete_transient( 'wpcalibrate_epi_activated' );
	}
}
