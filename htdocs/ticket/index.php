<?php
<<<<<<< HEAD
/* Copyright (C) - 2013-2016     Jean-François FERRY    <hello@librethic.io>
=======
/* Copyright (C) - 2013-2016    Jean-François FERRY     <hello@librethic.io>
 * Copyright (C) - 2019         Nicolas ZABOURI         <info@inovea-conseil.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
<<<<<<< HEAD
 *    \file     htdocs/ticket/history.php
=======
 *    \file     htdocs/ticket/agenda.php
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 *    \ingroup	ticket
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/actions_ticket.class.php';
require_once DOL_DOCUMENT_ROOT . '/ticket/class/ticketstats.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/dolgraph.class.php';
<<<<<<< HEAD
=======
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

$hookmanager = new HookManager($db);

// Initialize technical object to manage hooks. Note that conf->hooks_modules contains array
$hookmanager->initHooks(array('ticketsindex'));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// Load translation files required by the page
$langs->loadLangs(array('companies', 'other', 'ticket'));

$WIDTH = DolGraph::getDefaultGraphSizeForStats('width');
$HEIGHT = DolGraph::getDefaultGraphSizeForStats('height');

// Get parameters
$id = GETPOST('id', 'int');
$msg_id = GETPOST('msg_id', 'int');

<<<<<<< HEAD
$action = GETPOST('action', 'alpha', 3);
=======
$action = GETPOST('action', 'aZ09');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

if ($user->societe_id) {
    $socid = $user->societe_id;
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

$linkback='';
<<<<<<< HEAD
print load_fiche_titre($langs->trans('TicketsIndex'),$linkback,'title_ticket.png');
=======
print load_fiche_titre($langs->trans('TicketsIndex'), $linkback, 'title_ticket.png');
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9


$dir = '';
$filenamenb = $dir . "/" . $prefix . "ticketinyear-" . $endyear . ".png";
$fileurlnb = DOL_URL_ROOT . '/viewimage.php?modulepart=ticket&amp;file=ticketinyear-' . $endyear . '.png';

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
<<<<<<< HEAD
$WIDTH = (($shownb && $showtot) || !empty($conf->dol_optimize_smallscreen)) ? '256' : '320';
$HEIGHT = '192';
=======
$WIDTH = (($shownb && $showtot) || !empty($conf->dol_optimize_smallscreen)) ? '100%' : '80%';
$HEIGHT = '228';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

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
<<<<<<< HEAD
$total = 0;
=======

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$sql = "SELECT t.fk_statut, COUNT(t.fk_statut) as nb";
$sql .= " FROM " . MAIN_DB_PREFIX . "ticket as t";
if (!$user->rights->societe->client->voir && !$socid) {
    $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
}
<<<<<<< HEAD

$sql .= ' WHERE t.entity IN (' . getEntity('ticket', 1) . ')';
$sql .= " AND t.fk_statut IS NOT NULL";
$sql .= " AND date_format(datec,'%Y') = '" . $endyear . "'";
=======
$sql .= ' WHERE t.entity IN (' . getEntity('ticket') . ')';
$sql .= dolSqlDateFilter('datec', 0, 0, $endyear);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
if (!$user->rights->societe->client->voir && !$socid) {
    $sql .= " AND t.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
}

// External users restriction
if ($user->societe_id > 0) {
    $sql .= " AND t.fk_soc='" . $user->societe_id . "'";
} else {
    // For internals users,
    if (!empty($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY) && !$user->rights->ticket->manage) {
        $sql .= " AND t.fk_user_assign=" . $user->id;
    }
}
$sql .= " GROUP BY t.fk_statut";

$result = $db->query($sql);
if ($result) {
    while ($objp = $db->fetch_object($result)) {
        $found = 0;
<<<<<<< HEAD
        if ($objp->fk_statut == 0) {
            $tick['unread'] = $objp->nb;
        }
        if ($objp->fk_statut == 1) {
            $tick['read'] = $objp->nb;
        }
        if ($objp->fk_statut == 3) {
            $tick['answered'] = $objp->nb;
        }
        if ($objp->fk_statut == 4) {
            $tick['assigned'] = $objp->nb;
        }
        if ($objp->fk_statut == 5) {
            $tick['inprogress'] = $objp->nb;
        }
        if ($objp->fk_statut == 6) {
            $tick['waiting'] = $objp->nb;
        }
        if ($objp->fk_statut == 8) {
            $tick['closed'] = $objp->nb;
        }
        if ($objp->fk_statut == 9) {
            $tick['deleted'] = $objp->nb;
        }
    }

    if ((round($tick['unread']) ? 1 : 0) +(round($tick['read']) ? 1 : 0) +(round($tick['answered']) ? 1 : 0) +(round($tick['assigned']) ? 1 : 0) +(round($tick['inprogress']) ? 1 : 0) +(round($tick['waiting']) ? 1 : 0) +(round($tick['closed']) ? 1 : 0) +(round($tick['deleted']) ? 1 : 0) >= 2
    ) {
        $dataseries = array();
        $dataseries[] = array('label' => $langs->trans("Unread"), 'data' => round($tick['unread']));
        $dataseries[] = array('label' => $langs->trans("Read"), 'data' => round($tick['read']));
        $dataseries[] = array('label' => $langs->trans("Answered"), 'data' => round($tick['answered']));
        $dataseries[] = array('label' => $langs->trans("Assigned"), 'data' => round($tick['assigned']));
        $dataseries[] = array('label' => $langs->trans("InProgress"), 'data' => round($tick['inprogress']));
        $dataseries[] = array('label' => $langs->trans("Waiting"), 'data' => round($tick['waiting']));
        $dataseries[] = array('label' => $langs->trans("Closed"), 'data' => round($tick['closed']));
        $dataseries[] = array('label' => $langs->trans("Deleted"), 'data' => round($tick['deleted']));
    }
=======
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

    $dataseries = array();
    $dataseries[] = array('label' => $langs->trans("Unread"), 'data' => round($tick['unread']));
    $dataseries[] = array('label' => $langs->trans("Read"), 'data' => round($tick['read']));
    $dataseries[] = array('label' => $langs->trans("NeedMoreInformation"), 'data' => round($tick['needmoreinfo']));
    $dataseries[] = array('label' => $langs->trans("Assigned"), 'data' => round($tick['assigned']));
    $dataseries[] = array('label' => $langs->trans("InProgress"), 'data' => round($tick['inprogress']));
    $dataseries[] = array('label' => $langs->trans("Waiting"), 'data' => round($tick['waiting']));
    $dataseries[] = array('label' => $langs->trans("Closed"), 'data' => round($tick['closed']));
    $dataseries[] = array('label' => $langs->trans("Canceled"), 'data' => round($tick['canceled']));
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
$stringtoshow .= '<form class="flat formboxfilter" method="POST" action="' . $_SERVER["PHP_SELF"] . '">';
$stringtoshow .= '<input type="hidden" name="action" value="' . $refreshaction . '">';
$stringtoshow .= '<input type="hidden" name="DOL_AUTOSET_COOKIE" value="DOLUSERCOOKIE_ticket_by_status:year,shownb,showtot">';
$stringtoshow .= $langs->trans("Year") . ' <input class="flat" size="4" type="text" name="' . $param_year . '" value="' . $endyear . '">';
$stringtoshow .= '<input type="image" alt="' . $langs->trans("Refresh") . '" src="' . img_picto($langs->trans("Refresh"), 'refresh.png', '', '', 1) . '">';
$stringtoshow .= '</form>';
$stringtoshow .= '</div>';

print '<table class="noborder" width="100%">';
print '<tr class="liste_titre"><th >' . $langs->trans("Statistics") . ' ' . img_picto('', 'filter.png', 'id="idsubimgDOLUSERCOOKIE_ticket_by_status" class="linkobject"') . '</th></tr>';

<<<<<<< HEAD
print '<tr><td>';

// don't display graph if no series
if (! empty($dataseries) && count($dataseries) > 1) {
    $data = array();
=======
print '<tr><td class="center">';
print $stringtoshow;

// don't display graph if no series
if (! empty($dataseries) && count($dataseries) > 1) {
	$totalnb=0;
	foreach ($dataseries as $key => $value) {
		$totalnb += $value['data'];
	}

	$data = array();
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    foreach ($dataseries as $key => $value) {
        $data[] = array($value['label'], $value['data']);
    }
    $px1 = new DolGraph();
    $mesg = $px1->isGraphKo();
    if (!$mesg) {
        $px1->SetData($data);
        unset($data1);
<<<<<<< HEAD
        $px1->SetPrecisionY(0);
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $i = $startyear;
        $legend = array();
        while ($i <= $endyear) {
            $legend[] = $i;
            $i++;
        }
        $px1->SetType(array('pie'));
        $px1->SetLegend($legend);
        $px1->SetMaxValue($px1->GetCeilMaxValue());
        $px1->SetWidth($WIDTH);
        $px1->SetHeight($HEIGHT);
        $px1->SetYLabel($langs->trans("TicketStatByStatus"));
        $px1->SetShading(3);
        $px1->SetHorizTickIncrement(1);
<<<<<<< HEAD
        $px1->SetPrecisionY(0);
=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $px1->SetCssPrefix("cssboxes");
        $px1->mode = 'depth';
        //$px1->SetTitle($langs->trans("TicketStatByStatus"));

        $px1->draw($filenamenb, $fileurlnb);
<<<<<<< HEAD
        print $px1->show();
    }
}
print $stringtoshow;
=======
        print $px1->show($totalnb?0:1);
    }
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
print '</td></tr>';

print '</table>';

// Build graphic number of object
$data = $stats->getNbByMonth($endyear, $startyear);

print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';

<<<<<<< HEAD
/*
 * Last tickets
 */
