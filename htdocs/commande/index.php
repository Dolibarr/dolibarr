<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin        <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/commande/index.php
 *	\ingroup    commande
 *	\brief      Home page of customer order module
 */

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/core/class/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT ."/core/class/notify.class.php");
require_once(DOL_DOCUMENT_ROOT ."/commande/class/commande.class.php");

if (!$user->rights->commande->lire) accessforbidden();

$langs->load("orders");

// Security check
$socid=GETPOST('socid');
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}



/*
 * View
 */

$commandestatic=new Commande($db);
$form = new Form($db);
$formfile = new FormFile($db);
$help_url="EN:Module_Customers_Orders|FR:Module_Commandes_Clients|ES:Módulo Pedidos de clientes";

llxHeader("",$langs->trans("Orders"),$help_url);

print_fiche_titre($langs->trans("OrdersArea"));

print '<table width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Search form
 */
$var=false;
print '<table class="noborder" width="100%">';
print '<form method="post" action="liste.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchOrder").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="sref" size=18></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
print '</tr>';
print "</form></table><br>\n";


/*
 * Statistics
 */

$sql = "SELECT count(cf.rowid), c.fk_statut, c.facture";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."commande as c";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.fk_soc = s.rowid";
$sql.= " AND c.entity = ".$conf->entity;
if ($user->societe_id) $sql.=' AND c.fk_soc = '.$user->societe_id;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " GROUP BY c.fk_statut, c.facture";
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
                $bool=$row[2]?true:false;
                $vals[$row[1].$bool]+=$row[0];
                $totalinprocess+=$row[0];
            }
            $total+=$row[0];
        }
        $i++;
    }
    $db->free($resql);
    print '<table class="noborder" width="100%">';
    print '<tr class="liste_titre"><td colspan="2">'.$langs->trans("Statistics").' - '.$langs->trans("CustomersOrders").'</td></tr>'."\n";
    $listofstatus=array(0,1,2,3,3,-1);
    $bool=false;
    foreach ($listofstatus as $status)
    {
        $dataseries[]=array('label'=>$commandestatic->LibStatut($status,$bool,1),'data'=>(isset($vals[$status.$bool])?(int) $vals[$status.$bool]:0));
        if ($status==3 && $bool==false) $bool=true;
        else $bool=false;
    }
    if ($conf->use_javascript_ajax)
    {
        print '<tr><td align="center" colspan="2">';
        $data=array('series'=>$dataseries);
        dol_print_graph('stats',300,180,$data,1,'pie',1);
        print '</td></tr>';
    }
    $var=true;
    $bool=false;
    foreach ($listofstatus as $status)
    {
        if (! $conf->use_javascript_ajax)
        {
            $var=!$var;
            print "<tr ".$bc[$var].">";
            print '<td>'.$commandestatic->LibStatut($status,$bool,0).'</td>';
            print '<td align="right"><a href="liste.php?viewstatut='.$status.'">'.(isset($vals[$status.$bool])?$vals[$status.$bool]:0).' ';
            print $commandestatic->LibStatut($status,$bool,3);
            print '</a>';
            print '</td>';
            print "</tr>\n";
            if ($status==3 && $bool==false) $bool=true;
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
if ($conf->commande->enabled)
{
	$sql = "SELECT c.rowid, c.ref, s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	$sql.= " AND c.fk_statut = 0";
	if ($socid) $sql.= " AND c.fk_soc = ".$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

	$resql=$db->query($sql);
	if ($resql)
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="2">'.$langs->trans("DraftOrders").'</td></tr>';
		$langs->load("orders");
		$num = $db->num_rows($resql);
		if ($num)
		{
			$i = 0;
			$var = True;
			while ($i < $num)
			{
				$var=!$var;
				$obj = $db->fetch_object($resql);
				print "<tr $bc[$var]>";
				print '<td nowrap="nowrap">';
				print "<a href=\"fiche.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref."</a></td>";
				print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->nom,24).'</a></td></tr>';
				$i++;
			}
		}
		print "</table><br>";
	}
}

print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


$max=5;

/*
 * Last modified orders
 */

