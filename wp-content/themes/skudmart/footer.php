<?php
/**
 * The template for displaying the footer.
 * @package Skudmart WordPress theme
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>

        </main><!-- #main -->

        <?php do_action('skudmart/action/after_main'); ?>

        <?php
            do_action('skudmart/action/before_footer');

            if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'footer' ) ) {
                do_action('skudmart/action/footer');
            }

            do_action('skudmart/action/after_footer');
        ?>
    </div><!-- #wrap -->

    <?php do_action('skudmart/action/after_wrap'); ?>

</div><!-- #outer-wrap-->

<?php do_action('skudmart/action/after_outer_wrap'); ?>

<div class="la-overlay-global"></div>

<?php wp_footer(); ?>

</body>
</html>