<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
	    \file       htdocs/compta/dons/liste.php
        \ingroup    don
		\brief      Page de liste des dons
		\version    $Revision$
*/

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT ."/don.class.php");

$langs->load("companies");
$langs->load("donations");

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$statut=isset($_GET["statut"])?$_GET["statut"]:"-1";
$page=$_GET["page"];

if (! $sortorder) {  $sortorder="DESC"; }
if (! $sortfield) {  $sortfield="d.datedon"; }
if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;


/*
 * Affichage
 */
 
llxHeader();

$donstatic=new Don($db);

// Genere requete de liste des dons
$sql = "SELECT d.rowid, ".$db->pdate("d.datedon")." as datedon, d.prenom, d.nom, d.societe,";
$sql.= " d.amount, d.fk_statut as statut, ";
$sql.= " p.libelle as projet";
$sql.= " FROM ".MAIN_DB_PREFIX."don as d LEFT JOIN ".MAIN_DB_PREFIX."don_projet AS p";
$sql.= " ON p.rowid = d.fk_don_projet WHERE 1 = 1";
if ($statut >= 0)
{
  $sql .= " AND d.fk_statut = ".$statut;
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows($result);
  $i = 0;
  
  if ($statut >= 0)
    {
      print_barre_liste($libelle[$statut], $page, "liste.php", "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
    }
  else 
    {
      print_barre_liste($langs->trans("Donation"), $page, "liste.php", "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
    }
  print "<table class=\"noborder\" width=\"100%\">";

  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"),"liste.php","d.rowid","&page=$page&statut=$statut","","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Firstname"),"liste.php","d.prenom","&page=$page&statut=$statut","","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Name"),"liste.php","d.nom","&page=$page&statut=$statut","","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Company"),"liste.php","d.societe","&page=$page&statut=$statut","","",$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Date"),"liste.php","d.datedon","&page=$page&statut=$statut","",'align="center"',$sortfield,$sortorder);
  if ($conf->projet->enabled)
  {
    $langs->load("projects");
    print_liste_field_titre($langs->trans("Project"),"liste.php","projet","&page=$page&statut=$statut","","",$sortfield,$sortorder);
  }
  print_liste_field_titre($langs->trans("Amount"),"liste.php","d.amount","&page=$page&statut=$statut","",'align="right"',$sortfield,$sortorder);
  print_liste_field_titre($langs->trans("Status"),"liste.php","d.statut","&page=$page&statut=$statut","",'align="right"',$sortfield,$sortorder);
  print "</tr>\n";
    
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object($result);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"fiche.php?rowid=$objp->rowid\">".$objp->rowid."</a></td>\n";
      print "<td>".stripslashes($objp->prenom)."</td>\n";
      print "<td>".stripslashes($objp->nom)."</td>\n";
      print "<td>".stripslashes($objp->societe)."</td>\n";
      print '<td align="center">'.dol_print_date($objp->datedon).'</td>';
      if ($conf->projet->enabled) {
          print "<td>$objp->projet</td>\n";
      }
      print '<td align="right">'.price($objp->amount).'</td>';
      print '<td align="right">'.$donstatic->LibStatut($objp->statut,5).'</td>';

      print "</tr>";
      $i++;
    }
  print "</table>";
}
else
{
  dol_print_error($db);
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
