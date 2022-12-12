<?php

class Theme {

	public $folder_path;
	public $path;
	public $url;
	public $config;

	public $stylesheets = array();


	function __construct( $eigenheim ) {

		$theme_name = $eigenheim->config->get('theme');

		if( ! file_exists( $eigenheim->abspath.'theme/'.$theme_name.'/config.php') ) {
			$theme_name = 'default';
		}

		$file_path = 'theme/'.$theme_name.'/config.php';
		$this->config = $this->load_theme_config_from_file( $file_path );

		$this->folder_name = $theme_name;
		$this->path = 'theme/'.$theme_name.'/';
		$this->url = url('theme/'.$theme_name.'/');

		$this->add_stylesheet( 'css/eigenheim.css', 'global' );

	}


	function load(){
		global $eigenheim;
		$eigenheim->include( $this->path.'functions.php' );
	}


	function load_theme_config_from_file( $file_path ) {

		global $eigenheim;

		if( ! file_exists($file_path) ) {
			$eigenheim->debug( 'no config file found' );
			exit;
		}

		$config = include( $file_path );

		return $config;
	}


	function get( $key = false ) {

		if( ! $key ) return $this->config;

		if( array_key_exists($key, $this->config) ) return $this->config[$key];
		
		return false;
	}


	function add_stylesheet( $path, $type = 'theme' ) {

		global $eigenheim;

		$global_path = $eigenheim->abspath.'system/site/assets/';
		$global_url = $eigenheim->baseurl.'/system/site/assets/';

		if( $type == 'theme' && file_exists($this->path.$path) ) {
			$type = 'theme';
			$url = $this->url.$path;
		} elseif( $type == 'global' && file_exists($global_path.$path) ) {
			$type = 'global';
			$url = $global_url.$path;
		} else {
			return;
		}

		$this->stylesheets[$path] = [
			'path' => $path,
			'url' => $url,
			'type' => $type
		];
	}


	function remove_stylesheet( $path ) {
		if( ! array_key_exists($path, $this->stylesheets) ) return;

		unset($this->stylesheets[$path]);
	}


	function print_stylesheets() {

		global $eigenheim;

		foreach( $this->stylesheets as $stylesheet ) {
			if( $stylesheet['type'] == 'global' ) {
				$version = $eigenheim->version();
			} else {
				$version = $this->get('version');
			}
		?>
	<link rel="stylesheet" href="<?= $stylesheet['url'] ?>?v=<?= $version ?>">
<?php
		}

	}


	function snippet( $path, $args = array(), $return = false ) {
		
		global $eigenheim;

		$snippet_path = 'snippets/'.$path.'.php';

		if( file_exists($this->path.$snippet_path) ) {
			$include_path = $this->path.$snippet_path;
		} else {
			$include_path = 'system/site/'.$snippet_path;
		}

		if( ! file_exists( $eigenheim->abspath.$include_path) ) return;

		ob_start();

		$eigenheim->include( $include_path, $args );

		$snippet = ob_get_contents();
		ob_end_clean();

		if( $return === true ) {
			return $snippet;
		}

		echo $snippet;

	}


}
