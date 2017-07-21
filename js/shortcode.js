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
	  linktype = '';
		linktypestring = '';
		categories = [];
		categories_string = '';
		postids = [];
		postsidstring = '';

    linktype = $('input[name=bbb_link_type]:checked').val();
    linktypestring = ' link_type="' +  linktype + '"';
    categories = $('select#bbb-categories').val() || [];
    categories_string = '';
    if (categories.length) {
        categories_string = ' bbb_categories="' + categories.join(",") + '" ';
    }
    postids = $('select#bbb-post-ids').val() || [];
    postsidstring = '';
    if (postids.length) {
        postsidstring = ' bbb_posts="' + postids.join(",") +'" ';
    }
    $('p#shortcode').text('[bbb ' + linktypestring + categories_string  + postsidstring + ']');
}

function goToNewPageNew(dropdownlist) {
    var url = dropdownlist.options[dropdownlist.selectedIndex].value;
    if (url !== "") {
        window.open(url);
    }
}
