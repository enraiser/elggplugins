	<form id="import_form" action="" class="center" method="post">
	<?php if (!$this->current_class->ExternalAuth) {?>
		<table>
			<tr>
				<td>Email:</td>
				<td><input type="text" name="email" value="" style="width:90%" /></td>
			</tr>
			<tr>
				<td>Password:</td>
				<td><input type="password" name="pswd" value="" style="width:90%" /></td>
			</tr>
		</table>
		<?php if ($this->captcha_required && $this->captcha_url) {
				echo "<img src='{$this->captcha_url}'/>"; ?><br/>
				Enter text: <input type="text" name="captcha" value=""/><br/>
		<?php }	?>
	<?php } ?>
		<input type="hidden" name="state" value=""/>
		<input type="hidden" name="contacts_option" value="<?php echo $selected_option; ?>"/>
	<?php if ($this->error_returned && $this->error_message) {?>
		<span style="color:red;"><?php echo $this->error_message; ?></span><br/>
	<?php } ?>
		<button type="submit" id="btnContactsForm" value="import"><?php echo $this->current_class->ExternalAuth? "Authorize Externally" : "Import Contacts"; ?></button>
	</form>
