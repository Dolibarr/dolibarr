<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014-2017 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017      Ferran Marcet        <fmarcet@2byte.es>
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
 * 		\file       htdocs/projet/class/project.class.php
 * 		\ingroup    projet
 * 		\brief      File of class to manage projects
 */
require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';

/**
 *	Class to manage projects
 */
class Project extends CommonObject
{

    public $element = 'project';    //!< Id that identify managed objects
    public $table_element = 'projet';  //!< Name of table without prefix where object is stored
    public $table_element_line = 'projet_task';
    public $fk_element = 'fk_projet';
    public $ismultientitymanaged = 1;  // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe
    public $picto = 'projectpub';

    /**
     * {@inheritdoc}
     */
    protected $table_ref_field = 'ref';

    var $description;
	/**
	 * @var string
	 * @deprecated
	 * @see title
	 */
	public $titre;
    var $title;
    var $date_start;
    var $date_end;
    var $date_close;

    var $socid;             // To store id of thirdparty
    var $thirdparty_name;   // To store name of thirdparty (defined only in some cases)

    var $user_author_id;    //!< Id of project creator. Not defined if shared project.
	var $user_close_id;
    var $public;      //!< Tell if this is a public or private project
    var $budget_amount;
    var $bill_time;			// Is the time spent on project must be invoiced or not

    var $statuts_short;
    var $statuts_long;

    var $statut;			// 0=draft, 1=opened, 2=closed
    var $opp_status;		// opportunity status, into table llx_c_lead_status
	var $opp_percent;		// opportunity probability

    var $oldcopy;

    var $weekWorkLoad;			// Used to store workload details of a projet
    var $weekWorkLoadPerTask;	// Used to store workload details of tasks of a projet

	/**
	 * @var int Creation date
	 * @deprecated
	 * @see date_c
	 */
	public $datec;
	/**
	 * @var int Creation date
	 */
	public $date_c;
	/**
	 * @var int Modification date
	 * @deprecated
	 * @see date_m
	 */
	public $datem;
	/**
	 * @var int Modification date
	 */
	public $date_m;

	/**
	 * @var Task[]
	 */
	public $lines;

	/**
	 * Draft status
	 */
	const STATUS_DRAFT = 0;
	/**
	 * Open/Validated status
	 */
	const STATUS_VALIDATED = 1;
	/**
	 * Closed status
	 */
	const STATUS_CLOSED = 2;



    /**
     *  Constructor
     *
     *  @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        $this->statuts_short = array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');
        $this->statuts_long = array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');
    }

    /**
     *    Create a project into database
     *
     *    @param    User	$user       	User making creation
     *    @param	int		$notrigger		Disable triggers
     *    @return   int         			<0 if KO, id of created project if OK
     */
    function create($user, $notrigger=0)
    {
        global $conf, $langs;

        $error = 0;
        $ret = 0;

        $now=dol_now();

        // Check parameters
        if (!trim($this->ref))
        {
            $this->error = 'ErrorFieldsRequired';
            dol_syslog(get_class($this)."::create error -1 ref null", LOG_ERR);
            return -1;
        }
        if (! empty($conf->global->PROJECT_THIRDPARTY_REQUIRED) && ! $this->socid > 0)
        {
            $this->error = 'ErrorFieldsRequired';
            dol_syslog(get_class($this)."::create error -1 ref null", LOG_ERR);
            return -1;
        }

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "projet (";
        $sql.= "ref";
        $sql.= ", title";
        $sql.= ", description";
        $sql.= ", fk_soc";
        $sql.= ", fk_user_creat";
        $sql.= ", fk_statut";
        $sql.= ", fk_opp_status";
        $sql.= ", opp_percent";
        $sql.= ", public";
        $sql.= ", datec";
        $sql.= ", dateo";
        $sql.= ", datee";
        $sql.= ", opp_amount";
        $sql.= ", budget_amount";
        $sql.= ", bill_time";
        $sql.= ", entity";
        $sql.= ") VALUES (";
        $sql.= "'" . $this->db->escape($this->ref) . "'";
        $sql.= ", '" . $this->db->escape($this->title) . "'";
        $sql.= ", '" . $this->db->escape($this->description) . "'";
        $sql.= ", " . ($this->socid > 0 ? $this->socid : "null");
        $sql.= ", " . $user->id;
        $sql.= ", ".(is_numeric($this->statut) ? $this->statut : '0');
        $sql.= ", ".(is_numeric($this->opp_status) ? $this->opp_status : 'NULL');
        $sql.= ", ".(is_numeric($this->opp_percent) ? $this->opp_percent : 'NULL');
        $sql.= ", " . ($this->public ? 1 : 0);
        $sql.= ", '".$this->db->idate($now)."'";
        $sql.= ", " . ($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : 'null');
        $sql.= ", " . ($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : 'null');
        $sql.= ", " . (strcmp($this->opp_amount,'') ? price2num($this->opp_amount) : 'null');
        $sql.= ", " . (strcmp($this->budget_amount,'') ? price2num($this->budget_amount) : 'null');
        $sql.= ", " . ($this->bill_time ? 1 : 0);
        $sql.= ", ".$conf->entity;
        $sql.= ")";

        dol_syslog(get_class($this)."::create", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $this->id = $this->db->last_insert_id(MAIN_DB_PREFIX . "projet");
            $ret = $this->id;

            if (!$notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('PROJECT_CREATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers
            }
        }
        else
        {
            $this->error = $this->db->lasterror();
            $this->errno = $this->db->lasterrno();
            $error++;
        }

        // Update extrafield
        if (! $error) {
        	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        	{
        		$result=$this->insertExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        		}
        	}
        }

        if (! $error && !empty($conf->global->MAIN_DISABLEDRAFTSTATUS))
        {
            $res = $this->setValid($user);
            if ($res < 0) $error++;
        }

        if (! $error)
        {
            $this->db->commit();
            return $ret;
        }
        else
        {
            $this->db->rollback();
            return -1;
        }
    }

