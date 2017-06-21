<?php
/*
Plugin Name: BigBlueButton Rooms
Plugin URI: http://blindsidenetworks.com/integration
Description: BigBlueButton is an open source web conferencing system. This plugin integrates BigBlueButton into WordPress allowing bloggers to create and manage meetings rooms by using a Custom Post Type. For more information on setting up your own BigBlueButton server or for using an external hosting provider visit http://bigbluebutton.org/support
Version: 0.3.1
Author: Blindside Networks
Author URI: http://blindsidenetworks.com/
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/
/*
Releases:
    0.3.0  --  Written by Steve Puddick
                - Original version
    0.3.1  --  Updated and Extended by Jesus Federico
                - Rebranded and standarized code
                - Set up a default bbb server configuration
                - Fixed the wait for moderator to join
                - Added "open in a new window feature"
*/
//validate
global $wp_version;
$exit_msg = 'This plugin has been designed for Wordpress 2.5 and later, please upgrade your current one.';
if (version_compare($wp_version, '2.5', '<')) {
    exit($exit_msg);
}
register_activation_hook(__FILE__, 'bigbluebutton_custom_post_type_install');
function bigbluebutton_custom_post_type_install()
{
    $bigbluebutton_custom_post_type_settings = get_option('bigbluebutton_custom_post_type_settings');
    if (!isset($bigbluebutton_custom_post_type_settings)) {
        $bigbluebutton_custom_post_type_settings['endpoint'] = 'http://test-install.blindsidenetworks.com/bigbluebutton/';
        $bigbluebutton_custom_post_type_settings['secret'] = '8cd8ef52e8e101574e400365b55e11a6';
    } else {
        if (!isset($bigbluebutton_custom_post_type_settings['endpoint'])) {
            $bigbluebutton_custom_post_type_settings['endpoint'] = 'http://test-install.blindsidenetworks.com/bigbluebutton/';
        }
        if (!isset($bigbluebutton_custom_post_type_settings['secret'])) {
            $bigbluebutton_custom_post_type_settings['secret'] = '8cd8ef52e8e101574e400365b55e11a6';
        }
    }
    update_option('bigbluebutton_custom_post_type_settings', $bigbluebutton_custom_post_type_settings);
}
//constant definition
define('BIGBLUEBUTTON_CUSTOM_POST_TYPE_PLUGIN_VERSION', bigbluebutton_custom_post_type_get_version());
require_once 'includes/bbb_api.php';
add_action('init', 'myStartSession', 1);
function myStartSession()
{
    if (!session_id()) {
        session_start();
    }
}
/*
* This displays any notices that may be stored in $_SESSION
*/
function my_admin_notices()
{
    if (!empty($_SESSION['my_admin_notices'])) {
        print  $_SESSION['my_admin_notices'];
    }
    unset($_SESSION['my_admin_notices']);
}
add_action('admin_notices', 'my_admin_notices');
/*
 * This displays some CSS we need for the BigBlueButton Custom Post Type plugin, in the backend
 */
function bigbluebutton_custom_post_type_css_enqueue()
{
    $bigbluebutton_custom_post_type_style = plugins_url('css/bigbluebutton_custom_post_type.css', __FILE__);
    wp_register_style('bigbluebutton_custom_post_type_style', $bigbluebutton_custom_post_type_style);
    wp_enqueue_style('bigbluebutton_custom_post_type_style');
}
add_action('init', 'bigbluebutton_custom_post_type_css_enqueue');


function bigbluebutton_custom_post_type_scripts()
{
  wp_enqueue_style('bigbluebutton_custom_post_type_front-end', plugins_url('css/bigbluebutton_custom_post_type_front-end.css', __FILE__));
}
add_action('init', 'bigbluebutton_custom_post_type_scripts');

/*******************************
BBB ROOM CUSTOM POST TYPE DECLARATION
********************************/
function bigbluebutton_custom_post_type_init()
{
    $singular = 'BBB Room';
    $plural = 'BBB Rooms';
    $labels = array(
        'name'               => _x($plural, 'post type general name'),
        'singular_name'      => _x($singular, 'post type singular name'),
        'add_new'            => _x('Add New BBB Room', 'bbb'),
        'add_new_item'       => __('Add New '.$singular),
        'edit_item'          => __('Edit '.$singular),
        'new_item'           => __('New '.$singular),
        'all_items'          => __('All '.$plural),
        'view_item'          => __('View '.$singular),
        'search_items'       => __('Search '.$plural),
        'not_found'          => __('No '.$plural.' found'),
        'not_found_in_trash' => __('No '.$plural.' found in Trash'),
        'parent_item_colon'  => '',
        'menu_name'          => $plural,
    );
    $args = array(
        'labels'               => $labels,
        'public'               => true,
        'publicly_queryable'   => true,
        'show_ui'              => true,
        'show_in_menu'         => true,
        'query_var'            => true,
        'rewrite'              => array('slug' => 'bbb-room', 'with_front' => false),
        'capability_type'      => 'bbb-room',
        'capabilities'         => array(
          'edit_posts'         => 'edit_rooms_own_bbb-room',
          'edit_others_posts'  => 'edit_rooms_all_bbb-room',
          'delete_posts'       => 'delete_rooms_own_bbb-room',
          'delete_others_posts'=> 'delete_rooms_all_bbb-room',
          'read_private_posts' => 'read_rooms_bbb-room',
          'publish_posts'      => 'publish_recordings_all_bbb-room',
          'publish_post'       => 'publish_recordings_own_bbb-room',
          'create_rooms'       => 'edit_plugins_bbb-room'
        ),
        'map_meta_cap'         => true,
        'has_archive'          => true,
        'hierarchical'         => false,
        'menu_position'        => null,
        'supports'             => array('title', 'editor', 'page-attributes', 'author'),
    );
    register_post_type('bbb-room', $args);
  /*
    notice how we have set 'capability_type' => 'bbb-room'. This allows us to use wordpress's built in permission/role system
    rather than creating our own. This makes everything much easier. We will need an additional plugin such as http://wordpress.org/plugins/members/
    to manage the permissions and roles
   *
   */
}
add_action('init', 'bigbluebutton_custom_post_type_init',0);

