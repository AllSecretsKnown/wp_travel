<?php

class WpTravlr{

	private $wp_travel_ttl;

	public function __construct( $file = "" ) {
		$options = get_option('wp_travel_options');
		$this->wp_travel_ttl = $options['wp_travel_ttl'];

		//Hook into WP init and menu with our functions to render our stuff
		add_action( 'admin_init', array( &$this, 'wp_travel_init' ) );
		add_action( 'admin_menu', array( &$this, 'wp_travel_menu' ) );

		if ( $file != "" ) {
			//Make sure we initialize/sanitize the DB when activating/deactivating
			register_activation_hook( $file, array( &$this, 'wp_travel_add_defaults' ) );
			register_deactivation_hook( $file, array( &$this, 'wp_travel_delete_plugin_options' ) );
		}
	}

	public function get_wp_travel_ttl() {
		return $this->wp_travel_ttl;
	}

	/*
	 * Function to register oour settings with WP
	 */
	public function wp_travel_init() {
		//Params for register settings: optiongroup- a settings group name, option name, sanitize callback
		register_setting( 'wp_travel_options', 'wp_travel_options', array( &$this, 'oc_connection_validate_options' ) );
		add_settings_section('wp_travel_main', 'Wp Travel Settings', array(&$this, 'wp_travel_section_text'), 'wp_travel');
		add_settings_field( 'wp_travel_ttl', 'Enter TTL in seconds', array(&$this, 'wp_travel_setting_input'), 'wp_travel', 'wp_travel_main');
	}

	/*
	 * Function to ad a Admin menu
	 */
	public function wp_travel_menu() {
		// Menu page
		//Params page_title, menu_title, capability, menu_slug, callback function name, icon url, position
		add_menu_page( 'WP Travel Settings', 'WP Travel', 'manage_options', 'wp_travel', array( &$this, 'wp_travel_options' ) );
	}

	/*
	 * Function to ad defult to wp db
	 */
	public function wp_travel_add_defaults() {
		add_option( 'wp_travel_options', array('wp_travel_ttl' => 3600)  );
	}

	/*
	 * Unactivation function
	 */
	public function wp_travel_delete_plugin_options(){
		delete_option( 'wp_travel_options' );
	}

	/*
	 * Function to validate input from user
	 */
	public function oc_connection_validate_options( $input ){
		foreach ( $input as $key => &$option ) {
			$option = wp_filter_nohtml_kses( trim( $option ) ); // Sanitize and trim text input (remove white space, strip html tags, and escape characters)
		}
		return $input;
	}

	/*
	 * Settings page text
	 */
	public function wp_travel_section_text() {
		echo '<p>Enter Time to cache respons</p>';
	}

	/*
	 * Settings page input rendering
	 */
	public function wp_travel_setting_input() {
		$options = get_option('wp_travel_options');
		$wp_travel_ttl = $options['wp_travel_ttl'];
		echo "<input id='wp_travel_ttl' name='wp_travel_options[wp_travel_ttl]'
				type='text' value='$wp_travel_ttl' /> ";
	}

	/*
	 * Settings page
	 */
	public function wp_travel_options_page() {
		?>
			<div class="wrap">
					<?php screen_icon(); ?>
					<h2>WP Travel Settings</h2>

          <form action="options.php" method="post">

						<?php
						settings_fields('wp_travel_options');
						do_settings_section('');
						?>

          </form>
			</div>
		<?php
	}
}