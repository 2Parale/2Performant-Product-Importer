<?php
/*
Plugin Name: 2Performant Product Importer
Plugin URI: http://blog.2parale.ro/wp-plugin-2performant-product-importer-en/
Description: Imports products from product feeds in 2Performant affiliate networks. It requires authentication as an affiliate in one of these networks. Products are imported as individual posts (or other custom post types, configurable) which can use several custom fields based on product info from the feeds.
Version: 0.10.2
Author: 2Parale
Author URI: http://www.2parale.ro/
License: GPL2
*/

//ini_set('display_errors', 1);
//error_reporting(E_ALL);
//define('SCRIPT_DEBUG', true);

define('TPPI_VERSION', 'v0.10.2');

if ( is_admin() ) :

	add_action( 'admin_menu', 'tp_plugin_menu' );


	function my_add_bitly_cpts( $post_types ) {
		$post_types[] = 'my_custom_feature';
		$post_types[] = 'my_other_post_type';
		return $post_types;
	}

	add_filter( 'bitly_post_types' , 'my_add_bitly_cpts' );

	function tp_plugin_menu() {
		global $tp_plugin_settings_page;
		$tp_plugin_settings_page = add_options_page( '2Performant Product Importer Settings', '2Performant Product Importer', 'manage_options', '2performant-product-importer', 'tp_plugin_settings' );
		add_action( 'admin_init', 'tp_register_settings' );

		$pt = tp_get_post_type();
		$pt = ($pt == 'post') ? '' : '?post_type='.$pt;
		$feed_page = add_submenu_page( 'edit.php'.$pt, 'Add a product from a feed', 'Add from feed', 'edit_posts', 'tp_product_add_from_feed', 'tp_product_add_from_feed' );
		$toolbox_page = add_submenu_page( 'edit.php'.$pt, 'Product toolbox', 'Product toolbox', 'edit_posts', 'tp_product_toolbox', 'tp_product_toolbox' );
		add_action( 'admin_print_scripts-'.$tp_plugin_settings_page, 'tp_add_settings_script' );
		add_action( 'admin_print_styles-'.$feed_page, 'tp_add_feed_stylesheet' );
		add_action( 'admin_print_scripts-'.$feed_page, 'tp_add_feed_script' );
		add_action( 'admin_print_styles-'.$toolbox_page, 'tp_add_toolbox_stylesheet' );
		add_action( 'admin_print_scripts-'.$toolbox_page, 'tp_add_toolbox_script' );

		add_action('contextual_help', 'tp_plugin_settings_help', 10, 3 );

		wp_register_style( 'tp-feed-style', plugins_url('2performant-product-importer/css/2p.css', dirname(__FILE__)));
		wp_register_style( 'jquery-ui-redmond', plugins_url('2performant-product-importer/css/redmond/jquery-ui-1.10.4.custom.css', dirname(__FILE__)));


		wp_register_script( 'jquery-ui-core',plugins_url('2performant-product-importer/js/jquery.ui.core.js', dirname(__FILE__)), array(), TPPI_VERSION, true );
		wp_register_script( 'jquery-ui-widget',plugins_url('2performant-product-importer/js/jquery.ui.widget.js', dirname(__FILE__)), array(), TPPI_VERSION, true );
		wp_register_script( 'jquery-ui-progressbar',plugins_url('2performant-product-importer/js/jquery.ui.progressbar.js', dirname(__FILE__)), array('jquery-ui-core','jquery-ui-widget'), TPPI_VERSION, true );


		wp_register_script( 'jquery-infinitescroll', plugins_url('2performant-product-importer/js/jquery.infinitescroll.min.js', dirname(__FILE__)), array(), TPPI_VERSION, true );
		wp_register_script( 'tp-jquery-product-list', plugins_url('2performant-product-importer/js/jquery.productlist.js', dirname(__FILE__)), array( 'jquery-infinitescroll' ), TPPI_VERSION, true );
		wp_register_script( 'tp-settings-script',  plugins_url('2performant-product-importer/js/settings.js', dirname(__FILE__)), array( ), TPPI_VERSION, true );
		wp_register_script( 'tp-feed-script', plugins_url('2performant-product-importer/js/feed.js', dirname(__FILE__)), array('tp-jquery-product-list', 'wp-lists' ), TPPI_VERSION, true );
		wp_register_script( 'tp-edit-script', plugins_url('2performant-product-importer/js/edit.js', dirname(__FILE__)), array(), TPPI_VERSION );
		wp_register_script( 'tp-insert-script',  plugins_url('2performant-product-importer/js/insert.js', dirname(__FILE__)), array(), TPPI_VERSION );
		wp_register_script( 'tp-listing-script', plugins_url('2performant-product-importer/js/listing.js', dirname(__FILE__)), array(), TPPI_VERSION, true );
		wp_register_script( 'tp-toolbox-script', plugins_url('2performant-product-importer/js/toolbox.js', dirname(__FILE__)), array('jquery-ui-progressbar' ), TPPI_VERSION, true );
		wp_register_script( 'tp-tinymce-insert-script',  plugins_url('2performant-product-importer/tinymce-insert/js/insert.js', dirname(__FILE__)), array('tp-jquery-product-list' ), TPPI_VERSION );
	}

	function tp_add_settings_script() {
		wp_enqueue_script('tp-settings-script');
	}

	function tp_add_feed_stylesheet() {
//	var_dump('add style');
		wp_enqueue_style('tp-feed-style');
	}

	function tp_add_feed_script() {
		wp_enqueue_script('tp-feed-script');
	}

	function tp_add_toolbox_stylesheet() {
		wp_enqueue_style('jquery-ui-redmond');
	}

	function tp_add_toolbox_script() {
		wp_enqueue_script('tp-toolbox-script');
	}

	function tp_get_post_type() {
		$pt = get_option('tp_options_add_feed', array('post_type' => 'post'));
		$pt = $pt['post_type'];

		return $pt;
	}

