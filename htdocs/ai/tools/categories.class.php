<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 * Copyright (C) 2026		MDW						<mdeweerd@users.noreply.github.com>
 *
 * This program is free software; you can redistribute it and/or modify
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
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/ai/tools/categories.class.php
 * \ingroup ai
 * \brief MCP Server tool for Dolibarr categories.
 */

require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

/**
 * Class ToolCategories
 *
 * Provides various tools related to Dolibarr categories.
 */
class ToolCategories extends McpTool
{
	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB		$db				Database handler
	 * 	@param	User		$user			User object for permission checks
	 */
	public function __construct($db, $user)
	{
		$this->db = $db;
		$this->user = $user;
	}

	/**
	 * @var array<string,int>|null Cached category type map (scope string => integer type ID)
	 */
	private $categoryTypeMapCache = null;

	/**
	 * @var array<int,string>|null Cached reverse type map (integer type ID => scope string)
	 */
	private $reverseTypeMapCache = null;

	/**
	 * Get mapping of scope strings to integer type IDs (as stored in database).
	 * Uses the Categorie class MAP_ID to ensure consistency, including hook-extended types.
	 *
	 * @return array<string, int> Associative array mapping scope names to integer type IDs
	 */
	private function getCategoryTypeMap()
	{
		if ($this->categoryTypeMapCache !== null) {
			return $this->categoryTypeMapCache;
		}

		$tmpCat = new Categorie($this->db);

		$this->categoryTypeMapCache = $tmpCat->MAP_ID;

		return $this->categoryTypeMapCache;
	}

	/**
	 * Get reverse mapping of integer type IDs to scope strings
	 *
	 * @return array<int, string> Associative array mapping integer type IDs to scope names
	 */
	private function getReverseTypeMap()
	{
		if ($this->reverseTypeMapCache !== null) {
			return $this->reverseTypeMapCache;
		}

		$this->reverseTypeMapCache = array_flip($this->getCategoryTypeMap());
		return $this->reverseTypeMapCache;
	}

	/**
	 * Get list of valid category scopes
	 *
	 * @return string[] Array of valid scope strings
	 */
	private function getValidScopes()
	{
		return array_keys($this->getCategoryTypeMap());
	}

	/**
	 * Get scope string from category type (integer ID as stored in database)
	 *
	 * @param int $type Category type ID (integer from database)
	 * @return string|null Scope string or null if not found
	 */
	private function getScopeFromType($type)
	{
		$reverseMap = $this->getReverseTypeMap();
		return $reverseMap[$type] ?? null;
	}

	/**
	 * Check if user has create permission on categories
	 *
	 * @param int $category_type Category type ID (integer)
	 * @return bool
	 */
	private function hasCategoryCreatePermission($category_type)
	{
		// Get the scope string for permission checks
		$scope = $this->getScopeFromType($category_type);
		if ($scope === null) {
			return false;
		}

		if ($this->user->hasRight('categorie', 'creer')) {
			return true;
		}

		// Type-specific permissions based on scope
		switch ($scope) {
			case 'product':
			case 'service':
				return (bool) $this->user->hasRight('produit', 'creer');
			case 'supplier':
			case 'customer':
				return (bool) $this->user->hasRight('societe', 'creer');
			case 'member':
				return (bool) $this->user->hasRight('adherent', 'creer');
			case 'contact':
				return (bool) $this->user->hasRight('societe', 'contact', 'creer');
			case 'user':
				return (bool) $this->user->hasRight('user', 'user', 'creer');
			case 'project':
				return (bool) $this->user->hasRight('projet', 'creer');
			case 'ticket':
				return (bool) $this->user->hasRight('ticket', 'write');
			case 'website_page':
				return (bool) $this->user->hasRight('website', 'write');
			case 'knowledgemanagement':
				return (bool) $this->user->hasRight('knowledgemanagement', 'knowledgerecord', 'write');
			case 'fichinter':
				return (bool) $this->user->hasRight('fichinter', 'creer');
			case 'order':
				return (bool) $this->user->hasRight('commande', 'creer');
			case 'invoice':
				return (bool) $this->user->hasRight('facture', 'creer');
			case 'supplier_order':
				return (bool) $this->user->hasRight('supplier_order', 'creer');
			case 'supplier_invoice':
				return (bool) $this->user->hasRight('supplier_invoice', 'creer');
			case 'supplier_proposal':
				return (bool) $this->user->hasRight('supplier_proposal', 'creer');
			case 'propal':
				return (bool) $this->user->hasRight('propal', 'creer');
			case 'project_task':
				return (bool) $this->user->hasRight('projet', 'creer');
			case 'mo':
				return (bool) $this->user->hasRight('mrp', 'creer');
			default:
				return false;
		}
	}

