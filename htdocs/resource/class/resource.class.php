<?php
/* Copyright (C) 2013	Jean-François Ferry	<jfefe@aternatik.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

/**
 *  \file      	resource/class/resource.class.php
 *  \ingroup    resource
 *  \brief      Class file for resource object

 */

// Put here all includes required by your class file
require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";

/**
 *	DAO Resource object
 */
class Resource extends CommonObject
{
	var $element='resource';			//!< Id that identify managed objects
	var $table_element='resource';	//!< Name of table without prefix where object is stored

	var $resource_id;
	var $resource_type;
	var $element_id;
	var $element_type;
	var $busy;
	var $mandatory;
	var $fk_user_create;
	var $type_label;
	var $tms='';

    /**
     *  Constructor
     *
     *  @param	DoliDb		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;
        return 1;
    }

    /**
     *  Create object into database
     *
     *  @param	User	$user        User that creates
     *  @param  int		$notrigger   0=launch triggers after, 1=disable triggers
     *  @return int      		   	 <0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
    	$error=0;

    	// Clean parameters

    	if (isset($this->ref)) $this->ref=trim($this->ref);
    	if (isset($this->description)) $this->description=trim($this->description);
    	if (isset($this->fk_code_type_resource)) $this->fk_code_type_resource=trim($this->fk_code_type_resource);
    	if (isset($this->note_public)) $this->note_public=trim($this->note_public);
    	if (isset($this->note_private)) $this->note_private=trim($this->note_private);


    	// Insert request
    	$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";

    	$sql.= " entity,";
    	$sql.= "ref,";
    	$sql.= "description,";
    	$sql.= "fk_code_type_resource,";
    	$sql.= "note_public,";
    	$sql.= "note_private";

    	$sql.= ") VALUES (";

    	$sql.= $conf->entity.", ";
    	$sql.= " ".(! isset($this->ref)?'NULL':"'".$this->db->escape($this->ref)."'").",";
    	$sql.= " ".(! isset($this->description)?'NULL':"'".$this->db->escape($this->description)."'").",";
    	$sql.= " ".(! isset($this->fk_code_type_resource)?'NULL':"'".$this->db->escape($this->fk_code_type_resource)."'").",";
    	$sql.= " ".(! isset($this->note_public)?'NULL':"'".$this->db->escape($this->note_public)."'").",";
    	$sql.= " ".(! isset($this->note_private)?'NULL':"'".$this->db->escape($this->note_private)."'");

    	$sql.= ")";

    	$this->db->begin();

    	dol_syslog(get_class($this)."::create", LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if (! $resql) {
    		$error++; $this->errors[]="Error ".$this->db->lasterror();
    	}

    	if (! $error)
    	{
    		$this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

    		if (! $notrigger)
    		{
    			//// Call triggers
    			//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    			//$interface=new Interfaces($this->db);
    			//$result=$interface->run_triggers('RESOURCE_CREATE',$this,$user,$langs,$conf);
    			//if ($result < 0) { $error++; $this->errors=$interface->errors; }
    			//// End call triggers
    		}
    	}

    	// Commit or rollback
    	if ($error)
    	{
    		foreach($this->errors as $errmsg)
    		{
    			dol_syslog(get_class($this)."::create ".$errmsg, LOG_ERR);
    			$this->error.=($this->error?', '.$errmsg:$errmsg);
    		}
    		$this->db->rollback();
    		return -1*$error;
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
     *    @param      int	$id          id object
     *    @return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;
    	$sql = "SELECT";
    	$sql.= " t.rowid,";
    	$sql.= " t.entity,";
    	$sql.= " t.ref,";
    	$sql.= " t.description,";
    	$sql.= " t.fk_code_type_resource,";
    	$sql.= " t.note_public,";
    	$sql.= " t.note_private,";
    	$sql.= " t.tms,";
    	$sql.= " ty.label as type_label";
    	$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_resource as ty ON ty.code=t.fk_code_type_resource";
    	$sql.= " WHERE t.rowid = ".$this->db->escape($id);

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		if ($this->db->num_rows($resql))
    		{
    			$obj = $this->db->fetch_object($resql);

    			$this->id						=	$obj->rowid;
    			$this->entity					=	$obj->entity;
    			$this->ref						=	$obj->ref;
    			$this->description				=	$obj->description;
    			$this->fk_code_type_resource	=	$obj->fk_code_type_resource;
    			$this->note_public				=	$obj->note_public;
    			$this->note_private				=	$obj->note_private;
    			$this->type_label				=	$obj->type_label;

    		}
    		$this->db->free($resql);

    		return $this->id;
    	}
    	else
    	{
    		$this->error="Error ".$this->db->lasterror();
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
    function update($user=null, $notrigger=0)
    {
    	global $conf, $langs;
    	$error=0;

    	// Clean parameters
    	if (isset($this->ref)) $this->ref=trim($this->ref);
    	if (isset($this->fk_code_type_resource)) $this->fk_code_type_resource=trim($this->fk_code_type_resource);
    	if (isset($this->description)) $this->description=trim($this->description);

    	// Update request
    	$sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
    	$sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
    	$sql.= " description=".(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").",";
    	$sql.= " fk_code_type_resource=".(isset($this->fk_code_type_resource)?"'".$this->db->escape($this->fk_code_type_resource)."'":"null").",";
    	$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null')."";
    	$sql.= " WHERE rowid=".$this->id;

    	$this->db->begin();

    	dol_syslog(get_class($this)."::update", LOG_DEBUG);
    	$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

    	if (! $error)
    	{
    		if (! $notrigger)
    		{
    			// Uncomment this and change MYOBJECT to your own tag if you
    			// want this action calls a trigger.

    			//// Call triggers
    			//include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    			//$interface=new Interfaces($this->db);
    			//$result=$interface->run_triggers('MYOBJECT_MODIFY',$this,$user,$langs,$conf);
    			//if ($result < 0) { $error++; $this->errors=$interface->errors; }
    			//// End call triggers
    		}
    	}

    	// Commit or rollback
    	if ($error)
    	{
    		foreach($this->errors as $errmsg)
    		{
    			dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
    			$this->error.=($this->error?', '.$errmsg:$errmsg);
    		}
    		$this->db->rollback();
    		return -1*$error;
    	}
    	else
    	{
    		$this->db->commit();
    		return 1;
    	}
    }

    /**
     *    Load object in memory from database
     *
     *    @param      int	$id          id object
     *    @return     int         <0 if KO, >0 if OK
     */
    function fetch_element_resource($id)
    {
    	global $langs;
    	$sql = "SELECT";
    	$sql.= " t.rowid,";
   		$sql.= " t.resource_id,";
		$sql.= " t.resource_type,";
		$sql.= " t.element_id,";
		$sql.= " t.element_type,";
		$sql.= " t.busy,";
		$sql.= " t.mandatory,";
		$sql.= " t.fk_user_create,";
		$sql.= " t.tms";
		$sql.= " FROM ".MAIN_DB_PREFIX."element_resources as t";
    	$sql.= " WHERE t.rowid = ".$this->db->escape($id);

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		if ($this->db->num_rows($resql))
    		{
    			$obj = $this->db->fetch_object($resql);

    			$this->id				=	$obj->rowid;
    			$this->resource_id		=	$obj->resource_id;
    			$this->resource_type	=	$obj->resource_type;
    			$this->element_id		=	$obj->element_id;
    			$this->element_type		=	$obj->element_type;
    			$this->busy				=	$obj->busy;
    			$this->mandatory		=	$obj->mandatory;
    			$this->fk_user_create	=	$obj->fk_user_create;

				if($obj->resource_id && $obj->resource_type)
					$this->objresource = fetchObjectByElement($obj->resource_id,$obj->resource_type);
				if($obj->element_id && $obj->element_type)
					$this->objelement = fetchObjectByElement($obj->element_id,$obj->element_type);

    		}
    		$this->db->free($resql);

    		return $this->id;
    	}
    	else
    	{
    		$this->error="Error ".$this->db->lasterror();
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
    function delete($rowid, $notrigger=0)
    {
        global $user,$langs,$conf;

        $error=0;

        if (! $notrigger)
        {
            // Call trigger
            $result=$this->call_trigger('RESOURCE_DELETE',$user);
            if ($result < 0) return -1;
            // End call triggers
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE rowid =".$rowid;

        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $sql = "DELETE FROM ".MAIN_DB_PREFIX."element_resources";
            $sql.= " WHERE element_type='resource' AND resource_id ='".$this->db->escape($rowid)."'";
            dol_syslog(get_class($this)."::delete", LOG_DEBUG);
            if ($this->db->query($sql))
            {
                return 1;
            }
            else {
                $this->error=$this->db->lasterror();
                return -1;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

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
    function fetch_all($sortorder, $sortfield, $limit, $offset, $filter='')
    {
    	global $conf;
    	$sql="SELECT ";
    	$sql.= " t.rowid,";
    	$sql.= " t.entity,";
    	$sql.= " t.ref,";
    	$sql.= " t.description,";
    	$sql.= " t.fk_code_type_resource,";
    	$sql.= " t.tms,";
    	$sql.= " ty.label as type_label";
    	$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_resource as ty ON ty.code=t.fk_code_type_resource";
    	$sql.= " WHERE t.entity IN (".getEntity('resource',1).")";

    	//Manage filter
    	if (!empty($filter)){
    		foreach($filter as $key => $value) {
    			if (strpos($key,'date')) {
    				$sql.= ' AND '.$key.' = \''.$this->db->idate($value).'\'';
    			}
    			else {
    				$sql.= ' AND '.$key.' LIKE \'%'.$value.'%\'';
    			}
    		}
    	}
    	$sql.= " GROUP BY t.rowid";
    	$sql.= $this->db->order($sortfield,$sortorder);
    	if ($limit) $sql.= $this->db->plimit($limit+1,$offset);
    	dol_syslog(get_class($this)."::fetch_all", LOG_DEBUG);

    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		if ($num)
    		{
    			$i = 0;
    			while ($i < $num)
    			{
    				$obj = $this->db->fetch_object($resql);
    				$line = new Resource($this->db);
    				$line->id						=	$obj->rowid;
    				$line->ref						=	$obj->ref;
    				$line->description				=	$obj->description;
    				$line->fk_code_type_resource	=	$obj->fk_code_type_resource;
    				$line->type_label				=	$obj->type_label;

    				$this->lines[$i] = $line;
    				$i++;
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
    function fetch_all_resources($sortorder, $sortfield, $limit, $offset, $filter='')
    {
   		global $conf;
   		$sql="SELECT ";
   		$sql.= " t.rowid,";
   		$sql.= " t.resource_id,";
		$sql.= " t.resource_type,";
		$sql.= " t.element_id,";
		$sql.= " t.element_type,";
		$sql.= " t.busy,";
		$sql.= " t.mandatory,";
		$sql.= " t.fk_user_create,";
		$sql.= " t.tms";
   		$sql.= ' FROM '.MAIN_DB_PREFIX .'element_resources as t ';
   		$sql.= " WHERE t.entity IN (".getEntity('resource',1).")";

   		//Manage filter
   		if (!empty($filter)){
   			foreach($filter as $key => $value) {
   				if (strpos($key,'date')) {
   					$sql.= ' AND '.$key.' = \''.$this->db->idate($value).'\'';
   				}
   				else {
   					$sql.= ' AND '.$key.' LIKE \'%'.$value.'%\'';
   				}
   			}
   		}
   		$sql.= " GROUP BY t.rowid";
    	$sql.= $this->db->order($sortfield,$sortorder);
   		if ($limit) $sql.= $this->db->plimit($limit+1,$offset);
   		dol_syslog(get_class($this)."::fetch_all", LOG_DEBUG);

   		$resql=$this->db->query($sql);
   		if ($resql)
   		{
   			$num = $this->db->num_rows($resql);
   			if ($num)
   			{
   				$i = 0;
   				while ($i < $num)
   				{
   					$obj = $this->db->fetch_object($resql);
   					$line = new Resource($this->db);
   					$line->id				=	$obj->rowid;
   					$line->resource_id		=	$obj->resource_id;
   					$line->resource_type	=	$obj->resource_type;
   					$line->element_id		=	$obj->element_id;
   					$line->element_type		=	$obj->element_type;
   					$line->busy				=	$obj->busy;
   					$line->mandatory		=	$obj->mandatory;
   					$line->fk_user_create	=	$obj->fk_user_create;

					if($obj->resource_id && $obj->resource_type)
						$line->objresource = fetchObjectByElement($obj->resource_id,$obj->resource_type);
					if($obj->element_id && $obj->element_type)
						$line->objelement = fetchObjectByElement($obj->element_id,$obj->element_type);
        			$this->lines[$i] = $line;

   					$i++;
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
    function fetch_all_used($sortorder, $sortfield, $limit, $offset=1, $filter='')
    {
    	global $conf;

    	if ( ! $sortorder) $sortorder="ASC";
    	if ( ! $sortfield) $sortfield="t.rowid";

    	$sql="SELECT ";
    	$sql.= " t.rowid,";
    	$sql.= " t.resource_id,";
    	$sql.= " t.resource_type,";
    	$sql.= " t.element_id,";
    	$sql.= " t.element_type,";
    	$sql.= " t.busy,";
    	$sql.= " t.mandatory,";
    	$sql.= " t.fk_user_create,";
    	$sql.= " t.tms";
    	$sql.= ' FROM '.MAIN_DB_PREFIX .'element_resources as t ';
    	$sql.= " WHERE t.entity IN (".getEntity('resource',1).")";

    	//Manage filter
    	if (!empty($filter)){
    		foreach($filter as $key => $value) {
    			if (strpos($key,'date')) {
    				$sql.= ' AND '.$key.' = \''.$this->db->idate($value).'\'';
    			}
    			else {
    				$sql.= ' AND '.$key.' LIKE \'%'.$value.'%\'';
    			}
    		}
    	}
    	$sql.= " GROUP BY t.resource_id";
    	$sql.= $this->db->order($sortfield,$sortorder);
    	if ($limit) $sql.= $this->db->plimit($limit+1,$offset);
    	dol_syslog(get_class($this)."::fetch_all", LOG_DEBUG);

    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		$num = $this->db->num_rows($resql);
    		if ($num)
    		{
    			$i = 0;
    			while ($i < $num)
    			{
    				$obj = $this->db->fetch_object($resql);
    				$line = new Resource($this->db);
    				$line->id				=	$obj->rowid;
    				$line->resource_id		=	$obj->resource_id;
    				$line->resource_type	=	$obj->resource_type;
    				$line->element_id		=	$obj->element_id;
    				$line->element_type		=	$obj->element_type;
    				$line->busy				=	$obj->busy;
    				$line->mandatory		=	$obj->mandatory;
    				$line->fk_user_create	=	$obj->fk_user_create;

    				$this->lines[$i] = fetchObjectByElement($obj->resource_id,$obj->resource_type);

    				$i++;
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

    /**
     * Fetch all resources available, declared by modules
     * Load available resource in array $this->available_resources
     *
     * @return int 	number of available resources declared by modules
     * @deprecated, remplaced by hook getElementResources
     * @see getElementResources()
     */
    function fetch_all_available() {
    	global $conf;

    	if (! empty($conf->modules_parts['resources']))
    	{
    		$this->available_resources=(array) $conf->modules_parts['resources'];

    		return count($this->available_resources);
    	}
    	return 0;
    }

    /**
     *      Load properties id_previous and id_next
     *
     *      @param	string	$filter		Optional filter
     *	 	@param  int		$fieldid   	Name of field to use for the select MAX and MIN
     *      @return int         		<0 if KO, >0 if OK
     */
    function load_previous_next_ref($filter,$fieldid)
    {
    	global $conf, $user;

    	if (! $this->table_element)
    	{
    		dol_print_error('',get_class($this)."::load_previous_next_ref was called on objet with property table_element not defined");
    		return -1;
    	}

    	// this->ismultientitymanaged contains
    	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
    	$alias = 's';


    	$sql = "SELECT MAX(te.".$fieldid.")";
    	$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as te";
    	if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && empty($user->rights->societe->client->voir))) $sql.= ", ".MAIN_DB_PREFIX."societe as s";	// If we need to link to societe to limit select to entity
    	if (empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON ".$alias.".rowid = sc.fk_soc";
    	$sql.= " WHERE te.".$fieldid." < '".$this->db->escape($this->id)."'";
    	if (empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir) $sql.= " AND sc.fk_user = " .$user->id;
    	if (! empty($filter)) $sql.=" AND ".$filter;
    	if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir)) $sql.= ' AND te.fk_soc = s.rowid';			// If we need to link to societe to limit select to entity
    	if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql.= ' AND te.entity IN ('.getEntity($this->element, 1).')';

    	//print $sql."<br>";
    	$result = $this->db->query($sql);
    	if (! $result)
    	{
    		$this->error=$this->db->error();
    		return -1;
    	}
    	$row = $this->db->fetch_row($result);
    	$this->ref_previous = $row[0];


    	$sql = "SELECT MIN(te.".$fieldid.")";
    	$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as te";
    	if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir)) $sql.= ", ".MAIN_DB_PREFIX."societe as s";	// If we need to link to societe to limit select to entity
    	if (empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON ".$alias.".rowid = sc.fk_soc";
    	$sql.= " WHERE te.".$fieldid." > '".$this->db->escape($this->id)."'";
    	if (empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir) $sql.= " AND sc.fk_user = " .$user->id;
    	if (! empty($filter)) $sql.=" AND ".$filter;
    	if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 2 || ($this->element != 'societe' && empty($this->isnolinkedbythird) && !$user->rights->societe->client->voir)) $sql.= ' AND te.fk_soc = s.rowid';			// If we need to link to societe to limit select to entity
    	if (isset($this->ismultientitymanaged) && $this->ismultientitymanaged == 1) $sql.= ' AND te.entity IN ('.getEntity($this->element, 1).')';
    	// Rem: Bug in some mysql version: SELECT MIN(rowid) FROM llx_socpeople WHERE rowid > 1 when one row in database with rowid=1, returns 1 instead of null

    	//print $sql."<br>";
    	$result = $this->db->query($sql);
    	if (! $result)
    	{
    		$this->error=$this->db->error();
    		return -2;
    	}
    	$row = $this->db->fetch_row($result);
    	$this->ref_next = $row[0];

    	return 1;
    }


    /**
     *  Update element resource into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update_element_resource($user=null, $notrigger=0)
    {
    	global $conf, $langs;
		$error=0;

		// Clean parameters
		if (isset($this->resource_id)) $this->resource_id=trim($this->resource_id);
		if (isset($this->resource_type)) $this->resource_type=trim($this->resource_type);
		if (isset($this->element_id)) $this->element_id=trim($this->element_id);
		if (isset($this->element_type)) $this->element_type=trim($this->element_type);
		if (isset($this->busy)) $this->busy=trim($this->busy);
		if (isset($this->mandatory)) $this->mandatory=trim($this->mandatory);

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."element_resources SET";
		$sql.= " resource_id=".(isset($this->resource_id)?"'".$this->db->escape($this->resource_id)."'":"null").",";
		$sql.= " resource_type=".(isset($this->resource_type)?"'".$this->resource_type."'":"null").",";
		$sql.= " element_id=".(isset($this->element_id)?$this->element_id:"null").",";
		$sql.= " element_type=".(isset($this->element_type)?"'".$this->db->escape($this->element_type)."'":"null").",";
		$sql.= " busy=".(isset($this->busy)?$this->busy:"null").",";
		$sql.= " mandatory=".(isset($this->mandatory)?$this->mandatory:"null").",";
		$sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null')."";

        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
                // Call trigger
                $result=$this->call_trigger('RESOURCE_MODIFY',$user);
                if ($result < 0) $error++;
                // End call triggers
	    	}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::update ".$errmsg, LOG_ERR);
	            $this->error.=($this->error?', '.$errmsg:$errmsg);
			}
			$this->db->rollback();
			return -1*$error;
		}
		else
		{
			$this->db->commit();
			return 1;
		}
    }


    /*
     * Return an array with resources linked to the element
     *
     *
     */
    function getElementResources($element,$element_id,$resource_type='')
    {

	    // Links beetween objects are stored in this table
	    $sql = 'SELECT rowid, resource_id, resource_type, busy, mandatory';
	    $sql.= ' FROM '.MAIN_DB_PREFIX.'element_resources';
	    $sql.= " WHERE element_id='".$element_id."' AND element_type='".$element."'";
	    if($resource_type)
	    	$sql.=" AND resource_type LIKE '%".$resource_type."%'";
	    $sql .= ' ORDER BY resource_type';

	    dol_syslog(get_class($this)."::getElementResources", LOG_DEBUG);
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
    function fetchElementResources($element,$element_id)
    {
        $resources = $this->getElementResources($element,$element_id);
        $i=0;
        foreach($resources as $nb => $resource) {
            $this->lines[$i] = fetchObjectByElement($resource['resource_id'],$resource['resource_type']);
            $i++;
        }
        return $i;

    }


    /**
     *      Load in cache resource type code (setup in dictionary)
     *
     *      @return     int             Nb lignes chargees, 0 si deja chargees, <0 si ko
     */
    function load_cache_code_type_resource()
    {
    	global $langs;

    	if (count($this->cache_code_type_resource)) return 0;    // Cache deja charge

    	$sql = "SELECT rowid, code, label, active";
    	$sql.= " FROM ".MAIN_DB_PREFIX."c_type_resource";
    	$sql.= " WHERE active > 0";
    	$sql.= " ORDER BY rowid";
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
    			$label=($langs->trans("ResourceTypeShort".$obj->code)!=("ResourceTypeShort".$obj->code)?$langs->trans("ResourceTypeShort".$obj->code):($obj->label!='-'?$obj->label:''));
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
     *	@param      int		$withpicto		Add picto into link
     *	@param      string	$option			Where point the link ('compta', 'expedition', 'document', ...)
     *	@param      string	$get_params    	Parametres added to url
     *	@return     string          		String with URL
     */
    function getNomUrl($withpicto=0,$option='', $get_params='')
    {
        global $langs;

        $result='';
        $label=$langs->trans("ShowResource").': '.$this->ref;

        if ($option == '')
        {
            $link = '<a href="'.dol_buildpath('/resource/card.php',1).'?id='.$this->id. $get_params .'" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';
            $picto='resource@resource';
            $label=$langs->trans("ShowResource").': '.$this->ref;

        }

        $linkend='</a>';


        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$link.$this->ref.$linkend;
        return $result;
    }
}
