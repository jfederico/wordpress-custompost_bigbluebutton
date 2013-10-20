<?php
/*
Plugin Name: Big Blue Button Plugin With Custom Post Type Example
Plugin URI: 
Description: Big Blue Button Plugin With Custom Post Type Example
Version: 0.3
Author: Steve Puddick
Author URI: http://webrockstar.net/
*/


//constant definition

define("BIGBLUEBUTTON_DIR", WP_PLUGIN_URL . '/bbb-custom-post-type/' );
//define('BIGBLUEBUTTON_PLUGIN_VERSION', bigbluebutton_custom_post_type_get_version());
define('BIGBLUEBUTTON_PLUGIN_URL', plugin_dir_url( __FILE__ ));

require_once('php/bbb_api.php');




add_action('init', 'myStartSession', 1);
function myStartSession() {
    if(!session_id()) {
        session_start();
    }
}

/*
* This displays any notices that may be stored in $_SESSION
*/
function my_admin_notices(){
    if(!empty($_SESSION['my_admin_notices'])) print  $_SESSION['my_admin_notices'];
        unset ($_SESSION['my_admin_notices']);
}
add_action( 'admin_notices', 'my_admin_notices' );


/*
 * This displays some CSS we need for the BigBlueButton_CPT plugin, in the backend
 */
function bbb_css_enqueue($hook) {
    $bbb_style = plugins_url('bbb.css', __FILE__); 
    wp_register_style('bbb_style', $bbb_style);
    wp_enqueue_style('bbb_style'); 
}
add_action( 'admin_enqueue_scripts', 'bbb_css_enqueue' );

function bbb_scripts() {
    wp_enqueue_style( 'bbb-front', plugins_url('front-end.css', __FILE__) );
	
}

add_action( 'wp_enqueue_scripts', 'bbb_scripts' );

/*******************************
BBB ROOM CUSTOM POST TYPE DECLARATION
********************************/
add_action('init', 'bbb_post_type_init');
function bbb_post_type_init()  {
  $labels = array(
    'name' => _x('BBB Rooms', 'post type general name'),
    'singular_name' => _x('BBB Room', 'post type singular name'),
    'add_new' => _x('Add New', 'bbb'),
    'add_new_item' => __('Add New BBB Room'),
    'edit_item' => __('Edit BBB Room'),
    'new_item' => __('New BBB Room'),
    'all_items' => __('All BBB Rooms'),
    'view_item' => __('View BBB Room'),
    'search_items' => __('Search BBB Rooms'),
    'not_found' =>  __('No BBB Rooms found'),
    'not_found_in_trash' => __('No BBB Rooms found in Trash'), 
    'parent_item_colon' => '',
    'menu_name' => 'BBB Rooms'

  );
  $args = array(
    'labels' => $labels,
    'public' => true,
    'publicly_queryable' => true,
    'show_ui' => true, 
    'show_in_menu' => true, 
    'query_var' => true,
    'rewrite' => array( 'slug' => 'bbb-room','with_front' => FALSE),
    'capability_type' => 'bbb-room',
    'map_meta_cap' => true,
    'has_archive' => true, 
    'hierarchical' => false,
    'menu_position' => null,
    'supports' => array('title','editor','page-attributes','author'  )
  ); 
  register_post_type('bbb-room',$args);
  /*
    notice how we have set 'capability_type' => 'bbb-room'. This allows us to use wordpress's built in permission/role system
    rather than creating our own. This makes everything much easier. We will need an additional plugin such as http://wordpress.org/plugins/members/ 
    to manage the permissions and roles
   * 
   */
}

/* 
 * ***********************************
 * BBB ROOM TAXONOMY 
 * ***********************************
 * 
 * */
function build_bbb_room_taxonomies() {
	
    $labels = array(
        'name' => _x( 'BBB Room Categories', 'taxonomy general name' ),
        'singular_name' => _x( 'BBB Room Category', 'taxonomy singular name' ),
        'search_items' =>  __( 'Search BBB Room Categories' ),
        'popular_items' => __( 'Popular BBB Room  Categories' ),
        'all_items' => __( 'All BBB Room  Categories' ),
        'parent_item' => null,
        'parent_item_colon' => null,
        'edit_item' => __( 'Edit BBB Room Category' ), 
        'update_item' => __( 'Update BBB Room Category' ),
        'add_new_item' => __( 'Add New BBB Room Category' ),
        'new_item_name' => __( 'New BBB Room Category Name' ),
        'separate_items_with_commas' => __( 'Separate BBB room categories with commas' ),
        'add_or_remove_items' => __( 'Add or remove BBB room categories' ),
        'choose_from_most_used' => __( 'Choose from the most used BBB room categories' ),
        'menu_name' => __( 'BBB room Categories' )
    ); 

    register_taxonomy('bbb-room-category',array('bbb-room'),array(
            'hierarchical' => true,
            'labels' => $labels,
            'show_ui' => true,
            'update_count_callback' => '_update_post_term_count',
            'query_var' => true,
            'hierarchical' => true,
            'rewrite' => array( 'slug' => 'bbb-room-category'),
            'capabilities' => array(
                                'manage_terms' => 'manage_bbb-cat',
                                'edit_terms' => 'edit_bbb-cat',
                                'delete_terms' => 'delete_bbb-cat',
                                'assign_terms' => 'assign_bbb-cat' ) 

    ));
    /*
     * Again, we will need an additional plugin such as http://wordpress.org/plugins/members/  to map the bbb-room-category
     * capabilities to roles
     */
}

