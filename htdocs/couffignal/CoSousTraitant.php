<?php

require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.class.php';
require_once DOL_DOCUMENT_ROOT.'/couffignal/CommandFournisseurTools.php';
require_once DOL_DOCUMENT_ROOT.'/couffignal/FactureFournisseurTools.php';
require_once DOL_DOCUMENT_ROOT.'/couffignal/PaiementTools.php';

/**
 * Classe utilitaire pour la gestion des co-traitants et sous-traitants.
 */
class CoSousTraitant
{
	/**
	 * Récupère les informations des sous-traitants et co-traitants à partir d'une facture.
	 *
	 * @param DoliDB $db Gestionnaire de base de données.
	 * @param Facture $facture Facture à traiter.
	 * @return array
	 */
	public static function getSousTraitantsCoTraitants(DoliDB $db, Facture $facture): array
	{
		if (!$facture->project) {
			$facture->fetch_project();
			if (!$facture->project) return [];
		}

		$paiementSousTraitantId = PaiementTools::getPaimentSousTraitantId($db);
		if (!$paiementSousTraitantId) return [];

		$commandesFourn = CommandFournisseurTools::getOrdersValidatedFromProject($facture->project, $db);
		$facturesFourn = FactureFournisseurTools::getFacturesFournValidatedFromProject($facture->project, $db);
		if (!$commandesFourn && !$facturesFourn) return [];

		$filter = fn($arr, $isSous) => array_filter($arr, fn($el) => ($el->mode_reglement_id == $paiementSousTraitantId) === $isSous);
		$commandesCotraitants = $filter($commandesFourn, false);
		$commandesSousTraitants = $filter($commandesFourn, true);
		$facturesCoTraitants = $filter($facturesFourn, false);
		$facturesFournSousTraitant = $filter($facturesFourn, true);

		$sumFacturedFourn = array_sum(array_column($facturesFourn, 'total_ht'));
		$facturedMainCompanyDiff = $facture->getLastSituationCompletePrice(false) - $sumFacturedFourn;
		$totalHtOrders = array_sum(array_column(CommandeTools::getOrdersValidatedFromProject($db, $facture->project), 'total_ht'));

		return [
			'company' => [
				'name' => 'Couffignal',
				'market' => ['sum_total_ht' => $totalHtOrders - $sumFacturedFourn],
				'factured' => ['sum_total_ht' => round($facturedMainCompanyDiff, 2)],
			],
			'co_trait' => self::structureFacturesBySocid($db, $commandesCotraitants, $facturesCoTraitants),
			'sous_trait' => self::structureFacturesBySocid($db, $commandesSousTraitants, $facturesFournSousTraitant),
		];
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
