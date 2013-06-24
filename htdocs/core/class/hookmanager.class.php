<?php
/* Copyright (C) 2010-2012 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
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
 *	\file       htdocs/core/class/hookmanager.class.php
 *	\ingroup    core
 *	\brief      File of class to manage hooks
 */


/**
 *	Class to manage hooks
 */
class HookManager
{
	var $db;
	var $error;
	var $errors=array();

    // Context hookmanager was created for ('thirdpartycard', 'thirdpartydao', ...)
    var $contextarray=array();

	// Array with instantiated classes
	var $hooks=array();

	// Array result
	var $resArray=array();
	// Printable result
	var $resPrint='';

	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Handler acces base de donnees
	 */
	function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	Init array this->hooks with instantiated action controlers.
	 *  First, a hook is declared by a module by adding a constant MAIN_MODULE_MYMODULENAME_HOOKS
	 *  with value 'nameofcontext1:nameofcontext2:...' into $this->const of module descriptor file.
	 *  This make conf->hooks_modules loaded with an entry ('modulename'=>array(nameofcontext1,nameofcontext2,...))
	 *  When initHooks function is called, with initHooks(list_of_contexts), an array this->hooks is defined with instance of controler
	 *  class found into file /mymodule/class/actions_mymodule.class.php (if module has declared the context as a managed context).
	 *  Then when a hook is executeHook('aMethod'...) is called, the method aMethod found into class will be executed.
	 *
	 *	@param	array	$arraycontext	    Array list of searched hooks tab/features. For example: 'thirdpartycard' (for hook methods into page card thirdparty), 'thirdpartydao' (for hook methods into Societe), ...
	 *	@return	int							Always 1
	 */
	function initHooks($arraycontext)
	{
		global $conf;

		// Test if there is hooks to manage
        if (! is_array($conf->modules_parts['hooks']) || empty($conf->modules_parts['hooks'])) return;

        // For backward compatibility
		if (! is_array($arraycontext)) $arraycontext=array($arraycontext);

		$this->contextarray=array_unique(array_merge($arraycontext,$this->contextarray));    // All contexts are concatenated

		foreach($conf->modules_parts['hooks'] as $module => $hooks)
		{
			if ($conf->$module->enabled)
			{
				foreach($arraycontext as $context)
				{
				    if (is_array($hooks)) $arrayhooks=$hooks;    // New system
				    else $arrayhooks=explode(':',$hooks);        // Old system (for backward compatibility)

					if (in_array($context,$arrayhooks))    // We instantiate action class only if hook is required
					{
						$path 		= '/'.$module.'/class/';
						$actionfile = 'actions_'.$module.'.class.php';
						$pathroot	= '';

						// Include actions class overwriting hooks
						$resaction=dol_include_once($path.$actionfile);
						if ($resaction)
						{
    						$controlclassname = 'Actions'.ucfirst($module);
    						$actionInstance = new $controlclassname($this->db);
    						$this->hooks[$context][$module] = $actionInstance;
						}
					}
				}
			}
		}
		return 1;
	}

