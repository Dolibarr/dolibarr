<?php

require_once ("paypalfunctions.php");
// ==================================
// PayPal Express Checkout Module
// ==================================


//'------------------------------------
//' The paymentAmount is the total value of
//' the shopping cart, that was set
//' earlier in a session variable
//' by the shopping cart page
//'------------------------------------
$paymentAmount = $_SESSION["Payment_Amount"];

//'------------------------------------
//' The currencyCodeType and paymentType
//' are set to the selections made on the Integration Assistant
//'------------------------------------
$currencyCodeType = "EUR";
$paymentType = "Sale";

//'------------------------------------
//' The returnURL is the location where buyers return to when a
//' payment has been succesfully authorized.
//'
//' This is set to the value entered on the Integration Assistant
//'------------------------------------
$returnURL = $PAYPAL_API_OK;

//'------------------------------------
//' The cancelURL is the location buyers are sent to when they hit the
//' cancel button during authorization of payment during the PayPal flow
//'
//' This is set to the value entered on the Integration Assistant
//'------------------------------------
$cancelURL = $PAYPAL_API_KO;

//'------------------------------------
//' Calls the SetExpressCheckout API call
//'
//'-------------------------------------------------

if (empty($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY)) $conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY='integral';

// For payment with Paypal only
if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'paypalonly')
{
	$resArray = CallShortcutExpressCheckout ($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL);
}
// For payment with Credit card or Paypal
if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'integral')
{
	$resArray = CallMarkExpressCheckout ($paymentAmount, $currencyCodeType, $paymentType, $returnURL, $cancelURL);
}
// For direct payment with credit card
if ($conf->global->PAYPAL_API_INTEGRAL_OR_PAYPALONLY == 'cconly')
{
	//$resArray = DirectPayment (...);
}

$ack = strtoupper($resArray["ACK"]);
if($ack=="SUCCESS" || $ack=="SUCCESSWITHWARNING")
{
	RedirectToPayPal ( $resArray["TOKEN"] );
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
?>