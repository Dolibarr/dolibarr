<?php
/* Copyright (C) 2017	Regis Houssin	<regis.houssin@capnetworks.com>
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

//require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

/**
 * API class for extra fields (content of the extrafields)
 *
 * @access protected
 * @class DolibarrApiAccess {@requires user,external}
 */
class DictionaryExtraFields extends DolibarrApi
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
     * Get the list of extra fields.
     *
     * @param string	$sortfield	Sort field
     * @param string	$sortorder	Sort order
     * @param string    $type       Type of element ('adherent', 'commande', 'thirdparty', 'facture', 'propal', 'product', ...)
     * @param string    $sqlfilters Other criteria to filter answers separated by a comma. Syntax example "(t.label:like:'SO-%')"
     * @return List of events types
     *
     * @throws RestException
     */
    function index($sortfield = "t.pos", $sortorder = 'ASC', $type = '', $sqlfilters = '')
    {
        $list = array();

        if ($type == 'thirdparty') $type='societe';
        if ($type == 'contact') $type='socpeople';

        $sql = "SELECT t.rowid, t.name, t.label, t.type, t.size, t.elementtype, t.fieldunique, t.fieldrequired, t.param, t.pos, t.alwayseditable, t.perms, t.list, t.ishidden, t.fielddefault, t.fieldcomputed";
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
        			$list[$tab->elementtype][$tab->name]['ishidden']=$tab->ishidden;
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

}
