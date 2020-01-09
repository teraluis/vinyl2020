<?php

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

class Skudmart_Options {

    public static $color_default = array();

    public function __construct() {

        self::$color_default = array(
            'text_color' => '#535353',
            'body_color' => '#535353',
            'heading_color' => '#2f2f2f',
            'primary_color' => '#F53E6A',
            'secondary_color' => '#2f2f2f',
            'three_color' => '#A3A3A3',
            'border_color' => '#e8e8e8'
        );

    }

    public static function get_color_default( $key ){
        if(!empty($key)){
            if(!empty(self::$color_default) && array_key_exists( $key, self::$color_default )){
                return self::$color_default[$key];
            }
        }
        return '';
    }

    public static function get_config_main_layout_opts( $image_select = true, $inherit = false ){
        $options =  array(
            'col-1c'    => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/col-1c.png')    : esc_attr_x('1 column', 'admin-view', 'skudmart'),
            'col-2cl'   => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/col-2cl.png')   : esc_attr_x('2 columns left (3-9)', 'admin-view', 'skudmart'),
            'col-2cr'   => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/col-2cr.png')   : esc_attr_x('2 columns right (9-3)', 'admin-view', 'skudmart'),
            'col-2cl-l'   => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/col-2cl-l.png')   : esc_attr_x('2 columns left (4-8)', 'admin-view', 'skudmart'),
            'col-2cr-l'   => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/col-2cr-l.png')   : esc_attr_x('2 columns right (8-4)', 'admin-view', 'skudmart')
        );
        if($inherit){
            $inherit = array(
                'inherit' => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/inherit.png') : esc_attr_x('Inherit','admin-view', 'skudmart')
            );
            $options = $inherit + $options;
        }
        return $options;
    }

    public static function get_config_header_layout_opts( $image_select = true, $inherit = false ){
		
        $options =  array(
            '1'     => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/header-1.jpg')            : esc_attr_x('Header Layout 01 ( Logo + Menu + Access Icon )', 'admin-view', 'skudmart')
        );

        if(class_exists('LAHB_Helper')){
            $options = LAHB_Helper::get_all_prebuild_header_for_dropdown();
        }

        if($inherit){
            $inherit = array(
                'inherit' => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/header-inherit.png') : esc_attr_x('Inherit','admin-view', 'skudmart')
            );
            $options = $inherit + $options;
        }
        return $options;
    }

    public static function get_config_radio_opts( $inherit = true ){
        $options = array();
        if($inherit){
            $options['inherit'] = esc_html_x('Inherit', 'admin-view', 'skudmart');
        }
        $options['yes'] = esc_html_x('Yes', 'admin-view', 'skudmart');
        $options['no'] = esc_html_x('No', 'admin-view', 'skudmart');
        return $options;
    }

    public static function get_config_radio_onoff( $inherit = true ){
        $options = array();
        if($inherit){
            $options['inherit'] = esc_html_x('Inherit', 'admin-view', 'skudmart');
        }
        $options['on'] = esc_html_x('On', 'admin-view', 'skudmart');
        $options['off'] = esc_html_x('Off', 'admin-view', 'skudmart');
        return $options;
    }

    public static function get_config_page_title_bar_opts( $image_select = true, $inherit = false ) {
        $options =  array(
            '1'     => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/title-bar-style-1.jpg')    : esc_attr_x('Title & Breadcrumbs Centered', 'admin-view', 'skudmart'),
            '2'     => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/title-bar-style-2.jpg')    : esc_attr_x('Title & Breadcrumbs In Left', 'admin-view', 'skudmart'),
            '3'     => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/title-bar-style-3.jpg')    : esc_attr_x('Title & Breadcrumbs In Right', 'admin-view', 'skudmart'),
            '4'     => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/title-bar-style-4.jpg')    : esc_attr_x('Title Left & Breadcrumbs Right', 'admin-view', 'skudmart'),
            '5'     => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/title-bar-style-5.jpg')    : esc_attr_x('Title Right & Breadcrumbs Left', 'admin-view', 'skudmart')
        );
        if($inherit){
            $inherit = array(
                'inherit' => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/title-bar-inherit.png') : esc_attr_x('Inherit','admin-view', 'skudmart'),
                'hide' => $image_select ? esc_url( Skudmart_Theme_Class::$template_dir_url . '/assets/images/theme_options/title-bar-inherit.png') : esc_attr_x('Hidden','admin-view', 'skudmart')
            );
            $options = $inherit + $options;
        }
        return $options;
    }
	
}