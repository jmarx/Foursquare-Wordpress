<?php

class Foursquare_Explorer {

	function get_instance() {
		static $instance = array();

		if (!$instance) {
			$instance[0] = new Foursquare_Explorer;
		}
		return $instance[0];
	}

	private function __construct() {
		require_once("foursquareapi/src/FoursquareAPI.class.php");
		require_once("widget.php");
		add_action( 'admin_menu', array( $this, 'foursquare_local_menu' ));
		add_action( 'admin_init', array( $this, 'register_mysettings'));
		add_shortcode('foursquare_local', array( $this, 'foursquare_local_shortcode_func','shortcode'));
	}
	
	function register_mysettings() {
		//register our settings
		register_setting( 'foursquare-local-group', 'client_id' );
		register_setting( 'foursquare-local-group', 'client_secret' );
	}

	public function foursquare_local_menu() {
		add_options_page( 'Foursquare Local Explorer', 'Foursquare Local', 'manage_options', 'foursquare-local', array( $this, 'foursquare_local_options' ));
	}

	public function foursquare_local_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	?>
	<div class="wrap">
	<h2>Foursquare Local Explorer Settings</h2>

	<form method="post" action="options.php">
	    <?php settings_fields( 'foursquare-local-group' ); ?>
	    <table class="form-table">
	        <tr valign="top">
	        <th scope="row">Client ID</th>
	        <td><input size="65" type="text" name="client_id" value="<?php echo get_option('client_id'); ?>" /></td>
	        </tr>

	        <tr valign="top">
	        <th scope="row">Client Secret</th>
	        <td><input size="65" type="text" name="client_secret" value="<?php echo get_option('client_secret'); ?>" /></td>
	        </tr>
		</table>

	    <p class="submit">
	    <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
	    </p>

