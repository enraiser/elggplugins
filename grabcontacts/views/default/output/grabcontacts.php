<?php
/*
include_once  elgg_get_plugins_path() .  'grabcontacts/vendors/Svetlozar.NET/init.php';
include_once  elgg_get_plugins_path() .  'grabcontacts/vendors/example/contacts.main.php';

$handler = new ContactsHandler();
$handler->handle_request($_POST);

<iframe src="/mod/grabcontacts/vendors/index.php" width="700" height="350" scrolling="auto"></iframe> 

*/




	$params = array(
		'text' => "import from Yahoo/Gmail/Hotmail/AOL",
		'title' => elgg_echo('likes:see'),
		'rel' => 'popup',
		'href' => "#contact-grabber"
	);
	echo elgg_view('output/url', $params);
	echo "<div class='elgg-module elgg-module-popup s hidden clearfix' id='contact-grabber' style='width: 700px; position: absolute;'>";
	echo "<iframe src = '/mod/grabcontacts/vendors/index.php' width='700' height='350' scrolling='auto'></iframe>";
	echo "</div>"

	

?>
<script type="text/javascript">
function closemyself()
{
var graberdiv = document.getElementById('contact-grabber');
graberdiv.style.display = 'none';
}
</script>



