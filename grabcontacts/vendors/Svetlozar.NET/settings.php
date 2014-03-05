<?php

//! this is needed for OAuth revision 1.0a, when this file is included from external.php it will point to the correct location
$callback_url = SPUtils::current_request_url();

//! defaults to anonymous consumer key (if there is url to register for a key other than anonymous I am not aware of it)
$settings["plaxo/oauth"] 	= array(	"oauth_consumer_key" => "anonymous",
										"oauth_consumer_secret" => "",
										"oauth_callback" => $callback_url);

$settings["plaxo/urls"]		= array(	"request" => "http://www.plaxo.com/oauth/request",
										"access" => "http://www.plaxo.com/oauth/activate",
										"authorize" => "http://www.plaxo.com/oauth/authorize",
										"revoke" => "http://www.plaxo.com/oauth/revoke");

//! Default key/secret (register for private ones here: https://www.google.com/accounts/ManageDomains
//! note: you do not need to provide pem file however if you do (even though it won't be used) the security warning will not be displayed by goodgle
$settings["google/oauth"] 	= array(	"oauth_consumer_key" => "anonymous",
										"oauth_consumer_secret" => "anonymous",
										"oauth_callback" => $callback_url);

$settings["google/urls"]	= array(	"request" => "https://www.google.com/accounts/OAuthGetRequestToken",
										"access" => "https://www.google.com/accounts/OAuthGetAccessToken",
										"authorize" => "https://www.google.com/accounts/OAuthAuthorizeToken",
										"revoke" => "https://www.google.com/accounts/AuthSubRevokeToken");


//! Yahoo does not have anonymous access enabled, so you need to register your application to get a key/secret
//! http://developer.apps.yahoo.com/dashboard/

$settings["yahoo/oauth"] 	= array(	"oauth_consumer_key" => "dj0yJmk9ZXlmZnMxT1JYeU52JmQ9WVdrOVlVbEVPR1JYTkc4bWNHbzlNVFF5TXpjNE5UVTJNZy0tJnM9Y29uc3VtZXJzZWNyZXQmeD0yNQ--",
										"oauth_consumer_secret" => "86f93ea07f45765a9e1c3b274859dd24cf390018",
										"oauth_callback" => $callback_url);

$settings["yahoo/urls"]		= array(	"request" => "https://api.login.yahoo.com/oauth/v2/get_request_token",
										"access" => "https://api.login.yahoo.com/oauth/v2/get_token",
										"authorize" => "", 	//!< will be returned by yahoo with request token
										"revoke" => "");	//!< token expires in 1 hour (can be refreshed using oauth_session_handle, not done for importing contacts)


?>