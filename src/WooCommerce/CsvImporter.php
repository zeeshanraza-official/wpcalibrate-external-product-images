<?php
/**
 * WooCommerce CSV Product Importer Integration.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\WooCommerce;

use WPCalibrate\ExternalProductImages\Images\ProductMetaRepository;
use WPCalibrate\ExternalProductImages\Images\GalleryManager;
use WPCalibrate\ExternalProductImages\Images\UrlValidator;
use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Maps and processes external image CSV columns during native WooCommerce product imports.
 */
class CsvImporter {

	/**
	 * Initialize importer hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_filter( 'woocommerce_csv_product_import_mapping_options', [ $this, 'add_mapping_options' ], 10, 2 );
		add_filter( 'woocommerce_csv_product_import_mapping_default_columns', [ $this, 'add_default_mappings' ], 10, 1 );
		add_action( 'woocommerce_product_import_inserted_product_object', [ $this, 'process_imported_product' ], 10, 2 );
	}

	/**
	 * Add plugin fields to the CSV column mapping dropdown.
	 *
	 * @param array $options Mapping options list.
	 * @param array $item Sample column item.
	 * @return array
	 */
	public function add_mapping_options( array $options, array $item ): array {
		$options['wpcalibrate_epi_featured_url']   = __( 'External Featured Image', 'wpcalibrate-external-product-images' );
		$options['wpcalibrate_epi_featured_alt']   = __( 'External Image Alt', 'wpcalibrate-external-product-images' );
		$options['wpcalibrate_epi_gallery']        = __( 'External Gallery Images', 'wpcalibrate-external-product-images' );
		$options['wpcalibrate_epi_variation_url'] = __( 'External Variation Image', 'wpcalibrate-external-product-images' );
		$options['wpcalibrate_epi_variation_alt'] = __( 'External Variation Image Alt', 'wpcalibrate-external-product-images' );
		return $options;
	}

	/**
	 * Automatically recognize and map plugin CSV column headers.
	 *
	 * @param array $columns Default mapping table.
	 * @return array
	 */
	public function add_default_mappings( array $columns ): array {
		$columns[ __( 'External Featured Image', 'wpcalibrate-external-product-images' ) ]   = 'wpcalibrate_epi_featured_url';
		$columns[ __( 'External Image Alt', 'wpcalibrate-external-product-images' ) ]        = 'wpcalibrate_epi_featured_alt';
		$columns[ __( 'External Gallery Images', 'wpcalibrate-external-product-images' ) ]   = 'wpcalibrate_epi_gallery';
		$columns[ __( 'External Variation Image', 'wpcalibrate-external-product-images' ) ] = 'wpcalibrate_epi_variation_url';
		$columns[ __( 'External Variation Image Alt', 'wpcalibrate-external-product-images' ) ] = 'wpcalibrate_epi_variation_alt';

		// Also match exact English column headers regardless of active locale.
		$columns['External Featured Image']   = 'wpcalibrate_epi_featured_url';
		$columns['External Image Alt']        = 'wpcalibrate_epi_featured_alt';
		$columns['External Gallery Images']   = 'wpcalibrate_epi_gallery';
		$columns['External Variation Image'] = 'wpcalibrate_epi_variation_url';
		$columns['External Variation Image Alt'] = 'wpcalibrate_epi_variation_alt';

		return $columns;
	}

	/**
	 * Save external image data when a product or variation row is inserted/updated by CSV importer.
	 *
	 * @param \WC_Product $product Inserted product instance.
	 * @param array       $data Mapped CSV row data.
	 * @return void
	 */
	public function process_imported_product( \WC_Product $product, array $data ): void {
		$product_id = $product->get_id();
		$delimiter  = (string) Settings::get( 'csv_delimiter', ',' );

		// 1. Featured image URL.
		if ( ! empty( $data['wpcalibrate_epi_featured_url'] ) ) {
			$raw_url = trim( (string) $data['wpcalibrate_epi_featured_url'] );
			if ( UrlValidator::is_valid_url( $raw_url ) ) {
				ProductMetaRepository::set_featured_url( $product_id, $raw_url );
			}
		}

		// 2. Featured Alt text.
		if ( isset( $data['wpcalibrate_epi_featured_alt'] ) ) {
			ProductMetaRepository::set_featured_alt( $product_id, (string) $data['wpcalibrate_epi_featured_alt'] );
		}

		// 3. Gallery URLs.
		if ( ! empty( $data['wpcalibrate_epi_gallery'] ) ) {
			$gallery_items = GalleryManager::parse_delimited_urls( (string) $data['wpcalibrate_epi_gallery'], $delimiter );
			if ( ! empty( $gallery_items ) ) {
				ProductMetaRepository::set_gallery( $product_id, $gallery_items );
			}
		}

		// 4. Variation Image (for variation product rows).
		if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) ) {
			if ( ! empty( $data['wpcalibrate_epi_variation_url'] ) ) {
				$var_url = trim( (string) $data['wpcalibrate_epi_variation_url'] );
				if ( UrlValidator::is_valid_url( $var_url ) ) {
					ProductMetaRepository::set_variation_url( $product_id, $var_url );
				}
			}

			if ( isset( $data['wpcalibrate_epi_variation_alt'] ) ) {
				ProductMetaRepository::set_variation_alt( $product_id, (string) $data['wpcalibrate_epi_variation_alt'] );
			}
		}

		// If local caching and auto-caching is active, enqueue background job.
		if ( (string) Settings::get( 'cache_mode', 'direct' ) === 'local' && (bool) Settings::get( 'auto_cache_on_save', false ) ) {
			if ( method_exists( $product, 'is_type' ) && $product->is_type( 'variation' ) ) {
				do_action( 'wpcalibrate_epi_queue_cache_variation', $product_id );
			} else {
				do_action( 'wpcalibrate_epi_queue_cache_product', $product_id );
			}
		}
	}
}
