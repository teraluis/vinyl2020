<?php

// If this file is called directly, abort.
use Elementor\Element_Base;

if ( ! defined( 'WPINC' ) ) {
    die;
}

/** Basic Override **/

function lastudio_elementor_recreate_editor_file( ){
    $wp_upload_dir = wp_upload_dir( null, false );
    $target_source_file = $wp_upload_dir['basedir'] . '/elementor/editor.min.js';
    $remote_source_file = plugin_dir_path(LASTUDIO_PLUGIN_PATH) . 'elementor/assets/js/editor.min.js';

    if(file_exists($remote_source_file)){
        try {
            $file_content = @file_get_contents($remote_source_file);
            $string_search = array(
                'this.stylesheet.addDevice("mobile",0).addDevice("tablet",e.md).addDevice("desktop",e.lg)',
                '["desktop","tablet","mobile"]'
            );
            $string_replace = array(
                'this.stylesheet.addDevice("mobile",0).addDevice("tabletportrait",e.sm).addDevice("tablet",e.md).addDevice("laptop",e.lg).addDevice("desktop",e.xl)',
                '["desktop","laptop","tablet","tabletportrait","mobile"]'
            );
            $new_content = str_replace($string_search, $string_replace, $file_content);
            if (@file_put_contents($target_source_file, $new_content)) {
                update_option('lastudio-has-override-elementor-editor-js', true);
            }
            return true;
        }
        catch ( Exception $e ){
            return new WP_Error( 'lastudio_elementor.cannot_fetching', __( 'Could not open the file for fetching', 'lastudio' ) );
        }
    }
    else{
        return new WP_Error( 'lastudio_elementor.cannot_fetching', __( 'Could not open the file for fetching', 'lastudio' ) );
    }
}

add_action('lastudio_elementor_recreate_editor_file', 'lastudio_elementor_recreate_editor_file');

register_activation_hook( LASTUDIO_PLUGIN_PATH . 'lastudio.php', 'lastudio_elementor_recreate_editor_file' );

add_action( 'upgrader_process_complete', function( $upgrader_object, $options ){
    $target_plugin = 'elementor/elementor.php';
    if( $options['action'] == 'update' && $options['type'] == 'plugin' && isset( $options['plugins'] ) ) {
        foreach( $options['plugins'] as $plugin ) {
            if( $plugin == $target_plugin ) {
                do_action('lastudio_elementor_recreate_editor_file');
            }
        }
    }
}, 10, 2 );

function lastudio_elementor_override_editor_before_enqueue_scripts( $src, $handler ){
    if($handler == 'elementor-editor'){
        $wp_upload_dir = wp_upload_dir( null, false );
        return $wp_upload_dir['baseurl'] . '/elementor/editor.min.js';
    }
    return $src;
}

