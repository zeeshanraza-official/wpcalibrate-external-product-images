<?php
/**
 * WooCommerce Product Variation External Image Fields.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Admin;

use WPCalibrate\ExternalProductImages\Images\ProductMetaRepository;
use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles variation metabox fields and AJAX variation persistence.
 */
class VariationFields {

	/**
	 * Initialize variation hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		$variations_enabled = (bool) Settings::get( 'enable_variations', true );

		if ( ! $plugin_enabled || ! $variations_enabled ) {
			return;
		}

		add_action( 'woocommerce_product_after_variable_attributes', [ $this, 'render_variation_fields' ], 10, 3 );
		add_action( 'woocommerce_save_product_variation', [ $this, 'save_variation_fields' ], 10, 2 );
	}

	/**
	 * Render external image inputs inside each WooCommerce variation row.
	 *
	 * @param int      $loop Loop index.
	 * @param array    $variation_data Variation metadata.
	 * @param \WP_Post $variation Variation post object.
	 * @return void
	 */
	public function render_variation_fields( int $loop, array $variation_data, \WP_Post $variation ): void {
		$var_id       = $variation->ID;
		$url          = ProductMetaRepository::get_variation_url( $var_id );
		$alt          = ProductMetaRepository::get_variation_alt( $var_id );
		$cached_id    = ProductMetaRepository::get_variation_cached_id( $var_id );
		$cache_mode   = (string) Settings::get( 'cache_mode', 'direct' );
		?>
		<div class="wpcalibrate-variation-image-wrap form-row form-row-full" style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-top: 10px;">
			<h4 style="margin: 0 0 8px 0; font-size: 13px; color: #1e293b; display: flex; align-items: center; justify-content: space-between;">
				<span><?php esc_html_e( 'External Variation Image', 'wpcalibrate-external-product-images' ); ?></span>
				<?php if ( ! empty( $url ) ) : ?>
					<?php if ( 'local' === $cache_mode && $cached_id > 0 ) : ?>
						<span class="wpcalibrate-status-tag is-cached"><?php esc_html_e( 'Cached Locally', 'wpcalibrate-external-product-images' ); ?></span>
					<?php else : ?>
						<span class="wpcalibrate-status-tag is-direct"><?php esc_html_e( 'Direct Remote URL', 'wpcalibrate-external-product-images' ); ?></span>
					<?php endif; ?>
				<?php endif; ?>
			</h4>

			<div style="display: flex; gap: 8px; align-items: center;">
				<input type="url"
					name="_wpcalibrate_epi_variation_url[<?php echo esc_attr( (string) $loop ); ?>]"
					value="<?php echo esc_url( $url ); ?>"
					placeholder="https://example.com/variations/blue.jpg"
					class="regular-text"
					style="flex: 2;" />

				<input type="text"
					name="_wpcalibrate_epi_variation_alt[<?php echo esc_attr( (string) $loop ); ?>]"
					value="<?php echo esc_attr( $alt ); ?>"
					placeholder="<?php esc_attr_e( 'Variation alt text (optional)', 'wpcalibrate-external-product-images' ); ?>"
					class="regular-text"
					style="flex: 1;" />
			</div>
		</div>
		<?php
	}

	/**
	 * Save variation fields when WooCommerce saves a variation row.
	 *
	 * @param int $variation_id Variation post ID.
	 * @param int $i Loop index.
	 * @return void
	 */
	public function save_variation_fields( int $variation_id, int $i ): void {
		if ( ! current_user_can( 'edit_post', $variation_id ) ) {
			return;
		}

		if ( isset( $_POST['_wpcalibrate_epi_variation_url'][ $i ] ) ) {
			$raw_url = sanitize_text_field( wp_unslash( $_POST['_wpcalibrate_epi_variation_url'][ $i ] ) );
			ProductMetaRepository::set_variation_url( $variation_id, $raw_url );
		}

		if ( isset( $_POST['_wpcalibrate_epi_variation_alt'][ $i ] ) ) {
			$raw_alt = sanitize_text_field( wp_unslash( $_POST['_wpcalibrate_epi_variation_alt'][ $i ] ) );
			ProductMetaRepository::set_variation_alt( $variation_id, $raw_alt );
		}

		// Background caching queue if local cache mode and auto-caching is active.
		if ( (string) Settings::get( 'cache_mode', 'direct' ) === 'local' && (bool) Settings::get( 'auto_cache_on_save', false ) ) {
			do_action( 'wpcalibrate_epi_queue_cache_variation', $variation_id );
		}
	}
}
