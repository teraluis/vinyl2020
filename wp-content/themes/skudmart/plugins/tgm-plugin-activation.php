<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

add_action( 'tgmpa_register', 'skudmart_register_required_plugins' );

if(!function_exists('skudmart_register_required_plugins')){

	function skudmart_register_required_plugins() {

        $initial_required = array(
            'lastudio' => array(
                'source'    => 'https://la-studioweb.com/file-resouces/skudmart/plugins/lastudio/2.0.1/lastudio.zip',
                'version'   => '2.0.1'
            ),
            'lastudio-header-builders' => array(
                'source'    => 'https://la-studioweb.com/file-resouces/skudmart/plugins/lastudio-header-builders/1.1.1/lastudio-header-builders.zip',
                'version'   => '1.1.1'
            ),
            'revslider' => array(
                'source'    => 'https://la-studioweb.com/file-resouces/shared/plugins/revslider/6.1.3/revslider.zip',
                'version'   => '6.1.3'
            ),
            'skudmart-demo-data' => array(
                'source'    => 'https://la-studioweb.com/file-resouces/skudmart/plugins/skudmart-demo-data/1.0.0/skudmart-demo-data.zip',
                'version'   => '1.0.0'
            )
        );

        $from_option = get_option('skudmart_required_plugins_list', $initial_required);

		$plugins = array();

		$plugins[] = array(
			'name'					=> esc_html_x('LA-Studio Core', 'admin-view', 'skudmart'),
			'slug'					=> 'lastudio',
            'source'				=> isset($from_option['lastudio'], $from_option['lastudio']['source']) ? $from_option['lastudio']['source'] : $initial_required['lastudio']['source'],
            'required'				=> true,
            'version'				=> isset($from_option['lastudio'], $from_option['lastudio']['version']) ? $from_option['lastudio']['version'] : $initial_required['lastudio']['version']
		);

		$plugins[] = array(
			'name'					=> esc_html_x('LA-Studio Header Builder', 'admin-view', 'skudmart'),
			'slug'					=> 'lastudio-header-builders',
            'source'				=> isset($from_option['lastudio-header-builders'], $from_option['lastudio-header-builders']['source']) ? $from_option['lastudio-header-builders']['source'] : $initial_required['lastudio-header-builders']['source'],
            'required'				=> true,
            'version'				=> isset($from_option['lastudio-header-builders'], $from_option['lastudio-header-builders']['version']) ? $from_option['lastudio-header-builders']['version'] : $initial_required['lastudio-header-builders']['version']
		);

        $plugins[] = array(
            'name' 					=> esc_html_x('Elementor', 'admin-view', 'skudmart'),
            'slug' 					=> 'elementor',
            'required' 				=> true,
            'version'				=> '2.7.3'
        );

		$plugins[] = array(
			'name'     				=> esc_html_x('WooCommerce', 'admin-view', 'skudmart'),
			'slug'     				=> 'woocommerce',
			'version'				=> '3.7.0',
			'required' 				=> false
		);
        
        $plugins[] = array(
			'name'     				=> esc_html_x('Skudmart Package Demo Data', 'admin-view', 'skudmart'),
			'slug'					=> 'skudmart-demo-data',
            'source'				=> isset($from_option['skudmart-demo-data'], $from_option['skudmart-demo-data']['source']) ? $from_option['skudmart-demo-data']['source'] : $initial_required['skudmart-demo-data']['source'],
            'required'				=> false,
            'version'				=> isset($from_option['skudmart-demo-data'], $from_option['skudmart-demo-data']['version']) ? $from_option['skudmart-demo-data']['version'] : $initial_required['skudmart-demo-data']['version']
		);

		$plugins[] = array(
			'name'     				=> esc_html_x('Envato Market', 'admin-view', 'skudmart'),
			'slug'     				=> 'envato-market',
			'source'   				=> 'https://envato.github.io/wp-envato-market/dist/envato-market.zip',
			'required' 				=> false,
			'version' 				=> '2.0.2'
		);

		$plugins[] = array(
			'name' 					=> esc_html_x('Contact Form 7', 'admin-view', 'skudmart'),
			'slug' 					=> 'contact-form-7',
			'required' 				=> false
		);

		$plugins[] = array(
			'name'					=> esc_html_x('Slider Revolution', 'admin-view', 'skudmart'),
			'slug'					=> 'revslider',
            'source'				=> isset($from_option['revslider'], $from_option['revslider']['source']) ? $from_option['revslider']['source'] : $initial_required['revslider']['source'],
            'required'				=> false,
            'version'				=> isset($from_option['revslider'], $from_option['revslider']['version']) ? $from_option['revslider']['version'] : $initial_required['revslider']['version']
		);


		$config = array(
			'id'           				=> 'skudmart',
			'default_path' 				=> '',
			'menu'         				=> 'tgmpa-install-plugins',
			'has_notices'  				=> true,
			'dismissable'  				=> true,
			'dismiss_msg'  				=> '',
			'is_automatic' 				=> false,
			'message'      				=> ''
		);

		tgmpa( $plugins, $config );

	}

}
