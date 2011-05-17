<?php 

add_action( 'admin_init', 'tp_plugin_upgrade_settings' );

function tp_plugin_upgrade_settings() {
	$oldversion = get_option( 'tp_plugin_version' );
	if( $oldversion === false ) {
		$oldversion = 'v0.9';
	}
	update_option( 'tp_plugin_version', TPPI_VERSION );
	
	// OLDER THAN 1.0
	
	if( strcmp( $oldversion, 'v1.0') < 0 ) { // if older than 1.0
		
		
		// create default insert-from-feed HTML template
		// based on old user input from when there were no multiple templates
		
		$oldtemplate = tp_get_option( 'templates', 'template', '' );
		if( $oldtemplate ) {
			$template_list = tp_get_option( 'templates', 'template_list' );
			$template_list = array_merge( $template_list, array(
				'user-defined' => array(
					'type' => 'textarea',
					'label' => __( 'User defined', 'tppi' ),
					'value' => $oldtemplate
				)
			) );
			tp_set_option( 'templates', 'template_list', $template_list );
			tp_set_option( 'templates', 'default_template', 'user-defined' );
			tp_unset_option( 'templates', 'template' );
		}

		
	}
}

?>