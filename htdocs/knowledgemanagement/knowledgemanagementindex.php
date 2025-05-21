<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2024       Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2025       Charlene Benke         <charlene@patas-monkey.com>
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
 *	\file       htdocs/knowledgemanagement/knowledgemanagementindex.php
 *	\ingroup    knowledgemanagement
 *	\brief      Home page of knowledgemanagement top menu
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/knowledgemanagement/class/knowledgerecord.class.php';

/**
 * @var Conf $conf
 * @var DoliDB $db
 * @var HookManager $hookmanager
 * @var Translate $langs
 * @var User $user
 */

// Load translation files required by the page
$langs->loadLangs(array("knowledgemanagement"));

$action = GETPOST('action', 'aZ09');


// Security check
if ( !$user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')) {
	accessforbidden('Not enough permissions');
}
$socid = GETPOSTINT('socid');
if (!empty($user->socid) && $user->socid > 0) {
	$action = '';
	$socid = $user->socid;
}

$max = 5;
$now = dol_now();
$nowarray = dol_getdate(dol_now(), true);
$nowyear = $nowarray['year'];
$year = GETPOSTINT('year') > 0 ? GETPOSTINT('year') : $nowyear;
$startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;

/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$object = new KnowledgeRecord($db);

llxHeader("", $langs->trans("KnowledgeManagementArea"), '', '', 0, 0, '', '', '', 'mod-knowledgemanagement page-card_dashboard');

print load_fiche_titre($langs->trans("KnowledgeManagementArea"), '', 'knowledgemanagement.png@knowledgemanagement');

$dir = '';
$prefix = '';
$filenamenb = $dir."/".$prefix."knowlegdeinyear-".$endyear.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=knowledgemanagement&amp;file=knowlegdeinyear-'.$endyear.'.png';


$param_year = 'DOLUSERCOOKIE_knowledge_by_status_year';
$param_shownb = 'DOLUSERCOOKIE_knowledge_by_status_shownb';
$param_showtot = 'DOLUSERCOOKIE_knowledge_by_status_showtot';
$autosetarray = preg_split("/[,;:]+/", GETPOST('DOL_AUTOSET_COOKIE'));
$showtot = 0;
if (in_array('DOLUSERCOOKIE_knowledge_by_status', $autosetarray)) {
	$endyear = GETPOSTINT($param_year);
	$shownb = GETPOST($param_shownb, 'alpha');
	$showtot = GETPOST($param_showtot, 'alpha');
} elseif (!empty($_COOKIE['DOLUSERCOOKIE_knowledge_by_status'])) {
	$tmparray = json_decode($_COOKIE['DOLUSERCOOKIE_knowledge_by_status'], true);
	$endyear = $tmparray['year'];
	$shownb = empty($tmparray['shownb']) ? 0 : $tmparray['shownb'];
	$showtot = empty($tmparray['showtot']) ? 0 : $tmparray['showtot'];
}
if (empty($shownb) && empty($showtot)) {
	$showtot = 1;
	$shownb = 0;
}

if (empty($endyear)) {
	$endyear = $nowarray['year'];
}

$startyear = $endyear - 1;

// Change default WIDTH and HEIGHT (we need a smaller than default for both desktop and smartphone)
$WIDTH = (($shownb && $showtot) || !empty($conf->dol_optimize_smallscreen)) ? '100%' : '80%';
if (empty($conf->dol_optimize_smallscreen)) {
	$HEIGHT = '200';
} else {
	$HEIGHT = '160';
}


print '<div class="fichecenter"><div class="fichethirdleft">';

/*
 * Statistics area
 */
$tick = array(
	'draft' => 0,
	'validated' => 0,
	'canceled' => 0,
);

$sql = "SELECT t.status, COUNT(t.status) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";

$sql .= ' WHERE t.entity IN ('.getEntity($object->table_element).')';
$sql .= dolSqlDateFilter('date_creation', 0, 0, $endyear);



$sql .= " GROUP BY t.status";
$dataseries = array();
$result = $db->query($sql);
if ($result) {
	while ($objp = $db->fetch_object($result)) {
		$found = 0;
		if ($objp->status == KnowledgeRecord::STATUS_DRAFT) {
			$tick['draft'] = $objp->nb;
		}
		if ($objp->status == KnowledgeRecord::STATUS_VALIDATED) {
			$tick['validated'] = $objp->nb;
		}
		if ($objp->status == KnowledgeRecord::STATUS_CANCELED) {
			$tick['canceled'] = $objp->nb;
		}
	}

	include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';	// This define $badgeStatusX

	$colorseries = array();

	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->LibStatut(KnowledgeRecord::STATUS_DRAFT,1)), 'data' => round($tick['draft']));
	$colorseries[KnowledgeRecord::STATUS_DRAFT] = '-'.$badgeStatus0;
	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->LibStatut(KnowledgeRecord::STATUS_CANCELED,1)), 'data' => round($tick['canceled']));
	$colorseries[KnowledgeRecord::STATUS_CANCELED] = $badgeStatus9;
	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->LibStatut(KnowledgeRecord::STATUS_VALIDATED,1)), 'data' => round($tick['validated']));
	$colorseries[KnowledgeRecord::STATUS_VALIDATED] = $badgeStatus6;
} else {
	dol_print_error($db);
}




$stringtoshow = '<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("#idsubimgDOLUSERCOOKIE_knowledge_by_status").click(function() {
            jQuery("#idfilterDOLUSERCOOKIE_knowledge_by_status").toggle();
        });
    });
    </script>';
