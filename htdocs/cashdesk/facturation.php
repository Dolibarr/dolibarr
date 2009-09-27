<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008 Laurent Destailleur   <eldy@uers.sourceforge.net>
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

// Recuperation de la liste des articles
if ( $_GET['filtre'] ) {

	// Avec filtre
	$ret=array(); $i=0;
	$resql=$sql->query (
			'SELECT '.MAIN_DB_PREFIX.'product.rowid, ref, label, tva_tx
			FROM '.MAIN_DB_PREFIX.'product
			LEFT JOIN '.MAIN_DB_PREFIX.'product_stock ON '.MAIN_DB_PREFIX.'product.rowid = '.MAIN_DB_PREFIX.'product_stock.fk_product
			WHERE envente = 1
				AND fk_product_type = 0
				AND fk_entrepot = '.$conf_fkentrepot.'
				AND ref LIKE \'%'.$_GET['filtre'].'%\'
				OR label LIKE \'%'.$_GET['filtre'].'%\'
			ORDER BY label');
	while ( $tab = $sql->fetch_array($resql) )
	{
		foreach ( $tab as $cle => $valeur )
		{
			$ret[$i][$cle] = $valeur;
		}
		$i++;
	}
	$tab_designations=$ret;
} else {

	// Sans filtre
	$ret=array(); $i=0;
	$resql=$sql->query ('SELECT '.MAIN_DB_PREFIX.'product.rowid, ref, label, tva_tx
			FROM '.MAIN_DB_PREFIX.'product
			LEFT JOIN '.MAIN_DB_PREFIX.'product_stock ON '.MAIN_DB_PREFIX.'product.rowid = '.MAIN_DB_PREFIX.'product_stock.fk_product
			WHERE envente = 1
				AND fk_product_type = 0
				AND fk_entrepot = '.$conf_fkentrepot.'
			ORDER BY label');
	while ( $tab = $sql->fetch_array($resql) )
	{
		foreach ( $tab as $cle => $valeur )
		{
			$ret[$i][$cle] = $valeur;
		}
		$i++;
	}
	$tab_designations=$ret;
}

$nbr_enreg = count ($tab_designations);

if ( $nbr_enreg > 1 ) {

	if ( $nbr_enreg > $conf_taille_listes ) {

		$top_liste_produits = '----- '.$conf_taille_listes.' '.$langs->transnoentitiesnoconv("Products").' '.$langs->trans("on").' '.$nbr_enreg.' -----';

	} else {

		$top_liste_produits = '----- '.$nbr_enreg.' '.$langs->transnoentitiesnoconv("Products").' '.$langs->trans("on").' '.$nbr_enreg.' -----';

	}

} else if ( $nbr_enreg == 1 ) {

	$top_liste_produits = '----- 1 '.$langs->transnoentitiesnoconv("ProductFound"). ' -----';

} else {

	$top_liste_produits = '----- '.$langs->transnoentitiesnoconv("NoProductFound"). ' -----';

}


// Recuperation des taux de tva
global $mysoc;
$request="SELECT t.rowid, t.taux
		FROM ".MAIN_DB_PREFIX."c_tva as t, llx_c_pays as p
		WHERE t.fk_pays = p.rowid AND t.active = 1 AND p.code = '".$mysoc->pays_code."'";
//print $request;

$ret=array(); $i=0;
$res=$sql->query ($request);
while ( $tab = $sql->fetch_array($res) )
{
	foreach ( $tab as $cle => $valeur )
	{
		$ret[$i][$cle] = $valeur;
	}
	$i++;
}
$tab_tva = $ret;


// Reinitialisation du mode de paiement, en cas de retour aux achats apres validation
$obj_facturation->mode_reglement ('RESET');
$obj_facturation->montant_encaisse ('RESET');
$obj_facturation->montant_rendu ('RESET');
$obj_facturation->paiement_le ('RESET');


// Affichage des templates
require ('templates/facturation1.tpl.php');

?>
