<?php
/* Copyright (C) 2002-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011	   Juanjo Menent        <jmenent@2byte.es>
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
 *       \file       htdocs/comm/action/class/actioncomm.class.php
 *       \ingroup    agenda
 *       \brief      File of class to manage agenda events (actions)
 */
require_once DOL_DOCUMENT_ROOT.'/comm/action/class/cactioncomm.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonobject.class.php';


/**
 *		Class to manage agenda events (actions)
 */
class ActionComm extends CommonObject
{
    public $element='action';
    public $table_element = 'actioncomm';
    public $table_rowid = 'id';
    protected $ismultientitymanaged = 1;	// 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    var $id;

    var $type_id;		// id into parent table llx_c_actioncomm (will be deprecated into future, link should not be required)
    var $type_code;		// code into parent table llx_c_actioncomm (will be deprecated into future, link should not be required)
    var $type;			// label into parent table llx_c_actioncomm (will be deprecated into future, link should not be required)
    var $code;
    var $label;

    var $date;
    var $datec;			// Date creation record (datec)
    var $datem;			// Date modification record (tms)
    var $author;		// Object user that create action
    var $usermod;		// Object user that modified action

    var $datep;			// Date action start (datep)
    var $datef;			// Date action end (datep2)
    var $dateend;		// ??
    var $durationp = -1;      // -1=Unkown duration
    var $fulldayevent = 0;    // 1=Event on full day
    var $punctual = 1;        // Milestone
    var $percentage;    // Percentage
    var $location;      // Location
	var $transparency;	// Transparency (ical standard). Used to say if people assigned to event are busy or not by event. 0=available, 1=busy, 2=busy (refused events)
    var $priority;      // Free text ('' By default)
    var $note;          // Description

    var $usertodo;		// Object user that must do action
    var $userdone;	 	// Object user that did action

    var $societe;		// Company linked to action (optionnal)
    var $contact;		// Contact linked tot action (optionnal)
    var $fk_project;	// Id of project (optionnal)


    // Properties for links to other objects
    var $fk_element;    // Id of record
    var $elementtype;   // Type of record. This if property ->element of object linked to.

    // Ical
    var $icalname;
    var $icalcolor;

    var $actions=array();


