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
 * Jilt API class - used for both REST API as well as OAuth handling.
 *
 * @since 1.0.0
 */
class WC_Jilt_API extends Framework\SV_WC_API_Base {


	/** Jilt REST API version */
	const API_VERSION = 2;

	/** @var string linked Shop ID */
	protected $shop_id;

	/** @var \WC_Jilt_OAuth_Access_Token|string Jilt OAuth access token or API secret key */
	protected $auth_token;

	/** @var string HTTP Authorization scheme */
	protected $auth_scheme;

	/** @var string the last used transport name: 'cURL', 'fsockopen', or null */
	protected $request_transport;

	/** @var resource the last used curl handle resource, if curl was used as the transport mechanism */
	protected $curl_handle;

	/** @var array info from the last used curl handle resource, if curl was used as the transport mechanism */
	protected $curl_info;


	/**
	 * Sets up the API client.
	 *
	 * @since 1.0.0
	 *
	 * @param string $shop_id (optional) linked Shop ID
	 * @param \WC_Jilt_OAuth_Access_Token|string $auth_token Jilt OAuth access token or secret api key for shops using legacy auth
	 */
	public function __construct( $shop_id = null, $auth_token = null ) {

		$this->shop_id = $shop_id;

		// set auth creds
		$this->auth_token  = $auth_token;

		// OAuth uses Bearer, API secret key uses Token scheme
		$this->auth_scheme = $this->auth_token ? ( is_string( $this->auth_token ) ? 'Token' : 'Bearer' ) : null;

		// set up the request/response defaults
		$this->request_uri = $this->get_api_endpoint();
		$this->set_request_header( 'x-jilt-shop-domain', wc_jilt()->get_shop_domain() );
		$this->set_authorization_header();

		add_action( 'requests-curl.before_send', array( $this, 'set_curl_handle' ) );
		add_action( 'requests-curl.after_send',  array( $this, 'set_curl_info' ) );

		// pass through the client browser http referer
		$this->set_request_header( 'referer', $this->get_client_request_url() );

		if ( $user_id = get_current_user_id() ) {
			$this->set_request_header( 'x-jilt-remote-user-id', $user_id );
		}
	}


	/** API methods ****************************************************/


