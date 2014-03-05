<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

require_once SVETLOZARNET_DIR . 'Interfaces.php';
require_once SVETLOZARNET_DIR . 'UserSettings.php';
require_once SVETLOZARNET_WEBCLIENTS . 'SPHTTPSession.php';
require_once SVETLOZARNET_WEBCLIENTS . 'SPOAuthClient.php';

/**
 * Simple class for name/email pair
 */
class SPContactsItem
{
	public $name, $email;

	function __construct($name, $email)
	{
		$this->name = $name;
		$this->email = $email;
	}
}

abstract class ContactsResponses
{
	const 	ERROR_INVALID_LOGIN = 1,
			ERROR_NO_USERPASSWORD = 2,
			ERROR_NO_CONTACTS = 3,
			ERROR_UNKNOWN = 4,
			ERROR_CAPTCHA_REQUIRED = 5,
			//! delegated authentication
			ERROR_EXTERNAL_AUTH = 6;
}

abstract class SPContactsBase
{
	//! All public fields will be populated as return
	public  $RawSource,				//!< will be populated with unparsed contents used for generating the contacts collection
			$ReturnUnparsed,		//!< if set to true only raw source will be set, no contacts data will be parsed, default is false
			$Error,					//!< if error has been returned this will be set to a non-zero constant defined above
			$ContactsCount = 0;

	protected
			$_contacts,				//!< will be populated if GetContacts() is called
			$_names, $_emails,		//!< will be populated if GetContactsArrays() is called
			$return_arrays = false,
			$client;				//!< expected SPHTTPClient or subclass of it


	/**
	 * Base constructor, subclasses need to call it for options to be handled
	 * @param array $options
	 */
	public function __construct()
	{
		$options = func_get_args();
		if ($options && is_array(current($options)))
		{
			$options = array_shift($options);
		}
		$this->ReturnUnparsed = false;
		$this->SetOptions($options);
	}

	/**
	 * Case insensitive properties for ->Contacts, ->Names, ->Emails
	 * When subclassing you must call the parent::__get for this functionality to be active
	 * These are auto loaded on first attempt to access them
	 * Example
	 * 	$c = new SPContacts("user", "pass"); //!< a subclass of it
	 *  if ($c->Contacts) //!< we got something here so you can iterate over $c->Contacts
	 *  ...
	 *  ...
	 *  or
	 *  if ($c->Names && $c->Emails) //!< if you check for names/emails, either check only one of them or use && for both
	 *  ... //!< iterate over the parallel arrays
	 * @param $name
	 * @return array
	 */
	public function __get($name)
	{
		switch (strtolower($name))
		{
			case "contacts":
				$this->return_arrays = false;
				return $this->_contacts || $this->Error == 0 && $this->GetContacts() ? $this->_contacts : null;
				break;
			case "names":
				$this->return_arrays = true;
				return $this->_names || $this->Error == 0 && $this->GetContacts() ? $this->_names : null;
				break;
			case "emails":
				$this->return_arrays = true;
				return $this->_emails || $this->Error == 0 && $this->GetContacts() ? $this->_emails : null;
				break;
			default:
				trigger_error("Unknown property " . __CLASS__ . "::$name. Apart from any public/protected properties the following are only available: contacts, names, emails");
				break;
		}
	}


	/**
	 * Reset options, contacts data etc
	 */
	abstract public function Reset();

	/**
	 * @param array of options to set (order matters)
	 */
	abstract protected function SetOptions($options);

	/**
	 * Called internally, needs to be populated in subclasses
	 * Needs to set RawSource for ParseContacsData to work on
	 * @return bool - true if contacts data source has been retrieved successfully
	 */
	abstract protected function GetContactsData();


	/**
	 * Called internally, needs to be implemented in subclasses - parse the raw source into
	 * @return bool - true if more than 0 contacts were parsed from the source
	 */
	abstract protected function ParseContactsData();

