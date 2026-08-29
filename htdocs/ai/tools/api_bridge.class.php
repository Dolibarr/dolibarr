<?php
/* Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
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
 * \file htdocs/ai/tools/api_bridge.class.php
 * \ingroup ai
 * \brief WIP MCP tool bridge that derives AI tools from the enabled REST API classes.
 *
 * Proof of concept for the direction discussed in issue #38356 ("reuse the existing
 * API with a dynamic scan to detect which api is enabled so which tool must be
 * enabled"). Instead of hand-writing one tool definition per object, this bridge:
 *   1. detects which REST API endpoint classes are available AND whose module is
 *      enabled (isModEnabled), and
 *   2. converts their read methods into MCP tool definitions (JSON Schema built
 *      from reflection + docblock parsing), and
 *   3. executes calls IN-PROCESS on the API class (no HTTP self-call), behind a
 *      central authentication bridge (DolibarrApiAccess::$user = service user),
 *      catching RestException.
 *
 * Deliberate WIP limitations (POC scope):
 *   - Read-only: only index() (list) and get() are exposed. Write methods need a
 *     confirmation gate + body schemas harvested from each object's ->fields.
 *   - Endpoint map is a small explicit list; TODO generalize with the same
 *     dolGetModulesDirs()/getModuleDirForApiClass() scan used by api/index.php.
 *   - Schemas come from a light docblock parser; TODO reuse Restler's
 *     CommentParser/Routes metadata (what generates swagger.json) + cache them.
 *
 * Disabled unless the constant AI_MCP_API_BRIDGE is set to 1.
 */

/**
 * Class ToolApiBridge
 *
 * Exposes enabled REST API endpoints as MCP tools (read-only POC).
 */
class ToolApiBridge extends McpTool
{
	/**
	 * Generated tool definitions cache (per request).
	 *
	 * @var null|list<array<string, mixed>>
	 */
	private $defs = null;

	/**
	 * Map tool name => [endpoint key, api method name].
	 *
	 * @var array<string, array{0:string, 1:string}>
	 */
	private $routes = [];

	/**
	 * POC endpoint map: endpoint key => module condition, api class file and class name.
	 * TODO Replace with the dynamic scan of api_*.class.php used by api/index.php.
	 *
	 * @var array<string, array{module:string, path:string, class:string, label:string}>
	 */
	private $endpoints = [
		'thirdparties' => [
			'module' => 'societe',
			'path' => '/societe/class/api_thirdparties.class.php',
			'class' => 'Thirdparties',
			'label' => 'third parties (customers, prospects, suppliers)'
		],
		'proposals' => [
			'module' => 'propal',
			'path' => '/comm/propal/class/api_proposals.class.php',
			'class' => 'Proposals',
			'label' => 'commercial proposals (quotes / devis)'
		],
		'tickets' => [
			'module' => 'ticket',
			'path' => '/ticket/class/api_tickets.class.php',
			'class' => 'Tickets',
			'label' => 'support tickets'
		],
		'projects' => [
			'module' => 'projet',
			'path' => '/projet/class/api_projects.class.php',
			'class' => 'Projects',
			'label' => 'projects (including opportunities/leads)'
		],
		'tasks' => [
			'module' => 'projet',
			'path' => '/projet/class/api_tasks.class.php',
			'class' => 'Tasks',
			'label' => 'project tasks'
		],
		'agendaevents' => [
			'module' => 'agenda',
			'path' => '/comm/action/class/api_agendaevents.class.php',
			'class' => 'AgendaEvents',
			'label' => 'agenda / calendar events (meetings, calls)'
		],
		'interventions' => [
			'module' => 'ficheinter',
			'path' => '/fichinter/class/api_interventions.class.php',
			'class' => 'Interventions',
			'label' => 'field service interventions'
		],
		'contracts' => [
			'module' => 'contrat',
			'path' => '/contrat/class/api_contracts.class.php',
			'class' => 'Contracts',
			'label' => 'contracts (recurring services)'
		],
		'members' => [
			'module' => 'adherent',
			'path' => '/adherents/class/api_members.class.php',
			'class' => 'Members',
			'label' => 'foundation/association members'
		],
		'subscriptions' => [
			'module' => 'adherent',
			'path' => '/adherents/class/api_subscriptions.class.php',
			'class' => 'Subscriptions',
			'label' => 'member subscriptions'
		],
		'stockmovements' => [
			'module' => 'stock',
			'path' => '/product/stock/class/api_stockmovements.class.php',
			'class' => 'StockMovements',
			'label' => 'stock movements (in/out/transfer history)'
		],
		'warehouses' => [
			'module' => 'stock',
			'path' => '/product/stock/class/api_warehouses.class.php',
			'class' => 'Warehouses',
			'label' => 'warehouses'
		],
		'expensereports' => [
			'module' => 'expensereport',
			'path' => '/expensereport/class/api_expensereports.class.php',
			'class' => 'ExpenseReports',
			'label' => 'employee expense reports (notes de frais)'
		],
		'products' => [
			'module' => 'product',
			'path' => '/product/class/api_products.class.php',
			'class' => 'Products',
			'label' => 'products and services catalog',
			// Extra read methods beyond index/get: product variants & attributes
			'extra' => ['getAttributes' => 'attributes_list', 'getVariants' => 'variants_list']
		],
		// NB: stock inventories have no REST API class in core yet (no api_inventories) —
		// they cannot be bridged until one exists.
	];

	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB		$db			Database handler
	 * 	@param	User|null	$user		Service user provided by McpHandler
	 * 	@param	Conf|null	$conf		Dolibarr config (optional)
	 */
	public function __construct($db, $user = null, $conf = null)
	{
		$this->db = $db;
		$this->user = $user;
		if ($conf !== null) {
			$this->conf = $conf;
		}
	}

