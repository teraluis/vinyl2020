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
 * @package   WC-Jilt/Frontend
 * @author    Jilt
 * @category  Frontend
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * WC-API handler class
 *
 * @since 1.0.0
 */
class WC_Jilt_WC_API_Handler {


	/**
	 * Sets up the API handle class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'woocommerce_api_jilt', array( $this, 'route' ) );
	}


	/**
	 * Handles requests to the Jilt WC API endpoint
	 *
	 * @since 1.0.0
	 */
	public function route() {

		// identify the response as coming from the Jilt for WooCommerce plugin
		@header( 'x-jilt-version: ' . wc_jilt()->get_version() );

		// handle connections
		if ( ! empty( $_REQUEST['connect'] ) ) {
			wc_jilt()->load_class( '/includes/handlers/class-wc-jilt-connection-handler.php', 'WC_Jilt_Connection_Handler' );
		}

		// recovery URL
		if ( ! empty( $_REQUEST['token'] ) && ! empty( $_REQUEST['hash'] ) ) {
			$this->handle_recreate_cart();
		}
	}


	/**
	 * Attempt to recreate the cart. Log an error/display storefront notice on
	 * failure, and either way redirect to the checkout page
	 *
	 * @since 1.1.0
	 */
	protected function handle_recreate_cart() {

		$checkout_url = wc_get_checkout_url();

		// forward along any UTM params
		foreach ( $_GET as $key => $val ) {
			if ( 0 === strpos( $key, 'utm_' ) ) {
				$checkout_url = add_query_arg( $key, $val, $checkout_url );
			}
		}

		try {

			$this->recreate_cart();

			// if a coupon was provided in the recovery URL, set it so it can be applied after redirecting to checkout
			if ( isset( $_REQUEST['coupon'] ) && $coupon = rawurldecode( $_REQUEST['coupon'] ) ) {

				$checkout_url = add_query_arg( array( 'coupon' => wc_clean( $coupon ) ), $checkout_url );
			}

		} catch ( Framework\SV_WC_Plugin_Exception $e ) {

			// start a session if needed - this ensures that notices are rendered
			if ( ! WC()->session->has_session() ) {
				WC()->session->set_customer_session_cookie( true );
			}

			wc_jilt()->get_logger()->warning( 'Could not recreate cart: ' . $e->getMessage() );

			wc_add_notice( __( "Oops, we weren't able to recreate your cart, sorry! Please try adding your items to your cart again.", 'jilt-for-woocommerce' ), 'error' );
		}

		wp_safe_redirect( $checkout_url );
		exit;
	}


	/** Recreate Cart Helpers ******************************************************/


