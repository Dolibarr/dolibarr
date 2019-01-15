<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2015	   Charlie Benke		<charlie@patas-monkey.com>

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
 *	\file       htdocs/fichinter/index.php
 *	\ingroup    commande
 *	\brief      Home page of interventional module
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/html.formfile.class.php';
require_once DOL_DOCUMENT_ROOT .'/core/class/notify.class.php';
require_once DOL_DOCUMENT_ROOT .'/fichinter/class/fichinter.class.php';

if (!$user->rights->ficheinter->lire) accessforbidden();

// Load translation files required by the page
$langs->load("interventions");

// Security check
$socid=GETPOST('socid','int');
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}



/*
 * View
 */

$fichinterstatic=new Fichinter($db);
$form = new Form($db);
$formfile = new FormFile($db);
$help_url="EN:ModuleFichinters|FR:Module_Fiche_Interventions|ES:Módulo_FichaInterventiones";

llxHeader("",$langs->trans("Interventions"),$help_url);

print load_fiche_titre($langs->trans("InterventionsArea"));

print '<div class="fichecenter"><div class="fichethirdleft">';

if (! empty($conf->global->MAIN_SEARCH_FORM_ON_HOME_AREAS))     // This is useless due to the global search combo
{
    // Search ficheinter
    $var=false;
    print '<table class="noborder nohover" width="100%">';
    print '<form method="post" action="'.DOL_URL_ROOT.'/fichinter/list.php">';
    print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
    print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
    print '<tr class="oddeven"><td>';
    print $langs->trans("Intervention").':</td><td><input type="text" class="flat" name="sall" size="18"></td><td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
    print "</form></table><br>\n";
}


/*
 * Statistics
 */

$sql = "SELECT count(f.rowid), f.fk_statut";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."fichinter as f";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE f.entity IN (".getEntity('intervention').")";
$sql.= " AND f.fk_soc = s.rowid";
if ($user->societe_id) $sql.=' AND f.fk_soc = '.$user->societe_id;
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " GROUP BY f.fk_statut";
$resql = $db->query($sql);
if ($resql)
{
    $num = $db->num_rows($resql);
    $i = 0;

    $total=0;
    $totalinprocess=0;
    $dataseries=array();
    $vals=array();
    $bool=false;
    // -1=Canceled, 0=Draft, 1=Validated, 2=Accepted/On process, 3=Closed (Sent/Received, billed or not)
    while ($i < $num)
    {
        $row = $db->fetch_row($resql);
        if ($row)
        {
            //if ($row[1]!=-1 && ($row[1]!=3 || $row[2]!=1))
            {
                $bool=(! empty($row[2])?true:false);
                if (! isset($vals[$row[1].$bool])) $vals[$row[1].$bool]=0;
                $vals[$row[1].$bool]+=$row[0];
                $totalinprocess+=$row[0];
            }
            $total+=$row[0];
        }
        $i++;
    }
    $db->free($resql);
    print '<table class="noborder nohover" width="100%">';
    print '<tr class="liste_titre"><th colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("Interventions").'</th></tr>'."\n";
    $listofstatus=array(0,1,2);
    $bool=false;
    foreach ($listofstatus as $status)
    {
        $dataseries[]=array($fichinterstatic->LibStatut($status,$bool,1), (isset($vals[$status.$bool])?(int) $vals[$status.$bool]:0));
        if ($status==3 && ! $bool) $bool=true;
        else $bool=false;
    }
    if ($conf->use_javascript_ajax)
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
        $data=array('series'=>$dataseries);

        print '</td></tr>';
    }
    $bool=false;
    foreach ($listofstatus as $status)
    {
        if (! $conf->use_javascript_ajax)
        {
            print '<tr class="oddeven">';
            print '<td>'.$fichinterstatic->LibStatut($status,$bool,0).'</td>';
            print '<td align="right"><a href="list.php?viewstatut='.$status.'">'.(isset($vals[$status.$bool])?$vals[$status.$bool]:0).' ';
            print $fichinterstatic->LibStatut($status,$bool,3);
            print '</a>';
            print '</td>';
            print "</tr>\n";
            if ($status==3 && ! $bool) $bool=true;
            else $bool=false;
        }
    }
    //if ($totalinprocess != $total)
    //print '<tr class="liste_total"><td>'.$langs->trans("Total").' ('.$langs->trans("CustomersOrdersRunning").')</td><td align="right">'.$totalinprocess.'</td></tr>';
    print '<tr class="liste_total"><td>'.$langs->trans("Total").'</td><td align="right">'.$total.'</td></tr>';
    print "</table><br>";
}
else
{
    dol_print_error($db);
}