/**
* Used http://justintadlock.com/archives/2010/07/10/meta-capabilities-for-custom-post-types code as per below
* This function is basically mapping all the initilized capabilies and putting it in the array (detailed functionality can be seen in the above link)
*/
function bbb_map_meta_cap($cap, $user_id) {
  global $post;

  $args = array_slice( func_get_args(), 2 );
  $caps = array();
//edit_rooms_own_bbb-room //delete_rooms_own_bbb-room //read_rooms_bbb-room
if ( "edit_rooms_own" == $cap[0] || "delete_rooms_own" == $cap[0] || "read_rooms" == $cap[0] ) {
//  if ( "edit_rooms_own_bbb-room" == $cap[0] || "delete_rooms_own_bbb-room" == $cap[0] || "read_rooms_bbb-room" == $cap[0] ) {
		$post = get_post( $args[0] );
		$post_type = get_post_type_object( $post->post_type );
	}

if ( 'edit_room_own' == $cap[0] ) {
	//if ( 'edit_rooms_own_bbb-room' == $cap[0] ) {
		if ( $user_id == $post->post_author ){
			$caps[] = $post_type->cap->edit_posts;
    }
		else
    {
			$caps[] = $post_type->cap->edit_others_posts;
    }
	}

elseif ( 'delete_recordings_own' == $cap[0] ) {
//  elseif ( 'delete_rooms_own_bbb-room' == $cap[0] ) {
		if ( $user_id == $post->post_author )
			$caps[] = $post_type->cap->delete_posts;
		else
			$caps[] = $post_type->cap->delete_others_posts;
	}

 elseif ( 'read_bbb-room' == $cap[0] ) {
  //elseif ( 'read_rooms_bbb-room' == $cap[0] ) {
    if ( 'private' != $post->post_status )
      $caps[] = 'read';
    elseif ( $user_id == $post->post_author )
      $caps[] = 'read';
    else
      $caps[] = $post_type->cap->read_private_posts;
  }
   return $caps;
}
add_filter( 'map_meta_cap', 'bbb_map_meta_cap', 10, 4);
/*
 * ***********************************
 * BBB ROOM TAXONOMY
 * ***********************************
 *
 * */
function build_bbb_room_taxonomies()
{
    $singular = 'BBB Room';
    $labels = array(
        'name'                       => _x($singular.' Categories', 'taxonomy general name'),
        'singular_name'              => _x($singular.' Category', 'taxonomy singular name'),
        'search_items'               => __('Search '.$singular.' Categories'),
        'popular_items'              => __('Popular '.$singular.'  Categories'),
        'all_items'                  => __('All '.$singular.'  Categories'),
        'parent_item'                => null,
        'parent_item_colon'          => null,
        'edit_item'                  => __('Edit '.$singular.' Category'),
        'update_item'                => __('Update '.$singular.' Category'),
        'add_new_item'               => __('Add New '.$singular.' Category'),
        'new_item_name'              => __('New '.$singular.' Category Name'),
        'separate_items_with_commas' => __('Separate '.$singular.' categories with commas'),
        'add_or_remove_items'        => __('Add or remove '.$singular.' categories'),
        'choose_from_most_used'      => __('Choose from the most used '.$singular.' categories'),
        'menu_name'                  => __($singular.' Categories'),
    );
    register_taxonomy('bbb-room-category', array('bbb-room'), array(
            'hierarchical'                    => true,
            'labels'                          => $labels,
            'show_ui'                         => true,
            'update_count_callback'           => '_update_post_term_count',
            'query_var'                       => true,
            'hierarchical'                    => true,
            'rewrite'                         => array('slug' => 'bbb-room-category'),
            'capabilities'                    => array(
                    'manage_terms' => 'manage_bbb-cat',
                    'edit_terms'   => 'edit_bbb-cat',
                    'delete_terms' => 'delete_bbb-cat',
                    'assign_terms' => 'assign_bbb-cat', ),
    ));
    /*
     * Again, we will need an additional plugin such as http://wordpress.org/plugins/members/  to map the bbb-room-category
     * capabilities to roles
     */
}
add_action('init', 'build_bbb_room_taxonomies', 0);

/**
* Adding default roles
*/
function bbb_default_roles()
{
    $adminRole = get_role('administrator');

    $authorRole = get_role('author');
    $authorRole->add_cap($adminRole);

    $contributorRole = get_role('contributor');
    $contributorRole->add_cap($adminRole);

    $editorRole = get_role('editor');
    $editorRole->add_cap($adminRole);

    $subscriberRole = get_role('subscriber');
    $subscriberRole->add_cap('read_private_posts');

}
add_action('admin_init', 'bbb_default_roles');


function my_error_notice() {
$screen = get_current_screen();
   if ( $screen->id == 'bbb-room' ) {
    ?>
    <div class="notice notice-warning is-dismissible">
  	<p><strong>To change the default capabilities for each user, please install the "Members" plugin.</strong></p>
    </div>
    <?php
 }
}
add_action( 'admin_notices', 'my_error_notice' );
/*
 * Content for the 'Room Details' box
 */
