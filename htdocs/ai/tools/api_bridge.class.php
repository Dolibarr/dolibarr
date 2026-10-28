<?php
/* Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
 * Copyright (C) 2026	Nick Fragoulis
 * Copyright (C) 2026	Carlos Herrera			<cehojac@gmail.com>
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
 *      central authentication bridge (DolibarrApiAccess::$user = the acting user),
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
 *   - Schemas come from a light docblock parser; reusing Restler's
 *     CommentParser/Routes metadata was measured and rejected (see the
 *     discussion in #38356).
 *
 * Tool definitions are cached across requests in the module temp directory
 * (see defsCacheFile(): keyed on the enabled-modules list, so a module
 * (de)activation switches to a fresh cache file; editing this file - where
 * the enrichments live - invalidates it too). AI_MCP_BRIDGE_DEFS_CACHE_TTL
 * tunes the lifetime in seconds (default 86400, 0 disables the cache).
 */

/**
 * Class ToolApiBridge
 *
 * Exposes enabled REST API endpoints as MCP tools (read-only POC).
 */
class ToolApiBridge extends McpTool
{

	/**
	 * Endpoint key -> intent categories of the assistant's query classifier
	 * (classifyIntentUniversal() in parse_intent.php: billing, commercial,
	 * thirdparty, stock, project, reporting). On Latin-script queries the
	 * classifier prefilters which tools the model sees, so bridge tools MUST
	 * carry this vocabulary — anything else gets every bridge tool filtered
	 * out of the prompt. Endpoints absent from this map (external modules)
	 * fall back to all categories so they stay selectable.
	 */
	const ENDPOINT_CATEGORIES = array(
		'thirdparties' => array('thirdparty', 'billing', 'commercial'),
		'categories' => array('thirdparty', 'stock'),
		'invoices' => array('billing', 'thirdparty'),
		'supplier_invoices' => array('billing', 'thirdparty'),
		'supplier_orders' => array('commercial', 'thirdparty'),
		'supplier_proposals' => array('commercial', 'thirdparty'),
		'proposals' => array('commercial', 'thirdparty'),
		'orders' => array('commercial', 'thirdparty'),
		'products' => array('stock', 'commercial'),
		'stockmovements' => array('stock'),
		'warehouses' => array('stock'),
		'projects' => array('project'),
		'tasks' => array('project'),
		'agendaevents' => array('thirdparty', 'project'),
		'interventions' => array('project', 'commercial'),
		'contracts' => array('commercial', 'billing'),
		'members' => array('thirdparty', 'billing'),
		'subscriptions' => array('thirdparty', 'billing'),
		'expensereports' => array('billing'),
		'tickets' => array('thirdparty', 'project'),
		'shipments' => array('stock', 'commercial', 'thirdparty'),
		'receptions' => array('stock', 'thirdparty'),
		'contacts' => array('thirdparty', 'commercial'),
		'bankaccounts' => array('billing', 'reporting'),
		'variouspayments' => array('billing', 'reporting'),
		'socialcontributions' => array('billing', 'reporting'),
		'vatpayments' => array('billing', 'reporting'),
		'donations' => array('billing', 'thirdparty'),
		'holidays' => array('project', 'reporting'),
		'boms' => array('stock'),
		'mos' => array('stock'),
		'productlots' => array('stock'),
		'workstations' => array('stock'),
		'knowledgemanagement' => array('thirdparty', 'project'),
		'memberstypes' => array('thirdparty', 'billing'),
		'partnerships' => array('thirdparty', 'commercial'),
		'multicurrencies' => array('billing', 'reporting'),
		'websites' => array('commercial'),
		'users' => array('thirdparty', 'project')
	);

	/**
	 * Default and ceiling applied to list 'limit' parameters when called
	 * through the bridge. API methods default to 100 full objects — too much
	 * model context for a single call; everything stays reachable via 'page'.
	 */
	const BRIDGE_DEFAULT_LIMIT = 25;
	const BRIDGE_MAX_LIMIT = 100;

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
	 * Endpoints found by discoverEndpoints(), keyed by name. Filled on the first
	 * getDefinitions() call.
	 *
	 * @var array<string, array{module:string, path:string, candidate:string, class:string, label:string}>
	 */
	private $endpoints = [];

	/**
	 * Endpoint map + method whitelist: endpoint key => module condition, api class
	 * file/class, and the EXPLICIT list of exposed methods with their optional
	 * hand-written enrichment. A method absent from 'methods' is never exposed.
	 * Per method: 'suffix' (tool name suffix, defaults to list/get/lowercased),
	 * 'description' (appended to the derived tool description), 'params'
	 * (per-parameter doc overriding what the docblock parser guessed),
	 * 'fixed_params' (arguments forced on the call and hidden from the schema).
	 * An entry may set 'endpoint' to call the REST class of another key, so
	 * several groups of tools can share one REST class (documents_*).
	 * TODO Replace path/class discovery with the dynamic scan of api_*.class.php
	 * used by api/index.php.
	 *
	 * Endpoints are discovered at runtime by discoverEndpoints(); this map only
	 * carries the hand-written complement for the ones we know well, keyed by
	 * the same name the scan derives from the file (api_<key>.class.php). An
	 * endpoint absent from this map is still exposed, with the description and
	 * parameter docs reflection gives — that is the whole point of scanning.
	 *
	 * @var array<string, array{endpoint?:string, label?:string, methods?:array<string, array{suffix?:string, description?:string, params?:array<string,string>, fixed_params?:array<string,string>}>}>
	 */
	private $enrichments = [];

	/**
	 * Product names given by the last normaliseWriteArgs() call that matched no
	 * product, or several: name => close references found for it (may be empty).
	 *
	 * @var array<string,string[]>
	 */
	private $unresolvedProducts = [];

	/**
	 * Record the last normaliseWriteArgs() call found for a write on an existing
	 * record (or the order a shipment is made from), null when none.
	 *
	 * @var CommonObject|null
	 */
	private $writeTarget = null;

	/**
	 * Why the last normaliseWriteArgs() call could not prepare the write: a
	 * warehouse, a bank account, a payment mode or a project that is missing or
	 * ambiguous. Said to the user by writePrecheck().
	 *
	 * @var string[]
	 */
	private $writeProblems = [];

	/**
	 * Fields of the last update that may not be changed through the chat.
	 *
	 * @var string[]
	 */
	private $writeRefusedFields = [];

	/**
	 * Line of the last line write: the line found, its position, the changes.
	 *
	 * @var array{line:?object,position:int,changes:array<string,mixed>}
	 */
	private $writeLine = array('line' => null, 'position' => 0, 'changes' => array());

	/**
	 * Fallback docs for parameters shared by most API index()/get() methods, used
	 * when neither the per-method 'params' enrichment nor the docblock provides a
	 * usable description. Kept in one place so every bridged list tool documents
	 * the pagination/filter contract the same way.
	 *
	 * @var array<string, string>
	 */
	private $commonParamDocs = [
		'sortfield' => "Field to sort on, prefixed with 't.' (e.g. 't.rowid', 't.ref', 't.datec'). Use the SQL column names: creation date is 't.datec' (NEVER 'date_creation') and last modification 't.tms' (never 'date_modification').",
		'sortorder' => "Sort direction: 'ASC' or 'DESC'.",
		'limit' => "Maximum number of records to return.",
		'page' => "Zero-based page index for pagination.",
		'sqlfilters' => "Dolibarr Universal Search Filter (see 'sqlfilters format' in instructions): (t.field:operator:'value') combined with AND/OR and parentheses. E.g. \"(t.ref:like:'AB%') AND (t.datefield:>=:'2026-01-01')\"",
		'properties' => "Comma-separated list of properties to include in the response, to reduce its size (e.g. 'id,ref,label').",
		'id' => "Integer technical ID of the record."
	];

