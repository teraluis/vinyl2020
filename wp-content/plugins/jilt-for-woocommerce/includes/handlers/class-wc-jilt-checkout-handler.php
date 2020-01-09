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
 * @package   WC-Jilt/Checkout
 * @author    Jilt
 * @category  Frontend
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * Checkout Class
 *
 * Handles checkout page and orders that have been placed, but not yet paid for
 *
 * @since 1.0.0
 */
class WC_Jilt_Checkout_Handler {


	/**
	 * Setup class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->init();
	}


	/**
	 * Add hooks
	 *
	 * @since 1.0.0
	 */
	protected function init() {

		add_filter( 'woocommerce_form_field_email', array( $this, 'add_email_usage_notice' ), 100, 2 );

		add_action( 'woocommerce_review_order_before_submit', array( $this, 'output_privacy_consent_prompt' ), 100 );

		if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {

			add_filter( 'woocommerce_checkout_fields', array( $this, 'move_checkout_email_field' ), 1 );

		} else {

			// load customer data from session when a cart is recreated
			add_action( 'woocommerce_before_checkout_form', array( $this, 'load_customer_data_from_session' ) );
		}

		// maybe apply coupon code provided in the recovery URL
		add_action( 'wp', array( $this, 'maybe_apply_cart_recovery_coupon' ), 20 );

		// set order note content available and if pending recovery
		add_action( 'woocommerce_checkout_get_value', array( $this, 'maybe_set_order_note' ), 1, 2 );

		// handle placed orders
		add_action( 'woocommerce_checkout_order_processed', array( $this, 'checkout_order_processed' ) );

		// handle updating Jilt order data after a successful payment, for certain gateways
		add_filter( 'woocommerce_payment_successful_result', array( $this, 'maybe_update_jilt_order_on_successful_payment' ), 10, 2 );

		// handle payment complete, from a direct gateway
		add_action( 'woocommerce_payment_complete', array( $this, 'payment_complete' ) );

		// handle marking an order as recovered when completing it from the pay page
		add_action( 'woocommerce_thankyou', array( $this, 'handle_pay_page_order_completion' ) );
	}


	/**
	 * Gets the cart checkout URL for Jilt
	 *
	 * Visiting this URL will load the associated cart from session/persistent cart
	 *
	 * @since 1.0.0
	 * @param string $jilt_cart_token Jilt cart token
	 * @return string
	 */
	public static function get_checkout_recovery_url( $jilt_cart_token ) {

		$data = array( 'cart_token' => $jilt_cart_token );

		// encode
		$data = base64_encode( wp_json_encode( $data ) );

		// add hash for easier verification that the checkout URL hasn't been tampered with
		$integration = wc_jilt()->get_integration();
		$secret      = 'secret_key' === $integration->get_auth_method() ? $integration->get_secret_key() : $integration->get_client_secret();
		$hash        = hash_hmac( 'sha256', $data, $secret );
		$url         = self::get_jilt_wc_api_url();

		// returns URL like:
		// pretty permalinks enabled - https://example.com/wc-api/jilt?token=abc123&hash=xyz
		// pretty permalinks disabled - https://example.com?wc-api=jilt&token=abc123&hash=xyz
		return esc_url_raw( add_query_arg( array( 'token' => rawurlencode( $data ), 'hash' => $hash ), $url ) );
	}


	/**
	 * Maybe apply the recovery coupon provided in the recovery URL.
	 *
	 * @since 1.1.0
	 */
	public function maybe_apply_cart_recovery_coupon() {

		if ( WC_Jilt_Session::is_pending_recovery() && ! empty( $_REQUEST['coupon'] ) ) {

			$coupon_code = wc_clean( rawurldecode( $_REQUEST['coupon'] ) );

			if ( WC()->cart && ! WC()->cart->has_discount( $coupon_code ) ) {

				// ensure the cart data is fully populated before validating coupons
				WC()->cart->calculate_totals();

				WC()->cart->add_discount( $coupon_code );
			}
		}
	}


