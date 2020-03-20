<?php
/*
    Template Name: hommes
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
    background-color: white;
}

</style>
    <?php do_action( 'skudmart/action/before_content_wrap' ); ?>
<div class="container-fluid" style="background-color:black">
<div id="content-wrap" class="container" >
               <div class="entete_collection" >
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
        


        <?php do_action( 'skudmart/action/after_primary' ); ?>

    </div><!-- #content-wrap -->
    </div>
<div class="container">
        <div id="primary" class="content-area">

            <?php do_action( 'skudmart/action/before_content' ); ?>

            <div id="content" class="site-content">
                <div class="filtresContent">
                    <?php include('filtres.php'); ?>
                </div>
            </div><!-- #content -->

            <?php do_action( 'skudmart/action/after_content' ); ?>

        </div><!-- #primary -->    
</div>
    <?php do_action( 'skudmart/action/after_content_wrap' ); ?>

<?php get_footer();?>
<script>
  
    jQuery( document ).ready(function() {
    let monture = `{"couleur1":"","couleur2":"","couleur3":"","couleur4":"","couleur5":"","couleur6":"","couleur7":"","couleur8":"","couleur9":"","couleur10":"","couleur11":"","couleur12":"","couleur13":"","couleur14":"","couleur15":"","couleur16":"","cible":"F","matiere":"","forme":"","nom":"","usage":""}`;
    let data = "json="+monture;
        jQuery.ajax({
           url : 'http://localhost/webservice_mazette/symfony/web/app_dev.php/vfmontures/', 
           type : 'GET',
           dataType : 'json',
           data: data,
           success : function(obj, statut){
               jQuery(`<div class="row justify-content-around" id="affichageMontures"  style="background-color:white"></div>`).appendTo("#content");
               let modelTemp="";
               for(const o in obj){
                   let obj_monture = obj[o];
                   let nomMonture =obj_monture.nom;
                   let sortie = new Date(obj_monture.sortieCollection.timestamp * 1000);
                   let sortieJour = sortie.getDay();
                   let sortieMois= sortie.getMonth();
                   let sortieA= sortie.getFullYear();
                   let item=obj_monture.itemcode;
                   let cible = obj_monture.cible;
                   let matiere = obj_monture.matiere;
                   let forme = obj_monture.forme;
                   let usage = obj_monture.usage;
                   let taille = obj_monture.taille;
                   let collection = obj_monture.collection;
                   let couleur = obj_monture.couleur;
                   
                   if(modelTemp!==nomMonture){                      
                       jQuery(`<div class="col-xs-12 col-md-6 col-lg-6  slide-title" id="`+nomMonture+`Col"  aria-describedby="`+nomMonture.toUpperCase()+`"  ></div>`).appendTo("#affichageMontures")
                       jQuery(`<div id="`+nomMonture+`Model" class="frameModel" color="`+couleur.toLowerCase()+`" data-color="`+couleur.toLowerCase()+`"></div>`).appendTo("#"+nomMonture+"Col");
                    jQuery(`<div id="`+item+`" color="`+couleur.toLowerCase()+`" data-color="`+couleur.toLowerCase()+`">
                     <img src="https://revendeurs.angeleyes-eyewear.com/EspaceRevendeur/pic/`+item+`_1.jpg" width="500px" style="margin:auto">
                    </div>`).appendTo("#"+nomMonture+"Model"); 
                    
                   }else {
                    //let frameModel= jQuery("."+nomMonture).last();
                    jQuery(`<div id="`+item+`" color="`+couleur.toLowerCase()+`" data-color="`+couleur.toLowerCase()+`">
                     <img src="https://revendeurs.angeleyes-eyewear.com/EspaceRevendeur/pic/`+item+`_1.jpg" width="500px" style="margin:auto">
                    </div>`).appendTo("#"+nomMonture+"Model");                       
                   }
                   modelTemp=nomMonture;
               }
           },
           error : function(resultat, statut, erreur){
               console.log(resultat+" "+statut+" "+erreur);
           },
           complete : function(resultat, statut){              
                jQuery(document).ready(function(){
                  jQuery('.frameModel').slick({
                    dots: true,
                    infinite: true,
                    speed: 500,
                    fade: true,
                    cssEase: 'linear',
                    prevArrow: '',
                    nextArrow: '',
                    customPaging: function(slider,index) {
                      let div = slider.$slides[index];                   
		      var color = div.getAttribute('color');
		      color = color.toLowerCase();                        
                      return '<div class="custom-dot dot-'+color+'"></div>';
                    }                    
                  });
                });
                jQuery('.slide-title').each(function () {
                    var slide = jQuery(this);    
                    var slideChild= slide.first();
                    var nbColoris = slideChild.find(".slick-track").children().length;
                    if (slide.attr('aria-describedby') !== '') { // ignore extra/cloned slides
                        jQuery(`<div class="monturesDescription">`+slide.attr('aria-describedby')+`</div>`).appendTo(slide);
                        jQuery(`<div class="monturesNbColoris"> Disponible en `+nbColoris+` colori(s)</div>`).appendTo(slide);
                    }
                });                
           }
        });
    });        
</script>