    /**
     *      Constructor
     *
     *      @param		DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        $this->author = new stdClass();
        $this->usermod = new stdClass();
        $this->usertodo = new stdClass();
        $this->userdone = new stdClass();
        $this->societe = new stdClass();
        $this->contact = new stdClass();
    }

    /**
     *    Add an action/event into database
     *
     *    @param	User	$user      		Object user making action
     *    @param    int		$notrigger		1 = disable triggers, 0 = enable triggers
     *    @return   int 		        	Id of created event, < 0 if KO
     */
    function add($user,$notrigger=0)
    {
        global $langs,$conf,$hookmanager;

        $error=0;
        $now=dol_now();

        // Clean parameters
        $this->label=dol_trunc(trim($this->label),128);
        $this->location=dol_trunc(trim($this->location),128);
        $this->note=dol_htmlcleanlastbr(trim($this->note));
        if (empty($this->percentage))   $this->percentage = 0;
        if (empty($this->priority))     $this->priority = 0;
        if (empty($this->fulldayevent)) $this->fulldayevent = 0;
        if (empty($this->punctual))     $this->punctual = 0;
        if (empty($this->transparency)) $this->transparency = 0;
        if ($this->percentage > 100) $this->percentage = 100;
        if ($this->percentage == 100 && ! $this->dateend) $this->dateend = $this->date;
        if (! empty($this->datep) && ! empty($this->datef))   $this->durationp=($this->datef - $this->datep);
        if (! empty($this->date)  && ! empty($this->dateend)) $this->durationa=($this->dateend - $this->date);
        if (! empty($this->datep) && ! empty($this->datef) && $this->datep > $this->datef) $this->datef=$this->datep;
        if (! empty($this->date)  && ! empty($this->dateend) && $this->date > $this->dateend) $this->dateend=$this->date;
        if (! isset($this->fk_project) || $this->fk_project < 0) $this->fk_project = 0;
        if ($this->elementtype=='facture')  $this->elementtype='invoice';
        if ($this->elementtype=='commande') $this->elementtype='order';
        if ($this->elementtype=='contrat')  $this->elementtype='contract';

        if (! $this->type_id && $this->type_code)
        {
            // Get id from code
            $cactioncomm=new CActionComm($this->db);
            $result=$cactioncomm->fetch($this->type_code);

            if ($result > 0)
            {
                $this->type_id=$cactioncomm->id;
            }
            else if ($result == 0)
            {
                $this->error='Failed to get record with code '.$this->type_code.' from dictionnary "type of events"';
                return -1;
            }
            else
            {
                $this->error=$cactioncomm->error;
                return -1;
            }
        }

        // Check parameters
        if (! $this->type_id)
        {
            $this->error="ErrorWrongParameters";
            return -1;
        }

        $this->db->begin();

        $sql = "INSERT INTO ".MAIN_DB_PREFIX."actioncomm";
        $sql.= "(datec,";
        $sql.= "datep,";
        $sql.= "datep2,";
        $sql.= "durationp,";
        $sql.= "fk_action,";
        $sql.= "code,";
        $sql.= "fk_soc,";
        $sql.= "fk_project,";
        $sql.= "note,";
        $sql.= "fk_contact,";
        $sql.= "fk_user_author,";
        $sql.= "fk_user_action,";
        $sql.= "fk_user_done,";
        $sql.= "label,percent,priority,fulldayevent,location,punctual,";
        $sql.= "transparency,";
        $sql.= "fk_element,";
        $sql.= "elementtype,";
        $sql.= "entity";
        $sql.= ") VALUES (";
        $sql.= "'".$this->db->idate($now)."',";
        $sql.= (strval($this->datep)!=''?"'".$this->db->idate($this->datep)."'":"null").",";
        $sql.= (strval($this->datef)!=''?"'".$this->db->idate($this->datef)."'":"null").",";
        $sql.= (isset($this->durationp) && $this->durationp >= 0 && $this->durationp != ''?"'".$this->durationp."'":"null").",";
        $sql.= (isset($this->type_id)?$this->type_id:"null").",";
        $sql.= (isset($this->code)?" '".$this->code."'":"null").",";
        $sql.= (isset($this->societe->id) && $this->societe->id > 0?" '".$this->societe->id."'":"null").",";
        $sql.= (isset($this->fk_project) && $this->fk_project > 0?" '".$this->fk_project."'":"null").",";
        $sql.= " '".$this->db->escape($this->note)."',";
        $sql.= (isset($this->contact->id) && $this->contact->id > 0?"'".$this->contact->id."'":"null").",";
        $sql.= (isset($user->id) && $user->id > 0 ? "'".$user->id."'":"null").",";
        $sql.= (isset($this->usertodo->id) && $this->usertodo->id > 0?"'".$this->usertodo->id."'":"null").",";
        $sql.= (isset($this->userdone->id) && $this->userdone->id > 0?"'".$this->userdone->id."'":"null").",";
        $sql.= "'".$this->db->escape($this->label)."','".$this->percentage."','".$this->priority."','".$this->fulldayevent."','".$this->db->escape($this->location)."','".$this->punctual."',";
        $sql.= "'".$this->transparency."',";
        $sql.= (! empty($this->fk_element)?$this->fk_element:"null").",";
        $sql.= (! empty($this->elementtype)?"'".$this->elementtype."'":"null").",";
        $sql.= $conf->entity;
        $sql.= ")";

        dol_syslog(get_class($this)."::add sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."actioncomm","id");

            // Actions on extra fields (by external module or standard code)
            $hookmanager->initHooks(array('actioncommdao'));
            $parameters=array('actcomm'=>$this->id);
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

            if (! $error && ! $notrigger)
            {
                // Appel des triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('ACTION_CREATE',$this,$user,$langs,$conf);
                if ($result < 0) {
                    $error++; $this->errors=$interface->errors;
                }
                // Fin appel triggers
            }

            if (! $error)
            {
            	$this->db->commit();
            	return $this->id;
            }
            else
           {
	           	$this->db->rollback();
	           	return -1;
            }
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::add ".$this->error,LOG_ERR);
            return -1;
        }

    }

