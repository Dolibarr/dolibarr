<?php
/* Copyright (C) 2008-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2011-2012	Regis Houssin		<regis.houssin@inodbox.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file			htdocs/paypal/lib/paypal.lib.php
 *  \ingroup		paypal
 *  \brief			Library for common paypal functions
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';


/**
 *  Define head array for tabs of paypal tools setup pages
 *
 *  @return			Array of head
 */
function paypaladmin_prepare_head()
{
	global $langs, $conf;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT."/paypal/admin/paypal.php";
	$head[$h][1] = $langs->trans("PayPal");
	$head[$h][2] = 'paypalaccount';
	$h++;

	$object = new stdClass();

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'paypaladmin');

	complete_head_from_modules($conf, $langs, $object, $head, $h, 'paypaladmin', 'remove');

	return $head;
}


/**
 * Send redirect to paypal to browser
 *
 * @param	float	$paymentAmount		Amount
 * @param   string	$currencyCodeType	Currency code
 * @param	string	$paymentType		Payment type
 * @param  	string	$returnURL			Url to use if payment is OK
 * @param   string	$cancelURL			Url to use if payment is KO
 * @param   string	$tag				Full tag
 * @return	string						No return (a redirect is done) if OK, or Error message if KO
 */
function print_paypal_redirect($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $tag)
{
	//declaring of global variables
	global $conf, $langs;
	global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
	global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;

	global $shipToName, $shipToStreet, $shipToCity, $shipToState, $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum;
	global $email, $desc;

	//'------------------------------------
	//' Calls the SetExpressCheckout API call
	//'
	//'-------------------------------------------------

	if (empty($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY)) $conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY = 'integral';

	$solutionType = 'Sole';
	$landingPage = 'Billing';
	// For payment with Paypal only
	if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'paypalonly')
	{
		$solutionType = 'Mark';
		$landingPage = 'Login';
	}
	// For payment with Credit card or Paypal
	if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'integral')
	{
		$solutionType = 'Sole';
		$landingPage = 'Billing';
	}
	// For payment with Credit card
	if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'cconly')
	{
		$solutionType = 'Sole';
		$landingPage = 'Billing';
	}

	dol_syslog("print_paypal_redirect expresscheckout redirect with callSetExpressCheckout $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $tag, $solutionType, $landingPage, $shipToName, $shipToStreet, $shipToCity, $shipToState, $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum");
	$resArray = callSetExpressCheckout(
		$paymentAmount,
		$currencyCodeType,
		$paymentType,
		$returnURL,
		$cancelURL,
		$tag,
		$solutionType,
		$landingPage,
		$shipToName,
		$shipToStreet,
		$shipToCity,
		$shipToState,
		$shipToCountryCode,
		$shipToZip,
		$shipToStreet2,
		$phoneNum,
		$email,
		$desc
	);

	dol_syslog("print_paypal_redirect resArray=".var_export($resArray, true), LOG_DEBUG);

	$ack = strtoupper($resArray["ACK"]);
	if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
	{
		$token = $resArray["TOKEN"];

		// Redirect to paypal.com here
		$payPalURL = $API_Url.$token;
		dol_syslog("Redirect to ".$payPalURL, LOG_INFO);
		header("Location: ".$payPalURL);
		exit;
	} else {
		//Display a user friendly Error on the page using any of the following error information returned by PayPal
		$ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
		$ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
		$ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
		$ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

		if ($ErrorCode == 10729)
		{
			$mesg .= "PayPal can't accept payments for this thirdparty. An address is defined but is not complete (missing State).<br>Ask system administrator to fix address or to setup Paypal module to accept payments even on not complete addresses (remove option PAYPAL_REQUIRE_VALID_SHIPPING_ADDRESS).<br>\n";
		} else {
			$mesg = $langs->trans('SetExpressCheckoutAPICallFailed')."<br>\n";
			$mesg .= $langs->trans('DetailedErrorMessage').": ".$ErrorLongMsg."<br>\n";
			$mesg .= $langs->trans('ShortErrorMessage').": ".$ErrorShortMsg."<br>\n";
			$mesg .= $langs->trans('ErrorCode').": ".$ErrorCode."<br>\n";
			$mesg .= $langs->trans('ErrorSeverityCode').": ".$ErrorSeverityCode."<br>\n";
		}

		return $mesg;
	}
}

