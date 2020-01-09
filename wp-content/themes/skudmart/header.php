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
    <link rel="profile" href="//gmpg.org/xfn/11">
    <meta name="apple-mobile-web-app-capable" content="yes" />
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>

<?php do_action('skudmart/action/before_outer_wrap'); ?>

<div id="outer-wrap" class="site">

    <?php do_action('skudmart/action/before_wrap'); ?>

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