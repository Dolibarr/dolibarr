<?php
/* Copyright (c) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2013 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (c) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2012	   Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2014	   Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014	   Alexis Algoud		<alexis@atm-consulting.fr>
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
 *	 \file       htdocs/user/class/usergroup.class.php
 *	 \brief      File of class to manage user groups
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
if (! empty($conf->ldap->enabled)) require_once (DOL_DOCUMENT_ROOT."/core/class/ldap.class.php");


/**
 *	Class to manage user groups
 */
class UserGroup extends CommonObject
{
	public $element='usergroup';
	public $table_element='usergroup';
	protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	var $entity;		// Entity of group
	/**
	 * @deprecated
	 * @see name
	 */
	var $nom;			// Name of group
	var $globalgroup;	// Global group
	var $datec;			// Creation date of group
	var $datem;			// Modification date of group
	var $members=array();	// Array of users

	private $_tab_loaded=array();		// Array of cache of already loaded permissions

	var $oldcopy;		// To contains a clone of this when we need to save old properties of object


	/**
     *    Constructor de la classe
     *
     *    @param   DoliDb  $db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		return 0;
	}


	/**
	 *	Charge un objet group avec toutes ces caracteristiques (excpet ->members array)
	 *
	 *	@param      int		$id			id du groupe a charger
	 *	@param      string	$groupname	name du groupe a charger
	 *	@return		int					<0 if KO, >0 if OK
	 */
	function fetch($id='', $groupname='')
	{
		global $conf;

		$sql = "SELECT g.rowid, g.entity, g.nom as name, g.note, g.datec, g.tms as datem";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g";
		if ($groupname)
		{
			$sql.= " WHERE g.nom = '".$this->db->escape($groupname)."'";
		}
		else
		{
			$sql.= " WHERE g.rowid = ".$id;
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;
				$this->entity = $obj->entity;
				$this->name = $obj->name;
				$this->nom = $obj->name; // Deprecated
				$this->note = $obj->note;
				$this->datec = $obj->datec;
				$this->datem = $obj->datem;

				$this->members=$this->listUsersForGroup();


				// Retreive all extrafield for group
				// fetch optionals attributes and labels
				dol_include_once('/core/class/extrafields.class.php');
				$extrafields=new ExtraFields($this->db);
				$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
				$this->fetch_optionals($this->id,$extralabels);


				// Sav current LDAP Current DN
				//$this->ldap_dn = $this->_load_ldap_dn($this->_load_ldap_info(),0);
			}
			$this->db->free($result);
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


	/**
	 * 	Return array of groups objects for a particular user
	 *
	 *	@param		int		$userid 	User id to search
	 * 	@return		array     			Array of groups objects
	 */
	function listGroupsForUser($userid)
	{
		global $conf, $user;

		$ret=array();

		$sql = "SELECT g.rowid, ug.entity as usergroup_entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup as g,";
		$sql.= " ".MAIN_DB_PREFIX."usergroup_user as ug";
		$sql.= " WHERE ug.fk_usergroup = g.rowid";
		$sql.= " AND ug.fk_user = ".$userid;
		if(! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
		{
			$sql.= " AND g.entity IS NOT NULL";
		}
		else
		{
			$sql.= " AND g.entity IN (0,".$conf->entity.")";
		}
		$sql.= " ORDER BY g.nom";

		dol_syslog(get_class($this)."::listGroupsForUser", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{
				if (! array_key_exists($obj->rowid, $ret))
				{
					$newgroup=new UserGroup($this->db);
					$newgroup->fetch($obj->rowid);
					$ret[$obj->rowid]=$newgroup;
				}

				$ret[$obj->rowid]->usergroup_entity[]=$obj->usergroup_entity;
			}

			$this->db->free($result);

			return $ret;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Return array of User objects for group this->id (or all if this->id not defined)
	 *
	 * 	@param	string	$excludefilter		Filter to exclude
	 *  @param	int		$mode				0=Return array of user instance, 1=Return array of users id only
	 * 	@return	mixed						Array of users or -1 on error
	 */
	function listUsersForGroup($excludefilter='', $mode=0)
	{
		global $conf, $user;

		$ret=array();

		$sql = "SELECT u.rowid";
		if (! empty($this->id)) $sql.= ", ug.entity as usergroup_entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		if (! empty($this->id)) $sql.= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
		$sql.= " WHERE 1 = 1";
		if (! empty($this->id)) $sql.= " AND ug.fk_user = u.rowid";
		if (! empty($this->id)) $sql.= " AND ug.fk_usergroup = ".$this->id;
		if (! empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && ! $user->entity)
		{
			$sql.= " AND u.entity IS NOT NULL";
		}
		else
		{
			$sql.= " AND u.entity IN (0,".$conf->entity.")";
		}
		if (! empty($excludefilter)) $sql.=' AND ('.$excludefilter.')';

		dol_syslog(get_class($this)."::listUsersForGroup", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				if (! array_key_exists($obj->rowid, $ret))
				{
					if ($mode != 1)
					{
						$newuser=new User($this->db);
						$newuser->fetch($obj->rowid);
						$ret[$obj->rowid]=$newuser;
					}
					else $ret[$obj->rowid]=$obj->rowid;
				}
				if ($mode != 1 && ! empty($obj->usergroup_entity))
				{
					$ret[$obj->rowid]->usergroup_entity[]=$obj->usergroup_entity;
				}
			}

			$this->db->free($resql);

			return $ret;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *    Add a permission to a group
	 *
	 *    @param      int		$rid         id du droit a ajouter
	 *    @param      string	$allmodule   Ajouter tous les droits du module allmodule
	 *    @param      string	$allperms    Ajouter tous les droits du module allmodule, perms allperms
	 *    @return     int         			 > 0 if OK, < 0 if KO
	 */
	function addrights($rid,$allmodule='',$allperms='')
	{
		global $conf, $user, $langs;

		dol_syslog(get_class($this)."::addrights $rid, $allmodule, $allperms");
		$error=0;
		$whereforadd='';

		$this->db->begin();

		if (! empty($rid))
		{
			// Si on a demande ajout d'un droit en particulier, on recupere
			// les caracteristiques (module, perms et subperms) de ce droit.
			$sql = "SELECT module, perms, subperms";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE id = '".$this->db->escape($rid)."'";
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);
				$module=$obj->module;
				$perms=$obj->perms;
				$subperms=$obj->subperms;
			}
			else {
				$error++;
				dol_print_error($this->db);
			}

			// Where pour la liste des droits a ajouter
			$whereforadd="id=".$this->db->escape($rid);
			// Ajout des droits induits
			if ($subperms)   $whereforadd.=" OR (module='$module' AND perms='$perms' AND (subperms='lire' OR subperms='read'))";
			else if ($perms) $whereforadd.=" OR (module='$module' AND (perms='lire' OR perms='read') AND subperms IS NULL)";

			// Pour compatibilite, si lowid = 0, on est en mode ajout de tout
			// TODO A virer quand sera gere par l'appelant
			//if (substr($rid,-1,1) == 0) $whereforadd="module='$module'";
		}
		else {
			// Where pour la liste des droits a ajouter
			if (! empty($allmodule)) $whereforadd="module='".$this->db->escape($allmodule)."'";
			if (! empty($allperms))  $whereforadd=" AND perms='".$this->db->escape($allperms)."'";
		}

		// Ajout des droits de la liste whereforadd
		if (! empty($whereforadd))
		{
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE $whereforadd";
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$nid = $obj->id;

					$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights WHERE fk_usergroup = $this->id AND fk_id=".$nid;
					if (! $this->db->query($sql)) $error++;
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."usergroup_rights (fk_usergroup, fk_id) VALUES ($this->id, $nid)";
					if (! $this->db->query($sql)) $error++;

					$i++;
				}
			}
			else
			{
				$error++;
				dol_print_error($this->db);
			}
			
			if (! $error)
			{
			    $this->context = array('audit'=>$langs->trans("PermissionsAdd"));
			
			    // Call trigger
			    $result=$this->call_trigger('GROUP_MODIFY',$user);
			    if ($result < 0) { $error++; }
			    // End call triggers
			}			
		}

		if ($error) {
			$this->db->rollback();
			return -$error;
		}
		else {
			$this->db->commit();
			return 1;
		}

	}


	/**
	 *    Remove a permission from group
	 *
	 *    @param      int		$rid         id du droit a retirer
	 *    @param      string	$allmodule   Retirer tous les droits du module allmodule
	 *    @param      string	$allperms    Retirer tous les droits du module allmodule, perms allperms
	 *    @return     int         			 > 0 if OK, < 0 if OK
	 */
	function delrights($rid,$allmodule='',$allperms='')
	{
		global $conf, $user, $langs;

		$error=0;
		$wherefordel='';

		$this->db->begin();

		if (! empty($rid))
		{
			// Si on a demande supression d'un droit en particulier, on recupere
			// les caracteristiques module, perms et subperms de ce droit.
			$sql = "SELECT module, perms, subperms";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE id = '".$this->db->escape($rid)."'";
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);
				$module=$obj->module;
				$perms=$obj->perms;
				$subperms=$obj->subperms;
			}
			else {
				$error++;
				dol_print_error($this->db);
			}

			// Where pour la liste des droits a supprimer
			$wherefordel="id=".$this->db->escape($rid);
			// Suppression des droits induits
			if ($subperms=='lire' || $subperms=='read') $wherefordel.=" OR (module='$module' AND perms='$perms' AND subperms IS NOT NULL)";
			if ($perms=='lire' || $perms=='read')    $wherefordel.=" OR (module='$module')";

			// Pour compatibilite, si lowid = 0, on est en mode suppression de tout
			// TODO A virer quand sera gere par l'appelant
			//if (substr($rid,-1,1) == 0) $wherefordel="module='$module'";
		}
		else {
			// Where pour la liste des droits a supprimer
			if (! empty($allmodule)) $wherefordel="module='".$this->db->escape($allmodule)."'";
			if (! empty($allperms))  $wherefordel=" AND perms='".$this->db->escape($allperms)."'";
		}

