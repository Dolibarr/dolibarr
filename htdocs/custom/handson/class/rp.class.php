<?php
/* Copyright (C) 2017  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) ---Put here your own copyright and developer email---
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
 * \file        class/rp.class.php
 * \ingroup     handson
 * \brief       This file is a CRUD class file for RP (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for RP
 */
class RP extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'handson';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'rp';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'handson_rp';

	/**
	 * @var int  Does this object support multicompany module ?
	 * 0=No test on entity, 1=Test with field entity, 'field@table'=Test with link by field@table
	 */
	public $ismultientitymanaged = 0;

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 1;

	/**
	 * @var string String with name of icon for rp. Must be the part after the 'object_' into object_rp.png
	 */
	public $picto = 'object_project';


	const STATUS_DRAFT = 0;
	const STATUS_VALIDATED = 1;
	const STATUS_CANCELED = 9;


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
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'css'=>'minwidth300 maxwidth500 widthcentpercentminusx', 'cssview'=>'wordbreak', 'csslist'=>'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arrayofkeyval' to set a list of values if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel"). Note that type can be 'integer' or 'varchar'
	 *  'autofocusoncreate' to have field having the focus on a create form. Only 1 field should have this property set to 1.
	 *  'comment' is not used. You can store here any text of your choice. It is not used by application.
	 *
	 *  Note: To have value dynamic, you can set value to 0 in definition and edit the value on the fly into the constructor.
	 */

	// BEGIN MODULEBUILDER PROPERTIES
	/**
	 * @var array  Array with all fields and their property. Do not use it as a static var. It may be modified by constructor.
	 */
	public $fields = array(
		'rowid' => array('type' => 'integer', 'label' => 'TechnicalID', 'enabled' => '1', 'position' => 1, 'notnull' => 1, 'visible' => 0, 'noteditable' => '1', 'index' => 1, 'css' => 'left', 'comment' => "Id"),
		'ref' => array('type' => 'varchar(128)', 'label' => 'Name', 'enabled' => '1', 'position' => 20, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'css'=>'tdoverflowmax200', 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'contacts' => array('type' => 'contactlist', 'label' => 'Kontaktpersonen', 'enabled' => '1', 'position' => 53, 'notnull' => -1, 'visible' => 1, 'css' => 'maxwidth300', 'index' => 1, 'help' => "Kontaktpersonen des RP",),
		'season' => array('type' => 'chkbxlst:handson_saison:ref', 'label' => 'Saison', 'enabled' => 1, 'notnull' => -1, 'position' => '54', 'visible' => 1, 'css'=>'maxwidth200', 'index' => 1, 'help' => 'In welchen Saisons war dieser RP aktiv?'),
		'program' => array('type' => 'chkbxlst:handson_programm:ref', 'label' => 'Bildungsprogramm', 'enabled' => 1, 'notnull' => -1, 'position' => '55', 'visible' => 1, 'css'=>'maxwidth200', 'index' => 1, 'help' => 'An welchen Programmen nimmt dieser RP teil?'),
		'region' => array('type' => 'chkbxlst:handson_region:ref', 'label' => 'Region', 'enabled' => 1, 'notnull' => -1, 'position' => '56', 'visible' => 1, 'css'=>'maxwidth200', 'index' => 1, 'help' => 'Welche Regionen betreut dieser RP?'),
		'fk_soc' => array('type' => 'integer:Societe:societe/class/societe.class.php AND entity IN (__SHARED_ENTITIES__)', 'label' => 'Rechnungsadresse (Geschäftspartner)', 'css' => 'maxwidth300', 'enabled' => '1', 'position' => 500, 'notnull' => -1, 'visible' => 1, 'index' => 1, 'help' => "LinkToThirparty",),
		'shipping' => array('type' => 'integer:Contact:contact/class/contact.class.php', 'label' => 'Lieferadresse', 'enabled' => '1', 'position' => 501, 'css' => 'maxwidth300', 'notnull' => -1, 'visible' => 1, 'index' => 1, 'help' => "Lieferadresse des RP",),
		'contract_adr' => array('type' => 'integer:Contact:contact/class/contact.class.php', 'label' => 'Vertragsadresse', 'enabled' => '1', 'position' => 502, 'css' => 'maxwidth300', 'notnull' => -1, 'visible' => 1, 'index' => 1, 'help' => "Link zum Vertragskontakt",),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 500, 'notnull' => 1, 'visible' => -2,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 501, 'notnull' => 0, 'visible' => -2,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 511, 'notnull' => -1, 'visible' => -2,),
		'last_main_doc' => array('type' => 'varchar(255)', 'label' => 'LastMainDoc', 'enabled' => '1', 'position' => 600, 'notnull' => 0, 'visible' => 0,),
		'url' => array('type'=>'varchar(255)', 'label'=>'URL', 'enabled'=>'1', 'position'=>601, 'notnull'=>0, 'visible'=>0,),
		'note_public' => array('type'=>'html', 'label'=>'NotePublic', 'enabled'=>'1', 'position'=>602, 'notnull'=>0, 'visible'=>0,),
		'import_key' => array('type' => 'varchar(14)', 'label' => 'ImportId', 'enabled' => '1', 'position' => 1000, 'notnull' => -1, 'visible' => -2,),
		'model_pdf' => array('type' => 'varchar(255)', 'label' => 'Model pdf', 'enabled' => '1', 'position' => 1010, 'notnull' => -1, 'visible' => 0,),
	);
	public $rowid;
	public $ref;
	public $label;
	public $amount;
	public $qty;
	public $fk_soc;
	public $fk_project;
	public $description;
	public $note_public;
	public $note_private;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	public $last_main_doc;
	public $import_key;
	public $model_pdf;
	public $status;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'handson_rpline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_rp';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'RPline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('handson_rpdet');

	// /**
	//  * @var RPLine[]     Array of subtable lines
	//  */
	// public $lines = array();


	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $conf, $langs;

		$this->db = $db;

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) {
			$this->fields['rowid']['visible'] = 0;
		}
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) {
			$this->fields['entity']['enabled'] = 0;
		}

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->handson->rp->read) {
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
	 * Create object into database
	 *
	 * @param User $user User that creates
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, Id of created object if OK
	 */
	public function create(User $user, $notrigger = false)
	{
		$resultcreate = $this->createCommon($user, $notrigger);

		//$resultvalidate = $this->validate($user, $notrigger);

		return $resultcreate;
	}

	/**
	 * Clone an object into another one
	 *
	 * @param User $user User that creates
	 * @param int $fromid Id of object to clone
	 * @return    mixed                New object created, <0 if KO
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

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) {
			$object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_" . $object->ref : $this->fields['ref']['default'];
		}
		if (property_exists($object, 'label')) {
			$object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf") . " " . $object->label : $this->fields['label']['default'];
		}
		if (property_exists($object, 'status')) {
			$object->status = self::STATUS_DRAFT;
		}
		if (property_exists($object, 'date_creation')) {
			$object->date_creation = dol_now();
		}
		if (property_exists($object, 'date_modification')) {
			$object->date_modification = null;
		}
		// ...
		// Clear extrafields that are unique
		if (is_array($object->array_options) && count($object->array_options) > 0) {
			$extrafields->fetch_name_optionals_label($this->table_element);
			foreach ($object->array_options as $key => $option) {
				$shortkey = preg_replace('/options_/', '', $key);
				if (!empty($extrafields->attributes[$this->table_element]['unique'][$shortkey])) {
					//var_dump($key); var_dump($clonedObj->array_options[$key]); exit;
					unset($object->array_options[$key]);
				}
			}
		}

		// Create clone
		$object->context['createfromclone'] = 'createfromclone';
		$result = $object->createCommon($user);
		if ($result < 0) {
			$error++;
			$this->error = $object->error;
			$this->errors = $object->errors;
		}

		if (!$error) {
			// copy internal contacts
			if ($this->copy_linked_contact($object, 'internal') < 0) {
				$error++;
			}
		}

		if (!$error) {
			// copy external contacts if same company
			if (property_exists($this, 'fk_soc') && $this->fk_soc == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0) {
					$error++;
				}
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
	 * @param string $ref Ref
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetch($id, $ref = null)
	{
		$result = $this->fetchCommon($id, $ref);
		if ($result > 0 && !empty($this->table_element_line)) {
			$this->fetchLines();
		}
		return $result;
	}

	/**
	 * Load object lines in memory from the database
	 *
	 * @return int         <0 if KO, 0 if not found, >0 if OK
	 */
	public function fetchLines()
	{
		$this->lines = array();

		$result = $this->fetchLinesCommon();
		return $result;
	}


	/**
	 * Load list of objects in memory from the database.
	 *
	 * @param string $sortorder Sort Order
	 * @param string $sortfield Sort field
	 * @param int $limit limit
	 * @param int $offset Offset
	 * @param array $filter Filter array. Example array('field'=>'valueforlike', 'customurl'=>...)
	 * @param string $filtermode Filter mode (AND or OR)
	 * @return array|int                 int <0 if KO, array of pages if OK
	 */
	public function fetchAll($sortorder = '', $sortfield = '', $limit = 0, $offset = 0, array $filter = array(), $filtermode = 'AND')
	{
		global $conf;

		dol_syslog(__METHOD__, LOG_DEBUG);

		$records = array();

		$sql = 'SELECT ';
		$sql .= $this->getFieldList('t');
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) {
			$sql .= ' WHERE t.entity IN (' . getEntity($this->table_element) . ')';
		} else {
			$sql .= ' WHERE 1 = 1';
		}
		// Manage filter
		$sqlwhere = array();
		if (count($filter) > 0) {
			foreach ($filter as $key => $value) {
				if ($key == 't.rowid') {
					$sqlwhere[] = $key . '=' . $value;
				} elseif (in_array($this->fields[$key]['type'], array('date', 'datetime', 'timestamp'))) {
					$sqlwhere[] = $key . ' = \'' . $this->db->idate($value) . '\'';
				} elseif ($key == 'customsql') {
					$sqlwhere[] = $value;
				} elseif (strpos($value, '%') === false) {
					$sqlwhere[] = $key . ' IN (' . $this->db->sanitize($this->db->escape($value)) . ')';
				} else {
					$sqlwhere[] = $key . ' LIKE \'%' . $this->db->escape($value) . '%\'';
				}
			}
		}
		if (count($sqlwhere) > 0) {
			$sql .= ' AND (' . implode(' ' . $filtermode . ' ', $sqlwhere) . ')';
		}

		if (!empty($sortfield)) {
			$sql .= $this->db->order($sortfield, $sortorder);
		}
		if (!empty($limit)) {
			$sql .= ' ' . $this->db->plimit($limit, $offset);
		}

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < ($limit ? min($limit, $num) : $num)) {
				$obj = $this->db->fetch_object($resql);

				$record = new self($this->db);
				$record->setVarsFromFetchObj($obj);

				$records[$record->id] = $record;

				$i++;
			}
			$this->db->free($resql);

			return $records;
		} else {
			$this->errors[] = 'Error ' . $this->db->lasterror();
			dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);

			return -1;
		}
	}

	/**
	 * Update object into database
	 *
	 * @param User $user User that modifies
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function update(User $user, $notrigger = false)
	{
		return $this->updateCommon($user, $notrigger);
	}

	/**
	 * Delete object in database
	 *
	 * @param User $user User that deletes
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int             <0 if KO, >0 if OK
	 */
	public function delete(User $user, $notrigger = false)
	{
		return $this->deleteCommon($user, $notrigger);
		//return $this->deleteCommon($user, $notrigger, 1);
	}

	/**
	 *  Delete a line of object in database
	 *
	 * @param User $user User that delete
	 * @param int $idline Id of line to delete
	 * @param bool $notrigger false=launch triggers after, true=disable triggers
	 * @return int                >0 if OK, <0 if KO
	 */
	public function deleteLine(User $user, $idline, $notrigger = false)
	{
		if ($this->status < 0) {
			$this->error = 'ErrorDeleteLineNotAllowedByObjectStatus';
			return -2;
		}

		return $this->deleteLineCommon($user, $idline, $notrigger);
	}


	/**
	 *    Validate object
	 *
	 * @param User $user User making status change
	 * @param int $notrigger 1=Does not execute triggers, 0= execute triggers
	 * @return    int                        <=0 if OK, 0=Nothing done, >0 if KO
	 */
	public function validate($user, $notrigger = 0)
	{
		global $conf, $langs;

		require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

		$error = 0;

		// Protection
		if ($this->status == self::STATUS_VALIDATED) {
			dol_syslog(get_class($this) . "::validate action abandonned: already validated", LOG_WARNING);
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->handson->rp->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->handson->rp->rp_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) { // empty should not happened, but when it occurs, the test save life
			$num = $this->getNextNumRef();
		} else {
			$num = $this->ref;
		}
		$this->newref = $num;

		if (!empty($num)) {
			// Validate
			$sql = "UPDATE " . MAIN_DB_PREFIX . $this->table_element;
			$sql .= " SET ref = '" . $this->db->escape($num) . "',";
			$sql .= " status = " . self::STATUS_VALIDATED;
			if (!empty($this->fields['date_validation'])) {
				$sql .= ", date_validation = '" . $this->db->idate($now) . "'";
			}
			if (!empty($this->fields['fk_user_valid'])) {
				$sql .= ", fk_user_valid = " . ((int)$user->id);
			}
			$sql .= " WHERE rowid = " . ((int)$this->id);

			dol_syslog(get_class($this) . "::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('RP_VALIDATE', $user);
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
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ")), filepath = 'rp/" . $this->db->escape($this->newref) . "'";
				$sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'rp/" . $this->db->escape($this->ref) . "' and entity = " . $conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->handson->dir_output . '/rp/' . $oldref;
				$dirdest = $conf->handson->dir_output . '/rp/' . $newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this) . "::validate() rename dir " . $dirsource . " into " . $dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->handson->dir_output . '/rp/' . $newref, 'files', 1, '^' . preg_quote($oldref, '/'));
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

		// Set new ref and current status
		if (!$error) {
			$this->ref = $num;
			$this->status = self::STATUS_VALIDATED;
		}

		if (!$error) {
			$this->db->commit();
			return 1;
		} else {
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *    Set draft status
	 *
	 * @param User $user Object user that modify
	 * @param int $notrigger 1=Does not execute triggers, 0=Execute triggers
	 * @return    int                        <0 if KO, >0 if OK
	 */
	public function setDraft($user, $notrigger = 0)
	{
		// Protection
		if ($this->status <= self::STATUS_DRAFT) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->handson->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->handson->handson_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'RP_UNVALIDATE');
	}

	/**
	 *    Set cancel status
	 *
	 * @param User $user Object user that modify
	 * @param int $notrigger 1=Does not execute triggers, 0=Execute triggers
	 * @return    int                        <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function cancel($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_VALIDATED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->handson->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->handson->handson_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'RP_CANCEL');
	}

	/**
	 *    Set back to validated status
	 *
	 * @param User $user Object user that modify
	 * @param int $notrigger 1=Does not execute triggers, 0=Execute triggers
	 * @return    int                        <0 if KO, 0=Nothing done, >0 if OK
	 */
	public function reopen($user, $notrigger = 0)
	{
		// Protection
		if ($this->status != self::STATUS_CANCELED) {
			return 0;
		}

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->handson->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->handson->handson_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'RP_REOPEN');
	}

	/**
	 *  Return a link to the object card (with optionaly the picto)
	 *
	 * @param int $withpicto Include picto in link (0=No picto, 1=Include picto into link, 2=Only picto)
	 * @param string $option On what the link point to ('nolink', ...)
	 * @param int $notooltip 1=Disable tooltip
	 * @param string $morecss Add more css on link
	 * @param int $save_lastsearch_value -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 * @return    string                              String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		if (!empty($conf->dol_no_mouse_hover)) {
			$notooltip = 1; // Force disable tooltips
		}

		$result = '';

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans("RP") . '</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		$url = dol_buildpath('/handson/rp_card.php', 1) . '?id=' . $this->id;

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
				$label = $langs->trans("ShowRP");
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

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) {
				$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
			}
		} else {
			if ($withpicto) {
				require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

				list($class, $module) = explode('@', $this->picto);
				$upload_dir = $conf->$module->multidir_output[$conf->entity] . "/$class/" . dol_sanitizeFileName($this->ref);
				$filearray = dol_dir_list($upload_dir, "files");
				$filename = $filearray[0]['name'];
				if (!empty($filename)) {
					$pospoint = strpos($filearray[0]['name'], '.');

					$pathtophoto = $class . '/' . $this->ref . '/thumbs/' . substr($filename, 0, $pospoint) . '_mini' . substr($filename, $pospoint);
					if (empty($conf->global->{strtoupper($module . '_' . $class) . '_FORMATLISTPHOTOSASUSERS'})) {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><div class="photoref"><img class="photo' . $module . '" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div></div>';
					} else {
						$result .= '<div class="floatleft inline-block valignmiddle divphotoref"><img class="photouserphoto userphoto" alt="No photo" border="0" src="' . DOL_URL_ROOT . '/viewimage.php?modulepart=' . $module . '&entity=' . $conf->entity . '&file=' . urlencode($pathtophoto) . '"></div>';
					}

					$result .= '</div>';
				} else {
					$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
				}
			}
		}

		if ($withpicto != 2) {
			$result .= $this->ref;
		}

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('rpdao'));
		$parameters = array('id' => $this->id, 'getnomurl' => $result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) {
			$result = $hookmanager->resPrint;
		} else {
			$result .= $hookmanager->resPrint;
		}

		return $result;
	}

	/**
	 *  Return the label of the status
	 *
	 * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return    string                   Label of status
	 */
	public function getLabelStatus($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	/**
	 *  Return the label of the status
	 *
	 * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return    string                   Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->LibStatut($this->status, $mode);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

	/**
	 *  Return the status
	 *
	 * @param int $status Id status
	 * @param int $mode 0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return string                   Label of status
	 */
	public function LibStatut($status, $mode = 0)
	{
		// phpcs:enable
		if (empty($this->labelStatus) || empty($this->labelStatusShort)) {
			global $langs;
			//$langs->load("handson@handson");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->trans('Disabled');
		}

		$statusType = 'status' . $status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) {
			$statusType = 'status6';
		}

		return dolGetStatus($this->labelStatus[$status], $this->labelStatusShort[$status], '', $statusType, $mode);
	}

	/**
	 *    Load the info information in the object
	 *
	 * @param int $id Id of object
	 * @return    void
	 */
	public function info($id)
	{
		$sql = 'SELECT rowid, date_creation as datec, tms as datem,';
		$sql .= ' fk_user_creat, fk_user_modif';
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		$sql .= ' WHERE t.rowid = ' . ((int)$id);
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

				if ($obj->fk_user_valid) {
					$vuser = new User($this->db);
					$vuser->fetch($obj->fk_user_valid);
					$this->user_validation = $vuser;
				}

				if ($obj->fk_user_cloture) {
					$cluser = new User($this->db);
					$cluser->fetch($obj->fk_user_cloture);
					$this->user_cloture = $cluser;
				}

				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->datem);
				$this->date_validation = $this->db->jdate($obj->datev);
			}

			$this->db->free($result);
		} else {
			dol_print_error($this->db);
		}
	}

	/**
	 * Initialise object with example values
	 * Id must be 0 if object instance is a specimen
	 *
	 * @return void
	 */
	public function initAsSpecimen()
	{
		// Set here init that are not commonf fields
		// $this->property1 = ...
		// $this->property2 = ...

		$this->initAsSpecimenCommon();
	}

	/**
	 *    Create an array of lines
	 *
	 * @return array|int        array of lines if OK, <0 if KO
	 */
	public function getLinesArray()
	{
		$this->lines = array();

		$objectline = new RPLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql' => 'fk_rp = ' . ((int)$this->id)));

		if (is_numeric($result)) {
			$this->error = $this->error;
			$this->errors = $this->errors;
			return $result;
		} else {
			$this->lines = $result;
			return $this->lines;
		}
	}

	/**
	 *  Returns the reference to the following non used object depending on the active numbering module.
	 *
	 * @return string            Object free reference
	 */
	public function getNextNumRef()
	{
		global $langs, $conf;
		$langs->load("handson@handson");

		if (empty($conf->global->HANDSON_RP_ADDON)) {
			$conf->global->HANDSON_RP_ADDON = 'mod_rp_standard';
		}

		if (!empty($conf->global->HANDSON_RP_ADDON)) {
			$mybool = false;

			$file = $conf->global->HANDSON_RP_ADDON . ".php";
			$classname = $conf->global->HANDSON_RP_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array)$conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir . "core/modules/handson/");

				// Load file with numbering class (if found)
				$mybool |= @include_once $dir . $file;
			}

			if ($mybool === false) {
				dol_print_error('', "Failed to include file " . $file);
				return '';
			}

			if (class_exists($classname)) {
				$obj = new $classname();
				$numref = $obj->getNextValue($this);

				if ($numref != '' && $numref != '-1') {
					return $numref;
				} else {
					$this->error = $obj->error;
					//dol_print_error($this->db,get_class($this)."::getNextNumRef ".$obj->error);
					return "";
				}
			} else {
				print $langs->trans("Error") . " " . $langs->trans("ClassNotFound") . ' ' . $classname;
				return "";
			}
		} else {
			print $langs->trans("ErrorNumberingModuleNotSetup", $this->element);
			return "";
		}
	}

	/**
	 *  Create a document onto disk according to template module.
	 *
	 * @param string $modele Force template to use ('' to not force)
	 * @param Translate $outputlangs objet lang a utiliser pour traduction
	 * @param int $hidedetails Hide details of lines
	 * @param int $hidedesc Hide description
	 * @param int $hideref Hide ref
	 * @param null|array $moreparams Array to provide more information
	 * @return     int                        0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0, $moreparams = null)
	{
		global $conf, $langs;

		$result = 0;
		$includedocgeneration = 0;

		$langs->load("handson@handson");

		if (!dol_strlen($modele)) {
			$modele = 'standard_rp';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->RP_ADDON_PDF)) {
				$modele = $conf->global->RP_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/handson/doc/";

		if ($includedocgeneration && !empty($modele)) {
			$result = $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref, $moreparams);
		}

		return $result;
	}

	/**
	 * Action executed by scheduler
	 * CAN BE A CRON TASK. In such a case, parameters come from the schedule job setup field 'Parameters'
	 * Use public function doScheduledJob($param1, $param2, ...) to get parameters
	 *
	 * @return    int            0 if OK, <>0 if KO (this function is used also by cron so only 0 is OK)
	 */
	public function doScheduledJob()
	{
		global $conf, $langs;

		//$conf->global->SYSLOG_FILE = 'DOL_DATA_ROOT/dolibarr_mydedicatedlofile.log';

		$error = 0;
		$this->output = '';
		$this->error = '';

		dol_syslog(__METHOD__, LOG_DEBUG);

		$now = dol_now();

		$this->db->begin();

		// ...

		$this->db->commit();

		return $error;
	}


	/**
	 * Return HTML string to put an input field into a page
	 * Code very similar with showInputField of extra fields
	 *
	 * @param array $val Array of properties for field to show (used only if ->fields not defined)
	 * @param string $key Key of attribute
	 * @param string|array $value Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value, for array type must be array)
	 * @param string $moreparam To add more parameters on html input tag
	 * @param string $keysuffix Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param string $keyprefix Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param string|int $morecss Value for css to define style/length of field. May also be a numeric.
	 * @param int $nonewbutton Force to not show the new button on field that are links to object
	 * @return string
	 */
	public function showInputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = 0, $nonewbutton = 0)
	{
		global $conf, $langs, $form;

		if (!is_object($form)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
			$form = new Form($this->db);
		}

		if (!empty($this->fields)) {
			$val = $this->fields[$key];
		}

		$out = '';
		$type = '';
		$isDependList = 0;
		$param = array();
		$param['options'] = array();
		$reg = array();
		$size = !empty($this->fields[$key]['size']) ? $this->fields[$key]['size'] : 0;
		// Because we work on extrafields
		if (preg_match('/^(integer|link):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] . ':' . $reg[5] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(integer|link):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] => 'N');
			$type = 'link';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] . ':' . $reg[5] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(sellist):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^(chkbxlst):(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] . ':' . $reg[5] => 'N');
			$type = 'chkbxlst';
		} elseif (preg_match('/^(chkbxlst):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$type = 'chkbxlst';
		} elseif (preg_match('/^(chkbxlst):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[2] . ':' . $reg[3] => 'N');
			$type = 'chkbxlst';
		} elseif (preg_match('/varchar\((\d+)\)/', $val['type'], $reg)) {
			$param['options'] = array();
			$type = 'varchar';
			$size = $reg[1];
		} elseif
		(preg_match('/varchar/', $val['type'])) {
			$param['options'] = array();
			$type = 'varchar';
		} elseif
		(is_array($this->fields[$key]['arrayofkeyval'])) {
			$param['options'] = $this->fields[$key]['arrayofkeyval'];
			$type = 'select';
		} else {
			$param['options'] = array();
			$type = $this->fields[$key]['type'];
		}

		$label = $this->fields[$key]['label'];
		//$elementtype=$this->fields[$key]['elementtype'];	// Seems not used
		$default = $this->fields[$key]['default'];
		$computed = $this->fields[$key]['computed'];
		$unique = $this->fields[$key]['unique'];
		$required = $this->fields[$key]['required'];
		$autofocusoncreate = $this->fields[$key]['autofocusoncreate'];

		$langfile = $this->fields[$key]['langfile'];
		$list = $this->fields[$key]['list'];
		$hidden = (in_array(abs($this->fields[$key]['visible']), array(0, 2)) ? 1 : 0);

		$objectid = $this->id;

		if ($computed) {
			if (!preg_match('/^search_/', $keyprefix)) return '<span class="opacitymedium">' . $langs->trans("AutomaticallyCalculated") . '</span>';
			else return '';
		}

		// Set value of $morecss. For this, we use in priority showsize from parameters, then $val['css'] then autodefine
		if (empty($morecss) && !empty($val['css'])) {
			$morecss = $val['css'];
		} elseif (empty($morecss)) {
			if ($type == 'date') {
				$morecss = 'minwidth100imp';
			} elseif ($type == 'datetime' || $type == 'link') {    // link means an foreign key to another primary id
				$morecss = 'minwidth200imp';
			} elseif (in_array($type, array('int', 'integer', 'price')) || preg_match('/^double(\([0-9],[0-9]\)){0,1}/', $type)) {
				$morecss = 'maxwidth75';
			} elseif ($type == 'url') {
				$morecss = 'minwidth400';
			} elseif ($type == 'boolean') {
				$morecss = '';
			} else {
				if (round($size) < 12) {
					$morecss = 'minwidth100';
				} elseif (round($size) <= 48) {
					$morecss = 'minwidth200';
				} else {
					$morecss = 'minwidth400';
				}
			}
		}

		if (in_array($type, array('date', 'datetime'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];

			$showtime = in_array($type, array('datetime')) ? 1 : 0;

			// Do not show current date when field not required (see selectDate() method)
			if (!$required && $value == '') $value = '-1';

			// TODO Must also support $moreparam
			$out = $form->selectDate($value, $keyprefix . $key . $keysuffix, $showtime, $showtime, $required, '', 1, (($keyprefix != 'search_' && $keyprefix != 'search_options_') ? 1 : 0), 0, 1);
		} elseif (in_array($type, array('duration'))) {
			$out = $form->select_duration($keyprefix . $key . $keysuffix, $value, 0, 'text', 0, 1);
		} elseif (in_array($type, array('int', 'integer'))) {
			$tmp = explode(',', $size);
			$newsize = $tmp[0];
			$out = '<input type="text" class="k flat ' . $morecss . '" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" maxlength="' . $newsize . '" value="' . dol_escape_htmltag($value) . '"' . ($moreparam ? $moreparam : '') . ($autofocusoncreate ? ' autofocus' : '') . '>';
		} elseif (in_array($type, array('real'))) {
			$out = '<input type="text" class="flat ' . $morecss . '" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" value="' . dol_escape_htmltag($value) . '"' . ($moreparam ? $moreparam : '') . ($autofocusoncreate ? ' autofocus' : '') . '>';
		} elseif (preg_match('/varchar/', $type)) {
			$out = '<input type="text" class="flat ' . $morecss . '" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" maxlength="' . $size . '" value="' . dol_escape_htmltag($value) . '"' . ($moreparam ? $moreparam : '') . ($autofocusoncreate ? ' autofocus' : '') . '>';
		} elseif (in_array($type, array('mail', 'phone', 'url'))) {
			$out = '<input type="text" class="flat ' . $morecss . '" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" value="' . dol_escape_htmltag($value) . '" ' . ($moreparam ? $moreparam : '') . ($autofocusoncreate ? ' autofocus' : '') . '>';
		} elseif (preg_match('/^text/', $type)) {
			if (!preg_match('/search_/', $keyprefix))        // If keyprefix is search_ or search_options_, we must just use a simple text field
			{
				require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix . $key . $keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, false, ROWS_5, '90%');
				$out = $doleditor->Create(1);
			} else {
				$out = '<input type="text" class="flat ' . $morecss . ' maxwidthonsmartphone" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" value="' . dol_escape_htmltag($value) . '" ' . ($moreparam ? $moreparam : '') . '>';
			}
		} elseif (preg_match('/^html/', $type)) {
			if (!preg_match('/search_/', $keyprefix)) {        // If keyprefix is search_ or search_options_, we must just use a simple text field
				require_once DOL_DOCUMENT_ROOT . '/core/class/doleditor.class.php';
				$doleditor = new DolEditor($keyprefix . $key . $keysuffix, $value, '', 200, 'dolibarr_notes', 'In', false, false, !empty($conf->fckeditor->enabled) && $conf->global->FCKEDITOR_ENABLE_SOCIETE, ROWS_5, '90%');
				$out = $doleditor->Create(1);
			} else {
				$out = '<input type="text" class="flat ' . $morecss . ' maxwidthonsmartphone" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" value="' . dol_escape_htmltag($value) . '" ' . ($moreparam ? $moreparam : '') . '>';
			}
		} elseif ($type == 'boolean') {
			$checked = '';
			if (!empty($value)) {
				$checked = ' checked value="1" ';
			} else {
				$checked = ' value="1" ';
			}
			$out = '<input type="checkbox" class="flat ' . $morecss . ' maxwidthonsmartphone" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" ' . $checked . ' ' . ($moreparam ? $moreparam : '') . '>';
		} elseif ($type == 'price') {
			if (!empty($value)) {        // $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat ' . $morecss . ' maxwidthonsmartphone" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" value="' . $value . '" ' . ($moreparam ? $moreparam : '') . '> ' . $langs->getCurrencySymbol($conf->currency);
		} elseif (preg_match('/^double(\([0-9],[0-9]\)){0,1}/', $type)) {
			if (!empty($value)) {        // $value in memory is a php numeric, we format it into user number format.
				$value = price($value);
			}
			$out = '<input type="text" class="flat ' . $morecss . ' maxwidthonsmartphone" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" value="' . $value . '" ' . ($moreparam ? $moreparam : '') . '> ';
		} elseif ($type == 'select') {
			$out = '';
			if (!empty($conf->use_javascript_ajax) && !empty($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2)) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix . $key . $keysuffix, array(), 0);
			}

			$out .= '<select class="flat ' . $morecss . ' maxwidthonsmartphone" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" ' . ($moreparam ? $moreparam : '') . '>';
			if ((!isset($this->fields[$key]['default'])) || ($this->fields[$key]['notnull'] != 1)) $out .= '<option value="0">&nbsp;</option>';
			foreach ($param['options'] as $key => $val) {
				if ((string)$key == '') continue;
				list($val, $parent) = explode('|', $val);
				$out .= '<option value="' . $key . '"';
				$out .= (((string)$value == (string)$key) ? ' selected' : '');
				$out .= (!empty($parent) ? ' parent="' . $parent . '"' : '');
				$out .= '>' . $val . '</option>';
			}
			$out .= '</select>';
		} elseif ($type == 'sellist') {
			$out = '';
			if (!empty($conf->use_javascript_ajax) && !empty($conf->global->MAIN_EXTRAFIELDS_USE_SELECT2)) {
				include_once DOL_DOCUMENT_ROOT . '/core/lib/ajax.lib.php';
				$out .= ajax_combobox($keyprefix . $key . $keysuffix, array(), 0);
			}

			$out .= '<select class="flat ' . $morecss . ' maxwidthonsmartphone" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" ' . ($moreparam ? $moreparam : '') . '>';
			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName = '';
				$parentField = '';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value

				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2] . ' as rowid');

				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.' . $InfoFieldList[2] . ' as rowid';
					} else {
						$keyList = $InfoFieldList[2] . ' as rowid';
					}
				}
				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', ' . $parentField;
				}

				$fields_label = explode('|', $InfoFieldList[1]);
				if (is_array($fields_label)) {
					$keyList .= ', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sqlwhere = '';
				$sql = 'SELECT ' . $keyList;
				$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
				if (!empty($InfoFieldList[4])) {
					// can use SELECT request
					if (strpos($InfoFieldList[4], '$SEL$') !== false) {
						$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
					}

					// current object id can be use into filter
					if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
						$InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
					} else {
						$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
					}
					//We have to join on extrafield table
					if (strpos($InfoFieldList[4], 'extra') !== false) {
						$sql .= ' as main, ' . MAIN_DB_PREFIX . $InfoFieldList[0] . '_extrafields as extra';
						$sqlwhere .= ' WHERE extra.fk_object=main.' . $InfoFieldList[2] . ' AND ' . $InfoFieldList[4];
					} else {
						$sqlwhere .= ' WHERE ' . $InfoFieldList[4];
					}
				} else {
					$sqlwhere .= ' WHERE 1=1';
				}
				// Some tables may have field, some other not. For the moment we disable it.
				if (in_array($InfoFieldList[0], array('tablewithentity'))) {
					$sqlwhere .= ' AND entity = ' . $conf->entity;
				}
				$sql .= $sqlwhere;
				//print $sql;

				$sql .= ' ORDER BY ' . implode(', ', $fields_label);

				dol_syslog(get_class($this) . '::showInputField type=sellist', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$out .= '<option value="0">&nbsp;</option>';
					$num = $this->db->num_rows($resql);
					$i = 0;
					while ($i < $num) {
						$labeltoshow = '';
						$obj = $this->db->fetch_object($resql);

						// Several field into label (eq table:code|libelle:rowid)
						$notrans = false;
						$fields_label = explode('|', $InfoFieldList[1]);
						if (count($fields_label) > 1) {
							$notrans = true;
							foreach ($fields_label as $field_toshow) {
								$labeltoshow .= $obj->$field_toshow . ' ';
							}
						} else {
							$labeltoshow = $obj->{$InfoFieldList[1]};
						}
						$labeltoshow = dol_trunc($labeltoshow, 45);

						if ($value == $obj->rowid) {
							foreach ($fields_label as $field_toshow) {
								$translabel = $langs->trans($obj->$field_toshow);
								if ($translabel != $obj->$field_toshow) {
									$labeltoshow = dol_trunc($translabel, 18) . ' ';
								} else {
									$labeltoshow = dol_trunc($obj->$field_toshow, 18) . ' ';
								}
							}
							$out .= '<option value="' . $obj->rowid . '" selected>' . $labeltoshow . '</option>';
						} else {
							if (!$notrans) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
								if ($translabel != $obj->{$InfoFieldList[1]}) {
									$labeltoshow = dol_trunc($translabel, 18);
								} else {
									$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 18);
								}
							}
							if (empty($labeltoshow)) $labeltoshow = '(not defined)';
							if ($value == $obj->rowid) {
								$out .= '<option value="' . $obj->rowid . '" selected>' . $labeltoshow . '</option>';
							}

							if (!empty($InfoFieldList[3]) && $parentField) {
								$parent = $parentName . ':' . $obj->{$parentField};
							}

							$out .= '<option value="' . $obj->rowid . '"';
							$out .= ($value == $obj->rowid ? ' selected' : '');
							$out .= (!empty($parent) ? ' parent="' . $parent . '"' : '');
							$out .= '>' . $labeltoshow . '</option>';
						}

						$i++;
					}
					$this->db->free($resql);
				} else {
					print 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
				}
			}
			$out .= '</select>';
		} elseif ($type == 'checkbox') {
			$value_arr = explode(',', $value);
			$out = $form->multiselectarray($keyprefix . $key . $keysuffix, (empty($param['options']) ? null : $param['options']), $value_arr, '', 0, '', 0, '100%');
		} elseif ($type == 'radio') {
			$out = '';
			foreach ($param['options'] as $keyopt => $val) {
				$out .= '<input class="flat ' . $morecss . '" type="radio" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" ' . ($moreparam ? $moreparam : '');
				$out .= ' value="' . $keyopt . '"';
				$out .= ' id="' . $keyprefix . $key . $keysuffix . '_' . $keyopt . '"';
				$out .= ($value == $keyopt ? 'checked' : '');
				$out .= '/><label for="' . $keyprefix . $key . $keysuffix . '_' . $keyopt . '">' . $val . '</label><br>';
			}
		} elseif ($type == 'chkbxlst') {
			if (is_array($value)) {
				$value_arr = $value;
			} else {
				$value_arr = explode(',', $value);
			}

			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName = '';
				$parentField = '';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2] . ' as rowid');

				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list ($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', ' . $parentField;
				}
				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.' . $InfoFieldList[2] . ' as rowid';
					} else {
						$keyList = $InfoFieldList[2] . ' as rowid';
					}
				}

				$fields_label = explode('|', $InfoFieldList[1]);
				if (is_array($fields_label)) {
					$keyList .= ', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sqlwhere = '';
				$sql = 'SELECT ' . $keyList;
				$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
				if (!empty($InfoFieldList[4])) {
					// can use SELECT request
					if (strpos($InfoFieldList[4], '$SEL$') !== false) {
						$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
					}

					// current object id can be use into filter
					if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
						$InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
					} else {
						$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
					}

					// We have to join on extrafield table
					if (strpos($InfoFieldList[4], 'extra') !== false) {
						$sql .= ' as main, ' . MAIN_DB_PREFIX . $InfoFieldList[0] . '_extrafields as extra';
						$sqlwhere .= ' WHERE extra.fk_object=main.' . $InfoFieldList[2] . ' AND ' . $InfoFieldList[4];
					} else {
						$sqlwhere .= ' WHERE ' . $InfoFieldList[4];
					}
				} else {
					$sqlwhere .= ' WHERE 1=1';
				}

				// Some tables may have field, some other not. For the moment we disable it.
				if (in_array($InfoFieldList[0], array('tablewithentity'))) {
					$sqlwhere .= ' AND entity = ' . $conf->entity;
				}
				// $sql.=preg_replace('/^ AND /','',$sqlwhere);
				// print $sql;

				$sql .= $sqlwhere;
				dol_syslog(get_class($this) . '::showInputField type=chkbxlst', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;

					$data = array();

					while ($i < $num) {
						$labeltoshow = '';
						$obj = $this->db->fetch_object($resql);

						$notrans = false;
						// Several field into label (eq table:code|libelle:rowid)
						$fields_label = explode('|', $InfoFieldList[1]);
						if (count($fields_label) > 1) {
							$notrans = true;
							foreach ($fields_label as $field_toshow) {
								$labeltoshow .= $obj->$field_toshow . ' ';
							}
						} else {
							$labeltoshow = $obj->{$InfoFieldList[1]};
						}
						$labeltoshow = dol_trunc($labeltoshow, 45);

						if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
							foreach ($fields_label as $field_toshow) {
								$translabel = $langs->trans($obj->$field_toshow);
								if ($translabel != $obj->$field_toshow) {
									$labeltoshow = dol_trunc($translabel, 18) . ' ';
								} else {
									$labeltoshow = dol_trunc($obj->$field_toshow, 18) . ' ';
								}
							}

							$data[$obj->rowid] = $labeltoshow;
						} else {
							if (!$notrans) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
								if ($translabel != $obj->{$InfoFieldList[1]}) {
									$labeltoshow = dol_trunc($translabel, 18);
								} else {
									$labeltoshow = dol_trunc($obj->{$InfoFieldList[1]}, 18);
								}
							}
							if (empty($labeltoshow)) {
								$labeltoshow = '(not defined)';
							}

							if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
								$data[$obj->rowid] = $labeltoshow;
							}

							if (!empty($InfoFieldList[3]) && $parentField) {
								$parent = $parentName . ':' . $obj->{$parentField};
							}

							$data[$obj->rowid] = $labeltoshow;
						}

						$i++;
					}
					$this->db->free($resql);

					$out = $form->multiselectarray($keyprefix . $key . $keysuffix, $data, $value_arr, '', 0, $morecss, 0, '100%');

				} else {
					print 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
				}
			}
		} elseif ($type == 'link') {
			$param_list = array_keys($param['options']); // $param_list='ObjectName:classPath[:AddCreateButtonOrNot[:Filter]]'
			$param_list_array = explode(':', $param_list[0]);
			$showempty = (($required && $default != '') ? 0 : 1);

			if (!preg_match('/search_/', $keyprefix)) {
				if (!empty($param_list_array[2])) {        // If the entry into $fields is set to add a create button
					if (!empty($this->fields[$key]['picto'])) {
						$morecss .= ' widthcentpercentminusxx';
					} else {
						$morecss .= ' widthcentpercentminusx';
					}
				} else {
					if (!empty($this->fields[$key]['picto'])) {
						$morecss .= ' widthcentpercentminusx';
					}
				}
			}

			$out = $form->selectForForms($param_list[0], $keyprefix . $key . $keysuffix, $value, $showempty, '', '', $morecss, $moreparam, 0, empty($val['disabled']) ? 0 : 1);

			if (!empty($param_list_array[2])) {        // If the entry into $fields is set to add a create button
				if (!GETPOSTISSET('backtopage') && empty($val['disabled']) && empty($nonewbutton))    // To avoid to open several times the 'Create Object' button and to avoid to have button if field is protected by a "disabled".
				{
					list($class, $classfile) = explode(':', $param_list[0]);
					if (file_exists(dol_buildpath(dirname(dirname($classfile)) . '/card.php'))) $url_path = dol_buildpath(dirname(dirname($classfile)) . '/card.php', 1);
					else $url_path = dol_buildpath(dirname(dirname($classfile)) . '/' . strtolower($class) . '_card.php', 1);
					$paramforthenewlink = '';
					$paramforthenewlink .= (GETPOSTISSET('action') ? '&action=' . GETPOST('action', 'aZ09') : '');
					$paramforthenewlink .= (GETPOSTISSET('id') ? '&id=' . GETPOST('id', 'int') : '');
					$paramforthenewlink .= '&fk_' . strtolower($class) . '=--IDFORBACKTOPAGE--';
					// TODO Add Javascript code to add input fields already filled into $paramforthenewlink so we won't loose them when going back to main page
					$out .= '<a class="butActionNew" title="' . $langs->trans("New") . '" href="' . $url_path . '?action=create&backtopage=' . urlencode($_SERVER['PHP_SELF'] . ($paramforthenewlink ? '?' . $paramforthenewlink : '')) . '"><span class="fa fa-plus-circle valignmiddle"></span></a>';
				}
			}
		} elseif ($type == 'password') {
			// If prefix is 'search_', field is used as a filter, we use a common text field.
			$out = '<input type="' . ($keyprefix == 'search_' ? 'text' : 'password') . '" class="flat ' . $morecss . '" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '" value="' . $value . '" ' . ($moreparam ? $moreparam : '') . '>';
		} elseif ($type == 'array') {
			$newval = $val;
			$newval['type'] = 'varchar(256)';

			$out = '';
			if (!empty($value)) {
				foreach ($value as $option) {
					$out .= '<span><a class="' . dol_escape_htmltag($keyprefix . $key . $keysuffix) . '_del" href="javascript:;"><span class="fa fa-minus-circle valignmiddle"></span></a> ';
					$out .= $this->showInputField($newval, $keyprefix . $key . $keysuffix . '[]', $option, $moreparam, '', '', $morecss) . '<br></span>';
				}
			}
			$out .= '<a id="' . dol_escape_htmltag($keyprefix . $key . $keysuffix) . '_add" href="javascript:;"><span class="fa fa-plus-circle valignmiddle"></span></a>';

			$newInput = '<span><a class="' . dol_escape_htmltag($keyprefix . $key . $keysuffix) . '_del" href="javascript:;"><span class="fa fa-minus-circle valignmiddle"></span></a> ';
			$newInput .= $this->showInputField($newval, $keyprefix . $key . $keysuffix . '[]', '', $moreparam, '', '', $morecss) . '<br></span>';

			if (!empty($conf->use_javascript_ajax)) {
				$out .= '
					<script>
					$(document).ready(function() {
						$("a#' . dol_escape_js($keyprefix . $key . $keysuffix) . '_add").click(function() {
							$("' . dol_escape_js($newInput) . '").insertBefore(this);
						});

						$(document).on("click", "a.' . dol_escape_js($keyprefix . $key . $keysuffix) . '_del", function() {
							$(this).parent().remove();
						});
					});
					</script>';
			}
		} elseif ($type == 'contactlist') {
			if (is_array($value)) {
				$value_arr = $value;
			} else {
				$value_arr = explode(',', $value);
			}

			if (is_array($param['options'])) {
				$param_list = array_keys($param['options']);
				$InfoFieldList = explode(":", $param_list[0]);
				$parentName = '';
				$parentField = '';
				// 0 : tableName
				// 1 : label field name
				// 2 : key fields name (if differ of rowid)
				// 3 : key field parent (for dependent lists)
				// 4 : where clause filter on column or table extrafield, syntax field='value' or extra.field=value
				$keyList = (empty($InfoFieldList[2]) ? 'rowid' : $InfoFieldList[2] . ' as rowid');

				if (count($InfoFieldList) > 3 && !empty($InfoFieldList[3])) {
					list ($parentName, $parentField) = explode('|', $InfoFieldList[3]);
					$keyList .= ', ' . $parentField;
				}
				if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
					if (strpos($InfoFieldList[4], 'extra.') !== false) {
						$keyList = 'main.' . $InfoFieldList[2] . ' as rowid';
					} else {
						$keyList = $InfoFieldList[2] . ' as rowid';
					}
				}

				$fields_label = explode('|', $InfoFieldList[1]);
				if (is_array($fields_label)) {
					$keyList .= ', ';
					$keyList .= implode(', ', $fields_label);
				}

				$sqlwhere = '';
				$sql = 'SELECT rowid, firstname, lastname, email ';
				$sql .= ' FROM ' . MAIN_DB_PREFIX . 'socpeople';
				if (!empty($InfoFieldList[4])) {
					// can use SELECT request
					if (strpos($InfoFieldList[4], '$SEL$') !== false) {
						$InfoFieldList[4] = str_replace('$SEL$', 'SELECT', $InfoFieldList[4]);
					}

					// current object id can be use into filter
					if (strpos($InfoFieldList[4], '$ID$') !== false && !empty($objectid)) {
						$InfoFieldList[4] = str_replace('$ID$', $objectid, $InfoFieldList[4]);
					} else {
						$InfoFieldList[4] = str_replace('$ID$', '0', $InfoFieldList[4]);
					}

					// We have to join on extrafield table
					if (strpos($InfoFieldList[4], 'extra') !== false) {
						$sql .= ' as main, ' . MAIN_DB_PREFIX . $InfoFieldList[0] . '_extrafields as extra';
						$sqlwhere .= ' WHERE extra.fk_object=main.' . $InfoFieldList[2] . ' AND ' . $InfoFieldList[4];
					} else {
						$sqlwhere .= ' WHERE ' . $InfoFieldList[4];
					}
				} else {
					$sqlwhere .= ' WHERE 1=1';
				}

				// Some tables may have field, some other not. For the moment we disable it.
				if (in_array($InfoFieldList[0], array('tablewithentity'))) {
					$sqlwhere .= ' AND entity = ' . $conf->entity;
				}
				// $sql.=preg_replace('/^ AND /','',$sqlwhere);
				// print $sql;

				$sql .= $sqlwhere;
				dol_syslog(get_class($this) . '::showInputField type=contactlist', LOG_DEBUG);
				$resql = $this->db->query($sql);
				if ($resql) {
					$num = $this->db->num_rows($resql);
					$i = 0;

					$data = array();

					while ($i < $num) {
						$labeltoshow = '';
						$obj = $this->db->fetch_object($resql);

						$labeltoshow = $obj->firstname . ' ' . $obj->lastname . ', '. $obj->email;
						$labeltoshow = dol_trunc($labeltoshow, 45);

						$data[$obj->rowid] = $labeltoshow;

						$i++;
					}
					$this->db->free($resql);

					$out = $form->multiselectarray($keyprefix . $key . $keysuffix, $data, $value_arr, '', 0, $morecss, 0, '100%');

				} else {
					print 'Error in request ' . $sql . ' ' . $this->db->lasterror() . '. Check setup of extra parameters.<br>';
				}
			}
		}
		if (!empty($hidden)) {
			$out = '<input type="hidden" value="' . $value . '" name="' . $keyprefix . $key . $keysuffix . '" id="' . $keyprefix . $key . $keysuffix . '"/>';
		}
		/* Add comments
	 if ($type == 'date') $out.=' (YYYY-MM-DD)';
	 elseif ($type == 'datetime') $out.=' (YYYY-MM-DD HH:MM:SS)';
	 */
		return $out;
	}

	/**
	 * Return HTML string to show a field into a page
	 * Code very similar with showOutputField of extra fields
	 *
	 * @param array $val Array of properties of field to show
	 * @param string $key Key of attribute
	 * @param string $value Preselected value to show (for date type it must be in timestamp format, for amount or price it must be a php numeric value)
	 * @param string $moreparam To add more parametes on html input tag
	 * @param string $keysuffix Prefix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param string $keyprefix Suffix string to add into name and id of field (can be used to avoid duplicate names)
	 * @param mixed $morecss Value for css to define size. May also be a numeric.
	 * @return string
	 */
	public
	function showOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
	{

		global $conf, $langs, $form;

		if (!is_object($form)) {
			require_once DOL_DOCUMENT_ROOT . '/core/class/html.form.class.php';
			$form = new Form($this->db);
		}

		$objectid = $this->id;
		$label = $val['label'];
		$type = $val['type'];
		$size = $val['css'];
		$reg = array();

		// Convert var to be able to share same code than showOutputField of extrafields
		if (preg_match('/varchar\((\d+)\)/', $type, $reg)) {
			$type = 'varchar'; // convert varchar(xx) int varchar
			$size = $reg[1];
		} elseif (preg_match('/varchar/', $type)) $type = 'varchar'; // convert varchar(xx) int varchar
		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) $type = 'select';
		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) $type = 'link';

		$default = $val['default'];
		$computed = $val['computed'];
		$unique = $val['unique'];
		$required = $val['required'];
		$param = array();
		$param['options'] = array();

		if (!empty($val['arrayofkeyval']) && is_array($val['arrayofkeyval'])) $param['options'] = $val['arrayofkeyval'];
		if (preg_match('/^integer:(.*):(.*)/i', $val['type'], $reg)) {
			$type = 'link';
			$param['options'] = array($reg[1] . ':' . $reg[2] => $reg[1] . ':' . $reg[2]);
		} elseif (preg_match('/^sellist:(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] . ':' . $reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] . ':' . $reg[2] . ':' . $reg[3] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^sellist:(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] . ':' . $reg[2] => 'N');
			$type = 'sellist';
		} elseif (preg_match('/^chkbxlst:(.*):(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] . ':' . $reg[2] . ':' . $reg[3] . ':' . $reg[4] => 'N');
			$type = 'chkbxlst';
		} elseif (preg_match('/^chkbxlst:(.*):(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] . ':' . $reg[2] . ':' . $reg[3] => 'N');
			$type = 'chkbxlst';
		} elseif (preg_match('/^chkbxlst:(.*):(.*)/i', $val['type'], $reg)) {
			$param['options'] = array($reg[1] . ':' . $reg[2] => 'N');
			$type = 'chkbxlst';
		}

		$langfile = $val['langfile'];
		$list = $val['list'];
		$help = $val['help'];
		$hidden = (($val['visible'] == 0) ? 1 : 0); // If zero, we are sure it is hidden, otherwise we show. If it depends on mode (view/create/edit form or list, this must be filtered by caller)

		if ($hidden) return '';

		// If field is a computed field, value must become result of compute
		if ($computed) {
			// Make the eval of compute string
			//var_dump($computed);
			$value = dol_eval($computed, 1, 0);
		}

		if (empty($morecss)) {
			if ($type == 'date') {
				$morecss = 'minwidth100imp';
			} elseif ($type == 'datetime' || $type == 'timestamp') {
				$morecss = 'minwidth200imp';
			} elseif (in_array($type, array('int', 'double', 'price'))) {
				$morecss = 'maxwidth75';
			} elseif ($type == 'url') {
				$morecss = 'minwidth400';
			} elseif ($type == 'boolean') {
				$morecss = '';
			} else {
				if (round($size) < 12) {
					$morecss = 'minwidth100';
				} elseif (round($size) <= 48) {
					$morecss = 'minwidth200';
				} else {
					$morecss = 'minwidth400';
				}
			}
		}

		// Format output value differently according to properties of field
		if ($key == 'ref' && method_exists($this, 'getNomUrl') && $type == 'date') $value = $this->getNomUrl(1, '', 0, '', 1, dol_print_date($value, 'day'));
		elseif ($key == 'ref' && method_exists($this, 'getNomUrl')) $value = $this->getNomUrl(0, '', 0, '', 1);
		elseif ($key == 'status' && method_exists($this, 'getLibStatut')) $value = $this->getLibStatut(3);
		elseif ($type == 'date') {
			if (!empty($value)) {
				$value = dol_print_date($value, 'day');
			} else {
				$value = '';
			}
		} elseif ($type == 'datetime' || $type == 'timestamp') {
			if (!empty($value)) {
				$value = dol_print_date($value, 'dayhour');
			} else {
				$value = '';
			}
		} elseif ($type == 'duration') {
			include_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';
			if (!is_null($value) && $value !== '') {
				$value = convertSecondToTime($value, 'allhourmin');
			}
		} elseif ($type == 'double' || $type == 'real') {
			if (!is_null($value) && $value !== '') {
				$value = price($value);
			}
		} elseif ($type == 'boolean') {
			$checked = '';
			if (!empty($value)) {
				$checked = ' checked ';
			}
			$value = '<input type="checkbox" ' . $checked . ' ' . ($moreparam ? $moreparam : '') . ' readonly disabled>';
		} elseif ($type == 'mail') {
			$value = dol_print_email($value, 0, 0, 0, 64, 1, 1);
		} elseif ($type == 'url') {
			$value = dol_print_url($value, '_blank', 32, 1);
		} elseif ($type == 'phone') {
			$value = dol_print_phone($value, '', 0, 0, '', '&nbsp;', 1);
		} elseif ($type == 'price') {
			if (!is_null($value) && $value !== '') {
				$value = price($value, 0, $langs, 0, 0, -1, $conf->currency);
			}
		} elseif ($type == 'select') {
			$value = $param['options'][$value];
		} elseif ($type == 'sellist') {
			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) > 4 && !empty($InfoFieldList[4])) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2] . ' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT ' . $keyList;
			$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			if ($selectkey == 'rowid' && empty($value)) {
				$sql .= " WHERE " . $selectkey . "=0";
			} elseif ($selectkey == 'rowid') {
				$sql .= " WHERE " . $selectkey . "=" . $this->db->escape($value);
			} else {
				$sql .= " WHERE " . $selectkey . "='" . $this->db->escape($value) . "'";
			}

			//$sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this) . ':showOutputField:$type=sellist', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$value = ''; // value was used, so now we reste it to use it to build final output

				$obj = $this->db->fetch_object($resql);

				// Several field into label (eq table:code|libelle:rowid)
				$fields_label = explode('|', $InfoFieldList[1]);

				if (is_array($fields_label) && count($fields_label) > 1) {
					foreach ($fields_label as $field_toshow) {
						$translabel = '';
						if (!empty($obj->$field_toshow)) {
							$translabel = $langs->trans($obj->$field_toshow);
						}
						if ($translabel != $field_toshow) {
							$value .= dol_trunc($translabel, 18) . ' ';
						} else {
							$value .= $obj->$field_toshow . ' ';
						}
					}
				} else {
					$translabel = '';
					if (!empty($obj->{$InfoFieldList[1]})) {
						$translabel = $langs->trans($obj->{$InfoFieldList[1]});
					}
					if ($translabel != $obj->{$InfoFieldList[1]}) {
						$value = dol_trunc($translabel, 18);
					} else {
						$value = $obj->{$InfoFieldList[1]};
					}
				}
			} else dol_syslog(get_class($this) . '::showOutputField error ' . $this->db->lasterror(), LOG_WARNING);
		} elseif ($type == 'radio') {
			$value = $param['options'][$value];
		} elseif ($type == 'checkbox') {
			$value_arr = explode(',', $value);
			$value = '';
			if (is_array($value_arr) && count($value_arr) > 0) {
				$toprint = array();
				foreach ($value_arr as $keyval => $valueval) {
					$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . $param['options'][$valueval] . '</li>';
				}
				$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
			}
		} elseif ($type == 'chkbxlst') {
			dol_syslog('chk', LOG_EMERG);
			$value_arr = explode(',', $value);

			$param_list = array_keys($param['options']);
			$InfoFieldList = explode(":", $param_list[0]);

			$selectkey = "rowid";
			$keyList = 'rowid';

			if (count($InfoFieldList) >= 3) {
				$selectkey = $InfoFieldList[2];
				$keyList = $InfoFieldList[2] . ' as rowid';
			}

			$fields_label = explode('|', $InfoFieldList[1]);
			if (is_array($fields_label)) {
				$keyList .= ', ';
				$keyList .= implode(', ', $fields_label);
			}

			$sql = 'SELECT ' . $keyList;
			$sql .= ' FROM ' . MAIN_DB_PREFIX . $InfoFieldList[0];
			if (strpos($InfoFieldList[4], 'extra') !== false) {
				$sql .= ' as main';
			}
			// $sql.= " WHERE ".$selectkey."='".$this->db->escape($value)."'";
			// $sql.= ' AND entity = '.$conf->entity;

			dol_syslog(get_class($this) . ':showOutputField:$type=chkbxlst', LOG_DEBUG);
			$resql = $this->db->query($sql);
			if ($resql) {
				$value = ''; // value was used, so now we reste it to use it to build final output
				$toprint = array();
				while ($obj = $this->db->fetch_object($resql)) {
					// Several field into label (eq table:code|libelle:rowid)
					$fields_label = explode('|', $InfoFieldList[1]);
					if (is_array($value_arr) && in_array($obj->rowid, $value_arr)) {
						if (is_array($fields_label) && count($fields_label) > 1) {
							foreach ($fields_label as $field_toshow) {
								$translabel = '';
								if (!empty($obj->$field_toshow)) {
									$translabel = $langs->trans($obj->$field_toshow);
								}
								if ($translabel != $field_toshow) {
									$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . dol_trunc($translabel, 18) . '</li>';
								} else {
									$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . $obj->$field_toshow . '</li>';
								}
							}
						} else {
							$translabel = '';
							if (!empty($obj->{$InfoFieldList[1]})) {
								$translabel = $langs->trans($obj->{$InfoFieldList[1]});
							}
							if ($translabel != $obj->{$InfoFieldList[1]}) {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . dol_trunc($translabel, 18) . '</li>';
							} else {
								$toprint[] = '<li class="select2-search-choice-dolibarr noborderoncategories" style="background: #bbb">' . $obj->{$InfoFieldList[1]} . '</li>';
							}
						}
					}
				}
				$value = '<div class="select2-container-multi-dolibarr" style="width: 90%;"><ul class="select2-choices-dolibarr">' . implode(' ', $toprint) . '</ul></div>';
			} else {
				dol_syslog(get_class($this) . '::showOutputField error ' . $this->db->lasterror(), LOG_DEBUG);
			}
		} elseif ($type == 'link') {
			$out = '';

			// only if something to display (perf)
			if ($value) {
				$param_list = array_keys($param['options']); // $param_list='ObjectName:classPath'

				$InfoFieldList = explode(":", $param_list[0]);
				$classname = $InfoFieldList[0];
				$classpath = $InfoFieldList[1];
				$getnomurlparam = (empty($InfoFieldList[2]) ? 3 : $InfoFieldList[2]);
				if (!empty($classpath)) {
					dol_include_once($InfoFieldList[1]);
					if ($classname && class_exists($classname)) {
						$object = new $classname($this->db);
						$object->fetch($value);
						$value = $object->getNomUrl($getnomurlparam);
					}
				} else {
					dol_syslog('Error bad setup of extrafield', LOG_WARNING);
					return 'Error bad setup of extrafield';
				}
			} else $value = '';
		} elseif (preg_match('/^(text|html)/', $type)) {
			$value = dol_htmlentitiesbr($value);
		} elseif ($type == 'password') {
			$value = preg_replace('/./i', '*', $value);
		} elseif ($type == 'array') {
			$value = implode('<br>', $value);
		} elseif ($type == 'contactlist') {
			$out = '';

			dol_include_once('contact/class/contact.class.php');
			$object = new Contact($this->db);
			$values = explode(',', $value);

			foreach ($values as $e) {
				$object->fetch($e);
				$out .= $object->getNomUrl(1) . '<br>';
			}
			$value = $out;
		}

		//print $type.'-'.$size.'-'.$value;
		$out = $value;
		return $out;
	}



}


require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';

/**
 * Class RPLine. You can also remove this and generate a CRUD class for lines objects.
 */
class RPLine extends CommonObjectLine
{
	// To complete with content of an object RPLine
	// We should have a field rowid, fk_rp and position

	/**
	 * @var int  Does object support extrafields ? 0=No, 1=Yes
	 */
	public $isextrafieldmanaged = 0;

	/**
	 * Constructor
	 *
	 * @param DoliDb $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}
}
