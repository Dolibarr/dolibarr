<?php
/* Copyright (C) 2013-2018 Jean-François Ferry <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel <christophe@altairis.fr>
 * Copyright (C) 2019-2020 Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2020      Laurent Destailleur <eldy@users.sourceforge.net>
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
 *  \file       ticket/class/ticket.class.php
 *  \ingroup    ticket
 *  \brief      Class file for object ticket
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/ticket.lib.php';


/**
 *    Class to manage ticket
 */
class Ticket extends CommonObject
{
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'ticket';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'ticket';

	/**
	 * @var string Name of field for link to tickets
	 */
	public $fk_element = 'fk_ticket';

	/**
	 * @var int  Does ticketcore support multicompany module ? 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int  Does ticketcore support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for ticketcore. Must be the part after the 'object_' into object_ticketcore.png
	 */
	public $picto = 'ticket';


	/**
	 * @var string Hash to identify ticket publically
	 */
	public $track_id;

	/**
	 * @var int Thirdparty ID
	 */
	public $fk_soc;

	/**
	 * @var int Project ID
	 */
	public $fk_project;

	/**
	 * @var string Person email who have create ticket
	 */
	public $origin_email;

	/**
	 * @var int User id who have create ticket
	 */
	public $fk_user_create;

	/**
	 * @var int User id who have ticket assigned
	 */
	public $fk_user_assign;

	/**
	 * var string Ticket subject
	 */
	public $subject;

	/**
	 * @var string Ticket message
	 */
	public $message;

	/**
	 * @var int  Ticket statut
	 * @deprecated
	 */
	public $fk_statut;

	/**
	 * @var int  Ticket status
	 */
	public $status;

	/**
	 * @var string State resolution
	 */
	public $resolution;

	/**
	 * @var int Progress in percent
	 */
	public $progress;

	/**
	 * @var string Duration for ticket
	 */
	public $timing;

	/**
	 * @var string Type code
	 */
	public $type_code;

	/**
	 * @var string Category code
	 */
	public $category_code;

	/**
	 * @var string Severity code
	 */
	public $severity_code;

	/**
	 * Type label
	 */
	public $type_label;

	/**
	 * Category label
	 */
	public $category_label;

	/**
	 * Severity label
	 */
	public $severity_label;

	/**
	 * Email from user
	 */
	public $email_from;

	/**
	 * @var int Creation date
	 */
	public $datec = '';

	/**
	 * @var int Read date
	 */
	public $date_read = '';

	/**
	 * @var int Close ticket date
	 */
	public $date_close = '';

	/**
	 * @var array cache_types_tickets
	 */
	public $cache_types_tickets;

	/**
	 * @var array tickets categories
	 */
	public $cache_category_tickets;

	/**
	 * @var int Notify thirdparty at create
	 */
	public $notify_tiers_at_create;

	/**
	 * @var string msgid
	 */
	public $email_msgid;

	public $lines;

	/**
	 * @var string Regex pour les images
	 */
	public $regeximgext = '\.jpg|\.jpeg|\.bmp|\.gif|\.png|\.tiff';

