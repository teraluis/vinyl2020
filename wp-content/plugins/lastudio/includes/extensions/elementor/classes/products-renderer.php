<?php
namespace LaStudio_Element\Classes;

if (!defined('WPINC')) {
    die;
}

class Products_Renderer extends \WC_Shortcode_Products {



    private $unique_id;
    private $original_unique_id;
	private $settings = [];
	private $is_added_product_filter = false;
	const QUERY_CONTROL_NAME = 'query'; //Constraint: the class that uses the renderer, must use the same name

    public static $displayed_ids = [];

	public function __construct( $settings = [], $type = 'products' ) {

        $unique_id = isset($settings['unique_id']) ? $settings['unique_id'] : uniqid('elwc');
        $el_id = isset($settings['el_id']) ? $settings['el_id'] : 0;

        $this->unique_id = $unique_id .'_'. $el_id;
        $this->original_unique_id = $unique_id;

		$this->settings = $settings;
		$this->type = $type;
		$this->attributes = $this->parse_attributes( [
			'columns' => $settings['columns'],
			'limit' => $settings['limit'],
			'paginate' => $settings['paginate'],
			'cache' => false
		] );
		$this->query_args = $this->parse_query_args();
	}

    public static function add_to_avoid_list( $ids ) {
        self::$displayed_ids = array_merge( self::$displayed_ids, $ids );
    }

    public static function get_avoid_list_ids() {
        return self::$displayed_ids;
    }

	/**
	 * Override the original `get_query_results`
	 * with modifications that:
	 * 1. Remove `pre_get_posts` action if `is_added_product_filter`.
	 *
	 * @return bool|mixed|object
	 */

	protected function get_query_results() {
		$results = parent::get_query_results();
		// Start edit.
		if ( $this->is_added_product_filter ) {
			remove_action( 'pre_get_posts', [ wc()->query, 'product_query' ] );
		}
		// End edit.

        if ( $results && $results->ids ) {
            self::add_to_avoid_list($results->ids);
        }

		return $results;
	}

	protected function parse_query_args() {
		$prefix = self::QUERY_CONTROL_NAME . '_';
		$settings = &$this->settings;

		$query_args = [
			'post_type' => 'product',
			'post_status' => 'publish',
			'ignore_sticky_posts' => true,
			'no_found_rows' => false === wc_string_to_bool( $this->attributes['paginate'] ),
			'orderby' => $settings[ $prefix . 'orderby' ],
			'order' => strtoupper( $settings[ $prefix . 'order' ] ),
		];

		$query_args['meta_query'] = WC()->query->get_meta_query();
		$query_args['tax_query'] = [];

		if ( 'yes' === $settings['paginate'] && 'yes' === $settings['allow_order'] ) {
			$ordering_args = WC()->query->get_catalog_ordering_args();
		} else {
			$ordering_args = WC()->query->get_catalog_ordering_args( $query_args['orderby'], $query_args['order'] );
		}

		$query_args['orderby'] = $ordering_args['orderby'];
		$query_args['order'] = $ordering_args['order'];
		if ( $ordering_args['meta_key'] ) {
			$query_args['meta_key'] = $ordering_args['meta_key'];
		}

		// Visibility.
		$this->set_visibility_query_args( $query_args );

		//Featured.
		$this->set_featured_query_args( $query_args );

		// IDs.
		$this->set_ids_query_args( $query_args );

		// Set specific types query args.
		if ( method_exists( $this, "set_{$this->type}_query_args" ) ) {
			$this->{"set_{$this->type}_query_args"}( $query_args );
		}

		// Categories & Tags
		$this->set_terms_query_args( $query_args );

		//Exclude.
		$this->set_exclude_query_args( $query_args );

		//Avoid Duplicates
		$this->set_avoid_duplicates( $query_args );

		$paged_key = 'products' . $this->unique_id;

		if ( 'yes' === $settings['paginate'] ) {

            $page = absint( empty( $_GET[$paged_key] ) ? 1 : $_GET[$paged_key] );

            if ( 1 < $page ) {
                $query_args['paged'] = $page;
            }

			if ( 'yes' !== $settings['allow_order'] ) {
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 30 );
			}

			if ( 'yes' !== $settings['show_result_count'] ) {
				remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
			}
		}
		$query_args['posts_per_page'] = intval( $settings['limit'] );

