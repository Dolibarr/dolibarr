<?php
/* Copyright (C) 2017		ATM Consulting				<contact@atm-consulting.fr>
 * Copyright (C) 2017-2018	Laurent Destailleur			<eldy@destailleur.fr>
 * Copyright (C) 2018-2026  Frédéric France				<frederic.france@free.fr>
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
 *    \file       htdocs/blockedlog/admin/blockedlog_control.php
 *    \ingroup    blockedlog
 *    \brief      Page to view if a registered instance has done a backup restoration
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
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

// Load translation files required by the page
$langs->loadLangs(array('admin', 'banks', 'bills', 'blockedlog', 'other'));

// Get Parameters
$action      = GETPOST('action', 'aZ09');
$confirm     = GETPOST('confirm', 'aZ09');	// Used by the actions_linkedfiles.inc.php
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : getDolDefaultContextPage(__FILE__); // To manage different context of search
$backtopage  = GETPOST('backtopage', 'alpha'); // Go back to a dedicated page
$optioncss   = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')
$withtab = GETPOSTISSET('withtab') ? GETPOSTINT('withtab') : 1;

//$hmacexportkey = GETPOST('hmacexportkey', 'password');
$inputregistrationnumber = GETPOST('inputregistrationnumber');

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

// Access Control
if (((!$user->admin && !$user->hasRight('blockedlog', 'read')) || !isModEnabled('blockedlog')) && !userIsTaxAuditor()) {
	accessforbidden('Access to this page is reserved to an allowed tax auditors');
}

$result = restrictedArea($user, 'blockedlog', 0, '');

$permission = $user->hasRight('blockedlog', 'read');
$permissiontoadd = $user->hasRight('blockedlog', 'read');	// Permission is to upload new files to scan them
$permtoedit = $permissiontoadd;

$upload_dir = getMultidirOutput($block_static, 'blockedlog').'/archives';


/*
 * Actions
 */

if ($action == 'control' && $user->hasRight('blockedlog', 'read')) {		// read is read/export for blockedlog
	$action = '';
}


/*
 *	View
 */

$form = new Form($db);
$formother = new FormOther($db);

$title = $langs->trans("BrowseBlockedLog");
$help_url = "EN:Module_Unalterable_Archives_-_Logs|FR:Module_Archives_-_Logs_Inaltérable";

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'bodyforlist mod-blockedlog page-admin_blockedlog_list');

$linkback = '';

$morehtmlcenter = '';
$texttop = '';

$registrationnumber = getHashUniqueIdOfRegistration();
if (!userIsTaxAuditor()) {
	$texttop = '<small class="opacitymedium">'.$langs->trans("RegistrationNumber").':</small> <small>'.dol_trunc($registrationnumber, 10).'</small>';
	if (!isRegistrationDataSavedAndPushed()) {
		$texttop = '';
	}
}

print load_fiche_titre($title.'<br>'.$texttop, $linkback, 'blockedlog', 0, '', '', $morehtmlcenter);

$head = blockedlogadmin_prepare_head($withtab);

print dol_get_fiche_head($head, 'control', '', -1);

//print $texttop;
//print '<br><br>';

print '<div class="opacitymedium hideonsmartphone justify">';
print $langs->trans("ControlDesc")."<br>";
print "</div>\n";


print '<form method="POST" id="exportArchives" action="'.$_SERVER["PHP_SELF"].'?output=file">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="export">';

print '<div class="neutral">';

print '<span class="hideonsmartphone">'.$langs->trans("PleaseEnterPartialRegistrationNumber").': </span>';

print '<input type="text" name="inputregistrationnumber" class="width300" placeholder="'.$langs->trans("RegistrationNumber").'" value="'.$inputregistrationnumber.'" spellcheck="false">';

print '<br>';
print '<br>';

print '<center>';
print '<input type="submit" class="button small" name="submit" value="'.$langs->trans("ControlSignals").'">';
print '</center>';

print ' </div>';

print '</form>';

print dol_get_fiche_end();

print '<br>';

