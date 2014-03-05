<?php
// make sure only logged in users can see this page 
gatekeeper();


$pid = $segments[1];
// set the title
// for distributed plugins, be sure to use elgg_echo() for internationalization
$title = elgg_echo('blogbook:Create a new book/chapter');
 
// start building the main column of the page
$content = elgg_view_title($title);

	$vars = array(
		'pid' => $pid,
		'entity' => $tblog,
		'access_id' => ACCESS_DEFAULT,
		'write_access_id' => ACCESS_DEFAULT,
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