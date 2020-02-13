<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
    die;
}

require_once LASTUDIO_PLUGIN_PATH . 'includes/extensions/elementor/override/general.php';


function lastudio_elementor_autoload( $class ) {
    if ( 0 !== strpos( $class, 'LaStudio_Element' ) ) {
        return;
    }
    $filename = strtolower(
        preg_replace(
            [ '/^' . 'LaStudio_Element' . '\\\/', '/([a-z])([A-Z])/', '/_/', '/\\\/' ],
            [ '', '$1-$2', '-', DIRECTORY_SEPARATOR ],
            $class
        )
    );

    $filename = LASTUDIO_PLUGIN_PATH .'includes/extensions/elementor/' . $filename . '.php';

    if ( is_readable( $filename ) ) {
        include( $filename );
    }
}

spl_autoload_register( 'lastudio_elementor_autoload' );

function lastudio_elementor_template_path(){
    return apply_filters( 'LaStudioElement/template-path', 'partials/elementor/' );
}

function lastudio_elementor_get_template( $name = null ){

    $template = locate_template( lastudio_elementor_template_path() . $name );

    if ( ! $template ) {
        $template = LASTUDIO_PLUGIN_PATH  . 'includes/extensions/elementor/templates/' . str_replace('lastudio-', '', $name);
    }
    if ( file_exists( $template ) ) {
        return $template;
    }
    else {
        return false;
    }
}

function lastudio_elementor_get_all_modules(){
    $elementor_modules = [
        'advanced-carousel' => 'Advanced_Carousel',
        'advanced-map' => 'Advanced_Map',
        'animated-box' => 'Animated_Box',
        'animated-text' => 'Animated_Text',
        'audio' => 'Audio',
        'banner' => 'Banner',
        'button' => 'Button',
        'circle-progress' => 'Circle_Progress',
        'countdown-timer' => 'Countdown_Timer',
        'dropbar'  => 'Dropbar',
        'headline' => 'Headline',
        'horizontal-timeline' => 'Horizontal_Timeline',
        'image-comparison' => 'Image_Comparison',
        'images-layout' => 'Images_Layout',
        'instagram-gallery' => 'Instagram_Gallery',
        'portfolio' => 'Portfolio',
        'posts' => 'Posts',
        'price-list' => 'Price_List',
        'pricing-table' => 'Pricing_Table',
        'progress-bar' => 'Progress_Bar',
        'scroll-navigation' => 'Scroll_Navigation',
        'services' => 'Services',
        'subscribe-form' => 'Subscribe_Form',
        'table' => 'Table',
        'tabs' => 'Tabs',
        'team-member' => 'Team_Member',
        'testimonials' => 'Testimonials',
        'timeline' => 'Timeline',
        'video' => 'Video',
        'breadcrumbs' => 'Breadcrumbs',
        'post-navigation' => 'Post_Navigation',
        'slides' => 'Slides'
    ];

    return $elementor_modules;
}

function lastudio_elementor_get_active_modules(){

    $all_modules = lastudio_elementor_get_all_modules();

    $active_modules = get_option('lastudio_elementor_modules');

    $enable_modules = [];

    if(!empty($active_modules)){
        foreach ($active_modules as $module => $active ){
            if(!empty($active) && isset($all_modules[$module])){
                $enable_modules[$module] = $all_modules[$module];
            }
        }
    }

    if(defined('WPCF7_PLUGIN_URL')){
        $enable_modules['contact-form-7'] = 'Contact_Form_7';
    }
    if(class_exists('WooCommerce')){
        $enable_modules['products'] = 'Products';
    }

    return $enable_modules;
}

