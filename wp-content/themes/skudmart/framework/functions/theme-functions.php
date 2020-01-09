<?php
/**
 * This file includes helper functions used throughout the theme.
 *
 */
if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

if(!function_exists('skudmart_add_meta_into_head_tag')){
    function skudmart_add_meta_into_head_tag(){
        do_action('skudmart/action/head');
    }
}

/**
 * Adds classes to the body tag
 *
 * @since 1.0.0
 */
if (!function_exists('skudmart_body_classes')) {
    function skudmart_body_classes($classes) {
        $classes[] = is_rtl() ? 'rtl' : 'ltr';
        $classes[] = 'skudmart-body';
        $classes[] = 'lastudio-skudmart';
        $site_layout = skudmart_get_site_layout();
        $header_layout = skudmart_get_header_layout();
        $page_title_bar_layout = skudmart_get_page_header_layout();
        $main_fullwidth = skudmart_get_theme_option_by_context('main_full_width', 'no');
        $header_full_width = skudmart_get_theme_option_by_context('header_full_width', 'no');
        $header_sticky = skudmart_get_theme_option_by_context('header_sticky', 'no');
        $header_transparency = skudmart_get_theme_option_by_context('header_transparency', 'no');
        $footer_full_width = skudmart_get_theme_option_by_context('footer_full_width', 'no');
        $body_boxed = skudmart_get_option('body_boxed', 'no');
        $mobile_footer_bar = (skudmart_get_option('enable_header_mb_footer_bar', 'no') == 'yes') ? true : false;
        $mobile_footer_bar_items = skudmart_get_option('header_mb_footer_bar_component', array());
        $custom_body_class = skudmart_get_theme_option_by_context('body_class', '');
        if (!empty($custom_body_class)) {
            $classes[] = esc_attr($custom_body_class);
        }
        if ($body_boxed == 'yes') {
            $classes[] = 'body-boxed';
        }
        if (is_404()) {
            $classes[] = 'body-col-1c';
            $classes['page_title_bar'] = 'page-title-vhide';
            $header_transparency_404 = skudmart_get_option('header_transparency_404');
            if($header_transparency_404 == 'yes'){
                $classes[] = 'enable-header-transparency';
            }
            elseif ($header_transparency_404 == 'no'){
                $header_transparency = 'no';
            }
        }
        else {
            $classes[] = esc_attr('body-' . $site_layout);
            $classes['page_title_bar'] = esc_attr('page-title-v' . $page_title_bar_layout);
        }

        $header_transparency_blog = skudmart_get_option('header_transparency_blog' );
        if( skudmart_is_blog() ){
            if($header_transparency_blog != 'inherit'){
                $header_transparency = $header_transparency_blog;
            }
        }

        $classes[] = 'header-v-' . esc_attr($header_layout);
        if ($header_transparency == 'yes') {
            $classes[] = 'enable-header-transparency';
        }
        if ($header_sticky != 'no') {
            $classes[] = 'enable-header-sticky';
            if ($header_sticky == 'auto') {
                $classes[] = 'header-sticky-type-auto';
            }
        }
        if (is_singular('page')) {
            global $post;
            if (strpos($post->post_content, 'la_wishlist') !== false) {
                $classes[] = 'woocommerce-page';
                $classes[] = 'woocommerce-page-wishlist';
            }
            if (strpos($post->post_content, 'la_compare') !== false) {
                $classes[] = 'woocommerce-page';
                $classes[] = 'woocommerce-compare';
            }
            if (strpos($post->post_content, 'dokan-') !== false) {
                $classes[] = 'woocommerce-page';
                $classes[] = 'woocommerce-dokan-page';
            }
        }
        if ($header_full_width == 'yes') {
            $classes[] = 'enable-header-fullwidth';
        }
        if ($main_fullwidth == 'yes') {
            $classes[] = 'enable-main-fullwidth';
        }
        if ($footer_full_width == 'yes') {
            $classes[] = 'enable-footer-fullwidth';
        }
        if (skudmart_get_option('page_loading_animation', 'off') == 'on') {
            $classes[] = 'site-loading';
        }
        if ($mobile_footer_bar && !empty($mobile_footer_bar_items)) {
            $classes[] = 'enable-footer-bars';
            $classes[] = 'footer-bars--visible-' . esc_attr(skudmart_get_option('enable_header_mb_footer_bar_sticky', 'always'));
        }
        if ($site_layout == 'col-1c') {
            $blog_small_layout = skudmart_get_option('blog_small_layout', 'off');
            if ( is_singular('post')) {
                $single_small_layout_global = skudmart_get_option('single_small_layout', 'off');
                $single_small_layout = skudmart_get_post_meta(get_queried_object_id(), 'small_layout');
                if ($single_small_layout == 'on') {
                    $classes[] = 'enable-small-layout';
                }
                else {
                    if ($single_small_layout_global == 'on' && $single_small_layout != 'off') {
                        $classes[] = 'enable-small-layout';
                    }
                    else {
                        if ($blog_small_layout == 'on') {
                            $classes[] = 'enable-small-layout';
                        }
                    }
                }
            }
            elseif ( is_category() || is_tag()) {
                $blog_archive_small_layout = skudmart_get_term_meta(get_queried_object_id(), 'small_layout');
                if ($blog_archive_small_layout == 'on') {
                    $classes[] = 'enable-small-layout';
                }
                else {
                    if ($blog_small_layout == 'on' && $blog_archive_small_layout != 'off') {
                        $classes[] = 'enable-small-layout';
                    }
                }
            }
            elseif ( is_home() ) {
                $single_small_layout = skudmart_get_post_meta(get_option( 'page_for_posts', true ), 'small_layout');
                if ( $single_small_layout == 'on') {
                    $classes[] = 'enable-small-layout';
                }
                elseif ($blog_small_layout == 'on' && $single_small_layout != 'off'){
                    $classes[] = 'enable-small-layout';
                }
            }
        }
        if (function_exists('dokan_get_option')) {
            $page_id = dokan_get_option('dashboard', 'dokan_pages');
            if ($page_id) {
                if (dokan_is_store_page() || is_page($page_id) || (get_query_var('edit') && is_singular('product'))) {
                    $classes[] = 'woocommerce-page';
                }
            }
        }
        if (is_404()) {
            $content_404 = skudmart_get_option('404_page_content');
            if (!empty($content_404)) {
                $classes[] = 'has-customized-404';
            }
            else{
                $classes[] = 'has-default-404';
            }
        }
        if (class_exists('LAHB', false)) {
            $data = false;
            if (!empty($header_layout) && $header_layout != 'inherit') {
                if (!is_admin() && !isset($_GET['lastudio_header_builder'])) {
                    $data = LAHB_Helper::get_data_frontend_component_with_preset($header_layout, $data);
                }
            }
            $data = $data ? $data : LAHB_Helper::get_data_frontend_components();
            if (isset($data['desktop-view']['row1']['settings']['header_type'])) {
                $header_type = $data['desktop-view']['row1']['settings']['header_type'];
                if ($header_type == 'vertical') {
                    $vertical_component_id = $data['desktop-view']['row1']['settings']['uniqueId'];
                    $vertical_toggle = false;
                    if (!empty($data['components'][$vertical_component_id]['vertical_toggle']) && $data['components'][$vertical_component_id]['vertical_toggle'] == 'true') {
                        $vertical_toggle = true;
                    }
                    $classes[] = 'header-type-vertical';
                    if ($vertical_toggle) {
                        $classes[] = 'header-type-vertical--toggle';
                    }
                    else {
                        $classes[] = 'header-type-vertical--default';
                    }
                }
            }
        }
        if(is_singular()){
            if(get_post_meta( get_the_ID(), '_elementor_edit_mode', true )){
                $classes[] = 'page-use-builder';
            }
            else{
                if(is_singular(array('post', 'page'))){
                    $classes[] = 'page-use-gutenberg';
                }
            }
        }

        if(skudmart_is_blog() || is_singular(array('post'))) {
            $classes[] = 'skudmart-is-blog';
        }
            // Return classes
        return $classes;
    }
}