	/**
	 * Load the REST API runtime (Restler autoloader + DolibarrApi base classes),
	 * mirroring the bootstrap sequence of htdocs/api/index.php, so that the
	 * endpoint classes (which extend DolibarrApi and throw RestException) can be
	 * loaded and executed outside the Restler HTTP runtime.
	 *
	 * @return void
	 */
	private function loadApiRuntime()
	{
		require_once DOL_DOCUMENT_ROOT . '/includes/restler/framework/Luracast/Restler/AutoLoader.php';
		$loader = Luracast\Restler\AutoLoader::instance();
		spl_autoload_register($loader);
		require_once DOL_DOCUMENT_ROOT . '/api/class/api.class.php';
		require_once DOL_DOCUMENT_ROOT . '/api/class/api_access.class.php';
	}

	/**
	 * Returns tool definitions derived from the enabled REST API endpoints.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		if (!getDolGlobalInt('AI_MCP_API_BRIDGE')) {
			return [];	// Feature flag off: bridge exposes nothing.
		}
		if ($this->defs !== null) {
			return $this->defs;
		}
		$this->loadApiRuntime();

		$this->defs = [];
		$this->routes = [];

		foreach ($this->endpoints as $key => $ep) {
			if (!isModEnabled($ep['module'])) {
				continue;	// Dynamic part: a disabled module exposes no tools.
			}
			$file = DOL_DOCUMENT_ROOT . $ep['path'];
			if (!is_readable($file)) {
				continue;
			}
			require_once $file;
			if (!class_exists($ep['class'])) {
				continue;
			}

			// Read-only POC: expose list (index) and get, plus optional per-endpoint
			// extra read methods (e.g. product variants/attributes).
			$methodsToExpose = ['index' => 'list', 'get' => 'get'];
			if (!empty($ep['extra']) && is_array($ep['extra'])) {
				$methodsToExpose += $ep['extra'];
			}
			foreach ($methodsToExpose as $method => $suffix) {
				if (!method_exists($ep['class'], $method)) {
					continue;
				}
				$toolname = 'api_' . $key . '_' . $suffix;
				$def = $this->buildToolDefinition($ep, $key, $method, $toolname);
				if ($def) {
					$this->defs[] = $def;
					$this->routes[$toolname] = [$key, $method];
				}
			}
		}

		return $this->defs;
	}

	/**
	 * Build one MCP tool definition from an API class method via reflection + docblock.
	 *
	 * @param array{module:string, path:string, class:string, label:string} $ep       Endpoint entry
	 * @param string $key      Endpoint key (e.g. 'thirdparties')
	 * @param string $method   API method name ('index' or 'get')
	 * @param string $toolname Generated tool name
	 * @return array<string, mixed>|null Tool definition, or null on reflection failure
	 */
	private function buildToolDefinition(array $ep, string $key, string $method, string $toolname)
	{
		try {
			$rm = new ReflectionMethod($ep['class'], $method);
		} catch (ReflectionException $e) {
			return null;
		}

		$doc = (string) $rm->getDocComment();

		// First docblock line = human description of the endpoint.
		$summary = '';
		if (preg_match('/\*\s+([^@\s\/][^\n]*)/', $doc, $m)) {
			$summary = trim($m[1]);
		}

		// @param <type> $<name> <description>
		$paramDocs = [];
		if (preg_match_all('/@param\s+(\S+)\s+\$(\w+)\s+([^\n]*)/', $doc, $mm, PREG_SET_ORDER)) {
			foreach ($mm as $pm) {
				$paramDocs[$pm[2]] = ['type' => $pm[1], 'desc' => trim($pm[3])];
			}
		}

		$properties = [];
		$required = [];
		foreach ($rm->getParameters() as $p) {
			$pname = $p->getName();
			$ptype = isset($paramDocs[$pname]) ? $this->docTypeToJson($paramDocs[$pname]['type']) : 'string';
			$prop = [
				'type' => $ptype,
				'description' => isset($paramDocs[$pname]) ? $paramDocs[$pname]['desc'] : ''
			];
			if ($p->isOptional()) {
				try {
					$prop['default'] = $p->getDefaultValue();
				} catch (ReflectionException $e) {
					// keep without default
				}
			} else {
				$required[] = $pname;
			}
			$properties[$pname] = $prop;
		}

		$verb = ($method === 'index') ? 'List / search' : 'Get one record of';
		$schema = ['type' => 'object', 'properties' => $properties];
		if ($required) {
			$schema['required'] = $required;
		}

		return [
			'name' => $toolname,
			'description' => $verb . ' ' . $ep['label'] . ' through the Dolibarr REST API (auto-generated tool). ' . $summary,
			'inputSchema' => $schema
		];
	}

