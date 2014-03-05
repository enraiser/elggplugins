<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

require_once SVETLOZARNET_DIR . 'SPCommon.php';
/**
 * @author Svetlozar Petrov
 * Curl wrapper class - only GET/POST requests are being handled, for PUT I would suggest setting the CURLOPT_WRITEFUNCTION and handling the request that way
 */
class SPHTTPClient
{
	//! if you need to have all connections of SPHTTPClient and its subclasses go through proxy specify it here (otherwise you can specify it per instance in $http_proxy)
	//! expected format is 'http://proxy.address:port'
	public static $global_http_proxy = null;

	public	$auto_redirect = true,				//!< auto redirect when location header is received
			$auto_redirect_curl = true,			//!< if set to true the auto redirect will be done within curl, this is much faster, set to false if CUROPT_FOLLOWLOCATION is disabled
			$auto_redirect_max = 15,			//!< maximum redirects to follow
			$curl_error_code = 0,				//!< in case curl returns an error this is where to look for it
			$curl_error_text = "",				//!< if error returned from curl this will be populated, so check here fist if there is nothing in the response body
			$return_response_headers = true,	//!< set to true to populate $http_response_headers
			$return_response_body = false,		//!< when false execute_request will return boolean http_response_code == 200
			$http_response_code,				//!< code returned from the response
			$http_response_text,				//!< the text returned with the http response code
			$http_response_location = "",		//!< last url of the request
			$http_response_headers,				//!< will be populated with 2 parallel arrays, [0] => keys, [1] => values, this is becase header may be sent multiple times
			$response_content_type,				//!< if given in the response header this will be populated
			$response_charset,					//!< if given in the response header this will be populated
			$http_response_body,
			$timeout = 40,						//!< number of seconds before curl times out
			$request_headers,					//!< any headers to send - must be given as assoc array
			$http_proxy = null,					//!< you may specify proxy per instance or provide global one in SPHTTPClient::global_http_proxy
			$curl_user_agent,
			$curl_set_referrer = true;


	protected	$__header_function;				//!< if set this will be passed to curl as the header function, otherwise the default header function will be used
	protected 	$__curl_opts;
	protected 	$__curl = null;
	protected 	$__redirect;					//!< got a location header, if auto redirect this will be used to get next location
	protected 	$__next_location;
	protected	$__request_url_parts;			//!< base uri parts (no query parms)
	protected	$__request_query_parms;			//!< set during request (before request is made)
	protected	$__encoding_callback = "rawurlencode";



	/**
	 * Constructor will take either array with curlopts ready to load or string
	 * If string is provided it must unserialize to array of curl options
	 * No validation of the array/string is done, if the data is provided by the user it should be validated (perhaps compare to a hash/signiture)
	 * @param array or string $curlopts
	 */
	function __construct($curlopts = null)
	{
		if (ini_get("safe_mode") || ini_get("open_basedir"))
		{
			$this->auto_redirect_curl = false;
		}

		$this->reset_curl_state($curlopts);
		$this->CURLOPT_ENCODING = "";
	}


	/**
	 * General cleanup, if overwritten subclasses need to make sure the base destructor is called (or close the internal __curl handle)
	 */
	function __destruct()
	{
		if ($this->__curl)
		{
			curl_close($this->__curl);
			$this->__curl = null;
		}
	}


	/**
	 * Returns serialized curl opts (can be used for restoring an SPHTTPClient object to a previous state by calling reset_state with the string produced from get_state)
	 * @return string
	 */
	function get_curl_state()
	{
		return serialize(array_diff_assoc($this->__curl_opts, array(CURLOPT_HEADERFUNCTION => '')));
	}


	/**
	 * Returns all curl options that have been set (no serialization)
	 * @return array
	 */
	function get_curl_opts()
	{
		return array_diff_assoc($this->__curl_opts, array(CURLOPT_HEADERFUNCTION => ''));
	}

	/**
	 * Reset curl options to previous or empty state (should be only used from constructor)
	 */
	protected function reset_curl_state($curlopts = null)
	{
		$this->__curl_opts = ($curlopts && is_array($curlopts)) ? $curlopts : ((($opts = unserialize($curlopts)) && $is_array($opts)) ? $opts : array());
		$this->initopts();
	}

