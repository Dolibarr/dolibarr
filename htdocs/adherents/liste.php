<?PHP
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

require("./adherent.class.php");


llxHeader();

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

if ($sortorder == "") {  $sortorder="ASC"; }
if ($sortfield == "") {  $sortfield="d.nom"; }

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;

$pageprev = $page - 1;
$pagenext = $page + 1;

if (! isset($_GET["statut"]))
{
  $statut = 1 ;
}

$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, ".$db->pdate("d.datefin")." as datefin";
$sql .= " , d.email, t.libelle as type, d.morphy, d.statut, t.cotisation";
$sql .= " FROM ".MAIN_DB_PREFIX."adherent as d, ".MAIN_DB_PREFIX."adherent_type as t";
$sql .= " WHERE d.fk_adherent_type = t.rowid AND d.statut = $statut";
if ( $_POST["action"] == 'search')
{
  if (isset($_POST['search']) && $_POST['search'] != ''){
    $sql .= " AND (d.prenom LIKE '%".$_POST['search']."%' OR d.nom LIKE '%".$_POST['search']."%')";
  }
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  $titre="Liste des adhérents";
  if (isset($_GET["statut"]) && $_GET["statut"] == -1) { $titre="Liste des adhérents à valider"; }
  if (isset($_GET["statut"]) && $_GET["statut"] == 1) { $titre="Liste des adhérents valides"; }
  if (isset($_GET["statut"]) && $_GET["statut"] == 0) { $titre="Liste des adhérents résiliés"; }

  print_barre_liste($titre, $page, $PHP_SELF, "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
  print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";

  print '<tr class="liste_titre">';

  print '<td>';
  //  print_liste_field_titre("Prenom",$PHP_SELF,"d.prenom","&page=$page&statut=$statut");
  print_liste_field_titre("Prenom Nom / Société",$PHP_SELF,"d.nom","&page=$page&statut=$statut");
  print "</td>\n";

  print "<td>";
  print_liste_field_titre("Date cotisation",$PHP_SELF,"t.cotisation","&page=$page&statut=$statut");
  print "</td>\n";

  print "<td>";
  print_liste_field_titre("Email",$PHP_SELF,"d.email","&page=$page&statut=$statut");
  print "</td>\n";

  print "<td>";
  print_liste_field_titre("Type",$PHP_SELF,"t.libelle","&page=$page&statut=$statut");
  print "</td>\n";

  print "<td>";
  print_liste_field_titre("Personne",$PHP_SELF,"d.morphy","&page=$page&statut=$statut");
  print "</td>\n";

  print "<td>";
  print_liste_field_titre("Statut",$PHP_SELF,"d.statut","&page=$page&statut=$statut");
  print "</td>\n";

  print "<td>Action</td>\n";
  print "</tr>\n";
    
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object($i);

      $adh=new Adherent($db);
      
      $var=!$var;
      print "<tr $bc[$var]>";
      if ($objp->societe != ''){
	print "<td><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->prenom)." ".stripslashes($objp->nom)." / ".stripslashes($objp->societe)."</a></td>\n";
      }else{
	print "<td><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->prenom)." ".stripslashes($objp->nom)."</a></td>\n";
      }
      print "<td>";
      if ($objp->cotisation == 'yes')
	{
	  if ($objp->datefin < time())
	    {
	      print dolibarr_print_date($objp->datefin)." - Cotisation non recue ".img_warning()."</td>\n";
	    }
	  else 
	    {
	      print dolibarr_print_date($objp->datefin)."</td>\n";
	    }
	}
      else 
	{
	  print "&nbsp;</td>";
	}

      print "<td>$objp->email</td>\n";
      print "<td>$objp->type</td>\n";
      print "<td>".$adh->getmorphylib($objp->morphy)."</td>\n";
      print "<td>";

      if ($objp->statut == -1)
	{
	  print '<a href="fiche.php?rowid='.$objp->rowid.'">A valider</a>';
	}
      if ($objp->statut == 0)
	{
	  print 'Résilié';
	}
      if ($objp->statut == 1)
	{
	  print 'Validé';
	}

      print "</td>";
      print "<td><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".img_edit()."</a> &nbsp; ";
      print "<a href=\"fiche.php?rowid=$objp->rowid&action=resign\">".img_disable("Résilier")."</a> &nbsp; <a href=\"fiche.php?rowid=$objp->rowid&action=delete\">".img_delete()."</a></td>\n";
      print "</tr>";
      $i++;
    }
  print "</table><br>\n";
  print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
  
  print '<tr>';
  print '<td align="right">';
  print_fleche_navigation($page,$PHP_SELF,"&statut=$statut&sortorder=$sortorder&sortfield=$sortfield",1);
  print '</td>';
  print "</table><br>\n";

}
else
{
  print $sql;
  print $db->error();
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
