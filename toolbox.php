<?php

if ( is_admin() ) :

function tp_product_toolbox() {
	$tp = false;
	$errors = tp_verify_connection( $tp );
	
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
			<p><?php _e( 'Please note that the fields which were manually edited will not be updated.', 'tppi' ); ?></p>
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
	
	tp_campaign_dropdown( array(
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


class TP_AJAX_wrapper {
	
	public static function __callStatic( $name, $args = null ) {
		check_ajax_referer( 'tp_ajax_nonce' );
		
		$out = array();
		
		try {
			$res = call_user_func( array( __CLASS__, $name ) );
		} catch(Exception $e) {
			$out['error'] = $e->getMessage();
		}
		
		$out['responseStatus'] = $res ? 'ok' : 'null';
		$out['response'] = $res;
		
		echo json_encode($out);
		
		die();
	}
	
	protected static function getNumProducts() {
		$numposts = wp_count_posts( tp_get_post_type() );
		
		$res = array(
			'numProducts' => $numposts->publish
		);
		
		return $res;
	}
	
	protected static function getProducts() {
		$prods = get_posts( array(
			'type' => tp_get_post_type(),
			'numberposts' => -1,
		));
		foreach ( $prods as $k => $v ) {
			$t = get_post_meta( $v->ID, 'tp_product_info', true );
			if ( $t !== '' )
				$prods[$k] = $v->ID;
			else
				unset($prods[$k]);
		}
		
		$res = array ( 'ids' => $prods );
		
		return $res;
	}
	
	protected static function getCampaignProducts() {
		$prods = get_posts( array(
			'type' => tp_get_post_type(),
			'numberposts' => -1,
		));
		$campaign_id = ( isset( $_REQUEST['campaign_id'] ) && $_REQUEST['campaign_id'] != 'all' ) ? $_REQUEST['campaign_id'] : false;
		
		if ( ! is_numeric( $campaign_id ) )
			throw new Exception( sprintf( __( 'Invalid campaign ID: %1$s', 'tppi' ), $campaign_id ) );
		
		foreach ( $prods as $k => $v ) {
			$data = tp_get_post_product_data( $v->ID );
//			var_dump($campaign_id,$data->{'campaign-id'},$data->{'campaign-id'} != $campaign_id);
			if ( $campaign_id && $data && $data->{'campaign-id'} == $campaign_id ) {
				$prods[$k] = $v->ID;
			} else {
				unset( $prods[$k] );
			}
		}
//		var_dump($prods);
		
		$res = array ( 'ids' => $prods );
		
		return $res;
	}
	
	protected static function updateProduct() {
		$errors = array();
		$id = ( isset( $_REQUEST['post_id'] ) && is_numeric( $_REQUEST['post_id'] ) ) ? $_REQUEST['post_id'] : false;
		
		if ( ! $id )
			throw new Exception( __( 'Undefined post ID' ) );
		
		$p = get_post( $id );
		if ( ! $p )
			throw new Exception( sprintf( __( 'Invalid post ID: %1$s' ), $id ) );
		
		$product = tp_get_post_product_data( $id );
		
		try {
			require_once 'api.php';
			
			$live_product = tp_get_wrapper()->product_store_showitem( $product->{'product-store-id'}, $product->id );
			if ( isset( $live_product->error ) )
				$live_product = false;
		} catch( Exception $e ) {
			$live_product = false;
		}
		
		if ( ! $live_product ) {
			// Delete the post
			wp_delete_post( $id );
			throw new Exception( sprintf( __( 'Expired product: %1$s' ), $product->id ) );
		}
		
		$new_id = tp_add_product_from_feed( $product->id, $product->{'product-store-id'} );
		
		if ( is_array( $new_id ) )
			$errors = $new_id;
		else
			$id = $new_id;
		
		return array(
			'errors' => $errors,
			'id' => $id
		);
	}
	
	protected static function deleteProduct() {
		$errors = array();
		$id = ( isset( $_REQUEST['post_id'] ) && is_numeric( $_REQUEST['post_id'] ) ) ? $_REQUEST['post_id'] : false;
		$force = ( isset( $_REQUEST['force'] ) && $_REQUEST['force'] !== 'false' ) ? $_REQUEST['force'] : false;
		
		if ( ! $id )
			throw new Exception( __( 'Undefined post ID' ) );
		
		$p = get_post( $id );
		
		$att_id = get_post_meta( $id, '_thumbnail_id' );
		if ( $force && ! wp_delete_attachment( $att_id, true ) )
			$errors[] = __( 'Could not delete attachment', 'tppi' );
		
		if ( ! wp_delete_post( $id, $force ) )
			throw new Exception( sprintf(__( 'Could not delete post %s' ), $id ) );
		
		return array(
			'errors' => $errors,
			'id' => $id
		);
	}
}

$ajax_actions = array(
	'getNumProducts',
	'getProducts',
	'getCampaignProducts',
	'updateProduct',
	'deleteProduct'
);
foreach ( $ajax_actions as $action ) {
	add_action ( 'wp_ajax_tp_'.$action, array( 'TP_AJAX_wrapper', $action ) );
}

endif;

?>