$stringtoshow .= '<div class="center hideobject" id="idfilterDOLUSERCOOKIE_knowledge_by_status">'; // hideobject is to start hidden
$stringtoshow .= '<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
$stringtoshow .= '<input type="hidden" name="token" value="'.newToken().'">';
$stringtoshow .= '<input type="hidden" name="action" value="refresh">';
$stringtoshow .= '<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_knowledge_by_status:year,shownb,showtot">';
$stringtoshow .= $langs->trans("Year").' <input class="flat" size="4" type="text" name="'.$param_year.'" value="'.$endyear.'">';
$stringtoshow .= '<input type="image" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"), 'refresh.png', '', 0, 1).'">';
$stringtoshow .= '</form>';
$stringtoshow .= '</div>';

if ($user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre" ><th colspan=2>'.$langs->trans("Statistics").' '.$endyear.' '.img_picto('', 'filter.png', 'id="idsubimgDOLUSERCOOKIE_knowledge_by_status" class="linkobject"').'</th></tr>';

	print '<tr><td class="center" colspan="2">';
	print $stringtoshow;
	// don't display graph if no series
	if (!empty($dataseries) && count($dataseries) > 1) {
		$totalnb = 0;
		foreach ($dataseries as $key => $value) {
			$totalnb += $value['data'];
		}

		$data = array();
		foreach ($dataseries as $key => $value) {
			$data[] = array($value['label'], $value['data']);
		}
		$px1 = new DolGraph();
		$mesg = $px1->isGraphKo();

		if (!$mesg) {
			$px1->SetData($data);
			$px1->SetDataColor(array_values($colorseries));

			unset($data1);
			$i = $startyear;
			$legend = array();
			while ($i <= $endyear) {
				$legend[] = $i;
				$i++;
			}
			$px1->setShowLegend(2);
			$px1->SetType(array('pie'));
			$px1->SetLegend($legend);
			$px1->SetMaxValue($px1->GetCeilMaxValue());
			//$px1->SetWidth($WIDTH);
			$px1->SetHeight($HEIGHT);
			$px1->SetYLabel($langs->trans("KnowledgeStatByStatus"));
			$px1->SetShading(3);
			$px1->SetHorizTickIncrement(1);
			$px1->SetCssPrefix("cssboxes");
			$px1->mode = 'depth';
			//$px1->SetTitle($langs->trans("TicketStatByStatus"));

			$px1->draw($filenamenb, $fileurlnb);
			print $px1->show($totalnb ? 0 : 1);
		} else {
			print '<div class="error">'.$mesg.'</div>';
		}

	}
	print '</td></tr>';
	print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td class="right">'.$totalnb.'</td></tr>';
	print "</table></div><br>";

}





$sql = 'SELECT ';
$sql .= $object->getFieldList('t');
$sql = "SELECT t.rowid, t.ref, t.question, t.lang, t.date_creation, t.status";
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
$sql.= " WHERE t.status = 0";
$sql .= " AND t.entity IN (".getEntity($object->element).")";


$resql = $db->query($sql);
if ($resql) {
	$total = 0;
	$num = $db->num_rows($resql);

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="3">'.$langs->trans("DraftKnowledgeManagement").($num?'<span class="badge marginleftonlyshort">'.$num.'</span>':'').'</th></tr>';

	if ($num > 0) {
		$i = 0;
		while ($i < $num) {

			$obj = $db->fetch_object($resql);
			print '<tr class="oddeven"><td class="nowrap">';
			$object->id=$obj->rowid;
			$object->ref=$obj->ref;
			print $object->getNomUrl(1);
			print '</td>';
			print '<td class="nowrap">';
			print '</td>';
			print '<td class="left" class="nowrap">'.$obj->question.'</td></tr>';
			$i++;

		}
	} else {

		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoKnowledgeRecord").'</td></tr>';
	}
	print "</table><br>";

	$db->free($resql);
} else {
	dol_print_error($db);
}



print '</div><div class="fichetwothirdright">';


$NBMAX = (!getDolGlobalString('MAIN_SIZE_SHORTLIST_LIMIT') ? 25 : $conf->global->MAIN_SIZE_SHORTLIST_LIMIT);
$max = $NBMAX;

// Last modified myobject

$sql = "SELECT t.rowid, t.ref, t.question, t.lang, t.tms, t.status";
$sql .= " FROM ".MAIN_DB_PREFIX.$object->table_element." as t";
//if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE t.entity IN (".getEntity($object->element).")";
//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
//if ($socid)	$sql.= " AND s.rowid = $socid";
$sql .= " ORDER BY t.tms DESC";
$sql .= $db->plimit($max, 0);

$resql = $db->query($sql);
if ($resql) {
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre">';
	print '<th colspan="2">';
	print $langs->trans("BoxTitleLatestModifiedMyObjects", $max);
	print '</th>';
	print '<th class="right">'.$langs->trans("DateModificationShort").'</th>';
	print '</tr>';
	if ($num) {
		while ($i < $num) {
			$objp = $db->fetch_object($resql);

			$object->id=$objp->rowid;
			$object->ref=$objp->ref;
			$object->status = $objp->status;

			print '<tr class="oddeven">';
			print '<td class="nowrap">'.$object->getNomUrl(1).'</td>';
			print '<td class="right nowrap">';
			print "</td>";
			print '<td class="right nowrap">'.dol_print_date($db->jdate($objp->tms), 'day')."</td>";
			print '</tr>';
			$i++;
		}

		$db->free($resql);
	} else {
		print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("None").'</td></tr>';
	}
	print "</table><br>";
}

print '</div></div>';

// End of page
llxFooter();
$db->close();
