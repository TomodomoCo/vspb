<?php
/* 

Very Simple PayPal Bridge
Developed by Peter Upfold for Van Patten Media <http://www.vanpattenmedia.com>

Configuration file. This should be properly permissions-protected and set with your API credentials
and other settings.

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

*/

// live credentials
$VSPB_PP_CREDENTIALS_USER = '';
$VSPB_PP_CREDENTIALS_PASS = '';
$VSPB_PP_CREDENTIALS_SIGNATURE = '';

// sandbox credentials
/*
$VSPB_PP_CREDENTIALS_USER = '';
$VSPB_PP_CREDENTIALS_PASS = '';
$VSPB_PP_CREDENTIALS_SIGNATURE = '';
*/

$VSPB_PP_API_ENDPOINT = 'live'; // set to 'sandbox' or 'live'
//$VSPB_PP_API_ENDPOINT = 'sandbox';
$VSPB_PP_API_VERSION = '74.0';
$VSPB_PP_TIMEOUT = 20; // how many seconds to wait for PayPal before timing out

$VSPB_PP_CA_CERT_FILE = '/etc/ssl/certs/ca-certificates.crt';
/* This is for Ubuntu Server. Change this to the location of a CA certificate bundle,
so that the class can verify the secure connection with PayPal. */

//CentOS/RedHat
//$VSPB_PP_CA_CERT_FILE = '/etc/ssl/certs/ca-bundle.crt';

// some protection from abuse of the PayPal bridge class -- only allow specified API calls
$VSPB_PP_SEC_SHOULD_ENFORCE_API_METHOD_WHITELIST = true;
$VSPB_PP_SEC_API_METHOD_WHITELIST = array(

	'SetExpressCheckout',
	'GetExpressCheckoutDetails',
	'DoExpressCheckoutPayment'

); 

$VSPB_PP_IS_DEBUG = true; // ALWAYS set to false once running live, or logfiles will grow significantly
$VSPB_PP_IS_ENABLED = true; // disable the whole payment system

define('VSPB_PP_DEBUG_FILE', '/your/debug/file/location'); // should be a constant

?>