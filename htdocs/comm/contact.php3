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
if ($sortorder == "") {
  $sortorder="ASC";
}
if ($sortfield == "") {
  $sortfield="p.name";
}

if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

print "<DIV align=\"center\">";

print "<A href=\"$PHP_SELF?page=$pageprev&begin=$begin&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">&lt;- Prev</A>\n| ";
print "<A href=\"$PHP_SELF?page=$pageprev&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">*</A>\n| ";
for ($i = 65 ; $i < 91; $i++) {
  print "<A href=\"$PHP_SELF?begin=" . chr($i) . "&stcomm=$stcomm\" class=\"T3\">";
  
  if ($begin == chr($i) ) {
    print  "<b>-&gt;" . chr($i) . "&lt;-</b>" ; 
  } else {
    print  chr($i)  ; 
  }
  print "</A> | ";
}
print " <A href=\"$PHP_SELF?page=$pagenext&begin=$begin&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">Next -></A>\n";
print "</DIV><P>";


/*
 * Mode liste
 *
 *
 *
 */

$bc[1]="bgcolor=\"#90c090\"";
$bc[0]="bgcolor=\"#b0e0b0\"";

$sql = "SELECT s.idp, s.nom, cabrecrut, st.libelle as stcomm, p.idp as cidp, p.name, p.firstname, p.email ";
$sql .= "FROM societe as s, socpeople as p, c_stcomm as st WHERE s.fk_stcomm = st.id AND s.idp = p.fk_soc";

if (strlen($stcomm)) {
  $sql .= " AND s.fk_stcomm=$stcomm";
}

if (strlen($begin)) {
  $sql .= " AND upper(p.name) like '$begin%'";
}

if ($contactname) {
  $sql .= " AND lower(p.name) like '%".strtolower($contactname)."%'";
  $sortfield = "lower(p.name)";
  $sortorder = "ASC";
}

$sql .= " ORDER BY $sortfield $sortorder " . $db->plimit( $limit, $offset);

$result = $db->query($sql);
if ($result) {
  $num = $db->num_rows();
  $i = 0;
  
  if ($sortorder == "DESC") {
    $sortorder="ASC";
  } else {
    $sortorder="DESC";
  }
  print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
  print "<TR bgcolor=\"orange\">";
  print "<td>Action</td><TD>Nom</TD>";
  print "<TD>Prénom</TD>";
  print "<TD>email</TD>";
  print "<TD><a href=\"contact.php3?sortfield=lower(s.nom)&sortorder=$sortorder&begin=$begin\">Societe</a></td>";
  print '<TD align="center">Statut</TD><td>&nbsp;</td>';
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    
    $var=!$var;

    print "<TR $bc[$var]>";

    print "<TD>[&nbsp;<a href=\"index.php3?socid=$obj->idp\">T</A>&nbsp;|&nbsp;";
    print "<a href=\"index.php3?socid=$obj->idp\">E</A>&nbsp;|&nbsp;";
    print "<a href=\"index.php3?socid=$obj->idp\">F</A>&nbsp;]";

    print "<TD>$obj->name</TD>";
    print "<TD>$obj->firstname</TD>";
    print "<TD>$obj->email</TD>\n";
    print "<TD><a href=\"index.php3?socid=$obj->idp\">$obj->nom</A></td>\n";
    print '<TD align="center">'.$obj->stcomm.'</TD>';
    print "<TD><a href=\"addpropal.php3?socidp=$obj->idp&setcontact=$obj->cidp&action=create\">[Propal]</A></td>\n";
    print "</TR>\n";
    $i++;
  }
  print "</TABLE>";
  $db->free();
} else {
  print $db->error();
}

$db->close();

llxFooter();
?>
