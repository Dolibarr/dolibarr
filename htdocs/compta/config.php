<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
require("./pre.inc.php");

llxHeader();


if ($action == 'add') {

}
/*
 *
 * Mode creation
 *
 *
 *
 */

if ($action == 'create') {


} else {

    /*
     *
     * Liste
     *
     *
     */
    print_barre_liste("Comptes comptable",$page,$PHP_SELF);

    $sql = "SELECT number, label";
    $sql .= " FROM llx_compta_account";
    $sql .= " ORDER BY number";
  
    $result = $db->query($sql);
    if ($result) {
      $num = $db->num_rows();
    
      $i = 0;
      print "<TABLE border=\"0\" width=\"100%\" cellspacing=\"0\" cellpadding=\"3\">";
      print '<TR class="liste_titre">';
      print "<TD>Num&eacute;ro</TD><td>";
      print_liste_field_titre("Libellé",$PHP_SELF,"label");
      print "</td></TR>\n";
    
      if ($num > 0) {
	$var=True;
	while ($i < $num) {
	  $objp = $db->fetch_object( $i);
	  $var=!$var;
	
	  print "<TR $bc[$var]>";
	  print '<td>'.$objp->number.'</td>';
	  print '<td>'.$objp->label.'</td>';

	  print "</TR>\n";
	  $i++;
	}
      }
    
      print "</TABLE>";
      $db->free();
    } else {
      print $db->error();
    }



}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
