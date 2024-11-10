<?php
/* Copyright (C) 2004-2023  Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2015       Bahfir Abbes		<bafbes@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
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
 *		\file       htdocs/admin/tools/listevents.php
 *      \ingroup    core
 *      \brief      List of security events
 */

// Load Dolibarr environment
require '../../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/triggers/interface_20_all_Logevents.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

$action = GETPOST('action', 'aZ09');
$confirm = GETPOST('confirm', 'alpha');
$optioncss = GETPOST("optioncss", "aZ"); // Option for the css output (always '' except when 'print')
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : str_replace('_', '', basename(dirname(__FILE__)).basename(__FILE__, '.php')); // To manage different context of search

// Load translation files required by the page
$langs->loadLangs(array("companies", "admin", "users", "other","withdrawals"));

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
if (!$sortfield) {
	$sortfield = "e.rowid";
}
if (!$sortorder) {
	$sortorder = "DESC";
}

$search_rowid = GETPOST("search_rowid", "intcomma");
$search_code = GETPOST("search_code", "alpha");
$search_ip   = GETPOST("search_ip", "alpha");
$search_user = GETPOST("search_user", "alpha");
$search_desc = GETPOST("search_desc", "alpha");
$search_ua   = GETPOST("search_ua", "restricthtml");
$search_prefix_session = GETPOST("search_prefix_session", "restricthtml");
$search_entity = ($user->entity > 0 ? $user->entity : GETPOSTINT('search_entity'));

$now = dol_now();
$nowarray = dol_getdate($now);

if (GETPOSTINT("date_startmonth") > 0) {
	$date_start = dol_mktime(0, 0, 0, GETPOSTINT("date_startmonth"), GETPOSTINT("date_startday"), GETPOSTINT("date_startyear"), 'tzuserrel');
} else {
	$date_start = '';
}
if (GETPOSTINT("date_endmonth") > 0) {
	$date_end = dol_get_last_hour(dol_mktime(23, 59, 59, GETPOSTINT("date_endmonth"), GETPOSTINT("date_endday"), GETPOSTINT("date_endyear"), 'tzuserrel'), 'tzuserrel');
} else {
	$date_end = '';
}

// checks:if date_start>date_end  then date_end=date_start + 24 hours
if ($date_start !== '' && $date_end !== '' && $date_start > $date_end) {
	$date_end = $date_start + 86400;
}

if (!GETPOSTISSET('pageplusoneold') && !GETPOSTISSET('page') && $date_start === '') { // We define date_start and date_end
	$date_start = dol_get_first_day($nowarray['year'], $nowarray['mon'], 'tzuserrel');
}
if (!GETPOSTISSET('pageplusoneold') && !GETPOSTISSET('page') && $date_end === '') {
	$date_end = dol_get_last_day($nowarray['year'], $nowarray['mon'], 'tzuserrel');
}

// Set $date_startmonth...
$date_startday = '';
$date_startmonth = '';
$date_startyear = '';
$date_endday = '';
$date_endmonth = '';
$date_endyear = '';
if ($date_start !== '') {
	$tmp = dol_getdate($date_start);
	$date_startday = $tmp['mday'];
	$date_startmonth = $tmp['mon'];
	$date_startyear = $tmp['year'];
}
if ($date_end !== '') {
	$tmp = dol_getdate($date_end);
	$date_endday = $tmp['mday'];
	$date_endmonth = $tmp['mon'];
	$date_endyear = $tmp['year'];
}

// Add prefix session
$arrayfields = array(
	'e.prefix_session' => array(
		'label' => 'UserAgent',
		'checked' => (!getDolGlobalString('AUDIT_ENABLE_PREFIX_SESSION') ? 0 : 1),
		'enabled' => (!getDolGlobalString('AUDIT_ENABLE_PREFIX_SESSION') ? 0 : 1),
		'position' => 110
	)
);

