<?php
/* Copyright (C) 2002-2003	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin			<regis.houssin@capnetworks.com>
 * Copyright (C) 2014-2017	Alexandre Spangaro		<aspangaro@zendsi.com>
 * Copyright (C) 2015		Jean-François Ferry		<jfefe@aternatik.fr>
 * Copyright (C) 2016		Marcos García			<marcosgdf@gmail.com>
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
if (! empty($conf->categorie->enabled)) require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT . '/core/class/html.formaccounting.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingaccount.class.php';
if (! empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT . '/accountancy/class/accountingjournal.class.php';

$langs->loadLangs(array("banks","bills","categories","companies","compta"));

$action = GETPOST('action','aZ09');
$cancel = GETPOST('cancel', 'alpha');

// Security check
if (isset($_GET["id"]) || isset($_GET["ref"]))
{
	$id = isset($_GET["id"])?GETPOST("id"):(isset($_GET["ref"])?GETPOST("ref"):'');
}
$fieldid = isset($_GET["ref"])?'ref':'rowid';
if ($user->societe_id) $socid=$user->societe_id;
$result=restrictedArea($user,'banque',$id,'bank_account&bank_account','','',$fieldid);

$object = new Account($db);
$extrafields = new ExtraFields($db);
// fetch optionals attributes and labels
$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);



/*
 * Actions
 */

if ($cancel) $action='';

if ($action == 'add')
{
	$error=0;

	$db->begin();

	// Create account
	$object = new Account($db);

	$object->ref           = dol_sanitizeFileName(trim($_POST["ref"]));
	$object->label         = trim($_POST["label"]);
	$object->courant       = $_POST["type"];
	$object->clos          = $_POST["clos"];
	$object->rappro        = (isset($_POST["norappro"]) && $_POST["norappro"])?0:1;
	$object->url           = $_POST["url"];

	$object->bank            = trim($_POST["bank"]);
	$object->code_banque     = trim($_POST["code_banque"]);
	$object->code_guichet    = trim($_POST["code_guichet"]);
	$object->number          = trim($_POST["number"]);
	$object->cle_rib         = trim($_POST["cle_rib"]);
	$object->bic             = trim($_POST["bic"]);
	$object->iban            = trim($_POST["iban"]);
	$object->domiciliation   = trim($_POST["domiciliation"]);

	$object->proprio 	     = trim($_POST["proprio"]);
	$object->owner_address   = trim($_POST["owner_address"]);

	$account_number 		 = GETPOST('account_number','alpha');
	if (empty($account_number) || $account_number == '-1') { $object->account_number = ''; } else { $object->account_number = $account_number; }
	$fk_accountancy_journal  = GETPOST('fk_accountancy_journal','int');
	if ($fk_accountancy_journal <= 0) { $object->fk_accountancy_journal = ''; } else { $object->fk_accountancy_journal = $fk_accountancy_journal; }

	$object->solde           = $_POST["solde"];
	$object->date_solde      = dol_mktime(12,0,0,$_POST["remonth"],$_POST["reday"],$_POST["reyear"]);

	$object->currency_code   = trim($_POST["account_currency_code"]);

	$object->state_id  	     = $_POST["account_state_id"];
	$object->country_id      = $_POST["account_country_id"];

	$object->min_allowed     = GETPOST("account_min_allowed",'int');
	$object->min_desired     = GETPOST("account_min_desired",'int');
	$object->comment         = trim(GETPOST("account_comment"));

	$object->fk_user_author  = $user->id;

	if ($conf->global->MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED && empty($object->account_number))
	{
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("AccountancyCode")), null, 'errors');
		$action='create';       // Force chargement page en mode creation
		$error++;
	}
	if (empty($object->ref))
	{
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")), null, 'errors');
		$action='create';       // Force chargement page en mode creation
		$error++;
	}
	if (empty($object->label))
	{
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("LabelBankCashAccount")), null, 'errors');
		$action='create';       // Force chargement page en mode creation
		$error++;
	}

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

	if (! $error)
	{
		$id = $object->create($user);
		if ($id > 0)
		{
			// Category association
			$categories = GETPOST('categories', 'array');
			$object->setCategories($categories);

			$_GET["id"]=$id;            // Force chargement page en mode visu

			$action='';
		}
		else {
			$error++;
			setEventMessages($object->error, $object->errors, 'errors');

			$action='create';   // Force chargement page en mode creation
		}
	}

	if (! $error)
	{
		$db->commit();
	}
	else
	{
		$db->rollback();
	}
}

