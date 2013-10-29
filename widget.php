<?php
/**
 * Adds FourSquare_Explorer_Widget widget.
 */

class FourSquare_Explorer_Widget extends WP_Widget {

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


		echo $before_widget;
		if ( ! empty( $title ) )
			echo $before_title . $title . $after_title;
		Foursquare_Explorer::foursquare_local($instance['location'],$instance['items'],'widget');
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
		$instance['location'] = strip_tags( $new_instance['location'] );
		$instance['items'] = strip_tags( $new_instance['items'] );

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

		if ( isset( $instance[ 'location' ] ) ) {
			$location = $instance[ 'location' ];
		}

		if ( isset( $instance[ 'items' ] ) ) {
			$items = $instance[ 'items' ];
		}
		else {
			$items = 5;
		}

		?>

		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label>
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>" />
			<p>
			<strong>Number of items to display </strong>
				<input  size="3" id="<?php echo $this->get_field_id( 'items' ); ?>" name="<?php echo $this->get_field_name( 'items' ); ?>" type="text" value="<?php echo intval( $items ); ?>" /><br />
			<p>
			<strong>Location </strong>
			<br />

		<input class="widefat" id="<?php echo $this->get_field_id( 'location' ); ?>" name="<?php echo $this->get_field_name( 'location' ); ?>" type="text" value="<?php echo esc_attr( $location ); ?>" /><br />
			Spelled out City and State (Like you would at the bottom of an envelope), or Latitude Longitude combo<br>


		</p>
		<?php
	}

} // class FourSquare_Explorer_Widget

// register FourSquare_Explorer_Widget widget
add_action( 'widgets_init', create_function( '', 'register_widget( "FourSquare_Explorer_Widget" );' ) );