function lastudio_elementor_get_resource_dependencies(){

    $resource_base_url = apply_filters('LaStudioElement/resource-base-url', LASTUDIO_PLUGIN_URL . 'public/element');

    $resource_lib_url = LASTUDIO_PLUGIN_URL . 'public/element';

    $google_api_key = apply_filters('LaStudioElement/advanced-map/api', '');

    $min = (apply_filters('lasf_dev_mode', false) || WP_DEBUG) ? '' : '.min';

    $resource_dependencies = [
        'advanced-carousel' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-advanced-carousel-elm',
                    'src'       => $resource_base_url . '/css/carousel'.$min.'.css'
                ],
                [
                    'handler'   => 'lastudio-banner-elm',
                    'src'       => $resource_base_url . '/css/banner'.$min.'.css'
                ]
            ]
        ],
        'slides' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-slides-elm',
                    'src'       => $resource_base_url . '/css/slides'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-slides-elm',
                    'src'       => $resource_base_url . '/js/slides'.$min.'.js'
                ]
            ]
        ],
        'advanced-map' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-advanced-map-elm',
                    'src'       => $resource_base_url . '/css/map'.$min.'.css'
                ]
            ],
            'js'    => [
                [
                    'handler'   => 'google-maps-api',
                    'src'       => add_query_arg( array( 'key' => $google_api_key ), 'https://maps.googleapis.com/maps/api/js' )
                ],
                [
                    'handler'   => 'lastudio-advanced-map-elm',
                    'src'       => $resource_base_url . '/js/advanced-map'.$min.'.js'
                ]
            ]
        ],
        'animated-box' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-animated-box-elm',
                    'src'       => $resource_base_url . '/css/animated-box'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-animated-box-elm',
                    'src'       => $resource_base_url . '/js/animated-box'.$min.'.js'
                ]
            ]
        ],
        'animated-text' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-animated-text-elm',
                    'src'       => $resource_base_url . '/css/animated-text'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-anime-js',
                    'src'       => $resource_lib_url . '/js/lib/anime.min.js'
                ],
                [
                    'handler'   => 'lastudio-animated-text-elm',
                    'src'       => $resource_base_url . '/js/animated-text'.$min.'.js'
                ]
            ]
        ],
        'audio' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-audio-elm',
                    'src'       => $resource_base_url . '/css/audio'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-audio-elm',
                    'src'       => $resource_base_url . '/js/audio'.$min.'.js'
                ]
            ]
        ],
        'banner' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-banner-elm',
                    'src'       => $resource_base_url . '/css/banner'.$min.'.css'
                ]
            ],

        ],
        'button' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-button-elm',
                    'src'       => $resource_base_url . '/css/button'.$min.'.css'
                ]
            ]
        ],
        'circle-progress' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-circle-progress-elm',
                    'src'       => $resource_base_url . '/css/circle-progress'.$min.'.css'
                ]
            ]
        ],
        'dropbar'  => [
            'css'   => [
                [
                    'handler'   => 'lastudio-dropbar-elm',
                    'src'       => $resource_base_url . '/css/dropbar'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-dropbar-elm',
                    'src'       => $resource_base_url . '/js/dropbar'.$min.'.js'
                ]
            ]
        ],
        'headline' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-headline-elm',
                    'src'       => $resource_base_url . '/css/headline'.$min.'.css'
                ]
            ]
        ],
        'horizontal-timeline' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-horizontal-timeline-elm',
                    'src'       => $resource_base_url . '/css/horizontal-timeline'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-horizontal-timeline-elm',
                    'src'       => $resource_base_url . '/js/horizontal-timeline'.$min.'.js'
                ]
            ]
        ],
        'image-comparison' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-juxtapose',
                    'src'       => $resource_base_url . '/css/juxtapose'.$min.'.css'
                ],
                [
                    'handler'   => 'lastudio-image-comparison-elm',
                    'src'       => $resource_base_url . '/css/image-comparison'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-juxtapose',
                    'src'       => $resource_lib_url . '/js/lib/juxtapose.min.js'
                ],
                [
                    'handler'   => 'lastudio-image-comparison-elm',
                    'src'       => $resource_base_url . '/js/image-comparison'.$min.'.js'
                ]
            ]
        ],
        'images-layout' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-images-layout-elm',
                    'src'       => $resource_base_url . '/css/image-layout'.$min.'.css'
                ]
            ]
        ],
        'instagram-gallery' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-instagram-gallery-elm',
                    'src'       => $resource_base_url . '/css/instagram'.$min.'.css'
                ]
            ]
        ],
        'price-list' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-price-list-elm',
                    'src'       => $resource_base_url . '/css/price-list'.$min.'.css'
                ]
            ]
        ],
        'pricing-table' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-pricing-table-elm',
                    'src'       => $resource_base_url . '/css/pricing-table'.$min.'.css'
                ]
            ]
        ],
        'progress-bar' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-progress-bar-elm',
                    'src'       => $resource_base_url . '/css/progress-bar'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-anime-js',
                    'src'       => $resource_lib_url . '/js/lib/anime.min.js'
                ],
                [
                    'handler'   => 'lastudio-progress-bar-elm',
                    'src'       => $resource_base_url . '/js/progress-bar'.$min.'.js'
                ]
            ]
        ],
        'scroll-navigation' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-scroll-navigation-elm',
                    'src'       => $resource_base_url . '/css/scroll-navigation'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-scroll-navigation-elm',
                    'src'       => $resource_base_url . '/js/scroll-navigation'.$min.'.js'
                ]
            ],

        ],
        'services' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-services-elm',
                    'src'       => $resource_base_url . '/css/services'.$min.'.css'
                ]
            ]
        ],
        'subscribe-form' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-subscribe-form-elm',
                    'src'       => $resource_base_url . '/css/subscribe-form'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-subscribe-form-elm',
                    'src'       => $resource_base_url . '/js/subscribe-form'.$min.'.js'
                ]
            ]
        ],
        'table' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-table-elm',
                    'src'       => $resource_base_url . '/css/table'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'jquery-tablesorter',
                    'src'       => $resource_lib_url . '/js/lib/tablesorter.min.js'
                ],
                [
                    'handler'   => 'lastudio-table-elm',
                    'src'       => $resource_base_url . '/js/table'.$min.'.js'
                ]
            ],
        ],
        'tabs' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-tabs-elm',
                    'src'       => $resource_base_url . '/css/tabs'.$min.'.css'
                ]
            ]
        ],
        'team-member' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-team-member-elm',
                    'src'       => $resource_base_url . '/css/team-member'.$min.'.css'
                ]
            ]
        ],
        'testimonials' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-testimonials-elm',
                    'src'       => $resource_base_url . '/css/testimonials'.$min.'.css'
                ]
            ]
        ],
        'timeline' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-timeline-elm',
                    'src'       => $resource_base_url . '/css/timeline'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-timeline-elm',
                    'src'       => $resource_base_url . '/js/timeline'.$min.'.js'
                ]
            ]
        ],
        'video' => [
            'css'   => [
                [
                    'handler'   => 'lastudio-video-elm',
                    'src'       => $resource_base_url . '/css/video'.$min.'.css'
                ]
            ],
            'js'   => [
                [
                    'handler'   => 'lastudio-video-elm',
                    'src'       => $resource_base_url . '/js/video'.$min.'.js'
                ]
            ]
        ]
    ];

    $resource_dependencies = apply_filters('LaStudioElement/resource-dependencies', $resource_dependencies);

    $enable_modules = lastudio_elementor_get_active_modules();

    $modules = [];

    if(!empty($enable_modules)){
        foreach ($enable_modules as $k => $v){
            if(isset($resource_dependencies[$k])){
                $modules[$k] = $resource_dependencies[$k];
            }
        }
    }

    return apply_filters('LaStudioElement/module-enabled-resource-dependency', $modules);
}