if ($action == 'update')
{
	$error=0;

	// Update account
	$object = new Account($db);
	$object->fetch(GETPOST("id"));

	$object->ref             = dol_string_nospecial(trim($_POST["ref"]));
	$object->label           = trim($_POST["label"]);
	$object->courant         = $_POST["type"];
	$object->clos            = $_POST["clos"];
	$object->rappro          = (isset($_POST["norappro"]) && $_POST["norappro"])?0:1;
	$object->url             = trim($_POST["url"]);

	$object->bank            = trim($_POST["bank"]);
	$object->code_banque     = trim($_POST["code_banque"]);
	$object->code_guichet    = trim($_POST["code_guichet"]);
	$object->number          = trim($_POST["number"]);
	$object->cle_rib         = trim($_POST["cle_rib"]);
	$object->bic             = trim($_POST["bic"]);
	$object->iban            = trim($_POST["iban"]);
	$object->domiciliation   = trim($_POST["domiciliation"]);

	$object->proprio 	     = trim($_POST["proprio"]);
	$object->owner_address   = trim($_POST["owner_address"]);

	$account_number 		 = GETPOST('account_number', 'alpha');
	if (empty($account_number) || $account_number == '-1')
	{
		$object->account_number = '';
	}
	else
	{
		$object->account_number = $account_number;
	}
	$fk_accountancy_journal  = GETPOST('fk_accountancy_journal','int');
	if ($fk_accountancy_journal <= 0) { $object->fk_accountancy_journal = ''; } else { $object->fk_accountancy_journal = $fk_accountancy_journal; }

	$object->currency_code   = trim($_POST["account_currency_code"]);

	$object->state_id        = $_POST["account_state_id"];
	$object->country_id      = $_POST["account_country_id"];

	$object->min_allowed     = GETPOST("account_min_allowed",'int');
	$object->min_desired     = GETPOST("account_min_desired",'int');
	$object->comment         = trim(GETPOST("account_comment"));

	if ($conf->global->MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED && empty($object->account_number))
	{
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("AccountancyCode")), null, 'errors');
		$action='edit';       // Force chargement page en mode creation
		$error++;
	}
	if (empty($object->ref))
	{
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("Ref")), null, 'errors');
		$action='edit';       // Force chargement page en mode creation
		$error++;
	}
	if (empty($object->label))
	{
		setEventMessages($langs->transnoentitiesnoconv("ErrorFieldRequired",$langs->transnoentitiesnoconv("LabelBankCashAccount")), null, 'errors');
		$action='edit';       // Force chargement page en mode creation
		$error++;
	}

	// Fill array 'array_options' with data from add form
	$ret = $extrafields->setOptionalsFromPost($extralabels,$object);

	if (! $error)
	{
		$result = $object->update($user);
		if ($result >= 0)
		{
			// Category association
			$categories = GETPOST('categories', 'array');
			$object->setCategories($categories);

			$_GET["id"]=$_POST["id"];   // Force chargement page en mode visu
		}
		else
		{
			setEventMessages($object->error, $object->errors, 'errors');
			$action='edit';     // Force chargement page edition
		}
	}
}

if ($action == 'confirm_delete' && $_POST["confirm"] == "yes" && $user->rights->banque->configurer)
{
	// Delete
	$object = new Account($db);
	$object->fetch(GETPOST("id","int"));
	$result = $object->delete($user);

	if ($result > 0)
	{
		setEventMessages($langs->trans("RecordDeleted"), null, 'mesgs');
		header("Location: ".DOL_URL_ROOT."/compta/bank/list.php");
		exit;
	}
	else
	{
		setEventMessages($account->error, $account->errors, 'errors');
		$action='';
	}
}


/*
 * View
 */

$form = new Form($db);
$formbank = new FormBank($db);
$formcompany = new FormCompany($db);
if (! empty($conf->accounting->enabled)) $formaccounting = New FormAccounting($db);

$countrynotdefined=$langs->trans("ErrorSetACountryFirst").' ('.$langs->trans("SeeAbove").')';

$title = $langs->trans("FinancialAccount") . " - " . $langs->trans("Card");
$helpurl = "";
llxHeader("",$title,$helpurl);


// Creation

