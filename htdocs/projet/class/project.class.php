<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2020 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2017 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2019      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2022      Charlene Benke       <charlene@patas-monkey.com>
 * Copyright (C) 2023      Gauthier VERDOL      <gauthier.verdol@atm-consulting.fr>
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
 * 		\file       htdocs/projet/class/project.class.php
 * 		\ingroup    projet
 * 		\brief      File of class to manage projects
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';

/**
 *	Class to manage projects
 */
class Project extends CommonObject
{

	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'project';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'projet';

	/**
	 * @var string    Name of subtable line
	 */
	public $table_element_line = 'projet_task';

	/**
	 * @var string    Name of field date
	 */
	public $table_element_date;

	/**
	 * @var string Field with ID of parent key if this field has a parent
	 */
	public $fk_element = 'fk_projet';

	/**
	 * 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 * @var int
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'project';

	/**
	 * {@inheritdoc}
	 */
	protected $table_ref_field = 'ref';

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * @var int 	Date start
	 * @deprecated
	 * @see $date_start
	 */
	public $dateo;

	/**
	 * @var int 	Date start
	 */
	public $date_start;

	/**
	 * @var int 	Date end
	 * @deprecated
	 * @see $date_end
	 */
	public $datee;

	/**
	 * @var int 	Date end
	 */
	public $date_end;

	/**
	 * @var int 	Date start event
	 */
	public $date_start_event;

	/**
	 * @var int 	Date end event
	 */
	public $date_end_event;

	/**
	 * @var string	Location
	 */
	public $location;

	/**
	 * @var int Date close
	 */
	public $date_close;

	public $socid; // To store id of thirdparty
	public $thirdparty_name; // To store name of thirdparty (defined only in some cases)

	public $user_author_id; //!< Id of project creator. Not defined if shared project.

	/**
	 * @var int user close id
	 */
	public $fk_user_close;

	/**
	 * @var int user close id
	 */
	public $user_close_id;
	public $public; //!< Tell if this is a public or private project

	/**
	 * @var float budget Amount
	 */
	public $budget_amount;

	/**
	 * @var integer		Can use projects to follow opportunities
	 */
	public $usage_opportunity;

	/**
	 * @var integer		Can follow tasks on project and enter time spent on it
	 */
	public $usage_task;

	/**
	 * @var integer	 	Use to bill task spend time
	 */
	public $usage_bill_time; // Is the time spent on project must be invoiced or not

	/**
	   * @var integer		Event organization: Use Event Organization
	   */
	public $usage_organize_event;

	/**
	 * @var integer		Event organization: Allow unknown people to suggest new conferences
	 */
	public $accept_conference_suggestions;

	/**
	 * @var integer		Event organization: Allow unknown people to suggest new booth
	 */
	public $accept_booth_suggestions;

	/**
	 * @var float Event organization: registration price
	 */
	public $price_registration;

	/**
	 * @var float Event organization: booth price
	 */
	public $price_booth;

	/**
	 * @var float Max attendees
	 */
	public $max_attendees;

	public $statuts_short;
	public $statuts_long;

	public $statut; // 0=draft, 1=opened, 2=closed

	public $opp_status; // opportunity status, into table llx_c_lead_status
	public $opp_status_code;
	public $fk_opp_status; // opportunity status, into table llx_c_lead_status
	public $opp_amount; // opportunity amount
	public $opp_percent; // opportunity probability
	public $opp_weighted_amount; // opportunity weighted amount

	public $email_msgid;

	public $oldcopy;

	public $weekWorkLoad; // Used to store workload details of a projet
	public $weekWorkLoadPerTask; // Used to store workload details of tasks of a projet

	/**
	 * @var int Creation date
	 * @deprecated
	 * @see $date_c
	 */
	public $datec;

	/**
	 * @var int Creation date
	 */
	public $date_c;

	/**
	 * @var int Modification date
	 * @deprecated
	 * @see $date_m
	 */
	public $datem;

	/**
	 * @var int Modification date
	 */
	public $date_m;

