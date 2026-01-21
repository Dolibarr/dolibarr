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
	 * Get total HT of validated orders linked to the invoice.
	 *
	 * @param DoliDB $db Database handler
	 * @param Facture $facture Invoice object
	 * @param bool $load_obj Load and return object in response if True
	 * @return array List of orders with their client reference and total HT
	 */
	public static function getTotalHtOrdersLinkedToInvoice(DoliDB $db, Facture $invoice, bool $load_obj = False): array
	{

		$invoice->fetchObjectLinked();

		$orders = [];
		if (empty($invoice->linkedObjects['commande'])) { return []; }
		foreach ($invoice->linkedObjects['commande'] as $id => $order) {
			if (in_array($order->statut, [
				Commande::STATUS_VALIDATED,
				Commande::STATUS_SHIPMENTONPROCESS,
				Commande::STATUS_ACCEPTED
			])) {
				$orders[] = $order;
			}
		}
		$orders = CommandeTools::sortOrdersByDateAndRef($orders);
		if ($load_obj) {
			return array_map(static fn ($o) => ['ref_client' => $o->ref_ext . '(' .$o->ref.')', 'total_ht' => $o->total_ht, 'obj' => $o], $orders);
		}
		return array_map(static fn ($o) => ['ref_client' => $o->ref_ext . '(' .$o->ref.')', 'total_ht' => $o->total_ht], $orders);
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
		$totalHtOrders = self::getTotalHtOrdersLinkedToInvoice($db, $facture);
		$sumTotalHtOrders = array_sum(array_column($totalHtOrders, 'total_ht'));

		return $lastSituationCompletePrice - $sumTotalHtOrders;
	}
}
