<?php
/**
 * Unconfigured License Provider implementation.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Clean baseline provider representing the initial unconfigured state.
 */
class UnconfiguredLicenseProvider implements LicenseProviderInterface {

	/**
	 * Provider is deliberately not configured in v1.0.0.
	 *
	 * @return bool
	 */
	public function is_configured(): bool {
		return false;
	}

	/**
	 * Neutral status label.
	 *
	 * @return string
	 */
	public function get_status_label(): string {
		return __( 'Licensing provider not configured', 'wpcalibrate-external-product-images' );
	}

	/**
	 * Returns empty or masked key.
	 *
	 * @return string
	 */
	public function get_masked_key(): string {
		$raw_key = (string) get_option( 'wpcalibrate_epi_license_key', '' );
		if ( empty( $raw_key ) ) {
			return '';
		}

		$len = strlen( $raw_key );
		if ( $len <= 8 ) {
			return str_repeat( '•', $len );
		}

		return substr( $raw_key, 0, 4 ) . str_repeat( '•', $len - 8 ) . substr( $raw_key, -4 );
	}

	/**
	 * Provider metadata.
	 *
	 * @return array<string, mixed>
	 */
	public function get_info(): array {
		return [
			'configured' => false,
			'provider'   => 'none',
			'message'    => __( 'Commercial licensing features are not active in this installation. All core external image, gallery, variation, and CSV features operate without restrictions.', 'wpcalibrate-external-product-images' ),
		];
	}
}
