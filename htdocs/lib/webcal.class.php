<?PHP
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file webcal.class.php
		\brief Classe permettant d'acceder a la database webcalendar.
		\author Rodolphe Quiedeville.
		\version $Revision$

		Ensemble des fonctions permettant d'acceder a la database webcalendar.
*/

/*! \class Webcal
		\brief Classe permettant d'acceder a la database webcalendar
		
		Ensemble des fonctions permettant d'acceder a la database webcalendar
*/

class Webcal {
  var $localdb;
  var $heure = -1;
  var $duree = 0;

/*!
		\brief Permet de se connecter a la database webcalendar.
*/

  function Webcal()
    {
      global $conf;

      $this->localdb = new Db($conf->webcal->db->type,
			      $conf->webcal->db->host,
			      $conf->webcal->db->user,
			      $conf->webcal->db->pass,
			      $conf->webcal->db->name);
    }

/*!
		\brief ajoute une entree dans le calendrier de l'utilsateur
		\param[in] user		le login de l'utilisateur
		\param[in] date		la date de l'evenement dans le calendrier
		\param[in] texte		le titre a indiquer dans l'evenement
		\param[in] desc		la description a indiquer dans l'evenement
*/

  function add($user, $date, $texte, $desc)
    {

      $id = $this->get_next_id();

      $cal_id = $id;
      $cal_create_by = $user->webcal_login;
      $cal_date = strftime('%Y%m%d', $date);
      $cal_time  = $this->heure;
      $cal_mod_date = strftime('%Y%m%d', time());
      $cal_mod_time = strftime('%H%M', time());
      $cal_duration = $this->duree;
      $cal_priority = 2;
      $cal_type = "E";
      $cal_access = "P";
      $cal_name = $texte;
      $cal_description = $desc;

      $sql = "INSERT INTO webcal_entry (cal_id, cal_create_by,cal_date,cal_time,cal_mod_date,
			cal_mod_time,cal_duration,cal_priority,cal_type, cal_access, cal_name,cal_description)";

      $sql .= " VALUES ($cal_id, '$cal_create_by', $cal_date, $cal_time,$cal_mod_date, $cal_mod_time,
			$cal_duration,$cal_priority,'$cal_type', '$cal_access', '$cal_name','$cal_description')";

			if ( $this->localdb->query($sql) )
				{

	  			$sql = "INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status)";
	  			$sql .= " VALUES ($cal_id, '$cal_create_by', 'A')";

					if ( $this->localdb->query($sql) )
	    			{

	    			}
					else
	    			{
	      			$error = $this->localdb->error() . '<br>' .$sql;
	    			}
				}
      else
				{
	  			$error = $this->localdb->error() . '<br>' .$sql;
				}

      	$this->localdb->close();
    	}

/*!
		\brief obtient l'id suivant dans le webcalendar
		\retval id	retourne l'id suivant dans le webcalendar
*/


  function get_next_id()
    {

      $sql = "SELECT max(cal_id) FROM webcal_entry";

      if ($this->localdb->query($sql))
				{
	  			$id = $this->localdb->result(0, 0) + 1;
	  			return $id;
				}
      else
				{
	  			print $this->localdb->error();
				}
    }
}
?>
