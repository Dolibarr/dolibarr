<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur <eldy@uers.sourceforge.net>
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
 *
 * This page is called after submission of login page.
 * We set here login choices into session.
 */

include('../main.inc.php');
require_once(DOL_DOCUMENT_ROOT.'/cashdesk/include/environnement.php');
require_once(DOL_DOCUMENT_ROOT.'/cashdesk/class/Auth.class.php');

$langs->load("main");
$langs->load("admin");
$langs->load("cashdesk");

$username = $_POST['txtUsername'];
$password = $_POST['pwdPassword'];
$thirdpartyid = isset($_POST['socid'])?$_POST['socid']:$conf->global->CASHDESK_ID_THIRDPARTY;
$warehouseid = isset($_POST['warehouseid'])?$_POST['warehouseid']:$conf->global->CASHDESK_ID_WAREHOUSE;

$error = '';

// Check username
if (empty($username))
{
	$retour=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Login"));
	header ('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username);
	exit;
}
// Check third party id
if (! ($thirdpartyid > 0))
{
    $retour=$langs->trans("ErrorFieldRequired",$langs->transnoentities("CashDeskThirdPartyForSell"));
    header ('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username);
    exit;
}

// If we setup stock module to ask movement on invoices, we must not allow access if required setup not finished.
if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_BILL && ! ($warehouseid > 0))
{
	$retour=$langs->trans("You ask to decrease stock on invoice creation but warehouse for this is was not defined (Change stock module setup, or choose a warehouse).");
	header ('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username);
	exit;
}

if (! empty($_POST['txtUsername']) && $conf->banque->enabled && (empty($conf_fkaccount_cash) || empty($conf_fkaccount_cheque) || empty($conf_fkaccount_cb)))
{
//  $error.= '<div class="error"></div>';
    header("Location: index.php?err=".urlencode('Setup of Point of Sale module not complete. Bank account not defined').'&user='.$username);
    exit;
}



// Check password
$auth = new Auth($db);
$retour = $auth->verif ($username, $password);

if ( $retour >= 0 )
{
	$return=array();

	$sql = "SELECT rowid, name, firstname";
	$sql.= " FROM ".MAIN_DB_PREFIX."user";
	$sql.= " WHERE login = '".$username."'";
	$sql.= " AND entity IN (0,".$conf->entity.")";

	$result = $db->query($sql);
	if ($result)
	{
		$tab = $db->fetch_array($res);

		foreach ( $tab as $key => $value )
		{
			$return[$key] = $value;
		}

		$_SESSION['uid'] = $tab['rowid'];
		$_SESSION['uname'] = $username;
		$_SESSION['nom'] = $tab['name'];
		$_SESSION['prenom'] = $tab['firstname'];
		$_SESSION['CASHDESK_ID_THIRDPARTY'] = $thirdpartyid;
        $_SESSION['CASHDESK_ID_WAREHOUSE'] = $warehouseid;
		//var_dump($_SESSION);exit;

		header ('Location: '.DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=facturation&id=NOUV');
		exit;
	}
	else
	{
		dol_print_error($db);
	}

}
else
{
	$langs->load("errors");
    $langs->load("other");
	$retour=$langs->trans("ErrorBadLoginPassword");
	header ('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username);
	exit;
}

?>