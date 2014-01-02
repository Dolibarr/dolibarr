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
 *  \file      	place/class/resource.class.php
 *  \ingroup    place
 *  \brief      Class file for resource object

 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *	DAO Resource object
 */
class Resource extends CommonObject
{
	var $db;							//!< To store db handler
	var $error;							//!< To return error code (or message)
	var $errors=array();				//!< To return several error codes (or messages)
	//var $element='resource';			//!< Id that identify managed objects
	//var $table_element='llx_resource';	//!< Name of table without prefix where object is stored

    var $id;

	var $resource_id;
	var $resource_type;
	var $element_id;
	var $element_type;
	var $busy;
	var $mandatory;
	var $fk_user_create;
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
     *    Load object in memory from database
     *    @param      id          id object
     *    @return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
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

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
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
					$this->objresource = $this->fetchObjectByElement($obj->resource_id,$obj->resource_type);
				if($obj->element_id && $obj->element_type)
					$this->objelement = $this->fetchObjectByElement($obj->element_id,$obj->element_type);

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
     *	Load all objects into $this->lines
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
   		$sql.= " t.resource_id,";
		$sql.= " t.resource_type,";
		$sql.= " t.element_id,";
		$sql.= " t.element_type,";
		$sql.= " t.busy,";
		$sql.= " t.mandatory,";
		$sql.= " t.fk_user_create,";
		$sql.= " t.tms";
   		$sql.= ' FROM '.MAIN_DB_PREFIX .'element_resources as t ';
   		//$sql.= " WHERE t.entity IN (".getEntity('resource').")";

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
   		$sql.= " ORDER BY $sortfield $sortorder " . $this->db->plimit( $limit + 1 ,$offset);
   		dol_syslog(get_class($this)."::fetch_all sql=".$sql, LOG_DEBUG);

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
						$line->objresource = $this->fetchObjectByElement($obj->resource_id,$obj->resource_type);
					if($obj->element_id && $obj->element_type)
						$line->objelement = $this->fetchObjectByElement($obj->element_id,$obj->element_type);
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
    function fetch_all_used($sortorder="ASC",$sortfield="t.rowid",$limit, $offset, $filter='')
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
    	//$sql.= " WHERE t.entity IN (".getEntity('resource').")";

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
    	$sql.= " ORDER BY $sortfield $sortorder " . $this->db->plimit( $limit + 1 ,$offset);
    	dol_syslog(get_class($this)."::fetch_all sql=".$sql, LOG_DEBUG);

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

    				$this->lines[$i] = $this->fetchObjectByElement($obj->resource_id,$obj->resource_type);

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
     *
     * Load available resource in array $this->available_resources
     *
     *
     * @return int 	number of available resources declared by modules
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
     *  Update object into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
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