	/**
	 * @var Task[]
	 */
	public $lines;

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' is the CSS style to use on field. For example: 'maxwidth200'
	 *  'help' is a string visible as a tooltip on field
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' =>array('type'=>'integer', 'label'=>'ID', 'enabled'=>1, 'visible'=>-1, 'notnull'=>1, 'position'=>10),
		'ref' =>array('type'=>'varchar(50)', 'label'=>'Ref', 'enabled'=>1, 'visible'=>1, 'showoncombobox'=>1, 'position'=>15, 'searchall'=>1),
		'title' =>array('type'=>'varchar(255)', 'label'=>'ProjectLabel', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'position'=>17, 'showoncombobox'=>2, 'searchall'=>1),
		'entity' =>array('type'=>'integer', 'label'=>'Entity', 'default'=>1, 'enabled'=>1, 'visible'=>3, 'notnull'=>1, 'position'=>19),
		'fk_soc' =>array('type'=>'integer', 'label'=>'Thirdparty', 'enabled'=>1, 'visible'=>0, 'position'=>20),
		'dateo' =>array('type'=>'date', 'label'=>'DateStart', 'enabled'=>1, 'visible'=>1, 'position'=>30),
		'datee' =>array('type'=>'date', 'label'=>'DateEnd', 'enabled'=>1, 'visible'=>1, 'position'=>35),
		'description' =>array('type'=>'text', 'label'=>'Description', 'enabled'=>1, 'visible'=>3, 'position'=>55, 'searchall'=>1),
		'public' =>array('type'=>'integer', 'label'=>'Visibility', 'enabled'=>1, 'visible'=>1, 'position'=>65),
		'fk_opp_status' =>array('type'=>'integer', 'label'=>'OpportunityStatusShort', 'enabled'=>'getDolGlobalString("PROJECT_USE_OPPORTUNITIES")', 'visible'=>1, 'position'=>75),
		'opp_percent' =>array('type'=>'double(5,2)', 'label'=>'OpportunityProbabilityShort', 'enabled'=>'getDolGlobalString("PROJECT_USE_OPPORTUNITIES")', 'visible'=>1, 'position'=>80),
		'note_private' =>array('type'=>'html', 'label'=>'NotePrivate', 'enabled'=>1, 'visible'=>0, 'position'=>85, 'searchall'=>1),
		'note_public' =>array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>1, 'visible'=>0, 'position'=>90, 'searchall'=>1),
		'model_pdf' =>array('type'=>'varchar(255)', 'label'=>'ModelPdf', 'enabled'=>1, 'visible'=>0, 'position'=>95),
		'date_close' =>array('type'=>'datetime', 'label'=>'DateClosing', 'enabled'=>1, 'visible'=>0, 'position'=>105),
		'fk_user_close' =>array('type'=>'integer', 'label'=>'UserClosing', 'enabled'=>1, 'visible'=>0, 'position'=>110),
		'opp_amount' =>array('type'=>'double(24,8)', 'label'=>'OpportunityAmountShort', 'enabled'=>1, 'visible'=>'getDolGlobalString("PROJECT_USE_OPPORTUNITIES")', 'position'=>115),
		'budget_amount' =>array('type'=>'double(24,8)', 'label'=>'Budget', 'enabled'=>1, 'visible'=>-1, 'position'=>119),
		'usage_bill_time' =>array('type'=>'integer', 'label'=>'UsageBillTimeShort', 'enabled'=>1, 'visible'=>-1, 'position'=>130),
		'usage_opportunity' =>array('type'=>'integer', 'label'=>'UsageOpportunity', 'enabled'=>1, 'visible'=>-1, 'position'=>135),
		'usage_task' =>array('type'=>'integer', 'label'=>'UsageTasks', 'enabled'=>1, 'visible'=>-1, 'position'=>140),
		'usage_organize_event' =>array('type'=>'integer', 'label'=>'UsageOrganizeEvent', 'enabled'=>1, 'visible'=>-1, 'position'=>145),
		// Properties for event organization
		'date_start_event' =>array('type'=>'date', 'label'=>'DateStartEvent', 'enabled'=>"isModEnabled('eventorganization')", 'visible'=>1, 'position'=>200),
		'date_end_event' =>array('type'=>'date', 'label'=>'DateEndEvent', 'enabled'=>"isModEnabled('eventorganization')", 'visible'=>1, 'position'=>201),
		'location' =>array('type'=>'text', 'label'=>'Location', 'enabled'=>1, 'visible'=>3, 'position'=>55, 'searchall'=>202),
		'accept_conference_suggestions' =>array('type'=>'integer', 'label'=>'AllowUnknownPeopleSuggestConf', 'enabled'=>1, 'visible'=>-1, 'position'=>210),
		'accept_booth_suggestions' =>array('type'=>'integer', 'label'=>'AllowUnknownPeopleSuggestBooth', 'enabled'=>1, 'visible'=>-1, 'position'=>211),
		'price_registration' =>array('type'=>'double(24,8)', 'label'=>'PriceOfRegistration', 'enabled'=>1, 'visible'=>-1, 'position'=>212),
		'price_booth' =>array('type'=>'double(24,8)', 'label'=>'PriceOfBooth', 'enabled'=>1, 'visible'=>-1, 'position'=>215),
		'max_attendees' =>array('type'=>'integer', 'label'=>'MaxNbOfAttendees', 'enabled'=>1, 'visible'=>-1, 'position'=>215),
		// Generic
		'datec' =>array('type'=>'datetime', 'label'=>'DateCreationShort', 'enabled'=>1, 'visible'=>-2, 'position'=>400),
		'tms' =>array('type'=>'timestamp', 'label'=>'DateModificationShort', 'enabled'=>1, 'visible'=>-2, 'notnull'=>1, 'position'=>405),
		'fk_user_creat' =>array('type'=>'integer', 'label'=>'UserCreation', 'enabled'=>1, 'visible'=>0, 'notnull'=>1, 'position'=>410),
		'fk_user_modif' =>array('type'=>'integer', 'label'=>'UserModification', 'enabled'=>1, 'visible'=>0, 'position'=>415),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-1, 'position'=>420),
		'email_msgid'=>array('type'=>'varchar(255)', 'label'=>'EmailMsgID', 'enabled'=>1, 'visible'=>-1, 'position'=>450, 'help'=>'EmailMsgIDWhenSourceisEmail', 'csslist'=>'tdoverflowmax125'),
		'fk_statut' =>array('type'=>'smallint(6)', 'label'=>'Status', 'enabled'=>1, 'visible'=>1, 'notnull'=>1, 'position'=>500),
	);
	// END MODULEBUILDER PROPERTIES

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;

	/**
	 * Open/Validated status
	 */
	const STATUS_VALIDATED = 1;

	/**
	 * Closed status
	 */
	const STATUS_CLOSED = 2;

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->statuts_short = array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');
		$this->statuts_long = array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');

		global $conf;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID)) {
			$this->fields['rowid']['visible'] = 0;
		}

		if (empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
			$this->fields['fk_opp_status']['enabled'] = 0;
			$this->fields['opp_percent']['enabled'] = 0;
			$this->fields['opp_amount']['enabled'] = 0;
			$this->fields['usage_opportunity']['enabled'] = 0;
		}

		if (!empty($conf->global->PROJECT_HIDE_TASKS)) {
			$this->fields['usage_bill_time']['visible'] = 0;
			$this->fields['usage_task']['visible'] = 0;
		}

		if (empty($conf->eventorganization->enabled)) {
			$this->fields['usage_organize_event']['visible'] = 0;
			$this->fields['accept_conference_suggestions']['enabled'] = 0;
			$this->fields['accept_booth_suggestions']['enabled'] = 0;
			$this->fields['price_registration']['enabled'] = 0;
			$this->fields['price_booth']['enabled'] = 0;
			$this->fields['max_attendees']['enabled'] = 0;
		}
	}

	/**
	 *    Create a project into database
	 *
	 *    @param    User	$user       	User making creation
	 *    @param	int		$notrigger		Disable triggers
	 *    @return   int         			<0 if KO, id of created project if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;
		$ret = 0;

		$now = dol_now();

		// Clean parameters
		$this->note_private = dol_substr($this->note_private, 0, 65535);
		$this->note_public = dol_substr($this->note_public, 0, 65535);

		// Check parameters
		if (!trim($this->ref)) {
			$this->error = 'ErrorFieldsRequired';
			dol_syslog(get_class($this)."::create error -1 ref null", LOG_ERR);
			return -1;
		}
		if (!empty($conf->global->PROJECT_THIRDPARTY_REQUIRED) && !($this->socid > 0)) {
			$this->error = 'ErrorFieldsRequired';
			dol_syslog(get_class($this)."::create error -1 thirdparty not defined and option PROJECT_THIRDPARTY_REQUIRED is set", LOG_ERR);
			return -1;
		}

		// Create project
		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet (";
		$sql .= "ref";
		$sql .= ", title";
		$sql .= ", description";
		$sql .= ", fk_soc";
		$sql .= ", fk_user_creat";
		$sql .= ", fk_statut";
		$sql .= ", fk_opp_status";
		$sql .= ", opp_percent";
		$sql .= ", public";
		$sql .= ", datec";
		$sql .= ", dateo";
		$sql .= ", datee";
		$sql .= ", opp_amount";
		$sql .= ", budget_amount";
		$sql .= ", usage_opportunity";
		$sql .= ", usage_task";
		$sql .= ", usage_bill_time";
		$sql .= ", usage_organize_event";
		$sql .= ", accept_conference_suggestions";
		$sql .= ", accept_booth_suggestions";
		$sql .= ", price_registration";
		$sql .= ", price_booth";
		$sql .= ", max_attendees";
		$sql .= ", date_start_event";
		$sql .= ", date_end_event";
		$sql .= ", location";
		$sql .= ", email_msgid";
		$sql .= ", note_private";
		$sql .= ", note_public";
		$sql .= ", entity";
		$sql .= ", ip";
		$sql .= ") VALUES (";
		$sql .= "'".$this->db->escape($this->ref)."'";
		$sql .= ", '".$this->db->escape($this->title)."'";
		$sql .= ", '".$this->db->escape($this->description)."'";
		$sql .= ", ".($this->socid > 0 ? $this->socid : "null");
		$sql .= ", ".((int) $user->id);
		$sql .= ", ".(is_numeric($this->statut) ? ((int) $this->statut) : '0');
		$sql .= ", ".((is_numeric($this->opp_status) && $this->opp_status > 0) ? ((int) $this->opp_status) : 'NULL');
		$sql .= ", ".(is_numeric($this->opp_percent) ? ((int) $this->opp_percent) : 'NULL');
		$sql .= ", ".($this->public ? 1 : 0);
		$sql .= ", '".$this->db->idate($now)."'";
		$sql .= ", ".($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : 'null');
		$sql .= ", ".($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : 'null');
		$sql .= ", ".(strcmp($this->opp_amount, '') ? price2num($this->opp_amount) : 'null');
		$sql .= ", ".(strcmp($this->budget_amount, '') ? price2num($this->budget_amount) : 'null');
		$sql .= ", ".($this->usage_opportunity ? 1 : 0);
		$sql .= ", ".($this->usage_task ? 1 : 0);
		$sql .= ", ".($this->usage_bill_time ? 1 : 0);
		$sql .= ", ".($this->usage_organize_event ? 1 : 0);
		$sql .= ", ".($this->accept_conference_suggestions ? 1 : 0);
		$sql .= ", ".($this->accept_booth_suggestions ? 1 : 0);
		$sql .= ", ".(strcmp($this->price_registration, '') ? price2num($this->price_registration) : 'null');
		$sql .= ", ".(strcmp($this->price_booth, '') ? price2num($this->price_booth) : 'null');
		$sql .= ", ".(strcmp($this->max_attendees, '') ? ((int) $this->max_attendees) : 'null');
		$sql .= ", ".($this->date_start_event != '' ? "'".$this->db->idate($this->date_start_event)."'" : 'null');
		$sql .= ", ".($this->date_end_event != '' ? "'".$this->db->idate($this->date_end_event)."'" : 'null');
		$sql .= ", ".($this->location ? "'".$this->db->escape($this->location)."'" : 'null');
		$sql .= ", ".($this->email_msgid ? "'".$this->db->escape($this->email_msgid)."'" : 'null');
		$sql .= ", ".($this->note_private ? "'".$this->db->escape($this->note_private)."'" : 'null');
		$sql .= ", ".($this->note_public ? "'".$this->db->escape($this->note_public)."'" : 'null');
		$sql .= ", ".((int) $conf->entity);
		$sql .= ", ".(!isset($this->ip) ? 'NULL' : "'".$this->db->escape($this->ip)."'");
		$sql .= ")";

		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet");
			$ret = $this->id;

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('PROJECT_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->errno = $this->db->lasterrno();
			$error++;
		}

		// Update extrafield
		if (!$error) {
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && (getDolGlobalString('MAIN_DISABLEDRAFTSTATUS') || getDolGlobalString('MAIN_DISABLEDRAFTSTATUS_PROJECT'))) {
			$res = $this->setValid($user);
			if ($res < 0) {
				$error++;
			}
		}

		if (!$error) {
			$this->db->commit();
			return $ret;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Update a project
	 *
	 * @param  User		$user       User object of making update
	 * @param  int		$notrigger  1=Disable all triggers
	 * @return int                  <=0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		global $langs, $conf;

		$error = 0;

		// Clean parameters
		$this->title = trim($this->title);
		$this->description = trim($this->description);
		if ($this->opp_amount < 0) {
			$this->opp_amount = '';
		}
		if ($this->opp_percent < 0) {
			$this->opp_percent = '';
		}
		if ($this->date_end && $this->date_end < $this->date_start) {
			$this->error = $langs->trans("ErrorDateEndLowerThanDateStart");
			$this->errors[] = $this->error;
			$this->db->rollback();
			dol_syslog(get_class($this)."::update error -3 ".$this->error, LOG_ERR);
			return -3;
		}

		$this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

		if (dol_strlen(trim($this->ref)) > 0) {
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."projet SET";
			$sql .= " ref='".$this->db->escape($this->ref)."'";
			$sql .= ", title = '".$this->db->escape($this->title)."'";
			$sql .= ", description = '".$this->db->escape($this->description)."'";
			$sql .= ", fk_soc = ".($this->socid > 0 ? $this->socid : "null");
			$sql .= ", fk_statut = ".((int) $this->statut);
			$sql .= ", fk_opp_status = ".((is_numeric($this->opp_status) && $this->opp_status > 0) ? $this->opp_status : 'null');
			$sql .= ", opp_percent = ".((is_numeric($this->opp_percent) && $this->opp_percent != '') ? $this->opp_percent : 'null');
			$sql .= ", public = ".($this->public ? 1 : 0);
			$sql .= ", datec = ".($this->date_c != '' ? "'".$this->db->idate($this->date_c)."'" : 'null');
			$sql .= ", dateo = ".($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : 'null');
			$sql .= ", datee = ".($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : 'null');
			$sql .= ", date_close = ".($this->date_close != '' ? "'".$this->db->idate($this->date_close)."'" : 'null');
			$sql .= ", fk_user_close = ".($this->fk_user_close > 0 ? $this->fk_user_close : "null");
			$sql .= ", opp_amount = ".(strcmp($this->opp_amount, '') ? price2num($this->opp_amount) : "null");
			$sql .= ", budget_amount = ".(strcmp($this->budget_amount, '') ? price2num($this->budget_amount) : "null");
			$sql .= ", fk_user_modif = ".$user->id;
			$sql .= ", usage_opportunity = ".($this->usage_opportunity ? 1 : 0);
			$sql .= ", usage_task = ".($this->usage_task ? 1 : 0);
			$sql .= ", usage_bill_time = ".($this->usage_bill_time ? 1 : 0);
			$sql .= ", usage_organize_event = ".($this->usage_organize_event ? 1 : 0);
			$sql .= ", accept_conference_suggestions = ".($this->accept_conference_suggestions ? 1 : 0);
			$sql .= ", accept_booth_suggestions = ".($this->accept_booth_suggestions ? 1 : 0);
			$sql .= ", price_registration = ".(strcmp($this->price_registration, '') ? price2num($this->price_registration) : "null");
			$sql .= ", price_booth = ".(strcmp($this->price_booth, '') ? price2num($this->price_booth) : "null");
			$sql .= ", max_attendees = ".(strcmp($this->max_attendees, '') ? price2num($this->max_attendees) : "null");
			$sql .= ", date_start_event = ".($this->date_start_event != '' ? "'".$this->db->idate($this->date_start_event)."'" : 'null');
			$sql .= ", date_end_event = ".($this->date_end_event != '' ? "'".$this->db->idate($this->date_end_event)."'" : 'null');
			$sql .= ", location = '".$this->db->escape($this->location)."'";
			$sql .= ", entity = ".((int) $this->entity);
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::update", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				// Update extrafield
				if (!$error) {
					$result = $this->insertExtraFields();
					if ($result < 0) {
						$error++;
					}
				}

				if (!$error && !$notrigger) {
					// Call trigger
					$result = $this->call_trigger('PROJECT_MODIFY', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}

				if (!$error && (is_object($this->oldcopy) && $this->oldcopy->ref !== $this->ref)) {
					// We remove directory
					if ($conf->project->dir_output) {
						$olddir = $conf->project->dir_output."/".dol_sanitizeFileName($this->oldcopy->ref);
						$newdir = $conf->project->dir_output."/".dol_sanitizeFileName($this->ref);
						if (file_exists($olddir)) {
							include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
							$res = @rename($olddir, $newdir);
							if (!$res) {
								$langs->load("errors");
								$this->error = $langs->trans('ErrorFailToRenameDir', $olddir, $newdir);
								$error++;
							}
						}
					}
				}
				if (!$error) {
					$this->db->commit();
					$result = 1;
				} else {
					$this->db->rollback();
					$result = -1;
				}
			} else {
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				$this->db->rollback();
				if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
					$result = -4;
				} else {
					$result = -2;
				}
				dol_syslog(get_class($this)."::update error ".$result." ".$this->error, LOG_ERR);
			}
		} else {
			dol_syslog(get_class($this)."::update ref null");
			$result = -1;
		}

		return $result;
	}

	/**
	 * 	Get object from database
	 *
	 * 	@param      int		$id       		Id of object to load
	 * 	@param		string	$ref			Ref of project
	 * 	@param		string	$ref_ext		Ref ext of project
	 *  @param		string	$email_msgid	Email msgid
	 * 	@return     int      		   		>0 if OK, 0 if not found, <0 if KO
	 */
	public function fetch($id, $ref = '', $ref_ext = '', $email_msgid = '')
	{
		global $conf;

		if (empty($id) && empty($ref) && empty($ref_ext) && empty($email_msgid)) {
			dol_syslog(get_class($this)."::fetch Bad parameters", LOG_WARNING);
			return -1;
		}

		$sql = "SELECT rowid, entity, ref, title, description, public, datec, opp_amount, budget_amount,";
		$sql .= " tms, dateo as date_start, datee as date_end, date_close, fk_soc, fk_user_creat, fk_user_modif, fk_user_close, fk_statut as status, fk_opp_status, opp_percent,";
		$sql .= " note_private, note_public, model_pdf, usage_opportunity, usage_task, usage_bill_time, usage_organize_event, email_msgid,";
		$sql .= " accept_conference_suggestions, accept_booth_suggestions, price_registration, price_booth, max_attendees, date_start_event, date_end_event, location";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet";
		if (!empty($id)) {
			$sql .= " WHERE rowid = ".((int) $id);
		} else {
			$sql .= " WHERE entity IN (".getEntity('project').")";
			if (!empty($ref)) {
				$sql .= " AND ref = '".$this->db->escape($ref)."'";
			} elseif (!empty($ref_ext)) {
				$sql .= " AND ref_ext = '".$this->db->escape($ref_ext)."'";
			} else {
				$sql .= " AND email_msgid = '".$this->db->escape($email_msgid)."'";
			}
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num_rows = $this->db->num_rows($resql);

			if ($num_rows) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
				$this->ref = $obj->ref;
				$this->title = $obj->title;
				$this->description = $obj->description;
				$this->date_c = $this->db->jdate($obj->datec);
				$this->datec = $this->db->jdate($obj->datec); // TODO deprecated
				$this->date_m = $this->db->jdate($obj->tms);
				$this->datem = $this->db->jdate($obj->tms); // TODO deprecated
				$this->date_start = $this->db->jdate($obj->date_start);
				$this->date_end = $this->db->jdate($obj->date_end);
				$this->date_close = $this->db->jdate($obj->date_close);
				$this->note_private = $obj->note_private;
				$this->note_public = $obj->note_public;
				$this->socid = $obj->fk_soc;
				$this->user_author_id = $obj->fk_user_creat;
				$this->user_modification_id = $obj->fk_user_modif;
				$this->user_close_id = $obj->fk_user_close;
				$this->public = $obj->public;
				$this->statut = $obj->status; // deprecated
				$this->status = $obj->status;
				$this->opp_status = $obj->fk_opp_status;
				$this->opp_amount	= $obj->opp_amount;
				$this->opp_percent = $obj->opp_percent;
				$this->budget_amount = $obj->budget_amount;
				$this->model_pdf = $obj->model_pdf;
				$this->modelpdf = $obj->model_pdf; // deprecated
				$this->usage_opportunity = (int) $obj->usage_opportunity;
				$this->usage_task = (int) $obj->usage_task;
				$this->usage_bill_time = (int) $obj->usage_bill_time;
				$this->usage_organize_event = (int) $obj->usage_organize_event;
				$this->accept_conference_suggestions = (int) $obj->accept_conference_suggestions;
				$this->accept_booth_suggestions = (int) $obj->accept_booth_suggestions;
				$this->price_registration = $obj->price_registration;
				$this->price_booth = $obj->price_booth;
				$this->max_attendees = $obj->max_attendees;
				$this->date_start_event = $this->db->jdate($obj->date_start_event);
				$this->date_end_event = $this->db->jdate($obj->date_end_event);
				$this->location = $obj->location;
				$this->email_msgid = $obj->email_msgid;

				$this->db->free($resql);

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();

				return 1;
			}

			$this->db->free($resql);

			return 0;
		} else {
			$this->error = $this->db->lasterror();
			$this->errors[] = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Return list of elements for type, linked to a project
	 *
	 * 	@param		string		$type			'propal','order','invoice','order_supplier','invoice_supplier',...
	 * 	@param		string		$tablename		name of table associated of the type
	 * 	@param		string		$datefieldname	name of date field for filter
	 *  @param		int			$date_start		Start date
	 *  @param		int			$date_end		End date
	 *	@param		string		$projectkey		Equivalent key  to fk_projet for actual type
	 * 	@return		mixed						Array list of object ids linked to project, < 0 or string if error
	 */
	public function get_element_list($type, $tablename, $datefieldname = '', $date_start = '', $date_end = '', $projectkey = 'fk_projet')
	{
		// phpcs:enable

		global $hookmanager;

		$elements = array();

		if ($this->id <= 0) {
			return $elements;
		}

		$ids = $this->id;

		if ($type == 'agenda') {
			$sql = "SELECT id as rowid FROM ".MAIN_DB_PREFIX."actioncomm WHERE fk_project IN (".$this->db->sanitize($ids).") AND entity IN (".getEntity('agenda').")";
		} elseif ($type == 'expensereport') {
			$sql = "SELECT ed.rowid FROM ".MAIN_DB_PREFIX."expensereport as e, ".MAIN_DB_PREFIX."expensereport_det as ed WHERE e.rowid = ed.fk_expensereport AND e.entity IN (".getEntity('expensereport').") AND ed.fk_projet IN (".$this->db->sanitize($ids).")";
		} elseif ($type == 'project_task') {
			$sql = "SELECT DISTINCT pt.rowid FROM ".MAIN_DB_PREFIX."projet_task as pt WHERE pt.fk_projet IN (".$this->db->sanitize($ids).")";
		} elseif ($type == 'element_time') {	// Case we want to duplicate line foreach user
			$sql = "SELECT DISTINCT pt.rowid, ptt.fk_user FROM ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."element_time as ptt WHERE pt.rowid = ptt.fk_element AND ptt.elementtype = 'task' AND pt.fk_projet IN (".$this->db->sanitize($ids).")";
		} elseif ($type == 'stock_mouvement') {
			$sql = "SELECT ms.rowid, ms.fk_user_author as fk_user FROM ".MAIN_DB_PREFIX."stock_mouvement as ms, ".MAIN_DB_PREFIX."entrepot as e WHERE e.rowid = ms.fk_entrepot AND e.entity IN (".getEntity('stock').") AND ms.origintype = 'project' AND ms.fk_origin IN (".$this->db->sanitize($ids).") AND ms.type_mouvement = 1";
		} elseif ($type == 'loan') {
			$sql = "SELECT l.rowid, l.fk_user_author as fk_user FROM ".MAIN_DB_PREFIX."loan as l WHERE l.entity IN (".getEntity('loan').") AND l.fk_projet IN (".$this->db->sanitize($ids).")";
		} else {
			$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX.$tablename." WHERE ".$projectkey." IN (".$this->db->sanitize($ids).") AND entity IN (".getEntity($type).")";
		}

		if ($date_start > 0 && $type == 'loan') {
			$sql .= " AND (dateend > '".$this->db->idate($date_start)."' OR dateend IS NULL)";
		} elseif ($date_start > 0 && ($type != 'project_task')) {	// For table project_taks, we want the filter on date apply on project_time_spent table
			if (empty($datefieldname) && !empty($this->table_element_date)) {
				$datefieldname = $this->table_element_date;
			}
			if (empty($datefieldname)) {
				return 'Error this object has no date field defined';
			}
			$sql .= " AND (".$datefieldname." >= '".$this->db->idate($date_start)."' OR ".$datefieldname." IS NULL)";
		}

		if ($date_end > 0 && $type == 'loan') {
			$sql .= " AND (datestart < '".$this->db->idate($date_end)."' OR datestart IS NULL)";
		} elseif ($date_end > 0 && ($type != 'project_task')) {	// For table project_taks, we want the filter on date apply on project_time_spent table
			if (empty($datefieldname) && !empty($this->table_element_date)) {
				$datefieldname = $this->table_element_date;
			}
			if (empty($datefieldname)) {
				return 'Error this object has no date field defined';
			}
			$sql .= " AND (".$datefieldname." <= '".$this->db->idate($date_end)."' OR ".$datefieldname." IS NULL)";
		}

		$parameters = array(
			'sql'=>$sql,
			'type' => $type,
			'tablename' => $tablename,
			'datefieldname'  => $datefieldname,
			'dates' => $date_start,
			'datee' => $date_end,
			'fk_projet' => $projectkey,
			'ids' => $ids,
		);
		$reshook = $hookmanager->executeHooks('getElementList', $parameters);
		if ($reshook > 0) {
			$sql = $hookmanager->resPrint;
		} else {
			$sql .= $hookmanager->resPrint;
		}

		if (!$sql) {
			return -1;
		}

		//print $sql;
		dol_syslog(get_class($this)."::get_element_list", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$nump = $this->db->num_rows($result);
			if ($nump) {
				$i = 0;
				while ($i < $nump) {
					$obj = $this->db->fetch_object($result);

					$elements[$i] = $obj->rowid.(empty($obj->fk_user) ? '' : '_'.$obj->fk_user);

					$i++;
				}
				$this->db->free($result);
			}

			/* Return array even if empty*/
			return $elements;
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 *    Delete a project from database
	 *
	 *    @param       User		$user            User
	 *    @param       int		$notrigger       Disable triggers
	 *    @return      int       			      <0 if KO, 0 if not possible, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $langs, $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		if (!$error) {
			// Delete linked contacts
			$res = $this->delete_linked_contact();
			if ($res < 0) {
				$this->error = 'ErrorFailToDeleteLinkedContact';
				//$error++;
				$this->db->rollback();
				return 0;
			}
		}

		// Set fk_projet into elements to null
		$listoftables = array(
			'propal'=>'fk_projet', 'commande'=>'fk_projet', 'facture'=>'fk_projet',
			'supplier_proposal'=>'fk_projet', 'commande_fournisseur'=>'fk_projet', 'facture_fourn'=>'fk_projet',
			'expensereport_det'=>'fk_projet', 'contrat'=>'fk_projet',
			'fichinter'=>'fk_projet',
			'don'=>array('field'=>'fk_projet', 'module'=>'don'),
			'actioncomm'=>'fk_project',
			'mrp_mo'=>'fk_project',
			'entrepot'=>'fk_project'
		);
		foreach ($listoftables as $key => $value) {
			if (is_array($value)) {
				if (!isModEnabled($value['module'])) {
					continue;
				}
				$fieldname = $value['field'];
			} else {
				$fieldname = $value;
			}
			$sql = "UPDATE ".MAIN_DB_PREFIX.$key." SET ".$fieldname." = NULL where ".$fieldname." = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->lasterror();
				$error++;
				break;
			}
		}

		// Remove linked categories.
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_project";
			$sql .= " WHERE fk_project = ".((int) $this->id);

			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->errors[] = $this->db->lasterror();
			}
		}

		// Fetch tasks
		$this->getLinesArray($user, 0);

		// Delete tasks
		$ret = $this->deleteTasks($user);
		if ($ret < 0) {
			$error++;
		}


		// Delete all child tables
		if (!$error) {
			$elements = array('categorie_project'); // elements to delete. TODO Make goodway to delete
			foreach ($elements as $table) {
				if (!$error) {
					$sql = "DELETE FROM ".MAIN_DB_PREFIX.$table;
					$sql .= " WHERE fk_project = ".((int) $this->id);

					$result = $this->db->query($sql);
					if (!$result) {
						$error++;
						$this->errors[] = $this->db->lasterror();
					}
				}
			}
		}

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_extrafields";
			$sql .= " WHERE fk_object = ".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->lasterror();
				$error++;
			}
		}

		// Delete project
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet";
			$sql .= " WHERE rowid=".((int) $this->id);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $langs->trans("CantRemoveProject", $langs->transnoentitiesnoconv("ProjectOverview"));
				$error++;
			}
		}



		if (empty($error)) {
			// We remove directory
			$projectref = dol_sanitizeFileName($this->ref);
			if ($conf->project->dir_output) {
				$dir = $conf->project->dir_output."/".$projectref;
				if (file_exists($dir)) {
					$res = @dol_delete_dir_recursive($dir);
					if (!$res) {
						$this->errors[] = 'ErrorFailToDeleteDir';
						$error++;
					}
				}
			}

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('PROJECT_DELETE', $user);

				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (empty($error)) {
			$this->db->commit();
			return 1;
		} else {
			foreach ($this->errors as $errmsg) {
				dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			dol_syslog(get_class($this)."::delete ".$this->error, LOG_ERR);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Return the count of a type of linked elements of this project
	 *
	 * @param string	$type			The type of the linked elements (e.g. 'propal', 'order', 'invoice', 'order_supplier', 'invoice_supplier')
	 * @param string	$tablename		The name of table associated of the type
	 * @param string	$projectkey 	(optional) Equivalent key to fk_projet for actual type
	 * @return integer					The count of the linked elements (the count is zero on request error too)
	 */
	public function getElementCount($type, $tablename, $projectkey = 'fk_projet')
	{
		if ($this->id <= 0) {
			return 0;
		}

		if ($type == 'agenda') {
			$sql = "SELECT COUNT(id) as nb FROM ".MAIN_DB_PREFIX."actioncomm WHERE fk_project = ".((int) $this->id)." AND entity IN (".getEntity('agenda').")";
		} elseif ($type == 'expensereport') {
			$sql = "SELECT COUNT(ed.rowid) as nb FROM ".MAIN_DB_PREFIX."expensereport as e, ".MAIN_DB_PREFIX."expensereport_det as ed WHERE e.rowid = ed.fk_expensereport AND e.entity IN (".getEntity('expensereport').") AND ed.fk_projet = ".((int) $this->id);
		} elseif ($type == 'project_task') {
			$sql = "SELECT DISTINCT COUNT(pt.rowid) as nb FROM ".MAIN_DB_PREFIX."projet_task as pt WHERE pt.fk_projet = ".((int) $this->id);
		} elseif ($type == 'element_time') {	// Case we want to duplicate line foreach user
			$sql = "SELECT DISTINCT COUNT(pt.rowid) as nb FROM ".MAIN_DB_PREFIX."projet_task as pt, ".MAIN_DB_PREFIX."element_time as ptt WHERE pt.rowid = ptt.fk_element AND ptt.elementtype = 'task' AND pt.fk_projet = ".((int) $this->id);
		} elseif ($type == 'stock_mouvement') {
			$sql = "SELECT COUNT(ms.rowid) as nb FROM ".MAIN_DB_PREFIX."stock_mouvement as ms, ".MAIN_DB_PREFIX."entrepot as e WHERE e.rowid = ms.fk_entrepot AND e.entity IN (".getEntity('stock').") AND ms.origintype = 'project' AND ms.fk_origin = ".((int) $this->id)." AND ms.type_mouvement = 1";
		} elseif ($type == 'loan') {
			$sql = "SELECT COUNT(l.rowid) as nb FROM ".MAIN_DB_PREFIX."loan as l WHERE l.entity IN (".getEntity('loan').") AND l.fk_projet = ".((int) $this->id);
		} else {
			$sql = "SELECT COUNT(rowid) as nb FROM ".MAIN_DB_PREFIX.$tablename." WHERE ".$projectkey." = ".((int) $this->id)." AND entity IN (".getEntity($type).")";
		}

		$result = $this->db->query($sql);

		if (!$result) {
			return 0;
		}

		$obj = $this->db->fetch_object($result);

		$this->db->free($result);

		return $obj->nb;
	}

	/**
	 * 		Delete tasks with no children first, then task with children recursively
	 *
	 *  	@param     	User		$user		User
	 *		@return		int				<0 if KO, 1 if OK
	 */
	public function deleteTasks($user)
	{
		$countTasks = count($this->lines);
		$deleted = false;
		if ($countTasks) {
			foreach ($this->lines as $task) {
				if ($task->hasChildren() <= 0) {		// If there is no children (or error to detect them)
					$deleted = true;
					$ret = $task->delete($user);
					if ($ret <= 0) {
						$this->errors[] = $this->db->lasterror();
						return -1;
					}
				}
			}
		}
		$this->getLinesArray($user);
		if ($deleted && count($this->lines) < $countTasks) {
			if (count($this->lines)) {
				$this->deleteTasks($this->lines);
			}
		}

		return 1;
	}

	/**
	 * 		Validate a project
	 *
	 * 		@param		User	$user		   User that validate
	 *      @param      int     $notrigger     1=Disable triggers
	 * 		@return		int					   <0 if KO, 0=Nothing done, >0 if KO
	 */
	public function setValid($user, $notrigger = 0)
	{
		global $langs, $conf;

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this)."::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		// Check parameters
		if (preg_match('/^'.preg_quote($langs->trans("CopyOf").' ').'/', $this->title)) {
			$this->error = $langs->trans("ErrorFieldFormat", $langs->transnoentities("Label")).'. '.$langs->trans('RemoveString', $langs->transnoentitiesnoconv("CopyOf"));
			return -1;
		}

		$this->db->begin();

		$sql = "UPDATE ".MAIN_DB_PREFIX."projet";
		$sql .= " SET fk_statut = ".self::STATUS_VALIDATED;
		$sql .= " WHERE rowid = ".((int) $this->id);
		//$sql .= " AND entity = ".((int) $conf->entity);	// Disabled, when we use the ID for the where, we must not add any other search condition

		dol_syslog(get_class($this)."::setValid", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			// Call trigger
			if (empty($notrigger)) {
				$result = $this->call_trigger('PROJECT_VALIDATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}

			if (!$error) {
				$this->statut = 1;
				$this->db->commit();
				return 1;
			} else {
				$this->db->rollback();
				$this->error = join(',', $this->errors);
				dol_syslog(get_class($this)."::setValid ".$this->error, LOG_ERR);
				return -1;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * 		Close a project
	 *
	 * 		@param		User	$user		User that close project
	 * 		@return		int					<0 if KO, 0 if already closed, >0 if OK
	 */
	public function setClose($user)
	{
		global $langs, $conf;

		$now = dol_now();

		$error = 0;

		if ($this->statut != self::STATUS_CLOSED) {
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."projet";
			$sql .= " SET fk_statut = ".self::STATUS_CLOSED.", fk_user_close = ".((int) $user->id).", date_close = '".$this->db->idate($now)."'";
			$sql .= " WHERE rowid = ".((int) $this->id);
			$sql .= " AND fk_statut = ".self::STATUS_VALIDATED;

			if (!empty($conf->global->PROJECT_USE_OPPORTUNITIES)) {
				// TODO What to do if fk_opp_status is not code 'WON' or 'LOST'
			}

			dol_syslog(get_class($this)."::setClose", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				// Call trigger
				$result = $this->call_trigger('PROJECT_CLOSE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers

				if (!$error) {
					$this->statut = 2;
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					$this->error = join(',', $this->errors);
					dol_syslog(get_class($this)."::setClose ".$this->error, LOG_ERR);
					return -1;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				return -1;
			}
		}

		return 0;
	}

	/**
	 *  Return status label of object
	 *
	 *  @param  int			$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * 	@return string      			Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut(isset($this->statut) ? $this->statut : $this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Renvoi status label for a status
	 *
	 *  @param	int		$status     id status
	 *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * 	@return string				Label
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		$statustrans = array(
			0 => 'status0',
			1 => 'status4',
			2 => 'status6',
		);

		$statusClass = 'status0';
		if (!empty($statustrans[$status])) {
			$statusClass = $statustrans[$status];
		}

		return dolGetStatus($langs->transnoentitiesnoconv($this->statuts_long[$status]), $langs->transnoentitiesnoconv($this->statuts_short[$status]), '', $statusClass, $mode);
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
		global $conf, $langs;

		$langs->load('projects');
		$option = $params['option'] ?? '';
		$moreinpopup = $params['morinpopup'] ?? '';

		$datas = [];
		if ($option != 'nolink') {
			$datas['picto'] = img_picto('', $this->picto, 'class="pictofixedwidth"').' <u class="paddingrightonly">'.$langs->trans("Project").'</u>';
		}
		if (isset($this->status)) {
			$datas['picto'] .= ' '.$this->getLibStatut(5);
		}
		$datas['ref'] = (isset($datas['picto']) ? '<br>' : '').'<b>'.$langs->trans('Ref').': </b>'.$this->ref; // The space must be after the : to not being explode when showing the title in img_picto
		$datas['label'] = '<br><b>'.$langs->trans('Label').': </b>'.$this->title; // The space must be after the : to not being explode when showing the title in img_picto
		if (isset($this->public)) {
			$datas['visibility'] = '<br><b>'.$langs->trans("Visibility").":</b> ";
			$datas['visibility'] .= ($this->public ? img_picto($langs->trans('SharedProject'), 'world', 'class="pictofixedwidth"').$langs->trans("SharedProject") : img_picto($langs->trans('PrivateProject'), 'private', 'class="pictofixedwidth"').$langs->trans("PrivateProject"));
		}
		if (!empty($this->thirdparty_name)) {
			$datas['thirdparty'] = '<br><b>'.$langs->trans('ThirdParty').': </b>'.$this->thirdparty_name; // The space must be after the : to not being explode when showing the title in img_picto
		}
		if (!empty($this->date_start)) {
			$datas['datestart'] = '<br><b>'.$langs->trans('DateStart').': </b>'.dol_print_date($this->date_start, 'day'); // The space must be after the : to not being explode when showing the title in img_picto
		}
		if (!empty($this->date_end)) {
			$datas['dateend'] = '<br><b>'.$langs->trans('DateEnd').': </b>'.dol_print_date($this->date_end, 'day'); // The space must be after the : to not being explode when showing the title in img_picto
		}
		if ($moreinpopup) {
			$datas['moreinpopup'] = '<br>'.$moreinpopup;
		}

		return $datas;
	}

	/**
	 * 	Return clickable name (with picto eventually)
	 *
	 * 	@param	int		$withpicto		          0=No picto, 1=Include picto into link, 2=Only picto
	 * 	@param	string	$option			          Variant where the link point to ('', 'nolink')
	 * 	@param	int		$addlabel		          0=Default, 1=Add label into string, >1=Add first chars into string
	 *  @param	string	$moreinpopup	          Text to add into popup
	 *  @param	string	$sep			          Separator between ref and label if option addlabel is set
	 *  @param	int   	$notooltip		          1=Disable tooltip
	 *  @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *  @param	string	$morecss				  More css on a link
	 * 	@return	string					          String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $addlabel = 0, $moreinpopup = '', $sep = ' - ', $notooltip = 0, $save_lastsearch_value = -1, $morecss = '')
	{
		global $conf, $langs, $user, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';
		if (!empty($conf->global->PROJECT_OPEN_ALWAYS_ON_TAB)) {
			$option = $conf->global->PROJECT_OPEN_ALWAYS_ON_TAB;
		}
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'moreinpopup' => $moreinpopup,
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

		$url = '';
		if ($option != 'nolink') {
			if (preg_match('/\.php$/', $option)) {
				$url = dol_buildpath($option, 1).'?id='.$this->id;
			} elseif ($option == 'task') {
				$url = DOL_URL_ROOT.'/projet/tasks.php?id='.$this->id;
			} elseif ($option == 'preview') {
				$url = DOL_URL_ROOT.'/projet/element.php?id='.$this->id;
			} elseif ($option == 'eventorganization') {
				$url = DOL_URL_ROOT.'/eventorganization/conferenceorbooth_list.php?projectid='.$this->id;
			} else {
				$url = DOL_URL_ROOT.'/projet/card.php?id='.$this->id;
			}
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) {
				$add_save_lastsearch_values = 1;
			}
			if ($add_save_lastsearch_values) {
				$url .= '&save_lastsearch_values=1';
			}
		}

		$linkclose = '';
		if (empty($notooltip) && $user->hasRight('projet', 'lire')) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowProject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' :  ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$picto = 'projectpub';
		if (!$this->public) {
			$picto = 'project';
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), $picto, 'class="pictofixedwidth em088"', 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		if ($withpicto != 2) {
			$result .= (($addlabel && $this->title) ? '<span class="opacitymedium">'.$sep.dol_trunc($this->title, ($addlabel > 1 ? $addlabel : 0)).'</span>' : '');
		}

		global $action;
		$hookmanager->initHooks(array('projectdao'));
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
	 *  Initialise an instance with random values.
	 *  Used to build previews or test instances.
	 * 	id must be 0 if object instance is a specimen.
	 *
	 *  @return	void
	 */
	public function initAsSpecimen()
	{
		global $user, $langs, $conf;

		$now = dol_now();

		// Initialise parameters
		$this->id = 0;
		$this->ref = 'SPECIMEN';
		$this->entity = $conf->entity;
		$this->specimen = 1;
		$this->socid = 1;
		$this->date_c = $now;
		$this->date_m = $now;
		$this->date_start = $now;
		$this->date_end = $now + (3600 * 24 * 365);
		$this->note_public = 'SPECIMEN';
		$this->fk_ele = 20000;
		$this->opp_amount = 20000;
		$this->budget_amount = 10000;

		$this->usage_opportunity = 1;
		$this->usage_task = 1;
		$this->usage_bill_time = 1;
		$this->usage_organize_event = 1;

		/*
		 $nbp = mt_rand(1, 9);
		 $xnbp = 0;
		 while ($xnbp < $nbp)
		 {
		 $line = new Task($this->db);
		 $line->fk_project = 0;
		 $line->label = $langs->trans("Label") . " " . $xnbp;
		 $line->description = $langs->trans("Description") . " " . $xnbp;

		 $this->lines[]=$line;
		 $xnbp++;
		 }
		 */
	}

	/**
	 * 	Check if user has permission on current project
	 *
	 * 	@param	User	$user		Object user to evaluate
	 * 	@param  string	$mode		Type of permission we want to know: 'read', 'write'
	 * 	@return	int					>0 if user has permission, <0 if user has no permission
	 */
	public function restrictedProjectArea(User $user, $mode = 'read')
	{
		// To verify role of users
		$userAccess = 0;
		if (($mode == 'read' && !empty($user->rights->projet->all->lire)) || ($mode == 'write' && !empty($user->rights->projet->all->creer)) || ($mode == 'delete' && !empty($user->rights->projet->all->supprimer))) {
			$userAccess = 1;
		} elseif ($this->public && (($mode == 'read' && !empty($user->rights->projet->lire)) || ($mode == 'write' && !empty($user->rights->projet->creer)) || ($mode == 'delete' && !empty($user->rights->projet->supprimer)))) {
			$userAccess = 1;
		} else {	// No access due to permission to read all projects, so we check if we are a contact of project
			foreach (array('internal', 'external') as $source) {
				$userRole = $this->liste_contact(4, $source);
				$num = count($userRole);

				$nblinks = 0;
				while ($nblinks < $num) {
					if ($source == 'internal' && $user->id == $userRole[$nblinks]['id']) {	// $userRole[$nblinks]['id'] is id of user (llx_user) for internal contacts
						if ($mode == 'read' && $user->rights->projet->lire) {
							$userAccess++;
						}
						if ($mode == 'write' && $user->rights->projet->creer) {
							$userAccess++;
						}
						if ($mode == 'delete' && $user->rights->projet->supprimer) {
							$userAccess++;
						}
					}
					if ($source == 'external' && $user->socid > 0 && $user->socid == $userRole[$nblinks]['socid']) {	// $userRole[$nblinks]['id'] is id of contact (llx_socpeople) or external contacts
						if ($mode == 'read' && $user->rights->projet->lire) {
							$userAccess++;
						}
						if ($mode == 'write' && $user->rights->projet->creer) {
							$userAccess++;
						}
						if ($mode == 'delete' && $user->rights->projet->supprimer) {
							$userAccess++;
						}
					}
					$nblinks++;
				}
			}
			//if (empty($nblinks))	// If nobody has permission, we grant creator
			//{
			//	if ((!empty($this->user_author_id) && $this->user_author_id == $user->id))
			//	{
			//		$userAccess = 1;
			//	}
			//}
		}

		return ($userAccess ? $userAccess : -1);
	}

	/**
	 * Return array of projects a user has permission on, is affected to, or all projects
	 *
	 * @param 	User	$user			User object
	 * @param 	int		$mode			0=All project I have permission on (assigned to me or public), 1=Projects assigned to me only, 2=Will return list of all projects with no test on contacts
	 * @param 	int		$list			0=Return array, 1=Return string list
	 * @param	int		$socid			0=No filter on third party, id of third party
	 * @param	string	$filter			additionnal filter on project (statut, ref, ...)
	 * @return 	array|string			Array of projects id, or string with projects id separated with "," if list is 1
	 */
	public function getProjectsAuthorizedForUser($user, $mode = 0, $list = 0, $socid = 0, $filter = '')
	{
		$projects = array();
		$temp = array();

		$sql = "SELECT ".(($mode == 0 || $mode == 1) ? "DISTINCT " : "")."p.rowid, p.ref";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		if ($mode == 0) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."element_contact as ec ON ec.element_id = p.rowid";
		} elseif ($mode == 1) {
			$sql .= ", ".MAIN_DB_PREFIX."element_contact as ec";
		} elseif ($mode == 2) {
			// No filter. Use this if user has permission to see all project
		}
		$sql .= " WHERE p.entity IN (".getEntity('project').")";
		// Internal users must see project he is contact to even if project linked to a third party he can't see.
		//if ($socid || ! $user->rights->societe->client->voir)	$sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
		if ($socid > 0) {
			$sql .= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
		}

		// Get id of types of contacts for projects (This list never contains a lot of elements)
		$listofprojectcontacttype = array();
		$sql2 = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
		$sql2 .= " WHERE ctc.element = '".$this->db->escape($this->element)."'";
		$sql2 .= " AND ctc.source = 'internal'";
		$resql = $this->db->query($sql2);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$listofprojectcontacttype[$obj->rowid] = $obj->code;
			}
		} else {
			dol_print_error($this->db);
		}
		if (count($listofprojectcontacttype) == 0) {
			$listofprojectcontacttype[0] = '0'; // To avoid syntax error if not found
		}

		if ($mode == 0) {
			$sql .= " AND ( p.public = 1";
			$sql .= " OR ( ec.fk_c_type_contact IN (".$this->db->sanitize(join(',', array_keys($listofprojectcontacttype))).")";
			$sql .= " AND ec.fk_socpeople = ".((int) $user->id).")";
			$sql .= " )";
		} elseif ($mode == 1) {
			$sql .= " AND ec.element_id = p.rowid";
			$sql .= " AND (";
			$sql .= "  ( ec.fk_c_type_contact IN (".$this->db->sanitize(join(',', array_keys($listofprojectcontacttype))).")";
			$sql .= " AND ec.fk_socpeople = ".((int) $user->id).")";
			$sql .= " )";
		} elseif ($mode == 2) {
			// No filter. Use this if user has permission to see all project
		}

		$sql .= $filter;
		//print $sql;

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$row = $this->db->fetch_row($resql);
				$projects[$row[0]] = $row[1];
				$temp[] = $row[0];
				$i++;
			}

			$this->db->free($resql);

			if ($list) {
				if (empty($temp)) {
					return '0';
				}
				$result = implode(',', $temp);
				return $result;
			}
		} else {
			dol_print_error($this->db);
		}

		return $projects;
	}

	/**
	 * Load an object from its id and create a new one in database
	 *
	 *  @param	User	$user		          User making the clone
	 *  @param	int		$fromid     	      Id of object to clone
	 *  @param	bool	$clone_contact	      Clone contact of project
	 *  @param	bool	$clone_task		      Clone task of project
	 *  @param	bool	$clone_project_file	  Clone file of project
	 *  @param	bool	$clone_task_file	  Clone file of task (if task are copied)
	 *  @param	bool	$clone_note		      Clone note of project
	 *  @param	bool	$move_date		      Move task date on clone
	 *  @param	integer	$notrigger		      No trigger flag
	 *  @param  int     $newthirdpartyid      New thirdparty id
	 *  @return	int						      New id of clone
	 */
	public function createFromClone(User $user, $fromid, $clone_contact = false, $clone_task = true, $clone_project_file = false, $clone_task_file = false, $clone_note = true, $move_date = true, $notrigger = 0, $newthirdpartyid = 0)
	{
		global $langs, $conf;

		$error = 0;

		dol_syslog("createFromClone clone_contact=".$clone_contact." clone_task=".$clone_task." clone_project_file=".$clone_project_file." clone_note=".$clone_note." move_date=".$move_date, LOG_DEBUG);

		$now = dol_mktime(0, 0, 0, idate('m', dol_now()), idate('d', dol_now()), idate('Y', dol_now()));

		$clone_project = new Project($this->db);

		$clone_project->context['createfromclone'] = 'createfromclone';

		$this->db->begin();

		// Load source object
		$clone_project->fetch($fromid);
		$clone_project->fetch_optionals();
		if ($newthirdpartyid > 0) {
			$clone_project->socid = $newthirdpartyid;
		}
		$clone_project->fetch_thirdparty();

		$orign_dt_start = $clone_project->date_start;
		$orign_project_ref = $clone_project->ref;

		$clone_project->id = 0;
		if ($move_date) {
			$clone_project->date_start = $now;
			if (!(empty($clone_project->date_end))) {
				$clone_project->date_end = $clone_project->date_end + ($now - $orign_dt_start);
			}
		}

		$clone_project->date_c = $now;

		if (!$clone_note) {
			$clone_project->note_private = '';
			$clone_project->note_public = '';
		}

		//Generate next ref
		$defaultref = '';
		$obj = empty($conf->global->PROJECT_ADDON) ? 'mod_project_simple' : $conf->global->PROJECT_ADDON;
		// Search template files
		$file = ''; $classname = ''; $filefound = 0;
		$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
		foreach ($dirmodels as $reldir) {
			$file = dol_buildpath($reldir."core/modules/project/".$obj.'.php', 0);
			if (file_exists($file)) {
				$filefound = 1;
				dol_include_once($reldir."core/modules/project/".$obj.'.php');
				$modProject = new $obj;
				$defaultref = $modProject->getNextValue(is_object($clone_project->thirdparty) ? $clone_project->thirdparty : null, $clone_project);
				break;
			}
		}
		if (is_numeric($defaultref) && $defaultref <= 0) {
			$defaultref = '';
		}

		$clone_project->ref = $defaultref;
		$clone_project->title = $langs->trans("CopyOf").' '.$clone_project->title;

		// Create clone
		$result = $clone_project->create($user, $notrigger);

		// Other options
		if ($result < 0) {
			$this->error .= $clone_project->error;
			$error++;
		}

		if (!$error) {
			//Get the new project id
			$clone_project_id = $clone_project->id;

			//Note Update
			if (!$clone_note) {
				$clone_project->note_private = '';
				$clone_project->note_public = '';
			} else {
				$this->db->begin();
				$res = $clone_project->update_note(dol_html_entity_decode($clone_project->note_public, ENT_QUOTES | ENT_HTML5), '_public');
				if ($res < 0) {
					$this->error .= $clone_project->error;
					$error++;
					$this->db->rollback();
				} else {
					$this->db->commit();
				}

				$this->db->begin();
				$res = $clone_project->update_note(dol_html_entity_decode($clone_project->note_private, ENT_QUOTES | ENT_HTML5), '_private');
				if ($res < 0) {
					$this->error .= $clone_project->error;
					$error++;
					$this->db->rollback();
				} else {
					$this->db->commit();
				}
			}

			//Duplicate contact
			if ($clone_contact) {
				$origin_project = new Project($this->db);
				$origin_project->fetch($fromid);

				foreach (array('internal', 'external') as $source) {
					$tab = $origin_project->liste_contact(-1, $source);
					if (is_array($tab) && count($tab)>0) {
						foreach ($tab as $contacttoadd) {
							$clone_project->add_contact($contacttoadd['id'], $contacttoadd['code'], $contacttoadd['source'], $notrigger);
							if ($clone_project->error == 'DB_ERROR_RECORD_ALREADY_EXISTS') {
								$langs->load("errors");
								$this->error .= $langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
								$error++;
							} else {
								if ($clone_project->error != '') {
									$this->error .= $clone_project->error;
									$error++;
								}
							}
						}
					} elseif ($tab < 0) {
						$this->error .= $origin_project->error;
						$error++;
					}
				}
			}

			//Duplicate file
			if ($clone_project_file) {
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				$clone_project_dir = $conf->project->dir_output."/".dol_sanitizeFileName($defaultref);
				$ori_project_dir = $conf->project->dir_output."/".dol_sanitizeFileName($orign_project_ref);

				if (dol_mkdir($clone_project_dir) >= 0) {
					$filearray = dol_dir_list($ori_project_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', '', SORT_ASC, 1);
					foreach ($filearray as $key => $file) {
						$rescopy = dol_copy($ori_project_dir.'/'.$file['name'], $clone_project_dir.'/'.$file['name'], 0, 1);
						if (is_numeric($rescopy) && $rescopy < 0) {
							$this->error .= $langs->trans("ErrorFailToCopyFile", $ori_project_dir.'/'.$file['name'], $clone_project_dir.'/'.$file['name']);
							$error++;
						}
					}
				} else {
					$this->error .= $langs->trans('ErrorInternalErrorDetected').':dol_mkdir';
					$error++;
				}
			}

			//Duplicate task
			if ($clone_task) {
				require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';

				$taskstatic = new Task($this->db);

				// Security check
				$socid = 0;
				if ($user->socid > 0) {
					$socid = $user->socid;
				}

				$tasksarray = $taskstatic->getTasksArray(0, 0, $fromid, $socid, 0);

				$tab_conv_child_parent = array();

				// Loop on each task, to clone it
				foreach ($tasksarray as $tasktoclone) {
					$result_clone = $taskstatic->createFromClone($user, $tasktoclone->id, $clone_project_id, $tasktoclone->fk_parent, $move_date, true, false, $clone_task_file, true, false);
					if ($result_clone <= 0) {
						$this->error .= $taskstatic->error;
						$error++;
					} else {
						$new_task_id = $result_clone;
						$taskstatic->fetch($tasktoclone->id);

						//manage new parent clone task id
						// if the current task has child we store the original task id and the equivalent clone task id
						if (($taskstatic->hasChildren()) && !array_key_exists($tasktoclone->id, $tab_conv_child_parent)) {
							$tab_conv_child_parent[$tasktoclone->id] = $new_task_id;
						}
					}
				}

				//Parse all clone node to be sure to update new parent
				$tasksarray = $taskstatic->getTasksArray(0, 0, $clone_project_id, $socid, 0);
				foreach ($tasksarray as $task_cloned) {
					$taskstatic->fetch($task_cloned->id);
					if ($taskstatic->fk_task_parent != 0) {
						$taskstatic->fk_task_parent = $tab_conv_child_parent[$taskstatic->fk_task_parent];
					}
					$res = $taskstatic->update($user, $notrigger);
					if ($result_clone <= 0) {
						$this->error .= $taskstatic->error;
						$error++;
					}
				}
			}
		}

		unset($clone_project->context['createfromclone']);

		if (!$error) {
			$this->db->commit();
			return $clone_project_id;
		} else {
			$this->db->rollback();
			dol_syslog(get_class($this)."::createFromClone nbError: ".$error." error : ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *    Shift project task date from current date to delta
	 *
	 *    @param	integer		$old_project_dt_start	Old project start date
	 *    @return	int				                    1 if OK or < 0 if KO
	 */
	public function shiftTaskDate($old_project_dt_start)
	{
		global $user, $langs, $conf;

		$error = 0;
		$result = 0;

		$taskstatic = new Task($this->db);

		// Security check
		$socid = 0;
		if ($user->socid > 0) {
			$socid = $user->socid;
		}

		$tasksarray = $taskstatic->getTasksArray(0, 0, $this->id, $socid, 0);

		foreach ($tasksarray as $tasktoshiftdate) {
			$to_update = false;
			// Fetch only if update of date will be made
			if ((!empty($tasktoshiftdate->date_start)) || (!empty($tasktoshiftdate->date_end))) {
				//dol_syslog(get_class($this)."::shiftTaskDate to_update", LOG_DEBUG);
				$to_update = true;
				$task = new Task($this->db);
				$result = $task->fetch($tasktoshiftdate->id);
				if (!$result) {
					$error++;
					$this->error .= $task->error;
				}
			}
			//print "$this->date_start + $tasktoshiftdate->date_start - $old_project_dt_start";exit;

			//Calcultate new task start date with difference between old proj start date and origin task start date
			if (!empty($tasktoshiftdate->date_start)) {
				$task->date_start = $this->date_start + ($tasktoshiftdate->date_start - $old_project_dt_start);
			}

			//Calcultate new task end date with difference between origin proj end date and origin task end date
			if (!empty($tasktoshiftdate->date_end)) {
				$task->date_end = $this->date_start + ($tasktoshiftdate->date_end - $old_project_dt_start);
			}

			if ($to_update) {
				$result = $task->update($user);
				if (!$result) {
					$error++;
					$this->error .= $task->error;
				}
			}
		}
		if ($error != 0) {
			return -1;
		}
		return $result;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Associate element to a project
	 *
	 *    @param	string	$tableName			Table of the element to update
	 *    @param	int		$elementSelectId	Key-rowid of the line of the element to update
	 *    @return	int							1 if OK or < 0 if KO
	 */
	public function update_element($tableName, $elementSelectId)
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX.$tableName;

		if ($tableName == "actioncomm") {
			$sql .= " SET fk_project=".$this->id;
			$sql .= " WHERE id=".((int) $elementSelectId);
		} elseif ($tableName == "entrepot") {
			$sql .= " SET fk_project=".$this->id;
			$sql .= " WHERE rowid=".((int) $elementSelectId);
		} else {
			$sql .= " SET fk_projet=".$this->id;
			$sql .= " WHERE rowid=".((int) $elementSelectId);
		}

		dol_syslog(get_class($this)."::update_element", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		} else {
			return 1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Associate element to a project
	 *
	 *    @param	string	$tableName			Table of the element to update
	 *    @param	int		$elementSelectId	Key-rowid of the line of the element to update
	 *    @param	string	$projectfield	    The column name that stores the link with the project
	 *
	 *    @return	int							1 if OK or < 0 if KO
	 */
	public function remove_element($tableName, $elementSelectId, $projectfield = 'fk_projet')
	{
		// phpcs:enable
		$sql = "UPDATE ".MAIN_DB_PREFIX.$tableName;

		if ($tableName == "actioncomm") {
			$sql .= " SET fk_project=NULL";
			$sql .= " WHERE id=".((int) $elementSelectId);
		} else {
			$sql .= " SET ".$projectfield."=NULL";
			$sql .= " WHERE rowid=".((int) $elementSelectId);
		}

		dol_syslog(get_class($this)."::remove_element", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->error = $this->db->lasterror();
			return -1;
		} else {
			return 1;
		}
	}

	/**
	 *  Create an intervention document on disk using template defined into PROJECT_ADDON_PDF
	 *
	 *  @param	string		$modele			Force template to use ('' by default)
	 *  @param	Translate	$outputlangs	Objet lang to use for translation
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *  @return int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $langs;

		$langs->load("projects");

		if (!dol_strlen($modele)) {
			$modele = 'baleine';

			if ($this->model_pdf) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->PROJECT_ADDON_PDF)) {
				$modele = $conf->global->PROJECT_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/project/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}


	/**
	 * Load time spent into this->weekWorkLoad and this->weekWorkLoadPerTask for all day of a week of project.
	 * Note: array weekWorkLoad and weekWorkLoadPerTask are reset and filled at each call.
	 *
	 * @param 	int		$datestart		First day of week (use dol_get_first_day to find this date)
	 * @param 	int		$taskid			Filter on a task id
	 * @param 	int		$userid			Time spent by a particular user
	 * @return 	int						<0 if OK, >0 if KO
	 */
	public function loadTimeSpent($datestart, $taskid = 0, $userid = 0)
	{
		$error = 0;

		$this->weekWorkLoad = array();
		$this->weekWorkLoadPerTask = array();

		if (empty($datestart)) {
			dol_print_error('', 'Error datestart parameter is empty');
		}

		$sql = "SELECT ptt.rowid as taskid, ptt.element_duration, ptt.element_date, ptt.element_datehour, ptt.fk_element";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time AS ptt, ".MAIN_DB_PREFIX."projet_task as pt";
		$sql .= " WHERE ptt.fk_element = pt.rowid";
		$sql .= " AND ptt.elementtype = 'task'";
		$sql .= " AND pt.fk_projet = ".((int) $this->id);
		$sql .= " AND (ptt.element_date >= '".$this->db->idate($datestart)."' ";
		$sql .= " AND ptt.element_date <= '".$this->db->idate(dol_time_plus_duree($datestart, 1, 'w') - 1)."')";
		if ($taskid) {
			$sql .= " AND ptt.fk_element=".((int) $taskid);
		}
		if (is_numeric($userid)) {
			$sql .= " AND ptt.fk_user=".((int) $userid);
		}

		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$daylareadyfound = array();

			$num = $this->db->num_rows($resql);
			$i = 0;
			// Loop on each record found, so each couple (project id, task id)
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$day = $this->db->jdate($obj->element_date); // task_date is date without hours
				if (empty($daylareadyfound[$day])) {
					$this->weekWorkLoad[$day] = $obj->element_duration;
					$this->weekWorkLoadPerTask[$day][$obj->fk_element] = $obj->element_duration;
				} else {
					$this->weekWorkLoad[$day] += $obj->element_duration;
					$this->weekWorkLoadPerTask[$day][$obj->fk_element] += $obj->element_duration;
				}
				$daylareadyfound[$day] = 1;
				$i++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Load time spent into this->weekWorkLoad and this->weekWorkLoadPerTask for all day of a week of project.
	 * Note: array weekWorkLoad and weekWorkLoadPerTask are reset and filled at each call.
	 *
	 * @param 	int		$datestart		First day of week (use dol_get_first_day to find this date)
	 * @param 	int		$taskid			Filter on a task id
	 * @param 	int		$userid			Time spent by a particular user
	 * @return 	int						<0 if OK, >0 if KO
	 */
	public function loadTimeSpentMonth($datestart, $taskid = 0, $userid = 0)
	{
		$error = 0;

		$this->monthWorkLoad = array();
		$this->monthWorkLoadPerTask = array();

		if (empty($datestart)) {
			dol_print_error('', 'Error datestart parameter is empty');
		}

		$sql = "SELECT ptt.rowid as taskid, ptt.element_duration, ptt.element_date, ptt.element_datehour, ptt.fk_element";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_time AS ptt, ".MAIN_DB_PREFIX."projet_task as pt";
		$sql .= " WHERE ptt.fk_element = pt.rowid";
		$sql .= " AND ptt.elementtype = 'task'";
		$sql .= " AND pt.fk_projet = ".((int) $this->id);
		$sql .= " AND (ptt.element_date >= '".$this->db->idate($datestart)."' ";
		$sql .= " AND ptt.element_date <= '".$this->db->idate(dol_time_plus_duree($datestart, 1, 'm') - 1)."')";
		if ($taskid) {
			$sql .= " AND ptt.fk_element=".((int) $taskid);
		}
		if (is_numeric($userid)) {
			$sql .= " AND ptt.fk_user=".((int) $userid);
		}

		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$weekalreadyfound = array();

			$num = $this->db->num_rows($resql);
			$i = 0;
			// Loop on each record found, so each couple (project id, task id)
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				if (!empty($obj->element_date)) {
					$date = explode('-', $obj->element_date);
					$week_number = getWeekNumber($date[2], $date[1], $date[0]);
				}
				if (empty($weekalreadyfound[$week_number])) {
					$this->monthWorkLoad[$week_number] = $obj->element_duration;
					$this->monthWorkLoadPerTask[$week_number][$obj->fk_element] = $obj->element_duration;
				} else {
					$this->monthWorkLoad[$week_number] += $obj->element_duration;
					$this->monthWorkLoadPerTask[$week_number][$obj->fk_element] += $obj->element_duration;
				}
				$weekalreadyfound[$week_number] = 1;
				$i++;
			}
			$this->db->free($resql);
			return 1;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 * @param	User	$user   Objet user
	 * @return WorkboardResponse|int <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user)
	{
		// phpcs:enable
		global $conf, $langs;

		// For external user, no check is done on company because readability is managed by public status of project and assignement.
		//$socid=$user->socid;

		$response = new WorkboardResponse();
		$response->warning_delay = $conf->project->warning_delay / 60 / 60 / 24;
		$response->label = $langs->trans("OpenedProjects");
		$response->labelShort = $langs->trans("Opened");
		$response->url = DOL_URL_ROOT.'/projet/list.php?search_project_user=-1&search_status=1&mainmenu=project';
		$response->img = img_object('', "projectpub");
		$response->nbtodo = 0;
		$response->nbtodolate = 0;

		$sql = "SELECT p.rowid, p.fk_statut as status, p.fk_opp_status, p.datee as datee";
		$sql .= " FROM (".MAIN_DB_PREFIX."projet as p";
		$sql .= ")";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
		// For external user, no check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
		$sql .= " WHERE p.fk_statut = 1";
		$sql .= " AND p.entity IN (".getEntity('project').')';


		$projectsListId = null;
		if (!$user->hasRight("projet", "all", "lire")) {
			$response->url = DOL_URL_ROOT.'/projet/list.php?search_status=1&mainmenu=project';
			$projectsListId = $this->getProjectsAuthorizedForUser($user, 0, 1);
			if (empty($projectsListId)) {
				return $response;
			}

			$sql .= " AND p.rowid IN (".$this->db->sanitize($projectsListId).")";
		}

		// No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
		//if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".((int) $socid).")";
		// For external user, no check is done on company permission because readability is managed by public status of project and assignement.
		//if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id).") OR (s.rowid IS NULL))";

		//print $sql;
		$resql = $this->db->query($sql);
		if ($resql) {
			$project_static = new Project($this->db);


			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;

				$project_static->statut = $obj->status;
				$project_static->opp_status = $obj->fk_opp_status;
				$project_static->date_end = $this->db->jdate($obj->datee);

				if ($project_static->hasDelay()) {
					$response->nbtodolate++;
				}
			}

			return $response;
		}

		$this->error = $this->db->error();
		return -1;
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $dbs 		Database handler
	 * @param int $origin_id 	Old thirdparty id
	 * @param int $dest_id 		New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $dbs, $origin_id, $dest_id)
	{
		$tables = array(
			'projet'
		);

		return CommonObject::commonReplaceThirdparty($dbs, $origin_id, $dest_id, $tables);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Charge indicateurs this->nb pour le tableau de bord
	 *
	 * @return     int         <0 if KO, >0 if OK
	 */
	public function load_state_board()
	{
		// phpcs:enable
		global $user;

		$this->nb = array();

		$sql = "SELECT count(p.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."projet as p";
		$sql .= " WHERE";
		$sql .= " p.entity IN (".getEntity('project').")";
		if (empty($user->rights->projet->all->lire)) {
			$projectsListId = $this->getProjectsAuthorizedForUser($user, 0, 1);
			$sql .= "AND p.rowid IN (".$this->db->sanitize($projectsListId).")";
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["projects"] = $obj->nb;
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
	 * Is the project delayed?
	 *
	 * @return bool
	 */
	public function hasDelay()
	{
		global $conf;

		if (!($this->statut == self::STATUS_VALIDATED)) {
			return false;
		}
		if (!$this->date_end) {
			return false;
		}

		$now = dol_now();

		return ($this->date_end) < ($now - $conf->project->warning_delay);
	}


	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	public function info($id)
	{
		$sql = 'SELECT c.rowid, datec as datec, tms as datem,';
		$sql .= ' date_close as datecloture,';
		$sql .= ' fk_user_creat as fk_user_author, fk_user_close as fk_use_cloture';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'projet as c';
		$sql .= ' WHERE c.rowid = '.((int) $id);
		$result = $this->db->query($sql);
		if ($result) {
			if ($this->db->num_rows($result)) {
				$obj = $this->db->fetch_object($result);
				$this->id = $obj->rowid;
				if ($obj->fk_user_author) {
					$cuser = new User($this->db);
					$cuser->fetch($obj->fk_user_author);
					$this->user_creation = $cuser;
				}

				if (!empty($obj->fk_user_cloture)) {
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation     = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_cloture      = $this->db->jdate($obj->datecloture);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
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
	 * @return 	int							<0 if KO, >0 if OK
	 */
	public function setCategories($categories)
	{
		require_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		return parent::setCategoriesCommon($categories, Categorie::TYPE_PROJECT);
	}


	/**
	 * Create an array of tasks of current project
	 *
	 * @param	User	$user       		Object user we want project allowed to
	 * @param	int		$loadRoleMode		1= will test Roles on task;  0 used in delete project action
	 * @return 	int							>0 if OK, <0 if KO
	 */
	public function getLinesArray($user, $loadRoleMode = 1)
	{
		require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
		$taskstatic = new Task($this->db);

		$this->lines = $taskstatic->getTasksArray(0, $user, $this->id, 0, 0, '',  '-1', '', 0, 0, array(),  0,  array(),  0,  $loadRoleMode);
	}

	/**
	 *  Function sending an email to the current member with the text supplied in parameter.
	 *
	 *  @param	string	$text				Content of message (not html entities encoded)
	 *  @param	string	$subject			Subject of message
	 *  @param 	array	$filename_list      Array of attached files
	 *  @param 	array	$mimetype_list      Array of mime types of attached files
	 *  @param 	array	$mimefilename_list  Array of public names of attached files
	 *  @param 	string	$addr_cc            Email cc
	 *  @param 	string	$addr_bcc           Email bcc
	 *  @param 	int		$deliveryreceipt	Ask a delivery receipt
	 *  @param	int		$msgishtml			1=String IS already html, 0=String IS NOT html, -1=Unknown need autodetection
	 *  @param	string	$errors_to			erros to
	 *  @param	string	$moreinheader		Add more html headers
	 *  @since V18
	 *  @return	int							<0 if KO, >0 if OK
	 */
	public function sendEmail($text, $subject, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $addr_cc = "", $addr_bcc = "", $deliveryreceipt = 0, $msgishtml = -1, $errors_to = '', $moreinheader = '')
	{
		global $conf, $langs;
		// TODO EMAIL

		return 1;
	}
	/**
	 *	Return clicable link of object (with eventually picto)
	 *
	 *	@param      string	    $option                 Where point the link (0=> main card, 1,2 => shipment, 'nolink'=>No link)
	 *  @return		string		HTML Code for Kanban thumb.
	 */
	public function getKanbanView($option = '')
	{
		global $langs, $user;

		$selected = (empty($arraydata['selected']) ? 0 : $arraydata['selected']);

		$return = '<div class="box-flex-item box-flex-grow-zero">';
		$return .= '<div class="info-box info-box-sm">';
		$return .= '<span class="info-box-icon bg-infobox-action">';
		$return .= img_picto('', $this->picto);
		//$return .= '<i class="fa fa-dol-action"></i>'; // Can be image
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl() : $this->ref);
		if ($this->hasDelay()) {
			$return .= img_warning($langs->trans('Late'));
		}
		$return .= '</span>';
		$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		if (property_exists($this, 'date_start') && $this->date_start) {
			$return .= '<br><span class="info-box-label">'.dol_print_date($this->date_start, 'day').'</>';
		}
		if (property_exists($this, 'date_end') && $this->date_end) {
			if ($this->date_start) {
				$return .= ' - ';
			} else {
				$return .= '<br>';
			}
			$return .= '<span class="info-box-label">'.dol_print_date($this->date_end, 'day').'</>';
		}
		/*if (property_exists($this, 'user_author_id')) {
			$return .= '<br><span class="info-box-label opacitymedium">'.$langs->trans("Author").'</span>';
			$return .= '<span> : '.$user->getNomUrl(1).'</span>';
		}*/
		if ($this->usage_opportunity && $this->opp_status_code) {
			//$return .= '<br><span class="info-bo-label opacitymedium">'.$langs->trans("OpportunityStatusShort").'</span>';
			$return .= '<br><span class="info-box-label">'.	$langs->trans("OppStatus".$this->opp_status_code);
			$return .= ' <span class="opacitymedium">('.round($this->opp_percent).'%)</span>';
			$return .= '<br><span class="amount">'.price($this->opp_amount).'</span>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';
		return $return;
	}
}
