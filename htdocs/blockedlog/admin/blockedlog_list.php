<?php
/* Copyright (C) 2017		ATM Consulting				<contact@atm-consulting.fr>
 * Copyright (C) 2017-2018	Laurent Destailleur			<eldy@destailleur.fr>
 * Copyright (C) 2018-2025  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024-2026	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *    \file       htdocs/blockedlog/admin/blockedlog_list.php
 *    \ingroup    blockedlog
 *    \brief      Page to list and view unalterable logs
 */

// Load Dolibarr environment
require '../../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Societe $mysoc
 * @var Translate $langs
 * @var User $user
 *
 * @var string $dolibarr_main_db_name
 */
require_once DOL_DOCUMENT_ROOT.'/blockedlog/lib/blockedlog.lib.php';
require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/json.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'banks', 'bills', 'blockedlog', 'other'));

// Get Parameters
$action      = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : getDolDefaultContextPage(__FILE__); // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss   = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

$search_showonlyerrors = GETPOSTINT('search_showonlyerrors');
if ($search_showonlyerrors < 0) {
	$search_showonlyerrors = 0;
}

$search_startyear = GETPOSTINT('search_startyear');
$search_startmonth = GETPOSTINT('search_startmonth');
$search_startday = GETPOSTINT('search_startday');
$search_endyear = GETPOSTINT('search_endyear');
$search_endmonth = GETPOSTINT('search_endmonth');
$search_endday = GETPOSTINT('search_endday');
$search_id = GETPOST('search_id', 'alpha');					// Can be a USF search string
$search_fk_user = GETPOST('search_fk_user', 'intcomma');
$search_start = -1;
if (GETPOST('search_startyear') != '') {
	$search_start = dol_mktime(0, 0, 0, $search_startmonth, $search_startday, $search_startyear);
}
$search_end = -1;
if (GETPOST('search_endyear') != '') {
	$search_end = dol_mktime(23, 59, 59, $search_endmonth, $search_endday, $search_endyear);
}
$search_code = GETPOST('search_code', 'array:alpha');
$search_module_source = GETPOST('search_module_source', 'array:alpha');
$search_pos_source = GETPOST('search_pos_source');
$search_ref = GETPOST('search_ref', 'alpha');
$search_amount = GETPOST('search_amount', 'alpha');
$search_signature = GETPOST('search_signature', 'alpha');

if (($search_start == -1 || empty($search_start)) && !GETPOSTISSET('search_startmonth') && !GETPOSTISSET('begin')) {
	$search_start = dol_time_plus_duree(dol_now(), -1, 'w');
	$tmparray = dol_getdate($search_start);
	$search_startday = $tmparray['mday'];
	$search_startmonth = $tmparray['mon'];
	$search_startyear = $tmparray['year'];
}

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page == -1) {
	$page = 0;
}     // If $page is not defined, or '' or -1
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (empty($sortfield)) {
	$sortfield = 'rowid';
}
if (empty($sortorder)) {
	$sortorder = 'DESC';
}

$block_static = new BlockedLog($db);
$block_static->loadTrackedEvents();

// Access Control
if ((!$user->admin && !$user->hasRight('blockedlog', 'read')) || !isModEnabled('blockedlog')) {
	accessforbidden();
}

$result = restrictedArea($user, 'blockedlog', 0, '');

// Execution Time
$max_execution_time_for_importexport = getDolGlobalInt('EXPORT_MAX_EXECUTION_TIME', 300); // 5mn if not defined
$max_time = @ini_get("max_execution_time");
if ($max_time && $max_time < $max_execution_time_for_importexport) {
	dol_syslog("max_execution_time=".$max_time." is lower than max_execution_time_for_importexport=".$max_execution_time_for_importexport.". We try to increase it dynamically.");
	@ini_set("max_execution_time", $max_execution_time_for_importexport); // This work only if safe mode is off. also web servers has timeout of 300
}

