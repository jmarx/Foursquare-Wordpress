<?php
/*
Plugin Name: FourSquare Local Explorer
Description:  Shows recent checkin activity around certain geographic areas. Useful for neighborhood guides/hyperlocal blogs.
Author: Jeff Marx
*/

/* load php foursquare sdk */
require_once("foursquareapi/src/FoursquareAPI.class.php");
require_once("widget.php");

/* Options page */
add_action( 'admin_menu', 'foursquare_local_menu' );
function register_mysettings() {
	//register our settings
	register_setting( 'foursquare-local-group', 'client_id' );
	register_setting( 'foursquare-local-group', 'client_secret' );
	register_setting( 'foursquare-local-group', 'll' );
	register_setting( 'foursquare-local-group', 'location' );

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
			  <tr valign="top">
        <th scope="row">Default location</th>
        <td>

			Choose ONE of these methods to enter in your location<br>
			<strong>Latitude/Longitude</strong>
			<input type="text" name="ll" value="<?php echo get_option('ll'); ?>" /><br>
			If you have an exact latitude/long (ex: 41.0141,-73.7552 ). You'll get the best results with this. If you don't have that, leave this field blank.
			<p>
			<strong>Location spelled out:</strong>
			<input type="text" name="location" value="<?php echo get_option('location'); ?>" /><br>
			Enter a city's name and state in here as you would on a letter.  (ex: White Plains, NY).
			<p>


		</td>
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
define("LL", get_option('ll'));
define("LOCATION", get_option('location'));

/* display widget */
function foursquare_local($ll, $location) {

	// Load the Foursquare API library
	$client_id = CLIENT_ID;
	$client_secret = CLIENT_SECRET;

	//If we don't have either of these values, no reason to go forward. Just bail out
	if (empty($client_id) && empty($client_secret)) return;

	$foursquare = new FoursquareAPI($client_id, $client_secret);

	if (empty($location)) {
		$location = LOCATION;
	}

	if (empty($ll)) {
		$ll = LL;
	}
	//If we don't have either of these values, no reason to go forward. Just bail out
	if (empty($ll) && empty($location)) return;

	// Prepare parameters
	if (!empty($ll)) {
		$params = array("ll"=>$ll,"section" => "food","venuePhotos" => 1, "limit" => 5);
	} else {
		$params = array("near"=>$location,"section" => "food","venuePhotos" => 1, "limit" => 5);
	}
	/*
	For the time being, we are going to favor a lat/long result over a spelled out letter type of result just because
	we'll get better results, in the case that both fields are filled out. We could possibly change that though.
	todo: Write some javascript on the options page to not allow the user to populate bothe fields. Hopefully people will not be idiots and read the instructions
	carefully.
	*/

	$response = $foursquare->GetPublic("venues/explore",$params);
	//api call

	$venues = json_decode($response);
	//response from api call

	$photourl = '';
	foreach($venues->response->groups as $group):
	//loop through groups which only should be the one, "Recommended places"
		foreach($group->items as $venue):
		//loop through the items within that group and represents the venues
				?>
				<p>
				<a target="_blank" href="<?php echo $venue->venue->canonicalUrl ?>"><?php echo esc_html($venue->venue->name) ?></a>
				<br>
				<?php
				$featuredphotos = $venue->venue->featuredPhotos->items;


				if (!empty($featuredphotos)) {
					foreach($featuredphotos as $photo):
					//Loop through the featured photos array. All we need is the url which is close to the top.
						$photourl = $photo->url;
					endforeach; ?>
					<img width="100" height="100" src="<?php echo esc_html($photourl); ?>">
				<?php $herenow = $venue->venue->hereNow;
					if (!empty($herenow)) {
						$herenowcount = $herenow->count;
						if (!empty($herenowcount) && ($herenowcount != 1)) {
							echo "<br>Here now: ".esc_html($herenowcount)." People<br>";
						}
						elseif (!empty($herenowcount) && ($herenowcount == 1))  {
							echo "<br>Here now: ".esc_html($herenowcount)." Person<br>";
						}
					}
				}
				else {
					 echo '<img style="border:1px solid black" src="' .plugins_url( 'images/nopic.png' , __FILE__ ). '" >';
				}
		endforeach;
	endforeach;
	//wooh, all done. After all that looping i need a #beer.
}
