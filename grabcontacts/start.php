<?php
/**
 * Elgg grabcontacts plugin
 * @license: GPL v 2.
 * @author Sachin
 * @copyright TechIsUs
 * @link enraiser.com
 */

register_elgg_event_handler('init','system','grabcontacts_init');

function grabcontacts_init() {



	// Register a page handler, so we can have nice URLs
	register_page_handler('grabcontacts','grabcontacts_page_handler');
    elgg_extend_view('invitefriends/form', 'output/grabcontacts', 1);

}

function grabcontacts_page_handler($page) {
			
	gatekeeper();

	$title = sprintf(elgg_echo('grabcontacts:title:everyone'), elgg_get_config('sitename'));

	$body = elgg_view('grabcontacts/everyone');

	$sidebar = elgg_view("grabcontacts/grabcontacts");

	$params = array(
		'content' => $body,
		'title' => $title,
		'sidebar' => $sidebar,
	);
	$body = elgg_view_layout('one_sidebar', $params);

	echo elgg_view_page($title, $body);
			
}

