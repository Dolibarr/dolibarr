<?php
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

/**
   \file       htdocs/fourn/index.php
   \ingroup    fournisseur
   \brief      Page accueil de la zone fournisseurs
   \version    $Revision$
*/

require("./pre.inc.php");

if (!$user->rights->societe->lire)
  accessforbidden();


$page = $_GET["page"];
$sortorder = $_GET["sortorder"];
$sortfield = $_GET["sortfield"];

$langs->load("suppliers");
$langs->load("orders");
$langs->load("companies");

llxHeader();

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="nom";


/*
 * Mode Liste
 *
 */

$sql = "SELECT s.idp, s.nom, s.ville,".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st";
$sql.= " WHERE s.fk_stcomm = st.id AND s.fournisseur=1";
if ($socidp) $sql .= " AND s.idp=$socidp";
if ($socname) {
  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
  $sortfield = "lower(s.nom)";
  $sortorder = "ASC";
}
if (strlen($_GET["search_nom"]))
{
  $sql .= " AND s.nom LIKE '%".$_GET["search_nom"]."%'";
}
if (strlen($_GET["search_ville"]))
{
  $sql .= " AND s.ville LIKE '%".$_GET["search_ville"]."%'";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  print_barre_liste($langs->trans("ListOfSuppliers"), $page, "index.php", "", $sortfield, $sortorder, '', $num);

  print '<table class="liste" width="100%">';
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Company"),"index.php","s.nom","","",'valign="middle"',$sortfield);
  print_liste_field_titre($langs->trans("Town"),"index.php","s.ville","","",'valign="middle"',$sortfield);
  print_liste_field_titre($langs->trans("DateCreation"),"index.php","datec","","",'align="center"',$sortfield);
  print '<td class="liste_titre">&nbsp;</td>';
  print "</tr>\n";

  print '<tr class="liste_titre">';
  print '<form action="index.php" method="GET">';
  print '<td class="liste_titre"><input type="text" class="flat" name="search_nom" value="'.$_GET["search_nom"].'"></td>';
  print '<td class="liste_titre"><input type="text" class="flat" name="search_ville" value="'.$_GET["search_ville"].'"></td>';
  print '<td class="liste_titre" colspan="2" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'"></td>';
  print '</form>';
  print '</tr>';

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($resql);	
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?socid='.$obj->idp.'">'.img_object($langs->trans("ShowSupplier"),"company").'</a>';
      print "&nbsp;<a href=\"fiche.php?socid=$obj->idp\">$obj->nom</a></td>\n";
      print "<td>".$obj->ville."</td>\n";       
      print '<td align="center">'.dolibarr_print_date($obj->datec).'</td>';
      print "<td>&nbsp;</td>\n";       
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
