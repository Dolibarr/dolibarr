<?php
/* Copyright (C) 2011		Dimitri Mouillard	<dmouillard@teclib.com>
 * Copyright (C) 2012-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2012-2016	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2013		Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2016       Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
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
 *    \file       holiday.class.php
 *    \ingroup    holiday
 *    \brief      Class file of the module paid holiday.
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *	Class of the module paid holiday. Developed by Teclib ( http://www.teclib.com/ )
 */
class Holiday extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'holiday';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'holiday';

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_holiday';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'holiday';

	/**
	 * @var int User ID
	 */
	public $fk_user;

	public $date_create = '';

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var int|string Date start in PHP server TZ
	 */
	public $date_debut = '';

	/**
	 * @var int|string Date end in PHP server TZ
	 */
	public $date_fin = '';

	/**
	 * @var int|string Date start in GMT
	 */
	public $date_debut_gmt = '';

	/**
	 * @var int|string Date end in GMT
	 */
	public $date_fin_gmt = '';

	/**
	 * @var int|string 0:Full days, 2:Start afternoon end morning, -1:Start afternoon end afternoon, 1:Start morning end morning
	 */
	public $halfday = '';

	/**
	 * @var int Status 1=draft, 2=validated, 3=approved, 4 canceled, 5 refused
	 * @deprecated
	 */
	public $statut = 0;

	/**
	 * @var int 	ID of user that must approve. Real user for approval is fk_user_valid (old version) or fk_user_approve (new versions)
	 */
	public $fk_validator;

	/**
	 * @var int 	Date of validation or approval. TODO: Use date_valid instead for validation.
	 */
	public $date_valid = 0;

	/**
	 * @var int 	ID of user that has validated
	 */
	public $fk_user_valid;

	/**
	 * @var int 	Date approval
	 */
	public $date_approval;

	/**
	 * @var int 	ID of user that has approved
	 */
	public $fk_user_approve;

	/**
	 * @var int 	Date for refuse
	 */
	public $date_refuse = 0;

	/**
	 * @var int 	ID for refuse
	 */
	public $fk_user_refuse;

	/**
	 * @var int 	Date for cancellation
	 */
	public $date_cancel = 0;

	/**
	 * @var int 	ID for cancellation
	 */
	public $fk_user_cancel;

	/**
	 * @var int 	ID for creation
	 */
	public $fk_user_create;

	/**
	 * @var string Detail of refuse
	 */
	public $detail_refuse = '';

	/**
	 * @var int ID
	 */
	public $fk_type;

	public $holiday = array();
	public $events = array();
	public $logs = array();

	public $optName = '';
	public $optValue = '';
	public $optRowid = '';

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 1;
	/**
	 * Validated status
	 */
	const STATUS_VALIDATED = 2;
	/**
	 * Approved
	 */
	const STATUS_APPROVED = 3;
	/**
	 * Canceled
	 */
	const STATUS_CANCELED = 4;
	/**
	 * Refused
	 */
	const STATUS_REFUSED = 5;


	/**
	 *   Constructor
	 *
	 *   @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 0;
	}


	/**
	 *  Returns the reference to the following non used Order depending on the active numbering module
	 *  defined into HOLIDAY_ADDON
	 *
	 *	@param	Societe		$objsoc     third party object
	 *  @return string      			Holiday free reference
	 */
	public function getNextNumRef($objsoc)
	{
		global $langs, $conf;
		$langs->load("order");

		if (!getDolGlobalString('HOLIDAY_ADDON')) {
			$conf->global->HOLIDAY_ADDON = 'mod_holiday_madonna';
		}

		if (getDolGlobalString('HOLIDAY_ADDON')) {
			$mybool = false;

			$file = getDolGlobalString('HOLIDAY_ADDON') . ".php";
			$classname = getDolGlobalString('HOLIDAY_ADDON');

			// Include file with class
			$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir."core/modules/holiday/");

				// Load file with numbering class (if found)
				$mybool = ((bool) @include_once $dir.$file) || $mybool;
			}

			if (!$mybool) {
				dol_print_error(null, "Failed to include file ".$file);
				return '';
			}

			$obj = new $classname();
			'@phan-var-force ModelNumRefHolidays $obj';
			$numref = $obj->getNextValue($objsoc, $this);

			if ($numref != "") {
				return $numref;
			} else {
				$this->error = $obj->error;
				//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
				return "";
			}
		} else {
			print $langs->trans("Error")." ".$langs->trans("Error_HOLIDAY_ADDON_NotDefined");
			return "";
		}
	}

	/**
	 * Update balance of vacations and check table of users for holidays is complete. If not complete.
	 *
	 * @return	int			Return integer <0 if KO, >0 if OK
	 */
	public function updateBalance()
	{
		$this->db->begin();

		// Update sold of vocations
		$result = $this->updateSoldeCP();

		// Check nb of users into table llx_holiday_users and update with empty lines
		//if ($result > 0) $result = $this->verifNbUsers($this->countActiveUsersWithoutCP(), $this->getConfCP('nbUser'));

		if ($result >= 0) {
			$this->db->commit();
			return 0; // for cronjob use (0 is OK, any other value is an error code)
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *   Créer un congés payés dans la base de données
	 *
	 *   @param		User	$user        	User that create
	 *   @param     int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *   @return    int			         	Return integer <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;
		$error = 0;

		$now = dol_now();

		// Check parameters
		if (empty($this->fk_user) || !is_numeric($this->fk_user) || $this->fk_user < 0) {
			$this->error = "ErrorBadParameterFkUser";
			return -1;
		}
		if (empty($this->fk_validator) || !is_numeric($this->fk_validator) || $this->fk_validator < 0) {
			$this->error = "ErrorBadParameterFkValidator";
			return -1;
		}
		if (empty($this->fk_type) || !is_numeric($this->fk_type) || $this->fk_type < 0) {
			$this->error = "ErrorBadParameterFkType";
			return -1;
		}

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday(";
		$sql .= "ref,";
		$sql .= "fk_user,";
		$sql .= "date_create,";
		$sql .= "description,";
		$sql .= "date_debut,";
		$sql .= "date_fin,";
		$sql .= "halfday,";
		$sql .= "statut,";
		$sql .= "fk_validator,";
		$sql .= "fk_type,";
		$sql .= "fk_user_create,";
		$sql .= "entity";
		$sql .= ") VALUES (";
		$sql .= "'(PROV)',";
		$sql .= " ".((int) $this->fk_user).",";
		$sql .= " '".$this->db->idate($now)."',";
		$sql .= " '".$this->db->escape($this->description)."',";
		$sql .= " '".$this->db->idate($this->date_debut)."',";
		$sql .= " '".$this->db->idate($this->date_fin)."',";
		$sql .= " ".((int) $this->halfday).",";
		$sql .= " '1',";
		$sql .= " ".((int) $this->fk_validator).",";
		$sql .= " ".((int) $this->fk_type).",";
		$sql .= " ".((int) $user->id).",";
		$sql .= " ".((int) $conf->entity);
		$sql .= ")";

		$this->db->begin();

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."holiday");

			if ($this->id) {
				// update ref
				$initialref = '(PROV'.$this->id.')';
				if (!empty($this->ref)) {
					$initialref = $this->ref;
				}

				$sql = 'UPDATE '.MAIN_DB_PREFIX."holiday SET ref='".$this->db->escape($initialref)."' WHERE rowid=".((int) $this->id);
				if ($this->db->query($sql)) {
					$this->ref = $initialref;

					if (!$error) {
						$result = $this->insertExtraFields();
						if ($result < 0) {
							$error++;
						}
					}

					if (!$error && !$notrigger) {
						// Call trigger
						$result = $this->call_trigger('HOLIDAY_CREATE', $user);
						if ($result < 0) {
							$error++;
						}
						// End call triggers
					}
				}
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}


	/**
	 *	Load object in memory from database
	 *
	 *  @param	int		$id         Id object
	 *  @param	string	$ref        Ref object
	 *  @return int         		Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = '')
	{
		$sql = "SELECT";
		$sql .= " cp.rowid,";
		$sql .= " cp.ref,";
		$sql .= " cp.fk_user,";
		$sql .= " cp.date_create,";
		$sql .= " cp.description,";
		$sql .= " cp.date_debut,";
		$sql .= " cp.date_fin,";
		$sql .= " cp.halfday,";
		$sql .= " cp.statut as status,";
		$sql .= " cp.fk_validator,";
		$sql .= " cp.date_valid,";
		$sql .= " cp.fk_user_valid,";
		$sql .= " cp.date_approval,";
		$sql .= " cp.fk_user_approve,";
		$sql .= " cp.date_refuse,";
		$sql .= " cp.fk_user_refuse,";
		$sql .= " cp.date_cancel,";
		$sql .= " cp.fk_user_cancel,";
		$sql .= " cp.detail_refuse,";
		$sql .= " cp.note_private,";
		$sql .= " cp.note_public,";
		$sql .= " cp.fk_user_create,";
		$sql .= " cp.fk_type,";
		$sql .= " cp.entity";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp";
		if ($id > 0) {
			$sql .= " WHERE cp.rowid = ".((int) $id);
		} else {
			$sql .= " WHERE cp.ref = '".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id    = $obj->rowid;
				$this->ref   = ($obj->ref ? $obj->ref : $obj->rowid);
				$this->fk_user = $obj->fk_user;
				$this->date_create = $this->db->jdate($obj->date_create);
				$this->description = $obj->description;
				$this->date_debut = $this->db->jdate($obj->date_debut);
				$this->date_fin = $this->db->jdate($obj->date_fin);
				$this->date_debut_gmt = $this->db->jdate($obj->date_debut, 1);
				$this->date_fin_gmt = $this->db->jdate($obj->date_fin, 1);
				$this->halfday = $obj->halfday;
				$this->status = $obj->status;
				$this->statut = $obj->status;	// deprecated
				$this->fk_validator = $obj->fk_validator;
				$this->date_valid = $this->db->jdate($obj->date_valid);
				$this->fk_user_valid = $obj->fk_user_valid;
				$this->user_validation_id = $obj->fk_user_valid;
				$this->date_approval = $this->db->jdate($obj->date_approval);
				$this->fk_user_approve = $obj->fk_user_approve;
				$this->date_refuse = $this->db->jdate($obj->date_refuse);
				$this->fk_user_refuse = $obj->fk_user_refuse;
				$this->date_cancel = $this->db->jdate($obj->date_cancel);
				$this->fk_user_cancel = $obj->fk_user_cancel;
				$this->detail_refuse = $obj->detail_refuse;
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->fk_user_create = $obj->fk_user_create;
				$this->fk_type = $obj->fk_type;
				$this->entity = $obj->entity;

				$this->fetch_optionals();

				$result = 1;
			} else {
				$result = 0;
			}
			$this->db->free($resql);

			return $result;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	List holidays for a particular user or list of users
	 *
	 *  @param		int|string		$user_id    ID of user to list, or comma separated list of IDs of users to list
	 *  @param      string			$order      Sort order
	 *  @param      string			$filter     SQL Filter
	 *  @return     int      					-1 if KO, 1 if OK, 2 if no result
	 */
	public function fetchByUser($user_id, $order = '', $filter = '')
	{
		$sql = "SELECT";
		$sql .= " cp.rowid,";
		$sql .= " cp.ref,";

		$sql .= " cp.fk_user,";
		$sql .= " cp.fk_type,";
		$sql .= " cp.date_create,";
		$sql .= " cp.description,";
		$sql .= " cp.date_debut,";
		$sql .= " cp.date_fin,";
		$sql .= " cp.halfday,";
		$sql .= " cp.statut as status,";
		$sql .= " cp.fk_validator,";
		$sql .= " cp.date_valid,";
		$sql .= " cp.fk_user_valid,";
		$sql .= " cp.date_approval,";
		$sql .= " cp.fk_user_approve,";
		$sql .= " cp.date_refuse,";
		$sql .= " cp.fk_user_refuse,";
		$sql .= " cp.date_cancel,";
		$sql .= " cp.fk_user_cancel,";
		$sql .= " cp.detail_refuse,";

		$sql .= " uu.lastname as user_lastname,";
		$sql .= " uu.firstname as user_firstname,";
		$sql .= " uu.login as user_login,";
		$sql .= " uu.statut as user_status,";
		$sql .= " uu.photo as user_photo,";

		$sql .= " ua.lastname as validator_lastname,";
		$sql .= " ua.firstname as validator_firstname,";
		$sql .= " ua.login as validator_login,";
		$sql .= " ua.statut as validator_status,";
		$sql .= " ua.photo as validator_photo";

		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp, ".MAIN_DB_PREFIX."user as uu, ".MAIN_DB_PREFIX."user as ua";
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.fk_user = uu.rowid AND cp.fk_validator = ua.rowid"; // Hack pour la recherche sur le tableau
		$sql .= " AND cp.fk_user IN (".$this->db->sanitize($user_id).")";

		// Selection filter
		if (!empty($filter)) {
			$sql .= $filter;
		}

		// Order of display of the result
		if (!empty($order)) {
			$sql .= $order;
		}

		dol_syslog(get_class($this)."::fetchByUser", LOG_DEBUG);
		$resql = $this->db->query($sql);

		// If no SQL error
		if ($resql) {
			$i = 0;
			$tab_result = $this->holiday;
			$num = $this->db->num_rows($resql);

			// If no registration
			if (!$num) {
				return 2;
			}

			// List the records and add them to the table
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$tab_result[$i]['rowid'] = $obj->rowid;
				$tab_result[$i]['id'] = $obj->rowid;
				$tab_result[$i]['ref'] = ($obj->ref ? $obj->ref : $obj->rowid);

				$tab_result[$i]['fk_user'] = $obj->fk_user;
				$tab_result[$i]['fk_type'] = $obj->fk_type;
				$tab_result[$i]['date_create'] = $this->db->jdate($obj->date_create);
				$tab_result[$i]['description'] = $obj->description;
				$tab_result[$i]['date_debut'] = $this->db->jdate($obj->date_debut);
				$tab_result[$i]['date_fin'] = $this->db->jdate($obj->date_fin);
				$tab_result[$i]['date_debut_gmt'] = $this->db->jdate($obj->date_debut, 1);
				$tab_result[$i]['date_fin_gmt'] = $this->db->jdate($obj->date_fin, 1);
				$tab_result[$i]['halfday'] = $obj->halfday;
				$tab_result[$i]['statut'] = $obj->status;
				$tab_result[$i]['status'] = $obj->status;
				$tab_result[$i]['fk_validator'] = $obj->fk_validator;
				$tab_result[$i]['date_valid'] = $this->db->jdate($obj->date_valid);
				$tab_result[$i]['fk_user_valid'] = $obj->fk_user_valid;
				$tab_result[$i]['date_approval'] = $this->db->jdate($obj->date_approval);
				$tab_result[$i]['fk_user_approve'] = $obj->fk_user_approve;
				$tab_result[$i]['date_refuse'] = $this->db->jdate($obj->date_refuse);
				$tab_result[$i]['fk_user_refuse'] = $obj->fk_user_refuse;
				$tab_result[$i]['date_cancel'] = $this->db->jdate($obj->date_cancel);
				$tab_result[$i]['fk_user_cancel'] = $obj->fk_user_cancel;
				$tab_result[$i]['detail_refuse'] = $obj->detail_refuse;

				$tab_result[$i]['user_firstname'] = $obj->user_firstname;
				$tab_result[$i]['user_lastname'] = $obj->user_lastname;
				$tab_result[$i]['user_login'] = $obj->user_login;
				$tab_result[$i]['user_statut'] = $obj->user_status;
				$tab_result[$i]['user_status'] = $obj->user_status;
				$tab_result[$i]['user_photo'] = $obj->user_photo;

				$tab_result[$i]['validator_firstname'] = $obj->validator_firstname;
				$tab_result[$i]['validator_lastname'] = $obj->validator_lastname;
				$tab_result[$i]['validator_login'] = $obj->validator_login;
				$tab_result[$i]['validator_statut'] = $obj->validator_status;
				$tab_result[$i]['validator_status'] = $obj->validator_status;
				$tab_result[$i]['validator_photo'] = $obj->validator_photo;

				$i++;
			}

			// Returns 1 with the filled array
			$this->holiday = $tab_result;
			return 1;
		} else {
			// SQL Error
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	List all holidays of all users
	 *
	 *  @param      string	$order      Sort order
	 *  @param      string	$filter     SQL Filter
	 *  @return     int      			-1 if KO, 1 if OK, 2 if no result
	 */
	public function fetchAll($order, $filter)
	{
		$sql = "SELECT";
		$sql .= " cp.rowid,";
		$sql .= " cp.ref,";
		$sql .= " cp.fk_user,";
		$sql .= " cp.fk_type,";
		$sql .= " cp.date_create,";
		$sql .= " cp.tms as date_modification,";
		$sql .= " cp.description,";
		$sql .= " cp.date_debut,";
		$sql .= " cp.date_fin,";
		$sql .= " cp.halfday,";
		$sql .= " cp.statut as status,";
		$sql .= " cp.fk_validator,";
		$sql .= " cp.date_valid,";
		$sql .= " cp.fk_user_valid,";
		$sql .= " cp.date_approval,";
		$sql .= " cp.fk_user_approve,";
		$sql .= " cp.date_refuse,";
		$sql .= " cp.fk_user_refuse,";
		$sql .= " cp.date_cancel,";
		$sql .= " cp.fk_user_cancel,";
		$sql .= " cp.detail_refuse,";

		$sql .= " uu.lastname as user_lastname,";
		$sql .= " uu.firstname as user_firstname,";
		$sql .= " uu.login as user_login,";
		$sql .= " uu.statut as user_status,";
		$sql .= " uu.photo as user_photo,";

		$sql .= " ua.lastname as validator_lastname,";
		$sql .= " ua.firstname as validator_firstname,";
		$sql .= " ua.login as validator_login,";
		$sql .= " ua.statut as validator_status,";
		$sql .= " ua.photo as validator_photo";

		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp, ".MAIN_DB_PREFIX."user as uu, ".MAIN_DB_PREFIX."user as ua";
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.fk_user = uu.rowid AND cp.fk_validator = ua.rowid "; // Hack pour la recherche sur le tableau

		// Selection filtering
		if (!empty($filter)) {
			$sql .= $filter;
		}

		// order of display
		if (!empty($order)) {
			$sql .= $order;
		}

		dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);
		$resql = $this->db->query($sql);

		// If no SQL error
		if ($resql) {
			$i = 0;
			$tab_result = $this->holiday;
			$num = $this->db->num_rows($resql);

			// If no registration
			if (!$num) {
				return 2;
			}

			// List the records and add them to the table
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$tab_result[$i]['rowid'] = $obj->rowid;
				$tab_result[$i]['id'] = $obj->rowid;
				$tab_result[$i]['ref'] = ($obj->ref ? $obj->ref : $obj->rowid);

				$tab_result[$i]['fk_user'] = $obj->fk_user;
				$tab_result[$i]['fk_type'] = $obj->fk_type;
				$tab_result[$i]['date_create'] = $this->db->jdate($obj->date_create);
				$tab_result[$i]['date_modification'] = $this->db->jdate($obj->date_modification);
				$tab_result[$i]['description'] = $obj->description;
				$tab_result[$i]['date_debut'] = $this->db->jdate($obj->date_debut);
				$tab_result[$i]['date_fin'] = $this->db->jdate($obj->date_fin);
				$tab_result[$i]['date_debut_gmt'] = $this->db->jdate($obj->date_debut, 1);
				$tab_result[$i]['date_fin_gmt'] = $this->db->jdate($obj->date_fin, 1);
				$tab_result[$i]['halfday'] = $obj->halfday;
				$tab_result[$i]['statut'] = $obj->status;
				$tab_result[$i]['status'] = $obj->status;
				$tab_result[$i]['fk_validator'] = $obj->fk_validator;
				$tab_result[$i]['date_valid'] = $this->db->jdate($obj->date_valid);
				$tab_result[$i]['fk_user_valid'] = $obj->fk_user_valid;
				$tab_result[$i]['date_approval'] = $this->db->jdate($obj->date_approval);
				$tab_result[$i]['fk_user_approve'] = $obj->fk_user_approve;
				$tab_result[$i]['date_refuse'] = $obj->date_refuse;
				$tab_result[$i]['fk_user_refuse'] = $obj->fk_user_refuse;
				$tab_result[$i]['date_cancel'] = $obj->date_cancel;
				$tab_result[$i]['fk_user_cancel'] = $obj->fk_user_cancel;
				$tab_result[$i]['detail_refuse'] = $obj->detail_refuse;

				$tab_result[$i]['user_firstname'] = $obj->user_firstname;
				$tab_result[$i]['user_lastname'] = $obj->user_lastname;
				$tab_result[$i]['user_login'] = $obj->user_login;
				$tab_result[$i]['user_statut'] = $obj->user_status;
				$tab_result[$i]['user_status'] = $obj->user_status;
				$tab_result[$i]['user_photo'] = $obj->user_photo;

				$tab_result[$i]['validator_firstname'] = $obj->validator_firstname;
				$tab_result[$i]['validator_lastname'] = $obj->validator_lastname;
				$tab_result[$i]['validator_login'] = $obj->validator_login;
				$tab_result[$i]['validator_statut'] = $obj->validator_status;
				$tab_result[$i]['validator_status'] = $obj->validator_status;
				$tab_result[$i]['validator_photo'] = $obj->validator_photo;

				$i++;
			}
			// Returns 1 and adds the array to the variable
			$this->holiday = $tab_result;
			return 1;
		} else {
			// SQL Error
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *	Validate leave request
	 *
	 *  @param	User	$user        	User that validate
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function validate($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		$error = 0;

		$checkBalance = getDictionaryValue('c_holiday_types', 'block_if_negative', $this->fk_type, true);

		if ($checkBalance > 0) {
			$balance = $this->getCPforUser($this->fk_user, $this->fk_type);

			if ($balance < 0) {
				$this->error = 'LeaveRequestCreationBlockedBecauseBalanceIsNegative';
				return -1;
			}
		}

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref) || $this->ref == $this->id)) {
			$num = $this->getNextNumRef(null);
		} else {
			$num = $this->ref;
		}
		$this->newref = dol_sanitizeFileName($num);

		// Update status
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";
		$sql .= " fk_user_valid = ".((int) $user->id).",";
		$sql .= " date_valid = '".$this->db->idate(dol_now())."',";
		if (!empty($this->status) && is_numeric($this->status)) {
			$sql .= " statut = ".((int) $this->status).",";
		} else {
			$this->error = 'Property status must be a numeric value';
			$error++;
		}
		$sql .= " ref = '".$this->db->escape($num)."'";
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::validate", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAY_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ")), filepath = 'holiday/" . $this->db->escape($this->newref) . "'";
				$sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'holiday/" . $this->db->escape($this->ref) . "' and entity = " . ((int) $conf->entity);
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}
				$sql = 'UPDATE '.MAIN_DB_PREFIX."ecm_files set filepath = 'holiday/".$this->db->escape($this->newref)."'";
				$sql .= " WHERE filepath = 'holiday/".$this->db->escape($this->ref)."' and entity = ".$conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->holiday->multidir_output[$this->entity] . '/' . $oldref;
				$dirdest = $conf->holiday->multidir_output[$this->entity] . '/' . $newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this) . "::validate rename dir " . $dirsource . " into " . $dirdest);
					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($dirdest, 'files', 1, '^' . preg_quote($oldref, '/'));
						foreach ($listoffiles as $fileentry) {
							$dirsource = $fileentry['name'];
							$dirdest = preg_replace('/^' . preg_quote($oldref, '/') . '/', $newref, $dirsource);
							$dirsource = $fileentry['path'] . '/' . $dirsource;
							$dirdest = $fileentry['path'] . '/' . $dirdest;
							@rename($dirsource, $dirdest);
						}
					}
				}
			}
		}


		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::validate ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *	Approve leave request
	 *
	 *  @param	User	$user        	User that approve
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function approve($user = null, $notrigger = 0)
	{
		$error = 0;

		$checkBalance = getDictionaryValue('c_holiday_types', 'block_if_negative', $this->fk_type, true);

		if ($checkBalance > 0) {
			$balance = $this->getCPforUser($this->fk_user, $this->fk_type);

			if ($balance < 0) {
				$this->error = 'LeaveRequestCreationBlockedBecauseBalanceIsNegative';
				return -1;
			}
		}

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";
		$sql .= " description= '".$this->db->escape($this->description)."',";
		if (!empty($this->date_debut)) {
			$sql .= " date_debut = '".$this->db->idate($this->date_debut)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_fin)) {
			$sql .= " date_fin = '".$this->db->idate($this->date_fin)."',";
		} else {
			$error++;
		}
		$sql .= " halfday = ".((int) $this->halfday).",";
		if (!empty($this->status) && is_numeric($this->status)) {
			$sql .= " statut = ".((int) $this->status).",";
		} else {
			$error++;
		}
		if (!empty($this->fk_validator)) {
			$sql .= " fk_validator = ".((int) $this->fk_validator).",";
		} else {
			$error++;
		}
		if (!empty($this->date_valid)) {
			$sql .= " date_valid = '".$this->db->idate($this->date_valid)."',";
		} else {
			$sql .= " date_valid = NULL,";
		}
		if (!empty($this->fk_user_valid)) {
			$sql .= " fk_user_valid = ".((int) $this->fk_user_valid).",";
		} else {
			$sql .= " fk_user_valid = NULL,";
		}
		if (!empty($this->date_approval)) {
			$sql .= " date_approval = '".$this->db->idate($this->date_approval)."',";
		} else {
			$sql .= " date_approval = NULL,";
		}
		if (!empty($this->fk_user_approve)) {
			$sql .= " fk_user_approve = ".((int) $this->fk_user_approve).",";
		} else {
			$sql .= " fk_user_approve = NULL,";
		}
		if (!empty($this->date_refuse)) {
			$sql .= " date_refuse = '".$this->db->idate($this->date_refuse)."',";
		} else {
			$sql .= " date_refuse = NULL,";
		}
		if (!empty($this->fk_user_refuse)) {
			$sql .= " fk_user_refuse = ".((int) $this->fk_user_refuse).",";
		} else {
			$sql .= " fk_user_refuse = NULL,";
		}
		if (!empty($this->date_cancel)) {
			$sql .= " date_cancel = '".$this->db->idate($this->date_cancel)."',";
		} else {
			$sql .= " date_cancel = NULL,";
		}
		if (!empty($this->fk_user_cancel)) {
			$sql .= " fk_user_cancel = ".((int) $this->fk_user_cancel).",";
		} else {
			$sql .= " fk_user_cancel = NULL,";
		}
		if (!empty($this->detail_refuse)) {
			$sql .= " detail_refuse = '".$this->db->escape($this->detail_refuse)."'";
		} else {
			$sql .= " detail_refuse = NULL";
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::approve", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAY_APPROVE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::approve ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Update database
	 *
	 *  @param	User	$user        	User that modify
	 *  @param  int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *  @return int         			Return integer <0 if KO, >0 if OK
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$checkBalance = getDictionaryValue('c_holiday_types', 'block_if_negative', $this->fk_type, true);

		if ($checkBalance > 0 && $this->status != self::STATUS_DRAFT) {
			$balance = $this->getCPforUser($this->fk_user, $this->fk_type);

			if ($balance < 0) {
				$this->error = 'LeaveRequestCreationBlockedBecauseBalanceIsNegative';
				return -1;
			}
		}

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday SET";

		$sql .= " description= '".$this->db->escape($this->description)."',";

		if (!empty($this->date_debut)) {
			$sql .= " date_debut = '".$this->db->idate($this->date_debut)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_fin)) {
			$sql .= " date_fin = '".$this->db->idate($this->date_fin)."',";
		} else {
			$error++;
		}
		$sql .= " halfday = ".$this->halfday.",";
		if (!empty($this->status) && is_numeric($this->status)) {
			$sql .= " statut = ".$this->status.",";
		} else {
			$error++;
		}
		if (!empty($this->fk_validator)) {
			$sql .= " fk_validator = '".$this->db->escape($this->fk_validator)."',";
		} else {
			$error++;
		}
		if (!empty($this->date_valid)) {
			$sql .= " date_valid = '".$this->db->idate($this->date_valid)."',";
		} else {
			$sql .= " date_valid = NULL,";
		}
		if (!empty($this->fk_user_valid)) {
			$sql .= " fk_user_valid = ".((int) $this->fk_user_valid).",";
		} else {
			$sql .= " fk_user_valid = NULL,";
		}
		if (!empty($this->date_approval)) {
			$sql .= " date_approval = '".$this->db->idate($this->date_approval)."',";
		} else {
			$sql .= " date_approval = NULL,";
		}
		if (!empty($this->fk_user_approve)) {
			$sql .= " fk_user_approve = ".((int) $this->fk_user_approve).",";
		} else {
			$sql .= " fk_user_approve = NULL,";
		}
		if (!empty($this->date_refuse)) {
			$sql .= " date_refuse = '".$this->db->idate($this->date_refuse)."',";
		} else {
			$sql .= " date_refuse = NULL,";
		}
		if (!empty($this->fk_user_refuse)) {
			$sql .= " fk_user_refuse = ".((int) $this->fk_user_refuse).",";
		} else {
			$sql .= " fk_user_refuse = NULL,";
		}
		if (!empty($this->date_cancel)) {
			$sql .= " date_cancel = '".$this->db->idate($this->date_cancel)."',";
		} else {
			$sql .= " date_cancel = NULL,";
		}
		if (!empty($this->fk_user_cancel)) {
			$sql .= " fk_user_cancel = ".((int) $this->fk_user_cancel).",";
		} else {
			$sql .= " fk_user_cancel = NULL,";
		}
		if (!empty($this->detail_refuse)) {
			$sql .= " detail_refuse = '".$this->db->escape($this->detail_refuse)."'";
		} else {
			$sql .= " detail_refuse = NULL";
		}

		$sql .= " WHERE rowid = ".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAY_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}


	/**
	 *   Delete object in database
	 *
	 *	 @param		User	$user        	User that delete
	 *   @param     int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *	 @return	int						Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."holiday";
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('HOLIDAY_DELETE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *	Check if a user is on holiday (partially or completely) into a period.
	 *  This function can be used to avoid to have 2 leave requests on same period for example.
	 *  Warning: It consumes a lot of memory because it load in ->holiday all holiday of a dedicated user at each call.
	 *
	 *  @param 	int		$fk_user		Id user
	 *  @param 	integer	$dateStart		Start date of period to check
	 *  @param 	integer	$dateEnd		End date of period to check
	 *  @param  int     $halfday        Tag to define how start and end the period to check:
	 *                                  0:Full days, 2:Start afternoon end morning, -1:Start afternoon end afternoon, 1:Start morning end morning
	 * 	@return boolean					False = New range overlap an existing holiday, True = no overlapping (is never on holiday during checked period).
	 *  @see verifDateHolidayForTimestamp()
	 */
	public function verifDateHolidayCP($fk_user, $dateStart, $dateEnd, $halfday = 0)
	{
		$this->fetchByUser($fk_user, '', '');

		foreach ($this->holiday as $infos_CP) {
			if ($infos_CP['statut'] == Holiday::STATUS_CANCELED) {
				continue; // ignore not validated holidays
			}
			if ($infos_CP['statut'] == Holiday::STATUS_REFUSED) {
				continue; // ignore refused holidays
			}
			//var_dump("--");
			//var_dump("old: ".dol_print_date($infos_CP['date_debut'],'dayhour').' '.dol_print_date($infos_CP['date_fin'],'dayhour').' '.$infos_CP['halfday']);
			//var_dump("new: ".dol_print_date($dateStart,'dayhour').' '.dol_print_date($dateEnd,'dayhour').' '.$halfday);

			if ($halfday == 0) {
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					return false;
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					return false;
				}
			} elseif ($halfday == -1) {
				// new start afternoon, new end afternoon
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					if ($dateStart < $infos_CP['date_fin'] || in_array($infos_CP['halfday'], array(0, -1))) {
						return false;
					}
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					if ($dateStart < $dateEnd) {
						return false;
					}
					if ($dateEnd < $infos_CP['date_fin'] || in_array($infos_CP['halfday'], array(0, -1))) {
						return false;
					}
				}
			} elseif ($halfday == 1) {
				// new start morning, new end morning
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					if ($dateStart < $dateEnd) {
						return false;
					}
					if ($dateStart > $infos_CP['date_debut'] || in_array($infos_CP['halfday'], array(0, 1))) {
						return false;
					}
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					if ($dateEnd > $infos_CP['date_debut'] || in_array($infos_CP['halfday'], array(0, 1))) {
						return false;
					}
				}
			} elseif ($halfday == 2) {
				// new start afternoon, new end morning
				if ($dateStart >= $infos_CP['date_debut'] && $dateStart <= $infos_CP['date_fin']) {
					if ($dateStart < $infos_CP['date_fin'] || in_array($infos_CP['halfday'], array(0, -1))) {
						return false;
					}
				}
				if ($dateEnd <= $infos_CP['date_fin'] && $dateEnd >= $infos_CP['date_debut']) {
					if ($dateEnd > $infos_CP['date_debut'] || in_array($infos_CP['halfday'], array(0, 1))) {
						return false;
					}
				}
			} else {
				dol_print_error(null, 'Bad value of parameter halfday when calling function verifDateHolidayCP');
			}
		}

		return true;
	}


	/**
	 *	Check that a user is not on holiday for a particular timestamp. Can check approved leave requests and not into public holidays of company.
	 *
	 * 	@param 	int			$fk_user				Id user
	 *  @param	integer	    $timestamp				Time stamp date for a day (YYYY-MM-DD) without hours  (= 12:00AM in english and not 12:00PM that is 12:00)
	 *  @param	string		$status					Filter on holiday status. '-1' = no filter.
	 * 	@return array								array('morning'=> ,'afternoon'=> ), Boolean is true if user is available for day timestamp.
	 *  @see verifDateHolidayCP()
	 */
	public function verifDateHolidayForTimestamp($fk_user, $timestamp, $status = '-1')
	{
		$isavailablemorning = true;
		$isavailableafternoon = true;

		// Check into leave requests
		$sql = "SELECT cp.rowid, cp.date_debut as date_start, cp.date_fin as date_end, cp.halfday, cp.statut as status";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as cp";
		$sql .= " WHERE cp.entity IN (".getEntity('holiday').")";
		$sql .= " AND cp.fk_user = ".(int) $fk_user;
		$sql .= " AND cp.date_debut <= '".$this->db->idate($timestamp)."' AND cp.date_fin >= '".$this->db->idate($timestamp)."'";
		if ($status != '-1') {
			$sql .= " AND cp.statut IN (".$this->db->sanitize($status).")";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num_rows = $this->db->num_rows($resql); // Note, we can have 2 records if on is morning and the other one is afternoon
			if ($num_rows > 0) {
				$arrayofrecord = array();
				$i = 0;
				while ($i < $num_rows) {
					$obj = $this->db->fetch_object($resql);

					// Note: $obj->halfday is  0:Full days, 2:Start afternoon end morning, -1:Start afternoon, 1:End morning
					$arrayofrecord[$obj->rowid] = array('date_start' => $this->db->jdate($obj->date_start), 'date_end' => $this->db->jdate($obj->date_end), 'halfday' => $obj->halfday, 'status' => $obj->status);
					$i++;
				}

				// We found a record, user is on holiday by default, so is not available is true.
				$isavailablemorning = true;
				foreach ($arrayofrecord as $record) {
					if ($timestamp == $record['date_start'] && $record['halfday'] == 2) {
						continue;
					}
					if ($timestamp == $record['date_start'] && $record['halfday'] == -1) {
						continue;
					}
					$isavailablemorning = false;
					break;
				}
				$isavailableafternoon = true;
				foreach ($arrayofrecord as $record) {
					if ($timestamp == $record['date_end'] && $record['halfday'] == 2) {
						continue;
					}
					if ($timestamp == $record['date_end'] && $record['halfday'] == 1) {
						continue;
					}
					$isavailableafternoon = false;
					break;
				}
			}
		} else {
			dol_print_error($this->db);
		}

		$result = array('morning' => $isavailablemorning, 'afternoon' => $isavailableafternoon);
		if (!$isavailablemorning) {
			$result['morning_reason'] = 'leave_request';
		}
		if (!$isavailableafternoon) {
			$result['afternoon_reason'] = 'leave_request';
		}
		return $result;
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
		global $langs;

		$langs->load('holiday');
		$nofetch = !empty($params['nofetch']);

		$datas = array();
		$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Holiday").'</u>';
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		// show type for this record only in ajax to not overload lists
		if (!$nofetch && !empty($this->fk_type)) {
			$typeleaves = $this->getTypes(1, -1);
			if (empty($typeleaves[$this->fk_type])) {
				$labeltoshow = $langs->trans("TypeWasDisabledOrRemoved", $this->fk_type);
			} else {
				$labeltoshow = (($typeleaves[$this->fk_type]['code'] && $langs->trans($typeleaves[$this->fk_type]['code']) != $typeleaves[$this->fk_type]['code']) ? $langs->trans($typeleaves[$this->fk_type]['code']) : $typeleaves[$this->fk_type]['label']);
			}
			$datas['type'] = '<br><b>'.$langs->trans("Type") . ':</b> ' . $labeltoshow;
		}
		if (isset($this->halfday) && !empty($this->date_debut) && !empty($this->date_fin)) {
			$listhalfday = array(
				'morning' => $langs->trans("Morning"),
				"afternoon" => $langs->trans("Afternoon")
			);
			$starthalfday = ($this->halfday == -1 || $this->halfday == 2) ? 'afternoon' : 'morning';
			$endhalfday = ($this->halfday == 1 || $this->halfday == 2) ? 'morning' : 'afternoon';
			$datas['date_start'] = '<br><b>'.$langs->trans('DateDebCP') . '</b>: '. dol_print_date($this->date_debut, 'day') . '&nbsp;&nbsp;<span class="opacitymedium">'.$langs->trans($listhalfday[$starthalfday]).'</span>';
			$datas['date_end'] = '<br><b>'.$langs->trans('DateFinCP') . '</b>: '. dol_print_date($this->date_fin, 'day') . '&nbsp;&nbsp;<span class="opacitymedium">'.$langs->trans($listhalfday[$endhalfday]).'</span>';
		}


		return $datas;
	}

	/**
	 *	Return clickable name (with picto eventually)
	 *
	 *	@param	int			$withpicto					0=_No picto, 1=Includes the picto in the linkn, 2=Picto only
	 *  @param  int     	$save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param  int         $notooltip					1=Disable tooltip
	 *  @param  string  	$morecss                    Add more css on link
	 *	@return	string									String with URL
	 */
	public function getNomUrl($withpicto = 0, $save_lastsearch_value = -1, $notooltip = 0, $morecss = '')
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'nofetch' => 1,
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

		$url = DOL_URL_ROOT.'/holiday/card.php?id='.$this->id;

		//if ($option != 'nolink')
		//{
		// Add param to save lastsearch_values or not
		$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
		if ($save_lastsearch_value == -1 && isset($_SERVER["PHP_SELF"]) && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
			$add_save_lastsearch_values = 1;
		}
		if ($add_save_lastsearch_values) {
			$url .= '&save_lastsearch_values=1';
		}
		//}

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;

		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
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
	 *	Returns the label status
	 *
	 *	@param      int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *	@return     string      		Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode, $this->date_debut);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Returns the label of a status
	 *
	 *	@param      int			$status     Id status
	 *	@param      int			$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 *  @param		int|string	$startdate	Date holiday should start
	 *	@return     string      			Label
	 */
	public function LibStatut($status, $mode = 0, $startdate = '')
	{
		// phpcs:enable
		global $langs;

		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("mymodule");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('DraftCP');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ToReviewCP');
			$this->labelStatus[self::STATUS_APPROVED] = $langs->transnoentitiesnoconv('ApprovedCP');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('CancelCP');
			$this->labelStatus[self::STATUS_REFUSED] = $langs->transnoentitiesnoconv('RefuseCP');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->transnoentitiesnoconv('DraftCP');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->transnoentitiesnoconv('ToReviewCP');
			$this->labelStatusShort[self::STATUS_APPROVED] = $langs->transnoentitiesnoconv('ApprovedCP');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->transnoentitiesnoconv('CancelCP');
			$this->labelStatusShort[self::STATUS_REFUSED] = $langs->transnoentitiesnoconv('RefuseCP');
		}

		$params = array();
		$statusType = 'status6';
		if (!empty($startdate) && $startdate >= dol_now()) {		// If not yet passed, we use a green "in live" color
			$statusType = 'status4';
			$params = array('tooltip' => $this->labelStatus[$status].' - '.$langs->trans("Forthcoming"));
		}
		if ($status == self::STATUS_DRAFT) {
			$statusType = 'status0';
		}
		if ($status == self::STATUS_VALIDATED) {
			$statusType = 'status1';
		}
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status9';
		}
		if ($status == self::STATUS_REFUSED) {
			$statusType = 'status9';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode, '', $params);
	}


	/**
	 *   Show select with list of leave status
	 *
	 *   @param 	int		$selected   	Id of preselected status
	 *   @param		string	$htmlname		Name of HTML select field
	 *   @param		string	$morecss		More CSS on select component
	 *   @return    string					Show select of status
	 */
	public function selectStatutCP($selected = 0, $htmlname = 'select_statut', $morecss = 'minwidth125')
	{
		global $langs;

		// List of status label
		$name = array('DraftCP', 'ToReviewCP', 'ApprovedCP', 'CancelCP', 'RefuseCP');
		$nb = count($name) + 1;

		// Select HTML
		$out = '<select name="'.$htmlname.'" id="'.$htmlname.'" class="flat'.($morecss ? ' '.$morecss : '').'">'."\n";
		$out .= '<option value="-1">&nbsp;</option>'."\n";

		// Loop on status
		for ($i = 1; $i < $nb; $i++) {
			if ($i == $selected) {
				$out .= '<option value="'.$i.'" selected>'.$langs->trans($name[$i - 1]).'</option>'."\n";
			} else {
				$out .= '<option value="'.$i.'">'.$langs->trans($name[$i - 1]).'</option>'."\n";
			}
		}

		$out .= "</select>\n";

		$showempty = 0;
		$out .= ajax_combobox($htmlname, array(), 0, 0, 'resolve', ($showempty < 0 ? (string) $showempty : '-1'), $morecss);

		return $out;
	}

	/**
	 *  Met à jour une option du module Holiday Payés
	 *
	 *  @param	string	$name       name du paramètre de configuration
	 *  @param	string	$value      vrai si mise à jour OK sinon faux
	 *  @return boolean				ok or ko
	 */
	public function updateConfCP($name, $value)
	{
		$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
		$sql .= " value = '".$this->db->escape($value)."'";
		$sql .= " WHERE name = '".$this->db->escape($name)."'";

		dol_syslog(get_class($this).'::updateConfCP name='.$name, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			return true;
		}

		return false;
	}

	/**
	 *  Return value of a conf parameter for leave module
	 *  TODO Move this into llx_const table
	 *
	 *  @param	string	$name                 Name of parameter
	 *  @param  string  $createifnotfound     'stringvalue'=Create entry with string value if not found. For example 'YYYYMMDDHHMMSS'.
	 *  @return string|int<min,0>             Value of parameter. Example: 'YYYYMMDDHHMMSS' or < 0 if error
	 */
	public function getConfCP($name, $createifnotfound = '')
	{
		$sql = "SELECT value";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_config";
		$sql .= " WHERE name = '".$this->db->escape($name)."'";

		dol_syslog(get_class($this).'::getConfCP name='.$name.' createifnotfound='.$createifnotfound, LOG_DEBUG);
		$result = $this->db->query($sql);

		if ($result) {
			$obj = $this->db->fetch_object($result);
			// Return value
			if (empty($obj)) {
				if ($createifnotfound) {
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_config(name, value)";
					$sql .= " VALUES('".$this->db->escape($name)."', '".$this->db->escape($createifnotfound)."')";
					$result = $this->db->query($sql);
					if ($result) {
						return $createifnotfound;
					} else {
						$this->error = $this->db->lasterror();
						return -2;
					}
				} else {
					return '';
				}
			} else {
				return $obj->value;
			}
		} else {
			// Erreur SQL
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *	Met à jour le timestamp de la dernière mise à jour du solde des CP
	 *
	 *	@param		int		$userID		Id of user
	 *	@param		float	$nbHoliday	Nb of days
	 *  @param		int		$fk_type	Type of vacation
	 *  @return     int					0=Nothing done, 1=OK, -1=KO
	 */
	public function updateSoldeCP($userID = 0, $nbHoliday = 0, $fk_type = 0)
	{
		global $user, $langs;

		$error = 0;

		if (empty($userID) && empty($nbHoliday) && empty($fk_type)) {
			$langs->load("holiday");

			// Si mise à jour pour tout le monde en début de mois
			$now = dol_now();

			$month = date('m', $now);
			$newdateforlastupdate = dol_print_date($now, '%Y%m%d%H%M%S');

			// Get month of last update
			$lastUpdate = $this->getConfCP('lastUpdate', $newdateforlastupdate);
			$monthLastUpdate = $lastUpdate[4].$lastUpdate[5];
			//print 'month: '.$month.' lastUpdate:'.$lastUpdate.' monthLastUpdate:'.$monthLastUpdate;exit;

			// If month date is not same than the one of last update (the one we saved in database), then we update the timestamp and balance of each open user.
			if ($month != $monthLastUpdate) {
				$this->db->begin();

				$users = $this->fetchUsers(false, false, ' AND u.statut > 0');
				$nbUser = count($users);

				$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_config SET";
				$sql .= " value = '".$this->db->escape($newdateforlastupdate)."'";
				$sql .= " WHERE name = 'lastUpdate'";
				$result = $this->db->query($sql);

				$typeleaves = $this->getTypes(1, 1);

				// Update each user counter
				foreach ($users as $userCounter) {
					$nbDaysToAdd = (isset($typeleaves[$userCounter['type']]['newbymonth']) ? $typeleaves[$userCounter['type']]['newbymonth'] : 0);
					if (empty($nbDaysToAdd)) {
						continue;
					}

					dol_syslog("We update leave type id ".$userCounter['type']." for user id ".$userCounter['rowid'], LOG_DEBUG);

					$nowHoliday = $userCounter['nb_holiday'];
					$newSolde = $nowHoliday + $nbDaysToAdd;

					// We add a log for each user
					$this->addLogCP($user->id, $userCounter['rowid'], $langs->trans('HolidaysMonthlyUpdate'), $newSolde, $userCounter['type']);

					$result = $this->updateSoldeCP($userCounter['rowid'], $newSolde, $userCounter['type']);

					if ($result < 0) {
						$error++;
						break;
					}
				}

				if (!$error) {
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					return -1;
				}
			}

			return 0;
		} else {
			// Mise à jour pour un utilisateur
			$nbHoliday = price2num($nbHoliday, 5);

			$sql = "SELECT nb_holiday FROM ".MAIN_DB_PREFIX."holiday_users";
			$sql .= " WHERE fk_user = ".(int) $userID." AND fk_type = ".(int) $fk_type;
			$resql = $this->db->query($sql);
			if ($resql) {
				$num = $this->db->num_rows($resql);

				if ($num > 0) {
					// Update for user
					$sql = "UPDATE ".MAIN_DB_PREFIX."holiday_users SET";
					$sql .= " nb_holiday = ".((float) $nbHoliday);
					$sql .= " WHERE fk_user = ".(int) $userID." AND fk_type = ".(int) $fk_type;
					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				} else {
					// Insert for user
					$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_users(nb_holiday, fk_user, fk_type) VALUES (";
					$sql .= ((float) $nbHoliday);
					$sql .= ", ".(int) $userID.", ".(int) $fk_type.")";
					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				}
			} else {
				$this->errors[] = $this->db->lasterror();
				$error++;
			}

			if (!$error) {
				return 1;
			} else {
				return -1;
			}
		}
	}

	/**
	 *  Create entries for each user at setup step
	 *
	 *  @param	boolean		$single		Single
	 *  @param	int			$userid		Id user
	 *  @return void
	 */
	public function createCPusers($single = false, $userid = 0)
	{
		// do we have to add balance for all users ?
		if (!$single) {
			dol_syslog(get_class($this).'::createCPusers');
			$arrayofusers = $this->fetchUsers(false, true);

			foreach ($arrayofusers as $users) {
				$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_users";
				$sql .= " (fk_user, nb_holiday)";
				$sql .= " VALUES (".((int) $users['rowid'])."', '0')";

				$resql = $this->db->query($sql);
				if (!$resql) {
					dol_print_error($this->db);
				}
			}
		} else {
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_users";
			$sql .= " (fk_user, nb_holiday)";
			$sql .= " VALUES (".((int) $userid)."', '0')";

			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
			}
		}
	}

	/**
	 *  Return the balance of annual leave of a user
	 *
	 *  @param	int		$user_id    User ID
	 *  @param	int		$fk_type	Filter on type
	 *  @return float|null     		Balance of annual leave if OK, null if KO.
	 */
	public function getCPforUser($user_id, $fk_type = 0)
	{
		$sql = "SELECT nb_holiday";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_users";
		$sql .= " WHERE fk_user = ".(int) $user_id;
		if ($fk_type > 0) {
			$sql .= " AND fk_type = ".(int) $fk_type;
		}

		dol_syslog(get_class($this).'::getCPforUser user_id='.$user_id.' type_id='.$fk_type, LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			//return number_format($obj->nb_holiday,2);
			if ($obj) {
				return $obj->nb_holiday;
			} else {
				return null;
			}
		} else {
			return null;
		}
	}

	/**
	 *    Get list of Users or list of vacation balance.
	 *
	 *    @param      boolean			$stringlist	    If true return a string list of id. If false, return an array with detail.
	 *    @param      boolean   		$type			If true, read Dolibarr user list, if false, return vacation balance list.
	 *    @param      string            $filters        Filters. Warning: This must not contains data from user input.
	 *    @return     array|string|int      			Return an array
	 */
	public function fetchUsers($stringlist = true, $type = true, $filters = '')
	{
		global $conf;

		dol_syslog(get_class($this)."::fetchUsers", LOG_DEBUG);

		if ($stringlist) {
			if ($type) {
				// If user of Dolibarr
				$sql = "SELECT";
				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$sql .= " DISTINCT";
				}
				$sql .= " u.rowid";
				$sql .= " FROM ".MAIN_DB_PREFIX."user as u";

				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$sql .= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
					$sql .= " WHERE ((ug.fk_user = u.rowid";
					$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
					$sql .= " OR u.entity = 0)"; // Show always superadmin
				} else {
					$sql .= " WHERE u.entity IN (".getEntity('user').")";
				}
				$sql .= " AND u.statut > 0";
				$sql .= " AND u.employee = 1"; // We only want employee users for holidays
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$num = $this->db->num_rows($resql);
					$stringlist = '';

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						if ($i == 0) {
							$stringlist .= $obj->rowid;
						} else {
							$stringlist .= ', '.$obj->rowid;
						}

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $stringlist;
				} else {
					// Erreur SQL
					$this->error = "Error ".$this->db->lasterror();
					return -1;
				}
			} else {
				// We want only list of vacation balance for user ids
				$sql = "SELECT DISTINCT cpu.fk_user";
				$sql .= " FROM ".MAIN_DB_PREFIX."holiday_users as cpu, ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE cpu.fk_user = u.rowid";
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$num = $this->db->num_rows($resql);
					$stringlist = '';

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						if ($i == 0) {
							$stringlist .= $obj->fk_user;
						} else {
							$stringlist .= ', '.$obj->fk_user;
						}

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $stringlist;
				} else {
					// Erreur SQL
					$this->error = "Error ".$this->db->lasterror();
					return -1;
				}
			}
		} else {
			// Si faux donc return array
			// List for Dolibarr users
			if ($type) {
				// If we need users of Dolibarr
				$sql = "SELECT";
				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$sql .= " DISTINCT";
				}
				$sql .= " u.rowid, u.lastname, u.firstname, u.gender, u.photo, u.employee, u.statut as status, u.fk_user";
				$sql .= " FROM ".MAIN_DB_PREFIX."user as u";

				if (isModEnabled('multicompany') && getDolGlobalString('MULTICOMPANY_TRANSVERSE_MODE')) {
					$sql .= ", ".MAIN_DB_PREFIX."usergroup_user as ug";
					$sql .= " WHERE ((ug.fk_user = u.rowid";
					$sql .= " AND ug.entity IN (".getEntity('usergroup')."))";
					$sql .= " OR u.entity = 0)"; // Show always superadmin
				} else {
					$sql .= " WHERE u.entity IN (".getEntity('user').")";
				}

				$sql .= " AND u.statut > 0";
				$sql .= " AND u.employee = 1"; // We only want employee users for holidays
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$tab_result = $this->holiday;
					$num = $this->db->num_rows($resql);

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						$tab_result[$i]['rowid'] = $obj->rowid; // rowid of user
						$tab_result[$i]['id'] = $obj->rowid; // id of user
						$tab_result[$i]['name'] = $obj->lastname; // deprecated
						$tab_result[$i]['lastname'] = $obj->lastname;
						$tab_result[$i]['firstname'] = $obj->firstname;
						$tab_result[$i]['gender'] = $obj->gender;
						$tab_result[$i]['status'] = $obj->status;
						$tab_result[$i]['employee'] = $obj->employee;
						$tab_result[$i]['photo'] = $obj->photo;
						$tab_result[$i]['fk_user'] = $obj->fk_user; // rowid of manager
						//$tab_result[$i]['type'] = $obj->type;
						//$tab_result[$i]['nb_holiday'] = $obj->nb_holiday;

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $tab_result;
				} else {
					// Erreur SQL
					$this->errors[] = "Error ".$this->db->lasterror();
					return -1;
				}
			} else {
				// List of vacation balance users
				$sql = "SELECT cpu.fk_type, cpu.nb_holiday, u.rowid, u.lastname, u.firstname, u.gender, u.photo, u.employee, u.statut as status, u.fk_user";
				$sql .= " FROM ".MAIN_DB_PREFIX."holiday_users as cpu, ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE cpu.fk_user = u.rowid";
				if ($filters) {
					$sql .= $filters;
				}

				$resql = $this->db->query($sql);

				// Si pas d'erreur SQL
				if ($resql) {
					$i = 0;
					$tab_result = $this->holiday;
					$num = $this->db->num_rows($resql);

					// Boucles du listage des utilisateurs
					while ($i < $num) {
						$obj = $this->db->fetch_object($resql);

						$tab_result[$i]['rowid'] = $obj->rowid; // rowid of user
						$tab_result[$i]['id'] = $obj->rowid; // id of user
						$tab_result[$i]['name'] = $obj->lastname; // deprecated
						$tab_result[$i]['lastname'] = $obj->lastname;
						$tab_result[$i]['firstname'] = $obj->firstname;
						$tab_result[$i]['gender'] = $obj->gender;
						$tab_result[$i]['status'] = $obj->status;
						$tab_result[$i]['employee'] = $obj->employee;
						$tab_result[$i]['photo'] = $obj->photo;
						$tab_result[$i]['fk_user'] = $obj->fk_user; // rowid of manager

						$tab_result[$i]['type'] = $obj->fk_type;
						$tab_result[$i]['nb_holiday'] = $obj->nb_holiday;

						$i++;
					}
					// Retoune le tableau des utilisateurs
					return $tab_result;
				} else {
					// Erreur SQL
					$this->error = "Error ".$this->db->lasterror();
					return -1;
				}
			}
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return list of people with permission to validate leave requests.
	 * Search for permission "approve leave requests"
	 *
	 * @return  array|int       Array of user ids or -1 if error
	 */
	public function fetch_users_approver_holiday()
	{
		// phpcs:enable
		$users_validator = array();

		$sql = "SELECT DISTINCT ur.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."user_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
		$sql .= " WHERE ur.fk_id = rd.id and rd.module = 'holiday' AND rd.perms = 'approve'"; // Permission 'Approve';
		$sql .= "UNION";
		$sql .= " SELECT DISTINCT ugu.fk_user";
		$sql .= " FROM ".MAIN_DB_PREFIX."usergroup_user as ugu, ".MAIN_DB_PREFIX."usergroup_rights as ur, ".MAIN_DB_PREFIX."rights_def as rd";
		$sql .= " WHERE ugu.fk_usergroup = ur.fk_usergroup AND ur.fk_id = rd.id and rd.module = 'holiday' AND rd.perms = 'approve'"; // Permission 'Approve';
		//print $sql;

		dol_syslog(get_class($this)."::fetch_users_approver_holiday sql=".$sql);
		$result = $this->db->query($sql);
		if ($result) {
			$num_rows = $this->db->num_rows($result);
			$i = 0;
			while ($i < $num_rows) {
				$objp = $this->db->fetch_object($result);
				array_push($users_validator, $objp->fk_user);
				$i++;
			}
			return $users_validator;
		} else {
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_users_approver_holiday  Error ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *	Compte le nombre d'utilisateur actifs dans Dolibarr
	 *
	 *  @return     int      retourne le nombre d'utilisateur
	 */
	public function countActiveUsers()
	{
		$sql = "SELECT count(u.rowid) as compteur";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u";
		$sql .= " WHERE u.statut > 0";

		$result = $this->db->query($sql);
		$object = $this->db->fetch_object($result);

		return $object->compteur;
	}
	/**
	 *	Compte le nombre d'utilisateur actifs dans Dolibarr sans CP
	 *
	 *  @return     int      retourne le nombre d'utilisateur
	 */
	public function countActiveUsersWithoutCP()
	{
		$sql = "SELECT count(u.rowid) as compteur";
		$sql .= " FROM ".MAIN_DB_PREFIX."user as u LEFT OUTER JOIN ".MAIN_DB_PREFIX."holiday_users hu ON (hu.fk_user=u.rowid)";
		$sql .= " WHERE u.statut > 0 AND hu.fk_user IS NULL";

		$result = $this->db->query($sql);
		$object = $this->db->fetch_object($result);

		return $object->compteur;
	}

	/**
	 *  Compare le nombre d'utilisateur actif de Dolibarr à celui des utilisateurs des congés payés
	 *
	 *  @param    int	$userDolibarrWithoutCP	Number of active users in Dolibarr without holidays
	 *  @param    int	$userCP    				Number of active users into table of holidays
	 *  @return   int							Return integer <0 if KO, >0 if OK
	 */
	public function verifNbUsers($userDolibarrWithoutCP, $userCP)
	{
		if (empty($userCP)) {
			$userCP = 0;
		}
		dol_syslog(get_class($this).'::verifNbUsers userDolibarr='.$userDolibarrWithoutCP.' userCP='.$userCP);
		return 1;
	}


	/**
	 * addLogCP
	 *
	 * @param 	int		$fk_user_action		Id user creation
	 * @param 	int		$fk_user_update		Id user update
	 * @param 	string	$label				Label (Example: 'Leave', 'Manual update', 'Leave request cancelation'...)
	 * @param 	int		$new_solde			New value
	 * @param	int		$fk_type			Type of vacation
	 * @return 	int							Id of record added, 0 if nothing done, < 0 if KO
	 */
	public function addLogCP($fk_user_action, $fk_user_update, $label, $new_solde, $fk_type)
	{
		global $conf, $langs;

		$error = 0;

		$prev_solde = price2num($this->getCPforUser($fk_user_update, $fk_type), 5);
		$new_solde = price2num($new_solde, 5);
		//print "$prev_solde == $new_solde";

		if ($prev_solde == $new_solde) {
			return 0;
		}

		$this->db->begin();

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."holiday_logs (";
		$sql .= "date_action,";
		$sql .= "fk_user_action,";
		$sql .= "fk_user_update,";
		$sql .= "type_action,";
		$sql .= "prev_solde,";
		$sql .= "new_solde,";
		$sql .= "fk_type";
		$sql .= ") VALUES (";
		$sql .= " '".$this->db->idate(dol_now())."',";
		$sql .= " ".((int) $fk_user_action).",";
		$sql .= " ".((int) $fk_user_update).",";
		$sql .= " '".$this->db->escape($label)."',";
		$sql .= " ".((float) $prev_solde).",";
		$sql .= " ".((float) $new_solde).",";
		$sql .= " ".((int) $fk_type);
		$sql .= ")";

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->optRowid = $this->db->last_insert_id(MAIN_DB_PREFIX."holiday_logs");
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::addLogCP ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return $this->optRowid;
		}
	}

	/**
	 *  Liste le log des congés payés
	 *
	 *  @param	string	$order      Filtrage par ordre
	 *  @param  string	$filter     Filtre de séléction
	 *  @return int         		-1 si erreur, 1 si OK et 2 si pas de résultat
	 */
	public function fetchLog($order, $filter)
	{
		$sql = "SELECT";
		$sql .= " cpl.rowid,";
		$sql .= " cpl.date_action,";
		$sql .= " cpl.fk_user_action,";
		$sql .= " cpl.fk_user_update,";
		$sql .= " cpl.type_action,";
		$sql .= " cpl.prev_solde,";
		$sql .= " cpl.new_solde,";
		$sql .= " cpl.fk_type";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday_logs as cpl";
		$sql .= " WHERE cpl.rowid > 0"; // To avoid error with other search and criteria

		// Filtrage de séléction
		if (!empty($filter)) {
			$sql .= " ".$filter;
		}

		// Ordre d'affichage
		if (!empty($order)) {
			$sql .= " ".$order;
		}

		dol_syslog(get_class($this)."::fetchLog", LOG_DEBUG);
		$resql = $this->db->query($sql);

		// Si pas d'erreur SQL
		if ($resql) {
			$i = 0;
			$tab_result = $this->logs;
			$num = $this->db->num_rows($resql);

			// Si pas d'enregistrement
			if (!$num) {
				return 2;
			}

			// On liste les résultats et on les ajoutent dans le tableau
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$tab_result[$i]['rowid'] = $obj->rowid;
				$tab_result[$i]['id'] = $obj->rowid;
				$tab_result[$i]['date_action'] = $obj->date_action;
				$tab_result[$i]['fk_user_action'] = $obj->fk_user_action;
				$tab_result[$i]['fk_user_update'] = $obj->fk_user_update;
				$tab_result[$i]['type_action'] = $obj->type_action;
				$tab_result[$i]['prev_solde'] = $obj->prev_solde;
				$tab_result[$i]['new_solde'] = $obj->new_solde;
				$tab_result[$i]['fk_type'] = $obj->fk_type;

				$i++;
			}
			// Retourne 1 et ajoute le tableau à la variable
			$this->logs = $tab_result;
			return 1;
		} else {
			// Erreur SQL
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *  Return array with list of types
	 *
	 *  @param		int		$active		Status of type. -1 = Both
	 *  @param		int		$affect		Filter on affect (a request will change sold or not). -1 = Both
	 *  @return     array	    		Return array with list of types
	 */
	public function getTypes($active = -1, $affect = -1)
	{
		global $mysoc;

		$sql = "SELECT rowid, code, label, affect, delay, newbymonth";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_holiday_types";
		$sql .= " WHERE (fk_country IS NULL OR fk_country = ".((int) $mysoc->country_id).')';
		$sql .= " AND entity IN (".getEntity('c_holiday_types').")";
		if ($active >= 0) {
			$sql .= " AND active = ".((int) $active);
		}
		if ($affect >= 0) {
			$sql .= " AND affect = ".((int) $affect);
		}
		$sql .= " ORDER BY sortorder";

		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			if ($num) {
				$types = array();
				while ($obj = $this->db->fetch_object($result)) {
					$types[$obj->rowid] = array('id' => $obj->rowid, 'rowid' => $obj->rowid, 'code' => $obj->code, 'label' => $obj->label, 'affect' => $obj->affect, 'delay' => $obj->delay, 'newbymonth' => $obj->newbymonth);
				}

				return $types;
			}
		} else {
			dol_print_error($this->db);
		}

		return array();
	}


	/**
	 *  Load information on object
	 *
	 *  @param  int     $id      Id of object
	 *  @return void
	 */
	public function info($id)
	{
		global $conf;

		$sql = "SELECT f.rowid, f.statut as status,";
		$sql .= " f.date_create as datec,";
		$sql .= " f.tms as date_modification,";
		$sql .= " f.date_valid as datev,";
		$sql .= " f.date_approval as datea,";
		$sql .= " f.date_refuse as dater,";
		$sql .= " f.fk_user_create as fk_user_creation,";
		$sql .= " f.fk_user_modif as fk_user_modification,";
		$sql .= " f.fk_user_valid as fk_user_validation,";
		$sql .= " f.fk_user_approve as fk_user_approval_done,";
		$sql .= " f.fk_validator as fk_user_approval_expected,";
		$sql .= " f.fk_user_refuse as fk_user_refuse";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as f";
		$sql .= " WHERE f.rowid = ".((int) $id);
		$sql .= " AND f.entity = ".$conf->entity;

		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;

				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->date_modification);
				$this->date_validation = $this->db->jdate($obj->datev);
				$this->date_approval = $this->db->jdate($obj->datea);

				$this->user_creation_id = $obj->fk_user_creation;
				$this->user_validation_id = $obj->fk_user_validation;
				$this->user_modification_id = $obj->fk_user_modification;

				if ($obj->status == Holiday::STATUS_APPROVED || $obj->status == Holiday::STATUS_CANCELED) {
					if ($obj->fk_user_approval_done) {
						$this->fk_user_approve = $obj->fk_user_approval_done;
					}
				}
			}
			$this->db->free($resql);
		} else {
			dol_print_error($this->db);
		}
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
		global $user, $langs;

		// Initialise parameters
		$this->id = 0;
		$this->specimen = 1;

		$this->fk_user = $user->id;
		$this->description = 'SPECIMEN description';
		$this->date_debut = dol_now();
		$this->date_fin = dol_now() + (24 * 3600);
		$this->date_valid = dol_now();
		$this->fk_validator = $user->id;
		$this->halfday = 0;
		$this->fk_type = 1;
		$this->status = Holiday::STATUS_VALIDATED;

		return 1;
	}

	/**
	 *      Load this->nb for dashboard
	 *
	 *      @return     int         Return integer <0 if KO, >0 if OK
	 */
	public function loadStateBoard()
	{
		global $user;

		$this->nb = array();

		$sql = "SELECT count(h.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " WHERE h.statut > 1";
		$sql .= " AND h.entity IN (".getEntity('holiday').")";
		if (!$user->hasRight('expensereport', 'readall')) {
			$userchildids = $user->getAllChildIds(1);
			$sql .= " AND (h.fk_user IN (".$this->db->sanitize(implode(',', $userchildids)).")";
			$sql .= " OR h.fk_validator IN (".$this->db->sanitize(implode(',', $userchildids))."))";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["holidays"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param	User	$user   		Object user
	 *      @return WorkboardResponse|int 	Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user)
	{
		// phpcs:enable
		global $conf, $langs;

		if ($user->socid) {
			return -1; // protection pour eviter appel par utilisateur externe
		}

		$now = dol_now();

		$sql = "SELECT h.rowid, h.date_debut";
		$sql .= " FROM ".MAIN_DB_PREFIX."holiday as h";
		$sql .= " WHERE h.statut = 2";
		$sql .= " AND h.entity IN (".getEntity('holiday').")";
		if (!$user->hasRight('expensereport', 'read_all')) {
			$userchildids = $user->getAllChildIds(1);
			$sql .= " AND (h.fk_user IN (".$this->db->sanitize(implode(',', $userchildids)).")";
			$sql .= " OR h.fk_validator IN (".$this->db->sanitize(implode(',', $userchildids))."))";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$langs->load("members");

			$response = new WorkboardResponse();
			$response->warning_delay = $conf->holiday->approve->warning_delay / 60 / 60 / 24;
			$response->label = $langs->trans("HolidaysToApprove");
			$response->labelShort = $langs->trans("ToApprove");
			$response->url = DOL_URL_ROOT.'/holiday/list.php?search_status=2&amp;mainmenu=hrm&amp;leftmenu=holiday';
			$response->img = img_object('', "holiday");

			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;

				if ($this->db->jdate($obj->date_debut) < ($now - $conf->holiday->approve->warning_delay)) {
					$response->nbtodolate++;
				}
			}

			return $response;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}
	/**
	 *	Return clickable link of object (with eventually picto)
	 *
	 *	@param      string	    			$option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @param		array{string,mixed}		$arraydata				Label of holiday type (if known)
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
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.$this->getNomUrl().'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (property_exists($this, 'fk_type')) {
			$return .= '<br>';
			//$return .= '<span class="opacitymedium">'.$langs->trans("Type").'</span> : ';
			$return .= '<div class="info_box-label tdoverflowmax100" title="'.dol_escape_htmltag($arraydata['labeltype']).'">'.dol_escape_htmltag($arraydata['labeltype']).'</div>';
		}
		if (property_exists($this, 'date_debut') && property_exists($this, 'date_fin')) {
			$return .= '<span class="info-box-label small">'.dol_print_date($this->date_debut, 'day').'</span>';
			$return .= ' <span class="opacitymedium small">'.$langs->trans("To").'</span> ';
			$return .= '<span class="info-box-label small">'.dol_print_date($this->date_fin, 'day').'</span>';
			if (!empty($arraydata['nbopenedday'])) {
				$return .= ' ('.$arraydata['nbopenedday'].')';
			}
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
