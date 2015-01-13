<?php

if($vars['value']){
	echo elgg_view('output/group_alias', $vars);
} else {
	echo elgg_view('input/text', $vars);
}
