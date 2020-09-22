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
dol_include_once('/multicompany/class/actions_multicompany.class.php', 'ActionsMulticompany');
/** @var Translate $langs */
$langs->loadLangs(array(
	"errors",
	"admin",
	"main",
	"multicurrency"));



if (!$user->admin)
{
	accessforbidden();
}

// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
/** @var HookManager $hookmanager */
$hookmanager->initHooks(array('multicurrency_rates'));

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
// TODO: sorting, filtering, paginating


/*
 * Actions
 */

_handleActions($db);
exit;

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

/**
 * @param DoliDB $db
 * @param string $mode
 * @param int|null $targetId ID of the row targeted for edition, deletion, etc.
 */
function _mainView($db, $mode='view', $targetId=NULL) {
	global $langs;
	$title = $langs->trans('CurrencyRateSetup');
	$limit = 123;

	// column definition
	$TVisibleColumn = array(
		'rate.date_sync'
			=> array('callback' => 'Date'),
		'rate.rate'
			=> array('callback' => 'Number'),
		'currency.code'
			=> array('callback' => 'CurrencyCode'),
//		'rate.entity'
//			=> array('callback' => 'Entity'),
	);
	foreach ($TVisibleColumn as $colSelect => &$colParam) { $colParam['name'] = _columnAlias($colSelect); }
	unset($colParam);

	$sql = /** @lang SQL */
		'SELECT rate.rowid, ' . join(', ', array_keys($TVisibleColumn)) . ' FROM ' . MAIN_DB_PREFIX . 'multicurrency_rate rate'
		. ' LEFT JOIN ' . MAIN_DB_PREFIX . 'multicurrency currency ON rate.fk_multicurrency = currency.rowid'
		. ' WHERE rate.entity IN (' . getEntity('multicurrency') . ')'
		. ' ORDER BY rate.date_sync DESC'
		. ' LIMIT ' . intval($limit);
	$resql = $db->query($sql);
	if (!$resql) {
		setEventMessages($db->lasterror, array(), 'errors');
		$num_rows = 0;
	} else {
		$num_rows = $db->num_rows($resql);
	}

	llxHeader();
	echo load_fiche_titre($title);

	echo '<style>'
		 . 'button.like-link {'
		 . '  border: none;'
		 . '  padding: 0;'
		 . '  background: inherit;'
		 . '  cursor: pointer;'
		 .'}'
		 .'col.small-col {'
		 . '  width: 10%'
		 .'}'
		 . '</style>';

	echo '<table class="noborder centpercent">';
	echo '<colgroup>'
		 . '<col span="' . count($TVisibleColumn) . '">'
		 . '<col class="small-col" span="1">'
		 . '</colgroup>';


	// En-têtes de colonnes
	echo '<thead>';
	echo '<tr id="title-row">';
	foreach ($TVisibleColumn as $colSelect => $colParam) {
		echo '<th class="' . $colParam['name'] . '">';
		echo $langs->trans('Multicurrency' . _camel(ucfirst($colParam['name'])));
		echo '</th>';
	}
	echo '<th class="actions"></th>';
	echo '</tr>';

	// Formulaire des filtres de recherche
	echo '<tr id="filter-row">';
	foreach ($TVisibleColumn as $colSelect => $colParam) {
		echo '<td class="' . $colParam['name'] . '">';
		echo _getCellContent(
			GETPOST('search_' . $colParam['name']),
			$colParam,
			'search',
			'form-filter'
		);
		echo '</td>';
	}
	echo '<td class="actions">'
		 . '<form method="get" id="form-filter" action="' . $_SERVER["PHP_SELF"] . '">'
		 . '<button class="like-link" name="action" value="search">'
		 . '<span class="fa fa-search" >&nbsp;</span>'
		 . '</button>'
		 . '<button class="like-link" name="action" value="remove_filters">'
		 . '<span class="fa fa-remove" >&nbsp;</span>'
		 . '</button>'
		 . '</form>'
		 . '</td>';
	echo '</tr>';
	echo '</thead>';

	// formulaire d'ajout ('new')
	echo '<tbody>';
	echo '<tr id="row-add-new">';
	foreach ($TVisibleColumn as $colSelect => $colParam) {
		echo '<td class="' . $colParam['name'] . '">';
		// show an empty input
		echo _getCellContent('', $colParam, 'new', 'form-add-new');
		echo '</td>';
	}
	// entire form is inside cell because HTML does not allow forms inside tables unless they are inside cells
	echo '<td>'
		 .'<form method="post" id="form-add-new">'
		 .'<input type="hidden" name="action" value="add" />'
		 .'<input class="button" type="submit" value="' . $langs->trans('Add') . '"></a>'
		 .'</form>'
		 .'</td>';
	echo '</tr>';
	echo '</tbody>';

	// lignes
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
		$objId = intval($obj->rowid);
		$row_is_in_edit_mode = ($mode === 'modify' && $objId === $targetId);
		$form_update_name = "form-update-" . $objId;
		if (!$obj) { break; }
		echo '<tr id="row-' . intval($obj->rowid) . '">';
		foreach ($TVisibleColumn as $colSelect => $colParam) {
			$rawValue = $obj->{$colParam['name']};
			$displayMode = 'view';
			if ($row_is_in_edit_mode) { $displayMode = 'modify'; }
			$cellContent = _getCellContent($rawValue, $colParam, $displayMode, $form_update_name);
			echo '<td class="' . $colParam['name'] . '">' . $cellContent . '</td>';
		}

		echo '<td class="actions">';
		// save form (for the row in edit mode)
		if ($row_is_in_edit_mode) {
			echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="' . $form_update_name . '" style="display: inline;">'
				 . '<input type="hidden" name="id" value="' . $objId . '">'
				 . '<input type="hidden" name="action" value="update">'
				 . '<input type="submit" class="button" value="'
				 . htmlspecialchars($langs->trans('Save'), ENT_QUOTES)
				 . '" />'
				 . '</form>';
		}

		// edit + delete buttons (for rows not in edit mode)
		else {
			echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="form-edit-' . $objId . '" style="display: inline;">'
				 . '<input type="hidden" name="id" value="' . $objId . '">'
				 . '<input type="hidden" name="action" value="modify">'
				 . '<button class="like-link">'
				 . img_picto('edit', 'edit')
				 . '</button>'
				 . '</form>';
			echo '<form method="post" action="' . $_SERVER['PHP_SELF'] . '" id="form-delete-' . $objId . '" style="display: inline;">'
				 . '<input type="hidden" name="id" value="' . $objId . '">'
				 . '<input type="hidden" name="action" value="delete">'
				 . '<button class="like-link">'
				 . img_picto('delete', 'delete')
				 . '</button>'
				 . '</form>';
		}
		echo '</td>';
		echo '</tr>';
	}
	echo '</tbody>';
	echo '</table>';


	// End of page
	llxFooter();
	$db->close();
}

