<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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
require("../../tva.class.php");

/*
 *
 *
 */
/*
 *
 */

llxHeader();

/*
 * Liste
 *
 */
$sortorder=$_GET["sortorder"];
$sortfield=$_GET["sortfield"];
$page=$_GET["page"];

if ($sortorder == "")
{
  $sortorder="DESC";
}
if ($sortfield == "")
{
  $sortfield="d.dated";
}

if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT s.nom,s.idp, d.km,".$db->pdate("d.dated")." as dd, u.name, u.firstname, d.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."deplacement as d, ".MAIN_DB_PREFIX."user as u ";
$sql .= " WHERE d.fk_soc = s.idp AND d.fk_user = u.rowid";

if ($user->societe_id > 0)
{
  $sql .= " AND s.idp = " . $user->societe_id;
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1 ,$offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  print_barre_liste("Liste des déplacements", $page, "index.php","&socidp=$socidp",$sortfield,$sortorder,'',$num);

  $i = 0;
  print '<table class="noborder" width="100%" cellspacing="0" cellpadding="3">';
  print "<tr class=\"liste_titre\">";
  print_liste_field_titre_new ("Date","index.php","d.dated","","&socidp=$socidp",'',$sortfield);
  print_liste_field_titre_new ("Société","index.php","s.nom","","&socidp=$socidp",'',$sortfield);
  print '<td align="center">Utilisateur</TD>';
  print "</tr>\n";

  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?id='.$objp->rowid.'">'.dolibarr_print_date($objp->dd).'</a></td>';
      print '<td><a href="/comm/fiche.php?socid='.$objp->idp.'">'.$objp->nom."</a></td>\n";

      print '<td align="center">'.$objp->firstname.' '.$objp->name.'</td>';

      print "</tr>\n";
      
      $i++;
    }
  
  print "</table>";
  $db->free();
}
else
{
  print $db->error();
  print "<p>$sql";
}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
