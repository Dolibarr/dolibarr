<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * Mets à jour tous les contacts dans LDAP à partir de la base sql
 */

require ("../htdocs/master.inc.php");
require_once (DOL_DOCUMENT_ROOT."/contact.class.php");
require_once (DOL_DOCUMENT_ROOT."/user.class.php");

$error = 0;

$user = new User($db);

$sql = "SELECT m.rowid";
$sql .= " FROM ".MAIN_DB_PREFIX."socpeople";

$resql = $db->query($sql);

if ( $resql ) 
{
  $num = $db->num_rows($resql);
  $i = 0;
  
  if ($num == 1)
    {
      $row = $db->fetch_row($resql);

      $contact = new Contact($db);
      $contact->id = $row[0];
      $contact->update_ldap($user);

      $i++;      
    }
}


?>
