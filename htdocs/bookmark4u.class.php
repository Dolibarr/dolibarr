<?PHP
/* Copyright (c) 2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/bookmark4u.class.php
  \brief  Fichier de la classe bookmark4u
  \author Rodolphe Quiedeville
  \version $Revision$
*/

class Bookmark4u
{
  var $db;
	
  var $id;

  /**
   *    \brief Constructeur de la classe
   *    \param  $DB         handler accès base de données
   *    \param  $id         id de l'utilisateur (0 par défaut)
   */
  function Bookmark4u($DB, $id=0)
    {

      $this->db = $DB;
      $this->id = $id;

      return 1;
    }

  /**
   *
   *
   */
  function get_bk4u_uid($user)
    {

      $sql = "SELECT bk4u_uid FROM ".MAIN_DB_PREFIX."bookmark4u_login";
      $sql .= " WHERE fk_user =".$user->id;

      if ($this->db->query($sql)) 
	{
	  $num = $this->db->num_rows();

	  if ($num == 0)
	    {
	      $this->uid = 0;
	      return 0;
	    }
	  else
	    {
	      $row = $this->db->fetch_row(0);

	      $this->uid = $row[0];
	      return 0;
	    }
	
	  $this->db->free();
	}
      else
	{
	  return 1;
	}
    }
  /**
   *
   *
   *
   */

  function get_bk4u_login()
    {

      $sql = "SELECT user FROM bookmark4u.bk4u_passwd";
      $sql .= " WHERE uid =".$this->uid;

      if ($this->db->query($sql)) 
	{
	  $num = $this->db->num_rows();

	  if ($num == 0)
	    {
	      return 0;
	    }
	  else
	    {
	      $row = $this->db->fetch_row(0);

	      $this->login = $row[0];
	      return 0;
	    }
	
	  $this->db->free();
	}
      else
	{
	  return 1;
	}
    }



  /**
   * \brief     Créé un compte
   * \param     user Objet du user
   *
   */
  function create_account_from_user($user)
    {
      // TODO rendre la base et la table générique

      $sql = "INSERT INTO bookmark4u.bk4u_passwd (user, passwd, name, email, logincnt,rdate)";
      $sql .= " VALUES ('$user->login',password('$user->pass'),'$user->firstname $user->name','$user->email',0,now());";
      if ($this->db->query($sql))
	{
	  if ($this->db->affected_rows()) 
	    {
	      $this->uid = $this->db->last_insert_id();
	      
	      $sql = "INSERT INTO ".MAIN_DB_PREFIX."bookmark4u_login";
	      $sql .= " (fk_user, bk4u_uid)";
	      $sql .= " VALUES ($user->id, $this->uid)";

	      $this->db->query($sql);
	      
	      return 0;
	    }
	  else
	    {
	      dolibarr_syslog("Bookmark4u::Create_account_from_user INSERT 2");
	    }
	}
      else
	{
	  dolibarr_syslog("Bookmark4u::Create_account_from_user INSERT 1");
	  print $sql;
	}
      
    }
}
?>
