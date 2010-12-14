<?php
/* Copyright (C) 2008-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2007 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file			htdocs/paypal/lib/paypal.lib.php
 *  \ingroup		paypal
 *  \brief			Library for common paypal functions
 *  \version		$Id$
 */
function llxHeaderPaypal($title, $head = "")
{
	global $user, $conf, $langs;

	header("Content-type: text/html; charset=".$conf->file->character_set_client);

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">';
	//print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01//EN" http://www.w3.org/TR/1999/REC-html401-19991224/strict.dtd>';
	print "\n";
	print "<html>\n";
	print "<head>\n";
	print '<meta name="robots" content="noindex,nofollow">'."\n";
	print '<meta name="keywords" content="dolibarr,payment,online">'."\n";
	print '<meta name="description" content="Welcome on Dolibarr online payment form">'."\n";
	print "<title>".$title."</title>\n";
	if ($head) print $head."\n";
	if ($conf->global->PAYPAL_CSS_URL) print '<link rel="stylesheet" type="text/css" href="'.$conf->global->PAYPAL_CSS_URL.'?lang='.$langs->defaultlang.'">'."\n";
	else
	{
		print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.$conf->css.'?lang='.$langs->defaultlang.'">'."\n";
		print '<style type="text/css">';
		print '.CTableRow1      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #e6E6eE; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
		print '.CTableRow2      { margin: 1px; padding: 3px; font: 12px verdana,arial; background: #FFFFFF; color: #000000; -moz-border-radius-topleft:6px; -moz-border-radius-topright:6px; -moz-border-radius-bottomleft:6px; -moz-border-radius-bottomright:6px;}';
		print '</style>';
	}
	print "</head>\n";
	print '<body style="margin: 20px;">'."\n";
}

function llxFooterPaypal()
{
	print "</body>\n";
	print "</html>\n";
}



/**
 * Show footer of company in HTML pages
 *
 * @param   $fromcompany
 * @param   $langs
 */
