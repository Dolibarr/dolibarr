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

/**	    \file       htdocs/paypal/lib/paypalfunctions.lib.php
 *		\ingroup    paypal
 *		\brief      Page with Paypal init var.
 *		\version    $Id$
 */

if (session_id() == "") session_start();


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

// Proxy
$PROXY_HOST = '127.0.0.1';
$PROXY_PORT = '808';
$USE_PROXY = false;

// BN Code  is only applicable for partners
$sBNCode = "PP-ECWizard";

?>