/**
 * Calls a specialized callback depending on $colParam['callback'] (or a default one
 * if not set or found) to return a representation of $rawValue depending on $mode:
 *
 * @param mixed $rawValue      A raw value (as returned by the SQL handler)
 * @param array $colParam      Information about the kind of value (date, price, etc.)
 * @param string $mode         'view',   => returns the value for end user display
 *                             'modify', => returns a form to modify the value
 *                             'new',    => returns a form to put the value in a new record
 *                             'raw',    => does nothing (returns the raw value)
 *                             'text'    => returns a text-only version of the value
 *                                          (for text-only exports etc.)
 * @param string|null $formId  HTML id of the form on which to attach the input in
 *                             'modify' and 'new' modes
 * @return string
 */
function _getCellContent($rawValue, $colParam, $mode='view', $formId=NULL) {
	if ($mode === 'raw') return $rawValue;
	$callback = _cellContentCallbackName($colParam);
	return call_user_func($callback, $rawValue, $mode, $colParam['name'], $formId);
}

/**
 * @param $rawValue
 * @param string $mode
 * @param string $inputName
 * @return string
 * @see _getCellContent()
 */
function _getCellDefault($rawValue, $mode='view', $inputName='', $formId=NULL) {
	switch ($mode) {
		case 'view':
			return dol_escape_htmltag($rawValue);
		case 'modify': case 'new':
			$inputAttributes = array(
				'value' => $rawValue,
				'name' => $inputName,
			);
			if ($formId !== NULL) {
				$inputAttributes['form'] = $formId;
			}
			return _tagWithAttributes('input', $inputAttributes);
		case 'raw':
			return $rawValue;
		case 'text':
			return strip_tags($rawValue);
		case 'search':
			return '<input name="search_' . $inputName . '" value="'
				   . htmlspecialchars(GETPOST('search_' . $inputName), ENT_QUOTES)
				   . '" />';
	}
	return $rawValue;
}

/**
 * @param $rawValue
 * @param string $mode
 * @param string $inputName
 * @return string
 * @see _getCellContent()
 */
function _getCellDate($rawValue, $mode='view', $inputName='', $formId=NULL) {
	global $db;
	switch ($mode) {
		case 'view':
			$tms = $db->jdate($rawValue);
			$dateFormat = '%d/%m/%Y %H:%M';
			$dateFormat = '';
			return dol_print_date($tms, $dateFormat);
		case 'modify': case 'new':
			$inputAttributes = array(
				'type' => 'date',
				'value' => preg_replace('/^(.*?) .*/', '$1', $rawValue),
				'name' => $inputName,
			);
			if ($formId !== NULL) {
				$inputAttributes['form'] = $formId;
			}
			return _tagWithAttributes('input', $inputAttributes);
		case 'raw':
			return $rawValue;
		case 'text':
			return strip_tags($rawValue);
		case 'search':
			return '<input name="search_' . $inputName . '" value="'
				   . htmlspecialchars(GETPOST('search_' . $inputName), ENT_QUOTES)
				   . '" />';
	}
	return $rawValue;
}

