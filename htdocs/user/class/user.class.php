<?php
/* Copyright (c) 2002-2007 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (c) 2002-2003 Jean-Louis Bergamo   <jlb@j1b.org>
 * Copyright (c) 2004-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Sebastien Di Cintio  <sdicintio@ressource-toi.org>
 * Copyright (C) 2004      Benoit Mortier       <benoit.mortier@opensides.be>
 * Copyright (C) 2005-2017 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2005      Lionel Cousteix      <etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011      Herve Prot           <herve.prot@symeos.com>
 * Copyright (C) 2013-2014 Philippe Grand       <philippe.grand@atoo-net.com>
 * Copyright (C) 2013-2015 Alexandre Spangaro   <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018      Nicolas ZABOURI	<info@inovea-conseil.com>
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
 *  \file       htdocs/user/class/user.class.php
 *	\brief      File of class to manage users
 *  \ingroup	core
 */

require_once DOL_DOCUMENT_ROOT .'/core/class/commonobject.class.php';

/**
 *	Class to manage Dolibarr users
 */
class User extends CommonObject
{
	public $element='user';
	public $table_element='user';
	public $fk_element='fk_user';
	public $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

	public $id=0;
	public $statut;
	public $ldap_sid;
	public $search_sid;
	public $employee;
	public $gender;
	public $birth;
	public $email;
	public $skype;
	public $job;
	public $signature;
	public $address;
	public $zip;
	public $town;
	public $state_id;		// The state/department
	public $state_code;
	public $state;
	public $office_phone;
	public $office_fax;
	public $user_mobile;
	public $admin;
	public $login;
	public $api_key;
	public $entity;

	//! Clear password in memory
	public $pass;
	//! Clear password in database (defined if DATABASE_PWD_ENCRYPTED=0)
	public $pass_indatabase;
	//! Encrypted password in database (always defined)
	public $pass_indatabase_crypted;

	public $datec;
	public $datem;

	//! If this is defined, it is an external user
	/**
	 * @deprecated
	 * @see socid
	 */
	public $societe_id;
	/**
	 * @deprecated
	 * @see contactid
	 */
	public $contact_id;
	public $socid;
	public $contactid;

	public $fk_member;
	public $fk_user;

	public $clicktodial_url;
	public $clicktodial_login;
	public $clicktodial_password;
	public $clicktodial_poste;

	public $datelastlogin;
	public $datepreviouslogin;
	public $photo;
	public $lang;

	public $rights;                        // Array of permissions user->rights->permx
	public $all_permissions_are_loaded;	   // All permission are loaded
	public $nb_rights;			           // Number of rights granted to the user
	private $_tab_loaded=array();		   // Cache array of already loaded permissions

	public $conf;           		// To store personal config
	public $default_values;         // To store default values for user
	public $lastsearch_values_tmp;  // To store current search criterias for user
	public $lastsearch_values;      // To store last saved search criterias for user

	public $users = array();		// To store all tree of users hierarchy
	public $parentof;				// To store an array of all parents for all ids.
	private $cache_childids;

	public $accountancy_code;			// Accountancy code in prevision of the complete accountancy module

	public $thm;					// Average cost of employee - Used for valuation of time spent
	public $tjm;					// Average cost of employee

	public $salary;					// Monthly salary       - Denormalized value from llx_user_employment
	public $salaryextra;				// Monthly salary extra - Denormalized value from llx_user_employment
	public $weeklyhours;				// Weekly hours         - Denormalized value from llx_user_employment

	public $color;						// Define background color for user in agenda

	public $dateemployment;			// Define date of employment by company

	public $default_c_exp_tax_cat;
	public $default_range;

	public $fields=array(
        	'rowid'=>array('type'=>'integer', 'label'=>'TechnicalID', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1,  'index'=>1, 'position'=>1, 'comment'=>'Id'),
        	'lastname'=>array('type'=>'varchar(50)', 'label'=>'Name', 'enabled'=>1, 'visible'=>1,  'notnull'=>1,  'showoncombobox'=>1, 'index'=>1, 'position'=>20, 'searchall'=>1, 'comment'=>'Reference of object'),
        	'firstname'=>array('type'=>'varchar(50)', 'label'=>'Name','enabled'=>1, 'visible'=>1,  'notnull'=>1,  'showoncombobox'=>1, 'index'=>1, 'position'=>10, 'searchall'=>1, 'comment'=>'Reference of object'),
    	);

	/**
	 *    Constructor de la classe
	 *
	 *    @param   DoliDb  $db     Database handler
	 */
	function __construct($db)
	{
		$this->db = $db;

		// User preference
		$this->liste_limit = 0;
		$this->clicktodial_loaded = 0;

		// For cache usage
		$this->all_permissions_are_loaded = 0;
		$this->nb_rights = 0;

		// Force some default values
		$this->admin = 0;
		$this->employee = 1;

		$this->conf				    = new stdClass();
		$this->rights				= new stdClass();
		$this->rights->user			= new stdClass();
		$this->rights->user->user	= new stdClass();
		$this->rights->user->self	= new stdClass();
	}

	/**
	 *	Load a user from database with its id or ref (login).
	 *  This function does not load permissions, only user properties. Use getrights() for this just after the fetch.
	 *
	 *	@param	int		$id		       		If defined, id to used for search
	 * 	@param  string	$login       		If defined, login to used for search
	 *	@param  string	$sid				If defined, sid to used for search
	 * 	@param	int		$loadpersonalconf	1=also load personal conf of user (in $user->conf->xxx), 0=do not load personal conf.
	 *  @param  int     $entity             If a value is >= 0, we force the search on a specific entity. If -1, means search depens on default setup.
	 * 	@return	int							<0 if KO, 0 not found, >0 if OK
	 */
	function fetch($id='', $login='', $sid='', $loadpersonalconf=0, $entity=-1)
	{
		global $conf, $user;

		// Clean parameters
		$login=trim($login);

		// Get user
		$sql = "SELECT u.rowid, u.lastname, u.firstname, u.employee, u.gender, u.birth, u.email, u.job, u.skype, u.signature, u.office_phone, u.office_fax, u.user_mobile,";
		$sql.= " u.address, u.zip, u.town, u.fk_state as state_id, u.fk_country as country_id,";
		$sql.= " u.admin, u.login, u.note,";
		$sql.= " u.pass, u.pass_crypted, u.pass_temp, u.api_key,";
		$sql.= " u.fk_soc, u.fk_socpeople, u.fk_member, u.fk_user, u.ldap_sid,";
		$sql.= " u.statut, u.lang, u.entity,";
		$sql.= " u.datec as datec,";
		$sql.= " u.tms as datem,";
		$sql.= " u.datelastlogin as datel,";
		$sql.= " u.datepreviouslogin as datep,";
		$sql.= " u.photo as photo,";
		$sql.= " u.openid as openid,";
		$sql.= " u.accountancy_code,";
		$sql.= " u.thm,";
		$sql.= " u.tjm,";
		$sql.= " u.salary,";
		$sql.= " u.salaryextra,";
		$sql.= " u.weeklyhours,";
		$sql.= " u.color,";
		$sql.= " u.dateemployment,";
		$sql.= " u.ref_int, u.ref_ext,";
		$sql.= " u.default_range, u.default_c_exp_tax_cat,";			// Expense report default mode
		$sql.= " c.code as country_code, c.label as country,";
		$sql.= " d.code_departement as state_code, d.nom as state";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_country as c ON u.fk_country = c.rowid";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_departements as d ON u.fk_state = d.rowid";

		if ($entity < 0)
		{
			if ((empty($conf->multicompany->enabled) || empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) && (! empty($user->entity)))
			{
				$sql.= " WHERE u.entity IN (0,".$conf->entity.")";
			}
			else
			{
				$sql.= " WHERE u.entity IS NOT NULL";    // multicompany is on in transverse mode or user making fetch is on entity 0, so user is allowed to fetch anywhere into database
			}
		}
		else  // The fetch was forced on an entity
		{
			if (!empty($conf->multicompany->enabled) && !empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE))
				$sql.= " WHERE u.entity IS NOT NULL";    // multicompany is on in transverse mode or user making fetch is on entity 0, so user is allowed to fetch anywhere into database
			else
				$sql.= " WHERE u.entity IN (0, ".(($entity!='' && $entity >= 0)?$entity:$conf->entity).")";   // search in entity provided in parameter
		}

		if ($sid)    // permet une recherche du user par son SID ActiveDirectory ou Samba
		{
			$sql.= " AND (u.ldap_sid = '".$this->db->escape($sid)."' OR u.login = '".$this->db->escape($login)."') LIMIT 1";
		}
		else if ($login)
		{
			$sql.= " AND u.login = '".$this->db->escape($login)."'";
		}
		else
		{
			$sql.= " AND u.rowid = ".$id;
		}
		$sql.= " ORDER BY u.entity ASC";    // Avoid random result when there is 2 login in 2 different entities

