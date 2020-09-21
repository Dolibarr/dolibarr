<?php
/*
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
 */


/**
 *      \file       htdocs/multicurrency/multicurrency_rates.php
 *		\ingroup    multicurrency
 *		\brief      Shows an exchange rate editor
 */

$res=@include("../main.inc.php");				// For root directory

/**
 * @var User $user
 * @var DoliDB $db
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/html.formadmin.class.php';
require_once(DOL_DOCUMENT_ROOT."/core/lib/admin.lib.php");
require_once DOL_DOCUMENT_ROOT.'/core/class/doleditor.class.php';
$langs->loadLangs(array("errors", "admin", "main", "companies", "resource", "holiday", "accountancy", "hrm", "orders", "contracts", "projects", "propal", "bills", "interventions"));



if (!$user->admin)
{
	accessforbidden();
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
$hookmanager->initHooks(array('admin'));

// Load translation files required by the page

$action = GETPOST('action', 'alpha') ?GETPOST('action', 'alpha') : 'view';
$confirm = GETPOST('confirm', 'alpha');
$id = GETPOST('id', 'int');
$rowid = GETPOST('rowid', 'alpha');
$entity = GETPOST('entity', 'int');
$code = GETPOST('code', 'alpha');

$acts =array(); $actl =array();
$acts[0] = "activate";
$acts[1] = "disable";
$actl[0] = img_picto($langs->trans("Disabled"), 'switch_off');
$actl[1] = img_picto($langs->trans("Activated"), 'switch_on');

$listoffset = GETPOST('listoffset');
$listlimit = GETPOST('listlimit') > 0 ?GETPOST('listlimit') : 1000; // To avoid too long dictionaries
$active = 1;

$sortfield = GETPOST("sortfield", 'alpha');
$sortorder = GETPOST("sortorder", 'alpha');
$page = GETPOST("page", 'int');
if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
$offset = $listlimit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;


// This page is a generic page to edit dictionaries
// Put here declaration of dictionaries properties

// Sort order to show dictionary (0 is space). All other dictionaries (added by modules) will be at end of this.
$taborder = array(9, 0, 4, 3, 2, 0, 1, 8, 19, 16, 27, 38, 0, 5, 11, 0, 32, 33, 34, 0, 6, 0, 29, 0, 7, 24, 28, 17, 35, 36, 0, 10, 23, 12, 13, 0, 14, 0, 22, 20, 18, 21, 0, 15, 30, 0, 37, 0, 25, 0);

// Name of SQL tables of dictionaries
$tabname = array();
$tabname[1] = MAIN_DB_PREFIX."c_forme_juridique";

// Dictionary labels
$tablib = array();
$tablib[1] = "DictionaryCompanyJuridicalType";

// Requests to extract data
$tabsql = array();
$tabsql[1] = "SELECT f.rowid as rowid, f.code, f.libelle, c.code as country_code, c.label as country, f.active FROM ".MAIN_DB_PREFIX."c_forme_juridique as f, ".MAIN_DB_PREFIX."c_country as c WHERE f.fk_pays=c.rowid";

// Criteria to sort dictionaries
$tabsqlsort = array();
$tabsqlsort[1] = "country ASC, code ASC";

// Field names in select result for dictionary display
$tabfield = array();
$tabfield[1] = "code,libelle,country";

// Edit field names for editing a record
$tabfieldvalue = array();
$tabfieldvalue[1] = "code,libelle,country";

// Field names in the table for inserting a record
$tabfieldinsert = array();
$tabfieldinsert[1] = "code,libelle,fk_pays";

// Rowid name of field depending if field is autoincrement on or off..
// Use "" if id field is "rowid" and has autoincrement on
// Use "nameoffield" if id field is not "rowid" or has not autoincrement on
$tabrowid = array();
$tabrowid[1] = "";

// Condition to show dictionary in setup page
$tabcond = array();
$tabcond[1] = (!empty($conf->societe->enabled));

// List of help for fields
$tabhelp = array();
$tabhelp[1]  = array('code'=>$langs->trans("EnterAnyCode"));
// List of check for fields (NOT USED YET)
$tabfieldcheck = array();
$tabfieldcheck[1]  = array();

// Defaut sortorder
if (empty($sortfield))
{
	$tmp1 = explode(',', $tabsqlsort[$id]);
	$tmp2 = explode(' ', $tmp1[0]);
	$sortfield = preg_replace('/^.*\./', '', $tmp2[0]);
}

/*
 * Actions
 */