$MAXLINES = getDolGlobalInt('BLOCKEDLOG_MAX_LINES', 10000);
$MAXFORSHOWNLINKS = getDolGlobalInt('BLOCKEDLOG_MAX_FOR_SHOWN_LINKS', 100);


/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_id = '';
	$search_fk_user = '';
	$search_start = -1;
	$search_end = -1;
	$search_code = array();
	$search_module_source = '';
	$search_pos_source = '';
	$search_ref = '';
	$search_amount = '';
	$search_signature = '';
	$search_showonlyerrors = 0;
	$search_startyear = '';
	$search_startmonth = '';
	$search_startday = '';
	$search_endyear = '';
	$search_endmonth = '';
	$search_endday = '';
	$toselect = array();
	$search_array_options = array();
}

if (userIsTaxAuditor()) {
	// When this hidden option is on, open another tab as the tab by default
	header("Location: ".DOL_URL_ROOT."/blockedlog/admin/blockedlog_archives.php");
	exit;
}


/*
 *	View
 */

$form = new Form($db);

if (GETPOST('withtab', 'alpha')) {
	$title = $langs->trans("ModuleSetup").' '.$langs->trans('BlockedLog');
} else {
	$title = $langs->trans("BrowseBlockedLog");
}
$help_url = "EN:Module_Unalterable_Archives_-_Logs|FR:Module_Archives_-_Logs_Inaltérable";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-blockedlog page-admin_blockedlog_list');

// Get list of blocked logs.
// Warning: This make a fetch on each line.
$blocks = $block_static->getLog('all', (string) $search_id, $MAXLINES, $sortfield, $sortorder, (int) $search_fk_user, $search_start, $search_end, $search_ref, $search_amount, $search_code, $search_signature, $search_module_source, $search_pos_source);
if (!is_array($blocks)) {
	if ($blocks == -2) {
		setEventMessages($langs->trans("TooManyRecordToScanRestrictFilters", $MAXLINES), null, 'errors');
	} else {
		dol_print_error($block_static->db, $block_static->error, $block_static->errors);
		exit;
	}
}

$linkback = '';
if (GETPOST('withtab', 'alpha')) {
	$linkback = '<a href="'.dolBuildUrl($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
}

$morehtmlcenter = '';
$texttop = '';

$registrationnumber = getHashUniqueIdOfRegistration();
if (userIsTaxAuditor()) {
	$texttop = '<small class="opacitymedium">'.$langs->trans("RegistrationNumber").':</small> <small>'.dol_trunc($registrationnumber, 10).'</small>';
	if (!isRegistrationDataSavedAndPushed()) {
		$texttop = '';
	}
}

print load_fiche_titre($title.'<br>'.$texttop, $linkback, 'blockedlog', 0, '', '', $morehtmlcenter);

$head = blockedlogadmin_prepare_head(GETPOST('withtab', 'alpha'));

print dol_get_fiche_head($head, 'fingerprints', '', -1);

//print $texttop;
//print '<br><br>';

print '<div class="justify">';
print '<span class="opacitymedium hideonsmartphone">';
print $langs->trans("FingerprintsDesc")."<br>";
print $langs->trans("FilesIntegrityDesc").': ';
print '</span>';
print '<a href="'.DOL_URL_ROOT.'/blockedlog/admin/filecheck.php">'.img_picto('', 'url', 'class="pictofixedwidth"').$langs->trans("FileCheck").'</a>';
print '<br>';
print "</div>\n";

$htmltext = '';
$htmltext .= $langs->trans("UnalterableLogTool2", $langs->transnoentitiesnoconv("Archives"))."<br>";
$htmltext .= $langs->trans("UnalterableLogTool3")."<br>";
if ($mysoc->country_code == 'FR') {
	$htmltext .= '<br>'.$langs->trans("UnalterableLogTool1FR").'<br>';
}

print info_admin($htmltext, 0, 0, 'warning');


print '<br>';

$param = '';
if ($contextpage != getDolDefaultContextPage(__FILE__)) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
}
if ($search_id != '') {
	$param .= '&search_id='.urlencode($search_id);
}
if ($search_ref != '') {
	$param .= '&search_ref='.urlencode($search_ref);
}
if ($search_fk_user > 0) {
	$param .= '&search_fk_user='.urlencode($search_fk_user);
}
if ($search_amount) {
	$param .= '&search_module_source='.urlencode($search_module_source);
}
if ($search_pos_source) {
	$param .= '&search_pos_source='.urlencode($search_pos_source);
}
if ($search_startyear > 0) {
	$param .= '&search_startyear='.((int) $search_startyear);
}
if ($search_startmonth > 0) {
	$param .= '&search_startmonth='.((int) $search_startmonth);
}
if ($search_startday > 0) {
	$param .= '&search_startday='.((int) $search_startday);
}
if ($search_endyear > 0) {
	$param .= '&search_endyear='.((int) $search_endyear);
}
if ($search_endmonth > 0) {
	$param .= '&search_endmonth='.((int) $search_endmonth);
}
if ($search_endday > 0) {
	$param .= '&search_endday='.((int) $search_endday);
}
if ($search_amount) {
	$param .= '&search_amount='.urlencode($search_amount);
}
if ($search_signature) {
	$param .= '&search_signature='.urlencode($search_signature);
}
if ($search_showonlyerrors > 0) {
	$param .= '&search_showonlyerrors='.((int) $search_showonlyerrors);
}
if (GETPOST('withtab', 'alpha')) {
	$param .= '&withtab='.urlencode(GETPOST('withtab', 'alpha'));
}

