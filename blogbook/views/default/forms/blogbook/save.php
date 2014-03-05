<?php



        echo "<label>".elgg_echo('title')."</label>";
	echo elgg_view('input/text', array(
		'name' => 'title',
		'value' => $vars['title'],
	));

        echo "<label>".elgg_echo('description')."</label>";

	echo elgg_view('input/longtext', array(
		'name' => 'description',
		'value' => $vars['description'],
	));

if ($vars['guid']) {
	echo elgg_view('input/hidden', array(
		'name' => 'guid',
		'value' => $vars['guid'],
	));
}
if ($vars['pid']) {
	echo elgg_view('input/hidden', array(
		'name' => 'pid',
		'value' => $vars['pid'],
	));
}
//echo '</div><div class="elgg-foot">';


echo elgg_view('input/submit', array('value' => elgg_echo('save')));

echo '</div>';
?>

