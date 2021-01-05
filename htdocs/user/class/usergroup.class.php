<?php
/* Copyright (c) 2005       Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2005-2018	Laurent Destailleur	 <eldy@users.sourceforge.net>
 * Copyright (c) 2005-2018	Regis Houssin		 <regis.houssin@inodbox.com>
 * Copyright (C) 2012		Florian Henry		 <florian.henry@open-concept.pro>
 * Copyright (C) 2014		Juanjo Menent		 <jmenent@2byte.es>
 * Copyright (C) 2014		Alexis Algoud		 <alexis@atm-consulting.fr>
 * Copyright (C) 2018       Nicolas ZABOURI		 <info@inovea-conseil.com>
 * Copyright (C) 2019       Abbes Bahfir            <dolipar@dolipar.org>
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
if (!empty($conf->ldap->enabled)) require_once DOL_DOCUMENT_ROOT."/core/class/ldap.class.php";


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
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

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
	 * Date creation record (datec)
	 *
	 * @var integer
	 */
	public $datec;

	/**
	 * Date modification record (tms)
	 *
	 * @var integer
	 */
	public $datem;

	/**
	 * @var string Description
	 */
	public $note;

	public $members = array(); // Array of users

	public $nb_rights; // Number of rights granted to the user

	private $_tab_loaded = array(); // Array of cache of already loaded permissions

	public $oldcopy; // To contains a clone of this when we need to save old properties of object

	public $fields = array(
		'rowid'=>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'index'=>1, 'position'=>1, 'comment'=>'Id'),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'enabled'=>1, 'visible'=>0, 'notnull'=> 1, 'default'=>1, 'index'=>1, 'position'=>5),
		'nom'=>array('type'=>'varchar(180)', 'label'=>'Name', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'comment'=>'Group name'),
		'note' => array('type'=>'html', 'label'=>'Description', 'enabled'=>1, 'visible'=>1, 'position'=>20, 'notnull'=>-1,),
		'datec' => array('type'=>'datetime', 'label'=>'DateCreation', 'enabled'=>1, 'visible'=>-2, 'position'=>50, 'notnull'=>1,),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'enabled'=>1, 'visible'=>-2, 'position'=>60, 'notnull'=>1,),
		'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'ModelPDF', 'enabled'=>1, 'visible'=>0, 'position'=>100),
	);

	/**
	 * @var string    Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_usergroup';

	/**
	 * @var array	List of child tables. To test if we can delete object.
	 */
	protected $childtables = array();

	/**
	 * @var array	List of child tables. To know object to delete on cascade.
	 */
	protected $childtablesoncascade = array('usergroup_rights', 'usergroup_user');


	/**
	 *    Constructor de la classe
	 *
	 *    @param   DoliDb  $db     Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
		$this->nb_rights = 0;
	}


	/**
	 *  Charge un objet group avec toutes ses caracteristiques (except ->members array)
	 *
	 *	@param      int		$id				Id of group to load
	 *	@param      string	$groupname		Name of group to load
	 *  @param		boolean	$load_members	Load all members of the group
	 *	@return		int						<0 if KO, >0 if OK
	 */
	public function fetch($id = '', $groupname = '', $load_members = true)
	{
		global $conf;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		if (!empty($groupname))
		{
			$result = $this->fetchCommon(0, '', ' AND nom = \''.$this->db->escape($groupname).'\'');
		} else {
			$result = $this->fetchCommon($id);
		}

		$this->name = $this->nom; // For compatibility with field name

		if ($result)
		{
			if ($load_members)
			{
				$this->members = $this->listUsersForGroup();
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
	 *  @param		int		$userid 		User id to search
	 *  @param		boolean	$load_members	Load all members of the group
	 *  @return		array     				Array of groups objects
	 */
	public function listGroupsForUser($userid, $load_members = true)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT g.rowid, ug.entity as usergroup_entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."usergroup as g,";
		$sql .= " ".MAIN_DB_PREFIX."usergroup_user as ug";
		$sql .= " WHERE ug.fk_usergroup = g.rowid";
		$sql .= " AND ug.fk_user = ".$userid;
		if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity)
		{
			$sql .= " AND g.entity IS NOT NULL";
		} else {
			$sql .= " AND g.entity IN (0,".$conf->entity.")";
		}
		$sql .= " ORDER BY g.nom";

		dol_syslog(get_class($this)."::listGroupsForUser", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			while ($obj = $this->db->fetch_object($result))
			{
				if (!array_key_exists($obj->rowid, $ret))
				{
					$newgroup = new UserGroup($this->db);
					$newgroup->fetch($obj->rowid, '', $load_members);
					$ret[$obj->rowid] = $newgroup;
				}

				$ret[$obj->rowid]->usergroup_entity[] = $obj->usergroup_entity;
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
	 * 	@param	string	$excludefilter		Filter to exclude
	 *  @param	int		$mode				0=Return array of user instance, 1=Return array of users id only
	 * 	@return	mixed						Array of users or -1 on error
	 */
	public function listUsersForGroup($excludefilter = '', $mode = 0)
	{
		global $conf, $user;

		$ret = array();

		$sql = "SELECT u.rowid";
		if (!empty($this->id)) $sql .= ", ug.entity as usergroup_entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		if (!empty($this->id)) $sql .= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
		$sql .= " WHERE 1 = 1";
		if (!empty($this->id)) $sql .= " AND ug.fk_user = u.rowid";
		if (!empty($this->id)) $sql .= " AND ug.fk_usergroup = ".$this->id;
		if (!empty($conf->multicompany->enabled) && $conf->entity == 1 && $user->admin && !$user->entity)
		{
			$sql .= " AND u.entity IS NOT NULL";
		} else {
			$sql .= " AND u.entity IN (0,".$conf->entity.")";
		}
		if (!empty($excludefilter)) $sql .= ' AND ('.$excludefilter.')';

		dol_syslog(get_class($this)."::listUsersForGroup", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj = $this->db->fetch_object($resql))
			{
				if (!array_key_exists($obj->rowid, $ret))
				{
					if ($mode != 1)
					{
						$newuser = new User($this->db);
						$newuser->fetch($obj->rowid);
						$ret[$obj->rowid] = $newuser;
					} else $ret[$obj->rowid] = $obj->rowid;
				}
				if ($mode != 1 && !empty($obj->usergroup_entity))
				{
					$ret[$obj->rowid]->usergroup_entity[] = $obj->usergroup_entity;
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

		if (!empty($rid))
		{
			$module = $perms = $subperms = '';

			// Si on a demande ajout d'un droit en particulier, on recupere
			// les caracteristiques (module, perms et subperms) de ce droit.
			$sql = "SELECT module, perms, subperms";
			$sql .= " FROM ".MAIN_DB_PREFIX."rights_def";
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
			if ($subperms)   $whereforadd .= " OR (module='".$this->db->escape($module)."' AND perms='".$this->db->escape($perms)."' AND (subperms='lire' OR subperms='read'))";
			elseif ($perms) $whereforadd .= " OR (module='".$this->db->escape($module)."' AND (perms='lire' OR perms='read') AND subperms IS NULL)";
		} else {
			// Where pour la liste des droits a ajouter
			if (!empty($allmodule))
			{
				if ($allmodule == 'allmodules')
				{
					$whereforadd = 'allmodules';
				} else {
					$whereforadd = "module='".$this->db->escape($allmodule)."'";
					if (!empty($allperms))  $whereforadd .= " AND perms='".$this->db->escape($allperms)."'";
				}
			}
		}

		// Add permission of the list $whereforadd
		if (!empty($whereforadd))
		{
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql .= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql .= " WHERE entity = ".$entity;
			if (!empty($whereforadd) && $whereforadd != 'allmodules') {
				$sql .= " AND ".$whereforadd;
			}

			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$nid = $obj->id;

					$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights WHERE fk_usergroup = $this->id AND fk_id=".$nid." AND entity = ".$entity;
					if (!$this->db->query($sql)) $error++;
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."usergroup_rights (entity, fk_usergroup, fk_id) VALUES (".$entity.", ".$this->id.", ".$nid.")";
					if (!$this->db->query($sql)) $error++;

					$i++;
				}
			} else {
				$error++;
				dol_print_error($this->db);
			}

			if (!$error)
			{
				$langs->load("other");
				$this->context = array('audit'=>$langs->trans("PermissionsAdd").($rid ? ' (id='.$rid.')' : ''));

				// Call trigger
				$result = $this->call_trigger('USERGROUP_MODIFY', $user);
				if ($result < 0) { $error++; }
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

		if (!empty($rid))
		{
			$module = $perms = $subperms = '';

			// Si on a demande supression d'un droit en particulier, on recupere
			// les caracteristiques module, perms et subperms de ce droit.
			$sql = "SELECT module, perms, subperms";
			$sql .= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql .= " WHERE id = '".$this->db->escape($rid)."'";
			$sql .= " AND entity = ".$entity;

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

			// Where pour la liste des droits a supprimer
			$wherefordel = "id=".$this->db->escape($rid);
			// Suppression des droits induits
			if ($subperms == 'lire' || $subperms == 'read') $wherefordel .= " OR (module='".$this->db->escape($module)."' AND perms='".$this->db->escape($perms)."' AND subperms IS NOT NULL)";
			if ($perms == 'lire' || $perms == 'read') $wherefordel .= " OR (module='".$this->db->escape($module)."')";

			// Pour compatibilite, si lowid = 0, on est en mode suppression de tout
			// TODO A virer quand sera gere par l'appelant
			//if (substr($rid,-1,1) == 0) $wherefordel="module='$module'";
		} else {
			// Add permission of the list $wherefordel
			if (!empty($allmodule))
			{
				if ($allmodule == 'allmodules')
				{
					$wherefordel = 'allmodules';
				} else {
					$wherefordel = "module='".$this->db->escape($allmodule)."'";
					if (!empty($allperms)) $wherefordel .= " AND perms='".$this->db->escape($allperms)."'";
				}
			}
		}

		// Suppression des droits de la liste wherefordel
		if (!empty($wherefordel))
		{
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql .= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql .= " WHERE entity = ".$entity;
			if (!empty($wherefordel) && $wherefordel != 'allmodules') {
				$sql .= " AND ".$wherefordel;
			}

			$result = $this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$nid = 0;

					$obj = $this->db->fetch_object($result);
					if ($obj) {
						$nid = $obj->id;
					}

					$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_rights";
					$sql .= " WHERE fk_usergroup = $this->id AND fk_id=".$nid;
					$sql .= " AND entity = ".$entity;
					if (!$this->db->query($sql)) $error++;

					$i++;
				}
			} else {
				$error++;
				dol_print_error($this->db);
			}

			if (!$error)
			{
				$langs->load("other");
				$this->context = array('audit'=>$langs->trans("PermissionsDelete").($rid ? ' (id='.$rid.')' : ''));

				// Call trigger
				$result = $this->call_trigger('USERGROUP_MODIFY', $user);
				if ($result < 0) { $error++; }
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
	 *  Charge dans l'objet group, la liste des permissions auquels le groupe a droit
	 *
	 *  @param      string	$moduletag	 	Name of module we want permissions ('' means all)
	 *	@return     int						<0 if KO, >0 if OK
	 */
	public function getrights($moduletag = '')
	{
		global $conf;

		if ($moduletag && isset($this->_tab_loaded[$moduletag]) && $this->_tab_loaded[$moduletag])
		{
			// Rights for this module are already loaded, so we leave
			return;
		}

		if (!empty($this->all_permissions_are_loaded))
		{
			// We already loaded all rights for this group, so we leave
			return;
		}

		/*
		 * Recuperation des droits
		 */
		$sql = "SELECT r.module, r.perms, r.subperms ";
		$sql .= " FROM ".MAIN_DB_PREFIX."usergroup_rights as u, ".MAIN_DB_PREFIX."rights_def as r";
		$sql .= " WHERE r.id = u.fk_id";
		$sql .= " AND r.entity = ".$conf->entity;
		$sql .= " AND u.entity = ".$conf->entity;
		$sql .= " AND u.fk_usergroup = ".$this->id;
		$sql .= " AND r.perms IS NOT NULL";
		if ($moduletag) $sql .= " AND r.module = '".$this->db->escape($moduletag)."'";

		dol_syslog(get_class($this).'::getrights', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);

				if ($obj) {
					$module = $obj->module;
					$perms = $obj->perms;
					$subperms = $obj->subperms;

					if ($perms)
					{
						if (!isset($this->rights)) $this->rights = new stdClass(); // For avoid error
						if (!isset($this->rights->$module) || !is_object($this->rights->$module)) $this->rights->$module = new stdClass();
						if ($subperms)
						{
							if (!isset($this->rights->$module->$perms) || !is_object($this->rights->$module->$perms)) $this->rights->$module->$perms = new stdClass();
							if (empty($this->rights->$module->$perms->$subperms)) $this->nb_rights++;
							$this->rights->$module->$perms->$subperms = 1;
						} else {
							if (empty($this->rights->$module->$perms)) $this->nb_rights++;
							$this->rights->$module->$perms = 1;
						}
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
	 *	@return int    				<0 if KO, > 0 if OK
	 */
	public function delete(User $user)
	{
		return $this->deleteCommon($user);
	}

	/**
	 *	Create group into database
	 *
	 *	@param		int		$notrigger	0=triggers enabled, 1=triggers disabled
	 *	@return     int					<0 if KO, >=0 if OK
	 */
	public function create($notrigger = 0)
	{
		global $user, $conf;

		$this->datec = dol_now();
		if (!empty($this->name)) {
			$this->nom = $this->name; // Field for 'name' is called 'nom' in database
		}

		if (!isset($this->entity)) $this->entity = $conf->entity; // If not defined, we use default value

		return $this->createCommon($user, $notrigger);
	}

	/**
	 *		Update group into database
	 *
	 *      @param      int		$notrigger	    0=triggers enabled, 1=triggers disabled
	 *    	@return     int						<0 if KO, >=0 if OK
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
	 *  Return label of status of user (active, inactive)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut(0, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$status        	Id status
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string 			       	Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;
		$langs->load('users');
		return '';
	}

	/**
	 *  Return a link to the user card (with optionaly the picto)
	 *  Use this->id,this->lastname, this->firstname
	 *
	 *  @param  int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
	 *	@param  string	$option						On what the link point to ('nolink', )
	 *  @param	integer	$notooltip					1=Disable tooltip on picto and name
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $langs, $conf, $db, $hookmanager;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpicto) $withpicto = 0;

		$result = ''; $label = '';

		$label .= '<div class="centpercent">';
		$label .= '<u>'.$langs->trans("Group").'</u><br>';
		$label .= '<b>'.$langs->trans('Name').':</b> '.$this->name;
		$label .= '<br><b>'.$langs->trans("Description").':</b> '.$this->note;
		$label .= '</div>';

		$url = DOL_URL_ROOT.'/user/group/card.php?id='.$this->id;

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = "";
		if (empty($notooltip))
		{
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$langs->load("users");
				$label = $langs->trans("ShowGroup");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';

			/*
			 $hookmanager->initHooks(array('groupdao'));
			 $parameters=array('id'=>$this->id);
			 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
			 */
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result = $linkstart;
		if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		if ($withpicto != 2) $result .= $this->name;
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('groupdao'));
		$parameters = array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

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
		if ($mode == 0) $dn = $conf->global->LDAP_KEY_GROUPS."=".$info[$conf->global->LDAP_KEY_GROUPS].",".$conf->global->LDAP_GROUP_DN;
		if ($mode == 1) $dn = $conf->global->LDAP_GROUP_DN;
		if ($mode == 2) $dn = $conf->global->LDAP_KEY_GROUPS."=".$info[$conf->global->LDAP_KEY_GROUPS];
		return $dn;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Initialize the info array (array of LDAP values) that will be used to call LDAP functions
	 *
	 *	@return		array		Tableau info des attributs
	 */
	public function _load_ldap_info()
	{
		// phpcs:enable
		global $conf;

		$info = array();

		// Object classes
		$info["objectclass"] = explode(',', $conf->global->LDAP_GROUP_OBJECT_CLASS);

		// Champs
		if ($this->name && !empty($conf->global->LDAP_GROUP_FIELD_FULLNAME)) $info[$conf->global->LDAP_GROUP_FIELD_FULLNAME] = $this->name;
		//if ($this->name && ! empty($conf->global->LDAP_GROUP_FIELD_NAME)) $info[$conf->global->LDAP_GROUP_FIELD_NAME] = $this->name;
		if ($this->note && !empty($conf->global->LDAP_GROUP_FIELD_DESCRIPTION)) $info[$conf->global->LDAP_GROUP_FIELD_DESCRIPTION] = dol_string_nohtmltag($this->note, 2);
		if (!empty($conf->global->LDAP_GROUP_FIELD_GROUPMEMBERS))
		{
			$valueofldapfield = array();
			foreach ($this->members as $key=>$val)    // This is array of users for group into dolibarr database.
			{
				$muser = new User($this->db);
				$muser->fetch($val->id);
				$info2 = $muser->_load_ldap_info();
				$valueofldapfield[] = $muser->_load_ldap_dn($info2);
			}
			$info[$conf->global->LDAP_GROUP_FIELD_GROUPMEMBERS] = (!empty($valueofldapfield) ? $valueofldapfield : '');
		}
		if (!empty($info[$conf->global->LDAP_GROUP_FIELD_GROUPID])) {
			$info[$conf->global->LDAP_GROUP_FIELD_GROUPID] = $this->id;
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
	public function initAsSpecimen()
	{
		global $conf, $user, $langs;

		// Initialise parametres
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;

		$this->name = 'DOLIBARR GROUP SPECIMEN';
		$this->note = 'This is a note';
		$this->datec = time();
		$this->datem = time();

		// Members of this group is just me
		$this->members = array(
			$user->id => $user
		);
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
		if (!dol_strlen($modele))
		{
			if (!empty($conf->global->USERGROUP_ADDON_PDF))
			{
				$modele = $conf->global->USERGROUP_ADDON_PDF;
			} else {
				$modele = 'grass';
			}
		}

		$modelpath = "core/modules/usergroup/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}
}
