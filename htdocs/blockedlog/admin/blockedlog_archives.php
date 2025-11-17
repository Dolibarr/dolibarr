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
 *    \file       htdocs/blockedlog/admin/blockedlog_archives.php
 *    \ingroup    blockedlog
 *    \brief      Page to view/export and check unalterable logs
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
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'banks', 'bills', 'blockedlog', 'other'));

// Access Control
if ((!$user->admin && !$user->hasRight('blockedlog', 'read')) || empty($conf->blockedlog->enabled)) {
	accessforbidden();
}

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
$search_id = GETPOST('search_id', 'alpha');
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
$formother = new FormOther($db);

if (GETPOST('withtab', 'alpha')) {
	$title = $langs->trans("ModuleSetup").' '.$langs->trans('BlockedLog');
} else {
	$title = $langs->trans("BrowseBlockedLog");
}
$help_url = "EN:Module_Unalterable_Archives_-_Logs|FR:Module_Archives_-_Logs_Inaltérable";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-blockedlog page-admin_blockedlog_list');

$blocks = $block_static->getLog('all', (int) $search_id, $MAXLINES, $sortfield, $sortorder, (int) $search_fk_user, $search_start, $search_end, $search_ref, $search_amount, $search_code, $search_signature);
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
	$linkback = '<a href="'.($backtopage ? $backtopage : DOL_URL_ROOT.'/admin/modules.php').'">'.$langs->trans("BackToModuleList").'</a>';
}

print load_fiche_titre($title, $linkback, 'blockedlog');

$head = blockedlogadmin_prepare_head(GETPOST('withtab', 'alpha'));

print dol_get_fiche_head($head, 'archives', '', -1);

print '<div class="opacitymedium hideonsmartphone justify">';

print $langs->trans("ArchivesDesc")."<br>";
print "<br>\n";

print "</div>\n";

$htmltext = '';

$htmltext .= $langs->trans("UnalterableLogTool2", $langs->transnoentities("Archives"))."<br>";
if ($mysoc->country_code == 'FR') {
	$htmltext .= '<br>'.$langs->trans("UnalterableLogTool1FR").'<br>';
}
//$htmltext .= $langs->trans("UnalterableLogTool1");
//$htmltext .= $langs->trans("UnalterableLogTool3")."<br>";

print info_admin($htmltext);


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

// Add $param from extra fields
//include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_param.tpl.php';

print '<form method="POST" id="searchFormList" action="'.$_SERVER["PHP_SELF"].'?output=file">';
print '<input type="hidden" name="token" value="'.newToken().'">';

print '<div class="right">';
print $langs->trans("RestrictYearToExport").': ';
// Month
print $formother->select_month((string) GETPOSTINT('monthtoexport'), 'monthtoexport', 1, 0, 'minwidth50 maxwidth75imp valignmiddle', true);
print '<input type="text" name="yeartoexport" class="valignmiddle maxwidth75imp" value="'.GETPOST('yeartoexport').'" placeholder="'.$langs->trans("Year").'">';
print '<input type="hidden" name="withtab" value="'.GETPOST('withtab', 'alpha').'">';
print '<input type="submit" name="downloadcsv" class="button" value="'.$langs->trans('DownloadLogCSV').'">';
/*if (getDolGlobalString('BLOCKEDLOG_USE_REMOTE_AUTHORITY')) {
	print ' | <a href="?action=downloadblockchain'.(GETPOST('withtab', 'alpha') ? '&withtab='.GETPOST('withtab', 'alpha') : '').'">'.$langs->trans('DownloadBlockChain').'</a>';
}*/
print ' </div><br>';

print '</form>';

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

// TODO List of archive files into blockedlog/archives


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
		mydialog.dialog({autoOpen: false, modal: true, height: (window.innerHeight - 150), width: \'80%\', title: \''.dol_escape_js($langs->trans("UnlaterableDataOfEvent")).'\',});
		mydialog.dialog("open");
		return false;
	});
})
</script>'."\n";


if (GETPOST('withtab', 'alpha')) {
	print dol_get_fiche_end();
}

print '<br><br>';

// End of page
llxFooter();
$db->close();
