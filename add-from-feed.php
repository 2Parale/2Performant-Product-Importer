<?php

if ( is_admin() ) :

include_once 'api.php';

function tp_product_add_from_feed() {
	
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	$tp = false;
	try {
		$tp = tp_get_wrapper();
	} catch(Exception $e) {
		$errors[] = $e->getMessage();
	}
	
?><div class="wrap">
	<?php if ( function_exists( 'screen_icon' ) ) screen_icon(); ?><h2>2Performant Product Importer</h2>
<?php if ( ! empty( $errors ) ) : ?>
	<div id="setting-error-options_error" class="error settings-error">
<?php foreach ( $errors as $e ) : ?>
		<p><?php _e( $e, 'tppi' ); ?></p>
<?php endforeach; ?>
		<p><?php _e('Please check <a href="options-general.php?page=2performant-product-importer">settings page</a>', 'tppi'); ?></p>
	</div>
<?php elseif ( ! $tp ) : ?>
	<div id="setting-error-options_error" class="error settings-error">
		<p><?php _e('Unable to connect to 2Performant network', 'tppi'); ?></p>
	</div>
<?php else : ?>
	<!-- Habebum connectorum! -->
<?php
	ob_start();
	try {
	$products = array();
	$campaignID = 'approved';
	$feedID = 'all';
	
	if(isset($_REQUEST['tp_add_filter_feed'])) {
		$selectFeed = $_REQUEST['tp_add_filter_feed'];
		if(strpos($_REQUEST['tp_add_filter_feed'], 'c_') === 0) {
			$campaignID = substr($_REQUEST['tp_add_filter_feed'], 2);
		} elseif(strpos($_REQUEST['tp_add_filter_feed'], 'f_') === 0) {
			$feedID = substr($_REQUEST['tp_add_filter_feed'], 2);
		}
	}
	
	$search = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
	$page = isset($_REQUEST['tp_add_page']) ? $_REQUEST['tp_add_page'] : 1;
	$pt = tp_get_post_type();
?>
	<script type="text/javascript"><!--//<![CDATA[
		var tpBaseUrl = '<?php echo get_bloginfo('url').'/wp-content/plugins/'.dirname(plugin_basename(__FILE__)); ?>';
	//]]>--></script>
	<form name="form-search" action="" method="get">
<?php if ( $pt !== 'post' ) : ?>
		<input type="hidden" name="post_type" value="<?php echo $pt; ?>" />
<?php endif; ?>
		<input type="hidden" name="page" value="tp_product_add_from_feed" />
		<label for="tp_add_filter_feed">Feed</label>
		<?php echo tp_feed_dropdown( array(
			'name' => 'tp_add_filter_feed',
			'id' => 'tp_add_filter_feed',
			'class' => 'postform',
			'value' => $selectFeed
		) ); ?>
		<label for="tp_add_filter_search">Keywords</label>
		<input type="text" id="tp_add_filter_search" name="s" value="<?php echo $search; ?>" />
		<input type="submit" id="tp_add_filter_submit" class="button-secondary" value="<?php _e('Search') ?>" />
	</form>
	<input type="hidden" id="tp_ajax_nonce" value="<?php echo wp_create_nonce( 'tp_ajax_nonce' ); ?>" />
	<div id="tp_product_list_container">

	</div>
	
	<div id="tp-insert-toolbox" class="wrap">
		<?php $tax = get_taxonomy( 'category' ); ?>
		<div id="taxonomy-category" class="categorydiv">
			<div id="category-all" class="tabs-panel">
				<ul id="categorychecklist" class="list:category categorychecklist form-no-clear">
					<?php wp_terms_checklist(0, array( 'taxonomy' => 'category' ) ) ?>
				</ul>
			</div>

			<?php if ( !current_user_can($tax->cap->assign_terms) ) : ?>
			<p><em><?php _e('You cannot modify this Taxonomy.'); ?></em></p>
			<?php endif; ?>
			<?php if ( current_user_can($tax->cap->edit_terms) ) : ?>
				<div id="category-adder" class="wp-hidden-children">
					<h4>
						<a id="category-add-toggle" href="#category-add" class="hide-if-no-js" tabindex="3">
							<?php printf( __( '+ %s' ), $tax->labels->add_new_item ); ?>
						</a>
					</h4>
					<p id="category-add" class="category-add wp-hidden-child">
						<label class="screen-reader-text" for="newcategory"><?php echo $tax->labels->add_new_item; ?></label>
						<input type="text" name="newcategory" id="newcategory" class="form-required form-input-tip" value="<?php echo esc_attr( $tax->labels->new_item_name ); ?>" tabindex="3" aria-required="true"/>
						<label class="screen-reader-text" for="newcategory_parent">
							<?php echo $tax->labels->parent_item_colon; ?>
						</label>
						<?php wp_dropdown_categories( array( 'taxonomy' => 'category', 'hide_empty' => 0, 'name' => 'newcategory_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $tax->labels->parent_item . ' &mdash;', 'tab_index' => 3 ) ); ?>
						<input type="button" id="category-add-submit" class="add:categorychecklist:category-add button category-add-sumbit" value="<?php echo esc_attr( $tax->labels->add_new_item ); ?>" tabindex="3" />
						<?php wp_nonce_field( 'add-category', '_ajax_nonce-add-category', false ); ?>
						<span id="category-ajax-response"></span>
					</p>
				</div>
			<?php endif; ?>
		</div>
	</div>
<?php } catch(TPException $e) {
		ob_end_clean(); 
?>
	<div id="setting-error-options_error" class="error settings-error">
		<p><?php _e(get_class($e) . ': ' . $e->getMessage(), 'tppi'); ?></p>
<?php if($e->getData()) : ?>
		<p><?php _e( 'Additional data:', 'tppi' ); ?><br/><textarea cols="80"><?php var_dump($e->getData()); ?></textarea></p>
<?php endif; ?>
	</div>
<?php } catch(Exception $e) {
		ob_end_clean(); 
?>
	<div id="setting-error-options_error" class="error settings-error">
		<p><?php _e(get_class($e) . ': ' . $e->getMessage(), 'tppi'); ?></p>
	</div>
<?php } 
	ob_end_flush();
?>
</div><?php
	endif;
}

add_action ( 'wp_ajax_tp_addproduct_container', 'tp_ajax_addproduct_container' );
function tp_ajax_addproduct_container() {
	$products = array();
	$campaignID = 'approved';
	$feedID = 'all';
	
	if(isset($_REQUEST['tp_add_filter_feed'])) {
		$selectFeed = $_REQUEST['tp_add_filter_feed'];
		if(strpos($_REQUEST['tp_add_filter_feed'], 'c_') === 0) {
			$campaignID = substr($_REQUEST['tp_add_filter_feed'], 2);
		} elseif(strpos($_REQUEST['tp_add_filter_feed'], 'f_') === 0) {
			$feedID = substr($_REQUEST['tp_add_filter_feed'], 2);
		}
	}
	
	$search = isset($_REQUEST['s']) ? $_REQUEST['s'] : '';
	$page = isset($_REQUEST['tp_add_page']) ? $_REQUEST['tp_add_page'] : 1;
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
//	$jimminy_cricket = $tp->product_store_showitem($product->{'product_store_id'}, $product->id );
//	var_dump($product, $jimminy_cricket);
?>
	<li class="tp-product-list-entry<?php echo $pr ? ' existing' : ''; echo $pr && $outdated ? ' outdated' : ''; echo !($i = ++$i % 4) ? " clear" : ''; echo ($pr && $pr->post_status == 'trash') ? ' trash' : ''; ?>">
		<p class="tp-product-image product-<?php echo $product->id; ?>">
			<a href="<?php echo $product->url; ?>" target="_blank"><img src="<?php echo $product->{'image_url'}; ?>" title="<?php echo $product->title; ?>" class="tp-product-thumbnail" /></a>
			<br/>
			<a href="<?php echo $product->url; ?>" target="_blank"><small><?php _e('Click for details', 'tppi'); ?></small></a>
		</p>
		<p><span class="tp-product-title product-<?php echo $product->id; ?>"><strong><?php echo $product->brand; ?></strong> <?php echo $product->title; ?></span></p>
		<p><span class="tp-product-price product-<?php echo $product->id; ?>"><?php echo $product->price; ?></span></p>
		
		<input type="hidden" id="tp_product_<?php echo $product->id; ?>_id" class="tp-product-id" value="<?php echo $product->id; ?>" />
		<input type="hidden" id="tp_product_<?php echo $product->id; ?>_feed_id" class="tp-product-feed-id" value="<?php echo $product->{'product_store_id'}; ?>" />
		
<?php $cats = wp_get_post_categories( $pid );  foreach ( $cats as $cid ) : ?>
		<input type="hidden" class="tp-category-id" value="<?php echo $cid; ?>" />
<?php endforeach; ?>
		
		<div class="tp-product-toolbox product-<?php echo $product->id; ?>"></div>	
		
		<p class="tp-action-row submitbox">
			<input type="button" id="tp_product_<?php echo $product->id; ?>_button" class="button-secondary tp-product-action-button product-<?php echo $product->id; ?>" />
		</p>
	</li>
<?php endforeach; ?>
	</ul>
	<div class="tablenav">
		<div class="alignleft tablenav-pages">
<?php if($page > 1) : ?>
			<a id="tp-feed-prevpage" class="prev page-numbers" href="admin-ajax.php?_ajax_nonce=<?php echo wp_create_nonce( 'tp_ajax_nonce' );?>&action=tp_addproduct_container&tp_add_filter_feed=<?php echo isset($_REQUEST['tp_add_filter_feed']) ? $_REQUEST['tp_add_filter_feed'] : ''; ?>&s=<?php echo urlencode($search);?>&tp_add_page=<?php echo $page-1; ?>">&laquo;</a>
<?php endif; ?>
			<a id="tp-feed-nextpage" class="next page-numbers" href="admin-ajax.php?_ajax_nonce=<?php echo wp_create_nonce( 'tp_ajax_nonce' );?>&action=tp_addproduct_container&tp_add_filter_feed=<?php echo isset($_REQUEST['tp_add_filter_feed']) ? $_REQUEST['tp_add_filter_feed'] : ''; ?>&s=<?php echo urlencode($search);?>&tp_add_page=<?php echo $page+1; ?>">&raquo;</a>
		</div>
	</div>
<?php
	die();
}

add_action ( 'wp_ajax_tp_addproduct', 'tp_ajax_addproduct' );
function tp_ajax_addproduct() {
	check_ajax_referer( 'tp_ajax_nonce' );
	
	$id = isset( $_REQUEST['product_id'] ) ? $_REQUEST['product_id'] : die( -1 );
	$feed = isset( $_REQUEST['feed_id'] ) ? $_REQUEST['feed_id'] : die( -1 );
	$category = isset( $_REQUEST['category'] ) ? $_REQUEST['category'] : die( -1 );
	
	$res = tp_add_product_from_feed( $id, $feed, $category );
	
	if( is_array($res) ) {
		foreach ( $res as $err ) {
			echo "<li>$err</li>";
		}
		die();
	} elseif( is_numeric( $res ) ) {
		echo "ok";
		die();
	}
	
	die();
}

add_action ( 'wp_ajax_tp_deleteproduct', 'tp_ajax_deleteproduct' );
function tp_ajax_deleteproduct() {
	check_ajax_referer( 'tp_ajax_nonce' );
	
	$id = isset( $_REQUEST['product_id'] ) ? $_REQUEST['product_id'] : die( -1 );
	$feed = isset( $_REQUEST['feed_id'] ) ? $_REQUEST['feed_id'] : die( -1 );
	
	$res = tp_delete_product_from_feed( $id, $feed );
	
	if( is_array($res) ) {
		foreach ( $res as $err ) {
			echo "<li>$err</li>";
		}
		die();
	} elseif( is_bool( $res ) ) {
		echo "ok";
		die();
	}
	
	die();
}

endif;

?>