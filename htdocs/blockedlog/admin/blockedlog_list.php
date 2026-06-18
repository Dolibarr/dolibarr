<?php
/* Copyright (C) 2017		ATM Consulting				<contact@atm-consulting.fr>
 * Copyright (C) 2017-2018	Laurent Destailleur			<eldy@destailleur.fr>
 * Copyright (C) 2018-2026  Frédéric France				<frederic.france@free.fr>
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
$langs->loadLangs(array('admin', 'banks', 'bills', 'blockedlog', 'cashdesk', 'other'));

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
$search_module_source = GETPOSTISSET('search_module_source') ? GETPOST('search_module_source', 'array:alpha') : (isModEnabled('takepos') ? array('takepos') : array());
$search_pos_source = GETPOST('search_pos_source');
$search_ref = GETPOST('search_ref', 'alpha');
$search_type_code = GETPOST('search_type_code', 'aZ09');
$search_amount = GETPOST('search_amount', 'alpha');
$search_signature = GETPOST('search_signature', 'alpha');
$withtab = GETPOSTISSET('withtab') ? GETPOSTINT('withtab') : 1;

if (($search_start == -1 || empty($search_start)) && !GETPOSTISSET('search_startmonth') && !GETPOSTISSET('begin')) {
	$search_start = dol_time_plus_duree(dol_now(), -1, 'w');
	$tmparray = dol_getdate($search_start);
	$search_startday = $tmparray['mday'];
	$search_startmonth = $tmparray['mon'];
	$search_startyear = $tmparray['year'];
}

$includebeforev2 = GETPOSTINT('includebeforev2');

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

$error = 0;


/*
 * Actions
 */

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$search_id = '';
	$search_fk_user = '';
	$search_start = dol_time_plus_duree(dol_now(), -1, 'w');
	$search_end = -1;
	$search_code = array();
	$search_module_source = isModEnabled('takepos') ? array('takepos') : array();
	$search_pos_source = '';
	$search_ref = '';
	$search_type_code = '';			// Type of payment
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

