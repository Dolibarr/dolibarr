<?php
/* Copyright (C) 2005		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2017	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2012	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * 	\file       htdocs/compta/prelevement/orders_list.php
 * 	\ingroup    prelevement
 * 	\brief      Page to list direct debit orders or credit transfer orders
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/compta/prelevement/class/bonprelevement.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var Form $form
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array('banks', 'categories', 'withdrawals'));

$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'add', 'create', 'edit', 'update', 'view', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect = GETPOST('toselect', 'array'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'directdebitcredittransferlist'; // To manage different context of search
$backtopage = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss = GETPOST('optioncss', 'alpha');
$mode = GETPOST('mode', 'alpha');

$type = GETPOST('type', 'aZ09');

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;
if (!$sortorder) {
	$sortorder = "DESC";
}
if (!$sortfield) {
	$sortfield = "p.datec";
}

// Get supervariables
$statut = GETPOSTINT('statut');
$search_ref = GETPOST('search_ref', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');

$bon = new BonPrelevement($db);
$hookmanager->initHooks(array('withdrawalsreceiptslist'));

$usercancreate = $user->hasRight('prelevement', 'bons', 'creer');
$permissiontodelete = $user->hasRight('prelevement', 'creer');
if ($type == 'bank-transfer') {
	$usercancreate = $user->hasRight('paymentbybanktransfer', 'create');
	$permissiontodelete = $user->hasRight('paymentbybanktransfer', 'create');
}

// Security check
$socid = GETPOSTINT('socid');
if ($user->socid) {
	$socid = $user->socid;
}
if ($type == 'bank-transfer') {
	$result = restrictedArea($user, 'paymentbybanktransfer', '', '', '');
} else {
	$result = restrictedArea($user, 'prelevement', '', '', 'bons');
}


/*
 * Actions
 */
$error = 0;

if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_ref = "";
	$search_amount = "";
}

// Mass actions

// Delete draft
if (($massaction == "delete" || ($action == 'delete' && $confirm == 'yes')) && $permissiontodelete) {
	$TMsg = array();
	$db->begin();
	$objecttmp = new BonPrelevement($db);
	$nbignored = 0;
	$nbok = 0;
	foreach ($toselect as $toselectid) {
		$result = $objecttmp->fetch($toselectid);
		if ($result > 0) {
			if ($objecttmp->status != $objecttmp::STATUS_DRAFT || $objecttmp->credite > 0 || $objecttmp->date_creation != null) {
				$langs->load("errors");
				$nbignored++;
				$TMsg[] = '<div class="error">'.$langs->trans('ErrorOnlyDraftStatusCanBeDeletedInMassAction', $objecttmp->ref).'</div><br>';
				continue;
			}
			$result = $objecttmp->delete($user);
			if ($result < 0) { // if delete returns is < 0, there is an error, we break and rollback later
				setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
				$error++;
				break;
			} else {
				$nbok++;
			}
		} else {
			setEventMessages($objecttmp->error, $objecttmp->errors, 'errors');
			$error++;
			break;
		}
	}
	if (empty($error)) {
		// Message for elements well deleted
		if ($nbok > 1) {
			setEventMessages($langs->trans("RecordsDeleted", $nbok), null, 'mesgs');
		} elseif ($nbok > 0) {
			setEventMessages($langs->trans("RecordDeleted", $nbok), null, 'mesgs');
		} else {
			setEventMessages($langs->trans("NoRecordDeleted"), null, 'mesgs');
		}

		// Message for elements which can't be deleted
		if (!empty($TMsg)) {
			sort($TMsg);
			setEventMessages('', array_unique($TMsg), 'warnings');
		}

		$db->commit();
	} else {
		$db->rollback();
	}
	$massaction = '';
}
$objectclass = 'BonPrelevement';
$objectlabel = 'BonPrelevement';
if ($type == 'bank-transfer') {
	$uploaddir = $conf->paymentbybanktransfer->dir_output;
} else {
	$uploaddir = $conf->prelevement->dir_output;
}
include DOL_DOCUMENT_ROOT.'/core/actions_massactions.inc.php';

/*
 * View
 */

$directdebitorder = new BonPrelevement($db);

$titlekey = "WithdrawalsReceipts";
$title = $langs->trans("WithdrawalsReceipts");
if ($type == 'bank-transfer') {
	$titlekey = "BankTransferReceipts";
	$title = $langs->trans("BankTransferReceipts");
}
$help_url = '';


$sql = "SELECT p.rowid, p.ref, p.amount, p.statut, p.datec";

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".MAIN_DB_PREFIX."prelevement_bons as p";
$sql .= " WHERE p.entity IN (".getEntity('invoice').")";
if ($type == 'bank-transfer') {
	$sql .= " AND p.type = 'bank-transfer'";
} else {
	$sql .= " AND p.type = 'debit-order'";
}
if ($search_ref) {
	$sql .= natural_search("p.ref", $search_ref);
}
if ($search_amount) {
	$sql .= natural_search("p.amount", $search_amount, 1);
}

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);
	$sqlforcount = preg_replace('/GROUP BY .*$/', '', $sqlforcount);
	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Complete request and execute it with limit
$sql .= $db->order($sortfield, $sortorder);
if ($limit) {
	$sql .= $db->plimit($limit + 1, $offset);
}

$resql = $db->query($sql);
if (!$resql) {
	dol_print_error($db);
	exit;
}

$num = $db->num_rows($resql);

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist');

$arrayofselected = is_array($toselect) ? $toselect : array();
$param = '';
$param .= "&statut=".urlencode((string) ($statut));
if ($type == 'bank-transfer') {
	$param .= '&type=bank-transfer';
}
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}

