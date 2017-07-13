<?php
/*
Copyright 2012 Blindside Networks
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.
This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
Versions:
   1.0  --  Updated by Jesus Federico
                    (email : federico DOT jesus [a t ] g m ail DOT com)
*/
///================================================================================
//------------------Required Libraries and Global Variables-----------------------
//================================================================================
require 'includes/bbb_api.php';
require($_SERVER['DOCUMENT_ROOT'].'/wordpress-test/wp-load.php');//MAKE SUREE TO CHNAGE THE PATH
session_start();
$bbb_endpoint_name = 'mt_bbb_endpoint';
$bbb_secret_name = 'mt_bbb_secret';
$action_name = 'action';
$recordingID_name = 'recordingID';
$meetingID_name = 'meetingID';
$slug_name = 'slug';
$join = 'join';
$password = 'password';
//================================================================================
//------------------------------------Main----------------------------------------
//================================================================================
//Retrieves the bigbluebutton url, and salt from the seesion
if (!isset($_SESSION[$bbb_secret_name]) || !isset($_SESSION[$bbb_endpoint_name])) {
    header('HTTP/1.0 400 Bad Request. BigBlueButton_CPT Url or Salt are not accessible.');
} elseif (!isset($_GET[$action_name])) {
    header('HTTP/1.0 400 Bad Request. [action] parameter was not included in this query.');
} else {
    $salt_val = $_SESSION[$bbb_secret_name];
    $url_val = $_SESSION[$bbb_endpoint_name];
    $action = $_GET[$action_name];
    switch ($action) {
        case 'publish':
            header('Content-Type: text/plain; charset=utf-8');
            if (!isset($_GET[$recordingID_name])) {
                header('HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.');
            } else {
                $recordingID = $_GET[$recordingID_name];
                echo BigBlueButton::doPublishRecordings($recordingID, 'true', $url_val, $salt_val);
            }
            break;
        case 'unpublish':
            header('Content-Type: text/plain; charset=utf-8');
            if (!isset($_GET[$recordingID_name])) {
                header('HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.');
            } else {
                $recordingID = $_GET[$recordingID_name];
                echo BigBlueButton::doPublishRecordings($recordingID, 'false', $url_val, $salt_val);
            }
            break;
        case 'delete':
            header('Content-Type: text/plain; charset=utf-8');
            if (!isset($_GET[$recordingID_name])) {
                header('HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.');
            } else {
                $recordingID = $_GET[$recordingID_name];
                echo BigBlueButton::doDeleteRecordings($recordingID, $url_val, $salt_val);
            }
            break;
        case 'ping':
            header('Content-Type: text/xml; charset=utf-8');
            echo '<?xml version="1.0"?>'."\r\n";
            if (!isset($_GET[$meetingID_name])) {
                header('HTTP/1.0 400 Bad Request. [meetingID] parameter was not included in this query.');
            } else {
                $meetingID = $_GET[$meetingID_name];
                $response = BigBlueButton::getMeetingXML($meetingID, $url_val, $salt_val);
                echo '<response>'.$response.'</response>';
            }
            break;
        case 'join':
            global $current_user;
            //post is not recognizing '+' and '&'
            if((!isset($_POST[$slug_name]))){
                header('HTTP/1.0 400 Bad Request. [slug] parameter was not included in this query.');
            }else if((!isset($_POST[$join]))){
                header('HTTP/1.0 400 Bad Request. [join] parameter was not included in this query.');
            }else{
              $post = get_page_by_path($_POST[$slug_name], OBJECT, 'bbb-room');

              if($_POST[$join] === "true"){
                $username = $current_user->display_name;
                $bbbRoomToken = get_post_meta($post->ID, '_bbb_room_token', true);
                $meetingID = $bbbRoomToken;
                if(strlen($meetingID) == 12){
                  $meetingID = sha1(home_url().$meetingID);
                }
                $meetingName = get_the_title($post->ID);
                $welcomeString = get_post_meta($post->ID, '_bbb_room_welcome_msg', true);
                $moderatorPassword = get_post_meta($post->ID, '_bbb_moderator_password', true);
                $attendeePassword = get_post_meta($post->ID, '_bbb_attendee_password', true);
                $logoutURL = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'?logout=true';
                $bigbluebuttonSettings = get_option('bigbluebutton_custom_post_type_settings');
                $endpointVal = $bigbluebuttonSettings['endpoint'];
                $secretVal = $bigbluebuttonSettings['secret'];
                $password = '';
                $userCapArray = array();

                if(is_user_logged_in() == true) {
                  $userCapArray = $current_user->allcaps;
                }else {
                  $username = $_POST['name'];
                  $anonymousRole = get_role('anonymous');
                  $userCapArray = $anonymousRole->capabilities;
                }
                
                if($userCapArray["join_with_password_bbb-room"] == true ) {
                    if($userCapArray["join_as_moderator_bbb-room"] == true) {
                      if(strcmp($moderatorPassword,$_POST['password']) === 0) {
                          $password = $moderatorPassword;
                      }
                    }else {
                      if(strcmp($attendeePassword,$_POST['password']) === 0) {
                          $password = $attendeePassword;
                      }
                    }
                }else {
                    if($userCapArray["join_as_moderator_bbb-room"] === true) {
                      $password = $moderatorPassword;
                    }else {
                      $password = $attendeePassword;
                    }
                }
//have to check if meeting is running here
                $response = BigBlueButton::createMeetingArray($username, $meetingID,
                 $meetingName, $welcomeString, $moderatorPassword, $attendeePassword,
                 $secretVal, $endpointVal, $logoutURL, $record = 'false', $duration = 0,
                 $voiceBridge = 0, $metadata = array());

                if (!$response || $response['returncode'] == 'FAILED') {
                    echo "Sorry an error occured while creating the meeting room.";
                }else {
                    echo BigBlueButton::getJoinURL($meetingID, $username, $password, $secretVal, $endpointVal);
                }
              }else {
                if($post !== null){
                  echo get_permalink();
                }else {
                  echo "Sorry the page could not be viewed";
                }
              }
            }
            break;
        default:
            header('Content-Type: text/plain; charset=utf-8');
            echo BigBlueButton::getServerVersion($url_val);
    }
}
