<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *                         Jean-Louis Bergamo <jlb@j1b.org>
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

$db = new Db();

if ($sortorder == "") {  $sortorder="DESC"; }
if ($sortfield == "") {  $sortfield="d.nom"; }

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

if (! isset($statut))
{
  $statut = 1 ;
}

$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, ".$db->pdate("d.datefin")." as datefin";
$sql .= " , d.email, t.libelle as type, d.morphy, d.statut, t.cotisation";
$sql .= " FROM llx_adherent as d, llx_adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid AND d.statut = $statut";
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Liste des adhérents", $page, $PHP_SELF, "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
  print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";

  print '<TR class="liste_titre">';
  print "<td><a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&statut=$statut&sortorder=ASC&sortfield=d.prenom\">Prenom</a> <a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&statut=$statut&sortorder=ASC&sortfield=d.nom\">Nom</a> / <a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&statut=$statut&sortorder=ASC&sortfield=d.societe\">Société</a></td>\n";
  print "<td><a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&statut=$statut&sortorder=DESC&sortfield=d.datefin\">Date Cotisation</a></td>\n";
  print "<td><a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&statut=$statut&sortorder=ASC&sortfield=d.email\">Email</a></td>\n";
  print "<td><a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&statut=$statut&sortorder=ASC&sortfield=t.libelle\">Type</a></td>\n";
  print "<td><a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&statut=$statut&sortorder=ASC&sortfield=d.morphy\">Personne</a></td>\n";
  print "<td><a href=\"".$_SERVER['SCRIPT_NAME'] . "?page=$page&statut=$statut&sortorder=ASC&sortfield=d.statut\">Statut</a></td>\n";
  print "<td>Action</td>\n";
  print "</TR>\n";
    
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->prenom)." ".stripslashes($objp->nom)." / ".stripslashes($objp->societe)."</a></TD>\n";
      print "<TD>";
      if ($objp->cotisation == 'yes')
	{
	  if ($objp->datefin < time())
	    {
	      print "<b><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".strftime("%d %B %Y",$objp->datefin)."</a> - Cotisation non recue</b></td>\n";
	    }
	  else 
	    {
	      print "<a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".strftime("%d %B %Y",$objp->datefin)."</a></td>\n";
	    }
	}
      else 
	{
	  print "&nbsp;</td>";
	}

      print "<TD>$objp->email</TD>\n";
      print "<TD>$objp->type</TD>\n";
      print "<TD>$objp->morphy</TD>\n";
      print "<td>";
      if ($objp->statut == -1)
	{
	  print '<a href="fiche.php?rowid='.$objp->rowid.'">A valider</a>';
	}
      print "</td>";
      print "<TD><a href=\"edit.php?rowid=$objp->rowid\">Editer</a><br><a href=\"fiche.php?rowid=$objp->rowid&action=resign\">Resilier</a><br><a href=\"fiche.php?rowid=$objp->rowid&action=delete\">Supprimer</a></TD>\n";
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
