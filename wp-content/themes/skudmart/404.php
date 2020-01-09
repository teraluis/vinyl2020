<?php
/**
/**
 * The template for displaying all pages.
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that other
 * 'pages' on your WordPress site will use a different template.
 *
 * Learn more: https://codex.wordpress.org/Template_Hierarchy
 *
 * @package Skudmart WordPress theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
get_header(); ?>

<?php do_action( 'skudmart/action/before_content_wrap' ); ?>

<?php
$content_404 = skudmart_get_option('404_page_content');
?>

    <div id="content-wrap" class="container">

        <?php do_action( 'skudmart/action/before_primary' ); ?>

        <div id="primary" class="content-area">

            <?php do_action( 'skudmart/action/before_content' ); ?>

            <div id="content" class="site-content">

                <?php do_action( 'skudmart/action/before_content_inner' ); ?>

                <article class="entry">

                    <?php

                    // Elementor `404` location
                    if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'single' ) ) {

                        if(!empty($content_404)){
                            echo '<div class="customerdefine-404-content">';

                            echo wp_kses_post(skudmart_transfer_text_to_format( $content_404, true));

                            echo '</div>';
                        }

                        else{ ?>
                        <div class="default-404-content">
                            <div class="col-12">
                                <div class="default-404-content-inner">
                                    <h1><?php echo esc_html_x('404', 'front-end', 'skudmart') ?></h1>
                                    <h4><?php echo esc_html_x('Page Cannot Be Found!', 'front-end', 'skudmart') ?></h4>
                                    <p class="button-wrapper"><a class="button" href="<?php echo esc_url(home_url('/')) ?>"><?php echo esc_html_x('Back to home', 'front-view','skudmart')?></a></p>
                                </div>
                            </div>
                        </div>
                            <?php
                        }

                    } ?>

                </article>

                <?php do_action( 'skudmart/action/after_content_inner' ); ?>

            </div><!-- #content -->

            <?php do_action( 'skudmart/action/after_content' ); ?>

        </div><!-- #primary -->

        <?php do_action( 'skudmart/action/after_primary' ); ?>

    </div><!-- #content-wrap -->

<?php do_action( 'skudmart/action/after_content_wrap' ); ?>

<?php get_footer();?>