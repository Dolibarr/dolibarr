<?php
/* Copyright (C) 2004-2023  Laurent Destailleur      <eldy@users.sourceforge.net>
 * Copyright (C) 2011-2024  Alexandre Spangaro       <aspangaro@easya.solutions>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	    \file       htdocs/accountancy/admin/categories_list.php
 *		\ingroup    setup
 *		\brief      Page to administer data tables
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formcompany.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formaccounting.class.php';
require_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancycategory.class.php';

// Load translation files required by the page
$langs->loadLangs(array("errors", "admin", "companies", "resource", "holiday", "accountancy", "hrm"));

$action = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view';
$confirm = GETPOST('confirm', 'alpha');
$id = 32;
$rowid = GETPOST('rowid', 'alpha');
$code = GETPOST('code', 'alpha');

// Security access
if (!$user->hasRight('accounting', 'chartofaccount')) {
	accessforbidden();
}

$acts = array();
$acts[0] = "activate";
$acts[1] = "disable";
$actl = array();
$actl[0] = img_picto($langs->trans("Disabled"), 'switch_off', 'class="size15x"');
$actl[1] = img_picto($langs->trans("Activated"), 'switch_on', 'class="size15x"');

$listoffset = GETPOST('listoffset', 'alpha');
$listlimit = GETPOSTINT('listlimit') > 0 ? GETPOSTINT('listlimit') : 1000;

$sortfield = GETPOST("sortfield", 'aZ09comma');
$sortorder = GETPOST("sortorder", 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $listlimit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$search_country_id = GETPOSTINT('search_country_id');

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admin'));

// This page is a generic page to edit dictionaries
// Put here declaration of dictionaries properties

// Sort order to show dictionary (0 is space). All other dictionaries (added by modules) will be at end of this.
$taborder = array(32);

// Name of SQL tables of dictionaries
$tabname = array();
$tabname[32] = MAIN_DB_PREFIX."c_accounting_category";

// Dictionary labels
$tablib = array();
$tablib[32] = "DictionaryAccountancyCategory";

// Requests to extract data
$tabsql = array();
$tabsql[32] = "SELECT a.rowid as rowid, a.code as code, a.label, a.range_account, a.category_type, a.formula, a.position as position, a.fk_country as country_id, c.code as country_code, c.label as country, a.active FROM ".MAIN_DB_PREFIX."c_accounting_category as a, ".MAIN_DB_PREFIX."c_country as c WHERE a.fk_country=c.rowid and c.active=1";

// Criteria to sort dictionaries
$tabsqlsort = array();
$tabsqlsort[32] = "position ASC";

// Name of the fields in the result of select to display the dictionary
$tabfield = array();
$tabfield[32] = "code,label,range_account,category_type,formula,position,country";

// Name of editing fields for record modification
$tabfieldvalue = array();
$tabfieldvalue[32] = "code,label,range_account,category_type,formula,position,country_id";

// Name of the fields in the table for inserting a record
$tabfieldinsert = array();
$tabfieldinsert[32] = "code,label,range_account,category_type,formula,position,fk_country";

// Name of the rowid if the field is not of type autoincrement
// Example: "" if id field is "rowid" and has autoincrement on
//          "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid = array();
$tabrowid[32] = "";

// Condition to show dictionary in setup page
$tabcond = array();
$tabcond[32] = isModEnabled('accounting');

// List of help for fields
$tabhelp = array();
$tabhelp[32] = array('code' => $langs->trans("EnterAnyCode"), 'category_type' => $langs->trans("SetToYesIfGroupIsComputationOfOtherGroups"), 'formula' => $langs->trans("EnterCalculationRuleIfPreviousFieldIsYes"));

// List of check for fields (NOT USED YET)
$tabfieldcheck = array();
$tabfieldcheck[32] = array();

// Complete all arrays with entries found into modules
complete_dictionary_with_modules($taborder, $tabname, $tablib, $tabsql, $tabsqlsort, $tabfield, $tabfieldvalue, $tabfieldinsert, $tabrowid, $tabcond, $tabhelp, $tabfieldcheck);

$accountingcategory = new AccountancyCategory($db);


/*
 * Actions
 */

