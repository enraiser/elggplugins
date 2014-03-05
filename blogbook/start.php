<?php
/**
 * Describe plugin here
 */

elgg_register_event_handler('init', 'system', 'blogbook_init');

function blogbook_init() {
	$base_dir = elgg_get_plugins_path() . 'blogbook/actions/blogbook';
     elgg_register_action("blogbook/save", $base_dir . '/save.php');
     elgg_register_action('blogbook/delete', "$base_dir/delete.php");
     elgg_register_action('blogbook/insertblog', "$base_dir/insertblog.php");
     elgg_register_action('blogbook/remove', "$base_dir/removeblog.php");

	 elgg_register_action('LocationBook/savegrp', "$base_dir/savegrp.php");
    //a little help from you to promote my site.
    elgg_extend_view('page/elements/footer','blogbook/footer/footerlink');
     // Add a menu item to the main site menu
	$item = new ElggMenuItem('blogbook',elgg_echo('blogbook:blogbook'), 'blogbook/all');
	elgg_register_menu_item('site', $item);

     elgg_register_page_handler('blogbook', 'blogbook_page_handler');

	elgg_register_entity_url_handler('object', 'blogbook', 'blogbook_url_handler');
    //    register_plugin_hook('permissions_check', 'all', 'blogbook_permissions_check');

    elgg_register_entity_type('object', 'blogbook');
 }

function blogbook_page_handler($segments) {
    if ($segments[0] == 'add') {
        include elgg_get_plugins_path() . 'blogbook/pages/blogbook/add.php';
        return true;
    }
    if ($segments[0] == 'edit') {
        include elgg_get_plugins_path() . 'blogbook/pages/blogbook/edit.php';
        return true;
    }

    if ($segments[0] == 'all') {
        include elgg_get_plugins_path() . 'blogbook/pages/blogbook/all.php';
        return true;
    }

    if ($segments[0] == 'view') {
        include elgg_get_plugins_path() . 'blogbook/pages/blogbook/view.php';
        return true;
    }

    if ($segments[0] == 'insertblog') {
        include elgg_get_plugins_path() . 'blogbook/pages/blogbook/insertblog.php';
        return true;
    }
    if ($segments[0] == 'removeblog') {
        include elgg_get_plugins_path() . 'blogbook/pages/blogbook/removeblog.php';
        return true;
    }
    return false;
}
function blogbook_permissions_check($hook_name, $entity_type, $return_value, $params) {

 	//if (elgg_instanceof($params['entity'], 'object', 'blogbook')) {
	//	return true;
	//}
}
function blogbook_url_handler($entity) {
	if (!$entity->getOwnerEntity()) {
		// default to a standard view if no owner.
		return FALSE;
	}

	$friendly_title = elgg_get_friendly_title($entity->title);

	return "blogbook/view/{$entity->guid}/$friendly_title";
}