	/**
	 * Attempts to upgrade the shop from secret key to OAuth.
	 *
	 * @since 1.4.0
	 *
	 * @param int $shop_id the shop id
	 * @param string $domain the shop domain
	 * @param string $redirect_uri the redirect uri
	 * @return stdClass the response returned by Jilt
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function update_auth( $shop_id, $domain, $redirect_uri ) {

		return $this->perform_request( $this->get_new_request( array( 'method' => 'PUT', 'path' => "/shops/{$shop_id}/update_auth", 'data' => array(
			'domain'          => $domain,
			'redirect_uri'    => $redirect_uri,
		) ) ) );
	}


	/**
	 * Gets the current user public key
	 *
	 * @since 1.0.0
	 * @return string public key for the current API user
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function get_public_key() {

		$response = $this->perform_request( $this->get_new_request( array( 'method' => 'GET', 'path' => '/user' ) ) );

		return $response->public_key;
	}


	/**
	 * Find a shop by domain
	 *
	 * @since 1.0.0
	 *
	 * @param array $args associative array of search parameters. Supports: 'domain'
	 * @return stdClass the shop record returned by the API, or null if none was found
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function find_shop( $args ) {

		$response = $this->perform_request( $this->get_new_request( array( 'method' => 'GET', 'path' => '/shops', 'params' => $args ) ) );

		if ( 0 === count( $response->response_data ) ) {
			return null;
		} else {
			// return the first found shop
			return $response->response_data[0];
		}
	}


	/**
	 * Gets a shop.
	 *
	 * @since 1.5.0
	 *
	 * @param string $shop_id the shop UUID
	 * @return stdClass the shop record returned by the API
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function get_shop( $shop_id = null ) {

		$shop_id = null === $shop_id ? $this->shop_id : $shop_id;

		$response = $this->perform_request( $this->get_new_request( array( 'method' => 'GET', 'path' => "/shops/{$shop_id}" ) ) );

		return $response->response_data;
	}


	/**
	 * Create a shop
	 *
	 * @since 1.0.0
	 * @param array $args associative array of shop parameters.
	 *        Required: 'profile_type', 'domain'
	 * @return stdClass the shop record returned by the API
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function create_shop( $args ) {

		$response = $this->perform_request( $this->get_new_request( array( 'method' => 'POST', 'path' => '/shops', 'data' => $args ) ) );

		return $response->response_data;
	}


	/**
	 * Updates a shop.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args associative array of shop parameters
	 * @param int|string $shop_id optional shop ID to update
	 * @return stdClass the shop record returned by the API
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function update_shop( $args, $shop_id = null ) {

		$shop_id = null === $shop_id ? $this->shop_id : $shop_id;

		$response = $this->perform_request( $this->get_new_request( array( 'method' => 'PUT', 'path' => "/shops/{$shop_id}", 'data' => $args ) ) );

		return $response->response_data;
	}


	/**
	 * Deletes the shop.
	 *
	 * @since 1.1.0
	 *
	 * @return stdClass the shop record returned by the API
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function delete_shop() {

		$response = $this->perform_request( $this->get_new_request( array( 'method' => 'DELETE', 'path' => "/shops/{$this->shop_id}" ) ) );

		return $response->response_data;
	}


	/**
	 * Returns an order.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cart_token cart token
	 * @return stdClass the order record returned by the API
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function get_order( $cart_token ) {

		$response = $this->perform_request( $this->get_new_request( array( 'method' => 'GET', 'path' => "/shops/{$this->shop_id}/orders/{$cart_token}" ) ) );

		return $response->response_data;
	}


	/**
	 * Creates an order.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args associative array of order parameters
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function create_order( $args ) {

		$this->perform_request( $this->get_new_request( array( 'method' => 'POST', 'path' => "/shops/{$this->shop_id}/orders", 'data' => $args ) ) );
	}


	/**
	 * Updates an order.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cart_token cart token
	 * @param array $args associative array of order parameters
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function update_order( $cart_token, $args ) {

		$this->perform_request( $this->get_new_request( array( 'method' => 'PUT', 'path' => "/shops/{$this->shop_id}/orders/{$cart_token}", 'data' => $args ) ) );
	}


	/**
	 * Deletes an order.
	 *
	 * @since 1.0.0
	 *
	 * @param string $cart_token cart token
	 * @throws Framework\SV_WC_API_Exception on API error
	 * @return mixed
	 */
	public function delete_order( $cart_token ) {

		$this->perform_request( $this->get_new_request( array( 'method' => 'DELETE', 'path' => "/shops/{$this->shop_id}/orders/{$cart_token}" ) ) );
	}


	/**
	 * Restores a deleted order.
	 *
	 * @since 1.5.0
	 *
	 * @param string $cart_token cart token
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function restore_order( $cart_token ) {

		$this->perform_request( $this->get_new_request( array( 'method' => 'PATCH', 'path' => "/shops/{$this->shop_id}/orders/{$cart_token}", 'data' => array( 'deleted_at' => null ) ) ) );
	}


	/** Validation methods ****************************************************/


	/**
	 * Check if the response has any status code errors
	 *
	 * @since 1.0.0
	 * @see \SV_WC_API_Base::do_pre_parse_response_validation()
	 * @throws Framework\SV_WC_API_Exception non HTTP 200 status
	 */
	protected function do_pre_parse_response_validation() {

		switch ( $this->get_response_code() ) {

			// situation normal
			case 200:
			case 201:
			case 202:
			case 204:
				return;

			case 401:
				$headers = $this->get_response_headers();

				// expired token, try to refresh
				if ( ! $this->request instanceof WC_Jilt_API_OAuth2_Request && ! empty( $headers['www-authenticate'] ) && Framework\SV_WC_Helper::str_exists( $headers['www-authenticate'], 'The access token expired' ) ) {
					// first broadcast the request that resulted in this 401 response,
					// otherwise only the token refresh request will be logged
					$this->broadcast_request();

					if ( $this->maybe_refresh_access_token( true ) ) {
						// token refreshed: error averted!
						return;
					}
				}

				$this->handle_generic_api_error();
			break;

			// jilt account has been cancelled
			// TODO: this code has not yet been implemented see https://github.com/skyverge/jilt-app/issues/90
			case 410:
				$this->get_plugin()->handle_account_cancellation();
			break;

			default:
				$this->handle_generic_api_error();
		}
	}


