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
		$cumulated_invoices_of_cycle = array_merge([$facture], $facture->tab_previous_situation_invoice);
		$all_invoices_of_cycle = array_merge($cumulated_invoices_of_cycle, $facture->tab_next_situation_invoice);

		// -- Part 1 - Fetch related Orders to and Invoices from suppliers
		$supplier_docs = array(
			'direct' => self::fetchSousTraitantsCoTraitantsData($db, $facture, 'unique'),
			'cumulated' => self::fetchSousTraitantsCoTraitantsData($db, $facture, 'cumulated'),
			'all' => self::fetchSousTraitantsCoTraitantsData($db, $facture, 'full_cycle'),
		);

		// Manage case where all docs are empty, not to show the table
		$zero_docs = True; 
		foreach ($supplier_docs['all'] as $key => $doc_array) {
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
		$sum_tot_ht_of = fn($arr) => array_sum(array_column($arr, 'total_ht'));
		$sum_tot_ttc_of = fn($arr) => array_sum(array_column($arr, 'total_ttc'));
		$sum_prorata_ht_of = fn($arr) => array_sum(array_column($arr, 'prorata_discount'));

		// Total facturé Couffignal : Factures Clients cumulées - factures Co-trait cumulée
		$sumCumFacturedCoTrait = $sum_tot_ht_of($supplier_docs['cumulated']['invoices_co_trait']);
		$cumulatedTotalHTMainCompany = $sum_tot_ht_of($cumulated_invoices_of_cycle) - $sum_prorata_ht_of($cumulated_invoices_of_cycle);
		$facturedMainCompany = $cumulatedTotalHTMainCompany - $sumCumFacturedCoTrait;
		
		// Total à payer Couffignal
		$sumDirFacturedCoTrait = $sum_tot_ttc_of($supplier_docs['direct']['invoices_co_trait']);
		$sumDirFactureSsTrait = $sum_tot_ht_of($supplier_docs['direct']['invoices_ss_trait']);
		$ToPayMainCompany = $facture->ttc_BTP() - $sumDirFacturedCoTrait - $sumDirFactureSsTrait;
		// Note: Supplier invoices related data are cumulated up to now (column invoiced), while orders related data are for all the serie (column Marché)

		return [
			'company' => [
				'name' => 'Couffignal',
				'market' => ['sum_total_ht' => $sum_tot_ht_of($orders_related_to_cycle)],
				'factured' => ['sum_total_ht' => round($facturedMainCompany, 2)],
				'to_pay' => ['sum_total_ttc' => round($ToPayMainCompany, 2)]
			],
			'co_trait' => self::structureFacturesBySocid($db, $supplier_docs['all']['orders_co_trait'], $supplier_docs['cumulated']['invoices_co_trait'], $supplier_docs['direct']['invoices_co_trait']),
			'sous_trait' => self::structureFacturesBySocid($db, $supplier_docs['all']['orders_ss_trait'], $supplier_docs['cumulated']['invoices_ss_trait'], $supplier_docs['direct']['invoices_ss_trait']),
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
	 * @param array $allCommandesFourn Liste des commandes fournisseurs sur toute la série de factures client.
	 * @param array $CumulatedFacturesFourn Liste des factures fournisseurs cumulées sur la série de factures client jusqu'à la facture actuelle.
	 * @param array $DirectFacturesFourn Liste des factures fournisseurs directement liées à la facture actuelle.
	 * @return array
	 */
	private static function structureFacturesBySocid(DoliDB $db, array $allCommandesFourn, array $CumulatedFacturesFourn, array $DirectFacturesFourn): array
	{
		$result = [];

		// Initialisation des données par société
		foreach (array_merge($allCommandesFourn, $CumulatedFacturesFourn) as $el) {
			if (!isset($result[$el->socid])) {
				$fournisseur = new Fournisseur($db);
				$fournisseur->fetch($el->socid);
				$result[$el->socid] = [
					'soc_id' => $el->socid,
					'name' => $fournisseur->name,
					'market' => ['sum_total_ht' => 0, 'sum_total_ttc' => 0, 'summary' => []],
					'factured' => ['sum_total_ht' => 0, 'sum_total_ttc' => 0, 'summary' => []],
					'to_pay' => ['sum_total_ht' => 0, 'sum_total_ttc' => 0, 'summary' => []],
				];
			}
		}

		$params = array(
			'market' => $allCommandesFourn,
			'factured' => $CumulatedFacturesFourn,
			'to_pay' => $DirectFacturesFourn,
		);

		foreach ($params as $key => $docs) {
			foreach ($docs as $d) {
				$result[$d->socid][$key]['sum_total_ht'] += $d->total_ht;
				$result[$d->socid][$key]['sum_total_ttc'] += $d->total_ttc;
				$result[$d->socid][$key]['summary'][$d->id] = [
					'id' => $d->id,
					'ref' => $d->ref,
					'total_ht' => $d->total_ht,
				];
			}
		}

		// Tri des commandes et factures par référence
		foreach ($result as &$soc) {
			foreach ($params as $key => $docs) {
				usort($soc[$key]['summary'], fn($a, $b) => strcmp($a['ref'], $b['ref']));
			}
		}

		return $result;
	}
}
