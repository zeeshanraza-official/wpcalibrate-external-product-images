<?php
/**
 * WooCommerce CSV Product Exporter Integration.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\WooCommerce;

use WPCalibrate\ExternalProductImages\Images\ProductMetaRepository;
use WPCalibrate\ExternalProductImages\Images\GalleryManager;
use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Adds external image columns to native WooCommerce CSV product exports.
 */
class CsvExporter {

	/**
	 * Initialize exporter hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'woocommerce_product_export_column_names', [ $this, 'add_export_columns' ], 10, 1 );
		add_filter( 'woocommerce_product_export_default_columns', [ $this, 'add_export_columns' ], 10, 1 );

		// Hook column data callbacks.
		add_filter( 'woocommerce_product_export_product_column_wpcalibrate_epi_featured_url', [ $this, 'export_featured_url' ], 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_wpcalibrate_epi_featured_alt', [ $this, 'export_featured_alt' ], 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_wpcalibrate_epi_gallery', [ $this, 'export_gallery_urls' ], 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_wpcalibrate_epi_variation_url', [ $this, 'export_variation_url' ], 10, 2 );
		add_filter( 'woocommerce_product_export_product_column_wpcalibrate_epi_variation_alt', [ $this, 'export_variation_alt' ], 10, 2 );
	}

	/**
	 * Add plugin-specific columns to exporter options.
	 *
	 * @param array $columns Existing export columns.
	 * @return array
	 */
	public function add_export_columns( array $columns ): array {
		$columns['wpcalibrate_epi_featured_url']   = __( 'External Featured Image', 'wpcalibrate-external-product-images' );
		$columns['wpcalibrate_epi_featured_alt']   = __( 'External Image Alt', 'wpcalibrate-external-product-images' );
		$columns['wpcalibrate_epi_gallery']        = __( 'External Gallery Images', 'wpcalibrate-external-product-images' );
		$columns['wpcalibrate_epi_variation_url'] = __( 'External Variation Image', 'wpcalibrate-external-product-images' );
		$columns['wpcalibrate_epi_variation_alt'] = __( 'External Variation Image Alt', 'wpcalibrate-external-product-images' );
		return $columns;
	}

	/**
	 * Export external featured image URL.
	 *
	 * @param mixed       $value Column value.
	 * @param \WC_Product $product Product instance.
	 * @return string
	 */
	public function export_featured_url( mixed $value, \WC_Product $product ): string {
		return ProductMetaRepository::get_featured_url( $product->get_id() );
	}

	/**
	 * Export external featured image alt text.
	 *
	 * @param mixed       $value Column value.
	 * @param \WC_Product $product Product instance.
	 * @return string
	 */
	public function export_featured_alt( mixed $value, \WC_Product $product ): string {
		return ProductMetaRepository::get_featured_alt( $product->get_id() );
	}

	/**
	 * Export external gallery URLs formatted with active delimiter.
	 *
	 * @param mixed       $value Column value.
	 * @param \WC_Product $product Product instance.
	 * @return string
	 */
	public function export_gallery_urls( mixed $value, \WC_Product $product ): string {
		$gallery   = ProductMetaRepository::get_gallery( $product->get_id() );
		$delimiter = (string) Settings::get( 'csv_delimiter', ',' );
		return GalleryManager::format_to_delimited_string( $gallery, $delimiter );
	}

	/**
	 * Export external variation image URL.
	 *
	 * @param mixed       $value Column value.
	 * @param \WC_Product $product Product instance.
	 * @return string
	 */
	public function export_variation_url( mixed $value, \WC_Product $product ): string {
		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) ) {
			return ProductMetaRepository::get_variation_url( $product->get_id() );
		}
		return '';
	}

	/**
	 * Export external variation image alt.
	 *
	 * @param mixed       $value Column value.
	 * @param \WC_Product $product Product instance.
	 * @return string
	 */
	public function export_variation_alt( mixed $value, \WC_Product $product ): string {
		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) ) {
			return ProductMetaRepository::get_variation_alt( $product->get_id() );
		}
		return '';
	}
}
