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
 * Integration with WooCommerce Subscriptions.
 *
 * @since 1.5.5
 */
class WC_Jilt_Subscriptions_Integration extends WC_Jilt_Integration_Base {


	/**
	 * Adds hooks for WooCommerce Subscriptions.
	 *
	 * @since 1.5.5
	 */
	public function __construct() {

		// add data to the subscriptions REST API response output
		add_filter( 'woocommerce_rest_prepare_shop_subscription', array( $this, 'add_subscription_rest_api_response_data' ), 20, 2 );

		// don't copy over order-specific meta to the WC_Subscription object during renewal processing
		add_filter( 'wcs_renewal_order_meta', [ $this, 'do_not_copy_order_meta' ] );

		// block Subscriptions from sending order.updated webhooks to Jilt for a subscription object
		add_filter( 'woocommerce_webhook_should_deliver', [ $this, 'block_order_updated_webhooks' ], 10, 3 );
	}


	/**
	 * Adds additional data to a Subscription's REST API response.
	 *
	 * @internal
	 *
	 * @since 1.5.5
	 *
	 * @param \WP_REST_Response $response response object
	 * @param \WP_Post $post the subscription post object
	 * @return \WP_REST_Response
	 */
	public function add_subscription_rest_api_response_data( $response, $post ) {

		if ( 'shop_subscription' === get_post_type( $post ) && ( $subscription = wcs_get_subscription( $post->ID ) ) ) {

			$data = (array) $response->get_data();

			$data['jilt'] = array(
				// adds a "View URL" front end link similar to that provided by Memberships in the user memberships REST API responses
				'view_url' => $subscription->get_view_order_url(),
			);

			$response->set_data( $data );
		}

		return $response;
	}


	/**
	 * Don't copy order-specific meta to renewal orders from the WC_Subscription
	 * object. Generally the subscription object should not have any order-specific
	 * meta (aside from `payment_token` and `customer_id`).
	 *
	 * @since 1.6.2
	 *
	 * @param array $order_meta order meta to copy
	 * @return array
	 */
	public function do_not_copy_order_meta( $order_meta ) {

		$meta_keys = wc_jilt_get_order_meta_keys();

		foreach ( $order_meta as $index => $meta ) {

			if ( in_array( $meta['meta_key'], $meta_keys ) ) {
				unset( $order_meta[ $index ] );
			}
		}

		return $order_meta;
	}


	/**
	 * Prevents WooCommerce Subscriptions from sending an order.updated webhook to Jilt for a subscription object.
	 *
	 * The woocommerce_order_updated webhook is fired manually in the subscription
	 *  data store on update but it fires for the subscription object, not an order.
	 *
	 * @since 1.6.2
	 *
	 * @param bool $deliver whether the webhook should deliver
	 * @param \WC_Webhook $webhook the webhook object
	 * @param mixed $arg first hook argument
	 * @return bool
	 */
	public function block_order_updated_webhooks( $deliver, $webhook, $arg ) {

		$handle_subscription = $deliver && function_exists( 'wcs_is_subscription' ) && wcs_is_subscription( $arg );

		// check if a sub webhook for order.{action} that should deliver is going to Jilt
		if ( $handle_subscription && Framework\SV_WC_Helper::str_starts_with( $webhook->get_topic(), 'order.' ) && Framework\SV_WC_Helper::str_exists( $webhook->get_delivery_url(), wc_jilt()->get_app_hostname() ) ) {
			$deliver = false;
		}

		return $deliver;
	}


	/**
	 * Returns the integration title.
	 *
	 * @since 1.5.5
	 *
	 * @return string
	 */
	public function get_title() {

		return __( 'Subscriptions', 'jilt-for-woocommerce' );
	}


	/**
	 * Determines whether Subscriptions is installed and active.
	 *
	 * @since 1.5.5
	 *
	 * @return bool
	 */
	public function is_active() {

		return wc_jilt()->is_plugin_active( 'woocommerce-subscriptions.php' );
	}


}
