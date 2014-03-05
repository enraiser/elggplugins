<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

// NOTE: This is only OAuth 1 implementation, not compatible with OAuth 2

require_once 'SPHTTPClient.php';

class SPOAuthClient extends SPHTTPClient implements IObjectState
{
	//! http method to use for passing the OAuth parameters
	//! may differ from actual request method but if auth method is post, get request will be automatically converted to post
	const 	HTTP_AUTH_HEADER 	= 'HEADER',
			HTTP_AUTH_POST 		= SPConstants::HTTP_METHOD_POST,
			HTTP_AUTH_GET 		= SPConstants::HTTP_METHOD_GET;

	//! RESPONSES
	const   OAUTH_RESPONSE_UNKNOWN  = 'UNKNOWN',
	        OAUTH_USER_AUTH			= 'UserAuthorizationNeeded',
	        OAUTH_ACCESS_SUCCESS	= 'AccessTokenSuccess',
	        OAUTH_ACCESS_ERROR		= 'AccessTokenError',
	        OAUTH_HTTP_ERROR		= 'HttpError';

	//! options properties are named auth_* to not confuse with auto oauth parameters
	public 	$auth_url_request		= "",
			$auth_url_userauth 		= "",
			$auth_url_access		= "",
			$auth_url_revoke 		= "",
			$auth_handle_token		= true,				//!< intercept request, if no token is found get a request token, otherwise if token is not access token get access token and then continue
			$auth_oauth_response	= SPOAuthClient::OAUTH_RESPONSE_UNKNOWN, //!< will be set to any error/success condition after token request has been made
			$auth_http_method 		= "GET", 			//!< set to POST or HEADER, default is GET
			$auth_remove_extra	 	= true, 			//!< remove oauth fields from query or data if set to true (those will be sent only via the specified auth_http_method
			$auth_header_realm 		= "",
			$auth_header_name		= "Authorization",	//!< if HEADER is specified as the auth_http_method this will be the header name for each request
			$auth_user_authorized	= false,  			//!< will be set to true only on successful resource response
			$auth_secret			= "used in nonce generation, replace with something more unique after instantiating this class to ensure nonce will not collide with previous one"
			;

	//! parms that are not included when signing the request
	public $private_oauth_parms = array("oauth_consumer_secret", "oauth_token_secret", "oauth_callback_confirmed", "__oauth_access_token");
	public $include_in_state   = array("oauth_token", "oauth_token_secret", "__oauth_access_token");

	//! local store for all the oauth_ parameters, will be sent/updated during requests
	protected $__oauth;
	protected $__request_http_method;
	protected $__url_encoded_data; //!< store url encoded post data here for signiture

	//! interface implementation

	public function GetState()
	{
		return SPUtils::join_key_values_encode("=", "&", array_intersect_key($this->__oauth, array_flip($this->include_in_state)));
	}

	public function RestoreState($state_str)
	{
		foreach (SPUtils::parse_query($state_str) as $k => $v)
		{
			$this->__oauth[$k] = $v;
		}
	}

	/**
	 * @param array $oauth_parms - associative arrays with oauth_ parameters
	 * @param array $oauth_urls - associative array with keys: request, authorize, access, revoke (with corresponding url's as values)
	 */
	public function __construct($oauth_parms = null, $oauth_urls = null)
	{
		parent::__construct();

		$this->reset_oauth_parms();
		$this->set_oauth_parms($oauth_parms, $oauth_urls);
		$this->CURLOPT_USERAGENT = "Svetlozar.NET.OAuthClient.PHP/2010.02";
		$this->__encoding_callback = SPUtils::encode_function3986();
	}

	public function reset_oauth_parms()
	{
		$this->__oauth = array(
						"oauth_consumer_key" => "",
						"oauth_consumer_secret" => "",
						"oauth_token_secret" => "",
						"oauth_signature_method" => "HMAC-SHA1",
						"oauth_signature" => "",
						"oauth_timestamp" => "",
						"oauth_nonce" => ""
		);
	}

