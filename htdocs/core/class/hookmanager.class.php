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

    // Context hookmanager was created for ('thirdpartycard', 'thirdpartydao', ...)
    var $contextarray=array();

	// Array with instantiated classes
	var $hooks=array();

	/**
	 * Constructor
	 *
	 * @param	DoliDB	$DB		Handler acces base de donnees
	 */
	function HookManager($DB)
	{
		$this->db = $DB;
	}


	/**
	 *	Init array this->hooks with instantiated action controlers.
	 *
	 *  First, a hook is declared by a module by adding a constant MAIN_MODULE_MYMODULENAME_HOOKS
	 *  with value 'nameofcontext1:nameofcontext2:...' into $this->const of module descriptor file.
	 *  This make conf->hooks_modules loaded with an entry ('modulename'=>array(nameofcontext1,nameofcontext2,...))
	 *  When this function is called by callHooks(list_of_contexts), an array this->hooks is defined with instance of controler
	 *  class found into file /mymodule/class/actions_mymodule.class.php (if module has declared the context as a managed context).
	 *  Then when a hook is executeHook('aMethod'...) is called, the method aMethod found into class will be executed.
	 *
	 *	@param	array	$arraytype	    Array list of searched hooks tab/features. For example: 'thirdpartycard' (for hook methods into page card thirdparty), 'thirdpartydao' (for hook methods into Societe), ...
	 *	@return	int						Always 1
	 */
	function callHooks($arraytype)
	{
		global $conf;

		// Test if there is hooks to manage
        if (! is_array($conf->hooks_modules) || empty($conf->hooks_modules)) return;

        // For backward compatibility
		if (! is_array($arraytype)) $arraytype=array($arraytype);

		$this->contextarray=array_merge($arraytype,$this->contextarray);

		$i=0;
		foreach($conf->hooks_modules as $module => $hooks)
		{
			if ($conf->$module->enabled)
			{
				foreach($arraytype as $type)
				{
					if (in_array($type,$hooks))    // We instantiate action class only if hook is required
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
     *
     * 		@param		string	$method			Name of method hooked ('doActions', 'printSearchForm', 'showInputField', ...)
     * 	    @param		array	$parameters		Array of parameters
     * 		@param		Object	&$object		Object to use hooks on
     * 	    @param		string	&$action		Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
     * 		@return		mixed					For doActions,showInputField,showOutputField: Return 0 if we want to keep standard actions, >0 if if want to stop standard actions, <0 means KO.
     * 											For printSearchForm,printLeftBlock:           Return HTML string.
     * 											$this->error or this->errors are also defined by class called by this function if error.
     */
	function executeHooks($method, $parameters=false, &$object='', &$action='')
	{
		global $var;

        if (! is_array($this->hooks) || empty($this->hooks)) return '';

        $parameters['context']=join(':',$this->contextarray);
        dol_syslog(get_class($this).'::executeHooks method='.$method." action=".$action." context=".$parameters['context']);

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
