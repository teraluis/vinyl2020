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
 * Cart class
 *
 * Handles cart interactions
 *
 * @since 1.0.0
 */
class WC_Jilt_Cart_Handler {


	/** The cipher method name to use to encrypt the cart data */
	const CIPHER_METHOD = 'AES-128-CBC';

	/** The HMAC hash algorithm to use to sign the encrypted cart data */
	const HMAC_ALGO = 'sha256';


	/**
	 * Setup class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'woocommerce_cart_loaded_from_session', array( $this, 'handle_persistent_cart' ), 5 );

		add_action( 'wc_ajax_jilt_get_cart_data', array( $this, 'ajax_get_cart_data' ) );

		# add_action( 'woocommerce_cart_updated', array( $this, 'cart_updated' ) ); // TODO: determine if we can still use this anywhere {CW 2018-03-22}

		add_action( 'wp_login', array( $this, 'cart_updated' ) );

		// add Jilt data to WC cart fragments
		add_filter( 'woocommerce_add_to_cart_fragments', array( $this, 'add_cart_fragments' ) );
		add_action( 'wp_footer',                         array( $this, 'render_jilt_tracking_div' ) );
	}


	/**
	 * Gets the cart data for via AJAX.
	 *
	 * @internal
	 *
	 * @since 1.4.3
	 */
	public function ajax_get_cart_data() {

		$cart_data      = $this->get_cart_data();
		$cart_data_json = wp_json_encode( $cart_data );

		$response_data = array(
			'cart'       => $cart_data,
			'cart_token' => $this->get_cart_token(),
			'cart_hash'  => $this->get_cart_hash( $cart_data )
		);

		// log plaintext response
		$this->log_get_cart_data_request( $response_data );

		if ( $this->should_encrypt_cart_data() ) {
			$response_data['cart'] = $this->encrypt_cart_data( $cart_data_json );
		}

		// if the cart is empty, clear the unique cart token
		if ( WC()->cart->is_empty() ) {
			WC_Jilt_Session::unset_jilt_order_data();
		}

		wp_send_json_success( $response_data );
	}


	/**
	 * Enqueues frontend scripts and styles.
	 *
	 * TODO remove this method by version 1.6 {FN 2018-05-22}
	 *
	 * @internal
	 *
	 * @since 1.0.0
	 * @deprecated since 1.4.5
	 */
	public function enqueue_scripts() {

		_deprecated_function( 'WC_Jilt_Cart_Handler::enqueue_scripts()', '1.4.5', 'WC_Jilt_Frontend::enqueue_scripts_styles()' );

		wc_jilt()->get_frontend()->enqueue_scripts_styles();
	}


	/**
	 * Handle loading/setting Jilt data for the persistent cart. This is important
	 * for two key situations:
	 *
	 * 1) when a user logs in and their persistent cart is loaded, the cart token
	 * and Jilt order ID is set in the session. This most commonly occurs when
	 * the customer visits the recovery URL and is logged in and their persistent
	 * cart loaded. If not done properly, this can result in duplicate Jilt orders.
	 * Note this is only done if the cart token exists in user meta AND there is
	 * no existing cart token in the session.
	 *
	 * 2) when a guest user (with an existing cart/session) logs in (usually on
	 * the checkout page), the cart token and Jilt order ID is saved to user meta,
	 * at roughly the same time as the persistent cart. This ensures that if the user
	 * leaves and logs back in days later, their persistent cart will be loaded along
	 * with the correct Jilt order that it was originally associated with. Note this
	 * is done only if the cart token exists in the session but NOT in user meta.
	 *
	 * This method is hooked into woocommerce_cart_loaded_from_session,
	 * which runs prior to the woocommerce_cart_updated action, which is where
	 * we hook in below to handle creating/updating Jilt orders.
	 *
	 * @since 1.0.0
	 */
	public function handle_persistent_cart() {

		// bail for guest users, when the cart is empty, or when doing a WP cron request
		if ( ! is_user_logged_in() || WC()->cart->is_empty() || defined( 'DOING_CRON' ) ) {
			return;
		}

		$user_id    = get_current_user_id();
		$cart_token = get_user_meta( $user_id, '_wc_jilt_cart_token', true );

		if ( $cart_token && ! WC_Jilt_Session::get_cart_token() ) {

			// for a logged in user with a persistent cart, set the cart token to the session
			WC_Jilt_Session::set_jilt_order_data( $cart_token );

		} elseif ( ! $cart_token && WC_Jilt_Session::get_cart_token() ) {

			// when a guest user with an existing cart logs in, save the cart token to user meta
			update_user_meta( $user_id, '_wc_jilt_cart_token', WC_Jilt_Session::get_cart_token() );
		}

		// persist order notes into the session during a pending recovery
		if ( WC_Jilt_Session::is_pending_recovery() && $order_note = get_user_meta( $user_id, '_wc_jilt_order_note', true ) ) {
			WC()->session->set( 'wc_jilt_order_note', $order_note );
			delete_user_meta( $user_id, '_wc_jilt_order_note' );
		}
	}


