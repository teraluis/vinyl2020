<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_preset_blog_right_sidebar()
{
    return [
        [
            'key'       => 'layout_blog',
            'value'     => 'col-2cr'
        ]
    ];
}