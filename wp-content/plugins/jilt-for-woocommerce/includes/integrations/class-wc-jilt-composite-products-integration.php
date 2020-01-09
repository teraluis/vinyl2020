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
 * Add support for the WC Composite Products plugin by including the cart item
 * meta when available. Also group component products with, and following their
 * parent product.
 *
 * The order item meta is included automatically.
 *
 * @see https://github.com/skyverge/jilt-for-woocommerce/wiki/WooCommerce-Composite-Products-Integration
 * @since 1.4.3
 */
class WC_Jilt_Composite_Products_Integration extends WC_Jilt_Integration_Base {


	/**
	 * Setup the Composite Products integration class
	 *
	 * @since 1.4.3
	 */
	public function __construct() {

		add_filter( 'wc_jilt_order_cart_params',      array( $this, 'reorder_composite_cart_items' ) );
		add_filter( 'wc_jilt_order_cart_item_params', array( $this, 'add_composite_cart_item_properties' ), 10, 3 );
	}


	/**
	 * Get the title for this integration
	 *
	 * @see WC_Jilt_Integration::get_title()
	 * @since 1.4.3
	 * @return string integration title
	 */
	public function get_title() {
		return __( 'Composite Products', 'jilt-for-woocommerce' );
	}


	/**
	 * Is this integration active?
	 *
	 * @see WC_Jilt_Integration::is_active()
	 * @since 1.4.3
	 * @return boolean
	 */
	public function is_active() {
		return wc_jilt()->is_plugin_active( 'woocommerce-composite-products.php' );
	}


	/**
	 * Adds the Composite Products cart item properties, if needed.
	 *
	 * @since 1.4.3
	 * @param array $line_item Jilt line item data
	 * @param array $item WC line item data
	 * @param string $item_key WC cart key for item
	 * @return array associative array of Jilt line item data
	 */
	public function add_composite_cart_item_properties( $line_item, $item, $item_key ) {

		// parent product
		if ( ! empty( $item['composite_children'] ) ) {
			$line_item['properties']['_composite_cart_key'] = $item_key;
		}

		// child product
		if ( ! empty( $item['composite_item'] ) ) {
			$line_item['properties']['_composite_item']     = $item['composite_item'];
			$line_item['properties']['_composite_parent']   = $item['composite_parent'];
			$line_item['properties']['_composite_cart_key'] = $item_key;
		}

		return $line_item;
	}


	/**
	 * Composite Products end up ordered alphabetically by default, meaning
	 * that the parent product could be intermingled with its component
	 * products. This method will group and reorder composite products with the
	 * parent product first.
	 *
	 * @since 1.4.3
	 *
	 * @param array $cart_data the cart data sent to Jilt
	 * @return array cart data sent to Jilt
	 */
	public function reorder_composite_cart_items( $cart_data ) {

		$composite_items = array();

		// be sure the cart isn't empty
		if ( isset( $cart_data['line_items'] ) ) {

			// group all component items with their parent, and ensure the parent comes first
			foreach ( $cart_data['line_items'] as $i => $item ) {

				if ( isset( $item['properties']['_composite_cart_key'] ) && ! isset( $item['properties']['_composite_parent'] ) ) {

					// parent item
					$composite_items[] = $item;
					unset( $cart_data['line_items'][ $i ] );

					// now gather all component items
					foreach ( $cart_data['line_items'] as $j => $component_item ) {

						if ( isset( $component_item['properties']['_composite_parent'] ) &&
						     $component_item['properties']['_composite_parent'] == $item['properties']['_composite_cart_key'] ) {
							$composite_items[] = $component_item;
							unset( $cart_data['line_items'][ $j ] );
						}
					}
				}
			}

			if ( $composite_items ) {
				foreach ( $composite_items as $item ) {
					$cart_data['line_items'][] = $item;
				}
			}
		}

		return $cart_data;
	}


}
