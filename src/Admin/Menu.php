<?php
/**
 * WPCalibrate Top-Level Menu & Submenu Registration.
 *
 * @package WPCalibrate\ExternalProductImages
 */

declare(strict_types=1);

namespace WPCalibrate\ExternalProductImages\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Handles defensive parent menu creation and isolated plugin submenu registration.
 */
class Menu {

	/**
	 * Top-level menu slug shared across WPCalibrate plugins.
	 */
	public const PARENT_SLUG = 'wpcalibrate';

	/**
	 * Submenu page slug for this plugin.
	 */
	public const PAGE_SLUG = 'wpcalibrate-external-product-images';

	/**
	 * Capability required to access settings.
	 */
	public const REQUIRED_CAPABILITY = 'manage_woocommerce';

	/**
	 * Initialize menu hooks.
	 *
	 * @return void
	 */
	public function init(): void {
		// Priority 9 ensures parent is registered early, but defensively checks for existing registration.
		add_action( 'admin_menu', [ $this, 'register_menu' ], 9 );
		add_action( 'admin_head', [ $this, 'render_admin_menu_styles' ] );
	}

	/**
	 * Register the parent menu if missing, and add this plugin's submenu.
	 *
	 * @return void
	 */
	public function register_menu(): void {
		global $admin_page_hooks;

		$capability = self::REQUIRED_CAPABILITY;
		if ( ! current_user_can( $capability ) && current_user_can( 'manage_options' ) ) {
			$capability = 'manage_options';
		}

		// 1. Defensively register the parent "WPCalibrate" menu if no sibling WPCalibrate plugin has done so yet.
		if ( ! isset( $admin_page_hooks[ self::PARENT_SLUG ] ) ) {
			$icon_url = $this->get_menu_icon_url();

			add_menu_page(
				__( 'WPCalibrate', 'wpcalibrate-external-product-images' ),
				__( 'WPCalibrate', 'wpcalibrate-external-product-images' ),
				$capability,
				self::PARENT_SLUG,
				[ $this, 'render_parent_landing_page' ],
				$icon_url,
				58.5
			);
		}

		// 2. Register this plugin's dedicated submenu.
		$hook_suffix = add_submenu_page(
			self::PARENT_SLUG,
			__( 'External Product Images', 'wpcalibrate-external-product-images' ),
			__( 'External Images', 'wpcalibrate-external-product-images' ),
			$capability,
			self::PAGE_SLUG,
			[ SettingsPage::instance(), 'render' ]
		);

		// If this plugin created the parent menu, update the first duplicate submenu label.
		if ( isset( $admin_page_hooks[ self::PARENT_SLUG ] ) ) {
			// WordPress adds a child with the parent slug; rename it nicely if empty.
			global $submenu;
			if ( isset( $submenu[ self::PARENT_SLUG ] ) && ! empty( $submenu[ self::PARENT_SLUG ] ) ) {
				if ( $submenu[ self::PARENT_SLUG ][0][2] === self::PARENT_SLUG ) {
					$submenu[ self::PARENT_SLUG ][0][0] = __( 'Overview', 'wpcalibrate-external-product-images' );
				}
			}
		}
	}

	/**
	 * Renders parent landing fallback if user clicks the top-level item directly.
	 *
	 * @return void
	 */
	public function render_parent_landing_page(): void {
		// If our submenu exists, redirect or render the primary page.
		if ( current_user_can( self::REQUIRED_CAPABILITY ) || current_user_can( 'manage_options' ) ) {
			SettingsPage::instance()->render();
		} else {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'wpcalibrate-external-product-images' ) );
		}
	}

	/**
	 * Render CSS to constrain menu icon to 20x20 matching standard WordPress admin icon styling.
	 *
	 * @return void
	 */
	public function render_admin_menu_styles(): void {
		?>
		<style id="wpcalibrate-menu-icon-style">
			#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image {
				display: flex !important;
				align-items: center !important;
				justify-content: center !important;
			}
			#adminmenu .toplevel_page_wpcalibrate div.wp-menu-image img {
				width: 20px !important;
				height: 20px !important;
				max-width: 20px !important;
				max-height: 20px !important;
				padding: 0 !important;
				margin: 0 !important;
				object-fit: contain !important;
				opacity: 0.7;
				display: block !important;
				transition: opacity 0.15s ease-in-out;
			}
			#adminmenu .toplevel_page_wpcalibrate:hover div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.opensub div.wp-menu-image img {
				opacity: 1 !important;
			}
			#adminmenu .toplevel_page_wpcalibrate.wp-has-current-submenu div.wp-menu-image img,
			#adminmenu .toplevel_page_wpcalibrate.current div.wp-menu-image img {
				opacity: 1 !important;
			}
		</style>
		<?php
	}

	/**
	 * Get the menu icon URL using the authentic plugin branding asset.
	 *
	 * @return string
	 */
	private function get_menu_icon_url(): string {
		return plugins_url( 'branding/icon-white.png', WPCALIBRATE_EPI_FILE );
	}
}