if (GETPOST('button_removefilter', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter_x', 'alpha')) {
	$search_country_id = '';
}

// Actions add or modify an entry into a dictionary
if (GETPOST('actionadd', 'alpha') || GETPOST('actionmodify', 'alpha')) {
	$listfield = explode(',', str_replace(' ', '', $tabfield[$id]));
	$listfieldinsert = explode(',', $tabfieldinsert[$id]);
	$listfieldmodify = explode(',', $tabfieldinsert[$id]);
	$listfieldvalue = explode(',', $tabfieldvalue[$id]);

	// Check that all fields are filled
	$ok = 1;
	foreach ($listfield as $f => $value) {
		if ($value == 'formula' && !GETPOST('formula')) {
			continue;
		}
		if ($value == 'range_account' && !GETPOST('range_account')) {
			continue;
		}
		if (($value == 'country' || $value == 'country_id') && GETPOST('country_id')) {
			continue;
		}
		if (!GETPOSTISSET($value) || GETPOST($value) == '') {
			$ok = 0;
			$fieldnamekey = $listfield[$f];
			// We take translate key of field
			if ($fieldnamekey == 'libelle' || ($fieldnamekey == 'label')) {
				$fieldnamekey = 'Label';
			}
			if ($fieldnamekey == 'code') {
				$fieldnamekey = 'Code';
			}
			if ($fieldnamekey == 'note') {
				$fieldnamekey = 'Note';
			}
			if ($fieldnamekey == 'type') {
				$fieldnamekey = 'Type';
			}
			if ($fieldnamekey == 'position') {
				$fieldnamekey = 'Position';
			}
			if ($fieldnamekey == 'category_type') {
				$fieldnamekey = 'Calculated';
			}
			if ($fieldnamekey == 'country') {
				$fieldnamekey = 'Country';
			}

			setEventMessages($langs->transnoentities("ErrorFieldRequired", $langs->transnoentities($fieldnamekey)), null, 'errors');
		}
	}
	if (GETPOSTISSET("code")) {
		if (GETPOST("code") == '0') {
			$ok = 0;
			setEventMessages($langs->transnoentities('ErrorCodeCantContainZero'), null, 'errors');
		}
	}
	if (GETPOST('position') && !is_numeric(GETPOST('position', 'alpha'))) {
		$langs->loadLangs(array("errors"));
		$ok = 0;
		setEventMessages($langs->transnoentities('ErrorFieldMustBeANumeric', $langs->transnoentities("Position")), null, 'errors');
	}

	// Si verif ok et action add, on ajoute la ligne
	if ($ok && GETPOST('actionadd', 'alpha')) {
		$newid = 0;

		if ($tabrowid[$id]) {
			// Get free id for insert
			$sql = "SELECT MAX(".$db->sanitize($tabrowid[$id]).") newid FROM ".$db->sanitize($tabname[$id]);
			$result = $db->query($sql);
			if ($result) {
				$obj = $db->fetch_object($result);
				$newid = ($obj->newid + 1);
			} else {
				dol_print_error($db);
			}
		}

		// Add new entry
		$sql = "INSERT INTO ".$db->sanitize($tabname[$id])." (";
		// List of fields
		if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
			$sql .= $db->sanitize($tabrowid[$id]).",";
		}
		$sql .= $db->sanitize($tabfieldinsert[$id]);
		$sql .= ",active)";
		$sql .= " VALUES(";

		// List of values
		if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldinsert)) {
			$sql .= $newid.",";
		}
		$i = 0;
		foreach ($listfieldinsert as $f => $value) {
			if ($value == 'entity') {
				$_POST[$listfieldvalue[$i]] = $conf->entity;
			}
			if ($i) {
				$sql .= ",";
			}
			if (GETPOST($listfieldvalue[$i]) == '' && !$listfieldvalue[$i] == 'formula') {
				$sql .= "null"; // For vat, we want/accept code = ''
			} else {
				$sql .= "'".$db->escape(GETPOST($listfieldvalue[$i]))."'";
			}
			$i++;
		}
		$sql .= ",1)";

		dol_syslog("actionadd", LOG_DEBUG);
		$result = $db->query($sql);
		if ($result) {	// Add is ok
			setEventMessages($langs->transnoentities("RecordSaved"), null, 'mesgs');
			$_POST = array('id' => $id); // Clean $_POST array, we keep only
		} else {
			if ($db->errno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
				setEventMessages($langs->transnoentities("ErrorRecordAlreadyExists"), null, 'errors');
			} else {
				dol_print_error($db);
			}
		}
	}

	// If check ok and action modify, we modify the line
	if ($ok && GETPOST('actionmodify', 'alpha')) {
		if ($tabrowid[$id]) {
			$rowidcol = $tabrowid[$id];
		} else {
			$rowidcol = "rowid";
		}

		// Modify entry
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET ";
		// Modifie valeur des champs
		if ($tabrowid[$id] && !in_array($tabrowid[$id], $listfieldmodify)) {
			$sql .= $db->sanitize($tabrowid[$id])." = ";
			$sql .= "'".$db->escape($rowid)."', ";
		}
		$i = 0;
		foreach ($listfieldmodify as $field) {
			if ($field == 'fk_country' && GETPOST('country') > 0) {
				$_POST[$listfieldvalue[$i]] = GETPOST('country');
			} elseif ($field == 'entity') {
				$_POST[$listfieldvalue[$i]] = $conf->entity;
			}
			if ($i) {
				$sql .= ",";
			}
			$sql .= $field."=";
			if (GETPOST($listfieldvalue[$i]) == '' && !$listfieldvalue[$i] == 'range_account') {
				$sql .= "null"; // For range_account, we want/accept code = ''
			} else {
				$sql .= "'".$db->escape(GETPOST($listfieldvalue[$i]))."'";
			}
			$i++;
		}
		$sql .= " WHERE ".$rowidcol." = ".((int) $rowid);

		dol_syslog("actionmodify", LOG_DEBUG);
		//print $sql;
		$resql = $db->query($sql);
		if (!$resql) {
			setEventMessages($db->error(), null, 'errors');
		}
	}
	//$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
}