	</form>
	</div>
	<?php
	}
	function truncate($string, $length, $stopanywhere=false) {
		//truncates a string to a certain char length, stopping on a word if not specified otherwise.
		if (strlen($string) > $length) {
		//limit hit!
		$string = substr($string,0,($length -3));
		if ($stopanywhere) {
			//stop anywhere
				$string .= '...';
		        } else{
		             //stop on a word.
		            $string = substr($string,0,strrpos($string,' ')).'...';
		        }
		    }
		    return $string;
	}
	/* shortcode */
	function foursquare_local_shortcode_func($atts) {
		extract(shortcode_atts(array(
			'items' => '',
			'location' => '',
		), $atts));
		$items = intval($items);
		$location = esc_html($location);
		foursquare_local($location,$items,'shortcode');
	}

	/* display widget */
	function foursquare_local($location,$items,$type) {
		define("CLIENT_ID", get_option('client_id'));
		define("CLIENT_SECRET", get_option('client_secret'));

		// Load the Foursquare API library
		$client_id = CLIENT_ID;
		$client_secret = CLIENT_SECRET;

		//If we don't have either of these values, no reason to go forward. Just bail out
		if (empty($client_id) && empty($client_secret)) return;

		$foursquare = new FoursquareAPI($client_id, $client_secret);

		//If we don't have either of these values, no reason to go forward. Just bail out
		if (empty($location)) return;

		if (empty($items)) {
			$items = 5;
		}
		$params = array("near"=>$location,"section" => "food","venuePhotos" => 1, "limit" => $items);

		//Check for an existing transient
		$venues = get_transient('foursquare_'.$location.'_'.$items);

		if ($venues == false) {

			//api call
			$response = $foursquare->GetPublic("venues/explore",$params);

			//response from api call
			$venues = json_decode($response);

			//set transient - even a short one - to prevent throttling
			set_transient('foursquare_'.$location.'_'.$items,$venues,120);
		}
		$metacode = $venues->meta->code;

		if ($metacode == 200) {

		$photourl = '';
		foreach($venues->response->groups as $group):
		//loop through groups which only should be the one, "Recommended places"
			foreach($group->items as $venue):
			//loop through the items within that group and represents the venues
					?>
					<div class="venue">
							<?php
										$rating = $venue->venue->rating;
										if (!empty($rating )) {
											$ratingcheck = ($rating > 5 ? "positive" : "negative"); ?>
											<div style="float:right" class="venueScore <?php echo $ratingcheck; ?>"><?php echo round($rating, 1); ?></div>
										<?php } ?>
						<?php $catimage = $venue->venue->categories[0]->icon->prefix; ?>
					<img style="float:left; margin-right:3px;" src="<?php echo esc_attr($catimage); ?>32.png">

					<a style="margin-left:5px" class="venuetitle" target="_blank" href="<?php echo $venue->venue->canonicalUrl ?>"><?php echo esc_html($venue->venue->name) ?></a>
					<div class="categories" style="margin-left:40px;">
						<?php

						echo $venue->venue->categories[0]->name; ?>
					</div>
					<div style="clear:both; height:3px;">&nbsp;</div>

					<div style="margin-top:0px;">
						<?php if ($type == 'shortcode'): ?>
							<div style="float:left; display:inline; margin-right:5px; width:79%">
						<?php else: ?>
							<div style="float:left; display:inline; margin-right:5px; width:57%">
						<?php endif; ?>

										<div style="margin-top:0px;">


											<?php if (!empty($venue->venue->menu)) { ?>
												<a class="menulink" target="_blank" href="<?php echo $venue->venue->menu->url; ?>">Menu</a>
												<div class="address"><?php echo $venue->venue->location->address; ?></div>
											<?php }

											?>
	<div style="clear:both; height:0px;">&nbsp;</div>
										</div>
						<div style="clear:both; height:3px;">&nbsp;</div>

										<?php

							$herenow = $venue->venue->hereNow;
							if (!empty($herenow)) {
								$herenowcount = $herenow->count;
								if (!empty($herenowcount) && ($herenowcount != 1)) {
									echo "<div>Here now: ".esc_html($herenowcount)." People</div>";
								}
								elseif (!empty($herenowcount) && ($herenowcount == 1))  {
									echo "<div>Here now: ".esc_html($herenowcount)." Person</div>";
								}
							} ?>
						<div style="clear:both; height:5px;">&nbsp;</div>
								<?php
									 if (!empty($venue->tips)) {
										$tipgiver = $venue->tips[0]->user->firstName.' '.$venue->tips[0]->user->lastName.', '.$venue->tips[0]->user->homeCity;
										$tiptext = $venue->tips[0]->text;
										$tiptext = self::truncate($tiptext, 95);
										echo '"'.esc_html($tiptext).'"';
										echo '<div class="user">'.esc_html($tipgiver).'</div>';
									}
									?>
						</div>
						<?php if ($type == 'shortcode'): ?>
							<div style="float:left; display:inline; width:20%">
						<?php else: ?>
							<div style="float:left; display:inline; width:40%">
						<?php endif; ?>
							<?php
							$featuredphotos = $venue->venue->featuredPhotos->items;


							if (!empty($featuredphotos)) {
								foreach($featuredphotos as $photo):
								//Loop through the featured photos array. All we need is the url which is close to the top.
									$photourl = $photo->url;
								endforeach; ?>
								<img width="100" height="100" src="<?php echo esc_url($photourl); ?>">
							<?php
							}
							else {
								 echo '<img style="border:1px solid black" src="' .plugins_url( 'images/nopic.png' , __FILE__ ). '" >';
							} ?>
						</div>
						<div style="clear:both; height:0px;">&nbsp;</div>
						</div>
				<div style="clear:both; height:0px;">&nbsp;</div>

					</div>

			<?php
			endforeach;

		endforeach; ?>
		<div class="morelink"><div><a target="_blank" href="https://foursquare.com/explore?cat=bestNearby&near=<?php echo esc_html($location); ?>">More from <img src="<?php echo plugins_url( 'images/foursquarelogo_arrow.png' , __FILE__ ); ?>"></a></div></div>
		<?php
		//wooh, all done. After all that looping i need a #beer.
		}
		else{
			echo "the local explorer is unavailable at the moment";
		}
	}
}	
Foursquare_Explorer::get_instance();
?>