		$query_args = apply_filters( 'woocommerce_shortcode_products_query', $query_args, $this->attributes, $this->type );

		// Always query only IDs.
		$query_args['fields'] = 'ids';

		return $query_args;
	}

    protected function set_avoid_duplicates( &$query_args ) {

        if( ! empty( $this->settings[ self::QUERY_CONTROL_NAME . '_avoid_duplicates' ] ) ){
            $product__not_in = isset( $query_args['post__not_in'] ) ? $query_args['post__not_in'] : [];
            $product__not_in = array_merge( $product__not_in, self::get_avoid_list_ids() );
            $query_args['post__not_in'] = $product__not_in;
        }

    }

	protected function set_ids_query_args( &$query_args ) {
		$prefix = self::QUERY_CONTROL_NAME . '_';

		switch ( $this->settings[ $prefix . 'post_type' ] ) {
			case 'by_id':
				$post__in = $this->settings[ $prefix . 'posts_ids' ];
				break;
			case 'sale':
				$post__in = wc_get_product_ids_on_sale();
				break;
		}

		if ( ! empty( $post__in ) ) {
			$query_args['post__in'] = $post__in;
			remove_action( 'pre_get_posts', [ wc()->query, 'product_query' ] );
		}
	}

	private function set_terms_query_args( &$query_args ) {
		$prefix = self::QUERY_CONTROL_NAME . '_';

		$query_type = $this->settings[ $prefix . 'post_type' ];

		if ( 'by_id' === $query_type || 'current_query' === $query_type ) {
			return;
		}

		if ( empty( $this->settings[ $prefix . 'include' ] ) || empty( $this->settings[ $prefix . 'include_term_ids' ] ) || ! in_array( 'terms', $this->settings[ $prefix . 'include' ], true ) ) {
			return;
		}

		$terms = [];
		foreach ( $this->settings[ $prefix . 'include_term_ids' ] as $id ) {
			$term_data = get_term_by( 'term_taxonomy_id', $id );
			$taxonomy = $term_data->taxonomy;
			$terms[ $taxonomy ][] = $id;
		}
		$tax_query = [];
		foreach ( $terms as $taxonomy => $ids ) {
			$query = [
				'taxonomy' => $taxonomy,
				'field' => 'term_id',
				'terms' => $ids,
			];

			$tax_query[] = $query;
		}

		if ( ! empty( $tax_query ) ) {
			$query_args['tax_query'] = array_merge( $query_args['tax_query'], $tax_query );
		}
	}

	protected function set_featured_query_args( &$query_args ) {
		$prefix = self::QUERY_CONTROL_NAME . '_';
		if ( 'featured' === $this->settings[ $prefix . 'post_type' ] ) {
			$product_visibility_term_ids = wc_get_product_visibility_term_ids();

			$query_args['tax_query'][] = [
				'taxonomy' => 'product_visibility',
				'field' => 'term_taxonomy_id',
				'terms' => [ $product_visibility_term_ids['featured'] ],
			];
		}
	}

    protected function set_exclude_query_args( &$query_args ) {
        $prefix = self::QUERY_CONTROL_NAME . '_';

        if ( empty( $this->settings[ $prefix . 'exclude' ] ) ) {
            return;
        }

        $post__not_in = [];

        if ( in_array( 'current_post', $this->settings[ $prefix . 'exclude' ] ) ) {
            if ( is_singular() ) {
                $post__not_in[] = get_queried_object_id();
            }
        }

        if ( in_array( 'manual_selection', $this->settings[ $prefix . 'exclude' ] ) && ! empty( $this->settings[ $prefix . 'exclude_ids' ] ) ) {
            $post__not_in = array_merge( $post__not_in, $this->settings[ $prefix . 'exclude_ids' ] );
        }

        $query_args['post__not_in'] = empty( $query_args['post__not_in'] ) ? $post__not_in : array_merge( $query_args['post__not_in'], $post__not_in );

        if ( in_array( 'terms', $this->settings[ $prefix . 'exclude' ] ) && ! empty( $this->settings[ $prefix . 'exclude_term_ids' ] ) ) {
            $terms = [];
            foreach ( $this->settings[ $prefix . 'exclude_term_ids' ] as $to_exclude ) {
                $term_data = get_term_by( 'term_taxonomy_id', $to_exclude );
                $terms[ $term_data->taxonomy ][] = $to_exclude;
            }
            $tax_query = [];
            foreach ( $terms as $taxonomy => $ids ) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $ids,
                    'operator' => 'NOT IN',
                ];
            }
            if ( empty( $query_args['tax_query'] ) ) {
                $query_args['tax_query'] = $tax_query;
            } else {
                $query_args['tax_query']['relation'] = 'AND';
                $query_args['tax_query'][] = $tax_query;
            }
        }
    }

    protected function product_loop() {

        $globalWcLoopTmp            = array();
        $columns                    = absint( $this->attributes['columns'] ) ;
        $wrapper_classes            = $this->get_wrapper_classes( $columns );
        $layout                     = !empty($this->settings['layout']) ? $this->settings['layout'] : 'grid';
        $style                      = $this->settings[$layout . '_style'];

        $wrapper_classes[] = 'elementor_wc_widget_' . $this->unique_id;

        $loopCssClass 	= array();
        $container_attr = $disable_alt_image = $image_size = false;
        if( 'yes' == $this->settings['enable_custom_image_size'] ) {
            $image_size = true;
        }
        if( 'yes' == $this->settings['disable_alt_image'] ) {
            $disable_alt_image = true;
        }
        if( $layout == 'grid' ){
            if( 'yes' == $this->settings['carousel_enabled'] ) {
                $container_attr = ' data-la_component="AutoCarousel" ';
                $container_attr .= ' data-slider_config="'. esc_attr($this->settings['carousel_setting']) .'"';
                $loopCssClass[] = 'lastudio-carousel js-el la-slick-slider';
            }
        }

        $globalWcLoopTmp['loop_layout']    = $layout;
        $globalWcLoopTmp['loop_style']     = $style;

        if($image_size){
            $globalWcLoopTmp['image_size'] = $this->settings['image_size'];
        }
        if($disable_alt_image){
            $globalWcLoopTmp['disable_alt_image'] = true;
        }

        $loopCssClass[] = 'products ul_products';

        if($layout != 'list'){
            if( $layout != 'masonry' || ($layout == 'masonry' && 'true' != $this->settings['enable_custom_masonry_layout']) ){
                $loopCssClass[] = 'grid-items';

                $loopCssClass[] = lastudio_element_render_grid_classes([
                    'desktop'  => $this->settings['columns'],
                    'laptop'   => isset($this->settings['columns_laptop']) ? $this->settings['columns_laptop'] : '',
                    'tablet'   => $this->settings['columns_tablet'],
                    'mobile'  => isset($this->settings['columns_tabletportrait']) ? $this->settings['columns_tabletportrait'] : '',
                    'xmobile'   => $this->settings['columns_mobile']
                ]);
            }
            $loopCssClass[] = 'products-grid';
            $loopCssClass[] = 'products-grid-' . $style;

            if($layout == 'masonry'){

                $loopCssClass[] = 'js-el la-isotope-container';
                $loopCssClass[] = 'prods_masonry';

                if( 'true' == $this->settings['enable_custom_masonry_layout'] ) {
                    $loopCssClass[] = 'masonry__column-type-custom';
                    $container_attr  = ' data-la_component="AdvancedMasonry"';
                }
                else{
                    $container_attr  = ' data-la_component="DefaultMasonry"';
                }

                $container_attr .= ' data-item-width="' . ( $this->settings['masonry_item_base_width'] ? intval($this->settings['masonry_item_base_width']) : 300 ) . '"';
                $container_attr .= ' data-item-height="' . ( $this->settings['masonry_item_base_height'] ? intval($this->settings['masonry_item_base_height']) : 300 ) . '"';
                $container_attr .= ' data-container-width="' . ( $this->settings['masonry_container_width'] ? intval($this->settings['masonry_container_width']) : 1170 ) . '"';
                $container_attr .= ' data-md-col="' . ($this->settings['columns_tablet'] ? $this->settings['columns_tablet'] : 1) . '"';
                $container_attr .= ' data-sm-col="' . (!empty($this->settings['columns_tabletportrait']) ? $this->settings['columns_tabletportrait'] : 1) . '"';
                $container_attr .= ' data-xs-col="' . ($this->settings['columns_mobile'] ? $this->settings['columns_mobile'] : 1) . '"';
                $container_attr .= ' data-mb-col="' . ($this->settings['columns_mobile'] ? $this->settings['columns_mobile'] : 1) . '"';

                if( 'true' == $this->settings['enable_custom_masonry_layout'] ) {
                    $_item_sizes = $this->settings['masonry_item_sizes'];
                    $__new_item_sizes = array();
                    if(!empty($_item_sizes)){
                        foreach($_item_sizes as $k => $size){
                            $__new_item_sizes[$k] = [
                                'w' => $size['item_width'],
                                'h' => $size['item_height']
                            ];
                        }
                    }
                    $globalWcLoopTmp['item_sizes'] = $__new_item_sizes;
                }

                $globalWcLoopTmp['prods_masonry'] = true;

                $container_attr .= ' data-item_selector=".product_item"';
                $container_attr .= ' data-la-effect="sequencefade"';
            }
        }
        else{
            $loopCssClass[] = 'products-' . $layout;
            $loopCssClass[] = 'products-' . $layout . '-' . $style;
        }

        $pagi_data = [
            'unique_id' => $this->unique_id,
            'class' => 'la-ajax-pagination',
            'attr'  => ' data-parent-container=".elementor_wc_widget_'.esc_attr($this->unique_id).'" data-container=".elementor_wc_widget_'.esc_attr($this->unique_id).' ul.ul_products" data-item-selector=".product_item" data-ajax_request_id="'.esc_attr($this->unique_id).'"',
        ];

        if(isset($this->settings['paginate_as_loadmore']) && $this->settings['paginate_as_loadmore'] == 'yes'){
            $globalWcLoopTmp['products__loadmore_ajax_text'] = $this->settings['loadmore_text'];
            $pagi_data['class'] .= ' active-loadmore';
        }

        $globalWcLoopTmp['pagi_data'] = $pagi_data;

        $in_elementor = (isset($this->settings['in_elementor']) && $this->settings['in_elementor']) ? true : false;
        $is_ajax_load_first = (isset($this->settings['enable_ajax_load']) && $this->settings['enable_ajax_load'] == 'yes') ? true : false;

        $paged_key = 'products' . $this->unique_id;

        if(!$in_elementor && $is_ajax_load_first && !isset($_GET[$paged_key])){

            $ajax_html_first = '<div class="js-el ' . esc_attr( implode( ' ', $wrapper_classes ) ) . '" data-la_component="InfiniteScroll" data-pagination=".elementor_wc_widget_'.$this->unique_id.' .la-pagination">';

            $ajax_html_first .= sprintf(
                '<div class="row"><div class="col-xs-12"><ul class="%s"%s></ul></div></div>',
                esc_attr(implode(' ', $loopCssClass)),
                $container_attr ? $container_attr : ''
            );

            $ajax_html_first .= sprintf(
                '<nav class="%1$s" data-parent-container="%2$s" data-container="%2$s ul.ul_products" data-item-selector=".product_item" data-ajax_request_id="%3$s"><div class="la-ajax-loading-outer"><div class="la-loader spinner3"><div class="dot1"></div><div class="dot2"></div><div class="bounce1"></div><div class="bounce2"></div><div class="bounce3"></div><div class="cube1"></div><div class="cube2"></div><div class="cube3"></div><div class="cube4"></div></div></div><div class="products__loadmore_ajax pagination_ajax_loadmore"><a href="javascript:;"><span></span></a></div><ul class="page-numbers"><li><a class="next page-numbers" href="%4$s"></a></li></ul></nav>',
                'woocommerce-pagination la-pagination clearfix la-ajax-pagination la-ajax-load-first',
                '.elementor_wc_widget_' . $this->unique_id,
                $this->unique_id,
                add_query_arg($paged_key, 1, add_query_arg(null, null))
            );

            $ajax_html_first .= '</div>';

            return $ajax_html_first;
        }

        $products = $this->get_query_results();

        $loop_tpl               = array();
        $loop_tpl[]             = "woocommerce/content-product-{$layout}-{$style}.php";
        $loop_tpl[]             = "woocommerce/content-product-{$layout}.php";
        $loop_tpl[]             = "woocommerce/content-product.php";

        ob_start();

        if ( $products && $products->ids ) {

            // Prime meta cache to reduce future queries.
            update_meta_cache( 'post', $products->ids );
            update_object_term_cache( $products->ids, 'product' );

            // Setup the loop.

            wc_setup_loop(
                wp_parse_args($globalWcLoopTmp, array(
                    'columns'      => $columns,
                    'name'         => $this->type,
                    'is_shortcode' => true,
                    'is_search'    => false,
                    'is_paginated' => wc_string_to_bool( $this->attributes['paginate'] ),
                    'total'        => $products->total,
                    'total_pages'  => $products->total_pages,
                    'per_page'     => $products->per_page,
                    'current_page' => $products->current_page
                ))
            );

            $original_post = isset($GLOBALS['post']) ? $GLOBALS['post'] : false;

            // Fire standard shop loop hooks when paginating results so we can show result counts and so on.
            if ( wc_string_to_bool( $this->attributes['paginate'] ) ) {
                //do_action( 'woocommerce_before_shop_loop' );
            }

            if ( wc_get_loop_prop( 'total' ) ) {

                echo sprintf(
                    '<div class="row"><div class="col-xs-12"><ul class="%s"%s>',
                    esc_attr(implode(' ', $loopCssClass)),
                    $container_attr ? $container_attr : ''
                );

                foreach ( $products->ids as $product_id ) {
                    $GLOBALS['post'] = get_post( $product_id ); // WPCS: override ok.
                    setup_postdata( $GLOBALS['post'] );
                    // Set custom product visibility when quering hidden products.
                    add_action( 'woocommerce_product_is_visible', array( $this, 'set_product_as_visible' ) );

                    locate_template($loop_tpl, true, false);

                    remove_action( 'woocommerce_product_is_visible', array( $this, 'set_product_as_visible' ) );
                }

                echo '</ul></div></div>';
            }

            if($original_post){
                $GLOBALS['post'] = $original_post; // WPCS: override ok.
            }

            // Fire standard shop loop hooks when paginating results so we can show result counts and so on.
            if ( wc_string_to_bool( $this->attributes['paginate'] ) ) {
                do_action( 'woocommerce_after_shop_loop' );
            }

            do_action( "woocommerce_shortcode_after_{$this->type}_loop", $this->attributes );

            wp_reset_postdata();
            wc_reset_loop();

        }
        else{
            do_action( "woocommerce_shortcode_{$this->type}_loop_no_results", $this->attributes );
        }

        return '<div class="' . esc_attr( implode( ' ', $wrapper_classes ) ) . '">' . ob_get_clean() . '</div>';
    }
}
