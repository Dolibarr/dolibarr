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
require("../contact.class.php");
require("../lib/webcal.class.php");
require("../cactioncomm.class.php");
require("../actioncomm.class.php");

$user->getrights('propale');
$user->getrights('fichinter');
$user->getrights('commande');
$user->getrights('projet');


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
$pageprev = $page - 1;
$pagenext = $page + 1;

/*
 * Mode Liste
 *
 *
 *
 */

$sql = "SELECT s.idp, s.nom, s.ville, ".$db->pdate("s.datec")." as datec, ".$db->pdate("s.datea")." as datea,  st.libelle as stcomm, s.prefix_comm FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st WHERE s.fk_stcomm = st.id AND s.client=1";

if (strlen($stcomm))
{
  $sql .= " AND s.fk_stcomm=$stcomm";
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

  if ($num == 1)
    {
      $obj = $db->fetch_object(0);
      Header("Location: fiche.php?socid=$obj->idp");
    }
  else
    {
      llxHeader();
    }


  print_barre_liste("Liste des clients", $page, $PHP_SELF,"",$sortfield,$sortorder,'',$num);

  print '<div align="center">';

  print "| <A href=\"$PHP_SELF?page=$pageprev&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">*</A>\n| ";
  for ($ij = 65 ; $ij < 91; $ij++) {
    print "<A href=\"$PHP_SELF?begin=" . chr($ij) . "&stcomm=$stcomm\" class=\"T3\">";
    
    if ($_GET["begin"] == chr($ij) )
      {
	print  "<b>&gt;" . chr($ij) . "&lt;</b>" ; 
      } 
    else
      {
	print  chr($ij);
      } 
    print "</A> | ";
  }
  print "</div>";


  $i = 0;
  
  if ($sortorder == "DESC")
    {
      $sortorder="ASC";
    }
  else
    {
      $sortorder="DESC";
    }
  print '<TABLE class="noborder" width="100%" cellspacing="0" cellpadding="4">';
  print '<TR class="liste_titre">';
  print "<TD valign=\"center\">";
  print_liste_field_titre("Société",$PHP_SELF,"s.nom");
  print "</td><td>";
  print_liste_field_titre("Ville",$PHP_SELF,"s.ville");
  print "</td>";
  print "</TR>\n";
  $var=True;

  while ($i < min($num,$conf->liste_limit))
    {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;

      print "<TR $bc[$var]>";
      print '<td><a href="fiche.php?socid='.$obj->idp.'">';
      print img_file();
      print "</a>&nbsp;<a href=\"fiche.php?socid=$obj->idp\">$obj->nom</A></td>\n";
      print "<td>".$obj->ville."&nbsp;</td>\n";
      print "</tr>\n";
      $i++;
    }
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error() . ' ' . $sql;
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
