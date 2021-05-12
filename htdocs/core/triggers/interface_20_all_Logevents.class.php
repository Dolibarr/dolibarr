<?php
/* Copyright (C) 2005-2009	Laurent Destailleur	<eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@capnetworks.com>
=======
 * Copyright (C) 2009-2017	Regis Houssin		<regis.houssin@inodbox.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *  \file       htdocs/core/triggers/interface_20_all_Logevents.class.php
 *  \ingroup    core
 *  \brief      Trigger file for
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';


/**
<<<<<<< HEAD
 *  Class of triggers for security events
 */
class InterfaceLogevents extends DolibarrTriggers
{
	public $picto = 'technic';
	public $family = 'core';
	public $description = "Triggers of this module allows to add security event records inside Dolibarr.";
	public $version = self::VERSION_DOLIBARR;

	/**
	 * Function called when a Dolibarrr business event is done.
=======
 *  Class of triggers for security audit events
 */
class InterfaceLogevents extends DolibarrTriggers
{
	/**
	 * @var string Image of the trigger
	 */
	public $picto = 'technic';

	public $family = 'core';

	public $description = "Triggers of this module allows to add security event records inside Dolibarr.";

	/**
	 * Version of the trigger
	 * @var string
	 */
	public $version = self::VERSION_DOLIBARR;

	/**
	 * Function called when a Dolibarrr security audit event is done.
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User			$user       Object user
	 * @param Translate		$langs      Object langs
	 * @param conf			$conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
    {
    	if (! empty($conf->global->MAIN_LOGEVENTS_DISABLE_ALL)) return 0;	// Log events is disabled (hidden features)

    	$key='MAIN_LOGEVENTS_'.$action;
    	//dol_syslog("xxxxxxxxxxx".$key);
    	if (empty($conf->global->$key)) return 0;				// Log events not enabled for this action

    	if (empty($conf->entity)) $conf->entity = $entity;  // forcing of the entity if it's not defined (ex: in login form)

        $date = dol_now();

        // Actions
        if ($action == 'USER_LOGIN')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $text="(UserLogged,".$object->login.")";
            $text.=(empty($object->trigger_mesg)?'':' - '.$object->trigger_mesg);
            $desc="(UserLogged,".$object->login.")";
            $desc.=(empty($object->trigger_mesg)?'':' - '.$object->trigger_mesg);
        }
        if ($action == 'USER_LOGIN_FAILED')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            // Initialisation donnees (date,duree,texte,desc)
            $text=$object->trigger_mesg;	// Message direct
            $desc=$object->trigger_mesg;	// Message direct
        }
        if ($action == 'USER_LOGOUT')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
            $text="(UserLogoff,".$object->login.")";
            $desc="(UserLogoff,".$object->login.")";
        }
        if ($action == 'USER_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");

            // Initialisation donnees (date,duree,texte,desc)
<<<<<<< HEAD
            $text=$langs->transnoentities("NewUserCreated",$object->login);
            $desc=$langs->transnoentities("NewUserCreated",$object->login);
=======
            $text=$langs->transnoentities("NewUserCreated", $object->login);
            $desc=$langs->transnoentities("NewUserCreated", $object->login);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
        elseif ($action == 'USER_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");

            // Initialisation donnees (date,duree,texte,desc)
<<<<<<< HEAD
            $text=$langs->transnoentities("EventUserModified",$object->login);
            $desc=$langs->transnoentities("EventUserModified",$object->login);
=======
            $text=$langs->transnoentities("EventUserModified", $object->login);
            $desc=$langs->transnoentities("EventUserModified", $object->login);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
        elseif ($action == 'USER_NEW_PASSWORD')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");

            // Initialisation donnees (date,duree,texte,desc)
<<<<<<< HEAD
            $text=$langs->transnoentities("NewUserPassword",$object->login);
            $desc=$langs->transnoentities("NewUserPassword",$object->login);
=======
            $text=$langs->transnoentities("NewUserPassword", $object->login);
            $desc=$langs->transnoentities("NewUserPassword", $object->login);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
        elseif ($action == 'USER_ENABLEDISABLE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
			if ($object->statut == 0)
			{
<<<<<<< HEAD
				$text=$langs->transnoentities("UserEnabled",$object->login);
				$desc=$langs->transnoentities("UserEnabled",$object->login);
			}
			if ($object->statut == 1)
			{
				$text=$langs->transnoentities("UserDisabled",$object->login);
				$desc=$langs->transnoentities("UserDisabled",$object->login);
=======
				$text=$langs->transnoentities("UserEnabled", $object->login);
				$desc=$langs->transnoentities("UserEnabled", $object->login);
			}
			if ($object->statut == 1)
			{
				$text=$langs->transnoentities("UserDisabled", $object->login);
				$desc=$langs->transnoentities("UserDisabled", $object->login);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			}
        }
        elseif ($action == 'USER_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
<<<<<<< HEAD
            $text=$langs->transnoentities("UserDeleted",$object->login);
            $desc=$langs->transnoentities("UserDeleted",$object->login);
=======
            $text=$langs->transnoentities("UserDeleted", $object->login);
            $desc=$langs->transnoentities("UserDeleted", $object->login);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }

		// Groupes
        elseif ($action == 'GROUP_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
<<<<<<< HEAD
            $text=$langs->transnoentities("NewGroupCreated",$object->name);
            $desc=$langs->transnoentities("NewGroupCreated",$object->name);
=======
            $text=$langs->transnoentities("NewGroupCreated", $object->name);
            $desc=$langs->transnoentities("NewGroupCreated", $object->name);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
        elseif ($action == 'GROUP_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
<<<<<<< HEAD
            $text=$langs->transnoentities("GroupModified",$object->name);
            $desc=$langs->transnoentities("GroupModified",$object->name);
=======
            $text=$langs->transnoentities("GroupModified", $object->name);
            $desc=$langs->transnoentities("GroupModified", $object->name);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}
        elseif ($action == 'GROUP_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            $langs->load("users");
            // Initialisation donnees (date,duree,texte,desc)
<<<<<<< HEAD
            $text=$langs->transnoentities("GroupDeleted",$object->name);
            $desc=$langs->transnoentities("GroupDeleted",$object->name);
=======
            $text=$langs->transnoentities("GroupDeleted", $object->name);
            $desc=$langs->transnoentities("GroupDeleted", $object->name);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
		}

		// If not found
/*
        else
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.");
			return 0;
        }
*/

		// Add more information into desc from the context property
		if (! empty($desc) && ! empty($object->context['audit'])) $desc.=' - '.$object->context['audit'];

        // Add entry in event table
		include_once DOL_DOCUMENT_ROOT.'/core/class/events.class.php';

		$event=new Events($this->db);
        $event->type=$action;
        $event->dateevent=$date;
        $event->label=$text;
        $event->description=$desc;
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

            dol_syslog(get_class($this).": ".$this->error, LOG_ERR);
            return -1;
        }
    }
<<<<<<< HEAD

=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
