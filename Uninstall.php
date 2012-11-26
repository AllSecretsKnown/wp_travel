<?php

//This file is responsible for cleaning up the WP-DB when the plugin is being Uninstalled

//If Uninstall is not called by WP, exit!
//Else clean up Options-table in WP-DB
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit();
} else {
	delete_option( 'wp_travel_options' );
}