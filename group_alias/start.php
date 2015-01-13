<?php
/**
 * Elgg Group Alias
 *
 * @package ElggGroupAlias
 */

elgg_register_event_handler('init', 'system', 'group_alias_init');

/**
 * Initialize the group alias plugin.
 *
 */
function group_alias_init() {

	// Register a page handler, so we can have nice URLs
//	elgg_register_page_handler('g', 'group_alias_page_handler');
	elgg_register_plugin_hook_handler('forward', '404', 'group_url_router', 0);
    
	// Override URL handlers for groups
	elgg_register_entity_url_handler('group', 'all', 'group_alias_url');
	
	// Add alias field
	elgg_register_plugin_hook_handler('profile:fields', 'group', 'group_alias_field_setup');

	// Override some actions
	$action_base = elgg_get_plugins_path() . 'group_alias/actions/groups';
	elgg_register_action("groups/edit", "$action_base/edit.php");

	// Extend the main css view
	elgg_extend_view('css/elgg', 'group_alias/css');
	elgg_extend_view('js/elgg', 'group_alias/js');
	
}
function group_url_router($hook, $type, $return, $params) {
        
        $base_path = parse_url(elgg_get_site_url(), PHP_URL_PATH);
        $current_path = parse_url($params['current_url'], PHP_URL_PATH);
        $current_path = ($base_path == '/') ? substr($current_path,1) : str_replace($base_path, '', $current_path);
        $parts = explode('/', $current_path);
    
        if (count($parts) == 1 && $group = get_group_from_group_alias($parts[0])) {
            elgg_set_context('group');
            if (group_alias_page_handler($parts)) {
                exit;
            }
        }
        
        return $return;
    }
function get_group_from_group_alias($alias){
	$g = elgg_get_entities_from_metadata(array(
		'type' => 'group',
		'metadata_name' => 'alias',
		'metadata_value' => $alias,
		'limit' => 1,
	));
	return $g[0];
}

/**
 * Dispatcher for group alias.
 * URLs take the form of
 *  All groups:       g/
 *  Group profile:    g/<alias>
 *  Group Tools:      g/<alias>/<handler> => <handler>/group/<guid>
 *
 * @param array $page
 * @return bool
 */
function group_alias_page_handler($page) {
	
	elgg_set_context('groups');

	if (!isset($page[0])) {
		groups_page_handler(array('all'), 'groups');
		return true;
	}
	
	$group = get_group_from_group_alias($page[0]);
	
	if($group && !isset($page[1])){
		groups_page_handler(array('profile', $group->guid));
		
	} elseif($group && isset($page[1])) {
		forward("$page[1]/group/$group->guid");
		
	} else {
		groups_page_handler($page);
	}
	
	return true;
}

function group_alias_field_setup($hook, $type, $return, $params) {
	return array_merge(array('alias' => 'group_alias'), $return);
}

/**
 * Override the group url
 * 
 * @param ElggObject $group Group object
 * @return string
 */
function group_alias_url($group) {
	if(!$group->alias){
		return groups_url($group);
	}
	return "$group->alias";
}
