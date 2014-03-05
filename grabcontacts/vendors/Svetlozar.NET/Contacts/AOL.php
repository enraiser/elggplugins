<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

require_once 'SPContacts.base.php';

class AOL extends SPContacts
{
	protected function GetContactsData()
	{
		$this->client->curl_user_agent = "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6";

		if (strpos($this->username, "@aol.com") || strpos($this->username, "@aim.com"))
		{
			$this->username = current(explode("@", $this->username));
		}

		if ((isset($this->username) && trim($this->username)=="") || (isset($this->password) && trim($this->password)==""))
		{
			$this->Error = ContactsResponses::ERROR_NO_USERPASSWORD;
			return false;
		}

		//! attempt login
		if(!$this->client->get("https://my.screenname.aol.com/_cqr/login/login.psp?mcState=initialized&seamless=novl&sitedomain=sns.webmail.aol.com&lang=en&locale=us&authLev=2&siteState=ver%3a2%7cac%3aWS%7cat%3aSNS%7cld%3awebmail.aol.com%7cuv%3aAOL%7clc%3aen-us"))
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		$url = "https://my.screenname.aol.com/_cqr/login/login.psp";

		if (preg_match('/<form name="AOLLoginForm".*?action="([^"]*).*?<\/form>/si', $this->client->http_response_body, $matches))
		{
			$url = $matches[1];
			preg_match_all('/<input type="hidden" name="([^"]*)" value="([^"]*)".*?>/si', $matches[0], $matches);
			$params = array_combine($matches[1], $matches[2]);
		}
		else
		{
			//! ok not quite unknown, could not find the login form and without the login parms the login won't work and will return misleading error
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		$params['loginId'] = $this->username;
		$params['password'] = $this->password;

		if (!$this->client->post($url, SPUtils::join_key_values_assoc("=", "&", SPUtils::array_map_assoc("rawurlencode", $params))))
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}

		# check if login passed
		if(!$this->client->get_simple_cookie("Auth"))
		{
			#return error if it's not
			$this->Error = ContactsResponses::ERROR_INVALID_LOGIN;
			return false;
		}

		if (preg_match('/gTargetHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si',  $this->client->http_response_body, $matches) || preg_match('/gPreferredHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si', $this->client->http_response_body, $matches))
		{
			$url = "http://$matches[1]$matches[2]";
		}
		else
		{
			if(preg_match("/AV_PAGE='([^']+)/si",  $this->client->http_response_body, $matches) || preg_match("/action='([^']+)/si",  $this->client->http_response_body, $matches))
			{
				$url = $matches[1];
			}

			if (preg_match('/id="snsModule"\s+src="([^"]+)"/si',  $this->client->http_response_body, $matches))
			{
				$this->client->get($matches[1]);
				$url = $this->client->http_response_location;
			}

                        if(preg_match("/'loginForm', 'false', '([^']*)'/si",  $this->client->http_response_body, $matches))
			{
				$this->client->get($matches[1]);
				$url = $this->client->http_response_location;
			}

			if (preg_match('/gTargetHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si',  $this->client->http_response_body, $matches) || preg_match('/gPreferredHost = "([^"]*)".*?gSuccessPath = "([^"]*)"/si',  $this->client->http_response_body, $matches))
			{
				$url = "http://$matches[1]$matches[2]";
			}

                        if(preg_match('/gSuccessURL\s?=\s?"([^"]+)/si',  $this->client->http_response_body, $matches))
			{
				$url = $matches[1];
			}
		}

		$opturl = $url;

		//!get settings:
		$opturl = explode("/", $opturl);
		$opturl[count($opturl)-1] = "common/settings.js.aspx";
		$opturl = implode("/", $opturl);

		$this->client->get($opturl);

		$opturl = explode("/", $url);
		$opturl[count($opturl)-1]="AB";
		$opturl = implode("/", $opturl);

		$version = $this->client->get_simple_cookie("Version");
		$auth = $this->client->get_simple_cookie("Auth");
		$usr = "";

		if (preg_match('/"UserUID":"([^"]*)/si', $this->client->http_response_body, $matches) || preg_match('/uid:([^&]*)/si', $auth, $matches))
		{
			$usr = $matches[1];
		}

		#get the address book:
		$opturl .= "/addresslist-print.aspx?command=all&undefined&sort=LastFirstNick&sortDir=Ascending&nameFormat=FirstLastNick&version=$version&user=$usr";

		if (!$this->client->get($opturl))
		{
			$this->Error = ContactsResponses::ERROR_UNKNOWN;
			return false;
		}
		else
		{
			$this->RawSource = $this->client->http_response_body;
			return true;
		}
	}

	protected function ParseContactsData()
	{
		$m = explode("contactSeparator", $this->RawSource);

		$data = array_map(null, array_map(array("AOL", "parse_emails"), $m), array_map(array("AOL", "parse_names"), $m));

		while (list($email, $name) = current($data))
		{
			if ($email != "")
			{
				if ($name == "")
				{
					$name = current(explode("@", $email));
				}

				$this->__add_contact_item($name, $email);
			}

			next($data);
		}

		if (!$this->ContactsCount)
		{
			$this->Error = ContactsResponses::ERROR_NO_CONTACTS;
			return false;
		}

		return true;
	}

	static function parse_emails($str)
	{
		if(preg_match('/(?>Email).*?([^@<>]+@[^<]+)/si', $str, $matches))
			return trim($matches[1]);
		else
			return "";
	}

	static function parse_names($str)
	{
		if( preg_match('/fullName[^>]*>(.*?)<[^>]*>([^<]*)/si', $str, $matches) )
			return trim($matches[1]);
		else
			return "";
	}
}
?>