// if (GETPOST('actioncancel', 'alpha')) {
// 	$_GET["id"]=GETPOST('id', 'int');       // Force affichage dictionnaire en cours d'edition
// }

if ($action == 'confirm_delete' && $confirm == 'yes') {       // delete
	$rowidcol = "rowid";

	$sql = "DELETE from ".$db->sanitize($tabname[$id])." WHERE ".$db->sanitize($rowidcol)." = ".((int) $rowid);

	dol_syslog("delete", LOG_DEBUG);
	$result = $db->query($sql);
	if (!$result) {
		if ($db->errno() == 'DB_ERROR_CHILD_EXISTS') {
			setEventMessages($langs->transnoentities("ErrorRecordIsUsedByChild"), null, 'errors');
		} else {
			dol_print_error($db);
		}
	}
}

// activate
if ($action == $acts[0]) {
	$sql = '';
	$rowidcol = "rowid";

	if ($rowid) {
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET active = 1 WHERE ".$db->sanitize($rowidcol)." = ".((int) $rowid);
	} elseif ($code) {
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET active = 1 WHERE code = '".$db->escape($code)."'";
	}

	if ($sql) {
		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}
}

// disable
if ($action == $acts[1]) {
	$sql = '';
	$rowidcol = "rowid";

	if ($rowid) {
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET active = 0 WHERE ".$db->sanitize($rowidcol)." = ".((int) $rowid);
	} elseif ($code) {
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET active = 0 WHERE code = '".$db->escape($code)."'";
	}

	if ($sql) {
		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}
}

// favorite
if ($action == 'activate_favorite') {
	$sql = '';
	$rowidcol = "rowid";

	if ($rowid) {
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET favorite = 1 WHERE ".$db->sanitize($rowidcol)." = ".((int) $rowid);
	} elseif ($code) {
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET favorite = 1 WHERE code = '".$db->escape($code)."'";
	}

	if ($sql) {
		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}
}

// disable favorite
if ($action == 'disable_favorite') {
	$sql = '';
	$rowidcol = "rowid";

	if ($rowid) {
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET favorite = 0 WHERE ".$db->sanitize($rowidcol)." = ".((int) $rowid);
	} elseif ($code) {
		$sql = "UPDATE ".$db->sanitize($tabname[$id])." SET favorite = 0 WHERE code = '".$db->escape($code)."'";
	}

	if ($sql) {
		$result = $db->query($sql);
		if (!$result) {
			dol_print_error($db);
		}
	}
}


/*
 * View
 */

$form = new Form($db);
$formadmin = new FormAdmin($db);

