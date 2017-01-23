<?php
/* Copyright (C) 2016   Xebax Christy           <xebax@wanadoo.fr>
 * Copyright (C) 2016	Laurent Destailleur		<eldy@users.sourceforge.net>
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
 * API class for countries
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class DictionnaryCountries extends DolibarrApi
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
     * @throws RestException
     */
    function index($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $filter = '', $lang = '', $sqlfilters = '')
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
            for ($i = 0; $i < min($num, ($limit <= 0 ? $num : $limit)); $i++) {
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
     * @throws RestException
     */
    function get($id, $lang = '')
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
     * Clean sensible object datas
     *
     * @param object    $object    Object to clean
     * @return array Array of cleaned object properties
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
}