	function __destruct()
	{
		parent::__destruct();
	}

	public function get_auth_header($parms)
	{
		return "OAuth" . ($this->auth_header_realm ? " realm=\"" . $this->auth_header_realm . "\" " : " ") . SPUtils::join_key_values_assoc('="', '",', $parms) . '"';
	}

	/**
	 * If auth_oauth_response is OAUTH_USER_AUTH, call this function to get the authorization url (if authorize url had been initialized)
	 * Only very base authorization url is returned with appended oauth_token
	 * @return string|string
	 */
	public function get_user_authorization_url()
	{
		if ($this->auth_oauth_response != SPOAuthClient::OAUTH_USER_AUTH || !$this->oauth_token)
		{
			return "";
		}

		return $this->auth_url_userauth . (strpos($this->auth_url_userauth, "?") === false ? "?oauth_token=" : "&oauth_token=") . rawurlencode($this->oauth_token);
	}


	/**
	 * Return oauth_ property values from the local store
	 */
	public function __get($property)
	{
		if (parent::handles_get_property($property))
		{
			return parent::__get($property);
		}

		if  (isset($this->__oauth[$property]))
		{
			return $this->__oauth[$property];
		}
		else
		{
			return null;
		}
	}

	/**
	 * Allow assigning oauth_ properties directly to the instances of this class
	 * This is still SPHTTPClient subclass so anything unrecognized (possibley CURLOPT_* parms) pass to parent
	 */
	public function __set($property, $value)
	{
		if (parent::handles_set_property($property))
		{
			return parent::__set($property, $value);
		}

		if ($value == null)
		{
			unset($this->__oauth[$property]);
		}
		else
		{
			$this->__oauth[$property] = $value;
		}
	}

	/**
	 * Set oauth parms internally
	 * @param array $oauth_parms - associative array with parameters to set
	 * @param array $oauth_urls - associative array with urls and keys: request, authorize, access, revoke (all optional)
	 */
	function set_oauth_parms($oauth_parms = null, $oauth_urls = null)
	{
		if (is_array($oauth_urls))
		{
			list(
				$this->auth_url_request,
				$this->auth_url_userauth,
				$this->auth_url_access,
				$this->auth_url_revoke
				) = SPUtils::get_values($oauth_urls, "request", "authorize", "access", "revoke");
		}

		if (is_string($oauth_parms))
		{
			$oauth_parms = SPUtils::parse_query($oauth_parms);
		}

		if (is_array($oauth_parms))
		{
			foreach($oauth_parms as $k => $v)
			{
				$this->__oauth[$k] = $v;
			}
		}
	}

	function execute_request($http_request_method, $url, $data = null, $number_redirects = 0)
	{
		//! update nonce and timestamp, reset signature
		$this->oauth_nonce = md5(str_shuffle($this->auth_secret) . time() . rand());
		$this->oauth_timestamp = time();
		$this->oauth_signature = null;

		$this->__url_encoded_data = null;
		$this->__request_http_method = strtoupper($http_request_method);

		if ($this->auth_http_method == SPOAuthClient::HTTP_AUTH_POST)
		{
			//! if the auth http method has been set to POST convert a GET request to a POST request
			if ($http_request_method == SPConstants::HTTP_METHOD_GET)
			{
				$http_request_method = SPConstants::HTTP_METHOD_POST;
			}
			else if ($http_request_method == SPConstants::HTTP_METHOD_POST && $data)
			{
				//! if request is post and auth method is post assume data is provided for url encoded form content type
				$this->__url_encoded_data = is_string($data) ? SPUtils::parse_query($data) : $data;
				//! convert data to url encoded string before passing it to the base execute_request
				$data = SPUtils::join_key_values_assoc("=", "&", SPUtils::array_map_assoc($this->__encoding_callback, $data));
			}
		}

		return parent::execute_request($http_request_method, $url, $data, $number_redirects);
	}