add_action( 'init', 'build_bbb_room_taxonomies', 0 );




/*
 * This adds the 'Room Details' box and 'Room Recordings' box below the main content
 * area in a BigBlueButton_CPT post
 */
add_action('add_meta_boxes', 'bbb_meta_boxes');
function bbb_meta_boxes() {
    add_meta_box('room-details', __('Room Details'),  'bbb_room_details_metabox', 'bbb-room', 'normal', 'low');
    add_meta_box('room-recordings', __('Room Recordings'),  'bbb_room_recordings_metabox', 'bbb-room', 'normal', 'low');
    add_meta_box('room-status', __('Room Status'),  'bbb_room_status_metabox', 'bbb-room', 'normal', 'low');

}


/*
 * Content for the 'Room Details' box
 */ 
function bbb_room_details_metabox($post) {
	
    $bbb_attendee_password = get_post_meta($post->ID, '_bbb_attendee_password', TRUE);
    $bbb_moderator_password = get_post_meta($post->ID, '_bbb_moderator_password', TRUE);
    $bbb_must_wait_for_admin_start = get_post_meta($post->ID, '_bbb_must_wait_for_admin_start', TRUE);
    $bbb_is_recorded = get_post_meta($post->ID, '_bbb_is_recorded', TRUE);
    $bbb_room_token = get_post_meta($post->ID, '_bbb_room_token', TRUE);
    $bbb_room_welcome_msg = get_post_meta($post->ID, '_bbb_room_welcome_msg', TRUE);
?>
    <table class='custom-admin-table'>
        <tr  >
            <th>Attendee Password</th>
            <td>
                <input type="text" name='bbb_attendee_password'  class='' value='<?php echo $bbb_attendee_password; ?>' />
            </td>
        </tr>
        <tr  >
            <th>Moderator Password</th>
            <td>
                <input type="text" name='bbb_moderator_password'  class='' value='<?php echo $bbb_moderator_password; ?>' />
            </td>
        </tr>
        <tr  >
            <th>Wait for Admin to start meeting?</th>
            <td>
               	<?php // echo $bbb_must_wait_for_admin_start; ?>
               	<input type="radio" name='bbb_must_wait_for_admin_start' id="bbb_must_wait_for_admin_start_yes" value="1" <?php if (!$bbb_must_wait_for_admin_start || $bbb_must_wait_for_admin_start == "1") echo "checked='checked'"; ?> /><label for="bbb_must_wait_for_admin_start_yes" >Yes</label>
		<input type="radio" name='bbb_must_wait_for_admin_start' id="bbb_must_wait_for_admin_start_no" value="0" <?php if ($bbb_must_wait_for_admin_start == "0") echo "checked='checked'" ; ?> /><label for="bbb_must_wait_for_admin_start_no" >No</label>
            </td>
        </tr>
        <tr  >
            <th>Record meeting?</th>
            <td>
		<input type="radio" name='bbb_is_recorded' id="bbb_is_recorded_yes" value="1" <?php if (!$bbb_is_recorded || $bbb_is_recorded == '1' ) echo "checked='checked'" ; ?> /><label for="bbb_is_recorded_yes" >Yes</label>
                <input type="radio" name='bbb_is_recorded' id="bbb_is_recorded_no" value="0" <?php if ($bbb_is_recorded == '0' ) echo "checked='checked'" ; ?> /><label for="bbb_is_recorded_no" >No</label>
            </td>
        </tr>
        <tr  >
            <th>Room Token</th>
            <td>
                <p>The room token is set when the post is saved. This is not editable.</p>
                <p>Room Token: <strong><?php echo ($bbb_room_token ? $bbb_room_token : 'Token Not Set' );  ?></strong></p>
            </td>
        </tr>
        <tr  >
            <th>Room Welcome Msg</th>
            <td>
                <textarea name='bbb_room_welcome_msg' ><?php echo $bbb_room_welcome_msg; ?></textarea>
                
            </td>
        </tr>
	</table>
	<input type="hidden" name="bbb-noncename" id="bbb-noncename" value="<?php echo wp_create_nonce( 'bbb' ); ?>" />
   
	<?php
}

