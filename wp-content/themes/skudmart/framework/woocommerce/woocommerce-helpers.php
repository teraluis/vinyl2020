<?php
/**
 * WooCommerce helper functions
 * This functions only load if WooCommerce is enabled because
 * they should be used within Woo loops only.
 *
 * @package Skudmart WordPress theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if(!function_exists('skudmart_modify_sale_flash')){
    function skudmart_modify_sale_flash( $output ){
        return str_replace('class="onsale"', 'class="la-custom-badge onsale"', $output);
    }
}
add_filter('woocommerce_sale_flash', 'skudmart_modify_sale_flash');

if(!function_exists('skudmart_modify_product_list_preset')){
    function skudmart_modify_product_list_preset( $preset ){
        $preset = array(
            '1' => esc_html__( 'Default', 'skudmart' )
        );
        return $preset;
    }
}
add_filter('LaStudioElement/products/control/list_style', 'skudmart_modify_product_list_preset');

if(!function_exists('skudmart_modify_product_grid_preset')){
    function skudmart_modify_product_grid_preset( $preset ){
        return array(
            '1' => esc_html__( 'Type 1', 'skudmart' ),
            '2' => esc_html__( 'Type 2', 'skudmart' ),
            '3' => esc_html__( 'Type 3', 'skudmart' ),
            '4' => esc_html__( 'Type 4', 'skudmart' ),
            '5' => esc_html__( 'Type 5', 'skudmart' ),
            '6' => esc_html__( 'Type 6', 'skudmart' ),
            'mini' => esc_html__( 'Minimalist', 'skudmart' )
        );
    }
}
add_filter('LaStudioElement/products/control/grid_style', 'skudmart_modify_product_grid_preset');

if(!function_exists('skudmart_modify_product_masonry_preset')){
    function skudmart_modify_product_masonry_preset( $preset ){
        return array(
            '1' => esc_html__( 'Type 1', 'skudmart' ),
            '2' => esc_html__( 'Type 2', 'skudmart' ),
            '3' => esc_html__( 'Type 3', 'skudmart' ),
            '4' => esc_html__( 'Type 4', 'skudmart' ),
            '5' => esc_html__( 'Type 5', 'skudmart' ),
            '6' => esc_html__( 'Type 6', 'skudmart' )
        );
    }
}
add_filter('LaStudioElement/products/control/masonry_style', 'skudmart_modify_product_masonry_preset');

add_filter('woocommerce_product_description_heading', '__return_empty_string');
add_filter('woocommerce_product_additional_information_heading', '__return_empty_string');

if(!function_exists('skudmart_woo_get_product_per_page_array')){
    function skudmart_woo_get_product_per_page_array(){
        $per_page_array = apply_filters('skudmart/filter/get_product_per_page_array', skudmart_get_option('product_per_page_allow', ''));
        if(!empty($per_page_array)){
            $per_page_array = explode(',', $per_page_array);
            $per_page_array = array_map('trim', $per_page_array);
            $per_page_array = array_map('absint', $per_page_array);
            asort($per_page_array);
            return $per_page_array;
        }
        else{
            return array();
        }
    }
}

if(!function_exists('skudmart_woo_get_product_per_page')){
    function skudmart_woo_get_product_per_page(){
        return apply_filters('skudmart/filter/get_product_per_page', skudmart_get_option('product_per_page_default', 9));
    }
}

if(!function_exists('skudmart_get_base_shop_url')){
    function skudmart_get_base_shop_url( ){

        if(function_exists('la_get_base_shop_url')){
            return la_get_base_shop_url();
        }

        return get_post_type_archive_link( 'product' );
    }
}

if(!function_exists('skudmart_get_wc_attribute_for_compare')){
    function skudmart_get_wc_attribute_for_compare(){
        return array(
            'image'         => esc_html__( 'Image', 'skudmart' ),
            'title'         => esc_html__( 'Title', 'skudmart' ),
            'add-to-cart'   => esc_html__( 'Add to cart', 'skudmart' ),
            'price'         => esc_html__( 'Price', 'skudmart' ),
            'sku'           => esc_html__( 'Sku', 'skudmart' ),
            'description'   => esc_html__( 'Description', 'skudmart' ),
            'stock'         => esc_html__( 'Availability', 'skudmart' ),
            'weight'        => esc_html__( 'Weight', 'skudmart' ),
            'dimensions'    => esc_html__( 'Dimensions', 'skudmart' )
        );
    }
}

if(!function_exists('skudmart_get_wc_attribute_taxonomies')){
    function skudmart_get_wc_attribute_taxonomies( ){
        $attributes = array();
        if( function_exists( 'wc_get_attribute_taxonomies' ) && function_exists( 'wc_attribute_taxonomy_name' ) ) {
            $attribute_taxonomies = wc_get_attribute_taxonomies();
            if(!empty($attribute_taxonomies)){
                foreach( $attribute_taxonomies as $attribute ) {
                    $tax = wc_attribute_taxonomy_name( $attribute->attribute_name );
                    $attributes[$tax] = ucfirst( $attribute->attribute_name );
                }
            }
        }

        return $attributes;
    }
}

/**
 * This function allow get property of `woocommerce_loop` inside the loop
 * @since 1.0.0
 * @param string $prop Prop to get.
 * @param string $default Default if the prop does not exist.
 * @return mixed
 */

