<?php

if ( is_admin() ) :

function tp_product_toolbox() {
	$errors = array();
	
	$tp = false;
	try {
		$tp = tp_get_wrapper();
	} catch(Exception $e) {
		$errors[] = $e->getMessage();
	}
	
?><div class="wrap">
	<?php if ( function_exists( 'screen_icon' ) ) screen_icon(); ?><h2>2Performant Product Toolbox</h2>
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
	<?php wp_nonce_field( 'tp_ajax_nonce', 'tp_ajax_nonce', false ); ?>
	<h3><?php _e( 'Update all products', 'tppi' ); ?></h3>
	<div id="tp_toolbox_updateall">
		<div class="tp-container">
			<p><?php _e( 'Sometimes product feeds get updated and you end up with old product data on your site (e.g. price, availability). That\'s why you need to update your products from time to time.', 'tppi' ); ?></p>
			<p><span class="warning"><?php _e( 'Warning!', 'tppi' ); ?></span> <?php _e( 'Old products from the site without a correspondant in the product feeds will be deleted.', 'tppi' ); ?></p>
			<p><?php _e( 'Please note that the fields which were manually edited will not be updated unless you specify otherwise in the boxes below.', 'tppi' ); ?></p>
<?php 
	$product_fields = tp_get_option( 'fields', 'fields' );
	if( !empty($product_fields) ) :
?>			
			<p><?php _e( 'Fields to overwrite for all products. Check the boxes next to fields you want updated even if you manually edited them.', 'tppi' ); ?>:</p>
			<ul id="update_overwrites">
<?php foreach( $product_fields as $field_name => $field ) : ?>
				<li>
					<input type="checkbox" id="tp-overwrite-<?php echo esc_attr( $field_name ); ?>" value="<?php echo esc_attr( $field_name ); ?>" />
					<label for="tp-overwrite-<?php echo sanitize_title( $field_name ); ?>"><?php echo esc_attr( $field['label'] ); ?></label>
				</li>
<?php endforeach; ?>
			</ul>
<?php endif; ?>
		</div>
		<p class="submit">
			<input type="button" id="tp_toolbox_do_updateall" class="button-primary" value="<?php _e( 'Update all products' ); ?>" />
		</p>
	</div>
	<h3><?php _e( 'Delete all from campaign', 'tppi' ); ?></h3>
	<div id="tp_toolbox_deletecampaign">
		<div class="tp-container">
			<p><?php _e( 'You may want to delete all products from a certain campaign as a result of that campaign being deactivated.', 'tppi' ); ?></p>
		</div>
		<table class="form-table">
			<tr valign="top">
				<th scope="row"><?php _e( 'Campaign', 'tppi' ); ?></th>
				<td>
<?php
	require_once 'api.php';
	
	echo tp_campaign_dropdown( array(
		'name' => 'tp_toolbox_deletecampaign_campaign',
		'id' => 'tp_toolbox_deletecampaign_campaign',
		'class' => 'postform'
	) );
?>
				</td>
			</tr>
			<tr valign="top">
				<th scope="row"><?php _e( 'Force delete', 'tppi' ); ?></th>
				<td>
					<input type="checkbox" name="tp_toolbox_deletecampaign_force" id="tp_toolbox_deletecampaign_force" />
					<label for="tp_toolbox_deletecampaign_force"><?php _e( 'Check this to skip trash and force delete the products', 'tppi' ); ?></label>
				</td>
			</tr>
		</table>
		<p class="submit">
			<input type="button" id="tp_toolbox_do_deletecampaign" class="button-primary" value="<?php _e( 'Delete all from campaign' ); ?>" />
		</p>
	</div>
</div><?php
	endif;
}

	
function TP_AJAX_wrapper_getNumProducts() {
	check_ajax_referer( 'tp_ajax_nonce' );
	
	$out = array();
	
	try {
		global $wpdb;
		
		$perBatch = get_option( 'tp_options_add_feed', array('update_batch_size' => 50) );
		$perBatch = isset($perBatch['update_batch_size']) ? $perBatch['update_batch_size'] : 50;
		
		$q = $wpdb->prepare( "SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = %s AND post_status IN ('publish', 'draft', 'pending') AND (SELECT COUNT(*) FROM $wpdb->postmeta WHERE post_id = ID AND meta_key = 'tp_product_ID' ) > 0;", tp_get_post_type());
		$numposts = intval( $wpdb->get_var( $q ) );
		
		$res = array(
			'numProducts' => $numposts,
			'perBatch' => $perBatch
		);
	} catch(Exception $e) {
		$out['error'] = $e->getMessage();
	}
	
	$out['responseStatus'] = $res ? 'ok' : 'null';
	$out['response'] = $res;
	
	echo json_encode($out);
	
	die();
}

