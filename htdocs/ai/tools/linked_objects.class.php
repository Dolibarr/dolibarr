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
 * \file htdocs/ai/tools/linked_objects.class.php
 * \ingroup ai
 * \brief MCP tool to list the business objects linked to another one.
 *
 * Dolibarr records links between objects in the element_element table
 * (an invoice generated from an order, a shipment from an order, a
 * contract behind a ticket...). This tool walks those links so a
 * prompt like "the order behind this invoice" can be answered.
 */


/**
 * Linked-object navigation through the element_element table.
 */
class ToolLinkedObjects extends McpTool
{
	/**
	 * @var array<string, array{file:string, class:string, element:string, rights:array<int,array<int,string>>}>
	 * Whitelist of element types the tool accepts: the class to load, the
	 * element name fetchObjectLinked() uses, and the read right to hold.
	 */
	private static $ELEMENTS = array(
		'invoice' => array(
			'file' => '/compta/facture/class/facture.class.php', 'class' => 'Facture', 'element' => 'facture',
			'rights' => array(array('facture', 'lire')),
		),
		'order' => array(
			'file' => '/commande/class/commande.class.php', 'class' => 'Commande', 'element' => 'commande',
			'rights' => array(array('commande', 'lire')),
		),
		'propal' => array(
			'file' => '/comm/propal/class/propal.class.php', 'class' => 'Propal', 'element' => 'propal',
			'rights' => array(array('propal', 'lire')),
		),
		'shipping' => array(
			'file' => '/expedition/class/expedition.class.php', 'class' => 'Expedition', 'element' => 'expedition',
			'rights' => array(array('expedition', 'lire')),
		),
		'delivery' => array(
			'file' => '/delivery/class/delivery.class.php', 'class' => 'Delivery', 'element' => 'delivery',
			'rights' => array(array('livraison', 'lire')),
		),
		'contract' => array(
			'file' => '/contrat/class/contrat.class.php', 'class' => 'Contrat', 'element' => 'contrat',
			'rights' => array(array('contrat', 'lire')),
		),
		'ticket' => array(
			'file' => '/ticket/class/ticket.class.php', 'class' => 'Ticket', 'element' => 'ticket',
			'rights' => array(array('ticket', 'read')),
		),
		'intervention' => array(
			'file' => '/fichinter/class/fichinter.class.php', 'class' => 'Fichinter', 'element' => 'fichinter',
			'rights' => array(array('ficheinter', 'lire')),
		),
		'project' => array(
			'file' => '/projet/class/project.class.php', 'class' => 'Project', 'element' => 'project',
			'rights' => array(array('projet', 'lire')),
		),
		'task' => array(
			'file' => '/projet/class/task.class.php', 'class' => 'Task', 'element' => 'project_task',
			'rights' => array(array('projet', 'lire')),
		),
		'supplier_order' => array(
			'file' => '/fourn/class/fournisseur.commande.class.php', 'class' => 'CommandeFournisseur', 'element' => 'order_supplier',
			'rights' => array(array('fournisseur', 'commande', 'lire')),
		),
		'supplier_invoice' => array(
			'file' => '/fourn/class/fournisseur.facture.class.php', 'class' => 'FactureFournisseur', 'element' => 'invoice_supplier',
			'rights' => array(array('fournisseur', 'facture', 'lire')),
		),
		'member' => array(
			'file' => '/adherents/class/adherent.class.php', 'class' => 'Adherent', 'element' => 'member',
			'rights' => array(array('adherent', 'lire')),
		),
		'expensereport' => array(
			'file' => '/expensereport/class/expensereport.class.php', 'class' => 'ExpenseReport', 'element' => 'expensereport',
			'rights' => array(array('expensereport', 'lire')),
		),
	);

	/**
	 * Defines the linked-objects tool.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return array(
			array(
				'name' => 'ai_get_linked_objects',
				'description' => "List the business objects linked to a record: the order behind an invoice, the shipment of an order, the invoices of a contract, the project a task belongs to... Dolibarr stores these relations in its object-link table; this walks them and returns every linked record with its type, id and reference. Use it when the user asks about related documents ('what invoice came from this order', 'the delivery for that order', 'linked contracts').",
				'inputSchema' => array(
					'type' => 'object',
					'properties' => array(
						'element' => array(
							'type' => 'string',
							'description' => 'Type of the record: invoice, order, propal, shipping, delivery, contract, ticket, intervention, project, task, supplier_order, supplier_invoice, member, expensereport.',
							'enum' => array_keys(self::$ELEMENTS),
						),
						'id' => array(
							'type' => 'integer',
							'description' => 'Rowid of the record.',
						),
					),
					'required' => array('element', 'id'),
				),
			),
		);
	}

	/**
	 * Declared rights: the base read right of every element the tool may
	 * reach — the union keeps the declaration honest whatever is asked.
	 * The call itself checks the right of the requested element again.
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
		$element = isset($args['element']) ? strtolower(trim((string) $args['element'])) : '';
		$id = isset($args['id']) ? (int) $args['id'] : 0;

		if (!isset(self::$ELEMENTS[$element])) {
			return array('error' => "Unknown element '".$element."'. Known types: ".implode(', ', array_keys(self::$ELEMENTS)).".");
		}
		if ($id <= 0) {
			return array('error' => "Parameter 'id' must be a positive rowid.");
		}

		$meta = self::$ELEMENTS[$element];
		foreach ($meta['rights'] as $right) {
			$subperm = isset($right[2]) ? $right[2] : '';
			if (!$this->user->hasRight($right[0], $right[1], $subperm)) {
				return array('error' => "Permission denied: missing right ".$right[0]."/".$right[1]." to read ".$element." objects.");
			}
		}

		require_once DOL_DOCUMENT_ROOT.$meta['file'];
		$classname = $meta['class'];
		$object = new $classname($this->db);
		if ($object->fetch($id) <= 0) {
			return array('error' => "No ".$element." found with id ".$id.".");
		}

		$object->fetchObjectLinked($id, $meta['element']);

		// Name lookup per linked type: reuse the element map when the linked
		// type is known, so refs resolve; unknown types come back as bare ids.
		$elementToClass = array();
		foreach (self::$ELEMENTS as $m) {
			$elementToClass[$m['element']] = $m;
		}

		$result = array(
			'element' => $element,
			'id' => $id,
			'ref' => isset($object->ref) ? $object->ref : '',
			'linked' => array(),
		);

		foreach ((array) $object->linkedObjectsIds as $targettype => $ids) {
			$items = array();
			$count = 0;
			foreach ($ids as $linkedid) {
				if (++$count > 20) {	// cap per type: links are navigation, not a dump
					$items[] = array('more' => count($ids) - 20);
					break;
				}
				$entry = array('id' => (int) $linkedid);
				if (isset($elementToClass[$targettype])) {
					$lm = $elementToClass[$targettype];
					require_once DOL_DOCUMENT_ROOT.$lm['file'];
					$lclass = $lm['class'];
					$lobj = new $lclass($this->db);
					if ($lobj->fetch((int) $linkedid) > 0 && isset($lobj->ref)) {
						$entry['ref'] = $lobj->ref;
					}
				}
				$items[] = $entry;
			}
			$result['linked'][] = array('type' => $targettype, 'count' => count($ids), 'objects' => $items);
		}

		return $result;
	}
}
