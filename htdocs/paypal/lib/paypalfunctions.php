<?php
/* Copyright (C) 2010 Laurent Destailleur  <eldy@users.sourceforge.org>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**	    \file       htdocs/paypal/lib/paypalfunctions.php
 *		\ingroup    paypal
 *		\brief      Page with Paypal functions. Downloaded from Paypal.
 *		\version    $Id$
 */

/********************************************
 PayPal API Module

 Defines all the global variables and the wrapper functions
 ********************************************/
$PROXY_HOST = '127.0.0.1';
$PROXY_PORT = '808';

$SandboxFlag = ($PAYPAL_API_SANDBOX?true:false);

//'------------------------------------
//' PayPal API Credentials
//' Replace <API_USERNAME> with your API Username
//' Replace <API_PASSWORD> with your API Password
//' Replace <API_SIGNATURE> with your Signature
//'------------------------------------
$API_UserName=$PAYPAL_API_USER;
$API_Password=$PAYPAL_API_PASSWORD;
$API_Signature=$PAYPAL_API_SIGNATURE;

// BN Code 	is only applicable for partners
$sBNCode = "PP-ECWizard";


/*
 ' Define the PayPal Redirect URLs.
 ' 	This is the URL that the buyer is first sent to do authorize payment with their paypal account
 ' 	change the URL depending if you are testing on the sandbox or the live PayPal site
 '
 ' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
 ' For the live site, the URL is        https://www.paypal.com/webscr&cmd=_express-checkout&token=
 */

if ($SandboxFlag == true)
{
    $API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
    $PAYPAL_URL = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
}
else
{
    $API_Endpoint = "https://api-3t.paypal.com/nvp";
    $PAYPAL_URL = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
}

$USE_PROXY = false;
$version="2.3";

if (session_id() == "")
session_start();

/* An express checkout transaction starts with a token, that
 identifies to PayPal your transaction
 In this example, when the script sees a token, the script
 knows that the buyer has already authorized payment through
 paypal.  If no token was found, the action is to send the buyer
 to PayPal to first authorize payment
 */

/*
 '-------------------------------------------------------------------------------------------------------------------------------------------
 ' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
 ' Inputs:
 '		paymentAmount:  	Total value of the shopping cart
 '		currencyCodeType: 	Currency code value the PayPal API
 '		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
 '		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
 '		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
 '--------------------------------------------------------------------------------------------------------------------------------------------
 */
function CallShortcutExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL)
{
    //------------------------------------------------------------------------------------------------------------------------------------
    // Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation

    $nvpstr="&Amt=". $paymentAmount;
    $nvpstr = $nvpstr . "&PAYMENTACTION=" . $paymentType;
    $nvpstr = $nvpstr . "&ReturnUrl=" . $returnURL;
    $nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
    $nvpstr = $nvpstr . "&CURRENCYCODE=" . $currencyCodeType;

    $_SESSION["currencyCodeType"] = $currencyCodeType;
    $_SESSION["PaymentType"] = $paymentType;

    //'---------------------------------------------------------------------------------------------------------------
    //' Make the API call to PayPal
    //' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
    //' If an error occured, show the resulting errors
    //'---------------------------------------------------------------------------------------------------------------
    $resArray=hash_call("SetExpressCheckout", $nvpstr);
    $ack = strtoupper($resArray["ACK"]);
    if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
    {
        $token = urldecode($resArray["TOKEN"]);
        $_SESSION['TOKEN']=$token;
    }

    return $resArray;
}

/*
 '-------------------------------------------------------------------------------------------------------------------------------------------
 ' Purpose: 	Prepares the parameters for the SetExpressCheckout API Call.
 ' Inputs:
 '		paymentAmount:  	Total value of the shopping cart
 '		currencyCodeType: 	Currency code value the PayPal API
 '		paymentType: 		paymentType has to be one of the following values: Sale or Order or Authorization
 '		returnURL:			the page where buyers return to after they are done with the payment review on PayPal
 '		cancelURL:			the page where buyers return to when they cancel the payment review on PayPal
 '		shipToName:		the Ship to name entered on the merchant's site
 '		shipToStreet:		the Ship to Street entered on the merchant's site
 '		shipToCity:			the Ship to City entered on the merchant's site
 '		shipToState:		the Ship to State entered on the merchant's site
 '		shipToCountryCode:	the Code for Ship to Country entered on the merchant's site
 '		shipToZip:			the Ship to ZipCode entered on the merchant's site
 '		shipToStreet2:		the Ship to Street2 entered on the merchant's site
 '		phoneNum:			the phoneNum  entered on the merchant's site
 '--------------------------------------------------------------------------------------------------------------------------------------------
 */
function CallMarkExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL,
$cancelURL, $shipToName, $shipToStreet, $shipToCity, $shipToState,
$shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum
)
{
    //------------------------------------------------------------------------------------------------------------------------------------
    // Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation

    $nvpstr="&Amt=". $paymentAmount;
    $nvpstr = $nvpstr . "&PAYMENTACTION=" . $paymentType;
    $nvpstr = $nvpstr . "&ReturnUrl=" . $returnURL;
    $nvpstr = $nvpstr . "&CANCELURL=" . $cancelURL;
    $nvpstr = $nvpstr . "&CURRENCYCODE=" . $currencyCodeType;
    $nvpstr = $nvpstr . "&ADDROVERRIDE=1";
    $nvpstr = $nvpstr . "&SHIPTONAME=" . $shipToName;
    $nvpstr = $nvpstr . "&SHIPTOSTREET=" . $shipToStreet;
    $nvpstr = $nvpstr . "&SHIPTOSTREET2=" . $shipToStreet2;
    $nvpstr = $nvpstr . "&SHIPTOCITY=" . $shipToCity;
    $nvpstr = $nvpstr . "&SHIPTOSTATE=" . $shipToState;
    $nvpstr = $nvpstr . "&SHIPTOCOUNTRYCODE=" . $shipToCountryCode;
    $nvpstr = $nvpstr . "&SHIPTOZIP=" . $shipToZip;
    $nvpstr = $nvpstr . "&PHONENUM=" . $phoneNum;

    $_SESSION["currencyCodeType"] = $currencyCodeType;
    $_SESSION["PaymentType"] = $paymentType;

    //'---------------------------------------------------------------------------------------------------------------
    //' Make the API call to PayPal
    //' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
    //' If an error occured, show the resulting errors
    //'---------------------------------------------------------------------------------------------------------------
    $resArray=hash_call("SetExpressCheckout", $nvpstr);
    $ack = strtoupper($resArray["ACK"]);
    if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
    {
        $token = urldecode($resArray["TOKEN"]);
        $_SESSION['TOKEN']=$token;
    }

    return $resArray;
}

/*
 '-------------------------------------------------------------------------------------------
 ' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
 '
 ' Inputs:
 '		None
 ' Returns:
 '		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
 '-------------------------------------------------------------------------------------------
 */
function GetShippingDetails( $token )
{
    //'--------------------------------------------------------------
    //' At this point, the buyer has completed authorizing the payment
    //' at PayPal.  The function will call PayPal to obtain the details
    //' of the authorization, incuding any shipping information of the
    //' buyer.  Remember, the authorization is not a completed transaction
    //' at this state - the buyer still needs an additional step to finalize
    //' the transaction
    //'--------------------------------------------------------------

    //'---------------------------------------------------------------------------
    //' Build a second API request to PayPal, using the token as the
    //'  ID to get the details on the payment authorization
    //'---------------------------------------------------------------------------
    $nvpstr="&TOKEN=" . $token;

    //'---------------------------------------------------------------------------
    //' Make the API call and store the results in an array.
    //'	If the call was a success, show the authorization details, and provide
    //' 	an action to complete the payment.
    //'	If failed, show the error
    //'---------------------------------------------------------------------------
    $resArray=hash_call("GetExpressCheckoutDetails",$nvpstr);
    $ack = strtoupper($resArray["ACK"]);
    if($ack == "SUCCESS" || $ack=="SUCCESSWITHWARNING")
    {
        $_SESSION['payer_id'] =	$resArray['PAYERID'];
    }
    return $resArray;
}

