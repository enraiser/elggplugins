<?php
/**
 * View a single blogbook
 *
 * @package Elggtblogs
 */
function recursive_breadcrumb($tblog)
{
  if($tblog->parent_guid !='')
  {
  recursive_breadcrumb(get_entity($tblog->parent_guid));
  }
   
   elgg_push_breadcrumb($tblog->title,"/blogbook/view/$tblog->guid");
}

$tblog_guid = $segments[1];

$tblog = get_entity($tblog_guid);
if (!$tblog) {
	forward();
}
//echo $tblog->guid;
//echo $tblog->parent_guid;

elgg_push_breadcrumb(elgg_echo('blogbook:book'),"blogbook/all");
if($tblog->parent_guid !='')
  {
  $ptblog = get_entity($tblog->parent_guid);
  recursive_breadcrumb($ptblog);
}

$title = $tblog->title;

$content = elgg_view_entity($tblog, array('full_view' => true));

$bidlist = explode(",",$tblog->bids);

        foreach ($bidlist  as $value) {   
      	$ablog = get_entity($value) ;
			if($ablog != false) {
				$content .=  elgg_view_entity($ablog);
    		  	}
        } 
	  
$content .= "<hr><b>".elgg_echo('blogbook:Sub-Chapter(s)')."</b><ul>";


           
           $cidlist = explode(",",$tblog->cids);  
           
           foreach ($cidlist  as $value) {   
           $content .= "<li type=square><a href=\"/blogbook/view/";
           $content .=$value;
           $content .="\">";
           $content .=get_entity($value)->title;
           $content .="</a></li>";       
            } 
           
         $content .="</ul>";
$content .= elgg_view_comments($tblog);

if (elgg.isloggedin) {


	elgg_register_menu_item('title', array(
			'name' => '',
			'href' => "",
			'text' => elgg_view('blogbook/childlist',array('guid' => $tblog_guid)),
			'link_class' => '',
	));
}

$body = elgg_view_layout('content', array(
	'filter' => '',
	'content' => $content,
	'title' => $title,
	'sidebar' => elgg_view('blogbook/sidebar/navigation'),
));

echo elgg_view_page("", $body);

