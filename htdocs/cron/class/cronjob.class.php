<?php
/* Copyright (C) 2007-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2013	Florian Henry	<florian.henry@open-concept.pro>
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
 *  \file       cron/class/cronjob.class.php
 *  \ingroup    cron
 */

// Put here all includes required by your class file
require_once(DOL_DOCUMENT_ROOT."/core/class/commonobject.class.php");


/**
 *	Crob Job class
 */
class Cronjob extends CommonObject
{
	var $element='cronjob';			//!< Id that identify managed objects
	var $table_element='cronjob';		//!< Name of table without prefix where object is stored

    var $jobtype;
	var $tms='';
	var $datec='';
	var $label;
	var $command;
	var $classesname;
	var $objectname;
	var $methodename;
	var $params;
	var $md5params;
	var $module_name;
	var $priority;
	var $datelastrun='';
	var $datenextrun='';
	var $dateend='';
	var $datestart='';
	var $datelastresult='';
	var $lastresult;
	var $lastoutput;
	var $unitfrequency;
	var $frequency;
	var $status;
	var $fk_user_author;
	var $fk_user_mod;
	var $nbrun;
	var $libname;


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

		$now=dol_now();

		// Clean parameters

		if (isset($this->label)) $this->label=trim($this->label);
		if (isset($this->jobtype)) $this->jobtype=trim($this->jobtype);
		if (isset($this->command)) $this->command=trim($this->command);
		if (isset($this->classesname)) $this->classesname=trim($this->classesname);
		if (isset($this->objectname)) $this->objectname=trim($this->objectname);
		if (isset($this->methodename)) $this->methodename=trim($this->methodename);
		if (isset($this->params)) $this->params=trim($this->params);
		if (isset($this->md5params)) $this->md5params=trim($this->md5params);
		if (isset($this->module_name)) $this->module_name=trim($this->module_name);
		if (isset($this->priority)) $this->priority=trim($this->priority);
		if (isset($this->lastoutput)) $this->lastoutput=trim($this->lastoutput);
		if (isset($this->lastresult)) $this->lastresult=trim($this->lastresult);
		if (isset($this->unitfrequency)) $this->unitfrequency=trim($this->unitfrequency);
		if (isset($this->frequency)) $this->frequency=trim($this->frequency);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->note)) $this->note=trim($this->note);
		if (isset($this->nbrun)) $this->nbrun=trim($this->nbrun);
		if (isset($this->libname)) $this->libname = trim($this->libname);
		if (isset($this->test)) $this->test = trim($this->test);
		
		// Check parameters
		// Put here code to add a control on parameters values
		if (dol_strlen($this->datestart)==0) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->trans('CronDtStart'));
			$error++;
		}
		if (empty($this->label)) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->trans('CronLabel'));
			$error++;
		}
		if ((dol_strlen($this->datestart)!=0) && (dol_strlen($this->dateend)!=0) && ($this->dateend<$this->datestart)) {
			$this->errors[]=$langs->trans('CronErrEndDateStartDt');
			$error++;
		}
		if (empty($this->unitfrequency)) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->trans('CronFrequency'));
			$error++;
		}
		if (($this->jobtype=='command') && (empty($this->command))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->trans('CronCommand'));
			$error++;
		}
		if (($this->jobtype=='method') && (empty($this->classesname))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->trans('CronClass'));
			$error++;
		}
		if (($this->jobtype=='method' || $this->jobtype == 'function') && (empty($this->methodename))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->trans('CronMethod'));
			$error++;
		}
		if (($this->jobtype=='method') && (empty($this->objectname))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->trans('CronObject'));
			$error++;
		}

		if (($this->jobtype=='function') && (empty($this->libname))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->trans('CronLib'));
			$error++;
		}

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."cronjob(";

		$sql.= "datec,";
		$sql.= "jobtype,";
		$sql.= "label,";
		$sql.= "command,";
		$sql.= "classesname,";
		$sql.= "objectname,";
		$sql.= "methodename,";
		$sql.= "params,";
		$sql.= "md5params,";
		$sql.= "module_name,";
		$sql.= "priority,";
		$sql.= "datelastrun,";
		$sql.= "datenextrun,";
		$sql.= "dateend,";
		$sql.= "datestart,";
		$sql.= "lastresult,";
		$sql.= "datelastresult,";
		$sql.= "lastoutput,";
		$sql.= "unitfrequency,";
		$sql.= "frequency,";
		$sql.= "status,";
		$sql.= "fk_user_author,";
		$sql.= "fk_user_mod,";
		$sql.= "note,";
		$sql.= "nbrun,";
		$sql.= "maxrun,";
		$sql.= "libname,";
		$sql.= "test";
		$sql.= ") VALUES (";
		$sql.= " '".$this->db->idate($now)."',";
		$sql.= " ".(! isset($this->jobtype)?'NULL':"'".$this->db->escape($this->jobtype)."'").",";
		$sql.= " ".(! isset($this->label)?'NULL':"'".$this->db->escape($this->label)."'").",";
		$sql.= " ".(! isset($this->command)?'NULL':"'".$this->db->escape($this->command)."'").",";
		$sql.= " ".(! isset($this->classesname)?'NULL':"'".$this->db->escape($this->classesname)."'").",";
		$sql.= " ".(! isset($this->objectname)?'NULL':"'".$this->db->escape($this->objectname)."'").",";
		$sql.= " ".(! isset($this->methodename)?'NULL':"'".$this->db->escape($this->methodename)."'").",";
		$sql.= " ".(! isset($this->params)?'NULL':"'".$this->db->escape($this->params)."'").",";
		$sql.= " ".(! isset($this->md5params)?'NULL':"'".$this->db->escape($this->md5params)."'").",";
		$sql.= " ".(! isset($this->module_name)?'NULL':"'".$this->db->escape($this->module_name)."'").",";
		$sql.= " ".(! isset($this->priority)?'0':$this->priority).",";
		$sql.= " ".(! isset($this->datelastrun) || dol_strlen($this->datelastrun)==0?'NULL':$this->db->idate($this->datelastrun)).",";
		$sql.= " ".(! isset($this->datenextrun) || dol_strlen($this->datenextrun)==0?'NULL':$this->db->idate($this->datenextrun)).",";
		$sql.= " ".(! isset($this->dateend) || dol_strlen($this->dateend)==0?'NULL':$this->db->idate($this->dateend)).",";
		$sql.= " ".(! isset($this->datestart) || dol_strlen($this->datestart)==0?'NULL':$this->db->idate($this->datestart)).",";
		$sql.= " ".(! isset($this->lastresult)?'NULL':"'".$this->db->escape($this->lastresult)."'").",";
		$sql.= " ".(! isset($this->datelastresult) || dol_strlen($this->datelastresult)==0?'NULL':$this->db->idate($this->datelastresult)).",";
		$sql.= " ".(! isset($this->lastoutput)?'NULL':"'".$this->db->escape($this->lastoutput)."'").",";
		$sql.= " ".(! isset($this->unitfrequency)?'NULL':"'".$this->unitfrequency."'").",";
		$sql.= " ".(! isset($this->frequency)?'0':$this->frequency).",";
		$sql.= " ".(! isset($this->status)?'0':$this->status).",";
		$sql.= " ".$user->id.",";
		$sql.= " ".$user->id.",";
		$sql.= " ".(! isset($this->note)?'NULL':"'".$this->db->escape($this->note)."'").",";
		$sql.= " ".(! isset($this->nbrun)?'0':$this->db->escape($this->nbrun)).",";
		$sql.= " ".(empty($this->maxrun)?'0':$this->db->escape($this->maxrun)).",";
		$sql.= " ".(! isset($this->libname)?'NULL':"'".$this->db->escape($this->libname)."'").",";
		$sql.= " ".(! isset($this->test)?'NULL':"'".$this->db->escape($this->test)."'")."";
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."cronjob");

			if (! $notrigger)
			{
	            // Uncomment this and change MYOBJECT to your own tag if you
	            // want this action calls a trigger.

	            //// Call triggers
	            //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
	            //$interface=new Interfaces($this->db);
	            //$result=$interface->run_triggers('MYOBJECT_CREATE',$this,$user,$langs,$conf);
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
     *  Load object in memory from the database
     *
     *  @param	int		$id    Id object
     *  @return int          	<0 if KO, >0 if OK
     */
    function fetch($id)
    {
        $sql = "SELECT";
		$sql.= " t.rowid,";

		$sql.= " t.tms,";
		$sql.= " t.datec,";
		$sql.= " t.jobtype,";
		$sql.= " t.label,";
		$sql.= " t.command,";
		$sql.= " t.classesname,";
		$sql.= " t.objectname,";
		$sql.= " t.methodename,";
		$sql.= " t.params,";
		$sql.= " t.md5params,";
		$sql.= " t.module_name,";
		$sql.= " t.priority,";
		$sql.= " t.datelastrun,";
		$sql.= " t.datenextrun,";
		$sql.= " t.dateend,";
		$sql.= " t.datestart,";
		$sql.= " t.lastresult,";
		$sql.= " t.datelastresult,";
		$sql.= " t.lastoutput,";
		$sql.= " t.unitfrequency,";
		$sql.= " t.frequency,";
		$sql.= " t.status,";
		$sql.= " t.fk_user_author,";
		$sql.= " t.fk_user_mod,";
		$sql.= " t.note,";
		$sql.= " t.nbrun,";
		$sql.= " t.maxrun,";
		$sql.= " t.libname,";
		$sql.= " t.test";
        $sql.= " FROM ".MAIN_DB_PREFIX."cronjob as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id    = $obj->rowid;
                $this->ref = $obj->rowid;

				$this->tms = $this->db->jdate($obj->tms);
				$this->datec = $this->db->jdate($obj->datec);
				$this->label = $obj->label;
				$this->jobtype = $obj->jobtype;
				$this->command = $obj->command;
				$this->classesname = $obj->classesname;
				$this->objectname = $obj->objectname;
				$this->methodename = $obj->methodename;
				$this->params = $obj->params;
				$this->md5params = $obj->md5params;
				$this->module_name = $obj->module_name;
				$this->priority = $obj->priority;
				$this->datelastrun = $this->db->jdate($obj->datelastrun);
				$this->datenextrun = $this->db->jdate($obj->datenextrun);
				$this->dateend = $this->db->jdate($obj->dateend);
				$this->datestart = $this->db->jdate($obj->datestart);
				$this->lastresult = $obj->lastresult;
				$this->lastoutput = $obj->lastoutput;
				$this->datelastresult = $this->db->jdate($obj->datelastresult);
				$this->unitfrequency = $obj->unitfrequency;
				$this->frequency = $obj->frequency;
				$this->status = $obj->status;
				$this->fk_user_author = $obj->fk_user_author;
				$this->fk_user_mod = $obj->fk_user_mod;
				$this->note = $obj->note;
				$this->nbrun = $obj->nbrun;
				$this->maxrun = $obj->maxrun;
				$this->libname = $obj->libname;
				$this->test = $obj->test;
            }
            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            return -1;
        }
    }

    /**
     *  Load object in memory from the database
     *
	 *  @param	string		$sortorder    sort order
	 *  @param	string		$sortfield    sort field
	 *  @param	int			$limit		  limit page
	 *  @param	int			$offset    	  page
	 *  @param	int			$status    	  display active or not
	 *  @param	array		$filter    	  filter output
     *  @return int          			<0 if KO, >0 if OK
     */
    function fetch_all($sortorder='DESC', $sortfield='t.rowid', $limit=0, $offset=0, $status=1, $filter='')
    {
    	global $langs;
    	
    	$this->lines=array();
    	
    	$sql = "SELECT";
    	$sql.= " t.rowid,";
    	$sql.= " t.entity,";
    	$sql.= " t.tms,";
    	$sql.= " t.datec,";
    	$sql.= " t.jobtype,";
    	$sql.= " t.label,";
    	$sql.= " t.command,";
    	$sql.= " t.classesname,";
    	$sql.= " t.objectname,";
    	$sql.= " t.methodename,";
    	$sql.= " t.params,";
    	$sql.= " t.md5params,";
    	$sql.= " t.module_name,";
    	$sql.= " t.priority,";
    	$sql.= " t.datelastrun,";
    	$sql.= " t.datenextrun,";
    	$sql.= " t.dateend,";
    	$sql.= " t.datestart,";
    	$sql.= " t.lastresult,";
    	$sql.= " t.datelastresult,";
    	$sql.= " t.lastoutput,";
    	$sql.= " t.unitfrequency,";
    	$sql.= " t.frequency,";
    	$sql.= " t.status,";
    	$sql.= " t.fk_user_author,";
    	$sql.= " t.fk_user_mod,";
    	$sql.= " t.note,";
    	$sql.= " t.nbrun,";
    	$sql.= " t.libname,";
    	$sql.= " t.test";
    	$sql.= " FROM ".MAIN_DB_PREFIX."cronjob as t";
    	$sql.= " WHERE 1 = 1";
    	if ($status >= 0 && $status < 2) $sql.= " AND t.status = ".(empty($status)?'0':'1');
    	if ($status == 2) $sql.= " AND t.status = 2";
    	//Manage filter
    	if (is_array($filter) && count($filter)>0) {
    		foreach($filter as $key => $value) 
    		{
    		    if ($key == 't.rowid') $sql.= ' AND '.$key.' = '.$this->db->escape($value);
   				else $sql.= ' AND '.$key.' LIKE \'%'.$this->db->escape($value).'%\'';
    		}
    	}

    	$sql.= " ORDER BY $sortfield $sortorder ";
    	if (!empty($limit) && !empty($offset)) {
    		$sql.= $this->db->plimit($limit + 1,$offset);
    	}

    	$sqlwhere = array();

    	if (count($sqlwhere)>0) {
    		$sql.= " WHERE ".implode(' AND ',$sqlwhere);
    	}

    	dol_syslog(get_class($this)."::fetch_all", LOG_DEBUG);
    	$resql=$this->db->query($sql);
    	if ($resql)
    	{
    		$num=$this->db->num_rows($resql);
    		$i=0;

    		if ($num)
    		{
	    		while ($i < $num)
	    		{

	    			$line = new Cronjobline();

	    			$obj = $this->db->fetch_object($resql);

	    			$line->id    = $obj->rowid;
	    			$line->ref = $obj->rowid;

	    			$line->entity = $obj->entity;
	    			$line->tms = $this->db->jdate($obj->tms);
	    			$line->datec = $this->db->jdate($obj->datec);
	    			$line->label = $obj->label;
	    			$line->jobtype = $obj->jobtype;
	    			$line->command = $obj->command;
	    			$line->classesname = $obj->classesname;
	    			$line->objectname = $obj->objectname;
	    			$line->methodename = $obj->methodename;
	    			$line->params = $obj->params;
	    			$line->md5params = $obj->md5params;
	    			$line->module_name = $obj->module_name;
	    			$line->priority = $obj->priority;
	    			$line->datelastrun = $this->db->jdate($obj->datelastrun);
	    			$line->datenextrun = $this->db->jdate($obj->datenextrun);
	    			$line->dateend = $this->db->jdate($obj->dateend);
	    			$line->datestart = $this->db->jdate($obj->datestart);
	    			$line->lastresult = $obj->lastresult;
	    			$line->datelastresult = $this->db->jdate($obj->datelastresult);
	    			$line->lastoutput = $obj->lastoutput;
	    			$line->unitfrequency = $obj->unitfrequency;
	    			$line->frequency = $obj->frequency;
	    			$line->status = $obj->status;
	    			$line->fk_user_author = $obj->fk_user_author;
	    			$line->fk_user_mod = $obj->fk_user_mod;
	    			$line->note = $obj->note;
	    			$line->nbrun = $obj->nbrun;
	    			$line->libname = $obj->libname;
	    			$line->test = $obj->test;
	    			$this->lines[]=$line;

	    			$i++;

	    		}
    		}
    		$this->db->free($resql);

    		return 1;
    	}
    	else
    	{
    		$this->error="Error ".$this->db->lasterror();
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

    	$langs->load('cron');

		$error=0;

		// Clean parameters
		if (isset($this->label)) $this->label=trim($this->label);
		if (isset($this->jobtype)) $this->jobtype=trim($this->jobtype);
		if (isset($this->command)) $this->command=trim($this->command);
		if (isset($this->classesname)) $this->classesname=trim($this->classesname);
		if (isset($this->objectname)) $this->objectname=trim($this->objectname);
		if (isset($this->methodename)) $this->methodename=trim($this->methodename);
		if (isset($this->params)) $this->params=trim($this->params);
		if (isset($this->md5params)) $this->md5params=trim($this->md5params);
		if (isset($this->module_name)) $this->module_name=trim($this->module_name);
		if (isset($this->priority)) $this->priority=trim($this->priority);
		if (isset($this->lastoutput)) $this->lastoutput=trim($this->lastoutput);
		if (isset($this->lastresult)) $this->lastresult=trim($this->lastresult);
		if (isset($this->unitfrequency)) $this->unitfrequency=trim($this->unitfrequency);
		if (isset($this->frequency)) $this->frequency=trim($this->frequency);
		if (isset($this->status)) $this->status=trim($this->status);
		if (isset($this->note)) $this->note=trim($this->note);
		if (isset($this->nbrun)) $this->nbrun=trim($this->nbrun);
		if (empty($this->maxrun)) $this->maxrun=0;
        if (isset($this->libname)) $this->libname = trim($this->libname);
        if (isset($this->test)) $this->test = trim($this->test);
        
		// Check parameters
		// Put here code to add a control on parameters values
		if (dol_strlen($this->datestart)==0) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->transnoentitiesnoconv('CronDtStart'));
			$error++;
		}
		if ((dol_strlen($this->datestart)!=0) && (dol_strlen($this->dateend)!=0) && ($this->dateend<$this->datestart)) {
			$this->errors[]=$langs->trans('CronErrEndDateStartDt');
			$error++;
		}
		if (empty($this->label)) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->transnoentitiesnoconv('CronLabel'));
			$error++;
		}
		if (empty($this->unitfrequency)) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->transnoentitiesnoconv('CronFrequency'));
			$error++;
		}
		if (($this->jobtype=='command') && (empty($this->command))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->transnoentitiesnoconv('CronCommand'));
			$error++;
		}
		if (($this->jobtype=='method') && (empty($this->classesname))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->transnoentitiesnoconv('CronClass'));
			$error++;
		}
		if (($this->jobtype=='method' || $this->jobtype == 'function') && (empty($this->methodename))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->transnoentitiesnoconv('CronMethod'));
			$error++;
		}
		if (($this->jobtype=='method') && (empty($this->objectname))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->transnoentitiesnoconv('CronObject'));
			$error++;
		}

		if (($this->jobtype=='function') && (empty($this->libname))) {
			$this->errors[]=$langs->trans('CronFieldMandatory',$langs->transnoentitiesnoconv('CronLib'));
			$error++;
		}


        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."cronjob SET";

		$sql.= " label=".(isset($this->label)?"'".$this->db->escape($this->label)."'":"null").",";
		$sql.= " jobtype=".(isset($this->jobtype)?"'".$this->db->escape($this->jobtype)."'":"null").",";
		$sql.= " command=".(isset($this->command)?"'".$this->db->escape($this->command)."'":"null").",";
		$sql.= " classesname=".(isset($this->classesname)?"'".$this->db->escape($this->classesname)."'":"null").",";
		$sql.= " objectname=".(isset($this->objectname)?"'".$this->db->escape($this->objectname)."'":"null").",";
		$sql.= " methodename=".(isset($this->methodename)?"'".$this->db->escape($this->methodename)."'":"null").",";
		$sql.= " params=".(isset($this->params)?"'".$this->db->escape($this->params)."'":"null").",";
		$sql.= " md5params=".(isset($this->md5params)?"'".$this->db->escape($this->md5params)."'":"null").",";
		$sql.= " module_name=".(isset($this->module_name)?"'".$this->db->escape($this->module_name)."'":"null").",";
		$sql.= " priority=".(isset($this->priority)?$this->priority:"null").",";
		$sql.= " datelastrun=".(dol_strlen($this->datelastrun)!=0 ? "'".$this->db->idate($this->datelastrun)."'" : 'null').",";
		$sql.= " datenextrun=".(dol_strlen($this->datenextrun)!=0 ? "'".$this->db->idate($this->datenextrun)."'" : 'null').",";
		$sql.= " dateend=".(dol_strlen($this->dateend)!=0 ? "'".$this->db->idate($this->dateend)."'" : 'null').",";
		$sql.= " datestart=".(dol_strlen($this->datestart)!=0 ? "'".$this->db->idate($this->datestart)."'" : 'null').",";
		$sql.= " datelastresult=".(dol_strlen($this->datelastresult)!=0 ? "'".$this->db->idate($this->datelastresult)."'" : 'null').",";
		$sql.= " lastresult=".(isset($this->lastresult)?"'".$this->db->escape($this->lastresult)."'":"null").",";
		$sql.= " lastoutput=".(isset($this->lastoutput)?"'".$this->db->escape($this->lastoutput)."'":"null").",";
		$sql.= " unitfrequency=".(isset($this->unitfrequency)?$this->unitfrequency:"null").",";
		$sql.= " frequency=".(isset($this->frequency)?$this->frequency:"null").",";
		$sql.= " status=".(isset($this->status)?$this->status:"null").",";
		$sql.= " fk_user_mod=".$user->id.",";
		$sql.= " note=".(isset($this->note)?"'".$this->db->escape($this->note)."'":"null").",";
		$sql.= " nbrun=".((isset($this->nbrun) && $this->nbrun >0)?$this->nbrun:"null").",";
		$sql.= " maxrun=".((isset($this->maxrun) && $this->maxrun > 0)?$this->maxrun:"0").",";
		$sql.= " libname=".(isset($this->libname)?"'".$this->db->escape($this->libname)."'":"null").",";
		$sql.= " test=".(isset($this->test)?"'".$this->db->escape($this->test)."'":"null");
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
	 *  Delete object in database
	 *
     *	@param  User	$user        User that deletes
     *  @param  int		$notrigger	 0=launch triggers after, 1=disable triggers
	 *  @return	int					 <0 if KO, >0 if OK
	 */
	function delete($user, $notrigger=0)
	{
		$error=0;

		$this->db->begin();

//		if (! $error)
//		{
//			if (! $notrigger)
//			{
				// Uncomment this and change MYOBJECT to your own tag if you
		        // want this action calls a trigger.

		        //// Call triggers
		        //include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
		        //$interface=new Interfaces($this->db);
		        //$result=$interface->run_triggers('MYOBJECT_DELETE',$this,$user,$langs,$conf);
		        //if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        //// End call triggers
//			}
//		}

//		if (! $error)
//		{
    		$sql = "DELETE FROM ".MAIN_DB_PREFIX."cronjob";
    		$sql.= " WHERE rowid=".$this->id;

    		dol_syslog(get_class($this)."::delete", LOG_DEBUG);
    		$resql = $this->db->query($sql);
        	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
//		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delete ".$errmsg, LOG_ERR);
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
	 *	Load an object from its id and create a new one in database
	 *
	 *	@param	int		$fromid     Id of object to clone
	 * 	@return	int					New id of clone
	 */
	function createFromClone($fromid)
	{
		global $user,$langs;

		$error=0;

		$object=new Cronjob($this->db);

		$object->context['createfromclone'] = 'createfromclone';

		$this->db->begin();

		// Load source object
		$object->fetch($fromid);
		$object->id=0;
		$object->statut=0;

		// Clear fields
		// ...

		// Create clone
		$result=$object->create($user);

		// Other options
		if ($result < 0)
		{
			$this->error=$object->error;
			$error++;
		}

		if (! $error)
		{


		}

		unset($this->context['createfromclone']);

		// End
		if (! $error)
		{
			$this->db->commit();
			return $object->id;
		}
		else
		{
			$this->db->rollback();
			return -1;
		}
	}


	/**
	 *	Initialise object with example values
	 *	Id must be 0 if object instance is a specimen
	 *
	 *	@return	void
	 */
	function initAsSpecimen()
	{
		$this->id=0;
		$this->ref=0;

		$this->tms='';
		$this->datec='';
		$this->label='';
		$this->jobtype='';
		$this->command='';
		$this->classesname='';
		$this->objectname='';
		$this->methodename='';
		$this->params='';
		$this->md5params='';
		$this->module_name='';
		$this->priority='';
		$this->datelastrun='';
		$this->datenextrun='';
		$this->dateend='';
		$this->datestart='';
		$this->datelastresult='';
		$this->lastoutput='';
		$this->lastresult='';
		$this->unitfrequency='';
		$this->frequency='';
		$this->status='';
		$this->fk_user_author='';
		$this->fk_user_mod='';
		$this->note='';
		$this->nbrun='';
		$this->maxrun=100;
        $this->libname = '';
	}

	/**
	 *	Load object information
	 *
	 *	@return	int
	 */
	function info()
	{
		$sql = "SELECT";
		$sql.= " f.rowid, f.datec, f.tms, f.fk_user_mod, f.fk_user_author";
		$sql.= " FROM ".MAIN_DB_PREFIX."cronjob as f";
		$sql.= " WHERE f.rowid = ".$this->id;

		dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if ($resql)
		{
			if ($this->db->num_rows($resql))
			{
				$obj = $this->db->fetch_object($resql);
				$this->id = $obj->rowid;
				$this->date_creation = $this->db->jdate($obj->datec);
				$this->date_modification = $this->db->jdate($obj->tms);
				$this->user_modification = $obj->fk_user_mod;
				$this->user_creation = $obj->fk_user_author;
			}
			$this->db->free($resql);

			return 1;
		}
		else
		{
			$this->error="Error ".$this->db->lasterror();
			return -1;
		}
	}


	/**
	 * Run a job.
	 * Once job is finished, status and nb of run is updated. 
	 * This function does not plan the next run. This is done by function ->reprogram_jobs 
	 *
	 * @param   string		$userlogin    	User login
	 * @return	int					 		<0 if KO, >0 if OK
	 */
	function run_jobs($userlogin)
	{
		global $langs, $conf;

		$now=dol_now();
		$error = 0;
		$retval = '';

		$langs->load('cron');

		if (empty($userlogin)) 
		{
			$this->error="User login is mandatory";
			dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
			return -1;
		}

		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$user=new User($this->db);
		$result=$user->fetch('',$userlogin);
		if ($result<0)
		{
			$this->error="User Error:".$user->error;
			dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
			return -1;
		}
		else
		{
			if (empty($user->id))
			{
				$this->error=" User user login:".$userlogin." do not exists";
				dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
				return -1;
			}
		}

		dol_syslog(get_class($this)."::run_jobs jobtype=".$this->jobtype." userlogin=".$userlogin, LOG_DEBUG);

		// Increase limit of time. Works only if we are not in safe mode
		$ExecTimeLimit=600;
		if (!empty($ExecTimeLimit))
		{
			$err=error_reporting();
			error_reporting(0);     // Disable all errors
			//error_reporting(E_ALL);
			@set_time_limit($ExecTimeLimit);   // Need more than 240 on Windows 7/64
			error_reporting($err);
		}
		if (!empty($MemoryLimit))
		{
			@ini_set('memory_limit', $MemoryLimit);
		}

		// Update last run date start (to track running jobs)
		$this->datelastrun=$now;
		$this->datelastresult=null;
		$this->lastoutput='';
		$this->lastresult='';
		$this->nbrun=$this->nbrun + 1;
		$result = $this->update($user);       // This include begin/commit
		if ($result<0) {
			dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
			return -1;
		}

		// Run a method
		if ($this->jobtype=='method')
		{
			// load classes
			if (! $error)
			{
    			$ret=dol_include_once($this->classesname);
    			if ($ret===false || (! class_exists($this->objectname)))
    			{
    			    $this->error=$langs->trans('CronCannotLoadClass',$this->classesname,$this->objectname);
    				dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
    				$this->lastoutput = $this->error;
    				$this->lastresult = -1;
                    $retval = $this->lastresult;
                    $error++;
    			}
			}

			// test if method exists
			if (! $error)
			{
			    if (! method_exists($this->objectname, $this->methodename))
			    {
			        $this->error=$langs->trans('CronMethodDoesNotExists',$this->objectname,$this->methodename);
    				dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
    				$this->lastoutput = $this->error;
    				$this->lastresult = -1;
    				$retval = $this->lastresult;
    				$error++;
			    }
			}
			
			// Load langs
			if (! $error)
			{
				$result=$langs->load($this->module_name.'@'.$this->module_name);
				if ($result < 0)
				{
					dol_syslog(get_class($this)."::run_jobs Cannot load module lang file - ".$langs->error, LOG_ERR);
					$this->error = $langs->error;
					$this->lastoutput = $this->error;
					$this->lastresult = -1;
	                $retval = $this->lastresult;
	                $error++;
				}
			}
			
			if (! $error)
			{
				dol_syslog(get_class($this)."::run_jobs START ".$this->objectname."->".$this->methodename."(".$this->params.");", LOG_DEBUG);
	
				// Create Object for the call module
				$object = new $this->objectname($this->db);
	
				$params_arr = array_map('trim', explode(",",$this->params));
				if (!is_array($params_arr))
				{
					$result = call_user_func(array($object, $this->methodename), $this->params);
				}
				else
				{
					$result = call_user_func_array(array($object, $this->methodename), $params_arr);
				}
			
				if ($result===false || $result != 0)
				{
				    $langs->load("errors");
					dol_syslog(get_class($this)."::run_jobs END result=".$result." error=".$object->error, LOG_ERR);
				    $this->error = $object->error?$object->error:$langs->trans('ErrorUnknown');
					$this->lastoutput = ($object->output?$object->output."\n":"").$this->error;
					$this->lastresult = is_numeric($result)?$result:-1;
		            $retval = $this->lastresult;
		            $error++;
				}
				else
				{
					dol_syslog(get_class($this)."::run_jobs END");
				    $this->lastoutput=$object->output;
					$this->lastresult=var_export($result,true);
					$retval = $this->lastresult;
				}
			}		
		}

		if($this->jobtype == 'function')
		{
			//load lib
			$libpath = '/' . strtolower($this->module_name) . '/lib/' . $this->libname;
			$ret = dol_include_once($libpath);
			if ($ret === false)
			{
				$this->error = $langs->trans('CronCannotLoadLib') . ': ' . $libpath;
				dol_syslog(get_class($this) . "::run_jobs " . $this->error, LOG_ERR);
				return -1;
			}
			// Load langs
			$result=$langs->load($this->module_name . '@' . $this->module_name);
			if ($result<0)
			{
				dol_syslog(get_class($this) . "::run_jobs Cannot load module langs" . $langs->error, LOG_ERR);
				return -1;
			}
			dol_syslog(get_class($this) . "::run_jobs " . $this->libname . "::" . $this->methodename."(" . $this->params . ");", LOG_DEBUG);
			$params_arr = explode(", ", $this->params);
			if (!is_array($params_arr))
			{
				$result = call_user_func($this->methodename, $this->params);
			}
			else
			{
				$result = call_user_func_array($this->methodename, $params_arr);
			}

			if ($result === false || $result != 0)
			{
			    $langs->load("errors");
			    dol_syslog(get_class($this)."::run_jobs result=".$result, LOG_ERR);
			    $this->error = $langs->trans('ErrorUnknown');
			    $this->lastoutput = $this->error;
			    $this->lastresult = is_numeric($result)?$result:-1;
			    $retval = $this->lastresult;
			    $error++;
			}
			else
			{
                $this->lastoutput=var_export($result,true);
                $this->lastresult=var_export($result,true);	// Return code
                $retval = $this->lastresult;
			}
		}

		// Run a command line
		if ($this->jobtype=='command')
		{
			$command=escapeshellcmd($this->command);
			$command.=" 2>&1";
			dol_mkdir($conf->cronjob->dir_temp);
			$outputfile=$conf->cronjob->dir_temp.'/cronjob.'.$userlogin.'.out';

			dol_syslog(get_class($this)."::run_jobs system:".$command, LOG_DEBUG);
			$output_arr=array();

			$execmethod=(empty($conf->global->MAIN_EXEC_USE_POPEN)?1:2);	// 1 or 2
			if ($execmethod == 1)
			{
				exec($command, $output_arr, $retval);
				if ($retval != 0)
				{
				    $langs->load("errors");
				    dol_syslog(get_class($this)."::run_jobs retval=".$retval, LOG_ERR);
				    $this->error = 'Error '.$retval;
				    $this->lastoutput = '';     // Will be filled later
				    $this->lastresult = $retval;
				    $retval = $this->lastresult;
				    $error++;				    
				}
			}
			if ($execmethod == 2)
			{
				$ok=0;
				$handle = fopen($outputfile, 'w');
				if ($handle)
				{
					dol_syslog("Run command ".$command);
					$handlein = popen($command, 'r');
					while (!feof($handlein))
					{
						$read = fgets($handlein);
						fwrite($handle,$read);
						$output_arr[]=$read;
					}
					pclose($handlein);
					fclose($handle);
				}
				if (! empty($conf->global->MAIN_UMASK)) @chmod($outputfile, octdec($conf->global->MAIN_UMASK));
			}

			// Update with result
    		if (is_array($output_arr) && count($output_arr)>0)
    		{
    			foreach($output_arr as $val)
    			{
    				$this->lastoutput.=$val."\n";
    			}
    		}
    		
    		$this->lastresult=$retval;
		
    		dol_syslog(get_class($this)."::run_jobs output_arr:".var_export($output_arr,true)." lastoutput=".$this->lastoutput." lastresult=".$this->lastresult, LOG_DEBUG);
		}
		
		dol_syslog(get_class($this)."::run_jobs now we update job to track it is finished (with success or error)");
		
		$this->datelastresult=dol_now();
		$result = $this->update($user);       // This include begin/commit
		if ($result < 0)
		{
			dol_syslog(get_class($this)."::run_jobs ".$this->error, LOG_ERR);
			return -1;
		}
		else
		{
			return $error?-1:1;
		}

	}

	/**
	 * Reprogram a job
	 *
	 * @param  string		$userlogin      User login
	 * @param  timestamp    $now            Date returned by dol_now()
	 * @return int					        <0 if KO, >0 if OK
	 */
	function reprogram_jobs($userlogin, $now)
	{
		dol_syslog(get_class($this)."::reprogram_jobs userlogin:$userlogin", LOG_DEBUG);
        
		require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
		$user=new User($this->db);
		$result=$user->fetch('',$userlogin);
		if ($result<0) 
		{
			$this->error="User Error:".$user->error;
			dol_syslog(get_class($this)."::reprogram_jobs ".$this->error, LOG_ERR);
			return -1;
		}
		else 
		{
			if (empty($user->id)) 
			{
				$this->error=" User user login:".$userlogin." do not exists";
				dol_syslog(get_class($this)."::reprogram_jobs ".$this->error, LOG_ERR);
				return -1;
			}
		}

		dol_syslog(get_class($this)."::reprogram_jobs  ", LOG_DEBUG);

		
		if (empty($this->datenextrun)) 
		{
			if (empty($this->datestart)) $this->datenextrun = $now + ($this->frequency * $this->unitfrequency);
			else $this->datenextrun = $this->datestart + ($this->frequency * $this->unitfrequency);
		}

		if ($this->datenextrun < $now && $this->frequency > 0 && $this->unitfrequency > 0) 
		{
		    // Loop until date is after future
		    while ($this->datenextrun < $now)
		    {
		        $this->datenextrun += ($this->frequency * $this->unitfrequency);
		        
		        // TODO For exact frequency (every month, every year, ...), use instead a dol_time_plus_duree($time, $duration_value, $duration_unit)
		    }
		}
		else 
		{
			//$this->datenextrun=$this->datenextrun + ($this->frequency * $this->unitfrequency);
		}


		// Archive job
		if ($this->autodelete == 2)
		{
		    if (($this->maxrun > 0 && ($this->nbrun >= $this->maxrun))
		        || ($this->dateend && ($this->datenextrun > $this->dateend)))
		    {
		        $this->status = 2;
		        dol_syslog(get_class($this)."::reprogram_jobs Job will be set to archived", LOG_ERR);
		    }
		}
		
		$result = $this->update($user);
		if ($result<0) 
		{
			dol_syslog(get_class($this)."::reprogram_jobs ".$this->error, LOG_ERR);
			return -1;
		}

		return 1;

	}
}


/**
 *	Crob Job line class
 */
class Cronjobline
{

	var $id;
	var $ref;

	var $tms='';
	var $datec='';
	var $label;
	var $jobtype;
	var $command;
	var $classesname;
	var $objectname;
	var $methodename;
	var $params;
	var $md5params;
	var $module_name;
	var $priority;
	var $datelastrun='';
	var $datenextrun='';
	var $dateend='';
	var $datestart='';
	var $lastresult='';
	var $lastoutput;
	var $unitfrequency;
	var $frequency;
	var $status;
	var $fk_user_author;
	var $fk_user_mod;
	var $note;
	var $nbrun;
	var $libname;

	/**
	 *  Constructor
	 *
	 */
	function __construct()
	{
		return 1;
	}
}
