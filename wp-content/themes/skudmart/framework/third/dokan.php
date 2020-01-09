<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!function_exists('skudmart_dokan_override_current_title')){

    function skudmart_dokan_override_current_title( $title ){
        if(function_exists('dokan_is_store_page') && dokan_is_store_page()){
            $store_user         = dokan()->vendor->get( get_query_var( 'author' ) );
            return $store_user->get_shop_name();
        }
        return $title;
    }

    add_filter('skudmart/filter/current_title', 'skudmart_dokan_override_current_title');
}

if(!function_exists('skudmart_dokan_override_breadcrumbs')){

    function skudmart_dokan_override_breadcrumbs( $items, $args ){

        if (  function_exists('dokan_is_store_page') && dokan_is_store_page() ) {
            $store_user   = dokan()->vendor->get( get_query_var( 'author' ) );

            if ( is_paged() ){
                $tmp = '';
                if( count($items) > 1 ){
                    $tmp = $items[(count($items) - 1)];
                    unset($items[(count($items) - 1)]);
                }
                $items[] = sprintf( '<a href="%s" itemprop="item">%s</a>', esc_url( dokan_get_store_url( $store_user->get_id() ) ), $store_user->get_shop_name() );
                $items[] = $tmp;
            }
            else{
                $items[] = $store_user->get_shop_name();
            }
        }

        return $items;
    }

    add_filter('breadcrumb_trail_items', 'skudmart_dokan_override_breadcrumbs', 10, 2);
}

if(!function_exists('skudmart_override_dokan_main_query')){
    function skudmart_override_dokan_main_query( $query ) {
        if(function_exists('dokan_is_store_page') && dokan_is_store_page() && isset($query->query['term_section'])){
            $query->set('posts_per_page', 0);

            // fixed for WC 3.7
            if( ! empty( $_GET['orderby'] ) ) {
                $query->set('page_id', 0);
                $query->is_page = false;
                $query->is_singular = false;
            }

            WC()->query->product_query( $query );
        }
    }
    add_action('pre_get_posts', 'skudmart_override_dokan_main_query', 11);
}

if(!function_exists('skudmart_dokan_render_page_header')){
    function skudmart_dokan_render_page_header(){
        if(function_exists('dokan_is_store_page') && dokan_is_store_page()){
            if(dokan_get_option('enable_theme_store_header_tpl', 'dokan_general') == 'on'){
                get_template_part('dokan/store-custom-header');
            }
        }
    }
    add_action( 'skudmart/action/before_content_wrap', 'skudmart_dokan_render_page_header' );
}

if(!function_exists('skudmart_dokan_add_field_to_admin_setting_panels')){
    function skudmart_dokan_add_field_to_admin_setting_panels( $fields ){

        if(isset($fields['dokan_general'])){
            $fields['dokan_general']['store_banner_width'] = array(
                'name'    => 'store_banner_width',
                'label'   => __( 'Store Banner width', 'skudmart' ),
                'type'    => 'text',
                'default' => 625
            );
            $fields['dokan_general']['store_banner_height'] = array(
                'name'    => 'store_banner_height',
                'label'   => __( 'Store Banner height', 'skudmart' ),
                'type'    => 'text',
                'default' => 300
            );
            $fields['dokan_general']['enable_theme_store_header_tpl'] = array(
                'name'    => 'enable_theme_store_header_tpl',
                'label'   => __( 'Store Header Template', 'skudmart' ),
                'desc'    => __( 'Use Store Header Template from the theme', 'skudmart' ),
                'type'    => 'checkbox',
                'default' => 'on'
            );
        }
        return $fields;
    }

    add_filter('dokan_settings_fields', 'skudmart_dokan_add_field_to_admin_setting_panels', 0);
}

if(!function_exists('skudmart_dokan_add_store_banner_image_size_for_getter')){
    function skudmart_dokan_add_store_banner_image_size_for_getter( $data ){
        $data['store_banner_width_dokan_appearance']  = array( 'store_banner_width', 'dokan_general' );
        $data['store_banner_height_dokan_appearance']  = array( 'store_banner_height', 'dokan_general' );
        return $data;
    }
    add_filter('dokan_admin_settings_rearrange_map', 'skudmart_dokan_add_store_banner_image_size_for_getter');
}

if(!function_exists('skudmart_dokan_add_shop_sidebar')){
    function skudmart_dokan_add_shop_sidebar( $sidebar ){
        if(function_exists('dokan_is_store_page') && dokan_is_store_page()){
            $enable_theme_store_sidebar = dokan_get_option( 'enable_theme_store_sidebar', 'dokan_general', 'off' );
            if($enable_theme_store_sidebar == 'on'){
                $sidebar = skudmart_get_option('shop_sidebar', $sidebar);
            }
            else{
                $sidebar = 'sidebar-store';
            }
        }
        return $sidebar;
    }
    add_filter('skudmart/filter/sidebar_primary_name', 'skudmart_dokan_add_shop_sidebar', 30 );
}

if(!function_exists('skudmart_dokan_override_vendor_site_layout')){
    function skudmart_dokan_override_vendor_site_layout( $layout ){

        if(function_exists('dokan_is_store_page') && dokan_is_store_page()){
            $layout = 'col-2cl';
        }

        return $layout;
    }
    add_filter('skudmart/get_site_layout', 'skudmart_dokan_override_vendor_site_layout', 20 );
}

if(!function_exists('skudmart_dokan_override_widget_args')){
    function skudmart_dokan_override_widget_args( $args ) {
        $args['before_widget'] = '<aside id="%1$s" class="sidebar-box widget dokan-store-widget %2$s">';
        return $args;
    }
    add_filter('dokan_store_widget_args', 'skudmart_dokan_override_widget_args');
}

if(!function_exists('skudmart_dokan_profile_social_fields')){
    function skudmart_dokan_profile_social_fields( $fields ){
        if(isset($fields['fb'])){
            $fields['fb']['icon'] = 'facebook';
        }
        if(isset($fields['twitter'])){
            $fields['twitter']['icon'] = 'twitter';
        }
        if(isset($fields['pinterest'])){
            $fields['pinterest']['icon'] = 'pinterest';
        }
        if(isset($fields['linkedin'])){
            $fields['linkedin']['icon'] = 'linkedin';
        }
        if(isset($fields['youtube'])){
            $fields['youtube']['icon'] = 'youtube';
        }
        if(isset($fields['instagram'])){
            $fields['instagram']['icon'] = 'instagram';
        }
        if(isset($fields['flickr'])){
            $fields['flickr']['icon'] = 'flickr';
        }
        return $fields;
    }
    add_filter('dokan_profile_social_fields', 'skudmart_dokan_profile_social_fields');
}