add_action('elementor/init', function(){

    \LaStudio_Element\Classes\Query_Control::instance();

    \Elementor\Plugin::instance()->elements_manager->add_category(
        'lastudio',
        [
            'title' => esc_html__( 'LA-Studio Element', 'lastudio' ),
            'icon'  => 'font'
        ],
        1 );
} );

add_action('elementor/controls/controls_registered', function( $controls_manager ){
    $controls_manager->add_group_control( LaStudio_Element\Controls\Group_Control_Box_Style::get_type(), new LaStudio_Element\Controls\Group_Control_Box_Style() );
    if(!defined('ELEMENTOR_PRO_VERSION')){
        $controls_manager->add_group_control( LaStudio_Element\Controls\Group_Control_Motion_Fx::get_type(), new LaStudio_Element\Controls\Group_Control_Motion_Fx() );
    }
});

add_action('elementor/widgets/widgets_registered', function(){

    $modules = lastudio_elementor_get_active_modules();

    if( !empty($modules) ) {
        foreach ($modules as $module => $name){
            $class_name = 'LaStudio_Element\\Widgets\\' . $name;
            if(class_exists($class_name)){
                \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new $class_name() );
            }
        }
    }

});

add_action('elementor/editor/after_enqueue_styles', function(){
    wp_enqueue_style( 'lastudio-elementor', LASTUDIO_PLUGIN_URL . 'admin/css/elementor.css', false, LASTUDIO_VERSION);
});

add_action( 'wp_enqueue_scripts', function(){

    $min = (apply_filters('lasf_dev_mode', false) || WP_DEBUG) ? '' : '.min';

    $modules = lastudio_elementor_get_resource_dependencies();
    if(!empty($modules)){
        foreach ($modules as $module => $resource){
            if(!empty($resource['css'])){
                foreach ($resource['css'] as $css){
                    wp_register_style($css['handler'], $css['src'], false, LASTUDIO_VERSION);
                }
            }
            if(!empty($resource['js'])){
                foreach ($resource['js'] as $js){
                    wp_register_script($js['handler'], $js['src'], false, LASTUDIO_VERSION, true);
                }
            }
        }
    }

    $resource_base_url = apply_filters('LaStudioElement/resource-base-url', LASTUDIO_PLUGIN_URL . 'public/element');
    wp_register_script(
        'lastudio-element-front',
        $resource_base_url . '/js/lastudio-element'.$min.'.js' ,
        [ 'jquery', 'elementor-frontend' ],
        LASTUDIO_VERSION,
        true
    );

    wp_localize_script(
        'lastudio-element-front',
        'LaStudioElementConfigs',
        apply_filters( 'LaStudioElement/frontend/localize-data', [
            'ajaxurl'       => admin_url( 'admin-ajax.php' ),
            'invalidMail'   => esc_attr__( 'Please specify a valid e-mail', 'lastudio' ),
        ] )
    );

} );

