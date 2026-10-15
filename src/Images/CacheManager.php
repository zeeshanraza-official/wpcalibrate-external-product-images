<?php
/**
 * Local Cache Manager & Media Library Ingestion Service.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Images;

use WPCalibrate\ExternalProductImages\Settings\Settings;
use WPCalibrate\ExternalProductImages\Support\Logger;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Downloads permitted external images into the WordPress Media Library with full SSRF and MIME protections.
 */
class CacheManager {

	public const ATTACHMENT_META_SOURCE_URL = '_wpcalibrate_epi_source_url';

	/**
	 * Download and import an external image into WordPress Media Library.
	 *
	 * @param string $url Source external image URL.
	 * @param int    $parent_post_id Post ID to attach media to (product or variation).
	 * @param string $alt Optional alt text.
	 * @return int|WP_Error Attachment ID on success, or WP_Error on failure.
	 */
	public static function import_image( string $url, int $parent_post_id = 0, string $alt = '' ): int|WP_Error {
		$sanitized_url = UrlValidator::sanitize_url( $url );
		if ( empty( $sanitized_url ) ) {
			return new WP_Error( 'invalid_url', __( 'Cannot cache an empty or invalid image URL.', 'wpcalibrate-external-product-images' ) );
		}

		// 1. SSRF Safety check before connecting to remote server.
		$safety_check = UrlValidator::is_safe_for_server_fetch( $sanitized_url );
		if ( is_wp_error( $safety_check ) ) {
			Logger::warning( 'SSRF check blocked cache request: ' . $safety_check->get_error_message() );
			return $safety_check;
		}

		// 2. Check if a local attachment already exists for this exact source URL (Deduplication).
		$existing_id = self::find_existing_attachment_by_url( $sanitized_url );
		if ( $existing_id > 0 && wp_attachment_is_image( $existing_id ) ) {
			return $existing_id;
		}

		// 3. Prevent duplicate concurrent downloads with a lock transient.
		$lock_key = 'wpcalibrate_epi_lock_' . md5( $sanitized_url );
		if ( get_transient( $lock_key ) ) {
			return new WP_Error( 'download_in_progress', __( 'Download for this image is already in progress.', 'wpcalibrate-external-product-images' ) );
		}
		set_transient( $lock_key, true, 300 ); // 5 minute lock.

		// 4. Download file safely to temporary directory.
		$tmp_file = self::download_to_temp_file( $sanitized_url );
		delete_transient( $lock_key );

		if ( is_wp_error( $tmp_file ) ) {
			Logger::error( 'Remote download failed: ' . $tmp_file->get_error_message() );
			return $tmp_file;
		}

		// 5. Ingest temporary file into Media Library.
		$attachment_id = self::create_attachment_from_file( $tmp_file, $sanitized_url, $parent_post_id, $alt );

		// Clean up temp file.
		if ( file_exists( $tmp_file ) ) {
			@unlink( $tmp_file );
		}

		return $attachment_id;
	}

