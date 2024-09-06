<?php
/* Copyright (c) 2005       Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2018	Laurent Destailleur	 <eldy@users.sourceforge.net>
 * Copyright (c) 2005-2018	Regis Houssin		 <regis.houssin@inodbox.com>
 * Copyright (C) 2012		Florian Henry		 <florian.henry@open-concept.pro>
 * Copyright (C) 2014		Juanjo Menent		 <jmenent@2byte.es>
 * Copyright (C) 2014		Alexis Algoud		 <alexis@atm-consulting.fr>
 * Copyright (C) 2018       Nicolas ZABOURI		 <info@inovea-conseil.com>
 * Copyright (C) 2019       Abbes Bahfir            <dolipar@dolipar.org>
 * Copyright (C) 2023-2024  Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	 \file       htdocs/user/class/usergroup.class.php
 *	 \brief      File of class to manage user groups
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
if (isModEnabled('ldap')) {
	require_once DOL_DOCUMENT_ROOT."/core/class/ldap.class.php";
}


/**
 *	Class to manage user groups
 */
class UserGroup extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'usergroup';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'usergroup';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'group';

	/**
	 * @var int Entity of group
	 */
	public $entity;

	/**
	 * @var string
	 * @deprecated
	 * @see $name
	 */
	public $nom;

	/**
	 * @var string name
	 */
	public $name; // Name of group

	public $globalgroup; // Global group

	/**
	 * @var array<int>		Entity in table llx_user_group
	 * @deprecated			Seems not used.
	 */
	public $usergroup_entity;

	/**
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * @var string Description
	 */
	public $note;

	/**
	 * @var User[]
	 */
	public $members = array(); // Array of users

	public $nb_rights; // Number of rights granted to the user
	public $nb_users;  // Number of users in the group

	public $rights;	// Permissions of the group

	private $_tab_loaded = array(); // Array of cache of already loaded permissions

	/**
	 * @var int all_permissions_are_loaded
	 */
	public $all_permissions_are_loaded;

	public $oldcopy; // To contains a clone of this when we need to save old properties of object

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'index' => 1, 'position' => 1, 'comment' => 'Id'),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'enabled' => 1, 'visible' => 0, 'notnull' => 1, 'default' => '1', 'index' => 1, 'position' => 5),
		'nom' => array('type' => 'varchar(180)', 'label' => 'Name', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 10, 'searchall' => 1, 'comment' => 'Group name'),
		'note' => array('type' => 'html', 'label' => 'Description', 'enabled' => 1, 'visible' => 1, 'position' => 20, 'notnull' => -1, 'searchall' => 1),
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => 1, 'visible' => -2, 'position' => 50, 'notnull' => 1,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => 1, 'visible' => -2, 'position' => 60, 'notnull' => 1,),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'ModelPDF', 'enabled' => 1, 'visible' => 0, 'position' => 100),
	);

	/**
	 * @var string    Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_usergroup';

	/**
	 * @var array<string, array<string>>	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array();

	/**
	 * @var string[]	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade = array('usergroup_rights', 'usergroup_user');


	/**
	 *    Class constructor
	 *
	 *    @param   DoliDB  $db     Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->nb_rights = 0;
	}


	/**
	 *  Load a group object with all properties (except ->members array that is array of users in group)
	 *
	 *	@param      int		$id				Id of group to load
	 *	@param      string	$groupname		Name of group to load
	 *  @param		boolean	$load_members	Load all members of the group
	 *	@return		int						Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id = 0, $groupname = '', $load_members = false)
	{
		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		if (!empty($groupname)) {
			$result = $this->fetchCommon(0, '', ' AND nom = \''.$this->db->escape($groupname).'\'');
		} else {
			$result = $this->fetchCommon($id);
		}

		$this->name = $this->nom; // For compatibility with field name

		if ($result) {
			if ($load_members) {
				$excludefilter = '';
				$this->members = $this->listUsersForGroup($excludefilter, 0);	// This make a request to get list of users but may also do subrequest to fetch each users on some versions
			}

			return 1;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Return array of groups objects for a particular user
	 *
	 *  @param		int			$userid 		User id to search
	 *  @param		boolean		$load_members	Load all members of the group
	 *  @return		array|int     				Array of groups objects
	 */
	public function listGroupsForUser($userid, $load_members = true)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT g.rowid, ug.entity as usergroup_entity";
		$sql .= " FROM ".$this->db->prefix()."usergroup as g,";
		$sql .= " ".$this->db->prefix()."usergroup_user as ug";
		$sql .= " WHERE ug.fk_usergroup = g.rowid";
		$sql .= " AND ug.fk_user = ".((int) $userid);
		if (isModEnabled('multicompany') && $conf->entity == 1 && $user->admin && !$user->entity) {
			$sql .= " AND g.entity IS NOT NULL";
		} else {
			$sql .= " AND g.entity IN (0,".$conf->entity.")";
		}
		$sql .= " ORDER BY g.nom";

		dol_syslog(get_class($this)."::listGroupsForUser", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			while ($obj = $this->db->fetch_object($result)) {
				if (!array_key_exists($obj->rowid, $ret)) {
					$newgroup = new UserGroup($this->db);
					$newgroup->fetch($obj->rowid, '', $load_members);
					$ret[$obj->rowid] = $newgroup;
				}
				if (!is_array($ret[$obj->rowid]->usergroup_entity)) {
					$ret[$obj->rowid]->usergroup_entity = array();
				}
				// $ret[$obj->rowid] is instance of UserGroup
				$ret[$obj->rowid]->usergroup_entity[] = (int) $obj->usergroup_entity;
			}

			$this->db->free($result);

			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 	Return array of User objects for group this->id (or all if this->id not defined)
	 *
	 * 	@param	string	$excludefilter		Filter to exclude. Do not use here a string coming from user input.
	 *  @param	int		$mode				0=Return array of user instance, 1=Return array of users id only
	 * 	@return	mixed						Array of users or -1 on error
	 */
	public function listUsersForGroup($excludefilter = '', $mode = 0)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT u.rowid, u.login, u.lastname, u.firstname, u.photo, u.fk_soc, u.entity, u.employee, u.email, u.statut as status";
		if (!empty($this->id)) {
			$sql .= ", ug.entity as usergroup_entity";
		}
		$sql .= " FROM ".$this->db->prefix()."user as u";
		if (!empty($this->id)) {
			$sql .= ", ".$this->db->prefix()."usergroup_user as ug";
		}
		$sql .= " WHERE 1 = 1";
		if (!empty($this->id)) {
			$sql .= " AND ug.fk_user = u.rowid";
		}
		if (!empty($this->id)) {
			$sql .= " AND ug.fk_usergroup = ".((int) $this->id);
		}
		if (isModEnabled('multicompany') && $conf->entity == 1 && $user->admin && !$user->entity) {
			$sql .= " AND u.entity IS NOT NULL";
		} else {
			$sql .= " AND u.entity IN (0,".$conf->entity.")";
		}
		if (!empty($excludefilter)) {
			$sql .= ' AND ('.$excludefilter.')';
		}

		dol_syslog(get_class($this)."::listUsersForGroup", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				if (!array_key_exists($obj->rowid, $ret)) {
					if ($mode != 1) {
						$newuser = new User($this->db);
						//$newuser->fetch($obj->rowid);		// We are inside a loop, no subrequests inside a loop
						$newuser->id = $obj->rowid;
						$newuser->login = $obj->login;
						$newuser->photo = $obj->photo;
						$newuser->lastname = $obj->lastname;
						$newuser->firstname = $obj->firstname;
						$newuser->email = $obj->email;
						$newuser->socid = $obj->fk_soc;
						$newuser->entity = $obj->entity;
						$newuser->employee = $obj->employee;
						$newuser->status = $obj->status;

						$ret[$obj->rowid] = $newuser;
					} else {
						$ret[$obj->rowid] = $obj->rowid;
					}
				}
				if ($mode != 1 && !empty($obj->usergroup_entity)) {
					// $ret[$obj->rowid] is instance of User
					if (!is_array($ret[$obj->rowid]->usergroup_entity)) {
						$ret[$obj->rowid]->usergroup_entity = array();
					}
					$ret[$obj->rowid]->usergroup_entity[] = (int) $obj->usergroup_entity;
				}
			}

			$this->db->free($resql);

			return $ret;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *    Add a permission to a group
	 *
	 *    @param	int		$rid		id du droit a ajouter
	 *    @param	string	$allmodule	Ajouter tous les droits du module allmodule
	 *    @param	string	$allperms	Ajouter tous les droits du module allmodule, perms allperms
	 *    @param	int		$entity		Entity to use
	 *    @return	int					> 0 if OK, < 0 if KO
	 */
	public function addrights($rid, $allmodule = '', $allperms = '', $entity = 0)
	{
		global $conf, $user, $langs;

		$entity = (!empty($entity) ? $entity : $conf->entity);

		dol_syslog(get_class($this)."::addrights $rid, $allmodule, $allperms, $entity");
		$error = 0;
		$whereforadd = '';

		$this->db->begin();

		if (!empty($rid)) {
			$module = $perms = $subperms = '';

			// Si on a demande ajout d'un droit en particulier, on recupere
			// les caracteristiques (module, perms et subperms) de ce droit.
			$sql = "SELECT module, perms, subperms";
			$sql .= " FROM ".$this->db->prefix()."rights_def";
			$sql .= " WHERE id = ".((int) $rid);
			$sql .= " AND entity = ".((int) $entity);

			$result = $this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);
				if ($obj) {
					$module = $obj->module;
					$perms = $obj->perms;
					$subperms = $obj->subperms;
				}
			} else {
				$error++;
				dol_print_error($this->db);
			}

			// Where pour la liste des droits a ajouter
			$whereforadd = "id=".((int) $rid);
			// Find also rights that are herited to add them too
			if ($subperms) {
				$whereforadd .= " OR (module='".$this->db->escape($module)."' AND perms='".$this->db->escape($perms)."' AND (subperms='lire' OR subperms='read'))";
			} elseif ($perms) {
				$whereforadd .= " OR (module='".$this->db->escape($module)."' AND (perms='lire' OR perms='read') AND subperms IS NULL)";
			}
		} else {
			// Where pour la liste des droits a ajouter
			if (!empty($allmodule)) {
				if ($allmodule == 'allmodules') {
					$whereforadd = 'allmodules';
				} else {
					$whereforadd = "module='".$this->db->escape($allmodule)."'";
					if (!empty($allperms)) {
						$whereforadd .= " AND perms='".$this->db->escape($allperms)."'";
					}
				}
			}
		}

		// Add permission of the list $whereforadd
		if (!empty($whereforadd)) {
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql .= " FROM ".$this->db->prefix()."rights_def";
			$sql .= " WHERE entity = ".((int) $entity);
			if (!empty($whereforadd) && $whereforadd != 'allmodules') {
				$sql .= " AND ".$whereforadd;
			}

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num) {
					$obj = $this->db->fetch_object($result);
					$nid = $obj->id;

					$sql = "DELETE FROM ".$this->db->prefix()."usergroup_rights WHERE fk_usergroup = ".((int) $this->id)." AND fk_id=".((int) $nid)." AND entity = ".((int) $entity);
					if (!$this->db->query($sql)) {
						$error++;
					}
					$sql = "INSERT INTO ".$this->db->prefix()."usergroup_rights (entity, fk_usergroup, fk_id) VALUES (".((int) $entity).", ".((int) $this->id).", ".((int) $nid).")";
					if (!$this->db->query($sql)) {
						$error++;
					}

					$i++;
				}
			} else {
				$error++;
				dol_print_error($this->db);
			}

			if (!$error) {
				$langs->load("other");
				$this->context = array('audit' => $langs->trans("PermissionsAdd").($rid ? ' (id='.$rid.')' : ''));

				// Call trigger
				$result = $this->call_trigger('USERGROUP_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if ($error) {
			$this->db->rollback();
			return -$error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *    Remove a permission from group
	 *
	 *    @param	int		$rid		id du droit a retirer
	 *    @param	string	$allmodule	Retirer tous les droits du module allmodule
	 *    @param	string	$allperms	Retirer tous les droits du module allmodule, perms allperms
	 *    @param	int		$entity		Entity to use
	 *    @return	int					> 0 if OK, < 0 if OK
	 */
	public function delrights($rid, $allmodule = '', $allperms = '', $entity = 0)
	{
		global $conf, $user, $langs;

		$error = 0;
		$wherefordel = '';

		$entity = (!empty($entity) ? $entity : $conf->entity);

		$this->db->begin();

		if (!empty($rid)) {
			$module = $perms = $subperms = '';

			// Si on a demande suppression d'un droit en particulier, on recupere
			// les caracteristiques module, perms et subperms de ce droit.
			$sql = "SELECT module, perms, subperms";
			$sql .= " FROM ".$this->db->prefix()."rights_def";
			$sql .= " WHERE id = ".((int) $rid);
			$sql .= " AND entity = ".((int) $entity);

			$result = $this->db->query($sql);
			if ($result) {
				$obj = $this->db->fetch_object($result);
				if ($obj) {
					$module = $obj->module;
					$perms = $obj->perms;
					$subperms = $obj->subperms;
				}
			} else {
				$error++;
				dol_print_error($this->db);
			}

			// Where for the list of permissions to delete
			$wherefordel = "id = ".((int) $rid);
			// Suppression des droits induits
			if ($subperms == 'lire' || $subperms == 'read') {
				$wherefordel .= " OR (module='".$this->db->escape($module)."' AND perms='".$this->db->escape($perms)."' AND subperms IS NOT NULL)";
			}
			if ($perms == 'lire' || $perms == 'read') {
				$wherefordel .= " OR (module='".$this->db->escape($module)."')";
			}

			// Pour compatibilite, si lowid = 0, on est en mode suppression de tout
			// TODO To remove when this will be implemented by the caller
			//if (substr($rid,-1,1) == 0) $wherefordel="module='$module'";
		} else {
			// Add permission of the list $wherefordel
			if (!empty($allmodule)) {
				if ($allmodule == 'allmodules') {
					$wherefordel = 'allmodules';
				} else {
					$wherefordel = "module='".$this->db->escape($allmodule)."'";
					if (!empty($allperms)) {
						$wherefordel .= " AND perms='".$this->db->escape($allperms)."'";
					}
				}
			}
		}

		// Suppression des droits de la liste wherefordel
		if (!empty($wherefordel)) {
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql .= " FROM ".$this->db->prefix()."rights_def";
			$sql .= " WHERE entity = ".((int) $entity);
			if (!empty($wherefordel) && $wherefordel != 'allmodules') {
				$sql .= " AND ".$wherefordel;
			}

			$result = $this->db->query($sql);
			if ($result) {
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num) {
					$nid = 0;

					$obj = $this->db->fetch_object($result);
					if ($obj) {
						$nid = $obj->id;
					}

					$sql = "DELETE FROM ".$this->db->prefix()."usergroup_rights";
					$sql .= " WHERE fk_usergroup = $this->id AND fk_id=".((int) $nid);
					$sql .= " AND entity = ".((int) $entity);
					if (!$this->db->query($sql)) {
						$error++;
					}

					$i++;
				}
			} else {
				$error++;
				dol_print_error($this->db);
			}

			if (!$error) {
				$langs->load("other");
				$this->context = array('audit' => $langs->trans("PermissionsDelete").($rid ? ' (id='.$rid.')' : ''));

				// Call trigger
				$result = $this->call_trigger('USERGROUP_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if ($error) {
			$this->db->rollback();
			return -$error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *  Load the list of permissions for the user into the group object
	 *
	 *  @param      string	$moduletag	 	Name of module we want permissions ('' means all)
	 *  @return     int						Return integer <0 if KO, >=0 if OK
	 */
	public function getrights($moduletag = '')
	{
		global $conf;

		if ($moduletag && isset($this->_tab_loaded[$moduletag]) && $this->_tab_loaded[$moduletag]) {
			// Rights for this module are already loaded, so we leave
			return 0;
		}

		if (!empty($this->all_permissions_are_loaded)) {
			// We already loaded all rights for this group, so we leave
			return 0;
		}

		/*
		 * Recuperation des droits
		 */
		$sql = "SELECT r.module, r.perms, r.subperms ";
		$sql .= " FROM ".$this->db->prefix()."usergroup_rights as u, ".$this->db->prefix()."rights_def as r";
		$sql .= " WHERE r.id = u.fk_id";
		$sql .= " AND r.entity = ".((int) $conf->entity);
		$sql .= " AND u.entity = ".((int) $conf->entity);
		$sql .= " AND u.fk_usergroup = ".((int) $this->id);
		$sql .= " AND r.perms IS NOT NULL";
		if ($moduletag) {
			$sql .= " AND r.module = '".$this->db->escape($moduletag)."'";
		}

		dol_syslog(get_class($this).'::getrights', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				if ($obj) {
					$module = $obj->module;
					$perms = $obj->perms;
					$subperms = $obj->subperms;

					if ($perms) {
						if (!isset($this->rights)) {
							$this->rights = new stdClass(); // For avoid error
						}
						if (!isset($this->rights->$module) || !is_object($this->rights->$module)) {
							$this->rights->$module = new stdClass();
						}
						if ($subperms) {
							if (!isset($this->rights->$module->$perms) || !is_object($this->rights->$module->$perms)) {
								$this->rights->$module->$perms = new stdClass();
							}
							if (empty($this->rights->$module->$perms->$subperms)) {
								$this->nb_rights++;
							}
							$this->rights->$module->$perms->$subperms = 1;
						} else {
							if (empty($this->rights->$module->$perms)) {
								$this->nb_rights++;
							}
							$this->rights->$module->$perms = 1;
						}
					}
				}

				$i++;
			}
			$this->db->free($resql);
		}

		if ($moduletag == '') {
			// Si module etait non defini, alors on a tout charge, on peut donc considerer
			// que les droits sont en cache (car tous charges) pour cet instance de group
			$this->all_permissions_are_loaded = 1;
		} else {
			// If module defined, we flag it as loaded into cache
			$this->_tab_loaded[$moduletag] = 1;
		}

		return 1;
	}

	/**
	 *	Delete a group
	 *
	 *	@param	User	$user		User that delete
	 *	@return int    				Return integer <0 if KO, > 0 if OK
	 */
	public function delete(User $user)
	{
		return $this->deleteCommon($user);
	}

	/**
	 *	Create group into database
	 *
	 *	@param		int		$notrigger	0=triggers enabled, 1=triggers disabled
	 *	@return     int					Return integer <0 if KO, >=0 if OK
	 */
	public function create($notrigger = 0)
	{
		global $user, $conf;

		$this->datec = dol_now();
		if (!empty($this->name)) {
			$this->nom = $this->name; // Field for 'name' is called 'nom' in database
		}

		if (!isset($this->entity)) {
			$this->entity = $conf->entity; // If not defined, we use default value
		}

		return $this->createCommon($user, $notrigger);
	}

	/**
	 *		Update group into database
	 *
	 *      @param      int		$notrigger	    0=triggers enabled, 1=triggers disabled
	 *    	@return     int						Return integer <0 if KO, >=0 if OK
	 */
	public function update($notrigger = 0)
	{
		global $user, $conf;

		if (!empty($this->name)) {
			$this->nom = $this->name; // Field for 'name' is called 'nom' in database
		}

		return $this->updateCommon($user, $notrigger);
	}


	/**
	 *	Return full name (civility+' '+name+' '+lastname)
	 *
	 *	@param	Translate	$langs			Language object for translation of civility (used only if option is 1)
	 *	@param	int			$option			0=No option, 1=Add civility
	 * 	@param	int			$nameorder		-1=Auto, 0=Lastname+Firstname, 1=Firstname+Lastname, 2=Firstname, 3=Firstname if defined else lastname, 4=Lastname, 5=Lastname if defined else firstname
	 * 	@param	int			$maxlen			Maximum length
	 * 	@return	string						String with full name
	 */
	public function getFullName($langs, $option = 0, $nameorder = -1, $maxlen = 0)
	{
		//print "lastname=".$this->lastname." name=".$this->name." nom=".$this->nom."<br>\n";
		$lastname = $this->lastname;
		$firstname = $this->firstname;
		if (empty($lastname)) {
			$lastname = (isset($this->lastname) ? $this->lastname : (isset($this->name) ? $this->name : (isset($this->nom) ? $this->nom : (isset($this->societe) ? $this->societe : (isset($this->company) ? $this->company : '')))));
		}

		$ret = '';
		if (!empty($option) && !empty($this->civility_code)) {
			if ($langs->transnoentitiesnoconv("Civility".$this->civility_code) != "Civility".$this->civility_code) {
				$ret .= $langs->transnoentitiesnoconv("Civility".$this->civility_code).' ';
			} else {
				$ret .= $this->civility_code.' ';
			}
		}

		$ret .= dolGetFirstLastname($firstname, $lastname, $nameorder);

		return dol_trunc($ret, $maxlen);
	}

	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut(0, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a given status
	 *
	 *  @param	int		$status        Id status
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string 			       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;
		$langs->load('users');
		return '';
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param array $params ex option, infologin
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $menumanager;

		$option = $params['option'] ?? '';

		$datas = [];
		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
			$langs->load("users");
			return ['optimize' => $langs->trans("ShowGroup")];
		}
		$datas['divopen'] = '<div class="centpercent">';
		$datas['picto'] = img_picto('', 'group').' <u>'.$langs->trans("Group").'</u><br>';
		$datas['name'] = '<b>'.$langs->trans('Name').':</b> '.$this->name;
		$datas['description'] = '<br><b>'.$langs->trans("Description").':</b> '.$this->note;
		$datas['divclose'] = '</div>';

		return $datas;
	}

	/**
	 *  Return a link to the user card (with optionally the picto)
	 *  Use this->id,this->lastname, this->firstname
	 *
	 *  @param  int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
	 *	@param  string	$option						On what the link point to ('nolink', 'permissions')
	 *  @param	integer	$notooltip					1=Disable tooltip on picto and name
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $langs, $conf, $db, $hookmanager;

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') && $withpicto) {
			$withpicto = 0;
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
		];
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$label = '';
		} else {
			$label = implode($this->getTooltipContentArray($params));
		}

		if ($option == 'permissions') {
			$url = DOL_URL_ROOT.'/user/group/perms.php?id='.$this->id;
		} else {
			$url = DOL_URL_ROOT.'/user/group/card.php?id='.$this->id;
		}

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = "";
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$langs->load("users");
				$label = $langs->trans("ShowGroup");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result = $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->name;
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('groupdao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param		array	$info		Info array loaded by _load_ldap_info
	 *	@param		int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *									1=Return DN without key inside (ou=xxx,dc=aaa,dc=bbb)
	 *									2=Return key only (uid=qqq)
	 *	@return		string				DN
	 */
	public function _load_ldap_dn($info, $mode = 0)
	{
		// phpcs:enable
		global $conf;
		$dn = '';
		if ($mode == 0) {
			$dn = getDolGlobalString('LDAP_KEY_GROUPS') . "=".$info[getDolGlobalString('LDAP_KEY_GROUPS')]."," . getDolGlobalString('LDAP_GROUP_DN');
		}
		if ($mode == 1) {
			$dn = getDolGlobalString('LDAP_GROUP_DN');
		}
		if ($mode == 2) {
			$dn = getDolGlobalString('LDAP_KEY_GROUPS') . "=".$info[getDolGlobalString('LDAP_KEY_GROUPS')];
		}
		return $dn;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Initialize the info array (array of LDAP values) that will be used to call LDAP functions
	 *
	 *	@return		array		Tableau info des attributes
	 */
	public function _load_ldap_info()
	{
		// phpcs:enable
		global $conf;

		$info = array();

		// Object classes
		$info["objectclass"] = explode(',', getDolGlobalString('LDAP_GROUP_OBJECT_CLASS'));

		// Champs
		if ($this->name && getDolGlobalString('LDAP_GROUP_FIELD_FULLNAME')) {
			$info[getDolGlobalString('LDAP_GROUP_FIELD_FULLNAME')] = $this->name;
		}
		//if ($this->name && !empty($conf->global->LDAP_GROUP_FIELD_NAME)) $info[$conf->global->LDAP_GROUP_FIELD_NAME] = $this->name;
		if ($this->note && getDolGlobalString('LDAP_GROUP_FIELD_DESCRIPTION')) {
			$info[getDolGlobalString('LDAP_GROUP_FIELD_DESCRIPTION')] = dol_string_nohtmltag($this->note, 2);
		}
		if (getDolGlobalString('LDAP_GROUP_FIELD_GROUPMEMBERS')) {
			$valueofldapfield = array();
			foreach ($this->members as $key => $val) {    // This is array of users for group into dolibarr database.
				$muser = new User($this->db);
				$muser->fetch($val->id);
				$info2 = $muser->_load_ldap_info();
				$valueofldapfield[] = $muser->_load_ldap_dn($info2);
			}
			$info[getDolGlobalString('LDAP_GROUP_FIELD_GROUPMEMBERS')] = (!empty($valueofldapfield) ? $valueofldapfield : '');
		}
		if (getDolGlobalString('LDAP_GROUP_FIELD_GROUPID')) {
			$info[getDolGlobalString('LDAP_GROUP_FIELD_GROUPID')] = $this->id;
		}
		return $info;
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return int
	 */
	public function initAsSpecimen()
	{
		global $conf, $user, $langs;

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;

		$this->name = 'DOLIBARR GROUP SPECIMEN';
		$this->note = 'This is a note';
		$this->datec = time();
		$this->tms = time();

		// Members of this group is just me
		$this->members = array(
			$user->id => $user
		);

		return 1;
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 * 	@param	    string		$modele			Force model to use ('' to not force)
	 * 	@param		Translate	$outputlangs	Object langs to use for output
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 * 	@return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $user, $langs;

		$langs->load("user");

		// Positionne le modele sur le nom du modele a utiliser
		if (!dol_strlen($modele)) {
			if (getDolGlobalString('USERGROUP_ADDON_PDF')) {
				$modele = getDolGlobalString('USERGROUP_ADDON_PDF');
			} else {
				$modele = 'grass';
			}
		}

		$modelpath = "core/modules/usergroup/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    			$option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array{string,mixed}		$arraydata				Array of data
	 *  @return		string											HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'members')) {
			$return .= '<br><span class="info-box-status opacitymedium">'.(empty($this->nb_users) ? 0 : $this->nb_users).' '.$langs->trans('Users').'</span>';
		}
		if (property_exists($this, 'nb_rights')) {
			$return .= '<br><div class="info-box-status margintoponly opacitymedium">'.$langs->trans('NbOfPermissions').' : '.(empty($this->nb_rights) ? 0 : $this->nb_rights).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
