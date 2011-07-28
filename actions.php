<?php

function tp_strtopinfo( $str, $info ) {
	foreach ( $info as $var => $value ) {
		if( is_object( $value ) && !is_callable( array( $value, '__toString' ) ) )
			continue;
		if( is_array( $value ) )
			$value = implode(',', $value);
		$str = str_replace( "%{$var}%", esc_attr( $value ), $str );
	}
	
	return $str;
}

function tp_add_product_from_feed( $id, $feed, $category = array(), $overwrites = array() ) {
	include_once 'api.php';
	$tp = tp_get_wrapper();
	
	$pt = tp_get_post_type();
	
	if ( ! is_array( $category ) )
		$category = array( $category );
	if ( ! is_array( $overwrites ) )
		$overwrites = array( $overwrites );
	
	$errors = array();
	try {
		$pinfo = $tp->product_store_showitem( $feed, $id );
		
		if ( ! is_object( $pinfo ) )
			throw new Exception( sprintf( __( '2Performant API error: Invalid response for product %1$s (product store %2$s)' ), $id, $feed ) );
		
		if ( isset( $pinfo->error ) )
			throw new Exception( sprintf( __( '2Performant API error: %1$s' ), $pinfo->error ), 101 );
		
		$post_status = tp_get_option( 'add_feed', 'post_status' );
		$post_title = tp_strtopinfo( tp_get_option( 'add_feed', 'post_title' ), $pinfo);
		$post_content = tp_strtopinfo( tp_get_option( 'add_feed', 'post_content' ), $pinfo);
		
		$post = array(
			'post_type' => $pt,
			'post_title' => $post_title,
			'post_content' => $post_content,
			'post_status' => $post_status,
			'post_category' => $category
		);
		
		$pt = ($pt == 'post') ? '' : "post_type=$pt&";
		
		$existing = get_posts("{$pt}meta_key=tp_product_ID&meta_value=$id&post_status=publish,draft,pending");
		if(!empty($existing)) {
			$post['ID'] = array_pop($existing)->ID;
			unset( $post['post_status'] );
			$action = 'update';
		} else {
			$action = 'insert';
		}
		
		$func = "wp_{$action}_post";
		$post = apply_filters( "tp_{$action}_postdata", $post, $pinfo );
		do_action( "tp_before_$action", $post );
		
		$ok = $func( $post );
		if(!$ok)
			$errors[] = 'Error adding/updating product';
			
		update_post_meta( $ok, 'tp_product_ID', $id );
		tp_set_post_product_data( $ok, $pinfo );
		tp_set_post_meta( $ok, $pinfo, ( $func == 'wp_update_post' ), $overwrites );
		
		if( $func == 'wp_insert_post' && $pinfo->{'image_url'} && tp_get_option( 'add_feed', 'post_featured_image' ) )
			tp_add_product_thumbnail($pinfo->{'image_url'}, $ok);
		
		if( $func == 'wp_insert_post' && is_object( $pinfo->{'image_urls'} ) && tp_get_option( 'add_feed', 'post_gallery' ) )
		if( isset( $pinfo->{'image_urls'}->{'image_url'} ) && is_array( $pinfo->{'image_urls'}->{'image_url'} ) )
			tp_add_product_gallery($pinfo->{'image_urls'}->{'image_url'}, $ok);
		
		do_action( "tp_after_$action", get_post($ok) );
		
		return $ok;
	} catch(Exception $e) {
		$errors[] = $e->getMessage();
	}
	
	return $errors;
}

function _tp_add_product_image( $url, $post_id ) {
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
	
	return $attach_id;
}

function tp_add_product_thumbnail( $url, $post_id ) {
	if( ( $attach_id = _tp_add_product_image( $url, $post_id ) ) ) {
		// link to post as thumbnail
		update_post_meta($post_id, '_thumbnail_id', $attach_id);
	} else {
		return false;
	}
	
	return true;
}

function tp_add_product_gallery( $urls, $post_id ) {
	if( !is_array($urls) )
		return false;
	
	foreach( $urls as $url ) {
		_tp_add_product_image( $url, $post_id );
	}
	
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
//	var_dump($product->{'updated_at'}, $s->{'updated_at'}, $product->{'updated_at'} != $s->{'updated_at'});
	
	return ( $product->{'updated_at'} != $s->{'updated_at'} );
	
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
//		if( $func == 'wp_insert_post' && $pinfo->{'image_url'} )
//			tp_add_product_thumbnail($pinfo->{'image_url'}, $ok);
//		
//		return $ok;
//	} catch(Exception $e) {
//		$errors[] = $e->getMessage();
//	}
//	
//	return $errors;
}

function tp_decode_product_data( $data ) {
	$data = base64_decode( $data );
	if( ( unserialize( $data ) !== FALSE ) && ( is_object( unserialize( $data ) ) || is_array( unserialize( $data ) ) ) ) {
		$data = unserialize( $data );
		foreach( $data as $k => $v ) {
			if( is_object($data) )
				$data->$k = tp_decode_product_data( $v );
			else
				$data[$k] = tp_decode_product_data( $v );
		}
	}
	
	return $data;
}

function tp_encode_product_data( $realdata ) {
	$data = is_object($realdata) ? clone $realdata : $realdata;
	if( is_object( $data ) || is_array( $data ) ) {
		foreach( $data as $k => $v ) {
			if( is_object($data) )
				$data->$k = tp_encode_product_data( $v );
			else
				$data[$k] = tp_encode_product_data( $v );
		}
		$data = serialize( $data );
	}
	
	return base64_encode( $data );
}

function tp_get_post_product_data( $post_id ) {
	$data = get_post_meta( $post_id, 'tp_product_data', true );
	if ( $data === '' )
		return false;
	return tp_decode_product_data( $data );
}

function tp_set_post_product_data( $post_id, $product ) {
	return update_post_meta( $post_id, 'tp_product_data', tp_encode_product_data( $product ) );
}

function tp_set_post_meta( $id, $pinfo, $preserve = false, $overwrites = array() ) {
	if( !is_array($overwrites) )
		$overwrites = array( $overwrites );
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
		if ( $preserve && !in_array( esc_attr( $name ), $overwrites ) && $oldmeta[$name] && $oldmeta[$name] != tp_strtopinfo( $o['value'], $oldinfo ) ) {
			$vars[$name] = $oldmeta[$name];
		} else {
			// update post meta
			$vars[$name] = tp_strtopinfo( $o['value'], $pinfo );
		}
	}
	update_post_meta( $id, 'tp_product_info', $vars );
	
	$custom_fields = isset( $meta['other_fields'] ) ? $meta['other_fields'] : array();
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