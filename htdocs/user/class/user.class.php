<?php
/* Copyright (c) 2002-2007	Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (c) 2002-2003	Jean-Louis Bergamo		<jlb@j1b.org>
 * Copyright (c) 2004-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2004		Sebastien Di Cintio		<sdicintio@ressource-toi.org>
 * Copyright (C) 2004		Benoit Mortier			<benoit.mortier@opensides.be>
 * Copyright (C) 2005-2024	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2005		Lionel Cousteix			<etm_ltd@tiscali.co.uk>
 * Copyright (C) 2011		Herve Prot				<herve.prot@symeos.com>
 * Copyright (C) 2013-2019	Philippe Grand			<philippe.grand@atoo-net.com>
 * Copyright (C) 2013-2015	Alexandre Spangaro		<aspangaro@open-dsi.fr>
 * Copyright (C) 2015		Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2018		charlene Benke			<charlie@patas-monkey.com>
 * Copyright (C) 2018-2021	Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2019-2024	Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2019		Abbes Bahfir			<dolipar@dolipar.org>
 * Copyright (C) 2024		MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Lenin Rivas				<lenin.rivas777@gmail.com>
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
 *  \file       htdocs/user/class/user.class.php
 *	\brief      File of class to manage users
 *  \ingroup	core
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/usergroup.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonpeople.class.php';


/**
 *	Class to manage Dolibarr users
 */
class User extends CommonObject
{
	use CommonPeople;

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'user';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'user';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_user';

	/**
	 * @var string picto
	 */
	public $picto = 'user';

	public $id = 0;

	/**
	 * @var static old copy of User
	 */
	public $oldcopy;

	/**
	 * @var int
	 * @deprecated
	 * @see $status
	 */
	public $statut;

	public $status;

	/**
	 * @var string		Open ID
	 */
	public $openid;

	public $ldap_sid;
	public $search_sid;
	public $employee;
	public $civility_code;

	/**
	 * @var string fullname
	 */
	public $fullname;

	/**
	 * @var string|int<-1,-1> gender (man|woman|other)
	 */
	public $gender;

	public $birth;

	/**
	 * @var string email
	 */
	public $email;

	/**
	 * @var string email
	 */
	public $email_oauth2;

	/**
	 * @var string personal email
	 */
	public $personal_email;

	/**
	 * @var array array of socialnetwo18dprks
	 */
	public $socialnetworks;

	/**
	 * @var string job position
	 */
	public $job;

	/**
	 * @var string user signature
	 */
	public $signature;

	/**
	 * @var string office phone
	 */
	public $office_phone;

	/**
	 * @var string office fax
	 */
	public $office_fax;

	/**
	 * @var string phone mobile
	 */
	public $user_mobile;

	/**
	 * @var string personal phone mobile
	 */
	public $personal_mobile;

	/**
	 * @var int 1 if admin 0 if standard user
	 */
	public $admin;

	/**
	 * @var string user login
	 */
	public $login;

	/**
	 * @var string user apikey
	 */
	public $api_key;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var string Clear password in memory
	 */
	public $pass;

	/**
	 * @var string Encrypted password in memory
	 */
	public $pass_crypted;

	/**
	 * @var string Clear password in database (defined if DATABASE_PWD_ENCRYPTED=0)
	 */
	public $pass_indatabase;

	/**
	 * @var string Encrypted password in database (always defined)
	 */
	public $pass_indatabase_crypted;

	/**
	 * @var string Temporary password
	 */
	public $pass_temp;

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
	 * @var int If this is defined, it is an external user
	 */
	public $socid;

	/**
	 * @var int If this is defined, it is a user created from a contact
	 */
	public $contact_id;

	/**
	 * @var int ID
	 */
	public $fk_member;

	/**
	 * @var int User ID of supervisor
	 */
	public $fk_user;

	/**
	 * @var int User ID of expense validator
	 */
	public $fk_user_expense_validator;

	/**
	 * @var int User ID of holidays validator
	 */
	public $fk_user_holiday_validator;

	/**
	 * @string clicktodial url
	 */
	public $clicktodial_url;

	/**
	 * @var string clicktodial login
	 */
	public $clicktodial_login;

	/**
	 * @var string clicktodial password
	 */
	public $clicktodial_password;

	/**
	 * @var string clicktodial poste
	 */
	public $clicktodial_poste;

	/**
	 * @var int 	0 by default, 1 if click to dial data were already loaded for this user
	 */
	public $clicktodial_loaded;


	public $datelastlogin;
	public $datepreviouslogin;
	public $flagdelsessionsbefore;
	public $iplastlogin;
	public $ippreviouslogin;
	public $datestartvalidity;
	public $dateendvalidity;

	/**
	 * @var string photo filename
	 */
	public $photo;

	/**
	 * @var string default language
	 */
	public $lang;

	/**
	 * @var stdClass Class of permissions user->rights->permx
	 */
	public $rights;

	/**
	 * @var int  All permissions are loaded
	 */
	public $all_permissions_are_loaded;

	/**
	 * @var int Number of rights granted to the user. Value loaded after a getrights().
	 */
	public $nb_rights;

	/**
	 * @var array	To store list of groups of user (used by API /info for example)
	 */
	public $user_group_list;

	/**
	 * @var array Cache array of already loaded permissions
	 */
	private $_tab_loaded = array();

	/**
	 * @var stdClass To store personal config
	 */
	public $conf;

	public $default_values; // To store default values for user. Loaded by loadDefaultValues().

	public $lastsearch_values_tmp; // To store current search criteria for user
	public $lastsearch_values; // To store last saved search criteria for user

	/**
	 *	@var array<int,User>|array<int,array{rowid:int,id:int,fk_user:int,fk_soc:int,firstname:string,lastname:string,login:string,statut:int,entity:int,email:string,gender:string|int<-1,-1>,admin:int<0,1>,photo:string,fullpath:string,fullname:string,level:int}>  Array of User (filled from fetchAll) or Array with hierarchy of user information (filled with get_full_tree()
	 */
	public $users = array();
	public $parentof; // To store an array of all parents for all ids.
	private $cache_childids; // Cache array of already loaded children

	public $accountancy_code; // Accountancy code in prevision of the complete accountancy module

	public $thm; // Average cost of employee - Used for valuation of time spent
	public $tjm; // Average cost of employee

	public $salary; // Monthly salary       - Denormalized value from llx_user_employment
	public $salaryextra; // Monthly salary extra - Denormalized value from llx_user_employment
	public $weeklyhours; // Weekly hours         - Denormalized value from llx_user_employment

	/**
	 * @var string Define background color for user in agenda
	 */
	public $color;

	public $dateemployment; // Define date of employment by company
	public $dateemploymentend; // Define date of employment end by company

	public $default_c_exp_tax_cat;

	/**
	 * @var string ref for employee
	 */
	public $ref_employee;

	/**
	 * @var string national registration number
	 */
	public $national_registration_number;

	public $default_range;

	/**
	 *@var int id of warehouse
	 */
	public $fk_warehouse;

	/**
	 *@var int id of establishment
	 */
	public $fk_establishment;

	/**
	 *@var string label of establishment
	 */
	public $label_establishment;

	/**
	 * @var int egroupware id
	 */
	//private $egroupware_id;