	/**
	 * Adds name/email pair either to the $contacts array (with SPContactsItem elements)
	 * 								or to the $names $emails arrays with corresponding indexes
	 * @param $name
	 * @param $email
	 */
	protected function __add_contact_item($name, $email)
	{
		if(strpos($email, "@") == 0)
		{ //!< simple check for $email field to contain actual email address
			//! "@" either in first position or not in $email (strpos will return false or 0 or actual index position)
			return;
		}

		if ($this->return_arrays)
		{
			$this->_names[] = $name;
			$this->_emails[] = $email;
		}
		else
		{
			$this->_contacts[] = new SPContactsItem($name, $email);
		}

		$this->ContactsCount++;
	}

	/**
	 * Common implementation for GetContacts, subclasses should not have to reimplement this
	 * Params are all optional, if provided only the first 4 will be considered in the following order:
	 * @param $username
	 * @param $password
	 * @param $captcha - only if required
	 * @param $state - required together with captcha, must be the string returned by GetState() when ERROR_CAPTCHA_REQUIRED is returned
	 * @return bool - true if contacts were retrieved successfully, false otherwise
	 */
	public function GetContacts()
	{
		$options = func_get_args();
		$this->SetOptions($options);

		if (!$this->return_arrays)
		{
			$this->_contacts = array();
			$this->_names = $this->_emails = "";
		}

		if (!$this->GetContactsData())
		{
			return false;
		}

		if ($this->ReturnUnparsed || $this->ParseContactsData())
		{
			return true;
		}

		return false;
	}

	/**
	 * Common implementation for GetContacts, subclasses should not have to reimplement this
	 * Params are all optional, if provided only the first 4 will be considered in the following order:
	 * @param $username
	 * @param $password
	 * @param $captcha - only if required
	 * @param $state - required together with captcha, must be the string returned by GetState() when ERROR_CAPTCHA_REQUIRED is returned
	 * @return bool - true if contacts were retrieved successfully, false otherwise
	 */
	public function GetContactsArrays()
	{
		$options = func_get_args();
		$this->SetOptions($options);
		$this->return_arrays = true;
		$this->_contacts = null;
		$this->_names = array();
		$this->_emails = array();
		return $this->GetContacts();
	}
}

/**
 * @author Svetlozar Petrov http://svetlozar.net
 * Base class for any specific client login based contact importer classes
 *
 */
abstract class SPContacts extends SPContactsBase implements IObjectState
{
	public		$CaptchaUrl;

	protected 	$username, $password, $captcha;

	/**
	 * Initializes SPContacts, should be called only from subclasses with any parameters from func_get_args()
	 * Params are all optional, if provided only the first 4 will be considered in the following order (as array of strings):
	 * @param $username
	 * @param $password
	 * @param $captcha - only if required
	 * @param $state - required together with captcha, must be the string returned by GetState() when ERROR_CAPTCHA_REQUIRED is returned
	 */
	public function __construct()
	{
		$options = func_get_args();
		parent::__construct($options);
		$this->client = new SPHTTPSession();
		$this->client->curl_user_agent = "Mozilla/5.0 (Windows; U; Windows NT 6.1; en-US; rv:1.9.2) Gecko/20100115 Firefox/3.6";
	}

	public function GetContacts()
	{
		$options = func_get_args();
		$this->SetOptions($options);
		if (!$this->username || !$this->password)
		{
			$this->Error = ContactsResponses::ERROR_NO_USERPASSWORD;
			return false;
		}

		return parent::GetContacts();
	}

	/**
	 * Called internally with parameters from constructor or GetContacts()
	 * @param $options
	 */
	protected function SetOptions($options)
	{
		if (!$options || !is_array($options))
		{
			return;
		}

		if (count($options) == 1 && is_array($options[0]))
		{
			$options = $options[0];
		}

		$this->Reset();

		switch(count($options))
		{
			case 4:
				$this->RestoreState($options[3]);
			case 3:
				$this->captcha = $options[2];
			case 2:
				$this->password = $options[1];
			case 1:
				$this->username = $options[0];
			default:
				break;
		}
	}

	function Reset()
	{
		$this->Error = $this->ContactsCount = 0;
		$this->_names = $this->_emails = $this->_contacts = null;
		$this->CaptchaUrl = null;
		$this->RawSource = "";
		$this->username = $this->password = $this->captcha = "";
	}

