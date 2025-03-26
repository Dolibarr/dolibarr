<?php

declare(strict_types=1);

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
	 * valid if the amount of project orders corresponds to the amount of the last situation.
	 *
	 * @param DoliDB $db Database handler
	 * @param Facture $facture Invoice object
	 * @return bool Returns true if the last situation is not valid
	 */
	public static function lastSituationIsNotValid(DoliDB $db, Facture $facture): bool
	{
		$lastSituationCompletePrice = $facture->getLastSituationCompletePrice();
		$totalHtOrders = self::getTotalHtOrdersLinkedToProjectOfInvoice($db, $facture);
		$sumTotalHtOrders = round(array_sum(array_column($totalHtOrders, 'total_ht')), 2);

		return !(abs($lastSituationCompletePrice - $sumTotalHtOrders) <= 0.01);
	}
}
