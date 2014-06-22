<?php

elgg_register_event_handler('init', 'system', 'enriver_init');

function enriver_init() {
    elgg_register_plugin_hook_handler('register', 'menu:owner_block', 'enriver_owner_block_menu');
    elgg_register_page_handler('enriver', 'enriver_page_handler');
    
}


 
function enriver_page_handler($segments) {
    switch ($segments[0]) {
        case 'setup':

            switch($segments[1]){
                case  'whatsapp' :
                    include elgg_get_plugins_path() . 'enriver/pages/enriver/whatsapp.php';
                    break;
                 default :
                    $group = get_entity($segments[1]);
                    if($group->whatsapp_password and $group->whatsapp_password!=""){
                        $content = "<a href='".elgg_get_site_url()."enriver/whatsapp/".$segments[1]."'>WhatsApp to all Group Members</a></h2><br><br>";
                    }else{
                        elgg_push_breadcrumb($group->name,$group->getURL());
                        elgg_push_breadcrumb("whatsapp","");
                        $content = "<h2 class='elgg-heading-main'><a href='".elgg_get_site_url()."enriver/setup/whatsapp/".$segments[1]."'>Setup WhatsApp</a></h2><br><br>";
                    }
                    $body = elgg_view_layout('one_sidebar', array('content' => $content,'sidebar' => $sidebar));
                    echo elgg_view_page("", $body);
                    break;
            }
            break;
        case 'whatsapp' :
            $group = get_entity($segments[1]);
            elgg_push_breadcrumb($group->name,$group->getURL());
            elgg_push_breadcrumb("whatsapp","");
            $content = "<h2>Send  whatsapp message to group members who registerd their number<br>";
            $content .="<form method='post' action='".elgg_get_site_url()."enriver/setup/whatsapp/".$segments[1]."/5'>";
            $content .="<textarea name='groupmessage'></textarea>";
            $content .="<br><input type='submit' class='elgg-button elgg-button-action' name='submit'></form>";
            $body = elgg_view_layout('one_sidebar', array('content' => $content,'sidebar' => $sidebar));
            echo elgg_view_page("", $body);
            break;

        case 'all':
        default:
           include elgg_get_plugins_path() . 'recommendation/pages/recommendation/all.php';
           break;
    }

    return true;
}

function enriver_owner_block_menu($hook, $type, $value, $params) {
    if (elgg_instanceof($params['entity'], 'group')) {
        if($params['entity']->canEdit()) {
            $url = "/enriver/setup/{$params['entity']->guid}";
            $item = new ElggMenuItem('socialconnect', elgg_echo('social connect'), $url);
            $value[] = $item;
        }
    }
    return $value;
}
?>