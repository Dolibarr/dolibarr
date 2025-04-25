<?php

declare(strict_types=1);

require_once DOL_DOCUMENT_ROOT . '/commande/class/commande.class.php';

/**
 * Tools for Commande class
 */
class CommandeTools
{
	/**
	 * Get all validated orders from a project
	 *
	 * @param DoliDB $db Database handler
	 * @param Project $project Project object
	 * @return array
	 */
	public static function getOrdersValidatedFromProject(DoliDB $db, Project $project): array
	{
		$listOrdersRowId = $project->get_element_list('order', 'commande');

		$orders = [];
		foreach ($listOrdersRowId as $orderRowId) {
			$order = new Commande($db);
			$order->fetch($orderRowId);
			if ($order->statut == Commande::STATUS_VALIDATED) {
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