if (!function_exists('skudmart_render_header')) {
    function skudmart_render_header() {
        if (skudmart_get_theme_option_by_context('hide_header') == 'yes') {
            return;
        }
        get_template_part('partials/header/layout');
    }
}

if (!function_exists('skudmart_render_page_header')) {
    function skudmart_render_page_header() {
        $value = skudmart_get_page_header_layout();
        if (!empty($value) && $value != 'hide') {
            get_template_part('partials/page_header/layout', $value);
        }
    }
}

if (!function_exists('skudmart_render_sidebar')){
    function skudmart_render_sidebar (){
        get_sidebar();
    }
}

if (!function_exists('skudmart_render_footer')) {
    function skudmart_render_footer() {
        if (skudmart_get_theme_option_by_context('hide_footer') == 'yes') {
            return;
        }
        $value = skudmart_get_footer_layout();
        if (!empty($value) && $value != 'inherit') {
            $value = absint($value);
            if (!empty($value) && get_post_type($value) == 'elementor_library') {
                if (!function_exists('elementor_theme_do_location') || !elementor_theme_do_location('footer')) {
                    ?>
                    <footer id="footer" class="<?php echo esc_attr(skudmart_footer_classes()); ?>"<?php skudmart_schema_markup('footer'); ?>>
                        <?php do_action('skudmart/action/before_footer_inner'); ?>
                        <div id="footer-inner">
                            <div class="container"><?php
                                echo do_shortcode('[elementor-template id="' . $value . '"]');
                            ?></div>
                        </div><!-- #footer-inner -->
                        <?php do_action('skudmart/action/after_footer_inner'); ?>
                    </footer><!-- #footer -->
                    <?php
                }
            }
        }
        else {
            get_template_part('partials/footer/layout');
        }
    }
}