	/**
	 * Initialize common curl options, these are stored internally, will be passed to curl later
	 * This function is called from the constructor, you can override any CURLOPT_ properties later
	 */
	protected function initopts()
	{
		$this->__curl_opts[CURLOPT_SSL_VERIFYPEER] = false;
		$this->__curl_opts[CURLOPT_TIMEOUT] = $this->timeout;
		$this->__curl_opts[CURLOPT_RETURNTRANSFER] = true;
		$this->__header_function = array($this, "header_function");

		if (($proxy = SPHTTPClient::$global_http_proxy) || ($proxy = $this->http_proxy))
		{
			$this->__curl_opts[CURLOPT_PROXY] = $proxy;
		}
	}

	/**
	 * If the internal curl resource has been initialized return will be from curl_getinfo
	 * Applies to last request executed
	 * @param $curlinfo
	 */
	public function __get($curlinfo)
	{
		$curlinfo = strtoupper($curlinfo);
		if($this->handles_get_property($curlinfo) && defined($curlinfo))
		{
			return $this->__curl ? curl_getinfo($this->__curl, constant($curlinfo)) : null;
		}
		else
		{
			trigger_error("Undefined curl info constant $curlinfo");
		}
	}

	/**
	 * SPHTTPClient automatically handles CURLOPT_* properties for any other false will be returned
	 * @param string $property - property name
	 * @return boolean - true if current class can handle a property with the property name provided
	 */
	protected function handles_set_property($property)
	{
		return (strpos($property, "CURLOPT_") === 0);
	}

	/**
	 * CURLINFO_* properties are automatically resolved, allowing access to $this->CURLINFO_* that are not explicitly defined
	 * @param $property - property name
	 * @return bool - true if property starts with CURLINFO_
	 */
	protected function handles_get_property($property)
	{
		return (strpos($property, "CURLINFO_") === 0);
	}

	/**
	 * Internally stores any CURLOPT_ options, during http request these options will be passed to the curl resource
	 * @param $property
	 * @param $value
	 */
	public function __set($curlopt, $value)
	{
		$curlopt = strtoupper($curlopt);
		if ($this->handles_set_property($curlopt) && defined($curlopt))
		{
			$this->__curl_opts[constant($curlopt)] = $value;

			switch (constant($curlopt))
			{
				case CURLOPT_POST:
					if ($value === false || $value === null)
					{
						unset($this->__curl_opts[CURLOPT_POST]);
					}
					else
					{
						$this->__curl_opts[CURLOPT_POST] = true;
					}
				case CURLOPT_POSTFIELDS:
					//! setting CURLOPT_POSTFIELDS will automatically turn on CURLOPT_POST, setting either one to null or false will turn it off
					if ($value === null)
					{
						unset($this->__curl_opts[CURLOPT_POST]);
						unset($this->__curl_opts[CURLOPT_POSTFIELDS]);
					}
					else
					{
						$this->__curl_opts[CURLOPT_POST] = true;
						$this->__curl_opts[CURLOPT_POSTFIELDS] = $value;
						unset($this->__curl_opts[CURLOPT_HTTPGET]);
					}
					break;
				case CURLOPT_HTTPGET:
					//! if true it will automatically reset CURLOPT_POST and CURLOPT_POSTFIELDS
					if (!$value)
					{
						unset($this->__curl_opts[CURLOPT_HTTPGET]);
						break;
					}
					else
					{
						$this->__curl_opts[CURLOPT_HTTPGET] = true;
						unset($this->__curl_opts[CURLOPT_POST]);
						unset($this->__curl_opts[CURLOPT_POSTFIELDS]);
					}
					break;
				case CURLOPT_FOLLOWLOCATION:
					if ($this->auto_redirect_curl && $this->auto_redirect)
					{
						$this->__curl_opts[CURLOPT_FOLLOWLOCATION] = $value;
						$this->__curl_opts[CURLOPT_MAXREDIRS] = $this->auto_redirect_max;
						if ($this->curl_set_referrer && defined(CURLOPT_AUTOREFERER))
						{
							$this->__curl_opts[CURLOPT_AUTOREFERER] = true;
						}
					}
					else
					{
						//! make sure curl follow location is off
						$this->__curl_opts[CURLOPT_FOLLOWLOCATION] = false;
					}
					break;
				case CURLOPT_HEADERFUNCTION:
					$this->__header_function = $value;
					break;
				default:
					break;
			}
		}
		else
		{
			trigger_error("Undefined curl opt constant: $curlopt");
		}
	}

