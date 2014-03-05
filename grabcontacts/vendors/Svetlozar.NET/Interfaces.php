<?php
/*

Copyright (c) 2006-2011 Svetlozar Petrov, Svetlozar.NET

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

*/

interface IObjectState
{
	/**
	 * Return a string that can be later used to restore state
	 * @return string
	 */
	public function GetState();


	/**
	 * Handle state restoring
	 * @param $state_str
	 */
	public function RestoreState($state_str);
}

interface IAuthState extends IObjectState
{

	/**
	 * Do not 100% rely on the response of this function, make a web request to a protected resource and only when successful consider authenticated to be true
	 * @return bool true if initialized parameters indicate authorization has been given
	 */
	public function Authenticated();


	/**
	 * Accept number of (login) parameters and attempt authentication
	 * @return bool true if authentication has succeeded
	 */
	public function Authenticate();
}
?>