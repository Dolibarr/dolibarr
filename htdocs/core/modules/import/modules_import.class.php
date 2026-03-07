<?php
/* Copyright (C) 2005-2012	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/import/modules_import.class.php
 *	\ingroup    export
 *	\brief      File of parent class for import file readers
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';


/**
 *	Parent class for import file readers
 */
class ModeleImports
{
	/**
	 * @var DoliDB Database handler.
	 */
	public $db;

	/**
	 * @var string
	 */
	public $datatoimport;

	/**
	 * @var string Error code (or message)
	 */
	public $error = '';

	/**
	 * @var string[]|array<int,array<string,string>> Error codes (or messages)
	 */
	public $errors = array();

	/**
	 * @var string[]|array<int,array<string,string>> warnings codes (or messages)
	 */
	public $warnings = array();

	/**
	 * @var string Code of driver
	 */
	public $id;

	/**
	 * @var string label of driver
	 */
	public $label;

	/**
	 * @var string Extension of files imported by driver
	 */
	public $extension;

	/**
	 * Dolibarr version of driver
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * PHP minimal version required by driver
	 * @var array{0:int,1:int}
	 */
	public $phpmin = array(7, 0);

	/**
	 * Label of external lib used by driver
	 * @var string
	 */
	public $label_lib;

	/**
	 * Version of external lib used by driver
	 * @var string
	 */
	public $version_lib;

	// Array of all drivers
	/**
	 * @var array<string,string>
	 */
	public $driverlabel = array();

	/**
	 * @var array<string,string>
	 */
	public $driverdesc = array();

	/**
	 * @var array<string,string>
	 */
	public $driverversion = array();

	/**
	 * @var array<string,string>
	 */
	public $drivererror = array();

	/**
	 * @var array<string,string>
	 */
	public $liblabel = array();

	/**
	 * @var array<string,string>
	 */
	public $libversion = array();

	/**
	 * @var string charset
	 */
	public $charset;

	/**
	 * @var array<string,string>|string picto
	 */
	public $picto;

	/**
	 * @var string description
	 */
	public $desc;

	/**
	 * @var string escape
	 */
	public $escape;

	/**
	 * @var string enclosure
	 */
	public $enclosure;

	/**
	 * @var Societe thirdparty
	 */
	public $thirdpartyobject;

	/**
	 * Trigger mode for import:
	 * - strict_line: fire standard business trigger per row (default)
	 * - fast_bulk: skip per-row triggers and emit one IMPORT_BULK_DONE at end
	 *
	 * @var string
	 */
	public $importtriggermode = '';

	/**
	 * Simulation mode flag (step 4):
	 * - 0: definitive import
	 * - 1: simulation only (no trigger execution)
	 *
	 * @var int
	 */
	public $importissimulation = 0;

	/**
	 * Reused trigger interface instance to avoid re-instantiation on each imported row.
	 *
	 * @var ?Interfaces
	 */
	public $importtriggerinterface;

	/**
	 * Aggregated stats used by fast_bulk trigger mode.
	 *
	 * @var array<string,mixed>
	 */
	public $importbulkstats = array();

	/**
	 * Cached trigger prototype objects by table element for strict_line mode.
	 *
	 * @var array<string,object>
	 */
	public $importtriggerobjectprototypes = array();

	/**
	 * Cache hook-resolved actions by table/operation/element/objectclass for import trigger dispatch.
	 *
	 * @var array<string,string[]>
	 */
	public $importtriggeractionshookcache = array();

	/**
	 * Array to cache list of values resolved after conversion rules.
	 *
	 * @var array<string,array<string,mixed>>
	 */
	public $cacheconvert = array();

	/**
	 * Array to cache list of values loaded from field@table rules.
	 *
	 * @var array<string,array<int|string,mixed>>
	 */
	public $cachefieldtable = array();

	/**
	 * Number of inserted rows during current import.
	 *
	 * @var int
	 */
	public $nbinsert = 0;

	/**
	 * Number of updated rows during current import.
	 *
	 * @var int
	 */
	public $nbupdate = 0;

	/**
	 * @var	array<string,string>	Element mapping from table name
	 */
	public static $mapTableToElement = MODULE_MAPPING;

	/**
	 *  Constructor
	 */
	public function __construct()
	{
		global $hookmanager;

		if (is_object($hookmanager)) {
			$hookmanager->initHooks(array('import'));
			$parameters = array();
			$reshook = $hookmanager->executeHooks('constructModeleImports', $parameters, $this);
			if ($reshook >= 0 && !empty($hookmanager->resArray)) {
				foreach ($hookmanager->resArray as $mapList) {
					self::$mapTableToElement[$mapList['table']] = $mapList['element'];
				}
			}
		}
	}

	/**
	 * getDriverId
	 *
	 * @return string		Code of driver
	 */
	public function getDriverId()
	{
		return $this->id;
	}

	/**
	 *	getDriverLabel
	 *
	 *	@return string	Label
	 */
	public function getDriverLabel()
	{
		return $this->label;
	}

	/**
	 *	getDriverDesc
	 *
	 *	@return string	Description
	 */
	public function getDriverDesc()
	{
		return $this->desc;
	}

	/**
	 * getDriverExtension
	 *
	 * @return string	Driver suffix
	 */
	public function getDriverExtension()
	{
		return $this->extension;
	}

	/**
	 *	getDriverVersion
	 *
	 *	@return string	Driver version
	 */
	public function getDriverVersion()
	{
		return $this->version;
	}

	/**
	 *	getDriverLabel
	 *
	 *	@return string	Label of external lib
	 */
	public function getLibLabel()
	{
		return $this->label_lib;
	}

	/**
	 * getLibVersion
	 *
	 *	@return string	Version of external lib
	 */
	public function getLibVersion()
	{
		return $this->version_lib;
	}


	/**
	 *  Load into memory list of available import format
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  int		$maxfilenamelength  Max length of value to show
	 *  @return	array<int,string>			List of templates
	 */
	public function listOfAvailableImportFormat($db, $maxfilenamelength = 0)
	{
		dol_syslog(get_class($this)."::listOfAvailableImportFormat");

		$dir = DOL_DOCUMENT_ROOT."/core/modules/import/";
		$handle = opendir($dir);

		// Search list ov drivers available and qualified
		if (is_resource($handle)) {
			while (($file = readdir($handle)) !== false) {
				$reg = array();
				if (preg_match("/^import_(.*)\.modules\.php/i", $file, $reg)) {
					$moduleid = $reg[1];

					// Loading Class
					$file = $dir."/import_".$moduleid.".modules.php";
					$classname = "Import".ucfirst($moduleid);

					require_once	$file;
					$module = new $classname($db, '');
					'@phan-var-force ModeleImports $module';

					// Picto
					$this->picto[$module->id] = $module->picto;
					// Driver properties
					$this->driverlabel[$module->id] = $module->getDriverLabel();
					$this->driverdesc[$module->id] = $module->getDriverDesc();
					$this->driverversion[$module->id] = $module->getDriverVersion();
					$this->drivererror[$module->id] = $module->error ? $module->error : '';
					// If use an external lib
					$this->liblabel[$module->id] = ($module->error ? '<span class="error">'.$module->error.'</span>' : $module->getLibLabel());
					$this->libversion[$module->id] = $module->getLibVersion();
				}
			}
		}

		return array_keys($this->driverlabel);
	}


	/**
	 *  Return picto of import driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getPictoForKey($key)
	{
		return	$this->picto[$key];
	}

	/**
	 *  Return label of driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getDriverLabelForKey($key)
	{
		return	$this->driverlabel[$key];
	}

	/**
	 *  Return description of import drivervoi la description d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getDriverDescForKey($key)
	{
		return	$this->driverdesc[$key];
	}

	/**
	 *  Renvoi version d'un driver import
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getDriverVersionForKey($key)
	{
		return	$this->driverversion[$key];
	}

	/**
	 *  Renvoi libelle de librairie externe du driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getLibLabelForKey($key)
	{
		return	$this->liblabel[$key];
	}

	/**
	 *  Renvoi version de librairie externe du driver
	 *
	 *	@param	string	$key	Key
	 *	@return	string
	 */
	public function getLibVersionForKey($key)
	{
		return	$this->libversion[$key];
	}

