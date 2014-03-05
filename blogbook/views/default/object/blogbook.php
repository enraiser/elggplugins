<?php 
//$body = elgg_view_title($vars['entity']->title);

  $friendly_title = elgg_get_friendly_title($vars['entity']->title);
  $tblog_url = "blogbook/view/{$vars['entity']->guid}/$friendly_title"; 
$body = elgg_view('output/url', array(
		'href' => $tblog_url,
		'text' => elgg_echo($vars['entity']->title),
		'is_trusted' => true,
	));



$body .= elgg_view('output/longtext', array('value' => $vars['entity']->description));
$body .= elgg_echo($vars['entity']->pid); 
$body .= elgg_echo($vars['entity']->guids);



$metadata = elgg_view_menu('entity', array(
	'entity' => $vars['entity'],
	'handler' => 'blogbook',
	'sort_by' => 'priority',
	'class' => 'elgg-menu-hz',
));

	$params = array(
		'entity' => $vars['entity'],
		'title' => false,
		'metadata' => $metadata,
		'subtitle' => $subtitle,
		'tags' => $tags,
	);
	$params = $params + $vars;
	$summary = elgg_view('object/elements/summary', $params);
	echo elgg_view('object/elements/full', array(
		'summary' => $summary,
		'icon' => $owner_icon,
		'body' => $body,));
?>