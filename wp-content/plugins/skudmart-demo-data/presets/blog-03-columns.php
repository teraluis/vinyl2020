<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_preset_blog_03_columns()
{
    return [
        [
            'key'       => 'layout_blog',
            'value'     => 'col-1c'
        ],
        [
            'key'       => 'blog_design',
            'value'     => 'grid-3'
        ],
        [
            'key'       => 'main_full_width_archive_post',
            'value'     => 'yes'
        ],
        [
            'key'       => 'blog_excerpt_length',
            'value'     => '26'
        ],
        [
            'key'       => 'blog_thumbnail_height_custom',
            'value'     => '70%'
        ],
    ];
}