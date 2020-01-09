<?php
// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_post_type = get_post_type();

$post_css_class = array('single-content-article');
$post_css_class[] = 'single-' . $current_post_type . '-article';

?>
<article class="<?php echo esc_attr(join(' ', $post_css_class)); ?>">
    <div class="entry"<?php skudmart_schema_markup( 'entry_content' ); ?>>

        <?php do_action( 'skudmart/action/before_single_entry' ); ?>
        <?php the_content();
        wp_link_pages( array(
            'before' => '<div class="page-links">' . __( 'Pages:', 'skudmart' ),
            'after'  => '</div>',
        ) ); ?>
        <?php do_action( 'skudmart/action/after_single_entry' ); ?>
    </div>

    <?php

    // Display comments
    comments_template(); ?>

</article>