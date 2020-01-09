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
 * WooCommerce Jilt Main Plugin Class
 *
 * @since 1.0.0
 */
class WC_Jilt extends Framework\SV_WC_Plugin {


	/** plugin version number */
	const VERSION = '1.6.4';

	/** plugin id */
	const PLUGIN_ID = 'jilt';

	/** the app hostname */
	const HOSTNAME = 'jilt.com';

	/** @var \WC_Jilt_Integration instance */
	protected $integration;

	/** @var \WC_Jilt_Admin_Status instance */
	protected $admin_status;

	/** @var \WC_Jilt_Customer_Handler instance */
	protected $customer_handler;

	/** @var \WC_Jilt_Cart_Handler instance */
	protected $cart_handler;

	/** @var \WC_Jilt_Checkout_Handler instance */
	protected $checkout_handler;

	/** @var \WC_Jilt_Managed_Email_Notifications_Handler instance */
	protected $managed_email_notifications_handler;

	/** @var  \WC_Jilt_WC_API_Handler instance */
	protected $wc_api_handler;

	/** @var  \WC_Jilt_WC_REST_API_Handler instance */
	protected $wc_rest_api_handler;

	/** @var \WC_Jilt_Frontend instance */
	protected $frontend;

	/** @var \WC_Jilt_Logger the logger */
	private $logger;

	/** @var \WC_Jilt_Integrations instance */
	protected $integrations;


	/**
	 * Initializes the plugin
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		// the dedicated logger proof of concept needs to be loaded extremely early
		require_once( $this->get_plugin_path() . '/includes/class-wc-jilt-logger.php' );

		parent::__construct(
			self::PLUGIN_ID,
			self::VERSION,
			array(
				'text_domain' => 'jilt-for-woocommerce',
			)
		);

		// Include required files
		$this->includes();

		// GDPR handling: log and send request to Jilt to unschedule emails
		add_filter( 'wp_privacy_personal_data_erasers', array( $this, 'register_personal_data_eraser' ) );

		$this->add_milestone_hooks();
	}


	/**
	 * Initializes the lifecycle handler.
	 *
	 * @since 1.5.4
	 */
	protected function init_lifecycle_handler() {

		require_once( $this->get_plugin_path() . '/includes/Lifecycle.php' );

		$this->lifecycle_handler = new Lifecycle( $this );
	}


	/**
	 * Include required files
	 *
	 * @since 1.0.0
	 */
	public function includes() {

		// base integration & related classes
		require_once( $this->get_plugin_path() . '/includes/class-wc-jilt-session.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-jilt-product.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-jilt-order.php' );
		require_once( $this->get_plugin_path() . '/includes/class-wc-jilt-webhook.php' );

		// load Jilt API classes
		require_once( $this->get_plugin_path() . '/includes/api/class-wc-jilt-api.php' );
		require_once( $this->get_plugin_path() . '/includes/api/class-wc-jilt-api-request.php' );
		require_once( $this->get_plugin_path() . '/includes/api/class-wc-jilt-api-response.php' );
		require_once( $this->get_plugin_path() . '/includes/api/class-wc-jilt-api-oauth2-request.php' );
		require_once( $this->get_plugin_path() . '/includes/api/class-wc-jilt-api-oauth2-response.php' );
		require_once( $this->get_plugin_path() . '/includes/api/class-wc-jilt-requests.php' );
		require_once( $this->get_plugin_path() . '/includes/api/class-wc-jilt-oauth-access-token.php' );

		require_once( $this->get_plugin_path() . '/includes/admin/class-wc-jilt-integration-admin.php' );

		add_action( 'woocommerce_init', array( $this, 'add_api_request_logging' ) );

		$this->integration = $this->load_class( '/includes/class-wc-jilt-integration.php', 'WC_Jilt_Integration' );

		// customer handler must be loaded earlier than others to use woocommerce_init hook
		$this->customer_handler                    = $this->load_class( '/includes/handlers/class-wc-jilt-customer-handler.php', 'WC_Jilt_Customer_Handler' );
		$this->managed_email_notifications_handler = $this->load_class( '/includes/handlers/class-wc-jilt-managed-email-notifications-handler.php', 'WC_Jilt_Managed_Email_Notifications_Handler' );

		// frontend includes
		if ( ! defined( 'DOING_CRON' ) && ! is_admin() ) {
			add_action( 'init', array( $this, 'frontend_includes' ) );
		}

		// admin includes
		if ( is_admin() && ! is_ajax() ) {

			$this->admin_includes();

			// add configure/connect and support links to plugin install screen when already installed
			add_filter( 'plugin_install_action_links', array( $this, 'plugin_install_action_links' ), 10, 2 );
		}

		// WC REST API handler
		$this->wc_rest_api_handler = $this->load_class( '/includes/handlers/class-wc-jilt-wc-rest-api-handler.php', 'WC_Jilt_WC_REST_API_Handler' );

		// 3rd party integrations
		require_once( $this->get_plugin_path() . '/includes/integrations/class-wc-jilt-integrations.php' );

		$this->integrations = new \WC_Jilt_Integrations( $this );
	}