add_action('elementor/frontend/after_render', function(){
    $scriptNeedRemove = array(
        'jquery-slick',
    );
    foreach ($scriptNeedRemove as $script) {
        if (wp_script_is($script, 'registered')) {
            wp_dequeue_script($script);
        }
    }

});

add_filter('lastudio/theme/defer_scripts', function( $scripts ){

    $modules = lastudio_elementor_get_resource_dependencies();
    if(!empty($modules)){
        foreach ($modules as $module => $resource){
            if(!empty($resource['js'])){
                foreach ($resource['js'] as $js){
                    $scripts[] = $js['handler'];
                }
            }
        }
    }

    $scripts[] = 'lastudio-element-front';
    $scripts[] = 'lastudio-sticky';
    $scripts[] = 'lastudio-motion-fx';

    return $scripts;
});

add_filter('elementor/icons_manager/additional_tabs', function( $tabs ){
    $tabs['dlicon'] = [
        'name' => 'dlicon',
        'label' => __( 'DL Icons', 'lastudio' ),
        'url' =>  LASTUDIO_PLUGIN_URL . 'public/css/dlicon.css',
        'prefix' => '',
        'displayPrefix' => 'dlicon',
        'labelIcon' => 'fas fa-star',
        'ver' => '1.0.0',
        'fetchJson' => LASTUDIO_PLUGIN_URL . 'public/fonts/dlicon.json',
        'native' => false
    ];
    return $tabs;
});

function lastudio_elementor_tools_get_select_range( $to = 10 ){
    $range = range( 1, $to );
    return array_combine( $range, $range );
}

function lastudio_elementor_tools_get_nextprev_arrows_list( $type = '' ){
    if($type == 'prev'){
        return apply_filters(
            'lastudio_elements/carousel/available_arrows/prev',
            array(
                'lastudioicon-left-arrow'           => __( 'Default', 'lastudio' ),
                'lastudioicon-small-triangle-left'  => __( 'Small Triangle', 'lastudio' ),
                'lastudioicon-triangle-left'        => __( 'Triangle', 'lastudio' ),
                'lastudioicon-arrow-left'           => __( 'Arrow', 'lastudio' ),
            )
        );
    }
    return apply_filters(
        'lastudio_elements/carousel/available_arrows/next',
        array(
            'lastudioicon-right-arrow'           => __( 'Default', 'lastudio' ),
            'lastudioicon-small-triangle-right'  => __( 'Small Triangle', 'lastudio' ),
            'lastudioicon-triangle-right'        => __( 'Triangle', 'lastudio' ),
            'lastudioicon-arrow-right'           => __( 'Arrow', 'lastudio' ),
        )
    );
}

function lastudio_elementor_tools_get_carousel_arrow( $classes = []){
    $format = apply_filters( 'LaStudioElement/carousel/arrows_format', '<i class="%s lastudio-arrow"></i>', $classes );

    return sprintf( $format, implode( ' ', $classes ) );
}

function lastudio_elementor_get_public_post_types( $args = [] ){
    $post_type_args = [
        'show_in_nav_menus' => true,
    ];

    if ( ! empty( $args['post_type'] ) ) {
        $post_type_args['name'] = $args['post_type'];
    }

    $_post_types = get_post_types( $post_type_args, 'objects' );

    $post_types = [];

    foreach ( $_post_types as $post_type => $object ) {
        $post_types[ $post_type ] = $object->label;
    }

    return $post_types;
}

function lastudio_element_render_grid_classes( $columns = [] ){
    $columns = wp_parse_args( $columns, array(
        'desktop'  => '1',
        'laptop'   => '',
        'tablet'   => '',
        'mobile'  => '',
        'xmobile'   => ''
    ) );

    $replaces = array(
        'xmobile' => 'xmobile-block-grid',
        'mobile' => 'mobile-block-grid',
        'tablet' => 'tablet-block-grid',
        'laptop' => 'laptop-block-grid',
        'desktop' => 'block-grid'
    );

    $classes = array();

    foreach ( $columns as $device => $cols ) {
        if ( ! empty( $cols ) ) {
            $classes[] = sprintf( '%1$s-%2$s', $replaces[$device], $cols );
        }
    }
    return implode( ' ' , $classes );
}