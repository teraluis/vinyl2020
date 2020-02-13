<?php

function lahb_logo( $atts, $uniqid, $once_run_flag = true ) {

    if ( !$once_run_flag ){
        return '<div data-element-id="'.esc_attr( $uniqid ).'" class="lahb-element lahb-element--placeholder"></div>';
    }

    $com_uniqid = 'logo' . $uniqid;

	extract( LAHB_Helper::component_atts( array(
		'type'				=> 'image',
		'logo'				=> '',
		'transparent_logo'	=> '',
		'logo_text'			=> '',
		'extra_class'		=> '',
	), $atts ));
	
	$out = $styles = '';

    $tmp_logo = apply_filters('LaStudio_Builder/logo_id', false);
    $tmp_logo_transparency = apply_filters('LaStudio_Builder/logo_transparency_id', $tmp_logo);
    if(!empty($tmp_logo) && !is_attachment($tmp_logo)){
        $logo = $tmp_logo;
    }

    if(!empty($tmp_logo_transparency) && !is_attachment($tmp_logo_transparency)){
        $transparent_logo = $tmp_logo_transparency;
    }

	$logo_text			= !empty($logo_text) ? LAHB_Helper::translate_string($logo_text, $com_uniqid) : get_bloginfo( 'name' );
	$logo				= $logo ? wp_get_attachment_url( $logo ) : get_template_directory_uri() . '/assets/images/logo.png';
	$transparent_logo	= $transparent_logo ? wp_get_attachment_url( $transparent_logo ) : $logo;
	$extra_class		= $extra_class ? ' ' . $extra_class : '' ;

	if ( $once_run_flag ) :

		$dynamic_style = '';
		$dynamic_style .= lahb_styling_tab_output( $atts, 'logo', '.logo_' . esc_attr( $uniqid ) . ' img.lahb-logo' );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'transparent_logo', '.logo_' . esc_attr( $uniqid ) . ' img.lahb-logo-transparent' );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'text', '#lastudio-header-builder .logo_' . esc_attr( $uniqid ) . ' .la-site-name' );

		if ( $dynamic_style ) :
			LAHB_Helper::set_dynamic_styles( $dynamic_style );
		endif;

	endif;

	// render
	$out .= '<div data-element-id="'.esc_attr( $uniqid ).'" class="lahb-element lahb-logo' . esc_attr( $extra_class ) . ' logo_'.esc_attr( $uniqid ).'"'. ($once_run_flag ? ' itemscope itemtype="http://schema.org/Brand"' : '') .'><a href="' . esc_url( home_url( '/' ) ) . '" rel="home">';
		if ( ( ! empty( $logo ) || ! empty( $transparent_logo ) ) && $type == 'image' ) {

		    if(function_exists('jetpack_photon_url')){
                $src = jetpack_photon_url($logo);
                $src_transparency = jetpack_photon_url($transparent_logo);
            }
            else{
                $src = $logo;
                $src_transparency = $transparent_logo;
            }
			$out .= '
				<img class="lahb-logo logo--normal" src="' . esc_url( $src ) . '" alt="'. get_bloginfo('name') .'">
				<img class="lahb-logo logo--transparency" src="' . esc_url( $src_transparency ) . '" alt="'. get_bloginfo('name') .'">
			';
		}
		else {
			$out .= '<span class="la-site-name">' . $logo_text . '</span>';
		}
	$out .= '</a></div>';
	return $out;

}

LAHB_Helper::add_element( 'logo', 'lahb_logo', ['logo_text'] );
