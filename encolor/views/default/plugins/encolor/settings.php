<?php
	echo "<table id='theme_data'>";
		echo '<tr id="header_bg_test" style="border-bottom:1px solid black;"><td colspan="7" style="width:25%;"><h2 id="header_text" style="color:red;font-size:35px;padding-left:25px;">Site Name</h2></td></tr>';
		echo '<tr id="menu_bg_test" style="border-bottom:1px solid black;"><td style="padding-left:30px;">Menu Text1</td><td>Menu Text2</td><td>Menu Text3</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>';
		echo '<tr id="body_bg_test"><td colspan="5"><div style="width:20px;height:250px;display:inline-block;float:left;"></div>';
		$theme_tabs .="<br><div style='float:left;width:90%;'>";
			$theme_tbs["Tab11"] = array("text" => elgg_echo("Tab1"),"priority" => 50,"onclick" => "return false;",'selected'=>'selected');
			
			$theme_tbs["Tab22"] = array("text" => elgg_echo("Tab2"),"priority" => 150,"onclick" => "return false;");
			
			$theme_tbs["Tab33"] = array("text" => elgg_echo("Tab3"),"priority" => 200,"onclick" => "return false;");
			
			foreach($theme_tbs as $name => $theme_tab){
				$theme_tab["name"] = $name;
				elgg_register_menu_item("themetab", $theme_tab);
			}
			
			$theme_tabs .=  elgg_view_menu("themetab", array("sort_by" => "priority", "style" => "padding:0;margin-bottom:-5px;",'class' => 'elgg-menu-filter themetbs',));
			$theme_tabs .="</div>";
			echo $theme_tabs;
		echo "<br><br><br><div id='body_text' style='color:red;padding:3px;'><b>Site Content</b><br>Lorem ipsum dolor sit amet, vis partem impedit et, sale democritum mea at, veritus feugait fierent ut vim. Vix at soluta aliquam omnesque, pro tibique recteque an. Ne vix aliquid sapientem. Mea prima decore cu, vel et sint graece. Ad his principes dissentiunt, invidunt sensibus per et.</div>";
			
			echo elgg_view('input/submit', array(
				'value' => elgg_echo('Submit'),
				'name' => 'save',
				'onclick' => 'return false;',
				'id' => 'theme_submit_btn',
				'style' => 'margin:5px 0px 3px 5px;',
			));
		echo '</td><td colspan="2" style="border-left:1px solid black;"></td></tr>';
	echo "</table>";
	$keywords = array("#4786b8"=>"header","#ffffff"=>"body","#eeeeee"=>"tabbg","#ededed"=>"tabborder","#4787b8"=>"darkheader","#000000"=>"bodyfont","#080605"=>"invert");
	foreach($keywords as $key=>$keyword){
		$clr_textbox[$keyword] = elgg_view('input/text', array(
						'name' => $keyword.'_sample',
						'id' => $keyword.'_sample',
						'value' => $key,
						'style'=>'background-color:transparent;border-radius:0px;',
						'onchange'=>'func_'.$keyword.'_clr(this.value);',
					));
	}
	echo "<script>var reset_flds = ".json_encode($keywords)."</script>";
	echo "<div style='margin:10px 0px;height:36px;'><div id='prg_bar' style='display:none;text-align:center;'><img  src='".elgg_get_site_url()."vendor/elgg/elgg/_graphics/ajax_loader.GIF'><b>  . . . Please Wait . . .</b></img></div></div>";
	echo "<table id='theme_data1'>";
		echo "<tr><td colspan='7'><b>Primary Colors </b></td></tr>";
		echo '<tr>
				<td style="width:25%;">Choose Header Color : </td>
				<td style="width:10%;"><input type="color" name="params[header_color]" id="header_color" value="#60B8F7" style="padding:2px;" onchange="header_set_color(this.value);"></td>
				<td id="header1_bg_test">'.$clr_textbox["header"].'</td>
				<td style="width:25%;">Choose Body BG Color : </td>
				<td style="width:10%;"><input type="color" name="params[body_color]" id="body_color" value="#ffffff" style="padding:2px;" onchange="body_set_color(this.value);"></td>
				<td id="body1_bg_test">'.$clr_textbox["body"].'</td>
			</tr>';
		echo "<tr><td colspan='7'><b>Secondary Colors</b></td></tr>";
		echo "<tr><td>Site menu BG </td><td style='width:10%;'><input type='color' name='params[darkheader_color]' id='darkheader_color' value='#4787b8' style='padding:2px;' onchange='func_darkheader_clr(this.value);'></td><td id='darkheader_sample1'>".$clr_textbox['darkheader']."</td><td>tabbg: </td><td style='width:10%;'><input type='color' name='params[tabbg_color]' id='tabbg_color' value='#eeeeee' style='padding:2px;' onchange='func_tabbg_clr(this.value);'></td><td id='tabbg_sample1'>".$clr_textbox['tabbg']."</td></tr>";
		echo "<tr><td>Body font: </td><td style='width:10%;'><input type='color' name='params[bodyfont_color]' id='bodyfont_color' value='#000000' style='padding:2px;' onchange='func_bodyfont_clr(this.value);'></td><td id='bodyfont_sample1'>".$clr_textbox['bodyfont']."</td><td>tabborder: </td><td style='width:10%;'><input type='color' name='params[tabborder_color]' id='tabborder_color' value='#ededed' style='padding:2px;' onchange='func_tabborder_clr(this.value);'></td><td id='tabborder_sample1'>".$clr_textbox['tabborder']."</td></tr>";
		echo "<tr><td>Header Font: </td><td style='width:10%;'><input type='color' name='params[invert_color]' id='invert_color' value='#080605' style='padding:2px;' onchange='func_invert_clr(this.value);'></td><td id='invert_sample1'>".$clr_textbox['invert']."</td><td colspan='3'><input type='reset' value='Reset!' onclick='reset_fld();'></td></tr>";
	echo "</table>"; 
