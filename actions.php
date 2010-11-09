<?php

function tp_strtopinfo( $str, $info ) {
	foreach ( $info as $var => $value ) {
		$str = str_replace( "%{$var}%", $value, $str );
	}
	
	return $str;
}

function tp_add_product_from_feed( $id, $feed, $category = array() ) {
	include_once 'api.php';
	$tp = tp_get_wrapper();
	
	$pt = tp_get_post_type();
	
	if ( ! is_array( $category ) )
		$category = array( $category );
	
	$errors = array();
	try {
		$pinfo = $tp->product_store_showitem( $feed, $id );
		
		if ( ! is_object( $pinfo ) )
			throw new Exception( sprintf( __( '2Performant API error: Invalid response for product %1$s (product store %2$s)' ), $id, $feed ) );
		
		if ( isset( $pinfo->error ) )
			throw new Exception( sprintf( __( '2Performant API error: %1$s' ), $pinfo->error ), 101 );
		
		$post_status = get_option('tp_options_add_feed', array('post_status' => 'publish'));
		$post_status = $post_status['post_status'];
		$post = array(
			'post_type' => $pt,
			'post_title' => $pinfo->brand . " " . $pinfo->title,
			'post_status' => $post_status,
			'post_category' => $category
		);
		
		$pt = ($pt == 'post') ? '' : "post_type=$pt&";
		
		$existing = get_posts("{$pt}meta_key=tp_product_ID&meta_value=$id");
		if(!empty($existing)) {
			$post['ID'] = array_pop($existing)->ID;
			unset( $post['post_status'] );
			$func = 'wp_update_post';
		} else {
			$func = 'wp_insert_post';
		}
		
		$ok = $func( $post );
		if(!$ok)
			$errors[] = 'Error adding/updating product';
			
		update_post_meta( $ok, 'tp_product_ID', $id );
		tp_set_post_product_data( $ok, $pinfo );
		tp_set_post_meta( $ok, $pinfo, ( $func == 'wp_update_post' ) );
		
		if( $func == 'wp_insert_post' && $pinfo->{'image-url'} )
			tp_add_product_thumbnail($pinfo->{'image-url'}, $ok);
		
		return $ok;
	} catch(Exception $e) {
		$errors[] = $e->getMessage();
	}
	
	return $errors;
}

function tp_add_product_thumbnail( $url, $post_id ) {
	// save image
	$img = imagecreatefromstring( file_get_contents( $url ) );
	$upload_dir = wp_upload_dir();
	$path = $upload_dir['path'];
	$filename = $path . '/' . $post_id . '.png';
	if(!imagepng($img, $filename))
		return false;
	
	// add as media
	$wp_filetype = wp_check_filetype($filename, null );
	$attachment = array(
		'post_mime_type' => $wp_filetype['type'],
//		'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
		'post_title' => get_the_title( $post_id ),
		'post_content' => '',
		'post_status' => 'inherit'
	);
	$attach_id = wp_insert_attachment( $attachment, $filename, $post_id );
	if(!$attach_id)
		return false;
	
	// you must first include the image.php file
	// for the function wp_generate_attachment_metadata() to work
	require_once(ABSPATH . "wp-admin" . '/includes/image.php');
	$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
	wp_update_attachment_metadata( $attach_id,  $attach_data );
	
	// link to post as thumbnail
	update_post_meta($post_id, '_thumbnail_id', $attach_id);
	
	return true;
}

function tp_delete_product_from_feed( $id, $feed ) {
	include_once 'api.php';
	$tp = tp_get_wrapper();
	
	$pt = tp_get_post_type();
	
	$errors = array();
	try {
		$pinfo = $tp->product_store_showitem($feed, $id);
		
		$pt = ($pt == 'post') ? '' : "post_type=$pt&";
		
		$existing = get_posts("{$pt}meta_key=tp_product_ID&meta_value=$id&post_status=publish,draft,trash");
		if(!empty($existing)) {
			$pid = array_pop($existing)->ID;
			
			$att_id = get_post_meta($pid, '_thumbnail_id');
			wp_delete_attachment( $att_id, true );
			
			wp_delete_post( $pid, true);
			
			return true;
		}
	} catch(Exception $e) {
		$errors[] = $e->getMessage();
	}
	
	return $errors;
}