/*
 '-------------------------------------------------------------------------------------------------------------------------------------------
 ' Purpose: 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
 '
 ' Inputs:
 '		sBNCode:	The BN code used by PayPal to track the transactions from a given shopping cart.
 ' Returns:
 '		The NVP Collection object of the GetExpressCheckoutDetails Call Response.
 '--------------------------------------------------------------------------------------------------------------------------------------------
 */
function ConfirmPayment( $FinalPaymentAmt )
{
    /* Gather the information to make the final call to
     finalize the PayPal payment.  The variable nvpstr
     holds the name value pairs
     */


    //Format the other parameters that were stored in the session from the previous calls
    $token 				= urlencode($_SESSION['TOKEN']);
    $paymentType 		= urlencode($_SESSION['PaymentType']);
    $currencyCodeType 	= urlencode($_SESSION['currencyCodeType']);
    $payerID 			= urlencode($_SESSION['payer_id']);

    $serverName 		= urlencode($_SERVER['SERVER_NAME']);

    $nvpstr  = '&TOKEN=' . $token . '&PAYERID=' . $payerID . '&PAYMENTACTION=' . $paymentType . '&AMT=' . $FinalPaymentAmt;
    $nvpstr .= '&CURRENCYCODE=' . $currencyCodeType . '&IPADDRESS=' . $serverName;

    /* Make the call to PayPal to finalize payment
     If an error occured, show the resulting errors
     */
    $resArray=hash_call("DoExpressCheckoutPayment",$nvpstr);

    /* Display the API response back to the browser.
     If the response from PayPal was a success, display the response parameters'
     If the response was an error, display the errors received using APIError.php.
     */
    $ack = strtoupper($resArray["ACK"]);

    return $resArray;
}

/*
 '-------------------------------------------------------------------------------------------------------------------------------------------
 ' Purpose: 	This function makes a DoDirectPayment API call
 '
 ' Inputs:
 '		paymentType:		paymentType has to be one of the following values: Sale or Order or Authorization
 '		paymentAmount:  	total value of the shopping cart
 '		currencyCode:	 	currency code value the PayPal API
 '		firstName:			first name as it appears on credit card
 '		lastName:			last name as it appears on credit card
 '		street:				buyer's street address line as it appears on credit card
 '		city:				buyer's city
 '		state:				buyer's state
 '		countryCode:		buyer's country code
 '		zip:				buyer's zip
 '		creditCardType:		buyer's credit card type (i.e. Visa, MasterCard ... )
 '		creditCardNumber:	buyers credit card number without any spaces, dashes or any other characters
 '		expDate:			credit card expiration date
 '		cvv2:				Card Verification Value
 '
 '-------------------------------------------------------------------------------------------
 '
 ' Returns:
 '		The NVP Collection object of the DoDirectPayment Call Response.
 '--------------------------------------------------------------------------------------------------------------------------------------------
 */


function DirectPayment( $paymentType, $paymentAmount, $creditCardType, $creditCardNumber,
$expDate, $cvv2, $firstName, $lastName, $street, $city, $state, $zip,
$countryCode, $currencyCode )
{
    //Construct the parameter string that describes DoDirectPayment
    $nvpstr = "&AMT=" . $paymentAmount;
    $nvpstr = $nvpstr . "&CURRENCYCODE=" . $currencyCode;
    $nvpstr = $nvpstr . "&PAYMENTACTION=" . $paymentType;
    $nvpstr = $nvpstr . "&CREDITCARDTYPE=" . $creditCardType;
    $nvpstr = $nvpstr . "&ACCT=" . $creditCardNumber;
    $nvpstr = $nvpstr . "&EXPDATE=" . $expDate;
    $nvpstr = $nvpstr . "&CVV2=" . $cvv2;
    $nvpstr = $nvpstr . "&FIRSTNAME=" . $firstName;
    $nvpstr = $nvpstr . "&LASTNAME=" . $lastName;
    $nvpstr = $nvpstr . "&STREET=" . $street;
    $nvpstr = $nvpstr . "&CITY=" . $city;
    $nvpstr = $nvpstr . "&STATE=" . $state;
    $nvpstr = $nvpstr . "&COUNTRYCODE=" . $countryCode;
    $nvpstr = $nvpstr . "&IPADDRESS=" . $_SERVER['REMOTE_ADDR'];

    $resArray=hash_call("DoDirectPayment", $nvpstr);

    return $resArray;
}


