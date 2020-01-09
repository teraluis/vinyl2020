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
 * The OAuth response class.
 *
 * @since 1.4.0
 */
class WC_Jilt_API_OAuth2_Response extends Framework\SV_WC_API_JSON_Response {


	/**
	 * Returns the client secret.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_client_secret() {

		return $this->client_secret;
	}


	/**
	 * Returns the access token.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_access_token() {

		return $this->access_token;
	}


	/**
	 * Returns the refresh token.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_refresh_token() {

		return $this->refresh_token;
	}


	/**
	 * Returns the access token expiry.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_access_token_expiry() {

		$expires_in = $this->expires_in;

		if ( empty( $expires_in ) ) {
			$expires_in = 3600;
		}

		return time() + $expires_in;
	}


	/**
	 * Determines if the response has errors.
	 *
	 * @since 1.4.0
	 *
	 * @return bool
	 */
	public function has_api_errors() {

		$error = $this->error;

		return ! empty( $error );
	}


	/**
	 * Returns the response errors.
	 *
	 * @since 1.4.0
	 *
	 * @return \WP_Error
	 */
	public function get_api_errors() {

		return new WP_Error( $this->error, __( 'Authentication error. Please try again.', 'jilt-for-woocommerce' ) );
	}


	/**
	 * Returns the string representation of this response with any and all sensitive elements masked
	 * or removed.
	 *
	 * @since 1.4.0
	 *
	 * @see \SV_WC_API_Response::to_string_safe()
	 *
	 * @return string
	 */
	public function to_string_safe() {

		$string = $this->to_string();

		// mask the client secret, access token & refresh token
		$string = str_replace( array(
			$this->get_client_secret(),
			$this->get_access_token(),
			$this->get_refresh_token(),
		), array(
			str_repeat( '*', strlen( $this->get_client_secret() ) ),
			str_repeat( '*', strlen( $this->get_access_token() ) ),
			str_repeat( '*', strlen( $this->get_refresh_token() ) ),
		), $string );

		return wp_json_encode( json_decode( $string ), JSON_PRETTY_PRINT );
	}


}
