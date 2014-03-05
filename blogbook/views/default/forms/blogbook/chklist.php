<?php

$tblog = $vars['entity'];
$guido =$vars['guid'];
echo $guido;

echo $tblog->title;
$bloglist = elgg_get_entities(array(
	'type' => 'object',
	'subtype' => 'blog',
	'limit' =>'0',

));
foreach($vars as $val)
{
echo $val;
}
echo count($vars);



echo '<div>';
foreach($bloglist  as $aBlog)
 { 
  echo ("<input type='checkbox' name='cuid' value='$aBlog->guid' /> $aBlog->title<br />");	
 }

echo elgg_view('input/submit', array('value' => elgg_echo('Insert2')));

echo '</div>';
?>

