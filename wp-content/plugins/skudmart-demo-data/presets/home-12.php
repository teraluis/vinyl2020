<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_preset_home_12()
{
    return [
        [
            'key'               => 'header_layout',
            'value'             => 'header-layout-06'
        ],
        [
            'filter_name'       => 'LaStudio_Builder/logo_transparency_id',
            'filter_func'       => function(){
                return 673;
            }
        ]
    ];
}