	/**
	 * Returns an array of tool definitions, including name, description, and input schema.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		$validScopes = $this->getValidScopes();

		return [
			[
				"name" => "search_categories",
				"description" => "Search for categories by name or description. Returns a list of matching categories with their IDs, labels, types, and direct links. In Dolibarr, each category has a single type (scope) that determines what kind of objects it can be applied to (e.g., 'product', 'ticket', 'customer').",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"query" => [
							"type" => "string",
							"description" => "Partial category name or description to search for (case-insensitive)."
						],
						"scope" => [
							"type" => "string",
							"enum" => $validScopes,
							"description" => "Filter by a specific category scope/type (e.g., 'product', 'customer', 'project', 'ticket', 'user')."
						],
						"limit" => [
							"type" => "integer",
							"description" => "Maximum number of results to return (default: 20).",
							"default" => 20
						],
						"offset" => [
							"type" => "integer",
							"description" => "Offset for pagination (default: 0).",
							"default" => 0
						]
					],
					"required" => []
				]
			],
			[
				"name" => "get_category_details",
				"description" => "Retrieve comprehensive details for a specific category including its hierarchy (parent/children), linked objects count, and extrafields. Provide either the unique `category_id` (integer) or the `category_name` (exact label, case-insensitive). If using `category_name` and multiple categories share that name, use `scope` to disambiguate.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"category_id" => [
							"type" => "integer",
							"description" => "The unique numerical identifier of the category."
						],
						"category_name" => [
							"type" => "string",
							"description" => "The exact label (name) of the category (case-insensitive)."
						],
						"scope" => [
							"type" => "string",
							"enum" => $validScopes,
							"description" => "Optional: Filter by scope if `category_name` matches multiple categories with different types."
						]
					],
					"oneOf" => [
						["required" => ["category_id"]],
						["required" => ["category_name"]]
					]
				]
			],
			[
				"name" => "create_category",
				"description" => "Creates a new category in Dolibarr. Requires a 'label' (the category name) and a 'scope' (what type of object it applies to). Example: to create a category 'Urgent' for tickets, use label='Urgent' and scope='ticket'.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"label" => [
							"type" => "string",
							"description" => "The name of the category to create. (Required)."
						],
						"scope" => [
							"type" => "string",
							"enum" => $validScopes,
							"description" => "The type of objects this category applies to. Examples: 'product' for items, 'invoice' for customer bills, 'supplier' for vendors, 'ticket' for support tickets, 'project' for projects. (Required)."
						],
						"description" => [
							"type" => "string",
							"description" => "An optional detailed description for the category."
						],
						"parent_category_id" => [
							"type" => "integer",
							"description" => "Optional: The ID of an existing parent category (must be same scope)."
						],
						"color" => [
							"type" => "string",
							"description" => "Optional: Hex color code (e.g., '#FF5733')."
						]
					],
					"required" => ["label", "scope"]
				]
			]
		];
	}

	/**
	 * Return categories this tool belongs to.
	 * Used by the intent parser to filter available tools.
	 *
	 * @return array<string> List of categories (e.g., ['billing', 'commercial'])
	 */
	public function getCategories(): array
	{
		return ['category', 'stock', 'thirdparty', 'project'];
	}

	/**
	 * Executes the requested tool function based on its name.
	 *
	 * @param string $name The name of the tool to execute.
	 * @param array<string, mixed> $args The arguments for the tool (key-value pairs).
	 * @return mixed The result of the tool execution (usually an array) or an error array.
	 */
	public function execute(string $name, array $args)
	{
		switch ($name) {
			case 'search_categories':
				return $this->searchCategories($args);
			case 'get_category_details':
				return $this->getCategoryDetails($args);
			case 'create_category':
				return $this->createCategory($args);
			default:
				return ["error" => "Tool function '$name' not found."];
		}
	}

