<?php

if ( is_admin() ) :


// init process for button control
add_action('init', 'tp_edit_addbuttons');

function tp_edit_addbuttons() {
	// Don't bother doing this stuff if the current user lacks permissions
	if ( ! current_user_can( 'edit_posts' ) && ! current_user_can( 'edit_pages' ) )
		return;
	
	// Add only in Rich Editor mode
	if ( get_user_option( 'rich_editing' ) == 'true' ) {
		add_filter( 'mce_external_plugins', 'tp_add_tinymce_plugin' );
		add_filter( 'mce_buttons', 'tp_register_insert_product_button' );
	}
	
	wp_enqueue_script( 'tp-insert-script' );
}
 
function tp_register_insert_product_button( $buttons ) {
	array_push( $buttons, 'separator', 'tp_insert_product' );
	return $buttons;
}
 
// Load the TinyMCE plugin : editor_plugin.js (wp2.5)
function tp_add_tinymce_plugin( $plugin_array ) {
	$plugin_array['tp_insert_product'] = WP_PLUGIN_URL . '/' . str_replace( basename(__FILE__), '', plugin_basename(__FILE__) ) . 'tinymce-insert/editor_plugin.js';
	return $plugin_array;
}

//*/

add_action ( 'wp_ajax_tp_insertproduct_container', 'tp_ajax_insertproduct_container' );
function tp_ajax_insertproduct_container() {
	check_ajax_referer( 'tp_ajax_nonce' );
	
	$products = array();
	$campaignID = 'approved';
	$feedID = 'all';
	
	if(isset($_REQUEST['tp_insert_filter_feed'])) {
		$selectFeed = $_REQUEST['tp_insert_filter_feed'];
		if(substr($_REQUEST['tp_insert_filter_feed'], 0, strpos($_REQUEST['tp_insert_filter_feed'], 'c_')) === '') {
			$campaignID = substr($_REQUEST['tp_insert_filter_feed'], 2);
		} elseif(substr($_REQUEST['tp_insert_filter_feed'], 0, strpos($_REQUEST['tp_insert_filter_feed'], 'f_')) === '') {
			$feedID = substr($_REQUEST['tp_insert_filter_feed'], 2);
		}
	}
	
	$search = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
	$page = isset($_REQUEST['tp_insert_page']) ? $_REQUEST['tp_insert_page'] : 1;
	$pt = tp_get_post_type();
	
	$tp = tp_get_wrapper();
	$perpage = 24;
	$sort = 'date';
	$uniq_ids = true;
	$products = $tp->product_store_products_search($campaignID, $search, $feedID, null, $page, $perpage, $sort, $uniq_ids);
	$products = fix_result($products);
	$i = -1;
?>
	<ul class="tp-product-list clear">
<?php foreach($products as $product) : ?>
<?php 
	$t = get_posts( (( $pt == 'post') ? '' : "post_type={$pt}&") . "meta_key=tp_product_ID&meta_value={$product->id}&post_status=publish,draft,trash");
	$pr = empty($t) ? null : array_pop($t);
	$pid = $pr ? $pr->ID : 0;
	if ( $pr ) $outdated = tp_check_product_outdated($product, $pr);
	unset($t);
?>
	<li class="tp-product-list-entry<?php echo !($i = ++$i % 4) ? " clear" : ''; ?>">
		<p class="tp-product-image product-<?php echo $product->id; ?>">
			<a href="<?php echo $product->url; ?>" target="_blank"><img src="<?php echo $product->{'image-url'}; ?>" title="<?php echo $product->title; ?>" class="tp-product-thumbnail" /></a>
			<br/>
			<a href="<?php echo $product->url; ?>" target="_blank"><small><?php _e('Click for details', 'tppi'); ?></small></a>
		</p>
		<p><span class="tp-product-title product-<?php echo $product->id; ?>"><strong><?php echo $product->brand; ?></strong> <?php echo $product->title; ?></span></p>
		<p><span class="tp-product-price product-<?php echo $product->id; ?>"><?php echo $product->price; ?></span></p>
		
		<input type="hidden" id="tp_product_<?php echo $product->id; ?>_id" class="tp-product-id" value="<?php echo $product->id; ?>" />
		<input type="hidden" id="tp_product_<?php echo $product->id; ?>_feed_id" class="tp-product-feed-id" value="<?php echo $product->{'product-store-id'}; ?>" />
		
		<p class="tp-action-row submitbox">
			<input type="button" id="tp_product_<?php echo $product->id; ?>_button" class="button-secondary tp-product-action-button product-<?php echo $product->id; ?>" value="<?php _e( 'Insert', 'tppi' ); ?>" />
		</p>
	</li>
<?php endforeach; ?>
	</ul>
	<div class="tablenav">
		<div class="alignleft tablenav-pages">
<?php if($page > 1) : ?>
			<a id="tp-feed-prevpage" class="prev page-numbers" href="<?php echo admin_url( 'admin-ajax.php' ); ?>?_ajax_nonce=<?php echo wp_create_nonce( 'tp_ajax_nonce' );?>&action=tp_insertproduct_container&tp_insert_filter_feed=<?php echo isset($_REQUEST['tp_insert_filter_feed']) ? $_REQUEST['tp_insert_filter_feed'] : ''; ?>&s=<?php echo urlencode($search);?>&tp_insert_page=<?php echo $page-1; ?>">&laquo;</a>
<?php endif; ?>
			<a id="tp-feed-nextpage" class="next page-numbers" href="<?php echo admin_url( 'admin-ajax.php' ); ?>?_ajax_nonce=<?php echo wp_create_nonce( 'tp_ajax_nonce' );?>&action=tp_insertproduct_container&tp_insert_filter_feed=<?php echo isset($_REQUEST['tp_insert_filter_feed']) ? $_REQUEST['tp_insert_filter_feed'] : ''; ?>&s=<?php echo urlencode($search);?>&tp_insert_page=<?php echo $page+1; ?>">&raquo;</a>
		</div>
	</div>
<?php
	die();
}

endif;