	protected function set_request_options()
	{
		$data = $this->__url_encoded_data ? $this->__url_encoded_data : array();
		$query = $this->__request_query_parms ? $this->__request_query_parms : array();
		$auth = $this->base_auth_parms();
		if ($this->request_headers && isset($this->request_headers[$this->auth_header_name]))
		{
			unset($this->request_headers[$this->auth_header_name]);
		}

		$base_url_array = array_map("strtolower", $this->__request_url_parts);
		if (isset($base_url_array["path"]))
		{
			$base_url_array["path"] = $this->__request_url_parts["path"];
		}

		$base_url = SPUtils::url_from_parts($base_url_array);
		$base_parm_string = SPOAuthClient::construct_base_string(SPUtils::array_map_assoc($this->__encoding_callback, $query, $data, $auth));

		$this->oauth_signature = SPOAuthClient::sign_base_string(
																$this->oauth_signature_method,
																SPUtils::join_encoded3896($this->__request_http_method, $base_url, $base_parm_string),
																$this->oauth_consumer_secret,
																$this->oauth_token_secret
															);

		$auth["oauth_signature"] = $this->oauth_signature;

		switch ($this->auth_http_method)
		{
			case (SPConstants::HTTP_METHOD_GET):
				$query = array_merge($query, $auth);
				break;
			case (SPConstants::HTTP_METHOD_POST):
				$data = array_merge($data, $auth);
				$this->CURLOPT_POSTFIELDS = SPUtils::join_key_values_assoc("=", "&", SPUtils::array_map_assoc($this->__encoding_callback, $data));
				break;
			case (SPOAuthClient::HTTP_AUTH_HEADER):
				$this->request_headers[$this->auth_header_name] = $this->get_auth_header(SPUtils::array_map_assoc($this->__encoding_callback, $auth));
				break;
		}

		$this->__request_query_parms = $query;

		parent::set_request_options();
	}

	/**
	 * Auto loads a url (if access token provided) otherwise if no access token has been provided it grabs a request token
	 * You must have provided oauth parms with constructor, also oauth urls are required (again with constructor)
	 * If both URL and oauth_token provided but return is false, check if auth_user_authorized is set to true or false, also check the response code (it could be a bad request)
	 * @param string $http_request_method - http method to use for request (may differ from method used to get request/access token
	 * @param string $url - url that we want to access
	 * @param string/array $data - either url encoded string to post or assoc array (won't be url encoded for post)
	 * @return bool true if session start was successful (if starting session without url but oauth_token exists, it is unknown if this token is authorized till first request)
	 * If no token was provided to begin with, then the start session will request a token and return is true if request was successful
	 */
	function attempt_request($http_request_method, $url, $data = null)
	{
		if ($this->oauth_token)
		{
			if ($url)
			{
				return ($this->__oauth_access_token && $this->execute_request($http_request_method, $url, $data)) ? true
							: ($this->get_access_token() && $this->execute_request($http_request_method, $url, $data));
			}

			return false; //!< nothing to return really, unknown condition
		}
		else
		{
			return $this->get_request_token();
		}
	}

	/**
	 * Get a request/access token, returns true if successful (response body is in http_response_body)
	 * Called internally from get_access_token/get_request_token
	 * Parameters passed will be accepted in any given order, one bool, one string, if more given the last given will be used
	 * @param string $url  (access/request/other) token url
	 * @param bool $auto_load_response, default is false, true when requesting access token
	 * @return bool - returns true if response is HTTP 200
	 */
	protected function get_token()
	{
		$args = func_get_args();
		$url = "";
		$auto_load_response = false;

		foreach ($args as $v)
		{
			if (is_string($v))
			{
				$url = $v;
				continue;
			}

			if (is_bool($v))
			{
				$auto_load_response = $v;
				continue;
			}
		}

		if (!$url)
		{
			trigger_error("Method get_token expects a url.");
		}

		$this->execute_request($this->auth_http_method == SPOAuthClient::HTTP_AUTH_HEADER ? SPOAuthClient::HTTP_AUTH_GET : $this->auth_http_method, $url);

		//! remove oauth_token and oauth_secret, they will be loaded (if any returned) in the next lines of code)
		$this->oauth_token = null;
		$this->oauth_token_secret = null;
		$this->__oauth_access_token = null;

		if ($auto_load_response && $this->http_response_code == SPConstants::HTTP_OK)
		{
			$response_data = SPUtils::parse_query($this->http_response_body);

			if (is_array($response_data))
			{
				foreach ($response_data as $key => $value)
				{
					$this->__oauth[$key] = $value;
				}
			}
		}

		if ($this->http_response_code == SPConstants::HTTP_OK)
		{
			return true;
		}

		return false;

	}


