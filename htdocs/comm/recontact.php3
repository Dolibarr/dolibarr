<?PHP
/* Copyright (C) 2001-2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 *
 * $Id$
 * $Source$
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
 */
require("./pre.inc.php3");

llxHeader();
$db = new Db();
if ($sortfield == "") {
  $sortfield="a.datea";
}
if ($sortorder == "") {
  $sortorder="DESC";
}

if ($action == 'del') {
  $sql = "DELETE FROM llx_soc_recontact WHERE rowid=$rowid";
  $result = $db->query( $sql);
}

$yn["t"] = "oui";
$yn["f"] = "non";


if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

print "<DIV align=\"center\">";
print "<A href=\"$PHP_SELF?page=$pageprev&begin=$begin&stcomm=$stcomm\">< Prev</A>\n| ";
for ($i = 65 ; $i < 91; $i++) {
  print "<A href=\"$PHP_SELF?begin=" . chr($i) . "&stcomm=$stcomm\" class=\"T3\">";
  print  chr($i)  ;
  print "</A> | ";
}
print " <A href=\"$PHP_SELF?page=$pagenext&begin=$begin&stcomm=$stcomm\">Next ></A>\n";
print "</DIV><P>";

$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";

$sql = "SELECT s.nom as societe, s.idp as socidp,".$db->pdate("re.datere")." as datere, re.rowid, re.author";
$sql .= " FROM societe as s, llx_soc_recontact as re";
$sql .= " WHERE re.fk_soc = s.idp";
$sql .= " ORDER BY re.datere ASC ";
$sql .= $db->plimit( $limit, $offset);

$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
  $i = 0; $j = 0 ;
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"orange\"><td>&nbsp;</td>";
  print "<TD><a href=\"$PHP_SELF?sortfield=lower(s.nom)&sortorder=ASC\">Societe</a></td>";
  print "<TD colspan=\"3\">A recontacter le</TD>";
  print "<TD>Auteur</TD>";
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $obj = $db->fetch_object( $i);

    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD>" . ($j + 1 + ($limit * $page)) . "</TD>";
    print "<TD><a href=\"index.php3?socid=$obj->socidp\">$obj->societe</A></TD>\n";
  
    print "<TD>" .strftime("%d",$obj->datere)."</TD>\n";
    print "<TD>" .strftime("%B",$obj->datere)."</TD>\n";
    print "<TD>" .strftime("%Y",$obj->datere)."</TD>\n";
  
    print "<TD>$obj->author</TD>\n";
    print "<TD align=\"right\"><a href=\"$PHP_SELF?action=del&rowid=$obj->rowid\">Supprimer ce rappel</A></TD>\n";
    print "</TR>\n";
    $j++;

    $objold = $obj;

    $i++;   
  }
  print "</table>";
}


$db->free();
$db->close();

llxFooter();
?>
