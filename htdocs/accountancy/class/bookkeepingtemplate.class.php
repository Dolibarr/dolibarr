<?php
/* Copyright (C) 2024       AWeerWolf
 * Copyright (C) 2026       Alexandre Spangaro		<alexandre@inovea-conseil.com>
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
 * \file    accountancy/class/bookkeepingtemplate.class.php
 * \ingroup accountancy
 * \brief   This file is a CRUD class file for BookkeepingTemplate (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
require_once DOL_DOCUMENT_ROOT . '/accountancy/class/bookkeepingtemplateline.class.php';

/**
 * Class for BookkeepingTemplate
 */
class BookkeepingTemplate extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'accountancy';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'accounting_transaction_template';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'accounting_transaction_template';

	/**
	 * @var int<0,1>|string		0=No test on entity, 1=Test with field entity, 2=Test with link by societe
	 */
	public $ismultientitymanaged = 1;

	/**
	 * @var int<0,1>            Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for bookkeepingtemplate. Must be a 'fa-xxx' fontawesome code (or 'fa-xxx_fa_color_size') or 'bookkeepingtemplate@accountancy' if picto is file 'img/object_bookkeepingtemplate.png'.
	 */
	public $picto = 'fa-list';

	/**
	 *  'type' if the field format ('integer', 'integer:ObjectClass:PathToClass[:AddCreateButtonOrNot[:Filter]]', 'varchar(x)', 'double(24,8)', 'real', 'price', 'text', 'html', 'date', 'datetime', 'timestamp', 'duration', 'mail', 'phone', 'url', 'password')
	 *         Note: Filter can be a string like "(t.ref:like:'SO-%') or (t.date_creation:<:'20160101') or (t.nature:is:NULL)"
	 *  'label' the translation key.
	 *  'langfile' the key of the language file for translation.
	 *  'enabled' is a condition when the field must be managed.
	 *  'position' is the sort order of field.
	 *  'notnull' is set to 1 if not null in database. Set to -1 if we must set data to null if empty ('' or 0).
	 *  'visible' says if field is visible in list (Examples: 0=Not visible, 1=Visible on list and create/update/view forms, 2=Visible on list only, 3=Visible on create/update/view form only (not list), 4=Visible on list and update/view form only (not create). 5=Visible on list and view only (not create/not update). Using a negative value means field is not shown by default on list but can be selected for viewing)
	 *  'noteditable' says if field is not editable (1 or 0)
	 *  'default' is a default value for creation (can still be overwritten by the Setup of Default Values if the field is editable in creation form). Note: If default is set to '(PROV)' and field is 'ref', the default value will be set to '(PROVid)' where id is rowid when a new record is created.
	 *  'index' if we want an index in database.
	 *  'foreignkey'=>'tablename.field' if the field is a foreign key (it is recommended to name the field fk_...).
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
	 * @var array<string,array{type:string,label:string,langfile?:string,enabled:int<0,2>|string,position:int,notnull?:int,visible:int<-6,6>|string,alwayseditable?:int<0,1>|string,noteditable?:int<0,1>,default?:string,index?:int,foreignkey?:string,searchall?:int<0,1>,isameasure?:int<0,1>,css?:string,cssview?:string,csslist?:string,help?:string,showoncombobox?:int<0,4>|string,disabled?:int<0,1>,arrayofkeyval?:array<int|string,string>,autofocusoncreate?:int<0,1>,comment?:string,copytoclipboard?:int<1,2>,validate?:int<0,1>,showonheader?:int<0,1>,searchmulti?:int<0,1>,picto?:string}>    Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		"rowid" => array("type" => "integer", "label" => "TechnicalID", "enabled" => 1, 'position' => 1, 'notnull' => 1, "visible" => 0, "noteditable" => 1, "index" => 1, "css" => "left", "comment" => "Id"),
		"entity" => array("type" => "integer", "label" => "Entity", "enabled" => 1, 'position' => 5, 'notnull' => 1, "visible" => 0, "index" => 1, "default" => '1'),
		"code" => array("type" => "varchar(128)", "label" => "Code", "enabled" => 1, 'position' => 10, 'notnull' => 1, "visible" => 1, "index" => 2, "searchall" => 1, "css" => "minwidth300", "help" => "UniqueCodeForTemplate"),
		"label" => array("type" => "varchar(255)", "label" => "Label", "enabled" => 1, 'position' => 20, 'notnull' => 0, "visible" => 1, "css" => "minwidth300"),
		"date_creation" => array("type" => "datetime", "label" => "DateCreation", "enabled" => 1, 'position' => 500, 'notnull' => 1, "visible" => -2),
		"tms" => array("type" => "timestamp", "label" => "DateModification", "enabled" => 1, 'position' => 501, 'notnull' => 0, "visible" => -2),
		"fk_user_creat" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserAuthor", "picto" => "user", "enabled" => 1, 'position' => 510, 'notnull' => 1, "visible" => "-2", "csslist" => "tdoverflowmax150"),
		"fk_user_modif" => array("type" => "integer:User:user/class/user.class.php", "label" => "UserModif", "picto" => "user", "enabled" => 1, 'position' => 511, 'notnull' => -1, "visible" => "-2", "csslist" => "tdoverflowmax150"),
		"import_key" => array("type" => "varchar(14)", "label" => "ImportId", "enabled" => 1, 'position' => 1000, 'notnull' => -1, "visible" => -2),
	);
	// END MODULEBUILDER PROPERTIES

	/**
	 * @var int ID
	 */
	public $rowid;

	/**
	 * @var int Entity
	 */
	public $entity;

	/**
	 * @var string Code
	 */
	public $code;

	/**
	 * @var string Label
	 */
	public $label;

	/**
	 * @var int Unix timestamp of creation date
	 */
	public $date_creation;

	/**
	 * @var int Unix timestamp of last modification
	 */
	public $tms;

	/**
	 * @var int User ID who created
	 */
	public $fk_user_creat;

	/**
	 * @var int|null User ID who modified
	 */
	public $fk_user_modif;

	/**
	 * @var string|null Import key
	 */
	public $import_key;

	/**
	 * @var string Name of subtable line
	 */
	public $table_element_line = 'accounting_transaction_template_det';

	/**
	 * @var BookkeepingTemplateLine[] Array of subtable lines
	 */
	public $lines = array();


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (!getDolGlobalInt('MAIN_SHOW_TECHNICAL_ID') && isset($this->fields['rowid'], $this->fields['code']) && is_array($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (!isModEnabled('multicompany') && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

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
						$this->fields[$key]['arrayofkeyval'][$key2] = (string) $langs->trans($val2);
					}
				}
			}
		}
	}

	/**
	 * Create object into database
	 *
	 * @param   User    $user       User that creates
	 * @param   int     $notrigger  0=launch triggers after, 1=disable triggers
	 * @return  int                 Return integer <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = 0)
	{
		$resultcreate = $this->createCommon($user, $notrigger);
		return $resultcreate;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param User $user User that creates
	 * @param int $fromid Id of object to clone
	 * @return mixed         New object created, <0 if KO
	 */
	public function createFromClone(User $user, $fromid)
	{
		global $langs, $extrafields;
		$error = 0;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$object = new self($this->db);

		$this->db->begin();

		// Load source object
		$result = $object->fetchCommon($fromid);
		if ($result > 0 && !empty($object->table_element_line)) {
			$object->fetchLines();
		}

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);

		// Clear fields
		$object->code = "COPY_" . $object->code;
		$object->label = $langs->trans("CopyOf") . " " . $object->label;
		$object->date_creation = dol_now();

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);

		if ($result < 0) {
			$error++;
			$this->setErrorsFromObject($object);
		}

		if (!$error) {
			// Copy Attached Lines
			$this->getLinesArray();
			foreach ($this->lines as $linetoclone) {
				// Clear some info
				unset($linetoclone->id);
				unset($linetoclone->fk_user_creat);
				$linetoclone->fk_transaction_template = $object->id;

				// Save in DB
				$linetoclone->createCommon($user);
			}
		}

		unset($object->context['createfromclone']);

		// End
		if (!$error) {
			$this->db->commit();
			return $object;
		} else {
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Load object in memory from the database
	 *
	 * @param int $id Id object
	 * @param string $code Code
	 * @param int $noextrafields 0=Default to load extrafields, 1=No extrafields
	 * @param int $nolines 0=Default to load lines, 1=No lines
	 * @return int                    Return integer <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $code = null, $noextrafields = 0, $nolines = 0)
	{
		$result = $this->fetchCommon($id, $code, '', $noextrafields);
		if ($result > 0 && !empty($this->table_element_line) && empty($nolines)) {
			$this->fetchLines($noextrafields);
		}
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @param   int                             $noextrafields  0=Default to load extrafields, 1=No extrafields
	 * @return  BookkeepingTemplateLine[]|int                   Array of line objects if OK, <0 if KO
	 */
	public function fetchLines($noextrafields = 0)
	{
		$this->lines = array();

		$objectline = new BookkeepingTemplateLine($this->db);
		$result = $objectline->fetchAll('ASC', 'rowid', 0, 0, array('customsql' => 'fk_transaction_template = ' . ((int) $this->id)));

		if (is_numeric($result)) {
			$this->setErrorsFromObject($objectline);
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param   User      $user       User that modifies
	 * @param   int<0,1>  $notrigger  0=launch triggers after, 1=disable triggers
	 * @return  int                  Return integer <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = 0)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param   User        $user       User that deletes
	 * @param   int<0,1>    $notrigger  0=launch triggers after, 1=disable triggers
	 * @return  int                     Return integer <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = 0)
	{
		global $conf, $langs;

		$error = 0;

		$this->db->begin();

		// Delete the Lines first
		$obj_line = new BookkeepingTemplateLine($this->db);

		$sql = "DELETE FROM ".$this->db->prefix().$obj_line->table_element;
		$sql .= " WHERE fk_transaction_template = ".((int) $this->id);

		dol_syslog(get_class($this)."::delete sql=".$sql, LOG_DEBUG);

		$resql = $this->db->query($sql);
		if (!$resql) {
			$this->errors[] = $this->db->lasterror();
			$error++;
			dol_syslog(get_class($this)."::delete error deleting lines: ".$this->db->lasterror(), LOG_ERR);
		}

		if (empty($error)) {
			// Call trigger
			if (!$notrigger) {
				$result = $this->call_trigger(strtoupper(get_class($this)).'_DELETE', $user);
				if ($result < 0) {
					$error++;
				}
			}
		}

		if (empty($error)) {
			// Delete main object
			$sql = "DELETE FROM ".$this->db->prefix().$this->table_element;
			$sql .= " WHERE rowid = ".((int) $this->id);

			dol_syslog(get_class($this)."::delete sql=".$sql, LOG_DEBUG);

			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->errors[] = $this->db->lasterror();
				$this->error = $this->db->lasterror();
				$error++;
				dol_syslog(get_class($this)."::delete error deleting main object: ".$this->db->lasterror(), LOG_ERR);
			}
		}

		// Commit or rollback
		if (empty($error)) {
			$this->db->commit();
			return 1;
		} else {
			$this->error = implode(', ', $this->errors);
			$this->db->rollback();
			return -1;
		}
	}

	/**
	 * Delete a line of object in database
	 *
	 * @param   User        $user       User that delete
	 * @param   int         $idline     Id of line to delete
	 * @param   int<0,1>    $notrigger  0=launch triggers after, 1=disable triggers
	 * @return  int                     >0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = 0)
	{
		$tmpline = new BookkeepingTemplateLine($this->db);
		if ($tmpline->fetch($idline) > 0) {
			return $tmpline->delete($user);
		}

		return -1;
	}

	/**
	 * Create an array of lines
	 *
	 * @return BookkeepingTemplateLine[]|int  array of BookkeepingTemplateLine objects if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new BookkeepingTemplateLine($this->db);
		$result = $objectline->fetchAll('ASC', 'rowid', 0, 0, array('customsql' => 'fk_transaction_template = ' . ((int) $this->id)));

		if (is_numeric($result)) {
			$this->setErrorsFromObject($objectline);
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 * Returns the reference to the following non used object depending on the active numbering module.
	 *
	 * @return string  Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("accountancy");

		// Standard numbering - Generate a code based on timestamp
		$code = 'TPL' . dol_print_date(dol_now(), '%Y%m%d%H%M%S');

		return $code;
	}

	/**
	 * Return a link to the object card (with optionally the picto)
	 *
	 * @param int $withpicto Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 * @param string $option On what the link point to ('nolink', ...)
	 * @param int $notooltip 1=Disable tooltip
	 * @param string $morecss Add more css on link
	 * @param int $save_lastsearch_value -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return string                         String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1;
		}

		$result = '';

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans("BookkeepingTemplate") . '</u>';
		if (isset($this->code)) {
			$label .= '<br><b>' . $langs->trans('Code') . ':</b> ' . $this->code;
		}
		if (isset($this->label)) {
			$label .= '<br><b>' . $langs->trans('Label') . ':</b> ' . $this->label;
		}

		$url = DOL_URL_ROOT . '/accountancy/admin/template/card.php?id=' . $this->id;

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
			if (getDolGlobalInt('MAIN_OPTIMIZEFORTEXTBROWSER')) {
				$label = $langs->trans("ShowBookkeepingTemplate");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else {
			$linkclose = ($morecss ? ' class="' . $morecss . '"' : '');
		}

		if ($option == 'nolink') {
			$linkstart = '<span';
		} else {
			$linkstart = '<a href="' . $url . '"';
		}
		$linkstart .= $linkclose . '>';
		if ($option == 'nolink') {
			$linkend = '</span>';
		} else {
			$linkend = '</a>';
		}

		$result .= $linkstart;

		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
		}

		if ($withpicto != 2) {
			$result .= $this->code;
		}

		$result .= $linkend;

		return $result;
	}

	/**
	 * Return label of status
	 *
	 * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string     Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut(1, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

	/**
	 * Return label of a given status
	 *
	 * @param int $status Status
	 * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string       Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		global $langs;

		return '';
	}
}
