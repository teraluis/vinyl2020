<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_preset_blog_no_sidebar()
{
    return [
        [
            'key'       => 'layout_blog',
            'value'     => 'col-1c'
        ],
        [
            'key'       => 'blog_design',
            'value'     => 'list-2'
        ],
        [
            'key'       => 'blog_excerpt_length',
            'value'     => '80'
        ],
        [
            'key'       => 'blog_item_space',
            'value'     => [
                'desktop' => [
                    'bottom' => '90'
                ]
            ]
        ]
    ];
}