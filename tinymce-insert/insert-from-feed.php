<?php

$dir = str_replace( '\\', '/', dirname(__FILE__) );
$pos = strpos( $dir, 'wp-content/plugins');
$dir = substr( $dir, 0, $pos );

function tp_templates_dropdown( $options = array(), $defaultTemplate ) {
?>
<select name='tp_templates_list' id='tp_templates_list' class=''>
<?php
	foreach ( $options as $name => $value ) :
?>
			<option <?php echo ( $name == $defaultTemplate ) ? "selected='selected'" : ""; ?> value="<?php echo $name; ?>" class="level-0"><?php echo $options[$name]['label']; ?></option>
<?php
	endforeach;
?>
</select>
<?php
}

require_once( $dir . 'wp-admin/admin.php' );

$title = __( 'Insert products from a feed', 'tppi' );

@header('Content-Type: ' . get_option('html_type') . '; charset=' . get_option('blog_charset'));

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" <?php do_action('admin_xml_ns'); ?> <?php language_attributes(); ?>>
<head>
<meta http-equiv="Content-Type" content="<?php bloginfo('html_type'); ?>; charset=<?php echo get_option('blog_charset'); ?>" />
<title><?php echo $title; ?></title>
<?php
wp_enqueue_style( 'global' );
wp_enqueue_style( 'wp-admin' );
wp_enqueue_style( 'colors' );
wp_enqueue_style( 'ie' );
wp_enqueue_style( 'tp-feed-style' );
wp_enqueue_script( 'tp-tinymce-insert-script' );
?>
<script type="text/javascript">
//<![CDATA[
addLoadEvent = function(func){if(typeof jQuery!="undefined")jQuery(document).ready(func);else if(typeof wpOnload!='function'){wpOnload=func;}else{var oldonload=wpOnload;wpOnload=function(){oldonload();func();}}};
var userSettings = {'url':'<?php echo SITECOOKIEPATH; ?>','uid':'<?php if ( ! isset($current_user) ) $current_user = wp_get_current_user(); echo $current_user->ID; ?>','time':'<?php echo time(); ?>'};
var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
var tpBaseUrl = '<?php echo get_bloginfo('url').'/wp-content/plugins/'.dirname(dirname(plugin_basename(__FILE__))); ?>';
//]]>
</script>
<style type="text/css">
.container {
	margin: 1em;
}
.dotted {
	display:block;
	height:1px;
	border-top:1px dotted #c0c0c0;
	margin:5px 0;
	margin-top:10px;
	clear:both;
}
.tp_templates_list {
	float:left;
}
.tp_template_preview, .tp_templates_edit {
	float:right;
	margin:3px 2px 0 2px;
}
</style>
<?php
do_action('admin_print_styles');
do_action('admin_print_scripts');
do_action('admin_head');
/*
?>
<script type="text/javascript" src="<?php echo WP_PLUGIN_URL . '/' . dirname(dirname(plugin_basename(__FILE__))); ?>"></script>
<?php //*/ ?>
</head>
<body<?php if ( isset($GLOBALS['body_id']) ) echo ' id="' . $GLOBALS['body_id'] . '"'; ?>>

<div class="container">

<h3><?php echo $title; ?></h3>

<?php
	require_once( '../api.php' );
	$tp = false;
	$errors = tp_verify_connection( $tp );
	
	if ( ! empty( $errors ) ) : ?>
	<div>
<?php foreach ( $errors as $e ) : ?>
		<p><?php _e( $e, 'tppi' ); ?></p>
<?php endforeach; ?>
		<p><?php printf( __('Please check <a href="%1$s" target="_parent">settings page</a>', 'tppi'), get_bloginfo( 'url' ). '/wp-admin/options-general.php?page=2performant-product-importer'); ?></p>
	</div>
<?php elseif ( ! $tp ) : ?>
	<div>
		<p><?php _e('Unable to connect to 2Performant network', 'tppi'); ?></p>
	</div>
<?php else : ?>
		<script type="text/javascript">
		//<![CDATA[
		var html = '';
		var wpurl = '<?php bloginfo('wpurl') ?>';
		
		//]]>
		</script>
		<div id="tp_insert_filter">
