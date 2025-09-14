<?php

declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

/**
 * Tools for Commande class
 */
class CommandeTools
{
	/**
	 * Get validated orders linked to a project, optionally filtered by company ID
	 *
	 * @param DoliDB $db Database handler
	 * @param Project $project Project object
	 * @param int|null $socId Optional company ID to filter orders
	 * @return array List of validated orders linked to the project
	 */
	public static function getOrdersValidatedFromProject(DoliDB $db, Project $project, ?int $socId): array
	{
		$listOrdersId = $project->get_element_list('order', 'commande');

		$orders = [];
		foreach ($listOrdersId as $orderId) {
			$order = new Commande($db);
			$order->fetch($orderId);

			if ($socId && $order->socid != $socId) {
				continue; // Skip orders not linked to the specified company
			}

			if (in_array($order->statut, [
				Commande::STATUS_VALIDATED,
				Commande::STATUS_SHIPMENTONPROCESS,
				Commande::STATUS_ACCEPTED
			])) {
				$orders[] = $order;
			}
		}

		return self::sortOrdersByDateAndRef($orders);
	}

	/**
	 * Sort orders by the `date` property, and by `ref` if the dates are identical
	 *
	 * @param array $orders List of orders to sort.
	 * @return array Sorted list of orders.
	 */
	public static function sortOrdersByDateAndRef(array $orders): array
	{
		if (empty($orders)) {
			return [];
		}

		usort($orders, function ($a, $b) {
			$dateComparison = $a->date <=> $b->date;
			if ($dateComparison === 0) {
				return $a->ref <=> $b->ref;
			}
			return $dateComparison;
		});

		return $orders;
	}
}
