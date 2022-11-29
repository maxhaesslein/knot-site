<?php

if( ! $eigenheim ) exit;

function get_config( $option = false, $fallback = false ){

	$config = load_config_from_file();

	// TODO: build something better for default config options
	if( ! isset( $config['posts_per_page']) ) {
		$config['posts_per_page'] = 5; // default value
	}

	if( $option ) {
		if( ! array_key_exists( $option, $config ) ) {
			return $fallback;
		}
		return $config[$option];
	}

	return $config;
}


function load_config_from_file(){

	global $eigenheim;

	$config_file = $eigenheim->abspath.'config.php';

	if( ! file_exists($config_file) ) {
		// TODO: add debug option to show or hide this message
		echo '<p><strong>no config file found</strong></p>';
		exit;
	}

	$config = include( $config_file );

	return $config;
}