// Security check
/*
$socid = 0;
if ($user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}
*/

if (!$user->admin) {
	accessforbidden();
}


/*
 * Actions
 */

$now = dol_now();

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All tests are required to be compatible with all browsers
	$date_start = '';
	$date_end = '';
	$date_startday = '';
	$date_endday = '';
	$date_startmonth = '';
	$date_endmonth = '';
	$date_startyear = '';
	$date_endyear = '';
	$search_rowid = '';
	$search_code = '';
	$search_ip = '';
	$search_user = '';
	$search_desc = '';
	$search_ua = '';
	$search_prefix_session = '';
	$search_entity = '';
}

// Purge audit events
if ($action == 'confirm_purge' && $confirm == 'yes' && $user->admin) {
	$error = 0;

	$db->begin();
	$securityevents = new Events($db);

	// Delete events
	$sql = "DELETE FROM ".MAIN_DB_PREFIX."events";
	$sql .= " WHERE entity = ".$conf->entity;

	dol_syslog("listevents purge", LOG_DEBUG);
	$resql = $db->query($sql);
	if (!$resql) {
		$error++;
		setEventMessages($db->lasterror(), null, 'errors');
	}

	// Add event purge
	$text = $langs->trans("SecurityEventsPurged");
	$securityevent = new Events($db);
	$securityevent->type = 'SECURITY_EVENTS_PURGE';
	$securityevent->dateevent = $now;
	$securityevent->description = $text;

	$result = $securityevent->create($user);
	if ($result > 0) {
		$db->commit();
		dol_syslog($text, LOG_WARNING);
	} else {
		$error++;
		dol_syslog($securityevent->error, LOG_ERR);
		$db->rollback();
	}
}


/*
 *	View
 */

$title = $langs->trans("Audit");
llxHeader('', $title, '', '', 0, 0, '', '', '', 'mod-admin page-tools_listevents');

$form = new Form($db);

$userstatic = new User($db);
$usefilter = 0;

$sql = "SELECT e.rowid, e.type, e.ip, e.user_agent, e.dateevent,";
$sql .= " e.fk_user, e.description, e.prefix_session,";
$sql .= " u.login, u.admin, u.email, u.entity, u.firstname, u.lastname, u.gender, u.photo, u.statut as status";
$sql .= " FROM ".MAIN_DB_PREFIX."events as e";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON u.rowid = e.fk_user";
if ($search_entity > 0) {
	$sql .= " WHERE e.entity = ".((int) $search_entity).")";
} else {
	$sql .= " WHERE e.entity IN (".getEntity('event', (GETPOSTINT('search_current_entity') ? 0 : 1)).")";
}
if ($date_start !== '') {
	$sql .= " AND e.dateevent >= '".$db->idate($date_start)."'";
}
if ($date_end !== '') {
	$sql .= " AND e.dateevent <= '".$db->idate($date_end)."'";
}
if ($search_rowid) {
	$usefilter++;
	$sql .= natural_search("e.rowid", $search_rowid, 1);
}
if ($search_code) {
	$usefilter++;
	$sql .= natural_search("e.type", $search_code, 0);
}
if ($search_ip) {
	$usefilter++;
	$sql .= natural_search("e.ip", $search_ip, 0);
}
if ($search_user) {
	$usefilter++;
	$sql .= natural_search("u.login", $search_user, 0);
}
if ($search_desc) {
	$usefilter++;
	$sql .= natural_search("e.description", $search_desc, 0);
}
if ($search_ua) {
	$usefilter++;
	$sql .= natural_search("e.user_agent", $search_ua, 0);
}
if ($search_prefix_session) {
	$usefilter++;
	$sql .= natural_search("e.prefix_session", $search_prefix_session, 0);
}
$sql .= $db->order($sortfield, $sortorder);

