<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

class ContactsClass
{
	public $ClassName = "";
	public $ExternalAuth = false;
	public $FileName	= "";
	function __construct ($name, $external, $file)
	{
		//! same as key (includes .ext)
		$this->FileName = $file;

		//! printable name usually equivallent to webmail service name (example Plaxo, no ExtAuth is given)
		$this->ClassName = $name;

		//! indicates whether this is external version of a class
		$this->ExternalAuth = $external;
	}

	function __toString()
	{
		return $this->ClassName . ($this->ExternalAuth ? "(External Authentication)" : "");
	}
}

abstract class ContactsHelper
{
	public static $ContactsClasses = null;

	public static function init()
	{
		self::$ContactsClasses = self::GetClasses();
	}

	protected static function GetClasses()
	{
		$classes = array();
		foreach (array_filter(array_map(array("ContactsHelper", "StripExt"), scandir(SVETLOZARNET_CONTACTS))) as $v)
		{
			if (strtolower($v) == "index" || strpos($v, ".") === 0)
			{
				continue;
			}

			$name_parts = explode('.', $v);
			$classes[$v] = new ContactsClass(current($name_parts), count($name_parts) > 1 ? strtolower($name_parts[1]) == "ext" : false, $v);
		}

		return $classes;
	}

	public static function StripExt($file_name)
	{
		if (strlen($file_name) <= 4 || (strlen($file_name) >= 8 && !substr_compare($file_name, "base.php", -8, 8, false))
				|| substr_compare($file_name, ".php", -4, 4, false) !== 0)
		{
			return false;
		}

		return substr($file_name, 0, strlen($file_name) - 4);
	}

	/**
	 * If the class name is found
	 * @param string $class_name (must be the key portion of ContactsClasses, including the .ext part if given)
	 * @param mixed $options (any number of options needed to instantiate the class)
	 * @return SPContactsBase (subclass of)
	 */
	public static function GetInstance($file_name)
	{
		$options = func_get_args();
		array_shift($options); //!< throw the first element out, it's already in class name
		if(!self::IncludeClassFile($file_name))
		{
			return null;
		}

		$name_parts = explode('.', $file_name);
		$class_name = $name_parts[0];
		if (count($name_parts) > 1 && strtolower($name_parts[1]) == "ext")
		{
			$class_name .= "ExtAuth";
		}

		return new $class_name($options);
	}

	/**
	 * Include a contacts class file so that the class definition is available
	 * @param $class_name
	 * @return bool true if class name exists and file has been included
	 */
	public static function IncludeClassFile($file_name)
	{
		if(!self::IsContactsFile($file_name))
		{
			return false;
		}

		require_once SVETLOZARNET_CONTACTS . "$file_name.php";
		return true;
	}

	public static function IsContactsFile($file_name)
	{
		return isset(self::$ContactsClasses[$file_name]);
	}
}

ContactsHelper::init();

?>