	/**
	 * Handles generic API errors.
	 *
	 * @since 1.4.0
	 *
	 * @throws Framework\SV_WC_API_Exception
	 */
	private function handle_generic_api_error() {

		// default message to response code/message (e.g. HTTP Code 422 - Unprocessable Entity)
		$message = sprintf( 'HTTP code %s - %s', $this->get_response_code(), $this->get_response_message() );

		// if there's a more helpful Jilt API error message, use that instead
		if ( $this->get_raw_response_body() ) {
			$response = $this->get_parsed_response( $this->raw_response_body );

			if ( $response->response_data ) {
				$message = isset( $response->response_data->error_description ) ? $response->response_data->error_description : $response->response_data->error->message;
			}
		}

		throw new Framework\SV_WC_API_Exception( $message, $this->get_response_code() );
	}


	/** Helper methods ********************************************************/


	/**
	 * Set the curl handle for the current request
	 *
	 * Note: this method is public so that it can be called by the
	 * requests-curl.before_send action, and should not be called directly
	 *
	 * @since 1.5.0
	 * @param resoruce $handle curl handle
	 */
	public function set_curl_handle( $handle ) {

		$this->curl_handle = $handle;
	}


	/**
	 * Set the curl info for the current request
	 *
	 * Note: this method is public so that it can be called by the
	 * requests-curl.after_send action, and should not be called directly
	 *
	 * @since 1.5.0
	 */
	public function set_curl_info() {

		if ( $this->curl_handle ) {
			$this->curl_info = curl_getinfo( $this->curl_handle );
		}
	}

	/**
	 * Perform the request and return the parsed response
	 *
	 * @since 1.4.4
	 * @see SV_WC_API_Base::perform_request()
	 * @param object $request class instance which implements \SV_WC_API_Request
	 * @throws Exception
	 * @throws Framework\SV_WC_API_Exception
	 * @return object class instance which implements \SV_WC_API_Response
	 */
	protected function perform_request( $request ) {

		// TODO: consider making this part of SV_WC_API_Base::perform_request() {2018-04-02 - justinstern}
		$this->set_request_content_type_header( $request->request_content_type() );
		$this->set_request_accept_header( $request->accept_content_type() );

		return parent::perform_request( $request );
	}


	/**
	 * Perform the request
	 *
	 * @since 1.2.0
	 * @see Framework\SV_WC_API_Base::do_remote_request()
	 * @param string $request_uri
	 * @param array $request_args
	 * @return array|WP_Error
	 */
	protected function do_remote_request( $request_uri, $request_args ) {

		// prefer a different trasport mechanism than the default?
		$restore_transport_defaults = false;
		if ( isset( $request_args['preferred_transport'] ) && 'fsockopen' === $request_args['preferred_transport'] && class_exists( 'WC_Jilt_Requests' ) ) {
			WC_Jilt_Requests::prefer_fsockopen_transport();
			$restore_transport_defaults = true;
		}

		unset( $request_args['preferred_transport'], $this->curl_handle, $this->curl_info );

		$this->set_request_header( 'x-jilt-requested-at', time() );

		$request = parent::do_remote_request( $request_uri, $request_args );

		if ( $restore_transport_defaults ) {
			$this->request_transport = WC_Jilt_Requests::get_transport_name();
			WC_Jilt_Requests::restore_transport_defaults();
		}

		return $request;
	}


