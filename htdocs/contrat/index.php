<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2015      Jean-François Ferry	<jfefe@aternatik.fr>
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
 *	    \file       htdocs/contrat/index.php
 *      \ingroup    contrat
 *		\brief      Home page of contract area
 */

require ("../main.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contrat/class/contrat.class.php");
require_once (DOL_DOCUMENT_ROOT."/product/class/product.class.php");

$langs->load("products");
$langs->load("companies");
$langs->load("contracts");

$sortfield=GETPOST('sortfield','alpha');
$sortorder=GETPOST('sortorder','alpha');
$page=GETPOST('page','int');

$statut=GETPOST('statut')?GETPOST('statut'):1;

// Security check
$socid=0;
$id = GETPOST('id','int');
if (! empty($user->societe_id)) $socid=$user->societe_id;
$result = restrictedArea($user, 'contrat', $id);

$staticcompany=new Societe($db);
$staticcontrat=new Contrat($db);
$staticcontratligne=new ContratLigne($db);
$productstatic=new Product($db);


/*
 * Action
 */

// None


/*
 * View
 */

$now = dol_now();

llxHeader();

print load_fiche_titre($langs->trans("ContractsArea"),'','title_commercial.png');


//print '<table border="0" width="100%" class="notopnoleftnoright">';
//print '<tr><td valign="top" width="30%" class="notopnoleft">';
print '<div class="fichecenter"><div class="fichethirdleft">';


if (! empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
    // Search contract
    if (! empty($conf->contrat->enabled))
    {
    	print '<form method="post" action="'.DOL_URL_ROOT.'/contrat/list.php">';
    	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    	print '<table class="noborder nohover" width="100%">';
    	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
    	print '<tr class="oddeven">';
    	print '<td class="nowrap">'.$langs->trans("Contract").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
    	print '<td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
    	print "</table></form>\n";
    	print "<br>";
    }
}


/*
 * Statistics
 */

$nb=array();
$total=0;
$totalinprocess=0;
$dataseries=array();
$vals=array();

// Search by status (except expired)
$sql = "SELECT count(cd.rowid), cd.statut";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
$sql.= " AND (cd.statut != 4 OR (cd.statut = 4 AND (cd.date_fin_validite is null or cd.date_fin_validite >= '".$db->idate($now)."')))";
$sql.= " AND c.entity IN (".getEntity('contract', 0).")";
if ($user->societe_id) $sql.=' AND c.fk_soc = '.$user->societe_id;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " GROUP BY cd.statut";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;
	while ($i < $num)
	{
		$row = $db->fetch_row($resql);
		if ($row)
		{
    		$nb[$row[1]]=$row[0];
            if ($row[1]!=5)
            {
                $vals[$row[1]]=$row[0];
                $totalinprocess+=$row[0];
            }
            $total+=$row[0];
		}
		$i++;
	}
	$db->free($resql);
}
else
{
	dol_print_error($db);
}
// Search by status (only expired)
$sql = "SELECT count(cd.rowid), cd.statut";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."contratdet as cd, ".MAIN_DB_PREFIX."contrat as c";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cd.fk_contrat = c.rowid AND c.fk_soc = s.rowid";
$sql.= " AND (cd.statut = 4 AND cd.date_fin_validite < '".$db->idate($now)."')";
$sql.= " AND c.entity IN (".getEntity('contract', 0).")";
if ($user->societe_id) $sql.=' AND c.fk_soc = '.$user->societe_id;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " GROUP BY cd.statut";
$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);

    // 0 inactive, 4 active, 5 closed
    $i = 0;
    while ($i < $num)
    {
        $row = $db->fetch_row($resql);
        if ($row)
        {
            $nb[$row[1].true]=$row[0];
            if ($row[1]!=5)
            {
                $vals[$row[1]]=$row[0];
                $totalinprocess+=$row[0];
            }
            $total+=$row[0];
        }
        $i++;
    }
    $db->free($resql);
}
else
{
	dol_print_error($db);
}


