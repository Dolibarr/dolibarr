<?PHP
/* Copyright (C) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

Function dolibarr_ldap_connect()
{
  $ldapconnect = ldap_connect(LDAP_SERVER_HOST);

  return $ldapconnect;
}


Function dolibarr_ldap_bind($ds)
{
  if (defined("LDAP_SERVER_PASS") && LDAP_SERVER_DN && LDAP_SERVER_PASS)
    {
      $ldapbind = ldap_bind($ds, LDAP_SERVER_DN, LDAP_SERVER_PASS);
    }
/*  else
    {
      $ldapbind = ldap_bind($ds, $dn); -- connection anonyme desactivee
    }
*/
  return $ldapbind;
}

Function ldap_unacc($str)
{
  $stu = ereg_replace("é","e",$str);
  $stu = ereg_replace("è","e",$stu);
  $stu = ereg_replace("ê","e",$stu);
  $stu = ereg_replace("à","a",$stu);
  $stu = ereg_replace("ç","c",$stu);
  $stu = ereg_replace("ï","i",$stu);
  $stu = ereg_replace("ä","a",$stu);
  return $stu;
}

?>
