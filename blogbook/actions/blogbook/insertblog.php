<?php


gatekeeper();

$guid = get_input('guid');
$tblog = get_entity($guid);
$bids = get_input('bids',array());


foreach($bids as $bid)
 { 
    if($tblog->bids)$tblog->bids .=",";
    $tblog->bids .= $bid;

 }
system_message($tblog->bids);
$tblog->save();  
forward( $tblog->getURL());
?>