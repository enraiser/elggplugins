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

    elgg_extend_view('invitefriends/form', 'forms/invitefriendgraber', 1);

}