function bbb_room_recordings_metabox($post) {	

    /*
     * Rooms recordings that are specific to this bbb post (and subsquently the meetingID associated with the bbb post) 
     * will be listed here
     */
    echo bigbluebutton_custom_post_type_list_room_recordings($post->ID);
}

function bbb_room_status_metabox($post) {
    
    $bbb_settings = get_option( "bbb_settings");
        
    //Read in existing option value from database
    $url_val = $bbb_settings['bbb_url'];
    $salt_val = $bbb_settings['bbb_salt'];

    $bbb_moderator_password = get_post_meta($post->ID, '_bbb_moderator_password', TRUE);
    $bbb_room_token = get_post_meta($post->ID, '_bbb_room_token', TRUE);
    $meetingID = $bbb_room_token;
    $meetingID = bigbluebutton_custom_post_type_normalizeMeetingID($meetingID);
    
    if (!BigBlueButton_CPT::isMeetingRunning( $meetingID, $url_val, $salt_val ) ) {
        echo "<p>The meeting room is currently not running.</p>";
    } else {
       $end_meeting_url = BigBlueButton_CPT::getEndMeetingURL( $meetingID, $bbb_moderator_password, $url_val, $salt_val);
       echo "The meeting room is currently running.";
       echo "<p><a class='end-meeting' href='$end_meeting_url' target='_blank' >End Meeting Now</a></p>";
    }
            
            
    
}

// Add to admin_init function
add_action('save_post', 'save_bbb_data' );
 
function save_bbb_data($post_id) {	
	
    $new_nonce = wp_create_nonce( 'bbb' );

    if ($_POST['"bbb-noncename'] == $new_nonce) {
        //die('nonce fail');	
        return $post_id;
    }

    // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) 
        return $post_id;

    if ( !current_user_can( 'edit_bbb-room', $post_id ) )
        return $post_id;


    $post = get_post($post_id);
    if ($post->post_type == 'bbb-room') { 

        $token = get_post_meta($post->ID, '_bbb_room_token', TRUE);
        // Assign a random seed to generate unique ID on a BBB server
        if (!$token) {
            $meetingID = bigbluebutton_custom_post_type_generateToken();
            update_post_meta($post_id, '_bbb_room_token', $meetingID);
        }

        update_post_meta($post_id, '_bbb_attendee_password', esc_attr($_POST['bbb_attendee_password']) );
        update_post_meta($post_id, '_bbb_moderator_password', esc_attr($_POST['bbb_moderator_password']) );
        update_post_meta($post_id, '_bbb_must_wait_for_admin_start', esc_attr($_POST['bbb_must_wait_for_admin_start']) );
        update_post_meta($post_id, '_bbb_is_recorded', esc_attr($_POST['bbb_is_recorded']) );
        update_post_meta($post_id, '_bbb_room_welcome_msg', esc_attr($_POST['bbb_room_welcome_msg']) );

    }
    return $post_id;
}


add_action( 'before_delete_post', 'before_bbb_delete' );
function before_bbb_delete( $postid ){

    /*
     * If we want to do anything when the BBB post in wordpress is deleted, we can hook into here.
     */
}




/*
 * CONTENT FILTER TO ADD BBB BUTTON
 */
