<?php
/**
 * Permissions and Capabilities Helper.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Support;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enforces WordPress and WooCommerce role capabilities.
 */
class Capabilities {

	/**
	 * Verify if current user can manage plugin settings.
	 *
	 * @return bool
	 */
	public static function can_manage_settings(): bool {
		return current_user_can( 'manage_woocommerce' ) || current_user_can( 'manage_options' );
	}

	/**
	 * Verify if current user can edit a specific product or variation.
	 *
	 * @param int $post_id Product post ID.
	 * @return bool
	 */
	public static function can_edit_product( int $post_id ): bool {
		return current_user_can( 'edit_post', $post_id );
	}
}
