Very Simple PayPal Bridge
=========================

From Van Patten Media

Copyright (C) 2011-2012 Peter Upfold. All rights reserved. See LICENSE.md for your rights.

Overview
--------

This is a PHP class that provides a very simple interface for interacting with the PayPal NVP API.
You use the Bridge to issue API calls to the PayPal NVP API, and to get the response arguments back.
You'll probably most commonly use the NVP API for performing Express Checkout payments.

It handles the encoding and decoding of arguments and responses, as well as using cURL internally to issue the request to PayPal. It has full support for PHP exceptions, so your application can, if desired, respond gracefully to PayPal failures to the user, but log detailed error information for the site administrator.

Note that the Bridge is only an abstraction layer that means your code does not have to care about encoding the arguments, invoking requests with cURL, and so on.

Your code still needs to collect together the arguments for the PayPal API request in an associative array, call one function in VSPB, then it will receive the responses as an associative array(or an exception will be thrown, which your code must handle).

Contents
--------

In this repository, you will find:

* the class itself
* a configuration file example
* a demonstration file

The demo file doesn't go through an entire payment workflow, but does show you how to perform the first step in an Express Checkout payment, `SetExpressCheckout`, and displays the results. See the comments in the `demo.php` file for more information.

Disclaimer
----------

**This shouldn't really be necessary, but here goes:**

Building e-commerce code has to be done right. Throwing VSPB at your project and writing shoddy code around it isn't going to make a good e-commerce solution. You need to be rigorous about validating and checking user data, handling every possible error condition and integrating secure design decisions into every part of your application.

VSPB works great as a low-level layer for a PayPal Express Checkout NVP-based e-commerce site (_why do you think we wrote it?_), but it is just one piece of the solution. There are plenty of other responsibilities that your code has to discharge. **If you write an e-commerce solution, badly, around VSPB, don't blame
us when something bad happens!**

By the way, the LICENSE.md file details this disclaimer in its full legal force, but we thought it would be worth reiterating it here, so you actually read it.