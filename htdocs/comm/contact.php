<?php
/* Copyright (C) 2001-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2003      Éric Seigne          <erics@rycks.com>
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

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');


llxHeader();

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];
$socid=$_GET["socid"];
$type=$_GET["type"];


/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}

$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

if ($sortorder == "")
{
  $sortorder="ASC";
}
if ($sortfield == "")
{
  $sortfield="p.name";
}
$page=$_GET["page"];
if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;


if ($type == "c") {
  $label = " clients";
  $urlfiche="fiche.php";
}
if ($type == "p") {
  $label = " prospects";
  $urlfiche="prospect/fiche.php";
}
if ($type == "f") {
  $label = " fournisseurs";
  $urlfiche="fiche.php";
}


/*
 *
 * Mode liste
 *
 *
 */

$sql = "SELECT s.idp, s.nom,  st.libelle as stcomm, p.idp as cidp, p.name, p.firstname, p.email, p.phone ";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."socpeople as p, ".MAIN_DB_PREFIX."c_stcomm as st";
$sql .= " WHERE s.fk_stcomm = st.id AND s.idp = p.fk_soc";

if ($type == "c") {
  $sql .= " AND s.client = 1";
}
if ($type == "p") {
  $sql .= " AND s.client = 2";
}
if ($type == "f") {
  $sql .= " AND s.fournisseur = 1";
}


if (strlen($stcomm)) {
  $sql .= " AND s.fk_stcomm=$stcomm";
}

if (strlen($begin)) // filtre sur la premiere lettre du nom
{
  $sql .= " AND upper(p.name) like '$begin%'";
}

if ($_GET[contactname]) // acces a partir du module de recherche
{
  $sql .= " AND ( lower(p.name) like '%".strtolower($_GET[contactname])."%' OR lower(p.firstname) like '%".strtolower($_GET[contactname])."%') ";
  $sortfield = "lower(p.name)";
  $sortorder = "ASC";
}

if ($socid) {
  $sql .= " AND s.idp = $socid";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit, $offset);

$result = $db->query($sql);
if ($result) 
{
  $num = $db->num_rows();
  
  print_barre_liste("Liste des contacts $label",$page, "contact.php", "&amp;type=$type",$sortfield,$sortorder,"",$num);
  
  print "<DIV align=\"center\">";
  
  print "| <A href=\"contact.php?type=$type&page=$pageprev&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">*</A>\n| ";
  for ($i = 65 ; $i < 91; $i++) {
    print "<A href=\"contact.php?type=$type&begin=" . chr($i) . "&stcomm=$stcomm\" class=\"T3\">";
    
    if ($begin == chr($i) )
      {
	print  "<b>-&gt;" . chr($i) . "&lt;-</b>" ; 
      }
    else
      {
	print  chr($i)  ; 
      } 
    print "</A> | ";
  }
  print "</div>";
  
  if ($sortorder == "DESC")
    {
      $sortorder="ASC";
    }
  else
    {
      $sortorder="DESC";
  }
  
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<tr class="liste_titre"><td>';
  print_liste_field_titre("Nom","contact.php","lower(p.name)", $begin,"&amp;type=$type");
  print "</td><td>";
  print_liste_field_titre("Prénom","contact.php","lower(p.firstname)", $begin,"&amp;type=$type");
  print "</td><td>";
  print_liste_field_titre("Société","contact.php","lower(s.nom)", $begin,"&amp;type=$type");
  print "</td><TD>email</TD>";
  print '<td>Téléphone</td>';
  print "</tr>\n";
  $var=True;
  $i = 0;
  while ($i < min($num,$limit))
    {
      $obj = $db->fetch_object( $i);
    
      $var=!$var;

      print "<TR $bc[$var]>";
      print '<TD><a href="'.DOL_URL_ROOT.'/comm/people.php?contactid='.$obj->cidp.'&socid='.$obj->idp.'">'.img_file();
      print '</a>&nbsp;<a href="'.DOL_URL_ROOT.'/comm/people.php?contactid='.$obj->cidp.'&socid='.$obj->idp.'">'.$obj->name.'</a></td>';
      print "<TD>$obj->firstname</TD>";
      
      print '<TD><a href="contact.php?type='.$type.'&socid='.$obj->idp.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filter.png" border="0" alt="filtrer"></a>&nbsp;';
      print "<a href=\"".$urlfiche."?socid=$obj->idp\">$obj->nom</A></td>\n";
      
      print '<td><a href="action/fiche.php?action=create&actionid=4&contactid='.$obj->cidp.'&socid='.$obj->idp.'">'.$obj->email.'</a>&nbsp;</td>';
      
      print '<td><a href="action/fiche.php?action=create&actionid=1&contactid='.$obj->cidp.'&socid='.$obj->idp.'">'.dolibarr_print_phone($obj->phone).'</a>&nbsp;</td>';
      
      print "</TR>\n";
      $i++;
    }
  print "</TABLE></p>";
  $db->free();
}
else
{
  print_barre_liste("Liste des contacts $label",$page, "contact.php");
  
  print $db->error();
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
