<?php
/* Copyright (C) 2026 Nick Fragoulis
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
 * \file       htdocs/ai/tools/documents.class.php
 * \ingroup    ai
 * \brief      MCP tool listing the files attached to a business object.
 */

require_once DOL_DOCUMENT_ROOT.'/ai/class/mcptool.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';

/**
 * Class ToolDocuments
 *
 * Lists the documents attached to one object (metadata only, never content).
 * The REST documents API is not bridged: it is framework plumbing that needs
 * main.inc.php and fatals in-process, so the listing is done here instead,
 * mirroring what DocumentsApi::getDocumentsListByElement does.
 */
class ToolDocuments extends McpTool
{
	/**
	 * Elements whose files can be listed, with the right needed to read them.
	 * Same shape as the page-context whitelist: nothing outside it is reachable.
	 *
	 * @var array<string, array<int, string>>
	 */
	private $elements = array(
		'facture' => array('facture', 'lire'),
		'invoice_supplier' => array('fournisseur', 'facture', 'lire'),
		'commande' => array('commande', 'lire'),
		'order_supplier' => array('fournisseur', 'commande', 'lire'),
		'propal' => array('propal', 'lire'),
		'supplier_proposal' => array('supplier_proposal', 'lire'),
		'societe' => array('societe', 'lire'),
		'product' => array('produit', 'lire'),
		'contrat' => array('contrat', 'lire'),
		'ticket' => array('ticket', 'read'),
		'fichinter' => array('ficheinter', 'lire'),
		'project' => array('projet', 'lire'),
		'expedition' => array('expedition', 'lire'),
		'reception' => array('reception', 'lire')
	);

	/**
	 * Tool definitions.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return array(
			array(
				"name" => "list_documents",
				"description" => "List the files attached to a business object. Answers \"what files does this invoice have\", \"show the documents of that order\", \"is there anything attached to this third party\". Returns each file name, size, date and a download link, never the content. Give the object by id or by ref; search for it first when neither is known.",
				"inputSchema" => array(
					"type" => "object",
					"properties" => array(
						"element" => array(
							"type" => "string",
							"enum" => array_keys($this->elements),
							"description" => "Object type: facture (customer invoice), invoice_supplier, commande (customer order), order_supplier, propal (customer proposal), supplier_proposal, societe (third party), product, contrat (contract), ticket, fichinter (intervention), project, expedition (shipment), reception."
						),
						"id" => array(
							"type" => "integer",
							"description" => "Rowid of the object, when known."
						),
						"ref" => array(
							"type" => "string",
							"description" => "Reference of the object, when the id is unknown."
						)
					),
					"required" => array("element")
				)
			)
		);
	}

	/**
	 * Categories used by the intent parser.
	 *
	 * @return array<string> List of categories.
	 */
	public function getCategories(): array
	{
		return array('billing', 'commercial', 'thirdparty', 'stock', 'project', 'global');
	}

	/**
	 * Rights required: the read right of the object the files belong to.
	 *
	 * @param string $toolName Tool being executed.
	 * @return array<int,array<int,string>>|string Rights required, or a RIGHTS_* constant.
	 */
	public function getRequiredRights(string $toolName)
	{
		// The element is only known at execution time, so the per-element right
		// is checked in execute(); nothing here is readable without it.
		return $toolName === 'list_documents' ? array() : self::RIGHTS_UNDECLARED;
	}

	/**
	 * Execute the tool.
	 *
	 * @param string               $toolName The name of the tool to execute.
	 * @param array<string, mixed> $args     Arguments.
	 * @return array<string,mixed>|list<array<string,string>> One row per file, or an error.
	 */
	public function execute(string $toolName, array $args)
	{
		if ($toolName !== 'list_documents') {
			return array("error" => "Tool function '".$toolName."' not found.");
		}

		$element = isset($args['element']) ? (string) $args['element'] : '';
		$id = isset($args['id']) ? (int) $args['id'] : 0;
		$ref = isset($args['ref']) ? (string) $args['ref'] : '';

		if (!isset($this->elements[$element])) {
			return array("error" => "Listing documents is not available for '".dol_escape_htmltag($element)."'.");
		}
		if ($id <= 0 && $ref === '') {
			return array("error" => "Provide the id or the ref of the object.");
		}

		$right = $this->elements[$element];
		if (!$this->user->hasRight($right[0], $right[1], isset($right[2]) ? $right[2] : '')) {
			return array("error" => "Permission denied: reading ".$element." documents requires the right ".$right[0]."/".$right[1].".");
		}

		$object = fetchObjectByElement($id, $element, $ref);
		if (!is_object($object) || empty($object->id)) {
			return array("error" => "Object not found.");
		}

		// Entity and per-object access (external users restricted to their own).
		if (!checkUserAccessToObject($this->user, array($object->element), $object, $object->table_element, '')) {
			return array("error" => "Access denied to this object.");
		}

		$upload_dir = getMultidirOutput($object, '', 1);
		if (empty($upload_dir)) {
			return array("error" => "No document directory for this object type.");
		}

		$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', 'date', SORT_DESC, 1);
		if (empty($filearray)) {
			return array();	// no file attached: the chat says so, the model sees an empty list
		}

		$files = array();
		foreach ($filearray as $file) {
			$files[] = array(
				"name" => (string) $file['name'],
				"size" => dol_print_size((int) $file['size'], 1),
				"modified" => dol_print_date($file['date'], 'dayhour'),
				"url" => DOL_URL_ROOT."/document.php?modulepart=".urlencode($element)."&file=".urlencode(dol_sanitizeFileName($object->ref).'/'.$file['name'])
			);
			if (count($files) >= 50) {
				break;
			}
		}

		// A plain list, not a wrapper: the chat renders an array of rows as a
		// table, while a nested array inside an object is dropped by its
		// renderer and only the scalars around it would reach the user.
		return $files;
	}
}