	/**
	 * @var array<int>		Entity in table llx_user_group
	 * @deprecated			Seems not used.
	 */
	public $usergroup_entity;

	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => 1, 'visible' => -2, 'notnull' => 1, 'index' => 1, 'position' => 1, 'comment' => 'Id'),
		'lastname' => array('type' => 'varchar(50)', 'label' => 'Lastname', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 20, 'searchall' => 1),
		'firstname' => array('type' => 'varchar(50)', 'label' => 'Firstname', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 10, 'searchall' => 1),
		'ref_employee' => array('type' => 'varchar(50)', 'label' => 'RefEmployee', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 30, 'searchall' => 1),
		'national_registration_number' => array('type' => 'varchar(50)', 'label' => 'NationalRegistrationNumber', 'enabled' => 1, 'visible' => 1, 'notnull' => 1, 'showoncombobox' => 1, 'index' => 1, 'position' => 40, 'searchall' => 1)
	);

	const STATUS_DISABLED = 0;
	const STATUS_ENABLED = 1;

	/**
	 *    Constructor of the class
	 *
	 *    @param   DoliDB  $db     Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;
		// User preference
		$this->clicktodial_loaded = 0;

		// For cache usage
		$this->all_permissions_are_loaded = 0;
		$this->nb_rights = 0;

		// Force some default values
		$this->admin = 0;
		$this->employee = 1;

		$this->conf = new stdClass();
		$this->rights = new stdClass();
		$this->rights->user = new stdClass();
		$this->rights->user->user = new stdClass();
		$this->rights->user->self = new stdClass();
		$this->rights->user->user_advance = new stdClass();
		$this->rights->user->self_advance = new stdClass();
		$this->rights->user->group_advance = new stdClass();
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
	 *  @param	string	$email       		If defined, email to used for search
	 *  @param	int		$fk_socpeople		If defined, id of contact for search
	 *  @param	int		$use_email_oauth2	1=Use also email_oauth2 to fetch on email
	 * 	@return	int							Return integer <0 if KO, 0 not found, >0 if OK
	 */
	public function fetch($id = 0, $login = '', $sid = '', $loadpersonalconf = 0, $entity = -1, $email = '', $fk_socpeople = 0, $use_email_oauth2 = 0)
	{
		global $conf, $user;

		// Clean parameters
		$login = trim($login);

		// Get user
		$sql = "SELECT u.rowid, u.lastname, u.firstname, u.employee, u.gender, u.civility as civility_code, u.birth, u.job,";
		$sql .= " u.email, u.email_oauth2, u.personal_email,";
		$sql .= " u.socialnetworks,";
		$sql .= " u.signature, u.office_phone, u.office_fax, u.user_mobile, u.personal_mobile,";
		$sql .= " u.address, u.zip, u.town, u.fk_state as state_id, u.fk_country as country_id,";
		$sql .= " u.admin, u.login, u.note_private, u.note_public,";
		$sql .= " u.pass, u.pass_crypted, u.pass_temp, u.api_key,";
		$sql .= " u.fk_soc, u.fk_socpeople, u.fk_member, u.fk_user, u.ldap_sid, u.fk_user_expense_validator, u.fk_user_holiday_validator,";
		$sql .= " u.statut as status, u.lang, u.entity,";
		$sql .= " u.datec as datec,";
		$sql .= " u.tms as datem,";
		$sql .= " u.datelastlogin as datel,";
		$sql .= " u.datepreviouslogin as datep,";
		$sql .= " u.flagdelsessionsbefore,";
		$sql .= " u.iplastlogin,";
		$sql .= " u.ippreviouslogin,";
		$sql .= " u.datelastpassvalidation,";
		$sql .= " u.datestartvalidity,";
		$sql .= " u.dateendvalidity,";
		$sql .= " u.photo as photo,";
		$sql .= " u.openid as openid,";
		$sql .= " u.accountancy_code,";
		$sql .= " u.thm,";
		$sql .= " u.tjm,";
		$sql .= " u.salary,";
		$sql .= " u.salaryextra,";
		$sql .= " u.weeklyhours,";
		$sql .= " u.color,";
		$sql .= " u.dateemployment, u.dateemploymentend,";
		$sql .= " u.fk_warehouse,";
		$sql .= " u.ref_ext,";
		$sql .= " u.default_range, u.default_c_exp_tax_cat,"; // Expense report default mode
		$sql .= " u.national_registration_number,";
		$sql .= " u.ref_employee,";
		$sql .= " c.code as country_code, c.label as country,";
		$sql .= " d.code_departement as state_code, d.nom as state,";
		$sql .= " s.label as label_establishment, u.fk_establishment";
		$sql .= " FROM ".$this->db->prefix()."user as u";
		$sql .= " LEFT JOIN ".$this->db->prefix()."c_country as c ON u.fk_country = c.rowid";
		$sql .= " LEFT JOIN ".$this->db->prefix()."c_departements as d ON u.fk_state = d.rowid";
		$sql .= " LEFT JOIN ".$this->db->prefix()."establishment as s ON u.fk_establishment = s.rowid";

		if ($entity < 0) {
			if ((!isModEnabled('multicompany') || !getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) && (!empty($user->entity))) {
				$sql .= " WHERE u.entity IN (0, ".((int) $conf->entity).")";
			} else {
				$sql .= " WHERE u.entity IS NOT NULL"; // multicompany is on in transverse mode or user making fetch is on entity 0, so user is allowed to fetch anywhere into database
			}
		} else {
			// The fetch was forced on an entity
			if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
				$sql .= " WHERE u.entity IS NOT NULL"; // multicompany is on in transverse mode or user making fetch is on entity 0, so user is allowed to fetch anywhere into database
			} else {
				$sql .= " WHERE u.entity IN (0, ".((int) (($entity != '' && $entity >= 0) ? $entity : $conf->entity)).")"; // search in entity provided in parameter
			}
		}

		if ($sid) {
			// permet une recherche du user par son SID ActiveDirectory ou Samba
			$sql .= " AND (u.ldap_sid = '".$this->db->escape($sid)."' OR u.login = '".$this->db->escape($login)."')";
		} elseif ($login) {
			$sql .= " AND u.login = '".$this->db->escape($login)."'";
		} elseif ($email) {
			$sql .= " AND (u.email = '".$this->db->escape($email)."'";
			if ($use_email_oauth2) {
				$sql .= " OR u.email_oauth2 = '".$this->db->escape($email)."'";
			}
			$sql .= ")";
		} elseif ($fk_socpeople > 0) {
			$sql .= " AND u.fk_socpeople = ".((int) $fk_socpeople);
		} else {
			$sql .= " AND u.rowid = ".((int) $id);
		}
		$sql .= " ORDER BY u.entity ASC"; // Avoid random result when there is 2 login in 2 different entities

		if ($sid) {
			// permet une recherche du user par son SID ActiveDirectory ou Samba
			$sql .= ' '.$this->db->plimit(1);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num > 1) {
				$this->error = "USERDUPLICATEFOUND";
				dol_syslog(get_class($this)."::fetch more than 1 user found", LOG_WARNING);

				$this->db->free($resql);
				return 0;
			}

			$obj = $this->db->fetch_object($resql);
			if ($obj) {
				$this->id = $obj->rowid;
				$this->ref = $obj->rowid;

				$this->ref_ext = $obj->ref_ext;

				$this->ldap_sid = $obj->ldap_sid;
				$this->civility_code = $obj->civility_code;
				$this->lastname = $obj->lastname;
				$this->firstname = $obj->firstname;
				$this->ref_employee = $obj->ref_employee;
				$this->national_registration_number = $obj->national_registration_number;

				$this->employee = $obj->employee;

				$this->login = $obj->login;
				$this->gender       = $obj->gender;
				$this->birth        = $this->db->jdate($obj->birth);
				$this->pass_indatabase = $obj->pass;
				$this->pass_indatabase_crypted = $obj->pass_crypted;
				$this->pass = $obj->pass;
				$this->pass_temp	= $obj->pass_temp;
				$this->api_key = dolDecrypt($obj->api_key);

				$this->address 		= $obj->address;
				$this->zip 			= $obj->zip;
				$this->town 		= $obj->town;

				$this->country_id = $obj->country_id;
				$this->country_code = $obj->country_id ? $obj->country_code : '';
				//$this->country = $obj->country_id?($langs->trans('Country'.$obj->country_code)!='Country'.$obj->country_code?$langs->transnoentities('Country'.$obj->country_code):$obj->country):'';

				$this->state_id     = $obj->state_id;
				$this->state_code   = $obj->state_code;
				$this->state        = ($obj->state != '-' ? $obj->state : '');

				$this->office_phone	= $obj->office_phone;
				$this->office_fax   = $obj->office_fax;
				$this->user_mobile  = $obj->user_mobile;
				$this->personal_mobile = $obj->personal_mobile;
				$this->email = $obj->email;
				$this->email_oauth2 = $obj->email_oauth2;
				$this->personal_email = $obj->personal_email;
				$this->socialnetworks = ($obj->socialnetworks ? (array) json_decode($obj->socialnetworks, true) : array());
				$this->job = $obj->job;
				$this->signature = $obj->signature;
				$this->admin		= $obj->admin;
				$this->note_public = $obj->note_public;
				$this->note_private = $obj->note_private;

				$this->statut		= $obj->status;			// deprecated
				$this->status		= $obj->status;

				$this->photo		= $obj->photo;
				$this->openid		= $obj->openid;
				$this->lang			= $obj->lang;
				$this->entity		= $obj->entity;
				$this->accountancy_code = $obj->accountancy_code;
				$this->thm			= $obj->thm;
				$this->tjm			= $obj->tjm;
				$this->salary = $obj->salary;
				$this->salaryextra = $obj->salaryextra;
				$this->weeklyhours = $obj->weeklyhours;
				$this->color = $obj->color;
				$this->dateemployment = $this->db->jdate($obj->dateemployment);
				$this->dateemploymentend = $this->db->jdate($obj->dateemploymentend);

				$this->datec				= $this->db->jdate($obj->datec);
				$this->datem				= $this->db->jdate($obj->datem);
				$this->datelastlogin = $this->db->jdate($obj->datel);
				$this->datepreviouslogin = $this->db->jdate($obj->datep);
				$this->flagdelsessionsbefore = $this->db->jdate($obj->flagdelsessionsbefore, 'gmt');
				$this->iplastlogin = $obj->iplastlogin;
				$this->ippreviouslogin = $obj->ippreviouslogin;
				$this->datestartvalidity = $this->db->jdate($obj->datestartvalidity);
				$this->dateendvalidity = $this->db->jdate($obj->dateendvalidity);

				$this->socid                = $obj->fk_soc;
				$this->contact_id           = $obj->fk_socpeople;
				$this->fk_member            = $obj->fk_member;
				$this->fk_user = $obj->fk_user;
				$this->fk_user_expense_validator = $obj->fk_user_expense_validator;
				$this->fk_user_holiday_validator = $obj->fk_user_holiday_validator;

				$this->default_range = $obj->default_range;
				$this->default_c_exp_tax_cat = $obj->default_c_exp_tax_cat;
				$this->fk_warehouse = $obj->fk_warehouse;
				$this->fk_establishment = $obj->fk_establishment;
				$this->label_establishment = $obj->label_establishment;

				// Protection when module multicompany was set, admin was set to first entity and then, the module was disabled,
				// in such case, this admin user must be admin for ALL entities.
				if (!isModEnabled('multicompany') && $this->admin && $this->entity == 1) {
					$this->entity = 0;
				}

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				$this->db->free($resql);
			} else {
				$this->error = "USERNOTFOUND";
				dol_syslog(get_class($this)."::fetch user not found", LOG_DEBUG);

				$this->db->free($resql);
				return 0;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		// To get back the global configuration unique to the user
		if ($loadpersonalconf) {
			$result = $this->loadPersonalConf();

			$result = $this->loadDefaultValues();

			if ($result < 0) {
				$this->error = $this->db->lasterror();
				return -3;
			}
		}

		return 1;
	}


	/**
	 *  Load const values from database table user_param and set it into user->conf->XXX
	 *
	 *  @return int						>= 0 if OK, < 0 if KO
	 */
	public function loadPersonalConf()
	{
		global $conf;

		// Load user->conf for user
		$sql = "SELECT param, value FROM ".$this->db->prefix()."user_param";
		$sql .= " WHERE fk_user = ".((int) $this->id);
		$sql .= " AND entity = ".((int) $conf->entity);
		//dol_syslog(get_class($this).'::fetch load personalized conf', LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$p = (!empty($obj->param) ? $obj->param : '');
				if (!empty($p)) {
					$this->conf->$p = $obj->value;
				}
				$i++;
			}
			$this->db->free($resql);

			return $num;
		} else {
			$this->error = $this->db->lasterror();

			return -2;
		}
	}

	/**
	 *  Load default values from database table into property ->default_values
	 *
	 *  @return int						> 0 if OK, < 0 if KO
	 */
	public function loadDefaultValues()
	{
		global $conf;

		if (getDolGlobalString('MAIN_ENABLE_DEFAULT_VALUES')) {
			// Load user->default_values for user. TODO Save this in memcached ?
			require_once DOL_DOCUMENT_ROOT.'/core/class/defaultvalues.class.php';

			$defaultValues = new DefaultValues($this->db);
			$result = $defaultValues->fetchAll('', '', 0, 0, '(t.user_id:in:0,'.$this->id.') AND (entity:in:'.(isset($this->entity) ? $this->entity : $conf->entity).','.$conf->entity.')');	// User 0 (all) + me (if defined)
			//$result = $defaultValues->fetchAll('', '', 0, 0, array('t.user_id'=>array(0, $this->id), 'entity'=>array((isset($this->entity) ? $this->entity : $conf->entity), $conf->entity)));	// User 0 (all) + me (if defined)

			if (!is_array($result) && $result < 0) {
				setEventMessages($defaultValues->error, $defaultValues->errors, 'errors');
				dol_print_error($this->db);
				return -1;
			} elseif (count($result) > 0) {
				foreach ($result as $defval) {
					if (!empty($defval->page) && !empty($defval->type) && !empty($defval->param)) {
						$pagewithoutquerystring = $defval->page;
						$pagequeries = '';
						$reg = array();
						if (preg_match('/^([^\?]+)\?(.*)$/', $pagewithoutquerystring, $reg)) {    // There is query param
							$pagewithoutquerystring = $reg[1];
							$pagequeries = $reg[2];
						}
						$this->default_values[$pagewithoutquerystring][$defval->type][$pagequeries ? $pagequeries : '_noquery_'][$defval->param] = $defval->value;
					}
				}
			}
			if (!empty($this->default_values)) {
				foreach ($this->default_values as $a => $b) {
					foreach ($b as $c => $d) {
						krsort($this->default_values[$a][$c]);
					}
				}
			}
		}
		return 1;
	}

	/**
	 *  Return if a user has a permission.
	 *  You can use it like this: if ($user->hasRight('module', 'level11')).
	 *  It replaces old syntax: if ($user->rights->module->level1)
	 *
	 * 	@param	string	$module			Module of permission to check
	 *  @param  string	$permlevel1		Permission level1 (Example: 'read', 'write', 'delete')
	 *  @param  string	$permlevel2		Permission level2
	 *  @return int						1 if user has permission, 0 if not.
	 *  @see	clearrights(), delrights(), getrights(), hasRight()
	 */
	public function hasRight($module, $permlevel1, $permlevel2 = '')
	{
		// For compatibility with bad naming permissions on module
		$moduletomoduletouse = array(
			'compta' => 'comptabilite',
			'contract' => 'contrat',
			'member' => 'adherent',
			'mo' => 'mrp',
			'order' => 'commande',
			'produit' => 'product',
			'project' => 'projet',
			'propale' => 'propal',
			'shipping' => 'expedition',
			'task' => 'task@projet',
			'fichinter' => 'ficheinter',
			'inventory' => 'stock',
			'invoice' => 'facture',
			'invoice_supplier' => 'fournisseur',
			'order_supplier' => 'fournisseur',
			'knowledgerecord' => 'knowledgerecord@knowledgemanagement',
			'skill@hrm' => 'all@hrm', // skill / job / position objects rights are for the moment grouped into right level "all"
			'job@hrm' => 'all@hrm', // skill / job / position objects rights are for the moment grouped into right level "all"
			'position@hrm' => 'all@hrm', // skill / job / position objects rights are for the moment grouped into right level "all"
			'facturerec' => 'facture',
			'margins' => 'margin',
		);

		if (!empty($moduletomoduletouse[$module])) {
			$module = $moduletomoduletouse[$module];
		}

		$moduleRightsMapping = array(
			'product' => 'produit',
			'margin' => 'margins',
			'comptabilite' => 'compta'
		);

		$rightsPath = $module;
		if (!empty($moduleRightsMapping[$rightsPath])) {
			$rightsPath = $moduleRightsMapping[$rightsPath];
		}

		// If module is abc@module, we check permission user->hasRight(module, abc, permlevel1)
		$tmp = explode('@', $rightsPath, 2);
		if (!empty($tmp[1])) {
			if (strpos($module, '@') !== false) {
				$module = $tmp[1];
			}
			if ($tmp[0] != $tmp[1]) {
				// If $module = 'myobject@mymodule'
				$rightsPath = $tmp[1];
				$permlevel2 = $permlevel1;
				$permlevel1 = $tmp[0];
			} else {
				// If $module = 'abc@abc'
				$rightsPath = $tmp[1];
			}
		}

		// In $conf->modules, we have 'accounting', 'product', 'facture', ...
		// In $user->rights, we have 'accounting', 'produit', 'facture', ...
		//var_dump($this->rights->$rightsPath);
		//var_dump($conf->modules);
		//var_dump($module.' '.isModEnabled($module).' '.$rightsPath.' '.$permlevel1.' '.$permlevel2);
		if (!isModEnabled($module)) {
			return 0;
		}

		// Special case for external user
		if (!empty($this->socid)) {
			if ($module == 'societe' && ($permlevel1 == 'creer' || $permlevel1 == 'write')) {
				return 0;	// An external user never has the permission ->societe->write to see all thirdparties (always restricted to himself)
			}
			if ($module == 'societe' && $permlevel1 == 'client' && $permlevel2 == 'voir') {
				return 0;	// An external user never has the permission ->societe->client->voir to see all thirdparties (always restricted to himself)
			}
			if ($module == 'societe' && $permlevel1 == 'export') {
				return 0;	// An external user never has the permission ->societe->export to see all thirdparties (always restricted to himself)
			}
			if ($module == 'societe' && ($permlevel1 == 'supprimer' || $permlevel1 == 'delete')) {
				return 0;	// An external user never has the permission ->societe->delete to see all thirdparties (always restricted to himself)
			}
		}

		// For compatibility with bad naming permissions on permlevel1
		if ($permlevel1 == 'propale') {
			$permlevel1 = 'propal';
		}
		if ($permlevel1 == 'member') {
			$permlevel1 = 'adherent';
		}
		if ($permlevel1 == 'recruitmentcandidature') {
			$permlevel1 = 'recruitmentjobposition';
		}

		//var_dump($this->rights);
		//var_dump($rightsPath.' '.$permlevel1.' '.$permlevel2);
		if (empty($rightsPath) || empty($this->rights) || empty($this->rights->$rightsPath) || empty($permlevel1)) {
			return 0;
		}

		if ($permlevel2) {
			if (!empty($this->rights->$rightsPath->$permlevel1)) {
				if (!empty($this->rights->$rightsPath->$permlevel1->$permlevel2)) {
					return $this->rights->$rightsPath->$permlevel1->$permlevel2;
				}
				// For backward compatibility with old permissions called "lire", "creer", "create", "supprimer"
				// instead of "read", "write", "delete"
				if ($permlevel2 == 'read' && !empty($this->rights->$rightsPath->$permlevel1->lire)) {
					return $this->rights->$rightsPath->$permlevel1->lire;
				}
				if ($permlevel2 == 'write' && !empty($this->rights->$rightsPath->$permlevel1->creer)) {
					return $this->rights->$rightsPath->$permlevel1->creer;
				}
				if ($permlevel2 == 'write' && !empty($this->rights->$rightsPath->$permlevel1->create)) {
					return $this->rights->$rightsPath->$permlevel1->create;
				}
				if ($permlevel2 == 'delete' && !empty($this->rights->$rightsPath->$permlevel1->supprimer)) {
					return $this->rights->$rightsPath->$permlevel1->supprimer;
				}
			}
		} else {
			if (!empty($this->rights->$rightsPath->$permlevel1)) {
				return $this->rights->$rightsPath->$permlevel1;
			}
			// For backward compatibility with old permissions called "lire", "creer", "create", "supprimer"
			// instead of "read", "write", "delete"
			if ($permlevel1 == 'read' && !empty($this->rights->$rightsPath->lire)) {
				return $this->rights->$rightsPath->lire;
			}
			if ($permlevel1 == 'write' && !empty($this->rights->$rightsPath->creer)) {
				return $this->rights->$rightsPath->creer;
			}
			if ($permlevel1 == 'write' && !empty($this->rights->$rightsPath->create)) {
				return $this->rights->$rightsPath->create;
			}
			if ($permlevel1 == 'delete' && !empty($this->rights->$rightsPath->supprimer)) {
				return $this->rights->$rightsPath->supprimer;
			}
		}

		return 0;
	}

	/**
	 *  Add a right to the user
	 *
	 * 	@param	int		$rid			Id of permission to add or 0 to add several permissions
	 *  @param  string	$allmodule		Add all permissions of module $allmodule or 'allmodules' to include all modules.
	 *  @param  string	$allperms		Add all permissions of module $allmodule, subperms $allperms only or '' to include all permissions.
	 *  @param	int		$entity			Entity to use
	 *  @param  int	    $notrigger		1=Does not execute triggers, 0=Execute triggers
	 *  @return int						> 0 if OK, < 0 if KO
	 *  @see	clearrights(), delrights(), getrights(), hasRight()
	 */
	public function addrights($rid, $allmodule = '', $allperms = '', $entity = 0, $notrigger = 0)
	{
		global $conf, $user, $langs;

		$entity = (empty($entity) ? $conf->entity : $entity);

		dol_syslog(get_class($this)."::addrights $rid, $allmodule, $allperms, $entity, $notrigger for user id=".$this->id);

		if (empty($this->id)) {
			$this->error = 'Try to call addrights on an object user with an empty id';
			return -1;
		}

		$error = 0;
		$whereforadd = '';

		$this->db->begin();

		if (!empty($rid)) {
			$module = $perms = $subperms = '';

			// If we ask to add a given permission, we first load properties of this permission (module, perms and subperms).
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

			// Define the where for the permission to add
			$whereforadd = "id=".((int) $rid);
			// Add also inherited permissions
			if (!empty($subperms)) {
				$whereforadd .= " OR (module='".$this->db->escape($module)."' AND perms='".$this->db->escape($perms)."' AND (subperms='lire' OR subperms='read'))";
			} elseif (!empty($perms)) {
				$whereforadd .= " OR (module='".$this->db->escape($module)."' AND (perms='lire' OR perms='read') AND subperms IS NULL)";
			}
		} else {
			// A list of permission was requested (not a single specific permission)
			// based on the name of a module of permissions
			// Used in the where clause to determine the list of permissions to add.
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

		// Add automatically other permission using the criteria whereforadd
		// $whereforadd can be a SQL filter or the string 'allmodules'
		if (!empty($whereforadd)) {
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql .= " FROM ".$this->db->prefix()."rights_def";
			$sql .= " WHERE entity = ".((int) $entity);
			if (!empty($whereforadd) && $whereforadd != 'allmodules') {
				$sql .= " AND (".$whereforadd.")";	// Note: parenthesis are important because whereforadd can contains OR. Also note that $whereforadd is already sanitized
			}

			$sqldelete = "DELETE FROM ".$this->db->prefix()."user_rights";
			$sqldelete .= " WHERE fk_user = ".((int) $this->id)." AND fk_id IN (";
			$sqldelete .= $sql;
			$sqldelete .= ") AND entity = ".((int) $entity);
			if (!$this->db->query($sqldelete)) {
				$error++;
			}

			if (!$error) {
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						if ($obj) {
							$nid = $obj->id;

							$sql = "INSERT INTO ".$this->db->prefix()."user_rights (entity, fk_user, fk_id) VALUES (".((int) $entity).", ".((int) $this->id).", ".((int) $nid).")";
							if (!$this->db->query($sql)) {
								$error++;
							}
						}

						$i++;
					}
				} else {
					$error++;
					dol_print_error($this->db);
				}
			}
		}

		if (!$error && !$notrigger) {
			$langs->load("other");
			$this->context = array('audit' => $langs->trans("PermissionsAdd").($rid ? ' (id='.$rid.')' : ''));

			// Call trigger
			$result = $this->call_trigger('USER_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
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
	 *  Remove a right to the user
	 *
	 *  @param	int			$rid        Id of permission to remove (or 0 when using the other filters)
	 *  @param  string		$allmodule  Retirer tous les droits du module allmodule
	 *  @param  string		$allperms   Retirer tous les droits du module allmodule, perms allperms
	 *  @param	int|string	$entity		Entity to use. Example: '1', or '0,1', or '2,3'
	 *  @param  int	    	$notrigger	1=Does not execute triggers, 0=Execute triggers
	 *  @return int         			> 0 if OK, < 0 if OK
	 *  @see	clearrights(), addrights(), getrights(), hasRight()
	 */
	public function delrights($rid, $allmodule = '', $allperms = '', $entity = 0, $notrigger = 0)
	{
		global $conf, $user, $langs;

		$error = 0;
		$wherefordel = '';
		$entity = (!empty($entity) ? $entity : $conf->entity);

		$this->db->begin();

		if (!empty($rid)) {
			$module = $perms = $subperms = '';

			// When the request is to delete a specific permissions, this gets the
			// les charactis for the module, permissions and sub-permission of this permission.
			$sql = "SELECT module, perms, subperms";
			$sql .= " FROM ".$this->db->prefix()."rights_def";
			$sql .= " WHERE id = '".$this->db->escape($rid)."'";
			$sql .= " AND entity IN (".$this->db->sanitize($entity, 0, 0, 0, 0).")";

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

			// Where clause for the list of permissions to delete
			$wherefordel = "id=".((int) $rid);
			// Suppression des droits induits
			if ($subperms == 'lire' || $subperms == 'read') {
				$wherefordel .= " OR (module='".$this->db->escape($module)."' AND perms='".$this->db->escape($perms)."' AND subperms IS NOT NULL)";
			}
			if ($perms == 'lire' || $perms == 'read') {
				$wherefordel .= " OR (module='".$this->db->escape($module)."')";
			}
		} else {
			// The deletion of the permissions concerns the name of a module or
			// list of permissions.
			// Used in the Where clause to determine the list of permission to delete
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

		// Delete permission according to a criteria set into $wherefordel
		if (!empty($wherefordel)) {
			//print "$module-$perms-$subperms";
			$sql = "SELECT id";
			$sql .= " FROM ".$this->db->prefix()."rights_def";
			$sql .= " WHERE entity IN (".$this->db->sanitize($entity, 0, 0, 0, 0).")";
			if (!empty($wherefordel) && $wherefordel != 'allmodules') {
				$sql .= " AND (".$wherefordel.")";	// Note: parenthesis are important because wherefordel can contains OR. Also note that $wherefordel is already sanitized
			}

			// avoid admin to remove his own important rights
			if ($this->admin == 1) {
				$sql .= " AND id NOT IN (251, 252, 253, 254, 255, 256)"; // other users rights
				$sql .= " AND id NOT IN (341, 342, 343, 344)"; // own rights
				$sql .= " AND id NOT IN (351, 352, 353, 354)"; // groups rights
				$sql .= " AND id NOT IN (358)"; // user export
			}

			$sqldelete = "DELETE FROM ".$this->db->prefix()."user_rights";
			$sqldelete .= " WHERE fk_user = ".((int) $this->id)." AND fk_id IN (";
			$sqldelete .= $sql;
			$sqldelete .= ")";
			$sqldelete .= " AND entity IN (".$this->db->sanitize($entity, 0, 0, 0, 0).")";

			$resql = $this->db->query($sqldelete);
			if (!$resql) {
				$error++;
				dol_print_error($this->db);
			}
		}

		if (!$error && !$notrigger) {
			$langs->load("other");
			$this->context = array('audit' => $langs->trans("PermissionsDelete").($rid ? ' (id='.$rid.')' : ''));

			// Call trigger
			$result = $this->call_trigger('USER_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
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
	 *  Clear all permissions array of user
	 *
	 *  @return	void
	 *  @see	getrights(), hasRight()
	 */
	public function clearrights()
	{
		dol_syslog(get_class($this)."::clearrights reset user->rights");
		$this->rights = new stdClass();
		$this->nb_rights = 0;
		$this->all_permissions_are_loaded = 0;
		$this->_tab_loaded = array();
	}


	/**
	 *	Load permissions granted to a user->id into object user->rights
	 *
	 *	@param  string	$moduletag		Limit permission for a particular module ('' by default means load all permissions)
	 *  @param	int		$forcereload	Force reload of permissions even if they were already loaded (ignore cache)
	 *	@return	void
	 *  @see	clearrights(), delrights(), addrights(), hasRight()
	 */
	public function loadRights($moduletag = '', $forcereload = 0)
	{
		global $conf;

		$alreadyloaded = false;

		if (empty($forcereload)) {
			if ($moduletag && isset($this->_tab_loaded[$moduletag]) && $this->_tab_loaded[$moduletag]) {
				// Rights for this module are already loaded, so we leave
				$alreadyloaded = true;
			}

			if (!empty($this->all_permissions_are_loaded)) {
				// We already loaded all rights for this user, so we leave
				$alreadyloaded = true;
			}
		}

		// More init to avoid warnings/errors
		if (!isset($this->rights) || !is_object($this->rights)) {
			$this->rights = new stdClass();
		}
		if (!isset($this->rights->user) || !is_object($this->rights->user)) {
			$this->rights->user = new stdClass();
		}

		// Get permission of users + Get permissions of groups

		if (!$alreadyloaded) {
			// First user permissions
			$sql = "SELECT DISTINCT r.module, r.perms, r.subperms";
			$sql .= " FROM ".$this->db->prefix()."user_rights as ur,";
			$sql .= " ".$this->db->prefix()."rights_def as r";
			$sql .= " WHERE r.id = ur.fk_id";
			if (getDolGlobalString('MULTICOMPANY_BACKWARD_COMPATIBILITY')) {
				// on old version, we use entity defined into table r only
				$sql .= " AND r.entity IN (0,".(isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE') ? "1," : "").$conf->entity.")";
			} else {
				// On table r=rights_def, the unique key is (id, entity) because id is hard coded into module descriptor and insert during module activation.
				// So we must include the filter on entity on both table r. and ur.
				$sql .= " AND r.entity = ".((int) $conf->entity)." AND ur.entity = ".((int) $conf->entity);
			}
			$sql .= " AND ur.fk_user= ".((int) $this->id);
			$sql .= " AND r.perms IS NOT NULL";
			if (!getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
				$sql .= " AND r.perms NOT LIKE '%_advance'"; // Hide advanced perms if option is not enabled
			}
			if ($moduletag) {
				$sql .= " AND r.module = '".$this->db->escape($moduletag)."'";
			}

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

						if (!empty($perms)) {
							if (!empty($module)) {
								if (!isset($this->rights->$module) || !is_object($this->rights->$module)) {
									$this->rights->$module = new stdClass();
								}
								if (!empty($subperms)) {
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
					}
					$i++;
				}
				$this->db->free($resql);
			}

			// Now permissions of groups
			$sql = "SELECT DISTINCT r.module, r.perms, r.subperms, r.entity";
			$sql .= " FROM ".$this->db->prefix()."usergroup_rights as gr,";
			$sql .= " ".$this->db->prefix()."usergroup_user as gu,";
			$sql .= " ".$this->db->prefix()."rights_def as r";
			$sql .= " WHERE r.id = gr.fk_id";
			// @FIXME Very strange business rules. Must be always the same than into user->loadRights() user/perms.php and user/group/perms.php
			if (getDolGlobalString('MULTICOMPANY_BACKWARD_COMPATIBILITY')) {
				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$sql .= " AND gu.entity IN (0,".$conf->entity.")";
				} else {
					$sql .= " AND r.entity = ".((int) $conf->entity);
				}
			} else {
				$sql .= " AND gr.entity = ".((int) $conf->entity);	// Only groups created in current entity
				// The entity on the table gu=usergroup_user should be useless and should never be used because it is already into gr and r.
				// but when using MULTICOMPANY_TRANSVERSE_MODE, we may have inserted record that make rubbish result here due to the duplicate record of
				// other entities, so we are forced to add a filter on gu here
				$sql .= " AND gu.entity IN (0,".$conf->entity.")";
				$sql .= " AND r.entity = ".((int) $conf->entity);	// Only permission of modules enabled in current entity
			}
			// End of strange business rule
			$sql .= " AND gr.fk_usergroup = gu.fk_usergroup";
			$sql .= " AND gu.fk_user = ".((int) $this->id);
			$sql .= " AND r.perms IS NOT NULL";
			if ($moduletag) {
				$sql .= " AND r.module = '".$this->db->escape($moduletag)."'";
			}

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

						if (!empty($perms)) {
							if (!empty($module)) {
								if (!isset($this->rights->$module) || !is_object($this->rights->$module)) {
									$this->rights->$module = new stdClass();
								}
								if (!empty($subperms)) {
									if (!isset($this->rights->$module->$perms) || !is_object($this->rights->$module->$perms)) {
										$this->rights->$module->$perms = new stdClass();
									}
									if (empty($this->rights->$module->$perms->$subperms)) {	// already counted
										$this->nb_rights++;
									}
									$this->rights->$module->$perms->$subperms = 1;
								} else {
									if (empty($this->rights->$module->$perms)) {			// already counted
										$this->nb_rights++;
									}
									// if we have already define a subperm like this $this->rights->$module->level1->level2 with llx_user_rights, we don't want override level1 because the level2 can be not define on user group
									if (!isset($this->rights->$module->$perms) || !is_object($this->rights->$module->$perms)) {
										$this->rights->$module->$perms = 1;
									}
								}
							}
						}
					}
					$i++;
				}
				$this->db->free($resql);
			}

			// Force permission on user for admin
			if (!empty($this->admin)) {
				if (empty($this->rights->user->user)) {
					$this->rights->user->user = new stdClass();
				}
				$listofpermtotest = array('lire', 'creer', 'password', 'supprimer', 'export');
				foreach ($listofpermtotest as $permtotest) {
					if (empty($this->rights->user->user->$permtotest)) {
						$this->rights->user->user->$permtotest = 1;
						$this->nb_rights++;
					}
				}
				if (empty($this->rights->user->self)) {
					$this->rights->user->self = new stdClass();
				}
				$listofpermtotest = array('creer', 'password');
				foreach ($listofpermtotest as $permtotest) {
					if (empty($this->rights->user->self->$permtotest)) {
						$this->rights->user->self->$permtotest = 1;
						$this->nb_rights++;
					}
				}
				// Add test on advanced permissions
				if (getDolGlobalString('MAIN_USE_ADVANCED_PERMS')) {
					if (empty($this->rights->user->user_advance)) {
						$this->rights->user->user_advance = new stdClass();
					}
					$listofpermtotest = array('readperms', 'write');
					foreach ($listofpermtotest as $permtotest) {
						if (empty($this->rights->user->user_advance->$permtotest)) {
							$this->rights->user->user_advance->$permtotest = 1;
							$this->nb_rights++;
						}
					}
					if (empty($this->rights->user->self_advance)) {
						$this->rights->user->self_advance = new stdClass();
					}
					$listofpermtotest = array('readperms', 'writeperms');
					foreach ($listofpermtotest as $permtotest) {
						if (empty($this->rights->user->self_advance->$permtotest)) {
							$this->rights->user->self_advance->$permtotest = 1;
							$this->nb_rights++;
						}
					}
					if (empty($this->rights->user->group_advance)) {
						$this->rights->user->group_advance = new stdClass();
					}
					$listofpermtotest = array('read', 'readperms', 'write', 'delete');
					foreach ($listofpermtotest as $permtotest) {
						if (empty($this->rights->user) || empty($this->rights->user->group_advance->$permtotest)) {
							$this->rights->user->group_advance->$permtotest = 1;
							$this->nb_rights++;
						}
					}
				}
			}

			// For backward compatibility
			if (isset($this->rights->propale) && !isset($this->rights->propal)) {
				$this->rights->propal = $this->rights->propale;
			}
			if (isset($this->rights->propal) && !isset($this->rights->propale)) {
				$this->rights->propale = $this->rights->propal;
			}

			if (!$moduletag) {
				// If the module was not define, then everything is loaded.
				// Therefore, we can consider that the permissions are cached
				// because they were all loaded for this user instance.
				$this->all_permissions_are_loaded = 1;
			} else {
				// If the module is defined, we flag it as loaded into cache
				$this->_tab_loaded[$moduletag] = 1;
			}
		}
	}

	/**
	 *	Load permissions granted to a user->id into object user->rights
	 *  TODO Remove this method. It has a name conflict with getRights() in CommonObject and was replaced in v20 with loadRights()
	 *
	 *	@param  string	$moduletag		Limit permission for a particular module ('' by default means load all permissions)
	 *  @param	int		$forcereload	Force reload of permissions even if they were already loaded (ignore cache)
	 *	@return	void
	 *  @deprecated
	 *
	 *  @see	clearrights(), delrights(), addrights(), hasRight()
	 *  @phpstan-ignore-next-line
	 */
	public function getrights($moduletag = '', $forcereload = 0)
	{
		$this->loadRights($moduletag, $forcereload);
	}

	/**
	 *  Change status of a user
	 *
	 *	@param	int		$status		Status to set
	 *  @return int     			Return integer <0 if KO, 0 if nothing is done, >0 if OK
	 */
	public function setstatus($status)
	{
		global $conf, $langs, $user;

		$error = 0;

		// Check parameters
		if (isset($this->statut)) {
			if ($this->statut == $status) {
				return 0;
			}
		} elseif (isset($this->status) && $this->status == $status) {
			return 0;
		}

		$this->db->begin();

		// Save in database
		$sql = "UPDATE ".$this->db->prefix()."user";
		$sql .= " SET statut = ".((int) $status);
		$sql .= " WHERE rowid = ".((int) $this->id);
		$result = $this->db->query($sql);

		dol_syslog(get_class($this)."::setstatus", LOG_DEBUG);
		if ($result) {
			if ($status == 0) {
				$this->context['actionmsg'] = 'User '.$this->login.' disabled';
			} else {
				$this->context['actionmsg'] = 'User '.$this->login.' enabled';
			}
			// Call trigger
			$result = $this->call_trigger('USER_ENABLEDISABLE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if ($error) {
			$this->db->rollback();
			return -$error;
		} else {
			$this->status = $status;
			$this->statut = $status;
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
	 * @param 	int[]|int 	$categories 	Category or categories IDs
	 * @return 	int							Return integer <0 if KO, >0 if OK
	 */
	public function setCategories($categories)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		return parent::setCategoriesCommon($categories, Categorie::TYPE_USER);
	}

	/**
	 *  Delete the user
	 *
	 *	@param		User	$user	User than delete
	 * 	@return		int				Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user)
	{
		global $conf, $langs;

		$error = 0;

		$this->db->begin();

		$this->fetch($this->id);

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);

		// Remove rights
		$sql = "DELETE FROM ".$this->db->prefix()."user_rights WHERE fk_user = ".((int) $this->id);

		if (!$error && !$this->db->query($sql)) {
			$error++;
			$this->error = $this->db->lasterror();
		}

		// Remove group
		$sql = "DELETE FROM ".$this->db->prefix()."usergroup_user WHERE fk_user  = ".((int) $this->id);
		if (!$error && !$this->db->query($sql)) {
			$error++;
			$this->error = $this->db->lasterror();
		}

		// Remove params
		$sql = "DELETE FROM ".$this->db->prefix()."user_param WHERE fk_user  = ".((int) $this->id);
		if (!$error && !$this->db->query($sql)) {
			$error++;
			$this->error = $this->db->lasterror();
		}

		// If contact, remove link
		if ($this->contact_id > 0) {
			$sql = "UPDATE ".$this->db->prefix()."socpeople SET fk_user_creat = null WHERE rowid = ".((int) $this->contact_id);
			if (!$error && !$this->db->query($sql)) {
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

		// Remove extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error -4 ".$this->error, LOG_ERR);
			}
		}

		// Remove user
		if (!$error) {
			$sql = "DELETE FROM ".$this->db->prefix()."user WHERE rowid = ".((int) $this->id);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			if (!$this->db->query($sql)) {
				$error++;
				$this->error = $this->db->lasterror();
			}
		}

		if (!$error) {
			// Call trigger
			$result = $this->call_trigger('USER_DELETE', $user);
			if ($result < 0) {
				$error++;
				$this->db->rollback();
				return -1;
			}
			// End call triggers

			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Create a user into database
	 *
	 *  @param	User	$user        	Object user doing creation
	 *  @param  int		$notrigger		1=do not execute triggers, 0 otherwise
	 *  @return int			         	Return integer <0 if KO, id of created user if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;
		global $mysoc;

		// Clean parameters
		$this->setUpperOrLowerCase();

		$this->civility_code = trim((string) $this->civility_code);
		$this->login = trim((string) $this->login);
		if (!isset($this->entity)) {
			$this->entity = $conf->entity; // If not defined, we use default value
		}

		dol_syslog(get_class($this)."::create login=".$this->login.", user=".(is_object($user) ? $user->id : ''), LOG_DEBUG);

		$badCharUnauthorizedIntoLoginName = getDolGlobalString('MAIN_LOGIN_BADCHARUNAUTHORIZED', ',@<>"\'');

		// Check parameters
		if (getDolGlobalString('USER_MAIL_REQUIRED') && !isValidEmail($this->email)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail", $this->email);
			return -1;
		}
		if (empty($this->login)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFieldRequired", $langs->transnoentitiesnoconv("Login"));
			return -1;
		} elseif (preg_match('/['.preg_quote($badCharUnauthorizedIntoLoginName, '/').']/', $this->login)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadCharIntoLoginName", $langs->transnoentitiesnoconv("Login"));
			return -1;
		}

		$this->datec = dol_now();

		$error = 0;
		$this->db->begin();

		// Check if login already exists in same entity or into entity 0.
		if ($this->login) {
			$sqltochecklogin = "SELECT COUNT(*) as nb FROM ".$this->db->prefix()."user WHERE entity IN (".$this->db->sanitize((int) $this->entity).", 0) AND login = '".$this->db->escape($this->login)."'";
			$resqltochecklogin = $this->db->query($sqltochecklogin);
			if ($resqltochecklogin) {
				$objtochecklogin = $this->db->fetch_object($resqltochecklogin);
				if ($objtochecklogin && $objtochecklogin->nb > 0) {
					$langs->load("errors");
					$this->error = $langs->trans("ErrorLoginAlreadyExists", $this->login);
					dol_syslog(get_class($this)."::create ".$this->error, LOG_DEBUG);
					$this->db->rollback();
					return -6;
				}
				$this->db->free($resqltochecklogin);
			}
		}
		if (!empty($this->email)) {
			$sqltochecklogin = "SELECT COUNT(*) as nb FROM ".$this->db->prefix()."user WHERE entity IN (".$this->db->sanitize((int) $this->entity).", 0) AND email = '".$this->db->escape($this->email)."'";
			$resqltochecklogin = $this->db->query($sqltochecklogin);
			if ($resqltochecklogin) {
				$objtochecklogin = $this->db->fetch_object($resqltochecklogin);
				if ($objtochecklogin && $objtochecklogin->nb > 0) {
					$langs->load("errors");
					$this->error = $langs->trans("ErrorEmailAlreadyExists", $this->email);
					dol_syslog(get_class($this)."::create ".$this->error, LOG_DEBUG);
					$this->db->rollback();
					return -6;
				}
				$this->db->free($resqltochecklogin);
			}
		}

		// Insert into database
		$sql = "INSERT INTO ".$this->db->prefix()."user (datec, login, ldap_sid, entity)";
		$sql .= " VALUES('".$this->db->idate($this->datec)."', '".$this->db->escape($this->login)."', '".$this->db->escape($this->ldap_sid)."', ".((int) $this->entity).")";
		$result = $this->db->query($sql);

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		if ($result) {
			$this->id = $this->db->last_insert_id($this->db->prefix()."user");

			// Set default rights
			if ($this->set_default_rights() < 0) {
				$this->error = 'ErrorFailedToSetDefaultRightOfUser';
				$this->db->rollback();
				return -5;
			}

			if (getDolGlobalString('MAIN_DEFAULT_WAREHOUSE_USER') && getDolGlobalString('STOCK_USERSTOCK_AUTOCREATE')) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
				$langs->load("stocks");

				$entrepot = new Entrepot($this->db);
				$entrepot->label = $langs->trans("PersonalStock", $this->getFullName($langs));
				$entrepot->libelle = $entrepot->label; // For backward compatibility
				$entrepot->description = $langs->trans("ThisWarehouseIsPersonalStock", $this->getFullName($langs));
				$entrepot->statut = 1;
				$entrepot->country_id = $mysoc->country_id;

				$warehouseid = $entrepot->create($user);

				$this->fk_warehouse = $warehouseid;
			}

			// Update minor fields
			$result = $this->update($user, 1, 1);
			if ($result < 0) {
				$this->db->rollback();
				return -4;
			}

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('USER_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return $this->id;
			} else {
				//$this->error=$interface->error;
				dol_syslog(get_class($this)."::create ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -3;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -2;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Create a user from a contact object. User will be internal but if contact is linked to a third party, user will be external
	 *
	 *  @param	Contact	$contact    Object for source contact
	 * 	@param  string	$login      Login to force
	 *  @param  string	$password   Password to force
	 *  @return int 				Return integer <0 if error, if OK returns id of created user
	 */
	public function create_from_contact($contact, $login = '', $password = '')
	{
		// phpcs:enable
		global $conf, $user, $langs;

		$error = 0;

		// Define parameters
		$this->admin = 0;
		$this->civility_code = $contact->civility_code;
		$this->lastname = $contact->lastname;
		$this->firstname = $contact->firstname;
		//$this->gender = $contact->gender;		// contact has no gender
		$this->email = $contact->email;
		$this->socialnetworks = $contact->socialnetworks;
		$this->office_phone = $contact->phone_pro;
		$this->office_fax = $contact->fax;
		$this->user_mobile = $contact->phone_mobile;
		$this->address = $contact->address;
		$this->zip = $contact->zip;
		$this->town = $contact->town;
		$this->setUpperOrLowerCase();
		$this->state_id = $contact->state_id;
		$this->country_id = $contact->country_id;
		$this->employee = 0;

		if (empty($login)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$login = dol_buildlogin($contact->lastname, $contact->firstname);
		}
		$this->login = $login;

		$this->db->begin();

		// Create user and set $this->id. Trigger is disabled because executed later.
		$result = $this->create($user, 1);
		if ($result > 0) {
			$sql = "UPDATE ".$this->db->prefix()."user";
			$sql .= " SET fk_socpeople=".((int) $contact->id);
			$sql .= ", civility='".$this->db->escape($contact->civility_code)."'";
			if ($contact->socid > 0) {
				$sql .= ", fk_soc=".((int) $contact->socid);
			}
			$sql .= " WHERE rowid=".((int) $this->id);

			$resql = $this->db->query($sql);

			dol_syslog(get_class($this)."::create_from_contact", LOG_DEBUG);
			if ($resql) {
				$this->context['createfromcontact'] = 'createfromcontact';

				// Call trigger
				$result = $this->call_trigger('USER_CREATE', $user);
				if ($result < 0) {
					$error++;
					$this->db->rollback();
					return -1;
				}
				// End call triggers

				$this->db->commit();
				return $this->id;
			} else {
				$this->error = $this->db->error();

				$this->db->rollback();
				return -1;
			}
		} else {
			// $this->error deja positionne
			dol_syslog(get_class($this)."::create_from_contact - 0");

			$this->db->rollback();
			return $result;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Create a user into database from a member object.
	 *  If $member->fk_soc is set, it will be an external user.
	 *
	 *  @param	Adherent		$member		Object member source
	 * 	@param	string			$login		Login to force
	 *  @return int							Return integer <0 if KO, if OK, return id of created account
	 */
	public function create_from_member($member, $login = '')
	{
		// phpcs:enable
		global $user;

		// Set properties on new user
		$this->admin = 0;
		$this->civility_code = $member->civility_code;
		$this->lastname     = $member->lastname;
		$this->firstname    = $member->firstname;
		$this->gender		= $member->gender;
		$this->email        = $member->email;
		$this->fk_member    = $member->id;
		$this->address      = $member->address;
		$this->zip          = $member->zip;
		$this->town         = $member->town;
		$this->setUpperOrLowerCase();
		$this->state_id     = $member->state_id;
		$this->country_id   = $member->country_id;
		$this->socialnetworks = $member->socialnetworks;

		$this->pass         = $member->pass;
		$this->pass_crypted = $member->pass_indatabase_crypted;

		if (empty($login)) {
			include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
			$login = dol_buildlogin($member->lastname, $member->firstname);
		}
		$this->login = $login;

		$this->db->begin();

		// Create and set $this->id
		$result = $this->create($user);
		if ($result > 0) {
			if (!empty($this->pass)) {	// If a clear password was received (this situation should not happen anymore now), we use it to save it into database
				$newpass = $this->setPassword($user, $this->pass);
				if (is_int($newpass) && $newpass < 0) {
					$result = -2;
				}
			} elseif (!empty($this->pass_crypted)) {	// If an encrypted password is already known, we save it directly into database because the previous create did not save it.
				$sql = "UPDATE ".$this->db->prefix()."user";
				$sql .= " SET pass_crypted = '".$this->db->escape($this->pass_crypted)."'";
				$sql .= " WHERE rowid=".((int) $this->id);

				$resql = $this->db->query($sql);
				if (!$resql) {
					$result = -1;
				}
			}

			if ($result > 0 && $member->socid) {	// If member is linked to a thirdparty
				$sql = "UPDATE ".$this->db->prefix()."user";
				$sql .= " SET fk_soc=".((int) $member->socid);
				$sql .= " WHERE rowid=".((int) $this->id);

				dol_syslog(get_class($this)."::create_from_member", LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$this->db->commit();
					return $this->id;
				} else {
					$this->error = $this->db->lasterror();

					$this->db->rollback();
					return -1;
				}
			}
		}

		if ($result > 0) {
			$this->db->commit();
			return $this->id;
		} else {
			// $this->error was already set
			$this->db->rollback();
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Assign rights by default
	 *
	 *    @return     integer erreur <0, si ok renvoi le nbre de droits par default positions
	 */
	public function set_default_rights()
	{
		// phpcs:enable
		global $conf;

		$rd = array();
		$num = 0;
		$sql = "SELECT id FROM ".$this->db->prefix()."rights_def";
		$sql .= " WHERE bydefault = 1";
		$sql .= " AND entity = ".((int) $conf->entity);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				$rd[$i] = $row[0];
				$i++;
			}
			$this->db->free($resql);
		}
		$i = 0;
		while ($i < $num) {
			$sql = "DELETE FROM ".$this->db->prefix()."user_rights WHERE fk_user = $this->id AND fk_id=$rd[$i]";
			$result = $this->db->query($sql);

			$sql = "INSERT INTO ".$this->db->prefix()."user_rights (fk_user, fk_id) VALUES ($this->id, $rd[$i])";
			$result = $this->db->query($sql);
			if (!$result) {
				return -1;
			}
			$i++;
		}

		return $i;
	}

	/**
	 *  	Update a user into database (and also password if this->pass is defined)
	 *
	 *		@param	User	$user				User making update
	 *    	@param  int		$notrigger			1=do not execute triggers, 0 by default
	 *		@param	int		$nosyncmember		0=Synchronize linked member (standard info), 1=Do not synchronize linked member
	 *		@param	int		$nosyncmemberpass	0=Synchronize linked member (password), 1=Do not synchronize linked member
	 *		@param	int		$nosynccontact		0=Synchronize linked contact, 1=Do not synchronize linked contact
	 *    	@return int 		        		Return integer <0 if KO, >=0 if OK
	 */
	public function update($user, $notrigger = 0, $nosyncmember = 0, $nosyncmemberpass = 0, $nosynccontact = 0)
	{
		global $conf, $langs;

		$nbrowsaffected = 0;
		$error = 0;

		dol_syslog(get_class($this)."::update notrigger=".$notrigger.", nosyncmember=".$nosyncmember.", nosyncmemberpass=".$nosyncmemberpass);

		// Clean parameters
		$this->civility_code				= trim((string) $this->civility_code);
		$this->lastname						= trim((string) $this->lastname);
		$this->firstname					= trim((string) $this->firstname);
		$this->ref_employee					= trim((string) $this->ref_employee);
		$this->national_registration_number	= trim((string) $this->national_registration_number);
		$this->employee						= ($this->employee > 0 ? $this->employee : 0);
		$this->login						= trim((string) $this->login);
		$this->gender						= trim((string) $this->gender);

		$this->pass							= trim((string) $this->pass);
		$this->api_key						= trim((string) $this->api_key);
		$this->datestartvalidity			= empty($this->datestartvalidity) ? '' : $this->datestartvalidity;
		$this->dateendvalidity				= empty($this->dateendvalidity) ? '' : $this->dateendvalidity;

		$this->address						= trim((string) $this->address);
		$this->zip							= trim((string) $this->zip);
		$this->town							= trim((string) $this->town);

		$this->state_id						= ($this->state_id > 0 ? $this->state_id : 0);
		$this->country_id					= ($this->country_id > 0 ? $this->country_id : 0);
		$this->office_phone					= trim((string) $this->office_phone);
		$this->office_fax					= trim((string) $this->office_fax);
		$this->user_mobile					= trim((string) $this->user_mobile);
		$this->personal_mobile				= trim((string) $this->personal_mobile);
		$this->email						= trim((string) $this->email);
		$this->personal_email				= trim((string) $this->personal_email);

		$this->job							= trim((string) $this->job);
		$this->signature					= trim((string) $this->signature);
		$this->note_public					= trim((string) $this->note_public);
		$this->note_private					= trim((string) $this->note_private);
		$this->openid						= trim((string) $this->openid);
		$this->admin						= ($this->admin > 0 ? $this->admin : 0);

		$this->accountancy_code				= trim((string) $this->accountancy_code);
		$this->color						= trim((string) $this->color);
		$this->dateemployment				= empty($this->dateemployment) ? '' : $this->dateemployment;
		$this->dateemploymentend			= empty($this->dateemploymentend) ? '' : $this->dateemploymentend;

		$this->birth						= empty($this->birth) ? '' : $this->birth;
		$this->fk_warehouse					= (int) $this->fk_warehouse;
		$this->fk_establishment				= (int) $this->fk_establishment;

		$this->setUpperOrLowerCase();

		// Check parameters
		$badCharUnauthorizedIntoLoginName = getDolGlobalString('MAIN_LOGIN_BADCHARUNAUTHORIZED', ',@<>"\'');

		if (getDolGlobalString('USER_MAIL_REQUIRED') && !isValidEmail($this->email)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadEMail", $this->email);
			return -1;
		}
		if (empty($this->login)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorFieldRequired", 'Login');
			return -1;
		} elseif (preg_match('/['.preg_quote($badCharUnauthorizedIntoLoginName, '/').']/', $this->login)) {
			$langs->load("errors");
			$this->error = $langs->trans("ErrorBadCharIntoLoginName", $langs->transnoentitiesnoconv("Login"));
			return -1;
		}

		$this->db->begin();

		// Check if login already exists in same entity or into entity 0.
		if (is_object($this->oldcopy) && !$this->oldcopy->isEmpty() && $this->oldcopy->login != $this->login) {
			$sqltochecklogin = "SELECT COUNT(*) as nb FROM ".$this->db->prefix()."user WHERE entity IN (".$this->db->sanitize((int) $this->entity).", 0) AND login = '".$this->db->escape($this->login)."'";
			$resqltochecklogin = $this->db->query($sqltochecklogin);
			if ($resqltochecklogin) {
				$objtochecklogin = $this->db->fetch_object($resqltochecklogin);
				if ($objtochecklogin && $objtochecklogin->nb > 0) {
					$langs->load("errors");
					$this->error = $langs->trans("ErrorLoginAlreadyExists", $this->login);
					dol_syslog(get_class($this)."::create ".$this->error, LOG_DEBUG);
					$this->db->rollback();
					return -1;
				}
			}
		}
		if (is_object($this->oldcopy) && !$this->oldcopy->isEmpty() && !empty($this->email) && $this->oldcopy->email != $this->email) {
			$sqltochecklogin = "SELECT COUNT(*) as nb FROM ".$this->db->prefix()."user WHERE entity IN (".$this->db->sanitize((int) $this->entity).", 0) AND email = '".$this->db->escape($this->email)."'";
			$resqltochecklogin = $this->db->query($sqltochecklogin);
			if ($resqltochecklogin) {
				$objtochecklogin = $this->db->fetch_object($resqltochecklogin);
				if ($objtochecklogin && $objtochecklogin->nb > 0) {
					$langs->load("errors");
					$this->error = $langs->trans("ErrorEmailAlreadyExists", $this->email);
					dol_syslog(get_class($this)."::create ".$this->error, LOG_DEBUG);
					$this->db->rollback();
					return -1;
				}
			}
		}

		// Update data
		$sql = "UPDATE ".$this->db->prefix()."user SET";
		$sql .= " civility = '".$this->db->escape($this->civility_code)."'";
		$sql .= ", lastname = '".$this->db->escape($this->lastname)."'";
		$sql .= ", firstname = '".$this->db->escape($this->firstname)."'";
		$sql .= ", ref_employee = '".$this->db->escape($this->ref_employee)."'";
		$sql .= ", national_registration_number = '".$this->db->escape($this->national_registration_number)."'";
		$sql .= ", employee = ".(int) $this->employee;
		$sql .= ", login = '".$this->db->escape($this->login)."'";
		$sql .= ", api_key = ".($this->api_key ? "'".$this->db->escape(dolEncrypt($this->api_key, '', '', 'dolibarr'))."'" : "null");
		$sql .= ", gender = ".($this->gender != -1 ? "'".$this->db->escape($this->gender)."'" : "null"); // 'man' or 'woman' or 'other'
		$sql .= ", birth=".(strval($this->birth) != '' ? "'".$this->db->idate($this->birth, 'tzserver')."'" : 'null');
		if (!empty($user->admin)) {
			$sql .= ", admin = ".(int) $this->admin; // admin flag can be set/unset only by an admin user
		}
		$sql .= ", address = '".$this->db->escape($this->address)."'";
		$sql .= ", zip = '".$this->db->escape($this->zip)."'";
		$sql .= ", town = '".$this->db->escape($this->town)."'";
		$sql .= ", fk_state = ".((!empty($this->state_id) && $this->state_id > 0) ? "'".$this->db->escape($this->state_id)."'" : "null");
		$sql .= ", fk_country = ".((!empty($this->country_id) && $this->country_id > 0) ? "'".$this->db->escape($this->country_id)."'" : "null");
		$sql .= ", office_phone = '".$this->db->escape($this->office_phone)."'";
		$sql .= ", office_fax = '".$this->db->escape($this->office_fax)."'";
		$sql .= ", user_mobile = '".$this->db->escape($this->user_mobile)."'";
		$sql .= ", personal_mobile = '".$this->db->escape($this->personal_mobile)."'";
		$sql .= ", email = '".$this->db->escape($this->email)."'";
		$sql .= ", personal_email = '".$this->db->escape($this->personal_email)."'";
		$sql .= ", socialnetworks = '".$this->db->escape(json_encode($this->socialnetworks))."'";
		$sql .= ", job = '".$this->db->escape($this->job)."'";
		$sql .= ", signature = '".$this->db->escape($this->signature)."'";
		$sql .= ", accountancy_code = '".$this->db->escape($this->accountancy_code)."'";
		$sql .= ", color = '".$this->db->escape($this->color)."'";
		$sql .= ", dateemployment=".(strval($this->dateemployment) != '' ? "'".$this->db->idate($this->dateemployment)."'" : 'null');
		$sql .= ", dateemploymentend=".(strval($this->dateemploymentend) != '' ? "'".$this->db->idate($this->dateemploymentend)."'" : 'null');
		$sql .= ", datestartvalidity=".(strval($this->datestartvalidity) != '' ? "'".$this->db->idate($this->datestartvalidity)."'" : 'null');
		$sql .= ", dateendvalidity=".(strval($this->dateendvalidity) != '' ? "'".$this->db->idate($this->dateendvalidity)."'" : 'null');
		$sql .= ", note_private = '".$this->db->escape($this->note_private)."'";
		$sql .= ", note_public = '".$this->db->escape($this->note_public)."'";
		$sql .= ", photo = ".($this->photo ? "'".$this->db->escape($this->photo)."'" : "null");
		$sql .= ", openid = ".($this->openid ? "'".$this->db->escape($this->openid)."'" : "null");
		$sql .= ", fk_user = ".($this->fk_user > 0 ? "'".$this->db->escape($this->fk_user)."'" : "null");
		$sql .= ", fk_user_expense_validator = ".($this->fk_user_expense_validator > 0 ? "'".$this->db->escape($this->fk_user_expense_validator)."'" : "null");
		$sql .= ", fk_user_holiday_validator = ".($this->fk_user_holiday_validator > 0 ? "'".$this->db->escape($this->fk_user_holiday_validator)."'" : "null");
		if (isset($this->thm) || $this->thm != '') {
			$sql .= ", thm= ".($this->thm != '' ? "'".$this->db->escape($this->thm)."'" : "null");
		}
		if (isset($this->tjm) || $this->tjm != '') {
			$sql .= ", tjm= ".($this->tjm != '' ? "'".$this->db->escape($this->tjm)."'" : "null");
		}
		if (isset($this->salary) || $this->salary != '') {
			$sql .= ", salary= ".($this->salary != '' ? "'".$this->db->escape($this->salary)."'" : "null");
		}
		if (isset($this->salaryextra) || $this->salaryextra != '') {
			$sql .= ", salaryextra= ".($this->salaryextra != '' ? "'".$this->db->escape($this->salaryextra)."'" : "null");
		}
		$sql .= ", weeklyhours= ".($this->weeklyhours != '' ? "'".$this->db->escape($this->weeklyhours)."'" : "null");
		if (!empty($user->admin) && empty($user->entity) && $user->id != $this->id) {
			$sql .= ", entity = ".((int) $this->entity); // entity flag can be set/unset only by an another superadmin user
		}
		$sql .= ", default_range = ".($this->default_range > 0 ? $this->default_range : 'null');
		$sql .= ", default_c_exp_tax_cat = ".($this->default_c_exp_tax_cat > 0 ? $this->default_c_exp_tax_cat : 'null');
		$sql .= ", fk_warehouse = ".($this->fk_warehouse > 0 ? $this->fk_warehouse : "null");
		$sql .= ", fk_establishment = ".($this->fk_establishment > 0 ? $this->fk_establishment : "null");
		$sql .= ", lang = ".($this->lang ? "'".$this->db->escape($this->lang)."'" : "null");
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$nbrowsaffected += $this->db->affected_rows($resql);

			// Update password
			if (!empty($this->pass)) {
				if ($this->pass != $this->pass_indatabase && !dol_verifyHash($this->pass, $this->pass_indatabase_crypted)) {
					// If a new value for password is set and different than the one encrypted into database
					$result = $this->setPassword($user, $this->pass, 0, $notrigger, $nosyncmemberpass, 0, 1);
					if (is_int($result) && $result < 0) {
						return -5;
					}
				}
			}

			// If user is linked to a member, remove old link to this member
			if ($this->fk_member > 0) {
				dol_syslog(get_class($this)."::update remove link with member. We will recreate it later", LOG_DEBUG);
				$sql = "UPDATE ".$this->db->prefix()."user SET fk_member = NULL where fk_member = ".((int) $this->fk_member);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$this->error = $this->db->error();
					$this->db->rollback();
					return -5;
				}
			}
			// Set link to user
			dol_syslog(get_class($this)."::update set link with member", LOG_DEBUG);
			$sql = "UPDATE ".$this->db->prefix()."user SET fk_member =".($this->fk_member > 0 ? ((int) $this->fk_member) : 'null')." where rowid = ".((int) $this->id);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->error();
				$this->db->rollback();
				return -5;
			}

			if ($nbrowsaffected) {	// If something has changed in data
				if ($this->fk_member > 0 && !$nosyncmember) {
					dol_syslog(get_class($this)."::update user is linked with a member. We try to update member too.", LOG_DEBUG);

					require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

					// This user is linked with a member, so we also update member information
					// if this is an update.
					$adh = new Adherent($this->db);
					$result = $adh->fetch($this->fk_member);

					if ($result > 0) {
						$adh->civility_code = $this->civility_code;
						$adh->firstname = $this->firstname;
						$adh->lastname = $this->lastname;
						$adh->login = $this->login;
						$adh->gender = $this->gender;
						$adh->birth = $this->birth;

						$adh->pass = $this->pass;

						$adh->address = $this->address;
						$adh->town = $this->town;
						$adh->zip = $this->zip;
						$adh->state_id = $this->state_id;
						$adh->country_id = $this->country_id;

						$adh->email = $this->email;

						$adh->socialnetworks = $this->socialnetworks;

						$adh->phone = $this->office_phone;
						$adh->phone_mobile = $this->user_mobile;

						$adh->default_lang = $this->lang;

						$adh->user_id = $this->id;
						$adh->user_login = $this->login;

						$result = $adh->update($user, 0, 1, 0);
						if ($result < 0) {
							$this->error = $adh->error;
							$this->errors = $adh->errors;
							dol_syslog(get_class($this)."::update error after calling adh->update to sync it with user: ".$this->error, LOG_ERR);
							$error++;
						}
					} elseif ($result < 0) {
						$this->error = $adh->error;
						$this->errors = $adh->errors;
						$error++;
					}
				}

				if ($this->contact_id > 0 && !$nosynccontact) {
					dol_syslog(get_class($this)."::update user is linked with a contact. We try to update contact too.", LOG_DEBUG);

					require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

					// This user is linked with a contact, so we also update contact information if this is an update.
					$tmpobj = new Contact($this->db);
					$result = $tmpobj->fetch($this->contact_id);

					if ($result >= 0) {
						$tmpobj->civility_code = $this->civility_code;
						$tmpobj->firstname = $this->firstname;
						$tmpobj->lastname = $this->lastname;
						$tmpobj->login = $this->login;
						$tmpobj->gender = $this->gender;
						$tmpobj->birth = $this->birth;

						//$tmpobj->pass=$this->pass;

						$tmpobj->email = $this->email;

						$tmpobj->socialnetworks = $this->socialnetworks;

						$tmpobj->phone_pro = $this->office_phone;
						$tmpobj->phone_mobile = $this->user_mobile;
						$tmpobj->fax = $this->office_fax;

						$tmpobj->default_lang = $this->lang;

						$tmpobj->address = $this->address;
						$tmpobj->town = $this->town;
						$tmpobj->zip = $this->zip;
						$tmpobj->state_id = $this->state_id;
						$tmpobj->country_id = $this->country_id;

						$tmpobj->user_id = $this->id;
						$tmpobj->user_login = $this->login;

						$result = $tmpobj->update($tmpobj->id, $user, 0, 'update', 1);
						if ($result < 0) {
							$this->error = $tmpobj->error;
							$this->errors = $tmpobj->errors;
							dol_syslog(get_class($this)."::update error after calling adh->update to sync it with user: ".$this->error, LOG_ERR);
							$error++;
						}
					} else {
						$this->error = $tmpobj->error;
						$this->errors = $tmpobj->errors;
						$error++;
					}
				}
			}

			$action = 'update';

			// Actions on extra fields
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('USER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return $nbrowsaffected;
			} else {
				dol_syslog(get_class($this)."::update error=".$this->error, LOG_ERR);
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -2;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update the user's last login date in the database.
	 *  Function called when a new connection is made by the user
	 *
	 *  @return int     Return integer <0 si echec, >=0 si ok
	 */
	public function update_last_login_date()
	{
		// phpcs:enable
		$now = dol_now();

		$userremoteip = getUserRemoteIP();

		$sql = "UPDATE ".$this->db->prefix()."user SET";
		$sql .= " datepreviouslogin = datelastlogin,";
		$sql .= " ippreviouslogin = iplastlogin,";
		$sql .= " datelastlogin = '".$this->db->idate($now)."',";
		$sql .= " iplastlogin = '".$this->db->escape($userremoteip)."',";
		$sql .= " tms = tms"; // The last update date must change because the last login date is updated
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::update_last_login_date user->id=".$this->id." ".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->datepreviouslogin = $this->datelastlogin;
			$this->datelastlogin = $now;
			$this->ippreviouslogin = $this->iplastlogin;
			$this->iplastlogin = $userremoteip;
			return 1;
		} else {
			$this->error = $this->db->lasterror().' sql='.$sql;
			return -1;
		}
	}


	/**
	 *  Change password of a user
	 *
	 *  @param	User	$user             		Object user of user requesting the change (not the user for who we change the password). May be unknown.
	 *  @param  string	$password         		New password, in clear text or already encrypted (to generate if not provided)
	 *	@param	int		$changelater			0=Default, 1=Save password into pass_temp to change password only after clicking on confirm email
	 *	@param	int		$notrigger				1=Does not launch triggers
	 *	@param	int		$nosyncmember	        Do not synchronize linked member
	 *  @param	int		$passwordalreadycrypted 0=Value is cleartext password, 1=Value is encrypted value.
	 *  @param	int		$flagdelsessionsbefore  1=Save also the current date to ask to invalidate all other session before this date.
	 *  @return int|string		          		If OK return clear password, 0 if no change (warning, you may retrieve 1 instead of 0 even if password was same), < 0 if error
	 */
	public function setPassword($user, $password = '', $changelater = 0, $notrigger = 0, $nosyncmember = 0, $passwordalreadycrypted = 0, $flagdelsessionsbefore = 1)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';

		$error = 0;

		dol_syslog(get_class($this)."::setPassword user=".$user->id." password=".preg_replace('/./i', '*', $password)." changelater=".$changelater." notrigger=".$notrigger." nosyncmember=".$nosyncmember, LOG_DEBUG);

		// If new password not provided, we generate one
		if (!$password) {
			$password = getRandomPassword(false);
		}

		// Check and encrypt the password
		if (empty($passwordalreadycrypted)) {
			if (getDolGlobalString('USER_PASSWORD_GENERATED')) {
				// Add a check on rules for password syntax using the setup of the password generator
				$modGeneratePassClass = 'modGeneratePass'.ucfirst($conf->global->USER_PASSWORD_GENERATED);

				include_once DOL_DOCUMENT_ROOT.'/core/modules/security/generate/'.$modGeneratePassClass.'.class.php';
				if (class_exists($modGeneratePassClass)) {
					$modGeneratePass = new $modGeneratePassClass($this->db, $conf, $langs, $user);

					// To check an input user password, we disable the cleaning on ambiguous characters (this is used only for auto-generated password)
					$modGeneratePass->WithoutAmbi = 0;

					// Call to validatePassword($password) to check pass match rules
					$testpassword = $modGeneratePass->validatePassword($password);
					if (!$testpassword) {
						$this->error = $modGeneratePass->error;
						return -1;
					}
				}
			}


			// Now, we encrypt the new password
			$password_crypted = dol_hash($password);
		}

		// Update password
		if (!$changelater) {
			if (!is_object($this->oldcopy)) {
				$this->oldcopy = clone $this;
			}

			$this->db->begin();

			$sql = "UPDATE ".$this->db->prefix()."user";
			$sql .= " SET pass_crypted = '".$this->db->escape($password_crypted)."',";
			$sql .= " pass_temp = null";
			if (!empty($flagdelsessionsbefore)) {
				$sql .= ", flagdelsessionsbefore = '".$this->db->idate(dol_now() - 5, 'gmt')."'";
			}
			if (getDolGlobalString('DATABASE_PWD_ENCRYPTED')) {
				$sql .= ", pass = null";
			} else {
				$sql .= ", pass = '".$this->db->escape($password)."'";
			}
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::setPassword", LOG_DEBUG);
			$result = $this->db->query($sql);
			if ($result) {
				if ($this->db->affected_rows($result)) {
					$this->pass = $password;
					$this->pass_indatabase = $password;
					$this->pass_indatabase_crypted = $password_crypted;

					if ($this->fk_member && !$nosyncmember) {
						require_once DOL_DOCUMENT_ROOT.'/adherents/class/adherent.class.php';

						// This user is linked with a member, so we also update members information
						// if this is an update.
						$adh = new Adherent($this->db);
						$result = $adh->fetch($this->fk_member);

						if ($result >= 0) {
							$result = $adh->setPassword($user, $this->pass, (!getDolGlobalString('DATABASE_PWD_ENCRYPTED') ? 0 : 1), 1); // The encryption is not managed in the 'adherent' module
							if (is_int($result) && $result < 0) {
								$this->error = $adh->error;
								dol_syslog(get_class($this)."::setPassword ".$this->error, LOG_ERR);
								$error++;
							}
						} else {
							$this->error = $adh->error;
							$error++;
						}
					}

					dol_syslog(get_class($this)."::setPassword notrigger=".$notrigger." error=".$error, LOG_DEBUG);

					if (!$error && !$notrigger) {
						// Call trigger
						$result = $this->call_trigger('USER_NEW_PASSWORD', $user);
						if ($result < 0) {
							$error++;
							$this->db->rollback();
							return -1;
						}
						// End call triggers
					}

					$this->db->commit();
					return $this->pass;
				} else {
					$this->db->rollback();
					return 0;
				}
			} else {
				$this->db->rollback();
				dol_print_error($this->db);
				return -1;
			}
		} else {
			// We store password in password temporary field.
			// After receiving confirmation link, we will erase and store it in pass_crypted
			$sql = "UPDATE ".$this->db->prefix()."user";
			$sql .= " SET pass_temp = '".$this->db->escape($password)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::setPassword", LOG_DEBUG); // No log
			$result = $this->db->query($sql);
			if ($result) {
				return $password;
			} else {
				dol_print_error($this->db);
				return -3;
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Send a new password (or instructions to reset it) by email
	 *
	 *  @param	User	$user           Object user that send the email (not the user we send to) @todo object $user is not used !
	 *  @param	string	$password       New password
	 *	@param	int		$changelater	0=Send clear passwod into email, 1=Change password only after clicking on confirm email. @todo Add method 2 = Send link to reset password
	 *  @return int 		            Return integer < 0 si erreur, > 0 si ok
	 */
	public function send_password($user, $password = '', $changelater = 0)
	{
		// phpcs:enable
		global $conf, $langs, $mysoc;
		global $dolibarr_main_url_root;

		require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';

		$msgishtml = 0;

		// Define $msg
		$mesg = '';

		$outputlangs = new Translate("", $conf);

		if (isset($this->conf->MAIN_LANG_DEFAULT)
			&& $this->conf->MAIN_LANG_DEFAULT != 'auto') {	// If user has defined its own language (rare because in most cases, auto is used)
			$outputlangs->getDefaultLang($this->conf->MAIN_LANG_DEFAULT);
		}

		if ($this->conf->MAIN_LANG_DEFAULT) {
			$outputlangs->setDefaultLang($this->conf->MAIN_LANG_DEFAULT);
		} else {	// If user has not defined its own language, we used current language
			$outputlangs = $langs;
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "errors", "users", "other"));

		$appli = getDolGlobalString('MAIN_APPLICATION_TITLE', constant('DOL_APPLICATION_TITLE'));

		$subject = '['.$appli.'] '.$outputlangs->transnoentitiesnoconv("SubjectNewPassword", $appli);

		// Define $urlwithroot
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file

		if (!$changelater) {
			$url = $urlwithroot.'/';
			if (getDolGlobalString('URL_REDIRECTION_AFTER_CHANGEPASSWORD')) {
				$url = getDolGlobalString('URL_REDIRECTION_AFTER_CHANGEPASSWORD');
			}

			dol_syslog(get_class($this)."::send_password changelater is off, url=".$url);

			$mesg .= $outputlangs->transnoentitiesnoconv("RequestToResetPasswordReceived").".\n";
			$mesg .= $outputlangs->transnoentitiesnoconv("NewKeyIs")." :\n\n";
			$mesg .= $outputlangs->transnoentitiesnoconv("Login")." = ".$this->login."\n";
			$mesg .= $outputlangs->transnoentitiesnoconv("Password")." = ".$password."\n\n";
			$mesg .= "\n";

			$mesg .= $outputlangs->transnoentitiesnoconv("ClickHereToGoTo", $appli).': '.$url."\n\n";
			$mesg .= "--\n";
			$mesg .= $user->getFullName($outputlangs); // Username that send the email (not the user for who we want to reset password)
		} else {
			//print $password.'-'.$this->id.'-'.$conf->file->instance_unique_id;
			$url = $urlwithroot.'/user/passwordforgotten.php?action=validatenewpassword';
			$url .= '&username='.urlencode($this->login)."&passworduidhash=".urlencode(dol_hash($password.'-'.$this->id.'-'.$conf->file->instance_unique_id));
			if (isModEnabled('multicompany')) {
				$url .= '&entity='.(!empty($this->entity) ? $this->entity : 1);
			}

			dol_syslog(get_class($this)."::send_password changelater is on, url=".$url);

			$msgishtml = 1;

			$mesg .= $outputlangs->transnoentitiesnoconv("RequestToResetPasswordReceived")."<br>\n";
			$mesg .= $outputlangs->transnoentitiesnoconv("NewKeyWillBe")." :<br>\n<br>\n";
			$mesg .= '<strong>'.$outputlangs->transnoentitiesnoconv("Login")."</strong> = ".$this->login."<br>\n";
			$mesg .= '<strong>'.$outputlangs->transnoentitiesnoconv("Password")."</strong> = ".$password."<br>\n<br>\n";
			$mesg .= "<br>\n";
			$mesg .= $outputlangs->transnoentitiesnoconv("YouMustClickToChange")." :<br>\n";
			$mesg .= '<a href="'.$url.'" rel="noopener">'.$outputlangs->transnoentitiesnoconv("ConfirmPasswordChange").'</a>'."<br>\n<br>\n";
			$mesg .= $outputlangs->transnoentitiesnoconv("ForgetIfNothing")."<br>\n<br>\n";
		}

		$trackid = 'use'.$this->id;
		$sendcontext = 'passwordreset';

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
			$msgishtml,
			'',
			'',
			$trackid,
			'',
			$sendcontext
		);

		if ($mailfile->sendfile()) {
			return 1;
		} else {
			$langs->trans("errors");
			$this->error = $langs->trans("ErrorFailedToSendPassword").' '.$mailfile->error;
			return -1;
		}
	}

	/**
	 * 		Renvoie la derniere erreur fonctionnelle de manipulation de l'objet
	 *
	 * 		@return    string      chaine erreur
	 */
	public function error()
	{
		return $this->error;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Read clicktodial information for user
	 *
	 *  @return int Return integer <0 if KO, >0 if OK
	 */
	public function fetch_clicktodial()
	{
		// phpcs:enable
		$sql = "SELECT url, login, pass, poste ";
		$sql .= " FROM ".$this->db->prefix()."user_clicktodial as u";
		$sql .= " WHERE u.fk_user = ".((int) $this->id);

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->clicktodial_url = $obj->url;
				$this->clicktodial_login = $obj->login;
				$this->clicktodial_password = $obj->pass;
				$this->clicktodial_poste = $obj->poste;
			}

			$this->clicktodial_loaded = 1; // Data loaded (found or not)

			$this->db->free($resql);
			return 1;
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update clicktodial info
	 *
	 *  @return	int  Return integer <0 if KO, >0 if OK
	 */
	public function update_clicktodial()
	{
		// phpcs:enable
		$this->db->begin();

		$sql = "DELETE FROM ".$this->db->prefix()."user_clicktodial";
		$sql .= " WHERE fk_user = ".((int) $this->id);

		dol_syslog(get_class($this).'::update_clicktodial', LOG_DEBUG);
		$result = $this->db->query($sql);

		$sql = "INSERT INTO ".$this->db->prefix()."user_clicktodial";
		$sql .= " (fk_user,url,login,pass,poste)";
		$sql .= " VALUES (".$this->id;
		$sql .= ", '".$this->db->escape($this->clicktodial_url)."'";
		$sql .= ", '".$this->db->escape($this->clicktodial_login)."'";
		$sql .= ", '".$this->db->escape($this->clicktodial_password)."'";
		$sql .= ", '".$this->db->escape($this->clicktodial_poste)."')";

		dol_syslog(get_class($this).'::update_clicktodial', LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Add user into a group
	 *
	 *  @param	int		$group      Id of group
	 *  @param  int		$entity     Entity
	 *  @param  int		$notrigger  Disable triggers
	 *  @return int  				Return integer <0 if KO, >0 if OK
	 */
	public function SetInGroup($group, $entity, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".$this->db->prefix()."usergroup_user";
		$sql .= " WHERE fk_user  = ".((int) $this->id);
		$sql .= " AND fk_usergroup = ".((int) $group);
		$sql .= " AND entity = ".((int) $entity);

		$result = $this->db->query($sql);

		$sql = "INSERT INTO ".$this->db->prefix()."usergroup_user (entity, fk_user, fk_usergroup)";
		$sql .= " VALUES (".((int) $entity).",".((int) $this->id).",".((int) $group).")";

		$result = $this->db->query($sql);
		if ($result) {
			if (!$error && !$notrigger) {
				$this->context = array('audit' => $langs->trans("UserSetInGroup"), 'newgroupid' => $group);

				// Call trigger
				$result = $this->call_trigger('USER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::SetInGroup ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Remove a user from a group
	 *
	 *  @param	int   	$group       Id of group
	 *  @param  int		$entity      Entity
	 *  @param  int		$notrigger   Disable triggers
	 *  @return int  			     Return integer <0 if KO, >0 if OK
	 */
	public function RemoveFromGroup($group, $entity, $notrigger = 0)
	{
		// phpcs:enable
		global $conf, $langs, $user;

		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".$this->db->prefix()."usergroup_user";
		$sql .= " WHERE fk_user  = ".((int) $this->id);
		$sql .= " AND fk_usergroup = ".((int) $group);
		if (empty($entity)) {
			$sql .= " AND entity IN (0, 1)";	// group may be in entity 0 (so $entity=0) and link with user into entity 1.
		} else {
			$sql .= " AND entity = ".((int) $entity);
		}

		$result = $this->db->query($sql);
		if ($result) {
			if (!$error && !$notrigger) {
				$this->context = array('audit' => $langs->trans("UserRemovedFromGroup"), 'oldgroupid' => $group);

				// Call trigger
				$result = $this->call_trigger('USER_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				dol_syslog(get_class($this)."::RemoveFromGroup ".$this->error, LOG_ERR);
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *  Return a link with photo
	 * 	Use this->id,this->photo
	 *
	 *	@return	int		0=Valid, >0 if not valid
	 */
	public function isNotIntoValidityDateRange()
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		$now = dol_now();

		//dol_syslog("isNotIntoValidityDateRange ".$this->datestartvalidity);

		// Check date start validity
		if ($this->datestartvalidity && $this->datestartvalidity > dol_get_last_hour($now)) {
			return 1;
		}
		// Check date end validity
		if ($this->dateendvalidity && $this->dateendvalidity < dol_get_first_hour($now)) {
			return 1;
		}

		return 0;
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
	 * 	@see getImagePublicURLOfObject()
	 */
	public function getPhotoUrl($width, $height, $cssclass = '', $imagesize = '')
	{
		$result = '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$this->id.'">';
		$result .= Form::showphoto('userphoto', $this, $width, $height, 0, $cssclass, $imagesize);
		$result .= '</a>';

		return $result;
	}

	/**
	 * Return array of data to show into tooltips
	 *
	 * @param array $params 	Array with options, infologin
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $menumanager;
		global $dolibarr_main_demo;

		$infologin = $params['infologin'] ?? 0;
		$option = $params['option'] ?? '';

		$data = [];
		if (!empty($this->photo)) {
			$photo = '<div class="photointooltip floatright">';
			$photo .= Form::showphoto('userphoto', $this, 0, 60, 0, 'photoref photowithmargin photologintooltip', 'small', 0, 1); // Force height to 60 so we total height of tooltip can be calculated and collision can be managed
			$photo .= '</div>';
			$data['photo'] = $photo;
			//$label .= '<div style="clear: both;"></div>';
		}

		// Info Login
		$data['opendiv'] = '<div class="centpercent divtooltip">';
		$data['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("User").'</u> '.$this->getLibStatut(4);
		$data['name'] = '<br><b>'.$langs->trans('Name').':</b> '.dol_string_nohtmltag($this->getFullName($langs, ''));
		if (!empty($this->login)) {
			$data['login'] = '<br><b>'.$langs->trans('Login').':</b> '.dol_string_nohtmltag($this->login);
		}
		if (!empty($this->job)) {
			$data['job'] = '<br><b>'.$langs->trans("Job").':</b> '.dol_string_nohtmltag($this->job);
		}
		$data['email'] = '<br><b>'.$langs->trans("Email").':</b> '.dol_string_nohtmltag($this->email);
		if (!empty($this->office_phone) || !empty($this->office_fax) || !empty($this->fax)) {
			$phonelist = array();
			if ($this->office_phone) {
				$phonelist[] = dol_print_phone($this->office_phone, $this->country_code, $this->id, 0, '', '&nbsp', 'phone');
			}
			if ($this->office_fax) {
				$phonelist[] = dol_print_phone($this->office_fax, $this->country_code, $this->id, 0, '', '&nbsp', 'fax');
			}
			if ($this->user_mobile) {
				$phonelist[] = dol_print_phone($this->user_mobile, $this->country_code, $this->id, 0, '', '&nbsp', 'mobile');
			}
			$data['phones'] = '<br><b>'.$langs->trans('Phone').':</b> '.implode('&nbsp;', $phonelist);
		}
		if (!empty($this->admin)) {
			$data['administrator'] = '<br><b>'.$langs->trans("Administrator").'</b>: '.yn($this->admin);
		}
		if (!empty($this->accountancy_code) || $option == 'accountancy') {
			$langs->load("companies");
			$data['accountancycode'] = '<br><b>'.$langs->trans("AccountancyCode").'</b>: '.$this->accountancy_code;
		}
		$company = '';
		if (!empty($this->socid)) {	// Add thirdparty for external users
			$thirdpartystatic = new Societe($this->db);
			$thirdpartystatic->fetch($this->socid);
			$companyimg = '';
			if (empty($params['hidethirdpartylogo'])) {
				$companyimg = ' '.$thirdpartystatic->getNomUrl(2, 'nolink', 0, 1); // picto only of company
			}
			$company = ' ('.$langs->trans("Company").': '.($companyimg ? $companyimg : img_picto('', 'company')).' '.dol_string_nohtmltag($thirdpartystatic->name).')';
		}
		$type = ($this->socid ? $langs->trans("ExternalUser").$company : $langs->trans("InternalUser"));
		$data['type'] = '<br><b>'.$langs->trans("Type").':</b> '.$type;
		$data['closediv'] = '</div>';

		if ($infologin > 0) {
			$data['newlinelogin'] = '<br>';
			$data['session'] = '<br><u>'.$langs->trans("Session").'</u>';
			$data['ip'] = '<br><b>'.$langs->trans("IPAddress").'</b>: '.dol_string_nohtmltag(getUserRemoteIP());
			if (getDolGlobalString('MAIN_MODULE_MULTICOMPANY')) {
				$data['multicompany'] = '<br><b>'.$langs->trans("ConnectedOnMultiCompany").':</b> '.$conf->entity.' (User entity '.$this->entity.')';
			}
			$data['authentication'] = '<br><b>'.$langs->trans("AuthenticationMode").':</b> '.dol_string_nohtmltag($_SESSION["dol_authmode"].(empty($dolibarr_main_demo) ? '' : ' (demo)'));
			$data['connectedsince'] = '<br><b>'.$langs->trans("ConnectedSince").':</b> '.dol_print_date($this->datelastlogin, "dayhour", 'tzuser');
			$data['previousconnexion'] = '<br><b>'.$langs->trans("PreviousConnexion").':</b> '.dol_print_date($this->datepreviouslogin, "dayhour", 'tzuser');
			$data['currenttheme'] = '<br><b>'.$langs->trans("CurrentTheme").':</b> '.dol_string_nohtmltag($conf->theme);
			$data['currentmenumanager'] = '<br><b>'.$langs->trans("CurrentMenuManager").':</b> '.dol_string_nohtmltag($menumanager->name);
			$s = picto_from_langcode($langs->getDefaultLang());
			$data['currentuserlang'] = '<br><b>'.$langs->trans("CurrentUserLanguage").':</b> '.dol_string_nohtmltag(($s ? $s.' ' : '').$langs->getDefaultLang());
			$data['browser'] = '<br><b>'.$langs->trans("Browser").':</b> '.dol_string_nohtmltag($conf->browser->name.($conf->browser->version ? ' '.$conf->browser->version : '').' ('.$_SERVER['HTTP_USER_AGENT'].')');
			$data['layout'] = '<br><b>'.$langs->trans("Layout").':</b> '.dol_string_nohtmltag($conf->browser->layout);
			$data['screen'] = '<br><b>'.$langs->trans("Screen").':</b> '.dol_string_nohtmltag($_SESSION['dol_screenwidth'].' x '.$_SESSION['dol_screenheight']);
			if ($conf->browser->layout == 'phone') {
				$data['phone'] = '<br><b>'.$langs->trans("Phone").':</b> '.$langs->trans("Yes");
			}
			if (!empty($_SESSION["disablemodules"])) {
				$data['disabledmodules'] = '<br><b>'.$langs->trans("DisabledModules").':</b> <br>'.dol_string_nohtmltag(implode(', ', explode(',', $_SESSION["disablemodules"])));
			}
		}

		return $data;
	}

	/**
	 *  Return a HTML link to the user card (with optionally the picto)
	 * 	Use this->id,this->lastname, this->firstname
	 *
	 *	@param	int		$withpictoimg				Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto, -1=Include photo into link, -2=Only picto photo, -3=Only photo very small)
	 *	@param	string	$option						On what the link point to ('leave', 'accountancy', 'nolink', )
	 *  @param  integer $infologin      			0=Add default info tooltip, 1=Add complete info tooltip, -1=No info tooltip
	 *  @param	integer	$notooltip					1=Disable tooltip on picto and name
	 *  @param	int		$maxlen						Max length of visible user name
	 *  @param	int		$hidethirdpartylogo			Hide logo of thirdparty if user is external user
	 *  @param  string  $mode               		''=Show firstname and lastname, 'firstname'=Show only firstname, 'firstelselast'=Show firstname or lastname if not defined, 'login'=Show login
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpictoimg = 0, $option = '', $infologin = 0, $notooltip = 0, $maxlen = 24, $hidethirdpartylogo = 0, $mode = '', $morecss = '', $save_lastsearch_value = -1)
	{
		global $langs, $hookmanager, $user;

		if (!$user->hasRight('user', 'user', 'read') && $user->id != $this->id) {
			$option = 'nolink';
		}

		if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') && $withpictoimg) {
			$withpictoimg = 0;
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'infologin' => $infologin,
			'option' => $option,
			'hidethirdpartylogo' => $hidethirdpartylogo,
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

		$companylink = '';
		if (!empty($this->socid)) {	// Add thirdparty for external users
			$thirdpartystatic = new Societe($this->db);
			$thirdpartystatic->fetch($this->socid);
			if (empty($hidethirdpartylogo)) {
				$companylink = ' '.$thirdpartystatic->getNomUrl(2, 'nolink', 0, 1); // picto only of company
			}
		}

		if ($infologin < 0) {
			$label = '';
		}

		$url = DOL_URL_ROOT.'/user/card.php?id='.$this->id;
		if ($option == 'leave') {
			$url = DOL_URL_ROOT.'/holiday/list.php?id='.$this->id;
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

		$linkstart = '<a href="'.$url.'"';
		$linkclose = "";
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$langs->load("users");
				$label = $langs->trans("ShowUser");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams . ' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		//if ($withpictoimg == -1) $result.='<div class="nowrap">';
		$result .= (($option == 'nolink') ? '' : $linkstart);
		if ($withpictoimg) {
			$paddafterimage = '';
			if (abs((int) $withpictoimg) == 1) {
				$paddafterimage = 'style="margin-'.($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right').': 3px;"';
			}
			// Only picto
			if ($withpictoimg > 0) {
				$picto = '<!-- picto user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'"><div class="valignmiddle userphoto inline-block center marginrightonlyshort"'.($paddafterimage ? ' '.$paddafterimage : '').'>'.img_object('', 'user', 'class=""', 0, 0, $notooltip ? 0 : 1).'</div></span>';
			} else {
				// Picto must be a photo
				$picto = '<!-- picto photo user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'"'.($paddafterimage ? ' '.$paddafterimage : '').'>'.Form::showphoto('userphoto', $this, 0, 0, 0, 'userphoto'.($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1).'</span>';
			}
			$result .= $picto;
		}
		if ($withpictoimg > -2 && $withpictoimg != 2) {
			if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$result .= '<span class="nopadding usertext'.((!isset($this->status) || $this->status) ? '' : ' strikefordisabled').($morecss ? ' '.$morecss : '').'">';
			}
			if ($mode == 'login') {
				$result .= dol_string_nohtmltag(dol_trunc($this->login, $maxlen));
			} else {
				$result .= dol_string_nohtmltag($this->getFullName($langs, '', ($mode == 'firstelselast' ? 3 : ($mode == 'firstname' ? 2 : -1)), $maxlen));
			}
			if (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$result .= '</span>';
			}
		}
		$result .= (($option == 'nolink') ? '' : $linkend);
		//if ($withpictoimg == -1) $result.='</div>';

		$result .= $companylink;

		global $action;
		$hookmanager->initHooks(array('userdao'));
		$parameters = array('id' => $this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Return clickable link of login (optionally with picto)
	 *
	 *	@param	int		$withpictoimg		Include picto into link (1=picto, -1=photo)
	 *	@param	string	$option				On what the link point to ('leave', 'accountancy', 'nolink', )
	 *  @param	integer	$notooltip			1=Disable tooltip on picto and name
	 *  @param  string  $morecss       		Add more css on link
	 *	@return	string						String with URL
	 */
	public function getLoginUrl($withpictoimg = 0, $option = '', $notooltip = 0, $morecss = '')
	{
		global $langs, $user;

		$result = '';

		$linkstart = '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$this->id.'">';
		$linkend = '</a>';

		//Check user's rights to see an other user
		if ((!$user->hasRight('user', 'user', 'lire') && $this->id != $user->id)) {
			$option = 'nolink';
		}

		if ($option == 'xxx') {
			$linkstart = '<a href="'.DOL_URL_ROOT.'/user/card.php?id='.$this->id.'">';
			$linkend = '</a>';
		}

		if ($option == 'nolink') {
			$linkstart = '';
			$linkend = '';
		}

		$result .= $linkstart;
		if ($withpictoimg) {
			$paddafterimage = '';
			if (abs($withpictoimg) == 1) {
				$paddafterimage = 'style="margin-'.($langs->trans("DIRECTION") == 'rtl' ? 'left' : 'right').': 3px;"';
			}
			// Only picto
			if ($withpictoimg > 0) {
				$picto = '<!-- picto user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'">'.img_object('', 'user', $paddafterimage.' '.($notooltip ? '' : 'class="paddingright classfortooltip"'), 0, 0, $notooltip ? 0 : 1).'</span>';
			} else {
				// Picto must be a photo
				$picto = '<!-- picto photo user --><span class="nopadding userimg'.($morecss ? ' '.$morecss : '').'"'.($paddafterimage ? ' '.$paddafterimage : '').'>'.Form::showphoto('userphoto', $this, 0, 0, 0, 'userphoto'.($withpictoimg == -3 ? 'small' : ''), 'mini', 0, 1).'</span>';
			}
			$result .= $picto;
		}
		$result .= $this->login;
		$result .= $linkend;

		return $result;
	}

	/**
	 *  Return the label of the status of user (active, inactive)
	 *
	 *  @param  int		$mode          0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return	string 			       Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut(isset($this->statut) ? (int) $this->statut : (int) $this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return the label of a status of user (active, inactive)
	 *
	 *  @param  int     $status         Id status
	 *  @param  int		$mode           0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *  @return string                  Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatus[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
			$this->labelStatusShort[self::STATUS_ENABLED] = $langs->transnoentitiesnoconv('Enabled');
			$this->labelStatusShort[self::STATUS_DISABLED] = $langs->transnoentitiesnoconv('Disabled');
		}

		$statusType = 'status5';
		if ($status == self::STATUS_ENABLED) {
			$statusType = 'status4';
		}

		$label = $this->labelStatus[$status];
		$labelshort = $this->labelStatusShort[$status];

		$now = dol_now();
		if (!empty($this->datestartvalidity) && $now < $this->datestartvalidity) {
			$statusType = 'status3';
			$label .= ' ('.$langs->trans("UserNotYetValid").')';
		}
		if (!empty($this->dateendvalidity) && $now > ($this->dateendvalidity + 24 * 3600 - 1)) {
			$statusType = 'status2';
			$label .= ' ('.$langs->trans("UserExpired").')';
		}

		return dolGetStatus($label, $labelshort, '', $statusType, $mode);
	}


	/**
	 *	Return clicable link of object (optionally with picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array		$arraydata				Array of data
	 *  @return		string								HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '', $arraydata = null)
	{
		global $langs;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';

		$label = '';
		if (!empty($this->photo)) {
			//$label .= '<div class="photointooltip floatright">';
			$label .= Form::showphoto('userphoto', $this, 0, 60, 0, 'photokanban photoref photowithmargin photologintooltip', 'small', 0, 1); // Force height to 60 so we total height of tooltip can be calculated and collision can be managed
			//$label .= '</div>';
			//$label .= '<div style="clear: both;"></div>';
			$return .= $label;
		} else {
			$return .= img_picto('', $this->picto);
		}

		//$return .= '<i class="fa fa-dol-action"></i>'; // Can be image
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(0, '', 0, 0, 24, 0, '', 'valignmiddle') : $this->ref);
		if (isModEnabled('multicompany') && $this->admin && !$this->entity) {
			$return .= img_picto($langs->trans("SuperAdministratorDesc"), 'redstar', 'class="valignmiddle paddingright paddingleft"');
		} elseif ($this->admin) {
			$return .= img_picto($langs->trans("AdministratorDesc"), 'star', 'class="valignmiddle paddingright paddingleft"');
		}
		$return .= '</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'label')) {
			$return .= '<br><span class="info-box-label opacitymedium">'.$this->label.'</span>';
		}
		if ($this->email) {
			$return .= '<br><span class="info-box-label opacitymedium small">'.img_picto('', 'email').' '.$this->email.'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Retourne chaine DN complete dans l'annuaire LDAP pour l'objet
	 *
	 *	@param	array	$info		Info array loaded by _load_ldap_info
	 *	@param	int		$mode		0=Return full DN (uid=qqq,ou=xxx,dc=aaa,dc=bbb)
	 *								1=Return parent (ou=xxx,dc=aaa,dc=bbb)
	 *								2=Return key only (RDN) (uid=qqq)
	 *	@return	string				DN
	 */
	public function _load_ldap_dn($info, $mode = 0)
	{
		// phpcs:enable
		global $conf;
		$dn = '';
		if ($mode == 0) {
			$dn = getDolGlobalString('LDAP_KEY_USERS') . "=".$info[getDolGlobalString('LDAP_KEY_USERS')]."," . getDolGlobalString('LDAP_USER_DN');
		} elseif ($mode == 1) {
			$dn = getDolGlobalString('LDAP_USER_DN');
		} elseif ($mode == 2) {
			$dn = getDolGlobalString('LDAP_KEY_USERS') . "=".$info[getDolGlobalString('LDAP_KEY_USERS')];
		}
		return $dn;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Initialize the info array (array of LDAP values) that will be used to call LDAP functions
	 *
	 *	@return		array		Table with attribute information
	 */
	public function _load_ldap_info()
	{
		// phpcs:enable
		global $conf, $langs;

		$info = array();

		$socialnetworks = getArrayOfSocialNetworks();

		$keymodified = false;

		// Object classes
		$info["objectclass"] = explode(',', getDolGlobalString('LDAP_USER_OBJECT_CLASS'));

		$this->fullname = $this->getFullName($langs);

		// Possible LDAP KEY (constname => varname)
		$ldapkey = array(
			'LDAP_FIELD_FULLNAME'	=> 'fullname',
			'LDAP_FIELD_NAME'		=> 'lastname',
			'LDAP_FIELD_FIRSTNAME'	=> 'firstname',
			'LDAP_FIELD_LOGIN'		=> 'login',
			'LDAP_FIELD_LOGIN_SAMBA' => 'login',
			'LDAP_FIELD_PHONE'		=> 'office_phone',
			'LDAP_FIELD_MOBILE'		=> 'user_mobile',
			'LDAP_FIELD_FAX'		=> 'office_fax',
			'LDAP_FIELD_MAIL'		=> 'email',
			'LDAP_FIELD_SID'		=> 'ldap_sid',
		);

		// Champs
		foreach ($ldapkey as $constname => $varname) {
			if (!empty($this->$varname) && getDolGlobalString($constname)) {
				$info[getDolGlobalString($constname)] = $this->$varname;

				// Check if it is the LDAP key and if its value has been changed
				if (getDolGlobalString('LDAP_KEY_USERS') && $conf->global->LDAP_KEY_USERS == getDolGlobalString($constname)) {
					if (is_object($this->oldcopy) && !$this->oldcopy->isEmpty() && $this->$varname != $this->oldcopy->$varname) {
						$keymodified = true; // For check if LDAP key has been modified
					}
				}
			}
		}
		foreach ($socialnetworks as $key => $value) {
			if (!empty($this->socialnetworks[$value['label']]) && getDolGlobalString('LDAP_FIELD_'.strtoupper($value['label']))) {
				$info[getDolGlobalString('LDAP_FIELD_'.strtoupper($value['label']))] = $this->socialnetworks[$value['label']];
			}
		}
		if ($this->address && getDolGlobalString('LDAP_FIELD_ADDRESS')) {
			$info[getDolGlobalString('LDAP_FIELD_ADDRESS')] = $this->address;
		}
		if ($this->zip && getDolGlobalString('LDAP_FIELD_ZIP')) {
			$info[getDolGlobalString('LDAP_FIELD_ZIP')] = $this->zip;
		}
		if ($this->town && getDolGlobalString('LDAP_FIELD_TOWN')) {
			$info[getDolGlobalString('LDAP_FIELD_TOWN')] = $this->town;
		}
		if ($this->note_public && getDolGlobalString('LDAP_FIELD_DESCRIPTION')) {
			$info[getDolGlobalString('LDAP_FIELD_DESCRIPTION')] = dol_string_nohtmltag($this->note_public, 2);
		}
		if ($this->socid > 0) {
			$soc = new Societe($this->db);
			$soc->fetch($this->socid);

			$info[getDolGlobalString('LDAP_FIELD_COMPANY')] = $soc->name;
			if ($soc->client == 1) {
				$info["businessCategory"] = "Customers";
			}
			if ($soc->client == 2) {
				$info["businessCategory"] = "Prospects";
			}
			if ($soc->fournisseur == 1) {
				$info["businessCategory"] = "Suppliers";
			}
		}

		// When password is modified
		if (!empty($this->pass)) {
			if (getDolGlobalString('LDAP_FIELD_PASSWORD')) {
				$info[getDolGlobalString('LDAP_FIELD_PASSWORD')] = $this->pass; // this->pass = unencrypted password
			}
			if (getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')) {
				$info[getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')] = dol_hash($this->pass, 'openldap'); // Create OpenLDAP password (see LDAP_PASSWORD_HASH_TYPE)
			}
		} elseif (getDolGlobalString('LDAP_SERVER_PROTOCOLVERSION') !== '3') {
			// Set LDAP password if possible
			// If ldap key is modified and LDAPv3 we use ldap_rename function for avoid lose encrypt password
			if (getDolGlobalString('DATABASE_PWD_ENCRYPTED')) {
				// Just for the default MD5 !
				if (!getDolGlobalString('MAIN_SECURITY_HASH_ALGO')) {
					if ($this->pass_indatabase_crypted && getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')) {
						$info[getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')] = dolGetLdapPasswordHash($this->pass_indatabase_crypted, 'md5frommd5'); // Create OpenLDAP MD5 password from Dolibarr MD5 password
					}
				}
			} elseif (!empty($this->pass_indatabase)) {
				// Use $this->pass_indatabase value if exists
				if (getDolGlobalString('LDAP_FIELD_PASSWORD')) {
					$info[getDolGlobalString('LDAP_FIELD_PASSWORD')] = $this->pass_indatabase; // $this->pass_indatabase = unencrypted password
				}
				if (getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')) {
					$info[getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED')] = dol_hash($this->pass_indatabase, 'openldap'); // Create OpenLDAP password (see LDAP_PASSWORD_HASH_TYPE)
				}
			}
		}

		if (getDolGlobalString('LDAP_SERVER_TYPE') == 'egroupware') {
			$info["objectclass"][4] = "phpgwContact"; // compatibilite egroupware

			$info['uidnumber'] = $this->id;

			$info['phpgwTz'] = 0;
			$info['phpgwMailType'] = 'INTERNET';
			$info['phpgwMailHomeType'] = 'INTERNET';

			$info["phpgwContactTypeId"] = 'n';
			$info["phpgwContactCatId"] = 0;
			$info["phpgwContactAccess"] = "public";

			/*
			if (dol_strlen($this->egroupware_id) == 0) {
				$this->egroupware_id = 1;
			}
			$info["phpgwContactOwner"] = $this->egroupware_id;
			*/
			$info["phpgwContactOwner"] = 1;

			if ($this->email) {
				$info["rfc822Mailbox"] = $this->email;
			}
			if ($this->user_mobile) {
				$info["phpgwCellTelephoneNumber"] = $this->user_mobile;
			}
		}

		if (getDolGlobalString('LDAP_FIELD_USERID')) {
			$info[getDolGlobalString('LDAP_FIELD_USERID')] = $this->id;
		}
		if (getDolGlobalString('LDAP_FIELD_GROUPID')) {
			$usergroup = new UserGroup($this->db);
			$groupslist = $usergroup->listGroupsForUser($this->id);
			$info[getDolGlobalString('LDAP_FIELD_GROUPID')] = '65534';
			if (!empty($groupslist)) {
				foreach ($groupslist as $groupforuser) {
					$info[getDolGlobalString('LDAP_FIELD_GROUPID')] = $groupforuser->id; //Select first group in list
					break;
				}
			}
		}
		if (getDolGlobalString('LDAP_FIELD_HOMEDIRECTORY') && getDolGlobalString('LDAP_FIELD_HOMEDIRECTORYPREFIX')) {
			$info[getDolGlobalString('LDAP_FIELD_HOMEDIRECTORY')] = "{$conf->global->LDAP_FIELD_HOMEDIRECTORYPREFIX}/$this->login";
		}

		return $info;
	}


	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *	id must be 0 if object instance is a specimen.
	 *
	 *  @return	int
	 */
	public function initAsSpecimen()
	{
		global $user, $langs;

		$now = dol_now();

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->specimen = 1;

		$this->lastname = 'DOLIBARR';
		$this->firstname = 'SPECIMEN';
		$this->gender = 'man';
		$this->note_public = 'This is a note public';
		$this->note_private = 'This is a note private';
		$this->email = 'email@specimen.com';
		$this->personal_email = 'personalemail@specimen.com';
		$this->socialnetworks = array(
			'skype' => 'skypepseudo',
			'twitter' => 'twitterpseudo',
			'facebook' => 'facebookpseudo',
			'linkedin' => 'linkedinpseudo',
		);
		$this->office_phone = '0999999999';
		$this->office_fax = '0999999998';
		$this->user_mobile = '0999999997';
		$this->personal_mobile = '0999999996';
		$this->admin = 0;
		$this->login = 'dolibspec';
		$this->pass = 'dolibSpec+@123';
		//$this->pass_indatabase='dolibspec';									Set after a fetch
		//$this->pass_indatabase_crypted='e80ca5a88c892b0aaaf7e154853bccab';	Set after a fetch
		$this->datec = $now;
		$this->datem = $now;

		$this->datelastlogin = $now;
		$this->iplastlogin = '127.0.0.1';
		$this->datepreviouslogin = $now;
		$this->ippreviouslogin = '127.0.0.1';
		$this->statut = 1;		// deprecated
		$this->status = 1;

		$this->entity = 1;

		return 1;
	}

	/**
	 *  Load info of user object
	 *
	 *  @param  int		$id     Id of user to load
	 *  @return	void
	 */
	public function info($id)
	{
		$sql = "SELECT u.rowid, u.login as ref, u.datec,";
		$sql .= " u.tms as date_modification, u.entity";
		$sql .= " FROM ".$this->db->prefix()."user as u";
		$sql .= " WHERE u.rowid = ".((int) $id);

		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);

				$this->id = $obj->rowid;

				$this->ref = (!$obj->ref) ? $obj->rowid : $obj->ref;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->entity = $obj->entity;
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}


	/**
	 *    Return number of mass Emailing received by this contacts with its email
	 *
	 *    @return       int     Number of EMailings
	 */
	public function getNbOfEMailings()
	{
		$sql = "SELECT count(mc.email) as nb";
		$sql .= " FROM ".$this->db->prefix()."mailing_cibles as mc";
		$sql .= " WHERE mc.email = '".$this->db->escape($this->email)."'";
		$sql .= " AND mc.statut NOT IN (-1,0)"; // -1 error, 0 not sent, 1 sent with success

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = $obj->nb;

			$this->db->free($resql);
			return $nb;
		} else {
			$this->error = $this->db->error();
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
	public function getNbOfUsers($limitTo, $option = '', $admin = -1)
	{
		global $conf;

		$sql = "SELECT count(rowid) as nb";
		$sql .= " FROM ".$this->db->prefix()."user";
		if ($option == 'superadmin') {
			$sql .= " WHERE entity = 0";
		} else {
			$sql .= " WHERE entity IN (".getEntity('user', 0).")";
			if ($limitTo == 'active') {
				$sql .= " AND statut = 1";
			}
		}
		if ($admin >= 0) {
			$sql .= " AND admin = ".(int) $admin;
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			$nb = (int) $obj->nb;

			$this->db->free($resql);
			return $nb;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Update user using data from the LDAP
	 *
	 *  @param	Object	$ldapuser	Ladp User
	 *  @return int  				Return integer <0 if KO, >0 if OK
	 */
	public function update_ldap2dolibarr(&$ldapuser)
	{
		// phpcs:enable
		// TODO: Voir pourquoi le update met à jour avec toutes les valeurs vide (global $user écrase ?)
		global $user, $conf;

		$socialnetworks = getArrayOfSocialNetworks();

		$tmpvar = getDolGlobalString('LDAP_FIELD_FIRSTNAME');
		$this->firstname = $ldapuser->$tmpvar;
		$tmpvar = getDolGlobalString('LDAP_FIELD_NAME');
		$this->lastname = $ldapuser->$tmpvar;
		$tmpvar = getDolGlobalString('LDAP_FIELD_LOGIN');
		$this->login = $ldapuser->$tmpvar;
		$tmpvar = getDolGlobalString('LDAP_FIELD_PASSWORD');
		$this->pass = $ldapuser->$tmpvar;
		$tmpvar = getDolGlobalString('LDAP_FIELD_PASSWORD_CRYPTED');
		$this->pass_indatabase_crypted = $ldapuser->$tmpvar;

		$tmpvar = getDolGlobalString('LDAP_FIELD_PHONE');
		$this->office_phone = $ldapuser->$tmpvar;
		$tmpvar = getDolGlobalString('LDAP_FIELD_MOBILE');
		$this->user_mobile = $ldapuser->$tmpvar;
		$tmpvar = getDolGlobalString('LDAP_FIELD_FAX');
		$this->office_fax = $ldapuser->$tmpvar;
		$tmpvar = getDolGlobalString('LDAP_FIELD_MAIL');
		$this->email = $ldapuser->$tmpvar;
		foreach ($socialnetworks as $key => $value) {
			$tmpvar = getDolGlobalString('LDAP_FIELD_'.strtoupper($value['label']));
			$this->socialnetworks[$value['label']] = $ldapuser->$tmpvar;
		}
		$tmpvar = getDolGlobalString('LDAP_FIELD_SID');
		$this->ldap_sid = $ldapuser->$tmpvar;

		$tmpvar = getDolGlobalString('LDAP_FIELD_TITLE');
		$this->job = $ldapuser->$tmpvar;
		$tmpvar = getDolGlobalString('LDAP_FIELD_DESCRIPTION');
		$this->note_public = $ldapuser->$tmpvar;

		$result = $this->update($user);

		dol_syslog(get_class($this)."::update_ldap2dolibarr result=".$result, LOG_DEBUG);

		return $result;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return and array with all instantiated first level children users of current user
	 *
	 * @return	User[]|int<-1,-1>
	 * @see getAllChildIds()
	 */
	public function get_children()
	{
		// phpcs:enable
		$sql = "SELECT rowid FROM ".$this->db->prefix()."user";
		$sql .= " WHERE fk_user = ".((int) $this->id);

		dol_syslog(get_class($this)."::get_children", LOG_DEBUG);
		$res = $this->db->query($sql);
		if ($res) {
			$users = array();
			while ($rec = $this->db->fetch_array($res)) {
				$user = new User($this->db);
				$user->fetch($rec['rowid']);
				$users[] = $user;
			}
			return $users;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 *  Load this->parentof that is array(id_son=>id_parent, ...)
	 *
	 *  @return     int<-1,1>     Return integer <0 if KO, >0 if OK
	 */
	private function loadParentOf()
	{
		global $conf;

		$this->parentof = array();

		// Load array[child]=parent
		$sql = "SELECT fk_user as id_parent, rowid as id_son";
		$sql .= " FROM ".$this->db->prefix()."user";
		$sql .= " WHERE fk_user <> 0";
		$sql .= " AND entity IN (".getEntity('user').")";

		dol_syslog(get_class($this)."::loadParentOf", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->parentof[$obj->id_son] = $obj->id_parent;
			}
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Build the hierarchy/tree of users into an array.
	 *	Set and return this->users that is an array sorted according to tree with arrays of:
	 *				id = id user
	 *				lastname
	 *				firstname
	 *				fullname = Name with full path to user
	 *				fullpath = Full path composed of the ids: "_grandparentid_parentid_id"
	 *
	 *  @param      int		$deleteafterid      Removed all users including the leaf $deleteafterid (and all its child) in user tree.
	 *  @param		string	$filter				SQL filter on users. This parameter must not come from user input.
	 *	@return		int<-1,-1>|array<int,array{rowid:int,id:int,fk_user:int,fk_soc:int,firstname:string,lastname:string,login:string,statut:int,entity:int,email:string,gender:string|int<-1,-1>,admin:int<0,1>,photo:string,fullpath:string,fullname:string,level:int}>  Array of user information (also: $this->users). Note: $this->parentof is also set.
	 */
	public function get_full_tree($deleteafterid = 0, $filter = '')
	{
		// phpcs:enable
		global $conf, $user;
		global $hookmanager;

		// Actions hooked (by external module)
		$hookmanager->initHooks(array('userdao'));

		$this->users = array();

		// Init this->parentof that is array(id_son=>id_parent, ...)
		$this->loadParentOf();

		// Init $this->users array
		$sql = "SELECT DISTINCT u.rowid, u.firstname, u.lastname, u.fk_user, u.fk_soc, u.login, u.email, u.gender, u.admin, u.statut, u.photo, u.entity"; // Distinct reduce pb with old tables with duplicates
		$sql .= " FROM ".$this->db->prefix()."user as u";
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printUserListWhere', $parameters); // Note that $action and $object may have been modified by hook
		if ($reshook > 0) {
			$sql .= $hookmanager->resPrint;
		} else {
			$sql .= " WHERE u.entity IN (".getEntity('user').")";
		}
		if ($filter) {
			$sql .= " AND ".$filter;
		}

		dol_syslog(get_class($this)."::get_full_tree get user list", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$i = 0;
			while ($obj = $this->db->fetch_object($resql)) {
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
				// fields are filled with build_path_from_id_user
				$this->users[$obj->rowid]['fullpath'] = '';
				$this->users[$obj->rowid]['fullname'] = '';
				$this->users[$obj->rowid]['level'] = 0;
				$i++;
			}
		} else {
			dol_print_error($this->db);
			return -1;
		}

		// We add the fullpath property to each element of the first level (no parent exists)
		dol_syslog(get_class($this)."::get_full_tree call to build_path_from_id_user", LOG_DEBUG);
		foreach ($this->users as $key => $val) {
			$result = $this->build_path_from_id_user($key, 0); // Process a branch from the root user key (this user has no parent)
			if ($result < 0) {
				$this->error = 'ErrorLoopInHierarchy';
				return -1;
			}
		}

		// Exclude leaf including $deleteafterid from tree
		if ($deleteafterid) {
			//print "Look to discard user ".$deleteafterid."\n";
			$keyfilter1 = '^'.$deleteafterid.'$';
			$keyfilter2 = '_'.$deleteafterid.'$';
			$keyfilter3 = '^'.$deleteafterid.'_';
			$keyfilter4 = '_'.$deleteafterid.'_';
			foreach (array_keys($this->users) as $key) {
				$fullpath = (string) $this->users[$key]['fullpath'];
				if (preg_match('/'.$keyfilter1.'/', $fullpath) || preg_match('/'.$keyfilter2.'/', $fullpath)
					|| preg_match('/'.$keyfilter3.'/', $fullpath) || preg_match('/'.$keyfilter4.'/', $fullpath)) {
					unset($this->users[$key]);
				}
			}
		}

		dol_syslog(get_class($this)."::get_full_tree dol_sort_array", LOG_DEBUG);
		$this->users = dol_sort_array($this->users, 'fullname', 'asc', 1, 0, 1);

		//var_dump($this->users);

		return $this->users;
	}

	/**
	 * 	Return list of all child user ids in hierarchy (all sublevels).
	 *  Note: Calling this function also reset full list of users into $this->users.
	 *
	 *  @param      int      $addcurrentuser    1=Add also current user id to the list.
	 *	@return		array		      		  	Array of user id lower than user (all levels under user). This overwrite this->users.
	 *  @see get_children()
	 */
	public function getAllChildIds($addcurrentuser = 0)
	{
		$childids = array();

		if (isset($this->cache_childids[$this->id])) {
			$childids = $this->cache_childids[$this->id];
		} else {
			// Init this->users
			$this->get_full_tree();

			$idtoscan = $this->id;

			dol_syslog("Build childid for id = ".$idtoscan);
			foreach ($this->users as $id => $val) {
				//var_dump($val['fullpath']);
				if (preg_match('/_'.$idtoscan.'_/', $val['fullpath'])) {
					$childids[$val['id']] = $val['id'];
				}
			}
		}
		$this->cache_childids[$this->id] = $childids;

		if ($addcurrentuser) {
			$childids[$this->id] = $this->id;
		}

		return $childids;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	For user id_user and its children available in this->users, define property fullpath and fullname.
	 *  Function called by get_full_tree().
	 *
	 * 	@param		int		$id_user		id_user entry to update
	 * 	@param		int		$protection		Deep counter to avoid infinite loop (no more required, a protection is added with array useridfound)
	 *	@return		int<-1,1>               Return integer < 0 if KO (infinite loop), >= 0 if OK
	 */
	public function build_path_from_id_user($id_user, $protection = 0)
	{
		// phpcs:enable
		//dol_syslog(get_class($this)."::build_path_from_id_user id_user=".$id_user." protection=".$protection, LOG_DEBUG);

		if (!empty($this->users[$id_user]['fullpath'])) {
			// Already defined
			dol_syslog(get_class($this)."::build_path_from_id_user fullpath and fullname already defined", LOG_WARNING);
			return 0;
		}

		// Define fullpath and fullname
		$this->users[$id_user]['fullpath'] = '_'.$id_user;
		$this->users[$id_user]['fullname'] = $this->users[$id_user]['lastname'];
		$i = 0;
		$cursor_user = $id_user;

		$useridfound = array($id_user);
		while (!empty($this->parentof[$cursor_user]) && !empty($this->users[$this->parentof[$cursor_user]])) {
			if (in_array($this->parentof[$cursor_user], $useridfound)) {
				dol_syslog("The hierarchy of user has a recursive loop", LOG_WARNING);
				return -1; // Should not happen. Protection against looping hierarchy
			}
			$useridfound[] = $this->parentof[$cursor_user];
			$this->users[$id_user]['fullpath'] = '_'.$this->parentof[$cursor_user].$this->users[$id_user]['fullpath'];
			$this->users[$id_user]['fullname'] = $this->users[$this->parentof[$cursor_user]]['lastname'].' >> '.$this->users[$id_user]['fullname'];
			$i++;
			$cursor_user = $this->parentof[$cursor_user];
		}

		// We count number of _ to have level
		$this->users[$id_user]['level'] = dol_strlen(preg_replace('/[^_]/i', '', $this->users[$id_user]['fullpath']));

		return 1;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'user',
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}


	/**
	 *      Load metrics this->nb for dashboard
	 *
	 *      @return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $conf;

		$this->nb = array();

		$sql = "SELECT COUNT(DISTINCT u.rowid) as nb";
		$sql .= " FROM ".$this->db->prefix()."user as u";
		if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
			$sql .= ", ".$this->db->prefix()."usergroup_user as ug";
			$sql .= " WHERE ug.entity IN (".getEntity('usergroup').")";
			$sql .= " AND ug.fk_user = u.rowid";
		} else {
			$sql .= " WHERE u.entity IN (".getEntity('user').")";
		}
		$sql .= " AND u.statut > 0";
		//$sql.= " AND employee != 0";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["users"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
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
	 *  @param      null|array  $moreparams     Array to provide more information
	 * 	@return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $user, $langs;

		$langs->load("user");

		// Set the '$modele' to the name of the document template (model) to use
		if (!dol_strlen($modele)) {
			if (getDolGlobalString('USER_ADDON_PDF')) {
				$modele = getDolGlobalString('USER_ADDON_PDF');
			} else {
				$modele = 'bluesky';
			}
		}

		$modelpath = "core/modules/user/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return property of user from its id
	 *
	 *  @param	int		$rowid      id of contact
	 *  @param  string	$mode       'email', 'mobile', or 'name'
	 *  @return string  			Email of user with format: "Full name <email>"
	 */
	public function user_get_property($rowid, $mode)
	{
		// phpcs:enable
		$user_property = '';

		if (empty($rowid)) {
			return '';
		}

		$sql = "SELECT rowid, email, user_mobile, civility, lastname, firstname";
		$sql .= " FROM ".$this->db->prefix()."user";
		$sql .= " WHERE rowid = ".((int) $rowid);

		$resql = $this->db->query($sql);
		if ($resql) {
			$nump = $this->db->num_rows($resql);

			if ($nump) {
				$obj = $this->db->fetch_object($resql);

				if ($mode == 'email') {
					$user_property = dolGetFirstLastname($obj->firstname, $obj->lastname)." <".$obj->email.">";
				} elseif ($mode == 'mobile') {
					$user_property = $obj->user_mobile;
				} elseif ($mode == 'name') {
					$user_property = dolGetFirstLastname($obj->firstname, $obj->lastname);
				}
			}
			return $user_property;
		} else {
			dol_print_error($this->db);
		}

		return '';
	}

	/**
	 * Return string with full Url to virtual card
	 *
	 * @param	string		$mode		Mode for link
	 * @param	string		$typeofurl	'external' or 'internal'
	 * @return	string				    Url string link
	 */
	public function getOnlineVirtualCardUrl($mode = '', $typeofurl = 'external')
	{
		global $dolibarr_main_url_root;
		global $conf;

		$encodedsecurekey = dol_hash($conf->file->instance_unique_id.'uservirtualcard'.$this->id.'-'.$this->login, 'md5');
		if (isModEnabled('multicompany')) {
			$entity_qr = '&entity='.((int) $conf->entity);
		} else {
			$entity_qr = '';
		}
		// Define $urlwithroot
		$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
		$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
		//$urlwithroot=DOL_MAIN_URL_ROOT;					// This is to use same domain name than current

		if ($typeofurl == 'internal') {
			$urlwithroot = DOL_URL_ROOT;
		}

		return $urlwithroot.'/public/users/view.php?id='.$this->id.'&securekey='.$encodedsecurekey.$entity_qr.($mode ? '&mode='.urlencode($mode) : '');
	}

	/**
	 *	Load all objects into $this->users
	 *
	 *  @param	string		$sortorder		sort order
	 *  @param	string		$sortfield		sort field
	 *  @param	int			$limit			limit page
	 *  @param	int			$offset			page
	 * 	@param  string		$filter       	Filter as an Universal Search string.
	 * 										Example: '((client:=:1) OR ((client:>=:2) AND (client:<=:3))) AND (client:!=:8) AND (nom:like:'a%')'
	 * 	@param  string      $filtermode   	No more used
	 *  @param  bool        $entityfilter	Activate entity filter
	 *  @return int							Return integer <0 if KO, >0 if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, $filter = '', $filtermode = 'AND', $entityfilter = false)
	{
		global $conf, $user;

		$sql = "SELECT t.rowid";
		$sql .= ' FROM '.$this->db->prefix().$this->table_element.' as t ';

		if ($entityfilter) {
			if (getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
				if (!empty($user->admin) && empty($user->entity) && $conf->entity == 1) {
					$sql .= " WHERE t.entity IS NOT NULL"; // Show all users
				} else {
					$sql .= ",".$this->db->prefix()."usergroup_user as ug";
					$sql .= " WHERE ((ug.fk_user = t.rowid";
					$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
					$sql .= " OR t.entity = 0)"; // Show always superadmin
				}
			} else {
				$sql .= " WHERE t.entity IN (".getEntity('user').")";
			}
		} else {
			$sql .= " WHERE 1 = 1";
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(__METHOD__, LOG_DEBUG);

		$resql = $this->db->query($sql);
		if ($resql) {
			$this->users = array();
			$num = $this->db->num_rows($resql);
			if ($num) {
				while ($obj = $this->db->fetch_object($resql)) {
					$line = new self($this->db);
					$result = $line->fetch($obj->rowid);
					if ($result > 0 && !empty($line->id)) {
						$this->users[$obj->rowid] = clone $line;
					}
				}
				$this->db->free($resql);
			}
			return $num;
		} else {
			$this->errors[] = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Cache the SQL results of the function "findUserIdByEmail($email)"
	 *
	 * NOTE: findUserIdByEmailCache[...] === -1 means not found in database
	 *
	 * @var array
	 */
	private $findUserIdByEmailCache;

	/**
	 * Find a user by the given e-mail and return it's user id when found
	 *
	 * NOTE:
	 * Use AGENDA_DISABLE_EXACT_USER_EMAIL_COMPARE_FOR_EXTERNAL_CALENDAR
	 * to disable exact e-mail search
	 *
	 * @param string	$email	The full e-mail (or a part of a e-mail)
	 * @return int				Return integer <0 = user was not found, >0 = The id of the user
	 */
	public function findUserIdByEmail($email)
	{
		if (isset($this->findUserIdByEmailCache[$email])) {
			return $this->findUserIdByEmailCache[$email];
		}

		$this->findUserIdByEmailCache[$email] = -1;

		$sql = 'SELECT rowid';
		$sql .= ' FROM '.$this->db->prefix().'user';
		if (getDolGlobalString('AGENDA_DISABLE_EXACT_USER_EMAIL_COMPARE_FOR_EXTERNAL_CALENDAR')) {
			$sql .= " WHERE email LIKE '%".$this->db->escape($this->db->escapeforlike($email))."%'";
		} else {
			$sql .= " WHERE email = '".$this->db->escape($email)."'";
		}
		$sql .= ' LIMIT 1';

		$resql = $this->db->query($sql);
		if (!$resql) {
			return -1;
		}

		$obj = $this->db->fetch_object($resql);
		if (!$obj) {
			return -1;
		}

		$this->findUserIdByEmailCache[$email] = (int) $obj->rowid;

		return $this->findUserIdByEmailCache[$email];
	}
}
