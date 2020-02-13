<?php

function lahb_hamburger_menu( $atts, $uniqid, $once_run_flag = true ) {

    if ( !$once_run_flag ){
        return '<div data-element-id="'.esc_attr( $uniqid ).'" class="lahb-element lahb-element--placeholder"></div>';
    }

    $com_uniqid = 'hamburger-menu' . $uniqid;

	extract( LAHB_Helper::component_atts( array(
		'menu'				=> '',
		'hamburger_type'	=> 'toggle',
		'hamburger_icon'	=> 'lastudioicon-menu-4-1',
		'hamburger_text'	=> '',
		'hm_style'			=> 'light',
		'toggle_from'		=> 'right',
		'image_logo'		=> '',
		'socials'			=> 'true',
		'search'			=> 'true',
		'placeholder'		=> 'Search ...',
		'content'			=> 'false',
		'text_content'		=> '',
		'copyright'			=> 'Copyright',
		'extra_class'		=> '',
		'extra_class_panel' => '',
	), $atts ));

	$out = $menu_out = '';
    $dark_wrap       = ( $hm_style == 'dark' ) ? 'dark-wrap' : 'light-wrap' ;
	$menu_style		 = ( $hm_style == 'dark' ) ? 'hm-dark' : '' ;
	$hamburger_type  = $hamburger_type ? $hamburger_type : 'toggle' ;
    $menu_list_style = ( $hamburger_type == 'toggle' ) ? 'toggle-menu' : 'full-menu';
	$image_logo		 = $image_logo ? wp_get_attachment_url( $image_logo ) : '' ;

	if(!empty($image_logo) && function_exists('jetpack_photon_url')){
        $image_logo = jetpack_photon_url($image_logo);
    }

	if($hamburger_icon == '4line' || $hamburger_icon == '3line'){
	    $hamburger_icon = 'lastudioicon-menu-4-1';
    }

    $hamburger_icon	 = ! empty( $hamburger_icon ) ? '<i class="' . lahb_rename_icon($hamburger_icon) . '" ></i>' : '' ;

	if(!empty($hamburger_text)){
        $hamburger_icon	 .= '<span>'.LAHB_Helper::translate_string($hamburger_text, $com_uniqid).'</span>';
    }

	if ( $hamburger_type == 'toggle' ){
		$toggle_from = ( $toggle_from == 'right' ) ? 'toggle-right' : 'toggle-left';
	} else {
		$toggle_from = '';
	}

    if ( ! empty( $menu )  ) {
        $menu = LAHB_Helper::translate_string($menu, $com_uniqid);
        if(is_nav_menu($menu)){
            $menu_out = wp_nav_menu( array(
                'menu'          => $menu,
                'container'     => 'nav',
                'container_class' => 'hamburger-main',
                'menu_class'    => 'hamburger-nav ' . $menu_list_style,
                'depth'         => '5',
                'fallback_cb'   => array( 'LAHB_Nav_Walker', 'fallback' ),
                'items_wrap'    => '<ul id="%1$s" class="%2$s">%3$s</ul>',
                'echo'          => false,
                'walker'		=> new LAHB_Nav_Walker
            ) );
        }
    }
    else{
        $menu_out = '<div class="lahb-element">span>' . esc_html__( 'Your menu is empty or not selected! ', 'lastudio-header-builder' ) . '<a href="https://codex.wordpress.org/Appearance_Menus_Screen" class="sf-with-ul hcolorf" target="_blank">' . esc_html__( 'How to config a menu', 'lastudio-header-builder' ) . '</a></span></div>';
    }

	// styles
	if ( $once_run_flag ) :

        $css_el_icon_box = '.lahb-element.hbgm_' . esc_attr( $uniqid ) .' > a';
        $css_el_hm_box = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid );
        $css_el_hm_menu_box = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-nav';
        $css_el_hm_menu_item = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-nav > li > a';
        $css_el_hm_menu_item_hover = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-nav > li:hover > a';
        $css_el_hm_menu_item_current = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-nav > li.current > a';
        $css_el_hm_menu_item_current_hover = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-nav > li.current:hover > a';
        $css_el_hm_menu_item_shape = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-nav > li > a .hamburger-nav-icon';
        $css_el_hm_menu_item_shape_hover = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-nav > li:hover > a .hamburger-nav-icon';
        $css_el_hm_menu_sub_item = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-nav li li a';
        $css_el_hm_menu_sub_item_hover = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-nav li li:hover > a';
        $css_el_hm_element_box = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-elements';
        $css_el_hm_content = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .lahmb-text-content';
        $css_el_hm_content_hover = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .lahmb-text-content:hover';
        $css_el_hm_social = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-social-icons a';
        $css_el_hm_social_hover = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-social-icons a:hover';
        $css_el_hm_logo = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-logo-image-wrap';
        $css_el_hm_copyright = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-copyright';
        $css_el_hm_copyright_hover = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' .hamburger-copyright';
        $css_el_hm_search_box = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' form.search-form';
        $css_el_hm_search_input = '.lahb-body .la-hamburger-wrap-' . esc_attr( $uniqid ) . ' form.search-form .search-field';


		$dynamic_style = '';
        $dynamic_style .= lahb_styling_tab_output( $atts,'hamburger_icon_color','.hbgm_' . esc_attr($uniqid) . ' .hamburger-op-icon', '.hbgm_' . esc_attr($uniqid) . ' .hamburger-op-icon:hover');
        $dynamic_style .= lahb_styling_tab_output( $atts,'hamburger_icon_text','.hbgm_' . esc_attr($uniqid) . ' .hamburger-op-icon span');
		$dynamic_style .= lahb_styling_tab_output( $atts, 'hamburger_icon_box', $css_el_icon_box );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'hamburger_box', $css_el_hm_box );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'menu_box', $css_el_hm_menu_box );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'menu_item', $css_el_hm_menu_item,$css_el_hm_menu_item_hover );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'current_menu_item', $css_el_hm_menu_item_current,$css_el_hm_menu_item_current_hover );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'current_item_shape', $css_el_hm_menu_item_shape,$css_el_hm_menu_item_shape_hover );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'submenu_item', $css_el_hm_menu_sub_item,$css_el_hm_menu_sub_item_hover );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'elements_box', $css_el_hm_element_box );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'content', $css_el_hm_content,$css_el_hm_content_hover );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'socials', $css_el_hm_social,$css_el_hm_social_hover );

		$dynamic_style .= lahb_styling_tab_output( $atts, 'copyright', $css_el_hm_copyright, $css_el_hm_copyright_hover );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'search_input', $css_el_hm_search_input );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'search_box', $css_el_hm_search_box );
		$dynamic_style .= lahb_styling_tab_output( $atts, 'logo_box', $css_el_hm_logo );

        if ( $dynamic_style ) :
            LAHB_Helper::set_dynamic_styles( $dynamic_style );
        endif;

	endif;

	// extra class
	$extra_class = $extra_class ? ' ' . $extra_class : '';

	// render
    $out .= '<div data-element-id="'.esc_attr( $uniqid ).'" class="lahb-element lahb-icon-wrap lahb-hamburger-menu ' . esc_attr( $extra_class ) . ' hamburger-type-' . $hamburger_type . ' ' . $dark_wrap . ' hbgm_'.esc_attr( $uniqid ).'"><a href="#" data-id="'.esc_attr( $uniqid ).'" class="js-hamburger_trigger lahb-icon-element close-button hcolorf hamburger-op-icon">'.$hamburger_icon.'</a>';

	if ( $once_run_flag ) {
        if ( $hamburger_type == 'full' ) {
            $out .= '<div class="lahb-element--dontcopy la-hamburger-wrap-' . esc_attr($uniqid) . ' la-hamburger-wrap la-hamuburger-bg ' . esc_attr($menu_style) . ' ' . esc_attr($extra_class_panel) . '">
			<div class="hamburger-full-wrap">
			    <a href="javascript:;" class="btn-close-hamburger-menu-full"><i class="lastudioicon-e-remove"></i></a>
				<div class="lahb-hamburger-top">';
            $out .= $menu_out;
            $out .= '
				</div>
				<div class="lahb-hamburger-bottom hamburger-elements">';
            if (!empty($image_logo)) {
                $out .= '<div class="hamburger-logo-image-wrap"><img class="hamburger-logo-image" src="' . esc_url($image_logo) . '" alt="' . get_bloginfo('name') . '"></div>';
            }
            if ($content == 'true' && !empty($text_content)) {
                ob_start();
                echo '<div class="lahmb-text-content">' . LAHB_Helper::remove_js_autop(LAHB_Helper::translate_string($text_content, $com_uniqid)) . '</div>';
                $out .= ob_get_clean();
            }
            if ($socials == 'true') {
                ob_start();
                echo '<div class="hamburger-social-icons">';
                do_action('lastudio/header-builder/render-social');
                echo '</div>';
                $out .= ob_get_clean();
            }
            $out .= '</div></div></div>';
        }
        elseif ($hamburger_type == 'toggle') {
            $out .= '<div class="lahb-element--dontcopy hamburger-menu-wrap la-hamuburger-bg hamburger-menu-content ' . esc_attr($menu_style) . ' la-hamburger-wrap-' . esc_attr($uniqid) . ' ' . $toggle_from . ' ' . esc_attr($extra_class_panel) . '">
			    <a href="javascript:;" class="btn-close-hamburger-menu"><i class="lastudioicon-e-remove"></i></a>
				<div class="hamburger-menu-main">
					<div class="lahb-hamburger-top">';
            if (!empty($image_logo)) {
                $out .= '<div class="hamburger-logo-image-wrap"><img class="hamburger-logo-image" src="' . esc_url($image_logo) . '" alt="' . get_bloginfo('name') . '"></div>';
            }

            $out .= $menu_out;

            if ($search == 'true') :
                $out .= '<form role="search" class="search-form" action="' . esc_url(home_url('/')) . '" method="get"><input name="s" type="text" class="search-field hamburger-search-text-box" placeholder="' . ( !empty($placeholder) ? LAHB_Helper::translate_string($placeholder, $com_uniqid) : '' ) . '"><button class="search-button" type="submit"><i class="lastudioicon-zoom-1"></i></button></form>';
            endif;

            $out .= '</div>';

            $out .= '<div class="lahb-hamburger-bottom hamburger-elements">';
            if ($content == 'true' && !empty($text_content)) {
                $out .= '<div class="lahmb-text-content">' . LAHB_Helper::remove_js_autop(LAHB_Helper::translate_string($text_content, $com_uniqid)) . '</div>';
            }

            if ($socials == 'true') {
                ob_start(); ?>
                <div class="hamburger-social-icons"><?php do_action('lastudio/header-builder/render-social'); ?></div>
                <?php
                $out .= ob_get_contents();
                ob_end_clean();
            }

            if (!empty($copyright)) {
                $out .= '<div class="lahb-hamburger-bottom hamburger-copyright">' . LAHB_Helper::translate_string($copyright, $com_uniqid) . '</div>';
            }
            $out .= '</div>'; // Close .hamburger-elements
            $out .= '</div>'; // Close .hamburger-menu-main

            $out .= '</div>';
        }
    }

	$out .= '</div>';

	return $out;
}

LAHB_Helper::add_element( 'hamburger-menu', 'lahb_hamburger_menu', ['menu', 'hamburger_text','placeholder', 'text_content', 'copyright'] );
