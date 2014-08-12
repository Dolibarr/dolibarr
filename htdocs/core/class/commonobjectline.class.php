<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
 *	\file       htdocs/core/class/commonobjectline.class.php
 *  \ingroup    core
 *  \brief      File of the superclass of classes of lines of business objects (invoice, contract, PROPAL, commands, etc. ...)
 */


/**
 *  Parent class for class inheritance lines of business objects
 *  This class is useless for the moment so no inherit are done on it
 */
abstract class CommonObjectLine
{
    /**
     * Call trigger based on this instance
     * NB: Error from trigger are stacked in interface->errors
     * NB2: If return code of triggers are < 0, action calling trigger should cancel all transaction.
     *
     * @param   string    $trigger_name   trigger's name to execute
     * @param   User      $user           Object user
     * @return  int                       Result of run_triggers
     */
    function call_trigger($trigger_name, $user)
    {
    	global $langs,$conf;

    	include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
    	$interface=new Interfaces($this->db);
    	$result=$interface->run_triggers($trigger_name,$this,$user,$langs,$conf);
    	if ($result < 0)
    	{
    		if (!empty($this->errors))
    		{
    			$this->errors=array_merge($this->errors,$interface->errors);
    		}
    		else
    		{
    			$this->errors=$interface->errors;
    		}
    	}
    	return $result;
    }
}

