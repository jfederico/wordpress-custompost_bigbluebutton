var slug;


jQuery(function($){

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
* @param  baseurl  base url of the plugin
* @param  join join or view the room
*/
function bigbluebutton_join_meeting(baseurl,join,userSignedIn,passwordRequired,page){
	  var password='';
		var name='';

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

		var dataString = 'slug=' + slug + '&join=' + join + '&password=' + password + '&name=' + name;

		jQuery.ajax({
			type: "POST",
			url : baseurl+'/broker.php?action=join',
			async : true,
			data: dataString,
			dataType : "text",
			success : function(data){
				if(data.includes("http")){
					window.open(data);
				}
				else if (data.includes("9")) {
					bigbluebutton_custom_post_type_ping(baseurl, data);
					setInterval("bigbluebutton_custom_post_type_ping(baseurl, data)", 60000);
				}
			},
			error : function(xmlHttpRequest, status, error) {
				console.error("Ajax was not successful");
			}
		});
 }

 function bigbluebutton_custom_post_type_ping(baseurl, id) {
 	 jQuery.ajax({
 			 url : baseurl+'/broker.php?action=ping&meetingID='+id,
 			 async : true,
 			 dataType : "text",
 			 success : function(xmlDoc){
 					 if(xmlDoc == "true"){
 									jQuery("div#bbb-join-container").append("Join as Attendee");
 							 }
							 else{
								 var string = baseurl+'/img/polling.gif';
								  jQuery("div#bbb-join-container").append("<img src="+string+"\ />");
							 }
 			 },
 			 error : function(xmlHttpRequest, status, error) {
 			 }
 	 });
 }
