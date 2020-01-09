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
 * @package   WC-Jilt
 * @author    Jilt
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 *
 * @since 1.2.0
 */
class WC_Jilt_Session {


	/**
	 * Set Jilt order data to the session and user meta, if customer is logged in
	 *
	 * @since 1.0.0
	 * @param string $cart_token
	 */
	public static function set_jilt_order_data( $cart_token ) {

		WC()->session->set( 'wc_jilt_cart_token', $cart_token );

		if ( $user_id = get_current_user_id() ) {
			update_user_meta( $user_id, '_wc_jilt_cart_token', $cart_token );
		}
	}


	/**
	 * Unset Jilt order id from session and user meta
	 *
	 * @since 1.0.0
	 *
	 * @param integer $user_id optional user id, defaults to get_current_user_id()
	 */
	public static function unset_jilt_order_data( $user_id = null ) {

		if ( WC()->session ) {
			unset( WC()->session->wc_jilt_cart_token, WC()->session->wc_jilt_pending_recovery );
		}

		if ( $user_id || ( $user_id = get_current_user_id() ) ) {
			delete_user_meta( $user_id, '_wc_jilt_cart_token' );
			delete_user_meta( $user_id, '_wc_jilt_pending_recovery' );
		}
	}


	/**
	 * Flags a customer to not receive emails from Jilt.
	 *
	 * @since 1.4.5
	 *
	 * @param bool $value true to opt out, false to opt in
	 * @param null|int|\WP_User $user_id optional user to set the preference for not receiving Jilt emails, defaults to current user
	 * @return bool
	 */
	public static function set_customer_email_collection_opt_out( $value, $user_id = null ) {

		$user_id = null === $user_id ? get_current_user_id() : $user_id;

		if ( $user_id instanceof WP_User ) {
			$user_id = $user_id->ID;
		}

		$pretty_value = $value ? 'yes' : 'no';

		if ( $user_id ) {
			$success = update_user_meta( $user_id, '_wc_jilt_email_collection_opt_out', $pretty_value );
		} else {

			WC()->session->set( 'jilt_email_collection_opt_out', $pretty_value );

			// legacy session var, use true
			if ( $value ) {
				WC()->session->set( 'jilt_opt_out_add_to_cart_email_capture', true );
			}

			$success = true;
		}

		return (bool) $success;
	}


	/**
	 * Updates the customer's marketing consent flag.
	 *
	 * @since 1.4.5
	 *
	 * @param bool $consent_to_marketing true if they consent, false otherwise
	 * @param null|int|\WP_User $user_id user optional user to check opt out status for, defaults to current user
	 * @return bool
	 */
	public static function set_customer_marketing_consent( $consent_to_marketing, $user_id = null ) {

		$user_id = null === $user_id ? get_current_user_id() : $user_id;

		if ( $user_id instanceof WP_User ) {
			$user_id = $user_id->ID;
		}

		$pretty_value = $consent_to_marketing ? 'yes' : 'no';

		if ( $user_id ) {
			$success = update_user_meta( $user_id, '_wc_jilt_marketing_email_consent', $pretty_value );
		} else {
			WC()->session->set( 'jilt_marketing_email_consent', $pretty_value );
			$success = true;
		}

		return (bool) $success;
	}


	/** Getter methods ******************************************************/


	/**
	 * Return the cart token from the session
	 *
	 * @since 1.0.0
	 *
	 * @param integer $user_id optional user id
	 * @return string|null
	 */
	public static function get_cart_token( $user_id = null ) {

		if ( $user_id ) {
			return get_user_meta( $user_id, '_wc_jilt_cart_token' );
		} else {
			return ( WC()->session ) ? WC()->session->get( 'wc_jilt_cart_token' ) : '';
		}
	}


	/**
	 * Return the Jilt order ID from the session
	 *
	 * @since 1.0.0
	 * @deprecated since 1.4.0
	 * @return string|null
	 */
	public static function get_jilt_order_id() {

		_deprecated_function( 'WC_Jilt_Session::get_jilt_order_id()', '1.4.0', 'WC_Jilt_Session::get_cart_token' );

		return self::get_cart_token();
	}


	/**
	 * Returns true if the current checkout was created by a customer visiting
	 * a Jilt provided recovery URL
	 *
	 * @since 1.0.0
	 *
	 * @param integer $user_id optional user id, defaults to get_current_user_id()
	 * @return bool
	 */
	public static function is_pending_recovery( $user_id = null ) {

		if ( $user_id || ( $user_id = get_current_user_id() ) ) {
			return (bool) get_user_meta( $user_id, '_wc_jilt_pending_recovery', true );
		} elseif ( isset( WC()->session ) ) {
			return (bool) WC()->session->wc_jilt_pending_recovery;
		}

		return false;
	}


