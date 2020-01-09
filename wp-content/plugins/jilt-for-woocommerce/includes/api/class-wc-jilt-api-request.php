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
 * @package   WC-Jilt/API
 * @author    Jilt
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;


/**
 * Jilt API Request class
 *
 * @since 1.0.0
 */
class WC_Jilt_API_Request extends Framework\SV_WC_API_JSON_Request {


	/**
	 * @since 1.0.0
	 * @param string $method request method
	 * @param string $path request path
	 * @param array $params associative array of request params
	 * @param array $data associative array of request data
	 */
	public function __construct( $method, $path = '', $params = array(), $data = array() ) {

		$this->method = $method;
		$this->path   = $path;
		$this->params = $params;
		$this->data   = $data;
	}


	/**
	 * Get the content-type for this request
	 *
	 * TODO: consider adding request_content_type() and accept_content_type() to SV_WC_API_Request {2018-04-02 - justinstern}
	 *
	 * @since 1.4.4
	 * @return string 'application/json'
	 */
	public function request_content_type() {

		return 'application/json';
	}


	/**
	 * Get the accept type for this request
	 *
	 * TODO: consider adding request_content_type() and accept_content_type() to SV_WC_API_Request {2018-04-02 - justinstern}
	 *
	 * @since 1.4.4
	 * @return string 'application/json'
	 */
	public function accept_content_type() {

		return 'application/json';
	}


	/**
	 * Returns the string representation of this request
	 *
	 * @since 4.0.0
	 * @see SV_WC_API_Request::to_string()
	 * @return string request
	 */
	public function to_string() {

		$string = '';

		if ( $this->get_data() ) {
			$string = wp_json_encode( $this->get_data() );
		} elseif ( $this->get_params() ) {
			// deprecated GET /v1/shops request
			$string = http_build_query( $this->get_params(), '', '&' );
		}

		return $string;
	}


	/**
	 * Returns the string representation of this request with any and all sensitive elements masked
	 * or removed.
	 *
	 * @since 1.5.0
	 *
	 * @see \SV_WC_API_Request::to_string_safe()
	 *
	 * @return string
	 */
	public function to_string_safe() {

		$string = $this->to_string();

		// mask the WC REST API consumer key if it is present
		if ( ! empty( $this->data['woocommerce_consumer_key'] ) ) {
			$string = str_replace( $this->data['woocommerce_consumer_key'], str_repeat( '*', strlen( $this->data['woocommerce_consumer_key'] ) ), $string );
		}

		// mask the WC REST API consumer key if it is present
		if ( ! empty( $this->data['woocommerce_consumer_secret'] ) ) {
			$string = str_replace( $this->data['woocommerce_consumer_secret'], str_repeat( '*', strlen( $this->data['woocommerce_consumer_secret'] ) ), $string );
		}

		return $string;
	}


}