// Count total nb of records
$nbtotalofrecords = '';
/*if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
{
	$result = $db->query($sql);
	$nbtotalofrecords = $db->num_rows($result);
	if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
	{
		$page = 0;
		$offset = 0;
	}
}*/

$sql .= $db->plimit($limit + 1, $offset);

$result = $db->query($sql);
if ($result) {
	$num = $db->num_rows($result);
	$i = 0;

	$param = '';
	if ($contextpage && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage='.urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit='.((int) $limit);
	}
	if ($optioncss != '') {
		$param .= '&optioncss='.urlencode($optioncss);
	}
	if ($search_entity != '') {
		$param .= '&search_entity='.((int) $search_entity);
	}
	if ($search_rowid) {
		$param .= '&search_rowid='.urlencode((string) ($search_rowid));
	}
	if ($search_code) {
		$param .= '&search_code='.urlencode($search_code);
	}
	if ($search_ip) {
		$param .= '&search_ip='.urlencode($search_ip);
	}
	if ($search_user) {
		$param .= '&search_user='.urlencode($search_user);
	}
	if ($search_desc) {
		$param .= '&search_desc='.urlencode($search_desc);
	}
	if ($search_ua) {
		$param .= '&search_ua='.urlencode($search_ua);
	}
	if ($search_prefix_session) {
		$param .= '&search_prefix_session='.urlencode($search_prefix_session);
	}
	if ($date_startmonth) {
		$param .= "&date_startmonth=".((int) $date_startmonth);
	}
	if ($date_startday) {
		$param .= "&date_startday=".((int) $date_startday);
	}
	if ($date_startyear) {
		$param .= "&date_startyear=".((int) $date_startyear);
	}
	if ($date_endmonth) {
		$param .= "&date_endmonth=".((int) $date_endmonth);
	}
	if ($date_endday) {
		$param .= "&date_endday=".((int) $date_endday);
	}
	if ($date_endyear) {
		$param .= "&date_endyear=".((int) $date_endyear);
	}

	$center = '';
	if ($num) {
		$center = '<a class="butActionDelete small" href="'.$_SERVER["PHP_SELF"].'?action=purge">'.$langs->trans("Purge").'</a>';
	}

	print '<form method="POST" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.newToken().'">';

	// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
	print_barre_liste($langs->trans("ListOfSecurityEvents"), $page, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, $center, $num, $nbtotalofrecords, 'setup', 0, '', '', $limit);

	if ($action == 'purge') {
		$formquestion = array();
		print $form->formconfirm($_SERVER["PHP_SELF"].'?noparam=noparam', $langs->trans('PurgeAuditEvents'), $langs->trans('ConfirmPurgeAuditEvents'), 'confirm_purge', $formquestion, 'no', 1);
	}

	// Check some parameters
	// TODO Add a tab with this and other information
	/*
	global $dolibarr_main_prod, $dolibarr_nocsrfcheck;
	if (empty($dolibarr_main_prod)) {
		print $langs->trans("Warning").' dolibarr_main_prod = '.$dolibarr_main_prod;
		print ' '.img_warning($langs->trans('SwitchThisForABetterSecurity', 1)).'<br>';
	}
	if (!empty($dolibarr_nocsrfcheck)) {
		print $langs->trans("Warning").' dolibarr_nocsrfcheck = '.$dolibarr_nocsrfcheck;
		print ' '.img_warning($langs->trans('SwitchThisForABetterSecurity', 0)).'<br>';
	}
	*/

	print '<div class="div-table-responsive">';
	print '<table class="liste noborder centpercent">';

	// Fields title search
	print '<tr class="liste_titre">';

	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch center">';
		$searchpicto = $form->showFilterAndCheckAddButtons(0);
		print $searchpicto;
		print '</td>';
	}

	// ID
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth50" type="text" name="search_rowid" value="'.dol_escape_htmltag($search_rowid).'">';
	print '</td>';

	// Date
	print '<td class="liste_titre" width="15%">';
	print $form->selectDate($date_start === '' ? -1 : $date_start, 'date_start', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzuserrel');
	print $form->selectDate($date_end === '' ? -1 : $date_end, 'date_end', 0, 0, 0, '', 1, 0, 0, '', '', '', '', 1, '', '', 'tzuserrel');
	print '</td>';

	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75" type="text" name="search_code" value="'.dol_escape_htmltag($search_code).'">';
	print '</td>';

	// IP
	print '<td class="liste_titre">';
	print '<input class="flat maxwidth75" type="text" name="search_ip" value="'.dol_escape_htmltag($search_ip).'">';
	print '</td>';

	print '<td class="liste_titre">';
	print '<input class="flat maxwidth100" type="text" name="search_user" value="'.dol_escape_htmltag($search_user).'">';
	print '</td>';

	print '<td class="liste_titre">';
	//print '<input class="flat maxwidth100" type="text" size="10" name="search_desc" value="'.$search_desc.'">';
	print '</td>';

	print '<td class="liste_titre left">';
	print '<input class="flat maxwidth100" type="text" name="search_ua" value="'.dol_escape_htmltag($search_ua).'">';
	print '</td>';

	if (!empty($arrayfields['e.prefix_session']['checked'])) {
		print '<td class="liste_titre left">';
		print '<input class="flat maxwidth100" type="text" name="search_prefix_session" value="'.dol_escape_htmltag($search_prefix_session).'">';
		print '</td>';
	}

	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print '<td class="liste_titre maxwidthsearch">';
		$searchpicto = $form->showFilterAndCheckAddButtons(0);
		print $searchpicto;
		print '</td>';
	}

	print "</tr>\n";


	print '<tr class="liste_titre">';
	// Action column
	if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre('');
	}
	print_liste_field_titre("ID", $_SERVER["PHP_SELF"], "e.rowid", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Date", $_SERVER["PHP_SELF"], "e.dateevent", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Code", $_SERVER["PHP_SELF"], "e.type", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("IP", $_SERVER["PHP_SELF"], "e.ip", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("User", $_SERVER["PHP_SELF"], "u.login", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("Description", $_SERVER["PHP_SELF"], "e.description", "", $param, '', $sortfield, $sortorder);
	print_liste_field_titre("UserAgent", $_SERVER["PHP_SELF"], "e.user_agent", "", $param, '', $sortfield, $sortorder);
	if (!empty($arrayfields['e.prefix_session']['checked'])) {
		print_liste_field_titre("SuffixSessionName", $_SERVER["PHP_SELF"], "e.prefix_session", "", $param, '', $sortfield, $sortorder);
	}
	// Action column
	if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
		print_liste_field_titre('');
	}
	print "</tr>\n";

	while ($i < min($num, $limit)) {
		$obj = $db->fetch_object($result);

		print '<tr class="oddeven">';

		// Action column
		if (getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="center">';
			$htmltext = '<b>'.$langs->trans("UserAgent").'</b>: '.($obj->user_agent ? dol_string_nohtmltag($obj->user_agent) : $langs->trans("Unknown"));
			$htmltext .= '<br><b>'.$langs->trans("SuffixSessionName").' (DOLSESSID_...)</b>: '.($obj->prefix_session ? dol_string_nohtmltag($obj->prefix_session) : $langs->trans("Unknown"));
			print $form->textwithpicto('', $htmltext);
			print '</td>';
		}

		// ID
		print '<td class="nowrap left">'.dol_escape_htmltag($obj->rowid).'</td>';

		// Date
		print '<td class="nowrap left">'.dol_print_date($db->jdate($obj->dateevent), '%Y-%m-%d %H:%M:%S', 'tzuserrel').'</td>';

		// Code
		print '<td>'.dol_escape_htmltag($obj->type).'</td>';

		// IP
		print '<td class="nowraponall">';
		print dol_print_ip($obj->ip);
		print '</td>';

		// Login
		print '<td class="tdoverflowmax125">';
		if ($obj->fk_user > 0) {
			$userstatic->id = $obj->fk_user;
			$userstatic->login = $obj->login;
			$userstatic->admin = $obj->admin;
			$userstatic->entity = $obj->entity;
			$userstatic->status = $obj->status;
			$userstatic->gender = $obj->gender;
			$userstatic->photo = $obj->photo;
			$userstatic->firstname = $obj->firstname;
			$userstatic->lastname = $obj->lastname;
			$userstatic->email = $obj->email;

			if (isModEnabled('multicompany') && $userstatic->admin && !$userstatic->entity) {
				print img_picto($langs->trans("SuperAdministratorDesc"), 'redstar', 'class="valignmiddle paddingright"');
			} elseif ($userstatic->admin) {
				print img_picto($langs->trans("AdministratorDesc"), 'star', 'class="valignmiddle paddingright"');
			}

			//print $userstatic->getLoginUrl(-1);
			print $userstatic->getNomUrl(-1);
		} else {
			print '&nbsp;';
		}
		print '</td>';

		// Description
		$text = $langs->trans($obj->description);
		$reg = array();
		if (InterfaceLogevents::isEventActionTextKey($obj->description)) {
			$val = explode(' : ', $obj->description);
			$text = $langs->trans($val[0], isset($val[1]) ? $val[1] : '', isset($val[2]) ? $val[2] : '', isset($val[3]) ? $val[3] : '', isset($val[4]) ? $val[4] : '');
		}
		if (preg_match('/\((.*)\)(.*)/i', $obj->description, $reg)) {
			$val = explode(',', $reg[1]);
			$text = $langs->trans($val[0], isset($val[1]) ? $val[1] : '', isset($val[2]) ? $val[2] : '', isset($val[3]) ? $val[3] : '', isset($val[4]) ? $val[4] : '');
			if (!empty($reg[2])) {
				$text .= $reg[2];
			}
		}
		print '<td class="tdoverflowmax400" title="'.dol_escape_htmltag($text).'">';
		print dol_escape_htmltag($text);
		print '</td>';

		// User agent
		print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->user_agent).'">';
		print dol_escape_htmltag($obj->user_agent);
		print '</td>';

		// Prefix
		if (!empty($arrayfields['e.prefix_session']['checked'])) {
			print '<td class="tdoverflowmax150" title="'.dol_escape_htmltag($obj->prefix_session).'">';
			print dol_escape_htmltag($obj->prefix_session);
			print '</td>';
		}

		// Action column
		if (!getDolGlobalString('MAIN_CHECKBOX_LEFT_COLUMN')) {
			print '<td class="right">';
			$htmltext = '<b>'.$langs->trans("UserAgent").'</b>: '.($obj->user_agent ? dol_string_nohtmltag($obj->user_agent) : $langs->trans("Unknown"));
			$htmltext .= '<br><b>'.$langs->trans("SuffixSessionName").' (DOLSESSID_...)</b>: '.($obj->prefix_session ? dol_string_nohtmltag($obj->prefix_session) : $langs->trans("Unknown"));
			print $form->textwithpicto('', $htmltext);
			print '</td>';
		}

		print "</tr>\n";
		$i++;
	}

	if ($num == 0) {
		$colspan = 8;
		if (!empty($arrayfields['e.prefix_session']['checked'])) {
			$colspan++;
		}
		if ($usefilter) {
			print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoEventFoundWithCriteria").'</span></td></tr>';
		} else {
			print '<tr><td colspan="'.$colspan.'"><span class="opacitymedium">'.$langs->trans("NoEventOrNoAuditSetup").'</span></td></tr>';
		}
	}
	print "</table>";
	print "</div>";

	print "</form>";
	$db->free($result);
} else {
	dol_print_error($db);
}

// End of page
llxFooter();
$db->close();
