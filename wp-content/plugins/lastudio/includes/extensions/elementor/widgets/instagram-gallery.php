<?php
namespace LaStudio_Element\Widgets;

if (!defined('WPINC')) {
    die;
}

// Elementor Classes
use Elementor\Controls_Manager;
use Elementor\Group_Control_Border;
use Elementor\Group_Control_Background;
use Elementor\Group_Control_Box_Shadow;
use Elementor\Group_Control_Typography;
use Elementor\Scheme_Color;
use Elementor\Scheme_Typography;
use Elementor\Modules\DynamicTags\Module as TagsModule;
use LaStudio_Element\Controls\Group_Control_Box_Style;

/**
 * Instagram_Gallery Widget
 */
class Instagram_Gallery extends LA_Widget_Base {

    /**
     * Instagram API-server URL.
     *
     * @since 1.0.0
     * @var string
     */
    private $api_url = 'https://www.instagram.com/';

    /**
     * Alternative Instagram API-server URL.
     *
     * @var string
     */
    private $alt_api_url = 'https://apinsta.herokuapp.com/';

    /**
     * Official Instagram API-server URL.
     *
     * @var string
     */
    private $official_api_url = 'https://api.instagram.com/v1/';

    /**
     * Access token.
     *
     * @var string
     */
    private $access_token = null;

    /**
     * Request config
     *
     * @var array
     */
    public $config = array();

    public function get_name() {
        return 'lastudio-instagram-gallery';
    }

    protected function get_widget_title() {
        return esc_html__( 'Instagram', 'lastudio' );
    }

    public function get_icon() {
        return 'lastudioelements-icon-30';
    }

    public function get_style_depends() {
        return [
            'lastudio-instagram-gallery-elm'
        ];
    }

