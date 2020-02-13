<?php

// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_get_demo_array($dir_url, $dir_path){

    $demo_items = array(
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-01/',
            'title'     => 'Fashion Modern',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-02/',
            'title'     => 'Metro Grid',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-03/',
            'title'     => 'Fashion Clean',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-04/',
            'title'     => 'Fashion Modern 02',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-05/',
            'title'     => 'Suits Store',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-06/',
            'title'     => 'Fashion Creative',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-07/',
            'title'     => 'Fashion Modern 03',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-08/',
            'title'     => 'Sport Store',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-09/',
            'title'     => 'Fashion Creative 02',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-10/',
            'title'     => 'Fashion Hipster',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-11/',
            'title'     => 'Fashion Simple',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-12/',
            'title'     => 'Fashion FullScreen',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-13/',
            'title'     => 'Fashion Metro Grid',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-14/',
            'title'     => 'Fashion Sidebar',
            'category'  => array(
                'Demo'
            )
        ),
        array(
            'link'      => 'https://skudmart.la-studioweb.com/home-15/',
            'title'     => 'Mega Fashion',
            'category'  => array(
                'Demo'
            )
        )
    );

    $default_image_setting = array(
        'woocommerce_single_image_width' => 1000,
        'woocommerce_thumbnail_image_width' => 1000,
        'woocommerce_thumbnail_cropping' => 'custom',
        'woocommerce_thumbnail_cropping_custom_width' => 38,
        'woocommerce_thumbnail_cropping_custom_height' => 41,
        'thumbnail_size_w' => 540,
        'thumbnail_size_h' => 380,
        'medium_size_w' => 0,
        'medium_size_h' => 0,
        'medium_large_size_w' => 0,
        'medium_large_size_h' => 0,
        'large_size_w' => 0,
        'large_size_h' => 0
    );

    $default_menu = array(
        'main-nav'              => 'Primary Navigation'
    );

    $default_page = array(
        'page_for_posts' 	            => 'Blog',
        'woocommerce_shop_page_id'      => 'Shop',
        'woocommerce_cart_page_id'      => 'Cart',
        'woocommerce_checkout_page_id'  => 'Checkout',
        'woocommerce_myaccount_page_id' => 'My Account'
    );

    $slider = $dir_path . 'Slider/';
    $content = $dir_path . 'Content/';
    $widget = $dir_path . 'Widget/';
    $setting = $dir_path . 'Setting/';
    $preview = $dir_url;


    $data_return = array();

    for( $i = 1; $i <= 15; $i ++ ){
        $tmp_i = $i;
        $id = $tmp_i;
        if( $tmp_i < 10 ) {
            $id = '0'. $tmp_i;
        }
        $demo_item_name = !empty($demo_items[$tmp_i - 1]['title']) ? $demo_items[$tmp_i - 1]['title'] : 'Demo ' . $id;

        $value = array();
        $value['title']             = $demo_item_name;
        $value['category']          = !empty($demo_items[$tmp_i - 1]['category']) ? $demo_items[$tmp_i - 1]['category'] : array('Demo');
        $value['demo_preset']       = 'home-' . $id;
        $value['demo_url']          = !empty($demo_items[$tmp_i - 1]['link']) ? $demo_items[$tmp_i - 1]['link'] : 'https://skudmart.la-studioweb.com/home-' . $id . '/';
        $value['preview']           = !empty($demo_items[$tmp_i - 1]['image']) ? $demo_items[$tmp_i - 1]['image'] : $preview  .   'home-' . $id . '.jpg';
        $value['option']            = $setting  .   'home-' . $id . '.json';
        $value['content']           = $content  .   'data-sample.xml';
        $value['widget']            = $widget   .   'widget.json';

        $value['pages']             = array_merge(
            $default_page,
            array(
                'page_on_front' => 'Home ' . $id
            )
        );

        $value['menu-locations']    = array_merge(
            $default_menu,
            array(

            )
        );
        $value['other_setting']    = array_merge(
            $default_image_setting,
            array(

            )
        );

        if(in_array($tmp_i, [1,3,4,5,6,7,8,9,12])){
            $value['slider']  = $slider   .   'home-'. $id .'.zip';
        }

        $data_return['home-'. $id] = $value;
    }


    if(class_exists('LAHB_Helper')){
        $header_presets = LAHB_Helper::getHeaderDefaultData();

        $header_01 = json_decode($header_presets['header-layout-01']['data'], true);
        $header_02 = json_decode($header_presets['header-layout-02']['data'], true);
        $header_03 = json_decode($header_presets['header-layout-03']['data'], true);
        $header_04 = json_decode($header_presets['header-layout-04']['data'], true);
        $header_05 = json_decode($header_presets['header-layout-05']['data'], true);
        $header_06 = json_decode($header_presets['header-layout-06']['data'], true);
        $header_07 = json_decode($header_presets['header-layout-07']['data'], true);
        $header_08 = json_decode($header_presets['header-layout-08']['data'], true);
        $header_09 = json_decode($header_presets['header-layout-09']['data'], true);
        $header_vertical_01 = json_decode($header_presets['header-vertical-01']['data'], true);
        $header_vertical_02 = json_decode($header_presets['header-vertical-02']['data'], true);

        $data_return['home-01']['other_setting'] = $header_01;
        $data_return['home-02']['other_setting'] = $header_02;
        $data_return['home-03']['other_setting'] = $header_03;
        $data_return['home-04']['other_setting'] = $header_04;
        $data_return['home-05']['other_setting'] = $header_09;
        $data_return['home-06']['other_setting'] = $header_05;
        $data_return['home-07']['other_setting'] = $header_07;
        $data_return['home-08']['other_setting'] = $header_08;
        $data_return['home-09']['other_setting'] = $header_01;
        $data_return['home-10']['other_setting'] = $header_01;
        $data_return['home-11']['other_setting'] = $header_01;
        $data_return['home-12']['other_setting'] = $header_06;
        $data_return['home-13']['other_setting'] = $header_vertical_01;
        $data_return['home-14']['other_setting'] = $header_vertical_02;
        $data_return['home-15']['other_setting'] = $header_07;
    }

    return $data_return;
}