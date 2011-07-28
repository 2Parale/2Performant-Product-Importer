<?php

if ( is_admin() ) :

add_action( 'add_meta_boxes', 'tp_add_product_box' );

function tp_add_product_box() {
	global $post;
	
	// Check if it's a product
	if ( ! get_post_meta( $post->ID, 'tp_product_info', true ) )
		return false;

	$post_type = tp_get_post_type();
	add_meta_box( 'tp_product_info', __( '2Performant Product Information', 'tppi' ), 'tp_product_info_inner_boxes', $post_type, 'normal', 'high' );
	
	wp_enqueue_script( 'tp-edit-script' );
}

function tp_product_info_inner_boxes() {
	global $post;
	require_once 'api.php';
	
	// check for updates
	$s = tp_get_post_product_data( $post->ID );
	if ( $s && is_object( $s ) && isset( $s->id ) && isset( $s->{'product_store_id'} ) ) {
		$pr = tp_get_wrapper()->product_store_showitem( $s->{'product_store_id'}, $s->id );
		if ( $pr ) {
			$outdated = tp_check_product_outdated( $pr, $post );
			if ( $outdated ) {
?>
	<div id="tp-update-error" class="error">
		<input type="hidden" id="tp_ajax_nonce" value="<?php echo wp_create_nonce( 'tp_ajax_nonce' ); ?>" />
		<p>
			<strong><?php _e('Warning!'); ?></strong>
			<?php _e('Product is outdated. <a href="#" id="tp_update_product_info">Update now</a>'); ?>
		</p>
	</div>
<?php
			}
		}
	}
?>
	<input type="hidden" id="tp_post_id" value="<? echo $post->ID; ?>" />
	<style type="text/css">
	#tp_product_info .tp_product_field {
		width: 98%;
	}
	.tp_product_note {
		color:#EF9595;	
	}
	.tp_product_undo {
		display:none;
		color:#7DDFA6;
	}
	</style>
<?php
	
	// Use nonce for verification
	wp_nonce_field( plugin_basename( __FILE__ ), 'tp_product_info_nonce' );
	
	$info_fields = get_option( 'tp_options_fields' );
	$info_fields = $info_fields['fields'];
	
	$pm = get_post_meta( $post->ID, 'tp_product_info', true );
	$pAPI = tp_get_post_product_data( $post->ID );

	echo "<dl>";
	?>
	<?php 
	// The actual fields for data entry
	foreach ( $info_fields as $name => $var ) {
		
		$pAPI_value = tp_strtopinfo( $var['value'] , $pAPI );
		
		$var['value'] = isset( $pm[$name] ) ? $pm[$name] : '';
		$var['value'] = htmlspecialchars_decode ( trim( $var['value'] ) );
		$pAPI_value = htmlspecialchars_decode ( trim( $pAPI_value ) );
		
		$type = 'text';
		if ( isset( $var['type'] ) ) {
			$type = $var['type'];
		}
		
?><dt><label for="tp_product_<?php echo $name; ?>"><?php echo $var['label']; ?></label></dt><dd>
<?php
	$tp_hidden = ( $var['value'] != $pAPI_value ) ? '':'hidden';
	
	echo '<p id="note_'.$name.'" class="tp_product_note '.$tp_hidden.'"><strong>Note:</strong> This field was modified by you and will not be updated when you push the "UPDATE ALL" button from Toolbox. <a href="#" rel="'.$name.'" class="tp_product_revert" title="Revert to initial value got it from API">Revert &raquo;</a></p>';
	echo '<p id="undo_'.$name.'" class="tp_product_undo">You can Undo until you leave this page: <a href="#" class="tp_undo_revert" rel="'.$name.'" title="Undo reverted value">Undo</a></p>';
	?>
	<input type="hidden" id="tp_hidden_product_<?php echo $name; ?>" value="<?php echo $pAPI_value; ?>" />
	<input type="hidden" id="tp_modified_product_<?php echo $name; ?>" value="<?php echo $var['value']; ?>" />
	<?php
	switch($type) {
		case 'text':
?><input type="text" class="tp_product_field" rel="<?php echo $name; ?>" id="tp_product_<?php echo $name; ?>" name="tp_product_<?php echo $name; ?>" value="<?php echo $var['value']; ?>" /><?php
			break;
		case 'textarea':
?><textarea class="tp_product_field" rel="<?php echo $name; ?>" id="tp_product_<?php echo $name; ?>" name="tp_product_<?php echo $name; ?>" cols="40" rows="15"><?php echo $var['value']; ?></textarea><?php
			break;
	}
?>
	</dd>
<?php
	}
	
	echo "</dl>";
}

add_action( 'save_post', 'tp_save_productdata' );

function tp_save_productdata( $post_id ) {
	// verify this came from the our screen and with proper authorization,
	// because save_post can be triggered at other times
	if ( ! isset( $_POST['tp_product_info_nonce'] ) || ! wp_verify_nonce( $_POST['tp_product_info_nonce'], plugin_basename( __FILE__ ) ) ) {
		return $post_id;
	}

	// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want
	// to do anything
	if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
		return $post_id;

	// Check permissions
	if ( ! current_user_can( 'edit_post', $post_id ) )
		return $post_id;
	
	// Check if it's a product
	if ( ! get_post_meta( $post_id, 'tp_product_info', true ) )
		return $post_id;

	// OK, we're authenticated: we need to find and save the data
	
	$info_fields = get_option( 'tp_options_fields' );
	$info_fields = $info_fields['fields'];
	
	$pm = get_post_meta( $post_id, 'tp_product_info', true );
	
	foreach ( $info_fields as $name => $var ) {
		if ( isset($_POST['tp_product_'.$name] ) ) {
			$pm[$name] = $_POST['tp_product_'.$name];
		}
	}
	update_post_meta( $post_id, 'tp_product_info', $pm );
	
	return $post_id;
}

add_action ( 'wp_ajax_tp_updateproduct', 'tp_ajax_updateproduct' );
function tp_ajax_updateproduct() {
	check_ajax_referer( 'tp_ajax_nonce' );
	
	$id = isset( $_REQUEST['post_id'] ) ? $_REQUEST['post_id'] : die( -1 );
	
	$post = get_post( $id );
	$postdata = tp_get_post_product_data( $post->ID );
	
	$res = tp_add_product_from_feed( $postdata->id, $postdata->{'product_store_id'}, wp_get_post_categories( $post->ID ) );
	
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

endif;

?>
