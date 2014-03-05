<?php
include_once './Svetlozar.NET/init.php';

class ContactsHandler
{

	public static $default_class 	= "Yahoo"; 					//!< will be loaded by default if no specific contacts_option is provided with the query
	public static $contacts_option	= "contacts_option";		//!< name of the query parm that will contain the contacts_option
	public static $contacts_page 	= "contacts.page.php";		//!< the page that will be diplayed when handle_request is called
	public static $import_form 		= "contacts.import.php";	//!< the import form (username/pass or external authentication form)
	public static $invite_form 		= "contacts.invite.php";	//!< the form with contacts listing for the user to choose and submit for further processing
	public static $invite_done 		= "contacts.done.php";		//!< "form" to display after contacts have been selected and submitted
	public static $session_init 	= "contacts.session.php";	//!< file that will be included when initializing session (you may customize this file to your needs)

	private $contacts_classes;
	private $current_class;
	private $include_form;

	private $captcha_required = false;
	private $captcha_url = "";
	private $error_returned = false;
	private $error_message = "";
	private $contacts = null;
	private $output_page = true;
	private $display_menu = true;
	private $base_url = "";

	public $expect_redirect = false;
	public $redirect_url = "";

	/**
	 * If output page is set to true calling handle_request will output directly to the response, otherwise it will return only the form portion of the page as a string
	 * @param bool $output_page
	 * @param bool $redirect_expected (overrides output_page behavior completely, returns redirect url)
	 */
	function __construct($output_page = true, $redirect_expected = false)
	{
		//! General initializations
		$this->output_page = $output_page;
		$this->expect_redirect = $redirect_expected;

		//! ContactsHelper contains a list of all available classes (generated based on file names, we don't really want to include all the files at once since we really need only one to instantiate later)
		$this->contacts_classes = ContactsHelper::$ContactsClasses;

		//! remove contacts option from the query - not making any assumptions that the query will contain only this option
		$query_parms = array_diff_key($_GET, array(self::$contacts_option => ""));

		//! used for generating the menu links (update url with null url would give us the current url by default, so the result is current url + whatever other query parms except for contacts_option)
		$this->base_url = SPUtils::update_url(null, $query_parms, true);

		//! for menu links the url needs to be ready to append the contacts_option=ZZZ
		if($query_parms)
		{
			$this->base_url .= "&";
		}
		else
		{
			$this->base_url .= "?";
		}
		if (!$this->contacts_classes)
		{
			return;
		}

		//! note we are starting a session here, we'll be storing state information that is needed for the contacts importer classes
		$this->session_start();
	}

	/**
	 * Start session, this implementation assumes built-in PHP session handling
	 * You may subclass and override this function if you need to handle session differently
	 */
	protected function session_start()
	{
		@session_start();
	}

	/**
	 * Commit session (no more writes to session will be done after this point), this implementation assumes built-in PHP session handling
	 * Also assumption is made that the application will not need to write to session any more so if you have more to write to session from a calling code subclass and override with empty function body
	 * You may subclass and override this function if you need to handle session differently
	 */
	protected function session_commit()
	{
		@session_commit();
	}

	/**
	 * Add object/value to session store, this implementation assumes built-in PHP session handling
	 * You may subclass and override this function if you need to handle session differently
	 * @param string $key
	 * @param mixed $value
	 */
	protected function add_to_session($key, $value)
	{
		$_SESSION[$key] = $value;
	}

	/**
	 * Remove key from the session store, this implementation assumes built-in PHP session handling
	 * You may subclass and override this function if you need to handle session differently
	 * @param string $key
	 */
	protected function remove_from_session($key)
	{
		unset($_SESSION[$key]);
	}

	/**
	 * Get object/value from session store, this implementation assumes built-in PHP session handling
	 * You may subclass and override this function if you need to handle session differently
	 * @param string $key
	 * @param mixed $value
	 */
	protected function get_from_session($key)
	{
		if (isset($_SESSION[$key]))
		{
			return $_SESSION[$key];
		}

		return null;
	}