	/**
	 * Alert other actors that a request has been performed. This is primarily used
	 * for request logging.
	 *
	 * Unfortunately we have to override this entire method just to add the
	 * 'transport' key to the request data.structure.
	 *
	 * @see Framework\SV_WC_API_Base::broadcast_request()
	 * @since 1.2.0
	 */
	protected function broadcast_request() {

		$request_body = $this->get_sanitized_request_body();

		if ( $request_body && 'application/json' === $this->get_request()->request_content_type() ) {
			$request_body = wp_json_encode( json_decode( $request_body ), JSON_PRETTY_PRINT );
		}

		$request_data = array(
			'method'     => $this->get_request_method(),
			'uri'        => $this->get_request_uri(),
			'user-agent' => $this->get_request_user_agent(),
			'headers'    => $this->get_sanitized_request_headers(),
			'body'       => $request_body,
			'transport'  => $this->get_request_transport(),
		);

		if ( isset( $this->curl_info ) && $this->curl_info ) {
			$request_data = array_merge(
				$request_data,
				array(
					'dns-resolution'  => $this->format_ms( $this->curl_info['namelookup_time'] ),
					'tcp-connect'     => $this->format_ms( $this->curl_info['connect_time']     - $this->curl_info['namelookup_time'], 3 ),
					'ssl-handshake'   => $this->format_ms( $this->curl_info['pretransfer_time'] - $this->curl_info['connect_time'], 1 ),
					'ttlb'            => $this->format_ms( $this->curl_info['total_time']       - $this->curl_info['pretransfer_time'], 10 ),
					'request-total'   => $this->format_ms( $this->curl_info['total_time'], 1 ),
				)
			);
		} else {
			$request_data['request_total'] = $this->format_ms( $this->get_request_duration() );
		}

		$response_data = array(
			'code'    => $this->get_response_code(),
			'message' => $this->get_response_message(),
			'headers' => $this->get_response_headers(),
			'body'    => $this->get_sanitized_response_body() ? $this->get_sanitized_response_body() : $this->get_raw_response_body(),
		);

		/**
		 * API Base Request Performed Action.
		 *
		 * Fired when an API request is performed via this base class. Plugins can
		 * hook into this to log request/response data.
		 *
		 * @param array $request_data {
		 *     @type string $method request method, e.g. POST
		 *     @type string $uri request URI
		 *     @type string $user-agent
		 *     @type string $headers request headers
		 *     @type string $body request body
		 *     @type string $duration in seconds
		 *     @type string $transport name of transport used, if known: 'cURL', 'fsockopen', or null (added in 1.2.0, supported in WP 4.6+)
		 * }
		 * @param array $response data {
		 *     @type string $code response HTTP code
		 *     @type string $message response message
		 *     @type string $headers response HTTP headers
		 *     @type string $body response body
		 * }
		 * @param Framework\SV_WC_API_Base $this instance
		 */
		do_action( 'wc_' . $this->get_api_id() . '_api_request_performed', $request_data, $response_data, $this );
	}


	/**
	 * Return the given $time  formatted in milliseconds, with 4 + $extra_width
	 * worth of leading whitespace
	 *
	 * @since 1.5.0
	 * @param float $time in seconds
	 * @param integer $extra_width amount of additional leading whitespace to include
	 * @return string $time formatted in millisecoonds, with leading
	 *   whitespace, e.g. ' 1000ms'
	 */
	private function format_ms( $time, $extra_width = 0 ) {
		$width = $extra_width + 4;
		return sprintf( "%{$width}dms", number_format( $time * 1000 ) );
	}


	/**
	 * Get the last used request transport
	 *
	 * This method is compatible with WordPress 4.6+
	 *
	 * @since 1.2.0
	 * @return String the last used transport name: 'cURL', 'fsockopen', or null
	 */
	public function get_request_transport() {

		if ( isset( $this->request_transport ) ) {
			return $this->request_transport;
		}

		if ( class_exists( 'WC_Jilt_Requests' ) ) {
			$this->request_transport = WC_Jilt_Requests::get_transport_name();
		}

		return $this->request_transport;
	}


	/**
	 * Get the request arguments and override the timeout
	 *
	 * @since 1.0.0
	 * @see SV_WC_API_Base::get_request_args()
	 * @return array
	 */
	protected function get_request_args() {

		return array_merge( parent::get_request_args(), array( 'timeout' => 3 ) );
	}


	/**
	 * Perform a custom sanitization of the Authorization header, with a partial
	 * masking rather than the full mask of the base API class
	 *
	 * @since 1.0.0
	 * @see SV_WC_API_Base::get_sanitized_request_headers()
	 * @return array of sanitized request headers
	 */
	protected function get_sanitized_request_headers() {

		$sanitized_headers = parent::get_sanitized_request_headers();

		$headers = $this->get_request_headers();

		if ( ! empty( $headers['Authorization'] ) ) {
			list( $_, $credential ) = explode( ' ', $headers['Authorization'] );
			if ( strlen( $credential ) > 7 ) {
				$sanitized_headers['Authorization'] = $this->auth_scheme . ' ' . substr( $credential, 0, 2 ) . str_repeat( '*', strlen( $credential ) - 7 ) . substr( $credential, -4 );
			} else {
				// invalid key, no masking required
				$sanitized_headers['Authorization'] = $headers['Authorization'];
			}
		}

		return $sanitized_headers;
	}


