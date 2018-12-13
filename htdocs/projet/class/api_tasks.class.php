<?php
/* Copyright (C) 2015   Jean-François Ferry     <jfefe@aternatik.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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

 use Luracast\Restler\RestException;

 require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
 require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

/**
 * API class for projects
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Tasks extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'ref',
        'label'
    );

    /**
     * @var Task $task {@type Task}
     */
    public $task;

    /**
     * Constructor
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->task = new Task($this->db);
    }

    /**
     * Get properties of a task object
     *
     * Return an array with task informations
     *
     * @param   int         $id                     ID of task
     * @param   int         $includetimespent       0=Return only task. 1=Include a summary of time spent, 2=Include details of time spent lines (2 is no implemented yet)
     * @return 	array|mixed                         data without useless information
	 *
     * @throws 	RestException
     */
    function get($id, $includetimespent=0)
    {
		if(! DolibarrApiAccess::$user->rights->projet->lire) {
			throw new RestException(401);
		}

        $result = $this->task->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Task not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('task',$this->task->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

		if ($includetimespent == 1)
		{
		    $timespent = $this->task->getSummaryOfTimeSpent(0);
		}
		if ($includetimespent == 1)
		{
		    // TODO
		    // Add class for timespent records and loop and fill $line->lines with records of timespent
		}

		return $this->_cleanObjectDatas($this->task);
    }



    /**
     * List tasks
     *
     * Get a list of tasks
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int		       $limit		        Limit for list
     * @param int		       $page		        Page number
     * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of project objects
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $sqlfilters = '')
    {
        global $db, $conf;

        $obj_ret = array();

        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."projet_task as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE t.entity IN ('.getEntity('project').')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($socids) $sql.= " AND t.fk_soc IN (".$socids.")";
        if ($search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
        if ($search_sale > 0)
        {
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }

        $sql.= $db->order($sortfield, $sortorder);
        if ($limit)	{
            if ($page < 0)
            {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql.= $db->plimit($limit + 1, $offset);
        }

        dol_syslog("API Rest request");
        $result = $db->query($sql);

        if ($result)
        {
            $num = $db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $task_static = new Task($db);
                if($task_static->fetch($obj->rowid)) {
                    $obj_ret[] = $this->_cleanObjectDatas($task_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve task list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No task found');
        }
		return $obj_ret;
    }

    /**
     * Create task object
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of project
     */
    function post($request_data = null)
    {
      if(! DolibarrApiAccess::$user->rights->projet->creer) {
			  throw new RestException(401, "Insuffisant rights");
		  }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->task->$field = $value;
        }
        /*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->project->lines = $lines;
        }*/
        if ($this->task->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating task", array_merge(array($this->task->error), $this->task->errors));
        }

        return $this->task->id;
    }

    /**
     * Get time spent of a task
     *
     * @param int   $id                     Id of task
     * @return int
     *
     * @url	GET {id}/tasks
     */
    /*
    function getLines($id, $includetimespent=0)
    {
      if(! DolibarrApiAccess::$user->rights->projet->lire) {
		  	throw new RestException(401);
		  }

      $result = $this->project->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Project not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('project',$this->project->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
      $this->project->getLinesArray(DolibarrApiAccess::$user);
      $result = array();
      foreach ($this->project->lines as $line)      // $line is a task
      {
          if ($includetimespent == 1)
          {
              $timespent = $line->getSummaryOfTimeSpent(0);
          }
          if ($includetimespent == 1)
          {
                // TODO
                // Add class for timespent records and loop and fill $line->lines with records of timespent
          }
          array_push($result,$this->_cleanObjectDatas($line));
      }
      return $result;
    }
    */

    /**
     * Get roles a user is assigned to a task with
     *
     * @param   int   $id             Id of task
     * @param   int   $userid         Id of user (0 = connected user)
     *
     * @url	GET {id}/roles
     *
     * @return int
     */
    function getRoles($id, $userid=0)
    {
        global $db;

        if(! DolibarrApiAccess::$user->rights->projet->lire) {
            throw new RestException(401);
        }

        $result = $this->task->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Task not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('tasks',$this->task->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $usert = DolibarrApiAccess::$user;
        if ($userid > 0)
        {
            $usert = new User($this->db);
            $usert->fetch($userid);
        }
        $this->task->roles = $this->task->getUserRolesForProjectsOrTasks(0, $usert, 0, $id);
        $result = array();
        foreach ($this->task->roles as $line) {
            array_push($result,$this->_cleanObjectDatas($line));
        }
        return $result;
    }


    /**
     * Add a task to given project
     *
     * @param int   $id             Id of project to update
     * @param array $request_data   Projectline data
     *
     * @url	POST {id}/tasks
     *
     * @return int
     */
    /*
    function postLine($id, $request_data = null)
    {
      if(! DolibarrApiAccess::$user->rights->projet->creer) {
		  	throw new RestException(401);
		  }

      $result = $this->project->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Project not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('project',$this->project->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->project->addline(
                        $request_data->desc,
                        $request_data->subprice,
                        $request_data->qty,
                        $request_data->tva_tx,
                        $request_data->localtax1_tx,
                        $request_data->localtax2_tx,
                        $request_data->fk_product,
                        $request_data->remise_percent,
                        $request_data->info_bits,
                        $request_data->fk_remise_except,
                        'HT',
                        0,
                        $request_data->date_start,
                        $request_data->date_end,
                        $request_data->product_type,
                        $request_data->rang,
                        $request_data->special_code,
                        $fk_parent_line,
                        $request_data->fk_fournprice,
                        $request_data->pa_ht,
                        $request_data->label,
                        $request_data->array_options,
                        $request_data->fk_unit,
                        $this->element,
                        $request_data->id
      );

      if ($updateRes > 0) {
        return $updateRes;

      }
      return false;
    }
    */

    /**
     * Update a task to given project
     *
     * @param int   $id             Id of project to update
     * @param int   $taskid         Id of task to update
     * @param array $request_data   Projectline data
     *
     * @url	PUT {id}/tasks/{taskid}
     *
     * @return object
     */
    /*
    function putLine($id, $lineid, $request_data = null)
    {
      if(! DolibarrApiAccess::$user->rights->projet->creer) {
		  	throw new RestException(401);
		  }

      $result = $this->project->fetch($id);
      if( ! $result ) {
         throw new RestException(404, 'Project not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('project',$this->project->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
			$request_data = (object) $request_data;
      $updateRes = $this->project->updateline(
                        $lineid,
                        $request_data->desc,
                        $request_data->subprice,
                        $request_data->qty,
                        $request_data->remise_percent,
                        $request_data->tva_tx,
                        $request_data->localtax1_tx,
                        $request_data->localtax2_tx,
                        'HT',
                        $request_data->info_bits,
                        $request_data->date_start,
                        $request_data->date_end,
                        $request_data->product_type,
                        $request_data->fk_parent_line,
                        0,
                        $request_data->fk_fournprice,
                        $request_data->pa_ht,
                        $request_data->label,
                        $request_data->special_code,
                        $request_data->array_options,
                        $request_data->fk_unit
      );

      if ($updateRes > 0) {
        $result = $this->get($id);
        unset($result->line);
        return $this->_cleanObjectDatas($result);
      }
      return false;
    }*/


    /**
     * Update task general fields (won't touch time spent of task)
     *
     * @param int   $id             Id of task to update
     * @param array $request_data   Datas
     *
     * @return int
     */
    function put($id, $request_data = null)
    {
      if(! DolibarrApiAccess::$user->rights->projet->creer) {
		  	throw new RestException(401);
		  }

        $result = $this->task->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Task not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('tasks',$this->project->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}
        foreach($request_data as $field => $value) {
            if ($field == 'id') continue;
            $this->task->$field = $value;
        }

        if ($this->task->update(DolibarrApiAccess::$user) > 0)
        {
            return $this->get($id);
        }
        else
        {
            throw new RestException(500, $this->task->error);
        }
    }

    /**
     * Delete task
     *
     * @param   int     $id         Task ID
     *
     * @return  array
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->projet->supprimer) {
			throw new RestException(401);
		}
        $result = $this->task->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Task not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('tasks',$this->project->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if( ! $this->task->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete task : '.$this->task->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Task deleted'
            )
        );
    }


    /**
     * Add time spent to a task of a project.
     * You can test this API with the following input message
     * { "date": "2016-12-31 23:15:00", "duration": 1800, "user_id": 1, "note": "My time test" }
     *
     * @param   int         $id                 Task ID
     * @param   datetime    $date               Date (YYYY-MM-DD HH:MI:SS in GMT)
     * @param   int         $duration           Duration in seconds (3600 = 1h)
     * @param   int         $user_id            User (Use 0 for connected user)
     * @param   string      $note               Note
     *
     * @url POST    {id}/addtimespent
     *
     * @return  array
     */
    function addTimeSpent($id, $date, $duration, $user_id=0, $note='')
    {


        if( ! DolibarrApiAccess::$user->rights->projet->creer) {
            throw new RestException(401);
        }
        $result = $this->task->fetch($id);
        if ($result <= 0) {
            throw new RestException(404, 'Task not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('project', $this->task->fk_project)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $uid = $user_id;
        if (empty($uid)) $uid = DolibarrApiAccess::$user->id;

        $newdate = dol_stringtotime($date, 1);
        $this->task->timespent_date = $newdate;
        $this->task->timespent_datehour = $newdate;
        $this->task->timespent_withhour = 1;
        $this->task->timespent_duration = $duration;
        $this->task->timespent_fk_user  = $user_id;
        $this->task->timespent_note     = $note;

        $result = $this->task->addTimeSpent(DolibarrApiAccess::$user, 0);
        if ($result == 0) {
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when adding time: '.$this->task->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Time spent added'
            )
        );
    }


    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {

        $object = parent::_cleanObjectDatas($object);

        unset($object->barcode_type);
        unset($object->barcode_type_code);
        unset($object->barcode_type_label);
        unset($object->barcode_type_coder);
        unset($object->cond_reglement_id);
        unset($object->cond_reglement);
        unset($object->fk_delivery_address);
        unset($object->shipping_method_id);
        unset($object->fk_account);
        unset($object->note);
        unset($object->fk_incoterms);
        unset($object->libelle_incoterms);
        unset($object->location_incoterms);
        unset($object->name);
        unset($object->lastname);
        unset($object->firstname);
        unset($object->civility_id);
        unset($object->mode_reglement_id);
        unset($object->country);
        unset($object->country_id);
        unset($object->country_code);

        unset($object->weekWorkLoad);
        unset($object->weekWorkLoad);

        //unset($object->lines);            // for task we use timespent_lines, but for project we use lines

        unset($object->total_ht);
        unset($object->total_tva);
        unset($object->total_localtax1);
        unset($object->total_localtax2);
        unset($object->total_ttc);

        return $object;
    }

    /**
     * Validate fields before create or update object
     *
     * @param   array           $data   Array with data to verify
     * @return  array
     * @throws  RestException
     */
    function _validate($data)
    {
        $object = array();
        foreach (self::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $object[$field] = $data[$field];
        }
        return $object;
    }


    // TODO
    // getSummaryOfTimeSpent
}