function bigbluebutton_custom_post_type_room_details_metabox($post)
{
    wp_nonce_field(basename(__FILE__), 'bbb_rooms_nonce');
    $bbb_attendee_password = get_post_meta($post->ID, '_bbb_attendee_password', true);
    $bbb_moderator_password = get_post_meta($post->ID, '_bbb_moderator_password', true);
    $bbb_must_wait_for_admin_start = get_post_meta($post->ID, '_bbb_must_wait_for_admin_start', true);
    $bbb_is_recorded = get_post_meta($post->ID, '_bbb_is_recorded', true);
    $bbb_room_token = get_post_meta($post->ID, '_bbb_room_token', true);
    $bbb_room_welcome_msg = get_post_meta($post->ID, '_bbb_room_welcome_msg', true); ?>
    <table class='custom-admin-table'>
        <tr>
            <th>Attendee Password</th>
            <td>
                <input type="text" name='bbb_attendee_password' id="bbb_attendee_password" class='' value='<?php echo $bbb_attendee_password; ?>' />
            </td>
        </tr>
        <tr>
            <th>Moderator Password</th>
            <td>
                <input type="text" name='bbb_moderator_password'  class='' value='<?php echo $bbb_moderator_password; ?>' />
            </td>
        </tr>
        <tr>
            <th>Wait for Admin to start meeting?</th>
            <td>
               	<?php // echo $bbb_must_wait_for_admin_start;?>
               	<input type="radio" name='bbb_must_wait_for_admin_start' id="bbb_must_wait_for_admin_start_yes" value="1" <?php if (!$bbb_must_wait_for_admin_start || $bbb_must_wait_for_admin_start == '1') {
        echo "checked='checked'";
    } ?> /><label for="bbb_must_wait_for_admin_start_yes" >Yes</label>
		<input type="radio" name='bbb_must_wait_for_admin_start' id="bbb_must_wait_for_admin_start_no" value="0" <?php if ($bbb_must_wait_for_admin_start == '0') {
        echo "checked='checked'";
    } ?> /><label for="bbb_must_wait_for_admin_start_no" >No</label>
            </td>
        </tr>
        <tr>
            <th>Record meeting?</th>
            <td>
		<input type="radio" name='bbb_is_recorded' id="bbb_is_recorded_yes" value="1" <?php if (!$bbb_is_recorded || $bbb_is_recorded == '1') {
        echo "checked='checked'";
    } ?> /><label for="bbb_is_recorded_yes" >Yes</label>
                <input type="radio" name='bbb_is_recorded' id="bbb_is_recorded_no" value="0" <?php if ($bbb_is_recorded == '0') {
        echo "checked='checked'";
    } ?> /><label for="bbb_is_recorded_no" >No</label>
            </td>
        </tr>
        <tr>
            <th>Room Token</th>
            <td>
                <p>The room token is set when the post is saved. This is not editable.</p>
                <input type="hidden" name="bbb_room_token" value="<?php echo $bbb_room_token ? $bbb_room_token : 'Token Not Set'; ?>">
                <p>Room Token: <strong><?php echo $bbb_room_token ? $bbb_room_token : 'Token Not Set'; ?></strong></p>
            </td>
        </tr>
        <tr>
            <th>Room Welcome Msg</th>
            <td>
                <textarea name='bbb_room_welcome_msg' ><?php echo $bbb_room_welcome_msg; ?></textarea>

            </td>
        </tr>
	</table>
	<input type="hidden" name="bbb-noncename" id="bbb-noncename" value="<?php echo wp_create_nonce('bbb'); ?>" />

	<?php
}
function bigbluebutton_custom_post_type_room_recordings_metabox($post)
{
    /*
     * Rooms recordings that are specific to this bbb post (and subsquently the meetingID associated with the bbb post)
     * will be listed here
     */
    echo bigbluebutton_custom_post_type_list_room_recordings($post->ID);
}

function bigbluebutton_custom_post_type_room_status_metabox($post)
{
    global $wp_version, $current_site, $current_user, $wp_roles, $post;

    $out = '';
    $bigbluebutton_custom_post_type_settings = get_option('bigbluebutton_custom_post_type_settings');
    $endpoint_val = $bigbluebutton_custom_post_type_settings['endpoint'];
    $secret_val = $bigbluebutton_custom_post_type_settings['secret'];
    $bbb_attendee_password = get_post_meta($post->ID, '_bbb_attendee_password', true);
    $bbb_moderator_password = get_post_meta($post->ID, '_bbb_moderator_password', true);
    $bbb_must_wait_for_admin_start = get_post_meta($post->ID, '_bbb_must_wait_for_admin_start', true);
    $bbb_is_recorded = get_post_meta($post->ID, '_bbb_is_recorded', true);
    $bbb_room_token = get_post_meta($post->ID, '_bbb_room_token', true);
    $bbb_room_welcome_msg = get_post_meta($post->ID, '_bbb_room_welcome_msg', true);
    $bbb_meeting_name = get_the_title($post->ID);
    $meetingID = $bbb_room_token;
    $meetingID = bigbluebutton_custom_post_type_normalizeMeetingID($meetingID);

    if($_POST['SubmitList'] == 'End Meeting Now' ) {
      $response = BigBlueButton::endMeeting(bigbluebutton_custom_post_type_normalizeMeetingID($_POST['bbb_room_token']), $_POST['bbb_moderator_password'], $endpoint_val, $secret_val );
    }

    $recorded = $bbb_is_recorded;
    $duration = 0;
    $voicebridge = 0;
    $logouturl = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'?logout=true';
    //Metadata for tagging recordings
    $metadata = array(
       'meta_origin'                 => 'WordPress',
       'meta_originversion'          => $wp_version,
       'meta_origintag'              => 'wp_plugin-bigbluebutton_custom_post_type '.BIGBLUEBUTTON_CUSTOM_POST_TYPE_PLUGIN_VERSION,
       'meta_originservername'       => home_url(),
       'meta_originservercommonname' => get_bloginfo('name'),
       'meta_originurl'              => $logouturl,
    );

    if ($current_user->allcaps["edit_bbb-cat"]) {
      $password = $bbb_moderator_password;
    }
    else if($current_user->allcaps["read"]) {
      $password = $bbb_attendee_password;
    }

    if (!$current_user->ID) {
         $out .= '<div class="login-box" style="background-color:#eee;padding:20px;margin-bottom:20px;" >';
         if (get_option('users_can_register')) {
             $out .= '<p>Please login or register to access this room and view room recordings:</p>';
             $out .= wp_register('<p>', '</p>', false);
         } else {
             $out .= '<p>Only registered users can access rooms and view room recordings:</p>';
         }
         $out .= '</div>';
     } else {
         if ($current_user->display_name != '') {
             $name = $current_user->display_name;
         } elseif ($current_user->user_firstname != '' || $current_user->user_lastname != '') {
             $name = $current_user->user_firstname != '' ? $current_user->user_firstname.' ' : '';
             $name .= $current_user->user_lastname != '' ? $current_user->user_lastname.' ' : '';
         } elseif ($current_user->user_login != '') {
             $name = $current_user->user_login;
         } else {
             $name = $role;
         }
    }
    if(get_post_status($post->ID)==='publish'){

      $response = BigBlueButton::createMeetingArray($name, $meetingID, $bbb_meeting_name, $bbb_room_welcome_msg, $bbb_moderator_password, $bbb_attendee_password, $secret_val, $endpoint_val, $logouturl, $recorded ? 'true' : 'false', $duration, $voicebridge, $metadata);

      if (!$response || $response['returncode'] == 'FAILED') {
          $out .= 'Sorry an error occured while creating the meeting room.';
      } else {
          $bigbluebutton_custom_post_type_joinURL = BigBlueButton::getJoinURL($meetingID, $name, $password, $secret_val, $endpoint_val);
          $button_text = 'Join';
          $out .= '<input type="button" style=" left: 0;padding: 5x 100px;" class="button-primary" value="'.$button_text.'"  onClick="window.open(\''.$bigbluebutton_custom_post_type_joinURL.'\'); setTimeout(function() {document.location.reload(true);}, 5000);" />';
      }
  }
   if (!BigBlueButton::isMeetingRunning($meetingID, $endpoint_val, $secret_val)) {

   } else {
       $out .= '<input type="submit" name="SubmitList" style="position: absolute; left: 70px;padding: 5x;" class="button-primary" value="End Meeting Now" />&nbsp';
   }
  echo $out;
}
add_action('save_post', 'bigbluebutton_custom_post_type_room_status_metabox',999);

