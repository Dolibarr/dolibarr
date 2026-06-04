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
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */
/* htdocs/ai/tools/navigation.php */

require_once DOL_DOCUMENT_ROOT . '/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT . '/core/class/extrafields.class.php';

/**
 * \class ToolNavigation
 *
 * \brief AI tool for generating navigation URLs in Dolibarr
 */
class ToolNavigation extends McpTool
{
	/**
	 * Returns an array of tool definitions, including name, description, and input schema.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return [
			[
				"name" => "navigate_to_page",
				"description" => "Generates a valid Dolibarr URL. Handles generic names (e.g., 'invoice' maps to customer invoices) and directory structures automatically. Can filter lists by status.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"object_type" => [
							"type" => "string",
							"description" => "The object type. Examples: 'invoice', 'thirdparty', 'order', 'proposal', 'project', 'supplier_invoice'.",
						],
						"view" => [
							"type" => "string",
							"description" => "The type of view needed: 'list', 'card', 'create'.",
							"enum" => ["list", "card", "create"]
						],
						"id" => [
							"type" => "integer",
							"description" => "The ID of the record (optional)."
						],
						"ref" => [
							"type" => "string",
							"description" => "The Reference of the record (optional)."
						],
						"status_filter" => [
							"type" => "string",
							"description" => "A human-readable status to filter the list. Only applies to 'list' view. Examples: 'draft', 'open', 'paid', 'shipped', 'closed', 'canceled'."
						],
						"params" => [
							"type" => "object",
							"description" => "Additional URL parameters (e.g. {'search_thirdparty': 'MyCompany'}). These will be combined with the status filter."
						]
					],
					"required" => ["object_type", "view"]
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
		return ['global'];
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
		global $langs, $db;

		// Load translation files
		$langs->load("companies");
		$langs->load("bills");
		$langs->load("orders");
		$langs->load("propal");
		$langs->load("projects");
		$langs->load("sendings");

		if ($name !== 'navigate_to_page') {
			return null;
		}

		if (empty($this->user->id)) {
			return ["error" => "Permission Denied: User not logged in."];
		}

		$rawType = $args['object_type'] ?? '';
		$view = $args['view'] ?? 'list';
		$id = (int) ($args['id'] ?? 0);
		$ref = $args['ref'] ?? '';
		$statusFilter = $args['status_filter'] ?? '';
		$params = $args['params'] ?? [];

		// Resolve Logical Object to Physical Path (with Aliases)
		$pathInfo = $this->resolvePath($rawType, $view);

		if (empty($pathInfo)) {
			return ["error" => "Unknown object type: '$rawType'. Try 'invoice', 'order', or 'thirdparty'."];
		}

		$relativePath = $pathInfo['path'];
		$elementType = $pathInfo['type'];

		// Check permissions
		if (!$this->checkPermissions($elementType, $view, $id)) {
			return ["error" => "Permission Denied: You don't have permission to access this resource."];
		}

		// Build Query Parameters
		$queryParams = [];

		// Handle Action/ID logic
		if ($id > 0) {
			$queryParams['id'] = $id;
		} elseif (!empty($ref)) {
			$queryParams['ref'] = $ref;
		}

		// Set action for create view
		if ($view === 'create') {
			$queryParams['action'] = 'create';
		}

		// Handle Status Filtering
		if ($view === 'list' && !empty($statusFilter)) {
			$statusParam = $this->mapStatusToFilter($elementType, $statusFilter);
			if ($statusParam) {
				$queryParams = array_merge($queryParams, $statusParam);
			} else {
				return ["error" => "Unknown status filter '$statusFilter' for object type '$rawType'."];
			}
		} elseif (!empty($statusFilter)) {
			return ["error" => "The 'status_filter' parameter can only be used with the 'list' view."];
		}

		// Merge extra params
		if (!empty($params) && is_array($params)) {
			$queryParams = array_merge($queryParams, $params);
		}

		// Generate Native URL
		$baseUrl = dol_buildpath($relativePath, 1);

		$finalUrl = $baseUrl;
		if (!empty($queryParams)) {
			$finalUrl .= '?' . http_build_query($queryParams);
		}

		return [
			"url" => $finalUrl,
			"description" => $this->generateDescription($elementType, $view, $id, $statusFilter),
			"meta" => [
				"resolved_type" => $elementType,
				"path" => $relativePath
			]
		];
	}

	/**
	 * Maps human-readable status terms to Dolibarr URL parameters for a given element type.
	 * This is the core logic for accurate list filtering.
	 *
	 * @param string $elementType The normalized element type (e.g., 'invoice_customer')
	 * @param string $statusFilter The human-readable status (e.g., 'open', 'paid')
	 * @return array<string, int|string>|null An associative array for the URL query string, or null if no match.
	 */
	private function mapStatusToFilter($elementType, $statusFilter)
	{
		$statusFilter = strtolower(trim($statusFilter));

		// Master map of element types to their status filters
		$statusMap = [
			'invoice_customer' => [
				'draft' => ['statut' => 0],
				'unpaid' => ['statut' => 1], // Validated but not paid
				'paid' => ['statut' => 2],
			],
			'invoice_supplier' => [
				'draft' => ['statut' => 0],
				'unpaid' => ['statut' => 1],
				'paid' => ['statut' => 2],
			],
			'order' => [
				'draft' => ['statut' => 0],
				'validated' => ['statut' => 1],
				'shipped' => ['statut' => 2], // Or partially shipped
				'closed' => ['statut' => 3],
				'canceled' => ['statut' => -1],
			],
			'order_supplier' => [
				'draft' => ['statut' => 0],
				'validated' => ['statut' => 1],
				'approved' => ['statut' => 2],
				'received' => ['statut' => 3], // Or partially received
				'canceled' => ['statut' => -1],
			],
			'proposal' => [
				'draft' => ['statut' => 0],
				'open' => ['statut' => 1],
				'signed' => ['statut' => 2],
				'billed' => ['statut' => 3],
				'refused' => ['statut' => 4],
				'canceled' => ['statut' => 5],
			],
			'project' => [
				'draft' => ['status' => 0],
				'open' => ['status' => 1],
				'closed' => ['status' => 2],
			],
			'expedition' => [ // Shipments
				'draft' => ['status' => 0],
				'validated' => ['status' => 1],
				'shipped' => ['status' => 2],
				'canceled' => ['status' => -1],
			],
			'contract' => [
				'draft' => ['statut' => 0],
				'active' => ['statut' => 1],
				'closed' => ['statut' => 2],
				'resiliated' => ['statut' => 3], // Resiliated
			],
			'fichinter' => [ // Interventions
				'draft' => ['statut' => 0],
				'validated' => ['statut' => 1],
				'billed' => ['statut' => 2],
				'closed' => ['statut' => 3],
			],
			// Add other object types as needed
		];

		return $statusMap[$elementType][$statusFilter] ?? null;
	}

