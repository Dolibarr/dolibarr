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
 * \file htdocs/ai/tools/thirdparty.class.php
 * \ingroup ai
 * \brief MCP Server tool for Dolibarr categories.
 */

require_once DOL_DOCUMENT_ROOT . '/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT . '/contact/class/contact.class.php';


/**
 * Class ToolThirdParty
 *
 * Provides various tools related to Dolibarr categories.
 */
class ToolThirdParty extends McpTool
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
				"name" => "search_thirdparties",
				"description" => "Search for a thirdparty by ID, name, alias, code, or email. If a numerical ID is provided alone, it returns an exact match. If a name is provided, it returns a list of matches.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"query" => ["type" => ["string", "integer"], "description" => "The ID of the thirdparty, or a name/alias/code/email to search for."],
						"type" => ["type" => "string", "enum" => ["customer", "prospect", "supplier"], "description" => "Filter by type (optional)."],
						"country_code" => ["type" => "string", "description" => "ISO 2-letter country code (e.g. US, FR, GR) (optional)."],
						"limit" => ["type" => "integer", "default" => 5]
					],
					"required" => ["query"]
				]
			],
			[
				"name" => "count_thirdparties",
				"description" => "Count the number of thirdparties matching the search criteria.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"query" => ["type" => ["string", "integer"], "description" => "A name/alias/code/email to search for."],
						"type" => ["type" => "string", "enum" => ["customer", "prospect", "supplier"], "description" => "Filter by type (optional)."],
						"country_code" => ["type" => "string", "description" => "ISO 2-letter country code (e.g. US, FR, GR) (optional)."]
					],
					"required" => ["query"]
				]
			],
			[
				"name" => "get_thirdparty_details",
				"description" => "Get full details of a specific thirdparty by ID.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"id" => ["type" => "integer", "description" => "The unique numerical ID of the thirdparty."]
					],
					"required" => ["id"]
				]
			],
			[
				"name" => "create_thirdparty",
				"description" => "Create a new thirdparty (Using only terms: Customer, Prospect, or Supplier).",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"name" => ["type" => "string", "description" => "Name of the thirdparty"],
						"type" => ["type" => "string", "enum" => ["customer", "prospect", "supplier", "both", "none"], "default" => "customer", "description" => "Type of thirdparty. 'none' means not a customer or prospect."],
						"email" => ["type" => "string", "description" => "Email address"],
						"phone" => ["type" => "string", "description" => "Phone number"],
						"address" => ["type" => "string", "description" => "Address"],
						"zip" => ["type" => "string", "description" => "Postal code"],
						"town" => ["type" => "string", "description" => "Town/City"],
						"country_code" => ["type" => "string", "description" => "ISO 2-letter country code (e.g. US, FR, GR)"],
						"code_client" => ["type" => "string", "description" => "Customer code (optional, -1 for auto-generation)"],
						"idprof1" => ["type" => "string", "description" => "Professional ID 1"],
						"idprof2" => ["type" => "string", "description" => "Professional ID 2"],
						"idprof3" => ["type" => "string", "description" => "Professional ID 3"],
						"idprof4" => ["type" => "string", "description" => "Professional ID 4"]
					],
					"required" => ["name"]
				]
			],
			[
				"name" => "update_thirdparty",
				"description" => "Updates an existing thirdparty's details.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"id" => ["type" => "integer", "description" => "The ID of the thirdparty to update."],
						"name" => ["type" => "string", "description" => "The new name for the thirdparty."],
						"email" => ["type" => "string", "description" => "The new email address."],
						"phone" => ["type" => "string", "description" => "The new phone number."],
						"address" => ["type" => "string", "description" => "The new address."],
						"zip" => ["type" => "string", "description" => "The new postal code."],
						"town" => ["type" => "string", "description" => "The new town."],
						"country_code" => ["type" => "string", "description" => "The new ISO 2-letter country code."]
					],
					"required" => ["id"]
				]
			],
			[
				"name" => "list_thirdparty_contacts",
				"description" => "Lists all contacts associated with a specific thirdparty.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"id" => ["type" => "integer", "description" => "The ID of the thirdparty."]
					],
					"required" => ["id"]
				]
			],
			[
				"name" => "add_thirdparty_contact",
				"description" => "Adds a new contact to an existing thirdparty.",
				"inputSchema" => [
					"type" => "object",
					"properties" => [
						"thirdparty_identifier" => [
							"type" => ["string", "integer"],
							"description" => "The ID or name of the thirdparty to add the contact to."
						],
						"firstname" => ["type" => "string", "description" => "Contact's first name."],
						"lastname" => ["type" => "string", "description" => "Contact's last name."],
						"email" => ["type" => "string", "description" => "Contact's email address."],
						"phone" => ["type" => "string", "description" => "Contact's phone number."],
						"role" => ["type" => "string", "description" => "Contact's role or position within the company."]
					],
					"required" => ["thirdparty_identifier", "firstname", "lastname"]
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
		return ['thirdparty', 'billing', 'commercial', 'project', 'stock'];
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
			case 'search_thirdparties':
				return $this->search($args, 0);
			case 'count_thirdparties':
				return $this->search($args, 1);
			case 'get_thirdparty_details':			// Get info of agiven thidparty
				return $this->getDetails($args);
			case 'create_thirdparty':
				return $this->create($args);
			case 'update_thirdparty':
				return $this->update($args);
			case 'list_thirdparty_contacts':
				return $this->listContacts($args);
			case 'add_thirdparty_contact':
				return $this->addContact($args);
			default:
				return ["error" => "Tool function '$name' not found."];
		}
	}

	/**
	 * Search for third parties based on provided criteria.
	 *
	 * @param   array{query:string|int, type?:string, country_code?:string, limit?:int|string} $args   Array of arguments:
	 *                                                                                                 - query: Search string or ID
	 *                                                                                                 - country_code: ISO country code on 2 chars (FR, US, GR...)
	 *                                                                                                 - type: 'customer', 'prospect', 'supplier'
	 *                                                                                                 - limit: Limit results (default 5)
	 * @param	int		$count		If set to 1, returns only the count of results.
	 * @return array{error:string}|array{count:int}|list<array{id:int,name:string,alias:string,code_cust:string,code_sup:string,email:string,type:string,url:string}>
	 */
	private function search(array $args, int $count = 0)
	{
		if (!$this->user->hasRight('societe', 'lire')) {
			return ["error" => "Permission Denied"];
		}

		$query = $args['query'];
		$type = isset($args['type']) ? (string) $args['type'] : '';
		$country_code = isset($args['country_code']) ? (string) $args['country_code'] : '';
		$limit = isset($args['limit']) ? (int) $args['limit'] : 5;

		// Safety fallback
		if ($limit <= 0) {
			$limit = 5;
		}
		if ($limit > 1000) {
			dol_syslog("Search DB Error: Too many record requested", LOG_ERR);
			return ["error" => "DB Error"];
		}

		// Dolibarr SQL construction
		if ($count) {
			$sql = "SELECT COUNT(s.rowid) as nb";
		} else {
			$sql = "SELECT s.rowid, s.nom, s.name_alias, s.code_client, s.code_fournisseur, s.email, s.client, s.fournisseur";
		}
		$sql .= " FROM " . MAIN_DB_PREFIX . "societe as s";
		if ($country_code) {
			$sql.= " INNER JOIN ".MAIN_DB_PREFIX."c_country as c ON s.fk_pays = c.rowid AND c.code = '".$this->db->escape($country_code)."'";
		}
		$sql .= " WHERE s.entity IN (" . getEntity('societe') . ")";

		if (is_numeric($query)) {
			$sql .= " AND s.rowid = " . (int) $query;
			$limit = 1;
		} else {
			$sql .= " AND (s.nom LIKE '%" . $this->db->escape($query) . "%'";
			$sql .= " OR s.name_alias LIKE '%" . $this->db->escape($query) . "%'";
			$sql .= " OR s.code_client LIKE '%" . $this->db->escape($query) . "%'";
			$sql .= " OR s.code_fournisseur LIKE '%" . $this->db->escape($query) . "%'";
			$sql .= " OR s.email LIKE '%" . $this->db->escape($query) . "%')";
		}

		if ($type === 'customer') {
			$sql .= " AND s.client IN (1, 3)";
		} elseif ($type === 'prospect') {
			$sql .= " AND s.client IN (2, 3)";
		} elseif ($type === 'supplier') {
			$sql .= " AND s.fournisseur = 1";
		}

		$sql .= " ORDER BY s.nom ASC";
		$sql .= $this->db->plimit($limit);

		$resql = $this->db->query($sql);

		if (!$resql) {
			dol_syslog("Search DB Error: " . $this->db->lasterror(), LOG_ERR);
			return ["error" => "DB Error"];
		}

		$data = [];

		while ($obj = $this->db->fetch_object($resql)) {
			if ($count) {
				$data = [
					"count" => (int) $obj->nb
				];

				$this->db->free($resql);

				return $data;
			}

			$roles = [];

			// Cast strictly to ensure type safety in logic
			$is_client = (int) $obj->client;
			$is_supplier = (int) $obj->fournisseur;

			if ($is_client === 1 || $is_client === 3) {
				$roles[] = "Customer";
			}
			if ($is_client === 2 || $is_client === 3) {
				$roles[] = "Prospect";
			}
			if ($is_supplier === 1) {
				$roles[] = "Supplier";
			}

			$data[] = [
				"id"        => (int) $obj->rowid,
				"name"      => (string) $obj->nom,
				"alias"     => (string) $obj->name_alias,
				"code_cust" => (string) $obj->code_client,
				"code_sup"  => (string) $obj->code_fournisseur,
				"email"     => (string) $obj->email,
				"type"      => implode('/', $roles),
				"url"       => DOL_URL_ROOT . "/societe/card.php?socid=" . $obj->rowid
			];
		}

		$this->db->free($resql);

		return $data;
	}

	/**
	 * Fetch details for a specific third party (Societe).
	 *
	 * @param   array{id: int|string} $args   Arguments array containing the thirdparty ID.
	 *
	 * @return array{error:string}|array<string,mixed>
	 *
	 */
	private function getDetails(array $args): array
	{
		if (!$this->user->hasRight('societe', 'lire')) {
			return ["error" => "Permission Denied"];
		}

		if (empty($args['id'])) {
			return ["error" => "ID is required"];
		}

		$soc = new Societe($this->db);

		// Fetch returns > 0 on success, 0 on not found, < 0 on error
		$result = $soc->fetch((int) $args['id']);

		if ($result > 0) {
			return [
				"id"      => (int) $soc->id,
				"name"    => (string) $soc->nom,
				"address" => (string) $soc->address,
				"zip"     => (string) $soc->zip,
				"city"    => (string) $soc->town,
				"country" => (string) $soc->country,
				"email"   => (string) $soc->email,
				"phone"   => (string) $soc->phone,
				"vat"     => (string) $soc->tva_intra,
				// getLibStatut(2) returns the status label (short). Cast to string just in case.
				"status"  => (string) $soc->getLibStatut(2),
				"url"     => DOL_URL_ROOT . "/societe/card.php?socid=" . $soc->id
			];
		}

		return ["error" => "Thirdparty not found"];
	}


	/**
	 * Create a new third party (Societe).
	 *
	 * @param array<string,mixed> $args Arguments array. 'name' is mandatory.
	 *
	 * @return array{error:string}|array<string,mixed>
	 *
	 */
	private function create(array $args)
	{
		global $conf;

		// Check permissions
		if (!$this->user->hasRight('societe', 'creer')) {
			return ["error" => "Permission Denied"];
		}

		// Validate mandatory fields
		if (empty($args['name'])) {
			return ["error" => "Name is required"];
		}

		$soc = new Societe($this->db);

		// Assign properties with strict casting to prevent null issues in strict mode
		$soc->nom = (string) $args['name'];
		$soc->email = isset($args['email']) ? (string) $args['email'] : '';
		$soc->phone = isset($args['phone']) ? (string) $args['phone'] : '';
		$soc->address = isset($args['address']) ? (string) $args['address'] : '';
		$soc->zip = isset($args['zip']) ? (string) $args['zip'] : '';
		$soc->town = isset($args['town']) ? (string) $args['town'] : '';
		$soc->code_client = isset($args['code_client']) ? (string) $args['code_client'] : '';
		$soc->idprof1 = isset($args['idprof1']) ? (string) $args['idprof1'] : '';
		$soc->idprof2 = isset($args['idprof2']) ? (string) $args['idprof2'] : '';
		$soc->idprof3 = isset($args['idprof3']) ? (string) $args['idprof3'] : '';
		$soc->idprof4 = isset($args['idprof4']) ? (string) $args['idprof4'] : '';

		// Country Handling
		if (! empty($args['country_code'])) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';
			// getCountry returns an array or false/0.
			/** @var array{id:int}|false $info */
			$info = getCountry($args['country_code'], '1');
			if ($info && isset($info['id'])) {
				$soc->country_id = (int) $info['id'];
			}
		}

		// Fallback to default country if not set
		if (empty($soc->country_id) && !empty($conf->global->MAIN_INFO_SOCIETE_COUNTRY)) {
			$soc->country_id = (int) $conf->global->MAIN_INFO_SOCIETE_COUNTRY;
		}

		$type = $args['type'] ?? 'customer';
		$soc->client = 0;       // Default: not a customer
		$soc->fournisseur = 0;  // Default: not a supplier

		if ($type === 'customer') {
			$soc->client = 1;
			if (empty($soc->code_client)) {
				$soc->code_client = '-1';
			}
		} elseif ($type === 'prospect') {
			$soc->client = 2;
			if (empty($soc->code_client)) {
				$soc->code_client = '-1';
			}
		} elseif ($type === 'both') {
			$soc->client = 3; // Prospect + Customer
			if (empty($soc->code_client)) {
				$soc->code_client = '-1';
			}
		}

		if ($type === 'supplier' || $type === 'both') {
			$soc->fournisseur = 1;
			if (empty($soc->code_fournisseur)) {
				$soc->code_fournisseur = '-1';
			}
		}

		$result = $soc->create($this->user);

		if ($result > 0) {
			return [
				"status"  => "success",
				"message" => "Thirdparty created",
				"id"      => (int) $soc->id,
				"name"    => (string) $soc->name,
				"url"     => DOL_URL_ROOT . "/societe/card.php?socid=" . $soc->id
			];
		}

		return ["error" => "Create failed: " . (string) $soc->error];
	}

	/**
	 * Update a thirdparty record.
	 *
	 * @param array{id:int|string,name?:string,email?:string,phone?:string,address?:string,zip?:string,town?:string,country_code?:string} $args Thirdparty fields to update (ID required).
	 *
	 * @return array{status:string,message:string,id:int,url:string}|array{error:string}
	 *
	 */
	private function update(array $args)
	{
		if (!$this->user->hasRight('societe', 'creer') && !$this->user->hasRight('societe', 'modifier')) {
			return ["error" => "Permission Denied to update."];
		}

		if (empty($args['id'])) {
			return ["error" => "ID is required for update."];
		}

		$soc = new Societe($this->db);

		$result = $soc->fetch((int) $args['id']);
		if ($result <= 0) {
			return ["error" => "Thirdparty not found with ID: " . $args['id']];
		}

		// Update fields only if provided in arguments (Partial Update)
		// We cast to (string) to ensure strict type compliance
		if (isset($args['name'])) {
			$soc->nom = (string) $args['name'];
		}
		if (isset($args['email'])) {
			$soc->email = (string) $args['email'];
		}
		if (isset($args['phone'])) {
			$soc->phone = (string) $args['phone'];
		}
		if (isset($args['address'])) {
			$soc->address = (string) $args['address'];
		}
		if (isset($args['zip'])) {
			$soc->zip = (string) $args['zip'];
		}
		if (isset($args['town'])) {
			$soc->town = (string) $args['town'];
		}

		// Handle Country update
		if (!empty($args['country_code'])) {
			require_once DOL_DOCUMENT_ROOT . '/core/lib/company.lib.php';

			/** @var array{id:int}|false $info */
			$info = getCountry($args['country_code'], '1');

			if ($info && isset($info['id'])) {
				$soc->country_id = (int) $info['id'];
			}
		}

		if ($soc->update($soc->id, $this->user) > 0) {
			return [
				"status"  => "success",
				"message" => "Thirdparty updated successfully.",
				"id"      => (int) $soc->id,
				"url"     => DOL_URL_ROOT . "/societe/card.php?socid=" . $soc->id
			];
		}

		return ["error" => "Update failed: " . (string) $soc->error];
	}

	/**
	 * List all contacts for a given thirdparty.
	 *
	 * @param   array{id: int|string} $args   Arguments array containing the thirdparty ID.
	 *
	 * @return array{error: string}|list<array<string, int|string>>
	 *
	 */
	private function listContacts(array $args)
	{
		global $langs;

		if (!$this->user->hasRight('societe', 'lire')) {
			return ["error" => "Permission Denied"];
		}

		if (empty($args['id'])) {
			return ["error" => "Thirdparty ID is required"];
		}

		$socid = (int) $args['id'];
		$langs->load("companies");
		$langs->load("other");

		// Translations
		$label_id        = $langs->transnoentitiesnoconv("Id");
		$label_firstname = $langs->transnoentitiesnoconv("Firstname");
		$label_lastname  = $langs->transnoentitiesnoconv("Lastname");
		$label_email     = $langs->transnoentitiesnoconv("Email");
		$label_phone     = $langs->transnoentitiesnoconv("Phone");
		$label_role      = $langs->transnoentitiesnoconv("PostOrFunction");
		$label_link      = $langs->transnoentitiesnoconv("Link");

		$link_text       = $langs->transnoentitiesnoconv("Show");

		// Verify thirdparty exists first
		$soc = new Societe($this->db);
		if ($soc->fetch($socid) <= 0) {
			return ["error" => "Thirdparty not found with ID: " . $socid];
		}

		// Build SQL
		$sql = "SELECT t.rowid, t.firstname, t.lastname, t.email, t.phone, t.poste";
		$sql .= " FROM " . MAIN_DB_PREFIX . "socpeople as t";
		$sql .= " WHERE t.fk_soc = " . (int) $socid; // Fix: Added explicit (int) cast to satisfy CodingPhpTest static analysis
		$sql .= " AND t.entity IN (" . getEntity('socpeople') . ")";
		$sql .= " ORDER BY t.lastname ASC, t.firstname ASC";

		$resql = $this->db->query($sql);

		if (! $resql) {
			dol_syslog("DB Error in listContacts: " . $this->db->lasterror(), LOG_ERR);
			return ["error" => "DB Error"];
		}

		$data = [];

		while ($obj = $this->db->fetch_object($resql)) {
			// Absolute URL
			$absoluteUrl = DOL_URL_ROOT . "/contact/card.php?id=" . $obj->rowid;

			$htmlLink = "<a href='" . $absoluteUrl . "' target='_blank'>" . $link_text . "</a>";

			$data[] = [
				$label_id        => (int) $obj->rowid,
				$label_firstname => (string) $obj->firstname,
				$label_lastname  => (string) $obj->lastname,
				$label_email     => (string) $obj->email,
				$label_phone     => (string) $obj->phone,
				$label_role      => (string) $obj->poste,
				$label_link      => $htmlLink
			];
		}

		$this->db->free($resql);

		return $data;
	}

	/**
	 * Add a contact linked to a thirdparty.
	 *
	 * @param array{thirdparty_identifier:int|string,firstname:string,lastname:string,email?:string,phone?:string} $args Arguments array (identifier, firstname, lastname required).
	 *
	 * @return array{status:string,message:string,id:int,url?:string}|array{error:string}
	 *
	 *
	 */
	private function addContact(array $args)
	{
		if (!$this->user->hasRight('societe', 'creer')) {
			return ["error" => "Permission Denied to create contacts."];
		}

		if (empty($args['thirdparty_identifier']) || empty($args['firstname']) || empty($args['lastname'])) {
			return ["error" => "Thirdparty identifier, firstname and lastname are required."];
		}

		$identifier = $args['thirdparty_identifier'];
		$soc = new Societe($this->db);
		$res = 0;

		// Fetch Thirdparty
		if (is_numeric($identifier)) {
			// Fetch by RowID
			$res = $soc->fetch((int) $identifier);
		} else {
			// Fetch by Ref/Code (2nd argument of fetch is $ref)
			$res = $soc->fetch(0, (string) $identifier);
		}

		if ($res <= 0) {
			return ["error" => "Thirdparty not found with identifier: " . $identifier];
		}

		// Prepare Contact
		$contact = new Contact($this->db);
		$contact->socid = $soc->id; // Link to the fetched thirdparty
		$contact->firstname = (string) ($args['firstname'] ?? '');
		$contact->lastname = (string) $args['lastname'];
		$contact->email = isset($args['email']) ? (string) $args['email'] : '';
		$contact->phone_pro = isset($args['phone']) ? (string) $args['phone'] : '';
		$contact->poste = isset($args['role']) ? (string) $args['role'] : '';

		// Attempt Creation
		if ($contact->create($this->user) > 0) {
			return [
				"status"  => "success",
				"message" => "Contact created successfully.",
				"id"      => (int) $contact->id,
				"url"     => DOL_URL_ROOT . "/contact/card.php?id=" . $contact->id
			];
		}

		return ["error" => "Failed to create contact: " . (string) $contact->error];
	}
}
