<?php
/**
 * Describe plugin here
 
 */

elgg_register_event_handler('init', 'system', 'engape_init');

function engape_init() {
	expose_function("reg.user",
                "eg_reg_user",
                 array("email" => array('type' => 'string'),"password" => array('type' => 'string')),
                 'Register a new Users',
                 'GET',
                 false,
                 false
                );
	expose_function("list.river",
                "eg_list_river",
                 array("offset" => array('type' => 'string'),"type" => array('type' => 'string'),"refreshlist" => array('type' => 'string'),"extra" => array('type' => 'string')),
                 'Provide List of activity',
                 'GET',
                 false,
                 true
                );
	expose_function("list.entity",
                "eg_list_entity",
                 array("type" => array('type' => 'string'),"subtype" => array('type' => 'string'),"offset" => array('type' => 'string'),"limit" => array('type' => 'string')),
                 'provide list of entity',
                 'GET',
                 false,
                 false
                );
    expose_function("get.entity",
                    "eg_get_entity",
                    array("guid" => array('type' => 'string')),
                    'Get the properties of an Entity',
                    'GET',
                    false,
                    false
                    );
    expose_function("refresh.icons",
                    "eg_refresh_entity_icons",
                    array("refreshlist" => array('type' => 'string')),
                    'returns icontimes for given  set of icons',
                    'GET',
                    false,
                    true
                    );
    
    elgg_register_page_handler('engap', 'engap_page_handler');

 }
    
    
function engap_page_handler($segments)
{
     header('Access-Control-Allow-Origin: *');
     header('Cache-Control: max-age=2592000');
    elgg_set_viewtype('engap');
        $view_path  = implode("/", $segments);
		$aj = elgg_view($view_path,array());
        //echo elgg_view($view_path,array());
		if($aj) echo $aj;
		else  {
            echo "<ons-page id='no-page'><ons-toolbar><div class='left' style='color: #1284ff;' onclick='handle_go_back()'><ons-icon icon='ion-android-arrow-back'></ons-icon>Back</div><div class='center'>Not Found</div></ons-toolbar><br>";
            
            echo "<p>The View '".$view_path."' is not found at engap_page_handler()</p>";
            echo"</ons-page>";
        }
    return true;
}
    
    
function eg_reg_user($email,$password){
	$ar=split("@",$email);
    $username = $ar[0];
    $access_status = access_get_show_hidden_status();
    access_show_hidden_entities(true);
    if ($user = get_user_by_username($username)) {
        for($tmp=2;$tmp<1000;$tmp++){
            if (!($user = get_user_by_username($username.$tmp))) {
                $username .=$tmp;
                break;
            }
        }
    }
    access_show_hidden_entities($access_status);
    $ia = elgg_set_ignore_access(true);
    $guid1 = register_user($username,$password,$username,$email);

	elgg_set_user_validation_status($guid1, true, 'manual');
	elgg_set_ignore_access($ia);
	return $guid1;
}
    
function eg_refresh_entity_icons($refreshlist){
    if($refreshlist!='none')
        $refresharr=explode(",", $refreshlist);
    else $refresharr = array();
    foreach( $refresharr as $objid){
        $obj = get_entity($objid);
        if($obj instanceof ElggEntity){
            $return[$objid]['iconurl'] = $obj->getIconURL();
            $it = $obj->icontime; if ($obj->icontime==null)$it='null';
            $return[$objid]['icontime']= $it;
        }
    }
    return $return;
}
    
