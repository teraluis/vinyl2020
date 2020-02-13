<?php
/**
 * Testimonials item template
 */


$preset = $this->get_settings( 'preset' );

$class_array = array('lastudio-testimonials__item', 'grid-item');

$item_image = $this->__loop_item( array( 'item_image', 'url' ), '%s' );

$item_image = apply_filters('lastudio_wp_get_attachment_image_url', $item_image);

?>
<div class="<?php echo esc_attr(join(' ', $class_array)); ?>">
	<div class="lastudio-testimonials__item-inner">
		<div class="lastudio-testimonials__content"><?php
            if(!empty($item_image)){
                echo sprintf('<div class="lastudio-testimonials__figure"><div class="lastudio-testimonials__tag-img la-lazyload-image" data-background-image="%s"></div></div>', $item_image );
            }

            echo $this->__loop_item( array( 'item_comment' ), '<p class="lastudio-testimonials__comment"><span>%s</span></p>' );
            echo $this->__loop_item( array( 'item_name' ), '<div class="lastudio-testimonials__name"><span>%s</span></div>' );
            echo $this->__loop_item( array( 'item_position' ), '<div class="lastudio-testimonials__position"><span>%s</span></div>' );

            $item_rating = $this->__loop_item( array( 'item_rating' ), '%d' );
            if(absint($item_rating)> 0){
                $percentage =  (absint($item_rating) * 10) . '%';
                echo '<div class="lastudio-testimonials__rating"><span class="star-rating"><span style="width: '.$percentage.'"></span></span></div>';
            }
		?></div>
	</div>
</div>