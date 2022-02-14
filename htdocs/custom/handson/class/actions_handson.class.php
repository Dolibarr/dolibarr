<?php
/* Copyright (C) 2021 Kuba admin <js@hands-on-technology.org>
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
 * \file    handson/class/actions_handson.class.php
 * \ingroup handson
 * \brief   Example hook overload.
 *
 * Put detailed description here.
 */

/**
 * Class ActionsHandsOn
 */
class ActionsHandsOn
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
	 * @var array Errors
	 */
	public $errors = array();


	/**
	 * @var array Hook results. Propagated to $hookmanager->resArray for later reuse
	 */
	public $results = array();

	/**
	 * @var string String displayed by executeHook() immediately after return
	 */
	public $resprints;


	/**
	 * Constructor
	 *
	 * @param DoliDB $db Database handler
	 */
	public function __construct($db)
	{
		$this->db = $db;
	}


	/**
	 * Execute action
	 *
	 * @param array $parameters Array of parameters
	 * @param CommonObject $object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action 'add', 'update', 'view'
	 * @return    int                            <0 if KO,
	 *                                        =0 if OK but we want to process standard actions too,
	 *                                            >0 if OK and we want to replace standard actions.
	 */
	public function getNomUrl($parameters, &$object, &$action)
	{
		global $db, $langs, $conf, $user;
		$this->resprints = '';
		return 0;
	}

	/**
	 * Overloading the doActions function : replacing the parent's function with the one below
	 *
	 * @param array $parameters Hook metadatas (context, etc...)
	 * @param CommonObject $object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		require_once DOL_DOCUMENT_ROOT . "/custom/handson/core/modules/modHandsOn.class.php";
		$mod = new modHandsOn($this->db);
		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], $mod->module_parts['hooks']['data']))        // do something only for the context hot
		{

		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the doMassActions function : replacing the parent's function with the one below
	 *
	 * @param array $parameters Hook metadatas (context, etc...)
	 * @param CommonObject $object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function doMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2')))        // do something only for the context
		{
			foreach ($parameters['toselect'] as $objectid) {
				// Do action on each object id
			}
		}

		if (!$error) {
			$this->results = array('myreturn' => 999);
			$this->resprints = 'A text to show';
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Overloading the addMoreMassActions function : replacing the parent's function with the one below
	 *
	 * @param array $parameters Hook metadatas (context, etc...)
	 * @param CommonObject $object The object to process (an invoice if you are in invoice module, a propale in propale's module, etc...)
	 * @param string $action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function addMoreMassActions($parameters, &$object, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$error = 0; // Error counter
		$disabled = 1;

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2')))        // do something only for the context 'somecontext1' or 'somecontext2'
		{
			$this->resprints = '<option value="0"' . ($disabled ? ' disabled="disabled"' : '') . '>' . $langs->trans("HandsOnMassAction") . '</option>';
		}

		if (!$error) {
			return 0; // or return 1 to replace standard code
		} else {
			$this->errors[] = 'Error message';
			return -1;
		}
	}


	/**
	 * Execute action
	 *
	 * @param array $parameters Array of parameters
	 * @param Object $object Object output on PDF
	 * @param string $action 'add', 'update', 'view'
	 * @return  int                    <0 if KO,
	 *                                =0 if OK but we want to process standard actions too,
	 *                                >0 if OK and we want to replace standard actions.
	 */
	public function beforePDFCreation($parameters, &$object, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2')))        // do something only for the context 'somecontext1' or 'somecontext2'
		{
		}

		return $ret;
	}

	/**
	 * Execute action
	 *
	 * @param array $parameters Array of parameters
	 * @param Object $pdfhandler PDF builder handler
	 * @param string $action 'add', 'update', 'view'
	 * @return  int                    <0 if KO,
	 *                                  =0 if OK but we want to process standard actions too,
	 *                                  >0 if OK and we want to replace standard actions.
	 */
	public function afterPDFCreation($parameters, &$pdfhandler, &$action)
	{
		global $conf, $user, $langs;
		global $hookmanager;

		$outputlangs = $langs;

		$ret = 0;
		$deltemp = array();
		dol_syslog(get_class($this) . '::executeHooks action=' . $action);

		/* print_r($parameters); print_r($object); echo "action: " . $action; */
		if (in_array($parameters['currentcontext'], array('somecontext1', 'somecontext2'))) {
			// do something only for the context 'somecontext1' or 'somecontext2'
		}

		return $ret;
	}


	/**
	 * Overloading the loadDataForCustomReports function : returns data to complete the customreport tool
	 *
	 * @param array $parameters Hook metadatas (context, etc...)
	 * @param string $action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                             < 0 on error, 0 on success, 1 to replace standard code
	 */
	public function loadDataForCustomReports($parameters, &$action, $hookmanager)
	{
		global $conf, $user, $langs;

		$langs->load("handson@handson");

		$this->results = array();

		$head = array();
		$h = 0;

		if ($parameters['tabfamily'] == 'handson') {
			$head[$h][0] = dol_buildpath('/module/index.php', 1);
			$head[$h][1] = $langs->trans("Home");
			$head[$h][2] = 'home';
			$h++;

			$this->results['title'] = $langs->trans("HandsOn");
			$this->results['picto'] = 'handson@handson';
		}

		$head[$h][0] = 'customreports.php?objecttype=' . $parameters['objecttype'] . (empty($parameters['tabfamily']) ? '' : '&tabfamily=' . $parameters['tabfamily']);
		$head[$h][1] = $langs->trans("CustomReports");
		$head[$h][2] = 'customreports';

		$this->results['head'] = $head;

		return 1;
	}


	/**
	 * Overloading the restrictedArea function : check permission on an object
	 *
	 * @param array $parameters Hook metadatas (context, etc...)
	 * @param string $action Current action (if set). Generally create or edit or null
	 * @param HookManager $hookmanager Hook manager propagated to allow calling another hook
	 * @return  int                            <0 if KO,
	 *                                        =0 if OK but we want to process standard actions too,
	 *                                        >0 if OK and we want to replace standard actions.
	 */
	public function restrictedArea($parameters, &$action, $hookmanager)
	{
		global $user;

		if ($parameters['features'] == 'myobject') {
			if ($user->rights->handson->myobject->read) {
				$this->results['result'] = 1;
				return 1;
			} else {
				$this->results['result'] = 0;
				return 1;
			}
		}

		return 0;
	}

	/* Add here any other hooked methods... */
	public function printFieldListTitle($parameters, &$action, &$hookmanager)
	{
		require_once DOL_DOCUMENT_ROOT . "/custom/handson/core/modules/modHandsOn.class.php";
		$mod = new modHandsOn($this->db);
		if (in_array($parameters['currentcontext'], $mod->module_parts['hooks']['data'])) {
			$root = ($parameters['currentcontext'] == 'klaziOrders') ? '' : "../custom/handson/";
			$context = $parameters['currentcontext'];
			echo "<script>addExportButton('$root', '$context');</script>";
		}
		return 0;
	}

	/**
	 *  Adding a create shipment label button to shipment
	 *
	 * @return Button
	 *
	 */
	public function addMoreActionsButtons($parameters, &$object, &$action, &$hookmanager)
	{
		require_once DOL_DOCUMENT_ROOT . "/custom/handson/core/modules/modHandsOn.class.php";
		$mod = new modHandsOn($this->db);
		$err = 0;
		if (in_array($parameters['currentcontext'], $mod->module_parts['hooks']['data'])) {
			$costcenter = 'Kostenstelle';
			$reference = 'Referenz';

			if ($parameters['currentcontext'] == 'expeditioncard') {

				$datastring = base64_encode(
					$object->id
					. ';' . $object->getTotalWeightVolume()['weight']
					. ';' . $object->trueWidth
					. ';' . $object->trueHeight
					. ';' . $object->trueDepth
					. ';' . $costcenter
					. ';' . $reference
					. ';' . date('Y-m-d')
				);
				$sql = 'SELECT * FROM ' . MAIN_DB_PREFIX . 'element_contact AS ec ';
				$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'socpeople AS s ON ec.fk_socpeople=s.rowid ';
				$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'c_country AS c ON s.fk_pays=c.rowid ';
				$sql .= 'WHERE ec.fk_c_type_contact=102 AND ec.element_id=' . $object->id;
			} elseif ($parameters['currentcontext'] == 'rpcard') {
				if ($object->shipping != '') {
					$datastring = base64_encode(
						$object->id
						. ';5'
						. ';80'
						. ';40'
						. ';50'
						. ';' . $costcenter
						. ';' . $reference
						. ';' . date('Y-m-d')
					);
					$sql = 'SELECT s.rowid, firstname, lastname, address, phone, email, zip, town, fk_pays, c.rowid as pays_id, c.code FROM ' . MAIN_DB_PREFIX . 'socpeople AS s ';
					$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'c_country AS c ON s.fk_pays=c.rowid ';
					$sql .= 'WHERE s.rowid=' . $object->shipping;
				} else {
					$err++;
				}
			} else {
				$datastring = base64_encode(
					$object->id
					. ';5'
					. ';80'
					. ';40'
					. ';50'
					. ';' . $costcenter
					. ';' . $reference
					. ';' . date('Y-m-d')
				);
				$sql = 'SELECT s.rowid, firstname, lastname, address, phone, email, zip, town, fk_pays, c.rowid as pays_id, c.code FROM ' . MAIN_DB_PREFIX . 'socpeople AS s ';
				$sql .= 'LEFT JOIN ' . MAIN_DB_PREFIX . 'c_country AS c ON s.fk_pays=c.rowid ';
				$sql .= 'WHERE s.rowid=' . $object->id;
			}

			if ($err == 0) {
				$result = mysqli_fetch_assoc($this->db->query($sql));
				$address = explode(';', $result['address']);

				$addrstring = base64_encode(
					$result['firstname']
					. ';' . $result['lastname']
					. ';' . $address[0]
					. ';' . $address[1]
					. ';' . $address[2]
					. ';' . $address[3]
					. ';' . $result['zip']
					. ';' . $result['town']
					. ';' . $result['email']
					. ';' . $result['phone']
					. ';' . $result['code']
					. ';' . $result['rowid']
				);

				print '<a class="butAction" onclick="checkCreateShipmentLabel(\'' . $datastring . '\',\'' . $addrstring . '\')">Versandlabel drucken</a>';
			}
		}
		return 0;
	}
}
