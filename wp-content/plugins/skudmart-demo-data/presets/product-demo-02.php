<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_preset_product_demo_02()
{
    return [
        [
            'key' => 'woocommerce_product_page_design',
            'value' => '1'
        ],
        [
            'key' => 'main_full_width_single_product',
            'value' => 'no'
        ],
        [
            'key' => 'related_product_title',
            'value' => 'Frequently Bought Together'
        ],
        [
            'key' => 'woo_custom_block_single_product',
            'value' => [
                0 => [
                    'title' => 'After Cart Form',
                    'position' => 'pos2',
                    'content' => '<a href="#"><i class="lastudioicon-pin-3-2"></i>Store availability</a><a href="#"><i class="lastudioicon-cart-return"></i>Delivery and return</a><a href="#"><i class="lastudioicon-b-meeting"></i>Ask a Question</a>',
                    'el_class' => 'extradiv-after-frm-cart'
                ],
                1 => [
                    'title' => 'Three Service Icons',
                    'position' => 'pos8',
                    'content' => '[elementor-template id="850"]',
                    'el_class' => ''
                ],
                2 => [
                    'title' => 'Related prod',
                    'position' => 'pos12',
                    'content' => '[elementor-template id="868"]',
                    'el_class' => ''
                ]
            ]
        ],
        [
            'filter_name'       => 'skudmart/filter/get_option',
            'filter_func'       => function( $value, $key ) {
                if( $key == 'la_custom_css'){
                    $value .= '.s_product_content_middle + .la-custom-block .elementor .elementor-top-section {
    margin-top: 0;
    border-bottom-width: 1px;
    margin-bottom: 40px;
}
.wc_tabs_at_bottom .wc-tabs-wrapper {
    margin-bottom: 50px;
}
#main #content-wrap {
    padding-bottom: 40px;
}
@media(min-width: 1600px){
.row.s_product_content_top > .p-left {
    width: 46%;
}
.row.s_product_content_top > .p-right {
    padding-left: 65px;
}
}
#tab-additional_information,
li.additional_information_tab {
    display: none !important;
}

';
                }
                return $value;
            },
            'filter_priority'   => 10,
            'filter_args'       => 2
        ],
    ];
}