_handleActions($db);
exit;

/**
 *	Show fields in insert/edit mode
 *
 * 	@param		array		$fieldlist		Array of fields
 * 	@param		Object		$obj			If we show a particular record, obj is filled with record fields
 *  @param		string		$tabname		Name of SQL table
 *  @param		string		$context		'add'=Output field for the "add form", 'edit'=Output field for the "edit form", 'hide'=Output field for the "add form" but we dont want it to be rendered
 *	@return		string						'' or value of entity into table
 */
function fieldList($fieldlist, $obj = '', $tabname = '', $context = '')
{
	global $conf, $langs, $db, $mysoc;
	global $form;
	global $region_id;
	global $elementList, $sourceList, $localtax_typeList;

	$formadmin = new FormAdmin($db);
	$formcompany = new FormCompany($db);
	$formaccounting = new FormAccounting($db);

	$withentity = '';

	foreach ($fieldlist as $field => $value)
	{
		if ($fieldlist[$field] == 'entity') {
			$withentity = $obj->{$fieldlist[$field]};
			continue;
		}

		if (in_array($fieldlist[$field], array('code', 'libelle', 'type')) && $tabname == MAIN_DB_PREFIX."c_actioncomm" && in_array($obj->type, array('system', 'systemauto')))
		{
			$hidden = (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:'');
			print '<td>';
			print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$hidden.'">';
			print $langs->trans($hidden);
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'country')
		{
			if (in_array('region_id', $fieldlist))
			{
				print '<td>';
				print '</td>';
				continue;
			}	// For state page, we do not show the country input (we link to region, not country)
			print '<td>';
			$fieldname = 'country';
			print $form->select_country((!empty($obj->country_code) ? $obj->country_code : (!empty($obj->country) ? $obj->country : '')), $fieldname, '', 28, 'maxwidth150 maxwidthonsmartphone');
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'country_id')
		{
			if (!in_array('country', $fieldlist))	// If there is already a field country, we don't show country_id (avoid duplicate)
			{
				$country_id = (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : 0);
				print '<td class="tdoverflowmax100">';
				print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$country_id.'">';
				print '</td>';
			}
		}
		elseif ($fieldlist[$field] == 'region')
		{
			print '<td>';
			$formcompany->select_region($region_id, 'region');
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'region_id')
		{
			$region_id = (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:0);
			print '<td>';
			print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$region_id.'">';
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'lang')
		{
			print '<td>';
			print $formadmin->select_language($conf->global->MAIN_LANG_DEFAULT, 'lang');
			print '</td>';
		}
		// The type of the element (for contact types)
		elseif ($fieldlist[$field] == 'element')
		{
			print '<td>';
			print $form->selectarray('element', $elementList, (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:''));
			print '</td>';
		}
		// The source of the element (for contact types)
		elseif ($fieldlist[$field] == 'source')
		{
			print '<td>';
			print $form->selectarray('source', $sourceList, (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:''));
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'private')
		{
			print '<td>';
			print $form->selectyesno("private", (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:''));
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'type' && $tabname == MAIN_DB_PREFIX."c_actioncomm")
		{
			$type = (!empty($obj->type) ? $obj->type : 'user'); // Check if type is different of 'user' (external module)
			print '<td>';
			print $type.'<input type="hidden" name="type" value="'.$type.'">';
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'type' && $tabname == MAIN_DB_PREFIX.'c_paiement')
		{
			print '<td>';
			$select_list = array(0=>$langs->trans('PaymentTypeCustomer'), 1=>$langs->trans('PaymentTypeSupplier'), 2=>$langs->trans('PaymentTypeBoth'));
			print $form->selectarray($fieldlist[$field], $select_list, (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:'2'));
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'recuperableonly' || $fieldlist[$field] == 'type_cdr' || $fieldlist[$field] == 'deductible' || $fieldlist[$field] == 'category_type') {
			if ($fieldlist[$field] == 'type_cdr') print '<td class="center">';
			else print '<td>';
			if ($fieldlist[$field] == 'type_cdr') {
				print $form->selectarray($fieldlist[$field], array(0=>$langs->trans('None'), 1=>$langs->trans('AtEndOfMonth'), 2=>$langs->trans('CurrentNext')), (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:''));
			} else {
				print $form->selectyesno($fieldlist[$field], (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:''), 1);
			}
			print '</td>';
		}
		elseif (in_array($fieldlist[$field], array('nbjour', 'decalage', 'taux', 'localtax1', 'localtax2'))) {
			$class = "left";
			if (in_array($fieldlist[$field], array('taux', 'localtax1', 'localtax2'))) $class = "center"; // Fields aligned on right
			print '<td class="'.$class.'">';
			print '<input type="text" class="flat" value="'.(isset($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]} : '').'" size="3" name="'.$fieldlist[$field].'">';
			print '</td>';
		}
		elseif (in_array($fieldlist[$field], array('libelle_facture'))) {
			print '<td>';
			$transfound = 0;
			$transkey = '';
			// Special case for labels
			if ($tabname == MAIN_DB_PREFIX.'c_payment_term')
			{
				$langs->load("bills");
				$transkey = "PaymentCondition".strtoupper($obj->code);
				if ($langs->trans($transkey) != $transkey)
				{
					$transfound = 1;
					print $form->textwithpicto($langs->trans($transkey), $langs->trans("GoIntoTranslationMenuToChangeThis"));
				}
			}
			if (!$transfound)
			{
				print '<textarea cols="30" rows="'.ROWS_2.'" class="flat" name="'.$fieldlist[$field].'">'.(!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:'').'</textarea>';
			}
			else {
				print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$transkey.'">';
			}
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'price' || preg_match('/^amount/i', $fieldlist[$field])) {
			print '<td><input type="text" class="flat minwidth75" value="'.price((!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:'')).'" name="'.$fieldlist[$field].'"></td>';
		}
		elseif ($fieldlist[$field] == 'code' && isset($obj->{$fieldlist[$field]})) {
			print '<td><input type="text" class="flat minwidth75 maxwidth100" value="'.(!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:'').'" name="'.$fieldlist[$field].'"></td>';
		}
		elseif ($fieldlist[$field] == 'unit') {
			print '<td>';
			$units = array(
				'mm' => $langs->trans('SizeUnitmm'),
				'cm' => $langs->trans('SizeUnitcm'),
				'point' => $langs->trans('SizeUnitpoint'),
				'inch' => $langs->trans('SizeUnitinch')
			);
			print $form->selectarray('unit', $units, (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:''), 0, 0, 0);
			print '</td>';
		}
		// Le type de taxe locale
		elseif ($fieldlist[$field] == 'localtax1_type' || $fieldlist[$field] == 'localtax2_type')
		{
			print '<td class="center">';
			print $form->selectarray($fieldlist[$field], $localtax_typeList, (!empty($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:''));
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'accountancy_code' || $fieldlist[$field] == 'accountancy_code_sell' || $fieldlist[$field] == 'accountancy_code_buy')
		{
			print '<td>';
			if (!empty($conf->accounting->enabled))
			{
				$fieldname = $fieldlist[$field];
				$accountancy_account = (!empty($obj->$fieldname) ? $obj->$fieldname : 0);
				print $formaccounting->select_account($accountancy_account, '.'.$fieldlist[$field], 1, '', 1, 1, 'maxwidth200 maxwidthonsmartphone');
			}
			else
			{
				$fieldname = $fieldlist[$field];
				print '<input type="text" size="10" class="flat" value="'.(isset($obj->$fieldname) ? $obj->$fieldname : '').'" name="'.$fieldlist[$field].'">';
			}
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'fk_tva')
		{
			print '<td>';
			print $form->load_tva('fk_tva', $obj->taux, $mysoc, new Societe($db), 0, 0, '', false, -1);
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'fk_c_exp_tax_cat')
		{
			print '<td>';
			print $form->selectExpenseCategories($obj->fk_c_exp_tax_cat);
			print '</td>';
		}
		elseif ($fieldlist[$field] == 'fk_range')
		{
			print '<td>';
			print $form->selectExpenseRanges($obj->fk_range);
			print '</td>';
		}
		else
		{
			$fieldValue = isset($obj->{$fieldlist[$field]}) ? $obj->{$fieldlist[$field]}:'';

			if ($fieldlist[$field] == 'sortorder')
			{
				$fieldlist[$field] = 'position';
			}

			$classtd = ''; $class = '';
			if ($fieldlist[$field] == 'code') $class = 'maxwidth100';
			if (in_array($fieldlist[$field], array('dayrule', 'day', 'month', 'year', 'pos', 'use_default', 'affect', 'delay', 'position', 'sortorder', 'sens', 'category_type'))) $class = 'maxwidth50';
			if (in_array($fieldlist[$field], array('libelle', 'label', 'tracking'))) $class = 'quatrevingtpercent';
			print '<td class="'.$classtd.'">';
			$transfound = 0;
			$transkey = '';
			if (in_array($fieldlist[$field], array('label', 'libelle')))		// For label
			{
				// Special case for labels
				if ($tabname == MAIN_DB_PREFIX.'c_civility') {
					$transkey = "Civility".strtoupper($obj->code);
				}
				if ($tabname == MAIN_DB_PREFIX.'c_payment_term') {
					$langs->load("bills");
					$transkey = "PaymentConditionShort".strtoupper($obj->code);
				}
				if ($transkey && $langs->trans($transkey) != $transkey)
				{
					$transfound = 1;
					print $form->textwithpicto($langs->trans($transkey), $langs->trans("GoIntoTranslationMenuToChangeThis"));
				}
			}
			if (!$transfound)
			{
				print '<input type="text" class="flat'.($class ? ' '.$class : '').'" value="'.dol_escape_htmltag($fieldValue).'" name="'.$fieldlist[$field].'">';
			}
			else {
				print '<input type="hidden" name="'.$fieldlist[$field].'" value="'.$transkey.'">';
			}
			print '</td>';
		}
	}

	return $withentity;
}

