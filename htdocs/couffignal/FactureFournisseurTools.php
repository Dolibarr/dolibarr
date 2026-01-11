<?php

if (isModEnabled('clientpayfourn')) dol_include_once('/clientpayfourn/class/linkclientpayfourn.class.php');

/**
 * Classe utilitaire pour les factures fournisseurs.
 */
class FactureFournisseurTools
{
	/**
	 * Get array of supplier invoices linked to the invoice through direct paiment module
	 * Based on external module ClientPayFourn
	 *
	 * @param Facture $invoice Invoice object
	 * @param DoliDB $db Gestionnaire de base de données.
	 * @param boolean $cycle If True, iterate over all the invoices in the situation cycle
	 * 	 * 
	 * @return FactureFournisseur[] Array of supplier invoice objects 
	 */
	public static function getFacturesFournValidatedFromDebtCompensationLinks(FactureFournisseur|Facture $invoice, DoliDB $db, bool $cycle = False): array
	{

		if ($cycle) {
			// code...
			// Recursive call
		}

		// Check compliance to module requirements
		if (!isModEnabled('clientpayfourn')) {
			dol_syslog("Module clientpayfourn must be enabled for Co-Sous-traitant table to work", LOG_WARN);
			setEventMessages("", "Module clientpayfourn must be enabled for Co-Sous-traitant table to work", 'mesgs');
		}

		// Load linked object
		$obj = new LinkClientPayFourn($db);
		$factureFournLinked = $obj->getLinkedObjects($invoice, $db);

		// Safety filter on Validated and Closef supplier invoices only
		$facturesFourn = [];
		foreach ($factureFournLinked as $factureFourn) {
			$statusValid = [FactureFournisseur::STATUS_VALIDATED, FactureFournisseur::STATUS_CLOSED];
			if (in_array($factureFourn->status, $statusValid)) {
				$facturesFourn[] = $factureFourn;
			}
		}
		return $facturesFourn;
	}

	/**
	 * Récupère les commandes fournisseurs validées à partir des factures fournisseurs liées à la facture client.
	 *
	 * @param Project $project Projet lié à la facture.
	 * @return CommandeFournisseur[] Tableau d'objets de commandes fournisseurs.
	 */
	public static function getOrdersValidatedFromFacturesFourn(array $invoices): array
	{

		$orders = [];
		foreach ($invoices as $invoice) {
			$invoice->fetchObjectLinked(null, 'order_supplier', null, '');

			if (empty($invoice->linkedObjects['order_supplier'])) { continue; }
			foreach ($invoice->linkedObjects['order_supplier'] as $id => $order) {
				if (in_array($order->statut, [
					CommandeFournisseur::STATUS_VALIDATED, 
					CommandeFournisseur::STATUS_ACCEPTED, 
					CommandeFournisseur::STATUS_ORDERSENT, 
					CommandeFournisseur::STATUS_RECEIVED_PARTIALLY, 
					CommandeFournisseur::STATUS_RECEIVED_COMPLETELY
				])) {
					$orders[] = $order;
				}
			}
		}
		
		$orders = CommandeTools::sortOrdersByDateAndRef($orders);

		return $orders; 
	}
}
