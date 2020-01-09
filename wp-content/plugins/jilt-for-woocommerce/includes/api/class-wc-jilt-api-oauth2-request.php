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
 * The OAuth 2 request class.
 *
 * @since 1.4.0
 */
class WC_Jilt_API_OAuth2_Request implements Framework\SV_WC_API_Request {


	/** @var string request method, one of HEAD, GET, PUT, PATCH, POST, DELETE */
	protected $method;

	/** @var string request path */
	protected $path = '';

	/** @var array request params */
	protected $params = array();

	/** @var array request data */
	protected $data = array();


	/**
	 * Sets up the OAuth2 request instance.
	 *
	 * @since 1.4.0
	 *
	 * @param string $method request method
	 * @param string $path request path
	 */
	public function __construct( $method, $path = '' ) {
		$this->method = $method;
		$this->path   = $path;
	}


	/**
	 * Sets the data needed for requesting installation-specific OAuth client credentials
	 *
	 * @since 1.4.0
	 *
	 * @param string $domain the shop domain that the client will be scoped to
	 * @param string $redirect_uri the redirect URL
	 */
	public function set_connect_data( $domain, $redirect_uri ) {

		$this->data = array(
			'domain'          => $domain,
			'redirect_uri'    => $redirect_uri,
		);
	}


	/**
	 * Sets the data needed for generating an access token from an authorization code.
	 *
	 * @since 1.4.0
	 *
	 * @param string $code authorization code from the initial permissions request
	 * @param string $redirect_uri the redirect URL
	 * @param string $client_id the OAuth client id
	 * @param string $client_secret the OAuth client secret
	 */
	public function set_authorization_data( $code, $redirect_uri, $client_id, $client_secret ) {

		$this->data = array(
			'grant_type'    => 'authorization_code',
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
			'code'          => $code,
			'redirect_uri'  => $redirect_uri, // TODO: urlencode once this is resolved: https://github.com/doorkeeper-gem/doorkeeper/issues/1013
		);
	}


	/**
	 * Sets the data needed to refresh an access token.
	 *
	 * @since 1.4.0
	 *
	 * @param string $refresh_token refresh token
	 */
	public function set_refresh_data( $refresh_token ) {

		$this->data = array(
			'refresh_token' => $refresh_token,
			'grant_type'    => 'refresh_token',
		);
	}


	/**
	 * Sets the data needed to refresh an access token.
	 *
	 * @since 1.4.0
	 *
	 * @param string $token the OAuth access token to revoke
	 * @param string $client_id the OAuth client id
	 * @param string $client_secret the OAuth client secret
	 */
	public function set_revoke_data( $token, $client_id, $client_secret ) {

		$this->data = array(
			'token'         => $token,
			'client_id'     => $client_id,
			'client_secret' => $client_secret,
		);
	}


	/**
	 * Returns the request method.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_method() {

		return $this->method;
	}


	/**
	 * Returns the request path.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_path() {

		return $this->path;
	}


	/**
	 * Returns the request params.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_params() {

		return $this->params;
	}


	/**
	 * Returns the request data.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_data() {

		return $this->data;
	}


	/**
	 * Returns the string representation of this request.
	 *
	 * @since 1.4.0
	 *
	 * @see \SV_WC_API_Request::to_string()
	 *
	 * @return string
	 */
	public function to_string() {

		return ! empty( $this->data ) ? http_build_query( $this->data, '', '&' ) : '';
	}


	/**
	 * Returns the string representation of this request with any and all sensitive elements masked
	 * or removed.
	 *
	 * @since 1.4.0
	 *
	 * @see \SV_WC_API_Request::to_string_safe()
	 *
	 * @return string
	 */
	public function to_string_safe() {

		$string = $this->to_string();

		// mask the client secret code
		if ( ! empty( $this->data['client_secret'] ) ) {
			$string = str_replace( $this->data['client_secret'], str_repeat( '*', strlen( $this->data['client_secret'] ) ), $string );
		}

		// mask the authorization code
		if ( ! empty( $this->data['code'] ) ) {
			$string = str_replace( $this->data['code'], str_repeat( '*', strlen( $this->data['code'] ) ), $string );
		}

		// mask the access token
		if ( ! empty( $this->data['token'] ) ) {
			$string = str_replace( $this->data['token'], str_repeat( '*', strlen( $this->data['token'] ) ), $string );
		}

		// mask the refresh token
		if ( ! empty( $this->data['refresh_token'] ) ) {
			$string = str_replace( $this->data['refresh_token'], str_repeat( '*', strlen( $this->data['refresh_token'] ) ), $string );
		}

		return $string;
	}


	/**
	 * Get the content-type for this request
	 *
	 * TODO: consider adding request_content_type() and accept_content_type() to SV_WC_API_Request {2018-04-02 - justinstern}
	 *
	 * @since 1.4.4
	 * @return string 'application/x-www-form-urlencoded'
	 */
	public function request_content_type() {

		return 'application/x-www-form-urlencoded';
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


}
