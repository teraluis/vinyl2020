<?php
/**
 * This file includes dynamic css
 *
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

$css_primary_color = skudmart_get_option('primary_color', '#D2A35C');
$css_secondary_color = skudmart_get_option('secondary_color', '#1d1d1d');
$css_three_color = skudmart_get_option('three_color', '#979797');
$css_border_color = skudmart_get_option('border_color', '#ebebeb');

$device_lists = array('mobile', 'mobile_landscape', 'tablet', 'laptop', 'desktop');

$all_styles = array(
    'mobile' => array(),
    'mobile_landscape' => array(),
    'tablet' => array(),
    'laptop' => array(),
    'desktop' => array()
);
/**
 * Footer Bars
 */

$mb_footer_bar_visible = skudmart_get_option('mb_footer_bar_visible', '600');

echo '@media(min-width: '.esc_attr($mb_footer_bar_visible).'px){ body.enable-footer-bars{ padding-bottom: 0} .footer-handheld-footer-bar { opacity: 0 !important; visibility: hidden !important } }';

/**
 * Body Background
 */

$body_background = skudmart_get_option('body_background');
if(!empty(skudmart_array_filter_recursive($body_background))){
    echo skudmart_render_background_style_from_setting($body_background, 'body.skudmart-body');
}

/**
 * Main_Space
 */
$main_space = skudmart_get_theme_option_by_context('main_space');
if(!empty($main_space)){
    foreach ($main_space as $screen => $value ){
        $_css = '';
        $unit = !empty($value['unit'])? $value['unit']: 'px';
        $value_atts = shortcode_atts(array(
            'top' => '',
            'right' => '',
            'bottom' => '',
            'left' => '',
        ), $value);
        foreach ($value_atts as $k => $v){
            if($v !== ''){
                $_css .= 'padding-' . $k . ':' . $v . $unit . ';';
            }
        }
        if(!empty($_css)) {
            $all_styles[$screen][] = '#main #content-wrap{'. $_css .'}';
        }
    }
}

/**
 * Page Title Bar
 */
$page_title_bar_func = 'skudmart_get_option';
if( skudmart_string_to_bool(skudmart_get_theme_option_by_context('page_title_bar_style', 'no')) ){
    $page_title_bar_func = 'skudmart_get_theme_option_by_context';
}
if( skudmart_is_blog() ){
    if( skudmart_string_to_bool( skudmart_get_option('blog_post_override_page_title_bar', 'off') ) ) {
        $page_title_bar_func = 'skudmart_get_theme_option_by_context';
    }
}
elseif ( function_exists('is_product') && is_product() ){
    if( skudmart_string_to_bool( skudmart_get_option('single_product_override_page_title_bar', 'off') ) ) {
        $page_title_bar_func = 'skudmart_get_theme_option_by_context';
    }
}
elseif ( post_type_exists('la_portfolio') && is_singular('la_portfolio') ){
    if( skudmart_string_to_bool( skudmart_get_option('single_portfolio_override_page_title_bar', 'off') ) ) {
        $page_title_bar_func = 'skudmart_get_theme_option_by_context';
    }
}
elseif ( is_singular('post') ){
    if( skudmart_string_to_bool( skudmart_get_option('single_post_override_page_title_bar', 'off') ) ) {
        $page_title_bar_func = 'skudmart_get_theme_option_by_context';
    }
}
elseif ( function_exists('is_woocommerce') && is_woocommerce() ) {
    if( skudmart_string_to_bool( skudmart_get_option('woo_override_page_title_bar', 'off') ) ) {
        $page_title_bar_func = 'skudmart_get_theme_option_by_context';
    }
}
elseif ( post_type_exists('la_portfolio') && (is_post_type_archive('la_portfolio') || ( is_tax() && is_tax( get_object_taxonomies( 'la_portfolio' ) ) )) ) {
    if( skudmart_string_to_bool( skudmart_get_option('archive_portfolio_override_page_title_bar', 'off') ) ) {
        $page_title_bar_func = 'skudmart_get_theme_option_by_context';
    }
}

$page_title_bar_space = call_user_func($page_title_bar_func, 'page_title_bar_space');

