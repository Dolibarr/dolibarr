<?php
/* Copyright (C) 2013-2015	Jean-François Ferry	<jfefe@aternatik.fr>
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

/**
 *  DAO Resource object
 */
class Dolresource extends CommonObject
{
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

	public $resource_id;
	public $resource_type;
	public $element_id;
	public $element_type;
	public $busy;
	public $mandatory;

	/**
     * @var int ID
     */
	public $fk_user_create;

	public $type_label;
	public $tms = '';

	public $cache_code_type_resource = array();

	public $oldcopy;

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    public function __construct($db)
    {
        $this->db = $db;
    }

    /**
     *  Create object into database
     *
     *  @param	User    $user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    public function create($user, $notrigger = 0)
    {
        global $conf, $langs, $hookmanager;
        $error = 0;

    	// Clean parameters

    	if (isset($this->ref)) $this->ref = trim($this->ref);
    	if (isset($this->description)) $this->description = trim($this->description);
        if (!is_numeric($this->country_id)) $this->country_id = 0;
    	if (isset($this->fk_code_type_resource)) $this->fk_code_type_resource = trim($this->fk_code_type_resource);
    	if (isset($this->note_public)) $this->note_public = trim($this->note_public);
    	if (isset($this->note_private)) $this->note_private = trim($this->note_private);


    	// Insert request
    	$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";

    	$sql .= "entity,";
    	$sql .= "ref,";
    	$sql .= "description,";
    	$sql .= "fk_country,";
    	$sql .= "fk_code_type_resource,";
    	$sql .= "note_public,";
    	$sql .= "note_private";

    	$sql .= ") VALUES (";

    	$sql .= $conf->entity.", ";
    	$sql .= " ".(!isset($this->ref) ? 'NULL' : "'".$this->db->escape($this->ref)."'").",";
    	$sql .= " ".(!isset($this->description) ? 'NULL' : "'".$this->db->escape($this->description)."'").",";
        $sql .= " ".($this->country_id > 0 ? $this->country_id : 'null').",";
    	$sql .= " ".(!isset($this->fk_code_type_resource) ? 'NULL' : "'".$this->db->escape($this->fk_code_type_resource)."'").",";
    	$sql .= " ".(!isset($this->note_public) ? 'NULL' : "'".$this->db->escape($this->note_public)."'").",";
    	$sql .= " ".(!isset($this->note_private) ? 'NULL' : "'".$this->db->escape($this->note_private)."'");

    	$sql .= ")";

    	$this->db->begin();

    	dol_syslog(get_class($this)."::create", LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if (!$resql) {
    		$error++; $this->errors[] = "Error ".$this->db->lasterror();
    	}

    	if (!$error)
    	{
    		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);
    	}

    	if (!$error)
    	{
    		$action = 'create';

    		// Actions on extra fields
   			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
   			{
   				$result = $this->insertExtraFields();
   				if ($result < 0)
   				{
   					$error++;
   				}
    		}
    	}

    	if (!$error)
    	{
    		if (!$notrigger)
    		{
    			//// Call triggers
    			include_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';
    			$interface = new Interfaces($this->db);
    			$result = $interface->run_triggers('RESOURCE_CREATE', $this, $user, $langs, $conf);
    			if ($result < 0) { $error++; $this->errors = $interface->errors; }
    			//// End call triggers
    		}
    	}

    	// Commit or rollback
    	if ($error)
    	{
    		foreach ($this->errors as $errmsg)
    		{
    			dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
    			$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
    		}
    		$this->db->rollback();
    		return -1 * $error;
    	}
    	else
    	{
    		$this->db->commit();
    		return $this->id;
    	}
    }

    /**
     *    Load object in memory from database
     *
     *    @param    int		$id     Id of object
     *    @param	string	$ref	Ref of object
     *    @return   int         	<0 if KO, >0 if OK
     */
    public function fetch($id, $ref = '')
    {
    	global $langs;
    	$sql = "SELECT";
    	$sql .= " t.rowid,";
    	$sql .= " t.entity,";
    	$sql .= " t.ref,";
    	$sql .= " t.description,";
		$sql .= " t.fk_country,";
    	$sql .= " t.fk_code_type_resource,";
    	$sql .= " t.note_public,";
    	$sql .= " t.note_private,";
    	$sql .= " t.tms,";
    	$sql .= " ty.label as type_label";
    	$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
    	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_resource as ty ON ty.code=t.fk_code_type_resource";
    	if ($id) $sql .= " WHERE t.rowid = ".$this->db->escape($id);
    	else $sql .= " WHERE t.ref = '".$this->db->escape($ref)."'";

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		if ($this->db->num_rows($resql))
    		{
    			$obj = $this->db->fetch_object($resql);

    			$this->id = $obj->rowid;
    			$this->entity = $obj->entity;
    			$this->ref = $obj->ref;
    			$this->description				= $obj->description;
                $this->country_id = $obj->fk_country;
    			$this->fk_code_type_resource = $obj->fk_code_type_resource;
    			$this->note_public				= $obj->note_public;
    			$this->note_private = $obj->note_private;
    			$this->type_label = $obj->type_label;

    			// Retreive all extrafield
    			// fetch optionals attributes and labels
    			$this->fetch_optionals();
    		}
    		$this->db->free($resql);

    		return $this->id;
    	}
    	else
    	{
    		$this->error = "Error ".$this->db->lasterror();
    		dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
    		return -1;
    	}
    }


    /**
     *  Update object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    public function update($user = null, $notrigger = 0)
	{
		global $conf, $langs, $hookmanager;
		$error = 0;

		// Clean parameters
		if (isset($this->ref)) $this->ref = trim($this->ref);
		if (isset($this->fk_code_type_resource)) $this->fk_code_type_resource = trim($this->fk_code_type_resource);
		if (isset($this->description)) $this->description = trim($this->description);
        if (!is_numeric($this->country_id)) $this->country_id = 0;

		if (empty($this->oldcopy))
		{
			$org = new self($this->db);
			$org->fetch($this->id);
			$this->oldcopy = $org;
		}

		// Update request
		$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
		$sql .= " ref=".(isset($this->ref) ? "'".$this->db->escape($this->ref)."'" : "null").",";
		$sql .= " description=".(isset($this->description) ? "'".$this->db->escape($this->description)."'" : "null").",";
		$sql .= " fk_country=".($this->country_id > 0 ? $this->country_id : "null").",";
		$sql .= " fk_code_type_resource=".(isset($this->fk_code_type_resource) ? "'".$this->db->escape($this->fk_code_type_resource)."'" : "null").",";
		$sql .= " tms=".(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null')."";
		$sql .= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
		$resql = $this->db->query($sql);
		if (!$resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }

		if (!$error)
		{
			if (!$notrigger)
			{
				// Call trigger
				$result = $this->call_trigger('RESOURCE_MODIFY', $user);
				if ($result < 0) $error++;
				// End call triggers
			}
		}

		if (!$error && (is_object($this->oldcopy) && $this->oldcopy->ref !== $this->ref))
		{
			// We remove directory
			if (!empty($conf->resource->dir_output))
			{
				$olddir = $conf->resource->dir_output."/".dol_sanitizeFileName($this->oldcopy->ref);
				$newdir = $conf->resource->dir_output."/".dol_sanitizeFileName($this->ref);
				if (file_exists($olddir))
				{
					$res = @rename($olddir, $newdir);
					if (!$res)
					{
						$langs->load("errors");
						$this->error = $langs->trans('ErrorFailToRenameDir', $olddir, $newdir);
						$error++;
					}
				}
			}
		}

		if (!$error)
		{
			$action = 'update';

			// Actions on extra fields
			if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
			{
				$result = $this->insertExtraFields();
				if ($result < 0)
				{
					$error++;
				}
			}
		}

		// Commit or rollback
		if ($error)
		{
			foreach ($this->errors as $errmsg)
			{
				dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *    Load object in memory from database
     *
     *    @param      int	$id          id object
     *    @return     int         <0 if KO, >0 if OK
     */
    public function fetch_element_resource($id)
    {
        // phpcs:enable
    	global $langs;
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
    	$sql .= " WHERE t.rowid = ".$this->db->escape($id);

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		if ($this->db->num_rows($resql))
    		{
    			$obj = $this->db->fetch_object($resql);

    			$this->id = $obj->rowid;
    			$this->resource_id = $obj->resource_id;
    			$this->resource_type	= $obj->resource_type;
    			$this->element_id = $obj->element_id;
    			$this->element_type		= $obj->element_type;
    			$this->busy = $obj->busy;
    			$this->mandatory = $obj->mandatory;
    			$this->fk_user_create = $obj->fk_user_create;

				if ($obj->resource_id && $obj->resource_type) {
					$this->objresource = fetchObjectByElement($obj->resource_id, $obj->resource_type);
				}
				if ($obj->element_id && $obj->element_type) {
					$this->objelement = fetchObjectByElement($obj->element_id, $obj->element_type);
				}
    		}
    		$this->db->free($resql);

    		return $this->id;
    	}
    	else
    	{
    		$this->error = "Error ".$this->db->lasterror();
    		return -1;
    	}
    }

    /**
     *    Delete a resource object
     *
     *    @param	int		$rowid			Id of resource line to delete
     *    @param	int		$notrigger		Disable all triggers
     *    @return   int						>0 if OK, <0 if KO
     */
    public function delete($rowid, $notrigger = 0)
	{
		global $user, $langs, $conf;
        require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

		$error = 0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
		$sql .= " WHERE rowid =".$rowid;

		dol_syslog(get_class($this), LOG_DEBUG);
		if ($this->db->query($sql))
		{
			$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_resources";
			$sql .= " WHERE element_type='resource' AND resource_id =".$this->db->escape($rowid);
			dol_syslog(get_class($this)."::delete", LOG_DEBUG);
			$resql = $this->db->query($sql);
			if (!$resql)
			{
				$this->error = $this->db->lasterror();
				$error++;
			}
		}
		else
		{
			$this->error = $this->db->lasterror();
			$error++;
		}

		// Removed extrafields
		if (!$error) {
			$result = $this->deleteExtraFields();
			if ($result < 0)
			{
				$error++;
				dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
			}
		}

		if (!$notrigger)
		{
			// Call trigger
			$result = $this->call_trigger('RESOURCE_DELETE', $user);
			if ($result < 0) $error++;
			// End call triggers
		}

		if (!$error)
		{
			// We remove directory
			$ref = dol_sanitizeFileName($this->ref);
			if (!empty($conf->resource->dir_output))
			{
				$dir = $conf->resource->dir_output."/".dol_sanitizeFileName($this->ref);
				if (file_exists($dir))
				{
					$res = @dol_delete_dir_recursive($dir);
					if (!$res)
					{
						$this->errors[] = 'ErrorFailToDeleteDir';
						$error++;
					}
				}
			}
		}

		if (!$error)
		{
			$this->db->commit();
			return 1;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Load resource objects into $this->lines
     *
     *  @param	string		$sortorder    sort order
     *  @param	string		$sortfield    sort field
     *  @param	int			$limit		  limit page
     *  @param	int			$offset    	  page
     *  @param	array		$filter    	  filter output
     *  @return int          	<0 if KO, >0 if OK
     */
    public function fetch_all($sortorder, $sortfield, $limit, $offset, $filter = '')
    {
        // phpcs:enable
    	global $conf;

    	require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
    	$extrafields = new ExtraFields($this->db);

    	$sql = "SELECT ";
    	$sql .= " t.rowid,";
    	$sql .= " t.entity,";
    	$sql .= " t.ref,";
    	$sql .= " t.description,";
    	$sql .= " t.fk_code_type_resource,";
    	$sql .= " t.tms,";
    	// Add fields from extrafields
    	if (!empty($extrafields->attributes[$this->table_element]['label']))
    		foreach ($extrafields->attributes[$this->table_element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$this->table_element]['type'][$key] != 'separate' ? "ef.".$key.' as options_'.$key.', ' : '');
    	$sql .= " ty.label as type_label";
    	$sql .= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
    	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_resource as ty ON ty.code=t.fk_code_type_resource";
    	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$this->table_element."_extrafields as ef ON ef.fk_object=t.rowid";
    	$sql .= " WHERE t.entity IN (".getEntity('resource').")";
    	// Manage filter
    	if (!empty($filter)) {
    		foreach ($filter as $key => $value) {
    			if (strpos($key, 'date')) {
    				$sql .= ' AND '.$key.' = \''.$this->db->idate($value).'\'';
    			}
    			elseif (strpos($key, 'ef.') !== false) {
    				$sql .= $value;
    			}
    			else {
    				$sql .= ' AND '.$key.' LIKE \'%'.$this->db->escape($value).'%\'';
    			}
    		}
    	}
    	$sql .= $this->db->order($sortfield, $sortorder);
        $this->num_all = 0;
        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        {
            $result = $this->db->query($sql);
            $this->num_all = $this->db->num_rows($result);
        }
    	if ($limit) $sql .= $this->db->plimit($limit, $offset);
    	dol_syslog(get_class($this)."::fetch_all", LOG_DEBUG);

        $this->lines = array();
    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		if ($num)
    		{
    			while ($obj = $this->db->fetch_object($resql))
    			{
    				$line = new Dolresource($this->db);
    				$line->id = $obj->rowid;
    				$line->ref = $obj->ref;
    				$line->description = $obj->description;
                    $line->country_id = $obj->fk_country;
    				$line->fk_code_type_resource = $obj->fk_code_type_resource;
    				$line->type_label = $obj->type_label;

    				// fetch optionals attributes and labels

    				$line->fetch_optionals();

    				$this->lines[] = $line;
    			}
    			$this->db->free($resql);
    		}
    		return $num;
    	}
    	else
    	{
    		$this->error = $this->db->lasterror();
    		return -1;
    	}
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
     /**
     *	Load all objects into $this->lines
     *
     *  @param	string		$sortorder    sort order
	 *  @param	string		$sortfield    sort field
	 *  @param	int			$limit		  limit page
	 *  @param	int			$offset    	  page
	 *  @param	array		$filter    	  filter output
	 *  @return int          	<0 if KO, >0 if OK
     */
    public function fetch_all_resources($sortorder, $sortfield, $limit, $offset, $filter = '')
    {
        // phpcs:enable
   		global $conf;
   		$sql = "SELECT ";
   		$sql .= " t.rowid,";
   		$sql .= " t.resource_id,";
		$sql .= " t.resource_type,";
		$sql .= " t.element_id,";
		$sql .= " t.element_type,";
		$sql .= " t.busy,";
		$sql .= " t.mandatory,";
		$sql .= " t.fk_user_create,";
		$sql .= " t.tms";
   		$sql .= ' FROM '.MAIN_DB_PREFIX.'element_resources as t ';
   		$sql .= " WHERE t.entity IN (".getEntity('resource').")";

   		//Manage filter
   		if (!empty($filter)) {
   			foreach ($filter as $key => $value) {
   				if (strpos($key, 'date')) {
   					$sql .= ' AND '.$key.' = \''.$this->db->idate($value).'\'';
   				}
   				else {
   					$sql .= ' AND '.$key.' LIKE \'%'.$this->db->escape($value).'%\'';
   				}
   			}
   		}
    	$sql .= $this->db->order($sortfield, $sortorder);
   		if ($limit) $sql .= $this->db->plimit($limit + 1, $offset);
   		dol_syslog(get_class($this)."::fetch_all", LOG_DEBUG);

   		$resql = $this->db->query($sql);
   		if ($resql)
   		{
   			$num = $this->db->num_rows($resql);
   			if ($num)
   			{
   				while ($obj = $this->db->fetch_object($resql))
   				{
   					$line = new Dolresource($this->db);
   					$line->id = $obj->rowid;
   					$line->resource_id = $obj->resource_id;
   					$line->resource_type	= $obj->resource_type;
   					$line->element_id = $obj->element_id;
   					$line->element_type		= $obj->element_type;
   					$line->busy = $obj->busy;
   					$line->mandatory = $obj->mandatory;
   					$line->fk_user_create = $obj->fk_user_create;

					if ($obj->resource_id && $obj->resource_type)
						$line->objresource = fetchObjectByElement($obj->resource_id, $obj->resource_type);
					if ($obj->element_id && $obj->element_type)
						$line->objelement = fetchObjectByElement($obj->element_id, $obj->element_type);
        			$this->lines[] = $line;
   				}
   				$this->db->free($resql);
   			}
   			return $num;
   		}
   		else
   		{
   			$this->error = $this->db->lasterror();
   			return -1;
   		}
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *	Load all objects into $this->lines
     *
     *  @param	string		$sortorder    sort order
     *  @param	string		$sortfield    sort field
     *  @param	int			$limit		  limit page
     *  @param	int			$offset    	  page
     *  @param	array		$filter    	  filter output
     *  @return int          	<0 if KO, >0 if OK
     */
    public function fetch_all_used($sortorder, $sortfield, $limit, $offset = 1, $filter = '')
    {
        // phpcs:enable
    	global $conf;

    	if (!$sortorder) $sortorder = "ASC";
    	if (!$sortfield) $sortfield = "t.rowid";

    	$sql = "SELECT ";
    	$sql .= " t.rowid,";
    	$sql .= " t.resource_id,";
    	$sql .= " t.resource_type,";
    	$sql .= " t.element_id,";
    	$sql .= " t.element_type,";
    	$sql .= " t.busy,";
    	$sql .= " t.mandatory,";
    	$sql .= " t.fk_user_create,";
    	$sql .= " t.tms";
    	$sql .= ' FROM '.MAIN_DB_PREFIX.'element_resources as t ';
    	$sql .= " WHERE t.entity IN (".getEntity('resource').")";

    	//Manage filter
    	if (!empty($filter)) {
    		foreach ($filter as $key => $value) {
    			if (strpos($key, 'date')) {
    				$sql .= ' AND '.$key.' = \''.$this->db->idate($value).'\'';
    			}
    			else {
    				$sql .= ' AND '.$key.' LIKE \'%'.$this->db->escape($value).'%\'';
    			}
    		}
    	}
    	$sql .= $this->db->order($sortfield, $sortorder);
    	if ($limit) $sql .= $this->db->plimit($limit + 1, $offset);
    	dol_syslog(get_class($this)."::fetch_all", LOG_DEBUG);

    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		if ($num)
    		{
    			$this->lines = array();
    			while ($obj = $this->db->fetch_object($resql))
    			{
    				$line = new Dolresource($this->db);
    				$line->id = $obj->rowid;
    				$line->resource_id = $obj->resource_id;
    				$line->resource_type	= $obj->resource_type;
    				$line->element_id = $obj->element_id;
    				$line->element_type		= $obj->element_type;
    				$line->busy = $obj->busy;
    				$line->mandatory = $obj->mandatory;
    				$line->fk_user_create = $obj->fk_user_create;

    				$this->lines[] = fetchObjectByElement($obj->resource_id, $obj->resource_type);
    			}
    			$this->db->free($resql);
    		}
    		return $num;
    	}
    	else
    	{
    		$this->error = $this->db->lasterror();
    		return -1;
    	}
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     * Fetch all resources available, declared by modules
     * Load available resource in array $this->available_resources
     *
     * @return int 	number of available resources declared by modules
     * @deprecated, remplaced by hook getElementResources
     * @see getElementResources()
     */
    public function fetch_all_available()
    {
        // phpcs:enable
    	global $conf;

    	if (!empty($conf->modules_parts['resources']))
    	{
    		$this->available_resources = (array) $conf->modules_parts['resources'];

    		return count($this->available_resources);
    	}
    	return 0;
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Update element resource into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    public function update_element_resource($user = null, $notrigger = 0)
    {
        // phpcs:enable
    	global $conf, $langs;
		$error = 0;

		// Clean parameters
		if (isset($this->resource_id)) $this->resource_id = trim($this->resource_id);
		if (isset($this->resource_type)) $this->resource_type = trim($this->resource_type);
		if (isset($this->element_id)) $this->element_id = trim($this->element_id);
		if (isset($this->element_type)) $this->element_type = trim($this->element_type);
		if (isset($this->busy)) $this->busy = trim($this->busy);
		if (isset($this->mandatory)) $this->mandatory = trim($this->mandatory);

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."element_resources SET";
		$sql .= " resource_id=".(isset($this->resource_id) ? "'".$this->db->escape($this->resource_id)."'" : "null").",";
		$sql .= " resource_type=".(isset($this->resource_type) ? "'".$this->db->escape($this->resource_type)."'" : "null").",";
		$sql .= " element_id=".(isset($this->element_id) ? $this->element_id : "null").",";
		$sql .= " element_type=".(isset($this->element_type) ? "'".$this->db->escape($this->element_type)."'" : "null").",";
		$sql .= " busy=".(isset($this->busy) ? $this->busy : "null").",";
		$sql .= " mandatory=".(isset($this->mandatory) ? $this->mandatory : "null").",";
		$sql .= " tms=".(dol_strlen($this->tms) != 0 ? "'".$this->db->idate($this->tms)."'" : 'null')."";

        $sql .= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (!$resql) { $error++; $this->errors[] = "Error ".$this->db->lasterror(); }

		if (!$error)
		{
			if (!$notrigger)
			{
                // Call trigger
                $result = $this->call_trigger('RESOURCE_MODIFY', $user);
                if ($result < 0) $error++;
                // End call triggers
	    	}
		}

        // Commit or rollback
		if ($error)
		{
			foreach ($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error .= ($this->error ? ', '.$errmsg : $errmsg);
			}
			$this->db->rollback();
			return -1 * $error;
		}
		else
		{
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
     * @return array                    Aray of resources
     */
    public function getElementResources($element, $element_id, $resource_type = '')
    {
	    $resources = array();

	    // Links beetween objects are stored in this table
	    $sql = 'SELECT rowid, resource_id, resource_type, busy, mandatory';
	    $sql .= ' FROM '.MAIN_DB_PREFIX.'element_resources';
	    $sql .= " WHERE element_id=".$element_id." AND element_type='".$this->db->escape($element)."'";
	    if ($resource_type)
	    	$sql .= " AND resource_type LIKE '%".$this->db->escape($resource_type)."%'";
	    $sql .= ' ORDER BY resource_type';

	    dol_syslog(get_class($this)."::getElementResources", LOG_DEBUG);

        $resources = array();
	    $resql = $this->db->query($sql);
	    if ($resql)
	    {
	    	$num = $this->db->num_rows($resql);
	    	$i = 0;
	    	while ($i < $num)
	    	{
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

    /*
     *  Return an int number of resources linked to the element
     *
     *  @return     int
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


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *      Load in cache resource type code (setup in dictionary)
     *
     *      @return     int             Number of lines loaded, 0 if already loaded, <0 if KO
     */
    public function load_cache_code_type_resource()
    {
        // phpcs:enable
    	global $langs;

    	if (is_array($this->cache_code_type_resource) && count($this->cache_code_type_resource)) return 0; // Cache deja charge

    	$sql = "SELECT rowid, code, label, active";
    	$sql .= " FROM ".MAIN_DB_PREFIX."c_type_resource";
    	$sql .= " WHERE active > 0";
    	$sql .= " ORDER BY rowid";
    	dol_syslog(get_class($this)."::load_cache_code_type_resource", LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		$i = 0;
    		while ($i < $num)
    		{
    			$obj = $this->db->fetch_object($resql);
    			// Si traduction existe, on l'utilise, sinon on prend le libelle par defaut
    			$label = ($langs->trans("ResourceTypeShort".$obj->code) != ("ResourceTypeShort".$obj->code) ? $langs->trans("ResourceTypeShort".$obj->code) : ($obj->label != '-' ? $obj->label : ''));
    			$this->cache_code_type_resource[$obj->rowid]['code'] = $obj->code;
    			$this->cache_code_type_resource[$obj->rowid]['label'] = $label;
    			$this->cache_code_type_resource[$obj->rowid]['active'] = $obj->active;
    			$i++;
    		}
    		return $num;
    	}
    	else
    	{
    		dol_print_error($this->db);
    		return -1;
    	}
    }

    /**
     *	Return clicable link of object (with eventually picto)
     *
     *	@param      int		$withpicto					Add picto into link
     *	@param      string	$option						Where point the link ('compta', 'expedition', 'document', ...)
     *	@param      string	$get_params    				Parametres added to url
     *	@param		int  	$notooltip					1=Disable tooltip
     *  @param  	string  $morecss                    Add more css on link
     *  @param  	int     $save_lastsearch_value      -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     *	@return     string          					String with URL
     */
    public function getNomUrl($withpicto = 0, $option = '', $get_params = '', $notooltip = 0, $morecss = '', $save_lastsearch_value = -1)
    {
        global $conf, $langs;

        $result = '';
        $label = '<u>'.$langs->trans("ShowResource").'</u>';
        $label .= '<br>';
        $label .= '<b>'.$langs->trans('Ref').':</b> '.$this->ref;
        /*if (isset($this->status)) {
        	$label.= '<br><b>' . $langs->trans("Status").":</b> ".$this->getLibStatut(5);
        }*/
        if (isset($this->type_label)) {
        	$label.= '<br><b>' . $langs->trans("ResourceType").":</b> ".$this->type_label;
        }

        $url = DOL_URL_ROOT.'/resource/card.php?id='.$this->id;

        if ($option != 'nolink')
        {
        	// Add param to save lastsearch_values or not
        	$add_save_lastsearch_values = ($save_lastsearch_value == 1 ? 1 : 0);
        	if ($save_lastsearch_value == -1 && preg_match('/list\.php/', $_SERVER["PHP_SELF"])) $add_save_lastsearch_values = 1;
        	if ($add_save_lastsearch_values) $url .= '&save_lastsearch_values=1';
        }

        $linkclose = '';
        if (empty($notooltip))
        {
        	if (!empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
        	{
        		$label = $langs->trans("ShowMyObject");
        		$linkclose .= ' alt="'.dol_escape_htmltag($label, 1).'"';
        	}
        	$linkclose .= ' title="'.dol_escape_htmltag($label, 1).'"';
        	$linkclose .= ' class="classfortooltip'.($morecss ? ' '.$morecss : '').'"';
        }
        else $linkclose = ($morecss ? ' class="'.$morecss.'"' : '');

        $linkstart = '<a href="'.$url.$get_params.'"';
        $linkstart .= $linkclose.'>';
        $linkend = '</a>';
        /*$linkstart = '<a href="'.dol_buildpath('/resource/card.php', 1).'?id='.$this->id.$get_params.'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
        $linkend = '</a>';*/

        $result .= $linkstart;
        if ($withpicto) $result .= img_object(($notooltip ? '' : $label), ($this->picto ? $this->picto : 'generic'), ($notooltip ? (($withpicto != 2) ? 'class="paddingright"' : '') : 'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip ? 0 : 1);
        if ($withpicto != 2) $result .= $this->ref;
        $result .= $linkend;

        return $result;
    }


    /**
     *  Retourne le libelle du status d'un user (actif, inactif)
     *
     *  @param	int		$mode          0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *  @return	string 			       Label of status
     */
    public function getLibStatut($mode = 0)
    {
        return $this->LibStatut($this->status, $mode);
    }

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Return the status
     *
     *  @param	int		$status        	Id status
     *  @param  int		$mode          	0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto, 5=Long label + Picto
     *  @return string 			       	Label of status
     */
    public static function LibStatut($status, $mode = 0)
    {
        // phpcs:enable
        global $langs;

        return '';
    }
}
