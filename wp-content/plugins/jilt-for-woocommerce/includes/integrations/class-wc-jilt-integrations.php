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
 * Manages Jilt integrations with 3rd party plugins.
 *
 * @since 1.3.0
 */
class WC_Jilt_Integrations {


	/** @var \WC_Jilt_Integration_Base[] holds integration instances */
	public $integrations;


	/**
	 * Loads integrations.
	 *
	 * @since 1.3.0
	 *
	 * @param \WC_Jilt $wc_jilt plugin main class
	 */
	public function __construct( $wc_jilt ) {

		// integrations base abstract
		require_once( $wc_jilt->get_plugin_path() . '/includes/integrations/abstract-wc-jilt-integration-base.php' );

		/**
		 * Allow third party Jilt integrations to be registered.
		 *
		 * @since 1.3.0
		 *
		 * @param array|\WC_Jilt_Integration_Base[] $integrations array of string integration class names, or WC_Jilt_Integration integration instances
		 */
		$load_integrations = apply_filters( 'wc_jilt_integrations', array(
			'WC_Jilt_Subscriptions_Integration',
			'WC_Jilt_Product_Bundles_Integration',
			'WC_Jilt_Composite_Products_Integration',
			'WC_Jilt_Gift_Cards_Integration',
			'WC_Jilt_Paypal_Standard_Integration',
			'WC_Jilt_Advanced_Access_Manager_Integration',
			'WC_Jilt_Afterpay_Integration',
			'WC_Jilt_Pressable_Integration',
		) );

		foreach ( $load_integrations as $integration ) {

			if ( is_object( $integration ) || $integration instanceof \WC_Jilt_Integration_Base ) {

				$instance   = $integration;
				$class_name = get_class( $integration );

			} elseif ( is_string( $integration ) ) {

				$file = 'class-' . strtolower( str_replace( '_', '-', $integration ) ) . '.php';
				$path = $wc_jilt->get_plugin_path() . '/includes/integrations/' . $file;

				if ( ! is_readable( $path ) ) {
					continue;
				}

				if ( ! class_exists( $integration ) ) {
					require_once( $path );
				}

				$instance   = new $integration;
				$class_name = $integration;

			} else {

				continue;
			}

			$this->integrations[ $class_name ] = $instance;
		}
	}


	/**
	 * Gets an integration handler.
	 *
	 * @since 1.5.5
	 *
	 * @param string $class_name class name
	 * @return null|\WC_Jilt_Integration_Base
	 */
	public function get_integration_handler_instance( $class_name ) {

		return isset( $this->integrations[ $class_name ] ) ? $this->integrations[ $class_name ] : null;
	}


}
