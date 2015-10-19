<?php
	
/*
	Plugin Name: Simple Strava Stats
	Description: Creates a widget to display Strava stats
	Version:     0.1
	Author:      Kevin Marsden
	Author URI:  http://kmarsden.com
	License:     GPL2
	License URI: https://www.gnu.org/licenses/gpl-2.0.html
	Domain Path: /languages
	Text Domain: simple-strava-stats
*/

/**
 * Add Strava_Widget widget
 * Class based on WP_Widget example in the Codex
 */
class Simple_Strava_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress
	 */
	function __construct() {
		parent::__construct(
			'simple_strava_widget', // Base ID
			__( 'Simple Strava Stats', 'simple-strava-stats' ), // Name
			array( 'description' => __( 'Displays year to date stats for you Strava profile', 'simple-strava-stats' ), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
				
		
		$strava_access_token = $instance['access_token'];
		
		$strava_athlete_id = $instance['athlete_id'];
				
		$url = 'https://www.strava.com/api/v3/athletes/'.$strava_athlete_id.'/stats?access_token='.$strava_access_token;
										
		//based on transient Codex page
		if ( false === ( $response = get_transient( 'strava_stats_results' ) ) ) {
		 	// if it was there regenerate the data and save the transient
		 	$response = wp_remote_get( $url);
		 	set_transient( 'strava_stats_results', $response, 12 * HOUR_IN_SECONDS );
		}

		$json_results = json_decode($response[body], true);

		$miles = number_format($json_results[ytd_run_totals][distance]*0.000621371);
		$time_moving = number_format($json_results[ytd_run_totals][moving_time]/60/60);
		$elevation = number_format($json_results[ytd_run_totals][elevation_gain]*3.28084);

		 echo "<table class='simple-strava-stats-table'>"
		 . "<tr><th colspan='2' style='text-align:center'>".date('Y')." Totals</th></tr>"
		 . "<tr><td>Miles</td> "
		 . "<td  style='text-align:right;'>"
		 . esc_html( $miles )
		 . "</td></tr>"
		 . "<tr><td>Hours Moving</td>"
		 . "<td  style='text-align:right;'>"
		 . esc_html( $time_moving )
		 . "</td></tr>"
		 . "<tr><td>Elevation Gain (ft)</td>"
		 . "<td  style='text-align:right;'>"
		 .  esc_html( $elevation )
		 . "</td></tr>"
		 . "</table>";

		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( 'Strava Stats', 'simple-strava-stats' );
		$access_token = ! empty( $instance['access_token'] ) ? $instance['access_token'] : __( '', 'simple-strava-stats' );
		$athlete_id = ! empty( $instance['athlete_id'] ) ? $instance['athlete_id'] : __( '', 'simple-strava-stats' );



		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'access_token' ); ?>"><?php _e( 'Strava Access Token:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'access_token' ); ?>" name="<?php echo $this->get_field_name( 'access_token' ); ?>" type="text" value="<?php echo esc_attr( $access_token ); ?>">
		</p>
		<p>
		<label for="<?php echo $this->get_field_id( 'athlete_id' ); ?>"><?php _e( 'Strava Athlete ID:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'athlete_id' ); ?>" name="<?php echo $this->get_field_name( 'athlete_id' ); ?>" type="text" value="<?php echo esc_attr( $athlete_id ); ?>">
		</p>

		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['access_token'] = ( ! empty( $new_instance['access_token'] ) ) ? strip_tags( $new_instance['access_token'] ) : '';
		$instance['athlete_id'] = ( ! empty( $new_instance['athlete_id'] ) ) ? strip_tags( $new_instance['athlete_id'] ) : '';


		return $instance;
	}

} // end Strava_Widget class

function register_simple_strava_widgets() {
	register_widget( 'Simple_Strava_Widget' );
}

add_action( 'widgets_init', 'register_simple_strava_widgets' );