if(!function_exists('skudmart_get_wc_loop_prop')){
    function skudmart_get_wc_loop_prop( $prop, $default = ''){
        return isset( $GLOBALS['woocommerce_loop'], $GLOBALS['woocommerce_loop'][ $prop ] ) ? $GLOBALS['woocommerce_loop'][ $prop ] : $default;
    }
}

/**
 * This function allow set property of `woocommerce_loop`
 * @since 1.0.0
 * @param string $prop Prop to set.
 * @param string $value Value to set.
 */

if(!function_exists('skudmart_set_wc_loop_prop')){
    function skudmart_set_wc_loop_prop( $prop, $value = ''){
        if(isset($GLOBALS['woocommerce_loop'])){
            $GLOBALS['woocommerce_loop'][ $prop ] = $value;
        }
    }
}
/**
 * Override template product title
 */
if ( ! function_exists( 'woocommerce_template_loop_product_title' ) ) {
    function woocommerce_template_loop_product_title() {
        the_title( sprintf( '<h2 class="product_item--title"><a href="%s">', esc_url( get_the_permalink() ) ), '</a></h3>' );
    }
}


if(!function_exists('skudmart_wc_filter_show_page_title')){
    function skudmart_wc_filter_show_page_title( $show ){
        if( is_singular('product') && skudmart_string_to_bool( skudmart_get_option('product_single_hide_page_title', 'no') ) ){
            return false;
        }
        return $show;
    }
    add_filter('skudmart/filter/show_page_title', 'skudmart_wc_filter_show_page_title', 10, 1 );
}

if(!function_exists('skudmart_wc_filter_show_breadcrumbs')){
    function skudmart_wc_filter_show_breadcrumbs( $show ){
        if( is_singular('product') && skudmart_string_to_bool( skudmart_get_option('product_single_hide_breadcrumb', 'no') ) ){
            return false;
        }
        return $show;
    }
    add_filter('skudmart/filter/show_breadcrumbs', 'skudmart_wc_filter_show_breadcrumbs', 10, 1 );
}


if(!function_exists('skudmart_wc_allow_translate_text_in_swatches')){

    function skudmart_wc_allow_translate_text_in_swatches( $text ){
        return esc_html_x( 'Choose an option', 'front-view', 'skudmart' );
    }

    add_filter('LaStudio/swatches/args/show_option_none', 'skudmart_wc_allow_translate_text_in_swatches', 10, 1);
}

/**
 * Override page title bar from global settings
 * What we need to do now is
 * 1. checking in single content types
 *  1.1) post
 *  1.2) product
 *  1.3) portfolio
 * 2. checking in archives
 *  2.1) shop
 *  2.2) portfolio
 *
 * TIPS: List functions will be use to check
 * `is_product`, `is_single_la_portfolio`, `is_shop`, `is_woocommerce`, `is_product_taxonomy`, `is_archive_la_portfolio`, `is_tax_la_portfolio`
 */