function _handleActions($db) {
	$action = GETPOST('action', 'alpha');
	if (empty($action)) $action = 'view';

	$callbackName = '_action' . _camel($action);
	if (!function_exists($callbackName)) {
		setEventMessages('UnknownAction', array(), 'errors');
		header('Location: ' . $_SERVER['PHP_SELF']);
		exit;
	}
	call_user_func($callbackName, $db);
}

function _actionAdd($db) {

}

function _actionModify($db) {
	$id = GETPOST('id', 'int');
	_showDictionary($db, 'modify', $id);
}

function _actionView($db) {
	_showDictionary($db, 'view', intval(GETPOST('id', 'int'))));
}

function _actionDelete($db) {
	global $langs;
	global $delayedhtmlcontent;
	$form = new Form($db);
	$delayedhtmlcontent .= $form->formconfirm(
		$_SERVER["PHP_SELF"].'?'.($page ? 'page='.$page.'&' : '').$paramwithsearch,
		$langs->trans('DeleteLine'),
		$langs->trans('ConfirmDeleteLine'),
		'confirm_delete',
		'',
		0,
		1
	);
	_showDictionary($db, 'view', intval(GETPOST('id', 'int')));
}

function _actionConfirmDelete($db) {
	$id = GETPOST('id', 'int');
	$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'multicurrency_rate'
		   .' WHERE rowid = ' . intval($id);

	_showDictionary($db, 'view');
}

