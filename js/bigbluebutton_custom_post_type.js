
var meetingID;
var slug;
var permalink;

/**
* On change, sets the hidden input of the selcted field in the rooms shortcode.
*/
jQuery(function($){
	 $("#bbbRooms").change(function () {
    $("#hiddenInput").val($(this).val());
		var meetingInfo = ($(this).val()).split("_");
		slug = meetingInfo[0];
		meetingID = meetingInfo[1];
		permalink = meetingInfo[2]
	})
});

/**
* Joins the meeting session.
*/
function bigbluebutton_join_meeting(baseurl) {
		jQuery.ajax({
			url : baseurl+'/broker.php?action=join&meetingID='+ meetingID+'&slug='+slug,
			async : true,
			dataType : "text",
			success : function(data){
				if(data.includes(meetingID)){
					window.open(data);
				}
				//have to handle the case where cannot join a room
			},
			error : function(xmlHttpRequest, status, error) {
					console.error("Ajax was not successful");
			}
		});
}

/**
* Views the meeting room.
*/
function bigbluebutton_view_room() {
	  window.location.href=permalink;
}
