<?php
/*
	Plugin Name: Simple Strava Stats
	Description: Creates a widget to display Strava stats
	Version:     0.1
	Author:      Kevin Marsden
	Author URI:  http://kmarsden.com
	License:     GPL2
	License URI: https://www.gnu.org/licenses/gpl-2.0.html
	Text Domain: simple-strava-stats
*/

/**
 * Add Simple_Strava_Stats_Widget widget
 *
 * Class based on WP_Widget example in the Codex
 */
class Simple_Strava_Stats_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress
	*/
	function __construct() {
		parent::__construct(
			'simple_strava_stats_widget', // Base ID
			__( 'Simple Strava Stats', 'simple-strava-stats' ), // Name
			array( 'description' => __( 'Display stats from your Strava profile', 'simple-strava-stats' ), ) // Args
		);
	}
		
	var $strava_parsed_data;

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
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ) . $args['after_title'];
		}
		
		self::fetch_strava_data( $instance );
		
		//assign each result to a variable					
		$year_miles = number_format( $this->strava_parsed_data['ytd_run_totals']['distance'] * 0.000621371 ); //convert from meters to feet
		$year_time_moving = number_format( $this->strava_parsed_data['ytd_run_totals']['moving_time'] / 60 / 60 );  //convert from seconds to hours 
		$year_elevation = number_format( $this->strava_parsed_data['ytd_run_totals']['elevation_gain'] * 3.28084 ); //convert from meters to feet
		
		// week data not completed yet
		$week_miles = 0;
		$week_time_moving = 0;
		$week_elevation = 0;	

		//display results in table
		echo "<table class='simple-strava-stats-table' style='min-width: 300px;'>"
		. "<tr><th></th>"
		. "<th style='text-align:center;'>".__( 'Weekly', 'simple_strava_stats' )."</th>"
		. "<th style='text-align:center;'>".esc_html( date('Y') )."</th></tr>"
		
		. "<tr><td>".__( 'Mileage', 'simple_strava_stats')."</td> "
		. "<td style='text-align:right;'>".esc_html( number_format( $week_miles, 1 ) ).__( ' miles', 'simple_strava_stats' )
		. "</td>"
		. "<td style='text-align:right;'>".esc_html( $year_miles ).__( ' miles', 'simple_strava_stats' )."</td></tr>"
		
		. "<tr><td>".__('Moving Time', 'simple_strava_stats' )."</td>"
		. "<td style='text-align:right;'>".esc_html( number_format( $week_time_moving, 1 ) ).__( ' hours', 'simple_strava_stats' )."</td>"
		. "<td style='text-align:right;'>".esc_html( $year_time_moving ).__( ' hours', 'simple_strava_stats' )."</td></tr>"
		
		. "<tr><td style='word-wrap:normal;'>".__( ' Elevation Gain', 'simple_strava_stats'  )."</td>"
		. "<td style='text-align:right;'>".esc_html( number_format( $week_elevation, 1 ) ).__(' ft','simple_strava_stats' )."</td>"
		. "<td style='text-align:right;'>".esc_html( $year_elevation ).__(' ft', 'simple_strava_stats' )."</td></tr>"
		
		. "</table>";
		
		echo $args['after_widget'];
		
	}  // end widget method

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
		<label for="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>"><?php _e( 'Title:', 'simple-strava-stats' ); ?></label> 
		<input class="widefat" 
			id="<?php echo esc_attr( $this->get_field_id( 'title' ) ); ?>" 
			name="<?php echo $this->get_field_name( 'title' ); ?>" 
			type="text" value="<?php echo esc_attr( $title ); ?>">
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'access_token' ) ); ?>"><?php _e( 'Strava Access Token:', 'simple_strava_stats' ); ?></label> 
		<input class="widefat" 
			id="<?php echo $this->get_field_id( 'access_token' ); ?>" 
			name="<?php echo $this->get_field_name( 'access_token' ); ?>" 
			type="text" value="<?php echo esc_attr( $access_token ); ?>">
		</p>
		<p>
		<label for="<?php echo esc_attr( $this->get_field_id( 'athlete_id' ) ); ?>"><?php _e( 'Strava Athlete ID:', 'simple_strava_stats' ); ?></label> 
		<input class="widefat" 
			id="<?php echo $this->get_field_id( 'athlete_id' ); ?>" 
			name="<?php echo $this->get_field_name( 'athlete_id' ); ?>" 
			type="text" value="<?php echo esc_attr( $athlete_id ); ?>">
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
	
	/**
	 * Fetch data from Strava using the access token and athlete id from widget options
	 *
	 * Use transients to cache results because the API is rate limited
	 * If the transient does not exist, retrieve the data from Strava again
	 * Based on transients example in the Codex https://codex.wordpress.org/Transients_API
	 *
	 * @param array $instance Values from the widget options
	 *
	 * @return array $strava_parsed_data Decoded data from Strava
	 */	
	protected function fetch_strava_data( $instance ) {
		$strava_access_token = $instance['access_token'];
		$strava_athlete_id = $instance['athlete_id'];
		$url = 'https://www.strava.com/api/v3/athletes/'.$strava_athlete_id.'/stats?access_token='.$strava_access_token;

		if ( false === ( $response = get_transient( 'strava_stats_results' ) ) ) {
		 	$response = wp_remote_get( $url );
		 	//error_log( print_r( $response, true ) );  //display results in error_log.  Remove from production
		 	set_transient( 'strava_stats_results', $response, 60); //saves the transient for 60 seconds. Can increase for production
		}
		
		$this->strava_parsed_data = json_decode( $response['body'], true );
		//error_log( print_r( $this->strava_parsed_data, true ) ); //display results in error_log. Remove from production
		return $this->strava_parsed_data;
	}
	
} //end class Simple_Strava_Widget

function load_simple_strava_stats_widgets() {
	register_widget( 'Simple_Strava_Stats_Widget' );
}

add_action( 'widgets_init', 'load_simple_strava_stats_widgets' );