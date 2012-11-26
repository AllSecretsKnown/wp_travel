<?php

class WpTravelWidget extends WP_Widget {

	/*
	 * Function to register all info about our widget into the wp_widget
	 */
	public function __construct() {
		$widget_ops = array(
			'classname'   => 'WpTravelWidget',
			'description' => 'Get travel info by station name'
		);
		$this->WP_Widget( 'WpTravelWidget', 'A Travel Display', $widget_ops );
	}

	/*
	 * Function to display the widget on Admin page
	 */
	public function form( $instance ) {
		$defaults = array(
			'title'   => '',
			'station' => '',
			'option'  => 'coming'
		);

		$instance = wp_parse_args( (array) $instance, $defaults );

		$station = $instance['station'];
		$title   = $instance['title'];
		$option  = $instance['option'];
		?>

  <p>Title:
      <input type="text" class="widefat" name="<?php echo $this->get_field_name( 'title' ); ?>" value="<?php echo esc_attr( $title ); ?>" />
  </p>
  <p>station:
      <input type="text" class="widefat" name="<?php echo $this->get_field_name( 'station' ); ?>" value="<?php echo esc_attr( $station ); ?>" />
  </p>

  <p>What to get:<br>
      <select name="<?php echo $this->get_field_name( 'option' ); ?>">
          <option value="coming" <?php selected( $option, 'coming' ); ?> >Arrivals</option>
          <option value="going" <?php selected( $option, 'going' ); ?> >Departures</option>
      </select>
  </p>

	<?php
	}

	/*
	 * Function to validate the submited content
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = $old_instance;

		$instance['title']   = wp_filter_nohtml_kses( $new_instance['title'] );
		$instance['station'] = wp_filter_nohtml_kses( trim( $new_instance['station'] ) );
		$instance['option']  = $new_instance['option'];

		//Make sure were doing a vild search, help the user a bit
		$last_chars = substr( $instance['station'], count( $instance['station'] ) - 3, 2 );
		if ( $instance['station'] !== '' && strtolower( $last_chars ) !== ' c' ) {
			$instance['station'] .= ' C';
		}

		return $instance;
	}

	/*
	 * Function to display the widget on the actual page
	 */
	public function widget( $args, $instance ) {
		extract( $args );
		$my_wp_travlr = new WpTravlr();
		$my_travl     = new Travlr( $my_wp_travlr->get_wp_travel_ttl() );

		echo $before_widget;
		echo '<section class="every_tweets_section four columns alpha">';

		//This will echo widget title
		if ( isset( $instance['title'] ) && $instance['title'] != "" ) {
			echo $before_title . $instance['title'] . $after_title;
		}

		if ( $instance['option'] == 'coming' ) {
			$arrivals = $my_travl->what_comes_around( $instance['station'] );

			if ( isset( $arrivals ) && ! empty( $arrivals ) ) {
				?>
      <div class="alert alert-success">
          Ankomster: <?php echo $instance['station']; ?>
      </div>

      <table class="wp_tt">
          <thead>
          <tr>
              <th>När?</th>
              <th>Från?</th>
          </tr>
          </thead>
          <tbody>
						<?php
						foreach ( $arrivals as $key => $result ) {
							echo '<tr>';
							echo '<td>' . date( "H:i", strtotime( $key ) ) . '</td>';
							echo '<td>' . $result . '</td>';
							echo '</tr>';
						}
						?>
          </tbody>
      </table>
			<?php
			} else {
				echo '<div class="alert alert-error">Hittade inga ankomster, försök igen</div>';
			}
		} else {
			$departures = $my_travl->what_goes_around( $instance['station'] );
			if ( isset( $departures ) && ! empty( $departures ) ) {
				?>
      <div class="alert alert-success">
          <h2 class="widget_title">Avgångar: <?php echo $instance['station']; ?></h2>
      </div>
      <table class="table table-striped">
          <thead>
          <tr>
              <th>När?</th>
              <th>Till?</th>
          </tr>
          </thead>
          <tbody>
						<?php
						foreach ( $departures as $key => $result ) {
							echo '<tr>';
							echo '<td>' . date( "H:i", strtotime( $key ) ) . '</td>';
							echo '<td>' . $result . '</td>';
							echo '</tr>';
						}
						?>
          </tbody>
      </table>
			<?php
			} else {
				echo '<div class="alert alert-error">Hittade inga avgångar, försök igen</div>';
			}
		}

		echo '</section>';
		echo '<div class="clearfix"></div>';
		echo $after_widget;
	}
}

//Use widgets Init to register our widget
add_action( 'widgets_init', 'wp_travel_widget_register' );

//Register the widget
function wp_travel_widget_register() {
	register_widget( 'WpTravelWidget' );
}