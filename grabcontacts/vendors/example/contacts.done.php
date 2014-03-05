
<textarea id="selected_email_list" name="comment" class="elgg-input-longtext" style="display:none">
<?php
$emailstr='';
foreach ($post["contacts"] as $contact_email => $contact_name)
{
	echo ",".$contact_email;
}
?>

</textarea>
<script type="text/javascript">
window.onload = function() {
var emaillist = document.getElementById("selected_email_list");
parent.load_imported_emails(emaillist.value);
parent.closemyself();
}
</script>