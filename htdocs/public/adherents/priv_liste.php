<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2002-2003 Jean-Louis Bergamo <jlb@j1b.org>
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

//$db = new Db();

if ($sortorder == "") {  $sortorder="ASC"; }
if ($sortfield == "") {  $sortfield="nom"; }

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "select rowid,prenom,nom, societe, cp,ville,email,naiss,photo from llx_adherent where statut=1 ORDER BY  $sortfield $sortorder ". $db->plimit($conf->liste_limit, $offset);
//$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, cp, ville, d.email, t.libelle as type, d.morphy, d.statut, t.cotisation";
//$sql .= " FROM llx_adherent as d, llx_adherent_type as t";
//$sql .= " WHERE d.fk_adherent_type = t.rowid AND d.statut = $statut";
//$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Liste des adhérents", $page, $PHP_SELF, "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

  print '<TR class="liste_titre">';


  print "<td><a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&sortorder=ASC&sortfield=d.prenom\">Prenom</a> <a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&sortorder=ASC&sortfield=d.nom\">Nom</a> / <a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&sortorder=ASC&sortfield=d.societe\">Société</a></td>\n";

  print "<td>";
  print_liste_field_titre("Date naissance",$PHP_SELF,"naiss","&page=$page");
  print "</td>\n";

  print "<td>";
  print_liste_field_titre("Email",$PHP_SELF,"email","&page=$page");
  print "</td>\n";

  print "<td>";
  print_liste_field_titre("CP",$PHP_SELF,"cp","&page=$page");
  print "</td>\n";

  print "<td>";
  print_liste_field_titre("Vile",$PHP_SELF,"ville","&page=$page");
  print "</td>\n";

  print "<td>Photo</td>\n";
  print "</TR>\n";
    
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"priv_fiche.php?rowid=$objp->rowid\">".stripslashes($objp->prenom)." ".stripslashes($objp->nom)." / ".stripslashes($objp->societe)."</a></TD>\n";
      print "<TD>$objp->naiss</TD>\n";
      print "<TD>$objp->email</TD>\n";
      print "<TD>$objp->cp</TD>\n";
      print "<TD>$objp->ville</TD>\n";
      if (isset($objp->photo) && $objp->photo!= ''){
	print "<TD><A HREF=\"$objp->photo\"><IMG SRC=\"$objp->photo\" HEIGHT=64 WIDTH=64></A></TD>\n";
      }else{
	print "<TD>&nbsp;</TD>\n";
      }
      print "</tr>";
      $i++;
    }
  print "</table>";
}
else
{
  print $sql;
  print $db->error();
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
