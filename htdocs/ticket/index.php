<?php
/* Copyright (C) 2013-2016  Jean-François FERRY     <hello@librethic.io>
 * Copyright (C) 2019       Nicolas ZABOURI         <info@inovea-conseil.com>
 * Copyright (C) 2021-2024	Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
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
 *    \file       htdocs/ticket/index.php
 *    \ingroup    ticket
 */

// Load Dolibarr environment
require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formother.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticketstats.class.php';


$hookmanager = new HookManager($db);

// Initialize a technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('ticketsindex'));

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket'));

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

// Get parameters
$id = GETPOSTINT('id');
$msg_id = GETPOSTINT('msg_id');

$action = GETPOST('action', 'aZ09');

$socid = 0;
if ($user->socid) {
	$socid = $user->socid;
}
$userid = $user->id;

$nowarray = dol_getdate(dol_now(), true);
$nowyear = $nowarray['year'];
$year = GETPOSTINT('year') > 0 ? GETPOSTINT('year') : $nowyear;
$startyear = $year - (!getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS') ? 2 : max(1, min(10, getDolGlobalString('MAIN_STATS_GRAPHS_SHOW_N_YEARS'))));
$endyear = $year;

// Initialize objects
$object = new Ticket($db);

// Security check
//$result = restrictedArea($user, 'ticket|knowledgemanagement', 0, '', '', '', '');
if (!$user->hasRight('ticket', 'read') && !$user->hasRight('knowledgemanagement', 'knowledgerecord', 'read')) {
	accessforbidden('Not enough permissions');
}

$max = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT', 5);


/*
 * Actions
 */

// None



/*
 * View
 */

$resultboxes = FormOther::getBoxesArea($user, "11"); // Load $resultboxes (selectboxlist + boxactivated + boxlista + boxlistb)

$help_url = '';
llxHeader('', $langs->trans('TicketsIndex'), $help_url, '', 0, 0, '', '', '', 'mod-ticket page-dashboard');

$linkback = '';
print load_fiche_titre($langs->trans('TicketsIndex'), $resultboxes['selectboxlist'], 'ticket');


$dir = '';
$prefix = '';
$filenamenb = $dir."/".$prefix."ticketinyear-".$endyear.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=ticket&amp;file=ticketinyear-'.$endyear.'.png';

$stats = new TicketStats($db, $socid, $userid);
$param_year = 'DOLUSERCOOKIE_ticket_by_status_year';
$param_shownb = 'DOLUSERCOOKIE_ticket_by_status_shownb';
$param_showtot = 'DOLUSERCOOKIE_ticket_by_status_showtot';
$autosetarray = preg_split("/[,;:]+/", GETPOST('DOL_AUTOSET_COOKIE'));
if (in_array('DOLUSERCOOKIE_ticket_by_status', $autosetarray)) {
	$endyear = GETPOSTINT($param_year);
	$shownb = GETPOST($param_shownb, 'alpha');
	$showtot = GETPOST($param_showtot, 'alpha');
} elseif (!empty($_COOKIE['DOLUSERCOOKIE_ticket_by_status'])) {
	$tmparray = json_decode($_COOKIE['DOLUSERCOOKIE_ticket_by_status'], true);
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

print '<div class="clearboth"></div>';
print '<div class="fichecenter fichecenterbis">';

print '<div class="twocolumns">';

print '<div class="firstcolumn fichehalfleft boxhalfleft" id="boxhalfleft">';

/*
 * Statistics area
 */
$tick = array(
	'unread' => 0,
	'read' => 0,
	'needmoreinfo' => 0,
	'answered' => 0,
	'assigned' => 0,
	'inprogress' => 0,
	'waiting' => 0,
	'closed' => 0,
	'canceled' => 0,
	'deleted' => 0,
);

$sql = "SELECT t.fk_statut, COUNT(t.fk_statut) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= ' WHERE t.entity IN ('.getEntity('ticket').')';
$sql .= dolSqlDateFilter('datec', 0, 0, $endyear);

if (!$user->hasRight('societe', 'client', 'voir')) {
	$sql .= " AND t.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
}

// External users restriction
if ($user->socid > 0) {
	$sql .= " AND t.fk_soc= ".((int) $user->socid);
} else {
	// For internals users,
	if (getDolGlobalString('TICKET_LIMIT_VIEW_ASSIGNED_ONLY') && !$user->hasRight('ticket', 'manage')) {
		$sql .= " AND t.fk_user_assign = ".((int) $user->id);
	}
}
$sql .= " GROUP BY t.fk_statut";

$result = $db->query($sql);
if ($result) {
	while ($objp = $db->fetch_object($result)) {
		$found = 0;
		if ($objp->fk_statut == Ticket::STATUS_NOT_READ) {
			$tick['unread'] = $objp->nb;
		}
		if ($objp->fk_statut == Ticket::STATUS_READ) {
			$tick['read'] = $objp->nb;
		}
		if ($objp->fk_statut == Ticket::STATUS_NEED_MORE_INFO) {
			$tick['needmoreinfo'] = $objp->nb;
		}
		if ($objp->fk_statut == Ticket::STATUS_ASSIGNED) {
			$tick['assigned'] = $objp->nb;
		}
		if ($objp->fk_statut == Ticket::STATUS_IN_PROGRESS) {
			$tick['inprogress'] = $objp->nb;
		}
		if ($objp->fk_statut == Ticket::STATUS_WAITING) {
			$tick['waiting'] = $objp->nb;
		}
		if ($objp->fk_statut == Ticket::STATUS_CLOSED) {
			$tick['closed'] = $objp->nb;
		}
		if ($objp->fk_statut == Ticket::STATUS_CANCELED) {
			$tick['canceled'] = $objp->nb;
		}
	}

	include DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';	// This define $badgeStatusX

	$dataseries = array();
	$colorseries = array();

	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->labelStatusShort[Ticket::STATUS_NOT_READ]), 'data' => round($tick['unread']));
	$colorseries[Ticket::STATUS_NOT_READ] = '-'.$badgeStatus0;
	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->labelStatusShort[Ticket::STATUS_READ]), 'data' => round($tick['read']));
	$colorseries[Ticket::STATUS_READ] = $badgeStatus1;
	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->labelStatusShort[Ticket::STATUS_ASSIGNED]), 'data' => round($tick['assigned']));
	$colorseries[Ticket::STATUS_ASSIGNED] = $badgeStatus3;
	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->labelStatusShort[Ticket::STATUS_IN_PROGRESS]), 'data' => round($tick['inprogress']));
	$colorseries[Ticket::STATUS_IN_PROGRESS] = $badgeStatus4;
	if (getDolGlobalString('TICKET_INCLUDE_SUSPENDED_STATUS')) {
		$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->labelStatusShort[Ticket::STATUS_WAITING]), 'data' => round($tick['waiting']));
		$colorseries[Ticket::STATUS_WAITING] = '-'.$badgeStatus4;
	}
	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->labelStatusShort[Ticket::STATUS_NEED_MORE_INFO]), 'data' => round($tick['needmoreinfo']));
	$colorseries[Ticket::STATUS_NEED_MORE_INFO] = '-'.$badgeStatus3;
	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->labelStatusShort[Ticket::STATUS_CANCELED]), 'data' => round($tick['canceled']));
	$colorseries[Ticket::STATUS_CANCELED] = $badgeStatus9;
	$dataseries[] = array('label' => $langs->transnoentitiesnoconv($object->labelStatusShort[Ticket::STATUS_CLOSED]), 'data' => round($tick['closed']));
	$colorseries[Ticket::STATUS_CLOSED] = $badgeStatus6;
} else {
	dol_print_error($db);
}

