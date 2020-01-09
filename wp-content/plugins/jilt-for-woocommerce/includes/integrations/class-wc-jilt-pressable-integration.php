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
 * Adds support for Pressable account activations when detected.
 *
 * @since 1.6.0
 */
class WC_Jilt_Pressable_Integration extends WC_Jilt_Integration_Base {


	/**
	 * Constructs the Pressable integration class.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		add_filter( 'wc_jilt_app_connection_redirect_args', array( $this, 'add_pressable_connect_redirect_arg' ) );
	}


	/**
	 * Gets the title for this integration.
	 *
	 * @see WC_Jilt_Integration::get_title()
	 *
	 * @since 1.6.0
	 *
	 * @return string integration title
	 */
	public function get_title() {

		return __( 'Pressable', 'jilt-for-woocommerce' );
	}


	/**
	 * Adds the Pressable plan arg to the connection redirect args when appropriate.
	 *
	 * @since 1.6.0
	 *
	 * @param array $args redirect args
	 * @return array
	 */
	public function add_pressable_connect_redirect_arg( $args ) {

		if ( defined( 'IS_PRESSABLE' ) && IS_PRESSABLE ) {

			$args['plan'] = 'pressable';
		}

		return $args;
	}


}
