<?php
/* Copyright (C) 2026	Carlos Herrera		<cehojac@gmail.com>
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
 * \file htdocs/ai/tools/ecm_documents.class.php
 * \ingroup ai
 * \brief MCP tool to list the documents attached to a record.
 *
 * Roadmap item "ECM/file read tools" (#38356): "what files does this
 * have" — the documents tab already identifies its object, this tool
 * lists the files stored for it by delegating to the documents API
 * (same path resolution and same access checks as the REST endpoint).
 */

/**
 * ECM document listing for the assistant.
 *
 * api_documents.class.php is required lazily in execute(), not here:
 * that file pulls main.inc.php, which must not load at tool-scan time.
 */
class ToolEcmDocuments extends McpTool
{
	/**
	 * Defines the documents tool.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return array(
			array(
				'name' => 'ai_list_object_documents',
				'description' => "List the documents attached to a Dolibarr record (ECM): generated PDFs, uploaded scans, invoices of expenses... Use it when the user asks 'what files does this have', 'is there a signed scan on this order', 'show me the attachments'. Returns name, size and date per file — the content itself is not fetched.",
				'inputSchema' => array(
					'type' => 'object',
					'properties' => array(
						'modulepart' => array(
							'type' => 'string',
							'description' => "Module part of the record: 'invoice', 'order', 'propal', 'contract', 'shipment', 'intervention', 'project', 'thirdparty', 'ticket', 'supplier_order', 'supplier_invoice', 'expensereport', 'member', 'product'.",
						),
						'id' => array(
							'type' => 'integer',
							'description' => 'Rowid of the record.',
						),
					),
					'required' => array('modulepart', 'id'),
				),
			),
		);
	}

	/**
	 * Declared rights: document listing crosses module boundaries, so the
	 * declaration is the base read right; the documents API re-checks the
	 * element's own read right inside getDocumentsListByElement().
	 *
	 * @param  string $toolName Tool being executed.
	 * @return array<int,array<int,string>> Rights required.
	 */
	public function getRequiredRights(string $toolName)
	{
		return array(array('societe', 'lire'));
	}

	/**
	 * Execute tool.
	 *
	 * @param  string $name Tool function name
	 * @param  array<string,mixed> $args Tool arguments
	 * @return mixed
	 */
	public function execute(string $name, array $args)
	{
		$modulepart = isset($args['modulepart']) ? strtolower(trim((string) $args['modulepart'])) : '';
		$id = isset($args['id']) ? (int) $args['id'] : 0;

		if ($modulepart === '') {
			return array('error' => "Parameter 'modulepart' is required.");
		}
		if ($id <= 0) {
			return array('error' => "Parameter 'id' must be a positive rowid.");
		}

		// Loaded here, not at scan time: api_documents pulls main.inc.php.
		require_once DOL_DOCUMENT_ROOT.'/api/class/api_access.class.php';
		require_once DOL_DOCUMENT_ROOT.'/api/class/api_documents.class.php';

		// The API enforces the element's own read right on the acting user.
		DolibarrApiAccess::$user = $this->user;
		$documents = new Documents();

		try {
			$list = $documents->getDocumentsListByElement($modulepart, $id);
		} catch (Throwable $e) {
			return array('error' => $e->getMessage(), 'code' => $e->getCode());
		}

		$files = array();
		foreach ((array) $list as $doc) {
			$files[] = array(
				'name' => isset($doc['name']) ? $doc['name'] : (isset($doc['filename']) ? $doc['filename'] : ''),
				'size' => isset($doc['size']) ? $doc['size'] : null,
				'date' => isset($doc['date']) ? $doc['date'] : null,
				'type' => isset($doc['type']) ? $doc['type'] : null,
			);
		}

		return array(
			'modulepart' => $modulepart,
			'id' => $id,
			'count' => count($files),
			'files' => $files,
		);
	}
}
