<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	\file       htdocs/commande/index.php
 *	\ingroup    commande
 *	\brief      Page acceuil espace commandes
 *	\version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/html.formfile.class.php");
require_once(DOL_DOCUMENT_ROOT ."/notify.class.php");

if (!$user->rights->commande->lire) accessforbidden();

$langs->load("orders");

// Sécurité accés client
$socid='';
if ($_GET["socid"]) { $socid=$_GET["socid"]; }
if ($user->societe_id > 0)
{
	$action = '';
	$socid = $user->societe_id;
}



/*
 * View
 */

$commandestatic=new Commande($db);
$html = new Form($db);
$formfile = new FormFile($db);

llxHeader("",$langs->trans("Orders"),"Commande");

print_fiche_titre($langs->trans("OrdersArea"));

print '<table width="100%" class="notopnoleftnoright">';

print '<tr><td valign="top" width="30%" class="notopnoleft">';

/*
 * Zone recherche
 */
$var=false;
print '<table class="noborder" width="100%">';
print '<form method="post" action="liste.php">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchOrder").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="sf_ref" size=18></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="sall" size="18"></td>';
print '</tr>';
print "</form></table><br>\n";


/*
 * Commandes brouillons
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.rowid as socid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE c.fk_soc = s.rowid AND c.fk_statut = 0";
if ($socid) $sql .= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

if ( $db->query($sql) )
{
	$langs->load("orders");
	$num = $db->num_rows();
	if ($num)
	{
		$i = 0;
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="2">'.$langs->trans("DraftOrders").'</td></tr>';
		$var = True;
		while ($i < $num)
		{
			$var=!$var;
			$obj = $db->fetch_object();
			print "<tr $bc[$var]>";
			print '<td nowrap="nowrap">';
			print "<a href=\"fiche.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref."</a></td>";
			print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,24).'</a></td></tr>';
			$i++;
		}
		print "</table><br>";
	}
}

/*
 * Commandes à traiter
 */
$sql = "SELECT c.rowid, c.ref, s.nom, s.rowid as socid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .=" FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE c.fk_soc = s.rowid AND c.fk_statut = 1";
if ($socid) $sql .= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql .= " ORDER BY c.rowid DESC";

if ( $db->query($sql) )
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="2">'.$langs->trans("OrdersToProcess").'</td></tr>';

	$num = $db->num_rows();
	if ($num)
	{
		$i = 0;
		$var = True;
		while ($i < $num)
		{
			$var=!$var;
			$obj = $db->fetch_object();
			print "<tr $bc[$var]>";
			print '<td nowrap="nowrap">';

			$commandestatic->id=$obj->rowid;
			$commandestatic->ref=$obj->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="90" class="nobordernopadding" nowrap="nowrap">';
			print $commandestatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding" nowrap="nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding">';
			$filename=sanitizeFileName($obj->ref);
			$filedir=$conf->commande->dir_output . '/' . sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			$formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','','','',1);
			print '</td></tr></table>';

			print '</td>';
			print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dolibarr_trunc($obj->nom,24).'</a></td></tr>';
			$i++;
		}
	}

	print "</table><br>";
}


print '</td><td valign="top" width="70%" class="notopnoleftnoright">';


/*
 * Commandes en cours
 */
$sql = "SELECT c.rowid, c.ref, c.fk_statut, c.facture, s.nom, s.rowid as socid";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql .= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql .= " WHERE c.fk_soc = s.rowid AND c.fk_statut = 2 ";
if ($socid) $sql .= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql .= " ORDER BY c.rowid DESC";

if ( $db->query($sql) )
{
	$num = $db->num_rows();
	
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="3">'.$langs->trans("OnProcessOrders").' ('.$num.')</td></tr>';

	if ($num)
	{
		$i = 0;
		$var = True;
		while ($i < $num)
		{
			$var=!$var;
			$obj = $db->fetch_object();
			print "<tr $bc[$var]>";
			print '<td width="20%" nowrap="nowrap">';

			$commandestatic->id=$obj->rowid;
			$commandestatic->ref=$obj->ref;

			print '<table class="nobordernopadding"><tr class="nocellnopadd">';
			print '<td width="90" class="nobordernopadding" nowrap="nowrap">';
			print $commandestatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding" nowrap="nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding">';
			$filename=sanitizeFileName($obj->ref);
			$filedir=$conf->commande->dir_output . '/' . sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			$formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','','','',1);
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

/*
 * Dernières commandes traitées
 */
$max=5;

$sql = "SELECT c.rowid, c.ref, c.fk_statut, c.facture, s.nom, s.rowid as socid,";
$sql.= " ".$db->pdate("date_cloture")." as datec";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user";
$sql.= " FROM ".MAIN_DB_PREFIX."commande as c, ".MAIN_DB_PREFIX."societe as s";
if (!$user->rights->societe->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE c.fk_soc = s.rowid and c.fk_statut > 2";
if ($socid) $sql .= " AND c.fk_soc = ".$socid;
if (!$user->rights->societe->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " ORDER BY c.tms DESC";
$sql.= $db->plimit($max, 0);

$resql=$db->query($sql);
if ($resql)
{
	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre">';
	print '<td colspan="4">'.$langs->trans("LastClosedOrders",$max).'</td></tr>';

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
			print '<td width="90" class="nobordernopadding" nowrap="nowrap">';
			print $commandestatic->getNomUrl(1);
			print '</td>';

			print '<td width="16" class="nobordernopadding" nowrap="nowrap">';
			print '&nbsp;';
			print '</td>';

			print '<td width="16" align="right" class="nobordernopadding">';
			$filename=sanitizeFileName($obj->ref);
			$filedir=$conf->commande->dir_output . '/' . sanitizeFileName($obj->ref);
			$urlsource=$_SERVER['PHP_SELF'].'?id='.$obj->rowid;
			$formfile->show_documents('commande',$filename,$filedir,$urlsource,'','','','','',1);
			print '</td></tr></table>';

			print '</td>';

			print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.$obj->nom.'</a></td>';
			print '<td>'.dolibarr_print_date($obj->datec).'</td>';
			print '<td align="right">'.$commandestatic->LibStatut($obj->fk_statut,$obj->facture,5).'</td>';
			print '</tr>';
			$i++;
		}
	}
	print "</table><br>";
}




print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');

?>
