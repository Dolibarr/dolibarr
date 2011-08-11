<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 *	\file       htdocs/core/class/hookmanager.class.php
 *	\ingroup    core
 *	\brief      File of class to manage hooks
 *	\version    $Id: hookmanager.class.php,v 1.3 2011/08/10 17:40:45 hregis Exp $
 */


/**
 *	\class 		HookManager
 *	\brief 		Class to manage hooks
 */

class HookManager
{
	var $db;

	var $linkedObjectBlock;
	var $objectid;

	// Array with instantiated classes
	var $hooks=array();

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function HookManager($DB)
	{
		$this->db = $DB;
	}


	/**
	 *	Init array this->hooks with instantiated controler and/or dao
	 *	@param	    arraytype	    Array list of hooked tab/features. For example: thirdpartytab, ...
	 *	@return		int				Always 1
	 */
	function callHooks($arraytype)
	{
		global $conf;

		// Test if ther is hooks to manage
        if (! is_array($conf->hooks_modules) || empty($conf->hooks_modules)) return;

        // For backward compatibility
		if (! is_array($arraytype)) $arraytype=array($arraytype);

		$i=0;
		foreach($conf->hooks_modules as $module => $hooks)
		{
			if ($conf->$module->enabled)
			{
				foreach($arraytype as $type)
				{
					if (in_array($type,$hooks))
					{
						$path		= $module;
						if ($module == 'adherent') $path = 'adherents';
						$path 		= '/'.$path.'/class/';
						$actionfile = 'actions_'.$module.'.class.php';
						$pathroot	= '';

						$this->hooks[$i]['type']=$type;

						// Include actions class overwriting hooks
						$resaction=dol_include_once($path.$actionfile);
						if ($resaction)
						{
    						$controlclassname = 'Actions'.ucfirst($module);
    						$objModule = new $controlclassname($this->db);
    						$this->hooks[$i]['modules'][$objModule->module_number] = $objModule;
						}
						
						// Include dataservice class (model)
						// TODO storing dao is useless here. It's goal of controller to known which dao to manage
						$daofile 	= 'dao_'.$module.'.class.php';
						$resdao=dol_include_once($path.$daofile);
						if ($resdao)
						{
							// Instantiate dataservice class (model)
							$modelclassname = 'Dao'.ucfirst($module);
							$this->hooks[$i]['modules'][$objModule->module_number]->object = new $modelclassname($this->db);
						}

                        $i++;
					}
				}
			}
		}
		return 1;
	}

    /**
     * 		Execute hooks (if the were initialized) for the given method
     * 		@param		method		Method name to hook ('doActions', 'printSearchForm', ...)
     * 	    @param		action		Action code ('create', 'edit', 'view', 'add', 'update', 'delete'...)
     * 		@param		object		Object to use hooks on
     * 	    @param		id			Id.
     * 		@param		string		For doActions,showInputField,showOutputField: Return 0 if we want to keep doing standard actions, >0 if if want to stop standard actions, >0 means KO.
     * 								For printSearchForm,printLeftBlock:           Return HTML string.
     * 								$this->error or this->errors are also defined with hooks errors.
     */
	function executeHooks($method, $action='', &$object='', $id='', $parameters=false)
	{
		global $var;
		
        if (! is_array($this->hooks) || empty($this->hooks)) return '';

        // Loop on each hook
        $resaction=0; $resprint='';
        foreach($this->hooks as $hook)
        {
            if (! empty($hook['modules']))
            {
                foreach($hook['modules'] as $module)
                {
                	$var=!$var;
                	
                    // Hooks that return int
                    if ($method == 'doActions' && method_exists($module,$method))
                    {
                        $restmp+=$module->doActions($object, $action, $id); // action can be changed by method (to go back to other action for example), socid can be changed/set by method (during creation for example)
                        if ($restmp < 0 || ! empty($module->error) || (! empty($module->errors) && sizeof($module->errors) > 0))
                        {
                            $this->error=$module->error; $this->errors=$module->errors;
                            if ($action=='add')    $action='create';    // TODO this change must be inside the doActions
                            if ($action=='update') $action='edit';      // TODO this change must be inside the doActions
                        }
                        else
                        {
                            $resaction+=$restmp;
                        }
                    }
                    else if ($method == 'showInputFields' && method_exists($module,$method))
                    {
                        $restmp+=$module->showInputFields($object, $action, $id); // action can be changed by method (to go back to other action for example), socid can be changed/set by method (during creation for example)
                        if ($restmp < 0 || ! empty($module->error) || (! empty($module->errors) && sizeof($module->errors) > 0))
                        {
                            $this->error=$module->error; $this->errors=$module->errors;
                        }
                        else
                        {
                            $resaction+=$restmp;
                        }
                    }
                    else if ($method == 'showOutputFields' && method_exists($module,$method))
                    {
                        $restmp+=$module->showOutputFields($object, $id); // action can be changed by method (to go back to other action for example), socid can be changed/set by method (during creation for example)
                        if ($restmp < 0 || ! empty($module->error) || (! empty($module->errors) && sizeof($module->errors) > 0))
                        {
                            $this->error=$module->error; $this->errors=$module->errors;
                        }
                        else
                        {
                            $resaction+=$restmp;
                        }
                    }
                    // Hooks that return a string
                    else if ($method == 'printSearchForm' && method_exists($module,$method))
                    {
                        $resprint.='<!-- Begin search form hook area -->'."\n";
                        $resprint.=$module->printSearchForm($object, $action, $id); // action can be changed by method (to go back to other action for example), socid can be changed/set by method (during creation for example)
                        $resprint.="\n".'<!-- End of search form hook area -->'."\n";
                    }
                    else if ($method == 'printLeftBlock' && method_exists($module,$method))
                    {
                        $resprint.='<!-- Begin left block hook area -->'."\n";
                        $resprint.=$module->printLeftBlock($object, $action, $id); // action can be changed by method (to go back to other action for example), socid can be changed/set by method (during creation for example)
                        $resprint.="\n".'<!-- End of left block hook area -->'."\n";
                    }
                    // Hook generic
                    else if (method_exists($module,$method))
                    {
                    	if (is_array($parameters) && $parameters['special_code'] > 3 && $parameters['special_code'] != $module->module_number) continue;
                    	$resprint.=$module->$method($object, $action, $id, $parameters, $this);
                    }
                }
            }
        }

        if ($method == 'doActions' || $method == 'showInputFields' || $method == 'showOutputFields') return $resaction;
        return $resprint;
	}

}

?>