    /**
     * Update a project
     *
     * @param  User		$user       User object of making update
     * @param  int		$notrigger  1=Disable all triggers
     * @return int                  <=0 if KO, >0 if OK
     */
    function update($user, $notrigger=0)
    {
        global $langs, $conf;

		$error=0;

        // Clean parameters
        $this->title = trim($this->title);
        $this->description = trim($this->description);
		if ($this->opp_amount < 0) $this->opp_amount='';
		if ($this->opp_percent < 0) $this->opp_percent='';
        if ($this->date_end && $this->date_end < $this->date_start)
        {
            $this->error = $langs->trans("ErrorDateEndLowerThanDateStart");
            $this->errors[] = $this->error;
            $this->db->rollback();
            dol_syslog(get_class($this)."::update error -3 " . $this->error, LOG_ERR);
            return -3;
        }

        if (dol_strlen(trim($this->ref)) > 0)
        {
            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet SET";
            $sql.= " ref='" . $this->db->escape($this->ref) . "'";
            $sql.= ", title = '" . $this->db->escape($this->title) . "'";
            $sql.= ", description = '" . $this->db->escape($this->description) . "'";
            $sql.= ", fk_soc = " . ($this->socid > 0 ? $this->socid : "null");
            $sql.= ", fk_statut = " . $this->statut;
            $sql.= ", fk_opp_status = " . ((is_numeric($this->opp_status) && $this->opp_status > 0) ? $this->opp_status : 'null');
			$sql.= ", opp_percent = " . ((is_numeric($this->opp_percent) && $this->opp_percent != '') ? $this->opp_percent : 'null');
            $sql.= ", public = " . ($this->public ? 1 : 0);
            $sql.= ", datec=" . ($this->date_c != '' ? "'".$this->db->idate($this->date_c)."'" : 'null');
            $sql.= ", dateo=" . ($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : 'null');
            $sql.= ", datee=" . ($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : 'null');
            $sql.= ", date_close=" . ($this->date_close != '' ? "'".$this->db->idate($this->date_close)."'" : 'null');
            $sql.= ", fk_user_close=" . ($this->fk_user_close > 0 ? $this->fk_user_close : "null");
            $sql.= ", opp_amount = " . (strcmp($this->opp_amount, '') ? price2num($this->opp_amount) : "null");
            $sql.= ", budget_amount = " . (strcmp($this->budget_amount, '')  ? price2num($this->budget_amount) : "null");
            $sql.= ", fk_user_modif = " . $user->id;
            $sql.= ", bill_time = " . ($this->bill_time ? 1 : 0);
            $sql.= " WHERE rowid = " . $this->id;

            dol_syslog(get_class($this)."::update", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                // Update extrafield
                if (! $error)
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

                if (! $error && ! $notrigger)
                {
                	// Call trigger
                	$result=$this->call_trigger('PROJECT_MODIFY',$user);
                	if ($result < 0) { $error++; }
                	// End call triggers
                }

                if (! $error && (is_object($this->oldcopy) && $this->oldcopy->ref !== $this->ref))
                {
                	// We remove directory
                	if ($conf->projet->dir_output)
                	{
                		$olddir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($this->oldcopy->ref);
                		$newdir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($this->ref);
                		if (file_exists($olddir))
                		{
							include_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';
							$res=dol_move($olddir, $newdir);
							if (! $res)
                			{
							    $langs->load("errors");
								$this->error=$langs->trans('ErrorFailToRenameDir',$olddir,$newdir);
                				$error++;
                			}
                		}
                	}
                }
                if (! $error )
                {
                    $this->db->commit();
                    $result = 1;
                }
                else
              {
                    $this->db->rollback();
                    $result = -1;
                }
            }
            else
			{
		        $this->error = $this->db->lasterror();
		        $this->errors[] = $this->error;
		        $this->db->rollback();
			    if ($this->db->lasterrno() == 'DB_ERROR_RECORD_ALREADY_EXISTS')
			    {
			        $result = -4;
			    }
			    else
			    {
			        $result = -2;
			    }
		        dol_syslog(get_class($this)."::update error " . $result . " " . $this->error, LOG_ERR);
			}
        }
        else
        {
            dol_syslog(get_class($this)."::update ref null");
            $result = -1;
        }

        return $result;
    }

