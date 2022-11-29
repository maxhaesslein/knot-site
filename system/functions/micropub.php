<?php

if( ! $eigenheim ) exit;

// micropub spec: https://www.w3.org/TR/micropub/

function micropub_get_endpoint( $complete_path = false ){

	$endpoint = 'micropub'; // TODO: revisit this in the future; if this uses '/' we need to fix routing.php as well

	if( ! $complete_path ) {
		return $endpoint;
	}

	return url($endpoint);
}

function micropub_check_request(){

	if( ! empty($_POST) ) {
		micropub_handle_post_request();
		return;
	} elseif( ! empty($_GET) ) {
		micropub_handle_get_request();
		return;
	}

	$json = json_decode(file_get_contents('php://input'), true);
	if( $json ) {
		micropub_handle_json_request( $json );
		return;
	}

}

function micropub_handle_get_request(){

	if( empty($_GET['q']) ) return;

	if( $_GET['q'] == 'config' ) {

		$categories = get_categories();

		$config = array(
			// 'media-endpoint' => '', // TODO: add media endpoint for multiple images
			'categories' => $categories
		);

		header('Content-Type: application/json; charset=utf-8');
		echo json_encode( $config );
		exit;
	}

}

function micropub_handle_json_request( $json ) {

	micropub_check_authorization_bearer(); // this will exit with a error message if authorization is not allowed

	$data = array();

	$data['h'] = str_replace('h-', '', $json['type'][0]);
	foreach( $json['properties'] as $name => $property ) {

		if( is_array($property) ) $property = array_values($property);

		if( is_array($property) && count($property) == 1 ) $property = $property[0];

		// special case: content html
		if( $name == 'content' && ! empty($property['html']) ) $property = $property['html'];

		// special case: slug
		if( $name == 'mp-slug' ) $name = 'slug';

		$data[$name] = $property;
	}

	micropub_create_post( $data );

}

function micropub_handle_post_request() {

	micropub_check_authorization_bearer(); // this will exit with a error message if authorization is not allowed

	$data = $_POST;

	micropub_create_post( $data );

}

function micropub_create_post( $data ){

	$skip_fields = array( 'access_token', 'action' );
	foreach( $skip_fields as $key ) {
		if( ! array_key_exists( $key, $data) ) continue;
		unset($data[$key]);
	}

	// TODO: sanitize input. never trust anything we receive here. currently we just dump everything into a text file.

	$data['timestamp'] = time();
	$data['date'] = date('c', $data['timestamp']);

	if( ! empty($data['category']) ) {
		// we assume for now, that 'category' is either an array or a comma separated string
		if( ! is_array($data['category']) ) {
			$data['category'] = explode( ',', $data['category'] );
			$data['category'] = array_map( 'trim', $data['category'] );
		}
		$data['category'] = json_encode($data['category']);
	}

	if( empty($data['post-status']) ) $data['post-status'] = 'published'; // possible values: 'published' or 'draft'
	if( $data['post-status'] == 'publish' ) $data['post-status'] = 'published';

	$photo = false;
	if( ! empty($_FILES['photo']) ) $photo = $_FILES['photo'];

	$permalink = database_create_post( $data, $photo );
	// if something went wrong, database_create_post will exit - TODO: maybe return an error message and http status code from database_create_post() and exit here
	
	// success !
	// Set headers, return location
	header( "HTTP/1.1 201 Created" );
	header( "Location: ".$permalink );
	exit;

}

function micropub_check_authorization_bearer() {

	global $eigenheim;

	$headers = apache_request_headers();

	$token = $headers['Authorization'];

	$headers = array(
		"Content-Type: application/x-www-form-urlencoded",
		"Authorization: $token"
	);
	$response = request_post( 'https://tokens.indieauth.com/token', $headers );

	if( empty($response) ){
		header( "HTTP/1.1 401 Unauthorized" );
		exit;
	}

	// Check for scope=post or scope=create
	// Check for me=basedomain
	$me = $response['me'];
	$iss = $response['issued_by'];
	$client = $response['client_id'];
	$scope = $response['scope'];
	
	if( trailing_slash_it($me) != trailing_slash_it($eigenheim->baseurl) ){
		header( "HTTP/1.1 403 Forbidden" );
		exit;
	}

	$scopes = explode( ' ', $scope );
	$scope_found = false;
	foreach( array('post', 'create') as $possible_scope ){
		if( in_array($possible_scope, $scopes) ) {
			$scope_found = true;
			break;
		}
	}
	if( ! $scope_found ){
		header( "HTTP/1.1 403 Forbidden" );
		exit;
	}

}