	/**
	 * Get element from table name with prefix
	 *
	 * @param 	string	$tableNameWithPrefix	Table name with prefix
	 * @return 	string							Element name or table element as default
	 */
	public function getElementFromTableWithPrefix($tableNameWithPrefix)
	{
		$tableElement = preg_replace('/^'.preg_quote($this->db->prefix(), '/').'/', '', $tableNameWithPrefix);
		$element = $tableElement;

		if (isset(self::$mapTableToElement[$tableElement])) {
			$element = self::$mapTableToElement[$tableElement];
		}

		return	$element;
	}

	/**
	 * Return effective trigger mode for import flow.
	 *
	 * @return string
	 */
	protected function getImportTriggerMode()
	{
		$mode = trim((string) $this->importtriggermode);
		if ($mode === '') {
			$mode = (string) getDolGlobalString('IMPORT_TRIGGER_MODE_DEFAULT', 'strict_line');
		}
		if (!in_array($mode, array('strict_line', 'fast_bulk'), true)) {
			$mode = 'strict_line';
		}
		return $mode;
	}

	/**
	 * Register one SQL operation into bulk trigger stats.
	 *
	 * @param string $tablename		Table name with prefix
	 * @param string $operation		insert|update
	 * @return void
	 */
	protected function registerImportBulkEvent($tablename, $operation)
	{
		if (empty($this->importbulkstats)) {
			$this->importbulkstats = array(
				'insert' => 0,
				'update' => 0,
				'tables' => array(),
			);
		}

		$operation = strtolower((string) $operation);
		if (!isset($this->importbulkstats[$operation])) {
			$this->importbulkstats[$operation] = 0;
		}
		$this->importbulkstats[$operation]++;

		$tableElement = preg_replace('/^'.preg_quote($this->db->prefix(), '/').'/', '', (string) $tablename);
		if (!isset($this->importbulkstats['tables'][$tableElement])) {
			$this->importbulkstats['tables'][$tableElement] = array('insert' => 0, 'update' => 0);
		}
		if (!isset($this->importbulkstats['tables'][$tableElement][$operation])) {
			$this->importbulkstats['tables'][$tableElement][$operation] = 0;
		}
		$this->importbulkstats['tables'][$tableElement][$operation]++;
	}

	/**
	 * Execute one global trigger for fast_bulk mode.
	 *
	 * @param	string		$importid	Import key
	 * @param	User		$user		User
	 * @param	Translate	$langs		Langs
	 * @param	Conf		$conf		Conf
	 * @return	int
	 */
	public function runImportBulkTrigger($importid, $user, $langs, $conf)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';

		if (!($this->importtriggerinterface instanceof Interfaces)) {
			$this->importtriggerinterface = new Interfaces($this->db);
		}

		$object = new stdClass();
		$object->db = $this->db;
		$object->id = 0;
		$object->rowid = 0;
		$object->import_key = $importid;
		$object->context = array(
			'import' => 1,
			'operation' => 'bulk',
			'importtriggermode' => $this->getImportTriggerMode(),
		);
		$object->bulk_stats = (empty($this->importbulkstats) ? array('insert' => 0, 'update' => 0, 'tables' => array()) : $this->importbulkstats);

		try {
			$result = $this->importtriggerinterface->run_triggers('IMPORT_BULK_DONE', $object, $user, $langs, $conf);
		} catch (Throwable $e) {
			$this->errors[] = array('lib' => $e->getMessage(), 'type' => 'TRIGGER');
			$this->error = 'ErrorFailedTriggerCall';
			return -1;
		}

		if ($result < 0) {
			if (!empty($this->importtriggerinterface->errors)) {
				foreach ($this->importtriggerinterface->errors as $errormsg) {
					$this->errors[] = array('lib' => $errormsg, 'type' => 'TRIGGER');
				}
			}
			$this->error = 'ErrorFailedTriggerCall';
			return -1;
		}

