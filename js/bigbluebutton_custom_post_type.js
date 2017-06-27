function bigbluebutton_join_meeting(baseurl,meetingID,slug) {
		console.log("Join the meeting**");
		jQuery.ajax({
			url : baseurl+'/broker.php?action=join&meetingID='+ meetingID+'&slug='+slug,
			async : true,
			dataType : "text",
			success : function(joinURL){
					window.open(joinURL);
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
