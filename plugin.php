<?php
/*
Plugin Name: 2Performant Product Importer
Plugin URI: http://blog.2parale.ro/wp-plugin-2performant-product-importer-en/
Description: Imports products from product feeds in 2Performant affiliate networks. It requires authentication as an affiliate in one of these networks. Products are imported as individual posts (or other custom post types, configurable) which can use several custom fields based on product info from the feeds. 
Version: 0.9
Author: 2Parale
Author URI: http://www.2parale.ro/
License: GPL2
*/

//ini_set('display_errors', 1);
//error_reporting(E_ALL);
//define('SCRIPT_DEBUG', true);

define('TPPI_VERSION', 'v0.9');

if ( is_admin() ) :

add_action( 'admin_menu', 'tp_plugin_menu' );
	
function tp_plugin_menu() {
	global $tp_plugin_settings_page;
	$tp_plugin_settings_page = add_options_page( '2Performant Product Importer Settings', '2Performant Product Importer', 'manage_options', '2performant-product-importer', 'tp_plugin_settings' );
	add_action( 'admin_init', 'tp_register_settings' );
	
	$pt = tp_get_post_type();
	$pt = ($pt == 'post') ? '' : '?post_type='.$pt;
	$feed_page = add_submenu_page( 'edit.php'.$pt, 'Add a product from a feed', 'Add from feed', 'edit_posts', 'tp_product_add_from_feed', 'tp_product_add_from_feed' );
	$toolbox_page = add_submenu_page( 'edit.php'.$pt, 'Product toolbox', 'Product toolbox', 'edit_posts', 'tp_product_toolbox', 'tp_product_toolbox' );
	add_action( 'admin_print_scripts-'.$tp_plugin_settings_page, 'tp_add_settings_script' );
	add_action( 'admin_print_styles-'.$feed_page, 'tp_add_feed_stylesheet' );
	add_action( 'admin_print_scripts-'.$feed_page, 'tp_add_feed_script' );
	add_action( 'admin_print_styles-'.$toolbox_page, 'tp_add_toolbox_stylesheet' );
	add_action( 'admin_print_scripts-'.$toolbox_page, 'tp_add_toolbox_script' );
	
	add_action('contextual_help', 'tp_plugin_settings_help', 10, 3 );
	
//	var_dump(plugin_basename(__FILE__));

	wp_register_style( 'tp-feed-style', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/css/2p.css' );
	wp_register_style( 'jquery-ui-redmond', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/css/redmond/jquery-ui-1.8.5.custom.css' );
	wp_register_script( 'jquery-infinitescroll', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/jquery.infinitescroll.js', array('jquery'), 'v1.5', true );
	wp_register_script( 'jquery-ui-progressbar', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/jquery-ui-1.8.5.progressbar.min.js', array( 'jquery' ), 'v1.8.5', true );
	wp_register_script( 'tp-settings-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/settings.js', array( 'jquery' ), TPPI_VERSION, true );
	wp_register_script( 'tp-feed-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/feed.js', array( 'jquery', 'jquery-infinitescroll', 'wp-lists' ), TPPI_VERSION, true );
	wp_register_script( 'tp-edit-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/edit.js', array( 'jquery' ), TPPI_VERSION, true );
	wp_register_script( 'tp-listing-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/listing.js', array( 'jquery' ), TPPI_VERSION, true );
	wp_register_script( 'tp-toolbox-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/toolbox.js', array( 'jquery', 'jquery-ui-progressbar' ), TPPI_VERSION, true );
}

function tp_add_settings_script() {
	wp_enqueue_script('tp-settings-script');
}

function tp_add_feed_stylesheet() {
//	var_dump('add style');
	wp_enqueue_style('tp-feed-style');
}

function tp_add_feed_script() {
	wp_enqueue_script('tp-feed-script');
}

function tp_add_toolbox_stylesheet() {
	wp_enqueue_style('jquery-ui-redmond');
}

function tp_add_toolbox_script() {
	wp_enqueue_script('tp-toolbox-script');
}

function tp_get_post_type() {
	$pt = get_option('tp_options_add_feed', array('post_type' => 'post'));
	$pt = $pt['post_type'];
	
	return $pt;
}

endif;

function tp_get_the_product_field( $key, $id = false ) {
	global $post;
	if ( $id === false )
		$id = $post->ID;
	$t = get_post_meta( $id, 'tp_product_info', true );
	
	return isset( $t[$key] ) ? $t[$key] : '';
}

function tp_the_product_field( $key ) {
	echo tp_get_the_product_field( $key );
}

include_once 'actions.php';
include_once 'settings.php';
include_once 'edit-page-boxes.php';
include_once 'add-from-feed.php';
include_once 'toolbox.php';
include_once 'listing.php';

?>