		return 1;
	}

	/**
	 * Return trigger actions to execute for an import operation done in SQL legacy mode.
	 *
	 * @param	string	$tableElement	Table name without database prefix
	 * @param	string	$operation		Operation insert|update
	 * @param	string	$element		Element name
	 * @param	object|null $object		Object context
	 * @return	string[]				List of trigger action codes
	 */
	protected function getImportTriggerActions($tableElement, $operation, $element, $object = null)
	{
		$operation = strtolower((string) $operation);

		$actionMap = array(
			'societe' => array('insert' => 'COMPANY_CREATE', 'update' => 'COMPANY_MODIFY'),
			'product' => array('insert' => 'PRODUCT_CREATE', 'update' => 'PRODUCT_MODIFY'),
			'socpeople' => array('insert' => 'CONTACT_CREATE', 'update' => 'CONTACT_MODIFY'),
			'commande' => array('insert' => 'ORDER_CREATE', 'update' => 'ORDER_MODIFY'),
			'commandedet' => array('insert' => 'LINEORDER_INSERT', 'update' => 'LINEORDER_MODIFY'),
			'propal' => array('insert' => 'PROPAL_CREATE', 'update' => 'PROPAL_MODIFY'),
			'propaldet' => array('insert' => 'LINEPROPAL_INSERT', 'update' => 'LINEPROPAL_MODIFY'),
			'facture' => array('insert' => 'BILL_CREATE', 'update' => 'BILL_MODIFY'),
			'facturedet' => array('insert' => 'LINEBILL_INSERT', 'update' => 'LINEBILL_MODIFY'),
			'facture_fourn' => array('insert' => 'BILL_SUPPLIER_CREATE', 'update' => 'BILL_SUPPLIER_MODIFY'),
			'facture_fourn_det' => array('insert' => 'LINEBILL_SUPPLIER_CREATE', 'update' => 'LINEBILL_SUPPLIER_MODIFY'),
			'commande_fournisseur' => array('insert' => 'ORDER_SUPPLIER_CREATE', 'update' => 'ORDER_SUPPLIER_MODIFY'),
			'commande_fournisseurdet' => array('insert' => 'LINEORDER_SUPPLIER_CREATE', 'update' => 'LINEORDER_SUPPLIER_MODIFY'),
			'contrat' => array('insert' => 'CONTRACT_CREATE', 'update' => 'CONTRACT_MODIFY'),
			'contratdet' => array('insert' => 'LINECONTRACT_INSERT', 'update' => 'LINECONTRACT_MODIFY'),
			'fichinter' => array('insert' => 'FICHINTER_CREATE', 'update' => 'FICHINTER_MODIFY'),
			'fichinterdet' => array('insert' => 'LINEFICHINTER_CREATE', 'update' => 'LINEFICHINTER_MODIFY'),
			'expedition' => array('insert' => 'SHIPPING_CREATE', 'update' => 'SHIPPING_MODIFY'),
			'expeditiondet' => array('insert' => 'LINESHIPPING_INSERT', 'update' => 'LINESHIPPING_MODIFY'),
			'supplier_proposal' => array('insert' => 'SUPPLIER_PROPOSAL_CREATE', 'update' => 'SUPPLIER_PROPOSAL_MODIFY'),
			'supplier_proposaldet' => array('insert' => 'LINESUPPLIER_PROPOSAL_INSERT', 'update' => 'LINESUPPLIER_PROPOSAL_MODIFY'),
		);

		$actions = array();
		if (!empty($actionMap[$tableElement][$operation])) {
			$actions[] = $actionMap[$tableElement][$operation];
		}

		// Let external modules add explicit import trigger actions.
		// We merge with core mapping when present.
		$hookactions = $this->getImportTriggerActionsFromHooks($tableElement, $operation, $element, $object);
		if (!empty($hookactions)) {
			$actions = array_merge($actions, $hookactions);
		}

		// Dynamic fallback only for real business objects and only when
		// no explicit mapping/hook action exists.
		// Avoid generating trigger names from stdClass (legacy SQL context).
		if (empty($actions) && is_object($object) && method_exists($object, 'call_trigger')) {
			$triggerprefix = $this->getImportTriggerPrefixFromObject($object);
			$action = $this->buildImportTriggerActionFromPrefix($triggerprefix, $operation);
			if (!empty($action)) {
				$actions[] = $action;
			}
		}

		// Generic fallback for external/custom objects imported through legacy SQL path:
		// derive a deterministic trigger prefix from element/table when no explicit mapping exists.
		if (empty($actions)) {
			$triggerprefix = $this->getImportGenericTriggerPrefix($element, $tableElement);
			$action = $this->buildImportTriggerActionFromPrefix($triggerprefix, $operation);
			if (!empty($action)) {
				$actions[] = $action;
			}
		}

		return array_values(array_unique(array_filter($actions)));
	}

	/**
	 * Build trigger action code from prefix and operation.
	 *
	 * @param string $triggerprefix Trigger prefix (for example COMPANY or LINEORDER)
	 * @param string $operation     SQL operation (insert|update)
	 * @return string               Trigger action code, empty string if no match
	 */
	protected function buildImportTriggerActionFromPrefix($triggerprefix, $operation)
	{
		$triggerprefix = strtoupper(trim((string) $triggerprefix));
		$operation = strtolower((string) $operation);
		if ($triggerprefix === '') {
			return '';
		}

		if ($operation === 'update') {
			return $triggerprefix.'_MODIFY';
		}
		if ($operation === 'insert') {
			return preg_match('/^LINE/', $triggerprefix) ? $triggerprefix.'_INSERT' : $triggerprefix.'_CREATE';
		}

		return '';
	}

	/**
	 * Return trigger prefix from a business object.
	 *
	 * @param object $object Business object
	 * @return string        Trigger prefix
	 */
	protected function getImportTriggerPrefixFromObject($object)
	{
		if (!empty($object->TRIGGER_PREFIX)) {
			return (string) $object->TRIGGER_PREFIX;
		}
		if (!empty($object->element)) {
			return (string) $object->element;
		}
		return get_class($object);
	}

	/**
	 * Return generic trigger prefix derived from element or table.
	 *
	 * @param string $element      Object element name
	 * @param string $tableElement Table name without DB prefix
	 * @return string              Trigger prefix
	 */
	protected function getImportGenericTriggerPrefix($element, $tableElement)
	{
		$rawprefix = '';
		if (!empty($element)) {
			$rawprefix = (string) $element;
		} elseif (!empty($tableElement)) {
			$rawprefix = (string) $tableElement;
		}

		return trim((string) preg_replace('/[^A-Za-z0-9]+/', '_', strtoupper($rawprefix)), '_');
	}

	/**
	 * Resolve import trigger actions from hooks.
	 *
	 * Hook signature:
	 * - context: import
	 * - method: getImportTriggerActions
	 * - expected output (one of):
	 *   - $hookmanager->resArray['actions'] = array('ACTION_A', 'ACTION_B')
	 *   - $hookmanager->resArray['actions'] = 'ACTION_A,ACTION_B'
	 *   - $hookmanager->resArray['action'] = 'ACTION_A'
	 *
	 * @param	string	$tableElement	Table name without prefix
	 * @param	string	$operation		insert|update
	 * @param	string	$element		Element name
	 * @param	object|null $object		Object context
	 * @return	string[]
	 */
	protected function getImportTriggerActionsFromHooks($tableElement, $operation, $element, $object = null)
	{
		global $hookmanager;

		$actions = array();
		$objectclass = (is_object($object) ? get_class($object) : 'none');
		$cachekey = $tableElement.'|'.$operation.'|'.$element.'|'.$objectclass;
		if (isset($this->importtriggeractionshookcache[$cachekey])) {
			return $this->importtriggeractionshookcache[$cachekey];
		}

		if (!is_object($hookmanager)) {
			$this->importtriggeractionshookcache[$cachekey] = $actions;
			return $actions;
		}

		$hookmanager->initHooks(array('import'));
		$parameters = array(
			'tableelement' => (string) $tableElement,
			'operation' => (string) $operation,
			'element' => (string) $element,
		);
		$action = '';
		$hookmanager->executeHooks('getImportTriggerActions', $parameters, $object, $action);

		if (!empty($hookmanager->resArray['actions'])) {
			if (is_array($hookmanager->resArray['actions'])) {
				$actions = array_merge($actions, $hookmanager->resArray['actions']);
			} else {
				$actions = array_merge($actions, preg_split('/[\s,;|]+/', (string) $hookmanager->resArray['actions']));
			}
		}
		if (!empty($hookmanager->resArray['action'])) {
			$actions[] = (string) $hookmanager->resArray['action'];
		}

		$cleaned = array();
		foreach ($actions as $oneaction) {
			$oneaction = strtoupper(trim((string) $oneaction));
			if ($oneaction !== '') {
				$cleaned[] = $oneaction;
			}
		}

		$cleaned = array_values(array_unique($cleaned));
		$this->importtriggeractionshookcache[$cachekey] = $cleaned;
		return $cleaned;
	}

	/**
	 * Execute triggers for SQL legacy import.
	 *
	 * @param	string		$tablename		Table name with database prefix
	 * @param	string		$operation		Operation insert|update
	 * @param	int			$rowid			Row id if available, 0 otherwise
	 * @param	string		$importid		Import key
	 * @param	User		$user			User
	 * @param	Translate	$langs			Langs object
	 * @param	Conf		$conf			Conf object
	 * @return	int							1 on success, <0 on trigger error
	 */
	protected function triggerImportSqlOperation($tablename, $operation, $rowid, $importid, $user, $langs, $conf)
	{
		require_once DOL_DOCUMENT_ROOT.'/core/class/interfaces.class.php';

		$tableElement = preg_replace('/^'.preg_quote($this->db->prefix(), '/').'/', '', $tablename);
		$element = $this->getElementFromTableWithPrefix($tablename);
		$object = null;
		$needenrichobject = (bool) getDolGlobalInt('IMPORT_TRIGGER_ENRICH_OBJECT');

		// Fast-path: for mapped tables, get action list without loading business object.
		$actions = $this->getImportTriggerActions($tableElement, $operation, $element, null);

		// Resolve business object only when needed:
		// - dynamic action fallback requires real object,
		// - optional richer trigger context can be enabled with IMPORT_TRIGGER_ENRICH_OBJECT.
		if (empty($actions) || $needenrichobject) {
			// Try to resolve a real business object for full trigger compatibility.
			// - with rowid when available (best case),
			// - otherwise as a prototype object based on element/table.
			if ((int) $rowid > 0) {
				$objecttmp = fetchObjectByElement((int) $rowid, $tableElement);
				if (is_object($objecttmp)) {
					$object = $objecttmp;
				}
			}
			if (!is_object($object)) {
				$objecttmp = fetchObjectByElement(0, $tableElement);
				if (is_object($objecttmp)) {
					$object = $objecttmp;
					if ((int) $rowid > 0) {
						if (method_exists($object, 'fetch')) {
							$fetchres = $object->fetch((int) $rowid);
							if ($fetchres <= 0) {
								$object->id = (int) $rowid;
								$object->rowid = (int) $rowid;
							}
						} else {
							$object->id = (int) $rowid;
							$object->rowid = (int) $rowid;
						}
					}
				}
			}
		}

		// Compatibility path for strict_line mode:
		// for mapped actions, provide at least a business object prototype (not stdClass)
		// so custom triggers can call object methods safely.
		if (!is_object($object) && !empty($actions) && $this->getImportTriggerMode() === 'strict_line') {
			if (!isset($this->importtriggerobjectprototypes[$tableElement])) {
				$prototype = fetchObjectByElement(0, $tableElement);
				if (is_object($prototype)) {
					$this->importtriggerobjectprototypes[$tableElement] = $prototype;
				}
			}
			if (isset($this->importtriggerobjectprototypes[$tableElement])) {
				$object = clone $this->importtriggerobjectprototypes[$tableElement];
				$object->id = (int) $rowid;
				$object->rowid = (int) $rowid;
				if ((int) $rowid > 0 && method_exists($object, 'fetch')) {
					$fetchres = $object->fetch((int) $rowid);
					if ($fetchres <= 0) {
						$object->id = (int) $rowid;
						$object->rowid = (int) $rowid;
					}
				}
			}
		}

		if (!is_object($object)) {
			$object = new stdClass();
			$object->db = $this->db;
			$object->id = (int) $rowid;
			$object->rowid = (int) $rowid;
			$object->table_element = $tableElement;
			$object->element = $element;
		}
		$object->import_key = $importid;
		$object->context = array('import' => 1, 'operation' => $operation);

		if (empty($actions)) {
			$actions = $this->getImportTriggerActions($tableElement, $operation, $element, $object);
		}

		if (empty($actions)) {
			dol_syslog(get_class($this)."::triggerImportSqlOperation no trigger mapping for table=".$tableElement." operation=".$operation, LOG_DEBUG);
			return 1;
		}

		// Optional enrichment: provides full row payload for stdClass context.
		// Disabled by default for performance.
		if ($needenrichobject && $rowid > 0 && $object instanceof stdClass) {
			$sql = "SELECT * FROM ".$tablename." WHERE rowid = ".((int) $rowid);
			$resql = $this->db->query($sql);
			if ($resql) {
				$objrow = $this->db->fetch_object($resql);
				if ($objrow) {
					foreach (get_object_vars($objrow) as $key => $value) {
						$object->$key = $value;
					}
					if (!isset($object->id) && isset($object->rowid)) {
						$object->id = (int) $object->rowid;
					}
				}
			}
		}

		if (!($this->importtriggerinterface instanceof Interfaces)) {
			$this->importtriggerinterface = new Interfaces($this->db);
		}
		$interface = $this->importtriggerinterface;
		foreach ($actions as $action) {
			try {
				$result = $interface->run_triggers($action, $object, $user, $langs, $conf);
			} catch (Throwable $e) {
				$this->errors[] = array('lib' => $e->getMessage(), 'type' => 'TRIGGER');
				$this->error = 'ErrorFailedTriggerCall';
				return -1;
			}
			if ($result < 0) {
				if (!empty($interface->errors)) {
					foreach ($interface->errors as $errormsg) {
						$this->errors[] = array('lib' => $errormsg, 'type' => 'TRIGGER');
					}
				}
				$this->error = 'ErrorFailedTriggerCall';
				return -1;
			}
		}

		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Open input file
	 *
	 *	@param	string	$file       Path of filename
	 *  @return int                 Return integer <0 if KO, >=0 if OK
	 */
	public function import_open_file($file)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return -1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return nb of records. File must be closed.
	 *
	 *	@param	string	$file       Path of filename
	 *  @return	int					Return integer <0 if KO, >=0 if OK
	 */
	public function import_get_nb_of_lines($file)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Input header line from file
	 *
	 *  @return     int     Return integer <0 if KO, >=0 if OK
	 */
	public function import_read_header()
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return -1;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return array of next record in input file.
	 *
	 *  @return	array<string,array{val:mixed,type:int<-1,1>}>|boolean     Array of field values. Data are UTF8 encoded. [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=not empty string)
	 */
	public function import_read_record()
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return array();
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Close file handle
	 *
	 *  @return int
	 */
	public function import_close_file()
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return -1;
	}

	/**
	 * Shared implementation of import_insert for CSV/XLSX.
	 *
	 * @param	array<int,array{val:mixed,type:int}>|array<string,array{val:mixed,type:int}>|bool	$arrayrecord					Array of read values
	 * @param	array<int|string,string>	$array_match_file_to_database	Array of target fields where to insert data
	 * @param	Object						$objimport						Object import descriptor
	 * @param	int							$maxfields						Max number of fields to use
	 * @param	string						$importid						Import key
	 * @param	string[]					$updatekeys						Array of keys used to update first before insert
	 * @param	int							$recordpositionbase				0 when $arrayrecord starts at 0, 1 when starts at 1
	 * @return	int															Return integer <0 if KO, >0 if OK
	 */
	protected function commonImportInsert($arrayrecord, $array_match_file_to_database, $objimport, $maxfields, $importid, $updatekeys, $recordpositionbase = 0)
	{
		global $langs, $conf, $user;
		global $thirdparty_static; // Specific to thirdparty import
		global $tablewithentity_cache; // Cache to avoid to call  desc at each rows on tables

		if (is_array($arrayrecord) && !empty($recordpositionbase)) {
			$arrayrecord = array_values($arrayrecord);
		}

		$error = 0;
		$warning = 0;
		$importtriggermode = $this->getImportTriggerMode();
		$importissimulation = !empty($this->importissimulation);
		$this->errors = array();
		$this->warnings = array();

		//dol_syslog("import_csv.modules maxfields=".$maxfields." importid=".$importid);

		//var_dump($array_match_file_to_database);
		//var_dump($arrayrecord); exit;

		$array_match_database_to_file = array_flip($array_match_file_to_database);
		$sort_array_match_file_to_database = $array_match_file_to_database;
		ksort($sort_array_match_file_to_database);

		//var_dump($sort_array_match_file_to_database);

		if (count($arrayrecord) == 0 || (count($arrayrecord) == 1 && empty($arrayrecord[0]['val']))) {
			//print 'W';
			$this->warnings[$warning]['lib'] = $langs->trans('EmptyLine');
			$this->warnings[$warning]['type'] = 'EMPTY';
			$warning++;
		} else {
			$last_insert_id_array = array(); // store the last inserted auto_increment id for each table, so that dependent tables can be inserted with the appropriate id (eg: extrafields fk_object will be set with the last inserted object's id)
			$updatedone = false;
			$insertdone = false;
			// For each table to insert, me make a separate insert
			foreach ($objimport->array_import_tables[0] as $alias => $tablename) {
				// Build sql request
				$sql = '';
				$listfields = array();
				$listvalues = array();
				$i = 0;
				$errorforthistable = 0;

				// Define $tablewithentity_cache[$tablename] if not already defined
				if (!isset($tablewithentity_cache[$tablename])) {	// keep this test with "isset"
					dol_syslog("Check if table ".$tablename." has an entity field");
					$resql = $this->db->DDLDescTable($tablename, 'entity');
					if ($resql) {
						$obj = $this->db->fetch_object($resql);
						if ($obj) {
							$tablewithentity_cache[$tablename] = 1; // table contains entity field
						} else {
							$tablewithentity_cache[$tablename] = 0; // table does not contain entity field
						}
					} else {
						dol_print_error($this->db);
					}
				} else {
					//dol_syslog("Table ".$tablename." check for entity into cache is ".$tablewithentity_cache[$tablename]);
				}

				// Define an array to convert fields ('c.ref', ...) into column index (1, ...)
				$arrayfield = array();
				foreach ($sort_array_match_file_to_database as $key => $val) {
					$arrayfield[$val] = ($key - 1);
				}

				// $arrayrecord start at key 0
				// $sort_array_match_file_to_database start at key 1

				// Loop on each fields in the match array: $key = 1..n, $val=alias of field (s.nom)
				foreach ($sort_array_match_file_to_database as $key => $val) {
					$fieldalias = preg_replace('/\..*$/i', '', $val);
					$fieldname = preg_replace('/^.*\./i', '', $val);

					if ($alias != $fieldalias) {
						continue; // Not a field of current table
					}

					if ($key <= $maxfields) {
						// Set $newval with value to insert and set $listvalues with sql request part for insert
						$newval = '';
						if ($arrayrecord[($key - 1)]['type'] > 0) {
							$newval = $arrayrecord[($key - 1)]['val']; // If type of field into input file is not empty string (so defined into input file), we get value
						}

						//var_dump($newval);var_dump($val);
						//var_dump($objimport->array_import_convertvalue[0][$val]);

						// Make some tests on $newval

						// Is it a required field ?
						if (preg_match('/\*/', $objimport->array_import_fields[0][$val]) && ((string) $newval == '')) {
							// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
							$this->errors[$error]['lib'] = $langs->trans('ErrorMissingMandatoryValue', $key);
							$this->errors[$error]['type'] = 'NOTNULL';
							$errorforthistable++;
							$error++;
						} else {
							// Test format only if field is not a missing mandatory field (field may be a value or empty but not mandatory)
							// We convert field if required
							if (!empty($objimport->array_import_convertvalue[0][$val])) {
								//print 'Must convert '.$newval.' with rule '.join(',',$objimport->array_import_convertvalue[0][$val]).'. ';
								if ($objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeid'
									|| $objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromref'
									|| $objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeorlabel'
								) {
									// New val can be an id or ref. If it start with id: it is forced to id, if it start with ref: it is forced to ref. It not, we try to guess.
									$isidorref = 'id';
									if (!is_numeric($newval) && $newval != '' && !preg_match('/^id:/i', $newval)) {
										$isidorref = 'ref';
									}

									$newval = preg_replace('/^(id|ref):/i', '', $newval); // Remove id: or ref: that was used to force if field is id or ref
									//print 'Newval is now "'.$newval.'" and is type '.$isidorref."<br>\n";

									if ($isidorref == 'ref') {    // If value into input import file is a ref, we apply the function defined into descriptor
										$file = (empty($objimport->array_import_convertvalue[0][$val]['classfile']) ? $objimport->array_import_convertvalue[0][$val]['file'] : $objimport->array_import_convertvalue[0][$val]['classfile']);
										$class = $objimport->array_import_convertvalue[0][$val]['class'];
										$method = $objimport->array_import_convertvalue[0][$val]['method'];
										$cachekey = $file.'_'.$class.'_'.$method.'_';
										if (isset($this->cacheconvert[$cachekey][$newval]) && $this->cacheconvert[$cachekey][$newval] != '') {
											$newval = $this->cacheconvert[$cachekey][$newval];
										} else {
											$resultload = dol_include_once($file);
											if (empty($resultload)) {
												dol_print_error(null, 'Error trying to call file='.$file.', class='.$class.', method='.$method);
												break;
											}
											$classinstance = new $class($this->db);
											if ($class == 'CGenericDic') {
												$classinstance->element = $objimport->array_import_convertvalue[0][$val]['element'];
												$classinstance->table_element = $objimport->array_import_convertvalue[0][$val]['table_element'];
											}

											// Try the fetch from code or ref
											$param_array = array('', $newval);
											if ($class == 'AccountingAccount') {
												//var_dump($arrayrecord[0]['val']);
												/*include_once DOL_DOCUMENT_ROOT.'/accountancy/class/accountancysystem.class.php';
												 $tmpchartofaccount = new AccountancySystem($this->db);
												 $tmpchartofaccount->fetch(getDolGlobalInt('CHARTOFACCOUNTS'));
												 //var_dump($tmpchartofaccount->ref.' - '.$arrayrecord[0]['val']);
												 if ((! (getDolGlobalInt('CHARTOFACCOUNTS') > 0)) || $tmpchartofaccount->ref != $arrayrecord[0]['val'])
												 {
												 $this->errors[$error]['lib']=$langs->trans('ErrorImportOfChartLimitedToCurrentChart', $tmpchartofaccount->ref);
												 $this->errors[$error]['type']='RESTRICTONCURRENCTCHART';
												 $errorforthistable++;
												 $error++;
												 }*/
												$param_array = array('', $newval, 0, $arrayrecord[0]['val']); // Param to fetch parent from account, in chart.
											}
											if ($class == 'CActionComm') {
												$param_array = array($newval); // CActionComm fetch method have same parameter for id and code
											}
											$result = call_user_func_array(array($classinstance, $method), $param_array);

											// If duplicate record found
											if (!($classinstance->id != '') && $result == -2) {
												$this->errors[$error]['lib'] = $langs->trans('ErrorMultipleRecordFoundFromRef', $newval);
												$this->errors[$error]['type'] = 'FOREIGNKEY';
												$errorforthistable++;
												$error++;
											}

											// If not found, try the fetch from label
											if (!($classinstance->id != '') && $objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeorlabel') {
												$param_array = array('', '', $newval);
												call_user_func_array(array($classinstance, $method), $param_array);
											}
											$this->cacheconvert[$cachekey][$newval] = $classinstance->id;

											//print 'We have made a '.$class.'->'.$method.' to get id from code '.$newval.'. ';
											if ($classinstance->id != '') {	// id may be 0, it is a found value
												$newval = $classinstance->id;
											} elseif (! $error) {
												if (!empty($objimport->array_import_convertvalue[0][$val]['dict'])) {
													$this->errors[$error]['lib'] = $langs->trans('ErrorFieldValueNotIn', num2Alpha($key - 1), $newval, 'code', $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['dict']));
												} elseif (!empty($objimport->array_import_convertvalue[0][$val]['element'])) {
													$this->errors[$error]['lib'] = $langs->trans('ErrorFieldRefNotIn', num2Alpha($key - 1), $newval, $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['element']));
												} else {
													$this->errors[$error]['lib'] = 'ErrorBadDefinitionOfImportProfile';
												}
												$this->errors[$error]['type'] = 'FOREIGNKEY';
												$errorforthistable++;
												$error++;
											}
										}
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeandlabel') {
									$isidorref = 'id';
									if (!is_numeric($newval) && $newval != '' && !preg_match('/^id:/i', $newval)) {
										$isidorref = 'ref';
									}
									$newval = preg_replace('/^(id|ref):/i', '', $newval);

									if ($isidorref == 'ref') {
										$file = (empty($objimport->array_import_convertvalue[0][$val]['classfile']) ? $objimport->array_import_convertvalue[0][$val]['file'] : $objimport->array_import_convertvalue[0][$val]['classfile']);
											$class = $objimport->array_import_convertvalue[0][$val]['class'];
											$method = $objimport->array_import_convertvalue[0][$val]['method'];
											$codefromfield = $objimport->array_import_convertvalue[0][$val]['codefromfield'];
											$code = $arrayrecord[$arrayfield[$codefromfield]]['val'];
											$cachekey = $file.'_'.$class.'_'.$method.'_'.$code;
										if (isset($this->cacheconvert[$cachekey][$newval]) && $this->cacheconvert[$cachekey][$newval] != '') {
											$newval = $this->cacheconvert[$cachekey][$newval];
										} else {
											$resultload = dol_include_once($file);
											if (empty($resultload)) {
												dol_print_error(null, 'Error trying to call file='.$file.', class='.$class.', method='.$method.', code='.$code);
												break;
											}
											$classinstance = new $class($this->db);
											// Try the fetch from code and ref
											$param_array = array('', $newval, $code);
											call_user_func_array(array($classinstance, $method), $param_array);
											$this->cacheconvert[$cachekey][$newval] = $classinstance->id;
											if ($classinstance->id > 0) {    // we found record
												$newval = $classinstance->id;
											} else {
												if (!empty($objimport->array_import_convertvalue[0][$val]['dict'])) {
													$this->errors[$error]['lib'] = $langs->trans('ErrorFieldValueNotIn', num2Alpha($key - 1), $newval, 'scale', $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['dict']));
												} else {
													$this->errors[$error]['lib'] = 'ErrorFieldValueNotIn';
												}
												$this->errors[$error]['type'] = 'FOREIGNKEY';
												$errorforthistable++;
												$error++;
											}
										}
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'zeroifnull') {
									if (empty($newval)) {
										$newval = '0';
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeunits' || $objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchscalefromcodeunits') {
										$file = (empty($objimport->array_import_convertvalue[0][$val]['classfile']) ? $objimport->array_import_convertvalue[0][$val]['file'] : $objimport->array_import_convertvalue[0][$val]['classfile']);
										$class = $objimport->array_import_convertvalue[0][$val]['class'];
										$method = $objimport->array_import_convertvalue[0][$val]['method'];
										$units = $objimport->array_import_convertvalue[0][$val]['units'];
										$cachekey = $file.'_'.$class.'_'.$method.'_'.$units;
									if (isset($this->cacheconvert[$cachekey][$newval]) && $this->cacheconvert[$cachekey][$newval] != '') {
										$newval = $this->cacheconvert[$cachekey][$newval];
									} else {
										$resultload = dol_include_once($file);
										if (empty($resultload)) {
											dol_print_error(null, 'Error trying to call file='.$file.', class='.$class.', method='.$method.', units='.$units);
											break;
										}
										$classinstance = new $class($this->db);
										// Try the fetch from code or ref
										call_user_func_array(array($classinstance, $method), array('', '', $newval, $units));
										$scaleorid = (($objimport->array_import_convertvalue[0][$val]['rule'] == 'fetchidfromcodeunits') ? $classinstance->id : $classinstance->scale);
										$this->cacheconvert[$cachekey][$newval] = $scaleorid;
										//print 'We have made a '.$class.'->'.$method." to get a value from key '".$newval."' and we got '".$scaleorid."'.";exit;
										if ($classinstance->id > 0) {	// we found record
											$newval = $scaleorid ? $scaleorid : 0;
										} else {
											if (!empty($objimport->array_import_convertvalue[0][$val]['dict'])) {
												$this->errors[$error]['lib'] = $langs->trans('ErrorFieldValueNotIn', num2Alpha($key - 1), $newval, 'scale', $langs->transnoentitiesnoconv($objimport->array_import_convertvalue[0][$val]['dict']));
											} else {
												$this->errors[$error]['lib'] = 'ErrorFieldValueNotIn';
											}
											$this->errors[$error]['type'] = 'FOREIGNKEY';
											$errorforthistable++;
											$error++;
										}
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getcustomercodeifauto') {
									if (strtolower($newval) == 'auto') {
										$this->thirdpartyobject->get_codeclient(null, 0);
										$newval = $this->thirdpartyobject->code_client;
										//print 'code_client='.$newval;
									}
									if (empty($newval)) {
										$arrayrecord[($key - 1)]['type'] = -1; // If we get empty value, we will use "null"
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getsuppliercodeifauto') {
									if (strtolower($newval) == 'auto') {
										$this->thirdpartyobject->get_codefournisseur(null, 1);
										$newval = $this->thirdpartyobject->code_fournisseur;
										//print 'code_fournisseur='.$newval;
									}
									if (empty($newval)) {
										$arrayrecord[($key - 1)]['type'] = -1; // If we get empty value, we will use "null"
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getcustomeraccountancycodeifauto') {
									if (strtolower($newval) == 'auto') {
										$this->thirdpartyobject->get_codecompta('customer');
										$newval = $this->thirdpartyobject->code_compta_client;
										//print 'code_compta='.$newval;
									}
									if (empty($newval)) {
										$arrayrecord[($key - 1)]['type'] = -1; // If we get empty value, we will use "null"
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getsupplieraccountancycodeifauto') {
									if (strtolower($newval) == 'auto') {
										$this->thirdpartyobject->get_codecompta('supplier');
										$newval = $this->thirdpartyobject->code_compta_fournisseur;
										if (empty($newval)) {
											$arrayrecord[($key - 1)]['type'] = -1; // If we get empty value, we will use "null"
										}
										//print 'code_compta_fournisseur='.$newval;
									}
									if (empty($newval)) {
										$arrayrecord[($key - 1)]['type'] = -1; // If we get empty value, we will use "null"
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'getrefifauto') {
									if (strtolower($newval) == 'auto') {
										$defaultref = '';

										$classModForNumber = $objimport->array_import_convertvalue[0][$val]['class'];
										$pathModForNumber = $objimport->array_import_convertvalue[0][$val]['path'];

										if (!empty($classModForNumber) && !empty($pathModForNumber) && is_readable(DOL_DOCUMENT_ROOT.$pathModForNumber)) {
											require_once DOL_DOCUMENT_ROOT.$pathModForNumber;
											$modForNumber = new $classModForNumber();
											'@phan-var-force ModeleNumRefMembers|ModeleNumRefCommandes|ModeleNumRefSuppliersInvoices|ModeleNumRefSuppliersOrders|ModeleNumRefProjects|ModeleNumRefTask|ModeleNumRefPropales $modForNumber';

											$tmpobject = null;
											// Set the object with the date property when we can
											if (!empty($objimport->array_import_convertvalue[0][$val]['classobject'])) {
												$pathForObject = $objimport->array_import_convertvalue[0][$val]['pathobject'];
												require_once DOL_DOCUMENT_ROOT.$pathForObject;
												$tmpclassobject = $objimport->array_import_convertvalue[0][$val]['classobject'];
												$tmpobject = new $tmpclassobject($this->db);
												foreach ($arrayfield as $tmpkey => $tmpval) {	// $arrayfield is array('c.ref'=>0, ...)
													if (in_array($tmpkey, array('t.date', 'c.date_commande'))) {
														$tmpobject->date = dol_stringtotime($arrayrecord[$arrayfield[$tmpkey]]['val'], 1);
													}
												}
											}

											$defaultref = $modForNumber->getNextValue(null, $tmpobject);
										}
										if (is_numeric($defaultref) && $defaultref <= 0) {	// If error
											$defaultref = '';
										}
										$newval = $defaultref;
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'compute') {
									$file = (empty($objimport->array_import_convertvalue[0][$val]['classfile']) ? $objimport->array_import_convertvalue[0][$val]['file'] : $objimport->array_import_convertvalue[0][$val]['classfile']);
									$class = $objimport->array_import_convertvalue[0][$val]['class'];
									$method = $objimport->array_import_convertvalue[0][$val]['method'];
									$resultload = dol_include_once($file);
									if (empty($resultload)) {
										dol_print_error(null, 'Error trying to call file='.$file.', class='.$class.', method='.$method);
										break;
									}
									$classinstance = new $class($this->db);
										$computedFieldPos = isset($arrayfield[$fieldname]) ? ((int) $arrayfield[$fieldname]) : 0;
										$res = call_user_func_array(array($classinstance, $method), array(&$arrayrecord, $arrayfield, $computedFieldPos));
									if (empty($classinstance->error) && empty($classinstance->errors)) {
										$newval = $res; 	// We get new value computed.
									} else {
										$this->errors[$error]['type'] = 'CLASSERROR';
										$this->errors[$error]['lib'] = implode(
											"\n",
											array_merge([$classinstance->error], $classinstance->errors)
										);
										$errorforthistable++;
										$error++;
									}
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'numeric') {
									$newval = price2num($newval);
								} elseif ($objimport->array_import_convertvalue[0][$val]['rule'] == 'accountingaccount') {
									if (!getDolGlobalString('ACCOUNTING_MANAGE_ZERO')) {
										$newval = rtrim(trim($newval), "0");
									} else {
										$newval = trim($newval);
									}
								}

								//print 'Val to use as insert is '.$newval.'<br>';
							}

							// Test regexp
							if (!empty($objimport->array_import_regex[0][$val]) && ($newval != '')) {
								// If test regex string is "field@table" or "field@table:..." (means must exists into table ...)
								$reg = array();
								if (preg_match('/^(.+)@([^:]+)(:.+)?$/', $objimport->array_import_regex[0][$val], $reg)) {
									$field = $reg[1];
									$table = $reg[2];
									$filter = !empty($reg[3]) ? substr($reg[3], 1) : '';

									$cachekey = $field.'@'.$table;
									if (!empty($filter)) {
										$cachekey .= ':'.$filter;
									}

									// Load content of field@table into cache array
									if (!is_array($this->cachefieldtable[$cachekey])) { // If content of field@table not already loaded into cache
										$sql = "SELECT ".$field." as aliasfield FROM ".$table;
										if (!empty($filter)) {
											$sql .= ' WHERE '.$filter;
										}

										$resql = $this->db->query($sql);
										if ($resql) {
											$num = $this->db->num_rows($resql);
											$i = 0;
											while ($i < $num) {
												$obj = $this->db->fetch_object($resql);
												if ($obj) {
													$this->cachefieldtable[$cachekey][] = $obj->aliasfield;
												}
												$i++;
											}
										} else {
											dol_print_error($this->db);
										}
									}

									// Now we check cache is not empty (should not) and key is in cache
									if (!is_array($this->cachefieldtable[$cachekey]) || !in_array($newval, $this->cachefieldtable[$cachekey])) {
										$tableforerror = $table;
										if (!empty($filter)) {
											$tableforerror .= ':'.$filter;
										}
										$this->errors[$error]['lib'] = $langs->transnoentitiesnoconv('ErrorFieldValueNotIn', num2Alpha($key - 1), $newval, $field, $tableforerror);
										$this->errors[$error]['type'] = 'FOREIGNKEY';
										$errorforthistable++;
										$error++;
									}
								} elseif (!preg_match('/'.$objimport->array_import_regex[0][$val].'/i', $newval)) {
									// If test is just a static regex
									//if ($key == 19) print "xxx".$newval."zzz".$objimport->array_import_regex[0][$val]."<br>";
									$this->errors[$error]['lib'] = $langs->transnoentitiesnoconv('ErrorWrongValueForField', num2Alpha($key - 1), $newval, $objimport->array_import_regex[0][$val]);
									$this->errors[$error]['type'] = 'REGEX';
									$errorforthistable++;
									$error++;
								}
							}

							// Check HTML injection
							$inj = testSqlAndScriptInject($newval, 0);
							if ($inj) {
								$this->errors[$error]['lib'] = $langs->transnoentitiesnoconv('ErrorHtmlInjectionForField', num2Alpha($key - 1), dol_trunc($newval, 100));
								$this->errors[$error]['type'] = 'HTMLINJECTION';
								$errorforthistable++;
								$error++;
							}

							// Other tests
							// ...
						}

						// Define $listfields and $listvalues to build the SQL request
						if (isModEnabled("socialnetworks") && strpos($fieldname, "socialnetworks") !== false) {
							if (!in_array("socialnetworks", $listfields)) {
								$listfields[] = "socialnetworks";
								$socialkey = array_search("socialnetworks", $listfields);	// Return position of 'socialnetworks' key in array
								$listvalues[$socialkey] = '';
							}
							//var_dump($newval); var_dump($arrayrecord[($key - 1)]['type']);
							if (!empty($newval) && $arrayrecord[($key - 1)]['type'] > 0) {
								$socialkey = array_search("socialnetworks", $listfields);	// Return position of 'socialnetworks' key in array
								//var_dump('sk='.$socialkey);	// socialkey=19
								$socialnetwork = explode("_", $fieldname)[1];
								if (empty($listvalues[$socialkey]) || $listvalues[$socialkey] == "null") {
									$json = new stdClass();
									$json->$socialnetwork = $newval;
									$listvalues[$socialkey] = json_encode($json);
								} else {
									$jsondata = $listvalues[$socialkey];
									$json = json_decode($jsondata);
									$json->$socialnetwork = $newval;
									$listvalues[$socialkey] = json_encode($json);
								}
							}
						} else {
							$listfields[] = $fieldname;
							// Note: arrayrecord (and 'type') is filled with ->import_read_record called by import.php page before calling import_insert
							if (empty($newval) && $arrayrecord[($key - 1)]['type'] < 0) {
								$listvalues[] = ($newval == '0' ? (int) $newval : "null");
							} elseif (empty($newval) && $arrayrecord[($key - 1)]['type'] == 0) {
								$listvalues[] = "''";
							} else {
								$listvalues[] = "'".$this->db->escape($newval)."'";
							}
						}
					}
					$i++;
				}

				// We add hidden fields (but only if there is at least one field to add into table)
				// We process here all the fields that were declared into the array $this->import_fieldshidden_array of the descriptor file.
				// Previously we processed the ->import_fields_array.
				if (!empty($listfields) && is_array($objimport->array_import_fieldshidden[0])) {
					// Loop on each hidden fields to add them into listfields/listvalues
					foreach ($objimport->array_import_fieldshidden[0] as $tmpkey => $tmpval) {
						if (!preg_match('/^' . preg_quote($alias, '/') . '\./', $tmpkey)) {
							continue; // Not a field of current table
						}
						$keyfieldcache = preg_replace('/^' . preg_quote($alias, '/') . '\./', '', $tmpkey);

						if (in_array($keyfieldcache, $listfields)) {		// avoid duplicates in insert
							continue;
						} elseif ($tmpval == 'user->id') {
							$listfields[] = $keyfieldcache;
							$listvalues[] = ((int) $user->id);
						} elseif (preg_match('/^lastrowid-/', $tmpval)) {
							$tmp = explode('-', $tmpval);
							$lastinsertid = (isset($last_insert_id_array[$tmp[1]])) ? $last_insert_id_array[$tmp[1]] : 0;
							$listfields[] = $keyfieldcache;
							$listvalues[] = (int) $lastinsertid;
							$keyfield = $keyfieldcache;
							//print $tmpkey."-".$tmpval."-".$listfields."-".$listvalues."<br>";exit;
						} elseif (preg_match('/^const-/', $tmpval)) {
							$tmp = explode('-', $tmpval, 2);
							$listfields[] = $keyfieldcache;
							$listvalues[] = "'".$this->db->escape($tmp[1])."'";
						} elseif (preg_match('/^rule-/', $tmpval)) {	// Example: rule-computeAmount, rule-computeDirection, ...
							$fieldname = $tmpkey;
							if (!empty($objimport->array_import_convertvalue[0][$fieldname])) {
								if ($objimport->array_import_convertvalue[0][$fieldname]['rule'] == 'compute') {
									$file = (empty($objimport->array_import_convertvalue[0][$fieldname]['classfile']) ? $objimport->array_import_convertvalue[0][$fieldname]['file'] : $objimport->array_import_convertvalue[0][$fieldname]['classfile']);
									$class = $objimport->array_import_convertvalue[0][$fieldname]['class'];
									$method = $objimport->array_import_convertvalue[0][$fieldname]['method'];
									$type = $objimport->array_import_convertvalue[0][$fieldname]['type'];
									$resultload = dol_include_once($file);
									if (empty($resultload)) {
										dol_print_error(null, 'Error trying to call file=' . $file . ', class=' . $class . ', method=' . $method);
										break;
									}
									$classinstance = new $class($this->db);
										$computedFieldPos = isset($arrayfield[$fieldname]) ? ((int) $arrayfield[$fieldname]) : 0;
										$res = call_user_func_array(array($classinstance, $method), array(&$arrayrecord, $arrayfield, $computedFieldPos));
									if (empty($classinstance->error) && empty($classinstance->errors)) {
										$fieldArr = explode('.', $fieldname);
										if (count($fieldArr) > 0) {
											$fieldname = $fieldArr[1];
										}

										// Set $listfields and $listvalues
										$listfields[] = $fieldname;
										if ($type == 'int') {
											$listvalues[] = (int) $res;
										} elseif ($type == 'double') {
											$listvalues[] = (float) $res;
										} else {
											$listvalues[] = "'".$this->db->escape($res)."'";
										}
									} else {
										$this->errors[$error]['type'] = 'CLASSERROR';
										$this->errors[$error]['lib'] = implode(
											"\n",
											array_merge([$classinstance->error], $classinstance->errors)
										);
										$errorforthistable++;
										$error++;
									}
								}
							}
						} else {
							$this->errors[$error]['lib'] = 'Bad value of profile setup '.$tmpval.' for array_import_fieldshidden';
							$this->errors[$error]['type'] = 'Import profile setup';
							$error++;
						}
					}
				}
				//print 'listfields='.$listfields.'<br>listvalues='.$listvalues.'<br>';

				// If no error for this $alias/$tablename, we have a complete $listfields and $listvalues that are defined
				// so we can try to make the insert or update now.
				if (!$errorforthistable) {
					//print "$alias/$tablename/$listfields/$listvalues<br>";
					if (!empty($listfields)) {
						$updatedone = false;
						$insertdone = false;
						$where = array();

						$is_table_category_link = false;
						$fname = 'rowid';
						if (strpos($tablename, '_categorie_') !== false) {
							$is_table_category_link = true;
							$fname = '*';
						}

						if (!empty($updatekeys)) {
							// We do SELECT to get the rowid, if we already have the rowid, it's to be used below for related tables (extrafields)

							if (empty($lastinsertid)) {	// No insert done yet for a parent table
								$sqlSelect = "SELECT ".$fname." FROM ".$tablename;
								$data = array_combine($listfields, $listvalues);
								$where = array();	// filters to forge SQL request
								$filters = array();	// filters to forge output error message
								foreach ($updatekeys as $key) {
									$col = $objimport->array_import_updatekeys[0][$key];
									$key = preg_replace('/^.*\./i', '', $key);
									if (isModEnabled("socialnetworks") && strpos($key, "socialnetworks") !== false) {
										$tmp = explode("_", $key);
										$key = $tmp[0];
										$socialnetwork = $tmp[1];
										$jsondata = $data[$key];
										$json = json_decode($jsondata);
										$stringtosearch = json_encode($socialnetwork).':'.json_encode($json->$socialnetwork);
										//var_dump($stringtosearch);
										//var_dump($this->db->escape($stringtosearch));	// This provide a value for sql string (but not for a like)
										$where[] = $key." LIKE '%".$this->db->escape($this->db->escapeforlike($stringtosearch))."%'";
										$filters[] = $col." LIKE '%".$this->db->escape($this->db->escapeforlike($stringtosearch))."%'";
										//var_dump($where[1]); // This provide a value for sql string inside a like
									} else {
										$where[] = $key.' = '.$data[$key];
										$filters[] = $col.' = '.$data[$key];
									}
								}
								if (!empty($tablewithentity_cache[$tablename])) {
									$where[] = "entity IN (".getEntity($this->getElementFromTableWithPrefix($tablename)).")";
									$filters[] = "entity IN (".getEntity($this->getElementFromTableWithPrefix($tablename)).")";
								}
								$sqlSelect .= " WHERE ".implode(' AND ', $where);

								$resql = $this->db->query($sqlSelect);
								if ($resql) {
									$num_rows = $this->db->num_rows($resql);
									if ($num_rows == 1) {
										$res = $this->db->fetch_object($resql);
										$lastinsertid = $res->rowid;
										$keyfield = 'rowid';
										if ($is_table_category_link) {
											$lastinsertid = 'linktable';
										} // used to apply update on tables like llx_categorie_product and avoid being blocked for all file content if at least one entry already exists
										$last_insert_id_array[$tablename] = $lastinsertid;
									} elseif ($num_rows > 1) {
										$this->errors[$error]['lib'] = $langs->trans('MultipleRecordFoundWithTheseFilters', implode(', ', $filters));
										$this->errors[$error]['type'] = 'SQL';
										$error++;
									} else {
										// No record found with filters, insert will be tried below
									}
								} else {
									//print 'E';
									$this->errors[$error]['lib'] = $this->db->lasterror();
									$this->errors[$error]['type'] = 'SQL';
									$error++;
								}
							} else {
								// We have a last INSERT ID (got by previous pass), so we check if we have a row referencing this foreign key.
								// This is required when updating table with some extrafields. When inserting a record in parent table, we can make
								// a direct insert into subtable extrafields, but when me wake an update, the insertid is defined and the child record
								// may already exists. So we rescan the extrafield table to know if record exists or not for the rowid.
								// Note: For extrafield tablename, we have in importfieldshidden_array an entry 'extra.fk_object'=>'lastrowid-tableparent' so $keyfield is 'fk_object'
								$sqlSelect = "SELECT rowid FROM ".$tablename;
								if (empty($keyfield)) {
									$keyfield = 'rowid';
								}

								$sqlSelect .= " WHERE ".$keyfield." = ".((int) $lastinsertid);

								if (!empty($tablewithentity_cache[$tablename])) {
									$sqlSelect .= " AND entity IN (".getEntity($this->getElementFromTableWithPrefix($tablename)).")";
								}

								$resql = $this->db->query($sqlSelect);
								if ($resql) {
									$res = $this->db->fetch_object($resql);
									if ($this->db->num_rows($resql) == 1) {
										// We have a row referencing this last foreign key, continue with UPDATE.
									} else {
										// No record found referencing this last foreign key,
										// force $lastinsertid to 0 so we INSERT below.
										$lastinsertid = 0;
									}
								} else {
									//print 'E';
									$this->errors[$error]['lib'] = $this->db->lasterror();
									$this->errors[$error]['type'] = 'SQL';
									$error++;
								}
							}

							if (!empty($lastinsertid)) {
								// We db escape social network field because he isn't in field creation
								if (in_array("socialnetworks", $listfields)) {
									$socialkey = array_search("socialnetworks", $listfields);
									$tmpsql =  $listvalues[$socialkey];
									$listvalues[$socialkey] = "'".$this->db->escape($tmpsql)."'";
								}

								// Build SQL UPDATE request
								$sqlstart = "UPDATE ".$tablename;

								$data = array_combine($listfields, $listvalues);
								$set = array();
								foreach ($data as $key => $val) {
									$set[] = $key." = ".$val;	// $val was escaped/sanitized previously
								}
								$sqlstart .= " SET ".implode(', ', $set).", import_key = '".$this->db->escape($importid)."'";
								if (empty($keyfield)) {
									$keyfield = 'rowid';
								}

								$sqlend = " WHERE ".$keyfield." = ".((int) $lastinsertid);

								if ($is_table_category_link && !empty($where)) {
									'@phan-var-force string[] $where';
									$sqlend = " WHERE " . implode(' AND ', $where);
								}

								if (!empty($tablewithentity_cache[$tablename])) {
									$sqlend .= " AND entity IN (".getEntity($this->getElementFromTableWithPrefix($tablename)).")";
								}

								$sql = $sqlstart.$sqlend;

								// Run update request
								$resql = $this->db->query($sql);
								if ($resql) {
									// No error, update has been done. $this->db->db->affected_rows can be 0 if data hasn't changed
									$updatedone = true;
									if (!$importissimulation && $importtriggermode === 'strict_line') {
										$restrigger = $this->triggerImportSqlOperation($tablename, 'update', is_numeric($lastinsertid) ? (int) $lastinsertid : 0, $importid, $user, $langs, $conf);
										if ($restrigger < 0) {
											$this->errors[$error]['lib'] = $langs->trans('ErrorFailedTriggerCall');
											$this->errors[$error]['type'] = 'TRIGGER';
											$error++;
										}
									} elseif (!$importissimulation) {
										$this->registerImportBulkEvent($tablename, 'update');
									}
								} else {
									//print 'E';
									$this->errors[$error]['lib'] = $this->db->lasterror();
									$this->errors[$error]['type'] = 'SQL';
									$error++;
								}
							}
						}

						// Update not done, we do insert
						if (!$error && !$updatedone) {
							// We db escape social network field because he isn't in field creation
							if (in_array("socialnetworks", $listfields)) {
								$socialkey = array_search("socialnetworks", $listfields);
								$tmpsql =  $listvalues[$socialkey];
								$listvalues[$socialkey] = "'".$this->db->escape($tmpsql)."'";
							}

							// Build SQL INSERT request
							$sqlstart = "INSERT INTO ".$tablename."(".implode(", ", $listfields).", import_key";
							$sqlend = ") VALUES(".implode(', ', $listvalues).", '".$this->db->escape($importid)."'";
							if (!empty($tablewithentity_cache[$tablename])) {
								$sqlstart .= ", entity";
								$sqlend .= ", ".$conf->entity;
							}
							if (!empty($objimport->array_import_tables_creator[0][$alias])) {
								$sqlstart .= ", ".$objimport->array_import_tables_creator[0][$alias];
								$sqlend .= ", ".$user->id;
							}
								$sql = $sqlstart.$sqlend.")";
								//dol_syslog("import_csv.modules", LOG_DEBUG);

								// Run insert request
								$resql = $this->db->query($sql);
							if ($resql) {
								if (!$is_table_category_link) {
									$last_insert_id_array[$tablename] = $this->db->last_insert_id($tablename); // store the last inserted auto_increment id for each table, so that child tables can be inserted with the appropriate id. This must be done just after the INSERT request, else we risk losing the id (because another sql query will be issued somewhere in Dolibarr).
								}
								$insertdone = true;
								if (!$importissimulation && $importtriggermode === 'strict_line') {
									$triggerrowid = (!$is_table_category_link && !empty($last_insert_id_array[$tablename])) ? (int) $last_insert_id_array[$tablename] : 0;
									$restrigger = $this->triggerImportSqlOperation($tablename, 'insert', $triggerrowid, $importid, $user, $langs, $conf);
									if ($restrigger < 0) {
										$this->errors[$error]['lib'] = $langs->trans('ErrorFailedTriggerCall');
										$this->errors[$error]['type'] = 'TRIGGER';
										$error++;
									}
								} elseif (!$importissimulation) {
									$this->registerImportBulkEvent($tablename, 'insert');
								}
							} else {
								//print 'E';
								$this->errors[$error]['lib'] = $this->db->lasterror();
								$this->errors[$error]['type'] = 'SQL';
								$error++;
							}
						}
					}
					/*else
					{
					dol_print_error(null,'ErrorFieldListEmptyFor '.$alias."/".$tablename);
					}*/
				}

				if ($error) {
					break;
				}
			}

			if ($updatedone) {
				$this->nbupdate++;
			}
			if ($insertdone) {
				$this->nbinsert++;
			}
		}

		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Insert a record into database
	 *
	 *	@param	array<string,array{val:mixed,type:int<-1,1>}>|boolean	$arrayrecord                    Array of read values: [fieldpos] => (['val']=>val, ['type']=>-1=null,0=blank,1=string), [fieldpos+1]...
	 *	@param	array<int|string,string>	$array_match_file_to_database   Array of target fields where to insert data: [fieldpos] => 's.fieldname', [fieldpos+1]...
	 *	@param	Object		$objimport                      Object import (contains objimport->array_import_tables, objimport->array_import_fields, objimport->array_import_convertvalue, ...)
	 *	@param	int	$maxfields					Max number of fields to use
	 *	@param	string		$importid			Import key
	 *	@param	string[]	$updatekeys			Array of keys to use to try to do an update first before insert. This field are defined into the module descriptor.
	 *	@return	int								Return integer <0 if KO, >0 if OK
	 */
	public function import_insert($arrayrecord, $array_match_file_to_database, $objimport, $maxfields, $importid, $updatekeys)
	{
		// phpcs:enable
		return $this->commonImportInsert($arrayrecord, $array_match_file_to_database, $objimport, $maxfields, $importid, $updatekeys, 0);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output header of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @return	string							Empty string
	 */
	public function write_header_example($outputlangs)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return '';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output title line of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @param	string[]	$headerlinefields	Array of fields name
	 * 	@return	string							String output
	 */
	public function write_title_example($outputlangs, $headerlinefields)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return '';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output record of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 * 	@param	mixed[]		$contentlinevalues	Array of lines
	 * 	@return	string							Empty string
	 */
	public function write_record_example($outputlangs, $contentlinevalues)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return '';
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * 	Output footer of an example file for this format
	 *
	 * 	@param	Translate	$outputlangs		Output language
	 *  @return	string							String output
	 */
	public function write_footer_example($outputlangs)
	{
		// phpcs:enable
		$msg = get_class($this)."::".__FUNCTION__." not implemented";
		dol_syslog($msg, LOG_ERR);
		$this->errors[] = $msg;
		$this->error = $msg;
		return '';
	}
}
