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
 * @package   WC-Jilt/Handlers
 * @author    Jilt
 * @category  Frontend
 * @copyright Copyright (c) 2015-2019, SkyVerge, Inc.
 * @license   http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

defined( 'ABSPATH' ) or exit;

/**
 * Transactional Email Handler class
 *
 * Handles disabling core WC emails when the email is being managed by Jilt.
 *
 * @since 1.6.0
 */
class WC_Jilt_Managed_Email_Notifications_Handler {


	/** @var array email ids being managed by Jilt */
	protected $managed_email_notifications;


	/**
	 * Constructs the managed email notifications handler class.
	 *
	 * @since 1.6.0
	 */
	public function __construct() {

		add_action( 'init', array( $this, 'manage_emails' ) );

		// email settings table customizations
		add_filter( 'woocommerce_email_setting_columns',                    array( $this, 'customize_email_setting_columns' ) );
		add_action( 'woocommerce_email_setting_column_wc_jilt_status',      array( $this, 'render_email_status_column' ) );
		add_action( 'woocommerce_email_setting_column_wc_jilt_jilt_status', array( $this, 'render_email_jilt_status_column' ) );

		add_action( 'woocommerce_email_settings_before', array( $this, 'redirect_managed_email_settings_to_jilt' ) );
	}


	/**
	 * Initializes email management.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 */
	public function manage_emails() {

		if ( ! wc_jilt()->get_integration()->is_jilt_connected() ) {
			return;
		}

		$this->managed_email_notifications = wc_jilt()->get_integration()->get_managed_email_notifications();

		if ( empty( $this->managed_email_notifications ) || ! is_array( $this->managed_email_notifications ) ) {
			return;
		}

		// disable managed emails
		foreach ( $this->managed_email_notifications as $wc_email_id => $notification ) {

			if ( $this->is_email_managed( $wc_email_id ) ) {
				add_filter( "woocommerce_email_enabled_${wc_email_id}", '__return_false' );
			}
		}

		add_filter( 'woocommerce_email_title',       array( $this, 'override_managed_email_title' ), 10, 2 );
		add_filter( 'woocommerce_email_description', array( $this, 'override_managed_email_description' ), 10, 2 );
	}


	/**
	 * Overrides email titles for managed emails.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 *
	 * @param string $title the email title
	 * @param \WC_Email $email the email object
	 * @return string
	 */
	public function override_managed_email_title( $title, $email ) {

		if ( isset( $email->id ) && $this->is_email_managed( $email->id ) ) {

			$title .= __( ' (Managed by Jilt)', 'jilt-for-woocommerce' );
		}

		return $title;
	}


	/**
	 * Overrides email description for managed emails.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 *
	 * @param string $description description text
	 * @param \WC_Email $email the email object
	 * @return string
	 */
	public function override_managed_email_description( $description, $email ) {

		if ( isset( $email->id ) && $this->is_email_managed( $email->id ) ) {

			$description .= __( ' This email is being managed and sent by Jilt.', 'jilt-for-woocommerce' );
		}

		return $description;
	}


	/**
	 * Customizes the columns in the email settings table.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 *
	 * @param array $columns
	 * @return array
	 */
	public function customize_email_setting_columns( $columns ) {

		$column_keys = array_keys( $columns );

		// add jilt_status column before the email_type column, or at the end if email_type isn't found
		$jilt_status_index = array_search( 'email_type', $column_keys, true );
		array_splice( $column_keys, is_numeric( $jilt_status_index ) ? $jilt_status_index : count( $column_keys ), 0, 'wc_jilt_jilt_status' );

		// replace the status column, or put at the beginning if status isn't found
		$status_index = array_search( 'status', $column_keys, true );
		array_splice( $column_keys, is_numeric( $status_index ) ? $status_index : 0, is_numeric( $status_index ) ? 1 : 0, 'wc_jilt_status' );

		$columns['wc_jilt_jilt_status'] = __( 'Jilt Status', 'jilt-for-woocommerce' );
		$columns['wc_jilt_status']      = '';

		$new_columns = array();

		foreach ( $column_keys as $column_key ) {

			if ( isset( $columns[ $column_key ] ) ) {

				$new_columns[ $column_key ] = $columns[ $column_key ];
			}
		}

		return $new_columns;
	}


