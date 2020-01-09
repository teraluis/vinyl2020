<?php
/*
    Template Name: icons
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
get_header(); ?>

    <?php do_action( 'skudmart/action/before_content_wrap' ); ?>

    <div id="content-wrap" class="container">

        <?php do_action( 'skudmart/action/before_primary' ); ?>

        <div id="primary" class="content-area">

            <?php do_action( 'skudmart/action/before_content' ); ?>

            <div id="content" class="site-content">

                <?php do_action( 'skudmart/action/before_content_inner' ); ?>

                <?php
                // Check if posts exist
                if ( have_posts() ) :

                    // Elementor `archive` location
                    if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'archive' ) ) {


                        // Add Support For WPJM Archive Pages
                        if( is_post_type_archive('job_listing') || ( is_tax() && is_tax(get_object_taxonomies( 'job_listing' )) ) ) {
                            $pagination = '';
                            $pagination = 'show_pagination="true"';
                            $listing_layout = 'grid_1';
                            $shortcode = '[jobs show_tags="true" show_more="false" '.$pagination.' orderby="featured" order="DESC" listing_layout="'.$listing_layout.'"]';
                            echo do_shortcode(  $shortcode );
                        }
                        elseif( is_post_type_archive('la_portfolio') || ( is_tax() && is_tax(get_object_taxonomies( 'la_portfolio' )) ) ){

                        }
                        else{

                            $pagination_extra_attr = '';
                            $pagination_extra_cssclass = '';

                            $blog_thumbnail_height_mode = skudmart_get_option('blog_thumbnail_height_mode', 'original');
                            $blog_design = skudmart_get_option('blog_design');
                            $blog_pagination_type = skudmart_get_option('blog_pagination_type', 'pagination');
                            $is_grid_layout = false;
                            $data_js_component = array();
                            $blog_wrap_classes = array('entries', 'la-loop', 'lastudio-posts', 'blog__entries');
                            $blog_wrap_classes[] = 'preset-' . $blog_design;

                            if($blog_thumbnail_height_mode != 'original'){
                                $blog_wrap_classes[] = 'active-object-fit';
                            }

                            if(false !== strpos($blog_design, 'grid')){
                                $is_grid_layout = true;
                                $blog_wrap_classes[] = 'lastudio-posts--grid';
                            }
                            else{
                                $blog_wrap_classes[] = 'lastudio-posts--list';
                            }
                            if($is_grid_layout){
                                $blog_wrap_classes[] = 'grid-items';
                                $blog_wrap_classes[] = skudmart_get_responsive_column_classes('blog_post_column', array(
                                    'mobile' => 1,
                                    'tablet' => 1
                                ));
                                if(skudmart_string_to_bool(skudmart_get_option('blog_masonry'))){
                                    $blog_wrap_classes[] = 'la-isotope-container';
                                    $data_js_component[] = 'DefaultMasonry';
                                }
                            }
                            if($blog_pagination_type == 'infinite_scroll'){
                                $blog_wrap_classes[] = 'la-infinite-container';
                                $data_js_component[] = 'InfiniteScroll';
                                $pagination_extra_cssclass .= ' la-ajax-pagination active-loadmore active-infinite-loadmore';
                                $pagination_extra_attr = 'data-parent-container="#content" data-container="#content #blog-entries" data-item-selector=".lastudio-posts__item" data-ajax_request_id="main-blog" data-infinite-flag="#content > .infinite-flag"';
                            }
                            elseif($blog_pagination_type == 'load_more'){
                                $blog_wrap_classes[] = 'la-infinite-container infinite-show-loadmore';
                                $pagination_extra_cssclass .= ' la-ajax-pagination active-loadmore';
                                $pagination_extra_attr = 'data-parent-container="#content" data-container="#content #blog-entries" data-item-selector=".lastudio-posts__item" data-ajax_request_id="main-blog"';
                            }
                            if(!empty($data_js_component)){
                                $blog_wrap_classes[] = 'js-el';
                            }

                            ?>
                            <div id="blog-entries" data-infinite-flag="#content > .infinite-flag" data-pagination="#content > .la-pagination" class="<?php echo skudmart_blog_wrap_classes(join(' ', $blog_wrap_classes)); ?>" <?php if(!empty($data_js_component)){ echo ' data-la_component="'.esc_attr(json_encode($data_js_component)).'" data-item_selector=".loop__item"'; } ?>>

                                <?php
                                // Loop through posts
                                while ( have_posts() ) : the_post(); ?>
                                    <?php
                                    // Get post entry content
                                    get_template_part( 'partials/entry/layout', get_post_type() ); ?>

                                <?php endwhile; ?>

                            </div><!-- #blog-entries -->
                            <?php

                            // Display post pagination
                            skudmart_the_pagination(array(
                                'pagi_data' => array(
                                    'class' => $pagination_extra_cssclass,
                                    'attr'  => $pagination_extra_attr,
                                )
                            ));

                        }

                    }
                    ?>

                <?php
                // No posts found
                else : ?>

                    <?php
                    // Display no post found notice
                    get_template_part( 'partials/none' ); ?>

                <?php endif; ?>

                <?php do_action( 'skudmart/action/after_content_inner' ); ?>

            </div><!-- #content -->

            <?php do_action( 'skudmart/action/after_content' ); ?>

        </div><!-- #primary -->

        <?php do_action( 'skudmart/action/after_primary' ); ?>

    </div><!-- #content-wrap -->

    <?php do_action( 'skudmart/action/after_content_wrap' ); ?>

<?php get_footer();?>

