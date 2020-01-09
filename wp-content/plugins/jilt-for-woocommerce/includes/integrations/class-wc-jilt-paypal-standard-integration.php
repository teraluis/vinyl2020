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

/**
 * Add enhanced support for the WC core PayPal Standard plugin by clearing the
 * persistent cart and related Jilt data from the user meta record for a logged
 * in user.
 *
 * This provides a more robust solution that doesn't rely on the thank-you page
 * being rendered to clear the persistent cart/Jilt data.
 *
 * Without this integration active, if a user returns to the site in the same
 * session bypassing the thank-you page, or closes their browser while still on
 * the PayPal hosted success page, and later returns to the shop and logs into
 * their account, WC would load the persistent cart, and Jilt for WC would start
 * sending order update requests for the already-used cart token.
 *
 * @see https://github.com/skyverge/jilt-for-woocommerce/wiki/PayPal-Standard-Integration
 * @since 1.4.2
 */
class WC_Jilt_Paypal_Standard_Integration extends WC_Jilt_Integration_Base {


	/**
	 * Setup the PayPal Standard integration class
	 *
	 * @since 1.4.2
	 */
	public function __construct() {
		add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 15, 3 );
	}


	/**
	 * Get the title for this integration
	 *
	 * @see WC_Jilt_Integration::get_title()
	 * @since 1.4.2
	 *
	 * @return string integration title
	 */
	public function get_title() {
		return __( 'PayPal Standard', 'jilt-for-woocommerce' );
	}


	/**
	 * Is this integration active?
	 *
	 * @see WC_Jilt_Integration::is_active()
	 * @since 1.4.2
	 *
	 * @return boolean
	 */
	public function is_active() {
		return true;
	}


	/**
	 * Clear any persistent cart/jilt session data for logged in customers
	 *
	 * @since 1.4.2
	 *
	 * @param int $order_id order ID
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function order_status_changed( $order_id, $old_status, $new_status ) {
		global $wp;

		try {
			// PayPal IPN request
			if ( ! empty( $wp->query_vars['wc-api'] ) && 'WC_Gateway_Paypal' === $wp->query_vars['wc-api'] ) {

				$order = wc_get_order( $order_id );

				// PayPal order is completed or authorized: clear any user session
				// data so that we don't have to rely on the thank-you page rendering
				if ( ( $order->is_paid() || $new_status == 'on-hold' ) && ( $user_id = $order->get_user_id() ) ) {

					delete_user_meta( $user_id, '_woocommerce_persistent_cart_' . get_current_blog_id() );

					if ( WC_Jilt_Session::is_pending_recovery( $user_id ) ) {
						wc_jilt()->get_integration()->mark_order_as_recovered( $order_id );
					}

					if ( WC_Jilt_Session::get_cart_token( $user_id ) ) {
						WC_Jilt_Session::unset_jilt_order_data( $user_id );
					}
				}
			}
		} catch ( Exception $e ) {
			// safety net: we wouldn't want to halt IPN processing due to some exception being thrown
			wc_jilt()->get_logger()->warning( "Error handling order status change of '{$order_id}' from '{$old_status}' to '{$new_status}' during PayPal IPN: '{$e->getMessage()}'" );
		}
	}


}
