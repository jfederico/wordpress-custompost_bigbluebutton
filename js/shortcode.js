jQuery(document).ready(function($){
	renderShortcode();

	$('input[name=bbb_link_type]').change(function() {
		renderShortcode();
	});	 
	$('select#bbb-categories').change(function() {
		renderShortcode();
	});
	$('select#bbb-post-ids').change(function() {
		renderShortcode();
	});
});

function renderShortcode() {
    bbb_link_type = $('input[name=bbb_link_type]:checked').val();
    bbb_link_type_string = ' link_type="' +  bbb_link_type + '"';
    bbb_categories = $('select#bbb-categories').val() || [];
    bbb_categories_string = '';
    if (bbb_categories.length) {
        bbb_categories_string = ' bbb_categories="' + bbb_categories.join(",") + '" ';
    }
    bbb_post_ids = $('select#bbb-post-ids').val() || [];
    bbb_posts_id_string = '';
    if (bbb_post_ids.length) {
        bbb_posts_id_string = ' bbb_posts="' + bbb_post_ids.join(",") +'" ';
    }
    $('p#shortcode').text('[bbb ' + bbb_link_type_string + bbb_categories_string  + bbb_posts_id_string + ']');
}

function goToNewPageNew(dropdownlist) {
    var url = dropdownlist.options[dropdownlist.selectedIndex].value;
    if (url !== "") {
        window.open(url);
    }
}