	/**
	 * Maps user-friendly names to specific Dolibarr paths
	 *
	 * @param string $input User input object type
	 * @param string $view View type (list, card, create)
	 * @return array<string, mixed>|null Path information or null if not found
	 */
	private function resolvePath($input, $view)
	{
		$input = strtolower(trim($input));

		// Normalize Aliases (Make the tool robust to LLM guessing)
		$aliases = [
			// Invoices
			'invoice' => 'invoice_customer',
			'bill' => 'invoice_customer',
			'facture' => 'invoice_customer',
			'supplier_invoice' => 'invoice_supplier',
			'vendor_bill' => 'invoice_supplier',
			// Thirdparties
			'company' => 'thirdparty',
			'societe' => 'thirdparty',
			'customer' => 'thirdparty',
			'client' => 'thirdparty',
			'supplier' => 'thirdparty',
			'vendor' => 'thirdparty',
			// Commercial
			'propal' => 'proposal',
			'quote' => 'proposal',
			'command' => 'order',
			'customer_order' => 'order',
			'supplier_order' => 'order_supplier',
			// Products/Services
			'product' => 'product',
			'service' => 'product',
			// Projects
			'project' => 'project',
			'task' => 'project_task',
			// Shipping
			'shipment' => 'expedition',
			'shipping' => 'expedition',
			'delivery' => 'expedition',
			// Payments
			'payment' => 'payment',
			'payment_customer' => 'payment',
			'payment_supplier' => 'payment_supplier',
			// Banking
			'transaction' => 'bank',
			'account' => 'bank',
			'bank_account' => 'bank',
			// Events
			'event' => 'agenda',
			'agenda' => 'agenda',
			'appointment' => 'agenda',
			// Contracts
			'contract' => 'contract',
			// Interventions
			'intervention' => 'fichinter',
			// Members
			'member' => 'adherent',
			'membership' => 'adherent',
			// Categories
			'category' => 'categories',
		];

		$type = $aliases[$input] ?? $input;

		// Map Normalized Types to Physical Paths
		$map = [
			'thirdparty' => '/societe/',
			'contact' => '/contact/',
			'product' => '/product/',
			'project' => '/projet/',
			'project_task' => '/projet/tasks/',
			'invoice_customer' => '/compta/facture/',
			'invoice_supplier' => '/fourn/facture/',
			'order' => '/commande/',
			'order_supplier' => '/fourn/commande/',
			'proposal' => '/comm/propal/',
			'expedition' => '/expedition/',
			'payment' => '/compta/paiement.php', // Direct file, not directory
			'payment_supplier' => '/fourn/paiement.php', // Direct file, not directory
			'bank' => '/compta/bank/',
			'agenda' => '/comm/action/',
			'contract' => '/contrat/',
			'fichinter' => '/fichinter/',
			'adherent' => '/adherents/',
			'categories' => '/categories/',
		];

		if (!isset($map[$type])) {
			return null;
		}

		$dir = $map[$type];

		// Determine Script based on View
		// Handle special cases where the path is already a file
		if (strpos($dir, '.php') !== false) {
			return [
				'type' => $type,
				'path' => $dir
			];
		}

		$script = 'list.php'; // Default

		if ($view === 'card' || $view === 'create') {
			$script = 'card.php';
		}

		return [
			'type' => $type,
			'path' => $dir . $script
		];
	}

