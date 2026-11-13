<?php
/* Copyright (C) 2004-2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026		Nick Fragoulis
 * Copyright (C) 2026		Jose Martinez			<jose.martinez@pichinov.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/ai/admin/log_viewer.php
 * \ingroup ai
 * \brief AI Request Log Viewer with Payload Inspection
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * The main.inc.php has been included so the following variable are now defined:
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ai/class/airequestlog.class.php';

// Load translation files required by the page
$langs->loadLangs(array("admin", "other"));

// Get parameters
$action     = GETPOST('action', 'aZ09') ? GETPOST('action', 'aZ09') : 'view'; // The action 'view', 'purge', 'confirm_purge', 'delete', ...
$massaction = GETPOST('massaction', 'alpha'); // The bulk action (combo box choice into lists)
$confirm    = GETPOST('confirm', 'alpha'); // Result of a confirmation
$cancel     = GETPOST('cancel', 'alpha'); // We click on a Cancel button
$toselect   = GETPOST('toselect', 'array:int'); // Array of ids of elements selected into a list
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'ailoglist'; // To manage different context of search
$optioncss  = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$mode       = GETPOST('mode', 'aZ'); // The display mode ('list', ...)

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT('page');
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	// If $page is not defined, or '' or -1 or if we click on clear filters
	$page = 0;
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

// Initialize technical objects
$object = new AiRequestLog($db);
$hookmanager->initHooks(array($contextpage)); 	// Note that conf->hooks_modules contains array of activated contexes

// Default sort order (if not yet defined by previous GETPOST)
if (!$sortfield) {
	$sortfield = "l.date_request";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

// Initialize array of search criteria
$search_date_start = GETPOSTDATE('search_date_start', '', 'tzuserrel');
$search_date_end = GETPOSTDATE('search_date_end', 'end', 'tzuserrel');
$search_user = GETPOST('search_user', 'alpha');
$search_query = GETPOST('search_query', 'alpha');
$search_tool = GETPOST('search_tool', 'alpha');
$search_provider = GETPOST('search_provider', 'alpha');
$search_time_min = GETPOST('search_time_min', 'alpha');
$search_time_max = GETPOST('search_time_max', 'alpha');
$search_status = GETPOST('search_status', 'alpha');
if ($search_status == '-1') {
	$search_status = '';
}

// Definition of array of fields for columns
$arrayfields = array(
	'l.date_request' => array('label' => 'Date', 'checked' => '1', 'enabled' => '1', 'position' => 10),
	'u.login' => array('label' => 'User', 'checked' => '1', 'enabled' => '1', 'position' => 20),
	'l.query_text' => array('label' => 'Query', 'checked' => '1', 'enabled' => '1', 'position' => 30),
	'l.tool_name' => array('label' => 'MCPTool', 'checked' => '1', 'enabled' => '1', 'position' => 40),
	'l.provider' => array('label' => 'Provider', 'checked' => '1', 'enabled' => '1', 'position' => 50),
	'l.execution_time' => array('label' => 'Time', 'checked' => '1', 'enabled' => '1', 'position' => 60),
	'l.tokens_output' => array('label' => 'Tokens', 'checked' => '1', 'enabled' => '1', 'position' => 70, 'help' => 'input / output (thinking included)'),
	'cost' => array('label' => 'Cost', 'checked' => '1', 'enabled' => '1', 'position' => 80),
	'l.status' => array('label' => 'Status', 'checked' => '1', 'enabled' => '1', 'position' => 90),
);
// Add hook to complete $arrayfield
$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('completeArrayFields', $parameters, $object, $action); // Note that $action and $object may have been modified by hook

$arrayfields = dol_sort_array($arrayfields, 'position');

// Permissions
$permissiontoread = $user->admin;
$permissiontodelete = $user->admin;

// Security check
if (!isModEnabled('ai')) {
	accessforbidden('Module AI not activated.');
}
if (!$permissiontoread) {
	accessforbidden();
}


/*
 * Actions
 */

