<?php 
if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
{
	$sql = "SELECT COUNT(p.rowid) as nb, SUM(p.opp_amount) as opp_amount, p.fk_opp_status as opp_status";
	$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	$sql.= " WHERE p.entity = ".$conf->entity;
	$sql.= " AND p.fk_statut = 1";
	if ($mine || empty($user->rights->projet->all->lire)) $sql.= " AND p.rowid IN (".$projectsListId.")";
	if ($socid)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
	$sql.= " GROUP BY p.fk_opp_status";
	$resql = $db->query($sql);
	if ($resql)
	{
	    $num = $db->num_rows($resql);
	    $i = 0;

	    $totalnb=0;
	    $totalamount=0;
	    $ponderated_opp_amount=0;
	    $valsnb=array();
	    $valsamount=array();
	    $dataseries=array();
	    // -1=Canceled, 0=Draft, 1=Validated, (2=Accepted/On process not managed for customer orders), 3=Closed (Sent/Received, billed or not)
	    while ($i < $num)
	    {
	        $obj = $db->fetch_object($resql);
	        if ($obj)
	        {
	            //if ($row[1]!=-1 && ($row[1]!=3 || $row[2]!=1))
	            {
	                $valsnb[$obj->opp_status]=$obj->nb;
	                $valsamount[$obj->opp_status]=$obj->opp_amount;
	                $totalnb+=$obj->nb;
	                $totalamount+=$obj->opp_amount;
	                $ponderated_opp_amount = $ponderated_opp_amount + price2num($listofoppstatus[$obj->opp_status] * $obj->opp_amount / 100);
	            }
	            $total+=$row[0];
	        }
	        $i++;
	    }
	    $db->free($resql);

	    print '<table class="noborder nohover" width="100%">';
	    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("OpportunitiesStatusForOpenedProjects").'</td></tr>'."\n";
	    $var=true;
	    $listofstatus=array_keys($listofoppstatus);
	    foreach ($listofstatus as $status)
	    {
	    	$labelstatus = '';

			$code = dol_getIdFromCode($db, $status, 'c_lead_status', 'rowid', 'code');
	        if ($code) $labelstatus = $langs->trans("OppStatus".$code);
	        if (empty($labelstatus)) $labelstatus=$listofopplabel[$status];

	        //$labelstatus .= ' ('.$langs->trans("Coeff").': '.price2num($listofoppstatus[$status]).')';
	        $labelstatus .= ' - '.price2num($listofoppstatus[$status]).'%';

	        $dataseries[]=array('label'=>$labelstatus,'data'=>(isset($valsamount[$status])?(float) $valsamount[$status]:0));
	        if (! $conf->use_javascript_ajax)
	        {
	            $var=!$var;
	            print "<tr ".$bc[$var].">";
	            print '<td>'.$labelstatus.'</td>';
	            print '<td align="right"><a href="list.php?statut='.$status.'">'.price((isset($valsamount[$status])?(float) $valsamount[$status]:0), 0, '', 1, -1, -1, $conf->currency).'</a></td>';
	            print "</tr>\n";
	        }
	    }
	    if ($conf->use_javascript_ajax)
	    {
	        print '<tr class="impair"><td align="center" colspan="2">';
	        $data=array('series'=>$dataseries);
	        dol_print_graph('stats',400,180,$data,1,'pie',0,'',0);
	        print '</td></tr>';
	    }
	    //if ($totalinprocess != $total)
	    //print '<tr class="liste_total"><td>'.$langs->trans("Total").' ('.$langs->trans("CustomersOrdersRunning").')</td><td align="right">'.$totalinprocess.'</td></tr>';
	    print '<tr class="liste_total"><td>'.$langs->trans("OpportunityTotalAmount").'</td><td align="right">'.price($totalamount, 0, '', 1, -1, -1, $conf->currency).'</td></tr>';
	    print '<tr class="liste_total"><td>'.$langs->trans("OpportunityPonderatedAmount").'</td><td align="right">'.price($ponderated_opp_amount, 0, '', 1, -1, -1, $conf->currency).'</td></tr>';
	    print "</table><br>";
	}
	else
	{
	    dol_print_error($db);
	}
}
