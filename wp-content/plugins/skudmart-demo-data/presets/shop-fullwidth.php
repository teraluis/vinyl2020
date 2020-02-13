<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_preset_shop_fullwidth()
{
    return [
        [
            'filter_name'       => 'skudmart/filter/current_title',
            'filter_func'       => function( $title ) {
                $title = 'Shop fullwidth';
                return $title;
            },
            'filter_priority'   => 10,
            'filter_args'       => 1
        ]
    ];
}