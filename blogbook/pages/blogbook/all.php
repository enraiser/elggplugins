<?php
$tbloglist = elgg_get_entities(array(
	'type' => 'object',
	'subtype' => 'blogbook',
	'limit' =>'0',
));


foreach($tbloglist  as $tBlog)
 { 
 
   if(empty($tBlog->parent_guid)) {
      $body .= elgg_view_entity($tBlog);
      $body .='<hr>';
     }

 }
 
	elgg_register_menu_item('title', array(
			'name' => '',
			'href' => "blogbook/add",
			'text' => elgg_echo('blogbook:Create Book'),
			'link_class' => 'elgg-button elgg-button-action',
	));
$sidebar = "";


$body = elgg_view_layout('content', array(
	'filter' => '',
	'content' => $body,
	'title' => elgg_echo('blogbook:BlogBooks'),
	'sidebar' => elgg_view('blogbook/sidebar/navigation'),
));


 echo elgg_view_page(elgg_echo("blogbook:All Site Blogs"), $body);
?>