    protected function _register_controls() {

        $css_scheme = apply_filters(
            'LaStudioElement/instagram-gallery/css-scheme',
            array(
                'instance'       => '.lastudio-instagram-gallery__instance',
                'image_instance' => '.lastudio-instagram-gallery__image',
                'item'           => '.lastudio-instagram-gallery__item',
                'inner'          => '.lastudio-instagram-gallery__inner',
                'content'        => '.lastudio-instagram-gallery__content',
                'caption'        => '.lastudio-instagram-gallery__caption',
                'meta'           => '.lastudio-instagram-gallery__meta',
                'meta_item'      => '.lastudio-instagram-gallery__meta-item',
                'meta_icon'      => '.lastudio-instagram-gallery__meta-icon',
                'meta_label'     => '.lastudio-instagram-gallery__meta-label',
                'slick_list'       => '.lastudio-portfolio .slick-list',
            )
        );

        $this->start_controls_section(
            'section_instagram_settings',
            array(
                'label' => esc_html__( 'Instagram Settings', 'lastudio' ),
            )
        );

        $this->add_control(
            'endpoint',
            array(
                'label'   => esc_html__( 'What to display', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'hashtag',
                'options' => array(
                    'hashtag'  => esc_html__( 'Tagged Photos', 'lastudio' ),
                    'self'     => esc_html__( 'My Photos', 'lastudio' ),
                ),
            )
        );

        $this->add_control(
            'hashtag',
            array(
                'label' => esc_html__( 'Hashtag (enter without `#` symbol)', 'lastudio' ),
                'type'  => Controls_Manager::TEXT,
                'condition' => array(
                    'endpoint' => 'hashtag',
                ),
                'dynamic' => array(
                    'active' => true,
                    'categories' => array(
                        TagsModule::POST_META_CATEGORY,
                    ),
                ),
            )
        );

//		$this->add_control(
//			'self',
//			array(
//				'label' => esc_html__( 'Username', 'lastudio' ),
//				'type'  => Controls_Manager::TEXT,
//				'condition' => array(
//					'endpoint' => 'self',
//				),
//			)
//		);

        if ( ! $this->get_access_token() ) {
            $this->add_control(
                'set_access_token',
                array(
                    'type' => Controls_Manager::RAW_HTML,
                    'raw'  => sprintf(
                        esc_html__( 'Please set Instagram Access Token on the %1$s.', 'lastudio' ),
                        '<a target="_blank" href="'.admin_url('themes.php?page=theme_options').'">' . esc_html__( 'settings page', 'lastudio' ) . '</a>'
                    ),
                    'condition' => array(
                        'endpoint' => 'self',
                    ),
                )
            );
        }

        $this->add_control(
            'cache_timeout',
            array(
                'label'   => esc_html__( 'Cache Timeout', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'hour',
                'options' => array(
                    'none'   => esc_html__( 'None', 'lastudio' ),
                    'minute' => esc_html__( 'Minute', 'lastudio' ),
                    'hour'   => esc_html__( 'Hour', 'lastudio' ),
                    'day'    => esc_html__( 'Day', 'lastudio' ),
                    'week'   => esc_html__( 'Week', 'lastudio' ),
                ),
            )
        );

        $this->add_control(
            'photo_size',
            array(
                'label'   => esc_html__( 'Photo Size', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'high',
                'options' => array(
                    'thumbnail' => esc_html__( 'Thumbnail (150x150)', 'lastudio' ),
                    'low'       => esc_html__( 'Low (320x320)', 'lastudio' ),
                    'standard'  => esc_html__( 'Standard (640x640)', 'lastudio' ),
                    'high'      => esc_html__( 'High (original)', 'lastudio' ),
                ),
            )
        );

        $this->add_control(
            'posts_counter',
            array(
                'label'   => esc_html__( 'Number of instagram posts', 'lastudio' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 6,
                'min'     => 1,
                'max'     => 18,
                'step'    => 1,
            )
        );

        $this->add_control(
            'post_link',
            array(
                'label'        => esc_html__( 'Enable linking photos', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'post_caption',
            array(
                'label'        => esc_html__( 'Enable caption', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'post_caption_length',
            array(
                'label'   => esc_html__( 'Caption length', 'lastudio' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 50,
                'min'     => 1,
                'max'     => 300,
                'step'    => 1,
                'condition' => array(
                    'post_caption' => 'yes',
                ),
            )
        );

        $this->add_control(
            'post_comments_count',
            array(
                'label'        => esc_html__( 'Enable Comments Count', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->add_control(
            'post_likes_count',
            array(
                'label'        => esc_html__( 'Enable Likes Count', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'yes',
                'default'      => 'yes',
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_settings',
            array(
                'label' => esc_html__( 'Layout Settings', 'lastudio' ),
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

        $this->end_controls_section();
        /**
         * Slick settings
         */
        $this->start_controls_section(
            'section_carousel',
            array(
                'label' => esc_html__( 'Carousel', 'lastudio' ),
                'condition' => array(
                    'layout_type' => array(
                        'grid',
                        'list'
                    )
                )
            )
        );

        $this->add_control(
            'carousel_enabled',
            array(
                'label'        => esc_html__( 'Enable Carousel', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'yes',
                'default'      => '',
            )
        );

        $this->add_control(
            'slides_to_scroll',
            array(
                'label'     => esc_html__( 'Slides to Scroll', 'lastudio' ),
                'type'      => Controls_Manager::SELECT,
                'default'   => '1',
                'options'   => lastudio_elementor_tools_get_select_range( 6 ),
                'condition' => array(
                    'columns!' => '1',
                ),
            )
        );

        $this->add_control(
            'arrows',
            array(
                'label'        => esc_html__( 'Show Arrows Navigation', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'true',
                'default'      => 'true',
            )
        );

        $this->add_control(
            'prev_arrow',
            array(
                'label'   => esc_html__( 'Prev Arrow Icon', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'lastudioicon-left-arrow',
                'options' => lastudio_elementor_tools_get_nextprev_arrows_list('prev'),
                'condition' => array(
                    'arrows' => 'true',
                ),
            )
        );

        $this->add_control(
            'next_arrow',
            array(
                'label'   => esc_html__( 'Next Arrow Icon', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'lastudioicon-right-arrow',
                'options' => lastudio_elementor_tools_get_nextprev_arrows_list('next'),
                'condition' => array(
                    'arrows' => 'true',
                ),
            )
        );

        $this->add_control(
            'dots',
            array(
                'label'        => esc_html__( 'Show Dots Navigation', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'true',
                'default'      => '',
            )
        );

        $this->add_control(
            'pause_on_hover',
            array(
                'label'        => esc_html__( 'Pause on Hover', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'true',
                'default'      => '',
            )
        );

        $this->add_control(
            'autoplay',
            array(
                'label'        => esc_html__( 'Autoplay', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'true',
                'default'      => 'true',
            )
        );

        $this->add_control(
            'autoplay_speed',
            array(
                'label'     => esc_html__( 'Autoplay Speed', 'lastudio' ),
                'type'      => Controls_Manager::NUMBER,
                'default'   => 5000,
                'condition' => array(
                    'autoplay' => 'true',
                ),
            )
        );

        $this->add_control(
            'infinite',
            array(
                'label'        => esc_html__( 'Infinite Loop', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'true',
                'default'      => 'true',
            )
        );

        $this->add_control(
            'effect',
            array(
                'label'   => esc_html__( 'Effect', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'slide',
                'options' => array(
                    'slide' => esc_html__( 'Slide', 'lastudio' ),
                    'fade'  => esc_html__( 'Fade', 'lastudio' ),
                ),
                'condition' => array(
                    'columns' => '1',
                ),
            )
        );

        $this->add_control(
            'speed',
            array(
                'label'   => esc_html__( 'Animation Speed', 'lastudio' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 500,
            )
        );

        $this->add_responsive_control(
            'slick_list_padding_left',
            array(
                'label'      => esc_html__( 'Padding Left', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( '%', 'px', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 500,
                    ),
                    '%' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                    'em' => array(
                        'min' => 0,
                        'max' => 20,
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['slick_list'] . '' => 'padding-left: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'slick_list_padding_right',
            array(
                'label'      => esc_html__( 'Padding Right', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( '%', 'px', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 500,
                    ),
                    '%' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                    'em' => array(
                        'min' => 0,
                        'max' => 20,
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['slick_list'] . '' => 'padding-right: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->end_controls_section();

        /**
         * General Style Section
         */
        $this->start_controls_section(
            'section_general_style',
            array(
                'label'      => esc_html__( 'General', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
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
                'default' => [
                    'size' => 300,
                ],
                'condition' => array(
                    'layout_type' => 'grid',
                ),
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['image_instance'] => 'height: {{SIZE}}{{UNIT}};',
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
                    '{{WRAPPER}} ' . $css_scheme['item'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                    '{{WRAPPER}} ' . $css_scheme['instance'] => 'margin-left: -{{LEFT}}{{UNIT}}; margin-right: -{{RIGHT}}{{UNIT}};',
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
         * Caption Style Section
         */
        $this->start_controls_section(
            'section_caption_style',
            array(
                'label'      => esc_html__( 'Caption', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->add_control(
            'caption_color',
            array(
                'label'  => esc_html__( 'Color', 'lastudio' ),
                'type'   => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['caption'] => 'color: {{VALUE}}',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'caption_typography',
                'scheme'   => Scheme_Typography::TYPOGRAPHY_3,
                'selector' => '{{WRAPPER}} ' . $css_scheme['caption'],
            )
        );

        $this->add_responsive_control(
            'caption_padding',
            array(
                'label'      => __( 'Padding', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['caption'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'caption_margin',
            array(
                'label'      => __( 'Margin', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['caption'] => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'caption_width',
            array(
                'label' => esc_html__( 'Caption Width', 'lastudio' ),
                'type'  => Controls_Manager::SLIDER,
                'size_units' => array(
                    'px', 'em', '%',
                ),
                'range'      => array(
                    'px' => array(
                        'min' => 50,
                        'max' => 1000,
                    ),
                    '%' => array(
                        'min' => 0,
                        'max' => 100,
                    ),
                ),
                'default' => [
                    'size'  => 100,
                    'units' => '%'
                ],
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['caption'] => 'max-width: {{SIZE}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'caption_alignment',
            array(
                'label'   => esc_html__( 'Alignment', 'lastudio' ),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'center',
                'options' => array(
                    'flex-start'    => array(
                        'title' => esc_html__( 'Left', 'lastudio' ),
                        'icon'  => 'eicon-h-align-left',
                    ),
                    'center' => array(
                        'title' => esc_html__( 'Center', 'lastudio' ),
                        'icon'  => 'eicon-h-align-center',
                    ),
                    'flex-end' => array(
                        'title' => esc_html__( 'Right', 'lastudio' ),
                        'icon'  => 'eicon-h-align-right',
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['caption'] => 'align-self: {{VALUE}};',
                ),
            )
        );

        $this->add_responsive_control(
            'caption_text_alignment',
            array(
                'label'   => esc_html__( 'Text Alignment', 'lastudio' ),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'center',
                'options' => array(
                    'left'    => array(
                        'title' => esc_html__( 'Left', 'lastudio' ),
                        'icon'  => 'eicon-text-align-left',
                    ),
                    'center' => array(
                        'title' => esc_html__( 'Center', 'lastudio' ),
                        'icon'  => 'eicon-text-align-center',
                    ),
                    'right' => array(
                        'title' => esc_html__( 'Right', 'lastudio' ),
                        'icon'  => 'eicon-text-align-right',
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['caption'] => 'text-align: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        /**
         * Meta Style Section
         */
        $this->start_controls_section(
            'section_meta_style',
            array(
                'label'      => esc_html__( 'Meta', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->add_control(
            'comments_icon',
            array(
                'label'       => esc_html__( 'Comments Icon', 'lastudio' ),
                'type'        => Controls_Manager::ICON,
                'label_block' => true,
                'file'        => ''
            )
        );

        $this->add_control(
            'likes_icon',
            array(
                'label'       => esc_html__( 'Likes Icon', 'lastudio' ),
                'type'        => Controls_Manager::ICON,
                'label_block' => true,
                'file'        => ''
            )
        );

        $this->add_control(
            'meta_icon_color',
            array(
                'label'  => esc_html__( 'Icon Color', 'lastudio' ),
                'type'   => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['meta_icon'] => 'color: {{VALUE}}',
                ),
            )
        );

        $this->add_responsive_control(
            'meta_icon_size',
            array(
                'label'      => esc_html__( 'Icon Size', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array(
                    'px', 'em' ,
                ),
                'range'      => array(
                    'px' => array(
                        'min' => 1,
                        'max' => 100,
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['meta_icon'] . ' i' => 'font-size: {{SIZE}}{{UNIT}}',
                ),
            )
        );

        $this->add_control(
            'meta_label_color',
            array(
                'label'  => esc_html__( 'Text Color', 'lastudio' ),
                'type'   => Controls_Manager::COLOR,
                'selectors' => array(
                    '{{WRAPPER}} ' . $css_scheme['meta_label'] => 'color: {{VALUE}}',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Typography::get_type(),
            array(
                'name'     => 'meta_label_typography',
                'scheme'   => Scheme_Typography::TYPOGRAPHY_1,
                'selector' => '{{WRAPPER}} ' . $css_scheme['meta_label'],
            )
        );

        $this->add_group_control(
            Group_Control_Background::get_type(),
            array(
                'name'     => 'meta_background',
                'selector' => '{{WRAPPER}} ' . $css_scheme['meta'],
            )
        );

        $this->add_responsive_control(
            'meta_padding',
            array(
                'label'      => __( 'Padding', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['meta'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'meta_margin',
            array(
                'label'      => __( 'Margin', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['meta'] => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'meta_item_margin',
            array(
                'label'      => __( 'Item Margin', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['meta_item'] => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Border::get_type(),
            array(
                'name'        => 'meta_border',
                'label'       => esc_html__( 'Border', 'lastudio' ),
                'placeholder' => '1px',
                'default'     => '1px',
                'selector'    => '{{WRAPPER}} ' . $css_scheme['meta'],
            )
        );

        $this->add_responsive_control(
            'meta_radius',
            array(
                'label'      => __( 'Border Radius', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['meta'] => 'border-radius: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Shadow::get_type(),
            array(
                'name'     => 'meta_shadow',
                'selector' => '{{WRAPPER}} ' . $css_scheme['meta'],
            )
        );

        $this->add_responsive_control(
            'meta_alignment',
            array(
                'label'   => esc_html__( 'Alignment', 'lastudio' ),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'center',
                'options' => array(
                    'flex-start'    => array(
                        'title' => esc_html__( 'Left', 'lastudio' ),
                        'icon'  => 'eicon-h-align-left',
                    ),
                    'center' => array(
                        'title' => esc_html__( 'Center', 'lastudio' ),
                        'icon'  => 'eicon-h-align-center',
                    ),
                    'flex-end' => array(
                        'title' => esc_html__( 'Right', 'lastudio' ),
                        'icon'  => 'eicon-h-align-right',
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['meta'] => 'align-self: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        /**
         * Overlay Style Section
         */
        $this->start_controls_section(
            'section_overlay_style',
            array(
                'label'      => esc_html__( 'Overlay', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->add_control(
            'show_on_hover',
            array(
                'label'        => esc_html__( 'Show on hover', 'lastudio' ),
                'type'         => Controls_Manager::SWITCHER,
                'label_on'     => esc_html__( 'Yes', 'lastudio' ),
                'label_off'    => esc_html__( 'No', 'lastudio' ),
                'return_value' => 'yes',
                'default'      => 'yes',
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
                'selector' => '{{WRAPPER}} ' . $css_scheme['content'] . ':before',
            )
        );

        $this->add_responsive_control(
            'overlay_paddings',
            array(
                'label'      => __( 'Padding', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%' ),
                'selectors'  => array(
                    '{{WRAPPER}} ' . $css_scheme['content'] => 'padding: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
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
            'caption_order',
            array(
                'label'   => esc_html__( 'Caption Order', 'lastudio' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 1,
                'min'     => 1,
                'max'     => 4,
                'step'    => 1,
                'selectors' => array(
                    '{{WRAPPER}} '. $css_scheme['caption'] => 'order: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'meta_order',
            array(
                'label'   => esc_html__( 'Meta Order', 'lastudio' ),
                'type'    => Controls_Manager::NUMBER,
                'default' => 2,
                'min'     => 1,
                'max'     => 4,
                'step'    => 1,
                'selectors' => array(
                    '{{WRAPPER}} '. $css_scheme['meta'] => 'order: {{VALUE}};',
                ),
            )
        );

        $this->add_control(
            'cover_alignment',
            array(
                'label'   => esc_html__( 'Cover Content Vertical Alignment', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'center',
                'options' => array(
                    'flex-start'    => esc_html__( 'Top', 'lastudio' ),
                    'center'        => esc_html__( 'Center', 'lastudio' ),
                    'flex-end'      => esc_html__( 'Bottom', 'lastudio' ),
                    'space-between' => esc_html__( 'Space between', 'lastudio' ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} '. $css_scheme['content'] => 'justify-content: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

        /**
         * Arrow Sections
         */

        $this->start_controls_section(
            'section_arrows_style',
            array(
                'label'      => esc_html__( 'Carousel Arrows', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->start_controls_tabs( 'tabs_arrows_style' );

        $this->start_controls_tab(
            'tab_prev',
            array(
                'label' => esc_html__( 'Normal', 'lastudio' ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Style::get_type(),
            array(
                'name'           => 'arrows_style',
                'label'          => esc_html__( 'Arrows Style', 'lastudio' ),
                'selector'       => '{{WRAPPER}} .lastudio-carousel .lastudio-arrow',
                'fields_options' => array(
                    'color' => array(
                        'scheme' => array(
                            'type'  => Scheme_Color::get_type(),
                            'value' => Scheme_Color::COLOR_1,
                        ),
                    ),
                ),
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_next_hover',
            array(
                'label' => esc_html__( 'Hover', 'lastudio' ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Style::get_type(),
            array(
                'name'           => 'arrows_hover_style',
                'label'          => esc_html__( 'Arrows Style', 'lastudio' ),
                'selector'       => '{{WRAPPER}} .lastudio-carousel .lastudio-arrow:hover',
                'fields_options' => array(
                    'color' => array(
                        'scheme' => array(
                            'type'  => Scheme_Color::get_type(),
                            'value' => Scheme_Color::COLOR_1,
                        ),
                    ),
                ),
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'prev_arrow_position',
            array(
                'label'     => esc_html__( 'Prev Arrow Position', 'lastudio' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'prev_vert_position',
            array(
                'label'   => esc_html__( 'Vertical Position by', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'top',
                'options' => array(
                    'top'    => esc_html__( 'Top', 'lastudio' ),
                    'bottom' => esc_html__( 'Bottom', 'lastudio' ),
                ),
            )
        );

        $this->add_responsive_control(
            'prev_top_position',
            array(
                'label'      => esc_html__( 'Top Indent', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => -400,
                        'max' => 400,
                    ),
                    '%' => array(
                        'min' => -100,
                        'max' => 100,
                    ),
                    'em' => array(
                        'min' => -50,
                        'max' => 50,
                    ),
                ),
                'condition' => array(
                    'prev_vert_position' => 'top',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-arrow.prev-arrow' => 'top: {{SIZE}}{{UNIT}}; bottom: auto;',
                ),
            )
        );

        $this->add_responsive_control(
            'prev_bottom_position',
            array(
                'label'      => esc_html__( 'Bottom Indent', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => -400,
                        'max' => 400,
                    ),
                    '%' => array(
                        'min' => -100,
                        'max' => 100,
                    ),
                    'em' => array(
                        'min' => -50,
                        'max' => 50,
                    ),
                ),
                'condition' => array(
                    'prev_vert_position' => 'bottom',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-arrow.prev-arrow' => 'bottom: {{SIZE}}{{UNIT}}; top: auto;',
                ),
            )
        );

        $this->add_control(
            'prev_hor_position',
            array(
                'label'   => esc_html__( 'Horizontal Position by', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'left',
                'options' => array(
                    'left'  => esc_html__( 'Left', 'lastudio' ),
                    'right' => esc_html__( 'Right', 'lastudio' ),
                ),
            )
        );

        $this->add_responsive_control(
            'prev_left_position',
            array(
                'label'      => esc_html__( 'Left Indent', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => -400,
                        'max' => 400,
                    ),
                    '%' => array(
                        'min' => -100,
                        'max' => 100,
                    ),
                    'em' => array(
                        'min' => -50,
                        'max' => 50,
                    ),
                ),
                'condition' => array(
                    'prev_hor_position' => 'left',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-arrow.prev-arrow' => 'left: {{SIZE}}{{UNIT}}; right: auto;',
                ),
            )
        );

        $this->add_responsive_control(
            'prev_right_position',
            array(
                'label'      => esc_html__( 'Right Indent', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => -400,
                        'max' => 400,
                    ),
                    '%' => array(
                        'min' => -100,
                        'max' => 100,
                    ),
                    'em' => array(
                        'min' => -50,
                        'max' => 50,
                    ),
                ),
                'condition' => array(
                    'prev_hor_position' => 'right',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-arrow.prev-arrow' => 'right: {{SIZE}}{{UNIT}}; left: auto;',
                ),
            )
        );

        $this->add_control(
            'next_arrow_position',
            array(
                'label'     => esc_html__( 'Next Arrow Position', 'lastudio' ),
                'type'      => Controls_Manager::HEADING,
                'separator' => 'before',
            )
        );

        $this->add_control(
            'next_vert_position',
            array(
                'label'   => esc_html__( 'Vertical Position by', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'top',
                'options' => array(
                    'top'    => esc_html__( 'Top', 'lastudio' ),
                    'bottom' => esc_html__( 'Bottom', 'lastudio' ),
                ),
            )
        );

        $this->add_responsive_control(
            'next_top_position',
            array(
                'label'      => esc_html__( 'Top Indent', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => -400,
                        'max' => 400,
                    ),
                    '%' => array(
                        'min' => -100,
                        'max' => 100,
                    ),
                    'em' => array(
                        'min' => -50,
                        'max' => 50,
                    ),
                ),
                'condition' => array(
                    'next_vert_position' => 'top',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-arrow.next-arrow' => 'top: {{SIZE}}{{UNIT}}; bottom: auto;',
                ),
            )
        );

        $this->add_responsive_control(
            'next_bottom_position',
            array(
                'label'      => esc_html__( 'Bottom Indent', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => -400,
                        'max' => 400,
                    ),
                    '%' => array(
                        'min' => -100,
                        'max' => 100,
                    ),
                    'em' => array(
                        'min' => -50,
                        'max' => 50,
                    ),
                ),
                'condition' => array(
                    'next_vert_position' => 'bottom',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-arrow.next-arrow' => 'bottom: {{SIZE}}{{UNIT}}; top: auto;',
                ),
            )
        );

        $this->add_control(
            'next_hor_position',
            array(
                'label'   => esc_html__( 'Horizontal Position by', 'lastudio' ),
                'type'    => Controls_Manager::SELECT,
                'default' => 'right',
                'options' => array(
                    'left'  => esc_html__( 'Left', 'lastudio' ),
                    'right' => esc_html__( 'Right', 'lastudio' ),
                ),
            )
        );

        $this->add_responsive_control(
            'next_left_position',
            array(
                'label'      => esc_html__( 'Left Indent', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => -400,
                        'max' => 400,
                    ),
                    '%' => array(
                        'min' => -100,
                        'max' => 100,
                    ),
                    'em' => array(
                        'min' => -50,
                        'max' => 50,
                    ),
                ),
                'condition' => array(
                    'next_hor_position' => 'left',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-arrow.next-arrow' => 'left: {{SIZE}}{{UNIT}}; right: auto;',
                ),
            )
        );

        $this->add_responsive_control(
            'next_right_position',
            array(
                'label'      => esc_html__( 'Right Indent', 'lastudio' ),
                'type'       => Controls_Manager::SLIDER,
                'size_units' => array( 'px', '%', 'em' ),
                'range'      => array(
                    'px' => array(
                        'min' => -400,
                        'max' => 400,
                    ),
                    '%' => array(
                        'min' => -100,
                        'max' => 100,
                    ),
                    'em' => array(
                        'min' => -50,
                        'max' => 50,
                    ),
                ),
                'condition' => array(
                    'next_hor_position' => 'right',
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-arrow.next-arrow' => 'right: {{SIZE}}{{UNIT}}; left: auto;',
                ),
            )
        );

        $this->end_controls_section();

        $this->start_controls_section(
            'section_dots_style',
            array(
                'label'      => esc_html__( 'Carousel Dots', 'lastudio' ),
                'tab'        => Controls_Manager::TAB_STYLE,
                'show_label' => false,
            )
        );

        $this->start_controls_tabs( 'tabs_dots_style' );

        $this->start_controls_tab(
            'tab_dots_normal',
            array(
                'label' => esc_html__( 'Normal', 'lastudio' ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Style::get_type(),
            array(
                'name'           => 'dots_style',
                'label'          => esc_html__( 'Dots Style', 'lastudio' ),
                'selector'       => '{{WRAPPER}} .lastudio-carousel .lastudio-slick-dots li span',
                'fields_options' => array(
                    'color' => array(
                        'scheme' => array(
                            'type'  => Scheme_Color::get_type(),
                            'value' => Scheme_Color::COLOR_3,
                        ),
                    ),
                ),
                'exclude' => array(
                    'box_font_color',
                    'box_font_size',
                ),
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_dots_hover',
            array(
                'label' => esc_html__( 'Hover', 'lastudio' ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Style::get_type(),
            array(
                'name'           => 'dots_style_hover',
                'label'          => esc_html__( 'Dots Style', 'lastudio' ),
                'selector'       => '{{WRAPPER}} .lastudio-carousel .lastudio-slick-dots li span:hover',
                'fields_options' => array(
                    'color' => array(
                        'scheme' => array(
                            'type'  => Scheme_Color::get_type(),
                            'value' => Scheme_Color::COLOR_1,
                        ),
                    ),
                ),
                'exclude' => array(
                    'box_font_color',
                    'box_font_size',
                ),
            )
        );

        $this->end_controls_tab();

        $this->start_controls_tab(
            'tab_dots_active',
            array(
                'label' => esc_html__( 'Active', 'lastudio' ),
            )
        );

        $this->add_group_control(
            Group_Control_Box_Style::get_type(),
            array(
                'name'           => 'dots_style_active',
                'label'          => esc_html__( 'Dots Style', 'lastudio' ),
                'selector'       => '{{WRAPPER}} .lastudio-carousel .lastudio-slick-dots li.slick-active span',
                'fields_options' => array(
                    'color' => array(
                        'scheme' => array(
                            'type'  => Scheme_Color::get_type(),
                            'value' => Scheme_Color::COLOR_4,
                        ),
                    ),
                ),
                'exclude' => array(
                    'box_font_color',
                    'box_font_size',
                ),
            )
        );

        $this->end_controls_tab();

        $this->end_controls_tabs();

        $this->add_control(
            'dots_gap',
            array(
                'label' => esc_html__( 'Gap', 'lastudio' ),
                'type' => Controls_Manager::SLIDER,
                'default' => array(
                    'size' => 5,
                    'unit' => 'px',
                ),
                'range' => array(
                    'px' => array(
                        'min' => 0,
                        'max' => 50,
                    ),
                ),
                'selectors' => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-slick-dots li' => 'padding-left: {{SIZE}}{{UNIT}}; padding-right: {{SIZE}}{{UNIT}}',
                ),
                'separator' => 'before',
            )
        );

        $this->add_responsive_control(
            'dots_margin',
            array(
                'label'      => esc_html__( 'Dots Box Margin', 'lastudio' ),
                'type'       => Controls_Manager::DIMENSIONS,
                'size_units' => array( 'px', '%', 'em' ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-slick-dots' => 'margin: {{TOP}}{{UNIT}} {{RIGHT}}{{UNIT}} {{BOTTOM}}{{UNIT}} {{LEFT}}{{UNIT}};',
                ),
            )
        );

        $this->add_responsive_control(
            'dots_alignment',
            array(
                'label'   => esc_html__( 'Alignment', 'lastudio' ),
                'type'    => Controls_Manager::CHOOSE,
                'default' => 'center',
                'options' => array(
                    'flex-start' => array(
                        'title' => esc_html__( 'Left', 'lastudio' ),
                        'icon'  => 'eicon-h-align-left',
                    ),
                    'center' => array(
                        'title' => esc_html__( 'Center', 'lastudio' ),
                        'icon'  => 'eicon-h-align-center',
                    ),
                    'flex-end' => array(
                        'title' => esc_html__( 'Right', 'lastudio' ),
                        'icon'  => 'eicon-h-align-right',
                    ),
                ),
                'selectors'  => array(
                    '{{WRAPPER}} .lastudio-carousel .lastudio-slick-dots' => 'justify-content: {{VALUE}};',
                ),
            )
        );

        $this->end_controls_section();

    }

    protected function render() {

        $this->__context = 'render';

        $this->__open_wrap();
        include $this->__get_global_template( 'index' );
        $this->__close_wrap();
    }

    /**
     * Render gallery html.
     *
     * @return string
     */
    public function render_gallery() {
        $settings = $this->get_settings_for_display();

        if ( 'hashtag' === $settings['endpoint'] && empty( $settings['hashtag'] ) ) {
            return print esc_html__( 'Please, enter #hashtag.', 'lastudio' );
        }

        if ( 'self' === $settings['endpoint'] && ! $this->get_access_token() ) {
            return print esc_html__( 'Please, enter Access Token.', 'lastudio' );
        }

        $html = '';
        $col_class = '';

        // Endpoint.
        $endpoint = $this->sanitize_endpoint();

        switch ( $settings['cache_timeout'] ) {
            case 'none':
                $cache_timeout = 1;
                break;

            case 'minute':
                $cache_timeout = MINUTE_IN_SECONDS;
                break;

            case 'hour':
                $cache_timeout = HOUR_IN_SECONDS;
                break;

            case 'day':
                $cache_timeout = DAY_IN_SECONDS;
                break;

            case 'week':
                $cache_timeout = WEEK_IN_SECONDS;
                break;

            default:
                $cache_timeout = HOUR_IN_SECONDS;
                break;
        }

        $this->config = array(
            'endpoint'            => $endpoint,
            'target'              => ( 'hashtag' === $endpoint ) ? sanitize_text_field( $settings[ $endpoint ] ) : 'users',
            'posts_counter'       => $settings['posts_counter'],
            'post_link'           => filter_var( $settings['post_link'], FILTER_VALIDATE_BOOLEAN ),
            'photo_size'          => $settings['photo_size'],
            'post_caption'        => filter_var( $settings['post_caption'], FILTER_VALIDATE_BOOLEAN ),
            'post_caption_length' => ! empty( $settings['post_caption_length'] ) ? $settings['post_caption_length'] : 50,
            'post_comments_count' => filter_var( $settings['post_comments_count'], FILTER_VALIDATE_BOOLEAN ),
            'post_likes_count'    => filter_var( $settings['post_likes_count'], FILTER_VALIDATE_BOOLEAN ),
            'cache_timeout'       => $cache_timeout,
        );

        $posts = $this->get_posts( $this->config );

        if ( ! empty( $posts ) ) {

            foreach ( $posts as $post_data ) {
                $item_html   = '';
                $link        = ( 'hashtag' === $endpoint ) ? sprintf( $this->get_post_url(), $post_data['link'] ) : $post_data['link'];
                $the_image   = $this->the_image( $post_data );
                $the_caption = $this->the_caption( $post_data );
                $the_meta    = $this->the_meta( $post_data );

                $item_html = sprintf(
                    '<div class="lastudio-instagram-gallery__media">%1$s</div><div class="lastudio-instagram-gallery__content">%2$s%3$s</div>',
                    $the_image,
                    $the_caption,
                    $the_meta
                );

                if ( $this->config['post_link'] ) {
                    $link_format = '<a class="lastudio-instagram-gallery__link" href="%s" target="_blank" rel="nofollow">%s</a>';
                    $link_format = apply_filters( 'LaStudioElement/instagram-gallery/link-format', $link_format );

                    $item_html = sprintf( $link_format, esc_url( $link ), $item_html );
                }

                $html .= sprintf( '<div class="loop__item grid-item lastudio-instagram-gallery__item %s"><div class="lastudio-instagram-gallery__inner">%s</div></div>', $col_class, $item_html );
            }

        } else {
            $html .= sprintf(
                '<div class="loop__item grid-item lastudio-instagram-gallery__item">%s</div>',
                esc_html__( 'Posts not found', 'lastudio' )
            );
        }

        echo $html;
    }

    /**
     * Display a HTML link with image.
     *
     * @since  1.0.0
     * @param  array $item Item photo data.
     * @return string
     */
    public function the_image( $item ) {

        $size = $this->get_settings_for_display( 'photo_size' );

        $thumbnail_resources = $item['thumbnail_resources'];

        if ( array_key_exists( $size, $thumbnail_resources ) ) {
            $width = $thumbnail_resources[ $size ]['config_width'];
            $height = $thumbnail_resources[ $size ]['config_height'];
            $post_photo_url = $thumbnail_resources[ $size ]['src'];
        } else {
            $width = isset( $item['dimensions']['width'] ) ? $item['dimensions']['width'] : '';
            $height = isset( $item['dimensions']['height'] ) ? $item['dimensions']['height'] : '';
            $post_photo_url = isset( $item['image'] ) ? $item['image'] : '';
        }

        if ( empty( $post_photo_url ) ) {
            return '';
        }

        $srcset = 'srcset="data:image/gif;base64,R0lGODlhAQABAIAAAP///wAAACH5BAEAAAAALAAAAAABAAEAAAICRAEAOw=="';

        $photo_format = apply_filters( 'LaStudioElement/instagram-gallery/photo-format', '<img class="lastudio-instagram-gallery__image la-lazyload-image" src="%1$s" data-src="%1$s" width="%2$s" height="%3$s" %4$s alt="">');

        $image = sprintf( $photo_format, esc_url( $post_photo_url ),  esc_attr($width), esc_attr($height), $srcset);

        return $image;
    }

    /**
     * Display a caption.
     *
     * @since  1.0.0
     * @param  array $item Item photo data.
     * @return string
     */
    public function the_caption( $item ) {

        if ( ! $this->config['post_caption'] || empty( $item['caption'] ) ) {
            return;
        }

        $format = apply_filters(
            'LaStudioElement/instagram-gallery/the-caption-format', '<div class="lastudio-instagram-gallery__caption">%s</div>'
        );

        return sprintf( $format, $item['caption'] );
    }

    /**
     * Display a meta.
     *
     * @since  1.0.0
     * @param  array $item Item photo data.
     * @return string
     */
    public function the_meta( $item ) {

        if ( ! $this->config['post_comments_count'] && ! $this->config['post_likes_count'] ) {
            return;
        }

        $meta_html = '';

        if ( $this->config['post_comments_count'] ) {
            $meta_html .= sprintf(
                '<div class="lastudio-instagram-gallery__meta-item lastudio-instagram-gallery__comments-count"><span class="lastudio-instagram-gallery__comments-icon lastudio-instagram-gallery__meta-icon"><i class="%s"></i></span><span class="lastudio-instagram-gallery__comments-label lastudio-instagram-gallery__meta-label">%s</span></div>',
                $this->get_settings_for_display( 'comments_icon' ),
                $item['comments']
            );
        }

        if ( $this->config['post_likes_count'] ) {
            $meta_html .= sprintf(
                '<div class="lastudio-instagram-gallery__meta-item lastudio-instagram-gallery__likes-count"><span class="lastudio-instagram-gallery__likes-icon lastudio-instagram-gallery__meta-icon"><i class="%s"></i></span><span class="lastudio-instagram-gallery__likes-label lastudio-instagram-gallery__meta-label">%s</span></div>',
                $this->get_settings_for_display( 'likes_icon' ),
                $item['likes']
            );
        }

        $format = apply_filters( 'LaStudioElement/instagram-gallery/the-meta-format', '<div class="lastudio-instagram-gallery__meta">%s</div>' );

        return sprintf( $format, $meta_html );
    }

    /**
     * Retrieve a photos.
     *
     * @since  1.0.0
     * @param  array $config Set of configuration.
     * @return array
     */
    public function get_posts( $config ) {

        $transient_key = md5( $this->get_transient_key() );

        $data = get_transient( $transient_key );

        if ( ! empty( $data ) && 1 !== $config['cache_timeout'] && array_key_exists( 'thumbnail_resources', $data[0] ) ) {
            return $data;
        }

        $response = $this->remote_get( $config );

        if ( is_wp_error( $response ) ) {
            return array();
        }

        $data = ( 'hashtag' === $config['endpoint'] ) ? $this->get_response_data( $response ) : $this->get_response_data_from_official_api( $response );

        if ( empty( $data ) ) {
            return array();
        }

        set_transient( $transient_key, $data, $config['cache_timeout'] );

        return $data;
    }

    /**
     * Retrieve the raw response from the HTTP request using the GET method.
     *
     * @since  1.0.0
     * @return array|WP_Error
     */
    public function remote_get( $config ) {

        $url = $this->get_grab_url( $config );

        $response = wp_remote_get( $url, array(
            'timeout'   => 60,
            'sslverify' => false
        ) );

        $response_code = wp_remote_retrieve_response_code( $response );

        if ( '' === $response_code ) {
            return new \WP_Error;
        }

        $result = json_decode( wp_remote_retrieve_body( $response ), true );

        if ( ! is_array( $result ) ) {
            return new \WP_Error;
        }

        return $result;
    }

    /**
     * Get prepared response data.
     *
     * @param $response
     *
     * @return array
     */
    public function get_response_data( $response ) {

        $key = 'hashtag' == $this->config['endpoint'] ? 'hashtag' : 'user';

        if ( 'hashtag' === $key ) {
            $response = isset( $response['graphql'] ) ? $response['graphql'] : $response;
        }

        $response_items = ( 'hashtag' === $key ) ? $response[ $key ]['edge_hashtag_to_media']['edges'] : $response['graphql'][ $key ]['edge_owner_to_timeline_media']['edges'];

        if ( empty( $response_items ) ) {
            return array();
        }

        $data  = array();
        $nodes = array_slice(
            $response_items,
            0,
            $this->config['posts_counter'],
            true
        );

        foreach ( $nodes as $post ) {

            $_post               = array();
            $_post['link']       = $post['node']['shortcode'];
            $_post['image']      = $post['node']['thumbnail_src'];
            $_post['caption']    = isset( $post['node']['edge_media_to_caption']['edges'][0]['node']['text'] ) ? wp_html_excerpt( $post['node']['edge_media_to_caption']['edges'][0]['node']['text'], $this->config['post_caption_length'], '&hellip;' ) : '';
            $_post['comments']   = $post['node']['edge_media_to_comment']['count'];
            $_post['likes']      = $post['node']['edge_liked_by']['count'];
            $_post['dimensions'] = $post['node']['dimensions'];
            $_post['thumbnail_resources'] = $this->_generate_thumbnail_resources( $post );

            array_push( $data, $_post );
        }

        return $data;
    }

    /**
     * Get prepared response data from official api.
     *
     * @param $response
     *
     * @return array
     */
    public function get_response_data_from_official_api( $response ) {

        $response_items = $response['data'];

        if ( empty( $response_items ) ) {
            return array();
        }

        $data  = array();
        $nodes = array_slice(
            $response_items,
            0,
            $this->config['posts_counter'],
            true
        );

        foreach ( $nodes as $post ) {
            $_post             = array();
            $_post['link']     = $post['link'];
            $_post['caption']  = ! empty( $post['caption']['text'] ) ? wp_html_excerpt( $post['caption']['text'], $this->config['post_caption_length'], '&hellip;' ) : '';
            $_post['comments'] = $post['comments']['count'];
            $_post['likes']    = $post['likes']['count'];
            $_post['thumbnail_resources'] = $this->_generate_thumbnail_resources_from_official_api( $post );

            array_push( $data, $_post );
        }

        return $data;
    }

    /**
     * Generate thumbnail resources.
     *
     * @param $post_data
     *
     * @return array
     */
    public function _generate_thumbnail_resources( $post_data ) {
        $post_data = $post_data['node'];

        $thumbnail_resources = array(
            'thumbnail' => false,
            'low'       => false,
            'standard'  => false,
            'high'      => false,
        );

        if ( is_array( $post_data['thumbnail_resources'] ) && ! empty( $post_data['thumbnail_resources'] ) ) {
            foreach ( $post_data['thumbnail_resources'] as $key => $resources_data ) {

                if ( 150 === $resources_data['config_width'] ) {
                    $thumbnail_resources['thumbnail'] = $resources_data;

                    continue;
                }

                if ( 320 === $resources_data['config_width'] ) {
                    $thumbnail_resources['low'] = $resources_data;

                    continue;
                }

                if ( 640 === $resources_data['config_width'] ) {
                    $thumbnail_resources['standard'] = $resources_data;

                    continue;
                }
            }
        }

        if ( ! empty( $post_data['display_url'] ) ) {
            $thumbnail_resources['high'] = array(
                'src'           => $post_data['display_url'],
                'config_width'  => $post_data['dimensions']['width'],
                'config_height' => $post_data['dimensions']['height'],
            ) ;
        }

        return $thumbnail_resources;
    }

    /**
     * Generate thumbnail resources from official api.
     *
     * @param $post_data
     *
     * @return array
     */
    public function _generate_thumbnail_resources_from_official_api( $post_data ) {
        $thumbnail_resources = array(
            'thumbnail' => false,
            'low'       => false,
            'standard'  => false,
            'high'      => false,
        );

        if ( is_array( $post_data['images'] ) && ! empty( $post_data['images'] ) ) {

            $thumbnails_data = $post_data['images'];

            $thumbnail_resources['thumbnail'] = array(
                'src'           => $thumbnails_data['thumbnail']['url'],
                'config_width'  => $thumbnails_data['thumbnail']['width'],
                'config_height' => $thumbnails_data['thumbnail']['height'],
            );

            $thumbnail_resources['low'] = array(
                'src'           => $thumbnails_data['low_resolution']['url'],
                'config_width'  => $thumbnails_data['low_resolution']['width'],
                'config_height' => $thumbnails_data['low_resolution']['height'],
            );

            $thumbnail_resources['standard'] = array(
                'src'           => $thumbnails_data['standard_resolution']['url'],
                'config_width'  => $thumbnails_data['standard_resolution']['width'],
                'config_height' => $thumbnails_data['standard_resolution']['height'],
            );

            $thumbnail_resources['high'] = $thumbnail_resources['standard'];
        }

        return $thumbnail_resources;
    }

    /**
     * Retrieve a grab URL.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_grab_url( $config ) {

        if ( 'hashtag' == $config['endpoint'] ) {
            $url = sprintf( $this->get_tags_url(), $config['target'] );
            $url = add_query_arg( array( '__a' => 1 ), $url );

        } else {
            $url = $this->get_self_url();
            $url = add_query_arg( array( 'access_token' => $this->get_access_token() ), $url );
        }

        return $url;
    }

    /**
     * Retrieve a URL for photos by hashtag.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_tags_url() {
        return apply_filters( 'LaStudioElement/instagram-gallery/get-tags-url', $this->api_url . 'explore/tags/%s/' );
    }

    /**
     * Retrieve a URL for self photos.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_self_url() {
        return apply_filters( 'LaStudioElement/instagram-gallery/get-self-url', $this->official_api_url . 'users/self/media/recent/' );
    }

    /**
     * Retrieve a URL for post.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_post_url() {
        return apply_filters( 'LaStudioElement/instagram-gallery/get-post-url', $this->api_url . 'p/%s/' );
    }

    /**
     * sanitize endpoint.
     *
     * @since  1.0.0
     * @return string
     */
    public function sanitize_endpoint() {
        return in_array( $this->get_settings( 'endpoint' ) , array( 'hashtag', 'self' ) ) ? $this->get_settings( 'endpoint' ) : 'hashtag';
    }

    /**
     * Retrieve a photo sizes (in px) by option name.
     *
     * @since  1.0.0
     * @param  string $photo_size Photo size.
     * @return array
     */
    public function _get_relation_photo_size( $photo_size ) {
        switch ( $photo_size ) {

            case 'high':
                $size = array();
                break;

            case 'standard':
                $size = array( 640, 640 );
                break;

            case 'low':
                $size = array( 320, 320 );
                break;

            default:
                $size = array( 150, 150 );
                break;
        }

        return apply_filters( 'LaStudioElement/instagram-gallery/relation-photo-size', $size, $photo_size );
    }

    /**
     * Get transient key.
     *
     * @since  1.0.0
     * @return string
     */
    public function get_transient_key() {
        return sprintf( 'lastudio_elements_instagram_%s_%s_posts_count_%s_caption_%s',
            $this->config['endpoint'],
            $this->config['target'],
            $this->config['posts_counter'],
            $this->config['post_caption_length']
        );
    }

    /**
     * Generate setting json
     *
     * @return string
     */
    public function generate_carousel_setting_json(){
        $settings = $this->get_settings();

        $json_data = '';

        if ( 'yes' !== $settings['carousel_enabled'] ) {
            return $json_data;
        }

        $is_rtl = is_rtl();

        $desktop_col = absint( $settings['columns'] );
        $laptop_col = absint( isset($settings['columns_laptop']) ? $settings['columns_laptop'] : 0 );
        $tablet_col = absint( $settings['columns_tablet'] );
        $tabletportrait_col = absint(  isset($settings['columns_tabletportrait']) ? $settings['columns_tabletportrait'] : 0 );
        $mobile_col = absint( $settings['columns_mobile'] );

        if($laptop_col == 0){
            $laptop_col = $desktop_col;
        }
        if($tablet_col == 0){
            $tablet_col = $laptop_col;
        }
        if($tabletportrait_col == 0){
            $tabletportrait_col = $tablet_col;
        }
        if($mobile_col == 0){
            $mobile_col = 1;
        }

        $slidesToShow = array(
            'desktop'           => $desktop_col,
            'laptop'            => $laptop_col,
            'tablet'            => $tablet_col,
            'tabletportrait'    => $tabletportrait_col,
            'mobile'            => $mobile_col
        );

        $options = array(
            'slidesToShow'   => $slidesToShow,
            'autoplaySpeed'  => absint( $settings['autoplay_speed'] ),
            'autoplay'       => filter_var( $settings['autoplay'], FILTER_VALIDATE_BOOLEAN ),
            'infinite'       => filter_var( $settings['infinite'], FILTER_VALIDATE_BOOLEAN ),
            'pauseOnHover'   => filter_var( $settings['pause_on_hover'], FILTER_VALIDATE_BOOLEAN ),
            'speed'          => absint( $settings['speed'] ),
            'arrows'         => filter_var( $settings['arrows'], FILTER_VALIDATE_BOOLEAN ),
            'dots'           => filter_var( $settings['dots'], FILTER_VALIDATE_BOOLEAN ),
            'slidesToScroll' => absint( $settings['slides_to_scroll'] ),
            'prevArrow'      => lastudio_elementor_tools_get_carousel_arrow( array( $settings['prev_arrow'], 'prev-arrow slick-prev' ) ),
            'nextArrow'      => lastudio_elementor_tools_get_carousel_arrow( array( $settings['next_arrow'], 'next-arrow slick-next' ) ),
            'rtl'            => $is_rtl,
        );

        if ( 1 === absint( $settings['columns'] ) ) {
            $options['fade'] = ( 'fade' === $settings['effect'] );
        }

        $json_data = htmlspecialchars( json_encode( $options ) );

        return $json_data;

    }

    /**
     * Get access token.
     *
     * @return string
     */
    public function get_access_token() {

        if ( ! $this->access_token ) {
            $this->access_token = '';
        }


        return apply_filters('LaStudioElement/instagram-gallery/api', $this->access_token);
    }

}