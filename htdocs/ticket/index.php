<?php
/* Copyright (C) - 2013-2016    Jean-François FERRY     <hello@librethic.io>
 * Copyright (C) - 2019         Nicolas ZABOURI         <info@inovea-conseil.com>
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
 *    \file     htdocs/ticket/agenda.php
 *    \ingroup	ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticketstats.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('ticketsindex'));

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket'));

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

// Get parameters
$id = GETPOST('id', 'int');
$msg_id = GETPOST('msg_id', 'int');

$action = GETPOST('action', 'aZ09');

if ($user->socid) {
    $socid = $user->socid;
}

// Security check
$result = restrictedArea($user, 'ticket', 0, '', '', '', '');

$nowyear = strftime("%Y", dol_now());
$year = GETPOST('year') > 0 ? GETPOST('year') : $nowyear;
//$startyear=$year-2;
$startyear = $year - 1;
$endyear = $year;

$object = new Ticket($db);


/*
 * Actions
 */

// None


/*
 * View
 */

$form = new Form($db);
$tickesupstatic = new Ticket($db);

llxHeader('', $langs->trans('TicketsIndex'), '');

$linkback = '';
print load_fiche_titre($langs->trans('TicketsIndex'), $linkback, 'ticket');


$dir = '';
$filenamenb = $dir."/".$prefix."ticketinyear-".$endyear.".png";
$fileurlnb = DOL_URL_ROOT.'/viewimage.php?modulepart=ticket&amp;file=ticketinyear-'.$endyear.'.png';

$stats = new TicketStats($db, $socid, $userid);
$param_year = 'DOLUSERCOOKIE_ticket_by_status_year';
$param_shownb = 'DOLUSERCOOKIE_ticket_by_status_shownb';
$param_showtot = 'DOLUSERCOOKIE_ticket_by_status_showtot';
$autosetarray = preg_split("/[,;:]+/", GETPOST('DOL_AUTOSET_COOKIE'));
if (in_array('DOLUSERCOOKIE_ticket_by_status', $autosetarray)) {
    $endyear = GETPOST($param_year, 'int');
    $shownb = GETPOST($param_shownb, 'alpha');
    $showtot = GETPOST($param_showtot, 'alpha');
} else {
    $tmparray = json_decode($_COOKIE['DOLUSERCOOKIE_ticket_by_status'], true);
    $endyear = $tmparray['year'];
    $shownb = $tmparray['shownb'];
    $showtot = $tmparray['showtot'];
}
if (empty($shownb) && empty($showtot)) {
    $showtot = 1;
}

$nowarray = dol_getdate(dol_now(), true);
if (empty($endyear)) {
    $endyear = $nowarray['year'];
}

$startyear = $endyear - 1;
$WIDTH = (($shownb && $showtot) || !empty($conf->dol_optimize_smallscreen)) ? '100%' : '80%';
$HEIGHT = '200';

print '<div class="fichecenter"><div class="fichethirdleft">';

/*
 * Statistics area
 */
$tick = array(
    'unread' => 0,
    'read' => 0,
    'answered' => 0,
    'assigned' => 0,
    'inprogress' => 0,
    'waiting' => 0,
    'closed' => 0,
    'deleted' => 0,
);

$sql = "SELECT t.fk_statut, COUNT(t.fk_statut) as nb";
$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
if (!$user->rights->societe->client->voir && !$socid) {
    $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}
$sql .= ' WHERE t.entity IN ('.getEntity('ticket').')';
$sql .= dolSqlDateFilter('datec', 0, 0, $endyear);

if (!$user->rights->societe->client->voir && !$socid) {
    $sql .= " AND t.fk_soc = sc.fk_soc AND sc.fk_user = ".$user->id;
}

// External users restriction
if ($user->socid > 0) {
    $sql .= " AND t.fk_soc='".$user->socid."'";
} else {
    // For internals users,
    if (!empty($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY) && !$user->rights->ticket->manage) {
        $sql .= " AND t.fk_user_assign=".$user->id;
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

    include_once DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/theme_vars.inc.php';

    $dataseries = array();
    $colorseries = array();

    $dataseries[] = array('label' => $langs->trans("Unread"), 'data' => round($tick['unread']));
    $colorseries[Ticket::STATUS_NOT_READ] = '-'.$badgeStatus0;
    $dataseries[] = array('label' => $langs->trans("Read"), 'data' => round($tick['read']));
    $colorseries[Ticket::STATUS_READ] = $badgeStatus1;
    $dataseries[] = array('label' => $langs->trans("Assigned"), 'data' => round($tick['assigned']));
    $colorseries[Ticket::STATUS_ASSIGNED] = $badgeStatus3;
    $dataseries[] = array('label' => $langs->trans("InProgress"), 'data' => round($tick['inprogress']));
    $colorseries[Ticket::STATUS_IN_PROGRESS] = $badgeStatus4;
    $dataseries[] = array('label' => $langs->trans("Suspended"), 'data' => round($tick['waiting']));
    $colorseries[Ticket::STATUS_WAITING] = '-'.$badgeStatus3;
    $dataseries[] = array('label' => $langs->trans("NeedMoreInformation"), 'data' => round($tick['needmoreinfo']));
    $colorseries[Ticket::STATUS_NEED_MORE_INFO] = $badgeStatus9;
    $dataseries[] = array('label' => $langs->trans("Canceled"), 'data' => round($tick['canceled']));
    $colorseries[Ticket::STATUS_CANCELED] = $badgeStatus9;
    $dataseries[] = array('label' => $langs->trans("Closed"), 'data' => round($tick['closed']));
    $colorseries[Ticket::STATUS_CLOSED] = $badgeStatus6;
} else {
    dol_print_error($db);
}

$stringtoshow = '<script type="text/javascript" language="javascript">
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
$stringtoshow .= '<input type="image" alt="'.$langs->trans("Refresh").'" src="'.img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1).'">';
$stringtoshow .= '</form>';
$stringtoshow .= '</div>';

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