	/**
	 * Internal helper to resolve a category ID from its name (label) and optional scope.
	 * Returns category ID on success, or an error array.
	 *
	 * @param string $category_name The label to search for.
	 * @param string|null $scope Optional category scope (e.g., 'product', 'ticket').
	 * @return int|array<string, mixed> Category ID or an error array.
	 */
	private function resolveCategoryIdFromName($category_name, $scope = null)
	{

		if (
			!$this->user->hasRight('categorie', 'lire')
			&& !$this->user->hasRight('produit', 'lire')
			&& !$this->user->hasRight('societe', 'lire')
		) {
			return ["error" => "Permission Denied: User does not have read access to categories."];
		}

		$search_term = trim($category_name);
		$cat_type_map = $this->getCategoryTypeMap();

		$search_lower = strtolower($search_term);

		$sql = "SELECT c.rowid, c.label, c.type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie as c";
		$sql .= " WHERE c.entity IN (" . getEntity('category') . ")";
		$sql .= " AND LOWER(c.label) = '" . $this->db->escape($search_lower) . "'";

		if (!empty($scope)) {
			if (!isset($cat_type_map[$scope])) {
				return ["error" => "Invalid category scope '{$scope}' provided for lookup. Valid scopes: " . implode(', ', array_keys($cat_type_map))];
			}
			$sql .= " AND c.type = " . ((int) $cat_type_map[$scope]);
		}

		$sql .= " LIMIT 2";

		dol_syslog("ToolCategories::resolveCategoryIdFromName SQL: " . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			return ["error" => "Database error during category ID lookup for '{$category_name}': " . $this->db->lasterror()];
		}

		$num_rows = $this->db->num_rows($resql);

		if ($num_rows == 1) {
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			return (int) $obj->rowid;
		} elseif ($num_rows > 1) {
			$results = [];
			while ($obj = $this->db->fetch_object($resql)) {
				$results[] = [
					"id" => (int) $obj->rowid,
					"label" => $obj->label,
					"scope" => $this->getScopeFromType((int) $obj->type) ?? "unknown",
					"url" => dol_buildpath('/categories/viewcat.php', 1) . "?id=" . $obj->rowid . "&type=" . $obj->type
				];
			}
			$this->db->free($resql);
			return [
				"error" => "Multiple categories found with the label '{$category_name}'. Please specify a 'scope' to narrow down the result.",
				"matches" => $results
			];
		} else {
			$this->db->free($resql);

			// Try to find partial matches to help the user
			$hint_sql = "SELECT c.rowid, c.label, c.type";
			$hint_sql .= " FROM " . MAIN_DB_PREFIX . "categorie as c";
			$hint_sql .= " WHERE c.entity IN (" . getEntity('category') . ")";
			$hint_sql .= " AND LOWER(c.label) LIKE '%" . $this->db->escape($search_lower) . "%'";
			$hint_sql .= " LIMIT 5";

			$hint_resql = $this->db->query($hint_sql);
			$hints = [];
			if ($hint_resql) {
				while ($hint_obj = $this->db->fetch_object($hint_resql)) {
					$hints[] = [
						"id" => (int) $hint_obj->rowid,
						"label" => $hint_obj->label,
						"scope" => $this->getScopeFromType((int) $hint_obj->type) ?? "unknown"
					];
				}
				$this->db->free($hint_resql);
			}

			$error_msg = "Category with exact label '{$category_name}' not found.";
			if (!empty($hints)) {
				$error_msg .= " Did you mean one of these? " . json_encode($hints);
			}
			$error_msg .= " Use 'search_categories' with a partial name to find similar categories.";

			return ["error" => $error_msg];
		}
	}

