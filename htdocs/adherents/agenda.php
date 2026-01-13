<?php
/* Copyright (C) 2001-2007	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2005		Brice Davoleau				<brice.davoleau@gmail.com>
 * Copyright (C) 2005-2009	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2006-2011	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2007		Patrick Raguin				<patrick.raguin@gmail.com>
 * Copyright (C) 2010		Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2024-2025  Frédéric France             <frederic.france@free.fr>
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
 *    \file       htdocs/adherents/agenda.php
 *    \ingroup    member
 *    \brief      Page of members events
 */

// Load Dolibarr environment
require '../main.inc.php';
/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/member.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

// Load translation files required by the page
$langs->loadLangs(array('companies', 'members'));

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : getDolDefaultContextPage(__FILE__);

if (GETPOSTISARRAY('actioncode')) {
	$actioncode = GETPOST('actioncode', 'array:alpha', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
}

$search_rowid = GETPOST('search_rowid');
$search_agenda_label = GETPOST('search_agenda_label');
$search_complete = GETPOST('search_complete');
$search_dateevent_start = GETPOSTDATE('dateevent_start');
$search_dateevent_end = GETPOSTDATE('dateevent_end');

// Get Parameters
$id = GETPOSTINT('id') ? GETPOSTINT('id') : GETPOSTINT('rowid');

// Pagination
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
if (!$sortfield) {
	$sortfield = 'a.datep,a.id';
}
if (!$sortorder) {
	$sortorder = 'DESC,DESC';
}

// Get object canvas (By default, this is not defined, so standard usage of dolibarr)
$objcanvas = null;

// Initialize a technical object to manage hooks of page. Note that conf->hooks_modules contains an array of hook context
$hookmanager->initHooks(array('memberagenda', 'globalcard'));

// Security check
$result = restrictedArea($user, 'adherent', $id);

// Initialize a technical objects
$object = new Adherent($db);
$result = $object->fetch($id);
if ($result > 0) {
	$object->fetch_thirdparty();

	$adht = new AdherentType($db);
	$result = $adht->fetch($object->typeid);
}


/*
 *	Actions
 */

$parameters = array('id' => $id, 'objcanvas' => $objcanvas);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

if (empty($reshook)) {
	// Cancel
	if (GETPOST('cancel', 'alpha') && !empty($backtopage)) {
		header("Location: ".$backtopage);
		exit;
	}

	// Purge search criteria
	if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
		$actioncode = '';
		$search_rowid = '';
		$search_agenda_label = '';
		$search_complete = '';
	}
}



/*
 *	View
 */

$contactstatic = new Contact($db);

$form = new Form($db);


if ($object->id > 0) {
	require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';

	$langs->load("companies");

	$title = $langs->trans("Member")." - ".$langs->trans("Agenda");

	$help_url = "EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder";

	llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-member page-card_agenda');

	if (isModEnabled('notification')) {
		$langs->load("mails");
	}
	$head = member_prepare_head($object);

	print dol_get_fiche_head($head, 'agenda', $langs->trans("Member"), -1, 'user');

	$linkback = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/adherents/list.php', ['restore_lastsearch_values' => 1]).'">'.$langs->trans("BackToList").'</a>';

	$morehtmlref = '<a href="'.dolBuildUrl(DOL_URL_ROOT.'/adherents/vcard.php', ['id' => $object->id]).'" class="refid">';
	$morehtmlref .= img_picto($langs->trans("Download").' '.$langs->trans("VCard"), 'vcard', 'class="valignmiddle marginleftonly paddingrightonly"');
	$morehtmlref .= '</a>';

	dol_banner_tab($object, 'rowid', $linkback, 1, 'rowid', 'ref', $morehtmlref);

	print '<div class="fichecenter">';

	print '<div class="underbanner clearboth"></div>';

	$object->info($id);
	dol_print_object_info($object, 1);

	print '</div>';

	print '<div class="clearboth"></div>';

	print dol_get_fiche_end();


	//print '<div class="tabsAction">';
	//print '</div>';


	$newcardbutton = '';

	$messagingUrl = dolBuildUrl(DOL_URL_ROOT.'/adherents/messaging.php', ['rowid' => $object->id]);
	$newcardbutton .= dolGetButtonTitle($langs->trans('ShowAsConversation'), '', 'fa fa-comments imgforviewmode', $messagingUrl, '', 1);
	$messagingUrl = dolBuildUrl(DOL_URL_ROOT.'/adherents/agenda.php', ['id' => $object->id]);
	$newcardbutton .= dolGetButtonTitle($langs->trans('MessageListViewType'), '', 'fa fa-bars imgforviewmode', $messagingUrl, '', 2);

	if (isModEnabled('agenda')) {
		$newcardbutton .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', dolBuildUrl(DOL_URL_ROOT.'/comm/action/card.php', ['action' => 'create', 'origin' => 'member', 'originid' => $id, 'backtopage' => dolBuildUrl($_SERVER['PHP_SELF'], ['id' => $object->id, 'origin' => 'member', 'originid' => $id])]));
	}

	if (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) {
		print '<br>';

		$param = '&id='.$id;
		if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
			$param .= '&contextpage='.urlencode($contextpage);
		}
		if ($limit > 0 && $limit != $conf->liste_limit) {
			$param .= '&limit='.((int) $limit);
		}
		if ($search_rowid) {
			$param .= '&search_rowid='.urlencode($search_rowid);
		}
		if ($actioncode !== '' && $actioncode !== '-1') {
			$param .= '&actioncode='.urlencode($actioncode);
		}
		if ($search_agenda_label) {
			$param .= '&search_agenda_label='.urlencode($search_agenda_label);
		}
		if ($search_complete != '') {
			$param .= '&search_complete='.urlencode($search_complete);
		}
		if ($search_dateevent_start != '') {
			$param .= '&dateevent_startyear='.GETPOSTINT('dateevent_startyear');
			$param .= '&dateevent_startmonth='.GETPOSTINT('dateevent_startmonth');
			$param .= '&dateevent_startday='.GETPOSTINT('dateevent_startday');
		}
		if ($search_dateevent_end != '') {
			$param .= '&dateevent_endyear='.GETPOSTINT('dateevent_endyear');
			$param .= '&dateevent_endmonth='.GETPOSTINT('dateevent_endmonth');
			$param .= '&dateevent_endday='.GETPOSTINT('dateevent_endday');
		}

		// Try to know count of actioncomm from cache
		require_once DOL_DOCUMENT_ROOT.'/core/lib/memory.lib.php';
		$cachekey = 'count_events_member_'.$object->id;
		$nbEvent = dol_getcache($cachekey);

		$titlelist = $langs->trans("ActionsOnMember").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
		if (!empty($conf->dol_optimize_smallscreen)) {
			$titlelist = $langs->trans("Actions").(is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">('.$nbEvent.')</span>' : '');
		}

		print_barre_liste($titlelist, 0, $_SERVER["PHP_SELF"], $param, $sortfield, $sortorder, '', 0, -1, '', 0, $newcardbutton, '', 0, 1, 0);

		// List of all actions
		$filters = array();
		$filters['search_agenda_label'] = $search_agenda_label;
		$filters['search_rowid'] = $search_rowid;
		$filters['search_complete'] = $search_complete;		// Can be 'na', '0', '100', '50'

		// TODO Replace this with same code than into list.php
		show_actions_done($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder, $object->module);
	}
}

// End of page
llxFooter();
$db->close();
