<?php

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/couffignal/FactureFournisseurTools.php';

/**
 * Classe utilitaire pour la gestion des co-traitants et sous-traitants.
 */
class CoSousTraitant
{
	/**
	 * Prépare les informations des sous-traitants et co-traitants à partir d'une facture.
	 *
	 * @param DoliDB $db Gestionnaire de base de données.
	 * @param Facture $facture Facture à traiter.
	 * @return array
	 */
	public static function getSousTraitantsCoTraitants(DoliDB $db, Facture $facture): array
	{

		$facture->fetchPreviousNextSituationInvoice();
		$all_invoices_of_cycle = array_merge([$facture], $facture->tab_next_situation_invoice, $facture->tab_next_situation_invoice);

		// -- Part 1 - Fetch related Orders to and Invoices from suppliers
		$cumulated_supplier_docs = self::fetchSousTraitantsCoTraitantsData($db, $facture, 'cumulated');
		$all_supplier_docs = self::fetchSousTraitantsCoTraitantsData($db, $facture, 'full_cycle');
		var_dump($all_supplier_docs);
		// Manage case where all docs are empty, not to show the table
		$zero_docs = True; 
		foreach ($all_supplier_docs as $key => $doc_array) {
			if (!empty($doc_array)) {
				$zero_docs = False;
				break;
			}
		}
		if ($zero_docs) {
			return [];
		}
		
		// -- Part 2 - Fetch related Customer Orders 
		$orders_related_to_cycle = [];
		foreach ($all_invoices_of_cycle as $inv_in_cycle) {
			$orders_related = FactureTools::getTotalHtOrdersLinkedToInvoice($db, $inv_in_cycle);
			$orders_related_to_cycle = array_merge($orders_related_to_cycle, $orders_related);
		}
		$orders_related_to_cycle = array_map("unserialize", array_unique(array_map("serialize", $orders_related_to_cycle)));

		// -- Part 3 - Calculate values and results 
		// Total marché Couffignal
		$totalHtMarche = array_sum(array_column($orders_related_to_cycle, 'total_ht'));
		// Total facturé Couffignal : Factures Clients cumulées - factures Co-trait cumulée
		$sumFacturedCoTrait = array_sum(array_column($cumulated_supplier_docs['invoices_co_trait'], 'total_ht'));
		$facturedMainCompanyDiff = $facture->computeCompletedPrice(false) - $sumFacturedCoTrait;
		// Note: Supplier invoices related data are cumulated up to now (column invoiced), while orders related data are for all the serie (column Marché)

		return [
			'company' => [
				'name' => 'Couffignal',
				'market' => ['sum_total_ht' => $totalHtMarche],
				'factured' => ['sum_total_ht' => round($facturedMainCompanyDiff, 2)],
			],
			'co_trait' => self::structureFacturesBySocid($db, $all_supplier_docs['orders_co_trait'], $cumulated_supplier_docs['invoices_co_trait']),
			'sous_trait' => self::structureFacturesBySocid($db, $all_supplier_docs['orders_ss_trait'], $cumulated_supplier_docs['invoices_ss_trait']),
		];
	}


	/**
	 * Charge les factures fournisseurs et commandes des sous-traitants et co-traitants à partir d'une facture.
	 *
	 * @param DoliDB $db Gestionnaire de base de données.
	 * @param Facture $facture Facture à traiter.
	 * @param string $mode Mode:'unique', cumulated', 'full_cycle' for all documents attached to resp. this invoice, invoices in cycle up to the given one, all invoices in cycle. 
	 * @return array of array
	 */
	public static function fetchSousTraitantsCoTraitantsData(DoliDB $db, Facture $facture, string $mode): array
	{
		// Fetch related Orders and Invoices
		$facturesFourn = FactureFournisseurTools::getFacturesFournValidatedFromDebtCompensationLinks($facture, $db, $mode);
		$commandesFourn = FactureFournisseurTools::getOrdersValidatedFromFacturesFourn($facturesFourn);
		if (!$commandesFourn && !$facturesFourn) return [];

		// CommandeFournisseur via extrafields: options_typefournisseur, 1 pour co-traitant, 2 pour sous-traitant
		$filterCommandesFourn = fn($arr, $type) => array_filter($arr, fn(CommandeFournisseur $el) =>
			array_key_exists('options_typefournisseur', $el->array_options) && $el->array_options['options_typefournisseur'] == $type
		);

		return array(
			'orders_co_trait' => $filterCommandesFourn($commandesFourn, '1'),
			'orders_ss_trait' => $filterCommandesFourn($commandesFourn, '2'),
			// Autoliquidation de TVA = sous traitant, pas d'autoliquidation de TVA = co-traitant
			'invoices_co_trait' => array_filter($facturesFourn, fn($el) => !$el->vat_reverse_charge),
			'invoices_ss_trait' => array_filter($facturesFourn, fn($el) => $el->vat_reverse_charge),
		);
	}


	/**
	 * Structure les commandes et factures fournisseurs par société.
	 *
	 * @param DoliDB $db Gestionnaire de base de données.
	 * @param array $commandesFourn Liste des commandes fournisseurs.
	 * @param array $facturesFourn Liste des factures fournisseurs.
	 * @return array
	 */
	private static function structureFacturesBySocid(DoliDB $db, array $commandesFourn, array $facturesFourn): array
	{
		$result = [];

		// Initialisation des données par société
		foreach (array_merge($commandesFourn, $facturesFourn) as $el) {
			if (!isset($result[$el->socid])) {
				$fournisseur = new Fournisseur($db);
				$fournisseur->fetch($el->socid);
				$result[$el->socid] = [
					'soc_id' => $el->socid,
					'name' => $fournisseur->name,
					'market' => ['sum_total_ht' => 0, 'orders_fourn_resume' => []],
					'factured' => ['sum_total_ht' => 0, 'factures_fourn_resume' => []],
				];
			}
		}

		// Ajout des commandes fournisseurs
		foreach ($commandesFourn as $c) {
			$result[$c->socid]['market']['sum_total_ht'] += $c->total_ht;
			$result[$c->socid]['market']['orders_fourn_resume'][$c->id] = [
				'order_fourn_id' => $c->id,
				'ref' => $c->ref,
				'total_ht' => $c->total_ht,
			];
		}

		// Ajout des factures fournisseurs
		foreach ($facturesFourn as $f) {
			$result[$f->socid]['factured']['sum_total_ht'] += $f->total_ht;
			$result[$f->socid]['factured']['factures_fourn_resume'][$f->id] = [
				'facture_fourn_id' => $f->id,
				'ref' => $f->ref,
				'total_ht' => $f->total_ht,
			];
		}

		// Tri des commandes et factures par référence
		foreach ($result as &$soc) {
			usort($soc['market']['orders_fourn_resume'], fn($a, $b) => strcmp($a['ref'], $b['ref']));
			usort($soc['factured']['factures_fourn_resume'], fn($a, $b) => strcmp($a['ref'], $b['ref']));
		}

		return $result;
	}
}
