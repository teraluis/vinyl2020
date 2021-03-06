<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce product type (default column) exportability model
 *
 * @since 2.2.1
 */
class ACA_WC_Export_Product_Type extends ACP_Export_Model {

	public function get_value( $id ) {
		return wc_get_product( $id )->get_type();
	}

}
