<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo			<jlb@j1b.org>
 * Copyright (C) 2004-2020	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2019		Nicolas ZABOURI				<info@inovea-conseil.com>
 * Copyright (C) 2021-2023	Frédéric France				<frederic.france@netlgic.fr>
 * Copyright (C) 2021-2023	Waël Almoman				<info@almoman.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *       \file       htdocs/adherents/index.php
 *       \ingroup    member
 *       \brief      Home page of membership module
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';


// Load translation files required by the page
$langs->loadLangs(array("companies", "members"));


$hookmanager = new HookManager($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('membersindex'));


// Security check
$result = restrictedArea($user, 'adherent');


/*
 * Actions
 */

$userid = GETPOSTINT('userid');
if (GETPOST('addbox')) {
	// Add box (when submit is done from a form when ajax disabled)
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone = GETPOSTINT('areacode');
	$boxorder = GETPOST('boxorder', 'aZ09');
	$boxorder .= GETPOST('boxcombo', 'aZ09');
	$result = InfoBox::saveboxorder($db, $zone, $boxorder, $userid);
	if ($result > 0) {
		setEventMessages($langs->trans("BoxAdded"), null);
	}
}


/*
 * View
 */

$form = new Form($db);

// Load $resultboxes (selectboxlist + boxactivated + boxlista + boxlistb)
$resultboxes = FormOther::getBoxesArea($user, "2");

$title = $langs->trans("Members");
$help_url = 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros|DE:Modul_Mitglieder';

llxHeader('', $title, $help_url, '', 0, 0, '', '', '', 'mod-member page-index');

$staticmember = new Adherent($db);
$statictype = new AdherentType($db);
$subscriptionstatic = new Subscription($db);

print load_fiche_titre($langs->trans("MembersArea"), $resultboxes['selectboxlist'], 'members');

/*
 * Statistics
 */

$boxgraph = '';
if ($conf->use_javascript_ajax) {
	$year = idate('Y');
	$numberyears = getDolGlobalInt("MAIN_NB_OF_YEAR_IN_MEMBERSHIP_WIDGET_GRAPH");

	$boxgraph .= '<div class="div-table-responsive-no-min">';
	$boxgraph .= '<table class="noborder nohover centpercent">';
	$boxgraph .= '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").($numberyears ? ' ('.($year - $numberyears).' - '.$year.')' : '').'</th></tr>';
	$boxgraph .= '<tr><td class="center" colspan="2">';

	require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherentstats.class.php';
	$stats = new AdherentStats($db, 0, $userid);

	// Show array
	$sumMembers = $stats->countMembersByTypeAndStatus($numberyears);
	if (is_array($sumMembers) && !empty($sumMembers)) {
		$total = $sumMembers['total']['members_draft'] + $sumMembers['total']['members_pending'] + $sumMembers['total']['members_uptodate'] + $sumMembers['total']['members_expired'] + $sumMembers['total']['members_excluded'] + $sumMembers['total']['members_resiliated'];
	} else {
		$total = 0;
	}
	foreach (array('members_draft', 'members_pending', 'members_uptodate', 'members_expired', 'members_excluded', 'members_resiliated') as $val) {
		if (empty($sumMembers['total'][$val])) {
			$sumMembers['total'][$val] = 0;
		}
	}

	$dataseries = array();
	$dataseries[] = array($langs->transnoentitiesnoconv("MembersStatusToValid"), $sumMembers['total']['members_draft']);			// Draft, not yet validated
	$dataseries[] = array($langs->transnoentitiesnoconv("WaitingSubscription"), $sumMembers['total']['members_pending']);
	$dataseries[] = array($langs->transnoentitiesnoconv("UpToDate"), $sumMembers['total']['members_uptodate']);
	$dataseries[] = array($langs->transnoentitiesnoconv("OutOfDate"), $sumMembers['total']['members_expired']);
	$dataseries[] = array($langs->transnoentitiesnoconv("MembersStatusExcluded"), $sumMembers['total']['members_excluded']);
	$dataseries[] = array($langs->transnoentitiesnoconv("MembersStatusResiliated"), $sumMembers['total']['members_resiliated']);

	include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

	include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
	$dolgraph = new DolGraph();
	$dolgraph->SetData($dataseries);
	$dolgraph->SetDataColor(array('-'.$badgeStatus0, $badgeStatus1, $badgeStatus4, $badgeStatus8, '-'.$badgeStatus8, $badgeStatus6));
	$dolgraph->setShowLegend(2);
	$dolgraph->setShowPercent(1);
	$dolgraph->SetType(array('pie'));
	$dolgraph->setHeight('200');
	$dolgraph->draw('idgraphstatus');
	$boxgraph .= $dolgraph->show($total ? 0 : 1);

	$boxgraph .= '</td></tr>';
	$boxgraph .= '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">';
	$boxgraph .= $total;
	$boxgraph .= '</td></tr>';
	$boxgraph .= '</table>';
	$boxgraph .= '</div>';
	$boxgraph .= '<br>';
}

// boxes
print '<div class="clearboth"></div>';
print '<div class="fichecenter fichecenterbis">';

print '<div class="twocolumns">';

print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';

print $boxgraph;

print $resultboxes['boxlista'];

print '</div>'."\n";

print '<div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';

print $resultboxes['boxlistb'];

print '</div>'."\n";

print '</div>';
print '</div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardMembers', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter();
$db->close();
