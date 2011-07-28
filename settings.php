<?php
if ( is_admin() ) :

function _tp_get_settings( $section = false, $setting = false ) {
	$settings = array(
		'add_feed' => array(
			'label' => 'When adding from a feed',
			'settings' => array(
				'post_type' => array(
					'type' => 'custom',
					'callback' => 'tp_render_post_type_select',
					'label' => 'Destination post type',
					'description' => 'Will products be posts, pages or another kind of custom posts',
					'options' => get_post_types( array( 'public' => true ), 'objects' ),
					'default' => 'post'
				),
				'post_status' => array(
					'type' => 'select',
					'label' => 'Default post status',
					'description' => 'The state of the post immediately after adding it from a feed',
					'options' => array(
						'draft' => 'Draft',
						'publish' => 'Published'
					),
					'default' => 'draft'
				),
				'post_title' => array(
					'type' => 'text',
					'label' => 'Destination title structure',
					'description' => 'What the post title will be comprised of',
					'default' => '%brand% %title%'
				),
				'post_content' => array(
					'type' => 'text',
					'label' => 'Destination content structure',
					'description' => 'What the post content will look like',
					'default' => '%description%'
				),
				'post_featured_image' => array(
					'type' => 'checkbox',
					'label' => 'Destination featured image',
					'description' => 'Download product main image (if available) and use it as post featured image',
					'default' => true
				),
//				'post_gallery' => array(
//					'type' => 'checkbox',
//					'label' => 'Destination post gallery',
//					'description' => 'Download all product images (if available) and attach them to newly created post',
//					'default' => true
//				),
				'update_batch_size' => array(
					'type' => 'number',
					'label' => 'Update batch size',
					'description' => 'The maximum number of products to update in a single step',
					'default' => 50
				),
				'trash_expired' => array(
					'type' => 'checkbox',
					'label' => 'Trash expired products',
					'description' => 'Check this to send posts containing products which are no longer available to trash when updating all products',
					'default' => true
				)
			)
		),
		'fields' => array(
			'label' => 'Metadata to get when mass-importing',
			'settings' => array(
				'fields' => array(
					'type' => 'custom',
					'label' => 'Product fields',
					'callback' => 'tp_render_options_fields_fields'
				),
				'other_fields' => array(
					'type' => 'custom',
					'callback' => 'tp_render_options_fields_other_fields',
					'label' => 'Other custom fields'
				)
			)
		),
		'templates' => array(
			'label' => 'Inserting products into post',
			'settings' => array(
				'template_list' => array(
					'type' => 'custom',
					'label' => 'Templates',
					'callback' => 'tp_render_options_templates_fields',
					'default' => array(
						'template1' => array(
							'type' => 'textarea',
							'label' => 'Template 1',
							'value' => '<div class="tp-product-info">
	<div class="tp-product-thumbnail">
		<a href="%aff_link%">
			<img src="%image_url%" />
		</a>
	</div>
	<div class="tp-product-meta">
		<span class="tp-product-brand">%brand%</span>
		<span class="tp-product-title">%title%</span>
		<span class="tp-product-price">%price%</span>
	</div>
</div>'
						),
					)
				),
				'default_template' => array(
					'type'	=> 'hidden',
					'default' => 'template1'
				),
			),
		),
		'connection' => array(
			'label' => 'Connection settings',
			'settings' => array(
				'network' => array(
					'type' => 'text',
					'label' => 'Network API URL',
					'description' => 'E.g. http://api.network.com'
				),
				'username' => array(
					'type' => 'text',
					'label' => 'Username'
				),
				'password' => array(
					'type' => 'password',
					'label' => 'Password'
				),
				'connection_timeout' => array(
					'type' => 'number',
					'label' => 'Connection timeout',
					'description' => 'Time, in seconds, after which the connection to the API server is abandoned. Do not change unless you know what you are doing.',
					'default' => 10
				),
				'timeout' => array(
					'type' => 'number',
					'label' => 'Transfer timeout',
					'description' => 'Total number of seconds each API request can take. Use 0 for no limit, should be greater than connection timeout if set. Do not change unless you know what you are doing.',
					'default' => 0
				),
				'adapter' => array(
					'type' => 'select',
					'label' => 'Connection adapter',
					'description' => 'The connection adapter class. Do not change unless you know what you are doing.',
					'options' => array(
						'curl' => 'cURL',
						'socket' => 'Socket'
					),
					'default' => 'socket'
				)
			)
		),
		'cache' => array(
			'label' => 'Caching settings',
			'settings' => array(
				'enabled' => array(
					'type' => 'checkbox',
					'label' => 'Enable caching',
					'description' => 'Enable via a third party persistent object caching plugin such as W3 Total Cache'
				)
			)
		)
	);
	
	if ( $section !== false ) 
	if ( isset( $settings[$section] ) ) {
		$settings = $settings[$section];
		
		if( $setting !== false )
		if ( isset( $settings['settings'][$setting] ) ) {
			$settings = $settings['settings'][$setting];
		} else {
			return false;
		}
	} else {
		return false;
	}
	
	return $settings;
}

