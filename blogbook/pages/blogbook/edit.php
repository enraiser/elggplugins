<?php
// make sure only logged in users can see this page 
gatekeeper();


$page_owner = elgg_get_page_owner_entity();


$tblog_guid = $segments[1];


$tblog = get_entity($tblog_guid);

if (!elgg_instanceof($tblog, 'object', 'blogbook') || !$tblog->canEdit()) {
	register_error(elgg_echo('blogbook:unknown_book/chapter'));
	forward(REFERRER);
}


 
// set the title
// for distributed plugins, be sure to use elgg_echo() for internationalization
$title = "Edit blogbook post";
 
// start building the main column of the page
$content = elgg_view_title($title);
 
// add the form to this section



	$vars = array(
		'title' => $tblog->title, // bookmarklet support
	        'description'  =>$tblog->description,
		'pid' => $tblog->parent_guid,
                'cids' => $tblog->cids,
		'bids' => $tblog->bids,
		'entity' => $tblog,
		'access_id' => ACCESS_DEFAULT,
		'write_access_id' => ACCESS_DEFAULT,
		'guid' => $tblog_guid,
	);


$content = elgg_view_form('blogbook/save', array(), $vars);



// optionally, add the content for the sidebar
$sidebar = "";

// layout the page
$body = elgg_view_layout('one_sidebar', array(
   'content' => $content,
   'sidebar' => $sidebar
));
 
// draw the page
echo elgg_view_page($title, $body);

?>