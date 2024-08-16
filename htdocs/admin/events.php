<?php
/* Copyright (C) 2008-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013	   Juanjo Menent		<jmenent@2byte.es>
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
 *	    \file       htdocs/admin/events.php
 *      \ingroup    core
 *      \brief      Log event setup page
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/agenda.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';


if (!$user->admin) {
	accessforbidden();
}

// Load translation files required by the page
$langs->loadLangs(array("users", "admin", "other"));

$action = GETPOST('action', 'aZ09');
$contextpage = GETPOST('contextpage', 'aZ') ? GETPOST('contextpage', 'aZ') : 'auditeventslist'; // To manage different context of search
$optioncss = GETPOST('optioncss', 'aZ'); // Option for the css output (always '' except when 'print')

// Load variable for pagination
$limit = GETPOSTINT('limit') ? GETPOSTINT('limit') : $conf->liste_limit;
$sortfield = GETPOST('sortfield', 'aZ09comma');
$sortorder = GETPOST('sortorder', 'aZ09comma');
$page = GETPOSTISSET('pageplusone') ? (GETPOSTINT('pageplusone') - 1) : GETPOSTINT("page");
if (empty($page) || $page < 0 || GETPOST('button_search', 'alpha') || GETPOST('button_removefilter', 'alpha')) {
	$page = 0;
}     // If $page is not defined, or '' or -1 or if we click on clear filters
$offset = $limit * $page;
$pageprev = $page - 1;
$pagenext = $page + 1;

$securityevent = new Events($db);
$eventstolog = $securityevent->eventstolog;


/*
 *	Actions
 */

if ($action == "save") {
	$i = 0;

	$db->begin();

	foreach ($eventstolog as $key => $arr) {
		$param = 'MAIN_LOGEVENTS_'.$arr['id'];
		if (GETPOST($param, 'alphanohtml')) {
			dolibarr_set_const($db, $param, GETPOST($param, 'alphanohtml'), 'chaine', 0, '', $conf->entity);
		} else {
			dolibarr_del_const($db, $param, $conf->entity);
		}
	}

	$db->commit();
	setEventMessages($langs->trans("SetupSaved"), null, 'mesgs');
}



/*
 * View
 */

$form = new Form($db);

$varpage = empty($contextpage) ? $_SERVER["PHP_SELF"] : $contextpage;
$selectedfields = '';
$selectedfields .= $form->showCheckAddButtons('checkforselect', 1);

$wikihelp = 'EN:Setup_Security|FR:Paramétrage_Sécurité|ES:Configuración_Seguridad';
llxHeader('', $langs->trans("Audit"), $wikihelp, '', 0, 0, '', '', '', 'mod-admin page-events');

//$linkback='<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("SecuritySetup"), '', 'title_setup');

print '<span class="opacitymedium">'.$langs->trans("LogEventDesc", $langs->transnoentitiesnoconv("AdminTools"), $langs->transnoentitiesnoconv("Audit"))."</span><br>\n";
print "<br>\n";


print '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="save">';

$head = security_prepare_head();

print dol_get_fiche_head($head, 'audit', '', -1);

print '<br>';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print getTitleFieldOfList("TrackableSecurityEvents", 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, '')."\n";
print getTitleFieldOfList($selectedfields, 0, $_SERVER["PHP_SELF"], '', '', '', '', $sortfield, $sortorder, 'center maxwidthsearch ')."\n";
print '</tr>'."\n";
// Loop on each event type
foreach ($eventstolog as $key => $arr) {
	if ($arr['id']) {
		print '<tr class="oddeven">';
		print '<td>'.$arr['id'].'</td>';
		print '<td class="center">';
		$key = 'MAIN_LOGEVENTS_'.$arr['id'];
		$value = getDolGlobalString($key);
		print '<input class="oddeven checkforselect" type="checkbox" name="'.$key.'" value="1"'.($value ? ' checked' : '').'>';
		print '</td></tr>'."\n";
	}
}
print '</table>';

print '<div class="center">';
print '<input type="submit" name="save" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print dol_get_fiche_end();

print "</form>\n";


$s = $langs->trans("SeeReportPage", '{s1}'.$langs->transnoentities("Home").' - '.$langs->transnoentities("AdminTools").' - '.$langs->transnoentities("Audit").'{s2}');
print str_replace('{s2}', '</a>', str_replace('{s1}', '<a href="'.DOL_URL_ROOT.'/admin/tools/listevents.php" target="_blank">', $s));



// End of page
llxFooter();
$db->close();
