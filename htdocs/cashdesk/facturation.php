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

// Get list of articles (in warehouse '$conf_fkentrepot' if stock module enabled)
if ( $_GET['filtre'] ) {

	// Avec filtre
	$ret=array(); $i=0;

	$sql = "SELECT p.rowid, p.ref, p.label, p.tva_tx";
	$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
	if ($conf->stock->enabled && !empty($conf_fkentrepot)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON p.rowid = ps.fk_product";
	$sql.= " WHERE p.envente = 1";
	$sql.= " AND p.fk_product_type = 0";
	if ($conf->stock->enabled && !empty($conf_fkentrepot)) $sql.=" AND ps.fk_entrepot = '".$conf_fkentrepot."'";
	$sql.= " AND (p.ref LIKE '%".$_GET['filtre']."%' OR p.label LIKE '%".$_GET['filtre']."%')";
	$sql.= " ORDER BY label";
	
	dol_syslog($sql);
	$resql=$db->query ($sql);
	if ($resql)
	{
		while ( $tab = $db->fetch_array($resql) )
		{
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$i][$cle] = $valeur;
			}
			$i++;
		}
	}
	else
	{
		dol_print_error($db);
	}
	$tab_designations=$ret;
} else {

	// Sans filtre
	$ret=array();
	$i=0;
	
	$sql = "SELECT p.rowid, ref, label, tva_tx";
	$sql.= " FROM ".MAIN_DB_PREFIX."product as p";
	if ($conf->stock->enabled && !empty($conf_fkentrepot)) $sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON p.rowid = ps.fk_product";
	$sql.= " WHERE p.envente = 1";
	$sql.= " AND p.fk_product_type = 0";
	if ($conf->stock->enabled && !empty($conf_fkentrepot)) $sql.= " AND ps.fk_entrepot = ".$conf_fkentrepot;
	$sql.= " ORDER BY p.label";
	
	dol_syslog($sql);
	$resql=$db->query ($sql);
	if ($resql)
	{
		while ( $tab = $db->fetch_array($resql) )
		{
			foreach ( $tab as $cle => $valeur )
			{
				$ret[$i][$cle] = $valeur;
			}
			$i++;
		}
	}
	else
	{
		dol_print_error($db);
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

$ret=array();
$i=0;

$sql = "SELECT t.rowid, t.taux";
$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as t";
$sql.= ", ".MAIN_DB_PREFIX."c_pays as p";
$sql.= " WHERE t.fk_pays = p.rowid";
$sql.= " AND t.active = 1";
$sql.= " AND p.code = '".$mysoc->pays_code."'";
//print $request;

$res=$db->query ($sql);
if ($res)
{
	while ( $tab = $db->fetch_array($res) )
	{
		foreach ( $tab as $cle => $valeur )
		{
			$ret[$i][$cle] = $valeur;
		}
		$i++;
	}
}
else
{
	dol_print_error($db);
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