if (!function_exists('skudmart_render_footer_searchform_overlay')){
    function skudmart_render_footer_searchform_overlay(){
        get_template_part('partials/footer/searchform-overlay');
    }
}

if (!function_exists('skudmart_render_footer_cartwidget_overlay')){
    function skudmart_render_footer_cartwidget_overlay(){
        get_template_part('partials/footer/cart-overlay');
    }
}

if (!function_exists('skudmart_render_footer_newsletter_popup')){
    function skudmart_render_footer_newsletter_popup(){
        get_template_part('partials/footer/newsletter');
    }
}

if (!function_exists('skudmart_render_footer_handheld')){
    function skudmart_render_footer_handheld(){
        get_template_part('partials/footer/handheld');
    }
}

if(!function_exists('skudmart_add_extra_data_into_head')){
    function skudmart_add_extra_data_into_head(){
        if( $la_custom_css = skudmart_get_option('la_custom_css') ){
            echo sprintf( '<%1$s id="skudmart-custom-css">%2$s</%1$s>', 'style', skudmart_minify_css($la_custom_css));
        }
    }
}

if(!function_exists('skudmart_add_pageloader_icon')){
    function skudmart_add_pageloader_icon(){
        if( skudmart_string_to_bool( skudmart_get_option('page_loading_animation', 'off') ) ){
            $loading_style = skudmart_get_option('page_loading_style', 1);
            if($loading_style == 'custom'){
                if($img = skudmart_get_option('page_loading_custom')){
                    echo '<div class="la-image-loading spinner-custom"><div class="content"><div class="la-loader">'. wp_get_attachment_image($img, 'full') .'</div></div></div>';
                }
                else{
                    echo '<div class="la-image-loading"><div class="content"><div class="la-loader spinner1"></div></div></div>';
                }
            }
            else{
                echo '<div class="la-image-loading"><div class="content"><div class="la-loader spinner'.esc_attr($loading_style).'"><div class="dot1"></div><div class="dot2"></div><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div><div class="cube1"></div><div class="cube2"></div><div class="cube3"></div><div class="cube4"></div></div></div></div>';
            }
        }
    }
}

