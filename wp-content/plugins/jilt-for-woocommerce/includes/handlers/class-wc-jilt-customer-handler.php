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
 * @package   WC-Jilt/Cart
 * @author    Jilt
 * @category  Frontend
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * Customer Handler class
 *
 * Handles populating and updating additional WC_Customer session data that's not
 * handled by WC core. See the $fields array for the additional data.
 *
 * @since 1.0.6
 */
class WC_Jilt_Customer_Handler {


	/** @var array $fields form/user meta key => WC_Customer class property */
	protected $fields = array(
		'billing_first_name'  => 'first_name',
		'billing_last_name'   => 'last_name',
		'billing_company'     => 'company',
		'billing_email'       => 'email',
		'billing_phone'       => 'phone',
		'shipping_first_name' => 'shipping_first_name',
		'shipping_last_name'  => 'shipping_last_name',
		'shipping_company'    => 'shipping_company',
	);


	/**
	 * Bootstrap class.
	 *
	 * @since 1.0.6
	 */
	public function __construct() {
		$this->init();
	}


	/**
	 * Add required actions.
	 *
	 * @since 1.0.6
	 */
	protected function init() {

		// WC 3.0+ handles getting/setting the additional $fields above
		if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_lt_3_0() ) {

			add_action( 'woocommerce_init', array( $this, 'set_customer_data_from_user' ) );

			add_action( 'woocommerce_checkout_update_order_review', array( $this, 'update_from_order_review_ajax' ) );

			add_action( 'woocommerce_checkout_process', array( $this, 'update_data' ) );

			add_filter( 'woocommerce_checkout_get_value', array( $this, 'maybe_set_checkout_field_value' ), 1, 2 );
		}

		// set the customer last order on the user meta when an order is complete or the user profile is updated
		add_action( 'woocommerce_payment_complete',     array( $this, 'set_customer_last_order' ) );
		add_action( 'woocommerce_order_status_changed', array( $this, 'set_customer_last_order_on_completed_order' ), 10, 4 );
		add_action( 'profile_update',                   array( $this, 'set_customer_last_order_on_profile_updated' ) );

		add_action( 'wc_ajax_jilt_set_customer', array( $this, 'ajax_set_customer' ) );

		add_action( 'wc_ajax_jilt_set_customer_email_capture_disallowed', array( $this, 'ajax_set_customer_email_capture_disallowed' ) );