if (GETPOST('cancel', 'alpha')) {
	$action = 'list';
	$massaction = '';
}
if (!GETPOST('confirmmassaction', 'alpha') && $massaction != 'presend' && $massaction != 'confirm_presend') {
	$massaction = '';
}

$parameters = array('arrayfields' => &$arrayfields);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Selection of new fields
	include DOL_DOCUMENT_ROOT.'/core/actions_changeselectedfields.inc.php';

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
		$search_date_start = '';
		$search_date_end = '';
		$search_user = '';
		$search_query = '';
		$search_tool = '';
		$search_provider = '';
		$search_time_min = '';
		$search_time_max = '';
		$search_status = '';
		$toselect = array();
	}
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')
		|| GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha')) {
		$massaction = ''; // Protection to avoid mass action if we force a new search during a mass action confirmation
	}

	// Mass action delete (confirmation step is done by massactions_pre.tpl.php)
	if ($action == 'delete' && $confirm == 'yes' && $permissiontodelete) {
		if (empty($toselect)) {
			setEventMessages($langs->trans("NoRecordSelected"), null, 'warnings');
		} else {
			$sql = "DELETE FROM ".$db->prefix()."ai_request_log";
			$sql .= " WHERE rowid IN (".$db->sanitize(implode(',', $toselect)).")";
			$sql .= " AND entity IN (".getEntity('airequestlog').")";

			$resql = $db->query($sql);
			if ($resql) {
				setEventMessages($langs->trans("SelectedLogsDeleted")." (".$db->affected_rows($resql).")", null, 'mesgs');
				$toselect = array();
			} else {
				setEventMessages($db->lasterror(), null, 'errors');
			}
		}
		$action = 'list';
	}

	// Purge all logs (confirmation step is done by the formconfirm of action 'purge')
	if ($action == 'confirm_purge' && $confirm == 'yes' && $permissiontodelete) {
		$sql = "DELETE FROM ".$db->prefix()."ai_request_log";
		$sql .= " WHERE entity IN (".getEntity('airequestlog').")";

		$resql = $db->query($sql);
		if ($resql) {
			setEventMessages($langs->trans("LogsCleared")." (".$db->affected_rows($resql).")", null, 'mesgs');
			header('Location: '.$_SERVER["PHP_SELF"]);
			exit;
		}
		setEventMessages($db->lasterror(), null, 'errors');
		$action = 'list';
	}
}



/*
 * View
 */

$form = new Form($db);

$now = dol_now();

$title = $langs->trans("AIRequestLogs");
$help_url = '';
$morejs = array();
$morecss = array();


// Build and execute select
// --------------------------------------------------------------------
$sql = "SELECT l.rowid, l.date_request, l.query_text, l.tool_name, l.provider, l.execution_time, l.status, l.error_msg,";
$sql .= " l.raw_request_payload, l.raw_response_payload, l.tokens_input, l.tokens_output, l.model,";
$sql .= " u.login";
// Add fields from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql = preg_replace('/,\s*$/', '', $sql);

$sqlfields = $sql; // $sql fields to remove for count total

$sql .= " FROM ".$db->prefix()."ai_request_log as l";
$sql .= " LEFT JOIN ".$db->prefix()."user as u ON l.fk_user = u.rowid";
// Add table from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;
$sql .= " WHERE l.entity IN (".getEntity('airequestlog').")";
if ($search_date_start) {
	$sql .= " AND l.date_request >= '".$db->idate($search_date_start)."'";
}
if ($search_date_end) {
	$sql .= " AND l.date_request <= '".$db->idate($search_date_end)."'";
}
if ($search_user != '') {
	$sql .= natural_search('u.login', $search_user);
}
if ($search_query != '') {
	$sql .= natural_search('l.query_text', $search_query);
}
if ($search_tool != '') {
	$sql .= natural_search('l.tool_name', $search_tool);
}
if ($search_provider != '') {
	$sql .= natural_search('l.provider', $search_provider);
}
if ($search_time_min != '') {
	$sql .= " AND l.execution_time >= ".((float) price2num($search_time_min));
}
if ($search_time_max != '') {
	$sql .= " AND l.execution_time <= ".((float) price2num($search_time_max));
}
if ($search_status != '') {
	$sql .= " AND l.status = '".$db->escape($search_status)."'";
}
// Add where from hooks
$parameters = array();
$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$sql .= $hookmanager->resPrint;