endif;

function tp_get_the_product_field( $key, $id = false ) {
	global $post;
	if ( $id === false )
		$id = $post->ID;
	$t = get_post_meta( $id, 'tp_product_info', true );

	return isset( $t[$key] ) ? $t[$key] : '';
}

function tp_the_product_field( $key, $id = false ) {
	echo tp_get_the_product_field( $key, $id );
}


//automatically create bit.ly url for wordpress widgets
function bitly($url)
{
	$api = get_option('tp_options_bitly_options');
	//login information
	$login = $api['login'];   //your bit.ly login
	$apikey = $api['api_key']; //add your bit.ly API
	$format = 'json';   //choose between json or xml
	$version = '2.0.1';
	//generate the URL
	$bitly = 'http://api.bit.ly/v3/shorten?version='.$version.'&longUrl='.urlencode($url).'&login='.$login.'&apiKey='.$apikey.'&format='.$format;

	//fetch url
	$response = file_get_contents($bitly);
	//for json formating
	$json = @json_decode($response,true);
	return $json['data']['url'];
}

function toASCII( $str )
{
	return strtr(utf8_decode($str),
		utf8_decode(
			'ŠŒŽšœžŸ¥µÀÁÂÃÄÅÆÇÈÉÊËÌÍÎÏÐÑÒÓÔÕÖØÙÚÛÜÝßàáâãäåæçèéêëìíîïðñòóôõöøùúûüýÿ'),
		'SOZsozYYuAAAAAAACEEEEIIIIDNOOOOOOUUUUYsaaaaaaaceeeeiiiionoooooouuuuyy');
}




function performant_fetch_shortcodes( $content ) {

	$content_shortcodes = get_post_meta(get_the_ID(), 'content_shortcodes', true);
	if($content_shortcodes) {
		if(addslashes($content_shortcodes) == $content) {
			return $content;
		}
	}

	$pattern = get_shortcode_regex();

	$new_content = '';

	if ( preg_match_all( '/'. $pattern .'/s', $content, $matches ) && array_key_exists( 2, $matches ) && in_array( 'tp_product', $matches[2] )) {
		$shortcodes = $matches[0];
		foreach($shortcodes as $shortcode) {
			$shortcode = str_replace('\\', '', $shortcode);

			$new_content .= do_shortcode($shortcode);
		}
	}

	update_post_meta(get_the_ID(), 'fetched_shortcodes', $new_content);
	update_post_meta(get_the_ID(), 'content_shortcodes', $content);

	return $content;
}

add_filter( 'content_save_pre', 'performant_fetch_shortcodes', 10, 1 );


function fetch_shortcodes($content) {
	$content_fetched = get_post_meta(get_the_ID(), 'fetched_shortcodes', true);
	if($content_fetched) {
		return $content_fetched;
	} else {
		performant_fetch_shortcodes($content);
		return $content;
	}
}

add_filter( 'the_content', 'fetch_shortcodes' );

