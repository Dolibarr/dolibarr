<?php
/* Copyright (C) 2013-2015	Jean-François Ferry	<jfefe@aternatik.fr>
 * Copyright (C) 2015       Ion Agorria         <ion@agorria.com>
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
 *  \file      	resource/class/dolresource.class.php
 *  \ingroup    resource
 *  \brief      Class file for resource object

 */

require_once DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php";
require_once DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php";
require_once DOL_DOCUMENT_ROOT."/resource/class/resourcelog.class.php";

/**
 *	DAO Resource object
 */
class Dolresource extends CommonObject
{
    var $element='dolresource';			//!< Id that identify managed objects
    var $table_element='resource';	//!< Name of table without prefix where object is stored
    var $description;

    var $fk_code_type_resource;
    var $available;
    var $type_label;
    var $tms='';
    var $duration_value;
    var $duration_unit;
    var $management_type;
    var $starting_hour;
    var $cache_code_type_resource;

    //Management types
    const MANAGEMENT_TYPE_PLAIN    = 0;
    const MANAGEMENT_TYPE_SCHEDULE = 1;

    //Max tree depth
    const MAX_DEPTH = 50;

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
        global $conf, $langs, $hookmanager;
        $error=0;

        // Clean parameters
        if (isset($this->ref)) $this->ref=trim($this->ref);
        if (isset($this->description)) $this->description=trim($this->description);
        if (isset($this->fk_code_type_resource)) $this->fk_code_type_resource=trim($this->fk_code_type_resource);
        if (isset($this->note_public)) $this->note_public=trim($this->note_public);
        if (isset($this->note_private)) $this->note_private=trim($this->note_private);
        if (empty($this->country_id)) $this->country_id = 0;
        if (isset($this->duration_value)) $this->duration_value=trim($this->duration_value);
        if (empty($this->duration_unit)) $this->duration_unit = 0;
        if (empty($this->available)) $this->available = 0;
        if (empty($this->management_type)) $this->management_type = self::MANAGEMENT_TYPE_PLAIN;
        if (empty($this->starting_hour)) $this->starting_hour = 0;

        // Insert request
        $sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element."(";

        $sql.= "ref,";
        $sql.= "description,";
        $sql.= "fk_code_type_resource,";
        $sql.= "note_public,";
        $sql.= "note_private,";
        $sql.= "fk_country,";
        $sql.= "duration,";
        $sql.= "available,";
        $sql.= "management_type,";
        $sql.= "starting_hour,";
        $sql.= "entity";
        $sql.= ") VALUES (";
        $sql.= " ".(! isset($this->ref)?'NULL':"'".$this->db->escape($this->ref)."'").",";
        $sql.= " ".(! isset($this->description)?'NULL':"'".$this->db->escape($this->description)."'").",";
        $sql.= " ".(! isset($this->fk_code_type_resource)?'NULL':"'".$this->db->escape($this->fk_code_type_resource)."'").",";
        $sql.= " ".(! isset($this->note_public)?'NULL':"'".$this->db->escape($this->note_public)."'").",";
        $sql.= " ".(! isset($this->note_private)?'NULL':"'".$this->db->escape($this->note_private)."'").",";
        $sql.= " ".($this->country_id > 0 ? $this->country_id : 'null').",";
        $sql.= " '".$this->db->escape($this->duration_value . $this->duration_unit)."',";
        $sql.= " ".$this->available.",";
        $sql.= " ".$this->management_type.",";
        $sql.= " ".$this->starting_hour.",";
        $sql.= " ".getEntity('resource', 0);
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

            $action='create';

