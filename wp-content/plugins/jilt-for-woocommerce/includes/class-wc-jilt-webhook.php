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
 * @since 1.5.0
 */
class WC_Jilt_Webhook {


	/**
	 * Delete all Jilt for WooCommerce webhooks
	 *
	 * @since 1.5.0
	 */
	public static function delete_webhooks() {

		if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.3.0' ) ) {

			$data_store = WC_Data_Store::load( 'webhook' );
			$webhooks   = $data_store->get_webhooks_ids();

			foreach ( $webhooks as $webhook_id ) {

				$webhook = new WC_Webhook( $webhook_id );

				if ( false !== strpos( $webhook->get_delivery_url(), wc_jilt()->get_app_hostname() ) ) {

					 // true to delete permanently
					$webhook->delete( true );
				}
			}

		} else {

			$webhooks = get_posts( array(
				'fields'         => 'ids',
				'post_type'      => 'shop_webhook',
				'post_status'    => 'publish',
				'posts_per_page' => -1,
			) );

			foreach ( $webhooks as $webhook_id ) {

				$webhook = new WC_Webhook( $webhook_id );

				if ( false !== strpos( $webhook->get_delivery_url(), wc_jilt()->get_app_hostname() ) ) {

					wp_delete_post( $webhook_id, true );
				}
			}
		}
	}


}
