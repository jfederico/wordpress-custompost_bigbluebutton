var slug;

/**
* On change, sets the hidden input of the selected field in the rooms shortcode.
*/
jQuery(function($){

  //joins multiple rooms
	$("#bbbRooms").change(function(){
			setMeetingData(this);
	});

  //joins single rroom
	setMeetingData('input#hiddenInputSingle');

	function setMeetingData(hiddenInput){
		slug = $(hiddenInput).val();
	}

});

/**
* Joins the meeting session.
*/
function bigbluebutton_join_meeting(baseurl){
		jQuery.ajax({
			url : baseurl+'/broker.php?action=join&slug='+slug,
			async : true,
			dataType : "text",
			success : function(data){
				if(data.includes("meetingID")){
					window.open(data);
				}
				else{
					jQuery(function($){
				  	$("#roomCreateErrorMsg").show();
					});
				}
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
