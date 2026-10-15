<?php
/**
 * Action Scheduler Background Image Caching Job.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Background;

use WPCalibrate\ExternalProductImages\Images\CacheManager;
use WPCalibrate\ExternalProductImages\Images\ProductMetaRepository;
use WPCalibrate\ExternalProductImages\Support\Logger;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles asynchronous Action Scheduler queuing and execution for local image caching.
 */
class CacheImageJob {

	public const GROUP = 'wpcalibrate-external-product-images';
	public const ACTION_PRODUCT   = 'wpcalibrate_epi_process_cache_product';
	public const ACTION_VARIATION = 'wpcalibrate_epi_process_cache_variation';

	/**
	 * Initialize Action Scheduler job hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Listen for product queue events.
		add_action( 'wpcalibrate_epi_queue_cache_product', [ $this, 'enqueue_product_caching' ] );
		add_action( 'wpcalibrate_epi_queue_cache_variation', [ $this, 'enqueue_variation_caching' ] );

		// Job execution handlers.
		add_action( self::ACTION_PRODUCT, [ $this, 'process_product_caching' ], 10, 1 );
		add_action( self::ACTION_VARIATION, [ $this, 'process_variation_caching' ], 10, 1 );
	}

	/**
	 * Enqueue background job to cache product images.
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function enqueue_product_caching( int $product_id ): void {
		if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::ACTION_PRODUCT, [ 'product_id' => $product_id ], self::GROUP ) ) {
			return; // Deduplicate: already queued.
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::ACTION_PRODUCT, [ 'product_id' => $product_id ], self::GROUP );
		} else {
			// Direct synchronous fallback if Action Scheduler is not loaded.
			$this->process_product_caching( $product_id );
		}
	}

	/**
	 * Enqueue background job to cache variation image.
	 *
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	public function enqueue_variation_caching( int $variation_id ): void {
		if ( function_exists( 'as_has_scheduled_action' ) && as_has_scheduled_action( self::ACTION_VARIATION, [ 'variation_id' => $variation_id ], self::GROUP ) ) {
			return;
		}

		if ( function_exists( 'as_enqueue_async_action' ) ) {
			as_enqueue_async_action( self::ACTION_VARIATION, [ 'variation_id' => $variation_id ], self::GROUP );
		} else {
			$this->process_variation_caching( $variation_id );
		}
	}

	/**
	 * Process product caching job (idempotent).
	 *
	 * @param int $product_id Product ID.
	 * @return void
	 */
	public function process_product_caching( int $product_id ): void {
		// 1. Featured image.
		$featured_url = ProductMetaRepository::get_featured_url( $product_id );
		if ( ! empty( $featured_url ) ) {
			$alt = ProductMetaRepository::get_featured_alt( $product_id );
			$res = CacheManager::import_image( $featured_url, $product_id, $alt );
			if ( ! is_wp_error( $res ) && $res > 0 ) {
				ProductMetaRepository::set_cached_featured_id( $product_id, $res );
				Logger::info( sprintf( 'Successfully cached featured image for product #%d as attachment #%d', $product_id, $res ) );
			}
		}

		// 2. Gallery images.
		$gallery = ProductMetaRepository::get_gallery( $product_id );
		if ( ! empty( $gallery ) ) {
			$updated = false;
			foreach ( $gallery as $idx => $item ) {
				if ( empty( $item['cached_attachment_id'] ) && ! empty( $item['url'] ) ) {
					$att_id = CacheManager::import_image( $item['url'], $product_id, $item['alt'] ?? '' );
					if ( ! is_wp_error( $att_id ) && $att_id > 0 ) {
						$gallery[ $idx ]['cached_attachment_id'] = $att_id;
						$updated = true;
					}
				}
			}
			if ( $updated ) {
				ProductMetaRepository::set_gallery( $product_id, $gallery );
				Logger::info( sprintf( 'Successfully cached gallery images for product #%d', $product_id ) );
			}
		}
	}

	/**
	 * Process variation caching job (idempotent).
	 *
	 * @param int $variation_id Variation ID.
	 * @return void
	 */
	public function process_variation_caching( int $variation_id ): void {
		$var_url = ProductMetaRepository::get_variation_url( $variation_id );
		if ( ! empty( $var_url ) ) {
			$alt = ProductMetaRepository::get_variation_alt( $variation_id );
			$res = CacheManager::import_image( $var_url, $variation_id, $alt );
			if ( ! is_wp_error( $res ) && $res > 0 ) {
				ProductMetaRepository::set_variation_cached_id( $variation_id, $res );
				Logger::info( sprintf( 'Successfully cached variation image for variation #%d as attachment #%d', $variation_id, $res ) );
			}
		}
	}
}