print '<form method="POST" id="searchFormList" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'">';

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
print '<input type="hidden" name="withtab" value="'.GETPOST('withtab', 'alpha').'">';

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent liste">';

// Line of filters
print '<tr class="liste_titre_filter">';

// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}

print '<td class="liste_titre"><input type="text" class="maxwidth50" name="search_id" value="'.dol_escape_htmltag($search_id).'"></td>';

print '<td class="liste_titre">';
//print $langs->trans("from").': ';
print $form->selectDate($search_start, 'search_start');
//print '<br>';
//print $langs->trans("to").': ';
print $form->selectDate($search_end, 'search_end');
print '</td>';

// User
print '<td class="liste_titre">';
print $form->select_dolusers($search_fk_user, 'search_fk_user', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'maxwidth100');
print '</td>';

// Module source
print '<td class="liste_titre">';
print $form->multiselectarray('search_module_source', $block_static->trackedmodules, $search_module_source, 0, 0, 'minwidth75 maxwidth200', 1);
print '</td>';

// POS source
print '<td class="liste_titre">';
print '<input type="text" class="maxwidth50" name="search_pos_source" value="'.dol_escape_htmltag($search_pos_source).'">';
print '</td>';

// Actions code
print '<td class="liste_titre">';
print $form->multiselectarray('search_code', $block_static->trackedevents, $search_code, 0, 0, 'maxwidth200', 1);
print '</td>';

// Ref
print '<td class="liste_titre"><input type="text" class="maxwidth100" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';

// Amount
print '<td class="liste_titre right"><input type="text" class="maxwidth50" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';

// Full data
print '<td class="liste_titre"></td>';

// Fingerprint
print '<td class="liste_titre"><input type="text" class="maxwidth50" name="search_signature" value="'.dol_escape_htmltag($search_signature).'"></td>';

// Status
print '<td class="liste_titre center minwidth75imp parentonrightofpage">';
$array = array("1" => "OnlyNonValid");
print $form->selectarray('search_showonlyerrors', $array, $search_showonlyerrors, 1, 0, 0, '', 1, 0, 0, 'ASC', 'search_status width100 onrightofpage', 1);
print '</td>';

// Link to debug information object
if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
	print '<td class="liste_titre"></td>';
}

// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}

print '</tr>';


