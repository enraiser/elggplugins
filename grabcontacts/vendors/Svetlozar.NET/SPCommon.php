<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

class SPConstants
{
	/**
	 * RESPONSE CODES CONSTANTS (only most commonly used subset)
	 */
	const	HTTP_OK = 200,
			HTTP_MOVED_PERMANENTLY = 301,
			HTTP_FOUND = 302,
			HTTP_BAD_REQUEST = 400,
			HTTP_UNAUTHORIZED  = 401,
    		HTTP_FORBIDDEN = 403,
    		HTTP_NOT_FOUND = 404,
    		HTTP_METHOD_NOT_ALLOWED = 405;

	/**
	 * Request methods
	 *
	 */
	const	HTTP_METHOD_GET = "GET",
			HTTP_METHOD_POST = "POST";

}

/**
 * @author Svetlozar Petrov http://svetlozar.net
 * Class of utility functions, mostly related to handling arrays and any other web request related common tasks
 */
class SPUtils
{
	public static $encode_callbacks = array ("default" => "rawurlencode");
	/**
	 * Return updated url (current request url if provided url is null) with the provided query parms
	 * @param string $url - optional - if not given the current request url will be used instead
	 * @param array $query_parms - associative array or ready query string
	 * @param bool $replace_query - replace current query completely (default is false which will result in merging the provided query parms with the parms in the url)
	 * @return string
	 */
	public static function update_url($url = null, $query_parms, $replace_query = false)
	{
		if (!$url)
		{
			$request_url = SPUtils::url_from_parts( array("host" => $_SERVER["HTTP_HOST"],
							"scheme" => isset($_SERVER["HTTPS"]) ? "https" : "http",
							"port" => $_SERVER["SERVER_PORT"],
							"path" => $_SERVER["SCRIPT_NAME"]));
			$url_query_parms = $replace_query ? array() : $_GET;
		}
		else
		{
			$url_parts = parse_url($url);
			$url_query_parms = isset($url_parts['query']) && !$replace_query ? SPUtils::parse_query($url_parts['query']) : array();
			$request_url = SPUtils::url_from_parts($url_parts);
		}

		$query_parms = is_array($query_parms) ? $query_parms : SPUtils::parse_query($query_parms);

		$query_parms = array_merge($url_query_parms, $query_parms);

		$query = "";
		if ($query_parms)
		{
			$query = SPUtils::join_key_values_encode("=", "&", $query_parms);
		}

		return rtrim("$request_url?$query", "?");
	}

	/**
	 * Returns the current request url
	 * @return string
	 */
	public static function current_request_url()
	{
		$request_url = SPUtils::url_from_parts( array("host" => $_SERVER["HTTP_HOST"],
							"scheme" => isset($_SERVER["HTTPS"]) ? "https" : "http",
							"port" => $_SERVER["SERVER_PORT"],
							"path" => $_SERVER["SCRIPT_NAME"]));

		$query = "";
		if($_GET)
		{
			$query = SPUtils::join_key_values_encode("=", "&", $_GET);
		}

		return rtrim("$request_url?$query", "?");
	}

	static function hex_utf8($hex)
	{
		$v = hexdec($hex);
		return dec_utf8($v);
	}

	static function dec_utf8($v)
	{
		if ( $v >> 7 == 0 )
		{
			return $v;
		}

		$b = 128;
		$c = "";
		while( $v > 32)
		{
			$c = chr(128 | (63 & $v)) . $c;
			$v = $v >> 6;
			$b = 128 | ($b >> 1);
		}

		return chr($b | $v) . $c;
	}

	public static function decode_html_escaped($str)
	{
		return html_entity_decode(urldecode(
			str_replace('\\x', '%',
			preg_replace('/\\\u([0-9A-Fa-f]{1,4})/e', "'&#'. 0x$1 .';'", $str)
			)), 0, "UTF-8");
	}


	/**
	 * Returns parsed query as assoc array (using parse string which returns it into a parm passed by ref)
	 * @param string $query
	 * @return array $parsed
	 */
	public static function parse_query($query)
	{
		return (parse_str($query, $parsed) || true) ? $parsed : array();
	}

	/**
	 * Search array for a key case insensitive, return key proper cased
	 * @param string $needle
	 * @param array $assoc_array
	 * @return bool false or properly cased key
	 */
	public static function search_array($assoc_array, $key)
	{
		if (!is_array($assoc_array) || !$assoc_array)
		{
			return false;
		}

		if (isset($assoc_array[$key]))
		{
			return $assoc_array[$key];
		}

		$keys = array_keys($assoc_array);
		$keystolower = array_map("strtolower", $keys);

		if (($i = array_search(strtolower($key), $keystolower)) !== false)
		{
			return $keys[$i];
		}
		return false;
	}

