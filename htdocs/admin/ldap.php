<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Benoit Mortier <benoit.mortier@opensides.be>
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


if ($_GET["action"] == 'setvalue' && $user->admin)
{
  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'LDAP_SERVER_HOST', value='".$HTTP_POST_VARS["host"]."', visible=0";

  $db->query($sql);

  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'LDAP_SERVER_DN', value='".$HTTP_POST_VARS["dn"]."', visible=0";

  $db->query($sql);

  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'LDAP_SERVER_PASS', value='".$HTTP_POST_VARS["pass"]."', visible=0";

  $db->query($sql);

  $sql = "REPLACE INTO ".MAIN_DB_PREFIX."const SET name = 'LDAP_SERVER_TYPE', value='".$HTTP_POST_VARS["type"]."', visible=0";

  $db->query($sql);


  if ($db->query($sql))
    {
      Header("Location: ldap.php");
    }
}

llxHeader();

if (!$user->admin)
{
  print "Forbidden";
  llxfooter();
  exit;
}
/**
  *
  */

print_titre("Configuration de ldap");

print '<table class="noborder" width="100%" cellpadding="3" cellspacing="0">';
print '<tr>';
print '<td width="50%" valign="top">';

print '<table class="border" cellpadding="3" cellspacing="0">';
print '<tr class="liste_titre">';
print '<td>Nom</td>';
print '<td>Valeur</td><td colspan="2">&nbsp;</td>';
print "</tr>\n";
print '<tr><td>Serveur LDAP</td><td>'.LDAP_SERVER_HOST.'</td></tr>';

print '<tr><td>DN</td><td>'.LDAP_SERVER_DN.'</td></tr>';
print '<tr><td>Pass</td><td>'.LDAP_SERVER_PASS.'</td></tr>';
print '<tr><td>Type</td><td>'.LDAP_SERVER_TYPE.'</td></tr>';

print '</table>';

print '</td><td width="50%">';

print '<form method="post" action="ldap.php?action=setvalue">';

print '<table class="border" cellpadding="3" cellspacing="0">';
print '<TR class="liste_titre">';
print '<td>Nom</td>';
print '<td>Valeur</td><td colspan="2">&nbsp;</td>';
print "</tr>\n";
print '<tr><td>';
print 'Serveur LDAP</td><td>';
print '<input size="15" type="text" name="host" value="'.LDAP_SERVER_HOST.'">';
print '</td></tr>';
print '<tr><td>DN</td><td>';
print '<input size="25" type="text" name="dn" value="'.LDAP_SERVER_DN.'">';
print '</td></tr>';
print '<tr><td>Pass</td><td>';
print '<input size="25" type="text" name="pass" value="'.LDAP_SERVER_PASS.'">';
print '</td></tr>';

print '<tr><td><select name="type">';
print '<option value="openldap" selected>OpenLdap';
print '<option value="egroupware">Egroupware';
print '</select>';
print '</td></tr>';

print '<tr><td><input type="submit" value="changer"></td></tr>';
print '</table></form>';

print '</td></tr></table>';

/**
  *
  */

print '<a href="ldap.php?action=test">test de connection</a><br>';

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
