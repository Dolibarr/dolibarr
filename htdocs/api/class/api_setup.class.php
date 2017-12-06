<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2017	Regis Houssin	<regis.houssin@capnetworks.com>
 * Copyright (C) 2017	Neil Orley	<neil.orley@oeris.fr>
 *
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

use Luracast\Restler\RestException;

require_once DOL_DOCUMENT_ROOT.'/main.inc.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/ccountry.class.php';

/**
 * API class for dictionaries
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class Setup extends DolibarrApi
{
    private $translations = null;

    /**
     * Constructor
     */
    function __construct()
    {
        global $db;
        $this->db = $db;
    }

    /**
     * Get the list of payments types.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number {@min 0}
     * @param int       $active     Payment type is active or not {@min 0} {@max 1}
     * @param string    $sqlfilters SQL criteria to filter with. Syntax example "(t.code:=:'CHQ')"
     *
     * @url     GET dictionary/payment_types
     *
     * @return array [List of payment types]
     *
     * @throws 400 RestException
     * @throws 200 OK
     */
    function getPaymentTypes($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT id, code, type, libelle as label, module";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_paiement as t";
        $sql.= " WHERE t.active = ".$active;
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(400, 'error when validating parameter sqlfilters '.$sqlfilters);
            }
	          $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(400, $this->db->lasterror());
        }

        return $list;
    }

    /**
     * Get the list of countries.
     *
     * The names of the countries will be translated to the given language if
     * the $lang parameter is provided. The value of $lang must be a language
     * code supported by Dolibarr, for example 'en_US' or 'fr_FR'.
     * The returned list is sorted by country ID.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number (starting from zero)
     * @param string    $filter     To filter the countries by name
     * @param string    $lang       Code of the language the label of the countries must be translated to
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
     * @return List of countries
     *
     * @url     GET dictionary/countries
     *
     * @throws RestException
     */
    function getListOfCountries($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $filter = '', $lang = '', $sqlfilters = '')
    {
        $list = array();

        // Note: The filter is not applied in the SQL request because it must
        // be applied to the translated names, not to the names in database.
        $sql = "SELECT rowid FROM ".MAIN_DB_PREFIX."c_country as t";
        $sql.=" WHERE 1 = 1";
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

        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $obj = $this->db->fetch_object($result);
                $country = new Ccountry($this->db);
                if ($country->fetch($obj->rowid) > 0) {
                    // Translate the name of the country if needed
                    // and then apply the filter if there is one.
                    $this->translateLabel($country, $lang);

                    if (empty($filter) || stripos($country->label, $filter) !== FALSE) {
                        $list[] = $this->_cleanObjectDatas($country);
                    }
                }
            }
        } else {
            throw new RestException(503, 'Error when retrieving list of countries : '.$country->error);
        }

        return $list;
    }

    /**
     * Get country by ID.
     *
     * @param int       $id        ID of country
     * @param string    $lang      Code of the language the name of the
     *                             country must be translated to
     *
     * @url     GET dictionary/countries/{id}
     *
     * @throws RestException
     */
    function getCountryByID($id, $lang = '')
    {
        $country = new Ccountry($this->db);

        if ($country->fetch($id) < 0) {
            throw new RestException(503, 'Error when retrieving country : '.$country->error);
        }
        else if ($country->fetch($id) == 0) {
            throw new RestException(404, 'country not found');
        }

        $this->translateLabel($country, $lang);

        return $this->_cleanObjectDatas($country);
    }

    /**
     * Get the list of delivery times.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number {@min 0}
     * @param int       $active     Delivery times is active or not {@min 0} {@max 1}
     * @param string    $sqlfilters SQL criteria to filter with.
     *
     * @url     GET dictionary/availability
     *
     * @return array [List of availability]
     *
     * @throws 400 RestException
     * @throws 200 OK
     */
    function getAvailability($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT rowid, code, label";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_availability as t";
        $sql.= " WHERE t.active = ".$active;
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(400, 'error when validating parameter sqlfilters '.$sqlfilters);
            }
                  $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(400, $this->db->lasterror());
        }

        return $list;
    }

    /**
     * Clean sensible object datas
     *
     * @param object    $object    Object to clean
     * @return array 				Array of cleaned object properties
     */
    function _cleanObjectDatas($object)
    {
        $object = parent::_cleanObjectDatas($object);

        unset($object->error);
        unset($object->errors);

        return $object;
    }

    /**
     * Translate the name of the country to the given language.
     *
     * @param Ccountry $country   Country
     * @param string   $lang      Code of the language the name of the
     *                            country must be translated to
     */
    private function translateLabel($country, $lang)
    {
        if (!empty($lang)) {
            // Load the translations if this is a new language.
            if ($this->translations == null || $this->translations->getDefaultLang() !== $lang) {
                global $conf;
                $this->translations = new Translate('', $conf);
                $this->translations->setDefaultLang($lang);
                $this->translations->load('dict');
            }
            if ($country->code) {
                $key = 'Country'.$country->code;
                $translation = $this->translations->trans($key);
                if ($translation != $key) {
                    $country->label = html_entity_decode($translation);
                }
            }
        }
    }

    /**
     * Get the list of events types.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number (starting from zero)
     * @param string    $type       To filter on type of event
     * @param string    $module     To filter on module events
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
     * @return List of events types
     *
     * @url     GET dictionary/event_types
     *
     * @throws RestException
     */
    function getListOfEvents($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $type = '', $module = '', $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT id, code, type, libelle as label, module";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_actioncomm as t";
        $sql.= " WHERE t.active = 1";
        if ($type) $sql.=" AND t.type LIKE '%" . $this->db->escape($type) . "%'";
        if ($module)    $sql.=" AND t.module LIKE '%" . $this->db->escape($module) . "%'";
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


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(503, 'Error when retrieving list of events types : '.$this->db->lasterror());
        }

        return $list;
    }


    /**
     * Get the list of extra fields.
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param string    $type       Type of element ('adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...)
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'SO-%')"
     * @return List of events types
     *
     * @url     GET extrafields
     *
     * @throws RestException
     */
    function getListOfExtrafields($sortfield = "t.pos", $sortorder = 'ASC', $type = '', $sqlfilters = '')
    {
        $list = array();

        if ($type == 'thirdparty') $type='societe';
        if ($type == 'contact') $type='socpeople';

        $sql = "SELECT t.rowid, t.name, t.label, t.type, t.size, t.elementtype, t.fieldunique, t.fieldrequired, t.param, t.pos, t.alwayseditable, t.perms, t.list, t.fielddefault, t.fieldcomputed";
        $sql.= " FROM ".MAIN_DB_PREFIX."extrafields as t";
        $sql.= " WHERE t.entity IN (".getEntity('extrafields').")";
        if (! empty($type)) $sql.= " AND t.elementtype = '".$this->db->escape($type)."'";
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

        $sql.= $this->db->order($sortfield, $sortorder);

        $resql=$this->db->query($sql);
        if ($resql)
        {
        	if ($this->db->num_rows($resql))
        	{
        		while ($tab = $this->db->fetch_object($resql))
        		{
        			// New usage
        			$list[$tab->elementtype][$tab->name]['type']=$tab->type;
        			$list[$tab->elementtype][$tab->name]['label']=$tab->label;
        			$list[$tab->elementtype][$tab->name]['size']=$tab->size;
        			$list[$tab->elementtype][$tab->name]['elementtype']=$tab->elementtype;
        			$list[$tab->elementtype][$tab->name]['default']=$tab->fielddefault;
        			$list[$tab->elementtype][$tab->name]['computed']=$tab->fieldcomputed;
        			$list[$tab->elementtype][$tab->name]['unique']=$tab->fieldunique;
        			$list[$tab->elementtype][$tab->name]['required']=$tab->fieldrequired;
        			$list[$tab->elementtype][$tab->name]['param']=($tab->param ? unserialize($tab->param) : '');
        			$list[$tab->elementtype][$tab->name]['pos']=$tab->pos;
        			$list[$tab->elementtype][$tab->name]['alwayseditable']=$tab->alwayseditable;
        			$list[$tab->elementtype][$tab->name]['perms']=$tab->perms;
        			$list[$tab->elementtype][$tab->name]['list']=$tab->list;
        		}
        	}
        }
        else
        {
            throw new RestException(503, 'Error when retrieving list of extra fields : '.$this->db->lasterror());
        }

        if (! count($list))
        {
        	throw new RestException(404, 'No extrafield found');
        }

        return $list;
    }


    /**
     * Get the list of towns.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number (starting from zero)
     * @param string    $zipcode    To filter on zipcode
     * @param string    $town       To filter on city name
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.code:like:'A%') and (t.active:>=:0)"
     * @return List of towns
     *
     * @url     GET dictionary/towns
     *
     * @throws RestException
     */
    function getListOfTowns($sortfield = "zip,town", $sortorder = 'ASC', $limit = 100, $page = 0, $zipcode = '', $town = '', $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT rowid AS id, zip, town, fk_county, fk_pays AS fk_country";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_ziptown as t";
        $sql.= " WHERE t.active = 1";
        if ($zipcode) $sql.=" AND t.zip LIKE '%" . $this->db->escape($zipcode) . "%'";
        if ($town)    $sql.=" AND t.town LIKE '%" . $this->db->escape($town) . "%'";
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


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(503, 'Error when retrieving list of towns : '.$this->db->lasterror());
        }

        return $list;
    }

    /**
     * Get the list of payments terms.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number {@min 0}
     * @param int       $active     Payment term is active or not {@min 0} {@max 1}
     * @param string    $sqlfilters SQL criteria to filter. Syntax example "(t.code:=:'CHQ')"
     *
     * @url     GET dictionary/payment_terms
     *
     * @return array List of payment terms
     *
     * @throws 400 RestException
     * @throws 200 OK
     */
    function getPaymentTerms($sortfield = "sortorder", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
    {
        $list = array();

        $sql = "SELECT rowid as id, code, sortorder, libelle as label, libelle_facture as descr, type_cdr, nbjour, decalage, module";
        $sql.= " FROM ".MAIN_DB_PREFIX."c_payment_term as t";
        $sql.= " WHERE t.active = ".$active;
        // Add sql filters
        if ($sqlfilters)
        {
            if (! DolibarrApi::_checkFilters($sqlfilters))
            {
                throw new RestException(400, 'Error when validating parameter sqlfilters '.$sqlfilters);
            }
                $regexstring='\(([^:\'\(\)]+:[^:\'\(\)]+:[^:\(\)]+)\)';
            $sql.=" AND (".preg_replace_callback('/'.$regexstring.'/', 'DolibarrApi::_forge_criteria_callback', $sqlfilters).")";
        }


        $sql.= $this->db->order($sortfield, $sortorder);

        if ($limit) {
            if ($page < 0) {
                $page = 0;
            }
            $offset = $limit * $page;

            $sql .= $this->db->plimit($limit, $offset);
        }

        $result = $this->db->query($sql);

        if ($result) {
            $num = $this->db->num_rows($result);
            $min = min($num, ($limit <= 0 ? $num : $limit));
            for ($i = 0; $i < $min; $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(400, $this->db->lasterror());
        }

        return $list;
    }

}