print '<table class="noborder nohover" width="100%">';
print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Services").'</th></tr>'."\n";
$listofstatus=array(0,4,4,5); $bool=false;
foreach($listofstatus as $status)
{
    $dataseries[]=array($staticcontratligne->LibStatut($status,1,($bool?1:0)),(isset($nb[$status.$bool])?(int) $nb[$status.$bool]:0));
    if (empty($conf->use_javascript_ajax))
    {

        print '<tr class="oddeven">';
        print '<td>'.$staticcontratligne->LibStatut($status,0,($bool?1:0)).'</td>';
        print '<td align="right"><a href="services_list.php?mode='.$status.($bool?'&filter=expired':'').'">'.($nb[$status.$bool]?$nb[$status.$bool]:0).' '.$staticcontratligne->LibStatut($status,3,($bool?1:0)).'</a></td>';
        print "</tr>\n";
    }
    if ($status==4 && ! $bool) $bool=true;
    else $bool=false;
}
if (! empty($conf->use_javascript_ajax))
{
    print '<tr class="impair"><td align="center" colspan="2">';

    include_once DOL_DOCUMENT_ROOT.'/core/class/dolgraph.class.php';
    $dolgraph = new DolGraph();
    $dolgraph->SetData($dataseries);
    $dolgraph->setShowLegend(1);
    $dolgraph->setShowPercent(1);
    $dolgraph->SetType(array('pie'));
    $dolgraph->setWidth('100%');
    $dolgraph->draw('idgraphstatus');
    print $dolgraph->show($total?0:1);

    print '</td></tr>';
}
$listofstatus=array(0,4,4,5); $bool=false;
foreach($listofstatus as $status)
{
    if (empty($conf->use_javascript_ajax))
    {
    	print '<tr class="oddeven">';
    	print '<td>'.$staticcontratligne->LibStatut($status,0,($bool?1:0)).'</td>';
    	print '<td align="right"><a href="services_list.php?mode='.$status.($bool?'&filter=expired':'').'">'.($nb[$status.$bool]?$nb[$status.$bool]:0).' '.$staticcontratligne->LibStatut($status,3,($bool?1:0)).'</a></td>';
    	if ($status==4 && ! $bool) $bool=true;
    	else $bool=false;
        print "</tr>\n";
    }
}
//if ($totalinprocess != $total)
//print '<tr class="liste_total"><td>'.$langs->trans("Total").' ('.$langs->trans("ServicesRunning").')</td><td align="right">'.$totalinprocess.'</td></tr>';
print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">'.$total.'</td></tr>';
print "</table><br>";


// Draft contracts

if (! empty($conf->contrat->enabled) && $user->rights->contrat->lire)
{
	$sql  = "SELECT c.rowid, c.ref,";
	$sql.= " s.nom as name, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."contrat as c, ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE s.rowid = c.fk_soc";
	$sql.= " AND c.entity IN (".getEntity('contract', 0).")";
	$sql.= " AND c.statut = 0";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	if ($socid) $sql.= " AND c.fk_soc = ".$socid;

	$resql = $db->query($sql);

	if ( $resql )
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("DraftContracts").($num?' <span class="badge">'.$num.'</span>':'').'</th></tr>';
		if ($num)
		{
			$companystatic=new Societe($db);

			$i = 0;
			//$tot_ttc = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);

				$staticcontrat->ref=$obj->ref;
				$staticcontrat->id=$obj->rowid;

				$companystatic->id=$obj->socid;
				$companystatic->name=$obj->name;
				$companystatic->client=1;

				print '<tr class="oddeven"><td class="nowrap">';
				print $staticcontrat->getNomUrl(1,'');
				print '</td>';
				print '<td>';
				print $companystatic->getNomUrl(1,'',16);
				print '</td>';
				print '</tr>';
				//$tot_ttc+=$obj->total_ttc;
				$i++;

			}
		}
		else
		{
			print '<tr class="oddeven"><td colspan="3" class="opacitymedium">'.$langs->trans("NoContracts").'</td></tr>';
		}
		print "</table><br>";
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
}


