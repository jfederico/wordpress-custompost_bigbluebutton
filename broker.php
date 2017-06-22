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
session_start();
$bbb_endpoint_name = 'mt_bbb_endpoint';
$bbb_secret_name = 'mt_bbb_secret';
$action_name = 'action';
$recordingID_name = 'recordingID';
$meetingID_name = 'meetingID';
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
                error_log('RESPONSE: '.$response);
                echo '<response>'.$response.'</response>';
            }
            break;
        default:
            header('Content-Type: text/plain; charset=utf-8');
            echo BigBlueButton::getServerVersion($url_val);
    }
}