function TP_AJAX_wrapper_getProducts() {
	check_ajax_referer( 'tp_ajax_nonce' );
	
	$out = array();
	
	try {
		$page = ( isset( $_REQUEST['page'] ) && is_numeric( $_REQUEST['page'] ) ) ? $_REQUEST['page'] : 1;
		$perBatch = get_option( 'tp_options_add_feed', array('update_batch_size' => 50) );
		$perBatch = isset($perBatch['update_batch_size']) ? $perBatch['update_batch_size'] : 50;
		
		$prods = get_posts( 'type=' . tp_get_post_type() . '&post_status=publish,draft,pending&meta_key=tp_product_ID&numberposts='.intval($perBatch).'&offset='.intval($page*$perBatch) );
		$ids = array();
		foreach ( $prods as $k => $v ) {
			$t = get_post_meta( $v->ID, 'tp_product_info', true );
			$ok = false;
			
			$ids[] = $v->ID;
		}
		
		$res = array ( 'ids' => $ids );
	} catch(Exception $e) {
		$out['error'] = $e->getMessage();
	}
	
	$out['responseStatus'] = $res ? 'ok' : 'null';
	$out['response'] = $res;
	
	echo json_encode($out);
	
	die();
}
	
function TP_AJAX_wrapper_getCampaignProducts() {
	check_ajax_referer( 'tp_ajax_nonce' );
	
	$out = array();
	
	try {
		$prods = get_posts( array(
			'type' => tp_get_post_type(),
			'numberposts' => -1,
		));
		$campaign_id = ( isset( $_REQUEST['campaign_id'] ) && $_REQUEST['campaign_id'] != 'all' ) ? $_REQUEST['campaign_id'] : false;
		
		if ( ! is_numeric( $campaign_id ) )
			throw new Exception( sprintf( __( 'Invalid campaign ID: %1$s', 'tppi' ), $campaign_id ) );
		
		foreach ( $prods as $k => $v ) {
			$data = tp_get_post_product_data( $v->ID );
			if ( $campaign_id && $data && $data->{'campaign_id'} == $campaign_id ) {
				$prods[$k] = $v->ID;
			} else {
				unset( $prods[$k] );
			}
		}
		
		$res = array ( 'ids' => $prods );
	} catch(Exception $e) {
		$out['error'] = $e->getMessage();
	}
	
	$out['responseStatus'] = $res ? 'ok' : 'null';
	$out['response'] = $res;
	
	echo json_encode($out);
	
	die();
}
	
