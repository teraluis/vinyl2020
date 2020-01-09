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
 * Jilt API Response Class
 *
 * @since 1.0.0
 * @see SV_WC_API_JSON_Response
 */
class WC_Jilt_API_Response extends Framework\SV_WC_API_JSON_Response {


	/**
	 * Returns the string representation of this response with any and all sensitive elements masked
	 * or removed.
	 *
	 * @since 1.4.4
	 *
	 * @see \SV_WC_API_Response::to_string_safe()
	 *
	 * @return string
	 */
	public function to_string_safe() {

		$string = $this->to_string();

		if ( $string ) {

			$response = json_decode( $string );

			if ( isset( $response->token->access_token ) ) {
				$response->token->access_token = str_repeat( '*', strlen( $response->token->access_token ) );
			}
			if ( isset( $response->token->refresh_token ) ) {
				$response->token->refresh_token = str_repeat( '*', strlen( $response->token->refresh_token ) );
			}
			if ( isset( $response->client_secret ) ) {
				$response->client_secret = str_repeat( '*', strlen( $response->client_secret ) );
			}

			$string = wp_json_encode( $response, JSON_PRETTY_PRINT );
		}

		return $string;
	}


}
