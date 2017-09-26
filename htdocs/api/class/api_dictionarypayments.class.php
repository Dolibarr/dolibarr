<?php
/* Copyright (C) 2017	Neil Orley	<neil.orley@oeris.fr>
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

/**
 * API class for payment type (content of the paiement dictionary)
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class DictionaryPayments extends DolibarrApi
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
     * Get the list of payments types.
     *
     * @param string    $sortfield  Sort field
     * @param string    $sortorder  Sort order
     * @param int       $limit      Number of items per page
     * @param int       $page       Page number {@min 0}
     * @param int       $active     Payment type is active or not {@min 0} {@max 1}
     * @param string    $sqlfilters SQL criteria to filter. Syntax example "(t.code:=:'CHQ')"
     * @return List of payment types
     *
     * @throws RestException
     */
    function index($sortfield = "code", $sortorder = 'ASC', $limit = 100, $page = 0, $active = 1, $sqlfilters = '')
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
            throw new RestException(503, 'Error when retrieving list of payment types : '.$this->db->lasterror());
        }

        return $list;
    }

}
