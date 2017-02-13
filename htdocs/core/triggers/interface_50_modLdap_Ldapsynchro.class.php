<?php
/* Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2014       Marcos García       <marcosgdf@gmail.com>
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
 *  \file       htdocs/core/triggers/interface_50_modLdap_Ldapsynchro.class.php
 *  \ingroup    core
 *  \brief      Fichier de gestion des triggers LDAP
 */

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';
require_once DOL_DOCUMENT_ROOT."/core/class/ldap.class.php";
require_once DOL_DOCUMENT_ROOT."/user/class/usergroup.class.php";


/**
 *  Class of triggers for ldap module
 */
class InterfaceLdapsynchro extends DolibarrTriggers
{
	public $family = 'ldap';
	public $description = "Triggers of this module allows to synchronize Dolibarr toward a LDAP database.";
	public $version = self::VERSION_DOLIBARR;
	public $picto = 'technic';

	/**
	 * Function called when a Dolibarrr business event is done.
	 * All functions "runTrigger" are triggered if file is inside directory htdocs/core/triggers or htdocs/module/code/triggers (and declared)
	 *
	 * @param string		$action		Event action code
	 * @param Object		$object     Object
	 * @param User		    $user       Object user
	 * @param Translate 	$langs      Object langs
	 * @param conf		    $conf       Object conf
	 * @return int         				<0 if KO, 0 if no triggered ran, >0 if OK
	 */
	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
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
        	if (! empty($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE === 'dolibarr2ldap')
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
        	if (! empty($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE === 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

        	    if (empty($object->oldcopy) || ! is_object($object->oldcopy))
                {
                	dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
					$object->oldcopy = clone $object;
                }

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
            if (! empty($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE === 'dolibarr2ldap')
            {
                $ldap=new Ldap();
                $ldap->connect_bind();

                if (empty($object->oldcopy) || ! is_object($object->oldcopy))
                {
                	dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
					$object->oldcopy = clone $object;
                }

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
        	if (! empty($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE === 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->delete($dn);
				if ($result < 0)
				{
					$this->error="ErrorLDAP ".$ldap->error;
				}
				return $result;
    		}
        }
        elseif ($action == 'USER_SETINGROUP')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            if (! empty($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE === 'dolibarr2ldap')
            {
                $ldap=new Ldap();
                $ldap->connect_bind();

                // Must edit $object->newgroupid
                $usergroup=new UserGroup($this->db);
                if ($object->newgroupid > 0)
                {
                    $usergroup->fetch($object->newgroupid);

                    $oldinfo=$usergroup->_load_ldap_info();
                    $olddn=$usergroup->_load_ldap_dn($oldinfo);

                    // Verify if entry exist
                    $container=$usergroup->_load_ldap_dn($oldinfo,1);
                    $search = "(".$usergroup->_load_ldap_dn($oldinfo,2).")";
                    $records=$ldap->search($container,$search);
                    if (count($records) && $records['count'] == 0)
                    {
                        $olddn = '';
                    }

                    $info=$usergroup->_load_ldap_info();    // Contains all members, included the new one (insert already done before trigger call)
                    $dn=$usergroup->_load_ldap_dn($info);

                    $result=$ldap->update($dn,$info,$user,$olddn);
                    if ($result < 0)
                    {
                        $this->error="ErrorLDAP ".$ldap->error;
                    }
                }
                return $result;
            }
        }
        elseif ($action == 'USER_REMOVEFROMGROUP')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
            if (! empty($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE === 'dolibarr2ldap')
            {
                $ldap=new Ldap();
                $ldap->connect_bind();

                // Must edit $object->newgroupid
                $usergroup=new UserGroup($this->db);
                if ($object->oldgroupid > 0)
                {
                    $usergroup->fetch($object->oldgroupid);

                    $oldinfo=$usergroup->_load_ldap_info();
                    $olddn=$usergroup->_load_ldap_dn($oldinfo);

                    // Verify if entry exist
                    $container=$usergroup->_load_ldap_dn($oldinfo,1);
                    $search = "(".$usergroup->_load_ldap_dn($oldinfo,2).")";
                    $records=$ldap->search($container,$search);
                    if (count($records) && $records['count'] == 0)
                    {
                        $olddn = '';
                    }

                    $info=$usergroup->_load_ldap_info();    // Contains all members, included the new one (insert already done before trigger call)
                    $dn=$usergroup->_load_ldap_dn($info);

                    $result=$ldap->update($dn,$info,$user,$olddn);
                    if ($result < 0)
                    {
                        $this->error="ErrorLDAP ".$ldap->error;
                    }
                }
                return $result;
            }
        }

		// Groupes
        elseif ($action == 'GROUP_CREATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if (! empty($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE === 'dolibarr2ldap')
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
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if (! empty($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE === 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

        	    if (empty($object->oldcopy) || ! is_object($object->oldcopy))
                {
                	dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
					$object->oldcopy = clone $object;
                }

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
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if (! empty($conf->global->LDAP_SYNCHRO_ACTIVE) && $conf->global->LDAP_SYNCHRO_ACTIVE === 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->delete($dn);
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
	      	if (! empty($conf->global->LDAP_CONTACT_ACTIVE))
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
        	if (! empty($conf->global->LDAP_CONTACT_ACTIVE))
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

        	    if (empty($object->oldcopy) || ! is_object($object->oldcopy))
                {
                	dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
					$object->oldcopy = clone $object;
                }

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
	    	if (! empty($conf->global->LDAP_CONTACT_ACTIVE))
	    	{
	    		$ldap=new Ldap();
	    		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->delete($dn);
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
        	if (! empty($conf->global->LDAP_MEMBER_ACTIVE) && (string) $conf->global->LDAP_MEMBER_ACTIVE == '1')
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
        	if (! empty($conf->global->LDAP_MEMBER_ACTIVE) && (string) $conf->global->LDAP_MEMBER_ACTIVE == '1')
        	{
				// If status field is setup to be synchronized
				if (! empty($conf->global->LDAP_FIELD_MEMBER_STATUS))
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
        	if (! empty($conf->global->LDAP_MEMBER_ACTIVE) && (string) $conf->global->LDAP_MEMBER_ACTIVE == '1')
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
        	if (! empty($conf->global->LDAP_MEMBER_ACTIVE) && (string) $conf->global->LDAP_MEMBER_ACTIVE == '1')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

        	    if (empty($object->oldcopy) || ! is_object($object->oldcopy))
                {
                	dol_syslog("Trigger ".$action." was called by a function that did not set previously the property ->oldcopy onto object", LOG_WARNING);
					$object->oldcopy = clone $object;
                }

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
        	if (! empty($conf->global->LDAP_MEMBER_ACTIVE) && (string) $conf->global->LDAP_MEMBER_ACTIVE == '1')
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
        	if (! empty($conf->global->LDAP_MEMBER_ACTIVE) && (string) $conf->global->LDAP_MEMBER_ACTIVE == '1')
        	{
				// If status field is setup to be synchronized
				if (! empty($conf->global->LDAP_FIELD_MEMBER_STATUS))
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
			if (! empty($conf->global->LDAP_MEMBER_ACTIVE) && (string) $conf->global->LDAP_MEMBER_ACTIVE == '1')
			{
				$ldap=new Ldap();
				$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

				$result=$ldap->delete($dn);
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
