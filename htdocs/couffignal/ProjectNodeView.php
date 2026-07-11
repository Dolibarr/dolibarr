<?php

declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/couffignal/CoSousTraitant.php';
require_once DOL_DOCUMENT_ROOT.'/couffignal/FactureTools.php';
require_once DOL_DOCUMENT_ROOT.'/couffignal/FactureFournisseurTools.php';
if (isModEnabled('clientpayfourn')) dol_include_once('/clientpayfourn/class/linkclientpayfourn.class.php');
// Check compliance to module requirements
if (!isModEnabled('clientpayfourn')) {
	dol_syslog("Module clientpayfourn must be enabled for Co-Sous-traitant table to work", LOG_WARN);
	setEventMessages("", "Module clientpayfourn must be enabled for Co-Sous-traitant table to work", 'mesgs');
}

/**
 * Tools for ProjectNodeView class
 */
class ProjectNodeView
{
	// Types supported by ProjectNodeView::loadNode()
	const MANAGED_ELEMENTS = [
			'Commande' => [
				'custom_ref_attr' => 'ref_client', 
				'color' => '#65953d', 
				'link' => '/commande/card.php?id='
			], 
			'Facture' => [
				'custom_ref_attr' => 'ref_client',
				'color' => '#a23121', 
				'link' => '/compta/facture/card.php?facid='
			], 
			'CommandeFournisseur' => [
				'custom_ref_attr' => 'ref_supplier', 
				'color' => '#599caf', 
				'link' => '/fourn/commande/card.php?id='
			], 
			'FactureFournisseur' => [
				'custom_ref_attr' => 'ref_supplier', 
				'color' => '#6059af', 
				'link' => '/fourn/facture/card.php?facid=',
			]
		]; 

	/**
	 * Get the seeds - the starting point of the invoicing cycles - present in the project
	 *
	 * @param DoliDB $db
	 * @param Project $project Project object for which we search seeds
	 * @param array $properties Properties for get_element_list
	 * @param string $dates Forwarded
	 * @param string $datee Forwarded
	 * 
	 * @return array List of Invoices (Facture)
	 */
	public static function getCycleSeeds(DoliDB $db, Project $project, array $properties, ?string $dates, ?string $datee): array
	{
		// Collect all invoices
		$list_invoices = $project->get_element_list('invoice', $properties['table'], $properties['datefieldname'], $dates, $datee, 'fk_projet');

		// Assume only one serie of invoices
		$seeds = [];
		foreach ($list_invoices as $k => $id) {
			$inv = new Facture($db);
			$inv->fetch($id);
			if ($inv->is_first()) {
				// To support
				$inv->fetchPreviousNextSituationInvoice();
				$seeds[] = $inv;
			}
		}

		return $seeds;
	}


	/**
	 * Load a Node from its representing object
	 *
	 * @param Object Facture|FactureFournisseur|Commande|CommandeFournisseur $obj
	 * 
	 * @return array Array representing a node
	 */
	public static function loadNode(Facture|FactureFournisseur|Commande|CommandeFournisseur $obj): array
	{
		if (array_key_exists(get_class($obj), self::MANAGED_ELEMENTS)) {
			$k = get_class($obj);
			$custom_ref_attr = self::MANAGED_ELEMENTS[$k]['custom_ref_attr'];
			return [
			        'id' => $obj->ref,
			        'label' => $obj->ref . ' - ' . $obj->$custom_ref_attr,
			        'obj' => $obj,
			        'type' => $k,
			    	'link' => self::MANAGED_ELEMENTS[$k]['link'].$obj->id,
				    'tooltip' => $obj->getNomUrl(1),
				];
		}
		return [];
	}