	/**
	 * Find an existing attachment that was previously imported from this source URL.
	 *
	 * @param string $source_url Source URL.
	 * @return int Attachment ID or 0.
	 */
	public static function find_existing_attachment_by_url( string $source_url ): int {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$attachment_id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = %s AND meta_value = %s LIMIT 1",
				self::ATTACHMENT_META_SOURCE_URL,
				$source_url
			)
		);

		return $attachment_id ? (int) $attachment_id : 0;
	}

	/**
	 * Download remote image to temporary file using wp_safe_remote_get.
	 *
	 * @param string $url Source URL.
	 * @return string|WP_Error Local temporary file path or WP_Error.
	 */
	private static function download_to_temp_file( string $url ): string|WP_Error {
		$max_mb    = (int) Settings::get( 'max_download_size_mb', 10 );
		$max_bytes = $max_mb * 1024 * 1024;

		// Perform safe GET request with strict limits and SSL verification.
		$response = wp_safe_remote_get(
			$url,
			[
				'timeout'     => 15,
				'redirection' => 3,
				'sslverify'   => true,
				'headers'     => [
					'Accept' => 'image/webp,image/png,image/jpeg,image/*;q=0.8',
				],
			]
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $status ) {
			return new WP_Error(
				'http_status_error',
				sprintf(
					/* translators: %d: HTTP response code */
					__( 'Remote host responded with HTTP status code %d.', 'wpcalibrate-external-product-images' ),
					$status
				)
			);
		}

		// Check Content-Type header.
		$content_type = wp_remote_retrieve_header( $response, 'content-type' );
		if ( ! is_string( $content_type ) || ! str_starts_with( strtolower( trim( $content_type ) ), 'image/' ) ) {
			return new WP_Error( 'invalid_content_type', __( 'The remote file is not an image (invalid Content-Type header).', 'wpcalibrate-external-product-images' ) );
		}

		$body = wp_remote_retrieve_body( $response );
		$size = strlen( $body );

		if ( $size > $max_bytes ) {
			return new WP_Error(
				'file_too_large',
				sprintf(
					/* translators: 1: File size in MB, 2: Max allowed MB */
					__( 'Image size (%1$s MB) exceeds maximum allowed limit (%2$s MB).', 'wpcalibrate-external-product-images' ),
					number_format( $size / ( 1024 * 1024 ), 2 ),
					$max_mb
				)
			);
		}

		if ( empty( $body ) ) {
			return new WP_Error( 'empty_body', __( 'The remote server returned an empty response body.', 'wpcalibrate-external-product-images' ) );
		}

		// Write to temporary file in uploads directory.
		$wp_upload_dir = wp_upload_dir();
		$temp_dir      = $wp_upload_dir['basedir'] . '/wpcalibrate-temp';
		if ( ! file_exists( $temp_dir ) ) {
			wp_mkdir_p( $temp_dir );
		}

		$filename  = wp_unique_filename( $temp_dir, 'epi_' . md5( $url ) . '.tmp' );
		$temp_path = $temp_dir . '/' . $filename;

		$written = file_put_contents( $temp_path, $body );
		if ( false === $written ) {
			return new WP_Error( 'write_failed', __( 'Could not write temporary file to uploads directory.', 'wpcalibrate-external-product-images' ) );
		}

		// Inspect actual image data using getimagesize to prevent disguised executable/HTML files.
		$image_info = @getimagesize( $temp_path );
		if ( false === $image_info || empty( $image_info['mime'] ) || ! str_starts_with( $image_info['mime'], 'image/' ) ) {
			@unlink( $temp_path );
			return new WP_Error( 'corrupt_image', __( 'The downloaded payload is not a valid or supported image format.', 'wpcalibrate-external-product-images' ) );
		}

		return $temp_path;
	}

	/**
	 * Insert file into WordPress Media Library and create standard metadata.
	 *
	 * @param string $temp_file Path to temporary file.
	 * @param string $source_url Source URL.
	 * @param int    $parent_post_id Parent post ID.
	 * @param string $alt Alt text.
	 * @return int|WP_Error
	 */
	private static function create_attachment_from_file( string $temp_file, string $source_url, int $parent_post_id, string $alt ): int|WP_Error {
		$image_info = @getimagesize( $temp_file );
		$mime_type  = $image_info['mime'] ?? 'image/jpeg';

		// Determine safe extension based on MIME type.
		$ext_map = [
			'image/jpeg' => 'jpg',
			'image/png'  => 'png',
			'image/gif'  => 'gif',
			'image/webp' => 'webp',
			'image/avif' => 'avif',
		];
		$ext = $ext_map[ $mime_type ] ?? 'jpg';

		// Extract suggested filename from source URL path.
		$url_path = wp_parse_url( $source_url, PHP_URL_PATH );
		$base     = $url_path ? pathinfo( (string) $url_path, PATHINFO_FILENAME ) : 'product-image';
		$base     = sanitize_file_name( $base );
		if ( empty( $base ) ) {
			$base = 'product-image';
		}
		$target_filename = $base . '.' . $ext;

		$upload_dir = wp_upload_dir();
		$final_filename = wp_unique_filename( $upload_dir['path'], $target_filename );
		$final_path     = $upload_dir['path'] . '/' . $final_filename;

		// Move temp file to final uploads destination.
		if ( ! @rename( $temp_file, $final_path ) && ! @copy( $temp_file, $final_path ) ) {
			return new WP_Error( 'move_failed', __( 'Could not move image into WordPress uploads directory.', 'wpcalibrate-external-product-images' ) );
		}

		$title = sanitize_text_field( str_replace( [ '-', '_' ], ' ', $base ) );

		$attachment_data = [
			'post_mime_type' => $mime_type,
			'post_title'     => $title,
			'post_content'   => '',
			'post_status'    => 'inherit',
			'guid'           => $upload_dir['url'] . '/' . $final_filename,
		];

		$attachment_id = wp_insert_attachment( $attachment_data, $final_path, $parent_post_id );

		if ( is_wp_error( $attachment_id ) || 0 === $attachment_id ) {
			@unlink( $final_path );
			return new WP_Error( 'insert_failed', __( 'Could not create attachment post in database.', 'wpcalibrate-external-product-images' ) );
		}

		// Include image.php functions for generating metadata and thumbnails.
		if ( ! function_exists( 'wp_generate_attachment_metadata' ) ) {
			require_once ABSPATH . 'wp-admin/includes/image.php';
		}

		$meta = wp_generate_attachment_metadata( $attachment_id, $final_path );
		wp_update_attachment_metadata( $attachment_id, $meta );

		// Set alt text if provided.
		if ( ! empty( $alt ) ) {
			update_post_meta( $attachment_id, '_wp_attachment_image_alt', sanitize_text_field( $alt ) );
		}

		// Store source URL to prevent duplicate future imports.
		update_post_meta( $attachment_id, self::ATTACHMENT_META_SOURCE_URL, $source_url );

		return $attachment_id;
	}
}