$stringtoshow = '<script type="text/javascript">
    jQuery(document).ready(function() {
        jQuery("#idsubimgDOLUSERCOOKIE_ticket_by_status").click(function() {
            jQuery("#idfilterDOLUSERCOOKIE_ticket_by_status").toggle();
        });
    });
    </script>';
$stringtoshow .= '<div class="center hideobject" id="idfilterDOLUSERCOOKIE_ticket_by_status">'; // hideobject is to start hidden
$stringtoshow .= '<form class="flat formboxfilter" method="POST" action="'.$_SERVER["PHP_SELF"].'">';
$stringtoshow .= '<input type="hidden" name="token" value="'.newToken().'">';
$stringtoshow .= '<input type="hidden" name="action" value="refresh">';
$stringtoshow .= '<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_ticket_by_status:year,shownb,showtot">';
$stringtoshow .= $langs->trans("Year").' <input class="flat" size="4" type="text" name="'.$param_year.'" value="'.$endyear.'">';
$stringtoshow .= '<input type="image" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"), 'refresh.png', '', 0, 1).'">';
$stringtoshow .= '</form>';
$stringtoshow .= '</div>';

if ($user->hasRight('ticket', 'read')) {
	print '<div class="div-table-responsive-no-min">';
	print '<table class="noborder centpercent">';
	print '<tr class="liste_titre"><th >'.$langs->trans("Statistics").' '.$endyear.' '.img_picto('', 'filter.png', 'id="idsubimgDOLUSERCOOKIE_ticket_by_status" class="linkobject"').'</th></tr>';

	print '<tr><td class="center">';
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
			$px1->SetYLabel($langs->trans("TicketStatByStatus"));
			$px1->SetShading(3);
			$px1->SetHorizTickIncrement(1);
			$px1->SetCssPrefix("cssboxes");
			$px1->mode = 'depth';
			//$px1->SetTitle($langs->trans("TicketStatByStatus"));

			$px1->draw($filenamenb, $fileurlnb);
			print $px1->show($totalnb ? 0 : 1);
		}
	}
	print '</td></tr>';

	print '</table>';
	print '</div>';
}

if ($user->hasRight('ticket', 'read')) {
	// Build graphic number of object
	$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);

	print '<br>'."\n";
}

print $resultboxes['boxlista'];

print '</div>'."\n";

print '<div class="secondcolumn fichehalfright boxhalfright" id="boxhalfright">';