function tp_product_shortcode( $atts ) {


	extract( shortcode_atts( array (
		'id' => false,
		'feed' => false,
		'short_link' => false,
		'save_photo' => false,
	), $atts ) );


	if( ! ( is_numeric( $id ) && is_numeric( $feed ) ) )
		return false;

	require_once( 'api.php' );

	$pinfo = tp_get_wrapper()->product_store_showitem( $feed, $id );

	if( empty( $pinfo ) || isset( $pinfo->error ) )
		return false;

	$image_options = get_option('tp_options_image_options');

	if($save_photo == true) {

		$pinfo->image_url = str_replace(' ', '%20', $pinfo->image_url);
		$size = getimagesize($pinfo->image_url);
		$ext = end(explode('/', $size['mime']));

		$upload_dir = wp_upload_dir();

		require_once(ABSPATH . 'wp-admin/includes/media.php');
		require_once(ABSPATH . 'wp-admin/includes/file.php');
		require_once(ABSPATH . 'wp-admin/includes/image.php');

		$image_name = $image_options['image_name'];

		$image_name = str_replace('%title%', $pinfo->title ,$image_name);
		$image_name = str_replace('%brand%', $pinfo->brand ,$image_name);
		$image_name = str_replace('%category%', $pinfo->category ,$image_name);
		$image_name = str_replace('%price%', $pinfo->price ,$image_name);
		$image_name = str_replace('%description%', $pinfo->description ,$image_name);
		$image_name = str_replace('%subcategory%', $pinfo->subcategory ,$image_name);
		$image_name = str_replace('%id_product%', $pinfo->prid ,$image_name);

		$image_name = strtolower(str_replace(' ', '-', $image_name));

		$image_name = str_replace([',', '.', '*', '"', '\'', '\\', '(', ')', ':', '?', '/', '!', '@', '#', '$', '%', '&', '+', '&nbsp;'], '', $image_name);
		//$image_name = preg_replace('/[^a-zA-Z0-9_\-]+$/', '', $image_name);
		//$image_name = preg_replace('/\W$/', '', $image_name);

		$image_name = iconv('UTF-8', 'ASCII//TRANSLIT', $image_name);
		$image_name = toASCII($image_name);
		//$image_name = iconv(mb_detect_encoding($image_name, mb_detect_order(), true), "UTF-8", $image_name);
		//$image_name = mb_convert_encoding($image_name, 'UTF-8');
		$image_name = $image_name . '.' . $ext;

		if(!file_exists($upload_dir['path'].'/'.$image_name)) {
			//$pinfo->image_url = $upload_dir['url'].'/'.$image_name;

			$data = file_get_contents($pinfo->image_url);
			file_put_contents($upload_dir['path'] . '/' . $image_name, $data);

			$html = media_sideload_image($upload_dir['url'] . '/' . $image_name, get_the_ID(), $image_name);

			//return $upload_dir['url'].'/'.$image_name;
			if (isset($html->errors)) {
				return false;
			}

			$dom = new DOMDocument;
			$dom->loadHTML($html);
			$image_url = $dom->getElementsByTagName('img')->item(0)->getAttribute('src');

			$url = parse_url(get_site_url());
			$path = explode($url['host'], $image_url);
			$image_path = $_SERVER['DOCUMENT_ROOT'] . $path[1];

			//$image_path = $upload_dir['path'].'/'.$image_name;

			/* Resizing */
			$size['width'] = $size[0];
			$size['height'] = $size[1];
			if ($size['width'] > $image_options['max_image_width'] || $size['height'] > $image_options['max_image_height']) {
				$widthRatio = $image_options['max_image_width'] / $size['width'];
				$heightRatio = $image_options['max_image_height'] / $size['height'];
				$ratio = min($widthRatio, $heightRatio);
				$newWidth = (int)$size['width'] * $ratio;
				$newHeight = (int)$size['height'] * $ratio;

				$image_p = imagecreatetruecolor($newWidth, $newHeight);

				if ($ext == 'jpg' || $ext == 'jpeg')
					$image = imagecreatefromjpeg($image_url);
				else if ($ext == 'png')
					$image = imagecreatefrompng($image_url);

				imagecopyresampled($image_p, $image, 0, 0, 0, 0, $newWidth, $newHeight, $size['width'], $size['height']);

				if ($ext == 'jpg' || $ext == 'jpeg')
					imagejpeg($image_p, $image_path);
				else if ($ext == 'png')
					imagepng($image_p, $image_path);

			}
			/* END Resizing */

		}

		$pinfo->image_url = $upload_dir['url'].'/'.$image_name;

	}

	if($short_link == true) {
		$pinfo->aff_link = bitly('http:'.$pinfo->aff_link);
	}

	$template = get_option( 'tp_options_templates' );

	if( $template === false) {
		ob_start();
		?>
		<div class="tp-product-info">
			<?php if( isset($pinfo->image_url )) : ?>
				<div class="tp-product-thumbnail">
					<a href="<?php esc_attr( $pinfo->aff_link ); ?>">
						<img src="<?php echo esc_attr( $pinfo->image_url ); ?>" />
					</a>
				</div>
			<?php endif; ?>
			<div class="tp-product-meta">
				<span class="tp-product-brand"><?php echo esc_attr( $pinfo->brand ); ?></span>
				<span class="tp-product-title"><?php echo esc_attr( $pinfo->title ); ?></span>
				<span class="tp-product-price"><?php echo esc_attr( $pinfo->price ); ?></span>
			</div>
		</div>
		<?php
		$html = ob_get_contents();

		ob_end_clean();
	} else {
		$template = $template['template'];

		$html = tp_strtopinfo( $template, $pinfo );
	}

	return $html;
}
add_shortcode( 'tp_product', 'tp_product_shortcode' );

include_once 'actions.php';
include_once 'settings.php';
include_once 'edit-page-boxes.php';
include_once 'add-from-feed.php';
include_once 'toolbox.php';
include_once 'listing.php';
include_once 'edit-page-button.php';

?>