/**
 *-------------------------------------------------------------------------------------------------------------------------------------------
 * Purpose:     Prepares the parameters for the SetExpressCheckout API Call.
 * Inputs:
 *      paymentAmount:      Total value of the shopping cart
 *      currencyCodeType:   Currency code value the PayPal API
 *      paymentType:        paymentType has to be one of the following values: Sale or Order or Authorization
 *      returnURL:          the page where buyers return to after they are done with the payment review on PayPal
 *      cancelURL:          the page where buyers return to when they cancel the payment review on PayPal
 *      shipToName:     the Ship to name entered on the merchant's site
 *      shipToStreet:       the Ship to Street entered on the merchant's site
 *      shipToCity:         the Ship to City entered on the merchant's site
 *      shipToState:        the Ship to State entered on the merchant's site
 *      shipToCountryCode:  the Code for Ship to Country entered on the merchant's site
 *      shipToZip:          the Ship to ZipCode entered on the merchant's site
 *      shipToStreet2:      the Ship to Street2 entered on the merchant's site
 *      phoneNum:           the phoneNum  entered on the merchant's site
 *      email:              the buyer email
 *      desc:               Product description
 * See https://developer.paypal.com/docs/classic/api/merchant/SetExpressCheckout_API_Operation_NVP/
 *
 * @param 	double 			$paymentAmount		Payment amount
 * @param 	string 			$currencyCodeType	Currency
 * @param 	string 			$paymentType		Payment type
 * @param 	string 			$returnURL			Return Url
 * @param 	string 			$cancelURL			Cancel Url
 * @param 	string 			$tag				Full tag
 * @param 	string 			$solutionType		Type ('Mark' or 'Sole')
 * @param 	string 			$landingPage		Landing page ('Login' or 'Billing')
 * @param	string			$shipToName			Ship to name
 * @param	string			$shipToStreet		Ship to street
 * @param	string			$shipToCity			Ship to city
 * @param	string			$shipToState		Ship to state
 * @param	string			$shipToCountryCode	Ship to country code
 * @param	string			$shipToZip			Ship to zip
 * @param	string			$shipToStreet2		Ship to street2
 * @param	string			$phoneNum			Phone
 * @param	string			$email				Email
 * @param	string			$desc				Description
 * @return	array								Array
 */