/*
 * This adds the 'Room Details' box and 'Room Recordings' box below the main content
* area in a BigBlueButton Custom Post Type post
*/
function bigbluebutton_custom_post_type_meta_boxes()
{
    add_meta_box('room-details', __('Room Details'),  'bigbluebutton_custom_post_type_room_details_metabox', 'bbb-room', 'normal', 'low');
    add_meta_box('room-recordings', __('Room Recordings'),  'bigbluebutton_custom_post_type_room_recordings_metabox', 'bbb-room', 'normal', 'low');
    add_meta_box('room-status', __('Room Status'),  'bigbluebutton_custom_post_type_room_status_metabox', 'bbb-room', 'normal', 'low');
}
add_action('add_meta_boxes', 'bigbluebutton_custom_post_type_meta_boxes');
// Add to admin_init function
function save_bbb_data($post_id)
{
    $bbb_attendee_password = get_post_meta($post_id, '_bbb_attendee_password', true);
    $bbb_moderator_password = get_post_meta($post_id, '_bbb_moderator_password', true);
    $bigbluebutton_custom_post_type_settings = get_option('bigbluebutton_custom_post_type_settings');
    $endpoint_val = $bigbluebutton_custom_post_type_settings['endpoint'];
    $secret_val = $bigbluebutton_custom_post_type_settings['secret'];
    $new_nonce = wp_create_nonce('bbb');

    if ($_POST['"bbb-noncename'] == $new_nonce) {
        return $post_id;
    }
    // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return $post_id;
    }

    if (!current_user_can('edit_bbb-rooms', $post_id)) {
        return $post_id;
    }
    $post = get_post($post_id);
    if ($post->post_type == 'bbb-room') {
        $token = get_post_meta($post->ID, '_bbb_room_token', true);
        // Assign a random seed to generate unique ID on a BBB server
        if (!$token) {
            $meetingID = bigbluebutton_custom_post_type_generateToken();
            update_post_meta($post_id, '_bbb_room_token', $meetingID);
        }
        $attendeePW = bigbluebutton_custom_post_type_generatePasswd(6, 2);
        $moderatorPW = bigbluebutton_custom_post_type_generatePasswd(6, 2, $attendeePW);

        if(empty($_POST['bbb_attendee_password']) && (get_post_status($post->ID)==='publish'))
        {
          update_post_meta($post_id, '_bbb_attendee_password',$attendeePW);//random generated
        }
        else {
          update_post_meta($post_id, '_bbb_attendee_password', esc_attr($_POST['bbb_attendee_password']));
        }

        if(empty($_POST['bbb_moderator_password']) && (get_post_status($post->ID)==='publish')){
          update_post_meta($post_id, '_bbb_moderator_password',$moderatorPW);//random generated
        }
        else {
          update_post_meta($post_id, '_bbb_moderator_password', esc_attr($_POST['bbb_moderator_password']));
       }

       if(($bbb_moderator_password !== $_POST['bbb_moderator_password']) || ($bbb_attendee_password !== $_POST['bbb_attendee_password'])){
         $response = BigBlueButton::endMeeting(bigbluebutton_custom_post_type_normalizeMeetingID($_POST['bbb_room_token']), $bbb_moderator_password, $endpoint_val, $secret_val );
        }

        update_post_meta($post_id, '_bbb_must_wait_for_admin_start', esc_attr($_POST['bbb_must_wait_for_admin_start']));
        update_post_meta($post_id, '_bbb_is_recorded', esc_attr($_POST['bbb_is_recorded']));
        update_post_meta($post_id, '_bbb_room_welcome_msg', esc_attr($_POST['bbb_room_welcome_msg']));
    }
    return $post_id;
}
add_action('save_post', 'save_bbb_data');

function before_bbb_delete()
{
    /*
     * If we want to do anything when the BBB post in wordpress is deleted, we can hook into here.
     */
}
add_action('before_delete_post', 'before_bbb_delete');


/*
 * CONTENT FILTER TO ADD BBB BUTTON
 */