function html_print_paypal_footer($fromcompany,$langs)
{
	global $conf;

	// Juridical status
	$ligne1="";
	if ($fromcompany->forme_juridique_code)
	{
		$ligne1.=($ligne1?" - ":"").$langs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
	}
	// Capital
	if ($fromcompany->capital)
	{
		$ligne1.=($ligne1?" - ":"").$langs->transnoentities("CapitalOf",$fromcompany->capital)." ".$langs->transnoentities("Currency".$conf->monnaie);
	}
	// Prof Id 1
	if ($fromcompany->idprof1 && ($fromcompany->pays_code != 'FR' || ! $fromcompany->idprof2))
	{
		$field=$langs->transcountrynoentities("ProfId1",$fromcompany->pays_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$ligne1.=($ligne1?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->idprof1);
	}
	// Prof Id 2
	if ($fromcompany->idprof2)
	{
		$field=$langs->transcountrynoentities("ProfId2",$fromcompany->pays_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$ligne1.=($ligne1?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->idprof2);
	}

	// Second line of company infos
	$ligne2="";
	// Prof Id 3
	if ($fromcompany->idprof3)
	{
		$field=$langs->transcountrynoentities("ProfId3",$fromcompany->pays_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$ligne2.=($ligne2?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->idprof3);
	}
	// Prof Id 4
	if ($fromcompany->idprof4)
	{
		$field=$langs->transcountrynoentities("ProfId4",$fromcompany->pays_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$ligne2.=($ligne2?" - ":"").$field.": ".$langs->convToOutputCharset($fromcompany->idprof4);
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '')
	{
		$ligne2.=($ligne2?" - ":"").$langs->transnoentities("VATIntraShort").": ".$langs->convToOutputCharset($fromcompany->tva_intra);
	}

	print '<br><br><hr>'."\n";
	print '<center><font style="font-size: 10px;">'."\n";
	print $fromcompany->nom.'<br>';
	print $ligne1.'<br>';
	print $ligne2;
	print '</font></center>'."\n";
}




/**
 * Send redirect to paypal to browser
 *
 * @param       $paymentAmount
 * @param       $currencyCodeType
 * @param       $paymentType
 * @param       $returnURL
 * @param       $cancelURL
 * @param       $tag
 */
function print_paypal_redirect($paymentAmount,$currencyCodeType,$paymentType,$returnURL,$cancelURL,$tag)
{
    //declaring of global variables
    global $conf, $langs;
    global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;
    global $sBNCode;

    global $shipToName, $shipToStreet, $shipToCity, $shipToState, $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum;

    //'------------------------------------
    //' Calls the SetExpressCheckout API call
    //'
    //'-------------------------------------------------

    if (empty($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY)) $conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY='integral';

    $solutionType='Sole';
    $landingPage='Billing';
    // For payment with Paypal only
    if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'paypalonly')
    {
        $solutionType='Mark';
        $landingPage='Login';
    }
    // For payment with Credit card or Paypal
    if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'integral')
    {
        $solutionType='Sole';
        $landingPage='Billing';
    }
    // For payment with Credit card
    if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'cconly')
    {
        $solutionType='Sole';
        $landingPage='Billing';
    }

    dol_syslog("expresscheckout redirect with CallSetExpressCheckout $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $tag, $landingPage, $solutionType, $shipToName, $shipToStreet, $shipToCity, $shipToState, $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum");
    $resArray = CallSetExpressCheckout ($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $tag, $solutionType, $landingPage,
        $shipToName, $shipToStreet, $shipToCity, $shipToState, $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum);
    /* For direct payment with credit card
    {
        //$resArray = DirectPayment (...);
    }
    */

    $ack = strtoupper($resArray["ACK"]);
    if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
    {
        $token=$resArray["TOKEN"];

        // Redirect to paypal.com here
        $payPalURL = $API_Url . $token;
        header("Location: ".$payPalURL);
    }
    else
    {
        //Display a user friendly Error on the page using any of the following error information returned by PayPal
        $ErrorCode = urldecode($resArray["L_ERRORCODE0"]);
        $ErrorShortMsg = urldecode($resArray["L_SHORTMESSAGE0"]);
        $ErrorLongMsg = urldecode($resArray["L_LONGMESSAGE0"]);
        $ErrorSeverityCode = urldecode($resArray["L_SEVERITYCODE0"]);

        echo "SetExpressCheckout API call failed. ";
        echo "Detailed Error Message: " . $ErrorLongMsg;
        echo "Short Error Message: " . $ErrorShortMsg;
        echo "Error Code: " . $ErrorCode;
        echo "Error Severity Code: " . $ErrorSeverityCode;
    }

}

/*
 '-------------------------------------------------------------------------------------------------------------------------------------------
 ' Purpose:     Prepares the parameters for the SetExpressCheckout API Call.
 ' Inputs:
 '      paymentAmount:      Total value of the shopping cart
 '      currencyCodeType:   Currency code value the PayPal API
 '      paymentType:        paymentType has to be one of the following values: Sale or Order or Authorization
 '      returnURL:          the page where buyers return to after they are done with the payment review on PayPal
 '      cancelURL:          the page where buyers return to when they cancel the payment review on PayPal
 '      shipToName:     the Ship to name entered on the merchant's site
 '      shipToStreet:       the Ship to Street entered on the merchant's site
 '      shipToCity:         the Ship to City entered on the merchant's site
 '      shipToState:        the Ship to State entered on the merchant's site
 '      shipToCountryCode:  the Code for Ship to Country entered on the merchant's site
 '      shipToZip:          the Ship to ZipCode entered on the merchant's site
 '      shipToStreet2:      the Ship to Street2 entered on the merchant's site
 '      phoneNum:           the phoneNum  entered on the merchant's site
 '--------------------------------------------------------------------------------------------------------------------------------------------
 */
function CallSetExpressCheckout( $paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL, $tag, $solutionType, $landingPage,
$shipToName, $shipToStreet, $shipToCity, $shipToState, $shipToCountryCode, $shipToZip, $shipToStreet2, $phoneNum)
{
    //------------------------------------------------------------------------------------------------------------------------------------
    // Construct the parameter string that describes the SetExpressCheckout API call in the shortcut implementation

    //declaring of global variables
    global $conf, $langs;
    global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;
    global $sBNCode;

    $nvpstr="&AMT=". urlencode($paymentAmount);
    $nvpstr = $nvpstr . "&PAYMENTACTION=" . urlencode($paymentType);
    $nvpstr = $nvpstr . "&RETURNURL=" . urlencode($returnURL);
    $nvpstr = $nvpstr . "&CANCELURL=" . urlencode($cancelURL);
    $nvpstr = $nvpstr . "&CURRENCYCODE=" . urlencode($currencyCodeType);
    $nvpstr = $nvpstr . "&ADDROVERRIDE=1";
    //$nvpstr = $nvpstr . "&ALLOWNOTE=0";
    $nvpstr = $nvpstr . "&SHIPTONAME=" . urlencode($shipToName);
    $nvpstr = $nvpstr . "&SHIPTOSTREET=" . urlencode($shipToStreet);
    $nvpstr = $nvpstr . "&SHIPTOSTREET2=" . urlencode($shipToStreet2);
    $nvpstr = $nvpstr . "&SHIPTOCITY=" . urlencode($shipToCity);
    $nvpstr = $nvpstr . "&SHIPTOSTATE=" . urlencode($shipToState);
    $nvpstr = $nvpstr . "&SHIPTOCOUNTRYCODE=" . urlencode($shipToCountryCode);
    $nvpstr = $nvpstr . "&SHIPTOZIP=" . urlencode($shipToZip);
    $nvpstr = $nvpstr . "&PHONENUM=" . urlencode($phoneNum);
    $nvpstr = $nvpstr . "&SOLUTIONTYPE=" . urlencode($solutionType);
    $nvpstr = $nvpstr . "&LANDINGPAGE=" . urlencode($landingPage);
    //$nvpstr = $nvpstr . "&CUSTOMERSERVICENUMBER=" . urlencode($tag);
    $nvpstr = $nvpstr . "&INVNUM=" . urlencode($tag);



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
        $_SESSION['ipaddress']=$_SERVER['REMOTE_ADDR '];  // Payer ip
    }

    return $resArray;
}