/*
 * Draft orders
 */
if (! empty($conf->ficheinter->enabled))
{
	$sql = "SELECT f.rowid, f.ref, s.nom as name, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."fichinter as f";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE f.entity IN (".getEntity('intervention').")";
	$sql.= " AND f.fk_soc = s.rowid";
	$sql.= " AND f.fk_statut = 0";
	if ($socid) $sql.= " AND f.fk_soc = ".$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

	$resql=$db->query($sql);
	if ($resql)
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="2">'.$langs->trans("DraftFichinter").'</th></tr>';
		$langs->load("fichinter");
		$num = $db->num_rows($resql);
		if ($num)
		{
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td class="nowrap">';
				print "<a href=\"card.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowFichinter"),"intervention").' '.$obj->ref."</a></td>";
				print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->name,24).'</a></td></tr>';
				$i++;
			}
		}
		print "</table><br>";
	}
}


print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


$max=5;

/*
 * Last modified interventions
 */

$sql = "SELECT f.rowid, f.ref, f.fk_statut, f.date_valid as datec, f.tms as datem,";
$sql.= " s.nom as name, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."fichinter as f,";
$sql.= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE f.entity IN (".getEntity('intervention').")";
$sql.= " AND f.fk_soc = s.rowid";
//$sql.= " AND c.fk_statut > 2";
if ($socid) $sql .= " AND f.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " ORDER BY f.tms DESC";
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<th colspan="4">'.$langs->trans("LastModifiedInterventions",$max).'</th></tr>';

	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			print '<tr class="oddeven">';
			print '<td width="20%" class="nowrap">';

			$fichinterstatic->id=$obj->rowid;
			$fichinterstatic->ref=$obj->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding nowrap">';
			print $fichinterstatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
			$filename=dol_sanitizeFileName($obj->ref);
			$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			print $formfile->getDocumentsLink($fichinterstatic->element, $filename, $filedir);
			print '</td></tr></table>';

			print '</td>';

			print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->name.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($obj->datem),'day').'</td>';
			print '<td align="right">'.$fichinterstatic->LibStatut($obj->fk_statut,5).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table><br>";
}
else dol_print_error($db);


/*
 * interventions to process
 */

if (! empty($conf->ficheinter->enabled))
{
	$sql = "SELECT f.rowid, f.ref, f.fk_statut, s.nom as name, s.rowid as socid";
	$sql.=" FROM ".MAIN_DB_PREFIX."fichinter as f";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE f.entity IN (".getEntity('intervention').")";
	$sql.= " AND f.fk_soc = s.rowid";
	$sql.= " AND f.fk_statut = 1";
	if ($socid) $sql.= " AND f.fk_soc = ".$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	$sql.= " ORDER BY f.rowid DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<th colspan="3">'.$langs->trans("FichinterToProcess").' <a href="'.DOL_URL_ROOT.'/fichinter/list.php?viewstatut=1"><span class="badge">'.$num.'</span></a></th></tr>';

		if ($num)
		{
			$i = 0;
			while ($i < $num)
			{
				$obj = $db->fetch_object($resql);
				print '<tr class="oddeven">';
				print '<td class="nowrap" width="20%">';

				$fichinterstatic->id=$obj->rowid;
				$fichinterstatic->ref=$obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding nowrap">';
				print $fichinterstatic->getNomUrl(1);
				print '</td>';

				print '<td width="16" class="nobordernopadding nowrap">';
				print '&nbsp;';
				print '</td>';

				print '<td width="16" align="right" class="nobordernopadding hideonsmartphone">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				print $formfile->getDocumentsLink($fichinterstatic->element, $filename, $filedir);
				print '</td></tr></table>';

				print '</td>';

				print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->name,24).'</a></td>';

				print '<td align="right">'.$fichinterstatic->LibStatut($obj->fk_statut,5).'</td>';

				print '</tr>';
				$i++;
			}
		}

		print "</table><br>";
	}
	else dol_print_error($db);
}

print '</div></div></div>';


llxFooter();

$db->close();