function bbb_filter($content) {


    /*
     * Target only bbb-room post type, and on the 'single' page (not archive)
     * 
     * If we do not meet these requirements, output the content as usual.
     * 
     * If we do meet requirements, a button to go to the room will be attached at the bottom of the
     * regular page content
     */
    if ( 'bbb-room' == get_post_type( $post ) && is_single()  ) {
    
        global $wp_version, $current_site, $current_user, $wp_roles, $post;
        //Initializes the variable that will collect the output
        $out = '';

        
        $bbb_settings = get_option( "bbb_settings");
        
        //Read in existing option value from database
        $url_val = $bbb_settings['bbb_url'];
        $salt_val = $bbb_settings['bbb_salt'];
        
        
        $bbb_attendee_password = get_post_meta($post->ID, '_bbb_attendee_password', TRUE);
	$bbb_moderator_password = get_post_meta($post->ID, '_bbb_moderator_password', TRUE);
	$bbb_must_wait_for_admin_start = get_post_meta($post->ID, '_bbb_must_wait_for_admin_start', TRUE);
	$bbb_is_recorded = get_post_meta($post->ID, '_bbb_is_recorded', TRUE);
	$bbb_room_token = get_post_meta($post->ID, '_bbb_room_token', TRUE);
        $bbb_room_welcome_msg = get_post_meta($post->ID, '_bbb_room_welcome_msg', TRUE);
        $bbb_meeting_name = get_the_title($post->ID);
        
        $meetingID = $bbb_room_token;

        $meetingID = bigbluebutton_custom_post_type_normalizeMeetingID($meetingID);

        if( !$current_user->ID ) {
            /*
             * Right now no functionality is present to handle user's who are not logged in. That functionality
             * will go here
             * 
             * $name = $_POST['display_name'];
             * $password = $_POST['pwd'];
            */
            
            $out = '<div class="login-box" style="background-color:#eee;padding:20px;margin-bottom:20px;" >';
            if (get_option('users_can_register')) {
                
                $out .=  '<p>Please login or register to access this room and view room recordings:</p>';
                $out .= wp_register('<p>','</p>',false);
            } else {
                $out .=  '<p>Only registered users can access rooms and view room recordings:</p>';
            } 
            $out .= '</div>';
            
        } else {

            if( $current_user->display_name != '' ){
                $name = $current_user->display_name;
            } else if( $current_user->user_firstname != '' || $current_user->user_lastname != '' ){
                $name = $current_user->user_firstname != ''? $current_user->user_firstname.' ': '';
                $name .= $current_user->user_lastname != ''? $current_user->user_lastname.' ': '';
            } else if( $current_user->user_login != ''){
                $name = $current_user->user_login;
            } else {
                $name = $role;
            }

            /*
             * To make things easier and allow deeper integration with other plugins, we are using wordpress's
             * built in permission and capability functions rather than something custom. For more info check out:
             * http://codex.wordpress.org/Function_Reference/current_user_can
             */
            if (current_user_can( 'edit_bbb-room', $post->ID) )
                $password = $bbb_moderator_password;
            elseif (current_user_can( 'read') ) 
                $password = $bbb_attendee_password;

            //Extra parameters
            $recorded = $bbb_is_recorded;
            $welcome = $bbb_room_welcome_msg;
            $duration = 0;
            $voicebridge = 0;
            $logouturl = (is_ssl()? "https://": "http://") . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'] . '?logout=true';

            //Metadata for tagging recordings
            $metadata = Array(
                'meta_origin' => 'WordPress',
                'meta_originversion' => $wp_version,
                'meta_origintag' => 'wp_plugin-bigbluebutton_custom_post_type '.BIGBLUEBUTTON_PLUGIN_VERSION,
                'meta_originservername' => home_url(),
                'meta_originservercommonname' => get_bloginfo('name'),
                'meta_originurl' => $logouturl
            );

            //Call for creating meeting on the bigbluebutton_custom_post_type server
            $response = BigBlueButton_CPT::createMeetingArray($name, $meetingID, $bbb_meeting_name, $bbb_room_welcome_msg, $bbb_moderator_password, $bbb_attendee_password, $salt_val, $url_val, $logouturl, $recorded? 'true':'false', $duration, $voicebridge, $metadata );

           
            if(!$response || $response['returncode'] == 'FAILED' ){//If the server is unreachable, or an error occured
                $out .= "<p class='error'>". __('Sorry an error occured while creating the meeting room.','bbb'). "</p>";

            } else { //The user can join the meeting, as it is valid


                $bigbluebutton_custom_post_type_joinURL = BigBlueButton_CPT::getJoinURL($meetingID, $name, $password, $salt_val, $url_val );
                //If the meeting is already running or the moderator is trying to join or a viewer is trying to join and the
                //do not wait for moderator option is set to false then the user is immediately redirected to the meeting

                /*
                 * At the moment BigBlueButton_CPT::isMeetingRunning always returns false which only allows users to join in certain cases
                 */
                if ( (BigBlueButton_CPT::isMeetingRunning( $meetingID, $url_val, $salt_val ) && ($bbb_moderator_password == $password || $bbb_attendee_password == $password ) )
                        || $response['moderatorPW'] == $password
                        || ($response['attendeePW'] == $password && !$bbb_must_wait_for_admin_start)  ){

                    if ($bbb_moderator_password == $password ) {
                        $button_text = 'Join Room as Moderator';
                        $out .= '<p><a class="bbb" style="" href="'.$bigbluebutton_custom_post_type_joinURL .'" target="_blank">'.$button_text.'</a></p>';


                    } else if ($bbb_attendee_password == $password ) {
                        $button_text = 'Join Room as Attendee';
                        $out .= '<p><a class="bbb"  href="'.$bigbluebutton_custom_post_type_joinURL .'" target="_blank">'.$button_text.'</a></p>';

                    } 

                    //return $out;
                }

                //If the viewer has the correct password, but the meeting has not yet started they have to wait
                //for the moderator to start the meeting
                else if ($bbb_attendee_password == $password) {
                    //Stores the url and salt of the bigblubutton server in the session
                    $_SESSION['mt_bbb_url'] = $url_val;
                    $_SESSION['mt_salt'] = $salt_val;
                    //Displays the javascript to automatically redirect the user when the meeting begins
                    $out .= '<div id="bbb-join-container"></div>';
                    $out .= bigbluebutton_custom_post_type_display_reveal_script($bigbluebutton_custom_post_type_joinURL, $meetingID, $bbb_meeting_name, $name);
                    //return $out;
                }
            }

            $out .= bigbluebutton_custom_post_type_list_room_recordings($post->ID);

        }

  }
  
  /*
   * Show a listing of the recordings below the content and the 'join room' button. 
   * At the moment the listing of recordings is not working.
   */
  
  return $content . $out;
}