    /**
     * 	Get object from database
     *
     * 	@param      int		$id       	Id of object to load
     * 	@param		string	$ref		Ref of project
     * 	@return     int      		   	>0 if OK, 0 if not found, <0 if KO
     */
    function fetch($id, $ref='')
    {
    	global $conf;

        if (empty($id) && empty($ref)) return -1;

        $sql = "SELECT rowid, ref, title, description, public, datec, opp_amount, budget_amount,";
        $sql.= " tms, dateo, datee, date_close, fk_soc, fk_user_creat, fk_user_modif, fk_user_close, fk_statut, fk_opp_status, opp_percent,";
        $sql.= " note_private, note_public, model_pdf, bill_time";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet";
        if (! empty($id))
        {
        	$sql.= " WHERE rowid=".$id;
        }
        else if (! empty($ref))
        {
        	$sql.= " WHERE ref='".$this->db->escape($ref)."'";
        	$sql.= " AND entity IN (".getEntity('project').")";
        }

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num_rows = $this->db->num_rows($resql);

            if ($num_rows)
            {
                $obj = $this->db->fetch_object($resql);

                $this->id = $obj->rowid;
                $this->ref = $obj->ref;
                $this->title = $obj->title;
                $this->titre = $obj->title; // TODO deprecated
                $this->description = $obj->description;
                $this->date_c = $this->db->jdate($obj->datec);
                $this->datec = $this->db->jdate($obj->datec); // TODO deprecated
                $this->date_m = $this->db->jdate($obj->tms);
                $this->datem = $this->db->jdate($obj->tms);  // TODO deprecated
                $this->date_start = $this->db->jdate($obj->dateo);
                $this->date_end = $this->db->jdate($obj->datee);
                $this->date_close = $this->db->jdate($obj->date_close);
                $this->note_private = $obj->note_private;
                $this->note_public = $obj->note_public;
                $this->socid = $obj->fk_soc;
                $this->user_author_id = $obj->fk_user_creat;
                $this->user_modification_id = $obj->fk_user_modif;
                $this->user_close_id = $obj->fk_user_close;
                $this->public = $obj->public;
                $this->statut = $obj->fk_statut;
                $this->opp_status = $obj->fk_opp_status;
                $this->opp_amount	= $obj->opp_amount;
                $this->opp_percent	= $obj->opp_percent;
                $this->budget_amount	= $obj->budget_amount;
                $this->modelpdf	= $obj->model_pdf;
                $this->bill_time = (int) $obj->bill_time;

                $this->db->free($resql);

                // Retreive all extrafield
                // fetch optionals attributes and labels
                $this->fetch_optionals();

                return 1;
            }

            $this->db->free($resql);

            return 0;
        }
        else
        {
            $this->error = $this->db->lasterror();
            return -1;
        }
    }

    /**
     * 	Return list of projects
     *
     * 	@param		int		$socid		To filter on a particular third party
     * 	@return		array				List of projects
     */
    function liste_array($socid='')
    {
        global $conf;

        $projects = array();

        $sql = "SELECT rowid, title";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet";
        $sql.= " WHERE entity = " . $conf->entity;
        if (! empty($socid)) $sql.= " AND fk_soc = " . $socid;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $nump = $this->db->num_rows($resql);

            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($resql);

                    $projects[$obj->rowid] = $obj->title;
                    $i++;
                }
            }
            return $projects;
        }
        else
        {
            print $this->db->lasterror();
        }
    }

    /**
     * 	Return list of elements for type, linked to a project
     *
     * 	@param		string		$type			'propal','order','invoice','order_supplier','invoice_supplier',...
     * 	@param		string		$tablename		name of table associated of the type
     * 	@param		string		$datefieldname	name of date field for filter
     *  @param		int			$dates			Start date
     *  @param		int			$datee			End date
     * 	@return		mixed						Array list of object ids linked to project, < 0 or string if error
     */
    function get_element_list($type, $tablename, $datefieldname='', $dates='', $datee='')
    {
        $elements = array();

        if ($this->id <= 0) return $elements;

        $ids = $this->id;

		if ($type == 'agenda')
        {
        	$sql = "SELECT id as rowid FROM " . MAIN_DB_PREFIX . "actioncomm WHERE fk_project IN (". $ids .")";
        }
        elseif ($type == 'expensereport')
		{
            $sql = "SELECT ed.rowid FROM " . MAIN_DB_PREFIX . "expensereport as e, " . MAIN_DB_PREFIX . "expensereport_det as ed WHERE e.rowid = ed.fk_expensereport AND ed.fk_projet IN (". $ids .")";
		}
        elseif ($type == 'project_task')
		{
			$sql = "SELECT DISTINCT pt.rowid FROM " . MAIN_DB_PREFIX . "projet_task as pt, " . MAIN_DB_PREFIX . "projet_task_time as ptt WHERE pt.rowid = ptt.fk_task AND pt.fk_projet IN (". $ids .")";
		}
		elseif ($type == 'project_task_time')	// Case we want to duplicate line foreach user
		{
			$sql = "SELECT DISTINCT pt.rowid, ptt.fk_user FROM " . MAIN_DB_PREFIX . "projet_task as pt, " . MAIN_DB_PREFIX . "projet_task_time as ptt WHERE pt.rowid = ptt.fk_task AND pt.fk_projet IN (". $ids .")";
		}
		elseif ($type == 'stock_mouvement')
		{
			$sql = 'SELECT ms.rowid, ms.fk_user_author as fk_user FROM ' . MAIN_DB_PREFIX . "stock_mouvement as ms WHERE ms.origintype = 'project' AND ms.fk_origin  IN (". $ids .") AND ms.type_mouvement = 1";
		}
        else
		{
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . $tablename." WHERE fk_projet IN (". $ids .")";
		}

		if ($dates > 0)
		{
			if (empty($datefieldname) && ! empty($this->table_element_date)) $datefieldname=$this->table_element_date;
			if (empty($datefieldname)) return 'Error this object has no date field defined';
			$sql.=" AND (".$datefieldname." >= '".$this->db->idate($dates)."' OR ".$datefieldname." IS NULL)";
		}
    	if ($datee > 0)
		{
			if (empty($datefieldname) && ! empty($this->table_element_date)) $datefieldname=$this->table_element_date;
			if (empty($datefieldname)) return 'Error this object has no date field defined';
			$sql.=" AND (".$datefieldname." <= '".$this->db->idate($datee)."' OR ".$datefieldname." IS NULL)";
		}
		if (! $sql) return -1;

        //print $sql;
        dol_syslog(get_class($this)."::get_element_list", LOG_DEBUG);
        $result = $this->db->query($sql);
        if ($result)
        {
            $nump = $this->db->num_rows($result);
            if ($nump)
            {
                $i = 0;
                while ($i < $nump)
                {
                    $obj = $this->db->fetch_object($result);

                    $elements[$i] = $obj->rowid.(empty($obj->fk_user)?'':'_'.$obj->fk_user);

                    $i++;
                }
                $this->db->free($result);
            }

            /* Return array even if empty*/
            return $elements;
        }
        else
        {
            dol_print_error($this->db);
        }
    }

    /**
     *    Delete a project from database
     *
     *    @param       User		$user            User
     *    @param       int		$notrigger       Disable triggers
     *    @return      int       			      <0 if KO, 0 if not possible, >0 if OK
     */
    function delete($user, $notrigger=0)
    {
        global $langs, $conf;
        require_once DOL_DOCUMENT_ROOT . '/core/lib/files.lib.php';

        $error = 0;

        $this->db->begin();

        if (!$error)
        {
            // Delete linked contacts
            $res = $this->delete_linked_contact();
            if ($res < 0)
            {
                $this->error = 'ErrorFailToDeleteLinkedContact';
                //$error++;
                $this->db->rollback();
                return 0;
            }
        }

        // Set fk_projet into elements to null
        $listoftables=array(
        		'facture'=>'fk_projet','propal'=>'fk_projet','commande'=>'fk_projet',
                'facture_fourn'=>'fk_projet','commande_fournisseur'=>'fk_projet','supplier_proposal'=>'fk_projet',
        		'expensereport_det'=>'fk_projet','contrat'=>'fk_projet','fichinter'=>'fk_projet','don'=>'fk_projet'
        		);
        foreach($listoftables as $key => $value)
        {
   	        $sql = "UPDATE " . MAIN_DB_PREFIX . $key . " SET ".$value." = NULL where ".$value." = ". $this->id;
	        $resql = $this->db->query($sql);
	        if (!$resql)
	        {
	        	$this->errors[] = $this->db->lasterror();
	        	$error++;
	        	break;
	        }
        }

		// Fetch tasks
		$this->getLinesArray($user);

		// Delete tasks
		$ret = $this->deleteTasks($user);
		if ($ret < 0) $error++;

        // Delete project
        if (! $error)
        {
	        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet";
	        $sql.= " WHERE rowid=" . $this->id;

	        $resql = $this->db->query($sql);
	        if (!$resql)
	        {
	        	$this->errors[] = $langs->trans("CantRemoveProject");
	        	$error++;
	        }
        }

        if (! $error)
        {
	        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet_extrafields";
	        $sql.= " WHERE fk_object=" . $this->id;

	        $resql = $this->db->query($sql);
	        if (! $resql)
	        {
	        	$this->errors[] = $this->db->lasterror();
	        	$error++;
	        }
        }

        if (empty($error))
        {
            // We remove directory
            $projectref = dol_sanitizeFileName($this->ref);
            if ($conf->projet->dir_output)
            {
                $dir = $conf->projet->dir_output . "/" . $projectref;
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

            if (!$notrigger)
            {
                // Call trigger
                $result=$this->call_trigger('PROJECT_DELETE',$user);

                if ($result < 0) {
                    $error++;
                }
                // End call triggers
            }
        }

    	if (empty($error))
    	{
            $this->db->commit();
            return 1;
        }
        else
       {
        	foreach ( $this->errors as $errmsg )
        	{
				dol_syslog(get_class($this) . "::delete " . $errmsg, LOG_ERR);
				$this->error .= ($this->error ? ', ' . $errmsg : $errmsg);
			}
            dol_syslog(get_class($this) . "::delete " . $this->error, LOG_ERR);
            $this->db->rollback();
            return -1;
        }
    }
    
    /**
     * 		Delete tasks with no children first, then task with children recursively
     *  
     *  	@param     	User		$user		User
     *		@return		int				<0 if KO, 1 if OK
     */
    function deleteTasks($user)
    {
        $countTasks = count($this->lines);
        $deleted = false;
        if ($countTasks)
        {
            foreach($this->lines as $task)
            {
                if ($task->hasChildren() <= 0) {		// If there is no children (or error to detect them)
                    $deleted = true;
                    $ret = $task->delete($user);
                    if ($ret <= 0)
                    {
                        $this->errors[] = $this->db->lasterror();
                        return -1;
                    }
                }
            }
        }
        $this->getLinesArray($user);
        if ($deleted && count($this->lines) < $countTasks)
        {
            if (count($this->lines)) $this->deleteTasks($this->lines);
        }
        
        return 1;
    }

    /**
     * 		Validate a project
     *
     * 		@param		User	$user		   User that validate
     *      @param      int     $notrigger     1=Disable triggers
     * 		@return		int					   <0 if KO, >0 if OK
     */
    function setValid($user, $notrigger=0)
    {
        global $langs, $conf;

		$error=0;

        if ($this->statut != 1)
        {
            // Check parameters
            if (preg_match('/^'.preg_quote($langs->trans("CopyOf").' ').'/', $this->title))
            {
                $this->error=$langs->trans("ErrorFieldFormat",$langs->transnoentities("Label")).'. '.$langs->trans('RemoveString',$langs->transnoentitiesnoconv("CopyOf"));
                return -1;
            }

            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet";
            $sql.= " SET fk_statut = 1";
            $sql.= " WHERE rowid = " . $this->id;
            $sql.= " AND entity = " . $conf->entity;

            dol_syslog(get_class($this)."::setValid", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                // Call trigger
                if (empty($notrigger))
                {
                    $result=$this->call_trigger('PROJECT_VALIDATE',$user);
                    if ($result < 0) { $error++; }
                    // End call triggers
                }

                if (!$error)
                {
                	$this->statut=1;
                	$this->db->commit();
                    return 1;
                }
                else
                {
                    $this->db->rollback();
                    $this->error = join(',', $this->errors);
                    dol_syslog(get_class($this)."::setValid " . $this->error, LOG_ERR);
                    return -1;
                }
            }
            else
            {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                return -1;
            }
        }
    }

    /**
     * 		Close a project
     *
     * 		@param		User	$user		User that close project
     * 		@return		int					<0 if KO, 0 if already closed, >0 if OK
     */
    function setClose($user)
    {
        global $langs, $conf;

        $now = dol_now();

		$error=0;

        if ($this->statut != 2)
        {
            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet";
            $sql.= " SET fk_statut = 2, fk_user_close = ".$user->id.", date_close = '".$this->db->idate($now)."'";
            $sql.= " WHERE rowid = " . $this->id;
            $sql.= " AND entity = " . $conf->entity;
            $sql.= " AND fk_statut = 1";

            if (! empty($conf->global->PROJECT_USE_OPPORTUNITIES))
            {
            	// TODO What to do if fk_opp_status is not code 'WON' or 'LOST'
            }

            dol_syslog(get_class($this)."::setClose", LOG_DEBUG);
            $resql = $this->db->query($sql);
            if ($resql)
            {
                // Call trigger
                $result=$this->call_trigger('PROJECT_CLOSE',$user);
                if ($result < 0) { $error++; }
                // End call triggers

                if (!$error)
                {
                    $this->statut = 2;
                    $this->db->commit();
                    return 1;
                }
                else
                {
                    $this->db->rollback();
                    $this->error = join(',', $this->errors);
                    dol_syslog(get_class($this)."::setClose " . $this->error, LOG_ERR);
                    return -1;
                }
            }
            else
            {
                $this->db->rollback();
                $this->error = $this->db->lasterror();
                return -1;
            }
        }

        return 0;
    }

    /**
     *  Return status label of object
     *
     *  @param  int			$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * 	@return string      			Label
     */
    function getLibStatut($mode=0)
    {
        return $this->LibStatut($this->statut, $mode);
    }

    /**
     *  Renvoi status label for a status
     *
     *  @param	int		$statut     id statut
     *  @param  int		$mode       0=long label, 1=short label, 2=Picto + short label, 3=Picto, 4=Picto + long label, 5=Short label + Picto
     * 	@return string				Label
     */
    function LibStatut($statut, $mode=0)
    {
        global $langs;

        if ($mode == 0)
        {
            return $langs->trans($this->statuts_long[$statut]);
        }
        if ($mode == 1)
        {
            return $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 2)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_long[$statut]), 'statut0') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_long[$statut]), 'statut4') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_long[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 3)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_long[$statut]), 'statut0');
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_long[$statut]), 'statut4');
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_long[$statut]), 'statut6');
        }
        if ($mode == 4)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_long[$statut]), 'statut0') . ' ' . $langs->trans($this->statuts_long[$statut]);
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_long[$statut]), 'statut4') . ' ' . $langs->trans($this->statuts_long[$statut]);
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_long[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_long[$statut]);
        }
        if ($mode == 5)
        {
            if ($statut == 0)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_long[$statut]), 'statut0');
            if ($statut == 1)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_long[$statut]), 'statut4');
            if ($statut == 2)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_long[$statut]), 'statut6');
        }
    }

    /**
     * 	Return clicable name (with picto eventually)
     *
     * 	@param	int		$withpicto		          0=No picto, 1=Include picto into link, 2=Only picto
     * 	@param	string	$option			          Variant ('', 'nolink')
     * 	@param	int		$addlabel		          0=Default, 1=Add label into string, >1=Add first chars into string
     *  @param	string	$moreinpopup	          Text to add into popup
     *  @param	string	$sep			          Separator between ref and label if option addlabel is set
     *  @param	int   	$notooltip		          1=Disable tooltip
     *  @param  int     $save_lastsearch_value    -1=Auto, 0=No save of lastsearch_values when clicking, 1=Save lastsearch_values whenclicking
     * 	@return	string					          String with URL
     */
    function getNomUrl($withpicto=0, $option='', $addlabel=0, $moreinpopup='', $sep=' - ', $notooltip=0, $save_lastsearch_value=-1)
    {
        global $conf, $langs, $user, $hookmanager;

        if (! empty($conf->dol_no_mouse_hover)) $notooltip=1;   // Force disable tooltips

        $result = '';

        $label='';
        if ($option != 'nolink') $label = '<u>' . $langs->trans("ShowProject") . '</u>';
        $label .= ($label?'<br>':'').'<b>' . $langs->trans('Ref') . ': </b>' . $this->ref;	// The space must be after the : to not being explode when showing the title in img_picto
        $label .= ($label?'<br>':'').'<b>' . $langs->trans('Label') . ': </b>' . $this->title;	// The space must be after the : to not being explode when showing the title in img_picto
        if (! empty($this->thirdparty_name))
            $label .= ($label?'<br>':'').'<b>' . $langs->trans('ThirdParty') . ': </b>' . $this->thirdparty_name;	// The space must be after the : to not being explode when showing the title in img_picto
        if (! empty($this->dateo))
            $label .= ($label?'<br>':'').'<b>' . $langs->trans('DateStart') . ': </b>' . dol_print_date($this->dateo, 'day');	// The space must be after the : to not being explode when showing the title in img_picto
        if (! empty($this->datee))
            $label .= ($label?'<br>':'').'<b>' . $langs->trans('DateEnd') . ': </b>' . dol_print_date($this->datee, 'day');	// The space must be after the : to not being explode when showing the title in img_picto
        if ($moreinpopup) $label.='<br>'.$moreinpopup;

        $url='';
        if ($option != 'nolink')
        {
            if (preg_match('/\.php$/',$option)) {
                $url = dol_buildpath($option,1) . '?id=' . $this->id;
            }
            else if ($option == 'task')
            {
                $url = DOL_URL_ROOT . '/projet/tasks.php?id=' . $this->id;
            }
            else
            {
                $url = DOL_URL_ROOT . '/projet/card.php?id=' . $this->id;
            }
            // Add param to save lastsearch_values or not
            $add_save_lastsearch_values=($save_lastsearch_value == 1 ? 1 : 0);
            if ($save_lastsearch_value == -1 && preg_match('/list\.php/',$_SERVER["PHP_SELF"])) $add_save_lastsearch_values=1;
            if ($add_save_lastsearch_values) $url.='&save_lastsearch_values=1';
        }

        $linkclose='';
        if (empty($notooltip) && $user->rights->projet->lire)
        {
            if (! empty($conf->global->MAIN_OPTIMIZEFORTEXTBROWSER))
            {
                $label=$langs->trans("ShowProject");
                $linkclose.=' alt="'.dol_escape_htmltag($label, 1).'"';
            }
            $linkclose.=' title="'.dol_escape_htmltag($label, 1).'"';
            $linkclose.=' class="classfortooltip"';

			/*
			$hookmanager->initHooks(array('projectdao'));
			$parameters=array('id'=>$this->id);
			// Note that $action and $object may have been modified by some hooks
			$reshook=$hookmanager->executeHooks('getnomurltooltip',$parameters,$this,$action);
			if ($reshook > 0)
				$linkclose = $hookmanager->resPrint;
			*/
		}

        $picto = 'projectpub';
        if (! $this->public) $picto = 'project';

        $linkstart = '<a href="'.$url.'"';
        $linkstart.=$linkclose.'>';
        $linkend='</a>';

        $result .= $linkstart;
        if ($withpicto) $result.=img_object(($notooltip?'':$label), $picto, ($notooltip?(($withpicto != 2) ? 'class="paddingright"' : ''):'class="'.(($withpicto != 2) ? 'paddingright ' : '').'classfortooltip"'), 0, 0, $notooltip?0:1);
        if ($withpicto != 2) $result.= $this->ref;
        $result .= $linkend;
        if ($withpicto != 2) $result.=(($addlabel && $this->title) ? $sep . dol_trunc($this->title, ($addlabel > 1 ? $addlabel : 0)) : '');

        global $action;
        $hookmanager->initHooks(array('projectdao'));
        $parameters=array('id'=>$this->id, 'getnomurl'=>$result);
        $reshook=$hookmanager->executeHooks('getNomUrl',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
        if ($reshook > 0) $result = $hookmanager->resPrint;
        else $result .= $hookmanager->resPrint;

        return $result;
    }

    /**
     *  Initialise an instance with random values.
     *  Used to build previews or test instances.
     * 	id must be 0 if object instance is a specimen.
     *
     *  @return	void
     */
    function initAsSpecimen()
    {
        global $user, $langs, $conf;

        $now=dol_now();

        // Initialise parameters
        $this->id = 0;
        $this->ref = 'SPECIMEN';
        $this->specimen = 1;
        $this->socid = 1;
        $this->date_c = $now;
        $this->date_m = $now;
        $this->date_start = $now;
        $this->date_end = $now + (3600 * 24 * 365);
        $this->note_public = 'SPECIMEN';
		$this->fk_ele = 20000;
        $this->opp_amount = 20000;
        $this->budget_amount = 10000;

        /*
        $nbp = mt_rand(1, 9);
        $xnbp = 0;
        while ($xnbp < $nbp)
        {
            $line = new Task($this->db);
            $line->fk_project = 0;
            $line->label = $langs->trans("Label") . " " . $xnbp;
            $line->description = $langs->trans("Description") . " " . $xnbp;

            $this->lines[]=$line;
            $xnbp++;
        }
        */
    }

    /**
     * 	Check if user has permission on current project
     *
     * 	@param	User	$user		Object user to evaluate
     * 	@param  string	$mode		Type of permission we want to know: 'read', 'write'
     * 	@return	int					>0 if user has permission, <0 if user has no permission
     */
    function restrictedProjectArea($user, $mode='read')
    {
        // To verify role of users
        $userAccess = 0;
        if (($mode == 'read' && ! empty($user->rights->projet->all->lire)) || ($mode == 'write' && ! empty($user->rights->projet->all->creer)) || ($mode == 'delete' && ! empty($user->rights->projet->all->supprimer)))
        {
            $userAccess = 1;
        }
        else if ($this->public && (($mode == 'read' && ! empty($user->rights->projet->lire)) || ($mode == 'write' && ! empty($user->rights->projet->creer)) || ($mode == 'delete' && ! empty($user->rights->projet->supprimer))))
        {
            $userAccess = 1;
        }
        else
		{
            foreach (array('internal', 'external') as $source)
            {
                $userRole = $this->liste_contact(4, $source);
                $num = count($userRole);

                $nblinks = 0;
                while ($nblinks < $num)
                {
                    if ($source == 'internal' && preg_match('/^PROJECT/', $userRole[$nblinks]['code']) && $user->id == $userRole[$nblinks]['id'])
                    {
                        if ($mode == 'read'   && $user->rights->projet->lire)      $userAccess++;
                        if ($mode == 'write'  && $user->rights->projet->creer)     $userAccess++;
                        if ($mode == 'delete' && $user->rights->projet->supprimer) $userAccess++;
                    }
                    $nblinks++;
                }
            }
            //if (empty($nblinks))	// If nobody has permission, we grant creator
            //{
            //	if ((!empty($this->user_author_id) && $this->user_author_id == $user->id))
            //	{
            //		$userAccess = 1;
            //	}
            //}
        }

        return ($userAccess?$userAccess:-1);
    }

    /**
     * Return array of projects a user has permission on, is affected to, or all projects
     *
     * @param 	User	$user			User object
     * @param 	int		$mode			0=All project I have permission on (assigned to me and public), 1=Projects assigned to me only, 2=Will return list of all projects with no test on contacts
     * @param 	int		$list			0=Return array, 1=Return string list
     * @param	int		$socid			0=No filter on third party, id of third party
     * @param	string	$filter			additionnal filter on project (statut, ref, ...)
     * @return 	array or string			Array of projects id, or string with projects id separated with "," if list is 1
     */
    function getProjectsAuthorizedForUser($user, $mode=0, $list=0, $socid=0, $filter='')
    {
        $projects = array();
        $temp = array();

        $sql = "SELECT ".(($mode == 0 || $mode == 1) ? "DISTINCT " : "")."p.rowid, p.ref";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet as p";
        if ($mode == 0 || $mode == 1)
        {
            $sql.= ", " . MAIN_DB_PREFIX . "element_contact as ec";
        }
        $sql.= " WHERE p.entity IN (".getEntity('project').")";
        // Internal users must see project he is contact to even if project linked to a third party he can't see.
        //if ($socid || ! $user->rights->societe->client->voir)	$sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
        if ($socid > 0) $sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = " . $socid . ")";

        // Get id of types of contacts for projects (This list never contains a lot of elements)
        $listofprojectcontacttype=array();
        $sql2 = "SELECT ctc.rowid, ctc.code FROM ".MAIN_DB_PREFIX."c_type_contact as ctc";
        $sql2.= " WHERE ctc.element = '" . $this->db->escape($this->element) . "'";
        $sql2.= " AND ctc.source = 'internal'";
        $resql = $this->db->query($sql2);
        if ($resql)
        {
            while($obj = $this->db->fetch_object($resql))
            {
                $listofprojectcontacttype[$obj->rowid]=$obj->code;
            }
        }
        else dol_print_error($this->db);
        if (count($listofprojectcontacttype) == 0) $listofprojectcontacttype[0]='0';    // To avoid syntax error if not found

        if ($mode == 0)
        {
            $sql.= " AND ec.element_id = p.rowid";
            $sql.= " AND ( p.public = 1";
            $sql.= " OR ( ec.fk_c_type_contact IN (".join(',', array_keys($listofprojectcontacttype)).")";
            $sql.= " AND ec.fk_socpeople = ".$user->id.")";
            $sql.= " )";
        }
        if ($mode == 1)
        {
            $sql.= " AND ec.element_id = p.rowid";
            $sql.= " AND (";
            $sql.= "  ( ec.fk_c_type_contact IN (".join(',', array_keys($listofprojectcontacttype)).")";
            $sql.= " AND ec.fk_socpeople = ".$user->id.")";
            $sql.= " )";
        }
        if ($mode == 2)
        {
            // No filter. Use this if user has permission to see all project
        }

	$sql.= $filter;
        //print $sql;

        $resql = $this->db->query($sql);
        if ($resql)
        {
            $num = $this->db->num_rows($resql);
            $i = 0;
            while ($i < $num)
            {
                $row = $this->db->fetch_row($resql);
                $projects[$row[0]] = $row[1];
                $temp[] = $row[0];
                $i++;
            }

            $this->db->free($resql);

            if ($list)
            {
                if (empty($temp)) return '0';
                $result = implode(',', $temp);
                return $result;
            }
        }
        else
        {
            dol_print_error($this->db);
        }

        return $projects;
    }

     /**
      * Load an object from its id and create a new one in database
	  *
	  *	@param	int		$fromid     	Id of object to clone
	  *	@param	bool	$clone_contact	Clone contact of project
	  *	@param	bool	$clone_task		Clone task of project
	  *	@param	bool	$clone_project_file		Clone file of project
	  *	@param	bool	$clone_task_file		Clone file of task (if task are copied)
      *	@param	bool	$clone_note		Clone note of project
      * @param	bool	$move_date		Move task date on clone
      *	@param	integer	$notrigger		No trigger flag
      * @param  int     $newthirdpartyid  New thirdparty id
	  * @return	int						New id of clone
	  */
	function createFromClone($fromid,$clone_contact=false,$clone_task=true,$clone_project_file=false,$clone_task_file=false,$clone_note=true,$move_date=true,$notrigger=0,$newthirdpartyid=0)
	{
		global $user,$langs,$conf;

		$error=0;

		dol_syslog("createFromClone clone_contact=".$clone_contact." clone_task=".$clone_task." clone_project_file=".$clone_project_file." clone_note=".$clone_note." move_date=".$move_date,LOG_DEBUG);

		$now = dol_mktime(0,0,0,idate('m',dol_now()),idate('d',dol_now()),idate('Y',dol_now()));

		$clone_project=new Project($this->db);

		$clone_project->context['createfromclone']='createfromclone';

		$this->db->begin();

		// Load source object
		$clone_project->fetch($fromid);
		$clone_project->fetch_optionals();
		if ($newthirdpartyid > 0) $clone_project->socid = $newthirdpartyid;
		$clone_project->fetch_thirdparty();

		$orign_dt_start=$clone_project->date_start;
		$orign_project_ref=$clone_project->ref;

		$clone_project->id=0;
		if ($move_date) {
	        $clone_project->date_start = $now;
	        if (!(empty($clone_project->date_end)))
	        {
	        	$clone_project->date_end = $clone_project->date_end + ($now - $orign_dt_start);
	        }
		}

        $clone_project->datec = $now;

        if (! $clone_note)
        {
        	    $clone_project->note_private='';
    			$clone_project->note_public='';
        }

		//Generate next ref
		$defaultref='';
    	$obj = empty($conf->global->PROJECT_ADDON)?'mod_project_simple':$conf->global->PROJECT_ADDON;
    	// Search template files
    	$file=''; $classname=''; $filefound=0;
    	$dirmodels=array_merge(array('/'),(array) $conf->modules_parts['models']);
    	foreach($dirmodels as $reldir)
    	{
    	    $file=dol_buildpath($reldir."core/modules/project/".$obj.'.php',0);
    	    if (file_exists($file))
    	    {
    	        $filefound=1;
    	        dol_include_once($reldir."core/modules/project/".$obj.'.php');
            	$modProject = new $obj;
            	$defaultref = $modProject->getNextValue(is_object($clone_project->thirdparty)?$clone_project->thirdparty:null, $clone_project);
            	break;
    	    }
    	}
    	if (is_numeric($defaultref) && $defaultref <= 0) $defaultref='';

		$clone_project->ref=$defaultref;
		$clone_project->title=$langs->trans("CopyOf").' '.$clone_project->title;

		// Create clone
		$result=$clone_project->create($user,$notrigger);

		// Other options
		if ($result < 0)
		{
			$this->error.=$clone_project->error;
			$error++;
		}

		if (! $error)
		{
			//Get the new project id
			$clone_project_id=$clone_project->id;

			//Note Update
			if (!$clone_note)
       		{
        	    $clone_project->note_private='';
    			$clone_project->note_public='';
        	}
        	else
        	{
        		$this->db->begin();
				$res=$clone_project->update_note(dol_html_entity_decode($clone_project->note_public, ENT_QUOTES),'_public');
				if ($res < 0)
				{
					$this->error.=$clone_project->error;
					$error++;
					$this->db->rollback();
				}
				else
				{
					$this->db->commit();
				}

				$this->db->begin();
				$res=$clone_project->update_note(dol_html_entity_decode($clone_project->note_private, ENT_QUOTES), '_private');
				if ($res < 0)
				{
					$this->error.=$clone_project->error;
					$error++;
					$this->db->rollback();
				}
				else
				{
					$this->db->commit();
				}
        	}

			//Duplicate contact
			if ($clone_contact)
			{
				$origin_project = new Project($this->db);
				$origin_project->fetch($fromid);

				foreach(array('internal','external') as $source)
				{
					$tab = $origin_project->liste_contact(-1,$source);

					foreach ($tab as $contacttoadd)
					{
						$clone_project->add_contact($contacttoadd['id'], $contacttoadd['code'], $contacttoadd['source'],$notrigger);
						if ($clone_project->error == 'DB_ERROR_RECORD_ALREADY_EXISTS')
						{
							$langs->load("errors");
							$this->error.=$langs->trans("ErrorThisContactIsAlreadyDefinedAsThisType");
							$error++;
						}
						else
						{
							if ($clone_project->error!='')
							{
								$this->error.=$clone_project->error;
								$error++;
							}
						}
					}
				}
			}

			//Duplicate file
			if ($clone_project_file)
			{
				require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

				$clone_project_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($defaultref);
				$ori_project_dir = $conf->projet->dir_output . "/" . dol_sanitizeFileName($orign_project_ref);

				if (dol_mkdir($clone_project_dir) >= 0)
				{
					$filearray=dol_dir_list($ori_project_dir,"files",0,'','(\.meta|_preview.*\.png)$','',SORT_ASC,1);
					foreach($filearray as $key => $file)
					{
						$rescopy = dol_copy($ori_project_dir . '/' . $file['name'], $clone_project_dir . '/' . $file['name'],0,1);
						if (is_numeric($rescopy) && $rescopy < 0)
						{
							$this->error.=$langs->trans("ErrorFailToCopyFile",$ori_project_dir . '/' . $file['name'],$clone_project_dir . '/' . $file['name']);
							$error++;
						}
					}
				}
				else
				{
					$this->error.=$langs->trans('ErrorInternalErrorDetected').':dol_mkdir';
					$error++;
				}
			}

			//Duplicate task
			if ($clone_task)
			{
				require_once DOL_DOCUMENT_ROOT . '/projet/class/task.class.php';

				$taskstatic = new Task($this->db);

				// Security check
				$socid=0;
				if ($user->societe_id > 0) $socid = $user->societe_id;

				$tasksarray=$taskstatic->getTasksArray(0, 0, $fromid, $socid, 0);

				$tab_conv_child_parent=array();

				// Loop on each task, to clone it
			    foreach ($tasksarray as $tasktoclone)
			    {
					$result_clone = $taskstatic->createFromClone($tasktoclone->id,$clone_project_id,$tasktoclone->fk_parent,$move_date,true,false,$clone_task_file,true,false);
					if ($result_clone <= 0)
				    {
				    	$this->error.=$result_clone->error;
						$error++;
				    }
				    else
				    {
				    	$new_task_id=$result_clone;
				    	$taskstatic->fetch($tasktoclone->id);

				    	//manage new parent clone task id
				    	// if the current task has child we store the original task id and the equivalent clone task id
						if (($taskstatic->hasChildren()) && !array_key_exists($tasktoclone->id,$tab_conv_child_parent))
						{
							$tab_conv_child_parent[$tasktoclone->id] =  $new_task_id;
						}
				    }

			    }

			    //Parse all clone node to be sure to update new parent
			    $tasksarray=$taskstatic->getTasksArray(0, 0, $clone_project_id, $socid, 0);
			    foreach ($tasksarray as $task_cloned)
			    {
			    	$taskstatic->fetch($task_cloned->id);
			    	if ($taskstatic->fk_task_parent!=0)
			    	{
			    		$taskstatic->fk_task_parent=$tab_conv_child_parent[$taskstatic->fk_task_parent];
			    	}
			    	$res=$taskstatic->update($user,$notrigger);
			    	if ($result_clone <= 0)
				    {
				    	$this->error.=$taskstatic->error;
						$error++;
				    }
			    }
			}
		}

		unset($clone_project->context['createfromclone']);

		if (! $error)
		{
			$this->db->commit();
			return $clone_project_id;
		}
		else
		{
			$this->db->rollback();
			dol_syslog(get_class($this)."::createFromClone nbError: ".$error." error : " . $this->error, LOG_ERR);
			return -1;
		}
	}


	 /**
	  *    Shift project task date from current date to delta
	  *
	  *    @param	timestamp		$old_project_dt_start	old project start date
	  *    @return	int				1 if OK or < 0 if KO
	  */
	function shiftTaskDate($old_project_dt_start)
	{
		global $user,$langs,$conf;

		$error=0;

		$taskstatic = new Task($this->db);

		// Security check
		$socid=0;
		if ($user->societe_id > 0) $socid = $user->societe_id;

		$tasksarray=$taskstatic->getTasksArray(0, 0, $this->id, $socid, 0);

	    foreach ($tasksarray as $tasktoshiftdate)
	    {
	    	$to_update=false;
	    	// Fetch only if update of date will be made
	    	if ((!empty($tasktoshiftdate->date_start)) || (!empty($tasktoshiftdate->date_end)))
	    	{
	    		//dol_syslog(get_class($this)."::shiftTaskDate to_update", LOG_DEBUG);
	    		$to_update=true;
		    	$task = new Task($this->db);
		    	$result = $task->fetch($tasktoshiftdate->id);
		    	if (!$result)
		    	{
		    		$error++;
		    		$this->error.=$task->error;
		    	}
	    	}
			//print "$this->date_start + $tasktoshiftdate->date_start - $old_project_dt_start";exit;

	    	//Calcultate new task start date with difference between old proj start date and origin task start date
	    	if (!empty($tasktoshiftdate->date_start))
	    	{
				$task->date_start			= $this->date_start + ($tasktoshiftdate->date_start - $old_project_dt_start);
	    	}

	    	//Calcultate new task end date with difference between origin proj end date and origin task end date
	    	if (!empty($tasktoshiftdate->date_end))
	    	{
				$task->date_end		    	= $this->date_start + ($tasktoshiftdate->date_end - $old_project_dt_start);
	    	}

			if ($to_update)
			{
		    	$result = $task->update($user);
		    	if (!$result)
		    	{
		    		$error++;
		    		$this->error.=$task->error;
		    	}
			}
	    }
	    if ($error!=0)
	    {
	    	return -1;
	    }
	    return $result;
	}


	 /**
	  *    Associate element to a project
	  *
	  *    @param	string	$tableName			Table of the element to update
	  *    @param	int		$elementSelectId	Key-rowid of the line of the element to update
	  *    @return	int							1 if OK or < 0 if KO
	  */
	function update_element($tableName, $elementSelectId)
	{
		$sql="UPDATE ".MAIN_DB_PREFIX.$tableName;

		if ($tableName == "actioncomm")
		{
			$sql.= " SET fk_project=".$this->id;
			$sql.= " WHERE id=".$elementSelectId;
		}
		else
		{
			$sql.= " SET fk_projet=".$this->id;
			$sql.= " WHERE rowid=".$elementSelectId;
		}

		dol_syslog(get_class($this)."::update_element", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (!$resql) {
			$this->error=$this->db->lasterror();
			return -1;
		}else {
			return 1;
		}

	}

	/**
	 *    Associate element to a project
	 *
	 *    @param	string	$tableName			Table of the element to update
	 *    @param	int		$elementSelectId	Key-rowid of the line of the element to update
	 *    @return	int							1 if OK or < 0 if KO
	 */
	function remove_element($tableName, $elementSelectId)
	{
		$sql="UPDATE ".MAIN_DB_PREFIX.$tableName;

		if ($TableName=="actioncomm")
		{
			$sql.= " SET fk_project=NULL";
			$sql.= " WHERE id=".$elementSelectId;
		}
		else
		{
			$sql.= " SET fk_projet=NULL";
			$sql.= " WHERE rowid=".$elementSelectId;
		}

		dol_syslog(get_class($this)."::remove_element", LOG_DEBUG);
		$resql=$this->db->query($sql);
		if (!$resql) {
			$this->error=$this->db->lasterror();
			return -1;
		}else {
			return 1;
		}

	}

	/**
	 *  Create an intervention document on disk using template defined into PROJECT_ADDON_PDF
	 *
	 *  @param	string		$modele			Force template to use ('' by default)
	 *  @param	Translate	$outputlangs	Objet lang to use for translation
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *  @return int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf,$langs;

		$langs->load("projects");

		if (! dol_strlen($modele)) {

			$modele = 'baleine';

			if ($this->modelpdf) {
				$modele = $this->modelpdf;
			} elseif (! empty($conf->global->PROJECT_ADDON_PDF)) {
				$modele = $conf->global->PROJECT_ADDON_PDF;
			}
		}

		$modelpath = "core/modules/project/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}


	/**
	 * Load time spent into this->weekWorkLoad and this->weekWorkLoadPerTask for all day of a week of project.
	 * Note: array weekWorkLoad and weekWorkLoadPerTask are reset and filled at each call.
	 *
	 * @param 	int		$datestart		First day of week (use dol_get_first_day to find this date)
	 * @param 	int		$taskid			Filter on a task id
	 * @param 	int		$userid			Time spent by a particular user
	 * @return 	int						<0 if OK, >0 if KO
	 */
	public function loadTimeSpent($datestart, $taskid=0, $userid=0)
    {
        $error=0;

        $this->weekWorkLoad=array();
        $this->weekWorkLoadPerTask=array();

        if (empty($datestart)) dol_print_error('','Error datestart parameter is empty');

        $sql = "SELECT ptt.rowid as taskid, ptt.task_duration, ptt.task_date, ptt.task_datehour, ptt.fk_task";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt, ".MAIN_DB_PREFIX."projet_task as pt";
        $sql.= " WHERE ptt.fk_task = pt.rowid";
        $sql.= " AND pt.fk_projet = ".$this->id;
        $sql.= " AND (ptt.task_date >= '".$this->db->idate($datestart)."' ";
        $sql.= " AND ptt.task_date <= '".$this->db->idate(dol_time_plus_duree($datestart, 1, 'w') - 1)."')";
        if ($task_id) $sql.= " AND ptt.fk_task=".$taskid;
        if (is_numeric($userid)) $sql.= " AND ptt.fk_user=".$userid;

        //print $sql;
        $resql=$this->db->query($sql);
        if ($resql)
        {
				$daylareadyfound=array();

                $num = $this->db->num_rows($resql);
                $i = 0;
                // Loop on each record found, so each couple (project id, task id)
                while ($i < $num)
                {
                        $obj=$this->db->fetch_object($resql);
                        $day=$this->db->jdate($obj->task_date);		// task_date is date without hours
                        if (empty($daylareadyfound[$day]))
                        {
                        	$this->weekWorkLoad[$day] = $obj->task_duration;
                        	$this->weekWorkLoadPerTask[$day][$obj->fk_task] = $obj->task_duration;
                        }
                        else
                        {
                        	$this->weekWorkLoad[$day] += $obj->task_duration;
                        	$this->weekWorkLoadPerTask[$day][$obj->fk_task] += $obj->task_duration;
                        }
                        $daylareadyfound[$day]=1;
                        $i++;
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
     * Load indicators for dashboard (this->nbtodo and this->nbtodolate)
     *
     * @param	User	$user   Objet user
     * @return WorkboardResponse|int <0 if KO, WorkboardResponse if OK
     */
    function load_board($user)
    {
        global $conf, $langs;

        // For external user, no check is done on company because readability is managed by public status of project and assignement.
        //$socid=$user->societe_id;

        if (! $user->rights->projet->all->lire) $projectsListId = $this->getProjectsAuthorizedForUser($user,0,1,$socid);

        $sql = "SELECT p.rowid, p.fk_statut as status, p.fk_opp_status, p.datee as datee";
        $sql.= " FROM (".MAIN_DB_PREFIX."projet as p";
        $sql.= ")";
        $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."societe as s on p.fk_soc = s.rowid";
        // For external user, no check is done on company permission because readability is managed by public status of project and assignement.
        //if (! $user->rights->societe->client->voir && ! $socid) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."societe_commerciaux as sc ON sc.fk_soc = s.rowid";
        $sql.= " WHERE p.fk_statut = 1";
        $sql.= " AND p.entity IN (".getEntity('project').')';
        if (! $user->rights->projet->all->lire) $sql.= " AND p.rowid IN (".$projectsListId.")";
        // No need to check company, as filtering of projects must be done by getProjectsAuthorizedForUser
        //if ($socid || ! $user->rights->societe->client->voir)	$sql.= "  AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
        // For external user, no check is done on company permission because readability is managed by public status of project and assignement.
        //if (! $user->rights->societe->client->voir && ! $socid) $sql.= " AND ((s.rowid = sc.fk_soc AND sc.fk_user = " .$user->id.") OR (s.rowid IS NULL))";

        //print $sql;
        $resql=$this->db->query($sql);
        if ($resql)
        {
            $project_static = new Project($this->db);

            $response = new WorkboardResponse();
            $response->warning_delay = $conf->projet->warning_delay/60/60/24;
            $response->label = $langs->trans("OpenedProjects");
            if ($user->rights->projet->all->lire) $response->url = DOL_URL_ROOT.'/projet/list.php?search_status=1&mainmenu=project';
            else $response->url = DOL_URL_ROOT.'/projet/list.php?search_project_user=-1&search_status=1&mainmenu=project';
            $response->img = img_object('',"projectpub");

            // This assignment in condition is not a bug. It allows walking the results.
            while ($obj=$this->db->fetch_object($resql))
            {
                $response->nbtodo++;

                $project_static->statut = $obj->status;
                $project_static->opp_status = $obj->opp_status;
                $project_static->datee = $this->db->jdate($obj->datee);

                if ($project_static->hasDelay()) {
                    $response->nbtodolate++;
                }
            }

            return $response;
        }
        else
        {
            $this->error=$this->db->error();
            return -1;
        }
    }


	/**
	 * Function used to replace a thirdparty id with another one.
	 *
	 * @param DoliDB $db Database handler
	 * @param int $origin_id Old thirdparty id
	 * @param int $dest_id New thirdparty id
	 * @return bool
	 */
	public static function replaceThirdparty(DoliDB $db, $origin_id, $dest_id)
	{
		$tables = array(
			'projet'
		);

		return CommonObject::commonReplaceThirdparty($db, $origin_id, $dest_id, $tables);
	}


	/**
	 *      Charge indicateurs this->nb pour le tableau de bord
	 *
	 *      @return     int         <0 if KO, >0 if OK
	 */
	function load_state_board()
	{
	    global $user;

	    $this->nb=array();

	    $sql = "SELECT count(p.rowid) as nb";
	    $sql.= " FROM ".MAIN_DB_PREFIX."projet as p";
	    $sql.= " WHERE";
	    $sql.= " p.entity IN (".getEntity('project').")";
		if (! $user->rights->projet->all->lire)
		{
			$projectsListId = $this->getProjectsAuthorizedForUser($user,0,1);
			$sql .= "AND p.rowid IN (".$projectsListId.")";
		}

	    $resql=$this->db->query($sql);
	    if ($resql)
	    {
	        while ($obj=$this->db->fetch_object($resql))
	        {
	            $this->nb["projects"]=$obj->nb;
	        }
	        $this->db->free($resql);
	        return 1;
	    }
	    else
	    {
	        dol_print_error($this->db);
	        $this->error=$this->db->error();
	        return -1;
	    }
	}


	/**
	 * Is the project delayed?
	 *
	 * @return bool
	 */
	public function hasDelay()
	{
	    global $conf;

        if (! ($this->statut == 1)) return false;
        if (! $this->datee && ! $this->date_end) return false;

        $now = dol_now();

        return ($this->datee ? $this->datee : $this->date_end) < ($now - $conf->projet->warning_delay);
	}


	/**
	 *	Charge les informations d'ordre info dans l'objet commande
	 *
	 *	@param  int		$id       Id of order
	 *	@return	void
	 */
	function info($id)
	{
	    $sql = 'SELECT c.rowid, datec as datec, tms as datem,';
	    $sql.= ' date_close as datecloture,';
	    $sql.= ' fk_user_creat as fk_user_author, fk_user_close as fk_use_cloture';
	    $sql.= ' FROM '.MAIN_DB_PREFIX.'projet as c';
	    $sql.= ' WHERE c.rowid = '.$id;
	    $result=$this->db->query($sql);
	    if ($result)
	    {
	        if ($this->db->num_rows($result))
	        {
	            $obj = $this->db->fetch_object($result);
	            $this->id = $obj->rowid;
	            if ($obj->fk_user_author)
	            {
	                $cuser = new User($this->db);
	                $cuser->fetch($obj->fk_user_author);
	                $this->user_creation   = $cuser;
	            }

	            if ($obj->fk_user_cloture)
	            {
	                $cluser = new User($this->db);
	                $cluser->fetch($obj->fk_user_cloture);
	                $this->user_cloture   = $cluser;
	            }

	            $this->date_creation     = $this->db->jdate($obj->datec);
	            $this->date_modification = $this->db->jdate($obj->datem);
	            $this->date_cloture      = $this->db->jdate($obj->datecloture);
	        }

	        $this->db->free($result);

	    }
	    else
	    {
	        dol_print_error($this->db);
	    }
	}

	/**
	 * Sets object to supplied categories.
	 *
	 * Deletes object from existing categories not supplied.
	 * Adds it to non existing supplied categories.
	 * Existing categories are left untouch.
	 *
	 * @param int[]|int $categories Category or categories IDs
	 */
	public function setCategories($categories)
	{
		// Decode type
		$type_id = Categorie::TYPE_PROJECT;
		$type_text = 'project';


		// Handle single category
		if (!is_array($categories)) {
			$categories = array($categories);
		}

		// Get current categories
		require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';
		$c = new Categorie($this->db);
		$existing = $c->containing($this->id, $type_id, 'id');

		// Diff
		if (is_array($existing)) {
			$to_del = array_diff($existing, $categories);
			$to_add = array_diff($categories, $existing);
		} else {
			$to_del = array(); // Nothing to delete
			$to_add = $categories;
		}

		// Process
		foreach ($to_del as $del) {
			if ($c->fetch($del) > 0) {
				$result=$c->del_type($this, $type_text);
				if ($result<0) {
					$this->errors=$c->errors;
					$this->error=$c->error;
					return -1;
				}
			}
		}
		foreach ($to_add as $add) {
			if ($c->fetch($add) > 0) {
				$result=$c->add_type($this, $type_text);
				if ($result<0) {
					$this->errors=$c->errors;
					$this->error=$c->error;
					return -1;
				}
			}
		}

		return 1;
	}


	/**
	 * 	Create an array of tasks of current project
	 *
	 *  @param  User   $user       Object user we want project allowed to
	 * 	@return int		           >0 if OK, <0 if KO
	 */
	function getLinesArray($user)
	{
	    require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
	    $taskstatic = new Task($this->db);

	    $this->lines = $taskstatic->getTasksArray(0, $user, $this->id, 0, 0);
	}

}

