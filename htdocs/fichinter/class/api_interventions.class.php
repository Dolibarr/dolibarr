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
class Interventions extends DolibarrApi
{

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDS = array(
      'socid',
      'fk_project',
<<<<<<< HEAD
      'description'
=======
      'description',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    );

    /**
     * @var array   $FIELDS     Mandatory fields, checked when create and update object
     */
    static $FIELDSLINE = array(
      'description',
      'date',
<<<<<<< HEAD
      'duree'
=======
      'duree',
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    );

    /**
     * @var fichinter $fichinter {@type fichinter}
     */
    public $fichinter;

    /**
     * Constructor
     */
<<<<<<< HEAD
    function __construct()
    {
		global $db, $conf;
		$this->db = $db;
=======
    public function __construct()
    {
        global $db, $conf;
        $this->db = $db;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $this->fichinter = new Fichinter($this->db);
    }

    /**
     * Get properties of a Expense Report object
     *
<<<<<<< HEAD
     * Return an array with Expense Report informations
=======
     * Return an array with Expense Report information
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
     *
     * @param       int         $id         ID of Expense Report
     * @return 	    array|mixed             Data without useless information
     *
     * @throws 	RestException
     */
<<<<<<< HEAD
    function get($id)
    {
    	if(! DolibarrApiAccess::$user->rights->ficheinter->lire) {
    		throw new RestException(401);
    	}

    	$result = $this->fichinter->fetch($id);
    	if( ! $result ) {
    		throw new RestException(404, 'Intervention report not found');
    	}

    	if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
    		throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    	}

    	$this->fichinter->fetchObjectLinked();
    	return $this->_cleanObjectDatas($this->fichinter);
=======
    public function get($id)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401);
        }

        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Intervention report not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $this->fichinter->fetchObjectLinked();
        return $this->_cleanObjectDatas($this->fichinter);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * List of interventions
     *
     * Return a list of interventions
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
<<<<<<< HEAD
    function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '') {
=======
    public function index($sortfield = "t.rowid", $sortorder = 'ASC', $limit = 100, $page = 0, $thirdparty_ids = '', $sqlfilters = '')
    {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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

        $sql.= ' WHERE t.entity IN ('.getEntity('intervention').')';
        if ((!DolibarrApiAccess::$user->rights->societe->client->voir && !$socids) || $search_sale > 0) $sql.= " AND t.fk_soc = sc.fk_soc";
        if ($socids) $sql.= " AND t.fk_soc IN (".$socids.")";
        if ($search_sale > 0) $sql.= " AND t.rowid = sc.fk_soc";		// Join for the needed table to filter by sale
        // Insert sale filter
<<<<<<< HEAD
        if ($search_sale > 0)
        {
=======
        if ($search_sale > 0) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            $sql .= " AND sc.fk_user = ".$search_sale;
        }
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(503, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
<<<<<<< HEAD
	        $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
=======
            $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
            $i = 0;
            while ($i < $min)
            {
                $obj = $db->fetch_object($result);
                $fichinter_static = new Fichinter($db);
                if($fichinter_static->fetch($obj->rowid)) {
<<<<<<< HEAD
                	$obj_ret[] = $this->_cleanObjectDatas($fichinter_static);
=======
                    $obj_ret[] = $this->_cleanObjectDatas($fichinter_static);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
<<<<<<< HEAD
		return $obj_ret;
=======
        return $obj_ret;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Create intervention object
     *
     * @param   array   $request_data   Request data
     * @return  int     ID of intervention
     */
<<<<<<< HEAD
    function post($request_data = null)
    {
      if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
			  throw new RestException(401, "Insuffisant rights");
		  }
=======
    public function post($request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insuffisant rights");
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
     * Get lines of an intervention
     *
     * @param int   $id             Id of intervention
     *
     * @url	GET {id}/lines
     *
     * @return int
     */
    /* TODO
<<<<<<< HEAD
    function getLines($id) {
    	if(! DolibarrApiAccess::$user->rights->ficheinter->lire) {
    		throw new RestException(401);
    	}

    	$result = $this->fichinter->fetch($id);
    	if( ! $result ) {
    		throw new RestException(404, 'Intervention not found');
    	}

    	if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
    		throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    	}
    	$this->fichinter->getLinesArray();
    	$result = array();
    	foreach ($this->fichinter->lines as $line) {
    		array_push($result,$this->_cleanObjectDatas($line));
    	}
    	return $result;
    }
	*/
=======
    public function getLines($id)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->lire) {
            throw new RestException(401);
        }

        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Intervention not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
        $this->fichinter->getLinesArray();
        $result = array();
        foreach ($this->fichinter->lines as $line) {
            array_push($result,$this->_cleanObjectDatas($line));
        }
        return $result;
    }
    */
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

    /**
     * Add a line to given intervention
     *
     * @param 	int   	$id             Id of intervention to update
     * @param   array   $request_data   Request data
     *
     * @url     POST {id}/lines
     *
     * @return  int
     */
<<<<<<< HEAD
    function postLine($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
                          throw new RestException(401, "Insuffisant rights");
                  }
=======
    public function postLine($id, $request_data = null)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insuffisant rights");
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        // Check mandatory fields
        $result = $this->_validateLine($request_data);

        foreach($request_data as $field => $value) {
            $this->fichinter->$field = $value;
        }

<<<<<<< HEAD
        if( ! $result ) {
            throw new RestException(404, 'Intervention not found');
        }

                if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
                        throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
                }
=======
        if ( ! $result ) {
            throw new RestException(404, 'Intervention not found');
        }

        if ( ! DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

        $updateRes = $this->fichinter->addLine(
                DolibarrApiAccess::$user,
                $id,
                $this->fichinter->description,
                $this->fichinter->date,
                $this->fichinter->duree
        );

        if ($updateRes > 0) {
<<<<<<< HEAD
        	return $updateRes;
        }
        else {
        	throw new RestException(400, $this->fichinter->error);
=======
            return $updateRes;
        } else {
            throw new RestException(400, $this->fichinter->error);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }
    }

    /**
     * Delete order
     *
     * @param   int     $id         Order ID
     * @return  array
     */
<<<<<<< HEAD
    function delete($id)
    {
    	if(! DolibarrApiAccess::$user->rights->ficheinter->supprimer) {
    		throw new RestException(401);
    	}
    	$result = $this->fichinter->fetch($id);
    	if( ! $result ) {
    		throw new RestException(404, 'Intervention not found');
    	}

    	if( ! DolibarrApi::_checkAccessToResource('commande',$this->fichinter->id)) {
    		throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
    	}

    	if( ! $this->fichinter->delete(DolibarrApiAccess::$user)) {
    		throw new RestException(500, 'Error when delete intervention : '.$this->fichinter->error);
    	}

    	return array(
	    	'success' => array(
		    	'code' => 200,
		    	'message' => 'Intervention deleted'
	    	)
    	);

=======
    public function delete($id)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->supprimer) {
            throw new RestException(401);
        }
        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Intervention not found');
        }

        if( ! DolibarrApi::_checkAccessToResource('commande', $this->fichinter->id)) {
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        if( ! $this->fichinter->delete(DolibarrApiAccess::$user)) {
            throw new RestException(500, 'Error when delete intervention : '.$this->fichinter->error);
        }

        return array(
            'success' => array(
                'code' => 200,
                'message' => 'Intervention deleted'
            )
        );
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Validate an intervention
     *
     * If you get a bad value for param notrigger check, provide this in body
     * {
     *   "notrigger": 0
     * }
     *
     * @param   int $id             Intervention ID
     * @param   int $notrigger      1=Does not execute triggers, 0= execute triggers
     *
     * @url POST    {id}/validate
     *
     * @return  array
     */
<<<<<<< HEAD
    function validate($id, $notrigger=0)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
                          throw new RestException(401, "Insuffisant rights");
                  }
=======
    public function validate($id, $notrigger = 0)
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer) {
            throw new RestException(401, "Insuffisant rights");
        }
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        $result = $this->fichinter->fetch($id);
        if( ! $result ) {
            throw new RestException(404, 'Intervention not found');
        }

<<<<<<< HEAD
        if( ! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
=======
        if( ! DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->fichinter->setValid(DolibarrApiAccess::$user, $notrigger);
        if ($result == 0) {
<<<<<<< HEAD
        	throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
        	throw new RestException(500, 'Error when validating Intervention: '.$this->commande->error);
=======
            throw new RestException(304, 'Error nothing done. May be object is already validated');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when validating Intervention: '.$this->commande->error);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }

        $this->fichinter->fetchObjectLinked();

        return $this->_cleanObjectDatas($this->fichinter);
    }

    /**
     * Close an intervention
     *
     * @param   int 	$id             Intervention ID
     *
     * @url POST    {id}/close
     *
     * @return  array
     */
<<<<<<< HEAD
    function closeFichinter($id)
=======
    public function closeFichinter($id)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        if(! DolibarrApiAccess::$user->rights->ficheinter->creer)
        {
            throw new RestException(401, "Insuffisant rights");
        }
        $result = $this->fichinter->fetch($id);
        if (! $result) {
            throw new RestException(404, 'Intervention not found');
        }

<<<<<<< HEAD
        if (! DolibarrApi::_checkAccessToResource('fichinter',$this->fichinter->id)) {
=======
        if (! DolibarrApi::_checkAccessToResource('fichinter', $this->fichinter->id)) {
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
            throw new RestException(401, 'Access not allowed for login '.DolibarrApiAccess::$user->login);
        }

        $result = $this->fichinter->setStatut(3);

        if ($result == 0) {
<<<<<<< HEAD
        	throw new RestException(304, 'Error nothing done. May be object is already closed');
        }
        if ($result < 0) {
        	throw new RestException(500, 'Error when closing Intervention: '.$this->fichinter->error);
=======
            throw new RestException(304, 'Error nothing done. May be object is already closed');
        }
        if ($result < 0) {
            throw new RestException(500, 'Error when closing Intervention: '.$this->fichinter->error);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
        }

        $this->fichinter->fetchObjectLinked();

        return $this->_cleanObjectDatas($this->fichinter);
    }

    /**
     * Validate fields before create or update object
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
<<<<<<< HEAD
    function _validate($data)
=======
    private function _validate($data)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        $fichinter = array();
        foreach (Interventions::$FIELDS as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $fichinter[$field] = $data[$field];
        }
        return $fichinter;
    }


<<<<<<< HEAD
=======
    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    /**
     * Clean sensible object datas
     *
     * @param   object  $object    Object to clean
     * @return    array    Array of cleaned object properties
     */
<<<<<<< HEAD
    function _cleanObjectDatas($object) {

    	$object = parent::_cleanObjectDatas($object);

    	unset($object->statuts_short);
    	unset($object->statuts_logo);
    	unset($object->statuts);

    	return $object;
=======
    protected function _cleanObjectDatas($object)
    {
        // phpcs:enable
        $object = parent::_cleanObjectDatas($object);

        unset($object->statuts_short);
        unset($object->statuts_logo);
        unset($object->statuts);

        return $object;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    }

    /**
     * Validate fields before create or update object
     *
     * @param array $data   Data to validate
     * @return array
     *
     * @throws RestException
     */
<<<<<<< HEAD
    function _validateLine($data)
=======
    private function _validateLine($data)
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    {
        $fichinter = array();
        foreach (Interventions::$FIELDSLINE as $field) {
            if (!isset($data[$field]))
                throw new RestException(400, "$field field missing");
            $fichinter[$field] = $data[$field];
        }
        return $fichinter;
    }
<<<<<<< HEAD


=======
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
}
