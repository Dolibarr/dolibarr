<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
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
 * \file htdocs/ai/tools/reports.php
 * \ingroup ai
 * \brief MCP Server tool for reports.
 *
 * This tool provides functionalities to generate various reports related to
 * thirdparty transactions, sales, purchases, inventory, and other essential
 * ERP/CRM reports.
 */

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

/**
 * Class ToolReports
 *
 * Provides various tools related to Dolibarr reports.
 */
class ToolReports extends McpTool
{

	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB		$db			Database handler
	 */
	public function __construct(DoliDB  $db)
	{
		$this->db = $db;
	}

	/**
	 * Returns an array of tool definitions.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return [
			[
				"name" => "get_thirdparty_transactions",
				"description" => "Generate a list of raw transactions (Invoices, Orders) for a specific thirdparty.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"thirdparty_id" => ["type" => "integer", "description" => "The unique ID of the thirdparty."],
						"thirdparty_name" => ["type" => "string", "description" => "The name of the thirdparty."],
						"date_start" => ["type" => "string", "description" => "Start date (YYYY-MM-DD)."],
						"date_end" => ["type" => "string", "description" => "End date (YYYY-MM-DD)."],
						"transaction_type" => [
							"type" => "string",
							"enum" => ["all", "invoices", "orders", "proposals"],
							"description" => "Filter by type.",
							"default" => "all"
						]
					],
					"oneOf" => [
						["required" => ["thirdparty_id"]],
						["required" => ["thirdparty_name"]]
					]
				]
			],
			[
				"name" => "get_sales_report",
				"description" => "Generate a sales/revenue report. If a Thirdparty is provided, it returns a detailed breakdown for that customer. Otherwise, it returns a global summary.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"thirdparty_id" => [
							"type" => "integer",
							"description" => "Optional: The ID of the customer."
						],
						"date_start" => ["type" => "string", "description" => "Start date (YYYY-MM-DD)."],
						"date_end" => ["type" => "string", "description" => "End date (YYYY-MM-DD)."],
						"group_by" => [
							"type" => "string",
							"enum" => ["thirdparty", "product", "month"],
							"description" => "Only used if no Thirdparty is specified. Groups global results.",
							"default" => "thirdparty"
						]
					],
					"required" => ["date_start", "date_end"]
				]
			],
			[
				"name" => "get_purchase_report",
				"description" => "Generate a purchase/expense report. If a Supplier is provided, it returns a detailed breakdown. Otherwise, it returns a global summary.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"thirdparty_id" => [
							"type" => "integer",
							"description" => "Optional: The ID of the supplier."
						],
						"date_start" => ["type" => "string", "description" => "Start date (YYYY-MM-DD)."],
						"date_end" => ["type" => "string", "description" => "End date (YYYY-MM-DD)."],
						"group_by" => [
							"type" => "string",
							"enum" => ["supplier", "product", "month"],
							"description" => "Only used if no Supplier is specified. Groups global results.",
							"default" => "supplier"
						]
					],
					"required" => ["date_start", "date_end"]
				]
			],
			[
				"name" => "get_inventory_report",
				"description" => "Generate an inventory report showing current stock levels and valuation.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"category_id" => ["type" => "integer", "description" => "Filter by category ID."],
						"warehouse_id" => ["type" => "integer", "description" => "Filter by warehouse ID."],
						"include_zero_stock" => ["type" => "boolean", "default" => false]
					]
				]
			],
			[
				"name" => "get_financial_report",
				"description" => "Generate a summary financial report (Income vs Expense) for a period.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"date_start" => ["type" => "string", "description" => "Start date (YYYY-MM-DD)."],
						"date_end" => ["type" => "string", "description" => "End date (YYYY-MM-DD)."]
					],
					"required" => ["date_start", "date_end"]
				]
			],
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
		return ['reporting', 'commercial', 'billing', 'stock'];
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
			case 'get_thirdparty_transactions':
				return $this->getThirdpartyTransactions($args);
			case 'get_sales_report':
				return $this->getSalesReport($args);
			case 'get_purchase_report':
				return $this->getPurchaseReport($args);
			case 'get_inventory_report':
				return $this->getInventoryReport($args);
			case 'get_financial_report':
				return $this->getFinancialReport($args);
			default:
				return ["error" => "Tool function '$name' not found."];
		}
	}

	/**
	 * Resolves a Thirdparty ID from either an ID or a name.
	 *
	 * If `thirdparty_id` is provided, it is returned directly.
	 * Otherwise, if `thirdparty_name` is provided, the function searches
	 * the Dolibarr societe table using a LIKE match and returns the first match.
	 *
	 * @param array<string, mixed> $args Input parameters (thirdparty_id, thirdparty_name)
	 *
	 * @return int|null Thirdparty ID if found, otherwise null.
	 */
	private function resolveThirdparty($args)
	{

		if (!empty($args['thirdparty_id'])) {
			return (int) $args['thirdparty_id'];
		}

		if (!empty($args['thirdparty_name'])) {
			$name = $this->db->escape($args['thirdparty_name']);

			$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe
			WHERE nom LIKE '%" . $name . "%'
			AND entity IN (" . getEntity('societe') . ")
			LIMIT 1";

			$resql = $this->db->query($sql);
			if ($resql && $obj = $this->db->fetch_object($resql)) {
				return $obj->rowid;
			}
		}

		return null;
	}

