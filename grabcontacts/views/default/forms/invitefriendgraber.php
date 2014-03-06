<?php

echo  elgg_view('output/grabcontacts', array('entity'=>elgg_get_logged_in_user_entity(),));
?>
<script type="text/javascript">
function   load_imported_emails(emaillist){
	var emailid_textarea = document.getElementsByName('emails');
	emailid_textarea[0].value = emaillist;
}
</script>




