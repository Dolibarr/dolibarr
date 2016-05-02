<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier    <jeremie.o@laposte.net>
 * Copyright (C) 2008-2010 Laurent Destailleur <eldy@uers.sourceforge.net>
 * Copyright (C) 2011	   Juanjo Menent	   <jmenent@2byte.es>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * This page is called after submission of login page.
 * We set here login choices into session.
 */

/**
 *	\file       htdocs/cashdesk/index_verif.php
 *	\ingroup    cashdesk
 *	\brief      index_verif.php
 */

include '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/include/environnement.php';
require_once DOL_DOCUMENT_ROOT.'/cashdesk/class/Auth.class.php';

$langs->load("main");
$langs->load("admin");
$langs->load("cashdesk");

$username = GETPOST("txtUsername");
$password = GETPOST("pwdPassword");
$thirdpartyid = (GETPOST('socid','int') > 0)?GETPOST('socid','int'):$conf->global->CASHDESK_ID_THIRDPARTY;
$warehouseid = (GETPOST("warehouseid") > 0)?GETPOST("warehouseid",'int'):$conf->global->CASHDESK_ID_WAREHOUSE;
$bankid_cash = (GETPOST("CASHDESK_ID_BANKACCOUNT_CASH") > 0)?GETPOST("CASHDESK_ID_BANKACCOUNT_CASH",'int'):$conf->global->CASHDESK_ID_BANKACCOUNT_CASH;
$bankid_cheque = (GETPOST("CASHDESK_ID_BANKACCOUNT_CHEQUE") > 0)?GETPOST("CASHDESK_ID_BANKACCOUNT_CHEQUE",'int'):$conf->global->CASHDESK_ID_BANKACCOUNT_CHEQUE;
$bankid_cb = (GETPOST("CASHDESK_ID_BANKACCOUNT_CB") > 0)?GETPOST("CASHDESK_ID_BANKACCOUNT_CB",'int'):$conf->global->CASHDESK_ID_BANKACCOUNT_CB;

// Check username
if (empty($username))
{
	$retour=$langs->trans("ErrorFieldRequired",$langs->transnoentities("Login"));
	header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid.'&bankid_cash='.$bankid_cash.'&bankid_cheque='.$bankid_cheque.'&bankid_cb='.$bankid_cb);
	exit;
}
// Check third party id
if (! ($thirdpartyid > 0))
{
    $retour=$langs->trans("ErrorFieldRequired",$langs->transnoentities("CashDeskThirdPartyForSell"));
    header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid.'&bankid_cash='.$bankid_cash.'&bankid_cheque='.$bankid_cheque.'&bankid_cb='.$bankid_cb);
    exit;
}

// If we setup stock module to ask movement on invoices, we must not allow access if required setup not finished.
if (! empty($conf->stock->enabled) && empty($conf->global->CASHDESK_NO_DECREASE_STOCK) && ! ($warehouseid > 0))
{
	$retour=$langs->trans("CashDeskYouDidNotDisableStockDecease");
	header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid.'&bankid_cash='.$bankid_cash.'&bankid_cheque='.$bankid_cheque.'&bankid_cb='.$bankid_cb);
	exit;
}

// If stock decrease on bill validation, check user has stock edit permissions
if (! empty($conf->stock->enabled) && empty($conf->global->CASHDESK_NO_DECREASE_STOCK) && ! empty($username))
{
	$testuser=new User($db);
	$testuser->fetch(0,$username);
	$testuser->getrights('stock');
	if (empty($testuser->rights->stock->creer))
	{
		$retour=$langs->trans("UserNeedPermissionToEditStockToUsePos");
		header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid.'&bankid_cash='.$bankid_cash.'&bankid_cheque='.$bankid_cheque.'&bankid_cb='.$bankid_cb);
		exit;
	}
}


/*
if (! empty($_POST['txtUsername']) && ! empty($conf->banque->enabled) && (empty($conf_fkaccount_cash) && empty($conf_fkaccount_cheque) && empty($conf_fkaccount_cb)))
{
	$langs->load("errors");
	$retour=$langs->trans("ErrorModuleSetupNotComplete");
    header('Location: '.DOL_URL_ROOT.'/cashdesk/index.php?err='.urlencode($retour).'&user='.$username.'&socid='.$thirdpartyid.'&warehouseid='.$warehouseid);
    exit;
}
*/

// Check password
$auth = new Auth($db);
$retour = $auth->verif($username, $password);

if ( $retour >= 0 )
{
	$return=array();

	$sql = "SELECT rowid, lastname, firstname";
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
		$_SESSION['lastname'] = $tab['lastname'];
		$_SESSION['firstname'] = $tab['firstname'];
		$_SESSION['CASHDESK_ID_THIRDPARTY'] = ($thirdpartyid > 0 ? $thirdpartyid : '');
        $_SESSION['CASHDESK_ID_WAREHOUSE'] = ($warehouseid > 0 ? $warehouseid : '');
        
        $_SESSION['CASHDESK_ID_BANKACCOUNT_CASH'] = ($bankid_cash > 0 ? $bankid_cash : '');
        $_SESSION['CASHDESK_ID_BANKACCOUNT_CHEQUE'] = ($bankid_cheque > 0 ? $bankid_cheque : '');
        $_SESSION['CASHDESK_ID_BANKACCOUNT_CB'] = ($bankid_cb > 0 ? $bankid_cb : '');
        //var_dump($_SESSION);exit;

		header('Location: '.DOL_URL_ROOT.'/cashdesk/affIndex.php?menutpl=facturation&id=NOUV');
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

