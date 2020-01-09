<?php
/**
 * WooCommerce Jilt
 *
 * This source file is subject to the GNU General Public License v3.0
 * that is bundled with this package in the file license.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.html
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@jilt.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade WooCommerce Jilt to newer
 * versions in the future. If you wish to customize WooCommerce Jilt for your
 * needs please refer to http://help.jilt.com/jilt-for-woocommerce
 *
 * @package   WC-Jilt/Integrations
 * @author    Jilt
 * @category  Frontend
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * Adds "compatibility" by unhooking an action that the gift card plugins
 * misuse within the admin, and which breaks the Jilt for WC settings page
 * when saving.
 *
 * This covers the following plugins:
 *
 * - https://wordpress.org/plugins/gift-cards-for-woocommerce/
 * - https://wp-ronin.com/products/gift-cards/ (premium addon for gift-cards-for-woocommerce)
 * - https://codecanyon.net/item/woocommerce-ultimate-gift-card/19191057
 *
 * @since 1.4.0
 */
class WC_Jilt_Gift_Cards_Integration extends WC_Jilt_Integration_Base {


	/**
	 * Setup the Gift Cards integration class
	 *
	 * @since 1.4.0
	 */
	public function __construct() {

		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			// Note: We hook into plugins_loaded, 11 in order to follow the
			//   WC Ultimate Gift Card plugin
			add_action( 'plugins_loaded', array( $this, 'unhook_coupons_enabled_filters' ), 11 );
		}
	}


	/**
	 * Get the title for this integration
	 *
	 * @see WC_Jilt_Integration::get_title()
	 * @since 1.4.0
	 * @return string integration title
	 */
	public function get_title() {

		return __( 'Gift Cards', 'jilt-for-woocommerce' );
	}


	/**
	 * Is this integration active?
	 *
	 * @see WC_Jilt_Integration::is_active()
	 * @since 1.4.0
	 * @return boolean
	 */
	public function is_active() {

		return $this->is_wc_gift_cards_active() || $this->is_wc_ultimate_gift_cards_active();
	}


	/**
	 * Unhook the woocommerce_coupons_enabled that breaks the Jilt for WC admin
	 * settings page
	 *
	 * @since 1.4.0
	 */
	public function unhook_coupons_enabled_filters() {
		global $wp_filter;

		if ( $this->is_wc_ultimate_gift_cards_active() ) {
			// remove the offending filter for this orphaned instance
			if ( isset( $wp_filter['woocommerce_coupons_enabled']->callbacks[10] ) ) {
				foreach ( $wp_filter['woocommerce_coupons_enabled']->callbacks[10] as $name => $filter_args ) {
					if ( false !== strpos( $name, 'mwb_wgm_hidding_coupon_field_on_cart' ) ) {
						remove_filter( 'woocommerce_coupons_enabled', $filter_args['function'] );
					}
				}
			}
		}

		if ( $this->is_wc_gift_cards_active() ) {
			remove_filter( 'woocommerce_coupons_enabled', 'wpr_disable_coupons' );
		}
	}


	/**
	 * Is the WooCommerce Gift Cards plugin active?
	 *
	 * @since 1.4.0
	 * @return boolean true if the WooCommerce Gift Cards plugin is active
	 */
	private function is_wc_gift_cards_active() {
		return wc_jilt()->is_plugin_active( 'giftcards.php' );
	}


	/**
	 * Is the WooCommerce Ultimate Gift Cards plugin active?
	 *
	 * @since 1.4.0
	 * @return boolean true if the WooCommerce Ultimate Gift Cards plugin is active
	 */
	private function is_wc_ultimate_gift_cards_active() {
		return wc_jilt()->is_plugin_active( 'woocommerce-ultimate-gift-card.php' );
	}


}
