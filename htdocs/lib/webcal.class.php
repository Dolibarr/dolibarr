<?php
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

/*!
        \file       htdocs/lib/webcal.class.php
        \ingroup    webcal
		\brief      Ensemble des fonctions permettant d'acceder a la database webcalendar.
		\author     Rodolphe Quiedeville.
		\version    $Revision$
*/

require_once (DOL_DOCUMENT_ROOT ."/lib/".$conf->webcal->db->type.".lib.php");

/*!
        \class      Webcal
		\brief      Classe permettant d'acceder a la database webcalendar
*/

class Webcal {
  var $localdb;
  var $heure = -1;
  var $duree = 0;
  var $date;
  var $texte;
  var $desc;
  
/*!
		\brief      Constructeur de la classe d'interface à Webcalendar
*/

  function Webcal()
    {
      global $conf;

      // On initie la connexion à la base Webcalendar
      $this->localdb = new DoliDb(
                    $conf->webcal->db->type,
                    $conf->webcal->db->host,
                    $conf->webcal->db->user,
                    $conf->webcal->db->pass,
                    $conf->webcal->db->name);
    }


/*!
		\brief      Ajoute une entree dans le calendrier de l'utilisateur
		\param[in]  user		le login de l'utilisateur
		\param[in]  date		la date de l'evenement dans le calendrier
		\param[in]  texte		le titre a indiquer dans l'evenement
		\param[in]  desc		la description a indiquer dans l'evenement
        \return     int         1 en cas de succès, -1,-2, -3 en cas d'erreur
*/

  function add($user, $date, $texte, $desc)
    {

      // Recupère l'id max+1 dans la base webcalendar
      $id = $this->get_next_id();

      if ($id > 0) {
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
            		    // OK
                        return 1;        
            		}
            		else
            		{
            		    $error = $this->localdb->error() . '<br>' .$sql;
                        return -1;
            		}
            	}
            else
            	{
                	$error = $this->localdb->error() . '<br>' .$sql;
                    return -2;
            	}
        }
        else {
        	$error = $this->localdb->error() . '<br>' .$sql;
            return -3;
        }
    }


/*!
		\brief      Obtient l'id suivant dans le webcalendar
		\return     id	retourne l'id suivant dans le webcalendar
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
            return -1;
        }
    }
}
?>