// Count total nb of records
$nbtotalofrecords = '';
if (!getDolGlobalInt('MAIN_DISABLE_FULL_SCANLIST')) {
	/* The fast and low memory method to get and count full list converts the sql into a sql count */
	$sqlforcount = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT COUNT(*) as nbtotalofrecords', $sql);

	$resql = $db->query($sqlforcount);
	if ($resql) {
		$objforcount = $db->fetch_object($resql);
		$nbtotalofrecords = $objforcount->nbtotalofrecords;
	} else {
		dol_print_error($db);
	}

	if (($page * $limit) > (int) $nbtotalofrecords) {	// if total resultset is smaller than the paging size (filtering), goto and load page 0
		$page = 0;
		$offset = 0;
	}
	$db->free($resql);
}

// Token/cost totals over the SAME filter (not only the displayed page),
// grouped by model so the cost uses each model's own configured price.
$totIn = 0;
$totOut = 0;
$totCost = 0.0;
$costComplete = true;
$sqlforsum = preg_replace('/^'.preg_quote($sqlfields, '/').'/', 'SELECT l.model, SUM(l.tokens_input) as tin, SUM(l.tokens_output) as tout', $sql);
$sqlforsum .= " GROUP BY l.model";
$resql = $db->query($sqlforsum);
if ($resql) {
	while ($sumrow = $db->fetch_object($resql)) {
		$totIn += (int) $sumrow->tin;
		$totOut += (int) $sumrow->tout;
		$price = aiModelPrice((string) $sumrow->model);
		if ($price) {
			$totCost += ((int) $sumrow->tin * $price['in'] + (int) $sumrow->tout * $price['out']) / 1000000;
		} elseif ((int) $sumrow->tin + (int) $sumrow->tout > 0) {
			$costComplete = false;	// tokens exist for a model without a configured price
		}
	}
	$db->free($resql);
} else {
	dol_print_error($db);
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


$arrayofselected = is_array($toselect) ? $toselect : array();

$param = '';
if (!empty($mode)) {
	$param .= '&mode='.urlencode($mode);
}
if (!empty($contextpage) && $contextpage != 'ailoglist') {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_date_start) {
	$param .= '&search_date_startday='.GETPOSTINT('search_date_startday');
	$param .= '&search_date_startmonth='.GETPOSTINT('search_date_startmonth');
	$param .= '&search_date_startyear='.GETPOSTINT('search_date_startyear');
}
if ($search_date_end) {
	$param .= '&search_date_endday='.GETPOSTINT('search_date_endday');
	$param .= '&search_date_endmonth='.GETPOSTINT('search_date_endmonth');
	$param .= '&search_date_endyear='.GETPOSTINT('search_date_endyear');
}
if ($search_user != '') {
	$param .= '&search_user='.urlencode($search_user);
}
if ($search_query != '') {
	$param .= '&search_query='.urlencode($search_query);
}
if ($search_tool != '') {
	$param .= '&search_tool='.urlencode($search_tool);
}
if ($search_provider != '') {
	$param .= '&search_provider='.urlencode($search_provider);
}
if ($search_time_min != '') {
	$param .= '&search_time_min='.urlencode($search_time_min);
}
if ($search_time_max != '') {
	$param .= '&search_time_max='.urlencode($search_time_max);
}
if ($search_status != '') {
	$param .= '&search_status='.urlencode($search_status);
}
// Add $param from hooks
$parameters = array('param' => &$param);
$reshook = $hookmanager->executeHooks('printFieldListSearchParam', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
$param .= $hookmanager->resPrint;

// Use Post/Redirect/Get for filter submissions so browser history contains a
// reconstructible GET request instead of a POST that requires confirmation.
$filterSearchSubmitted = GETPOST('button_search_x', 'alpha') || GETPOST('button_search.x', 'alpha') || GETPOST('button_search', 'alpha');
$filterResetSubmitted = GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha');
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($filterSearchSubmitted || $filterResetSubmitted)) {
	$redirectUrl = dolBuildUrl($_SERVER['PHP_SELF'], array(
		'formfilteraction' => 'list',
		'sortfield' => $sortfield,
		'sortorder' => $sortorder,
	));
	$redirectUrl .= $param;
	header('Location: '.$redirectUrl, true, 303);
	exit;
}

// Output page
// --------------------------------------------------------------------

llxHeader('', $title, $help_url, '', 0, 0, $morejs, $morecss, '', 'mod-ai page-admin_log_viewer bodyforlist');

// Confirmation to purge all logs
if ($action == 'purge') {
	print $form->formconfirm($_SERVER["PHP_SELF"], $langs->trans("ClearAllLogs"), $langs->trans("ConfirmDeleteAllLogs"), 'confirm_purge', '', 'no', 1);
}

// List of mass actions available
$arrayofmassactions = array();
if (!empty($permissiontodelete)) {
	$arrayofmassactions['predelete'] = img_picto('', 'delete', 'class="pictofixedwidth"').$langs->trans("Delete");
}
if (GETPOSTINT('nomassaction') || in_array($massaction, array('presend', 'predelete'))) {
	$arrayofmassactions = array();
}
$massactionbutton = $form->selectMassAction('', $arrayofmassactions);

print '<form method="POST" id="searchFormList" spellcheck="false" action="'.$_SERVER["PHP_SELF"].'">'."\n";
if ($optioncss != '') {
	print '<input type="hidden" name="optioncss" value="'.$optioncss.'">';
}
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="formfilteraction" id="formfilteraction" value="list">';
print '<input type="hidden" name="action" value="list">';
print '<input type="hidden" name="sortfield" value="'.$sortfield.'">';
print '<input type="hidden" name="sortorder" value="'.$sortorder.'">';
print '<input type="hidden" name="page" value="'.$page.'">';
print '<input type="hidden" name="contextpage" value="'.$contextpage.'">';
print '<input type="hidden" name="page_y" value="">';
print '<input type="hidden" name="mode" value="'.$mode.'">';


$newcardbutton = '';
$newcardbutton .= dolGetButtonTitle($langs->trans('ClearAllLogs'), '', 'fa fa-trash', $_SERVER["PHP_SELF"].'?action=purge&token='.newToken(), '', $permissiontodelete);

print_barre_liste($title, $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $massactionbutton, $num, $nbtotalofrecords, 'ai', 0, $newcardbutton, '', $limit, 0, 0, 1);

// Add code for pre mass action (confirmation)
$topicmail = '';
$modelmail = '';
$objecttmp = new AiRequestLog($db);
$trackid = '';
include DOL_DOCUMENT_ROOT.'/core/tpl/massactions_pre.tpl.php';

// Filtered totals: tokens and, when prices are configured, the exact cost
// (each model's tokens priced with its own AI_MODEL_PRICES_JSON entry).
if ($totIn + $totOut > 0) {
	print '<div class="opacitymedium paddingbottom">';
	print $langs->trans("Total").': '.price($totIn, 0, $langs, 0, 0, 0).' in / '.price($totOut, 0, $langs, 0, 0, 0).' out';
	print ' &nbsp;&mdash;&nbsp; '.price($totCost, 0, $langs, 1, -1, 4).($costComplete ? '' : ' (+ '.$langs->trans("Unknown").')');
	print ' &nbsp;<span title="AI_MODEL_PRICES_JSON, price per 1M tokens, e.g. {&quot;gemini-2.5-flash&quot;:{&quot;in&quot;:0.30,&quot;out&quot;:2.50},&quot;default&quot;:{&quot;in&quot;:1,&quot;out&quot;:3}}">'.img_info('').'</span>';
	print '</div>';
}

$moreforfilter = '';

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
$htmlofselectarray = $form->multiSelectArrayWithCheckbox('selectedfields', $arrayfields, $varpage, $conf->main_checkbox_left_column);  // This also change content of $arrayfields with user setup
$selectedfields = $htmlofselectarray;
$selectedfields .= (count($arrayofmassactions) ? $form->showCheckAddButtons('checkforselect', 1) : '');

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="tagtable nobottomiftotal noborder liste'.($moreforfilter ? " listwithfilterbefore" : "").'">'."\n";

// Fields title search
// --------------------------------------------------------------------
print '<tr class="liste_titre_filter">';
// Action column
if ($conf->main_checkbox_left_column) {
	print '<td class="liste_titre center maxwidthsearch">';
	$searchpicto = $form->showFilterButtons('left');
	print $searchpicto;
	print '</td>';
}
// Date
if (!empty($arrayfields['l.date_request']['checked'])) {
	print '<td class="liste_titre center">';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_start ? $search_date_start : '', 'search_date_start', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('From'), 'tzuserrel');
	print '</div>';
	print '<div class="nowrap">';
	print $form->selectDate($search_date_end ? $search_date_end : '', 'search_date_end', 0, 0, 1, '', 1, 0, 0, '', '', '', '', 1, '', $langs->trans('to'), 'tzuserrel');
	print '</div>';
	print '</td>';
}
// User
if (!empty($arrayfields['u.login']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_user" value="'.dolPrintHTMLForAttribute($search_user).'"></td>';
}
// Query
if (!empty($arrayfields['l.query_text']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth150" name="search_query" value="'.dolPrintHTMLForAttribute($search_query).'"></td>';
}
// Tool
if (!empty($arrayfields['l.tool_name']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_tool" value="'.dolPrintHTMLForAttribute($search_tool).'"></td>';
}
// Provider
if (!empty($arrayfields['l.provider']['checked'])) {
	print '<td class="liste_titre"><input type="text" class="flat maxwidth100" name="search_provider" value="'.dolPrintHTMLForAttribute($search_provider).'"></td>';
}
// Time
if (!empty($arrayfields['l.execution_time']['checked'])) {
	print '<td class="liste_titre center nowraponall">';
	print '<input type="text" class="flat width40" name="search_time_min" value="'.dolPrintHTMLForAttribute($search_time_min).'" placeholder="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv('Min')).'">';
	print '<input type="text" class="flat width40" name="search_time_max" value="'.dolPrintHTMLForAttribute($search_time_max).'" placeholder="'.dolPrintHTMLForAttribute($langs->transnoentitiesnoconv('Max')).'">';
	print '</td>';
}
// Tokens and Cost columns carry no filter
if (!empty($arrayfields['l.tokens_output']['checked'])) {
	print '<td class="liste_titre"></td>';
}
if (!empty($arrayfields['cost']['checked'])) {
	print '<td class="liste_titre"></td>';
}
// Status
if (!empty($arrayfields['l.status']['checked'])) {
	print '<td class="liste_titre center parentonrightofpage">';
	print $form->selectarray('search_status', array('success' => $langs->trans("Success"), 'confirm' => $langs->trans("Confirm"), 'error' => $langs->trans("Error")), $search_status, 1, 0, 0, '', 0, 0, 0, '', 'maxwidth100 search_status width100 onrightofpage', 1);
	print '</td>';
}
// Fields from hook
$parameters = array('arrayfields' => $arrayfields);
$reshook = $hookmanager->executeHooks('printFieldListOption', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Details column
print '<td class="liste_titre"></td>';
// Action column
if (!$conf->main_checkbox_left_column) {
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
if ($conf->main_checkbox_left_column) {
	print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['l.date_request']['checked'])) {
	print_liste_field_titre($arrayfields['l.date_request']['label'], $_SERVER["PHP_SELF"], "l.date_request", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['u.login']['checked'])) {
	print_liste_field_titre($arrayfields['u.login']['label'], $_SERVER["PHP_SELF"], "u.login", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['l.query_text']['checked'])) {
	print_liste_field_titre($arrayfields['l.query_text']['label'], $_SERVER["PHP_SELF"], "l.query_text", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['l.tool_name']['checked'])) {
	print_liste_field_titre($arrayfields['l.tool_name']['label'], $_SERVER["PHP_SELF"], "l.tool_name", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['l.provider']['checked'])) {
	print_liste_field_titre($arrayfields['l.provider']['label'], $_SERVER["PHP_SELF"], "l.provider", "", $param, '', $sortfield, $sortorder);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['l.execution_time']['checked'])) {
	print_liste_field_titre($arrayfields['l.execution_time']['label'], $_SERVER["PHP_SELF"], "l.execution_time", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['l.tokens_output']['checked'])) {
	print_liste_field_titre($arrayfields['l.tokens_output']['label'], $_SERVER["PHP_SELF"], "l.tokens_output", "", $param, '', $sortfield, $sortorder, 'center ', $arrayfields['l.tokens_output']['help']);
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['cost']['checked'])) {
	print_liste_field_titre($arrayfields['cost']['label'], $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
if (!empty($arrayfields['l.status']['checked'])) {
	print_liste_field_titre($arrayfields['l.status']['label'], $_SERVER["PHP_SELF"], "l.status", "", $param, '', $sortfield, $sortorder, 'center ');
	$totalarray['nbfield']++;
}
// Hook fields
$parameters = array('arrayfields' => $arrayfields, 'param' => $param, 'sortfield' => $sortfield, 'sortorder' => $sortorder, 'totalarray' => &$totalarray);
$reshook = $hookmanager->executeHooks('printFieldListTitle', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;
// Details column
print_liste_field_titre('', $_SERVER["PHP_SELF"], "", "", $param, '', $sortfield, $sortorder, 'center ');
$totalarray['nbfield']++;
// Action column
if (!$conf->main_checkbox_left_column) {
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
	if (empty($obj)) {
		break; // Should not happen
	}

	// Show line of result
	print '<tr data-rowid="'.((int) $obj->rowid).'" class="oddeven row-with-select">';

	// Action column
	if ($conf->main_checkbox_left_column) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.((int) $obj->rowid).'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.((int) $obj->rowid).'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Date
	if (!empty($arrayfields['l.date_request']['checked'])) {
		print '<td class="center nowraponall">'.dol_print_date($db->jdate($obj->date_request), 'dayhour', 'tzuserrel').'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// User
	if (!empty($arrayfields['u.login']['checked'])) {
		print '<td class="tdoverflowmax150">'.($obj->login ? dolPrintLabel($obj->login) : $langs->trans("Unknown")).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Query
	if (!empty($arrayfields['l.query_text']['checked'])) {
		print '<td title="'.dolPrintHTMLForAttribute((string) $obj->query_text).'">'.dolPrintLabel(dol_trunc((string) $obj->query_text, 60)).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Tool
	if (!empty($arrayfields['l.tool_name']['checked'])) {
		print '<td class="tdoverflowmax150">'.dolPrintLabel((string) $obj->tool_name).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Provider
	if (!empty($arrayfields['l.provider']['checked'])) {
		print '<td class="tdoverflowmax150">'.dolPrintLabel((string) $obj->provider).'</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Time
	if (!empty($arrayfields['l.execution_time']['checked'])) {
		print '<td class="center nowraponall'.($obj->execution_time > 5 ? ' error' : '').'">'.round((float) $obj->execution_time, 2).'s</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Tokens (input / output) and cost, when the provider reported usage
	$hasusage = (isset($obj->tokens_input) || isset($obj->tokens_output));
	if (!empty($arrayfields['l.tokens_output']['checked'])) {
		if ($hasusage) {
			print '<td class="center nowraponall" title="'.dolPrintHTMLForAttribute((string) $obj->model).'">'.((int) $obj->tokens_input).' / '.((int) $obj->tokens_output).'</td>';
		} else {
			print '<td class="center opacitymedium">-</td>';
		}
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}
	if (!empty($arrayfields['cost']['checked'])) {
		$price = ($hasusage ? aiModelPrice((string) $obj->model) : null);
		if ($price) {
			$cost = ((int) $obj->tokens_input * $price['in'] + (int) $obj->tokens_output * $price['out']) / 1000000;
			print '<td class="center nowraponall">'.price($cost, 0, $langs, 1, -1, 4).'</td>';
		} else {
			print '<td class="center opacitymedium">-</td>';
		}
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Status
	if (!empty($arrayfields['l.status']['checked'])) {
		$badge = 'badge-status0';
		if ($obj->status == $langs->transnoentitiesnoconv("Success")) {
			$badge = 'badge-status4'; // Green
		}
		if ($obj->status == $langs->transnoentitiesnoconv("Confirm")) {
			$badge = 'badge-status3'; // Yellow
		}
		if ($obj->status == $langs->transnoentitiesnoconv('Error')) {
			$badge = 'badge-status8'; // Red
		}
		print '<td class="center"><span class="badge '.$badge.'">'.dolPrintLabel((string) $obj->status).'</span></td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	// Fields from hook
	$parameters = array('arrayfields' => $arrayfields, 'object' => $object, 'obj' => $obj, 'i' => $i, 'totalarray' => &$totalarray);
	$reshook = $hookmanager->executeHooks('printFieldListValue', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
	print $hookmanager->resPrint;

	// Details button (opens the popup with the payloads, provided base64 encoded to keep UTF-8 safe)
	print '<td class="center nowraponall">';
	print '<a href="#" class="ailogshowdetails"';
	print ' data-req="'.dolPrintHTMLForAttribute(base64_encode((string) $obj->raw_request_payload)).'"';
	print ' data-res="'.dolPrintHTMLForAttribute(base64_encode((string) $obj->raw_response_payload)).'"';
	print ' data-err="'.dolPrintHTMLForAttribute(base64_encode((string) $obj->error_msg)).'">';
	print img_picto('', 'search', 'class="pictofixedwidth"').$langs->trans("Detail");
	print '</a>';
	print '</td>';
	if (!$i) {
		$totalarray['nbfield']++;
	}

	// Action column
	if (empty($conf->main_checkbox_left_column)) {
		print '<td class="nowrap center">';
		if ($massactionbutton || $massaction) { // If we are in select mode (massactionbutton defined) or if we have already selected and sent an action ($massaction) defined
			$selected = 0;
			if (in_array($obj->rowid, $arrayofselected)) {
				$selected = 1;
			}
			print '<input id="cb'.((int) $obj->rowid).'" class="flat checkforselect" type="checkbox" name="toselect[]" value="'.((int) $obj->rowid).'"'.($selected ? ' checked="checked"' : '').'>';
		}
		print '</td>';
		if (!$i) {
			$totalarray['nbfield']++;
		}
	}

	print '</tr>'."\n";

	$i++;
}

// If no record found
if ($num == 0) {
	$colspan = $savnbfield;
	$hassearchfilter = ($search_date_start || $search_date_end || $search_user != '' || $search_query != '' || $search_tool != '' || $search_provider != '' || $search_time_min != '' || $search_time_max != '' || $search_status != '');
	print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoLogsFound");
	if ($hassearchfilter) {
		print ' '.$langs->trans("MatchingSearchCriteria");
	}
	print '. '.$langs->trans("TryAskingAI").'.</span></td></tr>';
}


$db->free($resql);

$parameters = array('arrayfields' => $arrayfields, 'sql' => $sql);
$reshook = $hookmanager->executeHooks('printFieldListFooter', $parameters, $object, $action); // Note that $action and $object may have been modified by hook
print $hookmanager->resPrint;

print '</table>'."\n";
print '</div>'."\n";

print '</form>'."\n";


// Popup to show the details (error, request and response payloads) of a log line
print '<div id="ailogdetailspopup" class="hidden">'."\n";
print '<div id="modalErrorBlock" class="hidden marginbottomonly">';
print '<div class="titre">'.$langs->trans("ErrorWarning").'</div>';
print '<div id="modalError" class="error"></div>';
print '</div>';
print '<div class="fichecenter">';
print '<div class="fichehalfleft">';
print '<div class="titre">'.$langs->trans("RequestPayload").'</div>';
print '<textarea id="modalReq" class="centpercent small" rows="25" style="font-family: monospace;" readonly></textarea>';
print '</div>';
print '<div class="fichehalfright">';
print '<div class="titre">'.$langs->trans("ResponsePayload").'</div>';
print '<textarea id="modalRes" class="centpercent small" rows="25" style="font-family: monospace;" readonly></textarea>';
print '</div>';
print '</div>';
print '</div>'."\n";

print '<script nonce="'.getNonce().'">
// UTF-8 safe base64 decoding function
function base64ToUtf8(str) {
	// Going backwards: from bytestream, to percent-encoding, to original string.
	return decodeURIComponent(atob(str).split(\'\').map(function(c) {
		return \'%\' + (\'00\' + c.charCodeAt(0).toString(16)).slice(-2);
	}).join(\'\'));
}

// Function to decode Unicode escape sequences in JSON strings
function decodeUnicodeEscapes(str) {
	// First, try to parse as JSON to handle escaped Unicode properly
	try {
		// If it is a JSON string, parse and stringify to decode escapes
		const parsed = JSON.parse(str);
		return JSON.stringify(parsed, null, 2);
	} catch (e) {
		// If not valid JSON, try to decode Unicode escapes in the string
		return str.replace(/\\\\u([0-9a-fA-F]{4})/g, function(match, p1) {
			return String.fromCharCode(parseInt(p1, 16));
		});
	}
}

// Sanitize HTML to prevent XSS
function escapeHtml(text) {
	const div = document.createElement(\'div\');
	div.textContent = text;
	return div.innerHTML;
}

function openLogModal(btn) {
	// Decode Base64 safely with UTF-8 support
	const reqBase64 = btn.getAttribute(\'data-req\') || \'\';
	const resBase64 = btn.getAttribute(\'data-res\') || \'\';
	const errBase64 = btn.getAttribute(\'data-err\') || \'\';

	let req = \'\';
	let res = \'\';
	let err = \'\';

	try {
		// Decode Unicode escape sequences
		if (reqBase64) {
			req = base64ToUtf8(reqBase64);
			req = decodeUnicodeEscapes(req);
		}
		if (resBase64) {
			res = base64ToUtf8(resBase64);
			res = decodeUnicodeEscapes(res);
		}
		if (errBase64) {
			err = base64ToUtf8(errBase64);
			err = decodeUnicodeEscapes(err);
		}
	} catch (e) {
		console.error(\'Error decoding base64:\', e);
		// Fallback to regular atob if UTF-8 decoding fails
		req = reqBase64 ? atob(reqBase64) : \'\';
		res = resBase64 ? atob(resBase64) : \'\';
		err = errBase64 ? atob(errBase64) : \'\';
	}

	// Set content as plain values (no HTML interpretation)
	jQuery("#modalReq").val(req || \'('.dol_escape_js($langs->transnoentitiesnoconv("NoRequestPayload")).')\');
	jQuery("#modalRes").val(res || \'('.dol_escape_js($langs->transnoentitiesnoconv("NoResponsePayload")).')\');

	if (err) {
		jQuery("#modalError").text(err);
		jQuery("#modalErrorBlock").show();
	} else {
		jQuery("#modalErrorBlock").hide();
	}

	jQuery("#ailogdetailspopup").dialog({
		autoOpen: false,
		closeOnEscape: true,
		modal: true,
		width: \'80%\',
		maxHeight: window.innerHeight - 60,
		title: \''.dol_escape_js($langs->transnoentitiesnoconv("LogDetails")).'\'
	});
	jQuery("#ailogdetailspopup").dialog("open");
}

jQuery(document).ready(function() {
	jQuery(".ailogshowdetails").on("click", function(event) {
		event.preventDefault();
		openLogModal(this);
	});
});
</script>'."\n";

// End of page
llxFooter();
$db->close();