if(!function_exists('skudmart_wc_override_page_title_bar_from_context')){
    function skudmart_wc_override_page_title_bar_from_context( $value, $key ){

        $array_key_allow = array(
            'page_title_bar_style',
            'page_title_bar_layout',
            'page_title_bar_border',
            'page_title_bar_background',
            'page_title_bar_space',
            'page_title_bar_heading_fonts',
            'page_title_bar_breadcrumb_fonts',
            'page_title_bar_heading_color',
            'page_title_bar_text_color',
            'page_title_bar_link_color',
            'page_title_bar_link_hover_color'
        );

        $array_key_alternative = array(
            'page_title_bar_layout',
            'page_title_bar_border',
            'page_title_bar_background',
            'page_title_bar_space',
            'page_title_bar_heading_fonts',
            'page_title_bar_breadcrumb_fonts',
            'page_title_bar_heading_color',
            'page_title_bar_text_color',
            'page_title_bar_link_color',
            'page_title_bar_link_hover_color'
        );

        /**
         * Firstly, we need to check the `$key` input
         */
        if( !in_array($key, $array_key_allow) ){
            return $value;
        }

        /**
         * Secondary, we need to check the `$context` input
         */

        if( !is_woocommerce() ){
            return $value;
        }
        if($key == 'page_title_bar_layout' && function_exists('dokan_is_store_page') && dokan_is_store_page()){
            return 'hide';
        }

        $func_name = 'skudmart_get_post_meta';
        $queried_object_id = get_queried_object_id();

        if( is_product_taxonomy() ){
            $func_name = 'skudmart_get_term_meta';
        }

        if( is_shop() ){
            $queried_object_id = wc_get_page_id( 'shop' );
        }

        if ( 'page_title_bar_layout' == $key ) {
            $page_title_bar_layout = call_user_func($func_name, $queried_object_id, $key);
            if($page_title_bar_layout && $page_title_bar_layout != 'inherit'){
                return $page_title_bar_layout;
            }
        }

        if( 'yes' == call_user_func($func_name ,$queried_object_id, 'page_title_bar_style') && in_array($key, $array_key_alternative) ){
            return $value;
        }

        $key_override = $new_key = false;

        if( is_product() ){
            $key_override = 'single_product_override_page_title_bar';
            $new_key = 'single_product_' . $key;
        }
        elseif ( is_shop() || is_product_taxonomy() ) {
            $key_override = 'woo_override_page_title_bar';
            $new_key = 'woo_' . $key;
        }

        if(false != $key_override){
            if( 'on' == skudmart_get_option($key_override, 'off') ){
                return skudmart_get_option($new_key, $value);
            }
        }

        return $value;
    }

    add_filter('skudmart/filter/get_theme_option_by_context', 'skudmart_wc_override_page_title_bar_from_context', 20, 2);
}

if(!function_exists('skudmart_override_woothumbnail_size_name')){
    function skudmart_override_woothumbnail_size_name( ) {
        return 'shop_thumbnail';
    }
    add_filter('woocommerce_gallery_thumbnail_size', 'skudmart_override_woothumbnail_size_name', 0);
}


if(!function_exists('skudmart_override_woothumbnail_size')){
    function skudmart_override_woothumbnail_size( $size ) {
        if(!function_exists('wc_get_theme_support')){
            return $size;
        }
        $size['width'] = absint( wc_get_theme_support( 'gallery_thumbnail_image_width', 180 ) );
        $cropping      = get_option( 'woocommerce_thumbnail_cropping', '1:1' );

        if ( 'uncropped' === $cropping ) {
            $size['height'] = '';
            $size['crop']   = 0;
        }
        elseif ( 'custom' === $cropping ) {
            $width          = max( 1, get_option( 'woocommerce_thumbnail_cropping_custom_width', '4' ) );
            $height         = max( 1, get_option( 'woocommerce_thumbnail_cropping_custom_height', '3' ) );
            $size['height'] = absint( round( ( $size['width'] / $width ) * $height ) );
            $size['crop']   = 1;
        }
        else {
            $cropping_split = explode( ':', $cropping );
            $width          = max( 1, current( $cropping_split ) );
            $height         = max( 1, end( $cropping_split ) );
            $size['height'] = absint( round( ( $size['width'] / $width ) * $height ) );
            $size['crop']   = 1;
        }

        return $size;
    }
    add_filter('woocommerce_get_image_size_gallery_thumbnail', 'skudmart_override_woothumbnail_size');
}

