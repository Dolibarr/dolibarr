<?php
/* Copyright (C) 2005-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
        \file       htdocs/includes/triggers/interface_all_Logevents.class.php
        \ingroup    core
        \brief      Trigger file for 
		\version	$Id$
*/


/**
        \class      InterfaceActionsAuto
        \brief      Classe des fonctions triggers des actions agenda
*/

class InterfaceActionsAuto
{
    var $db;
    var $error;
    
    var $date;
    var $duree;
    var $texte;
    var $desc;
    
    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceActionsAuto($DB)
    {
        $this->db = $DB ;
    
        $this->name = "ActionsAuto";
        $this->family = "agenda";
        $this->description = "Triggers of this module add actions in agenda according to setup made in agenda setup.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
    }

    /**
     *   \brief      Renvoi nom du lot de triggers
     *   \return     string      Nom du lot de triggers
     */
    function getName()
    {
        return $this->name;
    }
    
    /**
     *   \brief      Renvoi descriptif du lot de triggers
     *   \return     string      Descriptif du lot de triggers
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   \brief      Renvoi version du lot de triggers
     *   \return     string      Version du lot de triggers
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
     *      \brief      Fonction appelee lors du declenchement d'un evenement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre presentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concerne
     *      \param      user        Objet user
     *      \param      langs       Objet langs
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
    function run_trigger($action,$object,$user,$langs,$conf)
    {
		$key='MAIN_AGENDA_ACTIONAUTO_'.$action;
		//dolibarr_syslog("xxxxxxxxxxx".$key);
		if (empty($conf->global->$key)) return 0;				// Log events not enabled for this action
		
		$ok=0;
		
		// Actions
        if ($action == 'PROPAL_SENTBYMAIL')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
			$ok=1;
		}
        if ($action == 'BILL_SENTBYMAIL')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
			$ok=1;
		}
        elseif ($action == 'ORDER_SENTBYMAIL')
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
			$ok=1;
        }

		// If not found
/*
        else
        {
            dolibarr_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.");
			return 0;
        }
*/

        // Add entry in event table
        if ($ok)
        {
			// Insertion action
			require_once(DOL_DOCUMENT_ROOT.'/contact.class.php');
			require_once(DOL_DOCUMENT_ROOT.'/actioncomm.class.php');
			$actioncomm = new ActionComm($this->db);
			$actioncomm->type_id     = $object->actiontypeid;
			$actioncomm->type_code   = $object->actiontypecode;
			$actioncomm->label       = $object->actionmsg2;
			$actioncomm->note        = $object->actionmsg;
			$actioncomm->date        = time();	// L'action est faite maintenant
			$actioncomm->percentage  = 100;
			$actioncomm->contact     = new Contact($this->db,$object->sendtoid);
			$actioncomm->societe     = new Societe($this->db,$object->socid);
			$actioncomm->user        = $user;   // User qui a fait l'action
			$actioncomm->facid       = $object->facid;
			$actioncomm->orderrowid  = $object->orderrowid;
			$actioncomm->propalrowid = $object->propalrowid;
			
			$ret=$actioncomm->add($user);       // User qui saisit l'action
			if ($ret > 0)
			{
				return 1;
			}
			else
			{
                $error ="Failed to insert : ".$webcal->error." ";
                $this->error=$error;

                //dolibarr_syslog("interface_webcal.class.php: ".$this->error);
                return -1;
			}
		}

		return 0;
    }

}
?>
