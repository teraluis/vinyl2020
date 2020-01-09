<?php

/**
 * Plugin Name: WooCommerce Monetico Gateway
 * Plugin URI: http://www.absoluteweb.net/prestations/wordpress-woocommerce-extensions-traductions/woocommerce-monetico/
 * Description: Passerelle de paiement Monetico pour WooCommerce.
 * Version: 1.2
 * Author: Nicolas Maillard
 * Author URI: http://www.absoluteweb.net/
 * License: Copyright ABSOLUTE Web
 *
 * WC requires at least: 2.0
 * WC tested up to: 5
 *
 *	Intellectual Property rights, and copyright, reserved by Nicolas Maillard, ABSOLUTE Web as allowed by law incude,
 *	but are not limited to, the working concept, function, and behavior of this plugin,
 *	the logical code structure and expression as written.
 *
 *
 * @package     WooCommerce Monetico Gateway, WooCommerce API Manager
 * @author      Nicolas Maillard, ABSOLUTE Web
 * @category    Plugin
 * @copyright   Copyright (c) 2000-2018, Nicolas Maillard ABSOLUTE Web
 * @license     http://www.gnu.org/licenses/gpl-3.0.html GNU General Public License v3.0
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Displays an inactive message if the API License Key has not yet been activated
 */
if ( get_option( 'monetico_activated' ) != 'Activated' ) {
    add_action( 'admin_notices', 'MONETICO::am_monetico_inactive_notice' );
}

class MONETICO {

	/**
	 * Self Upgrade Values
	 */
	// Base URL to the remote upgrade API Manager server. If not set then the Author URI is used.
	public $upgrade_url = 'http://www.absoluteweb.net/';

	/**
	 * @var string
	 */
	public $version = '1.2';

	/**
	 * @var string
	 * This version is saved after an upgrade to compare this db version to $version
	 */
	public $monetico_version_name = 'plugin_monetico_version';

	/**
	 * @var string
	 */
	public $plugin_url;

	/**
	 * @var string
	 * used to defined localization for translation, but a string literal is preferred
	 *
	 * https://github.com/tommcfarlin/WordPress-Plugin-Boilerplate/issues/59
	 * http://markjaquith.wordpress.com/2011/10/06/translating-wordpress-plugins-and-themes-dont-get-clever/
	 * http://ottopress.com/2012/internationalization-youre-probably-doing-it-wrong/
	 */
	public $text_domain = 'monetico';

	/**
	 * Data defaults
	 * @var mixed
	 */
	private $monetico_software_product_id;

	public $monetico_data_key;
	public $monetico_api_key;
	public $monetico_activation_email;
	public $monetico_product_id_key;
	public $monetico_instance_key;
	public $monetico_deactivate_checkbox_key;
	public $monetico_activated_key;

	public $monetico_deactivate_checkbox;
	public $monetico_activation_tab_key;
	public $monetico_deactivation_tab_key;
	public $monetico_settings_menu_title;
	public $monetico_settings_title;
	public $monetico_menu_tab_activation_title;
	public $monetico_menu_tab_deactivation_title;

	public $monetico_options;
	public $monetico_plugin_name;
	public $monetico_product_id;
	public $monetico_renew_license_url;
	public $monetico_instance_id;
	public $monetico_domain;
	public $monetico_software_version;
	public $monetico_plugin_or_theme;

	public $monetico_update_version;

	public $monetico_update_check = 'am_monetico_plugin_update_check';

	/**
	 * Used to send any extra information.
	 * @var mixed array, object, string, etc.
	 */
	public $monetico_extra;

    /**
     * @var The single instance of the class
     */
    protected static $_instance = null;

    public static function instance() {

        if ( is_null( self::$_instance ) ) {
        	self::$_instance = new self();
        }

        return self::$_instance;
    }

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.2
	 */
	private function __clone() {}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.2
	 */
	private function __wakeup() {}

	public function __construct() {

		// Run the activation function
		register_activation_hook( __FILE__, array( $this, 'activation' ) );

		// Ready for translation
		load_plugin_textdomain( $this->text_domain, false, dirname( untrailingslashit( plugin_basename( __FILE__ ) ) ) . '/lang' );

		if ( is_admin() ) {

			// Check for external connection blocking
			add_action( 'admin_notices', array( $this, 'check_external_blocking' ) );

			/**
			 * Software Product ID is the product title string
			 * This value must be unique, and it must match the API tab for the product in WooCommerce
			 */
			$this->monetico_software_product_id = 'WooCommerce Monetico Gateway';

			/**
			 * Set all data defaults here
			 */
			$this->monetico_data_key 				= 'monetico';
			$this->monetico_api_key 					= 'api_key';
			$this->monetico_activation_email 		= 'activation_email';
			$this->monetico_product_id_key 			= 'monetico_product_id';
			$this->monetico_instance_key 			= 'monetico_instance';
			$this->monetico_deactivate_checkbox_key 	= 'monetico_deactivate_checkbox';
			$this->monetico_activated_key 			= 'monetico_activated';

			/**
			 * Set all admin menu data
			 */
			$this->monetico_deactivate_checkbox 			= 'am_deactivate_monetico_checkbox';
			$this->monetico_activation_tab_key 			= 'monetico_dashboard';
			$this->monetico_deactivation_tab_key 		= 'monetico_deactivation';
			$this->monetico_settings_menu_title 			= 'Licence Passerelle Monetico';
			$this->monetico_settings_title 				= 'Licence Passerelle Monetico';
			$this->monetico_menu_tab_activation_title 	= __( 'License Activation', 'monetico' );
			$this->monetico_menu_tab_deactivation_title 	= __( 'License Deactivation', 'monetico' );

			/**
			 * Set all software update data here
			 */
			$this->monetico_options 				= get_option( $this->monetico_data_key );
			$this->monetico_plugin_name 			= untrailingslashit( plugin_basename( __FILE__ ) ); // same as plugin slug. if a theme use a theme name like 'twentyeleven'
			$this->monetico_product_id 			= get_option( $this->monetico_product_id_key ); // Software Title
			$this->monetico_renew_license_url 	= 'http://www.absoluteweb.net/mon-compte'; // URL to renew a license. Trailing slash in the upgrade_url is required.
			$this->monetico_instance_id 			= get_option( $this->monetico_instance_key ); // Instance ID (unique to each blog activation)
			/**
			 * Some web hosts have security policies that block the : (colon) and // (slashes) in http://,
			 * so only the host portion of the URL can be sent. For example the host portion might be
			 * www.example.com or example.com. http://www.example.com includes the scheme http,
			 * and the host www.example.com.
			 * Sending only the host also eliminates issues when a client site changes from http to https,
			 * but their activation still uses the original scheme.
			 * To send only the host, use a line like the one below:
			 *
			 * $this->monetico_domain = str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
			 */
			$this->monetico_domain 				= str_ireplace( array( 'http://', 'https://' ), '', home_url() ); // blog domain name
			$this->monetico_software_version 	= $this->version; // The software version
			$this->monetico_plugin_or_theme 		= 'plugin'; // 'theme' or 'plugin'

			// Performs activations and deactivations of API License Keys
			require_once( plugin_dir_path( __FILE__ ) . 'am/classes/class-wc-key-api.php' );

			// Checks for software updatess
			require_once( plugin_dir_path( __FILE__ ) . 'am/classes/class-wc-plugin-update.php' );

			// Admin menu with the license key and license email form
			require_once( plugin_dir_path( __FILE__ ) . 'am/admin/class-wc-api-manager-menu.php' );

			$options = get_option( $this->monetico_data_key );

			/**
			 * Check for software updates
			 */
			if ( ! empty( $options ) && $options !== false ) {

				$this->update_check(
					$this->upgrade_url,
					$this->monetico_plugin_name,
					$this->monetico_product_id,
					$this->monetico_options[$this->monetico_api_key],
					$this->monetico_options[$this->monetico_activation_email],
					$this->monetico_renew_license_url,
					$this->monetico_instance_id,
					$this->monetico_domain,
					$this->monetico_software_version,
					$this->monetico_plugin_or_theme,
					$this->text_domain
					);

			}

		}

		/**
		 * Deletes all data if plugin deactivated
		 */
		register_deactivation_hook( __FILE__, array( $this, 'uninstall' ) );

	}