	/**
	 * Builds and returns a new API request object
	 *
	 * @since 1.0.0
	 *
	 * @see \SV_WC_API_Base::get_new_request()
	 *
	 * @param array $args {
	 *     Associative array of request arguments.
	 *
	 *     @type string $method the request's HTTP method
	 *     @type string $path the request path
	 *     @type string $type (optional) the request type: one of 'api' or 'oauth2', defaults to 'api'
	 *     @type array $params (optional) associative array of query parameters
	 * }
	 * @return \WC_Jilt_API_Request|\WC_Jilt_API_OAuth2_Request API request object
	 * @throws Framework\SV_WC_API_Exception
	 */
	protected function get_new_request( $args = array() ) {

		$args = wp_parse_args( $args, array(
			'method' => '',
			'path'   => '',
			'type'   => 'api',
		) );

		// OAuth2 request
		if ( 'oauth2' === $args['type'] ) {

			$this->set_response_handler( 'WC_Jilt_API_OAuth2_Response' );

			return new WC_Jilt_API_OAuth2_Request( $args['method'], $args['path'] );
		}

		// regular API requests
		elseif ( 'api' === $args['type'] ) {

			if ( $this->auth_token instanceof WC_Jilt_OAuth_Access_Token ) {
				$this->maybe_refresh_access_token();
			}

			// ensure we don't send any requests when there is no auth token
			if ( ! $this->auth_token ) {
				throw new Framework\SV_WC_API_Exception( __( 'Missing authentication token', 'jilt-for-woocommerce' ) );
			}

			$this->set_response_handler( 'WC_Jilt_API_Response' );

			return new WC_Jilt_API_Request(
				$args['method'],
				$args['path'],
				isset( $args['params'] ) ? $args['params'] : array(),
				isset( $args['data'] ) ? $args['data'] : array()
			);
		}
	}


	/**
	 * Returns the main plugin class
	 *
	 * @since 1.0.0
	 * @see Framework\SV_WC_API_Base::get_plugin()
	 * @return \WC_Jilt
	 */
	protected function get_plugin() {
		return wc_jilt();
	}


	/**
	 * Get the API endpoint URI
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public function get_api_endpoint() {

		return sprintf( 'https://%s/%s', wc_jilt()->get_api_hostname(), self::get_api_version() );
	}


	/**
	 * Returns the Jilt OAuth endpoint.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_oauth_endpoint() {

		return wc_jilt()->get_app_endpoint( 'oauth' );
	}


	/**
	 * Returns the Jilt Connect endpoint.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_connect_endpoint() {

		return wc_jilt()->get_app_endpoint( 'connect/woocommerce' );
	}


	/**
	 * Returns the public orders API endpoint.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_orders_endpoint() {

		return sprintf( '%s/shops/%s/orders/', $this->get_api_endpoint(), $this->get_shop_id() );
	}


	/**
	 * Return a friendly representation of the API version in use
	 *
	 * @since 1.0.0
	 * @return string
	 */
	public static function get_api_version() {

		return 'v' . self::API_VERSION;
	}


	/**
	 * Get the current shop id
	 *
	 * @since 1.2.0
	 *
	 * @return string shop id
	 */
	public function get_shop_id() {
		return $this->shop_id;
	}


	/**
	 * Set the current shop id
	 *
	 * @since 1.2.0
	 *
	 * @param string $shop_id
	 */
	public function set_shop_id( $shop_id ) {
		$this->shop_id = $shop_id;
	}


	/**
	 * Get the current API key
	 *
	 * @since 1.2.0
	 * @return string current api key
	 */
	public function get_secret_key() {

		/* @deprecated since 1.4.0 */
		_deprecated_function( 'WC_Jilt_API::get_secret_key()', '1.4.0', 'WC_Jilt_API::get_auth_token()' );

		return $this->get_auth_token();
	}


	/**
	 * Returns the current auth token.
	 *
	 * @since 1.4.0
	 *
	 * @return string|\WC_Jilt_OAuth_Access_Token current auth token
	 */
	public function get_auth_token() {
		return $this->auth_token;
	}


	/**
	 * Returns the current auth scheme.
	 *
	 * @since 1.4.0
	 *
	 * @return string current auth scheme
	 */
	public function get_auth_scheme() {
		return $this->auth_scheme;
	}


	/**
	 * Sets the authorization header for API requests.
	 *
	 * @since 1.4.0
	 */
	private function set_authorization_header() {

		if ( ! $this->auth_token ) {
			return;
		}

		$token = is_string( $this->auth_token ) ? $this->auth_token : $this->auth_token->get_token();

		$this->set_request_header( 'Authorization', $this->auth_scheme . ' ' . $token );
	}


	/** OAuth 2.0 Methods *****************************************************/