if(!empty($page_title_bar_space)){
    foreach ($page_title_bar_space as $screen => $value ){
        $_css = '';
        $unit = !empty($value['unit'])? $value['unit']: 'px';
        $value_atts = shortcode_atts(array(
            'top' => '',
            'right' => '',
            'bottom' => '',
            'left' => '',
        ), $value);
        foreach ($value_atts as $k => $v){
            if($v !== ''){
                $_css .= 'padding-' . $k . ':' . $v . $unit . ';';
            }
        }
        if(!empty($_css)) {
            $all_styles[$screen][] = '.section-page-header .page-header-inner{'. $_css .'}';
        }
    }
}

$page_title_bar_border = call_user_func($page_title_bar_func, 'page_title_bar_border');
$page_title_bar_background = call_user_func($page_title_bar_func, 'page_title_bar_background');

$page_title_bar_heading_color = call_user_func($page_title_bar_func, 'page_title_bar_heading_color', $css_secondary_color);
$page_title_bar_text_color = call_user_func($page_title_bar_func, 'page_title_bar_text_color', $css_secondary_color);
$page_title_bar_link_color = call_user_func($page_title_bar_func, 'page_title_bar_link_color', $css_secondary_color);
$page_title_bar_link_hover_color = call_user_func($page_title_bar_func, 'page_title_bar_link_hover_color', $css_primary_color);


if(!empty(skudmart_array_filter_recursive($page_title_bar_border))){
    echo skudmart_render_border_style_from_setting($page_title_bar_border, '.section-page-header');
}

if(!empty(skudmart_array_filter_recursive($page_title_bar_background))){
    echo skudmart_render_background_style_from_setting($page_title_bar_background, '.section-page-header');
}

/**
 * Build Typography - Page Header
 */
$page_title_bar_heading_fonts = call_user_func($page_title_bar_func, 'page_title_bar_heading_fonts');
$page_title_bar_breadcrumb_fonts = call_user_func($page_title_bar_func, 'page_title_bar_breadcrumb_fonts');
foreach ($device_lists as $screen){

    $_css = skudmart_render_typography_style_from_setting( $page_title_bar_heading_fonts, '.section-page-header .page-title', $screen );
    if(!empty($_css)) {
        $all_styles[$screen][] = $_css;
    }

    $_css = skudmart_render_typography_style_from_setting( $page_title_bar_breadcrumb_fonts, '.section-page-header .site-breadcrumbs', $screen );
    if(!empty($_css)) {
        $all_styles[$screen][] = $_css;
    }

}

if(!empty($page_title_bar_heading_color)){
    echo '.section-page-header .page-title { color: '.esc_attr($page_title_bar_heading_color).' }';
}

if(!empty($page_title_bar_text_color)){
    echo '.section-page-header { color: '.esc_attr($page_title_bar_text_color).' }';
}

if(!empty($page_title_bar_link_color)){
    echo '.section-page-header a { color: '.esc_attr($page_title_bar_link_color).' }';
}
if(!empty($page_title_bar_link_hover_color)){
    echo '.section-page-header a:hover { color: '.esc_attr($page_title_bar_link_hover_color).' }';
}

/**
 * Popup Style
 */
$popup_background = skudmart_get_option('popup_background');
if(!empty(skudmart_array_filter_recursive($popup_background))){
    echo skudmart_render_background_style_from_setting($popup_background, '.open-newsletter-popup .lightcase-inlineWrap');
}


/**
 * Shop Item Space
 */
$shop_item_space = skudmart_get_option('shop_item_space');
if(!empty($shop_item_space)){
    foreach ($shop_item_space as $screen => $value ){
        $_css = '';
        $_css2 = '';
        $unit = !empty($value['unit'])? $value['unit']: 'px';

        $value_atts = shortcode_atts(array(
            'top' => '',
            'right' => '',
            'bottom' => '',
            'left' => ''
        ), $value);


        foreach ($value_atts as $k => $v){
            if($v !== ''){
                $_css .= 'padding-' . $k . ':' . $v . $unit . ';';
                if($k == 'left' || $k == 'right'){
                    $_css2 .= 'margin-' . $k . ':-' . $v . $unit . ';';
                }
            }
        }

        if(!empty($_css)) {
            $all_styles[$screen][] = '.la-shop-products .ul_products.products{'. $_css2 .'}';
            $all_styles[$screen][] = '.la-shop-products .ul_products.products li.product_item{'. $_css .'}';
        }
    }
}
/**
 * Blog Item Image Height
 */