	/**
	 * Include required frontend files
	 *
	 * @since 1.0.0
	 */
	public function frontend_includes() {

		if ( $this->get_integration()->is_linked() ) {

			// cart/checkout handlers
			$this->cart_handler     = $this->load_class( '/includes/handlers/class-wc-jilt-cart-handler.php', 'WC_Jilt_Cart_Handler' );
			$this->checkout_handler = $this->load_class( '/includes/handlers/class-wc-jilt-checkout-handler.php', 'WC_Jilt_Checkout_Handler' );
		}

		// WC API: do our best to handle requests even when the plugin is not linked
		$this->wc_api_handler = $this->load_class( '/includes/handlers/class-wc-jilt-wc-api-handler.php', 'WC_Jilt_WC_API_Handler' );

		// always load the frontend class as it doesn't require a connection
		$this->frontend = $this->load_class( '/includes/frontend/class-wc-jilt-frontend.php', 'WC_Jilt_Frontend' );
	}


	/**
	 * Include required admin files
	 *
	 * @since 1.0.0
	 */
	public function admin_includes() {

		$this->admin_status = $this->load_class( '/includes/admin/class-wc-jilt-admin-status.php', 'WC_Jilt_Admin_Status' );
	}


	/** Admin methods ******************************************************/


	/**
	 * Render a notice for the user to read the docs before adding add-ons
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::add_delayed_admin_notices()
	 */
	public function add_delayed_admin_notices() {

		// show any dependency notices
		parent::add_delayed_admin_notices();

		// warn users if random_bytes is unavailable
		try {
			random_bytes(1);
		} catch ( \Exception $e ) {
			$this->get_admin_notice_handler()->add_admin_notice(
				__( 'Jilt works best with the random_bytes() PHP function, which is not available on your site. Please ask your hosting provider to add support for random_bytes().', 'jilt-for-woocommerce' ),
				'random-bytes-missing',
				array( 'notice_class' => 'error', 'always_show_on_settings' => true )
			);
		}

		// warn users if the site is not secured
		if ( ! wc_site_is_https() ) {
			$this->get_admin_notice_handler()->add_admin_notice(
				__( 'Your site is currently not secured with SSL/TLS. Please secure your site with a valid TLS certificate in order to maintain a secure connection with Jilt.', 'jilt-for-woocommerce' ),
				'missing-https',
				array(
					'notice_class' => 'error',
					'always_show_on_settings' => true
				)
			);
		}

		// warn users if we detect what looks like a local site
		if ( $this->is_local_site() ) {
			$this->get_admin_notice_handler()->add_admin_notice(
				__( 'Hey there! It looks like this site is running in a local environment. Jilt requires two-way communication with your site and may not connect or operate as expected.', 'jilt-for-woocommerce' ),
				'detected-local-site',
				array(
					'notice_class'            => 'notice-warning',
					'always_show_on_settings' => true
				)
			);
		}

		// warn if Jilt has notified us the billing on this account needs attention
		if ( $this->get_integration()->billing_needs_attention() ) {

			$this->get_admin_notice_handler()->add_admin_notice(
				/* translators: Placeholders: %1$s - <a> tag %2$s - </a> tag */
				sprintf( __( 'Uh oh! We\'ve detected a billing issue with your Jilt account, which will cause an interruption in service if not resolved. Please %1$sclick here to update your billing information%2$s.', 'jilt-for-woocommerce' ),
					'<a href="' . esc_url( wc_jilt()->get_app_endpoint( 'account/billing' ) ) . '" target="_blank">',
					'</a>'
				),
				'billing-needs-attention',
				array(
					'notice_class'            => 'notice-warning',
					'always_show_on_settings' => true,
				)
			);
		}

		if ( $this->get_integration()->is_jilt_connected() && ! $this->get_wc_rest_api_handler_instance()->is_configured() ) {

			// display a persistent notice if the WC REST API is unavailable or misconfigured
			$reason = $this->get_wc_rest_api_handler_instance()->get_api_configuration_error_long();

			$message = sprintf(
				/* translators: Placeholders: %1$s - connection error reason */
				__( 'Heads up! Jilt for WooCommerce is not able to communicate with the WooCommerce REST API: %1$s', 'jilt-for-woocommerce' ),
				$reason
			);

			$this->get_admin_notice_handler()->add_admin_notice(
				$message,
				'wc-rest-api-unavailable',
				array(
					'always_show_on_settings' => true,
					'notice_class'            => 'notice-error',
				)
			);

			return;
		}

		// no messages to display if the plugin is already configured
		if ( $this->get_integration()->is_configured() ) {

			// ...unless the shop is still using secret key authentication
			if ( 'secret_key' === $this->get_integration()->get_auth_method() ) {

				if ( $this->is_plugin_settings() ) {
					$message = __( "Heads up! There's a faster and more secure way to connect your shop to Jilt. Click the Reconnect button below to upgrade now.", 'jilt-for-woocommerce' );
				} else {
					// plugins page, link to settings
					/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
					$message = sprintf( __( 'There\'s a faster and more secure way to connect your shop to Jilt. %1$sReconnect your shop%2$s now to upgrade, it only takes 30 seconds :)', 'jilt-for-woocommerce' ), '<a href="' . esc_url( $this->get_settings_url() ) . '">', '</a>' );
				}

				$this->get_admin_notice_handler()->add_admin_notice(
					$message,
					'upgrade-auth-method-notice',
					array( 'always_show_on_settings' => true, 'notice_class' => 'notice-warning' )
				);
			}

			return;
		}

		$screen = get_current_screen();

		// plugins page, link to settings
		if ( null !== $screen && 'plugins' === $screen->id ) {
			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$message = sprintf( __( 'Thanks for installing Jilt! To get started, %1$sconnect your shop to Jilt%2$s :)', 'jilt-for-woocommerce' ), '<a href="' . esc_url( $this->get_settings_url() ) . '">', '</a>' );
		} elseif ( $this->is_plugin_settings() ) {
			$message = __( 'Thanks for installing Jilt! To get started, connect your shop to Jilt below :)', 'jilt-for-woocommerce' );
		}

		// only render on plugins or settings screen
		if ( ! empty( $message ) ) {
			$this->get_admin_notice_handler()->add_admin_notice(
				$message,
				'get-started-notice',
				array( 'always_show_on_settings' => false )
			);
		}
	}