	/**
	 * Renders the custom email status column.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 *
	 * @param \WC_Email $email the email
	 */
	public function render_email_status_column( \WC_Email $email ) {

		echo '<td class="wc-email-settings-table-wc_jilt_status">';

		if ( $this->is_email_managed( $email->id ) ) {
			echo '<span class="status-jilt-managed tips" data-tip="' . esc_attr__( 'Managed by Jilt', 'jilt-for-woocommerce' ) . '">' . esc_html__( 'Managed by Jilt', 'jilt-for-woocommerce' ) . '</span>';
		} elseif ( $email->is_manual() ) {
			echo '<span class="status-manual tips" data-tip="' . esc_attr__( 'Manually sent', 'woocommerce' ) . '">' . esc_html__( 'Manual', 'woocommerce' ) . '</span>';
		} elseif ( $email->is_enabled() ) {
			echo '<span class="status-enabled tips" data-tip="' . esc_attr__( 'Enabled', 'woocommerce' ) . '">' . esc_html__( 'Yes', 'woocommerce' ) . '</span>';
		} else {
			echo '<span class="status-disabled tips" data-tip="' . esc_attr__( 'Disabled', 'woocommerce' ) . '">-</span>';
		}

		echo '</td>';
	}

	/**
	 * Renders the custom email jilt_status column.
	 *
	 * @internal
	 *
	 * @since 1.6.0
	 *
	 * @param \WC_Email $email the email
	 */
	public function render_email_jilt_status_column( $email ) {

		$td_classes = array( 'wc-email-settings-table-wc_jilt_jilt_status' );

		if ( $this->is_email_managed( $email->id ) ) {
			$td_classes[] = 'jilt-managed';
			$td_classes[] = 'jilt-notification-state--' . $this->get_managed_notification_state( $email->id );
		}

		echo '<td class="' . esc_attr( implode( ' ', $td_classes ) ) . '">';

		if ( $this->is_email_managed( $email->id ) ) {

			echo '<span class="jilt-status">' . esc_html( $this->get_managed_notification_state_formatted( $email->id ) ) . '</span>';

		} else {

			echo '&mdash;';
		}

		echo '</td>';
	}


	/**
	 * Redirects the settings page of a managed email to the Jilt transactional notification for that email.
	 *
	 * @since 1.6.0
	 *
	 * @param \WC_Email $email the email object
	 */
	public function redirect_managed_email_settings_to_jilt( $email ) {

		if ( $this->is_email_managed( $email->id ) && $tn_id = $this->get_transactional_notification_id( $email->id ) ) {

			wp_redirect( wc_jilt()->get_integration()->get_transactional_notification_url( $tn_id ) );
			exit;
		}
	}


	/**
	 * Checks if a given email ID is being managed by Jilt and is active.
	 *
	 * @since 1.6.0
	 *
	 * @param string $email_id woocommerce email ID
	 * @return bool
	 */
	public function is_email_managed( $email_id ) {

		return (bool) $this->get_managed_notification_param( $email_id, 'active' );
	}


	/**
	 * Gets a param from the managed email notification for the given email ID.
	 *
	 * @since 1.6.0
	 *
	 * @param string $email_id woocommerce email ID
	 * @param string $param param name
	 * @return mixed|null
	 */
	public function get_managed_notification_param( $email_id, $param ) {

		return   isset( $this->managed_email_notifications[ $email_id ][ $param ] )
			   ? $this->managed_email_notifications[ $email_id ][ $param ]
			   : null;
	}


	/**
	 * Gets the transactional notification ID for a given notification.
	 *
	 * @since 1.6.0
	 *
	 * @param string $email_id woocommerce email ID
	 * @return int|null
	 */
	public function get_transactional_notification_id( $email_id ) {

		return $this->get_managed_notification_param( $email_id, 'transactional_notification_id' );
	}


	/**
	 * Gets the transactional notification state.
	 *
	 * @since 1.6.0
	 *
	 * @param string $email_id woocommerce email ID
	 * @return string|null
	 */
	public function get_managed_notification_state( $email_id ) {

		return $this->get_managed_notification_param( $email_id, 'state' );
	}


	/**
	 * Gets the transactional notification state formatted for display.
	 *
	 * @since 1.6.0
	 *
	 * @param string $email_id woocommerce email ID
	 * @return string|null
	 */
	public function get_managed_notification_state_formatted( $email_id ) {

		$state        = $this->get_managed_notification_state( $email_id );
		$known_states = array(
			'live'    => _x( 'Live', 'managed email state', 'jilt-for-woocommerce' ),
			'stopped' => _x( 'Stopped', 'managed email state', 'jilt-for-woocommerce' ),
			'draft'   => _x( 'Draft', 'managed email state', 'jilt-for-woocommerce' ),
		);

		return isset( $known_states[ $state ] ) ? $known_states[ $state ] : null;
	}


}
