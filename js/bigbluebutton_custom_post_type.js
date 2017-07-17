var slug;
var url2;
var meetid;
var name = '';
var password= '';
var test ='';

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
				else if (data.includes("a")) {
					url2 = baseurl;
					meetid = data;
					var string = url2+'/img/polling.gif';
					jQuery("div#bbb-join-container").append("<img src="+string+"\ />");
			    test=setInterval(bigbluebutton_custom_post_type_ping(), 5000);
				}
			},
			error : function() {
				console.error("Ajax was not successful");
			}
		});
 }

 function bigbluebutton_custom_post_type_ping() {

 	var dataString = 'slug=' + slug +'&name=' + name + '&password=' + password;

 	 jQuery.ajax({
		 type: "POST",
 			 url : url2+'/broker.php?action=ping&meetingID='+meetid,
 			 async : true,
			 data: dataString,
 			 dataType : "text",
 			 success : function(xmlDoc){
				 if(xmlDoc.includes("http")){
					  clearInterval(test);
						jQuery("div#bbb-join-container").remove();
					   window.open(xmlDoc);
					}
 			 },
 			 error : function() {
 			 console.error("Ajax was not successful PING");
 			 }
 	 });
 }
