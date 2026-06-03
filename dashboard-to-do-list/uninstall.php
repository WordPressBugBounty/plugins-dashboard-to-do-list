<?php

if( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit();

// Legacy options
delete_option( 'ardtdw-checkbox' );
delete_option( 'ardtdw-checkbox-editor' );
delete_option( 'ardtdw-checkbox-admineditor' );
// Current options
delete_option( 'ardtdw-textarea' );
delete_option( 'ardtdw-position' );
delete_option( 'ardtdw-color' );
delete_option( 'ardtdw-roles-dashboard' );
delete_option( 'ardtdw-roles-frontend' );