function bigbluebutton_custom_post_type_filter($content)
{

    /*
     * Target only bbb-room post type, and on the 'single' page (not archive)
     *
     * If we do not meet these requirements, output the content as usual.
     *
     * If we do meet requirements, a button to go to the room will be attached at the bottom of the
     * regular page content
     */
    if ('bbb-room' == get_post_type($post) && is_single()) {
        global $wp_version, $current_site, $current_user, $wp_roles, $post;
        $current_user = wp_get_current_user();

        //Initializes the variable that will collect the output
        $out = '';
        $bigbluebutton_custom_post_type_settings = get_option('bigbluebutton_custom_post_type_settings');
        //Read in existing option value from database
        $endpoint_val = $bigbluebutton_custom_post_type_settings['endpoint'];
        $secret_val = $bigbluebutton_custom_post_type_settings['secret'];
        $bbb_attendee_password = get_post_meta($post->ID, '_bbb_attendee_password', true);
        $bbb_moderator_password = get_post_meta($post->ID, '_bbb_moderator_password', true);
        $bbb_must_wait_for_admin_start = get_post_meta($post->ID, '_bbb_must_wait_for_admin_start', true);
        $bbb_is_recorded = get_post_meta($post->ID, '_bbb_is_recorded', true);
        $bbb_room_token = get_post_meta($post->ID, '_bbb_room_token', true);
        $bbb_room_welcome_msg = get_post_meta($post->ID, '_bbb_room_welcome_msg', true);
        $bbb_meeting_name = get_the_title($post->ID);
        $meetingID = $bbb_room_token;
        $meetingID = bigbluebutton_custom_post_type_normalizeMeetingID($meetingID);

        if (!$current_user->ID) {
            /*
             * Right now no functionality is present to handle user's who are not logged in. That functionality
             * will go here
             *
             * $name = $_POST['display_name'];
             * $password = $_POST['pwd'];
            */
            $out = '<div class="login-box" style="background-color:#eee;padding:20px;margin-bottom:20px;" >';
            if (get_option('users_can_register')) {
                $out .= '<p>Please login or register to access this room and view room recordings:</p>';
                $out .= wp_register('<p>', '</p>', false);
            } else {
                $out .= '<p>Only registered users can access rooms and view room recordings:</p>';
            }
            $out .= '</div>';
        } else {
            if ($current_user->display_name != '') {
                $name = $current_user->display_name;
            } elseif ($current_user->user_firstname != '' || $current_user->user_lastname != '') {
                $name = $current_user->user_firstname != '' ? $current_user->user_firstname.' ' : '';
                $name .= $current_user->user_lastname != '' ? $current_user->user_lastname.' ' : '';
            } elseif ($current_user->user_login != '') {
                $name = $current_user->user_login;
            } else {
                $name = $role;
            }
            /*
             * To make things easier and allow deeper integration with other plugins, we are using wordpress's
             * built in permission and capability functions rather than something custom. For more info check out:
             * http://codex.wordpress.org/Function_Reference/current_user_can
             */

            if ($current_user->allcaps["edit_bbb-cat"]) {
              $password = $bbb_moderator_password;
            }
            else if($current_user->allcaps["read"]) {
              $password = $bbb_attendee_password;
            }

            //Extra parameters
            $recorded = $bbb_is_recorded;
            $duration = 0;
            $voicebridge = 0;
            $logouturl = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'?logout=true';
            //Metadata for tagging recordings
            $metadata = array(
                'meta_origin'                 => 'WordPress',
                'meta_originversion'          => $wp_version,
                'meta_origintag'              => 'wp_plugin-bigbluebutton_custom_post_type '.BIGBLUEBUTTON_CUSTOM_POST_TYPE_PLUGIN_VERSION,
                'meta_originservername'       => home_url(),
                'meta_originservercommonname' => get_bloginfo('name'),
                'meta_originurl'              => $logouturl,
            );
            //Call for creating meeting on the bigbluebutton_custom_post_type server
            $response = BigBlueButton::createMeetingArray($name, $meetingID, $bbb_meeting_name, $bbb_room_welcome_msg, $bbb_moderator_password, $bbb_attendee_password, $secret_val, $endpoint_val, $logouturl, $recorded ? 'true' : 'false', $duration, $voicebridge, $metadata);

            if (!$response || $response['returncode'] == 'FAILED') {
                //If the server is unreachable, or an error occured
                $out .= "<p class='error'>".__('Sorry an error occured while creating the meeting room.', 'bbb').'</p>';
            } else { //The user can join the meeting, as it is valid
                $bigbluebutton_custom_post_type_joinURL = BigBlueButton::getJoinURL($meetingID, $name, $password, $secret_val, $endpoint_val);
                //If the meeting is already running or the moderator is trying to join or a viewer is trying to join and the
                //do not wait for moderator option is set to false then the user is immediately redirected to the meeting
                /*
                 * At the moment BigBlueButton::isMeetingRunning always returns false which only allows users to join in certain cases
                 */
                if ((BigBlueButton::isMeetingRunning($meetingID, $endpoint_val, $secret_val) && ($bbb_moderator_password == $password || $bbb_attendee_password == $password))
                        || $response['moderatorPW'] == $password
                        || ($response['attendeePW'] == $password && !$bbb_must_wait_for_admin_start)) {
                        if ($bbb_moderator_password == $password) {
                            $button_text = 'Join Room as Moderator';
                           $out .= '<a href="'.$bigbluebutton_custom_post_type_joinURL.'"><button>'.$button_text.'</button></a>';
                        } elseif ($bbb_attendee_password == $password) {
                            $button_text = 'Join Room as Attendee';
                            $out .= '<a href="'.$bigbluebutton_custom_post_type_joinURL.'"><button>'.$button_text.'</button></a>';
                        }

                }
                //If the viewer has the correct password, but the meeting has not yet started they have to wait
                //for the moderator to start the meeting
                elseif ($bbb_attendee_password == $password) {
                    //Stores the url and salt of the bigblubutton server in the session
                    $_SESSION['mt_bbb_endpoint'] = $endpoint_val;
                    $_SESSION['mt_bbb_salt'] = $secret_val;
                    //Displays the javascript to automatically redirect the user when the meeting begins
                    $out .= '<div id="bbb-join-container"></div>';
                    $out .= bigbluebutton_custom_post_type_display_reveal_script($bigbluebutton_custom_post_type_joinURL, $meetingID, $bbb_meeting_name, $name);
                }
            }
            $out .= bigbluebutton_custom_post_type_list_room_recordings($post->ID);
        }
    }
  /*
   * Show a listing of the recordings below the content and the 'join room' button.
   * At the moment the listing of recordings is not working.
   */
  return $content.$out;
}
add_filter('the_content', 'bigbluebutton_custom_post_type_filter');


//should also check to make sure we are on the BBB settings page
if (is_admin() && (isset($_POST['endpoint']) || isset($_POST['secret']))) {
    $bigbluebutton_custom_post_type_settings = get_option('bigbluebutton_custom_post_type_settings');
    $do_update = 0;
    if (isset($_POST['endpoint']) && ($bigbluebutton_custom_post_type_settings['endpoint'] != $_POST['endpoint'])) {
        $bigbluebutton_custom_post_type_settings['endpoint'] = $_POST['endpoint'];
        $do_update = 1;
    }
    if (isset($_POST['secret']) && ($bigbluebutton_custom_post_type_settings['secret'] != $_POST['secret'])) {
        $bigbluebutton_custom_post_type_settings['secret'] = $_POST['secret'];
        $do_update = 1;
    }
    if ($do_update) {
        $update_response = update_option('bigbluebutton_custom_post_type_settings', $bigbluebutton_custom_post_type_settings);
        if ($update_response) {
            add_action('admin_notices', 'bigbluebutton_custom_post_type_update_notice_success');
        } else {
            add_action('admin_notices', 'bigbluebutton_custom_post_type_update_notice_fail');
        }
    } else {
        add_action('admin_notices', 'bigbluebutton_custom_post_type_update_notice_no_change');
    }
}
function bigbluebutton_custom_post_type_update_notice_success()
{
    echo '<div class="updated">
       <p>BigBlueButton options have been updated.</p>
    </div>';
}
function bigbluebutton_custom_post_type_update_notice_fail()
{
    echo '<div class="error">
       <p>BigBlueButton options failed to update.</p>
    </div>';
}
function bigbluebutton_custom_post_type_update_notice_no_change()
{
    echo '<div class="updated">
       <p>BigBlueButton options have not changed.</p>
    </div>';
}
/*
 *  OPTIONS PAGE
 */
