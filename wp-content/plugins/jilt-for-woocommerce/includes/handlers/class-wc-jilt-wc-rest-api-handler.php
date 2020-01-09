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
 * @category  Frontend
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * The WC REST API handler.
 *
 * Adds custom routes and extra data to existing routes for the core WC REST API.
 *
 * @since 1.5.0
 */
class WC_Jilt_WC_REST_API_Handler {


	/**
	 * Sets up the API handle class.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// fix bug that sometimes prevents REST API authentication
		add_action( 'after_setup_theme', array( $this, 'ensure_no_current_user_for_rest_api_requests' ), 99 );

		// register the custom WC REST API routes
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );

		// add any meta queries necessary for custom Jilt params
		add_filter( 'woocommerce_rest_shop_order_object_query', array( $this, 'add_order_meta_query' ), 10, 2 );

		// add Jilt data to the WC REST API order response
		add_filter( 'woocommerce_rest_prepare_shop_order_object', array( $this, 'add_order_data' ), 10, 3 );

		// add last order and other user data to the WC REST API customer response
		add_filter( 'woocommerce_rest_prepare_customer', array( $this, 'add_customer_data' ), 10, 2 );

		// add Jilt data to the WC REST API system status response
		add_filter( 'woocommerce_rest_prepare_system_status', array( $this, 'add_system_status_shop_data' ), 10, 3 );
	}


	/**
	 * Makes sure the $current_user global is not set for WC REST API requests.
	 *
	 * This fixes a bug where the $current_user global has been modified by some
	 * third party and set to `0` when it shouldn't be set at all. This prevents WP
	 * from firing the `determine_current_user` action, which WooCommerce relies on
	 * to fire its `authenticate` method. This, in turn, results in 401 Unauthorized
	 * responses on properly-authenticated requests to the WC REST API.
	 *
	 * @internal
	 *
	 * @since 1.5.2
	 */
	public function ensure_no_current_user_for_rest_api_requests() {
		global $current_user;

		if ( $current_user instanceof \WP_User && 0 === (int) $current_user->ID ) {

			$parsed_url = wp_parse_url( rest_url() );

			if ( $parsed_url && isset( $parsed_url['path'] ) ) {

				$wc_rest_api_base = $parsed_url['path'] . 'wc/';

				if ( isset( $_SERVER['REQUEST_URI'] ) && 0 === strpos( $_SERVER['REQUEST_URI'], $wc_rest_api_base ) ) {

					unset( $current_user );
				}
			}
		}
	}


	/**
	 * Registers the custom WC REST API routes.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 */
	public function register_rest_routes() {

		require_once( wc_jilt()->get_plugin_path() . '/includes/api/rest/class-wc-jilt-rest-settings-controller.php' );
		require_once( wc_jilt()->get_plugin_path() . '/includes/api/rest/class-wc-jilt-rest-debug-log-controller.php' );

		$controllers = array(
			'WC_Jilt_REST_Settings_Controller',
			'WC_Jilt_REST_Debug_Log_Controller',
		);

		foreach ( $controllers as $controller ) {
			$this->$controller = new $controller();
			$this->$controller->register_routes();
		}
	}


	/**
	 * Adds any meta queries necessary for custom Jilt params.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param array $query_args core query args
	 * @param \WP_REST_Request $request request object
	 * @return array
	 */
	public function add_order_meta_query( $query_args, $request ) {

		$params = $request->get_params();

		// try by cart token
		if ( ! empty( $params['jilt_cart_token'] ) ) {

			$query_args['meta_query'] = array(
				array(
					'key'     => '_wc_jilt_cart_token',
					'value'   => $params['jilt_cart_token'],
					'compare' => '=',
				),
			);

		// try by Jilt order ID
		} elseif ( ! empty( $params['jilt_id'] ) ) {

			$query_args['meta_query'] = array(
				array(
					'key'     => '_wc_jilt_order_id',
					'value'   => $params['jilt_id'],
					'compare' => '=',
				),
			);
		}

		return $query_args;
	}


