<?php
/* Copyright (C) 2010-2011 Laurent Destailleur  <eldy@users.sourceforge.org>
 * Copyright (C) 2011      Regis Houssin  		<regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * \file       htdocs/paypal/lib/paypalfunctions.lib.php
 * \ingroup    paypal
 * \brief      Page with Paypal init var.
 */

if (session_id() == "")
{
    session_start();
    if (ini_get('register_globals'))    // To solve bug in using $_SESSION
    {
        foreach ($_SESSION as $key=>$value)
        {
            if (isset($GLOBALS[$key])) unset($GLOBALS[$key]);
        }
    }
}

// ==================================
// PayPal Express Checkout Module
// ==================================

$API_version="56";

/*
 ' Define the PayPal Redirect URLs.
 '  This is the URL that the buyer is first sent to do authorize payment with their paypal account
 '  change the URL depending if you are testing on the sandbox or the live PayPal site
 '
 ' For the sandbox, the URL is       https://www.sandbox.paypal.com/webscr&cmd=_express-checkout&token=
 ' For the live site, the URL is        https://www.paypal.com/webscr&cmd=_express-checkout&token=
 */
if ($conf->global->PAYPAL_API_SANDBOX)
{
    $API_Endpoint = "https://api-3t.sandbox.paypal.com/nvp";
    $API_Url = "https://www.sandbox.paypal.com/webscr?cmd=_express-checkout&token=";
}
else
{
    $API_Endpoint = "https://api-3t.paypal.com/nvp";
    $API_Url = "https://www.paypal.com/cgi-bin/webscr?cmd=_express-checkout&token=";
}

// Clean parameters
$PAYPAL_API_USER="";
if ($conf->global->PAYPAL_API_USER) $PAYPAL_API_USER=$conf->global->PAYPAL_API_USER;
$PAYPAL_API_PASSWORD="";
if ($conf->global->PAYPAL_API_PASSWORD) $PAYPAL_API_PASSWORD=$conf->global->PAYPAL_API_PASSWORD;
$PAYPAL_API_SIGNATURE="";
if ($conf->global->PAYPAL_API_SIGNATURE) $PAYPAL_API_SIGNATURE=$conf->global->PAYPAL_API_SIGNATURE;
$PAYPAL_API_SANDBOX="";
if ($conf->global->PAYPAL_API_SANDBOX) $PAYPAL_API_SANDBOX=$conf->global->PAYPAL_API_SANDBOX;

// Proxy
$PROXY_HOST = $conf->global->MAIN_PROXY_HOST;
$PROXY_PORT = $conf->global->MAIN_PROXY_PORT;
$PROXY_USER = $conf->global->MAIN_PROXY_USER;
$PROXY_PASS = $conf->global->MAIN_PROXY_PASS;
$USE_PROXY = empty($conf->global->MAIN_PROXY_USE)?false:true;

?>