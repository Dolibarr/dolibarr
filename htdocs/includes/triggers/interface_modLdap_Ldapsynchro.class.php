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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
        \file       htdocs/includes/triggers/interface_modLdap_Ldapsynchro.class.php
        \ingroup    core
        \brief      Fichier de gestion des triggers LDAP
		\version	$Id$
*/

require_once (DOL_DOCUMENT_ROOT."/lib/ldap.class.php");


/**
        \class      InterfaceLdapsynchro
        \brief      Classe des fonctions triggers des actions de synchro LDAP
*/

class InterfaceLdapsynchro
{
    var $db;
    var $error;


    /**
     *   \brief      Constructeur.
     *   \param      DB      Handler d'acces base
     */
    function InterfaceLdapsynchro($DB)
    {
        $this->db = $DB ;

        $this->name = eregi_replace('Interface','',get_class($this));
        $this->family = "ldap";
        $this->description = "Triggers of this module allows to synchronize Dolibarr toward a LDAP database.";
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
     *      \brief      Fonction appel�e lors du d�clenchement d'un �v�nement Dolibarr.
     *                  D'autres fonctions run_trigger peuvent etre pr�sentes dans includes/triggers
     *      \param      action      Code de l'evenement
     *      \param      object      Objet concern�
     *      \param      user        Objet user
     *      \param      lang        Objet lang
     *      \param      conf        Objet conf
     *      \return     int         <0 si ko, 0 si aucune action faite, >0 si ok
     */
	function run_trigger($action,$object,$user,$langs,$conf)
    {
        // Mettre ici le code � ex�cuter en r�action de l'action
        // Les donn�es de l'action sont stock�es dans $object

        if (! $conf->ldap->enabled) return 0;     // Module non actif

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
					$this->error="ErrorLDAP"." ".$ldap->error;
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

				$oldobject=$object;	// TODO Get oldobject

        		$oldinfo=$oldobject->_load_ldap_info();
        		$olddn=$oldobject->_load_ldap_dn($oldinfo);

        		$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->update($dn,$info,$user,$olddn);
				if ($result < 0)
				{
					$this->error="ErrorLDAP"." ".$ldap->error;
				}
				return $result;
    		}
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
        	if ($conf->ldap->enabled && $conf->global->LDAP_SYNCHRO_ACTIVE == 'dolibarr2ldap')
        	{
        		$ldap=new Ldap();
        		$ldap->connect_bind();

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->delete($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP"." ".$ldap->error;
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

	    	    $result=$ldap->add($dn,$info,$user);
				if ($result < 0)
				{
					$this->error="ErrorLDAP"." ".$ldap->error;
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

				$oldobject=$object;	// TODO Get oldobject

        		$oldinfo=$oldobject->_load_ldap_info();
        		$olddn=$oldobject->_load_ldap_dn($oldinfo);

        		$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->update($dn,$info,$user,$olddn);
				if ($result < 0)
				{
					$this->error="ErrorLDAP"." ".$ldap->error;
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
					$this->error="ErrorLDAP"." ".$ldap->error;
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
					$this->error="ErrorLDAP"." ".$ldap->error;
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

				$oldobject=$object;	// TODO Get oldobject

        		$oldinfo=$oldobject->_load_ldap_info();
        		$olddn=$oldobject->_load_ldap_dn($oldinfo);

				$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->update($dn,$info,$user,$olddn);
				if ($result < 0)
				{
					$this->error="ErrorLDAP"." ".$ldap->error;
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
					$this->error="ErrorLDAP"." ".$ldap->error;
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
					$this->error="ErrorLDAP"." ".$ldap->error;
				}
	    	    return $result;
    		}
        }
        elseif ($action == 'MEMBER_VALIDATE')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
        	{
				# If status field is setup to be synchronized
				if ($conf->global->LDAP_FIELD_MEMBER_STATUS)
				{
					$ldap=new Ldap();
	        		$ldap->connect_bind();

					$oldobject=$object;	// TODO Get oldobject

	        		$oldinfo=$oldobject->_load_ldap_info();
	        		$olddn=$oldobject->_load_ldap_dn($oldinfo);

	        		$info=$object->_load_ldap_info();
					$dn=$object->_load_ldap_dn($info);

		    	    $result=$ldap->update($dn,$info,$user,$olddn);
					if ($result < 0)
					{
						$this->error="ErrorLDAP"." ".$ldap->error;
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
				# If subscriptions fields are setup to be synchronized
				if ($conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_DATE
				|| $conf->global->LDAP_FIELD_MEMBER_FIRSTSUBSCRIPTION_AMOUNT
				|| $conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_DATE
				|| $conf->global->LDAP_FIELD_MEMBER_LASTSUBSCRIPTION_AMOUNT
				|| $conf->global->LDAP_FIELD_MEMBER_END_LASTSUBSCRIPTION)
				{
					$ldap=new Ldap();
	        		$ldap->connect_bind();

					$oldobject=$object;	// TODO Get oldobject

	        		$oldinfo=$oldobject->_load_ldap_info();
	        		$olddn=$oldobject->_load_ldap_dn($oldinfo);

	        		$info=$object->_load_ldap_info();
					$dn=$object->_load_ldap_dn($info);

		    	    $result=$ldap->update($dn,$info,$user,$olddn);
					if ($result < 0)
					{
						$this->error="ErrorLDAP"." ".$ldap->error;
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

				$oldobject=$object;	// TODO Get oldobject

        		$oldinfo=$oldobject->_load_ldap_info();
        		$olddn=$oldobject->_load_ldap_dn($oldinfo);

        		$info=$object->_load_ldap_info();
				$dn=$object->_load_ldap_dn($info);

	    	    $result=$ldap->update($dn,$info,$user,$olddn);
				if ($result < 0)
				{
					$this->error="ErrorLDAP"." ".$ldap->error;
				}
	    	    return $result;
    		}
        }
        elseif ($action == 'MEMBER_NEW_PASSWORD')
        {
            dol_syslog("Trigger '".$this->name."' for action '$action' launched by ".__FILE__.". id=".$object->id);
        	if ($conf->ldap->enabled && $conf->global->LDAP_MEMBER_ACTIVE)
        	{
				# If password field is setup to be synchronized
				if ($conf->global->LDAP_FIELD_PASSWORD || $conf->global->LDAP_FIELD_PASSWORD_CRYPTED)
				{
					$ldap=new Ldap();
	        		$ldap->connect_bind();

					$info=$object->_load_ldap_info();
					$dn=$object->_load_ldap_dn($info);

		    	    $result=$ldap->update($dn,$info,$user);
					if ($result < 0)
					{
						$this->error="ErrorLDAP"." ".$ldap->error;
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
				# If status field is setup to be synchronized
				if ($conf->global->LDAP_FIELD_MEMBER_STATUS)
				{
					$ldap=new Ldap();
	        		$ldap->connect_bind();

					$oldobject=$object;	// TODO Get oldobject

	        		$oldinfo=$oldobject->_load_ldap_info();
	        		$olddn=$oldobject->_load_ldap_dn($oldinfo);

	        		$info=$object->_load_ldap_info();
					$dn=$object->_load_ldap_dn($info);

		    	    $result=$ldap->update($dn,$info,$user,$olddn);
					if ($result < 0)
					{
						$this->error="ErrorLDAP"." ".$ldap->error;
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
					$this->error="ErrorLDAP"." ".$ldap->error;
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
