var pluginBaseUrl = bbbScript.pluginsUrl;
var meetingDetails;
var slug;
var bbbPingInterval ='';

jQuery(function($){
//one id for both?
	$("#bbbRooms").change(function(){
			setMeetingSlug(this);
	});

	setMeetingSlug('input#hiddenInputSingle');

  //sets the slug
	function setMeetingSlug(hiddenInput){
		slug = $(hiddenInput).val();
	}

});


/**
* Joins/Views the meeting/room.
*
* @param  join join or view the room
* @param  userSignedIn
* @param  passwordRequired
* @param  page
*/
function bigbluebutton_join_meeting(join, userSignedIn, passwordRequired, page){
		var name = '';
		var password = '';

		//clean this up
		if(page == "true")
		{
			if(userSignedIn == "false"){
				name = prompt("Please enter your name: ", "Enter name here");
			}

			if(passwordRequired == "true"){
				password = prompt("Please enter the password of the meeting: ", "Enter password here");
			}

		}else{
			if(userSignedIn == "false"){
				jQuery(function($) {
						name = $('input#displayname').val();
				});
			}

			if(passwordRequired == "true"){
				jQuery(function($) {
					password = $('input#roompw').val();
				});
			}
		}

		meetingDetails = '&slug=' + slug + '&join=' + join + '&password=' + password + '&name=' + name;

		jQuery.ajax({
			type: "POST",
			url : pluginBaseUrl+'/broker.php?action=join',
			async : true,
			data: meetingDetails,
			dataType : "text",
			success : function(data){
				if(isUrl(data)){
					window.open(data);
				}
				else {
					var pollingImgPath = pluginBaseUrl+'/img/polling.gif';
					jQuery("div#bbb-join-container").append
					("<center>Welcome to "+ slug +"!<br /><br /> \
					 The session has not been started yet.<br /><br />\
					 <center><img src="+ pollingImgPath +"\ /></center>\
					 (Your browser will automatically refresh and join the meeting when it starts.)</center>");
					jQuery("form#form1").hide();
					jQuery("input.bbb-shortcode-selector").hide();
			    bbbPingInterval = setInterval("bigbluebutton_custom_post_type_ping()", 5000);
				}
			},
			error : function() {
				console.error("Ajax was not successful: JOIN");
			}
		});
 }

/**
* This function is pinged every 5 seconds to see if the meeting is running
**/
 function bigbluebutton_custom_post_type_ping() {
 	 jQuery.ajax({
	   type: "POST",
		 url : pluginBaseUrl + '/broker.php?action=ping',
		 async : true,
		 data: meetingDetails,
		 dataType : "text",
		 success : function(data){
		 if(isUrl(data)){
			  clearInterval(bbbPingInterval);
				jQuery("div#bbb-join-container").remove();
			  window.open(data);
			}
		 },
		 error : function() {
		 	console.error("Ajax was not successful: PING");
		 }
 	 });
 }

/**
* Detecting weather the passed string is a URL
*
* @param s String thats passed to see if its a URL
* https://stackoverflow.com/questions/1701898/how-to-detect-whether-a-string-is-in-url-format-using-javascript
**/
 function isUrl(s) {
   var regexp = /(ftp|http|https):\/\/(\w+:{0,1}\w*@)?(\S+)(:[0-9]+)?(\/|\/([\w#!:.?+=&%@!\-\/]))?/
   return regexp.test(s);
}