elgg_flush_caches();
?>

<style>	

table#theme_data1 > td{
	padding:3px;
}
table#theme_data1{
	background-color:white;
}
table#theme_data{
	border: 2px solid #DADADA;
	border-radius:5px;
	width:100%;
	background-color:white;
}
table#theme_data td1,table#theme_data1 td{
	border: 1px solid black;
}

.elgg-menu-filter{
    margin-bottom: 5px;
    border-bottom: 1px solid black;
    display: table;
    width: 100%;
}
.elgg-menu-filter  > li a{
	text-decoration: none;
    display: block;
    padding: 4px 15px 6px;
    text-align: center;
    height: auto;
    color: #666;
}
.elgg-menu-filter  > li{
    float: left;
    border: 1px solid black;
    border-bottom: 0;
    background: white;
    margin: 0 0 0 5px;
    border-radius: 3px 3px 0 0;
}
</style>

<script>
document.onready = function(e) {
//onready function
};
var bdy_tab_clr = document.getElementsByClassName('elgg-menu-themetab elgg-menu-filter');
var bdy_tab_selected_clr = bdy_tab_clr[0].childNodes[0];
var bdy_tab1_brdr_clr = bdy_tab_clr[0].childNodes[0];
var bdy_tab2_brdr_clr = bdy_tab_clr[0].childNodes[1];
var bdy_tab3_brdr_clr = bdy_tab_clr[0].childNodes[2];
function body_set_color(from){
	test_color(from,'tabbg',site_name="");
	test_color(from,'tabborder',site_name="");
	func_body_clr(from);
}
function header_set_color(from){
	test_color(from,'bodyfont',site_name="");
	test_color(from,'invert',site_name="");
	test_color(from,'darkheader',site_name="");
	func_header_clr(from);
}
function func_body_clr(from){
	//var bdy_tab_selected_clr = document.getElementsByClassName('elgg-state-selected');
	bdy_tab_selected_clr.style.backgroundColor = from;//tab bg
	document.getElementById('body_bg_test').style.backgroundColor = from;
	document.getElementById('body_color').value = from;
	var bdy1_clr = document.getElementById('body_sample');
	bdy1_clr.style.backgroundColor = from;
	bdy1_clr.value = from;
	
}
function func_header_clr(from){
	document.getElementById('header_bg_test').style.backgroundColor = from;
	//document.getElementById('header_color').value = from;
	var hdr1_clr = document.getElementById('header_sample');
	hdr1_clr.style.backgroundColor = from;
	hdr1_clr.value = from;
}
function func_darkheader_clr(color){
	document.getElementById('darkheader_color').value = color;
	document.getElementById('darkheader_sample').style.backgroundColor = color;
	document.getElementById('darkheader_sample').value = color;
	var menu_clr = document.getElementById('menu_bg_test');
	var theme_submit_btn = document.getElementById('theme_submit_btn');
	menu_clr.style.backgroundColor = color;
	theme_submit_btn.style.backgroundColor = color;
}
function func_bodyfont_clr(color){
	document.getElementById('bodyfont_color').value = color;
	document.getElementById('bodyfont_sample').style.backgroundColor = color;
	document.getElementById('bodyfont_sample').value = color;
	var bdy_clr = document.getElementById('body_text');
	bdy_clr.style.color = color;
}
function func_invert_clr(color){
	document.getElementById('invert_color').value = color;
	document.getElementById('invert_sample').style.backgroundColor = color;
	document.getElementById('invert_sample').value = color;
	var hdr_txt_clr = document.getElementById('header_text');
	hdr_txt_clr.style.color = color;
}
function func_tabbg_clr(color){
	document.getElementById('tabbg_color').value = color;
	document.getElementById('tabbg_sample').style.backgroundColor = color;
	document.getElementById('tabbg_sample').value = color;
	bdy_tab2_brdr_clr.style.backgroundColor = bdy_tab3_brdr_clr.style.backgroundColor = color;//tab bg
}
function func_tabborder_clr(color){
	document.getElementById('tabborder_color').value = color;
	document.getElementById('tabborder_sample').style.backgroundColor = color;
	document.getElementById('tabborder_sample').value = color;
	bdy_tab_selected_clr.style.border=bdy_tab1_brdr_clr.style.border = bdy_tab2_brdr_clr.style.border = bdy_tab3_brdr_clr.style.border = "1px solid "+color;
	bdy_tab_selected_clr.style.borderBottom=bdy_tab1_brdr_clr.style.borderBottom = bdy_tab2_brdr_clr.style.borderBottom = bdy_tab3_brdr_clr.style.borderBottom = "0px";
	bdy_tab_clr[0].style.borderBottom = "1px solid "+color;
}
function reset_fld(){
	location.reload();
}
function test_color(from,func,site_name){
	var site_name = 'enColor,'+elgg.get_site_url();
	var p_bar = document.getElementById('prg_bar');
	p_bar.style.display = "block";
$.ajax({

	url: "https://www.enraiser.com/services/api/rest/json",
	type: 'GET',
	data: {
		method: "tool.getcolor",
		"from" : from,
		"function":func,
		"site_name":site_name,
	},
	success: function(response) {
		var color = response.result.color;
		switch(func) {
			case 'darkheader':
				func_darkheader_clr(color);
				break;
			case 'bodyfont':
				func_bodyfont_clr(color);
				break;
			case 'invert':
				func_invert_clr(color);
				break;
			case 'tabbg':
				func_tabbg_clr(color);
				break;
			case 'tabborder':
				func_tabborder_clr(color);
				break;
		}
		
			p_bar.style.display = 'none';
		
		//alert("color = "+color);
	},
	error: function(request, status, error) {
		alert("err = "+error);
	}
});

}
</script>