	/**
	 * Searches for categories based on a query and type.
	 *
	 * @param array<string, mixed> $args Array containing 'query' (string), 'scope' (string), 'limit' (int), 'offset' (int).
	 * @return array{error:string}|array{count:int}|list<array<string, mixed>> A list of found categories or an error array.
	 */
	private function searchCategories($args)
	{
		if (
			!$this->user->hasRight('categorie', 'lire')
			&& !$this->user->hasRight('produit', 'lire')
			&& !$this->user->hasRight('societe', 'lire')
		) {
			return [[
				"error" => "Permission Denied: User does not have read access to categories."
			]];
		}

		$query = !empty($args['query']) ? trim($args['query']) : '';
		$scope_filter = !empty($args['scope']) ? $args['scope'] : '';
		$limit = isset($args['limit']) ? max(1, min(100, (int) $args['limit'])) : 20;
		$offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;

		// Safety fallback
		if ($limit <= 0) {
			$limit = 5;
		}
		if ($limit > 1000) {
			dol_syslog("Search DB Error: Too many record requested", LOG_ERR);
			return ["error" => "DB Error"];
		}

		$cat_type_map = $this->getCategoryTypeMap();

		$sql = "SELECT c.rowid, c.label, c.description, c.type, c.color, c.fk_parent";
		$sql .= " FROM " . MAIN_DB_PREFIX . "categorie as c";
		$sql .= " WHERE c.entity IN (" . getEntity('category') . ")";

		if (!empty($query)) {
			$query_lower = strtolower($query);

			$sql .= " AND (LOWER(c.label) LIKE '%" . $this->db->escape($query_lower) . "%' OR LOWER(c.description) LIKE '%" . $this->db->escape($query_lower) . "%')";
		}

		if (!empty($scope_filter)) {
			if (!isset($cat_type_map[$scope_filter])) {
				return [[
					"error" => "Invalid category scope '{$scope_filter}'. Valid scopes: " . implode(', ', array_keys($cat_type_map))
				]];
			}
			$sql .= " AND c.type = " . ((int) $cat_type_map[$scope_filter]);
		}

		$sql .= " ORDER BY c.label ASC";
		$sql .= $this->db->plimit($limit, $offset);

		dol_syslog("ToolCategories::searchCategories SQL: " . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);
		$list = [];

		if ($resql) {
			$type_labels = [
				'product' => 'Product',
				'service' => 'Service',
				'supplier' => 'Supplier',
				'customer' => 'Customer/Prospect',
				'member' => 'Member',
				'contact' => 'Contact',
				'user' => 'User',
				'project' => 'Project',
				'ticket' => 'Ticket',
				'warehouse' => 'Warehouse',
				'order' => 'Customer Order',
				'invoice' => 'Customer Invoice',
				'propal' => 'Proposal',
				'supplier_order' => 'Supplier Order',
				'supplier_invoice' => 'Supplier Invoice',
				'supplier_proposal' => 'Supplier Proposal',
				'fichinter' => 'Intervention',
				'project_task' => 'Project Task',
				'bank_account' => 'Bank Account',
				'bank_line' => 'Bank Transaction',
				'actioncomm' => 'Agenda Event',
				'website_page' => 'Website Page',
				'knowledgemanagement' => 'Knowledge Record',
				'mo' => 'Manufacturing Order',
			];

			while ($r = $this->db->fetch_object($resql)) {
				$type_id = (int) $r->type;
				$scope_str = $this->getScopeFromType($type_id);

				$list[] = [
					"id" => (int) $r->rowid,
					"label" => $r->label,
					"description" => $r->description ?? '',
					"type" => $type_labels[$scope_str] ?? $scope_str ?? "Unknown",
					"color" => $r->color ?? null,
					"parent_id" => $r->fk_parent ? (int) $r->fk_parent : null,
					"url" => dol_buildpath('/categories/viewcat.php', 1) . "?id=" . (int) $r->rowid . "&type=" . $type_id
				];
			}
			$this->db->free($resql);
		}

		if (empty($list)) {
			$msg = "No categories found";
			if (!empty($query)) {
				$msg .= " matching '{$query}'";
			}
			if (!empty($scope_filter)) {
				$msg .= " in scope '{$scope_filter}'";
			}
			return [[
				"info" => $msg . "."
			]];
		}

		return $list;
	}

