<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

//defined('IMPORT_DEBUG') or define('IMPORT_DEBUG', true);

class LaStudio_Importer {

	protected $fetch_attachments = true;

	protected $demo_data, $current_id, $setting_args, $wxr_import, $theme_name;

	protected $demo_site_url;

	protected $logger = null;

	public function __construct( $theme_name = '', $demo_data = array(), $demo_site = '' ) {
		$this->theme_name  = $theme_name;
		$this->demo_data  = $demo_data;
		$this->demo_site_url = $demo_site;
		$this->current_site_url = site_url('/');

		$this->init();
	}

	private function init(){

		global $pagenow;

		if( 'tools.php' == $pagenow ) {
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		}

		if( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			if( isset($_REQUEST['action']) && $_REQUEST['action'] == 'lastudio-importer' ) {
				if(!class_exists('WXR_Importer')){
					require dirname( __FILE__ ) . '/importer/plugin.php';
				}
				$this->set_logger();
			}
		}

		add_action( 'wp_ajax_lastudio-importer', array( $this, 'action_process_importer' ) );
		add_filter( 'LaStudio_Importer/widgets/widget_setting_object', array( $this, 'fixed_nav_menu_widget_settings' ) );

		add_action( 'init', array( $this, 'clear_imported_data'), 1);
	}

