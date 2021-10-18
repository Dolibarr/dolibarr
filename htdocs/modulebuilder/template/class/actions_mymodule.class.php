<?php
/* Copyright (C) ---Put here your own copyright and developer email---
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file    htdocs/modulebuilder/template/class/actions_mymodule.class.php
 * \ingroup mymodule
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

declare(strict_types=1);

/**
 * Class ActionsMyModule
 */
class ActionsMyModule
{
	/**
	 * @var DoliDB Database handler.
	 */
	public DoliDB $db;

	/**
	 * @var string Error code (or message)
	 */
	public string $error = '';

	/**
	 * @var array Errors
	 */
	public array $errors = [];


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public array $results = [];

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public string $resprints;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct(DoliDB $db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param array        $parameters Array of parameters
	 * @param CommonObject $object     The object to process (an invoice if you are in invoice module,
	 *                                 a propale in propale's module, etc...)
	 * @param string       $action     'add', 'update', 'view'
	 *
	 * @return    int                            <0 if KO,
	 *                                        =0 if OK, but we want to process standard actions too,
	 *                                            >0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl(array $parameters, CommonObject $object, string &$action): int
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param array        $parameters  Hook metadatas (context, etc...)
	 * @param CommonObject $object      The object to process (an invoice if you are in invoice module,
	 *                                  a propale in propale's module, etc...)
	 * @param string       $action      Current action (if set). Generally create or edit or null
	 * @param HookManager  $hookmanager Hook manager propagated to allow calling another hook
	 *
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions(array $parameters, CommonObject $object, string &$action, HookManager $hookmanager): int
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2'])) {
			// do something only for the context 'somecontext1' or 'somecontext2'
			// Do what you want here...
			// You can for example call global vars like $fieldstosearchall to overwrite them,
			// or update database depending on $action and $_POST values.
		}

		if ($error) {
			$this->errors[] = 'Error message';
			return -1;
		}

		$this->results = ['myreturn' => 999];
		$this->resprints = 'A text to show';
		return 0; // or return 1 to replace standard code
	}


	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param array        $parameters  Hook metadatas (context, etc...)
	 * @param CommonObject $object      The object to process (an invoice if you are in invoice module,
	 *                                  a propale in propale's module, etc...)
	 * @param string       $action      Current action (if set). Generally create or edit or null
	 * @param HookManager  $hookmanager Hook manager propagated to allow calling another hook
	 *
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions(
		array $parameters,
		CommonObject $object,
		string &$action,
		HookManager $hookmanager
	): int {
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2'])) {
			// do something only for the context 'somecontext1' or 'somecontext2'
			foreach ($parameters['toselect'] as $objectid) {
				// Do action on each object id
			}
		}

		if ($error) {
			$this->errors[] = 'Error message';
			return -1;
		}

		$this->results = ['myreturn' => 999];
		$this->resprints = 'A text to show';
		return 0; // or return 1 to replace standard code
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param array        $parameters  Hook metadatas (context, etc...)
	 * @param CommonObject $object      The object to process (an invoice if you are in invoice module,
	 *                                  a propale in propale's module, etc...)
	 * @param string       $action      Current action (if set). Generally create or edit or null
	 * @param HookManager  $hookmanager Hook manager propagated to allow calling another hook
	 *
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions(
		array $parameters,
		CommonObject $object,
		string &$action,
		HookManager $hookmanager
	): int {
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$disabled = 1;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2'])) {
			// do something only for the context 'somecontext1' or 'somecontext2'
			$this->resprints = '<option value="0"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans(
					'MyModuleMassAction'
				) . '</option>';
		}

		if ($error) {
			$this->errors[] = 'Error message';
			return -1;
		}

		return 0; // or return 1 to replace standard code
	}


	/**
	 * Execute action
	 *
	 * @param array  $parameters Array of parameters
	 * @param Object $object     Object output on PDF
	 * @param string $action     'add', 'update', 'view'
	 *
	 * @return  int                    <0 if KO,
	 *                                =0 if OK, but we want to process standard actions too,
	 *                                >0 if OK and we want to replace standard actions.
	 * @throws Exception
	 */
	public function beforePDFCreation(array $parameters, object $object, string &$action): int
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = [];
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2'])) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param array  $parameters Array of parameters
	 * @param Object $pdfhandler PDF builder handler
	 * @param string $action     'add', 'update', 'view'
	 *
	 * @return  int                    <0 if KO,
	 *                                  =0 if OK, but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 * @throws Exception
	 */
	public function afterPDFCreation(array $parameters, object $pdfhandler, string &$action): int
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = [];
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], ['somecontext1', 'somecontext2'])) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}


	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param array       $parameters  Hook metadatas (context, etc...)
	 * @param string      $action      Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 *
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports(array $parameters, string &$action, HookManager $hookmanager): int
	{
		global $conf, $user, $langs;

		$langs->load('mymodule@mymodule');

		$this->results = [];

		$head = [];
		$h = 0;

		if ($parameters['tabfamily'] === 'mymodule') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans('Home');
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans('MyModule');
			$this->results['picto'] = 'mymodule@mymodule';
		}

		$head[$h][0] = 'customreports.php?objecttype=' . $parameters['objecttype'] . (empty($parameters['tabfamily']) ? '' : '&tabfamily=' . $parameters['tabfamily']);
		$head[$h][1] = $langs->trans('CustomReports');
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		return 1;
	}


	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param array       $parameters  Hook metadatas (context, etc...)
	 * @param string      $action      Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 *
	 * @return  int                            <0 if KO,
	 *                                        =0 if OK, but we want to process standard actions too,
	 *                                        >0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea(array $parameters, string &$action, HookManager $hookmanager): int
	{
		global $user;

		if ($parameters['features'] === 'myobject') {
			if ($user->rights->mymodule->myobject->read) {
				$this->results['result'] = 1;
			} else {
				$this->results['result'] = 0;
			}
			return 1;
		}

		return 0;
	}

	/**
	 * Execute action completeTabsHead
	 *
	 * @param array        $parameters  Array of parameters
	 * @param CommonObject $object      The object to process (an invoice if you are in invoice module,
	 *                                  a propale in propale's module, etc...)
	 * @param string       $action      'add', 'update', 'view'
	 * @param Hookmanager  $hookmanager hookmanager
	 *
	 * @return  int                             <0 if KO,
	 *                                          =0 if OK, but we want to process standard actions too,
	 *                                          >0 if OK and we want to replace standard actions.
	 */
	public function completeTabsHead(
		array &$parameters,
		CommonObject $object,
		string &$action,
		HookManager $hookmanager
	): int {
		global $langs, $conf, $user;

		if (!isset($parameters['object']->element)) {
			return 0;
		}
		if ($parameters['mode'] === 'remove') {
			// utilisé si on veut faire disparaitre des onglets.
			return 0;
		}

		if ($parameters['mode'] === 'add') {
			$langs->load('mymodule@mymodule');
			// utilisé si on veut ajouter des onglets.
			$counter = count($parameters['head']);
			$element = $parameters['object']->element;
			$id = $parameters['object']->id;
			// verifier le type d'onglet comme member_stats où ça ne doit pas apparaitre
			// if (in_array($element, ['societe', 'member', 'contrat', 'fichinter',
			// 'project', 'propal', 'commande', 'facture', 'order_supplier', 'invoice_supplier'])) {
			if (in_array($element, ['context1', 'context2'])) {
				$datacount = 0;

				$parameters['head'][$counter][0] = dol_buildpath(
						'/mymodule/mymodule_tab.php',
						1
					) . '?id=' . $id . '&amp;module=' . $element;
				$parameters['head'][$counter][1] = $langs->trans('MyModuleTab');
				if ($datacount > 0) {
					$parameters['head'][$counter][1] .= '<span class="badge marginleftonlyshort">' . $datacount . '</span>';
				}
				$parameters['head'][$counter][2] = 'mymoduleemails';
				$counter++;
			}
			if ($counter > 0 && (int) DOL_VERSION < 14) {
				$this->results = $parameters['head'];
				// return 1 to replace standard code
				return 1;
			}

			// en V14 et + $parameters['head'] est modifiable par référence
			return 0;
		}
	}

	/* Add here any other hooked methods... */
}
