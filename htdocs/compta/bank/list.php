<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2019 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2018      Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2020      Tobias Sekan			<tobias.sekan@startmail.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *       \file       htdocs/compta/bank/list.php
 *       \ingroup    banque
 *       \brief      Home page of bank module
 */

require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/bank.lib.php';
require_once DOL_DOCUMENT_ROOT.'/compta/tva/class/tva.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/sociales/class/chargesociales.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcategory.class.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingaccount.class.php';
if (!empty($conf->accounting->enabled)) require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountingjournal.class.php';
if (!empty($conf->categorie->enabled)) require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'accountancy', 'compta'));

$action = GETPOST('action', 'alpha');
$massaction = GETPOST('massaction', 'alpha');
$show_files = GETPOST('show_files', 'int');
$confirm = GETPOST('confirm', 'alpha');
$toselect = GETPOST('toselect', 'array');
$contextpage = GETPOST('contextpage', 'aZ') ?GETPOST('contextpage', 'aZ') : 'bankaccountlist'; // To manage different context of search

$search_ref = GETPOST('search_ref', 'alpha');
$search_label = GETPOST('search_label', 'alpha');
$search_number = GETPOST('search_number', 'alpha');
$search_status = GETPOST('search_status') ?GETPOST('search_status', 'alpha') : 'opened'; // 'all' or ''='opened'
$optioncss = GETPOST('optioncss', 'alpha');

if (!empty($conf->categorie->enabled))
{
	$search_category_list = GETPOST("search_category_".Categorie::TYPE_ACCOUNT."_list", "array");
}

// Security check
if ($user->socid) $socid = $user->socid;
if (!empty($user->rights->accounting->chartofaccount)) $allowed = 1; // Dictionary with list of banks accounting account allowed to manager of chart account
if (!$allowed) $result = restrictedArea($user, 'banque');

$diroutputmassaction = $conf->bank->dir_output.'/temp/massgeneration/'.$user->id;

$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortfield) $sortfield = 'b.label';
if (!$sortorder) $sortorder = 'ASC';

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$object = new Account($db);
$hookmanager->initHooks(array('bankaccountlist'));
$extrafields = new ExtraFields($db);

// fetch optionals attributes and labels
$extrafields->fetch_name_optionals_label($object->table_element);
$search_array_options = $extrafields->getOptionalsFromPost($object->table_element, '', 'search_');

// List of fields to search into when doing a "search in all"
$fieldstosearchall = array(
    'b.ref'=>'Ref',
    'b.label'=>'Label',
);

$checkedtypetiers = 0;
$arrayfields = array(
    'b.ref'=>array('label'=>$langs->trans("BankAccounts"), 'checked'=>1),
    'accountype'=>array('label'=>$langs->trans("Type"), 'checked'=>1),
    'b.label'=>array('label'=>$langs->trans("Label"), 'checked'=>1),
    'b.number'=>array('label'=>$langs->trans("AccountIdShort"), 'checked'=>1),
    'b.account_number'=>array('label'=>$langs->trans("AccountAccounting"), 'checked'=>(!empty($conf->accounting->enabled) || !empty($conf->accounting->enabled))),
    'b.fk_accountancy_journal'=>array('label'=>$langs->trans("AccountancyJournal"), 'checked'=>(!empty($conf->accounting->enabled) || !empty($conf->accounting->enabled))),
    'toreconcile'=>array('label'=>$langs->trans("TransactionsToConciliate"), 'checked'=>1),
    'b.currency_code'=>array('label'=>$langs->trans("Currency"), 'checked'=>0),
	'b.datec'=>array('label'=>$langs->trans("DateCreation"), 'checked'=>0, 'position'=>500),
    'b.tms'=>array('label'=>$langs->trans("DateModificationShort"), 'checked'=>0, 'position'=>500),
    'b.clos'=>array('label'=>$langs->trans("Status"), 'checked'=>1, 'position'=>1000),
    'balance'=>array('label'=>$langs->trans("Balance"), 'checked'=>1, 'position'=>1010),
);
// Extra fields
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label']) > 0)
{
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val)
	{
		if (!empty($extrafields->attributes[$object->table_element]['list'][$key]))
			$arrayfields["ef.".$key] = array('label'=>$extrafields->attributes[$object->table_element]['label'][$key], 'checked'=>(($extrafields->attributes[$object->table_element]['list'][$key] < 0) ? 0 : 1), 'position'=>$extrafields->attributes[$object->table_element]['pos'][$key], 'enabled'=>(abs($extrafields->attributes[$object->table_element]['list'][$key]) != 3 && $extrafields->attributes[$object->table_element]['perms'][$key]));
	}
}
$object->fields = dol_sort_array($object->fields, 'position');
$arrayfields = dol_sort_array($arrayfields, 'position');


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) { $action = 'list'; $massaction = ''; }
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') { $massaction = ''; }