/**
 * @param $rawValue
 * @param string $mode
 * @param string $inputName
 * @return string
 * @see _getCellContent()
 */
function _getCellNumber($rawValue, $mode='view', $inputName='', $formId=NULL) {
	switch ($mode) {
		case 'view':
			return price($rawValue);
		case 'modify': case 'new':
			$inputAttributes = array(
				'value' => $rawValue,
				'name' => $inputName,
				'placeholder' => '0,00',
				'pattern' => '\d+(?:[.,]\d+)?',
				'required' => 'required',
			);
			if ($formId !== NULL) {
				$inputAttributes['form'] = $formId;
			}
			return _tagWithAttributes('input', $inputAttributes);
		case 'raw':
			return $rawValue;
		case 'text':
			return strip_tags($rawValue);
		case 'search':
			return '<input name="search_' . $inputName . '" value="'
				   . htmlspecialchars(GETPOST('search_' . $inputName), ENT_QUOTES)
				   . '" />';
	}
	return $rawValue;
}

/**
 * @param $rawValue
 * @param string $mode
 * @param string $inputName
 * @return string
 */
function _getCellCurrencyCode($rawValue, $mode='view', $inputName='', $formId=NULL) {
	global $db, $langs;
	$form = new Form($db);
	switch ($mode) {
		case 'view': case 'modify': // 'modify' because the currency code is read-only
		return $langs->cache_currencies[$rawValue]['label'] . ' (' . $langs->getCurrencySymbol($rawValue) . ')';
		case 'new':
			$select = $form->selectMultiCurrency($rawValue, $inputName, 1);
			if ($formId) {
				// add form attribute to the output of selectCurrency
				$select = preg_replace(
					'/^<select /i',
					'<select form="' . htmlspecialchars($formId, ENT_QUOTES) . '" ',
					$select
				);
			}
			return $select;
		case 'raw':
			return $rawValue;
		case 'text':
			return strip_tags($rawValue);
		case 'search':
			return '<input name="search_' . $inputName . '" value="'
				   . htmlspecialchars(GETPOST('search_' . $inputName), ENT_QUOTES)
				   . '"'
				   . ' />';
	}
	return $rawValue;
}

///**
// * @param $rawValue
// * @param string $mode
// * @param string $inputName
// * @return string
// */
//function _getCellEntity($rawValue, $mode='view', $inputName='', $formId=NULL) {
//	global $db, $langs;
//	$form = new Form($db);
//	switch ($mode) {
//		case 'view': case 'modify': // 'modify' because the entity is read-only
//		return intval($rawValue);
//		case 'new':
//			$mc = new ActionsMulticompany($db);
//			$select = $mc->select_entities($rawValue, $inputName);
//			if ($formId) {
//				// add form attribute to the output of selectCurrency
//				$select = preg_replace(
//					'/^<select /i',
//					'<select form="' . htmlspecialchars($formId, ENT_QUOTES) . '" ',
//					$select
//				);
//			}
//			return $select;
//		case 'raw':
//			return $rawValue;
//		case 'text':
//			return strip_tags($rawValue);
//	}
//	return $rawValue;
//}

/**
 * @param array $colParam
 * @return string
 */
function _cellContentCallbackName($colParam) {
	global $langs;
	$cellContentCallback = '_getCellDefault';
	// possible override in column definition
	if (isset($colParam['callback'])) {
		$cbName = $colParam['callback'];
		// mandatory function name prefix: '_getCell' (some day, the callback may come from untrusted input)
		if (strpos($cbName, '_getCell') !== 0) $cbName = '_getCell' . $cbName;
		if (function_exists($cbName)) { $cellContentCallback = $cbName; }
		else {
			_setEventMessageOnce(
				$langs->trans('ErrorCallbackNotFound', $cbName),
				'warnings'
			);
		}
	}
	return $cellContentCallback;
}

/**
 * Returns an opening (or self-closing) tag with the (escaped) requested attributes
 *
 * Example: _tagWithAttributes('input', ['name' => 'test', 'value' => '"hello"'])
 *          => '<input name="test" value="&quot;nothing&quot;" />'
 *
 *
 * @param string $tagName
 * @param array $TAttribute  [$attrName => $attrVal]
 * @return string
 */
