<?php
/*
Plugin Name: FourSquare Local Explorer
Description:  Shows recent checkin activity around certain geographic areas. Useful for neighborhood guides/hyperlocal blogs.
Author: Jeff Marx
*/

/* load php foursquare sdk */
require_once("foursquareapi/src/FoursquareAPI.class.php");

/* Options page */
add_action( 'admin_menu', 'foursquare_local_menu' );
function register_mysettings() {
	//register our settings
	register_setting( 'foursquare-local-group', 'client_id' );
	register_setting( 'foursquare-local-group', 'client_secret' );	
	register_setting( 'foursquare-local-group', 'll' );

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
        <td><input type="text" name="client_id" value="<?php echo get_option('client_id'); ?>" /></td>
        </tr>
         
        <tr valign="top">
        <th scope="row">Client Secret</th>
        <td><input type="text" name="client_secret" value="<?php echo get_option('client_secret'); ?>" /></td>
        </tr>
	</table>
      
	  <p>
    
	 Type in your location:<br>
	 <input type="text" name="location" /><input type="submit" id="geo" value="Search!" /><br>
	 <input type="text" name="ll" value="<?php echo get_option('ll'); ?>" />
	
    
    <p class="submit">
    <input type="submit" class="button-primary" value="<?php _e('Save Changess') ?>" />
    </p>

</form>
</div>
<?php
}



// use constants for now until I convert this to a class
define("CLIENT_ID", get_option('client_id'));
define("CLIENT_SECRET", get_option('client_secret'));
define("LL", get_option('ll'));

/* display widget */	
function foursquare_local() {	
		
	
	echo "yo";
	// Load the Foursquare API library
	$foursquare = new FoursquareAPI(CLIENT_ID,CLIENT_SECRET);
	$id = get_option('client_id');
	
	// Prepare parameters
	$params = array("ll"=>LL,"section" => "food","venuePhotos" => 1);
	$response = $foursquare->GetPublic("venues/explore",$params);
	$venues = json_decode($response);
	$photourl = '';	
	foreach($venues->response->groups as $group):
	//loop through groups which only should be the one, "Recommended places"
		foreach($group->items as $venue):	
		//loop through the items within that group and represents the venues															
				echo esc_html($venue->venue->name).'<br>';
				$featuredphotos = $venue->venue->featuredPhotos->items;				
				if (!empty($featuredphotos)) {
					foreach($featuredphotos as $photo):
						$photourl = $photo->url;
					endforeach; ?>
					<img width="100" height="100" src="<?php echo esc_html($photourl); ?>">	<p>
				<?php
				} 
				else {
					 echo '<img style="border:1px solid black" src="' .plugins_url( 'images/nopic.png' , __FILE__ ). '" > <p>';
				}
		endforeach;
	endforeach;
}