	/**
	 * Recreate & checkout a cart from a Jilt checkout link
	 *
	 * Note: This behavior is not bypassed when the integration is disabled as
	 * it's always going to be valuable to a merchant to have functional
	 * recovery URLs
	 *
	 * @since 1.0.0
	 * @throws Framework\SV_WC_Plugin_Exception if hash verification fails
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	protected function recreate_cart() {

		if ( ! wc_jilt()->get_integration()->is_configured() ) {
			throw new Framework\SV_WC_Plugin_Exception( 'Plugin is not properly configured' );
		}

		$data = rawurldecode( $_REQUEST['token'] );
		$hash = $_REQUEST['hash'];

		$secret_keys   = wc_jilt()->get_integration()->get_secret_key_stash();
		$is_valid_hash = false;

		// try to verify the hash with all the secret keys from the stash, starting from the latest (last) one
		while ( $secret_key = array_pop( $secret_keys ) ) {

			if ( hash_equals( hash_hmac( 'sha256', $data, $secret_key ), $hash ) ) {
				$is_valid_hash = true;
				break;
			}
		}

		// verify hash
		if ( ! $is_valid_hash ) {
			throw new Framework\SV_WC_Plugin_Exception( 'Hash verification failed' );
		}

		// decode
		$data = json_decode( base64_decode( $data ) );

		// readability
		$cart_token = $data->cart_token;

		if ( ! $cart_token ) {
			throw new Framework\SV_WC_Plugin_Exception( 'Jilt cart token is empty.' );
		}

		// get Jilt order for verifying URL and recreating cart if session is not present
		$jilt_order = wc_jilt()->get_integration()->get_api()->get_order( $cart_token );

		// check if the order for this cart has already been placed
		$order_id = $this->get_order_id_for_cart_token( $cart_token );

		if ( $order_id && $order = wc_get_order( $order_id ) ) {

			$note = __( 'Customer visited Jilt order recovery URL.', 'jilt-for-woocommerce' );

			// re-enable a cancelled order for payment
			if ( $order->has_status( 'cancelled' ) ) {
				$order->update_status( 'pending', $note );
			} else {
				$order->add_order_note( $note );
			}

			$redirect = $order->needs_payment() ? $order->get_checkout_payment_url() : $order->get_checkout_order_received_url();

			WC()->session->set( 'wc_jilt_pending_recovery', true );

			// set (or refresh, if already set) session
			WC()->session->set_customer_session_cookie( true );

			wp_safe_redirect( $redirect );
			exit;
		}

		// check if cart is associated with a registered user / persistent cart
		$user_id = $this->get_user_id_for_cart_token( $cart_token );

		$cart_recreated = false;

		// order id is associated with a registered user
		if ( $user_id && $this->login_user( $user_id ) && $jilt_order->note ) {

			// save order note to be applied after redirect
			update_user_meta( $user_id, '_wc_jilt_order_note', $jilt_order->note );
			$cart_recreated = true;
		}

		if ( ! $cart_recreated ) {

			// set customer note in session, if present
			if ( $jilt_order->note ) {
				WC()->session->set( 'wc_jilt_order_note', $jilt_order->note );
			}

			// guest user
			$session = WC()->session->get_session( $jilt_order->client_session->token );

			if ( empty( $session ) || empty( $session['cart'] ) ) {
				$this->recreate_cart_from_jilt_order( $jilt_order );
			} else {
				$this->recreate_cart_for_guest( $session, $jilt_order );
			}
		}
	}


	/**
	 * Recreate cart for a user
	 *
	 * @since 1.0.0
	 * @param int $user_id The user ID
	 * @return boolean true if the user is logged in
	 */
	protected function login_user( $user_id ) {

		$logged_in = false;

		wc_jilt()->get_logger()->info( "Recreating cart for registered user: {$user_id}" );

		if ( is_user_logged_in() ) {

			// another user is logged in
			if ( (int) $user_id !== get_current_user_id() ) {

				wp_logout();

				// log the current user out, log in the new one
				if ( $this->allow_cart_recovery_user_login( $user_id ) ) {

					wc_jilt()->get_logger()->info( "Another user is logged in, logging them out & logging in user {$user_id}" );
					wp_set_current_user( $user_id );
					wp_set_auth_cookie( $user_id );
					update_user_meta( $user_id, '_wc_jilt_pending_recovery', true );
					$logged_in = true;

				// safety check fail: do not let an admin to be logged in automatically
				} else {

					wc_add_notice( __( 'Note: Auto-login disabled when recreating cart for WordPress Admin account. Checking out as guest.', 'jilt-for-woocommerce' ) );
					wc_jilt()->get_logger()->warning( "Not logging in user {$user_id} with admin rights" );
				}

			} else {

				wc_jilt()->get_logger()->info( 'User is already logged in' );
			}

		} else {

			// log the user in automatically
			if ( $this->allow_cart_recovery_user_login( $user_id ) ) {

				wc_jilt()->get_logger()->info( 'User is not logged in, logging in' );
				wp_set_current_user( $user_id );
				wp_set_auth_cookie( $user_id );
				update_user_meta( $user_id, '_wc_jilt_pending_recovery', true );
				$logged_in = true;

			// safety check fail: do not let an admin to be logged in automatically
			} else {

				wc_add_notice( __( 'Note: Auto-login disabled when recreating cart for WordPress Admin account. Checking out as guest.', 'jilt-for-woocommerce' ) );
				wc_jilt()->get_logger()->warning( "Not logging in user {$user_id} with admin rights" );
			}
		}

		wc_jilt()->get_logger()->info( 'Cart recreated from persistent cart' );

		return $logged_in;
	}


	/**
	 * Check if a user is allowed to be logged in for cart recovery
	 *
	 * @since 1.0.0
	 * @param int $user_id WP_User id
	 * @return bool
	 */
	private function allow_cart_recovery_user_login( $user_id ) {

		/**
		 * Filter users who do not possess high level rights
		 * to be logged in automatically upon cart recovery
		 *
		 * @since 1.0.0
		 * @param bool $allow_user_login Whether to allow or disallow
		 * @param int $user_id The user to log in
		 */
		$allow_user_login = apply_filters( 'wc_jilt_allow_cart_recovery_user_login', ! user_can( $user_id, 'edit_others_posts' ), $user_id );

		return (bool) $allow_user_login;
	}


