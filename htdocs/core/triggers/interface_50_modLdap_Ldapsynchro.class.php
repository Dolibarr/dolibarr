<?php
/* Copyright (C) 2005-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *  \file       htdocs/core/triggers/interface_50_modLdap_Ldapsynchro.class.php
 *  \ingroup    core
 *  \brief      Fichier de gestion des triggers LDAP
 */
require_once (DOL_DOCUMENT_ROOT."/core/class/ldap.class.php");


/**
 *  Class of triggers for ldap module
 */
class InterfaceLdapsynchro
{
    var $db;
    var $error;


    /**
     *   Constructor
     *
     *   @param		DoliDB		$db      Database handler
     */
    function InterfaceLdapsynchro($db)
    {
        $this->db = $db;

        $this->name = preg_replace('/^Interface/i','',get_class($this));
        $this->family = "ldap";
        $this->description = "Triggers of this module allows to synchronize Dolibarr toward a LDAP database.";
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
        if (empty($conf->ldap->enabled)) return 0;     // Module not active, we do nothing

        if (! function_exists('ldap_connect'))
        {
        	dol_syslog("Warning, module LDAP is enabled but LDAP functions not available in this PHP", LOG_WARNING);
        	return 0;
        }

        // Users
        if ($action == 'USER_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->add($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
    		}
        }
        elseif ($action == 'USER_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

        		$oldinfo=$object->oldcopy->_load_ldap_info();
        		$olddn=$object->oldcopy->_load_ldap_dn($oldinfo);

        		// Verify if entry exist
        		$container=$object->oldcopy->_load_ldap_dn($oldinfo,1);
        		$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo,2).")";
        		$records=$ldap->search($container,$search);
        		if (count($records) && $records['count'] == 0)
        		{
        			$olddn = '';
        		}

