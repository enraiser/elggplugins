php-contacts-importer
=====================

PHP Contacts Importer (Yahoo, Gmail, Plaxo, Hotmail, AOL) by Svetlozar.NET (Svetlozar Petrov)

Note: this is an open source version, APIs used may be deprecated, and non-API based code is provided as an example for web scraping (it may not be fully functional as websites like hotmail and yahoo update their interfaces from time to time, the point of releasing this code is not to keep it up to date)

Originally released under commercial license in 2006, completely reworked in early 2010.


Simple usage:

include_once './Svetlozar.NET/init.php'; // make sure path points to the correct location
$contacts_importer = ContactsHelper::GetInstance("Hotmail", "email@hotmail.com", "password");
$contacts = $contacts_importer->contacts;
// if no contacts check $contacts_importer for error

More advanced example available under the /example folder.