	/**
	 * Recreate cart for a guest
	 *
	 * @TODO: this method is now very similar to the recreate_cart_from_jilt_order()
	 * method and can probably be merged/refactored to be more DRY {MR 2016-05-18}
	 *
	 * @since 1.0.0
	 * @param array $session retrieved session from db
	 * @param stdClass $jilt_order
	 */
	protected function recreate_cart_for_guest( $session, $jilt_order ) {

		wc_jilt()->get_logger()->info( 'Recreating cart for guest user with active session' );

		// recreate cart
		$cart = maybe_unserialize( $session['cart'] );

		$existing_cart_hash = md5( wp_json_encode( WC()->session->cart ) );
		$loaded_cart_hash   = md5( wp_json_encode( $cart ) );

		// avoid re-setting the cart object if it matches the existing session cart
		if ( ! hash_equals( $existing_cart_hash, $loaded_cart_hash ) ) {

			WC()->session->set( 'cart', $cart );

			// apply any valid coupons
			$applied_coupons = maybe_unserialize( $session['applied_coupons'] );
			WC()->session->set( 'applied_coupons', $this->get_valid_coupons( $applied_coupons ) );

			// select the chosen shipping methods if any
			$chosen_shipping_methods = ! empty( $session['chosen_shipping_methods'] ) ? maybe_unserialize( $session['chosen_shipping_methods'] ) : null;
			WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );

			$shipping_method_counts = ! empty( $session['shipping_method_counts'] ) ? maybe_unserialize( $session['shipping_method_counts'] ) : null;
			WC()->session->set( 'shipping_method_counts', $shipping_method_counts );

			// select the chosen payment method if any
			$chosen_payment_method = ! empty( $session['chosen_payment_method'] ) ? $session['chosen_payment_method'] : null;
			WC()->session->set( 'chosen_payment_method', $chosen_payment_method );
		}

		// set customer data
		$this->set_customer_session_data( $session, $jilt_order );

		// set Jilt data in session
		WC()->session->set( 'wc_jilt_cart_token', $jilt_order->cart_token );
		WC()->session->set( 'wc_jilt_pending_recovery', true );

		// set (or refresh, if already set) session
		WC()->session->set_customer_session_cookie( true );

