<?php
function lahb_text( $atts, $uniqid, $once_run_flag = true ) {

    if ( !$once_run_flag ){
        return '<div data-element-id="'.esc_attr( $uniqid ).'" class="lahb-element lahb-element--placeholder"></div>';
    }

    $com_uniqid = 'text' . $uniqid;

	extract( LAHB_Helper::component_atts( array(
		'is_shortcode'	=> 'false',
		'text'			=> 'This is a text field',
		'link'			=> '',
		'link_new_tab'	=> 'false',
		'extra_class'	=> '',
        'icon'          => ''
	), $atts ));

	$out = '';

	$text			= $text ? $text : '' ;
	$link_new_tab	= $link_new_tab == 'true' ? 'target="_blank"' : '' ;

    $icon	 = ! empty( $icon ) && $icon != 'none' ? '<i class="' . lahb_rename_icon($icon) . '" ></i>' : '' ;

	// styles
	if ( $once_run_flag ) :
		$dynamic_style = '';
        $dynamic_style .= lahb_styling_tab_output( $atts, 'text', '#lastudio-header-builder .el__text_' . esc_attr( $uniqid ) . ' span' );
        $dynamic_style .= lahb_styling_tab_output( $atts, 'icon', '#lastudio-header-builder .el__text_' . esc_attr( $uniqid ) .' i','#lastudio-header-builder .el__text_' . esc_attr( $uniqid ) .':hover i'  );
        $dynamic_style .= lahb_styling_tab_output( $atts, 'background', '#lastudio-header-builder .el__text_' . esc_attr( $uniqid ) );
        $dynamic_style .= lahb_styling_tab_output( $atts, 'box', '#lastudio-header-builder .el__text_' . esc_attr( $uniqid ) );

        if ( $dynamic_style ) :
            LAHB_Helper::set_dynamic_styles( $dynamic_style );
        endif;
	endif;

	// extra class
	$extra_class = $extra_class ? ' ' . $extra_class : '' ;

	// render
	$out .= '<div data-element-id="'.esc_attr( $uniqid ).'" class="lahb-element lahb-element-wrap lahb-text-wrap lahb-text' . esc_attr( $extra_class ) . ' el__text_'.esc_attr( $uniqid ).'" id="lahb-text-' . esc_attr( $uniqid ) . '">';
		$text = str_replace('\"','',$text);
        $text = LAHB_Helper::translate_string($text, $com_uniqid);
		if ( $is_shortcode == 'true' ) {
		    ob_start();
			echo do_shortcode($text);
            $out .= ob_get_clean();
		}
		else {
			if ( ! empty ( $link ) ) {
				$out .= '<a href="' . esc_attr( LAHB_Helper::translate_string($link, $com_uniqid) ) . '" '. $link_new_tab .'>';
			}
            $out .= $icon . '<span>'. $text .'</span>';
			if ( ! empty ( $link ) ) {
				$out .= '</a>';
			}
		}
	$out .= '</div>';

	return $out;
}

LAHB_Helper::add_element( 'text', 'lahb_text' , ['text', 'link']);
