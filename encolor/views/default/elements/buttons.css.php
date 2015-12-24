<?php require  elgg_get_plugins_path()."encolor/colors.php"; ?>
/* **************************
	BUTTONS, modified by enRaiser
 * visit http://www.enraiser.com
************************** */
.elgg-button {
	font-family: Arial, "Helvetica Neue", Helvetica, sans-serif;
	color: <?php echo $bodybgcolor; ?>;
	width: auto;
	padding: 6px 12px;
	cursor: pointer;
	border-radius: 3px;
	box-shadow: inset 0 0 1px rgba(255, 255, 255, 0.6);

	-webkit-box-sizing: border-box;
	-moz-box-sizing: border-box;
	box-sizing: border-box;
}
.elgg-button:hover,
.elgg-button:focus {
	text-decoration: none;
}
.elgg-button-submit {	
	border: 1px solid rgba(0, 0, 0, 0.2);
	background: <?php echo $darkheader;?> ;
}
.elgg-button-submit:hover,
.elgg-button-submit:focus {
	background: #60B6F7;
}
.elgg-button-submit.elgg-state-disabled {
	background: <?php echo $tabbordercolor; ?>;
	cursor: default;
}
.elgg-button-cancel {
	border: 1px solid rgba(0, 0, 0, 0.2);
	background: #FAA51A;
}
.elgg-button-cancel:hover,
.elgg-button-cancel:focus {
	background: #E38F07;
}
.elgg-button-action {
	border: 1px solid rgba(0, 0, 0, 0.2);
	background: #F0F0F0;
	color: #333;
}
.elgg-button-action:hover,
.elgg-button-action:focus {
	background: <?php echo $tabbordercolor; ?>;
	color: #333;
}
.elgg-button-delete {
	border: 1px solid rgba(0, 0, 0, 0.2);
	background: #FF3300;
}
.elgg-button-delete:hover,
.elgg-button-delete:focus {
	background: #D63006;
}
.elgg-button-dropdown {
	background: none;
	text-decoration: none;
	display: block;
	position: relative;
	margin-left: 0;
	color: <?php echo $bodybgcolor; ?>;
	border: none;
	box-shadow: none;
	border-radius: 0;
}
.elgg-button-dropdown:hover,
.elgg-button-dropdown:focus,
.elgg-button-dropdown.elgg-state-active {
	color: <?php echo $bodybgcolor; ?>;
	background: <?php echo $headercolor; ?>;
	text-decoration: none;
}
.elgg-button-special {
	border: 1px solid rgba(0, 0, 0, 0.2);
	background: #42C5B8;
}
.elgg-button-special:hover,
.elgg-button-special:focus {
	background: #5ED9CD;
}
/* Use .elgg-size-small or .elgg-size-large for additional sizes */
.elgg-button.elgg-size-small {
	font-size: 12px;
	padding: 4px 8px;
}
.elgg-button.elgg-size-large {
	font-size: 20px;
	padding: 14px 20px;
	margin: 15px 0;
	border-radius: 5px;
}