//print '</td><td valign="top" width="70%" class="notopnoleftnoright">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


// Last modified contracts
$max=5;
$sql = 'SELECT ';
$sql.= ' sum('.$db->ifsql("cd.statut=0",1,0).') as nb_initial,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NULL OR cd.date_fin_validite >= '".$db->idate($now)."')",1,0).') as nb_running,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now)."')",1,0).') as nb_expired,';
$sql.= ' sum('.$db->ifsql("cd.statut=4 AND (cd.date_fin_validite IS NOT NULL AND cd.date_fin_validite < '".$db->idate($now - $conf->contrat->services->expires->warning_delay)."')",1,0).') as nb_late,';
$sql.= ' sum('.$db->ifsql("cd.statut=5",1,0).') as nb_closed,';
$sql.= " c.rowid as cid, c.ref, c.datec, c.tms, c.statut, s.nom as name, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s,";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " ".MAIN_DB_PREFIX."societe_commerciaux as sc,";
$sql.= " ".MAIN_DB_PREFIX."contrat as c";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."contratdet as cd ON c.rowid = cd.fk_contrat";
$sql.= " WHERE c.fk_soc = s.rowid";
$sql.= " AND c.entity IN (".getEntity('contract', 0).")";
$sql.= " AND c.statut > 0";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " GROUP BY c.rowid, c.ref, c.datec, c.tms, c.statut, s.nom, s.rowid";
$sql.= " ORDER BY c.tms DESC";
$sql.= " LIMIT ".$max;

dol_syslog("contrat/index.php", LOG_DEBUG);
$result=$db->query($sql);
if ($result)
{
	$num = $db->num_rows($result);
	$i = 0;

	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("LastContracts",5).'</th>';
	print '<th align="center">'.$langs->trans("DateModification").'</th>';
	//print '<th align="left">'.$langs->trans("Status").'</th>';
	print '<th align="center" width="80" colspan="4">'.$langs->trans("Services").'</th>';
	print "</tr>\n";

	while ($i < $num)
	{
		$obj = $db->fetch_object($result);

		print '<tr class="oddeven">';
		print '<td width="110" class="nowrap">';
		$staticcontrat->ref=($obj->ref?$obj->ref:$obj->cid);
		$staticcontrat->id=$obj->cid;
		print $staticcontrat->getNomUrl(1,16);
		if ($obj->nb_late) print img_warning($langs->trans("Late"));
		print '</td>';
		print '<td>';
		$staticcompany->id=$obj->socid;
		$staticcompany->name=$obj->name;
		print $staticcompany->getNomUrl(1,'',20);
		print '</td>';
		print '<td align="center">'.dol_print_date($db->jdate($obj->tms),'dayhour').'</td>';
		//print '<td align="left">'.$staticcontrat->LibStatut($obj->statut,2).'</td>';
		print '<td align="right" width="32">'.($obj->nb_initial>0 ? $obj->nb_initial.$staticcontratligne->LibStatut(0,3):'').'</td>';
		print '<td align="right" width="32">'.($obj->nb_running>0 ? $obj->nb_running.$staticcontratligne->LibStatut(4,3,0):'').'</td>';
		print '<td align="right" width="32">'.($obj->nb_expired>0 ? $obj->nb_expired.$staticcontratligne->LibStatut(4,3,1):'').'</td>';
		print '<td align="right" width="32">'.($obj->nb_closed>0  ? $obj->nb_closed.$staticcontratligne->LibStatut(5,3):'').'</td>';
		print "</tr>\n";
		$i++;
	}
	$db->free($result);

	print "</table>";

}
else
{
	dol_print_error($db);
}

print '<br>';

