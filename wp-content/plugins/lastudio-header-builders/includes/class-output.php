<?php
/**
 * Header Builder - Header Output Class.
 *
 * @author  LaStudio
 */

// don't load directly.
if ( ! defined( 'ABSPATH' ) ) {
    header('Status: 403 Forbidden');
    header('HTTP/1.1 403 Forbidden');
    exit;
}

if ( ! class_exists( 'LAHB_Output' ) ) :

    class LAHB_Output {

		/**
		 * Instance of this class.
         *
		 * @since	1.0.0
		 * @access	private
		 * @var		LAHB_Output
		 */
		private static $instance;

		/**
		 * Provides access to a single instance of a module using the singleton pattern.
		 *
		 * @since	1.0.0
		 * @return	object
		 */
		public static function get_instance() {

			if ( self::$instance === null ) {
				self::$instance = new self();
            }

			return self::$instance;

		}
		
		private static $dynamic_style = '';

		/**
		 * Constructor.
		 *
		 * @since	1.0.0
		 */
		public function __construct() {

		}
		
		public static function set_dynamic_styles( $styles, $preset_name = '' ){
		    self::$dynamic_style .= $styles;
        }

		/**
		 * Output header.
		 *
		 * @since	1.0.0
		 */
        public static function output($is_frontend_builder = false, $lahb_data = array(), $preset_name = '', $include_html_tag = true) {

            $is_frontend_builder = $is_frontend_builder ? $is_frontend_builder : LAHB_Helper::is_frontend_builder();

            $header_show = '';
            
            // header visibility
            if ( $header_show === '1') {
                $header_show = true;
            }
            elseif ( $header_show === '0' ) {
                $header_show = false;
            }
            elseif ( $header_show === false || empty( $header_show ) ) {
                $header_show = true;
            }

            if ( ! ( $is_frontend_builder || $header_show ) ) {
                return;
            }

            $lahb_data = $lahb_data ? $lahb_data :  LAHB_Helper::get_data_frontend_components();

            $prepare_data = LAHB_Helper::convertOldHeaderData( $lahb_data );

            $header_components = LAHB_Helper::get_only_components_from_settings($prepare_data);
            $panels_settings = LAHB_Helper::get_only_panels_from_settings($prepare_data);

            /**
             * What we need to do now is
             * 1) Render all the components - this will save more time
             * 2) Then we need render panel to match with screen view
             */
            $registered_components = LAHB_Helper::get_elements();
            $components_has_run = array();

            // Start render header output
            $class_frontend_builder = $is_frontend_builder ? ' lahb-frontend-builder' : '';
            if($include_html_tag){
                $output = '<header id="lastudio-header-builder" class="lahb-wrap' . esc_attr( $class_frontend_builder ) . '">';
            }
            else{
                $output = '';
            }

                $output .= '<div class="lahbhouter">';
                    $output .= '<div class="lahbhinner">';
                        $output .= '<div class="main-slide-toggle"></div>';

                        if(!empty($panels_settings)){

                            /**
                             * We need to check header type vertical first !!
                             * if this is vertical type ==> remove others areas on desktop-view except 'row1'
                             */
                            $__detect_header_type = '';
                            if(isset($panels_settings['desktop-view']['row1']['settings']['header_type'])){
                                $__detect_header_type = $panels_settings['desktop-view']['row1']['settings']['header_type'];
                            }

                            // Screen
                            foreach ( $panels_settings as $screen_view_index => $screen_view ) {
                                $output .= '<div class="lahb-screen-view lahb-' . esc_attr( $screen_view_index  ) . '">';

                                $vertical_header = '';

                                // Rows
                                foreach ( $screen_view as $row_index => $rows ) {
                                    if($screen_view_index == 'desktop-view' && $__detect_header_type == 'vertical'){
                                        if($row_index != 'row1' && $row_index != 'topbar'){
                                            continue;
                                        }
                                    }

                                    // check visibility
                                    $hidden_area = $rows['settings']['hidden_element'];
                                    if ( $hidden_area === 'false' ) {
                                        $hidden_area = false;
                                    }
                                    elseif ( $hidden_area === 'true' ) {
                                        $hidden_area = true;
                                    }

                                    // check vertical header
                                    if ( $screen_view_index == 'desktop-view' ) {
                                        $header_type = !empty($rows['settings']['header_type']) ? $rows['settings']['header_type'] : '';
                                        if ($row_index != 'row1') {
                                            if ($header_type == 'vertical'){
                                                continue;
                                            }
                                        }
                                        else {
                                            if ($header_type == 'vertical') {
                                                $vertical_header = ' lahb-vertical lahb-vcom';
                                            }
                                        }
                                    }

                                    // start render area
                                    if ( ! $hidden_area ) {
                                        $area_settings      = isset( $rows['settings'] ) ? $rows['settings'] : '';
                                        $areas              = array();
                                        $areas['left']      = isset( $rows['left'] ) ? $rows['left'] : '';
                                        $areas['center']    = isset( $rows['center'] ) ? $rows['center'] : '';
                                        $areas['right']     = isset( $rows['right'] ) ? $rows['right'] : '';

                                        $full_container = $container_padd = $content_position = $extra_class = $extra_id = '';
                                        if(isset($area_settings['uniqueId']) && isset($header_components[$area_settings['uniqueId']])){
                                            $area_settings = LAHB_Helper::component_atts( $area_settings, $header_components[ $area_settings['uniqueId'] ] );
                                        }
                                        extract( LAHB_Helper::component_atts( array(
                                            'full_container'	=> 'false',
                                            'container_padd'	=> 'true',
                                            'content_position'	=> 'middle',
                                            'extra_class'   	=> '',
                                            'extra_id'      	=> ''
                                        ), $area_settings ));

                                        // once fire

                                        $is_header_vertical = false;

                                        if ( $header_type == 'vertical' && $screen_view_index == 'desktop-view' ) {

                                            if ($header_type == 'vertical') {

                                                $is_header_vertical = true;

                                                $vertical_toggle = $vertical_toggle_icon = $logo = '';

                                                extract(LAHB_Helper::component_atts(array(
                                                    'vertical_toggle' => 'false',
                                                    'vertical_toggle_icon' => 'lastudioicon-menu-7',
                                                    'logo' => ''
                                                ), $area_settings));

                                                $area_settings['area_screen_index'] = $screen_view_index;
                                                $area_settings['area_row_index'] = $row_index;
                                                $area_settings['area_vertical'] = true;


                                                // Render Custom Style
                                                $vertical_dynamic_style = lahb_styling_tab_output($area_settings, 'logo', '#lastudio-header-builder .lahb-vertical-logo-wrap');
                                                $vertical_dynamic_style .= lahb_styling_tab_output($area_settings, 'toggle_bar', '#lastudio-header-builder .lahb-vertical-toggle-wrap');
                                                $vertical_dynamic_style .= lahb_styling_tab_output($area_settings, 'toggle_icon_box', '#lastudio-header-builder .vertical-toggle-icon', '#lastudio-header-builder .vertical-toggle-icon:hover');
                                                $vertical_dynamic_style .= lahb_styling_tab_output($area_settings, 'box', '#lastudio-header-builder.lahb-wrap .lahb-vertical');

                                                if (!empty($vertical_dynamic_style)) {
                                                    self::set_dynamic_styles('@media (min-width: 1280px) { ' . $vertical_dynamic_style . ' } ', $preset_name);
                                                }
                                            }

                                            if ($vertical_toggle == 'true') {
                                                $logo = $logo ? lahb_wp_get_attachment_url($logo) : '';
                                                // Render Toggle Wrap
                                                $output .= '<div class="lahb-vcom lahb-vertical-toggle-wrap">';
                                                if (!empty($logo)) {
                                                    $output .= sprintf(
                                                        '<div class="lahb-vertical-logo-wrap"><a href="%s"><img class="lahb-vertical-logo" src="%s" alt="%s"></a></div>',
                                                        esc_url(home_url('/')),
                                                        esc_url($logo),
                                                        get_bloginfo('name')
                                                    );
                                                }
                                                $output .= '<a href="#" class="vertical-toggle-icon"><i class="' . lahb_rename_icon($vertical_toggle_icon) . '" ></i></a>';

                                                $toggle_bar_rows = isset($panels_settings['desktop-view']['row2']) ? $panels_settings['desktop-view']['row2'] : [];
                                                if( !empty($toggle_bar_rows) && isset($toggle_bar_rows['settings']['hidden_element']) && !filter_var($toggle_bar_rows['settings']['hidden_element'], FILTER_VALIDATE_BOOLEAN)){

                                                    $output .= '<div class="lahb-vertical--extras">';
                                                    foreach ($toggle_bar_rows as $t_key => $t_col){
                                                        if($t_key == 'settings'){
                                                            continue;
                                                        }
                                                        if(!empty($t_col)){
                                                            foreach ($t_col as $t_el_index => $t_el){
                                                                if ($t_el_index === 'settings') {
                                                                    continue;
                                                                }
                                                                $t_hidden_el = $t_el['hidden_element'];
                                                                if (!$t_hidden_el) {
                                                                    $t_uniqid = $t_el['uniqueId'];
                                                                    $t_component_name = $t_el['name'];

                                                                    $once_run_flag = false;
                                                                    //make component as loaded
                                                                    if(!in_array($t_uniqid, $components_has_run)){
                                                                        $components_has_run[] = $t_uniqid;
                                                                        $once_run_flag = true;
                                                                    }

                                                                    if(isset($registered_components[$t_component_name])){
                                                                        $t_func_name_comp = $registered_components[$t_component_name];
                                                                        $output .= call_user_func( $t_func_name_comp, $header_components[$t_uniqid], $t_uniqid, $once_run_flag );
                                                                    }

                                                                }
                                                            }
                                                        }
                                                    }
                                                    $output .= '</div>';
                                                }

                                                $output .= '</div>';

                                            }

                                        }

                                        // height
                                        if ( ! empty( $area_height ) ) {
                                            $area_height = ! empty( $area_height ) ? $area_height : '';
                                            $area_height = 'height: ' . LAHB_Helper::css_sanatize( $area_height ) . ';';
                                            self::set_dynamic_styles( '#lastudio-header-builder .lahb-'.$screen_view_index.' .lahb-' . $row_index . '-area { ' . $area_height . ' }', $preset_name);
                                        }

                                        $dynamic_style = '';

                                        if(!$is_header_vertical){

                                            $dynamic_style .= lahb_styling_tab_output( $area_settings, 'typography', '.lahb-wrap .lahb-'.$screen_view_index.' .lahb-' . $row_index . '-area' );
                                            $dynamic_style .= lahb_styling_tab_output( $area_settings, 'background', '.lahb-wrap .lahb-'.$screen_view_index.' .lahb-' . $row_index . '-area' );
                                            $dynamic_style .= lahb_styling_tab_output( $area_settings, 'box', '.lahb-wrap .lahb-'.$screen_view_index.' .lahb-' . $row_index . '-area' );

                                            $dynamic_style .= lahb_styling_tab_output( $area_settings, 'transparency_background', '.enable-header-transparency .lahb-wrap:not(.is-sticky) .lahb-'.$screen_view_index.' .lahb-' . $row_index . '-area' );
                                            $dynamic_style .= lahb_styling_tab_output( $area_settings, 'transparency_text_color', '.enable-header-transparency .lahb-wrap:not(.is-sticky) .lahb-'.$screen_view_index.' .lahb-' . $row_index . '-area .lahb-element, .enable-header-transparency .lahb-wrap:not(.is-sticky) .lahb-'.$screen_view_index.' .lahb-' . $row_index . '-area .lahb-search .search-field' );
                                            $dynamic_style .= lahb_styling_tab_output( $area_settings, 'transparency_link_color', '.enable-header-transparency .lahb-wrap:not(.is-sticky) .lahb-'.$screen_view_index.' .lahb-' . $row_index . '-area .lahb-element:not(.lahb-nav-wrap) a' );
                                        }

                                        // width
                                        if ( ! empty( $area_width ) ) {
                                            $area_width = 'width: ' . LAHB_Helper::css_sanatize( $area_width ) . ';';
                                            self::set_dynamic_styles( '@media (min-width: 1280px) { .lahb-wrap .lahb-'.$screen_view_index.' .lahb-' . $row_index . '-area > .container { ' . $area_width . ' } }', $preset_name);
                                        }

                                        if ( !empty($dynamic_style) ) {
                                            self::set_dynamic_styles( $dynamic_style, $preset_name );
                                        }

                                        // Classes
                                        $area_classes   = '';
                                        $area_classes   .= ! empty($content_position) ? ' lahb-content-' . $content_position : '' ;
                                        $area_classes   .= ! empty($extra_class) ? ' ' . $extra_class : '' ;
                                        $container_padd = $container_padd == 'true' ? '' : ' la-no-padding';
                                        if( $full_container != 'false' ) {
                                            $container_padd .= ' la-container-full';
                                        }
                                        // Id
                                        $extra_id = ! empty( $extra_id ) ? ' id="' . esc_attr( $extra_id ) . '"' : '' ;

                                        // Toggle vertical
                                        if($screen_view_index == 'mobiles-view'){
                                            $row_layout = ' lahb-area__' . ( !empty($area_settings['row_layoutrow_layout_xs']) ? $area_settings['row_layoutrow_layout_xs'] : 'auto' );
                                        }
                                        elseif ($screen_view_index == 'tablets-view'){
                                            $row_layout = ' lahb-area__' . ( !empty($area_settings['row_layoutrow_layout_sm']) ? $area_settings['row_layoutrow_layout_sm'] : 'auto' );
                                        }
                                        else{
                                            $row_layout = ' lahb-area__' . ( !empty($area_settings['row_layoutrow_layout_md']) ? $area_settings['row_layoutrow_layout_md'] : 'auto' );
                                        }

                                        $output .= '<div class="lahb-area lahb-' . $row_index . '-area' . $vertical_header . $area_classes . $row_layout . '"' . $extra_id . '>';

                                        if(!$is_header_vertical){
                                            $output .= '<div class="container' . $container_padd . '">';
                                        }

                                        $output .= '<div class="lahb-content-wrap'. esc_attr($row_layout) .'">';

                                        // Columns
                                        foreach ( $areas as $area_key => $components ) {
                                            $output .= '<div class="lahb-col lahb-col__' . esc_attr($area_key) . '">';
                                            if ($components) {
                                                foreach ($components as $component_index => $component) {
                                                    if ($component_index === 'settings') {
                                                        continue;
                                                    }
                                                    $hidden_el = $component['hidden_element'];
                                                    if (!$hidden_el) {
                                                        $uniqid = $component['uniqueId'];
                                                        $component_name = $component['name'];

                                                        $once_run_flag = false;
                                                        //make component as loaded
                                                        if(!in_array($uniqid, $components_has_run)){
                                                            $components_has_run[] = $uniqid;
                                                            $once_run_flag = true;
                                                        }

                                                        if(isset($registered_components[$component_name])){
                                                            $func_name_comp = $registered_components[$component_name];
                                                            $output .= call_user_func( $func_name_comp, $header_components[$uniqid], $uniqid, $once_run_flag );
                                                        }

                                                    }

                                                } // end components loop
                                            }
                                            $output .= '</div>';

                                        } // end areas loop

                                        $output .= '</div><!-- .lahb-content-wrap -->';

                                        if(!$is_header_vertical) {
                                            $output .= '</div><!-- .container -->';
                                        }

                                        $output .= '</div><!-- .lahb-area -->';
                                    }
                                }
                                $output .= '</div>';
                            }
                        }

                    $output .= '</div>';
                $output .= '</div>';
                $output .= '<div class="lahb-wrap-sticky-height"></div>';

            if($include_html_tag) {
                $output .= '</header>';
            }

            if(!LAHB_Helper::is_prebuild_header_exists($preset_name)){
                $preset_name = '';
            }

            self::set_dynamic_styles(LAHB_Helper::get_styles());

            self::save_dynamic_styles($preset_name);

            if( $is_frontend_builder || ( isset($_GET['lastudio_header_builder']) && $_GET['lastudio_header_builder'] == 'inline_mode' ) ) {
                $output .= sprintf('<style id="lahb-frontend-styles-inline-css">%s</style>', LAHB_Helper::get_dynamic_styles($preset_name));
            }

            $script_fix = '';

            if( !$is_frontend_builder && !isset($_GET['lastudio_header_builder'])){
                $script_fix = '<script>';
                $script_fix .= 'var LaStudioHeaderBuilderHTMLDivCSS = unescape("'. rawurlencode(stripslashes(self::compress_css(self::$dynamic_style))) .'");';
                $script_fix .= 'var LaStudioHeaderBuilderHTMLDiv = document.getElementById("lahb-frontend-styles-inline-css");';
                $script_fix .= 'if(LaStudioHeaderBuilderHTMLDiv) { LaStudioHeaderBuilderHTMLDiv.innerHTML = LaStudioHeaderBuilderHTMLDivCSS; } else{ var LaStudioHeaderBuilderHTMLDiv = document.createElement("div"); LaStudioHeaderBuilderHTMLDiv.innerHTML = "<style>" + LaStudioHeaderBuilderHTMLDivCSS + "</style>"; document.getElementsByTagName("head")[0].appendChild(LaStudioHeaderBuilderHTMLDiv.childNodes[0]);}';
                $script_fix .= '</script>';
            }

            return $script_fix . $output;
        }


        public static function compress_css($css){

            // Return if no CSS
            if ( ! $css ) return;

            // Normalize whitespace
            $css = preg_replace( '/\s+/', ' ', $css );

            // Remove ; before }
            $css = preg_replace( '/;(?=\s*})/', '', $css );

            // Remove space after , : ; { } */ >
            $css = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $css );

            // Remove space before , ; { }
            $css = preg_replace( '/ (,|;|\{|})/', '$1', $css );

            // Strips leading 0 on decimal values (converts 0.5px into .5px)
            $css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css );

            // Strips units if value is 0 (converts 0px to 0)
            $css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css );

            // Trim
            $css = trim( $css );

            // Return minified CSS
            return $css;
        }


        public static function save_dynamic_styles( $preset_name = '' ){
            $session_key = 'lahb_dynamic_style';
            if(!empty($preset_name)){
                $session_key = 'lahb_dynamic_style_' . $preset_name;
            }
            $_SESSION[$session_key] = self::$dynamic_style;
        }

    }

endif;
