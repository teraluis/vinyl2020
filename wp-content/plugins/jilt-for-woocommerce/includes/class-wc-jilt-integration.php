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
 * @package   WC-Jilt/Admin
 * @author    Jilt
 * @category  Admin
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * Main integration class
 *
 * @since 1.0.0
 */
class WC_Jilt_Integration extends WC_Settings_API {


	/** @var \WC_Jilt_API instance */
	private $api;

	/** @var \WC_Jilt_OAuth_Access_Token the access token instance */
	private $access_token;

	/** @var string the API secret key */
	private $secret_key;

	/** @var \WC_Jilt_Integration_Admin instance */
	private $admin;


	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @see WC_Jilt_Integration::instance()
	 */
	public function __construct() {

		// @see WC_Settings_API::$id
		$this->id = 'jilt';

		// delegate admin-related setup
		$this->admin = new WC_Jilt_Integration_Admin( $this );

		// load settings
		$this->init_settings();

		if ( $this->is_linked() ) {

			// handle placed orders and keeping the financial status in sync with Jilt
			add_action( 'woocommerce_order_status_changed', array( $this, 'order_status_changed' ), 10, 3 );

			// make sure the order.updated webhook fires on refunds
			add_filter( 'woocommerce_webhook_topic_hooks', array( $this, 'add_refund_to_order_updated_webhook' ), 10, 2 );
		}

		// load styles/scripts
		add_action( 'admin_enqueue_scripts', array( $this, 'load_styles_scripts' ) );

		// handle plugin rating
		add_action( 'wp_ajax_woocommerce_jilt_rated', array( $this, 'mark_as_rated' ) );

		// handle a domain change, if it is detected
		add_action( 'init', array( $this, 'handle_domain_change' ) );
	}


	/**
	 * Handles local response to a detected domain change.
	 *
	 * @since 1.6.0
	 */
	public function handle_domain_change() {

		if ( $this->is_duplicate_site() ) {

			WC_Jilt_Webhook::delete_webhooks();
		}
	}


	/**
	 * Marks the plugin as rated.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 */
	public function mark_as_rated() {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( -1 );
		}

		update_option( 'wc_jilt_admin_footer_text_rated', 1 );

