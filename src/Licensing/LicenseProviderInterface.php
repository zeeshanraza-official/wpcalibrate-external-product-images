<?php
/**
 * Interface for WPCalibrate licensing providers.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Licensing;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Defines contract for future licensing integrations.
 */
interface LicenseProviderInterface {

	/**
	 * Check if a licensing provider is configured and available.
	 *
	 * @return bool
	 */
	public function is_configured(): bool;

	/**
	 * Get human-readable license status label.
	 *
	 * @return string
	 */
	public function get_status_label(): string;

	/**
	 * Get masked license key if set.
	 *
	 * @return string
	 */
	public function get_masked_key(): string;

	/**
	 * Get provider details or diagnostic info.
	 *
	 * @return array<string, mixed>
	 */
	public function get_info(): array;
}
