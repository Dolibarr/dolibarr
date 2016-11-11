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

 require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
 
/**
 * API class for projects
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Projects extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
        'ref',
        'title'
    );

    /**
     * @var Project $project {@type Project}
     */
    public $project;

    /**
     * Constructor
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->project = new Project($this->db);
    }

    /**
     * Get properties of a project object
     *
     * Return an array with project informations
     *
     * @param       int         $id         ID of project
     * @return 	array|mixed data without useless information
	 *
     * @throws 	RestException
     */
    function get($id)
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

        $this->project->fetchObjectLinked();
		return $this->_cleanObjectDatas($this->project);
    }

    
   
    /**
     * List projects
     *
     * Get a list of projects
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int		       $limit		        Limit for list
     * @param int		       $page		        Page number
     * @param string   	       $thirdparty_ids	    Thirdparty ids to filter projects of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param string           $sqlfilters          Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                               Array of project objects
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '') {
        global $db, $conf;

        $obj_ret = array();
        // case of external user, $thirdpartyid param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."projet as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE t.entity IN ('.getEntity('project', 1).')';
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
            while ($i < min($num, ($limit <= 0 ? $num : $limit)))
            {
                $obj = $db->fetch_object($result);
                $project_static = new Project($db);
                if($project_static->fetch($obj->rowid)) {
                    $obj_ret[] = parent::_cleanObjectDatas($project_static);
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve project list');
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No project found');
        }
		return $obj_ret;
    }

    /**
     * Create project object
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of project
     */
    function post($request_data = NULL)
    {
      if(! DolibarrApiAccess::$user->rights->projet->creer) {
			  throw new RestException(401, "Insuffisant rights");
		  }
        // Check mandatory fields
        $result = $this->_validate($request_data);

        foreach($request_data as $field => $value) {
            $this->project->$field = $value;
        }
        /*if (isset($request_data["lines"])) {
          $lines = array();
          foreach ($request_data["lines"] as $line) {
            array_push($lines, (object) $line);
          }
          $this->project->lines = $lines;
        }*/
        if ($this->project->create(DolibarrApiAccess::$user) <= 0) {
            $errormsg = $this->project->error;
            throw new RestException(500, $errormsg ? $errormsg : "Error while creating project");
        }

        return $this->project->id;
    }

    /**
     * Get tasks of a project
     *
     * @param int   $id             Id of project
     *
     * @url	GET {id}/tasks
     *
     * @return int
     */
    function getLines($id) {
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
      foreach ($this->project->lines as $line) {
        array_push($result,$this->_cleanObjectDatas($line));
      }
      return $result;
    }

    
    /**
     * Get users and roles assigned to a project
     *
     * @param int   $id             Id of project
     *
     * @url	GET {id}/roles
     *
     * @return int
     */
    function getRoles($id) {
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
        
        require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
        $taskstatic=new Task($this->db);
        $this->project->roles = $taskstatic->getUserRolesForProjectsOrTasks(DolibarrApiAccess::$user, 0, $id, 0);
        $result = array();
        foreach ($this->project->roles as $line) {
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
    function postLine($id, $request_data = NULL) {
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
        return $this->get($id)->line->rowid;

      }
      return false;
    }
    */
    
    /**
     * Update a task to given project
     *
     * @param int   $id             Id of project to update
     * @param int   $lineid         Id of line to update
     * @param array $request_data   Projectline data
     *
     * @url	PUT {id}/tasks/{lineid}
     *
     * @return object
     */
    /*
    function putLine($id, $lineid, $request_data = NULL) {
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
     * Delete a tasks of given project
     *
     *
     * @param int   $id             Id of project to update
     * @param int   $taskid         Id of task to delete
     *
     * @url	DELETE {id}/tasks/{taskid}
     *
     * @return int
     */
    function delLine($id, $taskid) {
      if(! DolibarrApiAccess::$user->rights->projet->creer) {
		  	throw new RestException(401);
		  }

      $result = $this->project->fetch($id);
      if( ! ($result > 0) ) {
         throw new RestException(404, 'Project not found');
      }

		  if( ! DolibarrApi::_checkAccessToResource('project',$this->project->id)) {
			  throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
      }
    
      require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
      $taskstatic=new Task($this->db);
      $result = $taskstatic->fetch($taskid);
      if( ! ($result > 0) ) {
          throw new RestException(404, 'Task not found');
      }
      
      $deleteRes = $taskstatic->delete(DolibarrApiAccess::$user);
      
      if( ! ($deleteRes > 0)) {
          throw new RestException(500, 'Error when delete tasks : '.$taskstatic->error);
      }
      
      return array(
          'success' => array(
              'code' => 200,
              'message' => 'Task deleted'
          )
      );
    }

    
    /**
     * Update project general fields (won't touch lines of project)
     *
     * @param int   $id             Id of project to update
     * @param array $request_data   Datas
     *
     * @return int
     */
    function put($id, $request_data = NULL) {
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
        foreach($request_data as $field => $value) {
            $this->project->$field = $value;
        }

        if($this->project->update(DolibarrApiAccess::$user, 0))
            return $this->get($id);

        return false;
    }

    /**
     * Delete project
     *
     * @param   int     $id         Project ID
     *
     * @return  array
     */
    function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->projet->supprimer) {
			throw new RestException(401);
		}
        $result = $this->project->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Project not found');
        }

		if( ! DolibarrApi::_checkAccessToResource('project',$this->project->id)) {
			throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
		}

        if( ! $this->project->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete project : '.$this->project->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Project deleted'
            )
        );

    }

    /**
     * Validate a project
     *
     * @param   int $id             Project ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     *
     * @url POST    {id}/validate
     *
     * @return  array
     * FIXME An error 403 is returned if the request has an empty body.
     * Error message: "Forbidden: Content type `text/plain` is not supported."
     * Workaround: send this in the body
     * {
     *   "notrigger": 0
     * }
     */
    function validate($id, $notrigger=0)
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

		$result = $this->project->setValid(DolibarrApiAccess::$user, $notrigger);
		if ($result == 0) {
		    throw new RestException(500, 'Error nothing done. May be object is already validated');
		}
		if ($result < 0) {
		    throw new RestException(500, 'Error when validating Project: '.$this->project->error);
		}

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Project validated'
            )
        );
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
