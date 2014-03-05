<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

require_once 'SPContacts.base.php';

class Hotmail extends SPContacts
{
	public $default_domain = "hotmail.com"; //!< if complete email is not provided as the username, the default domain will be added to it

	public function __get($name)
	{
		return parent::__get($name);
	}

	protected function GetContactsData()
	{
		if ((isset($this->username) && trim($this->username)=="") || (isset($this->password) && trim($this->password)==""))
		{
			$this->Error = ContactsResponses::ERROR_NO_USERPASSWORD;
			return false;
		}

		if (strpos($this->username, "@") === false)
		{
			//! Don't rely on this, there are many other domains supported by hotmail, allow users to enter full email address including domain part
			$this->username = "{$this->username}@{$this->default_domain}";
		}

		//! attempt login
		if (!$this->client->get("http://login.live.com/login.srf?id=2"))
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		//!$CkTst = "G" . time() . "000";
		//!$this->__cookies->set_cookie("CkTst=$CkTst", 'live.com', false);

		if (preg_match('/<form [^>]+action\="([^"]+)"[^>]*>/', $this->client->http_response_body, $matches))
		{
			$url = $matches[1];
			preg_match_all('/<input type="hidden"[^>]*name\="([^"]+)"[^>]*value\="([^"]*)"[^>]*>/', $this->client->http_response_body, $matches);
			$params = array_combine($matches[1], $matches[2]);
		}
		else
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		$sPad="IfYouAreReadingThisYouHaveTooMuchFreeTime";
		$lPad=strlen($sPad)-strlen($this->password);
		$PwPad=substr($sPad, 0,($lPad<0)?0:$lPad);

		$params['PwdPad']=$PwPad;
		if (strlen($this->password) > 16)
		{
			$this->password = substr($this->password, 0, 16);
		}

		$params['login'] = $this->username;
		$params['passwd'] = $this->password;
		$params['LoginOptions'] = "3";

		if (!$this->client->post($url, $params))
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		if (!$this->client->get_simple_cookie('MSPAuth') || !$this->client->get_simple_cookie('MSNPPAuth'))
		{
			$this->Error = ContactsResponses::ERROR_INVALID_LOGIN;
			return false;
		}

		if(preg_match('/replace[^"]*"([^"]*)"/', $this->client->http_response_body, $matches) || preg_match("/url=([^\"]*)\"/si", $this->client->http_response_body, $matches))
		{
			$this->client->get($matches[1]);
		}

		if(preg_match('#top.document.location="(http://mail.live.com[^"]*)#', $this->client->http_response_body, $matches))
		{
			$this->client->get("http://mail.live.com");
		}

		if(preg_match('/replace[^"]*"([^"]*)"/', $this->client->http_response_body, $matches) || preg_match("/url=([^\"]*)\"/si", $this->client->http_response_body, $matches) || preg_match("/self.location.href\s*=\s*'(http\\\\x3a\\\\x2f[^']*)/si", $this->client->http_response_body, $matches))
		{
			$this->client->get(urldecode(str_replace('\x', '%', $matches[1])));
		}

		if (strpos($this->client->http_response_location, "MessageAtLogin"))
		{
			$params = array();
			if(preg_match_all('/<input [^>]*name\="([^"]+)"[^>]*value\="([^"]*)"[^>]*>/si', $this->client->http_response_body, $matches))
			{
				$params = array_combine($matches[1], $matches[2]);
			}

			if(preg_match_all('/<input [^>]*value\="([^"]+)"[^>]*name\="([^"]*)"[^>]*>/si', $this->client->http_response_body, $matches))
			{
				$params = array_merge($params, array_combine($matches[2], $matches[1]));
			}

			$this->client->post($this->client->http_response_location, $params);
		}

		if (preg_match("/(?:nonce.?:.?')([^']*)/si", $this->client->http_response_body, $matches) || preg_match("/(?:(?>(?>#61;)|(?>x3d))(\d+))/si", $this->client->http_response_body, $matches))
		{
			$url = $this->client->http_response_location;
			$urlparts = explode("/", $url);
			$urlparts[count($urlparts)-1] = ($urlparts[count($urlparts)-2] == "mail" ? "" : "mail/") . "EditMessageLight.aspx?n=" . $matches[1];
			$url = implode("/", $urlparts);
			$this->client->get($url);

			if (preg_match('/"(ContactList.aspx[^"]*)/si', $this->client->http_response_body, $matches))
			{
				$urlparts[count($urlparts)-1] = ($urlparts[count($urlparts)-2] == "mail" ? "" : "mail/") . $matches[1];
				$url = implode("/", $urlparts);
				if($this->client->get($url))
				{
					$this->RawSource = $this->client->http_response_body;
					return true;
				}
				else
				{
					$this->Error = ContactsResponses::ERROR_UNKNOWN;
					return false;
				}
			}
		}

		return false;
	}

	protected function ParseContactsData()
	{
		if (preg_match_all("/(?>\[)(?>(?>'[^']*'),[^,\]]*,)'([^']*)(?>[^\[]+[\[]+){2}'([^\]']+)(?>'[\]]{2,},)/si", $this->RawSource, $matches) ||
                        preg_match_all("/(?>\[)(?>(?>'[^']+)'[^']*){2}'([^']*)(?>[^\[]+)\['([^\]']+)(?>'\]\],)/si", $this->RawSource, $matches))
		{
			list($e, $n) = SPUtils::array_multimap(array("SPUtils", "decode_html_escaped"), $matches[2], $matches[1]);
			$data = array_map(null, $n, $e);

			while (list($name, $email) = current($data))
			{
				if (strpos($email, "@"))
				{
					if ($name == "")
					{
						$name = current(explode("@", $email));
					}

					$this->__add_contact_item($name, $email);
				}
				next($data);
			}
		}
		if (!$this->ContactsCount)
		{
			$this->Error = ContactsResponses::ERROR_NO_CONTACTS;
			return false;
		}

		return true;
	}
}
?>