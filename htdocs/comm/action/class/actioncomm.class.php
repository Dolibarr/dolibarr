<?php
/* Copyright (C) 2002-2004  Rodolphe Quiedeville    <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2017  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2015	    Marcos García		    <marcosgdf@gmail.com>
 * Copyright (C) 2018	    Nicolas ZABOURI	        <info@inovea-conseil.com>
 * Copyright (C) 2018-2023  Frédéric France         <frederic.france@netlogic.fr>
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
 *       \file       htdocs/comm/action/class/actioncomm.class.php
 *       \ingroup    agenda
 *       \brief      File of class to manage agenda events (actions)
 */
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncommreminder.class.php';


/**
 *		Class to manage agenda events (actions)
 */
class ActionComm extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'action';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'actioncomm';

	/**
	 * @var string Name of id column
	 */
	public $table_rowid = 'id';

	/**
	 * @var string Name of icon for actioncomm object. Filename of icon is object_action.png
	 */
	public $picto = 'action';

	/**
	 * @var int 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var integer 0=Default
	 *              1=View may be restricted to sales representative only if no permission to see all or to company of external user if external user
	 *              2=Same than 1 but accept record if fksoc is empty
	 */
	public $restrictiononfksoc = 2;

	/**
	 * @var int Id of the event
	 */
	public $id;

	/**
	 * @var int Id of the event. Use $id as possible
	 */
	public $ref;

	/**
	 * @var int Id into parent table llx_c_actioncomm (used only if option to use type is set)
	 * 			This field is stored info fk_action. It contains the id into table llx_ac_actioncomm.
	 */
	public $type_id;

	/**
	 * @var string Calendar of event (Type of type of event). 'system'=Default calendar, 'systemauto'=Auto calendar, 'birthdate', 'holiday', 'module'=Calendar specific to a module
	 *             This field contains the type into table llx_ac_actioncomm ('system', 'systemauto', ...). It should be named 'type_type'.
	 */
	public $type;

	/**
	 * @var string Code into parent table llx_c_actioncomm (used only if option to use type is set). With default setup, should be AC_OTH_AUTO or AC_OTH.
	 *             This field contains the code into table llx_ac_actioncomm.
	 */
	public $type_code;

	/**
	 * @var string Type label
	 */
	public $type_label;

	/**
	 * @var string Color into parent table llx_c_actioncomm (used only if option to use type is set)
	 */
	public $type_color;

	/**
	 * @var string Picto for type of event (used only if option to use type is set)
	 */
	public $type_picto;

	/**
	 * @var string Free code to identify action. Ie: Agenda trigger add here AC_TRIGGERNAME ('AC_COMPANY_CREATE', 'AC_PROPAL_VALIDATE', ...)
	 * 			   This field is stored into field 'code' into llx_actioncomm.
	 */
	public $code;

	/**
	 * @var string Agenda event label
	 */
	public $label;

	/**
	 * @var string Agenda event label
	 * @deprecated Use $label
	 */
	public $libelle;

	/**
	 * @var integer Date creation record (datec)
	 */
	public $datec;

	/**
	 * @var integer Duration (duree)
	 */
	public $duree;

	/**
	 * @var integer Date modification record (tms)
	 */
	public $datem;

	/**
	 * @var User Object user that create action
	 * @deprecated
	 * @see $authorid
	 */
	public $author;

	/**
	 * @var User Object user that modified action
	 * @deprecated
	 * @see $usermodid
	 */
	public $usermod;

	/**
	 * @var int Id user that create action
	 */
	public $authorid;

	/**
	 * @var int Id user that modified action
	 */
	public $usermodid;

	/**
	 * @var integer Date action start (datep)
	 */
	public $datep;

	/**
	 * @var integer Date action end (datef)
	 */
	public $datef;

	/**
	 * @var integer This is date start action (datep) but modified to not be outside calendar view.
	 */
	public $date_start_in_calendar;

	/**
	 * @var integer This is date end action (datef) but modified to not be outside calendar view.
	 */
	public $date_end_in_calendar;

	/**
	 * @var integer Date action end (datep2)
	 */
	public $datep2;

	/**
	 * @var int -1=Unkown duration
	 * @deprecated
	 */
	public $durationp = -1;

	/**
	 * @var int 1=Event on full day
	 */
	public $fulldayevent = 0;

	/**
	 * @var int 1=???
	 */
	public $ponctuel;

	/**
	 * @var integer Percentage
	 */
	public $percentage;

	/**
	 * @var string Location
	 */
	public $location;

	/**
	 * @var int Transparency (ical standard). Used to say if people assigned to event are busy or not by event. 0=available, 1=busy, 2=busy (refused events)
	 */
	public $transparency;

	/**
	 * @var int 	(0 By default)
	 */
	public $priority;

	/**
	 * @var int[] 	Array of user ids
	 */
	public $userassigned = array();

	/**
	 * @var int 	Id of user owner = fk_user_action into table
	 */
	public $userownerid;

	/**
	 * @var int 	Id of user that has done the event. Used only if AGENDA_ENABLE_DONEBY is set.
	 */
	public $userdoneid;

	/**
	 * @var int[] Array of contact ids
	 */
	public $socpeopleassigned = array();

	/**
	 * @var int[] Array of other contact emails (not user, not contact)
	 */
	public $otherassigned = array();

	/**
	 * @var array	Array of reminders
	 */
	public $reminders = array();

	/**
	 * @var int thirdparty id linked to action
	 */
	public $socid;

	/**
	 * @var int socpeople id linked to action
	 */
	public $contact_id;


	/**
	 * @var Societe|null Company linked to action (optional)
	 * @deprecated
	 * @see $socid
	 */
	public $societe;

	/**
	 * @var Contact|null Contact linked to action (optional)
	 * @deprecated
	 * @see $contact_id
	 */
	public $contact;

	// Properties for links to other objects
	/**
	 * @var int 		Id of linked object
	 */
	public $fk_element; // Id of record

	/**
	 * @var int 		Id of record alternative for API
	 */
	public $elementid;

	/**
	 * @var string 		Type of record. This if property ->element of object linked to.
	 */
	public $elementtype;

	/**
	 * @var string Ical name
	 */
	public $icalname;

	/**
	 * @var string Ical color
	 */
	public $icalcolor;

	/**
	 * @var string Extraparam
	 */
	public $extraparams;

	/**
	 * @var array Actions
	 */
	public $actions = array();

	/**
	 * @var string Email msgid
	 */
	public $email_msgid;

	/**
	 * @var string Email from
	 */
	public $email_from;

	/**
	 * @var string Email sender
	 */
	public $email_sender;

	/**
	 * @var string Email to
	 */
	public $email_to;

	/**
	 * @var string Email tocc
	 */
	public $email_tocc;
	/**
	 * @var string Email tobcc
	 */
	public $email_tobcc;

	/**
	 * @var string Email subject
	 */
	public $email_subject;

	/**
	 * @var string Email errors to
	 */
	public $errors_to;

	/**
	 * @var int number of vote for an event
	 */
	public $num_vote;

	/**
	 * @var int if event is paid
	 */
	public $event_paid;

	/**
	 * @var int status use but Event organisation module
	 */
	public $status;

	/**
	 * Properties to manage the recurring events
	 */
	public $recurid;		/* A string YYYYMMDDHHMMSS shared by allevent of same serie */
	public $recurrule;		/* Rule of recurring */
	public $recurdateend;	/* Repeat until this date */

	public $calling_duration;


	/**
	 * Typical value for a event that is in a todo state
	 */
	const EVENT_TODO = 0;

	/**
	 * Typical value for a event that is in a progress state
	 */
	const EVENT_IN_PROGRESS = 50;

	/**
	 * Typical value for a event that is in a finished state
	 */
	const EVENT_FINISHED = 100;


	public $fields = array();


	/**
	 *      Constructor
	 *
	 *      @param      DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}

	/**
	 *    Add an action/event into database.
	 *    $this->type_id OR $this->type_code must be set.
	 *
	 *    @param	User	$user      		Object user making action
	 *    @param    int		$notrigger		1 = disable triggers, 0 = enable triggers
	 *    @return   int 		        	Id of created event, < 0 if KO
	 */
	public function create(User $user, $notrigger = 0)
	{
		global $langs, $conf;

		$error = 0;
		$now = dol_now();

		// Check parameters
		if (!isset($this->userownerid) || (string) $this->userownerid === '') {	// $this->userownerid may be 0 (anonymous event) or > 0
			dol_syslog("You tried to create an event but mandatory property ownerid was not defined", LOG_WARNING);
			$this->errors[] = 'ErrorActionCommPropertyUserowneridNotDefined';
			return -1;
		}

		// Clean parameters
		$this->label = dol_trunc(trim($this->label), 128);
		$this->location = dol_trunc(trim($this->location), 128);
		$this->note_private = dol_htmlcleanlastbr(trim(empty($this->note_private) ? $this->note : $this->note_private));
		if (empty($this->percentage)) {
			$this->percentage = 0;
		}
		if (empty($this->priority) || !is_numeric($this->priority)) {
			$this->priority = 0;
		}
		if (empty($this->fulldayevent)) {
			$this->fulldayevent = 0;
		}
		if (empty($this->transparency)) {
			$this->transparency = 0;
		}
		if ($this->percentage > 100) {
			$this->percentage = 100;
		}
		if (empty($this->datep) && $this->datep != '0') {	// We should not insert event in calendar without a start date
			$this->datep = $now;
		}
		if (!empty($this->datep) && !empty($this->datef)) {
			$this->durationp = ($this->datef - $this->datep); // deprecated
		}
		if (!empty($this->datep) && !empty($this->datef) && $this->datep > $this->datef) {
			$this->datef = $this->datep;
		}
		if (!isset($this->fk_project) || $this->fk_project < 0) {
			$this->fk_project = 0;
		}
		// For backward compatibility
		if ($this->elementtype == 'facture') {
			$this->elementtype = 'invoice';
		}
		if ($this->elementtype == 'commande') {
			$this->elementtype = 'order';
		}
		if ($this->elementtype == 'contrat') {
			$this->elementtype = 'contract';
		}

		if (!is_array($this->userassigned) && !empty($this->userassigned)) {	// For backward compatibility when userassigned was an int instead of an array
			$tmpid = (int) $this->userassigned;
			$this->userassigned = array();
			$this->userassigned[$tmpid] = array('id'=>$tmpid, 'transparency'=>$this->transparency);
		}

		$userownerid = $this->userownerid;
		$userdoneid = $this->userdoneid;

		// Be sure assigned user is defined as an array of array('id'=>,'mandatory'=>,...).
		if (empty($this->userassigned) || count($this->userassigned) == 0 || !is_array($this->userassigned)) {
			$this->userassigned = array($userownerid=>array('id'=>$userownerid, 'transparency'=>$this->transparency));
		}

		if (!$this->type_id || !$this->type_code) {
			$key = empty($this->type_id) ? $this->type_code : $this->type_id;

			// Get id from code
			$cactioncomm = new CActionComm($this->db);
			$result = $cactioncomm->fetch($key);

			if ($result > 0) {
				$this->type_id = $cactioncomm->id;
				$this->type_code = $cactioncomm->code;
			} elseif ($result == 0) {
				$this->error = $langs->trans('ErrorActionCommBadType', $this->type_id, $this->type_code);
				return -1;
			} else {
				$this->error = $cactioncomm->error;
				return -1;
			}
		}
		$code = empty($this->code) ? $this->type_code : $this->code;

		// Check parameters
		if (!$this->type_id) {
			$this->error = "ErrorWrongParameters";
			return -1;
		}

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm";
		$sql .= "(ref,";
		$sql .= "datec,";
		$sql .= "datep,";
		$sql .= "datep2,";
		$sql .= "durationp,"; // deprecated
		$sql .= "fk_action,";
		$sql .= "code,";
		$sql .= "ref_ext,";
		$sql .= "fk_soc,";
		$sql .= "fk_project,";
		$sql .= "note,";
		$sql .= "fk_contact,";
		$sql .= "fk_user_author,";
		$sql .= "fk_user_action,";
		$sql .= "fk_user_done,";
		$sql .= "label,percent,priority,fulldayevent,location,";
		$sql .= "transparency,";
		$sql .= "fk_element,";
		$sql .= "elementtype,";
		$sql .= "entity,";
		$sql .= "extraparams,";
		// Fields emails
		$sql .= "email_msgid,";
		$sql .= "email_from,";
		$sql .= "email_sender,";
		$sql .= "email_to,";
		$sql .= "email_tocc,";
		$sql .= "email_tobcc,";
		$sql .= "email_subject,";
		$sql .= "errors_to,";
		$sql .= "recurid,";
		$sql .= "recurrule,";
		$sql .= "recurdateend,";
		$sql .= "num_vote,";
		$sql .= "event_paid,";
		$sql .= "status,";
		$sql .= "ip";
		$sql .= ") VALUES (";
		$sql .= "'(PROV)', ";
		$sql .= "'".$this->db->idate($now)."', ";	// date creation
		$sql .= "'".$this->db->idate($this->datep)."', ";	// date start event
		$sql .= (strval($this->datef) != '' ? "'".$this->db->idate($this->datef)."'" : "null").", ";
		$sql .= ((isset($this->durationp) && $this->durationp >= 0 && $this->durationp != '') ? "'".$this->db->escape($this->durationp)."'" : "null").", "; // deprecated
		$sql .= (isset($this->type_id) ? $this->type_id : "null").",";
		$sql .= ($code ? ("'".$this->db->escape($code)."'") : "null").", ";
		$sql .= (!empty($this->ref_ext) ? "'".$this->db->escape($this->ref_ext)."'" : "null").", ";
		$sql .= ((isset($this->socid) && $this->socid > 0) ? ((int) $this->socid) : "null").", ";
		$sql .= ((isset($this->fk_project) && $this->fk_project > 0) ? ((int) $this->fk_project) : "null").", ";
		$sql .= " '".$this->db->escape($this->note_private)."', ";
		$sql .= ((isset($this->contact_id) && $this->contact_id > 0) ? ((int) $this->contact_id) : "null").", "; // deprecated, use ->socpeopleassigned
		$sql .= (isset($user->id) && $user->id > 0 ? $user->id : "null").", ";
		$sql .= ($userownerid > 0 ? $userownerid : "null").", ";
		$sql .= ($userdoneid > 0 ? $userdoneid : "null").", ";
		$sql .= "'".$this->db->escape($this->label)."', ";
		$sql .= "'".$this->db->escape($this->percentage)."', ";
		$sql .= "'".$this->db->escape($this->priority)."', ";
		$sql .= "'".$this->db->escape($this->fulldayevent)."', ";
		$sql .= "'".$this->db->escape($this->location)."', ";
		$sql .= "'".$this->db->escape($this->transparency)."', ";
		$sql .= (!empty($this->fk_element) ? ((int) $this->fk_element) : "null").", ";
		$sql .= (!empty($this->elementtype) ? "'".$this->db->escape($this->elementtype)."'" : "null").", ";
		$sql .= ((int) $conf->entity).",";
		$sql .= (!empty($this->extraparams) ? "'".$this->db->escape($this->extraparams)."'" : "null").", ";
		// Fields emails
		$sql .= (!empty($this->email_msgid) ? "'".$this->db->escape($this->email_msgid)."'" : "null").", ";
		$sql .= (!empty($this->email_from) ? "'".$this->db->escape($this->email_from)."'" : "null").", ";
		$sql .= (!empty($this->email_sender) ? "'".$this->db->escape($this->email_sender)."'" : "null").", ";
		$sql .= (!empty($this->email_to) ? "'".$this->db->escape($this->email_to)."'" : "null").", ";
		$sql .= (!empty($this->email_tocc) ? "'".$this->db->escape($this->email_tocc)."'" : "null").", ";
		$sql .= (!empty($this->email_tobcc) ? "'".$this->db->escape($this->email_tobcc)."'" : "null").", ";
		$sql .= (!empty($this->email_subject) ? "'".$this->db->escape($this->email_subject)."'" : "null").", ";
		$sql .= (!empty($this->errors_to) ? "'".$this->db->escape($this->errors_to)."'" : "null").", ";
		$sql .= (!empty($this->recurid) ? "'".$this->db->escape($this->recurid)."'" : "null").", ";
		$sql .= (!empty($this->recurrule) ? "'".$this->db->escape($this->recurrule)."'" : "null").", ";
		$sql .= (!empty($this->recurdateend) ? "'".$this->db->idate($this->recurdateend)."'" : "null").", ";
		$sql .= (!empty($this->num_vote) ? (int) $this->num_vote : "null").", ";
		$sql .= (!empty($this->event_paid) ? (int) $this->event_paid : 0).", ";
		$sql .= (!empty($this->status) ? (int) $this->status : "0").", ";
		$sql .= (!empty($this->ip) ? "'".$this->db->escape($this->ip)."'" : "null");
		$sql .= ")";

		dol_syslog(get_class($this)."::add", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->ref = $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."actioncomm", "id");
			$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm SET ref='".$this->db->escape($this->ref)."' WHERE id=".$this->id;
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				dol_syslog('Error to process ref: '.$this->db->lasterror(), LOG_ERR);
				$this->errors[] = $this->db->lasterror();
			}
			// Now insert assigned users
			if (!$error) {
				//dol_syslog(var_export($this->userassigned, true));
				$already_inserted = array();
				foreach ($this->userassigned as $key => $val) {
					// Common value with new behavior is to have $val = array('id'=>iduser, 'transparency'=>0|1) and $this->userassigned is an array of iduser => $val.
					if (!is_array($val)) {	// For backward compatibility when $val='id'.
						$val = array('id'=>$val);
					}

					if ($val['id'] > 0) {
						if (!empty($already_inserted[$val['id']])) {
							continue;
						}

						$sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm_resources(fk_actioncomm, element_type, fk_element, mandatory, transparency, answer_status)";
						$sql .= " VALUES(".((int) $this->id).", 'user', ".((int) $val['id']).", ".(empty($val['mandatory']) ? '0' : ((int) $val['mandatory'])).", ".(empty($val['transparency']) ? '0' : ((int) $val['transparency'])).", ".(empty($val['answer_status']) ? '0' : ((int) $val['answer_status'])).")";

						$resql = $this->db->query($sql);
						if (!$resql) {
							$error++;
							dol_syslog('Error to process userassigned: ' . $this->db->lasterror(), LOG_ERR);
							$this->errors[] = $this->db->lasterror();
						} else {
							$already_inserted[$val['id']] = true;
						}
						//var_dump($sql);exit;
					}
				}
			}

			if (!$error) {
				if (!empty($this->socpeopleassigned)) {
					$already_inserted = array();
					foreach ($this->socpeopleassigned as $id => $val) {
						// Common value with new behavior is to have $val = iduser and $this->socpeopleassigned is an array of iduser => $val.
						if (!empty($already_inserted[$id])) {
							continue;
						}

						$sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm_resources(fk_actioncomm, element_type, fk_element, mandatory, transparency, answer_status)";
						$sql .= " VALUES(".((int) $this->id).", 'socpeople', ".((int) $id).", 0, 0, 0)";

						$resql = $this->db->query($sql);
						if (!$resql) {
							$error++;
							dol_syslog('Error to process socpeopleassigned: ' . $this->db->lasterror(), LOG_ERR);
							$this->errors[] = $this->db->lasterror();
						} else {
							$already_inserted[$id] = true;
						}
					}
				}
			}

			if (!$error) {
				// Actions on extra fields
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('ACTION_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return $this->id;
			} else {
				$this->db->rollback();
				return -1;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Load an object from its id and create a new one in database
	 *
	 *  @param	    User	        $fuser      	Object user making action
	 *  @param		int				$socid			Id of thirdparty
	 *  @return		int								New id of clone
	 */
	public function createFromClone(User $fuser, $socid)
	{
		global $hookmanager;

		$error = 0;

		$this->db->begin();

		// Load source object
		$objFrom = clone $this;

		// Retrieve all extrafield
		// fetch optionals attributes and labels
		$this->fetch_optionals();

		//$this->fetch_userassigned();
		$this->fetchResources();

		$this->id = 0;
		$this->recurid = '';
		$this->recurrule = '';
		$this->recurdateend = '';

		// Create clone
		$this->context['createfromclone'] = 'createfromclone';
		$result = $this->create($fuser);
		if ($result < 0) {
			$error++;
		}

		if (!$error) {
			// Hook of thirdparty module
			if (is_object($hookmanager)) {
				$parameters = array('objFrom'=>$objFrom);
				$action = '';
				$reshook = $hookmanager->executeHooks('createFrom', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->setErrorsFromObject($hookmanager);
					$error++;
				}
			}

			// Call trigger
			$result = $this->call_trigger('ACTION_CLONE', $fuser);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		unset($this->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $this->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *  Load object from database
	 *
	 *  @param  int		$id     			Id of action to get
	 *  @param  string	$ref    			Ref of action to get
	 *  @param  string	$ref_ext			Ref ext to get
	 *  @param	string	$email_msgid		Email msgid
	 *  @param	string	$loadresources		1=Load also resources
	 *  @return	int							<0 if KO, >0 if OK
	 */
	public function fetch($id, $ref = '', $ref_ext = '', $email_msgid = '', $loadresources = 1)
	{
		global $langs;

		if (empty($id) && empty($ref) && empty($ref_ext) && empty($email_msgid)) {
			dol_syslog(get_class($this)."::fetch Bad parameters", LOG_WARNING);
			return -1;
		}

		$sql = "SELECT a.id,";
		$sql .= " a.ref as ref,";
		$sql .= " a.entity,";
		$sql .= " a.ref_ext,";
		$sql .= " a.datep,";
		$sql .= " a.datep2,";
		$sql .= " a.durationp,"; // deprecated
		$sql .= " a.datec,";
		$sql .= " a.tms as datem,";
		$sql .= " a.code, a.label, a.note as note_private,";
		$sql .= " a.fk_soc,";
		$sql .= " a.fk_project,";
		$sql .= " a.fk_user_author, a.fk_user_mod,";
		$sql .= " a.fk_user_action, a.fk_user_done,";
		$sql .= " a.fk_contact, a.percent as percentage,";
		$sql .= " a.fk_element as elementid, a.elementtype,";
		$sql .= " a.priority, a.fulldayevent, a.location, a.transparency,";
		$sql .= " a.email_msgid, a.email_subject, a.email_from, a.email_sender, a.email_to, a.email_tocc, a.email_tobcc, a.errors_to,";
		$sql .= " c.id as type_id, c.type as type_type, c.code as type_code, c.libelle as type_label, c.color as type_color, c.picto as type_picto,";
		$sql .= " s.nom as socname,";
		$sql .= " u.firstname, u.lastname as lastname,";
		$sql .= " num_vote, event_paid, a.status";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_actioncomm as c ON a.fk_action=c.id ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_author";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = a.fk_soc";
		$sql .= " WHERE ";
		if ($ref) {
			$sql .= " a.ref = '".$this->db->escape($ref)."'";
		} elseif ($ref_ext) {
			$sql .= " a.ref_ext = '".$this->db->escape($ref_ext)."'";
		} elseif ($email_msgid) {
			$sql .= " a.email_msgid = '".$this->db->escape($email_msgid)."'";
		} else {
			$sql .= " a.id = ".((int) $id);
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				$obj = $this->db->fetch_object($resql);

				$this->id         = $obj->id;
				$this->entity = $obj->entity;
				$this->ref        = $obj->ref;
				$this->ref_ext    = $obj->ref_ext;

				// Properties of parent table llx_c_actioncomm
				$this->type_id    = $obj->type_id;
				$this->type_code  = $obj->type_code;
				$this->type_color = $obj->type_color;
				$this->type_picto = $obj->type_picto;
				$this->type       = $obj->type_type;

				$this->code = $obj->code;
				$this->label = $obj->label;
				$this->datep = $this->db->jdate($obj->datep);
				$this->datef = $this->db->jdate($obj->datep2);

				$this->datec = $this->db->jdate($obj->datec);
				$this->datem = $this->db->jdate($obj->datem);

				$this->note = $obj->note_private; // deprecated
				$this->note_private = $obj->note_private;
				$this->percentage = $obj->percentage;

				$this->authorid = $obj->fk_user_author;
				$this->usermodid = $obj->fk_user_mod;

				if (!is_object($this->author)) {
					$this->author = new User($this->db); // To avoid warning
				}
				$this->author->id = $obj->fk_user_author; // deprecated
				$this->author->firstname = $obj->firstname; // deprecated
				$this->author->lastname = $obj->lastname; // deprecated
				if (!is_object($this->usermod)) {
					$this->usermod = new User($this->db); // To avoid warning
				}
				$this->usermod->id = $obj->fk_user_mod; // deprecated

				$this->userownerid = $obj->fk_user_action;
				$this->priority				= $obj->priority;
				$this->fulldayevent			= $obj->fulldayevent;
				$this->location				= $obj->location;
				$this->transparency			= $obj->transparency;

				$this->socid = $obj->fk_soc; // To have fetch_thirdparty method working
				$this->contact_id = $obj->fk_contact; // To have fetch_contact method working
				$this->fk_project = $obj->fk_project; // To have fetch_projet method working

				//$this->societe->id			= $obj->fk_soc;			// deprecated
				//$this->contact->id			= $obj->fk_contact;		// deprecated

				$this->fk_element = $obj->elementid;
				$this->elementid = $obj->elementid;
				$this->elementtype = $obj->elementtype;

				$this->num_vote = $obj->num_vote;
				$this->event_paid = $obj->event_paid;
				$this->status = $obj->status;

				//email information
				$this->email_msgid=$obj->email_msgid;
				$this->email_from=$obj->email_from;
				$this->email_sender=$obj->email_sender;
				$this->email_to=$obj->email_to;
				$this->email_tocc=$obj->email_tocc;
				$this->email_tobcc=$obj->email_tobcc;
				$this->email_subject=$obj->email_subject;
				$this->errors_to=$obj->errors_to;

				$this->fetch_optionals();

				if ($loadresources) {
					$this->fetchResources();
				}
			}

			$this->db->free($resql);
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		return $num;
	}

	/**
	 *    Initialize $this->userassigned & this->socpeopleassigned array with list of id of user and contact assigned to event
	 *
	 *    @return   int				<0 if KO, >0 if OK
	 */
	public function fetchResources()
	{
		$this->userassigned = array();
		$this->socpeopleassigned = array();

		$sql = 'SELECT fk_actioncomm, element_type, fk_element, answer_status, mandatory, transparency';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'actioncomm_resources';
		$sql .= ' WHERE fk_actioncomm = '.((int) $this->id);
		$sql .= " AND element_type IN ('user', 'socpeople')";
		$resql = $this->db->query($sql);
		if ($resql) {
			// If owner is known, we must but id first into list
			if ($this->userownerid > 0) {
				$this->userassigned[$this->userownerid] = array('id'=>$this->userownerid); // Set first so will be first into list.
			}

			while ($obj = $this->db->fetch_object($resql)) {
				if ($obj->fk_element > 0) {
					switch ($obj->element_type) {
						case 'user':
							$this->userassigned[$obj->fk_element] = array('id'=>$obj->fk_element, 'mandatory'=>$obj->mandatory, 'answer_status'=>$obj->answer_status, 'transparency'=>$obj->transparency);
							if (empty($this->userownerid)) {
								$this->userownerid = $obj->fk_element; // If not defined (should not happened, we fix this)
							}
							break;
						case 'socpeople':
							$this->socpeopleassigned[$obj->fk_element] = array('id'=>$obj->fk_element, 'mandatory'=>$obj->mandatory, 'answer_status'=>$obj->answer_status, 'transparency'=>$obj->transparency);
							break;
					}
				}
			}

			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Initialize this->userassigned array with list of id of user assigned to event
	 *
	 *    @param    bool    $override   Override $this->userownerid when empty. TODO This should be false by default. True is here to fix corrupted data.
	 *    @return   int                 <0 if KO, >0 if OK
	 */
	public function fetch_userassigned($override = true)
	{
		// phpcs:enable
		$sql = "SELECT fk_actioncomm, element_type, fk_element, answer_status, mandatory, transparency";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm_resources";
		$sql .= " WHERE element_type = 'user' AND fk_actioncomm = ".((int) $this->id);

		$resql2 = $this->db->query($sql);
		if ($resql2) {
			$this->userassigned = array();

			// If owner is known, we must but id first into list
			if ($this->userownerid > 0) {
				// Set first so will be first into list.
				$this->userassigned[$this->userownerid] = array('id'=>$this->userownerid);
			}

			while ($obj = $this->db->fetch_object($resql2)) {
				if ($obj->fk_element > 0) {
					$this->userassigned[$obj->fk_element] = array('id'=>$obj->fk_element,
																  'mandatory'=>$obj->mandatory,
																  'answer_status'=>$obj->answer_status,
																  'transparency'=>$obj->transparency);
				}

				if ($override === true) {
					// If not defined (should not happened, we fix this)
					if (empty($this->userownerid)) {
						$this->userownerid = $obj->fk_element;
					}
				}
			}

			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *    Delete event from database
	 *
	 *    @param    int		$notrigger		1 = disable triggers, 0 = enable triggers
	 *    @return   int 					<0 if KO, >0 if OK
	 */
	public function delete($notrigger = 0)
	{
		global $user;

		$error = 0;

		dol_syslog(get_class($this)."::delete", LOG_DEBUG);

		$this->db->begin();

		// remove categorie association
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_actioncomm";
			$sql .= " WHERE fk_actioncomm=".((int) $this->id);

			$res = $this->db->query($sql);
			if (!$res) {
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		// remove actioncomm_resources
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm_resources";
			$sql .= " WHERE fk_actioncomm=".((int) $this->id);

			$res = $this->db->query($sql);
			if (!$res) {
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm_reminder";
			$sql .= " WHERE fk_actioncomm = ".((int) $this->id);

			$res = $this->db->query($sql);
			if (!$res) {
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		// Removed extrafields
		if (!$error) {
			  $result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
			}
		}

		// remove actioncomm
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm";
			$sql .= " WHERE id=".((int) $this->id);

			$res = $this->db->query($sql);
			if (!$res) {
				$this->error = $this->db->lasterror();
				$error++;
			}
		}

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('ACTION_DELETE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				return -2;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *    Update action into database
	 *	  If percentage = 100, on met a jour date 100%
	 *
	 *    @param    User	$user			Object user making change
	 *    @param    int		$notrigger		1 = disable triggers, 0 = enable triggers
	 *    @return   int     				<0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		global $langs, $conf, $hookmanager;

		$error = 0;

		// Clean parameters
		$this->label = trim($this->label);
		$this->note_private = dol_htmlcleanlastbr(trim(!isset($this->note_private) ? $this->note : $this->note_private));
		if (empty($this->percentage)) {
			$this->percentage = 0;
		}
		if (empty($this->priority) || !is_numeric($this->priority)) {
			$this->priority = 0;
		}
		if (empty($this->transparency)) {
			$this->transparency = 0;
		}
		if (empty($this->fulldayevent)) {
			$this->fulldayevent = 0;
		}
		if ($this->percentage > 100) {
			$this->percentage = 100;
		}
		//if ($this->percentage == 100 && ! $this->dateend) $this->dateend = $this->date;
		if ($this->datep && $this->datef) {
			$this->durationp = ($this->datef - $this->datep); // deprecated
		}
		//if ($this->date  && $this->dateend) $this->durationa=($this->dateend - $this->date);
		if ($this->datep && $this->datef && $this->datep > $this->datef) {
			$this->datef = $this->datep;
		}
		//if ($this->date  && $this->dateend && $this->date > $this->dateend) $this->dateend=$this->date;
		if ($this->fk_project < 0) {
			$this->fk_project = 0;
		}

		// Check parameters
		if ($this->percentage == 0 && $this->userdoneid > 0) {
			$this->error = "ErrorCantSaveADoneUserWithZeroPercentage";
			return -1;
		}

		$socid = (($this->socid > 0) ? $this->socid : 0);
		$contactid = (($this->contact_id > 0) ? $this->contact_id : 0);
		$userownerid = ($this->userownerid ? $this->userownerid : 0);
		$userdoneid = ($this->userdoneid ? $this->userdoneid : 0);

		// If a type_id is set, we must also have the type_code set
		if ($this->type_id > 0) {
			if (empty($this->type_code)) {
				$cactioncomm = new CActionComm($this->db);
				$result = $cactioncomm->fetch($this->type_id);
				if ($result >= 0 && !empty($cactioncomm->code)) {
					$this->type_code = $cactioncomm->code;
				}
			}
		}

		$code = $this->code;
		if (empty($code) || (!empty($this->oldcopy) && $this->oldcopy->type_code != $this->type_code)) {	// If code unknown or if we change the type, we reset $code too
			$code = $this->type_code;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm";
		$sql .= " SET percent = '".$this->db->escape($this->percentage)."'";
		$sql .= ", fk_action = ".(int) $this->type_id;
		$sql .= ", code = " . ($code ? "'".$this->db->escape($code)."'" : "null");
		$sql .= ", label = ".($this->label ? "'".$this->db->escape($this->label)."'" : "null");
		$sql .= ", datep = ".(strval($this->datep) != '' ? "'".$this->db->idate($this->datep)."'" : 'null');
		$sql .= ", datep2 = ".(strval($this->datef) != '' ? "'".$this->db->idate($this->datef)."'" : 'null');
		$sql .= ", durationp = ".(isset($this->durationp) && $this->durationp >= 0 && $this->durationp != '' ? "'".$this->db->escape($this->durationp)."'" : "null"); // deprecated
		$sql .= ", note = '".$this->db->escape($this->note_private)."'";
		$sql .= ", fk_project =".($this->fk_project > 0 ? ((int) $this->fk_project) : "null");
		$sql .= ", fk_soc =".($socid > 0 ? ((int) $socid) : "null");
		$sql .= ", fk_contact =".($contactid > 0 ? ((int) $contactid) : "null");
		$sql .= ", priority = '".$this->db->escape($this->priority)."'";
		$sql .= ", fulldayevent = '".$this->db->escape($this->fulldayevent)."'";
		$sql .= ", location = ".($this->location ? "'".$this->db->escape($this->location)."'" : "null");
		$sql .= ", transparency = '".$this->db->escape($this->transparency)."'";
		$sql .= ", fk_user_mod = ".((int) $user->id);
		$sql .= ", fk_user_action = ".($userownerid > 0 ? ((int) $userownerid) : "null");
		$sql .= ", fk_user_done = ".($userdoneid > 0 ? ((int) $userdoneid) : "null");
		if (!empty($this->fk_element)) {
			$sql .= ", fk_element=".($this->fk_element ? ((int) $this->fk_element) : "null");
		}
		if (!empty($this->elementtype)) {
			$sql .= ", elementtype=".($this->elementtype ? "'".$this->db->escape($this->elementtype)."'" : "null");
		}
		if (!empty($this->num_vote)) {
			$sql .= ", num_vote=".($this->num_vote ? (int) $this->num_vote : null);
		}
		if (!empty($this->event_paid)) {
			$sql .= ", event_paid=".($this->event_paid ? (int) $this->event_paid : 0);
		}
		if (!empty($this->status)) {
			$sql .= ", status=".($this->status ? (int) $this->status : 0);
		}
		$sql .= " WHERE id=".((int) $this->id);

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		if ($this->db->query($sql)) {
			$action = 'update';

			// Actions on extra fields
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
			}

			// Now insert assignedusers
			if (!$error) {
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm_resources where fk_actioncomm = ".((int) $this->id)." AND element_type = 'user'";
				$resql = $this->db->query($sql);

				$already_inserted = array();
				foreach ($this->userassigned as $key => $val) {
					if (!is_array($val)) {	// For backward compatibility when val=id
						$val = array('id'=>$val);
					}
					if (!empty($already_inserted[$val['id']])) continue;

					$sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm_resources(fk_actioncomm, element_type, fk_element, mandatory, transparency, answer_status)";
					$sql .= " VALUES(".((int) $this->id).", 'user', ".((int) $val['id']).", ".(empty($val['mandatory']) ? '0' : ((int) $val['mandatory'])).", ".(empty($val['transparency']) ? '0' : ((int) $val['transparency'])).", ".(empty($val['answer_status']) ? '0' : ((int) $val['answer_status'])).")";

					$resql = $this->db->query($sql);
					if (!$resql) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					} else {
						$already_inserted[$val['id']] = true;
					}
					//var_dump($sql);exit;
				}
			}

			if (!$error) {
				$sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm_resources where fk_actioncomm = ".((int) $this->id)." AND element_type = 'socpeople'";
				$resql = $this->db->query($sql);

				if (!empty($this->socpeopleassigned)) {
					$already_inserted = array();
					foreach (array_keys($this->socpeopleassigned) as $key => $val) {
						if (!is_array($val)) {	// For backward compatibility when val=id
							$val = array('id'=>$val);
						}
						if (!empty($already_inserted[$val['id']])) continue;

						$sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm_resources(fk_actioncomm, element_type, fk_element, mandatory, transparency, answer_status)";
						$sql .= " VALUES(".((int) $this->id).", 'socpeople', ".((int) $val['id']).", 0, 0, 0)";

						$resql = $this->db->query($sql);
						if (!$resql) {
							$error++;
							$this->errors[] = $this->db->lasterror();
						} else {
							$already_inserted[$val['id']] = true;
						}
					}
				}
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('ACTION_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				dol_syslog(get_class($this)."::update ".join(',', $this->errors), LOG_ERR);
				return -2;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 *  Load all objects with filters.
	 *  @todo WARNING: This make a fetch on all records instead of making one request with a join.
	 *
	 *  @param		int		$socid			Filter by thirdparty
	 *  @param		int		$fk_element		Id of element action is linked to
	 *  @param		string	$elementtype	Type of element action is linked to
	 *  @param		string	$filter			Other filter
	 *  @param		string	$sortfield		Sort on this field
	 *  @param		string	$sortorder		ASC or DESC
	 *  @param		string	$limit			Limit number of answers
	 *  @return		array|string			Error string if KO, array with actions if OK
	 */
	public function getActions($socid = 0, $fk_element = 0, $elementtype = '', $filter = '', $sortfield = 'a.datep', $sortorder = 'DESC', $limit = 0)
	{
		global $conf, $langs, $hookmanager;

		$resarray = array();

		dol_syslog(get_class()."::getActions", LOG_DEBUG);

		// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($db);
		}
		$hookmanager->initHooks(array('agendadao'));

		$sql = "SELECT a.id";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		// Fields from hook
		$parameters = array('sql' => &$sql, 'socid' => $socid, 'fk_element' => $fk_element, 'elementtype' => $elementtype);
		$reshook = $hookmanager->executeHooks('getActionsListFrom', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) $sql.= $hookmanager->resPrint;
		$sql .= " WHERE a.entity IN (".getEntity('agenda').")";
		if (!empty($socid)) {
			$sql .= " AND a.fk_soc = ".((int) $socid);
		}
		if (!empty($elementtype)) {
			if ($elementtype == 'project') {
				$sql .= ' AND a.fk_project = '.((int) $fk_element);
			} elseif ($elementtype == 'contact') {
				$sql .= ' AND a.id IN';
				$sql .= " (SELECT fk_actioncomm FROM ".MAIN_DB_PREFIX."actioncomm_resources WHERE";
				$sql .= " element_type = 'socpeople' AND fk_element = ".((int) $fk_element).')';
			} else {
				$sql .= " AND a.fk_element = ".((int) $fk_element)." AND a.elementtype = '".$this->db->escape($elementtype)."'";
			}
		}
		if (!empty($filter)) {
			$sql .= $filter;
		}
		// Fields where hook
		$parameters = array('sql' => &$sql, 'socid' => $socid, 'fk_element' => $fk_element, 'elementtype' => $elementtype);
		$reshook = $hookmanager->executeHooks('getActionsListWhere', $parameters);    // Note that $action and $object may have been modified by hook
		if (!empty($hookmanager->resPrint)) $sql.= $hookmanager->resPrint;
		if ($sortorder && $sortfield) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		$sql .= $this->db->plimit($limit, 0);

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);

			if ($num) {
				for ($i = 0; $i < $num; $i++) {
					$obj = $this->db->fetch_object($resql);
					$actioncommstatic = new ActionComm($this->db);
					$actioncommstatic->fetch($obj->id);
					$resarray[$i] = $actioncommstatic;
				}
			}
			$this->db->free($resql);
			return $resarray;
		} else {
			return $this->db->lasterror();
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 * @param	User	$user   			Objet user
	 * @param	int		$load_state_board	Load indicator array this->nb
	 * @return WorkboardResponse|int 		<0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $load_state_board = 0)
	{
		// phpcs:enable
		global $conf, $langs;

		if (empty($load_state_board)) {
			$sql = "SELECT a.id, a.datep as dp";
		} else {
			$this->nb = array();
			$sql = "SELECT count(a.id) as nb";
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
		if (empty($user->rights->societe->client->voir) && !$user->socid) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
		}
		if (!$user->hasRight('agenda', 'allactions', 'read')) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."actioncomm_resources AS ar ON a.id = ar.fk_actioncomm AND ar.element_type ='user' AND ar.fk_element = ".((int) $user->id);
		}
		$sql .= " WHERE 1 = 1";
		if (empty($load_state_board)) {
			$sql .= " AND a.percent >= 0 AND a.percent < 100";
		}
		$sql .= " AND a.entity IN (".getEntity('agenda').")";
		if (empty($user->rights->societe->client->voir) && !$user->socid) {
			$sql .= " AND (a.fk_soc IS NULL OR sc.fk_user = ".((int) $user->id).")";
		}
		if ($user->socid) {
			$sql .= " AND a.fk_soc = ".((int) $user->socid);
		}
		if (!$user->hasRight('agenda', 'allactions', 'read')) {
			$sql .= " AND (a.fk_user_author = ".((int) $user->id)." OR a.fk_user_action = ".((int) $user->id)." OR a.fk_user_done = ".((int) $user->id);
			$sql .= " OR ar.fk_element = ".((int) $user->id);
			$sql .= ")";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			if (empty($load_state_board)) {
				$agenda_static = new ActionComm($this->db);
				$response = new WorkboardResponse();
				$response->warning_delay = $conf->agenda->warning_delay / 60 / 60 / 24;
				$response->label = $langs->trans("ActionsToDo");
				$response->labelShort = $langs->trans("ActionsToDoShort");
				$response->url = DOL_URL_ROOT.'/comm/action/list.php?mode=show_list&actioncode=0&status=todo&mainmenu=agenda';
				if ($user->hasRight("agenda", "allactions", "read")) {
					$response->url .= '&filtert=-1';
				}
				$response->img = img_object('', "action", 'class="inline-block valigntextmiddle"');
			}
			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				if (empty($load_state_board)) {
					$response->nbtodo++;
					$agenda_static->datep = $this->db->jdate($obj->dp);
					if ($agenda_static->hasDelay()) {
						$response->nbtodolate++;
					}
				} else {
					$this->nb["actionscomm"] = $obj->nb;
				}
			}

			$this->db->free($resql);
			if (empty($load_state_board)) {
				return $response;
			} else {
				return 1;
			}
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}


	/**
	 *  Charge les informations d'ordre info dans l'objet facture
	 *
	 *  @param	int		$id       	Id de la facture a charger
	 *  @return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT ';
		$sql .= ' a.id,';
		$sql .= ' datec,';
		$sql .= ' tms as datem,';
		$sql .= ' fk_user_author,';
		$sql .= ' fk_user_mod';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
		$sql .= ' WHERE a.id = '.((int) $id);

		dol_syslog(get_class($this)."::info", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->id;
				$this->user_creation_id = $obj->fk_user_author;
				$this->user_modification_id = $obj->fk_user_mod;
				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = empty($obj->datem) ? '' : $this->db->jdate($obj->datem);
			}
			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}


	/**
	 *  Return the label of the status
	 *
	 *  @param  int		$mode           0=Long label, 1=Short label, 2=Picto+Short label, 3=Picto, 4=Picto+Short label, 5=Short label+Picto, 6=Picto+Long label, 7=Very short label+Picto
	 *  @param  int		$hidenastatus   1=Show nothing if status is "Not applicable"
	 *  @return string          		String with status
	 */
	public function getLibStatut($mode, $hidenastatus = 0)
	{
		return $this->LibStatut($this->percentage, $mode, $hidenastatus, $this->datep);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return label of action status
	 *
	 *  @param  int     $percent        Percent
	 *  @param  int		$mode           0=Long label, 1=Short label, 2=Picto+Short label, 3=Picto, 4=Picto+Short label, 5=Short label+Picto, 6=Picto+Long label, 7=Very short label+Picto
	 *  @param  int		$hidenastatus   1=Show nothing if status is "Not applicable"
	 *  @param  int     $datestart      Date start of event
	 *  @return string		    		Label
	 */
	public function LibStatut($percent, $mode, $hidenastatus = 0, $datestart = '')
	{
		// phpcs:enable
		global $langs;

		$labelStatus = $langs->transnoentitiesnoconv('StatusNotApplicable');
		if ($percent == -1 && !$hidenastatus) {
			$labelStatus = $langs->transnoentitiesnoconv('StatusNotApplicable');
		} elseif ($percent == 0) {
			$labelStatus = $langs->transnoentitiesnoconv('StatusActionToDo').' (0%)';
		} elseif ($percent > 0 && $percent < 100) {
			$labelStatus = $langs->transnoentitiesnoconv('StatusActionInProcess').' ('.$percent.'%)';
		} elseif ($percent >= 100) {
			$labelStatus = $langs->transnoentitiesnoconv('StatusActionDone').' (100%)';
		}

		$labelStatusShort = $langs->transnoentitiesnoconv('StatusNotApplicable');
		if ($percent == -1 && !$hidenastatus) {
			$labelStatusShort = $langs->trans('NA');
		} elseif ($percent == 0) {
			$labelStatusShort = '0%';
		} elseif ($percent > 0 && $percent < 100) {
			$labelStatusShort = $percent.'%';
		} elseif ($percent >= 100) {
			$labelStatusShort = '100%';
		}

		$statusType = 'status9';
		if ($percent == -1 && !$hidenastatus) {
			$statusType = 'status9';
		}
		if ($percent == 0) {
			$statusType = 'status1';
		}
		if ($percent > 0 && $percent < 100) {
			$statusType = 'status3';
		}
		if ($percent >= 100) {
			$statusType = 'status6';
		}

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode);
	}

	/**
	 * getTooltipContentArray
	 * @param array $params params to construct tooltip data
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $conf, $langs, $user;
		$langs->load('agenda');

		$datas = array();
		$nofetch = !empty($params['nofetch']);

		// Set label of type
		$labeltype = '';
		if ($this->type_code) {
			$labeltype = ($langs->transnoentities("Action".$this->type_code) != "Action".$this->type_code) ? $langs->transnoentities("Action".$this->type_code) : $this->type_label;
		}
		if (empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
			if ($this->type_code != 'AC_OTH_AUTO') {
				$labeltype = $langs->trans('ActionAC_MANUAL');
			}
		}

		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans('Action').'</u>';
		if (!empty($this->ref)) {
			$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.dol_escape_htmltag($this->ref);
		}
		if (!empty($this->label)) {
			$datas['title'] = '<br><b>'.$langs->trans('Title').':</b> '.dol_escape_htmltag($this->label);
		}
		if (!empty($labeltype)) {
			$datas['labeltype'] = '<br><b>'.$langs->trans('Type').':</b> '.dol_escape_htmltag($labeltype);
		}
		if (!empty($this->location)) {
			$datas['location'] = '<br><b>'.$langs->trans('Location').':</b> '.dol_escape_htmltag($this->location);
		}
		if (isset($this->transparency)) {
			$datas['transparency'] = '<br><b>'.$langs->trans('Busy').':</b> '.yn($this->transparency);
		}
		if (!empty($this->email_msgid)) {
			$langs->load("mails");
			$datas['space'] = '<br>';
			// $datas['email'] = '<br><b>'.img_picto('', 'email').' '.$langs->trans("Email").'</b>';
			$datas['mailtopic'] = '<br><b>'.$langs->trans('MailTopic').':</b> '.dol_escape_htmltag($this->email_subject);
			$datas['mailfrom'] = '<br><b>'.$langs->trans('MailFrom').':</b> '.str_replace(array('<', '>'), array('&amp;lt', '&amp;gt'), $this->email_from);
			$datas['mailto'] = '<br><b>'.$langs->trans('MailTo').':</b> '.str_replace(array('<', '>'), array('&amp;lt', '&amp;gt'), $this->email_to);
			if (!empty($this->email_tocc)) {
				$datas['mailcc'] = '<br><b>'.$langs->trans('MailCC').':</b> '.str_replace(array('<', '>'), array('&amp;lt', '&amp;gt'), $this->email_tocc);
			}
			/* Disabled because bcc must remain by defintion not visible
			if (!empty($this->email_tobcc)) {
				$datas['mailccc'] = '<br><b>'.$langs->trans('MailCCC').':</b> '.$this->email_tobcc;
			} */
		}
		if (!empty($this->note_private)) {
			$datas['description'] = '<br><b>'.$langs->trans('Description').':</b><br>';
			// Try to limit length of content
			$texttoshow = dolGetFirstLineOfText($this->note_private, 10);
			// Restrict height of content into the tooltip
			$datas['note'] = '<div class="tenlinesmax">';
			$datas['note'] .= (dol_textishtml($texttoshow) ? str_replace(array("\r", "\n"), "", $texttoshow) : str_replace(array("\r", "\n"), '<br>', $texttoshow));
			$datas['note'] .= '</div>';
		}
		// show categories for this record only in ajax to not overload lists
		if (isModEnabled('categorie') && !$nofetch) {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$form = new Form($this->db);
			$datas['categories'] = '<br>' . $form->showCategories($this->id, Categorie::TYPE_ACTIONCOMM, 1);
		}

		return $datas;
	}

	/**
	 *  Return URL of event
	 *  Use $this->id, $this->type_code, $this->label and $this->type_label
	 *
	 *  @param	int		$withpicto				0 = No picto, 1 = Include picto into link, 2 = Only picto
	 *  @param	int		$maxlength				Max number of charaters into label. If negative, use the ref as label.
	 *  @param	string	$classname				Force style class on a link
	 *  @param	string	$option					'' = Link to action, 'birthday'= Link to contact, 'holiday' = Link to leave
	 *  @param	int		$overwritepicto			1 = Overwrite picto with this one
	 *  @param	int   	$notooltip		    	1 = Disable tooltip
	 *  @param  int     $save_lastsearch_value  -1 = Auto, 0 = No save of lastsearch_values when clicking, 1 = Save lastsearch_values whenclicking
	 *  @return	string							Chaine avec URL
	 */
	public function getNomUrl($withpicto = 0, $maxlength = 0, $classname = '', $option = '', $overwritepicto = 0, $notooltip = 0, $save_lastsearch_value = -1)
	{
		global $conf, $langs, $user, $hookmanager, $action;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$canread = 0;
		if ($user->hasRight('agenda', 'myactions', 'read') && ($this->authorid == $user->id || $this->userownerid == $user->id)) {
			$canread = 1; // Can read my event
		}
		if ($user->hasRight('agenda', 'myactions', 'read') && array_key_exists($user->id, $this->userassigned)) {
			$canread = 1; // Can read my event i am assigned
		}
		if ($user->hasRight('agenda', 'allactions', 'read')) {
			$canread = 1; // Can read all event of other
		}
		if (!$canread) {
			$option = 'nolink';
		}

		$label = $this->label;
		if (empty($label)) {
			$label = $this->libelle; // For backward compatibility
		}

		$result = '';

		// Set label of type
		$labeltype = '';
		if ($this->type_code) {
			$labeltype = ($langs->transnoentities("Action".$this->type_code) != "Action".$this->type_code) ? $langs->transnoentities("Action".$this->type_code) : $this->type_label;
		}
		if (empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
			if ($this->type_code != 'AC_OTH_AUTO') {
				$labeltype = $langs->trans('ActionAC_MANUAL');
			}
		}

		$tooltip = img_picto('', $this->picto).' <u>'.$langs->trans('Action').'</u>';
		if (!empty($this->ref)) {
			$tooltip .= '<br><b>'.$langs->trans('Ref').':</b> '.dol_escape_htmltag($this->ref);
		}
		if (!empty($label)) {
			$tooltip .= '<br><b>'.$langs->trans('Title').':</b> '.dol_escape_htmltag($label);
		}
		if (!empty($labeltype)) {
			$tooltip .= '<br><b>'.$langs->trans('Type').':</b> '.dol_escape_htmltag($labeltype);
		}
		if (!empty($this->location)) {
			$tooltip .= '<br><b>'.$langs->trans('Location').':</b> '.dol_escape_htmltag($this->location);
		}
		if (isset($this->transparency)) {
			$tooltip .= '<br><b>'.$langs->trans('Busy').':</b> '.yn($this->transparency);
		}
		if (!empty($this->email_msgid)) {
			$langs->load("mails");
			$tooltip .= '<br>';
			//$tooltip .= '<br><b>'.img_picto('', 'email').' '.$langs->trans("Email").'</b>';
			$tooltip .= '<br><b>'.$langs->trans('MailTopic').':</b> '.dol_escape_htmltag($this->email_subject);
			$tooltip .= '<br><b>'.$langs->trans('MailFrom').':</b> '.str_replace(array('<', '>'), array('&amp;lt', '&amp;gt'), !empty($this->email_from) ? $this->email_from : '');
			$tooltip .= '<br><b>'.$langs->trans('MailTo').':</b> '.str_replace(array('<', '>'), array('&amp;lt', '&amp;gt'), !empty($this->email_to) ? $this->email_to : '');
			if (!empty($this->email_tocc)) {
				$tooltip .= '<br><b>'.$langs->trans('MailCC').':</b> '.str_replace(array('<', '>'), array('&amp;lt', '&amp;gt'), $this->email_tocc);
			}
			/* Disabled because bcc must remain by defintion not visible
			if (!empty($this->email_tobcc)) {
				$tooltip .= '<br><b>'.$langs->trans('MailCCC').':</b> '.$this->email_tobcc;
			} */
		}
		if (!empty($this->note_private)) {
			$tooltip .= '<br><br><b>'.$langs->trans('Description').':</b><br>';
			$texttoshow = dolGetFirstLineOfText($this->note_private, 8);	// Try to limit length of content
			$tooltip .= '<div class="tenlinesmax">';						// Restrict height of content into the tooltip
			$tooltip .= (dol_textishtml($texttoshow) ? str_replace(array("\r", "\n"), "", $texttoshow) : str_replace(array("\r", "\n"), '<br>', $texttoshow));
			$tooltip .= '</div>';
		}
		$linkclose = '';
		$classfortooltip = 'classfortooltip';
		$dataparams = '';
		if (getDolGlobalInt('MAIN_ENABLE_AJAX_TOOLTIP')) {
			$params = [
				'id' => $this->id,
				'objecttype' => $this->element,
				'option' => $option,
				'nofetch' => 1,
			];
			$classfortooltip = 'classforajaxtooltip';
			$dataparams = ' data-params="'.dol_escape_htmltag(json_encode($params)).'"';
			$tooltip = '';
		}
		//if (!empty($conf->global->AGENDA_USE_EVENT_TYPE) && $this->type_color)
		//	$linkclose = ' style="background-color:#'.$this->type_color.'"';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowAction");
				$linkclose .= ' alt="'.dol_escape_htmltag($tooltip, 1).'"';
			}
			$linkclose .= ($tooltip ? ' title="'.dol_escape_htmltag($tooltip, 1).'"' :  ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classname.' '.$classfortooltip.'"';
		} else {
			$linkclose .= ' class="'.$classname.'"';
		}

		$url = '';
		if ($option == 'birthday') {
			$url = DOL_URL_ROOT.'/contact/perso.php?id='.$this->id;
		} elseif ($option == 'holiday') {
			$url = DOL_URL_ROOT.'/holiday/card.php?id='.$this->id;
		} else {
			$url = DOL_URL_ROOT.'/comm/action/card.php?id='.$this->id;
		}

		if ($option !== 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		if ($option == 'nolink') {
			$linkstart = '';
			$linkend = '';
		}

		if ($withpicto == 2) {
			if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
				$label = $labeltype;
			}
			$labelshort = '';
		} else {
			if (!empty($conf->global->AGENDA_USE_EVENT_TYPE) && empty($label)) {
				$label = $labeltype;
			}
			if ($maxlength < 0) {
				$labelshort = $this->ref;
			} else {
				$labelshort = dol_trunc($label, $maxlength);
			}
		}

		if ($withpicto) {
			if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) {	// Add code into ()
				if ($labeltype) {
					$label .= (preg_match('/'.preg_quote($labeltype, '/').'/', $label) ? '' : ' ('.$langs->transnoentities("Action".$this->type_code).')');
				}
			}
		}

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $langs->trans("ShowAction").': '.$label), ($overwritepicto ? $overwritepicto : 'action'), (($this->type_color && $overwritepicto) ? 'style="color: #'.$this->type_color.' !important;" ' : '').($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : ' class="'.(($withpicto != 2) ? 'paddingright ' : '').'"'), 0, 0, $notooltip ? 0 : 1);
		}
		$result .= dol_escape_htmltag($labelshort);
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array('actiondao'));
		$parameters = array('id'=>$this->id, 'getnomurl' => &$result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Return Picto of type of event
	 *
	 *  @return	string							HTML String
	 */
	public function getTypePicto()
	{
		global $conf;

		$imgpicto = '';
		if (!empty($conf->global->AGENDA_USE_EVENT_TYPE)) {
			$color = '';
			if ($this->type_color) {
				$color = 'style="color: #'.$this->type_color.' !important;"';
			}
			if ($this->type_picto) {
				$imgpicto = img_picto('', $this->type_picto, 'class="paddingright"');
			} else {
				if ($this->type_code == 'AC_RDV') {
					$imgpicto = img_picto('', 'meeting', $color, false, 0, 0, '', 'paddingright');
				} elseif ($this->type_code == 'AC_TEL') {
					$imgpicto = img_picto('', 'object_phoning', $color, false, 0, 0, '', 'paddingright');
				} elseif ($this->type_code == 'AC_FAX') {
					$imgpicto = img_picto('', 'object_phoning_fax', $color, false, 0, 0, '', 'paddingright');
				} elseif ($this->type_code == 'AC_EMAIL' || $this->type_code == 'AC_EMAIL_IN' || (!empty($this->code) && preg_match('/_SENTBYMAIL/', $this->code))) {
					$imgpicto = img_picto('', 'object_email', $color, false, 0, 0, '', 'paddingright');
				} elseif ($this->type_code == 'AC_INT') {
					$imgpicto = img_picto('', 'object_intervention', $color, false, 0, 0, '', 'paddingright');
				} elseif (!empty($this->code) && preg_match('/^TICKET_MSG/', $this->code)) {
					$imgpicto = img_picto('', 'object_conversation', $color, false, 0, 0, '', 'paddingright');
				} elseif ($this->type != 'systemauto') {
					$imgpicto = img_picto('', 'user-cog', $color, false, 0, 0, '', 'paddingright');
				} else {
					$imgpicto = img_picto('', 'cog', $color, false, 0, 0, '', 'paddingright');
				}
			}
		} else {
			// 2 picto: 1 for auto, 1 for manual
			if ($this->type != 'systemauto') {
				$imgpicto = img_picto('', 'user-cog', '', false, 0, 0, '', 'paddingright');
			} else {
				$imgpicto = img_picto('', 'cog', '', false, 0, 0, '', 'paddingright');
			}
		}
		return $imgpicto;
	}


	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param  int[]|int 	$categories 	Category or categories IDs
	 * @return int							<0 if KO, >0 if OK
	 */
	public function setCategories($categories)
	{
		// Handle single category
		if (!is_array($categories)) {
			$categories = array($categories);
		}

		// Get current categories
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, Categorie::TYPE_ACTIONCOMM, 'id');

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
				$c->del_type($this, Categorie::TYPE_ACTIONCOMM);
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0) {
				$c->add_type($this, Categorie::TYPE_ACTIONCOMM);
			}
		}
		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Export events from database into a cal file.
	 *
	 * @param string    $format         The format of the export 'vcal', 'ical/ics' or 'rss'
	 * @param string    $type           The type of the export 'event' or 'journal'
	 * @param integer   $cachedelay     Do not rebuild file if date older than cachedelay seconds
	 * @param string    $filename       The name for the exported file.
	 * @param array     $filters        Array of filters. Example array('notolderthan'=>99, 'year'=>..., 'idfrom'=>..., 'notactiontype'=>'systemauto', 'project'=>123, ...)
	 * @param integer   $exportholiday  0 = don't integrate holidays into the export, 1 = integrate holidays into the export
	 * @return integer                  -1 = error on build export file, 0 = export okay
	 */
	public function build_exportfile($format, $type, $cachedelay, $filename, $filters, $exportholiday = 0)
	{
		global $hookmanager;

		// phpcs:enable
		global $conf, $langs, $dolibarr_main_url_root, $mysoc;

		require_once DOL_DOCUMENT_ROOT."/core/lib/xcal.lib.php";
		require_once DOL_DOCUMENT_ROOT."/core/lib/date.lib.php";
		require_once DOL_DOCUMENT_ROOT."/core/lib/files.lib.php";

		dol_syslog(get_class($this)."::build_exportfile Build export file format=".$format.", type=".$type.", cachedelay=".$cachedelay.", filename=".$filename.", filters size=".count($filters), LOG_DEBUG);

		// Check parameters
		if (empty($format)) {
			return -1;
		}

		// Clean parameters
		if (!$filename) {
			$extension = 'vcs';
			if ($format == 'ical') {
				$extension = 'ics';
			}
			$filename = $format.'.'.$extension;
		}

		// Create dir and define output file (definitive and temporary)
		$result = dol_mkdir($conf->agenda->dir_temp);
		$outputfile = $conf->agenda->dir_temp.'/'.$filename;

		$result = 0;

		$buildfile = true;
		$login = ''; $logina = ''; $logind = ''; $logint = '';

		$now = dol_now();

		if ($cachedelay) {
			$nowgmt = dol_now();
			include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			if (dol_filemtime($outputfile) > ($nowgmt - $cachedelay)) {
				dol_syslog(get_class($this)."::build_exportfile file ".$outputfile." is not older than now - cachedelay (".$nowgmt." - ".$cachedelay."). Build is canceled");
				$buildfile = false;
			}
		}

		if ($buildfile) {
			// Build event array
			$eventarray = array();

			$sql = "SELECT a.id,";
			$sql .= " a.datep,"; // Start
			$sql .= " a.datep2,"; // End
			$sql .= " a.durationp,"; // deprecated
			$sql .= " a.datec, a.tms as datem,";
			$sql .= " a.label, a.code, a.note as note_private, a.fk_action as type_id,";
			$sql .= " a.fk_soc,";
			$sql .= " a.fk_user_author, a.fk_user_mod,";
			$sql .= " a.fk_user_action,";
			$sql .= " a.fk_contact, a.percent as percentage,";
			$sql .= " a.fk_element, a.elementtype,";
			$sql .= " a.priority, a.fulldayevent, a.location, a.transparency,";
			$sql .= " u.firstname, u.lastname, u.email,";
			$sql .= " s.nom as socname,";
			$sql .= " c.id as type_id, c.code as type_code, c.libelle as type_label,";
			$sql .= " num_vote, event_paid, a.status";
			$sql .= " FROM (".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."actioncomm as a)";
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_author"; // Link to get author of event for export
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = a.fk_soc";

			$parameters = array('filters' => $filters);
			$reshook = $hookmanager->executeHooks('printFieldListFrom', $parameters); // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;

			// We must filter on assignement table
			if ($filters['logint']) {
				$sql .= ", ".MAIN_DB_PREFIX."actioncomm_resources as ar";
			}
			$sql .= " WHERE a.fk_action=c.id";
			$sql .= " AND a.entity IN (".getEntity('agenda').")";
			foreach ($filters as $key => $value) {
				if ($key == 'notolderthan' && $value != '') {
					$sql .= " AND a.datep >= '".$this->db->idate($now - ($value * 24 * 60 * 60))."'";
				}
				if ($key == 'year') {
					$sql .= " AND a.datep BETWEEN '".$this->db->idate(dol_get_first_day($value, 1))."' AND '".$this->db->idate(dol_get_last_day($value, 12))."'";
				}
				if ($key == 'id') {
					$sql .= " AND a.id=".(is_numeric($value) ? $value : 0);
				}
				if ($key == 'idfrom') {
					$sql .= " AND a.id >= ".(is_numeric($value) ? $value : 0);
				}
				if ($key == 'idto') {
					$sql .= " AND a.id <= ".(is_numeric($value) ? $value : 0);
				}
				if ($key == 'project') {
					$sql .= " AND a.fk_project=".(is_numeric($value) ? $value : 0);
				}
				if ($key == 'actiontype') {
					$sql .= " AND c.type = '".$this->db->escape($value)."'";
				}
				if ($key == 'notactiontype') {
					$sql .= " AND c.type <> '".$this->db->escape($value)."'";
				}
				// We must filter on assignement table
				if ($key == 'logint') {
					$sql .= " AND ar.fk_actioncomm = a.id AND ar.element_type='user'";
				}
				if ($key == 'logina') {
					$logina = $value;
					$condition = '=';
					if (preg_match('/^!/', $logina)) {
						$logina = preg_replace('/^!/', '', $logina);
						$condition = '<>';
					}
					$userforfilter = new User($this->db);
					$result = $userforfilter->fetch('', $logina);
					if ($result > 0) {
						$sql .= " AND a.fk_user_author ".$condition." ".$userforfilter->id;
					} elseif ($result < 0 || $condition == '=') {
						$sql .= " AND a.fk_user_author = 0";
					}
				}
				if ($key == 'logint') {
					$logint = $value;
					$condition = '=';
					if (preg_match('/^!/', $logint)) {
						$logint = preg_replace('/^!/', '', $logint);
						$condition = '<>';
					}
					$userforfilter = new User($this->db);
					$result = $userforfilter->fetch('', $logint);
					if ($result > 0) {
						$sql .= " AND ar.fk_element = ".((int) $userforfilter->id);
					} elseif ($result < 0 || $condition == '=') {
						$sql .= " AND ar.fk_element = 0";
					}
				}
				if ($key == 'module') {
					$sql .= " AND c.module LIKE '%".$this->db->escape($value)."'";
				}
				if ($key == 'status') {
					$sql .= " AND a.status =".((int) $value);
				}
			}

			$sql .= " AND a.datep IS NOT NULL"; // To exclude corrupted events and avoid errors in lightning/sunbird import

			$parameters = array('filters' => $filters);
			$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
			$sql .= $hookmanager->resPrint;

			$sql .= " ORDER by datep";
			//print $sql;exit;

			dol_syslog(get_class($this)."::build_exportfile select events", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$diff = 0;
				while ($obj = $this->db->fetch_object($resql)) {
					$qualified = true;

					// 'eid','startdate','duration','enddate','title','summary','category','email','url','desc','author'
					$event = array();
					$event['uid'] = 'dolibarragenda-'.$this->db->database_name.'-'.$obj->id."@".$_SERVER["SERVER_NAME"];
					$event['type'] = $type;

					$datestart = $this->db->jdate($obj->datep) - (empty($conf->global->AGENDA_EXPORT_FIX_TZ) ? 0 : ($conf->global->AGENDA_EXPORT_FIX_TZ * 3600));

					// fix for -> Warning: A non-numeric value encountered
					if (is_numeric($this->db->jdate($obj->datep2))) {
						$dateend = $this->db->jdate($obj->datep2) - (empty($conf->global->AGENDA_EXPORT_FIX_TZ) ? 0 : ($conf->global->AGENDA_EXPORT_FIX_TZ * 3600));
					} else {
						// use start date as fall-back to avoid pb with empty end date on ICS readers
						$dateend = $datestart;
					}

					$duration = ($datestart && $dateend) ? ($dateend - $datestart) : 0;
					$event['summary'] = $obj->label.($obj->socname ? " (".$obj->socname.")" : "");

					$event['desc'] = $obj->note_private;
					$event['startdate'] = $datestart;
					$event['enddate'] = $dateend; // Not required with type 'journal'
					$event['duration'] = $duration; // Not required with type 'journal'
					$event['author'] = dolGetFirstLastname($obj->firstname, $obj->lastname);
					$event['priority'] = $obj->priority;
					$event['fulldayevent'] = $obj->fulldayevent;
					$event['location'] = $obj->location;
					$event['transparency'] = (($obj->transparency > 0) ? 'OPAQUE' : 'TRANSPARENT'); // OPAQUE (busy) or TRANSPARENT (not busy)
					$event['category'] = $obj->type_label;
					$event['email'] = $obj->email;
					// Define $urlwithroot
					$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
					$urlwithroot = $urlwithouturlroot.DOL_URL_ROOT; // This is to use external domain name found into config file
					//$urlwithroot=DOL_MAIN_URL_ROOT;						// This is to use same domain name than current
					$url = $urlwithroot.'/comm/action/card.php?id='.$obj->id;
					$event['url'] = $url;
					$event['created'] = $this->db->jdate($obj->datec) - (empty($conf->global->AGENDA_EXPORT_FIX_TZ) ? 0 : ($conf->global->AGENDA_EXPORT_FIX_TZ * 3600));
					$event['modified'] = $this->db->jdate($obj->datem) - (empty($conf->global->AGENDA_EXPORT_FIX_TZ) ? 0 : ($conf->global->AGENDA_EXPORT_FIX_TZ * 3600));
					$event['num_vote'] = $this->num_vote;
					$event['event_paid'] = $this->event_paid;
					$event['status'] = $this->status;

					// TODO: find a way to call "$this->fetch_userassigned();" without override "$this" properties
					$this->id = $obj->id;
					$this->fetch_userassigned(false);

					$assignedUserArray = array();

					foreach ($this->userassigned as $key => $value) {
						$assignedUser = new User($this->db);
						$assignedUser->fetch($value['id']);

						$assignedUserArray[$key] = $assignedUser;
					}

					$event['assignedUsers'] = $assignedUserArray;

					if ($qualified && $datestart) {
						$eventarray[] = $event;
					}
					$diff++;
				}

				$parameters = array('filters' => $filters, 'eventarray' => &$eventarray);
				$reshook = $hookmanager->executeHooks('addMoreEventsExport', $parameters); // Note that $action and $object may have been modified by hook
				if ($reshook > 0) {
					$eventarray = $hookmanager->resArray;
				}
			} else {
				$this->error = $this->db->lasterror();
				return -1;
			}

			if ($exportholiday == 1) {
				$langs->load("holiday");
				$title = $langs->transnoentities("Holidays");

				$sql = "SELECT u.rowid as uid, u.lastname, u.firstname, u.email, u.statut, x.rowid, x.date_debut as date_start, x.date_fin as date_end, x.halfday, x.statut as status";
				$sql .= " FROM ".MAIN_DB_PREFIX."holiday as x, ".MAIN_DB_PREFIX."user as u";
				$sql .= " WHERE u.rowid = x.fk_user";
				$sql .= " AND u.statut = '1'"; // Show only active users  (0 = inactive user, 1 = active user)
				$sql .= " AND (x.statut = '2' OR x.statut = '3')"; // Show only public leaves (2 = leave wait for approval, 3 = leave approved)

				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i   = 0;

					while ($i < $num) {
						$obj   = $this->db->fetch_object($resql);
						$event = array();

						if ($obj->halfday == 1) {
							$event['fulldayevent'] = false;

							$timestampStart = dol_stringtotime($obj->date_start." 00:00:00", 0);
							$timestampEnd   = dol_stringtotime($obj->date_end." 12:00:00", 0);
						} elseif ($obj->halfday == -1) {
							$event['fulldayevent'] = false;

							$timestampStart = dol_stringtotime($obj->date_start." 12:00:00", 0);
							$timestampEnd   = dol_stringtotime($obj->date_end." 23:59:59", 0);
						} else {
							$event['fulldayevent'] = true;

							$timestampStart = dol_stringtotime($obj->date_start." 00:00:00", 0);
							$timestampEnd   = dol_stringtotime($obj->date_end." 23:59:59", 0);
						}

						if (!empty($conf->global->AGENDA_EXPORT_FIX_TZ)) {
							$timestampStart = $timestampStart - ($conf->global->AGENDA_EXPORT_FIX_TZ * 3600);
							$timestampEnd   = $timestampEnd - ($conf->global->AGENDA_EXPORT_FIX_TZ * 3600);
						}

						$urlwithouturlroot = preg_replace('/'.preg_quote(DOL_URL_ROOT, '/').'$/i', '', trim($dolibarr_main_url_root));
						$urlwithroot       = $urlwithouturlroot.DOL_URL_ROOT;
						$url               = $urlwithroot.'/holiday/card.php?id='.$obj->rowid;

						$event['uid']          = 'dolibarrholiday-'.$this->db->database_name.'-'.$obj->rowid."@".$_SERVER["SERVER_NAME"];
						$event['author']       = dolGetFirstLastname($obj->firstname, $obj->lastname);
						$event['type']         = 'event';
						$event['category']     = "Holiday";
						$event['transparency'] = 'OPAQUE';
						$event['email']        = $obj->email;
						$event['created']      = $timestampStart;
						$event['modified']     = $timestampStart;
						$event['startdate']    = $timestampStart;
						$event['enddate']      = $timestampEnd;
						$event['duration']     = $timestampEnd - $timestampStart;
						$event['url']          = $url;

						if ($obj->status == 2) {
							// 2 = leave wait for approval
							$event['summary'] = $title." - ".$obj->lastname." (wait for approval)";
						} else {
							// 3 = leave approved
							$event['summary'] = $title." - ".$obj->lastname;
						}

						$eventarray[] = $event;

						$i++;
					}
				}
			}

			$langs->load("agenda");

			// Define title and desc
			$more = '';
			if ($login) {
				$more = $langs->transnoentities("User").' '.$login;
			}
			if ($logina) {
				$more = $langs->transnoentities("ActionsAskedBy").' '.$logina;
			}
			if ($logint) {
				$more = $langs->transnoentities("ActionsToDoBy").' '.$logint;
			}
			if ($logind) {
				$more = $langs->transnoentities("ActionsDoneBy").' '.$logind;
			}
			if ($more) {
				$title = 'Dolibarr actions '.$mysoc->name.' - '.$more;
				$desc = $more;
				$desc .= ' ('.$mysoc->name.' - built by Dolibarr)';
			} else {
				$title = 'Dolibarr actions '.$mysoc->name;
				$desc = $langs->transnoentities('ListOfActions');
				$desc .= ' ('.$mysoc->name.' - built by Dolibarr)';
			}

			// Create temp file
			$outputfiletmp = tempnam($conf->agenda->dir_temp, 'tmp'); // Temporary file (allow call of function by different threads
			dolChmod($outputfiletmp);

			// Write file
			if ($format == 'vcal') {
				$result = build_calfile($format, $title, $desc, $eventarray, $outputfiletmp);
			} elseif ($format == 'ical') {
				$result = build_calfile($format, $title, $desc, $eventarray, $outputfiletmp);
			} elseif ($format == 'rss') {
				$result = build_rssfile($format, $title, $desc, $eventarray, $outputfiletmp);
			}

			if ($result >= 0) {
				if (dol_move($outputfiletmp, $outputfile, 0, 1, 0, 0)) {
					$result = 1;
				} else {
					$this->error = 'Failed to rename '.$outputfiletmp.' into '.$outputfile;
					dol_syslog(get_class($this)."::build_exportfile ".$this->error, LOG_ERR);
					dol_delete_file($outputfiletmp, 0, 1);
					$result = -1;
				}
			} else {
				dol_syslog(get_class($this)."::build_exportfile build_xxxfile function fails to for format=".$format." outputfiletmp=".$outputfile, LOG_ERR);
				dol_delete_file($outputfiletmp, 0, 1);
				$langs->load("errors");
				$this->error = $langs->trans("ErrorFailToCreateFile", $outputfile);
			}
		}

		return $result;
	}

	/**
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 *  id must be 0 if object instance is a specimen.
	 *
	 *  @return	int >0 if ok
	 */
	public function initAsSpecimen()
	{
		global $user;

		$now = dol_now();

		// Initialise parametres
		$this->id = 0;
		$this->specimen = 1;

		$this->type_code = 'AC_OTH';
		$this->code = 'AC_SPECIMEN_CODE';
		$this->label = 'Label of event Specimen';
		$this->datec = $now;
		$this->datem = $now;
		$this->datep = $now;
		$this->datef = $now;
		$this->fulldayevent = 0;
		$this->percentage = 0;
		$this->status = 0;
		$this->location = 'Location';
		$this->transparency = 1; // 1 means opaque
		$this->priority = 1;
		//$this->note_public = "This is a 'public' note.";
		$this->note_private = "This is a 'private' note.";

		$this->userownerid = $user->id;
		$this->userassigned[$user->id] = array('id'=>$user->id, 'transparency'=> 1);
		return 1;
	}

	/**
	 *  Function used to replace a thirdparty id with another one.
	 *
	 * @param 	DoliDB 	$dbs 		Database handler, because function is static we name it $dbs not $db to avoid breaking coding test
	 * @param 	int 	$origin_id 	Old thirdparty id
	 * @param 	int 	$dest_id 	New thirdparty id
	 * @return 	bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'actioncomm'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}

	/**
	 *  Function used to replace a product id with another one.
	 *
	 *  @param DoliDB $dbs Database handler
	 *  @param int $origin_id Old product id
	 *  @param int $dest_id New product id
	 *  @return bool
	 */
	public static function replaceProduct(DoliDB $dbs, $origin_id, $dest_id)
	{
		$sql = 'UPDATE ' . MAIN_DB_PREFIX . 'actioncomm SET fk_element = ' . ((int) $dest_id) . ' WHERE elementtype="product" AND fk_element = '.((int) $origin_id);
		// using $dbs, not $this->db because function is static
		if (!$dbs->query($sql)) {
			//$this->errors = $dbs->lasterror();
			return false;
		}

		return true;
	}

	/**
	 *  Is the action delayed?
	 *
	 *  @return bool
	 */
	public function hasDelay()
	{
		global $conf;

		$now = dol_now();

		return $this->datep && ($this->datep < ($now - $conf->agenda->warning_delay));
	}


	/**
	 *  Load event reminder of events
	 *
	 *  @param	string	$type		Type of reminder 'browser' or 'email'
	 *  @param	int		$fk_user	Id of user
	 *  @param	bool	$onlypast	true = get only past reminder, false = get all reminders linked to this
	 *  @return int         		0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function loadReminders($type = '', $fk_user = 0, $onlypast = true)
	{
		global $conf, $langs, $user;

		$error = 0;

		$this->reminders = array();

		//Select all action comm reminders for event
		$sql = "SELECT rowid as id, typeremind, dateremind, status, offsetvalue, offsetunit, fk_user, fk_email_template, lasterror";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm_reminder";
		$sql .= " WHERE fk_actioncomm = ".((int) $this->id);
		if ($onlypast) {
			$sql .= " AND dateremind <= '".$this->db->idate(dol_now())."'";
		}
		if ($type) {
			$sql .= " AND typeremind = '".$this->db->escape($type)."'";
		}
		if ($fk_user > 0) {
			$sql .= " AND fk_user = ".((int) $fk_user);
		}
		if (empty($conf->global->AGENDA_REMINDER_EMAIL)) {
			$sql .= " AND typeremind <> 'email'";
		}
		if (empty($conf->global->AGENDA_REMINDER_BROWSER)) {
			$sql .= " AND typeremind <> 'browser'";
		}

		$sql .= $this->db->order("dateremind", "ASC");
		$resql = $this->db->query($sql);

		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$tmpactioncommreminder = new ActionCommReminder($this->db);
				$tmpactioncommreminder->id = $obj->id;
				$tmpactioncommreminder->typeremind = $obj->typeremind;
				$tmpactioncommreminder->dateremind = $obj->dateremind;
				$tmpactioncommreminder->offsetvalue = $obj->offsetvalue;
				$tmpactioncommreminder->offsetunit = $obj->offsetunit;
				$tmpactioncommreminder->status = $obj->status;
				$tmpactioncommreminder->fk_user = $obj->fk_user;
				$tmpactioncommreminder->fk_email_template = $obj->fk_email_template;
				$tmpactioncommreminder->lasterror = $obj->lasterror;

				$this->reminders[$obj->id] = $tmpactioncommreminder;
			}
		} else {
			$this->error = $this->db->lasterror();
			$error++;
		}

		return count($this->reminders);
	}


	/**
	 *  Send reminders by emails
	 *  CAN BE A CRON TASK
	 *
	 *  @return int         0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function sendEmailsReminder()
	{
		global $conf, $langs, $user;

		$error = 0;
		$this->output = '';
		$this->error = '';
		$nbMailSend = 0;
		$errorsMsg = array();

		if (!isModEnabled('agenda')) {	// Should not happen. If module disabled, cron job should not be visible.
			$langs->load("agenda");
			$this->output = $langs->trans('ModuleNotEnabled', $langs->transnoentitiesnoconv("Agenda"));
			return 0;
		}
		if (empty($conf->global->AGENDA_REMINDER_EMAIL)) {
			$langs->load("agenda");
			$this->output = $langs->trans('EventRemindersByEmailNotEnabled', $langs->transnoentitiesnoconv("Agenda"));
			return 0;
		}

		$now = dol_now();
		$actionCommReminder = new ActionCommReminder($this->db);

		dol_syslog(__METHOD__." start", LOG_INFO);

		$this->db->begin();

		//Select all action comm reminders
		$sql = "SELECT rowid as id FROM ".MAIN_DB_PREFIX."actioncomm_reminder";
		$sql .= " WHERE typeremind = 'email'";
		$sql .= " AND status = 0";	// 0=No yet sent, -1=Error. TODO Include reminder in error once we can count number of error, so we can try 5 times and not more on errors.
		$sql .= " AND dateremind <= '".$this->db->idate($now)."'";
		$sql .= " AND entity IN (".getEntity('actioncomm').")";
		$sql .= $this->db->order("dateremind", "ASC");
		$resql = $this->db->query($sql);

		if ($resql) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
			$formmail = new FormMail($this->db);

			while ($obj = $this->db->fetch_object($resql)) {
				$res = $actionCommReminder->fetch($obj->id);
				if ($res < 0) {
					$error++;
					$errorsMsg[] = "Failed to load invoice ActionComm Reminder";
				}

				if (!$error) {
					//Select email template
					$arraymessage = $formmail->getEMailTemplate($this->db, 'actioncomm_send', $user, $langs, (!empty($actionCommReminder->fk_email_template)) ? $actionCommReminder->fk_email_template : -1, 1);

					// Load event
					$res = $this->fetch($actionCommReminder->fk_actioncomm);
					if ($res > 0) {
						// PREPARE EMAIL
						$errormesg = '';

						// Make substitution in email content
						$substitutionarray = getCommonSubstitutionArray($langs, 0, '', $this);

						complete_substitutions_array($substitutionarray, $langs, $this);

						// Content
						$sendContent = make_substitutions($langs->trans($arraymessage->content), $substitutionarray);

						//Topic
						$sendTopic = (!empty($arraymessage->topic)) ? $arraymessage->topic : html_entity_decode($langs->transnoentities('EventReminder'));

						// Recipient
						$recipient = new User($this->db);
						$res = $recipient->fetch($actionCommReminder->fk_user);
						if ($res > 0) {
							if (!empty($recipient->email)) {
								$to = $recipient->email;
							} else {
								$errormesg = "Failed to send remind to user id=".$actionCommReminder->fk_user.". No email defined for user.";
								$error++;
							}
						} else {
							$errormesg = "Failed to load recipient with user id=".$actionCommReminder->fk_user;
							$error++;
						}

						// Sender
						$from = getDolGlobalString('MAIN_MAIL_EMAIL_FROM');
						if (empty($from)) {
							$errormesg = "Failed to get sender into global setup MAIN_MAIL_EMAIL_FROM";
							$error++;
						}

						if (!$error) {
							// Errors Recipient
							$errors_to = getDolGlobalString('MAIN_MAIL_ERRORS_TO');

							// Mail Creation
							$cMailFile = new CMailFile($sendTopic, $to, $from, $sendContent, array(), array(), array(), '', "", 0, 1, $errors_to, '', '', '', '', '');

							// Sending Mail
							if ($cMailFile->sendfile()) {
								$nbMailSend++;
							} else {
								$errormesg = 'Failed to send email to: '.$to.' '.$cMailFile->error.join(',', $cMailFile->errors);
								$error++;
							}
						}

						if (!$error) {
							$actionCommReminder->status = $actionCommReminder::STATUS_DONE;

							$res = $actionCommReminder->update($user);
							if ($res < 0) {
								$errorsMsg[] = "Failed to update status to done of ActionComm Reminder";
								$error++;
								break; // This is to avoid to have this error on all the selected email. If we fails here for one record, it may fails for others. We must solve first.
							}
						} else {
							$actionCommReminder->status = $actionCommReminder::STATUS_ERROR;
							$actionCommReminder->lasterror = dol_trunc($errormesg, 128, 'right', 'UTF-8', 1);

							$res = $actionCommReminder->update($user);
							if ($res < 0) {
								$errorsMsg[] = "Failed to update status to error of ActionComm Reminder";
								$error++;
								break; // This is to avoid to have this error on all the selected email. If we fails here for one record, it may fails for others. We must solve first.
							} else {
								$errorsMsg[] = $errormesg;
							}
						}
					} else {
						$errorsMsg[] = 'Failed to fetch record actioncomm with ID = '.$actionCommReminder->fk_actioncomm;
						$error++;
					}
				}
			}
		} else {
			$error++;
		}

		if (!$error) {
			// Delete also very old past events (we do not keep more than 1 month record in past)
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm_reminder";
			$sql .= " WHERE dateremind < '".$this->db->idate($now - (3600 * 24 * 32))."'";
			$sql .= " AND status = ".((int) $actionCommReminder::STATUS_DONE);
			$resql = $this->db->query($sql);

			if (!$resql) {
				$errorsMsg[] = 'Failed to delete old reminders';
				//$error++;		// If this fails, we must not rollback other SQL requests already done. Never mind.
			}
		}

		if (!$error) {
			$this->output = 'Nb of emails sent : '.$nbMailSend;
			$this->db->commit();

			dol_syslog(__METHOD__." end - ".$this->output, LOG_INFO);

			return 0;
		} else {
			$this->db->commit(); // We commit also on error, to have the error message recorded.
			$this->error = 'Nb of emails sent : '.$nbMailSend.', '.(!empty($errorsMsg)) ? join(', ', $errorsMsg) : $error;

			dol_syslog(__METHOD__." end - ".$this->error, LOG_INFO);

			return $error;
		}
	}

	/**
	 * Udpate the percent value of a event with the given id
	 *
	 * @param int		$id			The id of the event
	 * @param int		$percent	The new percent value for the event
	 * @param int		$usermodid	The user who modified the percent
	 * @return int					1 when update of the event was suscessfull, otherwise -1
	 */
	public function updatePercent($id, $percent, $usermodid = 0)
	{
		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm ";
		$sql .= " SET percent = ".(int) $percent;
		if ($usermodid > 0) $sql .= ", fk_user_mod = ".$usermodid;
		$sql .= " WHERE id = ".((int) $id);

		if ($this->db->query($sql)) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}
}