	/**
	 * Convert a docblock type to a JSON Schema type.
	 *
	 * @param string $type Docblock type (may be a union like int|string)
	 * @return string JSON Schema type
	 */
	private function docTypeToJson(string $type): string
	{
		$t = strtolower(trim(explode('|', $type)[0]));
		if (in_array($t, ['int', 'integer'], true)) {
			return 'integer';
		}
		if (in_array($t, ['float', 'double'], true)) {
			return 'number';
		}
		if ($t === 'bool' || $t === 'boolean') {
			return 'boolean';
		}
		return 'string';
	}

	/**
	 * Return categories this tool belongs to.
	 *
	 * @return array<string> List of categories
	 */
	public function getCategories(): array
	{
		return ['thirdparty', 'commercial', 'billing', 'stock', 'reporting'];
	}

	/**
	 * Execute a bridged tool: authenticate the service user, call the API method
	 * in-process with positional arguments, catch RestException.
	 *
	 * @param string $name The tool name (e.g. 'api_thirdparties_list').
	 * @param array<string, mixed> $args The tool arguments (named).
	 * @return mixed Result array, or ["error" => ...] on failure.
	 */
	public function execute(string $name, array $args)
	{
		if (!getDolGlobalInt('AI_MCP_API_BRIDGE')) {
			return ["error" => "API bridge is disabled (AI_MCP_API_BRIDGE not set)."];
		}

		$this->getDefinitions();	// ensure routes are built
		if (empty($this->routes[$name])) {
			return ["error" => "Tool function '$name' not found."];
		}
		list($key, $method) = $this->routes[$name];
		$ep = $this->endpoints[$key];

		// --- Authentication bridge (in-process replacement of DolibarrApiAccess::__isAllowed) ---
		// The API endpoint methods read the authenticated user from DolibarrApiAccess::$user
		// and their permission checks (hasRight) run against it. TODO: replicate entity
		// switching for multicompany setups.
		$this->loadApiRuntime();
		DolibarrApiAccess::$user = $this->user;
		$GLOBALS['user'] = $this->user;

		require_once DOL_DOCUMENT_ROOT . $ep['path'];
		$api = new $ep['class']();

		// Map named MCP args onto the method's positional signature.
		$rm = new ReflectionMethod($ep['class'], $method);
		$callArgs = [];
		foreach ($rm->getParameters() as $p) {
			$pname = $p->getName();
			if (array_key_exists($pname, $args)) {
				$callArgs[] = $args[$pname];
			} elseif ($p->isOptional()) {
				$callArgs[] = $p->getDefaultValue();
			} else {
				return ["error" => "Missing required parameter '$pname'."];
			}
		}

		try {
			$result = call_user_func_array([$api, $method], $callArgs);
			// Serialize API return (cleaned objects) into plain arrays for the MCP client.
			return json_decode(json_encode($result), true);
		} catch (Throwable $e) {
			$code = (int) $e->getCode();
			return [
				"error" => $e->getMessage(),
				"http_status" => ($code > 0 ? $code : 500)
			];
		}
	}
}
