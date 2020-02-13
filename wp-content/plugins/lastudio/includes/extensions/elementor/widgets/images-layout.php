<?php
namespace LaStudio_Element\Widgets;

if (!defined('WPINC')) {
    die;
}

// Elementor Classes
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Group_Control_Background;
use Elementor\Repeater;
use Elementor\Scheme_Color;
use Elementor\Scheme_Typography;
use Elementor\Utils;
use Elementor\Modules\DynamicTags\Module as TagsModule;

/**
 * Horizontal_Timeline Widget
 */
class Images_Layout extends LA_Widget_Base {

    public function get_name() {
        return 'lastudio-images-layout';
    }

    protected function get_widget_title() {
        return esc_html__( 'Images Layout', 'lastudio' );
    }

    public function get_icon() {
        return 'lastudioelements-icon-21';
    }

    public function get_style_depends() {
        return [
            'lastudio-images-layout-elm'
        ];
    }

    public function get_script_depends() {
        return [
            'imagesloaded',
            'lastudio-salvattore',
            'lastudio'
        ];
    }

    /**
     * [$item_counter description]
     * @var integer
     */
    public $item_counter = 0;

    protected function _register_controls() {

        $css_scheme = apply_filters(
            'LaStudioElement/images-layout/css-scheme',
            array(
                'instance'          => '.lastudio-images-layout',
                'list_container'    => '.lastudio-images-layout__list',
                'item'              => '.lastudio-images-layout__item',
                'inner'             => '.lastudio-images-layout__inner',
                'image_wrap'        => '.lastudio-images-layout__image',
                'image_instance'    => '.lastudio-images-layout__image-instance',
                'content_wrap'      => '.lastudio-images-layout__content',
                'icon'              => '.lastudio-images-layout__icon',
                'title'             => '.lastudio-images-layout__title',
                'desc'              => '.lastudio-images-layout__desc',
                'button'            => '.lastudio-images-layout__button',
            )
        );

        $this->start_controls_section(
            'section_settings',
            array(
                'label' => esc_html__( 'Settings', 'lastudio' ),
            )
        );

        $this->add_control(
            'layout_type',
            array(
                'label'   => esc_html__( 'Layout type', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'masonry',
                'options' => array(
                    'masonry' => esc_html__( 'Masonry', 'lastudio' ),
                    'grid'    => esc_html__( 'Grid', 'lastudio' ),
                    'list'    => esc_html__( 'List', 'lastudio' ),
                ),
            )
        );

        $this->add_responsive_control(
            'columns',
            array(
                'label'   => esc_html__( 'Columns', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 3,
                'options' => lastudio_elementor_tools_get_select_range( 6 ),
                'condition' => array(
                    'layout_type' => array( 'masonry', 'grid' ),
                ),
            )
        );

        $this->add_responsive_control(
            'item_height',
            array(
                'label' => esc_html__( 'Item Height', 'lastudio' ),
                'type'  => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'min' => 100,
                        'max' => 1000,
                    ),
                ),
                'condition' => array(
                    'layout_type' => 'grid',
                ),
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['image_instance'] => 'height: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_items_data',
            array(
                'label' => esc_html__( 'Items', 'lastudio' ),
            )
        );

        $repeater = new Repeater();

        $repeater->add_control(
            'item_image',
            array(
                'label'   => esc_html__( 'Image', 'lastudio' ),
                'type'    => Controls_Manager::MEDIA,
                'default' => array(
                    'url' => Utils::get_placeholder_image_src(),
                ),
                'dynamic' => array( 'active' => true ),
            )
        );

        $repeater->add_control(
            'item_icon',
            array(
                'label'       => esc_html__( 'Icon', 'lastudio' ),
                'type'        => Controls_Manager::ICON,
                'label_block' => true,
                'file'        => ''
            )
        );

        $repeater->add_control(
            'item_title',
            array(
                'label'   => esc_html__( 'Title', 'lastudio' ),
                'type'    => Controls_Manager::TEXT,
                'dynamic' => array( 'active' => true ),
            )
        );

        $repeater->add_control(
            'item_desc',
            array(
                'label'   => esc_html__( 'Description', 'lastudio' ),
                'type'    => Controls_Manager::TEXTAREA,
                'dynamic' => array( 'active' => true ),
            )
        );

        $repeater->add_control(
            'item_link_type',
            array(
                'label'   => esc_html__( 'Link type', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'lightbox',
                'options' => array(
                    'lightbox' => esc_html__( 'Lightbox', 'lastudio' ),
                    'external' => esc_html__( 'External', 'lastudio' ),
                ),
            )
        );

        $repeater->add_control(
            'item_url',
            array(
                'label'   => esc_html__( 'External Link', 'lastudio' ),
                'type'    => Controls_Manager::TEXT,
                'default' => '#',
                'condition' => array(
                    'item_link_type' => 'external',
                ),
                'dynamic' => array(
                    'active' => true,
                    'categories' => array(
                        TagsModule::POST_META_CATEGORY,
                        TagsModule::URL_CATEGORY,
                    ),
                ),
            )
        );

        $repeater->add_control(
            'item_target',
            array(
                'label'        => esc_html__( 'Open external link in new window', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'return_value' => '_blank',
                'default'      => '',
                'condition'    => array(
                    'item_link_type' => 'external',
                ),
            )
        );
        $repeater->add_control(
            'item_css_class',
            array(
                'label'   => esc_html__( 'Item CSS class', 'lastudio' ),
                'type'    => Controls_Manager::TEXT
            )
        );

        $this->add_control(
            'image_list',
            array(
                'type'        => Controls_Manager::REPEATER,
                'fields'      => array_values( $repeater->get_controls() ),
                'default'     => array(
                    array(
                        'item_image'       => array(
                            'url' => Utils::get_placeholder_image_src(),
                        ),
                        'item_title'       => esc_html__( 'Image #1', 'lastudio' ),
                        'item_desc'        => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'lastudio' ),
                        'item_url'         => '#',
                        'item_target'      => '',
                    ),
                    array(
                        'item_image'       => array(
                            'url' => Utils::get_placeholder_image_src(),
                        ),
                        'item_title'       => esc_html__( 'Image #2', 'lastudio' ),
                        'item_desc'        => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'lastudio' ),
                        'item_url'         => '#',
                        'item_target'      => '',
                    ),
                    array(
                        'item_image'       => array(
                            'url' => Utils::get_placeholder_image_src(),
                        ),
                        'item_title'       => esc_html__( 'Image #3', 'lastudio' ),
                        'item_desc'        => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'lastudio' ),
                        'item_url'         => '#',
                        'item_target'      => '',
                    ),
                    array(
                        'item_image'       => array(
                            'url' => Utils::get_placeholder_image_src(),
                        ),
                        'item_title'       => esc_html__( 'Image #4', 'lastudio' ),
                        'item_desc'        => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'lastudio' ),
                        'item_url'         => '#',
                        'item_target'      => '',
                    ),
                    array(
                        'item_image'       => array(
                            'url' => Utils::get_placeholder_image_src(),
                        ),
                        'item_title'       => esc_html__( 'Image #5', 'lastudio' ),
                        'item_desc'        => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'lastudio' ),
                        'item_url'         => '#',
                        'item_target'      => '',
                    ),
                    array(
                        'item_image'       => array(
                            'url' => Utils::get_placeholder_image_src(),
                        ),
                        'item_title'       => esc_html__( 'Image #6', 'lastudio' ),
                        'item_desc'        => esc_html__( 'Lorem ipsum dolor sit amet, consectetur adipiscing elit.', 'lastudio' ),
                        'item_url'         => '#',
                        'item_target'      => '',
                    ),
                ),
                'title_field' => '{{{ item_title }}}',
            )
        );

        $this->add_control(
            'title_html_tag',
            array(
                'label'   => esc_html__( 'Title HTML Tag', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'options' => array(
                    'h1'   => esc_html__( 'H1', 'lastudio' ),
                    'h2'   => esc_html__( 'H2', 'lastudio' ),
                    'h3'   => esc_html__( 'H3', 'lastudio' ),
                    'h4'   => esc_html__( 'H4', 'lastudio' ),
                    'h5'   => esc_html__( 'H5', 'lastudio' ),
                    'h6'   => esc_html__( 'H6', 'lastudio' ),
                    'div'  => esc_html__( 'div', 'lastudio' ),
                    'span' => esc_html__( 'span', 'lastudio' ),
                    'p'    => esc_html__( 'p', 'lastudio' ),
                ),
                'default' => 'h5',
                'separator' => 'before',
            )
        );

        $this->end_controls_section();

        /**
         * General Style Section
         */
        $this->start_controls_section(
            'section_images_layout_general_style',
            array(
                'label'      => esc_html__( 'General', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->add_responsive_control(
            'item_margin',
            array(
                'label' => esc_html__( 'Items Margin', 'lastudio' ),
                'type'  => Controls_Manager::SLIDER,
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['inner']          => 'margin: {{SIZE}}{{UNIT}};',
                    '{{WRAPPER}} ' . $css_scheme['list_container'] => 'margin: -{{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'        => 'item_border',
                'label'       => esc_html__( 'Border', 'lastudio' ),
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} ' . $css_scheme['inner'],
            )
        );

        $this->add_responsive_control(
            'item_border_radius',
            array(
                'label'      => __( 'Border Radius', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['inner'] => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'item_padding',
            array(
                'label'      => __( 'Padding', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['inner'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name' => 'item_shadow',
                'exclude' => array(
                    'box_shadow_position',
                ),
                'selector' => '{{WRAPPER}} ' . $css_scheme['inner'],
            )
        );

        $this->end_controls_section();

        /**
         * Icon Style Section
         */
        $this->start_controls_section(
            'section_images_layout_icon_style',
            array(
                'label'      => esc_html__( 'Icon', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->add_control(
            'icon_color',
            array(
                'label' => esc_html__( 'Icon Color', 'lastudio' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['icon'] . ' i' => 'color: {{VALUE}}',
                ),
            )
        );

        $this->add_control(
            'icon_bg_color',
            array(
                'label' => esc_html__( 'Icon Background Color', 'lastudio' ),
                'type' => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['icon'] . ' .lastudio-images-layout-icon-inner' => 'background-color: {{VALUE}}',
                ),
            )
        );

        $this->add_responsive_control(
            'icon_font_size',
            array(
                'label'      => esc_html__( 'Icon Font Size', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array(
                    'px', 'em' ,
                ),
                'range'      => array(
                    'px' => array(
                        'min' => 18,
                        'max' => 200,
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['icon'] . ' i' => 'font-size: {{SIZE}}{{UNIT}}',
                ),
            )
        );

        $this->add_responsive_control(
            'icon_size',
            array(
                'label'      => esc_html__( 'Icon Box Size', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array(
                    'px', 'em', '%',
                ),
                'range'      => array(
                    'px' => array(
                        'min' => 18,
                        'max' => 200,
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['icon'] . ' .lastudio-images-layout-icon-inner' => 'width: {{SIZE}}{{UNIT}}; height: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'        => 'icon_border',
                'label'       => esc_html__( 'Border', 'lastudio' ),
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} ' . $css_scheme['icon'] . ' .lastudio-images-layout-icon-inner',
            )
        );

        $this->add_control(
            'icon_box_border_radius',
            array(
                'label'      => esc_html__( 'Border Radius', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['icon'] . ' .lastudio-images-layout-icon-inner' => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'icon_box_margin',
            array(
                'label'      => __( 'Margin', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['icon'] . ' .lastudio-images-layout-icon-inner' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'icon_box_shadow',
                'selector' => '{{WRAPPER}} ' . $css_scheme['icon'] . ' .lastudio-images-layout-icon-inner',
            )
        );


        $this->add_control(
            'icon_horizontal_alignment',
            array(
                'label'   => esc_html__( 'Horizontal Alignment', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'center',
                'options' => array(
                    'flex-start'    => esc_html__( 'Left', 'lastudio' ),
                    'center'        => esc_html__( 'Center', 'lastudio' ),
                    'flex-end'      => esc_html__( 'Right', 'lastudio' ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} '. $css_scheme['icon'] => 'justify-content: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'icon_vertical_alignment',
            array(
                'label'   => esc_html__( 'Vertical Alignment', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'center',
                'options' => array(
                    'flex-start'    => esc_html__( 'Top', 'lastudio' ),
                    'center'        => esc_html__( 'Center', 'lastudio' ),
                    'flex-end'      => esc_html__( 'Bottom', 'lastudio' ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} '. $css_scheme['icon'] => 'align-items: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        /**
         * Title Style Section
         */
        $this->start_controls_section(
            'section_images_layout_title_style',
            array(
                'label'      => esc_html__( 'Title', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->add_control(
            'title_color',
            array(
                'label'  => esc_html__( 'Color', 'lastudio' ),
                'type'   => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['title'] => 'color: {{VALUE}}',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'title_typography',
                'scheme'   => Scheme_Typography::TYPOGRAPHY_3,
                'selector' => '{{WRAPPER}} ' . $css_scheme['title'],
            )
        );

        $this->add_responsive_control(
            'title_padding',
            array(
                'label'      => __( 'Padding', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['title'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'title_margin',
            array(
                'label'      => __( 'Margin', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['title'] => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'title_alignment',
            array(
                'label'   => esc_html__( 'Alignment', 'lastudio' ),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'center',
                'options' => array(
                    'left'    => array(
                        'title' => esc_html__( 'Left', 'lastudio' ),
                        'icon'  => 'eicon-h-align-left',
                    ),
                    'center' => array(
                        'title' => esc_html__( 'Center', 'lastudio' ),
                        'icon'  => 'eicon-h-align-center',
                    ),
                    'right' => array(
                        'title' => esc_html__( 'Right', 'lastudio' ),
                        'icon'  => 'eicon-h-align-right',
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['title'] => 'text-align: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        /**
         * Description Style Section
         */
        $this->start_controls_section(
            'section_images_layout_desc_style',
            array(
                'label'      => esc_html__( 'Description', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->add_control(
            'desc_color',
            array(
                'label'  => esc_html__( 'Color', 'lastudio' ),
                'type'   => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['desc'] => 'color: {{VALUE}}',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'desc_typography',
                'scheme'   => Scheme_Typography::TYPOGRAPHY_3,
                'selector' => '{{WRAPPER}} ' . $css_scheme['desc'],
            )
        );

        $this->add_responsive_control(
            'desc_padding',
            array(
                'label'      => __( 'Padding', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['desc'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'desc_margin',
            array(
                'label'      => __( 'Margin', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['desc'] => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'desc_alignment',
            array(
                'label'   => esc_html__( 'Alignment', 'lastudio' ),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'center',
                'options' => array(
                    'left'    => array(
                        'title' => esc_html__( 'Left', 'lastudio' ),
                        'icon'  => 'eicon-h-align-left',
                    ),
                    'center' => array(
                        'title' => esc_html__( 'Center', 'lastudio' ),
                        'icon'  => 'eicon-h-align-center',
                    ),
                    'right' => array(
                        'title' => esc_html__( 'Right', 'lastudio' ),
                        'icon'  => 'eicon-h-align-right',
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['desc'] => 'text-align: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        /**
         * Overlay Style Section
         */
        $this->start_controls_section(
            'section_images_layout_overlay_style',
            array(
                'label'      => esc_html__( 'Overlay', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name'     => 'overlay_background',
                'fields_options' => array(
                    'color' => array(
                        'scheme' => array(
                            'type'  => Scheme_Color::get_type(),
                            'value' => Scheme_Color::COLOR_2,
                        ),
                    ),
                ),
                'selector' => '{{WRAPPER}} ' . $css_scheme['content_wrap'] . ':before',
            )
        );

        $this->add_control(
            'overlay_opacity',
            array(
                'label'    => esc_html__( 'Opacity', 'lastudio' ),
                'type'     => Controls_Manager::NUMBER,
                'default'  => 0.6,
                'min'      => 0,
                'max'      => 1,
                'step'     => 0.1,
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['content_wrap'] . ':before' => 'opacity: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'overlay_paddings',
            array(
                'label'      => __( 'Padding', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['content_wrap'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        /**
         * Order Style Section
         */
        $this->start_controls_section(
            'section_order_style',
            array(
                'label'      => esc_html__( 'Content Order and Alignment', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->add_control(
            'item_title_order',
            array(
                'label'   => esc_html__( 'Title Order', 'lastudio' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 1,
                'min'     => 1,
                'max'     => 2,
                'step'    => 1,
                'selectors' => array(
                    '{{WRAPPER}} '. $css_scheme['title'] => 'order: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'item_content_order',
            array(
                'label'   => esc_html__( 'Content Order', 'lastudio' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 2,
                'min'     => 1,
                'max'     => 2,
                'step'    => 1,
                'selectors' => array(
                    '{{WRAPPER}} '. $css_scheme['desc'] => 'order: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'item_content_alignment',
            array(
                'label'   => esc_html__( 'Content Vertical Alignment', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'flex-end',
                'options' => array(
                    'flex-start'    => esc_html__( 'Top', 'lastudio' ),
                    'center'        => esc_html__( 'Center', 'lastudio' ),
                    'flex-end'      => esc_html__( 'Bottom', 'lastudio' ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} '. $css_scheme['content_wrap']  => 'justify-content: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();
    }
    /**
     * Get loop image html
     *
     */

    public function get_loop_image_item() {

        $image_data = $this->__loop_image_item('item_image', '', false);

        if(!empty($image_data)){
            $srcset = sprintf('width="%d" height="%d" srcset="%s"', $image_data[1], $image_data[2], 'data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw==');
            return sprintf( apply_filters('LaStudioElement/images-layout/image-format', '<img src="%1$s" data-src="%1$s" alt="" class="la-lazyload-image %2$s" %3$s>'), $image_data[0], 'lastudio-images-layout__image-instance' , $srcset);
        }

        return '';
    }

    /**
     * Get loop image html
     *
     */
    protected function __loop_image_item( $key = '', $format = '%s', $html_return = true ) {
        $item = $this->__processed_item;
        $params = [];

        if ( ! array_key_exists( $key, $item ) ) {
            return false;
        }

        $image_item = $item[ $key ];

        if ( ! empty( $image_item['id'] ) ) {
            $image_data = wp_get_attachment_image_src( $image_item['id'], 'full' );

            $params[] = apply_filters('lastudio_wp_get_attachment_image_url', $image_data[0]);
            $params[] = $image_data[1];
            $params[] = $image_data[2];
        } else {
            $params[] = $image_item['url'];
            $params[] = 1200;
            $params[] = 800;
        }

        if($html_return){
            return vsprintf( $format, $params );
        }
        else{
            return $params;
        }
    }

    protected function render() {

        $this->__context = 'render';

        $this->__open_wrap();
        include $this->__get_global_template( 'index' );
        $this->__close_wrap();
    }

    protected function _content_template() {}

}