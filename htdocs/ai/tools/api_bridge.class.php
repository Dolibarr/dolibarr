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
 * Exposure model (per review feedback on the PR):
 *   - Explicit whitelist: a method is exposed ONLY if it is listed under the
 *     'methods' key of its endpoint entry below — nothing is exposed just
 *     because reflection finds it. The current whitelist is read-only
 *     (index/get + a few product read helpers); any write method will have to
 *     be consciously whitelisted later, behind a confirmation gate + body
 *     schemas harvested from each object's ->fields.
 *   - Enrichment: reflection + docblock parsing give the skeleton (route, main
 *     params); each whitelisted method can carry a hand-written complement
 *     ('description' appended to the tool description, 'params' overriding
 *     per-parameter docs) merged over the derived schema, in the spirit of the
 *     hand-written getDefinitions() of the legacy ai/tools/*.class.php — but
 *     only as a complement, never a full rewrite.
 *
 * Remaining WIP limitations (POC scope):
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
	 * Endpoint map + method whitelist: endpoint key => module condition, api class
	 * file/class, and the EXPLICIT list of exposed methods with their optional
	 * hand-written enrichment. A method absent from 'methods' is never exposed.
	 * Per method: 'suffix' (tool name suffix, defaults to list/get/lowercased),
	 * 'description' (appended to the derived tool description), 'params'
	 * (per-parameter doc overriding what the docblock parser guessed).
	 * TODO Replace path/class discovery with the dynamic scan of api_*.class.php
	 * used by api/index.php.
	 *
	 * @var array<string, array{module:string, path:string, class:string, label:string, methods:array<string, array{suffix?:string, description?:string, params?:array<string,string>}>}>
	 */
	private $endpoints = [
		'thirdparties' => [
			'module' => 'societe',
			'path' => '/societe/class/api_thirdparties.class.php',
			'class' => 'Thirdparties',
			'label' => 'third parties (customers, prospects, suppliers)',
			'methods' => [
				'index' => [
					'description' => "Use 'mode' to restrict to a nature of third party instead of filtering on names.",
					'params' => [
						'mode' => "Nature filter: 0=all (default), 1=customers/prospects, 2=prospects only, 3=neither customer nor prospect, 4=suppliers.",
						'category' => "Rowid of a third-party category (tag) to restrict the list to."
					]
				],
				'get' => []
			]
		],
		'proposals' => [
			'module' => 'propal',
			'path' => '/comm/propal/class/api_proposals.class.php',
			'class' => 'Proposals',
			'label' => 'commercial proposals (quotes / devis)',
			'methods' => [
				'index' => [
					'params' => [
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5')."
					]
				],
				'get' => []
			]
		],
		'tickets' => [
			'module' => 'ticket',
			'path' => '/ticket/class/api_tickets.class.php',
			'class' => 'Tickets',
			'label' => 'support tickets',
			'methods' => ['index' => [], 'get' => []]
		],
		'projects' => [
			'module' => 'projet',
			'path' => '/projet/class/api_projects.class.php',
			'class' => 'Projects',
			'label' => 'projects (including opportunities/leads)',
			'methods' => ['index' => [], 'get' => []]
		],
		'tasks' => [
			'module' => 'projet',
			'path' => '/projet/class/api_tasks.class.php',
			'class' => 'Tasks',
			'label' => 'project tasks',
			'methods' => ['index' => [], 'get' => []]
		],
		'agendaevents' => [
			'module' => 'agenda',
			'path' => '/comm/action/class/api_agendaevents.class.php',
			'class' => 'AgendaEvents',
			'label' => 'agenda / calendar events (meetings, calls)',
			'methods' => ['index' => [], 'get' => []]
		],
		'interventions' => [
			'module' => 'ficheinter',
			'path' => '/fichinter/class/api_interventions.class.php',
			'class' => 'Interventions',
			'label' => 'field service interventions',
			'methods' => ['index' => [], 'get' => []]
		],
		'contracts' => [
			'module' => 'contrat',
			'path' => '/contrat/class/api_contracts.class.php',
			'class' => 'Contracts',
			'label' => 'contracts (recurring services)',
			'methods' => ['index' => [], 'get' => []]
		],
		'members' => [
			'module' => 'adherent',
			'path' => '/adherents/class/api_members.class.php',
			'class' => 'Members',
			'label' => 'foundation/association members',
			'methods' => ['index' => [], 'get' => []]
		],
		'subscriptions' => [
			'module' => 'adherent',
			'path' => '/adherents/class/api_subscriptions.class.php',
			'class' => 'Subscriptions',
			'label' => 'member subscriptions',
			'methods' => ['index' => [], 'get' => []]
		],
		'stockmovements' => [
			'module' => 'stock',
			'path' => '/product/stock/class/api_stockmovements.class.php',
			'class' => 'StockMovements',
			'label' => 'stock movements (in/out/transfer history)',
			'methods' => [
				'index' => [
					'description' => "History of physical stock changes; each movement carries product, warehouse, qty (signed) and date."
				],
				'get' => []
			]
		],
		'warehouses' => [
			'module' => 'stock',
			'path' => '/product/stock/class/api_warehouses.class.php',
			'class' => 'Warehouses',
			'label' => 'warehouses',
			'methods' => ['index' => [], 'get' => []]
		],
		'expensereports' => [
			'module' => 'expensereport',
			'path' => '/expensereport/class/api_expensereports.class.php',
			'class' => 'ExpenseReports',
			'label' => 'employee expense reports (notes de frais)',
			'methods' => ['index' => [], 'get' => []]
		],
		'products' => [
			'module' => 'product',
			'path' => '/product/class/api_products.class.php',
			'class' => 'Products',
			'label' => 'products and services catalog',
			'methods' => [
				'index' => [],
				'get' => [],
				'getAttributes' => [
					'suffix' => 'attributes_list',
					'description' => "Variant attributes (e.g. Size, Color) defined in the catalog."
				],
				'getVariants' => [
					'suffix' => 'variants_list',
					'description' => "Variants of one parent product.",
					'params' => ['id' => 'Rowid of the PARENT product.']
				]
			]
		],
		// NB: stock inventories have no REST API class in core yet (no api_inventories) —
		// they cannot be bridged until one exists.
	];

	/**
	 * Fallback docs for parameters shared by most API index()/get() methods, used
	 * when neither the per-method 'params' enrichment nor the docblock provides a
	 * usable description. Kept in one place so every bridged list tool documents
	 * the pagination/filter contract the same way.
	 *
	 * @var array<string, string>
	 */
	private $commonParamDocs = [
		'sortfield' => "Field to sort on, prefixed with 't.' (e.g. 't.rowid', 't.ref', 't.datec').",
		'sortorder' => "Sort direction: 'ASC' or 'DESC'.",
		'limit' => "Maximum number of records to return.",
		'page' => "Zero-based page index for pagination.",
		'sqlfilters' => "Universal search filter. Example: \"(t.ref:like:'PR%') and (t.datec:>=:'2026-01-01')\". Field names are prefixed with 't.'; operators: =, !=, <, <=, >, >=, like, is; combine clauses with 'and'/'or' and parentheses.",
		'properties' => "Comma-separated list of properties to include in the response, to reduce its size (e.g. 'id,ref,label').",
		'id' => "Rowid (numeric technical id) of the record."
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

			// Explicit whitelist: only the methods listed in the endpoint's
			// 'methods' entry are exposed — nothing else, whatever reflection
			// could find on the API class.
			foreach ($ep['methods'] as $method => $meta) {
				if (!method_exists($ep['class'], $method)) {
					continue;	// whitelisted method absent in this Dolibarr version
				}
				$suffix = $meta['suffix'] ?? ($method === 'index' ? 'list' : strtolower($method));
				$toolname = 'api_' . $key . '_' . $suffix;
				$def = $this->buildToolDefinition($ep, $key, $method, $toolname, $meta);
				if ($def) {
					$this->defs[] = $def;
					$this->routes[$toolname] = [$key, $method];
				}
			}
		}

		return $this->defs;
	}

	/**
	 * Build one MCP tool definition from an API class method: reflection + docblock
	 * give the skeleton, then the hand-written per-method enrichment is merged over
	 * it ('description' appended, 'params' overriding parameter docs, shared
	 * common docs as last fallback).
	 *
	 * @param array{module:string, path:string, class:string, label:string} $ep Endpoint entry
	 * @param string $key      Endpoint key (e.g. 'thirdparties')
	 * @param string $method   Whitelisted API method name (e.g. 'index', 'get')
	 * @param string $toolname Generated tool name
	 * @param array{suffix?:string, description?:string, params?:array<string,string>} $meta Hand-written enrichment for this method
	 * @return array<string, mixed>|null Tool definition, or null on reflection failure
	 */
	private function buildToolDefinition(array $ep, string $key, string $method, string $toolname, array $meta = [])
	{
		try {
			$rm = new ReflectionMethod($ep['class'], $method);
		} catch (ReflectionException $e) {
			return null;
		}

		$doc = (string) $rm->getDocComment();

		// First docblock line = human description of the endpoint. The leading
		// asterisk of the line is excluded so "/**\n * Foo" yields "Foo", not "* Foo".
		$summary = '';
		if (preg_match('/\*\s+([^@\s\/*][^\n]*)/', $doc, $m)) {
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
			// Parameter doc priority: hand-written per-method enrichment, then the
			// description guessed from the docblock, then the shared common docs.
			if (isset($meta['params'][$pname])) {
				$pdesc = $meta['params'][$pname];
			} elseif (!empty($paramDocs[$pname]['desc'])) {
				$pdesc = $paramDocs[$pname]['desc'];
			} else {
				$pdesc = $this->commonParamDocs[$pname] ?? '';
			}
			$prop = [
				'type' => $ptype,
				'description' => $pdesc
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

		if ($method === 'index') {
			$verb = 'List / search';
		} elseif ($method === 'get') {
			$verb = 'Get one record of';
		} else {
			$verb = 'Read from';	// other whitelisted read helpers (e.g. product variants)
		}
		$schema = ['type' => 'object', 'properties' => $properties];
		if ($required) {
			$schema['required'] = $required;
		}

		$description = $verb . ' ' . $ep['label'] . ' through the Dolibarr REST API (auto-generated tool). ' . $summary;
		if (!empty($meta['description'])) {
			$description = rtrim($description) . ' ' . $meta['description'];
		}

		return [
			'name' => $toolname,
			'description' => $description,
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