	/**
	 * Adds Jilt data to the WC REST API order response.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_REST_Response $response response object
	 * @param \WC_Order $order order object
	 * @param \WP_REST_Request $request the original request object
	 * @return \WP_REST_Response
	 */
	public function add_order_data( $response, $order, $request ) {

		$response_data = $response->data;

		// add custom properties to line items
		if ( ! empty( $response_data['line_items'] ) && is_array( $response_data['line_items' ] ) ) {

			foreach ( $response_data['line_items'] as $key => $item ) {

				$product_id = ! empty( $item['variation_id'] ) ? $item['variation_id'] : $item['product_id'];

				if ( $product = wc_get_product( $product_id ) ) {

					$response_data['line_items'][ $key ]['url']       = get_the_permalink( $product->get_id() );
					$response_data['line_items'][ $key ]['image_url'] = WC_Jilt_Product::get_product_image_url( $product );
				}
			}
		}

		// add custom properties to fee lines
		if ( ! empty( $response_data['fee_lines'] ) && is_array( $response_data['fee_lines' ] ) ) {

			foreach ( $response_data['fee_lines'] as $key => $fee ) {

				if ( ! empty( $fee['name'] ) ) {
					$response_data['fee_lines'][ $key ]['key'] = sanitize_title( $fee['name'] );
				}
			}
		}

		try {

			$order = new WC_Jilt_Order( $order->get_id() );

			$cart_token = $order->get_jilt_cart_token();

			$response_data['jilt'] = array(
				'admin_url'          => $order->get_order_edit_url(),
				'financial_status'   => $order->get_financial_status(),
				'fulfillment_status' => $order->get_fulfillment_status(),
				'requires_shipping'  => $order->needs_shipping(),
				'placed_at'          => $order->get_jilt_placed_at(),
				'cancelled_at'       => $order->get_jilt_cancelled_at(),
				'test'               => $order->is_test(),
				'customer_admin_url' => $order->get_customer_id() ? esc_url_raw( add_query_arg( array( 'user_id' => $order->get_customer_id() ), self_admin_url( 'user-edit.php' ) ) ) : '',
				'cart_token'         => $cart_token,
				'checkout_url'       => is_callable( 'WC_Jilt_Checkout_Handler::get_checkout_recovery_url' ) ? WC_Jilt_Checkout_Handler::get_checkout_recovery_url( $cart_token ) : '',
			);


			// add download information to response if available
			if ( $order->has_downloadable_item() && $order->is_download_permitted() ) {

				$response_data['jilt']['downloads'] = $this->get_downloadable_items( $order );
			}

		} catch ( Exception $e ) {

			wc_jilt()->get_logger()->debug( 'Could not add REST API data to order: ' . $e->getMessage() );
		}

		$response->data = $response_data;

		return $response;
	}


	/**
	 * Gets the download file information for an order. Based off of WC_Order::get_downloadable_items().
	 *
	 * @since 1.6.1
	 *
	 * @param \WC_Order $order order object
	 * @return array the download data
	 */
	protected function get_downloadable_items( $order ) {

		$downloads = array();

		foreach ( $order->get_items() as $item ) {

			if ( ! is_object( $item ) ) {
				continue;
			}

			if ( $item->is_type( 'line_item' ) ) {

				$item_downloads = $item->get_item_downloads();
				$product        = $item->get_product();

				if ( $product && $item_downloads ) {

					foreach ( $item_downloads as $file ) {

						$downloads[] = array(
							'line_item_id'        => $item->get_id(),
							'product_id'          => $product->get_id(),
							'download_url'        => $file['download_url'],
							'download_name'       => $file['name'],
							'download_id'         => $file['id'],
							'downloads_remaining' => $file['downloads_remaining'],
							'access_expires'      => $file['access_expires'],
						);
					}
				}
			}
		}

		/**
		 * Filters the download data sent to Jilt for an order.
		 *
		 * @since 1.6.1
		 *
		 * @param array $downloads the download data
		 * @param \WC_Order $order the order object
		 */
		return (array) apply_filters( 'wc_jilt_wc_rest_api_order_downloads', $downloads, $order );
	}


