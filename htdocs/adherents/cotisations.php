<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003 Jean-Louis Bergamo <jlb@j1b.org>
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

if ($action == 'add') {
  $datepaye = $db->idate(mktime(12, 0 , 0, $pmonth, $pday, $pyear));

  $paiement = new Paiement($db);

  $paiement->facid        = $facid;  
  $paiement->datepaye     = $datepaye;
  $paiement->amount       = $amount;
  $paiement->author       = $author;
  $paiement->paiementid   = $paiementid;
  $paiement->num_paiement = $num_paiement;
  $paiement->note         = $note;

  $paiement->create();

  $action = '';

}


if ($sortorder == "") {  $sortorder="DESC"; }
if ($sortfield == "") {  $sortfield="c.dateadh"; }

if ($page == -1) { $page = 0 ; }

$offset = $conf->liste_limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT c.cotisation, ".$db->pdate("c.dateadh")." as dateadh";
$sql .= " FROM llx_adherent as d, llx_cotisation as c";
$sql .= " WHERE d.rowid = c.fk_adherent";
if(isset($date_select) && $date_select != ''){
  $sql .= " AND dateadh LIKE '$date_select%'";
}
$result = $db->query($sql);
$Total=array();
$Number=array();
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $Total[strftime("%Y",$objp->dateadh)]+=price($objp->cotisation);
      $Number[strftime("%Y",$objp->dateadh)]+=1;
      $i++;
    }
}
$sql = "SELECT d.rowid, d.prenom, d.nom, d.societe, c.cotisation, ".$db->pdate("c.dateadh")." as dateadh";
$sql .= " FROM llx_adherent as d, llx_cotisation as c";
$sql .= " WHERE d.rowid = c.fk_adherent";
if(isset($date_select) && $date_select != ''){
  $sql .= " AND dateadh LIKE '$date_select%'";
}
$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit($conf->liste_limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  $i = 0;
  
  print_barre_liste("Liste des cotisations", $page, $PHP_SELF, "&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");

  print "<TABLE border=\"0\" cellspacing=\"0\" cellpadding=\"4\">\n";
  print '<TR class="liste_titre">';
  print "<td>Annee</td>";
  print '<td align="right">Montant</TD>';
  print "<td align=\"right\">Nombre</TD>";
  print "<td align=\"right\">Moyenne</TD>\n";
  print "</TR>\n";

  foreach ($Total as $key=>$value){
    $var=!$var;
    print "<TR $bc[$var]><TD><A HREF=\"$PHP_SELF?statut=$statut&date_select=$key\">$key</A></TD><TD align=\"right\">".price($value)."</TD><TD align=\"right\">".$Number[$key]."</TD><TD align=\"right\">".price($value/$Number[$key])."</TD></TR>\n";
  }
  print "</table><BR>\n";

  print "<TABLE border=\"0\" cellspacing=\"0\" cellpadding=\"4\">";

  print '<TR class="liste_titre">';
  //print "<td>Date</td>";
  print '<TD>';
  print_liste_field_titre("Date",$PHP_SELF,"c.dateadh","&page=$page&statut=$statut");
  print "</TD>\n";

  //print "<td align=\"right\">Montant</TD>";
  print '<TD>';
  print_liste_field_titre("Montant",$PHP_SELF,"c.cotisation","&page=$page&statut=$statut");
  print "</TD>\n";

  //print "<td>Prenom Nom / Société</td>";
  print '<TD>';
  //  print_liste_field_titre("Prenom",$PHP_SELF,"d.prenom","&page=$page&statut=$statut");
  print_liste_field_titre("Prenom Nom",$PHP_SELF,"d.nom","&page=$page&statut=$statut");
  print " / Société";
  print "</TD>\n";

  print "</TR>\n";
    
  $var=True;
  $total=0;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".strftime("%d %B %Y",$objp->dateadh)."</a></td>\n";
      print '<TD align="right">'.price($objp->cotisation).'</TD>';
      //$Total[strftime("%Y",$objp->dateadh)]+=price($objp->cotisation);
      $total+=price($objp->cotisation);
      if ($objp->societe != ''){
	print "<TD><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->prenom)." ".stripslashes($objp->nom)." / ".stripslashes($objp->societe)."</a></TD>\n";
      }else{
	print "<TD><a href=\"fiche.php?rowid=$objp->rowid&action=edit\">".stripslashes($objp->prenom)." ".stripslashes($objp->nom)."</a></TD>\n";
      }
      print "</tr>";
      $i++;
    }
  $var=!$var;
  print "<TR $bc[$var]>";
  print "<TD>Total</TD>\n";
  print "<TD align=\"right\">".price($total)."</TD>\n";
  //  print "<TD>&nbsp;</TD>\n";
  print "<TD align=\"right\">";
  print_fleche_navigation($page,$PHP_SELF,"&statut=$statut&sortorder=$sortorder&sortfield=$sortfield");
  print "</TD>\n";

  print "</TR>\n";
  print "</table>";
  print "<BR>\n";


}
else
{
  print $sql;
  print $db->error();
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