	/**
	 * Load Invoices nodes related to an invoicing cycle
	 *
	 * @param array $seed List of nodes representing the FactureFournisseur related to invoicing cycle
	 * @param array $links Links object for the graph
	 * 	 * 
	 * @return array List of Invoices nodes (Facture)
	 */
	public static function loadInvoicesNodes(Facture $seed, array &$links): array
	{
		// Initialize invoice nodes with invoices
		$inv_nodes = [];
		foreach (array_merge([$seed], $seed->tab_next_situation_invoice) as $inv) {
		    $inv_nodes[$inv->ref] = self::loadNode($inv);
		}
		
		// Initialize links between nodes
		$keys = array_keys($inv_nodes);
		foreach ($keys as $index => $key) {
		    // Check if there is a next key to avoid out-of-bounds
		    if (isset($keys[$index + 1])) {
		        $links[] = [
		            'from' => $inv_nodes[$key]['id'],
		            'to' => $inv_nodes[$keys[$index + 1]]['id']
		        ];
		    }
		}

		return $inv_nodes;
	}


	/**
	 * Load Orders nodes for all invoice nodes of a cycle
	 *
	 * @param array $inv_nodes List of nodes representing the Factures in invoicing cycle
	 * @param array $links Links object for the graph
	 * 
	 * @return array List of Orders nodes (Commande)
	 */
	public static function loadOrdersNodes(DoliDB $db, array $inv_nodes, array &$links): array
	{
		$order_nodes = [];
		foreach ($inv_nodes as $i => $node) {
			$orders = FactureTools::getTotalHtOrdersLinkedToInvoice($db, $node['obj'], True);
			// Add new nodes
			foreach ($orders as $o) {
			    $order_nodes[$o['obj']->ref] = self::loadNode($o['obj']);
				// Create links 
			    $o['obj']->fetchObjectLinked();
			    foreach ($o['obj']->linkedObjects['facture'] as $id => $facture) {
					$links[] = [
			            'from' => $o['obj']->ref,
			            'to' => $facture->ref
			        ];
		        }
			}
		}

		return $order_nodes;
	}


	/**
	 * Load Supplier Invoices nodes for all invoice nodes of a cycle
	 *
	 * @param array $inv_nodes List of nodes representing the Factures in invoicing cycle
	 * @param array $links Links object for the graph
	 * 
	 * @return array List of Supplier Invoices nodes (FactureFournisseur)
	 */
	public static function loadSupplierInvoicesNodes(DoliDB $db, array $inv_nodes, array &$links): array
	{
		$su_inv_nodes = [];
		foreach ($inv_nodes as $i => $node) {
			$obj = new LinkClientPayFourn($db);
			$su_invs = $obj->getLinkedObjects($node['obj'], $db);

			// Add new nodes
			foreach ($su_invs as $su_i) {
			    $su_inv_nodes[$su_i->ref] = self::loadNode($su_i);
				// Create links 
				$links[] = [
		            'from' => $node['id'],
		            'to' => $su_i->ref
		        ];
			}
		}

		return $su_inv_nodes;
	}


	/**
	 * Load Supplier Orders nodes for all supplier invoice nodes related to an invoicing cycle
	 *
	 * @param array $su_inv_nodes List of nodes representing the FactureFournisseur related to invoicing cycle
	 * @param array $links Links object for the graph
	 * 
	 * @return array List of Supplier Orders nodes (Commande)
	 */
	public static function loadSupplierOrdersNodes(array $su_inv_nodes, array &$links): array
	{
		$su_order_nodes = [];
		foreach ($su_inv_nodes as $i => $node) {
			$su_orders = FactureFournisseurTools::getOrdersValidatedFromFacturesFourn([$node['obj']]);
			// Add new nodes
			foreach ($su_orders as $o) {
			    $su_order_nodes[$o->ref] = self::loadNode($o);
				// Create links 
			    $o->fetchObjectLinked();
			    foreach ($o->linkedObjects['invoice_supplier'] as $id => $su_inv) {
					$links[] = [
			            'from' => $o->ref,
			            'to' => $su_inv->ref
			        ];
		        }
			}
		}

		return $su_order_nodes;
	}

}