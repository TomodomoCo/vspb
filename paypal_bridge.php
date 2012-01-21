<?php
/* 

Very Simple PayPal Bridge
Developed by Peter Upfold for Van Patten Media <http://www.vanpattenmedia.com>

========================================================================================================================

Copyright (C) 2011-2012 Peter Upfold. All rights reserved.

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy,
modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE
LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR
IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.

Except as contained in this notice, the names of the authors or copyright holders shall not be used in commercial advertising
or to otherwise promote the sale, commercial use or other commercial dealings regarding this Software without prior written
authorization from the the authors or copyright holders. Non-commercial use of the authors and copyright holders' names is
permitted, but it may be revoked on a case-by-case basis if the authors wish to disconnect themselves from a particular use.

========================================================================================================================

PayPal Bridge class:

This class is a very basic class for interacting with the NVP PayPal API.
It holds the PayPal credentials and will issue raw requests to the NVP
API. It will then return the results of those requests back to the caller.

Higher-level PayPal tasks, such as the whole of a payment workflow, should
be written, targeting this bridge class. Those higher-level classes would
then be responsible for issuing the correct API calls to this class for
processing (and dealing with parameters), as well as interpreting the results
which this class will fetch and send back to the caller.

*/

/* API Endpoint URIs */
define('VPM_PAYPAL_BRIDGE_SANDBOX_ENDPOINT', 'https://api-3t.sandbox.paypal.com/nvp');
define('VPM_PAYPAL_BRIDGE_LIVE_ENDPOINT', 'https://api-3t.paypal.com/nvp');

/* Where is the config file with credentials? */
define('VPM_PAYPAL_BRIDGE_REL_CONFIG_FILE', 'config.php');

/* Exceptions thrown by this class:

	Keep the error codes to 599 and below to avoid clashes with higher-level classes.

	0xx -- demonstration error, for testing the exception framework
	
		001 -- demonstration error code 01

	1xx -- configuration error
	
		101 -- PayPal credentials are not set properly in config.php.
		102 -- PayPal endpoint and/or API version are not set properly in config.php.
		103 -- PayPal endpoint specified incorrectly in config.php. Only 'sandbox' and 'live' are allowed.
		104 -- Link to CA Certificates directory is not set in config.php.
		105 -- The PayPal timeout is not set in config.php, or is not an integer.
		106 -- The debug and/or enabled values are not set in config.php.
		107 -- cURL is not available.
		108 -- The whole class is set to not enabled.
		
	
	2xx -- security error
		201 -- not authorised by whitelist to call that method
	
	3xx -- cURL error
		the final two digits will be the native cURL error code
		<http://curl.haxx.se/libcurl/c/libcurl-errors.html>
	

*/

class VerySimplePayPalBridge {

	private $version = '1.0'; // version of this class
	private $credentials;
	private $endpoint;
	private $apiVersion;
	private $certFile;
	private $timeout;
	private $enforceApiMethodWhiteList = true;
	private $apiMethodWhitelist;
	private $debug;
	private $enabled;
	
