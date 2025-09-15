<?php
/* Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2024       MDW                 <mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025       Valentin Grimal         <valentin.grimal@pichinov.com>
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
 * \file       htdocs/expedition/messaging.php
 * \ingroup    shipping
 * \brief      Page with events on Shipping
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT . '/expedition/class/expedition.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/expedition.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/sendings.lib.php';


/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->load("sendings");

$id     = GETPOSTINT('id');
$ref    = GETPOST('ref', 'alpha');
$socid  = GETPOSTINT('socid');
$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ09');

$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST("sortfield", "aZ09comma");
$sortorder = GETPOST("sortorder", 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
$page = is_numeric($page) ? $page : 0;
$page = $page == -1 ? 0 : $page;
if (!$sortfield) {
	$sortfield = "a.datep,a.id";
}
if (!$sortorder) {
	$sortorder = "DESC";
}
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (GETPOST('actioncode', 'array')) {
	$actioncode = GETPOST('actioncode', 'array', 3);
	if (!count($actioncode)) {
		$actioncode = '0';
	}
} else {
	$actioncode = GETPOST("actioncode", "alpha", 3) ? GETPOST("actioncode", "alpha", 3) : (GETPOST("actioncode") == '0' ? '0' : getDolGlobalString('AGENDA_DEFAULT_FILTER_TYPE_FOR_OBJECT'));
}
$search_rowid = GETPOST('search_rowid');
$search_agenda_label = GETPOST('search_agenda_label');

$hookmanager->initHooks(array('shippingcardinfo'));

// Security check
$id = GETPOSTINT("id");
$socid = 0;
// Shipping module doesn't typically have a draft status, so simplified restrictedArea
$result = restrictedArea($user, 'expedition', $id, 'expedition&shipping');

if (!$user->hasRight('expedition', 'lire')) {
	accessforbidden();
}



/*
 * Actions
 */

$object = new Expedition($db);

if ($id > 0 || !empty($ref)) {
	$object->fetch($id, $ref);
	$object->fetch_thirdparty();
	$object->info($object->id);
}

