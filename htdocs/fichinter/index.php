<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php3");
require("../contact.class.php3");

if ($user->societe_id > 0)
{
  $socid = $user->societe_id ;
}

llxHeader();

/*
 * Liste
 *
 */

if ($sortorder == "")
{
  $sortorder="DESC";
}
if ($sortfield == "")
{
  $sortfield="f.datei";
}

if ($page == -1) { $page = 0 ; }

$limit = $conf->liste_limit;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

$sql = "SELECT s.nom,s.idp, f.ref,".$db->pdate("f.datei")." as dp, f.rowid as fichid, f.fk_statut, f.duree";
$sql .= " FROM llx_societe as s, llx_fichinter as f ";
$sql .= " WHERE f.fk_soc = s.idp ";


if ($socid > 0)
{
  $sql .= " AND s.idp = " . $socid;
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit + 1 ,$offset);

if ( $db->query($sql) )
{
  $num = $db->num_rows();
  print_barre_liste("Liste des fiches d'intervention", $page, $PHP_SELF,"&socid=$socid",$sortfield,$sortorder,'',$num);

  $i = 0;
  print '<TABLE border="0" width="100%" cellspacing="0" cellpadding="4">';
  print "<TR class=\"liste_titre\">";
  print_liste_field_titre_new ("Num",$PHP_SELF,"f.ref","","&socid=$socid",'width="15%"',$sortfield);
  print_liste_field_titre_new ("Société",$PHP_SELF,"s.nom","","&socid=$socid",'',$sortfield);
  print_liste_field_titre_new ("Date",$PHP_SELF,"f.datei","","&socid=$socid",'',$sortfield);
  print '<TD align="center">Durée</TD>';
  print '<TD align="center">Statut</TD><td>&nbsp;</td>';
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;
      print "<TR $bc[$var]>";
      print "<TD><a href=\"fiche.php3?id=$objp->fichid\">$objp->ref</a></TD>\n";

      print '<td><a href="index.php?socid='.$objp->idp.'"><img src="'.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/filter.png" border="0"></a>&nbsp;';
      print "<a href=\"../comm/fiche.php3?socid=$objp->idp\">$objp->nom</a></TD>\n";
      print "<TD>".strftime("%d %B %Y",$objp->dp)."</TD>\n";
      print '<TD align="center">'.sprintf("%.1f",$objp->duree).'</TD>';
      print '<TD align="center">'.$objp->fk_statut.'</TD>';

      if ($user->societe_id == 0)
	{
	  print '<TD align="center"><a href="fiche.php3?socidp='.$objp->idp.'&action=create">[Fiche Inter]</A></td>';
	}
      else
	{
	  print "<td>&nbsp;</td>";
	}
      print "</TR>\n";
      
      $i++;
    }
  
  print "</TABLE>";
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