$blog_item_space = skudmart_get_option('blog_item_space');
if(!empty($blog_item_space)){
    foreach ($blog_item_space as $screen => $value ){
        $_css = '';
        $_css2 = '';
        $unit = !empty($value['unit'])? $value['unit']: 'px';

        $value_atts = shortcode_atts(array(
            'top' => '',
            'right' => '',
            'bottom' => '',
            'left' => '',
        ), $value);
        foreach ($value_atts as $k => $v){
            if($v !== ''){
                $_css .= 'padding-' . $k . ':' . $v . $unit . ';';
                if($k == 'left' || $k == 'right'){
                    $_css2 .= 'margin-' . $k . ':-' . $v . $unit . ';';
                }
            }
        }
        if(!empty($_css)) {
            $all_styles[$screen][] = '.lastudio-posts.blog__entries{'. $_css2 .'}';
            $all_styles[$screen][] = '.lastudio-posts.blog__entries .loop__item{'. $_css .'}';
        }
    }
}

$blog_thumbnail_height_mode = skudmart_get_option('blog_thumbnail_height_mode', 'original');
$blog_thumbnail_height_custom = skudmart_get_option('blog_thumbnail_height_custom', '70%');
$blog_thumbnail_height = '70%';

switch ($blog_thumbnail_height_mode){
    case '1-1':
        $blog_thumbnail_height = '100%';
        break;
    case '4-3':
        $blog_thumbnail_height = '75%';
        break;
    case '3-4':
        $blog_thumbnail_height = '133.34%';
        break;
    case '16-9':
        $blog_thumbnail_height = '56.25%';
        break;
    case '9-16':
        $blog_thumbnail_height = '177.78%';
        break;
    case 'custom':
        $blog_thumbnail_height = $blog_thumbnail_height_custom;
        break;
}
if($blog_thumbnail_height_mode != 'original'){
    $all_styles['mobile'][] = '.lastudio-posts.blog__entries .post-thumbnail .blog_item--thumbnail, .lastudio-posts.blog__entries .post-thumbnail .blog_item--thumbnail .slick-slide .sinmer{ padding-bottom: '.$blog_thumbnail_height.'}';
}

/**
 * Build Typography
 */

$typography_selectors = array(
    'body_font_family'                  => 'body',
    'headings_font_family'              => 'h1,h2,h3,h4,h5,h6,.theme-heading, .widget-title, .comments-title, .comment-reply-title, .entry-title',
    'heading1_font_family'              => 'h1',
    'heading2_font_family'              => 'h2',
    'heading3_font_family'              => 'h4',
    'heading4_font_family'              => 'h4',
    'blog_entry_title_font_family'      => '.lastudio-posts.blog__entries .entry-title',
    'blog_entry_meta_font_family'       => '.lastudio-posts.blog__entries .post-meta',
    'blog_entry_content_font_family'    => '.lastudio-posts.blog__entries .entry-excerpt',
    'blog_post_meta_font_family'        => '.single-post-article > .post-meta__item, .single-post-article > .post-meta .post-meta__item',
    'blog_post_content_font_family'     => 'body:not(.page-use-builder) .single-post-article > .entry',
);

foreach ($device_lists as $screen){
    foreach ($typography_selectors as $opt_key => $typography_selector ){
        $_css = skudmart_render_typography_style_from_setting( skudmart_get_option($opt_key), $typography_selector, $screen );

        if(!empty($_css)) {
            $all_styles[$screen][] = $_css;
        }
    }
}

/**
 * Build Typography - Custom Selector
 */
