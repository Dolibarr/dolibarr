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
 * or see http://www.gnu.org/
 *
 * $Id$
 * $Source$
 *
 */

 /**
  * Page-level DocBlock
  * @package ldap.lib
	* @version 1.9
	*
	*/

/**
 * connection au serveur ldap
 *
 * @access public
 * @return resource
 */

Function dolibarr_ldap_connect()
{
  $ldapconnect = ldap_connect(LDAP_SERVER_HOST);

  return $ldapconnect;
}

/**
 * bind au serveur ldap
 *
 * @access public
 *	@param resource $ds
 * @return bool
 *
 */

Function dolibarr_ldap_bind($ds)
{
  if (defined("LDAP_SERVER_PASS") && LDAP_SERVER_DN && LDAP_SERVER_PASS)
    {
      $ldapbind = ldap_bind($ds, LDAP_SERVER_DN, LDAP_SERVER_PASS);
    }

  return $ldapbind;
}

/**
 * unbind du serveur ldap
 *
 * @access public
 *	@param resource $ds
 * @return bool
 *
 */

Function dolibarr_ldap_unbind($ds)
{

   $ldapunbind = ldap_unbind($ds);

  return $ldapunbind;
}

/**
 * verification de la version du serveur ldap
 *
 * cette fonction permet de verifier la version du
 * protocole du serveur ldap
 *
 * @access public
 *	@param resource $ds
 * @return mixed
 *
 */

Function dolibarr_ldap_getversion($ds)
{
	$version = 0;

	ldap_get_option($ds, LDAP_OPT_PROTOCOL_VERSION, $version);

  return $version;
}

/**
 * changement de la version du serveur ldap
 *
 * cette fonction permet de modifier la version du
 * protocole du serveur ldap
 *
 * @access public
 *	@param resource $ds
 *	@param integer $version
 * @return bool
 *
 */

Function dolibarr_ldap_setversion($ds,$version)
{
	$ldapsetversion = ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, $version);

  return $ldapsetversion;
}

/**
 * suppression des accents d'une chaîne
 *
 * cette fonction permet d'enlever les accents d'une chaine
 * avant de l'envoyer au serveur ldap
 *
 * @access public
 *	@param string $str
 * @return string
 *
 */

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
