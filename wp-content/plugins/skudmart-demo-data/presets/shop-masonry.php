<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_preset_shop_masonry()
{
    return [
        [
            'filter_name'       => 'skudmart/filter/current_title',
            'filter_func'       => function( $title ) {
                $title = 'Shop Masonry';
                return $title;
            },
            'filter_priority'   => 10,
            'filter_args'       => 1
        ],
        [
            'key' => 'header_transparency_archive_product',
            'value' => 'yes'
        ],

        [
            'key' => 'page_title_bar_border',
            'value' => [
                'top' => '  '
            ]
        ],

        [
            'key' => 'page_title_bar_background',
            'value' => [
                'background-image' => [
                    'url' => '//skudmart.la-studioweb.com/wp-content/uploads/2019/09/shop-masonry-header-bg.jpg'
                ],
                'background-position' => 'center center',
                'background-size' => 'cover'
            ]
        ],
        [
            'key'   => 'page_title_bar_heading_fonts',
            'value' => [
                'color' => '#fff',
                'text-transform' => 'uppercase',
                'font-weight' => '400',
                'responsive' => 'yes',
                'unit'      => 'px',
                'font-size' => [
                    'mobile'    => '24',
                    'tablet'    => '34',
                    'desktop'   => '46',
                ],
                'letter-spacing' => [
                    'tablet'    => '2',
                    'laptop'    => '5',
                    'desktop'   => '10',
                ]
            ]
        ],
        [
            'key'   => 'page_title_bar_breadcrumb_fonts',
            'value' => [
                'color' => '#fff',
                'text-transform' => 'uppercase',
                'responsive' => 'yes',
                'unit'      => 'px',
                'font-size' => [
                    'mobile'    => '12'
                ],
                'letter-spacing' => [
                    'mobile'    => '.5'
                ]
            ]
        ],
        [
            'key'   => 'page_title_bar_link_color',
            'value' => '#fff'
        ],
        [
            'key' => 'page_title_bar_space',
            'value' => [
                'desktop' => [
                    'top' => 220,
                    'bottom' => 140,
                ],
                'laptop' => [
                    'top' => 180,
                    'bottom' => 100,
                ],
                'mobile' => [
                    'top' => 140,
                    'bottom' => 50,
                ],
            ]
        ],

        [
            'key' => 'main_space_archive_product',
            'value' => [
                'laptop' => [
                    'top' => 60,
                    'bottom' => 60,
                ]
            ]
        ],

        [
            'key' => 'shop_catalog_grid_style',
            'value' => '6'
        ],
        [
            'key' => 'woocommerce_pagination_type',
            'value' => 'load_more'
        ],
        [
            'key' => 'product_masonry_image_size',
            'value' => 'full'
        ],
        [
            'key' => 'woocommerce_toggle_grid_list',
            'value' => 'off'
        ],
        [
            'key' => 'active_shop_masonry',
            'value' => 'on'
        ],
        [
            'key' => 'shop_masonry_column_type',
            'value' => 'custom'
        ],
        [
            'key' => 'product_masonry_container_width',
            'value' => 1760
        ],
        [
            'key' => 'product_masonry_item_width',
            'value' => 570
        ],
        [
            'key' => 'product_masonry_item_height',
            'value' => 630
        ],
        [
            'key' => 'woocommerce_shop_masonry_custom_columns',
            'value' => [
                'mobile' => 2,
                'mobile_landscape' => 2,
                'tablet' => 3,
                'laptop' => 3
            ]
        ],
        [
            'key' => 'enable_shop_masonry_custom_setting',
            'value' => 'on'
        ],
        [
            'key' => 'shop_masonry_item_setting',
            'value' => [
                0 => [
                    'size_name' => '1w x 1h',
                    'w'         => 1,
                    'h'         => 1,
                ],
                1 => [
                    'size_name' => '1w x .8h',
                    'w'         => 1,
                    'h'         => .7,
                ],
                2 => [
                    'size_name' => '1w x 1h',
                    'w'         => 1,
                    'h'         => 1,
                ],
                3 => [
                    'size_name' => '1w x 1h',
                    'w'         => 1,
                    'h'         => .9,
                ]
            ]
        ],
        [
            'key' => 'shop_item_space',
            'value' => [
                'laptop' => [
                    'left' => '12',
                    'right' => '12',
                    'bottom' => '24'
                ],
                'mobile' => [
                    'left' => '5',
                    'right' => '5',
                    'bottom' => '10'
                ]
            ]
        ],
        [
            'filter_name'       => 'skudmart/filter/get_option',
            'filter_func'       => function( $value, $key ) {
                if( $key == 'la_custom_css'){
                    $value .= '.la-pagination.active-loadmore {margin-top: 2em;}';
                }
                return $value;
            },
            'filter_priority'   => 10,
            'filter_args'       => 2
        ],
        [
            'filter_name'       => 'LaStudio_Builder/logo_transparency_id',
            'filter_func'       => function( $value) {
                $value = 673;
                return $value;
            },
            'filter_priority'   => 10,
            'filter_args'       => 1
        ]
    ];
}