	/**
	 * Remove the Jilt data from order webhook payloads for non-Jilt destinations.
	 *
	 * @internal
	 *
	 * @since 1.5.6
	 *
	 * @param array $payload webhook payload data
	 * @param string $resource webhook resource
	 * @param int $resource_id resource ID like order ID
	 * @param int $webhook_id webhook ID
	 * @return array
	 */
	public function maybe_remove_webhook_data( $payload, $resource, $resource_id, $webhook_id ) {

		try {

			// lots of logic needed to sanity check the webhook ID here since the webhook object handling changed in WC 3.3+
			if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.3' ) ) {

				// WC can throw an exception here
				$webhook = wc_get_webhook( $webhook_id );

			} else {

				$webhook = new \WC_Webhook( $webhook_id );

				if ( ! $webhook->get_delivery_url() ) {
					throw new \Exception( 'Invalid webhook ID' );
				}
			}

			// if the delivery URL isn't pointing to the Jilt app, remove the Jilt API data
			if ( $webhook && 'order' === $resource && false === strpos( $webhook->get_delivery_url(), wc_jilt()->get_app_hostname() ) ) {
				unset( $payload['jilt'] );
			}

		} catch ( \Exception $exception ) {} // nothing we need to do in this case

		return $payload;
	}


	/**
	 * Adds data to the WC REST API customer response.
	 *
	 * @internal
	 *
	 * @since 1.5.6
	 *
	 * @param \WP_REST_Response $response response object
	 * @param \WP_User $customer user data
	 * @return \WP_REST_Response
	 */
	public function add_customer_data( $response, $customer ) {

		$data = (array) $response->get_data();

		if ( ! isset( $data['jilt'] ) ) {
			$data['jilt'] = array();
		}

		$data['jilt']['last_order_id']     = wc_jilt()->get_customer_handler_instance()->get_customer_last_order_id( $customer );
		$data['jilt']['last_order_number'] = wc_jilt()->get_customer_handler_instance()->get_customer_last_order_number( $customer );

		$response->set_data( $data );

		return $response;
	}


	/**
	 * Adds Jilt data to the WC REST API system status response.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_REST_Response $response response object
	 * @param array $system_status system status data
	 * @param \WP_REST_Request $request the original request object
	 * @return \WP_REST_Response
	 */
	public function add_system_status_shop_data( $response, $system_status, $request ) {

		$response->data['settings']->coupons_enabled = wc_coupons_enabled();
		$response->data['settings']->taxes_included  = 'incl' === get_option( 'woocommerce_tax_display_cart' );

		foreach ( wc_jilt()->get_integration()->get_shop_address() as $key => $val ) {

			if ( ! isset( $response->data['settings']->$key ) ) {

				$response->data['settings']->$key = $val;
			}
		}

		$response->data['environment']->admin_url = admin_url();

		$jilt_api_auth_method = 'secret_key' === wc_jilt()->get_integration()->get_auth_method() ? 'secret key' : 'oauth';

		$jilt_enabled = ! ( ! wc_jilt()->get_integration()->has_connected() || ! wc_jilt()->get_integration()->is_linked() || wc_jilt()->get_integration()->is_disabled() || wc_jilt()->get_integration()->is_duplicate_site() );

		$response->data['jilt'] = array(
			'shop_name'               => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'domain'                  => wc_jilt()->get_shop_domain(),
			'wordpress_site_url'      => get_home_url(), // including install directory, if any
			'wordpress_rest_url'      => rest_url(),
			'wc_created_at'           => wc_jilt()->get_wc_created_at(),
			'version'                 => wc_jilt()->get_version(),
			'free_shipping_available' => wc_jilt()->get_integration()->is_free_shipping_available(),
			'jilt_api_version'        => WC_Jilt_API::get_api_version(),
			'jilt_api_auth_method'    => $jilt_api_auth_method,
			'jilt_api_connected'      => wc_jilt()->get_integration()->has_connected(),
			'linked_to_jilt'          => wc_jilt()->get_integration()->is_linked(),
			'enabled'                 => $jilt_enabled,
		);

		if ( ! $jilt_enabled && wc_jilt()->get_integration()->is_duplicate_site() ) {
			$response->data['jilt']['enabled_reason'] = "Site duplication detected: original domain '" . wc_jilt()->get_integration()->get_linked_shop_domain() . "'";
		}

		if ( wc_jilt()->get_integration()->is_jilt_connected() ) {

			$api_is_configured = $this->is_configured();

			$response->data['jilt']['wc_api'] = $api_is_configured;

			if ( ! $api_is_configured ) {

				$response->data['jilt']['wc_api_reason'] = $this->get_api_configuration_error_short();
			}
		}

		return $response;
	}


	/**
	 * Gets a brief text error message describing REST API configuration
	 * issues.
	 *
	 * Appropriate for help tips.
	 *
	 * @since 1.5.1
	 *
	 * @return string
	 */
	public function get_api_configuration_error_short() {

		$message = '';

		$rest_api_status = $this->get_rest_api_status();

		if ( ! $this->permalinks_configured() ) {

			$message = 'Pretty permalinks are disabled.';

		} elseif ( $this->legacy_wc_rest_api_needed() ) {

			$message = 'Legacy WooCommerce REST API is disabled.';

		} elseif ( ! $this->key_exists() ) {

			$message = 'API key does not exist.';

		} elseif ( ! $this->key_permissions_are_correct() ) {

			$message = 'API key permissions are not sufficient.';

		} elseif ( ! $this->key_owner_permissions_are_correct() ) {

			$message = 'API key owner permissions are not sufficient.';

		} elseif ( $rest_api_status['is_disabled'] ) {

			$message = $rest_api_status['reason'];
		}

		return $message;
	}


	/**
	 * Gets a verbose HTML error message describing REST API configuration
	 * issues.
	 *
	 * Includes an anchor tag linking to a solution page, if relevant.
	 *
	 * @since 1.5.1
	 *
	 * @return string
	 */
	public function get_api_configuration_error_long() {

		$message = '';

		// build a URL that will enabled the REST API and create a key if needed
		$url = wp_nonce_url( add_query_arg( 'action', 'wc_jilt_enable_wc_rest_api', admin_url( 'admin.php' ) ), 'wc_jilt_enable_wc_rest_api' );

		$rest_api_status = $this->get_rest_api_status();

		if ( ! $this->permalinks_configured() ) {

			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$message = sprintf(
				__( 'Pretty permalinks are disabled. %1$sPlease update your permalink settings%2$s to enable API access for Jilt.', 'jilt-for-woocommerce' ),
				'<a href="' . esc_url( admin_url( 'options-permalink.php' ) ) . '">', '</a>'
			);

		} elseif ( $this->legacy_wc_rest_api_needed() ) {

			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$message = sprintf(
				__( 'Legacy WooCommerce REST API is disabled. %1$sClick here%2$s to enable legacy API.', 'jilt-for-woocommerce' ),
				'<a href="' . $url . '">', '</a>'
			);

		} elseif ( ! $this->key_exists() ) {

			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$message = sprintf(
				__( 'API key does not exist. %1$sClick here%2$s to create a WooCommerce API key for Jilt.', 'jilt-for-woocommerce' ),
				'<a href="' . $url . '">', '</a>'
			);

		} elseif ( ! $this->key_permissions_are_correct() ) {

			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$message = sprintf(
				__( 'API key permissions are not sufficient. %1$sClick here%2$s to correct the WooCommerce API key for Jilt.', 'jilt-for-woocommerce' ),
				'<a href="' . $url . '">', '</a>'
			);

		} elseif ( ! $this->key_owner_permissions_are_correct() ) {

			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$message = sprintf(
				__( 'API key owner permissions are not sufficient. %1$sClick here%2$s to correct the WooCommerce API key for Jilt.', 'jilt-for-woocommerce' ),
				'<a href="' . $url . '">', '</a>'
			);

		} elseif ( $rest_api_status['is_disabled'] ) {

			$message = $rest_api_status['reason'];
		}

		return $message;
	}


	/** API Key Handling Methods **********************************************/


	/**
	 * Determines if the WC REST API is configured and ready for Jilt by checking that:
	 *
	 * - permalinks settings are correct
	 * - the legacy WC REST API is enabled (if needed based on WC version)
	 * - an appropriately permissioned API key exists
	 * - the key owner has permissions to manage woocommerce
	 * - no known third parties are disabling the API
	 *
	 * @since 1.5.0
	 *
	 * @return bool true if the WC REST API is configured and available
	 */
	public function is_configured() {

		$rest_api_status = $this->get_rest_api_status();

		return $this->permalinks_configured()
			&& ! $this->legacy_wc_rest_api_needed()
			&& $this->has_valid_key()
			&& ! $rest_api_status['is_disabled'];
	}


	/**
	 * Checks if permalinks are correctly configured to support the WC REST API
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function permalinks_configured() {

		return '' !== get_option( 'permalink_structure' );
	}


	/**
	 * Checks if the legacy WC REST API needs to be enabled, based on WC version
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function legacy_wc_rest_api_needed() {

		return Framework\SV_WC_Plugin_Compatibility::is_wc_version_lt( '3.4' ) && 'yes' !== get_option( 'woocommerce_api_enabled' );
	}


	/**
	 * Enables the legacy WC REST API
	 *
	 * @since 1.5.0
	 */
	public function enable_legacy_wc_rest_api() {

		update_option( 'woocommerce_api_enabled', 'yes' );
	}


	/**
	 * Checks if the WC REST API key exists
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function key_exists() {

		return $this->get_key() !== null;
	}


	/**
	 * Determines if the WC REST API key exists with appropriate permissions
	 * and that the key owner has appropriate permissions
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function has_valid_key() {

		return $this->key_permissions_are_correct() && $this->key_owner_permissions_are_correct();
	}


	/**
	 * Determines if the integration has a valid WC REST API key configured.
	 *
	 * This checks that a key exists, and that has the proper permissions.
	 *
	 * @since 1.5.0
	 *
	 * @return bool|null
	 */
	public function key_permissions_are_correct() {

		$key = $this->get_key();

		if ( null === $key ) {
			return null;
		}

		return ! empty( $key->permissions ) && 'read_write' === $key->permissions;
	}


	/**
	 * Correct the existing REST API permissions.
	 *
	 * @since 1.5.0
	 *
	 * @return bool true on success, false if the key doesn't exist
	 * @throws Framework\SV_WC_Plugin_Exception if the key record can't be updated
	 */
	public function correct_key_permissions() {
		global $wpdb;

		$key_id = $this->get_key_id();

		if ( ! $key_id ) {
			return false;
		}

		$result = $wpdb->update(
			$wpdb->prefix . 'woocommerce_api_keys',
			array( 'permissions' => 'read_write' ),
			array( 'key_id'      => $key_id )
		);

		if ( ! $result ) {
			throw new Framework\SV_WC_Plugin_Exception( 'The key could not be updated' );
		}

		return true;
	}


	/**
	 * Verifies that the key owner has correct permissions: can manage woocommerce
	 *
	 * @since 1.5.0
	 *
	 * @return boolean|null
	 */
	public function key_owner_permissions_are_correct() {

		$key = $this->get_key();

		if ( null === $key ) {
			return null;
		}

		return user_can( $key->user_id, 'manage_woocommerce' );
	}


	/**
	 * Gets the WC REST API status details.
	 *
	 * Returns whether the REST API is disabled, and a reason.
	 *
	 * @since 1.5.1
	 *
	 * @return array
	 */
	public function get_rest_api_status() {

		/**
		 * Filters the WC REST API status.
		 *
		 * @since 1.5.1
		 *
		 * @param array $details {
		 *     WC REST API status details.
		 *
		 *     @type bool $is_disabled whether the REST API is disabled
		 *     @type string|null $reason the reason for the REST API's current status
		 * }
		 */
		return apply_filters( 'wc_jilt_wc_rest_api_status', array( 'is_disabled' => false, 'reason' => null ) );
	}


	/**
	 * Refreshes the WC REST API key.
	 *
	 * @since 1.5.0
	 *
	 * @param int $user_id WordPress user ID
	 * @return object|bool
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function refresh_key( $user_id = null ) {

		$this->revoke_key();

		return $this->create_key( $user_id );
	}


	/**
	 * Generates a WC REST API key for Jilt to use.
	 *
	 * @since 1.5.0
	 *
	 * @param int $user_id WordPress user ID
	 * @return object
	 * @throws Framework\SV_WC_Plugin_Exception
	 */
	public function create_key( $user_id = null ) {
		global $wpdb;

		// if no user is specified, try the current user or find an eligible admin
		if ( ! $user_id ) {

			$user_id = get_current_user_id();

			// if the current user can't manage WC, try and get the first admin
			if ( ! user_can( $user_id, 'manage_woocommerce' ) ) {

				$user_id = null;

				$administrator_ids = get_users( array(
					'role'   => 'administrator',
					'fields' => 'ID',
				) );

				foreach ( $administrator_ids as $administrator_id ) {

					if ( user_can( $administrator_id, 'manage_woocommerce' ) ) {

						$user_id = $administrator_id;
						break;
					}
				}

				if ( ! $user_id ) {
					throw new Framework\SV_WC_Plugin_Exception( 'No eligible users could be found' );
				}
			}

		// otherwise, check the user that's specified
		} elseif ( ! user_can( $user_id, 'manage_woocommerce' ) ) {

			throw new Framework\SV_WC_Plugin_Exception( "User {$user_id} does not have permission" );
		}

		$user = get_userdata( $user_id );

		if ( ! $user ) {
			throw new Framework\SV_WC_Plugin_Exception( 'Invalid user' );
		}

		$consumer_key    = 'ck_' . wc_rand_hash();
		$consumer_secret = 'cs_' . wc_rand_hash();

		$description = __( 'Jilt for WooCommerce', 'jilt-for-woocommerce' );

		$result = $wpdb->insert(
			$wpdb->prefix . 'woocommerce_api_keys',
			array(
				'user_id'         => $user->ID,
				'description'     => $description,
				'permissions'     => 'read_write',
				'consumer_key'    => wc_api_hash( $consumer_key ),
				'consumer_secret' => $consumer_secret,
				'truncated_key'   => substr( $consumer_key, -7 ),
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%s',
				'%s',
			)
		);

		if ( ! $result ) {
			throw new Framework\SV_WC_Plugin_Exception( 'The key could not be saved' );
		}

		$key = new \stdClass();

		$key->key_id          = $wpdb->insert_id;
		$key->user_id         = $user->ID;
		$key->consumer_key    = $consumer_key;
		$key->consumer_secret = $consumer_secret;

		// store the new key ID
		$this->set_key_id( $key->key_id );

		return $key;
	}


	/**
	 * Revokes the configured WC REST API key.
	 *
	 * @since 1.5.0
	 */
	public function revoke_key() {
		global $wpdb;

		if ( $key_id = $this->get_key_id() ) {
			$wpdb->delete( $wpdb->prefix . 'woocommerce_api_keys', array( 'key_id' => $key_id ), array( '%d' ) );
		}

		delete_option( 'wc_jilt_wc_api_key_id' );
	}


	/**
	 * Gets the configured WC REST API key.
	 *
	 * @since 1.5.0
	 *
	 * @return object|null
	 */
	public function get_key() {
		global $wpdb;

		$key = null;

		if ( $id = $this->get_key_id() ) {

			$key = $wpdb->get_row( $wpdb->prepare( "
				SELECT key_id, user_id, permissions, consumer_secret
				FROM {$wpdb->prefix}woocommerce_api_keys
				WHERE key_id = %d
			", $id ) );
		}

		return $key;
	}


	/**
	 * Gets the configured WC REST API key ID.
	 *
	 * @since 1.5.0
	 *
	 * @return int
	 */
	public function get_key_id() {

		return (int) get_option( 'wc_jilt_wc_api_key_id' );
	}


	/**
	 * Sets a WC REST API key ID.
	 *
	 * @since 1.5.0
	 *
	 * @param int $id key ID
	 */
	public function set_key_id( $id ) {

		update_option( 'wc_jilt_wc_api_key_id', $id );
	}


}
