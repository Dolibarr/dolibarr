<?php

/**
 * Classe utilitaire pour les factures fournisseurs.
 */
class FactureFournisseurTools
{
	/**
	 * Get list of supplier invoices linked to the project of the invoice
	 *
	 * @param Project $project Invoice object
	 * @param DoliDB $db Database handler
	 * @return FactureFournisseur[] Array of supplier invoice objects
	 */
	public static function getFacturesFournValidatedFromProject(Project $project, DoliDB $db): array
	{
		/** @var string[] $factureFournRowIdList */
		$factureFournRowIdList = $project->get_element_list('invoice_supplier', 'facture_fourn');
		$facturesFourn = [];
		foreach ($factureFournRowIdList as $facutreFournRowId) {
			$factureFourn = new FactureFournisseur($db);
			$factureFourn->fetch($facutreFournRowId);
			if ($factureFourn->status == FactureFournisseur::STATUS_VALIDATED) {
				$facturesFourn[] = $factureFourn;
			}
		}
		return $facturesFourn;
	}
}