$help_url = 'EN:Module_Double_Entry_Accounting#Setup|FR:Module_Comptabilit&eacute;_en_Partie_Double#Configuration';

llxHeader('', $langs->trans('DictionaryAccountancyCategory'), $help_url);

$titre = $langs->trans($tablib[$id]);
$linkback = '';
$titlepicto = 'setup';

print load_fiche_titre($titre, $linkback, $titlepicto);

print '<span class="opacitymedium">'.$langs->trans("AccountingAccountGroupsDesc", $langs->transnoentitiesnoconv("ByPersonalizedAccountGroups")).'</span><br><br>';

// Confirmation of the deletion of the line
if ($action == 'delete') {
	print $form->formconfirm($_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.$rowid.'&code='.$code.'&id='.$id.($search_country_id > 0 ? '&search_country_id='.$search_country_id : ''), $langs->trans('DeleteLine'), $langs->trans('ConfirmDeleteLine'), 'confirm_delete', '', 0, 1);
}

// Complete search query with sorting criteria
$sql = $tabsql[$id];

if ($search_country_id > 0) {
	if (preg_match('/ WHERE /', $sql)) {
		$sql .= " AND ";
	} else {
		$sql .= " WHERE ";
	}
	$sql .= " (a.fk_country = ".((int) $search_country_id)." OR a.fk_country = 0)";
}

// If sort order is "country", we use country_code instead
if ($sortfield == 'country') {
	$sortfield = 'country_code';
}
if (empty($sortfield)) {
	$sortfield = 'position';
}

$sql .= $db->order($sortfield, $sortorder);
$sql .= $db->plimit($listlimit + 1, $offset);


$fieldlist = explode(',', $tabfield[$id]);

$param = '&id='.$id;
if ($search_country_id > 0) {
	$param .= '&search_country_id='.urlencode((string) ($search_country_id));
}
$paramwithsearch = $param;
if ($sortorder) {
	$paramwithsearch .= '&sortorder='.urlencode($sortorder);
}
if ($sortfield) {
	$paramwithsearch .= '&sortfield='.urlencode($sortfield);
}
if (GETPOST('from', 'alpha')) {
	$paramwithsearch .= '&from='.urlencode(GETPOST('from', 'alpha'));
}
if ($listlimit) {
	$paramwithsearch .= '&listlimit='.urlencode((string) (GETPOSTINT('listlimit')));
}
print '<form action="'.$_SERVER['PHP_SELF'].'?id='.$id.'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="from" value="'.dol_escape_htmltag(GETPOST('from', 'alpha')).'">';
print '<input type="hidden" name="sortfield" value="'.dol_escape_htmltag($sortfield).'">';
print '<input type="hidden" name="sortorder" value="'.dol_escape_htmltag($sortorder).'">';


print '<div class="div-table-responsive-no-min">';
print '<table class="noborder centpercent">';

