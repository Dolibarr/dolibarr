<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

$db = new Db();
if ($sortfield == "") {
  $sortfield="lower(cd.categories_name)";
}
if ($sortorder == "") {
  $sortorder="ASC";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des catégories", $page, $PHP_SELF);

$sql = "SELECT c.categories_id, cd.categories_name ";
$sql .= " FROM ".DB_NAME_OSC.".categories as c,".DB_NAME_OSC.".categories_description as cd";
$sql .= " WHERE c.categories_id = cd.categories_id AND cd.language_id = ".OSC_LANGUAGE_ID;
$sql .= " AND c.parent_id = 0";
$sql .= " ORDER BY $sortfield $sortorder ";
$sql .= $db->plimit( $limit ,$offset);
 
if ( $db->query($sql) )
{
  $num = $db->num_rows();
  $i = 0;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR class=\"liste_titre\"><td>";
  print_liste_field_titre("Titre",$PHP_SELF, "a.title");
  print "</td>";
  print "<td></td>";
  print "</TR>\n";
  $var=True;
  while ($i < $num)
    {
      $objp = $db->fetch_object( $i);
      $var=!$var;

      printc($objp->categories_id,$db, 0);

      $i++;
    }
  print "</TABLE>";
  $db->free();
}
else
{
  print $db->error();
}

Function printc($id, $db, $level)
{

  $cat = new Categorie($db);
  $cat->fetch($id);

  print "<TR $bc[$var]><td>";

  for ($i = 0 ; $i < $level ; $i++)
    {
      print "&nbsp;&nbsp;|--";
    }

  print "<a href=\"fiche.php?id=$objp->rowid\">".$cat->name."</a></TD>\n";
  print "</TR>\n";

  $childs = array();
  $childs = $cat->liste_childs_array();
  if (sizeof($childs))
  {
    foreach($childs as $key => $value)
      {
	printc($key,$db, $level+1);
      }
  }

}
$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
