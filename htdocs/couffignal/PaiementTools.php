<?php

/**
 * Classe utilitaire pour les paiements.
 */
class PaiementTools
{
	/**
	 * Retrieve the ID of the payment method for subcontractors based on the payment code 'PAYDIR'
	 *
	 * @param DoliDB $db Database handler
	 * @return int|null ID of the subcontractor payment method or null if not found
	 */
	public static function getPaimentSousTraitantId(DoliDB $db): ?int
	{
		$sql = "SELECT id type FROM " . MAIN_DB_PREFIX . "c_paiement WHERE code = 'PAYDIR'";
		$resql = $db->query($sql);
		$paiement = $db->fetch_row($resql);
		if (empty($paiement)) {
			return null;
		}

		return (int) $paiement[0];
	}
}