<?php

	if(isset($_REQUEST['tp_insert_filter_feed'])) {
		$selectFeed = $_REQUEST['tp_insert_filter_feed'];
	}
	
	$search = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
	$page = isset($_REQUEST['tp_insert_page']) ? $_REQUEST['tp_insert_page'] : 1;
	
	$templatesList = tp_get_option( 'templates', 'template_list' );
	$defaultTemplate = tp_get_option( 'templates', 'default_template' );
?>
			<form method="get">
			<label for="tp_insert_filter_feed">Feed</label>
			<?php
				echo tp_feed_dropdown( array(
					'name' => 'tp_insert_filter_feed',
					'id' => 'tp_insert_filter_feed',
					'value' => $selectFeed
				) );
			?>
			<label for="tp_insert_filter_search">Keywords</label>
			<input type="text" id="tp_insert_filter_search" name="s" value="<?php echo esc_attr( $search ); ?>" />
			<input type="submit" class="button-secondary" id="tp_insert_filter_submit" value="<?php _e('Search') ?>" />
			</form>
		</div>
		<input type="hidden" id="tp_ajax_nonce" value="<?php echo wp_create_nonce( 'tp_ajax_nonce' ); ?>" />
		<div id="tp_product_list_container"></div>
		
		<div id="tp-insert-toolbox" class="wrap">
			<div class="dotted"></div>
			<div id="tp-templates-all">
				<label for="tp_templates_list"><small>Select template</small></label>
				<?php
					tp_templates_dropdown( $templatesList, $defaultTemplate );
				?>	
				<a href="#" class="tp_templates_edit" title="Edit the templates"><img src="<?php bloginfo('wpurl') ?>/wp-content/plugins/2performant-product-importer/img/edit.png" border="0" /></a>
				<a href="#" class="tp_template_preview" title="Preview the product with template selected"><img src="<?php bloginfo('wpurl') ?>/wp-content/plugins/2performant-product-importer/img/preview.png" border="0" /></a>
				<div class="dotted"></div>
				<input id="done-insert" name="done-insert" type="checkbox" checked="checked"/>
				<label for="done-insert"><small>This is the last product I want to insert</small></label>
				
			</div>
	</div>
<?php endif; ?>

<?php /* ?>
<p class="savebutton tp-insert-product-submit wrap">
<input type="button" class="button" id="tp_insert_product_submit" name="save" value="<?php esc_attr_e( 'Insert', 'tppi' ); ?>" onclick="tp_insertProduct.insert()" />
</p>
<?php //*/ ?>

</div>
<?php
	do_action('admin_print_footer_scripts');
?>
<script type="text/javascript">if(typeof wpOnload=='function')wpOnload();</script>
</body>
</html>
<?php /* ?>
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php echo esc_html( $title ); ?></h2>

	<form enctype="multipart/form-data" method="post" action="#" class="type-form validate" id="tp-insert-product-form">
<?php
	require_once( '../api.php' );
	$tp = false;
	$errors = tp_verify_connection( $tp );
	
	if ( ! empty( $errors ) ) : ?>
	<div>
<?php foreach ( $errors as $e ) : ?>
		<p><?php _e( $e, 'tppi' ); ?></p>
<?php endforeach; ?>
		<p><?php _e('Please check <a href="options-general.php?page=2performant-product-importer">settings page</a>', 'tppi'); ?></p>
	</div>
<?php elseif ( ! $tp ) : ?>
	<div>
		<p><?php _e('Unable to connect to 2Performant network', 'tppi'); ?></p>
	</div>
<?php else : ?>
		<div id="tp_insert_filter">
			<label for="tp_insert_filter_feed">Feed</label>
			<?php
				echo tp_feed_dropdown( array(
					'name' => 'tp_insert_filter_feed',
					'id' => 'tp_insert_filter_feed',
				) );
			?>
			<label for="tp_insert_filter_search">Keywords</label>
			<input type="text" id="tp_insert_filter_search" name="s" />
			<input type="button" class="button-secondary" id="tp_insert_filter_submit" value="<?php _e('Search') ?>" />
		</div>
		<div id="tp_insert_products"></div>
<?php endif; ?>
	<p>
	<input type="submit" class="button savebutton hide-if-no-js" name="save" value="<?php esc_attr_e( 'Insert',  ); ?>" />
	</p>
	</form>
	</div>

<?php
	include(ABSPATH . 'wp-admin/admin-footer.php');

//}
//*/