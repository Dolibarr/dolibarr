<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	    \file       htdocs/societe/rib.php
 *      \ingroup    societe
 *		\brief      BAN tab for companies
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");
require_once DOL_DOCUMENT_ROOT."/companybankaccount.class.php";

$langs->load("companies");
$langs->load("banks");
$langs->load("bills");

// Security check
$socid = isset($_GET["socid"])?$_GET["socid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'societe','','');

$soc = new Societe($db);
$soc->id = $_GET["socid"];
$soc->fetch($_GET["socid"]);


/*
 *	Actions
 */

if ($_POST["action"] == 'update' && ! $_POST["cancel"])
{
	// Modification
	$account = new CompanyBankAccount($db);

	$account->socid           = $soc->id;

	$account->bank            = $_POST["bank"];
	$account->label           = $_POST["label"];
	$account->courant         = $_POST["courant"];
	$account->clos            = $_POST["clos"];
	$account->code_banque     = $_POST["code_banque"];
	$account->code_guichet    = $_POST["code_guichet"];
	$account->number          = $_POST["number"];
	$account->cle_rib         = $_POST["cle_rib"];
	$account->bic             = $_POST["bic"];
	$account->iban_prefix     = $_POST["iban_prefix"];
	$account->domiciliation   = $_POST["domiciliation"];
	$account->proprio 	      = $_POST["proprio"];
	$account->adresse_proprio = $_POST["adresse_proprio"];

	$result = $account->update($user);
	if (! $result)
	{
		$message=$account->error();
		$_GET["action"]='edit';     // Force chargement page edition
	}
	else
	{
		$_GET["id"]=$_POST["id"];   // Force chargement page en mode visu
	}
}



/*
 *	View
 */

llxHeader();

$head=societe_prepare_head2($soc);

dol_fiche_head($head, 'rib', $langs->trans("ThirdParty"),0,'company');

$account = new CompanyBankAccount($db);
$account->socid=$soc->id;
$account->fetch($soc->id);


/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["socid"] && $_GET["action"] != 'edit')
{
	// Check BBAN
	if (! checkBanForAccount($account))
	{
		print '<div class="warning">'.$langs->trans("RIBControlError").'</div><br>';
	}

	print '<table class="border" width="100%">';

	print '<tr><td valign="top">'.$langs->trans("Bank").'</td>';
	print '<td colspan="4">'.$account->bank.'</td></tr>';

	if ($account->useDetailedBBAN())
	{
		print '<tr><td>'.$langs->trans("BankCode").'</td>';
		print '<td colspan="3">'.$account->code_banque.'</td>';
		print '</tr>';

		print '<tr><td>'.$langs->trans("DeskCode").'</td>';
		print '<td colspan="3">'.$account->code_guichet.'</td>';
		print '</tr>';
	}

	print '<tr><td>'.$langs->trans("BankAccountNumber").'</td>';
	print '<td colspan="3">'.$account->number.'</td>';
	print '</tr>';

	if ($account->useDetailedBBAN())
	{
		print '<tr><td>'.$langs->trans("BankAccountNumberKey").'</td>';
		print '<td colspan="3">'.$account->cle_rib.'</td>';
		print '</tr>';
	}

	print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
	print '<td colspan="4">'.$account->iban_prefix.'</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
	print '<td colspan="4">'.$account->bic.'</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
	print $account->domiciliation;
	print "</td></tr>\n";

	print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td><td colspan="4">';
	print $account->proprio;
	print "</td></tr>\n";

	print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
	print $account->adresse_proprio;
	print "</td></tr>\n";

	print '</table>';

	print '</div>';



	/*
	 * Barre d'actions
	 *
	 */
	print '<div class="tabsAction">';

	if ($user->rights->societe->creer)
	{
		print '<a class="butAction" href="rib.php?socid='.$soc->id.'&amp;action=edit">'.$langs->trans("Modify").'</a>';
	}

	print '</div>';

}

/* ************************************************************************** */
/*                                                                            */
/* Edition                                                                    */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["socid"] && $_GET["action"] == 'edit' && $user->rights->societe->creer)
{

	$form = new Form($db);

	if ($message) { print "$message<br><br>\n"; }

	print '<form action="rib.php?socid='.$soc->id.'" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$_GET["id"].'">';

	print '<table class="border" width="100%">';

	print '<tr><td valign="top">'.$langs->trans("Bank").'</td>';
	print '<td colspan="4"><input size="30" type="text" name="bank" value="'.$account->bank.'"></td></tr>';

	// BBAN
	if ($account->useDetailedBBAN())
	{
		print '<tr><td>'.$langs->trans("BankCode").'</td>';
		print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
		print '</tr>';

		print '<tr><td>'.$langs->trans("DeskCode").'</td>';
		print '<td><input size="8" type="text" class="flat" name="code_guichet" value="'.$account->code_guichet.'"></td>';
		print '</tr>';
	}

	print '<td>'.$langs->trans("BankAccountNumber").'</td>';
	print '<td><input size="15" type="text" class="flat" name="number" value="'.$account->number.'"></td>';
	print '</tr>';

	if ($account->useDetailedBBAN())
	{
		print '<td>'.$langs->trans("BankAccountNumberKey").'</td>';
		print '<td><input size="3" type="text" class="flat" name="cle_rib" value="'.$account->cle_rib.'"></td>';
		print '</tr>';
	}

	// IBAN
	print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
	print '<td colspan="4"><input size="30" type="text" name="iban_prefix" value="'.$account->iban_prefix.'"></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
	print '<td colspan="4"><input size="12" type="text" name="bic" value="'.$account->bic.'"></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="4">';
	print "<textarea name=\"domiciliation\" rows=\"4\" cols=\"40\">";
	print $account->domiciliation;
	print "</textarea></td></tr>";

	print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td>';
	print '<td colspan="4"><input size="30" type="text" name="proprio" value="'.$account->proprio.'"></td></tr>';
	print "</td></tr>\n";

	print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="4">';
	print "<textarea name=\"adresse_proprio\" rows=\"4\" cols=\"40\">";
	print $account->adresse_proprio;
	print "</textarea></td></tr>";

	print '<tr><td align="center" colspan="5"><input class="button" value="'.$langs->trans("Modify").'" type="submit">';
	print ' &nbsp; <input name="cancel" class="button" value="'.$langs->trans("Cancel").'" type="submit">';
	print '</td></tr>';

	print '</form>';
	print '</table>';
}



$db->close();


llxFooter('$Date$ - $Revision$');
?>
