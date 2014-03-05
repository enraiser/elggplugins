<?php
/**
 * Delete blog entity
 *
 * @package Blog
 */

gatekeeper();

$blog_guid = get_input('guid');
$blog = get_entity($blog_guid);

if (elgg_instanceof($blog, 'object', 'blogbook') && $blog->canEdit()) {
   if ($blog->delete()) {

    }   
} else {
	register_error(elgg_echo('blogbook:error:book_not_found'));
}

forward(REFERER);