	public static function admin_enqueue_scripts(){
		wp_register_script( 'eventsource', plugin_dir_url( __FILE__ ) . 'assets/js/eventsource.js' );
		wp_enqueue_script( 'lastudio-importer-js', plugin_dir_url( __FILE__ ) . 'assets/js/import.js' , array( 'jquery', 'eventsource' ) );
		wp_localize_script( 'lastudio-importer-js', 'lastudio_importer',
			array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'ajax_nonce'  => wp_create_nonce( 'lastudio-importer-security' ),
				'loader_text' => esc_html__( 'Importing now, please wait!', 'la-studio' ),
			)
		);
		wp_enqueue_style( 'lastudio-importer-css', plugin_dir_url( __FILE__ ) . 'assets/css/import.css', array()  );
	}

	public static function fixed_nav_menu_widget_settings( $widgets ) {
		if(isset($widgets->nav_menu)){
			$nav_menu = wp_get_nav_menu_object($widgets->nav_menu);
			if(isset($nav_menu->term_id)){
				$widgets->nav_menu = $nav_menu->term_id;
			}
		}
		return $widgets;
	}

	/**
	 * Get data from filters, after the theme has loaded and instantiate the importer.
	 */

	public function get_demo_data(){
		return $this->demo_data;
	}

	public function action_process_importer(){

		if( false === check_ajax_referer( 'lastudio-importer-security', 'security', false ) ) {
			wp_send_json( __( 'Access define!', 'la-studio' ) );
		}
		if( !current_user_can( 'import' ) ) {
			wp_send_json( __( 'Access define!', 'la-studio' ) );
		}

		$id = isset( $_REQUEST['id'] ) ? $_REQUEST['id'] : false;
		$args = shortcode_atts( array(
			'content'   => true,
			'widget'    => true,
			'slider'    => true,
			'option'    => true,
			'fetch_attachments' => true
		), isset($_REQUEST['args']) ? $_REQUEST['args'] : array() );

		if( empty( $id ) || !array_key_exists( $id, $this->demo_data ) ) {
			wp_send_json( __( 'Access define!', 'la-studio' ) );
		}

		$this->current_id = $id;
		$this->setting_args = $args;
		$this->wxr_import = $this->get_importer();

		if(isset($_REQUEST['start_import']) && $_REQUEST['start_import'] == 'true' && !empty($this->current_id) && !empty($this->demo_data) && !empty($this->wxr_import)){
			do_action('LaStudio_Importer/copy_image');
			add_filter( 'intermediate_image_sizes_advanced', '__return_null' );
			add_filter( 'wxr_importer.pre_process.post', array( $this, 'modify_post_author_when_using_ajax'), 10, 4 );
			$this->_start_import_stream();
		}
		elseif(!empty($_REQUEST['start_import_without_content']) && $_REQUEST['start_import_without_content'] == 'true' && !empty($this->current_id) && !empty($this->demo_data)){
			$this->_start_import_stream_without_content();
		}
		else{
			if(!isset($this->importer_is_running)){
				$this->_start_ajax_importer_handling();
			}
		}
	}

	protected function set_logger(){
		$this->logger = new WP_Importer_Logger_ServerSentEvents();
	}

	protected function _start_ajax_importer_handling(){
		/**
		 * Check data-sample.xml has imported
		 */
		$opts = get_option($this->theme_name . '_imported_demos');
		if(!empty($opts)){
			/*
			 * If content has been imported. We need import theme options, custom setting, slider ... etc.
			 */

			if( array_key_exists($this->current_id, $opts) ) {
				$this->setting_args = array(
					'content'   => false,
					'widget'    => false,
					'slider'    => false,
					'option'    => true,
					'fetch_attachments' => true
				);
			}

			$ajax_import_url = add_query_arg(
				array(
					'action' => 'lastudio-importer',
					'id'      => $this->current_id,
					'args'    => $this->setting_args,
					'security' => $_REQUEST['security'],
					'start_import_without_content' => 'true'
				),
				admin_url( 'admin-ajax.php' )
			);
		}
		else{
			$ajax_import_url = add_query_arg(
				array(
					'action' => 'lastudio-importer',
					'id'      => $this->current_id,
					'args'    => $this->setting_args,
					'security' => $_REQUEST['security'],
					'start_import' => 'true'
				),
				admin_url( 'admin-ajax.php' )
			);
		}

		$xml_data = $this->_get_data_for_demo();
		$mapping = $this->get_author_mapping();
		$fetch_attachments = ( ! empty( $this->setting_args['fetch_attachments'] ) && $this->setting_args['fetch_attachments'] && $this->allow_fetch_attachments() );
		$this->fetch_attachments = $fetch_attachments;
		$settings = compact( 'mapping', 'fetch_attachments' );

		update_option('_wxr_import_settings', $settings);

		wp_send_json(array(
			'status' => 'success',
			'data'  => array(
				'count' => array(
					'posts' => $xml_data->post_count,
					'media' => $xml_data->media_count,
					'users' => count( $xml_data->users ),
					'comments' => $xml_data->comment_count,
					'terms' => $xml_data->term_count,
				),
				'url' => $ajax_import_url,
				'strings' => array(
					'complete' => __( 'Import complete!', 'la-studio' ),
				)
			)
		));

	}

	protected function _get_data_for_demo(){
		$existing = get_option( $this->theme_name . sanitize_key($this->current_id) . '_wxr_import_info' );
		if ( ! empty( $existing ) ) {
			$this->authors = $existing->users;
			$this->version = $existing->version;
			return $existing;
		}
		$selected_demo = $this->demo_data[ $this->current_id ];
		$importer = $this->wxr_import;
		$data = $importer->get_preliminary_information( $selected_demo['content'] );
		if ( is_wp_error( $data ) ) {
			return $data;
		}
		update_option( $this->theme_name . sanitize_key($this->current_id) . '_wxr_import_info', $data);
		$this->authors = $data->users;
		$this->version = $data->version;
		return $data;
	}

	protected function _start_import_stream(){

		@ini_set( 'output_buffering', 'off' );
		@ini_set( 'zlib.output_compression', false );

		if ( $GLOBALS['is_nginx'] ) {
			header( 'X-Accel-Buffering: no' );
			header( 'Content-Encoding: none' );
		}
		// Start the event stream.
		header( 'Content-Type: text/event-stream' );
		$demo_selected = $this->demo_data[ $this->current_id ];

		$settings = get_option('_wxr_import_settings');
		if ( empty( $settings ) ) {
			// Tell the browser to stop reconnecting.
			status_header( 204 );
			exit;
		}

		// 2KB padding for IE
		echo ':' . str_repeat(' ', 2048) . "\n\n";

		// Time to run the import!
		set_time_limit(0);

		// Ensure we're not buffered.
		wp_ob_end_flush_all();
		flush();

		$mapping = $settings['mapping'];

		$importer = $this->wxr_import;
		if ( ! empty( $mapping['mapping'] ) ) {
			$importer->set_user_mapping( $mapping['mapping'] );
		}
		if ( ! empty( $mapping['slug_overrides'] ) ) {
			$importer->set_user_slug_overrides( $mapping['slug_overrides'] );
		}
		// Are we allowed to create users?
		if ( ! $this->allow_create_users() ) {
			add_filter( 'wxr_importer.pre_process.user', '__return_null' );
		}

		// Keep track of our progress
		add_action( 'wxr_importer.pre_process.post_meta', array( $this, 'imported_post_meta' ), 10, 2 );
		add_action( 'wxr_importer.processed.post', array( $this, 'imported_post' ), 10, 2 );
		add_action( 'wxr_importer.process_failed.post', array( $this, 'imported_post' ), 10, 2 );
		add_action( 'wxr_importer.processed.comment', array( $this, 'imported_comment' ) );
		add_action( 'wxr_importer.processed.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.process_failed.term', array( $this, 'imported_term' ) );
		add_action( 'wxr_importer.processed.user', array( $this, 'imported_user' ) );
		add_action( 'wxr_importer.process_failed.user', array( $this, 'imported_user' ) );

		// Clean up some memory
		unset( $settings );

		// Flush once more.
		flush();
		if( $this->setting_args['content'] && !empty( $demo_selected['content'] ) ) {

			if(!isset($this->running_import_content)){
				$err = $importer->import( $demo_selected['content'] );
				// Let the browser know we're done.
				$complete = array(
					'action' => 'ImportingContent',
					'error' => false,
				);
				if ( is_wp_error( $err ) ) {
					$complete['error'] = $err->get_error_message();
				}
				$this->running_import_content = false;
				$this->emit_sse_message( $complete );
			}
		}
		if( $this->setting_args['slider'] && !empty( $demo_selected['slider'] ) ) {
			if(!isset($this->running_import_slider)){
				$this->handling_importer_slider( $demo_selected['slider'] );
				$this->running_import_slider = false;
			}
		}
		if( $this->setting_args['widget'] && !empty( $demo_selected['widget'] ) ) {
			if(!isset($this->running_import_widget)){
				$this->handling_importer_widgets( $demo_selected['widget'] );
				$this->running_import_widget = false;
			}
		}
		if( $this->setting_args['option'] && !empty( $demo_selected['option'] ) ) {
			if(!isset($this->running_import_option)){
				$this->handling_importer_option( $demo_selected['option'] );
				$this->running_import_option = false;
			}
		}
		if(!isset($this->running_import_theme_mode)){
			$this->handling_importer_theme_mode( $this->current_id );
			$this->running_import_theme_mode = false;
		}
		if(!isset($this->importer_is_running)){
			if( false !== has_action('LaStudio_Importer/after_import') ){
				do_action( 'LaStudio_Importer/after_import', $demo_selected, $this->current_id, $this );
			}
			$this->importer_is_running = false;
		}

		// Remove the settings to stop future reconnects.
		delete_option('_wxr_import_settings');
		unset($this->running_import_content);
		unset($this->running_import_slider);
		unset($this->running_import_widget);
		unset($this->running_import_option);
		unset($this->running_import_theme_mode);
		unset($this->importer_is_running);
		exit;

	}

	protected function _start_import_stream_without_content(){
		@ini_set( 'output_buffering', 'off' );
		@ini_set( 'zlib.output_compression', false );

		if ( $GLOBALS['is_nginx'] ) {
			header( 'X-Accel-Buffering: no' );
			header( 'Content-Encoding: none' );
		}
		header( 'Content-Type: text/event-stream' );
		$demo_selected = $this->demo_data[ $this->current_id ];
		$settings = get_option('_wxr_import_settings');
		if ( empty( $settings ) ) {
			status_header( 204 );
			exit;
		}

		// 2KB padding for IE
		echo ':' . str_repeat(' ', 2048) . "\n\n";

		// Time to run the import!
		set_time_limit(0);

		// Ensure we're not buffered.
		wp_ob_end_flush_all();
		flush();
		unset( $settings );
		flush();

		if( $this->setting_args['slider'] && !empty( $demo_selected['slider'] ) ) {
			if(!isset($this->running_import_slider)){
				$this->handling_importer_slider( $demo_selected['slider'] );
				$this->running_import_slider = false;
			}
		}
		if( $this->setting_args['widget'] && !empty( $demo_selected['widget'] ) ) {
			if(!isset($this->running_import_widget)){
				$this->handling_importer_widgets( $demo_selected['widget'] );
				$this->running_import_widget = false;
			}
		}
		if( $this->setting_args['option'] && !empty( $demo_selected['option'] ) ) {
			if(!isset($this->running_import_option)){
				$this->handling_importer_option( $demo_selected['option'] );
				$this->running_import_option = false;
			}
		}
		if(!isset($this->running_import_theme_mode)){
			$this->handling_importer_theme_mode( $this->current_id );
			$this->running_import_theme_mode = false;
		}
		if(!isset($this->importer_is_running)){
			if( false !== has_action('LaStudio_Importer/after_import') ){
				do_action( 'LaStudio_Importer/after_import', $demo_selected, $this->current_id, $this );
			}
			$this->importer_is_running = false;
		}

		// Remove the settings to stop future reconnects.
		delete_option('_wxr_import_settings');
		unset($this->running_import_slider);
		unset($this->running_import_widget);
		unset($this->running_import_option);
		unset($this->running_import_theme_mode);
		unset($this->importer_is_running);

		flush();
		exit;
	}

	/**
	 * Get the importer instance.
	 *
	 * @return WXR_Importer
	 */
	protected function get_importer() {
		$importer = new WXR_Importer( $this->get_import_options() );
		$importer->set_logger( $this->logger );
		return $importer;
	}

	/**
	 * Get options for the importer.
	 *
	 * @return array Options to pass to WXR_Importer::__construct
	 */
	protected function get_import_options() {
		$options = array(
			'fetch_attachments' => $this->fetch_attachments,
			'default_author'    => get_current_user_id(),
		);

		/**
		 * Filter the importer options used in the admin UI.
		 *
		 * @param array $options Options to pass to WXR_Importer::__construct
		 */
		return apply_filters( 'wxr_importer.admin.import_options', $options );
	}

	/**
	 * Decide whether or not the importer should attempt to download attachment files.
	 * Default is true, can be filtered via import_allow_fetch_attachments. The choice
	 * made at the import options screen must also be true, false here hides that checkbox.
	 *
	 * @return bool True if downloading attachments is allowed
	 */
	protected function allow_fetch_attachments() {
		return apply_filters( 'import_allow_fetch_attachments', true );
	}

	/**
	 * Decide whether or not the importer is allowed to create users.
	 * Default is true, can be filtered via import_allow_create_users
	 *
	 * @return bool True if creating users is allowed
	 */
	protected function allow_create_users() {
		return apply_filters( 'import_allow_create_users', true );
	}

	/**
	 * Get mapping data from request data.
	 *
	 * Parses form request data into an internally usable mapping format.
	 *
	 * @param array $args Raw (UNSLASHED) POST data to parse.
	 * @return array Map containing `mapping` and `slug_overrides` keys.
	 */
	protected function get_author_mapping( $args = array() ) {
		if ( ! isset( $args['imported_authors'] ) ) {
			return array(
				'mapping'        => array(),
				'slug_overrides' => array(),
			);
		}

		$map        = isset( $args['user_map'] ) ? (array) $args['user_map'] : array();
		$new_users  = isset( $args['user_new'] ) ? $args['user_new'] : array();
		$old_ids    = isset( $args['imported_author_ids'] ) ? (array) $args['imported_author_ids'] : array();

		// Store the actual map.
		$mapping = array();
		$slug_overrides = array();

		foreach ( (array) $args['imported_authors'] as $i => $old_login ) {
			$old_id = isset( $old_ids[$i] ) ? (int) $old_ids[$i] : false;

			if ( !empty( $map[$i] ) ) {
				$user = get_user_by( 'id', (int) $map[$i] );

				if ( isset( $user->ID ) ) {
					$mapping[] = array(
						'old_slug' => $old_login,
						'old_id'   => $old_id,
						'new_id'   => $user->ID,
					);
				}
			} elseif ( !empty( $new_users[ $i ] ) ) {
				if ( $new_users[ $i ] !== $old_login ) {
					$slug_overrides[ $old_login ] = $new_users[ $i ];
				}
			}
		}

		return compact( 'mapping', 'slug_overrides' );
	}

	/**
	 * Emit a Server-Sent Events message.
	 *
	 * @param mixed $data Data to be JSON-encoded and sent in the message.
	 */
	protected function emit_sse_message( $data ) {
		echo "event: message\n";
		echo 'data: ' . wp_json_encode( $data ) . "\n\n";

		// Extra padding.
		echo ':' . str_repeat(' ', 2048) . "\n\n";

		flush();
	}

	/**
	 *
	 * Modify _menu_item_url
	 *
	 * @param $meta_item
	 * @param $post_id
	 * @return mixed
	 */

	public function imported_post_meta( $meta_item, $post_id ) {

		if(empty($this->demo_site_url)){
			return $meta_item;
		}
		if(isset($meta_item['key']) && isset($meta_item['value']) && $meta_item['key'] == '_menu_item_url'){
			$meta_item['value'] = str_replace($this->demo_site_url, $this->current_site_url, $meta_item['value']);
		}

		return $meta_item;
	}

	/**
	 * Send message when a post has been imported.
	 *
	 * @param int $id Post ID.
	 * @param array $data Post data saved to the DB.
	 */
	public function imported_post( $id, $data ) {
		$this->emit_sse_message( array(
			'action' => 'updateDelta',
			'type'   => ( $data['post_type'] === 'attachment' ) ? 'media' : 'posts',
			'delta'  => 1,
		));
	}

	/**
	 * Send message when a comment has been imported.
	 */
	public function imported_comment() {
		$this->emit_sse_message( array(
			'action' => 'updateDelta',
			'type'   => 'comments',
			'delta'  => 1,
		));
	}

	/**
	 * Send message when a term has been imported.
	 */
	public function imported_term() {
		$this->emit_sse_message( array(
			'action' => 'updateDelta',
			'type'   => 'terms',
			'delta'  => 1,
		));
	}

	/**
	 * Send message when a user has been imported.
	 */
	public function imported_user() {
		$this->emit_sse_message( array(
			'action' => 'updateDelta',
			'type'   => 'users',
			'delta'  => 1,
		));
	}

	/**
	 * Get data from a file
	 *
	 * @param string $file_path file path where the content should be saved.
	 * @return string $data, content of the file or WP_Error object with error message.
	 */
	public static function data_from_file( $file_path ) {

		// Check if the file-system method is 'direct', if not display an error.
		if ( ! 'direct' === get_filesystem_method() ) {
			return self::return_direct_filesystem_error();
		}

		// Verify WP file-system credentials.
		$verified_credentials = self::check_wp_filesystem_credentials();

		if ( is_wp_error( $verified_credentials ) ) {
			return $verified_credentials;
		}

		// By this point, the $wp_filesystem global should be working, so let's use it to read a file.
		global $wp_filesystem;

		$data = $wp_filesystem->get_contents( $file_path );

		if ( ! $data ) {
			return new WP_Error(
				'failed_reading_file_from_server',
				sprintf(
					__( 'An error occurred while reading a file from your server! Tried reading file from path: %s%s.', 'la-studio' ),
					'<br>',
					$file_path
				)
			);
		}

		// Return the file data.
		return $data;
	}

	/**
	 * Helper function: return the "no direct access file-system" error.
	 *
	 * @return WP_Error
	 */
	private static function return_direct_filesystem_error() {
		return new WP_Error(
			'no_direct_file_access',
			sprintf(
				__( 'This WordPress page does not have %sdirect%s write file access. This plugin needs it in order to save the demo import xml file to the upload directory of your site. You can change this setting with these instructions: %s.', 'la-studio' ),
				'<strong>',
				'</strong>',
				'<a href="http://gregorcapuder.com/wordpress-how-to-set-direct-filesystem-method/" target="_blank">How to set <strong>direct</strong> filesystem method</a>'
			)
		);
	}

	/**
	 * Helper function: check for WP file-system credentials needed for reading and writing to a file.
	 *
	 * @return boolean|WP_Error
	 */
	private static function check_wp_filesystem_credentials() {

		// Get user credentials for WP file-system API.
		$demo_import_page_url = wp_nonce_url( 'themes.php?page=theme_options', 'theme_options' );
		$demo_import_page_url = '';

		if ( false === ( $creds = request_filesystem_credentials( $demo_import_page_url, '', false, false, null ) ) ) {
			return new WP_error(
				'filesystem_credentials_could_not_be_retrieved',
				__( 'An error occurred while retrieving reading/writing permissions to your server (could not retrieve WP filesystem credentials)!', 'la-studio' )
			);
		}

		// Now we have credentials, try to get the wp_filesystem running.
		if ( ! WP_Filesystem( $creds ) ) {
			return new WP_Error(
				'wrong_login_credentials',
				__( 'Your WordPress login credentials don\'t allow to use WP_Filesystem!', 'la-studio' )
			);
		}

		return true;
	}

	/**
	 * Imports widgets from a json file.
	 *
	 * @param string $data_file path to json file with WordPress widget export data.
	 */
	private function handling_importer_widgets( $file ) {

		$response = array(
			'action' => 'ImportingWidget',
			'error'  => __( 'Widget import file could not be found.', 'la-studio' )
		);

		if( empty($file) || !file_exists($file) ) {
			$this->emit_sse_message( $response );
			return;
		}

		$data = self::data_from_file( $file );

		if ( is_wp_error( $data ) ) {
			$this->emit_sse_message( $response );
			return;
		}
		$data = json_decode( $data );
		// Import the widget data and save the results.
		$result = $this->import_widget_data( $data );
		if ( is_wp_error( $result ) ) {
			$response['error'] = $result->get_error_message();
		}else{
			$this->logger->info(__('Widget has been importer !', 'la-studio'));
			$response['error'] = __( 'Widget has been importer !', 'la-studio');
		}
		$this->emit_sse_message( $response );
		return;

	}
	/**
	 * Import widget JSON data
	 *
	 * @global array $wp_registered_sidebars
	 * @param object $data JSON widget data.
	 * @return array $results
	 */
	private function import_widget_data( $data ) {
		global $wp_registered_sidebars;
		// Have valid data? If no data or could not decode.
		if ( empty( $data ) || ! is_object( $data ) ) {
			return new WP_Error(
				'corrupted_widget_import_data',
				__( 'Widget import data could not be read. Please try a different file.', 'la-studio' )
			);
		}
		// Hook before import.
		do_action( 'LaStudio_Importer/widgets/before_import' );
		$data = apply_filters( 'LaStudio_Importer/widgets/before_import_data', $data );
		// Get all available widgets site supports.
		$available_widgets = $this->available_widgets();
		// Get all existing widget instances.
		$widget_instances = array();
		foreach ( $available_widgets as $widget_data ) {
			$widget_instances[ $widget_data['id_base'] ] = get_option( 'widget_' . $widget_data['id_base'] );
		}
		// Begin results.
		$results = array();

		// Loop import data's sidebars.
		foreach ( $data as $sidebar_id => $widgets ) {
			// Skip inactive widgets (should not be in export file).
			if ( 'wp_inactive_widgets' == $sidebar_id ) {
				continue;
			}
			// Check if sidebar is available on this site. Otherwise add widgets to inactive, and say so.
			if ( isset( $wp_registered_sidebars[ $sidebar_id ] ) ) {
				$sidebar_available    = true;
				$use_sidebar_id       = $sidebar_id;
				$sidebar_message_type = 'success';
				$sidebar_message      = '';
			}
			else {
				$sidebar_available    = false;
				$use_sidebar_id       = 'wp_inactive_widgets'; // Add to inactive if sidebar does not exist in theme.
				$sidebar_message_type = 'error';
				$sidebar_message      = __( 'Sidebar does not exist in theme (moving widget to Inactive)', 'la-studio' );
			}
			// Result for sidebar.
			$results[ $sidebar_id ]['name']         = ! empty( $wp_registered_sidebars[ $sidebar_id ]['name'] ) ? $wp_registered_sidebars[ $sidebar_id ]['name'] : $sidebar_id; // Sidebar name if theme supports it; otherwise ID.
			$results[ $sidebar_id ]['message_type'] = $sidebar_message_type;
			$results[ $sidebar_id ]['message']      = $sidebar_message;
			$results[ $sidebar_id ]['widgets']      = array();
			// Loop widgets.
			foreach ( $widgets as $widget_instance_id => $widget ) {
				$fail = false;
				// Get id_base (remove -# from end) and instance ID number.
				$id_base            = preg_replace( '/-[0-9]+$/', '', $widget_instance_id );
				$instance_id_number = str_replace( $id_base . '-', '', $widget_instance_id );

				// Does site support this widget?
				if ( ! $fail && ! isset( $available_widgets[ $id_base ] ) ) {
					$fail                = true;
					$widget_message_type = 'error';
					$widget_message      = __( 'Site does not support widget', 'la-studio' ); // Explain why widget not imported.
				}

				// Filter to modify settings object before conversion to array and import.
				// Leave this filter here for backwards compatibility with manipulating objects (before conversion to array below).
				// Ideally the newer wie_widget_settings_array below will be used instead of this.
				$widget = apply_filters( 'LaStudio_Importer/widgets/widget_setting_object', $widget ); // Object.

				// Convert multidimensional objects to multidimensional arrays.
				// Some plugins like Jetpack Widget Visibility store settings as multidimensional arrays.
				// Without this, they are imported as objects and cause fatal error on Widgets page.
				// If this creates problems for plugins that do actually intend settings in objects then may need to consider other approach: https://wordpress.org/support/topic/problem-with-array-of-arrays.
				// It is probably much more likely that arrays are used than objects, however.
				$widget = json_decode( json_encode( $widget ), true );

				// Filter to modify settings array.
				// This is preferred over the older wie_widget_settings filter above.
				// Do before identical check because changes may make it identical to end result (such as URL replacements).
				$widget = apply_filters( 'LaStudio_Importer/widgets/widget_setting_array', $widget );

				// Does widget with identical settings already exist in same sidebar?
				if ( ! $fail && isset( $widget_instances[ $id_base ] ) ) {

					// Get existing widgets in this sidebar.
					$sidebars_widgets = get_option( 'sidebars_widgets' );
					$sidebar_widgets  = isset( $sidebars_widgets[ $use_sidebar_id ] ) ? $sidebars_widgets[ $use_sidebar_id ] : array(); // Check Inactive if that's where will go.

					// Loop widgets with ID base.
					$single_widget_instances = ! empty( $widget_instances[ $id_base ] ) ? $widget_instances[ $id_base ] : array();
					foreach ( $single_widget_instances as $check_id => $check_widget ) {

						// Is widget in same sidebar and has identical settings?
						if ( in_array( "$id_base-$check_id", $sidebar_widgets ) && (array) $widget == $check_widget ) {
							$fail                = true;
							$widget_message_type = 'warning';
							$widget_message      = __( 'Widget already exists', 'la-studio' ); // Explain why widget not imported.

							break;
						}
					}
				}
				// No failure.
				if ( ! $fail ) {
					// Add widget instance.
					$single_widget_instances   = get_option( 'widget_' . $id_base ); // All instances for that widget ID base, get fresh every time.
					$single_widget_instances   = ! empty( $single_widget_instances ) ? $single_widget_instances : array( '_multiwidget' => 1 ); // Start fresh if have to.
					$single_widget_instances[] = $widget; // Add it.
					// Get the key it was given.
					end( $single_widget_instances );
					$new_instance_id_number = key( $single_widget_instances );
					// If key is 0, make it 1.
					// When 0, an issue can occur where adding a widget causes data from other widget to load, and the widget doesn't stick (reload wipes it).
					if ( '0' === strval( $new_instance_id_number ) ) {
						$new_instance_id_number                           = 1;
						$single_widget_instances[ $new_instance_id_number ] = $single_widget_instances[0];
						unset( $single_widget_instances[0] );
					}
					// Move _multiwidget to end of array for uniformity.
					if ( isset( $single_widget_instances['_multiwidget'] ) ) {
						$multiwidget = $single_widget_instances['_multiwidget'];
						unset( $single_widget_instances['_multiwidget'] );
						$single_widget_instances['_multiwidget'] = $multiwidget;
					}
					// Update option with new widget.
					update_option( 'widget_' . $id_base, $single_widget_instances );
					// Assign widget instance to sidebar.
					$sidebars_widgets = get_option( 'sidebars_widgets' ); // Which sidebars have which widgets, get fresh every time.
					$new_instance_id = $id_base . '-' . $new_instance_id_number; // Use ID number from new widget instance.
					$sidebars_widgets[ $use_sidebar_id ][] = $new_instance_id; // Add new instance to sidebar.
					update_option( 'sidebars_widgets', $sidebars_widgets ); // Save the amended data.
					// After widget import action.
					$after_widget_import = array(
						'sidebar'           => $use_sidebar_id,
						'sidebar_old'       => $sidebar_id,
						'widget'            => $widget,
						'widget_type'       => $id_base,
						'widget_id'         => $new_instance_id,
						'widget_id_old'     => $widget_instance_id,
						'widget_id_num'     => $new_instance_id_number,
						'widget_id_num_old' => $instance_id_number,
					);
					do_action( 'LaStudio_Importer/widgets/after_single_widget_import', $after_widget_import );

					// Success message.
					if ( $sidebar_available ) {
						$widget_message_type = 'success';
						$widget_message      = __( 'Imported', 'la-studio' );
					}
					else {
						$widget_message_type = 'warning';
						$widget_message      = __( 'Imported to Inactive', 'la-studio' );
					}
				}

				// Result for widget instance.
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['name']         = isset( $available_widgets[ $id_base ]['name'] ) ? $available_widgets[ $id_base ]['name'] : $id_base; // Widget name or ID if name not available (not supported by site).
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['title']        = ! empty( $widget['title'] ) ? $widget['title'] : __( '', 'la-studio' ); // Show "No Title" if widget instance is untitled.
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message_type'] = $widget_message_type;
				$results[ $sidebar_id ]['widgets'][ $widget_instance_id ]['message']      = $widget_message;

			}
		}

		// Hook after import.
		do_action( 'LaStudio_Importer/widgets/after_import' );

		// Return results.
		return apply_filters( 'LaStudio_Importer/widgets/import_results', $results );
	}

	/**
	 * Available widgets.
	 *
	 * Gather site's widgets into array with ID base, name, etc.
	 *
	 * @global array $wp_registered_widget_controls
	 * @return array $available_widgets, Widget information
	 */
	private function available_widgets() {
		global $wp_registered_widget_controls;
		$widget_controls   = $wp_registered_widget_controls;
		$available_widgets = array();

		foreach ( $widget_controls as $widget ) {
			if ( ! empty( $widget['id_base'] ) && ! isset( $available_widgets[ $widget['id_base'] ] ) ) {
				$available_widgets[ $widget['id_base'] ]['id_base'] = $widget['id_base'];
				$available_widgets[ $widget['id_base'] ]['name']    = $widget['name'];
			}
		}
		return apply_filters( 'LaStudio_Importer/widgets/available_widgets', $available_widgets );
	}

	private function handling_importer_slider( $file ) {

		if ( !empty( $file ) && file_exists( $file ) ) {
			if( class_exists('RevSlider') ) {
				$slider = new RevSlider();
				$result = $slider->importSliderFromPost( true, true, $file );
				if( is_wp_error( $result ) ) {
					$response['error'] = $result->get_error_message();
					$this->logger->error(
						sprintf(__('ImportingSlider %s', 'la-studio'), $result->get_error_message())
					);
				}else{
					$this->logger->info(
						__('Slider has been imported !', 'la-studio')
					);
				}
			}
		}

	}

	private function handling_importer_option( $file ) {
		if( empty( $file ) || !file_exists( $file ) ) {
			$this->emit_sse_message( array(
				'action' => 'ImportingOption',
				'error'  => __( 'Access define!', 'la-studio' )
			) );
			return;
		}

		$data = self::data_from_file( $file );
		if( is_wp_error( $data ) ) {
			$this->logger->error(
				__('Option config not found!', 'la-studio')
			);
			return;
		}

		$data = json_decode( $data, true );
		$data = maybe_unserialize( $data );

		if( empty( $data ) || !is_array( $data ) ) {
			$this->emit_sse_message( array(
				'action' => 'ImportingOption',
				'error'  => __( 'Options is null', 'la-studio')
			) );
			return;
		}

        $data['la_extension_available'] = array(
            'swatches' => true,
            '360' => true,
            'content_type' => true
        );

        $default_modules = [
            'advanced-carousel' => true,
            'advanced-map' => true,
            'animated-box' => true,
            'animated-text' => true,
            'audio' => true,
            'banner' => true,
            'brands' => true,
            'button' => true,
            'circle-progress' => true,
            'countdown-timer' => true,
            'dropbar'  => true,
            'headline' => true,
            'horizontal-timeline' => true,
            'image-comparison' => true,
            'images-layout' => true,
            'inline-svg' => true,
            'instagram-gallery' => true,
            'portfolio' => true,
            'posts' => true,
            'price-list' => true,
            'pricing-table' => true,
            'progress-bar' => true,
            'scroll-navigation' => true,
            'services' => true,
            'slider' => true,
            'subscribe-form' => true,
            'table' => true,
            'team-member' => true,
            'testimonials' => true,
            'timeline' => true,
            'video' => true
        ];

        $data['lastudio_elementor_modules'] = $default_modules;

		update_option( $this->theme_name . '_options', $data );
		$this->logger->info(
			__('Theme setting has been set', 'la-studio')
		);
		return;

	}

	private function handling_importer_theme_mode( $demo_id ) {

		$demo_data = $this->demo_data[ $demo_id ];
		$menu_locations = array();
		$menu_array = isset($demo_data['menu-locations']) ? $demo_data['menu-locations'] : array();
		if(!empty($menu_array)){
			foreach ($menu_array as $key => $menu){
				$menu_object = get_term_by( 'name', esc_attr($menu), 'nav_menu' );
				$menu_locations[$key] = isset($menu_object->term_id) ? $menu_object->term_id : '';
			}
		}
		if(!empty($menu_locations)){
			set_theme_mod( 'nav_menu_locations', $menu_locations);
			$this->logger->info(
				__('Menu Location has been set', 'la-studio')
			);
		}
		$pages = array();
		$page_array = isset($demo_data['pages']) ? $demo_data['pages'] : array();
		if(!empty($page_array)){
			foreach($page_array as $key => $title){
				$page = get_page_by_title( $title );
				if ( isset( $page->ID ) ) {
					update_option( $key, $page->ID );
					$pages[] = $page->ID;
				}
			}
		}
		if(!empty($pages)){
			update_option( 'show_on_front', 'page' );
			$this->logger->info(
				__('Home Page and Blog Page has been set!', 'la-studio')
			);
		}

		$options_name = $this->theme_name . '_imported_demos';
		$imported_demos = get_option( $options_name );
		if ( empty( $imported_demos ) ) {
			$imported_demos = array(
				$demo_id => $demo_data
			);
		}else {
			$imported_demos[$demo_id] = $demo_data;
		}
		$imported_demos['active_import'] = $demo_id;
		update_option( $options_name, $imported_demos );

		if(!empty($demo_data['other_setting'])){
			$other_setting = $demo_data['other_setting'];
			foreach( $other_setting as $k => $v){
				update_option( $k, $v );
			}
			$this->logger->info(
				__('Import other setting require success', 'la-studio')
			);
		}

		$this->logger->info(
			__('Active demo success !', 'la-studio')
		);
		$this->emit_sse_message(
			array(
				'action' => 'complete',
				'error'	 => false
			)
		);
	}

	public function modify_post_author_when_using_ajax($data, $meta, $comments, $terms){
		$data['post_author'] = (int) get_current_user_id();
		return $data;
	}

	public function clear_imported_data(){

		$options_name = $this->theme_name . '_imported_demos';

		if( isset($_GET['la_delete_demo_status']) && $_GET['la_delete_demo_status'] == 'yes'){
			delete_option($options_name);
		}

	}

}