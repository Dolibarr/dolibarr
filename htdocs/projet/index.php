<?php
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/projet/index.php
 *       \ingroup    projet
 *       \brief      Main project home page
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/project.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';


$langs->load("projects");
$langs->load("companies");

$mine = GETPOST('mode')=='mine' ? 1 : 0;

// Security check
$socid=0;
if ($user->societe_id > 0) $socid=$user->societe_id;
if (!$user->rights->projet->lire) accessforbidden();

$sortfield = GETPOST("sortfield",'alpha');
$sortorder = GETPOST("sortorder",'alpha');


/*
 * View
 */

$socstatic=new Societe($db);
$projectstatic=new Project($db);
$userstatic=new User($db);
$form=new Form($db);

$projectsListId = $projectstatic->getProjectsAuthorizedForUser($user,($mine?$mine:(empty($user->rights->projet->all->lire)?0:2)),1);
//var_dump($projectsListId);


llxHeader("",$langs->trans("Projects"),"EN:Module_Projects|FR:Module_Projets|ES:M&oacute;dulo_Proyectos");

$text=$langs->trans("Projects");
if ($mine) $text=$langs->trans("MyProjects");

print_fiche_titre($text,'','title_project.png');

// Show description of content
if ($mine) print $langs->trans("MyProjectsDesc").'<br><br>';
else
{
	if (! empty($user->rights->projet->all->lire) && ! $socid) print $langs->trans("ProjectsDesc").'<br><br>';
	else print $langs->trans("ProjectsPublicDesc").'<br><br>';
}


// Get list of ponderated percent for each status
$listofoppstatus=array(); $listofopplabel=array(); $listofoppcode=array();
$sql = "SELECT cls.rowid, cls.code, cls.percent, cls.label";
$sql.= " FROM ".MAIN_DB_PREFIX."c_lead_status as cls";
$resql = $db->query($sql);
if ( $resql )
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$objp = $db->fetch_object($resql);
		$listofoppstatus[$objp->rowid]=$objp->percent;
		$listofopplabel[$objp->rowid]=$objp->label;
		$listofoppcode[$objp->rowid]=$objp->code;
		$i++;
	}
}
else dol_print_error($db);



print '<div class="fichecenter"><div class="fichethirdleft">';

// Search project
if (! empty($conf->projet->enabled) && $user->rights->projet->lire)
{
	$var=false;
	print '<form method="post" action="'.DOL_URL_ROOT.'/projet/list.php">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<table class="noborder nohover" width="100%">';
	print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchAProject").'</td></tr>';
	print '<tr '.$bc[$var].'>';
	print '<td class="nowrap"><label for="sf_ref">'.$langs->trans("Ref").'</label>:</td><td><input type="text" class="flat" name="search_ref" id="sf_ref" size="18"></td>';
	print '<td rowspan="3"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
	print '<tr '.$bc[$var].'><td class="nowrap"><label for="syear">'.$langs->trans("Year").'</label>:</td><td><input type="text" class="flat" name="search_year" id="search_year" size="18"></td>';
	print '<tr '.$bc[$var].'><td class="nowrap"><label for="sall">'.$langs->trans("Other").'</label>:</td><td><input type="text" class="flat" name="search_all" id="search_all" size="18"></td>';
	print '</tr>';
	print "</table></form>\n";
	print "<br>\n";
}


/*
 * Statistics
 */

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
	        dol_print_graph('stats',400,180,$data,1,'pie',0,'');
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


// List of draft projects
print_projecttasks_array($db,$form,$socid,$projectsListId,0,0,$listofoppstatus);


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


print '<table class="noborder" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("OpenedProjectsByThirdparties"),$_SERVER["PHP_SELF"],"s.nom","","","",$sortfield,$sortorder);
print_liste_field_titre($langs->trans("NbOfProjects"),"","","","",'align="right"',$sortfield,$sortorder);
print "</tr>\n";

$sql = "SELECT COUNT(p.rowid) as nb, SUM(p.opp_amount)";
$sql.= ", s.nom as name, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
$sql.= " WHERE p.entity = ".$conf->entity;
$sql.= " AND p.fk_statut = 1";
if ($mine || empty($user->rights->projet->all->lire)) $sql.= " AND p.rowid IN (".$projectsListId.")";
if ($socid)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
$sql.= " GROUP BY s.nom, s.rowid";

$var=true;
$resql = $db->query($sql);
if ( $resql )
{
	$num = $db->num_rows($resql);
	$i = 0;

	while ($i < $num)
	{
		$obj = $db->fetch_object($resql);
		$var=!$var;
		print "<tr ".$bc[$var].">";
		print '<td class="nowrap">';
		if ($obj->socid)
		{
			$socstatic->id=$obj->socid;
			$socstatic->name=$obj->name;
			print $socstatic->getNomUrl(1);
		}
		else
		{
			print $langs->trans("OthersNotLinkedToThirdParty");
		}
		print '</td>';
		print '<td align="right"><a href="'.DOL_URL_ROOT.'/projet/list.php?socid='.$obj->socid.'&search_status=1">'.$obj->nb.'</a></td>';
		print "</tr>\n";

		$i++;
	}

	$db->free($resql);
}
else
{
	dol_print_error($db);
}
print "</table>";


print '<br>';


print_projecttasks_array($db,$form,$socid,$projectsListId,0,1,$listofoppstatus);



print '</div></div></div>';


llxFooter();

$db->close();
