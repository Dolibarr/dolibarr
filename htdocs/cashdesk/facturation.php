<?php
/* Copyright (C) 2007-2008 Jérémie Ollivier <jeremie.o@laposte.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

	// Récupération de la liste des articles
	if ( $_GET['filtre'] ) {

		// Avec filtre
		$tab_designations = $sql->fetchAll ( $sql->query ('
			SELECT llx_product.rowid, ref, label, tva_tx
			FROM llx_product
			LEFT JOIN llx_product_stock ON llx_product.rowid = llx_product_stock.fk_product
			WHERE envente = 1
				AND fk_product_type = 0
				AND fk_entrepot = '.$conf_fkentrepot.'
				AND ref LIKE \'%'.$_GET['filtre'].'%\'
				OR label LIKE \'%'.$_GET['filtre'].'%\'
			ORDER BY label
		;'));

	} else {

		// Sans filtre
		$tab_designations = $sql->fetchAll ( $sql->query ('
			SELECT llx_product.rowid, ref, label, tva_tx
			FROM llx_product
			LEFT JOIN llx_product_stock ON llx_product.rowid = llx_product_stock.fk_product
			WHERE envente = 1
				AND fk_product_type = 0
				AND fk_entrepot = '.$conf_fkentrepot.'
			ORDER BY label
		;'));

	}

	$nbr_enreg = count ($tab_designations);

	if ( $nbr_enreg > 1 ) {

		if ( $nbr_enreg > $conf_taille_listes ) {

			$top_liste_produits = '----- '.$conf_taille_listes.' produits affichés sur un total de '.$nbr_enreg.' -----';

		} else {

			$top_liste_produits = '----- '.$nbr_enreg.' produits affichés sur un total de '.$nbr_enreg.' -----';

		}

	} else if ( $nbr_enreg == 1 ) {

		$top_liste_produits = '----- 1 article trouvé -----';

	} else {

		$top_liste_produits = '----- Aucun article trouvé -----';

	}


	// Récupération des taux de tva
	global $mysoc;
	$request="SELECT t.rowid, t.taux
		FROM llx_c_tva as t, llx_c_pays as p
		WHERE t.fk_pays = p.rowid AND t.active = 1 AND p.code = '".$mysoc->pays_code."'"; 
	//print $request;
	$tab_tva = $sql->fetchAll ($sql->query ($request));


	// Réinitialisation du mode de paiement, en cas de retour aux achats après validation
	$obj_facturation->mode_reglement ('RESET');
	$obj_facturation->montant_encaisse ('RESET');
	$obj_facturation->montant_rendu ('RESET');
	$obj_facturation->paiement_le ('RESET');


	// Affichage des templates
	require ('templates/facturation1.tpl.php');

?>