$parameters = array('socid'=>$socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');

include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) // All tests are required to be compatible with all browsers
{
    $search_ref = '';
    $search_label = '';
    $search_number = '';
    $search_status = '';
}


/*
 * View
 */

$form = new FormCategory($db);

$title = $langs->trans('BankAccounts');

// Load array of financial accounts (opened by default)
$accounts = array();

$sql = "SELECT b.rowid, b.label, b.courant, b.rappro, b.account_number, b.fk_accountancy_journal, b.currency_code, b.datec as date_creation, b.tms as date_update";
// Add fields from extrafields
if (!empty($extrafields->attributes[$object->table_element]['label'])) {
	foreach ($extrafields->attributes[$object->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$object->table_element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
}
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " FROM ".MAIN_DB_PREFIX."bank_account as b";
if (is_array($extrafields->attributes[$object->table_element]['label']) && count($extrafields->attributes[$object->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$object->table_element."_extrafields as ef on (b.rowid = ef.fk_object)";

if (!empty($conf->categorie->enabled))
{
	$sql .= Categorie::getFilterJoinQuery(Categorie::TYPE_ACCOUNT, "b.rowid");
}

$sql .= " WHERE b.entity IN (".getEntity('bank_account').")";
if ($search_status == 'opened')  $sql .= " AND clos = 0";
if ($search_status == 'closed')  $sql .= " AND clos = 1";

if (!empty($conf->categorie->enabled))
{
	$sql .= Categorie::getFilterSelectQuery(Categorie::TYPE_ACCOUNT, "b.rowid", $search_category_list);
}

if ($search_ref != '')    $sql .= natural_search('b.ref', $search_ref);
if ($search_label != '')  $sql .= natural_search('b.label', $search_label);
if ($search_number != '') $sql .= natural_search('b.number', $search_number);
// Add where from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
    $result = $db->query($sql);
    $nbtotalofrecords = $db->num_rows($result);
}

$sql .= $db->plimit($limit + 1, $offset);

$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;
    while ($i < $num)
    {
        $objp = $db->fetch_object($resql);
        $accounts[$objp->rowid] = $objp->courant;
        $i++;
    }
    $db->free($resql);
}
else dol_print_error($db);



$help_url = 'EN:Module_Banks_and_Cash|FR:Module_Banques_et_Caisses|ES:M&oacute;dulo_Bancos_y_Cajas';
llxHeader('', $title, $help_url);

$link = '';


$num_rows = count($accounts);

$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) $param .= '&contextpage='.$contextpage;
if ($limit > 0 && $limit != $conf->liste_limit) $param .= '&limit='.$limit;
if ($search_ref != '')      $param .= '&search_ref='.$search_ref;
if ($search_label != '')    $param .= '&search_label='.$search_label;
if ($search_number != '')   $param .= '&search_number='.$search_number;
if ($search_status != '')   $param .= '&search_status='.$search_status;
if ($show_files)            $param .= '&show_files='.$show_files;
if ($optioncss != '')       $param .= '&optioncss='.$optioncss;
// Add $param from extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

// List of mass actions available
$arrayofmassactions = array(
//    'presend'=>$langs->trans("SendByMail"),
//    'builddoc'=>$langs->trans("PDFMerge"),
);
if ($user->rights->banque->supprimer) $arrayofmassactions['predelete'] = '<span class="fa fa-trash paddingrightonly"></span>'.$langs->trans("Delete");
if (in_array($massaction, array('presend', 'predelete'))) $arrayofmassactions = array();
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

$newcardbutton = '';
if ($user->rights->banque->configurer)
{
    $newcardbutton .= dolGetButtonTitle($langs->trans('NewFinancialAccount'), '', 'fa fa-plus-circle', 'card.php?action=create');
}


// Lines of title fields
print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">';
if ($optioncss != '') print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="search_status" value="'.$search_status.'">';

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', $num, $nbtotalofrecords, 'bank_account', 0, $newcardbutton, '', $limit, 1);

$topicmail = "Information";
//$modelmail="subscription";
$objecttmp = new Account($db);
//$trackid='sub'.$object->id;
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

if ($sall)
{
    foreach ($fieldstosearchall as $key => $val) $fieldstosearchall[$key] = $langs->trans($val);
    print '<div class="divsearchfieldfilter">'.$langs->trans("FilterOnInto", $sall).join(', ', $fieldstosearchall).'</div>';
}

$moreforfilter = '';

if (!empty($conf->categorie->enabled))
{
	$moreforfilter .= $form->getFilterBox(Categorie::TYPE_ACCOUNT, $search_category_list);
}

// Bank accounts
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters); // Note that $action and $object may have been modified by hook
if (empty($reshook)) $moreforfilter .= $hookmanager->resPrint;
else $moreforfilter = $hookmanager->resPrint;

if (!empty($moreforfilter))
{
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage); // This also change content of $arrayfields

print '<div class="div-table-responsive">';
print '<table class="tagtable liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

print '<tr class="liste_titre_filter">';

// Ref
if (!empty($arrayfields['b.ref']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_ref" value="'.dol_escape_htmltag($search_ref).'">';
    print '</td>';
}
// Label
if (!empty($arrayfields['b.label']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_label" value="'.dol_escape_htmltag($search_label).'">';
    print '</td>';
}
// Account type
if (!empty($arrayfields['accountype']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Bank number
if (!empty($arrayfields['b.number']['checked']))
{
    print '<td class="liste_titre">';
    print '<input class="flat" size="6" type="text" name="search_number" value="'.dol_escape_htmltag($search_number).'">';
    print '</td>';
}
// Account number
if (!empty($arrayfields['b.account_number']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Accountancy journal
if (!empty($arrayfields['b.fk_accountancy_journal']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Transactions to reconcile
if (!empty($arrayfields['toreconcile']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Currency
if (!empty($arrayfields['b.currency_code']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_input.tpl.php';

// Fields from hook
$parameters = array('arrayfields'=>$arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Date creation
if (!empty($arrayfields['b.datec']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Date modification
if (!empty($arrayfields['b.tms']['checked']))
{
    print '<td class="liste_titre">';
    print '</td>';
}
// Status
if (!empty($arrayfields['b.clos']['checked']))
{
    print '<td class="liste_titre center">';
    $array = array(
        'opened'=>$langs->trans("Opened"),
        'closed'=>$langs->trans("Closed")
    );
    print $form->selectarray("search_status", $array, $search_status, 1);
    print '</td>';
}
// Balance
if (!empty($arrayfields['balance']['checked']))
{
    print '<td class="liste_titre"></td>';
}
// Action column
print '<td class="liste_titre valignmiddle">';
$searchpicto = $form->showFilterAndCheckAddButtons($massactionbutton ? 1 : 0, 'checkforselect', 1);
print $searchpicto;
print '</td>';
print '</tr>';

// Fields title
print '<tr class="liste_titre">';
if (!empty($arrayfields['b.ref']['checked']))            print_liste_field_titre($arrayfields['b.ref']['label'], $_SERVER["PHP_SELF"], 'b.ref', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['b.label']['checked']))          print_liste_field_titre($arrayfields['b.label']['label'], $_SERVER["PHP_SELF"], 'b.label', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['accountype']['checked']))       print_liste_field_titre($arrayfields['accountype']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['b.number']['checked']))         print_liste_field_titre($arrayfields['b.number']['label'], $_SERVER["PHP_SELF"], 'b.number', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['b.account_number']['checked'])) print_liste_field_titre($arrayfields['b.account_number']['label'], $_SERVER["PHP_SELF"], 'b.account_number', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['b.fk_accountancy_journal']['checked'])) print_liste_field_titre($arrayfields['b.fk_accountancy_journal']['label'], $_SERVER["PHP_SELF"], 'b.fk_accountancy_journal', '', $param, '', $sortfield, $sortorder);
if (!empty($arrayfields['b.currency_code']['checked']))  print_liste_field_titre($arrayfields['b.currency_code']['label'], $_SERVER["PHP_SELF"], 'b.currency_code', '', $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['toreconcile']['checked']))      print_liste_field_titre($arrayfields['toreconcile']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ');
// Extra fields
include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_title.tpl.php';
// Hook fields
$parameters = array('arrayfields'=>$arrayfields, 'param'=>$param, 'sortfield'=>$sortfield, 'sortorder'=>$sortorder);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
if (!empty($arrayfields['b.datec']['checked']))          print_liste_field_titre($arrayfields['b.datec']['label'], $_SERVER["PHP_SELF"], "b.datec", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
if (!empty($arrayfields['b.tms']['checked']))            print_liste_field_titre($arrayfields['b.tms']['label'], $_SERVER["PHP_SELF"], "b.tms", "", $param, '', $sortfield, $sortorder, 'center nowrap ');
if (!empty($arrayfields['b.clos']['checked']))           print_liste_field_titre($arrayfields['b.clos']['label'], $_SERVER["PHP_SELF"], 'b.clos', '', $param, '', $sortfield, $sortorder, 'center ');
if (!empty($arrayfields['balance']['checked']))          print_liste_field_titre($arrayfields['balance']['label'], $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ');
print_liste_field_titre($selectedfields, $_SERVER["PHP_SELF"], "", '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ');
print "</tr>\n";


$total = array(); $found = 0; $i = 0; $lastcurrencycode = '';

foreach ($accounts as $key=>$type)
{
	if ($i >= $limit) break;

    $found++;

	$result = $objecttmp->fetch($key);

	$solde = $objecttmp->solde(1);

	if (!empty($lastcurrencycode) && $lastcurrencycode != $objecttmp->currency_code)
	{
		$lastcurrencycode = 'various'; // We found several different currencies
	}
	if ($lastcurrencycode != 'various')
	{
		$lastcurrencycode = $objecttmp->currency_code;
	}

	print '<tr class="oddeven">';

    // Ref
    if (!empty($arrayfields['b.ref']['checked']))
    {
        print '<td class="nowrap">'.$objecttmp->getNomUrl(1).'</td>';
	    if (!$i) $totalarray['nbfield']++;
    }

    // Label
    if (!empty($arrayfields['b.label']['checked']))
    {
		print '<td>'.$objecttmp->label.'</td>';
        if (!$i) $totalarray['nbfield']++;
    }

    // Account type
    if (!empty($arrayfields['accountype']['checked']))
    {
        print '<td>';
		print $objecttmp->type_lib[$objecttmp->type];
		print '</td>';
        if (!$i) $totalarray['nbfield']++;
    }

    // Number
    if (!empty($arrayfields['b.number']['checked']))
    {
        print '<td>'.$objecttmp->number.'</td>';
	    if (!$i) $totalarray['nbfield']++;
    }

    // Account number
    if (!empty($arrayfields['b.account_number']['checked']))
    {
    	print '<td>';
    	if (!empty($conf->accounting->enabled) && !empty($objecttmp->account_number))
    	{
    		$accountingaccount = new AccountingAccount($db);
    		$accountingaccount->fetch('', $objecttmp->account_number, 1);
    		print $accountingaccount->getNomUrl(0, 1, 1, '', 1);
    	}
    	else
    	{
    		print $objecttmp->account_number;
    	}
    	print '</td>';
	    if (!$i) $totalarray['nbfield']++;
    }

    // Accountancy journal
    if (!empty($arrayfields['b.fk_accountancy_journal']['checked']))
    {
    	print '<td>';
    	if (!empty($conf->accounting->enabled) && !empty($objecttmp->fk_accountancy_journal))
    	{
    		$accountingjournal = new AccountingJournal($db);
    		$accountingjournal->fetch($objecttmp->fk_accountancy_journal);
    		print $accountingjournal->getNomUrl(0, 1, 1, '', 1);
    	}
    	else
    	{
    		print '';
    	}
    	print '</td>';
		if (!$i) $totalarray['nbfield']++;
    }

    // Currency
    if (!empty($arrayfields['b.currency_code']['checked']))
    {
    	print '<td class="center">';
   		print $objecttmp->currency_code;
    	print '</td>';
    	if (!$i) $totalarray['nbfield']++;
    }

    // Transactions to reconcile
    if (!empty($arrayfields['toreconcile']['checked']))
    {
    	print '<td class="center">';

    	$conciliate = $objecttmp->canBeConciliated();
    	if ($conciliate == -2) print '<span class="opacitymedium">'.$langs->trans("CashAccount").'</span>';
    	elseif ($conciliate == -3) print '<span class="opacitymedium">'.$langs->trans("Closed").'</span>';
    	elseif (empty($objecttmp->rappro)) {
    		print '<span class="opacitymedium">'.$langs->trans("ConciliationDisabled").'</span>';
    	} else {
    		$result = $objecttmp->load_board($user, $objecttmp->id);
    		if ($result < 0) {
    			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
    		} else {
    			print '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?id='.$objecttmp->id.'&search_conciliated=0">';
    			print '<span class="badge badge-info classfortooltip" title="'.dol_htmlentities($langs->trans("TransactionsToConciliate")).'">';
    			print $result->nbtodo;
    			print '</span>';
				print '</a>';
    			if ($result->nbtodolate) {
    				print '<span title="'.dol_htmlentities($langs->trans("Late")).'" class="classfortooltip badge badge-danger marginleftonlyshort">';
    				print '<i class="fa fa-exclamation-triangle"></i> '.$result->nbtodolate;
    				print '</span>';
    			}
    		}
    	}

    	print '</td>';
	    if (!$i) $totalarray['nbfield']++;
    }

    // Extra fields
	if (is_array($objecttmp->array_options)) {
		$obj = new stdClass();
		foreach ($objecttmp->array_options as $k => $v) {
			$obj->$k = $v;
		}
	}
    include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_print_fields.tpl.php';
    // Fields from hook
    $parameters = array('arrayfields'=>$arrayfields, 'obj'=>$obj, 'i'=>$i, 'totalarray'=>&$totalarray);
    $reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $objecttmp); // Note that $action and $objecttmpect may have been modified by hook
    print $hookmanager->resPrint;
	// Date creation
    if (!empty($arrayfields['b.datec']['checked']))
    {
        print '<td class="center">';
        print dol_print_date($objecttmp->date_creation, 'dayhour');
        print '</td>';
	    if (!$i) $totalarray['nbfield']++;
    }
    // Date modification
    if (!empty($arrayfields['b.tms']['checked']))
    {
        print '<td class="center">';
        print dol_print_date($objecttmp->date_update, 'dayhour');
        print '</td>';
	    if (!$i) $totalarray['nbfield']++;
    }

    // Status
    if (!empty($arrayfields['b.clos']['checked']))
    {
		print '<td class="center">'.$objecttmp->getLibStatut(5).'</td>';
	    if (!$i) $totalarray['nbfield']++;
    }

    // Balance
    if (!empty($arrayfields['balance']['checked']))
    {
		print '<td class="nowraponall right">';
		print '<a href="'.DOL_URL_ROOT.'/compta/bank/bankentries_list.php?id='.$objecttmp->id.'">'.price($solde, 0, $langs, 0, -1, -1, $objecttmp->currency_code).'</a>';
		print '</td>';
		if (!$i) $totalarray['nbfield']++;
		if (!$i) $totalarray['pos'][$totalarray['nbfield']] = 'balance';
		$totalarray['val']['balance'] += $solde;
    }

	// Action column
	print '<td class="nowrap center">';
	if ($massactionbutton || $massaction)   // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
	{
	    $selected = 0;
	    if (in_array($objecttmp->id, $arrayofselected)) $selected = 1;
	    print '<input id="cb'.$objecttmp->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$objecttmp->id.'"'.($selected ? ' checked="checked"' : '').'>';
	}
	print '</td>';
	if (!$i) $totalarray['nbfield']++;

	print '</tr>';

	$total[$objecttmp->currency_code] += $solde;

	$i++;
}

// If no record found
if (!$found)
{
    $colspan = 1;
    foreach ($arrayfields as $key => $val) { if (!empty($val['checked'])) $colspan++; }
    print '<tr><td colspan="'.$colspan.'" class="opacitymedium">'.$langs->trans("NoRecordFound").'</td></tr>';
}

// Show total line
if ($lastcurrencycode != 'various')	// If there is several currency, $lastcurrencycode is set to 'various' before
{
	// Show total line
	include DOL_DOCUMENT_ROOT.'/core/tpl/list_print_total.tpl.php';
}

print '</table>';
print "</div>";

print "</form>";

// End of page
llxFooter();
$db->close();
