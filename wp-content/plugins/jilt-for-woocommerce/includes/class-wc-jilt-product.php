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
class WC_Jilt_Product {


	/**
	 * Return the image URL for a product
	 *
	 * @since 1.0.0
	 * @param \WC_Product $product
	 * @return string
	 */
	public static function get_product_image_url( WC_Product $product ) {

		$src = wc_placeholder_img_src();

		if ( $image_id = $product->get_image_id() ) {

			list( $src ) = wp_get_attachment_image_src( $image_id, 'full' );
		}

		return $src;
	}


	/**
	 * Get item variation data
	 *
	 * @since 1.0.0
	 * @param array $item
	 * @return array
	 */
	public static function get_variation_data( $item ) {

		$variation_data = array();

		if ( ! empty( $item['variation_id'] ) && $attributes = wc_get_product_variation_attributes( $item['variation_id'] ) ) {

			foreach ( $attributes as $name => $value ) {

				if ( '' === $value ) {
					continue;
				}

				$taxonomy = wc_attribute_taxonomy_name( str_replace( 'attribute_pa_', '', urldecode( $name ) ) );

				// If this is a term slug, get the term's nice name
				if ( taxonomy_exists( $taxonomy ) ) {

					$term = get_term_by( 'slug', $value, $taxonomy );

					if ( ! is_wp_error( $term ) && $term && $term->name ) {
						$value = $term->name;
					}

					$label = wc_attribute_label( $taxonomy );

					// If this is a custom option slug, get the options name
				} else {

					$value = apply_filters( 'woocommerce_variation_option_name', $value );

					// can occur after checkout, but generally not before
					if ( empty( $item['data'] ) || ! $item['data'] instanceof WC_Product ) {
						$item['data'] = wc_get_product( $item['variation_id'] );
					}

					$product_attributes = $item['data']->get_attributes();

					if ( isset( $product_attributes[ str_replace( 'attribute_', '', $name ) ] ) ) {

						$attribute_name = Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ? str_replace( 'attribute_', '', $name ) : $product_attributes[ str_replace( 'attribute_', '', $name ) ]['name'];

						$label = wc_attribute_label( $attribute_name, $item['data'] );

					} else {

						$label = $name;
					}
				}

				$variation_data[ $label ] = $value;
			}
		}

		return $variation_data;
	}


}
