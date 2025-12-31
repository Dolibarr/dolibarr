<?php
/* Copyright (C) 2017		ATM Consulting				<contact@atm-consulting.fr>
 * Copyright (C) 2017-2018	Laurent Destailleur			<eldy@destailleur.fr>
 * Copyright (C) 2018-2025  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
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

if (GETPOST('downloadcsv', 'alpha')) {
	$error = 0;

	$previoushash = '';
	$firstid = '';

	if (! (GETPOSTINT('yeartoexport') > 0)) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Year")), null, "errors");
		$error++;
	} else {
		// Get the ID of the first line qualified
		$sql = "SELECT rowid,date_creation,tms,user_fullname,action,amounts,element,fk_object,date_object,ref_object,signature,fk_user,object_data";
		$sql .= " FROM ".MAIN_DB_PREFIX."blockedlog";
		$sql .= " WHERE entity = ".((int) $conf->entity);
		if (GETPOSTINT('monthtoexport') > 0 || GETPOSTINT('yeartoexport') > 0) {
			$dates = dol_get_first_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') ? GETPOSTINT('monthtoexport') : 1);
			$datee = dol_get_last_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') ? GETPOSTINT('monthtoexport') : 12);
			$sql .= " AND date_creation BETWEEN '".$db->idate($dates)."' AND '".$db->idate($datee)."'";
		}
		$sql .= " ORDER BY rowid ASC"; // Required so we get the first one
		$sql .= $db->plimit(1);

		$res = $db->query($sql);
		if ($res) {
			// Make the first fetch to get first line
			$obj = $db->fetch_object($res);
			if ($obj) {
				$firstid = $obj->rowid;
				$previoushash = $block_static->getPreviousHash(0, $firstid);
			} else {	// If not data found for filter, we do not need previoushash neither firstid
				$firstid = '';
				$previoushash = 'nodata';
			}
		} else {
			$error++;
			setEventMessages($db->lasterror, null, 'errors');
		}
	}

	if (! $error) {
		// We record the export as a new line into the unalterable logs
		require_once DOL_DOCUMENT_ROOT.'/blockedlog/class/blockedlog.class.php';
		$b = new BlockedLog($db);

		$object = new stdClass();
		$object->id = 0;
		$object->element = 'module';
		$object->ref = 'systemevent';
		$object->entity = $conf->entity;
		$object->date = dol_now();

		$object->label = 'Export unalterable logs - Period: year='.GETPOSTINT('yeartoexport').(GETPOSTINT('monthtoexport') ? ' month='.GETPOSTINT('monthtoexport') : '');

		$action = 'BLOCKEDLOG_EXPORT';
		$result = $b->setObjectData($object, $action, 0, $user);
		//var_dump($b); exit;

		if ($result < 0) {
			setEventMessages('Failed to insert the export int the unalterable log', null, 'errors');
			$error++;
		}

		$res = $b->create($user);

		if ($res < 0) {
			setEventMessages('Failed to insert the export int the unalterable log', null, 'errors');
			$error++;
		}
	}

	if (!$error) {
		// Now restart request with all data, si without the limit(1) in sql request
		$sql = "SELECT rowid, date_creation, tms, user_fullname, action, amounts, element, fk_object, date_object, ref_object,";
		$sql .= " signature, fk_user, object_data, object_version, object_format, debuginfo";
		$sql .= " FROM ".MAIN_DB_PREFIX."blockedlog";
		$sql .= " WHERE entity = ".((int) $conf->entity);
		if (GETPOSTINT('monthtoexport') > 0 || GETPOSTINT('yeartoexport') > 0) {
			$dates = dol_get_first_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') ? GETPOSTINT('monthtoexport') : 1);
			$datee = dol_get_last_day(GETPOSTINT('yeartoexport'), GETPOSTINT('monthtoexport') ? GETPOSTINT('monthtoexport') : 12);
			$sql .= " AND date_creation BETWEEN '".$db->idate($dates)."' AND '".$db->idate($datee)."'";
		}
		$sql .= " ORDER BY rowid ASC"; // Required so later we can use the parameter $previoushash of checkSignature()

		$resql = $db->query($sql);
		if ($resql) {
			$nameofdownoadedfile = "unalterable-log-archive-".$dolibarr_main_db_name."-".(GETPOSTINT('yeartoexport') > 0 ? GETPOSTINT('yeartoexport').(GETPOSTINT('monthtoexport') > 0 ? sprintf("%02d", GETPOSTINT('monthtoexport')) : '').'-' : '').dol_print_date(dol_now(), 'dayhourlog', 'gmt').'UTC-DONOTMODIFY';

			$tmpfile = $conf->admin->dir_temp.'/unalterable-log-archive-tmp-'.$user->id.'.csv';

			$fh = fopen($tmpfile, 'w');

			// Print line with title
			fwrite($fh, $langs->transnoentities('Id')
				.';'.$langs->transnoentities('Date')
				.';'.$langs->transnoentities('User')
				.';'.$langs->transnoentities('Action')
				.';'.$langs->transnoentities('Element')
				.';'.$langs->transnoentities('Amounts')
				.';'.$langs->transnoentities('ObjectId')
				.';'.$langs->transnoentities('Date')
				.';'.$langs->transnoentities('Ref')
				.';'.$langs->transnoentities('Fingerprint')
				.';'.$langs->transnoentities('Status')
				.';'.$langs->transnoentities('Note')
				.';'.$langs->transnoentities('Version')
				.';'.$langs->transnoentities('FullData')
				.';'.$langs->transnoentities('DebugInfo')
				."\n");

			$loweridinerror = 0;
			$i = 0;

			while ($obj = $db->fetch_object($resql)) {
				// We set here all data used into signature calculation (see checkSignature method) and more
				// IMPORTANT: We must have here, the same rule for transformation of data than into the fetch method (db->jdate for date, ...)
				$block_static->id = $obj->rowid;
				$block_static->entity = $obj->entity;


				$block_static->date_creation = $db->jdate($obj->date_creation);		// TODO Use gmt

				$block_static->amounts = (float) $obj->amounts;						// Database store value with 8 digits, we cut ending 0 them with (flow)
				$block_static->vat = $obj->vat;

				$block_static->action = $obj->action;
				$block_static->date_object = $db->jdate($obj->date_object);			// TODO Use gmt ?
				$block_static->ref_object = $obj->ref_object;

				$block_static->user_fullname = $obj->user_fullname;

				$block_static->object_data = $block_static->dolDecodeBlockedData($obj->object_data);

				// Old hash + Previous fields concatenated = signature
				$block_static->signature = $obj->signature;

				$block_static->element = $obj->element;								// Not in signature
				$block_static->fk_object = $obj->fk_object;							// Not in signature

				$block_static->fk_user = $obj->fk_user;								// Not in signature

				$block_static->date_modification = $db->jdate($obj->tms);			// Not in signature
				$block_static->object_version = $obj->object_version;				// Not in signature
				$block_static->object_format = $obj->object_format;					// Not in signature

				$block_static->certified = ($obj->certified == 1);

				$block_static->linktoref = $obj->linktoref;
				$block_static->linktype = $obj->linktype;

				$block_static->debuginfo = $obj->debuginfo;

				//var_dump($block->id.' '.$block->signature, $block->object_data);
				$checksignature = $block_static->checkSignature($previoushash); // If $previoushash is not defined, checkSignature will search it

				if ($checksignature) {
					$statusofrecord = 'Valid';
					if ($loweridinerror > 0) {
						$statusofrecordnote = 'ValidButFoundAPreviousKO';
					} else {
						$statusofrecordnote = '';
					}
				} else {
					$statusofrecord = 'KO';
					$statusofrecordnote = 'LineCorruptedOrNotMatchingPreviousOne';
					$loweridinerror = $obj->rowid;
				}

				if ($i == 0) {
					$statusofrecordnote = $langs->trans("PreviousFingerprint").': '.$previoushash.($statusofrecordnote ? ' - '.$statusofrecordnote : '');
				}

				fwrite($fh, $block_static->id
					.';'.$block_static->date_creation
					.';"'.str_replace('"', '""', $block_static->user_fullname).'";'
					.$block_static->action
					.';'.$block_static->element
					.';'.$block_static->amounts			// Can be 1.20000000 with 8 digits. TODO Clean to have 8 digits in V1
					.';'.$block_static->fk_object
					.';'.$block_static->date_object
					.';"'.str_replace('"', '""', $block_static->ref_object).'";"'
					.$block_static->signature.'";'
					.$statusofrecord
					.';'.$statusofrecordnote
					.';'.$block_static->object_version
					.';"'.str_replace('"', '""', $obj->object_data).'"'				// We must the string to decode into object with dolDecodeBlockedData
					.';"'.str_replace('"', '""', $block_static->debuginfo).'"'
					."\n");

				// Set new previous hash for next fetch
				$previoushash = $obj->signature;

				$i++;
			}

			fclose($fh);

			// Calculate the md5 of the file (the last line has a return line)
			$md5value = md5_file($tmpfile);

			// Now add a signature to check integrity at end of file
			file_put_contents($tmpfile, 'END - md5='.$md5value, FILE_APPEND);
			dolChmod($tmpfile);

			header('Content-Type: application/octet-stream');
			header("Content-Transfer-Encoding: Binary");
			header("Content-disposition: attachment; filename=\"".$nameofdownoadedfile.".csv\"");

			readfile($tmpfile);

			exit;
		} else {
			setEventMessages($db->lasterror, null, 'errors');
		}
	}
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

$blocks = $block_static->getLog('all', (string) $search_id, $MAXLINES, $sortfield, $sortorder, (int) $search_fk_user, $search_start, $search_end, $search_ref, $search_amount, $search_code, $search_signature);
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

$registrationnumber = getHashUniqueIdOfRegistration();
$texttop = '<small class="opacitymedium">'.$langs->trans("RegistrationNumber").':</small> <small>'.dol_trunc($registrationnumber, 10).'</small>';

print load_fiche_titre($title.'<br>'.$texttop, $linkback, 'blockedlog', 0, '', '', $morehtmlcenter);

$head = blockedlogadmin_prepare_head(GETPOST('withtab', 'alpha'));

print dol_get_fiche_head($head, 'fingerprints', '', -1);

//print $texttop;
//print '<br><br>';

print '<div class="opacitymedium hideonsmartphone justify">';

print $langs->trans("FingerprintsDesc")."<br>";
$s = $langs->trans("FilesIntegrityDesc", '{s}');
$s = str_replace('{s}', DOL_URL_ROOT.'/blockedlog/admin/filecheck.php', $s);
print $s;
print "<br>\n";
print "</div>\n";

$htmltext = '';
$htmltext .= $langs->trans("UnalterableLogTool2", $langs->transnoentitiesnoconv("Archives"))."<br>";
$htmltext .= $langs->trans("UnalterableLogTool3")."<br>";

print info_admin($htmltext, 0, 0, 'warning');


print '<br>';

$param = '';
if ($contextpage != getDolDefaultContextPage(__FILE__)) {
	$param .= '&contextpage='.urlencode($contextpage);
}
if ($limit > 0 && $limit != $conf->liste_limit) {
	$param .= '&limit='.((int) $limit);
}
if ($search_id != '') {
	$param .= '&search_id='.urlencode($search_id);
}
if ($search_fk_user > 0) {
	$param .= '&search_fk_user='.urlencode($search_fk_user);
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
if ($optioncss != '') {
	$param .= '&optioncss='.urlencode($optioncss);
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
print $form->select_dolusers($search_fk_user, 'search_fk_user', 1, null, 0, '', '', '0', 0, 0, '', 0, '', 'maxwidth150');
print '</td>';

// Actions code
print '<td class="liste_titre">';
print $form->multiselectarray('search_code', $block_static->trackedevents, $search_code, 0, 0, 'maxwidth150', 1);
print '</td>';

// Ref
print '<td class="liste_titre"><input type="text" class="maxwidth50" name="search_ref" value="'.dol_escape_htmltag($search_ref).'"></td>';

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
if (getDolGlobalString('MAIN_FEATURES_LEVEL') > 0) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
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
print getTitleFieldOfList($langs->trans('Action'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Ref'), 0, $_SERVER["PHP_SELF"], 'ref_object', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($langs->trans('Amount'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'right ', 0, $langs->trans("TotalTTCIfInvoiceSeeCompleteDataForDetail").'<br>'.$langs->trans("AmountInCurrency", getDolCurrency()))."\n";
print getTitleFieldOfList($langs->trans('DataOfArchivedEvent'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ', 0, $langs->trans('DataOfArchivedEventHelp'), 1)."\n";
print getTitleFieldOfList($langs->trans('Fingerprint'), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($form->textwithpicto($langs->trans('Status'), $langs->trans('DataOfArchivedEventHelp2')), 0, $_SERVER["PHP_SELF"], '', '', $param, '', $sortfield, $sortorder, 'center ')."\n";
if (getDolGlobalString('MAIN_FEATURES_LEVEL') > 0) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
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
	// This is version that optimize the memory (note: it will not report errors that are outside the filter range)
	if (is_array($blocks)) {
		foreach ($blocks as &$block) {
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

			if ($nbshown < $MAXFORSHOWNLINKS) {	// For performance and memory purpose, we get/show the link of objects only for the 100 first output
				$object_link = $block->getObjectLink();
				$object_link_title = '';
			} else {
				$object_link = $block->element.'/'.$block->fk_object;
				$object_link_title = $langs->trans('LinkHasBeenDisabledForPerformancePurpose');
			}

			print '<tr class="oddeven">';

			// Action column
			if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
				print '<td class="liste_titre">';
				print '</td>';
			}

			// ID
			print '<td>'.dol_escape_htmltag((string) $block->id).'</td>';

			// Date
			print '<td class="nowraponall">'.dol_print_date($block->date_creation, 'dayhour').'</td>';

			// User
			print '<td class="tdoverflowmax200" title="'.dol_escape_htmltag($block->user_fullname).'">';
			//print $block->getUser()
			print dol_escape_htmltag($block->user_fullname);
			print '</td>';

			// Action
			$labelofaction = $langs->transnoentitiesnoconv('log'.$block->action);
			print '<td class="tdoverflowmax250" title="'.dol_escape_htmltag($labelofaction).'">'.dolPrintHTML($labelofaction).'</td>';

			// Ref
			print '<td class="nowraponall">';
			if (!empty($block->ref_object)) {
				print dol_escape_htmltag($block->ref_object);
			} else {
				// Ref not stored
			}
			print '</td>';

			//$tmpobj = json_decode($block->object_data);

			// Amount
			print '<td class="right nowraponall">';

			// Define $totalhtamount, $totalvatamount, $totalamount
			if (empty($totalamount[$block->action])) {
				$totalamount[$block->action] = array();
			}
			if ($block->action == 'BILL_VALIDATE') {
				$total_ht = $block->object_data->total_ht;
				$total_vat = $block->object_data->total_tva;
				$total_ttc = $block->object_data->total_ttc;

				if (empty($totalamount[$block->action][$block->ref_object])) {	// If not, we already met the event for this object, we keep only first one.
					$totalhtamount[$block->action][$block->ref_object] = $total_ht;
					$totalvatamount[$block->action][$block->ref_object] = $total_vat;
					$totalamount[$block->action][$block->ref_object] = $total_ttc;
				}
			} elseif ($block->action == 'PAYMENT_CUSTOMER_CREATE') {
				$total_ht = $block->object_data->amount;
				$total_vat = 0;
				$total_ttc = $block->object_data->amount;

				if (empty($totalhtamount[$block->action][$block->ref_object])) {
					$totalhtamount[$block->action][$block->ref_object] = 0;
				}
				if (empty($totalvatamount[$block->action][$block->ref_object])) {
					$totalvatamount[$block->action][$block->ref_object] = 0;
				}
				if (empty($totalamount[$block->action][$block->ref_object])) {
					$totalamount[$block->action][$block->ref_object] = 0;
				}
				$totalhtamount[$block->action][$block->ref_object] = $total_ht;
				$totalvatamount[$block->action][$block->ref_object] = $total_vat;
				$totalamount[$block->action][$block->ref_object] = $total_ttc;
			} else {
				$total_ttc = $block->amounts;
			}

			if (empty($total_ttc)) {
				print '<span class="opacitymedium">';
			}
			print price($total_ttc);
			if (empty($total_ttc)) {
				print '</span>';
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

	if ($nbshown == 0) {
		$colspan = 11;
		if (getDolGlobalString('MAIN_FEATURES_LEVEL') > 0) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
			$colspan++;
		}
		print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoRecordFound").'</span></td></tr>';
	} else {
		foreach ($totalamount as $key => $totalamountperref) {
			if ($key == 'BILL_VALIDATE') {
				// Total
				print '<tr class="totalline">';

				// Action column
				if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
					print '<td class="liste_titre">';
					print '</td>';
				}

				// ID
				print '<td colspan="2">'.dolPrintHTML($langs->trans("TotalForAction").' '.$langs->trans('log'.$key)).'</td>';

				// Date
				//print '<td class="nowraponall"></td>';

				// User
				print '<td class="tdoverflowmax200">';
				print '</td>';

				// Action
				print '<td></td>';

				// Ref
				print '<td class="nowraponall">';
				print '</td>';

				// Amount (HT)
				print '<td class="right nowraponall">';
				$totalhttoshow = 0;
				foreach ($totalhtamount[$key] as $value) {
					$totalhttoshow += $value;
				}
				print $langs->trans("HT").': ';
				print price($totalhttoshow);

				print '<br>';

				$totalvattoshow = 0;
				foreach ($totalvatamount[$key] as $value) {
					$totalvattoshow += $value;
				}
				print $langs->trans("VAT").': ';
				print price($totalvattoshow);

				print '<br>';

				$totaltoshow = 0;
				foreach ($totalamountperref as $value) {
					$totaltoshow += $value;
				}
				print $langs->trans("TTC").': ';
				print price($totaltoshow);
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
				if (getDolGlobalString('MAIN_FEATURES_LEVEL') > 0) {	// If in experimental or develop mode, we add some debug information. It may help developers to find origin of bugs.
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