	/**
	 * Requests installation-specific OAuth client credentials from Jilt
	 *
	 * @since 1.4.0
	 *
	 * @param string $domain the shop domain
	 * @param string $redirect_uri the redirect uri
	 * @return stdClass the client credentials returned by Jilt
	 * @throws Framework\SV_WC_API_Exception on API error
	 */
	public function get_client_credentials( $domain, $redirect_uri ) {

		$this->request_uri = $this->get_connect_endpoint();

		$request = $this->get_new_request( array(
			'type'   => 'oauth2',
			'method' => 'POST',
			'path'   => '/client',
		) );

		$request->set_connect_data( $domain, $redirect_uri );

		return $this->perform_request( $request );
	}


	/**
	 * Exchanges the authorization code for an access token & refresh token.
	 *
	 * @since 1.4.0
	 *
	 * @param string $code authorization code, returned after the user authorizes the plugin
	 * @param string $redirect_uri the redirect uri
	 * @param string $client_id the OAuth client id
	 * @param string $client_secret the OAuth client secret
	 * @throws Framework\SV_WC_API_Exception
	 * @return stdClass
	 */
	public function get_oauth_tokens( $code, $redirect_uri, $client_id, $client_secret ) {

		$this->request_uri = $this->get_oauth_endpoint();

		$request = $this->get_new_request( array(
			'type'   => 'oauth2',
			'method' => 'POST',
			'path'   => '/token',
		) );

		$request->set_authorization_data( $code, $redirect_uri, $client_id, $client_secret );

		return $this->perform_request( $request );
	}


	/**
	 * Refreshes the OAuth2 access token.
	 *
	 * @since 1.4.0
	 *
	 * @throws Framework\SV_WC_API_Exception
	 * @return stdClass
	 */
	public function refresh_oauth_token() {

		$this->request_uri = $this->get_oauth_endpoint();

		$request = $this->get_new_request( array(
			'type'   => 'oauth2',
			'method' => 'POST',
			'path'   => '/token',
		) );

		$request->set_refresh_data( $this->auth_token->get_refresh_token() );

		return $this->perform_request( $request );
	}


	/**
	 * Revokes the OAuth2 access token.
	 *
	 * @since 1.4.0
	 *
	 * @param string $client_id the OAuth client id
	 * @param string $client_secret the OAuth client secret
	 * @throws Framework\SV_WC_API_Exception
	 * @return stdClass
	 */
	public function revoke_oauth_token( $client_id, $client_secret ) {

		$this->request_uri = $this->get_oauth_endpoint();

		$request = $this->get_new_request( array(
			'type'   => 'oauth2',
			'method' => 'POST',
			'path'   => '/revoke',
		) );

		$request->set_revoke_data( $this->get_auth_token()->get_token(), $client_id, $client_secret );

		return $this->perform_request( $request );
	}


	/**
	 * Refreshes the OAuth 2 access token if it's expired.
	 *
	 * @since 1.4.0
	 *
	 * @param bool $force (optional) whether to force refreshing the access token or not, defaults to false
	 * @return bool true if the token was successfully refreshed, false otherwise
	 */
	protected function maybe_refresh_access_token( $force = false ) {

		$refreshed = false;
		$request_uri = $this->request_uri;

		if ( $force || $this->auth_token->is_expired() ) {

			try {

				$response     = $this->refresh_oauth_token();
				$access_token = json_decode( json_encode( $response->response_data ), true ); // convert stdClass to array

				// we already know the shop id and uuid
				unset( $access_token['shop_id'], $access_token['shop_uuid'] );

				$this->get_plugin()->get_integration()->set_access_token( $access_token );

				// set the auth token on api client and update the auth header
				$this->auth_token = $this->get_plugin()->get_integration()->get_access_token();

				$this->set_authorization_header();

				// success!
				$refreshed = true;

			} catch ( Framework\SV_WC_API_Exception $e ) {

				wc_jilt()->get_logger()->error( 'Could not refresh access token. ' . $e->getMessage(), $this->get_plugin()->get_id() );
			}
		}

		$this->request_uri = $request_uri;

		return $refreshed;
	}


	/**
	 * Get the full client request url, e.g. https://example.com/checkout/
	 *
	 * @since 1.4.0
	 * @return string the request url
	 */
	private function get_client_request_url() {

		if ( isset( $_SERVER['REQUEST_SCHEME'] ) ) {
			$scheme = $_SERVER['REQUEST_SCHEME'];
		} elseif ( isset( $_SERVER['HTTPS'] ) && 'on' == $_SERVER['HTTPS'] ) {
			$scheme = 'https';
		} else {
			$scheme = 'http';
		}

		return "{$scheme}://{$_SERVER['HTTP_HOST']}{$_SERVER['REQUEST_URI']}";
	}


}