// Last modified services
$sql = "SELECT c.ref, c.fk_soc, ";
$sql.= " cd.rowid as cid, cd.statut, cd.label, cd.fk_product, cd.description as note, cd.fk_contrat, cd.date_fin_validite,";
$sql.= " s.nom as name,";
$sql.= " p.rowid as pid, p.ref as pref, p.label as plabel, p.fk_product_type as ptype, p.entity as pentity";
$sql.= " FROM (".MAIN_DB_PREFIX."contrat as c";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= ") LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
$sql.= " WHERE c.entity IN (".getEntity('contract', 0).")";
$sql.= " AND cd.fk_contrat = c.rowid";
$sql.= " AND c.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " ORDER BY cd.tms DESC";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("LastModifiedServices",$max).'</th>';
	print "</tr>\n";

	while ($i < min($num,$max))
	{
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';
		print '<td width="110" class="nowrap">';
		$staticcontrat->ref=($obj->ref?$obj->ref:$obj->fk_contrat);
		$staticcontrat->id=$obj->fk_contrat;
		print $staticcontrat->getNomUrl(1,16);
		//if (1 == 1) print img_warning($langs->trans("Late"));
		print '</td>';
		print '<td>';
		if ($obj->fk_product > 0)
		{
    		$productstatic->id=$obj->fk_product;
            $productstatic->type=$obj->ptype;
            $productstatic->ref=$obj->pref;
			$productstatic->entity=$obj->pentity;
            print $productstatic->getNomUrl(1,'',20);
		}
		else
		{
		    print '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
            if ($obj->label) print ' '.dol_trunc($obj->label,20).'</a>';
            else print '</a> '.dol_trunc($obj->note,20);
		}
		print '</td>';
		print '<td>';
		$staticcompany->id=$obj->fk_soc;
		$staticcompany->name=$obj->name;
		print $staticcompany->getNomUrl(1,'',20);
		print '</td>';
		print '<td class="nowrap" align="right"><a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
		$dateend=$db->jdate($obj->date_fin_validite);
		print $staticcontratligne->LibStatut($obj->statut, 3, ($dateend && $dateend < $now)?1:0);
		print '</a></td>';
		print "</tr>\n";
		$i++;
	}
	$db->free();

	print "</table>";

}
else
{
	dol_print_error($db);
}

print '<br>';

// Not activated services
$sql = "SELECT c.ref, c.fk_soc, cd.rowid as cid, cd.statut, cd.label, cd.fk_product, cd.description as note, cd.fk_contrat,";
$sql.= " s.nom as name,";
$sql.= " p.rowid as pid, p.ref as pref, p.label as plabel, p.fk_product_type as ptype, p.entity as pentity";
$sql.= " FROM (".MAIN_DB_PREFIX."contrat as c";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= " ) LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
$sql.= " WHERE c.entity IN (".getEntity('contract', 0).")";
$sql.= " AND c.statut = 1";
$sql.= " AND cd.statut = 0";
$sql.= " AND cd.fk_contrat = c.rowid";
$sql.= " AND c.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " ORDER BY cd.tms DESC";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("NotActivatedServices").' <a href="'.DOL_URL_ROOT.'/contrat/services_list.php?mode=0"><span class="badge">'.$num.'</span></a></th>';
	print "</tr>\n";

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';

		print '<td width="110" class="nowrap">';
		$staticcontrat->ref=($obj->ref?$obj->ref:$obj->fk_contrat);
		$staticcontrat->id=$obj->fk_contrat;
		print $staticcontrat->getNomUrl(1,16);
		print '</td>';
		print '<td class="nowrap">';
		if ($obj->fk_product > 0)
		{
    		$productstatic->id=$obj->fk_product;
            $productstatic->type=$obj->ptype;
            $productstatic->ref=$obj->pref;
			$productstatic->entity=$obj->pentity;
            print $productstatic->getNomUrl(1,'',20);
		}
		else
		{
		    print '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
            if ($obj->label) print ' '.dol_trunc($obj->label,20).'</a>';
            else print '</a> '.dol_trunc($obj->note,20);
		}
        print '</td>';
		print '<td>';
		$staticcompany->id=$obj->fk_soc;
		$staticcompany->name=$obj->name;
		print $staticcompany->getNomUrl(1,'',20);
		print '</td>';
		print '<td width="16" align="right"><a href="ligne.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
		print $staticcontratligne->LibStatut($obj->statut,3);
		print '</a></td>';
		print "</tr>\n";
		$i++;
	}
	$db->free();

	print "</table>";

}
else
{
	dol_print_error($db);
}

