<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$prefix_opts = '_skudmart_post_options';

LASF::createMetabox( $prefix_opts, array(
    'title'        => esc_html_x('Meta Options', 'admin-view', 'skudmart'),
    'post_type'    => array('post', 'page', 'la_team_member', 'la_portfolio', 'product'),
    'show_restore' => true
) );

/**
 * Post Format Section
 */
LASF::createSection( $prefix_opts, array(
    'title'  => esc_html_x('Post', 'admin-view', 'skudmart'),
    'icon'   => 'fa fa-cog',
    'post_type_visible' => array('post'),
    'fields' => array(
        array(
            'type'          => 'subheading',
            'content'       => esc_html_x('For post format QUOTE', 'admin-view', 'skudmart')
        ),
        array(
            'id'            => 'format_quote_content',
            'type'          => 'textarea',
            'title'         => esc_html_x('Quote Content', 'admin-view', 'skudmart')
        ),
        array(
            'id'            => 'format_quote_author',
            'type'          => 'text',
            'title'         => esc_html_x('Quote Author', 'admin-view', 'skudmart')
        ),
        array(
            'id'            => 'format_quote_background',
            'type'          => 'color',
            'title'         => esc_html_x('Quote Background Color', 'admin-view', 'skudmart'),
            'default'       => '#343538'
        ),
        array(
            'id'            => 'format_quote_color',
            'type'          => 'color',
            'title'         => esc_html_x('Quote Text Color', 'admin-view', 'skudmart'),
            'default'       => '#fff'
        ),

        array(
            'type'          => 'subheading',
            'content'       => esc_html_x('For post format LINK', 'admin-view', 'skudmart')
        ),
        array(
            'id'            => 'format_link',
            'type'          => 'text',
            'title'         => esc_html_x('Custom Link', 'admin-view', 'skudmart')
        ),

        array(
            'type'          => 'subheading',
            'content'       => esc_html_x('For post format VIDEO & AUDIO', 'admin-view', 'skudmart')
        ),
        array(
            'id'            => 'format_video_url',
            'type'          => 'text',
            'title'         => esc_html_x('Custom Video Link', 'admin-view', 'skudmart'),
            'desc'          => esc_html_x('Insert Youtube or Vimeo embed link', 'admin-view', 'skudmart'),
        ),
        array(
            'id'            => 'format_embed',
            'type'          => 'textarea',
            'title'         => esc_html_x('Embed Code', 'admin-view', 'skudmart'),
            'desc'          => esc_html_x('Insert Youtube or Vimeo or Audio embed code.', 'admin-view', 'skudmart'),
            'sanitize'      => false
        ),
        array(
            'id'             => 'format_embed_aspect_ration',
            'type'           => 'select',
            'title'          => esc_html_x('Embed aspect ration', 'admin-view', 'skudmart'),
            'options'        => array(
                'origin'        => 'origin',
                '169'           => '16:9',
                '43'            => '4:3',
                '235'           => '2.35:1'
            )
        ),
        array(
            'type'          => 'subheading',
            'content'       => esc_html_x('For post format GALLERY', 'admin-view', 'skudmart')
        ),
        array(
            'id'            => 'format_gallery',
            'type'          => 'gallery',
            'title'         => esc_html_x('Gallery Images', 'admin-view', 'skudmart')
        )
    )
) );

/**
 * Member Information Section
 */