	/**
	 * Retrieves comprehensive details for a specific category.
	 *
	 * @param array<string, mixed> $args Array containing 'category_id' (int) or 'category_name' (string) and optional 'scope'.
	 * @return array<int, array<string, mixed>>|array<string, mixed> Category details or an error array.
	 */
	private function getCategoryDetails($args)
	{

		if (
			!$this->user->hasRight('categorie', 'lire')
			&& !$this->user->hasRight('produit', 'lire')
			&& !$this->user->hasRight('societe', 'lire')
		) {
			return ["error" => "Permission Denied: User does not have read access to categories."];
		}

		$category_id = 0;
		if (!empty($args['category_id'])) {
			$category_id = (int) $args['category_id'];
			if ($category_id <= 0) {
				return ["error" => "Invalid category_id provided. Must be a positive integer."];
			}
		} elseif (!empty($args['category_name'])) {
			$resolved_id = $this->resolveCategoryIdFromName($args['category_name'], $args['scope'] ?? null);
			if (is_array($resolved_id) && isset($resolved_id['error'])) {
				return $resolved_id;
			}
			$category_id = $resolved_id;
		} else {
			return ["error" => "Missing 'category_id' or 'category_name' argument. One must be provided to get category details."];
		}

		$cat = new Categorie($this->db);
		$result = $cat->fetch($category_id);
		if ($result <= 0) {
			return ["error" => "Category with ID {$category_id} not found."];
		}

		// Get scope from integer type
		$type_id = (int) $cat->type;
		$scope_str = $this->getScopeFromType($type_id);

		// Type labels for human-readable output
		$type_labels = [
			'product' => 'Product',
			'service' => 'Service',
			'supplier' => 'Supplier',
			'customer' => 'Customer/Prospect',
			'member' => 'Member',
			'contact' => 'Contact',
			'user' => 'User',
			'project' => 'Project',
			'ticket' => 'Ticket',
			'warehouse' => 'Warehouse',
			'order' => 'Customer Order',
			'invoice' => 'Customer Invoice',
			'propal' => 'Proposal',
			'supplier_order' => 'Supplier Order',
			'supplier_invoice' => 'Supplier Invoice',
			'supplier_proposal' => 'Supplier Proposal',
			'fichinter' => 'Intervention',
			'project_task' => 'Project Task',
			'bank_account' => 'Bank Account',
			'bank_line' => 'Bank Transaction',
			'actioncomm' => 'Agenda Event',
			'website_page' => 'Website Page',
			'knowledgemanagement' => 'Knowledge Record',
			'mo' => 'Manufacturing Order',
		];

		// Get children categories
		$children = [];
		$daughters = $cat->get_filles();
		if (is_array($daughters)) {
			foreach ($daughters as $daughter) {
				$children[] = [
					"id" => (int) $daughter->id,
					"label" => $daughter->label,
					"url" => dol_buildpath('/categories/viewcat.php', 1) . "?id=" . $daughter->id . "&type=" . $daughter->type
				];
			}
		}

		// Get parent categories (hierarchy)
		$parents = [];
		$mothers = $cat->get_meres();
		if (is_array($mothers)) {
			foreach ($mothers as $mother) {
				$parents[] = [
					"id" => (int) $mother->id,
					"label" => $mother->label,
					"url" => dol_buildpath('/categories/viewcat.php', 1) . "?id=" . $mother->id . "&type=" . $mother->type
				];
			}
		}

		// Count linked objects based on category type using the scope string
		$linked_objects_count = 0;
		$linked_objects_summary = "";
		if (!empty($scope_str)) {
			// Use the scope string (which matches Categorie::TYPE_* constants) for getObjectsInCateg
			$objects = $cat->getObjectsInCateg($scope_str, 1); // onlyids=1 for efficiency
			$linked_objects_count = is_array($objects) ? count($objects) : 0;
			$linked_objects_summary = "{$linked_objects_count} item" . ($linked_objects_count != 1 ? "s" : "") . " tagged with this category";
		}

		// Load extrafields
		$extrafields = [];
		if (!empty($cat->array_options) && is_array($cat->array_options)) {
			foreach ($cat->array_options as $key => $value) {
				if ($value !== null && $value !== '') {
					$clean_key = preg_replace('/^options_/', '', $key);
					$extrafields[$clean_key] = $value;
				}
			}
		}

		// Build full path for hierarchy display
		$full_path = $cat->label;
		if (!empty($parents)) {
			$path_parts = array_reverse($parents);
			$path_parts[] = ["label" => $cat->label];
			$full_path = implode(' > ', array_map(
				/**
				 * @param array{label:string} $p
				 * @return string
				 */
				static function ($p) {
					return $p['label'];
				},
				$path_parts
			));
		}

		$result = [
			"id" => (int) $cat->id,
			"label" => $cat->label,
			"description" => $cat->description,
			"scope" => $scope_str ?? "unknown",
			"scope_label" => (isset($scope_str) && isset($type_labels[$scope_str])) ? $type_labels[$scope_str] : ($scope_str ?? "Unknown"),
			"type_id" => $type_id,
			"color" => $cat->color,
			"visible" => (int) $cat->visible,
			"full_path" => $full_path,
			"linked_objects" => [
				"count" => $linked_objects_count,
				"summary" => $linked_objects_summary
			],
			"hierarchy" => [
				"parent" => !empty($parents) ? $parents[0] : null,
				"children_count" => count($children),
				"children" => $children
			],
			"extrafields" => !empty($extrafields) ? $extrafields : null,
			"dates" => [
				"created" => $cat->date_creation ? dol_print_date($cat->date_creation, 'dayrfc') : null,
				"modified" => $cat->date_modification ? dol_print_date($cat->date_modification, 'dayrfc') : null
			],
			"url" => dol_buildpath('/categories/viewcat.php', 1) . "?id=" . $cat->id . "&type=" . $type_id
		];

		// Add multilangs if available
		if (!empty($cat->multilangs) && is_array($cat->multilangs)) {
			$result["translations"] = $cat->multilangs;
		}

		return $result;
	}

