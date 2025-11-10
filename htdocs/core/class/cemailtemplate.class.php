<?php
/* Copyright (C) 2005-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011	Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2015-2017	Marcos García			<marcosgdf@gmail.com>
 * Copyright (C) 2015-2017	Nicolas ZABOURI			<info@inovea-conseil.com>
 * Copyright (C) 2018-2024  Frédéric France			<frederic.france@free.fr>
 * Copyright (C) 2022		Charlene Benke			<charlene@patas-monkey.com>
 * Copyright (C) 2023		Anthony Berton			<anthony.berton@bb2a.fr>
 * Copyright (C) 2024-2025	MDW						<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2025  		Jon Bendtsen         	<jon.bendtsen.github@jonb.dk>
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
require_once DOL_DOCUMENT_ROOT.'/core/class/doldeprecationhandler.class.php';


/**
 * Object of table llx_c_email_templates
 */
class CEmailTemplate extends CommonObject
{
	const TRIGGER_PREFIX = 'EMAILTEMPLATE';
	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'email_template';

	/**
	 * @var string 	Name of table without prefix where object is stored. This is also the key used for extrafields management (so extrafields know the link to the parent table).
	 */
	public $table_element = 'c_email_templates';


	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array<string,array{type:string,label:string,langfile?:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-6,6>|string,alwayseditable?:int<0,1>|string,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,cssview?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>|string,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>,searchmulti?:int<0,1>}>	Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		"rowid" => array("type" => "integer", "label" => "TechnicalID", 'enabled' => 1, 'position' => 10, 'notnull' => 1, 'visible' => -1,),
		"module" => array("type" => "varchar(32)", "label" => "Module", 'enabled' => 1, 'position' => 20, 'notnull' => 0, 'visible' => -1,),
		"type_template" => array("type" => "varchar(32)", "label" => "Typetemplate", 'enabled' => 1, 'position' => 25, 'notnull' => 0, 'visible' => -1,),
		"lang" => array("type" => "varchar(6)", "label" => "Lang", 'enabled' => 1, 'position' => 30, 'notnull' => 0, 'visible' => -1,),
		"private" => array("type" => "smallint(6)", "label" => "Private", 'enabled' => 1, 'position' => 35, 'notnull' => 1, 'visible' => -1,),
		"fk_user" => array("type" => "integer:User:user/class/user.class.php", "label" => "Fkuser", 'enabled' => 1, 'position' => 40, 'notnull' => 0, 'visible' => -1, "css" => "maxwidth500 widthcentpercentminusxx", "csslist" => "tdoverflowmax150",),
		"datec" => array("type" => "datetime", "label" => "DateCreation", 'enabled' => 1, 'position' => 45, 'notnull' => 0, 'visible' => -1,),
		"tms" => array("type" => "timestamp", "label" => "DateModification", 'enabled' => 1, 'position' => 50, 'notnull' => 1, 'visible' => -1,),
		"label" => array("type" => "varchar(255)", "label" => "Label", 'enabled' => 1, 'position' => 55, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1, "css" => "minwidth300", "cssview" => "wordbreak", "csslist" => "tdoverflowmax150",),
		"position" => array("type" => "smallint(6)", "label" => "Position", 'enabled' => 1, 'position' => 60, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"active" => array("type" => "integer", "label" => "Active", 'enabled' => 1, 'position' => 65, 'notnull' => 1, 'visible' => -1, 'alwayseditable' => 1,),
		"topic" => array("type" => "text", "label" => "Topic", 'enabled' => 1, 'position' => 70, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"content" => array("type" => "mediumtext", "label" => "Content", 'enabled' => 1, 'position' => 75, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"content_lines" => array("type" => "text", "label" => "Contentlines", "enabled" => "getDolGlobalString('MAIN_EMAIL_TEMPLATES_FOR_OBJECT_LINES')", 'position' => 80, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"enabled" => array("type" => "varchar(255)", "label" => "Enabled", 'enabled' => 1, 'position' => 85, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"joinfiles" => array("type" => "varchar(255)", "label" => "Joinfiles", 'enabled' => 1, 'position' => 90, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"email_from" => array("type" => "varchar(255)", "label" => "Emailfrom", 'enabled' => 1, 'position' => 95, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"email_to" => array("type" => "varchar(255)", "label" => "Emailto", 'enabled' => 1, 'position' => 100, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"email_tocc" => array("type" => "varchar(255)", "label" => "Emailtocc", 'enabled' => 1, 'position' => 105, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"email_tobcc" => array("type" => "varchar(255)", "label" => "Emailtobcc", 'enabled' => 1, 'position' => 110, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
		"defaultfortype" => array("type" => "smallint(6)", "label" => "Defaultfortype", 'enabled' => 1, 'position' => 115, 'notnull' => 0, 'visible' => -1, 'alwayseditable' => 1,),
	);
	/**
	 * @var int
	 */
	public $rowid;
	/**
	 * @var string type of the template
	 */
	public $type_template;
	/**
	 * @var int|string
	 */
	public $datec;
	/**
	 * @var int
	 */
	public $tms;
	/**
	 * @var int
	 */
	public $active;
	/**
	 * @var string if 0 hidden from GUI, if 1 visible in GUI
	 */
	public $enabled;
	/**
	 * @var int is the template a default or not
	 */
	public $defaultfortype;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var string 	Model mail label
	 */
	public $label;

	/**
	 * @var int Owner of email template
	 */
	public $fk_user;

	/**
	 * @var int Is template private
	 */
	public $private;

	/**
	 * @var string Model mail topic
	 */
	public $topic;

	/**
	 * @var string 	Model mail content
	 */
	public $content;
	/**
	 * @var string 	Model to use to generate the string with each lines
	 */
	public $content_lines;

	/**
	 * @var string language of the template
	 */
	public $lang;
	/**
	 * @var int<0,1>
	 */
	public $joinfiles;

	/**
	 * @var string sender email address
	 */
	public $email_from;

	/**
	 * @var string recipient email address
	 */
	public $email_to;

	/**
	 * @var string Additional visible recipients
	 */
	public $email_tocc;

	/**
	 * @var string additional hidden recipients
	 */
	public $email_tobcc;

	/**
	 * @var string Module the template is dedicated for
	 */
	public $module;

	/**
	 * @var int Position of template in a combo list
	 */
	public $position;
	// END MODULEBUILDER PROPERTIES



	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs;

		$this->db = $db;
		$this->ismultientitymanaged = 1;
		$this->isextrafieldmanaged = 1;

		// @phan-suppress-next-line PhanTypeMismatchProperty
		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid']) && !empty($this->fields['ref'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->hasRight('test', 'mailtemplate', 'read')) {
		 $this->fields['myfield']['visible'] = 1;
		 $this->fields['myfield']['noteditable'] = 0;
		 }*/

		// Unset fields that are disabled
		foreach ($this->fields as $key => $val) {
			if (isset($val['enabled']) && empty($val['enabled'])) {
				unset($this->fields[$key]);
			}
		}

		// Translate some data of arrayofkeyval
		if (is_object($langs)) {
			foreach ($this->fields as $key => $val) {
				if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) {
					foreach ($val['arrayofkeyval'] as $key2 => $val2) {
						$this->fields[$key]['arrayofkeyval'][$key2] = $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 *	Create email template
	 *  Required fields: label, type_template, topic
	 *
	 *	@param		User	$user 		Object user that make creation
	 *	@param		int	    $notrigger	Disable all triggers
	 *	@return 	int			        Return integer <0 if KO, >0 if OK
	 */
	public function create($user, $notrigger = 0)
	{
		global $conf;
		$error = 0;

		dol_syslog(get_class($this)."::create user=".$user->id);

		// Check parameters
		if (!empty($this->label)) {	// We check that label is not already used
			$result = $this->isExistingObject($this->element, 0, $this->label); // Check label is not yet used
			if ($result > 0) {
				$this->error = 'ErrorLabelAlreadyExists';
				dol_syslog(get_class($this)."::create ".$this->error, LOG_WARNING);
				$this->db->rollback();
				return -1;
			}
		}

		$now = dol_now();

		$this->db->begin();

		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (entity,";
		$sql .= " module, type_template, lang, private, fk_user, datec, label,";
		$sql .= " position, defaultfortype, enabled, active, email_from, email_to,";
		$sql .= " email_tocc, email_tobcc, topic, joinfiles, content, content_lines)";
		$sql .= " VALUES (";
		$sql .= " ".((int) $conf->entity).",";
		if (is_null($this->module)) {
			$sql .= " NULL,";
		} else {
			$sql .= " '".$this->db->escape($this->module)."',";
		}
		$sql .= " '".$this->db->escape($this->type_template)."',";
		if (is_null($this->lang)) {
			$sql .= " NULL,";
		} else {
			$sql .= " '".$this->db->escape($this->lang)."',";
		}
		$sql .= " ".((int) $this->private).",";
		if (is_null($this->fk_user)) {
			$sql .= " NULL,";
		} else {
			$sql .= " '".((int) $this->fk_user)."',";
		}
		if (is_null($this->datec)) {
			$sql .= " '".$this->db->idate($now)."',";
		} else {
			$sql .= " '".$this->db->idate($this->datec)."',";
		}
		$sql .= " '".$this->db->escape($this->label)."',";
		$sql .= " ".((int) $this->position).", ".((int) $this->defaultfortype).",";
		if (is_null($this->enabled)) {
			$sql .= " 1,";
		} else {
			$sql .= " '".((int) $this->enabled)."',";
		}
		if (is_null($this->active)) {
			$sql .= " 1,";
		} else {
			$sql .= " '".((int) $this->active)."',";
		}
		if (is_null($this->email_from)) {
			$sql .= " NULL,";
		} else {
			$sql .= " '".$this->db->escape($this->email_from)."',";
		}
		if (is_null($this->email_to)) {
			$sql .= " NULL,";
		} else {
			$sql .= " '".$this->db->escape($this->email_to)."',";
		}
		if (is_null($this->email_tocc)) {
			$sql .= " NULL,";
		} else {
			$sql .= " '".$this->db->escape($this->email_tocc)."',";
		}
		if (is_null($this->email_tobcc)) {
			$sql .= " NULL,";
		} else {
			$sql .= " '".$this->db->escape($this->email_tobcc)."',";
		}
		$sql .= " '".$this->db->escape($this->topic)."',";
		$sql .= " ".((int) $this->joinfiles).",";
		if (is_null($this->content)) {
			$sql .= " NULL,";
		} else {
			$sql .= " '".((string) $this->db->escape($this->content))."',";
		}
		if (is_null($this->content_lines)) {
			$sql .= " NULL";
		} else {
			$sql .= " '".((string) $this->db->escape($this->content_lines))."'";
		}
		$sql .= ")";


		dol_syslog(get_class($this)."::create", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

			if (!$notrigger) {
				// Call trigger
				$result = $this->call_trigger(self::TRIGGER_PREFIX.'_CREATE', $user);
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
				return -1 * $error;
			}
		} else {
			$this->error = $this->db->lasterror();
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 *      Update database with changed email template
	 *
	 *      @param      User	$user        	User that modify
	 *      @param      int		$notrigger	    0=launch triggers after, 1=disable triggers
	 *      @return     int      			   	Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		$error = 0;

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " module=".($this->module ? "'".$this->db->escape($this->module)."', " : 'NULL, ');
		$sql .= " type_template=".($this->type_template ? "'".$this->db->escape($this->type_template)."', " : 'NULL, ');
		$sql .= " lang=".($this->lang ? "'".$this->db->escape($this->lang)."', " : 'NULL, ');
		$sql .= " private=".((int) $this->private).",";
		$sql .= " fk_user=".((int) $this->fk_user).",";
		$sql .= " label=".($this->label ? "'".$this->db->escape($this->label)."', " : 'NULL, ');
		$sql .= " position=".((int) $this->position).",";
		$sql .= " defaultfortype=".((int) $this->defaultfortype).",";
		$sql .= " enabled=".($this->enabled ? "'".$this->db->escape($this->enabled)."', " : 'NULL, ');
		$sql .= " active=".((int) $this->active).",";
		$sql .= " email_from=".($this->email_from ? "'".$this->db->escape($this->email_from)."', " : 'NULL, ');
		$sql .= " email_to=".($this->email_to ? "'".$this->db->escape($this->email_to)."', " : 'NULL, ');
		$sql .= " email_tocc=".($this->email_tocc ? "'".$this->db->escape($this->email_tocc)."', " : 'NULL, ');
		$sql .= " email_tobcc=".($this->email_tobcc ? "'".$this->db->escape($this->email_tobcc)."', " : 'NULL, ');
		$sql .= " topic=".($this->topic ? "'".$this->db->escape($this->topic)."', " : 'NULL, ');
		$sql .= " joinfiles=".((int) $this->joinfiles).",";
		$sql .= " content=".($this->content ? "'".$this->db->escape($this->content)."', " : 'NULL, ');
		$sql .= " content_lines=".($this->content_lines ? "'".$this->db->escape($this->content_lines)."'" : 'NULL');
		$sql .= " WHERE rowid=".((int) $this->id);

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error && !$notrigger) {
			// Call trigger
			$result = $this->call_trigger(self::TRIGGER_PREFIX.'_MODIFY', $user);
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
	 *	Delete the email template
	 *
	 *	@param	User	$user		User object
	 *	@param	int		$notrigger	1=Does not execute triggers, 0= execute triggers
	 * 	@return	int					Return integer <=0 if KO, >0 if OK
	 */
	public function delete($user, $notrigger = 0)
	{
		$error = 0;

		dol_syslog(get_class($this)."::delete ".$this->id, LOG_DEBUG);

		$this->db->begin();

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger(self::TRIGGER_PREFIX.'_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		// Delete object link
		if (!$error) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element." WHERE rowid = ".((int) $this->id);
			$res = $this->db->query($sql);
			if (!$res) {
				$error++;
				$this->error = $this->db->lasterror();
				$this->errors[] = $this->error;
				dol_syslog(get_class($this)."::delete error ".$this->error, LOG_ERR);
			}
		}

		if (!$error) {
			dol_syslog(get_class($this)."::delete ".$this->id." by ".$user->id, LOG_DEBUG);
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param 	int    	$id   			Id object
	 * @param 	string 	$label  			Ref
	 * @param	int		$noextrafields	0=Default to load extrafields, 1=No extrafields
	 * @param	int		$nolines		0=Default to load extrafields, 1=No extrafields
	 * @return 	int     				Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $label = null, $noextrafields = 0, $nolines = 0)
	{
		// The table llx_c_email_templates has no field ref. The field ref was named "label" instead. So we change the call to fetchCommon.
		//$result = $this->fetchCommon($id, $label, '', $noextrafields);
		$result = $this->fetchCommon($id, '', " AND t.label = '".$this->db->escape($label)."'", $noextrafields);

		if ($result > 0 && !empty($this->table_element_line) && empty($nolines)) {
			$this->fetchLines($noextrafields);
		}
		return $result;
	}

	/**
	 *	Get email template from database.
	 *
	 *	@param      int			$id       	row Id of email template
	 *	@param      string		$label    	label of email template
	 *	@return     int         			>0 if OK, <0 if KO, 0 if not found
	 */
	public function apifetch($id, $label = '')
	{
		// Check parameters
		if (($id == 0 || empty($id)) && empty($label)) {
			dol_syslog(get_class($this)."::apifetch id and label are empty", LOG_DEBUG);
			$this->error = 'id='.$id.' and label are empty';
			return -1;
		}

		$sql = "SELECT e.rowid, e.entity, e.module, e.type_template, e.lang,";
		$sql .= " e.private, e.fk_user, e.datec, e.tms, e.label, e.position,";
		$sql .= " e.defaultfortype, e.enabled, e.active, e.email_from, e.email_to,";
		$sql .= " e.email_tocc, e.email_tobcc, e.topic, e.joinfiles, e.content,";
		$sql .= " e.content_lines FROM ".$this->db->prefix().$this->table_element." as e";
		if ($id) {
			$sql .= " WHERE e.rowid = ".((int) $id);
		} else {
			$sql .= " WHERE e.entity IN (".getEntity($this->table_element).")";
			if ($label) {
				$sql .= " AND e.label = '".$this->db->escape($label)."'";
			}
		}

		dol_syslog(get_class($this)."::apifetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = (int) $obj->rowid;
				$this->entity = (int) $obj->entity;

				$this->active = (int) $obj->active;
				$this->content = (string) $obj->content;
				$this->content_lines = (string) $obj->content_lines;
				$this->datec = $this->db->jdate($obj->datec);
				$this->defaultfortype = (int) $obj->defaultfortype;
				$this->email_from = (string) $obj->email_from;
				$this->email_to = (string) $obj->email_to;
				$this->email_tobcc = (string) $obj->email_tobcc;
				$this->email_tocc = (string) $obj->email_tocc;
				$this->enabled = (string) $obj->enabled;
				$this->fk_user = (int) $obj->fk_user;
				$this->joinfiles = (int) $obj->joinfiles;
				$this->label = (string) $obj->label;
				$this->lang = (string) $obj->lang;
				$this->module = (string) $obj->module;
				$this->position = (int) $obj->position;
				$this->private = (int) $obj->private;
				$this->tms = $this->db->jdate($obj->tms);
				$this->topic = (string) $obj->topic;
				$this->type_template = (string) $obj->type_template;

				// direct copy from facture.class.php
				$this->date_creation = $this->db->jdate($obj->datec);

				return 1;
			} else {
				if ($id) {
					$this->error = 'Email template with id '.((string) $id).' not found sql='.$sql;
				} elseif ($label) {
					$this->error = 'Email template with label '.$label.' not found sql='.$sql;
				}
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}
}

/**
 * Old class name for Object of table llx_c_email_templates
 * I prefer the CEmailTemplate name as it better reflects the database
 *
 * @deprecated Use now class CEmailTemplate
 */
class ModelMail extends CEmailTemplate
{
	// just another name for compatibility
}
