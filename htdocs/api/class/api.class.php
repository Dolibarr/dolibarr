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

use Luracast\Restler\Restler;
use Luracast\Restler\RestException;
use Luracast\Restler\Defaults;

require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';

/**
 * Class for API REST v1
 */
class DolibarrApi
{

    /**
     * @var DoliDb        $db Database object
     */
    static protected $db;

    /**
     * @var Restler     $r	Restler object
     */
    var $r;

    /**
     * Constructor
     *
     * @param	DoliDb	$db		      Database handler
     * @param   string  $cachedir     Cache dir
     */
    function __construct($db, $cachedir='')
    {
        global $conf;
        
        if (empty($cachedir)) $cachedir = $conf->api->dir_temp;
        Defaults::$cacheDirectory = $cachedir;
        
        $this->db = $db;
        $production_mode = ( empty($conf->global->API_PRODUCTION_MODE) ? false : true );
        $this->r = new Restler($production_mode);
        
        $this->r->setAPIVersion(1);
    }

    /**
     * Executed method when API is called without parameter
     *
     * Display a short message an return a http code 200
     *
     * @return array
     */
    function index()
    {
        return array(
            'success' => array(
                'code' => 200,
                'message' => __class__.' is up and running!'
            )
        );
    }


    /**
     * Clean sensible object datas
     *
     * @param   object  $object	Object to clean
     * @return	array	Array of cleaned object properties
     */
    function _cleanObjectDatas($object) {

        // Remove $db object property for object
        unset($object->db);
        
        // Remove linkedObjects. We should already have linkedObjectIds that avoid huge responses
        unset($object->linkedObjects);
        
        unset($object->lignes); // should be ->lines
        unset($object->oldline);
        
        unset($object->error);
        unset($object->errors);
        
        unset($object->ref_previous);
        unset($object->ref_next);
        unset($object->ref_int);
        
        unset($object->projet);     // Should be fk_project
        unset($object->project);    // Should be fk_project
        unset($object->author);     // Should be fk_user_author
        unset($object->timespent_old_duration);
        unset($object->timespent_id);
        unset($object->timespent_duration);
        unset($object->timespent_date);
        unset($object->timespent_datehour);
        unset($object->timespent_withhour);
        unset($object->timespent_fk_user);
        unset($object->timespent_note);
        
        unset($object->statuts);
        unset($object->statuts_short);
        unset($object->statuts_logo);
        unset($object->statuts_long);
        
        unset($object->element);
        unset($object->fk_element);
        unset($object->table_element);
        unset($object->table_element_line);
        unset($object->picto);
        
        // Remove the $oldcopy property because it is not supported by the JSON
        // encoder. The following error is generated when trying to serialize
        // it: "Error encoding/decoding JSON: Type is not supported"
        // Note: Event if this property was correctly handled by the JSON
        // encoder, it should be ignored because keeping it would let the API
        // have a very strange behavior: calling PUT and then GET on the same
        // resource would give different results:
        // PUT /objects/{id} -> returns object with oldcopy = previous version of the object
        // GET /objects/{id} -> returns object with oldcopy empty
        unset($object->oldcopy);

        // If object has lines, remove $db property
        if(isset($object->lines) && count($object->lines) > 0)  {
            $nboflines = count($object->lines);
        	for ($i=0; $i < $nboflines; $i++)
            {
                $this->_cleanObjectDatas($object->lines[$i]);
            }
        }

        // If object has linked objects, remove $db property
        /*
        if(isset($object->linkedObjects) && count($object->linkedObjects) > 0)  {
            foreach($object->linkedObjects as $type_object => $linked_object) {
                foreach($linked_object as $object2clean) {
                    $this->_cleanObjectDatas($object2clean);
                }
            }
        }*/
        
		return $object;
    }

	/**
	 * Check user access to a resource
	 *
	 * Check access by user to a given resource
	 *
	 * @param string	$resource		element to check
	 * @param int		$resource_id	Object ID if we want to check a particular record (optional) is linked to a owned thirdparty (optional).
	 * @param type		$dbtablename	'TableName&SharedElement' with Tablename is table where object is stored. SharedElement is an optional key to define where to check entity. Not used if objectid is null (optional)
	 * @param string	$feature2		Feature to check, second level of permission (optional). Can be or check with 'level1|level2'.
	 * @param string	$dbt_keyfield   Field name for socid foreign key if not fk_soc. Not used if objectid is null (optional)
	 * @param string	$dbt_select     Field name for select if not rowid. Not used if objectid is null (optional)
	 * @throws RestException
	 */
	static function _checkAccessToResource($resource, $resource_id=0, $dbtablename='', $feature2='', $dbt_keyfield='fk_soc', $dbt_select='rowid') {
        
		// Features/modules to check
		$featuresarray = array($resource);
		if (preg_match('/&/', $resource)) {
			$featuresarray = explode("&", $resource);
		}
		else if (preg_match('/\|/', $resource)) {
			$featuresarray = explode("|", $resource);
		}

		// More subfeatures to check
		if (! empty($feature2)) {
			$feature2 = explode("|", $feature2);
		}

		return checkUserAccessToObject(DolibarrApiAccess::$user, $featuresarray, $resource_id, $dbtablename, $feature2, $dbt_keyfield, $dbt_select);
	}
	
	/**
	 * Return if a $sqlfilters parameter is valid
	 * 
	 * @param  string   $sqlfilters     sqlfilter string
	 * @return boolean                  True if valid, False if not valid 
	 */
	function _checkFilters($sqlfilters)
	{
	    //$regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
	    //$tmp=preg_replace_all('/'.$regexstring.'/', '', $sqlfilters);
	    $tmp=$sqlfilters;
	    $ok=0;
	    $i=0; $nb=count($tmp);
	    $counter=0;
	    while ($i < $nb)
	    {
	        if ($tmp[$i]=='(') $counter++;
	        if ($tmp[$i]==')') $counter--;
            if ($counter < 0)
            {
	           $error="Bad sqlfilters=".$sqlfilters;
	           dol_syslog($error, LOG_WARNING);
	           return false;
            }
            $i++;
	    }
	    return true;
	}
	
	/**
	 * Function to forge a SQL criteria
	 * 
	 * @param  array    $matches       Array of found string by regex search
	 * @return string                  Forged criteria. Example: "t.field like 'abc%'"
	 */
	static function _forge_criteria_callback($matches)
	{
	    global $db;
	    
	    //dol_syslog("Convert matches ".$matches[1]);
	    if (empty($matches[1])) return '';
	    $tmp=explode(':',$matches[1]);
        if (count($tmp) < 3) return '';
        
	    $tmpescaped=$tmp[2];
	    if (preg_match('/^\'(.*)\'$/', $tmpescaped, $regbis))
	    {
	        $tmpescaped = "'".$db->escape($regbis[1])."'";
	    }
	    else
	    {
	        $tmpescaped = $db->escape($tmpescaped);
	    }
	    return $db->escape($tmp[0]).' '.strtoupper($db->escape($tmp[1]))." ".$tmpescaped;
	}	
}
