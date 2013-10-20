This plugin requires an additional capability assignment plugin in order to apply the appropriate wordpress 
capabilities to roles. "Members" is a good plugin to use: http://wordpress.org/plugins/members/. Although different 
configurations are possible, the following may work best in most situations:


ADMINISTRATOR:
************************
delete_bbb-rooms
delete_others_bbb-room
edit_bbb-room
edit_others_bbb-room
publish_bbb-rooms

delete_bbb-cat
manage_bbb-cat
edit_bbb-cat
assign_bbb-cat




TEACHER (new role):
************************
delete_bbb-rooms
edit_bbb-room
publish_bbb-rooms
assign_bbb-cat