    /**
     *    Load object from database
     *
     *    @param	int		$id     Id of action to get
     *    @return	int				<0 if KO, >0 if OK
     */
    function fetch($id)
    {
        global $langs;

        $sql = "SELECT a.id,";
        $sql.= " a.id as ref,";
        $sql.= " a.ref_ext,";
        $sql.= " a.datep,";
        $sql.= " a.datep2,";
        $sql.= " a.datec,";
        $sql.= " a.durationp,";
        $sql.= " a.tms as datem,";
        $sql.= " a.code, a.label, a.note,";
        $sql.= " a.fk_soc,";
        $sql.= " a.fk_project,";
        $sql.= " a.fk_user_author, a.fk_user_mod,";
        $sql.= " a.fk_user_action, a.fk_user_done,";
        $sql.= " a.fk_contact, a.percent as percentage,";
        $sql.= " a.fk_element, a.elementtype,";
        $sql.= " a.priority, a.fulldayevent, a.location, a.transparency,";
        $sql.= " c.id as type_id, c.code as type_code, c.libelle,";
        $sql.= " s.nom as socname,";
        $sql.= " u.firstname, u.lastname as lastname";
        $sql.= " FROM (".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."actioncomm as a)";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_author";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = a.fk_soc";
        $sql.= " WHERE a.id=".$id." AND a.fk_action=c.id";

        dol_syslog(get_class($this)."::fetch sql=".$sql);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id        = $obj->id;
                $this->ref       = $obj->ref;
                $this->ref_ext   = $obj->ref_ext;

                // Properties of parent table llx_c_actioncomm (will be deprecated in future)
                $this->type_id   = $obj->type_id;
                $this->type_code = $obj->type_code;
                $transcode=$langs->trans("Action".$obj->type_code);
                $type_libelle=($transcode!="Action".$obj->type_code?$transcode:$obj->libelle);
                $this->type      = $type_libelle;

				$this->code					= $obj->code;
                $this->label				= $obj->label;
                $this->datep				= $this->db->jdate($obj->datep);
                $this->datef				= $this->db->jdate($obj->datep2);

                $this->datec   				= $this->db->jdate($obj->datec);
                $this->datem   				= $this->db->jdate($obj->datem);

                $this->note					= $obj->note;
                $this->percentage			= $obj->percentage;

                $this->author->id			= $obj->fk_user_author;
                $this->author->firstname	= $obj->firstname;
                $this->author->lastname		= $obj->lastname;
                $this->usermod->id			= $obj->fk_user_mod;

                $this->usertodo->id			= $obj->fk_user_action;
                $this->userdone->id			= $obj->fk_user_done;
                $this->priority				= $obj->priority;
                $this->fulldayevent			= $obj->fulldayevent;
                $this->location				= $obj->location;
                $this->transparency			= $obj->transparency;

                $this->socid				= $obj->fk_soc;	// To have fetch_thirdparty method working
                $this->societe->id			= $obj->fk_soc;
                $this->contact->id			= $obj->fk_contact;
                $this->fk_project			= $obj->fk_project;

                $this->fk_element			= $obj->fk_element;
                $this->elementtype			= $obj->elementtype;
            }
            $this->db->free($resql);
            return 1;
        }
        else
        {
            $this->error=$this->db->lasterror();
            return -1;
        }
    }

    /**
     *    Delete event from database
     *
     *    @param    int		$notrigger		1 = disable triggers, 0 = enable triggers
     *    @return   int 					<0 if KO, >0 if OK
     */
    function delete($notrigger=0)
    {
        global $user,$langs,$conf;

        $error=0;

        $this->db->begin();

        $sql = "DELETE FROM ".MAIN_DB_PREFIX."actioncomm";
        $sql.= " WHERE id=".$this->id;

        dol_syslog(get_class($this)."::delete sql=".$sql, LOG_DEBUG);
        $res=$this->db->query($sql);
        if ($res < 0) {
        	$this->error=$this->db->lasterror();
        	$error++;
        }

        // Removed extrafields
        if (! $error) {
        	$result=$this->deleteExtraFields();
          	if ($result < 0)
           	{
           		$error++;
           		dol_syslog(get_class($this)."::delete error -3 ".$this->error, LOG_ERR);
           	}
        }

        if (!$error)
        {
            if (! $notrigger)
            {
                // Appel des triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('ACTION_DELETE',$this,$user,$langs,$conf);
                if ($result < 0) {
                    $error++; $this->errors=$interface->errors;
                }
                // Fin appel triggers
            }

            if (! $error)
            {
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->db->rollback();
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::delete ".$this->error,LOG_ERR);
            return -1;
        }
    }

    /**
     *    Update action into database
     *	  If percentage = 100, on met a jour date 100%
     *
     *    @param    User	$user			Object user making change
     *    @param    int		$notrigger		1 = disable triggers, 0 = enable triggers
     *    @return   int     				<0 if KO, >0 if OK
     */
    function update($user,$notrigger=0)
    {
        global $langs,$conf,$hookmanager;

        $error=0;

        // Clean parameters
        $this->label=trim($this->label);
        $this->note=trim($this->note);
        if (empty($this->percentage))    $this->percentage = 0;
        if (empty($this->priority))      $this->priority = 0;
        if (empty($this->transparency))  $this->transparency = 0;
        if (empty($this->fulldayevent))  $this->fulldayevent = 0;
        if ($this->percentage > 100) $this->percentage = 100;
        if ($this->percentage == 100 && ! $this->dateend) $this->dateend = $this->date;
        if ($this->datep && $this->datef)   $this->durationp=($this->datef - $this->datep);
        if ($this->date  && $this->dateend) $this->durationa=($this->dateend - $this->date);
        if ($this->datep && $this->datef && $this->datep > $this->datef) $this->datef=$this->datep;
        if ($this->date  && $this->dateend && $this->date > $this->dateend) $this->dateend=$this->date;
        if ($this->fk_project < 0) $this->fk_project = 0;

        // Check parameters
        if ($this->percentage == 0 && $this->userdone->id > 0)
        {
            $this->error="ErrorCantSaveADoneUserWithZeroPercentage";
            return -1;
        }

        $this->db->begin();

        //print 'eeea'.$this->datep.'-'.(strval($this->datep) != '').'-'.$this->db->idate($this->datep);
        $sql = "UPDATE ".MAIN_DB_PREFIX."actioncomm ";
        $sql.= " SET percent = '".$this->percentage."'";
        if ($this->fk_action > 0) $sql.= ", fk_action = '".$this->fk_action."'";
        $sql.= ", label = ".($this->label ? "'".$this->db->escape($this->label)."'":"null");
        $sql.= ", datep = ".(strval($this->datep)!='' ? "'".$this->db->idate($this->datep)."'" : 'null');
        $sql.= ", datep2 = ".(strval($this->datef)!='' ? "'".$this->db->idate($this->datef)."'" : 'null');
        //$sql.= ", datea = ".(strval($this->date)!='' ? "'".$this->db->idate($this->date)."'" : 'null');
        //$sql.= ", datea2 = ".(strval($this->dateend)!='' ? "'".$this->db->idate($this->dateend)."'" : 'null');
        $sql.= ", note = ".($this->note ? "'".$this->db->escape($this->note)."'":"null");
        $sql.= ", fk_soc =". ($this->societe->id > 0 ? "'".$this->societe->id."'":"null");
        $sql.= ", fk_project =". ($this->fk_project > 0 ? "'".$this->fk_project."'":"null");
        $sql.= ", fk_contact =". ($this->contact->id > 0 ? "'".$this->contact->id."'":"null");
        $sql.= ", priority = '".$this->priority."'";
        $sql.= ", fulldayevent = '".$this->fulldayevent."'";
        $sql.= ", location = ".($this->location ? "'".$this->db->escape($this->location)."'":"null");
        $sql.= ", transparency = '".$this->transparency."'";
        $sql.= ", fk_user_mod = '".$user->id."'";
        $sql.= ", fk_user_action=".($this->usertodo->id > 0 ? "'".$this->usertodo->id."'":"null");
        $sql.= ", fk_user_done=".($this->userdone->id > 0 ? "'".$this->userdone->id."'":"null");
        $sql.= " WHERE id=".$this->id;

        dol_syslog(get_class($this)."::update sql=".$sql);
        if ($this->db->query($sql))
        {

        	// Actions on extra fields (by external module or standard code)
        	$hookmanager->initHooks(array('actioncommdao'));
        	$parameters=array('actcomm'=>$this->id);
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

            if (! $notrigger)
            {
                // Appel des triggers
                include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
                $interface=new Interfaces($this->db);
                $result=$interface->run_triggers('ACTION_MODIFY',$this,$user,$langs,$conf);
                if ($result < 0) {
                    $error++; $this->errors=$interface->errors;
                }
                // Fin appel triggers
            }

            if (! $error)
            {
                $this->db->commit();
                return 1;
            }
            else
            {
                $this->db->rollback();
                dol_syslog(get_class($this)."::update ".join(',',$this->errors),LOG_ERR);
                return -2;
            }
        }
        else
        {
            $this->db->rollback();
            $this->error=$this->db->lasterror();
            dol_syslog(get_class($this)."::update ".$this->error,LOG_ERR);
            return -1;
        }
    }

    /**
     *   Load all objects with filters
     *
     *   @param		DoliDb	$db				Database handler
     *   @param		int		$socid			Filter by thirdparty
     * 	 @param		int		$fk_element		Id of element action is linked to
     *   @param		string	$elementtype	Type of element action is linked to
     *   @param		string	$filter			Other filter
     *   @return	array or string			Error string if KO, array with actions if OK
     */
    static function getActions($db, $socid=0, $fk_element=0, $elementtype='', $filter='')
    {
        global $conf, $langs;

        $resarray=array();

        $sql = "SELECT a.id";
        $sql.= " FROM ".MAIN_DB_PREFIX."actioncomm as a";
        $sql.= " WHERE a.entity = ".$conf->entity;
        if (! empty($socid)) $sql.= " AND a.fk_soc = ".$socid;
        if (! empty($elementtype))
        {
            if ($elementtype == 'project') $sql.= ' AND a.fk_project = '.$fk_element;
            else $sql.= " AND a.fk_element = ".$fk_element." AND a.elementtype = '".$elementtype."'";
        }
        if (! empty($filter)) $sql.= $filter;

        dol_syslog(get_class()."::getActions sql=".$sql);
        $resql=$db->query($sql);
        if ($resql)
        {
            $num = $db->num_rows($resql);

            if ($num)
            {
                for($i=0;$i<$num;$i++)
                {
                    $obj = $db->fetch_object($resql);
                    $actioncommstatic = new ActionComm($db);
                    $actioncommstatic->fetch($obj->id);
                    $resarray[$i] = $actioncommstatic;
                }
            }
            $db->free($resql);
            return $resarray;
        }
        else
       {
            return $db->lasterror();
        }
    }

    /**
     *      Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     *      @param	User	$user   Objet user
     *      @return int     		<0 if KO, >0 if OK
     */
    function load_board($user)
    {
        global $conf, $user;

        $now=dol_now();

        $this->nbtodo=$this->nbtodolate=0;

        $sql = "SELECT a.id, a.datep as dp";
        $sql.= " FROM (".MAIN_DB_PREFIX."actioncomm as a";
        $sql.= ")";
        if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON a.fk_soc = sc.fk_soc";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s ON a.fk_soc = s.rowid";
        $sql.= " WHERE a.percent >= 0 AND a.percent < 100";
        $sql.= " AND a.entity = ".$conf->entity;
        if (! $user->rights->societe->client->voir && ! $user->societe_id) $sql.= " AND (a.fk_soc IS NULL OR sc.fk_user = " .$user->id . ")";
        if ($user->societe_id) $sql.=" AND a.fk_soc = ".$user->societe_id;
        if (! $user->rights->agenda->allactions->read) $sql.= " AND (a.fk_user_author = ".$user->id . " OR a.fk_user_action = ".$user->id . " OR a.fk_user_done = ".$user->id . ")";

        $resql=$this->db->query($sql);
        if ($resql)
        {
            while ($obj=$this->db->fetch_object($resql))
            {
                $this->nbtodo++;
                if (isset($obj->dp) && $this->db->jdate($obj->dp) < ($now - $conf->actions->warning_delay)) $this->nbtodolate++;
            }
            return 1;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


    /**
     *      Charge les informations d'ordre info dans l'objet facture
     *
     *      @param	int		$id       	Id de la facture a charger
     *		@return	void
     */
    function info($id)
    {
        $sql = 'SELECT ';
        $sql.= ' a.id,';
        $sql.= ' datec,';
        $sql.= ' tms as datem,';
        $sql.= ' fk_user_author,';
        $sql.= ' fk_user_mod';
        $sql.= ' FROM '.MAIN_DB_PREFIX.'actioncomm as a';
        $sql.= ' WHERE a.id = '.$id;

        dol_syslog(get_class($this)."::info sql=".$sql);
        $result=$this->db->query($sql);
        if ($result)
        {
            if ($this->db->num_rows($result))
            {
                $obj = $this->db->fetch_object($result);
                $this->id = $obj->id;
                if ($obj->fk_user_author)
                {
                    $cuser = new User($this->db);
                    $cuser->fetch($obj->fk_user_author);
                    $this->user_creation     = $cuser;
                }
                if ($obj->fk_user_mod)
                {
                    $muser = new User($this->db);
                    $muser->fetch($obj->fk_user_mod);
                    $this->user_modification = $muser;
                }

                $this->date_creation     = $this->db->jdate($obj->datec);
                $this->date_modification = $this->db->jdate($obj->datem);
            }
            $this->db->free($result);
        }
        else
        {
            dol_print_error($this->db);
        }
    }


    /**
     *    	Return label of status
     *
     *    	@param	int		$mode           0=libelle long, 1=libelle court, 2=Picto + Libelle court, 3=Picto, 4=Picto + Libelle long, 5=Libelle court + Picto
     *      @param  int		$hidenastatus   1=Show nothing if status is "Not applicable"
     *    	@return string          		String with status
     */
    function getLibStatut($mode,$hidenastatus=0)
    {
        return $this->LibStatut($this->percentage,$mode,$hidenastatus);
    }

    /**
     *		Return label of action status
     *
     *    	@param	int		$percent        Percent
     *    	@param  int		$mode           0=Long label, 1=Short label, 2=Picto+Short label, 3=Picto, 4=Picto+Short label, 5=Short label+Picto, 6=Very short label+Picto
     *      @param  int		$hidenastatus   1=Show nothing if status is "Not applicable"
     *    	@return string		    		Label
     */
    function LibStatut($percent,$mode,$hidenastatus=0)
    {
        global $langs;

        if ($mode == 0)
        {
        	if ($percent==-1 && ! $hidenastatus) return $langs->trans('StatusNotApplicable');
        	else if ($percent==0) return $langs->trans('StatusActionToDo').' (0%)';
        	else if ($percent > 0 && $percent < 100) return $langs->trans('StatusActionInProcess').' ('.$percent.'%)';
        	else if ($percent >= 100) return $langs->trans('StatusActionDone').' (100%)';
        }
        else if ($mode == 1)
        {
        	if ($percent==-1 && ! $hidenastatus) return $langs->trans('StatusNotApplicable');
        	else if ($percent==0) return $langs->trans('StatusActionToDo');
        	else if ($percent > 0 && $percent < 100) return $percent.'%';
        	else if ($percent >= 100) return $langs->trans('StatusActionDone');
        }
        else if ($mode == 2)
        {
        	if ($percent==-1 && ! $hidenastatus) return img_picto($langs->trans('StatusNotApplicable'),'statut9').' '.$langs->trans('StatusNotApplicable');
        	else if ($percent==0) return img_picto($langs->trans('StatusActionToDo'),'statut1').' '.$langs->trans('StatusActionToDo');
        	else if ($percent > 0 && $percent < 100) return img_picto($langs->trans('StatusActionInProcess'),'statut3').' '. $percent.'%';
        	else if ($percent >= 100) return img_picto($langs->trans('StatusActionDone'),'statut6').' '.$langs->trans('StatusActionDone');
        }
        else if ($mode == 3)
        {
        	if ($percent==-1 && ! $hidenastatus) return img_picto($langs->trans("Status").': '.$langs->trans('StatusNotApplicable'),'statut9');
        	else if ($percent==0) return img_picto($langs->trans("Status").': '.$langs->trans('StatusActionToDo').' (0%)','statut1');
        	else if ($percent > 0 && $percent < 100) return img_picto($langs->trans("Status").': '.$langs->trans('StatusActionInProcess').' ('.$percent.'%)','statut3');
        	else if ($percent >= 100) return img_picto($langs->trans("Status").': '.$langs->trans('StatusActionDone').' (100%)','statut6');
        }
        else if ($mode == 4)
        {
        	if ($percent==-1 && ! $hidenastatus) return img_picto($langs->trans('StatusNotApplicable'),'statut9').' '.$langs->trans('StatusNotApplicable');
        	else if ($percent==0) return img_picto($langs->trans('StatusActionToDo'),'statut1').' '.$langs->trans('StatusActionToDo').' (0%)';
        	else if ($percent > 0 && $percent < 100) return img_picto($langs->trans('StatusActionInProcess'),'statut3').' '.$langs->trans('StatusActionInProcess').' ('.$percent.'%)';
        	else if ($percent >= 100) return img_picto($langs->trans('StatusActionDone'),'statut6').' '.$langs->trans('StatusActionDone').' (100%)';
        }
        else if ($mode == 5)
        {
        	if ($percent==-1 && ! $hidenastatus) return img_picto($langs->trans('StatusNotApplicable'),'statut9');
        	else if ($percent==0) return '0% '.img_picto($langs->trans('StatusActionToDo'),'statut1');
        	else if ($percent > 0 && $percent < 100) return $percent.'% '.img_picto($langs->trans('StatusActionInProcess').' - '.$percent.'%','statut3');
        	else if ($percent >= 100) return $langs->trans('StatusActionDone').' '.img_picto($langs->trans('StatusActionDone'),'statut6');
        }
        else if ($mode == 6)
        {
        	if ($percent==-1 && ! $hidenastatus) return img_picto($langs->trans('StatusNotApplicable'),'statut9');
        	else if ($percent==0) return '0% '.img_picto($langs->trans('StatusActionToDo'),'statut1');
        	else if ($percent > 0 && $percent < 100) return $percent.'% '.img_picto($langs->trans('StatusActionInProcess').' - '.$percent.'%','statut3');
        	else if ($percent >= 100) return img_picto($langs->trans('StatusActionDone'),'statut6');
        }
        return '';
    }

    /**
     *    	Renvoie nom clicable (avec eventuellement le picto)
     *      Utilise $this->id, $this->code et $this->label
     *
     * 		@param	int		$withpicto			0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
     *		@param	int		$maxlength			Nombre de caracteres max dans libelle
     *		@param	string	$classname			Force style class on a link
     * 		@param	string	$option				''=Link to action,'birthday'=Link to contact
     * 		@param	int		$overwritepicto		1=Overwrite picto
     *		@return	string						Chaine avec URL
     */
    function getNomUrl($withpicto=0,$maxlength=0,$classname='',$option='',$overwritepicto='')
    {
        global $langs;

        $result='';
        if ($option=='birthday') $lien = '<a '.($classname?'class="'.$classname.'" ':'').'href="'.DOL_URL_ROOT.'/contact/perso.php?id='.$this->id.'">';
        else $lien = '<a '.($classname?'class="'.$classname.'" ':'').'href="'.DOL_URL_ROOT.'/comm/action/fiche.php?id='.$this->id.'">';
        $lienfin='</a>';
        $label=$this->label;
        if (empty($label)) $label=$this->libelle;	// Fro backward compatibility
        //print 'rrr'.$this->libelle;

        if ($withpicto == 2)
        {
            $libelle=$label;
        	if (! empty($conf->global->AGENDA_USE_EVENT_TYPE)) $libelle=$langs->trans("Action".$this->type_code);
            $libelleshort='';
        }
        else if (empty($this->libelle))
        {
            $libelle=$label;
        	if (! empty($conf->global->AGENDA_USE_EVENT_TYPE)) $libelle=$langs->trans("Action".$this->type_code);
        	$libelleshort=dol_trunc($label, $maxlength);
        }
        else
       {
            $libelle=$label;
            $libelleshort=dol_trunc($label,$maxlength);
        }

        if ($withpicto)
        {
        	if (! empty($conf->global->AGENDA_USE_EVENT_TYPE))
        	{
        		 $libelle.=(($this->type_code && $libelle!=$langs->trans("Action".$this->type_code) && $langs->trans("Action".$this->type_code)!="Action".$this->type_code)?' ('.$langs->trans("Action".$this->type_code).')':'');
        	}
            $result.=$lien.img_object($langs->trans("ShowAction").': '.$libelle,($overwritepicto?$overwritepicto:'action')).$lienfin;
        }
        if ($withpicto==1) $result.=' ';
        $result.=$lien.$libelleshort.$lienfin;
        return $result;
    }


    /**
     *		Export events from database into a cal file.
     *
     *		@param	string		$format			'vcal', 'ical/ics', 'rss'
     *		@param	string		$type			'event' or 'journal'
     *		@param	int			$cachedelay		Do not rebuild file if date older than cachedelay seconds
     *		@param	string		$filename		Force filename
     *		@param	array		$filters		Array of filters
     *		@return int     					<0 if error, nb of events in new file if ok
     */
    function build_exportfile($format,$type,$cachedelay,$filename,$filters)
    {
        global $conf,$langs,$dolibarr_main_url_root,$mysoc;

        require_once (DOL_DOCUMENT_ROOT ."/core/lib/xcal.lib.php");
        require_once (DOL_DOCUMENT_ROOT ."/core/lib/date.lib.php");

        dol_syslog(get_class($this)."::build_exportfile Build export file format=".$format.", type=".$type.", cachedelay=".$cachedelay.", filename=".$filename.", filters size=".count($filters), LOG_DEBUG);

        // Check parameters
        if (empty($format)) return -1;

        // Clean parameters
        if (! $filename)
        {
            $extension='vcs';
            if ($format == 'ical') $extension='ics';
            $filename=$format.'.'.$extension;
        }

        // Create dir and define output file (definitive and temporary)
        $result=dol_mkdir($conf->agenda->dir_temp);
        $outputfile=$conf->agenda->dir_temp.'/'.$filename;

        $result=0;

        $buildfile=true;
        $login='';$logina='';$logind='';$logint='';

        $now = dol_now();

        if ($cachedelay)
        {
            $nowgmt = dol_now();
            include_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
            if (dol_filemtime($outputfile) > ($nowgmt - $cachedelay))
            {
                dol_syslog(get_class($this)."::build_exportfile file ".$outputfile." is not older than now - cachedelay (".$nowgmt." - ".$cachedelay."). Build is canceled");
                $buildfile = false;
            }
        }

        if ($buildfile)
        {
            // Build event array
            $eventarray=array();

            $sql = "SELECT a.id,";
            $sql.= " a.datep,";		// Start
            $sql.= " a.datep2,";	// End
            $sql.= " a.durationp,";
            $sql.= " a.datec, a.tms as datem,";
            $sql.= " a.label, a.code, a.note, a.fk_action as type_id,";
            $sql.= " a.fk_soc,";
            $sql.= " a.fk_user_author, a.fk_user_mod,";
            $sql.= " a.fk_user_action, a.fk_user_done,";
            $sql.= " a.fk_contact, a.percent as percentage,";
            $sql.= " a.fk_element, a.elementtype,";
            $sql.= " a.priority, a.fulldayevent, a.location,";
            $sql.= " u.firstname, u.lastname,";
            $sql.= " s.nom as socname,";
            $sql.= " c.id as type_id, c.code as type_code, c.libelle";
            $sql.= " FROM (".MAIN_DB_PREFIX."c_actioncomm as c, ".MAIN_DB_PREFIX."actioncomm as a)";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."user as u on u.rowid = a.fk_user_author";
            $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on s.rowid = a.fk_soc";
            $sql.= " WHERE a.fk_action=c.id";
            $sql.= " AND a.entity = ".$conf->entity;
            foreach ($filters as $key => $value)
            {
                if ($key == 'notolderthan') $sql.=" AND a.datep >= '".$this->db->idate($now-($value*24*60*60))."'";
                if ($key == 'year')         $sql.=" AND a.datep BETWEEN '".$this->db->idate(dol_get_first_day($value,1))."' AND '".$this->db->idate(dol_get_last_day($value,12))."'";
                if ($key == 'id')           $sql.=" AND a.id=".(is_numeric($value)?$value:0);
                if ($key == 'idfrom')       $sql.=" AND a.id >= ".(is_numeric($value)?$value:0);
                if ($key == 'idto')         $sql.=" AND a.id <= ".(is_numeric($value)?$value:0);
                if ($key == 'login')
                {
                    $login=$value;
                    $userforfilter=new User($this->db);
                    $result=$userforfilter->fetch('',$value);
                    $sql.= " AND (";
                    $sql.= " a.fk_user_author = ".$userforfilter->id;
                    $sql.= " OR a.fk_user_action = ".$userforfilter->id;
                    $sql.= " OR a.fk_user_done = ".$userforfilter->id;
                    $sql.= ")";
                }
                if ($key == 'logina')
                {
                    $logina=$value;
                    $userforfilter=new User($this->db);
                    $result=$userforfilter->fetch('',$value);
                    $sql.= " AND a.fk_user_author = ".$userforfilter->id;
                }
                if ($key == 'logint')
                {
                    $logint=$value;
                    $userforfilter=new User($this->db);
                    $result=$userforfilter->fetch('',$value);
                    $sql.= " AND a.fk_user_action = ".$userforfilter->id;
                }
                if ($key == 'logind')
                {
                    $logind=$value;
                    $userforfilter=new User($this->db);
                    $result=$userforfilter->fetch('',$value);
                    $sql.= " AND a.fk_user_done = ".$userforfilter->id;
                }
            }
            $sql.= " AND a.datep IS NOT NULL";		// To exclude corrupted events and avoid errors in lightning/sunbird import
            $sql.= " ORDER by datep";
            //print $sql;exit;

            dol_syslog(get_class($this)."::build_exportfile select events sql=".$sql);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                // Note: Output of sql request is encoded in $conf->file->character_set_client
                while ($obj=$this->db->fetch_object($resql))
                {
                    $qualified=true;

                    // 'eid','startdate','duration','enddate','title','summary','category','email','url','desc','author'
                    $event=array();
                    $event['uid']='dolibarragenda-'.$this->db->database_name.'-'.$obj->id."@".$_SERVER["SERVER_NAME"];
                    $event['type']=$type;
                    $datestart=$this->db->jdate($obj->datep);
                    $dateend=$this->db->jdate($obj->datep2);
                    $duration=$obj->durationp;
                    $event['summary']=$obj->label.($obj->socname?" (".$obj->socname.")":"");
                    $event['desc']=$obj->note;
                    $event['startdate']=$datestart;
                    $event['duration']=$duration;	// Not required with type 'journal'
                    $event['enddate']=$dateend;		// Not required with type 'journal'
                    $event['author']=dolGetFirstLastname($obj->firstname, $obj->lastname);
                    $event['priority']=$obj->priority;
                    $event['fulldayevent']=$obj->fulldayevent;
                    $event['location']=$obj->location;
                    $event['transparency']=(($obj->transparency > 0)?'OPAQUE':'TRANSPARENT');		// OPAQUE (busy) or TRANSPARENT (not busy)
                    $event['category']=$obj->libelle;	// libelle type action
					// Define $urlwithroot
					$urlwithouturlroot=preg_replace('/'.preg_quote(DOL_URL_ROOT,'/').'$/i','',trim($dolibarr_main_url_root));
					$urlwithroot=$urlwithouturlroot.DOL_URL_ROOT;			// This is to use external domain name found into config file
					//$urlwithroot=DOL_MAIN_URL_ROOT;						// This is to use same domain name than current
                    $url=$urlwithroot.'/comm/action/fiche.php?id='.$obj->id;
                    $event['url']=$url;
                    $event['created']=$this->db->jdate($obj->datec);
                    $event['modified']=$this->db->jdate($obj->datem);

                    if ($qualified && $datestart)
                    {
                        $eventarray[$datestart]=$event;
                    }
                }
            }
            else
            {
                $this->error=$this->db->lasterror();
                dol_syslog(get_class($this)."::build_exportfile ".$this->db->lasterror(), LOG_ERR);
                return -1;
            }

            $langs->load("agenda");

            // Define title and desc
            $more='';
            if ($login)  $more=$langs->transnoentities("User").' '.$login;
            if ($logina) $more=$langs->transnoentities("ActionsAskedBy").' '.$logina;
            if ($logint) $more=$langs->transnoentities("ActionsToDoBy").' '.$logint;
            if ($logind) $more=$langs->transnoentities("ActionsDoneBy").' '.$logind;
            if ($more)
            {
                $title='Dolibarr actions '.$mysoc->name.' - '.$more;
                $desc=$more;
                $desc.=' ('.$mysoc->name.' - built by Dolibarr)';
            }
            else
            {
                $title='Dolibarr actions '.$mysoc->name;
                $desc=$langs->transnoentities('ListOfActions');
                $desc.=' ('.$mysoc->name.' - built by Dolibarr)';
            }

            // Create temp file
            $outputfiletmp=tempnam($conf->agenda->dir_temp,'tmp');  // Temporary file (allow call of function by different threads
            @chmod($outputfiletmp, octdec($conf->global->MAIN_UMASK));

            // Write file
            if ($format == 'vcal') $result=build_calfile($format,$title,$desc,$eventarray,$outputfiletmp);
            if ($format == 'ical') $result=build_calfile($format,$title,$desc,$eventarray,$outputfiletmp);
            if ($format == 'rss')  $result=build_rssfile($format,$title,$desc,$eventarray,$outputfiletmp);

            if ($result >= 0)
            {
                if (rename($outputfiletmp,$outputfile)) $result=1;
                else
                {
                    dol_syslog(get_class($this)."::build_exportfile failed to rename ".$outputfiletmp." to ".$outputfile, LOG_ERR);
                    dol_delete_file($outputfiletmp,0,1);
                    $result=-1;
                }
            }
            else
            {
                dol_syslog(get_class($this)."::build_exportfile build_xxxfile function fails to for format=".$format." outputfiletmp=".$outputfile, LOG_ERR);
                dol_delete_file($outputfiletmp,0,1);
                $langs->load("errors");
                $this->error=$langs->trans("ErrorFailToCreateFile",$outputfile);
            }
        }

        return $result;
    }

    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     *	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $user,$langs,$conf;

        $now=dol_now();

        // Initialise parametres
        $this->id=0;
        $this->specimen=1;

        $this->type_code='AC_OTH';
        $this->code='AC_SPECIMEN_CODE';
        $this->label='Label of event Specimen';
        $this->datec=$now;
        $this->datem=$now;
        $this->datep=$now;
        $this->datef=$now;
        $this->author=$user;
        $this->usermod=$user;
        $this->fulldayevent=0;
        $this->punctual=0;
        $this->percentage=0;
        $this->location='Location';
        $this->transparency=0;
        $this->priority='Priority X';
        $this->note = 'Note';
    }

}

?>