	/**
	 * Adds the email usage notice as a description of the billing email field.
	 *
	 * This is super dirty, but descriptions have HTML escaped in WC < 3.4, so we replace the closing paragraph
	 *  tag directly instead. Change this to filter woocommerce_checkout_fields when WC 3.4 is required. {BR 2018-05-24}
	 *
	 * @internal
	 *
	 * @since 1.4.5
	 *
	 * @param string $field the field HTML
	 * @param string $key the field key
	 * @return string updated HTML
	 */
	public function add_email_usage_notice( $field, $key ) {

		if (    is_checkout() && 'billing_email' === $key
		     && wc_jilt()->get_integration()->show_email_usage_notice()
		     && null === WC_Jilt_Session::get_customer_email_collection_opt_out() ) {

			// find the trailing </p> tag to replace with our notice + </p>
			$pos     = strrpos( $field, '</p>' );
			$replace = '<br /><span class="wc-jilt-email-usage-notice">' . wc_jilt()->get_frontend()->get_email_usage_notice() . '</span></p>';

			if ( false !== $pos ) {
				$field = substr_replace( $field, $replace, $pos, strlen( '</p>' ) );
			}
		}

		return $field;
	}


	/**
	 * Add a consent request prompt at checkout.
	 *
	 * @internal
	 *
	 * @since 1.4.5
	 */
	public function output_privacy_consent_prompt() {

		if ( wc_jilt()->get_integration()->ask_consent_at_checkout() ) :

			$prompt = wp_kses_post( wc_jilt()->get_integration()->get_checkout_consent_prompt() );

			?>
			<div class="wc-jilt-checkout-consent-prompt">
				<p class="form-row">
					<label class="woocommerce-form__label woocommerce-form__label-for-checkbox checkbox">
						<input
							type="checkbox"
							id="wc-jilt-checkout-consent"
							class="woocommerce-form__input woocommerce-form__input-checkbox input-checkbox"
							name="wc_jilt_checkout_consent"
							value="yes"
							<?php checked( WC_Jilt_Session::get_customer_marketing_consent() ); ?>
						/>
						<span class="wc-jilt-checkout-consent-text"><?php echo $prompt; ?></span>
					</label>
					<input
						type="hidden"
						name="wc_jilt_checkout_consent_notice"
						value="<?php echo esc_attr( $prompt ); ?>"
					/>
				</p>
			</div>
			<?php

		endif;
	}


	/**
	 * Move the email field to the top of the checkout billing form.
	 *
	 * WC 3.0+ moved the email field to the bottom of the checkout form,
	 * which is less than ideal for capturing it. This method moves it
	 * to the top and makes it full-width.
	 *
	 * @internal
	 *
	 * @since 1.1.0
	 *
	 * @param array $fields
	 * @return array
	 */
	public function move_checkout_email_field( $fields ) {

		if ( isset( $fields['billing']['billing_email']['priority'] ) ) {

			$email_field = $fields['billing']['billing_email'];
			unset( $fields['billing']['billing_email'] );

			$email_field['priority']  = 5;
			$email_field['class']     = array( 'form-row-wide' );
			$email_field['autofocus'] = true;

			$fields['billing'] = array_merge( array( 'billing_email' => $email_field ), $fields['billing'] );

			// adjust layout of postcode/phone fields
			if ( isset( $fields['billing']['billing_postcode'], $fields['billing']['billing_phone'] ) ) {

				// note this method is hooked in at priority 1, so other customizations *should* be safe to add additional classes since we've gone first
				$fields['billing']['billing_postcode']['class'] = array( 'form-row-first', 'address-field' );
				$fields['billing']['billing_phone']['class']    = array( 'form-row-last' );
			}

			// remove autofocus from billing first name (set to email above)
			if ( isset( $fields['billing']['billing_first_name'] ) && ! empty( $fields['billing']['billing_first_name']['autofocus'] ) ) {
				$fields['billing']['billing_first_name']['autofocus'] = false;
			}
		}

		return $fields;
	}


	/**
	 * When a customer visits the checkout recovery URL, load their data from
	 * the session and pre-fill the checkout form
	 *
	 * Note that WC 3.0+ handles this automatically when using setters in
	 * the WC_Customer class.
	 *
	 * TODO: Remove this (and default_checkout_value()) when WC 3.0+ is
	 * required. {MR 2017-03-28}
	 *
	 * @since 1.0.0
	 */
	public function load_customer_data_from_session() {

		if ( ! WC_Jilt_Session::is_pending_recovery() ) {
			return;
		}

		// add default value filter hooks for checkout fields
		$address_fields = array_merge( WC()->countries->get_address_fields(), WC()->countries->get_address_fields( '', 'shipping_' ) );

		foreach ( $address_fields as $field => $data ) {
			add_filter( 'default_checkout_' . $field, array( $this, 'default_checkout_value' ), 10, 2 );
		}
	}


