<?php
// Do not allow directly accessing this file.
if ( ! defined( 'ABSPATH' ) ) {
    exit( 'Direct script access denied.' );
}

function la_skudmart_preset_home_10()
{
    return [
        [
            'key'               => 'header_transparency',
            'value'             => 'yes'
        ],
        [
            'filter_name'       => 'skudmart/filter/get_option',
            'filter_func'       => function( $value, $key ) {
                if( $key == 'la_custom_css'){
                    $value .= '
.enable-header-transparency .lahb-wrap:not(.is-sticky) .lahb-screen-view .lahb-row1-area .lahb-element:not(.lahb-nav-wrap) a,
.enable-header-transparency .lahb-wrap:not(.is-sticky) .lahb-screen-view .lahb-row1-area .lahb-element, 
.enable-header-transparency .lahb-wrap:not(.is-sticky) .lahb-screen-view .lahb-row1-area .lahb-search .search-field{
    color:#1d1d1d
}
.enable-header-transparency .lahb-wrap:not(.is-sticky) .logo--transparency{
    display: none;
}
.enable-header-transparency .lahb-wrap:not(.is-sticky) .logo--normal{
    display: inline-block;
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