	/**
	 * Encrypts the given cart data string.
	 *
	 * @since 1.5.1
	 *
	 * @param string $cart_data the cart data in json encoded string
	 * @return string $cart_data encrypted and base64 encoded
	 * @throws Framework\SV_WC_Plugin_Exception if the openssl extension is not loaded
	 */
	private function encrypt_cart_data( $cart_data ) {

		if ( ! extension_loaded( 'openssl' ) ) {
			throw new Framework\SV_WC_Plugin_Exception( 'Cannot encrypt cart data - the OpenSSL extension is not loaded' );
		}

		$key = substr( wc_jilt()->get_integration()->get_client_secret(), 0, 16 );

		$ivlen          = openssl_cipher_iv_length( self::CIPHER_METHOD );
		$iv             = openssl_random_pseudo_bytes( $ivlen );
		$ciphertext_raw = openssl_encrypt( $cart_data, self::CIPHER_METHOD, $key, OPENSSL_RAW_DATA, $iv );
		$hmac           = hash_hmac( self::HMAC_ALGO, $ciphertext_raw, $key, true );

		return base64_encode( $iv . $hmac . $ciphertext_raw );
	}


	/**
	 * Returns whether cart data sent to the browser should be encrypted or not.
	 *
	 * @since 1.5.1
	 *
	 * @return boolean
	 */
	private function should_encrypt_cart_data() {

		$should_encrypt = extension_loaded( 'openssl' );

		if ( ! wc_jilt()->get_integration()->get_client_secret() ) {
			$should_encrypt = false;
		}

		if ( ! in_array( self::CIPHER_METHOD, openssl_get_cipher_methods(), true ) || ! in_array( self::HMAC_ALGO, hash_algos(), true ) ) {
			$should_encrypt = false;
		}

		/**
		 * Filters whether or not cart data sent to the browser should be encrypted.
		 *
		 * @since 1.5.1
		 *
		 * @param bool $should_encrypt
		 */
		return apply_filters( 'wc_jilt_should_encrypt_cart_data', $should_encrypt );
	}


	/** Event handlers ******************************************************/


	/**
	 * Create or update a Jilt order when cart is updated
	 *
	 * This is called at the bottom of WC_Checkout::set_session(), after the
	 * session and optional persistent cart are set.
	 *
	 * This will be called:
	 *
	 * + When a user signs into their account from the Checkout page (2x, once for /checkout/ and again for /wc-ajax/update_order_review/)
	 * + When a product is added to cart via ajax (1x)
	 * + When a product is added to cart via form submit (1x)
	 * + When a product is removed from the cart via 'x' link in widget (1x)
	 * + When a product is removed from the cart via 'x' link in cart (2x, once for /cart/?remove_item=6974ce5ac660610b44d9b9fed0ff9548 and then /cart/?removed_item=1)
	 * + When the cart page is loaded (1x)
	 * + When the checkout page is loaded (1x)
	 * + When the checkout form is submitted (I think, /wp-admin/admin-ajax.php?action=woocommerce_checkout)
	 * + When the address form on the checkout page is refreshed (via /wc-ajax/update_order_review/)
	 * + When a user logs into WordPress, either from the /wp-login.php page or on the WooCommerce my account page
	 *
	 * This will not be called:
	 *
	 * + When a customer creates an account from the checkout page
	 * + On the pay page
	 *
	 * @since 1.0.0
	 */
	public function cart_updated() {

		if ( ! wc_jilt()->get_integration()->is_jilt_connected() ) {
			return;
		}

		// prevent duplicate updates when changing item quantities in the cart
		if ( isset( $_POST['cart'] ) ) {
			return;
		}

		$cart_token = WC_Jilt_Session::get_cart_token();

		if ( $cart_token ) {

			try {

				// update the existing Jilt order
				$this->get_api()->update_order( $cart_token, $this->get_cart_data() );

			} catch ( Framework\SV_WC_API_Exception $exception ) {

				// clear session so a new Jilt order can be created
				if ( 404 == $exception->getCode() ) {
					WC_Jilt_Session::unset_jilt_order_data();
					// try to create the order below
					$cart_token = null;
				}

				wc_jilt()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );
			}

		}