            // Actions on extra fields (by external module or standard code)
            // TODO le hook fait double emploi avec le trigger !!
            $hookmanager->initHooks(array('resourcedao'));
            $parameters=array();
            $reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
            if (empty($reshook))
            {
                if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
                {
                    $result=$this->insertExtraFields();
                    if ($result < 0)
                    {
                        $error++;
                    }
                }
            }
            else if ($reshook < 0) $error++;
        }

        if (! $error && ! $notrigger)
        {
            // Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('RESOURCE_CREATE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // End call triggers
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
     *  Load a product in memory from database
     *
     *  @param	int		$id      			Id of product/service to load
     *  @param  string	$ref     			Ref of product/service to load
     *  @return int     					<0 if KO, 0 if not found, >0 if OK
     */
    function fetch($id=0, $ref='')
    {
        // Check parameters
        if (! $id && ! $ref)
        {
            $this->error='ErrorWrongParameters';
            dol_print_error(get_class($this)."::fetch ".$this->error);
            return -1;
        }

        $sql = "SELECT";
        $sql.= " t.rowid,";
        $sql.= " t.entity,";
        $sql.= " t.ref,";
        $sql.= " t.description,";
        $sql.= " t.fk_code_type_resource,";
        $sql.= " t.note_public,";
        $sql.= " t.note_private,";
        $sql.= " t.fk_country,";
        $sql.= " t.duration,";
        $sql.= " t.tms,";
        $sql.= " t.available,";
        $sql.= " t.management_type,";
        $sql.= " t.starting_hour,";
        $sql.= " ty.label as type_label";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_resource as ty ON ty.code=t.fk_code_type_resource";
        if ($id) $sql.= " WHERE t.rowid = ".$this->db->escape($id);
        else
        {
            $sql.= " WHERE t.entity IN (".getEntity('resource', 1).")";
            if ($ref) $sql.= " AND t.ref = '".$this->db->escape($ref)."'";
        }

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id						=	$obj->rowid;
                $this->ref						=	$obj->ref;
                $this->description				=	$obj->description;
                $this->fk_code_type_resource	=	$obj->fk_code_type_resource;
                $this->note_public				=	$obj->note_public;
                $this->note_private				=	$obj->note_private;
                $this->country_id				=	$obj->fk_country;
                $this->duration_value			=	substr($obj->duration,0,dol_strlen($obj->duration)-1);
                $this->duration_unit			=	substr($obj->duration,-1);
                $this->available				=	$obj->available;
                $this->management_type			=	$obj->management_type;
                $this->starting_hour			=	$obj->starting_hour;
                $this->type_label				=	$obj->type_label;
                $result = $this->id;

                // fetch optionals attributes and labels
                dol_include_once('/core/class/extrafields.class.php');
                $extrafields=new ExtraFields($this->db);
                $extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
                $this->fetch_optionals($this->id,$extralabels);
            }
            else
            {
                $result = 0;
            }
            $this->db->free($resql);

            return $result;
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
    function update($user, $notrigger=0)
    {
        global $conf, $langs, $hookmanager;
        $error=0;

        // Clean parameters
        if (isset($this->ref)) $this->ref=trim($this->ref);
        if (isset($this->fk_code_type_resource)) $this->fk_code_type_resource=trim($this->fk_code_type_resource);
        if (isset($this->description)) $this->description=trim($this->description);
        if (empty($this->country_id)) $this->country_id = 0;
        if (isset($this->duration_value)) $this->duration_value=trim($this->duration_value);
        if (empty($this->duration_unit)) $this->duration_unit = 0;
        if (empty($this->available)) $this->available = 0;
        if (empty($this->management_type)) $this->management_type = self::MANAGEMENT_TYPE_PLAIN;
        if (empty($this->starting_hour)) $this->starting_hour = 0;

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " ref=".(isset($this->ref)?"'".$this->db->escape($this->ref)."'":"null").",";
        $sql.= " description=".(isset($this->description)?"'".$this->db->escape($this->description)."'":"null").",";
        $sql.= " fk_code_type_resource=".(isset($this->fk_code_type_resource)?"'".$this->db->escape($this->fk_code_type_resource)."'":"null").",";
        $sql.= " tms=".(dol_strlen($this->tms)!=0 ? "'".$this->db->idate($this->tms)."'" : 'null').",";
        $sql.= " fk_country=".($this->country_id > 0 ? $this->country_id : 'null').",";
        $sql.= " duration='".$this->db->escape($this->duration_value . $this->duration_unit)."',";
        $sql.= " available=".$this->available.",";
        $sql.= " management_type=".$this->management_type.",";
        $sql.= " starting_hour=".$this->starting_hour;
        $sql.= " WHERE rowid=".$this->id;

        $this->db->begin();

        dol_syslog(get_class($this)."::update", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

        if (! $error)
        {
            $action='update';

            // Actions on extra fields (by external module or standard code)
            // TODO le hook fait double emploi avec le trigger !!
            $hookmanager->initHooks(array('resourcedao'));
            $parameters=array();
            $reshook=$hookmanager->executeHooks('insertExtraFields',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
            if (empty($reshook))
            {
                if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
                {
                    $result=$this->insertExtraFields();
                    if ($result < 0)
                    {
                        $error++;
                    }
                }
            }
            else if ($reshook < 0) $error++;
        }

        if (! $error && ! $notrigger)
        {
            // Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('RESOURCE_MODIFY',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            // End call triggers
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

        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE rowid =".$rowid;

        dol_syslog(get_class($this), LOG_DEBUG);
        if ($this->db->query($sql))
        {
            $obj = new ResourceLink($this->db);
            $sql = "DELETE FROM ".MAIN_DB_PREFIX.$obj->table_element;
            $sql.= " WHERE resource_id =".$this->db->escape($rowid);
            dol_syslog(get_class($this)."::delete", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if (!$resql)
            {
            	$this->error=$this->db->lasterror();
            	$error++;
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            $error++;
        }

        // Removed extrafields
        if (! $error && empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) { // For avoid conflicts if trigger used
        	$result=$this->deleteExtraFields();
        	if ($result < 0)
        	{
        		$error++;
        		dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
        	}
        }

        if (! $error && ! $notrigger)
        {
        	// Call trigger
        	$result=$this->call_trigger('RESOURCE_DELETE',$user);
        	if ($result < 0) $error++;
        	// End call triggers
        }

        if (! $error)
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
    function fetchAll($sortorder, $sortfield, $limit, $offset, $filter='')
    {
        global $conf;
        $sql="SELECT ";
        $sql.= " t.rowid,";
        $sql.= " t.entity,";
        $sql.= " t.ref,";
        $sql.= " t.description,";
        $sql.= " t.fk_code_type_resource,";
        $sql.= " t.tms,";
        $sql.= " t.fk_country,";
        $sql.= " t.duration,";
        $sql.= " t.available,";
        $sql.= " t.management_type,";
        $sql.= " t.starting_hour,";

    	require_once(DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php');
    	$extrafields=new ExtraFields($this->db);
    	$extralabels=$extrafields->fetch_name_optionals_label($this->table_element,true);
    	if (is_array($extralabels) && count($extralabels)>0) {
    		foreach($extralabels as $label=>$code) {
    			$sql.= " ef.".$code." as extra_".$code.",";
    		}
    	}

    	$sql.= " ty.label as type_label";
    	$sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element." as t";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_type_resource as ty ON ty.code=t.fk_code_type_resource";
    	$sql.= " LEFT JOIN ".MAIN_DB_PREFIX.$this->table_element."_extrafields as ef ON ef.fk_object=t.rowid";
    	$sql.= " WHERE t.entity IN (".getEntity('resource',1).")";

        //Manage filter
        if (!empty($filter)){
            foreach($filter as $key => $value) {
                if (strpos($key,'date')) {
                    $sql.= ' AND '.$key.' = \''.$this->db->idate($value).'\'';
                }
                elseif (strpos($key,'ef.')!==false) {
                    $sql.= $value;
                }
                else {
                    $sql.= ' AND '.$key.' LIKE \'%'.$value.'%\'';
                }
            }
        }
        $sql.= $this->db->order($sortfield,$sortorder);
        $this->num_all = 0;
        if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
        {
            $result = $this->db->query($sql);
            $this->num_all = $this->db->num_rows($result);
        }
        if ($limit) $sql.= $this->db->plimit($limit, $offset);
        dol_syslog(get_class($this)."::fetchAll", LOG_DEBUG);

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
                    $line = new Dolresource($this->db);
                    $line->id						=	$obj->rowid;
                    $line->ref						=	$obj->ref;
                    $line->description				=	$obj->description;
                    $line->fk_code_type_resource	=	$obj->fk_code_type_resource;
                    $line->country_id				=	$obj->fk_country;
                    $line->duration_value			=	substr($obj->duration,0,dol_strlen($obj->duration)-1);
                    $line->duration_unit			=	substr($obj->duration,-1);
                    $line->available				=	$obj->available;
                    $line->management_type			=	$obj->management_type;
                    $line->starting_hour			=	$obj->starting_hour;
                    $line->type_label				=	$obj->type_label;

    				// Retreive all extrafield for thirdparty
    				// fetch optionals attributes and labels

    				$line->fetch_optionals($line->id,$extralabels);

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
     *      Load properties id_previous and id_next
     *
     *      @param	string	$filter		Optional filter
     *	 	@param  int		$fieldid   	Name of field to use for the select MAX and MIN
     *		@param	int		$nodbprefix	Do not include DB prefix to forge table name
     *      @return int         		<0 if KO, >0 if OK
     */
    function load_previous_next_ref($filter,$fieldid,$nodbprefix=0)
    {
        global $user;

        if (! $this->table_element)
        {
            dol_print_error('',get_class($this)."::load_previous_next_ref was called on objet with property table_element not defined");
            return -1;
        }

        // this->ismultientitymanaged contains
        // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
        $alias = 's';

        $sql = "SELECT MAX(te.".$fieldid.")";
        $sql.= " FROM ".(empty($nodbprefix)?MAIN_DB_PREFIX:'').$this->table_element." as te";
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
            $this->error=$this->db->lasterror();
            return -1;
        }
        $row = $this->db->fetch_row($result);
        $this->ref_previous = $row[0];


        $sql = "SELECT MIN(te.".$fieldid.")";
        $sql.= " FROM ".(empty($nodbprefix)?MAIN_DB_PREFIX:'').$this->table_element." as te";
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
            $this->error=$this->db->lasterror();
            return -2;
        }
        $row = $this->db->fetch_row($result);
        $this->ref_next = $row[0];

        return 1;
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
            $picto='resource';
            $label=$langs->trans("ShowResource").': '.$this->ref;

        }

        $linkend='</a>';


        if ($withpicto) $result.=($link.img_object($label, $picto, 'class="classfortooltip"').$linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        $result.=$link.$this->ref.$linkend;
        return $result;
    }

    /**
     * Returns a array of translated management types
     *
     *    @return	array				Translated array
     */
    static function management_types_trans()
    {
        global $langs;

        return array(
            self::MANAGEMENT_TYPE_PLAIN => $langs->trans("Simple"),
            self::MANAGEMENT_TYPE_SCHEDULE => $langs->trans("Schedule"),
        );
    }

    /**
     * Returns a array of schedules in specified date range
     *
     * @param        int    $date_start    Start date
     * @param        int    $date_end      End date
     * @param        array  $statuses      Status filter, empty for all
     * @return       array|int             Schedules retrieved or < 0 if error
     */
    private function getSchedules($date_start, $date_end, $statuses = array())
    {
        if (empty($date_start) || empty($date_end))
        {
            $this->error='ErrorWrongParameters';
            dol_print_error($this->db, get_class($this)."::fetch ".$this->error);
            return -1;
        }

        require_once DOL_DOCUMENT_ROOT.'/resource/class/resourceschedule.class.php';
        $schedules = array();
        $start = dol_print_date($date_start, '%Y');
        $end = dol_print_date($date_end, '%Y');
        //Iterate each year between dates, only store if is valid and has sections
        for ($year = $start; $year <= $end; $year++)
        {
            $schedule = new ResourceSchedule($this->db);
            $result = $schedule->fetch(0, $this->id, $year);
            if ($result > 0)
            {
                $result = $schedule->fetchSections($date_start, $date_end, $statuses);
            }
            if ($result < 0)
            {
                $this->error = $schedule->error;
                $this->errors = $schedule->errors;
                return $result;
            }
            else if ($result == 0)
            {
                $schedule = null;
            }
            $schedules[$year] = $schedule;
        }
        return $schedules;
    }

    /**
     * Returns state in specified date range
     *
     * @param    int        $date_start    Start date
     * @param    int        $date_end      End date
     * @param    int        $booker_id     Booker id
     * @param    string     $booker_type   Booker type
     * @return   int                       < 0 if KO, else status
     */
    public function getStatus($date_start, $date_end, $booker_id = 0, $booker_type = '')
    {
        if (empty($date_start) || empty($date_end))
        {
            $this->error='ErrorWrongParameters';
            dol_print_error($this->db, get_class($this)."::fetch ".$this->error);
            return -1;
        }

        $availables = ResourceStatus::$AVAILABLE;
        $priorities = ResourceStatus::$PRIORITY;
        $status = null;

        if ($this->management_type == self::MANAGEMENT_TYPE_PLAIN)
        {
            $status = ResourceStatus::AVAILABLE;
            //TODO implement
        }
        else if ($this->management_type == self::MANAGEMENT_TYPE_SCHEDULE)
        {
            $taken = false;
            $schedules = $this->getSchedules($date_start, $date_end);
            if (is_int($schedules))
            {
                return $schedules;
            }
            //Iterate each year between dates
            foreach ($schedules as $schedule)
            {
                if ($schedule == null) return ResourceStatus::NO_SCHEDULE;
                //Check each section and stop if a non available status is found
                foreach ($schedule->sections as $section)
                {
                    $section_status = $section->status;
                    //If sections belongs to same booker then ignore the status
                    if ($booker_id && $booker_type)
                    {
                        if ($section->booker_id == $booker_id && $section->booker_type == $booker_type)
                        {
                            $taken = true;
                            $section_status = ResourceStatus::TAKEN;
                        }
                    }
                    //Only update status section if has higher priority
                    if ($status === null || array_search($status, $priorities) < array_search($section_status, $priorities))
                    {
                        $status = $section_status;
                        //Break from loops if status is not available
                        if (!in_array($status, $availables)) break 2;
                    }
                }
            }

            // Set status as taken if one of sections is our and status is available
            if ($taken && in_array($status, $availables))
            {
                $status = ResourceStatus::TAKEN;
            }
        }

        if (!$this->available && in_array($status, $availables)) {
            $status = ResourceStatus::NOT_AVAILABLE;
        }

        dol_syslog(__METHOD__." id=".$this->id." status=".$status, LOG_DEBUG);
        dol_syslog("date_start=".$date_start." date_end=".$date_end." booker_id=".$booker_id." booker_type=".$booker_type, LOG_DEBUG);
        return $status;
    }

    /**
     * Sets the resource status
     *
     * @param   User   $user          User that modifies
     * @param   int    $date_start    Start date
     * @param   int    $date_end      End date
     * @param   array  $target        Specific statuses to update only
     * @param   int    $status        Status to set
     * @param   int    $booker_id     Booker id
     * @param   string $booker_type   Booker type
     * @param   bool   $skip_same     Ignores status that is already set
     * @param   int    $record_action Action to store in log or null to disable
     * @param   bool   $notrigger     false=launch triggers after, true=disable triggers
     * @return  int                   <0 if KO, 0> if OK
     */
    public function setStatus($user, $date_start, $date_end, $target, $status, $booker_id, $booker_type, $skip_same = true, $record_action = ResourceLog::STATUS_CHANGE, $notrigger = false)
    {
        global $langs,$conf;

        dol_syslog(__METHOD__." id=".$this->id." target=".implode(",", $target)." status=".$status, LOG_DEBUG);
        dol_syslog("date_start=".$date_start." date_end=".$date_end." booker_id=".$booker_id." booker_type=".$booker_type, LOG_DEBUG);

        $error = 0;
        $this->db->begin();

        if (!$this->available)
        {
            $this->errors[] = $langs->trans("ErrorResourceNotAvailable");
            $error++;
        }
        elseif ($this->management_type == self::MANAGEMENT_TYPE_PLAIN)
        {
            //TODO implement
        }
        else if ($this->management_type == self::MANAGEMENT_TYPE_SCHEDULE)
        {
            $schedules = $this->getSchedules($date_start, $date_end);
            if (is_int($schedules))
            {
                //this->errors is already filled
                $error++;
            }
            else
            {
                //Iterate each year between dates
                foreach ($schedules as $schedule)
                {
                    if ($schedule == null)
                    {
                        $trans = ResourceStatus::translated();
                        $this->errors[] = $langs->transnoentities('ResourceStatus', $this->ref, $trans[ResourceStatus::NO_SCHEDULE]);
                        $error++;
                        break;
                    }
                    $section = new ResourceScheduleSection($this->db);
                    $section->fk_schedule = $schedule->id;
                    $section->date_start = $date_start;
                    $section->date_end = $date_end;
                    $section->status = $status;
                    $section->booker_id = $booker_id;
                    $section->booker_type = $booker_type;

                    $result = $section->updateSections($target, true, $skip_same);
                    if ($result != 0)
                    {
                        $this->errors = $section->errors;
                        $error++;
                        break;
                    }
                }
            }
        }

        //Log the resource change if was successful
        if (! $error && isset($record_action))
        {
            require_once DOL_DOCUMENT_ROOT."/resource/class/resourcelog.class.php";
            $resourcelog = new ResourceLog($this->db);
            $resourcelog->fk_resource   = $this->id;
            $resourcelog->booker_id     = $booker_id;
            $resourcelog->booker_type   = $booker_type;
            $resourcelog->date_creation = dol_now();
            $resourcelog->date_start    = $date_start;
            $resourcelog->date_end      = $date_end;
            $resourcelog->status        = $status;
            $resourcelog->action        = $record_action;
            $result = $resourcelog->create($user);
            if ($result <= 0)
            {
                $this->errors = $resourcelog->errors;
                $error++;
            }
        }

        if (! $error && ! $notrigger)
        {
            //// Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('RESOURCE_SET_STATUS',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            //// End call triggers
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
     * Switch the resource
     *
     * @param   User   $user                User that modifies
     * @param   int    $date_start         Start date
     * @param   int    $date_end           End date
     * @param   int    $status             Status
     * @param   int    $booker_id          Booker id
     * @param   string $booker_type        Booker type
     * @param   int    $new_booker_id      New booker id
     * @param   string $new_booker_type    New booker type
     * @param   bool   $notrigger          false=launch triggers after, true=disable triggers
     * @return  int                        <0 if KO, 0> OK
     */
    public function switchResource($user, $date_start, $date_end, $status, $booker_id, $booker_type, $new_booker_id, $new_booker_type, $notrigger = false)
    {
        global $langs,$conf;

        dol_syslog(__METHOD__." id=".$this->id." status=".$status." new_booker_id=".$new_booker_id." new_booker_type=".$new_booker_type, LOG_DEBUG);
        dol_syslog("date_start=".$date_start." date_end=".$date_end." booker_id=".$booker_id." booker_type=".$booker_type, LOG_DEBUG);

        $error = 0;
        $this->db->begin();

        if ($this->management_type == self::MANAGEMENT_TYPE_PLAIN)
        {
            //TODO implement
        }
        else if ($this->management_type == self::MANAGEMENT_TYPE_SCHEDULE)
        {
            $schedules = $this->getSchedules($date_start, $date_end);
            if (is_int($schedules))
            {
                //this->errors is already filled
                $error++;
            }
            //Iterate each year between dates
            foreach ($schedules as $schedule)
            {
                if ($schedule == null)
                {
                    $trans = ResourceStatus::translated();
                    $this->errors[] = $langs->transnoentities('ResourceStatus', $this->ref, $trans[ResourceStatus::NO_SCHEDULE]);
                    $error++;
                    break;
                }
                $section = new ResourceScheduleSection($this->db);
                $section->fk_schedule = $schedule->id;
                $section->date_start = $date_start;
                $section->date_end = $date_end;
                $section->status = $status;
                $section->booker_id = $booker_id;
                $section->booker_type = $booker_type;

                $result = $section->switchSections($new_booker_id, $new_booker_type, true);
                if ($result != 0)
                {
                    $this->errors = $section->errors;
                    $error++;
                    break;
                }
            }
        }

        //Log the resource change if switch was successful
        if (! $error)
        {
            require_once DOL_DOCUMENT_ROOT."/resource/class/resourcelog.class.php";
            $resourcelog = new ResourceLog($this->db);
            $resourcelog->fk_resource   = $this->id;
            $resourcelog->booker_id     = $booker_id;
            $resourcelog->booker_type   = $booker_type;
            $resourcelog->date_creation = dol_now();
            $resourcelog->date_start    = $date_start;
            $resourcelog->date_end      = $date_end;
            $resourcelog->status        = $status;
            $resourcelog->action        = ResourceLog::BOOKER_SWITCH;
            $result = $resourcelog->create($user);
            if ($result <= 0)
            {
                $this->errors = $resourcelog->errors;
                $error++;
            }
        }

        if (! $error && ! $notrigger)
        {
            //// Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('RESOURCE_SWITCH',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            //// End call triggers
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
     * Frees the resource
     *
     * @param   User   $user          User that modifies
     * @param   int    $date_start    Start date
     * @param   int    $date_end      End date
     * @param   int    $status        Status to change
     * @param   int    $booker_id     Booker id
     * @param   string $booker_type   Booker type
     * @param   bool   $notrigger     false=launch triggers after, true=disable triggers
     * @return  int                   <0 if KO, 0> OK
     */
    public function freeResource($user, $date_start, $date_end, $status, $booker_id, $booker_type, $notrigger = false)
    {
        global $langs,$conf;

        dol_syslog(__METHOD__." id=".$this->id." status=".$status, LOG_DEBUG);
        dol_syslog("date_start=".$date_start." date_end=".$date_end." booker_id=".$booker_id." booker_type=".$booker_type, LOG_DEBUG);

        $changed = 0;
        $error = 0;
        $this->db->begin();

        if ($this->management_type == self::MANAGEMENT_TYPE_PLAIN)
        {
            $changed++;
            //TODO implement
        }
        else if ($this->management_type == self::MANAGEMENT_TYPE_SCHEDULE)
        {
            $schedules = $this->getSchedules($date_start, $date_end);
            if (is_int($schedules))
            {
                //this->errors is already filled
                $error++;
            }
            //Iterate each year between dates
            foreach ($schedules as $schedule)
            {
                if ($schedule == null) continue;
                $section = new ResourceScheduleSection($this->db);
                $section->fk_schedule = $schedule->id;
                $section->date_start = $date_start;
                $section->date_end = $date_end;
                $section->status = $status;
                $section->booker_id = $booker_id;
                $section->booker_type = $booker_type;

                $result = $section->restoreSections();
                if ($result < 0)
                {
                    $this->errors = $section->errors;
                    $error++;
                    break;
                }
                if ($result > 0) $changed += $result;
            }
        }

        //Log the resource change if free was successful
        if (! $error && $changed > 0)
        {
            require_once DOL_DOCUMENT_ROOT . "/resource/class/resourcelog.class.php";
            $resourcelog = new ResourceLog($this->db);
            $resourcelog->fk_resource   = $this->id;
            $resourcelog->booker_id     = $booker_id;
            $resourcelog->booker_type   = $booker_type;
            $resourcelog->date_creation = dol_now();
            $resourcelog->date_start    = $date_start;
            $resourcelog->date_end      = $date_end;
            $resourcelog->status        = $status;
            $resourcelog->action        = ResourceLog::RESOURCE_FREE;
            $result = $resourcelog->create($user);
            if ($result <= 0)
            {
                $this->errors = $resourcelog->errors;
                $error++;
            }
        }

        if (! $error && ! $notrigger)
        {
            //// Call triggers
            include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
            $interface=new Interfaces($this->db);
            $result=$interface->run_triggers('RESOURCE_FREE',$this,$user,$langs,$conf);
            if ($result < 0) { $error++; $this->errors=$interface->errors; }
            //// End call triggers
        }

        // Commit or rollback
        if ($error)
        {
            foreach($this->errors as $errmsg)
            {
                dol_syslog(__METHOD__." ".$errmsg, LOG_ERR);
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
}

/**
 *	DAO Resource Link object
 */
class ResourceLink extends CommonObject
{
    var $element='element_resources';		//!< Id that identify managed objects
    var $table_element='element_resources';	//!< Name of table without prefix where object is stored

    var $fk_parent;
    var $resource_id;
    var $resource_type;
    var $element_id;
    var $element_type;
    var $mandatory;
    var $dependency;
    var $fk_user_create;

    //Dependency modes
    const DEPENDENCY_ALL = 0;
    const DEPENDENCY_ALLMANDATORY = 1;
    const DEPENDENCY_SINGLEMANDATORY = 2;
    const DEPENDENCY_SINGLE = 3;

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
        $this->fk_parent = ($this->fk_parent != "" ? intval($this->fk_parent) : 0);
        if (isset($this->resource_id)) $this->resource_id=trim($this->resource_id);
        if (isset($this->resource_type)) $this->resource_type=trim($this->resource_type);
        if (isset($this->element_id)) $this->element_id=trim($this->element_id);
        if (isset($this->element_type)) $this->element_type=trim($this->element_type);
        $this->mandatory = ($this->mandatory != "" ? intval($this->mandatory) : 0);
        $this->dependency = ($this->dependency != "" ? intval($this->dependency) : 0);

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX.$this->table_element." (";
		$sql.= "fk_parent,";
		$sql.= "resource_id,";
		$sql.= "resource_type,";
		$sql.= "element_id,";
		$sql.= "element_type,";
		$sql.= "mandatory,";
		$sql.= "dependency";
		$sql.= ") VALUES (";
		$sql.= $this->fk_parent;
		$sql.= ", ".$this->resource_id;
		$sql.= ", '".$this->db->escape($this->resource_type)."'";
		$sql.= ", '".$this->element_id."'";
		$sql.= ", '".$this->db->escape($this->element_type)."'";
		$sql.= ", '".$this->mandatory."'";
		$sql.= ", '".$this->dependency."'";
		$sql.= ")";

        $this->db->begin();

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if (! $resql) {
            $error++; $this->errors[]="Error ".$this->db->lasterror();
        }

        if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX.$this->table_element);

            if (! $notrigger)
            {
                // Call triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('RESOURCELINK_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) { $error++; $this->errors=$interface->errors; }
                // End call triggers
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
        $sql = "SELECT";
        $sql.= " rowid,";
        $sql.= " fk_parent,";
        $sql.= " resource_id,";
        $sql.= " resource_type,";
        $sql.= " element_id,";
        $sql.= " element_type,";
        $sql.= " mandatory,";
        $sql.= " dependency,";
        $sql.= " fk_user_create,";
        $sql.= " tms";
        $sql.= " FROM ".MAIN_DB_PREFIX.$this->table_element."";
        $sql.= " WHERE rowid = ".$this->db->escape($id);

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id				=	$obj->rowid;
                $this->fk_parent		=	$obj->fk_parent;
                $this->resource_id		=	$obj->resource_id;
                $this->resource_type	=	$obj->resource_type;
                $this->element_id		=	$obj->element_id;
                $this->element_type		=	$obj->element_type;
                $this->mandatory		=	$obj->mandatory;
                $this->dependency		=	$obj->dependency;
                $this->fk_user_create	=	$obj->fk_user_create;
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
     *  Update element resource into database
     *
     *  @param	User	$user        User that modifies
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
     *  @return int     		   	 <0 if KO, >0 if OK
     */
    function update($user, $notrigger=0)
    {
        $error=0;

        // Clean parameters
        $this->fk_parent = ($this->fk_parent != "" ? intval($this->fk_parent) : 0);
        if (isset($this->resource_id)) $this->resource_id=trim($this->resource_id);
        if (isset($this->resource_type)) $this->resource_type=trim($this->resource_type);
        if (isset($this->element_id)) $this->element_id=trim($this->element_id);
        if (isset($this->element_type)) $this->element_type=trim($this->element_type);
        $this->mandatory = ($this->mandatory != "" ? intval($this->mandatory) : 0);
        $this->dependency = ($this->dependency != "" ? intval($this->dependency) : 0);

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX.$this->table_element." SET";
        $sql.= " fk_parent = ".$this->fk_parent.",";
        $sql.= " resource_id=".(isset($this->resource_id)?$this->resource_id:"null").",";
        $sql.= " resource_type=".(isset($this->resource_type)?"'".$this->db->escape($this->resource_type)."'":"null").",";
        $sql.= " element_id=".(isset($this->element_id)?$this->element_id:"null").",";
        $sql.= " element_type=".(isset($this->element_type)?"'".$this->db->escape($this->element_type)."'":"null").",";
        $sql.= " mandatory=".$this->mandatory.",";
        $sql.= " dependency=".$this->dependency.",";
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
                $result=$this->call_trigger('RESOURCELINK_MODIFY',$user);
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

    /**
     *    Delete a resource object
     *
     *    @param	int		$notrigger		Disable all triggers
     *    @return   int						>0 if OK, <0 if KO
     */
    function delete($notrigger=0)
    {
        global $user;

        if (! $notrigger)
        {
            // Call trigger
            $result=$this->call_trigger('RESOURCELINK_DELETE',$user);
            if ($result < 0) return -1;
            // End call triggers
        }

        $sql = "DELETE FROM ".MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE rowid =".$this->id;

        dol_syslog(get_class($this)."::delete", LOG_DEBUG);
        if ($this->db->query($sql))
        {
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     * Return an array with resources linked to the element
     *
     *  @param		int		$element_id			Type of id
     *  @param		string	$element_type		Type of element
     *  @param		string	$resource_type		Type of resource
     *  @param		bool	$mandatory_only		Only account mandatory links
     *  @return		int|array					0 < if KO, array if OK
     */
    function getResourcesLinked($element_id, $element_type, $resource_type='', $mandatory_only = false)
    {
        $resources = array();

        // Links between objects are stored in this table
        $res = new Dolresource($this->db);
        $sql = "SELECT DISTINCT";
        $sql.= " t.resource_id,";
        $sql.= " t.resource_type";
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element.' as t ';
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX.$res->table_element." as tr ON tr.rowid=t.resource_id";
        $sql.= " WHERE tr.entity IN (".getEntity('resource', 1).")";
        $sql.= " AND t.element_id='".$element_id."'";
        $sql.= " AND t.element_type='".$this->db->escape($element_type)."'";
        if ($resource_type)
            $sql.=" AND t.resource_type='".$this->db->escape($resource_type)."'";
        if ($mandatory_only)
            $sql.=" AND t.mandatory = 1";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                $resources[$obj->resource_id] = $obj->resource_type;
                $i++;
            }
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }

        return $resources;
    }

    /**
     * Return an array with elements which have linked the resource
     *
     *  @param		int		$resource_id		Type of id
     *  @param		string	$resource_type		Type of resource
     *  @param		string	$element_type		Type of element
     *  @param		bool	$mandatory_only		Only account mandatory links
     *  @return		int|array					0 < if KO, array if OK
     */
    function getElementLinked($resource_id, $resource_type='', $element_type='', $mandatory_only = false)
    {
        $elements = array();

        // Links between objects are stored in this table
        $sql = "SELECT DISTINCT";
        $sql.= " element_id,";
        $sql.= " element_type";
        $sql.= ' FROM '.MAIN_DB_PREFIX.$this->table_element;
        $sql.= " WHERE resource_id='".$resource_id."'";
        if ($resource_type)
            $sql.= " AND resource_type='".$this->db->escape($resource_type)."'";
        if ($element_type)
            $sql.=" AND element_type='".$this->db->escape($element_type)."'";
        if ($mandatory_only)
            $sql.=" AND mandatory = 1";

        dol_syslog(__METHOD__, LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $obj = $this->db->fetch_object($resql);
                $elements[$obj->element_id] = $obj->element_type;
                $i++;
            }
        } else {
            $this->errors[] = 'Error ' . $this->db->lasterror();
            dol_syslog(__METHOD__ . ' ' . join(',', $this->errors), LOG_ERR);
            return -1;
        }

        return $elements;
    }

    /**
     * Rebuilt the tree structure of resource links for element in the form of a table:
     *                link = resource link object
     *                resource = resource object
     *                childs = link child ids
     *                path = name with full path to the resource
     *                status = the status of resource
     *                level = link depth in tree (1 as root)
     *                root = the root link id
     * These variables are processed by processTreeDependency:
     *                status_priority = the status with highest priority from resource/childs
     *                satisfied = dependency is satisfied
     *                dependency = the dependent resource ids (only on root, includes root itself)
     *
     * @param   int     $element_id         Type of id
     * @param   string  $element_type       Type of element
     * @param   bool    $process_dependency Call to processTreeDependency after fetching tree
     * @param   int     $date_start         Start date
     * @param   int     $date_end           End date
     * @param   int     $booker_id          Booker id
     * @param   string  $booker_type        Booker type
     *
     * @return  array               Array of resources.
     */
    function getFullTree($element_id, $element_type, $process_dependency = false, $date_start = 0, $date_end = 0, $booker_id = 0, $booker_type = '')
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        if ($date_start <= 0) $date_start = dol_now();
        if ($date_end <= 0) $date_end = dol_now()+1;
        $tree = array();

        // Init $resources array
        $res = new Dolresource($this->db);
        $sql = "SELECT DISTINCT t.rowid, t.fk_parent, t.resource_id, t.resource_type, t.mandatory, t.dependency";
        $sql.= ' FROM '.MAIN_DB_PREFIX .$this->table_element.' as t ';
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX.$res->table_element." as tr ON tr.rowid=t.resource_id";
        $sql.= " WHERE tr.entity IN (".getEntity('resource', 1).")";
        $sql.= " AND t.element_id = ".$element_id;
        $sql.= " AND t.element_type = '".$this->db->escape($element_type)."'";

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $availables = ResourceStatus::$AVAILABLE;
            while ($obj = $this->db->fetch_object($resql))
            {
                $id = $obj->rowid;
                $resource = fetchObjectByElement($obj->resource_id, $obj->resource_type);
                if (is_object($resource) && $resource->id == $obj->resource_id && $resource instanceof Dolresource)
                {
                    $status = $resource->getStatus($date_start, $date_end, $booker_id, $booker_type);

                    $resource_type = $obj->resource_type;
                    if ($resource_type == "resource") $resource_type = "dolresource";

                    $link = new ResourceLink($this->db);
                    $link->id = $id;
                    $link->fk_parent = $obj->fk_parent;
                    $link->resource_id = $obj->resource_id;
                    $link->resource_type = $resource_type;
                    $link->mandatory = $obj->mandatory;
                    $link->dependency = $obj->dependency;

                    $tree[$id]['link'] = $link;
                    $tree[$id]['resource'] = $resource;
                    $tree[$id]['path'] = $resource->ref;
                    $tree[$id]['childs'] = array();
                    $tree[$id]['level'] = 1;
                    $tree[$id]['status'] = $status;
                    $tree[$id]['status_priority'] = $status;
                    $tree[$id]['satisfied'] = in_array($status, $availables);
                    $tree[$id]['dependency'] = array($resource->id => $resource);

                    unset($link);
                }
                unset($resource);
            }
        }
        else
        {
            $this->error=$this->db->lasterror();
            dol_syslog(__METHOD__." ".$this->error);
            return -1;
        }

        // We rescan resources to fill the data that can only be filled when all resources are loaded
        foreach($tree as $id => &$data)
        {
            $level = 1;
            $link = $data['link'];

            if (!empty($link->fk_parent)) {
                // Check if parent is valid, if not remove the orphaned entry from tree and db
                if (empty($tree[$link->fk_parent])) {
                    unset($tree[$id]);
                    $resobj = new ResourceLink($this->db);
                    $resobj->id = $id;
                    $resobj->delete();
                    continue;
                }

                // Add myself to parent childs
                $tree[$link->fk_parent]['childs'][] = $id;

                // Go for each parent recursively
                $cursor = $id;
                while ($level <= Dolresource::MAX_DEPTH && !empty($tree[$cursor]['link']->fk_parent))
                {
                    $level++;
                    $cursor = $tree[$cursor]['link']->fk_parent;
                    $data['path'] = $tree[$cursor]['resource']->ref.' >> '.$data['path'];
                }
                $data['root'] = $cursor;
            }
            else
            {
                $data['root'] = $id;
            }
            //Define level
            $data['level'] = $level;
        }
        unset($data); //Release the reference

        //Process dependency if solicited
        if ($process_dependency)
        {
            $this->processTreeDependency($tree);
        }

        $tree=dol_sort_array($tree, 'path', 'asc', true, false, true);
        dol_syslog(__METHOD__." finish", LOG_DEBUG);
        return $tree;
    }

    /**
     *  Process resources dependency settings, changes the provided $tree variable.
     *
     *    @param	array	$tree				Resource tree
     *
     */
    function processTreeDependency(&$tree)
    {
        dol_syslog(__METHOD__, LOG_DEBUG);

        $processed = array();
        $roots = array();
        $availables = ResourceStatus::$AVAILABLE;
        $priorities = ResourceStatus::$PRIORITY;

        //Load roots
        foreach ($tree as $id => $data) {
            if ($data['level'] == 1) {
                $roots[] = $id;
            }
        }

        //Set the roots as initial pending
        $pending = $roots;
        $i = 0;
        $total_count = count($tree);

        //Process while there is pending, there should not be more iterations that tree elements
        while (count($pending) > 0 && $i <= $total_count * 2)
        {
            $i++;
            $id = end($pending);
            $data = $tree[$id];

            //Check if has childs
            $childs = count($data['childs']);
            if ($childs == 0) {
                $status = $data['status_priority'];
                $tree[$id]['satisfied'] = true;
                $processed[$id] = in_array($status, $availables);
                $index = array_search($id, $pending);
                unset($pending[$index]);
            } else {
                //Check if childs are processed or add them to pending if not
                foreach ($data['childs'] as $child_id) {
                    if ($processed[$child_id] === null) {
                        $pending[] = $child_id;
                    } else {
                        $childs--;
                    }
                }
                //All childs are processed
                if ($childs == 0) {
                    $status_priority = $tree[$id]['status_priority'];
                    $child_lowest = null; #Lowest priority child, used for single modes
                    $dependency = $data['link']->dependency;
                    $root_id = $data['root'];
                    //Set the initial satisfied status
                    $satisfied = $dependency == ResourceLink::DEPENDENCY_ALL || $dependency == ResourceLink::DEPENDENCY_ALLMANDATORY;
                    //Iterate each child
                    foreach ($data['childs'] as $child_id)
                    {
                        $available = $processed[$child_id];
                        $child_data = $tree[$child_id];
                        $child_status = $child_data['status_priority'];
                        $child_resource = $child_data['resource'];
                        $mandatory = $child_data['link']->mandatory;

                        //Override current status by more prioritized child status (only if mandatory child)
                        if ($mandatory && array_search($status_priority, $priorities) < array_search($child_status, $priorities))
                        {
                            $status_priority = $child_status;
                        }

                        //Handle dependency mode
                        if (($dependency == ResourceLink::DEPENDENCY_ALL) || ($dependency == ResourceLink::DEPENDENCY_ALLMANDATORY && $mandatory))
                        {
                            //A single child resource is not available, take this unsatisfied
                            if ($available === false) $satisfied = false;
                            $tree[$root_id]['dependency'][$child_resource->id] = $child_resource;
                        }
                        elseif (($dependency == ResourceLink::DEPENDENCY_SINGLE) || ($dependency == ResourceLink::DEPENDENCY_SINGLEMANDATORY && $mandatory))
                        {
                            //A single child resource is available, take this as satisfied
                            if ($available === true) $satisfied = true;

                            //Load this child as default if null
                            if ($child_lowest === null) $child_lowest = $child_data;

                            //Replace this child with previous, as has lower priority
                            if (array_search($child_lowest['status_priority'], $priorities) > array_search($child_status, $priorities))
                            {
                                $child_lowest = $child_data;
                            }
                        }
                    }
                    //If its not satisfied change status_priority (if has lower priority than not available)
                    if (!$satisfied && array_search($status_priority, $priorities) < array_search(ResourceStatus::NOT_AVAILABLE, $priorities))
                    {
                        $status_priority = ResourceStatus::NOT_AVAILABLE;
                    }

                    //Set data
                    $tree[$id]['satisfied'] = $satisfied;
                    $tree[$id]['status_priority'] = $status_priority;

                    //Store the lowest priority resource as dependency for root
                    if ($child_lowest !== null) $tree[$root_id]['dependency'][$child_lowest->id] = $child_lowest;

                    //Store the dependency satisfied and itself to processed array
                    $processed[$id] = $satisfied && in_array($status_priority, $availables);

                    //Unset current from pending
                    $index = array_search($id, $pending);
                    unset($pending[$index]);
                }
            }
        }
    }

    /**
     *    Filters the passed tree, returns root status.
     *
     *    @param	array	$tree				Resource tree
     *    @param	string	$resource_type		Type of resource
     *    @param	int		$exclude_resource	Exclude the leafs with this resource id or containing this resource.
     *    @return	bool						Returns if root is excluded
     */
    function filterTree(&$tree, $resource_type='', $exclude_resource=0)
    {
        if (!empty($resource_type))
        {
            //Keep only resources with this type
            foreach ($tree as $id => $data)
            {
                $link = $data['link'];
                if ($link->resource_type != $resource_type)
                {
                    unset($tree[$id]);
                }
            }
        }

        $root_excluded = false;
        if (!empty($exclude_resource))
        {
            //Remove all linked resources and their parents which have excluded resource
            foreach ($tree as $id => $data) {
                if ($data['resource']->id == $exclude_resource) {
                    $link = $data['link'];
                    if (!empty($link->fk_parent)) {
                        unset($tree[$link->fk_parent]);
                    } else {
                        //Remove this resource without parent, also block root from adding more of this
                        $root_excluded = true;
                        unset($tree[$id]);
                    }
                }
            }
        }

        //Unset if parent is missing, this cleans orphaned leafs caused by $excluderesource
        foreach($tree as $id => $data)
        {
            $link = $data['link'];
            if (!empty($link->fk_parent) && empty($tree[$link->fk_parent])) {
                unset($tree[$id]);
            }
        }

        return $root_excluded;
    }

    /**
     *    Returns the available resources and which ones are not available if quantity is not meet
     *
     *    @param    array   $tree           Resource tree
     *    @param    int     $requested      Requested root resources
     *    @return   array                   Information array
     */
    function getAvailableRoots($tree, $requested)
    {
        dol_syslog(__METHOD__." quantity=".$requested, LOG_DEBUG);

        $need = $requested;
        $availables = ResourceStatus::$AVAILABLE;
        $priorities = ResourceStatus::$PRIORITY;
        $roots = array();
        foreach ($priorities as $_ => $status) $roots[$status] = array();

        //Filter roots
        foreach ($tree as $id => $data) {
            $status = $data['status_priority'];
            if ($data['level'] == 1 && $data['link']->mandatory) {
                $roots[$status][$id] = $data;
            }
        }

        //Separate roots by available/notavailable in priority order until all quantity is satisfied
        $available = array();
        $notavailable = array();
        foreach ($priorities as $priority => $status)
        {
            foreach ($roots[$status] as $id => $data)
            {
                if (in_array($status, $availables) && $need > 0)
                {
                    $available[$id] = $data;
                    $need--;
                }
                else
                {
                    $notavailable[$id] = $data;
                }
            }
        }

        $result = array(
            'available' => $available,
            'notavailable' => $notavailable,
            'need' => $need
        );

        return $result;
    }

    /**
     * Returns a array of translated dependency modes
     *
     *    @return	array				Translated array
     */
    static function dependency_modes_translated()
    {
        global $langs;

        return array(
            ResourceLink::DEPENDENCY_ALL => $langs->trans("AllResources"),
            ResourceLink::DEPENDENCY_ALLMANDATORY => $langs->trans("AllMandatory"),
            ResourceLink::DEPENDENCY_SINGLEMANDATORY => $langs->trans("SingleMandatory"),
            ResourceLink::DEPENDENCY_SINGLE => $langs->trans("SingleResource")
        );
    }
}

/**
 *	Resource statuses
 */
class ResourceStatus
{
    const UNKNOWN        = 0; //Status that is unknown, default status on new created calendar, serves as available
    const NOT_AVAILABLE  = 1; //Status to mark as not available
    const AVAILABLE      = 2; //Status to mark as available
    const OCCUPIED       = 3; //Status to mark as occupied
    const PLACED         = 4; //Status to note that resource might be occupied but is still not confirmed, acts as available
    const NO_SCHEDULE    = 5; //Special status to mark holes or missing calendars, serves as not available
    const TAKEN          = 6; //Status to mark resource as already taken, used internally so not visible for users
    const DEFAULT_STATUS = ResourceStatus::AVAILABLE;

    //Status that are considered available, the rest are marked as not available
    public static $AVAILABLE = array(
        ResourceStatus::TAKEN,
        ResourceStatus::AVAILABLE,
        ResourceStatus::UNKNOWN,
        ResourceStatus::PLACED,
    );

    //Statuses that are assignable manually, the rest are automatic and must not be assigned or changed by users
    public static $MANUAL = array(
        ResourceStatus::AVAILABLE,
        ResourceStatus::UNKNOWN,
        ResourceStatus::NOT_AVAILABLE,
    );

    //Status priority
    //Lower ones get replaced by higher ones when status reporting (so the parent knows the most important status)
    //Lower ones has more priority over higher number when occupying
    public static $PRIORITY = array(
        ResourceStatus::TAKEN,
        ResourceStatus::AVAILABLE,
        ResourceStatus::UNKNOWN,
        ResourceStatus::PLACED,
        ResourceStatus::NO_SCHEDULE,
        ResourceStatus::NOT_AVAILABLE,
        ResourceStatus::OCCUPIED,
    );

    //Statuses that are assigned automatically in occupation process
    public static $OCCUPATION = array(
        ResourceStatus::PLACED,
        ResourceStatus::OCCUPIED,
    );

    //Statuses that can have multiple bookers
    public static $MULTIPLE_BOOKER = array(
        ResourceStatus::PLACED,
    );

    /**
     * Returns a array of translated status
     *
     * @return    array                Translated array
     */
    public static function translated()
    {
        global $langs;

        return array(
            ResourceStatus::UNKNOWN       => $langs->trans("Unknown"),
            ResourceStatus::NOT_AVAILABLE => $langs->trans("NotAvailable"),
            ResourceStatus::AVAILABLE     => $langs->trans("Available"),
            ResourceStatus::PLACED        => $langs->trans("Placed"),
            ResourceStatus::OCCUPIED      => $langs->trans("Occupied"),
            ResourceStatus::NO_SCHEDULE   => $langs->trans("NoSchedule"),
        );
    }

    /**
     * Returns a array of status colors
     *
     * @param  string   $intensity  2 char hexadecimal code array (High, Medium, Low, Base)
     * @return array                Colors array
     */
    public static function colors($intensity)
    {
        return array(                        #          R               G               B
            ResourceStatus::UNKNOWN       => $intensity[0] . $intensity[0] . $intensity[0],
            ResourceStatus::NOT_AVAILABLE => $intensity[1] . $intensity[1] . $intensity[1],
            ResourceStatus::AVAILABLE     => $intensity[2] . $intensity[0] . $intensity[2],
            ResourceStatus::PLACED        => $intensity[2] . $intensity[2] . $intensity[0],
            ResourceStatus::OCCUPIED      => $intensity[0] . $intensity[2] . $intensity[2],
            ResourceStatus::NO_SCHEDULE   => $intensity[1] . $intensity[1] . $intensity[1],
        );
    }
}
