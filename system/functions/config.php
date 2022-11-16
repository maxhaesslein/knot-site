<?php

if( ! defined( 'EH_ABSPATH' ) ) exit;

function get_config( $option = false ){

	$config = load_config_from_file();

	if( $option ) {
		if( ! array_key_exists( $option, $config ) ) return false;
		return $config[$option];
	}

	return $config;
}


function load_config_from_file(){

	$config_file = EH_ABSPATH.'site/config.php';

	if( ! file_exists($config_file) ) {
		// TODO: add debug option to show or hide this message
		echo '<p><strong>no config file found</strong></p>';
		die();
	}

	$config = include( $config_file );

	return $config;
}