	/**
	 * Status
	 */
	const STATUS_NOT_READ = 0;
	const STATUS_READ = 1;
	const STATUS_ASSIGNED = 2;
	const STATUS_IN_PROGRESS = 3;
	const STATUS_NEED_MORE_INFO = 5;
	const STATUS_WAITING = 7;			// on hold
	const STATUS_CLOSED = 8;			// Closed - Solved
	const STATUS_CANCELED = 9;			// Closed - Not solved


	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or '$conf->global->MY_SETUP_PARAM)
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommanded to name the field fk_...).
	 *  'searchall' is 1 if we want to search in this field when making a search from the quick search button.
	 *  'isameasure' must be set to 1 if you want to have a total on list for this field. Field type must be summable like integer or double(24,8).
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'maxwidth200', 'wordbreak', 'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	public $fields = array(
		'rowid' => array('type'=>'integer', 'label'=>'TechnicalID', 'position'=>1, 'visible'=>-2, 'enabled'=>1, 'position'=>1, 'notnull'=>1, 'index'=>1, 'comment'=>"Id"),
		'entity' => array('type'=>'integer', 'label'=>'Entity', 'visible'=>0, 'enabled'=>1, 'position'=>5, 'notnull'=>1, 'index'=>1),
		'ref' => array('type'=>'varchar(128)', 'label'=>'Ref', 'visible'=>1, 'enabled'=>1, 'position'=>10, 'notnull'=>1, 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object", 'css'=>'', 'showoncombobox'=>1),
		'track_id' => array('type'=>'varchar(255)', 'label'=>'TicketTrackId', 'visible'=>-2, 'enabled'=>1, 'position'=>11, 'notnull'=>-1, 'searchall'=>1, 'help'=>"Help text"),
		'fk_user_create' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'Author', 'visible'=>1, 'enabled'=>1, 'position'=>15, 'notnull'=>1, 'css'=>'tdoverflowmax125 maxwidth150onsmartphone'),
		'origin_email' => array('type'=>'mail', 'label'=>'OriginEmail', 'visible'=>-2, 'enabled'=>1, 'position'=>16, 'notnull'=>1, 'index'=>1, 'searchall'=>1, 'comment'=>"Reference of object", 'css'=>'tdoverflowmax150'),
		'subject' => array('type'=>'varchar(255)', 'label'=>'Subject', 'visible'=>1, 'enabled'=>1, 'position'=>18, 'notnull'=>-1, 'searchall'=>1, 'help'=>"", 'css'=>'maxwidth200 tdoverflowmax200', 'autofocusoncreate'=>1),
		'type_code' => array('type'=>'varchar(32)', 'label'=>'Type', 'visible'=>1, 'enabled'=>1, 'position'=>20, 'notnull'=>-1, 'help'=>"", 'css'=>'maxwidth125 tdoverflowmax50'),
		'category_code' => array('type'=>'varchar(32)', 'label'=>'TicketCategory', 'visible'=>-1, 'enabled'=>1, 'position'=>21, 'notnull'=>-1, 'help'=>"", 'css'=>'maxwidth100 tdoverflowmax200'),
		'severity_code' => array('type'=>'varchar(32)', 'label'=>'Severity', 'visible'=>1, 'enabled'=>1, 'position'=>22, 'notnull'=>-1, 'help'=>"", 'css'=>'maxwidth100'),
		'fk_soc' => array('type'=>'integer:Societe:societe/class/societe.class.php', 'label'=>'ThirdParty', 'visible'=>1, 'enabled'=>'$conf->societe->enabled', 'position'=>50, 'notnull'=>-1, 'index'=>1, 'searchall'=>1, 'help'=>"LinkToThirparty", 'css'=>'tdoverflowmax150 maxwidth150onsmartphone'),
		'notify_tiers_at_create' => array('type'=>'integer', 'label'=>'NotifyThirdparty', 'visible'=>-1, 'enabled'=>0, 'position'=>51, 'notnull'=>1, 'index'=>1),
		'fk_project' => array('type'=>'integer:Project:projet/class/project.class.php', 'label'=>'Project', 'visible'=>-1, 'enabled'=>1, 'position'=>52, 'notnull'=>-1, 'index'=>1, 'help'=>"LinkToProject"),
		//'timing' => array('type'=>'varchar(20)', 'label'=>'Timing', 'visible'=>-1, 'enabled'=>1, 'position'=>42, 'notnull'=>-1, 'help'=>""),	// what is this ?
		'datec' => array('type'=>'datetime', 'label'=>'DateCreation', 'visible'=>1, 'enabled'=>1, 'position'=>500, 'notnull'=>1),
		'date_read' => array('type'=>'datetime', 'label'=>'TicketReadOn', 'visible'=>-1, 'enabled'=>1, 'position'=>501, 'notnull'=>1),
		'fk_user_assign' => array('type'=>'integer:User:user/class/user.class.php', 'label'=>'AssignedTo', 'visible'=>1, 'enabled'=>1, 'position'=>505, 'notnull'=>1, 'css'=>'tdoverflowmax125'),
		'date_close' => array('type'=>'datetime', 'label'=>'TicketCloseOn', 'visible'=>-1, 'enabled'=>1, 'position'=>510, 'notnull'=>1),
		'tms' => array('type'=>'timestamp', 'label'=>'DateModification', 'visible'=>-1, 'enabled'=>1, 'position'=>520, 'notnull'=>1),
		'message' => array('type'=>'text', 'label'=>'Message', 'visible'=>-2, 'enabled'=>1, 'position'=>540, 'notnull'=>-1,),
		'email_msgid' => array('type'=>'varchar(255)', 'label'=>'EmailMsgID', 'visible'=>-2, 'enabled'=>1, 'position'=>540, 'notnull'=>-1, 'help'=>'EmailMsgIDDesc'),
		'progress' => array('type'=>'integer', 'label'=>'Progression', 'visible'=>-1, 'enabled'=>1, 'position'=>540, 'notnull'=>-1, 'css'=>'right', 'help'=>"", 'isameasure'=>2, 'csslist'=>'width50'),
		'resolution' => array('type'=>'integer', 'label'=>'Resolution', 'visible'=>-1, 'enabled'=>'$conf->global->TICKET_ENABLE_RESOLUTION', 'position'=>550, 'notnull'=>1),
		'fk_statut' => array('type'=>'integer', 'label'=>'Status', 'visible'=>1, 'enabled'=>1, 'position'=>600, 'notnull'=>1, 'index'=>1, 'arrayofkeyval'=>array(0 => 'Unread', 1 => 'Read', 3 => 'Answered', 4 => 'Assigned', 5 => 'InProgress', 6 => 'Waiting', 8 => 'SolvedClosed', 9 => 'Deleted')),
		'import_key' =>array('type'=>'varchar(14)', 'label'=>'ImportId', 'enabled'=>1, 'visible'=>-2, 'position'=>900),
	);
	// END MODULEBUILDER PROPERTIES


	/**
	 *  Constructor
	 *
	 *  @param DoliDb $db Database handler
	 */
	public function __construct($db)
	{
		global $conf;

		$this->db = $db;

		$this->statuts_short = array(
			self::STATUS_NOT_READ => 'Unread',
			self::STATUS_READ => 'Read',
			self::STATUS_ASSIGNED => 'Assigned',
			self::STATUS_IN_PROGRESS => 'InProgress',
			self::STATUS_WAITING => 'OnHold',
			self::STATUS_NEED_MORE_INFO => 'NeedMoreInformationShort',
			self::STATUS_CLOSED => 'SolvedClosed',
			self::STATUS_CANCELED => 'Canceled'
		);
		$this->statuts = array(
			self::STATUS_NOT_READ => 'Unread',
			self::STATUS_READ => 'Read',
			self::STATUS_ASSIGNED => 'Assigned',
			self::STATUS_IN_PROGRESS => 'InProgress',
			self::STATUS_WAITING => 'OnHold',
			self::STATUS_NEED_MORE_INFO => 'NeedMoreInformation',
			self::STATUS_CLOSED => 'SolvedClosed',
			self::STATUS_CANCELED => 'Canceled'
		);
	}

	/**
	 *    Check properties of ticket are ok (like ref, track_id, ...).
	 *    All properties must be already loaded on object (this->ref, this->track_id, ...).
	 *
	 *    @return int        0 if OK, <0 if KO
	 */
	private function verify()
	{
		$this->errors = array();

		$result = 0;

		// Clean parameters
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}

		if (isset($this->track_id)) {
			$this->track_id = trim($this->track_id);
		}

		if (isset($this->fk_soc)) {
			$this->fk_soc = (int) $this->fk_soc;
		}

		if (isset($this->fk_project)) {
			$this->fk_project = (int) $this->fk_project;
		}

		if (isset($this->origin_email)) {
			$this->origin_email = trim($this->origin_email);
		}

		if (isset($this->fk_user_create)) {
			$this->fk_user_create = (int) $this->fk_user_create;
		}

		if (isset($this->fk_user_assign)) {
			$this->fk_user_assign = (int) $this->fk_user_assign;
		}

		if (isset($this->subject)) {
			$this->subject = trim($this->subject);
		}

		if (isset($this->message)) {
			$this->message = trim($this->message);
		}

		if (isset($this->fk_statut)) {
			$this->fk_statut = (int) $this->fk_statut;
		}

		if (isset($this->resolution)) {
			$this->resolution = trim($this->resolution);
		}

		if (isset($this->progress)) {
			$this->progress = trim($this->progress);
		}

		if (isset($this->timing)) {
			$this->timing = trim($this->timing);
		}

		if (isset($this->type_code)) {
			$this->type_code = trim($this->type_code);
		}

		if (isset($this->category_code)) {
			$this->category_code = trim($this->category_code);
		}

		if (isset($this->severity_code)) {
			$this->severity_code = trim($this->severity_code);
		}

		if (empty($this->ref)) {
			$this->errors[] = 'ErrorTicketRefRequired';
			dol_syslog(get_class($this)."::create error -1 ref null", LOG_ERR);
			$result = -1;
		}

		return $result;
	}

	/**
	 *  Create object into database
	 *
	 *  @param  User $user      User that creates
	 *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
	 *  @return int                      <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		$this->datec = dol_now();
		if (empty($this->track_id)) {
			$this->track_id = generate_random_id(16);
		}

		// Check more parameters
		// If error, this->errors[] is filled
		$result = $this->verify();

		if ($result >= 0) {
			// Insert request
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."ticket(";
			$sql .= "ref,";
			$sql .= "track_id,";
			$sql .= "fk_soc,";
			$sql .= "fk_project,";
			$sql .= "origin_email,";
			$sql .= "fk_user_create,";
			$sql .= "fk_user_assign,";
			$sql .= "email_msgid,";
			$sql .= "subject,";
			$sql .= "message,";
			$sql .= "fk_statut,";
			$sql .= "resolution,";
			$sql .= "progress,";
			$sql .= "timing,";
			$sql .= "type_code,";
			$sql .= "category_code,";
			$sql .= "severity_code,";
			$sql .= "datec,";
			$sql .= "date_read,";
			$sql .= "date_close,";
			$sql .= "entity,";
			$sql .= "notify_tiers_at_create";
			$sql .= ") VALUES (";
			$sql .= " ".(!isset($this->ref) ? '' : "'".$this->db->escape($this->ref)."'").",";
			$sql .= " ".(!isset($this->track_id) ? 'NULL' : "'".$this->db->escape($this->track_id)."'").",";
			$sql .= " ".($this->fk_soc > 0 ? $this->db->escape($this->fk_soc) : "null").",";
			$sql .= " ".($this->fk_project > 0 ? $this->db->escape($this->fk_project) : "null").",";
			$sql .= " ".(!isset($this->origin_email) ? 'NULL' : "'".$this->db->escape($this->origin_email)."'").",";
			$sql .= " ".($this->fk_user_create > 0 ? $this->fk_user_create : ($user->id > 0 ? $user->id : 'NULL')).",";
			$sql .= " ".($this->fk_user_assign > 0 ? $this->fk_user_assign : 'NULL').",";
			$sql .= " ".(empty($this->email_msgid) ? 'NULL' : "'".$this->db->escape($this->email_msgid)."'").",";
			$sql .= " ".(!isset($this->subject) ? 'NULL' : "'".$this->db->escape($this->subject)."'").",";
			$sql .= " ".(!isset($this->message) ? 'NULL' : "'".$this->db->escape($this->message)."'").",";
			$sql .= " ".(!isset($this->fk_statut) ? '0' : "'".$this->db->escape($this->fk_statut)."'").",";
			$sql .= " ".(!isset($this->resolution) ? 'NULL' : "'".$this->db->escape($this->resolution)."'").",";
			$sql .= " ".(!isset($this->progress) ? '0' : "'".$this->db->escape($this->progress)."'").",";
			$sql .= " ".(!isset($this->timing) ? 'NULL' : "'".$this->db->escape($this->timing)."'").",";
			$sql .= " ".(!isset($this->type_code) ? 'NULL' : "'".$this->db->escape($this->type_code)."'").",";
			$sql .= " ".(empty($this->category_code) || $this->category_code == '-1' ? 'NULL' : "'".$this->db->escape($this->category_code)."'").",";
			$sql .= " ".(!isset($this->severity_code) ? 'NULL' : "'".$this->db->escape($this->severity_code)."'").",";
			$sql .= " ".(!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : "'".$this->db->idate($this->datec)."'").",";
			$sql .= " ".(!isset($this->date_read) || dol_strlen($this->date_read) == 0 ? 'NULL' : "'".$this->db->idate($this->date_read)."'").",";
			$sql .= " ".(!isset($this->date_close) || dol_strlen($this->date_close) == 0 ? 'NULL' : "'".$this->db->idate($this->date_close)."'")."";
			$sql .= ", ".((int) $conf->entity);
			$sql .= ", ".(!isset($this->notify_tiers_at_create) ? '1' : "'".$this->db->escape($this->notify_tiers_at_create)."'");
			$sql .= ")";

			$this->db->begin();

			dol_syslog(get_class($this)."::create", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
			}

			if (!$error) {
				$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."ticket");

				if (!$notrigger) {
					// Call trigger
					$result = $this->call_trigger('TICKET_CREATE', $user);
					if ($result < 0) {
						$error++;
					}
					// End call triggers
				}
			}

			if (!$error && ! empty($conf->global->TICKET_ADD_AUTHOR_AS_CONTACT)) {
				// add creator as contributor
				if ($this->add_contact($user->id, 'CONTRIBUTOR', 'internal') < 0) {
					$error++;
				}
			}

			//Update extrafield
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
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
		} else {
			$this->db->rollback();
			dol_syslog(get_class($this)."::Create fails verify ".join(',', $this->errors), LOG_WARNING);
			return -3;
		}
	}

	/**
	 *  Load object in memory from the database
	 *
	 *  @param  int        	$id    			Id object
	 *  @param	string		$ref			Ref
	 *  @param	string		$track_id		Track id, a hash like ref
	 *  @param	string		$email_msgid	Email msgid
	 *  @return int              			<0 if KO, >0 if OK
	 */
	public function fetch($id = '', $ref = '', $track_id = '', $email_msgid = '')
	{
		global $langs;

		// Check parameters
		if (empty($id) && empty($ref) && empty($track_id) && empty($email_msgid)) {
			$this->error = 'ErrorWrongParameters';
			dol_print_error(get_class($this)."::fetch ".$this->error);
			return -1;
		}

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.ref,";
		$sql .= " t.track_id,";
		$sql .= " t.fk_soc,";
		$sql .= " t.fk_project,";
		$sql .= " t.origin_email,";
		$sql .= " t.fk_user_create,";
		$sql .= " t.fk_user_assign,";
		$sql .= " t.email_msgid,";
		$sql .= " t.subject,";
		$sql .= " t.message,";
		$sql .= " t.fk_statut as status,";
		$sql .= " t.resolution,";
		$sql .= " t.progress,";
		$sql .= " t.timing,";
		$sql .= " t.type_code,";
		$sql .= " t.category_code,";
		$sql .= " t.severity_code,";
		$sql .= " t.datec,";
		$sql .= " t.date_read,";
		$sql .= " t.date_close,";
		$sql .= " t.tms,";
		$sql .= " type.label as type_label, category.label as category_label, severity.label as severity_label";
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_type as type ON type.code=t.type_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_category as category ON category.code=t.category_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_severity as severity ON severity.code=t.severity_code";

		if ($id) {
			$sql .= " WHERE t.rowid = ".((int) $id);
		} else {
			$sql .= " WHERE t.entity IN (".getEntity($this->element, 1).")";
			if (!empty($ref)) {
				$sql .= " AND t.ref = '".$this->db->escape($ref)."'";
			} elseif ($track_id) {
				$sql .= " AND t.track_id = '".$this->db->escape($track_id)."'";
			} else {
				$sql .= " AND t.email_msgid = '".$this->db->escape($email_msgid)."'";
			}
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
				$this->ref = $obj->ref;
				$this->track_id = $obj->track_id;
				$this->fk_soc = $obj->fk_soc;
				$this->socid = $obj->fk_soc; // for fetch_thirdparty() method
				$this->fk_project = $obj->fk_project;
				$this->origin_email = $obj->origin_email;
				$this->fk_user_create = $obj->fk_user_create;
				$this->fk_user_assign = $obj->fk_user_assign;
				$this->email_msgid = $obj->email_msgid;
				$this->subject = $obj->subject;
				$this->message = $obj->message;

				$this->status = $obj->status;
				$this->fk_statut = $this->status; // For backward compatibility

				$this->resolution = $obj->resolution;
				$this->progress = $obj->progress;
				$this->timing = $obj->timing;

				$this->type_code = $obj->type_code;
				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$label_type = ($langs->trans("TicketTypeShort".$obj->type_code) != ("TicketTypeShort".$obj->type_code) ? $langs->trans("TicketTypeShort".$obj->type_code) : ($obj->type_label != '-' ? $obj->type_label : ''));
				$this->type_label = $label_type;

				$this->category_code = $obj->category_code;
				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$label_category = ($langs->trans("TicketCategoryShort".$obj->category_code) != ("TicketCategoryShort".$obj->category_code) ? $langs->trans("TicketCategoryShort".$obj->category_code) : ($obj->category_label != '-' ? $obj->category_label : ''));
				$this->category_label = $label_category;

				$this->severity_code = $obj->severity_code;
				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$label_severity = ($langs->trans("TicketSeverityShort".$obj->severity_code) != ("TicketSeverityShort".$obj->severity_code) ? $langs->trans("TicketSeverityShort".$obj->severity_code) : ($obj->severity_label != '-' ? $obj->severity_label : ''));
				$this->severity_label = $label_severity;

				$this->datec = $this->db->jdate($obj->datec);
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_read = $this->db->jdate($obj->date_read);
				$this->date_validation = $this->db->jdate($obj->date_read);
				$this->date_close = $this->db->jdate($obj->date_close);
				$this->tms = $this->db->jdate($obj->tms);
				$this->date_modification = $this->db->jdate($obj->tms);

				$this->fetch_optionals();

				$this->db->free($resql);
				return 1;
			} else {
				return 0;
			}
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 * Load all objects in memory from database
	 *
	 * @param  User   $user      User for action
	 * @param  string $sortorder Sort order
	 * @param  string $sortfield Sort field
	 * @param  int    $limit     page number
	 * @param  int    $offset    Offset for query
	 * @param  int    $arch      archive or not (not used)
	 * @param  array  $filter    Filter for query
	 *                           output
	 * @return int <0 if KO, >0 if OK
	 */
	public function fetchAll($user, $sortorder = 'ASC', $sortfield = 't.datec', $limit = '', $offset = 0, $arch = '', $filter = '')
	{
		global $langs;

		$extrafields = new ExtraFields($this->db);

		// fetch optionals attributes and labels
		$extrafields->fetch_name_optionals_label($this->table_element);

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.ref,";
		$sql .= " t.track_id,";
		$sql .= " t.fk_soc,";
		$sql .= " t.fk_project,";
		$sql .= " t.origin_email,";
		$sql .= " t.fk_user_create, uc.lastname as user_create_lastname, uc.firstname as user_create_firstname,";
		$sql .= " t.fk_user_assign, ua.lastname as user_assign_lastname, ua.firstname as user_assign_firstname,";
		$sql .= " t.subject,";
		$sql .= " t.message,";
		$sql .= " t.fk_statut,";
		$sql .= " t.resolution,";
		$sql .= " t.progress,";
		$sql .= " t.timing,";
		$sql .= " t.type_code,";
		$sql .= " t.category_code,";
		$sql .= " t.severity_code,";
		$sql .= " t.datec,";
		$sql .= " t.date_read,";
		$sql .= " t.date_close,";
		$sql .= " t.tms";
		$sql .= ", type.label as type_label, category.label as category_label, severity.label as severity_label";
		// Add fields for extrafields
		foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
			$sql .= ($extrafields->attributes[$this->table_element]['type'][$key] != 'separate' ? ",ef.".$key." as options_".$key : '');
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_type as type ON type.code=t.type_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_category as category ON category.code=t.category_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_severity as severity ON severity.code=t.severity_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid=t.fk_soc";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as uc ON uc.rowid=t.fk_user_create";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as ua ON ua.rowid=t.fk_user_assign";
		if (is_array($extrafields->attributes[$this->table_element]['label']) && count($extrafields->attributes[$this->table_element]['label'])) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."ticket_extrafields as ef on (t.rowid = ef.fk_object)";
		}
		if (empty($user->rights->societe->client->voir) && !$user->socid) {
			$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
		}

		$sql .= " WHERE t.entity IN (".getEntity('ticket').")";

		// Manage filter
		if (!empty($filter)) {
			foreach ($filter as $key => $value) {
				if (strpos($key, 'date')) { // To allow $filter['YEAR(s.dated)']=>$year
					$sql .= " AND ".$key." = '".$this->db->escape($value)."'";
				} elseif (($key == 't.fk_user_assign') || ($key == 't.type_code') || ($key == 't.category_code') || ($key == 't.severity_code') || ($key == 't.fk_soc')) {
					$sql .= " AND ".$key." = '".$this->db->escape($value)."'";
				} elseif ($key == 't.fk_statut') {
					if (is_array($value) && count($value) > 0) {
						$sql .= " AND ".$key." IN (".$this->db->sanitize(implode(',', $value)).")";
					} else {
						$sql .= " AND ".$key.' = '.((int) $value);
					}
				} else {
					$sql .= " AND ".$key." LIKE '%".$this->db->escape($value)."%'";
				}
			}
		}
		if (empty($user->rights->societe->client->voir) && !$user->socid) {
			$sql .= " AND t.fk_soc = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
		} elseif ($user->socid) {
			$sql .= " AND t.fk_soc = ".((int) $user->socid);
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this)."::fetch_all", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					$line = new TicketsLine();

					$line->id = $obj->rowid;
					$line->rowid = $obj->rowid;
					$line->ref = $obj->ref;
					$line->track_id = $obj->track_id;
					$line->fk_soc = $obj->fk_soc;
					$line->fk_project = $obj->fk_project;
					$line->origin_email = $obj->origin_email;

					$line->fk_user_create = $obj->fk_user_create;
					$line->user_create_lastname = $obj->user_create_lastname;
					$line->user_create_firstname = $obj->user_create_firstname;

					$line->fk_user_assign = $obj->fk_user_assign;
					$line->user_assign_lastname = $obj->user_assign_lastname;
					$line->user_assign_firstname = $obj->user_assign_firstname;

					$line->subject = $obj->subject;
					$line->message = $obj->message;
					$line->fk_statut = $obj->fk_statut;
					$line->resolution = $obj->resolution;
					$line->progress = $obj->progress;
					$line->timing = $obj->timing;

					// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
					$label_type = ($langs->trans("TicketTypeShort".$obj->type_code) != ("TicketTypeShort".$obj->type_code) ? $langs->trans("TicketTypeShort".$obj->type_code) : ($obj->type_label != '-' ? $obj->type_label : ''));
					$line->type_label = $label_type;

					$this->category_code = $obj->category_code;
					// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
					$label_category = ($langs->trans("TicketCategoryShort".$obj->category_code) != ("TicketCategoryShort".$obj->category_code) ? $langs->trans("TicketCategoryShort".$obj->category_code) : ($obj->category_label != '-' ? $obj->category_label : ''));
					$line->category_label = $label_category;

					$this->severity_code = $obj->severity_code;
					// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
					$label_severity = ($langs->trans("TicketSeverityShort".$obj->severity_code) != ("TicketSeverityShort".$obj->severity_code) ? $langs->trans("TicketSeverityShort".$obj->severity_code) : ($obj->severity_label != '-' ? $obj->severity_label : ''));
					$line->severity_label = $label_severity;

					$line->datec = $this->db->jdate($obj->datec);
					$line->date_read = $this->db->jdate($obj->date_read);
					$line->date_close = $this->db->jdate($obj->date_close);

					// Extra fields
					if (is_array($extrafields->attributes[$this->table_element]['label']) && count($extrafields->attributes[$this->table_element]['label'])) {
						foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
							$tmpkey = 'options_'.$key;
							$line->{$tmpkey} = $obj->$tmpkey;
						}
					}

					$this->lines[$i] = $line;
					$i++;
				}
			}
			$this->db->free($resql);
			return $num;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch_all ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Update object into database
	 *
	 *  @param  User $user      User that modifies
	 *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
	 *  @return int                     <0 if KO, >0 if OK
	 */
	public function update($user = 0, $notrigger = 0)
	{
		global $conf, $langs, $hookmanager;
		$error = 0;

		// Clean parameters
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}

		if (isset($this->track_id)) {
			$this->track_id = trim($this->track_id);
		}

		if (isset($this->fk_soc)) {
			$this->fk_soc = (int) $this->fk_soc;
		}

		if (isset($this->fk_project)) {
			$this->fk_project = (int) $this->fk_project;
		}

		if (isset($this->origin_email)) {
			$this->origin_email = trim($this->origin_email);
		}

		if (isset($this->fk_user_create)) {
			$this->fk_user_create = (int) $this->fk_user_create;
		}

		if (isset($this->fk_user_assign)) {
			$this->fk_user_assign = (int) $this->fk_user_assign;
		}

		if (isset($this->subject)) {
			$this->subject = trim($this->subject);
		}

		if (isset($this->message)) {
			$this->message = trim($this->message);
		}

		if (isset($this->fk_statut)) {
			$this->fk_statut = (int) $this->fk_statut;
		}

		if (isset($this->resolution)) {
			$this->resolution = trim($this->resolution);
		}

		if (isset($this->progress)) {
			$this->progress = trim($this->progress);
		}

		if (isset($this->timing)) {
			$this->timing = trim($this->timing);
		}

		if (isset($this->type_code)) {
			$this->timing = trim($this->type_code);
		}

		if (isset($this->category_code)) {
			$this->timing = trim($this->category_code);
		}

		if (isset($this->severity_code)) {
			$this->timing = trim($this->severity_code);
		}

		// Check parameters
		// Put here code to add a control on parameters values
		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."ticket SET";
		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "").",";
		$sql .= " track_id=".(isset($this->track_id) ? "'".$this->db->escape($this->track_id)."'" : "null").",";
		$sql .= " fk_soc=".(isset($this->fk_soc) ? "'".$this->db->escape($this->fk_soc)."'" : "null").",";
		$sql .= " fk_project=".(isset($this->fk_project) ? "'".$this->db->escape($this->fk_project)."'" : "null").",";
		$sql .= " origin_email=".(isset($this->origin_email) ? "'".$this->db->escape($this->origin_email)."'" : "null").",";
		$sql .= " fk_user_create=".(isset($this->fk_user_create) ? $this->fk_user_create : "null").",";
		$sql .= " fk_user_assign=".(isset($this->fk_user_assign) ? $this->fk_user_assign : "null").",";
		$sql .= " subject=".(isset($this->subject) ? "'".$this->db->escape($this->subject)."'" : "null").",";
		$sql .= " message=".(isset($this->message) ? "'".$this->db->escape($this->message)."'" : "null").",";
		$sql .= " fk_statut=".(isset($this->fk_statut) ? $this->fk_statut : "null").",";
		$sql .= " resolution=".(isset($this->resolution) ? $this->resolution : "null").",";
		$sql .= " progress=".(isset($this->progress) ? "'".$this->db->escape($this->progress)."'" : "null").",";
		$sql .= " timing=".(isset($this->timing) ? "'".$this->db->escape($this->timing)."'" : "null").",";
		$sql .= " type_code=".(isset($this->type_code) ? "'".$this->db->escape($this->type_code)."'" : "null").",";
		$sql .= " category_code=".(isset($this->category_code) ? "'".$this->db->escape($this->category_code)."'" : "null").",";
		$sql .= " severity_code=".(isset($this->severity_code) ? "'".$this->db->escape($this->severity_code)."'" : "null").",";
		$sql .= " datec=".(dol_strlen($this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql .= " date_read=".(dol_strlen($this->date_read) != 0 ? "'".$this->db->idate($this->date_read)."'" : 'null').",";
		$sql .= " date_close=".(dol_strlen($this->date_close) != 0 ? "'".$this->db->idate($this->date_close)."'" : 'null')."";
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			// Update extrafields
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error++;
			}
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger('TICKET_MODIFY', $user);
			if ($result < 0) {
				$error++;
			}
			  // End call triggers
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
	 *  Delete object in database
	 *
	 *     @param  User $user      User that deletes
	 *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
	 *  @return int                     <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		$this->db->begin();

		if (!$error) {
			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('TICKET_DELETE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error) {
			// Delete linked contacts
			$res = $this->delete_linked_contact();
			if ($res < 0) {
				dol_syslog(get_class($this)."::delete error", LOG_ERR);
				$error++;
			}
		}

		if (!$error) {
			// Delete linked object
			$res = $this->deleteObjectLinked();
			if ($res < 0) {
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

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."ticket";
			$sql .= " WHERE rowid=".((int) $this->id);

			dol_syslog(get_class($this)."::delete sql=".$sql);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$error++;
				$this->errors[] = "Error ".$this->db->lasterror();
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
	 *     Load an object from its id and create a new one in database
	 *
	 *     @param   User    $user       User that clone
	 *     @param   int     $fromid     Id of object to clone
	 *     @return  int                 New id of clone
	 */
	public function createFromClone(User $user, $fromid)
	{
		$error = 0;

		$object = new Ticket($this->db);

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id = 0;
		$object->statut = 0;

		// Clear fields
		// ...
		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->create($user);

		// Other options
		if ($result < 0) {
			$this->error = $object->error;
			$error++;
		}

		if (!$error) {
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object->id;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *     Initialise object with example values
	 *     Id must be 0 if object instance is a specimen
	 *
	 *     @return int
	 */
	public function initAsSpecimen()
	{
		$this->id = 0;
		$this->entity = 1;
		$this->ref = 'TI0501-001';
		$this->track_id = 'XXXXaaaa';
		$this->origin_email = 'email@email.com';
		$this->fk_project = 1;
		$this->fk_user_create = 1;
		$this->fk_user_assign = 1;
		$this->subject = 'Subject of ticket';
		$this->message = 'Message of ticket';
		$this->status = 0;
		$this->resolution = '1';
		$this->progress = '10';
		//$this->timing = '30';
		$this->type_code = 'TYPECODE';
		$this->category_code = 'CATEGORYCODE';
		$this->severity_code = 'SEVERITYCODE';
		$this->datec = '';
		$this->date_read = '';
		$this->date_close = '';
		$this->tms = '';
		return 1;
	}

	/**
	 * Print selected status
	 *
	 * @param 	string    $selected   	Selected status
	 * @return 	void
	 */
	public function printSelectStatus($selected = "")
	{
		print Form::selectarray('search_fk_statut', $this->statuts_short, $selected, $show_empty = 1, $key_in_label = 0, $value_as_key = 0, $option = '', $translate = 1, $maxlen = 0, $disabled = 0, $sort = '', $morecss = '');
	}


	/**
	 * Load into a cache the types of tickets (setup done into dictionaries)
	 *
	 * @return 	int       Number of lines loaded, 0 if already loaded, <0 if KO
	 */
	public function loadCacheTypesTickets()
	{
		global $langs;

		if (!empty($this->cache_types_tickets) && count($this->cache_types_tickets)) {
			return 0;
		}
		// Cache deja charge

		$sql = "SELECT rowid, code, label, use_default, pos, description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_type";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY pos";
		dol_syslog(get_class($this)."::load_cache_type_tickets", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$label = ($langs->trans("TicketTypeShort".$obj->code) != ("TicketTypeShort".$obj->code) ? $langs->trans("TicketTypeShort".$obj->code) : ($obj->label != '-' ? $obj->label : ''));
				$this->cache_types_tickets[$obj->rowid]['code'] = $obj->code;
				$this->cache_types_tickets[$obj->rowid]['label'] = $label;
				$this->cache_types_tickets[$obj->rowid]['use_default'] = $obj->use_default;
				$this->cache_types_tickets[$obj->rowid]['pos'] = $obj->pos;
				$i++;
			}
			return $num;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *      Load into a cache array, the list of ticket categories (setup done into dictionary)
	 *
	 *      @return int             Number of lines loaded, 0 if already loaded, <0 if KO
	 */
	public function loadCacheCategoriesTickets()
	{
		global $conf, $langs;

		if (!empty($this->cache_category_ticket) && count($this->cache_category_tickets)) {
			// Cache already loaded
			return 0;
		}

		$sql = "SELECT rowid, code, label, use_default, pos, description, public, active, force_severity, fk_parent";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_category";
		$sql .= " WHERE active > 0 AND entity = ".((int) $conf->entity);
		$sql .= " ORDER BY pos";
		dol_syslog(get_class($this)."::load_cache_categories_tickets", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$this->cache_category_tickets[$obj->rowid]['code'] = $obj->code;
				$this->cache_category_tickets[$obj->rowid]['use_default'] = $obj->use_default;
				$this->cache_category_tickets[$obj->rowid]['pos'] = $obj->pos;
				$this->cache_category_tickets[$obj->rowid]['public'] = $obj->public;
				$this->cache_category_tickets[$obj->rowid]['active'] = $obj->active;
				$this->cache_category_tickets[$obj->rowid]['force_severity'] = $obj->force_severity;
				$this->cache_category_tickets[$obj->rowid]['fk_parent'] = $obj->fk_parent;

				// If  translation exists, we use it to store already translated string.
				// Warning: You should not use this and recompute the translated string into caller code to get the value into expected language
				$label = ($langs->trans("TicketCategoryShort".$obj->code) != ("TicketCategoryShort".$obj->code) ? $langs->trans("TicketCategoryShort".$obj->code) : ($obj->label != '-' ? $obj->label : ''));
				$this->cache_category_tickets[$obj->rowid]['label'] = $label;

				$i++;
			}
			return $num;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *      Charge dans cache la liste des sévérité de tickets (paramétrable dans dictionnaire)
	 *
	 *      @return int             Number of lines loaded, 0 if already loaded, <0 if KO
	 */
	public function loadCacheSeveritiesTickets()
	{
		global $langs;

		if (!empty($this->cache_severity_tickets) && count($this->cache_severity_tickets)) {
			return 0;
		}
		// Cache deja charge

		$sql = "SELECT rowid, code, label, use_default, pos, description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_severity";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY pos";
		dol_syslog(get_class($this)."::loadCacheSeveritiesTickets", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$this->cache_severity_tickets[$obj->rowid]['code'] = $obj->code;
				// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
				$label = ($langs->trans("TicketSeverityShort".$obj->code) != ("TicketSeverityShort".$obj->code) ? $langs->trans("TicketSeverityShort".$obj->code) : ($obj->label != '-' ? $obj->label : ''));
				$this->cache_severity_tickets[$obj->rowid]['label'] = $label;
				$this->cache_severity_tickets[$obj->rowid]['use_default'] = $obj->use_default;
				$this->cache_severity_tickets[$obj->rowid]['pos'] = $obj->pos;
				$i++;
			}
			return $num;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}


	/**
	 * Return status label of object
	 *
	 * @param      	int		$mode     	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return     	string    			Label
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->libStatut($this->fk_statut, $mode, 0, $this->progress);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *    Return status label of object
	 *
	 *    @param      string 	$status      Id status
	 *    @param      int		$mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 *    @param	  int		$notooltip	 1=No tooltip
	 *    @param	  int		$progress	 Progression (0 to 100)
	 *    @return     string     			 Label
	 */
	public function LibStatut($status, $mode = 0, $notooltip = 0, $progress = 0)
	{
		// phpcs:enable
		global $langs;

		$labelStatus = $this->statuts[$status];
		$labelStatusShort = $this->statuts_short[$status];

		if ($status == self::STATUS_NOT_READ) {
			$statusType = 'status0';
		} elseif ($status == self::STATUS_READ) {
			$statusType = 'status1';
		} elseif ($status == self::STATUS_ASSIGNED) {
			$statusType = 'status2';
		} elseif ($status == self::STATUS_IN_PROGRESS) {
			$statusType = 'status4';
		} elseif ($status == self::STATUS_WAITING) {
			$statusType = 'status7';
		} elseif ($status == self::STATUS_NEED_MORE_INFO) {
			$statusType = 'status3';
		} elseif ($status == self::STATUS_CANCELED) {
			$statusType = 'status9';
		} elseif ($status == self::STATUS_CLOSED) {
			$statusType = 'status6';
		} else {
			$labelStatus = 'Unknown';
			$labelStatusShort = 'Unknown';
			$statusType = 'status0';
			$mode = 0;
		}

		$params = array();
		if ($notooltip) {
			$params = array('tooltip' => 'no');
		}

		$labelStatus = $langs->transnoentitiesnoconv($labelStatus);
		$labelStatusShort = $langs->transnoentitiesnoconv($labelStatusShort);

		if ($status == self::STATUS_IN_PROGRESS && $progress > 0) {
			$labelStatus .= ' ('.round($progress).'%)';
			$labelStatusShort .= ' ('.round($progress).'%)';
		}

		return dolGetStatus($labelStatus, $labelStatusShort, '', $statusType, $mode, '', $params);
	}


	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 *	@param	int		$withpicto					Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 *	@param	string	$option						On what the link point to ('nolink', ...)
	 *  @param	int  	$notooltip					1=Disable tooltip
	 *  @param  string  $morecss            		Add more css on link
	 *  @param  int     $save_lastsearch_value    	-1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return	string								String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $db, $conf, $langs;
		global $dolibarr_main_authentication, $dolibarr_main_demo;
		global $menumanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Ticket").'</u>';
		$label .= ' '.$this->getLibStatut(4);
		$label .= '<br>';
		$label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref.'<br>';
		$label .= '<b>'.$langs->trans('TicketTrackId').':</b> '.$this->track_id.'<br>';
		$label .= '<b>'.$langs->trans('Subject').':</b> '.$this->subject;

		$url = DOL_URL_ROOT.'/ticket/card.php?id='.$this->id;

		if ($option != 'nolink') {
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
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowTicket");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
			$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		return $result;
	}


	/**
	 *    Mark a message as read
	 *
	 *    @param    User		$user			    Object user
	 *    @param	  int			$notrigger		No trigger
	 *    @return   int							      <0 if KO, >0 if OK
	 */
	public function markAsRead($user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		if ($this->statut != self::STATUS_CANCELED) { // no closed
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."ticket";
			$sql .= " SET fk_statut = ".Ticket::STATUS_READ.", date_read='".$this->db->idate(dol_now())."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::markAsRead");
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->actionmsg = $langs->trans('TicketLogMesgReadBy', $this->ref, $user->getFullName($langs));
				$this->actionmsg2 = $langs->trans('TicketLogMesgReadBy', $this->ref, $user->getFullName($langs));

				if (!$error && !$notrigger) {
					// Call trigger
					$result = $this->call_trigger('TICKET_MODIFY', $user);
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
					$this->error = join(',', $this->errors);
					dol_syslog(get_class($this)."::markAsRead ".$this->error, LOG_ERR);
					return -1;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				dol_syslog(get_class($this)."::markAsRead ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}

	/**
	 *    Mark a message as read
	 *
	 *    @param    User	$user				Object user
	 *    @param    int 	$id_assign_user		ID of user assigned
	 *    @param    int 	$notrigger        	Disable trigger
	 *    @return   int							<0 if KO, 0=Nothing done, >0 if OK
	 */
	public function assignUser($user, $id_assign_user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;
		$this->db->begin();

		$this->oldcopy = dol_clone($this);

		$sql = "UPDATE ".MAIN_DB_PREFIX."ticket";
		if ($id_assign_user > 0) {
			$sql .= " SET fk_user_assign=".((int) $id_assign_user).", fk_statut = ".Ticket::STATUS_ASSIGNED;
		} else {
			$sql .= " SET fk_user_assign=null, fk_statut = ".Ticket::STATUS_READ;
		}
		$sql .= " WHERE rowid = ".((int) $this->id);

		dol_syslog(get_class($this)."::assignUser sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->fk_user_assign = $id_assign_user; // May be used by trigger

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger('TICKET_ASSIGNED', $user);
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
				$this->error = join(',', $this->errors);
				dol_syslog(get_class($this)."::assignUser ".$this->error, LOG_ERR);
				return -1;
			}
		} else {
			$this->db->rollback();
			$this->error = $this->db->lasterror();
			dol_syslog(get_class($this)."::assignUser ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 *  Send notification of changes by email
	 *
	 * 	@param  User   $user    		User that create
	 * 	@param  string $message 		Log message
	 * 	@return int                 	<0 if KO, >0 if OK (number of emails sent)
	 */
	private function sendLogByEmail($user, $message)
	{
		global $conf, $langs;

		$nb_sent = 0;

		$langs->load('ticket');

		// Retrieve email of all contacts (internal and external)
		$contacts = $this->listeContact(-1, 'internal');
		$contacts = array_merge($contacts, $this->listeContact(-1, 'external'));

		/* If origin_email and no socid, we add email to the list * */
		if (!empty($this->origin_email) && empty($this->fk_soc)) {
			$array_ext = array(array('firstname' => '', 'lastname' => '', 'email' => $this->origin_email, 'libelle' => $langs->transnoentities('TicketEmailOriginIssuer'), 'socid' => "-1"));
			$contacts = array_merge($contacts, $array_ext);
		}

		if (!empty($this->fk_soc)) {
			$this->fetch_thirdparty($this->fk_soc);
			$array_company = array(array('firstname' => '', 'lastname' => $this->client->name, 'email' => $this->client->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $this->client->id));
			$contacts = array_merge($contacts, $array_company);
		}

		// foreach contact send email with notification message
		if (count($contacts) > 0) {
			foreach ($contacts as $key => $info_sendto) {
				$message = '';
				$subject = '['.$conf->global->MAIN_INFO_SOCIETE_NOM.'] '.$langs->transnoentities('TicketNotificationEmailSubject', $this->track_id);
				$message .= $langs->transnoentities('TicketNotificationEmailBody', $this->track_id)."\n\n";
				$message .= $langs->transnoentities('Title').' : '.$this->subject."\n";

				$recipient_name = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1');
				$recipient = (!empty($recipient_name) ? $recipient_name : $info_sendto['email']).' ('.strtolower($info_sendto['libelle']).')';
				$message .= $langs->transnoentities('TicketNotificationRecipient').' : '.$recipient."\n";
				$message .= "\n";
				$message .= '* '.$langs->transnoentities('TicketNotificationLogMessage').' *'."\n";
				$message .= dol_html_entity_decode($log_message, ENT_QUOTES | ENT_HTML5)."\n";

				if ($info_sendto['source'] == 'internal') {
					$url_internal_ticket = dol_buildpath('/ticket/card.php', 2).'?track_id='.$this->track_id;
					$message .= "\n".$langs->transnoentities('TicketNotificationEmailBodyInfosTrackUrlinternal').' : <a href="'.$url_internal_ticket.'">'.$this->track_id.'</a>'."\n";
				} else {
					$url_public_ticket = ($conf->global->TICKET_URL_PUBLIC_INTERFACE ? $conf->global->TICKET_URL_PUBLIC_INTERFACE.'/' : dol_buildpath('/public/ticket/view.php', 2)).'?track_id='.$this->track_id;
					$message .= "\n".$langs->transnoentities('TicketNewEmailBodyInfosTrackUrlCustomer').' : <a href="'.$url_public_ticket.'">'.$this->track_id.'</a>'."\n";
				}

				$message .= "\n";
				$message .= $langs->transnoentities('TicketEmailPleaseDoNotReplyToThisEmail')."\n";

				$from = $conf->global->MAIN_INFO_SOCIETE_NOM.'<'.$conf->global->TICKET_NOTIFICATION_EMAIL_FROM.'>';
				$replyto = $from;

				// Init to avoid errors
				$filepath = array();
				$filename = array();
				$mimetype = array();

				$message = dol_nl2br($message);

				if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
					$old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
					$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
				}
				include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
				$sendtocc = '';
				$deliveryreceipt = 0;
				$mailfile = new CMailFile($subject, $info_sendto['email'], $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, 0);
				if ($mailfile->error || $mailfile->errors) {
					setEventMessages($mailfile->error, $mailfile->errors, 'errors');
				} else {
					$result = $mailfile->sendfile();
					if ($result > 0) {
						$nb_sent++;
					}
				}
				if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
					$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
				}
			}

			setEventMessages($langs->trans('TicketNotificationNumberEmailSent', $nb_sent), null, 'mesgs');
		}

		return $nb_sent;
	}

	/**
	 *      Charge la liste des actions sur le ticket
	 *
	 *      @return int             Number of lines loaded, 0 if already loaded, <0 if KO
	 */
	public function loadCacheLogsTicket()
	{
		global $langs;

		if (is_array($this->cache_logs_ticket) && count($this->cache_logs_ticket)) {
			return 0;
		}
		// Cache deja charge

		// TODO Read the table llx_actioncomm
		/*
		$sql = "SELECT rowid, fk_user_create, datec, message";
		$sql .= " FROM " . MAIN_DB_PREFIX . "ticket_logs";
		$sql .= " WHERE fk_track_id ='" . $this->db->escape($this->track_id) . "'";
		$sql .= " ORDER BY datec DESC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$this->cache_logs_ticket[$i]['id'] = $obj->rowid;
				$this->cache_logs_ticket[$i]['fk_user_create'] = $obj->fk_user_create;
				$this->cache_logs_ticket[$i]['datec'] = $this->db->jdate($obj->datec);
				$this->cache_logs_ticket[$i]['message'] = $obj->message;
				$i++;
			}
			return $num;
		} else {
			$this->error = "Error " . $this->db->lasterror();
			dol_syslog(get_class($this) . "::loadCacheLogsTicket " . $this->error, LOG_ERR);
			return -1;
		}*/

		return 0;
	}

	/**
	 * Add message into database
	 *
	 * @param User 	 $user      		  User that creates
	 * @param int  	 $notrigger 		  0=launch triggers after, 1=disable triggers
	 * @param array	 $filename_list       List of files to attach (full path of filename on file system)
	 * @param array	 $mimetype_list       List of MIME type of attached files
	 * @param array	 $mimefilename_list   List of attached file name in message
	 * @return int						  <0 if KO, >0 if OK
	 */
	public function createTicketMessage($user, $notrigger = 0, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array())
	{
		global $conf, $langs;
		$error = 0;

		$now = dol_now();

		// Clean parameters
		if (isset($this->fk_track_id)) {
			$this->fk_track_id = trim($this->fk_track_id);
		}

		if (isset($this->message)) {
			$this->message = trim($this->message);
		}

		$this->db->begin();

		// Insert entry into agenda with code 'TICKET_MSG'
		include_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
		$actioncomm = new ActionComm($this->db);
		$actioncomm->type_code = 'AC_OTH';
		$actioncomm->code = 'TICKET_MSG';
		if ($this->private) {
			$actioncomm->code = 'TICKET_MSG_PRIVATE';
		}
		$actioncomm->socid = $this->socid;
		$actioncomm->label = $this->subject;
		$actioncomm->note_private = $this->message;
		$actioncomm->userassigned = array($user->id);
		$actioncomm->userownerid = $user->id;
		$actioncomm->datep = $now;
		$actioncomm->percentage = -1; // percentage is not relevant for punctual events
		$actioncomm->elementtype = 'ticket';
		$actioncomm->fk_element = $this->id;

		$attachedfiles = array();
		$attachedfiles['paths'] = $filename_list;
		$attachedfiles['names'] = $mimefilename_list;
		$attachedfiles['mimes'] = $mimetype_list;
		if (is_array($attachedfiles) && count($attachedfiles) > 0) {
			$actioncomm->attachedfiles = $attachedfiles;
		}

		if (!empty($mimefilename_list) && is_array($mimefilename_list)) {
			$actioncomm->note_private = dol_concatdesc($actioncomm->note_private, "\n".$langs->transnoentities("AttachedFiles").': '.join(';', $mimefilename_list));
		}

		$actionid = $actioncomm->create($user);
		if ($actionid <= 0) {
			$error++;
			$this->error = $actioncomm->error;
			$this->errors = $actioncomm->errors;
		}

		// Commit or rollback
		if ($error) {
			$this->db->rollback();
			return -1 * $error;
		} else {
			$this->db->commit();
			return 1;
		}
	}

	/**
	 *      Load the list of event on ticket into ->cache_msgs_ticket
	 *
	 *      @return int             Number of lines loaded, 0 if already loaded, <0 if KO
	 */
	public function loadCacheMsgsTicket()
	{
		if (is_array($this->cache_msgs_ticket) && count($this->cache_msgs_ticket)) {
			return 0;
		}

		// Cache already loaded

		$sql = "SELECT id as rowid, fk_user_author, datec, label, note as message, code";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
		$sql .= " WHERE fk_element = ".(int) $this->id;
		$sql .= " AND elementtype = 'ticket'";
		$sql .= " ORDER BY datec DESC";

		dol_syslog(get_class($this)."::load_cache_actions_ticket", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$this->cache_msgs_ticket[$i]['id'] = $obj->rowid;
				$this->cache_msgs_ticket[$i]['fk_user_author'] = $obj->fk_user_author;
				$this->cache_msgs_ticket[$i]['datec'] = $this->db->jdate($obj->datec);
				$this->cache_msgs_ticket[$i]['subject'] = $obj->label;
				$this->cache_msgs_ticket[$i]['message'] = $obj->message;
				$this->cache_msgs_ticket[$i]['private'] = ($obj->code == 'TICKET_MSG_PRIVATE' ? 1 : 0);
				$i++;
			}
			return $num;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::load_cache_actions_ticket ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *    Close a ticket
	 *
	 *    @param    User    $user      	User that close
	 *    @param	int		$mode		0=Close solved, 1=Close abandonned
	 *    @return   int		           	<0 if KO, >0 if OK
	 */
	public function close(User $user, $mode = 0)
	{
		global $conf, $langs;

		if ($this->fk_statut != Ticket::STATUS_CLOSED && $this->fk_statut != Ticket::STATUS_CANCELED) { // not closed
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."ticket";
			$sql .= " SET fk_statut=".($mode ? Ticket::STATUS_CANCELED : Ticket::STATUS_CLOSED).", progress=100, date_close='".$this->db->idate(dol_now())."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::close mode=".$mode);
			$resql = $this->db->query($sql);
			if ($resql) {
				$error = 0;

				// Valid and close fichinter linked
				if (!empty($conf->ficheinter->enabled) && !empty($conf->global->WORKFLOW_TICKET_CLOSE_INTERVENTION)) {
					dol_syslog("We have closed the ticket, so we close all linked interventions");
					$this->fetchObjectLinked($this->id, $this->element, null, 'fichinter');
					if ($this->linkedObjectsIds) {
						foreach ($this->linkedObjectsIds['fichinter'] as $fichinter_id) {
							$fichinter = new Fichinter($this->db);
							$fichinter->fetch($fichinter_id);
							if ($fichinter->statut == 0) {
								$result = $fichinter->setValid($user);
								if (!$result) {
									$this->errors[] = $fichinter->error;
									$error++;
								}
							}
							if ($fichinter->statut < 3) {
								$result = $fichinter->setStatut(3);
								if (!$result) {
									$this->errors[] = $fichinter->error;
									$error++;
								}
							}
						}
					}
				}

				// Call trigger
				$result = $this->call_trigger('TICKET_CLOSE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers

				if (!$error) {
					$this->db->commit();
					return 1;
				} else {
					$this->db->rollback();
					$this->error = join(',', $this->errors);
					dol_syslog(get_class($this)."::close ".$this->error, LOG_ERR);
					return -1;
				}
			} else {
				$this->db->rollback();
				$this->error = $this->db->lasterror();
				dol_syslog(get_class($this)."::close ".$this->error, LOG_ERR);
				return -1;
			}
		}
	}

	/**
	 *     Search and fetch thirparties by email
	 *
	 *     @param  string $email   		Email
	 *     @param  int    $type    		Type of thirdparties (0=any, 1=customer, 2=prospect, 3=supplier)
	 *     @param  array  $filters 		Array of couple field name/value to filter the companies with the same name
	 *     @param  string $clause  		Clause for filters
	 *     @return array        		Array of thirdparties object
	 */
	public function searchSocidByEmail($email, $type = '0', $filters = array(), $clause = 'AND')
	{
		$thirdparties = array();
		$exact = 0;

		// Generation requete recherche
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."societe";
		$sql .= " WHERE entity IN (".getEntity('ticket', 1).")";
		if (!empty($type)) {
			if ($type == 1 || $type == 2) {
				$sql .= " AND client = ".((int) $type);
			} elseif ($type == 3) {
				$sql .= " AND fournisseur = 1";
			}
		}
		if (!empty($email)) {
			if (empty($exact)) {
				$regs = array();
				if (preg_match('/^([\*])?[^*]+([\*])?$/', $email, $regs) && count($regs) > 1) {
					$email = str_replace('*', '%', $email);
				} else {
					$email = '%'.$email.'%';
				}
			}
			$sql .= " AND ";
			if (is_array($filters) && !empty($filters)) {
				$sql .= "(";
			}

			$sql .= "email LIKE '".$this->db->escape($email)."'";
		}
		if (is_array($filters) && !empty($filters)) {
			foreach ($filters as $field => $value) {
				$sql .= " ".$clause." ".$field." LIKE '".$this->db->escape($value)."'";
			}
			if (!empty($email)) {
				$sql .= ")";
			}
		}

		$res = $this->db->query($sql);
		if ($res) {
			while ($rec = $this->db->fetch_array($res)) {
				$soc = new Societe($this->db);
				$soc->fetch($rec['rowid']);
				$thirdparties[] = $soc;
			}

			return $thirdparties;
		} else {
			$this->error = $this->db->error().' sql='.$sql;
			dol_syslog(get_class($this)."::searchSocidByEmail ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *     Search and fetch contacts by email
	 *
	 *     @param  string $email 	Email
	 *     @param  array  $socid 	Limit to a thirdparty
	 *     @param  string $case  	Respect case
	 *     @return array        	Array of contacts object
	 */
	public function searchContactByEmail($email, $socid = '', $case = '')
	{
		$contacts = array();

		// Generation requete recherche
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople";
		$sql .= " WHERE entity IN (".getEntity('socpeople').")";
		if (!empty($socid)) {
			$sql .= " AND fk_soc='".$this->db->escape($socid)."'";
		}

		if (!empty($email)) {
			$sql .= " AND ";

			if (!$case) {
				$sql .= "email LIKE '".$this->db->escape($email)."'";
			} else {
				$sql .= "email LIKE BINARY '".$this->db->escape($email)."'";
			}
		}

		$res = $this->db->query($sql);
		if ($res) {
			while ($rec = $this->db->fetch_array($res)) {
				include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
				$contactstatic = new Contact($this->db);
				$contactstatic->fetch($rec['rowid']);
				$contacts[] = $contactstatic;
			}

			return $contacts;
		} else {
			$this->error = $this->db->error().' sql='.$sql;
			dol_syslog(get_class($this)."::searchContactByEmail ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *    Define parent commany of current ticket
	 *
	 *    @param  int $id Id of thirdparty to set or '' to remove
	 *    @return int             <0 if KO, >0 if OK
	 */
	public function setCustomer($id)
	{
		if ($this->id) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."ticket";
			$sql .= " SET fk_soc = ".($id > 0 ? $id : "null");
			$sql .= " WHERE rowid = ".((int) $this->id);
			dol_syslog(get_class($this).'::setCustomer sql='.$sql);
			$resql = $this->db->query($sql);
			if ($resql) {
				return 1;
			} else {
				return -1;
			}
		} else {
			return -1;
		}
	}

	/**
	 *    Define progression of current ticket
	 *
	 *    @param  int $percent Progression percent
	 *    @return int             <0 if KO, >0 if OK
	 */
	public function setProgression($percent)
	{
		if ($this->id) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."ticket";
			$sql .= " SET progress = ".($percent > 0 ? $percent : "null");
			$sql .= " WHERE rowid = ".((int) $this->id);
			dol_syslog(get_class($this).'::set_progression sql='.$sql);
			$resql = $this->db->query($sql);
			if ($resql) {
				return 1;
			} else {
				return -1;
			}
		} else {
			return -1;
		}
	}

	/**
	 *     Link element with a project
	 * 	   Override core function because of key name 'fk_project' used for this module
	 *
	 *     @param  int 		$projectid 			Project id to link element to
	 *     @return int                   	   <0 if KO, >0 if OK
	 */
	public function setProject($projectid)
	{
		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setProject was called on objet with property table_element not defined", LOG_ERR);
			return -1;
		}

		$sql = 'UPDATE '.MAIN_DB_PREFIX.$this->table_element;
		if ($projectid) {
			$sql .= ' SET fk_project = '.((int) $projectid);
		} else {
			$sql .= ' SET fk_project = NULL';
		}
		$sql .= ' WHERE rowid = '.((int) $this->id);

		dol_syslog(get_class($this)."::setProject sql=".$sql);
		if ($this->db->query($sql)) {
			$this->fk_project = ((int) $projectid);
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 *     Link element with a contract
	 *
	 *     @param  int $contractid Contract id to link element to
	 *     @return int                        <0 if KO, >0 if OK
	 */
	public function setContract($contractid)
	{
		if (!$this->table_element) {
			dol_syslog(get_class($this)."::setContract was called on objet with property table_element not defined", LOG_ERR);
			return -1;
		}

		$result = $this->add_object_linked('contrat', $contractid);
		if ($result) {
			$this->fk_contract = $contractid;
			return 1;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/* gestion des contacts d'un ticket */

	/**
	 *  Return id des contacts interne de suivi
	 *
	 *  @return array       Liste des id contacts suivi ticket
	 */
	public function getIdTicketInternalContact()
	{
		return $this->getIdContact('internal', 'SUPPORTTEC');
	}

	/**
	 * Retrieve informations about internal contacts
	 *
	 *  @return array       Array with datas : firstname, lastname, socid (-1 for internal users), email, code, libelle, status
	 */
	public function getInfosTicketInternalContact()
	{
		return $this->listeContact(-1, 'internal');
	}

	/**
	 *  Return id des contacts clients pour le suivi ticket
	 *
	 *  @return array       Liste des id contacts suivi ticket
	 */
	public function getIdTicketCustomerContact()
	{
		return $this->getIdContact('external', 'SUPPORTCLI');
	}

	/**
	 * Retrieve informations about external contacts
	 *
	 *  @return array       Array with datas : firstname, lastname, socid (-1 for internal users), email, code, libelle, status
	 */
	public function getInfosTicketExternalContact()
	{
		return $this->listeContact(-1, 'external');
	}

	/**
	 *  Return id des contacts clients des intervenants
	 *
	 *  @return array       Liste des id contacts intervenants
	 */
	public function getIdTicketInternalInvolvedContact()
	{
		return $this->getIdContact('internal', 'CONTRIBUTOR');
	}

	/**
	 *  Return id des contacts clients des intervenants
	 *
	 *  @return array       Liste des id contacts intervenants
	 */
	public function getIdTicketCustomerInvolvedContact()
	{
		return $this->getIdContact('external', 'CONTRIBUTOR');
	}

	/**
	 * Return id of all contacts for ticket
	 *
	 * @return	array		Array of contacts for tickets
	 */
	public function getTicketAllContacts()
	{
		$array_contact = array();

		$array_contact = $this->getIdTicketInternalContact($exclude_self);

		$array_contact = array_merge($array_contact, $this->getIdTicketCustomerContact($exclude_self));

		$array_contact = array_merge($array_contact, $this->getIdTicketInternalInvolvedContact($exclude_self));
		$array_contact = array_merge($array_contact, $this->getIdTicketCustomerInvolvedContact($exclude_self));

		return $array_contact;
	}

	/**
	 * Return id of all contacts for ticket
	 *
	 * @return	array		Array of contacts
	 */
	public function getTicketAllCustomerContacts()
	{
		$array_contact = array();

		$array_contact = array_merge($array_contact, $this->getIdTicketCustomerContact($exclude_self));
		$array_contact = array_merge($array_contact, $this->getIdTicketCustomerInvolvedContact($exclude_self));

		return $array_contact;
	}


	/**
	 *    Get array of all contacts for a ticket
	 *    Override method of file commonobject.class.php to add phone number
	 *
	 *    @param	int    	$status 	Status of lines to get (-1=all)
	 *    @param	string 	$source 	Source of contact: external or thirdparty (llx_socpeople) or internal (llx_user)
	 *    @param	int    	$list   	0:Return array contains all properties, 1:Return array contains just id
	 *    @param    string  $code       Filter on this code of contact type ('SHIPPING', 'BILLING', ...)
	 *    @return 	array          		Array of contacts
	 */
	public function listeContact($status = -1, $source = 'external', $list = 0, $code = '')
	{
		global $langs;

		$tab = array();

		$sql = "SELECT ec.rowid, ec.statut  as statuslink, ec.fk_socpeople as id, ec.fk_c_type_contact"; // This field contains id of llx_socpeople or id of llx_user
		if ($source == 'internal') {
			$sql .= ", '-1' as socid, t.statut as statuscontact";
		}

		if ($source == 'external' || $source == 'thirdparty') {
			$sql .= ", t.fk_soc as socid, t.statut as statuscontact";
		}

		$sql .= ", t.civility, t.lastname as lastname, t.firstname, t.email";
		if ($source == 'internal') {
			$sql .= ", t.office_phone as phone, t.user_mobile as phone_mobile";
		}

		if ($source == 'external') {
			$sql .= ", t.phone as phone, t.phone_mobile as phone_mobile, t.phone_perso as phone_perso";
		}

		$sql .= ", tc.source, tc.element, tc.code, tc.libelle as type_contact_label";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_contact tc";
		$sql .= ", ".MAIN_DB_PREFIX."element_contact ec";
		if ($source == 'internal') {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user t on ec.fk_socpeople = t.rowid";
		}

		if ($source == 'external' || $source == 'thirdparty') {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."socpeople t on ec.fk_socpeople = t.rowid";
		}

		$sql .= " WHERE ec.element_id = ".((int) $this->id);
		$sql .= " AND ec.fk_c_type_contact=tc.rowid";
		$sql .= " AND tc.element='".$this->db->escape($this->element)."'";
		if ($source == 'internal') {
			$sql .= " AND tc.source = 'internal'";
		}

		if ($source == 'external' || $source == 'thirdparty') {
			$sql .= " AND tc.source = 'external'";
		}

		$sql .= " AND tc.active=1";
		if ($status >= 0) {
			$sql .= " AND ec.statut = ".((int) $status);
		}

		$sql .= " ORDER BY t.lastname ASC";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				if (!$list) {
					$transkey = "TypeContact_".$obj->element."_".$obj->source."_".$obj->code;
					$libelle_type = ($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->type_contact_label);
					$tab[$i] = array(
							'source' => $obj->source,
							'socid' => $obj->socid,
							'id' => $obj->id,
							'nom' => $obj->lastname, // For backward compatibility
							'civility' => $obj->civility,
							'lastname' => $obj->lastname,
							'firstname' => $obj->firstname,
							'email' => $obj->email,
							'rowid' => $obj->rowid,
							'code' => $obj->code,
							'libelle' => $libelle_type,
							'status' => $obj->statuslink,
							'statuscontact'=>$obj->statuscontact,
							'fk_c_type_contact' => $obj->fk_c_type_contact,
							'phone' => $obj->phone,
							'phone_mobile' => $obj->phone_mobile);
				} else {
					$tab[$i] = $obj->id;
				}

				$i++;
			}

			return $tab;
		} else {
			$this->error = $this->db->error();
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * Get a default reference.
	 *
	 * @param	Societe		$thirdparty		Thirdparty
	 * @return 	string   					Reference
	 */
	public function getDefaultRef($thirdparty = '')
	{
		global $conf;

		$defaultref = '';
		$modele = empty($conf->global->TICKET_ADDON) ? 'mod_ticket_simple' : $conf->global->TICKET_ADDON;

		// Search template files
		$file = '';
		$classname = '';
		$filefound = 0;
		$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
		foreach ($dirmodels as $reldir) {
			$file = dol_buildpath($reldir."core/modules/ticket/".$modele.'.php', 0);
			if (file_exists($file)) {
				$filefound = 1;
				$classname = $modele;
				break;
			}
		}

		if ($filefound) {
			$result = dol_include_once($reldir."core/modules/ticket/".$modele.'.php');
			$modTicket = new $classname;

			$defaultref = $modTicket->getNextValue($thirdparty, $this);
		}

		if (is_numeric($defaultref) && $defaultref <= 0) {
			$defaultref = '';
		}

		return $defaultref;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return if at least one photo is available
	 *
	 *  @param      string      $sdir       Directory to scan
	 *  @return     boolean                 True if at least one photo is available, False if not
	 */
	public function is_photo_available($sdir)
	{
		// phpcs:enable
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		global $conf;

		$dir = $sdir.'/';
		$nbphoto = 0;

		$dir_osencoded = dol_osencode($dir);
		if (file_exists($dir_osencoded)) {
			$handle = opendir($dir_osencoded);
			if (is_resource($handle)) {
				while (($file = readdir($handle)) !== false) {
					if (!utf8_check($file)) {
						$file = utf8_encode($file);
					}
					// To be sure data is stored in UTF8 in memory
					if (dol_is_file($dir.$file)) {
						return true;
					}
				}
			}
		}
		return false;
	}


	/**
	 * Copy files defined into $_SESSION array into the ticket directory of attached files.
	 * Used for files linked into messages.
	 * Files may be renamed during copy to avoid overwriting existing files.
	 *
	 * @return	array		Array with final path/name/mime of files.
	 */
	public function copyFilesForTicket()
	{
		global $conf;

		// Create form object
		include_once DOL_DOCUMENT_ROOT.'/core/class/html.formmail.class.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
		include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';

		$maxwidthsmall = 270;
		$maxheightsmall = 150;
		$maxwidthmini = 128;
		$maxheightmini = 72;

		$formmail = new FormMail($this->db);

		$attachedfiles = $formmail->get_attached_files();

		$filepath = $attachedfiles['paths'];
		$filename = $attachedfiles['names'];
		$mimetype = $attachedfiles['mimes'];

		// Copy files into ticket directory
		$destdir = $conf->ticket->dir_output.'/'.$this->ref;

		if (!dol_is_dir($destdir)) {
			dol_mkdir($destdir);
		}

		$listofpaths = array();
		$listofnames = array();
		foreach ($filename as $i => $val) {
			$destfile = $destdir.'/'.$filename[$i];
			// If destination file already exists, we add a suffix to avoid to overwrite
			if (is_file($destfile)) {
				$pathinfo = pathinfo($filename[$i]);
				$now = dol_now();
				$destfile = $destdir.'/'.$pathinfo['filename'].' - '.dol_print_date($now, 'dayhourlog').'.'.$pathinfo['extension'];
			}

			$res = dol_move($filepath[$i], $destfile, 0, 1);

			if (image_format_supported($destfile) == 1) {
				// Create small thumbs for image (Ratio is near 16/9)
				// Used on logon for example
				$imgThumbSmall = vignette($destfile, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
				// Create mini thumbs for image (Ratio is near 16/9)
				// Used on menu or for setup page for example
				$imgThumbMini = vignette($destfile, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
			}

			$formmail->remove_attached_files($i);

			// Fill array with new names
			$listofpaths[$i] = $destfile;
			$listofnames[$i] = basename($destfile);
		}

		return array('listofpaths'=>$listofpaths, 'listofnames'=>$listofnames, 'listofmimes'=>$mimetype);
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param  int[]|int $categories Category or categories IDs
	 * @return void
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
		$existing = $c->containing($this->id, Categorie::TYPE_TICKET, 'id');

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
				$c->del_type($this, Categorie::TYPE_TICKET);
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0) {
				$c->add_type($this, Categorie::TYPE_TICKET);
			}
		}

		return;
	}

	/**
	 * Add new message on a ticket (private/public area). Can also send it be email if GETPOST('send_email', 'int') is set.
	 *
	 * @param   User    $user       User for action
	 * @param   string  $action     Action string
	 * @param   int     $private    1=Message is private. TODO Implement this. What does this means ?
	 * @param   int     $public_area    				1=Is the public area
	 * @return  int
	 */
	public function newMessage($user, &$action, $private = 1, $public_area = 0)
	{
		global $mysoc, $conf, $langs;

		$error = 0;

		$object = new Ticket($this->db);

		$ret = $object->fetch('', '', GETPOST('track_id', 'alpha'));

		$object->socid = $object->fk_soc;
		$object->fetch_thirdparty();

		if ($ret < 0) {
			$error++;
			array_push($this->errors, $langs->trans("ErrorTicketIsNotValid"));
			$action = '';
		}

		if (!GETPOST("message")) {
			$error++;
			array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("message")));
			$action = 'add_message';
		}

		if (!$error) {
			$object->subject = GETPOST('subject', 'alphanohtml');
			$object->message = GETPOST("message", "restricthtml");
			$object->private = GETPOST("private_message", "alpha");

			$send_email = GETPOST('send_email', 'int');

			// Copy attached files (saved into $_SESSION) as linked files to ticket. Return array with final name used.
			$resarray = $object->copyFilesForTicket();

			$listofpaths = $resarray['listofpaths'];
			$listofnames = $resarray['listofnames'];
			$listofmimes = $resarray['listofmimes'];

			$id = $object->createTicketMessage($user, 0, $listofpaths, $listofmimes, $listofnames);
			if ($id <= 0) {
				$error++;
				$this->error = $object->error;
				$this->errors = $object->errors;
				$action = 'add_message';
			}

			if (!$error && $id > 0) {
				setEventMessages($langs->trans('TicketMessageSuccessfullyAdded'), null, 'mesgs');

				//var_dump($_SESSION); var_dump($listofpaths);exit;

				/*
				 * Public area
				 */
				if (!empty($public_area)) {
					/*
					 * Send emails to assigned users (public area notification)
					 */
					if (!empty($conf->global->TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_ENABLED)) {
						$assigned_user_dont_have_email = '';
						$sendto = array();
						if ($this->fk_user_assign > 0) {
							$assigned_user = new User($this->db);
							$assigned_user->fetch($this->fk_user_assign);
							if (!empty($assigned_user->email)) {
								$sendto[] = $assigned_user->getFullName($langs)." <".$assigned_user->email.">";
							} else {
								$assigned_user_dont_have_email = $assigned_user->getFullName($langs);
							}
						}
						if (empty($sendto)) {
							if (!empty($conf->global->TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL)) {
								$sendto[] = $conf->global->TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL;
							} elseif (!empty($conf->global->TICKET_NOTIFICATION_EMAIL_TO)) {
								$sendto[] = $conf->global->TICKET_NOTIFICATION_EMAIL_TO;
							}
						}

						// Add global email address recipient
						if (!empty($conf->global->TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS) &&
							!empty($conf->global->TICKET_NOTIFICATION_EMAIL_TO) && !in_array($conf->global->TICKET_NOTIFICATION_EMAIL_TO, $sendto)
						) {
							$sendto[] = $conf->global->TICKET_NOTIFICATION_EMAIL_TO;
						}

						if (!empty($sendto)) {
							$label_title = empty($conf->global->MAIN_APPLICATION_TITLE) ? $mysoc->name : $conf->global->MAIN_APPLICATION_TITLE;
							$subject = '['.$label_title.'- ticket #'.$object->track_id.'] '.$langs->trans('TicketNewMessage');

							// Message send
							$message = $langs->trans('TicketMessageMailIntroText');
							$message .= '<br><br>';
							$messagePost = GETPOST('message', 'restricthtml');
							if (!dol_textishtml($messagePost)) {
								$messagePost = dol_nl2br($messagePost);
							}
							$message .= $messagePost;

							// Customer company infos
							$message .= '<br><br>';
							$message .= "==============================================";
							$message .= !empty($object->thirdparty->name) ? '<br>'.$langs->trans('Thirdparty')." : ".$object->thirdparty->name : '';
							$message .= !empty($object->thirdparty->town) ? '<br>'.$langs->trans('Town')." : ".$object->thirdparty->town : '';
							$message .= !empty($object->thirdparty->phone) ? '<br>'.$langs->trans('Phone')." : ".$object->thirdparty->phone : '';

							// Email send to
							$message .= '<br><br>';
							if (!empty($assigned_user_dont_have_email)) {
								$message .= '<br>'.$langs->trans('NoEMail').' : '.$assigned_user_dont_have_email;
							}
							foreach ($sendto as $val) {
								$message .= '<br>'.$langs->trans('TicketNotificationRecipient').' : '.$val;
							}

							// URL ticket
							$url_internal_ticket = dol_buildpath('/ticket/card.php', 2).'?track_id='.$object->track_id;
							$message .= '<br><br>';
							$message .= $langs->trans('TicketNotificationEmailBodyInfosTrackUrlinternal').' : <a href="'.$url_internal_ticket.'">'.$object->track_id.'</a>';

							$this->sendTicketMessageByEmail($subject, $message, '', $sendto, $listofpaths, $listofmimes, $listofnames);
						}
					}
				} else {
					/*
					 * Private area
					 */
					/*
					 * Send emails to internal users (linked contacts)
					 */
					if ($send_email > 0) {
						// Retrieve internal contact datas
						$internal_contacts = $object->getInfosTicketInternalContact();

						$sendto = array();
						if (is_array($internal_contacts) && count($internal_contacts) > 0) {
							// altairis: set default subject
							$label_title = empty($conf->global->MAIN_APPLICATION_TITLE) ? $mysoc->name : $conf->global->MAIN_APPLICATION_TITLE;
							$subject = GETPOST('subject', 'nohtml') ? GETPOST('subject', 'nohtml') : '['.$label_title.'- ticket #'.$object->track_id.'] '.$langs->trans('TicketNewMessage');

							$message_intro = $langs->trans('TicketNotificationEmailBody', "#".$object->id);
							$message_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE;

							$message = $langs->trans('TicketMessageMailIntroText');
							$message .= '<br><br>';
							$messagePost = GETPOST('message', 'restricthtml');
							if (!dol_textishtml($messagePost)) {
								$messagePost = dol_nl2br($messagePost);
							}
							$message .= $messagePost;

							//  Coordonnées client
							$message .= '<br><br>';
							$message .= "==============================================<br>";
							$message .= !empty($object->thirdparty->name) ? $langs->trans('Thirdparty')." : ".$object->thirdparty->name : '';
							$message .= !empty($object->thirdparty->town) ? '<br>'.$langs->trans('Town')." : ".$object->thirdparty->town : '';
							$message .= !empty($object->thirdparty->phone) ? '<br>'.$langs->trans('Phone')." : ".$object->thirdparty->phone : '';

							// Build array to display recipient list
							foreach ($internal_contacts as $key => $info_sendto) {
								// altairis: avoid duplicate notifications
								if ($info_sendto['id'] == $user->id) {
									continue;
								}

								if ($info_sendto['email'] != '') {
									if (!empty($info_sendto['email'])) {
										$sendto[] = trim($info_sendto['firstname']." ".$info_sendto['lastname'])." <".$info_sendto['email'].">";
									}

									//Contact type
									$recipient = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1').' ('.strtolower($info_sendto['libelle']).')';
									$message .= (!empty($recipient) ? $langs->trans('TicketNotificationRecipient').' : '.$recipient.'<br>' : '');
								}
							}
							$message .= '<br>';
							// URL ticket
							$url_internal_ticket = dol_buildpath('/ticket/card.php', 2).'?track_id='.$object->track_id;

							// altairis: make html link on url
							$message .= '<br>'.$langs->trans('TicketNotificationEmailBodyInfosTrackUrlinternal').' : <a href="'.$url_internal_ticket.'">'.$object->track_id.'</a><br>';

							// Add global email address recipient
							if ($conf->global->TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS && !in_array($conf->global->TICKET_NOTIFICATION_EMAIL_TO, $sendto)) {
								if (!empty($conf->global->TICKET_NOTIFICATION_EMAIL_TO)) {
									$sendto[] = $conf->global->TICKET_NOTIFICATION_EMAIL_TO;
								}
							}

							// altairis: dont try to send email if no recipient
							if (!empty($sendto)) {
								$this->sendTicketMessageByEmail($subject, $message, '', $sendto, $listofpaths, $listofmimes, $listofnames);
							}
						}

						/*
						 * Send emails for externals users if not private (linked contacts)
						 */
						if (empty($object->private)) {
							// Retrieve email of all contacts (external)
							$external_contacts = $object->getInfosTicketExternalContact();

							// If no contact, get email from thirdparty
							if (is_array($external_contacts) && count($external_contacts) === 0) {
								if (!empty($object->fk_soc)) {
									$object->fetch_thirdparty($object->fk_soc);
									$array_company = array(array('firstname' => '', 'lastname' => $object->thirdparty->name, 'email' => $object->thirdparty->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $object->thirdparty->id));
									$external_contacts = array_merge($external_contacts, $array_company);
								} elseif (empty($object->fk_soc) && !empty($object->origin_email)) {
									$array_external = array(array('firstname' => '', 'lastname' => $object->origin_email, 'email' => $object->thirdparty->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $object->thirdparty->id));
									$external_contacts = array_merge($external_contacts, $array_external);
								}
							}

							$sendto = array();
							if (is_array($external_contacts) && count($external_contacts) > 0) {
								// altairis: get default subject for email to external contacts
								$label_title = empty($conf->global->MAIN_APPLICATION_TITLE) ? $mysoc->name : $conf->global->MAIN_APPLICATION_TITLE;
								$subject = GETPOST('subject') ? GETPOST('subject') : '['.$label_title.'- ticket #'.$object->track_id.'] '.$langs->trans('TicketNewMessage');

								$message_intro = GETPOST('mail_intro') ? GETPOST('mail_intro', 'restricthtml') : $conf->global->TICKET_MESSAGE_MAIL_INTRO;
								$message_signature = GETPOST('mail_signature') ? GETPOST('mail_signature', 'restricthtml') : $conf->global->TICKET_MESSAGE_MAIL_SIGNATURE;
								if (!dol_textishtml($message_intro)) {
									$message_intro = dol_nl2br($message_intro);
								}
								if (!dol_textishtml($message_signature)) {
									$message_signature = dol_nl2br($message_signature);
								}

								// We put intro after
								$messagePost = GETPOST('message', 'restricthtml');
								if (!dol_textishtml($messagePost)) {
									$messagePost = dol_nl2br($messagePost);
								}
								$message = $messagePost;
								$message .= '<br><br>';

								foreach ($external_contacts as $key => $info_sendto) {
									// altairis: avoid duplicate emails to external contacts
									if ($info_sendto['id'] == $user->contact_id) {
										continue;
									}

									if ($info_sendto['email'] != '' && $info_sendto['email'] != $object->origin_email) {
										if (!empty($info_sendto['email'])) {
											$sendto[] = trim($info_sendto['firstname']." ".$info_sendto['lastname'])." <".$info_sendto['email'].">";
										}

										$recipient = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1').' ('.strtolower($info_sendto['libelle']).')';
										$message .= (!empty($recipient) ? $langs->trans('TicketNotificationRecipient').' : '.$recipient.'<br>' : '');
									}
								}

								// If public interface is not enable, use link to internal page into mail
								$url_public_ticket = (!empty($conf->global->TICKET_ENABLE_PUBLIC_INTERFACE) ?
										(!empty($conf->global->TICKET_URL_PUBLIC_INTERFACE) ? $conf->global->TICKET_URL_PUBLIC_INTERFACE.'/view.php' : dol_buildpath('/public/ticket/view.php', 2)) : dol_buildpath('/ticket/card.php', 2)).'?track_id='.$object->track_id;
								$message .= '<br>'.$langs->trans('TicketNewEmailBodyInfosTrackUrlCustomer').' : <a href="'.$url_public_ticket.'">'.$object->track_id.'</a><br>';

								// Build final message
								$message = $message_intro.'<br><br>'.$message;

								// Add signature
								$message .= '<br>'.$message_signature;

								if (!empty($object->origin_email)) {
									$sendto[] = $object->origin_email;
								}

								if ($object->fk_soc > 0 && !in_array($object->origin_email, $sendto)) {
									$object->socid = $object->fk_soc;
									$object->fetch_thirdparty();
									if (!empty($object->thirdparty->email)) {
										$sendto[] = $object->thirdparty->email;
									}
								}

								// altairis: Add global email address reciepient
								if ($conf->global->TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS && !in_array($conf->global->TICKET_NOTIFICATION_EMAIL_TO, $sendto)) {
									if (!empty($conf->global->TICKET_NOTIFICATION_EMAIL_TO)) {
										$sendto[] = $conf->global->TICKET_NOTIFICATION_EMAIL_TO;
									}
								}

								// altairis: dont try to send email when no recipient
								if (!empty($sendto)) {
									$this->sendTicketMessageByEmail($subject, $message, '', $sendto, $listofpaths, $listofmimes, $listofnames);
								}
							}
						}
					}
				}

				// Set status to "answered" if not set yet, but only if internal user
				if ($object->fk_statut < 3 && !$user->socid) {
					$object->setStatut(3);
				}

				return 1;
			} else {
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			}
		} else {
			setEventMessages($this->error, $this->errors, 'errors');
			return -1;
		}
	}


	/**
	 * Send ticket by email to linked contacts
	 *
	 * @param string $subject          	  Email subject
	 * @param string $message          	  Email message
	 * @param int    $send_internal_cc 	  Receive a copy on internal email ($conf->global->TICKET_NOTIFICATION_EMAIL_FROM)
	 * @param array  $array_receiver   	  Array of receiver. exemple array('name' => 'John Doe', 'email' => 'john@doe.com', etc...)
	 * @param array	 $filename_list       List of files to attach (full path of filename on file system)
	 * @param array	 $mimetype_list       List of MIME type of attached files
	 * @param array	 $mimefilename_list   List of attached file name in message
	 * @return void
	 */
	public function sendTicketMessageByEmail($subject, $message, $send_internal_cc = 0, $array_receiver = array(), $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array())
	{
		global $conf, $langs;

		if ($conf->global->TICKET_DISABLE_ALL_MAILS) {
			dol_syslog(get_class($this).'::sendTicketMessageByEmail: Emails are disable into ticket setup by option TICKET_DISABLE_ALL_MAILS', LOG_WARNING);
			return '';
		}

		$langs->load("mails");

		include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		//$contactstatic = new Contact($this->db);

		// If no receiver defined, load all ticket linked contacts
		if (!is_array($array_receiver) || !count($array_receiver) > 0) {
			$array_receiver = $this->getInfosTicketInternalContact();
			$array_receiver = array_merge($array_receiver, $this->getInfosTicketExternalContact());
		}

		if ($send_internal_cc) {
			$sendtocc = $conf->global->TICKET_NOTIFICATION_EMAIL_FROM;
		}

		$from = $conf->global->TICKET_NOTIFICATION_EMAIL_FROM;
		if (is_array($array_receiver) && count($array_receiver) > 0) {
			foreach ($array_receiver as $key => $receiver) {
				$deliveryreceipt = 0;
				$filepath = $filename_list;
				$filename = $mimefilename_list;
				$mimetype = $mimetype_list;

				// Envoi du mail
				if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
					$old_MAIN_MAIL_AUTOCOPY_TO = $conf->global->MAIN_MAIL_AUTOCOPY_TO;
					$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
				}
				include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
				$trackid = "tic".$this->id;
				$mailfile = new CMailFile($subject, $receiver, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1, '', '', $trackid, '', 'ticket');
				if ($mailfile->error) {
					setEventMessages($mailfile->error, null, 'errors');
				} else {
					$result = $mailfile->sendfile();
					if ($result) {
						setEventMessages($langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($receiver, 2)), null, 'mesgs');
					} else {
						$langs->load("other");
						if ($mailfile->error) {
							setEventMessages($langs->trans('ErrorFailedToSendMail', $from, $receiver), null, 'errors');
							dol_syslog($langs->trans('ErrorFailedToSendMail', $from, $receiver).' : '.$mailfile->error);
						} else {
							setEventMessages('No mail sent. Feature is disabled by option MAIN_DISABLE_ALL_MAILS', null, 'errors');
						}
					}
				}
				if (!empty($conf->global->TICKET_DISABLE_MAIL_AUTOCOPY_TO)) {
					$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
				}
			}
		} else {
			$langs->load("other");
			setEventMessages($langs->trans('ErrorMailRecipientIsEmptyForSendTicketMessage'), null, 'warnings');
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *      @param          User	$user   Object user
	 *      @param          int		$mode   "opened" for askprice to close, "signed" for proposal to invoice
	 *      @return         int             <0 if KO, >0 if OK
	 */
	public function load_board($user, $mode)
	{
		// phpcs:enable
		global $conf, $user, $langs;

		$now = dol_now();
		$delay_warning = 0;

		$this->nbtodo = $this->nbtodolate = 0;
		$clause = " WHERE";

		$sql = "SELECT p.rowid, p.ref, p.datec as datec";
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket as p";
		if ($conf->societe->enabled && empty($user->rights->societe->client->voir) && !$user->socid) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON p.fk_soc = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = " AND";
		}
		$sql .= $clause." p.entity IN (".getEntity('ticket').")";
		if ($mode == 'opened') {
			$sql .= " AND p.fk_statut NOT IN (".Ticket::STATUS_CLOSED.", ".Ticket::STATUS_CANCELED.")";
		}
		if ($user->socid) {
			$sql .= " AND p.fk_soc = ".((int) $user->socid);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$label = $labelShort = '';
			$status = '';
			if ($mode == 'opened') {
				$status = 'openall';
				//$delay_warning = $conf->ticket->warning_delay;
				$delay_warning = 0;
				$label = $langs->trans("MenuListNonClosed");
				$labelShort = $langs->trans("MenuListNonClosed");
			}

			$response = new WorkboardResponse();
			//$response->warning_delay = $delay_warning / 60 / 60 / 24;
			$response->label = $label;
			$response->labelShort = $labelShort;
			$response->url = DOL_URL_ROOT.'/ticket/list.php?search_fk_statut[]='.$status;
			$response->img = img_object('', "ticket");

			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				$response->nbtodo++;
				if ($mode == 'opened') {
					$datelimit = $this->db->jdate($obj->datec) + $delay_warning;
					if ($datelimit < $now) {
						//$response->nbtodolate++;
					}
				}
			}
			return $response;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *      Load indicator this->nb of global stats widget
	 *
	 *      @return     int         <0 if ko, >0 if ok
	 */
	public function load_state_board()
	{
		// phpcs:enable
		global $conf, $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(p.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		if (empty($user->rights->societe->client->voir) && !$user->socid) {
			$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON s.rowid = sc.fk_soc";
			$sql .= " WHERE sc.fk_user = ".((int) $user->id);
			$clause = "AND";
		}
		$sql .= " ".$clause." p.entity IN (".getEntity('ticket').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			// This assignment in condition is not a bug. It allows walking the results.
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["ticket"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB 	$db 			Database handler
	 * @param int 		$origin_id 		Old thirdparty id
	 * @param int 		$dest_id 		New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty($db, $origin_id, $dest_id)
	{
		$tables = array('ticket');

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}
}


/**
 * Ticket line Class
 */
class TicketsLine
{
	/**
	 * @var int ID
	 * @deprecated
	 */
	public $rowid;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var string  $ref    Ticket reference
	 */
	public $ref;

	/**
	 * Hash to identify ticket
	 */
	public $track_id;

	/**
	 * @var int Thirdparty ID
	 */
	public $fk_soc;

	/**
	 * Project ID
	 */
	public $fk_project;

	/**
	 * Person email who have create ticket
	 */
	public $origin_email;

	/**
	 * User id who have create ticket
	 */
	public $fk_user_create;

	/**
	 * User id who have ticket assigned
	 */
	public $fk_user_assign;

	/**
	 * Ticket subject
	 */
	public $subject;

	/**
	 * Ticket message
	 */
	public $message;

	/**
	 * Ticket statut
	 */
	public $fk_statut;

	/**
	 * State resolution
	 */
	public $resolution;

	/**
	 * Progress in percent
	 */
	public $progress;

	/**
	 * Duration for ticket
	 */
	public $timing;

	/**
	 * Type code
	 */
	public $type_code;

	/**
	 * Category code
	 */
	public $category_code;

	/**
	 * Severity code
	 */
	public $severity_code;

	/**
	 * Type label
	 */
	public $type_label;

	/**
	 * Category label
	 */
	public $category_label;

	/**
	 * Severity label
	 */
	public $severity_label;

	/**
	 * Creation date
	 */
	public $datec = '';

	/**
	 * Read date
	 */
	public $date_read = '';

	/**
	 * Close ticket date
	 */
	public $date_close = '';
}