	/**
	 * Get default checkout value from session
	 *
	 * @since 1.0.0
	 * @param mixed $value
	 * @param string $input
	 * @return mixed
	 */
	public function default_checkout_value( $value, $input ) {

		$input  = str_replace( 'billing_', '', $input );
		$method = "get_$input";

		if ( ! $value ) {

			// if there's a getter method available on WC_Customer for this field, use it
			if ( is_callable( array( WC()->customer, $method ) ) ) {
				$value = WC()->customer->{$method}();
			}
			// otherwise, fall back to session
			else {
				$customer = WC()->session->get('customer');

				if ( $customer && isset( $customer[ $input ] ) ) {
					$value = $customer[ $input ];
				}
			}
		}
		return $value;
	}


	/**
	 * Maybe set the order note when there is a pending recovery
	 * with a previous value for the order note from the Jilt order.
	 *
	 * Note that unlike customer data (email, etc). this will not persist
	 * when/if the customer navigates away from the checkout page. We want to avoid
	 * a situation where the customer feels like they can't change the value of
	 * the order note field after it's been populated for them.
	 *
	 * @since 1.1.0
	 * @param string $value null
	 * @param string $input input field name
	 * @return null|string
	 */
	public function maybe_set_order_note( $value, $input ) {

		// target order comments input when a pending recovery order has an order note present
		if ( 'order_comments' !== $input || ! WC_Jilt_Session::is_pending_recovery() || ! $order_note = WC()->session->get( 'wc_jilt_order_note' ) ) {
			return $value;
		}

		unset( WC()->session->wc_jilt_order_note );

		return $order_note;
	}


	/**
	 * Sets the marketing consent attributes on the order.
	 *
	 * @since 1.5.7
	 *
	 * @param int $order_id order ID
	 */
	public function update_marketing_consent( $order_id ) {

		// if consent was offered at checkout
		if ( wc_jilt()->get_integration()->ask_consent_at_checkout() ) {

			$consent_accepted = isset( $_POST['wc_jilt_checkout_consent'] ) && 'yes' === $_POST['wc_jilt_checkout_consent'];

			// note that we don't persist the consent timestamp since we use the placed_at as a proxy for that
			update_post_meta( $order_id, '_wc_jilt_marketing_consent_accepted', $consent_accepted ? 'yes' : 'no' );
			update_post_meta( $order_id, '_wc_jilt_marketing_consent_notice', ! empty( $_POST['wc_jilt_checkout_consent_notice'] ) ? wc_clean( $_POST['wc_jilt_checkout_consent_notice'] ) : wc_jilt()->get_integration()->get_checkout_consent_prompt() );
			update_post_meta( $order_id, '_wc_jilt_marketing_consent_offered', 'yes' );

			WC_Jilt_Session::set_customer_marketing_consent( $consent_accepted );

		} else {

			update_post_meta( $order_id, '_wc_jilt_marketing_consent_offered', 'no' );
		}
	}


