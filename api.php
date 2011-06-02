<?php
$pear_dir = WP_PLUGIN_DIR . '/' . str_replace( basename( __FILE__ ), '', plugin_basename( __FILE__ ) ) . 'api';
set_include_path( get_include_path() . PATH_SEPARATOR . $pear_dir . PATH_SEPARATOR . $pear_dir . '/PEAR' );
include_once 'api/2performant.php';
include_once 'cache.php';

function tp_get_wrapper() {
	global $tp;
	
	if ( ! isset( $tp ) ) {
		if( tp_using_cache() ) {
			$tp = new CachedAPI();
		} else {
			$err = tp_verify_connection( $tp );
			if( !empty($err) )
				return false;
		}
	}
	
	return $tp;
}

function tp_verify_connection( &$tp = false ) {
	
	$connected = false;
	$errors = array();
	$connection = get_option( 'tp_options_connection' );
	if ( ! ( $connection['network'] && $connection['username'] && $connection['password'] ) ) {
		$errors[] = 'Please define connection parameters';
	} else {
		try {
			$api_options = array_merge( array( 'connection_timeout' => 10, 'timeout' => 0, 'adapter' => 'curl' ), $connection );
			$config = array( 'HTTP_Request2_config' => array(
				'connect_timeout' => $api_options['connection_timeout'],
				'timeout' => $api_options['timeout'],
				'adapter' => 'HTTP_Request2_Adapter_' . ucfirst($api_options['adapter'])
			) );
			$tp = new TPerformant( 'simple', array( "user" => trim( $connection['username'] ), "pass" => trim( $connection['password'] ) ), trim( $connection['network'] ), $config );
			
			try {
				$response = $tp->user_loggedin();
			} catch( Exception $e ) {
				$error = $e->getMessage();
//				$errors[] = $error;
				function starts_with( $haystack, $needle ) { return substr( $haystack, 0, strlen( $needle ) ) == $needle; }
				if ( starts_with( $error, 'Unavailable server. Response code: 401' ) ) {
					$errors[] = 'Invalid username and password';
				} elseif ( starts_with( $error, 'Unavailable server. Response code: ' ) ) {
					$errors[] = 'Invalid API URL';
				}
			}
			if( isset( $response ) && $response ) {
				if ( is_object( $response ) ) {
					$connected = true;
					
					if ( ! ( isset( $response->role ) && $response->role == 'affiliate' ) )
						$errors[] = 'Logged in user is not an affiliate';
				} else {
					$errors[] = 'Invalid API response. Please verify settings and contact affiliate network administrator.';
					ob_start();
					print_r( $response );
					trigger_error( 'API response: ' . ob_get_contents(), E_USER_WARNING );
					ob_end_clean();
				}
			}
		} catch( HTTP_Request2_Exception $e ) {
			$errors[] = 'API connection error: ' . $e->getMessage();
		} catch( Exception $e ) {
			$errors[] = get_class($e) . ': ' . $e->getMessage();
		}
	}
	return $errors;
}

function compareByName( $a, $b ){
		return ($a->name == $b->name) ? 0 : ( ( $a->name < $b->name ) ? -1 : 1 ) ;
}
function compareById($a, $b){
	return ($a->id == $b->id) ? 0 : ( ( $a->id < $b->id ) ? -1 : 1 ) ;
}

function tp_feed_dropdown( $options = array() ) {
	$options['value'] = isset( $options['value'] ) ? $options['value'] : '';
	$buffer = '';
	{
		$buffer .= "<select ".
			(isset( $options['name'] ) ? "name='{$options['name']}'" : '') ." ".
			(isset( $options['id'] ) ? "id='{$options['id']}'" : '') ." ".
			(isset( $options['class'] ) ? "class='{$options['class']}'" : '')
			.">";
		$buffer .= '<option value="all">'.__( 'All', 'tppi' ).'</option>';
		$tp = tp_get_wrapper();
		$campaigns = fix_result($tp->campaigns_listforaffiliate());
		
		// TODO: uncomment mai jos pentru categorii de campanii
//		$categories = array();
//		foreach ( $campaigns as $campaign ) {
//			if ( ! isset( $categories[$campaign->category] ) )
//				$categories[$campaign->{'category-id'}] = array();
//			$categories[$campaign->{'category-id'}][] = $campaign;
//		}
//		usort($categories);
	
		$categories = array($campaigns);
		
		foreach ( $categories as $category_name => $category ) :
			usort( $category, 'compareByName' );
			/*
			$buffer .= "<optgroup label='$category_name'>";
			//*/	
			foreach($category as $campaign) :
				$feeds = fix_result($tp->product_stores_list($campaign->id));
				if(!empty($feeds))
					$buffer .= "<option value='c_{$campaign->id}' class='level-0'".($options['value'] == "c_{$campaign->id}" ? ' selected="selected"' : '').">".htmlspecialchars($campaign->name)."</option>";
				usort($feeds, 'compareById');
				foreach($feeds as $feed) :
					$buffer .= "<option value='f_{$feed->id}' class='level-1'".($options['value'] == "f_{$feed->id}" ? ' selected="selected"' : '').">&nbsp;&nbsp;&nbsp;".htmlspecialchars($feed->name)."</option>";
				endforeach;
			endforeach;
			/*
			$buffer .= '</optgroup>';
			//*/
		endforeach;
		
		$buffer .= '</select>';
	}
	return $buffer;
}

function tp_campaign_dropdown( $options = array() ) {
	$options['value'] = isset( $options['value'] ) ? $options['value'] : '';
	$buffer = "<select ".
		(isset( $options['name'] ) ? "name='{$options['name']}'" : '').' '.
		(isset( $options['id'] ) ? "id='{$options['id']}'" : '').' '.
		(isset( $options['class'] ) ? "class='{$options['class']}'" : '')
		.">";
	$buffer .= '<option>'.__( 'Select a campaign', 'tppi' ).'</option>';
	$tp = tp_get_wrapper();
	$campaigns = fix_result($tp->campaigns_listforaffiliate());
	
	// TODO: uncomment mai jos pentru categorii de campanii
//	$categories = array();
//	foreach ( $campaigns as $campaign ) {
//		if ( ! isset( $categories[$campaign->category] ) )
//			$categories[$campaign->{'category-id'}] = array();
//		$categories[$campaign->{'category-id'}][] = $campaign;
//	}
//	ksort($categories);

	$categories = array($campaigns);
	foreach ( $categories as $category_name => $category ) :
		usort( $category, 'compareByName' );
		/*
		$buffer .= "<optgroup label='{$category_name}'>";
		//*/	
		foreach($category as $campaign) :
			$buffer .= "<option value='{$campaign->id}' class='level-0'".
				($options['value'] == $campaign->id ? ' selected="selected"' : '').'>'.
				htmlspecialchars($campaign->name).
				'</option>';
		endforeach;
		/*
		$buffer .= '</optgroup>';
		//*/
	endforeach;
	$buffer .= '</select>';
	
	return $buffer;
}

function fix_result($object) {
	if(!is_array($object))
		if(!empty($object))
			$object = array($object);
		else
			$object = array();
	return $object;
}

?>