<?php
include_once 'example/contacts.main.php';

class ExternalAuthHandler extends ContactsHandler
{
	public  $response_parm = "oauth_verifier";
	private $redirected = false; //!< is redirection needed
	private $response = false;	 //!< is response (need to handle response and close window)
	private $error = false;		 //!< error don't continue

	public function __construct($response_parm = null)
	{
		$this->redirected = false;
		$this->response = false;
		$this->error = false;
		$this->expect_redirect = true;

		if ($response_parm)
		{
			$this->response_parm = $response_parm;
		}

		parent::__construct(false, true);
	}

	public function handle_request($not_used = null, $not_used_ignore = null)
	{
		//! determine selected option for class
		$selected_option = "";
		if (isset($_GET[ContactsHandler::$contacts_option]))
		{
			$selected_option = $_GET[ContactsHandler::$contacts_option];
		}

		if (!$selected_option)
		{
			$this->error = true;
		}
		else
		{
			$this->session_start();

			$state = $this->get_from_session($selected_option);
			$oauth_parms = array();
			if ($state)
			{
				//! session data is stored as url encoded string, SPUtils::parse_query returns dictionary
				$oauth_parms = SPUtils::parse_query($state);
			}

			//! check if we have verifier, or if we already have access token in session
			//! either of these conditions means we already have user authorization (we get verifier back when the user is redirected back to external.php, and __oauth_access_token is set internally to indicate that the authorized request token has been already exchanged for a access token)
			if (isset($_GET[$this->response_parm]) || isset($oauth_parms["__oauth_access_token"]))
			{
				$this->response = true;
			}
			else
			{
				//! remove any previous request token (if any) from session and get another one
				//! assume previous token has been declined by the user or revoked already (by default the OAuth contacts importer classes, with the exception of Yahoo, make a call to revoke the access token as soon as the contacts data is retrieved, tokens may otherwise stay valid for long time)
				$this->remove_from_session($selected_option);

				//! see parent implementation - basically what happens there is instantiating the contacts importer class and attempting access to ->contacts, which would return null with error of "external authentication needed" + redirect url where we need to send the user to authorize our token
				//! another thing that happens behind the scenes when accessing ->contacts is getting a "request token" if we don't have a token already (and we wouldn't have a token on first request)
				//! External authentication/OAuth/API's usually are provided with limits, so request tokens in this examples are only acquired when the popup window is open (the other alternative is to get a request token on the main page and use the popup for redirect only)
				parent::handle_request(array("external" => "true"));
				if (!$this->redirect_url)
				{
					$this->error = true;
				}
			}
		}

		include_once("example/external.page.php");
	}
}

$handler = new ExternalAuthHandler();
$handler->handle_request();
?>