	/**
	 * Check if user has permissions for the requested resource using the modern hasRight() method.
	 *
	 * @param string $elementType Element type
	 * @param string $view View type
	 * @param int $id Element ID (for specific record access)
	 * @return bool True if user has permission
	 */
	private function checkPermissions($elementType, $view, $id = 0)
	{
		// Default to false
		$permitted = false;

		// Check permissions based on element type
		switch ($elementType) {
			case 'thirdparty':
				$permitted = $this->user->hasRight('societe', 'lire') ||
					($view === 'create' && $this->user->hasRight('societe', 'creer'));
				break;

			case 'contact':
				$permitted = $this->user->hasRight('societe', 'contact->lire') ||
					($view === 'create' && $this->user->hasRight('societe', 'contact->creer'));
				break;

			case 'product':
				$permitted = $this->user->hasRight('produit', 'lire') ||
					($view === 'create' && $this->user->hasRight('produit', 'creer'));
				break;

			case 'project':
				$permitted = $this->user->hasRight('projet', 'lire') ||
					($view === 'create' && $this->user->hasRight('projet', 'creer'));
				break;

			case 'project_task':
				$permitted = $this->user->hasRight('projet', 'lire');
				break;

			case 'invoice_customer':
				$permitted = $this->user->hasRight('facture', 'lire') ||
					($view === 'create' && $this->user->hasRight('facture', 'creer'));
				break;

			case 'invoice_supplier':
				$permitted = $this->user->hasRight('fournisseur', 'facture->lire') ||
					($view === 'create' && $this->user->hasRight('fournisseur', 'facture->creer'));
				break;

			case 'order':
				$permitted = $this->user->hasRight('commande', 'lire') ||
					($view === 'create' && $this->user->hasRight('commande', 'creer'));
				break;

			case 'order_supplier':
				$permitted = $this->user->hasRight('fournisseur', 'commande->lire') ||
					($view === 'create' && $this->user->hasRight('fournisseur', 'commande->creer'));
				break;

			case 'proposal':
				$permitted = $this->user->hasRight('propal', 'lire') ||
					($view === 'create' && $this->user->hasRight('propal', 'creer'));
				break;

			case 'expedition':
				$permitted = $this->user->hasRight('expedition', 'lire') ||
					($view === 'create' && $this->user->hasRight('expedition', 'creer'));
				break;

			case 'payment':
				$permitted = $this->user->hasRight('facture', 'paiement');
				break;

			case 'payment_supplier':
				$permitted = $this->user->hasRight('fournisseur', 'facture->paiement');
				break;

			case 'bank':
				$permitted = $this->user->hasRight('banque', 'lire') ||
					($view === 'create' && $this->user->hasRight('banque', 'creer'));
				break;

			case 'agenda':
				$permitted = $this->user->hasRight('agenda', 'myactions->read') ||
					$this->user->hasRight('agenda', 'allactions->read');
				break;

			case 'contract':
				$permitted = $this->user->hasRight('contrat', 'lire') ||
					($view === 'create' && $this->user->hasRight('contrat', 'creer'));
				break;

			case 'fichinter':
				$permitted = $this->user->hasRight('ficheinter', 'lire') ||
					($view === 'create' && $this->user->hasRight('ficheinter', 'creer'));
				break;

			case 'adherent':
				$permitted = $this->user->hasRight('adherent', 'lire') ||
					($view === 'create' && $this->user->hasRight('adherent', 'creer'));
				break;

			case 'categories':
				$permitted = $this->user->hasRight('categorie', 'lire') ||
					($view === 'create' && $this->user->hasRight('categorie', 'creer'));
				break;

			default:
				// If we don't have specific permission checks, default to read access
				$permitted = true;
				break;
		}

		// If accessing a specific record, check if user has access to that specific record
		if ($permitted && $id > 0) {
			$permitted = $this->checkSpecificRecordAccess($elementType, $id);
		}

		return $permitted;
	}

