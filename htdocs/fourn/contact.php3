<?PHP
/* Copyright (C) 2001-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*
 * Sécurité accés client
 */
if ($user->societe_id > 0) 
{
  $action = '';
  $socid = $user->societe_id;
}


$db = new Db();
if ($sortorder == "")
{
  $sortorder="ASC";
}
if ($sortfield == "")
 {
  $sortfield="p.name";
}

if ($page == -1) { $page = 0 ; }
$limit = $conf->liste_limit;
$offset = $limit * $page ;

print_barre_liste("Liste des contacts",$page, $PHP_SELF);

print "<DIV align=\"center\">";

print "| <A href=\"$PHP_SELF?page=$pageprev&stcomm=$stcomm&sortfield=$sortfield&sortorder=$sortorder&aclasser=$aclasser&coord=$coord\">*</A>\n| ";
for ($i = 65 ; $i < 91; $i++) {
  print "<A href=\"$PHP_SELF?begin=" . chr($i) . "&stcomm=$stcomm\" class=\"T3\">";
  
  if ($begin == chr($i) ) {
    print  "<b>-&gt;" . chr($i) . "&lt;-</b>" ; 
  } else {
    print  chr($i)  ; 
  }
  print "</A> | ";
}
print "</div>";


/*
 *
 * Mode liste
 *
 *
 */



$sql = "SELECT s.idp, s.nom,  st.libelle as stcomm, p.idp as cidp, p.name, p.firstname, p.email, p.phone ";
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

if ($socid) {
  $sql .= " AND s.idp = $socid";
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
  print "<TR class=\"liste_titre\">";
  print "<TD>Nom</TD>";
  print "<TD>Prénom</TD><td>";
  print_liste_field_titre("Société",$PHP_SELF,"lower(s.nom)", $begin);
  print "</td><TD>email</TD>";
  print '<TD>Téléphone</TD><td>&nbsp;</td>';
  print "</TR>\n";
  $var=True;
  while ($i < $num) {
    $obj = $db->fetch_object( $i);
    
    $var=!$var;

    print "<TR $bc[$var]>";

    print "<TD>$obj->name</TD>";
    print "<TD>$obj->firstname</TD>";

    print '<TD><a href="contact.php3?socid='.$obj->idp.'"><img src="/theme/'.$conf->theme.'/img/filter.png" border="0"></a>&nbsp;';
    print "<a href=\"index.php3?socid=$obj->idp\">$obj->nom</A></td>\n";
    print "<TD>$obj->email&nbsp;</TD>\n";
    print '<td><a href="actioncomm.php3?action=create&actionid=1&contactid='.$obj->cidp.'&socid='.$obj->idp.'">'.$obj->phone.'</a>&nbsp;</td>';

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

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
