<?php

gatekeeper();

$guid = get_input('guid');
$tblog = get_entity($guid);
$bids = get_input('bids',array());

$blogids = explode(",",$tblog->bids);
$blogids = array_diff($blogids,$bids);

$tblog->bids = implode(",",$blogids );
$tblog->save();  
forward( $tblog->getURL());
?>