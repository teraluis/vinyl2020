<?php
if(!defined("ABSPATH")) exit;

class PHPInfoer
{
	public function info()
	{
		ob_start();
			phpinfo(INFO_ALL & ~INFO_LICENSE & ~INFO_CREDITS);
		$info = ob_get_clean();

		/**
		 * Extract contents within <body> and </body> tags only
		 */
		$info = preg_replace("/^.*?\<body\>/is", "", $info);
		$info = preg_replace("/<\/body\>.*?$/is", "", $info);

		echo file_get_contents(dirname(__FILE__)."/header.html");
		echo $info;
		echo file_get_contents(dirname(__FILE__)."/footer.html");
	}
	
	public function admin_menus()
	{
		add_menu_page("PHP Info", "PHP Info (WP)", "manage_options", "PHPInfoer", array($this, "info"), "dashicons-welcome-widgets-menus", 70);
	}
	
	public function enqueue()
	{
		wp_enqueue_style("phpinfo.wp", plugins_url("/phpinfo.wp/phpinfo.css"), array(), false, "all");
	}

	/**
	 * Add project source code link
	 */
	public function row_meta($links=array(), $file="")
	{
		if(strpos($file, "phpinfo.wp/phpinfo.php")!==false)
		{
			$new_links = array(
				"github" => '<a href="https://github.com/anytizer/phpinfo.wp" target="_blank">Project Source</a>',
			);
			
			$links = array_merge($links, $new_links);
		}
		
		return $links;
	}
}