	/**
	 * Returns the plugin install action links. This will only be called if the plugin
	 * is active.
	 *
	 * @since 1.4.0
	 *
	 * @param array $actions associative array of action names to anchor tags
	 * @param array $plugin plugin currently being listed
	 * @return array associative array of plugin action links
	 */
	public function plugin_install_action_links( $actions, $plugin ) {

		if ( 'jilt-for-woocommerce' !== $plugin['slug'] ) {
			return $actions;
		}

		// support url if any
		if ( $this->get_support_url() ) {
			$actions[] = sprintf( '<a href="%s">%s</a>', $this->get_support_url(), esc_html_x( 'Support', 'noun', 'woocommerce-plugin-framework' ) );
		}

		// connect url if not connected, settings url otherwise
		if ( ! $this->get_integration()->is_jilt_connected() ) {
			$actions[] = sprintf( '<a href="%s" class="button button-primary wc-jilt-connect">%s</a>', $this->get_connect_url(), esc_html__( 'Connect to Jilt', 'jilt-for-woocommerce' ) );
		} elseif ( $this->get_settings_link( $this->get_id() ) ) {
			$actions[] = $this->get_settings_link( $this->get_id() );
		}

		// add the links to the front of the actions list
		return $actions;
	}


	/** Accessors  *******************************************************/


