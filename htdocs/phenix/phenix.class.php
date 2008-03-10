<?php
/* Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin        <regis@dolibarr.fr>
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
 */

/**
        \file       htdocs/phenix/phenix.class.php
        \ingroup    phenix
		\brief      Ensemble des fonctions permettant d'acceder a la database phenix.
		\author     Laurent Destailleur.
		\author     Regis Houssin.
		\version    $Revision$
*/


/**
        \class      Phenix
		\brief      Classe permettant d'acceder a la database phenix
*/

class Phenix {
    
    var $localdb;
    var $error;
    var $date;
    var $duree = 0;     /* Secondes */
    var $texte;
    var $desc;
    

  
    /**
    		\brief      Constructeur de la classe d'interface à Phenix
    */
    function Phenix()
    {
    	global $conf;
      global $dolibarr_main_db_type,$dolibarr_main_db_host,$dolibarr_main_db_user;
      global $dolibarr_main_db_pass,$dolibarr_main_db_name;

      // Défini parametres phenix (avec substitution eventuelle)
      $phenixtype=eregi_replace('__dolibarr_main_db_type__',$dolibarr_main_db_type,$conf->phenix->db->type);
      $phenixhost=eregi_replace('__dolibarr_main_db_host__',$dolibarr_main_db_host,$conf->phenix->db->host);
      $phenixport=eregi_replace('__dolibarr_main_db_port__',$dolibarr_main_db_port,$conf->phenix->db->port);
      $phenixuser=eregi_replace('__dolibarr_main_db_user__',$dolibarr_main_db_user,$conf->phenix->db->user);
      $phenixpass=eregi_replace('__dolibarr_main_db_pass__',$dolibarr_main_db_pass,$conf->phenix->db->pass);
      $phenixname=eregi_replace('__dolibarr_main_db_name__',$dolibarr_main_db_name,$conf->phenix->db->name);

      // On initie la connexion à la base Phenix
      require_once (DOL_DOCUMENT_ROOT ."/lib/databases/".$phenixtype.".lib.php");
      $this->localdb = new DoliDb($phenixtype,$phenixhost,$phenixuser,$phenixpass,$phenixname,$phenixport);
    }

// TODO : Modifier la suite....
// Ajouter variable pour l'extension du nom des tables "px_" qui peut etre different
// récupérer id du user à partir de son login


    /**
    		\brief      Ajoute objet en tant qu'entree dans le calendrier de l'utilisateur
    		\param[in]  user		    Le login de l'utilisateur
        \return     int         1 en cas de succès, -1,-2,-3 en cas d'erreur, -4 si login phenix non défini
    */
    function add($user)
    {
    	global $langs;
        
      dolibarr_syslog("Phenix::add user=".$user->id);
      
      // Test si login phenix défini pour le user
      if (! $user->phenix_login)
      {
      	$langs->load("other");
      	$this->error=$langs->transnoentities("ErrorPhenixLoginNotDefined","<a href=\"".DOL_URL_ROOT."/user/fiche.php?id=".$user->id."\">".$user->login."</a>");
      	dolibarr_syslog("Phenix::add ERROR ".$this->error);
      	return -4; 
      }
      
      $this->localdb->begin();

      // Recupère l'id max+1 dans la base webcalendar
      $id = $this->get_next_id();
        
      if ($id > 0)
      {
      	$age_id = $id;
        $age_createur_id = $user->id;
        $cal_date = strftime('%Y%m%d', $this->date);
        $cal_time = strftime('%H%M%S', $this->date);
        $cal_mod_date = strftime('%Y%m%d', time());
        $cal_mod_time = strftime('%H%M%S', time());
        $cal_duration = round($this->duree / 60);
        $cal_priority = 2;							// Medium avec 1.0, Haute avec 1.1
        																// Rem: 1.0: 1=bas, 2=medium, 3=haut
        																//      1.1: 1=haut, 2=haut, 3=haut, 4=medium ... 9=bas
        $cal_type = "E";								// Evenement de type "intemporel"
        $cal_access = "P";							// Acces publique
        $cal_name = $this->texte;				// Title for event
        $cal_description = $this->desc;	// Desc for event

        $sql = "INSERT INTO px_agenda (age_id, age_createur_id, cal_date, cal_time, cal_mod_date, cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, cal_name,cal_description)";
        $sql.= " VALUES ($age_id, '$age_createur_id', '$cal_date', '$cal_time', '$cal_mod_date', '$cal_mod_time', $cal_duration, $cal_priority, '$cal_type', '$cal_access', '$cal_name','$cal_description')";
        
        dolibarr_syslog("Phenix::add sql=".$sql);
        $resql=$this->localdb->query($sql);
        if ($resql)
        {
        	$sql = "INSERT INTO webcal_entry_user (cal_id, cal_login, cal_status)";
          $sql .= " VALUES ($cal_id, '$cal_create_by', 'A')";
            
        	$resql=$this->localdb->query($sql);
        	if ($resql)
        	{
        		// OK
            $this->localdb->commit();
            return 1;        
        	}
        	else
        	{
        		$this->localdb->rollback();
        		$this->error = $this->localdb->error() . '<br>' .$sql;
        		dolibarr_syslog("Phenix::add ERROR ".$this->error);
        		return -1;
        	}
        }
        else
        {
        	$this->localdb->rollback();
          $this->error = $this->localdb->error() . '<br>' .$sql;
          dolibarr_syslog("Phenix::add ERROR ".$this->error);
          return -2;
        }
      }
      else
      {
      	$this->localdb->rollback();
        $this->error = $this->localdb->error() . '<br>' .$sql;
        dolibarr_syslog("Phenix::add ERROR ".$this->error);
        return -3;
      }
    }
    

    /**
    		\brief      Obtient l'id suivant dans phenix
    		\return     int     Retourne l'id suivant dans phenix, <0 si ko
    */
    function get_next_id()
    {
    	$sql = "SELECT max(age_id) as id FROM px_agenda";

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
