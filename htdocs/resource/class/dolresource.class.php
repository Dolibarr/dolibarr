<?php
/* Copyright (C) 2013-2015		Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2023-2024		William Mead		<william.mead@manchenumerique.fr>
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
 *  \file      	htdocs/resource/class/dolresource.class.php
 *  \ingroup    resource
 *  \brief      Class file for resource object
 */

require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";
require_once DOL_DOCUMENT_ROOT.'/core/class/commonpeople.class.php';

/**
 *  DAO Resource object
 */
class Dolresource extends CommonObject
{
	use CommonPeople;
	/**
	 * @var string ID to identify managed object
	 */
	public $element = 'dolresource';

	/**
	 * @var string Name of table without prefix where object is stored
	 */
	public $table_element = 'resource';

	/**
	 * @var string String with name of icon for myobject. Must be the part after the 'object_' into object_myobject.png
	 */
	public $picto = 'resource';

	/**
	 * @var string address variables
	 */
	public $address;

	/**
	 * @var string zip of town
	 */
	public $zip;

	/**
	 * @var string yown
	 */
	public $town;

	/**
	 * @var string ID
	 */
	public $fk_code_type_resource;

	public $type_label;

	/**
	 * @var string description
	 */
	public $description;

	/**
	 * @var int ID country
	 */
	public $fk_country;

	/**
	 * @var int ID state
	 */
	public $fk_state;

	// Variable for a link of resource

	/**
	 * @var int ID
	 */
	public $resource_id;
	public $resource_type;
	public $element_id;
	public $element_type;
	public $busy;
	public $mandatory;
	public $fulldayevent;

	/**
	 * @var int ID
	 */
	public $fk_user_create;
	public $tms;

	/**
	 * Used by fetchElementResource() to return an object
	 */
	public $objelement;

	/**
	 * @var array	Cache of type of resources. TODO Use $conf->cache['type_of_resources'] instead
	 */
	public $cache_code_type_resource;

	/**
	 * @var Dolresource Clone of object before changing it
	 */
	public $oldcopy;


	/**
	 *  Constructor
	 *
	 *  @param	DoliDB		$db      Database handler
	 */
	public function __construct(DoliDb $db)
	{
		$this->db = $db;
		$this->tms = '';
		$this->cache_code_type_resource = array();
	}

