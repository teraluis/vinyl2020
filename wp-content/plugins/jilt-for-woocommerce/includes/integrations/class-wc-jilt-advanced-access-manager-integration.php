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
 * Adds support for the Advanced Access Manager by detecting and warning when
 * the REST API is disabled.
 *
 * @see https://wordpress.org/plugins/advanced-access-manager/
 *
 * @since 1.5.1
 */
class WC_Jilt_Advanced_Access_Manager_Integration extends WC_Jilt_Integration_Base {


	/**
	 * Sets up the Advanced Access Manager integration class.
	 *
	 * @since 1.5.1
	 */
	public function __construct() {

		add_filter( 'wc_jilt_wc_rest_api_status',  array( $this, 'adjust_rest_api_status' ), 10 );
	}


	/**
	 * Gets the title for this integration.
	 *
	 * @see \WC_Jilt_Integration_Base::get_title()
	 *
	 * @since 1.5.1
	 *
	 * @return string
	 */
	public function get_title() {

		return __( 'Advanced Access Manager', 'jilt-for-woocommerce' );
	}


	/**
	 * Determines if the integration is active.
	 *
	 * @see \WC_Jilt_Integration_Base::is_active()
	 *
	 * @since 1.5.1
	 *
	 * @return bool
	 */
	public function is_active() {

		return wc_jilt()->is_plugin_active( 'aam.php' );
	}


	/**
	 * Determines if the Advanced Access Manager is disabling WP REST API access.
	 *
	 * @since 1.5.1
	 *
	 * @param array $details an array containing is_disabled and reason keys
	 * @return array
	 */
	public function adjust_rest_api_status( $details ) {

		if ( ! $this->is_active() || ! class_exists( 'AAM_Core_Config' ) ) {
			return $details;
		}

		if ( isset( $details['is_disabled'] ) && false === $details['is_disabled'] && ! AAM_Core_Config::get( 'core.settings.restful', true ) ) {
			$details['is_disabled'] = true;
			$details['reason'] = __( 'WP REST API disabled by Advanced Access Manager', 'jilt-for-woocommerce' ); // TODO: potentially add a link to one-click disable the AAM setting {CW 2018-07-25}
		}

		return $details;
	}


}
