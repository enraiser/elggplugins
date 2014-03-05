<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

require_once 'SPContacts.base.php';

/**
 * Most magic moved to SPContactsExtAuth
 * @author Svetlozar Petrov
 */
class PlaxoExtAuth extends SPContactsExtAuth
{
	public $contacts_url 	= "http://www.plaxo.com/pdata/contacts/@me/@all";

	protected $url_key 		= "plaxo/urls";
	protected $auth_key 	= "plaxo/oauth";

	public function __get($name)
	{
		return parent::__get($name);
	}

	function ParseContactsData()
	{
		$parts = explode('"id"', $this->RawSource);
		foreach($parts as $v)
		{
			if (preg_match('/"displayName":"((?:[^"]*(?:(?<=\\\\)")?)*?)".*?"emails":\[{"value":"([^"]*)/si', $v, $matches))
			{
				$name = SPUtils::decode_html_escaped($matches[1]);
				$email = SPUtils::decode_html_escaped($matches[2]);

				if ($name == "")
				{
					$name = current(explode("@", $email));
				}

				$this->__add_contact_item($name, $email);
			}
			else if (preg_match('/emails":\[{"value":"([^"]*).*?name":{"formatted":"((?:[^"]*(?:(?<=\\\\)")?)*?)"/si', $v, $matches))
			{ // else generally not needed any more
				$name = SPUtils::decode_html_escaped($matches[2]);
				$email = SPUtils::decode_html_escaped($matches[1]);

				if ($name == "")
				{
					$name = current(explode("@", $email));
				}

				$this->__add_contact_item($name, $email);
			}
		}

		if ($this->client->auth_url_revoke)
		{
			$this->client->get($this->client->auth_url_revoke);
			$this->client->__oauth_access_token = null;
			$this->client->oauth_token = null;
			$this->client->oauth_token_secret = null;
		}

		if ($this->ContactsCount)
		{
			return true;
		}

		$this->Error = ContactsResponses::ERROR_NO_CONTACTS;
		return false;

	}
}
?>