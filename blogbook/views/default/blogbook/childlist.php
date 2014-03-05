  <div id="wrap">
      <b><ul id="navbar">
      <!-- The strange spacing herein prevents an IE6 whitespace bug. -->
         <li><a href="#"><?php echo elgg_echo('blogbook:Jump to')."</a><ul>";
           $tblog = get_entity($vars['guid']);
           $cidlist = explode(",",$tblog->cids);  
           
           foreach ($cidlist  as $value) {   
           echo("<li><a href=\"/blogbook/view/" );
           echo($value);
           echo("\">");
           echo(get_entity($value)->title);
           echo("</a></li>");       
            }         
         echo "</ul></li>";
	    echo "<li><a href='".elgg_get_site_url()."blogbook/add/".$vars['guid']."'>".elgg_echo('blogbook:Create Sub-Chapter')."</a></li>";
         echo "<li><a href='".elgg_get_site_url()."blogbook/insertblog/".$vars['guid']."'>".elgg_echo('blogbook:Insert a blog')."</a></li>";
         echo "<li><a href='".elgg_get_site_url()."blogbook/removeblog/".$vars['guid']."'>".elgg_echo('blogbook:Remove a blog')."</a></li>";
		 ?>
 
   </b></div>
<style>
/* These styles create the dropdown menus. */
#navbar {
   margin: 0;
   padding: 0;
   height: 1em; }
#navbar li {
   list-style: none;
   float: left; }
#navbar li a {
   display: block;
   padding: 3px 8px;
   text-decoration: none; }
#navbar li ul {
   display: none; 
   width: 10em; /* Width to help Opera out */
   background-color: #69f;}
#navbar li:hover ul, #navbar li.hover ul {
   display: block;
   position: absolute;
   margin: 0;
   padding: 0; }
#navbar li:hover li, #navbar li.hover li {
   float: none; }
#navbar li:hover li a, #navbar li.hover li a {
   background-color: #69f;
   border-bottom: 1px solid #fff;
   color: #000; }
#navbar li li a:hover {
   background-color: #8db3ff; }
</style>

<script>
// Javascript originally by Patrick Griffiths and Dan Webb.
// http://htmldog.com/articles/suckerfish/dropdowns/
sfHover = function() {
   var sfEls = document.getElementById("navbar").getElementsByTagName("li");
   for (var i=0; i<sfEls.length; i++) {
      sfEls[i].onmouseover=function() {
         this.className+=" hover";
      }
      sfEls[i].onmouseout=function() {
         this.className=this.className.replace(new RegExp(" hover\\b"), "");
      }
   }
}
if (window.attachEvent) window.attachEvent("onload", sfHover);
</script>
