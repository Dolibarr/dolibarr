<?PHP
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *	 \file       htdocs/fourn/commande/index.php
 *	 \ingroup    commande
 *	 \brief      Page accueil commandes fournisseurs
 *   \version    $Revision$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");

// Security check
$orderid = isset($_GET["orderid"])?$_GET["orderid"]:'';
if ($user->societe_id) $socid=$user->societe_id;
$result = restrictedArea($user, 'commande_fournisseur', $orderid,'');


/*
* 	View
*/

llxHeader('',$langs->trans("SuppliersOrdersArea"));

$commande = new CommandeFournisseur($db);
$userstatic=new User($db);

print_barre_liste($langs->trans("SuppliersOrdersArea"), $page, "index.php", "", $sortfield, $sortorder, '', $num);

print '<table class="notopnoleftnoright" width="100%">';
print '<tr valign="top"><td class="notopnoleft" width="30%">';


/*
 * Search form
 */
$var=false;
print '<table class="noborder" width="100%">';
print '<form method="post" action="liste.php">';
print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
print '<tr class="liste_titre"><td colspan="3">'.$langs->trans("SearchOrder").'</td></tr>';
print '<tr '.$bc[$var].'><td>';
print $langs->trans("Ref").':</td><td><input type="text" class="flat" name="search_ref" size=18></td><td rowspan="2"><input type="submit" value="'.$langs->trans("Search").'" class="button"></td></tr>';
print '<tr '.$bc[$var].'><td nowrap>'.$langs->trans("Other").':</td><td><input type="text" class="flat" name="search_all" size="18"></td>';
print '</tr>';
print "</form></table><br>\n";


/*
 * Legends / Status
 */

$sql = "SELECT count(cf.rowid), fk_statut";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s";
$sql.= ", ".MAIN_DB_PREFIX."commande_fournisseur as cf";
if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE cf.fk_soc = s.rowid";
$sql.= " AND s.entity = ".$conf->entity;
if ($user->societe_id) $sql.=' AND cf.fk_soc = '.$user->societe_id;
if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
$sql.= " GROUP BY cf.fk_statut";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  print '<table class="liste" width="100%">';

  print '<tr class="liste_titre"><td>'.$langs->trans("Status").'</td>';
  print '<td align="right">'.$langs->trans("Nb").'</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $row = $db->fetch_row($resql);
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>'.$commande->statuts[$row[1]].'</td>';
      print '<td align="right"><a href="liste.php?statut='.$row[1].'">'.$row[0].' '.$commande->LibStatut($row[1],3).'</a></td>';

      print "</tr>\n";
      $i++;
    }
  print "</table><br>";
  $db->free($resql);
}
else
{
  dol_print_error($db);
}

/*
 * Commandes brouillons
 */
if ($conf->fournisseur->enabled)
{
	$sql = "SELECT c.rowid, c.ref, s.nom, s.rowid as socid";
	$sql.= " FROM ".MAIN_DB_PREFIX."commande_fournisseur as c";
	$sql.= ", ".MAIN_DB_PREFIX."societe as s";
	if (!$user->rights->societe->client->voir && !$socid) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
	$sql.= " WHERE c.fk_soc = s.rowid";
	$sql.= " AND c.entity = ".$conf->entity;
	$sql.= " AND c.fk_statut = 0";
	if ($socid) $sql.= " AND c.fk_soc = ".$socid;
	if (!$user->rights->societe->client->voir && !$socid) $sql.= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;

	if ( $db->query($sql) )
	{
		print '<table class="noborder" width="100%">';
		print '<tr class="liste_titre">';
		print '<td colspan="2">'.$langs->trans("DraftOrders").'</td></tr>';
		$langs->load("orders");
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
				print "<a href=\"fiche.php?id=".$obj->rowid."\">".img_object($langs->trans("ShowOrder"),"order").' '.$obj->ref."</a></td>";
				print '<td><a href="'.DOL_URL_ROOT.'/comm/fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowCompany"),"company").' '.dol_trunc($obj->nom,24).'</a></td></tr>';
				$i++;
			}
		}
		print "</table><br>";
	}
}


print '</td><td width="70%" valign="top" class="notopnoleft">';


$sql = "SELECT u.rowid, u.name, u.firstname";
$sql.= " FROM ".MAIN_DB_PREFIX."user as u,";
$sql.= " ".MAIN_DB_PREFIX."user_rights as ur";
$sql.= ", ".MAIN_DB_PREFIX."rights_def as rd";
$sql.= " WHERE u.rowid = ur.fk_user";
$sql.= " AND (u.entity IN (0,".$conf->entity.")";
$sql.= " AND rd.entity = ".$conf->entity.")";
$sql.= " AND ur.fk_id = rd.id";
$sql.= " AND module = 'fournisseur'";
$sql.= " AND perms = 'commande'";
$sql.= " AND subperms = 'approuver'";

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;

  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre"><td>'.$langs->trans("UserWithApproveOrderGrant").'</td>';
  print "</tr>\n";
  $var=True;

  while ($i < $num)
    {
      $obj = $db->fetch_object($resql);
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td>';
      $userstatic->id=$obj->rowid;
      $userstatic->nom=$obj->name;
      $userstatic->prenom=$obj->firstname;
      print $userstatic->getNomUrl(1);
      print '</td>';
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else
{
  dol_print_error($db);
}

print '</td></tr></table>';

$db->close();

llxFooter('$Date$ - $Revision$');
?>