LASF::createSection( $prefix_opts, array(
    'title'  => esc_html_x('Member Information', 'admin-view', 'skudmart'),
    'icon'   => 'fa fa-cog',
    'post_type_visible' => array('la_team_member'),
    'fields' => array(
        array(
            'id'    => 'role',
            'type'  => 'text',
            'title' => esc_html_x('Role', 'admin-view', 'skudmart'),
        ),
        array(
            'id'    => 'phone',
            'type'  => 'text',
            'title' => esc_html_x('Phone', 'admin-view', 'skudmart'),
        ),
        array(
            'id'    => 'facebook',
            'type'  => 'text',
            'title' => esc_html_x('Facebook URL', 'admin-view', 'skudmart'),
        ),
        array(
            'id'    => 'twitter',
            'type'  => 'text',
            'title' => esc_html_x('Twitter URL', 'admin-view', 'skudmart'),
        ),
        array(
            'id'    => 'pinterest',
            'type'  => 'text',
            'title' => esc_html_x('Pinterest URL', 'admin-view', 'skudmart'),
        ),
        array(
            'id'    => 'linkedin',
            'type'  => 'text',
            'title' => esc_html_x('LinkedIn URL', 'admin-view', 'skudmart'),
        ),
        array(
            'id'    => 'dribbble',
            'type'  => 'text',
            'title' => esc_html_x('Dribbble URL', 'admin-view', 'skudmart'),
        ),
        array(
            'id'    => 'youtube',
            'type'  => 'text',
            'title' => esc_html_x('Youtube URL', 'admin-view', 'skudmart'),
        ),
        array(
            'id'    => 'email',
            'type'  => 'text',
            'title' => esc_html_x('Email Address', 'admin-view', 'skudmart'),
        )
    )
) );

/**
 * Product Section
 */
LASF::createSection( $prefix_opts, array(
    'title'  => esc_html_x('Product', 'admin-view', 'skudmart'),
    'icon'   => 'fa fa-cog',
    'post_type_visible' => array('product'),
    'fields' => array(
        array(
            'id'                => 'product_video_url',
            'type'              => 'text',
            'title'             => esc_html_x('Product Video URL', 'admin-view', 'skudmart')
        ),
        array(
            'id'                => 'product_badges',
            'type'              => 'group',
            'title'             => esc_html_x('Custom Badges', 'admin-view', 'skudmart'),
            'button_title'      => esc_html_x('Add Badge','admin-view', 'skudmart'),
            'max'               => 3,
            'fields'            => array(
                array(
                    'id'            => 'text',
                    'type'          => 'text',
                    'default'       => 'New',
                    'title'         => esc_html_x('Badge Text', 'admin-view', 'skudmart')
                ),
                array(
                    'id'            => 'bg',
                    'type'          => 'color',
                    'default'       => '',
                    'title'         => esc_html_x('Custom Badge Background Color', 'admin-view', 'skudmart')
                ),
                array(
                    'id'            => 'color',
                    'type'          => 'color',
                    'default'       => '',
                    'title'         => esc_html_x('Custom Badge Text Color', 'admin-view', 'skudmart')
                ),
                array(
                    'id'            => 'el_class',
                    'type'          => 'text',
                    'default'       => '',
                    'title'         => esc_html_x('Extra CSS class for badge', 'admin-view', 'skudmart')
                )
            )
        ),
    )
) );



/**
 * Layout Section
 */