	/** Load Shared Classes as on-demand Instances **********************************************/

	/**
	 * API Key Class.
	 *
	 * @return MONETICO_Key
	 */
	public function key() {
		return MONETICO_Key::instance();
	}

	/**
	 * Update Check Class.
	 *
	 * @return MONETICO_Update_API_Check
	 */
	public function update_check( $upgrade_url, $plugin_name, $product_id, $api_key, $activation_email, $renew_license_url, $instance, $domain, $software_version, $plugin_or_theme, $text_domain, $extra = '' ) {

		return MONETICO_Update_API_Check::instance( $upgrade_url, $plugin_name, $product_id, $api_key, $activation_email, $renew_license_url, $instance, $domain, $software_version, $plugin_or_theme, $text_domain, $extra );
	}

	public function plugin_url() {
		if ( isset( $this->plugin_url ) ) {
			return $this->plugin_url;
		}

		return $this->plugin_url = plugins_url( '/', __FILE__ );
	}

	/**
	 * Generate the default data arrays
	 */
	public function activation() {
		global $wpdb;

		$global_options = array(
			$this->monetico_api_key 				=> '',
			$this->monetico_activation_email 	=> '',
					);

		update_option( $this->monetico_data_key, $global_options );

		require_once( plugin_dir_path( __FILE__ ) . 'am/classes/class-wc-api-manager-passwords.php' );

		$monetico_password_management = new MONETICO_Password_Management();

		// Generate a unique installation $instance id
		$instance = $monetico_password_management->generate_password( 12, false );

		$single_options = array(
			$this->monetico_product_id_key 			=> $this->monetico_software_product_id,
			$this->monetico_instance_key 			=> $instance,
			$this->monetico_deactivate_checkbox_key 	=> 'on',
			$this->monetico_activated_key 			=> 'Deactivated',
			);

		foreach ( $single_options as $key => $value ) {
			update_option( $key, $value );
		}

		$curr_ver = get_option( $this->monetico_version_name );

		// checks if the current plugin version is lower than the version being installed
		if ( version_compare( $this->version, $curr_ver, '>' ) ) {
			// update the version
			update_option( $this->monetico_version_name, $this->version );
		}

	}

	/**
	 * Deletes all data if plugin deactivated
	 * @return void
	 */
	public function uninstall() {
		global $wpdb, $blog_id;

		$this->license_key_deactivation();

		// Remove options
		if ( is_multisite() ) {

			switch_to_blog( $blog_id );

			foreach ( array(
					$this->monetico_data_key,
					$this->monetico_product_id_key,
					$this->monetico_instance_key,
					$this->monetico_deactivate_checkbox_key,
					$this->monetico_activated_key,
					) as $option) {

					delete_option( $option );

					}

			restore_current_blog();

		} else {

			foreach ( array(
					$this->monetico_data_key,
					$this->monetico_product_id_key,
					$this->monetico_instance_key,
					$this->monetico_deactivate_checkbox_key,
					$this->monetico_activated_key
					) as $option) {

					delete_option( $option );

					}

		}

	}

	/**
	 * Deactivates the license on the API server
	 * @return void
	 */
	public function license_key_deactivation() {

		$activation_status = get_option( $this->monetico_activated_key );

		$api_email = $this->monetico_options[$this->monetico_activation_email];
		$api_key = $this->monetico_options[$this->monetico_api_key];

		$args = array(
			'email' => $api_email,
			'licence_key' => $api_key,
			);

		if ( $activation_status == 'Activated' && $api_key != '' && $api_email != '' ) {
			$this->key()->deactivate( $args ); // reset license key activation
		}
	}

    /**
     * Displays an inactive notice when the software is inactive.
     */
	public static function am_monetico_inactive_notice() { ?>
		<?php if ( ! current_user_can( 'manage_options' ) ) return; ?>
		<?php if ( isset( $_GET['page'] ) && 'monetico_dashboard' == $_GET['page'] ) return; ?>
		<div id="message" class="error">
			<p><?php printf( __( 'The Monetico Gateway API License Key has not been activated, so the plugin is inactive! %sClick here%s to activate the license key and the plugin.', 'monetico' ), '<a href="' . esc_url( admin_url( 'options-general.php?page=monetico_dashboard' ) ) . '">', '</a>' ); ?></p>
		</div>
		<?php
	}