// Form to add a new line
if ($tabname[$id]) {
	$fieldlist = explode(',', $tabfield[$id]);

	// Line for title
	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}
	foreach ($fieldlist as $field => $value) {
		// Determine le nom du champ par rapport aux noms possibles
		// dans les dictionnaires de donnees
		$valuetoshow = ucfirst($fieldlist[$field]); // By default
		$valuetoshow = $langs->trans($valuetoshow); // try to translate
		$class = "left";
		if ($fieldlist[$field] == 'type') {
			if ($tabname[$id] == MAIN_DB_PREFIX."c_paiement") {
				$valuetoshow = $form->textwithtooltip($langs->trans("Type"), $langs->trans("TypePaymentDesc"), 2, 1, img_help(1, ''));
			} else {
				$valuetoshow = $langs->trans("Type");
			}
		}
		if ($fieldlist[$field] == 'code') {
			$valuetoshow = $langs->trans("Code");
			$class = 'width75';
		}
		if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
			$valuetoshow = $langs->trans("Label");
		}
		if ($fieldlist[$field] == 'libelle_facture') {
			$valuetoshow = $langs->trans("LabelOnDocuments");
		}
		if ($fieldlist[$field] == 'country') {
			$valuetoshow = $langs->trans("Country");
		}
		if ($fieldlist[$field] == 'accountancy_code') {
			$valuetoshow = $langs->trans("AccountancyCode");
		}
		if ($fieldlist[$field] == 'accountancy_code_sell') {
			$valuetoshow = $langs->trans("AccountancyCodeSell");
		}
		if ($fieldlist[$field] == 'accountancy_code_buy') {
			$valuetoshow = $langs->trans("AccountancyCodeBuy");
		}
		if ($fieldlist[$field] == 'pcg_version' || $fieldlist[$field] == 'fk_pcg_version') {
			$valuetoshow = $langs->trans("Pcg_version");
		}
		if ($fieldlist[$field] == 'range_account') {
			$valuetoshow = $langs->trans("Comment");
			$class = 'width75';
		}
		if ($fieldlist[$field] == 'category_type') {
			$valuetoshow = $langs->trans("Calculated");
		}

		if ($valuetoshow != '') {
			print '<td class="'.$class.'">';
			if (!empty($tabhelp[$id][$value]) && preg_match('/^http(s*):/i', $tabhelp[$id][$value])) {
				print '<a href="'.$tabhelp[$id][$value].'">'.$valuetoshow.' '.img_help(1, $valuetoshow).'</a>';
			} elseif (!empty($tabhelp[$id][$value])) {
				print $form->textwithpicto($valuetoshow, $tabhelp[$id][$value]);
			} else {
				print $valuetoshow;
			}
			print '</td>';
		}
	}

	print '<td>';
	print '<input type="hidden" name="id" value="'.$id.'">';
	print '</td>';
	print '<td></td>';
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}
	print '</tr>';

	// Line to enter new values
	print '<tr class="oddeven nodrag nodrop nohover">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}

	$obj = new stdClass();
	// If data was already input, we define them in obj to populate input fields.
	if (GETPOST('actionadd', 'alpha')) {
		foreach ($fieldlist as $key => $val) {
			if (GETPOST($val) != '') {
				$obj->$val = GETPOST($val);
			}
		}
	}

	$tmpaction = 'create';
	$parameters = array('fieldlist' => $fieldlist, 'tabname' => $tabname[$id]);
	$reshook = $hookmanager->executeHooks('createDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
	$error = $hookmanager->error;
	$errors = $hookmanager->errors;

	if (empty($reshook)) {
		fieldListAccountingCategories($fieldlist, $obj, $tabname[$id], 'add');
	}

	print '<td colspan="2" class="right">';
	print '<input type="submit" class="button button-add" name="actionadd" value="'.$langs->trans("Add").'">';
	print '</td>';

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td></td>';
	}

	print "</tr>";

	$colspan = count($fieldlist) + 3;
	if ($id == 32) {
		$colspan++;
	}
}

print '</table>';
print '</div>';

print '<div class="div-table-responsive">';
print '<table class="noborder centpercent">';