		// Check parameters
		// Put here code to add a control on parameters values

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

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            // Call triggers
	            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('RESOURCE_MODIFY',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
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


    /**
     *
     *
     * @param string $element_type Element type project_task
     * @return array
     */
    function getElementProperties($element_type)
    {
    	// Parse element/subelement (ex: project_task)
    	$module = $element = $subelement = $element_type;

    	// If we ask an resource form external module (instead of default path)
    	if (preg_match('/^([^@]+)@([^@]+)$/i',$element_type,$regs))
    	{
    		$element = $subelement = $regs[1];
    		$module 	= $regs[2];
    	}

    	//print '<br />1. element : '.$element.' - module : '.$module .'<br />';

    	if ( preg_match('/^([^_]+)_([^_]+)/i',$element,$regs))
    	{
    		$module = $element = $regs[1];
    		$subelement = $regs[2];
    	}

    	$classfile = strtolower($subelement);
    	$classname = ucfirst($subelement);
    	$classpath = $module.'/class';


    	// For compat
    	if($element_type == "action") {
    		$classpath = 'comm/action/class';
    		$subelement = 'Actioncomm';
    		$classfile = strtolower($subelement);
    		$classname = ucfirst($subelement);
    		$module = 'agenda';
    	}


    	$element_properties = array(
    		'module' => $module,
    		'classpath' => $classpath,
    		'element' => $element,
    		'subelement' => $subelement,
    		'classfile' => $classfile,
    		'classname' => $classname
    	   );
    	return $element_properties;
    }

    /**
     * Fetch an object with element_type and his id
     * Inclusion classes is automatic
     *
     *
     */
    function fetchObjectByElement($element_id,$element_type) {

		global $conf;

		$element_prop = $this->getElementProperties($element_type);

		if (is_array($element_prop) && $conf->$element_prop['module']->enabled)
		{
			dol_include_once('/'.$element_prop['classpath'].'/'.$element_prop['classfile'].'.class.php');

			$objectstat = new $element_prop['classname']($this->db);
			$ret = $objectstat->fetch($element_id);
			if ($ret >= 0)
			{
				return $objectstat;
			}
		}
		return 0;
	}

    /**
     *	Add resources to the actioncom object
     *
     *	@param		int		$element_id			Element id
     *	@param		string	$element_type		Element type
     *	@param		int		$resource_id		Resource id
     *	@param		string	$resource_type		Resource type
     *	@param		array	$resource   		Resources linked with element
     *	@return		int					<=0 if KO, >0 if OK
     */
    function add_element_resource($element_id,$element_type,$resource_id,$resource_element,$busy=0,$mandatory=0)
    {
	    	$this->db->begin();

	    	$sql = "INSERT INTO ".MAIN_DB_PREFIX."element_resources (";
	    	$sql.= "resource_id";
	    	$sql.= ", resource_type";
	    	$sql.= ", element_id";
	    	$sql.= ", element_type";
	    	$sql.= ", busy";
			$sql.= ", mandatory";
	    	$sql.= ") VALUES (";
	    	$sql.= $resource_id;
	    	$sql.= ", '".$resource_element."'";
	    	$sql.= ", '".$element_id."'";
	    	$sql.= ", '".$element_type."'";
	    	$sql.= ", '".$busy."'";
	    	$sql.= ", '".$mandatory."'";
	    	$sql.= ")";

	    	dol_syslog(get_class($this)."::add_element_resource sql=".$sql, LOG_DEBUG);
	    	if ($this->db->query($sql))
	    	{
	    		$this->db->commit();
	    		return 1;
	    	}
	    	else
	    	{
	    		$this->error=$this->db->lasterror();
	    		$this->db->rollback();
	    		return  0;
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

	    dol_syslog(get_class($this)."::getElementResources sql=".$sql);
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

    function fetchElementResources($element,$element_id)
    {
    	$resources = $this->getElementResources($element,$element_id);
    	$i=0;
    	foreach($resources as $nb => $resource)
    	{
    		$this->lines[$i] = $this->fetchObjectByElement($resource['resource_id'],$resource['resource_type']);
    		$i++;
    	}
    	return $i;

    }

    /**
     *    Delete a link to resource line
     *    TODO: move into commonobject class
     *
     *    @param	int		$rowid			Id of resource line to delete
     *    @param	int		$element		element name (for trigger) TODO: use $this->element into commonobject class
     *    @param	int		$notrigger		Disable all triggers
     *    @return   int						>0 if OK, <0 if KO
     */
    function delete_resource($rowid, $element, $notrigger=0)
    {
    	global $user,$langs,$conf;

    	$error=0;

    	$sql = "DELETE FROM ".MAIN_DB_PREFIX."element_resources";
    	$sql.= " WHERE rowid =".$rowid;

    	dol_syslog(get_class($this)."::delete_resource sql=".$sql);
    	if ($this->db->query($sql))
    	{
    		if (! $notrigger)
    		{
    			// Call triggers
    			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    			$interface=new Interfaces($this->db);
    			$result=$interface->run_triggers(strtoupper($element).'_DELETE_RESOURCE',$this,$user,$langs,$conf);
    			if ($result < 0) {
    				$error++; $this->errors=$interface->errors;
    			}
    			// End call triggers
    		}

    		return 1;
    	}
    	else
    	{
    		$this->error=$this->db->lasterror();
    		dol_syslog(get_class($this)."::delete_resource error=".$this->error, LOG_ERR);
    		return -1;
    	}
    }

}
?>
