<?php

require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';

/**
 * Classe utilitaire pour les commandes fournisseurs.
 */
class CommandFournisseurTools
{
	/**
	 * Récupère les commandes fournisseurs validées à partir d'un projet.
	 *
	 * @param Project $project Projet lié à la facture.
	 * @param DoliDB $db Gestionnaire de base de données.
	 * @return CommandeFournisseur[] Tableau d'objets de commandes fournisseurs.
	 */
	public static function getOrdersValidatedFromProject(Project $project, DoliDB $db): array
	{
		$ordersFournIdList = $project->get_element_list('order_supplier', 'commande_fournisseur');
		$ordersFourn = [];
		foreach ($ordersFournIdList as $orderFournId) {
			$orderFourn = new CommandeFournisseur($db);
			$orderFourn->fetch($orderFournId);
			$statusValid = [CommandeFournisseur::STATUS_VALIDATED, CommandeFournisseur::STATUS_ACCEPTED, CommandeFournisseur::STATUS_ORDERSENT, CommandeFournisseur::STATUS_RECEIVED_PARTIALLY, CommandeFournisseur::STATUS_RECEIVED_COMPLETELY];
			if (in_array($orderFourn->statut, $statusValid)) {
				$ordersFourn[] = $orderFourn;
			}
		}
		return $ordersFourn;
	}
}
