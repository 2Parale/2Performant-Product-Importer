<?php 

if ( is_admin() ) :

$pt = tp_get_post_type();
$th_hook = ( $pt == 'post' || $pt == 'page' ) ? "manage_{$pt}s_columns" : "manage_edit-{$pt}_columns";


add_filter( $th_hook, 'tp_manage_post_table_header' );
function tp_manage_post_table_header( $defaults ) {
//	$defaults['tp_updated'] = __( 'Updated', 'tppi' );
	$defaults['tp_price'] = __( 'Price', 'tppi' );
	if ( function_exists( 'get_the_post_thumbnail' ) ) {
		$defaults = array_merge( array_splice( $defaults, 0, 1 ), array( 'tp_thumbnail' => __( 'Thumbnail', 'tppi' ) ), $defaults );
	}
	return $defaults;
}

add_action( 'manage_posts_custom_column' , 'tp_custom_columns', 10, 2 );
function tp_custom_columns( $column, $id ) {
	require_once 'api.php';
	
	if( $column == 'tp_updated' ) {
		$prod = tp_get_post_product_data( $id );
		
		if ( tp_check_product_outdated( tp_get_wrapper()->product_store_showitem( $prod->{'product_store_id'}, $prod->id ), get_post( $id ) ) ) {
?>
			<input type="hidden" value="<?php echo $id ?>" class="tp-update-product-id" />
			No. <a href="#" id="tp_update_product_action_<?php echo $id; ?>" class="tp-update-product-info">Update now</a>
			<input type="hidden" id="tp_ajax_nonce_<?php echo $id; ?>" value="<?php echo wp_create_nonce( 'tp_ajax_nonce' ); ?>" />
<?php
		} else {
			echo "Yes (".date(get_option('date_format').' '.get_option('time_format'), strtotime($prod->{'updated_at'})).")";
		}
	} elseif ( $column == 'tp_thumbnail' ) {
		echo get_the_post_thumbnail( $id, array( 60, 60 ) );
	} elseif ( $column == 'tp_price' ) {
		$meta = get_post_meta( $id, 'tp_product_info' );
		$price = $meta[0]['price'];
		echo esc_attr( $price );
	}
}

add_action( 'admin_enqueue_scripts', 'tp_listing_enqueue', 10, 1 );
function tp_listing_enqueue( $hook ) {
	if ( $hook == 'edit.php' ) {
		wp_enqueue_script( 'tp-listing-script' );
	}
}

endif;

?>