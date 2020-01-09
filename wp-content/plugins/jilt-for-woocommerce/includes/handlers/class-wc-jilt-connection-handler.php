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
 * @package   WC-Jilt/Frontend
 * @author    Jilt
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

use SkyVerge\WooCommerce\PluginFramework\v5_4_0 as Framework;

/**
 * Connection class.
 *
 * Handles Jilt connections.
 *
 * @since 1.4.0
 */
class WC_Jilt_Connection_Handler {


	/**
	 * Initializes the class.
	 *
	 * @since 1.4.0
	 */
	public function __construct() {

		if ( 'init' === $_REQUEST['connect'] ) {
			$this->handle_connect();
		} elseif ( 'done' === $_REQUEST['connect'] ) {
			$this->handle_connect_callback();
		}
	}


	/**
	 * Initiates the auth flow to connect the plugin to Jilt.
	 *
	 * @since 1.4.0
	 */
	private function handle_connect() {

		// check nonce
		if ( ! wp_verify_nonce( $_GET['nonce'], 'wc-jilt-connect-init' ) ) {
			return;
		}

		// only shop managers can connect to jilt
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$integration = wc_jilt()->get_integration();

		// no client id or secret present, or duplicate site, try to request client credentials
		if ( ! $integration->has_client_credentials() || $integration->is_duplicate_site() ) {
			$this->request_client_credentials(); // if this fails, user is redirected back to admin with a notice
		}

		$client_id = $integration->get_client_id();
		$state     = wp_create_nonce( 'wc-jilt-connect' );

		/**
		 * Filters the connection redirect args used when connecting to Jilt.
		 *
		 * @since 1.6.0
		 *
		 * @param array redirect args
		 */
		$redirect_args = apply_filters( 'wc_jilt_app_connection_redirect_args', array(
			'client_id'     => $client_id,
			'domain'        => urlencode( wc_jilt()->get_shop_domain() ),
			'email'         => urlencode( wc_jilt()->get_admin_email() ),
			'first_name'    => urlencode( wc_jilt()->get_admin_first_name() ),
			'last_name'     => urlencode( wc_jilt()->get_admin_last_name() ),
			'ssl'           => is_ssl(),
			'state'         => $state,
			'redirect_uri'  => rawurlencode( wc_jilt()->get_callback_url() ),
			'response_type' => 'code',
		) );

		wp_redirect( add_query_arg( $redirect_args, $integration->get_api()->get_connect_endpoint() ) );
		exit();
	}


	/**
	 * Requests installation-specific OAuth client credentials from Jilt.
	 *
	 * @since 1.4.0
	 */
	private function request_client_credentials() {

		$response = null;

		try {
			$response = wc_jilt()->get_integration()->get_api()->get_client_credentials( wc_jilt()->get_shop_domain(), wc_jilt()->get_callback_url() );

		} catch ( Framework\SV_WC_API_Exception $e ) {

			$error = $e->getMessage();

			/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - error message, %3$s - solution message */
			$notice = sprintf( __( '%1$sError communicating with Jilt%2$s %3$s %4$s', 'jilt-for-woocommerce' ),
				'<strong>',
				'</strong>',
				$error ? ( ': ' . $error . '.' ) : '', // add full stop
				sprintf(__( 'Please %1$sget in touch with Jilt Support%2$s to resolve this issue.', 'jilt-for-woocommerce' ),
					'<a target="_blank" href="' . esc_url( wc_jilt()->get_support_url( array( 'message' => $error ) ) ) . '">',
					'</a>'
				)
			);

			$message_handler = wc_jilt()->get_message_handler();
			$message_handler->add_error( $notice );

			wp_redirect( wc_jilt()->get_settings_url() );
			exit;
		}

		// TODO: consider adding dedicated setters for these {IT 2018-01-09}
		update_option( 'wc_jilt_client_id', $response->client_id );
		update_option( 'wc_jilt_client_secret', $response->client_secret );

		// stash the current client secret so that if a new client is created at some point,
		// we can still verify the recovery urls created by previous clients
		wc_jilt()->get_integration()->stash_secret_key( $response->client_secret );
	}