	public function __construct() {
	/*
		Construct the PayPalBridge object, loading in credentials
		and other details from config and checking execution
		requirements.		
	*/	
	
		// bring in the config file
		require(dirname(__FILE__).'/' . VPM_PAYPAL_BRIDGE_REL_CONFIG_FILE);
		
		// check and set our various private instance vars from config
		
		if (empty($VSPB_PP_CREDENTIALS_USER) || empty($VSPB_PP_CREDENTIALS_PASS)
		|| empty($VSPB_PP_CREDENTIALS_SIGNATURE))
		{
			throw new Exception('PayPal credentials are not set properly in config.php.', 101);
			return false;
		}
		
		$this->credentials = array(
			'USER' 			=> $VSPB_PP_CREDENTIALS_USER,
			'PWD' 			=> $VSPB_PP_CREDENTIALS_PASS,
			'SIGNATURE' 	=> $VSPB_PP_CREDENTIALS_SIGNATURE
		);
		
		if (empty($VSPB_PP_API_ENDPOINT) || empty($VSPB_PP_API_VERSION))
		{
			throw new Exception('PayPal endpoint and/or API version are not set properly in config.php.', 102);
			return false;
		}
		
		switch ($VSPB_PP_API_ENDPOINT)
		{
			case 'sandbox':
				$this->endpoint = VPM_PAYPAL_BRIDGE_SANDBOX_ENDPOINT;
			break;
			case 'live':
				$this->endpoint = VPM_PAYPAL_BRIDGE_LIVE_ENDPOINT;
			break;
			default:
				throw new Exception('PayPal endpoint specified incorrectly in config.php. Only \'sandbox\' and \'live\' are allowed.', 103);
				return false;
			break;
		
		}
		
		$this->apiVersion = $VSPB_PP_API_VERSION;
		
		if (empty($VSPB_PP_CA_CERT_FILE))
		{
			throw new Exception('Link to CA Certificates is not set in config.php.', 104);
			return false;
		}
		
		$this->certFile = $VSPB_PP_CA_CERT_FILE;
		
		if (empty($VSPB_PP_TIMEOUT) || !is_int($VSPB_PP_TIMEOUT))
		{
			throw new Exception('The PayPal timeout is not set in config.php, or is not an integer.', 105);
			return false;
		}
		
		$this->timeout = $VSPB_PP_TIMEOUT;
		
		/* initialise the security whitelist for api methods */
		
		if (!isset($VSPB_PP_SEC_SHOULD_ENFORCE_API_METHOD_WHITELIST)) {
			// fail safe if it's not set -- don't allow anything!
			$this->enforceApiMethodWhiteList = true;
			$this->apiMethodWhiteList = array();
		}
		else if ($VSPB_PP_SEC_SHOULD_ENFORCE_API_METHOD_WHITELIST !== false)
		{
			// enable the white list		
			if (!is_array($VSPB_PP_SEC_API_METHOD_WHITELIST) || count($VSPB_PP_SEC_API_METHOD_WHITELIST) < 1)
			{
				// invalid white list, so default fail safe and don't allow anything
				$this->enforceApiMethodWhiteList = true;
				$this->apiMethodWhiteList = array();
			}
			else {
				// valid white list, set it
				$this->enforceApiMethodWhiteList = true;
				$this->apiMethodWhiteList = $VSPB_PP_SEC_API_METHOD_WHITELIST;
			}
		}
		else {
			// the 'should enforce' variable must be boolean false so disable the whitelist
			$this->enforceApiMethodWhiteList = false;
			$this->apiMethodWhiteList = array();
		}
		
		if (empty($VSPB_PP_IS_DEBUG) || empty($VSPB_PP_IS_ENABLED))
		{
			throw new Exception('The debug and/or enabled values are not set in config.php.', 106);
			return false;			
		}
		
		$this->debug = ($VSPB_PP_IS_DEBUG === true) ? true : false;
		$this->enabled = ($VSPB_PP_IS_ENABLED === true) ? true : false;

		// check that we have a cURL
		if (!function_exists('curl_init') || !is_callable('curl_init'))
		{
			throw new Exception('cURL is not available.', 107);
			return false;
		}
		
		// unset all config variables so no-one else can see them anymore
		unset($GLOBALS['VSPB_PP_CREDENTIALS_USER'], $GLOBALS['VSPB_PP_CREDENTIALS_PASS'], $GLOBALS['VSPB_PP_CREDENTIALS_SIGNATURE'],
		$GLOBALS['VSPB_PP_API_ENDPOINT'],$GLOBALS['VSPB_PP_API_VERSION'], $GLOBALS['VSPB_PP_TIMEOUT'], $GLOBALS['VSPB_PP_CA_CERT_DIR'],
		$GLOBALS['VSPB_PP_SEC_SHOULD_ENFORCE_API_METHOD_WHITELIST'], $GLOBALS['VSPB_PP_SEC_API_METHOD_WHITELIST'], 
		$GLOBALS['VSPB_PP_IS_DEBUG'], $GLOBALS['VSPB_PP_IS_ENABLED'], $GLOBALS['VSPB_PP_DEBUG_FILE']);
		
		// everything is set up and ready
		return true;		
	
	}
	
