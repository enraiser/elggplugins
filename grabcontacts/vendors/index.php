<?php
include_once 'example/contacts.main.php';
$handler = new ContactsHandler();
$handler->handle_request($_POST);
?>