if(!function_exists('skudmart_override_woothumbnail_single')){
    function skudmart_override_woothumbnail_single( $size ) {
        if(!function_exists('wc_get_theme_support')){
            return $size;
        }
        $size['width'] = absint( wc_get_theme_support( 'single_image_width', get_option( 'woocommerce_single_image_width', 600 ) ) );
        $cropping      = get_option( 'woocommerce_thumbnail_cropping', '1:1' );

        if ( 'uncropped' === $cropping ) {
            $size['height'] = '';
            $size['crop']   = 0;
        }
        elseif ( 'custom' === $cropping ) {
            $width          = max( 1, get_option( 'woocommerce_thumbnail_cropping_custom_width', '4' ) );
            $height         = max( 1, get_option( 'woocommerce_thumbnail_cropping_custom_height', '3' ) );
            $size['height'] = absint( round( ( $size['width'] / $width ) * $height ) );
            $size['crop']   = 1;
        }
        else {
            $cropping_split = explode( ':', $cropping );
            $width          = max( 1, current( $cropping_split ) );
            $height         = max( 1, end( $cropping_split ) );
            $size['height'] = absint( round( ( $size['width'] / $width ) * $height ) );
            $size['crop']   = 1;
        }

        return $size;
    }
    add_filter('woocommerce_get_image_size_single', 'skudmart_override_woothumbnail_single', 0);
}



if ( !function_exists('skudmart_modify_text_woocommerce_catalog_orderby') ){
    function skudmart_modify_text_woocommerce_catalog_orderby( $data ) {
        $data = array(
            'menu_order' => __( 'Sort by Default', 'skudmart' ),
            'popularity' => __( 'Sort by Popularity', 'skudmart' ),
            'rating'     => __( 'Sort by Rated', 'skudmart' ),
            'date'       => __( 'Sort by Latest', 'skudmart' ),
            'price'      => sprintf(__( 'Sort by Price: %s', 'skudmart' ), '<i class="lastudioicon-arrow-up"></i>' ),
            'price-desc' => sprintf(__( 'Sort by Price: %s', 'skudmart' ), '<i class="lastudioicon-arrow-down"></i>' ),
        );
        return $data;
    }

    add_filter('woocommerce_catalog_orderby', 'skudmart_modify_text_woocommerce_catalog_orderby');
}

if(!function_exists('skudmart_add_custom_badge_for_product')){
    function skudmart_add_custom_badge_for_product(){
        global $product;
        $product_badges = skudmart_get_post_meta($product->get_id(), 'product_badges');
        if(empty($product_badges)){
            return;
        }
        $_tmp_badges = array();
        foreach($product_badges as $badge){
            if(!empty($badge['text'])){
                $_tmp_badges[] = $badge;
            }
        }
        if(empty($_tmp_badges)){
            return;
        }
        foreach($_tmp_badges as $i => $badge){
            $attribute = array();
            if(!empty($badge['bg'])){
                $attribute[] = 'background-color:' . esc_attr($badge['bg']);
            }
            if(!empty($badge['color'])){
                $attribute[] = 'color:' . esc_attr($badge['color']);
            }
            $el_class = ($i%2==0) ? 'odd' : 'even';
            if(!empty($badge['el_class'])){
                $el_class .= ' ';
                $el_class .= $badge['el_class'];
            }

            echo sprintf(
                '<span class="la-custom-badge %1$s" style="%3$s"><span>%2$s</span></span>',
                esc_attr($el_class),
                esc_html($badge['text']),
                (!empty($attribute) ? esc_attr(implode(';', $attribute)) : '')
            );
        }
    }
    add_action( 'woocommerce_before_shop_loop_item_title', 'skudmart_add_custom_badge_for_product', 9 );
    add_action( 'woocommerce_before_single_product_summary', 'skudmart_add_custom_badge_for_product', 9 );
}