print '<tr class="liste_titre">';
// Action column
if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList('<span id="blockchainstatus"></span>', 0, $_SERVER["PHP_SELF"], '', '', $param, 'class="center"', $sortfield, $sortorder, '')."\n";
}
print getTitleFieldOfList($langs->trans('#'), 0, $_SERVER["PHP_SELF"], 'rowid', '', $param, '', $sortfield, $sortorder, 'minwidth50 ')."\n";
print getTitleFieldOfList($langs->trans('Date'), 0, $_SERVER["PHP_SELF"], 'date_creation', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Author'), 0, $_SERVER["PHP_SELF"], 'user_fullname', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('POS'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Terminal'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Action'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Ref'), 0, $_SERVER["PHP_SELF"], 'ref_object', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Amount'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ', 0, $langs->trans("TotalTTCIfInvoiceSeeCompleteDataForDetail").'<br>'.$langs->trans("AmountInCurrency", getDolCurrency()))."\n";
print getTitleFieldOfList($langs->trans('DataOfArchivedEvent'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ', 0, $langs->trans('DataOfArchivedEventHelp'), 1)."\n";
print getTitleFieldOfList($langs->trans('Fingerprint'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($form->textwithpicto($langs->trans('Status'), $langs->trans('DataOfArchivedEventHelp2')), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ')."\n";
if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
	print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
}
// Action column
if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
	print getTitleFieldOfList('<span id="blockchainstatus"></span>', 0, $_SERVER["PHP_SELF"], '', '', $param, 'class="center"', $sortfield, $sortorder, '')."\n";
}
print '</tr>';

$checkresult = array();
$checkdetail = array();
$loweridinerror = 0;

if (getDolGlobalString('BLOCKEDLOG_SCAN_ALL_FOR_LOWERIDINERROR')) {
	// This is version that is faster but require more memory and report errors that are outside the filter range

	// TODO Make a full scan of table in reverse order of id of $block, so we can use the parameter $previoushash into checkSignature to save requests
	// to find the $loweridinerror.
} else {
	// This is version that optimize the memory (note: it will not report errors that are outside the filter range, but we don't need them)
	if (is_array($blocks)) {
		foreach ($blocks as &$block) {
			// Enable this log to get information used to recalculate the signature
			//var_dump($block->id.' '.$block->signature, $block->object_data);

			$tmpcheckresult = $block->checkSignature('', 1); // Note: this make a sql request at each call, we can't avoid this as the sorting order is various

			$checksignature = $tmpcheckresult['checkresult'];

			$checkresult[$block->id] = $checksignature; // false if error
			$checkdetail[$block->id] = $tmpcheckresult;

			if (!$checksignature) {
				if (empty($loweridinerror)) {
					$loweridinerror = $block->id;
				} else {
					$loweridinerror = min($loweridinerror, $block->id);
				}
			}
		}
	}
}

$refinvoicefound = array();
$totalhtamount = array();
$totalvatamount = array();
$totalamount = array();

if (is_array($blocks)) {
	$nbshown = 0;
	$object_link = '';
	$object_link_title = '';

	foreach ($blocks as &$block) {
		//if (empty($search_showonlyerrors) || ! $checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror))
		if (empty($search_showonlyerrors) || !$checkresult[$block->id]) {
			$nbshown++;

			if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {
				if ($nbshown < $MAXFORSHOWNLINKS) {	// For performance and memory purpose, we get/show the debug info link of objects only for the 100 first output
					$object_link = $block->getObjectLink();
					$object_link_title = '';
				} else {
					$object_link = $block->element.'/'.$block->fk_object;
					$object_link_title = $langs->trans('LinkHasBeenDisabledForPerformancePurpose');
				}
			}

			print '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td>';
				print '</td>';
			}

			// ID
			print '<td>'.dolPrintHTML((string) $block->id).'</td>';

			// Date
			print '<td class="nowraponall">'.dol_print_date($block->date_creation, 'dayhour', 'tzuserrel').'</td>';

			// User
			print '<td class="tdoverflowmax200" title="'.dolPrintHTMLForAttribute($block->user_fullname).'">';
			//print $block->getUser()
			print dolPrintHTML($block->user_fullname);
			print '</td>';

			// Module
			$labelofmodulesource = $block->module_source;
			print '<td class="tdoverflowmax250" title="'.dolPrintHTMLForAttribute($labelofmodulesource).'">'.dolPrintHTML($labelofmodulesource).'</td>';

			// Terminal
			print '<td>'.dolPrintHTML($block->pos_source).'</td>';

			// Action
			$labelofaction = $langs->transnoentitiesnoconv('log'.$block->action);
			print '<td class="tdoverflowmax250" title="'.dolPrintHTMLForAttribute($labelofaction).'">'.dolPrintHTML($labelofaction).'</td>';

			// Ref
			print '<td class="nowraponall"><div class="smallheight">';
			if (!empty($block->ref_object)) {
				print dol_escape_htmltag($block->ref_object);
				if ($block->linktype && $block->linktoref) {
					if ($block->linktype == 'payment') {
						print '<br><span class="opacitymedium small">'.$langs->trans("PaymentOf").' '.$block->linktoref.'</span>';
					}
					if ($block->linktype == 'replacedby') {
						print '<br><span class="opacitymedium small">'.$langs->trans("ReplacedBy").' '.$block->linktoref.'</span>';
					}
					if ($block->linktype == 'credit_note_of') {
						print '<br><span class="opacitymedium small">'.$langs->trans("CreditNoteOf").' '.$block->linktoref.'</span>';
					}
				}
			} else {
				// Ref not stored
			}
			print '</div></td>';

			//$tmpobj = json_decode($block->object_data);

			// Define $totalhtamount, $totalvatamount, $totalamount for $block action code and module
			$total_ht = $total_vat = $total_ttc = 0;
			sumAmountsForUnalterableEvent($block, $refinvoicefound, $totalhtamount, $totalvatamount, $totalamount, $total_ht, $total_vat, $total_ttc);

			// Amount
			print '<td class="right nowraponall">';
			if (!in_array($block->action, array('BLOCKEDLOG_EXPORT', 'CASHCONTROL_CLOSE', 'MODULE_SET', 'MODULE_RESET'))) {
				$ingrey = !in_array($block->action, array('BILL_VALIDATE', 'PAYMENT_CUSTOMER_CREATE', 'PAYMENT_CUSTOMER_DELETE'));
				if ($ingrey) {
					print '<span class="opacitymedium">';
				}
				print price($total_ttc);
				if ($ingrey) {
					print '</span>';
				}
			}
			print '</td>';

			// Details link
			print '<td class="center"><a href="#" data-blockid="'.$block->id.'" rel="show-info">'.img_picto($langs->trans('ShowDetails'), 'note', 'class="size15x"').'</span></td>';

			// Fingerprint
			print '<td class="nowraponall">';
			// Note: the previous line id is not necessarily id-1, so in texttoshow we say "on previous line" without giving id to avoid a search/fetch to get previous id.
			$texttoshow = $langs->trans("Fingerprint").' - '.$langs->trans("SavedOnLine").' =<br>'.$block->signature;
			$texttoshow .= '<br><br>'.$langs->trans("Fingerprint").' - Recalculated hash_hmac(\'sha256\', '.strtolower($langs->trans("PreviousHash").' on previous line').' + data, secret key) =<br>'.$checkdetail[$block->id]['calculatedsignature'];
			$texttoshow .= '<br><span class="opacitymedium">'.$langs->trans("PreviousHash").'='.$checkdetail[$block->id]['previoushash'].'</span>';
			$texttoshow .= '<br><span class="opacitymedium">'.$langs->trans("SecretKey").'=Not available from interface</span>';
			//$texttoshow .= '<br>keyforsignature='.$checkdetail[$block->id]['keyforsignature'];
			print $form->textwithpicto(dol_trunc($block->signature, 8), $texttoshow, 1, 'help', '', 0, 2, 'fingerprint'.$block->id);
			print '</td>';

			// Status
			print '<td class="center">';
			if (!$checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror)) {	// If error
				if ($checkresult[$block->id]) {
					print '<span class="badge badge-status4 badge-status" title="'.$langs->trans('OkCheckFingerprintValidityButChainIsKo').'">OK</span>';
				} else {
					print '<span class="badge badge-status8 badge-status" title="'.$langs->trans('KoCheckFingerprintValidity').'">KO</span>';
				}
			} else {
				print '<span class="badge badge-status4 badge-status" title="'.$langs->trans('OkCheckFingerprintValidity').'">OK</span>';
			}

			// Note
			if (!$checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror)) {	// If error
				if ($checkresult[$block->id]) {
					if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {
						print $form->textwithpicto('', $langs->trans('OkCheckFingerprintValidityButChainIsKo'));
					}
				}
			}

			/*
			if (getDolGlobalString('BLOCKEDLOG_USE_REMOTE_AUTHORITY') && getDolGlobalString('BLOCKEDLOG_AUTHORITY_URL')) {
				print ' '.($block->certified ? img_picto($langs->trans('AddedByAuthority'), 'info') : img_picto($langs->trans('NotAddedByAuthorityYet'), 'info_black'));
			}
			*/
			print '</td>';

			// Link to debug information object
			if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
				print '<td class="tdoverflowmax150"'.(preg_match('/<a/', $object_link) ? '' : 'title="'.dol_escape_htmltag(dol_string_nohtmltag($object_link.($object_link_title ? ' - '.$object_link_title : ''))).'"').'>';
				print '<!-- object_link -->';	// $object_link can be a '<a href' link or a text
				print $object_link;
				print '</td>';
			}

			// Action column
			if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="liste_titre">';
				print '</td>';
			}

			print '</tr>';
		}
	}

	// Show total line
	if ($nbshown == 0) {
		$colspan = 11;
		if (getDolGlobalString('MAIN_FEATURES_LEVEL') > 0) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
			$colspan++;
		}
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	} else {
		ksort($totalamount);
		foreach ($totalamount as $key => $totalamountperref) {
			if ($key == 'BILL_VALIDATE' || $key == 'PAYMENT_CUSTOMER') {
				// Total
				print '<tr class="liste_total totalblockedlog">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td>';
					print '</td>';
				}

				// ID
				print '<td colspan="4">';
				print dolPrintHTML($langs->trans("TotalForAction").' '.$langs->trans('log'.$key));
				if ($key == 'BILL_VALIDATE') {
					print ' <span class="opacitylow">('.$langs->trans("Turnover").')</span>';
				} elseif ($key == 'PAYMENT_CUSTOMER') {
					print ' <span class="opacitylow">('.$langs->trans("TurnoverCollected").')</span>';
				}
				print '</td>';

				// Action
				print '<td></td>';

				// Amount (HT)
				print '<td class="right nowraponall" colspan="3">';
				$totalhttoshow = 0;
				foreach ($totalhtamount[$key] as $value) {	// Loop on each module
					$totalhttoshow += $value;
				}
				$totalvattoshow = 0;
				foreach ($totalvatamount[$key] as $value) {
					$totalvattoshow += $value;
				}
				$totaltoshow = 0;
				foreach ($totalamountperref as $value) {
					$totaltoshow += $value;
				}

				if ($key == 'BILL_VALIDATE') {
					print price($totalhttoshow);
					print ' '.$langs->trans("HT");

					print ' - ';

					print price($totalvattoshow);
					print ' '.$langs->trans("VAT");

					print ' - ';
				}

				print price($totaltoshow);
				if ($key == 'BILL_VALIDATE') {
					print ' '.$langs->trans("TTC");
				}
				print '</td>';

				// Details link
				print '<td class="center"></td>';

				// Fingerprint
				print '<td class="nowraponall">';
				print '</td>';

				// Status
				print '<td class="center">';
				print '</td>';

				// Link to debug information object
				if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
					print '<td class="tdoverflowmax150"'.(preg_match('/<a/', $object_link) ? '' : 'title="'.dol_escape_htmltag(dol_string_nohtmltag($object_link.($object_link_title ? ' - '.$object_link_title : ''))).'"').'>';
					print '</td>';
				}

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="liste_titre">';
					print '</td>';
				}

				print '</tr>';
			}
		}


		// TODO Show the lifetime payment only if we click on a link.
		$afilterexists = ($search_id || ($search_fk_user > 0) || $search_ref || $search_amount || $search_signature || !empty($search_module_source) || $search_pos_source);

		if (! $afilterexists) {
			// Get lifetime amount of all invoices validated and payments created/deleted.
			// We do not use $totalamountalllines because it is only for the period, but we want lifetime amount since the first record to now.

			$totalamountlifetime = array('BILL_VALIDATE' => 0, 'PAYMENT_CUSTOMER_CREATE' => 0, 'PAYMENT_CUSTOMER_DELETE' => 0);
			$totalhtamountlifetime = array('BILL_VALIDATE' => 0, 'PAYMENT_CUSTOMER_CREATE' => 0, 'PAYMENT_CUSTOMER_DELETE' => 0);
			$foundoldformat = 0;
			$firstrecorddate = 0;
			if (empty($search_end) || $search_end == -1) {
				$search_end = dol_now();
			}
			include_once DOL_DOCUMENT_ROOT.'/blockedlog/admin/lifetimeamount.inc.php';

			if (empty($search_code) || in_array('BILL_VALIDATE', $search_code)) {
				// Total
				print '<tr class="liste_total totalblockedlog">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td></td>';
				}

				// ID
				print '<td colspan="4">';
				print dolPrintHTML($langs->trans("TotalForAction").' '.$langs->trans('logBILL_VALIDATE'));
				print ' <span class="opacitylow">('.$langs->trans("Turnover").')';
				print '<br>'.$langs->trans("LifetimeAmountShort").': '.dol_print_date($firstrecorddate, 'dayhour', 'tzuserrel');
				if ($search_end && $search_end != -1) {
					print ' - '.dol_print_date($search_end, 'dayhoursec', 'tzuserrel');
				} else {
					print ' - '.$langs->trans("Now");
				}
				print '</span>';
				print '</td>';

				// Action
				print '<td></td>';

				// Amount (HT)
				print '<td class="right nowraponall" colspan="3">';
				print $totalhtamountlifetime['BILL_VALIDATE'].' '.$langs->trans("HT")." - ".($foundoldformat ? '' : ($totalamountlifetime['BILL_VALIDATE'] - $totalhtamountlifetime['BILL_VALIDATE']).' '.$langs->transnoentitiesnoconv("VAT")).' - '.$totalamountlifetime['BILL_VALIDATE'].' '.$langs->trans("TTC");
				print '</td>';

				// Details link
				print '<td class="center"></td>';

				// Fingerprint
				print '<td class="nowraponall"></td>';

				// Status
				print '<td class="center"></td>';

				// Link to debug information object
				if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
					print '<td class="tdoverflowmax150"'.(preg_match('/<a/', $object_link) ? '' : 'title="'.dol_escape_htmltag(dol_string_nohtmltag($object_link.($object_link_title ? ' - '.$object_link_title : ''))).'"').'>';
					print '</td>';
				}

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="liste_titre"></td>';
				}

				print '</tr>';
			}
			if (empty($search_code) || in_array('PAYMENT_CUSTOMER_CREATE', $search_code) || in_array('PAYMENT_CUSTOMER_DELETE', $search_code)) {
				// Total
				print '<tr class="liste_total totalblockedlog">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td></td>';
				}

				// ID
				print '<td colspan="4">';
				print dolPrintHTML($langs->trans("TotalForAction").' '.$langs->trans('logPAYMENT_CUSTOMER'));
				print ' <span class="opacitylow">('.$langs->trans("TurnoverCollected").')';
				print '<br>'.$langs->trans("LifetimeAmountShort").': '.dol_print_date($firstrecorddate, 'dayhour', 'tzuserrel');
				if ($search_end && $search_end != -1) {
					print ' - '.dol_print_date($search_end, 'dayhoursec', 'tzuserrel');
				} else {
					print ' - '.$langs->trans("Now");
				}
				print '</span>';
				print '</td>';

				// Action
				print '<td></td>';

				// Amount (HT)
				print '<td class="right nowraponall" colspan="3">';
				print ($totalamountlifetime['PAYMENT_CUSTOMER_CREATE'] + $totalamountlifetime['PAYMENT_CUSTOMER_DELETE']);
				print '</td>';

				// Details link
				print '<td class="center"></td>';

				// Fingerprint
				print '<td class="nowraponall"></td>';

				// Status
				print '<td class="center"></td>';

				// Link to debug information object
				if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
					print '<td class="tdoverflowmax150"'.(preg_match('/<a/', $object_link) ? '' : 'title="'.dol_escape_htmltag(dol_string_nohtmltag($object_link.($object_link_title ? ' - '.$object_link_title : ''))).'"').'>';
					print '</td>';
				}

				// Action column
				if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="liste_titre"></td>';
				}

				print '</tr>';
			}
		}
	}
}

