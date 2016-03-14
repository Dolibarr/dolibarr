<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo	<jlb@j1b.org>
 * Copyright (C) 2004-2015	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2015	Alexandre Spangaro	<aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015       Jean-François Ferry	<jfefe@aternatik.fr>
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
 */

/**
 *	    \file       htdocs/compta/bank/card.php
 *      \ingroup    bank
 *		\brief      Page to create/view a bank account
 */

require('../../main.inc.php');
require_once DOL_DOCUMENT_ROOT . '/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/html.formbank.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

$langs->load("banks");
$langs->load("bills");
$langs->load("categories");
$langs->load("companies");
$langs->load("compta");

$action=GETPOST("action");

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?$_GET["id"]:(isset($_GET["ref"])?$_GET["ref"]:'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$id,'bank_account&bank_account','','',$fieldid);

$account = new Account($db);
$extrafields = new ExtraFields($db);
// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($account->table_element);

/*
 * Actions
 */
if ($_POST["action"] == 'add')
{
    $error=0;

    // Create account
    $account = new Account($db);

    $account->ref           = dol_sanitizeFileName(trim($_POST["ref"]));
    $account->label         = trim($_POST["label"]);
    $account->courant       = $_POST["type"];
    $account->clos          = $_POST["clos"];
    $account->rappro        = (isset($_POST["norappro"]) && $_POST["norappro"])?0:1;
    $account->url           = $_POST["url"];

	$account->bank            = trim($_POST["bank"]);
    $account->code_banque     = trim($_POST["code_banque"]);
    $account->code_guichet    = trim($_POST["code_guichet"]);
    $account->number          = trim($_POST["number"]);
    $account->cle_rib         = trim($_POST["cle_rib"]);
    $account->bic             = trim($_POST["bic"]);
    $account->iban            = trim($_POST["iban"]);
    $account->domiciliation   = trim($_POST["domiciliation"]);

    $account->proprio 	      = trim($_POST["proprio"]);
    $account->owner_address   = trim($_POST["owner_address"]);

    $account->account_number  = trim($_POST["account_number"]);
	$account->accountancy_journal  = trim($_POST["accountancy_journal"]);

    $account->solde           = $_POST["solde"];
    $account->date_solde      = dol_mktime(12,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

    $account->currency_code   = trim($_POST["account_currency_code"]);

    $account->state_id  	  = $_POST["account_state_id"];
    $account->country_id      = $_POST["account_country_id"];

    $account->min_allowed     = GETPOST("account_min_allowed",'int');
    $account->min_desired     = GETPOST("account_min_desired",'int');
    $account->comment         = trim($_POST["account_comment"]);

    if ($conf->global->MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED && empty($account->account_number))
    {
        setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("AccountancyCode")), null, 'error');
        $action='create';       // Force chargement page en mode creation
        $error++;
    }
    if (empty($account->ref))
    {
        setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")), null, 'errors');
        $action='create';       // Force chargement page en mode creation
        $error++;
    }
    if (empty($account->label))
    {
    	setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("LabelBankCashAccount")), null, 'errors');
    	$action='create';       // Force chargement page en mode creation
    	$error++;
    }

    // Fill array 'array_options' with data from add form
    $ret = $extrafields->setOptionalsFromPost($extralabels,$account);

    if (! $error)
    {
        $id = $account->create($user);
        if ($id > 0)
        {
            $_GET["id"]=$id;            // Force chargement page en mode visu
        }
        else {
            setEventMessages($account->error, $account->errors, 'errors');
            $action='create';   // Force chargement page en mode creation
        }
    }
}

