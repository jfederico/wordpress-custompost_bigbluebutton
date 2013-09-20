<?php
/*
Plugin Name: Big Blue Button Plugin With Custom Post Type Example
Plugin URI: 
Description: Big Blue Button Plugin With Custom Post Type Example
Version: 0.1
Author: Steve Puddick
Author URI: http://webrockstar.net/
*/

//constant definition
define("BIGBLUEBUTTON_DIR", WP_PLUGIN_URL . '/bbb-custom-post-type/' );
//define('BIGBLUEBUTTON_PLUGIN_VERSION', bigbluebutton_get_version());
define('BIGBLUEBUTTON_PLUGIN_URL', plugin_dir_url( __FILE__ ));

//constant message definition
define('BIGBLUEBUTTON_STRING_WELCOME', '<br>Welcome to <b>%%CONFNAME%%</b>!<br><br>To understand how BigBlueButton works see our <a href="event:http://www.bigbluebutton.org/content/videos"><u>tutorial videos</u></a>.<br><br>To join the audio bridge click the headset icon (upper-left hand corner). <b>Please use a headset to avoid causing noise for others.</b>');
define('BIGBLUEBUTTON_STRING_MEETING_RECORDED', '<br><br>This session is being recorded.');



//================================================================================
//------------------Required Libraries and Global Variables-----------------------
//================================================================================
require('php/bbb_api.php');


if (is_admin()) {
    /*
     * If we want to show a message such as 'error creating BBB room' on post creation event,
     * we will need to store this message in the $_SESSION global variable. In order to do this,
     * we need to start the session.
     */
    if (!session_id())
        session_start();
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
 * This displays some CSS we need for the BigBlueButton plugin, in the backend
 */
function bbb_css_enqueue($hook) {
    $bbb_style = plugins_url('bbb.css', __FILE__); 
    wp_register_style('bbb_style', $bbb_style);
    wp_enqueue_style('bbb_style'); 
}
add_action( 'admin_enqueue_scripts', 'bbb_css_enqueue' );


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
    'supports' => array('title','editor' )
  ); 
  register_post_type('bbb-room',$args);
}






/*
 * This adds the 'Room Details' box and 'Room Recordings' box below the main content
 * area in a BigBlueButton post
 */
add_action('add_meta_boxes', 'bbb_meta_boxes');
function bbb_meta_boxes() {
    add_meta_box('room-details', __('Room Details'),  'bbb_room_details_metabox', 'bbb-room', 'normal', 'low');
    add_meta_box('room-recordings', __('Room Recordings'),  'bbb_room_recordings_metabox', 'bbb-room', 'normal', 'low');
	
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
?>

    <p>There are no recordings for this room.</p>

    <?php
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
            $meetingID = bigbluebutton_generateToken();
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


add_action( 'before_delete_post', 'my_func' );
function my_func( $postid ){

    // We check if the global post type isn't ours and just return
    /*
    global $post_type;   
    if ( $post_type != 'my_custom_post_type' ) return;
	*/
    // My custom stuff for deleting my custom post type here
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
                                'manage_terms' => 'manage_bbb-room-categorys',
                                'edit_terms' => 'edit_bbb-room-categorys',
                                'delete_terms' => 'delete_bbb-room-categorys',
                                'assign_terms' => 'assign_bbb-room-categorys' ) 

    ));
}

add_action( 'init', 'build_bbb_room_taxonomies', 0 );



/*
 * CONTENT FILTER TO ADD BBB BUTTON
 */
