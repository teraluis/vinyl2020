<?php
/**
 * This file includes helper functions used throughout the theme.
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

add_filter( 'body_class', 'skudmart_body_classes' );

/**
 * Head
 */
add_action('wp_head', 'skudmart_add_meta_into_head_tag', 100 );
add_action('skudmart/action/head', 'skudmart_add_extra_data_into_head');

add_action('skudmart/action/before_outer_wrap', 'skudmart_add_pageloader_icon', 1);

/**
 * Header
 */
add_action( 'skudmart/action/header', 'skudmart_render_header', 10 );


/**
 * Page Header
 */
add_action( 'skudmart/action/page_header', 'skudmart_render_page_header', 10 );


/**
 * Sidebar
 */


$site_layout = skudmart_get_site_layout();

if($site_layout == 'col-2cr' || $site_layout == 'col-2cr-l'){
    add_action( 'skudmart/action/after_primary', 'skudmart_render_sidebar', 10 );
}
else{
    add_action( 'skudmart/action/before_primary', 'skudmart_render_sidebar', 10 );
}


/**
 * Footer
 */
add_action( 'skudmart/action/footer', 'skudmart_render_footer', 10 );

add_action( 'skudmart/action/after_outer_wrap', 'skudmart_render_footer_searchform_overlay', 10 );
add_action( 'skudmart/action/after_outer_wrap', 'skudmart_render_footer_cartwidget_overlay', 15 );
add_action( 'skudmart/action/after_outer_wrap', 'skudmart_render_footer_newsletter_popup', 20 );
add_action( 'skudmart/action/after_outer_wrap', 'skudmart_render_footer_handheld', 25 );

/**
 * Related Posts
 */
add_action( 'skudmart/action/after_main', 'skudmart_render_related_posts' );
/**
 * FILTERS
 */

add_filter('skudmart/filter/get_theme_option_by_context', 'skudmart_override_page_title_bar_from_context', 10, 2);
add_filter('previous_post_link', 'skudmart_override_post_navigation_template', 10, 5);
add_filter('next_post_link', 'skudmart_override_post_navigation_template', 10, 5);

add_filter('skudmart/filter/sidebar_primary_name', 'skudmart_override_sidebar_name_from_context');