$sql = "SELECT c.rowid, c.ref, c.fk_statut, c.facture, c.date_cloture as datec, c.tms as datem,";
$sql.= " s.nom, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."commande as c,";
$sql.= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.fk_soc = s.rowid";
$sql.= " AND c.entity = ".$conf->entity;
//$sql.= " AND c.fk_statut > 2";
if ($socid) $sql .= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " ORDER BY c.tms DESC";
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4">'.$langs->trans("LastModifiedOrders",$max).'</td></tr>';

	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		$var = True;
		while ($i < $num)
		{
			$var=!$var;
			$obj = $db->fetch_object($resql);

			print "<tr $bc[$var]>";
			print '<td width="20%" nowrap="nowrap">';

			$commandestatic->id=$obj->rowid;
			$commandestatic->ref=$obj->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="96" class="nobordernopadding" nowrap="nowrap">';
			print $commandestatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding" nowrap="nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding">';
			$filename=dol_sanitizeFileName($obj->ref);
			$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			$formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','',1,'',1);
			print '</td></tr></table>';

			print '</td>';

			print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
			print '<td>'.dol_print_date($db->jdate($obj->datem),'day').'</td>';
			print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,5).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table><br>";
}
else dol_print_error($db);


/*
 * Orders to process
 */
if ($conf->commande->enabled)
{
	$sql = "SELECT c.rowid, c.ref, c.fk_statut, c.facture, s.nom, s.rowid as socid";
	$sql.=" FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	$sql.= " AND c.fk_statut = 1";
	if ($socid) $sql.= " AND c.fk_soc = ".$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	$sql.= " ORDER BY c.rowid DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("OrdersToProcess").' <a href="'.DOL_URL_ROOT.'/commande/liste.php?viewstatut=1">('.$num.')</a></td></tr>';

		if ($num)
		{
			$i = 0;
			$var = True;
			while ($i < $num)
			{
				$var=!$var;
				$obj = $db->fetch_object($resql);
				print "<tr $bc[$var]>";
				print '<td nowrap="nowrap" width="20%">';

				$commandestatic->id=$obj->rowid;
				$commandestatic->ref=$obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding" nowrap="nowrap">';
				print $commandestatic->getNomUrl(1);
				print '</td>';

				print '<td width="16" class="nobordernopadding" nowrap="nowrap">';
				print '&nbsp;';
				print '</td>';

				print '<td width="16" align="right" class="nobordernopadding">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				$formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','',1,'',1);
				print '</td></tr></table>';

				print '</td>';

				print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->nom,24).'</a></td>';

				print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,5).'</td>';

				print '</tr>';
				$i++;
			}
		}

		print "</table><br>";
	}
	else dol_print_error($db);
}

/*
 * Orders thar are in a shipping process
 */
if ($conf->commande->enabled)
{
	$sql = "SELECT c.rowid, c.ref, c.fk_statut, c.facture, s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	$sql.= " AND c.fk_statut = 2 ";
	if ($socid) $sql.= " AND c.fk_soc = ".$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
	$sql.= " ORDER BY c.rowid DESC";

	$resql=$db->query($sql);
	if ($resql)
	{
		$num = $db->num_rows($resql);

		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("OnProcessOrders").' <a href="'.DOL_URL_ROOT.'/commande/liste.php?viewstatut=2">('.$num.')</a></td></tr>';

		if ($num)
		{
			$i = 0;
			$var = True;
			while ($i < $num)
			{
				$var=!$var;
				$obj = $db->fetch_object($resql);
				print "<tr $bc[$var]>";
				print '<td width="20%" nowrap="nowrap">';

				$commandestatic->id=$obj->rowid;
				$commandestatic->ref=$obj->ref;

				print '<table class="nobordernopadding"><tr class="nocellnopadd">';
				print '<td width="96" class="nobordernopadding" nowrap="nowrap">';
				print $commandestatic->getNomUrl(1);
				print '</td>';

				print '<td width="16" class="nobordernopadding" nowrap="nowrap">';
				print '&nbsp;';
				print '</td>';

				print '<td width="16" align="right" class="nobordernopadding">';
				$filename=dol_sanitizeFileName($obj->ref);
				$filedir=$conf->commande->dir_output . '/' . dol_sanitizeFileName($obj->ref);
				$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
				$formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','',1,'',1);
				print '</td></tr></table>';

				print '</td>';

				print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';

				print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,5).'</td>';

				print '</tr>';
				$i++;
			}
		}
		print "</table><br>";
	}
	else dol_print_error($db);
}


print '</td></tr></table>';

$db->close();

llxFooter();

?>