	/**
	 * Returns the integration class instance.
	 *
	 * @since 1.0.0
	 *
	 * @return WC_Jilt_Integration The integration class instance
	 */
	public function get_integration() {
		return $this->integration;
	}


	/**
	 * Returns the frontend instance.
	 *
	 * @since 1.3.0
	 *
	 * @return WC_Jilt_Frontend the frontend class instance
	 */
	public function get_frontend() {
		return $this->frontend;
	}


	/**
	 * Returns the checkout handler instance.
	 *
	 * @since 1.3.0
	 *
	 * @return \WC_Jilt_Checkout_Handler
	 */
	public function get_checkout_handler_instance() {

		return $this->checkout_handler;
	}


	/**
	 * Returns the customer handler instance.
	 *
	 * @since 1.3.0
	 *
	 * @return \WC_Jilt_Customer_Handler
	 */
	public function get_customer_handler_instance() {

		return $this->customer_handler;
	}


	/**
	 * Returns the cart handler instance.
	 *
	 * @since 1.3.0
	 *
	 * @return \WC_Jilt_Cart_Handler
	 */
	public function get_cart_handler_instance() {

		return $this->cart_handler;
	}


	/**
	 * Returns the WC API handler instance.
	 *
	 * @since 1.3.0
	 *
	 * @return \WC_Jilt_WC_API_Handler
	 */
	public function get_wc_api_handler_instance() {

		return $this->wc_api_handler;
	}


	/**
	 * Returns the WC REST API handler instance.
	 *
	 * @since 1.5.0
	 *
	 * @return \WC_Jilt_WC_REST_API_Handler
	 */
	public function get_wc_rest_api_handler_instance() {

		return $this->wc_rest_api_handler;
	}


	/**
	 * Returns the integrations handler instance.
	 *
	 * @since 1.5.5
	 *
	 * @return \WC_Jilt_Integrations
	 */
	public function get_integrations_handler_instance() {

		return $this->integrations;
	}


	/**
	 * Returns the WP Admin Message Handler instance for use with
	 * setting/displaying admin messages & errors.
	 *
	 * TODO: remove this when the method gets fixed in framework {IT 2018-01-26}
	 *
	 * @since 1.4.0
	 *
	 * @return Framework\SV_WP_Admin_Message_Handler
	 */
	public function get_message_handler() {

		// ensure admin message handler is loaded
		require_once( $this->get_framework_path() . '/class-sv-wp-admin-message-handler.php' );

		return parent::get_message_handler();
	}


	/**
	 * Returns the main Jilt Plugin instance, ensures only one instance is/can be loaded.
	 *
	 * @see wc_jilt()
	 *
	 * @since 1.0.0
	 *
	 * @return WC_Jilt
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}


	/** Helper methods ******************************************************/


	/**
	 * When the Jilt API indicates a customer's Jilt account has been cancelled,
	 * deactivate the plugin.
	 *
	 * @since 1.0.0
	 */
	public function handle_account_cancellation() {

		require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
		deactivate_plugins( $this->get_file() );
	}


	/**
	 * Returns the plugin name, localized.
	 *
	 * @see SV_WC_Plugin::get_plugin_name()
	 *
	 * @since 1.0.0
	 *
	 * @return string the plugin name
	 */
	public function get_plugin_name() {
		return __( 'Jilt for WooCommerce', 'jilt-for-woocommerce' );
	}


