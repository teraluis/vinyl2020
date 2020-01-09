<?php

/**
 * Theme functions and definitions.
 *
 * Sets up the theme and provides some helper functions
 *
 * When using a child theme (see https://codex.wordpress.org/Theme_Development
 * and https://codex.wordpress.org/Child_Themes), you can override certain
 * functions (those wrapped in a function_exists() call) by defining them first
 * in your child theme's functions.php file. The child theme's functions.php
 * file is included before the parent theme's file, so the child theme
 * functions would be used.
 *
 *
 * For more information on hooks, actions, and filters,
 * see https://codex.wordpress.org/Plugin_API
 *
 * @package Skudmart WordPress theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!defined('Skudmart_THEME_VERSION')){
    define('Skudmart_THEME_VERSION', '1.0.0');
}

if(!class_exists('Skudmart_Theme_Class')){

    final class Skudmart_Theme_Class {

        public static $template_dir_path = '';

        public static $template_dir_url = '';

        /**
         * Main Theme Class Constructor
         *
         * @since   1.0.0
         */
        public function __construct() {

            self::$template_dir_path   = get_template_directory();
            self::$template_dir_url    = get_template_directory_uri();

            // Define constants
            add_action( 'after_setup_theme', array( $this, 'constants' ), 0 );

            // Load all core theme function files
            add_action( 'after_setup_theme', array( $this, 'include_functions' ), 1 );

            // Load configuration classes
            add_action( 'after_setup_theme', array( $this, 'configs' ), 3 );

            // Load framework classes
            add_action( 'after_setup_theme', array( $this, 'classes' ), 4 );

            // Setup theme => add_theme_support: register_nav_menus, load_theme_textdomain, etc
            add_action( 'after_setup_theme', array( $this, 'theme_setup' ) );

            add_action( 'after_setup_theme', array( $this, 'theme_setup_default' ) );

            // register sidebar widget areas
            add_action( 'widgets_init', array( $this, 'register_sidebars' ) );

            /** Admin only actions **/
            if( is_admin() ) {
                // Load scripts in the WP admin
                add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
                add_action( 'elementor/editor/before_enqueue_scripts', array( $this, 'admin_scripts' ) );
            }
            /** Non Admin actions **/
            else{

                // Load theme CSS
                add_action( 'wp_enqueue_scripts', array( $this, 'theme_css' ) );

                // Load theme js
                add_action( 'wp_enqueue_scripts', array( $this, 'theme_js' ) );

                // Add a pingback url auto-discovery header for singularly identifiable articles
                add_action( 'wp_head', array( $this, 'pingback_header' ), 1 );

                // Add meta viewport tag to header
                add_action( 'wp_head', array( $this, 'meta_viewport' ), 1 );

                // Add meta apple web app capable tag to header
                add_action( 'wp_head', array( $this, 'apple_mobile_web_app_capable_header' ), 1 );

                // Add an X-UA-Compatible header
                add_filter( 'wp_headers', array( $this, 'x_ua_compatible_headers' ) );

                // Add schema markup to the authors post link
                add_filter( 'the_author_posts_link', array( $this, 'the_author_posts_link' ) );

                // Add support for Elementor Pro locations
                add_action( 'elementor/theme/register_locations', array( $this, 'register_elementor_locations' ) );

            }
            // Load scripts in the WP Customizer
            add_action( 'customize_preview_init', array( $this, 'customize_preview_init' ) );

        }

        /**
         * Define Constants
         *
         * @since   1.0.0
         */
        public function constants() {}

        /**
         * Load all core theme function files
         *
         * @since 1.0.0
         */
        public function include_functions() {

            require_once get_theme_file_path('/framework/functions/helpers.php');
            require_once get_theme_file_path('/framework/functions/theme-hooks.php');
            require_once get_theme_file_path('/framework/functions/theme-functions.php');
            require_once get_theme_file_path('/framework/third/lastudio.php');
            require_once get_theme_file_path('/framework/third/lastudio-elementor.php');

        }

        /**
         * Configs for 3rd party plugins.
         *
         * @since 1.0.0
         */
        public function configs() {

            // WooCommerce
            if(class_exists('WooCommerce')){
                require_once get_theme_file_path('/framework/woocommerce/woocommerce-config.php');

                // Dokan
                if(function_exists('dokan')){
                    require_once get_theme_file_path('/framework/third/dokan.php');
                }
            }
        }

        /**
         * Load theme classes
         *
         * @since   1.0.0
         */
        public function classes() {
            // Admin only classes
            if ( is_admin() ) {
                // Recommend plugins
                require_once get_theme_file_path('/plugins/class-tgm-plugin-activation.php');
                require_once get_theme_file_path('/plugins/tgm-plugin-activation.php');
            }

            // Breadcrumbs class
            require_once get_theme_file_path('/framework/classes/breadcrumbs.php');
            require_once get_theme_file_path('/framework/classes/class-options.php');
            require_once get_theme_file_path('/framework/classes/class-admin.php');
            require_once get_theme_file_path('/framework/classes/class-megamenu-init.php');
            require_once get_theme_file_path('/framework/classes/class-megamenu-walker.php');

            new Skudmart_Admin();

        }

        /**
         * Theme Setup
         *
         * @since   1.0.0
         */
        public function theme_setup() {

            $ext = apply_filters('skudmart/use_minify_css_file', false) || ( defined('WP_DEBUG') && WP_DEBUG ) ? '' : '.min';

            // Load text domain
            load_theme_textdomain( 'skudmart', self::$template_dir_path .'/languages' );

            // Get globals
            global $content_width;
            // Set content width based on theme's default design
            if ( ! isset( $content_width ) ) {
                $content_width = 1200;
            }

            // Register navigation menus
            register_nav_menus( array(
                'main-nav'   => esc_attr_x( 'Main Navigation', 'admin-view', 'skudmart' )
            ) );

            // Enable support for Post Formats
            add_theme_support( 'post-formats', array( 'video', 'gallery', 'audio', 'quote', 'link' ) );

            // Enable support for <title> tag
            add_theme_support( 'title-tag' );

            // Add default posts and comments RSS feed links to head
            add_theme_support( 'automatic-feed-links' );

            // Enable support for Post Thumbnails on posts and pages
            add_theme_support( 'post-thumbnails' );

            /**
             * Enable support for header image
             */
            add_theme_support( 'custom-header', apply_filters( 'skudmart/filter/custom_header_args', array(
                'width'              => 2000,
                'height'             => 1200,
                'flex-height'        => true,
                'video'              => true,
            ) ) );

            add_theme_support( 'custom-background' );

            // Declare WooCommerce support.
            add_theme_support( 'woocommerce' );
            if(skudmart_get_option('woocommerce_gallery_zoom') == 'yes'){
                add_theme_support( 'wc-product-gallery-zoom');
            }
            if(skudmart_get_option('woocommerce_gallery_lightbox') == 'yes'){
                add_theme_support( 'wc-product-gallery-lightbox');
            }

            // Support WP Job Manager
            add_theme_support( 'job-manager-templates' );

            // Add editor style
            add_editor_style( 'assets/css/editor-style'.$ext.'.css' );

            // Adding Gutenberg support
            add_theme_support( 'align-wide' );
            add_theme_support( 'wp-block-styles' );
            add_theme_support( 'responsive-embeds' );
            add_theme_support( 'editor-styles' );
            add_editor_style( 'assets/css/gutenberg-editor'.$ext.'.css' );

            add_theme_support( 'editor-color-palette', array(
                array(
                    'name' => esc_attr_x( 'theme primary', 'admin-view', 'skudmart' ),
                    'slug' => 'skudmart-theme-primary',
                    'color' => '#4241FF',
                ),

                array(
                    'name' => esc_attr_x( 'theme secondary', 'admin-view', 'skudmart' ),
                    'slug' => 'skudmart-theme-secondary',
                    'color' => '#212121',
                ),

                array(
                    'name' => esc_attr_x( 'strong magenta', 'admin-view', 'skudmart' ),
                    'slug' => 'strong-magenta',
                    'color' => '#a156b4',
                ),
                array(
                    'name' => esc_attr_x( 'light grayish magenta', 'admin-view', 'skudmart' ),
                    'slug' => 'light-grayish-magenta',
                    'color' => '#d0a5db',
                ),
                array(
                    'name' => esc_attr_x( 'very light gray', 'admin-view',  'skudmart' ),
                    'slug' => 'very-light-gray',
                    'color' => '#eee',
                ),
                array(
                    'name' => esc_attr_x( 'very dark gray', 'admin-view', 'skudmart' ),
                    'slug' => 'very-dark-gray',
                    'color' => '#444',
                ),
            ) );
        }

        /**
         * Theme Setup Default
         *
         * @since   1.0.0
         */
        public function theme_setup_default(){
            $check_theme = get_option('skudmart_has_init1', false);

            if(!$check_theme || !get_option('skudmart_options')){

                update_option(
                    'skudmart_options',
                    json_decode('{"layout":"col-1c","body_boxed":"no","body_max_width":"1230","main_full_width":"no","page_loading_animation":"on","page_loading_style":"1","breadcrumb_home_item":"text","body_font_family":{"font-family":"Roboto","extra-styles":["normal","italic","500","500italic","700","700italic"],"font-size":{"mobile":"14"},"responsive":"yes","unit":"px"},"page_title_bar_heading_tag":"h1","page_title_bar_layout":"1","page_title_bar_background":{"background-color":"#F9F9F9"},"page_title_bar_space":{"mobile":{"top":"40","bottom":"40","unit":"px"},"mobile_landscape":{"top":"40","bottom":"40","unit":"px"},"tablet":{"top":"50","bottom":"50","unit":"px"}},"layout_blog":"col-2cl","blog_small_layout":"off","blog_design":"list-1","blog_post_column":{"mobile":"1","mobile_landscape":"1","tablet":"1","laptop":"2","desktop":"3"},"blog_thumbnail_height_mode":"original","blog_excerpt_length":"37","layout_single_post":"col-2cl","single_small_layout":"off","header_transparency_single_post":"no","blog_post_page_title":"post-title","featured_images_single":"on","single_post_thumbnail_size":"full","blog_post_title":"off","blog_comments":"on","layout_archive_product":"col-1c","header_transparency_archive_product":"inherit","main_full_width_archive_product":"yes","main_space_archive_product":{"mobile":{"top":"30","bottom":"60","unit":"px"},"mobile_landscape":{"top":"","bottom":"","unit":"px"},"tablet":{"top":"60","bottom":"60","unit":"px"},"laptop":{"top":"","bottom":"","unit":"px"},"desktop":{"top":"","bottom":"","unit":"px"}},"catalog_mode":"off","catalog_mode_price":"off","shop_catalog_display_type":"grid","shop_catalog_grid_style":"1","woocommerce_catalog_columns":{"mobile":"1","mobile_landscape":"2","tablet":"3","laptop":"4","desktop":"4"},"woocommerce_shop_page_columns":{"mobile":"1","mobile_landscape":"2","tablet":"3","laptop":"4","desktop":"4"},"product_per_page_allow":"12,15,30","product_per_page_default":"12","woocommerce_pagination_type":"pagination","woocommerce_load_more_text":"Load More Products","woocommerce_enable_crossfade_effect":"off","woocommerce_show_rating_on_catalog":"off","woocommerce_show_addcart_btn":"on","woocommerce_show_quickview_btn":"off","woocommerce_show_wishlist_btn":"off","woocommerce_show_compare_btn":"off","layout_single_product":"col-1c","header_transparency_single_product":"no","main_full_width_single_product":"inherit","main_space_single_product":{"mobile":{"top":"20","bottom":"50","unit":"px"},"mobile_landscape":{"top":"30","bottom":"50","unit":"px"},"tablet":{"top":"70","bottom":"50","unit":"px"}},"woocommerce_product_page_design":"1","single_ajax_add_cart":"yes","move_woo_tabs_to_bottom":"yes","woocommerce_gallery_zoom":"yes","woocommerce_gallery_lightbox":"yes","product_single_hide_breadcrumb":"no","product_single_hide_page_title":"yes","product_single_hide_product_title":"no","product_gallery_column":{"mobile":"3","mobile_landscape":"3","tablet":"3","laptop":"3","desktop":"3"},"product_sharing":"off","related_products":"on","related_product_title":"","related_product_subtitle":"","related_products_columns":{"mobile":"1","mobile_landscape":"2","tablet":"3","laptop":"4","desktop":"4"},"upsell_products":"on","upsell_product_title":"","upsell_product_subtitle":"","upsell_products_columns":{"mobile":"1","mobile_landscape":"2","tablet":"3","laptop":"3","desktop":"4"},"crosssell_products":"on","crosssell_product_title":"","crosssell_product_subtitle":"","crosssell_products_columns":{"mobile":"1","mobile_landscape":"2","tablet":"3","laptop":"4","desktop":"4"},"footer_copyright":"2019 Created by LaStudio"}',true)
                );
                update_option('lastudio_header_layout', 'default');
                update_option('skudmart_has_init1', true);
                update_option('la_extension_available', array(
                    'swatches' => true,
                    '360' => false,
                    'content_type' => true
                ));
                update_option('lastudio_elementor_modules', array(
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
                ));
                update_option( 'elementor_cpt_support', array( 'page', 'post', 'la_portfolio') );
                update_option( 'elementor_disable_color_schemes', 'yes' );
                update_option( 'elementor_stretched_section_container', '#outer-wrap > #wrap' );
                update_option( 'elementor_page_title_selector', '#section_page_header' );
                update_option( 'elementor_editor_break_lines', 1 );
                update_option( 'elementor_edit_buttons', 'on' );
            }
        }

        /**
         * Adds the meta tag to the site header
         *
         * @since 1.0.0
         */
        public function pingback_header() {

            if ( is_singular() && pings_open() ) {
                printf( '<link rel="pingback" href="%s">' . "\n", esc_url( get_bloginfo( 'pingback_url' ) ) );
            }

        }

        /**
         * Adds the meta tag to the site header
         *
         * @since 1.0.0
         */
        public function apple_mobile_web_app_capable_header() {
            printf( '<meta name="apple-mobile-web-app-capable" content="yes">' );
        }

        /**
         * Adds the meta tag to the site header
         *
         * @since 1.0.0
         */
        public function meta_viewport() {

            // Meta viewport
            $viewport = '<meta name="viewport" content="user-scalable=no, width=device-width, initial-scale=1.0">';

            // Apply filters for child theme tweaking
            echo apply_filters( 'ocean_meta_viewport', $viewport );

        }

        /**
         * Load scripts in the WP admin
         *
         * @since 1.0.0
         */
        public function admin_scripts() {
            // Load font icon style
            wp_enqueue_style( 'skudmart-font-lastudioicon', get_theme_file_uri( '/assets/css/lastudioicon.min.css' ), false, '1.0.0' );
        }


        /**
         * Load front-end scripts
         *
         * @since   1.0.0
         */
        public function theme_css() {

            $styleNeedRemove = array(
                'yith-woocompare-widget',
                'jquery-selectBox',
                'yith-wcwl-font-awesome',
                'woocomposer-front-slick',
                'jquery-colorbox'
            );

            foreach ($styleNeedRemove as $style) {
                if (wp_style_is($style, 'registered')) {
                    wp_deregister_style($style);
                }
            }

            $theme_version = defined('WP_DEBUG') && WP_DEBUG ? time() : Skudmart_THEME_VERSION;

            $ext = apply_filters('skudmart/use_minify_css_file', false) || ( defined('WP_DEBUG') && WP_DEBUG ) ? '' : '.min';

            // Load font icon style
            wp_enqueue_style( 'skudmart-font-lastudioicon', get_theme_file_uri ('/assets/css/lastudioicon'.$ext.'.css' ), false, $theme_version );

            if(!class_exists('LASF')) {
                wp_enqueue_style( 'skudmart-fonts', $this->enqueue_google_fonts_url() , array(), null );
            }

            if(skudmart_get_option('enable_header_mb_footer_bar', 'no') == 'yes'){
                wp_enqueue_style( 'skudmart-footer-bar', get_theme_file_uri( '/assets/css/footer-bar'.$ext.'.css' ), false, $theme_version );
            }

            if(defined('ELEMENTOR_VERSION')){
                wp_enqueue_style( 'skudmart-extra-elementor', get_parent_theme_file_uri( '/assets/css/lastudio-elements'.$ext.'.css' ), false, $theme_version );
            }

            wp_enqueue_style( 'skudmart-theme', get_parent_theme_file_uri( '/style'.$ext.'.css' ), false, $theme_version );

            $additional_inline_stype = $this->css_page_preload() . $this->dynamic_css();

            $inline_handler_name = 'skudmart-theme';

            if(class_exists('WooCommerce')){
                wp_enqueue_style( 'skudmart-woocommerce', get_theme_file_uri( '/assets/css/woocommerce'.$ext.'.css' ), false, $theme_version );
                $inline_handler_name = 'skudmart-woocommerce';
            }

            wp_add_inline_style($inline_handler_name, skudmart_minify_css($additional_inline_stype));
        }

        /**
         * Returns all js needed for the front-end
         *
         * @since 1.0.0
         */
        public function theme_js() {


            $scriptNeedRemove = array(
                'woocomposer-slick',
                'jquery-slick',
            );

            foreach ($scriptNeedRemove as $script) {
                if (wp_script_is($script, 'registered')) {
                    wp_dequeue_script($script);
                }
            }

            $theme_version = defined('WP_DEBUG') && WP_DEBUG ? time() : Skudmart_THEME_VERSION;

            $ext = apply_filters('skudmart/use_minify_js_file', false) || ( defined('WP_DEBUG') && WP_DEBUG ) ? '' : '.min';

            // Get localized array
            $localize_array = $this->localize_array();

            wp_register_script( 'skudmart-modernizr-custom', get_theme_file_uri('/assets/js/lib/modernizr-custom'.$ext.'.js') , array('jquery'), $theme_version, true);

            wp_register_script( 'skudmart-sticky', get_theme_file_uri('/assets/js/lib/jquery.sticky'.$ext.'.js') , array('jquery'), $theme_version, true);

            $dependencies = array('jquery', 'skudmart-modernizr-custom', 'skudmart-sticky');

            if(apply_filters('skudmart/filter/force_enqueue_js_external', true)){
                wp_register_script('skudmart-plugins', get_theme_file_uri('/assets/js/lib/plugins.min.js' ), array('jquery'), $theme_version, true);
                $dependencies[] = 'skudmart-plugins';
            }

            if(class_exists('WooCommerce')){
                $dependencies[] = 'skudmart-product-gallery';

                if(!empty($localize_array['la_extension_available']['swatches'])){
                    $dependencies[] = 'skudmart-product-swatches';
                }

            }

            wp_enqueue_script('skudmart-theme', get_theme_file_uri( '/assets/js/app'.$ext.'.js' ), $dependencies, $theme_version, true);

            wp_enqueue_script('skudmart-header-builder', get_theme_file_uri( '/assets/js/header-builder'.$ext.'.js' ), array('skudmart-theme'), $theme_version, true);

            if (is_singular() && comments_open() && get_option('thread_comments')) {
                wp_enqueue_script('comment-reply');
            }

            wp_localize_script('skudmart-theme', 'la_theme_config', $localize_array );

        }

        /**
         * Functions.js localize array
         *
         * @since 1.0.0
         */
        public function localize_array() {

            $array = array(
                'security' => array(
                    'favorite_posts' => wp_create_nonce('favorite_posts'),
                    'wishlist_nonce' => wp_create_nonce('wishlist_nonce'),
                    'compare_nonce' => wp_create_nonce('compare_nonce')
                ),
                'product_single_design' => esc_attr(skudmart_get_option('woocommerce_product_page_design', 1)),
                'product_gallery_column' => esc_attr(json_encode(skudmart_get_option('product_gallery_column', array(
                    'xlg'	=> 3,
                    'lg' 	=> 3,
                    'md' 	=> 3,
                    'sm' 	=> 5,
                    'xs' 	=> 4,
                    'mb' 	=> 3
                )))),
                'single_ajax_add_cart' => skudmart_string_to_bool(skudmart_get_option('single_ajax_add_cart', 'off')),
                'i18n' => array(
                    'backtext' => esc_attr_x('Back', 'front-view', 'skudmart'),
                    'compare' => array(
                        'view' => esc_attr_x('View List Compare', 'front-view', 'skudmart'),
                        'success' => esc_attr_x('has been added to comparison list.', 'front-view', 'skudmart'),
                        'error' => esc_attr_x('An error occurred ,Please try again !', 'front-view', 'skudmart')
                    ),
                    'wishlist' => array(
                        'view' => esc_attr_x('View List Wishlist', 'front-view', 'skudmart'),
                        'success' => esc_attr_x('has been added to your wishlist.', 'front-view', 'skudmart'),
                        'error' => esc_attr_x('An error occurred, Please try again !', 'front-view', 'skudmart')
                    ),
                    'addcart' => array(
                        'view' => esc_attr_x('View Cart', 'front-view', 'skudmart'),
                        'success' => esc_attr_x('has been added to your cart', 'front-view', 'skudmart'),
                        'error' => esc_attr_x('An error occurred, Please try again !', 'front-view', 'skudmart')
                    ),
                    'global' => array(
                        'error' => esc_attr_x('An error occurred ,Please try again !', 'front-view', 'skudmart'),
                        'comment_author' => esc_attr_x('Please enter Name !', 'front-view', 'skudmart'),
                        'comment_email' => esc_attr_x('Please enter Email Address !', 'front-view', 'skudmart'),
                        'comment_rating' => esc_attr_x('Please select a rating !', 'front-view', 'skudmart'),
                        'comment_content' => esc_attr_x('Please enter Comment !', 'front-view', 'skudmart'),
                        'continue_shopping' => esc_attr_x('Continue Shopping', 'front-view', 'skudmart'),
                        'cookie_disabled' => esc_attr_x('We are sorry, but this feature is available only if cookies are enabled on your browser', 'front-view', 'skudmart')
                    )
                ),
                'popup' => array(
                    'max_width' => esc_attr(skudmart_get_option('popup_max_width', 790)),
                    'max_height' => esc_attr(skudmart_get_option('popup_max_height', 430))
                ),
                'js_path'       => esc_attr(self::$template_dir_url . '/assets/js/lib/'),
                'js_min'        => true,
                'theme_path'    => esc_attr(self::$template_dir_url . '/'),
                'ajax_url'      => esc_attr(admin_url('admin-ajax.php')),
                'la_extension_available' => get_option('la_extension_available', array(
                    'swatches' => true,
                    '360' => true,
                    'content_type' => true
                )),
                'mobile_bar' => esc_attr(skudmart_get_option('enable_header_mb_footer_bar_sticky', 'always'))
            );

            // Apply filters and return array
            return apply_filters( 'skudmart/filter/localize_array', $array );
        }


        /**
         * Add headers for IE to override IE's Compatibility View Settings
         *
         * @since 1.0.0
         */
        public function x_ua_compatible_headers( $headers ) {
            $headers['X-UA-Compatible'] = 'IE=edge';
            return $headers;
        }


        /**
         * Add schema markup to the authors post link
         *
         * @since 1.0.0
         */
        public function the_author_posts_link( $link ) {

            // Add schema markup
            $schema = skudmart_get_schema_markup( 'author_link' );
            if ( $schema ) {
                $link = str_replace( 'rel="author"', 'rel="author" '. $schema, $link );
            }
            // Return link
            return $link;

        }
        /**
         * Add support for Elementor Pro locations
         *
         * @since 1.0.0
         */
        public function register_elementor_locations( $elementor_theme_manager ) {
            $elementor_theme_manager->register_all_core_location();
        }

        /**
         * Registers sidebars
         *
         * @since   1.0.0
         */
        public function register_sidebars() {

            $heading = 'h4';
            $heading = apply_filters( 'skudmart/filter/sidebar_heading', $heading );

            // Default Sidebar
            register_sidebar( array(
                'name'			=> esc_html__( 'Default Sidebar', 'skudmart' ),
                'id'			=> 'sidebar',
                'description'	=> esc_html__( 'Widgets in this area will be displayed in the left or right sidebar area if you choose the Left or Right Sidebar layout.', 'skudmart' ),
                'before_widget'	=> '<div id="%1$s" class="widget sidebar-box %2$s">',
                'after_widget'	=> '</div>',
                'before_title'	=> '<'. $heading .' class="widget-title"><span>',
                'after_title'	=> '</span></'. $heading .'>',
            ) );

        }

        public function customize_preview_init(){
            wp_enqueue_script('skudmart-customizer', get_theme_file_uri( '/assets/js/customizer.js' ), array( 'jquery', 'customize-preview' ), null, true);
        }

        public static function enqueue_google_fonts_url(){
            $fonts_url = '';
            $fonts     = array();
            $subsets   = 'latin,latin-ext';

            if ( 'off' !== _x( 'on', 'Playfair Display font: on or off', 'skudmart' ) ) {
                $fonts[] = 'Playfair Display:400,400i,700,700i';
            }

            if ( 'off' !== _x( 'on', 'Roboto font: on or off', 'skudmart' ) ) {
                $fonts[] = 'Roboto:400,400i,500,500i,700,700i';
            }

            if ( $fonts ) {
                $fonts_url = add_query_arg( array(
                    'family' => urlencode( implode( '|', $fonts ) ),
                    'display' => 'swap',
                    'subset' => urlencode( $subsets )
                ), 'https://fonts.googleapis.com/css' );
            }
            return esc_url_raw( $fonts_url );
        }

        private function css_page_preload(){
            ob_start();
            include get_parent_theme_file_path('/framework/css/page-preload-css.php');
            return ob_get_clean();
        }

        private function dynamic_css(){
            ob_start();
            include get_parent_theme_file_path('/framework/css/dynamic-css.php');
            return ob_get_clean();
        }
    }

}

new Skudmart_Theme_Class();