function _tagWithAttributes($tagName, $TAttribute) {
	$selfClosing = in_array($tagName, array('area', 'base', 'br', 'col', 'embed', 'hr', 'img', 'input', 'link', 'meta', 'param', 'source', 'track', 'wbr'));
	$tag = '<' . $tagName;
	foreach ($TAttribute as $attrName => $attrVal) {
		$tag .= ' ' . $attrName . '="' . str_replace("\n", "&#10;", htmlspecialchars($attrVal, ENT_QUOTES)) . '"';
	}
	$tag .= $selfClosing ? ' />' : ' >';
	return $tag;
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


/**
 * Default: view all currency rates
 * @param DoliDB $db
 */
function _actionView($db) {
	_mainView($db, 'view', intval(GETPOST('id', 'int')));
}

/**
 * Add a new currency rate
 * @param DoliDB $db
 */
function _actionAdd($db) {
	global $langs, $conf;
	$dateSync = GETPOST('date_sync', 'alpha');
	$rate = GETPOST('rate', 'int');
	$code = GETPOST('code', 'aZ09');
	$entity = intval($conf->entity);
	$sql = 'INSERT INTO ' . MAIN_DB_PREFIX . 'multicurrency_rate (`date_sync`, `rate`, `fk_multicurrency`, `entity`)'
		   . ' VALUES ('
		   . ' "' . $db->escape($dateSync) . '"'
		   . ' ,"' . $db->escape($rate) . '"'
		   . ' , (SELECT rowid FROM llx_multicurrency WHERE code = "'. $db->escape($code) . '" AND entity IN ('. getEntity('multicurrency') .') LIMIT 1)'
		   . ' ,"' . $db->escape($entity) . '"'
		   .')';
	$resql = $db->query($sql);
	if (!$resql) {
		setEventMessages($langs->trans('TODOSaveFailed'), array(), 'errors');
	}
	_mainView($db, 'view');
}

/**
 * Show a currency rate in edit mode
 * @param DoliDB $db
 */
function _actionModify($db) {
	$id = intval(GETPOST('id', 'int'));
	_mainView($db, 'modify', $id);
}

/**
 * Saves a currency rate
 * @param $db
 */
function _actionUpdate($db) {
	global $langs;
	$id = intval(GETPOST('id', 'int'));
	$dateSync = GETPOST('date_sync', 'alpha');
	$rate = GETPOST('rate', 'int');
	$date = date_parse($dateSync);
	$date = dol_mktime(
		$date['hour'],
		$date['minute'],
		$date['second'],
		$date['month'],
		$date['day'],
		$date['year']
	);
	$sql = /** @lang SQL */
		'UPDATE ' . MAIN_DB_PREFIX . 'multicurrency_rate SET'
		. '   date_sync = "' . $db->idate($date) . '",'
		. '   rate = "' . price2num($rate) . '"'
		. ' WHERE rowid = ' . $id;
	$resql = $db->query($sql);
	if (!$resql) {
		setEventMessages($langs->trans($db->lasterror), array(), 'errors');
	} else {
		setEventMessages($langs->trans('Saved'), array(), 'mesgs');
	}
	_mainView($db);
}

/**
 * Show a confirm form prior to deleting a currency rate
 * @param DoliDB $db
 */
function _actionDelete($db) {
	global $langs;
	global $delayedhtmlcontent;
	$id = intval(GETPOST('id', 'int'));
	$form = new Form($db);
	$formParams = array(
		'id' => $id,
	);
	if (isset($page)) $formParams['page'] = $page;
	$formParams = http_build_query($formParams);
	$delayedhtmlcontent .= $form->formconfirm(
		$_SERVER["PHP_SELF"].'?'.$formParams,
		$langs->trans('DeleteLine'),
		$langs->trans('ConfirmDeleteLine'),
		'confirm_delete',
		'',
		0,
		1
	);
	_mainView($db, 'view');
}

/**
 * Delete a currency rate
 * @param DoliDB $db
 */
function _actionConfirmDelete($db) {
	global $langs;
	$id = intval(GETPOST('id', 'int'));
	if ($id === 0) {
		setEventMessages($langs->trans('WrongID'), array(), 'errors');
	} else {
		$sql = 'DELETE FROM ' . MAIN_DB_PREFIX . 'multicurrency_rate'
			. ' WHERE rowid = ' . $id;
		$resql = $db->query($sql);
		if (!$resql) {
			setEventMessages($db->lasterror, array(), 'errors');
		} else {
			setEventMessages($langs->trans('MulticurrencyRateDeleted'), array(), 'mesgs');
		}
	}
	_mainView($db, 'view');
}

/**
 * Calls setEventMessages only if $message is not already stored for display
 *
 * @param string $message
 * @param string $level 'errors', 'mesgs', 'warnings'
 */
function _setEventMessageOnce($message, $level='errors') {
	if (!in_array($message, $_SESSION['dol_events'][$level])) {
		setEventMessages($message, array(), $level);
	}
}
