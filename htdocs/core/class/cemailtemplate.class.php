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
 *
 *
 */
class cEmailTemplate extends CommonObject
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
	 * @var array<string,array{type:string,label:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-6,6>|string,alwayseditable?:int<0,1>,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>}>	Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
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
	 * @var null|string type of the template
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
	 * @var null|string
	 */
	public $enabled;
	/**
	 * @var null|int is the template a default or not
	 */
	public $defaultfortype;

	/**
	 * @var int ID
	 */
	public $id;

	/**
	 * @var null|string Model mail label
	 */
	public $label;

	/**
	 * @var null|int Owner of email template
	 */
	public $fk_user;

	/**
	 * @var int Is template private
	 */
	public $private;

	/**
	 * @var null|string Model mail topic
	 */
	public $topic;

	/**
	 * @var null|string 	Model mail content
	 */
	public $content;
	/**
	 * @var null|string 	Model to use to generate the string with each lines
	 */
	public $content_lines;

	/**
	 * @var null|string language of the template
	 */
	public $lang;
	/**
	 * @var int<0,1>
	 */
	public $joinfiles;

	/**
	 * @var null|string sender email address
	 */
	public $email_from;

	/**
	 * @var null|string recipient email address
	 */
	public $email_to;

	/**
	 * @var null|string Additional visible recipients
	 */
	public $email_tocc;

	/**
	 * @var null|string additional hidden recipients
	 */
	public $email_tobcc;

	/**
	 * @var string Module the template is dedicated for
	 */
	public $module;

	/**
	 * @var null|int Position of template in a combo list
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
		$this->ismultientitymanaged = 0;
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
		if (empty($id) && empty($label)) {
			return -1;
		}

		$sql = "SELECT e.rowid, e.entity, e.module, e.type_template, e.lang,";
		$sql .= " e.private, e.fk_user, e.datec, e.tms, e.label, e.position,";
		$sql .= " e.defaultfortype, e.enabled, e.active, e.email_from, e.email_to,";
		$sql .= " e.email_tocc, e.email_tobcc, e.topic, e.joinfiles, e.content,";
		$sql .= " e.content_lines FROM ".$this->db->prefix().$this->table_element." as e";
		if ($id) {
			$sql .= " WHERE e.rowid = ".((int) $id);
		}
		if ($label) {
			$sql .= " WHERE e.label = '".((string) $label)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$obj = $this->db->fetch_object($result);
			if ($obj) {
				$this->id = (int) $obj->rowid;
				$this->entity = (int) $obj->entity;

				$this->active = (int) $obj->active;
				$this->content = (string) $obj->content;
				$this->content_lines = (string) $obj->content_lines;
				$this->datec = (int) $obj->datec;
				$this->defaultfortype = (int) $obj->defaultfortype;
				$this->email_from = (string) $obj->email_from;
				$this->email_to = (string) $obj->email_to;
				$this->email_tobcc = (string) $obj->email_tobcc;
				$this->email_tocc = (string) $obj->email_tocc;
				$this->enabled = (string) $obj->enabled;
				$this->fk_user = (int) $obj->fk_user;
				$this->joinfiles = (string) $obj->joinfiles;
				$this->label = (string) $obj->label;
				$this->lang = (string) $obj->lang;
				$this->module = (string) $obj->module;
				$this->position = (int) $obj->position;
				$this->private = (int) $obj->private;
				$this->tms = $obj->tms;
				$this->topic = (string) $obj->topic;
				$this->type_template = (string) $obj->type_template;

				// direct copy from facture.class.php
				$this->date_creation = $this->db->jdate($obj->datec);
                
				return 1;
			} else {
				$this->error = 'Email template with id '.((string) $rowid).' not found sql='.$sql;
				return 0;
			}
		} else {
			$this->error = $this->db->error();
			return -1;
		}
	}
}

// I prefer the cEmailTemplate name as it better reflects the database
class ModelMail extends cEmailTemplate
{
	// just another name
}
