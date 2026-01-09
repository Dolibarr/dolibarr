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
	 * 
	 * @return FactureFournisseur[] Array of supplier invoice objects 
	 */
	public static function getFacturesFournValidatedFromDebtCompensationLinks(FactureFournisseur|Facture $invoice, DoliDB $db): array
	{
		// Check compliance to module requirements
		if (!isModEnabled('clientpayfourn')) {
			dol_syslog("Module clientpayfourn must be enabled for Co-Sous-traitant table to work", LOG_WARN);
			setEventMessages("", "Module clientpayfourn must be enabled for Co-Sous-traitant table to work", 'mesgs');
		}

		// Load linked object
		$obj = new LinkClientPayFourn($db);
		$factureFournLinked = $obj->getLinkedObjects($invoice, $db);
		var_dump($factureFournLinked);

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
}