$max = 15;
=======

/*
 * Latest tickets
 */

$max = 10;

>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
$sql = "SELECT t.rowid, t.ref, t.track_id, t.datec, t.subject, t.type_code, t.category_code, t.severity_code, t.fk_statut, t.progress,";
$sql .= " type.label as type_label, category.label as category_label, severity.label as severity_label";
$sql .= " FROM " . MAIN_DB_PREFIX . "ticket as t";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticket_type as type ON type.code=t.type_code";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticket_category as category ON category.code=t.category_code";
$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "c_ticket_severity as severity ON severity.code=t.severity_code";
if (!$user->rights->societe->client->voir && !$socid) {
    $sql .= ", " . MAIN_DB_PREFIX . "societe_commerciaux as sc";
}

$sql .= ' WHERE t.entity IN (' . getEntity('ticket', 1) . ')';
$sql .= " AND t.fk_statut=0";
if (!$user->rights->societe->client->voir && !$socid) {
    $sql .= " AND t.fk_soc = sc.fk_soc AND sc.fk_user = " . $user->id;
}

if ($user->societe_id > 0) {
    $sql .= " AND t.fk_soc='" . $user->societe_id . "'";
} else {
    // Restricted to assigned user only
    if ($conf->global->TICKET_LIMIT_VIEW_ASSIGNED_ONLY && !$user->rights->ticket->manage) {
        $sql .= " AND t.fk_user_assign=" . $user->id;
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
    print '<table class="noborder" width="100%">';
<<<<<<< HEAD
    print '<tr class="liste_titre"><th>' . $transRecordedType . '</th>';
    print '<th>' . $langs->trans('Date') . '</th>';
    print '<th>' . $langs->trans('Subject') . '</th>';
    print '<th>' . $langs->trans('Type') . '</th>';
    print '<th>' . $langs->trans('Category') . '</th>';
    print '<th>' . $langs->trans('Severity') . '</th>';
    print '<th></th>';
=======
    print '<tr class="liste_titre"><th colspan="5">' . $transRecordedType . '</th>';
    print '<th class="right" colspan="2"><a href="'.DOL_URL_ROOT.'/ticket/list.php?search_fk_statut[]='.Ticket::STATUS_NOT_READ.'">'.$langs->trans("FullList").'</th>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
            print '<td class="nowrap">';
            print $tickesupstatic->getNomUrl(1);
            print "</td>\n";

            // Creation date
<<<<<<< HEAD
            print '<td align="left">';
=======
            print '<td class="left">';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print dol_print_date($db->jdate($objp->datec), 'dayhour');
            print "</td>";

            // Subject
            print '<td class="nowrap">';
            print '<a href="card.php?track_id=' . $objp->track_id . '">' . dol_trunc($objp->subject, 30) . '</a>';
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

<<<<<<< HEAD
            print '<td class="nowrap">';
            print $tickesupstatic->getLibStatut(3);
=======
            print '<td class="nowrap right">';
            print $tickesupstatic->getLibStatut(5);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            print "</td>";

            print "</tr>\n";
            $i++;
        }

        $db->free();
    } else {
<<<<<<< HEAD
        print '<tr><td colspan="6" class="opacitymedium">' . $langs->trans('NoTicketsFound') . '</td></tr>';
=======
        print '<tr><td colspan="6" class="opacitymedium">' . $langs->trans('NoUnreadTicketsFound') . '</td></tr>';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    print "</table>";
    print '</div>';
} else {
    dol_print_error($db);
}

print '</div></div></div>';
print '<div style="clear:both"></div>';

<<<<<<< HEAD
print '<div class="tabsAction">';
print '<div class="inline-block divButAction"><a class="butAction" href="new.php?action=create_ticket">' . $langs->trans('CreateTicket') . '</a></div>';
print '<div class="inline-block divButAction"><a class="butAction" href="list.php">' . $langs->trans('TicketList') . '</a></div>';
print '</div>';
=======
$parameters = array('user' => $user);
$reshook = $hookmanager->executeHooks('dashboardTickets', $parameters, $object); // Note that $action and $object may have been modified by hook
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

// End of page
llxFooter('');
$db->close();
