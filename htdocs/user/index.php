<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004      Laurent Destailleur  <eldy@users.sourceforge.net>
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

$langs->load("users");


llxHeader();


$sortfield=$_GET["sortfield"];
$sortorder=$_GET["sortorder"];


print_titre($langs->trans("ListOfUsers"));

$sql = "SELECT u.rowid, u.name, u.firstname, u.code, u.login, u.module_comm, u.module_compta";
$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
$sql .= " ORDER BY ";
if ($sortfield) { $sql.="$sortfield $sortorder"; }
else { $sql.="u.name"; }

$result = $db->query($sql);
if ($result)
{
  $num = $db->num_rows();
  $i = 0;
  
  print "<br>";
  
  print "<table class=\"noborder\" width=\"100%\" cellspacing=\"0\" cellpadding=\"2\">";
  print '<tr class="liste_titre">';
  print "<td>";
  print_liste_field_titre($langs->trans("LastName"),"index.php","name");
  print "</td>";
  print "<td>";
  print_liste_field_titre($langs->trans("FirstName"),"index.php","firstname");
  print "</td>";
  print "<td>";
  print_liste_field_titre($langs->trans("Login"),"index.php","login");
  print "</td>";
  print "<td>";
  print_liste_field_titre($langs->trans("Code"),"index.php","code");
  print "</td>";
  print "</tr>\n";
  $var=True;
  while ($i < $num)
    {
      $obj = $db->fetch_object( $i);
      $var=!$var;
      
      print "<tr $bc[$var]>";
      print '<td><a href="fiche.php?id='.$obj->rowid.'">';
      print img_file();
      print '</a>&nbsp;'.ucfirst($obj->name).'</TD>';
      print '<td>'.ucfirst($obj->firstname).'</td>';
      if ($obj->login)
	{
	  print '<td><a href="fiche.php?id='.$obj->rowid.'">'.$obj->login.'</a></td>';
	}
      else
	{
	  print '<td><a class="impayee" href="fiche.php?id='.$obj->rowid.'">Inactif</a></td>';
	}        
      print '<td>'.$obj->code.'</TD>';
      print "</tr>\n";
      $i++;
    }
  print "</table>";
  $db->free();
}
else 
{
  print $db->error();
}

$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