if ($_POST["action"] == 'update' && ! $_POST["cancel"])
{
    $error=0;

    // Update account
    $account = new Account($db);
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
    $account->iban            = trim($_POST["iban"]);
    $account->domiciliation   = trim($_POST["domiciliation"]);

    $account->proprio 	      = trim($_POST["proprio"]);
    $account->owner_address   = trim($_POST["owner_address"]);

    $account->account_number  = trim($_POST["account_number"]);
	$account->accountancy_journal = trim($_POST["accountancy_journal"]);

    $account->currency_code   = trim($_POST["account_currency_code"]);

    $account->state_id        = $_POST["account_state_id"];
    $account->country_id      = $_POST["account_country_id"];

    $account->min_allowed     = GETPOST("account_min_allowed",'int');
    $account->min_desired     = GETPOST("account_min_desired",'int');
    $account->comment         = trim($_POST["account_comment"]);

    if ($conf->global->MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED && empty($account->account_number))
    {
        setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("AccountancyCode")), null, 'error');
        $action='edit';       // Force chargement page en mode creation
        $error++;
    }
    if (empty($account->ref))
    {
        setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")), null, 'errors');
        $action='edit';       // Force chargement page en mode creation
        $error++;
    }
    if (empty($account->label))
    {
    	setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("LabelBankCashAccount")), null, 'errors');
    	$action='edit';       // Force chargement page en mode creation
    	$error++;
    }

    // Fill array 'array_options' with data from add form
    $ret = $extrafields->setOptionalsFromPost($extralabels,$account);

    if (! $error)
    {
        $result = $account->update($user);
        if ($result >= 0)
        {
            $_GET["id"]=$_POST["id"];   // Force chargement page en mode visu
        }
        else
        {
	        setEventMessages($account->error, $account->errors, 'errors');
            $action='edit';     // Force chargement page edition
        }
    }
}

if ($_POST["action"] == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->banque->configurer)
{
    // Delete
    $account = new Account($db);
    $account->fetch($_GET["id"]);
    $account->delete();

    header("Location: ".DOL_URL_ROOT."/compta/bank/index.php");
    exit;
}


/*
 * View
 */

$form = new Form($db);
$formbank = new FormBank($db);
$formcompany = new FormCompany($db);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

llxHeader();


// Creation