function bigbluebutton_custom_post_type_options_page_callback()
{
    $bigbluebutton_custom_post_type_settings = get_option('bigbluebutton_custom_post_type_settings'); ?>
    <div class="wrap">
    <div id="icon-options-general" class="icon32"><br /></div><h2>BigBlueButton Settings</h2>
    <form  action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post" name="site_options_page" >
        <table class="custom-admin-table">
            <tr>
                <th>BigBlueButton Server Endpoint</th>
                <td>
                    <input type="text" size="56" name="endpoint" value="<?php echo $bigbluebutton_custom_post_type_settings['endpoint']; ?>" />
                    <p>Example: http://test-install.blindsidenetworks.com/bigbluebutton/</p>
                </td>
            </tr>
            <tr>
                <th>BigBlueButton Server Shared Secret</th>
                <td>
                    <input type="text" size="56" name="secret" value="<?php echo $bigbluebutton_custom_post_type_settings['secret']; ?>" />
                    <p>Example: 8cd8ef52e8e101574e400365b55e11a6</p>
                </td>
            </tr>
            <tr>
                <th></th>
                <td><input type="submit" class="button-primary" value="Save Settings" /></td>
            </tr>
        </table>
    </form>
    </div>
<?php
}
function register_site_options_page()
{
    add_submenu_page('options-general.php', 'Site Options', 'BigBlueButton', 'edit_pages', 'site-options', 'bigbluebutton_custom_post_type_options_page_callback');
}
add_action('admin_menu', 'register_site_options_page');
/*
 *  SHORTCODE GENERATOR PAGE
 */
function register_shortcode_generator_page()
{
    global $shortcode_generator_page;
    $shortcode_generator_page = add_submenu_page('tools.php', 'BigBlueButton Shortcodes', 'BigBlueButton Shortcodes', 'edit_pages', 'bigbluebutton_custom_post_type-shortcode', 'bigbluebutton_custom_post_type_shortcode_page_callback');
}
add_action('admin_menu', 'register_shortcode_generator_page');
function bigbluebutton_custom_post_type_shortcode_page_callback()
{
    ?>
    <h1>BBB Shortcode Generator</h1>
    <table class="bigbluebutton_custom_post_type-shortcode" >
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
                    $terms = get_terms('bbb-room-category');
    $count = count($terms);
    if ($count > 0) {
        echo "<select multiple id='bbb-categories'>";
        echo "<option value='0' >All BigBlueButton Categories</option>";
        foreach ($terms as $term) {
            echo '<option value='.$term->term_id.' >'.$term->name.'</option>';
        }
        echo '</select>';
    } else {
    } ?>
            </td>
        </tr>
        <tr>
            <th>
                Only show these rooms: <br />
                <small>Hold CTRL (pc) or COMMAND (Mac) to select multiple.</small>
            </th>
            <td>

                <?php
                $args = array('post_type' => 'bbb-room',
                                                'orderby' => 'name',
                                                'posts_per_page' => -1,
                                                'order' => 'DESC', );
    $bbb_room_query = new WP_Query($args); ?>
                <?php if ($bbb_room_query->have_posts()) : ?>
                        <select multiple id="bbb-post-ids" >
                <?php while ($bbb_room_query->have_posts()) : $bbb_room_query->the_post(); ?>

                        <?php echo "<option value='".$bbb_room_query->post->ID."' >".get_the_title().'</option>'; ?>
                        <?php // var_dump($post);?>
                <?php
                endwhile; ?>
                        </select>
                <?php wp_reset_postdata(); ?>

                <?php else:  ?>
                  <p><?php _e('No BBB Rooms have been created yet.'); ?></p>
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

<?php
}

function bigbluebutton_custom_post_type_shortcode_enqueue()
{
    global $shortcode_generator_page;
    $screen = get_current_screen();
    if ($screen->id == $shortcode_generator_page) {
        $bbb_shortcode = plugins_url('shortcode.js', __FILE__);
        wp_register_script('bbb_shortcode', $bbb_shortcode);
        wp_enqueue_script('bbb_shortcode');
    }
}
add_action('admin_enqueue_scripts', 'bigbluebutton_custom_post_type_shortcode_enqueue');


//Inserts a bigbluebuttonnew widget on the siderbar of the blog
function bigbluebutton_sidebar($args) {
    echo $args['before_widget'];
    echo $args['before_title'] .'BigBlueButtonNew'.$args['after_title'];
    echo bigbluebutton_custom_post_type_renderShortcode($atts, $content, 'bigbluebuttonnew');
    echo $args['after_widget'];
}

//Inserts a bigbluebuttonold widget on the siderbar of the blog
function bigbluebutton_sidebar2($args) {
    echo $args['before_widget'];
    echo $args['before_title'] .'BigBlueButtonOld'.$args['after_title'];
    echo bigbluebutton_custom_post_type_renderShortcode($atts, $content, 'bigbluebuttonold');
    echo $args['after_widget'];
}
//Registers the bigbluebutton widget
function bigbluebutton_widget_init() {
wp_register_sidebar_widget('bigbluebuttonsidebarwidget',
                          __('BigBlueButtonNew'),
                          'bigbluebutton_sidebar',
                          array( 'description' => 'Displays a BigBlueButton login form in a sidebar.'));
wp_register_sidebar_widget('bigbluebuttonsidebarwidget2',
                          __('BigBlueButtonOld'),
                          'bigbluebutton_sidebar2',
                          array( 'description' => 'Displays a BigBlueButton login form in a sidebar.'));
}
add_action('widgets_init', 'bigbluebutton_widget_init');


//Gets the slug of the current post
function the_slug($echo=true){
  $slug = basename(get_permalink());
  do_action('before_slug', $slug);
  $slug = apply_filters('slug_filter', $slug);
  do_action('after_slug', $slug);
  return $slug;
}