if ($action == 'create')
{
	$object=new Account($db);

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
	print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Ref").'</td>';
	print '<td><input size="8" type="text" class="flat" name="ref" value="'.dol_escape_htmltag(GETPOST("ref")?GETPOST("ref",'alpha'):$object->ref).'" maxlength="12" autofocus></td></tr>';

	// Label
	print '<tr><td class="fieldrequired">'.$langs->trans("LabelBankCashAccount").'</td>';
	print '<td><input size="30" type="text" class="flat" name="label" value="'.dol_escape_htmltag(GETPOST("label", 'alpha')).'"></td></tr>';

	// Type
	print '<tr><td class="fieldrequired">'.$langs->trans("AccountType").'</td>';
	print '<td>';
	$formbank->selectTypeOfBankAccount(isset($_POST["type"])?$_POST["type"]: Account::TYPE_CURRENT,"type");
	print '</td></tr>';

	// Currency
	print '<tr><td class="fieldrequired">'.$langs->trans("Currency").'</td>';
	print '<td>';
	$selectedcode=$object->currency_code;
	if (! $selectedcode) $selectedcode=$conf->currency;
	print $form->selectCurrency((isset($_POST["account_currency_code"])?$_POST["account_currency_code"]:$selectedcode), 'account_currency_code');
	//print $langs->trans("Currency".$conf->currency);
	//print '<input type="hidden" name="account_currency_code" value="'.$conf->currency.'">';
	print '</td></tr>';

	// Status
	print '<tr><td class="fieldrequired">'.$langs->trans("Status").'</td>';
	print '<td>';
	print $form->selectarray("clos", $object->status,(GETPOST("clos",'int')!=''?GETPOST("clos",'int'):$object->clos));
	print '</td></tr>';

	// Country
	$selectedcode='';
	if (isset($_POST["account_country_id"]))
	{
		$selectedcode=$_POST["account_country_id"]?$_POST["account_country_id"]:$object->country_code;
	}
	else if (empty($selectedcode)) $selectedcode=$mysoc->country_code;
	$object->country_code = getCountry($selectedcode, 2);	// Force country code on account to have following field on bank fields matching country rules

	print '<tr><td class="fieldrequired">'.$langs->trans("BankAccountCountry").'</td>';
	print '<td>';
	print $form->select_country($selectedcode,'account_country_id');
	if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
	print '</td></tr>';

	// State
	print '<tr><td>'.$langs->trans('State').'</td><td>';
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
	print '<td><input class="minwidth300" type="text" class="flat" name="url" value="'.GETPOST("url").'"></td></tr>';

	// Tags-Categories
	if ($conf->categorie->enabled)
	{
		print '<tr><td class="tdtop">'.$langs->trans("Categories").'</td><td>';
		$cate_arbo = $form->select_all_categories(Categorie::TYPE_ACCOUNT, '', 'parent', 64, 0, 1);
		$c = new Categorie($db);
		$cats = $c->containing($object->id,Categorie::TYPE_ACCOUNT);
		foreach($cats as $cat) {
			$arrayselected[] = $cat->id;
		}
		print $form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
		print "</td></tr>";
	}

	// Comment
	print '<tr><td class="tdtop">'.$langs->trans("Comment").'</td>';
	print '<td>';
	// Editor wysiwyg
	require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
	$doleditor=new DolEditor('account_comment',(GETPOST("account_comment")?GETPOST("account_comment"):$object->comment),'',90,'dolibarr_notes','',false,true,$conf->global->FCKEDITOR_ENABLE_SOCIETE,ROWS_4,'90%');
	$doleditor->Create();
	print '</td></tr>';

 	// Other attributes
	$parameters=array();
	$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;
	if (empty($reshook) && ! empty($extrafields->attribute_label))
	{
		print $object->showOptionals($extrafields,'edit',$parameters);
	}

	print '</table>';

	print '<br>';

	print '<table class="border" width="100%">';

	// Sold
	print '<tr><td class="titlefieldcreate">'.$langs->trans("InitialBankBalance").'</td>';
	print '<td><input size="12" type="text" class="flat" name="solde" value="'.(GETPOST("solde")?GETPOST("solde"):price2num($object->solde)).'"></td></tr>';

	print '<tr><td>'.$langs->trans("Date").'</td>';
	print '<td>';
	$form->select_date('', 're', 0, 0, 0, 'formsoc');
	print '</td></tr>';

	print '<tr><td>'.$langs->trans("BalanceMinimalAllowed").'</td>';
	print '<td><input size="12" type="text" class="flat" name="account_min_allowed" value="'.(GETPOST("account_min_allowed")?GETPOST("account_min_allowed"):$object->min_allowed).'"></td></tr>';

	print '<tr><td>'.$langs->trans("BalanceMinimalDesired").'</td>';
	print '<td><input size="12" type="text" class="flat" name="account_min_desired" value="'.(GETPOST("account_min_desired")?GETPOST("account_min_desired"):$object->min_desired).'"></td></tr>';

	print '</table>';
	print '<br>';

	if ($_POST["type"] == Account::TYPE_SAVINGS || $_POST["type"] == Account::TYPE_CURRENT)
	{
		print '<table class="border" width="100%">';

		// If bank account
		print '<tr><td class="titlefieldcreate">'.$langs->trans("BankName").'</td>';
		print '<td><input size="30" type="text" class="flat" name="bank" value="'.(GETPOST('bank')?GETPOST('bank','alpha'):$object->bank).'"></td>';
		print '</tr>';

		// Show fields of bank account
		foreach ($object->getFieldsToShow() as $val) {
			if ($val == 'BankCode') {
				$name = 'code_banque';
				$size = 8;
				$content = $object->code_banque;
			} elseif ($val == 'DeskCode') {
				$name = 'code_guichet';
				$size = 8;
				$content = $object->code_guichet;
			} elseif ($val == 'BankAccountNumber') {
				$name = 'number';
				$size = 18;
				$content = $object->number;
			} elseif ($val == 'BankAccountNumberKey') {
				$name = 'cle_rib';
				$size = 3;
				$content = $object->cle_rib;
			}

			print '<td>'.$langs->trans($val).'</td>';
			print '<td><input size="'.$size.'" type="text" class="flat" name="'.$name.'" value="'.(GETPOST($name)?GETPOST($name,'alpha'):$content).'"></td>';
			print '</tr>';
		}
		$ibankey = FormBank::getIBANLabel($object);
		$bickey="BICNumber";
		if ($object->getCountryCode() == 'IN') $bickey="SWIFT";

		// IBAN
		print '<tr><td>'.$langs->trans($ibankey).'</td>';
		print '<td><input size="34" maxlength="34" type="text" class="flat" name="iban" value="'.(GETPOST('iban')?GETPOST('iban','alpha'):$object->iban).'"></td></tr>';

		print '<tr><td>'.$langs->trans($bickey).'</td>';
		print '<td><input size="11" maxlength="11" type="text" class="flat" name="bic" value="'.(GETPOST('bic')?GETPOST('bic','alpha'):$object->bic).'"></td></tr>';

		print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td>';
		print "<textarea class=\"flat\" name=\"domiciliation\" rows=\"2\" cols=\"40\">";
		print (GETPOST('domiciliation')?GETPOST('domiciliation'):$object->domiciliation);
		print "</textarea></td></tr>";

		print '<tr><td>'.$langs->trans("BankAccountOwner").'</td>';
		print '<td><input size="30" type="text" class="flat" name="proprio" value="'.(GETPOST('proprio')?GETPOST('proprio','alpha'):$object->proprio).'">';
		print '</td></tr>';

		print '<tr><td class="tdtop">'.$langs->trans("BankAccountOwnerAddress").'</td><td>';
		print "<textarea class=\"flat\" name=\"owner_address\" rows=\"2\" cols=\"40\">";
		print (GETPOST('owner_address')?GETPOST('owner_address','alpha'):$object->owner_address);
		print "</textarea></td></tr>";

		print '</table>';
		print '<br>';
	}

	print '<table class="border" width="100%">';
	// Accountancy code
	$fieldrequired='';
	if (! empty($conf->global->MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED)) $fieldrequired='fieldrequired ';

	if (! empty($conf->accounting->enabled))
	{
		print '<tr><td class="'.$fieldrequired.'titlefieldcreate">'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		print $formaccounting->select_account($object->account_number, 'account_number', 1, '', 1, 1);
		print '</td></tr>';
	}
	else
	{
		print '<tr><td class="'.$fieldrequired.'titlefieldcreate">'.$langs->trans("AccountancyCode").'</td>';
		print '<td><input type="text" name="account_number" value="'.(GETPOST("account_number")?GETPOST('account_number', 'alpha'):$object->account_number).'"></td></tr>';
	}

	// Accountancy journal
	if (! empty($conf->accounting->enabled))
	{
		print '<tr><td>'.$langs->trans("AccountancyJournal").'</td>';
		print '<td>';
		print $formaccounting->select_journal($object->fk_accountancy_journal, 'fk_accountancy_journal', 4, 1, 0, 0);
		print '</td></tr>';
	}

	print '</table>';

	dol_fiche_end();

	print '<div class="center">';
	print '<input type="submit" class="button" value="' . $langs->trans("CreateAccount") . '">';
	print '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;';
	print '<input type="button" class="button" value="' . $langs->trans("Cancel") . '" onClick="javascript:history.go(-1)">';
	print '</div>';

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
		$object = new Account($db);
		if ($_GET["id"])
		{
			$object->fetch($_GET["id"]);
		}
		if ($_GET["ref"])
		{
			$object->fetch(0,$_GET["ref"]);
			$_GET["id"]=$object->id;
		}

		// Show tabs
		$head=bank_prepare_head($object);
		dol_fiche_head($head, 'bankname', $langs->trans("FinancialAccount"), -1, 'account');

		$formconfirm = '';

		// Confirmation to delete
		if ($action == 'delete')
		{
			$formconfirm = $form->formconfirm($_SERVER["PHP_SELF"].'?id='.$object->id,$langs->trans("DeleteAccount"),$langs->trans("ConfirmDeleteAccount"),"confirm_delete");

		}

		// Print form confirm
		print $formconfirm;

		$linkback = '<a href="'.DOL_URL_ROOT.'/compta/bank/list.php?restore_lastsearch_values=1">'.$langs->trans("BackToList").'</a>';

		$morehtmlref='';
		dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


		print '<div class="fichecenter">';
		print '<div class="fichehalfleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		// Type
		print '<tr><td class="titlefield">'.$langs->trans("AccountType").'</td>';
		print '<td>'.$object->type_lib[$object->type].'</td></tr>';

		// Currency
		print '<tr><td>'.$langs->trans("Currency").'</td>';
		print '<td>';
		$selectedcode=$object->currency_code;
		if (! $selectedcode) $selectedcode=$conf->currency;
		print $langs->trans("Currency".$selectedcode);
		print '</td></tr>';

		// Conciliate
		print '<tr><td>'.$langs->trans("Conciliable").'</td>';
		print '<td>';
		$conciliate=$object->canBeConciliated();
		if ($conciliate == -2) print $langs->trans("No").' ('.$langs->trans("CashAccount").')';
		else if ($conciliate == -3) print $langs->trans("No").' ('.$langs->trans("Closed").')';
		else print ($object->rappro==1 ? $langs->trans("Yes") : ($langs->trans("No").' ('.$langs->trans("ConciliationDisabled").')'));
		print '</td></tr>';

		print '<tr><td>'.$langs->trans("BalanceMinimalAllowed").'</td>';
		print '<td>'.$object->min_allowed.'</td></tr>';

		print '<tr><td>'.$langs->trans("BalanceMinimalDesired").'</td>';
		print '<td>'.$object->min_desired.'</td></tr>';

		// Accountancy code
		print '<tr class="liste_titre_add"><td class="titlefield">'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		if (! empty($conf->accounting->enabled)) {
			$accountingaccount = new AccountingAccount($db);
			$accountingaccount->fetch('',$object->account_number, 1);

			print $accountingaccount->getNomUrl(0,1,1,'',1);
		} else {
			print $object->account_number;
		}
		print '</td></tr>';

		// Accountancy journal
		if (! empty($conf->accounting->enabled))
		{
			print '<tr><td>'.$langs->trans("AccountancyJournal").'</td>';
			print '<td>';

            if ($object->fk_accountancy_journal > 0) {
                $accountingjournal = new AccountingJournal($db);
                $accountingjournal->fetch($object->fk_accountancy_journal);

                print $accountingjournal->getNomUrl(0, 1, 1, '', 1);
            }
			print '</td></tr>';
		}

		// Other attributes
		$cols = 2;
		include DOL_DOCUMENT_ROOT . '/core/tpl/extrafields_view.tpl.php';

		print '</table>';

		print '</div>';
		print '<div class="fichehalfright">';
		print '<div class="ficheaddleft">';
		print '<div class="underbanner clearboth"></div>';

		print '<table class="border centpercent">';

		// Categories
		if ($conf->categorie->enabled) {
			print '<tr><td class="titlefield">'.$langs->trans("Categories").'</td><td>';
			print $form->showCategories($object->id,'bank_account',1);
			print "</td></tr>";
		}

		print '<tr><td class="tdtop titlefield">'.$langs->trans("Comment").'</td>';
		print '<td>'.dol_htmlentitiesbr($object->comment).'</td></tr>';

		print '</table>';

		if ($object->type == Account::TYPE_SAVINGS || $object->type == Account::TYPE_CURRENT)
		{

			print '<div class="underbanner clearboth"></div>';

			print '<table class="border centpercent">';

			print '<tr class="liste_titre"><td class="titlefield">'.$langs->trans("BankName").'</td>';
			print '<td>'.$object->bank.'</td></tr>';

			// Show fields of bank account
			foreach ($object->getFieldsToShow() as $val) {
				if ($val == 'BankCode') {
					$content = $object->code_banque;
				} elseif ($val == 'DeskCode') {
					$content = $object->code_guichet;
				} elseif ($val == 'BankAccountNumber') {
					$content = $object->number;
				} elseif ($val == 'BankAccountNumberKey') {
					$content = $object->cle_rib;
				}

				print '<tr><td>'.$langs->trans($val).'</td>';
				print '<td>'.$content.'</td>';
				print '</tr>';
			}

			$ibankey = FormBank::getIBANLabel($object);
			$bickey="BICNumber";
			if ($object->getCountryCode() == 'IN') $bickey="SWIFT";

			print '<tr><td>'.$langs->trans($ibankey).'</td>';
			print '<td>'.$object->iban.'&nbsp;';
			if (! empty($object->iban)) {
				if (! checkIbanForAccount($object)) {
					print img_picto($langs->trans("IbanNotValid"),'warning');
				} else {
					print img_picto($langs->trans("IbanValid"),'info');
				}
			}
			print '</td></tr>';

			print '<tr><td>'.$langs->trans($bickey).'</td>';
			print '<td>'.$object->bic.'&nbsp;';
			if (! empty($object->bic)) {
				if (! checkSwiftForAccount($object)) {
					print img_picto($langs->trans("SwiftNotValid"),'warning');
				} else {
					print img_picto($langs->trans("SwiftValid"),'info');
				}
			}
			print '</td></tr>';

			print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td>';
			print nl2br($object->domiciliation);
			print "</td></tr>\n";

			print '<tr><td>'.$langs->trans("BankAccountOwner").'</td><td>';
			print $object->proprio;
			print "</td></tr>\n";

			print '<tr><td>'.$langs->trans("BankAccountOwnerAddress").'</td><td>';
			print nl2br($object->owner_address);
			print "</td></tr>\n";

			print '</table>';
		}

		print '</div>';
		print '</div>';
		print '</div>';

		print '<div class="clearboth"></div>';

		dol_fiche_end();

		/*
		 * Barre d'actions
		 */
		print '<div class="tabsAction">';

		if ($user->rights->banque->configurer)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit&id='.$object->id.'">'.$langs->trans("Modify").'</a>';
		}

		$canbedeleted=$object->can_be_deleted();   // Renvoi vrai si compte sans mouvements
		if ($user->rights->banque->configurer && $canbedeleted)
		{
			print '<a class="butActionDelete" href="'.$_SERVER["PHP_SELF"].'?action=delete&id='.$object->id.'">'.$langs->trans("Delete").'</a>';
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
		$object = new Account($db);
		$object->fetch(GETPOST('id','int'));

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

		print '<form action="'.$_SERVER["PHP_SELF"].'?id='.$object->id.'" method="post" name="formsoc">';
		print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		print '<input type="hidden" name="action" value="update">';
		print '<input type="hidden" name="id" value="'.$_REQUEST["id"].'">'."\n\n";

		dol_fiche_head('');

		print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		// Ref
		print '<tr><td class="fieldrequired titlefieldcreate">'.$langs->trans("Ref").'</td>';
		print '<td><input size="8" type="text" class="flat" name="ref" value="'.dol_escape_htmltag(isset($_POST["ref"])?GETPOST("ref"):$object->ref).'"></td></tr>';

		// Label
		print '<tr><td class="fieldrequired">'.$langs->trans("Label").'</td>';
		print '<td><input type="text" class="flat minwidth300" name="label" value="'.dol_escape_htmltag(isset($_POST["label"])?GETPOST("label"):$object->label).'"></td></tr>';

		// Type
		print '<tr><td class="fieldrequired">'.$langs->trans("AccountType").'</td>';
		print '<td class="maxwidth200onsmartphone">';
		$formbank->selectTypeOfBankAccount((isset($_POST["type"])?$_POST["type"]:$object->type),"type");
		print '</td></tr>';

		// Currency
		print '<tr><td class="fieldrequired">'.$langs->trans("Currency");
		print '<input type="hidden" value="'.$object->currency_code.'">';
		print '</td>';
		print '<td class="maxwidth200onsmartphone">';
		$selectedcode=$object->currency_code;
		if (! $selectedcode) $selectedcode=$conf->currency;
		print $form->selectCurrency((isset($_POST["account_currency_code"])?$_POST["account_currency_code"]:$selectedcode), 'account_currency_code');
		//print $langs->trans("Currency".$conf->currency);
		//print '<input type="hidden" name="account_currency_code" value="'.$conf->currency.'">';
		print '</td></tr>';

		// Status
		print '<tr><td class="fieldrequired">'.$langs->trans("Status").'</td>';
		print '<td class="maxwidth200onsmartphone">';
		print $form->selectarray("clos", $object->status, (isset($_POST["clos"])?$_POST["clos"]:$object->clos));
		print '</td></tr>';

		// Country
		$object->country_id=$object->country_id?$object->country_id:$mysoc->country_id;
		$selectedcode=$object->country_code;
		if (isset($_POST["account_country_id"])) $selectedcode=$_POST["account_country_id"];
		else if (empty($selectedcode)) $selectedcode=$mysoc->country_code;
		$object->country_code = getCountry($selectedcode, 2);	// Force country code on account to have following field on bank fields matching country rules

		print '<tr><td class="fieldrequired">'.$langs->trans("Country").'</td>';
		print '<td class="maxwidth200onsmartphone">';
		print $form->select_country($selectedcode,'account_country_id');
		if ($user->admin) print info_admin($langs->trans("YouCanChangeValuesForThisListFromDictionarySetup"),1);
		print '</td></tr>';

		// State
		print '<tr><td>'.$langs->trans('State').'</td><td class="maxwidth200onsmartphone">';
		if ($selectedcode)
		{
			print $formcompany->select_state(isset($_POST["account_state_id"])?$_POST["account_state_id"]:$object->state_id,$selectedcode,'account_state_id');
		}
		else
		{
			print $countrynotdefined;
		}
		print '</td></tr>';

		// Conciliable
		print '<tr><td>'.$langs->trans("Conciliable").'</td>';
		print '<td>';
		$conciliate=$object->canBeConciliated();
		if ($conciliate == -2) print $langs->trans("No").' ('.$langs->trans("CashAccount").')';
		else if ($conciliate == -3) print $langs->trans("No").' ('.$langs->trans("Closed").')';
		else print '<input type="checkbox" class="flat" name="norappro"'.(($conciliate > 0)?'':' checked="checked"').'"> '.$langs->trans("DisableConciliation");
		print '</td></tr>';

		// Balance
		print '<tr><td>'.$langs->trans("BalanceMinimalAllowed").'</td>';
		print '<td><input size="12" type="text" class="flat" name="account_min_allowed" value="'.(isset($_POST["account_min_allowed"])?GETPOST("account_min_allowed"):$object->min_allowed).'"></td></tr>';

		print '<tr><td>'.$langs->trans("BalanceMinimalDesired").'</td>';
		print '<td ><input size="12" type="text" class="flat" name="account_min_desired" value="'.(isset($_POST["account_min_desired"])?GETPOST("account_min_desired"):$object->min_desired).'"></td></tr>';

		// Web
		print '<tr><td>'.$langs->trans("Web").'</td>';
		print '<td><input class="maxwidth200onsmartphone" type="text" class="flat" name="url" value="'.(isset($_POST["url"])?GETPOST("url"):$object->url).'">';
		print '</td></tr>';

		// Tags-Categories
		if ($conf->categorie->enabled)
		{
			print '<tr><td class="tdtop">'.$langs->trans("Categories").'</td><td>';
			$cate_arbo = $form->select_all_categories(Categorie::TYPE_ACCOUNT, '', 'parent', 64, 0, 1);
			$c = new Categorie($db);
			$cats = $c->containing($object->id,Categorie::TYPE_ACCOUNT);
			foreach($cats as $cat) {
				$arrayselected[] = $cat->id;
			}
			print $form->multiselectarray('categories', $cate_arbo, $arrayselected, '', 0, '', 0, '100%');
			print "</td></tr>";
		}

		// Comment
		print '<tr><td class="tdtop">'.$langs->trans("Comment").'</td>';
		print '<td>';
		// Editor wysiwyg
		require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
		$doleditor=new DolEditor('account_comment',(GETPOST("account_comment")?GETPOST("account_comment"):$object->comment),'',90,'dolibarr_notes','',false,true,$conf->global->FCKEDITOR_ENABLE_SOCIETE,ROWS_4,'95%');
		$doleditor->Create();
		print '</td></tr>';

		// Other attributes
		$parameters=array();
		$reshook=$hookmanager->executeHooks('formObjectOptions',$parameters,$object,$action);    // Note that $action and $object may have been modified by hook
		print $hookmanager->resPrint;
		if (empty($reshook) && ! empty($extrafields->attribute_label))
		{
			print $object->showOptionals($extrafields,'edit');
		}

		print '</table>';
		print '<br>';


		//print '<div class="underbanner clearboth"></div>';

		print '<table class="border" width="100%">';

		// Accountancy code
		$tdextra = ' class="titlefieldcreate"';

		if (!empty($conf->global->MAIN_BANK_ACCOUNTANCY_CODE_ALWAYS_REQUIRED)) {
			$tdextra = ' class="fieldrequired titlefieldcreate"';
		}

		print '<tr class="liste_titre_add"><td'.$tdextra.'>'.$langs->trans("AccountancyCode").'</td>';
		print '<td>';
		if (!empty($conf->accounting->enabled)) {
			print $formaccounting->select_account($object->account_number, 'account_number', 1, '', 1, 1);
		} else {
			print '<input type="text" name="account_number" value="'.(GETPOST("account_number") ? GETPOST("account_number") : $object->account_number).'">';
		}
		print '</td></tr>';

		// Accountancy journal
		if (! empty($conf->accounting->enabled))
		{
			print '<tr><td class="fieldrequired">'.$langs->trans("AccountancyJournal").'</td>';
			print '<td>';
			print $formaccounting->select_journal($object->fk_accountancy_journal, 'fk_accountancy_journal', 4, 1, 0, 0);
			print '</td></tr>';
		}

		print '</table>';

		if ($_POST["type"] == Account::TYPE_SAVINGS || $_POST["type"] == Account::TYPE_CURRENT)
		{
			print '<br>';

			//print '<div class="underbanner clearboth"></div>';

			print '<table class="border" width="100%">';

			// If bank account
			print '<tr class="liste_titre_add"><td class="titlefieldcreate">'.$langs->trans("BankName").'</td>';
			print '<td><input size="30" type="text" class="flat" name="bank" value="'.$object->bank.'"></td>';
			print '</tr>';

			// Show fields of bank account
			foreach ($object->getFieldsToShow() as $val) {
				if ($val == 'BankCode') {
					$name = 'code_banque';
					$size = 8;
					$content = $object->code_banque;
				} elseif ($val == 'DeskCode') {
					$name = 'code_guichet';
					$size = 8;
					$content = $object->code_guichet;
				} elseif ($val == 'BankAccountNumber') {
					$name = 'number';
					$size = 18;
					$content = $object->number;
				} elseif ($val == 'BankAccountNumberKey') {
					$name = 'cle_rib';
					$size = 3;
					$content = $object->cle_rib;
				}

				print '<tr><td>'.$langs->trans($val).'</td>';
				print '<td><input size="'.$size.'" type="text" class="flat" name="'.$name.'" value="'.$content.'"></td>';
				print '</tr>';
			}

			$ibankey = FormBank::getIBANLabel($object);
			$bickey="BICNumber";
			if ($object->getCountryCode() == 'IN') $bickey="SWIFT";

			// IBAN
			print '<tr><td>'.$langs->trans($ibankey).'</td>';
			print '<td><input class="minwidth300 maxwidth200onsmartphone" maxlength="34" type="text" class="flat" name="iban" value="'.$object->iban.'"></td></tr>';

			print '<tr><td>'.$langs->trans($bickey).'</td>';
			print '<td><input class="minwidth150 maxwidth200onsmartphone" maxlength="11" type="text" class="flat" name="bic" value="'.$object->bic.'"></td></tr>';

			print '<tr><td>'.$langs->trans("BankAccountDomiciliation").'</td><td>';
			print '<textarea class="flat quatrevingtpercent" name="domiciliation" rows="'.ROWS_2.'">';
			print $object->domiciliation;
			print "</textarea></td></tr>";

			print '<tr><td>'.$langs->trans("BankAccountOwner").'</td>';
			print '<td><input class="maxwidth200onsmartphone" type="text" class="flat" name="proprio" value="'.$object->proprio.'"></td>';
			print '</tr>';

			print '<tr><td>'.$langs->trans("BankAccountOwnerAddress").'</td><td>';
			print '<textarea class="flat quatrevingtpercent" name="owner_address" rows="'.ROWS_2.'">';
			print $object->owner_address;
			print "</textarea></td></tr>";

			print '</table>';
		}

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
