<?php

/*
Plugin Name: WP Travel
Description: Get Arrivals and Departures from your favorite station
Version: 1.0
Author: Peter Persson
Author URI: https://github.com/AllSecretsKnown/wp_travel
*/
define( 'WP_TRAVEL_VERSION', '1.0' );

include_once dirname( __FILE__ ) . '/includes/Exttravlr/travlr.php';
include_once dirname( __FILE__ ) . '/includes/wp_travlr.php';
include_once dirname( __FILE__ ) . '/widgets/wp_travel_widget.php';

//if in admin, Instantiate the plugin
if ( is_admin() ) {
	new WpTravlr( __FILE__ );
}

