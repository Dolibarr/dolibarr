<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *      \file       htdocs/business/class/phase.class.php
 *      \ingroup    business
 *      \brief      This file is a class to manage phases of business
 *		\version    $Id$
 */


/**
 *      \class      Phase
 *      \brief      Class to manage phases of business
 */
class Phase extends CommonObject
{
	var $db;								//!< To store db handler
	var $error;								//!< To return error code (or message)
	var $errors=array();					//!< To return several error codes (or messages)
	var $element='business_phase';			//!< Id that identify managed objects
	var $table_element='business_phase';	//!< Name of table without prefix where object is stored

    var $id;

	var $fk_business;
	var $fk_milestone;
	var $label;
	var $description;
	var $date_c;
	var $date_start;
	var $date_end;
	var $progress;
	var $priority;
	var $fk_user_creat;
	var $fk_user_valid;
	var $statut;
	var $note_private;
	var $note_public;
	
	var $total_ht;					// Total net of tax
	var $total_tva;					// Total VAT
	var $total_ttc;					// Total with tax
	var $tva_tx;

    /**
     *      \brief      Constructor
     *      \param      DB      Database handler
     */
    function Phase($DB)
    {
        $this->db = $DB;
        
        $this->statuts_short=array(0=>'Draft',1=>'Validated',2=>'ActionRunningShort',5=>'ToBill');
		$this->statuts=array(0=>'Draft',1=>'Validated',2=>'ActionRunningShort',5=>'ToBill');
    }


    /**
     *      \brief      Create in database
     *      \param      user        	User that create
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, Id of created object if OK
     */
    function create($user, $notrigger=0)
    {
    	global $conf, $langs;
    	
    	include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');

		$error=0;

		// Clean parameters
		$this->label = trim($this->label);
		$this->description = trim($this->description);
		
		$total_ht	= price2num($this->total_ht);
		$tva_tx 	= price2num($this->tva_tx);
		
		$tabprice=calcul_price_total(1, $total_ht, 0, $tva_tx);
		$total_ht  = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];

		// Check parameters
		// Put here code to add control on parameters values

        // Insert request
		$sql = "INSERT INTO ".MAIN_DB_PREFIX."business_phase (";
		$sql.= "fk_business";
		//$sql.= ", fk_milestone";
		$sql.= ", label";
		$sql.= ", description";
		$sql.= ", datec";
		$sql.= ", fk_user_creat";
		$sql.= ", dateo";
		$sql.= ", datee";
		$sql.= ", total_ht";
		$sql.= ", total_tva";
		$sql.= ", total_ttc";
		$sql.= ", tva_tx";
		$sql.= ", progress";
        $sql.= ") VALUES (";
		$sql.= $this->fk_business;
		//$sql.= ", ".$this->fk_milestone;
		$sql.= ", '".addslashes($this->label)."'";
		$sql.= ", '".addslashes($this->description)."'";
		$sql.= ", ".$this->db->idate($this->date_c);
		$sql.= ", ".$user->id;
		$sql.= ", ".($this->date_start!=''?$this->db->idate($this->date_start):'null');
		$sql.= ", ".($this->date_end!=''?$this->db->idate($this->date_end):'null');
		$sql.= ", '".$total_ht."'";
		$sql.= ", '".$total_tva."'";
		$sql.= ", '".$total_ttc."'";
		$sql.= ", '".$tva_tx."'";
		$sql.= ", ".($this->progress!=''?$this->progress:0);
		$sql.= ")";

		$this->db->begin();

