<?php

// make sure only logged in users can see this page 
gatekeeper();

$guid = $segments[1];


$tblog = get_entity($guid);

//echo $tblog;
if (!elgg_instanceof($tblog, 'object', 'blogbook') || !$tblog->canEdit()) {
	register_error(elgg_echo('blogbook:unknown_book/chapter'));
	forward(REFERRER);
}





function tblog_get_page_content_list($guid) {
$container_guid = NULL;
	$return = array();

	$return['filter_context'] = $container_guid ? 'mine' : 'all';

	$options = array(
		'type' => 'object',
		'subtype' => 'blog',
		'full_view' => FALSE
	);

	$loggedin_userid = elgg_get_logged_in_user_guid();
	if ($container_guid) {
		// access check for closed groups
		group_gatekeeper();

		$options['container_guid'] = $container_guid;
		$container = get_entity($container_guid);
		if (!$container) {

		}
		$return['title'] = elgg_echo('blog:title:user_blogs', array($container->name));

		$crumbs_title = $container->name;
		elgg_push_breadcrumb($crumbs_title);

		if ($container_guid == $loggedin_userid) {
			$return['filter_context'] = 'mine';
		} else if (elgg_instanceof($container, 'group')) {
			$return['filter'] = false;
		} else {
			// do not show button or select a tab when viewing someone else's posts
			$return['filter_context'] = 'none';
		}
	} else {
		$return['filter_context'] = 'all';
		$return['title'] = elgg_echo('blogbook:select a blog');
		elgg_pop_breadcrumb();
		elgg_push_breadcrumb(elgg_echo('blog:blogs'));
	}

	//elgg_register_title_button();

	// show all posts for admin or users looking at their own blogs
	// show only published posts for other users.
	if (!(elgg_is_admin_logged_in() || (elgg_is_logged_in() && $container_guid == $loggedin_userid))) {
		$options['metadata_name_value_pairs'] = array(
			array('name' => 'status', 'value' => 'published'),
		);
	}
$tblog = get_entity($guid);
$bidlist = explode(",",$tblog->bids);
foreach ($bidlist  as $value) {   
        $aBlog = get_entity($value) ;
        $form_data .= "<input type='checkbox' name='bids[]' value='$value' /> $aBlog->title<br />";
 } 



$form_data .= "<input type='hidden' name='guid' value='$guid' />";	// TODO the problem is thst $guid is empty
$form_data .= elgg_view('input/submit', array('value' => elgg_echo('Remove')));

$list .=  elgg_view("input/form", array("body" => $form_data,
		    "action" => "/action/blogbook/remove",
		    "id" => "tblog_insert_form",
		    "class" => "elgg-form-alt"));


	if (!$list) {
		$return['content'] = elgg_echo('blog:none');
	} else {
		$return['content'] = $list;
	}
        
	return $return;
}


$params = tblog_get_page_content_list($guid);

	if (isset($params['sidebar'])) {
		$params['sidebar'] .= elgg_view('blog/sidebar', array('page' => $page_type));
	} else {
		$params['sidebar'] = elgg_view('blog/sidebar', array('page' => $page_type));
	}



	$body = elgg_view_layout('content', $params);

	echo elgg_view_page($params['title'], $body);
            
      

?>