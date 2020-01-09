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
 * Admin class
 *
 * @since 1.0.0
 */
class WC_Jilt_Admin_Status {


	/** @var string custom debug message */
	protected $debug_message;


	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'woocommerce_system_status_report', array( $this, 'add_jilt_status' ) );

		add_filter( 'woocommerce_debug_tools', array( $this, 'add_debug_tools' ) );

		// if one of our tools ran, ensure the success/failure message is correctly displayed
		if ( in_array( Framework\SV_WC_Helper::get_request( 'action' ), array( 'wc_jilt_delete_coupons', 'wc_jilt_clear_connection_data' ), true ) ) {
			add_filter( 'gettext', array( $this, 'translate_success_message' ), 10, 3 );
		}
	}


	/**
	 * Add Jilt status box to the WC Status page
	 *
	 * @since 1.0.0
	 */
	public function add_jilt_status() {
		?>
		<table class="wc_status_table widefat" cellspacing="0" id="jilt-status">
			<thead>
				<tr>
					<th colspan="3" data-export-label="Jilt"><?php esc_html_e( 'Jilt Abandoned Cart Recovery', 'jilt-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td data-export-label="Plugin Version"><?php esc_html_e( 'Plugin Version', 'jilt-for-woocommerce' ); ?>:</td>
					<td class="help"><?php echo wc_help_tip( esc_html__( 'The version of the Jilt plugin installed on your site.', 'jilt-for-woocommerce' ) ); ?></td>
					<td>
						<?php echo esc_html( wc_jilt()->get_version() ); ?>
						<?php if ( wc_jilt()->is_plugin_update_available() ) : ?>
							&ndash;
							<strong style="color:red;">
								<?php echo esc_html( sprintf( _x( '%s is available', 'Version info', 'jilt-for-woocommerce' ), wc_jilt()->get_latest_plugin_version() ) ) ?>
							</strong>
						<?php endif; ?>
					</td>
				</tr>
				<tr>
					<td data-export-label="Jilt API Version"><?php esc_html_e( 'Jilt API Version', 'jilt-for-woocommerce' ); ?>:</td>
					<td class="help"><?php echo wc_help_tip( esc_html__( 'The version of the Jilt REST API supported by this plugin.', 'jilt-for-woocommerce' ) ); ?></td>
					<td><?php echo esc_html( WC_Jilt_API::get_api_version() ); ?></td>
				</tr>
				<tr>
					<td data-export-label="Jilt API Authentication Method"><?php esc_html_e( 'Jilt API Authentication Method', 'jilt-for-woocommerce' ); ?>:</td>
					<td class="help"><?php echo wc_help_tip( esc_html__( 'Displays the authentication method used to connect to the Jilt API.', 'jilt-for-woocommerce' ) ); ?></td>
					<td><?php ( ( 'secret_key' === wc_jilt()->get_integration()->get_auth_method() ) ? esc_html_e( 'Secret key', 'jilt-for-woocommerce' ) : esc_html_e( 'OAuth', 'jilt-for-woocommerce' ) ); ?></td>
				</tr>
				<tr>
					<td data-export-label="Jilt API Connected"><?php esc_html_e( 'Jilt API Connected', 'jilt-for-woocommerce' ); ?>:</td>
					<td class="help"><?php echo wc_help_tip( esc_html__( 'Indicates whether the plugin has been successfully configured and connected to the Jilt API.', 'jilt-for-woocommerce' ) ); ?></td>
					<td><?php
						if ( wc_jilt()->get_integration()->has_connected() ) :
							echo $this->status_ok_mark();
						else:
							if ( 'secret_key' === wc_jilt()->get_integration()->get_auth_method() ) :
								/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
								$message = esc_html__( 'Please ensure the plugin is properly %1$sconfigured%2$s with your Jilt secret key.', 'jilt-for-woocommerce' );
							else :
								/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
								$message = esc_html__( 'Please ensure the plugin is %1$sconnected%2$s to Jilt.', 'jilt-for-woocommerce' );
							endif;
							echo $this->status_warning_mark( sprintf( $message, '<a href="' . esc_url( wc_jilt()->get_settings_url() ) . '">', '</a>' ) );
						endif;
					?></td>
				</tr>
				<tr>
					<td data-export-label="Linked to Jilt"><?php esc_html_e( 'Linked to Jilt', 'jilt-for-woocommerce' ); ?>:</td>
					<td class="help"><?php echo wc_help_tip( esc_html__( 'Indicates whether the plugin has successfully linked your shop to your Jilt account.', 'jilt-for-woocommerce' ) ); ?></td>
					<td><?php
						if ( wc_jilt()->get_integration()->is_linked() ) :
							echo $this->status_ok_mark();
						else:
							if ( 'secret_key' === wc_jilt()->get_integration()->get_auth_method() ) :
								/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
								$message = esc_html__( 'Please ensure the plugin is properly %1$sconfigured%2$s with your Jilt secret key.', 'jilt-for-woocommerce' );
							else :
								/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
								$message = esc_html__( 'Please ensure the plugin is %1$sconnected%2$s to Jilt.', 'jilt-for-woocommerce' );
							endif;
							echo $this->status_warning_mark( sprintf( $message, '<a href="' . esc_url( wc_jilt()->get_settings_url() ) . '">', '</a>' ) );
						endif;
					?></td>
				</tr>
				<tr>
					<td data-export-label="Enabled"><?php esc_html_e( 'Enabled', 'jilt-for-woocommerce' ); ?>:</td>
					<td class="help"><?php echo wc_help_tip( esc_html__( 'Indicates whether the plugin is enabled and sending Order data to Jilt.', 'jilt-for-woocommerce' ) ); ?></td>
					<td><?php
						if ( ! wc_jilt()->get_integration()->has_connected() || ! wc_jilt()->get_integration()->is_linked() || wc_jilt()->get_integration()->is_disabled() || wc_jilt()->get_integration()->is_duplicate_site() ) :
							$message = '';

							if ( wc_jilt()->get_integration()->is_duplicate_site() ) {
								/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
								$message = sprintf( __( 'It looks like this site has moved or is a duplicate site. For more information please %1$sget in touch%2$s', 'jilt-for-woocommerce' ),
									'<strong>', '</strong>',
									'<a target="_blank" href="' . wc_jilt()->get_support_url() . '">', '</a>'
								);
							}

							echo $this->status_warning_mark( $message );
						else:
							echo $this->status_ok_mark();
						endif;
					?></td>
				</tr>
				<tr>
					<td data-export-label="WooCommerce API"><?php esc_html_e( 'WooCommerce API', 'jilt-for-woocommerce' ); ?>:</td>
					<td class="help"><?php echo wc_help_tip( esc_html__( 'Indicates whether Jilt for WooCommerce is able to connect to and retrieve data from this store over the WooCommerce API.', 'jilt-for-woocommerce' ) ); ?></td>
					<td><?php
						if ( ! wc_jilt()->get_integration()->is_jilt_connected() ) {

							echo '&ndash;';

						} elseif ( ! wc_jilt()->get_wc_rest_api_handler_instance()->is_configured() ) {

							$message = wc_jilt()->get_wc_rest_api_handler_instance()->get_api_configuration_error_long();
							echo $this->status_warning_mark( $message );

						} else {

							echo $this->status_ok_mark();
						}
					?></td>
				</tr>
			</tbody>
		</table>
		<?php
	}


	/**
	 * Adds the Jilt for WooCommerce debug tools.
	 *
	 * @since 1.4.0
	 *
	 * @param array $tools WooCommerce core tools
	 * @return array
	 */
	public function add_debug_tools( $tools ) {

		$tools['wc_jilt_clear_connection_data'] = array(
			'name'     => __( 'Clear Jilt connection data', 'jilt-for-woocommerce' ),
			'button'   => __( 'Clear', 'woocommerce-plugin-framework' ),
			'desc'     => __( 'This tool will clear all Jilt connection data from the database, including OAuth client credentials.', 'jilt-for-woocommerce' ),
			'callback' => array( $this, 'run_clear_connection_data_tool' ),
		);

		$tools['wc_jilt_delete_coupons'] = array(
			'name'     => __( 'Delete Jilt coupons', 'jilt-for-woocommerce' ),
			'button'   => __( 'Delete', 'woocommerce-plugin-framework' ),
			'desc'     => sprintf(
				/** translators: Placeholders: %s - number of coupons to be deleted every time the tool is run */
				__( 'This tool will delete unused, expired coupons that were created by Jilt (up to %s at a time)', 'jilt-for-woocommerce' ),
				$this->get_delete_coupons_tool_per_run_limit()
			),
			'callback' => array( $this, 'run_delete_coupons_tool' ),
		);

		return $tools;
	}


	/**
	 * Runs the the clear connection data debug tool.
	 *
	 * @since 1.4.0
	 *
	 * @return bool
	 */
	public function run_clear_connection_data_tool() {

		wc_jilt()->get_integration()->clear_connection_data();

		$this->debug_message = __( 'Jilt connection data has been cleared.', 'jilt-for-woocommerce' );

		// WC 2.6 has no positive message output by default
		if ( Framework\SV_WC_Plugin_Compatibility::is_wc_version_lt_3_0() ) {

			echo '<div class="updated inline"><p>' . esc_html( $this->debug_message ) . '</p></div>';
		}

		return true;
	}


	/**
	 * Runs the the delete coupons debug tool.
	 *
	 * @since 1.5.0
	 *
	 * @return bool
	 */
	public function run_delete_coupons_tool() {

		$coupon_ids = get_posts( array(
			'fields'         => 'ids',
			'post_type'      => 'shop_coupon',
			'post_status'    => 'any',
			'posts_per_page' => $this->get_delete_coupons_tool_per_run_limit(),
			'meta_query'     => array(
				array(
					'key'     => 'jilt_discount_id',
					'compare' => 'EXISTS',
				),
			),
		) );

		$deleted = 0;

		foreach ( $coupon_ids as $coupon_id ) {

			$coupon = new \WC_Coupon( $coupon_id );

			// sanity check to ensure this is a valid Jilt discount coupon
			if ( ! $coupon->get_id() || ! $coupon->get_meta( 'jilt_discount_id' ) ) {
				continue;
			}

			// if this is an expired coupon that has never been used, it's toast
			if ( $coupon->get_usage_count() === 0 && $coupon->get_date_expires() && current_time( 'timestamp', true ) > $coupon->get_date_expires()->getTimestamp() ) {

				// delete permanently
				if ( $coupon->delete( true ) ) {
					$deleted++;
				}
			}
		}

		if ( ! empty( $deleted ) ) {
			$message = sprintf( _n( 'Success! %d Jilt coupon deleted.', 'Success! %d Jilt coupons deleted.', $deleted, 'jilt-for-woocommerce' ), $deleted );
		} else {
			$message = __( 'Success! All unused, expired Jilt coupons have been deleted.', 'jilt-for-woocommerce' );
		}

		$this->debug_message = $message;

		return true;
	}


	/**
	 * Gets the number of Jilt coupons to delete in one run of the Delete Coupons tool.
	 *
	 * @since 1.5.0
	 *
	 * @return int
	 */
	protected function get_delete_coupons_tool_per_run_limit() {

		/**
		 * Filters the number of Jilt coupons to delete in one run of the Delete Coupons tool.
		 *
		 * Sites can increase or decrease this value to handle timeouts.
		 *
		 * @since 1.5.0
		 *
		 * @param int $per_run number of Jilt coupons to delete in one run
		 */
		$per_run = (int) apply_filters( 'wc_jilt_admin_delete_coupons_tool_per_run_limit', 200 );

		return max( 1, $per_run );
	}


	/**
	 * Translates the tool success message.
	 *
	 * This can be removed in favor of returning the message string in `run_debug_tool()`
	 * when WC 3.1 is required, though that means the message will always be "success" styled.
	 *
	 * @since 1.4.0
	 *
	 * @param string $translated the text to output
	 * @param string $original the original text
	 * @param string $domain the textdomain
	 * @return string the updated text
	 */
	public function translate_success_message( $translated, $original, $domain ) {

		if ( 'woocommerce' === $domain && ( 'Tool ran.' === $original || 'There was an error calling %s' === $original ) && $this->debug_message ) {
			$translated = $this->debug_message;
		}

		return $translated;
	}


	/**
	 * Get the status warning mark HTML snippet, with optional message
	 *
	 * @since 1.5.0
	 *
	 * @param string $message optional warning message to display
	 * @return string html snippet
	 */
	private function status_warning_mark( $message = null ) {

		return '<mark class="error"><span class="dashicons dashicons-warning"></span> ' . wp_kses_post( $message ) . '</mark>';
	}


	/**
	 * Get the status ok mark HTML snippet, with optional message
	 *
	 * @since 1.5.0
	 *
	 * @param string $message optional success message to display
	 * @return string html snippet
	 */
	private function status_ok_mark( $message = null ) {

		return '<mark class="yes"><span class="dashicons dashicons-yes"></span>' . wp_kses_post( $message ) . '</mark>';
	}


}