$extra_typography = skudmart_get_option('extra_typography');
if(!empty($extra_typography)){
    foreach ($extra_typography as $item){
        if(!empty($item['selector']) && !empty($item['fonts'])){
            $css_custom_selector = rtrim(trim($item['selector']), ',');
            if(!empty($css_custom_selector)){
                foreach ($device_lists as $screen){
                    $_css = skudmart_render_typography_style_from_setting( $item['fonts'], $css_custom_selector, $screen );
                    if(!empty($_css)) {
                        $all_styles[$screen][] = $_css;
                    }
                }
            }
        }
    }
}

/**
 * Print the styles
 */
/**
 * MOBILE FIRST
 */
if(!empty($all_styles['mobile'])){
    echo join('', $all_styles['mobile']);
}

/**
 * MOBILE LANDSCAPE AND TABLET PORTRAIT
 */
if(!empty($all_styles['mobile_landscape'])){
    echo '@media (min-width: 600px) {';
    echo join('', $all_styles['mobile_landscape']);
    echo '}';
}

/**
 * TABLET LANDSCAPE
 */
if(!empty($all_styles['tablet'])){
    echo '@media (min-width: 800px) {';
    echo join('', $all_styles['tablet']);
    echo '}';
}

/**
 * LAPTOP LANDSCAPE
 */
if(!empty($all_styles['laptop'])){
    echo '@media (min-width: 1300px) {';
    echo join('', $all_styles['laptop']);
    echo '}';
}

/**
 * DESKTOP LANDSCAPE
 */
if(!empty($all_styles['desktop'])){
    echo '@media (min-width: 1600px) {';
    echo join('', $all_styles['desktop']);
    echo '}';
}
?>
.la-ajax-searchform.searching .search-form .search-button:before{
    border-top-color: <?php echo esc_attr($css_primary_color); ?>;
    border-bottom-color: <?php echo esc_attr($css_primary_color); ?>;
}

.pagination_ajax_loadmore a:hover {
    background-color: <?php echo esc_attr($css_primary_color); ?>;
    color: #fff;
}

.tparrows.arrow-01:hover{
    background-color: <?php echo esc_attr($css_primary_color); ?>;
}

.lastudio-carousel .lastudio-arrow{
color: <?php echo esc_attr($css_primary_color); ?>;
border-color: <?php echo esc_attr($css_primary_color); ?>;
}
.lastudio-carousel .lastudio-arrow:hover{
background-color: <?php echo esc_attr($css_primary_color); ?>;
border-color: <?php echo esc_attr($css_primary_color); ?>;
color: #fff;
}

.la-isotope-loading span{
box-shadow: 2px 2px 1px <?php echo esc_attr($css_primary_color); ?>;
}
table th,
table td{
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.gallery-caption {
color: <?php echo esc_attr($css_secondary_color); ?>;
}

a:hover {
color: <?php echo esc_attr($css_primary_color); ?>;
}

a.light:hover {
color: <?php echo esc_attr($css_primary_color); ?>;
}
blockquote {
border-color: <?php echo esc_attr($css_secondary_color); ?>;
}

hr {
border-color: <?php echo esc_attr($css_border_color); ?>;
}

form input[type="text"],
form input[type="password"],
form input[type="email"],
form input[type="url"],
form input[type="date"],
form input[type="month"],
form input[type="time"],
form input[type="datetime"],
form input[type="datetime-local"],
form input[type="week"],
form input[type="number"],
form input[type="search"],
form input[type="tel"],
form input[type="color"],
form select,
form textarea {
color: <?php echo esc_attr($css_secondary_color); ?>;
border-color: <?php echo esc_attr($css_border_color); ?>;
}

form input:not([type]) {
border-color: <?php echo esc_attr($css_border_color); ?>;
}

select {
border-color: <?php echo esc_attr($css_border_color); ?>;
}

form legend {
border-color: <?php echo esc_attr($css_border_color); ?>;
}

input[type="button"],
input[type="reset"],
input[type="submit"],
button[type="submit"],
.button {
background-color: <?php echo esc_attr($css_secondary_color); ?>;
}

input[type="button"]:hover,
input[type="reset"]:hover,
input[type="submit"]:hover,
button[type="submit"]:hover,
.button:hover {
background-color: <?php echo esc_attr($css_primary_color); ?>;
border-color: <?php echo esc_attr($css_primary_color); ?>;
}


.lahb-wrap .lahb-nav-wrap .menu li.current ul li a:hover,
.lahb-wrap .lahb-nav-wrap .menu ul.sub-menu li.current > a,
.lahb-wrap .lahb-nav-wrap .menu ul li.menu-item:hover > a {
color: <?php echo esc_attr($css_primary_color); ?>;
}

.lahb-nav-wrap .menu > li.current > a {
color: <?php echo esc_attr($css_primary_color); ?>;
}
.lahb-modal-login #user-logged .author-avatar img {
border-color: <?php echo esc_attr($css_primary_color); ?>;
}

