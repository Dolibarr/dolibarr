<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copytight (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
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
 *	    \file       htdocs/compta/bank/fiche.php
 *      \ingroup    banque
 *		\brief      Fiche creation compte bancaire
 *		\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/bank.lib.php");

$langs->load("banks");

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$id,'bank_account','','',$fieldid);


/*
 * Actions
 */
if ($_POST["action"] == 'add')
{
    // Creation compte
    $account = new Account($db,0);

    $account->ref           = dol_sanitizeFileName(trim($_POST["ref"]));
    $account->label         = trim($_POST["label"]);
    $account->courant       = $_POST["type"];
    $account->clos          = $_POST["clos"];
    $account->rappro        = (isset($_POST["norappro"]) && $_POST["norappro"])?0:1;
    $account->url           = $_POST["url"];

    $account->account_number  = trim($_POST["account_number"]);

    $account->solde           = $_POST["solde"];
    $account->date_solde      = dol_mktime(12,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

    $account->currency_code   = trim($_POST["account_currency_code"]);
    $account->country_code    = trim($_POST["account_country_code"]);

    $account->min_allowed     = $_POST["account_min_allowed"];
    $account->min_desired     = $_POST["account_min_desired"];
    $account->comment         = trim($_POST["account_comment"]);

    if ($account->label)
    {
        $id = $account->create($user->id);
        if ($id > 0)
        {
            $_GET["id"]=$id;            // Force chargement page en mode visu
        }
        else {
            $message='<div class="error">'.$account->error().'</div>';
            $_GET["action"]='create';   // Force chargement page en mode creation
        }
    } else {
        $message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("LabelBankCashAccount")).'</div>';
        $_GET["action"]='create';       // Force chargement page en mode creation
    }
}

