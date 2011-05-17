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
include_once(DOL_DOCUMENT_ROOT."/societe/class/societe.class.php");
require_once(DOL_DOCUMENT_ROOT.'/paypal/lib/paypal.lib.php');
require_once(DOL_DOCUMENT_ROOT."/paypal/lib/paypalfunctions.lib.php");

$langs->load('main');
$langs->load('users');
$langs->load('companies');


/*
 * View
 */

// Ajout directives pour resoudre bug IE
//header('Cache-Control: Public, must-revalidate');
//header('Pragma: public');

//top_htmlhead("", "", 1);  // Replaced with top_httphead. An ajax page does not need html header.
top_httphead();

//echo '<!-- Ajax page called with url '.$_SERVER["PHP_SELF"].'?'.$_SERVER["QUERY_STRING"].' -->'."\n";

//echo '<body class="nocellnopadd">'."\n";

dol_syslog(join(',',$_GET));

if (isset($_GET['action']) && ! empty($_GET['action']) && ( (isset($_GET['element']) && ! empty($_GET['element'])) || (isset($_GET['transaction_id']) && ! empty($_GET['transaction_id'])) ) )
{
	if ($_GET['action'] == 'create')
	{
		$soc = new Societe($db);
		$socid = $soc->fetchObjectFromImportKey($soc->table_element,$object['PAYERID']);
		if ($socid < 0)
		{
			// Create customer and return rowid
		}
		
		// Create element (order or bill)
		
		foreach ($_SESSION[$_GET['transaction_id']] as $key => $value)
		{
			echo $key.': '.$value.'<br />';
		}
		
	}
	else if ($_GET['action'] == 'showdetails')
	{
		// For optimization
		if (! isset($_SESSION[$_GET['transaction_id']]))
		{
			$_SESSION[$_GET['transaction_id']] = GetTransactionDetails($_GET['transaction_id']);
		}
		
		$var=true;
		
		echo '<table style="noboardernopading" width="100%">';
		echo '<tr class="liste_titre">';
		echo '<td colspan="2">'.$langs->trans('CustomerDetails').'</td>';
		echo '</tr>';
		
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('LastName').': </td><td>'.$_SESSION[$_GET['transaction_id']]['LASTNAME'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('FirstName').': </td><td>'.$_SESSION[$_GET['transaction_id']]['FIRSTNAME'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Address').': </td><td>'.$_SESSION[$_GET['transaction_id']]['SHIPTOSTREET'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Zip').' / '.$langs->trans('Town').': </td><td>'.$_SESSION[$_GET['transaction_id']]['SHIPTOZIP'].' '.$_SESSION[$_GET['transaction_id']]['SHIPTOCITY'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Country').': </td><td>'.$_SESSION[$_GET['transaction_id']]['SHIPTOCOUNTRYNAME'].'</td></tr>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Email').': </td><td>'.$_SESSION[$_GET['transaction_id']]['EMAIL'].'</td>';
		$var=!$var;
		echo '<tr '.$bc[$var].'><td>'.$langs->trans('Date').': </td><td>'.dol_print_date(dol_stringtotime($_SESSION[$_GET['transaction_id']]['ORDERTIME']),'dayhour').'</td>';
		
		echo '</table>';
		
		$i=0;
		
		echo '<table style="noboardernopading" width="100%">';
		
		echo '<tr class="liste_titre">';
		echo '<td>'.$langs->trans('Ref').'</td>';
		echo '<td>'.$langs->trans('Label').'</td>';
		echo '<td>'.$langs->trans('Qty').'</td>';
		echo '</tr>';
		
		while (isset($_SESSION[$_GET['transaction_id']]["L_NAME".$i]))
		{
			$var=!$var;
			
			echo '<tr '.$bc[$var].'>';
			echo '<td>'.$_SESSION[$_GET['transaction_id']]["L_NUMBER".$i].'</td>';
			echo '<td>'.$_SESSION[$_GET['transaction_id']]["L_NAME".$i].'</td>';
			echo '<td>'.$_SESSION[$_GET['transaction_id']]["L_QTY".$i].'</td>';
			echo '</tr>';
			
			$i++;
		}
		
		echo '</table>';
/*		
		echo '<br />';
		
		foreach ($_SESSION[$_GET['transaction_id']] as $key => $value)
		{
			echo $key.': '.$value.'<br />';
		}
*/
	}
}

//echo "</body>";
//echo "</html>";
?>