    /**
     * 		Execute hooks (if they were initialized) for the given method
     *
     * 		@param		string	$method			Name of method hooked ('doActions', 'printSearchForm', 'showInputField', ...)
     * 	    @param		array	$parameters		Array of parameters
     * 		@param		Object	&$object		Object to use hooks on
     * 	    @param		string	&$action		Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
     * 		@return		mixed					For doActions,formObjectOptions,pdf_xxx:    								Return 0 if we want to keep standard actions, >0 if if want to stop standard actions, <0 means KO.
     * 											For printSearchForm,printLeftBlock,printTopRightMenu,formAddObjectLine,...: Return HTML string. TODO Deprecated. Must always return an int and things to print into ->resprints.
     *                                          Can also return some values into an array ->results.
     * 											$this->error or this->errors are also defined by class called by this function if error.
     */
	function executeHooks($method, $parameters=false, &$object='', &$action='')
	{
        if (! is_array($this->hooks) || empty($this->hooks)) return '';

        $parameters['context']=join(':',$this->contextarray);
        dol_syslog(get_class($this).'::executeHooks method='.$method." action=".$action." context=".$parameters['context']);

        // Define type of hook ('output', 'returnvalue' or 'addreplace'). 'addreplace' should be type for all hooks. 'output' and 'returnvalue' are deprecated.
        $hooktype='output';
        if (preg_match('/^pdf_/',$method)) $hooktype='returnvalue';	// pdf_xxx except pdf_writelinedesc are returnvalue hooks. When there is 2 hooks of this type, only last one win.
        if (in_array($method,array('doActions','formObjectOptions','moveUploadedFile','pdf_writelinedesc','paymentsupplierinvoices','printSearchForm'))) $hooktype='addreplace';

        // Loop on each hook to qualify modules that declared context
        $modulealreadyexecuted=array();
        $resaction=0; $error=0;
		$this->resPrint=''; $this->resArray=array();
        foreach($this->hooks as $modules)    // this->hooks is an array with context as key and value is an array of modules that handle this context
        {
            if (! empty($modules))
            {
                foreach($modules as $module => $actionclassinstance)
                {
                	//print "Before hook ".get_class($actionclassinstance)." method=".$method." results=".count($actionclassinstance->results)." resprints=".count($actionclassinstance->resprints)." result=".$result." resaction=".$resaction."<br>\n";

                	//print 'class='.get_class($actionclassinstance).' method='.$method.' action='.$action;
                	// jump to next class if method does not exists
                    if (! method_exists($actionclassinstance,$method)) continue;
                	// test to avoid to run twice a hook, when a module implements several active contexts
                    if (in_array($module,$modulealreadyexecuted)) continue;
                    $modulealreadyexecuted[$module]=$module;
                    // Hooks that return int (doActions, formObjectOptions, pdf_writelinedesc, paymentsupplierinvoices)
                    if ($hooktype == 'addreplace')
                    {
                    	$resaction += $actionclassinstance->$method($parameters, $object, $action, $this); // $object and $action can be changed by method ($object->id during creation for example or $action to go back to other action for example)
                    	if ($resaction < 0 || ! empty($actionclassinstance->error) || (! empty($actionclassinstance->errors) && count($actionclassinstance->errors) > 0))
                    	{
                    		$error++;
                    		$this->error=$actionclassinstance->error; $this->errors=array_merge($this->errors, (array) $actionclassinstance->errors);

                    		// TODO remove this. Change must be inside the method of hook if required
                    		if ($method == 'doActions')
                    		{
                    			if ($action=='add')    $action='create';
                    			if ($action=='update') $action='edit';
                    		}
                    	}

                    	if (is_array($actionclassinstance->results))  $this->resArray =array_merge($this->resArray, $actionclassinstance->results);
                    	if (! empty($actionclassinstance->resprints)) $this->resPrint.=$actionclassinstance->resprints;
                    }
                    // Generic hooks that return a string or array (printSearchForm, printLeftBlock, printTopRightMenu, formAddObjectLine, formBuilddocOptions, ...)
                    else
					{
                    	// TODO. this should be done into the method of hook by returning nothing
                    	if (is_array($parameters) && ! empty($parameters['special_code']) && $parameters['special_code'] > 3 && $parameters['special_code'] != $actionclassinstance->module_number) continue;

                    	$result = $actionclassinstance->$method($parameters, $object, $action, $this); // $object and $action can be changed by method ($object->id during creation for example or $action to go back to other action for example)

                    	if (! empty($actionclassinstance->results) && is_array($actionclassinstance->results)) $this->resArray =array_merge($this->resArray, $actionclassinstance->results);
                    	if (! empty($actionclassinstance->resprints)) $this->resPrint.=$actionclassinstance->resprints;

                    	// TODO. remove this. array result must be set into $actionclassinstance->results
                    	if (is_array($result)) $this->resArray = array_merge($this->resArray, $result);
                    	// TODO. remove this. result must not be a string. we must use $actionclassinstance->resprint to return a string
                    	if (! is_array($result) && ! is_numeric($result)) $this->resPrint.=$result;
                    }

                    //print "After hook  ".get_class($actionclassinstance)." method=".$method." results=".count($actionclassinstance->results)." resprints=".count($actionclassinstance->resprints)." result=".$result." resaction=".$resaction."<br>\n";

                    unset($actionclassinstance->results);
                    unset($actionclassinstance->resprints);
                }
            }
        }

        // TODO remove this. When there is something to print for an output hook, ->resPrint is filled.
        if ($hooktype == 'output') return $this->resPrint;
		if ($hooktype == 'returnvalue') return $result;
        return ($error?-1:$resaction);
	}

}

?>
