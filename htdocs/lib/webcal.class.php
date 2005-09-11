<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
        \file       htdocs/lib/webcal.class.php
        \ingroup    webcalendar
		\brief      Ensemble des fonctions permettant d'acceder a la database webcalendar.
		\author     Rodolphe Quiedeville.
		\author     Laurent Destailleur.
		\version    $Revision$
*/


/**
        \class      Webcal
		\brief      Classe permettant d'acceder a la database webcalendar
*/

class Webcal {
    
    var $localdb;

    var $date;
    var $duree = 0;     // Secondes
    var $texte;
    var $desc;
    
    var $error;

  
    /**
    		\brief      Constructeur de la classe d'interface à Webcalendar
    */
    function Webcal()
    {
        global $conf;
        global $dolibarr_main_db_type,$dolibarr_main_db_host,$dolibarr_main_db_user;
        global $dolibarr_main_db_pass,$dolibarr_main_db_name;

        // Défini parametres webcal (avec substitution eventuelle)
        $webcaltype=eregi_replace('__dolibarr_main_db_type__',$dolibarr_main_db_type,$conf->webcal->db->type);
        $webcalhost=eregi_replace('__dolibarr_main_db_host__',$dolibarr_main_db_host,$conf->webcal->db->host);
        $webcaluser=eregi_replace('__dolibarr_main_db_user__',$dolibarr_main_db_user,$conf->webcal->db->user);
        $webcalpass=eregi_replace('__dolibarr_main_db_pass__',$dolibarr_main_db_pass,$conf->webcal->db->pass);
        $webcalname=eregi_replace('__dolibarr_main_db_name__',$dolibarr_main_db_name,$conf->webcal->db->name);

        // On initie la connexion à la base Webcalendar
        require_once (DOL_DOCUMENT_ROOT ."/lib/".$webcaltype.".lib.php");
        $this->localdb = new DoliDb($webcaltype,$webcalhost,$webcaluser,$webcalpass,$webcalname);
    }


    /**
    		\brief      Ajoute objet en tant qu'entree dans le calendrier de l'utilisateur
    		\param[in]  user		Le login de l'utilisateur
            \return     int         1 en cas de succès, -1,-2,-3 en cas d'erreur, -4 si login webcal non défini
    */
    function add($user)
{
        global $langs;
        
        dolibarr_syslog("Webcal::add user=$user");

        // Test si login webcal défini pour le user
        if (! $user->webcal_login) {
            $this->error=$langs->trans("ErrorWebcalLoginNotDefined","<a href=\"".DOL_URL_ROOT."/user/fiche.php?id=".$user->id."\">".$user->login."</a>");
            return -4; 
        }
        
        $this->localdb->begin();

        // Recupère l'id max+1 dans la base webcalendar
        $id = $this->get_next_id();
        
        if ($id > 0)
        {
            $cal_id = $id;
            $cal_create_by = $user->webcal_login;
            $cal_date = strftime('%Y%m%d', $this->date);
            $cal_time = strftime('%H%M%S', $this->date);
            $cal_mod_date = strftime('%Y%m%d', time());
            $cal_mod_time = strftime('%H%M%S', time());
            $cal_duration = round($this->duree / 60);
            $cal_priority = 2;
            $cal_type = "E";
            $cal_access = "P";
            $cal_name = $this->texte;
            $cal_description = $this->desc;

            $sql = "INSERT INTO webcal_entry (cal_id, cal_create_by,cal_date,cal_time,cal_mod_date, cal_mod_time,cal_duration,cal_priority,cal_type, cal_access, cal_name,cal_description)";
            $sql.= " VALUES ($cal_id, '$cal_create_by', '$cal_date', '$cal_time', '$cal_mod_date', '$cal_mod_time', $cal_duration, $cal_priority, '$cal_type', '$cal_access', '$cal_name','$cal_description')";

            if ($this->localdb->query($sql))
           	{
            	$sql = "INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status)";
            	$sql .= " VALUES ($cal_id, '$cal_create_by', 'A')";
            
        		if ( $this->localdb->query($sql) )
        		{
        		    // OK
                    $this->localdb->commit();
                    return 1;        
        		}
        		else
        		{
                    $this->localdb->rollback();
        		    $this->error = $this->localdb->error() . '<br>' .$sql;
                    return -1;
        		}
        	}
            else
        	{
                $this->localdb->rollback();
            	$this->error = $this->localdb->error() . '<br>' .$sql;
                return -2;
        	}
        }
        else
        {
            $this->localdb->rollback();
        	$this->error = $this->localdb->error() . '<br>' .$sql;
            return -3;
        }
    }


    /**
    		\brief      Obtient l'id suivant dans le webcalendar
    		\return     int     Retourne l'id suivant dans webcalendar, <0 si ko
    */
    function get_next_id()
    {
        $sql = "SELECT max(cal_id) as id FROM webcal_entry";

        $resql=$this->localdb->query($sql);
        if ($resql)
        {
            $obj=$this->localdb->fetch_object($resql);
            return ($obj->id + 1);
        }
        else
        {
            $this->error=$this->localdb->error();
            return -1;
        }
    }
   
}
?>