	/**
	 * Return array of values for a list of keys (case-insensitive), returns empty strings for keys that are not found in the array
	 * @param array $array
	 * @param string $key1
	 * @param string $key2
	 * @param string $key3
	 * ...
	 * @return string
	 */
	public static function get_values()
	{
		$args = func_get_args();
		if (!$args)
		{
			return $args;
		}

		$array = array_shift($args);

		if (!$array)
		{
			return count($args) ? array_fill(0, count($args), "") : array();
		}

		if (count($array) == 1 && is_array($array[0]))
		{
			$array = $array[0];
		}

		//! there may be a better/faster way?

		$inputvalues = array_values($array);
		list($keys, $args) = self::array_multimap("strtolower", array_keys($array), $args);
		$keys = array_flip($keys);

		$values = array();

		foreach($args as $key)
		{
			if (isset($keys[$key]))
			{
				$values[] = $inputvalues[$keys[$key]];
			}
			else
			{
				$values[] = "";
			}
		}

		return $values;
	}

	/**
	 * Return value for a given key (case-insensitive) or an empty string if not found
	 * @param array $array
	 * @param string $key
	 * @return string
	 */
	public static function get_value($array, $key)
	{
		return ($key = self::search_array($key, $array)) ? $array[$key] : "";
	}



	/**
	 * Returns the base url (no query) from parts
	 * @param array $url_parts returned by parse_url
	 * @return string
	 */
	public static function url_from_parts($url_parts)
	{
                if (strpos($url_parts["host"], ":"))
                {
                    $url_parts["host"] = current(explode(":", $url_parts["host"]));
                }
		if (isset($url_parts["port"]) &&
			!(($url_parts["scheme"] == "http" && $url_parts["port"] == "80") || ($url_parts["scheme"] == "https" && $url_parts["port"] == "443")))
		{
			$url_parts["port"] = ":$url_parts[port]";
		}
		else
		{
			$url_parts["port"] = "";
		}

		if (!isset($url_parts["path"]))
		{
			$url_parts["path"] = "/";
		}
		return sprintf("%s://%s%s%s", $url_parts["scheme"], $url_parts["host"], $url_parts["port"], $url_parts["path"]);
	}

	/**
	 * Join any number of strings, first parameter is assumed to be glue
	 * mostly useful when called from array_map with multiple arrays
	 * otherwise use implode($separator, array(str1, str2, str3...))
	 * @param string $separator
	 * @param string $str1
	 * @param string $str2
	 * @param string $str3 ...
	 * @return string
	 */
	public static function str_join()
	{
		$args = func_get_args();

		if (!$args)
		{
			return null;
		}

		$glue = array_shift($args);

		if ($glue == null)
		{
			$glue = "";
		}

		return $args ? implode($glue, $args) : $glue;
	}

	/**
	 * Concatenate any number of strings
	 * alias for implode("", array($str1, $str2, $str3...))
	 * @param string $separator
	 * @param string $str1
	 * @param string $str2
	 * @param string $str3 ...
	 * @return string
	 */
	public static function str_concat()
	{
		$args = func_get_args();
		return implode("", $args);
	}


	/**
	 * Join key value arrays (either one assoc array or one array with keys and another with values)
	 * @param string $key_separator
	 * @param string $item_separator
	 * @param string $encode_callback (optional) - must correspond to index in self::encode_callbacks (should not be array!)
	 * @param array $keysorassoc - array with keys or array with values
	 * @param array $values - if provided the $keysorassoc will be considered array of keys
	 * @return string
	 */
	public static function join_key_values_encode($key_separator, $item_separator)
	{
		$args = func_get_args();

		if (!$args)
		{
			return $args;
		}

		$key_separator = ($first = array_shift($args)) ? $first : "";
		$item_separator = ($first = array_shift($args)) ? $first : "";

		if (!count($args))
		{
			return "";
		}

		$encode_callback = self::$encode_callbacks["default"];
		if (!is_array(current($args)))
		{
			$encode_callback = self::$encode_callbacks[array_shift($args)];
		}

		$keys = array_shift($args);
		$values = array_shift($args);

		if ($values == null)
		{
			$values = array_map($encode_callback, array_values($keys));
			$keys = array_map($encode_callback, array_keys($keys));
		}
		else
		{
			list($keys, $values) = self::array_multimap($encode_callback, $keys, $values);
		}

		return self::join_key_values($key_separator, $item_separator, $keys, $values);
	}


