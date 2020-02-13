<?php
/**
 * The Header for our theme.
 *
 * @package Skudmart WordPress theme
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?><!DOCTYPE html>
<html <?php language_attributes(); ?><?php skudmart_schema_markup( 'html' ); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <link href="https://fonts.googleapis.com/css?family=Open+Sans+Condensed:300&display=swap" rel="stylesheet">
    <link href="<?php bloginfo('template_directory'); ?>/fonts/DIN_CONDENSED/stylesheet.css" rel="stylesheet">    
    <link href="<?php bloginfo('template_directory'); ?>/assets/css/slick-theme.css" rel="stylesheet">    
    <link rel="profile" href="//gmpg.org/xfn/11">
    <link rel="stylesheet" href="<?php bloginfo('template_directory'); ?>/assets/css/lunettes.css" rel="stylesheet"> 
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <?php wp_head(); ?>
    <style>
        #footer {
            background-color: black;
        }
    </style>
<?php 
if(is_home() || is_front_page()){
    ?>
    <style>
        #section_page_header {
            display: none;
        }
        #main {
            margin-top: -1em;
        }
    </style>
    <?php
}else {
?>
<style>
    #section_page_header {
        display: none;
    }
    #main {
        margin-top: -1em;
    }
</style>
<?php
}
?>
</head>

<body <?php body_class(); ?>>

<?php do_action('skudmart/action/before_outer_wrap'); ?>

<div id="outer-wrap" class="site">
    <?php do_action('skudmart/action/before_wrap'); ?>
    <nav id="menulogo">
        <?php
        $defaults = array(
            'menu'            => '',
            'container'       => false,
            'container_class' => '',
            'container_id'    => '',
            'menu_class'      => 'menu',
            'menu_id'         => '',
            'echo'            => true,
            'fallback_cb'     => 'wp_page_menu',
            'before'          => '',
            'after'           => '',
            'link_before'     => '',
            'link_after'      => '',
            'items_wrap'      => '<ul id="%1$s" class="%2$s">%3$s</ul>',
            'item_spacing'    => 'preserve',
            'depth'           => 0,
            'walker'          => '',
            'theme_location'  => '',
        );
        //wp_nav_menu( $defaults );
        ?>
    </nav>
    <div id="wrap">
        <?php

            do_action('skudmart/action/before_header');

            do_action('skudmart/action/header');

            do_action('skudmart/action/after_header');

        ?>

        <?php do_action('skudmart/action/before_main'); ?>

        <main id="main" class="site-main"<?php skudmart_schema_markup('main') ?>>
            <?php

                do_action('skudmart/action/before_page_header');

                do_action('skudmart/action/page_header');

                do_action('skudmart/action/after_page_header');