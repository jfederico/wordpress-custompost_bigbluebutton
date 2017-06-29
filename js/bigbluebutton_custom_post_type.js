var slug;

/**
* Sets the hidden input of the selected field in the rooms shortcode.
*/
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
* @param  string  baseurl  base url of the plugin
* @param  bool  join join or view the room
*/
function bigbluebutton_join_meeting(baseurl,join){
		jQuery.ajax({
			url : baseurl+'/broker.php?action=join&slug='+slug+'&join='+join,
			async : true,
			dataType : "text",
			success : function(data){
				if(data.includes("http")){
					window.open(data);
				}
				else{
					jQuery(function($){
						$("#roomMeetingErrorMsg").text(data).show();
					});
				}
			},
			error : function(xmlHttpRequest, status, error) {
				console.error("Ajax was not successful");
			}
		});
}