print '</table>';

print '</div>';

print '</form>';

// Javascript to manage the showinfo popup
print '<script type="text/javascript">

jQuery(document).ready(function () {
	jQuery("#dialogforpopup").dialog({
		closeOnEscape: true,
		classes: { "ui-dialog": "highlight" },
		maxHeight: window.innerHeight-60,
		height: window.innerHeight-60,
		width: '.($conf->browser->layout == 'phone' ? 400 : 700).',
		modal: true,
		autoOpen: false
	}).css("z-index: 5000");

	$("a[rel=show-info]").click(function() {
	    console.log("We click on tooltip a[rel=show-info], we open popup and get content using an ajax call");

		var fk_block = $(this).attr("data-blockid");

		$.ajax({
			method: "GET",
			data: { token: \''.currentToken().'\' },
			url: "'.DOL_URL_ROOT.'/blockedlog/ajax/block-info.php?id="+fk_block,
			dataType: "html"
		}).done(function(data) {
			jQuery("#dialogforpopup").html(data);
		});

		var mydialog = jQuery("#dialogforpopup");
		mydialog.dialog({autoOpen: false, modal: true, height: (window.innerHeight - 150), width: \'80%\', title: \''.dol_escape_js($langs->transnoentitiesnoconv("UnlaterableDataOfEvent")).'\',});
		mydialog.dialog("open");
		return false;
	});
})
</script>'."\n";


/*
if (getDolGlobalString('BLOCKEDLOG_USE_REMOTE_AUTHORITY') && getDolGlobalString('BLOCKEDLOG_AUTHORITY_URL')) {
	?>
		<script type="text/javascript">

			$.ajax({
				method: "GET",
				data: { token: '<?php echo currentToken() ?>' },
				url: '<?php echo DOL_URL_ROOT.'/blockedlog/ajax/check_signature.php' ?>',
				dataType: 'html'
			}).done(function(data) {
				if(data == 'hashisok') {
					$('#blockchainstatus').html('<?php echo $langs->trans('AuthorityReconizeFingerprintConformity').' '.img_picto($langs->trans('SignatureOK'), 'on') ?>');
				}
				else{
					$('#blockchainstatus').html('<?php echo $langs->trans('AuthorityDidntReconizeFingerprintConformity').' '.img_picto($langs->trans('SignatureKO'), 'off') ?>');
				}

			});

		</script>
	<?php
}
*/

print dol_get_fiche_end();

print '<br><br>';

// End of page
llxFooter();
$db->close();
