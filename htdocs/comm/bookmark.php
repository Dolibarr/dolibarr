<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

if ($sortorder == "") {
  $sortorder="DESC";
}
if ($sortfield == "") {
  $sortfield="idp";
}

$yn["t"] = "oui";
$yn["f"] = "non";
$ynn["1"] = "oui";
$ynn["0"] = "non";

if ($action == 'add')
{
  $sql = "INSERT INTO llx_bookmark (fk_soc, dateb, author) VALUES ($socidp, now(),'". $GLOBALS["REMOTE_USER"]."');";
  if (! $db->query($sql) )
    {
      print $db->error();
    }
}

if ($action == 'delete')
{
  $sql = "DELETE FROM  llx_bookmark WHERE rowid=$bid AND author = '". $GLOBALS["REMOTE_USER"]."'";
  $result = $db->query($sql);
}


if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

print '<div class="titre">Bookmark</div>';
 
$sql = "SELECT s.idp, s.nom, ".$db->pdate("b.dateb")." as dateb, st.libelle as stcomm, b.rowid as bid, b.author";
$sql .= " FROM ".MAIN_DB_PREFIX."societe as s, ".MAIN_DB_PREFIX."c_stcomm as st, ".MAIN_DB_PREFIX."bookmark as b";
$sql .= " WHERE b.fk_soc = s.idp AND s.fk_stcomm = st.id AND s.datea is not null";

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit, $offset);


if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;

  if ($sortorder == "DESC")
    {
      $sortorder="ASC";
    }
  else
    {
      $sortorder="DESC";
    }
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"orange\">";
  print "<TD>&nbsp;</TD>";
  print "<TD align=\"center\"><a href=\"index.php?sortfield=idp&sortorder=$sortorder&begin=$begin\">Id</a></TD>";
  print "<TD><a href=\"index.php?sortfield=lower(s.nom)&sortorder=$sortorder&begin=$begin\">Societe</a></td>";

  print "<TD align=\"center\">Statut</TD>";
  print "<TD>Auteur</TD>";
  print "<TD>Date</TD>";

  print "<TD>&nbsp;</TD>";
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      
      $var=!$var;
      $bc1="bgcolor=\"#90c090\"";
      $bc2="bgcolor=\"#b0e0b0\"";
      if (!$var)
	{
	  $bc=$bc1;
	}
      else
	{
	  $bc=$bc2;
	}
      print "<TR $bc>";
      print "<TD>" . ($i + 1 + ($limit * $page)) . "</TD>";
      print "<TD align=\"center\"><b>$obj->idp</b></TD>";
      print "<TD><a href=\"index.php?socid=$obj->idp\">$obj->nom</A></TD>\n";
      
      print "<TD align=\"center\">$obj->stcomm</TD>\n";
      print "<TD>$obj->author</TD>\n";
      print "<td>".strftime("%d %b %Y %H:%M", $obj->dateb) ."</td>";
      print "<TD>[<a href=\"$PHP_SELF?action=delete&bid=$obj->bid\">Delete</A>]</TD>\n";
      print "</TR>\n";
      $i++;
    }
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error();
}

$db->close();

?>
<p>
Seul l'auteur d'un bookmark peut le supprimer.

<?PHP
llxFooter();
?>
