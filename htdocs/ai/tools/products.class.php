<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/ai/tools/products.class.php
 * \ingroup ai
 * \brief MCP Server tool for products and services.
 *
 * This tool provides functionalities to search, analyze, and retrieve
 * comprehensive details about products and services within Dolibarr.
 * It also includes inventory analysis and supplier pricing information.
 */


require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/categories/class/categorie.class.php';

/**
 * Class ToolProducts
 *
 * Provides various tools related to Dolibarr products and services.
 */
class ToolProducts extends McpTool
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
	 * Returns an array of tool definitions, including name, description, and input schema.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return [
			[
				"name" => "search_products",
				"description" => "Search products and services by name or reference. Returns a list of matching items, including their unique IDs, physical stock, and price. Use this tool for general searches or when a product name might be ambiguous and you need to see multiple options.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"query" => [
							"type" => "string",
							"description" => "Partial product or service name/reference to search for."
						],
						"type" => [
							"type" => "string",
							"enum" => ["product", "service"],
							"description" => "Filter by type: 'product' (0) or 'service' (1)."
						],
						"limit" => [
							"type" => "integer",
							"description" => "Maximum number of results to return (default: 10).",
							"default" => 10
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
				"name" => "get_product_details",
				"description" => "Retrieve comprehensive details for a *single* product or service. You can provide either its unique `product_id` (integer) or its `product_name` (string, case-insensitive reference/label/barcode). If `product_name` is provided and is ambiguous, an error will be returned.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"product_id" => [
							"type" => "integer",
							"description" => "The unique numerical identifier of the product or service."
						],
						"product_name" => [
							"type" => "string",
							"description" => "The reference code, label, or barcode of the product or service (case-insensitive)."
						],
						"type" => [
							"type" => "string",
							"enum" => ["product", "service"],
							"description" => "Optional: Filter by type if `product_name` is provided and multiple items share the same name/reference."
						]
					],
					"oneOf" => [
						["required" => ["product_id"]],
						["required" => ["product_name"]]
					]
				]
			],
			[
				"name" => "analyze_stock_forecast",
				"description" => "Perform an inventory analysis for a specific product. You can provide either its unique `product_id` (integer) or its `product_name` (string, case-insensitive reference/label/barcode). This tool calculates virtual stock, forecasts burn rate based on recent sales, and suggests replenishment actions. If `product_name` is provided and is ambiguous, an error will be returned, suggesting to use 'search_products' first.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"product_id" => [
							"type" => "integer",
							"description" => "The unique numerical identifier of the product to analyze."
						],
						"product_name" => [
							"type" => "string",
							"description" => "The reference code, label, or barcode of the product to analyze (case-insensitive)."
						],
						"type" => [
							"type" => "string",
							"enum" => ["product", "service"],
							"description" => "Optional: Filter by type if `product_name` is provided and multiple items share the same name/reference."
						]
					],
					"oneOf" => [
						["required" => ["product_id"]],
						["required" => ["product_name"]]
					]
				]
			],
			[
				"name" => "get_supplier_prices",
				"description" => "Retrieve a list of all defined supplier prices for a given product or service. You can provide either its unique `product_id` (integer) or its `product_name` (string, case-insensitive reference/label/barcode). If `product_name` is provided and is ambiguous, an error will be returned, suggesting to use 'search_products' first.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"product_id" => [
							"type" => "integer",
							"description" => "The unique numerical identifier of the product or service."
						],
						"product_name" => [
							"type" => "string",
							"description" => "The reference code, label, or barcode of the product or service (case-insensitive)."
						],
						"type" => [
							"type" => "string",
							"enum" => ["product", "service"],
							"description" => "Optional: Filter by type if `product_name` is provided and multiple items share the same name/reference."
						]
					],
					"oneOf" => [
						["required" => ["product_id"]],
						["required" => ["product_name"]]
					]
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
		return ['stock', 'commercial', 'billing', 'reporting'];
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
			case 'search_products':
				return $this->search($args);
			case 'get_product_details':
				return $this->getDetails($args);
			case 'analyze_stock_forecast':
				return $this->analyze($args);
			case 'get_supplier_prices':
				return $this->getSupplierPrices($args);
			default:
				return ["error" => "Tool function '$name' not found."];
		}
	}

	/**
	 * Find a product by various identifiers (ID, Ref, Barcode, Label).
	 *
	 * @param   string|int       $identifier The search term (ID, ref, barcode, etc.).
	 * @param   string|null      $type       Optional filter: 'product' or 'service'.
	 *
	 * @return  Product|array{error: string, matches?: list<string>} Returns the Product object on success, or an error array.
	 */
	private function findProduct($identifier, ?string $type = null)
	{
		$product = new Product($this->db);
		// Cast strictly to string for string manipulation
		$searchString = trim((string) $identifier);

		// Regex to handle "id: 123" format
		$matches = [];
		if (preg_match('/^(?:id)[:\s]+(\d+)$/i', $searchString, $matches)) {
			$searchString = $matches[1];
		}

		// Try Fetch by ID (type filter is ignored for explicit IDs as they are unique)
		if (is_numeric($searchString)) {
			if ($product->fetch((int) $searchString) > 0) {
				// If type was explicitly requested, validate it matches
				if ($type !== null) {
					$expectedType = ($type === 'service') ? 1 : 0;
					if ((int) $product->type !== $expectedType) {
						return ["error" => "Product with ID {$searchString} found, but it is not a '{$type}'. It is a '" . (($product->type == 1) ? "service" : "product") . "'."];
					}
				}
				return $product;
			}
		}

		// Try Fetch by Ref (Standard fetch second argument)
		if ($product->fetch(0, $searchString) > 0) {
			// If type was explicitly requested, validate it matches
			if ($type !== null) {
				$expectedType = ($type === 'service') ? 1 : 0;
				if ((int) $product->type !== $expectedType) {
					// Ref matched, but type doesn't match. Don't return error yet,
					// let the SQL search below try to find the correct typed match.
				} else {
					return $product;
				}
			} else {
				return $product;
			}
		}

		// Build type filter for SQL
		$sqlTypeFilter = "";
		if ($type !== null) {
			if ($type === 'product') {
				$sqlTypeFilter = " AND fk_product_type = 0";
			} elseif ($type === 'service') {
				$sqlTypeFilter = " AND fk_product_type = 1";
			}
		}

		// Custom SQL Search (Barcode, Label, Ref - Exact Match)
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product";
		$sql .= " WHERE (barcode = '" . $this->db->escape($searchString) . "' OR label = '" . $this->db->escape($searchString) . "' OR ref = '" . $this->db->escape($searchString) . "')";
		$sql .= " AND entity IN (" . getEntity('product') . ")";
		$sql .= $sqlTypeFilter;
		$sql .= " LIMIT 1";

		$res = $this->db->query($sql);
		if ($res) {
			$numRows = $this->db->num_rows($res);
			if ($numRows > 0) {
				$row = $this->db->fetch_object($res);
				if ($row) {
					$product->fetch((int) $row->rowid);
					$this->db->free($res);
					return $product;
				}
			}
			$this->db->free($res);
		}

		// Loose match (LIKE) if exact match fails
		$sql = "SELECT rowid, ref, label FROM " . MAIN_DB_PREFIX . "product";
		$sql .= " WHERE (ref LIKE '%" . $this->db->escape($searchString) . "%' OR label LIKE '%" . $this->db->escape($searchString) . "%')";
		$sql .= " AND entity IN (" . getEntity('product') . ")";
		$sql .= " AND tosell = 1"; // Only fetch products available for sale
		$sql .= $sqlTypeFilter;
		$sql .= " LIMIT 5";

		$res = $this->db->query($sql);

		if ($res) {
			$numRows = $this->db->num_rows($res);
			if ($numRows > 0) {
				// If exactly one match found via loose search, use it
				if ($numRows == 1) {
					$row = $this->db->fetch_object($res);
					if ($row) {
						$product->fetch((int) $row->rowid);
						$this->db->free($res);
						return $product;
					}
				}

				// If multiple matches, return list for ambiguity error
				$matchList = [];
				while ($row = $this->db->fetch_object($res)) {
					$matchList[] = $row->ref . " - " . $row->label;
				}
				$this->db->free($res);

				return [
					"error"   => "Multiple products found for '" . $searchString . "'",
					"matches" => $matchList
				];
			}
			$this->db->free($res);
		}

		$typeHint = ($type !== null) ? " (of type '{$type}')" : "";
		return ["error" => "Product" . $typeHint . " '" . $searchString . "' not found."];
	}

	/**
	 * Extracts the product identifier from arguments.
	 * Returns the identifier string/int or null if missing.
	 *
	 * @param   array{product_id?: int|string, product_name?: string, type?: string} $args Arguments array
	 * @return  array{identifier: string|int|null, type: string|null}   The identifier and optional type.
	 */
	private function extractIdentifier(array $args): array
	{
		$identifier = null;
		if (!empty($args['product_id'])) {
			$identifier = $args['product_id'];
		} elseif (!empty($args['product_name'])) {
			$identifier = $args['product_name'];
		}

		$type = isset($args['type']) ? (string) $args['type'] : null;
		// Validate type value if provided
		if ($type !== null && !in_array($type, ['product', 'service'], true)) {
			$type = null; // Ignore invalid type values
		}

		return [
			'identifier' => $identifier,
			'type'       => $type
		];
	}

	/**
	 * Searches for products or services based on a query, type, and pagination.
	 *
	 * @param   array{query?: string, type?: string, limit?: int, offset?: int} $args Arguments array.
	 * @return  array<string, mixed>    Result array containing 'count', 'offset', 'limit', and 'results'.
	 *                                  Results is a list of array<string, mixed>.
	 *                                  Returns ['error' => string] on failure.
	 */
	private function search(array $args)
	{
		global $langs;

		// Check Permissions
		// Note: This requires read access to BOTH products and services.
		if (!$this->user->hasRight('produit', 'lire') || !$this->user->hasRight('service', 'lire')) {
			return ["error" => "Permission Denied: User does not have read access to products/services."];
		}

		// Validate and Sanitize Inputs
		$query = !empty($args['query']) ? trim((string) $args['query']) : '';
		$type_filter = !empty($args['type']) ? (string) $args['type'] : '';
		// Clamp limit between 1 and 100, default 10
		$limit = isset($args['limit']) ? max(1, min(100, (int) $args['limit'])) : 10;
		$offset = isset($args['offset']) ? max(0, (int) $args['offset']) : 0;

		// Safety fallback
		if ($limit <= 0) {
			$limit = 5;
		}
		if ($limit > 1000) {
			dol_syslog("Search DB Error: Too many record requested", LOG_ERR);
			return ["error" => "DB Error"];
		}

		// Build SQL
		$sql = "SELECT p.rowid, p.ref, p.label, p.price, p.stock, p.seuil_stock_alerte, p.desiredstock, p.fk_product_type";
		$sql .= " FROM " . MAIN_DB_PREFIX . "product as p";
		$sql .= " WHERE p.entity IN (" . getEntity('product') . ")";

		if (!empty($query)) {
			$q_escaped = $this->db->escape(strtolower($query));
			$sql .= " AND (LOWER(p.ref) LIKE '%" . $q_escaped . "%' OR LOWER(p.label) LIKE '%" . $q_escaped . "%')";
		}

		if (!empty($type_filter)) {
			if ($type_filter === 'product') {
				$sql .= " AND p.fk_product_type = 0";
			} elseif ($type_filter === 'service') {
				$sql .= " AND p.fk_product_type = 1";
			}
		}

		$sql .= " ORDER BY p.ref ASC";
		$sql .= $this->db->plimit($limit, $offset);

		dol_syslog(__METHOD__ . " SQL: " . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			dol_syslog(__METHOD__ . " Database error: " . $this->db->lasterror(), LOG_ERR);
			return ["error" => "Database error during search: " . $this->db->lasterror()];
		}

		$data = [];
		while ($obj = $this->db->fetch_object($resql)) {
			// Determine type string
			$typeStr = ($obj->fk_product_type == 0) ? "product" : "service";

			// Format price using global langs
			$priceFormated = price($obj->price, 0, $langs, 0, -1, -1, $this->user->conf->currency ?? '');

			$data[] = [
				"id"              => (int) $obj->rowid,
				"ref"             => $obj->ref,
				"label"           => $obj->label,
				"type"            => $typeStr,
				"physical_stock"  => (float) ($obj->stock ?? 0),
				"min_stock_alert" => (float) ($obj->seuil_stock_alerte ?? 0),
				"desired_stock"   => (float) ($obj->desiredstock ?? 0),
				"price"           => $priceFormated,
				"url"             => dol_buildpath('/product/card.php', 1) . "?id=" . $obj->rowid
			];
		}
		$this->db->free($resql);

		return [
			"count"   => count($data),
			"offset"  => $offset,
			"limit"   => $limit,
			"results" => $data
		];
	}

	/**
	 * Fetch product categories.
	 *
	 * @param   int $product_id Product ID
	 * @return  array<int, array{id: int, label: string, fk_parent: int}> List of categories
	 */
	private function getProductCategories(int $product_id): array
	{
		global $conf;

		// Return empty if Categories module is not enabled
		if (empty($conf->categorie->enabled)) {
			return [];
		}

		// Ensure class is loaded
		dol_include_once('/categories/class/categories.class.php');

		$categories = [];
		$staticCat = new Categorie($this->db);

		$cats = $staticCat->get_categories($product_id);

		if (is_array($cats) && count($cats) > 0) {
			foreach ($cats as $cat) {
				$categories[] = [
					"id"        => (int) $cat->id,
					"label"     => (string) $cat->label,
					"fk_parent" => (int) ($cat->fk_parent ?? 0)
				];
			}
		}

		return $categories;
	}

	/**
	 * Retrieves comprehensive details for a specific product or service.
	 *
	 * @param   array{product_id?: int|string, product_name?: string, type?: string} $args Arguments array.
	 * @return  array<string, mixed>    Product details or an error array.
	 *                                  Returns ['error' => string] on failure.
	 */
	private function getDetails(array $args)
	{
		global $conf;

		// Ensure Product class is loaded
		dol_include_once('/product/class/product.class.php');

		// Check Permissions
		if (!$this->user->hasRight('produit', 'lire') || !$this->user->hasRight('service', 'lire')) {
			return ["error" => "Permission Denied: User does not have read access to products/services."];
		}

		$extracted = $this->extractIdentifier($args);
		if ($extracted['identifier'] === null) {
			return ["error" => "Missing 'product_id' or 'product_name' argument. One must be provided to identify the product."];
		}

		$prod = $this->findProduct($extracted['identifier'], $extracted['type']);
		if (is_array($prod) && isset($prod['error'])) {
			return $prod;
		}

		// Explicitly load extrafields (custom fields)
		$prod->fetch_optionals();

		// Fetch categories using the helper method
		$categories = $this->getProductCategories($prod->id);

		// Process Extrafields
		$extrafields = [];
		if (!empty($prod->array_options) && is_array($prod->array_options)) {
			foreach ($prod->array_options as $key => $value) {
				$clean_key = (string) preg_replace('/^options_/', '', $key);
				$extrafields[$clean_key] = $value;
			}
		}

		// Build Response
		return [
			"id"              => (int) $prod->id,
			"ref"             => $prod->ref,
			"label"           => $prod->label,
			"description"     => $prod->description,
			"type"            => ($prod->type == 1) ? "service" : "product", // 0=Product, 1=Service

			// Statuses
			"status"          => ($prod->status == 1) ? "active" : "inactive", // Sell status
			"status_buy"      => ($prod->status_buy == 1) ? "buyable" : "not_buyable",
			"tobatch"         => (bool) ($prod->tobatch), // Batch/Serial management

			// Financials
			"price_ht"        => (float) ($prod->price),
			"price_ttc"       => (float) ($prod->price_ttc),
			"vat_rate"        => (float) ($prod->tva_tx),

			// Physical Specs
			"weight"          => (float) ($prod->weight),
			"weight_unit"     => (int) ($prod->weight_units ?? 0), // Stores ID of the unit
			"length"          => (float) ($prod->length),
			"length_unit"     => (int) ($prod->length_units ?? 0), // Stores ID of the unit
			"width"           => (float) ($prod->width),
			"height"          => (float) ($prod->height ?? 0),

			// Stocks (Only valid if stock module is enabled, but safe to return 0)
			"physical_stock"  => (float) ($prod->stock_reel),
			"virtual_stock"   => (float) ($prod->stock_theorique),
			"min_stock_alert" => (float) ($prod->seuil_stock_alerte ?? 0),
			"desired_stock"   => (float) ($prod->desiredstock ?? 0),

			// Identifiers
			"barcode"         => $prod->barcode,
			"barcode_type"    => $prod->barcode_type,

			// Meta
			"categories"      => $categories,
			"extrafields"     => $extrafields,
			"notes_private"   => $prod->note_private ?? '',
			"notes_public"    => $prod->note_public ?? '',
			"url"             => dol_buildpath('/product/card.php', 1) . "?id=" . $prod->id
		];
	}

	/**
	 * Get pending customer orders quantity for a product.
	 * Calculates the total quantity found in Sales Orders with status Validated (1) or In Process (2).
	 *
	 * @param   int   $product_id   Product ID
	 * @return  float               Pending quantity
	 */
	private function getPendingCustomerOrdersQty(int $product_id): float
	{
		global $conf;

		$qty = 0.0;

		// Check if Sales Orders module is enabled
		if (empty($conf->commande->enabled)) {
			return $qty;
		}

		$sql = "SELECT SUM(cd.qty) as pending_qty";
		$sql .= " FROM " . MAIN_DB_PREFIX . "commandedet as cd";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "commande as c ON cd.fk_commande = c.rowid";
		$sql .= " WHERE cd.fk_product = " . ((int) $product_id);
		// Status 1 = Validated, 2 = In progress (accepted/shipping started but not closed)
		$sql .= " AND c.fk_statut IN (1, 2)";
		$sql .= " AND c.entity IN (" . getEntity('commande') . ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			// Check isset, as SUM() returns NULL if no rows match
			if ($obj && isset($obj->pending_qty)) {
				$qty = (float) $obj->pending_qty;
			}
			$this->db->free($resql);
		} else {
			dol_syslog(__METHOD__ . " Database error: " . $this->db->lasterror(), LOG_ERR);
		}

		return $qty;
	}

	/**
	 * Get pending supplier orders quantity for a product.
	 * Sums quantity from Supplier Orders with status Validated(1), Approved(2), Ordered(3), or Partially Received(4).
	 *
	 * @param   int   $product_id   Product ID
	 * @return  float               Pending quantity
	 */
	private function getPendingSupplierOrdersQty(int $product_id): float
	{
		global $conf;

		$qty = 0.0;

		// Check if Supplier Order module is enabled.
		if (empty($conf->fournisseur->enabled) && empty($conf->supplier_order->enabled)) {
			return $qty;
		}

		$sql = "SELECT SUM(cfd.qty) as pending_qty";
		$sql .= " FROM " . MAIN_DB_PREFIX . "commande_fournisseurdet as cfd";
		$sql .= " JOIN " . MAIN_DB_PREFIX . "commande_fournisseur as cf ON cfd.fk_commande = cf.rowid";
		$sql .= " WHERE cfd.fk_product = " . ((int) $product_id);
		// Statuses: 1=Validated, 2=Approved, 3=Ordered, 4=Partially Received
		$sql .= " AND cf.fk_statut IN (1, 2, 3, 4)";
		$sql .= " AND cf.entity IN (" . getEntity('supplier_order') . ")";

		$resql = $this->db->query($sql);
		if ($resql) {
			$obj = $this->db->fetch_object($resql);
			// Handle NULL result from SUM() if no rows exist
			if ($obj && isset($obj->pending_qty)) {
				$qty = (float) $obj->pending_qty;
			}
			$this->db->free($resql);
		} else {
			dol_syslog(__METHOD__ . " Database error: " . $this->db->lasterror(), LOG_ERR);
		}

		return $qty;
	}

	/**
	 * Generate replenishment recommendation.
	 * Logic prioritizes Critical (Negative) -> High (Below Alert) -> Low (Below Desired).
	 *
	 * @param   float   $virtual_stock    Current virtual stock (Physical + Incoming - Outgoing).
	 * @param   float   $min_stock_alert  Minimum stock alert level (Seuil alerte).
	 * @param   float   $desired_stock    Desired stock level (Stock désiré).
	 * @param   float   $dailyBurnRate    Estimated daily consumption rate.
	 *
	 * @return  array{action: string, urgency: string, suggested_qty: int, reason: string}
	 */
	private function generateReplenishmentRecommendation(float $virtual_stock, float $min_stock_alert, float $desired_stock, float $dailyBurnRate): array
	{
		$replenish = false;
		$suggestedQty = 0.0;
		$urgency = "none";
		$reasonParts = [];

		// Critical: Negative Stock
		if ($virtual_stock < 0) {
			$replenish = true;
			$urgency = "critical";
			$reasonParts[] = "Virtual stock ({$virtual_stock}) is negative - immediate action required.";

			if ($desired_stock > 0) {
				$suggestedQty = $desired_stock - $virtual_stock;
			} elseif ($min_stock_alert > 0) {
				// If no desired stock, aim for 150% of alert level to create a buffer
				$suggestedQty = ($min_stock_alert * 1.5) - $virtual_stock;
			} else {
				// Fallback: Cover deficit + (30 days of burn rate OR 10 units)
				$suggestedQty = abs($virtual_stock) + max(10, $dailyBurnRate * 30);
			}
		} elseif ($min_stock_alert > 0 && $virtual_stock < $min_stock_alert) {
			// High Urgency: Below Alert Threshold

			$replenish = true;
			$urgency = "high";
			$reasonParts[] = "Virtual stock ({$virtual_stock}) is below the alert threshold ({$min_stock_alert}).";

			if ($desired_stock > 0) {
				$suggestedQty = $desired_stock - $virtual_stock;
				$reasonParts[] = "Aiming to reach desired stock level ({$desired_stock}).";
			} elseif ($dailyBurnRate > 0) {
				// Aim for higher of: 150% alert level OR 30 days stock
				$target = max($min_stock_alert * 1.5, $dailyBurnRate * 30);
				$suggestedQty = $target - $virtual_stock;
				$reasonParts[] = "Suggesting 30 days of supply based on burn rate.";
			} else {
				$suggestedQty = ($min_stock_alert * 1.5) - $virtual_stock;
			}
		} elseif ($desired_stock > 0 && $virtual_stock < $desired_stock) {
			// Low Urgency: Below Desired Level
			$replenish = true;
			$urgency = "low";
			$reasonParts[] = "Virtual stock ({$virtual_stock}) is below the desired stock level ({$desired_stock}).";
			$suggestedQty = $desired_stock - $virtual_stock;
		} else {
			// Adequate Stock
			$reasonParts[] = "Stock levels are adequate.";
			if ($min_stock_alert > 0) {
				$reasonParts[] = "Virtual stock ({$virtual_stock}) is above alert threshold ({$min_stock_alert}).";
			}
			if ($desired_stock > 0 && $virtual_stock >= $desired_stock) {
				$reasonParts[] = "At or above desired stock level ({$desired_stock}).";
			}
		}

		return [
			"action"        => $replenish ? "REORDER" : "OK",
			"urgency"       => $urgency,
			"suggested_qty" => max(0, (int) ceil($suggestedQty)),
			"reason"        => implode(" ", $reasonParts)
		];
	}

	/**
	 * Performs an inventory analysis for a specific product.
	 * Calculates burn rate based on last 90 days of sales (Invoices) and predicts stockout dates.
	 *
	 * @param   array{product_id?: int|string, product_name?: string, type?: string} $args Arguments array.
	 * @return  array<string, mixed>    Analysis results or an error array.
	 *                                  Returns ['error' => string] on failure.
	 *                                  Success shape:
	 *                                  {
	 *                                    id: int, ref: string, label: string, type: string,
	 *                                    physical_stock: float, virtual_stock: float,
	 *                                    min_stock_alert: float, desired_stock: float,
	 *                                    pending_customer_orders_qty: float, pending_supplier_orders_qty: float,
	 *                                    analysis: array{sales_last_90_days: float, daily_burn_rate: float, estimated_days_until_stockout: ?int, predicted_stockout_date: ?string, note?: string},
	 *                                    recommendation: array
	 *                                  }
	 */
	private function analyze(array $args)
	{
		global $conf;

		// Ensure Product class is loaded
		dol_include_once('/product/class/product.class.php');

		// Check Permissions
		if (!$this->user->hasRight('produit', 'lire') || !$this->user->hasRight('service', 'lire')) {
			return ["error" => "Permission Denied: User does not have read access to products/services."];
		}

		$extracted = $this->extractIdentifier($args);
		if ($extracted['identifier'] === null) {
			return ["error" => "Missing 'product_id' or 'product_name' argument. One must be provided to identify the product."];
		}

		$prod = $this->findProduct($extracted['identifier'], $extracted['type']);
		if (is_array($prod) && isset($prod['error'])) {
			return $prod;
		}

		// Load specific stock data (Warehouse Open mode usually)
		$stockResult = $prod->load_stock('warehouseopen');
		if ($stockResult < 0) {
			dol_syslog(__METHOD__ . " - Warning: Could not load detailed stock data for product {$prod->id}", LOG_WARNING);
		}

		// Gather current metrics
		$pending_customer_orders_qty = $this->getPendingCustomerOrdersQty($prod->id);
		$pending_supplier_orders_qty = $this->getPendingSupplierOrdersQty($prod->id);

		$physical_stock  = (float) ($prod->stock_reel);
		$virtual_stock   = (float) ($prod->stock_theorique);
		$min_stock_alert = (float) ($prod->seuil_stock_alerte ?? 0);
		$desired_stock   = (float) ($prod->desiredstock ?? 0);

		// Calculate sales in last 90 days
		$qtySold90 = 0.0;

		if (!empty($conf->facture->enabled)) {
			$ninety_days_ago = dol_time_plus_duree(dol_now(), -90, 'd');
			$date90daysAgoFormatted = $this->db->idate($ninety_days_ago);

			$sql = "SELECT SUM(d.qty) as qty_sold";
			$sql .= " FROM " . MAIN_DB_PREFIX . "facturedet as d";
			$sql .= " JOIN " . MAIN_DB_PREFIX . "facture as f ON d.fk_facture = f.rowid";
			$sql .= " WHERE d.fk_product = " . ((int) $prod->id);
			$sql .= " AND f.datef >= '" . $this->db->escape($date90daysAgoFormatted) . "'";
			// Status: 1=Validated(Unpaid), 2=Paid. Exclude Draft(0) and Abandoned(3).
			$sql .= " AND f.fk_statut IN (1, 2)";
			$sql .= " AND f.entity IN (" . getEntity('invoice') . ")";

			dol_syslog(__METHOD__ . " sales SQL: " . $sql, LOG_DEBUG);
			$resql = $this->db->query($sql);

			if ($resql) {
				$obj = $this->db->fetch_object($resql);
				if ($obj && isset($obj->qty_sold)) {
					$qtySold90 = (float) $obj->qty_sold;
				}
				$this->db->free($resql);
			} else {
				dol_syslog(__METHOD__ . " - Error calculating sales: " . $this->db->lasterror(), LOG_ERR);
			}
		}

		// Calculate Burn Rate
		$dailyBurnRate = ($qtySold90 > 0) ? $qtySold90 / 90 : 0.0;

		// Base Data Structure
		$data = [
			"id"                          => (int) $prod->id,
			"ref"                         => $prod->ref,
			"label"                       => $prod->label,
			"type"                        => ($prod->type == 0) ? "product" : "service",
			"physical_stock"              => $physical_stock,
			"virtual_stock"               => $virtual_stock,
			"min_stock_alert"             => $min_stock_alert,
			"desired_stock"               => $desired_stock,
			"pending_customer_orders_qty" => $pending_customer_orders_qty,
			"pending_supplier_orders_qty" => $pending_supplier_orders_qty,
			"url"                         => dol_buildpath('/product/stock/product.php', 1) . "?id=" . $prod->id,
			"analysis"                    => [
				'sales_last_90_days' => $qtySold90,
				'daily_burn_rate'    => round($dailyBurnRate, 4)
			]
		];

		// Stockout Prediction Logic
		if ($dailyBurnRate > 0 && $virtual_stock > 0) {
			$daysRemaining = $virtual_stock / $dailyBurnRate;
			$data['analysis']['estimated_days_until_stockout'] = (int) floor($daysRemaining);

			$stockout_timestamp = dol_time_plus_duree(dol_now(), (int) floor($daysRemaining), 'd');
			$data['analysis']['predicted_stockout_date'] = dol_print_date($stockout_timestamp, 'dayrfc');
		} elseif ($virtual_stock <= 0) {
			// Already out of stock or negative
			$data['analysis']['estimated_days_until_stockout'] = 0;
			$data['analysis']['predicted_stockout_date'] = dol_print_date(dol_now(), 'dayrfc');
		} else {
			// No sales history
			$data['analysis']['estimated_days_until_stockout'] = null;
			$data['analysis']['predicted_stockout_date'] = null;
			$data['analysis']['note'] = "No recent sales or burn rate is zero - cannot predict stockout.";
		}

		// Generate Recommendation
		$data['recommendation'] = $this->generateReplenishmentRecommendation(
			$virtual_stock,
			$min_stock_alert,
			$desired_stock,
			$dailyBurnRate
		);

		return $data;
	}

	/**
	 * Retrieves a list of all defined supplier prices for a given product or service.
	 *
	 * @param array<string, mixed> $args Input parameters (product_id, product_name, type)
	 * @return array<string, mixed>|array<string, string>
	 *
	 */
	private function getSupplierPrices(array $args)
	{

		if (!$this->user->hasRight('produit', 'lire') || !$this->user->hasRight('service', 'lire')) {
			return ["error" => "Permission Denied: User does not have read access to products/services."];
		}

		if (!$this->user->hasRight('fournisseur', 'lire')) {
			return ["error" => "Permission Denied: User does not have read access to suppliers."];
		}

		$extracted = $this->extractIdentifier($args);
		if ($extracted['identifier'] === null) {
			return ["error" => "Missing 'product_id' or 'product_name' argument. One must be provided to identify the product."];
		}

		$prod = $this->findProduct($extracted['identifier'], $extracted['type']);
		if (is_array($prod) && isset($prod['error'])) {
			return $prod;
		}

		$supplierPrices = [];

		$sql = "SELECT pfp.rowid, pfp.fk_soc, pfp.ref_fourn, pfp.price, pfp.unitprice,";
		$sql .= " pfp.quantity, pfp.remise_percent, pfp.remise,";
		$sql .= " pfp.multicurrency_code, pfp.multicurrency_tx, pfp.multicurrency_price, pfp.multicurrency_unitprice,";
		$sql .= " pfp.datec, pfp.tms,";
		$sql .= " pfp.delivery_time_days, pfp.packaging,";
		$sql .= " s.nom as supplier_name, s.code_fournisseur";
		$sql .= " FROM " . MAIN_DB_PREFIX . "product_fournisseur_price as pfp";
		$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON pfp.fk_soc = s.rowid";
		$sql .= " WHERE pfp.fk_product = " . ((int) $prod->id);
		$sql .= " AND pfp.entity IN (" . getEntity('product') . ")";
		$sql .= " ORDER BY pfp.unitprice ASC";

		dol_syslog("ToolProducts::getSupplierPrices SQL: " . $sql, LOG_DEBUG);
		$resql = $this->db->query($sql);

		if (!$resql) {
			return ["error" => "Database error fetching supplier prices: " . $this->db->lasterror()];
		}

		while ($obj = $this->db->fetch_object($resql)) {
			$supplierPrices[] = [
				"id" => (int) $obj->rowid,
				"supplier_id" => (int) $obj->fk_soc,
				"supplier_name" => $obj->supplier_name ?? 'Unknown',
				"supplier_code" => $obj->code_fournisseur ?? null,
				"supplier_ref" => $obj->ref_fourn ?? null,
				"price_ht" => (float) ($obj->price ?? 0),
				"unit_price_ht" => (float) ($obj->unitprice ?? 0),
				"quantity_min" => (int) ($obj->quantity ?? 1),
				"discount_percent" => (float) ($obj->remise_percent ?? 0),
				"currency_code" => $obj->multicurrency_code ?? null,
				"currency_rate" => (float) ($obj->multicurrency_tx ?? 1),
				"multicurrency_price" => (float) ($obj->multicurrency_price ?? 0),
				"multicurrency_unit_price" => (float) ($obj->multicurrency_unitprice ?? 0),
				"delivery_time_days" => $obj->delivery_time_days ? (int) $obj->delivery_time_days : null,
				"packaging" => (float) ($obj->packaging ?? 0),
				"date_created" => $obj->datec ? dol_print_date($this->db->jdate($obj->datec), 'dayrfc') : null,
				"date_modified" => $obj->tms ? dol_print_date($this->db->jdate($obj->tms), 'dayrfc') : null,
				"url" => dol_buildpath('/fourn/card.php', 1) . "?id=" . $obj->fk_soc
			];
		}
		$this->db->free($resql);

		return [
			"product_id" => (int) $prod->id,
			"product_ref" => $prod->ref,
			"product_label" => $prod->label,
			"supplier_prices_count" => count($supplierPrices),
			"supplier_prices" => $supplierPrices
		];
	}
}
