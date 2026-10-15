<?php
/**
 * Product Edit Screen Fields (External Featured Image & Gallery).
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Admin;

use WPCalibrate\ExternalProductImages\Images\ProductMetaRepository;
use WPCalibrate\ExternalProductImages\Images\UrlValidator;
use WPCalibrate\ExternalProductImages\Settings\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles metabox rendering and saving for WooCommerce products.
 */
class ProductFields {

	/**
	 * Nonce action name for saving external product image data.
	 */
	public const SAVE_ACTION = 'wpcalibrate_epi_save_product_meta';

	/**
	 * Nonce field name.
	 */
	public const NONCE_NAME = 'wpcalibrate_epi_product_nonce';

	/**
	 * Initialize hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );
		add_action( 'save_post_product', [ $this, 'save_product_fields' ], 10, 2 );
	}

	/**
	 * Register external image metaboxes on the WooCommerce product edit screen.
	 *
	 * @return void
	 */
	public function register_meta_boxes(): void {
		$plugin_enabled = (bool) Settings::get( 'enabled', true );
		if ( ! $plugin_enabled ) {
			return;
		}

		$featured_enabled = (bool) Settings::get( 'enable_featured', true );
		if ( $featured_enabled ) {
			add_meta_box(
				'wpcalibrate_epi_featured_box',
				__( 'External Featured Image', 'wpcalibrate-external-product-images' ),
				[ $this, 'render_featured_meta_box' ],
				'product',
				'side',
				'low'
			);
		}

		$gallery_enabled = (bool) Settings::get( 'enable_gallery', true );
		if ( $gallery_enabled ) {
			add_meta_box(
				'wpcalibrate_epi_gallery_box',
				__( 'External Product Gallery', 'wpcalibrate-external-product-images' ),
				[ $this, 'render_gallery_meta_box' ],
				'product',
				'normal',
				'low'
			);
		}
	}