		// Suppression des droits de la liste wherefordel
		if (! empty($wherefordel))
		{
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE $wherefordel";
			$sql.= " AND entity = ".$conf->entity;

			$result=$this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$nid = $obj->id;

					$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights";
					$sql.= " WHERE fk_usergroup = $this->id AND fk_id=".$nid;
					if (! $this->db->query($sql)) $error++;

					$i++;
				}
			}
			else
			{
				$error++;
				dol_print_error($this->db);
			}
			
			if (! $error)
			{
		        $this->context = array('audit'=>$langs->trans("PermissionsDelete"));
		        
			    // Call trigger
			    $result=$this->call_trigger('GROUP_MODIFY',$user);
			    if ($result < 0) { $error++; }
			    // End call triggers
			}
		}

		if ($error) {
			$this->db->rollback();
			return -$error;
		}
		else {
			$this->db->commit();
			return 1;
		}

	}


	/**
	 *  Charge dans l'objet group, la liste des permissions auquels le groupe a droit
	 *
	 *  @param      string	$moduletag	 	Name of module we want permissions ('' means all)
	 *	@return		int						<0 if KO, >0 if OK
	 */
	function getrights($moduletag='')
	{
		global $conf;

		if ($moduletag && isset($this->_tab_loaded[$moduletag]) && $this->_tab_loaded[$moduletag])
		{
			// Le fichier de ce module est deja charge
			return;
		}

		if (! empty($this->all_permissions_are_loaded))
		{
			// Si les permissions ont deja ete chargees, on quitte
			return;
		}

		/*
		 * Recuperation des droits
		 */
		$sql = "SELECT r.module, r.perms, r.subperms ";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup_rights as u, ".MAIN_DB_PREFIX."rights_def as r";
		$sql.= " WHERE r.id = u.fk_id";
		$sql.= " AND r.entity = ".$conf->entity;
		$sql.= " AND u.fk_usergroup = ".$this->id;
		$sql.= " AND r.perms IS NOT NULL";
		if ($moduletag) $sql.= " AND r.module = '".$this->db->escape($moduletag)."'";

		dol_syslog(get_class($this).'::getrights', LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				$module=$obj->module;
				$perms=$obj->perms;
				$subperms=$obj->subperms;

				if ($perms)
				{
					if (! isset($this->rights)) $this->rights = new stdClass(); // For avoid error
					if (! isset($this->rights->$module) || ! is_object($this->rights->$module)) $this->rights->$module = new stdClass();
					if ($subperms)
					{
						if (! isset($this->rights->$module->$perms) || ! is_object($this->rights->$module->$perms)) $this->rights->$module->$perms = new stdClass();
						$this->rights->$module->$perms->$subperms = 1;
					}
					else
					{
						$this->rights->$module->$perms = 1;
					}
				}

				$i++;
			}
			$this->db->free($resql);
		}

		if ($moduletag == '')
		{
			// Si module etait non defini, alors on a tout charge, on peut donc considerer
			// que les droits sont en cache (car tous charges) pour cet instance de group
			$this->all_permissions_are_loaded=1;
		}
		else
		{
		    // Si module defini, on le marque comme charge en cache
		    $this->_tab_loaded[$moduletag]=1;
		}

        return 1;
	}

	/**
	 *        Efface un groupe de la base
	 *
	 *        @return     <0 if KO, > 0 if OK
	 */
	function delete()
	{
		global $user,$conf,$langs;

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights";
		$sql .= " WHERE fk_usergroup = ".$this->id;
		$this->db->query($sql);

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_user";
		$sql .= " WHERE fk_usergroup = ".$this->id;
		$this->db->query($sql);

		// Remove extrafields
		if ((! $error) && (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))) // For avoid conflicts if trigger used
        {
			$result=$this->deleteExtraFields();
			if ($result < 0)
			{
           		$error++;
           		dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
           	}
        }

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup";
		$sql .= " WHERE rowid = ".$this->id;
		$result=$this->db->query($sql);
		if ($result)
		{
            // Call trigger
            $result=$this->call_trigger('GROUP_DELETE',$user);
            if ($result < 0) { $error++; $this->db->rollback(); return -1; }
            // End call triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *	Create group into database
	 *
	 *	@param		int		$notrigger	0=triggers enabled, 1=triggers disabled
	 *	@return     int					<0 if KO, >=0 if OK
	 */
	function create($notrigger=0)
	{
		global $user, $conf, $langs, $hookmanager;

		$error=0;
		$now=dol_now();

		if (! isset($this->entity)) $this->entity=$conf->entity;	// If not defined, we use default value

		$entity=$this->entity;
		if (! empty($conf->multicompany->enabled) && $conf->entity == 1) $entity=$this->entity;

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."usergroup (";
		$sql.= "datec";
		$sql.= ", nom";
		$sql.= ", entity";
		$sql.= ") VALUES (";
		$sql.= "'".$this->db->idate($now)."'";
		$sql.= ",'".$this->db->escape($this->nom)."'";
		$sql.= ",".$this->db->escape($entity);
		$sql.= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$result=$this->db->query($sql);
		if ($result)
		{
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."usergroup");

			if ($this->update(1) < 0) return -2;

			$action='create';

			// Actions on extra fields (by external module or standard code)
            // TODO le hook fait double emploi avec le trigger !!
			$hookmanager->initHooks(array('groupdao'));
			$parameters=array();
			$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			if (empty($reshook))
			{
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
				{
					$result=$this->insertExtraFields();
					if ($result < 0)
					{
						$error++;
					}
				}
			}
			else if ($reshook < 0) $error++;

			if (! $error && ! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('GROUP_CREATE',$user);
                if ($result < 0) { $error++; $this->db->rollback(); return -1; }
                // End call triggers
			}

			if ($error > 0) { $error++; $this->db->rollback(); return -1; }
			else $this->db->commit();

			return $this->id;
		}
		else
		{
		    $this->db->rollback();
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *		Update group into database
	 *
	 *      @param      int		$notrigger	    0=triggers enabled, 1=triggers disabled
	 *    	@return     int						<0 if KO, >=0 if OK
	 */
	function update($notrigger=0)
	{
		global $user, $conf, $langs, $hookmanager;

		$error=0;

		$entity=$conf->entity;
		if(! empty($conf->multicompany->enabled) && $conf->entity == 1)
		{
			$entity=$this->entity;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."usergroup SET ";
		$sql.= " nom = '" . $this->db->escape($this->name) . "'";
		$sql.= ", entity = " . $this->db->escape($entity);
		$sql.= ", note = '" . $this->db->escape($this->note) . "'";
		$sql.= " WHERE rowid = " . $this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$action='update';

			// Actions on extra fields (by external module or standard code)
            // TODO le hook fait double emploi avec le trigger !!
			$hookmanager->initHooks(array('groupdao'));
			$parameters=array();
			$reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			if (empty($reshook))
			{
				if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
				{
					$result=$this->insertExtraFields();
					if ($result < 0)
					{
						$error++;
					}
				}
			}
			else if ($reshook < 0) $error++;

			if (! $error && ! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('GROUP_MODIFY',$user);
                if ($result < 0) { $error++; }
                // End call triggers
			}

			if (! $error)
			{
			    $this->db->commit();
			    return 1;
			}
			else
			{
			    $this->db->rollback();
			    return -$error;
			}
		}
		else
		{
		    $this->db->rollback();
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param		array	$info		Info array loaded by _load_ldap_info
	 *	@param		int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *									1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *									2=Return key only (uid=qqq)
	 *	@return		string				DN
	 */
	function _load_ldap_dn($info,$mode=0)
	{
		global $conf;
		$dn='';
		if ($mode==0) $dn=$conf->global->LDAP_KEY_GROUPS."=".$info[$conf->global->LDAP_KEY_GROUPS].",".$conf->global->LDAP_GROUP_DN;
		if ($mode==1) $dn=$conf->global->LDAP_GROUP_DN;
		if ($mode==2) $dn=$conf->global->LDAP_KEY_GROUPS."=".$info[$conf->global->LDAP_KEY_GROUPS];
		return $dn;
	}


	/**
	 *	Initialize the info array (array of LDAP values) that will be used to call LDAP functions
	 *
	 *	@return		array		Tableau info des attributs
	 */
	function _load_ldap_info()
	{
		global $conf,$langs;
		$info=array();

		// Object classes
		$info["objectclass"]=explode(',',$conf->global->LDAP_GROUP_OBJECT_CLASS);

		// Champs
		if ($this->name && ! empty($conf->global->LDAP_GROUP_FIELD_FULLNAME)) $info[$conf->global->LDAP_GROUP_FIELD_FULLNAME] = $this->name;
		//if ($this->name && ! empty($conf->global->LDAP_GROUP_FIELD_NAME)) $info[$conf->global->LDAP_GROUP_FIELD_NAME] = $this->name;
		if ($this->note && ! empty($conf->global->LDAP_GROUP_FIELD_DESCRIPTION)) $info[$conf->global->LDAP_GROUP_FIELD_DESCRIPTION] = $this->note;
		if (! empty($conf->global->LDAP_GROUP_FIELD_GROUPMEMBERS))
		{
			$valueofldapfield=array();
			foreach($this->members as $key=>$val)    // This is array of users for group into dolibarr database.
			{
				$muser=new User($this->db);
				$muser->fetch($val->id);
				$info2 = $muser->_load_ldap_info();
                                $valueofldapfield[] = $muser->_load_ldap_dn($info2);
			}
			$info[$conf->global->LDAP_GROUP_FIELD_GROUPMEMBERS] = (!empty($valueofldapfield)?$valueofldapfield:'');
		}
		return $info;
	}


	/**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
	 */
	function initAsSpecimen()
	{
		global $conf, $user, $langs;

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;

		$this->name='DOLIBARR GROUP SPECIMEN';
		$this->note='This is a note';
		$this->datec=time();
		$this->datem=time();
		$this->members=array($user->id);	// Members of this group is just me
	}
}