function callSetExpressCheckout($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $tag, $solutionType, $landingPage, $shipToName, $shipToStreet, $shipToCity, $shipToState, $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum, $email = '', $desc = '')
{
	//------------------------------------------------------------------------------------------------------------------------------------
	// Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation

	//declaring of global variables
	global $conf, $langs, $mysoc;
	global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
	global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;

	$nvpstr = '';
	//$nvpstr = $nvpstr . "&VERSION=".$API_version;				// Already added by hash_call
	$nvpstr = $nvpstr."&RETURNURL=".urlencode($returnURL);
	$nvpstr = $nvpstr."&CANCELURL=".urlencode($cancelURL);
	if (!empty($conf->global->PAYPAL_ALLOW_NOTES))
	{
		$nvpstr = $nvpstr."&ALLOWNOTE=0";
	}
	if (empty($conf->global->PAYPAL_REQUIRE_VALID_SHIPPING_ADDRESS))
	{
		$nvpstr = $nvpstr."&NOSHIPPING=1"; // An empty or not complete shipping address will be accepted
	} else {
		$nvpstr = $nvpstr."&NOSHIPPING=0"; // A valid shipping address is required (full required fields mandatory)
	}
	$nvpstr = $nvpstr."&SOLUTIONTYPE=".urlencode($solutionType);
	$nvpstr = $nvpstr."&LANDINGPAGE=".urlencode($landingPage);
	if (!empty($conf->global->PAYPAL_CUSTOMER_SERVICE_NUMBER))
	{
		$nvpstr = $nvpstr."&CUSTOMERSERVICENUMBER=".urlencode($conf->global->PAYPAL_CUSTOMER_SERVICE_NUMBER); // Hotline phone number
	}

	$paypalprefix = 'PAYMENTREQUEST_0_';
	//$paypalprefix = '';
	if (!empty($paypalprefix) && $paymentType == 'Sole') $paymentType = 'Sale';

	$nvpstr = $nvpstr."&AMT=".urlencode($paymentAmount); // Total for all elements

	$nvpstr = $nvpstr."&".$paypalprefix."INVNUM=".urlencode($tag);
	$nvpstr = $nvpstr."&".$paypalprefix."AMT=".urlencode($paymentAmount); // AMT deprecated by paypal -> PAYMENTREQUEST_n_AMT
	$nvpstr = $nvpstr."&".$paypalprefix."ITEMAMT=".urlencode($paymentAmount); // AMT deprecated by paypal -> PAYMENTREQUEST_n_AMT
	$nvpstr = $nvpstr."&".$paypalprefix."PAYMENTACTION=".urlencode($paymentType); // PAYMENTACTION deprecated by paypal -> PAYMENTREQUEST_n_PAYMENTACTION
	$nvpstr = $nvpstr."&".$paypalprefix."CURRENCYCODE=".urlencode($currencyCodeType); // CURRENCYCODE deprecated by paypal -> PAYMENTREQUEST_n_CURRENCYCODE

	$nvpstr = $nvpstr."&".$paypalprefix."L_PAYMENTREQUEST_0_QTY0=1";
	$nvpstr = $nvpstr."&".$paypalprefix."L_PAYMENTREQUEST_0_AMT0=".urlencode($paymentAmount);
	$nvpstr = $nvpstr."&".$paypalprefix."L_PAYMENTREQUEST_0_NAME0=".urlencode($desc);
	$nvpstr = $nvpstr."&".$paypalprefix."L_PAYMENTREQUEST_0_NUMBER0=0";

	$nvpstr = $nvpstr."&".$paypalprefix."SHIPTONAME=".urlencode($shipToName); // SHIPTONAME deprecated by paypal -> PAYMENTREQUEST_n_SHIPTONAME
	$nvpstr = $nvpstr."&".$paypalprefix."SHIPTOSTREET=".urlencode($shipToStreet); //
	$nvpstr = $nvpstr."&".$paypalprefix."SHIPTOSTREET2=".urlencode($shipToStreet2);
	$nvpstr = $nvpstr."&".$paypalprefix."SHIPTOCITY=".urlencode($shipToCity);
	$nvpstr = $nvpstr."&".$paypalprefix."SHIPTOSTATE=".urlencode($shipToState);
	$nvpstr = $nvpstr."&".$paypalprefix."SHIPTOCOUNTRYCODE=".urlencode($shipToCountryCode);
	$nvpstr = $nvpstr."&".$paypalprefix."SHIPTOZIP=".urlencode($shipToZip);
	$nvpstr = $nvpstr."&".$paypalprefix."PHONENUM=".urlencode($phoneNum);
	if (!empty($email)) $nvpstr = $nvpstr."&".$paypalprefix."EMAIL=".urlencode($email); // EMAIL deprecated by paypal -> PAYMENTREQUEST_n_EMAIL
	if (!empty($desc))  $nvpstr = $nvpstr."&".$paypalprefix."DESC=".urlencode($desc); // DESC deprecated by paypal -> PAYMENTREQUEST_n_DESC

	if (!empty($conf->global->PAYPAL_LOGOIMG) && $mysoc->logo)
	{
		global $dolibarr_main_url_root;

		// Define $urlwithroot
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		$urllogo = $urlwithroot."/viewimage.php?modulepart=mycompany&file=".urlencode('logos/'.$mysoc->logo);
		$nvpstr = $nvpstr."&LOGOIMG=".urlencode($urllogo);
	}
	if (!empty($conf->global->PAYPAL_BRANDNAME))
	{
		$nvpstr = $nvpstr."&BRANDNAME=".urlencode($conf->global->PAYPAL_BRANDNAME); // BRANDNAME
	}
	if (!empty($conf->global->PAYPAL_NOTETOBUYER))
	{
		$nvpstr = $nvpstr."&NOTETOBUYER=".urlencode($conf->global->PAYPAL_NOTETOBUYER); // PAYPAL_NOTETOBUYER
	}

	$_SESSION["FinalPaymentAmt"] = $paymentAmount;
	$_SESSION["currencyCodeType"] = $currencyCodeType;
	$_SESSION["PaymentType"] = $paymentType; // 'Mark', 'Sole'
	$_SESSION['ipaddress'] = getUserRemoteIP();	// Payer ip

	//'---------------------------------------------------------------------------------------------------------------
	//' Make the API call to PayPal
	//' If the API call succeded, then redirect the buyer to PayPal to begin to authorize payment.
	//' If an error occured, show the resulting errors
	//'---------------------------------------------------------------------------------------------------------------
	$resArray = hash_call("SetExpressCheckout", $nvpstr);
	$ack = strtoupper($resArray["ACK"]);
	if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
	{
		$token = urldecode($resArray["TOKEN"]);
		$_SESSION['TOKEN'] = $token;
	}

	return $resArray;
}