if ($withtab) {
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
if ($withtab) {
	$linkback = '<a href="'.dolBuildUrl($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php', ['restore_lastsearch_values' => 1]).'">'.img_picto($langs->trans("BackToModuleList"), 'back', 'class="pictofixedwidth"').'<span class="hideonsmartphone">'.$langs->trans("BackToModuleList").'</span></a>';
}

$morehtmlcenter = '';
$texttop = '';

$registrationnumber = getHashUniqueIdOfRegistration();
if (!userIsTaxAuditor()) { // @phpstan-ignore-line as it is already checked before
	$texttop = '<small class="opacitymedium">'.$langs->trans("RegistrationNumber").':</small> <small>'.dol_trunc($registrationnumber, 10).'</small>';
	if (!isRegistrationDataSavedAndPushed()) {
		$texttop = '';
	}
}

print load_fiche_titre($title.'<br>'.$texttop, $linkback, 'blockedlog', 0, '', '', $morehtmlcenter);

$head = blockedlogadmin_prepare_head($withtab);

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

$nbrecorddone = $block_static->countRecord();
$mindisksize = 50;	// Gb
$maxtranspermonth = 10000;
$nbrecordallowed = $mindisksize * 1024 * 1024 / 40 - $nbrecorddone;
$nbmonthallowed = $nbrecordallowed / $maxtranspermonth;

$htmltext = '';
$htmltext .= $langs->trans("UnalterableLogTool2", $langs->transnoentitiesnoconv("Archives"))."<br>";
$htmltext .= '<span class="small">'.$langs->trans("UnalterableLogTool2MaxUsage", $nbrecorddone, $mindisksize, $nbrecordallowed)."</span><br>";

$htmltext .= '<span class="small">'.$langs->trans("UnalterableLogTool3")."</span><br>";
if ($mysoc->country_code == 'FR') {
	$htmltext .= '<br><span class="small">'.$langs->trans("UnalterableLogTool1FR", $langs->transnoentitiesnoconv("Archives")).'</span><br>';
} else {
	$htmltext .= '<span class="small">'.$langs->trans("UnalterableLogTool2b", $langs->transnoentitiesnoconv("Archives"))."</span><br>";
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
if ($search_type_code) {
	$param .= '&search_type_code='.urlencode($search_type_code);
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
if ($withtab) {
	$param .= '&withtab='.((int) $withtab);
}

// Clear memory cache of the obfuscation key
if (GETPOST('clearcache')) {
	unset($_SESSION['obfuscationkey_'.((int) $conf->entity)]);
	unset($conf->cache['obfuscationkey_'.((int) $conf->entity)]);
}

// Get the remoteobfuscation key
// Show an error to ask to retry later if we can't get it because it means we can't decode the HMAC KEY later so we can't validate record.
$remoteobfuscationkey = '';
try {
	$remoteobfuscationkey = $block_static->getObfuscationKey();
	// Note: To emulate a pb in getting the obfuscation key, there is some code to uncomment into the method
} catch (Exception $e) {
	$error++;

	print '<div class="error mess1">';
	print $e->getMessage();
	print '<br>';
	print '<a class="" href="'.$_SERVER["PHP_SELF"].'?clearcache=1">'.$langs->trans("Retry").'</a>';
	print '</div>';
}

// Get the encoded HMAC key.
$hmac_encoded_secret_key = $block_static->getEncodedHMACSecretKey();	// Can be old 'dolcrypt:...' if migration not yet complete but should be 'dolobfuscationv1...'
if (empty($hmac_encoded_secret_key)) {
	// This is no more the case since Dolibarr v23 and Blockedlog v2+
	print '<div class="error mess2">';
	print 'Error: BLOCKEDLOG_HMAC_KEY was not found. It should have been initialized to a value "BLOCKEDLOG_HMAC_...." during initialization of module BlockedLog or during migration from a very old version.';
	print '</div>';
}

// Here we have the obfuscated value of BLOCKEDLOG_HMAC_KEY in $hmac_encoded_secret_key. We need to unobfuscate it.
$hmac_secret_key = '';
if (!$error) {
	try {
		$hmac_secret_key = $block_static->getClearHMACSecretKey($hmac_encoded_secret_key);		// Note: On network trouble, an Exception is thrown to the caller
	} catch (Exception $e) {
		print '<div class="error mess3">';
		print $e->getMessage();
		print '<br>';
		print '<a class="" href="'.$_SERVER["PHP_SELF"].'?clearcache=1">'.$langs->trans("Retry").'</a>';
		print '</div>';
	}
}

print '<form method="POST" id="searchFormList" action="'.dolBuildUrl($_SERVER["PHP_SELF"]).'" spellcheck="false">';

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
print '<input type="hidden" name="withtab" value="'.$withtab.'">';

print '<div class="div-table-responsive">'; // You can use div-table-responsive-no-min if you don't need reserved height for your table
print '<table class="noborder centpercent liste">';

// Line of filters
print '<tr class="liste_titre_filter">';

// Action column
if ($conf->main_checkbox_left_column) {
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
//print $form->multiselectarray('search_module_source', $block_static->trackedmodules, $search_module_source, 0, 0, 'minwidth75 maxwidth200', 1);
print '<input type="text" class="maxwidth100" name="search_module_source" list="search_module_sources" value="'.dol_escape_htmltag($search_module_source[0]).'">';
if (isModEnabled('takepos')) {
	print '<datalist id="search_module_sources">
	    <option value="takepos">
	</datalist>';
}
print '</td>';

// POS source
print '<td class="liste_titre">';
print '<input type="text" class="maxwidth50" name="search_pos_source" value="'.dol_escape_htmltag($search_pos_source).'">';
print '</td>';

// Actions code

$actioncodetoshowincombo = array();
// Merge the action PAYMENT_CUSTOMER_CREATE and PAYMENT_CUSTOMER_DELETE into PAYMENT_CUSTOMER
foreach ($block_static->trackedevents as $key => $value) {
	if ($key === 'PAYMENT_CUSTOMER_DELETE') {
		$actioncodetoshowincombo['PAYMENT_CUSTOMER'] = array('id' => 'PAYMENT_CUSTOMER', 'label' => 'logPAYMENT_CUSTOMER', 'labelhtml' => img_picto('', 'bill', 'class="pictofixedwidth").').$langs->trans('logPAYMENT_CUSTOMER'));
		unset($actioncodetoshowincombo['PAYMENT_CUSTOMER_CREATE']);
		unset($actioncodetoshowincombo['PAYMENT_CUSTOMER_DELETE']);
	} else {
		$actioncodetoshowincombo[$key] = $value;
	}
}
$actioncodetoshowincombo['PAYMENT_CUSTOMER'] = array('id' => 'PAYMENT_CUSTOMER', 'label' => 'logPAYMENT_CUSTOMER', 'labelhtml' => img_picto('', 'bill', 'class="pictofixedwidth").').$langs->trans('logPAYMENT_CUSTOMER'));

print '<td class="liste_titre">';
print $form->multiselectarray('search_code', $actioncodetoshowincombo, $search_code, 0, 0, 'maxwidth200', 1);
print '</td>';

// Ref
print '<td class="liste_titre"><input type="text" class="maxwidth100" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';

// Payment mode
//print '<td class="liste_titre"><input type="text" class="maxwidth100" name="search_type_code" value="'.dol_escape_htmltag($search_type_code).'"></td>';

// Amount
print '<td class="liste_titre right"><input type="text" class="maxwidth50" name="search_amount" value="'.dol_escape_htmltag($search_amount).'"></td>';

// Full data
print '<td class="liste_titre"></td>';

// Fingerprint
print '<td class="liste_titre"><input type="text" class="maxwidth50" name="search_signature" value="'.dol_escape_htmltag($search_signature).'"></td>';

// Status
print '<td class="liste_titre center minwidth75imp parentonrightofpage">';
$array = array("1" => $langs->trans("OnlyNonValid").' (KO)');
print $form->selectarray('search_showonlyerrors', $array, $search_showonlyerrors, 1, 0, 0, '', 1, 0, 0, 'ASC', 'search_status width100 onrightofpage', 1);
print '</td>';

// Link to debug information object
if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
	print '<td class="liste_titre"></td>';
	print '<td class="liste_titre"></td>';
}

// Action column
if (!$conf->main_checkbox_left_column) {
	print '<td class="liste_titre center">';
	$searchpicto = $form->showFilterButtons();
	print $searchpicto;
	print '</td>';
}

print '</tr>';


print '<tr class="liste_titre">';
// Action column
if ($conf->main_checkbox_left_column) {
	print getTitleFieldOfList('<span id="blockchainstatus"></span>', 0, $_SERVER["PHP_SELF"], '', '', $param, 'class="center"', $sortfield, $sortorder, '')."\n";
}
print getTitleFieldOfList($langs->trans('#'), 0, $_SERVER["PHP_SELF"], 'rowid', '', $param, '', $sortfield, $sortorder, 'minwidth50 ')."\n";
print getTitleFieldOfList($langs->trans('Date'), 0, $_SERVER["PHP_SELF"], 'date_creation', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Author'), 0, $_SERVER["PHP_SELF"], 'user_fullname', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('POS'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Terminal'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Action'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Ref'), 0, $_SERVER["PHP_SELF"], 'ref_object', '', $param, '', $sortfield, $sortorder, '')."\n";
//print getTitleFieldOfList($langs->trans('PaymentMode'), 0, $_SERVER["PHP_SELF"], 'type_code', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Amount'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ', 0, $langs->trans("TotalTTCIfInvoiceSeeCompleteDataForDetail").'<br>'.$langs->trans("AmountInCurrency", getDolCurrency()))."\n";
print getTitleFieldOfList($langs->trans('DataOfArchivedEvent'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ', 0, $langs->trans('DataOfArchivedEventHelp'), 1)."\n";
print getTitleFieldOfList($langs->trans('Fingerprint'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($form->textwithpicto($langs->trans('Status'), $langs->trans('DataOfArchivedEventHelp2')), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ')."\n";
if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
	print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
	print getTitleFieldOfList('', 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
}
// Action column
if (!$conf->main_checkbox_left_column) {
	print getTitleFieldOfList('<span id="blockchainstatus"></span>', 0, $_SERVER["PHP_SELF"], '', '', $param, 'class="center"', $sortfield, $sortorder, '')."\n";
}
print '</tr>';

$checkresult = array();
$checkdetail = array();
$checkerror = array();
$loweridinerror = 0;		// The lower rowid we found an anomaly (for debug or analysis purposes only)

// This is the algorithm that optimize the memory (note: it will not report errors that are outside the filter range, but we don't need them)
if (is_array($blocks)) {
	foreach ($blocks as &$block) {
		// Enable this log to get information used to recalculate the signature
		//var_dump($block->id.' '.$block->signature, $block->object_data);

		$tmpcheckresult = $block->checkSignature('', 1); // Note: this make a sql request at each call, we can't avoid this as the sorting order and filter is various

		$checksignature = $tmpcheckresult['checkresult'];

		$checkresult[$block->id] = $checksignature; // false if error
		$checkdetail[$block->id] = $tmpcheckresult;

		if (!empty($tmpcheckresult['error'])) {
			$checkerror[$block->id] = $tmpcheckresult['error'];
		}
		if (!empty($block->note)) {
			$checkresult[$block->id] = false;
			//$checkerror[$block->id] = $block->note;
		}

		if (!$checksignature) {
			if (empty($loweridinerror)) {
				$loweridinerror = $block->id;
			} else {
				$loweridinerror = min($loweridinerror, $block->id);
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

	$colspan = 12;
	if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {
		$colspan++;
		$colspan++;
	}

	// Get the last record of the chain (may be used later).
	$lastrecord = $block_static->getLastRecord();

	// Check that there was no deletion on the end of chain.
	// Note: We can find a similar code into the blockedlog_archive
	$lockfile = $block_static->getEndOfChainFlagFile();
	$lockline = '';

	if (!file_exists($lockfile)) {
		$error++;

		print '<tr><td class="center" colspan="'.$colspan.'">';
		if ($mysoc->country_code == 'FR') {
			print '<span class="error">'.$langs->trans("ErrorEndOfChainFlagWasRemoved").'</span>';
		} else {
			print '<span class="warning">'.$langs->trans("WarningNoProtectionOnEndOfChain").'</span>';
		}
		print '</td></tr>';
	} else {
		$lockline = trim(file_get_contents($lockfile));
	}

	if (! $error) {
		$headstring = '';
		if (preg_match('/^dolcrypt/', $lockline)) {
			$headstring = dolDecrypt($lockline, '', 'BLOCKEDLOGHEAD');
		} elseif (preg_match('/^dolobfuscation/', $lockline)) {
			try {
				$remoteobfuscationkey = $block_static->getObfuscationKey();
				if (empty($remoteobfuscationkey)) {
					throw new Exception('Remote obfuscation key is empty');
				}
			} catch (Exception $e) {
				$error++;

				print '<tr><td class="center" colspan="'.$colspan.'">';
				$url_for_ping = getDolGlobalString('MAIN_URL_FOR_PING', "https://ping.dolibarr.org/");
				print '<span class="warning">'.$langs->trans("FailedToGetRemoteObfuscationKeyReTryLater", $url_for_ping).'</span>';
				print ' ';
				print '<span class="warning">'.$langs->trans("CantValidateEndOfChain").'</span>';
				print '</td></tr>';
			}
			$headstring = dolDecrypt($lockline, $remoteobfuscationkey, 'BLOCKEDLOGHEAD');
		}

		$reg = array();
		if (preg_match('/^BLOCKEDLOGHEAD (\d+) ([^\s]+) ([a-zA-Z0-9\-]+)/', $headstring, $reg)) {	// Failed to decypt the head
			// Compare with last line
			$lastrecordid = $lastrecord['id'];
			$lastrecorddate = $lastrecord['date'];
			$lastrecordsignature = $lastrecord['signature'];

			if ($reg[1] > $lastrecordid || $reg[3] != $lastrecordsignature) {
				$error++;

				// Check that last line is the one declared into the head flag. If not, it means some record were deleted at end of chain.
				print '<tr><td class="center" colspan="'.$colspan.'">';
				print '<span class="error">'.$langs->trans("ErrorEndOfChainRecordWasRemoved", str_replace(array('T', 'Z'), ' ', dol_print_date($lastrecorddate, 'dayhourrfc', 'gmt')), str_replace(array('T', 'Z'), ' ', $reg[2])).'</span>';
				print '</td></tr>';
			}
		} else {
			$error++;

			print '<tr><td class="center" colspan="'.$colspan.'">';
			print '<span class="error">'.$langs->trans("FailedToDecodeTheHeadFlagEndOfChainIsNotReliable").'</span>';
			print '</td></tr>';
		}
	}

	// Now loop on each line to show them
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
			if ($conf->main_checkbox_left_column) {
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

			// Module Source
			$labelofmodulesource = $block->module_source;
			print '<td class="tdoverflowmax250" title="'.dolPrintHTMLForAttribute($labelofmodulesource).'">'.dolPrintHTML($labelofmodulesource).'</td>';

			// Terminal POS
			print '<td>'.dolPrintHTML($block->pos_source).'</td>';

			// Action
			$labelofaction = $langs->transnoentitiesnoconv('log'.$block->action);
			print '<td class="" title="'.dolPrintHTMLForAttribute($labelofaction).'">';
			print '<div class="twolinesmax-normallineheight minwidth200onall small">';
			print dolPrintHTML($labelofaction);
			print '</div>';
			print '</td>';

			// Define $totalhtamount, $totalvatamount, $totalamount for $block action code and module
			$total_ht = $total_vat = $total_ttc = 0;
			sumAmountsForUnalterableEvent($block, $refinvoicefound, $totalhtamount, $totalvatamount, $totalamount, $total_ht, $total_vat, $total_ttc);

			// Ref
			print '<td class="nowraponall"><div class="smallheight" title="'.dolPrintHTMLForAttribute(price($total_ttc)).'">';
			if (!empty($block->ref_object)) {
				print dolPrintHTML($block->ref_object);
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

			// Payment mode
			//print '<td>'.dolPrintHTML($block->type_code).'</td>';

			// Amount
			print '<td class="right nowraponall"><span class="amount">';
			if (!in_array($block->action, array('BLOCKEDLOG_EXPORT', 'CASHCONTROL_CLOSE', 'MODULE_SET', 'MODULE_RESET'))) {
				$showamount = in_array($block->action, array('BILL_VALIDATE', 'PAYMENT_CUSTOMER_CREATE', 'PAYMENT_CUSTOMER_DELETE'));
				if ($showamount) {
					print price($total_ttc);
				}
			}
			print '</span></td>';

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
					//print '<span class="badge badge-status4 badge-status" title="'.dolPrintHTMLForAttribute($langs->trans('OkCheckFingerprintValidityButChainIsKo')).'">'.$langs->trans("StatusValid").'</span>';
					print '<span class="badge badge-status4 badge-status" title="'.dolPrintHTMLForAttribute($langs->trans('OkCheckFingerprintValidity')).'">'.$langs->trans("StatusValid").'</span>';
				} elseif ($block->action == 'MODULE_RESET') {
					// Old action code on old version.
					print '<span class="badge badge-status8 badge-status" title="'.dolPrintHTMLForAttribute('Module has been disabled').'">OK</span>';
				} else {
					print '<span class="badge badge-status8 badge-status" title="';
					if (!empty($checkerror[$block->id])) {
						print dolPrintHTMLForAttribute($checkerror[$block->id])."\n";
					}
					$alt = $langs->trans('KoCheckFingerprintValidity');
					if ($block->note) {
						$notetoshow = $block->note;
						$notetoshow = str_replace('EndOfChainDeletionDetected', $langs->trans("EndOfChainDeletionDetected"), $notetoshow);
						$alt .= "\n".' '.$langs->trans("AddtionalInformation").': '.$notetoshow;
					}

					print dolPrintHTMLForAttribute($alt).'">KO</span>';
				}
			} else {
				print '<span class="badge badge-status4 badge-status" title="'.$langs->trans('OkCheckFingerprintValidity').'">'.$langs->trans("StatusValid").'</span>';
			}

			// Add debug information
			if (!$checkresult[$block->id] || ($loweridinerror && $block->id >= $loweridinerror)) {	// If error
				if ($checkresult[$block->id]) {
					if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {
						print $form->textwithpicto('', $langs->trans('OkCheckFingerprintValidityButChainIsKo'));
					}
				}
			}
			print '</td>';

			// Link to debug information object
			if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
				print '<td class="nowraponall">';
				print '<!-- version -->';	// $object_link can be a '<a href' link or a text
				print '<span class="small">'.$block->object_version. '<br>'.$block->object_format.'</span>';
				print '</td>';

				print '<td class="tdoverflowmax150"'.(preg_match('/<a/', $object_link) ? '' : 'title="'.dol_escape_htmltag(dol_string_nohtmltag($object_link.($object_link_title ? ' - '.$object_link_title : ''))).'"').'>';
				print '<!-- object_link -->';	// $object_link can be a '<a href' link or a text with more information
				print $object_link;
				print '</td>';
			}

			// Action column
			if (!$conf->main_checkbox_left_column) {
				print '<td class="liste_titre">';
				print '</td>';
			}

			print '</tr>';
		}
	}

	// Show total lines
	if ($nbshown == 0) {
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	} else {
		ksort($totalamount);

		$showturnover = 0;
		foreach ($totalamount as $key => $totalamountperref) {
			if ($key == 'BILL_VALIDATE' || $key == 'PAYMENT_CUSTOMER') {
				$showturnover++;
			}
		}
		//var_dump($totalamount);

		foreach ($totalamount as $key => $totalamountperref) {
			if ($showturnover) {
				// Total
				print '<tr class="liste_total totalblockedlog">';

				// Action column
				if ($conf->main_checkbox_left_column) {
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
				print '<br><span class="opacitylow">'.$langs->trans("ForPeriodAndFilters").'</span>';
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
					print '<td></td>';

					print '<td class="tdoverflowmax150"'.(preg_match('/<a/', $object_link) ? '' : 'title="'.dol_escape_htmltag(dol_string_nohtmltag($object_link.($object_link_title ? ' - '.$object_link_title : ''))).'"').'>';
					print '</td>';
				}

				// Action column
				if (!$conf->main_checkbox_left_column) {
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
			global $foundoldformat, $firstrecorddate;
			include DOL_DOCUMENT_ROOT.'/blockedlog/admin/lifetimeamount.inc.php';

			if (empty($search_code) || in_array('BILL_VALIDATE', $search_code)) {
				// Total
				print '<tr class="liste_total totalblockedlog">';

				// Action column
				if ($conf->main_checkbox_left_column) {
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
				print '</span> &nbsp; ';

				// If there is at least one record with old format
				$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."blockedlog WHERE object_format < 'V2' and action = 'BILL_VALIDATE' LIMIT 1";
				$resql = $db->query($sql);
				$obj = $db->fetch_object($resql);
				if ($obj) {
					$foundav1 = 1;
					if ($includebeforev2) {
						print ' <span class="small"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?includebeforev2=0&'.($page ? 'page='.$page.'&' : '').$param.'">'.$form->textwithpicto($langs->trans("OnlyFromV2"), $langs->trans("OnlyFromV2Help")).'</a></span>';
					} else {
						print ' <span class="small"><a class="reposition" href="'.$_SERVER["PHP_SELF"].'?includebeforev2=1&'.($page ? 'page='.$page.'&' : '').$param.'">'.$form->textwithpicto($langs->trans("IncludesAll"), $langs->trans("IncludesAllHelp")).'</a></span>';
					}
				}
				print '</td>';

				// Action
				print '<td></td>';

				// Amount (HT)
				print '<td class="right nowraponall" colspan="3">';
				print ($foundoldformat ? '' : price($totalhtamountlifetime['BILL_VALIDATE']).' '.$langs->trans("HT")).($foundoldformat ? '' : " - ".price($totalamountlifetime['BILL_VALIDATE'] - $totalhtamountlifetime['BILL_VALIDATE']).' '.$langs->transnoentitiesnoconv("VAT")).($foundoldformat ? '' : " - ").price($totalamountlifetime['BILL_VALIDATE']).' '.$langs->trans("TTC");
				print '</td>';

				// Details link
				print '<td class="center"></td>';

				// Fingerprint
				print '<td class="nowraponall"></td>';

				// Status
				print '<td class="center"></td>';

				// Link to debug information object
				if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
					print '<td>';
					print '</td>';

					print '<td class="tdoverflowmax150"'.(preg_match('/<a/', $object_link) ? '' : 'title="'.dol_escape_htmltag(dol_string_nohtmltag($object_link.($object_link_title ? ' - '.$object_link_title : ''))).'"').'>';
					print '</td>';
				}

				// Action column
				if (!$conf->main_checkbox_left_column) {
					print '<td class="liste_titre"></td>';
				}

				print '</tr>';
			}
			if (empty($search_code)
				|| in_array('PAYMENT_CUSTOMER', $search_code)				// Filter for both PAYMENT_CUSTOMER_CREATE and PAYMENT_CUSTOMER_DELETE
				|| in_array('PAYMENT_CUSTOMER_CREATE', $search_code)
				|| in_array('PAYMENT_CUSTOMER_DELETE', $search_code)) {
				// Total
				print '<tr class="liste_total totalblockedlog">';

				// Action column
				if ($conf->main_checkbox_left_column) {
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
				print price($totalamountlifetime['PAYMENT_CUSTOMER_CREATE'] + $totalamountlifetime['PAYMENT_CUSTOMER_DELETE']);
				print '</td>';

				// Details link
				print '<td class="center"></td>';

				// Fingerprint
				print '<td class="nowraponall"></td>';

				// Status
				print '<td class="center"></td>';

				// Link to debug information object
				if (getDolGlobalString("BLOCKEDLOG_DEBUG")) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
					print '<td>';
					print '</td>';

					print '<td class="tdoverflowmax150"'.(preg_match('/<a/', $object_link) ? '' : 'title="'.dol_escape_htmltag(dol_string_nohtmltag($object_link.($object_link_title ? ' - '.$object_link_title : ''))).'"').'>';
					print '</td>';
				}

				// Action column
				if (!$conf->main_checkbox_left_column) {
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
