<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
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
 *       \file       htdocs/expedition/index.php
 *       \ingroup    expedition
 *       \brief      Home page of shipping area.
 */

require '../main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
require_once DOL_DOCUMENT_ROOT.'/expedition/class/expedition.class.php';

$langs->load("orders");
$langs->load("sendings");

/*
 *	View
 */

$orderstatic=new Commande($db);
$companystatic=new Societe($db);
$shipment=new Expedition($db);

$helpurl='EN:Module_Shipments|FR:Module_Exp&eacute;ditions|ES:M&oacute;dulo_Expediciones';
llxHeader('',$langs->trans("Shipment"),$helpurl);

print load_fiche_titre($langs->trans("SendingsArea"));


print '<div class="fichecenter"><div class="fichethirdleft">';


$var=false;
print '<form method="post" action="list.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<table class="noborder nohover" width="100%">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("Search").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Shipment").':</td><td><input type="text" class="flat" name="sall" size="18"></td><td><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print "</table></form><br>\n";

/*
 * Shipments to validate
 */
$clause = " WHERE ";

$sql = "SELECT e.rowid, e.ref, e.ref_customer,";
$sql.= " s.nom as name, s.rowid as socid,";
$sql.= " c.ref as commande_ref, c.rowid as commande_id";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON e.rowid = el.fk_target AND el.targettype = 'shipping'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON el.fk_source = c.rowid";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (!$user->rights->societe->client->voir && !$socid)
{
	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
	$sql.= $clause." sc.fk_user = " .$user->id;
	$clause = " AND ";
}
$sql.= $clause." e.fk_statut = 0";
$sql.= " AND e.entity IN (".getEntity('expedition', 1).")";
if ($socid) $sql.= " AND c.fk_soc = ".$socid;

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	if ($num)
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("SendingsToValidate").'</td></tr>';
		$i = 0;
		$var = True;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			
			$shipment->id=$obj->rowid;
			$shipment->ref=$obj->ref;
			$shipment->ref_customer=$obj->ref_customer;
				
			$var=!$var;
			print "<tr ".$bc[$var].'><td class="nowrap">';
			print $shipment->getNomUrl(1);
			print "</td>";
			print '<td>';
			print '<a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.$obj->name.'</a>';
			print '</td>';
			print '<td>';
			if ($obj->commande_id) print '<a href="'.DOL_URL_ROOT.'/commande/card.php?id='.$obj->commande_id.'">'.$obj->commande_ref.'</a>';
			print '</td></tr>';
			$i++;
		}
		print "</table><br>";
	}
}


/*
 * Commandes a traiter
 */
$sql = "SELECT c.rowid, c.ref, c.ref_client as ref_customer, c.fk_statut, s.nom as name, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."commande as c,";
$sql.= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.fk_soc = s.rowid";
$sql.= " AND c.entity = ".$conf->entity;
$sql.= " AND c.fk_statut = 1";
if ($socid) $sql.= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " ORDER BY c.rowid ASC";

$resql=$db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	if ($num)
	{
		$langs->load("orders");

		$i = 0;
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("OrdersToProcess").'</td></tr>';
		$var = True;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);

			$orderstatic->id=$obj->rowid;
			$orderstatic->ref=$obj->ref;
			$orderstatic->ref_customer=$obj->ref_customer;
			$orderstatic->statut=$obj->fk_statut;
			$orderstatic->facturee=0;
			
			$companystatic->name=$obj->name;
			$companystatic->id=$obj->socid;
			
			$var=!$var;
			print "<tr ".$bc[$var].">";
			print '<td class="nowrap">';
			print $orderstatic->getNomUrl(1);
			print '</td>';
			print '<td>';
			print $companystatic->getNomUrl(1,'customer',32);
			print '</td>';
			print '<td align="right">';
			print $orderstatic->getLibStatut(3);
			print '</td>';
			print '</tr>';
			$i++;
		}
		print "</table><br>";
	}
}


//print '</td><td valign="top" width="70%">';
print '</div><div class="fichetwothirdright"><div class="ficheaddleft">';


/*
 * Commandes en traitement
 */
$sql = "SELECT c.rowid, c.ref, c.ref_client as ref_customer, c.fk_statut as status, c.facture as billed, s.nom as name, s.rowid as socid";
$sql.= " FROM ".MAIN_DB_PREFIX."commande as c,";
$sql.= " ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.fk_soc = s.rowid";
$sql.= " AND c.entity = ".$conf->entity;
$sql.= " AND c.fk_statut = 2";
if ($socid) $sql.= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

$resql = $db->query($sql);
if ( $resql )
{
	$langs->load("orders");

	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("OrdersInProcess").'</td></tr>';
		$var = True;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
			
		    $orderstatic->id=$obj->rowid;
			$orderstatic->ref=$obj->ref;
			$orderstatic->ref_customer=$obj->ref_customer;
			$orderstatic->statut=$obj->status;
            $orderstatic->facturee=$obj->billed;
			
            $companystatic->name=$obj->name;
			$companystatic->id=$obj->socid;
				
			$var=!$var;
			print "<tr ".$bc[$var]."><td>";
			print $orderstatic->getNomUrl(1);
			print '</td>';
			print '<td>';
			print $companystatic->getNomUrl(1,'customer');
			print '</td>';
            print '<td align="right">';
            print $orderstatic->getLibStatut(3);
            print '</td>';
            print '</tr>';
			$i++;
		}
		print "</table><br>";
	}
}
else dol_print_error($db);


/*
 * Last shipments
 */
$sql = "SELECT e.rowid, e.ref, e.ref_customer,";
$sql.= " s.nom as name, s.rowid as socid,";
$sql.= " c.ref as commande_ref, c.rowid as commande_id";
$sql.= " FROM ".MAIN_DB_PREFIX."expedition as e";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."element_element as el ON e.rowid = el.fk_target AND el.targettype = 'shipping' AND el.sourcetype IN ('commande')";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."commande as c ON el.fk_source = c.rowid AND el.sourcetype IN ('commande') AND el.targettype = 'shipping'";
$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = e.fk_soc";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON e.fk_soc = sc.fk_soc";
$sql.= " WHERE e.entity IN (".getEntity('expedition', 1).")";
if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND sc.fk_user = " .$user->id;
$sql.= " AND e.fk_statut = 1";
if ($socid) $sql.= " AND c.fk_soc = ".$socid;
$sql.= " ORDER BY e.date_delivery DESC";
$sql.= $db->plimit(5, 0);

$resql = $db->query($sql);
if ($resql)
{
	$num = $db->num_rows($resql);
	if ($num)
	{
		$i = 0;
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="3">'.$langs->trans("LastSendings",$num).'</td></tr>';
		$var = True;
		while ($i < $num)
		{
			$obj = $db->fetch_object($resql);
		    
			$shipment->id=$obj->rowid;
			$shipment->ref=$obj->ref;
			$shipment->ref_customer=$obj->ref_customer;
				
			$var=!$var;
			print '<tr '.$bc[$var].'><td>';
			print $shipment->getNomUrl(1);
			print '</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/comm/card.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->name.'</a></td>';
			print '<td>';
			if ($obj->commande_id > 0)
			{
				$orderstatic->id=$obj->commande_id;
				$orderstatic->ref=$obj->commande_ref;
				print $orderstatic->getNomUrl(1);
			}
			else print '&nbsp;';
			print '</td></tr>';
			$i++;
		}
		print "</table><br>";
	}
	$db->free($resql);
}
else dol_print_error($db);


print '</div></div></div>';


llxFooter();
$db->close();
