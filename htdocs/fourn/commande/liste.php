<?PHP
/* Copyright (C) 2001-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *
 * $Id$
 * $Source$
 *
 */

/*! 
  \file       htdocs/fourn/commande/liste.php
  \ingroup    commande
  \brief      Liste des commandes fournisseurs
  \version    $Revision$
*/

require("./pre.inc.php");

$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];
$socid = $_GET["socid"];

if (!$user->rights->fournisseur->commande->lire) accessforbidden();

llxHeader('','Liste des commandes fournisseurs');

/*
 * Sécurité accés client/fournisseur
 */
if ($user->societe_id > 0) $socid = $user->societe_id;

if ($sortorder == "") $sortorder="DESC";
if ($sortfield == "") $sortfield="cf.date_creation";
$offset = $conf->liste_limit * $page ;


/*
 * Mode Liste
 */

$sql = "SELECT s.idp, s.nom, ".$db->pdate("cf.date_commande")." as dc";
$sql .= " ,cf.rowid,cf.ref, cf.fk_statut";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s ";
$sql .= " , ".MAIN_DB_PREFIX."commande_fournisseur as cf";
$sql .= " WHERE cf.fk_soc = s.idp ";

if ($socid)
{
  $sql .= " AND s.idp=$socid";
}

if (strlen($_GET["statut"]))
{
  $sql .= " AND fk_statut =".$_GET["statut"];
}

if (strlen($_GET["search_ref"]))
{
  $sql .= " AND cf.ref LIKE '%".$_GET["search_ref"]."%'";
}

if (strlen($_GET["search_nom"]))
{
  $sql .= " AND s.nom LIKE '%".$_GET["search_nom"]."%'";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  

  print_barre_liste($langs->trans("SuppliersOrders"), $page, "liste.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="liste">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"),$_SERVER["PHP_SELF"],"cf.ref");
  print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom");
  print_liste_field_titre($langs->trans("OrderDate"),$_SERVER["PHP_SELF"],"dc");
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="liste.php" method="GET">';
  print '<td><input type="text" name="search_ref" value="'.$_GET["search_ref"].'"></td>';
  print '<td><input type="text" name="search_nom" value="'.$_GET["search_nom"].'"></td>';
  print '<td><input class="button" type="submit" value="'.$langs->trans("Search").'"></td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($resql);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><img src="statut'.$obj->fk_statut.'.png">';
      print '&nbsp;<a href="'.DOL_URL_ROOT.'/fourn/commande/fiche.php?id='.$obj->rowid.'">'.$obj->ref.'</a></td>'."\n";
      print '<td><a href="'.DOL_URL_ROOT.'/fourn/fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowCompany"),"company").' ';
      print $obj->nom.'</a></td>'."\n";

      print "<td align=\"right\" width=\"100\">";
      if ($obj->dc)
	{
	  print dolibarr_print_date($obj->dc,"%e %b %Y");
	}
      else
	{
	  print "-";
	}
      print '</td>';
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free($resql);
}
else 
{
  dolibarr_print_error($db);
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
