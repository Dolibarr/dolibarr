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

 require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';

/**
 * API class for fichinters
 *
 * @access protected
 * @class  DolibarrApiAccess {@requires user,external}
 */
class Fichinters extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
      'socid',
      'fk_project',
      'description'
    );

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDSLINE = array(
      'fk_fichinter',
      'description',
      'date',
      'duree'
    );

    /**
     * @var fichinter $fichinter {@type fichinter}
     */
    public $fichinter;

    /**
     * Constructor
     */
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
        $this->fichinter = new Fichinter($this->db);
    }

    /**
     * List fichinters
     *
     * Get a list of fichinters
     *
     * @param string	       $sortfield	        Sort field
     * @param string	       $sortorder	        Sort order
     * @param int	       $limit		        Limit for list
     * @param int	       $page		        Page number
     * @param string   	       $thirdparty_ids	        Thirdparty ids to filter orders of. {@example '1' or '1,2,3'} {@pattern /^[0-9,]*$/i}
     * @param string           $sqlfilters              Other criteria to filter answers separated by a comma. Syntax example "(t.ref:like:'SO-%') and (t.date_creation:<:'20160101')"
     * @return  array                                   Array of order objects
     *
     * @throws RestException
     */
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '') {
        global $db, $conf;

        $obj_ret = array();

        // case of external user, $thirdparty_ids param is ignored and replaced by user's socid
        $socids = DolibarrApiAccess::$user->societe_id ? DolibarrApiAccess::$user->societe_id : $thirdparty_ids;

        // If the internal user must only see his customers, force searching by him
        $search_sale = 0;
        if (! DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) $search_sale = DolibarrApiAccess::$user->id;

        $sql = "SELECT t.rowid";
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql .= ", sc.fk_soc, sc.fk_user"; // We need these fields in order to filter by sale (including the case where the user can only see his prospects)
        $sql.= " FROM ".MAIN_DB_PREFIX."fichinter as t";

        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc"; // We need this table joined to the select in order to filter by sale

        $sql.= ' WHERE t.entity IN ('.getEntity('fichinter').')';
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
                $fichinter_static = new Fichinter($db);
                if($fichinter_static->fetch($obj->rowid)) {
                    $obj_ret[] = $fichinter_static;
                }
                $i++;
            }
        }
        else {
            throw new RestException(503, 'Error when retrieve fichinter list : '.$db->lasterror());
        }
        if( ! count($obj_ret)) {
            throw new RestException(404, 'No finchinter found');
        }
		return $obj_ret;
    }

    /**
     * Create fichinter object
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of fichinter
     */
    function post($request_data = NULL)
    {
      if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
			  throw new RestException(401, "Insuffisant rights");
		  }
        // Check mandatory fields
        $result = $this->_validate($request_data);
        foreach($request_data as $field => $value) {
            $this->fichinter->$field = $value;
        }

        if ($this->fichinter->create(DolibarrApiAccess::$user) < 0) {
            throw new RestException(500, "Error creating fichinter", array_merge(array($this->fichinter->error), $this->fichinter->errors));
        }

        return $this->fichinter->id;
    }

    /**
     * Create fichinter line object
     *
     * @param   array   $request_data   Request data
     *
     * @url     POST /line
     *
     * @return  boolean Create line fichinter work
     */
    function postLine($request_data = NULL)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
                          throw new RestException(401, "Insuffisant rights");
                  }
        // Check mandatory fields
        $result = $this->_validateLine($request_data);

        foreach($request_data as $field => $value) {
            $this->fichinter->$field = $value;
        }

        if( ! $result ) {
            throw new RestException(404, 'Fichinter not found');
        }

                if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
                        throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
                }

        if ($this->fichinter->addLine(
                DolibarrApiAccess::$user,
                $this->fichinter->fk_fichinter,
                $this->fichinter->description,
                $this->fichinter->date,
                $this->fichinter->duree) < 0) {
            throw new RestException(500, "Error creating fichinter line", array_merge(array($this->fichinter->error), $this->fichinter->errors));
        }

        return $this->fichinter;
    }

    /**
     * Validate a fichinter
     *
     * @param   int $id             fichinter ID
     *
     * @url POST    validate
     *
     * @return  array
     *
     */
    function validFichinter($id)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
                          throw new RestException(401, "Insuffisant rights");
                  }
        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Fichinter not found');
        }

                if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
                        throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
                }

        if( ! $this->fichinter->setValid(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when validate fichinter');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Fichinter validated'
            )
        );
    }

    /**
     * Close a fichinter
     *
     * @param   int $id             fichinter ID
     *
     * @url POST    close
     *
     * @return  array
     *
     */

    function closeFichinter($id)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
                          throw new RestException(401, "Insuffisant rights");
                  }
        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Fichinter not found');
        }

                if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
                        throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
                }

        if(! $this->fichinter->setStatut(3) ) {
            throw new RestException(500, 'Error when closed fichinter');
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Fichinter closed'
            )
        );
    }

    /**
     * Validate fields before create or update object
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    function _validate($data)
    {
        $fichinter = array();
        foreach (Fichinters::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $fichinter[$field] = $data[$field];
        }
        return $fichinter;
    }

    /**
     * Validate fields before create or update object
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
    function _validateLine($data)
    {
        $fichinter = array();
        foreach (Fichinters::$FIELDSLINE as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $fichinter[$field] = $data[$field];
        }
        return $fichinter;
    }


}