	/**
	 * Returns __FILE__
	 *
	 * @since 1.0.0
	 * @see SV_WC_Plugin::get_file()
	 * @return string the full path and filename of the plugin file
	 */
	protected function get_file() {
		return __FILE__;
	}


	/**
	 * Returns true if on the plugin settings page.
	 *
	 * @since 1.0.0
	 *
	 * @see SV_WC_Plugin::is_plugin_settings()
	 *
	 * @return boolean true if on the settings page
	 */
	public function is_plugin_settings() {

		return isset( $_GET['page'] ) && 'wc-jilt' === $_GET['page'];
	}


	/**
	 * Adds milestone hooks.
	 *
	 * @since 1.5.1
	 */
	protected function add_milestone_hooks() {

		// first abandoned cart recovered
		add_action( 'wc_jilt_order_recovered', function( $_ ) {
			wc_jilt()->get_lifecycle_handler()->trigger_milestone(
				'order-recovered', lcfirst( __( 'You have recovered your first order!', 'jilt-for-woocommerce' ) )
			);
		} );
	}


	/**
	 * Returns the plugin configuration URL.
	 *
	 * @since 1.0.0
	 *
	 * @see SV_WC_Plugin::get_settings_link()
	 *
	 * @param string $plugin_id optional plugin identifier.
	 * @return string plugin settings URL
	 */
	public function get_settings_url( $plugin_id = null ) {
		return admin_url( 'admin.php?page=wc-jilt' );
	}


	/**
	 * Returns the wordpress.org plugin page URL.
	 *
	 * @since 1.0.0
	 *
	 * @see SV_WC_Plugin::get_product_page_url()
	 *
	 * @return string wordpress.org product page url
	 */
	public function get_product_page_url() {

		return 'https://wordpress.org/plugins/jilt-for-woocommerce/';
	}


	/**
	 * Gets the Reviews page URL.
	 *
	 * @since 1.5.1
	 *
	 * @return string
	 */
	public function get_reviews_url() {

		return 'https://wordpress.org/support/plugin/jilt-for-woocommerce/reviews/?rate=5#new-post';
	}


	/**
	 * Returns the review link for Jilt.
	 *
	 * Because Jilt has no review link, this returns null.
	 *
	 * @since 1.0.0
	 *
	 * @see SV_WC_Plugin::get_review_url()
	 *
	 * @return string review url
	 */
	public function get_review_url() {
		return null;
	}


	/**
	 * Returns the plugin documentation url.
	 *
	 * @since 1.0.0
	 *
	 * @see SV_WC_Plugin::get_documentation_url()
	 *
	 * @return string documentation URL
	 */
	public function get_documentation_url() {

		return 'http://help.jilt.com/';
	}


	/**
	 * Returns the Jilt hostname.
	 *
	 * @sine 1.1.0
	 *
	 * @return string
	 */
	public function get_hostname() {

		/**
		 * Filters the Jilt hostname, used in development for changing to
		 * dev/staging instances.
		 *
		 * @since 1.1.0
		 *
		 * @param string $hostname
		 * @param \WC_Jilt $this instance
		 */
		return apply_filters( 'wc_jilt_hostname', self::HOSTNAME, $this );
	}


	/**
	 * Returns the app hostname.
	 *
	 * @since 1.0.3
	 *
	 * @return string app hostname, defaults to app.jilt.com
	 */
	public function get_app_hostname() {

		/**
		 * Filters the app Hostname.
		 *
		 * @since 1.5.0
		 *
		 * @param string app hostname
		 * @param \WC_Jilt plugin instance
		 */
		return apply_filters( 'wc_jilt_app_hostname', sprintf( 'app.%s', $this->get_hostname() ), $this );
	}


	/**
	 * Returns the api hostname.
	 *
	 * @since 1.5.6
	 *
	 * @return string api hostname, defaults to api.jilt.com
	 */
	public function get_api_hostname() {

		/**
		 * Filters the API Hostname.
		 *
		 * @since 1.5.6
		 *
		 * @param string api hostname
		 * @param \WC_Jilt plugin instance
		 */
		return apply_filters( 'wc_jilt_api_hostname', sprintf( 'api.%s', $this->get_hostname() ), $this );
	}


