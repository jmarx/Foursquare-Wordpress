<?php 
/**
 * Adds Foo_Widget widget.
 */

class Foo_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	public function __construct() {
		parent::__construct(
	 		'foursquare_explorer_widget', // Base ID
			'FourSquare Local Explorer', // Name
			array( 'description' => __( 'Display a local list of restaurants and venues', 'text_domain' ), ) // Args
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
		extract( $args );
		$title = apply_filters( 'widget_title', $instance['title'] );
		
		foursquare_local($instance['ll'],  $instance['location']); 
		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		echo __( 'Hello, World!', 'text_domain' );
		echo $after_widget;
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
		$instance['title'] = strip_tags( $new_instance['title'] );
		$instance['ll'] = strip_tags( $new_instance['ll'] );
		$instance['location'] = strip_tags( $new_instance['location'] );

		return $instance;
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		if ( isset( $instance[ 'title' ] ) ) {
			$title = $instance[ 'title' ];
		
		}
		else {
			$title = __( 'FourSquare Local Explorer', 'text_domain' );
		}
		
		if ( isset( $instance[ 'll' ] ) ) {
			$ll = $instance[ 'll' ];			
		}
		else {
			$ll = get_option('ll');
		}
		
		if ( isset( $instance[ 'location' ] ) ) {
			$location = $instance[ 'location' ];			
		}
		else {
			$location = get_option('location');
		}
		
		
		?>
		
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			<p>
			<strong>Location (Only use one of these);</strong>		
			<p>
			<?php
			$default_ll = get_option('ll');
			$default_location = get_option('location');
			?>
			
			Latitude/Longitude(Better results)<br>
			<input class="widefat" id="<?php echo $this->get_field_id( 'll' ); ?>" name="<?php echo $this->get_field_name( 'll' ); ?>" type="text" value="<?php echo esc_attr( $ll ); ?>" />
			<p>
			Spelled out(Like you would at the bottom of an envelope)<br> <input class="widefat" id="<?php echo $this->get_field_id( 'location' ); ?>" name="<?php echo $this->get_field_name( 'location' ); ?>" type="text" value="<?php echo esc_attr( $location ); ?>" />

					
		</p>
		<?php 
	}

} // class Foo_Widget

// register Foo_Widget widget
add_action( 'widgets_init', create_function( '', 'register_widget( "foo_widget" );' ) );