        		$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->update($dn,$info,$user,$olddn);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
    		}
        }
        elseif ($action == 'USER_NEW_PASSWORD')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
            {
                $ldap=new Ldap();
                $ldap->connect_bind();

                $oldinfo=$object->oldcopy->_load_ldap_info();
                $olddn=$object->oldcopy->_load_ldap_dn($oldinfo);

                // Verify if entry exist
                $container=$object->oldcopy->_load_ldap_dn($oldinfo,1);
                $search = "(".$object->oldcopy->_load_ldap_dn($oldinfo,2).")";
                $records=$ldap->search($container,$search);
                if (count($records) && $records['count'] == 0)
                {
                    $olddn = '';
                }

                $info=$object->_load_ldap_info();
                $dn=$object->_load_ldap_dn($info);

                $result=$ldap->update($dn,$info,$user,$olddn);
                if ($result < 0)
                {
                    $this->error="ErrorLDAP ".$ldap->error;
                }
                return $result;
            }
        }
        elseif ($action == 'USER_ENABLEDISABLE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        }
        elseif ($action == 'USER_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->delete($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
    		}
        }

		// Groupes
        elseif ($action == 'GROUP_CREATE')
        {
        	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

				// Get a gid number for objectclass PosixGroup
				if(in_array('posixGroup',$info['objectclass']))
					$info['gidNumber'] = $ldap->getNextGroupGid();

	    	    $result=$ldap->add($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
    		}
		}
        elseif ($action == 'GROUP_MODIFY')
        {
        	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

        		$oldinfo=$object->oldcopy->_load_ldap_info();
        		$olddn=$object->oldcopy->_load_ldap_dn($oldinfo);

        	    // Verify if entry exist
        		$container=$object->oldcopy->_load_ldap_dn($oldinfo,1);
        		$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo,2).")";
        		$records=$ldap->search($container,$search);
        		if (count($records) && $records['count'] == 0)
        		{
        			$olddn = '';
        		}

        		$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->update($dn,$info,$user,$olddn);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
    		}
		}
        elseif ($action == 'GROUP_DELETE')
        {
        	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->delete($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
    		}
		}

        // Contacts
        elseif ($action == 'CONTACT_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
	      	if ($conf->ldap->enabled && $conf->global->LDAP_CONTACT_ACTIVE)
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->add($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
    		}
        }
        elseif ($action == 'CONTACT_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_CONTACT_ACTIVE)
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

        		$oldinfo=$object->oldcopy->_load_ldap_info();
        		$olddn=$object->oldcopy->_load_ldap_dn($oldinfo);

        		// Verify if entry exist
        		$container=$object->oldcopy->_load_ldap_dn($oldinfo,1);
        		$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo,2).")";
        		$records=$ldap->search($container,$search);
        		if (count($records) && $records['count'] == 0)
        		{
        			$olddn = '';
        		}

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->update($dn,$info,$user,$olddn);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
    		}
        }
        elseif ($action == 'CONTACT_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
	    	if ($conf->ldap->enabled && $conf->global->LDAP_CONTACT_ACTIVE)
	    	{
	    		$ldap=new Ldap();
	    		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->delete($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
	    	    return $result;
			}
        }

        // Members
        elseif ($action == 'MEMBER_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->add($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
	    	    return $result;
    		}
        }
        elseif ($action == 'MEMBER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
        	{
				// If status field is setup to be synchronized
				if ($conf->global->LDAP_FIELD_MEMBER_STATUS)
				{
					$ldap=new Ldap();
	        		$ldap->connect_bind();

	        		$info=$object->_load_ldap_info();
					$dn=$object->_load_ldap_dn($info);
					$olddn=$dn;	// We know olddn=dn as we change only status

		    	    $result=$ldap->update($dn,$info,$user,$olddn);
					if ($result < 0)
					{
						$this->error="ErrorLDAP ".$ldap->error;
					}
		    	    return $result;
				}
			}
        }
        elseif ($action == 'MEMBER_SUBSCRIPTION')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
        	{
				// If subscriptions fields are setup to be synchronized
				if ($conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE
				|| $conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT
				|| $conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE
				|| $conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT
				|| $conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION)
				{
					$ldap=new Ldap();
	        		$ldap->connect_bind();

	        		$info=$object->_load_ldap_info();
					$dn=$object->_load_ldap_dn($info);
					$olddn=$dn;	// We know olddn=dn as we change only subscriptions

		    	    $result=$ldap->update($dn,$info,$user,$olddn);
					if ($result < 0)
					{
						$this->error="ErrorLDAP ".$ldap->error;
					}
		    	    return $result;
				}
			}
        }
        elseif ($action == 'MEMBER_MODIFY')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

        		$oldinfo=$object->oldcopy->_load_ldap_info();
        		$olddn=$object->oldcopy->_load_ldap_dn($oldinfo);

        		// Verify if entry exist
        		$container=$object->oldcopy->_load_ldap_dn($oldinfo,1);
        		$search = "(".$object->oldcopy->_load_ldap_dn($oldinfo,2).")";
        		$records=$ldap->search($container,$search);
        		if (count($records) && $records['count'] == 0)
        		{
        			$olddn = '';
        		}

        		$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->update($dn,$info,$user,$olddn);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
	    	    return $result;
    		}
        }
        elseif ($action == 'MEMBER_NEW_PASSWORD')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
        	{
				// If password field is setup to be synchronized
				if ($conf->global->LDAP_FIELD_PASSWORD || $conf->global->LDAP_FIELD_PASSWORD_CRYPTED)
				{
					$ldap=new Ldap();
	        		$ldap->connect_bind();

        			$info=$object->_load_ldap_info();
					$dn=$object->_load_ldap_dn($info);
					$olddn=$dn;	// We know olddn=dn as we change only password

		    	    $result=$ldap->update($dn,$info,$user,$olddn);
					if ($result < 0)
					{
						$this->error="ErrorLDAP ".$ldap->error;
					}
		    	    return $result;
				}
			}
		}
        elseif ($action == 'MEMBER_RESILIATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
        	{
				// If status field is setup to be synchronized
				if ($conf->global->LDAP_FIELD_MEMBER_STATUS)
				{
					$ldap=new Ldap();
	        		$ldap->connect_bind();

	        		$info=$object->_load_ldap_info();
					$dn=$object->_load_ldap_dn($info);
					$olddn=$dn;	// We know olddn=dn as we change only status

		    	    $result=$ldap->update($dn,$info,$user,$olddn);
					if ($result < 0)
					{
						$this->error="ErrorLDAP ".$ldap->error;
					}
		    	    return $result;
				}
			}
        }
        elseif ($action == 'MEMBER_DELETE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
			if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
			{
				$ldap=new Ldap();
				$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

				$result=$ldap->delete($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
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
