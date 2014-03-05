<?php
/**
 * Created by JetBrains PhpStorm.
 * User: JayS
 * Date: 7/13/12
 * Time: 10:56 PM
 * To change this template use File | Settings | File Templates.
 * New blogbook river entry
 */


$object = $vars['item']->getObjectEntity();
$excerpt = strip_tags($object->description);
$excerpt = elgg_get_excerpt($excerpt);

echo elgg_view('river/elements/layout', array(
	'item' => $vars['item'],
	'message' => $excerpt,
));



