<?php
/* Copyright (C) 2013-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
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

// variable $listofopplabel and $listofoppstatus should be defined

if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES))
{
	$sql = "SELECT p.fk_opp_status as opp_status, cls.code, COUNT(p.rowid) as nb, SUM(p.opp_amount) as opp_amount, SUM(p.opp_amount * p.opp_percent) as ponderated_opp_amount";
	$sql .= " FROM ".MAIN_DB_PREFIX."projet as p, ".MAIN_DB_PREFIX."c_lead_status as cls";
	$sql .= " WHERE p.entity IN (".getEntity('project').")";
	$sql .= " AND p.fk_opp_status = cls.rowid";
	$sql .= " AND p.fk_statut = 1"; // Opend projects only
	if ($mine || empty($user->rights->projet->all->lire)) $sql .= " AND p.rowid IN (".$projectsListId.")";
	if ($socid)	$sql .= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
	$sql .= " GROUP BY p.fk_opp_status, cls.code";
	$resql = $db->query($sql);

	if ($resql)
	{
	    $num = $db->num_rows($resql);
	    $i = 0;

	    $totalnb = 0;
	    $totaloppnb = 0;
	    $totalamount = 0;
	    $ponderated_opp_amount = 0;
	    $valsnb = array();
	    $valsamount = array();
	    $dataseries = array();
	    // -1=Canceled, 0=Draft, 1=Validated, (2=Accepted/On process not managed for customer orders), 3=Closed (Sent/Received, billed or not)
	    while ($i < $num)
	    {
	        $obj = $db->fetch_object($resql);
	        if ($obj)
	        {
                $valsnb[$obj->opp_status] = $obj->nb;
                $valsamount[$obj->opp_status] = $obj->opp_amount;
                $totalnb += $obj->nb;
                if ($obj->opp_status) $totaloppnb += $obj->nb;
				if (!in_array($obj->code, array('WON', 'LOST'))) {
					$totalamount += $obj->opp_amount;
					$ponderated_opp_amount += $obj->ponderated_opp_amount;
				}
	            $total += $obj->nb;
	        }
	        $i++;
	    }
	    $db->free($resql);

	    $ponderated_opp_amount = $ponderated_opp_amount / 100;

		print '<div class="div-table-responsive-no-min">';
	    print '<table class="noborder nohover centpercent">';
	    print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("OpportunitiesStatusForOpenedProjects").'</th></tr>'."\n";
	    $listofstatus = array_keys($listofoppstatus);
	    foreach ($listofstatus as $status)
	    {
	    	$labelStatus = '';

			$code = dol_getIdFromCode($db, $status, 'c_lead_status', 'rowid', 'code');
	        if ($code) $labelStatus = $langs->transnoentitiesnoconv("OppStatus".$code);
	        if (empty($labelStatus)) $labelStatus = $listofopplabel[$status];

	        //$labelStatus .= ' ('.$langs->trans("Coeff").': '.price2num($listofoppstatus[$status]).')';
	        //$labelStatus .= ' - '.price2num($listofoppstatus[$status]).'%';

	        $dataseries[] = array($labelStatus, (isset($valsamount[$status]) ? (float) $valsamount[$status] : 0));
	        if (!$conf->use_javascript_ajax)
	        {
	            print '<tr class="oddeven">';
	            print '<td>'.$labelStatus.'</td>';
	            print '<td class="right"><a href="list.php?statut='.$status.'">'.price((isset($valsamount[$status]) ? (float) $valsamount[$status] : 0), 0, '', 1, -1, -1, $conf->currency).'</a></td>';
	            print "</tr>\n";
	        }
	    }
	    if ($conf->use_javascript_ajax)
	    {
	        print '<tr><td class="center nopaddingleftimp nopaddingrightimp" colspan="2">';

	        include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
	        $dolgraph = new DolGraph();
	        $dolgraph->SetData($dataseries);
	        $dolgraph->SetDataColor(array_values($colorseries));
	        $dolgraph->setShowLegend(2);
	        $dolgraph->setShowPercent(1);
	        $dolgraph->SetType(array('pie'));
	        //$dolgraph->setWidth('100%');
	        $dolgraph->SetHeight('200');
	        $dolgraph->draw('idgraphstatus');
	        print $dolgraph->show($totaloppnb ? 0 : 1);

	        print '</td></tr>';
	    }
	    //if ($totalinprocess != $total)
	    //print '<tr class="liste_total"><td>'.$langs->trans("Total").' ('.$langs->trans("CustomersOrdersRunning").')</td><td class="right">'.$totalinprocess.'</td></tr>';
	    print '<tr class="liste_total"><td class="maxwidth200 tdoverflow">'.$langs->trans("OpportunityTotalAmount").' ('.$langs->trans("WonLostExcluded").')</td><td class="right">'.price($totalamount, 0, '', 1, -1, -1, $conf->currency).'</td></tr>';
	    print '<tr class="liste_total"><td class="minwidth200 tdoverflow">';
	    //print $langs->trans("OpportunityPonderatedAmount").' ('.$langs->trans("WonLostExcluded").')';
	    print $form->textwithpicto($langs->trans("OpportunityPonderatedAmount").' ('.$langs->trans("WonLostExcluded").')', $langs->trans("OpportunityPonderatedAmountDesc"), 1);
	    print '</td><td class="right">'.price(price2num($ponderated_opp_amount, 'MT'), 0, '', 1, -1, -1, $conf->currency).'</td></tr>';
	    print "</table>";
	    print "</div>";

	    print "<br>";
	}
	else
	{
	    dol_print_error($db);
	}
}