	/**
	 * Check for external blocking contstant
	 * @return string
	 */
	public function check_external_blocking() {
		// show notice if external requests are blocked through the WP_HTTP_BLOCK_EXTERNAL constant
		if( defined( 'WP_HTTP_BLOCK_EXTERNAL' ) && WP_HTTP_BLOCK_EXTERNAL === true ) {

			// check if our API endpoint is in the allowed hosts
			$host = parse_url( $this->upgrade_url, PHP_URL_HOST );

			if( ! defined( 'WP_ACCESSIBLE_HOSTS' ) || stristr( WP_ACCESSIBLE_HOSTS, $host ) === false ) {
				?>
				<div class="error">
					<p><?php printf( __( '<b>Warning!</b> You\'re blocking external requests which means you won\'t be able to get %s updates. Please add %s to %s.', 'monetico' ), $this->monetico_software_product_id, '<strong>' . $host . '</strong>', '<code>WP_ACCESSIBLE_HOSTS</code>'); ?></p>
				</div>
				<?php
			}

		}
	}

} // End of class

function MONETICO() {
    return MONETICO::instance();
}

// Initialize the class instance only once
MONETICO();

/*
*
*
*/

load_plugin_textdomain('monetico', false, dirname(plugin_basename(__FILE__)).'/lang');

function woocommerce_gateway_monetico_activation() {
	if (!is_plugin_active('woocommerce/woocommerce.php')&&!is_plugin_active('woocommerce-woocommerce-99e0562/woocommerce.php')) {
		deactivate_plugins(plugin_basename(__FILE__));		
		$message = sprintf(__("Sorry! To use WooCommerce extension Gateway %s, you must install and activate the WooCommerce extension.", 'monetico'), 'Monetico');
		wp_die($message, __("Extension Payment Gateway Monetico", 'monetico'), array('back_link' => true));
	}
}
//register_activation_hook(__FILE__, 'woocommerce_gateway_monetico_activation');

add_action('plugins_loaded', 'init_gateway_monetico', 0);

