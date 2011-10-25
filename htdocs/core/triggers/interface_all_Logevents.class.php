<?php
/* Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *      \file       htdocs/core/triggers/interface_all_Logevents.class.php
 *      \ingroup    core
 *      \brief      Trigger file for
 */


/**
 *      \class      InterfaceLogevents
 *      \brief      Class of triggers for security events
 */
class InterfaceLogevents
{
    var $db;
    var $error;

    var $date;
    var $duree;
    var $texte;
    var $desc;

    /**
     *   Constructor.
     *   @param      DB      Database handler
     */
    function InterfaceLogevents($DB)
    {
        $this->db = $DB ;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "core";
        $this->description = "Triggers of this module allows to add security event records inside Dolibarr.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->picto = 'technic';
    }

    /**
     *   Return name of trigger file
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *   @return     string      Version of trigger file
     */
    function getVersion()
    {
        global $langs;
        $langs->load("admin");

        if ($this->version == 'experimental') return $langs->trans("Experimental");
        elseif ($this->version == 'dolibarr') return DOL_VERSION;
        elseif ($this->version) return $this->version;
        else return $langs->trans("Unknown");
    }

    /**
     *      Function called when a Dolibarrr business event is done.
     *      All functions "run_trigger" are triggered if file is inside directory htdocs/core/triggers
     *      @param      action      Code de l'evenement
     *      @param      object      Objet concerne
     *      @param      user        Objet user
     *      @param      langs       Objet langs
     *      @param      conf        Objet conf
     *      @param      entity      Value for instance of data (Always 1 except if module MultiCompany is installed)
     *      @return     int         <0 if KO, 0 if no triggered ran, >0 if OK
     */
    function run_trigger($action,$object,$user,$langs,$conf,$entity=1)
    {
    	if (! empty($conf->global->MAIN_LOGEVENTS_DISABLE_ALL)) return 0;	// Log events is disabled (hidden features)

    	$key='MAIN_LOGEVENTS_'.$action;
    	//dol_syslog("xxxxxxxxxxx".$key);
    	if (empty($conf->global->$key)) return 0;				// Log events not enabled for this action

    	if (empty($conf->entity)) $conf->entity = $entity;  // forcing of the entity if it's not defined (ex: in login form)

        $this->date=gmmktime();
        $this->duree=0;

        // Actions
        if ($action == 'USER_LOGIN')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            // Initialisation donnees (date,duree,texte,desc)
            $this->texte="(UserLogged,".$object->login.")";
            $this->desc="(UserLogged,".$object->login.")";
        }
        if ($action == 'USER_LOGIN_FAILED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            // Initialisation donnees (date,duree,texte,desc)
            $this->texte=$object->trigger_mesg;	// Message direct
            $this->desc=$object->trigger_mesg;	// Message direct
        }
        if ($action == 'USER_LOGOUT')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            // Initialisation donnees (date,duree,texte,desc)
            $this->texte="(UserLogoff,".$object->login.")";
            $this->desc="(UserLogoff,".$object->login.")";
        }
        if ($action == 'USER_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");

            // Initialisation donnees (date,duree,texte,desc)
            $this->texte=$langs->transnoentities("NewUserCreated",$object->login);
            $this->desc=$langs->transnoentities("NewUserCreated",$object->login);
		}
        elseif ($action == 'USER_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");

            // Initialisation donnees (date,duree,texte,desc)
            $this->texte=$langs->transnoentities("EventUserModified",$object->login);
            $this->desc=$langs->transnoentities("EventUserModified",$object->login);
        }
        elseif ($action == 'USER_NEW_PASSWORD')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");

            // Initialisation donnees (date,duree,texte,desc)
            $this->texte=$langs->transnoentities("NewUserPassword",$object->login);
            $this->desc=$langs->transnoentities("NewUserPassword",$object->login);
        }
        elseif ($action == 'USER_ENABLEDISABLE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
			if ($object->statut == 0)
			{
				$this->texte=$langs->transnoentities("UserEnabled",$object->login);
				$this->desc=$langs->transnoentities("UserEnabled",$object->login);
			}
			if ($object->statut == 1)
			{
				$this->texte=$langs->transnoentities("UserDisabled",$object->login);
				$this->desc=$langs->transnoentities("UserDisabled",$object->login);
			}
        }
        elseif ($action == 'USER_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $this->texte=$langs->transnoentities("UserDeleted",$object->login);
            $this->desc=$langs->transnoentities("UserDeleted",$object->login);
        }

		// Groupes
        elseif ($action == 'GROUP_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $this->texte=$langs->transnoentities("NewGroupCreated",$object->nom);
            $this->desc=$langs->transnoentities("NewGroupCreated",$object->nom);
		}
        elseif ($action == 'GROUP_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $this->texte=$langs->transnoentities("GroupModified",$object->nom);
            $this->desc=$langs->transnoentities("GroupModified",$object->nom);
		}
        elseif ($action == 'GROUP_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $this->texte=$langs->transnoentities("GroupDeleted",$object->nom);
            $this->desc=$langs->transnoentities("GroupDeleted",$object->nom);
		}

		// If not found
/*
        else
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.");
			return 0;
        }
*/

        // Add entry in event table
        if ($this->date)
        {
			include_once(DOL_DOCUMENT_ROOT.'/core/class/events.class.php');

			$event=new Events($this->db);
            $event->type=$action;
            $event->dateevent=$this->date;
            $event->label=$this->texte;
            $event->description=$this->desc;
			$event->user_agent=$_SERVER["HTTP_USER_AGENT"];

            $result=$event->create($user);
            if ($result > 0)
            {
                return 1;
            }
            else
            {
                $error ="Failed to insert security event: ".$event->error;
                $this->error=$error;

                dol_syslog("interface_all_Logevents.class.php: ".$this->error, LOG_ERR);
                return -1;
            }
        }

		return 0;
    }

}
?>