	/**
	 * Join key/values as key followed by key separator followed by value then item separator [key ...]
	 * @param string $key_separator
	 * @param string $item_separator
	 * @param string $array (associative array expected)
	 * @return string
	 */
	public static function join_key_values_assoc($key_separator, $item_separator)
	{
		$args = func_get_args();

		if (!$args)
		{
			return $args;
		}

		$key_separator = ($first = array_shift($args)) ? $first : "";
		$item_separator = ($first = array_shift($args)) ? $first : "";

		if (!count($args))
		{
			return "";
		}

		$result = array();

		for ($i = 0; $i < count($args); $i++)
		{
			if (!$args[$i])
			{
				$result[] = "";
				continue;
			}

			$result[] = self::join_key_values($key_separator, $item_separator, array_keys($args[$i]), array_values($args[$i]));
		}

		return count($result) == 1 ? array_shift($result) : $result;
	}

	/**
	 * Join key/values as key followed by key separator followed by value then item separator [key ...]
	 * @param string $key_separator
	 * @param string $item_separator
	 * @param array $keys
	 * @param array $values
	 * @return string
	 */
	public static function join_key_values($key_separator, $item_separator, $keys, $values)
	{
		if (!$keys)
		{
			return "";
		}
		return implode($item_separator, array_map("implode", array_fill(0, count($keys), $key_separator), array_map(null, $keys, $values)));
	}

	/**
	 * Map a callback function to both keys and values returning associative array (or arrays if multiple arrays given)
	 * If callback produces same value for two different keys only the last one will be included in the resulting array
	 * @param callable $callback
	 * @param array $array1
	 * @param array $array2...
	 * @return array (or array of arrays)
	 */
	public static function array_map_assoc()
	{
		$args = func_get_args();

		if (!$args)
		{
			return $args;
		}

		$callback = null;
		if (is_callable(current($args)))
		{
			$callback = array_shift($args);
			if (!$args)
			{
				return $args;
			}
		}

		$result = array();

		for ($i = 0; $i < count($args); $i++)
		{
			if (!$args[$i])
			{
				$result[] = array();
				continue;
			}

			list($keys, $values) = self::array_multimap($callback, array_keys($args[$i]), array_values($args[$i]));
			$result[] = array_combine($keys, $values);
		}

		return count($result) == 1 ? array_shift($result) : $result;
	}

	/**
	 * Accept any number of arrays and returns array of the initial arrays after mapping $callback to each
	 * Expected either assoc or index arrays, only values will be changed by the callback call
	 * @param callable $callback
	 * @param array $array1
	 * @param array $array2...
	 * @return array (or array of arrays)
	 */
	public static function array_multimap()
	{
		$args = func_get_args();

		if (!$args)
		{
			return null;
		}

		$callback = null;
		if (is_callable(current($args)))
		{
			$callback = array_shift($args);
			if (!$args)
			{
				return $args;
			}
		}

		$result = array();

		for ($i = 0; $i < count($args); $i++)
		{
			$result[] = array_map($callback, $args[$i]);
		}

		return count($result) == 1 ? array_shift($result) : $result;
	}

	//! these will be (re)defined later
	private static $safe_chars3986 = "-._~";
	private static $safe_chars_encoded3986;
	private static $__encode_function3986;

	private static $__first_use = true;

	public static function encode_function3986()
	{
		self::init();
		return self::$__encode_function3986;
	}

	public static function join_encoded3896()
	{
		$args = func_get_args();
		return join("&", array_map(self::encode_function3986(), $args));
	}

	public static function rfc3986_encode($value)
	{
		return str_replace(self::$safe_chars_encoded3986, self::$safe_chars3986, rawurlencode($value));
	}

	public static function enforce_safe_char3986($value)
	{
		return $value != rawurlencode($value);
	}

	private static function init3986()
	{
		if (!self::$__first_use)
		{
			return;
		}

		self::$safe_chars3986 = array_values(array_filter(str_split(self::$safe_chars3986), array("SPUtils", "enforce_safe_char3986")));
		if (count(self::$safe_chars3986) != 0)
		{
			self::$safe_chars_encoded3986 = array_map("rawurlencode", self::$safe_chars3986);
			self::$__encode_function3986 = array("SPUtils", "rfc3986_encode");

			if (count(self::$safe_chars3986) == 1)
			{
				self::$safe_chars3986 = current(self::$safe_chars3986);
				self::$safe_chars_encoded3986 = current(self::$safe_chars_encoded3986);
			}
		}
		else
		{	//!< it would appear that rawurlencode in PHP 5.3 returns just what we need
			self::$__encode_function3986 = "rawurlencode";
		}

		self::$encode_callbacks["rfc3986"] = self::$__encode_function3986;
		self::$__first_use = false;
	}

	public static function init()
	{
		self::init3986();
	}
}

SPUtils::init();
?>