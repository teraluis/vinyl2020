<?php if ( ! defined( 'ABSPATH' ) ) { die; }

if(!class_exists('Skudmart_MegaMenu_Init')){
    
    class Skudmart_MegaMenu_Init{

        protected $fields = array();

        protected $default_metakey = '';

        public function __construct() {

            $query_args = array(
                'post_type'         => 'elementor_library',
                'orderby'           => 'title',
                'order'             => 'ASC',
                'posts_per_page'    => -1
            );

            $this->default_metakey = '_mm_meta';
            $this->fields = array(
                'icon' => array(
                    'id'    => 'icon',
                    'type'  => 'icon',
                    'class' => 'lasf-mm-icon',
                    'title' => esc_html__('Custom Icon','skudmart')
                ),
                'only_icon' => array(
                    'id'    => 'only_icon',
                    'type'  => 'switcher',
                    'class' => 'lasf-mm-only-icon',
                    'title' => esc_html__("Show Only Icon",'skudmart')
                ),
                'menu_type' => array(
                    'id'    => 'menu_type',
                    'class' => 'lasf-mm-menu-type',
                    'type'  => 'select',
                    'title' => esc_html__('Menu Type','skudmart'),
                    'options' => array(
                        'narrow'      => esc_html__('Narrow','skudmart'),
                        'wide'  => esc_html__('Wide','skudmart')
                    ),
                    'default' => 'narrow'
                ),
                'force_full_width' => array(
                    'id'    => 'force_full_width',
                    'class' => 'lasf-mm-force-full-width',
                    'type'  => 'switcher',
                    'title' => esc_html__('Force Full Width','skudmart'),
                    'desc' => esc_html__('Set 100% window width for popup','skudmart')
                ),
                'popup_max_width' =>array(
                    'id'     => 'popup_max_width',
                    'class' => 'lasf-mm-popup-max-width',
                    'type'   => 'dimensions',
                    'title'  => esc_html__('Popup Max Width','skudmart'),
                    'height' => false,
                    'units'  => array( 'px' ),
                ),
                'popup_column' => array(
                    'id'    => 'popup_column',
                    'class' => 'lasf-mm-popup-column',
                    'type'  => 'select',
                    'title' => esc_html__('Popup Columns','skudmart'),
                    'options' => array(
                        '1'         => esc_html__('1 Column','skudmart'),
                        '2'         => esc_html__('2 Columns','skudmart'),
                        '3'         => esc_html__('3 Columns','skudmart'),
                        '4'         => esc_html__('4 Columns','skudmart'),
                        '5'         => esc_html__('5 Columns','skudmart'),
                        '6'         => esc_html__('6 Columns','skudmart')
                    ),
                    'default'   => '4'
                ),
                'item_column' => array(
                    'id'    => 'item_column',
                    'class' => 'lasf-mm-item-column',
                    'type'  => 'text',
                    'title' => esc_html__('Columns','skudmart'),
                    'subtitle' => esc_html__('will occupy x columns of parent popup columns', 'skudmart')
                ),
                'block' => array(
                    'id'            => 'block',
                    'class'         => 'lasf-mm-block',
                    'type'          => 'select',
                    'title'         => esc_html__('Custom Block Before Menu Item','skudmart'),
                    'options'       => 'posts',
                    'query_args'    => $query_args,
                    'placeholder' => esc_html__('Select a block','skudmart')
                ),
                'block2' => array(
                    'id'            => 'block2',
                    'class'         => 'lasf-mm-block2',
                    'type'          => 'select',
                    'title'         => esc_html__('Custom Block After Menu Item','skudmart'),
                    'options'       => 'posts',
                    'query_args'    => $query_args,
                    'placeholder' => esc_html__('Select a block','skudmart')
                ),
                'popup_background' => array(
                    'id'           => 'popup_background',
                    'class'         => 'lasf-mm-popup-background',
                    'type'         => 'background',
                    'title' 	   => esc_html__('Popup Background','skudmart')
                ),
                'tip_label' => array(
                    'id'        => 'tip_label',
                    'class'     => 'lasf-mm-tip-label',
                    'type'      => 'text',
                    'title' 	=> esc_html__('Tip Label','skudmart')
                ),
                'tip_color' => array(
                    'id'        => 'tip_color',
                    'class'     => 'lasf-mm-tip-color',
                    'type'      => 'color',
                    'title' 	=> esc_html__('Tip Color','skudmart')
                ),
                'tip_background_color' => array(
                    'id'        => 'tip_background_color',
                    'class'     => 'lasf-mm-tip-background-color',
                    'type'      => 'color',
                    'title' 	=> esc_html__('Tip Background','skudmart')
                )
            );

            $this->load_hooks();
        }

        private function load_hooks(){
            add_action( 'wp_loaded',                        array( $this, 'load_walker_edit' ), 9);
            add_filter( 'wp_setup_nav_menu_item',           array( $this, 'setup_nav_menu_item' ));
            add_action( 'wp_nav_menu_item_custom_fields',   array( $this, 'add_megamu_field_to_menu_item' ), 10, 4);
            add_action( 'wp_update_nav_menu_item',          array( $this, 'update_nav_menu_item' ), 10, 3);
            add_filter( 'nav_menu_item_title',              array( $this, 'add_icon_to_menu_item' ),10, 4);
        }

        public function load_walker_edit() {
            add_filter( 'wp_edit_nav_menu_walker', array( $this, 'detect_edit_nav_menu_walker' ), 99 );
        }

        public function detect_edit_nav_menu_walker( $walker ) {
            require_once Skudmart_Theme_Class::$template_dir_path . '/framework/classes/class-megamenu-walker-edit.php';
            $walker = 'Skudmart_MegaMenu_Walker_Edit';
            return $walker;
        }

        public function setup_nav_menu_item($menu_item){
            $meta_value = skudmart_get_post_meta($menu_item->ID, '', $this->default_metakey, true);
            foreach ( $this->fields as $key => $value ){
                $menu_item->$key = isset($meta_value[$key]) ? $meta_value[$key] : '';
            }
            return $menu_item;
        }

        public function update_nav_menu_item( $menu_id, $menu_item_db_id, $menu_item_args ) {
            if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
                return;
            }
            check_admin_referer( 'update-nav_menu', 'update-nav-menu-nonce' );

            $key = $this->default_metakey;

            if ( ! empty( $_POST[$key][$menu_item_db_id] ) ) {
                $value = $_POST[$key][$menu_item_db_id];
                if(isset($value['popup_max_width']['width'])){
                    $value['popup_max_width'] = $value['popup_max_width']['width'];
                }
            }
            else {
                $value = null;
            }

            if(!empty($value)){
                update_post_meta( $menu_item_db_id, $key, $value );
            }
            else {
                delete_post_meta( $menu_item_db_id, $key );
            }
        }

        public function add_megamu_field_to_menu_item( $id, $item, $depth, $args ) {
            if(class_exists('LASF')){
                ?><div class="lastudio-megamenu-settings description-wide la-content">
                <h3><?php esc_html_e('MegaMenu Settings','skudmart');?></h3>
                <div class="lastudio-megamenu-custom-fields">
                    <?php
                    foreach ( $this->fields as $key => $field ) {
                        $unique     = $this->default_metakey . '['.$item->ID.']';
                        $default    = ( isset( $field['default'] ) ) ? $field['default'] : '';
                        $elem_id    = ( isset( $field['id'] ) ) ? $field['id'] : '';

                        $field['name'] = $unique. '[' . $elem_id . ']';
                        $field['id'] = $elem_id . '_' . $item->ID;
                        $elem_value =  isset($item->$key) ? $item->$key : $default;

                        if($key == 'popup_max_width'){
                            if(!isset($elem_value['width'])){
                                $elem_value = array(
                                    'width' => absint($elem_value),
                                    'unit'  => 'px'
                                );
                            }
                        }
                        LASF::field( $field, $elem_value, $unique );
                    }
                    ?>
                </div>
                </div><?php
            }
        }

        public function add_icon_to_menu_item($output, $item, $args, $depth){
            if ( !is_a( $args->walker, 'Skudmart_MegaMenu_Walker' ) && $item->icon){
                $icon_class = 'mm-icon ' . $item->icon;
                $icon = "<i class=\"".esc_attr($icon_class)."\"></i>";
                $output = $icon . $output;
            }
            return $output;
        }
    }
}