function tp_check_product_outdated( $product, $post = null ) {
	if ( $post == null )
		return false;
	
	$s = tp_get_post_product_data( $post->ID );
	 
//	var_dump($product, $s);
//	var_dump(get_post_meta( $post->ID, 'tp_product_data', true ), $s);
//	var_dump($product->{'updated-at'}, $s->{'updated-at'}, $product->{'updated-at'} != $s->{'updated-at'});
	
	return ( $product->{'updated-at'} != $s->{'updated-at'} );
	
//	$errors = array();
//	
//	if ( $post->post_title != $product->brand . " " . $product->title)
//		$errors[] = __( 'Post title outdated', 'tppi' );
//	
//	if ( get_post_meta( $post->ID, 'tp_product_ID' ) != $product->id )
//		$errors[] = __( 'Product ID outdated', 'tppi' );
//	
//	update_post_meta( $ok, 'tp_product_data', serialize( $pinfo ) );
//		
//		$opts = get_option( 'tp_options_fields', array( 'fields' => array() ) );
//		$opts = $opts['fields'];
//		
//		$vars = array();
//		foreach ( $opts as $name => $o ) {
//			$vars[$name] = tp_strtopinfo( $o['value'], $pinfo );
//		}
//		update_post_meta( $ok, 'tp_product_info', $vars );
//		
//		if( $func == 'wp_insert_post' && $pinfo->{'image-url'} )
//			tp_add_product_thumbnail($pinfo->{'image-url'}, $ok);
//		
//		return $ok;
//	} catch(Exception $e) {
//		$errors[] = $e->getMessage();
//	}
//	
//	return $errors;
}

function tp_get_post_product_data( $post_id ) {
	// base64 encoding for unserialize bug if string contains ';' - details http://davidwalsh.name/php-serialize-unserialize-issues
	$data = get_post_meta( $post_id, 'tp_product_data', true );
	if ( $data === '' )
		return false;
	$data = unserialize( base64_decode( $data ) );
	
	foreach ( $data as $property => $value ) {
		$data->$property = base64_decode( $value );
	}
	
	return $data;
}

function tp_set_post_product_data( $post_id, $product ) {
	// base64 encoding for unserialize bug if string contains ';' - details http://davidwalsh.name/php-serialize-unserialize-issues
	$data = new stdClass();
	foreach ( $product as $property => $value ) {
		$data->$property = base64_encode( $value );
	}
	
	$data = base64_encode( serialize( $data ) );
	return update_post_meta( $post_id, 'tp_product_data', $data );
}

function tp_set_post_meta( $id, $pinfo, $preserve = false ) {
	$meta = get_option( 'tp_options_fields', array( 'fields' => array(), 'other_fields' => array() ) );
	$opts = $meta['fields'];
	$oldmeta = get_post_meta( $id, 'tp_product_info', true );
	if ( $oldmeta == '' )
		$preserve = false;
	$oldinfo = tp_get_post_product_data( $id );
//	var_dump($oldmeta, $opts);
	
	$vars = array();
	foreach ( $opts as $name => $o ) {
		// if post meta was changed and should be preserved
		if ( $preserve && $oldmeta[$name] && $oldmeta[$name] != tp_strtopinfo( $o['value'], $oldinfo ) ) {
			$vars[$name] = $oldmeta[$name];
		} else {
			// update post meta
			$vars[$name] = tp_strtopinfo( $o['value'], $pinfo );
		}
	}
	update_post_meta( $id, 'tp_product_info', $vars );
	
	$custom_fields = $meta['other_fields'];
	foreach ( $custom_fields as $name => $o ) {
		$oldmeta = get_post_meta( $id, $name, true );
		if ( $preserve && $oldmeta && $oldmeta != tp_strtopinfo( $o['value'], $oldinfo ) ) {
			//update_post_meta( $id, $name, );
		} else {
			update_post_meta( $id, $name, tp_strtopinfo( $o['value'], $pinfo ) );
		}
	}
}

?>