function _showDictionary($db, $mode='view', $id=NULL) {
	global $langs;
	$form = new Form($db);
	$formadmin = new FormAdmin($db);
	$title = $langs->trans('CurrencyRateSetup');
	$limit = 123;

	$TVisibleColumn = array(
		'rate.date_sync' => array('Date'),
		'rate.rate' => array('Number'),
		'currency.code' => array('CurrencyCode'),
		'rate.entity' => array(),
	);
	foreach ($TVisibleColumn as $colSelect => &$colParam) {
		$colParam['name'] = _columnAlias($colSelect);
	}
	unset($colParam);

	$sql = 'SELECT rate.rowid, ' . join(', ', array_keys($TVisibleColumn)) . ' FROM ' . MAIN_DB_PREFIX . 'multicurrency_rate rate'
		   . ' LEFT JOIN ' . MAIN_DB_PREFIX . 'multicurrency currency ON rate.fk_multicurrency = currency.rowid'
		   . ' WHERE 1 = 1'
		   . ' LIMIT ' . intval($limit);
	$resql = $db->query($sql);
	if (!$resql) {
		llxFooter();
		return;
	}
	$num_rows = $db->num_rows($resql);

	llxHeader();
	echo load_fiche_titre($title, $linkback, $titlepicto);

	echo '<table class="noborder centpercent">';
	echo '<thead>';
	echo '<tr>';

	// titres
	foreach ($TVisibleColumn as $colSelect => $colParam) {
		echo '<th>';
		echo $langs->trans('multicurrencyColumn' . _camel(ucfirst($colParam['name'])));
		echo '</th>';
	}
	echo '<th></th>';
	echo '</tr>';
	echo '</thead>';
	echo '<tbody>';
	if (!$num_rows) {
		echo '<tr>';
		$colspan = count($TVisibleColumn);
		$colspan += 1; // account for the action column
		echo '<td colspan="' . $colspan . '">' . $langs->trans('NoResults') . '</td>';
		echo '</tr>';
	}
	for ($i = 0; $i < $num_rows; $i++) {
		$obj = $db->fetch_object($resql);
		if (!$obj) { break; }
		echo '<tr data-id="' . intval($obj->rowid) . '">';
		foreach ($TVisibleColumn as $colSelect => $colParam) {
			$rawValue = $obj->{$colParam['name']};

			// default callback for how to display the raw value
			$cellContentCallback = '_getCellDefault';

			// possible override in column definition
			if (isset($colParam['callback'])) {
				$cbName = $colParam['callback'];
				// mandatory function name prefix: '_getCell' (some day, the callback may come from untrusted input)
				if (strpos($cbName, '_getCell') !== 0) $cbName = '_getCell' . $cbName;
				if (function_exists($cbName)) { $cellContentCallback = $cbName; }
			}

			if ($mode === 'modify' && $obj->rowid === $id) {
				$cellContent = call_user_func($cellContentCallback, $rawValue, 'modify', $colParam['name']);
			} else {
				$cellContent = call_user_func($cellContentCallback, $rawValue, 'view', $colParam['name']);
			}
			echo '<td>' . $cellContent . '</td>';
		}

		echo '<td>'
			 . '<a href="?action=modify&id=' . intval($obj->rowid) . '">' . img_picto('edit', 'edit') . '</a>'
			 . '<a href="?action=delete&id=' . intval($obj->rowid) . '">' . img_picto('delete', 'delete') . '</a>'
			 . '</td>';
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';

	// End of page
	llxFooter();
	$db->close();
}

/**
 * @param $rawValue
 * @param string $mode
 * @param string $inputName
 * @return string
 */
function _getcellDefault($rawValue, $mode='view', $inputName='') {
	if ($mode === 'view') {
		return dol_escape_htmltag($rawValue);
	} elseif ($mode === 'modify') {
		return '<input value="'
			   . htmlspecialchars($rawValue, ENT_QUOTES)
			   . '" name="' . $inputName
			   . '" />';
	}
}

/**
 * Returns the name of the column in the object returned by DoliDB::fetch_object
 *
 * Example:
 *   $colSelect = 'abcd'               => 'abcd' // no table name, no alias
 *                'table.xyz AS abcd'  => 'abcd' // with table name
 *                'table.abcd'         => 'abcd' // with table name and alias
 *                'xyz AS abcd'        => 'xyz AS abcd' // not handled: alias without table name
 * @param string $colSelect
 * @return string
 */
function _columnAlias($colSelect) {
	// the regexp replacement does this:
	//     'table.abcd AS efgh' => 'efgh'
	// regexp explanation:
	//     '.*\.`?'     => not captured:  anything, then a dot, then an optional backtick;
	//     '([^ `]+)`?' => capture 1:     anything that doesn't have a space or a backtick (then an optional, uncaptured backtick)
	//     '(?:.....)?' => non-capturing: makes whatever is inside the parentheses optional
	//     '\s+as\s+`?' => not captured:  whitespace, then 'AS', then whitespace, then an optional backtick
	//     '([^ `]+)'   => capture 2:     anything that doesn't have a space or a backtick
	return preg_replace_callback(
		'/^.*\.`?([^ `]+)`?(?:\s+as\s+`?([^ `]+)`?)?/i',
		function ($m) { return isset($m[2]) ? $m[2] : $m[1]; },
		$colSelect
	);
}

/**
 * Returns $str in camel case ("snake_case_versus_camel_case" => 'snakeCaseVersusCamelCase')
 * @param $str
 * @return string|string[]|null
 */
function _camel($str) {
	return preg_replace_callback('/_(.)?/', function($m) { return ucfirst($m[1]); }, $str);
}