print '<br>';

// Expired services
$sql = "SELECT c.ref, c.fk_soc, cd.rowid as cid, cd.statut, cd.label, cd.fk_product, cd.description as note, cd.fk_contrat,";
$sql.= " s.nom as name,";
$sql.= " p.rowid as pid, p.ref as pref, p.label as plabel, p.fk_product_type as ptype, p.entity as pentity";
$sql.= " FROM (".MAIN_DB_PREFIX."contrat as c";
$sql.= ", ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= ", ".MAIN_DB_PREFIX."contratdet as cd";
$sql.= " ) LEFT JOIN ".MAIN_DB_PREFIX."product as p ON cd.fk_product = p.rowid";
$sql.= " WHERE c.entity IN (".getEntity('contract', 0).")";
$sql.= " AND c.statut = 1";
$sql.= " AND cd.statut = 4";
$sql.= " AND cd.date_fin_validite < '".$db->idate($now)."'";
$sql.= " AND cd.fk_contrat = c.rowid";
$sql.= " AND c.fk_soc = s.rowid";
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql.= " AND s.rowid = ".$socid;
$sql.= " ORDER BY cd.tms DESC";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	$i = 0;

	print '<table class="noborder" width="100%">';

	print '<tr class="liste_titre"><th colspan="4">'.$langs->trans("ListOfExpiredServices").' <a href="'.DOL_URL_ROOT.'/contrat/services_list.php?mode=4&amp;filter=expired"><span class="badge">'.$num.'</span></a></th>';
	print "</tr>\n";

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);

		print '<tr class="oddeven">';

		print '<td width="110" class="nowrap">';
		$staticcontrat->ref=($obj->ref?$obj->ref:$obj->fk_contrat);
		$staticcontrat->id=$obj->fk_contrat;
		print $staticcontrat->getNomUrl(1,16);
		print '</td>';
		print '<td class="nowrap">';
		if ($obj->fk_product > 0)
		{
    		$productstatic->id=$obj->fk_product;
            $productstatic->type=$obj->ptype;
            $productstatic->ref=$obj->pref;
			$productstatic->entity=$obj->pentity;
            print $productstatic->getNomUrl(1,'',20);
		}
		else
		{
		    print '<a href="'.DOL_URL_ROOT.'/contrat/card.php?id='.$obj->fk_contrat.'">'.img_object($langs->trans("ShowService"),"service");
            if ($obj->label) print ' '.dol_trunc($obj->label,20).'</a>';
            else print '</a> '.dol_trunc($obj->note,20);
		}
		print '</td>';
		print '<td>';
		$staticcompany->id=$obj->fk_soc;
		$staticcompany->name=$obj->name;
		print $staticcompany->getNomUrl(1,'',20);
		print '</td>';
		print '<td width="16" align="right"><a href="ligne.php?id='.$obj->fk_contrat.'&ligne='.$obj->cid.'">';
		print $staticcontratligne->LibStatut($obj->statut,3,1);
		print '</a></td>';
		print "</tr>\n";
		$i++;
	}
	$db->free();

	print "</table>";

}
else
{
	dol_print_error($db);
}


//print '</td></tr></table>';
print '</div></div></div>';


llxFooter();

$db->close();
