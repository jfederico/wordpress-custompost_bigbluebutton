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
			},
			error : function(xmlHttpRequest, status, error) {
				console.error("Ajax was not successful");
			}
		});
 }
