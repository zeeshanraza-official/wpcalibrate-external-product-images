<?php
/**
 * Settings Page Controller and View.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Admin;

use WPCalibrate\ExternalProductImages\Settings\Settings;
use WPCalibrate\ExternalProductImages\Settings\Sanitizer;
use WPCalibrate\ExternalProductImages\Licensing\UnconfiguredLicenseProvider;
use WPCalibrate\ExternalProductImages\Licensing\LicenseProviderInterface;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renders and handles plugin administration settings tabs.
 */
class SettingsPage {

	/**
	 * Singleton instance.
	 *
	 * @var self|null
	 */
	private static ?self $instance = null;

	/**
	 * License provider instance.
	 *
	 * @var LicenseProviderInterface
	 */
	private LicenseProviderInterface $license_provider;

	/**
	 * Get instance.
	 *
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor.
	 */
	private function __construct() {
		$this->license_provider = new UnconfiguredLicenseProvider();
	}

	/**
	 * Render the main administration interface.
	 *
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'wpcalibrate-external-product-images' ) );
		}

		// Handle save if POST request.
		$this->handle_save();

		// Determine active tab.
		$valid_tabs = [
			'overview'       => __( 'Overview', 'wpcalibrate-external-product-images' ),
			'general'        => __( 'General', 'wpcalibrate-external-product-images' ),
			'image_handling' => __( 'Image Handling', 'wpcalibrate-external-product-images' ),
			'cache'          => __( 'Cache', 'wpcalibrate-external-product-images' ),
			'import_export'  => __( 'Import & Export', 'wpcalibrate-external-product-images' ),
			'license'        => __( 'License', 'wpcalibrate-external-product-images' ),
			'support'        => __( 'Support', 'wpcalibrate-external-product-images' ),
		];

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current_tab = isset( $_GET['tab'] ) && array_key_exists( sanitize_key( $_GET['tab'] ), $valid_tabs )
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			? sanitize_key( $_GET['tab'] )
			: 'overview';

		$settings = Settings::get_all( true );
		$is_active = (bool) ( $settings['enabled'] ?? true );
		?>
		<div class="wrap wpcalibrate-admin-wrap">
			<!-- Header Area -->
			<div class="wpcalibrate-header-card">
				<div class="wpcalibrate-header-brand">
					<?php $this->render_brand_logo(); ?>
					<div>
						<h1 class="wpcalibrate-header-title"><?php esc_html_e( 'External Product Images', 'wpcalibrate-external-product-images' ); ?></h1>
						<p class="wpcalibrate-header-subtitle"><?php esc_html_e( 'Deliver remote WooCommerce product images, galleries, and variation assets with lightning speed and zero media library bloat.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
				</div>
				<div class="wpcalibrate-header-status">
					<span class="wpcalibrate-status-badge <?php echo $is_active ? 'is-active' : 'is-inactive'; ?>">
						<span class="wpcalibrate-status-dot"></span>
						<?php echo $is_active ? esc_html__( 'Service Active', 'wpcalibrate-external-product-images' ) : esc_html__( 'Service Paused', 'wpcalibrate-external-product-images' ); ?>
					</span>
					<span class="wpcalibrate-version-badge">v<?php echo esc_html( WPCALIBRATE_EPI_VERSION ); ?></span>
				</div>
			</div>

			<!-- Navigation Tabs -->
			<nav class="wpcalibrate-nav-tabs" aria-label="<?php esc_attr_e( 'Settings Navigation', 'wpcalibrate-external-product-images' ); ?>">
				<?php foreach ( $valid_tabs as $tab_key => $tab_label ) : ?>
					<?php
					$tab_url = add_query_arg(
						[
							'page' => Menu::PAGE_SLUG,
							'tab'  => $tab_key,
						],
						admin_url( 'admin.php' )
					);
					$active_class = ( $current_tab === $tab_key ) ? 'is-active' : '';
					?>
					<a href="<?php echo esc_url( $tab_url ); ?>" class="wpcalibrate-nav-tab <?php echo esc_attr( $active_class ); ?>">
						<?php echo $this->get_tab_icon( $tab_key ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<span><?php echo esc_html( $tab_label ); ?></span>
					</a>
				<?php endforeach; ?>
			</nav>

			<!-- Tab Content Body -->
			<div class="wpcalibrate-tab-content">
				<?php
				switch ( $current_tab ) {
					case 'general':
						$this->render_tab_general( $settings );
						break;
					case 'image_handling':
						$this->render_tab_image_handling( $settings );
						break;
					case 'cache':
						$this->render_tab_cache( $settings );
						break;
					case 'import_export':
						$this->render_tab_import_export( $settings );
						break;
					case 'license':
						$this->render_tab_license();
						break;
					case 'support':
						$this->render_tab_support( $settings );
						break;
					case 'overview':
					default:
						$this->render_tab_overview( $settings );
						break;
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Overview tab: Purpose, Method, 3-Step Quick Start, and Diagnostics.
	 *
	 * @param array<string, mixed> $settings Active settings.
	 * @return void
	 */
	private function render_tab_overview( array $settings ): void {
		$is_enabled = (bool) ( $settings['enabled'] ?? true );
		$cache_mode = (string) ( $settings['cache_mode'] ?? 'direct' );
		?>
		<!-- Hero Banner -->
		<div class="wpcalibrate-hero-banner">
			<div class="wpcalibrate-hero-content">
				<span class="wpcalibrate-pill-tag">⚡ <?php esc_html_e( 'High-Performance WooCommerce Extension', 'wpcalibrate-external-product-images' ); ?></span>
				<h2 class="wpcalibrate-hero-title"><?php esc_html_e( 'Supercharge WooCommerce with External Images & Zero Disk Bloat', 'wpcalibrate-external-product-images' ); ?></h2>
				<p class="wpcalibrate-hero-desc">
					<?php esc_html_e( 'Serve crisp, high-resolution product photography, multi-angle galleries, and variation images hosted on high-speed CDNs, AWS S3, Cloudflare R2, or remote suppliers directly to customer browsers without filling up your WordPress server storage.', 'wpcalibrate-external-product-images' ); ?>
				</p>
				<div class="wpcalibrate-hero-badges">
					<span class="wpcalibrate-badge-item">✓ <?php esc_html_e( 'Zero Media Library Bloat', 'wpcalibrate-external-product-images' ); ?></span>
					<span class="wpcalibrate-badge-item">✓ <?php esc_html_e( 'Direct Remote CDN Delivery', 'wpcalibrate-external-product-images' ); ?></span>
					<span class="wpcalibrate-badge-item">✓ <?php esc_html_e( 'SSRF Hardened', 'wpcalibrate-external-product-images' ); ?></span>
					<span class="wpcalibrate-badge-item">✓ <?php esc_html_e( 'HPOS & Blocks Ready', 'wpcalibrate-external-product-images' ); ?></span>
				</div>
			</div>
		</div>

		<!-- Live System Health Metrics Grid -->
		<div class="wpcalibrate-metrics-grid">
			<div class="wpcalibrate-metric-card">
				<div class="wpcalibrate-metric-icon is-green">
					<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
				</div>
				<div class="wpcalibrate-metric-body">
					<span class="wpcalibrate-metric-label"><?php esc_html_e( 'Engine Status', 'wpcalibrate-external-product-images' ); ?></span>
					<h3 class="wpcalibrate-metric-value"><?php echo $is_enabled ? esc_html__( 'Active', 'wpcalibrate-external-product-images' ) : esc_html__( 'Paused', 'wpcalibrate-external-product-images' ); ?></h3>
					<p class="wpcalibrate-metric-sub"><?php esc_html_e( 'Injecting image filters into WooCommerce', 'wpcalibrate-external-product-images' ); ?></p>
				</div>
			</div>

			<div class="wpcalibrate-metric-card">
				<div class="wpcalibrate-metric-icon is-blue">
					<svg viewBox="0 0 20 20" fill="currentColor"><path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z"/><path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z"/><path d="M17 4c0 1.657-3.134 3-7 3S3 5.657 3 4s3.134-3 7-3 7 1.343 7 3z"/></svg>
				</div>
				<div class="wpcalibrate-metric-body">
					<span class="wpcalibrate-metric-label"><?php esc_html_e( 'Active Delivery Mode', 'wpcalibrate-external-product-images' ); ?></span>
					<h3 class="wpcalibrate-metric-value"><?php echo ( 'local' === $cache_mode ) ? esc_html__( 'Local Cache', 'wpcalibrate-external-product-images' ) : esc_html__( 'Direct Remote', 'wpcalibrate-external-product-images' ); ?></h3>
					<p class="wpcalibrate-metric-sub"><?php echo ( 'local' === $cache_mode ) ? esc_html__( 'Images cached in Media Library', 'wpcalibrate-external-product-images' ) : esc_html__( 'Zero disk storage consumed', 'wpcalibrate-external-product-images' ); ?></p>
				</div>
			</div>

			<div class="wpcalibrate-metric-card">
				<div class="wpcalibrate-metric-icon is-purple">
					<svg viewBox="0 0 20 20" fill="currentColor"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"/></svg>
				</div>
				<div class="wpcalibrate-metric-body">
					<span class="wpcalibrate-metric-label"><?php esc_html_e( 'WooCommerce Integration', 'wpcalibrate-external-product-images' ); ?></span>
					<h3 class="wpcalibrate-metric-value"><?php esc_html_e( 'HPOS & Blocks', 'wpcalibrate-external-product-images' ); ?></h3>
					<p class="wpcalibrate-metric-sub"><?php esc_html_e( 'High-Performance Order Storage compatible', 'wpcalibrate-external-product-images' ); ?></p>
				</div>
			</div>

			<div class="wpcalibrate-metric-card">
				<div class="wpcalibrate-metric-icon is-emerald">
					<svg viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M12.395 2.553a1 1 0 00-1.45-.385c-.345.23-.614.558-.822.88-.214.33-.403.713-.57 1.116-.334.804-.614 1.768-.84 2.734a31.365 31.365 0 00-.613 3.58 2.64 2.64 0 01-.945-1.067c-.328-.68-.398-1.534-.398-2.654A1 1 0 005.05 6.05 6.981 6.981 0 003 11a7 7 0 1011.95-4.95c-.592-.591-.98-.985-1.348-1.467-.363-.476-.724-1.063-1.207-2.03zM12.12 15.12A3 3 0 017 13s.879.5 2.5.5c0-1 .5-4 1.25-4.5.5 1 .786 1.293 1.371 1.879A2.98 2.98 0 0113 13a2.99 2.99 0 01-.879 2.12z" clip-rule="evenodd"/></svg>
				</div>
				<div class="wpcalibrate-metric-body">
					<span class="wpcalibrate-metric-label"><?php esc_html_e( 'Environment Verified', 'wpcalibrate-external-product-images' ); ?></span>
					<h3 class="wpcalibrate-metric-value">PHP <?php echo esc_html( PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION ); ?></h3>
					<p class="wpcalibrate-metric-sub"><?php esc_html_e( 'WordPress 7.1+ & WooCommerce 11.2+', 'wpcalibrate-external-product-images' ); ?></p>
				</div>
			</div>
		</div>

		<!-- How To Use (3-Step Quick Start Guide) -->
		<div class="wpcalibrate-card">
			<div class="wpcalibrate-card-header-clean">
				<div>
					<span class="wpcalibrate-section-tag"><?php esc_html_e( 'Quick Start Guide', 'wpcalibrate-external-product-images' ); ?></span>
					<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'How to Use This Plugin (3 Simple Steps)', 'wpcalibrate-external-product-images' ); ?></h2>
				</div>
			</div>

