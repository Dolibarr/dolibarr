<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur <eldy@uers.sourceforge.net>
 * Copyright (C) 2011	   Juanjo Menent	   <jmenent@2byte.es>
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

$username = GETPOST("txtUsername");
$password = GETPOST("pwdPassword");
$thirdpartyid = (GETPOST('socid','int')!='')?GETPOST('socid','int'):$conf->global->CASHDESK_ID_THIRDPARTY;
$warehouseid = (GETPOST("warehouseid")!='')?GETPOST("warehouseid"):$conf->global->CASHDESK_ID_WAREHOUSE;

// Check username
if (empty($username))
{
	$retour=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Login"));
	header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid);
	exit;
}
// Check third party id
if (! ($thirdpartyid > 0))
{
    $retour=$langs->trans("ErrorFieldRequired",$langs->transnoentities("CashDeskThirdPartyForSell"));
    header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid);
    exit;
}

// If we setup stock module to ask movement on invoices, we must not allow access if required setup not finished.
if ($conf->stock->enabled && $conf->global->STOCK_CALCULATE_ON_BILL &&  ! ($warehouseid > 0))
{
	$retour=$langs->trans("CashDeskSetupStock");
	header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid);
	exit;
}

if (! empty($_POST['txtUsername']) && $conf->banque->enabled && (empty($conf_fkaccount_cash) || empty($conf_fkaccount_cheque) || empty($conf_fkaccount_cb)))
{
	$langs->load("errors");
	$retour=$langs->trans("ErrorModuleSetupNotComplete");
    header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid);
    exit;
}



// Check password
$auth = new Auth($db);
$retour = $auth->verif($username, $password);

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

		header('Location: '.DOL_URL_ROOT.'/cashdesk/affIndex.php?menu=facturation&id=NOUV');
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
	header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid);
	exit;
}

?>