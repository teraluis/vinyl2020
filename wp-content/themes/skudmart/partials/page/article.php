<?php
/**
 * Outputs page article
 *
 * @package Skudmart WordPress theme
 */

// Exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) {
    exit;
} ?>

<div class="entry"<?php skudmart_schema_markup( 'entry_content' ); ?>>

    <?php do_action( 'skudmart/action/before_page_entry' ); ?>

	<?php the_content();

	wp_link_pages( array(
		'before' => '<div class="clearfix"></div><div class="page-links">' . __( 'Pages:', 'skudmart' ),
		'after'  => '</div>',
	) );
	?>
    <div class="clearfix"></div>
    <?php

    // Display comments
    if ( comments_open() || get_comments_number() ) {
        comments_template();
    }

    ?>

    <?php do_action( 'skudmart/action/after_page_entry' ); ?>

</div>