	/**
	 * Returns string that can be later used to restore object state
	 * @return string
	 */
	function GetState()
	{
		return "";
	}

	/**
	 * Restore state from previous state string
	 * @param $state - string returned by GetState()
	 */
	function RestoreState($state)
	{
		//! No base implementation
		//! If any subclasses need to restore state they need to provide specific implementation for both GetState and RestoreState
	}
}

/**
 * @author Svetlozar Petrov http://svetlozar.net
 * Base class for any specific external authentication based contact importer classes
 *
 */
abstract class SPContactsExtAuth extends SPContactsBase implements IObjectState
{
	//! must be defined in subclasses
	public $contacts_url = "";

	//! must be defined in subclasses (correspond to keys in settings.php)
	protected $url_key 		= "";
	protected $auth_key 	= "";

	//! Any responses are the same as SPOAuthClient responses
	//! only errors will be returned

	public $UserAuthorizationUrl = ""; //!< will be set to authorization url that the user needs to be redirected to
	public $UserAuthorizationNeeded = false; //!< will be set to true if the user needs to authorize a token

	/**
	 * Initializes SPContactsExtAuth with SPOAuthClient as default
	 * Override if you need a different client instead
	 * Default options accepted are oauth_parms and oauth_urls both assoc arrays
	 */
	public function __construct()
	{
		$urls = SPUserSettings::get_settings($this->url_key);
		$auth = SPUserSettings::get_settings($this->auth_key);

		$this->client = new SPOAuthClient();
		$this->client->auth_http_method = SPOAuthClient::HTTP_AUTH_HEADER;

		parent::__construct(array($auth, $urls));
	}

	/**
	 * Called internally with parameters from constructor or GetContacts()
	 * @param $options
	 */
	protected function SetOptions($options)
	{
		if (!$options || !is_array($options))
		{
			return;
		}

		if (count($options) == 1 && is_array($options[0]))
		{
			$options = $options[0];
		}

		$this->Reset();

		$this->client->set_oauth_parms($options[0], $options[1]);

	}

	function Reset()
	{
		$this->Error = $this->ContactsCount = 0;
		$this->_names = $this->_emails = $this->_contacts = null;
		$this->UserAuthorizationNeeded = false;
		$this->RawSource = "";
		$this->UserAuthorizationUrl = "";
	}

	public function GetContacts()
	{
		if (!parent::GetContacts())
		{
			switch ($this->client->auth_oauth_response)
			{
				case SPOAuthClient::OAUTH_USER_AUTH:
					$this->Error = ContactsResponses::ERROR_EXTERNAL_AUTH;
					$this->UserAuthorizationNeeded = true;
					$this->UserAuthorizationUrl = $this->client->get_user_authorization_url();
					break;
				case SPOAuthClient::OAUTH_ACCESS_ERROR:
					$this->Error = ContactsResponses::ERROR_EXTERNAL_AUTH;
					$this->client->reset_oauth_parms();
					break;
				case SPOAuthClient::OAUTH_HTTP_ERROR:
				case SPOAuthClient::OAUTH_RESPONSE_UNKNOWN:
				default:
					$this->Error = ContactsResponses::ERROR_UNKNOWN;
					break;
			}
			return false;
		}
		return true;
	}

	/**
	 * Returns string that can be later used to restore object state
	 * @return string
	 */
	function GetState()
	{
		if (!$this->client)
			return "";
		else
			return $this->client->GetState();
	}

	/**
	 * Restore state from previous state string
	 * @param $state - string returned by GetState()
	 */
	function RestoreState($state)
	{
		if (!$this->client)
			return;
		else
			$this->client->RestoreState($state);
	}

	/**
	 * Base implementation, expected $contacts_url to be overriden in subclasses
	 */
	function GetContactsData()
	{
		$result = false;
		$result = $this->client->attempt_request("GET", $this->contacts_url);

		if (!$result || $this->client->auth_oauth_response == SPOAuthClient::OAUTH_USER_AUTH)
		{
			return false;
		}
		else
		{
			$this->RawSource = $this->client->http_response_body;
			return true;
		}
	}

}
?>