/**
 * Override page title bar from global settings
 * What we need to do now is
 * 1. checking in single content types
 *  1.1) post
 *  1.2) product
 *  1.3) portfolio
 * 2. checking in archives
 *  2.1) shop
 *  2.2) portfolio
 *
 * TIPS: List functions will be use to check
 * `is_product`, `is_single_la_portfolio`, `is_shop`, `is_woocommerce`, `is_product_taxonomy`, `is_archive_la_portfolio`, `is_tax_la_portfolio`
 */

if(!function_exists('skudmart_override_page_title_bar_from_context')){
    function skudmart_override_page_title_bar_from_context( $value, $key ){

        $array_key_allow = array(
            'page_title_bar_style',
            'page_title_bar_layout',
            'page_title_bar_background',
            'page_title_bar_border',
            'page_title_bar_space',
            'page_title_bar_heading_fonts',
            'page_title_bar_breadcrumb_fonts',
            'page_title_bar_heading_color',
            'page_title_bar_text_color',
            'page_title_bar_link_color',
            'page_title_bar_link_hover_color'
        );

        $array_key_alternative = array(
            'page_title_bar_layout',
            'page_title_bar_background',
            'page_title_bar_border',
            'page_title_bar_space',
            'page_title_bar_heading_fonts',
            'page_title_bar_breadcrumb_fonts',
            'page_title_bar_heading_color',
            'page_title_bar_text_color',
            'page_title_bar_link_color',
            'page_title_bar_link_hover_color'
        );

        /**
         * Firstly, we need to check the `$key` input
         */
        if( !in_array($key, $array_key_allow) ){
            return $value;
        }

        /**
         * Secondary, we need to check the `$context` input
         */

        if( !is_singular() && !is_tax(get_object_taxonomies( 'la_portfolio' )) && !is_post_type_archive('la_portfolio') && !skudmart_is_blog()){
            return $value;
        }

        $func_name = 'skudmart_get_post_meta';
        $queried_object_id = get_queried_object_id();

        if( is_tax(get_object_taxonomies( 'la_portfolio' ) ) || is_tag() || is_category() ){
            $func_name = 'skudmart_get_term_meta';
        }

        if ( 'page_title_bar_layout' == $key ) {
            $page_title_bar_layout = call_user_func($func_name, $queried_object_id, $key);
            if($page_title_bar_layout && $page_title_bar_layout != 'inherit'){
                return $page_title_bar_layout;
            }
        }

        if( 'yes' == call_user_func($func_name ,$queried_object_id, 'page_title_bar_style') && in_array($key, $array_key_alternative) ){
            return $value;
        }

        $key_override = $new_key = false;

        if( is_singular('la_portfolio') ) {
            $key_override = 'single_portfolio_override_page_title_bar';
            $new_key = 'single_portfolio_' . $key;
        }
        elseif( is_singular('post') ) {
            $key_override = 'single_post_override_page_title_bar';
            $new_key = 'single_post_' . $key;
        }
        elseif ( is_tax(get_object_taxonomies( 'la_portfolio' )) || is_post_type_archive('la_portfolio') ) {
            $key_override = 'archive_portfolio_override_page_title_bar';
            $new_key = 'archive_portfolio_' . $key;
        }

        elseif( skudmart_is_blog() ){
            $key_override = 'blog_post_override_page_title_bar';
            $new_key = 'blog_post_' . $key;
        }

        if(false != $key_override){
            if( 'on' == skudmart_get_option($key_override, 'off') ){
                return skudmart_get_option($new_key, $value);
            }
        }

        return $value;
    }

}

