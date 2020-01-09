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
 * The WC REST API Jilt settings controller.
 *
 * This class adds routes for reading, updating, and deleting the Jilt
 * integration settings.
 *
 * @since 1.5.0
 */
class WC_Jilt_REST_Settings_Controller extends WC_REST_Controller {


	/** @var string endpoint namespace */
	protected $namespace = 'wc/v2';

	/** @var string the route base */
	protected $rest_base = 'jilt/settings';


	/**
	 * Registers the routes for the Jilt integration settings.
	 *
	 * @since 1.5.0
	 */
	public function register_routes() {

		register_rest_route( $this->namespace, '/' . $this->rest_base, array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_items' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
			),
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( $this, 'update_items' ),
				'permission_callback' => array( $this, 'update_items_permissions_check' ),
				'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
			),
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( $this, 'delete_item' ),
				'permission_callback' => array( $this, 'delete_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}


	/**
	 * Checks if a given request has access to read the Jilt settings.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_REST_Request $request request object
	 * @return bool|\WP_Error
	 */
	public function get_items_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new \WP_Error( 'wc_jilt_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'jilt-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}


	/**
	 * Gets the Jilt settings values.
	 *
	 * @param \WP_REST_Request $request request object
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {

		$safe_settings     = wc_jilt()->get_integration()->get_safe_settings( wc_jilt()->get_integration()->get_settings() );
		$storefront_params = wc_jilt()->get_integration()->get_storefront_params();

		// TODO: refactor to include non-saved default setting values e.g. https://github.com/woocommerce/woocommerce/blob/590af1dd2da9c1615ec8065a1f9bda5e5030128e/includes/api/class-wc-rest-payment-gateways-controller.php#L288 {JS: 2018-06-21}
		return $this->prepare_items_for_response( array_merge( $safe_settings, $storefront_params ) ); // return the plugin settings and Storefront params combined, with the latter overriding any old plugin settings
	}


	/**
	 * Checks if a given request has access to update the Jilt settings.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_REST_Request $request request object
	 * @return bool|\WP_Error
	 */
	public function update_items_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'edit' ) ) {
			return new WP_Error( 'wc_jilt_rest_cannot_edit', __( 'Sorry, you cannot edit this resource.', 'jilt-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}


	/**
	 * Updates the Jilt settings values.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_REST_Request $request request object
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update_items( $request ) {

		$settings          = wc_jilt()->get_integration()->get_settings();
		$safe_settings     = wc_jilt()->get_integration()->get_safe_settings( $settings );
		$form_fields       = wc_jilt()->get_integration()->get_form_fields();
		$storefront_params = wc_jilt()->get_integration()->get_storefront_params();

		foreach ( $request->get_json_params() as $setting => $value ) {

			// if the setting is one of the plugin form fields
			if ( isset( $safe_settings[ $setting ], $form_fields[ $setting ] ) ) {

				// if the corresponding field has a type, validate it
				if ( ! empty( $form_fields[ $setting ]['type'] ) ) {

					$field = $form_fields[ $setting ];

					if ( is_callable( array( $this, 'validate_setting_' . $field['type'] . '_field' ) ) ) {
						$value = $this->{'validate_setting_' . $field['type'] . '_field'}( $value, $field );
					} else {
						$value = $this->validate_setting_text_field( $value, $field );
					}

					if ( is_wp_error( $value ) ) {
						return $value;
					}
				}

				$settings[ $setting ] = $safe_settings[ $setting ] = $value;

			// otherwise, it's a storefront param
			} else {

				$storefront_params[ $setting ] = $value;
			}
		}

		// since we accept "all the rest" to storefront params, re-check them for safety
		$storefront_params = wc_jilt()->get_integration()->get_safe_settings( $storefront_params );

		wc_jilt()->get_integration()->update_settings( $settings );
		wc_jilt()->get_integration()->update_storefront_params( $storefront_params );

		return $this->prepare_items_for_response( array_merge( $safe_settings, $storefront_params ) );
	}


	/**
	 * Prepares the Jilt settings for response.
	 *
	 * This sets the appropriate types based on the schema and removes any
	 * sensitive values, like secret key.
	 *
	 * @since 1.5.0
	 *
	 * @param array $settings settings items
	 * @return \WP_REST_Response|\WP_Error
	 */
	private function prepare_items_for_response( $settings ) {

		$schema = $this->get_item_schema();

		foreach ( $settings as $setting => $value ) {

			if ( ! empty( $schema['properties'][ $setting ]['type'] ) ) {
				settype( $settings[ $setting ], $schema['properties'][ $setting ]['type'] );
			}
		}

		return rest_ensure_response( wc_jilt()->get_integration()->get_safe_settings( $settings ) );
	}


	/**
	 * Checks if a given request has access to delete the Jilt connection.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_REST_Request $request request object
	 * @return bool|\WP_Error
	 */
	public function delete_item_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'delete' ) ) {
			return new WP_Error( 'wc_jilt_rest_cannot_delete', __( 'Sorry, you cannot delete this resource.', 'jilt-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}


	/**
	 * Deletes the Jilt integration connection.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_REST_Request $request request object
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {

		wc_jilt()->get_integration()->clear_connection_data();

		return rest_ensure_response( array() );
	}


	/**
	 * Gets the settings schema, conforming to JSON Schema.
	 *
	 * @since 1.5.0
	 *
	 * @return array
	 */
	public function get_item_schema() {

		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'settings',
			'type'       => 'object',
			'properties' => array(
				'post_checkout_registration' => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
				'log_threshold' => array(
					'type'    => 'integer',
					'context' => array( 'view', 'edit' ),
				),
				'show_email_usage_notice' => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
				'show_marketing_consent_opt_in' => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
				'checkout_consent_prompt' => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
				'recover_held_orders' => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
				'capture_email_on_add_to_cart' => array(
					'type'    => 'string',
					'context' => array( 'view', 'edit' ),
				),
			),
		);
	}


}
