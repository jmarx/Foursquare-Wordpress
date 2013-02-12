<?php
/*
Plugin Name: FourSquare Local Explorer
Description:  Shows recent checkin activity around certain geographic areas. Useful for neighborhood guides/hyperlocal blogs.
Author: Jeff Marx
*/

/* load php foursquare sdk */
require_once("foursquareapi/src/FoursquareAPI.class.php");
require_once("widget.php");


add_action( 'wp_enqueue_scripts', 'foursquare_stylesheet');
function foursquare_stylesheet() {
        wp_enqueue_style( 'foursquare-style', plugins_url('styles.css?version=1.0', __FILE__) );
}

/* Options page */
add_action( 'admin_menu', 'foursquare_local_menu' );
function register_mysettings() {
	//register our settings
	register_setting( 'foursquare-local-group', 'client_id' );
	register_setting( 'foursquare-local-group', 'client_secret' );
}

//call register settings function
add_action( 'admin_init', 'register_mysettings' );

function foursquare_local_menu() {
	add_options_page( 'Foursquare Local Explorer', 'Foursquare Local', 'manage_options', 'foursquare-local', 'foursquare_local_options' );
}

function foursquare_local_options() {
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

// use constants for now until I convert this to a class
define("CLIENT_ID", get_option('client_id'));
define("CLIENT_SECRET", get_option('client_secret'));



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

/* display widget */
function foursquare_local($location,$items) {

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


	$response = $foursquare->GetPublic("venues/explore",$params);
	//api call

	$venues = json_decode($response);
	//response from api call

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

				<a class="venuetitle" target="_blank" href="<?php echo $venue->venue->canonicalUrl ?>"><?php echo esc_html($venue->venue->name) ?></a>
				<div class="categories">
					<?php

					echo $venue->venue->categories[0]->name; ?>
				</div>
				<div style="clear:both; height:3px;">&nbsp;</div>

				<div style="margin-top:0px;">
					<div style="float:left; display:inline; margin-right:5px; width:57%">


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
									$tiptext = truncate($tiptext, 95);
									echo '"'.$tiptext.'"';
									echo '<div class="user">'.$tipgiver.'</div>';
								}
								?>
					</div>
					<div style="float:left; display:inline; width:40%">
						<?php
						//var_dump($venue);
						$featuredphotos = $venue->venue->featuredPhotos->items;


						if (!empty($featuredphotos)) {
							foreach($featuredphotos as $photo):
							//Loop through the featured photos array. All we need is the url which is close to the top.
								$photourl = $photo->url;
							endforeach; ?>
							<img width="100" height="100" src="<?php echo esc_html($photourl); ?>">
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
	<div class="morelink"><div><a target="_blank" href="https://foursquare.com/explore?cat=bestNearby&near=<?php echo esc_html($location); ?>">More from FourSquare >></a></div></div>
	<?php
	//wooh, all done. After all that looping i need a #beer.
	}
	else{
		echo "the local explorer is unavailable at the moment";
	}
}