.la-sharing-single-posts .social--sharing {
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.section-related-posts .related-posts-heading {
border-bottom-color: <?php echo esc_attr($css_border_color); ?>;
}

.widget-title {
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.widget_calendar table #today {
background-color: <?php echo esc_attr($css_primary_color); ?>;
}

.widget_recent_entries .pr-item {
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.widget_recent_entries .pr-item--right .post-date {
color: <?php echo esc_attr($css_three_color); ?>;
}

.widget_product_tag_cloud:not(.la_product_tag_cloud) a{
border-color: <?php echo esc_attr($css_border_color); ?>;
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.widget_product_tag_cloud:not(.la_product_tag_cloud) .active a,
.widget_product_tag_cloud:not(.la_product_tag_cloud) a:hover{
border-color: <?php echo esc_attr($css_secondary_color); ?>;
background-color: <?php echo esc_attr($css_secondary_color); ?>;
color: #fff;
}

.la-pagination ul .page-numbers {
color: <?php echo esc_attr($css_secondary_color); ?>;
}


.comments-title,
.comment-reply-title {
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.comment-list .comment-container:before {
border-color: <?php echo esc_attr($css_border_color); ?>;
}


.commentlist .comment-text{
    color: <?php echo esc_attr($css_secondary_color); ?>;
}

.commentlist .woocommerce-review__published-date{
    color: <?php echo esc_attr($css_three_color); ?>;
}

.search-form .search-button:hover {
color: <?php echo esc_attr($css_primary_color); ?>;
}

.searchform-fly-overlay.searched {
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.lastudio-posts .lastudio-more-wrap .lastudio-more {
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.lastudio-posts .lastudio-more-wrap .lastudio-more:hover {
background-color: <?php echo esc_attr($css_primary_color); ?>;
border-color: <?php echo esc_attr($css_primary_color); ?>;
}

.lastudio-posts .post-meta {
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.lastudio-advance-carousel-layout-icon .lastudio-carousel__item .lastudio-carousel__item-link:before {
background-color: <?php echo esc_attr($css_primary_color); ?>;
}

.lastudio-advance-carousel-layout-icon .lastudio-carousel__item .lastudio-carousel__icon {
color: <?php echo esc_attr($css_primary_color); ?>;
}
.lastudio-team-member__item .lastudio-images-layout__link:after {
background-color: <?php echo esc_attr($css_primary_color); ?>;
}

.lastudio-team-member__socials .item--social a:hover {
background-color: <?php echo esc_attr($css_primary_color); ?>;
}

.preset-type-7.lastudio-team-member .lastudio-team-member__inner-box {
color: <?php echo esc_attr($css_three_color); ?>;
}

.preset-type-7.lastudio-team-member .lastudio-team-member__socials {
color: <?php echo esc_attr($css_three_color); ?>;
}

.preset-type-7.lastudio-team-member .lastudio-team-member__item:hover .lastudio-team-member__inner-box {
background-color: <?php echo esc_attr($css_primary_color); ?>;
}

.preset-type-8.lastudio-team-member .item--social a:hover {
color: <?php echo esc_attr($css_primary_color); ?>;
}

.playout-grid.preset-type-4 .lastudio-portfolio__button {
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.playout-grid.preset-type-4 .lastudio-portfolio__button:hover {
background-color: <?php echo esc_attr($css_primary_color); ?>;
color: #fff;
}

.playout-grid.preset-type-6 .lastudio-portfolio__item:hover .lastudio-portfolio__button {
color: <?php echo esc_attr($css_primary_color); ?>;
}

.playout-grid.preset-type-7 .lastudio-portfolio__item:hover .lastudio-portfolio__button {
color: <?php echo esc_attr($css_primary_color); ?>;
}

.post-navigation .blog_pn_nav-meta {
color: <?php echo esc_attr($css_three_color); ?>;
}

.lastudio-portfolio.preset-list-type-1 .lastudio-arrow {
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.lastudio-portfolio.preset-list-type-1 .lastudio-arrow:hover {
background-color: <?php echo esc_attr($css_secondary_color); ?>;
color: #fff;
}

.has-skudmart-theme-primary-color {
color: <?php echo esc_attr($css_primary_color); ?>;
}

.has-skudmart-theme-secondary-color {
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.has-skudmart-theme-primary-background-color {
background-color: <?php echo esc_attr($css_primary_color); ?>;
}

.has-skudmart-theme-secondary-background-color {
background-color: <?php echo esc_attr($css_secondary_color); ?>;
}

.select2-container .select2-selection--single{
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.woocommerce-product-rating .woocommerce-review-link{
color: <?php echo esc_attr($css_three_color); ?>;
}

.woocommerce-message .button:hover,
.woocommerce-error .button:hover,
.woocommerce-info .button:hover{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.wc-toolbar .wc-view-toggle button.active{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.wc-toolbar .lasf-custom-dropdown button{
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.wc-toolbar .lasf-custom-dropdown ul{
border-color: <?php echo esc_attr($css_border_color); ?>;
}
.wc-toolbar .lasf-custom-dropdown ul li{
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.swatch-wrapper.selected .swatch-anchor{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.open-advanced-shop-filter .wc-toolbar-container .btn-advanced-shop-filter{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.widget_price_filter .ui-slider .ui-slider-range{
background-color: <?php echo esc_attr($css_secondary_color); ?>;
}

.sidebar-inner .product-categories ul{
color: <?php echo esc_attr($css_three_color); ?>;
}
.sidebar-inner .product-categories .current-cat > a,
.sidebar-inner .product-categories .active > a{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.widget_layered_nav .woocommerce-widget-layered-nav-list li a:before{
border-color: <?php echo esc_attr($css_three_color); ?>;
}
.widget_layered_nav .woocommerce-widget-layered-nav-list li:hover a:before,
.widget_layered_nav .woocommerce-widget-layered-nav-list li.chosen a:before{
background-color: <?php echo esc_attr($css_primary_color); ?>;
}

.product_item .item--overlay{
background-color: <?php echo esc_attr($css_secondary_color); ?>;
}

.woocommerce-product-gallery__actions a:hover{
background-color: <?php echo esc_attr($css_secondary_color); ?>;
}
.la-woo-thumbs .la-thumb.slick-current.slick-active{
border-color: <?php echo esc_attr($css_primary_color); ?>;
}

.la-woo-thumbs .slick-arrow:hover{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.product--summary .single-price-wrapper .price{
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.product--summary .product_meta a{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.product--summary .social--sharing a:hover{
background-color: <?php echo esc_attr($css_primary_color); ?>;
border-color: <?php echo esc_attr($css_primary_color); ?>;
}

.wc_tabs_at_bottom .wc-tabs{
border-color: <?php echo esc_attr($css_border_color); ?>;
}
.wc_tabs_at_bottom .wc-tabs li.active > a{
color: <?php echo esc_attr($css_secondary_color); ?>;
}

#tab-additional_information table{
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.custom-product-wrap .block_heading--title{
border-color: <?php echo esc_attr($css_border_color); ?>;
}


.entry-summary .add_compare.added,
.entry-summary .add_wishlist.added{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.entry-summary .add_compare .labtn-text,
.entry-summary .add_wishlist .labtn-text{
background-color: <?php echo esc_attr($css_secondary_color); ?>;
color: #fff;
}
.entry-summary .add_compare .labtn-text:after,
.entry-summary .add_wishlist .labtn-text:after{
border-top-color: <?php echo esc_attr($css_secondary_color); ?>;
opacity: .9;
}


.woocommerce-MyAccount-navigation li:hover a, .woocommerce-MyAccount-navigation li.is-active a{
background-color: <?php echo esc_attr($css_primary_color); ?>;
}

p.lost_password{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.product_list_widget li .amount{
color: <?php echo esc_attr($css_primary_color); ?>;
}

.shop_table.woocommerce-cart-form__contents td.actions:before{
border-top-color: <?php echo esc_attr($css_border_color); ?>;
}

.shop_table td.product-price,
.shop_table td.product-subtotal{
color: <?php echo esc_attr($css_secondary_color); ?>;
}

.shop_table .product-remove .remove:before{
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.shop_table .product-thumbnail a{
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.cart-collaterals .shipping-calculator-form select,
.cart-collaterals .shipping-calculator-form .input-text{
border-color: <?php echo esc_attr($css_border_color); ?>;
}
.cart-collaterals .shipping-calculator-form .select2-container .select2-selection--single{
border-color: <?php echo esc_attr($css_border_color); ?>;
background-color: transparent;
}

.cart-collaterals .lasf-extra-cart--coupon .input-text{
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.shop_table.woocommerce-cart-form__contents thead{
border-color: <?php echo esc_attr($css_border_color); ?>;
}

.shop_table.woocommerce-cart-form__contents tr.cart_item{
border-left-color: <?php echo esc_attr($css_border_color); ?>;
border-right-color: <?php echo esc_attr($css_border_color); ?>;
}
.section-checkout-step span.step-name{
border-top-color: <?php echo esc_attr($css_border_color); ?>;
border-bottom-color: <?php echo esc_attr($css_border_color); ?>;
}
.section-checkout-step span.step-name:before, .section-checkout-step span.step-name:after{
border-top-color: <?php echo esc_attr($css_border_color); ?>;
border-right-color: <?php echo esc_attr($css_border_color); ?>;
}


body.woocommerce-cart .section-checkout-step ul li.step-1 .step-name,
body.woocommerce-checkout .section-checkout-step ul li.step-2 .step-name,
body.woocommerce-cart .section-checkout-step ul li.step-1 .step-name:after,
body.woocommerce-checkout .section-checkout-step ul li.step-2 .step-name:after{
background-color: <?php echo esc_attr($css_primary_color); ?>;
}

form.woocommerce-checkout .woocommerce-checkout-review-order > h3,
form.woocommerce-checkout .woocommerce-billing-fields > h3,
form.woocommerce-checkout .woocommerce-shipping-fields > h3{
border-bottom-color: <?php echo esc_attr($css_border_color); ?>;
}

.la_wishlist_table.shop_table.woocommerce-cart-form__contents {
border-color: <?php echo esc_attr($css_border_color); ?>;
}
.lastudio-testimonials.preset-type-3 .lastudio-testimonials__figure:after{
color: <?php echo esc_attr($css_primary_color); ?>;
}
.lastudio-posts.preset-grid-4 .post-terms{
    background-color: <?php echo esc_attr($css_primary_color); ?>;
}


.has-default-404 .default-404-content .button:hover{
    background-color: <?php echo esc_attr($css_primary_color); ?>;
    border-color: <?php echo esc_attr($css_primary_color); ?>;
    color: #fff;
}

/************** DOKAN *****************/
body .dokan-orders-content .dokan-orders-area ul.order-statuses-filter{
    color: inherit;
}
body .dokan-orders-content .dokan-orders-area ul.order-statuses-filter li.active a{
    color: <?php echo esc_attr($css_primary_color); ?>;
}
.dokan-dashboard-wrap .select2-container .select2-selection--single .select2-selection__rendered,
.dokan-dashboard-wrap .select2-container--default .select2-selection--single .select2-selection__placeholder{
color: <?php echo esc_attr($css_secondary_color); ?>;
}
body.dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.dokan-common-links a:hover,
body.dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li:hover,
body.dokan-dashboard .dokan-dash-sidebar ul.dokan-dashboard-menu li.active{
    background-color: <?php echo esc_attr($css_primary_color); ?>;
}