	/**
	 * Handles callbacks from Jilt connect requests.
	 *
	 * @since 1.4.0
	 */
	private function handle_connect_callback() {

		// verify state
		if ( empty( $_GET['state'] ) || ! wp_verify_nonce( $_GET['state'], 'wc-jilt-connect' ) ) {
			wp_die( 'Missing or invalid param: state' );
		}

		if ( empty( $_GET['code'] ) ) {
			wp_die( 'Missing or invalid param: code' );
		}

		$response = null;

		try {
			$integration = wc_jilt()->get_integration();
			$response    = $integration->get_api()->get_oauth_tokens( $_GET['code'], wc_jilt()->get_callback_url(), $integration->get_client_id(), $integration->get_client_secret() );
		} catch ( Framework\SV_WC_API_Exception $e ) {

			$error = $e->getMessage();

			/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - error message, %3$s - solution message */
			$notice = sprintf( __( '%1$sError communicating with Jilt%2$s: %3$s %4$s', 'jilt-for-woocommerce' ),
				'<strong>',
				'</strong>',
				$error ? ( ': ' . $error . '.' ) : '', // add full stop
				sprintf(__( 'Please %1$sget in touch with Jilt Support%2$s to resolve this issue.', 'jilt-for-woocommerce' ),
					'<a target="_blank" href="' . esc_url( wc_jilt()->get_support_url( array( 'message' => $error ) ) ) . '">',
					'</a>'
				)
			);

			$message_handler = wc_jilt()->get_message_handler();
			$message_handler->add_error( $notice );

			wp_redirect( wc_jilt()->get_settings_url() );
			exit;
		}

		$data      = $response->response_data;
		$shop_id   = $data->shop_id;
		$shop_uuid = $data->shop_uuid;

		unset( $data->shop_uuid, $data->shop_id ); // don't store shop identifiers twice

		$integration->set_access_token( (array) $data );
		$integration->set_linked_shop_id( $shop_id );
		$integration->set_linked_shop_uuid( $shop_uuid );
		$integration->set_linked_shop_domain(); // store a historical reference to the connected shop's domain

		// remove secret key, if it was used previously
		if ( $integration->get_secret_key() ) {

			$integration->set_secret_key( null );

			unset( $integration->settings['secret_key'] );

			update_option( $integration->get_option_key(), $integration->settings );
		}

		$redirect_url = wc_jilt()->get_settings_url();

		try {

			$integration->refresh_public_key();

			$consumer_key = null;

			if ( ! wc_jilt()->get_wc_rest_api_handler_instance()->key_permissions_are_correct() ) {

				$key = wc_jilt()->get_wc_rest_api_handler_instance()->refresh_key();

				$consumer_key = $key->consumer_key;
			}

			// update the linked shop record with the latest settings
			// TODO: is this logic correct? {JS: 2018-06-15}
			$integration->update_shop( $consumer_key );

			// now that we're connected, dismiss the get started notice
			wc_jilt()->get_admin_notice_handler()->dismiss_notice( 'get-started-notice' );

			// if the shop is connected for the first time, show the welcome splash screen
			if ( ! get_option( 'wc_jilt_skip_welcome_screen', false ) ) {

				$redirect_url = add_query_arg( 'tab', 'welcome', $redirect_url );

				update_option( 'wc_jilt_skip_welcome_screen', true );

			} else {

				// otherwise, simply add a notice
				/* translators: %1$s - opening <a> tag, %2$s - closing </a> tag */
				$message = sprintf( __( 'Congratulations! Your shop is now connected to Jilt. You\'re now ready to %1$ssetup your first campaign%2$s to start sending emails to your customers.', 'jilt-for-woocommerce' ),
					'<a target="_blank" href="' . esc_url( wc_jilt()->get_app_endpoint() ) . '">',
					'</a>'
				);

				wc_jilt()->get_message_handler()->add_message( $message );
			}

		} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

			// well, this sucks... let's add a message and redirect back to admin

			/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - error message, %4$s - solution message */
			$message = sprintf( __( '%1$sError communicating with Jilt%2$s: %3$s %4$s', 'jilt-for-woocommerce' ),
				'<strong>',
				'</strong>',
				': ' . $exception->getMessage() . '.', // add full stop
				sprintf(__( 'Please %1$sget in touch with Jilt Support%2$s to resolve this issue.', 'jilt-for-woocommerce' ),
					'<a target="_blank" href="' . esc_url( wc_jilt()->get_support_url( array( 'message' => $exception->getMessage() ) ) ) . '">',
					'</a>'
				)
			);

			wc_jilt()->get_message_handler()->add_error( $message );
		}

		wp_redirect( $redirect_url );
		exit;
	}


}
