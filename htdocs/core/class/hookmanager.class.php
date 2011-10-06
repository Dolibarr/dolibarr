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
	 *	Init array this->hooks with instantiated controler
	 *
	 *  First, a hook is declared by a module by adding a constant MAIN_MODULE_MYMODULENAME_HOOKS
	 *  with value nameofhookkey1:nameofhookkey2:...:nameofhookkeyn.
	 *  This add into conf->hooks_modules an entrie ('modulename'=>nameofhookkey)
	 *  Then, when this function is called, an array this->hooks is defined with instance of controler
	 *  classes that support the hook called
	 *
	 *	@param	    arraytype	    Array list of searched hooks tab/features. For example: 'thirdpartytab', 'thirdparty',...
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
						$path 		= '/'.$module.'/class/';
						$actionfile = 'actions_'.$module.'.class.php';
						$pathroot	= '';

						$this->hooks[$i]['type']=$type;

						// Include actions class overwriting hooks
						$resaction=dol_include_once($path.$actionfile);
						if ($resaction)
						{
    						$controlclassname = 'Actions'.ucfirst($module);
    						$actionInstance = new $controlclassname($this->db);
    						$this->hooks[$i]['modules'][$module] = $actionInstance;
						}

						// Include dataservice class (model)
						// TODO storing dao is useless here. It's goal of controller to known which dao to manage
						$daofile 	= 'dao_'.$module.'.class.php';
						$resdao=dol_include_once($path.$daofile);
						if ($resdao)
						{
							// Instantiate dataservice class (model)
							$daoInstance = 'Dao'.ucfirst($module);
							$this->hooks[$i]['modules'][$module]->object = new $daoInstance($this->db);
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
     * 	    @param		parameters	Array of parameters
     * 	    @param		action		Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
     * 		@param		object		Object to use hooks on
     * 		@param		string		For doActions,showInputField,showOutputField: Return 0 if we want to keep doing standard actions, >0 if if want to stop standard actions, >0 means KO.
     * 								For printSearchForm,printLeftBlock:           Return HTML string.
     * 								$this->error or this->errors are also defined with hooks errors.
     */
	function executeHooks($method, $parameters=false, &$object='', &$action='')
	{
		global $var;

        if (! is_array($this->hooks) || empty($this->hooks)) return '';

        dol_syslog(get_class($this).'::executeHooks method='.$method." action=".$action);

        // Loop on each hook
        $resaction=0; $resprint='';
        foreach($this->hooks as $hook)
        {
            if (! empty($hook['modules']))
            {
                foreach($hook['modules'] as $module => $actioninstance)
                {
                	$var=!$var;

                    // Hooks that return int
                    if ($method == 'doActions' && method_exists($actioninstance,$method))
                    {
                        $resaction+=$actioninstance->doActions($parameters, $object, $action); // action can be changed by method (to go back to other action for example), socid can be changed/set by method (during creation for example)
                        if ($resaction < 0 || ! empty($actioninstance->error) || (! empty($actioninstance->errors) && count($actioninstance->errors) > 0))
                        {
                            $this->error=$actioninstance->error; $this->errors=$actioninstance->errors;
                            if ($action=='add')    $action='create';    // TODO this change must be inside the doActions
                            if ($action=='update') $action='edit';      // TODO this change must be inside the doActions
                        }
                    }
                    else if ($method == 'showInputFields' && method_exists($actioninstance,$method))
                    {
                        $resaction+=$actioninstance->showInputFields($parameters, $object, $action); // action can be changed by method (to go back to other action for example), socid can be changed/set by method (during creation for example)
                        if ($resaction < 0 || ! empty($actioninstance->error) || (! empty($actioninstance->errors) && count($actioninstance->errors) > 0))
                        {
                            $this->error=$actioninstance->error; $this->errors=$actioninstance->errors;
                        }
                    }
                    else if ($method == 'showOutputFields' && method_exists($actioninstance,$method))
                    {
                        $resaction+=$actioninstance->showOutputFields($parameters, $object, $action); // action can be changed by method (to go back to other action for example), socid can be changed/set by method (during creation for example)
                        if ($resaction < 0 || ! empty($actioninstance->error) || (! empty($actioninstance->errors) && count($actioninstance->errors) > 0))
                        {
                            $this->error=$actioninstance->error; $this->errors=$actioninstance->errors;
                        }
                    }
                    // Generic hooks that return a string (printSearchForm, printLeftBlock, formBuilddocOptions, ...)
                    else if (method_exists($actioninstance,$method))
                    {
                        if (is_array($parameters) && $parameters['special_code'] > 3 && $parameters['special_code'] != $actioninstance->module_number) continue;
                    	$resprint.=$actioninstance->$method($parameters, $object, $action, $this);
                    }
                }
            }
        }

        if ($method == 'doActions' || $method == 'showInputFields' || $method == 'showOutputFields') return $resaction;
        return $resprint;
	}

}

?>
