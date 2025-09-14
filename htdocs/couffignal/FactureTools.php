<?php

declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/couffignal/CommandeTools.php';

/**
 * Tools for Invoice class
 */
class FactureTools
{
	/**
	 * Get total HT of validated orders linked to the project of the invoice.
	 *
	 * @param DoliDB $db Database handler
	 * @param Facture $facture Invoice object
	 * @return array List of orders with their client reference and total HT
	 */
	public static function getTotalHtOrdersLinkedToProjectOfInvoice(DoliDB $db, Facture $facture): array
	{
		if ($facture->project === null) {
			$facture->fetch_project();
		}

		$orders = CommandeTools::getOrdersValidatedFromProject($db, $facture->project, (int) $facture->socid);

		return array_map(static fn ($order) => ['ref_client' => $order->ref_client, 'total_ht' => $order->total_ht], $orders);
	}

	/**
	 * Calculate the difference between the last situation complete price and the total HT of orders linked to the project of the invoice.
	 *
	 * @param DoliDB $db Database handler
	 * @param Facture $facture Invoice object
	 * @return float The difference
	 */
	public static function calculateDifference(DoliDB $db, Facture $facture): float
	{
		$lastSituationCompletePrice = $facture->getLastSituationCompletePrice(false);
		$totalHtOrders = self::getTotalHtOrdersLinkedToProjectOfInvoice($db, $facture);
		$sumTotalHtOrders = array_sum(array_column($totalHtOrders, 'total_ht'));

		return $lastSituationCompletePrice - $sumTotalHtOrders;
	}
}