// Build graphic number of object
$data = $stats->getNbByMonthWithPrevYear($endyear, $startyear);

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Latest tickets
 */

$max = 10;

$sql = "SELECT t.rowid, t.ref, t.track_id, t.datec, t.subject, t.type_code, t.category_code, t.severity_code, t.fk_statut, t.progress,";
$sql .= " type.label as type_label, category.label as category_label, severity.label as severity_label";
$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_type as type ON type.code=t.type_code";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_category as category ON category.code=t.category_code";
$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_severity as severity ON severity.code=t.severity_code";
if (!$user->rights->societe->client->voir && !$socid) {
    $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
}

$sql .= ' WHERE t.entity IN ('.getEntity('ticket').')';
$sql .= " AND t.fk_statut=0";
if (!$user->rights->societe->client->voir && !$socid) {
    $sql .= " AND t.fk_soc = sc.fk_soc AND sc.fk_user = ".$user->id;
}

if ($user->socid > 0) {
    $sql .= " AND t.fk_soc='".$user->socid."'";
} else {
    // Restricted to assigned user only
    if ($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY && !$user->rights->ticket->manage) {
        $sql .= " AND t.fk_user_assign=".$user->id;
    }
}
$sql .= $db->order("t.datec", "DESC");
$sql .= $db->plimit($max, 0);

//print $sql;
$result = $db->query($sql);
if ($result) {
    $num = $db->num_rows($result);

    $i = 0;

    $transRecordedType = $langs->trans("LatestNewTickets", $max);

    print '<div class="div-table-responsive-no-min">';
    print '<table class="noborder centpercent">';
    print '<tr class="liste_titre"><th colspan="5">'.$transRecordedType.'</th>';
    print '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/ticket/list.php?search_fk_statut[]='.Ticket::STATUS_NOT_READ.'">'.$langs->trans("FullList").'</th>';
    print '</tr>';
    if ($num > 0) {
        while ($i < $num) {
            $objp = $db->fetch_object($result);

            $tickesupstatic->id = $objp->rowid;
            $tickesupstatic->ref = $objp->ref;
            $tickesupstatic->track_id = $objp->track_id;
            $tickesupstatic->fk_statut = $objp->fk_statut;
            $tickesupstatic->progress = $objp->progress;
            $tickesupstatic->subject = $objp->subject;

            print '<tr class="oddeven">';

            // Ref
            print '<td class="nowraponall">';
            print $tickesupstatic->getNomUrl(1);
            print "</td>\n";

            // Creation date
            print '<td class="left">';
            print dol_print_date($db->jdate($objp->datec), 'dayhour');
            print "</td>";

            // Subject
            print '<td class="nowrap">';
            print '<a href="card.php?track_id='.$objp->track_id.'">'.dol_trunc($objp->subject, 30).'</a>';
            print "</td>\n";

            // Type
            print '<td class="nowrap">';
            print $objp->type_label;
            print '</td>';

            // Category
            print '<td class="nowrap">';
            print $objp->category_label;
            print "</td>";

            // Severity
            print '<td class="nowrap">';
            print $objp->severity_label;
            print "</td>";

            print '<td class="nowraponall right">';
            print $tickesupstatic->getLibStatut(5);
            print "</td>";

            print "</tr>\n";
            $i++;
        }

        $db->free();
    } else {
        print '<tr><td colspan="6" class="opacitymedium">'.$langs->trans('NoUnreadTicketsFound').'</td></tr>';
    }

    print "</table>";
    print '</div>';
} else {
    dol_print_error($db);
}

print '</div></div></div>';
print '<div style="clear:both"></div>';

$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardTickets', $parameters, $object); // Note that $action and $object may have been modified by hook

// End of page
llxFooter('');
$db->close();
