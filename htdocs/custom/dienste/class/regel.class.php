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
 * \file        class/regel.class.php
 * \ingroup     dienste
 * \brief       This file is a CRUD class file for Regel (Create/Read/Update/Delete)
 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
//require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
//require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';

/**
 * Class for Regel
 */
class Regel extends CommonObject
{
	/**
	 * @var string ID of module.
	 */
	public $module = 'dienste';

	/**
	 * @var string ID to identify managed object.
	 */
	public $element = 'regel';

	/**
	 * @var string Name of table without prefix where object is stored. This is also the key used for extrafields management.
	 */
	public $table_element = 'dienste_regel';

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
	 * @var string String with name of icon for regel. Must be the part after the 'object_' into object_regel.png
	 */
	public $picto = 'regel@dienste';


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
	 *  'css' and 'cssview' and 'csslist' is the CSS style to use on field. 'css' is used in creation and update. 'cssview' is used in view mode. 'csslist' is used for columns in lists. For example: 'maxwidth200', 'wordbreak', 'tdoverflowmax200'
	 *  'help' is a 'TranslationString' to use to show a tooltip on field. You can also use 'TranslationString:keyfortooltiponlick' for a tooltip on click.
	 *  'showoncombobox' if value of the field must be visible into the label of the combobox that list record
	 *  'disabled' is 1 if we want to have the field locked by a 'disabled' attribute. In most cases, this is never set into the definition of $fields into class, but is set dynamically by some part of code.
	 *  'arraykeyval' to set list of value if type is a list of predefined values. For example: array("0"=>"Draft","1"=>"Active","-1"=>"Cancel")
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
		'ref' => array('type' => 'varchar(128)', 'label' => 'Nr', 'enabled' => '1', 'position' => 10, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'searchall' => 1, 'showoncombobox' => '1', 'comment' => "Reference of object"),
		'type' => array('type' => 'smallint', 'label' => 'type', 'enabled' => '1', 'position' => 31, 'notnull' => 1, 'visible' => 1, 'index' => 1, 'arrayofkeyval' => array('0' => 'Telefon', '1' => 'B&uuml;ro'),),
		'choice' => array('type' => 'smallint', 'label' => 'choice', 'enabled' => '1', 'position' => 11, 'notnull' => 1, 'visible' => 1, 'index' => 0),
		'user' => array('type' => 'sellist:user:firstname:rowid::employee=1', 'label' => 'MitarbeiterIn', 'enabled' => '1', 'position' => 70, 'notnull' => 1, 'visible' => 1, 'index' => 1,),
		'description' => array('type' => 'text', 'label' => 'Description', 'enabled' => '1', 'position' => 60, 'notnull' => 0, 'visible' => 3,),
		'anzahl' => array('type' => 'integer', 'label' => 'anzahl', 'enabled' => '1', 'position' => 31, 'notnull' => 0, 'visible' => 1, 'index' => 0),
		'haufig' => array('type' => 'integer', 'label' => 'haufig', 'enabled' => '1', 'position' => 31, 'notnull' => 0, 'visible' => 1, 'index' => 0),
		'tags' => array('type' => 'smallint', 'label' => 'tags', 'enabled' => '1', 'position' => 31, 'notnull' => 0, 'visible' => 1, 'index' => 0, 'arrayofkeyval' => array('1' => 'montags', '2' => 'dienstags', '3' => 'mittwochs', '4' => 'donnerstags', '5' => 'freitags', '6' => 'samstags', '7' => 'sonntags')),
		'tag' => array('type' => 'smallint', 'label' => 'tag', 'enabled' => '1', 'position' => 31, 'notnull' => 0, 'visible' => 1, 'index' => 0, 'arrayofkeyval' => array('1' => 'Montag', '2' => 'Dienstag', '3' => 'Mittwoch', '4' => 'Donnerstag', '5' => 'Freitag', '6' => 'Samstag', '7' => 'Sonntag')),
		'zeitraum' => array('type' => 'smallint', 'label' => 'zeitraum', 'enabled' => '1', 'position' => 31, 'notnull' => 0, 'visible' => 1, 'index' => 0, 'arrayofkeyval' => array('0' => 'Tag', '1' => 'Woche', '2' => 'Monat', '3' => 'Quartal', '4' => 'Jahr')),
		'date_creation' => array('type' => 'datetime', 'label' => 'DateCreation', 'enabled' => '1', 'position' => 500, 'notnull' => 1, 'visible' => -2,),
		'tms' => array('type' => 'timestamp', 'label' => 'DateModification', 'enabled' => '1', 'position' => 501, 'notnull' => 0, 'visible' => -2,),
		'fk_user_creat' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserAuthor', 'enabled' => '1', 'position' => 510, 'notnull' => 1, 'visible' => -2, 'foreignkey' => 'user.rowid',),
		'fk_user_modif' => array('type' => 'integer:User:user/class/user.class.php', 'label' => 'UserModif', 'enabled' => '1', 'position' => 511, 'notnull' => -1, 'visible' => -2,),
	);
	public $rowid;
	public $ref;
	public $type;
	public $user;
	public $description;
	public $date_creation;
	public $tms;
	public $fk_user_creat;
	public $fk_user_modif;
	// END MODULEBUILDER PROPERTIES


	// If this object has a subtable with lines

	// /**
	//  * @var string    Name of subtable line
	//  */
	// public $table_element_line = 'dienste_regelline';

	// /**
	//  * @var string    Field with ID of parent key if this object has a parent
	//  */
	// public $fk_element = 'fk_regel';

	// /**
	//  * @var string    Name of subtable class that manage subtable lines
	//  */
	// public $class_element_line = 'Regelline';

	// /**
	//  * @var array	List of child tables. To test if we can delete object.
	//  */
	// protected $childtables = array();

	// /**
	//  * @var array    List of child tables. To know object to delete on cascade.
	//  *               If name matches '@ClassNAme:FilePathClass;ParentFkFieldName' it will
	//  *               call method deleteByParentField(parentId, ParentFkFieldName) to fetch and delete child object
	//  */
	// protected $childtablesoncascade = array('dienste_regeldet');

	// /**
	//  * @var RegelLine[]     Array of subtable lines
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

		if (empty($conf->global->MAIN_SHOW_TECHNICAL_ID) && isset($this->fields['rowid'])) $this->fields['rowid']['visible'] = 0;
		if (empty($conf->multicompany->enabled) && isset($this->fields['entity'])) $this->fields['entity']['enabled'] = 0;

		// Example to show how to set values of fields definition dynamically
		/*if ($user->rights->dienste->regel->read) {
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
	 * Compute Dienste for specified week
	 *
	 * @return mixed             <0 if KO, Id of created object if OK
	 */
	public function computeDienste()
	{
		return true;
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
		return $this->createCommon($user, $notrigger);
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
		if ($result > 0 && !empty($object->table_element_line)) $object->fetchLines();

		// get lines so they will be clone
		//foreach($this->lines as $line)
		//	$line->fetch_optionals();

		// Reset some properties
		unset($object->id);
		unset($object->fk_user_creat);
		unset($object->import_key);

		// Clear fields
		if (property_exists($object, 'ref')) $object->ref = empty($this->fields['ref']['default']) ? "Copy_Of_" . $object->ref : $this->fields['ref']['default'];
		if (property_exists($object, 'label')) $object->label = empty($this->fields['label']['default']) ? $langs->trans("CopyOf") . " " . $object->label : $this->fields['label']['default'];
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
			if (property_exists($this, 'socid') && $this->socid == $object->socid) {
				if ($this->copy_linked_contact($object, 'external') < 0)
					$error++;
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
		if ($result > 0 && !empty($this->table_element_line)) $this->fetchLines();
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
		$sql .= $this->getFieldList();
		$sql .= ' FROM ' . MAIN_DB_PREFIX . $this->table_element . ' as t';
		if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql .= ' WHERE t.entity IN (' . getEntity($this->table_element) . ')';
		else $sql .= ' WHERE 1 = 1';
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dienste->regel->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dienste->regel->regel_advance->validate))))
		 {
		 $this->error='NotEnoughPermissions';
		 dol_syslog(get_class($this)."::valid ".$this->error, LOG_ERR);
		 return -1;
		 }*/

		$now = dol_now();

		$this->db->begin();

		// Define new ref
		if (!$error && (preg_match('/^[\(]?PROV/i', $this->ref) || empty($this->ref))) // empty should not happened, but when it occurs, the test save life
		{
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
			if (!empty($this->fields['date_validation'])) $sql .= ", date_validation = '" . $this->db->idate($now) . "'";
			if (!empty($this->fields['fk_user_valid'])) $sql .= ", fk_user_valid = " . $user->id;
			$sql .= " WHERE rowid = " . $this->id;

			dol_syslog(get_class($this) . "::validate()", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				dol_print_error($this->db);
				$this->error = $this->db->lasterror();
				$error++;
			}

			if (!$error && !$notrigger) {
				// Call trigger
				$result = $this->call_trigger('REGEL_VALIDATE', $user);
				if ($result < 0) $error++;
				// End call triggers
			}
		}

		if (!$error) {
			$this->oldref = $this->ref;

			// Rename directory if dir was a temporary ref
			if (preg_match('/^[\(]?PROV/i', $this->ref)) {
				// Now we rename also files into index
				$sql = 'UPDATE ' . MAIN_DB_PREFIX . "ecm_files set filename = CONCAT('" . $this->db->escape($this->newref) . "', SUBSTR(filename, " . (strlen($this->ref) + 1) . ")), filepath = 'regel/" . $this->db->escape($this->newref) . "'";
				$sql .= " WHERE filename LIKE '" . $this->db->escape($this->ref) . "%' AND filepath = 'regel/" . $this->db->escape($this->ref) . "' and entity = " . $conf->entity;
				$resql = $this->db->query($sql);
				if (!$resql) {
					$error++;
					$this->error = $this->db->lasterror();
				}

				// We rename directory ($this->ref = old ref, $num = new ref) in order not to lose the attachments
				$oldref = dol_sanitizeFileName($this->ref);
				$newref = dol_sanitizeFileName($num);
				$dirsource = $conf->dienste->dir_output . '/regel/' . $oldref;
				$dirdest = $conf->dienste->dir_output . '/regel/' . $newref;
				if (!$error && file_exists($dirsource)) {
					dol_syslog(get_class($this) . "::validate() rename dir " . $dirsource . " into " . $dirdest);

					if (@rename($dirsource, $dirdest)) {
						dol_syslog("Rename ok");
						// Rename docs starting with $oldref with $newref
						$listoffiles = dol_dir_list($conf->dienste->dir_output . '/regel/' . $newref, 'files', 1, '^' . preg_quote($oldref, '/'));
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dienste->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dienste->dienste_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_DRAFT, $notrigger, 'REGEL_UNVALIDATE');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dienste->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dienste->dienste_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_CANCELED, $notrigger, 'REGEL_CANCEL');
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

		/*if (! ((empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dienste->write))
		 || (! empty($conf->global->MAIN_USE_ADVANCED_PERMS) && ! empty($user->rights->dienste->dienste_advance->validate))))
		 {
		 $this->error='Permission denied';
		 return -1;
		 }*/

		return $this->setStatusCommon($user, self::STATUS_VALIDATED, $notrigger, 'REGEL_REOPEN');
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

		if (!empty($conf->dol_no_mouse_hover)) $notooltip = 1; // Force disable tooltips

		$result = '';

		$label = img_picto('', $this->picto) . ' <u>' . $langs->trans("Regel") . '</u>';
		if (isset($this->status)) {
			$label .= ' ' . $this->getLibStatut(5);
		}
		$label .= '<br>';
		$label .= '<b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;

		$url = dol_buildpath('/dienste/regel_card.php', 1) . '?id=' . $this->id;

		if ($option != 'nolink') {
			// Add param to save lastsearch_values or not
			$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
			if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
			if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
		}

		$linkclose = '';
		if (empty($notooltip)) {
			if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER)) {
				$label = $langs->trans("ShowRegel");
				$linkclose .= ' alt="' . dol_escape_htmltag($label, 1) . '"';
			}
			$linkclose .= ' title="' . dol_escape_htmltag($label, 1) . '"';
			$linkclose .= ' class="classfortooltip' . ($morecss ? ' ' . $morecss : '') . '"';
		} else $linkclose = ($morecss ? ' class="' . $morecss . '"' : '');

		$linkstart = '<a href="' . $url . '"';
		$linkstart .= $linkclose . '>';
		$linkend = '</a>';

		$result .= $linkstart;

		if (empty($this->showphoto_on_popup)) {
			if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="' . (($withpicto != 2) ? 'paddingright ' : '') . 'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
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

		if ($withpicto != 2) $result .= $this->ref;

		$result .= $linkend;
		//if ($withpicto != 2) $result.=(($addlabel && $this->label) ? $sep . dol_trunc($this->label, ($addlabel > 1 ? $addlabel : 0)) : '');

		global $action, $hookmanager;
		$hookmanager->initHooks(array('regeldao'));
		$parameters = array('id' => $this->id, 'getnomurl' => $result);
		$reshook = $hookmanager->executeHooks('getNomUrl', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook > 0) $result = $hookmanager->resPrint;
		else $result .= $hookmanager->resPrint;

		return $result;
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
			//$langs->load("dienste@dienste");
			$this->labelStatus[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatus[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatus[self::STATUS_CANCELED] = $langs->trans('Disabled');
			$this->labelStatusShort[self::STATUS_DRAFT] = $langs->trans('Draft');
			$this->labelStatusShort[self::STATUS_VALIDATED] = $langs->trans('Enabled');
			$this->labelStatusShort[self::STATUS_CANCELED] = $langs->trans('Disabled');
		}

		$statusType = 'status' . $status;
		//if ($status == self::STATUS_VALIDATED) $statusType = 'status1';
		if ($status == self::STATUS_CANCELED) $statusType = 'status6';

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
		$sql .= ' WHERE t.rowid = ' . $id;
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

		$objectline = new RegelLine($this->db);
		$result = $objectline->fetchAll('ASC', 'position', 0, 0, array('customsql' => 'fk_regel = ' . $this->id));

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
		$langs->load("dienste@dienste");

		if (empty($conf->global->DIENSTE_REGEL_ADDON)) {
			$conf->global->DIENSTE_REGEL_ADDON = 'mod_regel_standard';
		}

		if (!empty($conf->global->DIENSTE_REGEL_ADDON)) {
			$mybool = false;

			$file = $conf->global->DIENSTE_REGEL_ADDON . ".php";
			$classname = $conf->global->DIENSTE_REGEL_ADDON;

			// Include file with class
			$dirmodels = array_merge(array('/'), (array)$conf->modules_parts['models']);
			foreach ($dirmodels as $reldir) {
				$dir = dol_buildpath($reldir . "core/modules/dienste/");

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

		$langs->load("dienste@dienste");

		if (!dol_strlen($modele)) {
			$modele = 'standard_regel';

			if (!empty($this->model_pdf)) {
				$modele = $this->model_pdf;
			} elseif (!empty($conf->global->REGEL_ADDON_PDF)) {
				$modele = $conf->global->REGEL_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/dienste/doc/";

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
		$today = date('Y-m-d');
		$tms = date('Y-m-d H:i:s');

		$this->db->begin();

		$sql = "SELECT * FROM llx_dienste_regel WHERE 1";
		$regeln = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

		$sql = "SELECT * FROM llx_dienste_buero WHERE 1";
		$budi = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

		for ($i = 8; $i < 13; $i++) {
			$day = date("Y-m-d", strtotime($today . " +" . $i . " days"));
			if (!($key = array_search($day, array_column($budi, 'ref')))) {
				foreach ($regeln as $regel) {
					if ($regel['choice'] == 1 && $regel['tags'] == ($i - 7)) {
						$sql = "INSERT INTO llx_dienste_buero (ref, user, date_creation, fk_user_creat) VALUES ('" . $day . "','" . $regel['user'] . "','" . $tms . "',1)";
						$this->db->query($sql);
					} elseif ($regel['choice'] == 2 && $regel['tag'] == ($i - 7)) {
						$last = date("Y-m-d", strtotime($today . " -" . abs(($i - 7) - (7 * ($regel['haufig'] - 1))) . " days"));
						// TODO each third, fourth etc. doesn't work yet

						if (($dienst = array_search($last, array_column($budi, 'ref'))) && $budi[$dienst]['user'] != $regel['user']) {
							$sql = "INSERT INTO llx_dienste_buero (ref, user, date_creation, fk_user_creat) VALUES ('" . $day . "','" . $regel['user'] . "','" . $tms . "',1)";
							$this->db->query($sql);
						}
					} elseif ($regel['choice'] == 3) {
						// TODO
					} else {
					}
				}
			}
		}

		$sql = "SELECT * FROM llx_dienste_regel WHERE type=0";
		$regeln = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

		$sql = "SELECT * FROM llx_dienste_buero WHERE 1";
		$budi = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

		$sql = "SELECT * FROM llx_dienste_telefon WHERE 1";
		$tedi = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);


		for ($i = 8; $i < 13; $i++) {
			$day = date("Y-m-d", strtotime($today . " +" . $i . " days"));
			if (!($key = array_search($day, array_column($tedi, 'ref')))) {
				foreach ($regeln as $regel) {
					$budi_user = array_search($day, array_column($budi, 'ref'));
					if ($regel['user'] != $budi_user) {
						$sql = "INSERT INTO llx_dienste_telefon (ref, user, date_creation, fk_user_creat) VALUES ('" . $day . "','" . $regel['user'] . "','" . $tms . "',1)";
						$this->db->query($sql);
					}
				}
			}
		}

		$sql = "SELECT * FROM llx_dienste_telefon WHERE 1";
		$tedi = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

		$sql = "SELECT user, MAX(ref) As ref FROM llx_dienste_telefon GROUP BY user ORDER BY ref ASC";
		$tedi_users = $this->db->query($sql)->fetch_all(MYSQLI_ASSOC);

		for ($i = 8; $i < 13; $i++) {
			$day = date("Y-m-d", strtotime($today . " +" . $i . " days"));

			if (!(array_search($day, array_column($tedi, 'ref')))) {

				$key = array_search($day, array_column($budi, 'ref'));
				$cur_user = $tedi_users[0]['user'];
				if($cur_user != null && $cur_user != $budi[$key]['user']) {
					array_shift($tedi_users);
					$sql = "INSERT INTO llx_dienste_telefon (ref, user, date_creation, fk_user_creat) VALUES ('" . $day . "','" . $cur_user . "','" . $tms . "',1)";
					$this->db->query($sql);
				} else {
					$cur_user = $tedi_users[1]['user'];
					array_splice($tedi_users, 1, 1);
					$sql = "INSERT INTO llx_dienste_telefon (ref, user, date_creation, fk_user_creat) VALUES ('" . $day . "','" . $cur_user . "','" . $tms . "',1)";
					$this->db->query($sql);
				}
			} else {
				dol_syslog('Something went wrong when creating Telefondienst', LOG_WARNING);
			}
		}

$this->db->commit();

return $error;
}

public
function showDienstOutputField($val, $key, $value, $moreparam = '', $keysuffix = '', $keyprefix = '', $morecss = '')
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
	elseif ($key == 'ref' && method_exists($this, 'getNomUrl')) $value = $this->getNomUrl(1, '', 0, '', 1);
	elseif ($key == 'status' && method_exists($this, 'getLibStatut')) $value = $this->getLibStatut(3);
	// HoT
	elseif ($key == 'type') {
		if ($value == '0') {
			$value .= '<a class="customer-back" title="' . $langs->trans("Telefon") . '" href="' . DOL_URL_ROOT . '/custom/card.php?socid=' . $companystatic->id . '">' . dol_substr($langs->trans("Telefon"), 0, 1) . '</a>';
		} elseif ($value == '1') {
			$value .= '<a class="vendor-back" title="' . $langs->trans("Büro") . '" href="' . DOL_URL_ROOT . '/fourn/card.php?socid=' . $companystatic->id . '">' . dol_substr($langs->trans("Büro"), 0, 1) . '</a>';
		}
		$value = substr($value, 1);
	} elseif ($type == 'date') {
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
	}

	//print $type.'-'.$size.'-'.$value;
	$out = $value;

	return $out;
}
}


require_once DOL_DOCUMENT_ROOT . '/core/class/commonobjectline.class.php';

/**
 * Class RegelLine. You can also remove this and generate a CRUD class for lines objects.
 */
class RegelLine extends CommonObjectLine
{
	// To complete with content of an object RegelLine
	// We should have a field rowid, fk_regel and position

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
