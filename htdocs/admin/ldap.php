<?php
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004 Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005 Regis Houssin        <regis.houssin@cap-networks.com>
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
 */
 
/*!	\file htdocs/admin/ldap.php
		\ingroup    ldap
		\brief      Page d'administration/configuration du module Ldap
		\version    $Revision$
*/

require("./pre.inc.php");
require (DOL_DOCUMENT_ROOT."/lib/ldap.lib.php");

$langs->load("admin");

if (!$user->admin)
  accessforbidden();

/*
 * Actions
 */
 
if ($_GET["action"] == 'setvalue' && $user->admin)
{
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'LDAP_SERVER_HOST';";
  $db->query($sql);

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('LDAP_SERVER_HOST','".$_POST["host"]."',0);";
	$db->query($sql);

  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'LDAP_SUFFIX_DN';";
  $db->query($sql);

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('LDAP_SUFFIX_DN','".$_POST["suffix"]."',0);";
  $db->query($sql);
  
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'LDAP_ADMIN_DN';";
  $db->query($sql);
  
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('LDAP_ADMIN_DN','".$_POST["admin"]."',0);";
  $db->query($sql);

  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'LDAP_ADMIN_PASS';";
  $db->query($sql);
  
  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('LDAP_ADMIN_PASS','".$_POST["pass"]."',0);";
  $db->query($sql);
  
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'LDAP_USER_DN';";
  $db->query($sql);

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('LDAP_USER_DN','".$_POST["user"]."',0);";
  $db->query($sql);
  
  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'LDAP_GROUP_DN';";
  $db->query($sql);

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('LDAP_GROUP_DN','".$_POST["group"]."',0);";
  $db->query($sql);

  $sql = "DELETE FROM ".MAIN_DB_PREFIX."const WHERE name = 'LDAP_SERVER_TYPE';";
  $db->query($sql);

  $sql = "INSERT INTO ".MAIN_DB_PREFIX."const (name,value,visible) VALUES
	('LDAP_SERVER_TYPE','".$_POST["type"]."',0);";

  if ($db->query($sql))
    {
      Header("Location: ldap.php");
    }
}

/*
 * Visu
 */

llxHeader();

print_titre($langs->trans("LDAPSetup"));

print '<br>';
print '<table class="noborder" width="100%">';
print '<tr>';
print '<td width="50%" valign="top">';

print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td><td colspan="2">&nbsp;</td>';
print "</tr>\n";
if (!defined("LDAP_SERVER_HOST") && LDAP_SERVER_HOST)
{
	print '<tr><td>'.$langs->trans("LDAPServer").'</td><td>'.$langs->trans("LDAPServer").'</td></tr>';
}
else
{
	print '<tr><td>'.$langs->trans("LDAPServer").'</td><td>'.LDAP_SERVER_HOST.'</td></tr>';
}	
print '<tr><td>'.$langs->trans("LDAPServer").'</td><td>'.LDAP_SERVER_HOST.'</td></tr>';
print '<tr><td>'.$langs->trans("LDAPSuffix").'</td><td>'.LDAP_SUFFIX_DN.'</td></tr>';
print '<tr><td>'.$langs->trans("DNAdmin").'</td><td>'.LDAP_ADMIN_DN.'</td></tr>';
print '<tr><td>'.$langs->trans("LDAPPassword").'</td><td>'.LDAP_ADMIN_PASS.'</td></tr>';
print '<tr><td>'.$langs->trans("DNUser").'</td><td>'.LDAP_USER_DN.'</td></tr>';
print '<tr><td>'.$langs->trans("DNGroup").'</td><td>'.LDAP_GROUP_DN.'</td></tr>';
print '<tr><td>'.$langs->trans("Type").'</td><td>'.LDAP_SERVER_TYPE.'</td></tr>';

print '</table>';

print '</td><td width="50%">';

/*
 * Modification
 */

print '<form method="post" action="ldap.php?action=setvalue">';

print '<table class="border" width="100%">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td>'.$langs->trans("Value").'</td><td colspan="2">&nbsp;</td>';
print "</tr>\n";
print '<tr><td>';
print $langs->trans("LDAPServer").'</td><td>';
print '<input size="25" type="text" name="host" value="'.LDAP_SERVER_HOST.'">';
print '</td></tr>';
print '<tr><td>'.$langs->trans("LDAPSuffix").'</td><td>';
print '<input size="25" type="text" name="suffix" value="'.LDAP_SUFFIX_DN.'">';
print '</td></tr>';
print '<tr><td>'.$langs->trans("DNAdmin").'</td><td>';
print '<input size="25" type="text" name="admin" value="'.LDAP_ADMIN_DN.'">';
print '</td></tr>';
print '<tr><td>'.$langs->trans("LDAPPassword").'</td><td>';
print '<input size="25" type="text" name="pass" value="'.LDAP_ADMIN_PASS.'">';
print '</td></tr>';
print '<tr><td>'.$langs->trans("DNUser").'</td><td>';
print '<input size="25" type="text" name="user" value="'.LDAP_USER_DN.'">';
print '</td></tr>';
print '<tr><td>'.$langs->trans("DNGroup").'</td><td>';
print '<input size="25" type="text" name="group" value="'.LDAP_GROUP_DN.'">';
print '</td></tr>';

print '<tr><td>'.$langs->trans("Type").'</td><td><select name="type">';
print '<option value="openldap" selected>OpenLdap';
print '<option value="egroupware">Egroupware';
print '</select>';
print '</td></tr>';

print '<tr><td colspan="2" align="center"><input type="submit" value="'.$langs->trans("Modify").'"></td></tr>';
print '</table></form>';

print '</td></tr></table>';

/*
 * test de la connexion
 */
 
 
if (defined("LDAP_SERVER_HOST") && LDAP_SERVER_HOST) {
    print '<a class="tabAction" href="ldap.php?action=test">'.$langs->trans("TestConnectLdap").'</a><br>';
}


if (defined("LDAP_SERVER_HOST") && LDAP_SERVER_HOST && $_GET["action"] == 'test')
{
  $ds = dolibarr_ldap_connect();

  if ($ds)
    {
      print "connection au serveur ldap réussie<br>";

      if ((dolibarr_ldap_getversion($ds) == 3))
				{
					print "Serveur ldap configuré en version 3<br>";
				}
				else
				{
					print "Serveur ldap configuré en version 2<br>";
				}

      $bind = dolibarr_ldap_bind($ds);

      if ($bind)
				{
	  			print "connection au dn $dn réussi<br>";
				}
      	else
				{
	  			print "connection au dn $dn raté<br>";
				}

				$unbind = dolibarr_ldap_unbind($ds);

			if ($bind)
				{
	  			print "déconnection du dn $dn réussi<br>";
				}
      	else
				{
	  			print "déconnection du dn $dn raté<br>";
				}
    }
  	else
    {
      print "connection au serveur ldap échouée<br>";
    }
}

$db->close();

llxFooter();
?>