//BigBlueButton shortcodes
function bigbluebutton_custom_post_type_renderShortcode($atts, $content, $tag)
{
   global  $current_user;
      extract(shortcode_atts(array(
                              'link_type'      => 'wordpress',
                              'bbb_categories' => '0',
                              'bbb_posts'      => '',
                              ), $atts));
      $output_string = '';
      $args = array('post_type'        => 'bbb-room',
                      'orderby'        => 'name',
                      'posts_per_page' => -1,
                      'order'          => 'DESC',
      );

      if ($bbb_categories) {

          $args['tax_query'] = array(
                                  array(
                                          'taxonomy' => 'bbb-room-category',
                                          'field'    => 'id',
                                          'terms'    => explode(',', $bbb_categories),
                                          ),
                                  );
      }

      if ($bbb_posts) {

          $args['post__in'] = explode(',', $bbb_posts);
      }

      $bbb_posts = new WP_Query($args); ?>

      <?php
      if($tag == 'bigbluebuttonnew')
      {
        if ($bbb_posts->have_posts()) :

                $output_string = '
                <style type="text/css">
               .shortcode{
                  background-color: #f6f6f6;
                  border: 1px solid #ccc;
                  padding:20px 30px 20px 30px;
                  width: 300px;
                }
                 </style>
                <form id="form1" class="shortcode">
                <label>Room:</label>
                <select onchange="location = this.options[this.selectedIndex].value;" style="color: #777; border-radius: 2px;background: #fff; width: 100%;">
                <option disabled selected value>select an option</option>';
        while ($bbb_posts->have_posts()) : $bbb_posts->the_post();
        $output_string .= "<option value='".get_permalink()."' >".get_the_title().'</option>';
        endwhile;
        $output_string .= '
              </select>
              </form>';

        wp_reset_postdata(); else:
          //$output_string .= '<p>' . __( 'No BBB Rooms have been created yet.' ) . '</p>';
        endif;
        return $output_string;
    }
    else
    {
      $bigbluebutton_custom_post_type_settings = get_option('bigbluebutton_custom_post_type_settings');
      $endpoint_val = $bigbluebutton_custom_post_type_settings['endpoint'];
      $secret_val = $bigbluebutton_custom_post_type_settings['secret'];
      $logouturl = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'?logout=true';
      $duration = 0;
      $voicebridge = 0;

      $output_string = '
                <script type="text/javascript">
                function goToNewPageNew(dropdownlist)
                {
                  var url = dropdownlist.options[dropdownlist.selectedIndex].value;
                  if (url != "")
                  {
                    window.open(url);
                  }
                }
                </script>';
        if ($bbb_posts->have_posts()) :
                $output_string .= '
                <form name="dropdownNew" class="shortcode">
                <label>Meeting:</label>
                <select name="list" accesskey="E"  style="color: #777; border-radius: 2px;background: #fff; width: 100%; ">
                <option disabled selected value>select an option</option>';
            while ($bbb_posts->have_posts()) :
              $bbb_posts->the_post();
              $slug = the_slug();
              if ($post = get_page_by_path($slug, OBJECT, 'bbb-room'))
              {
                  $bbb_room_token = get_post_meta($post->ID, '_bbb_room_token', true);
                  $meetingID = $bbb_room_token;
                  $meetingID = bigbluebutton_custom_post_type_normalizeMeetingID($meetingID);
                  $bbb_attendee_password = get_post_meta($post->ID, '_bbb_attendee_password', true);
                  $bbb_moderator_password = get_post_meta($post->ID, '_bbb_moderator_password', true);
                  $name = $current_user->display_name;
                  $bbb_meeting_name = get_the_title($post->ID);
                  $bbb_room_welcome_msg = get_post_meta($post->ID, '_bbb_room_welcome_msg', true);
                  $bbb_is_recorded = get_post_meta($post->ID, '_bbb_is_recorded', true);
                  $recorded = $bbb_is_recorded;
                  $response = BigBlueButton::createMeetingArray($name, $meetingID, $bbb_meeting_name, $bbb_room_welcome_msg, $bbb_moderator_password, $bbb_attendee_password, $secret_val, $endpoint_val, $logouturl, $recorded ? 'true' : 'false', $duration, $voicebridge, $metadata);

                  if ($current_user->allcaps["edit_bbb-cat"]) {
                    $password = $bbb_moderator_password;
                  }
                  else if($current_user->allcaps["read"]) {
                    $password = $bbb_attendee_password;
                  }

                  if (!$response || $response['returncode'] == 'FAILED')
                  {
                    //If the server is unreachable, or an error occured
                    $out .= "<p class='error'>".__('Sorry an error occured while creating the meeting room.', 'bbb').'</p>';
                  }
                  else
                  {
                    $bigbluebutton_custom_post_type_joinURL = BigBlueButton::getJoinURL($meetingID, $name , $password, $secret_val, $endpoint_val);
                  }
                  $output_string .= "<option value='".$bigbluebutton_custom_post_type_joinURL."' >".get_the_title().'</option>';
              }
            endwhile;
            $output_string .= '
                  </select>
                  <style type="text/css">
                 .oldshortcode{
              			border-radius: 2px;
              			width: 100%;
              			margin-top: 20px;
              		}
                   </style>
                    <input class="oldshortcode"  type="submit"  onClick="goToNewPageNew(document.dropdownNew.list)"  value="Join"/>
                  </form>
                  ';
            wp_reset_postdata();

        endif;
        return $output_string;
    }
}
add_shortcode('bigbluebuttonnew', 'bigbluebutton_custom_post_type_renderShortcode',1);
add_shortcode('bigbluebuttonold', 'bigbluebutton_custom_post_type_renderShortcode',1);



//Displays the javascript that handles redirecting a user, when the meeting has started
//the meetingName is the meetingID
/*
 * At the moment this does not work since the broker always returns false when checking if the meeting has started
 */