if(!function_exists('skudmart_override_post_navigation_template')){
    function skudmart_override_post_navigation_template( $output, $format, $link, $post, $adjacent ){
        $image = '';
        if(has_post_thumbnail($post)){
            $image = sprintf('<span class="nav_pnpp__image" style="background-image: url(\'%1$s\');"></span>', get_the_post_thumbnail_url($post));
        }
        $output = str_replace( '%image', $image, $output );
        $output = str_replace( '%author', get_the_author(), $output );
        return $output;
    }
}

if(!function_exists('skudmart_override_sidebar_name_from_context')){

    function skudmart_override_sidebar_name_from_context( $sidebar ){

        if( is_search() ) {
            if( ( $sidebar_search = skudmart_get_option('search_sidebar', $sidebar) ) && !empty($sidebar_search) ) {
                return $sidebar_search;
            }
        }

        if( is_tag() || is_category() ) {

            $sidebar = skudmart_get_option('blog_archive_sidebar', $sidebar );

            if( skudmart_string_to_bool( skudmart_get_option('blog_archive_global_sidebar', false) ) ){
                /*
                 * Return global sidebar if option will be enable
                 * We don't need more checking in context
                 */
                return $sidebar;
            }

            if( ( $_sidebar = skudmart_get_term_meta(get_queried_object_id(), 'sidebar') ) && !empty( $_sidebar ) ) {
                return $_sidebar;
            }

        }

        if(is_singular('post')){
            $sidebar = skudmart_get_option('posts_sidebar', $sidebar);
            if( skudmart_string_to_bool( skudmart_get_option('posts_global_sidebar', false) ) ){
                /*
                 * Return global sidebar if option will be enable
                 * We don't need more checking in context
                 */
                return $sidebar;
            }

            if( ( $_sidebar = skudmart_get_post_meta(get_queried_object_id(), 'sidebar') ) && !empty( $_sidebar ) ) {
                return $_sidebar;
            }

        }

        if ( post_type_exists('la_portfolio') ) {
            if ( is_tax() && is_tax( get_object_taxonomies( 'la_portfolio' ) ) ) {
                $sidebar = skudmart_get_option('portfolio_archive_sidebar', $sidebar);
                if( skudmart_string_to_bool(skudmart_get_option('portfolio_archive_global_sidebar', false)) ){
                    /*
                     * Return global sidebar if option will be enable
                     * We don't need more checking in context
                     */
                    return $sidebar;
                }

                if( ( $_sidebar = skudmart_get_term_meta(get_queried_object_id(), 'sidebar') ) && !empty( $_sidebar ) ) {
                    return $_sidebar;
                }
            }

            if(is_singular('la_portfolio')){
                $sidebar = skudmart_get_option('portfolio_sidebar', $sidebar);
                if( skudmart_string_to_bool(skudmart_get_option('portfolio_global_sidebar', false)) ){
                    /*
                     * Return global sidebar if option will be enable
                     * We don't need more checking in context
                     */
                    return $sidebar;
                }

                if( ( $_sidebar = skudmart_get_post_meta(get_queried_object_id(), 'sidebar') ) && !empty( $_sidebar ) ) {
                    return $_sidebar;
                }
            }
        }

        if(is_page()){
            $sidebar = skudmart_get_option('pages_sidebar', $sidebar);
            if( skudmart_string_to_bool(skudmart_get_option('pages_global_sidebar', false)) ){
                /*
                 * Return global sidebar if option will be enable
                 * We don't need more checking in context
                 */
                return $sidebar;
            }

            if( ( $_sidebar = skudmart_get_post_meta(get_queried_object_id(), 'sidebar') ) && !empty( $_sidebar ) ) {
                return $_sidebar;
            }

        }

        return $sidebar;
    }
}

if(!function_exists('skudmart_render_related_posts')){
    function skudmart_render_related_posts(){
        $move_related_to_bottom = skudmart_string_to_bool(skudmart_get_option('move_blog_related_to_bottom', 'off'));
        if( is_singular('post') && $move_related_to_bottom){
            get_template_part('partials/singular/related-posts');
        }
    }
}