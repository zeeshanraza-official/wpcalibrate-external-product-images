<?php
/**
 * Bulk & Quick Edit Product Support.
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
 * Adds external featured image controls to WooCommerce bulk-edit and quick-edit interfaces.
 */
class BulkEdit {

	/**
	 * Initialize bulk edit hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		if ( ! $plugin_enabled ) {
			return;
		}

		add_action( 'woocommerce_product_bulk_edit_start', [ $this, 'render_bulk_edit_fields' ] );
		add_action( 'woocommerce_product_bulk_edit_save', [ $this, 'save_bulk_edit_fields' ], 10, 1 );

		// Quick Edit hooks.
		add_action( 'quick_edit_custom_box', [ $this, 'render_quick_edit_box' ], 10, 2 );
		add_action( 'woocommerce_product_quick_edit_save', [ $this, 'save_quick_edit_fields' ], 10, 1 );
	}

	/**
	 * Render bulk edit fields in WooCommerce bulk edit table.
	 *
	 * @return void
	 */
	public function render_bulk_edit_fields(): void {
		?>
		<div class="inline-edit-group wpcalibrate-bulk-group" style="padding: 6px 0;">
			<label class="alignleft" style="width: 100%;">
				<span class="title"><?php esc_html_e( 'External Image', 'wpcalibrate-external-product-images' ); ?></span>
				<span class="input-text-wrap" style="display: flex; gap: 8px;">
					<select name="wpcalibrate_bulk_featured_action" class="wpcalibrate-bulk-action-select">
						<option value="no_change"><?php esc_html_e( '— No Change —', 'wpcalibrate-external-product-images' ); ?></option>
						<option value="set"><?php esc_html_e( 'Set/Replace Image URL', 'wpcalibrate-external-product-images' ); ?></option>
						<option value="clear"><?php esc_html_e( 'Clear External Image', 'wpcalibrate-external-product-images' ); ?></option>
					</select>
					<input type="url" name="wpcalibrate_bulk_featured_url" placeholder="https://example.com/image.jpg" class="regular-text" style="flex: 1;" />
				</span>
			</label>
		</div>
		<?php
	}

	/**
	 * Handle saving bulk edit updates for each product.
	 *
	 * @param \WC_Product $product Product instance being saved.
	 * @return void
	 */
	public function save_bulk_edit_fields( \WC_Product $product ): void {
		$product_id = $product->get_id();
		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		$action = isset( $_REQUEST['wpcalibrate_bulk_featured_action'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['wpcalibrate_bulk_featured_action'] ) ) : 'no_change';

		if ( 'clear' === $action ) {
			ProductMetaRepository::set_featured_url( $product_id, '' );
			ProductMetaRepository::set_featured_alt( $product_id, '' );
		} elseif ( 'set' === $action && ! empty( $_REQUEST['wpcalibrate_bulk_featured_url'] ) ) {
			$url = sanitize_text_field( wp_unslash( $_REQUEST['wpcalibrate_bulk_featured_url'] ) );
			ProductMetaRepository::set_featured_url( $product_id, $url );
		}
	}

	/**
	 * Render Quick Edit field on the Products list table.
	 *
	 * @param string $column_name Current column name.
	 * @param string $post_type Post type.
	 * @return void
	 */
	public function render_quick_edit_box( string $column_name, string $post_type ): void {
		if ( 'product' !== $post_type || 'thumb' !== $column_name ) {
			return;
		}
		?>
		<fieldset class="inline-edit-col-right">
			<div class="inline-edit-col">
				<label class="alignleft">
					<span class="title"><?php esc_html_e( 'Ext. Image URL', 'wpcalibrate-external-product-images' ); ?></span>
					<span class="input-text-wrap">
						<input type="url" name="_wpcalibrate_quick_featured_url" class="text regular-text" placeholder="https://example.com/image.jpg" />
					</span>
				</label>
			</div>
		</fieldset>
		<?php
	}

	/**
	 * Save Quick Edit field.
	 *
	 * @param \WC_Product $product Product instance.
	 * @return void
	 */
	public function save_quick_edit_fields( \WC_Product $product ): void {
		$product_id = $product->get_id();
		if ( ! current_user_can( 'edit_post', $product_id ) ) {
			return;
		}

		if ( isset( $_POST['_wpcalibrate_quick_featured_url'] ) ) {
			$url = sanitize_text_field( wp_unslash( $_POST['_wpcalibrate_quick_featured_url'] ) );
			ProductMetaRepository::set_featured_url( $product_id, $url );
		}
	}
}
