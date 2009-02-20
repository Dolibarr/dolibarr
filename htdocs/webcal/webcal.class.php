<?php
/* Copyright (C) 2002-2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/webcal/webcal.class.php
        \ingroup    webcalendar
		\brief      Ensemble des fonctions permettant d'acceder a la database webcalendar.
		\author     Rodolphe Quiedeville.
		\author     Laurent Destailleur.
		\version    $Id$
*/


/**
        \class      Webcal
		\brief      Classe permettant d'acceder a la database webcalendar
*/

class Webcal {
    
    var $localdb;
    var $error;
	var $version;		/* Version string from webcalendar. Not defined in 1.0 */
    var $date;
    var $duree = 0;     /* Secondes */
    var $texte;
    var $desc;
    

  
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
        $webcalport=eregi_replace('__dolibarr_main_db_port__',$dolibarr_main_db_port,$conf->webcal->db->port);
        $webcaluser=eregi_replace('__dolibarr_main_db_user__',$dolibarr_main_db_user,$conf->webcal->db->user);
        $webcalpass=eregi_replace('__dolibarr_main_db_pass__',$dolibarr_main_db_pass,$conf->webcal->db->pass);
        $webcalname=eregi_replace('__dolibarr_main_db_name__',$dolibarr_main_db_name,$conf->webcal->db->name);