	/**
	 * Returns an app endpoint with an optionally provided path
	 *
	 * @since 1.4.0
	 *
	 * @param string $path
	 * @return string
	 */
	public function get_app_endpoint( $path = '' ) {

		// returns URL like https://app.jilt.com/$path
		return sprintf( 'https://%1$s/%2$s', $this->get_app_hostname(), $path );
	}


	/**
	 * Returns the connection initialization URL.
	 *
	 * @since 1.4.0
	 *
	 * @return string url
	 */
	public function get_connect_url() {

		return add_query_arg( array(
			'wc-api'  => 'jilt',
			'connect' => 'init',
			'nonce'   => wp_create_nonce( 'wc-jilt-connect-init' )
		), get_home_url() );
	}


	/**
	 * Returns the connection callback URL.
	 *
	 * @since 1.4.0
	 *
	 * @return string url
	 */
	public function get_callback_url() {

		return add_query_arg( array(
			'wc-api'  => 'jilt',
			'connect' => 'done'
		), get_home_url() );
	}


	/**
	 * Returns the app Sign In setup URL.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_sign_in_url() {

		return $this->get_app_endpoint( 'signin' );
	}


	/**
	 * Returns the current shop domain, including the path if this is a
	 * Multisite directory install.
	 *
	 * @since 1.1.0
	 *
	 * @return string the current shop domain. e.g. 'example.com' or 'example.com/fr'
	 */
	public function get_shop_domain() {

		$domain = parse_url( get_home_url(), PHP_URL_HOST );

		if ( $this->is_multisite_directory_install() ) {
			$domain .= parse_url( get_home_url(), PHP_URL_PATH );
		}

		return $domain;
	}


	/**
	 * Is this a multisite directory install?
	 *
	 * @since 1.4.3
	 *
	 * @return boolean
	 */
	public function is_multisite_directory_install() {
		return defined( 'MULTISITE' ) && MULTISITE && ( ! defined( 'SUBDOMAIN_INSTALL' ) || ! SUBDOMAIN_INSTALL );
	}


	/**
	 * Returns the shop admin email, or current user's email if the former is not available.
	 *
	 * @since 1.4.0
	 *
	 * @return string email
	 */
	public function get_admin_email() {

		$email = get_option( 'admin_email' );

		if ( ! $email ) {
			$current_user = wp_get_current_user();
			$email        = $current_user->user_email;
		}

		return $email;
	}


	/**
	 * Returns the shop admin's first name, or the current user's if the former is not available.
	 *
	 * @since 1.4.0
	 *
	 * @return string the first name
	 */
	public function get_admin_first_name() {

		$user = get_user_by( 'email', $this->get_admin_email() );

		if ( ! $user ) {
			$user = wp_get_current_user();
		}

		return $user->user_firstname;
	}


	/**
	 * Returns the shop admin's last name, or the current user's if the former is not available.
	 *
	 * @since 1.4.0
	 *
	 * @return string the last name
	 */
	public function get_admin_last_name() {

		$user = get_user_by( 'email', $this->get_admin_email() );

		if ( ! $user ) {
			$user = wp_get_current_user();
		}

		return $user->user_lastname;
	}


	/**
	 * Returns the best available timestamp for when WooCommerce was installed in
	 * this site.
	 *
	 * For this we use the create date of the special shop page,
	 * if it exists
	 *
	 * @since 1.1.0
	 *
	 * @return string|null the timestamp at which WooCommerce was installed in this shop, in iso8601 format
	 */
	public function get_wc_created_at() {

		$shop_page = get_post( wc_get_page_id( 'shop' ) );

		if ( $shop_page ) {
			return date( 'Y-m-d\TH:i:s\Z', strtotime( $shop_page->post_date_gmt ) );
		}
	}


