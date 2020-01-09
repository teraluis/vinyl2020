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
 * Returns the One True Instance of Jilt for WC
 *
 * @since 1.0.0
 *
 * @return \WC_Jilt
 */
function wc_jilt() {
	return WC_Jilt::instance();
}


/**
 * Returns all order meta keys added by Jilt for WC.
 *
 * @since 1.6.2
 *
 * @return array all meta keys
 */
function wc_jilt_get_order_meta_keys() {

	return [
		'_wc_jilt_cart_token',
		'_wc_jilt_order_id',
		'_wc_jilt_placed_at',
		'_wc_jilt_cancelled_at',
		'_wc_jilt_marketing_consent_offered',
		'_wc_jilt_marketing_consent_accepted',
		'_wc_jilt_marketing_consent_notice',
	];
}