/**
 * 	Prepares the parameters for the GetExpressCheckoutDetails API Call.
 *
 *	@param	string	$token		Token
 *	@return	array				The NVP Collection object of the GetExpressCheckoutDetails Call Response.
 */
function getDetails($token)
{
	//'--------------------------------------------------------------
	//' At this point, the buyer has completed authorizing the payment
	//' at PayPal.  The function will call PayPal to obtain the details
	//' of the authorization, incuding any shipping information of the
	//' buyer.  Remember, the authorization is not a completed transaction
	//' at this state - the buyer still needs an additional step to finalize
	//' the transaction
	//'--------------------------------------------------------------

	//declaring of global variables
	global $conf, $langs;
	global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
	global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;

	//'---------------------------------------------------------------------------
	//' Build a second API request to PayPal, using the token as the
	//'  ID to get the details on the payment authorization
	//'---------------------------------------------------------------------------
	$nvpstr = "&TOKEN=".$token;

	//'---------------------------------------------------------------------------
	//' Make the API call and store the results in an array.
	//' If the call was a success, show the authorization details, and provide
	//'     an action to complete the payment.
	//' If failed, show the error
	//'---------------------------------------------------------------------------
	$resArray = hash_call("GetExpressCheckoutDetails", $nvpstr);
	$ack = strtoupper($resArray["ACK"]);
	if ($ack == "SUCCESS" || $ack == "SUCCESSWITHWARNING")
	{
		$_SESSION['payer_id'] = $resArray['PAYERID'];
	}
	return $resArray;
}


/**
 *	Validate payment
 *
 *	@param	string	$token				Token
 *	@param	string	$paymentType		Type
 *	@param	string	$currencyCodeType	Currency
 *	@param	string	$payerID			Payer ID
 *	@param	string	$ipaddress			IP Address
 *	@param	string	$FinalPaymentAmt	Amount
 *	@param	string	$tag				Full tag
 *	@return	array
 */
function confirmPayment($token, $paymentType, $currencyCodeType, $payerID, $ipaddress, $FinalPaymentAmt, $tag)
{
	/* Gather the information to make the final call to
     finalize the PayPal payment.  The variable nvpstr
     holds the name value pairs
     */

	//declaring of global variables
	global $conf, $langs;
	global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
	global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;

	$nvpstr = '';
	$nvpstr .= '&TOKEN='.urlencode($token);
	$nvpstr .= '&PAYERID='.urlencode($payerID);
	$nvpstr .= '&PAYMENTACTION='.urlencode($paymentType);
	$nvpstr .= '&AMT='.urlencode($FinalPaymentAmt);
	$nvpstr .= '&CURRENCYCODE='.urlencode($currencyCodeType);
	$nvpstr .= '&IPADDRESS='.urlencode($ipaddress);
	$nvpstr .= '&INVNUM='.urlencode($tag);

	/* Make the call to PayPal to finalize payment
     If an error occured, show the resulting errors
     */
	$resArray = hash_call("DoExpressCheckoutPayment", $nvpstr);

	/* Display the API response back to the browser.
     If the response from PayPal was a success, display the response parameters'
     If the response was an error, display the errors received using APIError.php.
     */
	$ack = strtoupper($resArray["ACK"]);

	return $resArray;
}

/**
 *	This function makes a DoDirectPayment API call
 *
 *  paymentType:        paymentType has to be one of the following values: Sale or Order or Authorization
 *  paymentAmount:      total value of the shopping cart
 *  currencyCode:       currency code value the PayPal API
 *  firstName:          first name as it appears on credit card
 *  lastName:           last name as it appears on credit card
 *  street:             buyer's street address line as it appears on credit card
 *  city:               buyer's city
 *  state:              buyer's state
 *  countryCode:        buyer's country code
 *  zip:                buyer's zip
 *  creditCardType:     buyer's credit card type (i.e. Visa, MasterCard ... )
 *  creditCardNumber:   buyers credit card number without any spaces, dashes or any other characters
 *  expDate:            credit card expiration date
 *  cvv2:               Card Verification Value
 *	@return		array	The NVP Collection object of the DoDirectPayment Call Response.
 */
