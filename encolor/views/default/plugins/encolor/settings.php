<?php
$header_settings = elgg_get_plugin_setting('header_color', 'encolor');
$body_settings = elgg_get_plugin_setting('body_color', 'encolor');
echo '<b>Choose Header Color</b> : <input type="color" name="params[header_color]" id="header_color" value="'.$header_settings.'"><br /><br />';
echo '<b>Choose Body BG Color</b> : <input type="color" name="params[body_color]" id="body_color" value="'.$body_settings.'"><br /><br />';
elgg_flush_caches();
//echo "<br>".elgg_view('input/submit', array('value' => elgg_echo('submit')));


?>