// List of available record in database
dol_syslog("htdocs/accountancy/admin/categories_list.php", LOG_DEBUG);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	// There is several pages
	if ($num > $listlimit) {
		print '<tr class="none"><td class="right" colspan="'.(2 + count($fieldlist)).'">';
		print_fleche_navigation($page, $_SERVER["PHP_SELF"], $paramwithsearch, ($num > $listlimit), '<li class="pagination"><span>'.$langs->trans("Page").' '.($page + 1).'</span></li>');
		print '</td></tr>';
	}

	$filterfound = 0;
	foreach ($fieldlist as $field => $value) {
		$showfield = 1; // By default
		if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
			$showfield = 0;
		}
		if ($showfield) {
			if ($value == 'country') {
				$filterfound++;
			}
		}
	}

	// Title line with search boxes
	print '<tr class="liste_titre liste_titre_add liste_titre_filter">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre center">';
		if ($filterfound) {
			$searchpicto = $form->showFilterAndCheckAddButtons(0);
			print $searchpicto;
		}
		print '</td>';
	}

	$filterfound = 0;
	foreach ($fieldlist as $field => $value) {
		$showfield = 1; // By default

		if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
			$showfield = 0;
		}

		if ($showfield) {
			if ($value == 'country') {
				print '<td class="liste_titre">';
				print $form->select_country($search_country_id, 'search_country_id', '', 28, 'maxwidth150 maxwidthonsmartphone');
				print '</td>';
				$filterfound++;
			} else {
				print '<td class="liste_titre"></td>';
			}
		}
	}
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre center">';
		if ($filterfound) {
			$searchpicto = $form->showFilterAndCheckAddButtons(0);
			print $searchpicto;
		}
		print '</td>';
	}
	print '</tr>';

	// Title of lines
	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print getTitleFieldOfList('');
	}
	foreach ($fieldlist as $field => $value) {
		// Determines the name of the field in relation to the possible names
		// in data dictionaries
		$showfield = 1; // By default
		$class = "left";
		$sortable = 1;
		$valuetoshow = '';

		$valuetoshow = ucfirst($fieldlist[$field]); // By default
		$valuetoshow = $langs->trans($valuetoshow); // try to translate
		if ($fieldlist[$field] == 'source') {
			$valuetoshow = $langs->trans("Contact");
		}
		if ($fieldlist[$field] == 'price') {
			$valuetoshow = $langs->trans("PriceUHT");
		}
		if ($fieldlist[$field] == 'taux') {
			if ($tabname[$id] != MAIN_DB_PREFIX."c_revenuestamp") {
				$valuetoshow = $langs->trans("Rate");
			} else {
				$valuetoshow = $langs->trans("Amount");
			}
			$class = 'center';
		}
		if ($fieldlist[$field] == 'type') {
			$valuetoshow = $langs->trans("Type");
		}
		if ($fieldlist[$field] == 'code') {
			$valuetoshow = $langs->trans("Code");
		}
		if ($fieldlist[$field] == 'libelle' || $fieldlist[$field] == 'label') {
			$valuetoshow = $langs->trans("Label");
		}
		if ($fieldlist[$field] == 'country') {
			$valuetoshow = $langs->trans("Country");
		}
		if ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
			$showfield = 0;
		}
		if ($fieldlist[$field] == 'accountancy_code') {
			$valuetoshow = $langs->trans("AccountancyCode");
		}
		if ($fieldlist[$field] == 'accountancy_code_sell') {
			$valuetoshow = $langs->trans("AccountancyCodeSell");
			$sortable = 0;
		}
		if ($fieldlist[$field] == 'accountancy_code_buy') {
			$valuetoshow = $langs->trans("AccountancyCodeBuy");
			$sortable = 0;
		}
		if ($fieldlist[$field] == 'fk_pcg_version') {
			$valuetoshow = $langs->trans("Pcg_version");
		}
		if ($fieldlist[$field] == 'account_parent') {
			$valuetoshow = $langs->trans("Accountsparent");
		}
		if ($fieldlist[$field] == 'pcg_type') {
			$valuetoshow = $langs->trans("Pcg_type");
		}
		if ($fieldlist[$field] == 'type_template') {
			$valuetoshow = $langs->trans("TypeOfTemplate");
		}
		if ($fieldlist[$field] == 'range_account') {
			$valuetoshow = $langs->trans("Comment");
		}
		if ($fieldlist[$field] == 'category_type') {
			$valuetoshow = $langs->trans("Calculated");
		}
		// Affiche nom du champ
		if ($showfield) {
			print getTitleFieldOfList($valuetoshow, 0, $_SERVER["PHP_SELF"], ($sortable ? $fieldlist[$field] : ''), ($page ? 'page='.$page.'&' : ''), $param, "", $sortfield, $sortorder, $class.' ');
		}
	}
	print getTitleFieldOfList($langs->trans("ListOfAccounts"), 0, $_SERVER["PHP_SELF"], "", ($page ? 'page='.$page.'&' : ''), $param, '', $sortfield, $sortorder, '');
	print getTitleFieldOfList($langs->trans("Status"), 0, $_SERVER["PHP_SELF"], "active", ($page ? 'page='.$page.'&' : ''), $param, '', $sortfield, $sortorder, 'center ');
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print getTitleFieldOfList('');
	}
	print '</tr>';


	if ($num) {
		$imaxinloop = ($listlimit ? min($num, $listlimit) : $num);

		// Lines with values
		while ($i < $imaxinloop) {
			$obj = $db->fetch_object($resql);

			//print_r($obj);
			print '<tr class="oddeven" id="rowid-'.$obj->rowid.'">';
			if ($action == 'edit' && ($rowid == (!empty($obj->rowid) ? $obj->rowid : $obj->code))) {
				$tmpaction = 'edit';
				$parameters = array('fieldlist' => $fieldlist, 'tabname' => $tabname[$id]);
				$reshook = $hookmanager->executeHooks('editDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks
				$error = $hookmanager->error;
				$errors = $hookmanager->errors;

				// Actions
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td></td>';
				}

				// Show fields
				if (empty($reshook)) {
					fieldListAccountingCategories($fieldlist, $obj, $tabname[$id], 'edit');
				}

				print '<td></td>';
				print '<td class="center">';
				print '<div name="'.(!empty($obj->rowid) ? $obj->rowid : $obj->code).'"></div>';
				print '<input type="hidden" name="page" value="'.$page.'">';
				print '<input type="hidden" name="rowid" value="'.$rowid.'">';
				print '<input type="submit" class="button button-edit smallpaddingimp" name="actionmodify" value="'.$langs->trans("Modify").'">';
				print '<input type="submit" class="button button-cancel smallpaddingimp" name="actioncancel" value="'.$langs->trans("Cancel").'">';
				print '</td>';
				// Actions
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td></td>';
				}
			} else {
				// Can an entry be erased or disabled ?
				$iserasable = 1;
				$canbedisabled = 1;
				$canbemodified = 1; // true by default
				if (isset($obj->code)) {
					if (($obj->code == '0' || $obj->code == '' || preg_match('/unknown/i', $obj->code))) {
						$iserasable = 0;
						$canbedisabled = 0;
					}
				}
				$url = $_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').'sortfield='.$sortfield.'&sortorder='.$sortorder.'&rowid='.(!empty($obj->rowid) ? $obj->rowid : (!empty($obj->code) ? $obj->code : '')).'&code='.(!empty($obj->code) ? urlencode($obj->code) : '');
				if ($param) {
					$url .= '&'.$param;
				}
				$url .= '&';

				$canbemodified = $iserasable;

				$tmpaction = 'view';
				$parameters = array('fieldlist' => $fieldlist, 'tabname' => $tabname[$id]);
				$reshook = $hookmanager->executeHooks('viewDictionaryFieldlist', $parameters, $obj, $tmpaction); // Note that $action and $object may have been modified by some hooks

				$error = $hookmanager->error;
				$errors = $hookmanager->errors;

				// Actions
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="center">';
					if ($canbemodified) {
						print '<a class="reposition editfielda marginleftonly marginrightonly" href="'.$url.'action=edit&token='.newToken().'">'.img_edit().'</a>';
					}
					if ($iserasable) {
						if ($user->admin) {
							print '<a class="marginleftonly marginrightonly" href="'.$url.'action=delete&token='.newToken().'">'.img_delete().'</a>';
						}
					}
					print '</td>';
				}

				if (empty($reshook)) {
					foreach ($fieldlist as $field => $value) {
						$showfield = 1;
						$title = '';
						$class = 'tddict';

						$tmpvar = $fieldlist[$field];
						$valuetoshow = $obj->$tmpvar;
						if ($value == 'category_type') {
							$valuetoshow = yn($valuetoshow);
						} elseif ($valuetoshow == 'all') {
							$valuetoshow = $langs->trans('All');
						} elseif ($fieldlist[$field] == 'country') {
							if (empty($obj->country_code)) {
								$valuetoshow = '-';
							} else {
								$key = $langs->trans("Country".strtoupper($obj->country_code));
								$valuetoshow = ($key != "Country".strtoupper($obj->country_code) ? $obj->country_code." - ".$key : $obj->country);
							}
						} elseif (in_array($fieldlist[$field], array('label', 'formula'))) {
							$class = "tdoverflowmax250";
							$title = $valuetoshow;
						} elseif (in_array($fieldlist[$field], array('range_account'))) {
							$class = "tdoverflowmax250 small";
							$title = $valuetoshow;
						} elseif ($fieldlist[$field] == 'region_id' || $fieldlist[$field] == 'country_id') {
							$showfield = 0;
						}

						// Show value for field
						if ($showfield) {
							print '<!-- '.$fieldlist[$field].' --><td class="'.$class.'"'.($title ? ' title="'.dol_escape_htmltag($title).'"' : '').'>'.dol_escape_htmltag($valuetoshow).'</td>';
						}
					}
				}

				// Link to setup the group
				print '<td>';
				if (empty($obj->formula)) {
					// Count number of accounts into group
					$nbofaccountintogroup = 0;
					$listofaccountintogroup = $accountingcategory->getCptsCat($obj->rowid);
					$nbofaccountintogroup = count($listofaccountintogroup);

					print '<a href="'.DOL_URL_ROOT.'/accountancy/admin/categories.php?action=display&save_lastsearch_values=1&account_category='.$obj->rowid.'">';
					print $langs->trans("NAccounts", $nbofaccountintogroup);
					print '</a>';
				} else {
					print '<span class="opacitymedium">'.$langs->trans("Formula").'</span>';
				}
				print '</td>';

				// Active
				print '<td class="center" class="nowrap">';
				if ($canbedisabled) {
					print '<a href="'.$url.'action='.$acts[$obj->active].'">'.$actl[$obj->active].'</a>';
				} else {
					print $langs->trans("AlwaysActive");
				}
				print "</td>";

				// Actions
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="center">';
					if ($canbemodified) {
						print '<a class="reposition editfielda paddingleft marginleftonly marginrightonly paddingright" href="'.$url.'action=edit&token='.newToken().'">'.img_edit().'</a>';
					}
					if ($iserasable) {
						if ($user->admin) {
							print '<a class="paddingleft marginleftonly marginrightonly paddingright" href="'.$url.'action=delete&token='.newToken().'">'.img_delete().'</a>';
						}
					}
					print '</td>';
				}
			}
			print "</tr>\n";
			$i++;
		}
	} else {
		$colspan = 10;
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
} else {
	dol_print_error($db);
}

