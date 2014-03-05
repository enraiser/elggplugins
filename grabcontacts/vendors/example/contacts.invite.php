	<form action="" id="invite_form" class="center" method="post">
		<table cellpadding="4px" cellspacing="0" width="100%;">
			<tr style="background-color:#D9D9D9; overflow:hidden;">
				<th><input type="checkbox" id="ToggleSelectedAll" checked="checked" title="Toggle Selected"/></th>
				<th id="NameColumn">Name</th>
				<th id="EmailColumn">Email</th>
			</tr><?php foreach($this->contacts as $contact) {?>
			<tr style="overflow:hidden">
				<td><input type="checkbox" name="contacts[<?php echo $contact->email; ?>]" value="<?php echo $contact->name; ?>" checked="checked" /></td>
				<td><span class="Names"><?php echo $contact->name; ?></span></td>
				<td><?php echo $contact->email; ?></td>
			</tr><?php } ?>
		</table>
		<button type="submit" id="btnContactsForm" value="invite" onsubmit="alert("OOOOO.")">Select These friends</button>
	</form>
