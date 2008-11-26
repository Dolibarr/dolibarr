<?php
/* Copyright (C) 2002-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/compta/bank/bankid_fr.php
 *  \ingroup    banque
 *  \brief      Fiche cr�ation compte bancaire
 *  \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");

$langs->load("banks");

if (!$user->admin && !$user->rights->banque)
accessforbidden();

/*
 * Actions
 */
if ($_POST["action"] == 'update' && ! $_POST["cancel"])
{
	// Modification
	$account = new Account($db, $_POST["id"]);
	$account->fetch($_POST["id"]);

	$account->bank            = trim($_POST["bank"]);
	$account->code_banque     = trim($_POST["code_banque"]);
	$account->code_guichet    = trim($_POST["code_guichet"]);
	$account->number          = trim($_POST["number"]);
	$account->cle_rib         = trim($_POST["cle_rib"]);
	$account->bic             = trim($_POST["bic"]);
	$account->iban            = trim($_POST["iban_prefix"]);	
	$account->iban_prefix     = trim($_POST["iban_prefix"]);	// deprecated
	$account->domiciliation   = trim($_POST["domiciliation"]);
	$account->proprio 	      = trim($_POST["proprio"]);
	$account->adresse_proprio = trim($_POST["adresse_proprio"]);

	if ($account->id)
	{
		$result = $account->update_bban($user);
		if ($result >= 0)
		{
			$_GET["id"]=$_POST["id"];   // Force chargement page en mode visu
		}
		else
		{
			$message='<div class="error">'.$account->error().'</div>';
			$_GET["action"]='edit';     // Force chargement page edition
		}
	}
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->banque->configurer)
{
	// Modification
	$account = new Account($db, $_GET["id"]);
	$account->delete($_GET["id"]);

	header("Location: ".DOL_URL_ROOT."/compta/bank/index.php");
	exit;
}


/*
 * View
 */

llxHeader();

$form = new Form($db);

/* ************************************************************************** */
/*                                                                            */
/* Affichage page en mode cr�ation                                            */
/*                                                                            */
/* ************************************************************************** */

if (($_GET["id"] || $_GET["ref"]) && $_GET["action"] != 'edit')
{
	$account = new Account($db);
	if ($_GET["id"])
	{
		$result=$account->fetch($_GET["id"]);
	}
	if ($_GET["ref"])
	{
		$result=$account->fetch(0,$_GET["ref"]);
		$_GET["id"]=$account->id;
	}

	/*
		* Affichage onglets
		*/
	// Onglets
	$head=bank_prepare_head($account);
	dolibarr_fiche_head($head, 'bankid', $langs->trans("FinancialAccount"));

	/*
		* Confirmation de la suppression
		*/
	if ($_GET["action"] == 'delete')
	{
		$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$account->id,$langs->trans("DeleteAccount"),$langs->trans("ConfirmDeleteAccount"),"confirm_delete");
		print '<br />';
	}

	// Check BBAN
	if (! checkBanForAccount($account)) 
	{
		print '<div class="warning">'.$langs->trans("RIBControlError").'</div><br>';
	}
	
	
	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">';
	print $form->showrefnav($account,'ref','',1,'ref');
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
	print '<td colspan="3">'.$account->label.'</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AccountType").'</td>';
	print '<td colspan="3">'.$account->type_lib[$account->type].'</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
	print '<td colspan="3">'.$account->getLibStatut(4).'</td></tr>';

	if ($account->type == 0 || $account->type == 1)
	{
		print '<tr><td valign="top">'.$langs->trans("BankName").'</td>';
		print '<td colspan="3">'.$account->bank.'</td></tr>';

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
		print '<td colspan="3">'.$account->iban_prefix.'</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
		print '<td colspan="3">'.$account->bic.'</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="3">';
		print nl2br($account->domiciliation);
		print "</td></tr>\n";

		print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td><td colspan="3">';
		print $account->proprio;
		print "</td></tr>\n";

		print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="3">';
		print nl2br($account->adresse_proprio);
		print "</td></tr>\n";
	}

	print '</table>';

	print "\n</div>\n";


	/*
		* Barre d'actions
		*
		*/
	print '<div class="tabsAction">';

	if ($user->rights->banque->configurer)
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$account->id.'">'.$langs->trans("Modify").'</a>';
	}

	print '</div>';

}

/* ************************************************************************** */
/*                                                                            */
/* Edition                                                                    */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["id"] && $_GET["action"] == 'edit' && $user->rights->banque->configurer)
{
	$account = new Account($db, $_GET["id"]);
	$account->fetch($_GET["id"]);

	print_titre($langs->trans("EditFinancialAccount"));
	print "<br>";

	if ($message) { print "$message<br>\n"; }

	print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$account->id.'" method="post">';
	print '<input type="hidden" name="action" value="update">';
	print '<input type="hidden" name="id" value="'.$_GET["id"].'">'."\n\n";

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td valign="top" width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3">'.$account->ref;
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
	print '<td colspan="3">'.$account->label;
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AccountType").'</td>';
	print '<td colspan="3">'.$account->type_lib[$account->type];
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
	print '<td colspan="3">'.$account->getLibStatut(4);
	print '</td></tr>';

	if ($account->type == 0 || $account->type == 1)
	{
		// If bank account
		print '<tr><td valign="top">'.$langs->trans("Bank").'</td>';
		print '<td colspan="3"><input size="30" type="text" class="flat" name="bank" value="'.$account->bank.'"></td>';
		print '</tr>';

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
		print '<td><input size="18" type="text" class="flat" name="number" value="'.$account->number.'"></td>';
		print '</tr>';

		if ($account->useDetailedBBAN())
		{
			print '<td>'.$langs->trans("BankAccountNumberKey").'</td>';
			print '<td><input size="3" type="text" class="flat" name="cle_rib" value="'.$account->cle_rib.'"></td>';
			print '</tr>';
		}

		// IBAN
		print '<tr><td valign="top">'.$langs->trans("IBAN").'</td>';
		print '<td colspan="3"><input size="26" type="text" class="flat" name="iban_prefix" value="'.$account->iban_prefix.'"></td></tr>';

		print '<tr><td valign="top">'.$langs->trans("BIC").'</td>';
		print '<td colspan="3"><input size="26" type="text" class="flat" name="bic" value="'.$account->bic.'"></td></tr>';

		print '<tr><td valign="top">'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="3">';
		print "<textarea class=\"flat\" name=\"domiciliation\" rows=\"2\" cols=\"40\">";
		print $account->domiciliation;
		print "</textarea></td></tr>";

		print '<tr><td valign="top">'.$langs->trans("BankAccountOwner").'</td>';
		print '<td colspan="3"><input size="30" type="text" class="flat" name="proprio" value="'.$account->proprio.'">';
		print '</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="3">';
		print "<textarea class=\"flat\" name=\"adresse_proprio\" rows=\"2\" cols=\"40\">";
		print $account->adresse_proprio;
		print "</textarea></td></tr>";

	}

	print '<tr><td align="center" colspan="4"><input value="'.$langs->trans("Save").'" type="submit" class="button">';
	print ' &nbsp; <input name="cancel" value="'.$langs->trans("Cancel").'" type="submit" class="button">';
	print '</td></tr>';
	print '</table>';

	print '</form>';
}



$db->close();

llxFooter('$Date$ - $Revision$');
?>
