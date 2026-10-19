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
 * \file htdocs/ai/tools/describe_object.class.php
 * \ingroup ai
 * \brief MCP tool that returns a compact business description of a record.
 *
 * Generative actions on context (roadmap #38356): "summarize this
 * proposal for the customer", "draft a reply to this ticket". The model
 * needs the record's content in a compact form — ref, third party,
 * dates, status, lines, totals — not the REST payload. This tool loads
 * the whitelisted object and returns that digest.
 */


/**
 * Compact record digest for generative prompts.
 */
class ToolDescribeObject extends McpTool
{
	/**
	 * @var array<string, array{file:string, class:string, rights:array<int,array<int,string>>}>
	 * Whitelist of element types: class to load and read right to hold.
	 */
	private static $ELEMENTS = array(
		'invoice' => array(
			'file' => '/compta/facture/class/facture.class.php', 'class' => 'Facture',
			'rights' => array(array('facture', 'lire')),
		),
		'order' => array(
			'file' => '/commande/class/commande.class.php', 'class' => 'Commande',
			'rights' => array(array('commande', 'lire')),
		),
		'propal' => array(
			'file' => '/comm/propal/class/propal.class.php', 'class' => 'Propal',
			'rights' => array(array('propal', 'lire')),
		),
		'contract' => array(
			'file' => '/contrat/class/contrat.class.php', 'class' => 'Contrat',
			'rights' => array(array('contrat', 'lire')),
		),
		'ticket' => array(
			'file' => '/ticket/class/ticket.class.php', 'class' => 'Ticket',
			'rights' => array(array('ticket', 'read')),
		),
		'intervention' => array(
			'file' => '/fichinter/class/fichinter.class.php', 'class' => 'Fichinter',
			'rights' => array(array('ficheinter', 'lire')),
		),
		'project' => array(
			'file' => '/projet/class/project.class.php', 'class' => 'Project',
			'rights' => array(array('projet', 'lire')),
		),
		'thirdparty' => array(
			'file' => '/societe/class/societe.class.php', 'class' => 'Societe',
			'rights' => array(array('societe', 'lire')),
		),
		'supplier_order' => array(
			'file' => '/fourn/class/fournisseur.commande.class.php', 'class' => 'CommandeFournisseur',
			'rights' => array(array('fournisseur', 'commande', 'lire')),
		),
		'supplier_invoice' => array(
			'file' => '/fourn/class/fournisseur.facture.class.php', 'class' => 'FactureFournisseur',
			'rights' => array(array('fournisseur', 'facture', 'lire')),
		),
		'expensereport' => array(
			'file' => '/expensereport/class/expensereport.class.php', 'class' => 'ExpenseReport',
			'rights' => array(array('expensereport', 'lire')),
		),
	);

	/**
	 * Defines the describe-object tool.
	 *
	 * @return list<array<string, mixed>> Array of tool definitions.
	 */
	public function getDefinitions(): array
	{
		return array(
			array(
				'name' => 'ai_describe_object',
				'description' => "Return a compact business digest of a Dolibarr record: reference, third party, dates, status, lines and totals. Call this first when the user asks to summarize, translate, explain or draft a message about the record on screen ('summarize this proposal', 'draft a reply to this ticket'), so the generation is grounded on the real content instead of guesses.",
				'inputSchema' => array(
					'type' => 'object',
					'properties' => array(
						'element' => array(
							'type' => 'string',
							'description' => 'Type of the record: invoice, order, propal, contract, ticket, intervention, project, thirdparty, supplier_order, supplier_invoice, expensereport.',
							'enum' => array_keys(self::$ELEMENTS),
						),
						'id' => array(
							'type' => 'integer',
							'description' => 'Rowid of the record. The page context carries it.',
						),
					),
					'required' => array('element', 'id'),
				),
			),
		);
	}

	/**
	 * Declared rights: the base read right; the element's own right is
	 * checked again inside execute() for the requested type.
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
		/** @var CommonObject $object */
		$object = new $classname($this->db);
		if ($object->fetch($id) <= 0) {
			return array('error' => "No ".$element." found with id ".$id.".");
		}

		$result = array(
			'element' => $element,
			'id' => $id,
			'ref' => isset($object->ref) ? $object->ref : '',
			'status' => method_exists($object, 'getLibStatut') ? dol_string_nohtmltag($object->getLibStatut(5)) : (isset($object->status) ? $object->status : null),
		);

		// Third party, resolved to name when we can read it.
		if (method_exists($object, 'fetch_thirdparty')) {
			$object->fetch_thirdparty();
			if (!empty($object->thirdparty) && !empty($object->thirdparty->name)) {
				$result['thirdparty'] = array('id' => (int) $object->thirdparty->id, 'name' => $object->thirdparty->name);
			}
		}

		$datemap = array('date' => 'date', 'date_validation' => 'validated_on', 'date_creation' => 'created_on', 'date_modification' => 'modified_on', 'delivery_date' => 'delivery_on', 'date_lim_reglement' => 'payment_due_on');
		foreach ($datemap as $prop => $label) {
			if (!empty($object->{$prop})) {
				$result['dates'][$label] = dol_print_date($object->{$prop}, 'day', 'tzuser');
			}
		}

		if (!empty($object->note_public)) {
			$result['note'] = dol_trunc(preg_replace('/\s+/', ' ', strip_tags((string) $object->note_public)), 500);
		}

		// Detail lines — capped: enough for a summary, not a data dump.
		if (!empty($object->lines) && is_array($object->lines)) {
			$lines = array();
			$count = 0;
			foreach ($object->lines as $line) {
				if (++$count > 30) {
					$lines[] = array('more' => count($object->lines) - 30);
					break;
				}
				$l = array(
					'description' => dol_trunc(preg_replace('/\s+/', ' ', strip_tags((string) (isset($line->description) ? $line->description : (isset($line->desc) ? $line->desc : '')))), 120),
				);
				if (isset($line->qty)) {
					$l['qty'] = $line->qty;
				}
				if (isset($line->subprice)) {
					$l['price'] = price($line->subprice);
				}
				$lines[] = $l;
			}
			$result['lines'] = $lines;
		}

		foreach (array('total_ht' => 'total_excl_tax', 'total_tva' => 'total_tax', 'total_ttc' => 'total_incl_tax') as $prop => $label) {
			if (isset($object->{$prop}) && $object->{$prop} !== '') {
				$result['totals'][$label] = price($object->{$prop});
			}
		}

		return $result;
	}
}