	/**
	 * Creates a new category in Dolibarr.
	 *
	 * @param array<string, mixed> $args Array containing 'label' (string), 'scope' (string), 'description' (string, optional), 'parent_category_id' (int, optional), 'color' (string, optional).
	 * @return array<string, mixed> Success message with new category ID or an error array.
	 */
	private function createCategory($args)
	{

		$label = trim($args['name'] ?? $args['label'] ?? '');
		$scope = trim($args['scope'] ?? '');
		$description = trim($args['description'] ?? '');
		$parent_category_id = (int) ($args['parent_category_id'] ?? 0);
		$color = trim($args['color'] ?? '');

		// Required fields
		if (empty($label)) {
			return ["error" => "Category label is required."];
		}
		if (empty($scope)) {
			return ["error" => "Category scope is required."];
		}

		$cat_type_map = $this->getCategoryTypeMap();

		// Validate scope
		if (!isset($cat_type_map[$scope])) {
			return ["error" => "Invalid scope '{$scope}' provided. Valid scopes: " . implode(', ', array_keys($cat_type_map))];
		}

		$category_type = (int) $cat_type_map[$scope];

		// Permission check
		if (!$this->hasCategoryCreatePermission($category_type)) {
			return ["error" => "Permission Denied: User does not have rights to create categories of type '{$scope}'."];
		}

		$cat = new Categorie($this->db);
		$cat->label = $label;
		$cat->description = $description;
		$cat->type = (string) $category_type;
		$cat->visible = 1;

		// Set color if provided (remove # prefix if present for storage)
		if (!empty($color)) {
			$cat->color = ltrim($color, '#');
		}

		// Handle parent category
		if ($parent_category_id > 0) {
			$parent_cat = new Categorie($this->db);
			$parent_fetch_result = $parent_cat->fetch($parent_category_id);
			if ($parent_fetch_result <= 0) {
				return ["error" => "Parent category with ID {$parent_category_id} not found."];
			}
			// Validate parent category type matches
			if ((int) $parent_cat->type != $category_type) {
				$parent_scope = $this->getScopeFromType((int) $parent_cat->type) ?? "unknown";
				return ["error" => "Cannot create a category with scope '{$scope}' under a parent with scope '{$parent_scope}'. Category types must match."];
			}
			$cat->fk_parent = $parent_category_id;
		}

		// Check for duplicate label within same type
		$label_lower = strtolower($label);

		$existing_sql = "SELECT COUNT(*) as cnt FROM " . MAIN_DB_PREFIX . "categorie";
		$existing_sql .= " WHERE LOWER(label) = '" . $this->db->escape($label_lower) . "'";
		$existing_sql .= " AND type = " . ((int) $category_type);
		$existing_sql .= " AND entity IN (" . getEntity('category') . ")";

		$existing_res = $this->db->query($existing_sql);
		if ($existing_res) {
			$existing_obj = $this->db->fetch_object($existing_res);
			if ($existing_obj && $existing_obj->cnt > 0) {
				return ["error" => "A category with the label '{$label}' already exists for scope '{$scope}'."];
			}
			$this->db->free($existing_res);
		}

		// Create the category
		$new_category_id = $cat->create($this->user);

		if ($new_category_id > 0) {
			return [
				"success" => true,
				"message" => "Category '{$label}' created successfully.",
				"category_id" => (int) $new_category_id,
				"label" => $label,
				"scope" => $scope,
				"type_id" => $category_type,
				"description" => $description,
				"parent_id" => $parent_category_id > 0 ? $parent_category_id : null,
				"color" => $cat->color,
				"url" => dol_buildpath('/categories/viewcat.php', 1) . "?id=" . $new_category_id . "&type=" . $category_type
			];
		} else {
			$error_msg = $cat->error ?: ($cat->errors ? implode(', ', $cat->errors) : $this->db->lasterror());
			return ["error" => "Failed to create category '{$label}': " . $error_msg];
		}
	}
}