add_filter( 'the_content', 'bbb_filter' );



//should also check to make sure we are on the BBB settings page
if ( is_admin() && isset( $_POST['bbb_url'] ) || isset( $_POST['bbb_salt'] ) || isset( $_POST['bbb_auth_key'] )   ) {

        
    $bbb_settings = get_option( "bbb_settings");
    $do_update = 0;
    if ( isset( $_POST['bbb_url']) && ($bbb_settings['bbb_url'] != $_POST['bbb_url']  ) ) {
        $bbb_settings['bbb_url'] = $_POST['bbb_url'];
        $do_update = 1;
    }
    if ( isset( $_POST['bbb_salt']) && ($bbb_settings['bbb_salt'] != $_POST['bbb_salt']) ) {
        $bbb_settings['bbb_salt'] = $_POST['bbb_salt'];
        $do_update = 1;
    }
    if ( isset( $_POST['bbb_auth_key']) && ($bbb_settings['bbb_auth_key'] != $_POST['bbb_auth_key']) ) {
        $bbb_settings['bbb_auth_key'] = $_POST['bbb_auth_key'];
        $do_update = 1;
    }
    if ($do_update) {
        $update_response = update_option( "bbb_settings", $bbb_settings );
        
        if ($update_response)
            add_action('admin_notices', 'bbb_update_notice_success');
        else
            add_action('admin_notices', 'bbb_update_notice_fail');
    } else {
        add_action('admin_notices', 'bbb_update_notice_no_change');
    }
    
    //add_action('admin_notices', 'update_notice');
}

function bbb_update_notice_success(){
    echo '<div class="updated">
       <p>BigBlueButton options have been updated.</p>
    </div>';
}

function bbb_update_notice_fail(){
    echo '<div class="error">
       <p>BigBlueButton options failed to update.</p>
    </div>';
}

function bbb_update_notice_no_change(){
    echo '<div class="updated">
       <p>BigBlueButton options have not changed.</p>
    </div>';
}


/*
 *  OPTIONS PAGE 
 */
add_action('admin_menu', 'register_site_options_page');

function register_site_options_page() {
    add_submenu_page( 'options-general.php', 'Site Options', 'BBB Options', 'edit_pages', 'site-options', 'bbb_options_page_callback' ); 
}

function bbb_options_page_callback() { 
    
    $bbb_settings = get_option( "bbb_settings");
   
    ?>
    <h1>BigBlueButton Settings</h1>
    <form  action="<?php echo $_SERVER["REQUEST_URI"]; ?>" method="post" name="site_options_page" >
        <table class="custom-admin-table">
            <tr>
                <th>URL of BBB Server</th>
                <td>
                    <input type="text" size="56" name="bbb_url" value="<?php echo $bbb_settings['bbb_url']; ?>" />
                    <p>Example: http://test-install.blindsidenetworks.com/bigbluebutton_custom_post_type/</p>
                </td>
            </tr>
            <tr>
                <th>Salt of BBB server</th>
                <td>
                    <input type="text" size="56" name="bbb_salt" value="<?php echo $bbb_settings['bbb_salt']; ?>" />
                    <p>Example: 8cd8ef52e8e101574e400365b55e11a6</p>
                </td>
            </tr>
            <tr>
                <th>BBB Authentication Key</th>
                <td>
                    <input type="text" size="56" name="bbb_auth_key" value="<?php echo $bbb_settings['bbb_auth_key']; ?>" />
                    <p>Example: </p>
                </td>
            </tr>
            <tr>
                <th></th>
                <td><input type="submit" value="Save Settings" /></td>
            </tr>
        </table>
    </form>
<?php }
 
/*
 *  SHORTCODE GENERATOR PAGE 
 */
add_action('admin_menu', 'register_shortcode_generator_page');

function register_shortcode_generator_page() {
    global $shortcode_generator_page;	
    $shortcode_generator_page = add_submenu_page( 'tools.php', 'BBB Shortcode Generator', 'BBB Shortcode Generator', 'edit_pages', 'bbb-shortcode', 'bbb_shortcode_page_callback' ); 
}