function _tp_get_default( $section, $setting, $default = false ) {
	$res = _tp_get_settings( $section, $setting );
	if( $res && is_array( $res ) ) {
		if( isset( $res['default'] ) )
			return $res['default'];
		else
			return $default;
	}
	
	return false;
}

function tp_register_settings() {
	$settings = _tp_get_settings();
	
//	register_setting( 'tp-options-group', 'tp_options_post_type' );
//	register_setting( 'tp-options-group', 'tp_options_fields' );
	
	foreach ( $settings as $section_id => $section ) {
		register_setting( 'tp-options-group', "tp_options_{$section_id}" );
		add_settings_section( "tp_options_{$section_id}", __( $section['label'], 'tppi' ), 'tp_section_callback', 'tp-options' );
		$values = get_option( "tp_options_{$section_id}" );
		$defaults = ( $values === false );
		if ( $values === false ) {
			$values = array();
		}
		foreach ( $section['settings'] as $setting_id => $setting ) {
			$setting['id'] = "tp_options_{$section_id}_{$setting_id}";
			$setting['name'] = "tp_options_{$section_id}[{$setting_id}]";
			$setting['value'] = isset( $values[$setting_id] ) ? $values[$setting_id] : ( $values[$setting_id] = isset( $setting['default'] ) ? $setting['default'] : null );
			add_settings_field( "tp_options_{$section_id}_{$setting_id}", __( $setting['label'], 'tppi' ), 'tp_render_field', 'tp-options', "tp_options_{$section_id}", $setting );	
		}
		if ( $defaults )
			update_option( "tp_options_{$section_id}", $values );
	}
}

function tp_section_callback() {}

