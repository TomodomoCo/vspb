<?php
/*

Demo File -- Very Simple PayPal Bridge

Just to show you how to perform an action and parse results with the VSPB.

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

	In this demonstration, all I'm going to show you is how to start an order
	with the PayPal NVP API. To do this, we send PayPal the items the user wants
	to order, along with a few other parameters, in a 'SetExpressCheckout' API request.
	
	We use the Very Simple PayPal bridge to make this API request, and this demonstrates the usage
	of the class.
	
	VSPB makes it easy to issue an API request and get the results from PayPal without worrying yourself
	with the encoding of the data into the NVP format, using cURL, and decoding the results.
	
	HOWEVER-- you still need to write code that takes the results of these actions and makes sensible decisions
	(like, only to issue the payment if PayPal returns you ACK=Success, for example).
	
	In the real world, to do the whole transaction, you need to implement code for this step,
	SetExpressCheckout, but also for GetExpressCheckoutDetails to validate the payment as 'ready'
	at PayPal, then finally issue the payment with DoExpressCheckoutPayment.
	
	It's important that you write all that code properly, since you're dealing with people's real transactions!
	
	For more information on writing your code to perform each stage of the PayPal Express Checkout dance,
	you should read the documentation at:
	<https://cms.paypal.com/us/cgi-bin/?cmd=_render-content&content_ID=developer/e_howto_api_nvp_NVPAPIOverview>
	
*/
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
		<title>VSPB Demo Page</title>
	</head>
	<body>
<?php
/* 
	In real life, you'd get $items from a shopping cart or something.
	You'd need to be very careful about validating the items your user
	has given you against your product database to make sure they are buying
	products that exist, at the desired prices!
	
	For now, we'll hard-code them in.
*/

$items[0]['name'] = 'Test item';
$items[0]['desc'] = 'This is a demonstration item.';
$items[0]['qty'] = 1;
$items[0]['amt'] = '299.00';
$successURL = 'http://www.example.com/payment_continue';
$cancelURL = 'http://www.example.com/payment_cancel';


// now, format the items for the API
$formattedItems = array();
$totalAmt = 0.0;

if (is_array($items) && count($items) > 0)
{
	foreach($items as $i => $item) {
	
		$formattedItems['L_PAYMENTREQUEST_0_NAME'.$i] = $item['name']; 	// name
		$formattedItems['L_PAYMENTREQUEST_0_DESC'.$i] = $item['desc']; 	// description
		$formattedItems['L_PAYMENTREQUEST_0_AMT'.$i] = $item['amt']; 	// individual item price
		$totalAmt += $item['amt'] * $item['qty'];						// calculate a running total of all items
		$formattedItems['L_PAYMENTREQUEST_0_QTY'.$i] = $item['qty'];	// quantity of this product to buy
	
	}
}

// PayPal needs these arguments for the SetExpressCheckout operation
$ppArgs = array(
	
	'RETURNURL'					=> $successURL,	// where to go if the checkout token was created successfully
	'CANCELURL'					=> $cancelURL, 	// where to go if cancel is clicked at PayPal
	'PAYMENTREQUEST_0_PAYMENTACTION' => 'Sale',	// this is a 'Sale' action
	'PAYMENTREQUEST_0_AMT'		=> $totalAmt,	// we calculated the total amount as we went along
	'PAYMENTREQUEST_0_CURRENCYCODE' => 'USD'	// currency code for the transaction

);

$ppArgs += $formattedItems; // add in our formatted items to purchase to the arguments

		
/*
	now, use the VSPB to call PayPal's SetExpressCheckout API method. The results will
	be stored, as an associative array, in $tokResponse.
*/

// set up the class first
require(dirname(__FILE__).'/paypal_bridge.php');
try {
	$pp = new VerySimplePayPalBridge();
}
catch (Exception $e) // the class constructor will throw errors if configuration isn't right
{
	// your application needs to handle errors gracefully. See paypal_bridge.php for error codes and messages.
	?><h1>There was an error!</h1>
	<p>The VSPB class had the following exception when trying to construct the class:</p>
	
	<p><strong>Error Code:</strong> <?php echo $e->getCode();?></p>
	<p><strong>Error Message:</strong> <?php echo $e->getMessage();?></p>
	
	<p>In the real world, your code could handle these exceptions any way you'd like. We're just displaying them here,
	in this demonstration, so you know what's going on!</p>
	</body></html>
	<?php
	die();
}


// now that the class is setup, issue the API call to PayPal
try {
	$tokResponse = $pp->callAPI(
		'SetExpressCheckout',
		$ppArgs			
	);				
}
catch (Exception $e)
{	
	// your application needs to handle errors gracefully. See paypal_bridge.php for error codes and messages.
	?><h1>There was an error!</h1>
	<p>The VSPB instance had the following exception:</p>
	
	<p><strong>Error Code:</strong> <?php echo $e->getCode();?></p>
	<p><strong>Error Message:</strong> <?php echo $e->getMessage();?></p>
	
	<p>In the real world, your code could handle these exceptions any way you'd like. We're just displaying them here,
	in this demonstration, so you know what's going on!</p>
	</body></html>
	<?php
	
	die();
}

/*
	now, the request has completed and we have a result from PayPal. Let's see what it looks like.
*/

	?><h1>Result from PayPal</h1>
	
	<p>The request to <code>SetExpressCheckout</code> was made, and here is the response we got from PayPal:</p>
	
	<table>
	<tr><th>Key</th><th>Value</th></tr>
	<?php
	
	foreach($tokResponse as $key => $val)
	{
	
		?><tr><td><code><?php echo strip_tags(htmlentities($key, ENT_QUOTES));?></code></td>
		<td><code><?php echo strip_tags(htmlentities($val, ENT_QUOTES)); ?></code></td></tr><?php
	
	}	
	
	?></table>
	
	<p>In the real world, your code would probably check for <code>ACK=Success</code> in <code>$tokResponse</code>, then take
	the returned <code>TOKEN</code>
	and redirect the user to <strong>https://www.[sandbox].paypal.com/webscr?cmd=_express-checkout&amp;token=<code>TOKEN</code></strong>.
	</p>
	</body>
</html>