function bbb_shortcode_page_callback() { ?>
    <h1>BBB Shortcode Generator</h1>
    <table class="bbb-shortcode" >
        <tr>
            <th>Direct or Wordpress Link?</th>
            <td>

                <input type="radio" name='bbb_link_type' id="bbb_link_type_direct" value="direct" checked="checked" /><label for="bbb_link_type_direct" >Direct</label>
                <input type="radio" name='bbb_link_type' id="bbb_link_type_wordpress" value="wordpress" /><label for="bbb_link_type_wordpress" >Wordpress</label>

            </td>
        </tr>
        <tr>
            <th>
                Only show rooms from: <br />
                <small>Hold CTRL (pc) or COMMAND (Mac) to select multiple.</small>	 	
            </th>
            <td>
                <?php

                    $terms =  get_terms( 'bbb-room-category');
                    $count = count($terms);
                    if ( $count > 0 ){
                         echo "<select multiple id='bbb-categories'>";
                         echo "<option value='0' >All BBB Categories</option>";
                         foreach ( $terms as $term ) {
                           echo "<option value=". $term->term_id ." >" . $term->name . "</option>";
                         }
                         echo "</select>";
                    } else {

                    }
                ?>
            </td>
        </tr>
        <tr>
            <th>
                Only show these rooms: <br />
                <small>Hold CTRL (pc) or COMMAND (Mac) to select multiple.</small>	 
            </th>
            <td>

                <?php

                $args = array ( 'post_type' => 'bbb-room', 
                                                'orderby' => 'name',
                                                'posts_per_page' => -1,
                                                'order' => 'DESC');

                $bbb_room_query = new WP_Query( $args );

                ?>
                <?php if ( $bbb_room_query->have_posts() ) : ?>
                        <select multiple id="bbb-post-ids" >
                <?php while ( $bbb_room_query->have_posts() ) : $bbb_room_query->the_post(); ?>   	

                        <?php echo "<option value='".$bbb_room_query->post->ID ."' >". get_the_title() ."</option>"; ?>
                        <?php // var_dump($post); ?>
                <?php   
                endwhile;
                ?>
                        </select>
                <?php wp_reset_postdata(); ?>

                <?php else:  ?>
                  <p><?php _e( 'No BBB Rooms have been created yet.' ); ?></p>
                <?php endif; ?>
            </td>
        </tr>
        <tr>
            <th>
                Shortcode: <br />
                <small>Copy this shortcode into post/page content to show a drop down list.</small>	
            </th>
            <td>
                <p id="shortcode" ></p>
            </td>
        </tr>
    </table>
	
<?php } 


function bbb_shortcode_enqueue($hook) {
    global $shortcode_generator_page;
    $screen = get_current_screen();

     if ( $screen->id == $shortcode_generator_page ) {

        $bbb_shortcode = plugins_url( 'shortcode.js' , __FILE__ );
        wp_register_script( 'bbb_shortcode', $bbb_shortcode);
        wp_enqueue_script( 'bbb_shortcode' );

     }
	
}
add_action( 'admin_enqueue_scripts', 'bbb_shortcode_enqueue' );
	


function bbbRenderShortcode( $atts ) {
    extract( shortcode_atts( array(
                            'link_type' => 'wordpress',
                            'bbb_categories' => '0',
                            'bbb_posts' => ''
                            ), $atts ) );

    $output_string = '';

    $args = array ( 'post_type' => 'bbb-room', 
                    'orderby' => 'name',
                    'posts_per_page' => -1,
                    'order' => 'DESC'

    );

    if ($bbb_categories) {
        $args['tax_query'] = array( 
                                array(
                                        'taxonomy' => 'bbb-room-category',
                                        'field' => 'id',
                                        'terms' => explode(",", $bbb_categories)
                                        )
                                );
    }

    if ($bbb_posts) {
        $args['post__in'] = explode(",", $bbb_posts);
    }

    $bbb_posts = new WP_Query( $args );
?>

    <?php if ( $bbb_posts->have_posts() ) : 
            $output_string = '<select onchange="location = this.options[this.selectedIndex].value;" >';
            while ( $bbb_posts->have_posts() ) : $bbb_posts->the_post();   	

                    $output_string .= "<option value='". get_permalink() ."' >". get_the_title() ."</option>"; 

    endwhile;

            $output_string .= '</select>';
            wp_reset_postdata(); 

    else:  
      //$output_string .= '<p>' . __( 'No BBB Rooms have been created yet.' ) . '</p>';
    endif; 


    return $output_string;
}
add_shortcode( 'bbb', 'bbbRenderShortcode' );



//Displays the javascript that handles redirecting a user, when the meeting has started
//the meetingName is the meetingID
/*
 * At the moment this does not work since the broker always returns false when checking if the meeting has started
 */