if(!function_exists('skudmart_wc_add_custom_countdown_to_product_details')){
    function skudmart_wc_add_custom_countdown_to_product_details(){
        global $product;
        if($product->is_on_sale()){
            $sale_price_dates_to = $product->get_date_on_sale_to() && ( $date = $product->get_date_on_sale_to()->getOffsetTimestamp() ) ? $date : '';
            $now = current_time('timestamp');
            if(!empty($sale_price_dates_to)){ ?>
                <div class="prod-countdown-timer js-el" data-la_component="CountDownTimer">
                    <div class="lastudio-countdown-timer" data-ct="<?php echo esc_attr($now); ?>" data-due-date="<?php echo esc_attr($sale_price_dates_to); ?>">
                        <?php if($sale_price_dates_to - $now > 86400): ?><div class="lastudio-countdown-timer__item item-days">
                            <div class="lastudio-countdown-timer__item-value" data-value="days"><span class="lastudio-countdown-timer__digit">0</span><span class="lastudio-countdown-timer__digit">0</span></div>
                            <div class="lastudio-countdown-timer__item-label"><?php esc_html_e('Days', 'skudmart') ?></div></div><?php endif; ?>
                        <div class="lastudio-countdown-timer__item item-hours">
                            <div class="lastudio-countdown-timer__item-value" data-value="hours"><span class="lastudio-countdown-timer__digit">0</span><span class="lastudio-countdown-timer__digit">0</span></div>
                            <div class="lastudio-countdown-timer__item-label"><?php esc_html_e('Hours', 'skudmart');?></div></div>
                        <div class="lastudio-countdown-timer__item item-minutes">
                            <div class="lastudio-countdown-timer__item-value" data-value="minutes"><span class="lastudio-countdown-timer__digit">0</span><span class="lastudio-countdown-timer__digit">0</span></div>
                            <div class="lastudio-countdown-timer__item-label"><?php esc_html_e('Mins', 'skudmart'); ?></div></div>
                        <div class="lastudio-countdown-timer__item item-seconds">
                            <div class="lastudio-countdown-timer__item-value" data-value="seconds"><span class="lastudio-countdown-timer__digit">0</span><span class="lastudio-countdown-timer__digit">0</span></div>
                            <div class="lastudio-countdown-timer__item-label"><?php esc_html_e('Secs', 'skudmart'); ?></div></div>
                    </div>
                </div>
                <?php
            }
        }
    }
}

if(!function_exists('skudmart_wc_add_custom_stock_to_product_details')){
    function skudmart_wc_add_custom_stock_to_product_details(){
        global $product;
        $stock_sold = ($total_sales = $product->get_total_sales()) ? $total_sales : 0;
        if($stock_sold > 0){
            $availability = sprintf(__('%s Sold', 'skudmart'), $stock_sold );
            echo str_replace('">', '"><span>' . $availability . '</span><i></i>', wc_get_stock_html( $product ));
        }
        else{
            echo wc_get_stock_html( $product );
        }
    }
}

remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 8 );
add_action( 'woocommerce_single_product_summary', 'skudmart_wc_add_custom_stock_to_product_details', 8 );
add_action( 'woocommerce_single_product_summary', 'skudmart_wc_add_custom_countdown_to_product_details', 25 );


if(!function_exists('skudmart_add_custom_block_to_cart_page')){
    function skudmart_add_custom_block_to_cart_page(){
        ?>
        <div class="lasf-extra-cart lasf-extra-cart--calc">
            <h2><?php esc_html_e('Estimate Shipping', 'skudmart'); ?></h2>
            <p><?php esc_html_e('Enter your destination to get shipping', 'skudmart'); ?></p>
            <div class="lasf-extra-cart-box"></div>
        </div>
        <div class="lasf-extra-cart lasf-extra-cart--coupon">
            <h2><?php esc_html_e('Discount code', 'skudmart'); ?></h2>
            <p><?php esc_html_e('Enter your coupon if you have one', 'skudmart'); ?></p>
            <div class="lasf-extra-cart-box"></div>
        </div>
        <?php
    }
    add_action('woocommerce_cart_collaterals', 'skudmart_add_custom_block_to_cart_page', 5);
}

if(!function_exists('skudmart_add_custom_step_into_woocommerce')){
    function skudmart_add_custom_step_into_woocommerce(){
?>
        <div class="row section-checkout-step">
            <div class="col-xs-12">
                <ul>
                    <li class="step-1"><span class="step-name"><span><span class="step-num"><?php esc_html_e('01', 'skudmart') ?></span><span><?php esc_html_e('Shopping Cart', 'skudmart') ?></span></span></span>
                    </li><li class="step-2"><span class="step-name"><span><span class="step-num"><?php esc_html_e('02', 'skudmart') ?></span><span><?php esc_html_e('Check out', 'skudmart') ?></span></span></span>
                    </li><li class="step-3"><span class="step-name"><span><span class="step-num"><?php esc_html_e('03', 'skudmart') ?></span><span><?php esc_html_e('Order completed', 'skudmart') ?></span></span></span></li>
                </ul>
            </div>
        </div>
<?php
    }
}
add_action('woocommerce_check_cart_items', 'skudmart_add_custom_step_into_woocommerce');

