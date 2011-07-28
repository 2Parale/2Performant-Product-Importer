<?php
/*
Plugin Name: 2Performant Product Importer
Plugin URI: http://blog.2parale.ro/wp-plugin-2performant-product-importer-en/
Description: Imports products from product feeds in 2Performant affiliate networks. It requires authentication as an affiliate in one of these networks. Products are imported as individual posts (or other custom post types, configurable) which can use several custom fields based on product info from the feeds. 
Version: 1.0a3
Author: 2Parale
Author URI: http://www.2parale.ro/
License: GPL2
*/

//ini_set('display_errors', 1);
//error_reporting(E_ALL);
//define('SCRIPT_DEBUG', true);

define('TPPI_VERSION', 'v1.0a3');

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
	wp_register_script( 'tp-jquery-product-list', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/jquery.productlist.js', array( 'jquery', 'jquery-infinitescroll' ), TPPI_VERSION, true );
	wp_register_script( 'tp-settings-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/settings.js', array( 'jquery' ), TPPI_VERSION, true );
	wp_register_script( 'tp-feed-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/feed.js', array( 'jquery', 'tp-jquery-product-list', 'wp-lists' ), TPPI_VERSION, true );
	wp_register_script( 'tp-edit-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/edit.js', array( 'jquery' ), TPPI_VERSION );
	wp_register_script( 'tp-listing-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/listing.js', array( 'jquery' ), TPPI_VERSION, true );
	wp_register_script( 'tp-toolbox-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/js/toolbox.js', array( 'jquery', 'jquery-ui-progressbar' ), TPPI_VERSION, true );
	wp_register_script( 'tp-tinymce-insert-script', '/wp-content/plugins/'.dirname(plugin_basename(__FILE__)).'/tinymce-insert/js/insert.js', array( 'jquery', 'tp-jquery-product-list' ), TPPI_VERSION );
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

function tp_the_product_field( $key, $id = false ) {
	echo tp_get_the_product_field( $key, $id );
}

include_once 'actions.php';
include_once 'settings.php';
include_once 'edit-page-boxes.php';
include_once 'add-from-feed.php';
include_once 'toolbox.php';
include_once 'listing.php';
include_once 'edit-page-button.php';
include_once 'upgrade-plugin.php';

// [tp_product id="id" feed="feed" template="template"]
function tp_product_shortcode( $atts ) {
	extract( shortcode_atts( array (
		'id' => false,
		'feed' => false,
		'template' => false,
	), $atts ) );
	
	if( ! ( is_numeric( $id ) && is_numeric( $feed ) ) )
		return false;
	
	require_once( 'api.php' );
	
	$html = '';
	$pinfo = tp_get_wrapper()->product_store_showitem( $feed, $id );
	
	if( empty( $pinfo ) || isset( $pinfo->error ) )
		return false;
	
	$tpl = tp_get_option( 'templates', 'template_list', false );
	$tpl = $tpl[$template];
	
	if( $tpl === false ) {
		ob_start();
?>
	<div class="tp-product-info">
<?php if( isset($pinfo->{'image_url'} ) ) : ?>
		<div class="tp-product-thumbnail">
			<a href="<?php echo esc_attr( $pinfo->aff_link ); ?>">
				<img src="<?php echo esc_attr( $pinfo->{'image_url'} ); ?>" />
			</a>
		</div>
<?php endif; ?>
		<div class="tp-product-meta">
			<span class="tp-product-brand"><?php echo esc_attr( $pinfo->brand ); ?></span>
			<span class="tp-product-title"><?php echo esc_attr( $pinfo->title ); ?></span>
			<span class="tp-product-price"><?php echo esc_attr( $pinfo->price ); ?></span>
		</div>
	</div>
<?php
		$html = ob_get_contents();
		
		ob_end_clean();
	} else {
		$template = $tpl['value'];
		
		$html = tp_strtopinfo( $template, $pinfo );
	}

	return $html;
}
add_shortcode( 'tp_product', 'tp_product_shortcode' );


/**
* Add Settings link to plugins page
*/
function tp_plugin_action_links($links, $file) {
	if ($file == "2performant-product-importer/plugin.php") {
		$settings_link = '<a href="' . get_bloginfo('wpurl') . '/wp-admin/options-general.php?page=2performant-product-importer">Settings</a>';
		array_unshift($links, $settings_link);
	}
	return $links;
}

/**
* Add the links to plugin action links filter.
*/
add_filter('plugin_action_links', 'tp_plugin_action_links', 10, 2);

/**
* Add caching check logic.
*/
add_filter('query_vars','tp_add_cache_trigger');
function tp_add_cache_trigger($vars) {
	$vars[] = 'tp_checkcache';
	$vars[] = 'tp_preview_template';
	return $vars;
}

add_action('template_redirect', 'tp_check_queryvars');
function tp_check_queryvars() {
	if(get_query_var('tp_checkcache') == 'true') {
		include_once 'api.php';
		$res = tp_cache_get( 'tp_testdata' );
		echo $res ? $res : 'no';
		exit;
	}
	
	if(get_query_var('tp_preview_template') == 'true') {
		include_once 'tinymce-insert/preview.php';
		exit;
	}
}

?>