function bigbluebutton_custom_post_type_display_reveal_script($bigbluebutton_custom_post_type_joinURL, $meetingID, $meetingName, $name){
    $out = '
    <script type="text/javascript">
        function bigbluebutton_custom_post_type_ping() {
            jQuery.ajax({
                url : "/wp-content/plugins/bbb-custom-post-type/php/broker.php?action=ping&meetingID='.urlencode($meetingID).'",
                async : true,
                dataType : "xml",
                success : function(xmlDoc){
                    $xml = jQuery( xmlDoc ), $running = $xml.find( "running" );
                    if($running.text() == "true"){
                        //window.location = "'.$bigbluebutton_custom_post_type_joinURL.'";
                        if (!jQuery("div#bbb-join-container a").length)
                            jQuery("div#bbb-join-container").append("<p><a class=\'bbb\'  href=\''.$bigbluebutton_custom_post_type_joinURL .'\' target=\'_blank\'>'. 'Join as Attendee' .'</a></p>");
                        }
                },
                error : function(xmlHttpRequest, status, error) {
                    //console.debug(xmlHttpRequest);
                    //console.debug(status);
                }
            });

        }

        setInterval("bigbluebutton_custom_post_type_ping()", 5000);
    </script>';

    $out .= '
    <table>
      <tbody>
        <tr>
          <td>
            Welcome '.$name.'!<br /><br />
            '.$meetingName.' session has not been started yet.<br /><br />
            <div align="center"><img src="./wp-content/plugins/bigbluebutton_custom_post_type/images/polling.gif" /></div><br />
            (Your browser will automatically refresh and join the meeting when it starts.)
          </td>
        </tr>
      </tbody>
    </table>';

    return $out;
}


//================================================================================
//---------------------------------List Recordings----------------------------------
//================================================================================
// Displays all the recordings available in the bigbluebutton_custom_post_type server
/*
 * Keep in mind that although this has the same function name as the previous version of the plugin, 
 * this functions quite differently. It only lists the recordings for the specific post, not all of the 
 * recordings on the server. 
 * 
 * Also, editing controls are only displayed in the backend, to users with the appropriate permissions
 * 
 * Right now this does not seem to be functional.
 */