$arrayofmassactions = array(
	//'presend'=>img_picto('', 'email', 'class="pictofixedwidth"').$langs->trans("SendByMail"),
	//'builddoc'=>img_picto('', 'pdf', 'class="pictofixedwidth"').$langs->trans("PDFMerge"),
);
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predeletedraft'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'">'."\n";
print '<input type="hidden" name="token" value="'.newToken().'">';
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';

if ($type != '') {
	print '<input type="hidden" name="type" value="'.$type.'">';
}

$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewList'), '', 'fa fa-bars imgforviewmode', $_SERVER["PHP_SELF"].'?mode=common'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ((empty($mode) || $mode == 'common') ? 2 : 1), array('morecss' => 'reposition'));
$newcardbutton .= dolGetButtonTitle($langs->trans('ViewKanban'), '', 'fa fa-th-list imgforviewmode', $_SERVER["PHP_SELF"].'?mode=kanban'.preg_replace('/(&|\?)*mode=[^&]+/', '', $param), '', ($mode == 'kanban' ? 2 : 1), array('morecss' => 'reposition'));
if ($usercancreate) {
	$newcardbutton .= dolGetButtonTitleSeparator();
	$newcardbutton .= dolGetButtonTitle($langs->trans('NewStandingOrder'), '', 'fa fa-plus-circle', DOL_URL_ROOT.'/compta/prelevement/create.php?type='.urlencode($type));
}

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'generic', 0, $newcardbutton, '', $limit, 0, 0, 1);

include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';


$moreforfilter = '';
/*$moreforfilter.='<div class="divsearchfield">';
 $moreforfilter.= $langs->trans('MyFilter') . ': <input type="text" name="search_myfield" value="'.dol_escape_htmltag($search_myfield).'">';
 $moreforfilter.= '</div>';*/

$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldPreListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
if (empty($reshook)) {
	$moreforfilter .= $hookmanager->resPrint;
} else {
	$moreforfilter = $hookmanager->resPrint;
}

if (!empty($moreforfilter)) {
	print '<div class="liste_titre liste_titre_bydiv centpercent">';
	print $moreforfilter;
	print '</div>';
}

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN'));  // This also change content of $arrayfields with user setup
$selectedfields = ($mode != 'kanban' ? $htmlofselectarray : '');
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';
print '<td class="liste_titre">&nbsp;</td>';
print '<td class="liste_titre right"><input type="text" class="flat maxwidth100" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';
print '<td class="liste_titre">&nbsp;</td>';
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}
print '</tr>'."\n";

$totalarray = array();
$totalarray['nbfield'] = 0;

// Fields title label
// --------------------------------------------------------------------
print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print_liste_field_titre($titlekey, $_SERVER["PHP_SELF"], "p.ref", '', $param, '', $sortfield, $sortorder);
$totalarray['nbfield']++;
print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "p.datec", "", $param, '', $sortfield, $sortorder, 'center ');
$totalarray['nbfield']++;
print_liste_field_titre("Amount", $_SERVER["PHP_SELF"], "p.amount", "", $param, '', $sortfield, $sortorder, 'right ');
$totalarray['nbfield']++;
print_liste_field_titre("Status", $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'right ');
$totalarray['nbfield']++;
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
print '</tr>'."\n";

// Loop on record
// --------------------------------------------------------------------

$i = 0;
$savnbfield = $totalarray['nbfield'];
$totalarray = array();
$totalarray['nbfield'] = 0;

$imaxinloop = ($limit ? min($num, $limit) : $num);
while ($i < $imaxinloop) {
	$obj = $db->fetch_object($resql);

	$directdebitorder->id = $obj->rowid;
	$directdebitorder->ref = $obj->ref;
	$directdebitorder->date_echeance = $obj->datec;
	$directdebitorder->total = $obj->amount;
	$directdebitorder->statut = $obj->statut;

	$object = $directdebitorder;

	if ($mode == 'kanban') {
		if ($i == 0) {
			print '<tr class="trkanban"><td colspan="'.$savnbfield.'">';
			print '<div class="box-flex-container kanban">';
		}
		// Output Kanban
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($object->id, $arrayofselected)) {
				$selected = 1;
			}
		}
		print $directdebitorder->getKanbanView('', array('selected' => in_array($obj->id, $arrayofselected)));
		if ($i == ($imaxinloop - 1)) {
			print '</div>';
			print '</td></tr>';
		}
	} else {
		// Show line of result
		$j = 0;
		print '<tr data-rowid="'.$object->id.'" class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '<td>';
		print $directdebitorder->getNomUrl(1);
		print "</td>\n";

		print '<td class="center">'.dol_print_date($db->jdate($obj->datec), 'day')."</td>\n";

		print '<td class="right"><span class="amount">'.price($obj->amount)."</span></td>\n";

		print '<td class="right">';
		print $bon->LibStatut($obj->statut, 5);
		print '</td>';

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="nowrap center">';
			if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
				$selected = 0;
				if (in_array($object->id, $arrayofselected)) {
					$selected = 1;
				}
				print '<input id="cb'.$object->id.'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.$object->id.'"'.($selected ? ' checked="checked"' : '').'>';
			}
			print '</td>';
			if (!$i) {
				$totalarray['nbfield']++;
			}
		}

		print '</tr>'."\n";
	}
	$i++;
}

if ($num == 0) {
	print '<tr><td colspan="5"><span class="opacitymedium">'.$langs->trans("None").'</span></td></tr>';
}

$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// End of page
llxFooter();
$db->close();
