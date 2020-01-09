<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!function_exists('skudmart_override_elementor_resource')){
    function skudmart_override_elementor_resource( $path ){
        $path = get_theme_file_uri('assets/addon');
        return $path;
    }
}
add_filter('LaStudioElement/resource-base-url', 'skudmart_override_elementor_resource');

if(!function_exists('skudmart_add_icon_library_into_elementor')){
    function skudmart_add_icon_library_into_elementor( $tabs ) {
        $tabs['lastudioicon'] = [
            'name' => 'lastudioicon',
            'label' => esc_html__( 'LA-Studio Icons', 'skudmart' ),
            'prefix' => 'lastudioicon-',
            'displayPrefix' => '',
            'labelIcon' => 'fas fa-star',
            'ver' => '1.0.0',
            'fetchJson' => get_theme_file_uri('assets/fonts/LaStudioIcons.json'),
            'native' => false
        ];
        return $tabs;
    }
}
add_filter('elementor/icons_manager/additional_tabs', 'skudmart_add_icon_library_into_elementor');

if(!function_exists('skudmart_add_banner_hover_effect')){
    function skudmart_add_banner_hover_effect( $effects ){
        return array_merge(array(
            'none'   => esc_html__( 'None', 'skudmart' ),
            'type-1' => esc_html__( 'LaStudio Type 1', 'skudmart' ),
            'type-2' => esc_html__( 'LaStudio Type 2', 'skudmart' ),
            'type-3' => esc_html__( 'LaStudio Type 3', 'skudmart' ),
        ), $effects);
    }
}
add_filter('LaStudioElement/banner/hover_effect', 'skudmart_add_banner_hover_effect');

if(!function_exists('skudmart_add_portfolio_preset')){
    function skudmart_add_portfolio_preset( ){
        return array(
            'type-1' => esc_html__( 'Type 1', 'skudmart' ),
            'type-2' => esc_html__( 'Type 2', 'skudmart' ),
            'type-3' => esc_html__( 'Type 3', 'skudmart' ),
            'type-4' => esc_html__( 'Type 4', 'skudmart' ),
            'type-5' => esc_html__( 'Type 5', 'skudmart' ),
            'type-6' => esc_html__( 'Type 6', 'skudmart' ),
            'type-7' => esc_html__( 'Type 7', 'skudmart' ),
        );
    }
}
add_filter('LaStudioElement/portfolio/control/preset', 'skudmart_add_portfolio_preset');

if(!function_exists('skudmart_add_portfolio_list_preset')){
    function skudmart_add_portfolio_list_preset( ){
        return array(
            'list-type-1' => esc_html__( 'Type 1', 'skudmart' ),
            'list-type-2' => esc_html__( 'Type 2', 'skudmart' ),
            'list-type-3' => esc_html__( 'Type 3', 'skudmart' ),
            'list-type-4' => esc_html__( 'Type 4', 'skudmart' )
        );
    }
}
add_filter('LaStudioElement/portfolio/control/preset_list', 'skudmart_add_portfolio_list_preset');

if(!function_exists('skudmart_add_team_member_preset')){
    function skudmart_add_team_member_preset( ){
        return array(
            'type-1' => esc_html__( 'Type 1', 'skudmart' ),
            'type-2' => esc_html__( 'Type 2', 'skudmart' ),
            'type-3' => esc_html__( 'Type 3', 'skudmart' ),
            'type-4' => esc_html__( 'Type 4', 'skudmart' ),
            'type-5' => esc_html__( 'Type 5', 'skudmart' ),
            'type-6' => esc_html__( 'Type 6', 'skudmart' ),
            'type-7' => esc_html__( 'Type 7', 'skudmart' ),
            'type-8' => esc_html__( 'Type 8', 'skudmart' )
        );
    }
}
add_filter('LaStudioElement/team-member/control/preset', 'skudmart_add_team_member_preset');

if(!function_exists('skudmart_add_posts_preset')){
    function skudmart_add_posts_preset( ){
        return array(
            'grid-1' => esc_html__( 'Grid 1', 'skudmart' ),
            'grid-2' => esc_html__( 'Grid 2', 'skudmart' ),
            'grid-3' => esc_html__( 'Grid 3', 'skudmart' ),
            'grid-4' => esc_html__( 'Grid 4', 'skudmart' ),
            'grid-5' => esc_html__( 'Grid 5', 'skudmart' ),
            'list-1' => esc_html__( 'List 1', 'skudmart' ),
            'list-2' => esc_html__( 'List 2', 'skudmart' )
        );
    }
}

add_filter('LaStudioElement/posts/control/preset', 'skudmart_add_posts_preset');

if(!function_exists('skudmart_add_google_maps_api')){
    function skudmart_add_google_maps_api( $key ){
        return skudmart_get_option('google_key', $key);
    }
}
add_filter('LaStudioElement/advanced-map/api', 'skudmart_add_google_maps_api');

if(!function_exists('skudmart_add_instagram_access_token_api')){
    function skudmart_add_instagram_access_token_api( $key ){
        return skudmart_get_option('instagram_token', $key);
    }
}
add_filter('LaStudioElement/instagram-gallery/api', 'skudmart_add_instagram_access_token_api');

if(!function_exists('skudmart_add_mailchimp_access_token_api')){
    function skudmart_add_mailchimp_access_token_api( $key ){
        return skudmart_get_option('mailchimp_api_key', $key);
    }
}
add_filter('LaStudioElement/mailchimp/api', 'skudmart_add_mailchimp_access_token_api');

if(!function_exists('skudmart_add_mailchimp_list_id')){
    function skudmart_add_mailchimp_list_id( $key ){
        return skudmart_get_option('mailchimp_list_id', $key);
    }
}
add_filter('LaStudioElement/mailchimp/list_id', 'skudmart_add_mailchimp_list_id');

if(!function_exists('skudmart_add_mailchimp_double_opt_in')){
    function skudmart_add_mailchimp_double_opt_in( $key ){
        return skudmart_get_option('mailchimp_double_opt_in', $key);
    }
}
add_filter('LaStudioElement/mailchimp/double_opt_in', 'skudmart_add_mailchimp_double_opt_in');

if(!function_exists('skudmart_render_breadcrumbs_in_widget')){
    function skudmart_render_breadcrumbs_in_widget( $args ) {

        $html_tag = 'nav';
        if(!empty($args['container'])){
            $html_tag = esc_attr($args['container']);
        }

        if ( function_exists( 'yoast_breadcrumb' ) ) {
            $classes = 'site-breadcrumbs';
            return yoast_breadcrumb( '<'.$html_tag.' class="'. esc_attr($classes) .'">', '</'.$html_tag.'>' );
        }

        $breadcrumb = apply_filters( 'breadcrumb_trail_object', null, $args );

        if ( !is_object( $breadcrumb ) ){
            $breadcrumb = new Skudmart_Breadcrumb_Trail( $args );
        }

        return $breadcrumb->trail();

    }
}
add_action('LaStudioElement/render_breadcrumbs_output', 'skudmart_render_breadcrumbs_in_widget');

if(!function_exists('skudmart_turnoff_default_style_of_gallery')){
    function skudmart_turnoff_default_style_of_gallery( $base ){
        if( 'image-gallery' === $base->get_name() ) {
            add_filter('use_default_gallery_style', '__return_false');
        }
    }
}
add_action('elementor/widget/before_render_content', 'skudmart_turnoff_default_style_of_gallery');