	/**
	 * Either output the contacts page (and return true/false) or return the form (import/invite) as a string (without writing it to the output stream)
	 * @param array $post assoc array (usually $_POST) with form parameters entered by user
	 * @return mixed bool|string bool if output_page is set to true and page has been included successfully, string with the form contents only if output_page is false
	 */
	function handle_request($post = null)
	{
		//! reset return values
		$this->error_message = "";
		$this->error_returned = false;
		$this->captcha_required = false;
		$this->captcha_url = "";
		$this->contacts = null;
		$this->display_menu = true;

		$contacts_importer = null;

		//! determine where to import contacts from based on contacts option value (Yahoo, Gmail etc)
		$selected_option = isset($_GET[self::$contacts_option]) ? $_GET[self::$contacts_option] : self::$default_class;

		//! we may have a different contacts_option in the query than the one that we really need which would be in the submitted form
		//! this will happen when a user opens a popup window for external authentication, comes back to the form and switches to another form, then back to the external window which would submit the form on success
		if (isset($post["contacts_option"]) && $post["contacts_option"])
		{ //!< post overrides get
			$selected_option = $post["contacts_option"];
		}

		//! when invite form has been submitted $post["contacts"] will contain the selected contacts with key = email and value = name
		//! this is the very last step (after importing contacts, displaying them to the user and user submitting back the selection)
		if (isset($post["contacts"]))
		{
			//! your implementation to handle selected contacts comes here handle selected contacts here then exit early no need to instantiate importer class
			foreach ($post["contacts"] as $contact_email => $contact_name)
			{
				//! do something here: store or send out emails (use PHPMailer or other mailing library)
			}

			$this->include_form = self::$invite_done;
			if ($this->output_page)
			{
				require_once self::$contacts_page;
				return;
			}
			else
			{
				return $this->get_form();
			}
		}

		//! back to the beginning, importing contacts: check if selected option maps to an actual class (we don't trust our users)
		$this->current_class = isset($this->contacts_classes[$selected_option]) ? $this->contacts_classes[$selected_option] : null;

		//! if we couldn't map the option to a class, we don't have much else to do, return an error here
		if (!$this->current_class)
		{
			$this->error_returned = true;
			$this->error_message = "Invalid option {$selected_option}";
		}
		else if (!$post) //! otherwise if this is not a post back we just display the import form (usename/password or button to open the popup for external authentication)
		{
			$this->include_form = self::$import_form;
		}
		else
		{
			//! get any state information pertinent to the request and remove it from session immediately
			//! (it will not be valid on next request, and may or may not be updated after attempt to import contacts)
			$state = $this->get_from_session($selected_option);
			$this->remove_from_session($selected_option);

			//! session is not the only place where we can store state information, add any more state info from the form if available
			if (isset($post["state"]) && $post["state"])
			{
				$state = $state ? "{$state}&{$post["state"]}" : $post["state"];
			}

			//! include only the file of the specific contacts importer class
			ContactsHelper::IncludeClassFile($this->current_class->FileName); //!< This actually checks if the file is valid contacts importer class before loading the file

			//! determine if ExtAuth needs to be added to the name of the class for external authentication, and then create a new instance of contacts importer class that corresponds to the selected option
			if ($this->current_class->ExternalAuth)
			{
				$class_name = $this->current_class->ClassName . "ExtAuth";
				$contacts_importer = new $class_name;
			}
			else if (isset($post['email'])) //!< if this is not external authentication form, extract any fields from the form that we need for creating a class instance
			{
				list($email, $password, $captcha) = SPUtils::get_values($post, "email", "pswd", "captcha");
				$contacts_importer = new $this->current_class->ClassName($email, $password, $captcha);
			}


			//! make sure instance of the contacts importer class has been created
			if (!$contacts_importer)
			{
				$this->error_returned = true;
				$this->error_message = "Could not initialize contacts importer.";
			}
			else
			{
				//! restore previously stored state (see above - this was combination of state stored in session + state submitted with the form)
				if ($state)
				{
					$contacts_importer->RestoreState($state);
				}

				//! $contacts_importer->contacts automagically loads the contacts data on success, returns null otherwise
				//! it will also accept Contacts (it's a getter property)
				if ($this->contacts = $contacts_importer->contacts)
				{
					//! you could store the list of contacts at this point, the example code displays it for selection
					$this->include_form = self::$invite_form;
					$this->display_menu = false;
				}
				else //!< if null or no contacts returned examine any error conditions
				{
					$this->error_returned = true;
					switch ($contacts_importer->Error)
					{
						case ContactsResponses::ERROR_INVALID_LOGIN:
							$this->error_message = "Invalid Login";
							break;
						case ContactsResponses::ERROR_EXTERNAL_AUTH:
							$this->error_message = "External Authentication Required";
							break;
						case ContactsResponses::ERROR_NO_CONTACTS:
							$this->error_message = "No contacts were found";
							break;
						case ContactsResponses::ERROR_NO_USERPASSWORD:
							$this->error_message = "Provide Email and Password";
							break;
						case ContactsResponses::ERROR_CAPTCHA_REQUIRED:
							//! a note on captcha, if we get it as a response we need to display it ($this is available in the form when it is included)
							$this->error_message = "Enter captcha to continue";
							$this->captcha_url = $contacts_importer->CaptchaUrl;
							$this->captcha_required = true;
							break;
						default:
							//! generic error
							$this->error_message = "Request could not be handled. Try again Later.";
							break;
					}

					//! usually redisplay the import form with errors returned
					$this->include_form = self::$import_form;
				}

				//! get any state information from the contacts importer class
				$state = $contacts_importer->GetState();

				//! if state is not empty and there has not been an error or the error requires state (captcha and external authentication both need to maintain state between requests)
				if ($state &&
					(!$contacts_importer->Error ||
						$contacts_importer->Error == ContactsResponses::ERROR_CAPTCHA_REQUIRED ||
				 		$contacts_importer->Error == ContactsResponses::ERROR_EXTERNAL_AUTH))
				{
					// store the state info to session
					$this->add_to_session($selected_option, $state);
				}
			}
		}

		$this->session_commit();

		//! get redirect(popup) url if external user authorization is needed
		//! this happens when contacts.main is included from external.php (thus, an early return here)
		if ($this->expect_redirect && is_subclass_of($contacts_importer, "SPContactsExtAuth")
				&& $contacts_importer->UserAuthorizationNeeded)
		{
			$this->redirect_url = $contacts_importer->UserAuthorizationUrl;
			return; //!< ok a third type of return if user needs to be redirected
		}

		if ($this->output_page)
		{
			//! render the page
			require_once self::$contacts_page;
		}
		else
		{
			//! or return the form as a string (an application/page that wants to include the form, would just echo it where necessary)
			return $this->get_form();
		}
	}

	/**
	 * Get the form contents as string
	 *
	 * @return string the form contents
	 */
	protected function get_form()
	{
		if (!$this->include_form)
			return "";

		ob_start();
		include $this->include_form;
		$contents = ob_get_contents();
		ob_end_clean();
		return $contents;
	}
}
?>