		wp_die();
	}


	/**
	 * Loads admin styles and scripts.
	 *
	 * @internal
	 *
	 * @since 1.4.0
	 */
	public function load_styles_scripts() {

		$screen              = get_current_screen();
		$wc_api_keys_tab     = Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte( '3.4.0' ) ? 'advanced' : 'api';
		$is_wc_api_keys_page = 'wc-settings' === Framework\SV_WC_Helper::get_request( 'page' ) && $wc_api_keys_tab === Framework\SV_WC_Helper::get_request( 'tab' ) && 'keys' === Framework\SV_WC_Helper::get_request( 'section' );

		if ( in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) || wc_jilt()->is_plugin_settings() || $is_wc_api_keys_page ) {

			wp_enqueue_style( 'woocommerce_admin_styles', WC()->plugin_url() . '/assets/css/admin.css' );
			wp_enqueue_script( 'jquery-tiptip',            WC()->plugin_url() . '/assets/js/jquery-tiptip/jquery.tipTip.min.js', array( 'jquery' ) );

			wp_enqueue_script( 'jilt-for-woocommerce-admin', wc_jilt()->get_plugin_url() . '/assets/js/admin/wc-jilt-admin.min.js', array( 'jquery' ), WC_Jilt::VERSION );

			wp_localize_script( 'jilt-for-woocommerce-admin', 'wc_jilt', array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'wc_api_key_id' => wc_jilt()->get_wc_rest_api_handler_instance()->get_key_id(),
				'feedback_url'  => wc_jilt()->get_integration()->get_api()->get_api_endpoint() . '/feedback',
				'shop_domain'   => wc_jilt()->get_shop_domain(),
				'admin_email'   => wp_get_current_user()->user_email,
				'i18n'          => array(
					'confirm_disconnect' => esc_html__( 'Are you sure you want to disconnect your shop from your Jilt account?', 'jilt-for-woocommerce' ),
					'confirm_key_revoke' => esc_html__( 'Are you sure you want to revoke this key? Jilt for WooCommerce will not work properly.', 'jilt-for-woocommerce' ),
					'select_an_option'   => esc_html__( 'Please select an option', 'jilt-for-woocommerce' ),
				),
			) );
		}

		wp_enqueue_style( 'jilt-for-woocommerce-admin', wc_jilt()->get_plugin_url() . '/assets/css/admin/wc-jilt-admin.min.css', array(), WC_Jilt::VERSION );
	}


	/**
	 * Returns the URL to the specified page within the Jilt web app, useful
	 * for direct linking to internal pages, like campaigns.
	 *
	 * @since 1.0.0
	 *
	 * @param string $page page URL partial, defaults to 'dashboard'
	 * @return string
	 */
	public function get_jilt_app_url( $page = 'dashboard' ) {
		return sprintf( $this->get_plugin()->get_app_endpoint() . 'shops/%1$d/%2$s', (int) $this->get_linked_shop_id(), rawurlencode( $page ) );
	}


	/**
	 * Returns the URL to the specified transaction notification (or the main transactional notifications page if not specified)
	 *
	 * @since 1.6.0
	 *
	 * @param int|null $tn_id transactional notification ID
	 * @return string
	 */
	public function get_transactional_notification_url( $tn_id = null ) {

		$last_segment = is_numeric( $tn_id ) ? '/' . (int) $tn_id : '';

		return $this->get_jilt_app_url( 'transactional_notifications' ) . $last_segment;
	}


	/**
	 * Gets the plugin settings
	 *
	 * @see WC_Settings_API::settings
	 * @since 1.1.0
	 * @return array associative array of plugin settings including the following keys:
	 *   - 'secret_key': string
	 *   - 'log_level': 100...900
	 *   - 'recover_held_orders': 'yes'|'no'
	 */
	public function get_settings() {
		return $this->settings;
	}


	/**
	 * Updates the plugin settings.
	 *
	 * @since 1.1.0
	 *
	 * @param array $data associative array of settings
	 */
	public function update_settings( $data ) {
		update_option( $this->get_option_key(), $data );
	}


	/**
	 * Clears out the the Jilt connection data.
	 *
	 * This includes: access token, public key, shop id, current shop domain,
	 * is disabled, webhooks, storefront params, secret key, and wc rest api key
	 *
	 * @since 1.1.0
	 */
	public function clear_connection_data() {

		$this->unlink_shop(); // this will mark the shop as uninstalled in Jilt
		$this->revoke_authorization(); // this will revoke the oauth access token
		$this->clear_access_token();
		$this->delete_storefront_params();

		WC_Jilt_Webhook::delete_webhooks();

		// remove the Jilt for WC REST API key
		$this->get_plugin()->get_wc_rest_api_handler_instance()->revoke_key();

		// TODO: remove the two following lines when dropping support for secret key auth
		delete_option( 'wc_jilt_secret_key' );
		delete_option( 'wc_jilt_public_key' );

		delete_option( 'wc_jilt_shop_uuid' );
		delete_option( 'wc_jilt_shop_id' );
		delete_option( 'wc_jilt_shop_domain' );
		delete_option( 'wc_jilt_disabled' );

		delete_option( 'wc_jilt_client_id' );
		delete_option( 'wc_jilt_client_secret' );

		// remove secret key, if was used so far
		if ( $this->get_secret_key() ) {

			unset( $_POST['woocommerce_jilt_secret_key'], $this->settings['secret_key'] );

			$this->set_secret_key( '' );

			update_option( $this->get_option_key(), $this->settings );
		}

		// re-initialize form fields, so that the auth token is not accidentally loaded from memory
		$this->init_form_fields();

		$this->api = null; // reset API instance
	}


	/**
	 * Returns the Jilt API instance.
	 *
	 * Since 1.4.0 this always returns an API instance, even if not authenticated.
	 *
	 * @since 1.0.0
	 *
	 * @return WC_Jilt_API the API instance
	 */
	public function get_api() {

		// override the current auth token with a new one?
		if ( null !== $this->api && $this->api->get_auth_token() !== $this->get_auth_token() ) {
			$this->api = null;
		}

		// prefer UUID when making API requests
		$shop_identifier = $this->get_linked_shop_uuid() ?: $this->get_linked_shop_id();

		if ( null === $this->api ) {
			$this->set_api(
				new WC_Jilt_API(
					$shop_identifier,
					$this->get_auth_token()
				)
			);
		}

		return $this->api;
	}


	/**
	 * Checks the site URL to determine whether this is likely a duplicate site.
	 *
	 * The typical case is when a production site is copied to a staging server
	 * in which case all of the Jilt keys will be copied as well, and staging
	 * will happily make production API requests.
	 *
	 * The one false positive that can happen here is if the site legitimately
	 * changes domains. Not sure yet how you would handle this, might require
	 * some administrator intervention.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean true if this is likely a duplicate site
	 */
	public function is_duplicate_site() {
		$shop_domain = $this->get_linked_shop_domain();

		return $shop_domain && $shop_domain !== $this->get_plugin()->get_shop_domain();
	}


	/**
	 * Returns the auth token for Jilt API - either OAuth access token or secret api key.
	 *
	 * @since 1.4.0
	 *
	 * @return WC_Jilt_OAuth_Access_Token|string|null OAuth access token or secret api key, or null if not available
	 */
	public function get_auth_token() {
		return 'secret_key' === $this->get_auth_method() ? $this->get_secret_key() : $this->get_access_token();
	}


	/**
	 * Returns the configured secret key.
	 *
	 * @since 1.0.0
	 *
	 * @return string the secret key, if set, null otherwise
	 */
	public function get_secret_key() {

		if ( null === $this->secret_key ) {
			// retrieve from db if not already set
			$this->set_secret_key( $this->get_option( 'secret_key' ) );
		}

		return $this->secret_key;
	}


	/**
	 * Sets the secret key.
	 *
	 * @since 1.2.0
	 *
	 * @param string $secret_key the secret key
	 */
	public function set_secret_key( $secret_key ) {

		$this->secret_key = $secret_key;
	}


	/**
	 * Checks whether the plugin configured.
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if the plugin is configured, false otherwise
	 */
	public function is_configured() {
		// if we have an authentication token (either the legacy secret key or an oauth access token), we're good to go
		return (bool) $this->get_auth_token();
	}


	/**
	 * Checks whether the plugin has connected to Jilt.
	 *
	 * @since 1.0.0
	 *
	 * @return bool true if the plugin has connected to Jilt
	 */
	public function has_connected() {

		if ( 'secret_key' === $this->get_auth_method() ) {

			// since the public key is returned by the REST API it serves as a
			// reasonable proxy for whether we've connected with the current secret key
			// note that we get the option directly
			return (bool) get_option( 'wc_jilt_public_key' );
		}

		// since the oauth access token is saved only after the site is authorized,
		// we can use it to determine whether the site is connected to Jilt
		return (bool) $this->get_auth_token();
	}


	/**
	 * Checks whether this shop has linked itself to a Jilt account.
	 *
	 * @since 1.0.0
	 *
	 * @return boolean true if this shop is linked
	 */
	public function is_linked() {
		return $this->get_linked_shop_uuid() || $this->get_linked_shop_id();
	}


	/**
	 * Returns the linked Jilt shop ID for this site, if any.
	 *
	 * @since 1.0.0
	 *
	 * @return int|null Jilt shop identifier, or null
	 */
	public function get_linked_shop_id() {
		return get_option( 'wc_jilt_shop_id', null );
	}


	/**
	 * Returns the linked Jilt shop UUID for this site, if any.
	 *
	 * @since 1.5.0
	 *
	 * @return string|null Jilt shop UUID, or null
	 */
	public function get_linked_shop_uuid() {
		return get_option( 'wc_jilt_shop_uuid', null );
	}


	/**
	 * Persists the given linked shop ID.
	 *
	 * @since 1.0.0
	 *
	 * @param int $id the linked shop ID
	 * @return int the provided $id
	 */
	public function set_linked_shop_id( $id ) {

		update_option( 'wc_jilt_shop_id', $id );

		if ( 'secret_key' === $this->get_auth_method() ) {

			$this->stash_secret_key( $this->get_secret_key() );

			// clear the API object so that the new shop id can be used for subsequent requests
			if ( null !== $this->api && $this->api->get_shop_id() !== $id ) {
				$this->api->set_shop_id( $id );
			}
		}

		return $id;
	}

	/**
	 * Persists the given linked shop UUID.
	 *
	 * @since 1.5.0
	 *
	 * @param string $uuid the linked shop UUID
	 * @return string the provided $uuid
	 */
	public function set_linked_shop_uuid( $uuid ) {

		update_option( 'wc_jilt_shop_uuid', $uuid );

		// clear the API object so that the new shop id can be used for subsequent requests
		if ( null !== $this->api && $this->api->get_shop_id() !== $uuid ) {
			$this->api->set_shop_id( $uuid );
		}

		return $uuid;
	}


	/**
	 * Checks whether the integration is disabled.
	 *
	 * When disabled, this indicates that although the plugin is
	 * installed, activated, and configured, it should not send any requests
	 * over the Jilt REST API.
	 *
	 * Since 1.4.0 this simply indicates that the site is detected to be duplicated (e.g.
	 * a production site that was migrated to staging).
	 *
	 * @since 1.1.0
	 *
	 * @return bool
	 */
	public function is_disabled() {
		return $this->is_duplicate_site();
	}


	/**
	 * Checks whether the shop is connected to Jilt and active or not.
	 *
	 * @since 1.4.0
	 *
	 * @return bool
	 */
	public function is_jilt_connected() {
		return $this->is_configured() && $this->is_linked() && ! $this->is_disabled();
	}


	/**
	 * Get the secret key stash
	 *
	 * @since 1.2.0
	 * @return array of secret key strings
	 */
	public function get_secret_key_stash() {
		$stash = get_option( 'wc_jilt_secret_key_stash', array() );

		if ( ! is_array( $stash ) ) {
			$stash = array();
		}

		return $stash;
	}


	/**
	 * Stashes the current secret key into the db.
	 *
	 * @since 1.1.0
	 *
	 * @param string $secret_key the secret key to stash
	 */
	public function stash_secret_key( $secret_key ) {

		// What is the purpose of all this you might ask? Well it provides us a
		// future means of validating/handling recovery URLs that were generated
		// with a prior secret key
		$stash = $this->get_secret_key_stash();

		if ( ! in_array( $secret_key, $stash, true ) ) {
			$stash[] = $secret_key;
		}

		update_option( 'wc_jilt_secret_key_stash', $stash );
	}


	/**
	 * Sets the access token.
	 *
	 * @since 1.4.0
	 *
	 * @param array $token oauth access token
	 */
	public function set_access_token( $token ) {

		update_option( 'wc_jilt_access_token', $token );

		$this->init_access_token( $token );
	}


	/**
	 * Returns the access token.
	 *
	 * @since 1.4.0
	 *
	 * @return \WC_Jilt_OAuth_Access_token|null jilt access token instance or null if not available
	 */
	public function get_access_token() {
		global $wpdb;

		if ( ! isset( $this->access_token ) ) {
			$this->init_access_token( maybe_unserialize( $wpdb->get_var( "SELECT option_value FROM {$wpdb->options} WHERE option_name='wc_jilt_access_token'" ) ) );
		}

		return $this->access_token ? $this->access_token : null;
	}


	/**
	 * Initializes the access token.
	 *
	 * @since 1.4.0
	 *
	 * @param array $token token args
	 */
	private function init_access_token( $token ) {
		$this->access_token = is_array( $token ) ? new WC_Jilt_OAuth_Access_token( $token ) : null;
	}


	/**
	 * Clears the access token.
	 *
	 * @since 1.4.0
	 */
	public function clear_access_token() {

		delete_option( 'wc_jilt_access_token' );

		$this->access_token = null;
	}


	/**
	 * Sets the shop public key.
	 *
	 * @since 1.4.0
	 *
	 * @param string $key shop public key
	 */
	public function set_public_key( $key ) {
		update_option( 'wc_jilt_public_key', $key );
	}


	/**
	 * Returns the authentication method used for REST API calls.
	 *
	 * Secret key authentication is deprecated since 1.4.0 and is used only to provide
	 * backwards compatibility for shops that haven't upgraded to OAuth2 access token yet.
	 *
	 * @since 1.4.0
	 *
	 * @return string the authentication method, either 'secret_key' or 'access_token'
	 */
	public function get_auth_method() {
		return $this->get_secret_key() ? 'secret_key' : 'access_token';
	}


	/**
	 * Persists the given linked Shop identifier.
	 *
	 * @since 1.1.0
	 *
	 * @return String the shop domain that was set
	 */
	public function set_shop_domain() {

		_deprecated_function( 'WC_Jilt_Integration::set_shop_domain()', '1.4.0', 'WC_Jilt_Integration::set_linked_shop_domain' );

		return $this->set_linked_shop_domain();
	}


	/**
	 * Persists the linked shop domain for historical reference.
	 *
	 * @since 1.4.0
	 *
	 * @return string the shop domain that was set
	 */
	public function set_linked_shop_domain() {

		$shop_domain = $this->get_plugin()->get_shop_domain();

		// prevent migration plugins from overriding the domain by masking it, so we can
		// detect later if the site has been moved and act accordingly
		$shop_domain = str_replace( '.', '[.]', $shop_domain );

		update_option( 'wc_jilt_shop_domain', $shop_domain );

		return $shop_domain;
	}


	/**
	 * Returns the stored shop domain.
	 *
	 * @since 1.4.0
	 *
	 * @return string the shop domain that was stored when connecting to Jilt
	 */
	public function get_linked_shop_domain() {
		return str_replace( '[.]', '.', get_option( 'wc_jilt_shop_domain', '' ) );
	}


	/**
	 * Checks whether a notice should be displayed to customers about email collection usage.
	 *
	 * @since 1.4.5
	 *
	 * @return bool
	 */
	public function show_email_usage_notice() {

		return 'yes' === $this->get_storefront_param( 'show_email_usage_notice', 'no' );
	}


	/**
	 * Checks whether a checkbox to be ticked for consent should be displayed at checkout.
	 *
	 * @since 1.4.5
	 *
	 * @return bool
	 */
	public function ask_consent_at_checkout() {

		return 'yes' === $this->get_storefront_param( 'show_marketing_consent_opt_in', 'no' );
	}


	/**
	 * Returns the checkout consent prompt.
	 *
	 * @since 1.4.5
	 *
	 * @return string may contain HTML
	 */
	public function get_checkout_consent_prompt() {

		return (string) $this->get_storefront_param( 'checkout_consent_prompt', '' );
	}


	/**
	 * Checks whether held orders should be considered as placed.
	 *
	 * @since 1.1.0
	 *
	 * @return boolean true if "on-hold" orders should not be considered as placed, false otherwise if "on-hold" should be considered recoverable
	 */
	public function recover_held_orders() {

		return 'yes' === $this->get_storefront_param( 'recover_held_orders', 'no' );
	}


	/**
	 * Gets emails that are being managed by Jilt.
	 *
	 * Follows this format:
	 * managed_email_notifications: {
	 *   {wc_email_id}: {
	 *     active: true,
	 *     transactional_notification_id: 123,
	 *     state: 'live' // could be 'live', 'stopped', or 'draft'
	 *   },
	 *   ...
	 * }
	 *
	 * @since 1.6.0
	 *
	 * @return array of emails
	 */
	public function get_managed_email_notifications() {

		$emails = $this->get_storefront_param( 'managed_email_notifications', array() );

		return is_array( $emails ) ? $emails : array( $emails );
	}


	/**
	 * Checks if the Jilt account billing needs attention.
	 *
	 * This is set via storefront params from Jilt.
	 *
	 * @since 1.6.0
	 *
	 * @return bool
	 */
	public function billing_needs_attention() {

		return 'yes' === $this->get_storefront_param( 'billing_needs_attention' );
	}


	/**
	 * Checks if the post-checkout registration prompt is enabled.
	 *
	 * @since 1.3.0
	 *
	 * @return boolean true if one-click registration is enabled
	 */
	public function allow_post_checkout_registration() {

		// when updating settings, make sure we have the new value
		if ( isset( $_POST['woocommerce_jilt_post_checkout_registration'] ) ) {
			return 'yes' === $_POST['woocommerce_jilt_post_checkout_registration'];
		}

		return 'yes' === $this->get_option( 'post_checkout_registration' );
	}


	/**
	 * Checks if the add-to-cart email prompt is enabled.
	 *
	 * @since 1.4.0
	 *
	 * @param string $context optional context argument (pass 'frontend' to check if we can capture email in that context, skip for checking the option only)
	 * @return boolean true if capture email on add-to-cart is enabled
	 */
	public function capture_email_on_add_to_cart( $context = 'option' ) {

		$capture = 'yes' === $this->get_storefront_param( 'capture_email_on_add_to_cart', 'no' );

		if ( $capture && 'frontend' === $context ) {

			$capture =    ! is_user_logged_in()
			           && ! WC()->session->get( 'jilt_opt_out_add_to_cart_email_capture' )
			           && true !== WC_Jilt_Session::get_customer_email_collection_opt_out()
			           && ! $this->has_customer_email();
		}

		return $capture;
	}


	/**
	 * Checks whether we have the customer's email or not.
	 *
	 * @since 1.4.5
	 *
	 * @return bool
	 */
	private function has_customer_email() {

		if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_gte_3_0() ) {
			$has_email = is_callable( array( WC()->customer, 'get_billing_email' ) ) && WC()->customer->get_billing_email();
		} else {
			$has_email = is_object( WC()->customer ) && ! empty( WC()->customer->email );
		}

		return $has_email;
	}


	/**
	 * Get base data for creating/updating a linked shop in Jilt
	 *
	 * @since 1.0.0
	 *
	 * @param string $consumer_key a new WC REST API consumer key
	 * @return array
	 */
	public function get_shop_data( $consumer_key = null ) {

		$theme = wp_get_theme();

		$data = array(
			'domain'                  => $this->get_plugin()->get_shop_domain(),
			'admin_url'               => admin_url(),
			'wordpress_site_url'      => get_home_url(), // including install directory, if any
			'profile_type'            => 'woocommerce',
			'woocommerce_version'     => WC()->version,
			'wordpress_version'       => get_bloginfo( 'version' ),
			'integration_version'     => $this->get_plugin()->get_version(),
			'php_version'             => PHP_VERSION,
			'name'                    => html_entity_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			'main_theme'              => $theme->name,
			'currency'                => get_woocommerce_currency(),
			'primary_locale'          => strtolower( get_locale() ),
			'timezone'                => $this->get_store_timezone(),
			'created_at'              => $this->get_plugin()->get_wc_created_at(),
			'coupons_enabled'         => wc_coupons_enabled(),
			'free_shipping_available' => $this->is_free_shipping_available(),
			'integration_enabled'     => $this->is_jilt_connected(),
			'taxes_included'          => 'incl' === get_option( 'woocommerce_tax_display_cart' ),
			// include a simple list of status slugs, without wc- prefixes
			'valid_order_statuses'    => array_map(
				function( $status ) {
					return 'wc-' === substr( $status, 0, 3 ) ? substr( $status, 3 ) : $status;
				},
				array_keys( wc_get_order_statuses() )
			),
		);

		// add shop address
		$data = array_merge(
			$data,
			$this->get_shop_address()
		);

		// avoid sending false negatives
		if ( $this->is_ssl() ) {
			$data['supports_ssl'] = true;
		}

		$wc_api_key = $this->get_plugin()->get_wc_rest_api_handler_instance()->get_key();

		// Pass the consumer key & secret up to jilt when the key is first created
		if ( $wc_api_key && null !== $consumer_key ) {
			$data['woocommerce_consumer_key']    = $consumer_key;
			$data['woocommerce_consumer_secret'] = $wc_api_key->consumer_secret;
		}

		/**
		 * Filter shop data params used for updating the remote shop record via
		 * the API
		 *
		 * @since 1.3.2
		 *
		 * @param array $data the shop data
		 * @param WC_Jilt_Integration $this
		 */
		$data = apply_filters( 'wc_jilt_shop_data', $data, $this );

		return $data;
	}


	/** API methods ******************************************************/


	/**
	 * Link this shop to Jilt. The basic algorithm is to first attempt to
	 * create the shop over the Jilt API. If this request fails with a
	 * "Domain has already been taken" error, we try to find it over the Jilt
	 * API by domain, and update with the latest shop data.
	 *
	 * TODO: remove this when dropping secret key auth support {IT 2018-01-22}
	 *
	 * @since 1.0.0
	 *
	 * @return int the Jilt linked shop id, or false if the linking failed
	 * @throws Framework\SV_WC_API_Exception network exception or API error
	 */
	public function link_shop() {

		if ( $this->is_configured() && ! $this->is_duplicate_site() ) {

			$args = $this->get_shop_data();

			// set shop owner/email
			$current_user       = wp_get_current_user();
			$args['shop_owner'] = $current_user->user_firstname . ' ' . $current_user->user_lastname;
			$args['email']      = $current_user->user_email;

			try {

				$shop = $this->get_api()->create_shop( $args );
				$this->set_shop_domain();

				return $this->set_linked_shop_id( $shop->id );

			} catch ( Framework\SV_WC_API_Exception $exception ) {

				if ( Framework\SV_WC_Helper::str_exists( $exception->getMessage(), 'Domain has already been taken' ) ) {

					// log the exception and continue attempting to recover
					$this->get_plugin()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );

				} else {

					// for any error other than "Domain has already been taken" rethrow so the calling code can handle
					throw $exception;
				}
			}

			// if we're down here, it means that our attempt to create the
			// shop failed with "domain has already been taken". Lets try to
			// recover gracefully by finding the shop over the API
			$shop = $this->get_api()->find_shop( array( 'domain' => $args['domain'] ) );

			// no shop found? it might even exist, but the current API user might not have access to it
			if ( ! $shop ) {
				return false;
			}

			// we successfully found our shop. attempt to update it and save the ID
			try {

				// update the linked shop record with the latest settings
				$this->get_api()->update_shop( $args, $shop->id );

			} catch ( Framework\SV_WC_API_Exception $exception ) {

				// otherwise, log the exception
				$this->get_plugin()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );
			}

			$this->set_shop_domain();

			return $this->set_linked_shop_id( $shop->id );
		}
	}


	/**
	 * Unlink shop from Jilt
	 *
	 * @since 1.1.0
	 */
	public function unlink_shop() {

		// there is no remote Jilt shop for a duplicate site
		if ( $this->is_duplicate_site() ) {
			return;
		}

		try {
			// if the plugin is not configured properly (expired token, unable to refresh, no legacy secret key) we cannot unlink the shop
			if ( $this->is_configured() ) {
				$this->get_api()->delete_shop();
			}
		} catch ( Framework\SV_WC_API_Exception $exception ) {
			// quietly log any exception
			$this->get_plugin()->get_logger()->error( "Error communicating with Jilt when unlinking shop: {$exception->getMessage()}" );
		}
	}


	/**
	 * Revokes the integration plugin authorization.
	 *
	 * @since 1.4.0
	 */
	public function revoke_authorization() {

		try {
			// if the plugin is not configured properly (expired token, unable to refresh, no legacy secret key) we cannot revoke the token
			if ( $this->is_configured() ) {
				$this->get_api()->revoke_oauth_token( $this->get_client_id(), $this->get_client_secret() );
			}
		} catch ( Framework\SV_WC_API_Exception $exception ) {
			// quietly log any exception
			$this->get_plugin()->get_logger()->error( "Error communicating with Jilt when revoking OAuth token: {$exception->getMessage()}" );
		}
	}


	/**
	 * Gets the shop info from Jilt.
	 *
	 * @since 1.5.0
	 *
	 * @return \stdClass
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function get_shop() {

		if ( ! $this->is_linked() || $this->is_duplicate_site() ) {
			return null;
		}

		try {

			return $this->get_api()->get_shop();

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			// disconnect if the shop isn't found (e.g. remote private key was changed)
			if ( $exception->getCode() == 404 ) {
				$this->clear_connection_data();
			}

			// log and rethrow the exception
			$this->get_plugin()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );

			if ( ! defined( 'DOING_CRON' ) ) {
				throw $exception;
			}
		}
	}


	/**
	 * Update the shop info in Jilt once per day, useful for keeping track
	 * of which WP/WC versions are in use
	 *
	 * @since 1.0.0
	 *
	 * @param string|null $consumer_key a new WC REST API consumer key
	 * @param bool $is_retry whether this is a retry after failure, i.e. an invalid WC REST API key
	 * @throws Framework\SV_WC_API_Exception
	 */
	public function update_shop( $consumer_key = null, $is_retry = false ) {

		if ( ! $this->is_jilt_connected() ) {
			return;
		}

		// ensure the key is in a correct format
		$consumer_key = is_string( $consumer_key ) && Framework\SV_WC_Helper::str_starts_with( $consumer_key, 'ck_' ) ? $consumer_key : null;

		try {

			// update the linked shop record with the latest settings
			$this->get_api()->update_shop( $this->get_shop_data( $consumer_key ) );

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			// disconnect if the shop isn't found (e.g. remote private key was changed)
			if ( in_array( $exception->getCode(), array( 404, 401 ) ) ) {
				$this->clear_connection_data();
			}

			// log and rethrow the exception
			$this->get_plugin()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );

			if ( $consumer_key ) {

				// if this was a first attempt at setting the REST API keys, retry
				if ( ! $is_retry ) {

					$this->update_shop( $consumer_key, true );
					return;
				}

				// otherwise, just remove the local API keys
				$this->get_plugin()->get_wc_rest_api_handler_instance()->revoke_key();
			}

			if ( ! defined( 'DOING_CRON' ) ) {
				throw $exception;
			}
		}
	}


	/**
	 * Get and persist the public key for the current API user from the Jilt REST
	 * API
	 *
	 * @since 1.0.0
	 * @return string the public key
	 * @throws Framework\SV_WC_API_Exception on network exception or API error
	 */
	public function refresh_public_key() {

		return $this->get_public_key( true );
	}


	/**
	 * Returns the configured public key.
	 *
	 * Passing true will refresh the key from the Jilt REST API.
	 *
	 * @since 1.0.0
	 *
	 * @param boolean $refresh true if the current API user public key should be fetched from the Jilt API
	 * @return string the public key, if set
	 * @throws Framework\SV_WC_API_Exception on network exception or API error
	 */
	public function get_public_key( $refresh = false ) {

		$public_key = get_option( 'wc_jilt_public_key', null );

		if ( ( $refresh || ! $public_key ) && $this->is_configured() ) {
			update_option( 'wc_jilt_public_key', $this->get_api()->get_public_key() );
		}

		return $public_key;
	}


	/**
	 * Checks whether we have OAuth client credentials.
	 *
	 * @since 1.4.0
	 *
	 * @return bool true if we have credentials, false otherwise
	 */
	public function has_client_credentials() {
		return $this->get_client_id() && $this->get_client_secret();
	}


	/**
	 * Returns the OAuth client id.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_client_id() {
		return get_option( 'wc_jilt_client_id' );
	}


	/**
	 * Returns the OAuth client secret.
	 *
	 * @since 1.4.0
	 *
	 * @return string
	 */
	public function get_client_secret() {
		return get_option( 'wc_jilt_client_secret' );
	}


	/**
	 * Sets the OAuth client secret.
	 *
	 * Also adds to the secret key stash.
	 *
	 * @since 1.4.1
	 *
	 * @param string $secret client secret key
	 * @return string
	 */
	public function set_client_secret( $secret ) {

		update_option( 'wc_jilt_client_secret', $secret );

		$this->stash_secret_key( $secret );
	}


	/** Other methods ******************************************************/


	/**
	 * Update related Jilt order when order status changes
	 *
	 * @since 1.0.0
	 * @param int $order_id order ID
	 * @param string $old_status
	 * @param string $new_status
	 */
	public function order_status_changed( $order_id, $old_status, $new_status ) {

		if ( ! $this->is_jilt_connected() ) {
			return;
		}

		$order = new WC_Jilt_Order( $order_id );
		$cart_token = $order->get_jilt_cart_token();

		// generate a token if needed at checkout, otherwise bail so we're not
		// pushing order data for untracked orders up to Jilt
		if ( ! $cart_token ) {

			if ( defined( 'WOOCOMMERCE_CHECKOUT' ) && WOOCOMMERCE_CHECKOUT ) {

				$cart_token = $order->set_jilt_cart_token();

			} else {

				return;
			}
		}

		$jilt_placed_at    = $order->get_jilt_placed_at();
		$jilt_cancelled_at = $order->get_jilt_cancelled_at();

		// when a non-placed order transitions to a paid (processing/completed)
		// or on-hold status (unless "Recover Held Orders" is enabled), mark it
		// as placed. see also WC_Abstract_Order::update_status()
		if ( ! $jilt_placed_at && $this->is_placed( $order, $old_status, $new_status ) ) {

			$jilt_placed_at = current_time( 'timestamp', true );
			update_post_meta( $order_id, '_wc_jilt_placed_at', $jilt_placed_at );
			$jilt_placed_at = date( 'Y-m-d\TH:i:s\Z', $jilt_placed_at );
		}

		// handle order cancellation
		if ( ! $jilt_cancelled_at && 'cancelled' === $new_status ) {

			$jilt_cancelled_at = current_time( 'timestamp', true );
			update_post_meta( $order_id, '_wc_jilt_cancelled_at', $jilt_cancelled_at );
			$jilt_cancelled_at = date( 'Y-m-d\TH:i:s\Z', $jilt_cancelled_at );
		}

		$params = $order->get_jilt_order_data();

		$params['status'] = $new_status;

		if ( $jilt_placed_at ) {
			$params['placed_at'] = $jilt_placed_at;

			if ( $this->is_order_pending_recovery( $order_id ) || ( 'on-hold' === $old_status && $this->recover_held_orders() ) ) {
				$this->mark_order_as_recovered( $order_id );
			}
		}

		if ( $jilt_cancelled_at ) {
			$params['cancelled_at'] = $jilt_cancelled_at;
		}

		// update Jilt order details
		try {

			$this->get_api()->update_order( $cart_token, $params );

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			$this->get_plugin()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );
		}
	}


	/**
	 * Makes sure that the order.updated webhook is fired when refunds are made.
	 *
	 * @since 1.5.6
	 *
	 * @param array $topic_hooks the existing registered webhooks
	 * @param \WC_Webhook $webhook_instance the webhook instance
	 * @return array
	 */
	public function add_refund_to_order_updated_webhook( $topic_hooks, $webhook_instance ) {

		if ( isset( $topic_hooks['order.updated'] ) && ! in_array( 'woocommerce_order_refunded', $topic_hooks['order.updated'], true ) ) {
			$topic_hooks['order.updated'][] = 'woocommerce_order_refunded';
		}

		return $topic_hooks;
	}


	/** Helper methods ******************************************************/


	/**
	 * Marks an order as pending recovery.
	 *
	 * @since 1.5.0
	 *
	 * @param int|string $order_id order ID
	 */
	public function mark_order_as_pending_recovery( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return;
		}

		update_post_meta( $order_id, '_wc_jilt_pending_recovery', true );
	}


	/**
	 * Checks whether an order is pending recovery.
	 *
	 * @since 1.5.0
	 *
	 * @param int|string $order_id order ID
	 * @return bool
	 */
	public function is_order_pending_recovery( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		return (bool) get_post_meta( $order_id, '_wc_jilt_pending_recovery', true );
	}


	/**
	 * Checks whether an order is recovered.
	 *
	 * @since 1.5.0
	 *
	 * @param int|string $order_id order ID
	 * @return bool
	 */
	public function is_order_recovered( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof WC_Order ) {
			return false;
		}

		return (bool) get_post_meta( $order_id, '_wc_jilt_recovered', true );
	}


	/**
	 * Marks an order as recovered by Jilt.
	 *
	 * In 1.5.0 moved here from \WC_Jilt_Checkout_Handler
	 *
	 * @since 1.0.1
	 *
	 * @param int|string $order_id order ID
	 */
	public function mark_order_as_recovered( $order_id ) {

		$order = wc_get_order( $order_id );

		if ( ! $order instanceof \WC_Order || $this->is_order_recovered( $order_id ) ) {
			return;
		}

		delete_post_meta( $order_id, '_wc_jilt_pending_recovery' );
		update_post_meta( $order_id, '_wc_jilt_recovered', true );

		$order->add_order_note( __( 'Order recovered by Jilt.', 'jilt-for-woocommerce' ) );

		/**
		 * Fires when an order is recovered by Jilt.
		 *
		 * @since 1.4.2
		 *
		 * @param \WC_Order $order the order object
		 */
		do_action( 'wc_jilt_order_recovered', $order );
	}


	/**
	 * Checks whether the given order considered to be placed.
	 *
	 * @since 1.4.0
	 *
	 * @param \WC_Jilt_Order $order the order
	 * @param string $old_status
	 * @param string $new_status
	 * @return boolean true if $order is considered to be placed
	 */
	private function is_placed( $order, $old_status, $new_status ) {

		$placed = $order->is_paid() || ( $new_status === 'on-hold' && ! $this->recover_held_orders() );

		/**
		 * Filters whether the given order is considered to be placed.
		 *
		 * @since 1.4.0
		 *
		 * @param boolean $placed whether the order is considered placed
		 * @param \WC_Jilt_Order $order the order
		 * @param string $old_status
		 * @param string $new_status
		 * @param \WC_Jilt_Integration $this
		 */
		$placed = apply_filters( 'wc_jilt_order_is_placed', $placed, $order, $old_status, $new_status, $this );

		return $placed;
	}


	/**
	 * Return the timezone string for a store
	 *
	 * @since 1.2.0
	 * @return string
	 */
	protected function get_store_timezone() {
		return wc_timezone_string();
	}


	/**
	 * Is the current request being performed over ssl?
	 *
	 * This implementation does not use the wc_site_is_https() approach of
	 * testing the "home" wp option for "https" because that has been found not
	 * to be a very reliable indicator of SSL support.
	 *
	 * @since 1.2.0
	 * @return boolean true if the site is configured to use HTTPS
	 */
	protected function is_ssl() {
		return is_ssl();
	}


	/**
	 * Set the API object
	 *
	 * @since 1.1.0
	 * @param WC_Jilt_API $api the Jilt API object
	 */
	protected function set_api( $api ) {
		$this->api = $api;
	}


	/**
	 * Get the main plugin instance
	 *
	 * @since 1.2.0
	 * @return \WC_Jilt
	 */
	protected function get_plugin() {
		return wc_jilt();
	}


	/**
	 * Does there seem to be a coupon-enabled WC free shipping method available?
	 *
	 * Supports WC >= 2.6
	 *
	 * @return boolean true if there appears to be a coupon-enabled WC free
	 *   shipping method available.
	 */
	public function is_free_shipping_available() {
		global $wpdb;

		$zone_methods = $wpdb->get_results( "SELECT instance_id FROM {$wpdb->prefix}woocommerce_shipping_zone_methods as methods WHERE methods.method_id = 'free_shipping' AND is_enabled = 1" );
		foreach ( $zone_methods as $zone_method ) {
			$free_shipping_method = new WC_Shipping_Free_Shipping( $zone_method->instance_id );
			if ( in_array( $free_shipping_method->requires, array( 'coupon', 'either', 'both' ), true ) ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * Returns the shop address, including province_code/country_code for
	 * WC < 3.2.0, and address1, address2, city, and zip otherwise.
	 *
	 * @since 1.3.0
	 *
	 * @return array of shop address fields
	 */
	public function get_shop_address() {

		$base_location = wc_get_base_location();

		// state/country are always available
		$address = array(
			'province_code' => $base_location['state'],
			'country_code'  => $base_location['country'],
		);

		$wc_version = defined( 'WC_VERSION' ) && WC_VERSION ? WC_VERSION : null;

		// address1/address2/city/zip are available for later versions of WC
		if ( $wc_version && version_compare( $wc_version, '3.2.0', '>=' ) && isset( WC()->countries ) ) {
			$address = array_merge(
				$address,
				array(
					'address1' => WC()->countries->get_base_address(),
					'address2' => WC()->countries->get_base_address_2(),
					'city'     => WC()->countries->get_base_city(),
					'zip'      => WC()->countries->get_base_postcode(),
				)
			);
		}

		return $address;
	}


	/**
	 * Gets a stored Storefront parameter.
	 *
	 * @since 1.5.4-dev.
	 *
	 * @param string $name parameter name
	 * @param string $default default value to return if the setting isn't set
	 * @return string|null
	 */
	public function get_storefront_param( $name, $default = null ) {

		if ( ! is_string( $default ) ) {
			$default = null;
		}

		$params = $this->get_storefront_params();

		return isset( $params[ $name ] ) ? $params[ $name ] : $default;
	}


	/**
	 * Gets the stored Storefront parameters.
	 *
	 * @since 1.5.4-dev.
	 *
	 * @return array
	 */
	public function get_storefront_params() {

		return (array) get_option( 'jilt_storefront_params', array() );
	}


	/**
	 * Updates the stored Storefront parameters.
	 *
	 * @since 1.5.4
	 *
	 * @param array $params updated Storefront parameters
	 */
	public function update_storefront_params( array $params ) {

		update_option( 'jilt_storefront_params', $params );
	}


	/**
	 * Deletes the stored Storefront parameters.
	 *
	 * @since 1.6.0
	 */
	public function delete_storefront_params() {

		delete_option( 'jilt_storefront_params' );
	}


	/**
	 * Returns an array of settings with sensitive data removed.
	 *
	 * @since 1.6.4
	 *
	 * @param array $settings settings items
	 * @return array
	 */
	public function get_safe_settings( $settings ) {

		unset(
			$settings['secret_key'],
			$settings['consumer_key'],
			$settings['consumer_secret'],
			$settings['oauth_consumer_key'],
			$settings['oauth_nonce'],
			$settings['oauth_signature'],
			$settings['oauth_signature_method'],
			$settings['oauth_timestamp']
		);

		return $settings;
	}


	/** Admin delegator methods ******************************************************/


	/**
	 * Initializes form fields in the format required by WC_Integration
	 *
	 * @see WC_Settings_API::init_form_fields()
	 * @since 1.0.0
	 */
	public function init_form_fields() {
		// delegate to admin instance
		$this->admin->init_form_fields();
	}


	/**
	 * Returns the Jilt Connection Status "setting" HTML fragment
	 *
	 * @see WC_Jilt_Integration_Admin::generate_jilt_status_html()
	 *
	 * @since 1.2.0
	 *
	 * @param mixed $key
	 * @param mixed $data
	 * @return string HTML fragment
	 */
	public function generate_jilt_status_html( $key, $data ) {
		return $this->admin->generate_jilt_status_html( $key, $data );
	}


}
