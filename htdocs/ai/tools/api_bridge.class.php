<?php
/* Copyright (C) 2026	Jose Martinez			<jose.martinez@pichinov.com>
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
 *   - Schemas come from a light docblock parser; TODO reuse Restler's
 *     CommentParser/Routes metadata (what generates swagger.json).
 *   - Tool definitions are rebuilt on every request; TODO cache them,
 *     invalidated on module (de)activation.
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
		'tickets' => array('thirdparty', 'project')
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
	 * (per-parameter doc overriding what the docblock parser guessed).
	 * TODO Replace path/class discovery with the dynamic scan of api_*.class.php
	 * used by api/index.php.
	 *
	 * Endpoints are discovered at runtime by discoverEndpoints(); this map only
	 * carries the hand-written complement for the ones we know well, keyed by
	 * the same name the scan derives from the file (api_<key>.class.php). An
	 * endpoint absent from this map is still exposed, with the description and
	 * parameter docs reflection gives — that is the whole point of scanning.
	 *
	 * @var array<string, array{label?:string, methods?:array<string, array{suffix?:string, description?:string, params?:array<string,string>}>}>
	 */
	private $enrichments = [
		'thirdparties' => [
			'label' => 'third parties (customers, prospects, suppliers)',
			'methods' => [
				'index' => [
					'default_properties' => 'id,name,code_client,code_fournisseur,email,town,client,fournisseur,status',
					'description' => "Use 'mode' to restrict to a nature of third party instead of filtering on names. To find one company by name use sqlfilters on t.nom (e.g. \"(t.nom:like:'%acme%')\"); other useful fields: t.name_alias, t.code_client, t.code_fournisseur, t.email, t.town, t.zip, t.fk_pays (country rowid), t.status (1=open, 0=closed). Prefer a small 'limit' and 'properties' (e.g. 'id,name,code_client,code_fournisseur,email,town,client,fournisseur,status') to keep answers short.",
					'params' => [
						'mode' => "Nature filter: 0=all (default), 1=customers/prospects, 2=prospects only, 3=neither customer nor prospect, 4=suppliers.",
						'category' => "Rowid of a third-party category (tag) to restrict the list to.",
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}} instead of a bare list; use it to know the total count."
					]
				],
				'get' => [
					'description' => "Returns the full record: address, contact channels, customer/supplier codes, VAT number, default payment terms/modes, outstanding limit. In the result 'client' is 1=customer, 2=prospect, 3=both; 'fournisseur' is 1 when supplier."
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
		],
		'categories' => [
			'label' => 'categories / tags',
			'methods' => [
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
		],
		'invoices' => [
			'label' => 'customer invoices',
			'methods' => [
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
				]
			]
		],
		'proposals' => [
			'label' => 'commercial proposals (quotes / devis)',
			'methods' => [
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
				]
			]
		],
		'tickets' => [
			'label' => 'support tickets',
			'methods' => [
				'index' => [
					'default_properties' => 'id,ref,track_id,subject,fk_soc,fk_statut,severity_code,type_code,datec',
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
		],
		'projects' => [
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
				'getByRef' => [
					'suffix' => 'get_by_ref',
					'description' => "One project by its exact reference (e.g. 'PJ2401-0001').",
					'params' => ['ref' => "Exact project reference."]
				]
			]
		],
		'tasks' => [
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
				]
			]
		],
		'agendaevents' => [
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
		],
		'interventions' => [
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
		],
		'contracts' => [
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
		],
		'members' => [
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
		],
		'subscriptions' => [
			'label' => 'member subscriptions',
			'methods' => [
				'index' => [
					'description' => "All membership fee payments across members: fk_adherent (member rowid), dateh (period start), datef (period end), amount. NB: the default sortfield here is 'dateadh' WITHOUT the 't.' prefix (API quirk). To get the fees of one member prefer api_members_subscriptions_list.",
					'params' => [
						'pagination_data' => "Set to true to get {data, pagination:{total,page,page_count,limit}}."
					]
				],
				'get' => [
					'description' => "One subscription payment with its member, period and amount."
				]
			]
		],
		'stockmovements' => [
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
		],
		'warehouses' => [
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
		],
		'expensereports' => [
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
		],
		'products' => [
			'label' => 'products and services catalog',
			'methods' => [
				'index' => [
					'default_properties' => 'id,ref,label,type,price,price_ttc,tva_tx,status,status_buy',
					'description' => "Search by name with sqlfilters on t.label, by reference on t.ref (e.g. \"(t.label:like:'%screw%')\"). Result fields: type (0=product, 1=service), price (sale price excl. tax), price_ttc, tva_tx (VAT rate), status (1=for sale), status_buy (1=for purchase), stock_reel (only with includestockdata=1). Prefer 'properties' (e.g. 'id,ref,label,type,price,price_ttc,tva_tx,status,status_buy') and a small 'limit'.",
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
					'description' => "Full product record: description, prices, VAT, barcode, weight/dimensions, accounting codes, optional stock and sub-products.",
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
		'sortfield' => "Field to sort on, prefixed with 't.' (e.g. 't.rowid', 't.ref', 't.datec'). Use the SQL column names: creation date is 't.datec' (NEVER 'date_creation') and last modification 't.tms' (never 'date_modification').",
		'sortorder' => "Sort direction: 'ASC' or 'DESC'.",
		'limit' => "Maximum number of records to return.",
		'page' => "Zero-based page index for pagination.",
		'sqlfilters' => "Universal search filter. Syntax: (t.field:operator:'value'); operators: =, !=, <, <=, >, >=, like, is; combine clauses with 'and'/'or' and parentheses. Example: \"(t.ref:like:'PR%') and (t.datec:>=:'2026-01-01')\". 'like' is case-insensitive; the IN operator is NOT supported (use 'or'); dates as 'YYYY-MM-DD'.",
		'properties' => "Comma-separated list of properties to include in the response, to reduce its size (e.g. 'id,ref,label').",
		'id' => "Rowid (numeric technical id) of the record."
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
		$loader = Luracast\Restler\AutoLoader::instance();
		spl_autoload_register($loader);
		require_once DOL_DOCUMENT_ROOT . '/api/class/api.class.php';
		require_once DOL_DOCUMENT_ROOT . '/api/class/api_access.class.php';
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
					if (in_array($file_searched, ['api_access.class.php', 'api_setup.class.php', 'api_documents.class.php', 'api_login.class.php', 'api_status.class.php'], true)) {
						continue;	// Framework plumbing, not business endpoints (setup/documents even require main.inc.php, fatal outside a web page).
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
		if (!getDolGlobalInt('AI_MCP_API_BRIDGE')) {
			return [];	// Feature flag off: bridge exposes nothing.
		}
		if ($this->defs !== null) {
			return $this->defs;
		}
		$this->loadApiRuntime();

		$this->defs = [];
		$this->routes = [];
		$this->endpoints = $this->discoverEndpoints();

		foreach ($this->endpoints as $key => $ep) {
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
			if (empty($methods) || !$this->resolveEndpointClass($key)) {
				continue;	// nothing left to expose, or api file without its class
			}
			$ep = $this->endpoints[$key];	// re-read: 'class' is now resolved

			foreach ($methods as $method => $meta) {
				// A method may need an optional module beyond its endpoint's own
				// (e.g. products getVariants needs Variants): skip when off, so
				// the tool does not exist instead of failing opaquely.
				if (!empty($meta['module']) && !isModEnabled($meta['module'])) {
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
			// Exception for the two syntax-bearing params (sqlfilters, sortfield):
			// their API docblocks carry a thin per-endpoint example that would win
			// over — and hide — the full syntax contract (operators, and/or, the
			// unsupported IN, the datec/tms column names), so there the common doc
			// is APPENDED to the docblock description instead of being shadowed.
			if (isset($meta['params'][$pname])) {
				$pdesc = $meta['params'][$pname];
			} elseif (!empty($paramDocs[$pname]['desc'])) {
				$pdesc = $paramDocs[$pname]['desc'];
				if (in_array($pname, ['sqlfilters', 'sortfield'], true) && !empty($this->commonParamDocs[$pname])) {
					$pdesc = rtrim($pdesc, '. ').'. '.$this->commonParamDocs[$pname];
				}
			} else {
				$pdesc = $this->commonParamDocs[$pname] ?? '';
			}
			$prop = [
				'type' => $ptype,
				'description' => $pdesc
			];
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
	 * Execute a bridged tool: authenticate the acting user, call the API method
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
			if (array_key_exists($pname, $args)) {
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
			try {
				$result = call_user_func_array([$api, $method], $callArgs);
				// Serialize API return (cleaned objects) into plain arrays for the MCP client.
				$output = json_decode(json_encode($result), true);
			} catch (Throwable $e) {
				$code = (int) $e->getCode();
				$message = $e->getMessage();
				if ($message === '') {
					// Core throws bare RestException(403) in places: give the model
					// something to reason on instead of an empty string.
					$message = 'Access denied or resource error (HTTP '.($code > 0 ? $code : 500).').';
				}
				$output = [
					"error" => $message,
					"http_status" => ($code > 0 ? $code : 500)
				];
			}
		}

		// Restore the caller's context (single exit point).
		DolibarrApiAccess::$user = $saveduserapi;
		if ($saveduserglobal !== null) {
			$GLOBALS['user'] = $saveduserglobal;
		}

		return $output;
	}
}