	/**
	 * If request token is granted (HTTP 200 OK response, true will be returned) inspect the response body for parameters needed for next request
	 * Parameters passed will be accepted in any given order, one array and one string, if more given the last given will be used
	 * @return bool true on success
	 */
	public function get_request_token()
	{
		$this->auth_user_authorized = false;

		$url = $this->auth_url_request;

		$this->get_token($url, true);

		//! we got token which needs to be authorized by external redirect
		if ($this->oauth_token)
		{
			$this->auth_oauth_response = SPOAuthClient::OAUTH_USER_AUTH;
			return true;
		}

		$this->auth_oauth_response = SPOAuthClient::OAUTH_RESPONSE_UNKNOWN;
		return false;
	}

	/**
	 * If access token is granted (HTTP 200 OK response) the current object can be used for subsequent authorized requests
	 * @return bool true on success
	 */
	public function get_access_token()
	{
		$this->auth_user_authorized = false;
		$url = $this->auth_url_access;
		$this->oauth_callback = null; //!< not needed for access


		if (!$this->oauth_token)
		{
			return false; //!< don't have a token to exchange with an access one
		}

		$this->get_token($url, true);

		if ($this->http_response_code == SPConstants::HTTP_OK && $this->oauth_token)
		{
			$this->oauth_verifier = null; //!< don't need it any more if it has been provided
			$this->auth_user_authorized = true;
			$this->__oauth_access_token = "true";
			return true;
		}

		return false;
	}

	/**
	 * @returns array of oauth parms to be included in the base string
	 */
	function base_auth_parms()
	{
		$this->oauth_signature = null;
		return array_diff_key($this->__oauth, array_flip($this->private_oauth_parms));
	}


	/**
	 * Sign a base string using the given algoritm (if supported, generate notice otherwise)
	 * @param $algo
	 * @param $string_to_sign
	 * @param $consumer_secret
	 * @param $token_secret
	 * @return string signiture for the given parms
	 */
	public static function sign_base_string($algo, $string_to_sign, $consumer_secret, $token_secret)
	{
		$algo = strtoupper($algo);

		$key = SPUtils::join_encoded3896($consumer_secret, $token_secret);

		if ($algo == "HMAC-SHA1")
		{
			return base64_encode(hash_hmac("sha1", $string_to_sign, $key, true));
		}
		else if ($algo == "PLAINTEXT")
		{
			return SPOAuthClient::rfc3986_encode($key);
		}
		else
		{
			trigger_error("$algo signature method not supported");
		}
	}


	/**
	 * Construct base string from multiple assoc arrays (sorted before joined)
	 * @return string
	 */
	public static function construct_base_string()
	{
		$args = func_get_args();
		if (count($args) == 1 && is_array($args[0]) && isset($args[0][0]) && is_array($args[0][0]))
		{
			$args = $args[0];
		}
		$keys = array();
		$values = array();

		foreach ($args as $assoc_array)
		{
			$keys = array_merge($keys, array_keys($assoc_array));
			$values = array_merge($values, array_values($assoc_array));
		}

		array_multisort($keys, SORT_ASC, SORT_STRING, $values, SORT_STRING, SORT_ASC);
		return SPUtils::join_key_values("=", "&", $keys, $values);
	}
}
?>