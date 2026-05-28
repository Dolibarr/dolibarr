<?php
/* Copyright (C) 2026	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2026	Nick Fragoulis
 * Copyright (C) 2026		MDW						<mdeweerd@users.noreply.github.com>
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
 * \file htdocs/ai/tools/crud_objects.class.php
 * \ingroup ai
 * \brief MCP Server tool for CRUD operations on Dolibarr objects.
 */

require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';

/**
 * Tool class for CRUD operations on Dolibarr objects
 */
class ToolCrudObjects extends McpTool
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
	 * Configuration Map.
	 *
	 * Defines specific field names for each object type to ensure correct data mapping.
	 * Each entry contains: class, path, card, date_field, soc_field
	 *
	 * @var array<string, array{class:string,path:string,card:string,date_field:string,soc_field:string}>
	 */
	private $map = [
		// --- CUSTOMER OBJECTS ---
		'proposal' => [
			'class' => 'Propal',
			'path' => '/comm/propal/class/propal.class.php',
			'card' => '/comm/propal/card.php',
			'date_field' => 'datep', // Propal uses 'datep'
			'soc_field' => 'socid'
		],
		'order' => [
			'class' => 'Commande',
			'path' => '/commande/class/commande.class.php',
			'card' => '/commande/card.php',
			'date_field' => 'date_commande', // Commande uses 'date_commande'
			'soc_field' => 'socid'
		],
		'invoice' => [
			'class' => 'Facture',
			'path' => '/compta/facture/class/facture.class.php',
			'card' => '/compta/facture/card.php',
			'date_field' => 'date',
			'soc_field' => 'socid'
		],
		// --- SUPPLIER OBJECTS ---
		'supplier_proposal' => [
			'class' => 'SupplierProposal',
			'path' => '/supplier_proposal/class/supplier_proposal.class.php',
			'card' => '/supplier_proposal/card.php',
			'date_field' => 'date', // Uses standard 'date' property for doc date
			'soc_field' => 'socid'
		],
		'supplier_order' => [
			'class' => 'CommandeFournisseur',
			'path' => '/fourn/class/fournisseur.commande.class.php',
			'card' => '/fourn/commande/card.php',
			'date_field' => 'date_commande',
			'soc_field' => 'socid'
		],
		'supplier_invoice' => [
			'class' => 'FactureFournisseur',
			'path' => '/fourn/class/fournisseur.facture.class.php',
			'card' => '/fourn/facture/card.php',
			'date_field' => 'date',
			'soc_field' => 'socid'
		],
		// --- LOGISTICS ---
		'shipment' => [
			'class' => 'Expedition',
			'path' => '/expedition/class/expedition.class.php',
			'card' => '/expedition/card.php',
			'date_field' => 'date_expedition',
			'soc_field' => 'socid'
		],
		'reception' => [
			'class' => 'Reception',
			'path' => '/reception/class/reception.class.php',
			'card' => '/reception/card.php',
			'date_field' => 'date_reception',
			'soc_field' => 'socid'
		],
	];

	/**
	 * Permission map for CRUD operations.
	 * Maps object types to their required Dolibarr permission (module, permission).
	 *
	 * @var array<string, array{0:string, 1:string}>
	 */
	private const PERM_MAP = [
		'proposal'          => ['propal', 'creer'],
		'order'             => ['commande', 'creer'],
		'invoice'           => ['facture', 'creer'],
		'supplier_proposal' => ['supplier_proposal', 'creer'],
		'supplier_order'    => ['fournisseur', 'commande'],
		'supplier_invoice'  => ['fournisseur', 'facture'],
		'shipment'          => ['expedition', 'creer'],
		'reception'         => ['reception', 'creer'],
	];

	/**
	 * Returns an array of tool definitions, including name, description, and input schema.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return [
			// Order tool
			[
				"name" => "create_sales_order",
				"description" => "Create a CUSTOMER SALES ORDER. This is specifically for creating ORDERS that customers place with you. USE THIS TOOL whenever user mentions: 'create', 'new' or 'add' with 'order', 'customer order' or 'sales order'. This is NOT for invoices or supplier orders. Examples of when to use this tool:
- 'create order for customer X'
- 'new order for Y'
- 'add order from customer Z'
- 'add order for X with 5 items'
If user says 'order' without any qualifier, they mean a SALES ORDER - use this tool.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"socid" => [
							"type" => "integer",
							"description" => "Customer ID (Thirdparty ID) - REQUIRED"
						],
						"date_commande" => [
							"type" => "string",
							"description" => "Order date (YYYY-MM-DD format, optional, defaults to today)"
						],
						"note" => [
							"type" => "string",
							"description" => "Order notes (optional)"
						],
						"lines" => [
							"type" => "array",
							"description" => "Products being ordered by the customer",
							"items" => [
								"type" => "object",
								"properties" => [
									"product_id" => ["type" => "integer", "default" => 0, "description" => "Product ID (0 if not found)"],
									"description" => ["type" => "string", "description" => "Product name or description"],
									"quantity" => ["type" => "number", "default" => 1, "description" => "Quantity ordered"],
									"unit_price" => ["type" => "number", "description" => "Selling price per unit (optional)"],
									"vat_rate" => ["type" => "number", "description" => "VAT rate (optional, auto-calculated if not provided)"]
								],
								"required" => ["quantity"]
							]
						]
					],
					"required" => ["socid"]
				]
			],
			// Invoice tool
			[
				"name" => "create_customer_invoice",
				"description" => "Create a customer invoice (bill). Do NOT use this for orders - use create_sales_order instead. Do NOT use this for payments - use pay_invoice instead. Examples: 'create invoice for customer X', 'new bill customer Y'",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"object_type" => [
							"type" => "string",
							"enum" => ["invoice"],
							"default" => "invoice"
						],
						"header" => [
							"type" => "object",
							"description" => "Invoice header data. Must include 'socid' (Customer ID).",
							"properties" => [
								"socid" => ["type" => "integer", "description" => "Customer ID (Thirdparty ID)"],
								"date" => ["type" => "string", "description" => "Invoice date (YYYY-MM-DD)"],
								"note_public" => ["type" => "string", "description" => "Public note"],
								"note_private" => ["type" => "string", "description" => "Private note"]
							],
							"required" => ["socid"]
						],
						"lines" => [
							"type" => "array",
							"description" => "Invoice line items.",
							"items" => [
								"type" => "object",
								"properties" => [
									"product_id" => ["type" => "integer", "default" => 0],
									"description" => ["type" => "string"],
									"quantity" => ["type" => "number", "default" => 1],
									"unit_price" => ["type" => "number"],
									"vat_rate" => ["type" => "number"],
									"fk_unit" => ["type" => "integer"]
								],
								"required" => ["quantity"]
							]
						]
					],
					"required" => ["header"]
				]
			],
			// Generic tool for other documents (excluding order and invoice)
			[
				"name" => "create_other_document",
				"description" => "Create documents other than orders and invoices. Use this for: 'proposal', 'supplier_order', 'supplier_invoice', 'supplier_proposal'. DO NOT use for 'order' or 'invoice' - they have dedicated tools.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"object_type" => [
							"type" => "string",
							"enum" => ['proposal', 'supplier_order', 'supplier_invoice', 'supplier_proposal'],
							"description" => "Document type. Cannot be 'order' or 'invoice'."
						],
						"header" => [
							"type" => "object",
							"description" => "Header data. Must include 'socid'.",
							"properties" => [
								"socid" => ["type" => "integer", "description" => "Thirdparty ID (Customer for proposal, Supplier for supplier_*)"],
								"date" => ["type" => "string", "description" => "Document date (YYYY-MM-DD)"],
								"duree_validite" => ["type" => "integer", "description" => "Validity in days (proposal only)"],
								"note_public" => ["type" => "string", "description" => "Public note"],
								"note_private" => ["type" => "string", "description" => "Private note"]
							],
							"required" => ["socid"]
						],
						"lines" => [
							"type" => "array",
							"description" => "Array of line items.",
							"items" => [
								"type" => "object",
								"properties" => [
									"product_id" => ["type" => "integer", "default" => 0],
									"description" => ["type" => "string"],
									"quantity" => ["type" => "number", "default" => 1],
									"unit_price" => ["type" => "number"],
									"vat_rate" => ["type" => "number"],
									"fk_unit" => ["type" => "integer"]
								],
								"required" => ["quantity"]
							]
						]
					],
					"required" => ["object_type", "header"]
				]
			],
			[
				"name" => "add_line_item",
				"description" => "Add a single line to an existing draft document.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"object_type" => ["type" => "string", "enum" => array_keys($this->map)],
						"parent_id" => ["type" => "integer"],
						"product_id" => ["type" => "integer", "default" => 0],
						"description" => ["type" => "string"],
						"quantity" => ["type" => "number", "default" => 1],
						"unit_price" => ["type" => "number"],
						"vat_rate" => ["type" => "number"]
					],
					"required" => ["object_type", "parent_id", "quantity"]
				]
			],
			[
				"name" => "delete_object",
				"description" => "Delete a Draft document.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"object_type" => ["type" => "string", "enum" => array_keys($this->map)],
						"id" => ["type" => "integer"]
					],
					"required" => ["object_type", "id"]
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
		return ['commercial', 'billing', 'thirdparty'];
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
		global $user, $langs, $conf, $mysoc;

		// Ensure $this->user is the authenticated global user
		$this->user = $user;

		if (!$user->id) {
			return ["error" => "User not authenticated."];
		}

		if (!is_object($mysoc) || empty($mysoc->id)) {
			$mysoc = new Societe($this->db);
			$mysoc->setMysoc($conf);
		}

		$langs->loadLangs(["main", "bills", "companies", "orders", "propal", "products", "supplier_orders", "supplier_proposals", "sendings", "receptions"]);

		try {
			switch ($name) {
				case 'create_sales_order':
					// Direct mapping for order
					$args['object_type'] = 'order';
					// Reorganize args to match createDocument format
					if (!isset($args['header']) && isset($args['socid'])) {
						$args['header'] = [
							'socid' => $args['socid'],
							'date_commande' => $args['date_commande'] ?? null,
							'note' => $args['note'] ?? null
						];
						unset($args['socid'], $args['date_commande'], $args['note']);
					}
					return $this->createDocument($args);

				case 'create_customer_invoice':
					$args['object_type'] = 'invoice';
					return $this->createDocument($args);

				case 'create_other_document':
					// object_type is already set in args
					return $this->createDocument($args);

				case 'add_line_item':
					return $this->addLineItem($args);

				case 'delete_object':
					return $this->deleteObject($args);

				default:
					return ["error" => "Unknown tool: $name"];
			}
		} catch (Exception $e) {
			return ["error" => "Exception: " . $e->getMessage()];
		}
	}

	/**
	 * Create Document
	 *
	 * @param array<string, mixed> $args {
	 *                                   object_type: string,
	 *                                   header: array<string, mixed>,
	 *                                   lines?: array<array<string, mixed>>
	 *                                   } Arguments including type, header data, and optional lines.
	 *
	 * @return array<string, mixed>
	 */
	private function createDocument(array $args)
	{
		$type = (string) $args['object_type'];

		// Validate type against map
		if (! isset($this->map[$type])) {
			return ["error" => "Configuration not found for object type: " . $type];
		}

		// Check permissions
		$permError = $this->checkPermission($type);
		if ($permError !== null) {
			return $permError;
		}

		/** @var array{class: string, path: string, card: string, date_field: string, soc_field: string} $confMap */
		$confMap = $this->map[$type];

		// Instantiate the specific Dolibarr class (Propal, Commande, etc.)
		// We treat it as 'mixed' or generic object here to allow dynamic property assignment
		$obj = $this->instantiate($type);

		// Process Header with Field Mapping
		foreach ($args['header'] as $k => $v) {
			$key = (string) $k;

			// Map 'date' to specific date field (e.g., date_commande)
			if ($key === 'date' && isset($confMap['date_field'])) {
				$key = $confMap['date_field'];
			}
			// Map 'socid' to specific soc field
			if ($key === 'socid' && isset($confMap['soc_field'])) {
				$key = $confMap['soc_field'];
				$obj->fk_soc = $v; // Standard Dolibarr field for thirdparty linkage
			}

			// Convert date strings to timestamp if needed
			if (strpos($key, 'date') !== false && ! is_numeric($v) && is_string($v)) {
				$timestamp = strtotime($v);
				if ($timestamp !== false) {
					$v = $timestamp;
				}
			}

			// Assign value dynamically
			// PHPStan normally dislikes dynamic property access on objects, so we suppress it for this mapper logic
			/** @phpstan-ignore-next-line */
			$obj->{$key} = $v;
		}

		// Set Defaults
		$dateField = $confMap['date_field'] ?? 'date';
		// Check if date field is empty (property might not exist or be null/0)
		if (empty($obj->{$dateField})) {
			/** @phpstan-ignore-next-line */
			$obj->{$dateField} = dol_now();
		}

		// Specific default for Proposals
		if ($type === 'proposal' && empty($obj->duree_validite)) {
			$obj->duree_validite = 15;
		}

		// Attempt Creation
		$id = $obj->create($this->user);

		if ($id <= 0) {
			$err = (string) $obj->error;
			if (! empty($obj->errors)) {
				$err .= " " . json_encode($obj->errors);
			}
			return ["error" => "Creation failed ($type): " . $err];
		}

		// Process Lines (if provided)
		$linesAdded = 0;
		$lineErrors = [];

		if (! empty($args['lines']) && is_array($args['lines'])) {
			foreach ($args['lines'] as $line) {
				$line['object_type'] = $type;
				$line['parent_id'] = $id;

				// Process line addition
				// Assumes processAddLine returns array{success: bool, error?: string}
				$res = $this->processAddLine($obj, $line);

				if (! empty($res['success'])) {
					$linesAdded++;
				} else {
					$lineErrors[] = isset($res['error']) ? (string) $res['error'] : 'Unknown line error';
				}
			}
		}

		return [
			"success"     => true,
			"id"          => (int) $id,
			"ref"         => (string) $obj->ref,
			"lines_added" => $linesAdded,
			"line_errors" => $lineErrors,
			"url"         => DOL_URL_ROOT . $confMap['card'] . "?id=" . $id
		];
	}

	/**
	 * Add a line to a document object.
	 *
	 * @param CommonObject $object The Dolibarr object (Propal, Commande, Facture, etc.).
	 * @param array<string, mixed> $args {
	 *                                   product?: string,
	 *                                   description?: string,
	 *                                   qty?: float|int|string,
	 *                                   quantity?: float|int|string,
	 *                                   price?: float|int|string,
	 *                                   unit_price?: float|int|string,
	 *                                   vat_rate?: float|int|string,
	 *                                   discount?: float|int|string,
	 *                                   object_type: string
	 *                                   } Line arguments.
	 *
	 * @return array<string, mixed>
	 */
	private function processAddLine(CommonObject $object, array $args)
	{
		global $mysoc, $conf;
		// Check status (Dolibarr objects usually use 'statut' property, 0 = Draft)
		if (isset($object->statut) && $object->statut != 0) {
			return ["success" => false, "error" => "Document is not in draft status"];
		}

		// Ensure Thirdparty is loaded
		if (empty($object->thirdparty)) {
			$object->fetch_thirdparty();
		}

		// Get company default VAT
		$companyDefaultVAT = 0.0;
		if (! empty($conf->global->MAIN_VAT_DEFAULT)) {
			$companyDefaultVAT = (float) $conf->global->MAIN_VAT_DEFAULT;
		}

		// Normalize Inputs
		$productIdentifier = isset($args['product']) ? (string) $args['product'] : (isset($args['description']) ? (string) $args['description'] : '');

		$qtyInput = $args['qty'] ?? $args['quantity'] ?? 1;
		$qty = (float) $qtyInput;

		$priceInput = isset($args['price']) ? $args['price'] : ($args['unit_price'] ?? null);
		$price = ($priceInput !== null) ? (float) $priceInput : null;

		$vat = isset($args['vat_rate']) ? (float) $args['vat_rate'] : null;
		$discount = isset($args['discount']) ? (float) $args['discount'] : 0.0;

		if ($qty <= 0) {
			return ["success" => false, "error" => "Quantity must be positive."];
		}

		// Find product
		/** @var Product|null $prod */
		$prod = null;

		if ($productIdentifier !== '') {
			$findResult = $this->findProduct($productIdentifier);
			if (is_array($findResult) && isset($findResult['error'])) {
				// Only abort if the caller EXPLICITLY asked for a product (via the 'product'
				// argument). If they only provided a free-text 'description', we silently
				// fall through with $prod = null so Dolibarr creates a free-text line item,
				// which is a perfectly valid Dolibarr feature.
				// Previous behaviour aborted ALL line creations whose description didn't
				// match an existing product reference, which broke AI-driven creation of
				// invoices/orders/proposals from one-off line descriptions.
				if (isset($args['product'])) {
					return array_merge(['success' => false], $findResult);
				}
				// fall through: $prod stays null
			}
			if (is_object($findResult)) {
				$prod = $findResult;
			}
		}

		// Set values based on product or user input
		if ($price === null) {
			$price = ($prod && isset($prod->price)) ? (float) $prod->price : 0.0;
		}

		if ($vat === null) {
			$vat = ($prod && isset($prod->tva_tx) && $prod->tva_tx !== '') ? (float) $prod->tva_tx : $companyDefaultVAT;
		}

		// Description
		$userDesc = isset($args['description']) ? (string) $args['description'] : '';
		$desc = '';

		if ($userDesc !== '') {
			if ($prod && ! empty($prod->label)) {
				if (strtolower($userDesc) === strtolower($prod->label)) {
					$desc = $prod->label;
				} else {
					$desc = $prod->label . ' - ' . $userDesc;
				}
			} else {
				$desc = $userDesc;
			}
		} else {
			if ($prod && ! empty($prod->label)) {
				$desc = $prod->label;
			}
		}

		// Product Unit handling
		$fk_unit = 0;
		if (! empty($conf->global->PRODUCT_USE_UNITS) && $prod && ! empty($prod->fk_unit)) {
			$fk_unit = (int) $prod->fk_unit;
		}

		// Add the line
		$res = 0;
		$docType = (string) $args['object_type'];
		$fkProduct = ($prod && isset($prod->id)) ? (int) $prod->id : 0;
		$prodType = ($prod && isset($prod->type)) ? (int) $prod->type : 0;

		if ($docType === 'invoice') {
			/** @var Facture $object */
			$res = $object->addline($desc, $price, $qty, $vat, 0, 0, $fkProduct, $discount, '', '', 0, 0, '', 'HT', 0, $prodType, -1, 0, '', 0);
		} elseif ($docType === 'order') {
			/** @var Commande $object */
			$res = $object->addline($desc, $price, $qty, $vat, 0, 0, $fkProduct, $discount, 0, 0, 'HT', 0, '', '', $prodType);
		} elseif ($docType === 'proposal') {
			/** @var Propal $object */
			$res = $object->addline($desc, $price, $qty, $vat, 0, 0, $fkProduct, $discount, 'HT', 0, 0, $prodType);
		} elseif ($docType === 'supplier_invoice') {
			/** @var FactureFournisseur $object */
			// IMPORTANT: FactureFournisseur::addline() does NOT share the same signature
			// as Facture::addline(). Its parameter order is:
			//   ($desc, $pu, $txtva, $txlocaltax1, $txlocaltax2, $qty, $fk_product, $remise_percent, ...)
			// i.e. $qty is in position 6, not 3 (unlike customer Facture / Commande / Propal).
			// The previous call passed our $qty as $txtva (-> a 1% VAT rate) and our $vat
			// as $txlocaltax1, and position 6 ended up being a hardcoded 0 -> a line was
			// inserted with qty=0, which Dolibarr silently dropped from the visible totals.
			$res = $object->addline($desc, $price, $vat, 0, 0, $qty, $fkProduct, $discount, '', '', 0, 0, 'HT', $prodType);
		} elseif ($docType === 'supplier_order') {
			/** @var CommandeFournisseur $object */
			$res = $object->addline($desc, $price, $qty, $vat, 0, 0, $fkProduct, $discount, 0, 0, 'HT', 0, '', '', $prodType);
		} elseif ($docType === 'supplier_proposal') {
			/** @var SupplierProposal $object */
			$res = $object->addline($desc, $price, $qty, $vat, 0, 0, $fkProduct, $discount, 0, 0, 'HT', 0, '', '', $prodType);
		} elseif ($docType === 'shipment') {
			// Shipment Logic
			if (! getDolGlobalString('SHIPMENT_STANDALONE')) {
				return ["success" => false, "error" => "Shipment standalone mode required to add lines manually."];
			}
			// addlinefree(qty, type, fk_product, fk_unit, weight, desc, weight_units)
			/** @var Expedition $object */
			$res = $object->addlinefree($qty, 'shipping', $fkProduct, $fk_unit, 0, $desc, 0);
		} elseif ($docType === 'reception') {
			// Reception Logic
			if (! getDolGlobalString('RECEPTION_STANDALONE')) {
				return ["success" => false, "error" => "Reception standalone mode required to add lines manually."];
			}
			require_once DOL_DOCUMENT_ROOT . '/reception/class/receptionlinebatch.class.php';
			// addlinefree(qty, type, fk_product, fk_unit, weight, desc, weight_units)
			/** @var Reception $object */
			$res = $object->addlinefree($qty, 'reception', $fkProduct, $fk_unit, 0, $desc, 0);
		} else {
			return ["success" => false, "error" => "Type $docType not supported for lines"];
		}

		// Update unit if needed (Logic for standard docs, Shipment/Reception handle units in addlinefree)
		// Only trigger updateLineUnit for the standard commercial documents
		$commercialDocs = ['invoice', 'order', 'proposal', 'supplier_invoice', 'supplier_order', 'supplier_proposal'];
		if (in_array($docType, $commercialDocs, true) && $res > 0 && $fk_unit > 0 && ! empty($conf->global->PRODUCT_USE_UNITS)) {
			$this->updateLineUnit($docType, $res, $fk_unit);
		}

		if ($res > 0) {
			return [
				"success" => true,
				"line_id" => (int) $res,
				"debug"   => [
					"product_identifier" => $productIdentifier,
					"final_description"  => $desc
				]
			];
		}

		$errorMsg = isset($object->error) ? (string) $object->error : 'Unknown error adding line';
		return ["success" => false, "error" => $errorMsg];
	}

	/**
	 * Entry point for the 'add_line_item' tool. Adds line to an already existing object.
	 * Instantiates the document and calls the line processing helper.
	 *
	 * @param array{object_type:string,parent_id:int,product_id?:int,description?:string,quantity?:float|int,unit_price?:float|int,vat_rate?:float|int} $args Tool arguments for adding a line item
	 *
	 * @return array{success:bool,line_id?:int,error?:string}
	 */
	private function addLineItem(array $args)
	{
		$type = (string) $args['object_type'];

		// Check Permissions
		$permError = $this->checkPermission($type);
		if ($permError !== null) {
			return [
				'success' => false,
				'error'   => $permError['error'] ?? 'Permission denied'
			];
		}

		$parentId = (int) $args['parent_id'];

		// Instantiate and Fetch the Parent Document
		try {
			$obj = $this->instantiate($type);
		} catch (Exception $e) {
			return ["success" => false, "error" => $e->getMessage()];
		}

		if (! method_exists($obj, 'fetch')) {
			return ["success" => false, "error" => "Object does not support fetching"];
		}

		$result = $obj->fetch($parentId);
		if ($result <= 0) {
			return ["success" => false, "error" => "Parent document not found with ID: " . $parentId];
		}

		// Map Schema arguments to Helper arguments
		// The helper expects 'product' (which can be an ID or Ref), but schema sends 'product_id'
		if (! empty($args['product_id'])) {
			$args['product'] = (string) $args['product_id'];
		}

		// Call the helper logic
		// processAddLine(CommonObject $object, array $args)
		return $this->processAddLine($obj, $args);
	}

	/**
	 * Find a product by various identifiers (ID, Ref, Barcode, Label).
	 *
	 * @param   string|int $identifier The search term (ID, ref, barcode, etc.).
	 *
	 * @return  Product|array{error: string, matches?: list<string>} Returns the Product object on success, or an error array.
	 */
	private function findProduct($identifier)
	{
		$product = new Product($this->db);
		// Cast strictly to string for string manipulation
		$searchString = trim((string) $identifier);

		// Regex to handle "id: 123" format
		$matches = [];
		if (preg_match('/^(?:id)[:\s]+(\d+)$/i', $searchString, $matches)) {
			$searchString = $matches[1];
		}

		// Try to Fetch by ID
		if (is_numeric($searchString)) {
			if ($product->fetch((int) $searchString) > 0) {
				return $product;
			}
		}

		// Try to Fetch by Ref
		if ($product->fetch(0, $searchString) > 0) {
			return $product;
		}

		// Custom SQL Search (Barcode, Label, Ref - Exact Match)

		$sql = "SELECT rowid FROM " . MAIN_DB_PREFIX . "product";
		$sql .= " WHERE (barcode = '" . $this->db->escape($searchString) . "' OR label = '" . $this->db->escape($searchString) . "' OR ref = '" . $this->db->escape($searchString) . "')";
		$sql .= " AND entity IN (" . getEntity('product') . ")";
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

		return ["error" => "Product '" . $searchString . "' not found."];
	}

	/**
	 * Delete a document object.
	 *
	 * @param   array{object_type: string, id: int|string} $args   Arguments containing type and ID.
	 *
	 * @return  array{success: bool}|array{error: string} Result array.
	 */
	private function deleteObject(array $args): array
	{
		$type = (string) $args['object_type'];
		$id = (int) $args['id'];

		// Check permissions
		$permError = $this->checkPermission($type);
		if ($permError !== null) {
			return $permError;
		}

		// Instantiate generic object based on type
		$obj = $this->instantiate($type);

		// Fetch object
		if ($obj->fetch($id) <= 0) {
			return ["error" => "Object not found with ID: " . $id];
		}

		// Check Status: Can only delete drafts (statut == 0)
		// We use int cast because status might be string '0' in some DB configurations
		$status = isset($obj->statut) ? (int) $obj->statut : -1;

		if ($status !== 0) {
			return ["error" => "Can only delete drafts (status 0). Current status: " . $status];
		}

		// Perform Deletion
		if ($obj->delete($this->user) > 0) {
			return ["success" => true];
		}

		// Capture error message
		$errorMsg = ! empty($obj->error) ? (string) $obj->error : 'Unknown error';

		return ["error" => "Delete failed: " . $errorMsg];
	}

	/**
	 * Check if the current user has permission for the given object type.
	 *
	 * @param   string $type  Object type key.
	 *
	 * @return  array{error: string}|null  Null if allowed, error array if denied.
	 */
	private function checkPermission(string $type): ?array
	{
		if (! isset(self::PERM_MAP[$type])) {
			return ["error" => "Unknown type for permission check: " . $type];
		}
		[$module, $perm] = self::PERM_MAP[$type];
		if (! $this->user->hasRight($module, $perm)) {
			return ["error" => "Permission denied for action on " . $type];
		}
		return null;
	}

	/**
	 * Factory Helper to instantiate Dolibarr objects.
	 *
	 * @param   string $type  Object type key (e.g., 'proposal', 'invoice').
	 *
	 * @return  CommonObject  New instance of the specific Dolibarr class.
	 * @throws  Exception     If the type is unknown or class not found.
	 */
	private function instantiate(string $type): CommonObject
	{
		if (! isset($this->map[$type])) {
			throw new Exception("Unknown type: " . $type);
		}

		$config = $this->map[$type];
		$path = (string) $config['path'];
		$className = (string) $config['class'];

		// Include the base class and the specific class file
		require_once DOL_DOCUMENT_ROOT . '/core/class/commonobject.class.php';
		require_once DOL_DOCUMENT_ROOT . $path;

		if (! class_exists($className)) {
			throw new Exception("Class '$className' not found for type '$type'");
		}

		return new $className($this->db);
	}

	/**
	 * Helper to update units via SQL directly.
	 *
	 * @param   string $type   Document type (e.g., 'invoice', 'order').
	 * @param   int    $lineId Line RowID.
	 * @param   int    $unitId Unit RowID.
	 *
	 * @return  void			Only attempts to update the database, no result indication
	 */
	private function updateLineUnit(string $type, int $lineId, int $unitId): void
	{
		// Map document types to their specific detail tables
		/** @var array<string, string> $tableMap */
		$tableMap = [
			'invoice'           => 'facturedet',
			'order'             => 'commandedet',
			'proposal'          => 'propaldet',
			'supplier_invoice'  => 'facture_fourn_det',
			'supplier_order'    => 'commande_fournisseurdet',
			'supplier_proposal' => 'supplier_proposaldet'
		];

		if (! isset($tableMap[$type])) {
			return;
		}

		$table = $tableMap[$type];

		$sql = "UPDATE " . MAIN_DB_PREFIX . $this->db->escape($table);
		$sql .= " SET fk_unit = " . (int) $unitId;
		$sql .= " WHERE rowid = " . (int) $lineId;

		$resql = $this->db->query($sql);

		if (! $resql) {
			dol_syslog("Error updating unit for line $lineId: " . $this->db->lasterror(), LOG_ERR);
		}
	}
}
