<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

// NOTE: DEPRECATED

require_once 'SPHTTPClient.php';
require_once SVETLOZARNET_DIR . 'Interfaces.php';

abstract class GoogleAuthResponses
{
	//! Authentication Responses
	const
			Authenticated			= 'Authenticated',
			BadAuthentication		= 'BadAuthentication',
			NotVerified 			= 'NotVerified',
			TermsNotAgreed 			= 'TermsNotAgreed',
			Unknown					= 'Unknown',
			CaptchaRequired 		= 'CaptchaRequired',
			AccountDeleted 			= 'AccountDeleted',
			AccountDisabled 		= 'AccountDisabled',
			ServiceDisabled 		= 'ServiceDisabled',
			ServiceUnavailable 		= 'ServiceUnavailable',
            EmailPasswordMissing 	= 'EmailPasswordMissing',
            HTTPError				= 'HTTPError';

}

class GoogleAccountTypes
{
	const
		 	GOOGLE 					= "GOOGLE",
			HOSTED 					= "HOSTED",
			HOSTED_OR_GOOGLE 		= "HOSTED_OR_GOOGLE";
}

class GoogleLoginClient extends SPHTTPClient implements IAuthState
{
	protected static 	$login_url				= "https://www.google.com/accounts/ClientLogin",
						$captcha_base_url 		= "http://www.google.com/accounts/",
						$base_application_name 	= "Svetlozar.NET.GoogleLoginClient.PHP-2010.02";

	public 	$CaptchaUrl, 		//!< will be provided if captcha input is needed for next login attempt
			$LoginResponse,		//!< will be set to one of the GoogleAuthResponses on login attempt
			$AccountType,		//!< must be one of the defined in GoogleAccountTypes
			$Service = "cp";	//!< defaults to "cp" for contacts

	private $auth_parms; //!< either login or authentication parms

	/**
	 * Constructor will either restore authenticated state (setting authenticated to true if auth token is given) or initialize the login parms to empty
	 * Do not call the constructor with login parameters, use Authenticate() instead
	 * @param string $authstate - optional, if provided it should be from GetState when authenticated is true
	 */
	public function __construct()
	{
		$authstate = func_get_args();
		if ($authstate && is_array(current($authstate)))
		{
			$authstate = array_shift($authstate);
		}

		if ($authstate)
		{
			$this->RestoreState($authstate);
		}

		if (!$this->Authenticated())
		{
			$this->Reset();
			$this->SetSource(isset($_SERVER["HTTP_HOST"]) ? $_SERVER["HTTP_HOST"] : "Unknown");
		}

		parent::__construct();
	}

	public function Reset()
	{
		$this->auth_parms = array (	"accountType" => GoogleAccountTypes::HOSTED_OR_GOOGLE,
                                    "Email" => "",
                                    "Passwd" => "",
                                    "service" => "cp",
                                    "logintoken" => "",
                                    "logincaptcha" => "",
                                    "source" => "");
	}

	/**
	 * Set google application source, do note that source will be combined with the base application name, subclasses should respect this and leave it alone
	 * @param string $source
	 */
	public function SetSource($source)
	{
		if (SPUtils::search_array($this->auth_parms, "auth"))
		{
			//! source is only needed during authentication, if we already have a token we are ok
			return;
		}

		$this->auth_parms["source"] = str_replace("-", "/", $source) . "-" . self::$base_application_name;
	}

	public function GetState()
	{
		if(SPUtils::search_array($this->auth_parms, "auth") !== false)
		{
			return rawurlencode(SPUtils::join_key_values_encode("=", "&", $this->auth_parms));
		}
		else
		{
			$a = array();
			foreach($this->auth_parms as $k => $v)
			{
				if ($k == "Email" || $k == "Passwd" || $k == "source" || !trim($v))
					continue;
				$a[$k] = $v;
			}

			return rawurlencode(SPUtils::join_key_values_encode("=", "&", $a));
		}
	}

	public function RestoreState($state)
	{
		if (!$state)
		{
			return;
		}

		$state = rawurldecode($state);
		$statearr = SPUtils::parse_query($state);
		if($auth = SPUtils::search_array($statearr, "auth"))
		{
			$this->auth_parms = $statearr;
			$this->auto_redirect_curl = false;
			$this->request_headers["Authorization"] = "GoogleLogin auth=$statearr[$auth]";
		}
		else
		{
			$this->auth_parms = array_merge($this->auth_parms, $statearr);
		}
	}

	/**
	 * This method assumes authentication has not happened previously
	 * Parameters expected: email, password, captcha, extra (where extra is retrieved via GetState() after previous unsucessful attempt, optional or null otherwise)
	 * @param string $email
	 * @param string $password
	 * @param string $captcha
	 * @param string $extra
	 */
	public function Authenticate()
	{
		$loginparms = func_get_args();
		if ($loginparms)
		{
			if (is_array(current($loginparms)))
			{
				$loginparms = array_shift($login_parms);
			}

			if(count($loginparms) > 3 && $loginparms[3])
			{
				RestoreState($loginparms[3]);
			}

			if(count($loginparms) > 2 && $loginparms[2])
			{
				$this->auth_parms["logincaptcha"] = $loginparms[2];
			}

			if(count($loginparms) > 1 && $loginparms[1])
			{
				$this->auth_parms["Passwd"] = $loginparms[1];
			}

			if(count($loginparms) > 0 && $loginparms[0])
			{
				$this->auth_parms["Email"] = $loginparms[0];
			}
		}

		if(!$this->auth_parms["Email"] || !$this->auth_parms["Passwd"])
		{
			$this->LoginResponse = GoogleAuthResponses::EmailPasswordMissing;
			return false;
		}

		$auth_string = SPUtils::join_key_values_encode("=", "&", $this->auth_parms);
		if (!$this->post(self::$login_url, $auth_string) && (!$this->http_response_code || !$this->http_response_body))
		{
			$this->LoginResponse = GoogleAuthResponses::HTTPError;
			return false;
		}

		$response = SPUtils::parse_query(join("&", explode("\n", $this->http_response_body)));
		if ($auth = SPUtils::search_array($response, "auth"))
		{
			$this->auth_parms = $response;
			$this->auto_redirect_curl = false;
			$this->request_headers["Authorization"] = "GoogleLogin auth=$response[$auth]";
			return true;
		}

		$this->LoginResponse = isset($response["Error"]) ? $response["Error"] : GoogleAuthResponses::Unknown;
		$this->auth_parms["logintoken"] = isset($response['CaptchaToken']) ? $response['CaptchaToken'] : "";
		$this->CaptchaUrl = self::$captcha_base_url . (isset($response['CaptchaUrl']) ? $response['CaptchaUrl'] : "");

		return false;
	}

	public function Authenticated()
	{
		return SPUtils::search_array($this->auth_parms, "auth") !== false;
	}
}
?>