	/**
	 * Returns the Jilt support URL, with optional parameters.
	 *
	 * @since 1.0.0
	 *
	 * @param array $args Optional array of method arguments:
	 *        'domain' defaults to server domain
	 *        'form_type' defaults to 'support'
	 *        'platform' defaults to 'WooCommerce'
	 *        'message' defaults to false, if given this will be pre-populated in the support form message field
	 *        'first_name' defaults to current user first name
	 *        'last_name' defaults to current user last name
	 *        'email' defaults to current user email
	 *        Any parameter can be excluded from the returned URL by setting to false.
	 *        If $args itself is null, then no parameters will be added to the support URL
	 * @return string support URL
	 */
	public function get_support_url( $args = array() ) {

		if ( is_array( $args ) ) {

			$current_user = wp_get_current_user();

			$args = array_merge(
				array(
					'domain'     => $this->get_shop_domain(),
					'form_type'  => 'support',
					'platform'   => 'woocommerce',
					'first_name' => $current_user->user_firstname,
					'last_name'  => $current_user->user_lastname,
					'email'      => $current_user->user_email,
				),
				$args
			);

			// strip out empty params, and urlencode the others
			foreach ( $args as $key => $value ) {
				if ( false === $value ) {
					unset( $args[ $key ] );
				} else {
					$args[ $key ] = urlencode( $value );
				}
			}
		}

		return 'https://jilt.com/contact/' . ( null !== $args && count( $args ) > 0 ? '?' . build_query( $args ) : '' );
	}


	/**
	 * Returns the currently released version of the plugin available on wordpress.org
	 *
	 * @since 1.1.0
	 *
	 * @return string the version, e.g. '1.0.0'
	 */
	public function get_latest_plugin_version() {

		if ( false === ( $version_data = get_transient( md5( $this->get_id() ) . '_version_data' ) ) ) {
			$changelog = wp_safe_remote_get( 'https://plugins.svn.wordpress.org/jilt-for-woocommerce/trunk/readme.txt' );
			$cl_lines  = explode( '\n', wp_remote_retrieve_body( $changelog ) );

			if ( ! empty( $cl_lines ) ) {
				foreach ( $cl_lines as $line_num => $cl_line ) {
					if ( preg_match( '/= ([\d\-]{10}) - version ([\d.]+) =/', $cl_line, $matches ) ) {
						$version_data = array( 'date' => $matches[1] , 'version' => $matches[2] );
						set_transient( md5( $this->get_id() ) . '_version_data', $version_data, DAY_IN_SECONDS );
						break;
					}
				}
			}
		}

		if ( isset( $version_data['version'] ) ) {
			return $version_data['version'];
		}
	}


	/**
	 *  Checks whether there a plugin update available on wordpress.org
	 *
	 * @since 1.1.0
	 *
	 * @return boolean true if there's an update available
	 */
	public function is_plugin_update_available() {

		$current_plugin_version = $this->get_latest_plugin_version();

		if ( ! $current_plugin_version ) {
			return false;
		}

		return version_compare( $current_plugin_version, $this->get_version(), '>' );
	}


	/**
	 * Checks some known hostname patterns to see if the site might be running locally.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function is_local_site() {

		$local_hosts = array(
			'/localhost/',
			'/127\.0\.0\.1/',
			'/::1/',
			'/\.test$/',
			'/\.local$/'
		);

		$host = $_SERVER['HTTP_HOST'];

		foreach ( $local_hosts as $local_host ) {

			if ( preg_match( $local_host, $host ) ) {

				return true;
			}
		}

		return false;
	}


	/** Privacy methods *****************************************************/


	/**
	 * Registers a GDPR compliant personal data eraser in WordPress for handling erasure requests.
	 *
	 * @internal
	 *
	 * @since 1.4.5
	 *
	 * @param array $erasers list of WordPress personal data erasers
	 * @return array
	 */
	public function register_personal_data_eraser( array $erasers ) {

		$erasers['jilt-for-woocommerce'] = array(
			'eraser_friendly_name' => $this->get_plugin_name(),
			'callback'             => array( $this, 'handle_personal_data_erasure_request' ),
		);

		return $erasers;
	}


