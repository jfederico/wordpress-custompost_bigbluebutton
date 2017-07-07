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
* @param  baseurl  base url of the plugin
* @param  join join or view the room
*/
function bigbluebutton_join_meeting(baseurl,join){
	  var password;

	  jQuery(function($){
			password = $('input#roompw').val();
		});

		var dataString = 'slug='+slug+'&join='+join+ '&password='+password;

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