function tp_render_field( $setting ) {
	extract( $setting );
	$type = empty( $type ) ? 'text' : $type;
	$class = isset( $class ) ? $class : array();
	$class = is_array( $class ) ? $class : array ( $class );
	
	$output = '';
	switch ( $type ) {
		case 'select':
			if ( isset( $options ) && is_array( $options ) ) {
				foreach ( $options as $key => $option ) {
					$selected = $key == $value ? 'selected="selected"' : '';
					$output .= "<option value='".esc_attr($key)."' {$selected}>" . esc_attr( $option ) . "</option>";
				}
			}
			$class = implode( ' ', $class );
			$output = "<select name='" . esc_attr( $name ) . "' id='" . esc_attr( $id ) . "' class='".esc_attr( $class ) . "'>$output</select>";
			break;
		case 'text':
		case 'number':
		case 'password':
			$class = array_merge( $type == 'number' ? array( 'small-text' ) : array ( 'regular-text' ), $class );
			$class = implode( ' ', $class );
			$output = "<input type='" . esc_attr( $type == 'number' ? 'text' : $type ) . "' class='" . esc_attr( $class ) . "' name='" . esc_attr( $name ) . "' id='" . esc_attr( $id ) . "' value='" . esc_attr( $value ) . "' />";
			break;
		case 'hidden':
			$output = "<input type='hidden' name='" . esc_attr( $name ) . "' id='" . esc_attr( $id ) . "' value='" . esc_attr( $value ) . "' />";
			break;
		case 'textarea':
			$class = array_merge( array ( 'large-text', 'code' ), $class );
			$class = implode( ' ', $class );
			$output = "<textarea rows='10' cols='50' class='" . esc_attr( $class ) . "' name='" . esc_attr( $name ) . "' id='" . esc_attr( $id ) . "'>" . esc_attr( $value ) . "</textarea>";
			break;
		case 'checkbox':
			$class = implode( ' ', $class );
			$output = "<input type='checkbox' class='" . esc_attr( $class ) . "' name='" . esc_attr( $name ) . "' id='" . esc_attr( $id ) . "' ".((boolean)($value) ? "checked='checked'" : "")." />";
			break;
		case 'custom':
			if ( isset( $callback ) ) {
				$output .= call_user_func( $callback, $setting );
			}
			break;
		default:
			trigger_error( 'Invalid setting type', E_WARNING );
	}
	
	if ( isset( $description ) )
		$output .= " <span class='description'>" . esc_attr( $description ) . "</span>";
	
	echo $output;
}

function tp_render_post_type_select( $setting ) {
	extract( $setting );
	$type = empty( $type ) ? 'text' : $type;
	$class = isset( $class ) ? $class : array();
	$class = is_array( $class ) ? $class : array ( $class );
	
	if ( isset( $options ) && is_array( $options ) ) {
		foreach ( $options as $option ) {
			$selected = $option->name == $value ? 'selected="selected"' : '';
			$output .= "<option value='{$option->name}' {$selected}>{$option->label}</option>";
		}
	}
	$class = implode( ' ', $class );
	$output = "<select name='$name' id='$id' class='$class'>$output</select>";
	
	return $output;
}

$field_names = array(
	'brand',
	'price',
	'product_store_id',
	'category',
	'created_at',
	'subcategory',
	'delta',
	'title',
	'campaign_id',
	'updated_at',
	'url',
	'id',
	'other_data',
	'update_type',
	'caption',
	'clicks',
	'promoted',
	'unique_code',
	'description',
	'prid',
	'active',
	'image_url',
	'image_urls',
	'aff_link'
);

function tp_render_options_fields_fields( $setting ) {
	extract($setting);
	if ( ! $value )
		$value = array(
			'brand' => array(
				'type' => 'text',
				'label' => 'Product brand name',
				'value' => '%brand%'
			),
			'title' => array(
				'type' => 'text',
				'label' => 'Product name',
				'value' => '%title%'
			),
			'description' => array(
				'type' => 'textarea',
				'label' => 'Product description',
				'value' => '%description%'
			),
			'price' => array(
				'type' => 'text',
				'label' => 'Product price',
				'value' => '%price%'
			),
			'aff_link' => array(
				'type' => 'text',
				'label' => 'Affiliate link',
				'value' => '%aff_link%'
			),
		);
		foreach ( $value as $kk => $vv ) {
			$value[$kk]['selectable_type'] = true;
		}
?>
	<p class="description"><?php printf( __( 'See <a href="#" id="%1$s">help section</a> above for details', 'tppi' ), 'tp_options_fields_fields_help' ); ?></p>
	<script type="text/javascript"><!--//<![CDATA[
		var tp_options_fields_fields = <?php echo json_encode( $value ); ?>,
			tp_options_fields_fields_name = '<?php echo $name?>';
	//]]--></script><a name="tp_options_fields_fields" id="tp_options_fields_fields_anchor"></a>
<?php
	//*
	echo "<table id='tp_fields_fields' class='fields'>";
	echo "<tr class='head'><th></th><th scope='column'>" . __( 'Key', 'tppi' ) . "</th><th scope='column'>" . __( 'Value', 'tppi' ) . "</th></tr>";
	foreach ( $value as $i => $p ) {
		echo "<tr class='product_field tp_$i'><th scope='row'>" . $p['label'] . "</th><td class='product_field_key'><em>" . esc_attr( $i ) . "</em></td><td class='product_field_value'>" . esc_attr( $p['value'] );
		foreach ( $p as $j => $v ) {
			echo "<input type='hidden' name='{$name}[{$i}][{$j}]' id='{$id}_{$i}_{$j}' value='$v' />";
		}
		echo "</td></tr>";
	}
	echo "</table>";
	//*/
}