/*
 '-------------------------------------------------------------------------------------------
 ' Purpose:     Prepares the parameters for the GetExpressCheckoutDetails API Call.
 '
 ' Inputs:
 '      None
 ' Returns:
 '      The NVP Collection object of the GetExpressCheckoutDetails Call Response.
 '-------------------------------------------------------------------------------------------
 */
function GetDetails( $token )
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
    global $sBNCode;

    //'---------------------------------------------------------------------------
    //' Build a second API request to PayPal, using the token as the
    //'  ID to get the details on the payment authorization
    //'---------------------------------------------------------------------------
    $nvpstr="&TOKEN=" . $token;

    //'---------------------------------------------------------------------------
    //' Make the API call and store the results in an array.
    //' If the call was a success, show the authorization details, and provide
    //'     an action to complete the payment.
    //' If failed, show the error
    //'---------------------------------------------------------------------------
    $resArray=hash_call("GetExpressCheckoutDetails",$nvpstr);
    $ack = strtoupper($resArray["ACK"]);
    if($ack == "SUCCESS" || $ack=="SUCCESSWITHWARNING")
    {
        $_SESSION['payer_id'] = $resArray['PAYERID'];
    }
    return $resArray;
}

/*
 '-------------------------------------------------------------------------------------------------------------------------------------------
 ' Purpose:     Validate payment
 '--------------------------------------------------------------------------------------------------------------------------------------------
 */