	/**
	 * 	Constructor
	 *
	 * 	@param	DoliDB		$db			Database handler
	 * 	@param	User|null	$user		Acting user provided by McpHandler (the caller; tool calls run with this user's rights)
	 * 	@param	Conf|null	$conf		Dolibarr config (optional)
	 */
	public function __construct($db, $user = null, $conf = null)
	{
		$this->db = $db;
		$this->user = $user;
		if ($conf !== null) {
			$this->conf = $conf;
		}

		// Note: the list of parameters for each tool is described into "params" to overload the one found into the API.
		// They are completed with descriptions like for searchfields, pagination_data, ... if the parameter exists in the API. using the content
		// found into $this->commonParamDocs.


		// Third parties
		$descriptioncreate = "Create a third party (customer, prospect or supplier). The user must confirms before anything is written.";

		$descriptionlist = "List third parties (prospects, customers, suppliers,...).";
		$descriptionlist .= "Use parameter 'mode' to restrict to a nature of third party (0=all, 1=only customers, 2=only prospects, 3=neither customer nor prospect, 4=suppliers).\n";
		$descriptionlist .= "To find one company by name use sqlfilters on t.nom or t.name_alias (e.g. \"((t.nom:like:'%acme%') OR (t.alias_name:like:'%acme%'))\").\n";
		$descriptionlist .= "Prefer a small 'limit' and 'properties' (e.g. 'id,name,code_client,code_fournisseur,email,town,client,fournisseur,status') to keep answers short.\n";
		$descriptionlist .= "List of useful sqlfilters or output fields with type:\n";
		$descriptionlist .= "- t.nom : string (thirdparty name)\n";
		$descriptionlist .= "- t.name_alias : string (thirdparty alternative name)\n";
		$descriptionlist .= "- t.email : string (thirdparty email)\n";
		$descriptionlist .= "- t.town : string (thirdparty email)\n";
		$descriptionlist .= "- t.zip : string (thirdparty email)\n";
		$descriptionlist .= "- t.fk_pays : integer (thirdparty country ID)\n";
		$descriptionlist .= "- t.status : integer enum (status ID) among 1=open, 0=closed.";
		$descriptionlist .= "\n";

		$descriptionfetch = "Fetch the full record of a given thirdparty with main properties (name, name_alias, email, address, status, contact channels, customer/supplier codes, VAT number, default payment terms/modes, outstanding limit).";
		$descriptionfetch .= "Fetch is done from ID.\n";
		$descriptionfetch .= "In the result 'client' is 1=customer, 2=prospect, 3=both; 'fournisseur' is 1 when supplier.";

		$this->enrichments['thirdparties'] = [
			'label' => 'third parties (customers, prospects, suppliers)',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => $descriptioncreate,
					'write' => [
						'preview' => 'AIPreviewCreateThirdparty',
						'required' => ['name'],
						'object' => 'Societe',
						'args' => ['name'],
						'right' => ['societe', 'creer']
					],
					'params' => [
						"name" => "Company or person name.",
						"client" => "1 customer, 2 prospect, 3 both, 0 none.",
						"fournisseur" => "1 when the third party is a supplier."
					]
				],
				'index' => [
					'default_properties' => 'id,name,name_alias,code_client,code_fournisseur,email,town,client,fournisseur,status',
					'description' => $descriptionlist,
					'params' => [
						'mode' => "Nature filter: 0=all (default), 1=customers/prospects, 2=prospects only, 3=neither customer nor prospect, 4=suppliers.",
						'category' => "Rowid of a third-party category (tag) to restrict the list to.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}} instead of a bare list; use it to know the total count."
					]
				],
				'get' => [
					'description' => $descriptionfetch
				],
				'getByEmail' => [
					'suffix' => 'get_by_email',
					'description' => "Find one third party by its exact company email address (not contact emails).",
					'params' => ['email' => "Exact email address of the company."]
				],
				'getOutStandingInvoices' => [
					'suffix' => 'outstanding_invoices',
					'description' => "Total amount still due on validated, unpaid invoices of one third party. Returns {opened: amount} in the company currency.",
					'params' => ['mode' => "'customer' (default) for customer invoices, 'supplier' for supplier invoices."]
				],
				'getOutStandingOrder' => [
					'suffix' => 'outstanding_orders',
					'description' => "Total amount of open (not yet invoiced) orders of one third party. Returns {opened: amount}.",
					'params' => ['mode' => "'customer' (default) for sales orders, 'supplier' for purchase orders."]
				],
				'getOutStandingProposals' => [
					'suffix' => 'outstanding_proposals',
					'description' => "Total amount of open commercial proposals of one third party. Returns {opened: amount}.",
					'params' => ['mode' => "'customer' (default) or 'supplier'."]
				]
			]
		];

		$this->enrichments['categories'] = [
			'label' => 'categories / tags',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => "Create a category (tag). The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewCreateCategory',
						'required' => ['label', 'type'],
						'args' => ['type', 'label'],
						'right' => ['categorie', 'creer']
					],
					'params' => [
						"label" => "Category label.", "type" => "What it classifies: product, customer, supplier, member, contact, project."
					]
				],
				'index' => [
					'description' => "Categories form a tree per type (fk_parent = parent rowid, 0 for root). Always pass 'type' to restrict to one kind of object. Search by name with sqlfilters on t.label.",
					'params' => [
						'type' => "Kind of object the category applies to: 'product', 'customer', 'supplier', 'contact', 'member', 'project', 'user', 'bank_account', 'warehouse', 'actioncomm', 'website_page', 'ticket', 'knowledgemanagement'."
					]
				],
				'get' => [
					'params' => ['include_childs' => "Set to true to also return the sub-categories (children). The parameter name 'include_childs' is fixed by the REST API."]
				],
				'getObjects' => [
					'suffix' => 'objects_list',
					'description' => "Objects tagged with one category (e.g. all products in category 12, all customers tagged 'VIP').",
					'params' => [
						'id' => "Rowid of the category.",
						'type' => "Object type to list: 'product', 'customer', 'supplier', 'contact', 'member', 'project', 'user', 'warehouse', 'actioncomm', 'ticket'.",
						'onlyids' => "1 to return only rowids (faster, use it for counting), 0 (default) for full objects."
					]
				]
			]
		];

		$this->enrichments['supplier_invoices'] = [
			'label' => 'supplier invoices (vendor bills)',
			'methods' => [
				'index' => [
					'description' => "List supplier invoices (vendor bills). Filter with sqlfilters, for example to restrict to one supplier.",
					'params' => [
						'sqlfilters' => "Universal filter, e.g. (t.fk_soc:=:2)",
						'limit' => "Rows to return."
					]
				],
				'get' => [
					'description' => "Get one supplier invoice by rowid, with its lines."
				],
				// The REST endpoint names the line fields description and pu_ht.
				'postLine' => $this->addLineMethod('supplier invoice', 'api_supplier_invoices_list', 'FactureFournisseur', ['fournisseur', 'facture', 'creer'], true, ['desc' => 'description', 'subprice' => 'pu_ht']),
				'post' => [
					'suffix' => 'create',
					'description' => "Create a DRAFT supplier invoice (vendor bill). Nothing is validated or paid: the draft is created and the user finishes it in Dolibarr. Give socid, or simply the supplier name, plus ref_supplier (the vendor's own invoice number) and the lines; a line may name its product with product_ref and the catalogue price is used. Do not call a search tool first. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewCreateSupplierInvoice',
						'required' => ['socid', 'ref_supplier'],
						'object' => 'FactureFournisseur',
						'thirdparty' => 'socid',
						'lines' => 'lines',
						'right' => ['fournisseur', 'facture', 'creer'],
						'buy' => true,
						'defaults' => ['date' => 'now']
					],
					'params' => [
						'socid' => "Rowid of the supplier, or its name: a name is resolved here, so no separate lookup call is needed.",
						'ref_supplier' => "The vendor's own invoice number, as printed on the bill.",
						'lines' => "Array of lines: desc, qty, subprice (unit price excl. tax), tva_tx (VAT rate). A line may instead give product_ref, and the catalogue product and price are used."
					]
				]
			]
		];

		$this->enrichments['supplier_orders'] = [
			'label' => 'supplier order (purchase order)',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => "Create a DRAFT supplier order (purchase order). Nothing is validated or sent: the draft is created and the user finishes it in Dolibarr, or asks for the next step explicitly. The third party is mandatory: give socid, or simply the third party name as the user said it (it is resolved here, do not call a search tool first); ask the user only when no third party is named at all. Give the lines; a line may name its product with product_ref and the catalogue price is used. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewCreateSupplierOrder',
						'required' => ['socid'],
						'object' => 'CommandeFournisseur',
						'thirdparty' => 'socid',
						'lines' => 'lines',
						'right' => ['fournisseur', 'commande', 'creer'],
						'buy' => true,
						'defaults' => ['date' => 'now']
					],
					'params' => [
						'socid' => "Rowid of the third party, or its name: a name is resolved here, so no separate lookup call is needed.",
						'lines' => "Array of lines: desc, qty, subprice (unit price excl. tax), tva_tx (VAT rate). A line may instead give product_ref, and the catalogue product and price are used."
					]
				],
				'index' => [
					'description' => "List supplier order (purchase order). Filter with sqlfilters.",
					'params' => ['sqlfilters' => "Universal filter, e.g. (t.fk_soc:=:2)", 'limit' => "Rows to return."]
				],
				'get' => ['description' => "Get one record by rowid, with its lines."],
				'postLine' => $this->addLineMethod('supplier order', 'api_supplier_orders_list', 'CommandeFournisseur', ['fournisseur', 'commande', 'creer'], true),
				'put' => [
					'suffix' => 'update',
					'description' => 'Update selected supplier order header fields after confirmation. Identify the order by id or exact ref. Omitted fields are preserved. Status, totals, supplier and lines cannot be changed.',
					'write' => [
						'parent' => 'id', 'ref' => 'ref', 'object' => 'CommandeFournisseur',
						'right' => ['fournisseur', 'commande', 'creer'], 'read_right' => ['fournisseur', 'commande', 'lire'],
						'access' => ['fournisseur', 'commande_fournisseur', 'commande'],
						'preview' => 'AIPreviewUpdateRecord',
						'update' => [
							'ref_supplier' => ['type' => 'string'], 'note_private' => ['type' => 'string'], 'note_public' => ['type' => 'string'],
							'delivery_date' => ['type' => ['integer', 'null'], 'minimum' => 0, 'description' => 'Unix timestamp, or null to clear.'],
							'fk_project' => ['type' => ['integer', 'null'], 'minimum' => 1, 'description' => 'Rowid of the project, or null to unlink it.'],
							'array_options' => ['type' => 'object', 'description' => 'Extra fields by their options_ code, e.g. {"options_mycode": "value"}.']
						],
						'preview_labels' => ['ref_supplier' => 'RefSupplier', 'note_private' => 'NotePrivate', 'note_public' => 'NotePublic', 'delivery_date' => 'DeliveryDate', 'fk_project' => 'Project'],
						'preview_dates' => ['delivery_date'],
						// Named in the confirmation by ref and label, not by rowid.
						'preview_objects' => ['fk_project' => 'Project'],
						// Extra fields the order really has: a misspelt one is refused
						// before the confirmation rather than silently ignored.
						'extrafields' => 'commande_fournisseur',
						'references' => ['fk_project' => ['projet', 'projet', '']]
					]
				]
			]
		];

		$this->enrichments['supplier_proposals'] = [
			'label' => 'supplier proposal (price request)',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => "Create a DRAFT supplier proposal (price request). Nothing is validated or sent: the draft is created and the user finishes it in Dolibarr, or asks for the next step explicitly. Give socid, or simply the third party name, and the lines; a line may name its product with product_ref and the catalogue price is used. Do not call a search tool first. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewCreateSupplierProposal',
						'required' => ['socid'],
						'object' => 'SupplierProposal',
						'thirdparty' => 'socid',
						'lines' => 'lines',
						'right' => ['supplier_proposal', 'creer'],
						'buy' => true,
						'defaults' => ['date' => 'now']
					],
					'params' => [
						'socid' => "Rowid of the third party, or its name: a name is resolved here, so no separate lookup call is needed.",
						'lines' => "Array of lines: desc, qty, subprice (unit price excl. tax), tva_tx (VAT rate). A line may instead give product_ref, and the catalogue product and price are used."
					]
				],
				'index' => [
					'description' => "List supplier proposal (price request). Filter with sqlfilters.",
					'params' => ['sqlfilters' => "Universal filter, e.g. (t.fk_soc:=:2)", 'limit' => "Rows to return."]
				],
				'get' => ['description' => "Get one record by rowid, with its lines."],
				'postLine' => $this->addLineMethod('supplier proposal', 'api_supplier_proposals_list', 'SupplierProposal', ['supplier_proposal', 'creer'], true)
			]
		];

		$this->enrichments['invoices'] = [
			'label' => 'customer invoices',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => "Create a DRAFT customer invoice. Nothing is validated, sent or paid: the draft is created and the user finishes it in Dolibarr, or asks for the next step explicitly. Give socid, or simply the third party name, and the lines; a line may name its product with product_ref and the catalogue price is used. Do not call a search tool first. The user confirms before anything is written.",
					'write' => [
						// Hand-written: a preview cannot be derived from a method
						// signature, and it is the sentence a human approves.
						'preview' => 'AIPreviewCreateInvoice',
						'required' => ['socid'],
						'object' => 'Facture',
						'thirdparty' => 'socid',
						'lines' => 'lines',
						// The REST method checks this itself, but declaring it
						// here refuses the call before a confirmation is issued:
						// no preview for a write the user could never perform.
						'right' => ['facture', 'creer']
					],
					'params' => [
						'socid' => "Rowid of the customer, or its name: a name is resolved here, so no separate lookup call is needed.",
						'lines' => "Array of lines: desc, qty, subprice (unit price excl. tax), tva_tx (VAT rate). A line may instead give product_ref, and the catalogue product and price are used."
					]
				],
				'index' => [
					'default_properties' => 'id,ref,socid,date,date_lim_reglement,total_ht,total_ttc,paye,remaintopay',
					'description' => "Use 'status' for the usual questions (unpaid, paid, drafts). Oldest first: sortfield 't.datef' with sortorder 'ASC' (due-date order: 't.date_lim_reglement'). Set withLines=false for lists: lines are large and rarely needed. Amounts: total_ht (excl. tax), total_tva, total_ttc (incl. tax), paye (1=paid). Dates are unix timestamps: date (invoice date), date_lim_reglement (due date). Overdue unpaid invoices: status='unpaid' plus sqlfilters \"(t.date_lim_reglement:<:'YYYY-MM-DD')\". Useful sqlfilters fields: t.ref, t.datef, t.total_ttc, t.fk_soc, t.type (0=standard, 1=replacement, 2=credit note, 3=deposit, 4=proforma), t.fk_statut (0=draft, 1=validated, 2=paid, 3=abandoned).",
					'params' => [
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5'). Look the rowid up with api_thirdparties_list first when only a name is known.",
						'status' => "One of 'draft', 'unpaid' (validated and not fully paid), 'paid', 'cancelled'. Empty = all.",
						'withLines' => "false to omit invoice lines from each record (recommended for lists).",
						'loadlinkedobjects' => "1 to include linked objects (orders, proposals, shipments) — slower, default 0.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}; use it to know how many invoices match."
					]
				],
				'get' => [
					'description' => "One invoice with its lines (product, qty, unit price, VAT rate, line totals), status, remaining amount to pay and linked contacts. Look the rowid up with api_invoices_list (sqlfilters on t.ref) when only the reference is known.",
					'params' => [
						'contact_list' => "0 = no contacts, 1 (default) = contact rowids, 2 = full contact records.",
						'withLines' => "false to omit the lines."
					]
				],
				'getByRef' => [
					'suffix' => 'get_by_ref',
					'description' => "One invoice by its exact reference (e.g. 'FA2401-0001').",
					'params' => ['ref' => "Exact invoice reference.", 'contact_list' => "0 = no contacts, 1 (default) = contact rowids, 2 = full contact records."]
				],
				'getPayments' => [
					'suffix' => 'payments_list',
					'description' => "Payments already recorded on one invoice: amount, date, payment mode, bank reference.",
					'params' => ['id' => "Rowid of the invoice."]
				],
				'postLine' => $this->addLineMethod('customer invoice', 'api_invoices_get_by_ref', 'Facture', ['facture', 'creer'])
			]
		];

		// Sale orders
		$descriptioncreate = "Create a DRAFT sale order (customer order). Nothing is validated or sent: the draft is created and the user finishes it in Dolibarr, or asks for the next step explicitly. The third party is mandatory: give socid, or simply the third party name as the user said it (it is resolved here, do not call a search tool first); ask the user only when no third party is named at all. Give the lines; a line may name its product with product_ref and the catalogue price is used. The user confirms before anything is written.";

		$descriptionlist = "Return a list of sale orders with properties: id, ref, socid, date_commande, delivery_date, total_ht, total_ttc, statut. ";
		$descriptionlist .= "Useful sqlfilters fields with type:\n";
		$descriptionlist .= "- t.ref : string (sale order ref)\n";
		$descriptionlist .= "- t.fk_soc : integer (thirdparty ID),\n";
		$descriptionlist .= "- t.date_commande : timestamp (order date),\n";
		$descriptionlist .= "- t.total_ht : float, t.total_ttc : float,\n";
		$descriptionlist .= "- t.fk_statut : integer enum (status ID) among -1=cancelled, 0=draft, 1=validated (open), 2=shipment in progress, 3=closed (delivered / billed).";

		$descriptionfetch = "Fetch one order with its lines (product, qty, unit price, discount, line totals), status and dates. Fetch is done from ID.";

		$descriptionfetchref = "Fetch one order with its lines (product, qty, unit price, discount, line totals), status and dates. Fetch is done from exact REF (e.g. 'CO2401-0001').";

		$this->enrichments['orders'] = [
			'label' => 'sale orders',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => $descriptioncreate,
					'write' => [
						'preview' => 'AIPreviewCreateOrder',
						'required' => ['socid'],
						'object' => 'Commande',
						'thirdparty' => 'socid',
						'lines' => 'lines',
						'right' => ['commande', 'creer'],
						// Commande::create() refuses without one, and a draft
						// created today is what the user means.
						'defaults' => ['date' => 'now']
					],
					'params' => [
						'socid' => "Rowid of the customer, or its name: a name is resolved here, so a separate lookup call can be avoided here.",
						'lines' => "Array of order lines: desc, qty, subprice (unit price excl. tax), tva_tx (VAT rate). A line may instead give product_ref, and the catalogue product and price are used."
					]
				],
				'index' => [
					'default_properties' => 'id,ref,socid,date_commande,delivery_date,total_ht,total_ttc,status',
					'description' => $descriptionlist,
					'params' => [
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5'). Look the rowid up with api_thirdparties_list first when only a name is known.",
						'loadlinkedobjects' => "1 to include linked objects (proposals, shipments, invoices) — slower, default 0.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}; use it to know how many orders match."
					]
				],
				'get' => [
					'description' => $descriptionfetch,
					'params' => ['contact_list' => "0 = no need of contacts, 1 (default) = need contact rowids, 2 = need full contact records."]
				],
				'getByRef' => [
					'suffix' => 'get_by_ref',
					'description' => $descriptionfetchref,
					'params' => ['ref' => "Exact order reference.", 'contact_list' => "0 = no need of contacts, 1 (default) = need contact rowids, 2 = need full contact records."]
				],
				'postLine' => $this->addLineMethod('sale order', 'api_orders_get_by_ref', 'Commande', ['commande', 'creer'])
			]
		];

		$this->enrichments['proposals'] = [
			'label' => 'commercial proposals (quotes / devis)',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => "Create a DRAFT commercial proposal (quote). Nothing is validated or sent: the draft is created and the user finishes it in Dolibarr, or asks for the next step explicitly. Give socid, or simply the third party name, and the lines; a line may name its product with product_ref and the catalogue price is used. Do not call a search tool first. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewCreateProposal',
						'required' => ['socid'],
						'object' => 'Propal',
						'thirdparty' => 'socid',
						'lines' => 'lines',
						'right' => ['propal', 'creer'],
						'defaults' => ['date' => 'now']
					],
					'params' => [
						'socid' => "Rowid of the third party, or its name: a name is resolved here, so no separate lookup call is needed.",
						'lines' => "Array of lines: desc, qty, subprice (unit price excl. tax), tva_tx (VAT rate). A line may instead give product_ref, and the catalogue product and price are used."
					]
				],
				'index' => [
					'default_properties' => 'id,ref,socid,datep,fin_validite,total_ht,total_ttc,fk_statut',
					'description' => "Statuses (t.fk_statut): 0=draft, 1=validated (open, awaiting answer), 2=signed/accepted, 3=not signed/refused, 4=billed. Dates are unix timestamps: datep (proposal date), fin_validite (validity end — expired open proposals: fk_statut=1 plus sqlfilters \"(t.fin_validite:<:'YYYY-MM-DD')\"). Useful sqlfilters fields: t.ref, t.datep, t.total_ht, t.total_ttc, t.fk_soc, t.fk_statut.",
					'params' => [
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5'). Look the rowid up with api_thirdparties_list first when only a name is known.",
						'loadlinkedobjects' => "1 to include linked objects (orders, invoices) — slower, default 0.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}; use it to know how many proposals match."
					]
				],
				'get' => [
					'description' => "One proposal with its lines (product, qty, unit price, discount, line totals), status and validity date.",
					'params' => ['contact_list' => "0 = no contacts, 1 (default) = contact rowids, 2 = full contact records."]
				],
				'getByRef' => [
					'suffix' => 'get_by_ref',
					'description' => "One proposal by its exact reference (e.g. 'PR2401-0001').",
					'params' => ['ref' => "Exact proposal reference.", 'contact_list' => "0 = no contacts, 1 (default) = contact rowids, 2 = full contact records."]
				],
				'postLine' => $this->addLineMethod('commercial proposal', 'api_proposals_get_by_ref', 'Propal', ['propal', 'creer'])
			]
		];

		$this->enrichments['tickets'] = [
			'label' => 'support tickets',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => "Create a support ticket. The user confirms before anything is written. subject and message are mandatory; socid is optional (a ticket may belong to no third party).",
					'write' => [
						'preview' => 'AIPreviewCreateTicket',
						'required' => ['subject', 'message'],
						'object' => 'Ticket',
						'thirdparty' => 'socid',
						'right' => ['ticket', 'write'],
						'args' => ['subject', 'message'],
						// The ticket API validates 'socid' but the object persists
						// 'fk_soc'; the status column reads 'status'.
						'copy' => ['socid' => 'fk_soc'],
						'rename' => ['fk_statut' => 'status'],
						// Silent by default: the assistant must not turn a
						// creation the human approved into an emailed
						// notification nobody saw coming.
						'defaults' => ['notify_tiers_at_create' => 0],
					],
					'params' => [
						'subject' => "Ticket subject (required).",
						'message' => "First message describing the request (required).",
						'socid' => "Rowid of the customer the ticket belongs to, or its name as said (resolved here). Omit or 0 for a ticket not linked to a third party.",
						'fk_user_assign' => "Rowid of the user to assign the ticket to.",
						'type_code' => "Nature of the request, e.g. COM=commercial question, ISSUE=incident, HELP=request, OTHER.",
						'category_code' => "Ticket group/category code.",
						'severity_code' => "Severity: LOW, NORMAL, HIGH or BLOCKING.",
						'fk_project' => "Rowid of a project the ticket relates to.",
						'notify_tiers_at_create' => "1 to notify the third party by email on creation, 0 (default) silent."
					]
				],
				'postNewMessage' => [
					'suffix' => 'reply',
					'description' => "Post a message on an existing support ticket, given by its rowid or its ref. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewReplyTicket',
						'required' => ['message'],
						'object' => 'Ticket',
						'label' => 'ticket',
						'right' => ['ticket', 'write'],
						'args' => ['track_id', 'message'],
						// The ticket by rowid or ref; its tracking id is filled in here,
						// so the model never has to read or repeat that credential.
						'target' => 'id',
						'lookup' => 'ref',
						'target_or' => 'track_id',
						'track_from_target' => true,
					],
					'params' => [
						'id' => "The ticket: rowid, or its ref (e.g. TS2401-0001).",
						'track_id' => "Public tracking id of the ticket, only when neither the rowid nor the ref is known.",
						'message' => "Message text to post on the ticket (required).",
						'private' => "1 for an internal note the customer never sees, 0 (default) for a public message."
					]
				],
				'put' => [
					'suffix' => 'update',
					'description' => "Update fields of an existing support ticket: subject, status, severity, type, category, project, third party. Only the fields given are changed. To close, cancel or assign it use api_tickets_close / api_tickets_assign. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewUpdateTicket',
						'required' => ['id'],
						'object' => 'Ticket',
						'label' => 'ticket',
						'right' => ['ticket', 'write'],
						'args' => ['id'],
						'target' => 'id',
						'lookup' => 'ref',
						// Closing and assigning go through their own tools, which run
						// what the card runs (closing date, triggers, notifications).
						'refuse_values' => [
							'fk_statut' => ['values' => [8, 9], 'tool' => 'api_tickets_close'],
							'status' => ['values' => [8, 9], 'tool' => 'api_tickets_close'],
							'fk_user_assign' => ['values' => null, 'tool' => 'api_tickets_assign'],
						],
						// Same name mismatch as the create: the API validates
						// 'socid' while Ticket::update persists 'fk_soc', and
						// the status column reads 'status' (fk_statut is a
						// read-only backward-compatible alias).
						'copy' => ['socid' => 'fk_soc'],
						'rename' => ['fk_statut' => 'status'],
					],
					'params' => [
						'id' => "The ticket: rowid, or its ref (required).",
						'subject' => "New subject.",
						'socid' => "Rowid of the customer to link the ticket to, or its name as said.",
						'fk_statut' => "New status: 0=not read, 1=read, 3=in progress, 5=needs more info, 7=waiting. To close or cancel use api_tickets_close, to assign use api_tickets_assign.",
						'severity_code' => "New severity: LOW, NORMAL, HIGH or BLOCKING.",
						'type_code' => "New nature: COM, ISSUE, HELP or OTHER.",
						'category_code' => "New group/category code.",
						'fk_project' => "Rowid of a project to relate the ticket to."
					]
				],
				'postContact' => [
					'suffix' => 'add_contact',
					'description' => "Link a contact to a support ticket. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewAddTicketContact',
						'required' => ['id', 'contactid', 'type'],
						'object' => 'Ticket',
						'label' => 'ticket',
						'right' => ['ticket', 'write'],
						'args' => ['contactid', 'id'],
						'target' => 'id',
						'lookup' => 'ref',
						'contact' => 'ticket',
					],
					'params' => [
						'id' => "The ticket: rowid, or its ref (required).",
						'contactid' => "Rowid of the contact (a person of a third party), or of the user when source is 'internal' (required).",
						'type' => "Role on the ticket (required): SUPPORTCLI (customer contact following it) or CONTRIBUTOR for a contact; SUPPORTTEC (in charge) or CONTRIBUTOR for a user.",
						'source' => "'external' (default) for a third-party contact, 'internal' for a Dolibarr user."
					]
				],
				'deleteContact' => [
					'suffix' => 'remove_contact',
					'description' => "Unlink a contact from a support ticket. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewRemoveTicketContact',
						'required' => ['id', 'contactid', 'type'],
						'object' => 'Ticket',
						'label' => 'ticket',
						'right' => ['ticket', 'write'],
						'args' => ['contactid', 'id'],
						'target' => 'id',
						'lookup' => 'ref',
						'contact' => 'ticket',
					],
					'params' => [
						'id' => "The ticket: rowid, or its ref (required).",
						'contactid' => "Rowid of the contact, or of the user when source is 'internal' (required).",
						'type' => "Role to remove (required): SUPPORTCLI, CONTRIBUTOR or SUPPORTTEC.",
						'source' => "'external' (default) or 'internal'."
					]
				],
				'close' => [
					'suffix' => 'close',
					'description' => "Close a support ticket, or cancel it (cancel: 1), as the Close and Abandon buttons of the card do: closing date, progress and notifications included. Give 'id' (rowid, or its ref). The user confirms before anything is written.",
					'write' => [
						'kind' => 'action',
						'preview' => 'AIPreviewCloseTicket',
						'preview_cancel' => 'AIPreviewCancelTicket',
						'object' => 'Ticket',
						'label' => 'ticket',
						'target' => 'id',
						'lookup' => 'ref',
						// Every status but closed (8) and canceled (9).
						'states' => [0, 1, 2, 3, 5, 6, 7],
						'state_error' => 'AIWriteTicketClosed',
						'pass' => ['cancel'],
						'right' => ['ticket', 'write'],
					],
					'params' => [
						'id' => "The ticket: rowid, or its ref.",
						'cancel' => "1 to cancel (abandon) the ticket instead of closing it."
					]
				],
				'assign' => [
					'suffix' => 'assign',
					'description' => "Assign a support ticket to a user, or unassign it (fk_user_assign: 0), as the card does: the status follows and the user is notified. Give 'id' (rowid, or its ref) and fk_user_assign (rowid of the user, or 'me'). The user confirms before anything is written.",
					'write' => [
						'kind' => 'action',
						'preview' => 'AIPreviewAssignTicket',
						'preview_unassign' => 'AIPreviewUnassignTicket',
						'object' => 'Ticket',
						'label' => 'ticket',
						'target' => 'id',
						'lookup' => 'ref',
						'states' => [0, 1, 2, 3, 5, 6, 7],
						'state_error' => 'AIWriteTicketClosed',
						'pass' => ['fk_user_assign'],
						'right' => ['ticket', 'write'],
					],
					'params' => [
						'id' => "The ticket: rowid, or its ref.",
						'fk_user_assign' => "Rowid of the user to assign it to, 'me' for yourself, or 0 to unassign."
					]
				],
				'index' => [
					// track_id stays out of list results on purpose: it is the
					// bearer credential of the public ticket interface, and api_tickets_get
					// still returns it when the reply flow needs it.
					'default_properties' => 'id,ref,subject,fk_soc,fk_statut,severity_code,type_code,datec',
					'description' => "Statuses (t.fk_statut): 0=not read, 1=read, 2=assigned, 3=in progress, 5=needs more info, 7=waiting, 8=closed, 9=canceled. Open tickets = fk_statut < 8. Severity in severity_code (LOW, NORMAL, HIGH, BLOCKING), nature in type_code (COM=commercial, ISSUE=incident, ...). Useful sqlfilters fields: t.subject, t.fk_statut, t.severity_code, t.type_code, t.datec (creation), t.fk_user_assign (assigned user rowid).",
					'params' => [
						'socid' => "Third-party rowid to restrict tickets to one customer (0 = all).",
						'loadcontacts' => "1 to include linked contacts per ticket, 0 (default, faster) without.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One ticket with its subject, full message, status, severity, assigned user and linked third party. The public tracking id is in track_id."
				],
				'getByTrackId' => [
					'suffix' => 'get_by_track_id',
					'description' => "One ticket by its public tracking id (the hash customers receive by email).",
					'params' => ['track_id' => "Public tracking id of the ticket."]
				],
				'getByRef' => [
					'suffix' => 'get_by_ref',
					'description' => "One ticket by its exact internal reference (e.g. 'TS2401-0001').",
					'params' => ['ref' => "Exact ticket reference."]
				]
			]
		];

		$this->enrichments['projects'] = [
			'label' => 'projects (including opportunities/leads)',
			'methods' => [
				'index' => [
					'default_properties' => 'id,ref,title,fk_soc,public,date_start,date_end,fk_statut,opp_status,opp_amount',
					'description' => "Statuses (t.fk_statut): 0=draft, 1=open, 2=closed. A project used as a sales opportunity carries opp_status (pipeline step rowid), opp_percent (probability) and opp_amount. Dates are unix timestamps: date_start (dateo), date_end (datee). Useful sqlfilters fields: t.ref, t.title, t.fk_soc, t.fk_statut, t.dateo, t.datee, t.public (1=visible to everyone). Tasks of a project: use api_tasks_list with sqlfilters \"(t.fk_projet:=:'ID')\".",
					'params' => [
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5').",
						'category' => "Rowid of a project category (tag) to restrict the list to.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One project with its dates, status, budget, opportunity data and linked third party."
				],
				'put' => $this->projectUpdateMethod(),
				'getByRef' => [
					'suffix' => 'get_by_ref',
					'description' => "One project by its exact reference (e.g. 'PJ2401-0001').",
					'params' => ['ref' => "Exact project reference."]
				]
			]
		];

		$this->enrichments['tasks'] = [
			'label' => 'project tasks',
			'methods' => [
				'index' => [
					'default_properties' => 'id,ref,label,fk_projet,progress,planned_workload,duration_effective,date_start,date_end',
					'description' => "Tasks belong to a project (fk_projet — filter one project with sqlfilters \"(t.fk_projet:=:'ID')\"). progress is a percentage (0-100); planned_workload and duration_effective (time already spent) are in SECONDS — divide by 3600 for hours. fk_task_parent > 0 for subtasks. Useful sqlfilters fields: t.label, t.fk_projet, t.progress, t.dateo (start), t.datee (end).",
					'params' => [
						'includetimespent' => "1 to also load the time-spent summary per task (slower).",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One task with its project, planned workload, progress and dates (durations in seconds).",
					'params' => ['includetimespent' => "1 to include the detail of time spent records."]
				],
				'getTimespent' => [
					'suffix' => 'timespent_list',
					'description' => "Time records logged on one task: date, duration in seconds, user and note. Sum task_duration for the total.",
					'params' => ['id' => "Rowid of the task."]
				],
				'put' => [
					'suffix' => 'update',
					'description' => "Update fields of an existing task: label, description, progress percent, planned workload, dates. Only the fields given are changed. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewChangeTask',
						'required' => ['id'],
						'object' => 'Task',
						'label' => 'task',
						'right' => ['projet', 'creer'],
						'args' => ['id'],
						'target' => 'id',
						'lookup' => 'ref',
						'dates' => ['date_start', 'date_end'],
						'hours' => ['planned_workload'],
						'trim_fields' => ['label', 'description'],
					],
					'params' => [
						'id' => "The task: rowid, or its ref (required).",
						'label' => "New label.",
						'description' => "New description.",
						'progress' => "New progress percent 0-100.",
						'planned_workload' => "New planned workload in hours.",
						'date_start' => "New start date YYYY-MM-DD.",
						'date_end' => "New end date YYYY-MM-DD."
					]
				],
				'addTimeSpent' => [
					'suffix' => 'add_timespent',
					'description' => "Log a time-spent record on a task: date, duration in hours, optional note and a new progress percent. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewAddTimespent',
						'required' => ['id', 'date', 'duration'],
						'object' => 'Task',
						'label' => 'task',
						'right' => ['projet', 'creer'],
						'target' => 'id',
						'lookup' => 'ref',
						'args' => ['duration', 'date', 'id'],
						// No 'dates' entry: the REST method parses the text
						// itself — a timestamp would be re-parsed into garbage.
						// It is checked as text instead.
						'text_dates' => ['date'],
						'hours' => ['duration'],
					],
					'params' => [
						'id' => "The task: rowid, or its ref (required).",
						'date' => "Date of the work YYYY-MM-DD or 'today' (required).",
						'duration' => "Duration in hours, e.g. 1.5 (required).",
						'note' => "Optional note for the record.",
						'user_id' => "Rowid of the user who did the work, or 'me' for yourself. Omit to use the acting user.",
						'progress' => "New task progress percent 0-100 (optional)."
					]
				]
			]
		];

		$this->enrichments['agendaevents'] = [
			'label' => 'agenda / calendar events (meetings, calls)',
			'methods' => [
				'index' => [
					'default_properties' => 'id,ref,label,type_code,datep,datef,fulldayevent,percentage,fk_soc,userownerid',
					'description' => "Default sortfield is t.id — pass sortfield 't.datep' with sortorder 'ASC' for chronological order. Nature in type_code (AC_RDV=meeting, AC_TEL=phone call, AC_EMAIL=email, AC_OTH=other, AC_OTH_AUTO=automatic log). percentage: -1 = plain event, 0-99 = to-do in progress, 100 = done. Dates are unix timestamps: datep (start), datef (end). Upcoming events: sqlfilters \"(t.datep:>=:'YYYY-MM-DD')\". Other useful fields: t.label, t.fk_soc, t.fk_element/t.elementtype (linked business object).",
					'params' => [
						'user_ids' => "Comma-separated user rowids to restrict to events owned by these users (e.g. '1,3').",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One event with its type, start/end dates, owner, linked third party/contact and note."
				]
			]
		];

		$this->enrichments['interventions'] = [
			'label' => 'field service interventions',
			'methods' => [
				'index' => [
					'default_properties' => 'id,ref,socid,fk_statut,description,datec,duration',
					'description' => "Statuses (t.fk_statut): 0=draft, 1=validated, 2=billed, 3=done/closed. duration is in SECONDS (divide by 3600 for hours). Useful sqlfilters fields: t.ref, t.fk_soc, t.fk_statut, t.datec, t.description.",
					'params' => [
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5').",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One intervention with its lines (each line = one on-site work entry with date, duration in seconds and description)."
				]
			]
		];

		$this->enrichments['contracts'] = [
			'label' => 'contracts (recurring services)',
			'methods' => [
				'index' => [
					'default_properties' => 'id,ref,socid,date_contrat,statut',
					'description' => "Contract statuses (t.statut): 0=draft, 1=validated. What matters is usually the LINE status (each line is one service): 0=inactive/draft, 4=active/running, 5=closed. Set withLines=false for lists (lines are large), then read one contract with api_contracts_get or its lines with api_contracts_lines_list. Useful sqlfilters fields: t.ref, t.fk_soc, t.statut, t.date_contrat.",
					'params' => [
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5').",
						'withLines' => "false to omit the service lines from each record (recommended for lists).",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One contract with its service lines: per line fk_product, description, date_start/date_end (unix timestamps) and statut (0=inactive, 4=active, 5=closed).",
					'params' => ['withLines' => "false to omit the lines."]
				],
				'getLines' => [
					'suffix' => 'lines_list',
					'description' => "Service lines of one contract, with their own pagination and sqlfilters (fields prefixed 'd.', e.g. \"(d.statut:=:'4')\" for active services).",
					'params' => ['id' => "Rowid of the contract."]
				]
			]
		];

		$this->enrichments['members'] = [
			'label' => 'foundation/association members',
			'methods' => [
				'index' => [
					'default_properties' => 'id,ref,firstname,lastname,societe,email,typeid,statut,datefin',
					'description' => "Statuses (t.statut): -1=draft, 1=validated (member), 0=membership terminated (resiliated), -2=excluded. Whether the subscription is up to date is in datefin (unix timestamp of the paid-up end date): late members = statut 1 plus sqlfilters \"(t.datefin:<:'YYYY-MM-DD')\" (or datefin null). Useful sqlfilters fields: t.firstname, t.lastname, t.societe, t.email, t.statut, t.datefin.",
					'params' => [
						'typeid' => "Rowid of a member type to restrict the list to.",
						'category' => "Rowid of a member category (tag) to restrict the list to.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One member with identity, member type, status, linked third party and paid-up end date (datefin)."
				],
				'getSubscriptions' => [
					'suffix' => 'subscriptions_list',
					'description' => "Subscription (membership fee) history of one member: period start/end and amount per payment.",
					'params' => ['id' => "Rowid of the member."]
				]
			]
		];

		$this->enrichments['subscriptions'] = [
			'label' => 'subscriptions',
			'methods' => [
				'index' => [
					'description' => "All membership subscriptions (with fee payments or not) across members: fk_adherent (member rowid), dateh (period start), datef (period end), amount. NB: the default sortfield here is 'dateadh' WITHOUT the 't.' prefix (API quirk). To get the fees of one member prefer api_members_subscriptions_list.",
					'params' => [
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One subscription payment with its member, period and amount."
				]
			]
		];

		$this->enrichments['stockmovements'] = [
			'label' => 'stock movements (in/out/transfer history)',
			'methods' => [
				'index' => [
					'default_properties' => 'id,product_id,warehouse_id,qty,date,type,label,inventorycode',
					'description' => "History of physical stock changes; each movement carries product_id, warehouse_id, qty (SIGNED: positive=in, negative=out), date and label. Movements of one product: sqlfilters \"(t.fk_product:=:'ID')\"; of one warehouse: \"(t.fk_entrepot:=:'ID')\"; over a period: \"(t.datem:>=:'YYYY-MM-DD')\". The source document (reception, shipment, inventory...) is in origintype/fk_origin when set.",
					'params' => [
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => []
			]
		];

		$this->enrichments['warehouses'] = [
			'label' => 'warehouses',
			'methods' => [
				'index' => [
					'description' => "Warehouses/locations: ref (name), lieu (short location), statut (1=open, 0=closed). Search by name with sqlfilters on t.ref. Per-product stock by warehouse is NOT here — use api_products_get with includestockdata=1.",
					'params' => [
						'category' => "Rowid of a warehouse category (tag) to restrict the list to.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One warehouse with its address, status and description."
				]
			]
		];

		$this->enrichments['shipments'] = [
			'label' => 'customer shipments (expéditions, goods sent)',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => "Create a DRAFT customer shipment (delivery note) WITHOUT a source order, as the shipment card allows when standalone shipments are enabled. Nothing is validated and no stock moves: like the lines added on the shipment card, these lines carry no warehouse, and a product tracked by lot or serial number cannot be shipped this way. The customer is mandatory: give socid, or simply the customer name. Each line names a catalogue product (product_ref or fk_product) or carries a free desc, with a qty and an optional unit. To ship an existing order, do not use this tool: tell the user to create the shipment from the order card.",
					'write' => [
						'preview' => 'AIPreviewCreateShipment',
						'required' => ['socid'],
						'object' => 'Expedition',
						'thirdparty' => 'socid',
						'lines' => 'lines',
						'right' => ['expedition', 'creer'],
						'kind' => 'shipment',
						'line_fields' => [
							'product_ref' => ['type' => 'string', 'description' => 'Ref or label of a catalogue product.'],
							'desc' => ['type' => 'string', 'description' => 'Free text, for a line with no product.'],
							'qty' => ['type' => 'number'],
							'unit' => ['type' => 'string', 'description' => 'Unit as said, e.g. pieces, kg. Omit to use the product unit.']
						]
					],
					'params' => [
						'socid' => "Rowid of the customer, or its name: a name is resolved here, so no separate lookup call is needed.",
						'ref_customer' => "Customer's own reference for this delivery (optional).",
						'date_delivery' => "Planned delivery date, YYYY-MM-DD (optional).",
						'lines' => "Array of lines: product_ref (or fk_product) or desc for a free line, qty, unit (optional)."
					]
				],
				'index' => [
					'default_properties' => 'id,ref,socid,ref_customer,date_delivery,date_shipping,statut',
					'description' => "Statuses (t.fk_statut): 0=draft (reference '(PROVnn)' until validated), 1=validated (goods left), 2=closed (billed / processed). Dates are unix timestamps: date_shipping (sent), date_delivery (planned delivery). Useful sqlfilters fields: t.ref, t.ref_customer, t.fk_soc, t.fk_statut, t.date_delivery.",
					'params' => [
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5'). Look the rowid up with api_thirdparties_list first when only a name is known.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}; use it to know how many shipments match."
					]
				],
				'get' => [
					'description' => "One shipment: customer, source order, dates, status, tracking number; the lines are in api_shipments_get_lines."
				],
				'getLines' => [
					'suffix' => 'get_lines',
					'description' => "Lines of a shipment: product, quantity shipped, batch/lot when the product is tracked.",
					'params' => ['id' => "Rowid of the shipment."]
				]
			]
		];

		$this->enrichments['receptions'] = [
			'label' => 'supplier receptions (réceptions, goods received)',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => "Create a DRAFT supplier reception (goods received, typically from a supplier delivery note) WITHOUT a supplier order, as the reception card allows when standalone receptions are enabled. Nothing is validated: stock does not move until the user validates the reception in Dolibarr. The supplier is mandatory: give socid, or simply the supplier name. Each line names a catalogue product (product_ref or fk_product) and a qty; optional: warehouse (name or rowid, else the default warehouse), cost_price (unit purchase price excl. tax, else the supplier price of the product), batch with eatby/sellby for a lot-tracked product. To receive an existing supplier order, do not use this tool: tell the user to create the reception from the order card.",
					'write' => [
						'preview' => 'AIPreviewCreateReception',
						'required' => ['socid'],
						'object' => 'Reception',
						'thirdparty' => 'socid',
						'lines' => 'lines',
						'right' => ['reception', 'creer'],
						'buy' => true,
						'kind' => 'reception',
						'line_fields' => [
							'product_ref' => ['type' => 'string', 'description' => 'Ref or label of a catalogue product. Mandatory: a reception line moves the stock of a product.'],
							'qty' => ['type' => 'number'],
							'warehouse' => ['type' => 'string', 'description' => 'Warehouse receiving the goods, by name or rowid. Omit to use the default warehouse.'],
							'cost_price' => ['type' => 'number', 'description' => 'Unit purchase price excl. tax. Omit to use the supplier price of the product.'],
							'batch' => ['type' => 'string', 'description' => 'Lot or serial number, for a lot-tracked product.'],
							'eatby' => ['type' => 'string', 'description' => 'Eat-by date YYYY-MM-DD (lot-tracked product).'],
							'sellby' => ['type' => 'string', 'description' => 'Sell-by date YYYY-MM-DD (lot-tracked product).']
						]
					],
					'params' => [
						'socid' => "Rowid of the supplier, or its name: a name is resolved here, so no separate lookup call is needed.",
						'ref_supplier' => "Supplier's reference printed on the delivery note (optional).",
						'date_delivery' => "Delivery date, YYYY-MM-DD (optional).",
						'lines' => "Array of lines: product_ref (or fk_product), qty, warehouse (optional), cost_price (optional), batch/eatby/sellby (optional)."
					]
				],
				'index' => [
					'default_properties' => 'id,ref,socid,ref_supplier,date_reception,date_delivery,statut',
					'description' => "Statuses (t.fk_statut): 0=draft (reference '(PROVnn)' until validated), 1=validated (goods received into stock), 2=closed / processed. Dates are unix timestamps: date_reception (received), date_delivery (planned). Useful sqlfilters fields: t.ref, t.ref_supplier, t.fk_soc, t.fk_statut, t.date_delivery. A draft created by the chat keeps its '(PROVnn)' reference: search it by id or with sqlfilters on t.ref_supplier.",
					'params' => [
						'thirdparty_ids' => "Comma-separated supplier rowids to restrict to (e.g. '1,5'). Look the rowid up with api_thirdparties_list first when only a name is known.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}; use it to know how many receptions match."
					]
				],
				'get' => [
					'description' => "One reception: supplier, supplier reference, dates, status; the lines are in api_receptions_get_lines."
				],
				'getLines' => [
					'suffix' => 'get_lines',
					'description' => "Lines of a reception: product, quantity received, batch/lot and warehouse when tracked.",
					'params' => ['id' => "Rowid of the reception."]
				]
			]
		];

		$this->enrichments['expensereports'] = [
			'label' => 'employee expense reports (notes de frais)',
			'methods' => [
				'index' => [
					'default_properties' => 'id,ref,fk_user_author,date_debut,date_fin,total_ht,total_ttc,fk_statut',
					'description' => "Statuses (t.fk_statut): 0=draft, 2=validated (waiting approval), 4=canceled, 5=approved, 6=paid, 99=refused. The employee is fk_user_author (a USER rowid, not a third party). Period: date_debut/date_fin (unix timestamps). Useful sqlfilters fields: t.ref, t.fk_user_author, t.fk_statut, t.date_debut, t.total_ttc.",
					'params' => [
						'user_ids' => "Comma-separated user rowids to restrict to the reports of these employees (e.g. '1,3').",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One expense report with its lines: per line the date, expense type code (type_fees_code: TRA_TRIP=transport, TRA_MEAL=meal, ...), VAT and amounts."
				]
			]
		];

		// Sale orders
		$descriptioncreate = "Create a product or service in the catalog. The user must confirm before anything is written.";

		$descriptionlist = "Search products using the sqlfilters (e.g. \"(t.label:like:'%screw%')\").\n";
		$descriptionlist .= "Prefer 'properties' (e.g. 'id,ref,label,type,price,price_ttc,tva_tx,status,status_buy') and a small 'limit'.\n";

		$descriptionlist .= "Useful sqlfilters fields with type:\n";
		$descriptionlist .= "- t.ref : string (sale order ref)\n";
		$descriptionlist .= "- t.label : string (product label)\n";
		$descriptionlist .= "- t.fk_soc : integer (thirdparty ID),\n";
		$descriptionlist .= "- t.date_commande : timestamp (order date),\n";
		$descriptionlist .= "- t.price_ttc : float (default customer price), t.tva_tx : float (default vat rate),\n";
		$descriptionlist .= "- t.status : integer (status For Sale) among 0=not for sale, 1=for sale\n";
		$descriptionlist .= "- t.status_buy : integer (status For purchase) among 0=not for purchase, 1=for purchase\n";
		$descriptionlist .= "- t.stock_reel : float (qty in stock, output only when param includestockdata=1)\n";

		$descriptionfetch = "Fetch full product record (description, prices, VAT, barcode, weight/dimensions, accounting codes, optional stock and sub-products), status and dates. Fetch is done from ID.";

		$this->enrichments['products'] = [
			'label' => 'products and services catalog',
			'methods' => [
				'post' => [
					'suffix' => 'create',
					'description' => $descriptioncreate,
					'write' => [
						'preview' => 'AIPreviewCreateProduct',
						'required' => ['ref', 'label'],
						'object' => 'Product',
						'args' => ['label'],
						'right' => ['produit', 'creer']
					],
					'params' => [
						"ref" => "Internal reference.", "label" => "Product label.", "price" => "Selling price excl. tax.", "type" => "0 product, 1 service."
					]
				],
				'index' => [
					'default_properties' => 'id,ref,label,type,price,price_ttc,tva_tx,status,status_buy',
					'description' => $descriptionlist,
					'params' => [
						'mode' => "0=all (default), 1=products only, 2=services only.",
						'category' => "Rowid of a product category to restrict the list to.",
						'variant_filter' => "0=all (default), 1=products without variants, 2=parents of variants only, 3=variants only.",
						'ids_only' => "true to return only rowids (fast, use for counting).",
						'includestockdata' => "1 to add stock_reel / stock_theorique per product (slower; requires the Stock module).",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => $descriptionfetch,
					'params' => [
						'includestockdata' => "1 to load stock_reel, stock_theorique and per-warehouse stock (requires the Stock module).",
						'includesubproducts' => "true to load the kit/BOM components (sub-products).",
						'includeparentid' => "true to add fk_product_parent for a variant.",
						'includetrans' => "true to load multilingual labels/descriptions."
					]
				],
				'getByRef' => [
					'suffix' => 'get_by_ref',
					'description' => "One product by its exact reference (e.g. 'PROD-001'); same options as get.",
					'params' => ['ref' => "Exact product reference."]
				],
				'getByBarcode' => [
					'suffix' => 'get_by_barcode',
					'description' => "One product by its barcode (EAN/UPC); same options as get.",
					'params' => ['barcode' => "Barcode value as printed."]
				],
				'getPurchasePrices' => [
					'suffix' => 'purchase_prices_list',
					'description' => "Supplier prices of one product: for each supplier, the supplier reference, minimum quantity, unit purchase price and VAT. Identify the product by id, ref or barcode.",
					'params' => ['id' => "Rowid of the product (use 0 when identifying by ref or barcode).", 'ref' => "Product reference, alternative to id.", 'barcode' => "Product barcode, alternative to id."]
				],
				'getAttributes' => [
					'suffix' => 'attributes_list',
					'module' => 'variants',
					'description' => "Variant attributes (e.g. Size, Color) defined in the catalog."
				],
				'getVariants' => [
					'suffix' => 'variants_list',
					'module' => 'variants',
					'description' => "Variants of one parent product.",
					'params' => ['id' => 'Rowid of the PARENT product.', 'includestock' => "1 to add stock data on each variant."]
				],
				'getStock' => [
					'suffix' => 'stock',
					// Needs the Stock module on top of Products: when it is off
					// the tool does not exist, instead of the bare 403 the API
					// method throws.
					'module' => 'stock',
					// Declared so a caller without stock/lire neither sees the
					// tool in tools/list nor hits the API's bare 403; the
					// endpoint's own produit/lire check stays downstream.
					'right' => [['produit', 'lire'], ['stock', 'lire']],
					'description' => "Physical stock of one product: per-warehouse quantities in stock_warehouses (keyed by warehouse rowid, signed qty, with batch detail when lot tracking is on) plus the stock_reel and stock_theorique totals. Answers \"how much of product X do we have\" and \"where is it stored\". Identify the product by rowid — look it up first with api_products_list when only the ref is known.",
					'params' => [
						'id' => "Rowid of the product.",
						'selected_warehouse_id' => "Rowid of a warehouse to return its stock only; omitted for every warehouse."
					]
				]
			]
		];

		// NB: stock inventories have no REST API class in core yet (no api_inventories) —
		// they cannot be bridged until one exists.
		// The REST class documents serves three different file areas, each one
		// exposed as its own group of tools so the model never mixes them: the
		// files of business objects, the free tree of the ECM module, and the
		// public medias of the website module. 'endpoint' names the REST class
		// they all call, 'fixed_params' the arguments forced on the call.
		$uploadParams = [
			'filename' => "Name of the file to create (e.g. 'photo.jpg').",
			'filecontent' => "Content of the file. Omit to create an empty file.",
			'fileencoding' => "'base64' when filecontent is base64-encoded, empty (default) for plain text.",
			'overwriteifexists' => "1 to overwrite a file that already exists with the same name, 0 (default) to fail instead.",
			'createdirifnotexists' => "1 (default) to create the subdirectories when they do not exist, 0 to fail."
		];
		$this->enrichments['documents_objects'] = [
			'endpoint' => 'documents',
			'label' => 'documents attached to business objects',
			'methods' => [
				'getDocumentsListByElement' => [
					'suffix' => 'list',
					'description' => "List the files attached to a business object (invoice, proposal, order, third party, product, project...). Answers \"what files does this invoice have\", \"show the documents of that order\", \"is there anything attached to this third party\". Returns each file name, size, date and a download link, never the content. Give the object by id or by ref; search for it first when neither is known. A draft ref is written in parentheses, like (PROV42): pass it with them. Not for the free directory tree of the DMS (use the api_documents_manual tools) nor for the public medias of the website (use the api_documents_medias tools).",
					'params' => [
						'modulepart' => "Object type: invoice, supplier_invoice, order, supplier_order, propal (proposal), supplier_proposal, shipment, societe (third party), product, contract, ficheinter (intervention), project, project_task, ticket, expensereport, holiday, member, actioncomm (event), category, user.",
						'id' => "Rowid of the object, when known.",
						'ref' => "Reference of the object, when the id is unknown."
					]
				],
				// GET /documents/download of the same REST class: the answer carries
				// the file content base64-encoded, so keep it for small files only.
				'index' => [
					'suffix' => 'download',
					'description' => "Download one file attached to a business object and return its content base64-encoded. Give modulepart (the object type) and original_file, the path relative to that area. List the files of the object with api_documents_objects_list first to learn the path, or build it from the object (e.g. 'FA2401-0001/FA2401-0001.pdf'). Only for small files: the whole content travels in the answer.",
					'params' => [
						'modulepart' => "Object type: same values as api_documents_objects_list modulepart.",
						'original_file' => "Relative path with filename, relative to the area (e.g. 'FA2401-0001/FA2401-0001.pdf')."
					]
				],
				// POST /documents/upload of the same REST class. No 'right' is
				// declared: the API checks the access per modulepart, and no single
				// Dolibarr right covers every area (an invoice needs facture/...).
				'post' => [
					'suffix' => 'upload',
					'description' => "Attach a file to a business object. Give modulepart (the object type), the ref of the object (its directory is derived from it; for a third party or a contact use the rowid instead), and filecontent base64-encoded with fileencoding='base64'. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewUploadDocument',
						'required' => ['filename', 'modulepart', 'ref'],
						'args' => ['filename', 'modulepart']
					],
					'params' => [
						'filename' => $uploadParams['filename'],
						'modulepart' => "Object type: same values as api_documents_objects_list modulepart.",
						'ref' => "Reference of the business object the file is attached to (rowid for a third party or a contact).",
						'filecontent' => $uploadParams['filecontent'],
						'fileencoding' => $uploadParams['fileencoding'],
						'overwriteifexists' => $uploadParams['overwriteifexists']
					]
				]
			]
		];
		$this->enrichments['documents_manual'] = [
			'endpoint' => 'documents',
			'label' => 'documents of the DMS free directory tree',
			'methods' => [
				'getDocumentsListByDirectory' => [
					'suffix' => 'list',
					'module' => 'ecm',
					'fixed_params' => ['modulepart' => 'ecm'],
					'description' => "List the files and subdirectories of a directory of the DMS (ECM module) manual area, the free directory tree where users file documents not linked to a business object. Answers \"what is in the Contracts folder\", \"find the file named agreement\". Returns for each entry its type (dir or file), its path to give to api_documents_manual_download, size, date and a download link, never the content. Start at the root (empty subdir), then go down.",
					'params' => [
						'subdir' => "Directory to list, relative to the root of the DMS manual tree (e.g. 'Contracts/2024'). Empty for the root.",
						'recursive' => "1 to also list the content of the subdirectories, 0 (default) for this directory only.",
						'search' => "Return only the files and directories whose name contains this text.",
						'sortfield' => "Sort on 'name' (default), 'date' or 'size'.",
						'sortorder' => "'asc' (default) or 'desc'."
					]
				],
				'index' => [
					'suffix' => 'download',
					'module' => 'ecm',
					'fixed_params' => ['modulepart' => 'ecm'],
					'description' => "Download one file of the DMS (ECM module) manual area, the free directory tree where users file documents not linked to a business object, and return its content base64-encoded. Give original_file, the path relative to the root of that tree (e.g. 'MyDirectory/MySubDirectory/agreement.pdf'): the path api_documents_manual_list returns. Only for small files: the whole content travels in the answer.",
					'params' => [
						'original_file' => "Relative path with filename, relative to the root of the DMS manual tree (e.g. 'Contracts/2024/agreement.pdf')."
					]
				],
				'post' => [
					'suffix' => 'upload',
					'module' => 'ecm',
					'fixed_params' => ['modulepart' => 'ecm'],
					'description' => "Upload a file into the DMS (ECM module) manual area, the free directory tree where users file documents not linked to a business object. Give subdir (the directory inside the tree, preferably an existing one) and filecontent base64-encoded with fileencoding='base64'. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewUploadDocument',
						'required' => ['filename'],
						'args' => ['filename', 'modulepart']
					],
					'params' => [
						'filename' => $uploadParams['filename'],
						'subdir' => "Directory inside the DMS manual tree (e.g. 'Contracts/2024'). Empty for the root.",
						'filecontent' => $uploadParams['filecontent'],
						'fileencoding' => $uploadParams['fileencoding'],
						'overwriteifexists' => $uploadParams['overwriteifexists'],
						'createdirifnotexists' => $uploadParams['createdirifnotexists']
					]
				]
			]
		];
		// No 'right' declared on medias: the API checks it (website/write to upload).
		$this->enrichments['documents_medias'] = [
			'endpoint' => 'documents',
			'label' => 'public medias of the website module',
			'methods' => [
				'getDocumentsListByDirectory' => [
					'suffix' => 'list',
					'fixed_params' => ['modulepart' => 'medias'],
					'description' => "List the files and subdirectories of a directory of the public medias directory, the files (images, videos, documents) published and reachable from the websites of the website module. Answers \"which images does the website mywebsite use\", \"is there a logo in the medias\". Returns for each entry its type (dir or file), its path to give to api_documents_medias_download, size, date and a download link, never the content. The images of a website are usually in 'image/<website ref>'.",
					'params' => [
						'subdir' => "Directory to list, relative to the medias directory (e.g. 'image/mywebsite'). Empty for the root.",
						'recursive' => "1 to also list the content of the subdirectories, 0 (default) for this directory only.",
						'search' => "Return only the files and directories whose name contains this text.",
						'sortfield' => "Sort on 'name' (default), 'date' or 'size'.",
						'sortorder' => "'asc' (default) or 'desc'."
					]
				],
				'index' => [
					'suffix' => 'download',
					'fixed_params' => ['modulepart' => 'medias'],
					'description' => "Download one file of the public medias directory, the files (images, videos, documents) published and reachable from the websites of the website module, and return its content base64-encoded. Give original_file, the path inside that directory (e.g. 'image/mywebsite/picture.png'): the path api_documents_medias_list returns. Only for small files: the whole content travels in the answer.",
					'params' => [
						'original_file' => "Relative path with filename inside the medias directory (e.g. 'image/mywebsite/picture.png')."
					]
				],
				'post' => [
					'suffix' => 'upload',
					'fixed_params' => ['modulepart' => 'medias'],
					'description' => "Upload a file into the public medias directory, where the images, videos and documents of the websites of the website module are stored: the file becomes publicly reachable. Give subdir (e.g. 'image/mywebsite' for the images of the website mywebsite) and filecontent base64-encoded with fileencoding='base64'. The user confirms before anything is written.",
					'write' => [
						'preview' => 'AIPreviewUploadDocument',
						'required' => ['filename', 'subdir'],
						'args' => ['filename', 'modulepart']
					],
					'params' => [
						'filename' => $uploadParams['filename'],
						'subdir' => "Directory inside the medias directory (e.g. 'image/mywebsite' for the images of the website mywebsite).",
						'filecontent' => $uploadParams['filecontent'],
						'fileencoding' => $uploadParams['fileencoding'],
						'overwriteifexists' => $uploadParams['overwriteifexists'],
						'createdirifnotexists' => $uploadParams['createdirifnotexists']
					]
				]
			]
		];

		$this->addReadEndpoints();
		$this->addWritesOnExistingRecords();
		// Modules answer last so their enrichment keys can never shadow a core one.
		$this->loadExternalEnrichments();
	}

	/**
	 * Merge endpoint enrichments declared by external modules through the
	 * 'addMcpApiBridgeEnrichments' hook (aimcp context).
	 *
	 * An external module declares the part of the whitelist that core cannot
	 * know: which methods of its own api_*.class.php files are exposed, with
	 * the parameter docs and 'write' blocks they carry. The declaration is
	 * what makes an external endpoint exposed at all — the scan only decides
	 * what is reachable — and a module can never shadow a core entry: keys
	 * already populated here are kept, the module's own are appended.
	 *
	 * Every other layer stays in place for module endpoints: the endpoint is
	 * discovered only while its module is enabled, the tools pass the
	 * per-context allow-lists, their 'right' blocks are checked per call, and
	 * every 'write' goes through the confirmation gate.
	 *
	 * Expected module return (in $this->results of its actions class):
	 *   array('endpointkey' => array('label' => ..., 'methods' => array(...)))
	 *
	 * @return void
	 */
	private function loadExternalEnrichments()
	{
		global $hookmanager;
		if (!is_object($hookmanager)) {
			if (!defined('DOL_DOCUMENT_ROOT')) {
				return;	// No Dolibarr bootstrap (unit tests): no module can answer anyway
			}
			require_once DOL_DOCUMENT_ROOT . '/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}

		$hookmanager->initHooks(array('aimcp'));

		try {
			$action = '';
			$hookmanager->executeHooks('addMcpApiBridgeEnrichments', array('db' => $this->db, 'user' => $this->user, 'conf' => isset($this->conf) ? $this->conf : null), $this, $action);

			if (!is_array($hookmanager->resArray)) {
				return;
			}

			foreach ($hookmanager->resArray as $moduleEnrichments) {
				if (!is_array($moduleEnrichments)) {
					continue;
				}
				foreach ($moduleEnrichments as $key => $entry) {
					$key = strtolower((string) $key);
					if (isset($this->enrichments[$key]) || !is_array($entry)) {
						continue;	// core entries win; non-array garbage ignored
					}
					if (empty($entry['methods']) || !is_array($entry['methods'])) {
						$entry['methods'] = $this->defaultMethods();
					}
					$this->enrichments[$key] = $entry;
				}
			}
		} catch (\Throwable $e) {
			dol_syslog("[ToolApiBridge] Error during 'addMcpApiBridgeEnrichments' hook execution: ".$e->getMessage(), LOG_ERR);
		}
	}

	/**
	 * Read-only tools the whitelist did not reach yet: new endpoints (bank,
	 * donations, leave, manufacturing, lots, knowledge base, member types,
	 * partnerships, workstations, taxes, currencies, contacts) and the read
	 * methods of endpoints already exposed (stock per warehouse, prices,
	 * time spent, contacts of a document, discounts, tags...). Each REST class
	 * checks its own read right. Salaries, recruitment candidates and event
	 * attendees stay out: they are personal data about people.
	 *
	 * @return void
	 */
	private function addReadEndpoints()
	{
		$pagination = "Set to true to get {data, pagination:{total,page,page_count,limit}}; use it to know how many records match.";

		$this->enrichments['bankaccounts'] = array(
			'label' => 'bank and cash accounts',
			'methods' => array(
				'index' => array(
					'default_properties' => 'id,ref,label,bank,currency_code,clos,courant',
					'description' => "Bank and cash accounts: ref, label, bank, currency. clos: 0 open, 1 closed. courant: 0 savings, 1 current, 2 cash. The balance is in api_bankaccounts_balance, the transactions in api_bankaccounts_lines."
				),
				'get' => array('description' => "One bank account with its bank details and currency."),
				'getLines' => array(
					'suffix' => 'lines',
					'description' => "Transactions of one bank account: date (dateo), value date (datev), amount (signed: positive is money in), label, payment type, reconciliation (rappro 1 when reconciled). Only the most recent 50 are returned: restrict the period with sqlfilters. Here the field names have NO 't.' prefix, e.g. \"(dateo:>=:'2026-01-01')\" or \"(label:like:'%rent%')\".",
					'params' => array('id' => "Rowid of the bank account."),
					// The REST method has no limit: a real account would send its whole history.
					// Oldest first there, so the end is the most recent.
					'max_items' => 50,
					'keep' => 'last'
				),
				'getBalance' => array(
					'suffix' => 'balance',
					'description' => "Current balance of one bank account.",
					'params' => array('id' => "Rowid of the bank account.")
				)
			)
		);

		$this->enrichments['variouspayments'] = array(
			'label' => 'miscellaneous bank payments',
			'methods' => array(
				'index' => array(
					'description' => "Payments recorded directly on a bank account, outside invoices: date (datep), amount, sens (0 money out, 1 money in), label, accounting account.",
					'params' => array('pagination_data' => $pagination)
				),
				'get' => array('description' => "One miscellaneous payment.")
			)
		);

		$this->enrichments['socialcontributions'] = array(
			'label' => 'social and fiscal contributions (taxes, charges)',
			'methods' => array(
				'index' => array(
					'description' => "Social and fiscal charges to pay: label, type, period, amount, paye (1 paid). Unpaid ones: sqlfilters \"(t.paye:=:0)\".",
					'params' => array('pagination_data' => $pagination)
				),
				'get' => array('description' => "One social or fiscal charge."),
				'getPayments' => array(
					'suffix' => 'payments_list',
					'description' => "Payments made on one social or fiscal charge.",
					'params' => array('id' => "Rowid of the charge.")
				)
			)
		);

		$this->enrichments['vatpayments'] = array(
			'label' => 'VAT declarations and payments',
			'methods' => array(
				'index' => array(
					'description' => "VAT to pay or refund per period: label, period end (datev), amount, paye (1 paid).",
					'params' => array('pagination_data' => $pagination)
				),
				'get' => array('description' => "One VAT declaration."),
				'getPayments' => array(
					'suffix' => 'payments_list',
					'description' => "Payments made on one VAT declaration.",
					'params' => array('id' => "Rowid of the VAT declaration.")
				)
			)
		);

		$this->enrichments['donations'] = array(
			'label' => 'donations',
			'methods' => array(
				'index' => array(
					'description' => "Donations received: donor, amount, date, project. Statuses (t.fk_statut): 0 draft, 1 validated (promised), 2 paid, -1 canceled.",
					'params' => array(
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5').",
						'pagination_data' => $pagination
					)
				),
				'get' => array('description' => "One donation with its donor, amount and status.")
			)
		);

		$this->enrichments['holidays'] = array(
			'label' => 'leave requests (holidays, days off)',
			'methods' => array(
				'index' => array(
					'description' => "Leave requests of employees: fk_user (the employee, a USER rowid), date_debut / date_fin (start / end), leave type (fk_type). Statuses (t.statut): 1 draft, 2 waiting for approval, 3 approved, 4 canceled, 5 refused. Who is off on a day: approved ones with sqlfilters \"(t.date_debut:<=:'YYYY-MM-DD') AND (t.date_fin:>=:'YYYY-MM-DD')\".",
					'params' => array(
						'user_ids' => "Comma-separated user rowids to restrict to the requests of these employees.",
						'pagination_data' => $pagination
					),
					// The reason of a leave is free text, sometimes medical: it stays here.
					'drop_fields' => array('description', 'note_private', 'note_public')
				),
				'get' => array(
					'description' => "One leave request with its employee, dates, type and status.",
					'drop_fields' => array('description', 'note_private', 'note_public')
				)
			)
		);

		$this->enrichments['boms'] = array(
			'label' => 'bills of materials (BOM)',
			'methods' => array(
				'index' => array(
					'description' => "Bills of materials: ref, label, the product it makes (fk_product), quantity made. Statuses (t.status): 0 draft, 1 validated, 9 canceled. The components are in api_boms_lines."
				),
				'get' => array('description' => "One bill of materials."),
				'getLines' => array(
					'suffix' => 'lines',
					'description' => "Components of one bill of materials: product, quantity, efficiency.",
					'params' => array('id' => "Rowid of the bill of materials.")
				)
			)
		);

		$this->enrichments['mos'] = array(
			'label' => 'manufacturing orders (MO)',
			'methods' => array(
				'index' => array(
					'description' => "Manufacturing orders: ref, product to make (fk_product), quantity, bill of materials (fk_bom), planned dates. Statuses (t.status): 0 draft, 1 validated (to produce), 2 in progress, 3 produced, 9 canceled."
				),
				'get' => array('description' => "One manufacturing order with what it consumes and produces.")
			)
		);

		$this->enrichments['productlots'] = array(
			'label' => 'product lots and serial numbers',
			'methods' => array(
				'index' => array(
					'description' => "Lots and serial numbers of products: batch (the number), fk_product, eat-by date (eatby), sell-by date (sellby). Lots of one product: sqlfilters \"(t.fk_product:=:ID)\".",
					'params' => array('pagination_data' => $pagination)
				),
				'get' => array('description' => "One lot or serial number with its dates.")
			)
		);

		$this->enrichments['workstations'] = array(
			'label' => 'workstations (manufacturing)',
			'methods' => array(
				'index' => array('description' => "Workstations used in manufacturing: ref, label, type, hourly costs."),
				'get' => array('description' => "One workstation."),
				'getByRef' => array(
					'suffix' => 'get_by_ref',
					'description' => "One workstation by its exact reference.",
					'params' => array('ref' => "Exact workstation reference.")
				)
			)
		);

		$this->enrichments['knowledgemanagement'] = array(
			'label' => 'knowledge base articles',
			'methods' => array(
				'index' => array(
					'description' => "Knowledge base articles: ref, question, answer, language. Statuses (t.status): 0 draft, 1 validated, 9 canceled. Search the question with sqlfilters \"(t.question:like:'%word%')\".",
					'params' => array(
						'category' => "Rowid of a knowledge category to restrict the list to.",
						'pagination_data' => $pagination
					)
				),
				'get' => array('description' => "One knowledge base article with its question and answer.")
			)
		);

		$this->enrichments['memberstypes'] = array(
			'label' => 'member types',
			'methods' => array(
				'index' => array('description' => "Types of members: label, whether a subscription is due, its amount and duration."),
				'get' => array('description' => "One member type.")
			)
		);

		$this->enrichments['partnerships'] = array(
			'label' => 'partnerships',
			'methods' => array(
				'index' => array('description' => "Partnerships with third parties or members: type, dates. Statuses (t.status): 0 draft, 1 validated, 2 approved, 3 refused, 9 canceled."),
				'get' => array('description' => "One partnership.")
			)
		);

		$this->enrichments['multicurrencies'] = array(
			'label' => 'currencies and exchange rates',
			'methods' => array(
				'index' => array('description' => "Currencies in use, with their code and current rate."),
				'get' => array('description' => "One currency."),
				'getByCode' => array(
					'suffix' => 'get_by_code',
					'description' => "One currency by its ISO code (e.g. 'USD').",
					'params' => array('code' => "ISO code of the currency.")
				),
				'getRates' => array(
					'suffix' => 'rates',
					'description' => "Exchange rates of one currency, the most recent 30.",
					'params' => array('id' => "Rowid of the currency."),
					'max_items' => 30,
					'keep' => 'first'
				)
			)
		);

		// Websites of the website module, with their pages and blog posts. The page content is
		// left out of the lists (it can be large): read one page with api_websites_page_get.
		$this->enrichments['websites'] = array(
			'label' => 'websites (website module)',
			'methods' => array(
				'index' => array(
					'description' => "Websites built with the website module: ref, description, main language, virtual host, home page (fk_default_home). Statuses (t.status): 0 offline, 1 online.",
					'default_properties' => 'id,ref,description,lang,otherlang,status,virtualhost'
				),
				'get' => array('description' => "One website."),
				'getByRef' => array(
					'suffix' => 'get_by_ref',
					'description' => "One website by its exact reference.",
					'params' => array('ref' => "Exact website reference.")
				),
				'indexPages' => array(
					'suffix' => 'pages_list',
					'description' => "Pages of one website: pageurl (alias), title, type_container ('page', 'blogpost', 'menu', 'other', 'header', 'footer'...), lang, status (0 offline, 1 online). Blog posts are the pages with type_container 'blogpost'. Search with sqlfilters \"(t.type_container:=:'blogpost')\".",
					'default_properties' => 'id,pageurl,title,description,type_container,lang,status,date_creation,author_alias',
					'params' => array('id' => "Rowid of the website.")
				),
				'getPage' => array(
					'suffix' => 'page_get',
					'description' => "One page of a website, with its HTML content.",
					'params' => array('id' => "Rowid of the website.", 'pageid' => "Rowid of the page.")
				),
				'postPage' => array(
					'suffix' => 'page_create',
					'description' => "Create a page (type_container 'page') or a blog post (type_container 'blogpost') in a website. Give id (rowid of the website), pageurl (alias, only a-z, 0-9, - and _), title and content (HTML, put inside a <section> tag, no PHP code). It is created offline (status 0) unless status 1 is given. The user confirms before anything is written.",
					'write' => array(
						'preview' => 'AIPreviewCreateWebsitePage',
						'required' => array('id', 'pageurl', 'title'),
						'object' => 'WebsitePage',
						'args' => array('title', 'pageurl', 'id'),
						'args_objects' => array('id' => 'Website'),
						'right' => array('website', 'write')
					),
					'params' => array(
						'id' => "Rowid of the website.",
						'pageurl' => "Alias of the page, used in its URL: only a-z, 0-9, - and _.",
						'title' => "Title of the page.",
						'type_container' => "Kind of page: 'page' (default) or 'blogpost' for an article of a blog.",
						'content' => "HTML content of the page, inside a <section> tag. No PHP code.",
						'description' => "Short description (meta description), 255 characters max.",
						'keywords' => "Keywords, separated by commas.",
						'lang' => "Language code of the page, e.g. 'en' or 'fr'. Omit for the website language.",
						'author_alias' => "Name of the author to show.",
						'status' => "0 offline (default), 1 online."
					)
				)
			)
		);

		// Contacts (people of third parties), read side.
		$this->enrichments['contacts'] = array(
			'label' => 'contacts (people of third parties)',
			'methods' => array(
				'index' => array(
					'default_properties' => 'id,firstname,lastname,poste,email,phone_pro,phone_mobile,socid,statut',
					'description' => "Contacts are the people of a third party. Contacts of one third party: thirdparty_ids. Search by name with sqlfilters on t.lastname or t.firstname. statut: 1 active, 0 inactive.",
					'params' => array(
						'thirdparty_ids' => "Comma-separated third-party rowids to restrict to (e.g. '1,5').",
						'pagination_data' => $pagination
					)
				),
				'get' => array('description' => "One contact with name, job title, phones, email and third party."),
				'getByEmail' => array(
					'suffix' => 'get_by_email',
					'description' => "One contact by its exact email address.",
					'params' => array('email' => "Exact email address of the contact."),
					// The REST method returns an empty contact when several share the address.
					'needs_id' => "No single contact has this email (none, or several): search with api_contacts_list and sqlfilters on t.email."
				),
				'getCategories' => array(
					'suffix' => 'categories_list',
					'description' => "Tags (categories) of one contact.",
					'params' => array('id' => "Rowid of the contact.")
				)
			)
		);

		// Users (staff accounts) and their groups, read side. Lookups by id,
		// login, email and self stay downstream-gated so a user may still read
		// his own record without the user/user/lire right. Write side (user
		// creation, password, group assignment) is left out on purpose.
		$this->enrichments['users'] = array(
			'label' => 'users (staff accounts) and user groups',
			'methods' => array(
				'index' => array(
					'default_properties' => 'id,login,firstname,lastname,email,job,statut,admin,employee',
					'description' => "Users of the ERP (staff accounts): login, name, email, job, employee flag. statut: 1 active, 0 inactive. Search by name with sqlfilters on t.lastname, t.firstname or t.login. Useful when you need the rowid of a user to assign him somewhere (project, task, ticket).",
					'params' => array(
						'user_ids' => "Comma-separated user rowids to restrict to (e.g. '1,5').",
						'category' => "Rowid of a user category (tag) to restrict to its members."
					),
					'right' => array('user', 'user', 'lire')
				),
				'get' => array(
					'description' => "One user with login, name, email, phones, job and status.",
					'params' => array('id' => "Rowid of the user.", 'includepermissions' => "1 to also load his permission set (heavy, default 0).")
				),
				'getByLogin' => array(
					'suffix' => 'get_by_login',
					'description' => "One user by its exact login.",
					'params' => array('login' => "Exact login of the user.", 'includepermissions' => "1 to also load his permission set (heavy, default 0).")
				),
				'getByEmail' => array(
					'suffix' => 'get_by_email',
					'description' => "One user by its exact email address.",
					'params' => array('email' => "Exact email address of the user.", 'includepermissions' => "1 to also load his permission set (heavy, default 0).")
				),
				'getInfo' => array(
					'suffix' => 'me',
					'description' => "The user currently connected: answers 'who am I', 'my email', 'my permissions'.",
					'params' => array('includepermissions' => "1 to also load his permission set (heavy, default 0).")
				),
				'getGroups' => array(
					'suffix' => 'groups_of_user',
					'description' => "Groups a user belongs to.",
					'params' => array('id' => "Rowid of the user."),
					'right' => array('user', 'user', 'lire')
				),
				'listGroups' => array(
					'suffix' => 'groups_list',
					'description' => "All user groups (e.g. administrators, sales): name, note. Members of one group come from api_users_group_info with load_members=1.",
					'params' => array(
						'group_ids' => "Comma-separated group rowids to restrict to (e.g. '1,5')."
					)
				),
				'infoGroups' => array(
					'suffix' => 'group_info',
					'description' => "One user group: name, note; set load_members=1 to include its members.",
					'params' => array('group' => "Rowid of the user group.", 'load_members' => "1 to include the members of the group (default 0).", 'includepermissions' => "1 to also load its permission set (heavy, default 0).")
				)
			)
		);

		// Read methods of endpoints already exposed.
		$more = array(
			'warehouses' => array(
				'listProducts' => array(
					'suffix' => 'products_list',
					'description' => "Products stored in one warehouse, with their stock there when includestockdata=1. Answers \"what is in warehouse X\".",
					'params' => array('id' => "Rowid of the warehouse.", 'includestockdata' => "1 to add the stock of each product.", 'pagination_data' => $pagination)
				)
			),
			'products' => array(
				'getSubproducts' => array(
					'suffix' => 'subproducts',
					'description' => "Components of a kit (virtual product): each sub-product with its quantity.",
					'params' => array('id' => "Rowid of the kit product.")
				),
				'getCustomerPricesPerSegment' => array(
					'suffix' => 'prices_per_segment',
					// Only in that price mode: elsewhere the REST method refuses.
					'setup' => array('PRODUIT_MULTIPRICES', 'PRODUIT_CUSTOMER_PRICES_AND_MULTIPRICES'),
					'description' => "Selling prices of one product per price level (segment), when several price levels are used.",
					'params' => array('id' => "Rowid of the product.")
				),
				'getCustomerPricesPerCustomer' => array(
					'suffix' => 'prices_per_customer',
					'setup' => array('PRODUIT_CUSTOMER_PRICES', 'PRODUIT_CUSTOMER_PRICES_AND_MULTIPRICES'),
					'description' => "Selling prices of one product negotiated per customer, when customer prices are used.",
					'params' => array('id' => "Rowid of the product.", 'thirdparty_id' => "Rowid of one customer to restrict to.")
				),
				'getCustomerPricesPerQuantity' => array(
					'suffix' => 'prices_per_quantity',
					'setup' => array('PRODUIT_CUSTOMER_PRICES_BY_QTY'),
					'description' => "Selling prices of one product by quantity, when prices by quantity are used.",
					'params' => array('id' => "Rowid of the product.")
				),
				'getSupplierProducts' => array(
					'suffix' => 'supplier_products_list',
					'description' => "Products with their supplier prices: for each, the suppliers that sell it, their reference and price. Restrict to one supplier with 'supplier'.",
					'params' => array('supplier' => "Rowid of one supplier to restrict to.", 'mode' => "0 all, 1 products only, 2 services only.", 'category' => "Rowid of a product category.")
				),
				'getCategories' => array(
					'suffix' => 'categories_list',
					'description' => "Tags (categories) of one product.",
					'params' => array('id' => "Rowid of the product.")
				)
			),
			'projects' => array(
				'getContacts' => array(
					'suffix' => 'contacts_list',
					'description' => "People of one project: internal users and external contacts, with their role (project leader, contributor...).",
					'params' => array('id' => "Rowid of the project.", 'type' => "'internal' or 'external' to restrict, empty for both.")
				),
				'getTimespent' => array(
					'suffix' => 'timespent_list',
					'description' => "Summary of the time spent on each task of one project (first 100 tasks): total duration in seconds and the users who recorded it. The time records themselves are in api_projects_timespent_all or api_tasks_timespent_list.",
					'params' => array('id' => "Rowid of the project."),
					'max_items' => 100,
					'keep' => 'first'
				),
				'listTimespent' => array(
					'suffix' => 'timespent_all',
					'description' => "Time recorded across projects, with project, task, user, date and duration in seconds. Restrict with thirdparty_ids or sqlfilters (e.g. a period on t.element_date).",
					'params' => array('thirdparty_ids' => "Comma-separated third-party rowids.", 'pagination_data' => $pagination)
				)
			),
			'orders' => array(
				'getOrderShipments' => array(
					'suffix' => 'shipments_list',
					'description' => "Shipments made for one sale order, with what each shipped.",
					'params' => array('id' => "Rowid of the sale order.")
				),
				'getContacts' => array(
					'suffix' => 'contacts_list',
					'description' => "Contacts of one sale order with their role (billing, shipping, follow-up...).",
					'params' => array('id' => "Rowid of the sale order.", 'type' => "Role code to restrict to, e.g. BILLING, SHIPPING, CUSTOMER.")
				)
			),
			'proposals' => array(
				'getContacts' => array(
					'suffix' => 'contacts_list',
					'description' => "Contacts of one commercial proposal with their role.",
					'params' => array('id' => "Rowid of the proposal.", 'type' => "Role code to restrict to.")
				)
			),
			'invoices' => array(
				'getContacts' => array(
					'suffix' => 'contacts_list',
					'description' => "Contacts of one customer invoice with their role.",
					'params' => array('id' => "Rowid of the invoice.", 'type' => "Role code to restrict to.")
				),
				'indexTemplateInvoices' => array(
					'suffix' => 'templates_list',
					'description' => "Recurring invoice templates: customer, frequency, next generation date, amounts. Set withLines=false for lists.",
					'params' => array('thirdparty_ids' => "Comma-separated third-party rowids.", 'withLines' => "false to omit the lines.", 'pagination_data' => $pagination)
				),
				'getTemplateInvoice' => array(
					'suffix' => 'template_get',
					'description' => "One recurring invoice template with its lines and frequency.",
					'params' => array('id' => "Rowid of the template.")
				)
			),
			'supplier_invoices' => array(
				'getPayments' => array(
					'suffix' => 'payments_list',
					'description' => "Payments already made on one supplier invoice: amount, date, payment mode, bank account.",
					'params' => array('id' => "Rowid of the supplier invoice.")
				)
			),
			'expensereports' => array(
				'getLines' => array(
					'suffix' => 'lines',
					'description' => "Lines of one expense report: date, expense type, amounts, VAT.",
					'params' => array('id' => "Rowid of the expense report.")
				),
				'getAllPayments' => array(
					'suffix' => 'payments_list',
					'description' => "Payments of expense reports: amount, date, payment mode."
				)
			),
			'interventions' => array(
				'getContacts' => array(
					'suffix' => 'contacts_list',
					'description' => "Contacts of one intervention with their role.",
					'params' => array('id' => "Rowid of the intervention.", 'type' => "Role code to restrict to.")
				)
			),
			'thirdparties' => array(
				'getByBarcode' => array(
					'suffix' => 'get_by_barcode',
					'description' => "One third party by its barcode.",
					'params' => array('barcode' => "Barcode value.")
				),
				'getFixedAmountDiscounts' => array(
					'suffix' => 'discounts_list',
					'description' => "Fixed-amount discounts and credits of one third party: available, or already used on an invoice.",
					'params' => array('id' => "Rowid of the third party.", 'mode' => "'customer' (default) or 'supplier'.", 'filter' => "'none' (all), 'available' or 'used'.")
				),
				'getSalesRepresentatives' => array(
					'suffix' => 'sales_representatives_list',
					'description' => "Sales representatives (users) following one third party.",
					'params' => array('id' => "Rowid of the third party.", 'mode' => "0 full records, 1 rowids only.")
				),
				'getCategories' => array(
					'suffix' => 'categories_list',
					'description' => "Customer tags (categories) of one third party.",
					'params' => array('id' => "Rowid of the third party.")
				),
				'getSupplierCategories' => array(
					'suffix' => 'supplier_categories_list',
					'description' => "Supplier tags (categories) of one third party.",
					'params' => array('id' => "Rowid of the third party.")
				)
			),
			'categories' => array(
				'getListForObject' => array(
					'suffix' => 'of_object_list',
					'description' => "Tags (categories) of any object: give its rowid and type.",
					'params' => array('id' => "Rowid of the object.", 'type' => "Its type: 'product', 'customer', 'supplier', 'contact', 'member', 'project', 'user', 'warehouse', 'actioncomm', 'ticket'.")
				)
			),
			'members' => array(
				'getByThirdparty' => array(
					'suffix' => 'get_by_thirdparty',
					'description' => "The member linked to one third party.",
					'params' => array('thirdparty' => "Rowid of the third party.")
				),
				'getNbByYear' => array(
					'suffix' => 'count_by_year',
					'description' => "Number of new members per year."
				),
				'getNbByMonth' => array(
					'suffix' => 'count_by_month',
					'description' => "Number of new members per month of one year.",
					'params' => array('year' => "Year, e.g. 2026.")
				),
				'getAmountByMonth' => array(
					'suffix' => 'subscriptions_amount_by_month',
					'description' => "Amount of subscriptions per month of one year.",
					'params' => array('year' => "Year, e.g. 2026.")
				)
			)
		);
		foreach ($more as $key => $methods) {
			if (!isset($this->enrichments[$key])) {
				continue;
			}
			foreach ($methods as $method => $meta) {
				$this->enrichments[$key]['methods'][$method] = $meta;
			}
		}
	}

	/**
	 * Enrichment of the postLine method of a document (order, proposal, invoice,
	 * and their supplier counterparts): add ONE line to an existing draft.
	 *
	 * @param  string                $label      Document name, as the model reads it (e.g. 'sale order').
	 * @param  string                $lookupTool Tool to find the rowid when only the ref is known.
	 * @param  string                $class      Business class of the document.
	 * @param  string[]              $right      Right needed to write.
	 * @param  bool                  $buy        True for a supplier document: purchase price and VAT.
	 * @param  array<string,string>  $rename     Line fields the REST endpoint names differently.
	 * @param  array<string,mixed>   $defaults   Line fields always sent, unless given.
	 * @return array<string,mixed>               Method enrichment.
	 */
	private function addLineMethod($label, $lookupTool, $class, array $right, $buy = false, array $rename = array(), array $defaults = array())
	{
		$lookup = (substr($lookupTool, -7) === '_by_ref') ? $lookupTool : $lookupTool." with sqlfilters (t.ref:=:'THEREF')";

		$description = "Add ONE line to an existing ".$label.". Only a DRAFT ".$label." (status 0) accepts new lines. ";
		$description .= "Give 'id' (".$label." rowid; when only the ref is known, look it up with ".$lookup.") and the line fields: product_ref (product ref or exact label) and qty, ";
		$description .= "optionally subprice (unit price excl. tax, ".($buy ? "supplier purchase price" : "catalogue price")." when omitted), remise_percent (discount %), tva_tx (VAT rate, default rate when omitted), desc (text of a free line without product). ";
		$description .= "Call it once per line to add. The user confirms before anything is written.";

		$write = array(
			// One sentence for every document: the ref names it.
			'preview' => 'AIPreviewAddDocumentLine',
			'object' => $class,
			// The line is written into an existing record, named by this
			// argument, instead of creating a new one.
			'parent' => 'id',
			'right' => $right
		);
		if ($buy) {
			$write['buy'] = true;
		}
		if ($rename) {
			$write['rename'] = $rename;
		}
		if ($defaults) {
			$write['defaults'] = $defaults;
		}

		return array(
			'suffix' => 'add_line',
			'description' => $description,
			'write' => $write,
			'params' => array(
				'id' => "Rowid of the draft ".$label." to add the line to.",
				'request_data' => "The line, as an object: {\"product_ref\": \"PINKDRESS\", \"qty\": 2}, optionally with subprice, remise_percent, tva_tx, desc. The fields may also be given flat, next to 'id'."
			)
		);
	}

	/**
	 * Writes on records that already exist, and the creates that need more than
	 * a third party and lines: edits, validation, payment, deletion of drafts,
	 * contacts, interventions, contracts, projects and tasks. Each one is a
	 * whitelisted REST method with its own preview
	 * and precheck, behind the same confirmation as the other writes.
	 *
	 * @return void
	 */
	private function addWritesOnExistingRecords()
	{
		$advanced = $this->setupValue('MAIN_USE_ADVANCED_PERMS');

		// Edits: only the listed fields, so a model cannot change a status, an
		// entity or an amount behind a sentence about a phone number.
		$this->enrichments['thirdparties']['methods']['put'] = $this->updateMethod('third party', 'Societe', array('societe', 'creer'), array(
			'name' => "Company or person name.",
			'name_alias' => "Trade name, the name people use.",
			'address' => "Street address.",
			'zip' => "Postal code.",
			'town' => "Town.",
			'phone' => "Phone.",
			'phone_mobile' => "Mobile phone.",
			'fax' => "Fax.",
			'email' => "Email of the company.",
			'url' => "Website.",
			'tva_intra' => "Intra-community VAT number.",
			'note_public' => "Public note.",
			'note_private' => "Private note.",
			'client' => "1 customer, 2 prospect, 3 both, 0 none.",
			'fournisseur' => "1 supplier, 0 not a supplier.",
			'status' => "1 open, 0 closed."
		), 'thirdparty');

		$productfields = array(
			'label' => "Label.",
			'description' => "Description.",
			'status' => "On sale: 1 yes, 0 no.",
			'status_buy' => "On purchase: 1 yes, 0 no.",
			'weight' => "Weight.",
			'note_public' => "Public note.",
			'note_private' => "Private note."
		);
		// The REST update writes a price only in these price modes: elsewhere the
		// change would be silently ignored, so it is not offered.
		if ($this->setupValue('PRODUCT_PRICE_UNIQ') || $this->setupValue('PRODUIT_CUSTOMER_PRICES')) {
			$productfields['price'] = "Selling price excl. tax.";
		}
		$this->enrichments['products']['methods']['put'] = $this->updateMethod('product', 'Product', array('produit', 'creer'), $productfields, 'ref');

		$this->enrichments['contacts']['methods']['post'] = array(
			'suffix' => 'create',
			'description' => "Add a contact (a person), usually at a third party. Give lastname, and socid or simply the third party name when the person belongs to a company: it is resolved here. The user confirms before anything is written.",
			'write' => array(
				'preview' => 'AIPreviewAddContact',
				'required' => array('lastname'),
				'object' => 'Contact',
				'thirdparty' => 'socid',
				'thirdparty_optional' => true,
				'args' => array('_name', '_at'),
				'right' => array('societe', 'contact', 'creer')
			),
			'params' => array(
				'lastname' => "Last name.",
				'firstname' => "First name.",
				'socid' => "Third party: rowid, or its name. Omit for a contact with no company.",
				'poste' => "Job title.",
				'email' => "Email.",
				'phone_pro' => "Work phone.",
				'phone_mobile' => "Mobile phone."
			)
		);
		$this->enrichments['contacts']['methods']['put'] = $this->updateMethod('contact', 'Contact', array('societe', 'contact', 'creer'), array(
			'firstname' => "First name.",
			'lastname' => "Last name.",
			'poste' => "Job title.",
			'email' => "Email.",
			'phone_pro' => "Work phone.",
			'phone_perso' => "Personal phone.",
			'phone_mobile' => "Mobile phone.",
			'address' => "Street address.",
			'zip' => "Postal code.",
			'town' => "Town.",
			'note_public' => "Public note.",
			'note_private' => "Private note.",
			'statut' => "1 active, 0 inactive."
		), '');

		// Validation. The trigger and the numbering stay core's: a model can
		// never skip the triggers or force a number.
		$this->enrichments['invoices']['methods']['validate'] = $this->validateMethod('customer invoice', 'Facture', 'AIPreviewValidateInvoice', $advanced ? array('facture', 'invoice_advance', 'validate') : array('facture', 'creer'), array('force_number' => '', 'notrigger' => 0), 'STOCK_CALCULATE_ON_BILL');
		$this->enrichments['orders']['methods']['validate'] = $this->validateMethod('sale order', 'Commande', 'AIPreviewValidateOrder', $advanced ? array('commande', 'order_advance', 'validate') : array('commande', 'creer'), array('notrigger' => 0), 'STOCK_CALCULATE_ON_VALIDATE_ORDER');
		$this->enrichments['proposals']['methods']['validate'] = $this->validateMethod('commercial proposal', 'Propal', 'AIPreviewValidateProposal', $advanced ? array('propal', 'propal_advance', 'validate') : array('propal', 'creer'), array('notrigger' => 0), '');
		// Logistic documents. Each line carries its own warehouse, so none is asked
		// here; whether validating moves the stock is a setup choice of the instance
		// (STOCK_CALCULATE_ON_RECEPTION / _SHIPMENT), and the confirmation says so.
		$receptionMovesStock = isModEnabled('stock') && getDolGlobalInt('STOCK_CALCULATE_ON_RECEPTION');
		$shipmentMovesStock = isModEnabled('stock') && getDolGlobalInt('STOCK_CALCULATE_ON_SHIPMENT');
		$this->enrichments['receptions']['methods']['validate'] = $this->validateMethod('supplier reception', 'Reception', $receptionMovesStock ? 'AIPreviewValidateReceptionStock' : 'AIPreviewValidateReception', $advanced ? array('reception', 'reception_advance', 'validate') : array('reception', 'creer'), array('notrigger' => 0), '');
		$this->enrichments['shipments']['methods']['validate'] = $this->validateMethod('shipment', 'Expedition', $shipmentMovesStock ? 'AIPreviewValidateShipmentStock' : 'AIPreviewValidateShipment', $advanced ? array('expedition', 'shipping_advance', 'validate') : array('expedition', 'creer'), array('notrigger' => 0), '');
		if ($receptionMovesStock) {
			$this->enrichments['receptions']['methods']['validate']['description'] .= " On this Dolibarr, validating a reception brings the received goods into the stock of their warehouses.";
		}
		if ($shipmentMovesStock) {
			$this->enrichments['shipments']['methods']['validate']['description'] .= " On this Dolibarr, validating a shipment takes the shipped goods out of stock.";
		}

		// A payment: the distributed method, the only one that takes an amount.
		$this->enrichments['invoices']['methods']['addPaymentDistributed'] = array(
			'suffix' => 'add_payment',
			'description' => "Record a payment received on a VALIDATED customer invoice. Give 'id' (rowid, or its ref), and 'amount' only for a partial payment: by default what remains is paid. payment_mode is how it was paid (cash, cheque, transfer, card, or a code like LIQ, CHQ, VIR, CB); bank_account is the account it went into (name or rowid, may be omitted when there is only one). The user confirms before anything is written.",
			'write' => array(
				'kind' => 'payment',
				'preview' => 'AIPreviewPayInvoice',
				'object' => 'Facture',
				'label' => 'customer invoice',
				'target' => 'id',
				'lookup' => 'ref',
				'fixed' => array('closepaidinvoices' => 'yes', 'accepthigherpayment' => false, 'ref_ext' => ''),
				'right' => array('facture', 'paiement')
			),
			'params' => array(
				'id' => "The invoice: rowid, or its ref.",
				'amount' => "Amount received, incl. tax. Omit to pay what remains.",
				'payment_mode' => "How it was paid: cash, cheque, transfer, card, or a payment code.",
				'bank_account' => "Bank account it was paid into: name, ref or rowid. Omit when there is only one.",
				'date' => "Payment date YYYY-MM-DD. Omit for today.",
				'num_payment' => "Cheque or transfer number.",
				'issuer' => "Who wrote the cheque, needed for a cheque.",
				'comment' => "Note."
			)
		);

		// Deletion, of drafts only: a validated document has a number and an
		// accounting life, it is cancelled in Dolibarr, never deleted from a chat.
		$deletes = array(
			'invoices' => array('customer invoice', 'Facture', array('facture', 'supprimer')),
			'orders' => array('sale order', 'Commande', array('commande', 'supprimer')),
			'proposals' => array('commercial proposal', 'Propal', array('propal', 'supprimer')),
			'supplier_invoices' => array('supplier invoice', 'FactureFournisseur', array('fournisseur', 'facture', 'supprimer')),
			'supplier_proposals' => array('supplier proposal', 'SupplierProposal', array('supplier_proposal', 'supprimer')),
			'contracts' => array('contract', 'Contrat', array('contrat', 'supprimer')),
			'interventions' => array('intervention', 'Fichinter', array('ficheinter', 'supprimer'))
		);
		foreach ($deletes as $key => $d) {
			$this->enrichments[$key]['methods']['delete'] = $this->deleteMethod($d[0], $d[1], $d[2]);
		}

		// Lines of a draft: changed or removed, never on a validated document.
		$lines = array(
			'proposals' => array('commercial proposal', 'Propal', array('propal', 'creer'), 'product'),
			'orders' => array('sale order', 'Commande', array('commande', 'creer'), 'product'),
			'invoices' => array('customer invoice', 'Facture', array('facture', 'creer'), 'product'),
			'supplier_invoices' => array('supplier invoice', 'FactureFournisseur', array('fournisseur', 'facture', 'creer'), 'supplier'),
			'contracts' => array('contract', 'Contrat', array('contrat', 'creer'), 'contract'),
			'interventions' => array('intervention', 'Fichinter', array('ficheinter', 'creer'), 'intervention')
		);
		foreach ($lines as $key => $l) {
			$this->enrichments[$key]['methods']['putLine'] = $this->lineMethod('update', $l[0], $l[1], $l[2], $l[3]);
			$this->enrichments[$key]['methods']['deleteLine'] = $this->lineMethod('delete', $l[0], $l[1], $l[2], $l[3]);
		}
		// The REST postLine() of a contract reads every field: none is left unset.
		$this->enrichments['contracts']['methods']['postLine'] = $this->addLineMethod('contract', 'api_contracts_list', 'Contrat', array('contrat', 'creer'), false, array(), array('price_base_type' => 'HT', 'localtax1_tx' => 0, 'localtax2_tx' => 0, 'remise_percent' => 0, 'date_start' => '', 'date_end' => '', 'subprice_excl_tax' => 0, 'info_bits' => 0, 'fk_fournprice' => null, 'pa_ht' => 0, 'array_options' => array(), 'fk_unit' => null, 'rang' => -1));
		$this->enrichments['interventions']['methods']['postLine'] = $this->lineMethod('add', 'intervention', 'Fichinter', array('ficheinter', 'creer'), 'intervention');

		// Header of a draft: the fields a user corrects, by their old and new value.
		$project = array('type' => array('integer', 'null'), 'minimum' => 1, 'description' => 'Rowid of the project, or null to unlink it.');
		$date = array('type' => array('integer', 'null'), 'minimum' => 0, 'description' => 'Unix timestamp, or null to clear.');
		$text = array('type' => 'string');
		$this->enrichments['proposals']['methods']['put'] = $this->headerUpdateMethod('commercial proposal', 'Propal', array('propal', 'creer'), array('propal', 'lire'), array('propal', '', ''), 'propal', array(
			'ref_client' => array($text, 'RefCustomer'), 'note_public' => array($text, 'NotePublic'), 'note_private' => array($text, 'NotePrivate'),
			'fk_project' => array($project, 'Project')
		));
		$this->enrichments['orders']['methods']['put'] = $this->headerUpdateMethod('sale order', 'Commande', array('commande', 'creer'), array('commande', 'lire'), array('commande', '', ''), 'commande', array(
			'ref_client' => array($text, 'RefCustomer'), 'note_public' => array($text, 'NotePublic'), 'note_private' => array($text, 'NotePrivate'),
			'delivery_date' => array($date, 'DeliveryDate'), 'fk_project' => array($project, 'Project')
		));
		$this->enrichments['invoices']['methods']['put'] = $this->headerUpdateMethod('customer invoice', 'Facture', array('facture', 'creer'), array('facture', 'lire'), array('facture', '', ''), 'facture', array(
			'ref_client' => array($text, 'RefCustomer'), 'note_public' => array($text, 'NotePublic'), 'note_private' => array($text, 'NotePrivate'),
			'date_lim_reglement' => array($date, 'DateMaxPayment'), 'fk_project' => array($project, 'Project')
		));
		$this->enrichments['supplier_invoices']['methods']['put'] = $this->headerUpdateMethod('supplier invoice', 'FactureFournisseur', array('fournisseur', 'facture', 'creer'), array('fournisseur', 'facture', 'lire'), array('fournisseur', 'facture_fourn', 'facture'), 'facture_fourn', array(
			'ref_supplier' => array($text, 'RefSupplier'), 'label' => array($text, 'Label'), 'note_public' => array($text, 'NotePublic'), 'note_private' => array($text, 'NotePrivate'),
			'date_echeance' => array($date, 'DateMaxPayment'), 'fk_project' => array($project, 'Project')
		));
		$this->enrichments['supplier_proposals']['methods']['put'] = $this->headerUpdateMethod('supplier proposal', 'SupplierProposal', array('supplier_proposal', 'creer'), array('supplier_proposal', 'lire'), array('supplier_proposal', '', ''), 'supplier_proposal', array(
			'note_public' => array($text, 'NotePublic'), 'note_private' => array($text, 'NotePrivate'),
			'delivery_date' => array($date, 'DeliveryDate'), 'fk_project' => array($project, 'Project')
		));
		$this->enrichments['contracts']['methods']['put'] = $this->headerUpdateMethod('contract', 'Contrat', array('contrat', 'creer'), array('contrat', 'lire'), array('contrat', '', ''), 'contrat', array(
			'ref_customer' => array($text, 'RefCustomer'), 'ref_supplier' => array($text, 'RefSupplier'), 'note_public' => array($text, 'NotePublic'), 'note_private' => array($text, 'NotePrivate'),
			'fk_project' => array($project, 'Project')
		));
		$this->enrichments['interventions']['methods']['put'] = $this->headerUpdateMethod('intervention', 'Fichinter', array('ficheinter', 'creer'), array('ficheinter', 'lire'), array('ficheinter', '', ''), 'fichinter', array(
			'description' => array($text, 'Description'), 'ref_client' => array($text, 'RefCustomer'), 'note_public' => array($text, 'NotePublic'), 'note_private' => array($text, 'NotePrivate'),
			'fk_project' => array($project, 'Project')
		));

		$this->enrichments['interventions']['methods']['post'] = array(
			'suffix' => 'create',
			'description' => "Create a DRAFT intervention (field service) for a third party. Give socid (rowid, or simply the name: it is resolved here) and description; fk_project (rowid or ref) is optional. The user confirms before anything is written.",
			'write' => array(
				'preview' => 'AIPreviewCreateIntervention',
				'required' => array('socid', 'description'),
				'object' => 'Fichinter',
				'thirdparty' => 'socid',
				'project' => 'fk_project',
				'args' => array('_who', 'description'),
				// Mandatory for the REST endpoint, even without a project.
				'defaults' => array('fk_project' => 0),
				'right' => array('ficheinter', 'creer')
			),
			'params' => array(
				'socid' => "Third party: rowid, or its name.",
				'description' => "What the intervention is about.",
				'fk_project' => "Project: rowid or ref. Optional."
			)
		);

		$this->enrichments['contracts']['methods']['post'] = array(
			'suffix' => 'create',
			'description' => "Create a DRAFT contract for a third party; its services are added in Dolibarr. Give socid (rowid, or simply the name: it is resolved here). The user confirms before anything is written.",
			'write' => array(
				'preview' => 'AIPreviewCreateContract',
				'required' => array('socid'),
				'object' => 'Contrat',
				'thirdparty' => 'socid',
				'args' => array('_who'),
				'dates' => array('date_contrat'),
				// Contrat::create() refuses without them; the card proposes the user.
				'defaults' => array('date_contrat' => 'now', 'commercial_signature_id' => 'me', 'commercial_suivi_id' => 'me'),
				'right' => array('contrat', 'creer')
			),
			'params' => array(
				'socid' => "Third party: rowid, or its name.",
				'ref_customer' => "The customer's own reference.",
				'date_contrat' => "Date of the contract YYYY-MM-DD. Omit for today.",
				'note_public' => "Public note."
			)
		);

		$this->enrichments['projects']['methods']['post'] = array(
			'suffix' => 'create',
			'description' => "Create a project, with its reference from the numbering. Give title; socid (rowid or simply the name) when the project is for a third party. The user confirms before anything is written.",
			'write' => array(
				'preview' => 'AIPreviewCreateProject',
				'required' => array('title'),
				'object' => 'Project',
				'thirdparty' => 'socid',
				'thirdparty_optional' => true,
				'args' => array('title', '_who'),
				'dates' => array('date_start', 'date_end'),
				'defaults' => array('ref' => 'auto', 'date_start' => 'now'),
				// The card makes its creator the project leader; without it a user
				// who cannot see every project could not see the one just made.
				'creator_contact' => 'PROJECTLEADER',
				'right' => array('projet', 'creer')
			),
			'params' => array(
				'title' => "Project title.",
				'socid' => "Third party: rowid, or its name. Optional.",
				'description' => "Description.",
				'date_start' => "Start date YYYY-MM-DD. Omit for today.",
				'date_end' => "End date YYYY-MM-DD.",
				'budget_amount' => "Budget."
			)
		);

		$this->enrichments['tasks']['methods']['post'] = array(
			'suffix' => 'create',
			'description' => "Create a task in a project, with its reference from the numbering. Give label and fk_project (rowid, ref or exact title of the project). The user confirms before anything is written.",
			'write' => array(
				'preview' => 'AIPreviewCreateTask',
				'required' => array('label', 'fk_project'),
				'object' => 'Task',
				'project' => 'fk_project',
				'args' => array('label', '_project'),
				'dates' => array('date_start', 'date_end'),
				'hours' => array('planned_workload'),
				'defaults' => array('ref' => 'auto'),
				'right' => array('projet', 'creer')
			),
			'params' => array(
				'label' => "Task label.",
				'fk_project' => "Project: rowid, ref or exact title.",
				'description' => "Description.",
				'planned_workload' => "Planned workload in hours.",
				'date_start' => "Start date YYYY-MM-DD.",
				'date_end' => "End date YYYY-MM-DD."
			)
		);
	}

	/**
	 * A setup constant read while the tools are declared. The tools are also
	 * declared outside a full Dolibarr environment (unit tests), where no
	 * setup is loaded: the constant then reads as not set.
	 *
	 * @param  string $name Constant name.
	 * @return string       Its value, or '' when not set or not loaded.
	 */
	private function setupValue($name)
	{
		return function_exists('getDolGlobalString') ? getDolGlobalString($name) : '';
	}

	/**
	 * Forget what the previous normaliseWriteArgs() call found, so a problem of
	 * one call is never reported for the next one.
	 *
	 * @return void
	 */
	private function resetWriteState()
	{
		$this->writeProblems = array();
		$this->writeRefusedFields = array();
		$this->writeTarget = null;
		$this->writeLine = array('line' => null, 'position' => 0, 'changes' => array());
	}

	/**
	 * Enrichment of the put method of a record: change some of its fields.
	 *
	 * @param  string               $label  Record name, as the model reads it.
	 * @param  string               $class  Business class of the record.
	 * @param  string[]             $right  Right needed to write.
	 * @param  array<string,string> $fields Fields that may be changed, with their doc.
	 * @param  string               $lookup How a non numeric id is found: 'thirdparty' (by name), 'ref', or '' (rowid only).
	 * @return array<string,mixed>          Method enrichment.
	 */
	private function updateMethod($label, $class, array $right, array $fields, $lookup)
	{
		$how = array('thirdparty' => "rowid, or its name", 'ref' => "rowid, or its exact ref", '' => "rowid");

		return array(
			'suffix' => 'update',
			'description' => "Change fields of an existing ".$label.". Give 'id' (".$how[$lookup].") and ONLY the fields to change, with their new value: ".implode(', ', array_keys($fields)).". No other field can be changed here. The user confirms before anything is written.",
			'write' => array(
				'kind' => 'update',
				'preview' => 'AIPreviewChangeRecord',
				'object' => $class,
				'label' => $label,
				'target' => 'id',
				'lookup' => $lookup,
				'fields' => array_keys($fields),
				'right' => $right
			),
			'params' => array_merge(array('id' => "The ".$label." to change: ".$how[$lookup]."."), $fields)
		);
	}

	/**
	 * Enrichment of the validate method of a document.
	 *
	 * @param  string               $label      Document name, as the model reads it.
	 * @param  string               $class      Business class of the document.
	 * @param  string               $preview    Translation key of the confirmation.
	 * @param  string[]             $right      Right needed to validate. With advanced permissions (a module, perm, subperm triple) the REST method still checks the module's 'creer', which is then required too.
	 * @param  array<string,mixed>  $fixed      Arguments the call always carries, whatever the model sends.
	 * @param  string               $stockconst Constant that makes the validation move stock, '' for none.
	 * @return array<string,mixed>              Method enrichment.
	 */
	private function validateMethod($label, $class, $preview, array $right, array $fixed, $stockconst)
	{
		$description = "Validate a DRAFT ".$label.": it gets its definitive reference and can no longer be edited. Give 'id' (rowid, or its ref, e.g. (PROV12)).";
		$params = array('id' => "The draft ".$label.": rowid, or its ref.");
		if ($stockconst !== '') {
			$description .= " When the validation moves stock, give 'warehouse' (name or rowid) unless there is only one.";
			$params['warehouse'] = "Warehouse the stock leaves from: name or rowid. Only needed when validating moves stock and there are several warehouses.";
		}
		$description .= " The user confirms before anything is written.";

		return array(
			'suffix' => 'validate',
			'description' => $description,
			'write' => array(
				'kind' => 'action',
				'preview' => $preview,
				'object' => $class,
				'label' => $label,
				'target' => 'id',
				'lookup' => 'ref',
				'fixed' => $fixed,
				'stock' => $stockconst,
				'right' => $right
			) + (count($right) === 3 ? array('read_right' => array($right[0], 'creer')) : array()),
			'params' => $params
		);
	}

	/**
	 * Enrichment of the delete method of a document, for its drafts only.
	 *
	 * @param  string              $label Document name, as the model reads it.
	 * @param  string              $class Business class of the document.
	 * @param  string[]            $right Right needed to delete.
	 * @return array<string,mixed>        Method enrichment.
	 */
	private function deleteMethod($label, $class, array $right)
	{
		return array(
			'suffix' => 'delete',
			'description' => "Delete a DRAFT ".$label." for good. Only a draft can be deleted here. Give 'id' (rowid, or its ref, e.g. (PROV12)). The user confirms before anything is written.",
			'write' => array(
				'kind' => 'delete',
				'preview' => 'AIPreviewDeleteDraft',
				'object' => $class,
				'label' => $label,
				'target' => 'id',
				'lookup' => 'ref',
				'right' => $right
			),
			'params' => array('id' => "The draft ".$label.": rowid, or its ref.")
		);
	}

	/**
	 * Enrichment of a line method of a draft document: change, delete or, for an
	 * intervention, add one line.
	 *
	 * @param  string   $action  'update', 'delete' or 'add'.
	 * @param  string   $label   Document name, as the model reads it.
	 * @param  string   $class   Business class of the document.
	 * @param  string[] $right   Right needed to write.
	 * @param  string   $profile Line shape: 'product', 'supplier', 'contract' or 'intervention'.
	 * @return array<string,mixed> Method enrichment.
	 */
	private function lineMethod($action, $label, $class, array $right, $profile)
	{
		$fields = ($profile === 'intervention') ? array(
			'desc' => "Text of the line.",
			'date' => "Date of the work, YYYY-MM-DD.",
			'duration' => "Time spent, in hours (1.5 for 1h30)."
		) : array(
			'qty' => "New quantity.",
			'subprice' => "New unit price excl. tax.",
			'remise_percent' => "New discount, in %.",
			'tva_tx' => "New VAT rate.",
			'desc' => "New text of the line.",
			'unit' => "New unit, by name (piece, kg, hour...)."
		);
		if ($profile === 'supplier') {
			unset($fields['desc']);	// FactureFournisseur::updateline() keeps the old text
		}
		$which = array(
			'line' => "The line: its position as shown on the document (1 = first), or its product ref or label.",
			'lineid' => "Rowid of the line, when known from a read of the document."
		);
		$params = array('id' => "The draft ".$label.": rowid, or its ref.");
		if ($action === 'add') {
			$description = "Add ONE line (work done) to a DRAFT ".$label.". Give 'id', desc, and date and duration. The user confirms before anything is written.";
			$params += $fields;
		} elseif ($action === 'update') {
			$description = "Change ONE line of a DRAFT ".$label.". Give 'id', the line ('line' or 'lineid') and ONLY what changes: ".implode(', ', array_keys($fields)).". The product of a line cannot be changed: delete the line and add another. The user confirms before anything is written.";
			$params += $which + $fields;
		} else {
			$description = "Delete ONE line of a DRAFT ".$label.". Give 'id' and the line ('line' or 'lineid'). The user confirms before anything is written.";
			$params += $which;
		}

		return array(
			'suffix' => $action.'_line',
			'description' => $description,
			'write' => array(
				'kind' => 'line',
				'line_action' => $action,
				'line_profile' => $profile,
				'preview' => ($action === 'add') ? 'AIPreviewAddInterventionLine' : ($action === 'update' ? 'AIPreviewChangeDocumentLine' : 'AIPreviewDeleteDocumentLine'),
				'object' => $class,
				'label' => $label,
				'target' => 'id',
				'lookup' => 'ref',
				'fields' => ($action === 'delete') ? array() : array_keys($fields),
				'right' => $right
			),
			'params' => $params
		);
	}

	/**
	 * Enrichment of the put method of a document header: the listed fields of a
	 * draft, confirmed with their old and new value.
	 *
	 * @param  string   $label       Document name, as the model reads it.
	 * @param  string   $class       Business class of the document.
	 * @param  string[] $right       Right needed to write.
	 * @param  string[] $readright   Right needed to read.
	 * @param  string[] $access      Feature, table and subfeature, as the REST class checks them.
	 * @param  string   $extrafields Element of its extra fields.
	 * @param  array<string,array{0:array<string,mixed>,1:string}> $fields Field => schema and label key.
	 * @return array<string,mixed> Method enrichment.
	 */
	private function headerUpdateMethod($label, $class, array $right, array $readright, array $access, $extrafields, array $fields)
	{
		$update = array();
		$labels = array();
		$dates = array();
		foreach ($fields as $name => $f) {
			$update[$name] = $f[0];
			$labels[$name] = $f[1];
			if (strpos((string) ($f[0]['description'] ?? ''), 'timestamp') !== false) {
				$dates[] = $name;
			}
		}
		$update['array_options'] = array('type' => 'object', 'description' => 'Extra fields by their options_ code, e.g. {"options_mycode": "value"}.');

		return array(
			'suffix' => 'update',
			'description' => "Update header fields of a DRAFT ".$label." after confirmation: ".implode(', ', array_keys($update)).". Identify it by id or exact ref. Omitted fields are preserved. Status, totals, third party and lines cannot be changed here.",
			'write' => array(
				'parent' => 'id', 'ref' => 'ref', 'object' => $class, 'label' => $label,
				'right' => $right, 'read_right' => $readright, 'access' => $access,
				'draft_only' => true,
				'preview' => 'AIPreviewUpdateRecord',
				'update' => $update,
				'preview_labels' => $labels,
				'preview_dates' => $dates,
				'preview_objects' => isset($update['fk_project']) ? array('fk_project' => 'Project') : array(),
				'extrafields' => $extrafields,
				'references' => isset($update['fk_project']) ? array('fk_project' => array('projet', 'projet', '')) : array()
			)
		);
	}

	/**
	 * Describe bounded edits of an existing project.
	 *
	 * @return array<string,mixed> Method enrichment
	 */
	private function projectUpdateMethod()
	{
		return [
			'suffix' => 'update',
			'description' => 'Partially update a draft or open project by exactly one positive id or exact ref. Omitted fields stay unchanged; empty text clears it, null clears a date. Project dates have day precision in the server timezone. The user confirms old-to-new values. No status, sharing, ownership, contacts, tasks or extrafield changes.',
			'write' => [
				'preview' => 'AIPreviewUpdateProject',
				'right' => ['projet', 'creer'], 'read_right' => ['projet', 'lire'],
				'parent' => 'id', 'ref' => 'ref', 'object' => 'Project',
				'access' => ['project', '', ''], 'project_update' => true,
				'trim_fields' => ['title', 'description', 'note_public', 'note_private'],
				'sanitize_fields' => ['title' => 'alphanohtml', 'description' => 'restricthtml', 'note_public' => 'restricthtml', 'note_private' => 'restricthtml'],
				'preview_labels' => ['title' => 'Label', 'description' => 'Description', 'note_public' => 'NotePublic', 'note_private' => 'NotePrivate', 'date_start' => 'DateStart', 'date_end' => 'DateEnd'],
				'preview_dates' => ['date_start', 'date_end'], 'preview_dateonly' => true,
				'compact_result' => true,
				'update' => [
					'title' => ['type' => 'string', 'minLength' => 1, 'maxLength' => 255],
					'description' => ['type' => 'string'], 'note_public' => ['type' => 'string'], 'note_private' => ['type' => 'string'],
					'date_start' => ['type' => ['integer', 'null'], 'minimum' => 1, 'description' => 'Unix timestamp, or null to clear. Day precision in the server timezone.'],
					'date_end' => ['type' => ['integer', 'null'], 'minimum' => 1, 'description' => 'Unix timestamp, or null to clear. Must not precede the resulting start date.']
				]
			]
		];
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
		require_once DOL_DOCUMENT_ROOT . '/core/lib/functions2.lib.php';	// dolGetModulesDirs(), getModuleDirForApiClass() — main.inc.php loads this only conditionally; api/index.php requires it explicitly for the same reason
		require_once DOL_DOCUMENT_ROOT . '/includes/restler/framework/Luracast/Restler/AutoLoader.php';
		$previousloaders = spl_autoload_functions();
		$loader = Luracast\Restler\AutoLoader::instance();
		spl_autoload_register($loader);
		require_once DOL_DOCUMENT_ROOT . '/api/class/api.class.php';
		require_once DOL_DOCUMENT_ROOT . '/api/class/api_access.class.php';
		// Luracast\Restler\AutoLoader::instance() (called here and by api_access.class.php) unregisters all the other autoloaders
		// (the Composer one, the one of PHPUnit...). Put them back: outside of the Restler HTTP runtime, the rest of the code still
		// needs them to load the classes that are not loaded yet.
		foreach ($previousloaders as $previousloader) {
			spl_autoload_register($previousloader);
		}
	}

	/**
	 * Discover the REST API endpoints of every enabled module.
	 *
	 * Mirrors the scan htdocs/api/index.php performs to register endpoints with
	 * Restler, so the bridge exposes exactly the API surface the REST layer
	 * exposes — external modules included — instead of a list maintained by hand.
	 *
	 * The walk is the same in both places: dolGetModulesDirs() gives the module
	 * directories, each mod*.class.php names a module, getModuleDirForApiClass()
	 * maps it to the directory holding its API classes, and every
	 * api_<key>.class.php there is one endpoint. A few modules are named
	 * differently in their descriptor and in isModEnabled(); those exceptions are
	 * copied from api/index.php rather than reinvented, so the two stay in step.
	 *
	 * Note the class name is resolved through class_exists(), which is
	 * case-insensitive in PHP: api_agendaevents.class.php yields the candidate
	 * "Agendaevents" and still matches the declared AgendaEvents. Reflection is
	 * then used to recover the real spelling for display.
	 *
	 * @return array<string, array{module:string, path:string, candidate:string, class:string, label:string}> Endpoints keyed by name
	 */
	private function discoverEndpoints(): array
	{
		$endpoints = [];

		foreach (dolGetModulesDirs() as $dir) {
			$handle = @opendir(dol_osencode($dir));
			if (!is_resource($handle)) {
				continue;
			}

			while (($file = readdir($handle)) !== false) {
				$regmod = [];
				if (!is_readable($dir.$file) || !preg_match("/^mod(.*)\\.class\\.php$/i", $file, $regmod)) {
					continue;
				}

				$module = strtolower($regmod[1]);
				$moduledirforclass = getModuleDirForApiClass($module);

				// Same descriptor-name to module-name exceptions as api/index.php.
				$modulenameforenabled = $module;
				if ($module == 'propale') {
					$modulenameforenabled = 'propal';
				} elseif ($module == 'supplierproposal') {
					$modulenameforenabled = 'supplier_proposal';
				} elseif ($module == 'ficheinter') {
					$modulenameforenabled = 'intervention';
				} elseif ($module == 'product' && !isModEnabled('product') && isModEnabled('service')) {
					$modulenameforenabled = 'service';
				}

				if (!isModEnabled($modulenameforenabled)) {
					continue;	// A disabled module exposes no tools.
				}

				$dir_part = dol_buildpath('/'.$moduledirforclass.'/class/');
				$handle_part = @opendir(dol_osencode($dir_part));
				if (!is_resource($handle_part)) {
					continue;
				}

				while (($file_searched = readdir($handle_part)) !== false) {
					if (in_array($file_searched, ['api_access.class.php', 'api_setup.class.php', 'api_login.class.php', 'api_status.class.php'], true)) {
						continue;	// Framework plumbing, not business endpoints.
					}
					$regapi = [];
					if (!is_readable($dir_part.$file_searched) || !preg_match("/^api_(.*)\\.class\\.php$/i", $file_searched, $regapi)) {
						continue;
					}

					$key = strtolower($regapi[1]);
					if (isset($endpoints[$key])) {
						continue;	// First module wins, as in the REST layer.
					}

					$endpoints[$key] = [
						'module' => $modulenameforenabled,
						'path' => $dir_part.$file_searched,
						'candidate' => str_replace('_', '', ucwords($regapi[1])),
						'class' => '',	// resolved lazily by resolveEndpointClass()
						'label' => $this->enrichments[$key]['label'] ?? $key,
					];
				}
				closedir($handle_part);
			}
			closedir($handle);
		}

		ksort($endpoints);

		return $endpoints;
	}

	/**
	 * Load an endpoint's api file and resolve its real class name (lazy, cached
	 * in $this->endpoints). class_exists() is case-insensitive, so the candidate
	 * "Agendaevents" matches the declared AgendaEvents; reflection then recovers
	 * the real spelling.
	 *
	 * @param string $key Endpoint key
	 * @return bool True when the class is resolved
	 */
	private function resolveEndpointClass(string $key): bool
	{
		if ($this->endpoints[$key]['class'] !== '') {
			return true;
		}
		require_once $this->endpoints[$key]['path'];
		$candidate = $this->endpoints[$key]['candidate'];
		$classname = '';
		if (class_exists($candidate.'Api')) {
			$classname = $candidate.'Api';
		} elseif (class_exists($candidate)) {
			$classname = $candidate;
		}
		if ($classname === '') {
			return false;	// api_xxx file without the matching class.
		}
		// $classname passed class_exists() above, so the constructor cannot throw.
		$reflection = new ReflectionClass($classname);
		$this->endpoints[$key]['class'] = $reflection->getName();

		// A key-only label ("paiements") is poor guidance for the model; take
		// the first line of the class docblock when no enrichment names it.
		if ($this->endpoints[$key]['label'] === $key) {
			$classdoc = (string) $reflection->getDocComment();
			if (preg_match('/\*\s+([^@\s\/*][^\n]*)/', $classdoc, $mlabel)) {
				// "API class for contacts" -> "contacts": keep the object, drop the boilerplate.
				$this->endpoints[$key]['label'] = trim(preg_replace('/^API class (for|of)\s+(the\s+)?/i', '', trim($mlabel[1])));
			}
		}

		return true;
	}

	/**
	 * Methods exposed for an endpoint carrying no hand-written entry.
	 *
	 * Deliberately the read-only pair, per the review that merged this bridge
	 * ("we must start with only few methods exposed"). Discovery widens which
	 * endpoints are reachable, never what may be done to them: a write method
	 * still has to be whitelisted consciously, behind a confirmation gate.
	 *
	 * @return array<string, array{}> Method name => empty enrichment
	 */
	private function defaultMethods(): array
	{
		return ['index' => [], 'get' => []];
	}

	/**
	 * Groups of tools to build: enrichment key => key of the discovered endpoint
	 * whose REST class it calls. An enrichment calls the endpoint of the same
	 * name, unless it names another one with 'endpoint' (several groups of tools
	 * can share one REST class). Ordered by endpoint, as discovered.
	 *
	 * @return array<string, string> Enrichment key => endpoint key
	 */
	private function toolGroups(): array
	{
		$groups = [];
		foreach (array_keys($this->endpoints) as $epkey) {
			foreach ($this->enrichments as $key => $enrichment) {
				if (($enrichment['endpoint'] ?? $key) === $epkey) {
					$groups[$key] = $epkey;
				}
			}
		}

		return $groups;
	}

	/**
	 * Arguments a tool forces on its call ('fixed_params' of its enrichment),
	 * whatever the caller sent: the documents area of a group of documents tools.
	 *
	 * @param  string              $toolName Tool name.
	 * @param  array<string,mixed> $args     Tool arguments as sent.
	 * @return array<string,mixed>           Arguments with the fixed ones set.
	 */
	private function applyFixedParams(string $toolName, array $args)
	{
		$meta = $this->methodMetaFor($toolName);
		foreach ((array) ($meta['fixed_params'] ?? array()) as $pname => $value) {
			$args[$pname] = $value;
		}

		return $args;
	}

	/**
	 * Apply the AI_MCP_API_BRIDGE_METHODS restriction. Intersection only: the
	 * constant can narrow what the whitelist exposes — on every endpoint,
	 * enriched or not — but can never add a method to it, so it can never turn
	 * on a write method behind the whitelist's back.
	 *
	 * @param array<string, array{suffix?:string, description?:string, params?:array<string,string>}> $methods Whitelisted methods
	 * @return array<string, array{suffix?:string, description?:string, params?:array<string,string>}> Restricted methods
	 */
	private function applyMethodRestriction(array $methods): array
	{
		$configured = getDolGlobalString('AI_MCP_API_BRIDGE_METHODS');
		if ($configured === '') {
			return $methods;
		}
		$allowed = array_map('trim', explode(',', $configured));
		return array_intersect_key($methods, array_flip($allowed));
	}

	/**
	 * Returns tool definitions derived from the enabled REST API endpoints.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		if ($this->defs !== null) {
			return $this->defs;
		}

		// Cross-request cache of the generated definitions: the directory scans
		// and the per-method reflection/docblock work below produce the same
		// result for a given set of enabled modules, so it is generated once
		// and reread from a JSON file until something relevant changes. The
		// routes and the endpoint map ride along because execute() needs them
		// (the endpoint class itself is still required lazily at call time).
		$cachettl = getDolGlobalInt('AI_MCP_BRIDGE_DEFS_CACHE_TTL', 86400);
		$cachefile = ($cachettl > 0) ? $this->defsCacheFile() : '';
		if ($cachefile !== '' && is_readable($cachefile) && (dol_now() - (int) filemtime($cachefile)) < $cachettl) {
			$payload = json_decode((string) file_get_contents($cachefile), true);
			if (is_array($payload) && isset($payload['defs'], $payload['routes'], $payload['endpoints'])
				&& is_array($payload['defs']) && is_array($payload['routes']) && is_array($payload['endpoints'])) {
				$this->defs = $payload['defs'];
				$this->routes = $payload['routes'];
				$this->endpoints = $payload['endpoints'];

				return $this->defs;
			}
		}

		$this->loadApiRuntime();

		$this->defs = [];
		$this->routes = [];
		$this->endpoints = $this->discoverEndpoints();

		foreach ($this->toolGroups() as $key => $epkey) {
			// Endpoint whitelist, per the review that merged the bridge in
			// #39856 ("we should add a whitelist of api we think it is enable
			// for ai"): a discovered endpoint is exposed only when it carries a
			// hand-written $enrichments entry. Discovery decides what is
			// reachable; the enrichment entry is the conscious line that makes
			// it exposed. Removing this guard means auto-exposing every enabled
			// module's endpoints — an explicit decision to make, not a default.
			if (!isset($this->enrichments[$key])) {
				continue;
			}
			// Method whitelist: only the listed methods are exposed — nothing
			// else, whatever reflection could find on the API class. Enriched
			// endpoints without a 'methods' key fall back to the read-only pair.
			$methods = $this->applyMethodRestriction($this->enrichments[$key]['methods'] ?? $this->defaultMethods());
			if (empty($methods) || !$this->resolveEndpointClass($epkey)) {
				continue;	// nothing left to expose, or api file without its class
			}
			$ep = $this->endpoints[$epkey];	// re-read: 'class' is now resolved
			$ep['label'] = $this->enrichments[$key]['label'] ?? $ep['label'];

			foreach ($methods as $method => $meta) {
				// A method may need an optional module beyond its endpoint's own
				// (e.g. products getVariants needs Variants): skip when off, so
				// the tool does not exist instead of failing opaquely.
				if (!empty($meta['module']) && !isModEnabled($meta['module'])) {
					continue;
				}
				// A method that only works in a mode of the setup (a price mode)
				// does not exist when that mode is off.
				if (!empty($meta['setup']) && !$this->setupAllows((array) $meta['setup'])) {
					continue;
				}
				if (!method_exists($ep['class'], $method)) {
					continue;	// whitelisted method absent in this Dolibarr version
				}
				$suffix = $meta['suffix'] ?? ($method === 'index' ? 'list' : strtolower($method));
				$toolname = 'api_' . $key . '_' . $suffix;
				$def = $this->buildToolDefinition($ep, $key, $method, $toolname, $meta);
				if ($def) {
					$def['categories'] = self::ENDPOINT_CATEGORIES[$key] ?? array('billing', 'commercial', 'thirdparty', 'stock', 'project', 'reporting');
					$this->defs[] = $def;
					$this->routes[$toolname] = [$key, $method];
				}
			}
		}

		if ($cachefile !== '') {
			$this->writeDefsCache($cachefile);
		}

		return $this->defs;
	}

	/**
	 * Whether one of these setup constants is on.
	 *
	 * @param  string[] $consts Constant names.
	 * @return bool
	 */
	private function setupAllows(array $consts)
	{
		foreach ($consts as $const) {
			if (getDolGlobalString($const)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Path of the definitions cache file for the CURRENT state, or '' when no
	 * writable temp directory exists. The state signature is part of the file
	 * name, so any relevant change - a module (de)activated, a Dolibarr
	 * upgrade, another entity, an edit of this file (which holds the
	 * enrichments), or a change of the DB-driven restrictions
	 * (AI_MCP_API_BRIDGE_METHODS) - simply points to a
	 * different file: no explicit invalidation hook to maintain, and an
	 * administrator RESTRICTING what the AI may reach takes effect on the
	 * very next request (review sonikf). External-module API updates that
	 * change none of these are covered by the TTL.
	 *
	 * @return string Absolute cache file path, or '' to skip caching
	 */
	private function defsCacheFile()
	{
		global $conf;

		$dir = '';
		if (!empty($conf->ai->multidir_temp[$conf->entity])) {
			$dir = $conf->ai->multidir_temp[$conf->entity];
		} elseif (!empty($conf->ai->dir_temp)) {
			$dir = $conf->ai->dir_temp;
		}
		if (empty($dir) || dol_mkdir($dir) < 0) {
			return '';
		}

		$modules = array_map('strval', array_values((array) $conf->modules));
		sort($modules);
		$signature = implode(',', $modules).'|'.DOL_VERSION.'|'.((int) $conf->entity).'|'.((int) @filemtime(__FILE__)).'|'.DOL_DOCUMENT_ROOT;
		// Security-relevant runtime restrictions live in the DATABASE, not in
		// this file: they must be part of the signature too, or restricting
		// them would silently keep serving the wider cached toolset for up to
		// a full TTL (review sonikf on the initial version).
		$signature .= '|'.getDolGlobalString('AI_MCP_API_BRIDGE_METHODS');
		// The setup the writes are declared with: the validation right and the
		// product fields an edit offers.
		foreach (array('MAIN_USE_ADVANCED_PERMS', 'PRODUCT_PRICE_UNIQ', 'PRODUIT_CUSTOMER_PRICES') as $const) {
			$signature .= '|'.$const.'='.getDolGlobalString($const);
		}
		// The setup modes some methods depend on: turning one on shows its tools at once.
		foreach ($this->enrichments as $meta) {
			foreach (($meta['methods'] ?? array()) as $mmeta) {
				foreach ((array) ($mmeta['setup'] ?? array()) as $const) {
					$signature .= '|'.$const.'='.getDolGlobalString($const);
				}
			}
		}

		return rtrim($dir, '/').'/bridge_tooldefs_'.md5($signature).'.json';
	}

	/**
	 * Persist the generated definitions/routes/endpoints, pruning cache files
	 * of previous states so stale signatures do not pile up. Written to a
	 * temporary name then renamed, so a concurrent reader never sees a
	 * truncated file.
	 *
	 * @param string $cachefile Target file from defsCacheFile()
	 * @return void
	 */
	private function writeDefsCache(string $cachefile)
	{
		$payload = json_encode(array('defs' => $this->defs, 'routes' => $this->routes, 'endpoints' => $this->endpoints));
		if (!is_string($payload)) {
			return;
		}
		foreach ((array) glob(dirname($cachefile).'/bridge_tooldefs_*.json') as $old) {
			if (is_string($old) && $old !== $cachefile) {
				@unlink($old);
			}
		}
		$tmpfile = $cachefile.'.tmp.'.getmypid();
		if (file_put_contents($tmpfile, $payload) !== false) {
			@rename($tmpfile, $cachefile);
		}
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
			// Exception for the two syntax-bearing params (sqlfilters, sortfield):
			// their API docblocks carry a thin per-endpoint example that would win
			// over — and hide — the full syntax contract (operators, and/or, the
			// unsupported IN, the datec/tms column names), so there the common doc
			// is APPENDED to the docblock description instead of being shadowed.
			$commondesc = (string) ($this->commonParamDocs[$pname] ?? '');
			if (isset($meta['params'][$pname])) {
				$pdesc = $meta['params'][$pname];
			} elseif (!empty($paramDocs[$pname]['desc'])) {
				$pdesc = $paramDocs[$pname]['desc'];
				if ($pname === 'sqlfilters' && $commondesc !== '') {
					$keptdesc = preg_replace('/.*Syntax example/', 'Example', rtrim($pdesc));
					$pdesc = $commondesc;
					if ($keptdesc) {
						$pdesc .= "\n".$keptdesc;
					}
				}
				if ($pname === 'sortfield' && $commondesc !== '') {
					$pdesc = rtrim((string) $pdesc, '. ').'. '.$commondesc;
				}
			} else {
				$pdesc = $commondesc;
			}
			// The API docblocks carry Restler's inline validation tags. They are
			// markup, not prose: left in place they reach the model as noise
			// ("... (example '1' or '1,2,3') {@pattern /^[0-9,]*$/i}"). Lift the
			// ones JSON Schema can express into real constraints, and drop the
			// rest from the text.
			$constraints = [];
			$pdesc = $this->liftInlineTags($pdesc, $ptype, $constraints);

			$prop = [
				'type' => $ptype,
				'description' => $pdesc
			];
			$prop += $constraints;
			if ($p->isOptional()) {
				try {
					$prop['default'] = ($pname == 'limit') ? self::BRIDGE_DEFAULT_LIMIT : $p->getDefaultValue();
				} catch (ReflectionException $e) {
					// keep without default
				}
			} else {
				$required[] = $pname;
			}
			$properties[$pname] = $prop;
		}

		// A create receives one request_data body, which tells the model nothing:
		// show the fields it accepts instead, flat, as the call is made. A line
		// added to an existing record ('parent') keeps the API's own parameters.
		if (!empty($meta['write']) && empty($meta['write']['parent']) && !empty($meta['params'])) {
			$properties = [];
			$required = [];
			foreach ($meta['params'] as $pname => $pdesc) {
				if ($pname === 'lines') {
					// Lines of a commercial document, unless the write declares its own
					// line fields (a reception names a warehouse and a cost price, not a VAT rate).
					$lineFields = !empty($meta['write']['line_fields']) ? $meta['write']['line_fields'] : [
						'product_ref' => ['type' => 'string', 'description' => 'Ref or label of a catalogue product.'],
						'desc' => ['type' => 'string', 'description' => 'Free text, for a line with no product.'],
						'qty' => ['type' => 'number'],
						'unit' => ['type' => 'string', 'description' => 'Unit as said, e.g. pieces, kg, hour. Omit to use the product unit.'],
						'subprice' => ['type' => 'number', 'description' => 'Unit price excl. tax. Omit to use the catalogue price.'],
						'tva_tx' => ['type' => 'number', 'description' => 'VAT rate. Omit to use the default rate.']
					];
					$properties[$pname] = [
						'type' => 'array',
						'description' => $pdesc,
						'items' => [
							'type' => 'object',
							'properties' => $lineFields
						]
					];
					continue;
				}
				$properties[$pname] = ['type' => 'string', 'description' => $pdesc];
			}
			foreach ((array) ($meta['write']['required'] ?? array()) as $pname) {
				$required[] = $pname;
			}
		}

		// Arguments the tool forces on the call are not the caller's to give.
		$fixed = array_keys((array) ($meta['fixed_params'] ?? array()));
		if ($fixed) {
			$properties = array_diff_key($properties, array_flip($fixed));
			$required = array_values(array_diff($required, $fixed));
		}

		if (!empty($meta['write'])) {
			$verb = 'Write to';
		} elseif ($method === 'index') {
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
		if (!empty($meta['write']['update'])) {
			$schema = $this->updateInputSchema($meta['write']);
		}

		// A write runs only once confirmed: the first call answers input_required
		// with a requestState (see McpHandler::checkWriteConfirmation()), and the
		// caller runs it by calling again with the same arguments plus it.
		if (!empty($meta['write'])) {
			$schema['properties']['requestState'] = array(
				'type' => 'string',
				'description' => 'Confirmation state returned with the input_required answer of a first call. Call again with the same arguments plus this value to run the write. Short-lived, one use.'
			);
		}

		$schema = self::normalizeInputSchema($schema);

		$description = $verb . ' ' . $ep['label'] . ' through the Dolibarr REST API (auto-generated tool). ' . $summary;
		// When an explicit description is given, it has priority on auto-generated description
		if (!empty($meta['description'])) {
			$description = $meta['description'];
		}

		return [
			'name' => $toolname,
			'description' => $description,
			'inputSchema' => $schema
		];
	}

	/**
	 * Lift Restler's inline validation tags out of a parameter description.
	 *
	 * The REST API documents constraints the way Restler reads them to build
	 * swagger.json: {@min 1}, {@max 100}, {@choice yes,no}, {@pattern /re/flags},
	 * {@const value}.
	 * Those carry exactly what JSON Schema calls minimum, maximum, enum and
	 * pattern, so they are translated instead of being shown to the model as
	 * part of the sentence. Tags with no JSON Schema equivalent ({@type} names a
	 * PHP class, {@from} names the HTTP source, which in-process calls have no
	 * use for) are removed from the text and otherwise ignored.
	 *
	 * @param ?string $desc Parameter description, as written in the docblock (may be null)
	 * @param string $ptype JSON Schema type already determined for this parameter
	 * @param array<string, mixed> $constraints Filled with the JSON Schema constraints found
	 * @return string The description with every inline tag removed
	 */
	private function liftInlineTags($desc, string $ptype, array &$constraints): string
	{
		$desc = (string) $desc;

		if (strpos($desc, '{@') === false) {
			return $desc;
		}

		$matches = [];
		if (preg_match_all('/\{@(\w[\w-]*)\s*([^}]*)\}/', $desc, $matches, PREG_SET_ORDER)) {
			foreach ($matches as $tag) {
				$name = strtolower($tag[1]);
				$value = trim($tag[2]);

				if ($name === 'min' && is_numeric($value)) {
					$constraints['minimum'] = $this->tagValueToNumber($value);
				} elseif ($name === 'max' && is_numeric($value)) {
					$constraints['maximum'] = $this->tagValueToNumber($value);
				} elseif ($name === 'choice' && $value !== '') {
					$choices = array_map('trim', explode(',', $value));
					if ($ptype === 'integer' || $ptype === 'number') {
						foreach ($choices as $i => $choice) {
							if (is_numeric($choice)) {
								$choices[$i] = $this->tagValueToNumber($choice);
							}
						}
					}
					$constraints['enum'] = array_values($choices);
				} elseif (($name === 'const' || $name === 'constant') && $value !== '') {
					$val = $value;
					if ($ptype === 'integer' || $ptype === 'number') {
						if (is_numeric($val)) {
							$val = $this->tagValueToNumber($val);
						}
					} elseif ($ptype === 'boolean') {
						$val = filter_var($val, FILTER_VALIDATE_BOOLEAN);
					}
					// JSON Schema const is rejected by Gemini and some LLM function-calling APIs;
					// normalize to a single-element enum instead.
					$constraints['enum'] = array($val);
				} elseif ($name === 'pattern' && $value !== '') {
					$pattern = $this->restlerPatternToJsonSchema($value);
					if ($pattern !== '') {
						$constraints['pattern'] = $pattern;
					}
				}
			}
		}

		// Remove every tag, including the ones left untranslated, then tidy the
		// whitespace the removal leaves behind.
		$desc = preg_replace('/\s*\{@\w[\w-]*[^}]*\}/', '', $desc);

		return trim(preg_replace('/\s{2,}/', ' ', (string) $desc));
	}

	/**
	 * Convert a numeric tag value to the PHP number JSON encodes as a number.
	 *
	 * {@min 0} must reach the model as 0, not "0": a JSON Schema minimum given
	 * as a string is not a minimum.
	 *
	 * @param string $value Numeric tag value, already checked with is_numeric()
	 * @return int|float
	 */
	private function tagValueToNumber(string $value)
	{
		return (strpos($value, '.') === false) ? (int) $value : (float) $value;
	}

	/**
	 * Convert a Restler {@pattern} value to a JSON Schema pattern.
	 *
	 * JSON Schema patterns are ECMA-262 regexps with no delimiters and no flags,
	 * so the PCRE delimiters are stripped. A flag that changes what the regexp
	 * accepts cannot be carried over; rather than silently tightening the
	 * constraint, the pattern is then dropped and only the description keeps the
	 * information. The one exception is /i on a regexp holding no letter, where
	 * the flag has nothing to act on.
	 *
	 * @param string $value Raw tag value, e.g. "/^[0-9,]*$/i"
	 * @return string JSON Schema pattern, or '' when it cannot be expressed
	 */
	private function restlerPatternToJsonSchema(string $value): string
	{
		$reg = [];
		if (!preg_match('/^(.)(.*)\1([a-zA-Z]*)$/s', $value, $reg)) {
			return '';	// not delimited: not a PCRE literal, leave it in the description
		}

		$expression = $reg[2];
		$flags = $reg[3];

		if ($flags !== '' && !($flags === 'i' && !preg_match('/[a-zA-Z]/', $expression))) {
			return '';
		}

		return $expression;
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
	 * Rights are enforced by the REST API classes themselves.
	 *
	 * @param string $toolName Tool being executed.
	 * @return array<int,array<int,string>>|string Rights triples for a write, or RIGHTS_ENFORCED_DOWNSTREAM for a read.
	 */
	public function getRequiredRights(string $toolName)
	{
		// A write declares its right here as well as relying on the REST class,
		// so the call is refused before the gate issues a confirmation: no
		// preview for a write the user could never perform.
		$meta = $this->methodMetaFor($toolName);
		$write = isset($meta['write']) ? $meta['write'] : array();
		if (!empty($write['right'])) {
			return empty($write['read_right']) ? array($write['right']) : array($write['right'], $write['read_right']);
		}

		// A read method may declare the rights it needs on top of the
		// endpoint's own base checks (products.getStock also needs
		// stock/lire). Declaring them keeps the tool out of tools/list for a
		// caller who lacks them, and refuses the call with a named right
		// instead of the bare 403 the API class would throw. Both shapes are
		// accepted: a single triple ['module','perm'] or a list of triples.
		if (!empty($meta['right'])) {
			$declared = (isset($meta['right'][0]) && is_array($meta['right'][0])) ? $meta['right'] : array($meta['right']);
			$rights = array();
			foreach ($declared as $triple) {
				if (!is_array($triple)) {
					continue;
				}
				$strings = array();
				foreach (array_values($triple) as $v) {
					$strings[] = (string) $v;
				}
				if ($strings) {
					$rights[] = $strings;
				}
			}
			if ($rights) {
				return $rights;
			}
		}

		return self::RIGHTS_ENFORCED_DOWNSTREAM;
	}

	/**
	 * VAT rate core would apply to a line, when the caller gave none.
	 *
	 * @param  int  $socid      Third party of the document.
	 * @param  int  $fk_product Product of the line, 0 for a free line.
	 * @param  bool $buy        True for a supplier document: the third party sells to us.
	 * @return float            Rate to write.
	 */
	private function defaultVatRate($socid, $fk_product, $buy = false)
	{
		global $mysoc;

		require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
		$soc = new Societe($this->db);
		if ($socid <= 0 || $soc->fetch($socid) <= 0 || !is_object($mysoc)) {
			return 0;
		}

		return (float) ($buy ? get_default_tva($soc, $mysoc, $fk_product) : get_default_tva($mysoc, $soc, $fk_product));
	}

	/**
	 * Unit purchase price of a product, for a supplier document line given by
	 * product ref: the sale price would be wrong there.
	 *
	 * @param  Product $prod  Product of the line.
	 * @param  int     $socid Supplier of the document, 0 if unknown.
	 * @param  float   $qty   Quantity of the line, for the price by quantity.
	 * @return float          Cheapest price of this supplier, else the cost price, else 0.
	 */
	private function defaultBuyPrice($prod, $socid, $qty)
	{
		require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
		$pf = new ProductFournisseur($this->db);
		if ($socid > 0 && $pf->find_min_price_product_fournisseur($prod->id, $qty, $socid) > 0) {
			return (float) $pf->fourn_unitprice;
		}

		return (float) $prod->cost_price;
	}

	/**
	 * Load a business record of one of the classes the writes declare.
	 *
	 * @param  string $class Business class declared by the write.
	 * @param  int    $id    Rowid of the record.
	 * @param  string $ref   Exact reference when no rowid was supplied.
	 * @return Facture|Commande|Propal|FactureFournisseur|CommandeFournisseur|SupplierProposal|Societe|Product|Reception|Expedition|Project|Contrat|Fichinter|null Record, or null when it cannot be read.
	 */
	protected function fetchBusinessObject($class, $id, $ref = '')
	{
		$paths = array(
			'Facture' => '/compta/facture/class/facture.class.php',
			'Commande' => '/commande/class/commande.class.php',
			'Propal' => '/comm/propal/class/propal.class.php',
			'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
			'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
			'SupplierProposal' => '/supplier_proposal/class/supplier_proposal.class.php',
			'Societe' => '/societe/class/societe.class.php',
			'Product' => '/product/class/product.class.php',
			'Reception' => '/reception/class/reception.class.php',
			'Expedition' => '/expedition/class/expedition.class.php',
			'Ticket' => '/ticket/class/ticket.class.php',
			'Project' => '/projet/class/project.class.php',
			'Contrat' => '/contrat/class/contrat.class.php',
			'Fichinter' => '/fichinter/class/fichinter.class.php'
		);
		if (empty($paths[$class]) || ((int) $id <= 0 && $ref === '')) {
			return null;
		}
		require_once DOL_DOCUMENT_ROOT.$paths[$class];
		$obj = new $class($this->db);
		'@phan-var-force Facture|Commande|Propal|FactureFournisseur|CommandeFournisseur|SupplierProposal|Societe|Product|Reception|Expedition|Project|Contrat|Fichinter $obj';
		$result = $ref === '' ? $obj->fetch((int) $id) : $obj->fetch((int) $id, $ref);
		if ($result > 0) {
			return $obj;
		}

		return null;
	}

	/**
	 * Ref of a record just created, for the confirmation shown to the user.
	 *
	 * @param  string $class Business class declared by the write.
	 * @param  int    $id    Rowid returned by the API.
	 * @return string        Ref, or empty when it cannot be read.
	 */
	private function refOfCreatedObject($class, $id)
	{
		$obj = $this->fetchRecord($class, $id);

		return $obj ? (string) $obj->ref : '';
	}

	/**
	 * A record linked by a header field, as the confirmation names it: its ref
	 * and label, or null (as the other fields print it) when the field is empty.
	 *
	 * @param  string $class Business class of the linked record.
	 * @param  mixed  $id    Rowid, or null.
	 * @return string        Readable name.
	 */
	private function previewObjectName($class, $id)
	{
		if (empty($id) || !is_numeric($id)) {
			return json_encode($id);
		}
		$obj = $this->fetchBusinessObject($class, (int) $id);
		if (!$obj) {
			return '#'.((int) $id);
		}
		$name = (string) ($obj->title ?? ($obj->label ?? ($obj->name ?? '')));

		return $obj->ref.($name !== '' ? ' - '.$name : '');
	}

	/**
	 * Record a line write goes into, for a write declaring a 'parent'.
	 *
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @param  array<string,mixed> $args  Arguments of the call.
	 * @return Facture|Commande|Propal|FactureFournisseur|CommandeFournisseur|SupplierProposal|Societe|Product|Project|Contrat|Fichinter|null Record, or null when not found.
	 */
	private function fetchWriteParent(array $write, array $args)
	{
		if (!empty($write['ref']) && !empty($write['object'])) {
			return $this->fetchBusinessObject($write['object'], (int) ($args[$write['parent']] ?? 0), (string) ($args[$write['ref']] ?? ''));
		}
		if (empty($write['parent']) || empty($write['object']) || !is_numeric($args[$write['parent']] ?? '')) {
			return null;
		}

		return $this->fetchBusinessObject($write['object'], (int) $args[$write['parent']]);
	}

	/**
	 * Schema of a partial update declared by an endpoint's write metadata.
	 *
	 * @param array<string,mixed> $write Write metadata
	 * @return array<string,mixed> JSON Schema
	 */
	private function updateInputSchema(array $write)
	{
		$properties = $write['update'];
		$properties[$write['parent']] = ['type' => 'integer', 'minimum' => 1];
		$properties[$write['ref']] = ['type' => 'string', 'minLength' => 1];
		$properties['request_data'] = ['oneOf' => [
			['type' => 'object', 'additionalProperties' => false, 'properties' => $write['update'], 'minProperties' => 1],
			['type' => 'string', 'description' => 'A JSON object containing only the permitted update fields: '.implode(', ', array_keys($write['update'])).'.']
		]];
		$changes = [['required' => ['request_data']]];
		foreach (array_keys($write['update']) as $field) {
			$changes[] = ['required' => [$field]];
		}
		return ['type' => 'object', 'additionalProperties' => false, 'properties' => $properties,
			'oneOf' => [['required' => [$write['parent']]], ['required' => [$write['ref']]]], 'anyOf' => $changes];
	}

	/**
	 * Check object visibility with the same resource arguments as REST.
	 *
	 * @param array<int,string> $access Resource, table and subfeature
	 * @param int $id Object ID
	 * @return bool Whether the acting user can access the object
	 */
	protected function canAccessWriteObject(array $access, $id)
	{
		return checkUserAccessToObject($this->user, [$access[0]], $id, $access[1], $access[2] === '' ? '' : [$access[2]], 'fk_soc');
	}

	/**
	 * Apply native project write authorization, not merely read-all visibility.
	 *
	 * @param Project $project Project already checked for visibility and entity
	 * @return bool Whether the acting user can edit the project
	 */
	protected function canWriteProject($project)
	{
		return $project->restrictedProjectArea($this->user, 'write') > 0;
	}

	/**
	 * Check resulting dates, project write access and native update side effects.
	 *
	 * @param array<string,mixed> $args Normalized update arguments
	 * @param Project $parent Existing record
	 * @return void
	 * @throws InvalidArgumentException When the edit is not safe
	 */
	private function validateProjectUpdate(array $args, $parent)
	{
		$project = $parent;
		if (!$project || !$this->canAccessWriteObject(['project', '', ''], (int) $project->id) || !$this->canWriteProject($project)) {
			throw new InvalidArgumentException('Access denied to project');
		}
		if (!in_array($project->status ?? null, [0, 1, '0', '1'], true)) {
			throw new InvalidArgumentException('Only draft or open projects can be edited by this tool');
		}
		$start = array_key_exists('date_start', $args) ? $args['date_start'] : $parent->date_start;
		$end = array_key_exists('date_end', $args) ? $args['date_end'] : $parent->date_end;
		if ($start && $end && $end < $start) {
			throw new InvalidArgumentException('End date must not precede start date');
		}
	}

	/**
	 * Normalise and validate partial updates without creation defaults.
	 *
	 * @param array<string,mixed> $args Incoming arguments
	 * @param array<string,mixed> $write Write metadata
	 * @return array<string,mixed> Flat arguments, with a resolved parent ID
	 * @throws InvalidArgumentException When the update cannot be applied
	 */
	private function normaliseUpdateArgs(array $args, array $write)
	{
		if (array_key_exists('request_data', $args)) {
			$body = $args['request_data'];
			if (is_string($body)) {
				$decoded = json_decode($body);
				$body = $decoded instanceof stdClass ? json_decode($body, true) : null;
			}
			if (!is_array($body) || !$body || array_filter(array_keys($body), 'is_int')) {
				throw new InvalidArgumentException('request_data must be a non-empty object');
			}
			if (array_diff_key($body, $write['update'])) {
				throw new InvalidArgumentException('request_data must contain only the permitted update fields');
			}
			unset($args['request_data']);
			foreach ($body as $key => $value) {
				if (array_key_exists($key, $args) && $args[$key] !== $value) {
					throw new InvalidArgumentException('Conflicting value for '.$key);
				}
			}
			$args = array_merge($body, $args);
		}
		$idkey = $write['parent'];
		$refkey = $write['ref'];
		$hasid = array_key_exists($idkey, $args);
		$hasref = array_key_exists($refkey, $args);
		if ($hasid === $hasref || ($hasid && (!is_int($args[$idkey]) || $args[$idkey] <= 0)) || ($hasref && (!is_string($args[$refkey]) || trim($args[$refkey]) === ''))) {
			throw new InvalidArgumentException('Supply one positive integer id or one exact ref');
		}
		$changes = array_diff_key($args, array_flip([$idkey, $refkey, 'requestState']));
		if (!$changes || array_diff_key($changes, $write['update'])) {
			throw new InvalidArgumentException('Supply only the permitted update fields, with at least one change');
		}
		foreach ($changes as $field => $value) {
			if (is_string($value) && in_array($field, $write['trim_fields'] ?? [], true)) {
				$value = trim($value);
				$changes[$field] = $args[$field] = $value;
			}
			if (is_string($value) && !empty($write['sanitize_fields'][$field]) && sanitizeVal($value, $write['sanitize_fields'][$field]) !== $value) {
				throw new InvalidArgumentException('Text would be changed by API sanitization: '.$field);
			}
			$types = (array) $write['update'][$field]['type'];
			$valid = ($value === null && in_array('null', $types, true))
				|| (is_string($value) && in_array('string', $types, true))
				|| (is_int($value) && in_array('integer', $types, true) && $value >= ($write['update'][$field]['minimum'] ?? 0))
				|| (is_array($value) && in_array('object', $types, true) && !array_filter(array_keys($value), 'is_int'));
			$definition = $write['update'][$field];
			if (is_string($value)) {
				$valid = $valid && dol_strlen($value) >= ($definition['minLength'] ?? 0) && dol_strlen($value) <= ($definition['maxLength'] ?? PHP_INT_MAX);
			}
			if (!$valid) {
				throw new InvalidArgumentException('Invalid value for '.$field);
			}
		}
		foreach ([$write['right'], $write['read_right']] as $right) {
			if (!$this->user || !call_user_func_array([$this->user, 'hasRight'], $right)) {
				throw new InvalidArgumentException('Not enough permissions');
			}
		}
		$parent = $this->fetchWriteParent($write, $args);
		if (!$parent) {
			throw new InvalidArgumentException('Record not found');
		}
		if (!$this->canAccessWriteObject($write['access'], (int) $parent->id)) {
			throw new InvalidArgumentException('Access denied to record');
		}
		if (!empty($write['draft_only']) && (int) $parent->status !== 0) {
			global $langs;
			$langs->load('other');
			throw new InvalidArgumentException($langs->transnoentitiesnoconv('AIWriteNotDraftEdit', (string) $write['label'], (string) $parent->ref));
		}
		if (!empty($write['project_update'])) {
			$this->validateProjectUpdate($args, $parent);
		}
		foreach (($write['references'] ?? []) as $field => $access) {
			if (!empty($changes[$field]) && !$this->canAccessWriteObject($access, (int) $changes[$field])) {
				throw new InvalidArgumentException('Access denied to '.$field);
			}
		}
		if (!empty($write['extrafields']) && isset($changes['array_options']) && !empty($this->db)) {
			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
			$extrafields->fetch_name_optionals_label((string) $write['extrafields']);
			$known = array_keys((array) ($extrafields->attributes[$write['extrafields']]['label'] ?? []));
			foreach (array_keys($changes['array_options']) as $key) {
				if (strpos((string) $key, 'options_') !== 0 || !in_array(substr((string) $key, 8), $known, true)) {
					throw new InvalidArgumentException('Unknown extra field '.$key.($known ? ' (known: options_'.implode(', options_', $known).')' : ' (this record has no extra fields)'));
				}
			}
		}
		unset($args[$refkey]);
		$args[$idkey] = (int) $parent->id;
		return $args;
	}

	/**
	 * Unit id from the word a user used for it: code, short label or label, in the
	 * user's language, singular or plural ("piece", "pieces", "pcs").
	 *
	 * @param	string	$name	Unit as written
	 * @return	int				Rowid of the unit, 0 when none or several match
	 */
	private function unitIdFromName($name)
	{
		require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';

		return aiUnitIdFromName($this->db, $name);
	}

	/**
	 * Normalise the arguments of a write before they are previewed or executed.
	 *
	 * A model may send the record as a request_data body (sometimes a JSON
	 * string) instead of flat fields, and may name line fields the way the
	 * custom tools do. Both are accepted here so the preview describes what the
	 * API will actually write.
	 *
	 * @param  array<string,mixed> $args  Arguments as received.
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @return array<string,mixed>        Flat arguments with REST field names.
	 */
	private function normaliseWriteArgs(array $args, array $write = array())
	{
		$this->unresolvedProducts = array();
		if (!empty($write['update'])) {
			return $this->normaliseUpdateArgs($args, $write);
		}

		if (isset($args['request_data'])) {
			$body = $args['request_data'];
			if (is_string($body)) {
				$decoded = json_decode($body, true);
				$body = is_array($decoded) ? $decoded : array();
			}
			if (is_array($body)) {
				// A model may nest the record under a key of its own naming
				// ({"thirdparty": {...}}): unwrap a lone array value rather than
				// passing the wrapper to the API, which sees no fields at all.
				if (count($body) === 1) {
					$only = reset($body);
					if (is_array($only) && !isset($body[0])) {
						$body = $only;
					}
				}
				unset($args['request_data']);
				$args = array_merge($body, $args);
			}
		}

		// A line added to an existing record: its fields come flat, next to the
		// record id. A model used to the create tools may still wrap it in a
		// one-line 'lines' array. The third party, needed for the default VAT
		// rate, is the record's.
		if (!empty($write['parent'])) {
			if (!empty($args['lines']) && is_array($args['lines']) && count($args['lines']) === 1 && is_array(reset($args['lines']))) {
				$line = reset($args['lines']);
				unset($args['lines']);
				$args = array_merge($line, $args);
			}
			if (!isset($args['qty']) && !isset($args['quantity'])) {
				$args['qty'] = 1;	// "add product X to the order" means one
			}
			$parent = $this->fetchWriteParent($write, $args);
			$this->resetWriteState();

			return $this->normaliseWriteLine($args, $this->socidOf($parent), !empty($write['buy']));
		}

		// A write on a record that exists.
		$this->resetWriteState();
		$kind = (string) ($write['kind'] ?? '');
		if (in_array($kind, array('update', 'action', 'delete', 'payment', 'line'), true)) {
			return $this->normaliseTargetArgs($args, $write);
		}
		if (!empty($write['lookup']) && $kind === '') {
			$args = $this->normaliseOneRecordArgs($args, $write);
		}

		// Record fields under the names a model is likely to use: the database
		// column (nom) rather than the API field (name), or the obvious synonym.
		$fieldAliases = array('nom' => 'name', 'company' => 'name', 'mail' => 'email', 'tel' => 'phone', 'telephone' => 'phone', 'libelle' => 'label', 'title' => 'label');
		foreach ($fieldAliases as $from => $to) {
			// A field the write itself names is never renamed (a project has a title).
			if (in_array($from, (array) ($write['required'] ?? array()), true) || in_array($from, (array) ($write['args'] ?? array()), true)) {
				continue;
			}
			if (isset($args[$from]) && !isset($args[$to]) && !is_array($args[$from])) {
				$args[$to] = $args[$from];
				unset($args[$from]);
			}
		}

		// The third party may arrive as a name: resolve it here rather than making
		// the model spend a turn on a lookup, which with one tool call per answer
		// means the write never happens.
		if (empty($args['socid']) || !is_numeric($args['socid'])) {
			$named = '';
			foreach (array('socid', 'thirdparty', 'company', 'customer', 'supplier') as $key) {
				if (!empty($args[$key]) && !is_numeric($args[$key])) {
					$named = (string) $args[$key];
					break;
				}
			}
			if ($named !== '') {
				$sql = "SELECT rowid FROM ".$this->db->prefix()."societe";
				$sql .= " WHERE entity IN (".getEntity('societe').")";
				$sql .= " AND (nom = '".$this->db->escape($named)."' OR code_client = '".$this->db->escape($named)."' OR code_fournisseur = '".$this->db->escape($named)."')";
				$sql .= " LIMIT 2";
				$resql = $this->db->query($sql);
				if ($resql && $this->db->num_rows($resql) === 1) {
					// One match only: several would mean guessing which company the
					// user meant, and the guess would only surface in the preview.
					$obj = $this->db->fetch_object($resql);
					$args['socid'] = (int) $obj->rowid;
				} else {
					// The name as people say it: the trade name, or some words of the
					// legal name in any order ("Anthropic" for "Anthropic Ireland,
					// Limited"). One match only, and only when the whole name given
					// is that phrase.
					global $user;
					require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';
					$named = trim($named);
					// No narrowing to customers or suppliers here: a company the user
					// may mean is never dropped silently. Several fit -> nothing is
					// resolved and writePrecheck() lists them for the user.
					$found = aiFindThirdpartiesInText($this->db, $user, $named);
					if (count($found) === 1 && $found[0]['start'] === 0 && $found[0]['end'] === strlen($named) && count($found[0]['candidates']) === 1) {
						$args['socid'] = (int) key($found[0]['candidates']);
					}
				}
			}
		}

		// A logistic document (reception, shipment): the header names a model is
		// likely to use, dates as text, and a warehouse given once for every line.
		$kind = (string) ($write['kind'] ?? '');
		$headerWarehouse = 0;
		if ($kind !== '') {
			$logisticAliases = array('supplier_ref' => 'ref_supplier', 'ref_fournisseur' => 'ref_supplier', 'delivery_note' => 'ref_supplier', 'customer_ref' => 'ref_customer', 'ref_client' => 'ref_customer', 'delivery_date' => 'date_delivery', 'date' => 'date_delivery');
			foreach ($logisticAliases as $from => $to) {
				if (isset($args[$from]) && !isset($args[$to]) && !is_array($args[$from])) {
					$args[$to] = $args[$from];
					unset($args[$from]);
				}
			}
			// The business classes want timestamps, a model writes dates as text.
			foreach (array('date_delivery', 'date_reception') as $datefield) {
				if (isset($args[$datefield]) && !is_numeric($args[$datefield])) {
					$args[$datefield] = $this->writeDate($datefield, $args[$datefield]);
					if ($args[$datefield] === null) {
						unset($args[$datefield]);
					}
				}
			}
			foreach (array('warehouse', 'warehouse_id', 'fk_warehouse', 'entrepot') as $key) {
				if (!empty($args[$key]) && !is_array($args[$key]) && $kind === 'reception') {
					// A warehouse that was named must exist and be open: never the default instead.
					$headerWarehouse = $this->resolveWarehouse((string) $args[$key]);
				}
				unset($args[$key]);	// the API reads the warehouse on each line, never on the header
			}
		}

		if (!empty($args['lines']) && is_array($args['lines'])) {
			foreach ($args['lines'] as $i => $line) {
				if (is_array($line)) {
					$args['lines'][$i] = $this->normaliseWriteLine($line, is_numeric($args['socid'] ?? '') ? (int) $args['socid'] : 0, !empty($write['buy']), $kind, $headerWarehouse);
				}
			}
		}

		$args = $this->normaliseCreateExtras($args, $write);

		// Names for the confirmation sentence; '_' arguments never reach the API.
		if (!empty($write['thirdparty'])) {
			global $langs;
			$args['_who'] = '';
			$args['_at'] = '';
			$socid = (int) (is_numeric($args[$write['thirdparty']] ?? '') ? $args[$write['thirdparty']] : 0);
			// No database when the tools are only declared (unit tests): nothing to name.
			$soc = ($socid > 0 && $this->db) ? $this->fetchRecord('Societe', $socid) : null;
			if ($soc instanceof Societe) {
				$langs->load('other');
				$args['_who'] = ' '.$langs->trans('AIPreviewForThirdparty', $soc->name);
				$args['_at'] = ' '.$langs->trans('AIPreviewAtThirdparty', $soc->name);
			}
		}
		if (($write['object'] ?? '') === 'Contact') {
			$args['_name'] = trim(((string) ($args['firstname'] ?? '')).' '.((string) ($args['lastname'] ?? '')));
		}

		return $args;
	}

	/**
	 * Normalise one document line: field names, product given by ref or label,
	 * default VAT rate.
	 *
	 * @param  array<string,mixed> $line             Line as received.
	 * @param  int                 $socid            Third party of the document, 0 if unknown.
	 * @param  bool                $buy              True for a supplier document: purchase price and VAT.
	 * @param  string              $kind             '' for a commercial document, 'reception' or 'shipment' for a logistic one (warehouse, batch, cost price; no VAT).
	 * @param  int                 $defaultWarehouse Warehouse given on the header, for the lines that name none.
	 * @return array<string,mixed>                   Line with REST field names.
	 */
	private function normaliseWriteLine(array $line, $socid, $buy = false, $kind = '', $defaultWarehouse = 0)
	{
		$aliases = array('quantity' => 'qty', 'unit_price' => 'subprice', 'price' => 'subprice', 'description' => 'desc', 'vat_rate' => 'tva_tx', 'product_id' => 'fk_product');
		if ($kind !== '') {
			$aliases += array('warehouse' => 'fk_entrepot', 'warehouse_id' => 'fk_entrepot', 'fk_warehouse' => 'fk_entrepot', 'entrepot' => 'fk_entrepot', 'entrepot_id' => 'fk_entrepot', 'lot' => 'batch', 'serial' => 'batch', 'lot_number' => 'batch');
		}
		foreach ($aliases as $from => $to) {
			if (isset($line[$from]) && !isset($line[$to])) {
				$line[$to] = $line[$from];
				unset($line[$from]);
			}
		}
		if ($kind === 'reception') {
			// The price of a received line is what it cost: whatever the model
			// called it, the API reads cost_price.
			foreach (array('subprice', 'buy_price', 'purchase_price', 'cost') as $from) {
				if (isset($line[$from]) && !isset($line['cost_price'])) {
					$line['cost_price'] = $line[$from];
				}
				unset($line[$from]);
			}
		}
		// A line given by product ref carries no price: resolve it so the
		// preview and the write use the catalogue product, as the REST
		// endpoint expects an id.
		if (empty($line['fk_product']) && !empty($line['product_ref'])) {
			require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
			$prod = new Product($this->db);
			$wanted = (string) $line['product_ref'];
			$found = ($prod->fetch(0, $wanted) > 0);
			if (!$found) {
				// A user names a product the way it appears on screen, which is
				// its label: accept that too, and only when it is not ambiguous,
				// so nothing is billed off a guess.
				$sql = "SELECT rowid FROM ".$this->db->prefix()."product";
				$sql .= " WHERE entity IN (".getEntity('product').")";
				$sql .= " AND (label = '".$this->db->escape($wanted)."' OR ref = '".$this->db->escape($wanted)."')";
				$sql .= " LIMIT 2";
				$resql = $this->db->query($sql);
				if ($resql && $this->db->num_rows($resql) === 1) {
					$obj = $this->db->fetch_object($resql);
					$found = ($prod->fetch((int) $obj->rowid) > 0);
				}
			}
			if (!$found && $buy && $socid > 0) {
				// A supplier document carries the SUPPLIER's references - a delivery
				// note almost never shows ours. Look the ref up in that supplier's
				// catalogue, and only there, so another supplier's "33303" never fits.
				$sql = "SELECT DISTINCT pfp.fk_product FROM ".$this->db->prefix()."product_fournisseur_price as pfp";
				$sql .= " WHERE pfp.entity IN (".getEntity('productsupplierprice').")";
				$sql .= " AND pfp.fk_soc = ".((int) $socid);
				$sql .= " AND pfp.ref_fourn = '".$this->db->escape($wanted)."'";
				$sql .= " LIMIT 2";
				$resql = $this->db->query($sql);
				if ($resql && $this->db->num_rows($resql) === 1) {
					$obj = $this->db->fetch_object($resql);
					$found = ($prod->fetch((int) $obj->fk_product) > 0);
				}
			}
			if ($found) {
				$line['fk_product'] = $prod->id;
				if (empty($line['fk_unit']) && !isset($line['unit']) && !empty($prod->fk_unit)) {
					$line['fk_unit'] = (int) $prod->fk_unit;	// the product is in hand: its unit costs nothing more
				}
				if (!isset($line['desc'])) {
					$line['desc'] = $prod->label;
				}
				if (!isset($line['subprice'])) {
					$line['subprice'] = $buy ? $this->defaultBuyPrice($prod, $socid, (float) ($line['qty'] ?? 1)) : $prod->price;
				}
			}
			if (!$found && !isset($line['desc'])) {
				// Unknown or ambiguous: keep what the user called it, so the
				// line reads "popularpt" instead of appearing blank.
				$line['desc'] = $wanted;
			}
			if (!$found) {
				$this->unresolvedProducts[$wanted] = $this->closeProductRefs($wanted, $buy ? $socid : 0);
			}
			unset($line['product_ref']);
		}
		// Unit: the one the user named else the product's own,
		// as the card would. A name matching no unit, or several, is dropped
		// rather than guessed.
		if (isset($line['unit']) || (isset($line['fk_unit']) && !is_numeric($line['fk_unit']))) {
			$unit = $this->unitIdFromName((string) ($line['unit'] ?? $line['fk_unit']));
			unset($line['unit'], $line['fk_unit']);
			if ($unit > 0) {
				$line['fk_unit'] = $unit;
			}
		}
		if (empty($line['fk_unit']) && !empty($line['fk_product'])) {
			$unitprod = $this->fetchBusinessObject('Product', (int) $line['fk_product']);
			if ($unitprod instanceof Product && !empty($unitprod->fk_unit)) {
				$line['fk_unit'] = (int) $unitprod->fk_unit;
			}
		}
		if ($kind !== '') {
			// A logistic line moves stock: it needs a warehouse, by name or rowid,
			// else the one given on the header, else the default one. No VAT, no
			// selling price on such a line.
			if ($kind === 'shipment') {
				// A shipment line without a source order is a free line, which core
				// records with no warehouse (as the shipment card does): none is
				// given, so none is announced in the confirmation either.
				unset($line['fk_entrepot']);
				$lotprod = empty($line['fk_product']) ? null : $this->fetchBusinessObject('Product', (int) $line['fk_product']);
				if (isModEnabled('productbatch') && $lotprod instanceof Product && $lotprod->hasbatch()) {
					global $langs;
					$langs->load('other');
					$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteShipmentLotProduct', $lotprod->ref);
				}
			} else {
				if (isset($line['fk_entrepot']) && (string) $line['fk_entrepot'] !== '' && !is_array($line['fk_entrepot'])) {
					// Named on the line, by name or rowid: an open warehouse, or refused.
					$line['fk_entrepot'] = $this->resolveWarehouse((string) $line['fk_entrepot']);
				}
				if (empty($line['fk_entrepot'])) {
					$line['fk_entrepot'] = $defaultWarehouse > 0 ? $defaultWarehouse : $this->defaultWarehouseId();
				}
				if (empty($line['fk_entrepot'])) {
					unset($line['fk_entrepot']);
				}
			}
			// No VAT and no selling price on a logistic line: a shipment line carries
			// no amount at all, a received line is valued by its cost price.
			unset($line['tva_tx'], $line['subprice']);
			if ($kind === 'reception') {
				// A received line without a price is valued at the supplier price of
				// the product, as the card would propose.
				if (!isset($line['cost_price']) && !empty($line['fk_product'])) {
					$costprod = $this->fetchBusinessObject('Product', (int) $line['fk_product']);
					if ($costprod instanceof Product) {
						$line['cost_price'] = $this->defaultBuyPrice($costprod, $socid, (float) ($line['qty'] ?? 1));
					}
				}
				// Lot dates arrive as text: the line class wants timestamps.
				foreach (array('eatby', 'sellby') as $datefield) {
					if (isset($line[$datefield]) && !is_numeric($line[$datefield])) {
						$line[$datefield] = $this->writeDate($datefield, $line[$datefield]);
						if ($line[$datefield] === null) {
							unset($line[$datefield]);
						}
					}
				}
			}

			return $line;
		}
		// A line with no rate is written at 0 %: ask core for the rate that
		// applies between the two companies for this product, which is what
		// the card would have filled in.
		if (!isset($line['tva_tx']) && $socid > 0) {
			$line['tva_tx'] = $this->defaultVatRate($socid, (int) ($line['fk_product'] ?? 0), $buy);
		}

		return $line;
	}

	/**
	 * A date written as text, as a timestamp. A date that cannot be read is
	 * refused before the confirmation, never dropped or replaced by today.
	 *
	 * @param  string $field Field name, for the message.
	 * @param  mixed  $value Date as written.
	 * @param  int    $gmt   1 to read it in GMT (logistic dates), 0 in the server time zone.
	 * @return int|null      Timestamp, or null when empty or unreadable.
	 */
	private function writeDate($field, $value, $gmt = 1)
	{
		global $langs;

		$text = trim((string) $value);
		if ($text === '') {
			return null;
		}
		$ts = dol_stringtotime($text, $gmt);
		if ($ts > 0) {
			return $ts;
		}
		$langs->load('other');
		$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteUnreadableDate', $text, $field);

		return null;
	}

	/**
	 * The lines of a reception or shipment as the confirmation lists them:
	 * quantity, product, and for a reception the warehouse and the cost.
	 *
	 * @param  array<int,mixed> $lines Normalised lines.
	 * @param  string           $kind  'reception' or 'shipment'.
	 * @return string                  Lines, separated by semicolons.
	 */
	private function previewLogisticLines(array $lines, $kind)
	{
		global $langs;

		require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
		$warehouses = array();
		$out = array();
		foreach (array_slice($lines, 0, 10) as $line) {
			if (!is_array($line)) {
				continue;
			}
			$product = empty($line['fk_product']) ? null : $this->fetchBusinessObject('Product', (int) $line['fk_product']);
			$name = ($product instanceof Product) ? $product->ref.' - '.$product->label : (string) ($line['desc'] ?? '');
			$text = price2num($line['qty'] ?? 1).' × '.dol_trunc($name, 50);
			if ($kind === 'reception') {
				$wid = (int) ($line['fk_entrepot'] ?? 0);
				if ($wid > 0 && !isset($warehouses[$wid])) {
					$warehouse = new Entrepot($this->db);
					$warehouses[$wid] = ($warehouse->fetch($wid) > 0) ? $warehouse->ref : '#'.$wid;
				}
				if ($wid > 0) {
					$text .= ' '.$langs->trans('AIPreviewIntoWarehouse', $warehouses[$wid]);
				}
				$text .= (isset($line['cost_price']) && (float) $line['cost_price'] > 0) ? ' @ '.price($line['cost_price']) : ' ('.$langs->trans('AIPreviewNoCostPrice').')';
				if (!empty($line['batch'])) {
					$text .= ', '.$langs->trans('Batch').' '.$line['batch'];
				}
			}
			$out[] = $text;
		}
		if (count($lines) > 10) {
			$out[] = '…';
		}

		return implode('; ', $out);
	}

	/**
	 * Warehouse a logistic line goes to when none is named: the user's default
	 * warehouse, else the one set up for the company, else the only active one.
	 *
	 * @return int Rowid, 0 when there is no obvious default.
	 */
	private function defaultWarehouseId()
	{
		if (!empty($this->user->fk_warehouse)) {
			return (int) $this->user->fk_warehouse;
		}
		if (getDolGlobalInt('MAIN_DEFAULT_WAREHOUSE') > 0) {
			return getDolGlobalInt('MAIN_DEFAULT_WAREHOUSE');
		}
		$resql = $this->db->query("SELECT rowid FROM ".$this->db->prefix()."entrepot WHERE entity IN (".getEntity('stock').") AND statut = 1 LIMIT 2");
		if ($resql && $this->db->num_rows($resql) === 1) {
			$obj = $this->db->fetch_object($resql);

			return (int) $obj->rowid;
		}

		return 0;
	}

	/**
	 * Preview of what a bridge write would do, for the confirmation gate.
	 *
	 * Read tools return NO_WRITE and run as before. A method declaring a
	 * 'write' block in its enrichment is gated: the sentence below is what the
	 * user approves, so it names the third party and the lines rather than
	 * echoing arguments.
	 *
	 * @param  string             $toolName Tool being called.
	 * @param  array<string,mixed> $args    Arguments as received.
	 * @return string                       Preview sentence, or McpTool::NO_WRITE.
	 */
	public function writeConfirmationPreview(string $toolName, array $args)
	{
		global $langs;

		$langs->load("other");

		$write = $this->writeMetaFor($toolName);
		if (empty($write)) {
			return McpTool::NO_WRITE;
		}
		$args = $this->applyFixedParams($toolName, $args);
		if (!empty($write['update'])) {
			try {
				$args = $this->normaliseWriteArgs($args, $write);
			} catch (InvalidArgumentException $e) {
				return $e->getMessage();
			}
			$parent = $this->fetchWriteParent($write, $args);
			if (!$parent) {
				return 'Record not found';
			}
			if (!empty($write['project_update'])) {
				$context = (string) $parent->title;
			} else {
				$supplier = $this->fetchBusinessObject('Societe', (int) $parent->socid);
				$context = $supplier ? $supplier->name : '';
			}
			$langs->load('sendings');
			$langs->load('bills');
			$changes = [];
			foreach ($write['update'] as $field => $definition) {
				if (array_key_exists($field, $args)) {
					$old = $parent->$field ?? null;
					$label = isset($write['preview_labels'][$field]) ? $langs->trans($write['preview_labels'][$field]) : $field;
					if (is_array($args[$field])) {
						foreach ($args[$field] as $key => $value) {
							$changes[] = $label.'.'.$key.': '.json_encode($old[$key] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES).' → '.json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
						}
					} elseif (!empty($write['preview_objects'][$field])) {
						// A linked record by its ref and label, "none" when unlinked.
						$changes[] = $label.': '.$this->previewObjectName((string) $write['preview_objects'][$field], $old).' → '.$this->previewObjectName((string) $write['preview_objects'][$field], $args[$field]);
					} else {
						$isdate = in_array($field, $write['preview_dates'] ?? [], true);
						$dateonly = !empty($write['preview_dateonly']);
						$format = $dateonly ? 'dayrfc' : 'standard';
						$tz = $dateonly ? 'tzserver' : 'gmt';
						$suffix = $dateonly ? '' : ' UTC';
						$oldvalue = $isdate && is_numeric($old) ? dol_print_date($old, $format, $tz).$suffix : json_encode($old, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
						$newvalue = $isdate && is_numeric($args[$field]) ? dol_print_date($args[$field], $format, $tz).$suffix : json_encode($args[$field], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
						$changes[] = $label.': '.$oldvalue.' → '.$newvalue;
					}
				}
			}
			return $langs->trans($write['preview'], $parent->ref, (string) $context, implode('; ', $changes));
		}

		$args = $this->normaliseWriteArgs($args, $write);

		$kind = (string) ($write['kind'] ?? '');
		if (in_array($kind, array('update', 'action', 'delete', 'payment', 'line'), true)) {
			return $this->previewTargetWrite($write, $args);
		}

		// A line added to an existing record names the product, the record and
		// the amount of the line.
		if (!empty($write['parent'])) {
			$parent = $this->fetchWriteParent($write, $args);
			$what = (string) ($args['desc'] ?? '');
			$product = $this->fetchBusinessObject('Product', (int) ($args['fk_product'] ?? 0));
			if ($product instanceof Product) {
				$what = $product->ref.' - '.$product->label;
			}
			$qty = (float) ($args['qty'] ?? 1);
			$total = $qty * (float) ($args['subprice'] ?? 0) * (1 - (float) ($args['remise_percent'] ?? 0) / 100);

			return $langs->trans($write['preview'], (string) $qty, dol_trunc($what, 60), ($parent ? $parent->ref : ''), price(price2num($total, 'MT')));
		}

		// A single record (third party, product, category, ticket) has no lines:
		// it names the arguments that make up its sentence, then every other
		// argument the call will write, so the human approves nothing they did
		// not get to see. Language keys opt in to that extra list with a
		// trailing %s; keys without it simply ignore the appended value.
		if (!empty($write['args'])) {
			$values = array();
			foreach ($write['args'] as $argname) {
				$values[] = dol_trunc($this->previewValue($write, $argname, $args[$argname] ?? '', $args), 80);
			}
			// Only the declared parameters reach the REST call (see execute()): an
			// internal '_' argument or one the call drops is never shown as written.
			$declared = (array) ($this->methodMetaFor($toolName)['params'] ?? array());
			$extra = array();
			foreach ($args as $argname => $argvalue) {
				if (in_array($argname, $write['args'], true) || !array_key_exists($argname, $declared) || is_array($argvalue) || (string) $argvalue === '') {
					continue;
				}
				$extra[] = $argname.'='.dol_trunc($this->previewValue($write, $argname, $argvalue, $args), 40);
			}
			$values[] = empty($extra) ? '' : ' ('.implode(', ', $extra).')';
			return $langs->trans($write['preview'], ...$values);
		}

		$who = '';
		$socid = (int) ($args[$write['thirdparty']] ?? 0);
		if ($socid > 0) {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$soc = new Societe($this->db);
			if ($soc->fetch($socid) > 0) {
				$who = ' '.$langs->trans("AIPreviewForThirdparty", $soc->name);
			}
		}

		$lines = isset($args[$write['lines']]) && is_array($args[$write['lines']]) ? $args[$write['lines']] : array();
		$total = 0.0;
		$priced = false;
		foreach ($lines as $line) {
			// A received line is valued at its cost price, a sold one at its selling price.
			$unitprice = $line['subprice'] ?? ($line['cost_price'] ?? '');
			if ($unitprice === '' || is_null($unitprice)) {
				continue;
			}
			$priced = true;
			$total += ((float) ($line['qty'] ?? 1)) * ((float) $unitprice);
		}
		$what = $langs->trans(count($lines) === 1 ? "AIPreviewLine" : "AIPreviewLines", (string) count($lines));
		if ($priced) {
			$what .= ', '.price($total);
		} elseif (($write['kind'] ?? '') !== 'shipment') {
			$what .= ', '.$langs->trans("AIPreviewFromCatalogue");	// a shipment line carries no price at all
		}
		if (($write['kind'] ?? '') !== '' && $lines) {
			// Goods are confirmed line by line: what, how many, and where they go.
			$what .= ': '.$this->previewLogisticLines($lines, (string) $write['kind']);
		}

		return $langs->trans($write['preview'], $who, $what);
	}

	/**
	 * One value of a confirmation sentence, the way the user knows it: the record
	 * an id stands for, hours rather than the seconds the API takes, a date as
	 * a date. Anything else is shown as given.
	 *
	 * @param  array<string,mixed> $write   The 'write' enrichment of the tool.
	 * @param  string              $argname Argument name.
	 * @param  mixed               $value   Its value, after normaliseWriteArgs().
	 * @param  array<string,mixed> $args    All the arguments (the source of a contact).
	 * @return string                       Text for the sentence.
	 */
	private function previewValue(array $write, $argname, $value, array $args)
	{
		global $langs;

		if (is_array($value)) {
			return '';
		}
		$value = (string) $value;
		if ($value !== '' && in_array($argname, (array) ($write['hours'] ?? array()), true) && is_numeric($value)) {
			return convertSecondToTime((int) $value, 'allhourmin');
		}
		if ($value !== '' && in_array($argname, (array) ($write['dates'] ?? array()), true) && is_numeric($value)) {
			return dol_print_date((int) $value, 'day');
		}
		$class = (string) ($write['object'] ?? '');
		// An argument naming a record other than the written one (the website a
		// page is created in) is resolved to what the user knows it by.
		$argsObjects = (array) ($write['args_objects'] ?? array());
		if (isset($argsObjects[$argname]) && is_numeric($value) && (int) $value > 0 && !empty($this->db)) {
			$obj = $this->fetchRecord((string) $argsObjects[$argname], (int) $value);
			return ($obj && $this->userCanReach((string) $argsObjects[$argname], (int) $obj->id)) ? $this->describeRecord($obj) : $value;
		}
		// The record the write is about, named only to a user who may see it.
		if ($class !== '' && $argname === (string) ($write['target'] ?? 'id') && is_numeric($value) && (int) $value > 0 && !empty($this->db)) {
			$obj = $this->fetchRecord($class, (int) $value);
			return ($obj && $this->userCanReach($class, (int) $obj->id)) ? $this->describeRecord($obj) : $value;
		}
		if ($argname === (string) ($write['thirdparty'] ?? '') && is_numeric($value) && (int) $value > 0 && !empty($this->db)) {
			$soc = $this->fetchRecord('Societe', (int) $value);
			return ($soc && $this->userCanReach('Societe', (int) $soc->id)) ? $this->describeRecord($soc) : $value;
		}
		if ($class === 'Ticket' && $argname === 'track_id' && $value !== '' && !empty($this->db)) {
			require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
			$ticket = new Ticket($this->db);
			if ($ticket->fetch(0, '', $value) > 0 && $this->userCanReach('Ticket', (int) $ticket->id)) {
				return $this->describeRecord($ticket);
			}
		}
		if ($argname === 'contactid' && is_numeric($value) && (int) $value > 0 && !empty($this->db)) {
			if ((string) ($args['source'] ?? 'external') === 'internal') {
				$u = new User($this->db);
				return ($u->fetch((int) $value) > 0) ? $u->getFullName($langs) : $value;
			}
			$contact = $this->fetchRecord('Contact', (int) $value);
			return ($contact && $this->userCanReach('Contact', (int) $contact->id)) ? $this->describeRecord($contact) : $value;
		}

		return $value;
	}

	/**
	 * Refuse, before any confirmation, a write that could only fail, so the user
	 * is never asked to approve it:
	 * - a document for a third party that is missing, unknown, or that several
	 *   third parties fit (the API would answer "Thirdparty with id=0 not found");
	 * - a line for a record that is missing or no longer a draft, or for an
	 *   unknown product.
	 *
	 * @param  string             $toolName Tool being called.
	 * @param  array<string,mixed> $args    Arguments as received.
	 * @return string                       Error message, or '' to proceed.
	 */
	public function writePrecheck(string $toolName, array $args)
	{
		global $langs, $user;

		$write = $this->writeMetaFor($toolName);
		if (empty($write)) {
			return '';
		}
		$args = $this->applyFixedParams($toolName, $args);

		try {
			$args = $this->normaliseWriteArgs($args, $write);
		} catch (InvalidArgumentException $e) {
			return $e->getMessage();
		}
		if (!empty($write['update'])) {
			return '';
		}

		$kind = (string) ($write['kind'] ?? '');
		if (in_array($kind, array('update', 'action', 'delete', 'payment', 'line'), true)) {
			return $this->precheckTargetWrite($write, $args);
		}
		// What the arguments already showed: a project that cannot be found, a
		// required field left empty (the third party is checked below).
		if (!empty($this->writeProblems)) {
			return $this->writeProblems[0];
		}
		foreach ((array) ($write['required'] ?? array()) as $field) {
			if ($field !== ($write['thirdparty'] ?? '') && (!isset($args[$field]) || $args[$field] === '' || $args[$field] === array())) {
				$langs->load('other');
				return $langs->transnoentitiesnoconv('AIWriteMissingField', $field);
			}
		}

		if (!empty($write['thirdparty'])) {
			$field = (string) $write['thirdparty'];
			$named = false;
			foreach (array($field, 'thirdparty', 'company', 'customer', 'supplier') as $key) {
				if (!empty($args[$key])) {
					$named = true;
				}
			}
			// A contact, a project or a ticket may be for no third party at all:
			// a third party the write does not require is checked only when one
			// was given. When it is required, any missing, non-numeric or
			// non-positive value stays refused.
			$optional = !empty($write['thirdparty_optional']) || !in_array($field, (array) ($write['required'] ?? array()), true);
			if ($optional && !$named) {
				$field = '';
			}
			// A rowid the user may not see is refused like an unknown one, before
			// a confirmation sentence could name it (the REST class would refuse
			// the write itself, but only after the user read the sentence).
			if ($field !== '' && is_numeric($args[$field] ?? '') && (int) $args[$field] > 0 && $this->user instanceof User && !$this->userCanReach('Societe', (int) $args[$field])) {
				$langs->load('other');
				return $langs->transnoentitiesnoconv('AIWriteThirdpartyNotFound', (string) ((int) $args[$field]));
			}
			if ($field !== '' && (empty($args[$field]) || !is_numeric($args[$field]) || (int) $args[$field] <= 0)) {
				$langs->load('other');
				$named = '';
				foreach (array($field, 'thirdparty', 'company', 'customer', 'supplier') as $key) {
					if (!empty($args[$key]) && is_string($args[$key]) && !is_numeric($args[$key])) {
						$named = trim($args[$key]);
						break;
					}
				}
				if ($named === '') {
					return $langs->transnoentitiesnoconv('AIWriteNoThirdparty');
				}

				require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';
				$names = array();
				foreach (aiFindThirdpartiesInText($this->db, $user, $named) as $mt) {
					foreach ($mt['candidates'] as $c) {
						if ($c['visible']) {
							$names[] = $c['name'].' (id '.$c['id'].')';
						}
					}
				}
				if (count($names) > 1) {
					return $langs->transnoentitiesnoconv('AIWriteSeveralThirdparties', $named, implode(', ', $names));
				}
				if (count($names) === 1) {
					// Part of the name fits one company, the rest does not: ask, never assume.
					return $langs->transnoentitiesnoconv('AIWriteThirdpartyDidYouMean', $named, $names[0]);
				}

				return $langs->transnoentitiesnoconv('AIWriteThirdpartyNotFound', $named);
			}
		}

		// A logistic document: nothing is asked of the user when the write would
		// fail anyway - standalone documents switched off, no line, a line with
		// no product to move, or no warehouse to receive into.
		$kind = (string) ($write['kind'] ?? '');
		if ($kind !== '') {
			$langs->load('other');
			$standaloneConst = ($kind === 'reception') ? 'RECEPTION_STANDALONE' : 'SHIPMENT_STANDALONE';
			if (!empty($args['origin_id']) || !empty($args['origin_type'])) {
				// These tools write free lines: from an order, the card is the way.
				return $langs->transnoentitiesnoconv('AIWriteLogisticFromOrder', $kind);
			}
			if (!getDolGlobalString($standaloneConst)) {
				return $langs->transnoentitiesnoconv('AIWriteStandaloneOff', $kind, $standaloneConst);
			}
			$lines = isset($args[$write['lines']]) && is_array($args[$write['lines']]) ? $args[$write['lines']] : array();
			if (empty($lines)) {
				return $langs->transnoentitiesnoconv('AIWriteNoLines');
			}
			if (!empty($this->unresolvedProducts)) {
				return $this->unresolvedProductsMessage();
			}
			if ($kind === 'reception') {
				foreach ($lines as $line) {
					if (!is_array($line) || empty($line['fk_product'])) {
						return $langs->transnoentitiesnoconv('AIWriteReceptionLineNeedsProduct', (string) (is_array($line) ? ($line['desc'] ?? '') : ''));
					}
					if (empty($line['fk_entrepot']) && !getDolGlobalString('STOCK_WAREHOUSE_NOT_REQUIRED_FOR_RECEPTIONS')) {
						return $langs->transnoentitiesnoconv('AIWriteNoWarehouse');
					}
				}
			}

			return '';
		}

		if (empty($write['parent'])) {
			return '';
		}

		$parent = $this->fetchWriteParent($write, $args);
		// A document the user may not see is answered like a missing one: no ref,
		// no status, no sentence naming it.
		if (!$parent || ($this->user instanceof User && !$this->userCanReach((string) $write['object'], (int) $parent->id))) {
			return "No record found with ".$write['parent']."=".((string) ($args[$write['parent']] ?? '')).". Look its rowid up by ref first.";
		}
		// Core only adds lines to a draft, and otherwise fails with no message.
		if ((int) $parent->status !== 0) {
			return "Record ".$parent->ref." is not a draft (status ".((int) $parent->status)."): lines can only be added to a draft. Nothing was written.";
		}
		if (!empty($this->unresolvedProducts)) {
			return $this->unresolvedProductsMessage();
		}

		return '';
	}

	/**
	 * References close to one that matched no product, for the user to pick from:
	 * the supplier's references when the document is a supplier one (the note
	 * says "33303", the catalogue has "33303-6"), else our own references.
	 *
	 * @param  string   $wanted Reference as written on the document.
	 * @param  int      $socid  Supplier whose catalogue is searched, 0 for a customer document.
	 * @return string[]         Up to five "ref = our ref (label)" strings, closest first.
	 */
	private function closeProductRefs($wanted, $socid)
	{
		$wanted = trim((string) $wanted);
		if (dol_strlen($wanted) < 3) {
			return array();
		}
		// "5553-987" on the note, "5553-12" and "5553-14" in the catalogue (a size
		// the note omits): try the stem before the last separator too. Then the
		// stem before a trailing number ("SEAFXR20" -> the SEAFXR family), so a
		// missing size still shows its siblings.
		$stems = array($wanted);
		if (preg_match('/^(.{3,})[-\/ .][^-\/ .]+$/', $wanted, $reg)) {
			$stems[] = $reg[1];
		}
		if (preg_match('/^(.{3,}?)\d+$/', $wanted, $reg)) {
			$stems[] = $reg[1];
		}
		$found = array();
		foreach ($stems as $stem) {
			if ($socid > 0) {
				$sql = "SELECT DISTINCT pfp.ref_fourn, p.ref, p.label FROM ".$this->db->prefix()."product_fournisseur_price as pfp";
				$sql .= " INNER JOIN ".$this->db->prefix()."product as p ON p.rowid = pfp.fk_product";
				$sql .= " WHERE pfp.entity IN (".getEntity('productsupplierprice').") AND pfp.fk_soc = ".((int) $socid);
				$sql .= " AND pfp.ref_fourn LIKE '".$this->db->escape($this->db->escapeforlike($stem))."%' ORDER BY pfp.ref_fourn LIMIT 6";
				$resql = $this->db->query($sql);
				while ($resql && ($obj = $this->db->fetch_object($resql))) {
					$found[$obj->ref_fourn] = $obj->ref_fourn.' = '.$obj->ref.' ('.dol_trunc((string) $obj->label, 30).')';
				}
			}
			if (empty($found)) {
				$sql = "SELECT ref, label FROM ".$this->db->prefix()."product";
				$sql .= " WHERE entity IN (".getEntity('product').") AND ref LIKE '".$this->db->escape($this->db->escapeforlike($stem))."%' ORDER BY ref LIMIT 6";
				$resql = $this->db->query($sql);
				while ($resql && ($obj = $this->db->fetch_object($resql))) {
					$found[$obj->ref] = $obj->ref.' ('.dol_trunc((string) $obj->label, 30).')';
				}
			}
			if (!empty($found)) {
				break;
			}
		}

		return array_slice(array_values($found), 0, 5);
	}

	/**
	 * Message refusing a write whose products could not be resolved, with the
	 * close references found for each, so the user (or the model) can pick one.
	 *
	 * @return string Message for the caller.
	 */
	private function unresolvedProductsMessage()
	{
		$parts = array();
		foreach ($this->unresolvedProducts as $wanted => $close) {
			$parts[] = "'".((string) $wanted)."'".(empty($close) ? '' : ' (close refs: '.implode(', ', $close).')');
		}

		return "Product ".implode(', ', $parts)." not found, or several products match it. A supplier reference is looked up in the catalogue of the resolved supplier only. Use a close ref if it is the right product, search with api_products_list, or create it with api_products_create first.";
	}

	/**
	 * Record of a write on an existing record, by rowid or by ref: the business
	 * documents of fetchBusinessObject() and the records only these writes use
	 * (contacts, projects, tasks, interventions, contracts, tickets).
	 *
	 * @param  string $class Business class declared by the write.
	 * @param  int    $id    Rowid of the record, 0 to look it up by ref.
	 * @param  string $ref   Ref of the record, used when no rowid is given.
	 * @return CommonObject|null Record, or null when it cannot be read.
	 */
	private function fetchRecord($class, $id, $ref = '')
	{
		$paths = array(
			'Facture' => '/compta/facture/class/facture.class.php',
			'Commande' => '/commande/class/commande.class.php',
			'Propal' => '/comm/propal/class/propal.class.php',
			'FactureFournisseur' => '/fourn/class/fournisseur.facture.class.php',
			'CommandeFournisseur' => '/fourn/class/fournisseur.commande.class.php',
			'SupplierProposal' => '/supplier_proposal/class/supplier_proposal.class.php',
			'Societe' => '/societe/class/societe.class.php',
			'Product' => '/product/class/product.class.php',
			'Contact' => '/contact/class/contact.class.php',
			'Project' => '/projet/class/project.class.php',
			'Task' => '/projet/class/task.class.php',
			'Ticket' => '/ticket/class/ticket.class.php',
			'Fichinter' => '/fichinter/class/fichinter.class.php',
			'Contrat' => '/contrat/class/contrat.class.php',
			'Reception' => '/reception/class/reception.class.php',
			'Expedition' => '/expedition/class/expedition.class.php',
			'WebsitePage' => '/website/class/websitepage.class.php',
			'Website' => '/website/class/website.class.php'
		);
		$ref = trim((string) $ref);
		if (empty($paths[$class]) || ((int) $id <= 0 && $ref === '')) {
			return null;
		}
		require_once DOL_DOCUMENT_ROOT.$paths[$class];
		$obj = new $class($this->db);
		'@phan-var-force Facture|Commande|Propal|FactureFournisseur|CommandeFournisseur|SupplierProposal|Societe|Product|Contact|Project|Task|Ticket|Fichinter|Contrat|Reception|Expedition|WebsitePage|Website $obj';
		if ((int) $id > 0) {
			$found = ($obj->fetch((int) $id) > 0);
		} elseif ($obj instanceof Contact || $obj instanceof Societe) {
			$found = false;	// no ref: found by name elsewhere, or by rowid
		} else {
			$found = ($obj->fetch(0, $ref) > 0);
		}

		return ($found && (int) $obj->id > 0) ? $obj : null;
	}

	/**
	 * Record a targeted write is about, from the id or the words the model sent.
	 *
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @param  mixed               $value Rowid, ref or name given by the model.
	 * @return CommonObject|null          Record, or null when none or several fit (the reason is in writeProblems).
	 */
	private function resolveWriteTarget(array $write, $value)
	{
		global $langs, $user;

		$class = (string) ($write['object'] ?? '');
		$label = (string) ($write['label'] ?? $class);
		if (is_numeric($value) && (int) $value > 0) {
			return $this->fetchRecord($class, (int) $value);
		}
		$value = trim((string) $value);
		if ($value === '') {
			return null;
		}

		$lookup = (string) ($write['lookup'] ?? '');
		if ($lookup === 'thirdparty') {
			// The same matching as the documents: trade name, words in any order.
			require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';
			$names = array();
			$ids = array();
			foreach (aiFindThirdpartiesInText($this->db, $user, $value) as $mt) {
				foreach ($mt['candidates'] as $cid => $c) {
					if ($c['visible'] && !isset($ids[$cid])) {
						$ids[$cid] = true;
						$names[] = $c['name'].' (id '.$cid.')';
					}
				}
			}
			if (count($ids) === 1) {
				return $this->fetchRecord($class, (int) key($ids));
			}
			if (count($ids) > 1) {
				$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteSeveralThirdparties', $value, implode(', ', $names));
			}

			return null;
		}
		if ($lookup === 'ref') {
			return $this->fetchRecord($class, 0, $value);
		}

		return null;
	}

	/**
	 * Whether the acting user may reach a record, with the same check the REST
	 * class makes before touching it (sales representative restriction, entity).
	 *
	 * @param  string $class Business class of the record.
	 * @param  int    $id    Rowid of the record.
	 * @return bool
	 */
	private function userCanReach($class, $id)
	{
		// Feature, table&shared element, second level feature: as each REST class passes them.
		$map = array(
			'Task' => array('task', '', ''),
			'Ticket' => array('ticket', '', ''),
			'Facture' => array('facture', '', ''),
			'Commande' => array('commande', '', ''),
			'Propal' => array('propal', '', ''),
			'FactureFournisseur' => array('fournisseur', 'facture_fourn', 'facture'),
			'CommandeFournisseur' => array('fournisseur', 'commande_fournisseur', 'commande'),
			'SupplierProposal' => array('supplier_proposal', '', ''),
			'Societe' => array('societe', '', ''),
			'Product' => array('product', '', ''),
			'Contact' => array('contact', 'socpeople&societe', ''),
			'Project' => array('project', '', ''),
			'Reception' => array('reception', '', ''),
			'Expedition' => array('expedition', '', ''),
			'Contrat' => array('contrat', '', ''),
			'Fichinter' => array('ficheinter', '', '')
		);
		if (!isset($map[$class])) {
			return true;
		}
		require_once DOL_DOCUMENT_ROOT.'/core/lib/security.lib.php';
		$m = $map[$class];

		return (bool) checkUserAccessToObject($this->user, array($m[0]), (int) $id, $m[1], ($m[2] !== '' ? array($m[2]) : ''), 'fk_soc', 'rowid');
	}

	/**
	 * Third party of a record, 0 for a record that has none (product, third party).
	 *
	 * @param  CommonObject|null $obj Record.
	 * @return int
	 */
	private function socidOf($obj)
	{
		$vars = is_object($obj) ? get_object_vars($obj) : array();

		return (int) ($vars['socid'] ?? 0);
	}

	/**
	 * Short description of a document for a confirmation: ref, third party, amount.
	 *
	 * @param  CommonObject $obj Record.
	 * @return string
	 */
	private function describeRecord($obj)
	{
		global $conf, $langs;

		if ($obj instanceof Societe) {
			return (string) $obj->name;
		}
		if ($obj instanceof Product) {
			return $obj->ref.' - '.$obj->label;
		}
		if ($obj instanceof Contact) {
			return $obj->getFullName($langs);
		}
		$parts = array((string) $obj->ref);
		// A task or a ticket is known by its label or subject more than its ref.
		if ($obj instanceof Task && (string) $obj->label !== '') {
			$parts[] = (string) $obj->label;
		}
		if ($obj instanceof Ticket && (string) $obj->subject !== '') {
			$parts[] = (string) $obj->subject;
		}
		if ($this->socidOf($obj) > 0) {
			require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
			$soc = new Societe($this->db);
			if ($soc->fetch($this->socidOf($obj)) > 0) {
				$parts[] = $soc->name;
			}
		}
		if (!empty($obj->total_ttc)) {
			$parts[] = price((float) $obj->total_ttc, 0, $langs, 1, -1, -1, $conf->currency);
		}

		return implode(' - ', $parts);
	}

	/**
	 * Open warehouse from the words of the user, or the only one there is.
	 *
	 * @param  mixed $value Name, ref or rowid given; empty when none was said.
	 * @return int          Rowid, 0 when none or several fit (the reason is in writeProblems).
	 */
	private function resolveWarehouse($value)
	{
		global $langs;

		$list = array();
		$sql = "SELECT rowid, ref, lieu FROM ".$this->db->prefix()."entrepot";
		$sql .= " WHERE entity IN (".getEntity('stock').") AND statut = 1";
		$sql .= " ORDER BY ref LIMIT 100";
		$resql = $this->db->query($sql);
		while ($resql && ($obj = $this->db->fetch_object($resql))) {
			$list[(int) $obj->rowid] = array((string) $obj->ref, (string) $obj->lieu);
		}
		$names = array();
		foreach ($list as $id => $w) {
			$names[] = $w[0].' (id '.$id.')';
		}

		return $this->pickOne($list, $value, $names, 'AIWriteWhichWarehouse', 'AIWriteWarehouseNotFound', 'AIWriteNoOpenWarehouse');
	}

	/**
	 * Open bank account from the words of the user, or the only one there is.
	 *
	 * @param  mixed $value Label, ref or rowid given; empty when none was said.
	 * @return int          Rowid, 0 when none or several fit (the reason is in writeProblems).
	 */
	private function resolveBankAccount($value)
	{
		$list = array();
		$sql = "SELECT rowid, ref, label FROM ".$this->db->prefix()."bank_account";
		$sql .= " WHERE entity IN (".getEntity('bank_account').") AND clos = 0";
		$sql .= " ORDER BY label LIMIT 100";
		$resql = $this->db->query($sql);
		while ($resql && ($obj = $this->db->fetch_object($resql))) {
			$list[(int) $obj->rowid] = array((string) $obj->label, (string) $obj->ref);
		}
		$names = array();
		foreach ($list as $id => $b) {
			$names[] = $b[0].' (id '.$id.')';
		}

		return $this->pickOne($list, $value, $names, 'AIWriteWhichBankAccount', 'AIWriteBankAccountNotFound', 'AIWriteNoBankAccount');
	}

	/**
	 * Active payment mode from the words of the user: its code, its label in the
	 * dictionary or in the user's language, or the usual words for it.
	 *
	 * @param  mixed $value Code, label, words or rowid given.
	 * @return int          Rowid in c_paiement, 0 when none fits (the reason is in writeProblems).
	 */
	private function resolvePaymentMode($value)
	{
		global $langs;

		$langs->load('bills');
		$synonyms = array('cash' => 'LIQ', 'espèces' => 'LIQ', 'check' => 'CHQ', 'cheque' => 'CHQ', 'chèque' => 'CHQ', 'transfer' => 'VIR', 'bank transfer' => 'VIR', 'wire' => 'VIR', 'virement' => 'VIR', 'card' => 'CB', 'credit card' => 'CB', 'debit card' => 'CB', 'carte' => 'CB', 'direct debit' => 'PRE');
		$list = array();
		$sql = "SELECT id, code, libelle FROM ".$this->db->prefix()."c_paiement";
		$sql .= " WHERE active = 1 AND entity IN (".getEntity('c_paiement').")";
		$sql .= " ORDER BY id LIMIT 100";
		$resql = $this->db->query($sql);
		while ($resql && ($obj = $this->db->fetch_object($resql))) {
			$list[(int) $obj->id] = array((string) $obj->code, (string) $obj->libelle, $langs->transnoentitiesnoconv('PaymentType'.$obj->code), $langs->transnoentitiesnoconv('PaymentTypeShort'.$obj->code));
		}
		$names = array();
		foreach ($list as $p) {
			$names[] = $p[2].' ('.$p[0].')';
		}
		$want = dol_strtolower(trim((string) $value));
		if (isset($synonyms[$want])) {
			$value = $synonyms[$want];
		}

		return $this->pickOne($list, $value, $names, 'AIWriteWhichPaymentMode', 'AIWritePaymentModeNotFound', '', false);
	}

	/**
	 * One entry of a short list from what the user said: its rowid, one of its
	 * names said in full, else the only entry whose name contains the words.
	 *
	 * @param  array<int,string[]> $list       Rowid => names it is known by.
	 * @param  mixed               $value      What the user said, empty when nothing.
	 * @param  string[]            $names      Names to list when asking.
	 * @param  string              $askKey     Translation key when nothing was said and several exist.
	 * @param  string              $missingKey Translation key when nothing fits what was said.
	 * @param  string              $noneKey    Translation key when the list is empty, '' to never refuse for that.
	 * @param  bool                $onlyOne    Take the only entry when nothing was said.
	 * @return int                             Rowid, 0 when none or several fit.
	 */
	private function pickOne(array $list, $value, array $names, $askKey, $missingKey, $noneKey, $onlyOne = true)
	{
		global $langs;

		$langs->load('other');
		if (empty($list)) {
			if ($noneKey !== '') {
				$this->writeProblems[] = $langs->transnoentitiesnoconv($noneKey);
			}
			return 0;
		}
		$value = trim((string) $value);
		if ($value === '') {
			if ($onlyOne && count($list) === 1) {
				return (int) key($list);
			}
			$this->writeProblems[] = $langs->transnoentitiesnoconv($askKey, implode(', ', $names));
			return 0;
		}
		if (is_numeric($value) && isset($list[(int) $value])) {
			return (int) $value;
		}
		$want = dol_strtolower($value);
		$contains = array();
		foreach ($list as $id => $known) {
			foreach ($known as $knownname) {
				$haystack = dol_strtolower((string) $knownname);
				if ($haystack === '') {
					continue;
				}
				if ($haystack === $want) {
					return (int) $id;
				}
				if (strpos($haystack, $want) !== false) {
					$contains[$id] = true;
				}
			}
		}
		if (count($contains) === 1) {
			return (int) key($contains);
		}
		$this->writeProblems[] = $langs->transnoentitiesnoconv($missingKey, $value, implode(', ', $names));

		return 0;
	}

	/**
	 * Arguments of a write on one existing record that is not a targeted write
	 * (a ticket reply, a ticket or task edit, a ticket contact): the record given
	 * by rowid or ref is found here, with the user's access, so a ref works as
	 * the id and an unknown record is refused before the confirmation. A
	 * contact is checked the same way, and a value another tool handles (a
	 * ticket closed or assigned) is pointed to that tool.
	 *
	 * @param  array<string,mixed> $args  Arguments as sent.
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @return array<string,mixed>        Arguments with the record rowid.
	 */
	private function normaliseOneRecordArgs(array $args, array $write)
	{
		global $langs;

		$langs->load('other');
		$class = (string) ($write['object'] ?? '');
		$label = (string) ($write['label'] ?? $class);
		$field = (string) ($write['target'] ?? 'id');
		$given = $args[$field] ?? ($args['ref'] ?? '');
		unset($args['ref']);
		$canCheck = !empty($this->db) && $this->user instanceof User;
		if ((string) $given !== '') {
			$obj = $canCheck ? $this->resolveWriteTarget($write, $given) : null;
			if ($canCheck && (!$obj || !$this->userCanReach($class, (int) $obj->id))) {
				$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteRecordNotFound', $label, (string) $given);
			} elseif ($obj) {
				$args[$field] = (int) $obj->id;
				if (!empty($write['track_from_target']) && empty($args['track_id']) && $obj instanceof Ticket) {
					$args['track_id'] = (string) $obj->track_id;
				}
			}
		} elseif (!empty($write['target_or']) && (string) ($args[$write['target_or']] ?? '') !== '') {
			if ($class === 'Ticket' && $canCheck) {
				require_once DOL_DOCUMENT_ROOT.'/ticket/class/ticket.class.php';
				$ticket = new Ticket($this->db);
				if ($ticket->fetch(0, '', (string) $args[$write['target_or']]) <= 0 || !$this->userCanReach('Ticket', (int) $ticket->id)) {
					$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteRecordNotFound', $label, (string) $args[$write['target_or']]);
				}
			}
		} elseif (!empty($write['target_or'])) {
			$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteMissingField', $field);
		}

		foreach ((array) ($write['refuse_values'] ?? array()) as $name => $rule) {
			if (!array_key_exists($name, $args) || (string) $args[$name] === '') {
				continue;
			}
			if ($rule['values'] === null || in_array((int) $args[$name], (array) $rule['values'], true)) {
				$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteUseOtherTool', $name, (string) $args[$name], (string) $rule['tool']);
			}
		}

		if (!empty($write['contact']) && $canCheck) {
			$source = ((string) ($args['source'] ?? 'external') === 'internal') ? 'internal' : 'external';
			$args['source'] = $source;
			$cid = (int) ($args['contactid'] ?? 0);
			if ($cid > 0) {
				if ($source === 'internal') {
					$u = new User($this->db);
					if ($u->fetch($cid) <= 0 || (int) $u->status !== 1) {
						$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteRecordNotFound', 'user', (string) $cid);
					}
				} else {
					$contact = $this->fetchRecord('Contact', $cid);
					if (!$contact || !$this->userCanReach('Contact', $cid)) {
						$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteRecordNotFound', 'contact', (string) $cid);
					}
				}
			}
			// The role by its code, as the dictionary of contact types has it.
			$type = strtoupper(trim((string) ($args['type'] ?? '')));
			if ($type !== '') {
				$codes = array();
				$sql = "SELECT code FROM ".$this->db->prefix()."c_type_contact";
				$sql .= " WHERE element = '".$this->db->escape((string) $write['contact'])."' AND source = '".$this->db->escape($source)."' AND active = 1";
				$resql = $this->db->query($sql);
				while ($resql && ($o = $this->db->fetch_object($resql))) {
					$codes[] = (string) $o->code;
				}
				if (!in_array($type, $codes, true)) {
					$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteUnknownRole', (string) $args['type'], implode(', ', $codes));
				}
				$args['type'] = $type;
			}
		}

		return $args;
	}

	/**
	 * Arguments of a write on an existing record (update, validation, deletion,
	 * payment): the record found from what the user said, and only what the
	 * REST method may receive.
	 *
	 * @param  array<string,mixed> $args  Arguments, request_data already unwrapped.
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @return array<string,mixed>        Arguments for the REST method.
	 */
	private function normaliseTargetArgs(array $args, array $write)
	{
		global $langs;

		$kind = (string) $write['kind'];
		$field = (string) ($write['target'] ?? 'id');
		$given = $args[$field] ?? ($args['ref'] ?? '');
		$this->writeTarget = $this->resolveWriteTarget($write, $given);
		$out = array($field => ($this->writeTarget ? (int) $this->writeTarget->id : $given));

		if ($kind === 'update') {
			$aliases = array('nom' => 'name', 'company' => 'name', 'mail' => 'email', 'tel' => 'phone', 'telephone' => 'phone', 'mobile' => 'phone_mobile', 'job' => 'poste', 'libelle' => 'label', 'title' => 'label');
			$this->writeRefusedFields = array();
			foreach ($args as $name => $value) {
				if (in_array($name, array($field, 'ref', 'requestState'), true) || substr((string) $name, 0, 1) === '_') {
					continue;
				}
				if (isset($aliases[$name]) && in_array($aliases[$name], $write['fields'], true) && !isset($args[$aliases[$name]])) {
					$name = $aliases[$name];
				}
				if (in_array($name, $write['fields'], true) && !is_array($value)) {
					$out[$name] = $value;
				} else {
					$this->writeRefusedFields[] = (string) $name;
				}
			}

			return $out;
		}

		if ($kind === 'action') {
			// An action taking more than its record (who to assign it to, cancel).
			foreach ((array) ($write['pass'] ?? array()) as $name) {
				if (array_key_exists($name, $args) && !is_array($args[$name])) {
					$out[$name] = ($args[$name] === 'me' && $this->user instanceof User) ? (int) $this->user->id : $args[$name];
				}
			}
			if (!empty($write['stock']) && $this->writeTarget && $this->validationMovesStock($write, $this->writeTarget)) {
				$out['idwarehouse'] = $this->resolveWarehouse($args['warehouse'] ?? ($args['idwarehouse'] ?? ''));
			}

			return $out;
		}

		if ($kind === 'payment') {
			$invoice = $this->writeTarget;
			$remain = ($invoice instanceof Facture) ? (float) $invoice->getRemainToPay() : 0.0;
			$amount = (isset($args['amount']) && $args['amount'] !== '') ? (float) price2num($args['amount'], 'MT') : $remain;
			$mode = $this->resolvePaymentMode($args['payment_mode'] ?? ($args['paymentid'] ?? ''));
			$account = isModEnabled('bank') ? $this->resolveBankAccount($args['bank_account'] ?? ($args['accountid'] ?? '')) : 0;
			$date = trim((string) ($args['date'] ?? ($args['datepaye'] ?? '')));
			$out['arrayofamounts'] = array((string) $out[$field] => array('amount' => (string) $amount, 'multicurrency_amount' => ''));
			$out['datepaye'] = ($date !== '' && dol_stringtotime($date) > 0) ? $date : dol_print_date(dol_now(), 'dayrfc');
			$out['paymentid'] = $mode;
			$out['accountid'] = $account;
			$out['num_payment'] = (string) ($args['num_payment'] ?? '');
			$out['comment'] = (string) ($args['comment'] ?? '');
			$out['chqemetteur'] = (string) ($args['issuer'] ?? ($args['chqemetteur'] ?? ''));
			$out['chqbank'] = (string) ($args['issuer_bank'] ?? ($args['chqbank'] ?? ''));
			$out['_amount'] = $amount;
			$out['_remain'] = $remain;
			if ($mode > 0 && dol_getIdFromCode($this->db, (string) $mode, 'c_paiement', 'id', 'code', 1) === 'CHQ' && $out['chqemetteur'] === '') {
				$langs->load('other');
				$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteChequeIssuer');
			}

			return $out;
		}

		if ($kind === 'line') {
			return $this->normaliseLineArgs($args, $write, $out);
		}

		return $out;	// delete: the record alone
	}

	/**
	 * Arguments of a line write on a draft: the line found from what the user
	 * said, and the full body the REST method needs, its unchanged values kept.
	 *
	 * @param  array<string,mixed> $args  Arguments as received.
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @param  array<string,mixed> $out   Arguments already resolved (the document id).
	 * @return array<string,mixed>        Arguments for the REST method.
	 */
	private function normaliseLineArgs(array $args, array $write, array $out)
	{
		global $langs;

		$action = (string) $write['line_action'];
		$profile = (string) $write['line_profile'];
		$doc = $this->writeTarget;
		$aliases = array('quantity' => 'qty', 'unit_price' => 'subprice', 'price' => 'subprice', 'pu_ht' => 'subprice', 'description' => 'desc', 'label' => 'desc', 'vat_rate' => 'tva_tx', 'discount' => 'remise_percent', 'fk_unit' => 'unit', 'line_id' => 'lineid', 'rowid' => 'lineid', 'position' => 'line', 'product_ref' => 'line', 'product' => 'line');
		$changes = array();
		foreach ($args as $name => $value) {
			if (in_array($name, array('id', 'ref', 'requestState'), true) || substr((string) $name, 0, 1) === '_') {
				continue;
			}
			$name = (isset($aliases[$name]) && !isset($args[$aliases[$name]])) ? $aliases[$name] : $name;
			if (in_array($name, array('line', 'lineid'), true) && $action !== 'add') {
				continue;
			}
			if (in_array($name, (array) $write['fields'], true) && !is_array($value)) {
				$changes[$name] = $value;
			} elseif ($name !== 'request_data') {
				$this->writeRefusedFields[] = (string) $name;
			}
		}

		$langs->load('other');
		$line = null;
		$position = 0;
		if ($action !== 'add' && $doc && (int) $doc->status === 0) {
			$wanted = $args['lineid'] ?? ($args['line_id'] ?? ($args['rowid'] ?? ''));
			$byid = ((string) $wanted !== '');
			if (!$byid) {
				$wanted = $args['line'] ?? ($args['position'] ?? ($args['product_ref'] ?? ($args['product'] ?? '')));
			}
			$w = dol_strtolower(trim((string) $wanted));
			$found = array();
			$exact = array();
			foreach (array_values((array) $doc->lines) as $i => $l) {
				if ($byid || is_numeric($wanted)) {
					// By rowid, or by its position on the document (1 = first).
					if ($byid ? ((int) $l->id === (int) $wanted) : ((int) $wanted === $i + 1)) {
						$found[$i + 1] = $l;
					}
					continue;
				}
				// By product: its ref exactly, else words of its label or text.
				$ref = dol_strtolower((string) ($l->product_ref ?? ($l->ref ?? '')));
				$words = dol_strtolower($this->lineName($l).' '.dol_string_nohtmltag((string) ($l->desc ?? ($l->description ?? ''))));
				if ($w !== '' && $ref === $w) {
					$exact[$i + 1] = $l;
				} elseif ($w !== '' && strpos($words, $w) !== false) {
					$found[$i + 1] = $l;
				}
			}
			if ($exact) {
				$found = $exact;
			}
			if ((string) $wanted === '') {
				$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteLineNotGiven', $this->linesSummary($doc));
			} elseif (count($found) === 1) {
				$position = (int) key($found);
				$line = reset($found);
			} else {
				$this->writeProblems[] = $langs->transnoentitiesnoconv(count($found) ? 'AIWriteLineSeveral' : 'AIWriteLineNotFound', (string) $wanted, $this->linesSummary($doc));
			}
		}

		// Values as the line stores them: hours in seconds, dates as timestamps, a unit by its rowid.
		if (isset($changes['unit'])) {
			$unit = $this->unitIdFromName((string) $changes['unit']);
			if ($unit <= 0) {
				$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteUnknownUnit', (string) $changes['unit']);
			}
			$changes['unit'] = $unit;
		}
		foreach (array('qty', 'subprice', 'remise_percent', 'tva_tx') as $num) {
			if (isset($changes[$num])) {
				$changes[$num] = (float) price2num($changes[$num]);
			}
		}
		if (isset($changes['duration'])) {
			$changes['duration'] = (int) round((float) price2num($changes['duration']) * 3600);
		}
		if (isset($changes['date']) && !is_numeric($changes['date'])) {
			$changes['date'] = $this->writeDate('date', $changes['date'], 0);
			if ($changes['date'] === null) {
				unset($changes['date']);
			}
		}
		$this->writeLine = array('line' => $line, 'position' => $position, 'changes' => $changes);

		if ($action === 'delete') {
			$out['lineid'] = $line ? (int) $line->id : 0;
			return $out;
		}
		if ($profile === 'intervention') {
			$body = array();
			foreach (array('desc' => 'description', 'date' => 'date', 'duration' => 'duration') as $from => $to) {
				if (array_key_exists($from, $changes)) {
					$body[$to] = $changes[$from];
				}
			}
			if ($action === 'add') {
				$body += array('date' => dol_now(), 'duration' => 0);
			} else {
				$out['lineid'] = $line ? (int) $line->id : 0;
			}
			$out['_body'] = $body;
			return $out;
		}

		// Every value the REST updateline() reads, from the line itself, so only what
		// the user changed is changed (most REST putLine() pass absent fields as null).
		$out['lineid'] = $line ? (int) $line->id : 0;
		$body = array();
		if ($line) {
			$keep = array('desc', 'label', 'subprice', 'qty', 'remise_percent', 'tva_tx', 'localtax1_tx', 'localtax2_tx', 'info_bits', 'date_start', 'date_end', 'product_type', 'fk_parent_line', 'fk_fournprice', 'pa_ht', 'special_code', 'fk_unit', 'ref_ext', 'rang', 'situation_percent', 'fk_product', 'ref_supplier', 'date_start_real', 'date_end_real');
			foreach ($keep as $field) {
				$body[$field] = $line->$field ?? null;
			}
			if ($profile === 'supplier') {
				$body['subprice'] = $line->pu_ht ?? ($line->subprice ?? null);
				$body['desc'] = $line->description ?? ($line->desc ?? null);
			} elseif ($profile === 'contract') {
				$body['desc'] = $line->description ?? ($line->desc ?? null);
				$body['fk_fourn_price'] = $line->fk_fournprice ?? null;
			}
			$body['array_options'] = is_array($line->array_options ?? null) ? $line->array_options : array();
			// A VAT code stays with its rate, else updateline() drops it.
			if (!array_key_exists('tva_tx', $changes) && !empty($line->vat_src_code) && strpos((string) $body['tva_tx'], '(') === false) {
				$body['tva_tx'] = $body['tva_tx'].' ('.$line->vat_src_code.')';
			}
		}
		foreach (array('qty', 'subprice', 'remise_percent', 'tva_tx', 'desc') as $field) {
			if (array_key_exists($field, $changes)) {
				$body[$field] = $changes[$field];
			}
		}
		if (array_key_exists('unit', $changes)) {
			$body['fk_unit'] = $changes['unit'];
		}
		$body['price_base_type'] = 'HT';
		$body['multicurrency_subprice'] = 0;	// recomputed from the unit price
		if ($profile === 'supplier') {
			$body['pu_ht'] = $body['subprice'] ?? null;
			$body['description'] = $body['desc'] ?? null;
			unset($body['subprice'], $body['desc']);
		}
		$out['_body'] = $body;

		return $out;
	}

	/**
	 * Lines of a document, as a numbered list for a message: position, product, quantity.
	 *
	 * @param  CommonObject $doc Document with its lines.
	 * @return string
	 */
	private function linesSummary($doc)
	{
		$list = array();
		foreach (array_values((array) $doc->lines) as $i => $l) {
			if ($i >= 15) {
				$list[] = '...';
				break;
			}
			$list[] = ($i + 1).': '.dol_trunc($this->lineName($l), 40).(isset($l->qty) ? ' x '.((float) $l->qty) : '');
		}

		return $list ? implode('; ', $list) : '-';
	}

	/**
	 * A line as the user knows it: product ref and label, else its text.
	 *
	 * @param  object $l Line.
	 * @return string
	 */
	private function lineName($l)
	{
		$ref = (string) ($l->product_ref ?? ($l->ref ?? ''));
		$label = (string) ($l->product_label ?? '');
		$text = trim(dol_string_nohtmltag((string) ($l->desc ?? ($l->description ?? ''))));

		return $ref !== '' ? $ref.($label !== '' ? ' - '.$label : '') : $text;
	}

	/**
	 * Whether validating this document moves stock, so a warehouse is needed.
	 *
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @param  CommonObject        $obj   Document to validate.
	 * @return bool
	 */
	private function validationMovesStock(array $write, $obj)
	{
		if (!isModEnabled('stock') || !getDolGlobalString((string) $write['stock'])) {
			return false;
		}
		foreach ((array) $obj->lines as $line) {
			if (!empty($line->fk_product) && (int) $line->product_type === 0) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Arguments of a create that names a project, or gives dates or hours.
	 *
	 * @param  array<string,mixed> $args  Arguments, third party already resolved.
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @return array<string,mixed>        Arguments for the REST create.
	 */
	private function normaliseCreateExtras(array $args, array $write)
	{
		global $langs;

		if (!empty($write['project'])) {
			$field = (string) $write['project'];
			$given = $args[$field] ?? ($args['project'] ?? '');
			unset($args['project']);
			if ($given !== '' && $given !== 0 && $given !== '0') {
				$project = $this->resolveWriteTarget(array('object' => 'Project', 'lookup' => 'ref'), $given);
				if (!$project && !is_numeric($given)) {
					// A project is often named by its title.
					$sql = "SELECT rowid FROM ".$this->db->prefix()."projet";
					$sql .= " WHERE entity IN (".getEntity('project').") AND title = '".$this->db->escape((string) $given)."'";
					$sql .= " LIMIT 2";
					$resql = $this->db->query($sql);
					if ($resql && $this->db->num_rows($resql) === 1) {
						$obj = $this->db->fetch_object($resql);
						$project = $this->fetchRecord('Project', (int) $obj->rowid);
					}
				}
				if ($project instanceof Project && $this->userCanReach('Project', (int) $project->id)) {
					$args[$field] = (int) $project->id;
					$args['_project'] = $project->ref.' - '.$project->title;
				} else {
					$langs->load('other');
					$this->writeProblems[] = $langs->transnoentitiesnoconv('AIWriteProjectNotFound', (string) $given);
				}
			}
		}
		foreach ((array) ($write['dates'] ?? array()) as $field) {
			if (isset($args[$field]) && $args[$field] !== 'now' && !is_numeric($args[$field])) {
				$args[$field] = $this->writeDate($field, $args[$field], 0);
				if ($args[$field] === null) {
					unset($args[$field]);
				}
			}
		}
		// Dates the REST method reads as text itself: checked here, 'today' as a date.
		foreach ((array) ($write['text_dates'] ?? array()) as $field) {
			if (!isset($args[$field]) || is_numeric($args[$field])) {
				continue;
			}
			if (in_array(dol_strtolower(trim((string) $args[$field])), array('today', 'now'), true)) {
				$args[$field] = dol_print_date(dol_now(), 'dayrfc', 'tzuserrel');
			} elseif ($this->writeDate($field, $args[$field]) === null) {
				unset($args[$field]);
			}
		}
		foreach ((array) ($write['hours'] ?? array()) as $field) {
			if (isset($args[$field]) && is_numeric($args[$field])) {
				$args[$field] = (int) round((float) $args[$field] * 3600);
			}
		}

		return $args;
	}

	/**
	 * Confirmation sentence of a write on an existing record.
	 *
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @param  array<string,mixed> $args  Normalised arguments.
	 * @return string
	 */
	private function previewTargetWrite(array $write, array $args)
	{
		global $langs;

		$obj = $this->writeTarget;
		$what = $obj ? $this->describeRecord($obj) : (string) ($args[$write['target'] ?? 'id'] ?? '');
		$kind = (string) $write['kind'];

		if ($kind === 'update') {
			$changes = array();
			foreach ((array) $write['fields'] as $name) {
				if (!array_key_exists($name, $args)) {
					continue;
				}
				$old = ($obj && isset($obj->$name) && !is_array($obj->$name)) ? (string) $obj->$name : '';
				$changes[] = $name.' "'.dol_trunc($old, 40).'" → "'.dol_trunc((string) $args[$name], 40).'"';
			}

			return $langs->trans($write['preview'], $write['label'], $what, implode(', ', $changes));
		}
		if ($kind === 'delete') {
			return $langs->trans($write['preview'], $write['label'], $what);
		}
		if ($kind === 'line') {
			return $this->previewLineWrite($write, $what);
		}
		if ($kind === 'payment') {
			$mode = (int) ($args['paymentid'] ?? 0);
			$code = $mode > 0 ? (string) dol_getIdFromCode($this->db, (string) $mode, 'c_paiement', 'id', 'code', 1) : '';
			$account = '';
			if ((int) ($args['accountid'] ?? 0) > 0) {
				require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';
				$acc = new Account($this->db);
				if ($acc->fetch((int) $args['accountid']) > 0) {
					$account = $acc->label;
				}
			}

			$how = $langs->transnoentitiesnoconv('AIPreviewPaymentHow', ($code !== '' ? $langs->transnoentitiesnoconv('PaymentType'.$code) : ''), $account, dol_print_date(dol_stringtotime((string) $args['datepaye']), 'day'));

			return $langs->trans($write['preview'], price((float) ($args['_amount'] ?? 0)), $what, $how);
		}

		// Other actions: who the record goes to, or the other way to end it.
		if (array_key_exists('fk_user_assign', $args) && !empty($write['preview_unassign'])) {
			if ((int) $args['fk_user_assign'] <= 0) {
				return $langs->trans($write['preview_unassign'], $what);
			}
			$assignee = new User($this->db);
			$name = ($assignee->fetch((int) $args['fk_user_assign']) > 0) ? $assignee->getFullName($langs) : (string) $args['fk_user_assign'];

			return $langs->trans($write['preview'], $what, $name);
		}
		if (!empty($args['cancel']) && !empty($write['preview_cancel'])) {
			return $langs->trans($write['preview_cancel'], $what);
		}

		// Validation: the warehouse is said when the stock moves.
		if (!empty($args['idwarehouse'])) {
			require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
			$wh = new Entrepot($this->db);
			if ($wh->fetch((int) $args['idwarehouse']) > 0) {
				$what .= ', '.$langs->trans('AIPreviewStockFrom', (string) $wh->ref);
			}
		}

		return $langs->trans($write['preview'], $what);
	}

	/**
	 * Confirmation sentence of a line write: the line by its position and product,
	 * each change from its old to its new value, and the new line total.
	 *
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @param  string              $what  The document, as describeRecord() names it.
	 * @return string
	 */
	private function previewLineWrite(array $write, $what)
	{
		global $langs;

		$langs->load('main');
		$langs->load('products');
		$langs->load('interventions');
		$line = $this->writeLine['line'];
		$changes = $this->writeLine['changes'];
		$position = (string) $this->writeLine['position'];
		$labels = array('qty' => 'Qty', 'subprice' => 'PriceUHT', 'remise_percent' => 'Discount', 'tva_tx' => 'VAT', 'desc' => 'Description', 'unit' => 'Unit', 'date' => 'Date', 'duration' => 'Duration');
		$text = array();
		foreach ($changes as $name => $new) {
			$old = $line ? ($line->{$name === 'unit' ? 'fk_unit' : ($name === 'subprice' && isset($line->pu_ht) ? 'pu_ht' : $name)} ?? null) : null;
			if ($name === 'desc') {
				$old = $line ? ($line->desc ?? ($line->description ?? '')) : '';
			}
			if ($name === 'unit') {
				$old = $this->unitLabel((int) $old);
				$new = $this->unitLabel((int) $new);
			} elseif ($name === 'subprice') {
				$old = ($old === null) ? '' : price((float) $old);
				$new = price((float) $new);
			} elseif ($name === 'duration') {
				$old = ($old === null) ? '' : convertSecondToTime((int) $old, 'allhourmin');
				$new = convertSecondToTime((int) $new, 'allhourmin');
			} elseif ($name === 'date') {
				$old = empty($old) ? '' : dol_print_date((int) $old, 'day');
				$new = dol_print_date((int) $new, 'day');
			} elseif (in_array($name, array('qty', 'remise_percent', 'tva_tx'), true)) {
				$old = ($old === null) ? '' : (string) ((float) $old);
				$new = (string) ((float) $new);
			} else {
				$old = dol_trunc(dol_string_nohtmltag((string) $old), 40);
				$new = dol_trunc(dol_string_nohtmltag((string) $new), 40);
			}
			$text[] = $langs->transnoentitiesnoconv($labels[$name]).' '.($write['line_action'] === 'add' ? '' : $old.' → ').$new;
		}

		if ($write['line_action'] === 'add') {
			return $langs->trans($write['preview'], $what, implode(', ', $text));
		}
		$name = $line ? dol_trunc($this->lineName($line), 60) : '';
		if ($write['line_action'] === 'delete') {
			$amount = ($line && isset($line->total_ht)) ? ', '.price((float) $line->total_ht) : '';
			return $langs->trans($write['preview'], $position, $what, $name.(isset($line->qty) ? ' x '.((float) $line->qty) : '').$amount);
		}
		// The new total of the line, as core will compute it before taxes.
		if ($line && (array_key_exists('qty', $changes) || array_key_exists('subprice', $changes) || array_key_exists('remise_percent', $changes))) {
			$qty = (float) ($changes['qty'] ?? $line->qty);
			$pu = (float) ($changes['subprice'] ?? ($line->pu_ht ?? $line->subprice));
			$rem = (float) ($changes['remise_percent'] ?? $line->remise_percent);
			$text[] = $langs->transnoentitiesnoconv('TotalHT').' '.price((float) ($line->total_ht ?? 0)).' → '.price(price2num($qty * $pu * (1 - $rem / 100), 'MT'));
		}

		return $langs->trans($write['preview'], $position, $what, $name, implode(', ', $text));
	}

	/**
	 * Label of a unit, as the user reads it.
	 *
	 * @param  int    $id Rowid of the unit, 0 for none.
	 * @return string
	 */
	private function unitLabel($id)
	{
		global $langs;

		if ($id <= 0 || empty($this->db)) {
			return '-';
		}
		$resql = $this->db->query("SELECT label, short_label FROM ".$this->db->prefix()."c_units WHERE rowid = ".((int) $id));
		$obj = $resql ? $this->db->fetch_object($resql) : null;

		return $obj ? $langs->transnoentitiesnoconv((string) $obj->label) : (string) $id;
	}

	/**
	 * Why a write on an existing record cannot run, or '' when it can.
	 *
	 * @param  array<string,mixed> $write The 'write' enrichment of the tool.
	 * @param  array<string,mixed> $args  Normalised arguments.
	 * @return string
	 */
	private function precheckTargetWrite(array $write, array $args)
	{
		global $langs;

		$langs->load('other');
		$obj = $this->writeTarget;
		$label = (string) $write['label'];
		$given = (string) ($args[$write['target'] ?? 'id'] ?? '');
		if (!empty($this->writeProblems)) {
			return $this->writeProblems[0];
		}
		if (!$obj || !$this->userCanReach((string) $write['object'], (int) $obj->id)) {
			return $langs->transnoentitiesnoconv('AIWriteRecordNotFound', $label, $given);
		}
		$kind = (string) $write['kind'];

		if ($kind === 'update') {
			if (!empty($this->writeRefusedFields)) {
				return $langs->transnoentitiesnoconv('AIWriteFieldsNotAllowed', implode(', ', $this->writeRefusedFields), implode(', ', (array) $write['fields']));
			}
			if (count($args) < 2) {
				return $langs->transnoentitiesnoconv('AIWriteNothingToChange');
			}

			return '';
		}
		if ($kind === 'action' && isset($write['states'])) {
			// An action other than a validation: allowed in the statuses it lists.
			if (!in_array((int) $obj->status, (array) $write['states'], true)) {
				return $langs->transnoentitiesnoconv((string) $write['state_error'], $label, (string) $obj->ref);
			}
			if (array_key_exists('fk_user_assign', $args) && (int) $args['fk_user_assign'] > 0) {
				$assignee = new User($this->db);
				if ($assignee->fetch((int) $args['fk_user_assign']) <= 0 || (int) $assignee->status !== 1) {
					return $langs->transnoentitiesnoconv('AIWriteRecordNotFound', 'user', (string) $args['fk_user_assign']);
				}
			}

			return '';
		}
		if ($kind === 'line') {
			if ((int) $obj->status !== 0) {
				return $langs->transnoentitiesnoconv('AIWriteNotDraftLines', $label, (string) $obj->ref);
			}
			if (!empty($this->writeRefusedFields)) {
				return $langs->transnoentitiesnoconv('AIWriteFieldsNotAllowed', implode(', ', $this->writeRefusedFields), implode(', ', (array) $write['fields']));
			}
			$changes = $this->writeLine['changes'];
			if ($write['line_action'] === 'update' && !$changes) {
				return $langs->transnoentitiesnoconv('AIWriteNothingToChange');
			}
			if ($write['line_action'] === 'add' && trim((string) ($changes['desc'] ?? '')) === '') {
				return $langs->transnoentitiesnoconv('AIWriteMissingField', 'desc');
			}
			if (array_key_exists('qty', $changes) && (float) $changes['qty'] <= 0) {
				return $langs->transnoentitiesnoconv('AIWriteLineQtyZero');
			}

			return '';
		}
		if ($kind === 'action' || $kind === 'delete') {
			if ((int) $obj->status !== 0) {
				return $langs->transnoentitiesnoconv($kind === 'action' ? 'AIWriteNotDraftValidate' : 'AIWriteNotDraftDelete', $label, (string) $obj->ref);
			}
			if ($kind === 'action' && count((array) $obj->lines) === 0) {
				return $langs->transnoentitiesnoconv('AIWriteValidateNoLines', $label, (string) $obj->ref);
			}

			return '';
		}
		if ($kind === 'payment') {
			$remain = (float) ($args['_remain'] ?? 0);
			if ((int) $obj->status !== 1 || !empty($obj->paye) || $remain <= 0) {
				return $langs->transnoentitiesnoconv('AIWriteInvoiceNotPayable', (string) $obj->ref);
			}
			$amount = (float) ($args['_amount'] ?? 0);
			if ($amount <= 0) {
				return $langs->transnoentitiesnoconv('AIWritePaymentAmountInvalid');
			}
			if ($amount > $remain) {
				return $langs->transnoentitiesnoconv('AIWritePaymentTooHigh', price($amount), (string) $obj->ref, price($remain));
			}
		}

		return '';
	}

	/**
	 * The 'write' enrichment of a tool, when it has one.
	 *
	 * @param  string $toolName Tool name as exposed.
	 * @return array<string,mixed> Empty when the tool is a read.
	 */
	private function writeMetaFor($toolName)
	{
		$meta = $this->methodMetaFor($toolName);

		return isset($meta['write']) ? $meta['write'] : array();
	}

	/**
	 * Find the enrichment of the method a tool name maps to.
	 *
	 * @param  string $toolName Tool name (api_<endpoint>_<suffix>).
	 * @return array<string,mixed>              Method enrichment, or array() when unknown.
	 */
	private function methodMetaFor($toolName)
	{
		foreach ($this->enrichments as $key => $meta) {
			foreach (($meta['methods'] ?? array()) as $method => $mmeta) {
				$suffix = $mmeta['suffix'] ?? ($method === 'index' ? 'list' : strtolower($method));
				if ($toolName === 'api_'.$key.'_'.$suffix) {
					return $mmeta;
				}
			}
		}

		return array();
	}

	/**
	 * Resolve 'me' sent as an argument value on user-referencing fields.
	 *
	 * The model may answer "assign this task to me" with
	 * fk_user_assign: 'me' — the same sentinel the write defaults already
	 * expand to the acting user. Only fields whose name references a user
	 * are touched: a literal 'me' in a text field (subject, label, note)
	 * is left alone.
	 *
	 * @param  array<string,mixed> $args Tool arguments as sent.
	 * @return array<string,mixed>      Arguments with 'me' resolved on user fields.
	 */
	private function resolveUserSentinels(array $args)
	{
		foreach ($args as $name => $value) {
			if ($value === 'me' && stripos((string) $name, 'user') !== false) {
				$args[$name] = (int) $this->user->id;
			} elseif (is_array($value)) {
				$args[$name] = $this->resolveUserSentinels($value);
			}
		}

		return $args;
	}

	/**
	 * Return categories this tool belongs to.
	 *
	 * @return array<string> List of categories
	 */
	public function getCategories(): array
	{
		// Union of the classifier categories of the whitelisted endpoints —
		// derived so it cannot drift, expressed in the classifier vocabulary
		// so query filtering keeps working (see ENDPOINT_CATEGORIES).
		$all = array();
		foreach (array_keys($this->enrichments) as $key) {
			$all = array_merge($all, self::ENDPOINT_CATEGORIES[$key] ?? array());
		}

		return array_values(array_unique($all));
	}
	/**
	 * Map a bridge endpoint key to the element type ExtraFields uses.
	 *
	 * @param string $key Endpoint key from the enrichment map.
	 * @return string ExtraFields element type, '' when the objects carry none.
	 */
	private function extrafieldsElementForEndpoint($key)
	{
		$map = array(
			'thirdparties' => 'societe',
			'contacts' => 'socpeople',
			'invoices' => 'facture',
			'supplierinvoices' => 'facture_fourn',
			'orders' => 'commande',
			'supplierorders' => 'commande_fournisseur',
			'proposals' => 'propal',
			'supplierproposals' => 'supplier_proposal',
			'products' => 'product',
			'contracts' => 'contrat',
			'interventions' => 'fichinter',
			'tickets' => 'ticket',
			'projects' => 'projet',
			'tasks' => 'project_task',
			'members' => 'adherent',
			'expensereports' => 'expensereport',
			'shipments' => 'expedition',
			'receptions' => 'reception',
			'agendaevents' => 'actioncomm',
			'warehouses' => 'stock',
			'categories' => 'categorie',
			'bankaccounts' => 'bank_account',
			'donations' => 'don',
			'holidays' => 'holiday',
			'boms' => 'bom_bom',
			'mos' => 'mrp_mo',
			'productlots' => 'product_lot',
			'knowledgemanagement' => 'knowledgemanagement_knowledgerecord',
			'memberstypes' => 'adherent_type',
			'partnerships' => 'partnership',
			'workstations' => 'workstation_workstation',
			'socialcontributions' => 'chargesociales',
			'variouspayments' => 'payment_various',
			'vatpayments' => 'tva'
		);

		return $map[$key] ?? '';
	}

	/**
	 * Keep what the chat shows of a file list: name, size, date and a link.
	 *
	 * @param	mixed	$output		Result of getDocumentsListByElement()
	 * @param	string	$modulepart	Object type the caller asked for
	 * @return	mixed				Projected list, or $output unchanged if it is not a list
	 */
	private function projectDocumentList($output, $modulepart)
	{
		// document.php does not know every alias the API accepts
		$aliases = array('contrat' => 'contract', 'projet' => 'project', 'categorie' => 'category', 'adherent' => 'member');
		$part = $aliases[$modulepart] ?? $modulepart;
		$paginated = is_array($output) && isset($output['data']) && is_array($output['data']);
		$files = $paginated ? $output['data'] : $output;
		if (!is_array($files)) {
			return $output;
		}
		$list = array();
		foreach ($files as $file) {
			$level1 = (string) ($file['level1name'] ?? '');
			$list[] = array(
				'name' => (string) ($file['name'] ?? ''),
				'size' => dol_print_size((int) ($file['size'] ?? 0), 1),
				'modified' => dol_print_date((int) ($file['date'] ?? 0), 'dayhour'),
				'url' => DOL_URL_ROOT.'/document.php?modulepart='.urlencode($part).'&file='.urlencode(($level1 !== '' ? $level1.'/' : '').(string) ($file['relativename'] ?? $file['name'] ?? ''))
			);
		}
		if ($paginated) {
			$output['data'] = $list;
			return $output;
		}
		return $list;
	}

	/**
	 * Keep what the chat shows of the content of a directory: type, name, path,
	 * size, date, and a link for a file.
	 *
	 * @param	mixed	$output		Result of getDocumentsListByDirectory()
	 * @param	string	$modulepart	Area listed ('ecm' or 'medias')
	 * @return	mixed				Projected list, or $output unchanged if it is not a list
	 */
	private function projectDirectoryList($output, $modulepart)
	{
		$paginated = is_array($output) && isset($output['data']) && is_array($output['data']);
		$entries = $paginated ? $output['data'] : $output;
		if (!is_array($entries)) {
			return $output;
		}
		$list = array();
		foreach ($entries as $entry) {
			$isdir = (($entry['type'] ?? '') === 'dir');
			$item = array(
				'type' => $isdir ? 'dir' : 'file',
				'name' => (string) ($entry['name'] ?? ''),
				'path' => (string) ($entry['relativename'] ?? ''),
				'modified' => dol_print_date((int) ($entry['date'] ?? 0), 'dayhour')
			);
			if (!$isdir) {
				$item['size'] = dol_print_size((int) ($entry['size'] ?? 0), 1);
				$item['url'] = DOL_URL_ROOT.'/document.php?modulepart='.urlencode($modulepart).'&file='.urlencode($item['path']);
			}
			$list[] = $item;
		}
		if ($paginated) {
			$output['data'] = $list;
			return $output;
		}
		return $list;
	}

	/**
	 * Whether the object a file list was asked for exists.
	 *
	 * @param	array<string,mixed>	$args	Tool arguments (modulepart, id, ref)
	 * @return	bool
	 */
	private function documentObjectExists(array $args)
	{
		$object = fetchObjectByElement((int) ($args['id'] ?? 0), (string) ($args['modulepart'] ?? ''), (string) ($args['ref'] ?? ''));
		return is_object($object) && $object->id > 0;
	}

	/**
	 * Shape a read result as its enrichment asks: drop the fields that must not
	 * leave the server, and cap a list the REST method returns unbounded.
	 *
	 * @param  array<mixed>        $output Result of the REST method.
	 * @param  array<string,mixed> $meta   Enrichment of the method.
	 * @return array<mixed>                Shaped result.
	 */
	private function shapeReadOutput(array $output, array $meta)
	{
		$paginated = isset($output['data']) && is_array($output['data']);
		$isList = $paginated || array_key_exists(0, $output);
		$records = $paginated ? $output['data'] : ($isList ? $output : array($output));

		$drop = array_flip((array) ($meta['drop_fields'] ?? array()));
		if (!empty($drop)) {
			foreach ($records as $i => $record) {
				if (is_array($record)) {
					$records[$i] = array_diff_key($record, $drop);
				}
			}
		}
		if (!$isList) {
			return $records[0];
		}

		$max = (int) ($meta['max_items'] ?? 0);
		$total = count($records);
		if ($max > 0 && $total > $max) {
			$records = (($meta['keep'] ?? 'first') === 'last') ? array_slice($records, -$max) : array_slice($records, 0, $max);

			return array(
				'data' => array_values($records),
				'pagination' => array('total' => $total, 'shown' => $max),
				'note' => 'Only '.$max.' of '.$total.' are shown: narrow the request (a period, a filter) to see the others.'
			);
		}
		if ($paginated) {
			$output['data'] = $records;

			return $output;
		}

		return $records;
	}

	/**
	 * Link the acting user to a record just created, with the role the card
	 * gives its creator (the leader of a project). A role missing from the
	 * dictionary is logged, not fatal: the record exists, as on the card.
	 *
	 * @param  string $class Business class of the record.
	 * @param  int    $id    Rowid of the record.
	 * @param  string $code  Internal contact type code.
	 * @return void
	 */
	private function addCreatorContact($class, $id, $code)
	{
		$obj = $this->fetchRecord($class, $id);
		if (!$obj || !$this->user instanceof User) {
			return;
		}
		$result = $obj->add_contact((int) $this->user->id, $code, 'internal');
		if ($result < 0) {
			dol_syslog(get_class($this).'::addCreatorContact '.$class.' '.$id.' '.$code.' result='.$result.' '.$obj->error, LOG_WARNING);
		}
	}

	/**
	 * Execute a bridged tool: authenticate the acting user, call the API method
	 * in-process with positional arguments, catch RestException.
	 *
	 * @param string $name The tool name (e.g. 'api_thirdparties_list').
	 * @param array<string, mixed> $args The tool arguments (named).
	 * @return mixed Result array, or ["error" => ...] on failure.
	 */
	public function execute(string $name, array $args)
	{
		$args = $this->applyFixedParams($name, $args);

		// A model may send the record as a request_data body, or name line fields
		// the way the custom tools do: accept both, so the write receives what the
		// preview described.
		$writemeta = $this->writeMetaFor($name);
		if ($writemeta) {
			try {
				$args = $this->normaliseWriteArgs($args, $writemeta);
			} catch (InvalidArgumentException $e) {
				return ['error' => $e->getMessage()];
			}
		}

		$args = $this->resolveUserSentinels($args);

		$this->getDefinitions();	// ensure routes are built
		if (empty($this->routes[$name])) {
			return ["error" => "Tool function '$name' not found."];
		}
		list($key, $method) = $this->routes[$name];
		$epkey = (string) ($this->enrichments[$key]['endpoint'] ?? $key);	// REST endpoint the tool group calls
		$ep = $this->endpoints[$epkey];

		// A write on a record that exists: what the call always carries, whatever
		// the model sent (the triggers run and core gives the number), and the
		// ref of a record to delete, only known before.
		$writekind = '';
		$refbefore = '';
		if ($writemeta) {
			$writekind = (string) ($writemeta['kind'] ?? '');
			foreach ((array) ($writemeta['fixed'] ?? array()) as $field => $value) {
				$args[$field] = $value;
			}
			if ($writekind === 'delete' && $this->writeTarget) {
				$refbefore = (string) $this->writeTarget->ref;
			}
		}

		// Server-side guarantee of compact list results: doc-strings recommend
		// 'properties', but a model that ignores them would otherwise pull full
		// ~130-column objects — unreadable in the chat table and in the PDF
		// report. When the whitelist entry declares default_properties and the
		// caller did not choose, the default applies; an explicit 'properties'
		// from the model always wins.
		$methodmeta = isset($this->enrichments[$key]['methods'][$method]) ? $this->enrichments[$key]['methods'][$method] : array();
		if (!empty($methodmeta['default_properties']) && !array_key_exists('properties', $args)) {
			$args['properties'] = $methodmeta['default_properties'];
		}

		// --- Authentication bridge (in-process replacement of DolibarrApiAccess::__isAllowed) ---
		// The API endpoint methods read the authenticated user from DolibarrApiAccess::$user
		// and their permission checks (hasRight) run against it. TODO: replicate entity
		// switching for multicompany setups.
		$this->loadApiRuntime();
		// Establish the caller's permission context, the same way the REST entry
		// point does in api_access.class.php ("Set also the global variable $user
		// to the $user of API"): the API layer authenticates via
		// DolibarrApiAccess::$user, and API/business code reads the global.
		// Unlike a REST request, this runs in-process mid-request, so both are
		// restored at the single exit point below — nothing after a tool call
		// (hooks, triggers, log attribution, another handler) may inherit the
		// tool's user.
		$saveduserapi = DolibarrApiAccess::$user;
		$saveduserglobal = empty($GLOBALS['user']) ? null : $GLOBALS['user'];
		DolibarrApiAccess::$user = $this->user;
		$GLOBALS['user'] = $this->user;

		require_once $ep['path'];
		$api = new $ep['class']();

		// Map named MCP args onto the method's positional signature.
		$rm = new ReflectionMethod($ep['class'], $method);
		$callArgs = [];
		$output = null;
		foreach ($rm->getParameters() as $p) {
			$pname = $p->getName();
			// A write method takes the record as one body parameter. The tool
			// advertises the fields flat, because that is what a model fills
			// in, so the body is rebuilt here from everything it sent.
			if ($pname === 'request_data') {
				// A line write: the body normaliseLineArgs() built, unchanged values included.
				if ($writekind === 'line') {
					$callArgs[] = (array) ($args['_body'] ?? array());
					continue;
				}
				if (!empty($writemeta['update'])) {
					$callArgs[] = array_intersect_key($args, $writemeta['update']);
					continue;
				}
				$body = $args;
				unset($body['requestState']);
				// Only the fields the tool advertised may be written: the human
				// approved a preview of those, not of arbitrary object properties
				// (a ticket update must not rewrite track_id, fk_user_create,
				// origin_email, dates...). 'parent' writes keep the flat line
				// fields, which are not individually declared in 'params'.
				if (!empty($writemeta) && empty($writemeta['parent']) && !empty($methodmeta['params'])) {
					$body = array_intersect_key($body, $methodmeta['params']);
				}
				// An update writes the allowed fields it was given, nothing else.
				if ($writekind === 'update') {
					$body = array_intersect_key($args, array_flip((array) $writemeta['fields']));
				}
				foreach (array_keys($body) as $field) {
					if (substr((string) $field, 0, 1) === '_') {
						unset($body[$field]);
					}
				}
				foreach (($writemeta['defaults'] ?? array()) as $field => $default) {
					if (!isset($body[$field])) {
						if ($default === 'now') {
							$default = dol_now();
						} elseif ($default === 'me') {
							$default = (int) $this->user->id;
						}
						$body[$field] = $default;
					}
				}
				// Fields named the common way up to here, renamed for an
				// endpoint that reads them under another name.
				foreach (($writemeta['rename'] ?? array()) as $from => $to) {
					if (array_key_exists($from, $body) && !isset($body[$to])) {
						$body[$to] = $body[$from];
						unset($body[$from]);
					}
				}
				// Fields the object persists under another name while the API
				// still validates the documented one (the ticket API checks
				// access on 'socid' but Ticket::create/update write 'fk_soc').
				foreach (($writemeta['copy'] ?? array()) as $from => $to) {
					if (isset($body[$from])) {
						$body[$to] = $body[$from];
					}
				}
				$callArgs[] = $body;
				continue;
			}
			// A write tool advertises a flat schema: only the declared arguments
			// may reach a positional parameter (a caller must not fill
			// 'notrigger' or any other option the schema never showed). A write
			// on an existing record is the exception: its arguments were rebuilt
			// from scratch by normaliseTargetArgs(), with the options fixed there.
			if (array_key_exists($pname, $args)
				&& (empty($writemeta) || empty($methodmeta['params']) || array_key_exists($pname, $methodmeta['params'])
					|| isset($methodmeta['fixed_params'][$pname])
					|| in_array($writekind, array('update', 'action', 'delete', 'payment', 'line'), true))) {
				// Cap an explicit 'limit': one call must not pull thousands of full objects.
				$callArgs[] = ($pname == 'limit') ? min((int) $args[$pname], self::BRIDGE_MAX_LIMIT) : $args[$pname];
			} elseif ($p->isOptional()) {
				// Bridge default for an omitted 'limit' is smaller than the API's 100.
				$callArgs[] = ($pname == 'limit') ? self::BRIDGE_DEFAULT_LIMIT : $p->getDefaultValue();
			} else {
				$output = ["error" => "Missing required parameter '$pname'."];
				break;
			}
		}

		if ($output === null) {
			// Core can print an error page while refusing access (reproducible on
			// the REST route): keep it out of the JSON answer, in the log instead.
			ob_start();
			try {
				$result = call_user_func_array([$api, $method], $callArgs);
				// Serialize API return (cleaned objects) into plain arrays for the MCP client.
				$output = json_decode(json_encode($result), true);
				if (in_array($writekind, array('update', 'action', 'delete', 'payment', 'line'), true)) {
					// The record the user named, under its ref (a new one once
					// validated), rather than the whole object the API returns.
					$targetid = (int) ($args[$writemeta['target'] ?? 'id'] ?? 0);
					$done = array('success' => true, 'id' => $targetid);
					if ($writekind === 'payment') {
						$done['payment_id'] = (int) $result;
					}
					$ref = ($writekind === 'delete') ? $refbefore : $this->refOfCreatedObject((string) $writemeta['object'], $targetid);
					// Some REST deletes answer success when core failed: check.
					if ($writekind === 'delete' && $this->fetchRecord((string) $writemeta['object'], $targetid)) {
						$done = array('error' => 'The '.$writemeta['label'].' '.$refbefore.' could not be deleted.', 'http_status' => 500);
						dol_syslog(get_class($this).'::execute '.$name.' reported success but the record is still there', LOG_ERR);
					}
					if ($ref !== '') {
						$done['ref'] = $ref;
					}
					$output = $done;
				} elseif (!empty($writemeta['compact_result'])) {
					if (!is_array($output) || (int) ($output['id'] ?? 0) !== (int) $args[$writemeta['parent']] || !is_string($output['ref'] ?? null) || $output['ref'] === '') {
						throw new RuntimeException('The API did not return the updated record');
					}
					$output = ['success' => true, 'id' => (int) $output['id'], 'ref' => $output['ref']];
				} elseif ($epkey === 'documents' && !is_array($output)) {
					// The upload REST method answers with the file name it wrote, not a rowid.
					$output = array('success' => true, 'filename' => (string) $output);
				} elseif (!is_array($output)) {
					// A create returns the new rowid as a scalar: name it, so the
					// caller gets the same shape as any other tool result.
					$output = array('success' => true, 'id' => (int) $output);
					// A line write returns the rowid of the new line: the record it
					// went into is what the user knows.
					if (!empty($writemeta['parent'])) {
						$output = array('success' => true, 'line_id' => (int) $output['id'], 'id' => (int) ($args[$writemeta['parent']] ?? 0));
					}
					// Name what was written: an id alone tells the user nothing, and
					// the ref is what they see on the document itself.
					if (!empty($writemeta['object']) && $output['id'] > 0) {
						$ref = $this->refOfCreatedObject($writemeta['object'], $output['id']);
						if ($ref !== '') {
							$output['ref'] = $ref;
						}
						if (!empty($writemeta['creator_contact'])) {
							$this->addCreatorContact((string) $writemeta['object'], (int) $output['id'], (string) $writemeta['creator_contact']);
						}
					}
				}
				if ($epkey === 'documents' && $method === 'getDocumentsListByElement') {
					$output = $this->projectDocumentList($output, (string) ($args['modulepart'] ?? ''));
				}
				if ($epkey === 'documents' && $method === 'getDocumentsListByDirectory') {
					$output = $this->projectDirectoryList($output, (string) ($args['modulepart'] ?? ''));
				}
			} catch (Throwable $e) {
				$code = (int) $e->getCode();
				$message = $e->getMessage();
				if ($message === '') {
					// Core throws bare RestException(403) in places: give the model
					// something to reason on instead of an empty string.
					$message = 'Access denied or resource error (HTTP '.($code > 0 ? $code : 500).').';
				}
				if (strpos($message, 'Error when validating parameter sqlfilters') === 0) {
					// The API's own wording ("Bad syntax of the search string: ...")
					// shows the user a query they never wrote: say what went wrong
					// and what to do instead.
					global $langs;
					dol_syslog(get_class($this).'::execute '.$name.' '.$message, LOG_WARNING);
					$langs->load('other');
					$message = $langs->transnoentitiesnoconv('AIBadSearchFilter');
				}
				$output = [
					"error" => $message,
					"http_status" => ($code > 0 ? $code : 500)
				];
				// The API answers 404 for an object with no files: that is an empty
				// list, unless the object itself does not exist. For a download,
				// 404 means the file itself is missing: keep the error.
				if ($epkey === 'documents' && $method === 'getDocumentsListByElement' && $code == 404 && $this->documentObjectExists($args)) {
					$output = [];
				}
			}
			$printed = ob_get_clean();
			if ($printed !== '' && $printed !== false) {
				dol_syslog(get_class($this).'::execute '.$name.' printed output: '.dol_trunc(strip_tags($printed), 200), LOG_WARNING);
			}
		}

		// A record read that came back empty is no record.
		if (!empty($methodmeta['needs_id']) && is_array($output) && !isset($output['error']) && empty($output['id'])) {
			$output = array('error' => (string) $methodmeta['needs_id'], 'http_status' => 404);
		}

		// A list the REST method does not bound, and fields that stay on the server.
		if (is_array($output) && !isset($output['error']) && (!empty($methodmeta['max_items']) || !empty($methodmeta['drop_fields']))) {
			$output = $this->shapeReadOutput($output, $methodmeta);
		}

		// Extrafields flagged as personal data (GDPR) must not reach an AI provider.
		$elementForExtrafields = $this->extrafieldsElementForEndpoint($epkey);
		if ($elementForExtrafields !== '' && empty($writemeta['compact_result'])) {
			require_once DOL_DOCUMENT_ROOT.'/ai/lib/ai.lib.php';
			$output = aiStripPersonalExtrafields($this->db, $output, $elementForExtrafields);
		}

		// Restore the caller's context (single exit point).
		DolibarrApiAccess::$user = $saveduserapi;
		if ($saveduserglobal !== null) {
			$GLOBALS['user'] = $saveduserglobal;
		}

		return $output;
	}

	/**
	 * Endpoint and method a bridge tool calls, for a page that shows the tool to
	 * a person (the palette of the chat) under a plain name.
	 *
	 * @param  string $name Tool name.
	 * @return array{endpoint:string,method:string,suffix:string,label:string}|null Route, or null when the name is not a bridge tool.
	 */
	public function toolRoute(string $name)
	{
		$this->getDefinitions();
		if (empty($this->routes[$name])) {
			return null;
		}
		list($key, $method) = $this->routes[$name];

		return array(
			'endpoint' => (string) $key,
			'method' => (string) $method,
			'suffix' => (string) substr($name, strlen('api_'.$key.'_')),
			'label' => (string) ($this->enrichments[$key]['label'] ?? ($this->endpoints[$this->enrichments[$key]['endpoint'] ?? $key]['label'] ?? $key))
		);
	}
}