	/**
	 * Add any options, overwrite options etc (a hook for subclasses to modify the request before execution)
	 * __request_query_parms is avalable here before the url has been set
	 * for post data check the stored post fields
	 */
	protected function set_request_options()
	{
		$headers = array();

		$query_str = SPUtils::join_key_values_assoc("=", "&", SPUtils::array_map_assoc($this->__encoding_callback, $this->__request_query_parms));
		$this->__curl_opts[CURLOPT_URL] = SPUtils::url_from_parts($this->__request_url_parts) . ($query_str ? "?$query_str" : "" );

		if ($this->request_headers && is_array($this->request_headers))
		{

			foreach($this->request_headers as $name => $value)
			{
				$headers[] = "{$name}: {$value}";
			}

			$this->__curl_opts[CURLOPT_HTTPHEADER] = $headers;
		}

		$this->CURLOPT_FOLLOWLOCATION = $this->auto_redirect;

		if ($this->curl_user_agent)
		{
			$this->__curl_opts[CURLOPT_USERAGENT] = $this->curl_user_agent;
		}

		if ($this->curl_set_referrer && $this->http_response_location)
		{
			$this->__curl_opts[CURLOPT_REFERER] = $this->http_response_location;
		}

		if (function_exists("curl_setopt_array"))
		{
			curl_setopt_array($this->__curl, $this->__curl_opts);
			return;
		}

		foreach ($this->__curl_opts as $key => $value)
		{
			curl_setopt($this->__curl, $key, $value);
		}
	}


	/**
	 * Handle any headers of interest
	 * Curl handle is provided if options need to be set directly on it
	 */
	protected function handle_response_headers($ch)
	{
		if($this->auto_redirect && ($location_header = array_search("Location", $this->http_response_headers[0])) !== false)
		{
			$this->__next_location = $this->http_response_headers[1][$location_header];
			$this->__redirect = true;
			if (strpos($this->__next_location, "/") === 0)
			{
				$this->__next_location = (isset($this->__request_url_parts["scheme"]) ? "{$this->__request_url_parts["scheme"]}://" : "http://") . $this->__request_url_parts["host"] . $this->__next_location;
			}
		}
		else
		{
			$this->__redirect = false;
		}
	}

