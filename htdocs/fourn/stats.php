<?php
/* Copyright (C) 2001-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
        \file       htdocs/fourn/stats.php
        \ingroup    fournisseur
        \brief      Page stats fournisseurs
        \version    $Revision$
*/

require("./pre.inc.php");

$user->getrights();

$langs->load("suppliers");
$langs->load("orders");
$langs->load("companies");

if (!$user->rights->societe->lire)
  accessforbidden();


$page = isset($_GET["page"])?$_GET["page"]:'';
$sortorder = isset($_GET["sortorder"])?$_GET["sortorder"]:'';
$sortfield = isset($_GET["sortfield"])?$_GET["sortfield"]:'';
$socname = isset($_GET["socname"])?$_GET["socname"]:'';
$search_nom = isset($_GET["search_nom"])?$_GET["search_nom"]:'';
$search_ville = isset($_GET["search_ville"])?$_GET["search_ville"]:'';

// Sécurité accés client
$socid='';
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
if (! $sortorder) $sortorder="ASC";
if (! $sortfield) $sortfield="year";

$years=array();
$fourns=array();


/*
 * Affichage liste
 *
 */

llxHeader();

$sql = "SELECT s.rowid as socid, s.nom, s.ville, ca.ca_genere as ca, ca.year";
$sql.= " , code_fournisseur, code_compta_fournisseur";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", sc.fk_soc, sc.fk_user ";
$sql.= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st, ".MAIN_DB_PREFIX."fournisseur_ca as ca";
if ($_GET["cat"]) $sql .= ", ".MAIN_DB_PREFIX."categorie_fournisseur as cf";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
$sql.= " WHERE s.fk_stcomm = st.id AND s.fournisseur=1 AND s.rowid = ca.fk_societe";
if ($_GET["cat"]) $sql .= " AND cf.fk_societe = s.rowid AND cf.fk_categorie = '".$_GET["cat"]."'";
if (!$user->rights->commercial->client->voir && !$socid) $sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id;
if ($socid) $sql .= " AND s.rowid = ".$socid;
if ($socname) {
  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
  $sortfield = "lower(s.nom)";
  $sortorder = "ASC";
}
if ($search_nom)
{
  $sql .= " AND s.nom LIKE '%".$search_nom."%'";
}
if ($search_ville)
{
  $sql .= " AND s.ville LIKE '%".$search_ville."%'";
}
$sql .= " AND ca.year > (date_format(now(),'%Y') - 5)";

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit+1, $offset);

dolibarr_syslog("fourn/stats.php sql=".$sql);
$resql = $db->query($sql);
if ($resql)
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object($resql);	
      $var=!$var;
      $i++;

      $fourns[$obj->socid] = $obj->nom;
      $years[$obj->year] = $obj->year;
      $ca[$obj->socid][$obj->year] = $obj->ca;
    }

}
else 
{
  dolibarr_print_error($db);
}


print_barre_liste($langs->trans("SuppliersProductsSellSalesTurnover"), $page, "stats.php", "", $sortfield, $sortorder, '', $num);

print '<form action="stats.php" method="GET">';
print '<table class="liste" width="100%">';
print '<tr class="liste_titre">';
print_liste_field_titre($langs->trans("Company"),$_SERVER["PHP_SELF"],"s.nom","","",'valign="middle"',$sortfield,$sortorder);
print_liste_field_titre($langs->trans("Town"),$_SERVER["PHP_SELF"],"s.ville","","",'valign="middle"',$sortfield,$sortorder);
print '<td class="liste_titre">&nbsp;</td>';

foreach($years as $year)
{
  print  '<td align="right" class="liste_titre">'.$langs->trans("CA") .' '.$year.'</td>';
}
print '<td align="left" class="liste_titre">&nbsp;</td>';
print "</tr>\n";

print '<tr class="liste_titre">';

print '<td class="liste_titre"><input type="text" class="flat" name="search_nom" value="'.$search_nom.'"></td>';
print '<td class="liste_titre"><input type="text" class="flat" name="search_ville" value="'.$search_ville.'"></td>';

foreach($years as $year)
{
  print '<td align="left" class="liste_titre">&nbsp;';
  print '</td>';
}

print '<td class="liste_titre" colspan="2" align="right"><input class="liste_titre" type="image" src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/search.png" alt="'.$langs->trans("Search").'"></td>';

print '</tr>';
$var=True;

foreach($fourns as $fid => $fnom)
{
  $var=!$var;
  
  print "<tr $bc[$var]>";
  print '<td><a href="fiche.php?socid='.$obj->socid.'">'.img_object($langs->trans("ShowSupplier"),"company").'</a>';
  print "&nbsp;<a href=\"fiche.php?socid=".$fid."\">".$fnom."</a></td>\n";
  print "<td>".$obj->ville."</td>\n";       
  print '<td align="left">'.$obj->code_fournisseur.'&nbsp;</td>';
  
  foreach($years as $year)
    {
      print '<td align="right">'.price($ca[$fid][$year]).'&nbsp;</td>';
    }
  print '<td align="right">&nbsp;</td>';
  print "</tr>\n";
}

print "</table>\n";
print "</form>\n";
$db->free($resql);











$db->close();

llxFooter('$Date$ - $Revision$');
?>