if ($action == 'create')
{
	$account=new Account($db);

	print load_fiche_titre($langs->trans("NewFinancialAccount"), '', 'title_bank.png');

    if ($conf->use_javascript_ajax)
    {
		print "\n".'<script type="text/javascript" language="javascript">';
        print 'jQuery(document).ready(function () {
                    jQuery("#selecttype").change(function() {
                        document.formsoc.action.value="create";
                        document.formsoc.submit();
                    });
                    jQuery("#selectaccount_country_id").change(function() {
                        document.formsoc.action.value="create";
                        document.formsoc.submit();
                    });
               })';
        print '</script>'."\n";
    }

	print '<form action="'.$_SERVER["PHP_SELF"].'" name="formsoc" method="post">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="add">';
	print '<input type="hidden" name="clos" value="0">';

	dol_fiche_head('');

	print '<table class="border" width="100%">';

	// Ref
	print '<tr><td class="fieldrequired" width="25%">'.$langs->trans("Ref").'</td>';
	print '<td colspan="3"><input size="8" type="text" class="flat" name="ref" value="'.($_POST["ref"]?$_POST["ref"]:$account->ref).'" maxlength="12"></td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("LabelBankCashAccount").'</td>';
	print '<td colspan="3"><input size="30" type="text" class="flat" name="label" value="'.GETPOST("label", 'alpha').'"></td></tr>';

	// Type
	print '<tr><td class="fieldrequired">'.$langs->trans("AccountType").'</td>';
	print '<td colspan="3">';
	$formbank->select_type_comptes_financiers(isset($_POST["type"])?$_POST["type"]:1,"type");
	print '</td></tr>';

	// Currency
	print '<tr><td class="fieldrequired">'.$langs->trans("Currency").'</td>';
	print '<td colspan="3">';
	$selectedcode=$account->account_currency_code;
	if (! $selectedcode) $selectedcode=$conf->currency;
	print $form->selectCurrency((isset($_POST["account_currency_code"])?$_POST["account_currency_code"]:$selectedcode), 'account_currency_code');
	//print $langs->trans("Currency".$conf->currency);
	//print '<input type="hidden" name="account_currency_code" value="'.$conf->currency.'">';
	print '</td></tr>';

	// Status
    print '<tr><td class="fieldrequired">'.$langs->trans("Status").'</td>';
    print '<td colspan="3">';
    print $form->selectarray("clos",array(0=>$account->status[0],1=>$account->status[1]),(isset($_POST["clos"])?$_POST["clos"]:$account->clos));
    print '</td></tr>';

    // Country
	$selectedcode='';
	if (isset($_POST["account_country_id"]))
	{
		$selectedcode=$_POST["account_country_id"]?$_POST["account_country_id"]:$account->country_code;
	}
	else if (empty($selectedcode)) $selectedcode=$mysoc->country_code;
	$account->country_code = getCountry($selectedcode, 2);	// Force country code on account to have following field on bank fields matching country rules

	print '<tr><td class="fieldrequired">'.$langs->trans("BankAccountCountry").'</td>';
	print '<td colspan="3">';
	print $form->select_country($selectedcode,'account_country_id');
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	print '</td></tr>';

	// State
	print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
	if ($selectedcode)
	{
		$formcompany->select_departement(isset($_POST["account_state_id"])?$_POST["account_state_id"]:'',$selectedcode,'account_state_id');
	}
	else
	{
		print $countrynotdefined;
	}
	print '</td></tr>';

	// Web
	print '<tr><td>'.$langs->trans("Web").'</td>';
	print '<td colspan="3"><input size="50" type="text" class="flat" name="url" value="'.$_POST["url"].'"></td></tr>';

	// Comment
	print '<tr><td class="tdtop">'.$langs->trans("Comment").'</td>';
	print '<td colspan="3">';
    // Editor wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('account_comment',(GETPOST("account_comment")?GETPOST("account_comment"):$account->comment),'',90,'dolibarr_notes','',false,true,$conf->global->FCKEDITOR_ENABLE_SOCIETE,4,70);
	$doleditor->Create();
	print '</td></tr>';

 	// Other attributes
	$parameters=array('colspan' => 3);
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$account,$action);    // Note that $action and $object may have been modified by hook
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $account->showOptionals($extrafields,'edit',$parameters);
	}

	print '</table>';

	print '<br>';

	print '<table class="border" width="100%">';

	// Sold
	print '<tr><td width="25%">'.$langs->trans("InitialBankBalance").'</td>';
	print '<td colspan="3"><input size="12" type="text" class="flat" name="solde" value="'.(GETPOST("solde")?GETPOST("solde"):price2num($account->solde)).'"></td></tr>';

	print '<tr><td>'.$langs->trans("Date").'</td>';
	print '<td colspan="3">';
	$form->select_date('', 're', 0, 0, 0, 'formsoc');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("BalanceMinimalAllowed").'</td>';
	print '<td colspan="3"><input size="12" type="text" class="flat" name="account_min_allowed" value="'.($_POST["account_min_allowed"]?$_POST["account_min_allowed"]:$account->account_min_allowed).'"></td></tr>';

	print '<tr><td>'.$langs->trans("BalanceMinimalDesired").'</td>';
	print '<td colspan="3"><input size="12" type="text" class="flat" name="account_min_desired" value="'.($_POST["account_min_desired"]?$_POST["account_min_desired"]:$account->account_min_desired).'"></td></tr>';

	print '</table>';
	print '<br>';

	if ($_POST["type"] == 0 || $_POST["type"] == 1)
	{
		print '<table class="border" width="100%">';

		// If bank account
		print '<tr><td width="25%">'.$langs->trans("BankName").'</td>';
		print '<td colspan="3"><input size="30" type="text" class="flat" name="bank" value="'.$account->bank.'"></td>';
		print '</tr>';

		// Show fields of bank account
		$fieldlists='BankCode DeskCode AccountNumber BankAccountNumberKey';
		if (! empty($conf->global->BANK_SHOW_ORDER_OPTION))
		{
			if (is_numeric($conf->global->BANK_SHOW_ORDER_OPTION))
			{
				if ($conf->global->BANK_SHOW_ORDER_OPTION == '1') $fieldlists='BankCode DeskCode BankAccountNumberKey AccountNumber';
			}
			else $fieldlists=$conf->global->BANK_SHOW_ORDER_OPTION;
		}
		$fieldlistsarray=explode(' ',$fieldlists);

		foreach($fieldlistsarray as $val)
		{
			if ($val == 'BankCode')
			{
				if ($account->useDetailedBBAN()  == 1)
				{
					print '<tr><td>'.$langs->trans("BankCode").'</td>';
					print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
					print '</tr>';
				}
			}

			if ($val == 'DeskCode')
			{
				if ($account->useDetailedBBAN()  == 1)
				{
					print '<tr><td>'.$langs->trans("DeskCode").'</td>';
					print '<td><input size="8" type="text" class="flat" name="code_guichet" value="'.$account->code_guichet.'"></td>';
					print '</tr>';
				}
			}

			if ($val == 'BankCode')
			{
				if ($account->useDetailedBBAN()  == 2)
				{
					print '<tr><td>'.$langs->trans("BankCode").'</td>';
					print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
					print '</tr>';
				}
			}

			if ($val == 'AccountNumber')
			{
				print '<td>'.$langs->trans("BankAccountNumber").'</td>';
				print '<td><input size="18" type="text" class="flat" name="number" value="'.$account->number.'"></td>';
				print '</tr>';
			}

			if ($val == 'BankAccountNumberKey')
			{
				if ($account->useDetailedBBAN() == 1)
				{
					print '<td>'.$langs->trans("BankAccountNumberKey").'</td>';
					print '<td><input size="3" type="text" class="flat" name="cle_rib" value="'.$account->cle_rib.'"></td>';
					print '</tr>';
				}
			}
		}
		$ibankey="IBANNumber";
		$bickey="BICNumber";
		if ($account->getCountryCode() == 'IN') $ibankey="IFSC";
		if ($account->getCountryCode() == 'IN') $bickey="SWIFT";

		// IBAN
		print '<tr><td>'.$langs->trans($ibankey).'</td>';
		print '<td colspan="3"><input size="34" maxlength="34" type="text" class="flat" name="iban" value="'.$account->iban.'"></td></tr>';

		print '<tr><td>'.$langs->trans($bickey).'</td>';
		print '<td colspan="3"><input size="11" maxlength="11" type="text" class="flat" name="bic" value="'.$account->bic.'"></td></tr>';

		print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="3">';
		print "<textarea class=\"flat\" name=\"domiciliation\" rows=\"2\" cols=\"40\">";
		print $account->domiciliation;
		print "</textarea></td></tr>";

		print '<tr><td>'.$langs->trans("BankAccountOwner").'</td>';
		print '<td colspan="3"><input size="30" type="text" class="flat" name="proprio" value="'.$account->proprio.'">';
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="3">';
		print "<textarea class=\"flat\" name=\"owner_address\" rows=\"2\" cols=\"40\">";
		print $account->owner_address;
		print "</textarea></td></tr>";

		print '</table>';
		print '<br>';
	}

	print '<table class="border" width="100%">';
	// Accountancy code
    if (! empty($conf->global->MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED))
    {
        print '<tr><td class="fieldrequired"  width="25%">'.$langs->trans("AccountancyCode").'</td>';
        print '<td colspan="3"><input type="text" name="account_number" value="'.(GETPOST("account_number")?GETPOST('account_number', 'alpha'):$account->account_number).'"></td></tr>';
    }
    else
    {
        print '<tr><td width="25%">'.$langs->trans("AccountancyCode").'</td>';
        print '<td colspan="3"><input type="text" name="account_number" value="'.(GETPOST("account_number")?GETPOST('account_number', 'alpha'):$account->account_number).'"></td></tr>';
    }

	// Accountancy journal
	if (! empty($conf->accounting->enabled))
	{
		print '<tr><td>'.$langs->trans("AccountancyJournal").'</td>';
	    print '<td colspan="3"><input type="text" name="accountancy_journal" value="'.(GETPOST("accountancy_journal")?GETPOST('accountancy_journal', 'alpha'):$account->accountancy_journal).'"></td></tr>';
	}

	print '</table>';

	dol_fiche_end();

	print '<div class="center"><input value="'.$langs->trans("CreateAccount").'" type="submit" class="button"></div>';

	print '</form>';
}
/* ************************************************************************** */
/*                                                                            */
/* Visu et edition                                                            */
/*                                                                            */
/* ************************************************************************** */
else
{
    if (($_GET["id"] || $_GET["ref"]) && $action != 'edit')
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
		dol_fiche_head($head, 'bankname', $langs->trans("FinancialAccount"),0,'account');

		/*
		* Confirmation to delete
		*/
		if ($action == 'delete')
		{
			print $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$account->id,$langs->trans("DeleteAccount"),$langs->trans("ConfirmDeleteAccount"),"confirm_delete");

		}

		print '<table class="border" width="100%">';

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/index.php">'.$langs->trans("BackToList").'</a>';

		// Ref
		print '<tr><td width="25%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="3">';
		print $form->showrefnav($account, 'ref', $linkback, 1, 'ref');
		print '</td></tr>';

		// Label
		print '<tr><td>'.$langs->trans("Label").'</td>';
		print '<td colspan="3">'.$account->label.'</td></tr>';

		// Type
		print '<tr><td>'.$langs->trans("AccountType").'</td>';
		print '<td colspan="3">'.$account->type_lib[$account->type].'</td></tr>';

		// Currency
		print '<tr><td>'.$langs->trans("Currency").'</td>';
		print '<td colspan="3">';
		$selectedcode=$account->account_currency_code;
		if (! $selectedcode) $selectedcode=$conf->currency;
		print $langs->trans("Currency".$selectedcode);
		print '</td></tr>';

		// Status
		print '<tr><td>'.$langs->trans("Status").'</td>';
		print '<td colspan="3">'.$account->getLibStatut(4).'</td></tr>';

		// Country
		print '<tr><td>'.$langs->trans("BankAccountCountry").'</td><td>';
		if ($account->country_id > 0)
		{
			$img=picto_from_langcode($account->country_code);
			print $img?$img.' ':'';
			print getCountry($account->getCountryCode(),0,$db);
		}
		print '</td></tr>';

		// State
		print '<tr><td>'.$langs->trans('State').'</td><td>';
		if ($account->state_id > 0) print getState($account->state_id);
		print '</td></tr>';

		// Conciliate
		print '<tr><td>'.$langs->trans("Conciliable").'</td>';
		print '<td colspan="3">';
		$conciliate=$account->canBeConciliated();
		if ($conciliate == -2) print $langs->trans("No").' ('.$langs->trans("CashAccount").')';
        else if ($conciliate == -3) print $langs->trans("No").' ('.$langs->trans("Closed").')';
		else print ($account->rappro==1 ? $langs->trans("Yes") : ($langs->trans("No").' ('.$langs->trans("ConciliationDisabled").')'));
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("BalanceMinimalAllowed").'</td>';
		print '<td colspan="3">'.$account->min_allowed.'</td></tr>';

		print '<tr><td>'.$langs->trans("BalanceMinimalDesired").'</td>';
		print '<td colspan="3">'.$account->min_desired.'</td></tr>';

		print '<tr><td>'.$langs->trans("Web").'</td><td colspan="3">';
		if ($account->url) print '<a href="'.$account->url.'" target="_gobank">';
		print $account->url;
		if ($account->url) print '</a>';
		print "</td></tr>\n";

		print '<tr><td class="tdtop">'.$langs->trans("Comment").'</td>';
		print '<td colspan="3">'.dol_htmlentitiesbr($account->comment).'</td></tr>';

		// Other attributes
		$parameters=array('colspan' => 3);
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$account,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $account->showOptionals($extrafields);
		}

		print '</table>';

		print '<br>';

		if ($account->type == 0 || $account->type == 1)
		{
			print '<table class="border" width="100%">';

			print '<tr><td valign="top" width="25%">'.$langs->trans("BankName").'</td>';
			print '<td colspan="3">'.$account->bank.'</td></tr>';

			// Show fields of bank account
			$fieldlists='BankCode DeskCode AccountNumber BankAccountNumberKey';
			if (! empty($conf->global->BANK_SHOW_ORDER_OPTION))
			{
				if (is_numeric($conf->global->BANK_SHOW_ORDER_OPTION))
				{
					if ($conf->global->BANK_SHOW_ORDER_OPTION == '1') $fieldlists='BankCode DeskCode BankAccountNumberKey AccountNumber';
				}
				else $fieldlists=$conf->global->BANK_SHOW_ORDER_OPTION;
			}
			$fieldlistsarray=explode(' ',$fieldlists);

			foreach($fieldlistsarray as $val)
			{
				if ($val == 'BankCode')
				{
					if ($account->useDetailedBBAN() == 1)
					{
						print '<tr><td>'.$langs->trans("BankCode").'</td>';
						print '<td colspan="3">'.$account->code_banque.'</td>';
						print '</tr>';
					}
				}
				if ($val == 'DeskCode')
				{
					if ($account->useDetailedBBAN() == 1)
					{
						print '<tr><td>'.$langs->trans("DeskCode").'</td>';
						print '<td colspan="3">'.$account->code_guichet.'</td>';
						print '</tr>';
					}
				}

				if ($val == 'BankCode')
				{
					if ($account->useDetailedBBAN() == 2)
					{
						print '<tr><td>'.$langs->trans("BankCode").'</td>';
						print '<td colspan="3">'.$account->code_banque.'</td>';
						print '</tr>';
					}
				}

				if ($val == 'AccountNumber')
				{
					print '<tr><td>'.$langs->trans("BankAccountNumber").'</td>';
					print '<td colspan="3">'.$account->number.'</td>';
					print '</tr>';
				}

				if ($val == 'BankAccountNumberKey')
				{
					if ($account->useDetailedBBAN() == 1)
					{
						print '<tr><td>'.$langs->trans("BankAccountNumberKey").'</td>';
						print '<td colspan="3">'.$account->cle_rib.'</td>';
						print '</tr>';
					}
				}
			}

			$ibankey="IBANNumber";
			$bickey="BICNumber";
			if ($account->getCountryCode() == 'IN') $ibankey="IFSC";
			if ($account->getCountryCode() == 'IN') $bickey="SWIFT";

			print '<tr><td>'.$langs->trans($ibankey).'</td>';
			print '<td colspan="3">'.$account->iban.'&nbsp;';
			if (! empty($account->iban)) {
				if (! checkIbanForAccount($account)) {
					print img_picto($langs->trans("IbanNotValid"),'warning');
				} else {
					print img_picto($langs->trans("IbanValid"),'info');
				}
			}
			print '</td></tr>';

			print '<tr><td>'.$langs->trans($bickey).'</td>';
			print '<td colspan="3">'.$account->bic.'&nbsp;';
			if (! empty($account->bic)) {
				if (! checkSwiftForAccount($account)) {
					print img_picto($langs->trans("SwiftNotValid"),'warning');
				} else {
					print img_picto($langs->trans("SwiftValid"),'info');
				}
			}
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="3">';
			print nl2br($account->domiciliation);
			print "</td></tr>\n";

			print '<tr><td>'.$langs->trans("BankAccountOwner").'</td><td colspan="3">';
			print $account->proprio;
			print "</td></tr>\n";

			print '<tr><td>'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="3">';
			print nl2br($account->owner_address);
			print "</td></tr>\n";

			print '</table>';
			print '<br>';
		}

		print '<table class="border" width="100%">';
		// Accountancy code
		print '<tr><td width="25%">'.$langs->trans("AccountancyCode").'</td>';
		print '<td colspan="3">'.$account->account_number.'</td></tr>';

		// Accountancy journal
		if (! empty($conf->accounting->enabled))
		{
			print '<tr><td>'.$langs->trans("AccountancyJournal").'</td>';
			print '<td colspan="3">'.$account->accountancy_journal.'</td></tr>';
		}

		print '</table>';

		print '</div>';


		/*
		 * Barre d'actions
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

    if (GETPOST('id','int') && $action == 'edit' && $user->rights->banque->configurer)
    {
        $account = new Account($db);
        $account->fetch(GETPOST('id','int'));

        print load_fiche_titre($langs->trans("EditFinancialAccount"), '', 'title_bank.png');

        if ($conf->use_javascript_ajax)
        {
            print "\n".'<script type="text/javascript" language="javascript">';
            print 'jQuery(document).ready(function () {
                        jQuery("#selecttype").change(function() {
                            document.formsoc.action.value="edit";
                            document.formsoc.submit();
                        });
                   })'."\n";

			print 'jQuery(document).ready(function () {
                        jQuery("#selectaccount_country_id").change(function() {
                            document.formsoc.action.value="edit";
                            document.formsoc.submit();
                        });
                   })';
            print '</script>'."\n";
        }

        print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$account->id.'" method="post" name="formsoc">';
        print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
        print '<input type="hidden" name="action" value="update">';
        print '<input type="hidden" name="id" value="'.$_REQUEST["id"].'">'."\n\n";

        dol_fiche_head('');

        print '<table class="border" width="100%">';

		// Ref
		print '<tr><td class="fieldrequired" width="25%">'.$langs->trans("Ref").'</td>';
		print '<td colspan="3"><input size="8" type="text" class="flat" name="ref" value="'.(isset($_POST["ref"])?$_POST["ref"]:$account->ref).'"></td></tr>';

		// Label
        print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td>';
        print '<td colspan="3"><input size="30" type="text" class="flat" name="label" value="'.(isset($_POST["label"])?$_POST["label"]:$account->label).'"></td></tr>';

        // Type
        print '<tr><td class="fieldrequired">'.$langs->trans("AccountType").'</td>';
        print '<td colspan="3">';
		$formbank->select_type_comptes_financiers((isset($_POST["type"])?$_POST["type"]:$account->type),"type");
        print '</td></tr>';

		// Currency
		print '<tr><td class="fieldrequired">'.$langs->trans("Currency");
		print '<input type="hidden" value="'.$account->currency_code.'">';
		print '</td>';
		print '<td colspan="3">';
		$selectedcode=$account->account_currency_code;
		if (! $selectedcode) $selectedcode=$conf->currency;
		print $form->selectCurrency((isset($_POST["account_currency_code"])?$_POST["account_currency_code"]:$selectedcode), 'account_currency_code');
		//print $langs->trans("Currency".$conf->currency);
		//print '<input type="hidden" name="account_currency_code" value="'.$conf->currency.'">';
		print '</td></tr>';

		// Status
        print '<tr><td class="fieldrequired">'.$langs->trans("Status").'</td>';
        print '<td colspan="3">';
        print $form->selectarray("clos",array(0=>$account->status[0],1=>$account->status[1]),(isset($_POST["clos"])?$_POST["clos"]:$account->clos));
        print '</td></tr>';

		// Country
		$account->country_id=$account->country_id?$account->country_id:$mysoc->country_id;
		$selectedcode=$account->country_code;
		if (isset($_POST["account_country_id"])) $selectedcode=$_POST["account_country_id"];
		else if (empty($selectedcode)) $selectedcode=$mysoc->country_code;
		$account->country_code = getCountry($selectedcode, 2);	// Force country code on account to have following field on bank fields matching country rules

		print '<tr><td class="fieldrequired">'.$langs->trans("Country").'</td>';
		print '<td colspan="3">';
		print $form->select_country($selectedcode,'account_country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '</td></tr>';

		// State
		print '<tr><td>'.$langs->trans('State').'</td><td colspan="3">';
		if ($selectedcode)
		{
			print $formcompany->select_state(isset($_POST["account_state_id"])?$_POST["account_state_id"]:$account->state_id,$selectedcode,'account_state_id');
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';

		// Conciliable
        print '<tr><td>'.$langs->trans("Conciliable").'</td>';
        print '<td colspan="3">';
        $conciliate=$account->canBeConciliated();
        if ($conciliate == -2) print $langs->trans("No").' ('.$langs->trans("CashAccount").')';
        else if ($conciliate == -3) print $langs->trans("No").' ('.$langs->trans("Closed").')';
        else print '<input type="checkbox" class="flat" name="norappro"'.(($conciliate > 0)?'':' checked="checked"').'"> '.$langs->trans("DisableConciliation");
        print '</td></tr>';

        // Balance
		print '<tr><td>'.$langs->trans("BalanceMinimalAllowed").'</td>';
		print '<td colspan="3"><input size="12" type="text" class="flat" name="account_min_allowed" value="'.(isset($_POST["account_min_allowed"])?$_POST["account_min_allowed"]:$account->min_allowed).'"></td></tr>';

		print '<tr><td>'.$langs->trans("BalanceMinimalDesired").'</td>';
		print '<td colspan="3"><input size="12" type="text" class="flat" name="account_min_desired" value="'.(isset($_POST["account_min_desired"])?$_POST["account_min_desired"]:$account->min_desired).'"></td></tr>';

		// Web
        print '<tr><td>'.$langs->trans("Web").'</td>';
        print '<td colspan="3"><input size="50" type="text" class="flat" name="url" value="'.(isset($_POST["url"])?$_POST["url"]:$account->url).'">';
        print '</td></tr>';

		// Comment
		print '<tr><td class="tdtop">'.$langs->trans("Comment").'</td>';
		print '<td colspan="3">';
	    // Editor wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor=new DolEditor('account_comment',(GETPOST("account_comment")?GETPOST("account_comment"):$account->comment),'',90,'dolibarr_notes','',false,true,$conf->global->FCKEDITOR_ENABLE_SOCIETE,4,70);
		$doleditor->Create();
		print '</td></tr>';

		// Other attributes
		$parameters=array('colspan' => 3);
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$account,$action);    // Note that $action and $object may have been modified by hook
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $account->showOptionals($extrafields,'edit');
		}
		print '</table>';
		print '<br>';

		if ($_POST["type"] == 0 || $_POST["type"] == 1)
		{
			print '<table class="border" width="100%">';

			// If bank account
			print '<tr><td width="25%">'.$langs->trans("BankName").'</td>';
			print '<td colspan="3"><input size="30" type="text" class="flat" name="bank" value="'.$account->bank.'"></td>';
			print '</tr>';

			// Show fields of bank account
			$fieldlists='BankCode DeskCode AccountNumber BankAccountNumberKey';
			if (! empty($conf->global->BANK_SHOW_ORDER_OPTION))
			{
				if (is_numeric($conf->global->BANK_SHOW_ORDER_OPTION))
				{
					if ($conf->global->BANK_SHOW_ORDER_OPTION == '1') $fieldlists='BankCode DeskCode BankAccountNumberKey AccountNumber';
				}
				else $fieldlists=$conf->global->BANK_SHOW_ORDER_OPTION;
			}
			$fieldlistsarray=explode(' ',$fieldlists);

			foreach($fieldlistsarray as $val)
			{
				if ($val == 'BankCode')
				{
					if ($account->useDetailedBBAN()  == 1)
					{
						print '<tr><td>'.$langs->trans("BankCode").'</td>';
						print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
						print '</tr>';
					}
				}

				if ($val == 'DeskCode')
				{
					if ($account->useDetailedBBAN()  == 1)
					{
						print '<tr><td>'.$langs->trans("DeskCode").'</td>';
						print '<td><input size="8" type="text" class="flat" name="code_guichet" value="'.$account->code_guichet.'"></td>';
						print '</tr>';
					}
				}

				if ($val == 'BankCode')
				{
					if ($account->useDetailedBBAN()  == 2)
					{
						print '<tr><td>'.$langs->trans("BankCode").'</td>';
						print '<td><input size="8" type="text" class="flat" name="code_banque" value="'.$account->code_banque.'"></td>';
						print '</tr>';
					}
				}

				if ($val == 'AccountNumber')
				{
					print '<td>'.$langs->trans("BankAccountNumber").'</td>';
					print '<td><input size="18" type="text" class="flat" name="number" value="'.$account->number.'"></td>';
					print '</tr>';
				}

				if ($val == 'BankAccountNumberKey')
				{
					if ($account->useDetailedBBAN() == 1)
					{
						print '<td>'.$langs->trans("BankAccountNumberKey").'</td>';
						print '<td><input size="3" type="text" class="flat" name="cle_rib" value="'.$account->cle_rib.'"></td>';
						print '</tr>';
					}
				}
			}

			$ibankey="IBANNumber";
			$bickey="BICNumber";
			if ($account->getCountryCode() == 'IN') $ibankey="IFSC";
			if ($account->getCountryCode() == 'IN') $bickey="SWIFT";

			// IBAN
			print '<tr><td>'.$langs->trans($ibankey).'</td>';
			print '<td colspan="3"><input size="34" maxlength="34" type="text" class="flat" name="iban" value="'.$account->iban.'"></td></tr>';

			print '<tr><td>'.$langs->trans($bickey).'</td>';
			print '<td colspan="3"><input size="11" maxlength="11" type="text" class="flat" name="bic" value="'.$account->bic.'"></td></tr>';

			print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td colspan="3">';
			print "<textarea class=\"flat\" name=\"domiciliation\" rows=\"2\" cols=\"40\">";
			print $account->domiciliation;
			print "</textarea></td></tr>";

			print '<tr><td>'.$langs->trans("BankAccountOwner").'</td>';
			print '<td colspan="3"><input size="30" type="text" class="flat" name="proprio" value="'.$account->proprio.'">';
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("BankAccountOwnerAddress").'</td><td colspan="3">';
			print "<textarea class=\"flat\" name=\"owner_address\" rows=\"2\" cols=\"40\">";
			print $account->owner_address;
			print "</textarea></td></tr>";

			print '</table>';
			print '<br>';
		}

		print '<table class="border" width="100%">';

		// Accountancy code
        if (! empty($conf->global->MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED))
        {
            print '<tr><td class="fieldrequired" width="25%">'.$langs->trans("AccountancyCode").'</td>';
            print '<td colspan="3"><input type="text" name="account_number" value="'.(isset($_POST["account_number"])?$_POST["account_number"]:$account->account_number).'"></td></tr>';
        }
        else
        {
            print '<tr><td width="25%">'.$langs->trans("AccountancyCode").'</td>';
            print '<td colspan="3"><input type="text" name="account_number" value="'.(isset($_POST["account_number"])?$_POST["account_number"]:$account->account_number).'"></td></tr>';
        }

		// Accountancy journal
		if (! empty($conf->accounting->enabled))
		{
	        print '<tr><td>'.$langs->trans("AccountancyJournal").'</td>';
	        print '<td colspan="3"><input type="text" name="accountancy_journal" value="'.(isset($_POST["accountancy_journal"])?$_POST["accountancy_journal"]:$account->accountancy_journal).'"></td></tr>';
		}

		print '</table>';

		dol_fiche_end();

		print '<div class="center">';
		print '<input value="'.$langs->trans("Modify").'" type="submit" class="button">';
		print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
		print '<input name="cancel" value="'.$langs->trans("Cancel").'" type="submit" class="button">';
		print '</div>';

		print '</form>';
	}

}

llxFooter();
$db->close();