	/**
	 * Create object in database
	 *
	 * @param	User	$user		User that creates
	 * @param	int		$no_trigger	0=launch triggers after, 1=disable triggers
	 * @return	int					Return integer if KO: <0, if OK: Id of created object
	 */
	public function create(User $user, int $no_trigger = 0)
	{
		$error = 0;

		// Clean parameters
		$new_resource_values = [$this->ref, $this->address, $this->zip, $this->town, $this->description, $this->country_id, $this->state_id, $this->fk_code_type_resource, $this->note_public, $this->note_private];
		foreach ($new_resource_values as $key => $value) {
			if (isset($value)) {
				$new_resource_values[$key] = trim($value);
			}
		}

		// Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";
		$sql .= "entity,";
		$sql .= "ref,";
		$sql .= "address,";
		$sql .= "zip,";
		$sql .= "town,";
		$sql .= "description,";
		$sql .= "fk_country,";
		$sql .= "fk_state,";
		$sql .= "fk_code_type_resource,";
		$sql .= "note_public,";
		$sql .= "note_private";
		$sql .= ") VALUES (";
		$sql .= getEntity('resource') . ", ";
		foreach ($new_resource_values as $value) {
			$sql .= " " . ((isset($value) && $value > 0) ? "'" . $this->db->escape($value) . "'" : 'NULL') . ",";
		}
		$sql = rtrim($sql, ",");
		$sql .= ")";

		// Database session
		$this->db->begin();
		try {
			dol_syslog(get_class($this) . "::create", LOG_DEBUG);
		} catch (Exception $exception) {
			error_log('dol_syslog error: ' . $exception->getMessage());
		}
		$resql = $this->db->query($sql);
		if (!$resql) {
			$error++;
			$this->errors[] = "Error ".$this->db->lasterror();
		}

		if (!$error) {
			$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
			$result = $this->insertExtraFields();
			if ($result < 0) {
				$error=-1;
			}
		}

		if (!$error && !$no_trigger) {
			$result = $this->call_trigger('RESOURCE_CREATE', $user);
			if ($result < 0) {
				$error=-1;
			}
		}

		// Commit or rollback
		if ($error) {
			foreach ($this->errors as $errmsg) {
				try {
					dol_syslog(get_class($this) . "::create " . $errmsg, LOG_ERR);
				} catch (Exception $exception) {
					error_log('dol_syslog error: ' . $exception->getMessage());
				}
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return $error;
		} else {
			$this->db->commit();
			return $this->id;
		}
	}

	/**
	 * Load object into memory from database
	 *
	 * @param	int		$id		Id of object
	 * @param	string	$ref	Ref of object
	 * @return	int				Return integer if KO: <0 , if OK: >0
	 */
	public function fetch($id, $ref = '')
	{
		global $langs;
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.ref,";
		$sql .= " t.address,";
		$sql .= " t.zip,";
		$sql .= " t.town,";
		$sql .= " t.description,";
		$sql .= " t.fk_country,";
		$sql .= " t.fk_state,";
		$sql .= " t.fk_code_type_resource,";
		$sql .= " t.note_public,";
		$sql .= " t.note_private,";
		$sql .= " t.tms,";
		$sql .= " ty.label as type_label";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_resource as ty ON ty.code=t.fk_code_type_resource";
		if ($id) {
			$sql .= " WHERE t.rowid = ".((int) $id);
		} else {
			$sql .= " WHERE t.ref = '".$this->db->escape($ref)."'";
		}

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->entity = $obj->entity;
				$this->ref = $obj->ref;
				$this->address = $obj->address;
				$this->zip = $obj->zip;
				$this->town = $obj->town;
				$this->description				= $obj->description;
				$this->country_id = $obj->fk_country;
				$this->state_id = $obj->fk_state;
				$this->fk_code_type_resource = $obj->fk_code_type_resource;
				$this->note_public				= $obj->note_public;
				$this->note_private = $obj->note_private;
				$this->type_label = $obj->type_label;

				// Retrieve all extrafield
				// fetch optionals attributes and labels
				$this->fetch_optionals();
			}
			$this->db->free($resql);

			return $this->id;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
			return -1;
		}
	}


	/**
	 * Update object in database
	 *
	 * @param	User	$user		User that modifies
	 * @param	int		$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int					Return integer if KO: <0 , if OK: >0
	 */
	public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs, $hookmanager;
		$error = 0;

		// Clean parameters
		if (isset($this->ref)) {
			$this->ref = trim($this->ref);
		}
		if (isset($this->address)) {
			$this->address = trim($this->address);
		}
		if (isset($this->zip)) {
			$this->zip = trim($this->zip);
		}
		if (isset($this->town)) {
			$this->town = trim($this->town);
		}
		if (isset($this->fk_code_type_resource)) {
			$this->fk_code_type_resource = trim($this->fk_code_type_resource);
		}
		if (isset($this->description)) {
			$this->description = trim($this->description);
		}
		if (!is_numeric($this->country_id)) {
			$this->country_id = 0;
		}
		if (!is_numeric($this->state_id)) {
			$this->state_id = 0;
		}

		// $this->oldcopy should have been set by the caller of update (here properties were already modified)
		if (empty($this->oldcopy)) {
			$this->oldcopy = dol_clone($this);
		}

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " address=".(isset($this->address) ? "'".$this->db->escape($this->address)."'" : "null").",";
		$sql .= " zip=".(isset($this->zip) ? "'".$this->db->escape($this->zip)."'" : "null").",";
		$sql .= " town=".(isset($this->town) ? "'".$this->db->escape($this->town)."'" : "null").",";
		$sql .= " description=".(isset($this->description) ? "'".$this->db->escape($this->description)."'" : "null").",";
		$sql .= " fk_country=".($this->country_id > 0 ? $this->country_id : "null").",";
		$sql .= " fk_state=".($this->state_id > 0 ? $this->state_id : "null").",";
		$sql .= " fk_code_type_resource=".(isset($this->fk_code_type_resource) ? "'".$this->db->escape($this->fk_code_type_resource)."'" : "null").",";
		$sql .= " tms=".(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null');
		$sql .= " WHERE rowid=".((int) $this->id);

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
				$result = $this->call_trigger('RESOURCE_MODIFY', $user);
				if ($result < 0) {
					$error++;
				}
				// End call triggers
			}
		}

		if (!$error && (is_object($this->oldcopy) && $this->oldcopy->ref !== $this->ref)) {
			// We remove directory
			if (!empty($conf->resource->dir_output)) {
				$olddir = $conf->resource->dir_output."/".dol_sanitizeFileName($this->oldcopy->ref);
				$newdir = $conf->resource->dir_output."/".dol_sanitizeFileName($this->ref);
				if (file_exists($olddir)) {
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
			$action = 'update';

			// Actions on extra fields
			if (!$error) {
				$result = $this->insertExtraFields();
				if ($result < 0) {
					$error++;
				}
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
	 * Load data of resource links into memory from database
	 *
	 * @param	int	$id		Id of link element_resources
	 * @return	int			Return integer if KO: <0, if OK: >0
	 */
	public function fetchElementResource($id)
	{
		$sql = "SELECT";
		$sql .= " t.rowid,";
		$sql .= " t.resource_id,";
		$sql .= " t.resource_type,";
		$sql .= " t.element_id,";
		$sql .= " t.element_type,";
		$sql .= " t.busy,";
		$sql .= " t.mandatory,";
		$sql .= " t.fk_user_create,";
		$sql .= " t.tms";
		$sql .= " FROM ".MAIN_DB_PREFIX."element_resources as t";
		$sql .= " WHERE t.rowid = ".((int) $id);

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			if ($this->db->num_rows($resql)) {
				$obj = $this->db->fetch_object($resql);

				$this->id = $obj->rowid;
				$this->resource_id = $obj->resource_id;
				$this->resource_type	= $obj->resource_type;
				$this->element_id = $obj->element_id;
				$this->element_type		= $obj->element_type;
				$this->busy = $obj->busy;
				$this->mandatory = $obj->mandatory;
				$this->fk_user_create = $obj->fk_user_create;

				/*if ($obj->resource_id && $obj->resource_type) {
					$this->objresource = fetchObjectByElement($obj->resource_id, $obj->resource_type);
				}*/
				if ($obj->element_id && $obj->element_type) {
					$this->objelement = fetchObjectByElement($obj->element_id, $obj->element_type);
				}
			}
			$this->db->free($resql);

			return $this->id;
		} else {
			$this->error = "Error ".$this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Delete a resource object
	 *
	 * @param	int		$rowid			Id of resource line to delete
	 * @param	int		$notrigger		Disable all triggers
	 * @return	int						Return integer if OK: >0, if KO: <0
	 */
	public function delete($rowid, $notrigger = 0)
	{
		global $user, $langs, $conf;
		require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE rowid = ".((int) $rowid);

		dol_syslog(get_class($this), LOG_DEBUG);
		if ($this->db->query($sql)) {
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_resources";
			$sql .= " WHERE element_type='resource' AND resource_id = ".((int) $rowid);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql) {
				$this->error = $this->db->lasterror();
				$error++;
			}
		} else {
			$this->error = $this->db->lasterror();
			$error++;
		}

		// Removed extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0) {
				$error++;
				dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
			}
		}

		if (!$notrigger) {
			// Call trigger
			$result = $this->call_trigger('RESOURCE_DELETE', $user);
			if ($result < 0) {
				$error++;
			}
			// End call triggers
		}

		if (!$error) {
			// We remove directory
			$ref = dol_sanitizeFileName($this->ref);
			if (!empty($conf->resource->dir_output)) {
				$dir = $conf->resource->dir_output."/".dol_sanitizeFileName($this->ref);
				if (file_exists($dir)) {
					$res = @dol_delete_dir_recursive($dir);
					if (!$res) {
						$this->errors[] = 'ErrorFailToDeleteDir';
						$error++;
					}
				}
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

	/**
	 * Load resource objects into $this->lines
	 *
	 * @param	string		$sortorder		sort order
	 * @param	string		$sortfield		sort field
	 * @param	int			$limit			limit page
	 * @param	int			$offset			page
	 * @param	array		$filter			filter output
	 * @return	int							Return integer if KO: <0, if OK number of lines loaded
	 */
	public function fetchAll($sortorder, $sortfield, $limit, $offset, $filter = [])
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
		$extrafields = new ExtraFields($this->db);

		$sql = "SELECT ";
		$sql .= " t.rowid,";
		$sql .= " t.entity,";
		$sql .= " t.ref,";
		$sql .= " t.address,";
		$sql .= " t.zip,";
		$sql .= " t.town,";
		$sql .= " t.description,";
		$sql .= " t.fk_country,";
		$sql .= " t.fk_state,";
		$sql .= " t.fk_code_type_resource,";
		$sql .= " t.tms,";
		// Add fields from extrafields
		if (!empty($extrafields->attributes[$this->table_element]) && !empty($extrafields->attributes[$this->table_element]['label'])) {
			foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) {
				$sql .= ($extrafields->attributes[$this->table_element]['type'][$key] != 'separate' ? "ef.".$key." as options_".$key.', ' : '');
			}
		}
		$sql .= " ty.label as type_label";
		$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_resource as ty ON ty.code=t.fk_code_type_resource";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$this->table_element."_extrafields as ef ON ef.fk_object=t.rowid";
		$sql .= " WHERE t.entity IN (".getEntity('resource').")";
		// Manage filter
		if (!empty($filter)) {
			foreach ($filter as $key => $value) {
				if (strpos($key, 'date')) {
					$sql .= " AND ".$key." = '".$this->db->idate($value)."'";
				} elseif (strpos($key, 'ef.') !== false) {
					$sql .= $value;
				} else {
					$sql .= " AND ".$key." LIKE '%".$this->db->escape($value)."%'";
				}
			}
		}
		$sql .= $this->db->order($sortfield, $sortorder);
		if ($limit) {
			$sql .= $this->db->plimit($limit, $offset);
		}

		dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);

		$this->lines = array();
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			if ($num) {
				while ($obj = $this->db->fetch_object($resql)) {
					$line = new Dolresource($this->db);
					$line->id = $obj->rowid;
					$line->ref = $obj->ref;
					$line->address = $obj->address;
					$line->zip = $obj->zip;
					$line->town = $obj->town;
					$line->description = $obj->description;
					$line->country_id = $obj->fk_country;
					$line->state_id = $obj->fk_state;
					$line->fk_code_type_resource = $obj->fk_code_type_resource;
					$line->type_label = $obj->type_label;

					// fetch optionals attributes and labels

					$line->fetch_optionals();

					$this->lines[] = $line;
				}
				$this->db->free($resql);
			}
			return $num;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}

	/**
	 * Update element resource in database
	 *
	 * @param	User	$user		User that modifies
	 * @param	int		$notrigger	0=launch triggers after, 1=disable triggers
	 * @return	int					Return integer if KO: <0, if OK: >0
	 */
	public function updateElementResource($user = null, $notrigger = 0)
	{
		global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (!is_numeric($this->resource_id)) {
			$this->resource_id = 0;
		}
		if (isset($this->resource_type)) {
			$this->resource_type = trim($this->resource_type);
		}
		if (!is_numeric($this->element_id)) {
			$this->element_id = 0;
		}
		if (isset($this->element_type)) {
			$this->element_type = trim($this->element_type);
		}
		if (isset($this->busy)) {
			$this->busy = trim($this->busy);
		}
		if (isset($this->mandatory)) {
			$this->mandatory = trim($this->mandatory);
		}

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX."element_resources SET";
		$sql .= " resource_id = ".(isset($this->resource_id) ? (int) $this->resource_id : "null").",";
		$sql .= " resource_type = ".(isset($this->resource_type) ? "'".$this->db->escape($this->resource_type)."'" : "null").",";
		$sql .= " element_id = ".(isset($this->element_id) ? (int) $this->element_id : "null").",";
		$sql .= " element_type = ".(isset($this->element_type) ? "'".$this->db->escape($this->element_type)."'" : "null").",";
		$sql .= " busy = ".(isset($this->busy) ? (int) $this->busy : "null").",";
		$sql .= " mandatory = ".(isset($this->mandatory) ? (int) $this->mandatory : "null").",";
		$sql .= " tms = ".(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null');
		$sql .= " WHERE rowid=".((int) $this->id);

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
				$result = $this->call_trigger('RESOURCE_MODIFY', $user);
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
	 * Return an array with resources linked to the element
	 *
	 * @param string    $element        Element
	 * @param int       $element_id     Id
	 * @param string    $resource_type  Type
	 * @return array                    Array of resources
	 */
	public function getElementResources($element, $element_id, $resource_type = '')
	{
		$resources = array();

		// Links between objects are stored in this table
		$sql = 'SELECT rowid, resource_id, resource_type, busy, mandatory';
		$sql .= ' FROM '.MAIN_DB_PREFIX.'element_resources';
		$sql .= " WHERE element_id=".((int) $element_id)." AND element_type='".$this->db->escape($element)."'";
		if ($resource_type) {
			$sql .= " AND resource_type LIKE '%".$this->db->escape($resource_type)."%'";
		}
		$sql .= ' ORDER BY resource_type';

		dol_syslog(get_class($this)."::getElementResources", LOG_DEBUG);

		$resources = array();
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$resources[$i] = array(
					'rowid' => $obj->rowid,
					'resource_id' => $obj->resource_id,
					'resource_type'=>$obj->resource_type,
					'busy'=>$obj->busy,
					'mandatory'=>$obj->mandatory
				);
				$i++;
			}
		}

		return $resources;
	}

	/**
	 *  Return an int number of resources linked to the element
	 *
	 * @param		string	$element		Element type
	 * @param		int		$element_id		Element id
	 * @return		int						Nb of resources loaded
	 */
	public function fetchElementResources($element, $element_id)
	{
		$resources = $this->getElementResources($element, $element_id);
		$i = 0;
		foreach ($resources as $nb => $resource) {
			$this->lines[$i] = fetchObjectByElement($resource['resource_id'], $resource['resource_type']);
			$i++;
		}
		return $i;
	}

	/**
	 * Load in cache resource type code (setup in dictionary)
	 *
	 * @return		int		Number of lines loaded, if already loaded: 0, if KO: <0
	 */
	public function loadCacheCodeTypeResource()
	{
		global $langs;

		if (is_array($this->cache_code_type_resource) && count($this->cache_code_type_resource)) {
			return 0; // Cache deja charge
		}

		$sql = "SELECT rowid, code, label, active";
		$sql .= " FROM ".MAIN_DB_PREFIX."c_type_resource";
		$sql .= " WHERE active > 0";
		$sql .= " ORDER BY rowid";
		dol_syslog(get_class($this)."::load_cache_code_type_resource", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num) {
				$obj = $this->db->fetch_object($resql);

				$label = ($langs->trans("ResourceTypeShort".$obj->code) != "ResourceTypeShort".$obj->code ? $langs->trans("ResourceTypeShort".$obj->code) : ($obj->label != '-' ? $obj->label : ''));
				$this->cache_code_type_resource[$obj->rowid]['code'] = $obj->code;
				$this->cache_code_type_resource[$obj->rowid]['label'] = $label;
				$this->cache_code_type_resource[$obj->rowid]['active'] = $obj->active;
				$i++;
			}
			return $num;
		} else {
			dol_print_error($this->db);
			return -1;
		}
	}

	/**
	 * getTooltipContentArray
	 *
	 * @param	array	$params		ex option, infologin
	 * @since	v18
	 * @return	array
	 */
	public function getTooltipContentArray($params)
	{
		global $langs;

		$langs->load('resource');

		$datas = [];

		$datas['picto'] = img_picto('', $this->picto).' <u>'.$langs->trans("Resource").'</u>';
		$datas['ref'] = '<br><b>'.$langs->trans('Ref').':</b> '.$this->ref;
		/*if (isset($this->status)) {
		 $datas['status'] = '<br><b>' . $langs->trans("Status").":</b> ".$this->getLibStatut(5);
		 }*/
		if (isset($this->type_label)) {
			$datas['label'] = '<br><b>'.$langs->trans("ResourceType").":</b> ".$this->type_label;
		}

		return $datas;
	}

	/**
	 * Return clickable link of object (with optional picto)
	 *
	 *	@param      int		$withpicto					Add picto into link
	 *	@param      string	$option						Where point the link ('compta', 'expedition', 'document', ...)
	 *	@param      string	$get_params    				Parameters added to url
	 *	@param		int  	$notooltip					1=Disable tooltip
	 *  @param  	string  $morecss                    Add more css on link
	 *  @param  	int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
	 *	@return     string          					String with URL
	 */
	public function getNomUrl($withpicto = 0, $option = '', $get_params = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
	{
		global $conf, $langs, $hookmanager;

		$result = '';
		$params = [
			'id' => $this->id,
			'objecttype' => $this->element,
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

		$url = DOL_URL_ROOT.'/resource/card.php?id='.$this->id;

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
				$label = $langs->trans("ShowMyObject");
				$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
			}
			$linkclose .= ($label ? ' title="'.dol_escape_htmltag($label, 1).'"' : ' title="tocomplete"');
			$linkclose .= $dataparams.' class="'.$classfortooltip.($morecss ? ' '.$morecss : '').'"';
		} else {
			$linkclose = ($morecss ? ' class="'.$morecss.'"' : '');
		}

		$linkstart = '<a href="'.$url.$get_params.'"';
		$linkstart .= $linkclose.'>';
		$linkend = '</a>';
		/*$linkstart = '<a href="'.DOL_URL_ROOT.'/resource/card.php?id='.$this->id.$get_params.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
		 $linkend = '</a>';*/

		$result .= $linkstart;
		if ($withpicto) {
			$result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), (($withpicto != 2) ? 'class="paddingright"' : ''), 0, 0, $notooltip ? 0 : 1);
		}
		if ($withpicto != 2) {
			$result .= $this->ref;
		}
		$result .= $linkend;

		global $action;
		$hookmanager->initHooks(array($this->element . 'dao'));
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
	 * Get status label
	 *
	 * @param	int		$mode		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 6=Long label + Picto
	 * @return	string				Label of status
	 */
	public function getLibStatut($mode = 0)
	{
		return $this->getLibStatusLabel($this->status, $mode);
	}

	/**
	 * Get status
	 *
	 * @param	int		$status		Id status
	 * @param	int		$mode 		0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 5=Long label + Picto
	 * @return	string 				Label of status
	 */
	public static function getLibStatusLabel($status, $mode = 0)
	{
		return '';
	}

	/**
	 *      Load indicators this->nb for state board
	 *
	 * @return	int		Return integer if KO: <0, if OK: >0
	 */
	public function loadStateBoard()
	{
		$this->nb = array();

		$sql = "SELECT count(r.rowid) as nb";
		$sql .= " FROM ".MAIN_DB_PREFIX."resource as r";
		$sql .= " WHERE r.entity IN (".getEntity('resource').")";

		$resql = $this->db->query($sql);
		if ($resql) {
			while ($obj = $this->db->fetch_object($resql)) {
				$this->nb["dolresource"] = $obj->nb;
			}
			$this->db->free($resql);
			return 1;
		} else {
			dol_print_error($this->db);
			$this->error = $this->db->error();
			return -1;
		}
	}
}
