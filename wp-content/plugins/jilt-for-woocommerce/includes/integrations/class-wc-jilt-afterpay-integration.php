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
 * Add support for the Afterpay Gateway for WooCommerce by linking an Afterpay
 * quote record to a placed order via Jilt cart token.
 *
 * Without this integration active, a checkout using Afterpay results in a cart
 * tracked within Jilt app which is never placed, and a placed order that is a
 * duplicate of the cart. The cart record within Jilt will continue to receive
 * cart recovery emails within Jilt, despite it actually being placed.
 *
 * @see https://wordpress.org/plugins/afterpay-gateway-for-woocommerce/
 * @see https://github.com/skyverge/jilt-for-woocommerce/wiki/Afterpay-Integration
 * @since 1.5.7
 */
class WC_Jilt_Afterpay_Integration extends WC_Jilt_Integration_Base {


	/** @var int the Afterpay quote id */
	private $quote_id;

	/** @var array key-value pairs of jilt meta */
	private $jilt_meta = array();


	/**
	 * Sets up the Afterpay integration class.
	 *
	 * @since 1.5.7
	 */
	public function __construct() {

		add_action( 'save_post_afterpay_quote', array( $this, 'save_jilt_data_to_quote' ), 10, 3 );

		add_action( 'before_delete_post', array( $this, 'capture_jilt_data_from_quote' ) );

		add_action( 'woocommerce_new_order', array( $this, 'save_jilt_data_to_order' ) );
	}


	/**
	 * Gets the title for this integration.
	 *
	 * @see WC_Jilt_Integration::get_title()
	 *
	 * @since 1.5.7
	 *
	 * @return string integration title
	 */
	public function get_title() {
		return __( 'Afterpay Gateway for WooCommerce', 'jilt-for-woocommerce' );
	}


	/**
	 * Checks if this integration is active.
	 *
	 * @see WC_Jilt_Integration::is_active()
	 *
	 * @since 1.5.7
	 *
	 * @return boolean
	 */
	public function is_active() {
		return wc_jilt()->is_plugin_active( 'afterpay-gateway-for-woocommerce.php' );
	}


	/**
	 * Persists any Jilt cart data to the temporary Afterpay quote record.
	 *
	 * @since 1.5.7
	 *
	 * @param int $post_id
	 * @param WP_Post $post
	 * @param bool $update
	 */
	public function save_jilt_data_to_quote( $post_id, $post, $update ) {

		// only act on new orders
		if ( $update ) {
			return;
		}

		wc_jilt()->get_checkout_handler_instance()->update_marketing_consent( $post_id );

		// mark as pending recovery
		if ( WC_Jilt_Session::is_pending_recovery() ) {
			wc_jilt()->get_integration()->mark_order_as_pending_recovery( $post_id );
		}

		$cart_token = WC_Jilt_Session::get_cart_token();

		if ( $cart_token ) {
			update_post_meta( $post_id, '_wc_jilt_cart_token', $cart_token );
		}
	}


	/**
	 * Captures any jilt data from the Afterpay quote record before it gets
	 * deleted.
	 *
	 * @since 1.5.7
	 *
	 * @param int $post_id
	 */
	public function capture_jilt_data_from_quote( $post_id ) {

		$post = get_post( $post_id );

		if ( 'afterpay_quote' === $post->post_type ) {

			$this->quote_id = (int) $post_id;

			$post_meta = get_post_meta( $post_id, '', true );

			foreach ( $post_meta as $key => $value ) {
				if ( 0 === strpos( $key, '_wc_jilt' ) ) {
					$this->jilt_meta[ $key ] = $value[0];
				}
			}
		}
	}


	/**
	 * Persists any Jilt meta data from the Afterpay quote to the newly placed
	 * Afterpay order.
	 *
	 * @since 1.5.7
	 *
	 * @param int $order_id
	 */
	public function save_jilt_data_to_order( $order_id ) {

		$order_id = (int) $order_id;

		if ( $order_id === $this->quote_id ) {

			foreach ( $this->jilt_meta as $key => $value ) {
				update_post_meta( $order_id, $key, $value );
			}
		}
	}


}