function bigbluebutton_custom_post_type_list_room_recordings($postID = 0) {
    global $current_user;
    
    $bbb_room_token = get_post_meta($postID, '_bbb_room_token', TRUE);
    $meetingID = $bbb_room_token;
    $meetingID = bigbluebutton_custom_post_type_normalizeMeetingID($meetingID);
    //Initializes the variable that will collect the output
    $out = '';

    $bbb_settings = get_option( "bbb_settings");

    //Read in existing option value from database
    $url_val = $bbb_settings['bbb_url'];
    $salt_val = $bbb_settings['bbb_salt'];

    
    $_SESSION['mt_bbb_url'] = $url_val;
    $_SESSION['mt_salt'] = $salt_val;


    $listOfRecordings = Array();
    if( $meetingID != '' ){
        $recordingsArray = BigBlueButton_CPT::getRecordingsArray($meetingID, $url_val, $salt_val);
        if( $recordingsArray['returncode'] == 'SUCCESS' && !$recordingsArray['messageKey'] ){
            $listOfRecordings = $recordingsArray['recordings'];
        }
    }

    if (class_exists('FirePHP')) {
        
        $firephp = FirePHP::getInstance(true);
        $firephp->log(BigBlueButton_CPT::getRecordingsArray($meetingID, $url_val, $salt_val));
         
    }
    
    
    
    //Checks to see if there are no meetings in the wordpress db and if so alerts the user
    if(count($listOfRecordings) == 0){
        $out .= '<p><strong>There are no recordings available.</strong></p>';
        return $out;
    }


    if ( current_user_can( 'edit_bbb-room', $postID) && is_admin()  ) {
        $out .= '
        <script type="text/javascript">
            wwwroot = \''.get_bloginfo('url').'\'
            function actionCall(action, recordingid) {

                action = (typeof action == \'undefined\') ? \'publish\' : action;
         
                if (action == \'publish\' || (action == \'delete\' && confirm("Are you sure to delete this recording?"))) {
                    if (action == \'publish\') {
                        var el_a = document.getElementById(\'actionbar-publish-a-\'+ recordingid);
                        if (el_a) {
                            var el_img = document.getElementById(\'actionbar-publish-img-\'+ recordingid);
                            if (el_a.title == \'Hide\' ) {
                                action = \'unpublish\';
                                el_a.title = \'Show\';
                                el_img.src = wwwroot + \'/wp-content/plugins/bbb-custom-post-type/images/show.gif\';
                            } else {
                                action = \'publish\';
                                el_a.title = \'Hide\';
                                el_img.src = wwwroot + \'/wp-content/plugins/bbb-custom-post-type/images/hide.gif\';
                            }
                        }
                    } else {
                        // Removes the line from the table
                        jQuery(document.getElementById(\'actionbar-tr-\'+ recordingid)).remove();
                    }
                    actionurl = wwwroot + "/wp-content/plugins/bbb-custom-post-type/php/broker.php?action=" + action + "&recordingID=" + recordingid;
                    jQuery.ajax({
                            url : actionurl,
                            async : false,
                            success : function(response){
                            },
                            error : function(xmlHttpRequest, status, error) {
                                console.debug(xmlHttpRequest);
                            }
                        });
                }
            }
        </script>';
    }


    //Print begining of the table
    $out .= '
    <div id="bbb-recordings-div" class="bbb-recordings">
    <table class="stats" cellspacing="5">
      <tr>
        <th class="hed" colspan="1">Recording</td>
        <th class="hed" colspan="1">Meeting Room Name</td>
        <th class="hed" colspan="1">Date</td>
        <th class="hed" colspan="1">Duration</td>';
    if ( current_user_can( 'edit_bbb-room', $postID) && is_admin() ) {
        $out .= '
        <th class="hedextra" colspan="1">Toolbar</td>';
    }
    $out .= '
      </tr>';
    foreach( $listOfRecordings as $recording){
        
            /// Prepare playback recording links
            $type = '';
            foreach ( $recording['playbacks'] as $playback ){
                if ($recording['published'] == 'true'){
                    $type .= '<a href="'.$playback['url'].'" target="_new">'.$playback['type'].'</a>&#32;';
                } else {
                    $type .= $playback['type'].'&#32;';
                }
            }

            /// Prepare duration
            $endTime = isset($recording['endTime'])? floatval($recording['endTime']):0;
            $endTime = $endTime - ($endTime % 1000);
            $startTime = isset($recording['startTime'])? floatval($recording['startTime']):0;
            $startTime = $startTime - ($startTime % 1000);
            $duration = intval(($endTime - $startTime) / 60000);

            /// Prepare date
            //Make sure the startTime is timestamp
            if( !is_numeric($recording['startTime']) ){
                $date = new DateTime($recording['startTime']);
                $recording['startTime'] = date_timestamp_get($date);
            } else {
                $recording['startTime'] = ($recording['startTime'] - $recording['startTime'] % 1000) / 1000;
            }

            //Format the date
            //$formatedStartDate = gmdate("M d Y H:i:s", $recording['startTime']);
            $formatedStartDate = date_i18n( "M d Y H:i:s", $recording['startTime'], false );

            //Print detail
            
            if ($recording['published'] == 'true' || is_admin() ) {
                
                $out .= '
                <tr id="actionbar-tr-'.$recording['recordID'].'">
                  <td>'.$type.'</td>
                  <td>'.$recording['meetingName'].'</td>
                  <td>'.$formatedStartDate.'</td>
                  <td>'.$duration.' min</td>';

                /// Prepare actionbar if role is allowed to manage the recordings
                if ( current_user_can( 'edit_bbb-room', $postID)  && is_admin() ) {
                    $action = ($recording['published'] == 'true')? 'Hide': 'Show';
                    $actionbar = "<a id=\"actionbar-publish-a-".$recording['recordID']."\" title=\"".$action."\" href=\"#\"><img id=\"actionbar-publish-img-".$recording['recordID']."\" src=\"".get_bloginfo('url')."/wp-content/plugins/bbb-custom-post-type/images/".strtolower($action).".gif\" class=\"iconsmall\" onClick=\"actionCall('publish', '".$recording['recordID']."'); return false;\" /></a>";
                    $actionbar .= "<a id=\"actionbar-delete-a-".$recording['recordID']."\" title=\"Delete\" href=\"#\"><img id=\"actionbar-delete-img-".$recording['recordID']."\" src=\"".get_bloginfo('url')."/wp-content/plugins/bbb-custom-post-type/images/delete.gif\" class=\"iconsmall\" onClick=\"actionCall('delete', '".$recording['recordID']."'); return false;\" /></a>";
                    $out .= '
                    <td>'.$actionbar.'</td>';
                }

                $out .= '
                </tr>';
                
            }
        
    }

    //Print end of the table
    $out .= '  </table>
    </div>';

    return $out;

}


//================================================================================
//------------------------------- Helping functions ------------------------------
//================================================================================
//Validation methods

function bigbluebutton_custom_post_type_generateToken($tokenLength=6){
    $token = '';
    
    if(function_exists('openssl_random_pseudo_bytes')) {
        $token .= bin2hex(openssl_random_pseudo_bytes($tokenLength));
    } else {
        //fallback to mt_rand if php < 5.3 or no openssl available
        $characters = '0123456789abcdef';
        $charactersLength = strlen($characters)-1;
        $tokenLength *= 2;
        
        //select some random characters
        for ($i = 0; $i < $tokenLength; $i++) {
            $token .= $characters[mt_rand(0, $charactersLength)];
        }
    }
     
    return $token;
}

function bigbluebutton_custom_post_type_generatePasswd($numAlpha=6, $numNonAlpha=2, $salt=''){
    $listAlpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $listNonAlpha = ',;:!?.$/*-+&@_+;./*&?$-!,';
    
    $pepper = '';
    do{
        $pepper = str_shuffle( substr(str_shuffle($listAlpha),0,$numAlpha) . substr(str_shuffle($listNonAlpha),0,$numNonAlpha) );
    } while($pepper == $salt);
    
    return $pepper;
}

function bigbluebutton_custom_post_type_normalizeMeetingID($meetingID){
    return (strlen($meetingID) == 12)? sha1(home_url().$meetingID): $meetingID;
}



?>