function tp_render_options_templates_fields( $setting ) {
	extract($setting);
	if ( ! $value )
		$value = tp_get_option( 'templates', 'template_list' );
		
//		foreach ( $value as $kk => $vv ) {
//			$value[$kk]['selectable_type'] = true;
//		}
?>
		<p class="description">Key of any template should not contain whitespaces. See <a href="#" id="tp_options_templates_help">help section</a> for more details about templates.</p>

		<script type="text/javascript"><!--//<![CDATA[
		var tp_options_templates_list = <?php echo json_encode( $value ); ?>,
			tp_options_templates_list_name = '<?php echo $name?>';
	//]]--></script><a name="tp_options_templates_list" id="tp_options_templates_list_anchor"></a>
<?php
	/*
	echo "<table id='tp_templates' class='fields'>";
	echo "<tr class='head'><th scope='column'>" . __( 'Template name', 'tppi' ) . "</th><th scope='column'>" . __( 'Template output', 'tppi' ) . "</th></tr>";
	foreach ( $value as $i => $p ) {
		echo "<tr class='product_field tp_$i'><th scope='row'>" . $p['label'] . "</th><td class='product_field_value'><textarea rows='10' cols='80'>" . esc_attr( $p['value'] ) . "</textarea>";
		foreach ( $p as $j => $v ) {
			echo "<input type='hidden' name='{$name}[{$i}][{$j}]' id='{$id}_{$i}_{$j}' value='" . esc_attr( $p['value'] ) . "' />";
		}
		echo "</td></tr>";
	}
	echo "</table>";
	*/
}

function tp_render_options_fields_other_fields( $setting ) {
	extract($setting);
	if ( ! $value )
		$value = array();
?>
	<p class="description"><?php printf( __( 'See <a href="#" id="%1$s">help section</a> above for details', 'tppi' ), 'tp_options_fields_other_fields_help' ); ?></p>
	<script type="text/javascript"><!--//<![CDATA[
		var tp_options_fields_other_fields = <?php echo json_encode( $value ); ?>,
			tp_options_fields_other_fields_name = '<?php echo $name?>';
	//]]--></script><a name="tp_options_fields_other_fields" id="tp_options_fields_other_fields_anchor"></a>
<?php
	//*
	echo "<table id='tp_fields_other_fields' class='fields'>";
	echo "<tr class='head'><th></th><th scope='column'>" . __( 'Key', 'tppi' ) . "</th><th scope='column'>" . __( 'Value', 'tppi' ) . "</th></tr>";
	foreach ( $value as $i => $p ) {
		echo "<tr class='product_field tp_$i'><th scope='row'>" . $p['label'] . "</th><td class='product_field_key'><em>" . esc_attr( $i ) . "</em></td><td class='product_field_value'>" . esc_attr( $p['value'] );
		foreach ( $p as $j => $v ) {
			echo "<input type='hidden' name='{$name}[{$i}][{$j}]' id='{$id}_{$i}_{$j}' value='$v' />";
		}
		echo "</td></tr>";
	}
	echo "</table>";
	//*/
}