function TP_AJAX_wrapper_updateProduct() {
	check_ajax_referer( 'tp_ajax_nonce' );
	
	$out = array();
	
	try {
		$errors = array();
		$id = ( isset( $_REQUEST['post_id'] ) && is_numeric( $_REQUEST['post_id'] ) ) ? $_REQUEST['post_id'] : false;
		$overwrites = ( isset( $_REQUEST['overwrites'] ) && is_array( $_REQUEST['overwrites'] ) ) ? $_REQUEST['overwrites'] : false;
		
		if ( ! $id )
			throw new Exception( __( 'Undefined post ID' ) );
		
		$p = get_post( $id );
		if ( ! $p )
			throw new Exception( sprintf( __( 'Invalid post ID: %1$s' ), $id ) );
		
		$product_id = intval( get_post_meta( $id, 'tp_product_ID', true ) );
		if( !$product_id )
			throw new Exception( sprintf( __('No product ID attached for post %d'), $id ) );
			
		$product = tp_get_post_product_data( $id );
		if( !is_object($product) || empty($product->{'product_store_id'}) || empty($product->id) ) {
			$product = tp_get_wrapper()->product_store_products_search('approved',"@id $product_id");
			if( empty( $product ) )
				throw new Exception( sprintf( __('Invalid product data for ID %d'), $product_id ) );
			if( !is_object($product) ) {
				if( !is_array( $product ) )
					throw new Exception( sprintf( __('Invalid product data received from API for ID %d'), $product_id ) );
				while( is_array( $product ) ) {
					$product = array_pop( $product );
				}
				if( !is_object($product) || empty($product->{'product_store_id'}) || empty($product->id) )
					throw new Exception( sprintf( __('Invalid product data received from API for ID %d'), $product_id ) );
			}
		}
		
		try {
			require_once 'api.php';
			
			$live_product = tp_get_wrapper()->product_store_showitem( $product->{'product_store_id'}, $product->id );
			if ( isset( $live_product->error ) )
				$live_product = false;
		} catch( Exception $e ) {
			$live_product = false;
		}
		
		if ( ! $live_product ) {
			if( tp_get_option( 'add_feed', 'trash_expired', true ) ) {
				// Delete the post
				wp_delete_post( $id );
			}
			throw new Exception( sprintf( __( 'Expired product: %1$s' ), ( $product ? tp_strtopinfo( '%brand% %title% (%id%)', $product ) : '' ) ) );
		}
		
		$new_id = tp_add_product_from_feed( $product->id, $product->{'product_store_id'}, array(), $overwrites );
		
		if ( is_array( $new_id ) )
			$errors = $new_id;
		else
			$id = $new_id;
		
		$res = array(
			'errors' => $errors,
			'id' => $id,
			'name' => $p->post_title
		);
	} catch(Exception $e) {
		$out['error'] = $e->getMessage();
	}
	
	$out['responseStatus'] = ( isset( $res ) && $res ) ? 'ok' : 'null';
	$out['response'] = isset( $res ) ? $res : null;
	
	echo json_encode($out);
	
	die();
}
	
function TP_AJAX_wrapper_deleteProduct() {
	check_ajax_referer( 'tp_ajax_nonce' );
	
	$out = array();
	
	try {
		$errors = array();
		$id = ( isset( $_REQUEST['post_id'] ) && is_numeric( $_REQUEST['post_id'] ) ) ? $_REQUEST['post_id'] : false;
		$force = ( isset( $_REQUEST['force'] ) && $_REQUEST['force'] !== 'false' ) ? $_REQUEST['force'] : false;
		
		if ( ! $id )
			throw new Exception( __( 'Undefined post ID' ) );
		
		$p = get_post( $id );
		if ( ! $p )
			throw new Exception( sprintf( __( 'Invalid post ID: %1$s' ), $id ) );
		
		$att_id = get_post_meta( $id, '_thumbnail_id' );
		if ( $force && ! wp_delete_attachment( $att_id, true ) )
			$errors[] = __( 'Could not delete attachment', 'tppi' );
		
		if ( ! wp_delete_post( $id, $force ) )
			throw new Exception( sprintf(__( 'Could not delete post %s' ), $id ) );
		
		$res = array(
			'errors' => $errors,
			'id' => $id,
			'name' => $p->post_title
		);
	} catch(Exception $e) {
		$out['error'] = $e->getMessage();
	}
	
	$out['responseStatus'] = $res ? 'ok' : 'null';
	$out['response'] = $res;
	
	echo json_encode($out);
	
	die();
}

$ajax_actions = array(
	'getNumProducts',
	'getProducts',
	'getCampaignProducts',
	'updateProduct',
	'deleteProduct'
);
foreach ( $ajax_actions as $action ) {
	add_action ( 'wp_ajax_tp_'.$action, 'TP_AJAX_wrapper_' . $action );
}

//add_action('admin_init','tp_suppress_ajax_error', 5); // higher priority so that it catches all errors
//function tp_suppress_ajax_error() {
//	if( defined( 'DOING_AJAX' ) && DOING_AJAX && substr( $_REQUEST['action'], 0, 3 ) == 'tp_' )
//		ob_start();
//}

endif;

?>