		wc_jilt()->get_logger()->info( 'Cart recreated from session' );
	}


	/**
	 * Recreate the entire cart from a Jilt order. Generally used when a guest
	 * customer's existing session has expired
	 *
	 * TODO: this generates a new session entry each time the checkout recovery
	 * URL is visited because the customer ID for a new session can't be set (WC
	 * generates it internally). Extra sessions really don't matter too much
	 * (they still have the 48 hour expiration) but it's not super clean either.
	 * May be worth a PR to WC core to allow customer IDs to be set (perhaps via
	 * the WC_Session_Handler::set_customer_session_cookie() method) {MR 2016-05-18}
	 *
	 * @since 1.0.0
	 * @param stdClass $jilt_order
	 * @throws Framework\SV_WC_Plugin_Exception if required data is missing
	 */
	protected function recreate_cart_from_jilt_order( $jilt_order ) {

		wc_jilt()->get_logger()->info( 'Recreating cart for guest user with no active session' );

		/**
		 * Filters the the remote client session data sent from the Jilt App when recreating the local cart.
		 *
		 * This is potentially useful for adding support for other extensions.
		 *
		 * @since 1.4.0
		 *
		 * @param array $client_session session data returned from REST API
		 * @param stdClass $jilt_order returned from REST API
		 */
		$client_session = apply_filters( 'wc_jilt_remote_session_for_cart_recreate', $jilt_order->client_session, $jilt_order );

		// cart data must be array, JSON encode/decode is a hack to recursively convert object to array
		$cart                    = json_decode( wp_json_encode( $client_session->cart ), true );
		$applied_coupons         = (array) $client_session->applied_coupons;
		$chosen_shipping_methods = (array) $client_session->chosen_shipping_methods;
		$shipping_method_counts  = (array) $client_session->shipping_method_counts;
		$chosen_payment_method   = $client_session->chosen_payment_method;

		// sanity check
		if ( empty( $cart ) ) {
			throw new Framework\SV_WC_Plugin_Exception( 'Cart missing from Jilt order client session' );
		}

		// base session data
		WC()->session->set( 'cart', $cart );
		WC()->session->set( 'applied_coupons', $this->get_valid_coupons( $applied_coupons ) );
		WC()->session->set( 'chosen_shipping_methods', $chosen_shipping_methods );
		WC()->session->set( 'shipping_method_counts', $shipping_method_counts );
		WC()->session->set( 'chosen_payment_method', $chosen_payment_method );

		// customer
		$this->set_customer_session_data( array(), $jilt_order );

		// Jilt data
		WC()->session->set( 'wc_jilt_cart_token', $jilt_order->cart_token );
		WC()->session->set( 'wc_jilt_pending_recovery', true );

		// set (or refresh, if already set) session
		WC()->session->set_customer_session_cookie( true );

		wc_jilt()->get_logger()->info( 'Cart recreated from Jilt order' );
	}


	/** Helper methods ******************************************************/


	/**
	 * Set the customer session data after merging the existing (if any) customer
	 * data in the session with the customer data provided in the Jilt order.
	 *
	 * @since 1.1.0
	 *
	 * @param array $session session data
	 * @param \stdClass $jilt_order Jilt order retrieved from the API
	 */
	protected function set_customer_session_data( $session, $jilt_order ) {

		$session_customer = isset( $session['customer'] ) ? maybe_unserialize( $session['customer'] ) : array();

		$customer_data = array();

		$has_billing_address  = isset( $jilt_order->billing_address )  && ! empty( $jilt_order->billing_address );
		$has_shipping_address = isset( $jilt_order->shipping_address ) && ! empty( $jilt_order->shipping_address );

		foreach ( WC_Jilt_Order::get_jilt_order_address_mapping() as $wc_field => $jilt_field ) {

			if ( $has_billing_address && isset( $jilt_order->billing_address->{$jilt_field} ) ) {
				$customer_data[ $wc_field ] = $jilt_order->billing_address->{$jilt_field};
			}

			if ( $has_shipping_address && isset( $jilt_order->shipping_address->{$jilt_field} ) ) {
				$customer_data[ 'shipping_' . $wc_field ] = $jilt_order->shipping_address->{$jilt_field};
			}
		}

		foreach ( $customer_data as $key => $value ) {

			$method = Framework\SV_WC_Helper::str_starts_with( $key, 'shipping_' ) ? "set_{$key}" : "set_billing_{$key}";

			// note that the set_*() methods _must_ be used, as any customer data that's set
			// directly in the session is overwritten on the shutdown hook, see
			// WooCommerce::init()
			if ( is_callable( array( WC()->customer, $method ) ) ) {
				WC()->customer->$method( $value );
			}
		}
	}


	/**
	 * Returns $coupons, with any invalid coupons removed
	 *
	 * @since 1.0.3
	 * @param array $coupons array of string coupon codes
	 * @return array $coupons with any invalid codes removed
	 */
	private function get_valid_coupons( $coupons ) {
		$valid_coupons = array();

		if ( $coupons ) {
			foreach ( $coupons as $coupon_code ) {
				$the_coupon = new WC_Coupon( $coupon_code );

				if ( ! $the_coupon->is_valid() ) {
					continue;
				}

				$valid_coupons[] = $coupon_code;
			}
		}

		return $valid_coupons;
	}


	/**
	 * Get order ID for the provided cart token
	 *
	 * @since 1.0.0
	 * @param string $cart_token
	 * @return int|null Order ID, if found, null otherwise
	 */
	private function get_order_id_for_cart_token( $cart_token ) {

		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_wc_jilt_cart_token'
			AND meta_value = %s
		", $cart_token ) );
	}


	/**
	 * Get user ID for the provided cart token
	 *
	 * @since 1.0.0
	 * @param string $cart_token
	 * @return int|null User ID, if found, null otherwise
	 */
	private function get_user_id_for_cart_token( $cart_token ) {

		global $wpdb;

		return $wpdb->get_var( $wpdb->prepare( "
			SELECT user_id
			FROM {$wpdb->usermeta}
			WHERE meta_key = '_wc_jilt_cart_token'
			AND meta_value = %s
		", $cart_token ) );
	}


}