print '</table>';
print '</div>';

print '</form>';

print '<br>';

// End of page
llxFooter();
$db->close();


/**
 *	Show fields in insert/edit mode
 *
 * 	@param		array	$fieldlist		Array of fields
 * 	@param		Object	$obj			If we show a particular record, obj is filled with record fields
 *  @param		string	$tabname		Name of SQL table
 *  @param		string	$context		'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we don't want it to be rendered
 *	@return		void
 */
function fieldListAccountingCategories($fieldlist, $obj = null, $tabname = '', $context = '')
{
	global $conf, $langs, $db;
	global $form, $mysoc;

	$formadmin = new FormAdmin($db);
	$formcompany = new FormCompany($db);
	if (isModEnabled('accounting')) {
		$formaccounting = new FormAccounting($db);
	}

	foreach ($fieldlist as $field => $value) {
		if ($fieldlist[$field] == 'country') {
			print '<td>';
			$fieldname = 'country';
			if ($context == 'add') {
				$fieldname = 'country_id';
				$preselectcountrycode = GETPOSTISSET('country_id') ? GETPOSTINT('country_id') : $mysoc->country_code;
				print $form->select_country($preselectcountrycode, $fieldname, '', 28, 'maxwidth150 maxwidthonsmartphone');
			} else {
				$preselectcountrycode = (empty($obj->country_code) ? (empty($obj->country) ? $mysoc->country_code : $obj->country) : $obj->country_code);
				print $form->select_country($preselectcountrycode, $fieldname, '', 28, 'maxwidth150 maxwidthonsmartphone');
			}
			print '</td>';
		} elseif ($fieldlist[$field] == 'country_id') {
			if (!in_array('country', $fieldlist)) {	// If there is already a field country, we don't show country_id (avoid duplicate)
				$country_id = (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : 0);
				print '<td>';
				print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$country_id.'">';
				print '</td>';
			}
		} elseif ($fieldlist[$field] == 'category_type') {
			print '<td>';
			print $form->selectyesno($fieldlist[$field], (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : ''), 1);
			print '</td>';
		} elseif ($fieldlist[$field] == 'code' && isset($obj->{$fieldlist[$field]})) {
			print '<td><input type="text" class="flat minwidth100" value="'.(!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : '').'" name="'.$fieldlist[$field].'"></td>';
		} else {
			print '<td>';
			$class = '';
			if (in_array($fieldlist[$field], array('code', 'formula'))) {
				$class = 'maxwidth75';
			}
			if (in_array($fieldlist[$field], array('label', 'range_account'))) {
				$class = 'maxwidth150';
			}
			if ($fieldlist[$field] == 'position') {
				$class = 'maxwidth50';
			}
			print '<input type="text" class="flat'.($class ? ' '.$class : '').'" value="'.(isset($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : '').'" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
	}
}