        // On initie la connexion à la base Webcalendar
        require_once (DOL_DOCUMENT_ROOT ."/lib/databases/".$webcaltype.".lib.php");
        $this->localdb = new DoliDb($webcaltype,$webcalhost,$webcaluser,$webcalpass,$webcalname,$webcalport);
    }


    /**
    		\brief      Ajoute objet en tant qu'entree dans le calendrier de l'utilisateur
    		\param[in]  user		Le login de l'utilisateur
            \return     int         1 en cas de succès, -1,-2,-3 en cas d'erreur, -4 si login webcal non défini
    */
    function add($user)
	{
        global $langs;
        
        dol_syslog("Webcal::add user=".$user->id);

        // Test si login webcal défini pour le user
        if (! $user->webcal_login)
		{
			$langs->load("other");
            $this->error=$langs->transnoentities("ErrorWebcalLoginNotDefined","<a href=\"".DOL_URL_ROOT."/user/fiche.php?id=".$user->id."\">".$user->login."</a>");
			dol_syslog("Webcal::add ERROR ".$this->error);
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
            $cal_priority = 2;				// Medium avec 1.0, Haute avec 1.1
											// Rem: 1.0: 1=bas, 2=medium, 3=haut
											//      1.1: 1=haut, 2=haut, 3=haut, 4=medium ... 9=bas
            $cal_type = "E";				// Evenement de type "intemporel"
            $cal_access = "P";				// Acces publique
            $cal_name = $this->texte;		// Title for event
            $cal_description = $this->desc;	// Desc for event

            $sql = "INSERT INTO webcal_entry (cal_id, cal_create_by,cal_date,cal_time,cal_mod_date, cal_mod_time,cal_duration,cal_priority,cal_type, cal_access, cal_name,cal_description)";
            $sql.= " VALUES ($cal_id, '$cal_create_by', '$cal_date', '$cal_time', '$cal_mod_date', '$cal_mod_time', $cal_duration, $cal_priority, '$cal_type', '$cal_access', '$cal_name','$cal_description')";

			dol_syslog("Webcal::add sql=".$sql);
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
					dol_syslog("Webcal::add ERROR ".$this->error);
                    return -1;
        		}
        	}
            else
        	{
                $this->localdb->rollback();
            	$this->error = $this->localdb->error() . '<br>' .$sql;
				dol_syslog("Webcal::add ERROR ".$this->error);
                return -2;
        	}
        }
        else
        {
            $this->localdb->rollback();
        	$this->error = $this->localdb->error() . '<br>' .$sql;
			dol_syslog("Webcal::add ERROR ".$this->error);
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

	
    /**
    		\brief      Export fichier cal depuis base webcalendar
			\param		format			'ical' or 'vcal'
			\param		type			'event' or 'journal'
			\param		cachedelay		Do not rebuild file if date older than cachedelay seconds	
			\param		filename		Force filename
			\param		filters			Array of filters
    		\return     int     		<0 if error, nb of events in new file if ok
    */
	function build_calfile($format,$type,$cachedelay,$filename,$filters)
	{
		global $conf,$langs;
		
		require_once (DOL_DOCUMENT_ROOT ."/lib/xcal.lib.php");

		dol_syslog("webcal::build_calfile Build cal file format=".$format.", type=".$type.", cachedelay=".$cachedelay.", filename=".$filename.", filters size=".sizeof($filters), LOG_DEBUG);

		// Check parameters
		if (empty($format)) return -1;

		// Clean parameters
		if (! $filename)
		{
			$extension='vcs';
			if ($format == 'ical') $extension='ics';
			$filename=$format.'.'.$extension;
		}
		
		create_exdir($conf->webcal->dir_temp);
		$outputfile=$conf->webcal->dir_temp.'/'.$filename;
		$result=0;
		
		$buildfile=true;
		if ($cachedelay)
		{
			// \TODO Check cache
		}
		
		if ($buildfile)
		{
			// Build event array
			$eventarray=array();
			
			$sql = "SELECT cal_id, cal_create_by, ";
			$sql.= " cal_date, cal_time, cal_mod_date,";
			$sql.= " cal_mod_time, cal_duration, cal_priority, cal_type, cal_access, cal_name, cal_description";
			$sql.= " FROM webcal_entry";
			$sql.= " order by cal_date";

			dol_syslog("Webcal::build_vcal select events sql=".$sql);
			$resql=$this->localdb->query($sql);
			if ($resql)
			{
				while ($obj=$this->localdb->fetch_object($resql))
				{
					$qualified=true;
					
					// 'eid','startdate','duration','enddate','title','summary','category','email','url','desc','author'
					$event=array();
					$event['uid']='dolibarrwebcal-'.$this->localdb->database_name.'-'.$obj->cal_id."@".$_SERVER["SERVER_NAME"];
					$event['type']=$type;
					$date=$obj->cal_date;
					$time=$obj->cal_time;
					if (eregi('^([0-9][0-9][0-9][0-9])([0-9][0-9])([0-9][0-9])$',$date,$reg))
					{
						$year=$reg[1];
						$month=$reg[2];
						$day=$reg[3];
						if (! empty($filters['year'])  && $year != $filters['year'])  $qualified=false;
						if (! empty($filters['month']) && $year != $filters['month']) $qualified=false;
						if (! empty($filters['day'])   && $year != $filters['day'])   $qualified=false;
					}
					if (eregi('^([0-9]?[0-9])([0-9][0-9])([0-9][0-9])$',$time,$reg))
					{
						$hour=sprintf("%02d",$reg[1]);
						$min=sprintf("%02d",$reg[2]);
						$sec=sprintf("%02d",$reg[3]);
					}
					$datestart=dol_mktime($hour,$min,$sec,$month,$day,$year);
					$event['startdate']=$datestart;
					//$event['duration']=$obj->cal_duration;	// Not required with type 'journal'
					//$event['enddate']='';						// Not required with type 'journal'
					$event['summary']=$obj->cal_name;
					$event['desc']=$obj->cal_description;
					$event['author']=$obj->cal_create_by;
					$event['transparency']='TRANSPARENT';		// TRANSPARENT or OPAQUE
					$url=$conf->global->PHPWEBCALENDAR_URL;
					if (! eregi('\/$',$url)) $url.='/';
					$url.='view_entry.php?id='.$obj->cal_id;
					$event['url']=$url;
					
					if ($qualified)
					{
						$eventarray[$datestart]=$event;
					}
				}
			}
			else
			{
				dol_syslog("webcal::build_calfile ".$this->localdb->lasterror());
				return -1;
			}
			
			// Write file
			$title='Webcalendar events ';
			$desc='Webcalendar events for database '.$this->localdb->database_name.' - built by Dolibarr';
			$result=build_calfile($format,$title,$desc,$eventarray,$outputfile);
		}
		
		return $result;
	}
  
}
?>