	   	dol_syslog(get_class($this)."::create sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX."business_phase");

			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('PHASE_CREATE',$this,$user,$langs,$conf);
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
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetch($id)
    {
    	global $langs;

        $sql = "SELECT ";
		$sql.= "p.rowid";
		$sql.= ", p.fk_business";
		$sql.= ", p.label";
		$sql.= ", p.description";
		//$sql.= ", p.duration_effective";
		$sql.= ", p.dateo";
		$sql.= ", p.datee";
		$sql.= ", p.fk_user_creat";
		$sql.= ", p.fk_user_valid";
		$sql.= ", p.fk_statut";
		$sql.= ", p.progress";
		$sql.= ", p.priority";
		$sql.= ", p.note_private";
		$sql.= ", p.note_public";
		$sql.= ", p.total_ht";
		$sql.= ", p.total_tva";
		$sql.= ", p.total_ttc";
		$sql.= ", p.tva_tx";
        $sql.= " FROM ".MAIN_DB_PREFIX."business_phase as p";
        $sql.= " WHERE p.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetch sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->id					= $obj->rowid;
                $this->ref					= $obj->rowid;
				$this->fk_business			= $obj->fk_business;
				//$this->fk_milestone			= $obj->fk_milestone;
				$this->label				= $obj->label;
				$this->description			= $obj->description;
				//$this->duration_effective	= $obj->duration_effective;
				$this->date_c				= $this->db->jdate($obj->datec);
				$this->date_start			= $this->db->jdate($obj->dateo);
				$this->date_end				= $this->db->jdate($obj->datee);
				$this->fk_user_creat		= $obj->fk_user_creat;
				$this->fk_user_valid		= $obj->fk_user_valid;
				$this->statut				= $obj->fk_statut;
				$this->progress				= $obj->progress;
				$this->priority				= $obj->priority;
				$this->note_private			= $obj->note_private;
				$this->note_public			= $obj->note_public;
				$this->total_ht				= $obj->total_ht;
				$this->total_tva			= $obj->total_tva;
				$this->total_ttc			= $obj->total_ttc;
				$this->tva_tx				= $obj->tva_tx;
            }

            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetch ".$this->error, LOG_ERR);
            return -1;
        }
    }


    /**
     *      \brief      Update database
     *      \param      user        	User that modify
     *      \param      notrigger	    0=launch triggers after, 1=disable triggers
     *      \return     int         	<0 if KO, >0 if OK
     */
    function update($user=0, $notrigger=0)
    {
    	global $conf, $langs;
    	
    	include_once(DOL_DOCUMENT_ROOT.'/lib/price.lib.php');
    	
		$error=0;

		// Clean parameters
		if (isset($this->label)) $this->label=trim($this->label);
		if (isset($this->description)) $this->description=trim($this->description);
		//if (isset($this->duration_effective)) $this->duration_effective=trim($this->duration_effective);

		// Check parameters
		$total_ht	= price2num($this->total_ht);
		$tva_tx 	= price2num($this->tva_tx);
		
		$tabprice=calcul_price_total(1, $total_ht, 0, $tva_tx);
		$total_ht  = $tabprice[0];
		$total_tva = $tabprice[1];
		$total_ttc = $tabprice[2];

        // Update request
        $sql = "UPDATE ".MAIN_DB_PREFIX."business_phase SET";
		$sql.= " label=".(isset($this->label)?"'".addslashes($this->label)."'":"null").",";
		$sql.= " description=".(isset($this->description)?"'".addslashes($this->description)."'":"null").",";
		//$sql.= " duration_effective=".(isset($this->duration_effective)?$this->duration_effective:"null").",";
		$sql.= " dateo=".($this->date_start!=''?$this->db->idate($this->date_start):'null').",";
		$sql.= " datee=".($this->date_end!=''?$this->db->idate($this->date_end):'null').",";
		$sql.= " total_ht='".$total_ht."',";
		$sql.= " total_tva='".$total_tva."',";
		$sql.= " total_ttc='".$total_ttc."',";
		$sql.= " tva_tx='".$tva_tx."',";
		$sql.= " progress=".$this->progress;
        $sql.= " WHERE rowid=".$this->id;

		$this->db->begin();

		dol_syslog(get_class($this)."::update sql=".$sql, LOG_DEBUG);
        $resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('PHASE_MODIFY',$this,$user,$langs,$conf);
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
	*   \brief      Delete object in database
    *	\param      user        	User that delete
    *   \param      notrigger	    0=launch triggers after, 1=disable triggers
	*	\return		int				<0 if KO, >0 if OK
	*/
	function delete($user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		$this->db->begin();

		if ($this->hasChildren() > 0)
		{
			dol_syslog(get_class($this)."::delete Can't delete record as it has some child", LOG_WARNING);
			$this->error='ErrorRecordHasChildren';
			$this->db->rollback();
			return 0;
		}

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task";
		$sql.= " WHERE rowid=".$this->id;

		dol_syslog(get_class($this)."::delete sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
		        // Call triggers
		        include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
		        $interface=new Interfaces($this->db);
		        $result=$interface->run_triggers('TASK_DELETE',$this,$user,$langs,$conf);
		        if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        // End call triggers
			}
		}

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
	 *		\brief		Return nb of children
	 *		\return 	<0 if KO, 0 if no children, >0 if OK
	 */
	function hasChildren()
	{
		$ret=0;

		$sql = "SELECT COUNT(*) as nb";
		$sql.= " FROM ".MAIN_DB_PREFIX."projet_task";
		$sql.= " WHERE fk_task_parent=".$this->id;

		dol_syslog(get_class($this)."::hasChildren sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }
		else
		{
			$obj=$this->db->fetch_object($resql);
			if ($obj) $ret=$obj->nb;
		}

		if (! $error)
		{
			return $ret;
		}
		else
		{
			return -1;
		}
	}


	/**
	 *	\brief      Renvoie nom clicable (avec eventuellement le picto)
	 *	\param		withpicto		0=Pas de picto, 1=Inclut le picto dans le lien, 2=Picto seul
	 *	\param		option			Sur quoi pointe le lien
	 *	\return		string			Chaine avec URL
	 */
	function getNomUrl($withpicto=0,$option='')
	{
		global $langs;

		$result='';

		$lien = '<a href="'.DOL_URL_ROOT.'/business/phases/phase.php?id='.$this->id.'">';
		$lienfin='</a>';

		$picto='projecttask';

		$label=$langs->trans("ShowPhase").': '.$this->ref.($this->label?' - '.$this->label:'');

		if ($withpicto) $result.=($lien.img_object($label,$picto).$lienfin);
		if ($withpicto && $withpicto != 2) $result.=' ';
		if ($withpicto != 2) $result.=$lien.$this->ref.$lienfin;
		return $result;
	}

	/**
	 *		\brief		Initialise object with example values
	 *		\remarks	id must be 0 if object instance is a specimen.
	 */
	function initAsSpecimen()
	{
		$this->id=0;

		$this->fk_projet='';
		$this->fk_task_parent='';
		$this->title='';
		$this->duration_effective='';
		$this->fk_user_creat='';
		$this->statut='';
		$this->note='';
	}

	/**
	 * Return list of phases for a business
	 * Sort order is on business
	 * @param	user		Object user to limit phase affected to a particular user
	 * @param	businessid	Business id
	 * @param	socid		Third party id
	 * @return 	array		Array of tasks
	 */
	function getPhasesArray($user=0, $businessid=0, $socid=0)
	{
		global $conf;

		$phases = array();

		$sql = "SELECT b.rowid as businessid, b.ref, b.label as business_label, b.public";
		$sql.= ", p.rowid as phaseid, p.label as phase_label, p.progress, p.total_ht, p.total_ttc, p.fk_statut";
		$sql.= " FROM ".MAIN_DB_PREFIX."business as b";
		$sql.= " RIGHT JOIN ".MAIN_DB_PREFIX."business_phase as p on p.fk_business = b.rowid";
		$sql.= " WHERE b.entity = ".$conf->entity;
		if ($socid)	$sql.= " AND b.fk_soc = ".$socid;
		if ($businessid) $sql.= " AND b.rowid =".$businessid;
		$sql.= " ORDER BY p.rang ASC, p.rowid";
		
		//print $sql;

		dol_syslog("Phase::getPhasesArray sql=".$sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			// Loop on each record found, so each couple (project id, task id)
			while ($i < $num)
			{
				$error=0;

				$obj = $this->db->fetch_object($resql);

				if ((! $obj->public) && is_object($user))	// If not public and we ask a filter on user
				{
					if (! $this->getUserRolesForPhase($user, $obj->phaseid) && ! $user->rights->business->all->read)
					{
						$error++;
						//print '<br>error<br>';
					}
				}

				if (! $error)
				{
					$phases[$i]->id           	= $obj->phaseid;
					$phases[$i]->businessid   	= $obj->businessid;
					$phases[$i]->businessref  	= $obj->ref;
					$phases[$i]->businesslabel	= $obj->business_label;
					$phases[$i]->label        	= $obj->phase_label;
					$phases[$i]->description  	= $obj->description;
					$phases[$i]->total_ht		= $obj->total_ht;
					$phases[$i]->total_ttc		= $obj->total_ttc;
					//$phases[$i]->fk_milestone 	= $obj->fk_milestone;
					//$phases[$i]->duration     	= $obj->duration_effective;
					$phases[$i]->statut     	= $obj->fk_statut;
					$phases[$i]->progress     	= $obj->progress;
					$phases[$i]->public       	= $obj->public;
				}

				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}

		return $phases;
	}

	/**
	 * Return list of roles for a user for each projects or each tasks (or a particular project or task)
	 * @param 	user
	 * @param 	businessid		Business id to filter on a business
	 * @return 	array			Array (businessid => 'list of roles for business')
	 */
	function getUserRolesForPhase($user,$phaseid=0)
	{
		$phaserole = array();

		dol_syslog("Phase::getUserRolesForPhase user=".is_object($user)." phaseid=".$phaseid);

		$sql = "SELECT p.rowid as phaseid, ec.element_id, ctc.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."business_phase as p";
		$sql.= ", ".MAIN_DB_PREFIX."element_contact as ec";
		$sql.= ", ".MAIN_DB_PREFIX."c_type_contact as ctc";
		$sql.= " WHERE p.rowid = ec.element_id";
		$sql.= " AND ctc.element = 'business_phase'";
		$sql.= " AND ctc.rowid = ec.fk_c_type_contact";
		if (is_object($user)) $sql.= " AND ec.fk_socpeople = ".$user->id;
		$sql.= " AND ec.statut = 4";
		if ($phaseid) $sql.= " AND p.rowid = ".$phaseid;

		//print $sql.'<br>';
		dol_syslog("Phase::getUserRolesForPhase sql=".$sql);
		$resql = $this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i = 0;
			while ($i < $num)
			{
				$obj = $this->db->fetch_object($resql);
				if (empty($phaserole[$obj->phaseid])) $phaserole[$obj->phaseid] = $obj->code;
				else $phaserole[$obj->phaseid].=','.$obj->code;
				$i++;
			}
			$this->db->free($resql);
		}
		else
		{
			dol_print_error($this->db);
		}

		return $phaserole;
	}

	/**
	 *    \brief     Add time spent
	 *    \param     user           user id
	 *    \param     notrigger	    0=launch triggers after, 1=disable triggers
	 */
	function addTimeSpent($user, $notrigger=0)
	{
		$ret = 0;

		// Clean parameters
		if (isset($this->timespent_note)) $this->timespent_note = trim($this->timespent_note);

		$sql = "INSERT INTO ".MAIN_DB_PREFIX."projet_task_time (";
		$sql.= "fk_task";
		$sql.= ", task_date";
		$sql.= ", task_duration";
		$sql.= ", fk_user";
		$sql.= ", note";
		$sql.= ") VALUES (";
		$sql.= $this->id;
		$sql.= ", '".$this->db->idate($this->timespent_date)."'";
		$sql.= ", ".$this->timespent_duration;
		$sql.= ", ".$this->timespent_fk_user;
		$sql.= ", ".(isset($this->timespent_note)?"'".addslashes($this->timespent_note)."'":"null");
		$sql.= ")";

		dol_syslog(get_class($this)."::addTimeSpent sql=".$sql, LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			$task_id = $this->db->last_insert_id(MAIN_DB_PREFIX."projet_task_time");
			$ret = $task_id;

			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('TASK_TIMESPENT_CREATE',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::addTimeSpent error -1 ".$this->error,LOG_ERR);
			$ret = -1;
		}

		if ($ret >= 0)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET duration_effective = duration_effective + '".price2num($this->timespent_duration)."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::addTimeSpent sql=".$sql, LOG_DEBUG);
			if (! $this->db->query($sql) )
			{
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::addTimeSpent error -2 ".$this->error, LOG_ERR);
				$ret = -2;
			}
		}

		return $ret;
	}

    /**
     *    \brief      Load object in memory from database
     *    \param      id          id object
     *    \return     int         <0 if KO, >0 if OK
     */
    function fetchTimeSpent($id)
    {
    	global $langs;

        $sql = "SELECT";
		$sql.= " t.rowid,";
		$sql.= " t.fk_task,";
		$sql.= " t.task_date,";
		$sql.= " t.task_duration,";
		$sql.= " t.fk_user,";
		$sql.= " t.note";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
        $sql.= " WHERE t.rowid = ".$id;

    	dol_syslog(get_class($this)."::fetchTimeSpent sql=".$sql, LOG_DEBUG);
        $resql=$this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
            {
                $obj = $this->db->fetch_object($resql);

                $this->timespent_id			= $obj->rowid;
				$this->id					= $obj->fk_task;
				$this->timespent_date		= $obj->task_date;
				$this->timespent_duration	= $obj->task_duration;
				$this->timespent_user		= $obj->fk_user;
				$this->timespent_note		= $obj->note;
            }

            $this->db->free($resql);

            return 1;
        }
        else
        {
      	    $this->error="Error ".$this->db->lasterror();
            dol_syslog(get_class($this)."::fetchTimeSpent ".$this->error, LOG_ERR);
            return -1;
        }
    }

	/**
	 *    \brief     Update time spent
	 *    \param     user           User id
	 *    \param     notrigger	    0=launch triggers after, 1=disable triggers
	 */
	function updateTimeSpent($user, $notrigger=0)
	{
		$ret = 0;

		// Clean parameters
		if (isset($this->timespent_note)) $this->timespent_note = trim($this->timespent_note);

		$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task_time SET";
		$sql.= " task_date = '".$this->db->idate($this->timespent_date)."',";
		$sql.= " task_duration = ".$this->timespent_duration.",";
		$sql.= " fk_user = ".$this->timespent_fk_user.",";
		$sql.= " note = ".(isset($this->timespent_note)?"'".addslashes($this->timespent_note)."'":"null");
		$sql.= " WHERE rowid = ".$this->timespent_id;

		dol_syslog(get_class($this)."::updateTimeSpent sql=".$sql, LOG_DEBUG);
		if ($this->db->query($sql) )
		{
			if (! $notrigger)
			{
	            // Call triggers
	            include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
	            $interface=new Interfaces($this->db);
	            $result=$interface->run_triggers('TASK_TIMESPENT_MODIFY',$this,$user,$langs,$conf);
	            if ($result < 0) { $error++; $this->errors=$interface->errors; }
	            // End call triggers
			}
			$ret = 1;
		}
		else
		{
			$this->error=$this->db->lasterror();
			dol_syslog(get_class($this)."::updateTimeSpent error -1 ".$this->error,LOG_ERR);
			$ret = -1;
		}

		if ($ret == 1 && ($this->timespent_old_duration != $this->timespent_duration))
		{
			$newDuration = $this->timespent_duration - $this->timespent_old_duration;

			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET duration_effective = duration_effective + '".$newDuration."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::updateTimeSpent sql=".$sql, LOG_DEBUG);
			if (! $this->db->query($sql) )
			{
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::addTimeSpent error -2 ".$this->error, LOG_ERR);
				$ret = -2;
			}
		}

		return $ret;
	}
    
	/**
	 *    \brief      Delete time spent
	 *    \param      user        	User that delete
	 *    \param      notrigger	    0=launch triggers after, 1=disable triggers
	 *    \return		int			<0 if KO, >0 if OK
	 */
	function delTimeSpent($user, $notrigger=0)
	{
		global $conf, $langs;

		$error=0;

		$this->db->begin();

		$sql = "DELETE FROM ".MAIN_DB_PREFIX."projet_task_time";
		$sql.= " WHERE rowid = ".$this->timespent_id;

		dol_syslog(get_class($this)."::delTimeSpent sql=".$sql);
		$resql = $this->db->query($sql);
    	if (! $resql) { $error++; $this->errors[]="Error ".$this->db->lasterror(); }

		if (! $error)
		{
			if (! $notrigger)
			{
		        // Call triggers
		        include_once(DOL_DOCUMENT_ROOT . "/core/interfaces.class.php");
		        $interface=new Interfaces($this->db);
		        $result=$interface->run_triggers('TASK_TIMESPENT_DELETE',$this,$user,$langs,$conf);
		        if ($result < 0) { $error++; $this->errors=$interface->errors; }
		        // End call triggers
			}
		}

		if (! $error)
		{
			$sql = "UPDATE ".MAIN_DB_PREFIX."projet_task";
			$sql.= " SET duration_effective = duration_effective - '".$this->timespent_duration."'";
			$sql.= " WHERE rowid = ".$this->id;

			dol_syslog(get_class($this)."::delTimeSpent sql=".$sql, LOG_DEBUG);
			if ($this->db->query($sql) )
			{
				$result = 0;
			}
			else
			{
				$this->error=$this->db->lasterror();
				dol_syslog(get_class($this)."::addTimeSpent error -3 ".$this->error, LOG_ERR);
				$result = -2;
			}
		}

        // Commit or rollback
		if ($error)
		{
			foreach($this->errors as $errmsg)
			{
	            dol_syslog(get_class($this)."::delTimeSpent ".$errmsg, LOG_ERR);
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
	 *    \brief      Return status label of object
	 *    \param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * 	  \return     string      Label
	 */
	function getLibStatut($mode=0)
	{
		return $this->LibStatut($this->statut,$mode);
	}

	/**
	 *    \brief      Return status label of object
	 *    \param      statut      id statut
	 *    \param      mode        0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
	 * 	  \return     string      Label
	 */
	function LibStatut($statut,$mode=0)
	{
		global $langs;

		if ($mode == 0)
		{
			return $langs->trans($this->statuts[$statut]);
		}
		if ($mode == 1)
		{
			return $langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 2)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut1').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut3').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==5) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 3)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut1');
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut3');
			if ($statut==5) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
		}
		if ($mode == 4)
		{
			if ($statut==0) return img_picto($langs->trans($this->statuts_short[$statut]),'statut0').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==1) return img_picto($langs->trans($this->statuts_short[$statut]),'statut1').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==2) return img_picto($langs->trans($this->statuts_short[$statut]),'statut3').' '.$langs->trans($this->statuts_short[$statut]);
			if ($statut==5) return img_picto($langs->trans($this->statuts_short[$statut]),'statut4').' '.$langs->trans($this->statuts_short[$statut]);
		}
		if ($mode == 5)
		{
			if ($statut==0) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut0');
			if ($statut==1) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut1');
			if ($statut==2) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut3');
			if ($statut==5) return $langs->trans($this->statuts_short[$statut]).' '.img_picto($langs->trans($this->statuts_short[$statut]),'statut4');
		}
	}

}
?>