		if ( ! $cart_token && ! WC()->cart->is_empty() ) {

			try {

				// create a new Jilt order
				$this->get_api()->create_order( $this->get_cart_data() );

			} catch ( Framework\SV_WC_API_Exception $exception ) {

				wc_jilt()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );
			}
		}
	}


	/**
	 * When a user intentionally empties their cart, delete the associated Jilt
	 * order
	 *
	 * @since 1.0.0
	 */
	public function cart_emptied() {

		if ( ! wc_jilt()->get_integration()->is_jilt_connected() ) {
			return;
		}

		$cart_token = WC_Jilt_Session::get_cart_token();

		if ( ! $cart_token ) {
			return;
		}

		WC_Jilt_Session::unset_jilt_order_data();

		try {

			// TODO: need to make sure an order isn't deleted after being placed
			$this->get_api()->delete_order( $cart_token );

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			wc_jilt()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );
		}
	}


	/**
	 * Gets the cart data for updating/creating a Jilt order via the API.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	protected function get_cart_data() {

		$cart_token = $this->get_cart_token();
		$params     = array_merge( array(
			'total_price'       => $this->amount_to_int( $this->get_cart_total() ),
			'subtotal_price'    => $this->get_subtotal_price(),
			'total_tax'         => $this->amount_to_int( WC()->cart->tax_total + WC()->cart->shipping_tax_total ),
			'total_discounts'   => $this->amount_to_int( WC()->cart->discount_cart ),
			'total_shipping'    => $this->amount_to_int( WC()->cart->shipping_total ),
			'requires_shipping' => WC()->cart->needs_shipping(),
			'currency'          => get_woocommerce_currency(),
			'checkout_url'      => WC_Jilt_Checkout_Handler::get_checkout_recovery_url( $cart_token ),
			'line_items'        => $this->get_cart_product_line_items(),
			'fee_items'         => $this->get_cart_fee_line_items(),
			'client_details'    => $this->get_client_details(),
			'client_session'    => WC_Jilt_Session::get_client_session(),
			'cart_token'        => $cart_token,
		), $this->get_customer_data() );

		// anonymize IP address unless customer has seen the opt out and taken no action
		if ( false !== WC_Jilt_Session::get_customer_email_collection_opt_out() ) {

			$ip_str  = isset( $params['client_details']['browser_ip'] ) ? $params['client_details']['browser_ip'] : '';
			$ip_type = is_string( $ip_str ) && '' !== $ip_str ? strlen( @inet_pton( $ip_str ) ) : null;
			$ip_mask = array( '4' => '255.255.255.0', '16' => 'ffff:ffff:ffff:ffff:0000:0000:0000:0000' );
			$anon_ip = null;

			if ( $ip_type && array_key_exists( $ip_type, $ip_mask ) ) {
				$anon_ip = @inet_ntop( @inet_pton( $ip_str ) & @inet_pton( $ip_mask[ (string) $ip_type ] ) );
			}

			if ( is_string( $anon_ip ) && '' !== $anon_ip ) {
				$params['client_details']['browser_ip'] = $anon_ip;
			} else {
				unset( $params['client_details']['browser_ip'] );
			}
		}

		/**
		 * Filters the cart data used for creating or updating a Jilt order via the API.
		 *
		 * @since 1.0.0
		 *
		 * @param array $params associative array
		 * @param \WC_Jilt_Cart_Handler $cart_handler instance
		 */
		return (array) apply_filters( 'wc_jilt_order_cart_params', $params, $this );
	}


	/**
	 * Returns a hash for the given cart data, or gets the cart data if none is given.
	 *
	 * @since 1.5.1
	 *
	 * @param array|null $cart_data cart data or null if retrieving cart
	 * @return string cart hash
	 */
	public function get_cart_hash( $cart_data = null ) {

		$cart_data = $cart_data ? $cart_data : $this->get_cart_data();

		return md5( wp_json_encode( $cart_data ) );
	}


	/**
	 * Returns the cart token.
	 *
	 * Generates the cart token if necessary and stores it in the session.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_cart_token() {

		$cart_token = WC_Jilt_Session::get_cart_token();

		if ( ! $cart_token ) {

			$cart_token = WC_Jilt_Order::generate_cart_token();

			WC_Jilt_Session::set_jilt_order_data( $cart_token );
		}

		return $cart_token;
	}


	/**
	 * Adds Jilt cart data to the WC fragments that are automatically refreshed
	 * by WooCommerce on the cart and checkout pages, and for the cart widget.
	 *
	 * @internal
	 *
	 * @since 1.5.6
	 *
	 * @param array $fragments $jquery selector => $content
	 * @return array
	 */
	public function add_cart_fragments( $fragments ) {

		$selector  = 'div#' . $this->get_tracking_element_id();
		$cart_data = $this->get_cart_data();
		$hash      = $this->get_cart_hash( $cart_data );

		if ( $this->should_encrypt_cart_data() ) {
			$cart_data = $this->encrypt_cart_data( wp_json_encode( $cart_data ) );
		}

		$fragments[ $selector ] = $this->get_jilt_tracking_div( $this->get_cart_token(), $hash, $cart_data );

		// if the cart is empty, clear the unique cart token
		if ( WC()->cart->is_empty() ) {
			WC_Jilt_Session::unset_jilt_order_data();
		}

		return $fragments;
	}


	/**
	 * Gets the tracking element ID.
	 *
	 * @since 1.5.6
	 *
	 * @return string
	 */
	public function get_tracking_element_id() {

		/**
		 * Filters the jilt tracking element ID.
		 *
		 * @since 1.5.6
		 *
		 * @param string tracking element ID
		 * @param \WC_Jilt_Cart_Handler instance of the cart handler
		 */
		return apply_filters( 'wc_jilt_tracking_element_id', 'jilt-cart-data', $this );
	}


	/**
	 * Gets the Jilt tracking div to add to the page.
	 *
	 * @internal
	 *
	 * @since 1.5.6
	 *
	 * @param string $cart_token unique cart token
	 * @param string $hash cart hash
	 * @param array|string $cart_data cart data to add to the data
	 * @return string
	 */
	public function get_jilt_tracking_div( $cart_token = '', $hash = '', $cart_data = '' ) {

		$data = array(
			'cart_token' => $cart_token,
			'hash'       => $hash,
			'cart_data'  => $cart_data,
		);

		return sprintf(
			'<div id="%1$s" style="display: none !important;">%2$s</div>',
			esc_attr( $this->get_tracking_element_id() ),
			esc_html( json_encode( $data ) )
		);
	}


	/**
	 * Outputs the Jilt tracking div.
	 *
	 * @since 1.5.6
	 */
	public function render_jilt_tracking_div() {

		// can't rely on an initial hash value as this will likely be cached
		echo $this->get_jilt_tracking_div();
	}


	/**
	 * Get the customer data (email/ID, billing / shipping address) used when
	 * creating/updating an order in Jilt
	 *
	 * @since 1.0.6
	 * @return array
	 */
	protected function get_customer_data() {

		$params = array(
			'billing_address'  => array(),
			'shipping_address' => array(),
		);

		// set the billing/shipping fields from the WC_Customer object
		$params = $this->set_address_fields_from_customer( $params );

		$user = is_user_logged_in() ? get_user_by( 'id', get_current_user_id() ) : null;

		// set customer data from the logged in user, if available. note that this
		// info can be different than the billing/shipping info.
		if ( $user instanceof \WP_User ) {

			$params['customer'] = array(
				'email'                   => $user->user_email,
				'first_name'              => $user->first_name,
				'last_name'               => $user->last_name,
				'customer_id'             => $user->ID,
				'admin_url'               => esc_url_raw( add_query_arg( array( 'user_id' => $user->ID ), self_admin_url( 'user-edit.php' ) ) )
			);

		} elseif ( $this->has_customer_data_from_js_api() ) {

			// set from WC_Customer, if available. currently this should only occur
			// if custom code is using the public JS API to set this data.
			$params['customer'] = array(
				'email'      => Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? WC()->customer->get_billing_email() : WC()->customer->email,
				'first_name' => Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? WC()->customer->get_billing_first_name() : WC()->customer->first_name,
				'last_name'  => Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? WC()->customer->get_billing_last_name() : WC()->customer->last_name,
			);
		}

		return $params;
	}


	/**
	 * Set address fields (billing/shipping) from the customer object in the
	 * session.
	 *
	 * @since 1.1.0
	 *
	 * @param array $params
	 * @return array
	 */
	private function set_address_fields_from_customer( $params ) {

		foreach ( WC_Jilt_Order::get_jilt_order_address_mapping() as $wc_field => $jilt_field ) {

			$billing_method  = "get_billing_{$wc_field}";
			$shipping_method = "get_shipping_{$wc_field}";

			$billing_value  = WC()->customer->$billing_method();
			$shipping_value = is_callable( array( WC()->customer, $shipping_method ) ) ? WC()->customer->$shipping_method() : null;

			$params['billing_address'][ $jilt_field ]  = ( $billing_value !== '' ) ? $billing_value : null;
			$params['shipping_address'][ $jilt_field ] = ( $shipping_value !== '' ) ? $shipping_value : null;
		}

		return $params;
	}


	/**
	 * Return true if customer data was set from the JS API.
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	private function has_customer_data_from_js_api() {

		return WC()->customer->get_first_name() || WC()->customer->get_last_name() || WC()->customer->get_billing_email();
	}


	/**
	 * Get the cart subtotal, in pennies
	 *
	 * @since 1.3.0
	 * @return int the subtotal price, maybe inclusive of taxes, in pennies
	 */
	public function get_subtotal_price() {

		if ( 'excl' === get_option( 'woocommerce_tax_display_cart' ) ) {
			$subtotal = WC()->cart->subtotal_ex_tax;
		} else {
			$subtotal = WC()->cart->subtotal;
		}

		return $this->amount_to_int( $subtotal );
	}


	/**
	 * Map WC cart items to Jilt line items
	 *
	 * @since 1.0.0
	 * @return array Mapped line items
	 */
	private function get_cart_product_line_items() {

		$line_items = array();

		// products
		foreach ( WC()->cart->get_cart() as $item_key => $item ) {

			$product = $item['data'];

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			// prepare main line item params
			$line_item = array(
				'title'      => html_entity_decode( $product->get_title() ),
				'product_id' => $item['product_id'],
				'quantity'   => $item['quantity'],
				'sku'        => $product->get_sku(),
				'url'        => get_the_permalink( $item['product_id'] ),
				'image_url'  => WC_Jilt_Product::get_product_image_url( $product ),
				'key'        => $item_key,
				'price'      => $this->get_item_price( $product ),
				'tax_lines'  => $this->get_tax_lines( $item ),
				'properties' => array(),
			);

			// add variation data
			if ( ! empty( $item['variation_id'] ) ) {

				$line_item['variant_id'] = $item['variation_id'];
				$line_item['variation']  = WC_Jilt_Product::get_variation_data( $item );
			}

			/**
			 * Filter cart item params used for creating/updating a Jilt order
			 * via the API
			 *
			 * @since 1.0.0
			 *
			 * @param array $line_item Jilt line item data
			 * @param array $item WC line item data
			 * @param string $item_key WC cart key for item
			 */
			$line_items[] = apply_filters( 'wc_jilt_order_cart_item_params', $line_item, $item, $item_key );
		}

		return $line_items;
	}


	/**
	 * Get the tax lines, if any, for this item
	 *
	 * @since 1.3.0
	 *
	 * @param array Item associative array
	 * @return array of tax lines, e.g. [ [ 'amount' => 135 ] ]
	 */
	private function get_tax_lines( $item ) {

		// a simplistic implementation for now, but if WC identifies the actual
		// taxes a la Shopify, we can make this more comprehensive
		return array(
			array(
				'amount' => isset( $item['line_tax'] ) ? $this->amount_to_int( $item['line_tax'] ) : 0,
			),
		);
	}


	/**
	 * Map WC cart fee line items to Jilt fee items
	 *
	 * @since 1.1.0
	 * @return array Mapped fee items
	 */
	private function get_cart_fee_line_items() {

		$fee_items = array();

		// fees
		if ( $fees = WC()->cart->get_fees() ) {
			foreach ( $fees as $fee ) {

				$fee_item = array(
					'title'  => html_entity_decode( $fee->name ),
					'key'    => $fee->id,
					'amount' => $this->amount_to_int( $fee->amount ),
				);

				/**
				 * Filter cart fee item params used for creating/updating a Jilt order
				 * via the API
				 *
				 * @since 1.1.0
				 *
				 * @param array $fee_item Jilt fee item data
				 * @param \stdClass $fee WC fee object
				 */
				$fee_items[] = apply_filters( 'wc_jilt_order_cart_fee_params', $fee_item, $fee );
			}
		}

		return $fee_items;
	}


	/**
	 * Get any client details for the cart
	 *
	 * @since 1.2.0
	 * @return array associative array of client details (if available)
	 */
	private function get_client_details() {

		$client_details = array();

		if ( $browser_ip = WC_Geolocation::get_ip_address() ) {
			$client_details['browser_ip'] = $browser_ip;
		}
		if ( ! empty( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$client_details['accept_language'] = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
		}
		if ( ! empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
			$client_details['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
		}

		return $client_details;
	}


	/**
	 * Returns the cart total.
	 *
	 * WooCommerce does not set the cart total unless on the cart or checkout pages.
	 * @see \WC_Cart::calculate_totals() so on other pages (or for AJAX requests) it's calculated manually
	 *
	 * @since 1.0.6
	 *
	 * @return float
	 */
	protected function get_cart_total() {

		// since Jilt moved to Storefront JS the request will be via AJAX and these conditions will be skipped, but left here for legacy & consistency
		if ( is_checkout() || is_cart() ) {

			// on cart/checkout, total is already calculated by WooCommerce itself
			$total = WC()->cart->total;

		} else {

			// on product pages, other pages or while doing AJAX, we need to calculate the total ourselves
			WC()->cart->calculate_totals();

			$total = WC()->cart->total;
		}

		return is_numeric( $total ) ? (float) $total : (float) 0;
	}


	/**
	 * Get the product price, either inclusive or exclusive of tax, depending
	 * on the WooCommerce "display cart prices inclusive/exclusive of taxes"
	 * setting.
	 *
	 * Note: this is adapted from WC_Cart::get_product_price()
	 *
	 * @since 1.3.0
	 *
	 * @param WC_Product $product the product
	 * @return int the product price in pennies
	 */
	private function get_item_price( $product ) {

		if ( 'excl' == get_option( 'woocommerce_tax_display_cart' ) ) {
			$price = Framework\SV_WC_Product_Compatibility::wc_get_price_excluding_tax( $product );
		} else {
			$price = Framework\SV_WC_Product_Compatibility::wc_get_price_including_tax( $product );
		}

		return $this->amount_to_int( $price );
	}


	/**
	 * Convert a price/total to the lowest currency unit (e.g. cents)
	 *
	 * @since 1.0.6
	 *
	 * @param string|float $number
	 *
	 * @return int
	 */
	private function amount_to_int( $number ) {

		if ( is_string( $number ) ) {
			$number = (float) $number;
		}

		return round( $number * 100, 0 );
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
	 * Log the get cart data request/response
	 *
	 * @since 1.4.3
	 *
	 * @param array $cart_data
	 */
	private function log_get_cart_data_request( $cart_data ) {

		// pieces of the request that we care about
		$request = array(
			'method'     => $_SERVER['REQUEST_METHOD'],
			'uri'        => $this->get_client_request_url(),
			'user-agent' => $_SERVER['HTTP_USER_AGENT'],
			'remote-ip'  => $_SERVER['REMOTE_ADDR'],
			'headers'    => array(
				'accept'  => $_SERVER['HTTP_ACCEPT'],
				'referer' => $_SERVER['HTTP_REFERER'],
			),
		);

		// the response format used by wp_send_json_success()
		$response_body = array(
			'success' => true,
			'data'    => $cart_data
		);

		$response = array(
			'code'    => 200,
			'message' => 'OK',
			'headers' => array(
				'content-type' => 'application/json; charset=' . get_option( 'blog_charset' ),
			),
			'body' => json_encode( $response_body, JSON_PRETTY_PRINT),
		);

		wc_jilt()->get_logger()->log_api_request( $request, $response );
	}


	/**
	 * Get the full client request url, e.g. https://example.com/checkout/
	 *
	 * @since 1.4.3
	 * @return string the request url
	 */
	private function get_client_request_url() {

		if ( isset( $_SERVER['REQUEST_SCHEME'] ) ) {
			$scheme = $_SERVER['REQUEST_SCHEME'];
		} elseif ( isset( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		return "{$scheme}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	}


}