		// set customer info upon login
		add_action( 'wp_login', array( $this, 'customer_login' ), 1, 2 );
	}


	/**
	 * Set the additional data (f not already set) from user meta (when the customer
	 * is logged in) immediately after the WC_Customer object is instantiated.
	 *
	 * @since 1.0.6
	 */
	public function set_customer_data_from_user() {

		// nothing to do for non-logged in users
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id = get_current_user_id();

		// set from user meta, if not already set
		foreach ( $this->fields as $user_meta_key => $customer_property ) {

			if ( isset( WC()->customer->$customer_property ) ) {
				continue;
			}

			if ( $value = get_user_meta( $user_id, $user_meta_key, true ) ) {
				WC()->customer->$customer_property = $value;
			}
		}

		// handle when only user data is set (no billing_*/shipping_* user meta)
		if ( ! metadata_exists( 'user', $user_id, 'billing_email' ) ) {

			$user = get_user_by( 'id', $user_id );

			WC()->customer->email      = $user->user_email;
			WC()->customer->first_name = $user->first_name;
			WC()->customer->last_name  = $user->last_name;
		}
	}


	/**
	 * Update the additional data at checkout when the Ajax order review is triggered.
	 *
	 * @since 1.0.6
	 * @param string $post_data jQuery.serialize()'d form data
	 */
	public function update_from_order_review_ajax( $post_data ) {

		$posted = array();

		parse_str( $post_data, $posted );

		$this->update_data( $posted );
	}


	/**
	 * Update the additional data during checkout processing.
	 *
	 * @since 1.0.6
	 * @param array $data checkout form data
	 */
	public function update_data( $data = array() ) {

		if ( empty( $data ) ) {

			$data = $_POST;

			if ( wc_jilt()->get_integration()->ask_consent_at_checkout() ) {

				$marketing_email_consent = isset( $data['wc_jilt_checkout_consent'] ) && 'yes' === $data['wc_jilt_checkout_consent'];

				if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
					WC()->customer->update_meta_data( '_wc_jilt_marketing_email_consent', $marketing_email_consent );
				} else {
					WC()->customer->marketing_email_consent = $marketing_email_consent;
				}
			}
		}

		foreach ( $this->fields as $field_key => $customer_property ) {

			if ( ! empty( $data[ $field_key ] ) ) {

				WC()->customer->$customer_property = wc_clean( $data[ $field_key ] );
			}
		}
	}


	/**
	 * Ajax handler for setting customer data.
	 *
	 * @since 1.0.6
	 */
	public function ajax_set_customer() {

		// first security check: return false on failure
		$valid_nonce = check_ajax_referer( 'jilt-for-wc', 'security', false );

		if ( ! $valid_nonce ) {

			// remove any of WC's nonce session modifications since the nonce may
			// have been generated before a session was created
			remove_filter( 'nonce_user_logged_out', array( WC()->session, 'nonce_user_logged_out' ) );

			// second security check: die on failure
			check_ajax_referer( 'jilt-for-wc', 'security' );
		}


		// prevent overriding the logged in user's email address
		if ( is_user_logged_in() ) {
			wp_send_json_error( array(
				'message' => __( 'You cannot set customer data for logged-in user.', 'jilt-for-woocommerce' ),
			) );
		}

		// start the session if not yet started
		if ( ! WC()->session->has_session() ) {
			WC()->session->set_customer_session_cookie( true );
		}

		if ( ! empty( $_POST['email_capture_opt_out'] ) && wc_jilt()->get_integration()->show_email_usage_notice() ) {

			// flag the customer to opt out or in to email collection
			$opt_out = 'true' === $_POST['email_capture_opt_out'];
			WC_Jilt_Session::set_customer_email_collection_opt_out( $opt_out );
		}

		if ( ! empty( $_POST['add_to_cart_opt_out'] ) ) {
			// do not ask again to collect an email by adding to cart
			WC()->session->set( 'jilt_opt_out_add_to_cart_email_capture', true );
		}

		$first_name = ! empty( $_POST['first_name'] ) ? sanitize_user( $_POST['first_name'] ) : null;
		$last_name  = ! empty( $_POST['last_name'] ) ? sanitize_user( $_POST['last_name'] ) : null;
		$email      = ! empty( $_POST['email'] ) ? filter_var( $_POST['email'], FILTER_VALIDATE_EMAIL ) : null;

		$this->set_customer_info( $first_name, $last_name, $email );

		// if the customer has a cart, always recalculate the totals so their order gets updated
		if ( ! WC()->cart->is_empty() ) {
			wc_jilt()->get_cart_handler_instance()->cart_updated();
		}

		wp_send_json_success( array(
			'message' => 'Successfully set customer data.'
		) );
	}


	/**
	 * Sets the customer's last order data when an order status changes to complete.
	 *
	 * @internal
	 *
	 * @since 1.5.6
	 *
	 * @param int $order_id order ID
	 * @param string $old_status status moved from
	 * @param string $new_status status moved to
	 * @param \WC_Order $order order object
	 */
	public function set_customer_last_order_on_completed_order( $order_id, $old_status, $new_status, $order ) {

		if ( 'completed' === $new_status ) {

			$this->set_customer_last_order( $order );
		}
	}


	/**
	 * Sets the customer's last order data when a user is updated.
	 *
	 * @internal
	 *
	 * @since 1.5.6
	 *
	 * @param int $user_id the ID of the user being updated
	 */
	public function set_customer_last_order_on_profile_updated( $user_id ) {

		// this may trigger setting the customer last order user meta if not present
		$this->get_customer_last_order_data( $user_id );
	}


	/**
	 * Sets a customer's last order ID and number on user meta.
	 *
	 * @since 1.5.6
	 *
	 * @param array|int|\WC_Order $order_id the order (can be an array containing a user ID, in which case will flag the meta to 0 to mark no complete orders)
	 * @return null set data or null if not set
	 */
	public function set_customer_last_order( $order_id ) {

		$last_order_data = null;

		if ( is_numeric( $order_id ) ) {
			$order = wc_get_order( $order_id );
		} else {
			$order = $order_id;
		}

		if ( $order instanceof \WC_Order && ( $user_id = $order->get_user_id() ) ) {

			$last_order_data = array(
				'last_order_id'     => Framework\SV_WC_Order_Compatibility::get_prop( $order, 'id' ),
				'last_order_number' => $order->get_order_number(),
			);

			$success = (bool) update_user_meta( $user_id, '_wc_last_order', $last_order_data );

			if ( ! $success ) {
				$last_order_data = null;
			}

		} elseif ( is_array( $order_id ) ) {

			$user_id = current( $order_id );

			if ( is_numeric( $user_id ) && ( $user = get_user_by( 'id', $user_id ) ) ) {

				update_user_meta( $user->ID, '_wc_last_order', 0 );
			}
		}

		return $last_order_data;
	}


	/**
	 * Retrieves the last order information for a user.
	 *
	 * If there's no information set for the user, it will try to fetch the latest order and set the information on the user meta.
	 *
	 * @since 1.5.6
	 *
	 * @param int|\WP_User|\WC_Customer $customer_id user identifier
	 * @return array|null
	 */
	private function get_customer_last_order_data( $customer_id ) {

		$last_order_data = null;

		if ( $customer_id instanceof \WP_User ) {
			$customer_id = (int) $customer_id->ID;
		} elseif ( $customer_id instanceof \WC_Customer ) {
			$customer_id = Framework\SV_WC_Plugin_Compatibility::is_wc_version_lt_3_0() ? (int) $customer_id->ID : (int) $customer_id->get_id();
		}

		if ( is_numeric( $customer_id ) && $customer_id > 0 ) {

			$last_order_data = get_user_meta( $customer_id, '_wc_last_order', true );

			if ( ! is_array( $last_order_data ) ) {

				$user_orders = (array) wc_get_orders( array(
					'customer' => $customer_id,
					'limit'    => 1,
					'status'   => 'completed',
					'orderby'  => 'date',
				) );

				if ( ! empty( $user_orders ) && is_array( $user_orders ) ) {
					$last_order_data = $this->set_customer_last_order( current( $user_orders ) );
				} else {
					// set meta to 0 to mark no complete orders (it will be updated with the next order)
					$this->set_customer_last_order( array( $customer_id ) );
				}
			}
		}

		return ! empty( $last_order_data ) && is_array( $last_order_data ) ? $last_order_data : null;
	}


	/**
	 * Returns a customer's last order as stored in user meta.
	 *
	 * @since 1.5.6
	 *
	 * @param int|\WP_User|\WC_Customer $customer user identifier
	 * @return \WC_Order|null
	 */
	public function get_customer_last_order( $customer ) {

		$last_order = wc_get_order( (int) $this->get_customer_last_order_id( $customer ) );

		return $last_order instanceof \WC_Order ? $last_order : null;
	}


	/**
	 * Returns the customer's last order ID as stored in user meta.
	 *
	 * @since 1.5.6
	 *
	 * @param int|\WP_User|\WC_Customer $customer user identifier
	 * @return int|null
	 */
	public function get_customer_last_order_id( $customer ) {

		$order_data = $this->get_customer_last_order_data( $customer );

		return is_array( $order_data ) && isset( $order_data['last_order_id'] ) && is_numeric( $order_data['last_order_id'] ) ? (int) $order_data['last_order_id'] : null;
	}


	/**
	 * Returns the customer's last order number as stored in user meta.
	 *
	 * @since 1.5.6
	 *
	 * @param int|\WP_User|\WC_Customer $customer user identifier
	 * @return string|null
	 */
	public function get_customer_last_order_number( $customer ) {

		$order_data = $this->get_customer_last_order_data( $customer );

		return is_array( $order_data ) && isset( $order_data['last_order_number'] ) && ( is_string( $order_data['last_order_number'] ) || is_numeric( $order_data['last_order_number'] ) ) ? (string) $order_data['last_order_number'] : null;
	}


	/**
	 * Handle setting first/last name and email when a customer logs in.
	 *
	 * @since 1.1.0
	 * @param string $username, unused
	 * @param \WP_User $user
	 */
	public function customer_login( $username, $user ) {

		$this->set_customer_info( $user->first_name, $user->last_name, $user->user_email );
	}


	/**
	 * Set the first name, last name, and email address for Customer session
	 * object.
	 *
	 * @since 1.1.0
	 *
	 * @param string $first_name
	 * @param string $last_name
	 * @param string $email
	 */
	private function set_customer_info( $first_name, $last_name, $email ) {

		if ( ! WC()->customer instanceof WC_Customer ) {
			return;
		}

		WC()->customer->set_billing_first_name( $first_name );
		WC()->customer->set_billing_last_name( $last_name );

		try {

			WC()->customer->set_billing_email( $email );

		} catch( \WC_Data_Exception $exception ) {

			wc_jilt()->get_logger()->error( 'Error setting customer email: ' . $exception->getMessage() );
		}

	}


	/**
	 * Pre-fill checkout fields with additional WC_Customer data if set
	 *
	 * @since 1.0.6
	 * @param string|null $value default checkout field value
	 * @param string $checkout_field_name checkout field name
	 * @return string
	 */
	public function maybe_set_checkout_field_value( $value, $checkout_field_name ) {

		if ( isset( $this->fields[ $checkout_field_name ] ) ) {

			// map checkout field name to that used by the WC_Customer class
			$customer_field_name = $this->fields[ $checkout_field_name ];
		} else {

			// return the default value if the checkout field isn't one managed by WC_Customer
			return $value;
		}

		return ! empty( WC()->customer->$customer_field_name ) ? WC()->customer->$customer_field_name : $value;
	}


	/**
	 * Flags a customer to opt out from email notifications from Jilt via AJAX.
	 *
	 * @internal
	 *
	 * @since 1.4.5
	 */
	public function ajax_set_customer_email_capture_disallowed() {

		check_ajax_referer( 'jilt-for-wc', 'security' );

		if ( ! empty( $_POST['email_capture_opt_out'] ) ) {

			if ( WC_Jilt_Session::set_customer_email_collection_opt_out( true ) ) {
				wp_send_json_success();
			}
		 }

		wp_send_json_error();
	}


}