	/**
	 * Execute a GET/POST request with the provided so far CURL options
	 * @param $http_request_type GET/POST
	 * @param $url
	 * @param $data - for GET this should be assoc array, for POST a string or assoc array
	 * @param $number_redirects
	 * @return bool or response body
	 */
	public function execute_request($http_request_method, $url, $data = null, $number_redirects = 0)
	{
                if (strpos($url, "/") === 0)
                {
                        $url = (isset($this->__request_url_parts["scheme"]) ? "{$this->__request_url_parts["scheme"]}://" : "http://") . $this->__request_url_parts["host"] . $url;
                }

                if (!preg_match("/^http/i", $url))
		{
			$url = isset($this->__request_url_parts["scheme"]) ? "{$this->__request_url_parts["scheme"]}://$url" : "http://$url";
		}

		$this->curl_error_text = "";
		$this->curl_error_code = 0;
		$this->http_response_code = 0;
		$this->http_response_text = "";
		$this->http_response_headers = array(array(), array());
		$this->response_content_type = "";
		$this->response_charset = "";
		$this->http_response_body = "";
		$this->__next_location = $url;
		$this->__request_url_parts = parse_url($url);
		$this->__request_query_parms = isset($this->__request_url_parts["query"]) ? SPUtils::parse_query($this->__request_url_parts["query"]) : array();

		$http_request_method = strtoupper($http_request_method);

		if ($http_request_method == SPConstants::HTTP_METHOD_POST)
		{
			# for POST it will be assumed tha data is already in the necessary format (string for url encoded or associative array for multipart)
			$this->CURLOPT_POSTFIELDS = $data;
		}
		else if ($http_request_method == SPConstants::HTTP_METHOD_GET)
		{
			$this->CURLOPT_HTTPGET = true;

			# if data is given, only associative array is expected with query parms to be updated/added to the current query
			if ($data && is_array($data) && $this->__request_query_parms)
			{
				$this->__request_query_parms = array_merge($this->__request_query_parms, $data);
			}
			else if ($data && is_array($data))
			{
				$this->__request_query_parms = $data;
			}
		}
		else
		{
			//! else continue executing the request anyways, assume it is a custom method and if all curl options are set correctly it will be handled properly
			//! For PUT requests for example use write function callback (see php curl documentation) (do it directly on $this->__curl in a subclass set_request_options)
			$this->CURLOPT_HTTPGET = false;
			$this->CURLOPT_POST = false;
		}

		if (!$this->__curl)
		{
			$this->__curl = curl_init();
		}

		if ($this->__header_function)
		{
			curl_setopt($this->__curl, CURLOPT_HEADERFUNCTION, $this->__header_function);
		}

		$this->set_request_options();
		$this->http_response_body = curl_exec($this->__curl);
		$this->CURLOPT_HTTPGET = true; # resetting post fields

		$this->http_response_location = $this->auto_redirect_curl ? curl_getinfo($this->__curl, CURLINFO_EFFECTIVE_URL) : $url;
		$this->http_response_code = curl_getinfo($this->__curl, CURLINFO_HTTP_CODE);

		$this->curl_error_code = curl_errno($this->__curl);
		$this->curl_error_text = curl_error($this->__curl);

		if ($this->curl_error_code)
		{
			return $this->return_response_body ? "" : false;
		}

		if ($this->__redirect && $this->auto_redirect && !$this->auto_redirect_curl && $number_redirects < $this->auto_redirect_max)
		{
			return $this->execute_request(SPConstants::HTTP_METHOD_GET, $this->__next_location, null, $number_redirects + 1);
		}

		$this->response_content_type = curl_getinfo($this->__curl, CURLINFO_CONTENT_TYPE);
		if ($this->response_content_type)
		{
			list($this->response_content_type, $charset) = explode(";", "{$this->response_content_type};");
			if (preg_match("/charset=([^\s;$]+)/si", $charset, $matches))
			{
				$this->response_charset = $matches[1];
			}
		}

		return $this->return_response_body ? $this->http_response_body : $this->http_response_code == SPConstants::HTTP_OK;
	}


	/**
	 * Alias for execute_request (called with "GET" as the request method)
	 * @param $url
	 * @param $data
	 * @param $number_redirects
	 * @return execute_request result
	 */
	function get($url, $data = null, $number_redirects = 0)
	{
		return $this->execute_request(SPConstants::HTTP_METHOD_GET, $url, $data, $number_redirects);
	}

	/**
	 * Alias for execute_request (called with "POST" as the request method, and url encoded data)
	 * @param $url
	 * @param $data
	 * @param $number_redirects
	 * @return execute_request result
	 */
	function post($url, $data = null, $number_redirects = 0)
	{
		if (is_array($data))
		{
			$data = SPUtils::join_key_values_encode("=", "&", $data);
		}
		return $this->execute_request(SPConstants::HTTP_METHOD_POST, $url, $data, $number_redirects);
	}

	/**
	 * Callback from CURL - handle headers
	 * @param $ch - curl handle
	 * @param $header - header returned
	 */
	function header_function($ch, $header) //!< parse any useful headers and cookies
	{
		$length = strlen($header);

		if (strpos($header, "HTTP") === 0)
		{
			# beginning of header response
			$this->http_response_headers = array(array(), array());
			$this->__redirect = false;
			if ($this->__next_location)
			{
				$this->http_response_location = $this->__next_location;
				$this->__request_url_parts = parse_url($this->__next_location);
				$this->__next_location = "";
			}
			if (preg_match("/.*?\s([^\s]*)(.*?)$/", $header, $matches))
			{
				$this->http_response_text = trim($matches[2]);
			}
		}

		if ($this->return_response_headers && preg_match("/^([^\s][^\s]*):(.*?)$/", $header, $matches))
		{
			$this->http_response_headers[0][] = trim($matches[1]);
			$this->http_response_headers[1][] = trim($matches[2]);
		}

		if((trim($header) == ""))
		{
			# end of header response
			$this->handle_response_headers($ch);
		}

		return $length;
	}
}
?>