LASF::createSection( $prefix_opts, array(
    'title'  => esc_html_x('Layout', 'admin-view', 'skudmart'),
    'icon'   => 'fa fa-cog',
    'fields' => array(
        array(
            'id'        => 'layout',
            'type'      => 'image_select',
            'title'     => esc_html_x('Layout', 'admin-view', 'skudmart'),
            'subtitle'  => esc_html_x('Select main content and sidebar alignment. Choose between 1, 2 or 3 column layout.', 'admin-view', 'skudmart'),
            'default'   => 'inherit',
            'options'   => Skudmart_Options::get_config_main_layout_opts(true, true)
        ),
        array(
            'id'        => 'small_layout',
            'type'      => 'button_set',
            'default'   => 'inherit',
            'title'     => esc_html_x('Enable Small Layout', 'admin-view', 'skudmart'),
            'dependency' => array('layout', '==', 'col-1c'),
            'options'   => array(
                'inherit'        => esc_html_x('Inherit', 'admin-view', 'skudmart'),
                'on'        => esc_html_x('On', 'admin-view', 'skudmart'),
                'off'       => esc_html_x('Off', 'admin-view', 'skudmart')
            )
        ),
        array(
            'id'        => 'main_full_width',
            'type'      => 'button_set',
            'default'   => 'inherit',
            'title'     => esc_html_x('100% Main Width', 'admin-view', 'skudmart'),
            'subtitle'      => esc_html_x('Turn on to have the main area display at 100% width according to the window size. Turn off to follow site width.', 'admin-view', 'skudmart'),
            'options'   => Skudmart_Options::get_config_radio_opts()
        ),

        skudmart_render_responsive_main_space_options(array(
            'id'        => 'main_space',
            'title'     => esc_html_x('Custom Main Space', 'admin-view', 'skudmart'),
            'subtitle'  => esc_html_x('Leave empty if you not need', 'admin-view', 'skudmart'),
        )),

        array(
            'id'             => 'sidebar',
            'type'           => 'select',
            'title'          => esc_html_x('Override Sidebar', 'admin-view', 'skudmart'),
            'subtitle'       => esc_html_x('Select sidebar that will display on this page.', 'admin-view', 'skudmart'),
            'options'        => 'sidebars',
            'placeholder'    => esc_html_x('None', 'admin-view', 'skudmart'),
            'dependency'     => array('layout', '!=', 'col-1c')
        ),

        array(
            'id'        => 'body_class',
            'type'      => 'text',
            'title'     => esc_html_x('Custom Body CSS Class', 'admin-view', 'skudmart')
        ),
    )
) );

/**
 * Header Section
 */
LASF::createSection( $prefix_opts, array(
    'title'  => esc_html_x('Header', 'admin-view', 'skudmart'),
    'icon'   => 'fa fa-arrow-up',
    'fields' => array(
        array(
            'id'            => 'hide_header',
            'type'          => 'button_set',
            'default'       => 'no',
            'title'         => esc_html_x('Hide header', 'admin-view', 'skudmart'),
            'options'       => Skudmart_Options::get_config_radio_opts(false)
        ),
        array(
            'id'            => 'header_layout',
            'type'          => 'select',
            'title'         => esc_html_x('Header Layout', 'admin-view', 'skudmart'),
            'subtitle'      => esc_html_x('Controls the layout of the header.', 'admin-view', 'skudmart'),
            'default'       => 'inherit',
            'options'       => Skudmart_Options::get_config_header_layout_opts(false, true),
            'dependency'    => array( 'hide_header', '==', 'no' )
        ),
        array(
            'id'            => 'header_transparency',
            'type'          => 'button_set',
            'default'       => 'inherit',
            'title'         => esc_html_x('Enable Header Transparency', 'admin-view', 'skudmart'),
            'options'       => Skudmart_Options::get_config_radio_opts(),
            'dependency'    => array( 'hide_header', '==', 'no' )
        ),
        array(
            'id'            => 'header_sticky',
            'type'          => 'button_set',
            'default'       => 'inherit',
            'title'         => esc_html_x('Enable Header Sticky', 'admin-view', 'skudmart'),
            'options'       => array(
                'inherit'   => esc_html_x('Inherit', 'admin-view', 'skudmart'),
                'no'        => esc_html_x('Disable', 'admin-view', 'skudmart'),
                'auto'      => esc_html_x('Activate when scroll up', 'admin-view', 'skudmart'),
                'yes'       => esc_html_x('Activate when scroll up & down', 'admin-view', 'skudmart')
            ),
            'dependency'    => array( 'hide_header', '==', 'no' )
        )
    )
) );

/**
 * Page Title Bar Section
 */

$tmp_page_title = skudmart_options_section_page_title_bar_auto_detect('default', true);

$page_title_layout = array_shift($tmp_page_title);