function tp_plugin_settings() {
	include_once 'api.php';
	
	if ( ! current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	$errors = tp_verify_connection();
	if( tp_using_cache() )
		$errors += tp_verify_cache();
	
?>
<style type="text/css">
table.tp-field-table th {
	font-weight: bold;
}
table.tp-field-table textarea {
	height: 100px;
}
</style>
<div class="wrap">
	<?php if ( function_exists( 'screen_icon' ) ) screen_icon(); ?><h2>2Performant Product Importer</h2>
<?php if ( ! empty( $errors ) ) : ?>
	<div id="setting-error-options_error" class="error settings-error">
<?php foreach ( $errors as $e ) : ?>
		<p><?php _e( $e, 'tppi' ); ?></p>
<?php endforeach; ?>
	</div>
<?php endif; ?>
	<form name="form" action="options.php" method="post">
	<?php settings_fields( 'tp-options-group' ); ?>
	<?php do_settings_sections('tp-options'); ?>
	<p class="submit">
		<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	</p>
	</form>
</div><?php
}

function tp_plugin_settings_help( $contextual_help, $screen_id, $screen ) {
	global $tp_plugin_settings_page;
	global $field_names;
	
	if ( $screen_id != $tp_plugin_settings_page )
		return $contextual_help;
	
	$desc = sprintf( __( 'The new %1$s from %2$s: %3$s', 'tppi' ), '%title%', '%brand%', '%caption%' );
	$result = sprintf( __( 'The new %1$s from %2$s: %3$s', 'tppi' ), 'men\'s sneakers', 'Nike', 'Now on sale!' );
	ob_start();
	
?>
	<p><?php _e( 'This is your command center for the 2Performant Product Importer.' ); ?></p>
	<h3><?php _e( 'When adding from a feed', 'tppi' ); ?></h3>
	<p><strong><?php _e( 'Destination post type', 'tppi' ); ?></strong> - <?php printf( __( 'Wordpress supports <a href="%1$s" target="_blank">Custom Post Types</a>, which you can use to create your own custom post type (e.g. %2$s) to import and show off products. Or you can opt for the built-in post types.', 'tppi' ), 'http://codex.wordpress.org/Custom_Post_Types', '<code>product</code>' ); ?></p>
	<p><strong><?php _e( 'Default post status', 'tppi' ); ?></strong> - <?php _e( 'When you import a product from a feed you can have the destination post wait for you to publish it or you can immediately send it to your target audience.', 'tppi' ); ?></p>
	<p><strong><?php _e( 'Update batch size', 'tppi' ); ?></strong> - <?php _e( 'When you are updating the products, loading too many at once could fill up your server memory. Therefore, you can select how many products should be updated at once by customizing this setting.', 'tppi' ); ?></p>
	<h3><?php _e( 'Metadata to get when mass-importing', 'tppi' ); ?></h3>
	<p><strong><?php _e( 'Product fields', 'tppi' ); ?></strong> - <?php printf( __('These are the fields you can use in your custom theme. The syntax is %1$s, where %2$s is the WordPress field name and %3$s is the value of the product info field from 2Performant.', 'tppi'), "<code>tp_the_product_field( 'wp-key' )</code>", '<code>wp-key</code>', '<code>%product-key%</code>' ); ?></p>
	<p><strong><?php _e( 'Other custom fields', 'tppi' ); ?></strong> - <?php printf( __( '<a href="%1$s" target="_blank">Custom fields</a> that can originally be set by other plugins, but whose values you want to override. For example you could set %2$s\'s %3$s custom field to something like %4$s in order to get %5$s.', 'tppi'), 'http://codex.wordpress.org/Custom_Fields', "<a href='http://wordpress.org/extend/plugins/all-in-one-seo-pack/' target='_blank'>All in One SEO Pack</a>", '<code>_aioseop_description</code>', '<code>'.$desc.'</code>', '<em>'.$result.'</em>' ); ?></p>
	<p><?php echo sprintf( __('Possible product info fields are: %1$s.', 'tppi'), '<code>%' . implode( '%</code>, <code>%', $field_names ) . '%</code>' ); ?></p>
	<h3><?php _e( 'Inserting products into post', 'tppi' ); ?></h3>
	<p><strong><?php _e( 'User-defined output template', 'tppi' ); ?></strong> - <?php printf( __('This defines how the products inserted into the post via the button on the <abbr title="What You See Is What You Get">WYSIWYG</abbr> (visual) editor will look like. You can use the product info fields mentioned above using the %1$s syntax described above.', 'tppi'), '<code>%product-info-field%</code>' ); ?></p>
	<h3><?php _e( 'Connection settings', 'tppi' ); ?></h3>
	<p><strong><?php _e( 'Network API URL', 'tppi' ); ?></strong> - <?php printf( __( 'If you\'re unsure, ask your affiliate network operator for this. It\'s usually %1$s, where %2$s is your network.', 'tppi' ), '<code>api.network.com</code>', '<code>network.com</code>' ); ?></p>
	<p><strong><?php _e( 'Username', 'tppi' ); ?> &amp; <?php _e( 'Password', 'tppi' ); ?></strong> - <?php _e( 'Self explanatory.', 'tppi' ); ?></p>
	<p><strong><?php _e( 'Connection timeout', 'tppi' ); ?> &amp; <?php _e( 'Transfer timeout', 'tppi' ); ?></strong> - <?php _e( 'The amount of time, in seconds, after which the connection to the API server and the data transfer from the API server respectively should be dropped. If you don\'t know what this means, you should probably stick to the default values.', 'tppi' ); ?></p>
	<p><strong><?php _e( 'Connection adapter', 'tppi' ); ?></strong> - <?php _e( 'Which HTTP client implementation to use when connecting to the API server.', 'tppi' ); ?></p>
	<h3><?php _e( 'Connection settings', 'tppi' ); ?></h3>
	<p><strong><?php _e( 'Enable caching', 'tppi' ); ?></strong> - <?php printf( __( 'Whether to use a persistent object caching mechanism provided by another plugin, in order to speed up repetitive requests to the API server, such as getting the available feed list. %1$s is an excellent choice for the matter at hand.', 'tppi' ), '<a href="http://wordpress.org/extend/plugins/w3-total-cache/" target="_blank">W3 Total Cache</a>' ); ?></p>
	<p></p>
	<p><strong><?php _e( 'For more information' ); ?></strong></p>
	
<?php
	
	$contextual_help = ob_get_contents() . $contextual_help;
	ob_end_clean();
	
	return $contextual_help;
}

endif;

function tp_get_option( $group, $name, $default = null ) {
	if( is_null($default) )
		$default = _tp_get_default( $group, $name );
	$option = get_option( sprintf( 'tp_options_%s', $group ), array( $name => $default ) );
	if( !is_array($option) )
		$option = array( $name => $default );
	if( !isset($option[$name]) )
		$option = array( $name => $default );
	return $option[$name];
}

function tp_set_option( $group, $name, $value ) {
	$option = get_option( sprintf( 'tp_options_%s', $group ), array( $name => $value ) );
	if( !is_array($option) )
		$option = array( $name => $value );
	if( !isset($option[$name]) )
		$option[$name] = $value;
	
	update_option( sprintf( 'tp_options_%s', $group ), $option );
}

function tp_unset_option( $group, $name ) {
	$option = get_option( sprintf( 'tp_options_%s', $group ), false );
	if( !is_array($option) )
		return false;
	if( !isset($option[$name]) )
		return false;
	
	unset( $option[$name] );
	update_option( sprintf( 'tp_options_%s', $group ), $option );
}

?>