	/**
	 * Return the client session data that should be stored in Jilt. This is used
	 * to recreate the cart for guest customers who do not have an active session.
	 *
	 * Note that we're explicitly *not* saving the entire session, as it could
	 * contain confidential information that we don't want stored in Jilt. For
	 * future integrations with other extensions, the filter can be used to include
	 * their data.
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_client_session() {

		$session = array(
			'token'                   => WC()->session->get_customer_id(),
			'cart'                    => WC()->session->get( 'cart' ),
			'customer'                => self::get_customer_session_data(),
			'applied_coupons'         => WC()->session->get( 'applied_coupons' ),
			'chosen_shipping_methods' => WC()->session->get( 'chosen_shipping_methods' ),
			'shipping_method_counts'  => WC()->session->get( 'shipping_method_counts' ),
			'chosen_payment_method'   => WC()->session->get( 'chosen_payment_method' ),
		);

		/**
		 * Allow actors to filter the client session data sent to Jilt. This is
		 * potentially useful for adding support for other extensions.
		 *
		 * @since 1.0.0
		 * @param array $session session data
		 */
		return apply_filters( 'wc_jilt_get_client_session', $session );
	}


	/**
	 * Checks whether a user has opted out from receiving emails from Jilt.
	 *
	 * @since 1.4.5
	 *
	 * @param null|int|\WP_User $user_id user optional user to check opt out status for, defaults to current user
	 * @return bool|null true if opted out, false if opted in, null if not yet set
	 */
	public static function get_customer_email_collection_opt_out( $user_id = null ) {

		$user_id = null === $user_id ? get_current_user_id() : $user_id;

		if ( $user_id instanceof WP_User ) {
			$user_id = $user_id->ID;
		}

		if ( ! $user_id ) {
			$opt_out = WC()->session->get( 'jilt_email_collection_opt_out' );
		} else {
			$opt_out = get_user_meta( $user_id, '_wc_jilt_email_collection_opt_out', true );
		}

		if ( 'yes' === $opt_out ) {
			return true;
		} elseif ( 'no' === $opt_out ) {
			return false;
		} else {
			return null;
		}
	}


	/**
	 * Checks whether a user has consented to marketing emails.
	 *
	 * @since 1.4.5
	 *
	 * @param null|int|\WP_User $user_id user optional user to check opt out status for, defaults to current user
	 * @return bool
	 */
	public static function get_customer_marketing_consent( $user_id = null ) {

		$user_id = null === $user_id ? get_current_user_id() : $user_id;

		if ( $user_id instanceof WP_User ) {
			$user_id = $user_id->ID;
		}

		if ( $user_id ) {
			$consented = get_user_meta( $user_id, '_wc_jilt_marketing_email_consent', true );
		} else {
			$consented = WC()->session->get( 'jilt_marketing_email_consent' );
		}

		if ( 'yes' === $consented ) {
			return true;
		} elseif ( 'no' === $consented ) {
			return false;
		} else {
			return null;
		}
	}


	/**
	 * The WC_Customer class does not persist data changed during an execution cycle
	 * until the `shutdown` hook. Because of this, the get_client_session() method
	 * above can't get the customer data directly as it can possibly contain
	 * stale data. This method builds the customer session data directly from
	 * the WC_Customer class which will return the most recent data.
	 *
	 * @since 1.0.6
	 * @return array
	 */
	protected static function get_customer_session_data() {

		$data = array();

		$properties = array(
			'first_name', 'last_name', 'company', 'email', 'phone', 'address_1',
			'address_2', 'city', 'state', 'postcode', 'country', 'shipping_first_name',
			'shipping_last_name', 'shipping_company', 'shipping_address_1',
			'shipping_address_2', 'shipping_city', 'shipping_state', 'shipping_postcode',
			'shipping_country'
		);

		foreach ( $properties as $property ) {

			if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {

				$method = Framework\SV_WC_Helper::str_starts_with( $property, 'shipping_' ) ? "get_{$property}" : "get_billing_{$property}";

				$data[ $property ] = WC()->customer->$method();

			} else {

				$data[ $property ] = WC()->customer->$property;
			}

		}

		$data['is_vat_exempt']       = WC()->customer->is_vat_exempt();
		$data['calculated_shipping'] = WC()->customer->has_calculated_shipping();

		return $data;
	}


}
