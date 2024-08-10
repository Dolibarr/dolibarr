<?php
/* Copyright (C) 2013-2018 Jean-François Ferry <hello@librethic.io>
 * Copyright (C) 2016      Christophe Battarel <christophe@altairis.fr>
 * Copyright (C) 2019-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2020      Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2023      Charlene Benke 	   <charlene@patas-monkey.com>
 * Copyright (C) 2023	   Benjamin Falière	   <benjamin.faliere@altairis.fr>
 * Copyright (C) 2024		William Mead		<william.mead@manchenumerique.fr>
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
	 * @var DoliDB Database handler
	 */
	public $db;

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
	 * @var string String with name of icon for ticketcore. Must be the part after the 'object_' into object_ticketcore.png
	 */
	public $picto = 'ticket';

	/**
	 * @var ?string Hash to identify ticket publicly
	 */
	public $track_id;

	/**
	 * @var int Thirdparty ID
	 */
	public $fk_soc;
	public $socid;

	/**
	 * @var int Project ID
	 */
	public $fk_project;

	/**
	 * @var int Contract ID
	 */
	public $fk_contract;

	/**
	 * @var ?string Email of person who created the ticket
	 */
	public $origin_email;

	/**
	 * @var int User id who created the ticket
	 */
	public $fk_user_create;

	/**
	 * @var int User id who the ticket is assigned to
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
	 * @var string Private message
	 */
	public $private;

	/**
	 * @var int  Ticket statut
	 * @deprecated use status
	 * @see $status
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
	 * Email to reply to
	 */
	public $origin_replyto;

	/**
	 * References from origin email
	 */
	public $origin_references;

	/**
	 * @var int Creation date
	 */
	public $datec;

	/**
	 * @var int Read date
	 */
	public $date_read;

	/**
	 * @var int Last message date
	 */
	public $date_last_msg_sent;

	/**
	 * @var int Close ticket date
	 */
	public $date_close;

	/**
	 * @var array cache_types_tickets
	 */
	public $cache_types_tickets;

	/**
	 * @var array tickets categories
	 */
	public $cache_category_tickets;

	/**
	 * @var array cache msgs ticket
	 */
	public $cache_msgs_ticket;

	/**
	 * @var int 	Notify thirdparty at create
	 */
	public $notify_tiers_at_create;

	/**
	 * @var string 	Email MSGID
	 */
	public $email_msgid;

	/**
	 * @var string 	Email Date
	 */
	public $email_date;

	/**
	 * @var string 	IP address
	 */
	public $ip;

	/**
	 * @var static $oldcopy  State of this ticket as it was stored before an update operation (for triggers)
	 */
	public $oldcopy;

	/**
	 * @var Ticket[] array of Tickets
	 */
	public $lines;


	/**
	 * @var string Regex pour les images
	 */
	public $regeximgext = '\.gif|\.jpg|\.jpeg|\.png|\.bmp|\.webp|\.xpm|\.xbm'; // See also into images.lib.php

	/**
	 * Status
	 */
	const STATUS_NOT_READ = 0;			// Draft. Not take into account yet.
	const STATUS_READ = 1;				// Ticket was read.
	const STATUS_ASSIGNED = 2;			// Ticket was just assigned to someone. Not in progress yet.
	const STATUS_IN_PROGRESS = 3;		// In progress
	const STATUS_NEED_MORE_INFO = 5;	// Waiting requester feedback
	const STATUS_WAITING = 7;			// On hold
	const STATUS_CLOSED = 8;			// Closed - Solved
	const STATUS_CANCELED = 9;			// Closed - Not solved


	/**
	 *  'type' field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'sellist:TableName:LabelFieldName[:KeyFieldName[:KeyFieldParent[:Filter]]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'text:none', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'picto' is code of a picto to show before value in forms
	 *  'enabled' is a condition when the field must be managed (Example: 1 or 'getDolGlobalString('MY_SETUP_PARAM'))
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwrote by the Setup of Default Values if field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
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
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'visible' => -2, 'enabled' => 1, 'position' => 1, 'notnull' => 1, 'index' => 1, 'comment' => "Id"),
		'entity' => array('type' => 'integer', 'label' => 'Entity', 'visible' => 0, 'enabled' => 1, 'position' => 5, 'notnull' => 1, 'index' => 1),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Ref', 'visible' => 1, 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'index' => 1, 'searchall' => 1, 'comment' => "Reference of object", 'css' => '', 'showoncombobox' => 1),
		'track_id' => array('type' => 'varchar(255)', 'label' => 'TicketTrackId', 'visible' => -2, 'enabled' => 1, 'position' => 11, 'notnull' => -1, 'searchall' => 1, 'help' => "Help text"),
		'fk_user_create' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'Author', 'visible' => 1, 'enabled' => 1, 'position' => 15, 'notnull' => 1, 'csslist' => 'tdoverflowmax100 maxwidth150onsmartphone'),
		'origin_email' => array('type' => 'mail', 'label' => 'OriginEmail', 'visible' => -2, 'enabled' => 1, 'position' => 16, 'notnull' => 1, 'index' => 1, 'searchall' => 1, 'comment' => "Reference of object", 'csslist' => 'tdoverflowmax150'),
		'origin_replyto' => array('type' => 'mail', 'label' => 'EmailReplyto', 'visible' => -2, 'enabled' => 1, 'position' => 17, 'notnull' => 1, 'index' => 1, 'searchall' => 1, 'comment' => "Email to reply to", 'csslist' => 'tdoverflowmax150'),
		'origin_references' => array('type' => 'text', 'label' => 'EmailReferences', 'visible' => -2, 'enabled' => 1, 'position' => 18, 'notnull' => 1, 'index' => 1, 'searchall' => 1, 'comment' => "References from origin email", 'csslist' => 'tdoverflowmax150'),
		'subject' => array('type' => 'varchar(255)', 'label' => 'Subject', 'visible' => 1, 'enabled' => 1, 'position' => 19, 'notnull' => -1, 'searchall' => 1, 'help' => "", 'css' => 'maxwidth200 tdoverflowmax200', 'csslist'=>'tdoverflowmax250', 'autofocusoncreate' => 1),
		'type_code' => array('type' => 'varchar(32)', 'label' => 'Type', 'visible' => 1, 'enabled' => 1, 'position' => 20, 'notnull' => -1, 'help' => "", 'csslist' => 'tdoverflowmax100'),
		'category_code' => array('type' => 'varchar(32)', 'label' => 'TicketCategory', 'visible' => -1, 'enabled' => 1, 'position' => 21, 'notnull' => -1, 'help' => "", 'css' => 'maxwidth100 tdoverflowmax200'),
		'severity_code' => array('type' => 'varchar(32)', 'label' => 'Severity', 'visible' => 1, 'enabled' => 1, 'position' => 22, 'notnull' => -1, 'help' => "", 'css' => 'maxwidth100'),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php', 'label' => 'ThirdParty', 'visible' => 1, 'enabled' => 'isModEnabled("societe")', 'position' => 50, 'notnull' => -1, 'index' => 1, 'searchall' => 1, 'help' => "OrganizationEventLinkToThirdParty", 'css' => 'tdoverflowmax150 maxwidth150onsmartphone'),
		'notify_tiers_at_create' => array('type' => 'integer', 'label' => 'NotifyThirdparty', 'visible' => -1, 'enabled' => 0, 'position' => 51, 'notnull' => 1, 'index' => 1),
		'fk_project' => array('type' => 'integer:Project:projet/class/project.class.php', 'label' => 'Project', 'visible' => -1, 'enabled' => '$conf->project->enabled', 'position' => 52, 'notnull' => -1, 'index' => 1, 'help' => "LinkToProject"),
		'fk_contract' => array('type' => 'integer:Contrat:contrat/class/contrat.class.php', 'label' => 'Contract', 'visible' => -1, 'enabled' => '$conf->contract->enabled', 'position' => 53, 'notnull' => -1, 'index' => 1, 'help' => "LinkToContract"),
		//'timing' => array('type'=>'varchar(20)', 'label'=>'Timing', 'visible'=>-1, 'enabled'=>1, 'position'=>42, 'notnull'=>-1, 'help'=>""),	// what is this ?
		'datec' => array('type' => 'datetime', 'label' => 'DateCreation', 'visible' => 1, 'enabled' => 1, 'position' => 500, 'notnull' => 1, 'csslist' => 'nowraponall'),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'visible' => -1, 'enabled' => 1, 'position' => 501, 'notnull' => 1),
		'date_read' => array('type' => 'datetime', 'label' => 'DateReading', 'visible' => -1, 'enabled' => 1, 'position' => 505, 'notnull' => 1, 'csslist' => 'nowraponall'),
		'date_last_msg_sent' => array('type' => 'datetime', 'label' => 'TicketLastMessageDate', 'visible' => 0, 'enabled' => 1, 'position' => 506, 'notnull' => -1),
		'fk_user_assign' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'AssignedTo', 'visible' => 1, 'enabled' => 1, 'position' => 507, 'notnull' => 1, 'csslist' => 'tdoverflowmax100 maxwidth150onsmartphone'),
		'date_close' => array('type' => 'datetime', 'label' => 'TicketCloseOn', 'visible' => -1, 'enabled' => 1, 'position' => 510, 'notnull' => 1),
		'message' => array('type' => 'html', 'label' => 'Message', 'visible' => -2, 'enabled' => 1, 'position' => 540, 'notnull' => -1,),
		'email_msgid' => array('type' => 'varchar(255)', 'label' => 'EmailMsgID', 'visible' => -2, 'enabled' => 1, 'position' => 540, 'notnull' => -1, 'help' => 'EmailMsgIDDesc', 'csslist' => 'tdoverflowmax100'),
		'email_date' => array('type' => 'datetime', 'label' => 'EmailDate', 'visible' => -2, 'enabled' => 1, 'position' => 541),
		'progress' => array('type' => 'integer', 'label' => 'Progression', 'visible' => -1, 'enabled' => 1, 'position' => 540, 'notnull' => -1, 'css' => 'right', 'help' => "", 'isameasure' => 2, 'csslist' => 'width50'),
		'resolution' => array('type' => 'integer', 'label' => 'Resolution', 'visible' => -1, 'enabled' => 'getDolGlobalString("TICKET_ENABLE_RESOLUTION")', 'position' => 550, 'notnull' => 1),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'PDFTemplate', 'enabled' => 1, 'visible' => 0, 'position' => 560),
		'extraparams' => array('type' => 'varchar(255)', 'label' => 'Extraparams', 'enabled' => 1, 'visible' => -1, 'position' => 570),
		'fk_statut' => array('type' => 'integer', 'label' => 'Status', 'visible' => 1, 'enabled' => 1, 'position' => 600, 'notnull' => 1, 'index' => 1, 'arrayofkeyval' => array(0 => 'Unread', 1 => 'Read', 2 => 'Assigned', 3 => 'InProgress', 5 => 'NeedMoreInformation', 7 => 'OnHold', 8 => 'SolvedClosed', 9 => 'Deleted')),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => 1, 'visible' => -2, 'position' => 900),
	);
	// END MODULEBUILDER PROPERTIES


	/**
	 *  Constructor
	 *
	 *  @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;

		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;

		$this->labelStatusShort = array(
			self::STATUS_NOT_READ => 'Unread',
			self::STATUS_READ => 'Read',
			self::STATUS_ASSIGNED => 'Assigned',
			self::STATUS_IN_PROGRESS => 'InProgress',
			self::STATUS_NEED_MORE_INFO => 'NeedMoreInformationShort',
			self::STATUS_WAITING => 'OnHold',
			self::STATUS_CLOSED => 'SolvedClosed',
			self::STATUS_CANCELED => 'Canceled'
		);
		$this->labelStatus = array(
			self::STATUS_NOT_READ => 'Unread',
			self::STATUS_READ => 'Read',
			self::STATUS_ASSIGNED => 'Assigned',
			self::STATUS_IN_PROGRESS => 'InProgress',
			self::STATUS_NEED_MORE_INFO => 'NeedMoreInformation',
			self::STATUS_WAITING => 'OnHold',
			self::STATUS_CLOSED => 'SolvedClosed',
			self::STATUS_CANCELED => 'Canceled'
		);

		if (!getDolGlobalString('TICKET_INCLUDE_SUSPENDED_STATUS')) {
			unset($this->fields['fk_statut']['arrayofkeyval'][self::STATUS_WAITING]);
			unset($this->labelStatusShort[self::STATUS_WAITING]);
			unset($this->labelStatus[self::STATUS_WAITING]);
		}
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
			if (dol_strlen($this->message) > 65000) {
				$this->errors[] = 'ErrorFieldTooLong';
				dol_syslog(get_class($this).'::create error -1 message too long', LOG_ERR);
				$result = -1;
			}
		}

		if (isset($this->fk_statut)) {
			$this->fk_statut = (int) $this->fk_statut;
		}

		if (isset($this->resolution)) {
			$this->resolution = trim($this->resolution);
		}

		if (isset($this->progress)) {
			$this->progress = (int) $this->progress;
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
	 *
	 * Check if ref exists or not
	 *
	 * @param string $action    Action
	 * @param string $getRef    Reference of object
	 * @return bool
	 */
	public function checkExistingRef(string $action, string $getRef): bool
	{
		$test = new self($this->db);

		if ($test->fetch('', $getRef) > 0) {
			if (($action == 'add') || ($action == 'update' && $this->ref != $getRef)) {
				return true;
			}
		}

		$this->ref = $getRef;
		return false;
	}

	/**
	 *  Create object into database
	 *
	 *  @param  User $user      User that creates
	 *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
	 *  @return int                      Return integer <0 if KO, Id of created object if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;

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
			$this->entity = ((isset($this->entity) && is_numeric($this->entity)) ? $this->entity : $conf->entity);

			// Insert request
			$sql = "INSERT INTO ".MAIN_DB_PREFIX."ticket(";
			$sql .= "ref,";
			$sql .= "track_id,";
			$sql .= "fk_soc,";
			$sql .= "fk_project,";
			$sql .= "fk_contract,";
			$sql .= "origin_email,";
			$sql .= "origin_replyto,";
			$sql .= "origin_references,";
			$sql .= "fk_user_create,";
			$sql .= "fk_user_assign,";
			$sql .= "email_msgid,";
			$sql .= "email_date,";
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
			$sql .= "notify_tiers_at_create,";
			$sql .= "model_pdf,";
			$sql .= "ip";
			$sql .= ") VALUES (";
			$sql .= " ".(!isset($this->ref) ? '' : "'".$this->db->escape($this->ref)."'").",";
			$sql .= " ".(!isset($this->track_id) ? 'NULL' : "'".$this->db->escape($this->track_id)."'").",";
			$sql .= " ".($this->fk_soc > 0 ? ((int) $this->fk_soc) : "null").",";
			$sql .= " ".($this->fk_project > 0 ? ((int) $this->fk_project) : "null").",";
			$sql .= " ".($this->fk_contract > 0 ? ((int) $this->fk_contract) : "null").",";
			$sql .= " ".(!isset($this->origin_email) ? 'NULL' : "'".$this->db->escape($this->origin_email)."'").",";
			$sql .= " ".(!isset($this->origin_replyto) ? 'NULL' : "'".$this->db->escape($this->origin_replyto)."'").",";
			$sql .= " ".(!isset($this->origin_references) ? 'NULL' : "'".$this->db->escape($this->origin_references)."'").",";
			$sql .= " ".(!isset($this->fk_user_create) ? ($user->id > 0 ? ((int) $user->id) : 'NULL') : ($this->fk_user_create > 0 ? ((int) $this->fk_user_create) : 'NULL')).",";
			$sql .= " ".($this->fk_user_assign > 0 ? ((int) $this->fk_user_assign) : 'NULL').",";
			$sql .= " ".(empty($this->email_msgid) ? 'NULL' : "'".$this->db->escape($this->email_msgid)."'").",";
			$sql .= " ".(empty($this->email_date) ? 'NULL' : "'".$this->db->idate($this->email_date)."'").",";
			$sql .= " ".(!isset($this->subject) ? 'NULL' : "'".$this->db->escape($this->subject)."'").",";
			$sql .= " ".(!isset($this->message) ? 'NULL' : "'".$this->db->escape($this->message)."'").",";
			$sql .= " ".(!isset($this->status) ? '0' : ((int) $this->status)).",";
			$sql .= " ".(!isset($this->resolution) ? 'NULL' : ((int) $this->resolution)).",";
			$sql .= " ".(!isset($this->progress) ? '0' : ((int) $this->progress)).",";
			$sql .= " ".(!isset($this->timing) ? 'NULL' : "'".$this->db->escape($this->timing)."'").",";
			$sql .= " ".(!isset($this->type_code) ? 'NULL' : "'".$this->db->escape($this->type_code)."'").",";
			$sql .= " ".(empty($this->category_code) || $this->category_code == '-1' ? 'NULL' : "'".$this->db->escape($this->category_code)."'").",";
			$sql .= " ".(!isset($this->severity_code) ? 'NULL' : "'".$this->db->escape($this->severity_code)."'").",";
			$sql .= " ".(!isset($this->datec) || dol_strlen($this->datec) == 0 ? 'NULL' : "'".$this->db->idate($this->datec)."'").",";
			$sql .= " ".(!isset($this->date_read) || dol_strlen($this->date_read) == 0 ? 'NULL' : "'".$this->db->idate($this->date_read)."'").",";
			$sql .= " ".(!isset($this->date_close) || dol_strlen($this->date_close) == 0 ? 'NULL' : "'".$this->db->idate($this->date_close)."'");
			$sql .= ", ".((int) $this->entity);
			$sql .= ", ".(!isset($this->notify_tiers_at_create) ? 1 : ((int) $this->notify_tiers_at_create));
			$sql .= ", '".$this->db->escape($this->model_pdf)."'";
			$sql .= ", ".(!isset($this->ip) ? 'NULL' : "'".$this->db->escape($this->ip)."'");
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
			}

			if (!$error && getDolGlobalString('TICKET_ADD_AUTHOR_AS_CONTACT') && empty($this->context["createdfrompublicinterface"])) {
				// add creator as contributor

				// We first check the type of contact (internal or external)
				if (!empty($user->socid) && !empty($user->contact_id) && getDolGlobalInt('TICKET_ADD_AUTHOR_AS_CONTACT') == 2) {
					$contact_type = 'external';
					$contributor_id = $user->contact_id;
				} else {
					$contact_type = 'internal';
					$contributor_id = $user->id;
				}

				// We add the creator as contributor
				if ($this->add_contact($contributor_id, 'CONTRIBUTOR', $contact_type) < 0) {
					$error++;
				}
			}

			if (!$error && $this->fk_user_assign > 0) {
				if ($this->add_contact($this->fk_user_assign, 'SUPPORTTEC', 'internal') < 0) {
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

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('TICKET_CREATE', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
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
			dol_syslog(get_class($this)."::Create fails verify ".implode(',', $this->errors), LOG_WARNING);
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
	 *  @return int              			Return integer <0 if KO, >0 if OK
	 */
	public function fetch($id = 0, $ref = '', $track_id = '', $email_msgid = '')
	{
		global $langs;

		// Check parameters
		if (empty($id) && empty($ref) && empty($track_id) && empty($email_msgid)) {
			$this->error = 'ErrorWrongParameters';
			dol_print_error(null, get_class($this)."::fetch ".$this->error);
			return -1;
		}

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.ref,";
		$sql .= " t.track_id,";
		$sql .= " t.fk_soc,";
		$sql .= " t.fk_project,";
		$sql .= " t.fk_contract,";
		$sql .= " t.origin_email,";
		$sql .= " t.origin_replyto,";
		$sql .= " t.origin_references,";
		$sql .= " t.fk_user_create,";
		$sql .= " t.fk_user_assign,";
		$sql .= " t.email_msgid,";
		$sql .= " t.email_date,";
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
		$sql .= " t.date_last_msg_sent,";
		$sql .= " t.date_close,";
		$sql .= " t.tms,";
		$sql .= " t.model_pdf,";
		$sql .= " t.extraparams,";
		$sql .= " t.ip,";
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
				$this->fk_contract = $obj->fk_contract;
				$this->origin_email = $obj->origin_email;
				$this->origin_replyto = $obj->origin_replyto;
				$this->origin_references = $obj->origin_references;
				$this->fk_user_create = $obj->fk_user_create;
				$this->fk_user_assign = $obj->fk_user_assign;
				$this->email_msgid = $obj->email_msgid;
				$this->email_date = $this->db->jdate($obj->email_date);
				$this->subject = $obj->subject;
				$this->message = $obj->message;
				$this->model_pdf = $obj->model_pdf;
				$this->extraparams = !empty($obj->extraparams) ? (array) json_decode($obj->extraparams, true) : array();
				$this->ip = $obj->ip;

				$this->status = $obj->status;
				$this->fk_statut = $this->status; // For backward compatibility

				$this->resolution = $obj->resolution;
				$this->progress = $obj->progress;
				$this->timing = $obj->timing;

				$this->type_code = $obj->type_code;
				$label_type = ($langs->trans("TicketTypeShort".$obj->type_code) != "TicketTypeShort".$obj->type_code ? $langs->trans("TicketTypeShort".$obj->type_code) : ($obj->type_label != '-' ? $obj->type_label : ''));
				$this->type_label = $label_type;

				$this->category_code = $obj->category_code;
				$label_category = ($langs->trans("TicketCategoryShort".$obj->category_code) != "TicketCategoryShort".$obj->category_code ? $langs->trans("TicketCategoryShort".$obj->category_code) : ($obj->category_label != '-' ? $obj->category_label : ''));
				$this->category_label = $label_category;

				$this->severity_code = $obj->severity_code;
				$label_severity = ($langs->trans("TicketSeverityShort".$obj->severity_code) != "TicketSeverityShort".$obj->severity_code ? $langs->trans("TicketSeverityShort".$obj->severity_code) : ($obj->severity_label != '-' ? $obj->severity_label : ''));
				$this->severity_label = $label_severity;

				$this->datec = $this->db->jdate($obj->datec);
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_read = $this->db->jdate($obj->date_read);
				$this->date_validation = $this->db->jdate($obj->date_read);
				$this->date_last_msg_sent = $this->db->jdate($obj->date_last_msg_sent);
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
	 * @param  User   		$user      	User for action
	 * @param  string 		$sortorder 	Sort order
	 * @param  string 		$sortfield 	Sort field
	 * @param  int    		$limit     	Limit
	 * @param  int    		$offset    	Offset page
	 * @param  int    		$arch      	Archive or not (not used)
	 * @param  string|array $filter    	Filter for query
	 * @return int 						Return integer <0 if KO, >0 if OK
	 */
	public function fetchAll($user, $sortorder = 'ASC', $sortfield = 't.datec', $limit = 0, $offset = 0, $arch = 0, $filter = '')
	{
		global $langs, $extrafields;

		// fetch optionals attributes and labels
		$extrafields->fetch_name_optionals_label($this->table_element);

		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.ref,";
		$sql .= " t.track_id,";
		$sql .= " t.fk_soc,";
		$sql .= " t.fk_project,";
		$sql .= " t.fk_contract,";
		$sql .= " t.origin_email,";
		$sql .= " t.origin_replyto,";
		$sql .= " t.origin_references,";
		$sql .= " t.fk_user_create, uc.lastname as user_create_lastname, uc.firstname as user_create_firstname,";
		$sql .= " t.fk_user_assign, ua.lastname as user_assign_lastname, ua.firstname as user_assign_firstname,";
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
		$sql .= " t.date_last_msg_sent,";
		$sql .= " t.date_close,";
		$sql .= " t.tms,";
		$sql .= " type.label as type_label, category.label as category_label, severity.label as severity_label";
		// Add fields for extrafields
		if ($extrafields->attributes[$this->table_element]['count'] > 0) {
			foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
				$sql .= ($extrafields->attributes[$this->table_element]['type'][$key] != 'separate' ? ",ef.".$key." as options_".$key : '');
			}
		}
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_type as type ON type.code = t.type_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_category as category ON category.code = t.category_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_ticket_severity as severity ON severity.code = t.severity_code";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = t.fk_soc";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as uc ON uc.rowid = t.fk_user_create";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as ua ON ua.rowid = t.fk_user_assign";
		if ($extrafields->attributes[$this->table_element]['count'] > 0) {
			if (is_array($extrafields->attributes[$this->table_element]['label']) && count($extrafields->attributes[$this->table_element]['label'])) {
				$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."ticket_extrafields as ef on (t.rowid = ef.fk_object)";
			}
		}
		$sql .= " WHERE t.entity IN (".getEntity('ticket').")";

		// Manage filter
		if (is_array($filter)) {
			foreach ($filter as $key => $value) {
				if (strpos($key, 'date')) { // To allow $filter['YEAR(s.dated)']=>$year
					$sql .= " AND ".$this->db->sanitize($key)." = '".$this->db->escape($value)."'";
				} elseif (($key == 't.fk_user_assign') || ($key == 't.type_code') || ($key == 't.category_code') || ($key == 't.severity_code') || ($key == 't.fk_soc')) {
					$sql .= " AND ".$this->db->sanitize($key)." = '".$this->db->escape($value)."'";
				} elseif ($key == 't.fk_statut') {
					if (is_array($value) && count($value) > 0) {
						$sql .= " AND ".$this->db->sanitize($key)." IN (".$this->db->sanitize(implode(',', $value)).")";
					} else {
						$sql .= " AND ".$this->db->sanitize($key).' = '.((int) $value);
					}
				} elseif ($key == 't.fk_contract') {
					$sql .= " AND ".$this->db->sanitize($key).' = '.((int) $value);
				} else {
					$sql .= " AND ".$this->db->sanitize($key)." LIKE '%".$this->db->escape($this->db->escapeforlike($value))."%'";
				}
			}

			$filter = '';
		}

		// Manage filter
		$errormessage = '';
		$sql .= forgeSQLFromUniversalSearchCriteria($filter, $errormessage);
		if ($errormessage) {
			$this->errors[] = $errormessage;
			dol_syslog(__METHOD__.' '.implode(',', $this->errors), LOG_ERR);
			return -1;
		}

		// Case of external user
		$socid = $user->socid ? $user->socid : 0;
		// If the internal user must only see his customers, force searching by him
		$search_sale = 0;
		if (!$user->hasRight('societe', 'client', 'voir')) {
			$search_sale = $user->id;
		}
		// Search on sale representative
		if ($search_sale && $search_sale != '-1') {
			if ($search_sale == -2) {
				$sql .= " AND NOT EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc)";
			} elseif ($search_sale > 0) {
				$sql .= " AND EXISTS (SELECT sc.fk_soc FROM ".MAIN_DB_PREFIX."societe_commerciaux as sc WHERE sc.fk_soc = t.fk_soc AND sc.fk_user = ".((int) $search_sale).")";
			}
		}
		// Search on socid
		if ($socid) {
			$sql .= " AND t.fk_soc = ".((int) $socid);
		}

		$sql .= $this->db->order($sortfield, $sortorder);
		if (!empty($limit)) {
			$sql .= $this->db->plimit($limit + 1, $offset);
		}

		dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);
		$resql = $this->db->query($sql);

		if ($resql) {
			$this->lines = array();

			$num = $this->db->num_rows($resql);
			$i = 0;

			if ($num) {
				while ($i < $num) {
					$obj = $this->db->fetch_object($resql);

					$line = new self($this->db);

					$line->id = $obj->rowid;
					//$line->rowid = $obj->rowid;
					$line->ref = $obj->ref;
					$line->track_id = $obj->track_id;
					$line->fk_soc = $obj->fk_soc;
					$line->fk_project = $obj->fk_project;
					$line->fk_contract = $obj->fk_contract;
					$line->origin_email = $obj->origin_email;
					$line->origin_replyto = $obj->origin_replyto;
					$line->origin_references = $obj->origin_references;

					$line->fk_user_create = $obj->fk_user_create;
					$line->fk_user_assign = $obj->fk_user_assign;

					$line->subject = $obj->subject;
					$line->message = $obj->message;
					$line->fk_statut = $obj->status;
					$line->status = $obj->status;
					$line->resolution = $obj->resolution;
					$line->progress = $obj->progress;
					$line->timing = $obj->timing;

					$label_type = ($langs->trans("TicketTypeShort".$obj->type_code) != "TicketTypeShort".$obj->type_code ? $langs->trans("TicketTypeShort".$obj->type_code) : ($obj->type_label != '-' ? $obj->type_label : ''));
					$line->type_label = $label_type;

					$this->category_code = $obj->category_code;
					$label_category = ($langs->trans("TicketCategoryShort".$obj->category_code) != "TicketCategoryShort".$obj->category_code ? $langs->trans("TicketCategoryShort".$obj->category_code) : ($obj->category_label != '-' ? $obj->category_label : ''));
					$line->category_label = $label_category;

					$this->severity_code = $obj->severity_code;
					$label_severity = ($langs->trans("TicketSeverityShort".$obj->severity_code) != "TicketSeverityShort".$obj->severity_code ? $langs->trans("TicketSeverityShort".$obj->severity_code) : ($obj->severity_label != '-' ? $obj->severity_label : ''));
					$line->severity_label = $label_severity;

					$line->datec = $this->db->jdate($obj->datec);
					$line->date_read = $this->db->jdate($obj->date_read);
					$line->date_last_msg_sent = $this->db->jdate($obj->date_last_msg_sent);
					$line->date_close = $this->db->jdate($obj->date_close);

					// Extra fields
					if ($extrafields->attributes[$this->table_element]['count'] > 0) {
						if (is_array($extrafields->attributes[$this->table_element]['label']) && count($extrafields->attributes[$this->table_element]['label'])) {
							foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
								$tmpkey = 'options_'.$key;
								$line->{$tmpkey} = $obj->$tmpkey;
							}
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
			dol_syslog(get_class($this)."::fetchAll ".$this->error, LOG_ERR);
			return -1;
		}
	}

	/**
	 *  Update object into database
	 *
	 *  @param  User $user      User that modifies
	 *  @param  int  $notrigger 0=launch triggers after, 1=disable triggers
	 *  @return int                     Return integer <0 if KO, >0 if OK
	 */
	public function update($user, $notrigger = 0)
	{
		$error = 0;

		// $this->oldcopy should have been set by the caller of update
		//if (empty($this->oldcopy)) {
		//	dol_syslog("this->oldcopy should have been set by the caller of update (here properties were already modified)", LOG_WARNING);
		//	$this->oldcopy = dol_clone($this, 2);
		//}

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

		if (isset($this->fk_contract)) {
			$this->fk_contract = (int) $this->fk_contract;
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
			if (dol_strlen($this->message) > 65000) {
				$this->errors[] = 'ErrorFieldTooLong';
				dol_syslog(get_class($this).'::update error -1 message too long', LOG_ERR);
				return -1;
			}
		}

		if (isset($this->fk_statut)) {
			$this->fk_statut = (int) $this->fk_statut;
		}

		if (isset($this->resolution)) {
			$this->resolution = trim($this->resolution);
		}

		if (isset($this->progress)) {
			$this->progress = (int) $this->progress;
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
		if (isset($this->model_pdf)) {
			$this->model_pdf = trim($this->model_pdf);
		}
		// Check parameters
		// Put here code to add a control on parameters values
		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."ticket SET";
		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "").",";
		$sql .= " track_id=".(isset($this->track_id) ? "'".$this->db->escape($this->track_id)."'" : "null").",";
		$sql .= " fk_soc=".(isset($this->fk_soc) ? (int) $this->fk_soc : "null").",";
		$sql .= " fk_project=".(isset($this->fk_project) ? (int) $this->fk_project : "null").",";
		$sql .= " fk_contract=".(isset($this->fk_contract) ? (int) $this->fk_contract : "null").",";
		$sql .= " origin_email=".(isset($this->origin_email) ? "'".$this->db->escape($this->origin_email)."'" : "null").",";
		$sql .= " origin_replyto=".(isset($this->origin_replyto) ? "'".$this->db->escape($this->origin_replyto)."'" : "null").",";
		$sql .= " origin_references=".(isset($this->origin_references) ? "'".$this->db->escape($this->origin_references)."'" : "null").",";
		$sql .= " fk_user_create=".(isset($this->fk_user_create) ? (int) $this->fk_user_create : "null").",";
		$sql .= " fk_user_assign=".(isset($this->fk_user_assign) ? (int) $this->fk_user_assign : "null").",";
		$sql .= " subject=".(isset($this->subject) ? "'".$this->db->escape($this->subject)."'" : "null").",";
		$sql .= " message=".(isset($this->message) ? "'".$this->db->escape($this->message)."'" : "null").",";
		$sql .= " fk_statut=".(isset($this->fk_statut) ? (int) $this->fk_statut : "0").",";
		$sql .= " resolution=".(isset($this->resolution) ? (int) $this->resolution : "null").",";
		$sql .= " progress=".(isset($this->progress) ? "'".$this->db->escape($this->progress)."'" : "null").",";
		$sql .= " timing=".(isset($this->timing) ? "'".$this->db->escape($this->timing)."'" : "null").",";
		$sql .= " type_code=".(isset($this->type_code) ? "'".$this->db->escape($this->type_code)."'" : "null").",";
		$sql .= " category_code=".(isset($this->category_code) ? "'".$this->db->escape($this->category_code)."'" : "null").",";
		$sql .= " severity_code=".(isset($this->severity_code) ? "'".$this->db->escape($this->severity_code)."'" : "null").",";
		$sql .= " datec=".(dol_strlen($this->datec) != 0 ? "'".$this->db->idate($this->datec)."'" : 'null').",";
		$sql .= " date_read=".(dol_strlen($this->date_read) != 0 ? "'".$this->db->idate($this->date_read)."'" : 'null').",";
		$sql .= " date_last_msg_sent=".(dol_strlen($this->date_last_msg_sent) != 0 ? "'".$this->db->idate($this->date_last_msg_sent)."'" : 'null').",";
		$sql .= " model_pdf=".(isset($this->model_pdf) ? "'".$this->db->escape($this->model_pdf)."'" : "null").",";
		$sql .= " date_close=".(dol_strlen($this->date_close) != 0 ? "'".$this->db->idate($this->date_close)."'" : 'null');
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
	 *  @return int                     Return integer <0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
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

		// Delete all child tables

		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."categorie_ticket";
			$sql .= " WHERE fk_ticket = ".(int) $this->id;

			$result = $this->db->query($sql);
			if (!$result) {
				$error++;
				$this->errors[] = $this->db->lasterror();
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
			} else {
				// we delete file with dol_delete_dir_recursive
				$this->deleteEcmFiles(1);

				$dir = DOL_DATA_ROOT.'/'.$this->element.'/'.$this->ref;
				// For remove dir
				if (dol_is_dir($dir)) {
					if (!dol_delete_dir_recursive($dir)) {
						$this->errors[] = $this->error;
					}
				}
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

		// Clear fields
		$object->id = 0;
		$object->statut = 0;
		$object->status = 0;

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
		$this->progress = 10;
		//$this->timing = '30';
		$this->type_code = 'TYPECODE';
		$this->category_code = 'CATEGORYCODE';
		$this->severity_code = 'SEVERITYCODE';
		$this->datec = dol_now();
		$this->date_read = dol_now();
		$this->date_last_msg_sent = dol_now();
		$this->date_close = dol_now();
		$this->tms = dol_now();

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
		print Form::selectarray('search_fk_statut', $this->labelStatusShort, $selected, $show_empty = 1, $key_in_label = 0, $value_as_key = 0, $option = '', $translate = 1, $maxlen = 0, $disabled = 0, $sort = '', $morecss = '');
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
		$sql .= " WHERE entity IN (".getEntity('c_ticket_type').")";
		$sql .= " AND active > 0";
		$sql .= " ORDER BY pos";
		dol_syslog(get_class($this)."::load_cache_type_tickets", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$label = ($langs->trans("TicketTypeShort".$obj->code) != "TicketTypeShort".$obj->code ? $langs->trans("TicketTypeShort".$obj->code) : ($obj->label != '-' ? $obj->label : ''));
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
	 *      @param	int		$publicgroup	0=No public group, 1=Public group only, -1=All
	 *      @return int             		Number of lines loaded, 0 if already loaded, <0 if KO
	 */
	public function loadCacheCategoriesTickets($publicgroup = -1)
	{
		global $conf, $langs;

		if ($publicgroup == -1 && !empty($this->cache_category_ticket) && count($this->cache_category_tickets)) {
			// Cache already loaded
			return 0;
		}

		$sql = "SELECT rowid, code, label, use_default, pos, description, public, active, force_severity, fk_parent";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_category";
		$sql .= " WHERE entity IN (".getEntity('c_ticket_category').")";
		$sql .= " AND active > 0";
		if ($publicgroup > -1) {
			$sql .= " AND public = ".((int) $publicgroup);
		}
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
				$label = ($langs->trans("TicketCategoryShort".$obj->code) != "TicketCategoryShort".$obj->code ? $langs->trans("TicketCategoryShort".$obj->code) : ($obj->label != '-' ? $obj->label : ''));
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
		global $conf, $langs;

		if (!empty($conf->cache['severity_tickets']) && count($conf->cache['severity_tickets'])) {
			// Cache already loaded
			return 0;
		}

		$sql = "SELECT rowid, code, label, use_default, pos, description";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_ticket_severity";
		$sql .= " WHERE entity IN (".getEntity('c_ticket_severity').")";
		$sql .= " AND active > 0";
		$sql .= " ORDER BY pos";
		dol_syslog(get_class($this)."::loadCacheSeveritiesTickets", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$conf->cache['severity_tickets'][$obj->rowid]['code'] = $obj->code;
				$label = ($langs->trans("TicketSeverityShort".$obj->code) != "TicketSeverityShort".$obj->code ? $langs->trans("TicketSeverityShort".$obj->code) : ($obj->label != '-' ? $obj->label : ''));
				$conf->cache['severity_tickets'][$obj->rowid]['label'] = $label;
				$conf->cache['severity_tickets'][$obj->rowid]['use_default'] = $obj->use_default;
				$conf->cache['severity_tickets'][$obj->rowid]['pos'] = $obj->pos;
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
		return $this->LibStatut($this->status, $mode, 0, $this->progress);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return status label of object
	 *
	 * @param	string		$status			Id status
	 * @param	int			$mode			0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @param	int			$notooltip		1=No tooltip
	 * @param	int			$progress		Progression (0 to 100)
	 * @return	string						Label
	 */
	public function LibStatut($status, $mode = 0, $notooltip = 0, $progress = 0)
	{
		// phpcs:enable
		global $langs, $hookmanager;

		$labelStatus = (isset($status) && !empty($this->labelStatus[$status])) ? $this->labelStatus[$status] : '';
		$labelStatusShort = (isset($status) && !empty($this->labelStatusShort[$status])) ? $this->labelStatusShort[$status] : '';

		switch ($status) {
			case self::STATUS_NOT_READ:						// Not read
				$statusType = 'status0';
				break;
			case self::STATUS_READ:							// Read
				$statusType = 'status1';
				break;
			case self::STATUS_ASSIGNED:						// Assigned
				$statusType = 'status2';
				break;
			case self::STATUS_IN_PROGRESS:					// In progress
				$statusType = 'status4';
				break;
			case self::STATUS_WAITING:						// Waiting/pending/suspended
				$statusType = 'status7';
				break;
			case self::STATUS_NEED_MORE_INFO:				// Waiting more information from the requester
				$statusType = 'status3';
				break;
			case self::STATUS_CANCELED:						// Canceled
				$statusType = 'status9';
				break;
			case self::STATUS_CLOSED:						// Closed
				$statusType = 'status6';
				break;
			default:
				$labelStatus = 'Unknown';
				$labelStatusShort = 'Unknown';
				$statusType = 'status0';
				$mode = 0;
		}

		$parameters = array(
			'status'          => $status,
			'mode'            => $mode,
		);

		// Note that $action and $object may have been modified by hook
		$reshook = $hookmanager->executeHooks('LibStatut', $parameters, $this);

		if ($reshook > 0) {
			return $hookmanager->resPrint;
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
	 * getTooltipContentArray
	 *
	 * @param array $params ex option, infologin
	 * @since v18
	 * @return array
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$langs->load('ticket');
		$nofetch = !empty($params['nofetch']);

		$datas = array();
		$datas['picto'] = img_picto('', $this->picto).' <u class="paddingrightonly">'.$langs->trans("Ticket").'</u>';
		$datas['picto'] .= ' '.$this->getLibStatut(4);
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		$datas['track_id'] = '<br><b>'.$langs->trans('TicketTrackId').':</b> '.$this->track_id;
		$datas['subject'] = '<br><b>'.$langs->trans('Subject').':</b> '.$this->subject;
		if ($this->date_creation) {
			$datas['date_creation'] = '<br><b>'.$langs->trans('DateCreation').':</b> '.dol_print_date($this->date_creation, 'dayhour');
		}
		if ($this->date_modification) {
			$datas['date_modification'] = '<br><b>'.$langs->trans('DateModification').':</b> '.dol_print_date($this->date_modification, 'dayhour');
		}
		// show categories for this record only in ajax to not overload lists
		if (isModEnabled('category') && !$nofetch) {
			require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
			$form = new Form($this->db);
			$datas['categories'] = '<br>' . $form->showCategories($this->id, Categorie::TYPE_TICKET, 1);
		}

		return $datas;
	}

	/**
	 *  Return a link to the object card (with optionally the picto)
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
		global $action, $conf, $hookmanager, $langs;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
			'option' => $option,
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

		$url = DOL_URL_ROOT.'/ticket/card.php?id='.$this->id;

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

		$linkclose = '';
		if (empty($notooltip)) {
			if (getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowTicket");
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
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		$hookmanager->initHooks(array('ticketdao'));
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
	 *    Mark a message as read
	 *
	 *    @param    User		$user			Object user
	 *    @param	int			$notrigger		No trigger
	 *    @return   int							Return integer <0 if KO, 0=nothing done, >0 if OK
	 */
	public function markAsRead($user, $notrigger = 0)
	{
		global $langs;

		$error = 0;

		if ($this->status != self::STATUS_CANCELED) { // no closed
			$this->oldcopy = dol_clone($this, 2);

			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."ticket";
			$sql .= " SET fk_statut = ".Ticket::STATUS_READ.", date_read = '".$this->db->idate(dol_now())."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::markAsRead");
			$resql = $this->db->query($sql);
			if ($resql) {
				$this->context['actionmsg'] = $langs->trans('TicketLogMesgReadBy', $this->ref, $user->getFullName($langs));
				$this->context['actionmsg2'] = $langs->trans('TicketLogMesgReadBy', $this->ref, $user->getFullName($langs));

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
					$this->error = implode(',', $this->errors);
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

		return 0;
	}

	/**
	 *    Set an assigned user to a ticket.
	 *
	 *    @param    User	$user				Object user
	 *    @param    int 	$id_assign_user		ID of user assigned
	 *    @param    int 	$notrigger        	Disable trigger
	 *    @return   int							Return integer <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function assignUser($user, $id_assign_user, $notrigger = 0)
	{
		$error = 0;

		$this->oldcopy = dol_clone($this, 2);

		$this->db->begin();

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
				$this->error = implode(',', $this->errors);
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
	 * Add message into database
	 *
	 * @param 	User 	$user      		  	User that creates
	 * @param 	int  	$notrigger 		  	0=launch triggers after, 1=disable triggers
	 * @param 	array	$filename_list      List of files to attach (full path of filename on file system)
	 * @param 	array	$mimetype_list      List of MIME type of attached files
	 * @param 	array	$mimefilename_list  List of attached file name in message
	 * @param 	boolean	$send_email      	Whether the message is sent by email
	 * @param   int     $public_area    	0=Default, 1 if we are creating the message from a public area (so we can search contact from email to add it as contact of ticket if TICKET_ASSIGN_CONTACT_TO_MESSAGE is set)
	 * @return 	int						  	Return integer <0 if KO, >0 if OK
	 */
	public function createTicketMessage($user, $notrigger = 0, $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array(), $send_email = false, $public_area = 0)
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
		$actioncomm->type_code = 'AC_OTH_AUTO';	// This is not an entry that must appears into manual calendar but only into CRM calendar
		$actioncomm->code = 'TICKET_MSG';
		if ($this->private) {
			$actioncomm->code = 'TICKET_MSG_PRIVATE';
		}
		if ($send_email) {
			$actioncomm->code .= '_SENTBYMAIL';
		}
		if ((empty($user->id) || $user->id == 0) && isset($_SESSION['email_customer'])) {
			$actioncomm->email_from = $_SESSION['email_customer'];
		}
		$actioncomm->socid = $this->socid;
		$actioncomm->label = $this->subject;
		$actioncomm->note_private = $this->message;
		$actioncomm->userassigned = array($user->id => array('id' => $user->id,'transparency' => 0));
		$actioncomm->userownerid = $user->id;
		$actioncomm->datep = $now;
		$actioncomm->percentage = -1; // percentage is not relevant for punctual events
		$actioncomm->elementtype = 'ticket';
		$actioncomm->fk_element = $this->id;
		$actioncomm->fk_project = $this->fk_project;

		// add contact id from author email on public interface
		if ($public_area && !empty($this->origin_email) && getDolGlobalString('TICKET_ASSIGN_CONTACT_TO_MESSAGE')) {
			$contacts = $this->searchContactByEmail($this->origin_email);
			if (!empty($contacts)) {
				// Ensure that contact is active and select first active contact
				foreach ($contacts as $contact) {
					if ((int) $contact->statut == 1) {
						$actioncomm->contact_id = $contact->id;
						break;
					}
				}
			}
		}

		$attachedfiles = array();
		$attachedfiles['paths'] = $filename_list;
		$attachedfiles['names'] = $mimefilename_list;
		$attachedfiles['mimes'] = $mimetype_list;
		if (is_array($attachedfiles) && count($attachedfiles) > 0) {
			$actioncomm->attachedfiles = $attachedfiles;
		}

		//if (!empty($mimefilename_list) && is_array($mimefilename_list)) {
		//	$actioncomm->note_private = dol_concatdesc($actioncomm->note_private, "\n".$langs->transnoentities("AttachedFiles").': '.implode(';', $mimefilename_list));
		//}
		$actionid = $actioncomm->create($user);
		if ($actionid <= 0) {
			$error++;
			$this->error = $actioncomm->error;
			$this->errors = $actioncomm->errors;
		}

		if ($actionid > 0) {
			if (is_array($attachedfiles) && array_key_exists('paths', $attachedfiles) && count($attachedfiles['paths']) > 0) {
				foreach ($attachedfiles['paths'] as $key => $filespath) {
					$destdir = $conf->agenda->dir_output.'/'.$actionid;
					$destfile = $destdir.'/'.$attachedfiles['names'][$key];
					if (dol_mkdir($destdir) >= 0) {
						require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
						dol_move($filespath, $destfile);
						if (in_array($actioncomm->code,  array('TICKET_MSG', 'TICKET_MSG_SENTBYMAIL'))) {
							$ecmfile = new EcmFiles($this->db);
							$destdir = preg_replace('/^'.preg_quote(DOL_DATA_ROOT, '/').'/', '', $destdir);
							$destdir = preg_replace('/[\\/]$/', '', $destdir);
							$destdir = preg_replace('/^[\\/]/', '', $destdir);
							$ecmfile->fetch(0, '', $destdir.'/'.$attachedfiles['names'][$key]);
							require_once DOL_DOCUMENT_ROOT.'/core/lib/security2.lib.php';
							$ecmfile->share = getRandomPassword(true);
							$result = $ecmfile->update($user);
							if ($result < 0) {
								setEventMessages($ecmfile->error, $ecmfile->errors, 'warnings');
							}
						}
					}
				}
			}
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
		if (!empty($this->cache_msgs_ticket) && is_array($this->cache_msgs_ticket) && count($this->cache_msgs_ticket)) {
			return 0;
		}

		// Cache already loaded

		$sql = "SELECT id as rowid, fk_user_author, email_from, datec, datep, label, note as message, code";
		$sql .= " FROM ".MAIN_DB_PREFIX."actioncomm";
		$sql .= " WHERE fk_element = ".(int) $this->id;
		$sql .= " AND elementtype = 'ticket'";
		$sql .= " ORDER BY datep DESC";

		dol_syslog(get_class($this)."::load_cache_actions_ticket", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);
				$this->cache_msgs_ticket[$i]['id'] = $obj->rowid;
				$this->cache_msgs_ticket[$i]['fk_user_author'] = $obj->fk_user_author;
				if (in_array($obj->code, array('TICKET_MSG', 'AC_TICKET_CREATE')) && empty($obj->fk_user_author)) {
					$this->cache_msgs_ticket[$i]['fk_contact_author'] = $obj->email_from;
				}
				$this->cache_msgs_ticket[$i]['datec'] = $this->db->jdate($obj->datec);
				$this->cache_msgs_ticket[$i]['datep'] = $this->db->jdate($obj->datep);
				$this->cache_msgs_ticket[$i]['subject'] = $obj->label;
				$this->cache_msgs_ticket[$i]['message'] = $obj->message;
				$this->cache_msgs_ticket[$i]['private'] = (preg_match('/^TICKET_MSG_PRIVATE/', $obj->code) ? 1 : 0);
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
	 *    @param	int		$mode		0=Close solved, 1=Close abandoned
	 *    @return   int		           	Return integer <0 if KO, 0=nothing done, >0 if OK
	 */
	public function close(User $user, $mode = 0)
	{
		global $conf;

		if ($this->status != Ticket::STATUS_CLOSED && $this->status != Ticket::STATUS_CANCELED) { // not closed
			$this->db->begin();

			$sql = "UPDATE ".MAIN_DB_PREFIX."ticket";
			$sql .= " SET fk_statut=".($mode ? Ticket::STATUS_CANCELED : Ticket::STATUS_CLOSED).", progress=100, date_close='".$this->db->idate(dol_now())."'";
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::close mode=".$mode);
			$resql = $this->db->query($sql);
			if ($resql) {
				$error = 0;

				// Valid and close fichinter linked
				if (isModEnabled('intervention') && getDolGlobalString('WORKFLOW_TICKET_CLOSE_INTERVENTION')) {
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
					$this->error = implode(',', $this->errors);
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

		return 0;
	}

	/**
	 *     Search and fetch thirparties by email
	 *
	 *     @param  string 		$email   		Email
	 *     @param  int    		$type    		Type of thirdparties (0=any, 1=customer, 2=prospect, 3=supplier)
	 *     @param  array  		$filters 		Array of couple field name/value to filter the companies with the same name
	 *     @param  string 		$clause  		Clause for filters
	 *     @return array|int    		   		Array of thirdparties object
	 */
	public function searchSocidByEmail($email, $type = 0, $filters = array(), $clause = 'AND')
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
	 *     @param  string 		$email 		Email
	 *     @param  int  		$socid 		Limit to a thirdparty
	 *     @param  string 		$case  		Respect case
	 *     @return array|int        		Array of contacts object
	 */
	public function searchContactByEmail($email, $socid = 0, $case = '')
	{
		$contacts = array();

		// Forge the search SQL
		$sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."socpeople";
		$sql .= " WHERE entity IN (".getEntity('contact').")";
		if (!empty($socid)) {
			$sql .= " AND fk_soc = ".((int) $socid);
		}
		if (!empty($email)) {
			$sql .= " AND ";
			if (!$case) {
				$sql .= "email = '".$this->db->escape($email)."'";
			} else {
				$sql .= "email LIKE BINARY '".$this->db->escape($this->db->escapeforlike($email))."'";
			}
		}

		$res = $this->db->query($sql);
		if ($res) {
			while ($rec = $this->db->fetch_object($res)) {
				include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
				$contactstatic = new Contact($this->db);
				$contactstatic->fetch($rec->rowid);
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
	 *    @param  int $id		Id of thirdparty to set or '' to remove
	 *    @return int           Return integer <0 if KO, >0 if OK
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
	 *    @return int             Return integer <0 if KO, >0 if OK
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
	 *     Link element with a contract
	 *
	 *     @param  int $contractid Contract id to link element to
	 *     @return int                        Return integer <0 if KO, >0 if OK
	 */
	public function setContract($contractid)
	{
		if ($this->id) {
			$sql = "UPDATE ".MAIN_DB_PREFIX."ticket";
			$sql .= " SET fk_contract = ".($contractid > 0 ? $contractid : "null");
			$sql .= " WHERE rowid = ".((int) $this->id);
			dol_syslog(get_class($this).'::setContract sql='.$sql);
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
	 *  Retrieve information about internal contacts
	 *
	 *  @param    int     $status     Status of user or company                                array<array{id:int,email:string,firstname:string,lastname:string,libelle:string}>
	 *  @return array<array{id:int,email:string,firstname:string,lastname:string,libelle:string,socid:int,code:string,status:int}>             Array with datas : firstname, lastname, socid (-1 for internal users), email, code, libelle, status
	 */
	public function getInfosTicketInternalContact($status = -1)
	{
		return $this->listeContact(-1, 'internal', 0, '', $status);
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
	 * Retrieve information about external contacts
	 *
	 *  @param    int     $status     Status of user or company
	 *  @return array                 Array with datas : firstname, lastname, socid (-1 for internal users), email, code, libelle, status
	 */
	public function getInfosTicketExternalContact($status = -1)
	{
		return $this->listeContact(-1, 'external', 0, '', $status);
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

		$array_contact = $this->getIdTicketInternalContact();

		$array_contact = array_merge($array_contact, $this->getIdTicketCustomerContact());

		$array_contact = array_merge($array_contact, $this->getIdTicketInternalInvolvedContact());

		$array_contact = array_merge($array_contact, $this->getIdTicketCustomerInvolvedContact());

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

		$array_contact = array_merge($array_contact, $this->getIdTicketCustomerContact());

		$array_contact = array_merge($array_contact, $this->getIdTicketCustomerInvolvedContact());

		return $array_contact;
	}


	/**
	 *    Get array of all contacts for a ticket
	 *    Override method of file commonobject.class.php to add phone number
	 *
	 *    @param    int     $statusoflink   Status of lines to get (-1=all)
	 *    @param    string  $source         Source of contact: external or thirdparty (llx_socpeople) or internal (llx_user)
	 *    @param    int     $list           0:Return array contains all properties, 1:Return array contains just id
	 *    @param    string  $code           Filter on this code of contact type ('SHIPPING', 'BILLING', ...)
	 *    @param    int     $status         Status of user or company
	 *    @return   array<int|array{source:string,id:int,rowid:int,email:string,civility:string,firstname:string,lastname:string,labeltype:string,libelle:string,socid:int,code:string,status:int,statuscontact:string,fk_c_typecontact:string,phone:string,phone_mobile:string,nom:string}>|int<-1,-1>      Array of array('email'=>..., 'lastname'=>...)
	 */
	public function listeContact($statusoflink = -1, $source = 'external', $list = 0, $code = '', $status = -1)
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
			if ($status >= 0) {
				$sql .= " AND t.statut = ".((int) $status);
			}
		}

		if ($source == 'external' || $source == 'thirdparty') {
			$sql .= " AND tc.source = 'external'";
			if ($status >= 0) {
				$sql .= " AND t.statut = ".((int) $status);
			}
		}

		if (!empty($code)) {
			$sql .= " AND tc.code = '".$this->db->escape($code)."'";
		}

		$sql .= " AND tc.active=1";
		if ($statusoflink >= 0) {
			$sql .= " AND ec.statut = ".((int) $statusoflink);
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
					$labelType = ($langs->trans($transkey) != $transkey ? $langs->trans($transkey) : $obj->type_contact_label);
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
						'libelle' => $labelType,		// deprecated, replaced with labeltype
						'labeltype' => $labelType,
						'status' => $obj->statuslink,
						'statuscontact' => $obj->statuscontact,
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
	public function getDefaultRef($thirdparty = null)
	{
		global $conf;

		$defaultref = '';
		$modele = getDolGlobalString('TICKET_ADDON', 'mod_ticket_simple');

		// Search template files
		$file = '';
		$classname = '';
		$dirmodels = array_merge(array('/'), (array) $conf->modules_parts['models']);
		foreach ($dirmodels as $reldir) {
			$file = dol_buildpath($reldir."core/modules/ticket/".$modele.'.php', 0);
			if (file_exists($file)) {
				$classname = $modele;
				break;
			}
		}

		if ($classname !== '') {
			$result = dol_include_once($reldir."core/modules/ticket/".$modele.'.php');
			$modTicket = new $classname();

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
						$file = mb_convert_encoding($file, 'UTF-8', 'ISO-8859-1');	// To be sure data is stored in UTF8 in memory
					}
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
	 * @param	string		$forcetrackid	Force trackid used for $keytoavoidconflict into get_attached_files()
	 * @return	array|int					Array with final path/name/mime of files.
	 */
	public function copyFilesForTicket($forcetrackid = null)
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
		$formmail->trackid = (is_null($forcetrackid) ? 'tic'.$this->id : '');
		$attachedfiles = $formmail->get_attached_files();

		$filepath = $attachedfiles['paths'];	// path is for example user->dir_temp.'/'.$user->id.'/'...
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

			$moreinfo = array('description' => 'File saved by copyFilesForTicket', 'src_object_type' => $this->element, 'src_object_id' => $this->id);
			$res = dol_move($filepath[$i], $destfile, 0, 1, 0, 1, $moreinfo);
			if (!$res) {
				// Move has failed
				$this->error = "Failed to move file ".dirbasename($filepath[$i])." into ".dirbasename($destfile);
				return -1;
			} else {
				// If file is an image, we create thumbs
				if (image_format_supported($destfile) == 1) {
					// Create small thumbs for image (Ratio is near 16/9)
					// Used on logon for example
					$imgThumbSmall = vignette($destfile, $maxwidthsmall, $maxheightsmall, '_small', 50, "thumbs");
					// Create mini thumbs for image (Ratio is near 16/9)
					// Used on menu or for setup page for example
					$imgThumbMini = vignette($destfile, $maxwidthmini, $maxheightmini, '_mini', 50, "thumbs");
				}
			}

			// Clear variables into session
			$formmail->remove_attached_files($i);

			// Fill array with new names
			$listofpaths[$i] = $destfile;
			$listofnames[$i] = basename($destfile);
		}

		return array('listofpaths' => $listofpaths, 'listofnames' => $listofnames, 'listofmimes' => $mimetype);
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param  int[]|int 	$categories 	Category or categories IDs
	 * @return int							Return integer <0 if KO, >0 if OK
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

		return 1;
	}

	/**
	 * Add new message on a ticket (private/public area).
	 * Can also send it by email if GETPOST('send_email', 'int') is set. For such email, header and footer is added.
	 *
	 * @param   User    $user       	User for action
	 * @param   string  $action     	Action string
	 * @param   int     $private    	1=Message is private (must not be visible by external users)
	 * @param   int     $public_area    0=Default,
	 * 									1=If we are creating the message from a public area, so confirmation email will be sent to the author
	 * 									and we can search contact from email to add it as contact of ticket if TICKET_ASSIGN_CONTACT_TO_MESSAGE is set
	 * @return  int						Return integer <0 if KO, >= 0 if OK
	 */
	public function newMessage($user, &$action, $private = 1, $public_area = 0)
	{
		global $mysoc, $conf, $langs;

		$error = 0;

		$object = new Ticket($this->db);

		$ret = $object->fetch('', '', GETPOST('track_id', 'alpha'));

		$object->socid = $object->fk_soc;
		$object->fetch_thirdparty();
		$object->fetch_project();

		if ($ret < 0) {
			$error++;
			array_push($this->errors, $langs->trans("ErrorTicketIsNotValid"));
			$action = '';
		}

		if (!GETPOST("message")) {
			$error++;
			array_push($this->errors, $langs->trans("ErrorFieldRequired", $langs->transnoentities("Message")));
			$action = 'add_message';
		}

		if (!$error) {
			$object->subject = GETPOST('subject', 'alphanohtml');
			$object->message = GETPOST("message", "restricthtml");
			$object->private = GETPOST("private_message", "alpha");

			$send_email = GETPOSTINT('send_email');

			// Copy attached files (saved into $_SESSION) as linked files to ticket. Return array with final name used.
			$resarray = $object->copyFilesForTicket();
			if (is_numeric($resarray) && $resarray == -1) {
				setEventMessages($object->error, $object->errors, 'errors');
				return -1;
			}

			$listofpaths = $resarray['listofpaths'];
			$listofnames = $resarray['listofnames'];
			$listofmimes = $resarray['listofmimes'];

			$id = $object->createTicketMessage($user, 0, $listofpaths, $listofmimes, $listofnames, $send_email, $public_area);
			if ($id <= 0) {
				$error++;
				$this->error = $object->error;
				$this->errors = $object->errors;
				$action = 'add_message';
			}

			if (!$error && $id > 0) {
				setEventMessages($langs->trans('TicketMessageSuccessfullyAdded'), null, 'mesgs');

				if (!empty($public_area)) {
					/*
					 * Message created from the Public interface
					 *
					 * Send emails to assigned users (public area notification)
					 */
					if (getDolGlobalString('TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_ENABLED')) {
						// Retrieve internal contact datas
						$internal_contacts = $object->getInfosTicketInternalContact(1);

						$assigned_user_dont_have_email = '';

						$sendto = array();

						if ($this->fk_user_assign > 0) {
							$assigned_user = new User($this->db);
							$assigned_user->fetch($this->fk_user_assign);
							if (!empty($assigned_user->email)) {
								$sendto[$assigned_user->email] = $assigned_user->getFullName($langs)." <".$assigned_user->email.">";
							} else {
								$assigned_user_dont_have_email = $assigned_user->getFullName($langs);
							}
						}

						// Build array to display recipient list
						foreach ($internal_contacts as $key => $info_sendto) {
							// Avoid duplicate notifications
							if ($info_sendto['id'] == $user->id) {
								continue;
							}

							// We check if the email address is not the assignee's address to prevent notification from being sent twice
							if (!empty($info_sendto['email']) && $assigned_user->email != $info_sendto['email']) {
								$sendto[] = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'])." <".$info_sendto['email'].">";
							}
						}

						if (empty($sendto)) {
							if (getDolGlobalString('TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL')) {
								$sendto[getDolGlobalString('TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL')] = getDolGlobalString('TICKET_PUBLIC_NOTIFICATION_NEW_MESSAGE_DEFAULT_EMAIL');
							} elseif (getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO')) {
								$sendto[getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO')] = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO');
							}
						}

						// Add global email address recipient
						if (getDolGlobalString('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS') &&
							getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO') && !array_key_exists(getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO'), $sendto)
						) {
							$sendto[getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO')] = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO');
						}

						if (!empty($sendto)) {
							$appli = getDolGlobalString('MAIN_APPLICATION_TITLE', $mysoc->name);

							$subject = '['.$appli.'- ticket #'.$object->track_id.'] '.$this->subject;

							// Message send
							$message = getDolGlobalString('TICKET_MESSAGE_MAIL_INTRO', $langs->trans('TicketMessageMailIntroText'));
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
					 * Message send from the Backoffice / Private area
					 *
					 * Send emails to internal users (linked contacts) then, if private is not set, to external users (linked contacts or thirdparty email if no contact set)
					 */
					if ($send_email > 0) {
						// Retrieve internal contact datas
						$internal_contacts = $object->getInfosTicketInternalContact(1);

						$sendto = array();
						if (is_array($internal_contacts) && count($internal_contacts) > 0) {
							// Set default subject
							$appli = getDolGlobalString('MAIN_APPLICATION_TITLE', $mysoc->name);

							$subject = GETPOST('subject', 'alphanohtml') ? GETPOST('subject', 'alphanohtml') : '['.$appli.' - '.$langs->trans("Ticket").' #'.$object->track_id.'] '.$langs->trans('TicketNewMessage');

							$message_intro = $langs->trans('TicketNotificationEmailBody', "#".$object->id);
							$message_signature = GETPOST('mail_signature') ? GETPOST('mail_signature') : getDolGlobalString('TICKET_MESSAGE_MAIL_SIGNATURE');

							$message = getDolGlobalString('TICKET_MESSAGE_MAIL_INTRO', $langs->trans('TicketMessageMailIntroText'));
							$message .= '<br><br>';
							$messagePost = GETPOST('message', 'restricthtml');
							if (!dol_textishtml($messagePost)) {
								$messagePost = dol_nl2br($messagePost);
							}
							$message .= $messagePost;

							// Data about customer
							$message .= '<br><br>';
							$message .= "==============================================<br>";
							$message .= !empty($object->thirdparty->name) ? $langs->trans('Thirdparty')." : ".$object->thirdparty->name : '';
							$message .= !empty($object->thirdparty->town) ? '<br>'.$langs->trans('Town')." : ".$object->thirdparty->town : '';
							$message .= !empty($object->thirdparty->phone) ? '<br>'.$langs->trans('Phone')." : ".$object->thirdparty->phone : '';

							// Build array to display recipient list
							foreach ($internal_contacts as $key => $info_sendto) {
								// Avoid duplicate notifications
								if ($info_sendto['id'] == $user->id) {
									continue;
								}

								if ($info_sendto['email'] != '') {
									if (!empty($info_sendto['email'])) {
										$sendto[$info_sendto['email']] = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'])." <".$info_sendto['email'].">";
									}

									// Contact type
									$recipient = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1').' ('.strtolower($info_sendto['libelle']).')';
									$message .= (!empty($recipient) ? $langs->trans('TicketNotificationRecipient').' : '.$recipient.'<br>' : '');
								}
							}
							$message .= '<br>';
							// URL ticket
							$url_internal_ticket = dol_buildpath('/ticket/card.php', 2).'?track_id='.$object->track_id;

							// Add html link on url
							$message .= '<br>'.$langs->trans('TicketNotificationEmailBodyInfosTrackUrlinternal').' : <a href="'.$url_internal_ticket.'">'.$object->track_id.'</a><br>';

							// Add global email address recipient
							if (getDolGlobalString('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS') && !array_key_exists(getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO'), $sendto)) {
								if (getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO')) {
									$sendto[getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO')] = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO');
								}
							}

							// don't try to send email if no recipient
							if (!empty($sendto)) {
								$this->sendTicketMessageByEmail($subject, $message, '', $sendto, $listofpaths, $listofmimes, $listofnames);
							}
						}

						/*
						 * Send emails for externals users if not private (linked contacts)
						 */
						if (empty($object->private)) {
							// Retrieve email of all contacts (external)
							$external_contacts = $object->getInfosTicketExternalContact(1);

							// If no contact, get email from thirdparty
							if (is_array($external_contacts) && count($external_contacts) === 0) {
								if (!empty($object->fk_soc)) {
									$object->fetch_thirdparty($object->fk_soc);
									$array_company = array(array('firstname' => '', 'lastname' => $object->thirdparty->name, 'email' => $object->thirdparty->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $object->thirdparty->id));
									$external_contacts = array_merge($external_contacts, $array_company);
								} elseif (empty($object->fk_soc) && !empty($object->origin_replyto)) {
									$array_external = array(array('firstname' => '', 'lastname' => $object->origin_replyto, 'email' => $object->origin_replyto, 'libelle' => $langs->transnoentities('Customer'), 'socid' => 0));
									$external_contacts = array_merge($external_contacts, $array_external);
								} elseif (empty($object->fk_soc) && !empty($object->origin_email)) {
									$array_external = array(array('firstname' => '', 'lastname' => $object->origin_email, 'email' => $object->thirdparty->email, 'libelle' => $langs->transnoentities('Customer'), 'socid' => $object->thirdparty->id));
									$external_contacts = array_merge($external_contacts, $array_external);
								}
							}

							$sendto = array();
							if (is_array($external_contacts) && count($external_contacts) > 0) {
								// Get default subject for email to external contacts
								$appli = getDolGlobalString('MAIN_APPLICATION_TITLE', $mysoc->name);

								$subject = GETPOST('subject') ? GETPOST('subject') : '['.$appli.' - '.$langs->trans("Ticket").' #'.$object->track_id.'] '.$langs->trans('TicketNewMessage');

								$message_intro = GETPOST('mail_intro') ? GETPOST('mail_intro', 'restricthtml') : getDolGlobalString('TICKET_MESSAGE_MAIL_INTRO');
								$message_signature = GETPOST('mail_signature') ? GETPOST('mail_signature', 'restricthtml') : getDolGlobalString('TICKET_MESSAGE_MAIL_SIGNATURE');
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
									// avoid duplicate emails to external contacts
									if ($info_sendto['id'] == $user->contact_id) {
										continue;
									}

									if ($info_sendto['email'] != '' && $info_sendto['email'] != $object->origin_email) {
										if (!empty($info_sendto['email'])) {
											$sendto[$info_sendto['email']] = trim($info_sendto['firstname']." ".$info_sendto['lastname'])." <".$info_sendto['email'].">";
										}

										$recipient = dolGetFirstLastname($info_sendto['firstname'], $info_sendto['lastname'], '-1').' ('.strtolower($info_sendto['libelle']).')';
										$message .= (!empty($recipient) ? $langs->trans('TicketNotificationRecipient').' : '.$recipient.'<br>' : '');
									}
								}

								// If public interface is not enable, use link to internal page into mail
								$url_public_ticket = (getDolGlobalInt('TICKET_ENABLE_PUBLIC_INTERFACE') ?
										(getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE') !== '' ? getDolGlobalString('TICKET_URL_PUBLIC_INTERFACE') . '/view.php' : dol_buildpath('/public/ticket/view.php', 2)) : dol_buildpath('/ticket/card.php', 2)).'?track_id='.$object->track_id;

								$message .= '<br>'.$langs->trans('TicketNewEmailBodyInfosTrackUrlCustomer').' : <a href="'.$url_public_ticket.'">'.$object->track_id.'</a><br>';

								// Build final message
								$message = $message_intro.'<br><br>'.$message;

								// Add signature
								$message .= '<br>'.$message_signature;

								if (!empty($object->origin_replyto)) {
									$sendto[$object->origin_replyto] = $object->origin_replyto;
								} elseif (!empty($object->origin_email)) {
									$sendto[$object->origin_email] = $object->origin_email;
								}

								if ($object->fk_soc > 0 && !array_key_exists($object->origin_replyto, $sendto) && !array_key_exists($object->origin_email, $sendto)) {
									$object->socid = $object->fk_soc;
									$object->fetch_thirdparty();
									if (!empty($object->thirdparty->email)) {
										$sendto[$object->thirdparty->email] = $object->thirdparty->email;
									}
								}

								// Add global email address recipient
								if (getDolGlobalString('TICKET_NOTIFICATION_ALSO_MAIN_ADDRESS') && !array_key_exists(getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO'), $sendto)) {
									if (getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO')) {
										$sendto[getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO')] = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_TO');
									}
								}

								// Don't try to send email when no recipient
								if (!empty($sendto)) {
									$result = $this->sendTicketMessageByEmail($subject, $message, '', $sendto, $listofpaths, $listofmimes, $listofnames);
									if ($result) {
										// update last_msg_sent date (for last message sent to external users)
										$this->date_last_msg_sent = dol_now();
										$this->update($user, 1);	// disable trigger when updating date_last_msg_sent. sendTicketMessageByEmail already create an event in actioncomm table.
									}
								}
							}
						}
					}
				}

				// Set status back to "In progress" if not set yet, but only if internal user and not a private message
				// Or set status to "In progress" if the client has answered and if the ticket has started
				// So we are sure to leave the STATUS_DRAFT, STATUS_NEED_INFO.
				if (($object->status < self::STATUS_IN_PROGRESS && !$user->socid && !$private) ||
					($object->status > self::STATUS_IN_PROGRESS && $public_area)
				) {
					$object->setStatut($object::STATUS_IN_PROGRESS);
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
	 * @param int    $send_internal_cc 	  Receive a copy on internal email (getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM')
	 * @param array  $array_receiver   	  Array of receiver. Example array('name' => 'John Doe', 'email' => 'john@doe.com', etc...)
	 * @param array	 $filename_list       List of files to attach (full path of filename on file system)
	 * @param array	 $mimetype_list       List of MIME type of attached files
	 * @param array	 $mimefilename_list   List of attached file name in message
	 * @return boolean     					True if mail sent to at least one receiver, false otherwise
	 */
	public function sendTicketMessageByEmail($subject, $message, $send_internal_cc = 0, $array_receiver = array(), $filename_list = array(), $mimetype_list = array(), $mimefilename_list = array())
	{
		global $conf, $langs, $user;

		if (getDolGlobalString('TICKET_DISABLE_ALL_MAILS')) {
			dol_syslog(get_class($this).'::sendTicketMessageByEmail: Emails are disable into ticket setup by option TICKET_DISABLE_ALL_MAILS', LOG_WARNING);
			return false;
		}

		$langs->load("mails");

		include_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
		//$contactstatic = new Contact($this->db);

		// If no receiver defined, load all ticket linked contacts
		if (!is_array($array_receiver) || !count($array_receiver) > 0) {
			$array_receiver = $this->getInfosTicketInternalContact(1);
			$array_receiver = array_merge($array_receiver, $this->getInfosTicketExternalContact(1));
		}

		$sendtocc = '';
		if ($send_internal_cc) {
			$sendtocc = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM');
		}

		$from = getDolGlobalString('TICKET_NOTIFICATION_EMAIL_FROM');
		$is_sent = false;
		if (is_array($array_receiver) && count($array_receiver) > 0) {
			foreach ($array_receiver as $key => $receiver) {
				$deliveryreceipt = 0;
				$filepath = $filename_list;
				$filename = $mimefilename_list;
				$mimetype = $mimetype_list;

				// Send email

				$old_MAIN_MAIL_AUTOCOPY_TO = getDolGlobalString('MAIN_MAIL_AUTOCOPY_TO');
				if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
					$conf->global->MAIN_MAIL_AUTOCOPY_TO = '';
				}

				$upload_dir_tmp = $conf->user->dir_output."/".$user->id.'/temp';

				include_once DOL_DOCUMENT_ROOT.'/core/class/CMailFile.class.php';
				$trackid = "tic".$this->id;

				$moreinheader = 'X-Dolibarr-Info: sendTicketMessageByEmail'."\r\n";
				if (!empty($this->email_msgid)) {
					// We must also add 1 entry In-Reply-To: <$this->email_msgid> with Message-ID we respond from (See RFC5322).
					$moreinheader .= 'In-Reply-To: <'.$this->email_msgid.'>'."\r\n";
					// TODO We should now be able to give the in_reply_to as a dedicated parameter of new CMailFile() instead of into $moreinheader.
				}

				// We should add here also a header 'References:'
				// According to RFC5322, we should add here all the References fields of the initial message concatenated with
				// the Message-ID of the message we respond from (but each ID must be once).
				$references = '';
				if (!empty($this->origin_references)) {		// $this->origin_references should be '<'.$this->origin_references.'>'
					$references .= (empty($references) ? '' : ' ').$this->origin_references;
				}
				if (!empty($this->email_msgid) && !preg_match('/'.preg_quote($this->email_msgid, '/').'/', $references)) {
					$references .= (empty($references) ? '' : ' ').'<'.$this->email_msgid.'>';
				}
				if ($references) {
					$moreinheader .= 'References: '.$references."\r\n";
					// TODO We should now be able to give the references as a dedicated parameter of new CMailFile() instead of into $moreinheader.
				}

				$mailfile = new CMailFile($subject, $receiver, $from, $message, $filepath, $mimetype, $filename, $sendtocc, '', $deliveryreceipt, -1, '', '', $trackid, $moreinheader, 'ticket', '', $upload_dir_tmp);

				if ($mailfile->error) {
					setEventMessages($mailfile->error, null, 'errors');
				} else {
					$result = $mailfile->sendfile();
					if ($result) {
						setEventMessages($langs->trans('MailSuccessfulySent', $mailfile->getValidAddress($from, 2), $mailfile->getValidAddress($receiver, 2)), null, 'mesgs');
						$is_sent = true;
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

				if (getDolGlobalString('TICKET_DISABLE_MAIL_AUTOCOPY_TO')) {
					$conf->global->MAIN_MAIL_AUTOCOPY_TO = $old_MAIN_MAIL_AUTOCOPY_TO;
				}
			}
		} else {
			$langs->load("other");
			setEventMessages($langs->trans('ErrorMailRecipientIsEmptyForSendTicketMessage'), null, 'warnings');
		}

		return $is_sent;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load indicators for dashboard (this->nbtodo and this->nbtodolate)
	 *
	 *  @param          User	$user   Object user
	 *  @param          int		$mode   "opened" for askprice to close, "signed" for proposal to invoice
	 *  @return         WorkboardResponse|int             Return integer <0 if KO, WorkboardResponse if OK
	 */
	public function load_board($user, $mode)
	{
		// phpcs:enable
		global $user, $langs;

		$now = dol_now();
		$delay_warning = 0;

		$clause = " WHERE";

		$sql = "SELECT p.rowid, p.ref, p.datec as datec";
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket as p";
		if (isModEnabled('societe') && !$user->hasRight('societe', 'client', 'voir') && !$user->socid) {
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

	/**
	 *      Load indicator this->nb of global stats widget
	 *
	 *      @return     int         Return integer <0 if ko, >0 if ok
	 */
	public function loadStateBoard()
	{
		global $user;

		$this->nb = array();
		$clause = "WHERE";

		$sql = "SELECT count(p.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."ticket as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON p.fk_soc = s.rowid";
		if (!$user->hasRight('societe', 'client', 'voir')) {
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

	/**
	 *	Return clicable link of object (with eventually picto)
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
		$return .= img_picto('', $this->picto);
		$return .= '</span>';
		$return .= '<div class="info-box-content">';
		$return .= '<span class="info-box-ref inline-block tdoverflowmax150 valignmiddle">'.(method_exists($this, 'getNomUrl') ? $this->getNomUrl(1) : $this->ref).'</span>';
		if ($selected >= 0) {
			$return .= '<input id="cb'.$this->id.'" class="flat checkforselect fright" type="checkbox" name="toselect[]" value="'.$this->id.'"'.($selected ? ' checked="checked"' : '').'>';
		}
		if (!empty($arraydata['user_assignment'])) {
			$return .= '<br><span class="info-box-label" title="'.dol_escape_htmltag($langs->trans("AssignedTo")).'">'.$arraydata['user_assignment'].'</span>';
		}
		if (property_exists($this, 'type_code') && !empty($this->type_code)) {
			$return .= '<br>';
			$return .= '<div class="tdoverflowmax125 inline-block">'.$langs->getLabelFromKey($this->db, 'TicketTypeShort'.$this->type_code, 'c_ticket_type', 'code', 'label', $this->type_code).'</div>';
		}
		if (method_exists($this, 'getLibStatut')) {
			$return .= '<br><div class="info-box-status">'.$this->getLibStatut(3).'</div>';
		}
		$return .= '</div>';
		$return .= '</div>';
		$return .= '</div>';

		return $return;
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 *  @param	    string		$modele			Force template to use ('' to not force)
	 *  @param		Translate	$outputlangs	object lang a utiliser pour traduction
	 *  @param      int			$hidedetails    Hide details of lines
	 *  @param      int			$hidedesc       Hide description
	 *  @param      int			$hideref        Hide ref
	 *  @param      null|array  $moreparams     Array to provide more information
	 *  @return     int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $langs;

		$langs->load("ticket");
		$outputlangs->load("ticket");

		if (!dol_strlen($modele)) {
			$modele = 'generic_ticket_odt';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (getDolGlobalString('TICKET_ADDON_PDF')) {
				$modele = getDolGlobalString('TICKET_ADDON_PDF');
			}
		}

		$modelpath = "core/modules/ticket/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
	}
}
