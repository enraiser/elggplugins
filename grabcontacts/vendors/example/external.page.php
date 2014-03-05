<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<title>External Authentication</title>
</head>
<body>
<?php
if ($this->error)
{
?>
Request could not be completed. Please, try again later.
<a href="" onclick="window.close()">Close Window</a>
<?php
}
else
{
	echo "Redirecting...";
}
?>
<script type="text/javascript">
var QueryParms = (function(){
	var query = window.location.search.substr(1).split("&");
	var query_parms = {};
	for (i = 0; i < query.length; i++)
	{
		var parms = query[i].split("=");
		query_parms[parms[0]] = parms[1];
	}
	return query_parms;
})();

function ReturnToParent()
{
	window.opener.form_ready = true;
	window.opener.ImportContacts("oauth_verifier=" + QueryParms["oauth_verifier"], QueryParms["contacts_option"]);
	setTimeout(function() { window.close(); }, 1000);
}

function Redirect(url)
{
	if (url == "")
		return;
	window.location = url;
}

<?php if ($this->redirect_url) { ?>
window.focus();
Redirect("<?php echo $this->redirect_url; ?>");
<?php }
else if (!$this->error){
	echo 'ReturnToParent();';
}
?>
</script>
</body>