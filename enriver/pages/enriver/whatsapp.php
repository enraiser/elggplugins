<?php

    function enriver_onCodeRequest($mynumber, $method,$length)
    {

       // echo "you will get a ".$method." of length ".$length." on ".$mynumber;
         //   echo "<br>----------------------<br>";

    }
    function enriver_onCodeRegister($phone, $login, $pw, $type, $expiration, $kind, $price, $cost, $currency, $price_expiration){
        $group = get_entity($_SESSION['enriver_setup_group_guid']);
        $group->whatsapp_password =$pw;
    }

    $group = get_entity($segments[2]);
    elgg_push_breadcrumb($group->name,$group->getURL());
    elgg_push_breadcrumb("whatsapp","");
    $_SESSION['enriver_setup_group_guid'] =$segments[2];
    if ($group->canEdit()) {
    $step = $segments[3];
        if($step ==''){
            $content = "<h2>Enter your phone detail</h2>Please dont use the phone on which you have installed whatsapp,(otherwise your current whatsapp will stop working for few minutes).Use countrycode without +";
            $content .="<form method='post' action='".elgg_get_site_url()."enriver/setup/whatsapp/$segments[2]/2'>";
            $content .="<br><table><tr><td><label>Mobile Number&nbsp</label></td><td><input type='text' style='width:400px;'name='mobile'></td></tr>";
            $content .="<tr><td><label>IMEI&nbsp</label></td><td><input type='text' style='width:400px; name='imei'></td></tr>";
            $content .="<tr><td><label>Nick Name&nbsp</label></td><td><input type='text' style='width:400px; name='nickname'></td></tr>";
            $content .="<tr><td colspan='2'><input type='submit' class='elgg-button elgg-button-action' name='submit'><td></tr></table></form>";
        }elseif($step =='2'){
            $group->whatsapp_mobile = $_POST['mobile'];
            $group->whatsapp_imei = $_POST['mobile'];
            $group->whatsapp_name = $_POST['mobile'];
            $group->save();
            require elgg_get_plugins_path().'enriver/vendors/whatsapi/whatsprot.class.php';
            //echo ."/".."/". ;
            $wa = new WhatsProt($_POST['mobile'], $_POST['imei'], $_POST['nickname'], false);
            $wa->eventManager()->bind("onCodeRequest", "enriver_onCodeRequest");
            $wa->connect();
            $wa->codeRequest();
              for($i = 0; $i < 5; $i++) {
                  $wa->pollMessages();
              }
            $content = "<h2>You will get 6 digit code on your mobile,Enter the 6 digit code here (without dash)";
            $content .="<form method='post' action='".elgg_get_site_url()."enriver/setup/whatsapp/$segments[2]/3'>";
            $content .="<input type='text' name='code'>";
            $content .="<input type='submit' name='submit'></form>";
        }elseif($step =='3'){
            require elgg_get_plugins_path().'enriver/vendors/whatsapi/whatsprot.class.php';
            $wa = new WhatsProt($group->whatsapp_mobile,$group->whatsapp_imei,$group->whatsapp_name , false);
            $wa->eventManager()->bind("onCodeRegister", "enriver_onCodeRegister");
             $wa->connect();
      
            $wa->codeRegister($_POST['code']);
            for($i = 0; $i < 5; $i++) {
                $wa->pollMessages();
            }
            $content = "<h2>Send Test message to other phone number,(make sure other phone has ".$group->whatsapp_mobile." in contact. ";
            $content .="<form method='post' action='".elgg_get_site_url()."enriver/setup/whatsapp/$segments[2]/4'>";
            $content .="<label>Mobile Number&nbsp</label> <input type='text' style='width:400px;' name='testmobile'>";
            $content .="<br><input type='submit'class='elgg-button elgg-button-action' name='submit'></form>";
        }elseif($step =='4'){
           // echo $group->whatsapp_mobile."".$group->whatsapp_password;
            require elgg_get_plugins_path().'enriver/vendors/whatsapi/whatsprot.class.php';
            $wa = new WhatsProt($group->whatsapp_mobile,$group->whatsapp_imei,$group->whatsapp_name , false);
            $wa->connect();
            $wa->loginWithPassword($group->whatsapp_password);
           // $wa->sendSetProfilePicture($group->getIconURL());
            $wa->sendMessage($_POST['testmobile'], "You have done it,Visit http://enraiser.com visit  http://Kindit.org ");
                       for($i = 0; $i < 5; $i++) {
                $wa->pollMessages();
            }
            $content = "check now";
        }elseif($step =='5'){
            require elgg_get_plugins_path().'enriver/vendors/whatsapi/whatsprot.class.php';
            $wa = new WhatsProt($group->whatsapp_mobile,$group->whatsapp_imei,$group->whatsapp_name , false);
            $wa->connect();
            $wa->loginWithPassword($group->whatsapp_password);
            $members = $group->getMembers();
            foreach($members as $member){
                if($member->mobile and $member->mobile!=""){
                    $targets[] =$member->mobile;
                    $wa->sendMessage($member->mobile, $_POST['groupmessage']);
                }
            }

            for($i = 0; $i < 5; $i++) {
                $wa->pollMessages();
            }
            $content = "check now";

    $content = "<h2 class='elgg-heading-main'>done</h2><br><br>";
    }
}else{
    $content = "<h2 class='elgg-heading-main'>No permission</h2><br><br>";
}
$body = elgg_view_layout('one_sidebar', array('content' => $content,'sidebar' => $sidebar));
echo elgg_view_page("", $body);