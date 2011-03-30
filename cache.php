<?php

function tp_using_cache() {
	$res = get_option('tp_options_cache', array('enabled' => true));
	$res = $res['enabled'];
	
	return $res;
}

function tp_cache_get( $key ) {
	global $wpdb, $wp_object_cache;
	return wp_cache_get( $key, md5($wpdb->dbhost.$wpdb->dbname.$wpdb->prefix) );
}

function tp_cache_set( $key, $value, $interval = 0 ) {
	global $wpdb;
	return wp_cache_set( $key, $value, md5($wpdb->dbhost.$wpdb->dbname.$wpdb->prefix), $interval );
}

function tp_cache_delete( $key ) {
	global $wpdb;
	return wp_cache_delete( $key, md5($wpdb->dbhost.$wpdb->dbname.$wpdb->prefix) );
}

function tp_verify_cache() {
	$errors = array();
	$data = 'testdata1234';
	
	tp_cache_set( 'tp_testdata', $data );
	$url = get_site_url() . '?tp_checkcache=true';
	
	$ok = false;
	$req = new HTTP_Request2();
	$req->setMethod( HTTP_Request2::METHOD_GET );
	$req->setUrl( $url );
	$req->setHeader("Accept", "application/xml");
	$req->setHeader("Content-Type", "application/xml");
	$req->setHeader("Accept-encoding", "identity");
	$response = $req->send();
	
	if(!PEAR::isError($response) && $response->getStatus() == 200) {
		if( $response->getBody() == $data )
			$ok = true;
	}
	
	if( !$ok ) {
		$errors[] = (is_admin() ? 'Admin-side cache not working!' : 'Cache not working!') . " Maybe you haven't yet installed an object caching plugin like <a href='http://wordpress.org/extend/plugins/w3-total-cache/' target='_blank'>W3 Total Cache</a>?";
	} else {
		tp_cache_delete( 'tp_testdata' );
	}
	
	return $errors;
}


class CachedAPI {
	private static $instance = null;
	private $intervals = array(
		// 'func_name' => interval (seconds)
		// TODO: let user set each function cache expiration time
	);
	private $keys = array();
	
	public function getInstance() {
		if( !isset( self::$instance ) ) {
			$err = tp_verify_connection( self::$instance );
			if( !empty($err) ) {
				self::$instance = null;
				return false;
			}
		}
		
		return self::$instance;
	}
	
	public function __construct() {
		$this->getInstance();
	}
	
	public function __call( $name, $args) {
		$key = $name . serialize( $args );
		$interval = isset( $this->intervals[$name] ) ? $this->intervals[$name] : false;
		
		$res = tp_cache_get( $key );
		if( !$res ) {
			// Cache miss
			$res = call_user_func_array( array( self::$instance, $name ), $args );
			tp_cache_set($key, $res, $interval);
		}
		
		$this->keys[$key] = $res;
		
		return $res;
	}
	
	public function trace() {
		print_r($this->keys);
	}
}


?>
