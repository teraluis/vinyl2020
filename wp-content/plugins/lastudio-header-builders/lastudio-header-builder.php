<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://la-studioweb.com/
 * @since             1.0.0
 * @package           LaStudio_Header_Builder
 *
 * @wordpress-plugin
 * Plugin Name:       LA-Studio Header Builder
 * Plugin URI:        https://la-studioweb.com/
 * Description:       This plugin use only for LA-Studio theme
 * Version:           1.1.1
 * Author:            LA-Studio
 * Author URI:        https://la-studioweb.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       lastudio-header-builder
 * Domain Path:       /languages
 */

// don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

if ( ! class_exists( 'LaStudio_Header_Builder' ) ) :
    class LaStudio_Header_Builder {

        /**
		 * Instance of this class.
         *
		 * @since   1.0.0
		 * @access  private
		 * @var     LaStudio_Header_Builder
		 */
		private static $instance;

		/**
		 * The modules variable holds all modules of the plugin.
		 *
		 * @since	1.0.0
		 * @access	private
		 * @var		object
		 */
		private static $modules = array();

        /**
		 * Main path.
		 *
		 * @since   1.0.0
		 * @access  private
		 * @var     string
		 */
		private static $path;

		/**
		 * Absolute url.
		 *
		 * @since   1.0.0
		 * @access  private
		 * @var     string
		 */
		private static $url;

		/**
		 * The current version of the LaStudio Header Footer Builder.
		 *
		 * @since    1.0.0
		 */
		const VERSION		= '1.1.1';

		/**
		 * The LaStudio Header Footer Builder prefix to reference classes inside it.
		 *
		 * @since	1.0.0
		 */
		const CLASS_PREFIX	= 'LAHB_';

		/**
		 * The LaStudio Header Footer Builder prefix to reference files and prefixes inside it.
		 *
		 * @since	1.0.0
		 */
		const FILE_PREFIX	= 'lahb-';

        /**
		 * Provides access to a single instance of a module using the singleton pattern.
		 *
		 * @since   1.0.0
		 * @return	object
		 */
		public static function get_instance() {
			if ( self::$instance === null ) {
				self::$instance = new self();
            }

			return self::$instance;
		}

        /**
		 * Define the core functionality of the LaStudio Header Footer Builder.
		 *
		 * Load the dependencies.
		 *
		 * @since	1.0.0
		 */
		public function __construct() {
			self::$path	= plugin_dir_path( __FILE__ );
			self::$url	= plugin_dir_url( __FILE__ );


            add_action('init', array( $this, 'register_my_session' ), 1);

            require_once( self::$path . 'includes/functions/functions.php' );
			require_once( self::$path . 'includes/class-loader.php' );

			self::$modules['LAHB_Loader']			= LAHB_Loader::get_instance();
			self::$modules['LAHB_Helper']			= LAHB_Helper::get_instance();
			// LAHB_Helper::clearHeaderData();
			LAHB_Helper::setHeaderDefaultData();
			self::$modules['LAHB_Enqueue']			= LAHB_Enqueue::get_instance();
			self::$modules['LAHB_Ajax']			    = LAHB_Ajax::get_instance();
			self::$modules['LAHB_Field']			= LAHB_Field::get_instance();
			self::$modules['LAHB_Element']			= LAHB_Element::get_instance();
			self::$modules['LAHB_Output']			= LAHB_Output::get_instance();
			self::$modules['LAHB_Frontend_Builder']= LAHB_Frontend_Builder::get_instance();

            load_plugin_textdomain(
                'lastudio-header-builder',
                false,
                basename( dirname( __FILE__ ) ) . '/languages'
            );

            //add_action('LaStudio_Builder/UpdateDynamicStrings', array( $this, 'UpdateDynamicStrings') );

            add_action('after_setup_theme', array( $this, 'AutoRegisterDynamicStrings') );
		}

		/**
		 * Get the LaStudio Header Footer Builder absolute path.
		 *
		 * @since	1.0.0
		 */
		public static function get_path() {
			return self::$path;
		}

		/**
		 * Get the LaStudio Header Footer Builder absolute url.
		 *
		 * @since	1.0.0
		 */
		public static function get_url() {
			return self::$url;
		}

		public function register_my_session(){
		    if(version_compare(phpversion(), '5.4.0', '>=')){
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                }
            }
		    else{
                if( !session_id() ) {
                    session_start();
                }
            }
        }

        public function AutoRegisterDynamicStrings(){

		    if(!function_exists('pll_register_string')){
		        return;
            }

		    $lahb_preheaders = get_option('lahb_preheaders');
            $lahb_data_frontend_components = get_option('lahb_data_frontend_components');
            $all_components = array();
            $all_components[] = LAHB_Helper::get_only_components_from_settings($lahb_data_frontend_components);
            foreach ($lahb_preheaders as $value){
                if(!empty($value['data'])){
                    $data = json_decode($value['data'], true);
                    if(!empty($data['lahb_data_frontend_components']['components'])){
                        $all_components[] = $data['lahb_data_frontend_components']['components'];
                    }
                }
            }

            $domain = 'Header Builder';
            $context = 'Component';

		    $get_dynamic_strings = LAHB_Helper::get_dynamic_strings();
		    $all_strings = [];

            if(!empty($all_components)){
                foreach ($all_components as $components){
                    if(!empty($components)){
                        foreach ($components as $uid => $component){
                            if( isset($component['component_name']) && isset($get_dynamic_strings[$component['component_name']]) ) {
                                // Grab the dynamic string
                                foreach ($get_dynamic_strings[$component['component_name']] as $k){
                                    if(!empty($component[$k])){
                                        $all_strings[] = array(
                                            'uid'               => $component['component_name'] . $uid,
                                            'component_name'    => $component['component_name'],
                                            'key'               => $k,
                                            'string'            => $component[$k]
                                        );
                                    }
                                }
                            }
                        }
                    }
                }
            }

            if(!empty($all_strings)){
                foreach ($all_strings as $string){
                    $context_tmp = '['. ucwords(str_replace('-', ' ', $string['component_name'])) . '] - ' . ucwords(str_replace('_', ' ', $string['key']));
                    pll_register_string($context_tmp, $string['string'], $domain, true);
                }
            }
        }

    }

	// Create a simple alias
	class_alias( 'LaStudio_Header_Builder', 'LAHB' );

endif;

// Run LaStudio Header Footer Builder
add_action('plugins_loaded', array('LAHB', 'get_instance'));