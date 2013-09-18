<?php
/*
Plugin Name: Big Blue Button Plugin With Custom Post Type Example
Plugin URI: 
Description: Big Blue Button Plugin With Custom Post Type Example
Version: 0.1
Author: Steve Puddick
Author URI: http://webrockstar.net/
*/



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
	
	?>
    <table class='custom-admin-table'>
    	<tr  >
            <th>Attendee Password</th>
            <td>
               	<?php if ($author_id == $current_user_id) { ?>
                    <input type="text" name='bbb_attendee_password'  class='' value='<?php echo $bbb_attendee_password; ?>' />
                <?php } else { ?>
                    <p>Attendee Password is only visible and editable by room creator.</p>	
                <?php } ?>
            </td>
        </tr>
        <tr  >
            <th>Moderator Password</th>
            <td>
                <?php if ($author_id == $current_user_id) { ?>
                    <input type="text" name='bbb_moderator_password'  class='' value='<?php echo $bbb_moderator_password; ?>' />
                <?php } else { ?>
                    <p>Moderator Password is only visible and editable by room creator.</p>	
                <?php } ?>
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
		
            //Call BBB API to get token. Right now a generic token will be used as a placeholder
            $token = '123456';
            update_post_meta($post_id, '_bbb_room_token', $token) ;
            
            /*
            if($something_went_wrong) {
                 //Append error notice if something went wrong
                 $_SESSION['my_admin_notices'] .= '<div class="error"><p>This or that went wrong</p></div>';
                 return false; //might stop processing here
            }
            if($somthing_to_notice) {  //i.e. successful saving
                 //Append notice if something went wrong
                 $_SESSION['my_admin_notices'] .= '<div class="updated"><p>Post updated</p></div>';
            }
            */

            update_post_meta($post_id, '_bbb_attendee_password', esc_attr($_POST['bbb_attendee_password']) );
            update_post_meta($post_id, '_bbb_moderator_password', esc_attr($_POST['bbb_moderator_password']) );
            update_post_meta($post_id, '_bbb_must_wait_for_admin_start', esc_attr($_POST['bbb_must_wait_for_admin_start']) );
            update_post_meta($post_id, '_bbb_is_recorded', esc_attr($_POST['bbb_is_recorded']) );
		
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
  
  //target only BBB post type
  if ( 'bbb-room' == get_post_type( $post ) && is_single()  )
  	$room_button = '<p><a class="bbb" style="display:inline-block;border-radius:5px;padding:20px;border:1px solid #1862DB;background-color:#C3D7F7" href="#">Enter BBB Room</a></p>';
  
  return $content . $room_button;
}

add_filter( 'the_content', 'bbb_filter' );


/*
 *  OPTIONS PAGE 
 */
add_action('admin_menu', 'register_site_options_page');

function register_site_options_page() {
	add_submenu_page( 'options-general.php', 'Site Options', 'BBB Options', 'edit_pages', 'site-options', 'bbb_options_page_callback' ); 
}

function bbb_options_page_callback() { ?>
	
	<table>
		<tr>
			<th>URL of BBB Server</th>
			<td><input type="text" /></td>
		</tr>
		<tr>
			<th>Salt of BBB server</th>
			<td><input type="text" /></td>
		</tr>
		<tr>
			<th></th>
			<td><input type="submit" value="Save Settings" /></td>
		</tr>
	</table>
	
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

?>