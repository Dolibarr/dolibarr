<?php
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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


$sql = "SELECT rowid, number, label FROM ".MAIN_DB_PREFIX."compta_account ORDER BY number";
if ( $db->query($sql) ) {
  $num = $db->num_rows();
  $i = 0;
  $options = "<option value=\"0\" SELECTED></option>";
  while ($i < $num) {
    $obj = $db->fetch_object();
    $options .= "<option value=\"$obj->rowid\">$obj->number</option>\n"; $i++;
  }
  $db->free();

}




if ($action == 'add') {

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."compta (datec, fk_compta_account, label, amount)";
  $sql .= " VALUES (now(),$number, '$label',$amount)";

  $db->query($sql);

}
/*
 *
 * Mode creation
 *
 *
 *
 */

if ($action == 'create') {
  //

} else {

  /*
   *
   * Liste
   *
   *
   */
  print_barre_liste("Comptes comptable",$page,"ligne.php");
  
  print "<table class=\"noborder\" width=\"100%\">";
  print '<tr class="liste_titre">';
  print_liste_field_titre($langs->trans("Ref"),"ligne.php","id");
  print_liste_field_titre($langs->trans("Label"),"ligne.php","label");
  print '<td>'.$langs->trans("Amount").'</td>';
  print "</tr>\n";
    
  print '<form action="ligne.php" method="post">';
  print '<input type="hidden" name="action" value="add">';
  print '<tr><td><select name="number">'.$options.'</select></td>';
  print '<td><input type="text" name="label" size="30"></td>';
  print '<td><input type="text" name="amount" size="10"></td>';
  print '<td><input type="submit" value="add"></td>';
  print '</tr>';
  print '</form>';

  $sql = "SELECT ca.number, c.label, c.amount";
  $sql .= " FROM ".MAIN_DB_PREFIX."compta_account as ca, ".MAIN_DB_PREFIX."compta as c WHERE c.fk_compta_account = ca.rowid";
  $sql .= " ORDER BY ca.number";
  
  $result = $db->query($sql);
  if ($result) {
    $num = $db->num_rows();
    $i = 0;
    if ($num > 0) {
	$var=True;
	while ($i < $num) {
	  $objp = $db->fetch_object($result);
	  $var=!$var;
	
	  print "<TR $bc[$var]>";
	  print '<td>'.$objp->number.'</td>';
	  print '<td>'.$objp->label.'</td>';
	  print '<td>'.price($objp->amount).'</td>';

	  print "</TR>\n";
	  $i++;
	}
      }
    

      $db->free();
    } else {
      print $db->error();
    }
      print "</TABLE>";
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