function lastudio_elementor_override_editor_wp_head(){
    ?>
    <script type="text/template" id="tmpl-elementor-control-responsive-switchers">
        <div class="elementor-control-responsive-switchers">
            <#
            var devices = responsive.devices || [ 'desktop', 'laptop', 'tablet', 'tabletportrait', 'mobile' ];
            _.each( devices, function( device ) { #>
            <a class="elementor-responsive-switcher elementor-responsive-switcher-{{ device }}" data-device="{{ device }}">
                <i class="eicon-device-{{ device }}"></i>
            </a>
            <# } );
            #>
        </div>
    </script>
    <?php
}

function lastudio_load_override_elementor_file(){
    if(defined('ELEMENTOR_VERSION')){

        $wp_upload_dir = wp_upload_dir( null, false );
        $target_source_file = $wp_upload_dir['basedir'] . '/elementor/editor.min.js';
        if(!file_exists($target_source_file)){
            do_action('lastudio_elementor_recreate_editor_file');
        }

        add_action('script_loader_src', 'lastudio_elementor_override_editor_before_enqueue_scripts', 10, 2);
        add_action('elementor/editor/wp_head', 'lastudio_elementor_override_editor_wp_head' );

        require_once LASTUDIO_PLUGIN_PATH . 'includes/extensions/elementor/override/includes/base/controls-stack.php';
        require_once LASTUDIO_PLUGIN_PATH . 'includes/extensions/elementor/override/core/files/css/base.php' ;
        require_once LASTUDIO_PLUGIN_PATH . 'includes/extensions/elementor/override/core/responsive/responsive.php';
    }
}
add_action('plugins_loaded', 'lastudio_load_override_elementor_file');


function lastudio_elementor_get_widgets_black_list( $black_list ){
    $new_black_list = array(
        'WP_Widget_Calendar',
        'WP_Widget_Pages',
        'WP_Widget_Archives',
        'WP_Widget_Media_Audio',
        'WP_Widget_Media_Image',
        'WP_Widget_Media_Gallery',
        'WP_Widget_Media_Video',
        'WP_Widget_Meta',
        'WP_Widget_Text',
        'WP_Widget_RSS',
        'WP_Widget_Custom_HTML',
        'RevSliderWidget',
        'LaStudio_Widget_Recent_Posts',
        'LaStudio_Widget_Product_Sort_By',
        'LaStudio_Widget_Price_Filter_List',
        'LaStudio_Widget_Product_Tag',
        'WP_Widget_Recent_Posts',
        'WP_Widget_Recent_Comments',
        'WC_Widget_Cart',
        'WC_Widget_Layered_Nav_Filters',
        'WC_Widget_Layered_Nav',
        'WC_Widget_Price_Filter',
        'WC_Widget_Product_Search',
        'WC_Widget_Product_Tag_Cloud',
        'WC_Widget_Products',
        'WC_Widget_Recently_Viewed',
        'WC_Widget_Top_Rated_Products',
        'WC_Widget_Recent_Reviews',
        'WC_Widget_Rating_Filter'
    );

    $new_black_list = array_merge($black_list, $new_black_list);

    return $new_black_list;
}

add_filter('elementor/widgets/black_list', 'lastudio_elementor_get_widgets_black_list', 20);

add_action( 'elementor/editor/before_enqueue_scripts', function(){
    wp_enqueue_script(
        'lastudio-elementor-backend',
        LASTUDIO_PLUGIN_URL . 'public/element/js/editor-backend.js' ,
        ['jquery'],
        LASTUDIO_VERSION,
        true
    );

    wp_localize_script('lastudio-elementor-backend', 'LaCustomBPFE', [
        'laptop' => [
            'name' => __( 'Laptop', 'lastudio' ),
            'text' => __( 'Preview for 1366px', 'lastudio' )
        ],
        'tablet' => [
            'name' => __( 'Tablet Landscape', 'lastudio' ),
            'text' => __( 'Preview for 1024px', 'lastudio' )
        ],
        'tabletportrait' => [
            'name' => __( 'Tablet Portrait', 'lastudio' ),
            'text' => __( 'Preview for 768px', 'lastudio' )
        ]
    ]);
} );

/** Add Pro config to widget **/

/**
 * Enqueue Monion & Sticky Scripts
 */
add_action('elementor/frontend/before_enqueue_scripts', function(){

    if(defined('ELEMENTOR_PRO_VERSION')){
        return;
    }

    wp_register_script(
        'lastudio-sticky',
        LASTUDIO_PLUGIN_URL . 'public/element/js/lib/jquery.sticky.min.js',
        [
            'jquery',
        ],
        LASTUDIO_VERSION,
        true
    );

    wp_enqueue_script(
        'lastudio-motion-fx',
        LASTUDIO_PLUGIN_URL . 'public/element/js/lib/motion-fx.min.js' ,
        [
            'elementor-frontend-modules',
            'lastudio-sticky'
        ],
        LASTUDIO_VERSION,
        true
    );
});

add_action('elementor/element/after_section_end', function( $controls_stack, $section_id ){
    if ( 'section_custom_css_pro' !== $section_id || defined('ELEMENTOR_PRO_VERSION') ) {
        return;
    }

    $old_section = \Elementor\Plugin::instance()->controls_manager->get_control_from_stack( $controls_stack->get_unique_name(), 'section_custom_css_pro' );
    \Elementor\Plugin::instance()->controls_manager->remove_control_from_stack( $controls_stack->get_unique_name(), [ 'section_custom_css_pro', 'custom_css_pro' ] );

    $controls_stack->start_controls_section(
        'section_custom_css',
        [
            'label' => __( 'Custom CSS', 'lastudio' ),
            'tab' => $old_section['tab'],
        ]
    );

    $controls_stack->add_control(
        'custom_css',
        [
            'type' => Elementor\Controls_Manager::CODE,
            'label' => __( 'Add your own custom CSS here', 'lastudio' ),
            'language' => 'css',
            'description' => __( 'Use "selector" to target wrapper element. Examples:<br>selector {color: red;} // For main element<br>selector .child-element {margin: 10px;} // For child element<br>.my-class {text-align: center;} // Or use any custom selector', 'lastudio' ),
            'render_type' => 'ui',
            'separator' => 'none'
        ]
    );

    $controls_stack->end_controls_section();

}, 10, 2);

add_action('elementor/element/parse_css', function( $post_css, $element ){

    if(defined('ELEMENTOR_PRO_VERSION')){
        return;
    }

    if ( $post_css instanceof Elementor\Core\DynamicTags\Dynamic_CSS) {
        return;
    }
    $element_settings = $element->get_settings();
    if ( empty( $element_settings['custom_css'] ) ) {
        return;
    }
    $css = trim( $element_settings['custom_css'] );

    if ( empty( $css ) ) {
        return;
    }
    $css = str_replace( 'selector', $post_css->get_element_unique_selector( $element ), $css );

    // Add a css comment
    $css = sprintf( '/* Start custom CSS for %s, class: %s */', $element->get_name(), $element->get_unique_selector() ) . $css . '/* End custom CSS */';

    $post_css->get_stylesheet()->add_raw_css( $css );
}, 10, 2);

add_action('elementor/css-file/post/parse', function( $post_css ){

    if(defined('ELEMENTOR_PRO_VERSION')){
        return;
    }

    $document = \Elementor\Plugin::instance()->documents->get( $post_css->get_post_id() );
    $custom_css = $document->get_settings( 'custom_css' );
    $custom_css = trim( $custom_css );
    if ( empty( $custom_css ) ) {
        return;
    }
    $custom_css = str_replace( 'selector', $document->get_css_wrapper_selector(), $custom_css );
    // Add a css comment
    $custom_css = '/* Start custom CSS for page-settings */' . $custom_css . '/* End custom CSS */';
    $post_css->get_stylesheet()->add_raw_css( $custom_css );

});

function lastudio_element_add_controls_group_to_element( $element ){
    if(defined('ELEMENTOR_PRO_VERSION')){
        return;
    }
    $exclude = [];
    $selector = '{{WRAPPER}}';
    if ( $element instanceof Elementor\Element_Section ) {
        $exclude[] = 'motion_fx_mouse';
    }
    elseif ( $element instanceof Elementor\Element_Column ) {
        $selector .= ' > .elementor-column-wrap';
    }
    else {
        $selector .= ' > .elementor-widget-container';
    }
    $element->add_group_control(
        'motion_fx',
        [
            'name' => 'motion_fx',
            'selector' => $selector,
            'exclude' => $exclude,
        ]
    );
}

add_action( 'elementor/element/section/section_effects/after_section_start', 'lastudio_element_add_controls_group_to_element' );
add_action( 'elementor/element/column/section_effects/after_section_start', 'lastudio_element_add_controls_group_to_element' );
add_action( 'elementor/element/common/section_effects/after_section_start', 'lastudio_element_add_controls_group_to_element' );

function lastudio_element_add_controls_group_to_element_background( $element ){
    if(defined('ELEMENTOR_PRO_VERSION')){
        return;
    }
    $element->start_injection( [
        'of' => 'background_bg_width_mobile',
    ] );

    $element->add_group_control(
        'motion_fx',
        [
            'name' => 'background_motion_fx',
            'exclude' => [
                'rotateZ_effect',
                'tilt_effect',
                'transform_origin_x',
                'transform_origin_y',
            ],
        ]
    );

    $options = [
        'separator' => 'before',
        'conditions' => [
            'relation' => 'or',
            'terms' => [
                [
                    'terms' => [
                        [
                            'name' => 'background_background',
                            'value' => 'classic',
                        ],
                        [
                            'name' => 'background_image[url]',
                            'operator' => '!==',
                            'value' => '',
                        ],
                    ],
                ],
                [
                    'terms' => [
                        [
                            'name' => 'background_background',
                            'value' => 'gradient',
                        ],
                        [
                            'name' => 'background_color',
                            'operator' => '!==',
                            'value' => '',
                        ],
                        [
                            'name' => 'background_color_b',
                            'operator' => '!==',
                            'value' => '',
                        ],
                    ],
                ],
            ],
        ],
    ];

    $element->update_control( 'background_motion_fx_motion_fx_scrolling', $options );

    $element->update_control( 'background_motion_fx_motion_fx_mouse', $options );

    $element->end_injection();
}
add_action( 'elementor/element/section/section_background/before_section_end', 'lastudio_element_add_controls_group_to_element_background' );
add_action( 'elementor/element/column/section_style/before_section_end', 'lastudio_element_add_controls_group_to_element_background' );

function lastudio_element_add_sticky_control_to_element( $element ){
    if(defined('ELEMENTOR_PRO_VERSION')){
        return;
    }
    $element->add_control(
        'sticky',
        [
            'label' => __( 'Sticky', 'lastudio' ),
            'type' => Elementor\Controls_Manager::SELECT,
            'options' => [
                '' => __( 'None', 'lastudio' ),
                'top' => __( 'Top', 'lastudio' ),
                'bottom' => __( 'Bottom', 'lastudio' ),
            ],
            'separator' => 'before',
            'render_type' => 'none',
            'frontend_available' => true,
        ]
    );

    $element->add_control(
        'sticky_on',
        [
            'label' => __( 'Sticky On', 'lastudio' ),
            'type' => Elementor\Controls_Manager::SELECT2,
            'multiple' => true,
            'label_block' => 'true',
            'default' => [ 'desktop', 'tablet', 'mobile' ],
            'options' => [
                'desktop' => __( 'Desktop', 'lastudio' ),
                'tablet' => __( 'Tablet', 'lastudio' ),
                'mobile' => __( 'Mobile', 'lastudio' ),
            ],
            'condition' => [
                'sticky!' => '',
            ],
            'render_type' => 'none',
            'frontend_available' => true,
        ]
    );

    $element->add_control(
        'sticky_offset',
        [
            'label' => __( 'Offset', 'lastudio' ),
            'type' => Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'min' => 0,
            'max' => 500,
            'required' => true,
            'condition' => [
                'sticky!' => '',
            ],
            'render_type' => 'none',
            'frontend_available' => true,
        ]
    );

    $element->add_control(
        'sticky_effects_offset',
        [
            'label' => __( 'Effects Offset', 'lastudio' ),
            'type' => Elementor\Controls_Manager::NUMBER,
            'default' => 0,
            'min' => 0,
            'max' => 1000,
            'required' => true,
            'condition' => [
                'sticky!' => '',
            ],
            'render_type' => 'none',
            'frontend_available' => true,
        ]
    );

    /*		if ( $element instanceof Elementor\Widget_Base ) { */
    $element->add_control(
        'sticky_parent',
        [
            'label' => __( 'Stay In Column', 'lastudio' ),
            'type' => Elementor\Controls_Manager::SWITCHER,
            'condition' => [
                'sticky!' => '',
            ],
            'render_type' => 'none',
            'frontend_available' => true,
        ]
    );
    /*		} */

    $element->add_control(
        'sticky_divider',
        [
            'type' => Elementor\Controls_Manager::DIVIDER,
        ]
    );
}
add_action( 'elementor/element/section/section_effects/after_section_start', 'lastudio_element_add_sticky_control_to_element' );
add_action( 'elementor/element/common/section_effects/after_section_start', 'lastudio_element_add_sticky_control_to_element' );

/** Add Shortcode **/
if(!defined('ELEMENTOR_PRO_VERSION')) {
    if (is_admin()) {
        add_action('manage_' . Elementor\TemplateLibrary\Source_Local::CPT . '_posts_columns', function ($defaults) {
            $defaults['shortcode'] = __('Shortcode', 'lastudio');
            return $defaults;
        });
        add_action('manage_' . Elementor\TemplateLibrary\Source_Local::CPT . '_posts_custom_column', function ( $column_name, $post_id) {
            if ( 'shortcode' === $column_name ) {
                // %s = shortcode, %d = post_id
                $shortcode = esc_attr( sprintf( '[%s id="%d"]', 'elementor-template', $post_id ) );
                printf( '<input class="elementor-shortcode-input" type="text" readonly onfocus="this.select()" value="%s" />', $shortcode );
            }
        }, 10, 2);
    }
    add_shortcode( 'elementor-template', function( $attributes = [] ){
        if ( empty( $attributes['id'] ) ) {
            return '';
        }
        $include_css = false;
        if ( isset( $attributes['css'] ) && 'false' !== $attributes['css'] ) {
            $include_css = (bool) $attributes['css'];
        }
        return Elementor\Plugin::instance()->frontend->get_builder_content_for_display( $attributes['id'], $include_css );
    } );

    add_action('elementor/init', function(){
        Elementor\Plugin::instance()->documents->register_document_type( 'footer', LaStudio_Element\Classes\Footer_Location::get_class_full_name() );
    });
}

add_filter('single_template', function( $template ){
    if(is_singular('elementor_library')){
        return LASTUDIO_PLUGIN_PATH .'includes/extensions/elementor/single-elementor_library.php';
    }
    return $template;
});
/** Add Footer Location **/


/**   OVERRIDE Widget Base  **/