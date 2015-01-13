<?php
/**
 * Group Alias activation script
 * It sets an alias if group hasn't.
 */

foreach(elgg_get_entities(array('type' => 'group')) as $group){
	if(!$group->alias){
		$alias = elgg_get_friendly_title($group->name);
		$alias = preg_replace("/-/", "_", $alias);
		// If alias is token
		if(get_group_from_group_alias($alias)){
			$alias .= $group->guid;
		}
		$group->alias = $alias;
		$group->save();
	}
}
