<?PHP
/* Copyright (C) 2002 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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


llxHeader();
$db = new Db();
if ($sortorder == "") 
{
  $sortorder="ASC";
}
if ($sortfield == "") 
{
  $sortfield="nom";
}

print_titre("Liste des adherents");


$ds=ldap_connect("localhost"); 

if ($ds) 
{
  print "Connected";
  // bind with appropriate dn to give update access
  $r=ldap_bind($ds,"cn=admin,dc=rodo,dc=lan", "gnu8lx");
  

  // prepare data
  $info["cn"]="John Jones";
  $info["sn"]="Jones";
  $info["mail"]="jonj@here.and.now";
  $info["objectclass"]="person";
  
  // add data to directory
  // $r=ldap_add($ds, "cn=John Jones, o=Adherents, c=FR", $info);
  

  ldap_close($ds);
} 
else 
{
  echo "Unable to connect to LDAP server"; 
}


$db->close();

llxFooter("<em>Derni&egrave;re modification $Date$ r&eacute;vision $Revision$</em>");
?>