if ($user->hasRight('ticket', 'read')) {
	/*
	 * Latest unread tickets
	 */

	$sql = "SELECT t.rowid, t.ref, t.track_id, t.datec, t.subject, t.type_code, t.category_code, t.severity_code, t.fk_statut as status, t.progress,";
	$sql .= " type.code as type_code, type.label as type_label,";
	$sql .= " category.code as category_code, category.label as category_label,";
	$sql .= " severity.code as severity_code, severity.label as severity_label";
	$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_type as type ON type.code=t.type_code";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_category as category ON category.code=t.category_code";
	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_severity as severity ON severity.code=t.severity_code";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	}

	$sql .= ' WHERE t.entity IN ('.getEntity('ticket').')';
	$sql .= " AND t.fk_statut = 0";
	if (!$user->hasRight('societe', 'client', 'voir')) {
		$sql .= " AND t.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
	}

	if ($user->socid > 0) {
		$sql .= " AND t.fk_soc= ".((int) $user->socid);
	} else {
		// Restricted to assigned user only
		if (getDolGlobalString('TICKET_LIMIT_VIEW_ASSIGNED_ONLY') && !$user->hasRight('ticket', 'manage')) {
			$sql .= " AND t.fk_user_assign = ".((int) $user->id);
		}
	}
	$sql .= $db->order("t.datec", "DESC");
	$sql .= $db->plimit($max, 0);

	//print $sql;
	$result = $db->query($sql);
	if ($result) {
		$num = $db->num_rows($result);

		$i = 0;

		$tmpmax = getDolGlobalInt('MAIN_SIZE_SHORTLIST_LIMIT_LAST_MODIFIED_TICKETS', $max);
		$transRecordedType = $langs->trans("LatestNewTickets", $tmpmax);

		print '<div class="div-table-responsive-no-min">';
		print '<table class="noborder centpercent">';
		print '<tr class="liste_titre"><th colspan="5">'.$transRecordedType;
		print '<a href="'.DOL_URL_ROOT.'/ticket/list.php?search_fk_statut[]='.Ticket::STATUS_NOT_READ.'" title="'.$langs->trans("FullList").'">';
		print '<span class="badge marginleftonlyshort">...</span>';
		//print $langs->trans("FullList")
		print '</a>';
		print '</th>';
		print '<th>';
		print '</th>';
		print '<th>';
		print '</th>';
		print '</tr>';
		if ($num > 0) {
			while ($i < $num) {
				$objp = $db->fetch_object($result);

				$object->id = $objp->rowid;
				$object->ref = $objp->ref;
				$object->track_id = $objp->track_id;
				$object->status = $objp->status;
				$object->progress = $objp->progress;
				$object->subject = $objp->subject;

				print '<tr class="oddeven">';

				// Ref
				print '<td class="nowraponall">';
				print $object->getNomUrl(1);
				print "</td>\n";

				// Creation date
				print '<td class="center nowraponall">';
				print dol_print_date($db->jdate($objp->datec), 'dayhour');
				print "</td>";

				// Subject
				print '<td class="nowrap tdoverflowmax150">';
				print '<a href="card.php?track_id='.$objp->track_id.'" title="'.dolPrintHTMLForAttribute($objp->subject).'">'.dol_trunc($objp->subject, 30).'</a>';
				print "</td>\n";

				// Type
				print '<td class="nowrap tdoverflowmax100">';
				$s = $langs->getLabelFromKey($db, 'TicketTypeShort'.$objp->type_code, 'c_ticket_type', 'code', 'label', $objp->type_code);
				print '<span title="'.dol_escape_htmltag($s).'">'.$s.'</span>';
				print '</td>';

				// Category
				print '<td class="nowrap">';
				if (!empty($objp->category_code)) {
					$s = $langs->getLabelFromKey($db, 'TicketCategoryShort'.$objp->category_code, 'c_ticket_category', 'code', 'label', $objp->category_code);
					print '<span title="'.dol_escape_htmltag($s).'">'.$s.'</span>';
				}
				//print $objp->category_label;
				print "</td>";

				// Severity = Priority
				print '<td class="nowrap" title="'.$langs->trans("Priority").'">';
				$s = $langs->getLabelFromKey($db, 'TicketSeverityShort'.$objp->severity_code, 'c_ticket_severity', 'code', 'label', $objp->severity_code);
				print '<span title="'.dol_escape_htmltag($s).'">'.$s.'</span>';
				//print $objp->severity_label;
				print "</td>";

				print '<td class="nowraponall right">';
				print $object->getLibStatut(5);
				print "</td>";

				print "</tr>\n";
				$i++;
			}

			$db->free($result);
		} else {
			print '<tr><td colspan="7"><span class="opacitymedium">'.$langs->trans('NoUnreadTicketsFound').'</span></td></tr>';
		}

		print "</table>";
		print '</div>';

		print '<br>';
	} else {
		dol_print_error($db);
	}
}

print $resultboxes['boxlistb'];

print '</div>';
print '</div>';
print '</div>';


print '<div class="clearboth"></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardTickets', $parameters, $object); // Note that $action and $object may have been modified by hook


// End of page
llxFooter();
$db->close();