	/**
	 * Check if user has access to a specific record
	 *
	 * @param string $elementType Element type
	 * @param int $id Element ID
	 * @return bool True if user has access to the specific record
	 */
	private function checkSpecificRecordAccess($elementType, $id)
	{
		global $db, $conf;

		// For thirdparties, check if user has access to this specific thirdparty
		if ($elementType === 'thirdparty') {
			require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
			$soc = new Societe($db);
			if ($soc->fetch($id) > 0) {
				return $soc->isInEEC() || $soc->isCustomer() || $soc->isSupplier();
			}
			return false;
		}

		// For projects, check if user is assigned to the project
		if ($elementType === 'project') {
			require_once DOL_DOCUMENT_ROOT . '/projet/class/project.class.php';
			$project = new Project($db);
			if ($project->fetch($id) > 0) {
				return $project->restrictedProjectArea($this->user) == 0;
			}
			return false;
		}

		// For other element types, we'll assume access if the user has general permission
		// In a full implementation, you would check each object type specifically
		return true;
	}

	/**
	 * Generate a human-readable description for the URL
	 *
	 * @param string $type Element type
	 * @param string $view View type
	 * @param int $id Element ID
	 * @param string $statusFilter The status filter used
	 * @return string Description
	 */
	private function generateDescription($type, $view, $id, $statusFilter = '')
	{
		global $langs;

		// Load translations
		$langs->load("companies");
		$langs->load("bills");
		$langs->load("orders");
		$langs->load("propal");
		$langs->load("projects");

		// Get the label for the element type
		$label = '';
		switch ($type) {
			case 'thirdparty':
				$label = $langs->trans("ThirdParty");
				break;
			case 'contact':
				$label = $langs->trans("Contact");
				break;
			case 'product':
				$label = $langs->trans("ProductService");
				break;
			case 'project':
				$label = $langs->trans("Project");
				break;
			case 'project_task':
				$label = $langs->trans("Task");
				break;
			case 'invoice_customer':
				$label = $langs->trans("CustomerInvoice");
				break;
			case 'invoice_supplier':
				$label = $langs->trans("SupplierInvoice");
				break;
			case 'order':
				$label = $langs->trans("CustomerOrder");
				break;
			case 'order_supplier':
				$label = $langs->trans("SupplierOrder");
				break;
			case 'proposal':
				$label = $langs->trans("Proposal");
				break;
			case 'expedition':
				$label = $langs->trans("Shipment");
				break;
			case 'payment':
				$label = $langs->trans("Payment");
				break;
			case 'payment_supplier':
				$label = $langs->trans("SupplierPayment");
				break;
			case 'bank':
				$label = $langs->trans("BankAccount");
				break;
			case 'agenda':
				$label = $langs->trans("Event");
				break;
			case 'contract':
				$label = $langs->trans("Contract");
				break;
			case 'fichinter':
				$label = $langs->trans("Intervention");
				break;
			case 'adherent':
				$label = $langs->trans("Member");
				break;
			case 'categories':
				$label = $langs->trans("Category");
				break;
			default:
				$label = ucfirst($type);
				break;
		}

		// Generate description based on view and status
		if ($view === 'list') {
			$baseDesc = $langs->trans("ListOf") . " " . $label;
			if (!empty($statusFilter)) {
				return $baseDesc . " (" . ucfirst($statusFilter) . ")";
			}
			return $baseDesc;
		} elseif ($view === 'create') {
			return $langs->trans("New") . " " . $label;
		} elseif ($id > 0) {
			return $label . " #" . $id;
		} else {
			return $label;
		}
	}
}
