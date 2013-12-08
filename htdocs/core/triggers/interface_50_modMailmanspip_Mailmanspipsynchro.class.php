<?php
/* Copyright (C) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/core/triggers/interface_50_modMailmanspip_Mailmanspipsynchro.class.php
 *  \ingroup    core
 *  \brief      File to manage triggers Mailman and Spip
 */
require_once (DOL_DOCUMENT_ROOT."/mailmanspip/class/mailmanspip.class.php");
require_once (DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php");


/**
 *  Class of triggers for MailmanSpip module
 */
class InterfaceMailmanSpipsynchro
{
    var $db;
    var $error;


    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "ldap";
        $this->description = "Triggers of this module allows to synchronize Mailman an Spip.";
        $this->version = 'dolibarr';                        // 'experimental' or 'dolibarr' or version
        $this->picto = 'technic';
    }

    /**
     *   Return name of trigger file
     *
     *   @return     string      Name of trigger file
     */
    function getName()
    {
        return $this->name;
    }

    /**
     *   Return description of trigger file
     *
     *   @return     string      Description of trigger file
     */
    function getDesc()
    {
        return $this->description;
    }

    /**
     *   Return version of trigger file
     *
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
     *
     *      @param	string		$action		Event action code
     *      @param  Object		$object     Object
     *      @param  User		$user       Object user
     *      @param  Translate	$langs      Object langs
     *      @param  conf		$conf       Object conf
     *      @return int         			<0 if KO, 0 if no triggered ran, >0 if OK
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        if (empty($conf->mailmanspip->enabled)) return 0;     // Module not active, we do nothing

        if (! function_exists('ldap_connect'))
        {
        	dol_syslog("Warning, module LDAP is enabled but LDAP functions not available in this PHP", LOG_WARNING);
        	return 0;
        }

        // Users
        if ($action == 'USER_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

        }
        elseif ($action == 'USER_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_NEW_PASSWORD')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_ENABLEDISABLE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_SETINGROUP')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_REMOVEFROMGROUP')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }

        elseif ($action == 'CATEGORY_LINK')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

        	// We add subscription if we change category (new category may means more mailing-list to subscribe)
    		if ($object->linkto->add_to_abo() < 0)
    		{
    			$this->error=$object->linkto->error;
    			$this->errors=$object->linkto->errors;
    			$return=-1;
    		}
			else
			{
				$return=1;
			}

        	return $return;
        }
        elseif ($action == 'CATEGORY_UNLINK')
        {
        	dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

        	// We remove subscription if we change category (lessw category may means less mailing-list to subscribe)
        	if ($object->unlinkoff->del_to_abo() < 0)
        	{
    			$this->error=$object->unlinkoff->error;
        		$this->errors=$object->unlinkoff->errors;
        		$return=-1;
        	}
        	else
        	{
        		$return=1;
        	}

        	return $return;
        }

        // Members
        elseif ($action == 'MEMBER_VALIDATE' || $action == 'MEMBER_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

			$return=0;
            // Add user into some linked tools (mailman, spip, etc...)
			if (($object->oldcopy->email != $object->email) || ($object->oldcopy->typeid != $object->typeid))	// TODO Do del/add also if type change
			{
				if (is_object($object->oldcopy) && ($object->oldcopy->email != $object->email))    // If email has changed we delete mailman subscription for old email
				{
					if ($object->oldcopy->del_to_abo() < 0)
					{
						if (! empty($object->oldcopy->error)) $this->error=$object->oldcopy->error;
						$this->errors=$object->oldcopy->errors;
						$return=-1;
					}
					else
					{
						$return=1;
					}
				}
    			// We add subscription if new email or new type (new type may means more mailing-list to subscribe)
    			if ($object->add_to_abo() < 0)
    			{
    				 if (! empty($object->error)) $this->error=$object->error;
    				 $this->errors=$object->errors;
    				 $return=-1;
    			}
				else
				{
					$return=1;
				}
			}

			return $return;
        }
        elseif ($action == 'MEMBER_NEW_PASSWORD')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
		}
        elseif ($action == 'MEMBER_RESILIATE' || $action == 'MEMBER_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);

            $return=0;
            // Remove from external tools (mailman, spip, etc...)
        	if ($object->del_to_abo() < 0)
			{
				if (! empty($object->error)) $this->error=$object->error;
				$this->errors=$object->errors;
				$return=-1;
			}
			else
			{
				$return=1;
			}
        }

		// If not found
/*
        else
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' was ran by ".__FILE__." but no handler found for this action.");
			return -1;
        }
*/
		return 0;
    }

}
?>