array_unshift($tmp_page_title, array(
    'id'            => 'page_title_bar_style',
    'type'          => 'button_set',
    'default'       => 'no',
    'title'         => esc_html_x('Enable Custom Style', 'admin-view', 'skudmart'),
    'options'       => Skudmart_Options::get_config_radio_opts(false),
    'dependency'    => array( 'page_title_bar_layout', '!=', 'hide' )
));

array_unshift($tmp_page_title, array(
    'id'            => 'hide_breadcrumb',
    'type'          => 'button_set',
    'default'       => 'no',
    'title'         => esc_html_x('Hide Breadcrumbs', 'admin-view', 'skudmart'),
    'options'       => Skudmart_Options::get_config_radio_opts(false),
    'dependency'    => array( 'page_title_bar_layout', '!=', 'hide' )
));

array_unshift($tmp_page_title, array(
    'id'            => 'enable_page_title_subtext',
    'type'          => 'button_set',
    'default'       => 'no',
    'title'         => esc_html_x('Replace breadcrumb by custom text', 'admin-view', 'skudmart'),
    'options'       => Skudmart_Options::get_config_radio_opts(false),
    'dependency'    => array( 'page_title_bar_layout|hide_breadcrumb', '!=|==', 'hide|no' )
));

array_unshift($tmp_page_title, array(
    'id'            => 'page_title_custom_subtext',
    'type'          => 'text',
    'title'         => esc_html_x('Custom Text', 'admin-view', 'skudmart'),
    'dependency'    => array( 'page_title_bar_layout|hide_breadcrumb|enable_page_title_subtext', '!=|==|==', 'hide|no|yes' )
));

array_unshift($tmp_page_title, array(
    'id'            => 'hide_page_title',
    'type'          => 'button_set',
    'default'       => 'no',
    'title'         => esc_html_x('Hide Page Title', 'admin-view', 'skudmart'),
    'options'       => Skudmart_Options::get_config_radio_opts(false),
    'dependency'    => array( 'page_title_bar_layout', '!=', 'hide' )
));

array_unshift($tmp_page_title, $page_title_layout);

LASF::createSection( $prefix_opts, array(
    'title'  => esc_html_x('Page Title Bar', 'admin-view', 'skudmart'),
    'icon'   => 'fa fa-sliders',
    'fields' => $tmp_page_title
) );

/**
 * Footer Section
 */
$footer_link = sprintf('<a href="%s">%s</a>', add_query_arg(array('post_type' => 'elementor_library', 'elementor_library_type' => 'footer'), admin_url('edit.php')), esc_html__('here', 'skudmart'));
LASF::createSection( $prefix_opts, array(
    'title'  => esc_html_x('Footer', 'admin-view', 'skudmart'),
    'icon'   => 'fa fa-arrow-down',
    'fields' => array(
        array(
            'id'            => 'hide_footer',
            'type'          => 'button_set',
            'default'       => 'no',
            'title'         => esc_html_x('Hide Footer', 'admin-view', 'skudmart'),
            'options'       => Skudmart_Options::get_config_radio_opts(false)
        ),
        array(
            'id'            => 'footer_layout',
            'type'          => 'select',
            'default'       => '',
            'title'         => esc_html_x('Footer Layout', 'admin-view', 'skudmart'),
            'placeholder'   => esc_html_x('Select a layout', 'admin-view', 'skudmart'),
            'subtitle'      => sprintf(__('You can manage footer layout on %s', 'skudmart'), $footer_link ),
            'options'       => 'posts',
            'query_args'  => array(
                'post_type'         => 'elementor_library',
                'posts_per_page'    => -1,
                'post_status'       => 'publish',
                'nopaging'          => true,
                'order'             => 'ASC',
                'tax_query' => array(
                    array(
                        'taxonomy' => 'elementor_library_type',
                        'field' => 'slug',
                        'terms' => 'footer'
                    )
                )
            )
        )
    )
) );