function bbb_filter($content) {
/*
 * if (logged_in and (can_auto_join_as_attendee || (is_post_author && can_join_as_moderator)  ))


    else if (form data submitted)
            if (form_data_good)
                    auto direct to room
            else
                    show entry form with error note 

    else
            show entry form 
 * 
 * 
 * 
 * 
 */    






//target only BBB post type
    if ( 'bbb-room' == get_post_type( $post ) && is_single()  ) {
    
        global $wp_version, $current_site, $current_user, $wp_roles, $post;
        //Initializes the variable that will collect the output
        $out = '';

        //Set the role for the current user if is logged in
        /*
        $role = null;
        if( $current_user->ID ) {
            $role = "unregistered";
            foreach($wp_roles->role_names as $_role => $Role) {
                if (array_key_exists($_role, $current_user->caps)){
                    $role = $_role;
                    break;
                }
            }
        } else {
            $role = "anonymous";
        }
        */
        
        $bbb_settings = get_option( "bbb_settings");
        
        //Read in existing option value from database
        $url_val = $bbb_settings['bbb_url'];
        $salt_val = $bbb_settings['bbb_salt'];
        
        //Read in existing permission values from database
        //$permissions = get_option('bigbluebutton_permissions');

        //Gets all the meetings from wordpress database
        //$listOfMeetings = $wpdb->get_results("SELECT meetingID, meetingName, meetingVersion, attendeePW, moderatorPW FROM ".$table_name." ORDER BY meetingName");

        $dataSubmitted = false;
        $meetingExist = false;

        $dataSubmitted = true;
        $meetingExist = true;

        
        $bbb_attendee_password = get_post_meta($post->ID, '_bbb_attendee_password', TRUE);
	$bbb_moderator_password = get_post_meta($post->ID, '_bbb_moderator_password', TRUE);
	$bbb_must_wait_for_admin_start = get_post_meta($post->ID, '_bbb_must_wait_for_admin_start', TRUE);
	$bbb_is_recorded = get_post_meta($post->ID, '_bbb_is_recorded', TRUE);
	$bbb_room_token = get_post_meta($post->ID, '_bbb_room_token', TRUE);
        $bbb_room_welcome_msg = get_post_meta($post->ID, '_bbb_room_welcome_msg', TRUE);
        $bbb_meeting_name = get_the_title($post->ID);
        
        
        $meetingID = $bbb_room_token;

        //$found = $wpdb->get_row("SELECT * FROM ".$table_name." WHERE meetingID = '".$meetingID."'");
        
        $meetingID = bigbluebutton_normalizeMeetingID($meetingID);

        if( !$current_user->ID ) {
            /*
            $name = isset($_POST['display_name']) && $_POST['display_name'] ? $_POST['display_name']: $role;

            if( bigbluebutton_validate_defaultRole($role, 'none') ) {
                $password = $_POST['pwd'];
            } else {
                $password = $permissions[$role]['defaultRole'] == 'none'? $found->moderatorPW: $found->attendeePW;
            }
            */
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

            //admin
            if (current_user_can( 'manage_options') )
                $password = $bbb_moderator_password;
            elseif (current_user_can( 'edit_posts') ) //other logged in user beside admin
                $password = $bbb_attendee_password;
            //$password = $permissions[$role]['defaultRole'] == 'moderator'? $found->moderatorPW: $found->attendeePW;

        }

        //Extra parameters
        $recorded = $bbb_is_recorded;
        $welcome = $bbb_room_welcome_msg;
        $duration = 0;
        $voicebridge = 0;
        $logouturl = (is_ssl()? "https://": "http://") . $_SERVER['HTTP_HOST']  . $_SERVER['REQUEST_URI'];

        //Metadata for tagging recordings
        $metadata = Array(
            'meta_origin' => 'WordPress',
            'meta_originversion' => $wp_version,
            'meta_origintag' => 'wp_plugin-bigbluebutton '.BIGBLUEBUTTON_PLUGIN_VERSION,
            'meta_originservername' => home_url(),
            'meta_originservercommonname' => get_bloginfo('name'),
            'meta_originurl' => $logouturl
        );
        
//Call for creating meeting on the bigbluebutton server
        $response = BigBlueButton::createMeetingArray($name, $meetingID, $bbb_meeting_name, $bbb_room_welcome_msg, $bbb_moderator_password, $bbb_attendee_password, $salt_val, $url_val, $logouturl, $recorded? 'true':'false', $duration, $voicebridge, $metadata );

        //Analyzes the bigbluebutton server's response
        if(!$response || $response['returncode'] == 'FAILED' ){//If the server is unreachable, or an error occured
            $out .= "Sorry an error occured while joining the meeting.";
            //return $out;

        } else{ //The user can join the meeting, as it is valid

            if( !isset($response['messageKey']) || $response['messageKey'] == '' ){
                // The meeting was just created, insert the create event to the log
                //$rows_affected = $wpdb->insert( $table_logs_name, array( 'meetingID' => $found->meetingID, 'recorded' => $found->recorded, 'timestamp' => time(), 'event' => 'Create' ) );
            }

            $bigbluebutton_joinURL = BigBlueButton::getJoinURL($meetingID, $name, $password, $salt_val, $url_val );
            //If the meeting is already running or the moderator is trying to join or a viewer is trying to join and the
            //do not wait for moderator option is set to false then the user is immediately redirected to the meeting
            if ( (BigBlueButton::isMeetingRunning( $meetingID, $url_val, $salt_val ) && ($bbb_moderator_password == $password || $bbb_attendee_password == $password ) )
                    || $response['moderatorPW'] == $password
                    || ($response['attendeePW'] == $password && !$bbb_must_wait_for_admin_start)  ){
                //If the password submitted is correct then the user gets redirected

                //Note: change this to click action on button rather than direct link
                $out .= '
                            <p><a class="bbb" style="display:inline-block;border-radius:5px;padding:20px;border:1px solid #1862DB;background-color:#C3D7F7" href="'.$bigbluebutton_joinURL .'" target="_blank">Enter BBB Room</a></p>
                        ';
                //return $out;
            }
            //If the viewer has the correct password, but the meeting has not yet started they have to wait
            //for the moderator to start the meeting
            else if ($bbb_attendee_password == $password) {
                //Stores the url and salt of the bigblubutton server in the session
                $_SESSION['mt_bbb_url'] = $url_val;
                $_SESSION['mt_salt'] = $salt_val;
                //Displays the javascript to automatically redirect the user when the meeting begins
                $out .= bigbluebutton_display_redirect_script($bigbluebutton_joinURL, $meetingID, $bbb_meeting_name, $name);
                //return $out;
            }
        }
        
    
  }
  //$room_button = '<p><a class="bbb" style="display:inline-block;border-radius:5px;padding:20px;border:1px solid #1862DB;background-color:#C3D7F7" href="#">Enter BBB Room</a></p>';
  
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
    
    add_action('admin_notices', 'update_notice');
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
        <table>
            <tr>
                <th>URL of BBB Server</th>
                <td>
                    <input type="text" size="56" name="bbb_url" value="<?php echo $bbb_settings['bbb_url']; ?>" />
                    <p>Example: http://test-install.blindsidenetworks.com/bigbluebutton/</p>
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
function bigbluebutton_display_redirect_script($bigbluebutton_joinURL, $meetingID, $meetingName, $name){
    $out = '
    <script type="text/javascript">
        function bigbluebutton_ping() {
            jQuery.ajax({
                url : "/wp-content/plugins/bbb-custom-post-type/php/broker.php?action=ping&meetingID='.urlencode($meetingID).'",
                async : true,
                dataType : "xml",
                success : function(xmlDoc){
                    $xml = jQuery( xmlDoc ), $running = $xml.find( "running" );
                    if($running.text() == "true"){
                        window.location = "'.$bigbluebutton_joinURL.'";
                    }
                },
                error : function(xmlHttpRequest, status, error) {
                    console.debug(xmlHttpRequest);
                }
            });

        }

        setInterval("bigbluebutton_ping()", 5000);
    </script>';

    $out .= '
    <table>
      <tbody>
        <tr>
          <td>
            Welcome '.$name.'!<br /><br />
            '.$meetingName.' session has not been started yet.<br /><br />
            <div align="center"><img src="./wp-content/plugins/bigbluebutton/images/polling.gif" /></div><br />
            (Your browser will automatically refresh and join the meeting when it starts.)
          </td>
        </tr>
      </tbody>
    </table>';

    return $out;
}



//================================================================================
//------------------------------- Helping functions ------------------------------
//================================================================================
//Validation methods


function bigbluebutton_can_participate($role){
    $permissions = get_option('bigbluebutton_permissions');
    if( $role == 'unregistered' ) $role = 'anonymous';
    return ( isset($permissions[$role]['participate']) && $permissions[$role]['participate'] );

}

function bigbluebutton_can_manageRecordings($role){
    $permissions = get_option('bigbluebutton_permissions');
    if( $role == 'unregistered' ) $role = 'anonymous';
    return ( isset($permissions[$role]['manageRecordings']) && $permissions[$role]['manageRecordings'] );

}

function bigbluebutton_validate_defaultRole($wp_role, $bbb_role){
    $permissions = get_option('bigbluebutton_permissions');
    if( $wp_role == null || $wp_role == 'unregistered' || $wp_role == '' )
        $role = 'anonymous';
    else
        $role = $wp_role;
    return ( isset($permissions[$role]['defaultRole']) && $permissions[$role]['defaultRole'] == $bbb_role );
}

function bigbluebutton_generateToken($tokenLength=6){
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

function bigbluebutton_generatePasswd($numAlpha=6, $numNonAlpha=2, $salt=''){
    $listAlpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $listNonAlpha = ',;:!?.$/*-+&@_+;./*&?$-!,';
    
    $pepper = '';
    do{
        $pepper = str_shuffle( substr(str_shuffle($listAlpha),0,$numAlpha) . substr(str_shuffle($listNonAlpha),0,$numNonAlpha) );
    } while($pepper == $salt);
    
    return $pepper;
}

function bigbluebutton_normalizeMeetingID($meetingID){
    return (strlen($meetingID) == 12)? sha1(home_url().$meetingID): $meetingID;
}



?>