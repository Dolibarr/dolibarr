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
 * API class for towns (content of the ziptown dictionary)
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class DictionnaryTowns extends DolibarrApi
{
    /**
     * Constructor
     */
    function __construct()
    {
        global $db;
        $this->db = $db;
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
     * @throws RestException
     */
    function index($sortfield = "zip,town", $sortorder = 'ASC', $limit = 100, $page = 0, $zipcode = '', $town = '', $sqlfilters = '')
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
            for ($i = 0; $i < min($num, ($limit <= 0 ? $num : $limit)); $i++) {
                $list[] = $this->db->fetch_object($result);
            }
        } else {
            throw new RestException(503, 'Error when retrieving list of towns : '.$this->db->lasterror());
        }
        
        return $list;
    }

}
