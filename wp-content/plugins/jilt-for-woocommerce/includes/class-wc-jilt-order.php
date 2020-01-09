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
 * @package   WC-Jilt/Order
 * @author    Jilt
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * Jilt Order Class
 *
 * Extends the WooCommerce Order class to add additional information and
 * functionality specific to Jilt
 *
 * Note: this class does not represent an order stored in the Jilt app
 *
 * @since 1.0.0
 * @extends \WC_Order
 */
class WC_Jilt_Order extends WC_Order {


	/**
	 * Generates a UUIDv4 cart token.
	 *
	 * @since 1.4.5
	 *
	 * @see https://stackoverflow.com/a/15875555
	 *
	 * @return string
	 */
	public static function generate_cart_token() {

		try {
			$data = random_bytes( 16 );

			$data[6] = chr( ord( $data[6] ) & 0x0f | 0x40 ); // set version to 0100
			$data[8] = chr( ord( $data[8] ) & 0x3f | 0x80 ); // set bits 6-7 to 10

			return vsprintf( '%s%s-%s-%s-%s-%s%s%s', str_split( bin2hex( $data ), 4 ) );

		} catch ( Exception $e ) {

			// fall back to mt_rand if random_bytes is unavailable
			return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',

				// 32 bits for "time_low"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),

				// 16 bits for "time_mid"
				mt_rand( 0, 0xffff ),

				// 16 bits for "time_hi_and_version",
				// four most significant bits holds version number 4
				mt_rand( 0, 0x0fff ) | 0x4000,

				// 16 bits, 8 bits for "clk_seq_hi_res",
				// 8 bits for "clk_seq_low",
				// two most significant bits holds zero and one for variant DCE1.1
				mt_rand( 0, 0x3fff ) | 0x8000,

				// 48 bits for "node"
				mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
			);
		}

	}


	/**
	 * Returns an order by Jilt cart token.
	 *
	 * @since 1.4.0
	 *
	 * @param string $cart_token the cart token
	 * @return WC_Jilt_Order the identified order, or null if not found
	 */
	public static function find_by_cart_token( $cart_token ) {

		$query_params = array(
			'post_type'   => 'shop_order',
			'post_status' => 'any',
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'key'     => '_wc_jilt_cart_token',
					'value'   => $cart_token,
					'compare' => '=',
				)
			)
		);

		$result = new WP_Query( $query_params );

		if ( count( $result->posts ) > 0 ) {
			$post_id = $result->posts[0];
		} else {
			return null;
		}

		try {
			$order = new self( $post_id );

			if ( ! Framework\SV_WC_Order_Compatibility::get_prop( $order, 'id' ) ) {
				return null;
			}

			return $order;
		} catch ( Exception $e ) {
			return null;
		}
	}


	/**
	 * Get an order by Jilt order id
	 *
	 * @since 1.2.0
	 * @param int $jilt_order_id the remote Jilt order identifier
	 * @return WC_Jilt_Order the identified order, or null if not found
	 */
	public static function find_by_jilt_order_id( $jilt_order_id ) {

		$query_params = array(
			'post_type'   => 'shop_order',
			'post_status' => 'any',
			'fields'      => 'ids',
			'meta_query'  => array(
				array(
					'key'     => '_wc_jilt_order_id',
					'value'   => $jilt_order_id,
					'compare' => '=',
				)
			)
		);

		$result = new WP_Query( $query_params );

		if ( count( $result->posts ) > 0 ) {
			$post_id = $result->posts[0];
		} else {
			return null;
		}

		try {
			$order = new self( $post_id );

			if ( ! Framework\SV_WC_Order_Compatibility::get_prop( $order, 'id' ) ) {
				return null;
			}

			return $order;
		} catch ( Exception $e ) {
			return null;
		}
	}


	/**
	 * Get the order data for updating a Jilt order via the API
	 *
	 * @since 1.0.0
	 * @return array
	 */
	public function get_jilt_order_data() {

		$params = array(
			'name'               => $this->get_order_number(),
			'order_id'           => $this->get_id(),
			'admin_url'          => $this->get_order_edit_url(),
			'status'             => $this->get_status(),
			'financial_status'   => $this->get_financial_status(),
			'fulfillment_status' => $this->get_fulfillment_status(),
			'total_price'        => $this->amount_to_int( $this->get_total() ),
			'subtotal_price'     => $this->get_subtotal_price(),
			'total_tax'          => $this->amount_to_int( $this->get_total_tax() ),
			'total_discounts'    => $this->amount_to_int( $this->get_total_discount() ),
			'total_shipping'     => $this->amount_to_int( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? $this->get_shipping_total() : $this->get_total_shipping() ),
			'requires_shipping'  => $this->needs_shipping(),
			'currency'           => Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? $this->get_currency() : $this->get_order_currency(),
			'line_items'         => $this->get_product_line_items(),
			'fee_items'          => $this->get_fee_line_items(),
			'cart_token'         => $this->get_jilt_cart_token(),
			'test'               => $this->is_test(),
			'client_details'    => array(
				'browser_ip' => Framework\SV_WC_Order_Compatibility::get_prop( $this, 'customer_ip_address' ),
				'user_agent' => Framework\SV_WC_Order_Compatibility::get_prop( $this, 'customer_user_agent' ),
			),
			'customer'           => array(
				'email'      => Framework\SV_WC_Order_Compatibility::get_prop( $this, 'billing_email' ),
				'first_name' => Framework\SV_WC_Order_Compatibility::get_prop( $this, 'billing_first_name' ),
				'last_name'  => Framework\SV_WC_Order_Compatibility::get_prop( $this, 'billing_last_name' ),
				'phone'      => Framework\SV_WC_Order_Compatibility::get_prop( $this, 'billing_phone' ),
			),
			'billing_address'    => $this->map_address_to_jilt( 'billing' ),
			'shipping_address'   => $this->map_address_to_jilt( 'shipping' ),
		);

		if ( $this->get_jilt_placed_at() ) {
			$params['placed_at'] = $this->get_jilt_placed_at();
		}
		if ( $this->get_jilt_cancelled_at() ) {
			$params['cancelled_at'] = $this->get_jilt_cancelled_at();
		}

		if ( $user_id = $this->get_user_id() ) {
			$customer = new WC_Customer( $user_id );
			$params['customer']['customer_id'] = $user_id;

			// it's possible to have order billing contact info, but not user info,
			// so use the user info if set, otherwise keep the billing info as fallback
			if ( $customer->get_email() ) {
				$params['customer']['email'] = $customer->get_email();
			}
			if ( $customer->get_first_name() ) {
				$params['customer']['first_name'] = $customer->get_first_name();
			}
			if ( $customer->get_last_name() ) {
				$params['customer']['last_name'] = $customer->get_last_name();
			}
			if ( $customer->get_billing_phone() ) {
				$params['customer']['phone'] = $customer->get_billing_phone();
			}
			$params['customer']['admin_url'] = esc_url_raw( add_query_arg( array( 'user_id' => $user_id ), self_admin_url( 'user-edit.php' ) ) );
		}

		// add the marketing consent data if it was accepted
		if ( $this->marketing_consent_accepted() ) {

			$params['customer']['accepts_marketing']       = true;

			if ( ! empty( $params['client_details']['browser_ip'] ) ) {
				$params['customer']['consent_ip_address'] = $params['client_details']['browser_ip'];
			}

			$params['customer']['consent_context']   = 'checkout';
			$params['customer']['consent_timestamp'] = $this->get_jilt_placed_at();
			$params['customer']['consent_notice']    = $this->get_marketing_consent_notice();

		// let Jilt know if it was offered but not accepted
		} elseif ( $this->marketing_consent_offered() ) {

			$params['customer']['accepts_marketing'] = false;
		}

		if ( $customer_note = Framework\SV_WC_Order_Compatibility::get_prop( $this, 'customer_note' ) ) {
			$params['note'] = $customer_note;
		}

		if ( is_callable( 'WC_Jilt_Checkout_Handler::get_checkout_recovery_url' ) ) {
			$params['checkout_url'] = WC_Jilt_Checkout_Handler::get_checkout_recovery_url( $this->get_jilt_cart_token() );
		}

		/**
		 * Filter the order data used for updating a Jilt order
		 * via the API
		 *
		 * @since 1.0.0
		 * @param array $params
		 * @param \WC_Jilt_Order $order instance
		 */
		return apply_filters( 'wc_jilt_order_params', $params, $this );
	}


	/**
	 * Get the datetime at which this order was placed, or null
	 *
	 * @since 1.2.0
	 * @return string|null placed_at datetime in iso8601 format or null
	 */
	public function get_jilt_placed_at() {

		$placed_at = get_post_meta( $this->get_id(), '_wc_jilt_placed_at', true );
		return $placed_at ? date( 'Y-m-d\TH:i:s\Z', $placed_at ) : null;
	}


	/**
	 * Get the datetime at which this order was cancelled, or null
	 *
	 * @since 1.2.0
	 * @return string|null cancelled_at datetime in iso8601 format or null
	 */
	public function get_jilt_cancelled_at() {

		$cancelled_at = get_post_meta( $this->get_id(), '_wc_jilt_cancelled_at', true );
		return $cancelled_at ? date( 'Y-m-d\TH:i:s\Z', $cancelled_at ) : null;
	}


	/**
	 * Set a unique cart token for this order.
	 *
	 * @since 1.5.0
	 *
	 * @param string $cart_token Optional cart token to set; if not provided
	 *        one will be generated
	 * @return string the cart token
	 */
	public function set_jilt_cart_token( $cart_token = null ) {

		if ( null === $cart_token ) {
			$cart_token = self::generate_cart_token();
		}

		update_post_meta( $this->get_id(), '_wc_jilt_cart_token', $cart_token );
	}


	/**
	 * Get the Jilt cart token for an order.
	 *
	 * @since 1.1.0
	 * @return string
	 */
	public function get_jilt_cart_token() {

		return get_post_meta( $this->get_id(), '_wc_jilt_cart_token', true );
	}


	/**
	 * Get the Jilt order ID for an order.
	 *
	 * @since 1.1.0
	 * @deprecated since 1.4.0
	 * @return int|string
	 */
	public function get_jilt_order_id() {

		_deprecated_function( 'WC_Jilt_Order::get_jilt_order_id()', '1.4.0', 'WC_Jilt_Order::get_jilt_cart_token' );

		return $this->get_jilt_cart_token();
	}


	/**
	 * Get the financial status for the order
	 *
	 * @since 1.0.0
	 *
	 * @return null|string
	 */
	public function get_financial_status() {

		$order_status     = $this->get_status();
		$financial_status = null;

		switch ( $order_status ) {

			// intentionally not mapped yet:
			// cancelled

			case 'failed':
			case 'pending':
			case 'on-hold':
				$financial_status = 'pending';
			break;

			case 'refunded':
				$financial_status = 'refunded';
			break;
		}

		if ( $this->is_paid() ) {
			$financial_status = 'paid';
		} elseif ( $this->get_total_refunded() && $this->get_total_refunded() !== $this->get_total() ) {
			$financial_status = 'partially_refunded';
		}

		/**
		 * Filter order financial status for Jilt
		 *
		 * @since 1.0.0
		 *
		 * @param null|string $financial_status
		 * @param int $order_id
		 */
		$financial_status = apply_filters( 'wc_jilt_order_financial_status', $financial_status, $this->id );

		return $this->is_valid_financial_status( $financial_status ) ? $financial_status : null;
	}


	/**
	 * Is the given financial status valid?
	 *
	 * @since 1.3.0
	 * @param string $financial_status
	 * @return boolean
	 */
	private function is_valid_financial_status( $financial_status ) {
		$valid = array(
			'pending',
			'authorized',
			'partially_paid',
			'paid',
			'partially_refunded',
			'refunded',
			'voided',
		);
		return in_array( $financial_status, $valid, true );
	}


	/**
	 * Get the fulfillment status for the order
	 *
	 * @since 1.2.1
	 *
	 * @return null|string
	 */
	public function get_fulfillment_status() {

		$fulfillment_status = null;
		$order_status       = $this->get_status();

		if ( $this->needs_shipping() ) {

			if ( 'completed' === $order_status ) {
				$fulfillment_status = 'fulfilled';
			} elseif ( ! in_array( $this->get_financial_status(), array( 'refunded', 'partially_refunded' ), true ) ) {
				// if an order was refunded, especially if partially refunded,
				// we can't be sure if it was unfulfilled
				$fulfillment_status = 'unfulfilled';
			}
		}

		/**
		 * Filter order fulfillment status for Jilt
		 *
		 * @since 1.2.1
		 * @param null|string $fulfillment_status
		 * @param int $order_id
		 */
		$fulfillment_status = apply_filters( 'wc_jilt_order_fulfillment_status', $fulfillment_status, $this->id );

		return $this->is_valid_fulfillment_status( $fulfillment_status ) ? $fulfillment_status : null;
	}


	/**
	 * Is the given fulfillment status valid?
	 *
	 * @since 1.3.0
	 * @param string $fulfillment_status
	 * @return boolean
	 */
	private function is_valid_fulfillment_status( $fulfillment_status ) {
		$valid = array(
			'fulfilled',
			'unfulfilled',
			'partial',
		);
		return in_array( $fulfillment_status, $valid, true );
	}


	/**
	 * Get the admin edit url for the order
	 *
	 * @since 1.0.0
	 * @return string|null
	 */
	public function get_order_edit_url() {

		if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
			$id   = $this->get_id();
			$post = get_post( $id );
		} else {
			$id   = $this->id;
			$post = $this->post;
		}

		if ( 'revision' === $post->post_type ) {
			$action = '';
		} else {
			$action = '&action=edit';
		}

		$post_type_object = get_post_type_object( $post->post_type );

		if ( ! $post_type_object ) {
			return null;
		}

		return esc_url_raw( admin_url( sprintf( $post_type_object->_edit_link . $action, $id ) ) );
	}


	/**
	 * Get the order subtotal, in pennies
	 *
	 * Note: this implementation is adapted directly from WC_Abstract_Order
	 *
	 * @since 1.3.0
	 *
	 * @return int the subtotal price, maybe inclusive of taxes, in pennies
	 */
	public function get_subtotal_price() {
		$subtotal = 0;
		$tax_display = get_option( 'woocommerce_tax_display_cart' );

		foreach ( $this->get_items() as $item ) {

			$subtotal += $item->get_subtotal();

			if ( 'incl' === $tax_display ) {
				$subtotal += $item->get_subtotal_tax();
			}
		}

		return $this->amount_to_int( $subtotal );
	}


	/**
	 * Determine if the order needs shipping or not
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function needs_shipping() {

		$needs_shipping = false;

		foreach ( $this->get_items() as $item ) {

			$_product = $this->get_product_from_item( $item );

			if ( ( $_product instanceof WC_Product ) && $_product->needs_shipping() ) {

				$needs_shipping = true;
				break;
			}
		}

		return $needs_shipping;
	}


	/**
	 * Map a WooCommerce address to Jilt address
	 *
	 * @since 1.0.0
	 * @param string $address_type either `billing` or `shipping`, defaults to `billing`
	 * @return array associative array suitable for Jilt API consumption
	 */
	public function map_address_to_jilt( $address_type = 'billing' ) {

		$address = $this->get_address( $address_type );
		$mapped_address = array();

		foreach ( self::get_jilt_order_address_mapping() as $wc_param => $jilt_param ) {

			if ( ! isset( $address[ $wc_param ] ) ) {
				continue;
			}

			$mapped_address[ $jilt_param ] = $address[ $wc_param ];
		}

		return $mapped_address;
	}


	/**
	 * Get WooCommerce order address -> Jilt order address mapping
	 *
	 * @since 1.0.0
	 * @return array $mapping
	 */
	public static function get_jilt_order_address_mapping() {

		/**
		 * Filter which WooCommerce address fields are mapped to which Jilt address fields
		 *
		 * @since 1.0.0
		 * @param array $mapping Associative array 'wc_param' => 'jilt_param'
		 */
		return apply_filters( 'wc_jilt_address_mapping', array(
			'email'      => 'email',
			'first_name' => 'first_name',
			'last_name'  => 'last_name',
			'address_1'  => 'address1',
			'address_2'  => 'address2',
			'company'    => 'company',
			'city'       => 'city',
			'state'      => 'state_code',
			'postcode'   => 'postal_code',
			'country'    => 'country_code',
			'phone'      => 'phone',
		) );
	}


	/**
	 * Convert a price/total to the lowest currency unit (e.g. cents)
	 *
	 * @since 1.2.0
	 * @param string|float $number
	 * @return int
	 */
	private function amount_to_int( $number ) {

		return round( $number * 100, 0 );
	}


	/**
	 * Return the product line items for the given Order in the format required
	 * by Jilt
	 *
	 * @since 1.0.0
	 * @return array
	 */
	private function get_product_line_items() {

		$line_items = array();

		foreach ( $this->get_items() as $item_id => $item ) {

			$product = $this->get_product_from_item( $item );

			if ( ! $product instanceof WC_Product ) {
				continue;
			}

			// prepare main line item params
			$line_item = array(
				'title'      => html_entity_decode( $item->get_name() ),
				'product_id' => $product->get_id(),
				'quantity'   => $item->get_quantity(),
				'sku'        => $product->get_sku(),
				'url'        => get_the_permalink( $product->get_id() ),
				'image_url'  => WC_Jilt_Product::get_product_image_url( $product ),
				'key'        => $item_id,
				'price'      => $this->amount_to_int( $this->get_item_subtotal( $item, 'incl' === get_option( 'woocommerce_tax_display_cart' ) ) ),
				'tax_lines'  => $this->get_tax_lines( $item ),
			);

			// add variation data
			if ( $product->is_type( 'variation' ) ) {

				$line_item['variant_id'] = $product->get_id();
				$line_item['product_id'] = Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? $product->get_parent_id() : $product->get_parent();
				$line_item['variation']  = WC_Jilt_Product::get_variation_data( $item );
			}

			// add line item meta (including hidden meta)
			$item_meta = array();

			foreach( $item->get_formatted_meta_data( false ) as $id => $meta ) {

				$item_meta[] = array(
					'label' => $meta->key,
					'value' => $meta->value,
				);
			}

			if ( ! empty( $item_meta ) ) {
				foreach ( $item_meta as $property ) {

					$core_properties = array( '_qty', '_product_id', '_variation_id', '_line_subtotal', '_line_total', '_line_subtotal_tax', '_line_tax' );

					// skip normal product attributes - these are already handled as variation data
					if ( isset( $property['key'] ) && ( Framework\SV_WC_Helper::str_starts_with( $property['key'], 'pa_' ) || in_array( $property['key'], $core_properties, true ) ) ) {
						continue;
					}

					if ( ! isset( $line_item['properties'] ) ) {
						$line_item['properties'] = array();
					}

					$line_item['properties'][ $property['label'] ] = $property['value'];
				}
			}

			/**
			 * Filter order item params used for updating a Jilt order
			 * via the API
			 *
			 * @since 1.0.0
			 * @param array $line_item Jilt line item data
			 * @param stdClass $item WC line item data in format provided by SV_WC_Helper::get_order_line_items()
			 * @param \WC_Jilt_Order $order instance
			 */
			$line_items[] = apply_filters( 'wc_jilt_order_line_item_params', $line_item, $item, $this );
		}

		return $line_items;
	}


	/**
	 * Get the tax lines, if any, for this item
	 *
	 * @since 1.3.0
	 * @param stdclass|WC_Order_Item_Product Item associative array
	 * @return array of tax lines, e.g. [ [ 'amount' => 135 ] ]
	 */
	private function get_tax_lines( $item ) {

		$total_tax = $item->get_total_tax( false );

		// a simplistic implementation for now, but if WC identifies the actual
		// taxes a la Shopify, we can make this more comprehensive
		// TODO: should we be using total_tax or subtotal_tax here?  {JS: 2017-11-10}
		return array(
			array(
				'amount' => $this->amount_to_int( $total_tax ),
			),
		);
	}


	/**
	 * Return the fee line items for this Order in the format required by Jilt
	 *
	 * @since 1.1.0
	 * @return array order fee items in Jilt format
	 */
	private function get_fee_line_items() {

		$fee_items = array();

		foreach ( $this->get_fees() as $fee ) {

			$name = Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? $fee->get_name() : $fee['name'];
			$id   = sanitize_title( $name );

			$fee_item = array(
				'title'  => html_entity_decode( $name ),
				'key'    => $id,
				'amount' => $this->amount_to_int( $this->get_item_total( $fee ) ),
			);

			/**
			 * Filter order fee params used for updating a Jilt order
			 * via the API
			 *
			 * @since 1.1.0
			 * @param array $fee_item Jilt fee item data
			 * @param array|\WC_Order_Item_Fee $fee provided by WC_Order::get_fees()
			 * @param \WC_Jilt_Order $order instance
			 */
			$fee_items[] = apply_filters( 'wc_jilt_order_fee_item_params', $fee_item, $fee, $this );
		}

		return $fee_items;
	}


	/**
	 * Determines if the order was a test order.
	 *
	 * @since 1.5.6
	 *
	 * @return bool|null
	 */
	public function is_test() {

		$is_test        = null;
		$payment_method = $this->get_payment_method();
		$gateways       = WC()->payment_gateways()->payment_gateways();
		$gateway        = ! empty( $gateways[ $payment_method ] ) ? $gateways[ $payment_method ] : null;

		// check for a SkyVerge plugin framework gateway
		if ( is_object( $gateway ) && defined( get_class( $gateway ) . '::ENVIRONMENT_TEST' ) && is_callable( array ( $gateway, 'get_order_meta' ) ) ) {
			$is_test = $gateway::ENVIRONMENT_TEST === $gateway->get_order_meta( $this, 'environment' );
		}

		/**
		 * Filters whether an order was a test order.
		 *
		 * @since 1.5.6
		 *
		 * @param bool|null $is_test whether this was a test order or null if that couldn't be determined
		 * @param \WC_Order $order order object
		 * @param \WC_Payment_Gateway|null $gateway payment gateway object if available
		 */
		return apply_filters( 'wc_jilt_order_is_test', $is_test, $this, $gateway );
	}


	/**
	 * Determines whether marketing consent was offered at checkout.
	 *
	 * @since 1.4.5
	 *
	 * @return bool
	 */
	public function marketing_consent_offered() {

		return 'yes' === get_post_meta( $this->get_id(), '_wc_jilt_marketing_consent_offered', true );
	}


	/**
	 * Determines whether marketing consent was accepted at checkout.
	 *
	 * @since 1.4.5
	 *
	 * @return bool
	 */
	public function marketing_consent_accepted() {

		return 'yes' === get_post_meta( $this->get_id(), '_wc_jilt_marketing_consent_accepted', true );
	}


	/**
	 * Gets the marketing consent notice as displayed at checkout.
	 *
	 * @since 1.4.5
	 *
	 * @return string
	 */
	public function get_marketing_consent_notice() {

		return get_post_meta( $this->get_id(), '_wc_jilt_marketing_consent_notice', true );
	}


}