	/**
	 * Render the External Featured Image side metabox.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render_featured_meta_box( \WP_Post $post ): void {
		wp_nonce_field( self::SAVE_ACTION, self::NONCE_NAME );

		$featured_url = ProductMetaRepository::get_featured_url( $post->ID );
		$featured_alt = ProductMetaRepository::get_featured_alt( $post->ID );
		$cached_id    = ProductMetaRepository::get_cached_featured_id( $post->ID );
		$cache_mode   = (string) Settings::get( 'cache_mode', 'direct' );
		?>
		<div class="wpcalibrate-product-metabox">
			<div class="wpcalibrate-meta-row">
				<label for="wpcalibrate_epi_featured_url" class="wpcalibrate-meta-label">
					<?php esc_html_e( 'External Image URL (HTTPS)', 'wpcalibrate-external-product-images' ); ?>
				</label>
				<input type="url"
					id="wpcalibrate_epi_featured_url"
					name="_wpcalibrate_epi_featured_url"
					value="<?php echo esc_url( $featured_url ); ?>"
					placeholder="https://example.com/images/product.jpg"
					class="widefat"
					autocomplete="off" />
			</div>

			<div class="wpcalibrate-meta-row">
				<label for="wpcalibrate_epi_featured_alt" class="wpcalibrate-meta-label">
					<?php esc_html_e( 'Alt Text Override (Optional)', 'wpcalibrate-external-product-images' ); ?>
				</label>
				<input type="text"
					id="wpcalibrate_epi_featured_alt"
					name="_wpcalibrate_epi_featured_alt"
					value="<?php echo esc_attr( $featured_alt ); ?>"
					placeholder="<?php esc_attr_e( 'Leave empty to use product title', 'wpcalibrate-external-product-images' ); ?>"
					class="widefat" />
			</div>

			<!-- Live Preview Area -->
			<div id="wpcalibrate_featured_preview_wrap" class="wpcalibrate-preview-box" style="<?php echo empty( $featured_url ) ? 'display:none;' : ''; ?>">
				<div class="wpcalibrate-preview-img-wrap">
					<img id="wpcalibrate_featured_preview_img"
						src="<?php echo esc_url( $featured_url ); ?>"
						alt="<?php echo esc_attr( $featured_alt ); ?>" />
				</div>
				<div class="wpcalibrate-preview-meta">
					<?php if ( 'local' === $cache_mode && $cached_id > 0 ) : ?>
						<span class="wpcalibrate-status-tag is-cached"><?php esc_html_e( 'Cached Locally', 'wpcalibrate-external-product-images' ); ?></span>
					<?php else : ?>
						<span class="wpcalibrate-status-tag is-direct"><?php esc_html_e( 'Direct Remote URL', 'wpcalibrate-external-product-images' ); ?></span>
					<?php endif; ?>
					<br />
					<button type="button" id="wpcalibrate_clear_featured_btn" class="button-link-delete" style="margin-top: 6px; font-size: 12px;">
						<?php esc_html_e( 'Clear External Image', 'wpcalibrate-external-product-images' ); ?>
					</button>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the External Product Gallery normal metabox.
	 *
	 * @param \WP_Post $post Current post object.
	 * @return void
	 */
	public function render_gallery_meta_box( \WP_Post $post ): void {
		$gallery_items = ProductMetaRepository::get_gallery( $post->ID );
		?>
		<div class="wpcalibrate-gallery-manager">
			<p class="description">
				<?php esc_html_e( 'Add external image URLs for this product gallery. Reorder using drag-and-drop or the accessible Move Up/Down buttons.', 'wpcalibrate-external-product-images' ); ?>
			</p>

			<div class="wpcalibrate-gallery-add-bar" style="display: flex; gap: 8px; margin: 12px 0;">
				<input type="url" id="wpcalibrate_new_gallery_url" placeholder="https://example.com/gallery/img1.jpg" class="regular-text" style="flex: 1;" />
				<input type="text" id="wpcalibrate_new_gallery_alt" placeholder="<?php esc_attr_e( 'Alt text (optional)', 'wpcalibrate-external-product-images' ); ?>" class="regular-text" style="flex: 1;" />
				<button type="button" id="wpcalibrate_add_gallery_btn" class="button">
					<?php esc_html_e( 'Add Image', 'wpcalibrate-external-product-images' ); ?>
				</button>
				<button type="button" id="wpcalibrate_toggle_bulk_btn" class="button">
					<?php esc_html_e( 'Bulk Add...', 'wpcalibrate-external-product-images' ); ?>
				</button>
			</div>

			<!-- Bulk Add Modal / Section -->
			<div id="wpcalibrate_bulk_gallery_wrap" style="display: none; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px; margin-bottom: 12px;">
				<label for="wpcalibrate_bulk_gallery_textarea" style="font-weight: 600; display: block; margin-bottom: 4px;">
					<?php esc_html_e( 'Paste multiple image URLs (one per line):', 'wpcalibrate-external-product-images' ); ?>
				</label>
				<textarea id="wpcalibrate_bulk_gallery_textarea" rows="4" class="widefat" placeholder="https://example.com/p1.jpg&#10;https://example.com/p2.jpg"></textarea>
				<div style="margin-top: 8px; display: flex; gap: 8px;">
					<button type="button" id="wpcalibrate_confirm_bulk_btn" class="button button-primary"><?php esc_html_e( 'Add URLs to Gallery', 'wpcalibrate-external-product-images' ); ?></button>
					<button type="button" id="wpcalibrate_cancel_bulk_btn" class="button"><?php esc_html_e( 'Cancel', 'wpcalibrate-external-product-images' ); ?></button>
				</div>
			</div>

			<!-- Ordered Gallery List -->
			<ul id="wpcalibrate_gallery_list" class="wpcalibrate-gallery-list">
				<?php foreach ( $gallery_items as $index => $item ) : ?>
					<li class="wpcalibrate-gallery-item" data-index="<?php echo esc_attr( (string) $index ); ?>">
						<span class="wpcalibrate-gallery-handle dashicons dashicons-menu" title="<?php esc_attr_e( 'Drag to reorder', 'wpcalibrate-external-product-images' ); ?>"></span>
						<img src="<?php echo esc_url( $item['url'] ); ?>" class="wpcalibrate-gallery-thumb" alt="<?php echo esc_attr( $item['alt'] ); ?>" />
						<div class="wpcalibrate-gallery-fields">
							<input type="hidden" name="_wpcalibrate_epi_gallery[<?php echo esc_attr( (string) $index ); ?>][url]" value="<?php echo esc_url( $item['url'] ); ?>" class="wpcalibrate-gallery-url-input" />
							<input type="text" name="_wpcalibrate_epi_gallery[<?php echo esc_attr( (string) $index ); ?>][alt]" value="<?php echo esc_attr( $item['alt'] ); ?>" placeholder="<?php esc_attr_e( 'Alt text', 'wpcalibrate-external-product-images' ); ?>" class="widefat wpcalibrate-gallery-alt-input" />
							<input type="hidden" name="_wpcalibrate_epi_gallery[<?php echo esc_attr( (string) $index ); ?>][cached_attachment_id]" value="<?php echo esc_attr( (string) ( $item['cached_attachment_id'] ?? 0 ) ); ?>" />
						</div>
						<div class="wpcalibrate-gallery-actions">
							<button type="button" class="wpcalibrate-gallery-btn-move btn-move-up dashicons dashicons-arrow-up-alt2" title="<?php esc_attr_e( 'Move Up', 'wpcalibrate-external-product-images' ); ?>"></button>
							<button type="button" class="wpcalibrate-gallery-btn-move btn-move-down dashicons dashicons-arrow-down-alt2" title="<?php esc_attr_e( 'Move Down', 'wpcalibrate-external-product-images' ); ?>"></button>
							<button type="button" class="button-link-delete dashicons dashicons-trash btn-remove-item" title="<?php esc_attr_e( 'Remove', 'wpcalibrate-external-product-images' ); ?>"></button>
						</div>
					</li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}