function bigbluebutton_custom_post_type_display_reveal_script($bigbluebutton_custom_post_type_joinURL, $meetingID, $meetingName, $name)
{
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
                            jQuery("div#bbb-join-container").append("<p><a class=\'bbb\'  href=\''.$bigbluebutton_custom_post_type_joinURL.'\' target=\'_blank\'>'.'Join as Attendee'.'</a></p>");
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
            <div align="center"><img src="'.get_bloginfo('url').'/wp-content/plugins/wordpress-custompost_bigbluebutton/img/polling.gif" /></div><br />
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
function bigbluebutton_custom_post_type_list_room_recordings($postID = 0)
{
    global $current_user;
    $bbb_room_token = get_post_meta($postID, '_bbb_room_token', true);
    $meetingID = $bbb_room_token;
    $meetingID = bigbluebutton_custom_post_type_normalizeMeetingID($meetingID);
    //Initializes the variable that will collect the output
    $out = '';
    $bigbluebutton_custom_post_type_settings = get_option('bigbluebutton_custom_post_type_settings');
    //Read in existing option value from database
    $endpoint_val = $bigbluebutton_custom_post_type_settings['endpoint'];
    $secret_val = $bigbluebutton_custom_post_type_settings['secret'];
    $_SESSION['mt_bbb_endpoint'] = $endpoint_val;
    $_SESSION['mt_bbb_secret'] = $secret_val;
    $listOfRecordings = array();
    if ($meetingID != '') {
        $recordingsArray = BigBlueButton::getRecordingsArray($meetingID, $endpoint_val, $secret_val);
        if ($recordingsArray['returncode'] == 'SUCCESS' && !$recordingsArray['messageKey']) {
            $listOfRecordings = $recordingsArray['recordings'];
        }
    }
    if (class_exists('FirePHP')) {
        $firephp = FirePHP::getInstance(true);
        $firephp->log(BigBlueButton::getRecordingsArray($meetingID, $endpoint_val, $secret_val));
    }
    //Checks to see if there are no meetings in the wordpress db and if so alerts the user
    if (count($listOfRecordings) == 0) {
        $out .= '<p><strong>There are no recordings available.</strong></p>';
        return $out;
    }
    if (current_user_can('edit_bbb-room', $postID) && is_admin()) {
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
                                el_img.src = wwwroot + \'/wp-content/plugins/wordpress-custompost_bigbluebutton/img/show.gif\';
                            } else {
                                action = \'publish\';
                                el_a.title = \'Hide\';
                                el_img.src = wwwroot + \'/wp-content/plugins/wordpress-custompost_bigbluebutton/img/hide.gif\';
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
    if (current_user_can('edit_bbb-room', $postID) && is_admin()) {
        $out .= '
        <th class="hedextra" colspan="1">Toolbar</td>';
    }
    $out .= '
      </tr>';
    foreach ($listOfRecordings as $recording) {
            /// Prepare playback recording links
            $type = '';
        foreach ($recording['playbacks'] as $playback) {
            if ($recording['published'] == 'true') {
                $type .= '<a href="'.$playback['url'].'" target="_new">'.$playback['type'].'</a>&#32;';
            } else {
                $type .= $playback['type'].'&#32;';
            }
        }
            /// Prepare duration
        $endTime = isset($recording['endTime']) ? floatval($recording['endTime']) : 0;
        $endTime = $endTime - ($endTime % 1000);
        $startTime = isset($recording['startTime']) ? floatval($recording['startTime']) : 0;
        $startTime = $startTime - ($startTime % 1000);
        $duration = intval(($endTime - $startTime) / 60000);
            /// Prepare date
            //Make sure the startTime is timestamp
            if (!is_numeric($recording['startTime'])) {
                $date = new DateTime($recording['startTime']);
                $recording['startTime'] = date_timestamp_get($date);
            } else {
                $recording['startTime'] = ($recording['startTime'] - $recording['startTime'] % 1000) / 1000;
            }
            //Format the date
            $formatedStartDate = date_i18n('M d Y H:i:s', $recording['startTime'], false);
            //Print detail
            if ($recording['published'] == 'true' || is_admin()) {
                $out .= '
                <tr id="actionbar-tr-'.$recording['recordID'].'">
                  <td>'.$type.'</td>
                  <td>'.$recording['meetingName'].'</td>
                  <td>'.$formatedStartDate.'</td>
                  <td>'.$duration.' min</td>';
                /// Prepare actionbar if role is allowed to manage the recordings
                if (current_user_can('edit_bbb-room', $postID) && is_admin()) {
                    $action = ($recording['published'] == 'true') ? 'Hide' : 'Show';
                    $actionbar = '<a id="actionbar-publish-a-'.$recording['recordID'].'" title="'.$action.'" href="#"><img id="actionbar-publish-img-'.$recording['recordID'].'" src="'.get_bloginfo('url').'/wp-content/plugins/wordpress-custompost_bigbluebutton/img/'.strtolower($action).".gif\" class=\"iconsmall\" onClick=\"actionCall('publish', '".$recording['recordID']."'); return false;\" /></a>";
                    $actionbar .= '<a id="actionbar-delete-a-'.$recording['recordID'].'" title="Delete" href="#"><img id="actionbar-delete-img-'.$recording['recordID'].'" src="'.get_bloginfo('url')."/wp-content/plugins/wordpress-custompost_bigbluebutton/img/delete.gif\" class=\"iconsmall\" onClick=\"actionCall('delete', '".$recording['recordID']."'); return false;\" /></a>";
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
function bigbluebutton_custom_post_type_generateToken($tokenLength = 6)
{
    $token = '';
    if (function_exists('openssl_random_pseudo_bytes')) {
        $token .= bin2hex(openssl_random_pseudo_bytes($tokenLength));
    } else {
        //fallback to mt_rand if php < 5.3 or no openssl available
        $characters = '0123456789abcdef';
        $charactersLength = strlen($characters) - 1;
        $tokenLength *= 2;
        //select some random characters
        for ($i = 0; $i < $tokenLength; ++$i) {
            $token .= $characters[mt_rand(0, $charactersLength)];
        }
    }
    return $token;
}

//generates random password
function bigbluebutton_custom_post_type_generatePasswd($numAlpha = 6, $numNonAlpha = 2, $salt = '')
{
    $listAlpha = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $listNonAlpha = ',;:!?.$/*-+&@_+;./*&?$-!,';
    do {
        $pepper = str_shuffle(substr(str_shuffle($listAlpha), 0, $numAlpha).substr(str_shuffle($listNonAlpha), 0, $numNonAlpha));
    } while ($pepper == $salt);
    return $pepper;
}

//normalizing meeting ID
function bigbluebutton_custom_post_type_normalizeMeetingID($meetingID)
{
    return (strlen($meetingID) == 12) ? sha1(home_url().$meetingID) : $meetingID;
}

//Returns current plugin version.
function bigbluebutton_custom_post_type_get_version()
{
    if (!function_exists('get_plugins')) {
        require_once ABSPATH.'wp-admin/includes/plugin.php';
    }
    $plugin_folder = get_plugins('/'.plugin_basename(dirname(__FILE__)));
    $plugin_file = basename((__FILE__));
    return $plugin_folder[$plugin_file]['Version'];
}
?>
