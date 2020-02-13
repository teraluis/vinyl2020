<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_preset_shop_left_sidebar()
{
    return [
        [
            'key'               => 'layout_archive_product',
            'value'             => 'col-2cl'
        ],
        [
            'filter_name'       => 'skudmart/filter/current_title',
            'filter_func'       => function( $title ) {
                $title = 'Shop left sidebar';
                return $title;
            },
            'filter_priority'   => 10,
            'filter_args'       => 1
        ],
        [
            'key'               => 'shop_item_space',
            'value'             => [
                'laptop' => [
                    'left' => '15',
                    'right' => '15',
                    'bottom' => '30'
                ],
                'mobile' => [
                    'left' => '5',
                    'right' => '5',
                    'bottom' => '10'
                ]
            ]
        ],
        [
            'key'               => 'woocommerce_shop_page_columns',
            'value'             => [
                'desktop' => 3,
                'laptop' => 3,
                'tablet' => 3,
                'mobile_landscape' => 2,
                'mobile' => 2
            ]
        ]
    ];
}