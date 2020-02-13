<?php

// Do not allow directly accessing this file.
if (!defined('ABSPATH')) {
    exit('Direct script access denied.');
}

/**
 * Get excerpt
 *
 * @since 1.0.0
 */
if ( ! function_exists( 'la_excerpt' ) ) {

    function la_excerpt( $length = 30 ) {
        global $post;

        // Check for custom excerpt
        if ( has_excerpt( $post->ID ) ) {
            $output = $post->post_excerpt;
        }

        // No custom excerpt
        else {

            // Check for more tag and return content if it exists
            if ( strpos( $post->post_content, '<!--more-->' ) ) {
                $output = apply_filters( 'the_content', get_the_content() );
            }

            // No more tag defined
            else {
                $output = wp_trim_words( strip_shortcodes( $post->post_content ), $length );
            }

        }

        return $output;

    }

}

if(!function_exists('la_minify_css')){
    function la_minify_css( $css = '' ){
        // Return if no CSS
        if ( ! $css ) return;

        // Normalize whitespace
        $css = preg_replace( '/\s+/', ' ', $css );

        // Remove ; before }
        $css = preg_replace( '/;(?=\s*})/', '', $css );

        // Remove space after , : ; { } */ >
        $css = preg_replace( '/(,|:|;|\{|}|\*\/|>) /', '$1', $css );

        // Remove space before , ; { }
        $css = preg_replace( '/ (,|;|\{|})/', '$1', $css );

        // Strips leading 0 on decimal values (converts 0.5px into .5px)
        $css = preg_replace( '/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css );

        // Strips units if value is 0 (converts 0px to 0)
        $css = preg_replace( '/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css );

        // Remove empty padding and margin value
        //$css = preg_replace( '/(margin|padding)(-)?(left|right|top|bottom)?:(-)?(%|em|ex|px|in|cm|mm|pt|pc);?/', '', $css );

        // Remove selector with empty value
        //$css = preg_replace('/(?:[^\r\n,{}]+)(?:,(?=[^}]*{)|\s*{[\s]*})/', '', $css);

        // Remove selector with empty value within media query
        //$css = preg_replace('/(?:[^\r\n,{}]+)(?:,(?=[^}]*{)|\s*{[\s]*})/', '', $css);

        // Trim
        $css = trim( $css );

        // Return minified CSS
        return $css;
    }
}

if(!function_exists('la_get_base_shop_url')){
    function la_get_base_shop_url(){

        if(!function_exists('WC')){
            return home_url('/');
        }

        if ( defined( 'SHOP_IS_ON_FRONT' ) ) {
            $link = home_url();
        }
        elseif ( is_shop() ) {
            $link = get_permalink( wc_get_page_id( 'shop' ) );
        }
        elseif( is_tax( get_object_taxonomies( 'product' ) ) ) {

            if( is_product_category() ) {
                $link = get_term_link( get_query_var( 'product_cat' ), 'product_cat' );
            }
            elseif ( is_product_tag() ) {
                $link = get_term_link( get_query_var( 'product_tag' ), 'product_tag' );
            }
            else{
                $queried_object = get_queried_object();
                $link = get_term_link( $queried_object->slug, $queried_object->taxonomy );
            }
        }
        elseif ( function_exists('dokan_is_store_page') && dokan_is_store_page() ){
            $current_url = add_query_arg(null, null, dokan_get_store_url(get_query_var('author')));
            $current_url = remove_query_arg(array('page', 'paged', 'mode_view', 'la_doing_ajax'), $current_url);
            $link = preg_replace('/\/page\/\d+/', '', $current_url);
            $tmp = explode('?', $link);
            if(isset($tmp[0])){
                $link = $tmp[0];
            }
        }
        else{
            $link = get_post_type_archive_link( 'product' );
        }

        return $link;
    }
}

if (!function_exists('la_log')) {
    function la_log($log) {
        if (true === WP_DEBUG) {
            date_default_timezone_set('Asia/Ho_Chi_Minh');
            if (is_array($log) || is_object($log)) {
                error_log(print_r($log, true));
            } else {
                error_log($log);
            }
        }
    }
}

if(!function_exists('la_string_to_bool')){
    function la_string_to_bool($string){
        return is_bool($string) ? $string : ('yes' === $string || 1 === $string || 'true' === $string || '1' === $string);
    }
}

/**
 * Define a constant if it is not already defined.
 *
 * @since 1.0.0
 * @param string $name Constant name.
 * @param string $value Value.
 */
if(!function_exists('la_maybe_define_constant')){
    function la_maybe_define_constant($name, $value){
        if (!defined($name)) {
            define($name, $value);
        }
    }
}

/**
 *
 * Add framework element
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 */
if(!function_exists('la_fw_add_element')){
    function la_fw_add_element($field = array(), $value = '', $unique = ''){

        echo 'cho ti';

    }
}

if(!function_exists('la_add_script_to_compare')){
    function la_add_script_to_compare() {
        echo '<script type="text/javascript">var redirect_to_cart=true;</script>';
    }
}
add_action('yith_woocompare_after_main_table', 'la_add_script_to_compare');

if(!function_exists('la_add_script_to_quickview_product')){
    function la_add_script_to_quickview_product()
    {
        global $product;
        if (function_exists('is_product') && isset($_GET['product_quickview']) && is_product()) {
            if ($product->get_type() == 'variable') {
                wp_print_scripts('underscore');
                wc_get_template('single-product/add-to-cart/variation.php');
                ?>
                <script type="text/javascript">
                    /* <![CDATA[ */
                    var _wpUtilSettings = <?php echo wp_json_encode(array(
                        'ajax' => array('url' => admin_url('admin-ajax.php', 'relative'))
                    ));?>;
                    var wc_add_to_cart_variation_params = <?php echo wp_json_encode(array(
                        'i18n_no_matching_variations_text' => esc_attr__('Sorry, no products matched your selection. Please choose a different combination.', 'lastudio'),
                        'i18n_make_a_selection_text' => esc_attr__('Select product options before adding this product to your cart.', 'lastudio'),
                        'i18n_unavailable_text' => esc_attr__('Sorry, this product is unavailable. Please choose a different combination.', 'lastudio')
                    )); ?>;
                    /* ]]> */
                </script>
                <script type="text/javascript" src="<?php echo esc_url(includes_url('js/wp-util.min.js')) ?>"></script>
                <script type="text/javascript"
                        src="<?php echo esc_url(WC()->plugin_url()) . '/assets/js/frontend/add-to-cart-variation.min.js' ?>"></script>
                <?php
            } else {
                ?>
                <script type="text/javascript">
                    /* <![CDATA[ */
                    var wc_single_product_params = <?php echo wp_json_encode(array(
                        'i18n_required_rating_text' => esc_attr__('Please select a rating', 'lastudio'),
                        'review_rating_required' => get_option('woocommerce_review_rating_required'),
                        'flexslider' => apply_filters('woocommerce_single_product_carousel_options', array(
                            'rtl' => is_rtl(),
                            'animation' => 'slide',
                            'smoothHeight' => false,
                            'directionNav' => false,
                            'controlNav' => 'thumbnails',
                            'slideshow' => false,
                            'animationSpeed' => 500,
                            'animationLoop' => false, // Breaks photoswipe pagination if true.
                        )),
                        'zoom_enabled' => 0,
                        'photoswipe_enabled' => 0,
                        'flexslider_enabled' => 1,
                    ));?>;
                    /* ]]> */
                </script>
                <?php
            }
        }
    }
}
add_action('woocommerce_after_single_product', 'la_add_script_to_quickview_product');

if(!function_exists('la_theme_fix_wc_track_product_view')){
    function la_theme_fix_wc_track_product_view()
    {
        if (!is_singular('product')) {
            return;
        }
        if (!function_exists('wc_setcookie')) {
            return;
        }
        global $post;
        if (empty($_COOKIE['woocommerce_recently_viewed'])) {
            $viewed_products = array();
        }
        else {
            $viewed_products = (array)explode('|', $_COOKIE['woocommerce_recently_viewed']);
        }
        if (!in_array($post->ID, $viewed_products)) {
            $viewed_products[] = $post->ID;
        }
        if (sizeof($viewed_products) > 15) {
            array_shift($viewed_products);
        }
        wc_setcookie('woocommerce_recently_viewed', implode('|', $viewed_products));
    }
}
add_action('template_redirect', 'la_theme_fix_wc_track_product_view', 30);

if(!function_exists('la_add_extra_section_to_theme_options')){
    function la_add_extra_section_to_theme_options(){
        $theme = wp_get_theme();
        $prefix = strtolower($theme->get_template()) . '_options';

        /**
         * Social Panel
         */
        LASF::createSection( $prefix, array(
            'id'            => 'social_panel',
            'title'         => esc_html_x('Social Media', 'admin-view', 'lastudio'),
            'icon'          => 'fa fa-share-alt'
        ));

        /**
         * Social Panel - Social Media Links
         */
        LASF::createSection( $prefix, array(
            'parent'        => 'social_panel',
            'title'         => esc_html_x('Social Media Links', 'admin-view', 'lastudio'),
            'icon'          => 'fa fa-share-alt',
            'fields'        => array(
                array(
                    'id'        => 'social_links',
                    'type'      => 'group',
                    'title'     => esc_html_x('Social Media Links', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Social media links use a repeater field and allow one network per field. Click the "Add" button to add additional fields.', 'admin-view', 'lastudio'),
                    'button_title'    => esc_html_x('Add','admin-view', 'lastudio'),
                    'max_item'  => 10,
                    'fields'    => array(
                        array(
                            'id'        => 'title',
                            'type'      => 'text',
                            'default'   => esc_html_x('Title', 'admin-view', 'lastudio'),
                            'title'     => esc_html_x('Title', 'admin-view', 'lastudio')
                        ),
                        array(
                            'id'        => 'icon',
                            'type'      => 'icon',
                            'default'   => 'fa fa-share',
                            'title'     => esc_html_x('Custom Icon', 'admin-view', 'lastudio')
                        ),
                        array(
                            'id'        => 'link',
                            'type'      => 'text',
                            'default'   => '#',
                            'title'     => esc_html_x('Link (URL)', 'admin-view', 'lastudio')
                        )
                    )
                )
            )
        ));

        /**
         * Social Panel - Social Sharing Box
         */
        LASF::createSection( $prefix, array(
            'parent'        => 'social_panel',
            'title'         => esc_html_x('Social Sharing Box', 'admin-view', 'lastudio'),
            'icon'          => 'fa fa-share-square-o',
            'fields'        => array(
                array(
                    'id'        => 'sharing_facebook',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('Facebook', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display Facebook in the social share box.', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'sharing_twitter',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('Twitter', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display Twitter in the social share box.', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'sharing_reddit',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('Reddit', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display Reddit in the social share box.', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'sharing_linkedin',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('LinkedIn', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display LinkedIn in the social share box.', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'sharing_tumblr',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('Tumblr', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display Tumblr in the social share box.', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'sharing_pinterest',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('Pinterest', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display Pinterest in the social share box.', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'sharing_line',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('LINE', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display LINE in the social share box.', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'sharing_whatapps',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('Whatsapp', 'admin-view','lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display Whatsapp in the social share box.', 'admin-view','lastudio')
                ),
                array(
                    'id'        => 'sharing_telegram',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('Telegram','admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display Telegram in the social share box.', 'admin-view','lastudio')
                ),
                array(
                    'id'        => 'sharing_vk',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('VK', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display VK in the social share box.', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'sharing_email',
                    'type'      => 'switcher',
                    'default'   => false,
                    'title'     => esc_html_x('Email', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Turn on to display Email in the social share box.', 'admin-view', 'lastudio')
                )
            )
        ));


        /**
         * Additional Code Panel
         */
        LASF::createSection( $prefix, array(
            'id'            => 'additional_code_panel',
            'title'         => esc_html_x('Additional Code', 'admin-view', 'lastudio'),
            'icon'          => 'fa fa-code',
            'fields'        => array(
                array(
                    'id'        => 'google_key',
                    'type'      => 'text',
                    'title'     => esc_html_x('Google Maps APIs Key', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('Type your Google Maps APIs Key here.', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'instagram_token',
                    'type'      => 'text',
                    'title'     => esc_html_x('Instagram Access Token', 'admin-view', 'lastudio'),
                    'subtitle'  => esc_html_x('In order to display your photos you need an Access Token from Instagram.', 'admin-view', 'lastudio'),
                    'desc'      => sprintf(
                        __('<a target="_blank" href="%s">Click here</a> to get your API', 'lastudio'),
                        '//la-studioweb.com/tools/instagram-token/'
                    )
                ),

                array(
                    'id'       => 'la_custom_css',
                    'type'     => 'code_editor',
                    'title'    => esc_html_x('Custom CSS', 'admin-view', 'lastudio'),
                    'subtitle' => esc_html_x('Paste your custom CSS code here.', 'admin-view', 'lastudio'),
                    'class'    => 'lasf-field-fullwidth',
                    'settings' => array(
                        'codemirror' => array(
                            'mode' => 'css'
                        )
                    ),
                    'transport' => 'postMessage'
                ),

                array(
                    'id'       => 'header_js',
                    'type'     => 'code_editor',
                    'title'    => esc_html_x('Header Javascript Code', 'admin-view', 'lastudio'),
                    'subtitle' => esc_html_x('Paste your custom JS code here. The code will be added to the header of your site.', 'admin-view', 'lastudio'),
                    'class'    => 'lasf-field-fullwidth',
                    'settings' => array(
                        'codemirror' => array(
                            'mode' => 'javascript'
                        )
                    ),
                    'default' =>';(function( $, window, document, undefined ) {
  "use strict";

    $(function(){
        
        // do stuff    

    });

})( jQuery, window, document );',
                ),

                array(
                    'id'       => 'footer_js',
                    'type'     => 'code_editor',
                    'title'    => esc_html_x('Footer Javascript Code', 'admin-view', 'lastudio'),
                    'subtitle' => esc_html_x('Paste your custom JS code here. The code will be added to the footer of your site.', 'admin-view', 'lastudio'),
                    'class'    => 'lasf-field-fullwidth',
                    'settings' => array(
                        'codemirror' => array(
                            'mode' => 'javascript'
                        )
                    ),
                    'default' =>';(function( $, window, document, undefined ) {
  "use strict";

    $(function(){
        
        // do stuff    

    });

})( jQuery, window, document );',
                )
            )
        ));


        /**
         * Newsletter Popup Panel
         */
        LASF::createSection( $prefix, array(
            'id'            => 'popup_panel',
            'title'         => esc_html_x('Newsletter Popup', 'admin-view', 'lastudio'),
            'icon'          => 'fa fa-check',
            'fields'        => array(
                array(
                    'id' => 'enable_newsletter_popup',
                    'type' => 'switcher',
                    'title' => esc_html_x('Enable Newsletter Popup', 'admin-view', 'lastudio'),
                    'default' => false
                ),
                array(
                    'id' => 'popup_max_width',
                    'type' => 'text',
                    'title' => esc_html_x("Popup Max Width", 'admin-view', 'lastudio'),
                    'default' => 790,
                    'dependency' => array('enable_newsletter_popup', '==', 'true')
                ),
                array(
                    'id' => 'popup_max_height',
                    'type' => 'text',
                    'title' => esc_html_x("Popup Max Height", 'admin-view', 'lastudio'),
                    'default' => 430,
                    'dependency' => array('enable_newsletter_popup', '==', 'true')
                ),
                array(
                    'id'        => 'popup_background',
                    'type'      => 'background',
                    'title'     => esc_html_x('Popup Background', 'admin-view', 'lastudio'),
                    'dependency' => array('enable_newsletter_popup', '==', 'true')
                ),
                array(
                    'id' => 'only_show_newsletter_popup_on_home_page',
                    'type' => 'switcher',
                    'title' => esc_html_x('Only showing on homepage', 'admin-view', 'lastudio'),
                    'default' => false,
                    'dependency' => array('enable_newsletter_popup', '==', 'true')
                ),
                array(
                    'id' => 'disable_popup_on_mobile',
                    'type' => 'switcher',
                    'title' => esc_html_x("Don't show popup on mobile", 'admin-view', 'lastudio'),
                    'default' => false,
                    'dependency' => array('enable_newsletter_popup', '==', 'true')
                ),
                array(
                    'id' => 'newsletter_popup_delay',
                    'type' => 'text',
                    'title' => esc_html_x('Popup showing after', 'admin-view', 'lastudio'),
                    'subtitle' => esc_html_x('Show Popup when site loaded after (number) seconds ( 1000ms = 1 second )', 'admin-view', 'lastudio'),
                    'default' => '2000',
                    'dependency' => array('enable_newsletter_popup', '==', 'true'),
                ),
                array(
                    'id' => 'show_checkbox_hide_newsletter_popup',
                    'type' => 'switcher',
                    'title' => esc_html_x('Display option "Does not show popup again"', 'admin-view', 'lastudio'),
                    'default' => false,
                    'dependency' => array('enable_newsletter_popup', '==', 'true')
                ),
                array(
                    'id' => 'popup_dont_show_text',
                    'type' => 'text',
                    'title' => esc_html_x('Text "Does not show popup again"', 'admin-view', 'lastudio'),
                    'default' => 'Do not show popup anymore',
                    'dependency' => array('enable_newsletter_popup|show_checkbox_hide_newsletter_popup', '==|==', 'true|true'),
                ),
                array(
                    'id' => 'newsletter_popup_show_again',
                    'type' => 'text',
                    'title' => esc_html_x('Back display popup after', 'admin-view', 'lastudio'),
                    'subtitle' => esc_html_x('Enter number day', 'admin-view', 'lastudio'),
                    'default' => '1',
                    'dependency' => array('enable_newsletter_popup|show_checkbox_hide_newsletter_popup', '==|==', 'true|true'),
                ),
                array(
                    'id' => 'newsletter_popup_content',
                    'type' => 'wp_editor',
                    'title' => esc_html_x('Newsletter Popup Content', 'admin-view', 'lastudio'),
                    'dependency' => array('enable_newsletter_popup', '==', 'true'),
                )
            )
        ));


        /**
         * Extensions Panel
         */
        LASF::createSection( $prefix, array(
            'id'            => 'la_extension_panel',
            'title'         => esc_html_x('Extensions', 'admin-view', 'lastudio'),
            'icon'          => 'fa fa-lock',
        ));

        /**
         * Extensions Panel - General
         */
        LASF::createSection( $prefix, array(
            'parent'        => 'la_extension_panel',
            'title'         => esc_html_x('General', 'admin-view', 'lastudio'),
            'icon'          => 'fa fa-lock',
            'fields'        => array(
                array(
                    'id'       => 'la_extension_available',
                    'type'     => 'checkbox',
                    'title'    => esc_html_x('Extensions Available', 'admin-view', 'lastudio'),
                    'options'  => array(
                        'swatches' => 'Product Color Swatches',
                        '360' => 'Product 360',
                        'content_type' => 'Custom Content Type'
                    ),
                    'default'  => array(
                        'swatches', '360', 'content_type'
                    )
                ),
                array(
                    'type'    => 'subheading',
                    'content' => esc_html_x('Mailing List Manager', 'admin-view', 'lastudio')
                ),
                array(
                    'id'        => 'mailchimp_api_key',
                    'type'      => 'text',
                    'title'     => esc_html_x('MailChimp API key', 'admin-view', 'lastudio'),
                    'attributes'=> array(
                        'placeholder' => esc_html_x('MailChimp API key', 'admin-view', 'lastudio')
                    ),
                    'subtitle'  => sprintf( '%1$s <a href="http://kb.mailchimp.com/integrations/api-integrations/about-api-keys">%2$s</a>', esc_html__( 'Input your MailChimp API key', 'lastudio' ), esc_html__( 'About API Keys', 'lastudio' ) ),
                ),
                array(
                    'id'        => 'mailchimp_list_id',
                    'type'      => 'text',
                    'attributes'=> array(
                        'placeholder' => esc_html_x('MailChimp list ID', 'admin-view', 'lastudio')
                    ),
                    'title'     => esc_html_x('MailChimp list ID', 'admin-view', 'lastudio'),
                    'subtitle'  => sprintf( '%1$s <a href="http://kb.mailchimp.com/lists/managing-subscribers/find-your-list-id">%2$s</a>', esc_html__( 'MailChimp list ID', 'lastudio' ), esc_html__( 'list ID', 'lastudio' ) ),
                ),
                array(
                    'id'        => 'mailchimp_double_opt_in',
                    'type'      => 'switcher',
                    'title'     => esc_html__( 'Double opt-in', 'lastudio' ),
                    'subtitle'  => esc_html__( 'Send contacts an opt-in confirmation email when they subscribe to your list.', 'lastudio' ),
                ),
                array(
                    'type'    => 'subheading',
                    'content' => esc_html_x('Plugins Updates', 'admin-view', 'lastudio')
                ),
                array(
                    'type'    => 'content',
                    'content' => '<div class="lasf_table"><div class="lasf_table--top"><a class="button button-primary lasf-button-check-plugins-for-updates" href="javascript:;">Check for updates</a></div></div>'
                )
            )
        ));


        /**
         * Extensions Panel - Elementor Available Widgets
         */

        if(function_exists('lastudio_elementor_get_all_modules')){
            $elementor_module_tmp = lastudio_elementor_get_all_modules();
            $elementor_modules = [];

            if(!empty($elementor_module_tmp)){
                foreach ($elementor_module_tmp as $k => $v){
                    $elementor_modules[$k] = str_replace('_', ' ', $v);
                }

                LASF::createSection( $prefix, array(
                    'parent'        => 'la_extension_panel',
                    'title'         => esc_html_x('Elementor Available Widgets', 'admin-view', 'lastudio'),
                    'icon'          => 'fa fa-lock',
                    'fields'        => array(
                        array(
                            'id'       => 'la_elementor_modules',
                            'type'     => 'checkbox',
                            'class'    => 'lasf-field-fullwidth lasf-field-la_elementor_modules',
                            'title'    => esc_html_x('Available Widgets', 'admin-view', 'lastudio'),
                            'subtitle' => esc_html_x('List of widgets that will be available when editing the page', 'admin-view', 'lastudio'),
                            'options'  => $elementor_modules,
                            'default'  => array_keys($elementor_modules)
                        )
                    )
                ));

            }
        }

        /**
         * Backup Panel
         */
        LASF::createSection( $prefix, array(
            'id'        => 'backup_panel',
            'title'     => esc_html_x('Import / Export', 'admin-view', 'lastudio'),
            'icon'      => 'fa fa-refresh',
            'fields'    => array(
                array(
                    'type'    => 'notice',
                    'style'   => 'warning',
                    'content' => esc_html_x('You can save your current options. Download a Backup and Import.', 'admin-view', 'lastudio'),
                ),
                array(
                    'type'      => 'backup'
                )
            )
        ));
    }
}

add_action('init', 'la_add_extra_section_to_theme_options', 11);

add_action('lasf_theme_setting_save_after', function( $request, $instance ) {

    if(isset($request['la_extension_available'])){

        $default = array(
            'swatches' => false,
            '360' => false,
            'content_type' => false
        );

        $la_extension_available = !empty($request['la_extension_available']) ? $request['la_extension_available'] : array('default' => 'hello');

        if(in_array('swatches',$la_extension_available)){
            $default['swatches'] = true;
        }
        if(in_array('360',$la_extension_available)){
            $default['360'] = true;
        }

        if(in_array('content_type',$la_extension_available)){
            $default['content_type'] = true;
        }
        update_option('la_extension_available', $default);
    }

    if(isset($request['la_elementor_modules']) && function_exists('lastudio_elementor_get_all_modules')){

        $elementor_module_tmp = lastudio_elementor_get_all_modules();

        $default_modules = [];
        foreach ($elementor_module_tmp as $k => $v){
            $default_modules[$k] = false;
        }

        $la_widget_available = !empty($request['la_elementor_modules']) ? $request['la_elementor_modules'] : [];

        if(!empty($la_widget_available)){
            foreach ($la_widget_available as $module){
                if(isset($default_modules[$module])){
                    $default_modules[$module] = true;
                }
            }
        }
        else{
            if(!get_option('lastudio_elementor_modules_has_init', false)){
                $default_modules = [];
                foreach ($elementor_module_tmp as $k => $v){
                    $default_modules[$k] = true;
                }

                update_option('lastudio_elementor_modules', $default_modules);
                update_option('lastudio_elementor_modules_has_init', true);
            }
        }
        update_option('lastudio_elementor_modules', $default_modules);
    }

} , 10, 2);

add_shortcode('la_wishlist', function( $atts, $content ){
    ob_start();
    if(function_exists('wc_print_notices')){
        get_template_part('woocommerce/la_wishlist');
    }
    return ob_get_clean();
});

add_shortcode('la_compare', function( $atts, $content ){
    ob_start();
    if(function_exists('wc_print_notices')){
        get_template_part('woocommerce/la_compare');
    }
    return ob_get_clean();
});


if(!function_exists('la_get_all_image_sizes')){
    function la_get_all_image_sizes() {

        global $_wp_additional_image_sizes;

        $sizes  = get_intermediate_image_sizes();
        $result = array();

        foreach ( $sizes as $size ) {
            if ( in_array( $size, array( 'thumbnail', 'medium', 'medium_large', 'large' ) ) ) {
                $result[ $size ] = ucwords( trim( str_replace( array( '-', '_' ), array( ' ', ' ' ), $size ) ) );
            } else {
                $result[ $size ] = sprintf(
                    '%1$s (%2$sx%3$s)',
                    ucwords( trim( str_replace( array( '-', '_' ), array( ' ', ' ' ), $size ) ) ),
                    $_wp_additional_image_sizes[ $size ]['width'],
                    $_wp_additional_image_sizes[ $size ]['height']
                );
            }
        }

        return array_merge( array( 'full' => esc_html__( 'Full', 'lastudio' ) ), $result );
    }
}



if(!function_exists('lasf_array_diff_assoc_recursive')){
    function lasf_array_diff_assoc_recursive($array1, $array2) {
        $difference=array();
        foreach($array1 as $key => $value) {
            if( is_array($value) ) {
                if( !isset($array2[$key]) || !is_array($array2[$key]) ) {
                    $difference[$key] = $value;
                } else {
                    $new_diff = lasf_array_diff_assoc_recursive($value, $array2[$key]);
                    if( !empty($new_diff) )
                        $difference[$key] = $new_diff;
                }
            } else if( !array_key_exists($key,$array2) || $array2[$key] !== $value ) {
                $difference[$key] = $value;
            }
        }
        return $difference;
    }
}

add_action('wp_ajax_lasf_check_plugins_for_updates', function(){

    do_action('lastudio_elementor_recreate_editor_file');

    $theme_obj = wp_get_theme();

    $option_key = $theme_obj->template . '_required_plugins_list';

    $theme_version = $theme_obj->version;

    if( $theme_obj->parent() !== false ) {
        $theme_version = $theme_obj->parent()->version;
    }

    $remote_url = 'https://la-studioweb.com/file-resouces/' ;

    $response = wp_remote_get($remote_url, array(
        'method' => 'POST',
        'timeout' => 30,
        'redirection' => 5,
        'httpversion' => '1.0',
        'blocking' => true,
        'headers' => array(),
        'body' => array(
            'theme_name'    => $theme_obj->template,
            'site_url'      => home_url('/')
        ),
        'cookies' => array()
    ));

    // request failed
    if ( is_wp_error( $response ) ) {
        echo 'Could not connect to server, please try later';
        die();
    }

    $code = (int) wp_remote_retrieve_response_code( $response );

    if ( $code !== 200 ) {
        echo 'Could not connect to server, please try later';
        die();
    }

    try{

        $body = json_decode(wp_remote_retrieve_body($response), true);

        $response_theme_version = !empty($body['theme']['version']) ? $body['theme']['version'] : $theme_version;

        if( version_compare($response_theme_version, $theme_version) >= 0 ) {

            $old_plugins = get_option($option_key, array());

            if( !empty( $body['plugins'] ) &&  !empty( lasf_array_diff_assoc_recursive( $body['plugins'], $old_plugins ) ) ) {
                update_option($option_key, $body['plugins']);
                echo 'Please go to `Appearance` -> `Install Plugins` to update the required plugins ( if it is available )';
            }
            else{
                echo 'Please go to `Appearance` -> `Install Plugins` to update the required plugins ( if it is available )';
            }
        }
        else{
            echo 'Please go to `Appearance` -> `Install Plugins` to update the required plugins ( if it is available )';
        }

    }
    catch ( Exception $ex ){
        echo 'Could not connect to server, please try later';
    }
    die();

});