$parameters = array('id' => $socid);
$reshook = $hookmanager->executeHooks('doActions', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
if ($reshook < 0) {
	setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
}

// Purge search criteria
if (GETPOST('button_removefilter_x', 'alpha') || GETPOST('button_removefilter.x', 'alpha') || GETPOST('button_removefilter', 'alpha')) { // All test are required to be compatible with all browsers
	$actioncode = '';
	$search_agenda_label = '';
}



/*
 * View
 */

$form = new Form($db);
$agenda = (isModEnabled('agenda') && ($user->hasRight('agenda', 'myactions', 'read') || $user->hasRight('agenda', 'allactions', 'read'))) ? '/' . $langs->trans("Agenda") : '';
$title = $langs->trans('Events') . $agenda . ' - ' . $object->ref; // Shipping uses ref primarily

if (getDolGlobalString('MAIN_HTML_TITLE') && preg_match('/shippingrefonly/', getDolGlobalString('MAIN_HTML_TITLE')) && $object->ref) { // New constant or fallback
	$title = $object->ref . ' - ' . $langs->trans("Info");
}
$help_url = "EN:Module_Shippings|FR:Module_Expeditions|ES:M&oacute;dulo_Expediciones";
llxHeader("", $title, $help_url, '', 0, 0, '', '', '', 'mod-shipping page-card_messaging');

$head = shipping_prepare_head($object);

print dol_get_fiche_head($head, 'agenda', $langs->trans("Shipment"), -1, $object->picto);


// Shipping card

if (!empty($_SESSION['pageforbacktolist']) && !empty($_SESSION['pageforbacktolist']['expedition'])) {
	$tmpurl = $_SESSION['pageforbacktolist']['expedition'];
	$tmpurl = preg_replace('/__SOCID__/', (string) $object->socid, $tmpurl);
	$linkback = '<a href="' . $tmpurl . (preg_match('/\?/', $tmpurl) ? '&' : '?') . 'restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
} else {
	$linkback = '<a href="' . DOL_URL_ROOT . '/expedition/list.php?restore_lastsearch_values=1">' . $langs->trans("BackToList") . '</a>';
}

$morehtmlref = '<div class="refidno">';
// Ref
$morehtmlref .= $object->ref;
// Thirdparty
if (!empty($object->thirdparty->id) && $object->thirdparty->id > 0) {
	$morehtmlref .= '<br>' . $object->thirdparty->getNomUrl(1, 'shipping');
}
$morehtmlref .= '</div>';

// Define a complementary filter for search of next/prev ref.
$object->next_prev_filter = ''; // Placeholder

dol_banner_tab($object, 'ref', $linkback, 1, 'ref', 'ref', $morehtmlref);


print '<div class="fichecenter">';
print '<div class="underbanner clearboth"></div>';

dol_print_object_info($object, 1);

print '</div>';

print '<div class="clearboth"></div>';

print dol_get_fiche_end();


// Actions buttons

$out = '';
$permok = $user->hasRight('agenda', 'myactions', 'create');
if ($permok) {
	$out .= '&shippingid=' . $object->id;
}



if (!empty($object->id)) {
	print '<br>';

	$morehtmlright = '';

	// Show link to change view in message
	$messagingUrl = DOL_URL_ROOT . '/expedition/messaging.php?id=' . $object->id;
	$morehtmlright .= dolGetButtonTitle($langs->trans('ShowAsConversation'), '', 'fa fa-comments imgforviewmode', $messagingUrl, '', 2); // Status 2 for "current page"

	// Show link to change view in agenda
	$messagingUrl = DOL_URL_ROOT . '/expedition/agenda.php?id=' . $object->id;
	$morehtmlright .= dolGetButtonTitle($langs->trans('MessageListViewType'), '', 'fa fa-bars imgforviewmode', $messagingUrl, '', 1); // Status 1 for "not current page"


	// Show link to add event
	if (isModEnabled('agenda')) {
		$addActionBtnRight = $user->hasRight('agenda', 'myactions', 'create') || $user->hasRight('agenda', 'allactions', 'create');
		$morehtmlright .= dolGetButtonTitle($langs->trans('AddAction'), '', 'fa fa-plus-circle', DOL_URL_ROOT . '/comm/action/card.php?action=create' . $out . '&socid=' . $object->socid . '&backtopage=' . urlencode($_SERVER["PHP_SELF"] . '?id=' . $object->id), '', (int) $addActionBtnRight);
	}

	$param = '&id=' . $object->id;
	if (!empty($contextpage) && $contextpage != $_SERVER["PHP_SELF"]) {
		$param .= '&contextpage=' . urlencode($contextpage);
	}
	if ($limit > 0 && $limit != $conf->liste_limit) {
		$param .= '&limit=' . ((int) $limit);
	}

	require_once DOL_DOCUMENT_ROOT . '/core/lib/memory.lib.php';
	$cachekey = 'count_events_expedition_' . $object->id;
	$nbEvent = dol_getcache($cachekey);

	$titlelist = $langs->trans("ActionsOnShipping") . (is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">(' . $nbEvent . ')</span>' : '');
	if (!empty($conf->dol_optimize_smallscreen)) {
		$titlelist = $langs->trans("Actions") . (is_numeric($nbEvent) ? '<span class="opacitymedium colorblack paddingleft">(' . $nbEvent . ')</span>' : '');
	}

	print_barre_liste($titlelist, 0, $_SERVER["PHP_SELF"], '', $sortfield, $sortorder, '', 0, -1, '', 0, $morehtmlright, '', 0, 1, 0);

	// List of all actions
	$filters = array();
	$filters['search_agenda_label'] = $search_agenda_label;
	$filters['search_rowid'] = $search_rowid;

	// This function needs to be able to handle an 'expedition' object. You might need to adapt it or create a new one.
	show_actions_messaging($conf, $langs, $db, $object, null, 0, $actioncode, '', $filters, $sortfield, $sortorder);
}

// End of page
llxFooter();
$db->close();
