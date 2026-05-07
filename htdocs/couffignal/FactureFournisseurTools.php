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
	 * @param string $cycle 	'unique'|'cumulated'|'full_cycle', resp. for the given invoice, all invoices in cycle up to given invoice, all the invoices in the situation cycle
	 * 	 * 
	 * @return FactureFournisseur[] Array of supplier invoice objects 
	 */
	public static function getFacturesFournValidatedFromDebtCompensationLinks(FactureFournisseur|Facture $invoice, DoliDB $db, string $cycle = 'unique'): array
	{

		// Check compliance to module requirements
		if (!isModEnabled('clientpayfourn')) {
			dol_syslog("Module clientpayfourn must be enabled for Co-Sous-traitant table to work", LOG_WARN);
			setEventMessages("", "Module clientpayfourn must be enabled for Co-Sous-traitant table to work", 'mesgs');
		}

		// Load linked object
		$obj = new LinkClientPayFourn($db);
		$factureFournLinked = $obj->getLinkedObjects($invoice, $db);

		// Safety filter on Validated and Closed supplier invoices only
		$facturesFourn = array();
		foreach ($factureFournLinked as $factureFourn) {
			$statusValid = [FactureFournisseur::STATUS_VALIDATED, FactureFournisseur::STATUS_CLOSED];
			if (in_array($factureFourn->status, $statusValid)) {
				$facturesFourn[$factureFourn->ref] = $factureFourn;
			}
		}

		// Recursive calls managing the calls over invoicing cycle
		switch ($cycle) {
		 	case 'unique':
		 		break;
		 	case 'cumulated':
		 		$invoice->fetchPreviousNextSituationInvoice();
		 		foreach ($invoice->tab_previous_situation_invoice as $inv) {
		 			$facturesFourn = array_merge($facturesFourn, self::getFacturesFournValidatedFromDebtCompensationLinks($inv, $db, 'unique'));
		 		}
		 		break;
		 	case 'full_cycle':
		 		$invoice->fetchPreviousNextSituationInvoice();
		 		foreach (array_merge($invoice->tab_previous_situation_invoice, $invoice->tab_next_situation_invoice) as $inv) {
		 			$facturesFourn = array_merge($facturesFourn, self::getFacturesFournValidatedFromDebtCompensationLinks($inv, $db, 'unique'));
		 		}
		 		break;
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
		foreach ($invoices as $ref => $invoice) {
			$invoice->fetchObjectLinked();

			if (empty($invoice->linkedObjects['order_supplier'])) { continue; }
			foreach ($invoice->linkedObjects['order_supplier'] as $id => $order) {
				if (in_array($order->statut, [
					CommandeFournisseur::STATUS_VALIDATED, 
					CommandeFournisseur::STATUS_ACCEPTED, 
					CommandeFournisseur::STATUS_ORDERSENT, 
					CommandeFournisseur::STATUS_RECEIVED_PARTIALLY, 
					CommandeFournisseur::STATUS_RECEIVED_COMPLETELY
				])) {
					$orders[$order->ref] = $order;
				}
			}
		}
		
		$orders = CommandeTools::sortOrdersByDateAndRef($orders);

		return $orders; 
	}
}