	/**
	 * Issues a request to Jilt to unschedule emails to be sent to the requester's email address.
	 *
	 * TODO this method needs an endpoint from Jilt API to issue unscheduling requests {FN 2018-05-23}
	 *
	 * @internal
	 *
	 * @since 1.4.5
	 *
	 * @param string $email_address address of the user that issued the erasure request
	 * @return array associative array with erasure response
	 */
	public function handle_personal_data_erasure_request( $email_address ) {

		$response = array(
			'items_removed'  => false,
			'items_retained' => false,
			'messages'       => array(),
			'done'           => true,
		);

		if ( $this->get_integration()->is_jilt_connected() ) {

			// TODO: send a request to Jilt to programmatically unschedule emails targeting the requester's email address {FN 2018-05-23}
			// based on the response we can determine if the request went well or not and populate $response information
			// /* translators: Placeholder: %s - email address */
			// $response['messages'][]    = sprintf( __( 'A request was successfully sent to Jilt to unschedule all emails schedule for %s.', 'jilt-for-woocommerce' ), $email_address );
			// $response['items_removed'] = true;

		} else {

			// TODO: if Jilt is disconnected, we should probably warn admin that the request could not be sent {FN 2018-05-23}
			// $response['messages'][]     = __( 'Could not establish a connection with Jilt to issue a personal data erasure request.', 'jilt-for-woocommerce' );
			// $response['items_retained'] = true;
		}

		return $response;
	}


	/** Logging methods *****************************************************/


	/**
	 * Returns the logger instance.
	 *
	 * Note: bypassing the framework core logger from SV_WC_Plugin in order to
	 * experiment with a proof of concept implementation for splitting the log
	 * functionality out into a new class: WC_Jilt_Logger
	 *
	 * @since 1.2.0
	 *
	 * @return \WC_Jilt_Logger the logger
	 */
	public function get_logger() {

		$log_threshold = (int) $this->get_integration()->get_option( 'log_threshold' );

		if ( null === $this->logger ) {
			$this->logger = new WC_Jilt_Logger( $log_threshold, $this->get_id() );
		} else {
			if ( (int) $this->logger->get_threshold() !== $log_threshold ) {
				$this->logger->set_threshold( $log_threshold );
			}
		}

		return $this->logger;
	}


	/**
	 * Logs a statement at log level INFO.
	 *
	 * Note: this method is not intended to be called directly. It's overridden
	 * from the SV_WC_Plugin framework in order to delegate to the WC_Jilt_Logger
	 * instance.
	 *
	 * @see SV_WC_Plugin::log()
	 * @see self::log_with_level()
	 *
	 * @since 1.0.0
	 *
	 * @param string $message error or message to save to log
	 * @param string $log_id optional log id to segment the files by, defaults to plugin id
	 */
	public function log( $message, $log_id = null ) {

		// delegate to logger instance and consider this method to be log level INFO
		$this->get_logger()->info( $message, array( 'source' => $log_id ) );
	}


	/**
	 * Automatically logs API requests/responses when using SV_WC_API_Base.
	 *
	 * @see SV_WC_Plugin::add_api_request_logging()
	 * @see SV_WC_API_Base::broadcast_request()
	 *
	 * @since 1.2.0
	 */
	public function add_api_request_logging() {

		// deal with the fact that this is called by the framework before the
		// integration class is even instantiated
		if ( ! $this->get_integration() ) {
			return;
		}

		// delegate to logger instance
		$action_name = 'wc_' . $this->get_id() . '_api_request_performed';

		if ( ! has_action( $action_name ) ) {
			add_action( $action_name, array( $this->get_logger(), 'log_api_request' ), 10, 2 );
		}
	}


	/**
	 * Generates a unique token at a specified length.
	 *
	 * Based on wp_generate_password() but doesn't filter the result to avoid
	 * plugin conflicts.
	 *
	 * @since 1.4.2
	 *
	 * @param int $length desired token length
	 * @return string
	 */
	public function generate_random_token( $length ) {

		$length = (int) $length;

		$chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
		$token = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$token .= substr( $chars, wp_rand( 0, strlen( $chars ) - 1 ), 1 );
		}

		return $token;
	}


} // end WC_Jilt class
