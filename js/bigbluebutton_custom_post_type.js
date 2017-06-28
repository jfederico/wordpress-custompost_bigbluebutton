

var slug;
/**
* On change, sets the hidden input of the selected field in the rooms shortcode.
*/
jQuery(function($){

	$("#bbbRooms").change(function(){
			setMeetingData(this);//chnage this to how the single data button works for hidden input
	});

	$("#singleButton").click(function() {
		  setMeetingData('input#hiddenInputSingle');
  });

	function setMeetingData(hiddenInput){
		slug = $(hiddenInput).val();
	}
});

/**
* Joins the meeting session.
*/
function bigbluebutton_join_meeting(baseurl) {
		jQuery.ajax({
			url : baseurl+'/broker.php?action=join&slug='+slug,
			async : true,
			dataType : "text",
			success : function(data){
				console.log("DATA  "+data);
				if(data.includes("meetingID")){
					window.open(data);
				}
				else{
					console.error("Sorry an error occured while creating the meeting room.");
				}
				//have to handle the case where cannot join a room
				console.log("Ajax was* successful");
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