	/**
	 * This is called once the checkout has been processed and an order has been created.
	 * Does not necessarily mean that the order has been paid for.
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 *
	 * @param int $order_id order ID
	 */
	public function checkout_order_processed( $order_id ) {

		if ( ! wc_jilt()->get_integration()->is_jilt_connected() ) {
			return;
		}

		$this->update_marketing_consent( $order_id );

		// mark as pending recovery
		if ( WC_Jilt_Session::is_pending_recovery() ) {
			wc_jilt()->get_integration()->mark_order_as_pending_recovery( $order_id );
		}

		// update Jilt order details
		try {

			$order = new WC_Jilt_Order( $order_id );
			$cart_token = WC_Jilt_Session::get_cart_token();

			// set (generate if needed) cart token
			$order->set_jilt_cart_token( $cart_token );

			$this->get_api()->update_order( $cart_token, $this->get_jilt_order_data( $order ) );

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			wc_jilt()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );
		}
	}


	/**
	 * Handle updating the Jilt order when order details aren't available until
	 * *after* payment is received. Gateways like Amazon Payments Advanced and
	 * other on-site/iframed gateways act this way and otherwise result in a lot
	 * of placed orders with empty order data in Jilt.
	 *
	 * Important: this is a non-standard use of a filter used in an action context,
	 * but I preferred to use this over the woocommerce_thankyou action since that
	 * requires it to be present on the template. {MR 2016-12-06}
	 *
	 * @since 1.0.7
	 * @param array $result payment successful result
	 * @param int $order_id
	 * @return array
	 */
	public function maybe_update_jilt_order_on_successful_payment( $result, $order_id ) {

		if ( ! wc_jilt()->get_integration()->is_jilt_connected() ) {
			return $result;
		}

		if ( ! $cart_token = get_post_meta( $order_id, '_wc_jilt_cart_token', true ) ) {
			return $result;
		}

		try {

			$order = new WC_Jilt_Order( $order_id );
			$this->get_api()->update_order( $cart_token, $this->get_jilt_order_data( $order ) );

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			wc_jilt()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );
		}

		return $result;
	}


	/**
	 * Handles the payment complete action.
	 *
	 * @internal
	 *
	 * @since 1.4.2
	 *
	 * @param int $order_id
	 */
	public function payment_complete( $order_id ) {

		// ensure customer total spent and order count are updated now, not upon completed status
		// remove this if https://github.com/woocommerce/woocommerce/pull/23402 is merged {BR 2019-04-18}
		if ( wc_get_order( $order_id ) instanceof \WC_Order ) {
			wc_paying_customer( $order_id );
		}

		if ( WC_Jilt_Session::get_cart_token() ) {
			WC_Jilt_Session::unset_jilt_order_data();
		}
	}

	 /**
	 * Handles pay page completion.
	 *
	 * When a customer completes a pending recovery from the pay page (e.g an order
	 * originally placed with an off-site gateway then later completed via an
	 * on-site gateway), marks it as recovered.
	 *
	 * @since 1.0.7
	 *
	 * @param $order_id
	 */
	public function handle_pay_page_order_completion( $order_id ) {

		if ( wc_jilt()->get_integration()->is_order_pending_recovery( $order_id ) ) {

			$order = wc_get_order( $order_id );

			// mark as recovered, unless order is on-hold and recovering on-hold orders is turned on
			if ( 'on-hold' !== $order->get_status() || ! wc_jilt()->get_integration()->recover_held_orders() ) {
				wc_jilt()->get_integration()->mark_order_as_recovered( $order_id );
			}

			WC_Jilt_Session::unset_jilt_order_data();
		}
	}


	/**
	 * Mark an order as recovered by Jilt
	 *
	 * @since 1.0.1
	 * @deprecated 1.5.0
	 *
	 * @param int|string $order_id order ID
	 */
	public function mark_order_as_recovered( $order_id ) {

		Framework\SV_WC_Plugin_Compatibility::wc_deprecated_function( __METHOD__, '1.5.0', get_class( wc_jilt()->get_integration() ) . '::mark_order_as_recovered' );

		wc_jilt()->get_integration()->mark_order_as_recovered( $order_id );
	}


	/**
	 * Gets the order data.
	 *
	 * @since 1.2.0
	 *
	 * @param $order \WC_Jilt_Order the order object
	 * @return array associative array of order data formatted for Jilt
	 */
	private function get_jilt_order_data( $order ) {

		$data = $order->get_jilt_order_data();
		$data['client_session'] = WC_Jilt_Session::get_client_session();

		return $data;
	}


	/**
	 * Helper method to improve the readability of methods calling the API
	 *
	 * @since 1.0.0
	 * @return \WC_Jilt_API instance
	 */
	private function get_api() {
		return wc_jilt()->get_integration()->get_api();
	}


	/**
	 * Return the WC API URL for handling Jilt recovery links by accounting
	 * for whether pretty permalinks are enabled or not.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	private static function get_jilt_wc_api_url() {

		$scheme = wc_site_is_https() ? 'https' : 'http';

		return get_option( 'permalink_structure' )
			? get_home_url( null, 'wc-api/jilt', $scheme )
			: add_query_arg( 'wc-api', 'jilt', get_home_url( null, null, $scheme ) );
	}


}
