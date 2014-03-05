<?php

gatekeeper();

// get the form inputs
$title = get_input('title');
$description= get_input('description');
$pid = get_input('pid');
//$bids = get_input('bids');
$guid = get_input('guid'); 

// create a new blogbook object

if (!$guid) {
$newobj = True;
  $tblog = new ElggObject();
  $tblog->subtype = "blogbook";
    $tblog->owner_guid = elgg_get_logged_in_user_guid();
 }
else {
	 $tblog = get_entity($guid);
	 system_message('Blogbook saving '.$guid."owner = ".$tblog->owner_guid." loggedin= ".elgg_get_logged_in_user_guid());
	 //system_message('Blogbook saving '.$guid."owner = ".$tblog->owner_guid);
	if (!$tblog->canEdit()) {
		system_message(elgg_echo('blogbook:Blogbook save failed '));
		forward(REFERRER);
	}
}

$tblog->title = $title;
$tblog->description = $description;
 
 // for now make all my_blog posts public
$tblog->access_id = ACCESS_PUBLIC;
 
 // owner is logged in user

 
 // save pid as metadata
$tblog->parent_guid= $pid;

// save to database and get id of the new my_blog
$tblog_guid =  $tblog->save();
 
 // if the blogbook was saved, we want to display the new post
 // otherwise, we want to register an error and forward back to the form

 if ( $tblog_guid) {
 
    if($newobj) { 
		if ($pid)	{
			   system_message(elgg_echo("blogbook:new chapter is created"));
			   if (!$guid) {   
			$ptblog = get_entity($pid);  
			if($ptblog->cids)$ptblog->cids .=",";
				$ptblog->cids .= $tblog_guid;           
			$ptblog->save();
			   }
		}
		else		{
			system_message(elgg_echo('blogbook:new book is created'));
			}
        add_to_river('river/object/blogbook/create','create', elgg_get_logged_in_user_guid(), $tblog->getGUID());
		}
	else{	
		system_message(elgg_echo("blogbook:book/chapter is saved"));
		}
    forward( $tblog->getURL());
 } else {
    register_error(elgg_echo('blogbook:The book/chapter could not be saved'));
    forward(REFERER); // REFERER is a global variable that defines the previous page
 }
?>