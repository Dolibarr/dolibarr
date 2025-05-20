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
	 * Get total amount excluding tax of validated orders linked to the project of the invoice
	 *
	 * @param DoliDB $db Database handler
	 * @param Facture $facture Invoice object
	 * @return array Array of orders with ref_client and total_ht
	 */
	public static function getTotalHtOrdersLinkedToProjectOfInvoice(DoliDB $db, Facture $facture): array
	{
		if ($facture->project === null) {
			$facture->fetch_project();
		}

		$orders = CommandeTools::getOrdersValidatedFromProject($db, $facture->project);

		return array_map(static fn ($order) => ['ref_client' => $order->ref_client, 'total_ht' => $order->total_ht], $orders);
	}

	/**
	 * Check the difference between the last situation price and the total orders.
	 *
	 * @param DoliDB $db Database handler
	 * @param Facture $facture Invoice object
	 * @return float Difference between the last situation price and the total orders
	 */
	public static function calculateDifference(DoliDB $db, Facture $facture): float
	{
		$lastSituationCompletePrice = $facture->getLastSituationCompletePrice(false);
		$totalHtOrders = self::getTotalHtOrdersLinkedToProjectOfInvoice($db, $facture);
		$sumTotalHtOrders = array_sum(array_column($totalHtOrders, 'total_ht'));

		return $lastSituationCompletePrice - $sumTotalHtOrders;
	}
}