	/**
	 * Generate a sales/revenue report.
	 *
	 * @param array<string, mixed> $args Input arguments (date_start, date_end, limit, etc.)
	 * @return array<int, array<string, string>> List of sales with localized keys and values.
	 */
	private function getSalesReport(array $args): array
	{
		global $langs;

		$langs->loadLangs(array("main", "bills", "companies", "products"));

		$limit     = isset($args['limit']) ? (int) $args['limit'] : 50;
		$dateStart = dol_stringtotime($args['date_start']);
		$dateEnd   = dol_stringtotime($args['date_end']);
		$socid     = $this->resolveThirdparty($args);
		$groupBy   = isset($args['group_by']) ? (string) $args['group_by'] : 'thirdparty';

		$list      = [];
		$totalSum  = 0.0;
		// Status Filter: Valid (1) and Paid (2). Exclude Draft (0) and Abandoned (3).
		$dateRange = " AND f.datef >= '" . $this->db->idate($dateStart)
			. "' AND f.datef <= '" . $this->db->idate($dateEnd)
			. "' AND f.fk_statut IN (1, 2)";

		// CASE 1 -- Detailed list for a specific thirdparty.
		if ($socid) {
			$sql = "SELECT f.rowid, f.ref, f.total_ttc, f.fk_statut, f.paye, f.datef, s.nom FROM "
				. MAIN_DB_PREFIX . "facture as f LEFT JOIN "
				. MAIN_DB_PREFIX . "societe as s ON f.fk_soc = s.rowid WHERE f.entity IN ("
				. getEntity('facture') . ")"
				. $dateRange
				. " AND f.fk_soc = " . (int) $socid
				. " ORDER BY f.datef DESC LIMIT " . ((int) $limit);

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($r = $this->db->fetch_object($resql)) {
					$totalSum += (float) $r->total_ttc;

					$statusLabel = $langs->transnoentitiesnoconv("Unknown");
					if ($r->fk_statut == 1 && $r->paye == 0) {
						$statusLabel = $langs->transnoentitiesnoconv("BillStatusNotPaid");
					} elseif ($r->fk_statut == 1 && $r->paye == 1) {
						$statusLabel = $langs->transnoentitiesnoconv("BillStatusStarted");
					} elseif ($r->fk_statut == 2) {
						$statusLabel = $langs->transnoentitiesnoconv("BillStatusPaid");
					}

					$url     = DOL_URL_ROOT . "/compta/facture/card.php?id=" . $r->rowid;
					$refHtml = '<a href="' . $url . '">' . $r->ref . '</a>';

					$list[] = [
						$langs->transnoentitiesnoconv("Ref")      => $refHtml,
						$langs->transnoentitiesnoconv("Date")     => dol_print_date($this->db->jdate($r->datef), 'day'),
						$langs->transnoentitiesnoconv("Customer") => $r->nom,
						$langs->transnoentitiesnoconv("Amount")   => price($r->total_ttc),
						$langs->transnoentitiesnoconv("Status")   => $statusLabel
					];
				}
				$this->db->free($resql);
			}
		} else {
			// CASE 2 -- Global grouped report.
			// Mirrors the pattern already used by getPurchaseReport(); previous implementation
			// of getSalesReport() ignored $groupBy entirely and always returned a flat list.
			$sanitizedSqlGroup = '';
			$colName           = '';
			$sqlJoin           = " LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON f.fk_soc = s.rowid";

			if ($groupBy === 'month') {
				$sanitizedSqlGroup = "DATE_FORMAT(f.datef, '%Y-%m')";
				$colName           = $langs->transnoentitiesnoconv("Month");
			} elseif ($groupBy === 'product') {
				// Aggregate on product line items. Lines without product_id fall back to their description.
				$sanitizedSqlGroup = "COALESCE(p.ref, fd.description, '?')";
				$colName           = $langs->transnoentitiesnoconv("Product");
				$sqlJoin .= " INNER JOIN " . MAIN_DB_PREFIX . "facturedet as fd ON fd.fk_facture = f.rowid LEFT JOIN "
					. MAIN_DB_PREFIX . "product as p ON fd.fk_product = p.rowid";
			} else {
				// Default: group by customer
				$sanitizedSqlGroup = "s.nom";
				$colName           = $langs->transnoentitiesnoconv("Customer");
			}

			// For product grouping we sum line totals (more accurate per-product);
			// otherwise we sum the invoice total_ttc.
			$amountExpr = ($groupBy === 'product') ? "SUM(fd.total_ttc)" : "SUM(f.total_ttc)";
			$countExpr  = ($groupBy === 'product') ? "COUNT(DISTINCT f.rowid)" : "COUNT(f.rowid)";

			$sql = "SELECT " . $sanitizedSqlGroup . " as group_key, "
				. $amountExpr . " as total_amount, "
				. $countExpr . " as count_inv FROM "
				. MAIN_DB_PREFIX . "facture as f"
				. $sqlJoin
				. " WHERE f.entity IN (" . getEntity('facture') . ")"
				. $dateRange
				. " GROUP BY group_key ORDER BY total_amount DESC LIMIT "
				. ((int) max(1, $limit));

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($r = $this->db->fetch_object($resql)) {
					$totalSum += (float) $r->total_amount;
					$list[] = [
						$colName                                  => $r->group_key ? $r->group_key : $langs->transnoentitiesnoconv('Unknown'),
						$langs->transnoentitiesnoconv("Number")   => (int) $r->count_inv,
						$langs->transnoentitiesnoconv("Amount")   => price($r->total_amount)
					];
				}
				$this->db->free($resql);
			}
		}

		if (empty($list)) {
			return [[$langs->transnoentitiesnoconv("Info") => $langs->transnoentitiesnoconv("NoRecordFound")]];
		}

		// Append Total Row (shape depends on detailed-vs-grouped path)
		if ($socid) {
			$list[] = [
				$langs->transnoentitiesnoconv("Ref")      => $langs->transnoentitiesnoconv("Total"),
				$langs->transnoentitiesnoconv("Date")     => "",
				$langs->transnoentitiesnoconv("Customer") => "",
				$langs->transnoentitiesnoconv("Amount")   => price($totalSum),
				$langs->transnoentitiesnoconv("Status")   => ""
			];
		} else {
			$list[] = [
				$langs->transnoentitiesnoconv("Total")  => $langs->transnoentitiesnoconv("Total"),
				$langs->transnoentitiesnoconv("Amount") => price($totalSum)
			];
		}

		return $list;
	}

	/**
	 * Generate a list of raw transactions (Invoices, Orders, Proposals).
	 *
	 * @param array<string, mixed> $args Input arguments.
	 * @return array<int, array<string, string>> Combined list of transactions.
	 */
	private function getThirdpartyTransactions(array $args): array
	{
		global $langs;

		$langs->loadLangs(array("main", "bills", "orders", "propal"));

		$dateStart = dol_stringtotime($args['date_start']);
		$dateEnd = dol_stringtotime($args['date_end']);
		$type = isset($args['transaction_type']) ? (string) $args['transaction_type'] : 'all';

		$socid = $this->resolveThirdparty($args);
		if (!$socid) {
			$langs->load("errors");
			return [[$langs->transnoentitiesnoconv("Error") => $langs->transnoentitiesnoconv("ErrorThirdPartyNotFound")]];
		}

		$queries = [];

		// Invoices
		if ($type == 'all' || $type == 'invoices') {
			$queries[] = "SELECT 'Invoice' as source_type, rowid, ref, total_ttc as amount, datef as date_entry, fk_statut
						  FROM " . MAIN_DB_PREFIX . "facture
						  WHERE fk_soc = " . (int) $socid . " AND entity IN (" . getEntity('facture') . ")
						  AND fk_statut IN (1, 2)";
		}

		// Orders
		if ($type == 'all' || $type == 'orders') {
			$queries[] = "SELECT 'Order' as source_type, rowid, ref, total_ttc as amount, date_commande as date_entry, fk_statut
						  FROM " . MAIN_DB_PREFIX . "commande
						  WHERE fk_soc = " . (int) $socid . " AND entity IN (" . getEntity('commande') . ")
						  AND fk_statut > 0";
		}

		// Proposals
		if ($type == 'all' || $type == 'proposals') {
			$queries[] = "SELECT 'Proposal' as source_type, rowid, ref, total_ttc as amount, datep as date_entry, fk_statut
						  FROM " . MAIN_DB_PREFIX . "propal
						  WHERE fk_soc = " . (int) $socid . " AND entity IN (" . getEntity('propal') . ")
						  AND fk_statut > 0";
		}

		if (empty($queries)) {
			return [[$langs->transnoentitiesnoconv("Error") => "Invalid transaction type"]];
		}

		$sql = "SELECT * FROM (";
		$sql .= implode(" UNION ", $queries);
		$sql .= ") as combined_transactions ";
		$whereParts = [];
		if ($dateStart > 0) {
			$whereParts[] = "date_entry >= '" . $this->db->idate($dateStart) . "'";
		}
		if ($dateEnd > 0) {
			$whereParts[] = "date_entry <= '" . $this->db->idate($dateEnd) . "'";
		}

		if (!empty($whereParts)) {
			$sql .= " WHERE " . implode(" AND ", $whereParts);
		}

		$sql .= " ORDER BY date_entry DESC";

		$resql = $this->db->query($sql);
		$list = [];
		$totalAmt = 0.0;

		if ($resql) {
			while ($r = $this->db->fetch_object($resql)) {
				$totalAmt += (float) $r->amount;

				$statusTxt = "";
				$urlPath = "";

				if ($r->source_type === 'Invoice') {
					$urlPath = "/compta/facture/card.php?id=" . $r->rowid;
					if ($r->fk_statut == 2) {
						$statusTxt = $langs->transnoentitiesnoconv("BillStatusPaid");
					} elseif ($r->fk_statut == 1) {
						$statusTxt = $langs->transnoentitiesnoconv("BillStatusNotPaid");
					}
				} elseif ($r->source_type === 'Order') {
					$urlPath = "/commande/card.php?id=" . $r->rowid;
					if ($r->fk_statut == 1) {
						$statusTxt = $langs->transnoentitiesnoconv("StatusOrderValidated");
					} elseif ($r->fk_statut == 2) {
						$statusTxt = $langs->transnoentitiesnoconv("StatusOrderOnProcess");
					} elseif ($r->fk_statut == 3) {
						$statusTxt = $langs->transnoentitiesnoconv("StatusOrderDelivered");
					}
				} elseif ($r->source_type === 'Proposal') {
					$urlPath = "/comm/propal/card.php?id=" . $r->rowid;
					if ($r->fk_statut == 1) {
						$statusTxt = $langs->transnoentitiesnoconv("PropalStatusValidated");
					} elseif ($r->fk_statut == 2) {
						$statusTxt = $langs->transnoentitiesnoconv("PropalStatusSigned");
					} elseif ($r->fk_statut == 3) {
						$statusTxt = $langs->transnoentitiesnoconv("PropalStatusNotSigned");
					} elseif ($r->fk_statut == 4) {
						$statusTxt = $langs->transnoentitiesnoconv("PropalStatusBilled");
					}
				}

				$fullUrl = $urlPath ? DOL_URL_ROOT . $urlPath : "";
				$refHtml = $fullUrl ? '<a href="' . $fullUrl . '">' . $r->ref . '</a>' : $r->ref;

				$list[] = [
					$langs->transnoentitiesnoconv("Type") => $langs->transnoentitiesnoconv($r->source_type),
					$langs->transnoentitiesnoconv("Ref") => $refHtml,
					$langs->transnoentitiesnoconv("Date") => dol_print_date($this->db->jdate($r->date_entry), 'day'),
					$langs->transnoentitiesnoconv("Amount") => price($r->amount),
					$langs->transnoentitiesnoconv("Status") => $statusTxt
				];
			}
			$this->db->free($resql);
		}

		if (empty($list)) {
			return [[$langs->transnoentitiesnoconv("Info") => $langs->transnoentitiesnoconv("NoRecordFound")]];
		}

		// Summary
		$list[] = [
			$langs->transnoentitiesnoconv("Type") => $langs->transnoentitiesnoconv("Total"),
			$langs->transnoentitiesnoconv("Ref") => "",
			$langs->transnoentitiesnoconv("Date") => "",
			$langs->transnoentitiesnoconv("Amount") => price($totalAmt),
			$langs->transnoentitiesnoconv("Status") => ""
		];

		return $list;
	}

	/**
	 * Generate a purchase/expense report.
	 *
	 * @param array<string, mixed> $args Input arguments.
	 * @return array<int, array<string, string>> List of purchases or groups.
	 */
	private function getPurchaseReport(array $args): array
	{
		global $langs;

		$langs->loadLangs(array("main", "bills", "companies"));

		$socid = $this->resolveThirdparty($args);
		$groupBy = isset($args['group_by']) ? (string) $args['group_by'] : 'supplier';
		$dateStart = dol_stringtotime($args['date_start']);
		$dateEnd = dol_stringtotime($args['date_end']);
		$list = [];
		$totalSum = 0.0;

		// Detailed report for a specific Supplier
		if ($socid) {
			$sql = "SELECT f.rowid, f.ref, f.total_ttc, f.datef, s.nom
					FROM " . MAIN_DB_PREFIX . "facture_fourn as f
					LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON f.fk_soc = s.rowid
					WHERE f.entity IN (" . getEntity('facture_fourn') . ")
					AND f.fk_soc = " . (int) $socid . "
					AND f.datef >= '" . $this->db->idate($dateStart) . "'
					AND f.datef <= '" . $this->db->idate($dateEnd) . "'
					AND f.fk_statut > 0
					ORDER BY f.datef DESC";

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($r = $this->db->fetch_object($resql)) {
					$totalSum += (float) $r->total_ttc;

					$url = DOL_URL_ROOT . "/fourn/facture/card.php?id=" . $r->rowid;
					$refHtml = '<a href="' . $url . '">' . $r->ref . '</a>';

					$list[] = [
						$langs->transnoentitiesnoconv("Ref") => $refHtml,
						$langs->transnoentitiesnoconv("Date") => dol_print_date($this->db->jdate($r->datef), 'day'),
						$langs->transnoentitiesnoconv("Supplier") => $r->nom,
						$langs->transnoentitiesnoconv("Amount") => price($r->total_ttc)
					];
				}
				$this->db->free($resql);
			}
		} else {  // Global Grouped Report
			$sanitizedSqlGroup = "";
			$colName = "";

			if ($groupBy === 'month') {
				$sanitizedSqlGroup = "DATE_FORMAT(f.datef, '%Y-%m')";
				$colName = $langs->transnoentitiesnoconv("Month");
			} else {
				$sanitizedSqlGroup = "s.nom";
				$colName = $langs->transnoentitiesnoconv("Supplier");
			}

			$sql = "SELECT " . $sanitizedSqlGroup . " as group_key, SUM(f.total_ttc) as total_amount, COUNT(f.rowid) as count_inv
				FROM " . MAIN_DB_PREFIX . "facture_fourn as f
				LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON f.fk_soc = s.rowid
				WHERE f.entity IN (" . getEntity('facture_fourn') . ")
				AND f.datef >= '" . $this->db->idate($dateStart) . "'
				AND f.datef <= '" . $this->db->idate($dateEnd) . "'
				AND f.fk_statut > 0
				GROUP BY group_key
				ORDER BY total_amount DESC";

			$resql = $this->db->query($sql);
			if ($resql) {
				while ($r = $this->db->fetch_object($resql)) {
					$totalSum += (float) $r->total_amount;
					$list[] = [
						$colName => $r->group_key ? $r->group_key : $langs->transnoentitiesnoconv('Unknown'),
						$langs->transnoentitiesnoconv("Number") => $r->count_inv,
						$langs->transnoentitiesnoconv("Amount") => price($r->total_amount)
					];
				}
				$this->db->free($resql);
			}
		}

		if (empty($list)) {
			return [[$langs->transnoentitiesnoconv("Info") => $langs->transnoentitiesnoconv("NoRecordFound")]];
		}

		// Summary Row
		$summary = [
			$langs->transnoentitiesnoconv("Amount") => price($totalSum)
		];

		if ($socid) {
			$summary[$langs->transnoentitiesnoconv("Ref")] = $langs->transnoentitiesnoconv("Total");
			$summary[$langs->transnoentitiesnoconv("Date")] = "";
			$summary[$langs->transnoentitiesnoconv("Supplier")] = "";
		} else {
			$summary[($groupBy === 'month' ? $langs->transnoentitiesnoconv("Month") : $langs->transnoentitiesnoconv("Supplier"))] = $langs->transnoentitiesnoconv("Total");
			$summary[$langs->transnoentitiesnoconv("Number")] = "";
		}
		$list[] = $summary;

		return $list;
	}

	/**
	 * Generate an inventory report.
	 *
	 * @param array<string, mixed> $args Input arguments.
	 * @return array<int, array<string, string>> Stock list.
	 */
	private function getInventoryReport(array $args): array
	{
		global $langs;

		$langs->loadLangs(array("products", "stocks"));

		$catId = isset($args['category_id']) ? (int) $args['category_id'] : 0;
		$warehouseId = isset($args['warehouse_id']) ? (int) $args['warehouse_id'] : 0;
		$includeZero = isset($args['include_zero_stock']) ? (bool) $args['include_zero_stock'] : false;

		$sql = "SELECT p.rowid, p.ref, p.label, p.pmp, ";

		if ($warehouseId > 0) {
			$sql .= " ps.reel as stock_level ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "product as p ";
			$sql .= " LEFT JOIN " . MAIN_DB_PREFIX . "product_stock as ps ON p.rowid = ps.fk_product ";
			$sql .= " WHERE ps.fk_entrepot = " . (int) $warehouseId;
		} else {
			$sql .= " p.stock as stock_level ";
			$sql .= " FROM " . MAIN_DB_PREFIX . "product as p ";
			$sql .= " WHERE 1=1 ";
		}

		$sql .= " AND p.entity IN (" . getEntity('product') . ")";

		if ($catId > 0) {
			$sql .= " AND p.rowid IN (SELECT fk_product FROM " . MAIN_DB_PREFIX . "categorie_product WHERE fk_categorie = " . (int) $catId . ")";
		}

		if (!$includeZero) {
			if ($warehouseId > 0) {
				$sql .= " AND ps.reel > 0";
			} else {
				$sql .= " AND p.stock > 0";
			}
		}

		$sql .= " ORDER BY p.ref ASC LIMIT 200";

		$resql = $this->db->query($sql);
		$list = [];
		$totalValuation = 0.0;
		$totalItems = 0;

		if ($resql) {
			while ($r = $this->db->fetch_object($resql)) {
				$stockVal = $r->stock_level * $r->pmp;
				$totalValuation += $stockVal;
				$totalItems += (int) $r->stock_level;

				$url = DOL_URL_ROOT . "/product/card.php?id=" . $r->rowid;
				$refHtml = '<a href="' . $url . '">' . $r->ref . '</a>';

				$list[] = [
					$langs->transnoentitiesnoconv("Ref") => $refHtml,
					$langs->transnoentitiesnoconv("Label") => $r->label,
					$langs->transnoentitiesnoconv("Stock") => $r->stock_level,
					$langs->transnoentitiesnoconv("PMPValue") => price($r->pmp),
					$langs->transnoentitiesnoconv("TotalValue") => price($stockVal)
				];
			}
			$this->db->free($resql);
		}

		if (empty($list)) {
			return [[$langs->transnoentitiesnoconv("Info") => $langs->transnoentitiesnoconv("NoRecordFound")]];
		}

		$list[] = [
			$langs->transnoentitiesnoconv("Ref") => $langs->transnoentitiesnoconv("Total"),
			$langs->transnoentitiesnoconv("Label") => "",
			$langs->transnoentitiesnoconv("Stock") => $totalItems,
			$langs->transnoentitiesnoconv("PMPValue") => "",
			$langs->transnoentitiesnoconv("TotalValue") => price($totalValuation)
		];

		return $list;
	}

	/**
	 * Generate a summary financial report (Income vs Expense).
	 *
	 * @param array<string, mixed> $args Input arguments.
	 * @return array<int, array<string, string>> Financial summary.
	 */
	private function getFinancialReport(array $args): array
	{
		global $langs;

		$langs->loadLangs(array("compta", "bills"));
		$dateStart = dol_stringtotime($args['date_start']);
		$dateEnd = dol_stringtotime($args['date_end']);

		// Income (Customer Invoices - Validated/Paid, no Drafts)
		$sqlIncome = "SELECT SUM(total_ttc) as total FROM " . MAIN_DB_PREFIX . "facture
					  WHERE entity IN (" . getEntity('facture') . ")
					  AND datef >= '" . $this->db->idate($dateStart) . "'
					  AND datef <= '" . $this->db->idate($dateEnd) . "'
					  AND fk_statut IN (1, 2)";

		$resIncome = $this->db->query($sqlIncome);
		$objIncome = $this->db->fetch_object($resIncome);
		$income = $objIncome && $objIncome->total ? (float) $objIncome->total : 0.0;

		// Expenses (Supplier Invoices - Validated, no Drafts)
		$sqlExpense = "SELECT SUM(total_ttc) as total FROM " . MAIN_DB_PREFIX . "facture_fourn
					   WHERE entity IN (" . getEntity('facture_fourn') . ")
					   AND datef >= '" . $this->db->idate($dateStart) . "'
					   AND datef <= '" . $this->db->idate($dateEnd) . "'
					   AND fk_statut > 0";

		$resExpense = $this->db->query($sqlExpense);
		$objExpense = $this->db->fetch_object($resExpense);
		$expense = $objExpense && $objExpense->total ? (float) $objExpense->total : 0.0;

		$net = $income - $expense;

		$list = [
			[
				$langs->transnoentitiesnoconv("Category") => $langs->transnoentitiesnoconv("Income"),
				$langs->transnoentitiesnoconv("Description") => $langs->transnoentitiesnoconv("BillsCustomers"),
				$langs->transnoentitiesnoconv("Amount") => price($income)
			],
			[
				$langs->transnoentitiesnoconv("Category") => $langs->transnoentitiesnoconv("Expenses"),
				$langs->transnoentitiesnoconv("Description") => $langs->transnoentitiesnoconv("BillsSuppliers"),
				$langs->transnoentitiesnoconv("Amount") => price($expense)
			],
			[
				$langs->transnoentitiesnoconv("Category") => $langs->transnoentitiesnoconv("Total"),
				$langs->transnoentitiesnoconv("Description") => $langs->transnoentitiesnoconv("Profit"),
				$langs->transnoentitiesnoconv("Amount") => price($net)
			]
		];

		return $list;
	}
}
