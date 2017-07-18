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
$bbbEndpointName = 'mt_bbb_endpoint';
$bbbSecretName = 'mt_bbb_secret';
$actionName = 'action';
$recordingIDName = 'recordingID';
$slugName = 'slug';
$join = 'join';
$password = 'password';

//================================================================================
//------------------------------------Main----------------------------------------
//================================================================================
//Retrieves the bigbluebutton url, and salt from the seesion
if (!isset($_SESSION[$bbbSecretName]) || !isset($_SESSION[$bbbEndpointName])) {
    header('HTTP/1.0 400 Bad Request. BigBlueButton_CPT Url or Salt are not accessible.');
} elseif (!isset($_GET[$actionName])) {
    header('HTTP/1.0 400 Bad Request. [action] parameter was not included in this query.');
} else {
    $secretVal = $_SESSION[$bbbSecretName];
    $endpointVal = $_SESSION[$bbbEndpointName];
    $action = $_GET[$actionName];
    switch ($action) {
        case 'publish':
            header('Content-Type: text/plain; charset=utf-8');
            if (!isset($_GET[$recordingIDName])) {
                header('HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.');
            } else {
                $recordingID = $_GET[$recordingIDName];
                echo BigBlueButton::doPublishRecordings($recordingID, 'true', $endpointVal, $secretVal);
            }
            break;
        case 'unpublish':
            header('Content-Type: text/plain; charset=utf-8');
            if (!isset($_GET[$recordingIDName])) {
                header('HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.');
            } else {
                $recordingID = $_GET[$recordingIDName];
                echo BigBlueButton::doPublishRecordings($recordingID, 'false', $endpointVal, $secretVal);
            }
            break;
        case 'delete':
            header('Content-Type: text/plain; charset=utf-8');
            if (!isset($_GET[$recordingIDName])) {
                header('HTTP/1.0 400 Bad Request. [recordingID] parameter was not included in this query.');
            } else {
                $recordingID = $_GET[$recordingIDName];
                echo BigBlueButton::doDeleteRecordings($recordingID, $endpointVal, $secretVal);
            }
            break;
        case 'ping':
            $username = setUserName();
            $meetingID = setMeetingID($_POST[$slugName]);
            $password = setPassword($_POST[$slugName]);
            $response = BigBlueButton::getMeetingXML($meetingID, $endpointVal, $secretVal);
            if((strpos($response,"true") !== false)){
              echo BigBlueButton::getJoinURL($meetingID, $username, $password , $secretVal, $endpointVal);
            }
            break;
        case 'join'://cant join when editor (in old plugin, it direcclty says "sorry you are not allowed to join this page)"
            //post is not recognizing '+' and '&'
            if((!isset($_POST[$slugName]))){
                header('HTTP/1.0 400 Bad Request. [slug] parameter was not included in this query.');
            }else if((!isset($_POST[$join]))){
                header('HTTP/1.0 400 Bad Request. [join] parameter was not included in this query.');
            }else{
              $post = get_page_by_path($_POST[$slugName], OBJECT, 'bbb-room');
              if($_POST[$join] === "true"){
                $username = setUserName();
                $meetingID = setMeetingID($_POST[$slugName]);
                $password = setPassword($_POST[$slugName]);
                $meetingName = get_the_title($post->ID);
                $welcomeString = get_post_meta($post->ID, '_bbb_room_welcome_msg', true);
                $moderatorPassword = get_post_meta($post->ID, '_bbb_moderator_password', true);
                $attendeePassword = get_post_meta($post->ID, '_bbb_attendee_password', true);
                $bbbIsRecorded = get_post_meta($post->ID, '_bbb_is_recorded', true);
                $logoutURL = (is_ssl() ? 'https://' : 'http://').$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'?logout=true';
                $bbbWaitForAdminStart = get_post_meta($post->ID, '_bbb_must_wait_for_admin_start', true);
                $metaData = array(
                 'meta_origin' => 'WordPress',
                 'meta_origintag' => 'wp_plugin-bigbluebutton_custom_post_type ',
                 'meta_originservername' => home_url(),
                 'meta_originservercommonname' => get_bloginfo('name'),
                 'meta_originurl' => $logoutURL,
                );
                $response = BigBlueButton::createMeetingArray($username, $meetingID, $meetingName, $welcomeString, $moderatorPassword,$attendeePassword, $secretVal, $endpointVal, $logoutURL, $bbbIsRecorded ? 'true' : 'false', $duration = 0, $voiceBridge = 0, $metaData);

                if (!$response || $response['returncode'] == 'FAILED') {
                    echo "Sorry an error occured while creating the meeting room.";
                }else {
                    $bigbluebuttonJoinURL = BigBlueButton::getJoinURL($meetingID, $username, $password, $secretVal, $endpointVal);
                    $isMeetingRunning = BigBlueButton::isMeetingRunning($meetingID, $endpointVal, $secretVal);
                    if (($isMeetingRunning && ($moderatorPassword == $password || $attendeePassword == $password))
                         || $response['moderatorPW'] == $password
                         || ($response['attendeePW'] == $password && !$bbbWaitForAdminStart)) {
                          echo $bigbluebuttonJoinURL;
                    }
                    elseif ($attendeePassword == $password) {
                        echo '';
                    }
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
            echo BigBlueButton::getServerVersion($endpointVal);
    }
}

/**
* Sets the password of the meeting
**/
function setPassword($slug){
  $post = get_page_by_path($slug, OBJECT, 'bbb-room');
  $current_user = wp_get_current_user();
  $password='';
  $moderatorPassword = get_post_meta($post->ID, '_bbb_moderator_password', true);
  $attendeePassword = get_post_meta($post->ID, '_bbb_attendee_password', true);

  if(is_user_logged_in() == true) {
    $userCapArray = $current_user->allcaps;

  }else {
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
  return $password;
}

/**
* Sets the user name of the moderator or attendee
**/
function setUserName(){
  $current_user = wp_get_current_user();
  $username = $current_user->display_name;
  if($username == '' || $username == null){
    $username = $_POST['name'];
  }
  return $username;
}

/**
* Sets the meetingID
**/
function setMeetingID($slug)
{
  $post = get_page_by_path($slug, OBJECT, 'bbb-room');
  $bbbRoomToken = get_post_meta($post->ID, '_bbb_room_token', true);
  $meetingID = $bbbRoomToken;
  if(strlen($meetingID) == 12){
    $meetingID = sha1(home_url().$meetingID);
  }
  return $meetingID;
}
