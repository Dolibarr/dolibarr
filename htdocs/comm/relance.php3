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
require("../lib/functions.inc.php3");
llxHeader();
$db = new Db();
if ($sortfield == "") {
  $sortfield="a.datea";
}
if ($sortorder == "") {
  $sortorder="DESC";
}

$active["1"] = "Offres en ligne";
$active["-1"] = "Moderation";
$active["-2"] = "Refusées";
$active["0"] = "Rédaction";
$active["-3"] = "Désactivées";
$active["-4"] = "Supprimées";

$yn["t"] = "oui";
$yn["f"] = "non";
$deacmeth["b"] = "robots";

if ($page == -1) { $page = 0 ; }
$limit = 26;
$offset = $limit * $page ;
$pageprev = $page - 1;
$pagenext = $page + 1;

print "<DIV align=\"center\">";
print "<A href=\"$PHP_SELF?page=$pageprev&begin=$begin&stcomm=$stcomm\">< Prev</A>\n| ";
for ($i = 65 ; $i < 91; $i++) {
  print "<A href=\"$PHP_SELF?begin=" . chr($i) . "&stcomm=$stcomm\" class=\"T3\">";
  
  if ($begin == chr($i) ) {
    print  "<b>-&gt;" . chr($i) . "&lt;-</b>" ; 
  } else {
    print  chr($i)  ; 
  }
  print "</A> | ";
}
print " <A href=\"$PHP_SELF?page=$pagenext&begin=$begin&stcomm=$stcomm\">Next ></A>\n";
print "</DIV><P>";

$bc[0]="bgcolor=\"#90c090\"";
$bc[1]="bgcolor=\"#b0e0b0\"";

$sql = "SELECT a.fk_action, s.nom as societe, s.idp as socidp,a.id, int(a.datea) as da, a.datea, c.libelle, a.author FROM actioncomm as a, c_actioncomm as c, societe as s WHERE a.fk_soc = s.idp AND c.id=a.fk_action";

$sql .= " ORDER BY a.fk_soc ASC, a.datea ASC ";
$sql .= "  LIMIT $limit OFFSET $offset";


$result = $db->query($sql);
$num = $db->num_rows();
$i = 0; $j = 0 ;
print "<p><TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"4\">";
print "<TR bgcolor=\"orange\"><td>&nbsp;</td>";
print "<TD><a href=\"$PHP_SELF?sortfield=lower(s.nom)&sortorder=ASC\">Societe</a></td>";
print "<TD>Date</TD>";
print "<TD>Derni&egrave;re action</TD>";
print "<TD>Auteur</TD>";
print "</TR>\n";
$var=True;
while ($i < $num) {
  $obj = $db->fetch_object( $i);

  if ($i == 0) {
    $objold = $obj;
  }

  if (($objold->socidp <> $obj->socidp) && $objold->fk_action <> 11) {
    $var=!$var;
    print "<TR $bc[$var]>";
    print "<TD>" . ($j + 1 + ($limit * $page)) . "</TD>";
    print "<TD><a href=\"index.php3?socid=$objold->socidp\">$objold->societe</A></TD>\n";
    
    print "<TD>" .gljftime("%d %b %Y %H:%M",$objold->datea)."</TD>\n";
    
    print "<TD>$objold->libelle</TD>\n";
    print "<TD>$objold->author</TD>\n";
    print "<TD align=\"center\">$objold->stcomm</TD>\n";
    print "</TR>\n";
    $j++;
  }
  $objold = $obj;

  $i++;   
}
if ( $objold->fk_action <> 11) {    
  $var=!$var;
  
  print "<TR $bc[$var]>";
  print "<TD>" . ($j + 1 + ($limit * $page)) . "</TD>";
  print "<TD><a href=\"index.php3?socid=$objold->socidp\">$objold->societe</A></TD>\n";
  
  print "<TD>" .gljftime("%d %b %Y %H:%M",$objold->datea)."</TD>\n";
  
  print "<TD>$objold->libelle</TD>\n";
  print "<TD>$objold->author</TD>\n";
  print "<TD align=\"center\">$objold->stcomm</TD>\n";
  print "</TR>\n";
}
print "</TABLE>";

$db->free();
$db->close();

llxFooter();
?>
