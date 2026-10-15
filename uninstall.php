<?php
/**
 * Plugin uninstaller.
 *
 * Runs only when the plugin is deleted via the WordPress admin plugins dashboard.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// Check administrator opt-in setting for data deletion.
$settings = get_option( 'wpcalibrate_epi_settings', [] );
$delete_data = is_array( $settings ) && ! empty( $settings['delete_data_on_uninstall'] );

if ( ! $delete_data ) {
	// Administrator chose to preserve plugin configuration and external image metadata.
	return;
}

global $wpdb;

// 1. Delete plugin-specific options.
delete_option( 'wpcalibrate_epi_settings' );
delete_option( 'wpcalibrate_epi_version' );

// 2. Delete plugin transients from options table.
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
		'_transient_wpcalibrate_epi_%',
		'_transient_timeout_wpcalibrate_epi_%'
	)
);

// 3. Delete plugin-specific post metadata.
$meta_keys = [
	'_wpcalibrate_epi_featured_url',
	'_wpcalibrate_epi_featured_alt',
	'_wpcalibrate_epi_gallery',
	'_wpcalibrate_epi_cached_featured_id',
	'_wpcalibrate_epi_cache_map',
	'_wpcalibrate_epi_source_version',
	'_wpcalibrate_epi_variation_url',
	'_wpcalibrate_epi_variation_alt',
	'_wpcalibrate_epi_variation_cached_id',
];

$placeholders = implode( ', ', array_fill( 0, count( $meta_keys ), '%s' ) );
// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
$wpdb->query( $wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key IN ($placeholders)", ...$meta_keys ) );

// 4. If HPOS (High-Performance Order Storage) or custom tables contain product meta if relevant,
// native $wpdb->postmeta covers standard WooCommerce products and variations.

// 5. Unschedule any remaining Action Scheduler tasks for this plugin.
if ( function_exists( 'as_unschedule_all_actions' ) ) {
	as_unschedule_all_actions( '', [], 'wpcalibrate-external-product-images' );
}
