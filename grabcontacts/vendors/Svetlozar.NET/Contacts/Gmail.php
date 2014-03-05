<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

// NOTE: DEPRECATED

require_once 'SPContacts.base.php';
require_once SVETLOZARNET_WEBCLIENTS. 'GoogleLoginClient.php';

class Gmail extends SPContacts
{
	public static $contacts_url 	= "http://www.google.com/m8/feeds/contacts/default/property-email?max-results=9999";

	public function __get($name)
	{
		return parent::__get($name);
	}

	/**
	 * Initializes Gmail
	 * Params are all optional, if provided only the first 4 will be considered in the following order (as array of strings):
	 * @param $username
	 * @param $password
	 * @param $captcha - only if required
	 * @param $state - required together with captcha, must be the string returned by GetState() when ERROR_CAPTCHA_REQUIRED is returned
	 */
	public function __construct()
	{
		$options = func_get_args();
		$this->SetOptions($options);
		$this->client = new GoogleLoginClient();
	}


	public function GetState()
	{
		return $this->client->GetState();
	}

	public function RestoreState($state)
	{
		return $this->client->RestoreState($state);
	}

	protected function GetContactsData()
	{
		$this->client->Authenticate($this->username, $this->password, $this->captcha);
		if (!$this->client->Authenticated())
		{
			switch($this->client->LoginResponse)
			{
				case GoogleAuthResponses::BadAuthentication:
					$this->Error = ContactsResponses::ERROR_INVALID_LOGIN;
					break;
				case GoogleAuthResponses::CaptchaRequired:
					$this->Error = ContactsResponses::ERROR_CAPTCHA_REQUIRED;
					$this->CaptchaUrl = $this->client->CaptchaUrl;
					break;
				default:
					$this->Error = ContactsResponses::ERROR_UNKNOWN;
					break;
			}
			return false;
		}

		if($this->client->get(self::$contacts_url))
		{
			$this->RawSource = $this->client->http_response_body;
			$this->client->Reset();
			return true;
		}
		else
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

	}

	protected function ParseContactsData()
	{
		$parts = explode('<entry>', $this->RawSource);
		foreach($parts as $v)
		{
			if (preg_match("/(?:<title type='text'>)([^<]*)<.*?(?:<gd:email)[^>]*?address='([^']+)'/si", $v, $matches))
			{
				$name = $matches[1];
				$email = $matches[2];

				if ($name == "")
				{
					$name = current(explode("@", $email));
				}

				$this->__add_contact_item($name, $email);
			}
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