	public function callAPI($apiMethod, $requestParameters)
	{
	/*
		Issue a request to the PayPal API with our configured credential.
		We will call $apiMethod, passing in $requestParameters to PayPal,
		and await a response before responding.
		
		This is **synchronous**, so may incur waiting times up to timeout.
		Calling functions should be aware of that.
		
		We expect $requestParameters to be an associative array of key-value
		pairs. This function will perform URL encoding.
		
		We will return an associative array of key-value pairs. We will decode
		it from its format, but the calling function is responsible for display
		safety (specialchars, HTML tags, etc.)		
		
	*/
	
		// have we been disabled?
		if (!$this->enabled)
		{
			throw new Exception('PayPal is disabled.', 108);
			return false;
		}
	
		// are we allowed to call the method requested?
		if ($this->enforceApiMethodWhiteList)
		{
			if (!in_array($apiMethod, $this->apiMethodWhiteList, true))
			{
				throw new Exception('Not authorised to call API method \''.strip_tags(htmlentities($apiMethod)).'\'.', 201);
				return false;
			}
		}
		
		// build the POST parameters from API method, version, credentials and $requestParameters
		$fullParameters = array(
		
			'METHOD'			=> $apiMethod,
			'VERSION'			=> $this->apiVersion,
		
		)
		+ $this->credentials
		+ $requestParameters;
		
		$httpParameters = http_build_query($fullParameters);
		
		$cURLDebugFile = ($this->debug) ? VSPB_PP_DEBUG_FILE : null;
		if ($cURLDebugFile) {
			$cURLDebugFileHandler = fopen($cURLDebugFile, 'a');
			fwrite($cURLDebugFileHandler, "Starting cURL debugging…\n\n".gmdate('r')."\n\nRequest from: ".$_SERVER['REMOTE_ADDR']."\n\n####################\nAbout to start request at ".gmdate('Y-m-d H:i:s \u\s u')." with microtime ".microtime()."\n\n");
			$beginReqTime = microtime();
			$beginReqTime = explode(' ', $beginReqTime);
			$beginReqTime = $beginReqTime[1] + $beginReqTime[0];
		}
		
		// prepare cURL for liftoff
		$cURLOptions = array (
		
			CURLOPT_URL				=> $this->endpoint,
			CURLOPT_SSL_VERIFYPEER	=> true,
			CURLOPT_SSL_VERIFYHOST	=> 2, /* do not change this or the above line */
			CURLOPT_CAINFO			=> $this->certFile,
			CURLOPT_RETURNTRANSFER	=> true,
			CURLOPT_POST			=> true,
			CURLOPT_POSTFIELDS		=> $httpParameters,
			CURLOPT_USERAGENT		=> 'VSPB/'.$this->version.' (using v'.$this->apiVersion.' API)', /* VSPB_VERSION */
			CURLOPT_VERBOSE			=> $this->debug,
			CURLOPT_STDERR			=> ($this->debug) ? $cURLDebugFileHandler : null,
			CURLOPT_HTTPHEADER		=> array ('Accept: text/plain', 'Expect: ')
		
		);	
		
		$ch = curl_init();
		curl_setopt_array($ch, $cURLOptions);
		$ppResponse = curl_exec($ch);
		
		if (curl_errno($ch)) {
			$cErrorNo = curl_errno($ch);
			$cErrorString = curl_error($ch);
			curl_close($ch);
			
			if ($cURLDebugFileHandler)
			{
				$endReqTime = microtime();
				$endReqTime = explode(' ', $endReqTime);
				$endReqTime = $endReqTime[1] + $endReqTime[0];
				fwrite($cURLDebugFileHandler, "\n################################\nFinished at ".gmdate('Y-m-d H:i:s \u\s u')." with microtime ".microtime()."\nRequest took: ".number_format(($endReqTime-$beginReqTime), 4)." seconds\n\n");
				fclose($cURLDebugFileHandler);
			}
			
			throw new Exception('cURL failed: \''.$cErrorString.'\'', (300 + $cErrorNo));
			return false;
		} else {
		
			curl_close($ch);
			
			if ($cURLDebugFileHandler)
			{
				$endReqTime = microtime();
				$endReqTime = explode(' ', $endReqTime);
				$endReqTime = $endReqTime[1] + $endReqTime[0];
				fwrite($cURLDebugFileHandler, "\n################################\nFinished at ".gmdate('Y-m-d H:i:s \u\s u')." with microtime ".microtime()."\nRequest took: ".number_format(($endReqTime-$beginReqTime), 4)." seconds\n\n");
				fclose($cURLDebugFileHandler);
			}
			
			$response = array('VPM_PAYPAL_BRIDGE_ENDPOINT_REF', ($this->endpoint == VPM_PAYPAL_BRIDGE_SANDBOX_ENDPOINT) ? 'sandbox' : 'live');
			parse_str($ppResponse, $response);
			
			return $response;		
		}
	
	}

}

?>