/**
 '-------------------------------------------------------------------------------------------------------------------------------------------
 * hash_call: Function to perform the API call to PayPal using API signature
 * @methodName is name of API  method.
 * @nvpStr is nvp string.
 * returns an associtive array containing the response from the server.
 '-------------------------------------------------------------------------------------------------------------------------------------------
 */
function hash_call($methodName,$nvpStr)
{
    //declaring of global variables
    global $API_Endpoint, $version, $API_UserName, $API_Password, $API_Signature;
    global $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $gv_ApiErrorURL;
    global $sBNCode;

    //setting the curl parameters.
    $ch = curl_init();
    /*print $API_Endpoint."-".$version."-".$API_UserName."-".$API_Password."-".$API_Signature."<br>";
     print $USE_PROXY."-".$gv_ApiErrorURL."-".$sBNCode."<br>";
     print $nvpStr;
     exit;*/
    curl_setopt($ch, CURLOPT_URL,$API_Endpoint);
    curl_setopt($ch, CURLOPT_VERBOSE, 1);

    //turning off the server and peer verification(TrustManager Concept).
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);

    curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
    curl_setopt($ch, CURLOPT_POST, 1);

    //if USE_PROXY constant set to TRUE in Constants.php, then only proxy will be enabled.
    //Set proxy name to PROXY_HOST and port number to PROXY_PORT in constants.php
    if($USE_PROXY)
    curl_setopt ($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT);

    //NVPRequest for submitting to server
    $nvpreq="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($version) . "&PWD=" . urlencode($API_Password) . "&USER=" . urlencode($API_UserName) . "&SIGNATURE=" . urlencode($API_Signature) . $nvpStr . "&BUTTONSOURCE=" . urlencode($sBNCode);

    //setting the nvpreq as POST FIELD to curl
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

    //getting response from server
    $response = curl_exec($ch);

    //convrting NVPResponse to an Associative Array
    $nvpResArray=deformatNVP($response);
    $nvpReqArray=deformatNVP($nvpreq);
    $_SESSION['nvpReqArray']=$nvpReqArray;

    if (curl_errno($ch))
    {
        // moving to display page to display curl errors
        $_SESSION['curl_error_no']=curl_errno($ch) ;
        $_SESSION['curl_error_msg']=curl_error($ch);

        //Execute the Error handling module to display errors.
    }
    else
    {
        //closing the curl
        curl_close($ch);
    }

    return $nvpResArray;
}

/*'----------------------------------------------------------------------------------
 Purpose: Redirects to PayPal.com site.
 Inputs:  NVP string.
 Returns:
 ----------------------------------------------------------------------------------
 */
function RedirectToPayPal ( $token )
{
    global $PAYPAL_URL;

    // Redirect to paypal.com here
    $payPalURL = $PAYPAL_URL . $token;
    header("Location: ".$payPalURL);
}


/*'----------------------------------------------------------------------------------
 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
 * It is usefull to search for a particular key and displaying arrays.
 * @nvpstr is NVPString.
 * @nvpArray is Associative Array.
 ----------------------------------------------------------------------------------
 */
function deformatNVP($nvpstr)
{
    $intial=0;
    $nvpArray = array();

    while(strlen($nvpstr))
    {
        //postion of Key
        $keypos= strpos($nvpstr,'=');
        //position of value
        $valuepos = strpos($nvpstr,'&') ? strpos($nvpstr,'&'): strlen($nvpstr);

        /*getting the Key and Value values and storing in a Associative Array*/
        $keyval=substr($nvpstr,$intial,$keypos);
        $valval=substr($nvpstr,$keypos+1,$valuepos-$keypos-1);
        //decoding the respose
        $nvpArray[urldecode($keyval)] =urldecode( $valval);
        $nvpstr=substr($nvpstr,$valuepos+1,strlen($nvpstr));
    }
    return $nvpArray;
}

?>