if ($_POST["action"] == 'update' && ! $_POST["cancel"])
{
    // Modification
    $account = new Account($db, $_POST["id"]);
    $account->fetch($_POST["id"]);

    $account->ref             = dol_string_nospecial(trim($_POST["ref"]));
    $account->label           = trim($_POST["label"]);
    $account->courant         = $_POST["type"];
    $account->clos            = $_POST["clos"];
    $account->rappro          = (isset($_POST["norappro"]) && $_POST["norappro"])?0:1;
    $account->url             = trim($_POST["url"]);

    $account->bank            = trim($_POST["bank"]);
    $account->code_banque     = trim($_POST["code_banque"]);
    $account->code_guichet    = trim($_POST["code_guichet"]);
    $account->number          = trim($_POST["number"]);
    $account->cle_rib         = trim($_POST["cle_rib"]);
    $account->bic             = trim($_POST["bic"]);
    $account->iban_prefix     = trim($_POST["iban_prefix"]);
    $account->domiciliation   = trim($_POST["domiciliation"]);

    $account->proprio 	      = trim($_POST["proprio"]);
    $account->adresse_proprio = trim($_POST["adresse_proprio"]);

    $account->account_number  = trim($_POST["account_number"]);

    $account->currency_code   = trim($_POST["account_currency_code"]);
    $account->country_code    = trim($_POST["account_country_code"]);

    $account->min_allowed     = $_POST["account_min_allowed"];
    $account->min_desired     = $_POST["account_min_desired"];
    $account->comment         = trim($_POST["account_comment"]);

    if ($account->label)
    {
        $result = $account->update($user);
        if ($result >= 0)
        {
            $_GET["id"]=$_POST["id"];   // Force chargement page en mode visu
        }
        else
        {
            $message='<div class="error">'.$account->error().'</div>';
            $_GET["action"]='edit';     // Force chargement page edition
        }
    } else {
        $message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("LabelBankCashAccount")).'</div>';
        $_GET["action"]='create';       // Force chargement page en mode creation
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
/* Affichage page en mode creation                                            */
/*                                                                            */
/* ************************************************************************** */

if ($_GET["action"] == 'create')
{
	print_fiche_titre($langs->trans("NewFinancialAccount"));

	if ($message) { print "$message<br>\n"; }

	print '<form action="'.$_SERVER["PHP_SELF"].'" name="createbankaccount" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="clos" value="0">';

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td valign="top">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3"><input size="8" type="text" class="flat" name="ref" value="'.$account->ref.'"></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("LabelBankCashAccount").'</td>';
	print '<td colspan="3"><input size="30" type="text" class="flat" name="label" value="'.$_POST["label"].'"></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("AccountType").'</td>';
	print '<td colspan="3">';
	print $form->select_type_comptes_financiers(isset($_POST["type"])?$_POST["type"]:1,"type");
	print '</td></tr>';

	// Code compta
	if ($conf->comptaexpert->enabled)
	{
		print '<tr><td valign="top">'.$langs->trans("AccountancyCode").'</td>';
		print '<td colspan="3"><input type="text" name="account_number" value="'.$account->account_number.'"></td></tr>';
	}
	else
	{
		print '<input type="hidden" name="account_number" value="'.$account->account_number.'">';
	}

	// Currency
	print '<tr><td valign="top">'.$langs->trans("Currency").'</td>';
	print '<td colspan="3">';

	$selectedcode=$account->account_currency_code;
	if (! $selectedcode) $selectedcode=$conf->monnaie;
	$form->select_currency($selectedcode, 'account_currency_code');
	//print $langs->trans("Currency".$conf->monnaie);
	//print '<input type="hidden" name="account_currency_code" value="'.$conf->monnaie.'">';

	print '</td></tr>';

	// Pays
	print '<tr><td valign="top">'.$langs->trans("Country").'</td>';
	print '<td colspan="3">';
	$selectedcode=$account->account_country_code;
	if (! $selectedcode) $selectedcode=$mysoc->pays_code;
	$form->select_pays($selectedcode, 'account_country_code');
	print '</td></tr>';

	// Web
	print '<tr><td valign="top">'.$langs->trans("Web").'</td>';
	print '<td colspan="3"><input size="50" type="text" class="flat" name="url" value="'.$_POST["url"].'"></td></tr>';

	// Comment
	print '<tr><td valign="top">'.$langs->trans("Comment").'</td>';
	print '<td colspan="3">';
   // �diteur wysiwyg
	if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_MAILING)
	{
		require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
		$doleditor=new DolEditor('account_comment',$account->comment,200,'dolibarr_notes','',false);
		$doleditor->Create();
	}
	else
	{
		print '<textarea class="flat" name="account_comment" cols="70" rows="10">';
		print dol_htmlentitiesbr_decode($account->comment).'</textarea>';
	}
	print '</td></tr>';

	// Solde
	print '<tr><td colspan="4"><b>'.$langs->trans("InitialBankBalance").'...</b></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("InitialBankBalance").'</td>';
	print '<td colspan="3"><input size="12" type="text" class="flat" name="solde" value="'.price2num($account->solde).'"></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("Date").'</td>';
	print '<td colspan="3">';
	$form->select_date(time(), 're', 0, 0, 0, 'createbankaccount');
	print '</td></tr>';

	print '<tr><td valign="top">'.$langs->trans("BalanceMinimalAllowed").'</td>';
	print '<td colspan="3"><input size="12" type="text" class="flat" name="account_min_allowed" value="'.$account->account_min_allowed.'"></td></tr>';

	print '<tr><td valign="top">'.$langs->trans("BalanceMinimalDesired").'</td>';
	print '<td colspan="3"><input size="12" type="text" class="flat" name="account_min_desired" value="'.$account->account_min_desired.'"></td></tr>';

	print '<tr><td align="center" colspan="4"><input value="'.$langs->trans("CreateAccount").'" type="submit" class="button"></td></tr>';
	print '</form>';
	print '</table>';
}
/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */
else
{
    if (($_GET["id"] || $_GET["ref"]) && $_GET["action"] != 'edit')
	{
		$account = new Account($db);
		if ($_GET["id"])
		{
			$account->fetch($_GET["id"]);
		}
		if ($_GET["ref"])
		{
			$account->fetch(0,$_GET["ref"]);
			$_GET["id"]=$account->id;
		}

		/*
		* Affichage onglets
		*/

		// Onglets
		$head=bank_prepare_head($account);
		dol_fiche_head($head, 'bankname', $langs->trans("FinancialAccount"));

		/*
		* Confirmation to delete
		*/
		if ($_GET["action"] == 'delete')
		{
			$ret=$form->form_confirm($_SERVER["PHP_SELF"].'?id='.$account->id,$langs->trans("DeleteAccount"),$langs->trans("ConfirmDeleteAccount"),"confirm_delete");
			if ($ret == 'html') print '<br>';
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

		print '<tr><td valign="top">'.$langs->trans("Conciliable").'</td>';
		print '<td colspan="3">';
		if ($account->type == 0 || $account->type == 1) print ($account->rappro==1 ? $langs->trans("Yes") : ($langs->trans("No").' ('.$langs->trans("ConciliationDisabled").')'));
		if ($account->type == 2)                        print $langs->trans("No").' ('.$langs->trans("CashAccount").')';
		print '</td></tr>';

		// Code compta
		if ($conf->comptaexpert->enabled)
		{
			print '<tr><td valign="top">'.$langs->trans("AccountancyCode").'</td>';
			print '<td colspan="3">'.$account->account_number.'</td></tr>';
		}

		// Currency
		print '<tr><td valign="top">'.$langs->trans("Currency").'</td>';
		print '<td colspan="3">';

		$selectedcode=$account->account_currency_code;
		if (! $selectedcode) $selectedcode=$conf->monnaie;
		print $langs->trans("Currency".$selectedcode);

		print '</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("BalanceMinimalAllowed").'</td>';
		print '<td colspan="3">'.$account->min_allowed.'</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("BalanceMinimalDesired").'</td>';
		print '<td colspan="3">'.$account->min_desired.'</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("Web").'</td><td colspan="3">';
		if ($account->url) print '<a href="'.$account->url.'" target="_gobank">';
		print $account->url;
		if ($account->url) print '</a>';
		print "</td></tr>\n";

		print '<tr><td valign="top">'.$langs->trans("Comment").'</td>';
		print '<td colspan="3">'.$account->comment.'</td></tr>';

		print '</table>';

		print '</div>';


		/*
		* Barre d'actions
		*
		*/
		print '<div class="tabsAction">';

		if ($user->rights->banque->configurer)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$account->id.'">'.$langs->trans("Modify").'</a>';
		}

		$canbedeleted=$account->can_be_deleted();   // Renvoi vrai si compte sans mouvements
		if ($user->rights->banque->configurer && $canbedeleted)
		{
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$account->id.'">'.$langs->trans("Delete").'</a>';
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
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="id" value="'.$_GET["id"].'">'."\n\n";

        print '<table class="border" width="100%">';

		// Ref
		print '<tr><td valign="top">'.$langs->trans("Ref").'</td>';
		print '<td colspan="3"><input size="8" type="text" class="flat" name="ref" value="'.$account->ref.'"></td></tr>';

        print '<tr><td valign="top">'.$langs->trans("Label").'</td>';
        print '<td colspan="3"><input size="30" type="text" class="flat" name="label" value="'.$account->label.'"></td></tr>';

        print '<tr><td valign="top">'.$langs->trans("AccountType").'</td>';
        print '<td colspan="3">';
		print $form->select_type_comptes_financiers($account->type,"type");
        print '</td></tr>';

        print '<tr><td valign="top">'.$langs->trans("Status").'</td>';
        print '<td colspan="3">';
        $form->select_array("clos",array(0=>$account->status[0],1=>$account->status[1]),$account->clos);
        print '</td></tr>';

        print '<tr><td valign="top">'.$langs->trans("Conciliable").'</td>';
        print '<td colspan="3">';
        if ($account->type == 0 || $account->type == 1) print '<input type="checkbox" class="flat" name="norappro" '.($account->rappro?'':'checked="true"').'"> '.$langs->trans("DisableConciliation");
        if ($account->type == 2)                        print $langs->trans("No").' ('.$langs->trans("CashAccount").')';
        print '</td></tr>';

		// Code compta
		if ($conf->comptaexpert->enabled)
		{
			print '<tr><td valign="top">'.$langs->trans("AccountancyCode").'</td>';
	        print '<td colspan="3"><input type="text" name="account_number" value="'.$account->account_number.'"></td></tr>';
		}
		else
		{
	        print '<input type="hidden" name="account_number" value="'.$account->account_number.'">';
		}

		// Currency
		print '<tr><td valign="top">'.$langs->trans("Currency");
		print '<input type="hidden" value="'.$account->currency_code.'">';
		print '</td>';
		print '<td colspan="3">';

		$selectedcode=$account->account_currency_code;
		if (! $selectedcode) $selectedcode=$conf->monnaie;
		$form->select_currency($selectedcode, 'account_currency_code');
		//print $langs->trans("Currency".$conf->monnaie);
		//print '<input type="hidden" name="account_currency_code" value="'.$conf->monnaie.'">';

		print '</td></tr>';

		print '<tr><td valign="top">'.$langs->trans("BalanceMinimalAllowed").'</td>';
		print '<td colspan="3"><input size="12" type="text" class="flat" name="account_min_allowed" value="'.$account->min_allowed.'"></td></tr>';

		print '<tr><td valign="top">'.$langs->trans("BalanceMinimalDesired").'</td>';
		print '<td colspan="3"><input size="12" type="text" class="flat" name="account_min_desired" value="'.$account->min_desired.'"></td></tr>';

		// Web
        print '<tr><td valign="top">'.$langs->trans("Web").'</td>';
        print '<td colspan="3"><input size="50" type="text" class="flat" name="url" value="'.$account->url.'">';
        print '</td></tr>';

		// Comment
		print '<tr><td valign="top">'.$langs->trans("Comment").'</td>';
		print '<td colspan="3">';
	   // �diteur wysiwyg
		if ($conf->fckeditor->enabled && $conf->global->FCKEDITOR_ENABLE_MAILING)
		{
			require_once(DOL_DOCUMENT_ROOT."/lib/doleditor.class.php");
			$doleditor=new DolEditor('account_comment',$account->comment,200,'dolibarr_notes','',false);
			$doleditor->Create();
		}
		else
		{
			print '<textarea class="flat" name="account_comment" cols="70" rows="10">';
			print dol_htmlentitiesbr_decode($account->comment).'</textarea>';
		}
		print '</td></tr>';

        print '<tr><td align="center" colspan="4"><input value="'.$langs->trans("Modify").'" type="submit" class="button">';
        print ' &nbsp; <input name="cancel" value="'.$langs->trans("Cancel").'" type="submit" class="button">';
        print '</td></tr>';
        print '</table>';

        print '</form>';
	}

}



$db->close();

llxFooter('$Date$ - $Revision$');
?>