if ($inputregistrationnumber && strlen($inputregistrationnumber) < 8) {
	// Message
	print img_picto('', 'cross', 'class="pictofixedwidth"').$langs->trans("RegistrationNumberIsTooShort");
} elseif ($inputregistrationnumber) {
	// Return known information on this registration number
	if (!isModEnabled('captureserver')) {
		print 'The module captureserver to capture the signals from installed instance is not enabled. Is this instance the master instance of the Dolibarr foundation ?';
	} else {
		// Report registration
		$sql = "SELECT rowid, registerid, registername, registerprofid, registeremail, date_creation, tms, content, comment,";
		$sql .= " versiondolibarr, versionblockedlog, country_code";
		$sql .= " FROM ".MAIN_DB_PREFIX."captureserver_captureserver";
		$sql .= " WHERE registerid LIKE '".$db->escape($inputregistrationnumber)."%'";
		$sql .= " AND type = 'dolibarrregistration'";
		$sql .= " LIMIT 100";		// Should have only 1 record, so a limit of 100 is enough

		$resql = $db->query($sql);
		if ($resql) {
			$num = 0;
			while ($obj = $db->fetch_object($resql)) {
				$num++;
				print img_picto('', 'tick', 'class="pictofixedwidth"').' <b>'.dol_print_date($obj->date_creation, 'dayhour', 'gmt').'</b> &nbsp; ';
				print $langs->trans("RegistrationDone");
				print ' - '.$langs->trans("BlockedLogInstance").' '.dolPrintHTML($obj->registerid);
				print '<br>';
				print $langs->trans("LastRegistrationUpdate").' '.dol_print_date($obj->tms, 'dayhour', 'gmt');
				print '<br>';
				print $obj->registername.' - '.$obj->registerprofid.' - '.$obj->country_code.' - '.$obj->registeremail;
				print '<br>';
				print $langs->trans("Version").': '.$obj->versiondolibarr.' - '.$langs->trans("VersionOfModule", $langs->transnoentitiesnoconv("BlockedLog")).': '.$obj->versionblockedlog;
				/*
				print '<div class="small">';
				print $langs->trans("Note").': ';
				print $obj->comment;
				print '</div>';
				*/
				print '<br>';
			}
			if ($num == 0) {
				print img_picto('', 'cross', 'class="pictofixedwidth error"').$langs->trans("NoRegistrationFound");
				print '<br>';
			}
		} else {
			dol_print_error($db);
		}

		print '<br>';


		// Report backup restoration or last lines deletion
		$sql = "SELECT rowid, registerid, date_creation, content, comment from ".MAIN_DB_PREFIX."captureserver_captureserver";
		$sql .= " WHERE registerid LIKE '".$db->escape($inputregistrationnumber)."%'";
		$sql .= " AND type = 'deletion_or_backup_restoration'";
		$sql .= " LIMIT 100";		// Should not happen, so a limit of 100 is enough

		$resql = $db->query($sql);
		if ($resql) {
			$num = 0;
			while ($obj = $db->fetch_object($resql)) {
				$num++;
				print img_picto('', 'cross', 'class="pictofixedwidth"').' <b>'.dol_print_date($obj->date_creation, 'dayhour').'</b> &nbsp; ';
				print $langs->trans("BackupRestorationOrLastLineDeletionDetected");
				print ' - '.$langs->trans("BlockedLogInstance").' '.dolPrintHTML($obj->registerid);
				print '<br>';
				print '<div class="small">';
				print $langs->trans("Note").': ';
				print $obj->comment;
				print '</div>';
				print '<br>';
				/*
				print '<textarea class=small">';
				print dolPrintHTMLForTextArea($obj->content);
				print '</textarea>';
				*/
				print '<br>';
			}
			if ($num == 0) {
				print img_picto('', 'tick', 'class="pictofixedwidth"').$langs->trans("NoBackupRestorationOrLastLineDeletionDetected");
			}
		} else {
			dol_print_error($db);
		}

		print '<br>';
	}
}


// End of page
llxFooter();
$db->close();
