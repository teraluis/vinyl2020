<?php
/**
 * After Container template.
 *
 * @package Skudmart WordPress theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
} ?>

<?php do_action( 'skudmart/action/before_content_wrap' ); ?>

<div id="content-wrap" class="container">

    <?php do_action( 'skudmart/action/before_primary' ); ?>

    <div id="primary" class="content-area">

        <?php do_action( 'skudmart/action/before_content' ); ?>

        <div id="content" class="site-content">

            <?php do_action( 'skudmart/action/before_content_inner' ); ?>

            <article class="single-page-article">