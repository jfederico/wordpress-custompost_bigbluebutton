function bigbluebutton_join_meeting(baseurl,meetingID,slug) {
		console.log("Join the meeting");
		jQuery.ajax({
			url : baseurl+'/broker.php?action=join&meetingID='+ meetingID+'&slug='+slug,
			async : true,
			dataType : "xml",
			success : function(xmlDoc) {//have to fix the parameter for it
				//window.open(getJoinURL);
				console.log("*** ajax was successful ***" + JSON.stringify(xmlDoc));
			},
			error : function(xmlHttpRequest, status, error) {
					console.log("*** ajax was *NOT* successful ***");
			}
		});

}

function bigbluebutton_view_room() {
	  console.log("View the room");
	  //url=
	  //window.location=url;
}