/*
function DirectPayment($paymentType, $paymentAmount, $creditCardType, $creditCardNumber, $expDate, $cvv2, $firstName, $lastName, $street, $city, $state, $zip, $countryCode, $currencyCode, $tag)
{
    //declaring of global variables
    global $conf, $langs;
    global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;

    //Construct the parameter string that describes DoDirectPayment
    $nvpstr = '';
    $nvpstr = $nvpstr . "&AMT=" . urlencode($paymentAmount);              // deprecated by paypal
    $nvpstr = $nvpstr . "&CURRENCYCODE=" . urlencode($currencyCode);
    $nvpstr = $nvpstr . "&PAYMENTACTION=" . urlencode($paymentType);      // deprecated by paypal
    $nvpstr = $nvpstr . "&CREDITCARDTYPE=" . urlencode($creditCardType);
    $nvpstr = $nvpstr . "&ACCT=" . urlencode($creditCardNumber);
    $nvpstr = $nvpstr . "&EXPDATE=" . urlencode($expDate);
    $nvpstr = $nvpstr . "&CVV2=" . urlencode($cvv2);
    $nvpstr = $nvpstr . "&FIRSTNAME=" . urlencode($firstName);
    $nvpstr = $nvpstr . "&LASTNAME=" . urlencode($lastName);
    $nvpstr = $nvpstr . "&STREET=" . urlencode($street);
    $nvpstr = $nvpstr . "&CITY=" . urlencode($city);
    $nvpstr = $nvpstr . "&STATE=" . urlencode($state);
    $nvpstr = $nvpstr . "&COUNTRYCODE=" . urlencode($countryCode);
    $nvpstr = $nvpstr . "&IPADDRESS=" . getUserRemotIP();
    $nvpstr = $nvpstr . "&INVNUM=" . urlencode($tag);

    $resArray=hash_call("DoDirectPayment", $nvpstr);

    return $resArray;
}
*/


/**
 * hash_call: Function to perform the API call to PayPal using API signature
 *
 * @param	string	$methodName 	is name of API  method.
 * @param	string	$nvpStr 		is nvp string.
 * @return	array					returns an associtive array containing the response from the server.
 */