			<div class="wpcalibrate-steps-grid">
				<div class="wpcalibrate-step-card">
					<div class="wpcalibrate-step-number">1</div>
					<h3 class="wpcalibrate-step-title"><?php esc_html_e( 'Configure Core Rules', 'wpcalibrate-external-product-images' ); ?></h3>
					<p class="wpcalibrate-step-desc">
						<?php esc_html_e( 'Choose your image delivery strategy (Direct Remote vs Local Cache Mode) and ensure HTTPS enforcement is enabled to prevent browser mixed-content warnings.', 'wpcalibrate-external-product-images' ); ?>
					</p>
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => Menu::PAGE_SLUG, 'tab' => 'general' ], admin_url( 'admin.php' ) ) ); ?>" class="wpcalibrate-btn-link">
						<?php esc_html_e( 'Configure Settings →', 'wpcalibrate-external-product-images' ); ?>
					</a>
				</div>

				<div class="wpcalibrate-step-card">
					<div class="wpcalibrate-step-number">2</div>
					<h3 class="wpcalibrate-step-title"><?php esc_html_e( 'Assign External Images', 'wpcalibrate-external-product-images' ); ?></h3>
					<p class="wpcalibrate-step-desc">
						<?php esc_html_e( 'Edit any WooCommerce product. In the sidebar metabox, paste your external featured image URL. Add multi-image external galleries with drag-and-drop, or set variation images.', 'wpcalibrate-external-product-images' ); ?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=product' ) ); ?>" class="wpcalibrate-btn-link">
						<?php esc_html_e( 'Manage Products →', 'wpcalibrate-external-product-images' ); ?>
					</a>
				</div>

				<div class="wpcalibrate-step-card">
					<div class="wpcalibrate-step-number">3</div>
					<h3 class="wpcalibrate-step-title"><?php esc_html_e( 'Native CSV Import & Export', 'wpcalibrate-external-product-images' ); ?></h3>
					<p class="wpcalibrate-step-desc">
						<?php esc_html_e( 'Bulk update thousands of products effortlessly using WooCommerce native Products > Import / Export with our 5 dedicated external image CSV columns.', 'wpcalibrate-external-product-images' ); ?>
					</p>
					<a href="<?php echo esc_url( add_query_arg( [ 'page' => Menu::PAGE_SLUG, 'tab' => 'import_export' ], admin_url( 'admin.php' ) ) ); ?>" class="wpcalibrate-btn-link">
						<?php esc_html_e( 'View CSV Documentation →', 'wpcalibrate-external-product-images' ); ?>
					</a>
				</div>
			</div>
		</div>

		<!-- Technical Architecture & Method -->
		<div class="wpcalibrate-card">
			<div class="wpcalibrate-card-header-clean">
				<div>
					<span class="wpcalibrate-section-tag"><?php esc_html_e( 'Architecture & Lifecycle', 'wpcalibrate-external-product-images' ); ?></span>
					<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Technical Method (How It Works Under the Hood)', 'wpcalibrate-external-product-images' ); ?></h2>
				</div>
			</div>

			<div class="wpcalibrate-architecture-grid">
				<div class="wpcalibrate-arch-card">
					<div class="wpcalibrate-arch-badge">01</div>
					<h4><?php esc_html_e( 'Non-Invasive Pipeline', 'wpcalibrate-external-product-images' ); ?></h4>
					<p><?php esc_html_e( 'We never modify WooCommerce core files or template PHP. All output is seamlessly intercepted via standard hooks:', 'wpcalibrate-external-product-images' ); ?></p>
					<ul class="wpcalibrate-code-list">
						<li><code>woocommerce_product_get_image</code></li>
						<li><code>woocommerce_single_product_image_thumbnail_html</code></li>
						<li><code>woocommerce_available_variation</code></li>
						<li><code>woocommerce_cart_item_thumbnail</code></li>
					</ul>
				</div>

				<div class="wpcalibrate-arch-card">
					<div class="wpcalibrate-arch-badge">02</div>
					<h4><?php esc_html_e( 'Centralized Precedence Engine', 'wpcalibrate-external-product-images' ); ?></h4>
					<p><?php esc_html_e( 'Image resolution is managed by a single Image Resolver service with strict hierarchy:', 'wpcalibrate-external-product-images' ); ?></p>
					<ol class="wpcalibrate-ordered-list">
						<li><strong><?php esc_html_e( 'Cached Local Media', 'wpcalibrate-external-product-images' ); ?></strong> (<?php esc_html_e( 'if Local Cache Mode is active', 'wpcalibrate-external-product-images' ); ?>)</li>
						<li><strong><?php esc_html_e( 'Direct Remote URL', 'wpcalibrate-external-product-images' ); ?></strong> (<?php esc_html_e( 'fast CDN delivery', 'wpcalibrate-external-product-images' ); ?>)</li>
						<li><strong><?php esc_html_e( 'Native Local Media', 'wpcalibrate-external-product-images' ); ?></strong> (<?php esc_html_e( 'fallback if assigned', 'wpcalibrate-external-product-images' ); ?>)</li>
						<li><strong><?php esc_html_e( 'Store Placeholder', 'wpcalibrate-external-product-images' ); ?></strong> (<?php esc_html_e( 'custom image or WooCommerce placeholder', 'wpcalibrate-external-product-images' ); ?>)</li>
					</ol>
				</div>

				<div class="wpcalibrate-arch-card">
					<div class="wpcalibrate-arch-badge">03</div>
					<h4><?php esc_html_e( 'Zero-Blocking Fallback & Anti-SSRF', 'wpcalibrate-external-product-images' ); ?></h4>
					<p><?php esc_html_e( 'Storefront rendering stays fast even if a remote host goes down. Our client-side observer swaps broken remote images to placeholders without server-side HTTP stalls, and background caching rejects unsafe internal and loopback IP addresses.', 'wpcalibrate-external-product-images' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Get inline SVG icon for settings tabs.
	 *
	 * @param string $tab_key Tab identifier.
	 * @return string Inline SVG markup.
	 */
	private function get_tab_icon( string $tab_key ): string {
		switch ( $tab_key ) {
			case 'overview':
				return '<svg class="wpcalibrate-tab-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M10.707 2.293a1 1 0 00-1.414 0l-7 7a1 1 0 001.414 1.414L4 10.414V17a1 1 0 001 1h2a1 1 0 001-1v-2a1 1 0 011-1h2a1 1 0 011 1v2a1 1 0 001 1h2a1 1 0 001-1v-6.586l.293.293a1 1 0 001.414-1.414l-7-7z"/></svg>';
			case 'general':
				return '<svg class="wpcalibrate-tab-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>';
			case 'image_handling':
				return '<svg class="wpcalibrate-tab-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z" clip-rule="evenodd"/></svg>';
			case 'cache':
				return '<svg class="wpcalibrate-tab-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M3 12v3c0 1.657 3.134 3 7 3s7-1.343 7-3v-3c0 1.657-3.134 3-7 3s-7-1.343-7-3z"/><path d="M3 7v3c0 1.657 3.134 3 7 3s7-1.343 7-3V7c0 1.657-3.134 3-7 3S3 8.657 3 7z"/><path d="M17 4c0 1.657-3.134 3-7 3S3 5.657 3 4s3.134-3 7-3 7 1.343 7 3z"/></svg>';
			case 'import_export':
				return '<svg class="wpcalibrate-tab-icon" viewBox="0 0 20 20" fill="currentColor"><path d="M8 5a1 1 0 100 2h5.586l-1.293 1.293a1 1 0 001.414 1.414l3-3a1 1 0 000-1.414l-3-3a1 1 0 10-1.414 1.414L13.586 5H8zM12 15a1 1 0 100-2H6.414l1.293-1.293a1 1 0 10-1.414-1.414l-3 3a1 1 0 000 1.414l3 3a1 1 0 001.414-1.414L6.414 15H12z"/></svg>';
			case 'license':
				return '<svg class="wpcalibrate-tab-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>';
			case 'support':
				return '<svg class="wpcalibrate-tab-icon" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-2 0c0 .993-.241 1.929-.668 2.754l-1.524-1.525a3.997 3.997 0 00.192-1.229c0-.46-.078-.899-.221-1.309l1.636-1.09A7.96 7.96 0 0116 10zM7 10c0 .46.078.899.221 1.309l-1.636 1.09A7.96 7.96 0 016 10c0-.993.241-1.929.668-2.754l1.524 1.525A3.997 3.997 0 008 10zm1.754-3.524A3.997 3.997 0 0110 6c.46 0 .899.078 1.309.221l1.09-1.636A7.96 7.96 0 0010 4c-.993 0-1.929.241-2.754.668l1.508 1.808zm2.492 7.048A3.997 3.997 0 0110 14c-.46 0-.899-.078-1.309-.221l-1.09 1.636A7.96 7.96 0 0010 16c.993 0 1.929-.241 2.754-.668l-1.508-1.808z" clip-rule="evenodd"/></svg>';
			default:
				return '';
		}
	}

	/**
	 * Handle POST submission with strict capability and nonce checks.
	 *
	 * @return void
	 */
	private function handle_save(): void {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		if ( ! isset( $_POST['wpcalibrate_epi_save_settings_nonce'] ) ) {
			return;
		}

		check_admin_referer( 'wpcalibrate_epi_save_settings_action', 'wpcalibrate_epi_save_settings_nonce' );

		if ( ! current_user_can( 'manage_woocommerce' ) && ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Unauthorized action.', 'wpcalibrate-external-product-images' ) );
		}

		// Read current settings and merge submitted updates for current tab.
		$current = Settings::get_all( true );
		$posted  = isset( $_POST['wpcalibrate_epi_settings'] ) && is_array( $_POST['wpcalibrate_epi_settings'] )
			? wp_unslash( $_POST['wpcalibrate_epi_settings'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			: [];

		// For checkboxes present in this tab form, set them to true if posted, false if not posted.
		if ( isset( $_POST['wpcalibrate_form_fields'] ) && is_string( $_POST['wpcalibrate_form_fields'] ) ) {
			$fields = explode( ',', sanitize_text_field( wp_unslash( $_POST['wpcalibrate_form_fields'] ) ) );
			foreach ( $fields as $field ) {
				$field = trim( $field );
				if ( ! empty( $field ) ) {
					if ( isset( $posted[ $field ] ) ) {
						$current[ $field ] = $posted[ $field ];
					} else {
						// Unchecked checkbox or omitted field.
						$current[ $field ] = false;
					}
				}
			}
		}

		// Also merge any other explicit posted fields.
		foreach ( $posted as $k => $v ) {
			$current[ $k ] = $v;
		}

		Settings::update_all( $current );

		add_settings_error(
			'wpcalibrate_epi_messages',
			'wpcalibrate_epi_settings_updated',
			__( 'Settings successfully saved.', 'wpcalibrate-external-product-images' ),
			'success'
		);

		settings_errors( 'wpcalibrate_epi_messages' );
	}

	/**
	 * Render General tab.
	 *
	 * @param array<string, mixed> $settings Active settings.
	 * @return void
	 */
	private function render_tab_general( array $settings ): void {
		$tab_url = add_query_arg( [ 'page' => Menu::PAGE_SLUG, 'tab' => 'general' ], admin_url( 'admin.php' ) );
		?>
		<form method="post" action="<?php echo esc_url( $tab_url ); ?>" class="wpcalibrate-form">
			<?php wp_nonce_field( 'wpcalibrate_epi_save_settings_action', 'wpcalibrate_epi_save_settings_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_form_fields" value="enabled,https_only,default_fallback,custom_fallback_id,delete_data_on_uninstall" />

			<div class="wpcalibrate-card">
				<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Core Engine Status', 'wpcalibrate-external-product-images' ); ?></h2>
				<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Control the master activation state and network protocol rules for external product images.', 'wpcalibrate-external-product-images' ); ?></p>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_enabled" class="wpcalibrate-label"><?php esc_html_e( 'Enable External Image Engine', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'When disabled, all external image filters and frontend overrides are suspended. Existing local WooCommerce product images display normally.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<label class="wpcalibrate-switch">
							<input type="checkbox" id="field_enabled" name="wpcalibrate_epi_settings[enabled]" value="1" <?php checked( (bool) ( $settings['enabled'] ?? true ) ); ?> />
							<span class="wpcalibrate-slider"></span>
						</label>
					</div>
				</div>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_https_only" class="wpcalibrate-label"><?php esc_html_e( 'Enforce HTTPS Only', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Recommended. Rejects non-secure http:// URLs to prevent mixed-content browser warnings and insecure network delivery.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<label class="wpcalibrate-switch">
							<input type="checkbox" id="field_https_only" name="wpcalibrate_epi_settings[https_only]" value="1" <?php checked( (bool) ( $settings['https_only'] ?? true ) ); ?> />
							<span class="wpcalibrate-slider"></span>
						</label>
					</div>
				</div>
			</div>

			<div class="wpcalibrate-card">
				<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Fallback Precedence & Placeholder', 'wpcalibrate-external-product-images' ); ?></h2>
				<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Configure fallback imagery when an external image cannot be rendered or is missing.', 'wpcalibrate-external-product-images' ); ?></p>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_default_fallback" class="wpcalibrate-label"><?php esc_html_e( 'Default Fallback Mode', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Choose what to show when neither an external image nor a local WooCommerce image exists.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<select id="field_default_fallback" name="wpcalibrate_epi_settings[default_fallback]" class="wpcalibrate-select">
							<option value="woocommerce_placeholder" <?php selected( $settings['default_fallback'], 'woocommerce_placeholder' ); ?>><?php esc_html_e( 'Standard WooCommerce Placeholder (Default)', 'wpcalibrate-external-product-images' ); ?></option>
							<option value="custom_attachment" <?php selected( $settings['default_fallback'], 'custom_attachment' ); ?>><?php esc_html_e( 'Custom Media Library Image', 'wpcalibrate-external-product-images' ); ?></option>
							<option value="none" <?php selected( $settings['default_fallback'], 'none' ); ?>><?php esc_html_e( 'No Fallback (Render Nothing)', 'wpcalibrate-external-product-images' ); ?></option>
						</select>
					</div>
				</div>

				<div class="wpcalibrate-field-row" id="row_custom_fallback" style="<?php echo ( $settings['default_fallback'] === 'custom_attachment' ) ? '' : 'display:none;'; ?>">
					<div class="wpcalibrate-field-info">
						<label for="field_custom_fallback_id" class="wpcalibrate-label"><?php esc_html_e( 'Custom Fallback Image', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Select an image from the WordPress Media Library to serve as your store-wide fallback.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<?php
						$custom_id = absint( $settings['custom_fallback_id'] ?? 0 );
						$preview_url = $custom_id ? wp_get_attachment_image_url( $custom_id, 'thumbnail' ) : '';
						?>
						<div class="wpcalibrate-media-picker">
							<div class="wpcalibrate-media-preview" id="custom_fallback_preview" style="<?php echo $preview_url ? '' : 'display:none;'; ?>">
								<img src="<?php echo esc_url( $preview_url ?: '' ); ?>" alt="" id="custom_fallback_img" />
							</div>
							<input type="hidden" name="wpcalibrate_epi_settings[custom_fallback_id]" id="field_custom_fallback_id" value="<?php echo esc_attr( (string) $custom_id ); ?>" />
							<button type="button" class="button" id="wpcalibrate_select_fallback_btn"><?php esc_html_e( 'Select from Media Library', 'wpcalibrate-external-product-images' ); ?></button>
							<button type="button" class="button button-link-delete" id="wpcalibrate_remove_fallback_btn" style="<?php echo $custom_id ? '' : 'display:none;'; ?>"><?php esc_html_e( 'Remove', 'wpcalibrate-external-product-images' ); ?></button>
						</div>
					</div>
				</div>
			</div>

			<div class="wpcalibrate-card">
				<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Data Management & Uninstall Policy', 'wpcalibrate-external-product-images' ); ?></h2>
				<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Choose what happens if this plugin is uninstalled from the WordPress Plugins screen.', 'wpcalibrate-external-product-images' ); ?></p>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_delete_data_on_uninstall" class="wpcalibrate-label"><?php esc_html_e( 'Delete Plugin Data on Uninstall', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Default: disabled. When enabled, removing this plugin will purge plugin settings and external image product metadata. Local images in your Media Library will never be deleted.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<label class="wpcalibrate-switch">
							<input type="checkbox" id="field_delete_data_on_uninstall" name="wpcalibrate_epi_settings[delete_data_on_uninstall]" value="1" <?php checked( (bool) ( $settings['delete_data_on_uninstall'] ?? false ) ); ?> />
							<span class="wpcalibrate-slider"></span>
						</label>
					</div>
				</div>
			</div>

			<div class="wpcalibrate-form-actions">
				<button type="submit" class="button button-primary wpcalibrate-btn-primary"><?php esc_html_e( 'Save Changes', 'wpcalibrate-external-product-images' ); ?></button>
			</div>
		</form>
		<?php
	}

	/**
	 * Render Image Handling tab.
	 *
	 * @param array<string, mixed> $settings Active settings.
	 * @return void
	 */
	private function render_tab_image_handling( array $settings ): void {
		$tab_url = add_query_arg( [ 'page' => Menu::PAGE_SLUG, 'tab' => 'image_handling' ], admin_url( 'admin.php' ) );
		?>
		<form method="post" action="<?php echo esc_url( $tab_url ); ?>" class="wpcalibrate-form">
			<?php wp_nonce_field( 'wpcalibrate_epi_save_settings_action', 'wpcalibrate_epi_save_settings_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_form_fields" value="enable_featured,enable_gallery,enable_variations,client_fallback,default_alt_mode" />

			<div class="wpcalibrate-card">
				<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Product Image Scope', 'wpcalibrate-external-product-images' ); ?></h2>
				<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Enable or disable external image features across specific product components.', 'wpcalibrate-external-product-images' ); ?></p>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_enable_featured" class="wpcalibrate-label"><?php esc_html_e( 'External Main / Featured Images', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Allows products to use external URLs for catalog archives, search, widgets, cart thumbnails, and single product main displays.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<label class="wpcalibrate-switch">
							<input type="checkbox" id="field_enable_featured" name="wpcalibrate_epi_settings[enable_featured]" value="1" <?php checked( (bool) ( $settings['enable_featured'] ?? true ) ); ?> />
							<span class="wpcalibrate-slider"></span>
						</label>
					</div>
				</div>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_enable_gallery" class="wpcalibrate-label"><?php esc_html_e( 'External Product Galleries', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Allows products to configure multiple ordered external URLs with zoom, lightbox, and thumbnail switching.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<label class="wpcalibrate-switch">
							<input type="checkbox" id="field_enable_gallery" name="wpcalibrate_epi_settings[enable_gallery]" value="1" <?php checked( (bool) ( $settings['enable_gallery'] ?? true ) ); ?> />
							<span class="wpcalibrate-slider"></span>
						</label>
					</div>
				</div>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_enable_variations" class="wpcalibrate-label"><?php esc_html_e( 'External Variation Images', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Allows each product variation to define an external image URL that swaps responsively upon customer variation selection.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<label class="wpcalibrate-switch">
							<input type="checkbox" id="field_enable_variations" name="wpcalibrate_epi_settings[enable_variations]" value="1" <?php checked( (bool) ( $settings['enable_variations'] ?? true ) ); ?> />
							<span class="wpcalibrate-slider"></span>
						</label>
					</div>
				</div>
			</div>

			<div class="wpcalibrate-card">
				<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Resilience & Accessibility', 'wpcalibrate-external-product-images' ); ?></h2>
				<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Manage browser fallback behavior and automated alt text rules.', 'wpcalibrate-external-product-images' ); ?></p>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_client_fallback" class="wpcalibrate-label"><?php esc_html_e( 'Browser-Side Broken Image Fallback', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Gracefully replaces remote images that fail in the customer browser (404, DNS failure, offline host) with the configured fallback without blocking server rendering.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<label class="wpcalibrate-switch">
							<input type="checkbox" id="field_client_fallback" name="wpcalibrate_epi_settings[client_fallback]" value="1" <?php checked( (bool) ( $settings['client_fallback'] ?? true ) ); ?> />
							<span class="wpcalibrate-slider"></span>
						</label>
					</div>
				</div>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_default_alt_mode" class="wpcalibrate-label"><?php esc_html_e( 'Default Alt Text Strategy', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Applied when no custom alt text override is provided for an external image.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<select id="field_default_alt_mode" name="wpcalibrate_epi_settings[default_alt_mode]" class="wpcalibrate-select">
							<option value="product_title" <?php selected( $settings['default_alt_mode'], 'product_title' ); ?>><?php esc_html_e( 'Product Title (Recommended)', 'wpcalibrate-external-product-images' ); ?></option>
							<option value="filename" <?php selected( $settings['default_alt_mode'], 'filename' ); ?>><?php esc_html_e( 'Sanitized URL Filename', 'wpcalibrate-external-product-images' ); ?></option>
							<option value="empty" <?php selected( $settings['default_alt_mode'], 'empty' ); ?>><?php esc_html_e( 'Empty Alt Text', 'wpcalibrate-external-product-images' ); ?></option>
						</select>
					</div>
				</div>
			</div>

			<div class="wpcalibrate-form-actions">
				<button type="submit" class="button button-primary wpcalibrate-btn-primary"><?php esc_html_e( 'Save Changes', 'wpcalibrate-external-product-images' ); ?></button>
			</div>
		</form>
		<?php
	}

	/**
	 * Render Cache tab.
	 *
	 * @param array<string, mixed> $settings Active settings.
	 * @return void
	 */
	private function render_tab_cache( array $settings ): void {
		$tab_url = add_query_arg( [ 'page' => Menu::PAGE_SLUG, 'tab' => 'cache' ], admin_url( 'admin.php' ) );
		?>
		<form method="post" action="<?php echo esc_url( $tab_url ); ?>" class="wpcalibrate-form">
			<?php wp_nonce_field( 'wpcalibrate_epi_save_settings_action', 'wpcalibrate_epi_save_settings_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_form_fields" value="cache_mode,auto_cache_on_save,delete_local_on_url_change,max_download_size_mb,health_check_lifetime_hours" />

			<div class="wpcalibrate-card">
				<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Image Delivery Mode', 'wpcalibrate-external-product-images' ); ?></h2>
				<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Switch between Direct Remote delivery (default) and optional Local Cache mode.', 'wpcalibrate-external-product-images' ); ?></p>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_cache_mode" class="wpcalibrate-label"><?php esc_html_e( 'Delivery Mode', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Direct Remote Mode renders URLs directly without storing files locally. Local Cache Mode allows importing external files into the Media Library.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<select id="field_cache_mode" name="wpcalibrate_epi_settings[cache_mode]" class="wpcalibrate-select">
							<option value="direct" <?php selected( $settings['cache_mode'], 'direct' ); ?>><?php esc_html_e( 'Direct Remote Mode (Default, Zero Media Bloat)', 'wpcalibrate-external-product-images' ); ?></option>
							<option value="local" <?php selected( $settings['cache_mode'], 'local' ); ?>><?php esc_html_e( 'Local Cache Mode (Download into Media Library)', 'wpcalibrate-external-product-images' ); ?></option>
						</select>
					</div>
				</div>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_auto_cache_on_save" class="wpcalibrate-label"><?php esc_html_e( 'Automatically Cache on Product Save', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Queues an asynchronous Action Scheduler background job to download external images when a product is saved or updated. Active only when Local Cache Mode is enabled.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<label class="wpcalibrate-switch">
							<input type="checkbox" id="field_auto_cache_on_save" name="wpcalibrate_epi_settings[auto_cache_on_save]" value="1" <?php checked( (bool) ( $settings['auto_cache_on_save'] ?? false ) ); ?> />
							<span class="wpcalibrate-slider"></span>
						</label>
					</div>
				</div>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_delete_local_on_url_change" class="wpcalibrate-label"><?php esc_html_e( 'Purge Old Local Copy on URL Change', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'When an administrator changes an external URL, safely delete the previously cached local attachment to prevent orphaned media files.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<label class="wpcalibrate-switch">
							<input type="checkbox" id="field_delete_local_on_url_change" name="wpcalibrate_epi_settings[delete_local_on_url_change]" value="1" <?php checked( (bool) ( $settings['delete_local_on_url_change'] ?? false ) ); ?> />
							<span class="wpcalibrate-slider"></span>
						</label>
					</div>
				</div>
			</div>

			<div class="wpcalibrate-card">
				<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Safety & Download Constraints', 'wpcalibrate-external-product-images' ); ?></h2>
				<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Server-side protections to prevent denial-of-service and SSRF abuse during image caching.', 'wpcalibrate-external-product-images' ); ?></p>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_max_download_size_mb" class="wpcalibrate-label"><?php esc_html_e( 'Maximum Download File Size (MB)', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Rejects remote downloads exceeding this size limit (1 to 100 MB). Prevents memory exhaustion.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<input type="number" id="field_max_download_size_mb" name="wpcalibrate_epi_settings[max_download_size_mb]" value="<?php echo esc_attr( (string) $settings['max_download_size_mb'] ); ?>" min="1" max="100" class="small-text" />
					</div>
				</div>

				<div class="wpcalibrate-field-row">
					<div class="wpcalibrate-field-info">
						<label for="field_health_check_lifetime_hours" class="wpcalibrate-label"><?php esc_html_e( 'Health Check Cache TTL (Hours)', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Duration to remember remote host availability responses to avoid redundant diagnostic checks.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<input type="number" id="field_health_check_lifetime_hours" name="wpcalibrate_epi_settings[health_check_lifetime_hours]" value="<?php echo esc_attr( (string) $settings['health_check_lifetime_hours'] ); ?>" min="1" max="168" class="small-text" />
					</div>
				</div>
			</div>

			<div class="wpcalibrate-form-actions">
				<button type="submit" class="button button-primary wpcalibrate-btn-primary"><?php esc_html_e( 'Save Changes', 'wpcalibrate-external-product-images' ); ?></button>
			</div>
		</form>
		<?php
	}

	/**
	 * Render Import & Export tab.
	 *
	 * @param array<string, mixed> $settings Active settings.
	 * @return void
	 */
	private function render_tab_import_export( array $settings ): void {
		$tab_url = add_query_arg( [ 'page' => Menu::PAGE_SLUG, 'tab' => 'import_export' ], admin_url( 'admin.php' ) );
		?>
		<form method="post" action="<?php echo esc_url( $tab_url ); ?>" class="wpcalibrate-form">
			<?php wp_nonce_field( 'wpcalibrate_epi_save_settings_action', 'wpcalibrate_epi_save_settings_nonce' ); ?>
			<input type="hidden" name="wpcalibrate_form_fields" value="csv_delimiter" />

			<div class="wpcalibrate-card">
				<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'WooCommerce Native CSV Integration', 'wpcalibrate-external-product-images' ); ?></h2>
				<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Seamlessly import and export external images using the standard WooCommerce Products > Import / Export tools.', 'wpcalibrate-external-product-images' ); ?></p>

				<table class="wpcalibrate-table">
					<thead>
						<tr>
							<th><?php esc_html_e( 'CSV Column Name', 'wpcalibrate-external-product-images' ); ?></th>
							<th><?php esc_html_e( 'Description & Format', 'wpcalibrate-external-product-images' ); ?></th>
							<th><?php esc_html_e( 'Example', 'wpcalibrate-external-product-images' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<tr>
							<td><code>External Featured Image</code></td>
							<td><?php esc_html_e( 'HTTPS URL for the product or variation main featured image.', 'wpcalibrate-external-product-images' ); ?></td>
							<td><code>https://cdn.example.com/products/p101.jpg</code></td>
						</tr>
						<tr>
							<td><code>External Image Alt</code></td>
							<td><?php esc_html_e( 'Optional alt text override for the external featured image.', 'wpcalibrate-external-product-images' ); ?></td>
							<td><code>Premium Leather Jacket in Cognac</code></td>
						</tr>
						<tr>
							<td><code>External Gallery Images</code></td>
							<td><?php esc_html_e( 'Multiple external URLs separated by the configured gallery delimiter.', 'wpcalibrate-external-product-images' ); ?></td>
							<td><code>https://cdn.example.com/p1-1.jpg, https://cdn.example.com/p1-2.jpg</code></td>
						</tr>
						<tr>
							<td><code>External Variation Image</code></td>
							<td><?php esc_html_e( 'HTTPS URL for specific variable product variation rows.', 'wpcalibrate-external-product-images' ); ?></td>
							<td><code>https://cdn.example.com/variants/blue.jpg</code></td>
						</tr>
						<tr>
							<td><code>External Variation Image Alt</code></td>
							<td><?php esc_html_e( 'Optional alt text override for the variation image.', 'wpcalibrate-external-product-images' ); ?></td>
							<td><code>Blue Variant - Studio View</code></td>
						</tr>
					</tbody>
				</table>

				<div class="wpcalibrate-field-row" style="margin-top: 20px;">
					<div class="wpcalibrate-field-info">
						<label for="field_csv_delimiter" class="wpcalibrate-label"><?php esc_html_e( 'Gallery URL Delimiter in CSV', 'wpcalibrate-external-product-images' ); ?></label>
						<p class="wpcalibrate-hint"><?php esc_html_e( 'Character used to separate multiple external gallery URLs in the External Gallery Images column.', 'wpcalibrate-external-product-images' ); ?></p>
					</div>
					<div class="wpcalibrate-field-control">
						<select id="field_csv_delimiter" name="wpcalibrate_epi_settings[csv_delimiter]" class="wpcalibrate-select">
							<option value="," <?php selected( $settings['csv_delimiter'], ',' ); ?>><?php esc_html_e( 'Comma (,)', 'wpcalibrate-external-product-images' ); ?></option>
							<option value="|" <?php selected( $settings['csv_delimiter'], '|' ); ?>><?php esc_html_e( 'Pipe (|)', 'wpcalibrate-external-product-images' ); ?></option>
							<option value=";" <?php selected( $settings['csv_delimiter'], ';' ); ?>><?php esc_html_e( 'Semicolon (;)', 'wpcalibrate-external-product-images' ); ?></option>
						</select>
					</div>
				</div>
			</div>

			<div class="wpcalibrate-form-actions">
				<button type="submit" class="button button-primary wpcalibrate-btn-primary"><?php esc_html_e( 'Save Changes', 'wpcalibrate-external-product-images' ); ?></button>
			</div>
		</form>
		<?php
	}

	/**
	 * Render License tab.
	 *
	 * @return void
	 */
	private function render_tab_license(): void {
		$status_label = $this->license_provider->get_status_label();
		$info         = $this->license_provider->get_info();
		$masked_key   = $this->license_provider->get_masked_key();
		?>
		<div class="wpcalibrate-card">
			<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'Plugin License Status', 'wpcalibrate-external-product-images' ); ?></h2>
			<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Manage your product license and service connectivity.', 'wpcalibrate-external-product-images' ); ?></p>

			<div class="wpcalibrate-status-callout is-neutral">
				<span class="dashicons dashicons-info"></span>
				<div>
					<strong><?php echo esc_html( $status_label ); ?></strong>
					<p><?php echo esc_html( $info['message'] ); ?></p>
				</div>
			</div>

			<div class="wpcalibrate-field-row">
				<div class="wpcalibrate-field-info">
					<label for="field_license_key" class="wpcalibrate-label"><?php esc_html_e( 'License Key', 'wpcalibrate-external-product-images' ); ?></label>
					<p class="wpcalibrate-hint"><?php esc_html_e( 'Optional license key field reserved for future commercial support and managed CDN integrations.', 'wpcalibrate-external-product-images' ); ?></p>
				</div>
				<div class="wpcalibrate-field-control">
					<input type="text" id="field_license_key" name="wpcalibrate_license_key" value="<?php echo esc_attr( $masked_key ); ?>" placeholder="<?php esc_attr_e( 'Enter license key if supplied', 'wpcalibrate-external-product-images' ); ?>" class="regular-text" disabled />
					<p class="description" style="margin-top: 5px;"><?php esc_html_e( 'All core features in v1.0.0 are fully functional without a license key.', 'wpcalibrate-external-product-images' ); ?></p>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Support tab.
	 *
	 * @param array<string, mixed> $settings Active settings.
	 * @return void
	 */
	private function render_tab_support( array $settings ): void {
		global $wp_version;
		$wc_version = defined( 'WC_VERSION' ) ? WC_VERSION : 'unknown';
		?>
		<div class="wpcalibrate-card">
			<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'WPCalibrate Support & Inquiries', 'wpcalibrate-external-product-images' ); ?></h2>
			<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Need help, custom enhancements, or high-volume CDN architecture? Get in touch directly with our engineering team.', 'wpcalibrate-external-product-images' ); ?></p>

			<div class="wpcalibrate-support-grid">
				<div class="wpcalibrate-support-card">
					<span class="dashicons dashicons-email-alt wpcalibrate-support-icon"></span>
					<h3><?php esc_html_e( 'Email Support', 'wpcalibrate-external-product-images' ); ?></h3>
					<p><a href="mailto:support@wpcalibrate.com">support@wpcalibrate.com</a></p>
					<span class="wpcalibrate-support-meta"><?php esc_html_e( 'Standard 24h response', 'wpcalibrate-external-product-images' ); ?></span>
				</div>
				<div class="wpcalibrate-support-card">
					<span class="dashicons dashicons-phone wpcalibrate-support-icon"></span>
					<h3><?php esc_html_e( 'WhatsApp / Phone', 'wpcalibrate-external-product-images' ); ?></h3>
					<p><a href="https://wa.me/447474795976" target="_blank" rel="noopener noreferrer">+44 7474 795976</a></p>
					<span class="wpcalibrate-support-meta"><?php esc_html_e( 'Direct engineering support', 'wpcalibrate-external-product-images' ); ?></span>
				</div>
				<div class="wpcalibrate-support-card">
					<span class="dashicons dashicons-admin-site wpcalibrate-support-icon"></span>
					<h3><?php esc_html_e( 'Official Website', 'wpcalibrate-external-product-images' ); ?></h3>
					<p><a href="https://wpcalibrate.com" target="_blank" rel="noopener noreferrer">https://wpcalibrate.com</a></p>
					<span class="wpcalibrate-support-meta"><?php esc_html_e( 'Documentation & plugins', 'wpcalibrate-external-product-images' ); ?></span>
				</div>
				<div class="wpcalibrate-support-card">
					<span class="dashicons dashicons-cart wpcalibrate-support-icon"></span>
					<h3><?php esc_html_e( 'Marketplace', 'wpcalibrate-external-product-images' ); ?></h3>
					<p><a href="https://marketplace.wpcalibrate.com/" target="_blank" rel="noopener noreferrer">marketplace.wpcalibrate.com</a></p>
					<span class="wpcalibrate-support-meta"><?php esc_html_e( 'Verified extensions', 'wpcalibrate-external-product-images' ); ?></span>
				</div>
			</div>
		</div>

		<div class="wpcalibrate-card">
			<h2 class="wpcalibrate-card-title"><?php esc_html_e( 'System Status & Diagnostics', 'wpcalibrate-external-product-images' ); ?></h2>
			<p class="wpcalibrate-card-desc"><?php esc_html_e( 'Diagnostic overview for support requests.', 'wpcalibrate-external-product-images' ); ?></p>

			<table class="wpcalibrate-table">
				<tbody>
					<tr>
						<td><strong><?php esc_html_e( 'Plugin Version', 'wpcalibrate-external-product-images' ); ?></strong></td>
						<td><code><?php echo esc_html( WPCALIBRATE_EPI_VERSION ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WordPress Version', 'wpcalibrate-external-product-images' ); ?></strong></td>
						<td><code><?php echo esc_html( (string) $wp_version ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'WooCommerce Version', 'wpcalibrate-external-product-images' ); ?></strong></td>
						<td><code><?php echo esc_html( (string) $wc_version ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'PHP Runtime Version', 'wpcalibrate-external-product-images' ); ?></strong></td>
						<td><code><?php echo esc_html( PHP_VERSION ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Active Delivery Mode', 'wpcalibrate-external-product-images' ); ?></strong></td>
						<td><code><?php echo ( ( $settings['cache_mode'] ?? 'direct' ) === 'local' ) ? esc_html__( 'Local Cache Mode', 'wpcalibrate-external-product-images' ) : esc_html__( 'Direct Remote Mode', 'wpcalibrate-external-product-images' ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'Action Scheduler Available', 'wpcalibrate-external-product-images' ); ?></strong></td>
						<td><code><?php echo function_exists( 'as_enqueue_async_action' ) ? esc_html__( 'Yes', 'wpcalibrate-external-product-images' ) : esc_html__( 'No (Direct Execution)', 'wpcalibrate-external-product-images' ); ?></code></td>
					</tr>
					<tr>
						<td><strong><?php esc_html_e( 'cURL Extension', 'wpcalibrate-external-product-images' ); ?></strong></td>
						<td><code><?php echo extension_loaded( 'curl' ) ? esc_html__( 'Enabled', 'wpcalibrate-external-product-images' ) : esc_html__( 'Disabled', 'wpcalibrate-external-product-images' ); ?></code></td>
					</tr>
				</tbody>
			</table>
		</div>
		<?php
	}

	/**
	 * Render branding logo.
	 *
	 * @return void
	 */
	private function render_brand_logo(): void {
		$dark_path = WPCALIBRATE_EPI_DIR . 'branding/icon-dark.png';
		if ( file_exists( $dark_path ) ) {
			$data = file_get_contents( $dark_path );
			if ( false !== $data ) {
				$b64 = 'data:image/png;base64,' . base64_encode( $data );
				echo '<img src="' . esc_attr( $b64 ) . '" alt="WPCalibrate" class="wpcalibrate-header-logo" />';
				return;
			}
		}

		echo '<span class="dashicons dashicons-images-alt2 wpcalibrate-header-logo-fallback"></span>';
	}
}