	/**
	 * Save external image and gallery data on product save.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 * @return void
	 */
	public function save_product_fields( int $post_id, \WP_Post $post ): void {
		// Verify nonce.
		if ( ! isset( $_POST[ self::NONCE_NAME ] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_NAME ] ) ), self::SAVE_ACTION ) ) {
			return;
		}

		// Prevent autosave overwrites.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		// Check user permission to edit this product.
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Save Featured Image URL.
		if ( isset( $_POST['_wpcalibrate_epi_featured_url'] ) ) {
			$raw_url = sanitize_text_field( wp_unslash( $_POST['_wpcalibrate_epi_featured_url'] ) );
			ProductMetaRepository::set_featured_url( $post_id, $raw_url );
		}

		// Save Featured Alt.
		if ( isset( $_POST['_wpcalibrate_epi_featured_alt'] ) ) {
			$raw_alt = sanitize_text_field( wp_unslash( $_POST['_wpcalibrate_epi_featured_alt'] ) );
			ProductMetaRepository::set_featured_alt( $post_id, $raw_alt );
		}

		// Save Gallery.
		if ( isset( $_POST['_wpcalibrate_epi_gallery'] ) && is_array( $_POST['_wpcalibrate_epi_gallery'] ) ) {
			$raw_gallery = wp_unslash( $_POST['_wpcalibrate_epi_gallery'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$clean_items = [];
			foreach ( $raw_gallery as $item ) {
				if ( ! is_array( $item ) || empty( $item['url'] ) ) {
					continue;
				}
				$clean_items[] = [
					'url'                  => sanitize_text_field( (string) $item['url'] ),
					'alt'                  => isset( $item['alt'] ) ? sanitize_text_field( (string) $item['alt'] ) : '',
					'cached_attachment_id' => isset( $item['cached_attachment_id'] ) ? absint( $item['cached_attachment_id'] ) : 0,
				];
			}
			ProductMetaRepository::set_gallery( $post_id, $clean_items );
		} else {
			// If field is submitted but empty array/omitted, check if explicit clear happened.
			if ( isset( $_POST[ self::NONCE_NAME ] ) && ! isset( $_POST['_wpcalibrate_epi_gallery'] ) ) {
				ProductMetaRepository::set_gallery( $post_id, [] );
			}
		}

		// If Local Cache Mode is enabled and auto-caching is on, trigger background download.
		if ( (string) Settings::get( 'cache_mode', 'direct' ) === 'local' && (bool) Settings::get( 'auto_cache_on_save', false ) ) {
			do_action( 'wpcalibrate_epi_queue_cache_product', $post_id );
		}
	}
}