if(!function_exists('skudmart_add_custom_heading_to_checkout_order_review')){
    function skudmart_add_custom_heading_to_checkout_order_review(){
        ?><h3 id="order_review_heading_ref"><?php esc_html_e( 'Your order', 'skudmart' ); ?></h3><?php
    }
}
add_action('woocommerce_checkout_order_review', 'skudmart_add_custom_heading_to_checkout_order_review', 0);

if(!function_exists('skudmart_override_woocommerce_product_get_rating_html')){
    function skudmart_override_woocommerce_product_get_rating_html( $html ) {
        if(!empty($html)){
            $html = '<div class="product_item--rating">'.$html.'</div>';
        }
        return $html;
    }
}
add_filter('woocommerce_product_get_rating_html', 'skudmart_override_woocommerce_product_get_rating_html');


if(!function_exists('skudmart_callback_func_to_show_custom_block')){
    function skudmart_callback_func_to_show_custom_block( $block = array(), $hook_name = '', $priority = 10 ){
        if(!empty($block['content']) && !empty($hook_name)){
            echo '<div class="la-custom-block '. (!empty($block['el_class']) ? esc_attr($block['el_class']) : '') .'">';
            echo skudmart_transfer_text_to_format($block['content'], true);
            echo '</div>';
        }
    }
}

if(!function_exists('skudmart_add_custom_block_to_single_product_page')){
    function skudmart_add_custom_block_to_single_product_page(){

        $position_detect = array(
            'pos1' => array(
                'hook_name' => 'woocommerce_single_product_summary',
                'priority'  => 30 /* After Cart */
            ),
            'pos2' => array(
                'hook_name' => 'woocommerce_single_product_summary',
                'priority'  => 40 /* After Meta */
            ),
            'pos3' => array(
                'hook_name' => 'woocommerce_single_product_summary',
                'priority'  => 10 /* After Price */
            ),
            'pos4' => array(
                'hook_name' => 'woocommerce_single_product_summary',
                'priority'  => 5 /* After Title */
            ),
            'pos5' => array(
                'hook_name' => 'woocommerce_single_product_summary',
                'priority'  => 20 /* After Description */
            ),
            'pos6' => array(
                'hook_name' => 'skudmart/action/after_woocommerce_single_product_summary',
                'priority'  => 10 /* Beside Summary */
            ),
            'pos7' => array(
                'hook_name' => 'skudmart/action/before_wc_tabs',
                'priority'  => 10 /* Before Tabs */
            ),
            'pos8' => array(
                'hook_name' => 'skudmart/action/after_wc_tabs',
                'priority'  => 10 /* Before Tabs */
            ),
            'pos9' => array(
                'hook_name' => 'woocommerce_after_single_product_summary',
                'priority'  => 30 /* After Related */
            ),
            'pos10' => array(
                'hook_name' => 'woocommerce_after_single_product_summary',
                'priority'  => 15 /* After Up-sells */
            ),
            'pos11' => array(
                'hook_name' => 'skudmart/action/before_main',
                'priority'  => 10 /* After Main Wrap */
            ),
            'pos12' => array(
                'hook_name' => 'skudmart/action/after_main',
                'priority'  => 10 /* After Main Wrap */
            )
        );

        if(skudmart_string_to_bool(skudmart_get_option('woo_enable_custom_block_single_product'))){
            $blocks = skudmart_get_option('woo_custom_block_single_product');
            if(!empty($blocks) && is_array($blocks)){
                foreach ($blocks as $k => $block){
                    $block_content = !empty($block['content']) ? $block['content'] : '';
                    $block_position = !empty($block['position']) ? $block['position'] : '';

                    if(!empty($block_content) && !empty($block_position) && is_array($position_detect[$block_position]) ){
                        $hooks = $position_detect[$block_position];
                        $hook_name = $hooks['hook_name'];
                        $priority = $hooks['priority'];

                        add_action( $hook_name, function() use( $block, $hook_name, $priority ) {  skudmart_callback_func_to_show_custom_block($block, $hook_name, $priority); }, $priority );
                    }
                }
            }
        }
    }
    add_action('wp_head', 'skudmart_add_custom_block_to_single_product_page');
}