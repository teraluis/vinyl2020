<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
?>
<header id="lastudio-header-builder" class="<?php echo skudmart_header_classes(); ?>"<?php skudmart_schema_markup( 'header' ); ?>>
    <?php
    if ( ! function_exists( 'elementor_theme_do_location' ) || ! elementor_theme_do_location( 'header' ) ) {
        $value = skudmart_get_header_layout();
        if (class_exists('LAHB')) {
            $data = false;
            if (!empty($value) && $value != 'inherit') {
                if (!is_admin() && !isset($_GET['lastudio_header_builder'])) {
                    $data = LAHB_Helper::get_data_frontend_component_with_preset($value, $data);
                }
            }
            echo LAHB_Output::output(false, $data, $value, false);
        }
        else {
            get_template_part('partials/header/content', $value);
        }
    }
    ?>
</header>