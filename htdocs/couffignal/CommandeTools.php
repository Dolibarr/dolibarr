<?php

declare(strict_types=1);

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

		return $orders;
	}
}
