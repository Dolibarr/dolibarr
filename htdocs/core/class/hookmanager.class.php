<?php
/* Copyright (C) 2010-2016 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2010-2014 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2010-2011 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
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
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[] Error codes (or messages)
	 */
	public $errors = array();

	/**
	 * @var string[] Context hookmanager was created for ('thirdpartycard', 'thirdpartydao', ...)
	 */
	public $contextarray = array();

	/**
	 * array<string,array<string,null|string|CommonHookActions>> 	Array with instantiated classes
	 */
	public $hooks = array();

	/**
	 * array<string,array<string,null|string|CommonHookActions>> 	Array with instantiated classes sorted by hook priority
	 */
	public $hooksSorted = array();

	/**
	 * @var array<string,array{name:string,contexts:string[],file:string,line:string,count:int}> 	List of hooks called during this request (key = hash)
	 */
	public $hooksHistory = [];

	/**
	 * @var mixed[] Result
	 */
	public $resArray = array();

	/**
	 * @var string Printable result
	 */
	public $resPrint = '';

	/**
	 * @var int Nb of qualified hook ran
	 */
	public $resNbOfHooks = 0;

	/**
	 * Constructor
	 *
	 * @param	DoliDB		$db		Database handler
	 * @return void
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 *	Init array $this->hooks with instantiated action controllers.
	 *  First, a hook is declared by a module by adding a constant MAIN_MODULE_MYMODULENAME_HOOKS with value 'nameofcontext1:nameofcontext2:...' into $this->const of module descriptor file.
	 *  This makes $conf->hooks_modules loaded with an entry ('modulename'=>array(nameofcontext1,nameofcontext2,...))
	 *  When initHooks function is called, with initHooks(list_of_contexts), an array $this->hooks is defined with instance of controller
	 *  class found into file /mymodule/class/actions_mymodule.class.php (if module has declared the context as a managed context).
	 *  Then when a hook executeHooks('aMethod'...) is called, the method aMethod found into class will be executed.
	 *
	 *	@param	string[]	$arraycontext	    Array list of context hooks to activate. For example: 'thirdpartycard' (for hook methods into page card thirdparty), 'thirdpartydao' (for hook methods into Societe), ...
	 *	@return	int<0,1>						0 or 1
	 */
	public function initHooks($arraycontext)
	{
		global $conf;

		// Test if there is at least one hook to manage
		if (!is_array($conf->modules_parts['hooks']) || empty($conf->modules_parts['hooks'])) {
			return 0;
		}

		// For backward compatibility
		if (!is_array($arraycontext)) {
			$arraycontext = array($arraycontext);
		}

		$this->contextarray = array_unique(array_merge($arraycontext, $this->contextarray)); // All contexts are concatenated but kept unique

		$foundcontextmodule = false;

		// Loop on each module that bring hooks. Add an entry into $arraytolog if we found a module that ask to act in the context $arraycontext
		foreach ($conf->modules_parts['hooks'] as $module => $hooks) {
			if (!isModEnabled($module)) {
				continue;
			}

			//dol_syslog(get_class($this).'::initHooks module='.$module.' arraycontext='.join(',',$arraycontext));
			foreach ($arraycontext as $context) {
				if (is_array($hooks)) {
					$arrayhooks = $hooks; // New system = array of hook contexts claimed by the module $module
				} else {
					$arrayhooks = explode(':', $hooks); // Old system (for backward compatibility)
				}

				if (in_array($context, $arrayhooks) || in_array('all', $arrayhooks)) {    // We instantiate action class only if initialized hook is handled by the module
					// Include actions class overwriting hooks
					if (empty($this->hooks[$context][$module]) || !is_object($this->hooks[$context][$module])) {	// If set to an object value, class was already loaded so we do nothing.
						$path = '/'.$module.'/class/';
						$actionfile = 'actions_'.$module.'.class.php';

						$resaction = dol_include_once($path.$actionfile);
						if ($resaction) {
							$controlclassname = 'Actions'.ucfirst($module);

							$actionInstance = new $controlclassname($this->db);
							'@phan-var-force CommonHookActions $actionInstance';


							$priority = empty($actionInstance->priority) ? 50 : $actionInstance->priority;

							$this->hooks[$context][$module] = $actionInstance;
							$this->hooksSorted[$context][$priority.':'.$module] = $actionInstance;

							$foundcontextmodule = true;

							// Hook has been initialized with another couple $context/$module
							$stringtolog = 'context='.$context.'-path='.$path.$actionfile.'-priority='.$priority;
							dol_syslog(get_class($this)."::initHooks Loading hooks: ".$stringtolog, LOG_DEBUG);
						} else {
							dol_syslog(get_class($this)."::initHooks Failed to load hook in ".$path.$actionfile, LOG_WARNING);
						}
					} else {
						// Hook was already initialized for this context and module
					}
				}
			}
		}

		// Log the init of hook
		// dol_syslog(get_class($this)."::initHooks Loading hooks: ".implode(', ', $arraytolog), LOG_DEBUG);

		if ($foundcontextmodule) {
			foreach ($arraycontext as $context) {
				if (!empty($this->hooksSorted[$context])) {
					ksort($this->hooksSorted[$context], SORT_NATURAL);
				}
			}
		}

		return 1;
	}

	/**
	 *  Execute hooks (if they were initialized) for the given method
	 *
	 *  @param		string	$method			Name of method hooked ('doActions', 'printSearchForm', 'showInputField', ...)
	 *  @param		array<string,mixed>	$parameters		Array of parameters
	 *  @param		object	$object			Object to use hooks on
	 *  @param		string	$action			Action code on calling page ('create', 'edit', 'view', 'add', 'update', 'delete'...)
	 *  @return		int<-1,1>				For 'addreplace' hooks (doActions, formConfirm, formObjectOptions, pdf_xxx,...): 	Return 0 if we want to keep standard actions, >0 if we want to stop/replace standard actions, <0 if KO. Things to print are returned into ->resprints and set into ->resPrint. Things to return are returned into ->results by hook and set into ->resArray for caller.
	 *                                      For 'output' hooks (printLeftBlock, formAddObjectLine, formBuilddocOptions, ...):	Return 0 if we want to keep standard actions, >0 uf we want to stop/replace standard actions (at least one > 0 and replacement will be done), <0 if KO. Things to print are returned into ->resprints and set into ->resPrint. Things to return are returned into ->results by hook and set into ->resArray for caller.
	 *                                      All types can also return some values into an array ->results that will be merged into this->resArray for caller.
	 *                                      $this->error or this->errors are also defined by class called by this function if error.
	 */
	public function executeHooks($method, $parameters = array(), &$object = null, &$action = '')
	{
		//global $debugbar;
		//if (is_object($debugbar) && get_class($debugbar) === 'DolibarrDebugBar') {
		if (isModEnabled('debugbar') && function_exists('debug_backtrace')) {
			$trace = debug_backtrace();
			if (isset($trace[0])) {
				$hookInformations = [
					'name' => $method,
					'contexts' => $this->contextarray,
					'file' => $trace[0]['file'],
					'line' => $trace[0]['line'],
					'count' => 0,
				];
				$hash = md5(json_encode($hookInformations));
				if (!empty($this->hooksHistory[$hash])) {
					$this->hooksHistory[$hash]['count']++;
				} else {
					$hookInformations['count'] = 1;
					$this->hooksHistory[$hash] = $hookInformations;
				}
			}
		}

		if (!is_array($this->hooks) || empty($this->hooks)) {
			return 0; // No hook available, do nothing.
		}
		if (!is_array($parameters)) {
			dol_syslog('executeHooks was called with a non array $parameters. Surely a bug.', LOG_WARNING);
			$parameters = array();
		}

		$parameters['context'] = implode(':', $this->contextarray);
		//dol_syslog(get_class($this).'::executeHooks method='.$method." action=".$action." context=".$parameters['context']);

		// Define type of hook ('output' or 'addreplace').
		$hooktype = 'addreplace';
		// TODO Remove hooks with type 'output' (example createFrom). All hooks must be converted into 'addreplace' hooks.
		if (in_array($method, array(
			'createFrom',
			'dashboardAccountancy',
			'dashboardActivities',
			'dashboardCommercials',
			'dashboardContracts',
			'dashboardDonation',
			'dashboardEmailings',
			'dashboardExpenseReport',
			'dashboardHRM',
			'dashboardInterventions',
			'dashboardMRP',
			'dashboardMembers',
			'dashboardOpensurvey',
			'dashboardOrders',
			'dashboardOrdersSuppliers',
			'dashboardProductServices',
			'dashboardProjects',
			'dashboardPropals',
			'dashboardSpecialBills',
			'dashboardSupplierProposal',
			'dashboardThirdparties',
			'dashboardTickets',
			'dashboardUsersGroups',
			'dashboardWarehouse',
			'dashboardWarehouseReceptions',
			'dashboardWarehouseSendings',
			'insertExtraHeader',
			'insertExtraFooter',
			'printLeftBlock',
			'formAddObjectLine',
			'formBuilddocOptions',
			'showSocinfoOnPrint'
		))) {
			$hooktype = 'output';
		}

		// Init return properties
		$localResPrint = '';
		$localResArray = array();

		$this->resNbOfHooks = 0;

		// Here, the value for $method and $hooktype are given.
		// Loop on each hook to qualify modules that have declared context
		$modulealreadyexecuted = array();
		$resaction = 0;
		$error = 0;
		foreach ($this->hooksSorted as $context => $modules) {    // $this->hooks is an array with the context as key and the value is an array of modules that handle this context
			if (!empty($modules)) {
				'@phan-var-force array<string,CommonHookActions> $modules';
				// Loop on each active hooks of module for this context
				foreach ($modules as $module => $actionclassinstance) {
					$module = preg_replace('/^\d+:/', '', $module);		// $module string is 'priority:module'
					//print "Before hook ".get_class($actionclassinstance)." method=".$method." module=".$module." hooktype=".$hooktype." results=".count($actionclassinstance->results)." resprints=".count($actionclassinstance->resprints)." resaction=".$resaction."<br>\n";

					// test to avoid running twice a hook, when a module implements several active contexts
					if (in_array($module, $modulealreadyexecuted)) {
						continue;
					}

					// jump to next module/class if method does not exist
					if (!method_exists($actionclassinstance, $method)) {
						continue;
					}

					$this->resNbOfHooks++;

					$modulealreadyexecuted[$module] = $module;

					// Clean class (an error may have been set from a previous call of another method for same module/hook)
					$actionclassinstance->error = '';
					$actionclassinstance->errors = array();

					if (getDolGlobalInt('MAIN_HOOK_DEBUG')) {
						// This his too much verbose, enabled if const enabled only
						dol_syslog(get_class($this)."::executeHooks Qualified hook found (hooktype=".$hooktype."). We call method ".get_class($actionclassinstance).'->'.$method.", context=".$context.", module=".$module.", action=".$action.((is_object($object) && property_exists($object, 'id')) ? ', object id='.$object->id : '').((is_object($object) && property_exists($object, 'element')) ? ', object element='.$object->element : ''), LOG_DEBUG);
					}

					// Add current context to avoid method execution in bad context, you can add this test in your method : eg if($currentcontext != 'formfile') return;
					// Note: The hook can use the $currentcontext in its code to avoid to be ran twice or be ran for one given context only
					$parameters['currentcontext'] = $context;
					// Hooks that must return int (hooks with type 'addreplace')
					if ($hooktype == 'addreplace') {
						// @phan-suppress-next-line PhanUndeclaredMethod  The method's existence is tested above.
						$resactiontmp = $actionclassinstance->$method($parameters, $object, $action, $this); // $object and $action can be changed by method ($object->id during creation for example or $action to go back to other action for example)
						$resaction += $resactiontmp;

						if ($resactiontmp < 0 || !empty($actionclassinstance->error) || (!empty($actionclassinstance->errors) && count($actionclassinstance->errors) > 0)) {
							$error++;
							$this->error = $actionclassinstance->error;
							$this->errors = array_merge($this->errors, (array) $actionclassinstance->errors);
							dol_syslog("Error on hook module=".$module.", method ".$method.", class ".get_class($actionclassinstance).", hooktype=".$hooktype.(empty($this->error) ? '' : " ".$this->error).(empty($this->errors) ? '' : " ".implode(",", $this->errors)), LOG_ERR);
						}

						if (isset($actionclassinstance->results) && is_array($actionclassinstance->results)) {
							if ($resactiontmp > 0) {
								$localResArray = $actionclassinstance->results;
							} else {
								$localResArray = array_merge_recursive($localResArray, $actionclassinstance->results);
							}
						}

						if (!empty($actionclassinstance->resprints)) {
							if ($resactiontmp > 0) {
								$localResPrint = (string) $actionclassinstance->resprints;
							} else {
								$localResPrint .= (string) $actionclassinstance->resprints;
							}
						}
					} else {
						// Generic hooks that return a string or array (printLeftBlock, formAddObjectLine, formBuilddocOptions, ...)

						// TODO. this test should be done into the method of hook by returning nothing
						if (is_array($parameters) && !empty($parameters['special_code']) && $parameters['special_code'] > 3 && $parameters['special_code'] != $actionclassinstance->module_number) {
							continue;
						}

						if (getDolGlobalInt('MAIN_HOOK_DEBUG')) {
							dol_syslog("Call method ".$method." of class ".get_class($actionclassinstance).", module=".$module.", hooktype=".$hooktype, LOG_DEBUG);
						}

						// @phan-suppress-next-line PhanUndeclaredMethod  The method's existence is tested above.
						$resactiontmp = $actionclassinstance->$method($parameters, $object, $action, $this); // $object and $action can be changed by method ($object->id during creation for example or $action to go back to other action for example)
						$resaction += $resactiontmp;

						if (!empty($actionclassinstance->results) && is_array($actionclassinstance->results)) {
							$localResArray = array_merge_recursive($localResArray, $actionclassinstance->results);
						}
						if (!empty($actionclassinstance->resprints)) {
							$localResPrint .= (string) $actionclassinstance->resprints;
						}
						if (is_numeric($resactiontmp) && $resactiontmp < 0) {
							$error++;
							$this->error = $actionclassinstance->error;
							$this->errors = array_merge($this->errors, (array) $actionclassinstance->errors);
							dol_syslog("Error on hook module=".$module.", method ".$method.", class ".get_class($actionclassinstance).", hooktype=".$hooktype.(empty($this->error) ? '' : " ".$this->error).(empty($this->errors) ? '' : " ".implode(",", $this->errors)), LOG_ERR);
						}

						// TODO dead code to remove (do not disable this, but fix your hook instead): result must not be a string but an int. you must use $actionclassinstance->resprints to return a string
						if (!is_array($resactiontmp) && !is_numeric($resactiontmp)) {
							dol_syslog('Error: Bug into hook '.$method.' of module class '.get_class($actionclassinstance).'. Method must not return a string but an int (0=OK, 1=Replace, -1=KO) and set string into ->resprints', LOG_ERR);
							if (empty($actionclassinstance->resprints)) {
								$localResPrint .= $resactiontmp;
							}
						}
					}

					//print "After hook context=".$context." ".get_class($actionclassinstance)." method=".$method." hooktype=".$hooktype." results=".count($actionclassinstance->results)." resprints=".count($actionclassinstance->resprints)." resaction=".$resaction."<br>\n";

					$actionclassinstance->results = array();
					$actionclassinstance->resprints = null;
				}
			}
		}

		$this->resPrint = $localResPrint;
		$this->resArray = $localResArray;

		return ($error ? -1 : $resaction);
	}
}