function eg_list_river($offset,$type,$refreshlist,$extra){

    $owner_guid = elgg_get_logged_in_user_guid();

    $db_prefix = elgg_get_config('dbprefix');
    $option = array(
      'wheres'=>array("rv.id > ".$offset),
      'limit' =>100,

    );
    if($type='newsfeed'){
        $option['joins'] = array("JOIN {$db_prefix}entities object ON object.guid = rv.object_guid");
        $option['wheres'] = array("
                          rv.id > $offset AND (
                          rv.subject_guid = $owner_guid
                          OR rv.subject_guid IN (SELECT guid_two FROM {$db_prefix}entity_relationships WHERE guid_one=$owner_guid AND relationship='follower')
                          OR rv.subject_guid IN (SELECT guid_one FROM {$db_prefix}entity_relationships WHERE guid_two=$owner_guid AND relationship='friend'))
                          ");
        $river_list= elgg_get_river($option);
                          
    }elseif ($type='timeline'){
                if($extra == 'self')$extra = $owner_guid ;
                    $sql2 .= " FROM {$dbprefix}river rv ";
                    $sql2 .= " WHERE (rv.object_guid = $extra)";
                    $sql2 .= " OR    (rv.subject_guid = $extra)";
                    
                    $sql1 = "SELECT count(DISTINCT rv.id) as total";
                    $total = get_data_row($sql1.$sql2);
                    
                    $sql1 = "SELECT DISTINCT rv.*";
                    $sql3 .= " ORDER BY rv.posted desc LIMIT {$offset},15";
                    $river_list = get_data($sql1.$sql2.$sql3, 'elgg_row_to_elgg_river_item');
    }
    
    if($refreshlist!='none')
            $refresharr=explode(",", $refreshlist);
     else $refresharr = array();
    foreach( $refresharr as $objid){
           $obj = get_entity($objid);
           if($obj instanceof ElggEntity){
                 $return['refresh'][$objid]['iconurl'] = $obj->getIconURL();
                 $it = $obj->icontime; if ($obj->icontime==null)$it='null';
                 $return['refresh'][$objid]['icontime']= $it;
           }
     }
                                  
	foreach($river_list as $riverobj){
		$subject = $riverobj->getSubjectEntity();
		$obj = $riverobj->getObjectEntity();
		//$summary = elgg_extract('summary', $vars, elgg_view('river/elements/summary',array('item'=>$vars['item'])));
		$action = $riverobj->action_type;
        $type = $riverobj->type;
         $subtype = $riverobj->subtype ? $riverobj->subtype : 'default';
        if($riverobj->type =='comment'){
            $key = "river:comment:$type:$subtype";
            $summary = "k1".elgg_echo($key, array($subject->name, "junk"));
        }elseif($riverobj->type =='user' and $action =='update'){
  
                    if($riverobj->view == 'river/user/default/profileiconupdate'){
                     //elgg_echo('river:update:user:avatar', array($subject_link));
                        $key = 'river:update:user:avatar';
                        $summary = elgg_echo($key , array($subject->name));
                    }
        }elseif($riverobj->type =='user' and $action =='friend'){
                        $key = 'river:friend:user:default';
                        $summary=$summary = elgg_echo($key, array($subject->name, $obj->name));
                

        }else{
                $key = "river:$action:$type:$subtype";
                $object_text = $obj->title ? $obj->title : $obj->name;
                $summary = elgg_echo($key, array($subject->name, $object_text));
        }
		$objtype = $obj->type;
		$objsubtype = $obj->subtype ? $obj->subtype : 'default';			
		$objectpath = "entity/".$objtype."/".$objsubtype."/";
        $description = $obj->briefdescription ? $obj->briefdescription : elgg_get_excerpt($obj->description);
		$return['fresh'][] = array("id"=>$riverobj->id,"title"=>$summary,"description"=>$description,"subject"=>$subject->getGUID(),"object"=>$obj->getGUID(),"objectpath"=>$objectpath);
                                  
        if(!isset($return['refresh'][$subject->getGUID()])){
            $return['refresh'][$subject->getGUID()]['iconurl'] = $subject->getIconURL();
            $it = $obj->icontime; if ($obj->icontime==null)$it='null';
            $return['refresh'][$subject->getGUID()]['icontime']= $it;
        }
	}
    return $return;
}
function eg_list_entity($type,$subtype,$offset,$limit){
    $option = array(
                    'type'=>$type,
                    'offset'=>$offset,
                    'limit'=>$limit
                    
                    );
    if($subtype !='default')$option['subtype'] =$subtype;
    $entity_list= elgg_get_entities($option);
    $return = array();
    foreach($entity_list as $entity){
        $entity_title = $entity->title ? $entity->title : $entity->name;
        $description = $entity->briefdescription ? $entity->briefdescription : elgg_get_excerpt($entity->description);
	$it = $entity->icontime; if ($entity->icontime==null)$it='null';
        $return[]=array("title"=>$entity_title,"guid"=>$entity->guid,"iconurl"=>$entity->getIconURL(),'description'=>$description,"icontime"=>$it);
    }
	return $return;
}

function eg_get_entity($guid){

        $entity = get_entity($guid);
        $entity_title = $entity->title ? $entity->title : $entity->name;
        $subtype = $entity->subtype ? $riverobj->subtype : 'default';
        $description = $entity->briefdescription ? $entity->briefdescription : elgg_get_excerpt($entity->description);
        return array("title"=>$entity_title,"guid"=>$entity->guid,"iconurl"=>$entity->getIconURL(),"type"=>$entity->type,"subtype"=>$subtype,'description'=>$description);
}

