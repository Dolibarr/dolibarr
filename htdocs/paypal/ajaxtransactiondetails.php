<?php
/* Copyright (C) 2011 Regis Houssin  <regis@dolibarr.fr>
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

/**
 *       \file       htdocs/paypal/ajaxtransactiondetails.php
 *       \brief      File to return Ajax response on paypal transaction details
 *       \version    $Id$
 */

if (! defined('NOTOKENRENEWAL')) define('NOTOKENRENEWAL',1); // Disables token renewal
if (! defined('NOREQUIREMENU'))  define('NOREQUIREMENU','1');
if (! defined('NOREQUIREHTML'))  define('NOREQUIREHTML','1');
if (! defined('NOREQUIREAJAX'))  define('NOREQUIREAJAX','1');
if (! defined('NOREQUIRESOC'))   define('NOREQUIRESOC','1');
if (! defined('NOCSRFCHECK'))    define('NOCSRFCHECK','1');

require('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php');
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypalfunctions.lib.php");


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//print '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

//print '<body class="nocellnopadd">'."\n";

dol_syslog(join(',',$_GET));

if (isset($_GET['transaction_id']) && ! empty($_GET['transaction_id']))
{
	$resArray = GetTransactionDetails($_GET['transaction_id']);
	
	foreach($resArray as $key => $value) {
		echo $key.': '.$value.'<br />';
	}
	
	//print json_encode($resArray);
}

//print "</body>";
//print "</html>";
?>