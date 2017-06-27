
var meetingID;
var slug;

jQuery(function($){
	 $("#bbbRooms").change(function () {
    $("#hiddenInput").val($(this).val());
		console.log("INFOO "+ $(this).val());
		var meetingInfo = ($(this).val()).split("_");
		slug = meetingInfo[0];
		meetingID = meetingInfo[1];
	})
});

function bigbluebutton_join_meeting(baseurl) {
		jQuery.ajax({
			url : baseurl+'/broker.php?action=join&meetingID='+ meetingID+'&slug='+slug,
			async : true,
			dataType : "text",
			success : function(data){
				console.log(data);
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

function bigbluebutton_view_room() {
	  console.log("View the room");
	  //url=
	  //window.location=url;
}
