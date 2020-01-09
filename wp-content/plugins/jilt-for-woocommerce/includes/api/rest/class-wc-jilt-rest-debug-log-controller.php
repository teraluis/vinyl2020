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
 * The WC REST API Jilt debug log controller.
 *
 * This class adds routes for reading the integration debug logs.
 *
 * @since 1.5.0
 */
class WC_Jilt_REST_Debug_Log_Controller extends WC_REST_Controller {


	/** @var string endpoint namespace */
	protected $namespace = 'wc/v2';

	/** @var string the route base */
	protected $rest_base = 'jilt/logs';


	/**
	 * Registers the routes for the Jilt integration log.
	 *
	 * @since 1.5.0
	 */
	public function register_routes() {

		// retrieve the log file contents
		register_rest_route( $this->namespace, "/{$this->rest_base}/file", array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_item' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
		) );

		// TODO: add a route for getting DB log entries, handled by \WC_Log_Handler_DB {CW 2018-04-03}
	}


	/**
	 * Checks if a given request has access to read the Jilt integration log.
	 *
	 * @since 1.5.0
	 *
	 * @param \WP_REST_Request $request request object
	 * @return bool|\WP_Error
	 */
	public function get_item_permissions_check( $request ) {

		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new \WP_Error( 'wc_jilt_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'jilt-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}

		return true;
	}


	/**
	 * Gets the Jilt integration log.
	 *
	 * @param \WP_REST_Request $request request object
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {

		$params = $request->get_params();
		$log_date = null;

		if ( isset( $params['date'] ) && preg_match( '/\d{4}-\d{2}-\d{2}/', $params['date'] ) ) {
			$log_date = $params['date'];
		}

		$file_path = \WC_Log_Handler_File::get_log_file_path( wc_jilt()->get_id() );

		if ( $log_date ) {
			$file_path = preg_replace( '/jilt-\d{4}-\d{2}-\d{2}/', 'jilt-' . $params['date'], $file_path );
		}

		// no log file present
		if ( ! $file_path || ! file_exists( $file_path ) ) {
			return new \WP_Error( 'wc_jilt_rest_log_file_not_found', "Resource '" . basename( $file_path ) . "' does not exist.", array( 'status' => 404 ) );
		}

		if ( ! is_readable( $file_path ) ) {
			return new \WP_Error( 'wc_jilt_rest_log_file_not_readable', "Resource '" . basename( $file_path ) . "' is not readable.", array( 'status' => 404 ) );
		}

		$file = file_get_contents( $file_path );

		// something went wrong while reading the file
		// this shouldn't happen, but just in case
		if ( false === $file ) {
			return new \WP_Error( 'wc_jilt_rest_log_file_error', 'The log file could not be sent.', array( 'status' => 500 ) );
		}

		$data = array(
			'log_file'   => basename( $file_path ),
			'contents'   => $file,
			'updated_at' => date( 'Y-m-d\TH:i:s\Z', filemtime( $file_path ) ),
		);

		return rest_ensure_response( $data );
	}


}
