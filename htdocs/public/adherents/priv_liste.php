<?php
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("./pre.inc.php");

llxHeader();


if ($sortorder == "") {  $sortorder="ASC"; }
if ($sortfield == "") {  $sortfield="nom"; }

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "select rowid,prenom,nom, societe, cp,ville,email,naiss,photo from ".MAIN_DB_PREFIX."adherent where statut=1 ORDER BY  $sortfield $sortorder ". $db->plimit($conf->liste_limit, $offset);
//$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, cp, ville, d.email, t.libelle as type, d.morphy, d.statut, t.cotisation";
//$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
//$sql .= " WHERE d.fk_adherent_type = t.rowid AND d.statut = $statut";
//$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Liste des adhérents", $page, "priv_liste.php", "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
  print "<table class=\"noborder\" width=\"100%\">";

  print '<tr class="liste_titre">';
  print "<td><a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&sortorder=ASC&sortfield=d.prenom\">Prenom</a> <a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&sortorder=ASC&sortfield=d.nom\">Nom</a> / <a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&sortorder=ASC&sortfield=d.societe\">Société</a></td>\n";
  print_liste_field_titre("Date naissance","priv_liste.php","naiss","&page=$page");
  print_liste_field_titre("Email","priv_liste.php","email","&page=$page");
  print_liste_field_titre("CP","priv_liste.php","cp","&page=$page");
  print_liste_field_titre("Vile","priv_liste.php","ville","&page=$page");
  print "<td>Photo</td>\n";
  print "</tr>\n";
    
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
      print "<td><a href=\"priv_fiche.php?rowid=$objp->rowid\">".stripslashes($objp->prenom)." ".stripslashes($objp->nom)." / ".stripslashes($objp->societe)."</a></TD>\n";
      print "<td>$objp->naiss</td>\n";
      print "<td>$objp->email</td>\n";
      print "<td>$objp->cp</td>\n";
      print "<td>$objp->ville</td>\n";
      if (isset($objp->photo) && $objp->photo!= ''){
	print "<td><A HREF=\"$objp->photo\"><IMG SRC=\"$objp->photo\" HEIGHT=64 WIDTH=64></A></TD>\n";
      }else{
	print "<td>&nbsp;</td>\n";
      }
      print "</tr>";
      $i++;
    }
  print "</table>";
}
else
{
  dolibarr_print_error($db);
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