function hash_call($methodName, $nvpStr)
{
	//declaring of global variables
	global $conf, $langs;
	global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT, $PROXY_USER, $PROXY_PASS;
	global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;

	// TODO problem with triggers
	$API_version = "98.0";
	if (!empty($conf->global->PAYPAL_API_SANDBOX) || GETPOST('forcesandbox', 'alpha'))		// We can force sand box with param 'forcesandbox'
	{
		$API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
		$API_Url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
	} else {
		$API_Endpoint = "https://api-3t.paypal.com/nvp";
		$API_Url = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
	}

	// Clean parameters
	$PAYPAL_API_USER = "";
	if (!empty($conf->global->PAYPAL_API_USER)) $PAYPAL_API_USER = $conf->global->PAYPAL_API_USER;
	$PAYPAL_API_PASSWORD = "";
	if (!empty($conf->global->PAYPAL_API_PASSWORD)) $PAYPAL_API_PASSWORD = $conf->global->PAYPAL_API_PASSWORD;
	$PAYPAL_API_SIGNATURE = "";
	if (!empty($conf->global->PAYPAL_API_SIGNATURE)) $PAYPAL_API_SIGNATURE = $conf->global->PAYPAL_API_SIGNATURE;
	$PAYPAL_API_SANDBOX = "";
	if (!empty($conf->global->PAYPAL_API_SANDBOX)) $PAYPAL_API_SANDBOX = $conf->global->PAYPAL_API_SANDBOX;
	// TODO END problem with triggers

	dol_syslog("Paypal API endpoint ".$API_Endpoint);

	//setting the curl parameters.
	$ch = curl_init();

	/*print $API_Endpoint."-".$API_version."-".$PAYPAL_API_USER."-".$PAYPAL_API_PASSWORD."-".$PAYPAL_API_SIGNATURE."<br>";
     print $USE_PROXY."-".$gv_ApiErrorURL."<br>";
     print $nvpStr;
     exit;*/
	curl_setopt($ch, CURLOPT_URL, $API_Endpoint);
	curl_setopt($ch, CURLOPT_VERBOSE, 1);
	// TLSv1 by default or change to TLSv1.2 in module configuration
	curl_setopt($ch, CURLOPT_SSLVERSION, (empty($conf->global->PAYPAL_SSLVERSION) ? 1 : $conf->global->PAYPAL_SSLVERSION));

	//turning off the server and peer verification(TrustManager Concept).
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

	curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, empty($conf->global->MAIN_USE_CONNECT_TIMEOUT) ? 5 : $conf->global->MAIN_USE_CONNECT_TIMEOUT);
	curl_setopt($ch, CURLOPT_TIMEOUT, empty($conf->global->MAIN_USE_RESPONSE_TIMEOUT) ? 30 : $conf->global->MAIN_USE_RESPONSE_TIMEOUT);

	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_POST, 1);

	//if USE_PROXY constant set to true in Constants.php, then only proxy will be enabled.
	if ($USE_PROXY)
	{
		dol_syslog("Paypal API hash_call set proxy to ".$PROXY_HOST.":".$PROXY_PORT." - ".$PROXY_USER.":".$PROXY_PASS);
		//curl_setopt ($ch, CURLOPT_PROXYTYPE, CURLPROXY_HTTP); // Curl 7.10
		curl_setopt($ch, CURLOPT_PROXY, $PROXY_HOST.":".$PROXY_PORT);
		if ($PROXY_USER) curl_setopt($ch, CURLOPT_PROXYUSERPWD, $PROXY_USER.":".$PROXY_PASS);
	}

	//NVPRequest for submitting to server
	$nvpreq = "METHOD=".urlencode($methodName)."&VERSION=".urlencode($API_version)."&PWD=".urlencode($PAYPAL_API_PASSWORD)."&USER=".urlencode($PAYPAL_API_USER)."&SIGNATURE=".urlencode($PAYPAL_API_SIGNATURE).$nvpStr;
	$nvpreq .= "&LOCALECODE=".strtoupper($langs->getDefaultLang(1));
	//$nvpreq.="&BRANDNAME=".urlencode();       // Override merchant name
	//$nvpreq.="&NOTIFYURL=".urlencode();       // For Instant Payment Notification url


	dol_syslog("Paypal API hash_call nvpreq=".$nvpreq);

	//setting the nvpreq as POST FIELD to curl
	curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

	//getting response from server
	$response = curl_exec($ch);

	$nvpReqArray = deformatNVP($nvpreq);
	$_SESSION['nvpReqArray'] = $nvpReqArray;

	//convrting NVPResponse to an Associative Array
	dol_syslog("Paypal API hash_call Response nvpresp=".$response);
	$nvpResArray = deformatNVP($response);

	if (curl_errno($ch)) {
		// moving to display page to display curl errors
		$_SESSION['curl_error_no'] = curl_errno($ch);
		$_SESSION['curl_error_msg'] = curl_error($ch);

		//Execute the Error handling module to display errors.
	} else {
		//closing the curl
		curl_close($ch);
	}

	return $nvpResArray;
}


/**
 * This function will take NVPString and convert it to an Associative Array and it will decode the response.
 * It is usefull to search for a particular key and displaying arrays.
 *
 * @param	string	$nvpstr 		NVPString
 * @return	array					nvpArray = Associative Array
 */
function deformatNVP($nvpstr)
{
	$intial = 0;
	$nvpArray = array();

	while (strlen($nvpstr))
	{
		//postion of Key
		$keypos = strpos($nvpstr, '=');
		//position of value
		$valuepos = strpos($nvpstr, '&') ? strpos($nvpstr, '&') : strlen($nvpstr);

		/*getting the Key and Value values and storing in a Associative Array*/
		$keyval = substr($nvpstr, $intial, $keypos);
		$valval = substr($nvpstr, $keypos + 1, $valuepos - $keypos - 1);
		//decoding the respose
		$nvpArray[urldecode($keyval)] = urldecode($valval);
		$nvpstr = substr($nvpstr, $valuepos + 1, strlen($nvpstr));
	}
	return $nvpArray;
}

/**
 * 	Get API errors
 *
 * 	@return	array		Array of errors
 */
function getApiError()
{
	$errors = array();

	$resArray = $_SESSION['reshash'];

	if (isset($_SESSION['curl_error_no']))
	{
		$errors[] = $_SESSION['curl_error_no'].'-'.$_SESSION['curl_error_msg'];
	}

	foreach ($resArray as $key => $value)
	{
		$errors[] = $key.'-'.$value;
	}

	return $errors;
}
