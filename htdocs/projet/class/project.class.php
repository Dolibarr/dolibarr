<?php
/* Copyright (C) 2002-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2010 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2013	   Florian Henry        <florian.henry@open-concept.pro>
 * Copyright (C) 2014      Marcos García        <marcosgdf@gmail.com>
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
    protected $ismultientitymanaged = 1;  // 0=No test on entity, 1=Test with field entity, 2=Test with link by societe

    /**
     * {@inheritdoc}
     */
    protected $table_ref_field = 'ref';

    var $id;
    var $ref;
    var $description;
    var $title;
    var $date_start;
    var $date_end;
    var $date_close;
    var $socid;
    var $user_author_id;    //!< Id of project creator. Not defined if shared project.
	var $user_close_id;
    var $public;      //!< Tell if this is a public or private project
    var $note_private;
    var $note_public;
    var $budget_amount;

    var $statuts_short;
    var $statuts;			// 0=draft, 1=opened, 2=closed

    var $oldcopy;

    var $weekWorkLoad;			// Used to store workload details of a projet
    var $weekWorkLoadPerTask;	// Used to store workload details of tasks of a projet


    /**
     *  Constructor
     *
     *  @param      DoliDB		$db      Database handler
     */
    function __construct($db)
    {
        $this->db = $db;

        $this->statuts_short = array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');
        $this->statuts = array(0 => 'Draft', 1 => 'Opened', 2 => 'Closed');
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

        $this->db->begin();

        $sql = "INSERT INTO " . MAIN_DB_PREFIX . "projet (";
        $sql.= "ref";
        $sql.= ", title";
        $sql.= ", description";
        $sql.= ", fk_soc";
        $sql.= ", fk_user_creat";
        $sql.= ", fk_statut";
        $sql.= ", public";
        $sql.= ", datec";
        $sql.= ", dateo";
        $sql.= ", datee";
        $sql.= ", budget_amount";
        $sql.= ", entity";
        $sql.= ") VALUES (";
        $sql.= "'" . $this->db->escape($this->ref) . "'";
        $sql.= ", '" . $this->db->escape($this->title) . "'";
        $sql.= ", '" . $this->db->escape($this->description) . "'";
        $sql.= ", " . ($this->socid > 0 ? $this->socid : "null");
        $sql.= ", " . $user->id;
        $sql.= ", 0";
        $sql.= ", " . ($this->public ? 1 : 0);
        $sql.= ", '".$this->db->idate($now)."'";
        $sql.= ", " . ($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : 'null');
        $sql.= ", " . ($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : 'null');
        $sql.= ", " . ($this->budget_amount != ''?price2num($this->budget_amount):'null');
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
        if (!$error) {
        	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
        	{
        		$result=$this->insertExtraFields();
        		if ($result < 0)
        		{
        			$error++;
        		}
        	}
        }

        if (!$error && !empty($conf->global->MAIN_DISABLEDRAFTSTATUS))
        {
            $res = $this->setValid($user);
            if ($res < 0) $error++;
        }

        if (!$error)
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
     * @return int
     */
    function update($user, $notrigger=0)
    {
        global $langs, $conf;

		$error=0;

        // Clean parameters
        $this->title = trim($this->title);
        $this->description = trim($this->description);

        if (dol_strlen(trim($this->ref)) > 0)
        {
            $this->db->begin();

            $sql = "UPDATE " . MAIN_DB_PREFIX . "projet SET";
            $sql.= " ref='" . $this->db->escape($this->ref) . "'";
            $sql.= ", title = '" . $this->db->escape($this->title) . "'";
            $sql.= ", description = '" . $this->db->escape($this->description) . "'";
            $sql.= ", fk_soc = " . ($this->socid > 0 ? $this->socid : "null");
            $sql.= ", fk_statut = " . $this->statut;
            $sql.= ", public = " . ($this->public ? 1 : 0);
            $sql.= ", datec=" . ($this->date_c != '' ? "'".$this->db->idate($this->date_c)."'" : 'null');
            $sql.= ", dateo=" . ($this->date_start != '' ? "'".$this->db->idate($this->date_start)."'" : 'null');
            $sql.= ", datee=" . ($this->date_end != '' ? "'".$this->db->idate($this->date_end)."'" : 'null');
            $sql.= ", date_close=" . ($this->date_close != '' ? "'".$this->db->idate($this->date_close)."'" : 'null');
            $sql.= ", fk_user_close=" . ($this->fk_user_close > 0 ? $this->fk_user_close : "null");
            $sql.= ", budget_amount = " . ($this->budget_amount > 0 ? $this->budget_amount : "null");
            $sql.= " WHERE rowid = " . $this->id;

            dol_syslog(get_class($this)."::Update", LOG_DEBUG);
            $resql=$this->db->query($sql);
            if ($resql)
            {
                if (!$notrigger)
                {
                    // Call trigger
                    $result=$this->call_trigger('PROJECT_MODIFY',$user);
                    if ($result < 0) { $error++; }
                    // End call triggers
                }

                //Update extrafield
                if (!$error) {
                	if (empty($conf->global->MAIN_EXTRAFIELDS_DISABLED)) // For avoid conflicts if trigger used
                	{
                		$result=$this->insertExtraFields();
                		if ($result < 0)
                		{
                			$error++;
                		}
                	}
                }

                if (! $error && (is_object($this->oldcopy) && $this->oldcopy->ref != $this->ref))
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
                				$this->error='ErrorFailToMoveDir';
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
                dol_syslog(get_class($this)."::Update error -2 " . $this->error, LOG_ERR);
                $result = -2;
            }
        }
        else
        {
            dol_syslog(get_class($this)."::Update ref null");
            $result = -1;
        }

        return $result;
    }

    /**
     * 	Get object and lines from database
     *
     * 	@param      int		$id       	Id of object to load
     * 	@param		string	$ref		Ref of project
     * 	@return     int      		   	>0 if OK, 0 if not found, <0 if KO
     */
    function fetch($id, $ref='')
    {
        if (empty($id) && empty($ref)) return -1;

        $sql = "SELECT rowid, ref, title, description, public, datec, budget_amount,";
        $sql.= " tms, dateo, datee, date_close, fk_soc, fk_user_creat, fk_user_close, fk_statut, note_private, note_public, model_pdf";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet";
        if (! empty($id))
        {
        	$sql.= " WHERE rowid=".$id;
        }
        else if (! empty($ref))
        {
        	$sql.= " WHERE ref='".$this->db->escape($ref)."'";
        	$sql.= " AND entity IN (".getEntity('project',1).")";
        }

        dol_syslog(get_class($this)."::fetch", LOG_DEBUG);
        $resql = $this->db->query($sql);
        if ($resql)
        {
            if ($this->db->num_rows($resql))
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
                $this->user_close_id = $obj->fk_user_close;
                $this->public = $obj->public;
                $this->statut = $obj->fk_statut;
                $this->budget_amount	= $obj->budget_amount;
                $this->modelpdf	= $obj->model_pdf;

                $this->db->free($resql);

                return 1;
            }
            else
            {
                return 0;
            }
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
     * 	Return list of elements for type linked to project
     *
     * 	@param		string		$type			'propal','order','invoice','order_supplier','invoice_supplier'
     * 	@param		string		$tablename		name of table associated of the type
     * 	@param		string		$datefieldname	name of table associated of the type
     *  @param		string		$dates			Start date (at 00:00:00)
     *  @param		string		$datee			End date (at 23:00:00)
     * 	@return		mixed						Array list of object ids linked to project, < 0 or string if error
     */
    function get_element_list($type, $tablename, $datefieldname='', $dates='', $datee='')
    {
        $elements = array();

        if ($type == 'agenda')
        {
            $sql = "SELECT id as rowid FROM " . MAIN_DB_PREFIX . "actioncomm WHERE fk_project=" . $this->id;
        }
        else
		{
            $sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . $tablename." WHERE fk_projet=" . $this->id;
		}
    	if ($type == 'expensereport')
		{
            $sql = "SELECT ed.rowid FROM " . MAIN_DB_PREFIX . "expensereport as e, " . MAIN_DB_PREFIX . "expensereport_det as ed WHERE e.rowid = ed.fk_expensereport AND ed.fk_projet=" . $this->id;
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

                    $elements[$i] = $obj->rowid;

                    $i++;
                }
                $this->db->free($result);

                /* Return array */
                return $elements;
            }
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
        		'facture'=>'fk_projet','propal'=>'fk_projet','commande'=>'fk_projet','facture_fourn'=>'fk_projet','commande_fournisseur'=>'fk_projet',
        		'expensereport_det'=>'fk_projet','contrat'=>'fk_projet','fichinter'=>'fk_projet','don'=>'fk_project'
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

        // Delete tasks
        if (! $error)
        {
	        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet_task_time";
	        $sql.= " WHERE fk_task IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "projet_task WHERE fk_projet=" . $this->id . ")";

	        $resql = $this->db->query($sql);
	        if (!$resql)
	        {
	        	$this->errors[] = $this->db->lasterror();
	        	$error++;
	        }
        }

        if (! $error)
        {
	        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet_task_extrafields";
	        $sql.= " WHERE fk_object IN (SELECT rowid FROM " . MAIN_DB_PREFIX . "projet_task WHERE fk_projet=" . $this->id . ")";

	        $resql = $this->db->query($sql);
	        if (!$resql)
	        {
	        	$this->errors[] = $this->db->lasterror();
	        	$error++;
	        }
        }

        if (! $error)
        {
	        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet_task";
	        $sql.= " WHERE fk_projet=" . $this->id;

	        $resql = $this->db->query($sql);
	        if (!$resql)
	        {
	        	$this->errors[] = $this->db->lasterror();
	        	$error++;
	        }
        }

        // Delete project
        if (! $error)
        {
	        $sql = "DELETE FROM " . MAIN_DB_PREFIX . "projet";
	        $sql.= " WHERE rowid=" . $this->id;

	        $resql = $this->db->query($sql);
	        if (!$resql)
	        {
	        	$this->errors[] = $this->db->lasterror();
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
     * 		Validate a project
     *
     * 		@param		User	$user		User that validate
     * 		@return		int					<0 if KO, >0 if OK
     */
    function setValid($user)
    {
        global $langs, $conf;

		$error=0;

        if ($this->statut != 1)
        {
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
                $result=$this->call_trigger('PROJECT_VALIDATE',$user);
                if ($result < 0) { $error++; }
                // End call triggers

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
     * 		@return		int					<0 if KO, >0 if OK
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
            return $langs->trans($this->statuts[$statut]);
        }
        if ($mode == 1)
        {
            return $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 2)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 3)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0');
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4');
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6');
        }
        if ($mode == 4)
        {
            if ($statut == 0)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut0') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 1)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut4') . ' ' . $langs->trans($this->statuts_short[$statut]);
            if ($statut == 2)
                return img_picto($langs->trans($this->statuts_short[$statut]), 'statut6') . ' ' . $langs->trans($this->statuts_short[$statut]);
        }
        if ($mode == 5)
        {
            if ($statut == 0)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut0');
            if ($statut == 1)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut4');
            if ($statut == 2)
                return $langs->trans($this->statuts_short[$statut]) . ' ' . img_picto($langs->trans($this->statuts_short[$statut]), 'statut6');
        }
    }

    /**
     * 	Return clicable name (with picto eventually)
     *
     * 	@param	int		$withpicto		0=No picto, 1=Include picto into link, 2=Only picto
     * 	@param	string	$option			Variant ('', 'nolink')
     * 	@param	int		$addlabel		0=Default, 1=Add label into string, >1=Add first chars into string
     * 	@return	string					Chaine avec URL
     */
    function getNomUrl($withpicto=0, $option='', $addlabel=0)
    {
        global $langs;

        $result = '';
        $link = '';
        $linkend = '';
        $label = '<u>' . $langs->trans("ShowProject") . '</u>';
        if (! empty($this->ref))
            $label .= '<br><b>' . $langs->trans('Ref') . ':</b> ' . $this->ref;
        if (! empty($this->title))
            $label .= '<br><b>' . $langs->trans('Name') . ':</b> ' . $this->title;
        $linkclose = '" title="'.dol_escape_htmltag($label, 1).'" class="classfortooltip">';

        if ($option != 'nolink') {
            if (preg_match('/\.php$/',$option)) {
                $link = '<a href="' . dol_buildpath($option,1) . '?id=' . $this->id . $linkclose;
                $linkend = '</a>';
            } else {
                $link = '<a href="' . DOL_URL_ROOT . '/projet/card.php?id=' . $this->id . $linkclose;
                $linkend = '</a>';
            }
        }

        $picto = 'projectpub';
        if (!$this->public) $picto = 'project';


        if ($withpicto) $result.=($link . img_object($label, $picto, 'class="classfortooltip"') . $linkend);
        if ($withpicto && $withpicto != 2) $result.=' ';
        if ($withpicto != 2) $result.=$link . $this->ref . $linkend . (($addlabel && $this->title) ? ' - ' . dol_trunc($this->title, ($addlabel > 1 ? $addlabel : 0)) : '');
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
        $this->note_public = 'SPECIMEN';
        $this->budget_amount = 10000;

        /*
        $nbp = rand(1, 9);
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
     * @param 	int		$mode			0=All project I have permission on, 1=Projects affected to me only, 2=Will return list of all projects with no test on contacts
     * @param 	int		$list			0=Return array,1=Return string list
     * @param	int		$socid			0=No filter on third party, id of third party
     * @return 	array or string			Array of projects id, or string with projects id separated with ","
     */
    function getProjectsAuthorizedForUser($user, $mode=0, $list=0, $socid=0)
    {
        $projects = array();
        $temp = array();

        $sql = "SELECT ".(($mode == 0 || $mode == 1) ? "DISTINCT " : "")."p.rowid, p.ref";
        $sql.= " FROM " . MAIN_DB_PREFIX . "projet as p";
        if ($mode == 0 || $mode == 1)
        {
            $sql.= ", " . MAIN_DB_PREFIX . "element_contact as ec";
            $sql.= ", " . MAIN_DB_PREFIX . "c_type_contact as ctc";
        }
        $sql.= " WHERE p.entity IN (".getEntity('project',1).")";
        // Internal users must see project he is contact to even if project linked to a third party he can't see.
        //if ($socid || ! $user->rights->societe->client->voir)	$sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = ".$socid.")";
        if ($socid > 0) $sql.= " AND (p.fk_soc IS NULL OR p.fk_soc = 0 OR p.fk_soc = " . $socid . ")";

        if ($mode == 0)
        {
            $sql.= " AND ec.element_id = p.rowid";
            $sql.= " AND ( p.public = 1";
            $sql.= " OR ( ctc.rowid = ec.fk_c_type_contact";
            $sql.= " AND ctc.element = '" . $this->element . "'";
            $sql.= " AND ( (ctc.source = 'internal' AND ec.fk_socpeople = ".$user->id.")";
            $sql.= " )";
            $sql.= " ))";
        }
        if ($mode == 1)
        {
            $sql.= " AND ec.element_id = p.rowid";
            $sql.= " AND ctc.rowid = ec.fk_c_type_contact";
            $sql.= " AND ctc.element = '" . $this->element . "'";
            $sql.= " AND ( (ctc.source = 'internal' AND ec.fk_socpeople = ".$user->id.")";
            $sql.= " )";
        }
        if ($mode == 2)
        {
            // No filter. Use this if user has permission to see all project
        }
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
	  *	@param	bool	$clone_contact	clone contact of project
	  *	@param	bool	$clone_task		clone task of project
	  *	@param	bool	$clone_project_file		clone file of project
	  *	@param	bool	$clone_task_file		clone file of task (if task are copied)
      *	@param	bool	$clone_note		clone note of project
      * @param	bool	$move_date		move task date on clone
      *	@param	integer	$notrigger		no trigger flag
	  * @return	int						New id of clone
	  */
	function createFromClone($fromid,$clone_contact=false,$clone_task=true,$clone_project_file=false,$clone_task_file=false,$clone_note=true,$move_date=true,$notrigger=0)
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
    	if (! empty($conf->global->PROJECT_ADDON) && is_readable(DOL_DOCUMENT_ROOT ."/core/modules/project/".$conf->global->PROJECT_ADDON.".php"))
    	{

        	require_once DOL_DOCUMENT_ROOT ."/core/modules/project/".$conf->global->PROJECT_ADDON.'.php';
        	$modProject = new $obj;
        	$defaultref = $modProject->getNextValue(is_object($clone_project->thirdparty)?$clone_project->thirdparty->id:0,$clone_project);
    	}

    	if (is_numeric($defaultref) && $defaultref <= 0) $defaultref='';

		$clone_project->ref=$defaultref;

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
					$filearray=dol_dir_list($ori_project_dir,"files",0,'','(\.meta|_preview\.png)$','',SORT_ASC,1);
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

				//manage new parent clone task id
				$tab_conv_child_parent=array();

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

		if ($TableName=="actioncomm")
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
	 *  @param	string		$modele			force le modele a utiliser ('' par defaut)
	 *  @param	Translate	$outputlangs	objet lang a utiliser pour traduction
	 *  @param  int			$hidedetails    Hide details of lines
	 *  @param  int			$hidedesc       Hide description
	 *  @param  int			$hideref        Hide ref
	 *  @return int         				0 if KO, 1 if OK
	 */
	public function generateDocument($modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $conf,$langs;

		$langs->load("projects");

		// Positionne modele sur le nom du modele de projet a utiliser
		if (! dol_strlen($modele))
		{
			if (! empty($conf->global->PROJECT_ADDON_PDF))
			{
				$modele = $conf->global->PROJECT_ADDON_PDF;
			}
			else
			{
				$modele='baleine';
			}
		}

		$modelpath = "core/modules/project/doc/";

		return $this->commonGenerateDocument($modelpath, $modele, $outputlangs, $hidedetails, $hidedesc, $hideref);
	}


	/**
	 * Load time spent into this->weekWorkLoad and this->weekWorkLoadPerTask for all day of a week of project
	 *
	 * @param 	int		$datestart		First day of week (use dol_get_first_day to find this date)
	 * @param 	int		$taskid			Filter on a task id
	 * @param 	int		$userid			Time spent by a particular user
	 * @return 	int						<0 if OK, >0 if KO
	 */
	public function loadTimeSpent($datestart,$taskid=0,$userid=0)
    {
        $error=0;

        if (empty($datestart)) dol_print_error('','Error datestart parameter is empty');

        $sql = "SELECT ptt.rowid as taskid, ptt.task_duration, ptt.task_date, ptt.fk_task";
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task_time AS ptt, ".MAIN_DB_PREFIX."projet_task as pt";
        $sql.= " WHERE ptt.fk_task = pt.rowid";
        $sql.= " AND pt.fk_projet = ".$this->id;
        $sql.= " AND (ptt.task_date >= '".$this->db->idate($datestart)."' ";
        $sql.= " AND ptt.task_date <= '".$this->db->idate($datestart + (7 * 24 * 3600) - 1)."')";
        if ($task_id) $sql.= " AND ptt.fk_task=".$taskid;
        if (is_numeric($userid)) $sql.= " AND ptt.fk_user=".$userid;

        //print $sql;
        $resql=$this->db->query($sql);
        if ($resql)
        {

                $num = $this->db->num_rows($resql);
                $i = 0;
                // Loop on each record found, so each couple (project id, task id)
                 while ($i < $num)
                {
                        $obj=$this->db->fetch_object($resql);
                        $day=$this->db->jdate($obj->task_date);
                        $this->weekWorkLoad[$day] +=  $obj->task_duration;
                        $this->weekWorkLoadPerTask[$day][$obj->fk_task] += $obj->task_duration;
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

}