		$result = $this->db->query($sql);
		if ($result)
		{
			$obj = $this->db->fetch_object($result);
			if ($obj)
			{
				$this->id 			= $obj->rowid;
				$this->ref 			= $obj->rowid;

				$this->ref_int 		= $obj->ref_int;
				$this->ref_ext 		= $obj->ref_ext;

				$this->ldap_sid 	= $obj->ldap_sid;
				$this->lastname		= $obj->lastname;
				$this->firstname 	= $obj->firstname;

				$this->employee		= $obj->employee;

				$this->login		= $obj->login;
				$this->gender       = $obj->gender;
				$this->birth        = $this->db->jdate($obj->birth);
				$this->pass_indatabase = $obj->pass;
				$this->pass_indatabase_crypted = $obj->pass_crypted;
				$this->pass			= $obj->pass;
				$this->pass_temp	= $obj->pass_temp;
				$this->api_key		= $obj->api_key;

				$this->address 		= $obj->address;
				$this->zip 			= $obj->zip;
				$this->town 		= $obj->town;

				$this->country_id   = $obj->country_id;
				$this->country_code = $obj->country_id?$obj->country_code:'';
				//$this->country 		= $obj->country_id?($langs->trans('Country'.$obj->country_code)!='Country'.$obj->country_code?$langs->transnoentities('Country'.$obj->country_code):$obj->country):'';

				$this->state_id     = $obj->state_id;
				$this->state_code   = $obj->state_code;
				$this->state        = ($obj->state!='-'?$obj->state:'');

				$this->office_phone	= $obj->office_phone;
				$this->office_fax   = $obj->office_fax;
				$this->user_mobile  = $obj->user_mobile;
				$this->email		= $obj->email;
				$this->skype		= $obj->skype;
				$this->job			= $obj->job;
				$this->signature	= $obj->signature;
				$this->admin		= $obj->admin;
				$this->note			= $obj->note;
				$this->statut		= $obj->statut;
				$this->photo		= $obj->photo;
				$this->openid		= $obj->openid;
				$this->lang			= $obj->lang;
				$this->entity		= $obj->entity;
				$this->accountancy_code		= $obj->accountancy_code;
				$this->thm			= $obj->thm;
				$this->tjm			= $obj->tjm;
				$this->salary		= $obj->salary;
				$this->salaryextra	= $obj->salaryextra;
				$this->weeklyhours	= $obj->weeklyhours;
				$this->color		= $obj->color;
				$this->dateemployment	= $this->db->jdate($obj->dateemployment);

				$this->datec				= $this->db->jdate($obj->datec);
				$this->datem				= $this->db->jdate($obj->datem);
				$this->datelastlogin		= $this->db->jdate($obj->datel);
				$this->datepreviouslogin	= $this->db->jdate($obj->datep);

				$this->societe_id           = $obj->fk_soc;		// deprecated
				$this->contact_id           = $obj->fk_socpeople;	// deprecated
				$this->socid                = $obj->fk_soc;
				$this->contactid            = $obj->fk_socpeople;
				$this->fk_member            = $obj->fk_member;
				$this->fk_user        		= $obj->fk_user;

				$this->default_range		= $obj->default_range;
				$this->default_c_exp_tax_cat	= $obj->default_c_exp_tax_cat;

				// Protection when module multicompany was set, admin was set to first entity and then, the module was disabled,
				// in such case, this admin user must be admin for ALL entities.
				if (empty($conf->multicompany->enabled) && $this->admin && $this->entity == 1) $this->entity = 0;

				// Retreive all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$this->db->free($result);
			}
			else
			{
				$this->error="USERNOTFOUND";
				dol_syslog(get_class($this)."::fetch user not found", LOG_DEBUG);

				$this->db->free($result);
				return 0;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		// To get back the global configuration unique to the user
		if ($loadpersonalconf)
		{
			// Load user->conf for user
			$sql = "SELECT param, value FROM ".MAIN_DB_PREFIX."user_param";
			$sql.= " WHERE fk_user = ".$this->id;
			$sql.= " AND entity = ".$conf->entity;
			//dol_syslog(get_class($this).'::fetch load personalized conf', LOG_DEBUG);
			$resql=$this->db->query($sql);
			if ($resql)
			{
				$num = $this->db->num_rows($resql);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($resql);
					$p=(! empty($obj->param)?$obj->param:'');
					if (! empty($p)) $this->conf->$p = $obj->value;
					$i++;
				}
				$this->db->free($resql);
			}
			else
			{
				$this->error=$this->db->lasterror();
				return -2;
			}

			// Load user->default_values for user. TODO Save this in memcached ?
			$sql = "SELECT rowid, entity, type, page, param, value";
			$sql.= " FROM ".MAIN_DB_PREFIX."default_values";
			$sql.= " WHERE entity IN (".$this->entity.",".$conf->entity.")";
			$sql.= " AND user_id IN (0, ".$this->id.")";
			$resql = $this->db->query($sql);
			if ($resql)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					if (! empty($obj->page) && ! empty($obj->type) && ! empty($obj->param))
					{
						// $obj->page is relative URL with or without params
						// $obj->type can be 'filters', 'sortorder', 'createform', ...
						// $obj->param is key or param
						$pagewithoutquerystring=$obj->page;
						$pagequeries='';
						if (preg_match('/^([^\?]+)\?(.*)$/', $pagewithoutquerystring, $reg))	// There is query param
						{
							$pagewithoutquerystring=$reg[1];
							$pagequeries=$reg[2];
						}
						$this->default_values[$pagewithoutquerystring][$obj->type][$pagequeries?$pagequeries:'_noquery_'][$obj->param]=$obj->value;
						//if ($pagequeries) $this->default_values[$pagewithoutquerystring][$obj->type.'_queries']=$pagequeries;
					}
				}
				// Sort by key, so _noquery_ is last
				if(!empty($this->default_values)) {
					foreach($this->default_values as $a => $b)
					{
						foreach($b as $c => $d)
						{
							krsort($this->default_values[$a][$c]);
						}
					}
				}
				$this->db->free($resql);
			}
			else
			{
				$this->error=$this->db->lasterror();
				return -3;
			}
		}

		return 1;
	}

	/**
	 *  Add a right to the user
	 *
	 * 	@param	int		$rid			id of permission to add
	 *  @param  string	$allmodule		Add all permissions of module $allmodule
	 *  @param  string	$allperms		Add all permissions of module $allmodule, subperms $allperms only
	 *  @param	int		$entity			Entity to use
	 *  @param  int	    $notrigger		1=Does not execute triggers, 0=Execute triggers
	 *  @return int						> 0 if OK, < 0 if KO
	 *  @see	clearrights, delrights, getrights
	 */
	function addrights($rid, $allmodule='', $allperms='', $entity=0, $notrigger=0)
	{
		global $conf, $user, $langs;

		$entity = (! empty($entity)?$entity:$conf->entity);

		dol_syslog(get_class($this)."::addrights $rid, $allmodule, $allperms, $entity");
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
			$sql.= " AND entity = ".$entity;

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
			if (! empty($subperms))   $whereforadd.=" OR (module='$module' AND perms='$perms' AND (subperms='lire' OR subperms='read'))";
			else if (! empty($perms)) $whereforadd.=" OR (module='$module' AND (perms='lire' OR perms='read') AND subperms IS NULL)";
		}
		else {
			// On a pas demande un droit en particulier mais une liste de droits
			// sur la base d'un nom de module de de perms
			// Where pour la liste des droits a ajouter
			if (! empty($allmodule))
			{
				$whereforadd="module='".$this->db->escape($allmodule)."'";
				if (! empty($allperms)) $whereforadd.=" AND perms='".$this->db->escape($allperms)."'";
			}
		}

		// Ajout des droits trouves grace au critere whereforadd
		if (! empty($whereforadd))
		{
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE ".$whereforadd;
			$sql.= " AND entity = ".$entity;

			$result=$this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$nid = $obj->id;

					$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = ".$this->id." AND fk_id=".$nid." AND entity = ".$entity;
					if (! $this->db->query($sql)) $error++;
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (entity, fk_user, fk_id) VALUES (".$entity.", ".$this->id.", ".$nid.")";
					if (! $this->db->query($sql)) $error++;

					$i++;
				}
			}
			else
			{
				$error++;
				dol_print_error($this->db);
			}
		}

		if (! $error && ! $notrigger)
		{
			$langs->load("other");
			$this->context = array('audit'=>$langs->trans("PermissionsAdd").($rid?' (id='.$rid.')':''));

			// Call trigger
			$result=$this->call_trigger('USER_MODIFY',$user);
			if ($result < 0) { $error++; }
			// End call triggers
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
	 *  Remove a right to the user
	 *
	 *  @param	int		$rid        Id du droit a retirer
	 *  @param  string	$allmodule  Retirer tous les droits du module allmodule
	 *  @param  string	$allperms   Retirer tous les droits du module allmodule, perms allperms
	 *  @param	int		$entity		Entity to use
	 *  @param  int	    $notrigger	1=Does not execute triggers, 0=Execute triggers
	 *  @return int         		> 0 if OK, < 0 if OK
	 *  @see	clearrights, addrights, getrights
	 */
	function delrights($rid, $allmodule='', $allperms='', $entity=0, $notrigger=0)
	{
		global $conf, $user, $langs;

		$error=0;
		$wherefordel='';
		$entity = (! empty($entity)?$entity:$conf->entity);

		$this->db->begin();

		if (! empty($rid))
		{
			// Si on a demande supression d'un droit en particulier, on recupere
			// les caracteristiques module, perms et subperms de ce droit.
			$sql = "SELECT module, perms, subperms";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE id = '".$this->db->escape($rid)."'";
			$sql.= " AND entity = ".$entity;

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
			if ($perms=='lire' || $perms=='read')       $wherefordel.=" OR (module='$module')";
		}
		else {
			// On a demande suppression d'un droit sur la base d'un nom de module ou perms
			// Where pour la liste des droits a supprimer
			if (! empty($allmodule)) $wherefordel="module='".$this->db->escape($allmodule)."'";
			if (! empty($allperms))  $wherefordel=" AND perms='".$this->db->escape($allperms)."'";
		}

		// Suppression des droits selon critere defini dans wherefordel
		if (! empty($wherefordel))
		{
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql.= " FROM ".MAIN_DB_PREFIX."rights_def";
			$sql.= " WHERE $wherefordel";
			$sql.= " AND entity = ".$entity;

			$result=$this->db->query($sql);
			if ($result)
			{
				$num = $this->db->num_rows($result);
				$i = 0;
				while ($i < $num)
				{
					$obj = $this->db->fetch_object($result);
					$nid = $obj->id;

					$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights";
					$sql.= " WHERE fk_user = ".$this->id." AND fk_id=".$nid;
					$sql.= " AND entity = ".$entity;
					if (! $this->db->query($sql)) $error++;

					$i++;
				}
			}
			else
			{
				$error++;
				dol_print_error($this->db);
			}
		}

		if (! $error && ! $notrigger)
		{
			$langs->load("other");
			$this->context = array('audit'=>$langs->trans("PermissionsDelete").($rid?' (id='.$rid.')':''));

			// Call trigger
			$result=$this->call_trigger('USER_MODIFY',$user);
			if ($result < 0) { $error++; }
			// End call triggers
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
	 *  Clear all permissions array of user
	 *
	 *  @return	void
	 *  @see	getrights
	 */
	function clearrights()
	{
		dol_syslog(get_class($this)."::clearrights reset user->rights");
		$this->rights='';
		$this->all_permissions_are_loaded=false;
		$this->_tab_loaded=array();
	}


	/**
	 *	Load permissions granted to user into object user
	 *
	 *	@param  string	$moduletag    Limit permission for a particular module ('' by default means load all permissions)
	 *	@return	void
	 *  @see	clearrights, delrights, addrights
	 */
	function getrights($moduletag='')
	{
		global $conf;

		if ($moduletag && isset($this->_tab_loaded[$moduletag]) && $this->_tab_loaded[$moduletag])
		{
			// Le fichier de ce module est deja charge
			return;
		}

		if ($this->all_permissions_are_loaded)
		{
			// Si les permissions ont deja ete charge pour ce user, on quitte
			return;
		}

		// Recuperation des droits utilisateurs + recuperation des droits groupes

		// D'abord les droits utilisateurs
		$sql = "SELECT r.module, r.perms, r.subperms";
		$sql.= " FROM ".MAIN_DB_PREFIX."user_rights as ur";
		$sql.= ", ".MAIN_DB_PREFIX."rights_def as r";
		$sql.= " WHERE r.id = ur.fk_id";
		if (! empty($conf->global->MULTICOMPANY_BACKWARD_COMPATIBILITY))
		{
			$sql.= " AND r.entity IN (0,".(! empty($conf->multicompany->enabled) && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)?"1,":"").$conf->entity.")";
		}
		else
		{
			$sql.= " AND ur.entity = ".$conf->entity;
		}
		$sql.= " AND ur.fk_user= ".$this->id;
		$sql.= " AND r.perms IS NOT NULL";
		if ($moduletag) $sql.= " AND r.module = '".$this->db->escape($moduletag)."'";

		$resql = $this->db->query($sql);
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
					if (! isset($this->rights) || ! is_object($this->rights)) $this->rights = new stdClass(); // For avoid error
					if ($module)
					{
						if (! isset($this->rights->$module) || ! is_object($this->rights->$module)) $this->rights->$module = new stdClass();
						if ($subperms)
						{
							if (! isset($this->rights->$module->$perms) || ! is_object($this->rights->$module->$perms)) $this->rights->$module->$perms = new stdClass();
							if(empty($this->rights->$module->$perms->$subperms)) $this->nb_rights++;
							$this->rights->$module->$perms->$subperms = 1;
						}
						else
						{
							if(empty($this->rights->$module->$perms)) $this->nb_rights++;
							$this->rights->$module->$perms = 1;
						}
					}
				}
				$i++;
			}
			$this->db->free($resql);
		}

		// Maintenant les droits groupes
		$sql = "SELECT r.module, r.perms, r.subperms";
		$sql.= " FROM ".MAIN_DB_PREFIX."usergroup_rights as gr,";
		$sql.= " ".MAIN_DB_PREFIX."usergroup_user as gu,";
		$sql.= " ".MAIN_DB_PREFIX."rights_def as r";
		$sql.= " WHERE r.id = gr.fk_id";
		if (! empty($conf->global->MULTICOMPANY_BACKWARD_COMPATIBILITY))
		{
			if (! empty($conf->multicompany->enabled) && ! empty($conf->global->MULTICOMPANY_TRANSVERSE_MODE)) {
				$sql.= " AND gu.entity IN (0,".$conf->entity.")";
			} else {
				$sql.= " AND r.entity = ".$conf->entity;
			}
		}
		else
		{
			$sql.= " AND gr.entity = ".$conf->entity;
			$sql.= " AND r.entity = ".$conf->entity;
		}
		$sql.= " AND gr.fk_usergroup = gu.fk_usergroup";
		$sql.= " AND gu.fk_user = ".$this->id;
		$sql.= " AND r.perms IS NOT NULL";
		if ($moduletag) $sql.= " AND r.module = '".$this->db->escape($moduletag)."'";

		$resql = $this->db->query($sql);
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
					if (! isset($this->rights) || ! is_object($this->rights)) $this->rights = new stdClass(); // For avoid error
					if (! isset($this->rights->$module) || ! is_object($this->rights->$module)) $this->rights->$module = new stdClass();
					if ($subperms)
					{
						if (! isset($this->rights->$module->$perms) || ! is_object($this->rights->$module->$perms)) $this->rights->$module->$perms = new stdClass();
						if(empty($this->rights->$module->$perms->$subperms)) $this->nb_rights++;
						$this->rights->$module->$perms->$subperms = 1;
					}
					else
					{
						if(empty($this->rights->$module->$perms)) $this->nb_rights++;
						// if we have already define a subperm like this $this->rights->$module->level1->level2 with llx_user_rights, we don't want override level1 because the level2 can be not define on user group
						if (!is_object($this->rights->$module->$perms)) $this->rights->$module->$perms = 1;
					}

				}
				$i++;
			}
			$this->db->free($resql);
		}

		// For backward compatibility
		if (isset($this->rights->propale) && ! isset($this->rights->propal)) $this->rights->propal = $this->rights->propale;
		if (isset($this->rights->propal) && ! isset($this->rights->propale)) $this->rights->propale = $this->rights->propal;

		if (! $moduletag)
		{
			// Si module etait non defini, alors on a tout charge, on peut donc considerer
			// que les droits sont en cache (car tous charges) pour cet instance de user
			$this->all_permissions_are_loaded=1;
		}
		else
		{
			// Si module defini, on le marque comme charge en cache
			$this->_tab_loaded[$moduletag]=1;
		}
	}

	/**
	 *  Change status of a user
	 *
	 *	@param	int		$statut		Status to set
	 *  @return int     			<0 if KO, 0 if nothing is done, >0 if OK
	 */
	function setstatus($statut)
	{
		global $conf,$langs,$user;

		$error=0;

		// Check parameters
		if ($this->statut == $statut) return 0;
		else $this->statut = $statut;

		$this->db->begin();

		// Deactivate user
		$sql = "UPDATE ".MAIN_DB_PREFIX."user";
		$sql.= " SET statut = ".$this->statut;
		$sql.= " WHERE rowid = ".$this->id;
		$result = $this->db->query($sql);

		dol_syslog(get_class($this)."::setstatus", LOG_DEBUG);
		if ($result)
		{
			// Call trigger
			$result=$this->call_trigger('USER_ENABLEDISABLE',$user);
			if ($result < 0) { $error++; }
			// End call triggers
		}

		if ($error)
		{
			$this->db->rollback();
			return -$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param int[]|int $categories Category or categories IDs
	 */
	public function setCategories($categories)
	{
		// Handle single category
		if (!is_array($categories)) {
			$categories = array($categories);
		}

		// Get current categories
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, Categorie::TYPE_USER, 'id');

		// Diff
		if (is_array($existing)) {
			$to_del = array_diff($existing, $categories);
			$to_add = array_diff($categories, $existing);
		} else {
			$to_del = array(); // Nothing to delete
			$to_add = $categories;
		}

		// Process
		foreach ($to_del as $del) {
			if ($c->fetch($del) > 0) {
				$c->del_type($this, 'user');
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0) {
				$c->add_type($this, 'user');
			}
		}

		return;
	}

	/**
	 *    	Delete the user
	 *
	 * 		@return		int		<0 if KO, >0 if OK
	 */
	function delete()
	{
		global $user,$conf,$langs;

		$error=0;

		$this->db->begin();

		$this->fetch($this->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);

		// Remove rights
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = ".$this->id;

		if (! $error && ! $this->db->query($sql))
		{
			$error++;
			$this->error = $this->db->lasterror();
		}

		// Remove group
		$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_user WHERE fk_user  = ".$this->id;
		if (! $error && ! $this->db->query($sql))
		{
			$error++;
			$this->error = $this->db->lasterror();
		}

		// If contact, remove link
		if ($this->contact_id)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."socpeople SET fk_user_creat = null WHERE rowid = ".$this->contact_id;
			if (! $error && ! $this->db->query($sql))
			{
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

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

		// Remove user
		if (! $error)
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."user WHERE rowid = ".$this->id;
		   	dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		   	if (! $this->db->query($sql))
		   	{
		   		$error++;
		   		$this->error = $this->db->lasterror();
		   	}
		}

		if (! $error)
		{
			// Call trigger
			$result=$this->call_trigger('USER_DELETE',$user);
			if ($result < 0)
			{
				$error++;
				$this->db->rollback();
				return -1;
			}
			// End call triggers

			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Create a user into database
	 *
	 *  @param	User	$user        	Objet user doing creation
	 *  @param  int		$notrigger		1=do not execute triggers, 0 otherwise
	 *  @return int			         	<0 if KO, id of created user if OK
	 */
	function create($user, $notrigger=0)
	{
		global $conf,$langs;
		global $mysoc;

		// Clean parameters
		$this->login = trim($this->login);
		if (! isset($this->entity)) $this->entity=$conf->entity;	// If not defined, we use default value

		dol_syslog(get_class($this)."::create login=".$this->login.", user=".(is_object($user)?$user->id:''), LOG_DEBUG);

		// Check parameters
		if (! empty($conf->global->USER_MAIL_REQUIRED) && ! isValidEMail($this->email))
		{
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail",$this->email);
			return -1;
		}
		if (empty($this->login))
		{
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFieldRequired",$langs->transnoentitiesnoconv("Login"));
			return -1;
		}

		$this->datec = dol_now();

		$error=0;
		$this->db->begin();

		$sql = "SELECT login FROM ".MAIN_DB_PREFIX."user";
		$sql.= " WHERE login ='".$this->db->escape($this->login)."'";
		$sql.= " AND entity IN (0,".$this->db->escape($conf->entity).")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$this->db->free($resql);

			if ($num)
			{
				$this->error = 'ErrorLoginAlreadyExists';
				dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);
				$this->db->rollback();
				return -6;
			}
			else
			{
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."user (datec,login,ldap_sid,entity)";
				$sql.= " VALUES('".$this->db->idate($this->datec)."','".$this->db->escape($this->login)."','".$this->db->escape($this->ldap_sid)."',".$this->db->escape($this->entity).")";
				$result=$this->db->query($sql);

				dol_syslog(get_class($this)."::create", LOG_DEBUG);
				if ($result)
				{
					$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."user");

					// Set default rights
					if ($this->set_default_rights() < 0)
					{
						$this->error='ErrorFailedToSetDefaultRightOfUser';
						$this->db->rollback();
						return -5;
					}

					// Update minor fields
					$result = $this->update($user,1,1);
					if ($result < 0)
					{
						$this->db->rollback();
						return -4;
					}

					if (! empty($conf->global->STOCK_USERSTOCK_AUTOCREATE))
					{
						require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
						$langs->load("stocks");
						$entrepot = new Entrepot($this->db);
						$entrepot->libelle = $langs->trans("PersonalStock",$this->getFullName($langs));
						$entrepot->description = $langs->trans("ThisWarehouseIsPersonalStock",$this->getFullName($langs));
						$entrepot->statut = 1;
						$entrepot->country_id = $mysoc->country_id;
						$entrepot->create($user);
					}

					if (! $notrigger)
					{
						// Call trigger
						$result=$this->call_trigger('USER_CREATE',$user);
						if ($result < 0) { $error++; }
						// End call triggers
					}

					if (! $error)
					{
						$this->db->commit();
						return $this->id;
					}
					else
					{
						//$this->error=$interface->error;
						dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
						$this->db->rollback();
						return -3;
					}
				}
				else
				{
					$this->error=$this->db->lasterror();
					$this->db->rollback();
					return -2;
				}
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Create a user from a contact object. User will be internal but if contact is linked to a third party, user will be external
	 *
	 *  @param	Contact	$contact    Object for source contact
	 * 	@param  string	$login      Login to force
	 *  @param  string	$password   Password to force
	 *  @return int 				<0 if error, if OK returns id of created user
	 */
	function create_from_contact($contact,$login='',$password='')
	{
		global $conf,$user,$langs;

		$error=0;

		// Define parameters
		$this->admin		= 0;
		$this->lastname		= $contact->lastname;
		$this->firstname	= $contact->firstname;
		$this->gender		= $contact->gender;
		$this->email		= $contact->email;
		$this->skype 		= $contact->skype;
		$this->office_phone	= $contact->phone_pro;
		$this->office_fax	= $contact->fax;
		$this->user_mobile	= $contact->phone_mobile;
		$this->address      = $contact->address;
		$this->zip          = $contact->zip;
		$this->town         = $contact->town;
		$this->state_id     = $contact->state_id;
		$this->country_id   = $contact->country_id;
		$this->employee     = 0;

		if (empty($login)) $login=strtolower(substr($contact->firstname, 0, 4)) . strtolower(substr($contact->lastname, 0, 4));
		$this->login = $login;

		$this->db->begin();

		// Cree et positionne $this->id
		$result=$this->create($user);
		if ($result > 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."user";
			$sql.= " SET fk_socpeople=".$contact->id;
			if ($contact->socid) $sql.=", fk_soc=".$contact->socid;
			$sql.= " WHERE rowid=".$this->id;
			$resql=$this->db->query($sql);

			dol_syslog(get_class($this)."::create_from_contact", LOG_DEBUG);
			if ($resql)
			{
				$this->context['createfromcontact']='createfromcontact';

				// Call trigger
				$result=$this->call_trigger('USER_CREATE',$user);
				if ($result < 0) { $error++; $this->db->rollback(); return -1; }
				// End call triggers

				$this->db->commit();
				return $this->id;
			}
			else
			{
				$this->error=$this->db->error();

				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			// $this->error deja positionne
			dol_syslog(get_class($this)."::create_from_contact - 0");

			$this->db->rollback();
			return $result;
		}

	}

	/**
	 *  Create a user into database from a member object
	 *
	 *  @param	Adherent	$member		Object member source
	 * 	@param	string		$login		Login to force
	 *  @return int						<0 if KO, if OK, return id of created account
	 */
	function create_from_member($member,$login='')
	{
		global $conf,$user,$langs;

		// Positionne parametres
		$this->admin = 0;
		$this->lastname     = $member->lastname;
		$this->firstname    = $member->firstname;
		$this->gender		= $member->gender;
		$this->email        = $member->email;
		$this->fk_member    = $member->id;
		$this->pass         = $member->pass;
		$this->address      = $member->address;
		$this->zip          = $member->zip;
		$this->town         = $member->town;
		$this->state_id     = $member->state_id;
		$this->country_id   = $member->country_id;

		if (empty($login)) $login=strtolower(substr($member->firstname, 0, 4)) . strtolower(substr($member->lastname, 0, 4));
		$this->login = $login;

		$this->db->begin();

		// Create and set $this->id
		$result=$this->create($user);
		if ($result > 0)
		{
			$newpass=$this->setPassword($user,$this->pass);
			if (is_numeric($newpass) && $newpass < 0) $result=-2;

			if ($result > 0 && $member->fk_soc)	// If member is linked to a thirdparty
			{
				$sql = "UPDATE ".MAIN_DB_PREFIX."user";
				$sql.= " SET fk_soc=".$member->fk_soc;
				$sql.= " WHERE rowid=".$this->id;

				dol_syslog(get_class($this)."::create_from_member", LOG_DEBUG);
				$resql=$this->db->query($sql);
				if ($resql)
				{
					$this->db->commit();
					return $this->id;
				}
				else
				{
					$this->error=$this->db->lasterror();

					$this->db->rollback();
					return -1;
				}
			}
		}

		if ($result > 0)
		{
			$this->db->commit();
			return $this->id;
		}
		else
		{
			// $this->error deja positionne
			$this->db->rollback();
			return -2;
		}
	}

	/**
	 *    Assign rights by default
	 *
	 *    @return     integer erreur <0, si ok renvoi le nbre de droits par defaut positionnes
	 */
	function set_default_rights()
	{
		global $conf;

		$sql = "SELECT id FROM ".MAIN_DB_PREFIX."rights_def";
		$sql.= " WHERE bydefault = 1";
		$sql.= " AND entity = ".$conf->entity;

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			$rd = array();
			while ($i < $num)
			{
				$row = $this->db->fetch_row($resql);
				$rd[$i] = $row[0];
				$i++;
			}
			$this->db->free($resql);
		}
		$i = 0;
		while ($i < $num)
		{

			$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_rights WHERE fk_user = $this->id AND fk_id=$rd[$i]";
			$result=$this->db->query($sql);

			$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_rights (fk_user, fk_id) VALUES ($this->id, $rd[$i])";
			$result=$this->db->query($sql);
			if (! $result) return -1;
			$i++;
		}

		return $i;
	}

	/**
	 *  	Update a user into database (and also password if this->pass is defined)
	 *
	 *		@param	User	$user				User qui fait la mise a jour
	 *    	@param  int		$notrigger			1 ne declenche pas les triggers, 0 sinon
	 *		@param	int		$nosyncmember		0=Synchronize linked member (standard info), 1=Do not synchronize linked member
	 *		@param	int		$nosyncmemberpass	0=Synchronize linked member (password), 1=Do not synchronize linked member
	 *		@param	int		$nosynccontact		0=Synchronize linked contact, 1=Do not synchronize linked contact
	 *    	@return int 		        		<0 si KO, >=0 si OK
	 */
	function update($user, $notrigger=0, $nosyncmember=0, $nosyncmemberpass=0, $nosynccontact=0)
	{
		global $conf, $langs;

		$nbrowsaffected=0;
		$error=0;

		dol_syslog(get_class($this)."::update notrigger=".$notrigger.", nosyncmember=".$nosyncmember.", nosyncmemberpass=".$nosyncmemberpass);

		// Clean parameters
		$this->lastname     = trim($this->lastname);
		$this->firstname    = trim($this->firstname);
		$this->employee    	= $this->employee?$this->employee:0;
		$this->login        = trim($this->login);
		$this->gender       = trim($this->gender);
		$this->birth        = trim($this->birth);
		$this->pass         = trim($this->pass);
		$this->api_key      = trim($this->api_key);
		$this->address		= $this->address?trim($this->address):trim($this->address);
		$this->zip			= $this->zip?trim($this->zip):trim($this->zip);
		$this->town			= $this->town?trim($this->town):trim($this->town);
		$this->state_id		= trim($this->state_id);
		$this->country_id	= ($this->country_id > 0)?$this->country_id:0;
		$this->office_phone = trim($this->office_phone);
		$this->office_fax   = trim($this->office_fax);
		$this->user_mobile  = trim($this->user_mobile);
		$this->email        = trim($this->email);
		$this->skype        = trim($this->skype);
		$this->job    		= trim($this->job);
		$this->signature    = trim($this->signature);
		$this->note         = trim($this->note);
		$this->openid       = trim(empty($this->openid)?'':$this->openid);    // Avoid warning
		$this->admin        = $this->admin?$this->admin:0;
		$this->address		= empty($this->address)?'':$this->address;
		$this->zip			= empty($this->zip)?'':$this->zip;
		$this->town			= empty($this->town)?'':$this->town;
		$this->accountancy_code = trim($this->accountancy_code);
		$this->color 		= empty($this->color)?'':$this->color;
		$this->dateemployment 	= empty($this->dateemployment)?'':$this->dateemployment;

		// Check parameters
		if (! empty($conf->global->USER_MAIL_REQUIRED) && ! isValidEMail($this->email))
		{
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail",$this->email);
			return -1;
		}
		if (empty($this->login))
		{
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFieldRequired",$this->login);
			return -1;
		}

		$this->db->begin();

		// Update datas
		$sql = "UPDATE ".MAIN_DB_PREFIX."user SET";
		$sql.= " lastname = '".$this->db->escape($this->lastname)."'";
		$sql.= ", firstname = '".$this->db->escape($this->firstname)."'";
		$sql.= ", employee = ".(int) $this->employee;
		$sql.= ", login = '".$this->db->escape($this->login)."'";
		$sql.= ", api_key = ".($this->api_key ? "'".$this->db->escape($this->api_key)."'" : "null");
		$sql.= ", gender = ".($this->gender != -1 ? "'".$this->db->escape($this->gender)."'" : "null");	// 'man' or 'woman'
		$sql.= ", birth=".(strval($this->birth)!='' ? "'".$this->db->idate($this->birth)."'" : 'null');
		if (! empty($user->admin)) $sql.= ", admin = ".(int) $this->admin;	// admin flag can be set/unset only by an admin user
		$sql.= ", address = '".$this->db->escape($this->address)."'";
		$sql.= ", zip = '".$this->db->escape($this->zip)."'";
		$sql.= ", town = '".$this->db->escape($this->town)."'";
		$sql.= ", fk_state = ".((! empty($this->state_id) && $this->state_id > 0)?"'".$this->db->escape($this->state_id)."'":"null");
		$sql.= ", fk_country = ".((! empty($this->country_id) && $this->country_id > 0)?"'".$this->db->escape($this->country_id)."'":"null");
		$sql.= ", office_phone = '".$this->db->escape($this->office_phone)."'";
		$sql.= ", office_fax = '".$this->db->escape($this->office_fax)."'";
		$sql.= ", user_mobile = '".$this->db->escape($this->user_mobile)."'";
		$sql.= ", email = '".$this->db->escape($this->email)."'";
		$sql.= ", skype = '".$this->db->escape($this->skype)."'";
		$sql.= ", job = '".$this->db->escape($this->job)."'";
		$sql.= ", signature = '".$this->db->escape($this->signature)."'";
		$sql.= ", accountancy_code = '".$this->db->escape($this->accountancy_code)."'";
		$sql.= ", color = '".$this->db->escape($this->color)."'";
		$sql.= ", dateemployment=".(strval($this->dateemployment)!='' ? "'".$this->db->idate($this->dateemployment)."'" : 'null');
		$sql.= ", note = '".$this->db->escape($this->note)."'";
		$sql.= ", photo = ".($this->photo?"'".$this->db->escape($this->photo)."'":"null");
		$sql.= ", openid = ".($this->openid?"'".$this->db->escape($this->openid)."'":"null");
		$sql.= ", fk_user = ".($this->fk_user > 0?"'".$this->db->escape($this->fk_user)."'":"null");
		if (isset($this->thm) || $this->thm != '')                 $sql.= ", thm= ".($this->thm != ''?"'".$this->db->escape($this->thm)."'":"null");
		if (isset($this->tjm) || $this->tjm != '')                 $sql.= ", tjm= ".($this->tjm != ''?"'".$this->db->escape($this->tjm)."'":"null");
		if (isset($this->salary) || $this->salary != '')           $sql.= ", salary= ".($this->salary != ''?"'".$this->db->escape($this->salary)."'":"null");
		if (isset($this->salaryextra) || $this->salaryextra != '') $sql.= ", salaryextra= ".($this->salaryextra != ''?"'".$this->db->escape($this->salaryextra)."'":"null");
		$sql.= ", weeklyhours= ".($this->weeklyhours != ''?"'".$this->db->escape($this->weeklyhours)."'":"null");
		$sql.= ", entity = '".$this->db->escape($this->entity)."'";
		$sql.= ", default_range = ".($this->default_range > 0 ? $this->default_range : 'null');
		$sql.= ", default_c_exp_tax_cat = ".($this->default_c_exp_tax_cat > 0 ? $this->default_c_exp_tax_cat : 'null');

		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$nbrowsaffected+=$this->db->affected_rows($resql);

			// Update password
			if (!empty($this->pass))
			{
				if ($this->pass != $this->pass_indatabase && $this->pass != $this->pass_indatabase_crypted)
				{
					// Si mot de passe saisi et different de celui en base
					$result=$this->setPassword($user,$this->pass,0,$notrigger,$nosyncmemberpass);
					if (! $nbrowsaffected) $nbrowsaffected++;
				}
			}

			// If user is linked to a member, remove old link to this member
			if ($this->fk_member > 0)
			{
				dol_syslog(get_class($this)."::update remove link with member. We will recreate it later", LOG_DEBUG);
				$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member = NULL where fk_member = ".$this->fk_member;
				$resql = $this->db->query($sql);
				if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -5; }
			}
			// Set link to user
			dol_syslog(get_class($this)."::update set link with member", LOG_DEBUG);
			$sql = "UPDATE ".MAIN_DB_PREFIX."user SET fk_member =".($this->fk_member>0?$this->fk_member:'null')." where rowid = ".$this->id;
			$resql = $this->db->query($sql);
			if (! $resql) { $this->error=$this->db->error(); $this->db->rollback(); return -5; }

			if ($nbrowsaffected)	// If something has changed in data
			{
				if ($this->fk_member > 0 && ! $nosyncmember)
				{
					dol_syslog(get_class($this)."::update user is linked with a member. We try to update member too.", LOG_DEBUG);

					require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

					// This user is linked with a member, so we also update member information
					// if this is an update.
					$adh=new Adherent($this->db);
					$result=$adh->fetch($this->fk_member);

					if ($result > 0)
					{
						$adh->firstname=$this->firstname;
						$adh->lastname=$this->lastname;
						$adh->login=$this->login;
						$adh->gender=$this->gender;
						$adh->birth=$this->birth;

						$adh->pass=$this->pass;

						$adh->societe=(empty($adh->societe) && $this->societe_id ? $this->societe_id : $adh->societe);

						$adh->address=$this->address;
						$adh->town=$this->town;
						$adh->zip=$this->zip;
						$adh->state_id=$this->state_id;
						$adh->country_id=$this->country_id;

						$adh->email=$this->email;
						$adh->skype=$this->skype;
						$adh->phone=$this->office_phone;
						$adh->phone_mobile=$this->user_mobile;

						$adh->user_id=$this->id;
						$adh->user_login=$this->login;

						$result=$adh->update($user,0,1,0);
						if ($result < 0)
						{
							$this->error=$adh->error;
							$this->errors=$adh->errors;
							dol_syslog(get_class($this)."::update error after calling adh->update to sync it with user: ".$this->error, LOG_ERR);
							$error++;
						}
					}
					elseif ($result < 0)
					{
						$this->error=$adh->error;
						$this->errors=$adh->errors;
						$error++;
					}
				}

				if ($this->contact_id > 0 && ! $nosynccontact)
				{
					dol_syslog(get_class($this)."::update user is linked with a contact. We try to update contact too.", LOG_DEBUG);

					require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

					// This user is linked with a contact, so we also update contact information
					// if this is an update.
					$tmpobj=new Contact($this->db);
					$result=$tmpobj->fetch($this->contact_id);

					if ($result >= 0)
					{
						$tmpobj->firstname=$this->firstname;
						$tmpobj->lastname=$this->lastname;
						$tmpobj->login=$this->login;
						$tmpobj->gender=$this->gender;
						$tmpobj->birth=$this->birth;

						//$tmpobj->pass=$this->pass;

						//$tmpobj->societe=(empty($tmpobj->societe) && $this->societe_id ? $this->societe_id : $tmpobj->societe);

						$tmpobj->email=$this->email;
						$tmpobj->skype=$this->skype;
						$tmpobj->phone_pro=$this->office_phone;
						$tmpobj->phone_mobile=$this->user_mobile;
						$tmpobj->fax=$this->office_fax;

						$tmpobj->address=$this->address;
						$tmpobj->town=$this->town;
						$tmpobj->zip=$this->zip;
						$tmpobj->state_id=$this->state_id;
						$tmpobj->country_id=$this->country_id;

						$tmpobj->user_id=$this->id;
						$tmpobj->user_login=$this->login;

						$result=$tmpobj->update($tmpobj->id, $user, 0, 'update', 1);
						if ($result < 0)
						{
							$this->error=$tmpobj->error;
							$this->errors=$tmpobj->errors;
							dol_syslog(get_class($this)."::update error after calling adh->update to sync it with user: ".$this->error, LOG_ERR);
							$error++;
						}
					}
					else
					{
						$this->error=$tmpobj->error;
						$this->errors=$tmpobj->errors;
						$error++;
					}
				}
			}

			$action='update';

			// Actions on extra fields
			if (! $error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED))
			{
				$result=$this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}

			if (! $error && ! $notrigger)
			{
				// Call trigger
				$result=$this->call_trigger('USER_MODIFY',$user);
				if ($result < 0) { $error++; }
				// End call triggers
			}

			if (! $error)
			{
				$this->db->commit();
				return $nbrowsaffected;
			}
			else
			{
				dol_syslog(get_class($this)."::update error=".$this->error,LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -2;
		}

	}

	/**
	 *    Mise a jour en base de la date de derniere connexion d'un utilisateur
	 *	  Fonction appelee lors d'une nouvelle connexion
	 *
	 *    @return     <0 si echec, >=0 si ok
	 */
	function update_last_login_date()
	{
		$now=dol_now();

		$sql = "UPDATE ".MAIN_DB_PREFIX."user SET";
		$sql.= " datepreviouslogin = datelastlogin,";
		$sql.= " datelastlogin = '".$this->db->idate($now)."',";
		$sql.= " tms = tms";    // La date de derniere modif doit changer sauf pour la mise a jour de date de derniere connexion
		$sql.= " WHERE rowid = ".$this->id;

		dol_syslog(get_class($this)."::update_last_login_date user->id=".$this->id." ".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$this->datepreviouslogin=$this->datelastlogin;
			$this->datelastlogin=$now;
			return 1;
		}
		else
		{
			$this->error=$this->db->lasterror().' sql='.$sql;
			return -1;
		}
	}


	/**
	 *  Change password of a user
	 *
	 *  @param	User	$user             		Object user of user making change
	 *  @param  string	$password         		New password in clear text (to generate if not provided)
	 *	@param	int		$changelater			1=Change password only after clicking on confirm email
	 *	@param	int		$notrigger				1=Does not launch triggers
	 *	@param	int		$nosyncmember	        Do not synchronize linked member
	 *  @return string 			          		If OK return clear password, 0 if no change, < 0 if error
	 */
	function setPassword($user, $password='', $changelater=0, $notrigger=0, $nosyncmember=0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT .'/core/lib/security2.lib.php';

		$error=0;

		dol_syslog(get_class($this)."::setPassword user=".$user->id." password=".preg_replace('/./i','*',$password)." changelater=".$changelater." notrigger=".$notrigger." nosyncmember=".$nosyncmember, LOG_DEBUG);

		// If new password not provided, we generate one
		if (! $password)
		{
			$password=getRandomPassword(false);
		}

		// Crypt password
		$password_crypted = dol_hash($password);

		// Mise a jour
		if (! $changelater)
		{
			if (! is_object($this->oldcopy)) $this->oldcopy = clone $this;

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."user";
			$sql.= " SET pass_crypted = '".$this->db->escape($password_crypted)."',";
			$sql.= " pass_temp = null";
			if (! empty($conf->global->DATABASE_PWD_ENCRYPTED))
			{
				$sql.= ", pass = null";
			}
			else
			{
				$sql.= ", pass = '".$this->db->escape($password)."'";
			}
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::setPassword", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result)
			{
				if ($this->db->affected_rows($result))
				{
					$this->pass=$password;
					$this->pass_indatabase=$password;
					$this->pass_indatabase_crypted=$password_crypted;

					if ($this->fk_member && ! $nosyncmember)
					{
						require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

						// This user is linked with a member, so we also update members informations
						// if this is an update.
						$adh=new Adherent($this->db);
						$result=$adh->fetch($this->fk_member);

						if ($result >= 0)
						{
							$result=$adh->setPassword($user,$this->pass,(empty($conf->global->DATABASE_PWD_ENCRYPTED)?0:1),1);	// Cryptage non gere dans module adherent
							if ($result < 0)
							{
								$this->error=$adh->error;
								dol_syslog(get_class($this)."::setPassword ".$this->error,LOG_ERR);
								$error++;
							}
						}
						else
						{
							$this->error=$adh->error;
							$error++;
						}
					}

					dol_syslog(get_class($this)."::setPassword notrigger=".$notrigger." error=".$error,LOG_DEBUG);

					if (! $error && ! $notrigger)
					{
						// Call trigger
						$result=$this->call_trigger('USER_NEW_PASSWORD',$user);
						if ($result < 0) { $error++; $this->db->rollback(); return -1; }
						// End call triggers
					}

					$this->db->commit();
					return $this->pass;
				}
				else
				{
					$this->db->rollback();
					return 0;
				}
			}
			else
			{
				$this->db->rollback();
				dol_print_error($this->db);
				return -1;
			}
		}
		else
		{
			// We store clear password in password temporary field.
			// After receiving confirmation link, we will crypt it and store it in pass_crypted
			$sql = "UPDATE ".MAIN_DB_PREFIX."user";
			$sql.= " SET pass_temp = '".$this->db->escape($password)."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::setPassword", LOG_DEBUG);	// No log
			$result = $this->db->query($sql);
			if ($result)
			{
				return $password;
			}
			else
			{
				dol_print_error($this->db);
				return -3;
			}
		}
	}


	/**
	 *  Send new password by email
	 *
	 *  @param	User	$user           Object user that send email
	 *  @param	string	$password       New password
	 *	@param	int		$changelater	0=Send clear passwod into email, 1=Change password only after clicking on confirm email. @TODO Add method 2 = Send link to reset password
	 *  @return int 		            < 0 si erreur, > 0 si ok
	 */
	function send_password($user, $password='', $changelater=0)
	{
		global $conf,$langs;
		global $dolibarr_main_url_root;

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

		$msgishtml=0;

		// Define $msg
		$mesg = '';

		$outputlangs=new Translate("",$conf);
		if (isset($this->conf->MAIN_LANG_DEFAULT)
		&& $this->conf->MAIN_LANG_DEFAULT != 'auto')
		{	// If user has defined its own language (rare because in most cases, auto is used)
			$outputlangs->getDefaultLang($this->conf->MAIN_LANG_DEFAULT);
		}
		else
		{	// If user has not defined its own language, we used current language
			$outputlangs=$langs;
		}

		$outputlangs->load("main");
		$outputlangs->load("errors");
		$outputlangs->load("users");
		$outputlangs->load("other");

		$appli=constant('DOL_APPLICATION_TITLE');
		if (!empty($conf->global->MAIN_APPLICATION_TITLE)) $appli=$conf->global->MAIN_APPLICATION_TITLE;

		$subject = $outputlangs->transnoentitiesnoconv("SubjectNewPassword", $appli);

		// Define $urlwithroot
		$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
		$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;		// This is to use external domain name found into config file

		if (! $changelater)
		{
			$url = $urlwithroot.'/';

			$mesg.= $outputlangs->transnoentitiesnoconv("RequestToResetPasswordReceived").".\n";
			$mesg.= $outputlangs->transnoentitiesnoconv("NewKeyIs")." :\n\n";
			$mesg.= $outputlangs->transnoentitiesnoconv("Login")." = ".$this->login."\n";
			$mesg.= $outputlangs->transnoentitiesnoconv("Password")." = ".$password."\n\n";
			$mesg.= "\n";

			$mesg.= $outputlangs->transnoentitiesnoconv("ClickHereToGoTo", $appli).': '.$url."\n\n";
			$mesg.= "--\n";
			$mesg.= $user->getFullName($outputlangs);	// Username that make then sending

			dol_syslog(get_class($this)."::send_password changelater is off, url=".$url);
		}
		else
		{
			$url = $urlwithroot.'/user/passwordforgotten.php?action=validatenewpassword&username='.$this->login."&passwordhash=".dol_hash($password);

			$mesg.= $outputlangs->transnoentitiesnoconv("RequestToResetPasswordReceived")."\n";
			$mesg.= $outputlangs->transnoentitiesnoconv("NewKeyWillBe")." :\n\n";
			$mesg.= $outputlangs->transnoentitiesnoconv("Login")." = ".$this->login."\n";
			$mesg.= $outputlangs->transnoentitiesnoconv("Password")." = ".$password."\n\n";
			$mesg.= "\n";
			$mesg.= $outputlangs->transnoentitiesnoconv("YouMustClickToChange")." :\n";
			$mesg.= $url."\n\n";
			$mesg.= $outputlangs->transnoentitiesnoconv("ForgetIfNothing")."\n\n";

			dol_syslog(get_class($this)."::send_password changelater is on, url=".$url);
		}

		$mailfile = new CMailFile(
			$subject,
			$this->email,
			$conf->global->MAIN_MAIL_EMAIL_FROM,
			$mesg,
			array(),
			array(),
			array(),
			'',
			'',
			0,
			$msgishtml
		);

		if ($mailfile->sendfile())
		{
			return 1;
		}
		else
		{
			$langs->trans("errors");
			$this->error=$langs->trans("ErrorFailedToSendPassword").' '.$mailfile->error;
			return -1;
		}
	}

	/**
	 * 		Renvoie la derniere erreur fonctionnelle de manipulation de l'objet
	 *
	 * 		@return    string      chaine erreur
	 */
	function error()
	{
		return $this->error;
	}


	/**
	 *    	Read clicktodial information for user
	 *
	 * 		@return		<0 if KO, >0 if OK
	 */
	function fetch_clicktodial()
	{
		$sql = "SELECT url, login, pass, poste ";
		$sql.= " FROM ".MAIN_DB_PREFIX."user_clicktodial as u";
		$sql.= " WHERE u.fk_user = ".$this->id;

		$resql = $this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);

				$this->clicktodial_url = $obj->url;
				$this->clicktodial_login = $obj->login;
				$this->clicktodial_password = $obj->pass;
				$this->clicktodial_poste = $obj->poste;
			}

			$this->clicktodial_loaded = 1;	// Data loaded (found or not)

			$this->db->free($resql);
			return 1;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Update clicktodial info
	 *
	 *  @return	integer
	 */
	function update_clicktodial()
	{
		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."user_clicktodial";
		$sql .= " WHERE fk_user = ".$this->id;

		dol_syslog(get_class($this).'::update_clicktodial', LOG_DEBUG);
		$result = $this->db->query($sql);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."user_clicktodial";
		$sql .= " (fk_user,url,login,pass,poste)";
		$sql .= " VALUES (".$this->id;
		$sql .= ", '". $this->db->escape($this->clicktodial_url) ."'";
		$sql .= ", '". $this->db->escape($this->clicktodial_login) ."'";
		$sql .= ", '". $this->db->escape($this->clicktodial_password) ."'";
		$sql .= ", '". $this->db->escape($this->clicktodial_poste) ."')";

		dol_syslog(get_class($this).'::update_clicktodial', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Add user into a group
	 *
	 *  @param	int	$group      Id of group
	 *  @param  int		$entity     Entity
	 *  @param  int		$notrigger  Disable triggers
	 *  @return int  				<0 if KO, >0 if OK
	 */
	function SetInGroup($group, $entity, $notrigger=0)
	{
		global $conf, $langs, $user;

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_user";
		$sql.= " WHERE fk_user  = ".$this->id;
		$sql.= " AND fk_usergroup = ".$group;
		$sql.= " AND entity = ".$entity;

		$result = $this->db->query($sql);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."usergroup_user (entity, fk_user, fk_usergroup)";
		$sql.= " VALUES (".$entity.",".$this->id.",".$group.")";

		$result = $this->db->query($sql);
		if ($result)
		{
			if (! $error && ! $notrigger)
			{
				$this->newgroupid=$group;    // deprecated. Remove this.
				$this->context = array('audit'=>$langs->trans("UserSetInGroup"), 'newgroupid'=>$group);

				// Call trigger
				$result=$this->call_trigger('USER_MODIFY',$user);
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
				dol_syslog(get_class($this)."::SetInGroup ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Remove a user from a group
	 *
	 *  @param	int   $group       Id of group
	 *  @param  int		$entity      Entity
	 *  @param  int		$notrigger   Disable triggers
	 *  @return int  			     <0 if KO, >0 if OK
	 */
	function RemoveFromGroup($group, $entity, $notrigger=0)
	{
		global $conf,$langs,$user;

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."usergroup_user";
		$sql.= " WHERE fk_user  = ".$this->id;
		$sql.= " AND fk_usergroup = ".$group;
		$sql.= " AND entity = ".$entity;

		$result = $this->db->query($sql);
		if ($result)
		{
			if (! $error && ! $notrigger)
			{
				$this->oldgroupid=$group;    // deprecated. Remove this.
				$this->context = array('audit'=>$langs->trans("UserRemovedFromGroup"), 'oldgroupid'=>$group);

				// Call trigger
				$result=$this->call_trigger('USER_MODIFY',$user);
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
				$this->error=$interface->error;
				dol_syslog(get_class($this)."::RemoveFromGroup ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Return a link with photo
	 * 	Use this->id,this->photo
	 *
	 *	@param	int		$width			Width of image
	 *	@param	int		$height			Height of image
	 *  @param	string	$cssclass		Force a css class
	 * 	@param	string	$imagesize		'mini', 'small' or '' (original)
	 *	@return	string					String with URL link
	 */
	function getPhotoUrl($width, $height, $cssclass='', $imagesize='')
	{
		$result='';

		$result.='<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$this->id.'">';
		$result.=Form::showphoto('userphoto', $this, $width, $height, 0, $cssclass, $imagesize);
		$result.='</a>';

		return $result;
	}

	/**
	 *  Return a link to the user card (with optionaly the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpictoimg				Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
	 *	@param	string	$option						On what the link point to ('leave', 'nolink', )
	 *  @param  integer $infologin      			0=Add default info tooltip, 1=Add complete info tooltip, -1=No info tooltip
	 *  @param	integer	$notooltip					1=Disable tooltip on picto and name
	 *  @param	int		$maxlen						Max length of visible user name
	 *  @param	int		$hidethirdpartylogo			Hide logo of thirdparty if user is external user
	 *  @param  string  $mode               		''=Show firstname and lastname, 'firstname'=Show only firstname, 'login'=Show login
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	function getNomUrl($withpictoimg=0, $option='', $infologin=0, $notooltip=0, $maxlen=24, $hidethirdpartylogo=0, $mode='',$morecss='', $save_lastsearch_value=-1)
	{
		global $langs, $conf, $db, $hookmanager;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER) && $withpictoimg) $withpictoimg=0;

		$result=''; $label='';
		$link=''; $linkstart=''; $linkend='';

		if (! empty($this->photo))
		{
			$label.= '<div class="photointooltip">';
			$label.= Form::showphoto('userphoto', $this, 0, 60, 0, 'photowithmargin photologintooltip', 'small', 0, 1);	// Force height to 60 so we total height of tooltip can be calculated and collision can be managed
			$label.= '</div><div style="clear: both;"></div>';
		}

		// Info Login
		$label.= '<div class="centpercent">';
		$label.= '<u>' . $langs->trans("User") . '</u><br>';
		$label.= '<b>' . $langs->trans('Name') . ':</b> ' . $this->getFullName($langs,'');
		if (! empty($this->login))
			$label.= '<br><b>' . $langs->trans('Login') . ':</b> ' . $this->login;
		$label.= '<br><b>' . $langs->trans("EMail").':</b> '.$this->email;
		if (! empty($this->admin))
			$label.= '<br><b>' . $langs->trans("Administrator").'</b>: '.yn($this->admin);
		if (! empty($this->socid) )	// Add thirdparty for external users
		{
			$thirdpartystatic = new Societe($db);
			$thirdpartystatic->fetch($this->socid);
			if (empty($hidethirdpartylogo)) $companylink = ' '.$thirdpartystatic->getNomUrl(2, (($option == 'nolink')?'nolink':''));	// picto only of company
			$company=' ('.$langs->trans("Company").': '.$thirdpartystatic->name.')';
		}
		$type=($this->socid?$langs->trans("External").$company:$langs->trans("Internal"));
		$label.= '<br><b>' . $langs->trans("Type") . ':</b> ' . $type;
		$label.= '<br><b>' . $langs->trans("Status").'</b>: '.$this->getLibStatut(0);
		$label.='</div>';
		if ($infologin > 0)
		{
			$label.= '<br>';
			$label.= '<br><u>'.$langs->trans("Connection").'</u>';
			$label.= '<br><b>'.$langs->trans("IPAddress").'</b>: '.$_SERVER["REMOTE_ADDR"];
			if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $label.= '<br><b>'.$langs->trans("ConnectedOnMultiCompany").':</b> '.$conf->entity.' (user entity '.$this->entity.')';
			$label.= '<br><b>'.$langs->trans("AuthenticationMode").':</b> '.$_SESSION["dol_authmode"].(empty($dolibarr_main_demo)?'':' (demo)');
			$label.= '<br><b>'.$langs->trans("ConnectedSince").':</b> '.dol_print_date($this->datelastlogin,"dayhour",'tzuser');
			$label.= '<br><b>'.$langs->trans("PreviousConnexion").':</b> '.dol_print_date($this->datepreviouslogin,"dayhour",'tzuser');
			$label.= '<br><b>'.$langs->trans("CurrentTheme").':</b> '.$conf->theme;
			$label.= '<br><b>'.$langs->trans("CurrentMenuManager").':</b> '.$menumanager->name;
			$s=picto_from_langcode($langs->getDefaultLang());
			$label.= '<br><b>'.$langs->trans("CurrentUserLanguage").':</b> '.($s?$s.' ':'').$langs->getDefaultLang();
			$label.= '<br><b>'.$langs->trans("Browser").':</b> '.$conf->browser->name.($conf->browser->version?' '.$conf->browser->version:'').' ('.$_SERVER['HTTP_USER_AGENT'].')';
			$label.= '<br><b>'.$langs->trans("Layout").':</b> '.$conf->browser->layout;
			$label.= '<br><b>'.$langs->trans("Screen").':</b> '.$_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight'];
			if (! empty($conf->browser->phone)) $label.= '<br><b>'.$langs->trans("Phone").':</b> '.$conf->browser->phone;
			if (! empty($_SESSION["disablemodules"])) $label.= '<br><b>'.$langs->trans("DisabledModules").':</b> <br>'.join(', ',explode(',',$_SESSION["disablemodules"]));
		}
		if ($infologin < 0) $label='';

		$url = DOL_URL_ROOT.'/user/card.php?id='.$this->id;
		if ($option == 'leave') $url = DOL_URL_ROOT.'/holiday/list.php?id='.$this->id;

		if ($option != 'nolink')
		{
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
			if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
		}

		$linkstart='<a href="'.$url.'"';
		$linkclose="";
		if (empty($notooltip))
		{
			if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
			{
				$langs->load("users");
				$label=$langs->trans("ShowUser");
				$linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose.= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose.= ' class="classfortooltip'.($morecss?' '.$morecss:'').'"';

			/*
			 $hookmanager->initHooks(array('userdao'));
			 $parameters=array('id'=>$this->id);
			 $reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
			 if ($reshook > 0) $linkclose = $hookmanager->resPrint;
			 */
		}

		$linkstart.=$linkclose.'>';
		$linkend='</a>';

		//if ($withpictoimg == -1) $result.='<div class="nowrap">';
		$result.=(($option == 'nolink')?'':$linkstart);
		if ($withpictoimg)
		{
		  	$paddafterimage='';
			if (abs($withpictoimg) == 1) $paddafterimage='style="margin-right: 3px;"';
			// Only picto
			if ($withpictoimg > 0) $picto='<!-- picto user --><div class="inline-block nopadding userimg'.($morecss?' '.$morecss:'').'">'.img_object('', 'user', $paddafterimage.' '.($notooltip?'':'class="classfortooltip"'), 0, 0, $notooltip?0:1).'</div>';
			// Picto must be a photo
			else $picto='<!-- picto photo user --><div class="inline-block nopadding userimg'.($morecss?' '.$morecss:'').'"'.($paddafterimage?' '.$paddafterimage:'').'>'.Form::showphoto('userphoto', $this, 0, 0, 0, 'userphoto'.($withpictoimg==-3?'small':''), 'mini', 0, 1).'</div>';
			$result.=$picto;
		}
		if ($withpictoimg > -2 && $withpictoimg != 2)
		{
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result.='<div class="inline-block nopadding valignmiddle usertext'.((! isset($this->statut) || $this->statut)?'':' strikefordisabled').($morecss?' '.$morecss:'').'">';
			if ($mode == 'login') $result.=dol_trunc($this->login, $maxlen);
			else $result.=$this->getFullName($langs,'',($mode == 'firstname' ? 2 : -1),$maxlen);
			if (empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) $result.='</div>';
		}
		$result.=(($option == 'nolink')?'':$linkend);
		//if ($withpictoimg == -1) $result.='</div>';

		$result.=$companylink;

		global $action;
		$hookmanager->initHooks(array('userdao'));
		$parameters=array('id'=>$this->id, 'getnomurl'=>$result);
		$reshook=$hookmanager->executeHooks('getNomUrl',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
	}

	/**
	 *  Return clickable link of login (eventualy with picto)
	 *
	 *	@param	int		$withpicto		Include picto into link
	 *	@param	string	$option			Sur quoi pointe le lien
	 *	@return	string					Chaine avec URL
	 */
	function getLoginUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$linkstart = '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$this->id.'">';
		$linkend='</a>';

		if ($option == 'xxx')
		{
			$linkstart = '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$this->id.'">';
			$linkend='</a>';
		}

		$result.=$linkstart;
		if ($withpicto) $result.=img_object($langs->trans("ShowUser"), 'user', 'class="paddingright"');
		$result.=$this->login;
		$result.=$linkend;
		return $result;
	}

	/**
	 *  Return label of status of user (active, inactive)
	 *
	 *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return	string 			       Label of status
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *  Renvoi le libelle d'un statut donne
	 *
	 *  @param	int		$statut        	Id statut
	 *  @param  int		$mode          	0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
	 *  @return string 			       	Label of status
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;
		$langs->load('users');

		if ($mode == 0)
		{
			$prefix='';
			if ($statut == 1) return $langs->trans('Enabled');
			if ($statut == 0) return $langs->trans('Disabled');
		}
		if ($mode == 1)
		{
			if ($statut == 1) return $langs->trans('Enabled');
			if ($statut == 0) return $langs->trans('Disabled');
		}
		if ($mode == 2)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'),'statut4','class="pictostatus"').' '.$langs->trans('Enabled');
			if ($statut == 0) return img_picto($langs->trans('Disabled'),'statut5','class="pictostatus"').' '.$langs->trans('Disabled');
		}
		if ($mode == 3)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'),'statut4','class="pictostatus"');
			if ($statut == 0) return img_picto($langs->trans('Disabled'),'statut5','class="pictostatus"');
		}
		if ($mode == 4)
		{
			if ($statut == 1) return img_picto($langs->trans('Enabled'),'statut4','class="pictostatus"').' '.$langs->trans('Enabled');
			if ($statut == 0) return img_picto($langs->trans('Disabled'),'statut5','class="pictostatus"').' '.$langs->trans('Disabled');
		}
		if ($mode == 5)
		{
			if ($statut == 1) return $langs->trans('Enabled').' '.img_picto($langs->trans('Enabled'),'statut4','class="pictostatus"');
			if ($statut == 0) return $langs->trans('Disabled').' '.img_picto($langs->trans('Disabled'),'statut5','class="pictostatus"');
		}
	}


	/**
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param	array	$info		Info array loaded by _load_ldap_info
	 *	@param	int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *								1=Return parent (ou=xxx,dc=aaa,dc=bbb)
	 *								2=Return key only (RDN) (uid=qqq)
	 *	@return	string				DN
	 */
	function _load_ldap_dn($info,$mode=0)
	{
		global $conf;
		$dn='';
		if ($mode==0) $dn=$conf->global->LDAP_KEY_USERS."=".$info[$conf->global->LDAP_KEY_USERS].",".$conf->global->LDAP_USER_DN;
		if ($mode==1) $dn=$conf->global->LDAP_USER_DN;
		if ($mode==2) $dn=$conf->global->LDAP_KEY_USERS."=".$info[$conf->global->LDAP_KEY_USERS];
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
		$keymodified=false;

		// Object classes
		$info["objectclass"]=explode(',',$conf->global->LDAP_USER_OBJECT_CLASS);

		$this->fullname=$this->getFullName($langs);

		// Possible LDAP KEY (constname => varname)
		$ldapkey = array(
			'LDAP_FIELD_FULLNAME'	=> 'fullname',
			'LDAP_FIELD_NAME'		=> 'lastname',
			'LDAP_FIELD_FIRSTNAME'	=> 'firstname',
			'LDAP_FIELD_LOGIN'		=> 'login',
			'LDAP_FIELD_LOGIN_SAMBA'	=> 'login',
			'LDAP_FIELD_PHONE'		=> 'office_phone',
			'LDAP_FIELD_MOBILE'		=> 'user_mobile',
			'LDAP_FIELD_FAX'			=> 'office_fax',
			'LDAP_FIELD_MAIL'		=> 'email',
			'LDAP_FIELD_SID'			=> 'ldap_sid',
			'LDAP_FIELD_SKYPE'		=> 'skype'
		);

		// Champs
		foreach ($ldapkey as $constname => $varname)
		{
			if (! empty($this->$varname) && ! empty($conf->global->$constname))
			{
				$info[$conf->global->$constname] = $this->$varname;

				// Check if it is the LDAP key and if its value has been changed
				if (! empty($conf->global->LDAP_KEY_USERS) && $conf->global->LDAP_KEY_USERS == $conf->global->$constname)
				{
					if (! empty($this->oldcopy) && $this->$varname != $this->oldcopy->$varname) $keymodified=true; // For check if LDAP key has been modified
				}
			}
		}
		if ($this->address && ! empty($conf->global->LDAP_FIELD_ADDRESS))			$info[$conf->global->LDAP_FIELD_ADDRESS] = $this->address;
		if ($this->zip && ! empty($conf->global->LDAP_FIELD_ZIP))					$info[$conf->global->LDAP_FIELD_ZIP] = $this->zip;
		if ($this->town && ! empty($conf->global->LDAP_FIELD_TOWN))					$info[$conf->global->LDAP_FIELD_TOWN] = $this->town;
		if ($this->note_public && ! empty($conf->global->LDAP_FIELD_DESCRIPTION))	$info[$conf->global->LDAP_FIELD_DESCRIPTION] = dol_string_nohtmltag($this->note_public, 2);
		if ($this->socid > 0)
		{
			$soc = new Societe($this->db);
			$soc->fetch($this->socid);

			$info[$conf->global->LDAP_FIELD_COMPANY] = $soc->name;
			if ($soc->client == 1)      $info["businessCategory"] = "Customers";
			if ($soc->client == 2)      $info["businessCategory"] = "Prospects";
			if ($soc->fournisseur == 1) $info["businessCategory"] = "Suppliers";
		}

		// When password is modified
		if (! empty($this->pass))
		{
			if (! empty($conf->global->LDAP_FIELD_PASSWORD))				$info[$conf->global->LDAP_FIELD_PASSWORD] = $this->pass;	// this->pass = mot de passe non crypte
			if (! empty($conf->global->LDAP_FIELD_PASSWORD_CRYPTED))		$info[$conf->global->LDAP_FIELD_PASSWORD_CRYPTED] = dol_hash($this->pass, 4); // Create OpenLDAP MD5 password (TODO add type of encryption)
		}
		// Set LDAP password if possible
		else if ($conf->global->LDAP_SERVER_PROTOCOLVERSION !== '3') // If ldap key is modified and LDAPv3 we use ldap_rename function for avoid lose encrypt password
		{
			if (! empty($conf->global->DATABASE_PWD_ENCRYPTED))
			{
				// Just for the default MD5 !
				if (empty($conf->global->MAIN_SECURITY_HASH_ALGO))
				{
					if ($this->pass_indatabase_crypted && ! empty($conf->global->LDAP_FIELD_PASSWORD_CRYPTED))	{
						$info[$conf->global->LDAP_FIELD_PASSWORD_CRYPTED] = dol_hash($this->pass_indatabase_crypted, 5); // Create OpenLDAP MD5 password from Dolibarr MD5 password
					}
				}
			}
			// Use $this->pass_indatabase value if exists
			else if (! empty($this->pass_indatabase))
			{
				if (! empty($conf->global->LDAP_FIELD_PASSWORD))				$info[$conf->global->LDAP_FIELD_PASSWORD] = $this->pass_indatabase;	// $this->pass_indatabase = mot de passe non crypte
				if (! empty($conf->global->LDAP_FIELD_PASSWORD_CRYPTED))		$info[$conf->global->LDAP_FIELD_PASSWORD_CRYPTED] = dol_hash($this->pass_indatabase, 4); // md5 for OpenLdap TODO add type of encryption
			}
		}

		if ($conf->global->LDAP_SERVER_TYPE == 'egroupware')
		{
			$info["objectclass"][4] = "phpgwContact"; // compatibilite egroupware

			$info['uidnumber'] = $this->id;

			$info['phpgwTz']      = 0;
			$info['phpgwMailType'] = 'INTERNET';
			$info['phpgwMailHomeType'] = 'INTERNET';

			$info["phpgwContactTypeId"] = 'n';
			$info["phpgwContactCatId"] = 0;
			$info["phpgwContactAccess"] = "public";

			if (dol_strlen($this->egroupware_id) == 0)
			{
				$this->egroupware_id = 1;
			}

			$info["phpgwContactOwner"] = $this->egroupware_id;

			if ($this->email) $info["rfc822Mailbox"] = $this->email;
			if ($this->phone_mobile) $info["phpgwCellTelephoneNumber"] = $this->phone_mobile;
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
		global $user,$langs;

		$now=dol_now();

		// Initialise parametres
		$this->id=0;
		$this->ref = 'SPECIMEN';
		$this->specimen=1;

		$this->lastname='DOLIBARR';
		$this->firstname='SPECIMEN';
		$this->gender='man';
		$this->note='This is a note';
		$this->email='email@specimen.com';
		$this->skype='tom.hanson';
		$this->office_phone='0999999999';
		$this->office_fax='0999999998';
		$this->user_mobile='0999999997';
		$this->admin=0;
		$this->login='dolibspec';
		$this->pass='dolibspec';
		//$this->pass_indatabase='dolibspec';									Set after a fetch
		//$this->pass_indatabase_crypted='e80ca5a88c892b0aaaf7e154853bccab';	Set after a fetch
		$this->datec=$now;
		$this->datem=$now;

		$this->datelastlogin=$now;
		$this->datepreviouslogin=$now;
		$this->statut=1;

		//$this->societe_id = 1;	For external users
		//$this->contact_id = 1;	For external users
		$this->entity = 1;
	}

	/**
	 *  Load info of user object
	 *
	 *  @param  int		$id     Id of user to load
	 *  @return	void
	 */
	function info($id)
	{
		$sql = "SELECT u.rowid, u.login as ref, u.datec,";
		$sql.= " u.tms as date_modification, u.entity";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE u.rowid = ".$id;

		$result=$this->db->query($sql);
		if ($result)
		{
			if ($this->db->num_rows($result))
			{
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->ref			     = (! $obj->ref) ? $obj->rowid : $obj->ref;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->entity            = $obj->entity;
			}

			$this->db->free($result);

		}
		else
		{
			dol_print_error($this->db);
		}
	}


	/**
	 *    Return number of mass Emailing received by this contacts with its email
	 *
	 *    @return       int     Number of EMailings
	 */
	function getNbOfEMailings()
	{
		$sql = "SELECT count(mc.email) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."mailing_cibles as mc";
		$sql.= " WHERE mc.email = '".$this->db->escape($this->email)."'";
		$sql.= " AND mc.statut NOT IN (-1,0)";      // -1 erreur, 0 non envoye, 1 envoye avec succes

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$nb=$obj->nb;

			$this->db->free($resql);
			return $nb;
		}
		else
		{
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Return number of existing users
	 *
	 *  @param	string	$limitTo	Limit to '' or 'active'
	 *  @param	string	$option		'superadmin' = return for entity 0 only
	 *  @param	int		$admin		Filter on admin tag
	 *  @return int  				Number of users
	 */
	function getNbOfUsers($limitTo, $option='', $admin=-1)
	{
		global $conf;

		$sql = "SELECT count(rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."user";
		if ($option == 'superadmin')
		{
			$sql.= " WHERE entity = 0";
			if ($admin >= 0) $sql.= " AND admin = ".$admin;
		}
		else
		{
			$sql.=" WHERE entity IN (".getEntity('user',0).")";
			if ($limitTo == 'active') $sql.= " AND statut = 1";
			if ($admin >= 0) $sql.= " AND admin = ".$admin;
		}

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$obj = $this->db->fetch_object($resql);
			$nb=$obj->nb;

			$this->db->free($resql);
			return $nb;
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Update user using data from the LDAP
	 *
	 *  @param	ldapuser	$ldapuser	Ladp User
	 *
	 *  @return int  				<0 if KO, >0 if OK
	 */
	function update_ldap2dolibarr(&$ldapuser)
	{
		// TODO: Voir pourquoi le update met à jour avec toutes les valeurs vide (global $user écrase ?)
		global $user, $conf;

		$this->firstname=$ldapuser->{$conf->global->LDAP_FIELD_FIRSTNAME};
		$this->lastname=$ldapuser->{$conf->global->LDAP_FIELD_NAME};
		$this->login=$ldapuser->{$conf->global->LDAP_FIELD_LOGIN};
		$this->pass=$ldapuser->{$conf->global->LDAP_FIELD_PASSWORD};
		$this->pass_indatabase_crypted=$ldapuser->{$conf->global->LDAP_FIELD_PASSWORD_CRYPTED};

		$this->office_phone=$ldapuser->{$conf->global->LDAP_FIELD_PHONE};
		$this->user_mobile=$ldapuser->{$conf->global->LDAP_FIELD_MOBILE};
		$this->office_fax=$ldapuser->{$conf->global->LDAP_FIELD_FAX};
		$this->email=$ldapuser->{$conf->global->LDAP_FIELD_MAIL};
		$this->skype=$ldapuser->{$conf->global->LDAP_FIELD_SKYPE};
		$this->ldap_sid=$ldapuser->{$conf->global->LDAP_FIELD_SID};

		$this->job=$ldapuser->{$conf->global->LDAP_FIELD_TITLE};
		$this->note=$ldapuser->{$conf->global->LDAP_FIELD_DESCRIPTION};

		$result = $this->update($user);

		dol_syslog(get_class($this)."::update_ldap2dolibarr result=".$result, LOG_DEBUG);

		return $result;
	}


	/**
	 * Return and array with all instanciated first level children users of current user
	 *
	 * @return	void
	 * @see getAllChildIds
	 */
	function get_children()
	{
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."user";
		$sql.= " WHERE fk_user = ".$this->id;

		dol_syslog(get_class($this)."::get_children result=".$result, LOG_DEBUG);
		$res  = $this->db->query($sql);
		if ($res)
		{
			$users = array ();
			while ($rec = $this->db->fetch_array($res))
			{
				$user = new User($this->db);
				$user->fetch($rec['rowid']);
				$users[] = $user;
			}
			return $users;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 * 	Load this->parentof that is array(id_son=>id_parent, ...)
	 *
	 *	@return		int		<0 if KO, >0 if OK
	 */
	private function load_parentof()
	{
		global $conf;

		$this->parentof=array();

		// Load array[child]=parent
		$sql = "SELECT fk_user as id_parent, rowid as id_son";
		$sql.= " FROM ".MAIN_DB_PREFIX."user";
		$sql.= " WHERE fk_user <> 0";
		$sql.= " AND entity IN (".getEntity('user').")";

		dol_syslog(get_class($this)."::load_parentof", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			while ($obj= $this->db->fetch_object($resql))
			{
				$this->parentof[$obj->id_son]=$obj->id_parent;
			}
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * 	Reconstruit l'arborescence hierarchique des users sous la forme d'un tableau
	 *	Set and return this->users that is an array sorted according to tree with arrays of:
	 *				id = id user
	 *				lastname
	 *				firstname
	 *				fullname = nom avec chemin complet du user
	 *				fullpath = chemin complet compose des id: "_grandparentid_parentid_id"
	 *
	 *  @param      int		$deleteafterid      Removed all users including the leaf $deleteafterid (and all its child) in user tree.
	 *  @param		string	$filter				SQL filter on users
	 *	@return		array		      		  	Array of users $this->users. Note: $this->parentof is also set.
	 */
	function get_full_tree($deleteafterid=0, $filter='')
	{
		global $conf, $user;
		global $hookmanager;

		// Actions hooked (by external module)
		$hookmanager->initHooks(array('userdao'));

		$this->users = array();

		// Init this->parentof that is array(id_son=>id_parent, ...)
		$this->load_parentof();

		// Init $this->users array
		$sql = "SELECT DISTINCT u.rowid, u.firstname, u.lastname, u.fk_user, u.fk_soc, u.login, u.email, u.gender, u.admin, u.statut, u.photo, u.entity";	// Distinct reduce pb with old tables with duplicates
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		// Add fields from hooks
		$parameters=array();
		$reshook=$hookmanager->executeHooks('printUserListWhere',$parameters);    // Note that $action and $object may have been modified by hook
		if ($reshook > 0) {
			$sql.=$hookmanager->resPrint;
		} else {
			$sql.= " WHERE u.entity IN (".getEntity('user').")";
		}
		if ($filter) $sql.=" AND ".$filter;

		dol_syslog(get_class($this)."::get_full_tree get user list", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$i=0;
			while ($obj = $this->db->fetch_object($resql))
			{
				$this->users[$obj->rowid]['rowid'] = $obj->rowid;
				$this->users[$obj->rowid]['id'] = $obj->rowid;
				$this->users[$obj->rowid]['fk_user'] = $obj->fk_user;
				$this->users[$obj->rowid]['fk_soc'] = $obj->fk_soc;
				$this->users[$obj->rowid]['firstname'] = $obj->firstname;
				$this->users[$obj->rowid]['lastname'] = $obj->lastname;
				$this->users[$obj->rowid]['login'] = $obj->login;
				$this->users[$obj->rowid]['statut'] = $obj->statut;
				$this->users[$obj->rowid]['entity'] = $obj->entity;
				$this->users[$obj->rowid]['email'] = $obj->email;
				$this->users[$obj->rowid]['gender'] = $obj->gender;
				$this->users[$obj->rowid]['admin'] = $obj->admin;
				$this->users[$obj->rowid]['photo'] = $obj->photo;
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
			return -1;
		}

		// We add the fullpath property to each elements of first level (no parent exists)
		dol_syslog(get_class($this)."::get_full_tree call to build_path_from_id_user", LOG_DEBUG);
		foreach($this->users as $key => $val)
		{
			$result = $this->build_path_from_id_user($key,0);	// Process a branch from the root user key (this user has no parent)
			if ($result < 0)
			{
				$this->error='ErrorLoopInHierarchy';
				return -1;
			}
		}

		// Exclude leaf including $deleteafterid from tree
		if ($deleteafterid)
		{
			//print "Look to discard user ".$deleteafterid."\n";
			$keyfilter1='^'.$deleteafterid.'$';
			$keyfilter2='_'.$deleteafterid.'$';
			$keyfilter3='^'.$deleteafterid.'_';
			$keyfilter4='_'.$deleteafterid.'_';
			foreach($this->users as $key => $val)
			{
				if (preg_match('/'.$keyfilter1.'/',$val['fullpath']) || preg_match('/'.$keyfilter2.'/',$val['fullpath'])
					|| preg_match('/'.$keyfilter3.'/',$val['fullpath']) || preg_match('/'.$keyfilter4.'/',$val['fullpath']))
				{
					unset($this->users[$key]);
				}
			}
		}

		dol_syslog(get_class($this)."::get_full_tree dol_sort_array", LOG_DEBUG);
		$this->users=dol_sort_array($this->users, 'fullname', 'asc', true, false);

		//var_dump($this->users);

		return $this->users;
	}

	/**
	 * 	Return list of all child users id in herarchy (all sublevels).
	 *  Note: Calling this function also reset full list of users into $this->users.
	 *
	 *  @param      int      $addcurrentuser    1=Add also current user id to the list.
	 *	@return		array		      		  	Array of user id lower than user (all levels under user). This overwrite this->users.
	 *  @see get_children
	 */
	function getAllChildIds($addcurrentuser=0)
	{
		$childids=array();

		if (isset($this->cache_childids[$this->id]))
		{
			$childids = $this->cache_childids[$this->id];
		}
		else
		{
			// Init this->users
			$this->get_full_tree();

			$idtoscan=$this->id;

			dol_syslog("Build childid for id = ".$idtoscan);
			foreach($this->users as $id => $val)
			{
				//var_dump($val['fullpath']);
				if (preg_match('/_'.$idtoscan.'_/', $val['fullpath'])) $childids[$val['id']]=$val['id'];
			}
		}
		$this->cache_childids[$this->id] = $childids;

		if ($addcurrentuser) $childids[$this->id]=$this->id;

		return $childids;
	}

	/**
	 *	For user id_user and its childs available in this->users, define property fullpath and fullname.
	 *  Function called by get_full_tree().
	 *
	 * 	@param		int		$id_user		id_user entry to update
	 * 	@param		int		$protection		Deep counter to avoid infinite loop (no more required, a protection is added with array useridfound)
	 *	@return		int                     < 0 if KO (infinit loop), >= 0 if OK
	 */
	function build_path_from_id_user($id_user,$protection=0)
	{
		dol_syslog(get_class($this)."::build_path_from_id_user id_user=".$id_user." protection=".$protection, LOG_DEBUG);

		if (! empty($this->users[$id_user]['fullpath']))
		{
			// Already defined
			dol_syslog(get_class($this)."::build_path_from_id_user fullpath and fullname already defined", LOG_WARNING);
			return 0;
		}

		// Define fullpath and fullname
		$this->users[$id_user]['fullpath'] = '_'.$id_user;
		$this->users[$id_user]['fullname'] = $this->users[$id_user]['lastname'];
		$i=0; $cursor_user=$id_user;

		$useridfound=array($id_user);
		while (! empty($this->parentof[$cursor_user]))
		{
			if (in_array($this->parentof[$cursor_user], $useridfound))
			{
				dol_syslog("The hierarchy of user has a recursive loop", LOG_WARNING);
				return -1;     // Should not happen. Protection against looping hierarchy
			}
			$useridfound[]=$this->parentof[$cursor_user];
			$this->users[$id_user]['fullpath'] = '_'.$this->parentof[$cursor_user].$this->users[$id_user]['fullpath'];
			$this->users[$id_user]['fullname'] = $this->users[$this->parentof[$cursor_user]]['lastname'].' >> '.$this->users[$id_user]['fullname'];
			$i++; $cursor_user=$this->parentof[$cursor_user];
		}

		// We count number of _ to have level
		$this->users[$id_user]['level']=dol_strlen(preg_replace('/[^_]/i','',$this->users[$id_user]['fullpath']));

		return 1;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'user'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}


	/**
	 *      Charge indicateurs this->nb pour le tableau de bord
	 *
	 *      @return     int         <0 if KO, >0 if OK
	 */
	function load_state_board()
	{
		global $conf;

		$this->nb=array();

		$sql = "SELECT count(u.rowid) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql.= " WHERE u.statut > 0";
		//$sql.= " AND employee != 0";
		$sql.= " AND u.entity IN (".getEntity('user').")";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			while ($obj=$this->db->fetch_object($resql))
			{
				$this->nb["users"]=$obj->nb;
			}
			$this->db->free($resql);
			return 1;
		}
		else
		{
			dol_print_error($this->db);
			$this->error=$this->db->error();
			return -1;
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 * 	@param	    string		$modele			Force model to use ('' to not force)
	 * 	@param		Translate	$outputlangs	Object langs to use for output
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
         *  @param   null|array  $moreparams     Array to provide more information
	 * 	@return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0, $moreparams=null)
	{
		global $conf,$user,$langs;

		$langs->load("user");

		// Positionne le modele sur le nom du modele a utiliser
		if (! dol_strlen($modele))
		{
			if (! empty($conf->global->USER_ADDON_PDF))
			{
				$modele = $conf->global->USER_ADDON_PDF;
			}
			else
			{
				$modele = 'bluesky';
			}
		}

		$modelpath = "core/modules/user/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	/**
	 *  Return property of user from its id
	 *
	 *  @param	int		$rowid      id of contact
	 *  @param  string	$mode       'email' or 'mobile'
	 *  @return string  			Email of user with format: "Full name <email>"
	 */
	function user_get_property($rowid,$mode)
	{
		$user_property='';

		if (empty($rowid)) return '';

		$sql = "SELECT rowid, email, user_mobile, civility, lastname, firstname";
		$sql.= " FROM ".MAIN_DB_PREFIX."user";
		$sql.= " WHERE rowid = '".$rowid."'";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$nump = $this->db->num_rows($resql);

			if ($nump)
			{
				$obj = $this->db->fetch_object($resql);

				if ($mode == 'email') $user_property = dolGetFirstLastname($obj->firstname, $obj->lastname)." <".$obj->email.">";
				else if ($mode == 'mobile') $user_property = $obj->user_mobile;
			}
			return $user_property;
		}
		else
		{
			dol_print_error($this->db);
		}
	}

	/**
	 *	Load all objects into $this->users
	 *
	 *  @param	string		$sortorder    sort order
	 *  @param	string		$sortfield    sort field
	 *  @param	int			$limit		  limit page
	 *  @param	int			$offset    	  page
	 *  @param	array		$filter    	  filter output
	 *  @return int          	<0 if KO, >0 if OK
	 */
	function fetchAll($sortorder='', $sortfield='', $limit=0, $offset=0, $filter=array())
	{
		global $conf;

		$sql="SELECT t.rowid";
		$sql.= ' FROM '.MAIN_DB_PREFIX .$this->table_element.' as t ';
		$sql.= " WHERE 1";

		//Manage filter
		if (!empty($filter)){
			foreach($filter as $key => $value) {
				if (strpos($key,'date')) {
					$sql.= ' AND '.$key.' = \''.$this->db->idate($value).'\'';
				}
				elseif ($key=='customsql') {
					$sql.= ' AND '.$value;
				} else {
					$sql.= ' AND '.$key.' LIKE \'%'.$value.'%\'';
				}
			}
		}
		$sql.= $this->db->order($sortfield,$sortorder);
		if ($limit) $sql.= $this->db->plimit($limit+1,$offset);

		dol_syslog(get_class($this)."::".__METHOD__, LOG_DEBUG);

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$this->users=array();
			$num = $this->db->num_rows($resql);
			if ($num)
			{
				while ($obj = $this->db->fetch_object($resql))
				{
					$line = new self($this->db);
					$result = $line->fetch($obj->rowid);
					if ($result>0 && !empty($line->id)) {
						$this->users[$obj->rowid] = clone $line;
					}
				}
				$this->db->free($resql);
			}
			return $num;
		}
		else
		{
			$this->errors[] = $this->db->lasterror();
			return -1;
		}

	}

}