function ConfirmPayment( $token, $paymentType, $currencyCodeType, $payerID, $ipaddress, $FinalPaymentAmt, $tag )
{
    /* Gather the information to make the final call to
     finalize the PayPal payment.  The variable nvpstr
     holds the name value pairs
     */

    //declaring of global variables
    global $conf, $langs;
    global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;
    global $sBNCode;

    $nvpstr  = '&TOKEN=' . urlencode($token) . '&PAYERID=' . urlencode($payerID) . '&PAYMENTACTION=' . urlencode($paymentType) . '&AMT=' . urlencode($FinalPaymentAmt);
    $nvpstr .= '&CURRENCYCODE=' . urlencode($currencyCodeType) . '&IPADDRESS=' . urlencode($ipaddress);
    //$nvpstr .= '&CUSTOM=' . urlencode($tag);
    $nvpstr .= '&INVNUM=' . urlencode($tag);

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
 ' Purpose:     This function makes a DoDirectPayment API call
 '
 ' Inputs:
 '      paymentType:        paymentType has to be one of the following values: Sale or Order or Authorization
 '      paymentAmount:      total value of the shopping cart
 '      currencyCode:       currency code value the PayPal API
 '      firstName:          first name as it appears on credit card
 '      lastName:           last name as it appears on credit card
 '      street:             buyer's street address line as it appears on credit card
 '      city:               buyer's city
 '      state:              buyer's state
 '      countryCode:        buyer's country code
 '      zip:                buyer's zip
 '      creditCardType:     buyer's credit card type (i.e. Visa, MasterCard ... )
 '      creditCardNumber:   buyers credit card number without any spaces, dashes or any other characters
 '      expDate:            credit card expiration date
 '      cvv2:               Card Verification Value
 '
 '-------------------------------------------------------------------------------------------
 '
 ' Returns:
 '      The NVP Collection object of the DoDirectPayment Call Response.
 '--------------------------------------------------------------------------------------------------------------------------------------------
 */

function DirectPayment( $paymentType, $paymentAmount, $creditCardType, $creditCardNumber,
$expDate, $cvv2, $firstName, $lastName, $street, $city, $state, $zip,
$countryCode, $currencyCode, $tag )
{
    //declaring of global variables
    global $conf, $langs;
    global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;
    global $sBNCode;

    //Construct the parameter string that describes DoDirectPayment
    $nvpstr = "&AMT=" . urlencode($paymentAmount);
    $nvpstr = $nvpstr . "&CURRENCYCODE=" . urlencode($currencyCode);
    $nvpstr = $nvpstr . "&PAYMENTACTION=" . urlencode($paymentType);
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
    $nvpstr = $nvpstr . "&IPADDRESS=" . $_SERVER['REMOTE_ADDR'];
    $nvpstr = $nvpstr . "&INVNUM=" . urlencode($tag);

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
    global $conf, $langs;
    global $API_Endpoint, $API_Url, $API_version, $USE_PROXY, $PROXY_HOST, $PROXY_PORT;
    global $PAYPAL_API_USER, $PAYPAL_API_PASSWORD, $PAYPAL_API_SIGNATURE;
    global $sBNCode;

    dol_syslog("Paypal API endpoint ".$API_Endpoint);

    //setting the curl parameters.
    $ch = curl_init();

    /*print $API_Endpoint."-".$API_version."-".$PAYPAL_API_USER."-".$PAYPAL_API_PASSWORD."-".$PAYPAL_API_SIGNATURE."<br>";
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
    if($USE_PROXY) curl_setopt ($ch, CURLOPT_PROXY, $PROXY_HOST. ":" . $PROXY_PORT);

    //NVPRequest for submitting to server
    $nvpreq ="METHOD=" . urlencode($methodName) . "&VERSION=" . urlencode($API_version) . "&PWD=" . urlencode($PAYPAL_API_PASSWORD) . "&USER=" . urlencode($PAYPAL_API_USER) . "&SIGNATURE=" . urlencode($PAYPAL_API_SIGNATURE) . $nvpStr . "&BUTTONSOURCE=" . urlencode($sBNCode);
    $nvpreq.="&LOCALE=".strtoupper($langs->getDefaultLang(1));
    //$nvpreq.="&BRANDNAME=".urlencode();       // Override merchant name
    //$nvpreq.="&NOTIFYURL=".urlencode();       // For Instant Payment Notification url


    dol_syslog("Paypal API Request nvpreq=".$nvpreq);

    //setting the nvpreq as POST FIELD to curl
    curl_setopt($ch, CURLOPT_POSTFIELDS, $nvpreq);

    //getting response from server
    $response = curl_exec($ch);

    $nvpReqArray=deformatNVP($nvpreq);
    $_SESSION['nvpReqArray']=$nvpReqArray;

    //convrting NVPResponse to an Associative Array
    dol_syslog("Paypal API Response nvpresp=".$response);
    $nvpResArray=deformatNVP($response);

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