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
 * Integration admin class. The WC_Jilt_Integration class delegates as much
 * admin related functionality as possible to this class. This strange setup is
 * the result of the tight coupling between integration functionality code and
 * admin settings within WooCommerce.
 *
 * @since 1.1.0
 */
class WC_Jilt_Integration_Admin {


	/** @var WC_Jilt_Integration instance */
	private $integration;

	/** @var bool whether to update shop via API when saving settings */
	private $update_shop_on_save = true;


	/**
	 * Initialize the class
	 *
	 * @since 1.0.0
	 * @see WC_Jilt_Integration::instance()
	 * @param \WC_Jilt_Integration $integration
	 */
	public function __construct( $integration ) {

		$this->integration = $integration;

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_menu', array( $this, 'add_menu_link' ) );
	}


	/**
	 * Initializes the integration admin.
	 *
	 * TODO: after moving away from WC_Integration, the current admin setup makes
	 * little sense and feels quite messy with mixed concerns - we have the admin class setting properties
	 * on the main integration class instance, for example. We want to refactor this rather soon. {IT 2018-02-18}
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 */
	public function init() {

		if ( $this->get_integration()->is_linked() ) {

			// update the shop info in Jilt once per day
			if ( ! wp_next_scheduled( 'wc_jilt_shop_update' ) ) {
				wp_schedule_event( time(), 'daily', 'wc_jilt_shop_update' );
			}

			add_action( 'wc_jilt_shop_update', array( $this->get_integration(), 'update_shop') );
		}

		// it's up to us to save our admin options
		if ( is_admin() ) {

			add_filter( 'woocommerce_settings_api_sanitized_fields_jilt', array( $this, 'sanitize_fields' ) );

			// report connection errors
			add_action( 'admin_notices', array( $this, 'show_connection_notices' ) );

			// whenever WC settings are changed, update data in Jilt app
			add_action( 'woocommerce_settings_saved', array( __CLASS__, 'update_shop' ) );

			add_action( 'admin_footer', array( $this, 'output_deactivation_modal' ) );

			add_filter( 'admin_footer_text', array( $this, 'admin_footer_text' ), 1 );
		}

		// enable the WC REST API and create a key for Jilt if necessary, via the admin action
		add_action( 'admin_action_wc_jilt_enable_wc_rest_api', array( $this, 'enable_wc_rest_api' ) );

		// ensure shop data is updated when shipping settings are changed
		$this->add_shipping_settings_hooks();

		// Load admin form
		$this->init_form_fields();
	}


	/**
	 * Adds the action & filter hooks to update shop data when shipping settings
	 * are changed.
	 *
	 * @since 1.5.0
	 */
	protected function add_shipping_settings_hooks() {

		// update the Jilt shop whenever a shipping zone or method is changed
		add_action( 'woocommerce_shipping_zone_method_status_toggled', array( $this, 'update_shop_shipping' ) );
		add_action( 'woocommerce_shipping_zone_method_deleted',        array( $this, 'update_shop_shipping' ) );
		add_action( 'woocommerce_delete_shipping_zone',                array( $this, 'update_shop_shipping' ) );

		// hook into \WC_Shipping_Zone::get_shipping_methods(), which is called
		// while saving method settings during AJAX. This is the only way we can
		// be sure to catch any changes to shipping method settings within zones
		add_filter( 'woocommerce_shipping_zone_shipping_methods', function( $methods ) {

			// if currently saving a shipping method via AJAX, update the Jilt shop
			if ( doing_action( 'wp_ajax_woocommerce_shipping_zone_methods_save_settings' ) ) {
				$this->update_shop_shipping();
			}

			return $methods;

		} );
	}


	/**
	 * Updates the shop data after shipping options have changed.
	 *
	 * @since 1.5.0
	 */
	public function update_shop_shipping() {

		try {

			$this->get_integration()->update_shop();

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			wc_jilt()->get_logger()->error( 'Could not update the shop shipping data: ' . $exception->getMessage() );
		}
	}


