<?php
/*
    Template Name: femmes
*/
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
get_header(); ?>
<style>
#main #content-wrap {
    padding-top: 16px;
    padding-bottom: 50px;
}
#main {
    background-color: black;
}

</style>
    <?php do_action( 'skudmart/action/before_content_wrap' ); ?>

    <div id="content-wrap" class="container">
               <div class="entete_collection">
                   <div class="row">
                       <div class="col description_collection">
                           <div class="titre_collection"><?php the_title(); ?></div>
                           <div class="breadcump-lunettes">
                               <a href="<?php get_home_url(); ?>">ACCUEIL</a> >
                               <?php
                               $ancestors = get_post_ancestors($post);
                                foreach ($ancestors as $crumb) {
                                echo '<a href="'.get_permalink($crumb).'">'.get_the_title($crumb).'</a> > ';
                                }
                                echo get_the_title();
                               ?>
                           </div>
                           <div class="descriptif-collection">
                               contenu
                               <?php 
                               the_field("description");
                               ?>
                           </div>
                       </div>
                       <div class="col ">
                           <div class="clodos">
                               
                           </div>
                       </div>
                   </div>
                </div>
        <?php do_action( 'skudmart/action/before_primary' ); ?>
 
        <div id="primary" class="content-area">

            <?php do_action( 'skudmart/action/before_content' ); ?>

            <div id="content" class="site-content">



            </div><!-- #content -->

            <?php do_action( 'skudmart/action/after_content' ); ?>

        </div><!-- #primary -->

        <?php do_action( 'skudmart/action/after_primary' ); ?>

    </div><!-- #content-wrap -->

    <?php do_action( 'skudmart/action/after_content_wrap' ); ?>

<?php get_footer();?>

