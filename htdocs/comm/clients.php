<?PHP
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socidp = $user->societe_id;
}

$page=$_GET["page"];
$begin=$_GET["begin"];
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $_GET["page"] ;
$pageprev = $_GET["page"] - 1;
$pagenext = $_GET["page"] + 1;


$sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm ";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st ";
$sql .= " WHERE s.fk_stcomm = st.id AND s.client=1";

if ($socidp)
{
  $sql .= " AND s.idp = $socidp";
}

if ($_GET["search_nom"])
{
  $sql .= " AND s.nom like '%".strtolower($_GET["search_nom"])."%'";
}

if (strlen($begin))
{
  $sql .= " AND upper(s.nom) like '$begin%'";
}

if ($user->societe_id)
{
  $sql .= " AND s.idp = " .$user->societe_id;
}

if ($socname)
{
  $sql .= " AND lower(s.nom) like '%".strtolower($socname)."%'";
  $sortfield = "lower(s.nom)";
  $sortorder = "ASC";
}

if ($sortorder == "")
{
  $sortorder="DESC";
}
if ($sortfield == "")
{
  $sortfield="s.datec";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit +1, $offset);

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  /*
  if ($num == 1)
    {
      $obj = $db->fetch_object(0);
      Header("Location: fiche.php?socid=$obj->idp");
    }
  else
    {
      llxHeader();
    }
  */
  llxHeader();


  print_barre_liste("Liste des clients", $page, "clients.php","&amp;begin=$begin",$sortfield,$sortorder,"",$num);

  print '<div align="center">';

  print "| <A href=\"clients.php?page=$pageprevamp;stcomm=$stcommamp;sortfield=$sortfieldamp;sortorder=$sortorderamp;aclasser=$aclasseramp;coord=$coord&amp;begin=$begin\">*</A>\n| ";
  for ($ij = 65 ; $ij < 91; $ij++) {
    print "<A href=\"clients.php?begin=" . chr($ij) . "&amp;stcomm=$stcomm\" class=\"T3\">";
    
    if ($begin == chr($ij) )
      {
	print  "<b>&gt;" . chr($ij) . "&lt;</b>" ; 
      } 
    else
      {
	print  chr($ij);
      } 
    print "</a> | ";
  }
  print "</div>";

  $i = 0;
  
  print '<table class="liste">';
  print '<tr class="liste_titre">';
  print "<td valign=\"right\">";

  $addu = "&amp;page=$page&amp;begin=$begin&amp;search_nom=".$_GET["search_nom"];

  print_liste_field_titre("Société","clients.php","s.nom",$addu);
  print "</td><td>";
  print_liste_field_titre("Ville","clients.php","s.ville",$addu);
  print "</td>";
  print "</tr>\n";

  print '<form method="get" action="clients.php">';
  print '<tr class="liste_titre">';
  print '<td valign="right">';
  print '<input type="text" name="search_nom" value="'.$_GET["search_nom"].'">';
  print "</td><td>&nbsp;";
  print "</td>";
  print "</tr>\n";

  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;

      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?socid='.$obj->idp.'">';
      print img_file();
      print "</a>&nbsp;<a href=\"fiche.php?socid=$obj->idp\">$obj->nom</A></td>\n";
      print "<td>".$obj->ville."&nbsp;</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
