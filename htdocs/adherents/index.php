<?php
/* Copyright (C) 2001-2002	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2003		Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (C) 2004-2020	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2019       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2021		Frédéric France			<frederic.france@netlgic.fr>
 * Copyright (C) 2021       Waël Almoman            <info@almoman.com>
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

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent_type.class.php';
require_once DOL_DOCUMENT_ROOT.'/adherents/class/subscription.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('membersindex'));

// Load translation files required by the page
$langs->loadLangs(array("companies", "members"));

// Security check
$result = restrictedArea($user, 'adherent');


/*
 * Actions
 */

if (GETPOST('addbox')) {
	// Add box (when submit is done from a form when ajax disabled)
	require_once DOL_DOCUMENT_ROOT.'/core/class/infobox.class.php';
	$zone = GETPOST('areacode', 'int');
	$userid = GETPOST('userid', 'int');
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

llxHeader('', $langs->trans("Members"), 'EN:Module_Foundations|FR:Module_Adh&eacute;rents|ES:M&oacute;dulo_Miembros');

$staticmember = new Adherent($db);
$statictype = new AdherentType($db);
$subscriptionstatic = new Subscription($db);

print load_fiche_titre($langs->trans("MembersArea"), $resultboxes['selectboxlist'], 'members');

$MembersValidated = array();
$MembersToValidate = array();
$MembersUpToDate = array();
$MembersExcluded = array();
$MembersResiliated = array();

$AdherentType = array();

// Type of membership
$sql = "SELECT t.rowid, t.libelle as label, t.subscription,";
$sql .= " d.statut, count(d.rowid) as somme";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent_type as t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."adherent as d";
$sql .= " ON t.rowid = d.fk_adherent_type";
$sql .= " AND d.entity IN (".getEntity('adherent').")";
$sql .= " WHERE t.entity IN (".getEntity('member_type').")";
$sql .= " GROUP BY t.rowid, t.libelle, t.subscription, d.statut";

dol_syslog("index.php::select nb of members per type", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$objp = $db->fetch_object($resql);

		$adhtype = new AdherentType($db);
		$adhtype->id = $objp->rowid;
		$adhtype->subscription = $objp->subscription;
		$adhtype->label = $objp->label;
		$AdherentType[$objp->rowid] = $adhtype;

		if ($objp->statut == -1) {
			$MembersToValidate[$objp->rowid] = $objp->somme;
		}
		if ($objp->statut == 1) {
			$MembersValidated[$objp->rowid] = $objp->somme;
		}
		if ($objp->statut == -2) {
			$MembersExcluded[$objp->rowid] = $objp->somme;
		}
		if ($objp->statut == 0) {
			$MembersResiliated[$objp->rowid] = $objp->somme;
		}

		$i++;
	}
	$db->free($resql);
}

$now = dol_now();

// Members up to date list
// current rule: uptodate = the end date is in future whatever is type
// old rule: uptodate = if type does not need payment, that end date is null, if type need payment that end date is in future)
$sql = "SELECT count(*) as somme , d.fk_adherent_type";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
$sql .= " WHERE d.entity IN (".getEntity('adherent').")";
$sql .= " AND d.statut = 1 AND (d.datefin >= '".$db->idate($now)."' OR t.subscription = 0)";
$sql .= " AND t.rowid = d.fk_adherent_type";
$sql .= " GROUP BY d.fk_adherent_type";

dol_syslog("index.php::select nb of uptodate members by type", LOG_DEBUG);
$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num) {
		$objp = $db->fetch_object($resql);
		$MembersUpToDate[$objp->fk_adherent_type] = $objp->somme;
		$i++;
	}
	$db->free($resql);
}

/*
 * Statistics
 */

$boxgraph = '';
if ($conf->use_javascript_ajax) {
	$boxgraph .='<div class="div-table-responsive-no-min">';
	$boxgraph .='<table class="noborder nohover centpercent">';
	$boxgraph .='<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").'</th></tr>';
	$boxgraph .='<tr><td class="center" colspan="2">';

	$SumToValidate = 0;
	$SumValidated = 0;
	$SumUpToDate = 0;
	$SumResiliated = 0;
	$SumExcluded = 0;

	$total = 0;
	$dataval = array();
	$i = 0;
	foreach ($AdherentType as $key => $adhtype) {
		$dataval['draft'][] = array($i, isset($MembersToValidate[$key]) ? $MembersToValidate[$key] : 0);
		$dataval['uptodate'][] = array($i, isset($MembersUpToDate[$key]) ? $MembersUpToDate[$key] : 0);
		$dataval['notuptodate'][] = array($i, isset($MembersValidated[$key]) ? $MembersValidated[$key] - (isset($MembersUpToDate[$key]) ? $MembersUpToDate[$key] : 0) : 0);
		$dataval['excluded'][] = array($i, isset($MembersExcluded[$key]) ? $MembersExcluded[$key] : 0);
		$dataval['resiliated'][] = array($i, isset($MembersResiliated[$key]) ? $MembersResiliated[$key] : 0);

		$SumToValidate += isset($MembersToValidate[$key]) ? $MembersToValidate[$key] : 0;
		$SumValidated += isset($MembersValidated[$key]) ? $MembersValidated[$key] - (isset($MembersUpToDate[$key]) ? $MembersUpToDate[$key] : 0) : 0;
		$SumUpToDate += isset($MembersUpToDate[$key]) ? $MembersUpToDate[$key] : 0;
		$SumExcluded += isset($MembersExcluded[$key]) ? $MembersExcluded [$key] : 0;
		$SumResiliated += isset($MembersResiliated[$key]) ? $MembersResiliated[$key] : 0;
		$i++;
	}
	$total = $SumToValidate + $SumValidated + $SumUpToDate + $SumExcluded + $SumResiliated;
	$dataseries = array();
	$dataseries[] = array($langs->transnoentitiesnoconv("MembersStatusToValid"), round($SumToValidate));			// Draft, not yet validated
	$dataseries[] = array($langs->transnoentitiesnoconv("UpToDate"), round($SumUpToDate));
	$dataseries[] = array($langs->transnoentitiesnoconv("OutOfDate"), round($SumValidated));
	$dataseries[] = array($langs->transnoentitiesnoconv("MembersStatusExcluded"), round($SumExcluded));
	$dataseries[] = array($langs->transnoentitiesnoconv("MembersStatusResiliated"), round($SumResiliated));

	include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

	include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
	$dolgraph = new DolGraph();
	$dolgraph->SetData($dataseries);
	$dolgraph->SetDataColor(array('-'.$badgeStatus0, $badgeStatus4, '-'.$badgeStatus1, '-'.$badgeStatus8, $badgeStatus6));
	$dolgraph->setShowLegend(2);
	$dolgraph->setShowPercent(1);
	$dolgraph->SetType(array('pie'));
	$dolgraph->setHeight('200');
	$dolgraph->draw('idgraphstatus');
	$boxgraph .=$dolgraph->show($total ? 0 : 1);

	$boxgraph .= '</td></tr>';
	$boxgraph .= '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">';
	$boxgraph .= $SumToValidate + $SumValidated + $SumUpToDate + $SumExcluded + $SumResiliated;
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
