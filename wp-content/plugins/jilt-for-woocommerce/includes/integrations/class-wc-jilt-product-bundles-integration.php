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
 * Add support for the WC Product Bundles plugin by including the cart item
 * meta: _bundled_by, _bundled_item_id, and _bundled_items when availble.
 *
 * The order item meta is included automatically.
 *
 * @see https://github.com/skyverge/jilt-for-woocommerce/wiki/Product-Bundles-Integration
 * @since 1.3.0
 */
class WC_Jilt_Product_Bundles_Integration extends WC_Jilt_Integration_Base {


	/**
	 * Setup the Product Bundles integration class
	 *
	 * @since 1.3.0
	 */
	public function __construct() {

		add_filter( 'wc_jilt_order_cart_item_params',           array( $this, 'get_cart_item_properties' ), 10, 2 );
		add_filter( 'wc_jilt_remote_session_for_cart_recreate', array( $this, 'fix_remote_session_for_cart_recreate' ) );
	}


	/**
	 * Get the title for this integration
	 *
	 * @see WC_Jilt_Integration::get_title()
	 * @since 1.3.0
	 * @return string integration title
	 */
	public function get_title() {
		return __( 'Product Bundles', 'jilt-for-woocommerce' );
	}


	/**
	 * Is this integration active?
	 *
	 * @see WC_Jilt_Integration::is_active()
	 * @since 1.3.0
	 * @return boolean
	 */
	public function is_active() {
		return wc_jilt()->is_plugin_active( 'woocommerce-product-bundles.php' );
	}


	/**
	 * Adds the Product Bundles cart item properties, if needed.
	 *
	 * @since 1.3.0
	 * @param array $line_item Jilt line item data
	 * @param array $item WC line item data
	 * @return array associative array of Jilt line item data
	 */
	public function get_cart_item_properties( $line_item, $item ) {

		if ( ! empty( $item['bundled_by'] ) ) {
			$line_item['properties']['_bundled_by'] = $item['bundled_by'];
		}
		if ( ! empty( $item['bundled_item_id'] ) ) {
			$line_item['properties']['_bundled_item_id'] = $item['bundled_item_id'];
		}
		if ( ! empty( $item['bundled_items'] ) ) {
			$line_item['properties']['_bundled_items'] = $item['bundled_items'];
		}

		return $line_item;
	}


	/**
	 * Fixes the client session for carts containing Product Bundles.
	 *
	 * Parent bundle items must come *directly* before their child bundled products,
	 * otherwise the child items can end up adding their price to the cart
	 * total, or grouped with the wrong parent.
	 *
	 * This can be removed once enough time has passed since
	 * https://github.com/skyverge/jilt-app/issues/612 has been deployed
	 *
	 * @since 1.4.0
	 *
	 * @param array $client_session session data returned from REST API
	 * @return array session data with any product bundles fixed
	 */
	public function fix_remote_session_for_cart_recreate( $client_session ) {

		// ensure the cart is an array rather than object
		$cart = json_decode( wp_json_encode( $client_session->cart ), true );
		$bundle_items = array();

		// group all bundled items with their parent, and ensure the parent comes first
		foreach ( $cart as $key => $item ) {
			if ( isset( $item['bundled_items'] ) ) {

				// parent item
				$bundle_items[ $key ] = $item;
				unset( $cart[ $key ] );

				// now gather all child items
				foreach ( $item['bundled_items'] as $child_key ) {
					if ( isset( $cart[ $child_key ] ) ) {

						$bundle_items[ $child_key ] = $cart[ $child_key ];
						unset( $cart[ $child_key ] );
					}
				}
			}
		}

		if ( $bundle_items ) {

			$cart = array_merge( $bundle_items, $cart );
			$client_session->cart = $cart;
		}

		return $client_session;
	}


}
