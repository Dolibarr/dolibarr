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
 * but WITHOUT ANY WARRANTY, without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 * \file htdocs/ai/tools/invoices.php
 * \ingroup ai
 * \brief MCP Server tool for Invoice management.
 */

require_once DOL_DOCUMENT_ROOT . '/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/paiement/class/paiement.class.php';
require_once DOL_DOCUMENT_ROOT . '/compta/bank/class/account.class.php';
require_once DOL_DOCUMENT_ROOT . '/core/lib/date.lib.php';

/**
 * Class ToolInvoices
 *
 * Provides various tools related to Dolibarr invoices.
 */
class ToolInvoices extends McpTool
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
	 * Returns an array of tool definitions, including name, description, and input schema.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return [
			[
				"name" => "search_invoice",
				"description" => "Search for invoices. By default, lists UNPAID invoices. Excludes drafts.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"customer" => [
							"type" => "string",
							"description" => "Optional: Customer name."
						],
						"status" => [
							"type" => "string",
							"enum" => ["unpaid", "paid", "draft", "all"],
							"description" => "Filter by status. Default is 'unpaid'. 'all' shows history but excludes drafts.",
							"default" => "unpaid"
						],
						"limit" => [
							"type" => "integer",
							"default" => 10
						]
					]
				]
			],
			[
				"name" => "get_invoice",
				"description" => "Get details of a specific invoice by ID or Reference.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"ref" => ["type" => "string", "description" => "Invoice Ref (e.g. FA2401-001)"],
						"id" => ["type" => "integer", "description" => "Invoice ID"]
					],
					"oneOf" => [
						["required" => ["ref"]],
						["required" => ["id"]]
					]
				]
			],
			[
				"name" => "validate_invoice",
				"description" => "Validate a draft invoice.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"invoice" => ["type" => "string", "description" => "Invoice ID or Ref."]
					],
					"required" => ["invoice"]
				]
			],
			[
				"name" => "pay_invoice",
				"description" => "Register a payment for an invoice.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"invoice" => ["type" => "string", "description" => "Invoice ID or Reference."],
						"amount" => ["type" => "number", "description" => "Amount to pay. Defaults to full remaining."],
						"payment_mode" => ["type" => "string", "description" => "Code (VIR, CB, LIQ)."],
						"bank_account" => ["type" => "string", "description" => "Bank Account Name/Ref."]
					],
					"required" => ["invoice"]
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
		return ['billing'];
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
			case 'search_invoice':
			case 'search_invoices':
				return $this->searchInvoices($args);

			case 'get_invoice':
				return $this->getInvoice($args);

			case 'validate_invoice':
				return $this->validateInvoice($args);

			case 'pay_invoice':
				return $this->payInvoice($args);

			default:
				return ["error" => "Tool function '$name' not found."];
		}
	}

	/**
	 * Search invoices based on filters.
	 *
	 * @param array<string, mixed> $args Input filters (limit, status, customer)
	 *
	 * @return array<int, array<string, mixed>>|array<string, string>
	 */
	private function searchInvoices($args)
	{
		$limit = isset($args['limit']) ? (int) $args['limit'] : 10;
		$status = isset($args['status']) ? $args['status'] : 'unpaid';

		// Safety fallback
		if ($limit <= 0) {
			$limit = 5;
		}
		if ($limit > 1000) {
			dol_syslog("Search DB Error: Too many record requested", LOG_ERR);
			return ["error" => "DB Error"];
		}

		$sql = "SELECT f.rowid, f.ref, f.total_ttc, f.fk_statut, f.paye, f.datef, s.nom
				FROM " . MAIN_DB_PREFIX . "facture as f
				LEFT JOIN " . MAIN_DB_PREFIX . "societe as s ON f.fk_soc = s.rowid
				WHERE f.entity IN (" . getEntity('facture') . ")";

		// Status filtering
		if ($status === 'draft') {
			// Explicitly asking for drafts
			$sql .= " AND f.fk_statut = 0";
		} elseif ($status === 'paid') {
			// Fully paid
			$sql .= " AND f.fk_statut = 2";
		} elseif ($status === 'all') {
			// Valid invoices (Unpaid + Paid). EXCLUDES Drafts (0) and Abandoned (3)
			$sql .= " AND f.fk_statut IN (1, 2)";
		} else {
			// Default: 'unpaid'
			// In Dolibarr: fk_statut=1 means Validated but not fully paid.
			$sql .= " AND f.fk_statut = 1 AND f.paye = 0";
		}

		// Customer Filter
		if (!empty($args['customer'])) {
			$cust = $this->findCustomer($args['customer']);
			if (!is_array($cust)) {
				$sql .= " AND f.fk_soc = " . ((int) $cust->id);
			} else {
				$sql .= " AND s.nom LIKE '%" . $this->db->escape($args['customer']) . "%'";
			}
		}

		$sql .= " ORDER BY f.datef DESC LIMIT " . ((int) $limit);

		$resql = $this->db->query($sql);
		$list = [];

		if ($resql) {
			while ($r = $this->db->fetch_object($resql)) {
				// Double check to ensure no PROV/Drafts slip through unless asked
				if ($status !== 'draft' && $r->fk_statut == 0) {
					continue;
				}

				$ref = ($r->fk_statut == 0 ? "(PROV" . $r->rowid . ")" : $r->ref);

				// Calculate Status Label
				$statusLabel = "Unknown";
				if ($r->fk_statut == 0) {
					$statusLabel = "Draft";
				} elseif ($r->fk_statut == 1) {
					$statusLabel = "Unpaid";
				} elseif ($r->fk_statut == 2) {
					$statusLabel = "Paid";
				} elseif ($r->fk_statut == 3) {
					$statusLabel = "Abandoned";
				}

				$list[] = [
					"ref" => $ref,
					"date" => dol_print_date($this->db->jdate($r->datef), 'day'),
					"customer" => $r->nom,
					"amount" => price($r->total_ttc),
					"status" => $statusLabel,
					"url" => DOL_URL_ROOT . "/compta/facture/card.php?id=" . $r->rowid
				];
			}
			$this->db->free($resql);
		}

		if (empty($list)) {
			return ["info" => "No " . $status . " invoices found matching your criteria."];
		}

		return $list;
	}

	/**
	 * Get full invoice details.
	 *
	 * @param array<string, mixed> $args Input parameters (ref or id)
	 *
	 * @return array<string, mixed>
	 */
	private function getInvoice($args)
	{
		$id = isset($args['ref']) ? $args['ref'] : (isset($args['id']) ? $args['id'] : null);

		$invoice = $this->findInvoice($id);
		if (is_array($invoice)) {
			return $invoice;
		}

		$invoice->fetch_thirdparty();
		$invoice->fetch_lines();

		$lines = [];
		foreach ($invoice->lines as $l) {
			$prodRef = !empty($l->product_ref) ? $l->product_ref : (!empty($l->product_label) ? $l->product_label : '');

			$lines[] = [
				"product" => $prodRef,
				"desc" => dol_html_entity_decode(strip_tags($l->desc), ENT_QUOTES),
				"qty" => (float) $l->qty,
				"price" => price($l->subprice),
				"total_line" => price($l->total_ht),
				"vat" => $l->tva_tx . "%"
			];
		}

		return [
			"id" => $invoice->id,
			"ref" => $invoice->ref,
			"date" => dol_print_date($invoice->date, 'day'),
			"status" => $invoice->getLibStatut(1),
			"customer" => $invoice->thirdparty->name,
			"total_ht" => price($invoice->total_ht),
			"total_ttc" => price($invoice->total_ttc),
			"lines" => $lines,
			"url" => DOL_URL_ROOT . "/compta/facture/card.php?id=" . $invoice->id
		];
	}

	/**
	 * Validate a draft invoice.
	 *
	 * @param array<string, mixed> $args  Input parameters (invoice ref or id)
	 *
	 * @return array<string, mixed>
	 */
	private function validateInvoice($args)
	{
		global $user;
		$invoice = $this->findInvoice($args['invoice']);
		if (is_array($invoice)) {
			return $invoice;
		}

		if ($invoice->statut != 0) {
			return ["error" => "Invoice is already validated."];
		}

		if ($invoice->validate($user) < 0) {
			$error = $invoice->error;
			if (!empty($invoice->errors)) {
				$error .= ' ' . implode(', ', $invoice->errors);
			}
			if (empty(trim($error))) {
				$error = 'Unknown error (validate returned < 0 with no message)';
			}
			return ["error" => "Validation failed: " . $error];
		}

		$invoice->fetch($invoice->id);
		return [
			"success" => true,
			"new_ref" => $invoice->ref,
			"status" => "Validated (Unpaid)",
			"url" => DOL_URL_ROOT . "/compta/facture/card.php?id=" . $invoice->id
		];
	}

	/**
	 * Register a payment on an invoice.
	 *
	 * @param array<string, mixed> $args Input parameters (invoice, amount, payment_mode, bank_account)
	 *
	 * @return array<string, mixed>
	 */
	private function payInvoice($args)
	{
		global $user;
		$invoice = $this->findInvoice($args['invoice']);
		if (is_array($invoice)) {
			return $invoice;
		}

		// Cannot pay drafts
		if ($invoice->statut == 0) {
			return ["error" => "Cannot pay a Draft invoice. Please validate it first."];
		}

		$bank = $this->findBankAccount(isset($args['bank_account']) ? $args['bank_account'] : '');
		if (!$bank) {
			return ["error" => "No active Bank account found to receive payment."];
		}

		$remaining = $invoice->total_ttc - $invoice->getSommePaiement();
		if ($remaining <= 0) {
			return ["error" => "Invoice is already fully paid."];
		}

		$amount = isset($args['amount']) ? (float) $args['amount'] : $remaining;
		if ($amount > $remaining) {
			$amount = $remaining;
		}

		$code = isset($args['payment_mode']) ? $args['payment_mode'] : 'VIR';
		$modeId = dol_getIdFromCode($this->db, $code, 'c_paiement', 'code', 'id');

		$this->db->begin();
		$payment = new Paiement($this->db);
		$payment->datepaye = dol_now();
		$payment->amounts = [$invoice->id => $amount];
		$payment->paiementid = $modeId;
		$payment->paiementcode = $code;

		$paymentId = $payment->create($user, 1);
		if ($paymentId < 0) {
			$this->db->rollback();
			return ["error" => "Payment creation failed: " . implode(', ', $payment->errors)];
		}
		$payment->fetch($paymentId);
		if ($payment->addPaymentToBank($user, 'payment', '(Payment via AI)', $bank->rowid, '', '') < 0) {
			$this->db->rollback();
			return ["error" => "Failed to add payment to bank ledger."];
		}

		$this->db->commit();

		return [
			"success" => true,
			"paid_amount" => price($amount),
			"remaining_due" => price($remaining - $amount),
			"status" => ($remaining - $amount <= 0) ? "Fully Paid" : "Partially Paid",
			"payment_url" => DOL_URL_ROOT . "/compta/paiement/card.php?id=" . $paymentId
		];
	}

	/**
	 * Find a customer by identifier.
	 *
	 * @param string $identifier ID, ref, code or name
	 * @return Societe|array<string, mixed>
	 */
	private function findCustomer($identifier)
	{
		global $conf;

		$customer = new Societe($this->db);
		$identifier = trim($identifier);

		if (preg_match('/^(?:socid|id)[:\s]+(\d+)$/i', $identifier, $m)) {
			$identifier = $m[1];
		} elseif (preg_match('/^(?:code|ref)[:\s]+(.+)$/i', $identifier, $m)) {
			$identifier = $m[1];
		}

		if (is_numeric($identifier)) {
			if ($customer->fetch((int) $identifier) > 0) {
				return $customer;
			}
		}

		// Exact
		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "societe
			WHERE (nom = '" . $this->db->escape($identifier) . "'
			OR code_client = '" . $this->db->escape($identifier) . "')
			AND entity IN (" . getEntity('societe') . ")";

		$resql = $this->db->query($sql);

		if ($resql && $this->db->num_rows($resql) > 0) {
			$obj = $this->db->fetch_object($resql);
			$customer->fetch($obj->rowid);
			$this->db->free($resql);
			return $customer;
		}

		$sql = "SELECT rowid, nom FROM " . MAIN_DB_PREFIX . "societe
			WHERE (nom LIKE '%" . $this->db->escape($identifier) . "%'
			OR code_client LIKE '%" . $this->db->escape($identifier) . "%')
			AND entity IN (" . getEntity('societe') . ")
			LIMIT 5";

		$resql = $this->db->query($sql);

		if ($resql) {
			$num = $this->db->num_rows($resql);

			if ($num == 1) {
				$obj = $this->db->fetch_object($resql);
				$customer->fetch($obj->rowid);
				$this->db->free($resql);
				return $customer;
			} elseif ($num > 1) {
				$matches = [];
				while ($obj = $this->db->fetch_object($resql)) {
					$matches[] = $obj->nom;
				}
				$this->db->free($resql);
				return ["error" => "Multiple customers found.", "matches" => $matches];
			}
		}

		return ["error" => "Customer not found."];
	}

	/**
	 * Find an invoice by identifier.
	 *
	 * @param string|int $identifier Invoice ID or ref
	 * @return Facture|array<string, string>
	 */
	private function findInvoice($identifier)
	{
		$invoice = new Facture($this->db);
		$identifier = trim($identifier);

		if (preg_match('/^\(?prov[-_]?(\d+)\)?$/i', $identifier, $matches)) {
			if ($invoice->fetch((int) $matches[1]) > 0) {
				return $invoice;
			}
		}
		if (is_numeric($identifier)) {
			if ($invoice->fetch((int) $identifier) > 0) {
				return $invoice;
			}
		}
		if ($invoice->fetch(0, $identifier) > 0) {
			return $invoice;
		}

		return ["error" => "Invoice not found."];
	}

	/**
	 * Find a bank account.
	 *
	 * @param string|int $identifier Bank account id, ref or label
	 * @return object|null
	 */
	private function findBankAccount($identifier)
	{
		global $conf;

		$identifier = trim($identifier);
		$params = [];


		$sql = "SELECT rowid, label FROM " . MAIN_DB_PREFIX . "bank_account
			WHERE entity IN (" . getEntity('bank_account') . ") AND clos = 0";

		if (is_numeric($identifier)) {
			$sql .= " AND rowid = " . ((int) $identifier);
		} elseif (!empty($identifier)) {
			$sql .= " AND (ref = '" . $this->db->escape($identifier) . "'
						OR label LIKE '%" . $this->db->escape($identifier) . "%')";
		}

		$resql = $this->db->query($sql);

		if ($resql && $this->db->num_rows($resql) > 0) {
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			return $obj;
		}

		// fallback
		$sql = "SELECT rowid, label FROM " . MAIN_DB_PREFIX . "bank_account
			WHERE entity IN (" . getEntity('bank_account') . ") AND clos = 0
			LIMIT 1";

		$resql = $this->db->query($sql);

		if ($resql && $this->db->num_rows($resql) > 0) {
			$obj = $this->db->fetch_object($resql);
			$this->db->free($resql);
			return $obj;
		}

		return null;
	}
}