function init_gateway_monetico() {
	
	if ( ! class_exists( 'WC_Payment_Gateway' ) ) { return; }
	
	define('__WPRootMonetico__',dirname(dirname(dirname(dirname(__FILE__)))));
	define('__ServerRootMonetico__',dirname(dirname(dirname(dirname(dirname(__FILE__))))));
	define('CURRENT_DIR', plugin_dir_path( __FILE__ ));
	
	// Constantes issues du kit Monetico
	if(!defined("MONETICOPAIEMENT_VERSION"))
		define ("MONETICOPAIEMENT_VERSION", "3.0");
	//define ("MONETICOPAIEMENT_URLSERVER", "https://p.monetico-services.com/");
	define ("MONETICOPAIEMENT_CTLHMAC","V4.0.sha1.php--[CtlHmac%s%s]-%s");
	define ("MONETICOPAIEMENT_CTLHMACSTR", "CtlHmac%s%s");
	define ("MONETICOPAIEMENT_PHASE2BACK_RECEIPT","version=2\ncdr=%s");
	define ("MONETICOPAIEMENT_PHASE2BACK_MACOK","0");
	define ("MONETICOPAIEMENT_PHASE2BACK_MACNOTOK","1\n");
	define ("MONETICOPAIEMENT_PHASE2BACK_FIELDS", "%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*");
	define ("MONETICOPAIEMENT_PHASE1GO_FIELDS", "%s*%s*%s%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s*%s");
	define ("MONETICOPAIEMENT_REFUND_FIELDS", "%s*%s*%s%s*%s*%s*%s*%s*%s*");
	if(!defined("MONETICOPAIEMENT_URLPAYMENT"))
		define ("MONETICOPAIEMENT_URLPAYMENT", "paiement.cgi");
	define ("MONETICOPAIEMENT_URLREFUND", "recredit_paiement.cgi");

	class WC_Gateway_Monetico extends WC_Payment_Gateway {
			
		public function __construct() { 
        	$this->id = 'monetico';
			$this->order_button_text  = __( 'Proceed to Credit Card', 'monetico' ); // Payer par Carte Bancaire
			$this->method_title = 'Monetico';
			$this->logo = plugins_url('woocommerce-gateway-monetico/logo/monetico-paiement.png');
        	$this->has_fields = false;	
			$this->init_form_fields();
			$this->init_settings();
			$this->icon = apply_filters('woocommerce_monetico_icon', $this->settings['gateway_image']);
			$this->title = $this->settings['title'];
			$this->description =  apply_filters( 'woocommerce_gateway_description', $this->settings['description'], $this->id);
			if ( version_compare( WOOCOMMERCE_VERSION, '2.2', '>=' ) ):
				$this->supports = array('products', 'refunds');
			endif;
			add_action( 'woocommerce_api_'.strtolower(get_class($this)), array( $this, 'check_monetico_response' ) );
			add_action('woocommerce_receipt_monetico', array($this, 'receipt_page'));
			add_action('woocommerce_update_options_payment_gateways', array(&$this, 'process_admin_options')); 
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			if ( version_compare( WOOCOMMERCE_VERSION, '2.1.12', '>' ) ): /* WC 2.2 */
				add_filter('woocommerce_thankyou_order_received_text', array($this, 'abw_txt_erreur_paiement'));
			endif;
    		add_action('woocommerce_thankyou_monetico', array($this, 'thankyou_page'));
			add_action( 'woocommerce_email_before_order_table', array( $this, 'paiement_confirme' ), 10, 3 );
    	} 
		function init_form_fields() {
			
			if ( version_compare( WOOCOMMERCE_VERSION, '2.3.0', '<' ) ) {
				$dir_log = __WPRootMonetico__;
			} else {
				$upload_dir = wp_upload_dir();
				$dir_log = $upload_dir['basedir'];
			}
			
			$this->form_fields = array(
				'enabled' => array(
								'title' => __( "Enable/Disable", 'monetico' ), 
								'type' => 'checkbox', 
								'label' => __( "Check to enable the payment Monetico.", 'monetico' ), 
								'default' => 'yes'
							), 
				'title' => array(
								'title' => __( "Title", 'monetico' ), 
								'type' => 'text', 
								'description' => __( "Title displayed when selecting the method of payment.", 'monetico' ), 
								'default' => __( "Credit Card", 'monetico' ),
								'css' => 'width:150px',
								'desc_tip' => true
							),
				'description' => array(
								'title' => __( "Message client", 'monetico' ), 
								'type' => 'textarea', 
								'description' => __( "Inform the customer of payment by credit card.", 'monetico' ), 
								'default' => __( "By choosing this method of payment you can make your payment on the secure server of our bank.", 'monetico' ),
								'desc_tip' => true
							), 
				'gateway_image' => array(
								'title' => __( "Icon payment", 'monetico' ), 
								'type' => 'text', 
								'description' => __( "Url of the image displayed when selecting the method of payment.", 'monetico' ),
								'default' => plugins_url('woocommerce-gateway-monetico/logo/logo-monetico-paiement.png'),
								'css' => 'width:90%',
								'desc_tip' => true
							), 
				'monetico_mode' => array(
					'title' => __("Mode", 'monetico'), 
					'type' => 'select', 
					'description' => __( "Select the mode to use Monetico. You must perform three tests before requesting passage into production to Monetico.", 'monetico' ),
					'options' => array(
						'Test' => __("Test", 'monetico'),
						'Production' => __("Production", 'monetico')
					),
					'default' => 'Test',
					'css' => 'width:160px',
					'desc_tip' => true
				),
				'cle' => array(
								'title' => __("Key", 'monetico'), 
								'type' => 'text', 
								'description' => __( "Secure key.", 'monetico' ), 
								'default' => '12345678901234567890123456789012345678P0',
								'desc_tip' => true
							), 
				'tpe' => array(
								'title' => __("TPE", 'monetico'), 
								'type' => 'text', 
								'description' => __( "Number Electronic Payment Terminal.", 'monetico' ), 
								'default' => '0000001',
								'css' => 'width:100px',
								'desc_tip' => true
							),
				'code_societe' => array(
								'title' => __("Company code", 'monetico'), 
								'type' => 'text', 
								'default' => 'abcdefghij',
								'css' => 'width:150px',
								'desc_tip' => true
							),
				'currency_code' => array(
								'title' => __("Currency", 'monetico'), 
								'type' => 'text', 
								'description' => __( "ISO 4217 compliant.", 'monetico' ), 
								'default' => 'EUR',
								'css' => 'width:40px',
								'desc_tip' => true
							), 
				'merchant_country' => array(
								'title' => __("Language", 'monetico'), 
								'type' => 'text', 
								'description' => __( "Language of the company: FR -> France.", 'monetico' ), 
								'default' => 'FR',
								'css' => 'width:40px',
								'desc_tip' => true
							), 
				'bouton' => array(
								'title' => __("Button", 'monetico'), 
								'type' => 'text', 
								'description' => __( "Button text to access the server from the bank.", 'monetico' ), 
								'default' => __("Secure connection to the server of the bank", 'monetico'),
								'desc_tip' => true
							),
				'redirection' => array(
								'title' => __( "Redirection", 'monetico' ), 
								'id' => 'activer_redirection',
								'type' => 'checkbox', 
								'label' => __( "Check to enable the automatic redirection to the bank server.", 'monetico' ), 
								'default' => 'yes',
								'description' => __("Automatically disabled when debug mode is enabled.", 'monetico'),
								'desc_tip' => true
							), 
				'msg_redirection' => array(
								'title' => __( "Redirection message", 'monetico' ), 
								'id' => 'activer_message_redirection',
								'type' => 'text', 
								'default' => __("Thank you for your order. We redirect you to the server of our bank.", 'monetico'),
								'description' => __("Leave blank to not show message in lightbox.", 'monetico'),
								'desc_tip' => true,
								'css' => 'width:90%'
							),
				'logfile' => array(
								'title' => __("Logfile", 'monetico'), 
								'type' => 'text', 
								'description' => __( "Leave blank to not register log. The destination folder must be writable. If the file does not exist it will be created.", 'monetico' ),
								'default' => $dir_log.'/wc-logs/log_monetico.txt',
								'css' => 'width:90%',
								'desc_tip' => true
							), 
				'debug' => array(
								'title' => __( "Debug", 'monetico' ), 
								'type' => 'checkbox', 
								'label' => __( "Show debugging information.", 'monetico'),
								'description' => __('Do not activate production.', 'monetico' ),
								'default' => 'yes',
								'desc_tip' => true
							)						
				);
		
		}
		public function admin_options() {
	
			?>
            <p><img src="<?php echo $this->logo; ?>" /></p>
			<h3><?php _e("Monetico payment", 'monetico'); ?></h3>
			<p><?php printf(__("Authorizes payments Monetico. This requires the signing of a sales contract with a bank distance compatible with the payment solution %sMonetico%s.", 'monetico'), '<a href="https://www.monetico-paiement.fr/" target="_blank">', '</a>'); ?></p>
            <p><?php printf(__("See our %sinstructions%s carefully to set up your payment solution Monetico.", 'monetico'), '<a href="'.plugin_dir_url( __FILE__ ).'instructions-installation-parametrages.txt" target="_blank">', '</a>'); ?></p>
			<table class="form-table">
			<?php
				$this->generate_settings_html();
				$javascript = "$('input#woocommerce_monetico_redirection').change(function() {
					if ($(this).is(':checked')) {
						$('#woocommerce_monetico_msg_redirection').closest('tr').show();
					} else {
						$('#woocommerce_monetico_msg_redirection').closest('tr').hide();
					}
				}).change();";
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.20', '>' ) ): /* 2.1 */
					wc_enqueue_js( $javascript );
				else:
					global $woocommerce;
					$woocommerce->add_inline_js( $javascript );
				endif;
			?>
			<?php
			echo '<tr><td colspan="2"><strong>'.__("Information about your installation:",'monetico').'</strong></td></tr>';
			echo '<tr><td>'.__("Wordpress root",'monetico').'</td><td><pre>'.__WPRootMonetico__.'</pre></td></tr>';
			echo '<tr><td>'.__("Hosting root",'monetico').'</td><td><pre>'.__ServerRootMonetico__.'</pre></td></tr>';
			echo '<tr><td>'.__("CGI2 URL",'monetico').'</td><td><pre>'.get_site_url().'/?wc-api=WC_Gateway_Monetico</pre></td></tr>';
			
			?>
			</table><!--/.form-table-->
			<?php
		}
		function payment_fields() {
			if ($this->description) echo wpautop(wptexturize($this->description));
		}
		public function generate_monetico_form( $order_id ) {
			global $woocommerce;
			
			$order = new WC_Order( $order_id );
			
			$monetico_settings = get_option('woocommerce_monetico_settings');
			if($monetico_settings['monetico_mode']=="Test"):
				$url_serveur = "https://p.monetico-services.com/test/";
			else:
				$url_serveur = "https://p.monetico-services.com/";
			endif;
			define ("MONETICOPAIEMENT_URLSERVER", $url_serveur);
			
			$order_total = is_callable( array( $order, 'get_total' ) ) ? $order->get_total() : $order->order_total; // WC 3.0
			$montant = number_format(str_replace(",",".",$order_total),2,".","");
			// Ajout de filtres
			$monetico_settings['cle'] = apply_filters( 'monetico_change_cle', $monetico_settings['cle'] );
			$monetico_settings['tpe'] = apply_filters( 'monetico_change_tpe', $monetico_settings['tpe'] );
			$monetico_settings['code_societe'] = apply_filters( 'monetico_change_code_societe', $monetico_settings['code_societe'] );
			$monetico_settings['bouton'] = apply_filters( 'monetico_change_bouton', $monetico_settings['bouton'] );
			$monetico_settings['msg_redirection'] = apply_filters( 'monetico_change_msg_redirection', $monetico_settings['msg_redirection'] );
			define ("MONETICOPAIEMENT_KEY", $monetico_settings['cle']);
			define ("MONETICOPAIEMENT_EPTNUMBER", $monetico_settings['tpe']);
			define ("MONETICOPAIEMENT_COMPANYCODE", $monetico_settings['code_societe']);
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.20', '>' ) ): /* WC 2.1 */
				$urlok = $urlko = $order->get_checkout_order_received_url();
			else:
				$urlok = $urlko = add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id'))));
			endif;
			// Ajout de filtres pour modifier dynamiquement les urls
			$urlok = apply_filters( 'monetico_change_url_ok', $urlok );
			$urlko = apply_filters( 'monetico_change_url_ko', $urlko );
			define ("MONETICOPAIEMENT_URLOK", $urlok);
			define ("MONETICOPAIEMENT_URLKO", $urlko);
			
			require_once(CURRENT_DIR."monetico/MoneticoPaiement_Ept.inc.php");
			
			$sOptions = "";
			$sReference = $order_id;
			$sMontant = $montant;
			$sDevise  = apply_filters( 'monetico_change_devise', $monetico_settings['currency_code'] );
			$sDate = date("d/m/Y:H:i:s");
			$sLangue = apply_filters( 'monetico_change_langue', $monetico_settings['merchant_country'] );
			$billing_email = is_callable( array( $order, 'get_billing_email' ) ) ? $order->get_billing_email() : $order->billing_email; // WC 3.0
			$sEmail = $billing_email;
			$sTexteLibre = $billing_email;
			$sNbrEch = "";
			$sDateEcheance1 = "";
			$sMontantEcheance1 = "";
			$sDateEcheance2 = "";
			$sMontantEcheance2 = "";
			$sDateEcheance3 = "";
			$sMontantEcheance3 = "";
			$sDateEcheance4 = "";
			$sMontantEcheance4 = "";
			$oEpt = new MoneticoPaiement_Ept($sLangue);      		
			$oHmac = new MoneticoPaiement_Hmac($oEpt);      	        
			
			// Chaine de controle pour support
			//$CtlHmac = sprintf(MONETICOPAIEMENT_CTLHMAC, $oEpt->sVersion, $oEpt->sNumero, $oHmac->computeHmac(sprintf(MONETICOPAIEMENT_CTLHMACSTR, $oEpt->sVersion, $oEpt->sNumero)));
			
			$phase1go_fields = sprintf(MONETICOPAIEMENT_PHASE1GO_FIELDS,     $oEpt->sNumero,
                                              $sDate,
                                              $sMontant,
                                              $sDevise,
                                              $sReference,
                                              $sTexteLibre,
                                              $oEpt->sVersion,
                                              $oEpt->sLangue,
                                              $oEpt->sCodeSociete, 
                                              $sEmail,
                                              $sNbrEch,
                                              $sDateEcheance1,
                                              $sMontantEcheance1,
                                              $sDateEcheance2,
                                              $sMontantEcheance2,
                                              $sDateEcheance3,
                                              $sMontantEcheance3,
                                              $sDateEcheance4,
                                              $sMontantEcheance4,
                                              $sOptions);
			
			$sMAC = $oHmac->computeHmac($phase1go_fields);
			
			?>
				
			<!-- FORMULAIRE TYPE DE PAIEMENT / PAYMENT FORM TEMPLATE -->
			<form action="<?php echo $oEpt->sUrlPaiement; ?>" method="post" id="PaymentRequest">
			<p>
				<input type="hidden" name="version"             id="version"        value="<?php echo $oEpt->sVersion;?>" />
				<input type="hidden" name="TPE"                 id="TPE"            value="<?php echo $oEpt->sNumero;?>" />
				<input type="hidden" name="date"                id="date"           value="<?php echo $sDate;?>" />
				<input type="hidden" name="montant"             id="montant"        value="<?php echo $sMontant.$sDevise;?>" />
				<input type="hidden" name="reference"           id="reference"      value="<?php echo $sReference;?>" />
				<input type="hidden" name="MAC"                 id="MAC"            value="<?php echo $sMAC;?>" />
				<input type="hidden" name="url_retour"          id="url_retour"     value="<?php echo trailingslashit(get_bloginfo('wpurl'));?>" />
				<input type="hidden" name="url_retour_ok"       id="url_retour_ok"  value="<?php echo $oEpt->sUrlOK;?>" />
				<input type="hidden" name="url_retour_err"      id="url_retour_err" value="<?php echo $oEpt->sUrlKO;?>" />
				<input type="hidden" name="lgue"                id="lgue"           value="<?php echo $oEpt->sLangue;?>" />
				<input type="hidden" name="societe"             id="societe"        value="<?php echo $oEpt->sCodeSociete;?>" />
				<input type="hidden" name="texte-libre"         id="texte-libre"    value="<?php echo HtmlEncode($sTexteLibre);?>" />
				<input type="hidden" name="mail"                id="mail"           value="<?php echo $sEmail;?>" />
				<input type="submit" name="bouton"              id="bouton"         value="<?php _e($monetico_settings['bouton'], 'monetico'); ?>" />
			</p>
			</form>
			<!-- FIN FORMULAIRE TYPE DE PAIEMENT / END PAYMENT FORM TEMPLATE -->			
<?php		
			if($monetico_settings['redirection']=='yes'&&$monetico_settings['debug']!='yes'){
				if(trim($monetico_settings['msg_redirection'])!="") {
					$javascript = '
					$.blockUI({
							message: "' . esc_js( __( $monetico_settings['msg_redirection'], 'monetico' ) ) . '",
							baseZ: 99999,
							overlayCSS:
							{
								background: "#000",
								opacity: 0.75
							},
							css: {
								padding:        "20px",
								zindex:         "9999999",
								textAlign:      "center",
								color:          "#555",
								border:         "3px solid #aaa",
								backgroundColor:"#fff",
								cursor:         "wait",
								lineHeight:		"24px",
							}
						});
					';
					if ( version_compare( WOOCOMMERCE_VERSION, '2.0.20', '>' ) ): /* 2.1 */
						wc_enqueue_js( $javascript );
					else:
						$woocommerce->add_inline_js( $javascript );
					endif;
				}
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.20', '>' ) ): /* 2.1 */
					wc_enqueue_js( 'jQuery("#bouton").click();' );
				else:
					$woocommerce->add_inline_js( 'jQuery("#bouton").click();' );
				endif;
			}

			if($monetico_settings['debug']=='yes'){
				echo "<fieldset><legend><strong>".__("Debug mode active","monetico")."</strong>&nbsp;</legend>";
				echo "<strong>Clé :</strong> ".substr($monetico_settings['cle'],0,5).str_repeat("*",30).substr($monetico_settings['cle'],35,5)."<br/>";
				echo "<strong>URL Monetico :</strong> ".MONETICOPAIEMENT_URLSERVER."<br/>";
				echo "<strong>version :</strong> ".$oEpt->sVersion."<br/>";
				echo "<strong>TPE :</strong> ".$oEpt->sNumero."<br/>";
				echo "<strong>date :</strong> ".$sDate."<br/>";
				echo "<strong>montant :</strong> ".$sMontant.$sDevise."<br/>";
				echo "<strong>reference :</strong> ".$sReference."<br/>";
				echo "<strong>MAC :</strong> ".$sMAC."<br/>";
				echo "<strong>url_retour :</strong> ".trailingslashit(get_bloginfo('url'))."<br/>";
				echo "<strong>url_retour_ok :</strong> ".$oEpt->sUrlOK."<br/>";
				echo "<strong>url_retour_err :</strong> ".$oEpt->sUrlKO."<br/>";
				echo "<strong>lgue :</strong> ".$oEpt->sLangue."<br/>";
				echo "<strong>societe :</strong> ".$oEpt->sCodeSociete."<br/>";
				echo "<strong>mail :</strong> ".$sEmail;
				echo "</fieldset>";
			}
		}		
		
		function process_payment( $order_id ) {
			$order = new WC_Order( (int) $order_id );
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.14', '>=' ) ): /* WC 2.1 */
				$redirect = $order->get_checkout_payment_url( true ); /* WC 2.1 */
			else:
				$redirect = add_query_arg('order', $order_id, add_query_arg('key', $order->order_key, get_permalink(get_option('woocommerce_pay_page_id'))));
			endif;
			return array(
				'result' 	=> 'success',
				'redirect'	=> $redirect
			);		}
		function receipt_page( $order ) {

			echo '<p>'.apply_filters( 'monetico_change_bank_msg', __("Thank you for your order, please click on the button below to make payment to our bank.", "monetico")).'</p>';
			
			echo $this->generate_monetico_form( $order );
			
		}
		function check_monetico_response() {
			global $woocommerce;
			
			if (isset($_GET['wc-api']) && $_GET['wc-api'] == 'WC_Gateway_Monetico'):

				$monetico_settings = get_option('woocommerce_monetico_settings');
				require_once(CURRENT_DIR."monetico/MoneticoPaiement_Ept.inc.php");
				$MoneticoPaiement_bruteVars = getMethode();
				$order_id = (int) $MoneticoPaiement_bruteVars['reference'];
				$order = new WC_Order( $order_id );
				
				define ("MONETICOPAIEMENT_KEY", apply_filters('monetico_change_cle',$monetico_settings['cle']));
				define ("MONETICOPAIEMENT_EPTNUMBER", apply_filters('monetico_change_tpe',$monetico_settings['tpe']));
				if($monetico_settings['monetico_mode']=="Test"):
					$url_serveur = "https://p.monetico-services.com/test/";
				else:
					$url_serveur = "https://p.monetico-services.com/";
				endif;
				define ("MONETICOPAIEMENT_URLSERVER", $url_serveur);
				define ("MONETICOPAIEMENT_COMPANYCODE", apply_filters('monetico_change_code_societe',$monetico_settings['code_societe']));
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.20', '>' ) ): /* WC 2.1 */
					define ("MONETICOPAIEMENT_URLOK", $order->get_checkout_order_received_url());
					define ("MONETICOPAIEMENT_URLKO", $order->get_checkout_order_received_url());
				else:
					define ("MONETICOPAIEMENT_URLOK", add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id')))));
					define ("MONETICOPAIEMENT_URLKO", add_query_arg('key', $order->order_key, add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_thanks_page_id')))));
				endif;

				$MoneticoPaiement_bruteVars['texte-libre']=stripslashes($MoneticoPaiement_bruteVars['texte-libre']);
				$oEpt = new MoneticoPaiement_Ept();
				$oHmac = new MoneticoPaiement_Hmac($oEpt);
				$phase2back_fields = sprintf(MONETICOPAIEMENT_PHASE2BACK_FIELDS, $oEpt->sNumero,
                        $MoneticoPaiement_bruteVars["date"],
                        $MoneticoPaiement_bruteVars['montant'],
                        $MoneticoPaiement_bruteVars['reference'],
                        $MoneticoPaiement_bruteVars['texte-libre'],
                        $oEpt->sVersion,
                        $MoneticoPaiement_bruteVars['code-retour'],
                        $MoneticoPaiement_bruteVars['cvx'],
                        $MoneticoPaiement_bruteVars['vld'],
                        $MoneticoPaiement_bruteVars['brand'],
                        $MoneticoPaiement_bruteVars['status3ds'],
                        $MoneticoPaiement_bruteVars['numauto'],
                        $MoneticoPaiement_bruteVars['motifrefus'],
                        $MoneticoPaiement_bruteVars['originecb'],
                        $MoneticoPaiement_bruteVars['bincb'],
                        $MoneticoPaiement_bruteVars['hpancb'],
                        $MoneticoPaiement_bruteVars['ipclient'],
                        $MoneticoPaiement_bruteVars['originetr'],
                        $MoneticoPaiement_bruteVars['veres'],
                        $MoneticoPaiement_bruteVars['pares']
					);
				
				$logfile = $monetico_settings['logfile'];
				if($logfile!="")
					$fp=@fopen($logfile, "a");
				
				if ($oHmac->computeHmac($phase2back_fields) == strtolower($MoneticoPaiement_bruteVars['MAC'])) {
										
					switch($MoneticoPaiement_bruteVars['code-retour']) {
						case "Annulation" :
							switch($MoneticoPaiement_bruteVars['motifrefus']) {
								case "Appel Phonie" : $msg_err = __("The customer's bank requests additional information.","monetico"); break;
								case "Refus" :
								case "Interdit" : $msg_err = __("The customer's bank refuses to grant permission.","monetico"); break;
								case "Filtrage" : $msg_err = __("The payment request was blocked by the filter settings that the merchant has implemented in its Fraud Prevention Module.","monetico");
									$filtrage = array(1=>__("IP address","monetico"), 2=>__("Card number","monetico"), 3=>__("Card BIN","monetico"), 4=>__("Country card","monetico"), 5=>__("Country IP","monetico"), 6=>__("Consistency country / card countries IP","monetico"), 7=>__("Disposable email","monetico"), 8=>__("Limitation amount for a CB over time","monetico"), 9=>__("Limitation of number of transactions for a CB over time","monetico"), 11=>__("Limitation of number of transactions over a period alias","monetico"), 12=>__("Limitation on amount per alias over time","monetico"), 13=>__("Limitation amount by IP over time","monetico"), 14=>__("Limitation of number of transactions by IP over time","monetico"), 15=>__("Testers cards","monetico"), 16=>__("Limitation on number of aliases by CB","monetico"));
									$filtres = explode("-", $MoneticoPaiement_bruteVars['filtragecause']);
									foreach($filtres as $filtre) {
										if(trim($filtre)!=""&&is_numeric($filtre)) {
											$liste.= $filtrage[trim($filtre)].", ";	
										}
									}
									if($liste!="") {
										$liste = substr($liste, 0, -2);
										$msg_err .= " ".__("Cause of filtering:","monetico")." ".$liste.".";
									}
								break;
								case "3DSecure" : $msg_err = __("3D Secure authentication negatively received from the holder's bank.","monetico"); break;
								default : $msg_err = __("Unknown error","monetico");
							}
							$order->update_status('failed');
							
							$order->add_order_note(__("Credit Card Payment: FAIL<br/>Error:",'monetico').' '.$msg_err);
							if ( version_compare( WOOCOMMERCE_VERSION, '2.0.14', '>=' ) ): 
								$payer_url = $order->get_checkout_payment_url();
							else:
								$payer_url = add_query_arg('order_id', $order_id, add_query_arg('order', $order->order_key, add_query_arg('pay_for_order', 'true', get_permalink(get_option('woocommerce_pay_page_id')))));
							endif;
							$order->add_order_note(sprintf(__("Failure of payment by credit card for your order, <a href=\"%s\">click here</a> to retry payment.", "monetico"), $payer_url),1); /* WC 2.1 */
							break;
				
						case "payetest":
						case "paiement":
							$order_status = is_callable( array( $order, 'get_status') ) ? $order->get_status() : $order->status; // WC 3.0
							if ($order_status !== 'completed') {
								if ($order_status == 'processing') {
								} else {
									switch($MoneticoPaiement_bruteVars['status3ds']):
										case -1: $msg_3ds = __("The transaction was not made according to the protocol 3DSecure.", 'monetico'); break;
										case 1: $msg_3ds = __("The transaction was made according to 3DS protocol and the risk level is", 'monetico')." ".__("low.", 'monetico'); break;
										case 2: $msg_3ds = __("The transaction may not be done according to the protocol 3DSecure, the holder has however been authenticated through 3DSecure.", 'monetico'); break;
										case 3: $msg_3ds = __("The transaction was made according to 3DS protocol and the risk level is", 'monetico')." ".__("high.", 'monetico'); break;
										case 4: $msg_3ds = __("The transaction was made according to 3DS protocol and the risk level is", 'monetico')." ".__("very high.", 'monetico'); break;
										default : $msg_3ds = "";
									endswitch;
									$msg_3ds = " ".$msg_3ds;
									$order->add_order_note(trim(__("Credit card payment confirmed.",'monetico').$msg_3ds));
									$order->payment_complete($MoneticoPaiement_bruteVars['numauto']);
									$woocommerce->cart->empty_cart();
								}
							}	
							break;
				
						case "paiement_pf2":
						case "paiement_pf3":
						case "paiement_pf4":
							break;
				
						case "Annulation_pf2":
						case "Annulation_pf3":
						case "Annulation_pf4":
							break;
					}
				
					$receipt = MONETICOPAIEMENT_PHASE2BACK_MACOK;
					
					if($fp) {
						foreach($MoneticoPaiement_bruteVars as $key => $value)
							fwrite($fp, $key." : ".$value."\n");
						fwrite( $fp, "-------------------------------------------\n");
					}
				
				} else {
					$receipt = MONETICOPAIEMENT_PHASE2BACK_MACNOTOK.$phase2back_fields;
					if($fp)
						fwrite($fp, date("d/m/Y H:i:s")." : ".utf8_decode(__("Payment problem, HMAC does not match","monetico"))."\n".$phase2back_fields."\n-------------------------------------------\n");
				}
				if($fp)
					@fclose($fp);
				printf (MONETICOPAIEMENT_PHASE2BACK_RECEIPT, $receipt);
				die();
			endif;
		}
		
		public function process_refund( $order_id, $amount = null, $reason = '' ) {
			global $wpdb;
			$order = wc_get_order( $order_id );
			if ( ! ($order && $order->get_transaction_id()) ) {
				return false;
			}
			$monetico_settings = get_option('woocommerce_monetico_settings');
			$devise = $monetico_settings['currency_code'];
			$montant_recredit = number_format(str_replace(",", ".", $amount), 2, ".", "").$devise;
			$montant = number_format(str_replace(",", ".", $order->get_total()), 2, ".", "").$devise;
			
			$sql = "SELECT m.meta_value AS refund
    				FROM {$wpdb->prefix}postmeta AS m
    				LEFT JOIN {$wpdb->prefix}posts AS p ON ( p.ID = m.post_id )
    				WHERE m.meta_key LIKE '_refund_amount'
    				AND p.post_parent = ".$order_id;
			$refunds = $wpdb->get_results( $sql );
			
			foreach ($refunds as $refund) {
 				$total_refunds += $refund->refund;
			}
			$total_refunds = $total_refunds-$amount;
			$montant_possible = number_format(str_replace(",", ".", $order->get_total()-$total_refunds ), 2, ".", "").$devise;
			
			define ("MONETICOPAIEMENT_URLSERVER", "https://p.monetico-services.com/");
			define ("MONETICOPAIEMENT_KEY", $monetico_settings['cle']);
			define ("MONETICOPAIEMENT_EPTNUMBER", $monetico_settings['tpe']);
			define ("MONETICOPAIEMENT_COMPANYCODE", $monetico_settings['code_societe']);
			
			require_once(CURRENT_DIR."monetico/MoneticoPaiement_Ept.inc.php");
			
			$sDate = date("d/m/Y:H:i:s");
			$sLangue = $monetico_settings['merchant_country'];
			$sReference = $order_id;
            $sTexteLibre = $reason;
			$oEpt = new MoneticoPaiement_Ept($sLangue);      		
			$oHmac = new MoneticoPaiement_Hmac($oEpt);      	        
						
			$refund_fields = sprintf(MONETICOPAIEMENT_REFUND_FIELDS,     $oEpt->sNumero,
                                              $sDate,
                                              $montant_recredit,
                                              $montant_possible,
                                              $sReference,
                                              $sTexteLibre,
                                              $oEpt->sVersion,
                                              $oEpt->sLangue,
                                              $oEpt->sCodeSociete
                                              );
			$sMAC = $oHmac->computeHmac($refund_fields);
			
			
			$args = array(
				'body' => array( 
					'version' => MONETICOPAIEMENT_VERSION,
					'TPE' => $monetico_settings['tpe'],
					'date' => $sDate,
					'date_commande' => date("d/m/Y", strtotime( $order->order_date )),
					'date_remise' => date("d/m/Y", strtotime( $order->order_date )),
					'num_autorisation' => $order->get_transaction_id(),
					'montant' => $montant,
					'montant_recredit' => $montant_recredit,
					'montant_possible' => $montant_possible,
					'reference' => $sReference,
					'texte-libre' => $sTexteLibre,
					'lgue' => $oEpt->sLangue,
					'societe' => $oEpt->sCodeSociete,
					'MAC' => $sMAC
					 )
				);
			$response = wp_remote_post( MONETICOPAIEMENT_URLSERVER.MONETICOPAIEMENT_URLREFUND, $args );
			
			
			if ( is_wp_error( $response ) ):
			   return $response;
			else:
				$body = explode("\n", $response['body']);
				if($body[2]!="cdr=0"):
					return new WP_Error( 'erreur_monetico', __("Error:", 'monetico')." ".str_replace("lib=", "", $body[3]) );	
				else:
					if($reason!='') $more = " (".$reason.")"; else $more = "";
					$order->add_order_note( sprintf( __( 'Refunded %s - Autorisation ID: %s', 'monetico' ).$more, wc_price($amount), str_replace("aut=", "", $body[4])), 1);
					if($montant_possible==$montant_recredit):
						$order->update_status( 'refunded', '' );
					endif;
					return true;	
				endif;
			endif;
  			return false;
		}
		
		function paiement_confirme($order, $sent_to_admin, $plain_text = false) {
			$payment_method = is_callable( array( $order, 'get_payment_method') ) ? $order->get_payment_method() : $order->payment_method; // WC 3.0
			if ( ! $sent_to_admin && 'monetico' === $payment_method && $order->has_status( 'processing' ) ) {  /// $order->get_status()
			
				echo '<p>'.__("Credit card payment confirmed.",'monetico').'</p>';
			}
		}

		function abw_txt_erreur_paiement($texte) {
			global $woocommerce;
			global $wp;
			$order_id = (int) $wp->query_vars['order-received'];
			$order = new WC_Order( $order_id );
			$payment_method = is_callable( array( $order, 'get_payment_method') ) ? $order->get_payment_method() : $order->payment_method; // WC 3.0
			$order_status = is_callable( array( $order, 'get_status') ) ? $order->get_status() : $order->status; // WC 3.0
			if ($payment_method == 'monetico' && ($order_status == 'pending'||$order_status == 'cancelled')) {
				return __("Payment error! Your order is not confirmed.", "monetico");
			} else {
				return $texte;
			}
		}
	
		function thankyou_page() {
			global $woocommerce;
			if ( version_compare( WOOCOMMERCE_VERSION, '2.0.20', '>' ) ): /* WC 2.1 */
				global $wp;
				$order_id = (int) $wp->query_vars['order-received'];
			else:
				$order_id = (int) $_GET['order'];
			endif;
			$order = new WC_Order( $order_id );
			$statuts = array('processing', 'completed');
			$order_status = is_callable( array( $order, 'get_status') ) ? $order->get_status() : $order->status; // WC 3.0
			if ( in_array($order_status, apply_filters( 'monetico_change_liste_statuts_ok', $statuts)) ) {
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.20', '>' ) ): /* WC 2.1 */
					$url_commande = $order->get_view_order_url();
					$order_total = is_callable( array( $order, 'get_total' ) ) ? $order->get_total() : $order->order_total; // WC 3.0
					$montant_commande = wc_price($order_total);
				else:
					$url_commande = add_query_arg('order', $order_id, get_permalink(get_option('woocommerce_view_order_page_id')));
					$montant_commande = woocommerce_price($order->order_total);
				endif;
				$compte_client = get_post_meta( $order_id, '_customer_user', true );
				printf("<p>".__("Your credit card payment of %s has been finalized with our bank", "monetico"), $montant_commande);
				if($compte_client>0):
					printf(__(", <a href=\"%s\">click here</a> to view your order.", "monetico")."</p>", $url_commande);
				else:
					echo ".</p>";
				endif;
			} elseif($order_status != 'failed') {
				if ( version_compare( WOOCOMMERCE_VERSION, '2.0.14', '>=' ) ): /* WC 2.1 */
					$payer_url = $order->get_checkout_payment_url();
				else:
					$payer_url = add_query_arg('order_id', $order_id, add_query_arg('order', $order->order_key, add_query_arg('pay_for_order', 'true', get_permalink(get_option('woocommerce_pay_page_id')))));
				endif;
				printf("<p>".__("Failure of payment by credit card for your order, <a href=\"%s\">click here</a> to retry payment.", "monetico")."</p>", $payer_url); /* WC 2.1 */
			}
		}
	}

	function add_monetico_gateway( $methods ) {
		$methods[] = 'WC_Gateway_Monetico'; return $methods;
	}

	add_filter('woocommerce_payment_gateways', 'add_monetico_gateway' );

}

function woocommerce_gateway_monetico_add_link($links, $file) {
	if ( version_compare( WOOCOMMERCE_VERSION, '2.0.20', '>' ) ): /* WC 2.1 */
		$reglages_url = 'admin.php?page=wc-settings&tab=checkout&section=wc_gateway_monetico';
	else:
		$reglages_url = 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_Gateway_Monetico';
	endif;
	$links[] = '<a href="'.admin_url($reglages_url).'">' . __('Settings','monetico') .'</a>';
	return $links;
}
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'woocommerce_gateway_monetico_add_link',  10, 2);
?>