	/**
	 * Outputs the deactivation survey modal HTML.
	 *
	 * @since 1.5.0
	 */
	public function output_deactivation_modal() {

		$screen = get_current_screen();

		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) ) {
			return;
		}

		$options = array(
			'no_need' => array(
				'title'   => esc_html__( 'I no longer need this plugin', 'jilt-for-woocommerce' ),
			),
			'switching' => array(
				'title'   => esc_html__( "I'm switching to a different plugin", 'jilt-for-woocommerce' ),
				'details' => esc_html__( 'Please share which plugin', 'jilt-for-woocommerce' ),
			),
			'not_working' => array(
				'title'   => esc_html__( "I couldn't get the plugin to work", 'jilt-for-woocommerce' ),
				'details' => esc_html__( 'Please share what happened', 'jilt-for-woocommerce' ),
			),
			'temporary' => array(
				'title'   => esc_html__( "It's a temporary deactivation", 'jilt-for-woocommerce' ),
			),
			'other' => array(
				'title'   => esc_html__( 'Other', 'jilt-for-woocommerce' ),
				'details' => esc_html__( 'Please share the reason', 'jilt-for-woocommerce' ),
			),
		);

		?>
		<div id="wc-jilt-deactivation-modal">
			<div class="wc-jilt-deactivation-modal-content">
				<form id="wc-jilt-deactivation-survey" method="post">

					<h1><span class="dashicons dashicons-testimonial"></span> <?php esc_html_e( 'Quick Feedback', 'jilt-for-woocommerce' ); ?></h1>

					<p><?php printf( esc_html__('If you have a moment, please share why you are deactivating %s:', 'jilt-for-woocommerce' ), esc_html( wc_jilt()->get_plugin_name() ) ); ?></p>

					<div class="deactivation-options">
						<?php foreach ( $options as $id => $option ) : ?>
							<p class="deactivation-option">
								<label for="wc-jilt-for-woocommerce-deactivation-option-<?php echo esc_attr( $id ); ?>">
									<input id="wc-jilt-for-woocommerce-deactivation-option-<?php echo esc_attr( $id ); ?>" type="radio" name="code" value="<?php echo esc_attr( $id ); ?>" />
									<span class="option-title"><?php echo esc_html( $option['title'] ); ?></span>
								</label>
								<?php if ( ! empty( $option['details'] ) ) : ?>
									<span class="option-details" style="display:none;"><input type="text" class="large-text" placeholder="<?php echo esc_attr( $option['details'] ); ?>" /></span>
								<?php endif; ?>
							</p>
						<?php endforeach; ?>
					</div>

					<div class="modal-footer">
						<button type="submit" class="js-submit-and-deactivate button button-primary button-large"><?php printf( esc_html__('Submit %s Deactivate', 'jilt-for-woocommerce' ), '&amp;' ); ?></button>
						<a href="#" class="js-skip-and-deactivate"><?php printf( esc_html__('Skip %s Deactivate', 'jilt-for-woocommerce' ), '&amp;' ); ?></a>
					</div>
				</form>
			</div>
		</div>
		<?php
	}


	/**
	 * Adds the Jilt top level menu link below Products.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 */
	public function add_menu_link() {

		$this->page = add_menu_page(
			__( 'Jilt', 'jilt-for-woocommerce' ),
			__( 'Jilt', 'jilt-for-woocommerce' ),
			'manage_woocommerce',
			'wc-jilt',
			array( $this, 'render_menu_page' ),
			null,
			'56'
		);
	}


	/**
	 * Renders the pages for the Jilt top-level menu.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 */
	public function render_menu_page() {
		global $current_tab, $current_section;

		// permissions check
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return;
		}

		$tabs            = array( 'settings' => __( 'Settings', 'jilt-for-woocommerce' ) );
		$current_tab     = empty( $_GET['tab'] ) ? 'settings' : sanitize_title( $_GET['tab'] );
		$current_section = empty( $_REQUEST['section'] ) ? '' : sanitize_title( $_REQUEST['section'] );

		// save settings
		if ( ! empty( $_POST ) && 'settings' === $current_tab ) {

			check_admin_referer( 'save-jilt-settings' );

			$this->process_admin_options();

			if ( $this->update_shop_on_save ) {
				$this->update_shop();
			}

			wc_jilt()->get_message_handler()->add_message( __( 'Your settings have been saved.', 'jilt-for-woocommerce' ) );
		}

		include( 'views/html-admin-screen.php' );
	}


	/**
	 * Sanitize Jilt settings. Removes faux settings, such as `links`.
	 *
	 * @since 1.0.0
	 * @param array $sanitized_fields
	 * @return array
	 */
	public function sanitize_fields( $sanitized_fields ) {

		unset( $sanitized_fields['links'], $sanitized_fields['status'] );

		return $sanitized_fields;
	}


	/**
	 * Initializes form fields in the format required by WC_Settings_API
	 *
	 * @since 1.0.0
	 */
	public function init_form_fields() {

		// settings that are available regardless of connection status
		$form_fields = array(
			'status'                     => $this->get_connection_status_form_field(),
			'post_checkout_registration' => $this->get_post_checkout_registration_form_field(),
			'log_threshold'              => $this->get_log_threshold_form_field(),
		);

		if ( $this->get_integration()->is_jilt_connected() ) {

			if ( 'secret_key' === $this->get_integration()->get_auth_method() ) {
				$form_fields = array( 'secret_key' => array(
					'title'   => __( 'Secret Key', 'jilt-for-woocommerce' ),
					'type'    => 'password',
					'custom_attributes' => array( 'autofill' => 'off' ),
					/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
					'description' => sprintf( __( 'Get this from your %1$sJilt account%2$s', 'jilt-for-woocommerce' ), '<a href="' . esc_url( 'https://' . $this->get_plugin()->get_app_hostname() . '/shops/new/woocommerce' ) . '" target="_blank">', '</a>' ),
				) ) + $form_fields;
			}

			$form_fields += array(
				'links' => array(
					'title'       => '',
					'type'        => 'title',
					'description' => $this->get_links_form_field_description(),
				),
			);

		}

		$this->get_integration()->form_fields = $form_fields;
	}


	/**
	 * Update Jilt public key and shop ID when updating secret key
	 *
	 * @since 1.0.0
	 * @see WC_Settings_API::process_admin_options
	 * @return bool
	 */
	public function process_admin_options() {

		$integration = $this->get_integration();

		// connect to Jilt
		if ( isset( $_POST['woocommerce_jilt_connect'] ) ) {

			wp_safe_redirect( wc_jilt()->get_connect_url() );
			exit;
		}

		// when updating settings, make sure we have the new value so we log any
		// API requests that might occur
		if ( isset( $_POST['woocommerce_jilt_log_threshold'] ) ) {
			$this->get_plugin()->get_logger()->set_threshold( (int) $_POST['woocommerce_jilt_log_threshold'] );
		}

		// disconnect from Jilt - either when pressing the disconnect button or if secret key has been removed
		if ( isset( $_POST['woocommerce_jilt_disconnect'] ) || ( isset( $_POST['woocommerce_jilt_secret_key'] ) && empty( $_POST['woocommerce_jilt_secret_key'] ) ) ) {

			$integration->clear_connection_data();

			$this->get_plugin()->get_message_handler()->add_message( __( 'Your shop is now disconnected from Jilt.', 'jilt-for-woocommerce' ) );

			wp_redirect( $this->get_plugin()->get_settings_url() );
			exit;
		}

		// TODO: remove the following block when dropping support for secret key authentication for good {IT 2017-12-13}
		if ( ! isset( $_POST['woocommerce_jilt_disconnect'] ) && ! empty( $_POST['woocommerce_jilt_secret_key'] ) ) {

			$old_secret_key = $this->get_integration()->get_secret_key();
			$new_secret_key = $_POST['woocommerce_jilt_secret_key'];

			// secret key has been changed, so unlink (uninstall) remote shop
			if ( $new_secret_key && $new_secret_key !== $old_secret_key && $integration->is_linked() ) {
				$this->get_integration()->unlink_shop();
			}

			if ( $new_secret_key && ( $new_secret_key !== $old_secret_key || ! $integration->has_connected() || ! $integration->is_linked() ) ) {
				$this->connect_to_jilt( $new_secret_key );

				// avoid an extra useless REST API request
				$this->update_shop_on_save = false;
			}

			// the links that we added to the settings block could be incorrect now
			if ( $new_secret_key !== $old_secret_key ) {
				add_action( 'admin_footer',  array( $this, 'update_jilt_links_js' ) );
			}
		}

		return $integration->process_admin_options();
	}


	/**
	 * Update the remote shop resource with the latest data. Render an error
	 * message if there's a failure to communicate.
	 *
	 * @since 1.2.0
	 *
	 * @param string $consumer_key a new WC REST API consumer key
	 */
	public static function update_shop( $consumer_key = null ) {

		// update shop data in Jilt (especially plugin version)
		try {

			wc_jilt()->get_integration()->update_shop( $consumer_key );

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			$solution_message = null;

			if ( $exception->getCode() == 404 ) {
				/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
				$solution_message = sprintf( __( 'Shop not found, please try re-connecting to Jilt or %1$sget in touch with Jilt Support%2$s to resolve this issue.', 'jilt-for-woocommerce' ),
					'<a target="_blank" href="' . esc_url( wc_jilt()->get_support_url( array( 'message' => $exception->getMessage() ) ) ) . '">',
					'</a>'
				);
			} elseif ( $exception->getCode() == 401 ) {
				/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
				$solution_message = sprintf( __( 'Shop not authorized, please try re-connecting to Jilt or %1$sget in touch with Jilt Support%2$s to resolve this issue.', 'jilt-for-woocommerce' ),
					'<a target="_blank" href="' . esc_url( wc_jilt()->get_support_url( array( 'message' => $exception->getMessage() ) ) ) . '">',
					'</a>'
				);
			}

			self::add_api_error_notice( array( 'error_message' => $exception->getMessage(), 'solution_message' => $solution_message ) );
		}
	}


	/**
	 * Returns an HTML fragment containing the Jilt external campaigns/dashboard
	 * links for the plugin settings page
	 *
	 * @since 1.0.3
	 * @return string HTML fragment
	 */
	private function get_links_form_field_description() {

		/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag*/
		$links = sprintf( esc_html__( '%1$sGet Support!%2$s', 'jilt-for-woocommerce' ),
			'<a target="_blank" href="' . esc_url( $this->get_plugin()->get_support_url() ) . '">', '</a>'
		);

		if ( $this->get_integration()->is_linked() ) {
			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$links = sprintf( esc_html__( '%1$sGo to Jilt dashboard%2$s', 'jilt-for-woocommerce' ),
				'<a target="_blank" href="' . esc_url( $this->get_integration()->get_jilt_app_url() ) . '">', '</a>'
			) . ' | ' . $links;
		}

		return $links;
	}


	/**
	 * Render javascript to update the Jilt campaign/statistics external links
	 * shown on the Jilt plugin settings page, via JavaScript. This is done
	 * when the shop's connection to Jilt may have changed, since the links are
	 * first written out before any Jilt connection/disconnection is handled.
	 *
	 * @since 1.0.3
	 */
	public function update_jilt_links_js() {
		?>
		<script>
			jQuery( '#woocommerce_jilt_links + p' ).html( '<?php echo $this->get_links_form_field_description(); ?>' );
			jQuery( '.woocommerce_jilt_status' ).html( '<?php echo $this->get_connection_status(); ?>' );
		</script>
		<?php
	}


	/**
	 * Changes the admin footer text on Jilt admin pages.
	 *
	 * @internal
	 *
	 * @since 1.5.0
	 *
	 * @param string $footer_text
	 * @return string
	 */
	public function admin_footer_text( $footer_text ) {

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return $footer_text;
		}

		$screen = get_current_screen();

		// Check to make sure we're on a WooCommerce admin page.
		if ( ! empty( $screen ) && 'toplevel_page_wc-jilt' === $screen->id ) {

			// adjust the footer text
			if ( ! get_option( 'wc_jilt_admin_footer_text_rated' ) ) {

				$footer_text = sprintf(
					/* translators: %1$s - Jilt, %2$s - five stars */
					esc_html__( 'If you like %1$s please leave us a %2$s rating. A huge thanks in advance!', 'jilt-for-woocommerce' ),
					sprintf( '<strong>%s</strong>', esc_html__( 'Jilt', 'jilt-for-woocommerce' ) ),
					'<a href="' . esc_url( wc_jilt()->get_product_page_url() ) . 'reviews?rate=5#new-post" target="_blank" class="wc-jilt-rating-link" data-rated="' . esc_attr__( 'Thanks :)', 'jilt-for-woocommerce' ) . '">&#9733;&#9733;&#9733;&#9733;&#9733;</a>'
				);

				wc_enqueue_js( "
					jQuery( 'a.wc-jilt-rating-link' ).click( function() {
						jQuery.post( '" . WC()->ajax_url() . "', { action: 'woocommerce_jilt_rated' } );
						jQuery( this ).parent().text( jQuery( this ).data( 'rated' ) );
					});
				" );

			} else {
				$footer_text = esc_html__( 'Thank you for recovering sales with Jilt.', 'jilt-for-woocommerce' );
			}
		}

		return $footer_text;
	}


	/**
	 * We already show connection error notices when the plugin settings save
	 * post is happening; this method makes those notices more persistent by
	 * showing a connection notice on a regular page load if there's an issue
	 * with the Jilt connection.
	 *
	 * @since 1.0.3
	 */
	public function show_connection_notices() {

		if ( $this->get_integration()->is_duplicate_site() ) {
			if ( ! ( $this->get_plugin()->is_plugin_settings() && isset( $_POST['save'] ) ) ) {
				// don't render the message if we're saving on the plugin settings page

				/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - <a> tag, %4$s </a> tag */
				$message = sprintf( __( 'It looks like this site has moved or is a duplicate site. %1$sJilt for WooCommerce%2$s has been disabled to prevent sending recovery emails from a staging or test environment. For more information please %3$sget in touch%4$s.', 'jilt-for-woocommerce' ),
					'<strong>', '</strong>',
					'<a target="_blank" href="' . $this->get_plugin()->get_support_url() . '">', '</a>'
				);
				$this->get_plugin()->get_admin_notice_handler()->add_admin_notice(
					$message,
					'duplicate-site-unlink-notice',
					array( 'notice_class' => 'error' )
				);
			}

			return;
		}

		// if we're on the Jilt settings page and we're not currently saving the settings (e.g. regular page load), and the plugin is configured
		if ( ! $this->get_plugin()->is_plugin_settings() || isset( $_POST['save'] ) || ! $this->get_integration()->is_configured() ) {
			return;
		}

		$message = null;

		// call to action based on error state
		if ( ! $this->get_integration()->has_connected() ) {

			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$solution_message = sprintf( __( 'Please try re-connecting to Jilt or %1$sget in touch with Jilt Support%2$s to help resolve this issue.', 'jilt-for-woocommerce' ),
				'<a target="_blank" href="' . esc_url( $this->get_plugin()->get_support_url() ) . '">',
				'</a>'
			);
			self::add_api_error_notice( array( 'solution_message' => $solution_message  ));

		} elseif ( ! $this->get_integration()->is_linked() ) {
			self::add_api_error_notice( array( 'support_message' => "I'm having an issue linking my shop to Jilt" ) );
		}
	}


	/**
	 * If a $secret_key is provided, attempt to connect to the Jilt API to
	 * retrieve the corresponding Public Key, and link the shop to Jilt
	 *
	 * TODO: remove this when dropping secret key auth support {IT 2018-01-22}
	 *
	 * @since 1.0.0
	 *
	 * @param string $secret_key the secret key to use, or empty string
	 * @return true if this shop is successfully connected to Jilt, false otherwise
	 */
	private function connect_to_jilt( $secret_key ) {

		try {

			// remove the previous public key and linked shop id, if any, when the secret key is changed
			$this->get_integration()->clear_connection_data();
			$this->get_integration()->set_secret_key( $secret_key );
			$this->get_integration()->refresh_public_key();

			if ( is_int( $this->get_integration()->link_shop() ) ) {
				// dismiss the "welcome" message now that we've successfully linked
				$this->get_plugin()->get_admin_notice_handler()->dismiss_notice( 'get-started-notice' );
				$this->get_plugin()->get_admin_notice_handler()->add_admin_notice(
					__( 'Shop is now linked to Jilt!', 'jilt-for-woocommerce' ),
					'shop-linked'
				);
				return true;
			} else {
				self::add_api_error_notice( array( 'error_message' => 'Unable to link shop' ) );
			}

			return false;

		} catch ( Framework\SV_WC_API_Exception $exception ) {

			$solution_message = null;

			// call to action based on error message
			if ( Framework\SV_WC_Helper::str_exists( $exception->getMessage(), 'Invalid API Key provided' ) ) {

				/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
				$solution_message = sprintf( __( 'Please try re-connecting to Jilt or %1$sget in touch with Jilt Support%2$s to resolve this issue.', 'jilt-for-woocommerce' ),
					'<a target="_blank" href="' . esc_url( $this->get_plugin()->get_support_url( array( 'message' => $exception->getMessage() ) ) ) . '">',
					'</a>'
				);
			}

			self::add_api_error_notice( array( 'error_message' => $exception->getMessage(), 'solution_message' => $solution_message ) );

			$this->get_plugin()->get_logger()->error( "Error communicating with Jilt: {$exception->getMessage()}" );

			return false;
		}
	}


	/**
	 * Report an API error message in an admin notice with a link to the Jilt
	 * support page. Optionally log error.
	 *
	 * @since 1.1.0
	 * @param array $params Associative array of params:
	 *   'error_message': optional error message
	 *   'solution_message': optional solution message (defaults to "get in touch with support")
	 *   'support_message': optional message to include in a support request
	 *     (defaults to error_message)
	 *
	 */
	private static function add_api_error_notice( $params ) {

		if ( ! isset( $params['error_message'] ) ) {
			$params['error_message'] = null;
		}

		// this will be pre-populated in any support request form. Defaults to
		// the error message, if not set
		if ( empty( $params['support_message'] ) ) {
			$params['support_message'] = $params['error_message'];
		}

		if ( empty( $params['solution_message'] ) ) {
			// generic solution message: get in touch with support
			/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
			$params['solution_message'] = sprintf(__( 'Please %1$sget in touch with Jilt Support%2$s to resolve this issue.', 'jilt-for-woocommerce' ),
				'<a target="_blank" href="' . esc_url( wc_jilt()->get_support_url( array( 'message' => $params['support_message'] ) ) ) . '">',
				'</a>'
			);
		}

		if ( ! empty( $params['error_message'] ) ) {
			// add a full stop
			$params['error_message'] .= '.';
		}

		/* translators: Placeholders: %1$s - <strong> tag, %2$s - </strong> tag, %3$s - error message, %4$s - solution message */
		$notice = sprintf( __( '%1$sError communicating with Jilt%2$s: %3$s %4$s', 'jilt-for-woocommerce' ),
			'<strong>',
			'</strong>',
			$params['error_message'],
			$params['solution_message']
		);

		wc_jilt()->get_admin_notice_handler()->add_admin_notice(
			$notice,
			'api-error',
			array( 'notice_class' => 'error' )
		);
	}


	/**
	 * Enables the WC REST API and creates a key for Jilt if necessary, via the admin action.
	 *
	 * @since 1.5.0
	 */
	public function enable_wc_rest_api() {

		// nonce check
		check_admin_referer( 'wc_jilt_enable_wc_rest_api' );

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( esc_html__( "Sorry, you don't have permission to do that.", 'jilt-for-woocommerce' ) );
		}

		try {

			// enable the legacy WC REST API if needed (WC < 3.4)
			if ( $this->get_plugin()->get_wc_rest_api_handler_instance()->legacy_wc_rest_api_needed() ) {

				$this->get_plugin()->get_wc_rest_api_handler_instance()->enable_legacy_wc_rest_api();
			}

			// fix existing key permissions
			if ( false === $this->get_plugin()->get_wc_rest_api_handler_instance()->key_permissions_are_correct() ) {

				$this->get_plugin()->get_wc_rest_api_handler_instance()->correct_key_permissions();
			}

			// for all other key issues, generate a new key
			if ( ! $this->get_plugin()->get_wc_rest_api_handler_instance()->has_valid_key() ) {

				$key = $this->get_plugin()->get_wc_rest_api_handler_instance()->refresh_key();
				$this->get_integration()->update_shop( $key->consumer_key );
			}

			$this->get_plugin()->get_message_handler()->add_message( __( 'Success! The WooCommerce REST API has been enabled for Jilt.', 'jilt-for-woocommerce' ) );

		} catch ( Framework\SV_WC_Plugin_Exception $exception ) {

			$this->get_plugin()->get_message_handler()->add_error( __( 'Oops! Something went wrong. Please try re-connecting to Jilt.', 'jilt-for-woocommerce' ) );
		}

		wp_safe_redirect( $this->get_plugin()->get_settings_url() );
		exit;
	}


	/** Helper methods ******************************************************/


	/**
	 * Get the form field options for the log threshold setting
	 *
	 * @since 1.4.2
	 *
	 * @return array
	 */
	private function get_log_threshold_form_field() {

		/* translators: Placeholders: %1$s - <a> tag, %2$s - </a> tag */
		$description = sprintf(
			__( 'Save detailed error messages and API requests/responses to the %1$sdebug log%2$s', 'jilt-for-woocommerce' ),
			'<a href="' . esc_url( WC_Jilt_Logger::get_log_file_url( $this->get_plugin()->get_id() ) ) . '">',
			'</a>'
		);

		return array(
			'title'   => __( 'Logging', 'jilt-for-woocommerce' ),
			'type'    => 'select',
			'description'    => $description,
			'default' => WC_Jilt_Logger::OFF,
			'options' => array(
				WC_Jilt_Logger::OFF       => _x( 'Off',   'Logging disabled', 'jilt-for-woocommerce' ),
				WC_Jilt_Logger::DEBUG     => _x( 'Debug', 'Log level debug',  'jilt-for-woocommerce' ),
				WC_Jilt_Logger::INFO      => __( 'Info',  'Log level info',   'jilt-for-woocommerce' ),
				WC_Jilt_Logger::WARNING   => __( 'Warning',  'Log level warn',   'jilt-for-woocommerce' ),
				WC_Jilt_Logger::ERROR     => __( 'Error', 'Log level error',  'jilt-for-woocommerce' ),
				WC_Jilt_Logger::EMERGENCY => __( 'Emergency', 'Log level emergency',  'jilt-for-woocommerce' ),
			),
		);
	}


	/**
	 * Get the form field options for the post checkout registration option
	 *
	 * @since 1.4.2
	 *
	 * @return array
	 */
	private function get_post_checkout_registration_form_field() {

		return array(
			'title'    => __( 'Post-checkout registration', 'jilt-for-woocommerce' ),
			'label'    => __( 'Enable customer registration on the "Order Received" page.', 'jilt-for-woocommerce' ),
			'desc_tip' => __( 'Uses all customer details from the order for the new account for one-click registration.', 'jilt-for-woocommerce' ),
			'type'     => 'checkbox',
			'default'  => 'no',
		);
	}


	/**
	 * Get the form field options for the connection status action
	 *
	 * @since 1.4.2
	 *
	 * @return array
	 */
	private function get_connection_status_form_field() {

		return array(
			'title'       => __( 'Connection Status', 'jilt-for-woocommerce' ),
			'type'        => 'jilt_status',
			'description' => $this->get_connection_status(),
		);
	}


	/**
	 * Get the Jilt integration instance
	 *
	 * @since 1.2.0
	 * @return WC_Jilt_Integration
	 */
	private function get_integration() {
		return $this->integration;
	}


	/**
	 * Get the plugin
	 *
	 * @since 1.2.0
	 * @return \WC_Jilt
	 */
	private function get_plugin() {
		return wc_jilt();
	}


	/**
	 * Returns the Jilt Connection Status "setting" HTML fragment
	 *
	 * @see WC_Jilt_Integration::generate_jilt_status_html()
	 * @since 1.2.0
	 * @param  mixed $key
	 * @param  mixed $data
	 * @return string HTML fragment
	 */
	public function generate_jilt_status_html( $key, $data ) {

		ob_start();
		?><tr valign="top">
			<th scope="row" class="titledesc">
				<label><?php echo wp_kses_post( $data['title'] ); ?></label>
			</th>
			<td>
				<p class="woocommerce_jilt_status"><?php echo $data['description']; ?></p>
			</td>
		</tr><?php

		return ob_get_clean();
	}


	/**
	 * Returns an HTML fragment containing the Jilt connection status at a high
	 * level: Connected or Not Connected. A more nuanced connection status is
	 * rendered in the WC System Status page
	 *
	 * @since 1.2.0
	 *
	 * @return string HTML fragment
	 */
	private function get_connection_status() {

		$fragment = '';

		if ( $this->get_integration()->is_jilt_connected() ) {

			if ( 'secret_key' === $this->get_integration()->get_auth_method() ) {

				$tip = __( 'Jilt is connected, but secret key authentication is deprecated. Please reconnect to Jilt.', 'jilt-for-woocommerce' );
				$fragment .= '<mark class="warning help_tip" data-tip="' . esc_attr( $tip ) . '" style="color: #ffb900; background-color: transparent; cursor: help;">&#9888;</mark> ';
				$fragment .= '<input type="submit" class="button button-primary" name="woocommerce_jilt_connect" value="' . esc_attr__( 'Re-connect to Jilt', 'jilt-for-woocommerce' ) . '">';

			} elseif ( ! wc_jilt()->get_wc_rest_api_handler_instance()->is_configured() ) {

				$tip = sprintf(
					/* translators: Placeholders: %1$s - connection error reason */
					__( 'Jilt is connected, but WooCommerce REST API is not available: %1$s', 'jilt-for-woocommerce' ),
					wc_jilt()->get_wc_rest_api_handler_instance()->get_api_configuration_error_short()
				);
				$fragment .= '<mark class="warning help_tip" data-tip="' . $tip . '" style="color: #ffb900; background-color: transparent; cursor: help;">&#9888;</mark> ';

			} else {

				$tip = __( 'Jilt is connected!', 'jilt-for-woocommerce' );
				$fragment .= '<mark class="yes help_tip" data-tip="' . esc_attr( $tip ) . '" style="color: #7ad03a; background-color: transparent; cursor: help;">&#10004;</mark>';
			}

			$fragment .= '<input type="submit" class="button" name="woocommerce_jilt_disconnect" id="woocommerce_jilt_disconnect" value="' . esc_attr__( 'Disconnect from Jilt', 'jilt-for-woocommerce' ) . '">';

		} else {

			if ( ! $this->get_integration()->has_connected() || ! $this->get_integration()->is_linked() ) {
				$tip = __( 'Please ensure the plugin has been successfully connected to Jilt.', 'jilt-for-woocommerce' );
			} elseif ( $this->get_integration()->is_duplicate_site() ) {
				$tip = __( 'It looks like this site has moved or is a duplicate site.', 'jilt-for-woocommerce' );
			}

			$tip = isset( $tip ) ? 'data-tip="' . esc_attr( $tip ) . '"' : '';

			$fragment .= '<mark class="error help_tip" ' . $tip . ' style="color: #a00; background-color: transparent; cursor: help;">&#10005;</mark>';

			$label = $this->get_integration()->has_connected() ? __( 'Re-connect to Jilt', 'jilt-for-woocommerce' ) : __( 'Connect to Jilt', 'jilt-for-woocommerce' );

			$fragment .= '<input type="submit" class="button button-primary" name="woocommerce_jilt_connect" id="woocommerce_jilt_connect" value="' . esc_attr( $label ) . '">';
		}

		return $fragment;
	}


}
