<?php
/**
 * Loop item template
 */
$banner_url = $this->__get_banner_image_src();

?>
<figure class="lastudio-banner la-lazyload-image lastudio-effect-<?php $this->__html( 'effect', '%s' ); ?><?php
if( $this->get_settings_for_display( 'custom_height' ) ) {
    echo ' image-custom-height';
}
?>" data-background-image="<?php echo esc_url($banner_url); ?>"><?php
    $target = $this->__get_html( 'link_target', ' target="%s"' );

    echo '<div class="lastudio-banner__overlay"></div>';
    echo $this->__get_banner_image();
    echo '<figcaption class="lastudio-banner__content">';
    echo '<div class="lastudio-banner__content-wrap">';
    $title_tag = $this->__get_html( 'title_tag', '%s' );

    $this->__html( 'title', '<' . $title_tag  . ' class="lastudio-banner__title">%s</' . $title_tag  . '>' );
    $this->__html( 'text', '<div class="lastudio-banner__text">%s</div>' );
    $this->__html( 'btn_text', '<button type="button" class="elementor-button elementor-size-md lastudio-banner__button lastudio-carousel__item-button">%s</button>' );

    echo '</div>';
    $this->__html( 'link', '<a href="%s" class="lastudio-banner__link"' . $target . '>' );
    $this->__html( 'link', '</a>' );
    echo '</figcaption>';
?></figure>