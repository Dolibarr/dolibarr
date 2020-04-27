<?php
/* Copyright (C) 2007-2008 Jeremie Ollivier <jeremie.o@laposte.net>
 * Copyright (C) 2008-2011 Laurent Destailleur   <eldy@uers.sourceforge.net>
 * Copyright (C) 2011 Juanjo Menent			  	 <jmenent@2byte.es>
 * Copyright (C) 2013 Marcos García					<marcosgdf@gmail.com>
 * Copyright (C) 2013 Adolfo Segura 				<adolfo.segura@gmail.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <https://www.gnu.org/licenses/>.
 */

/**
 *	\file       htdocs/cashdesk/facturation.php
 *	\ingroup    cashdesk
 *	\brief      Include to show main page for cashdesk module
 */




/*
 * View
 */

$form = new Form($db);

// Get list of articles (in warehouse '$conf_fkentrepot' if defined and stock module enabled)
if (GETPOST('filtre', 'alpha')) {
	// Avec filtre
	$ret = array(); $i = 0;

	$sql = "SELECT p.rowid, p.ref, p.label, p.tva_tx, p.fk_product_type";
	if (!empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql .= ", ps.reel";
	$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
	if (!empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON p.rowid = ps.fk_product AND ps.fk_entrepot = '".$conf_fkentrepot."'";
	$sql .= " WHERE p.entity IN (".getEntity('product').")";
	$sql .= " AND p.tosell = 1";
	if (!$conf->global->CASHDESK_SERVICES) $sql .= " AND p.fk_product_type = 0";
	$sql .= " AND (";
	$sql .= "p.ref LIKE '%".$db->escape(GETPOST('filtre'))."%' OR p.label LIKE '%".$db->escape(GETPOST('filtre'))."%'";
	if (!empty($conf->barcode->enabled))
	{
		$filtre = GETPOST('filtre', 'alpha');

		//If the barcode looks like an EAN13 format and the last digit is included in it,
		//then whe look for the 12-digit too
		//As the twelve-digit string will also hit the 13-digit code, we only look for this one
		if (strlen($filtre) == 13) {
			$crit_12digit = substr($filtre, 0, 12);
			$sql .= " OR p.barcode LIKE '%".$db->escape($crit_12digit)."%'";
		} else {
			$sql .= " OR p.barcode LIKE '%".$db->escape($filtre)."%'";
		}
	}
	$sql .= ")";
	$sql .= " ORDER BY label";

	dol_syslog("facturation.php", LOG_DEBUG);
	$resql = $db->query($sql);
	if ($resql)
	{
		$nbr_enreg = $db->num_rows($resql);

		while ($i < $conf_taille_listes && $tab = $db->fetch_array($resql))
		{
			foreach ($tab as $cle => $valeur)
			{
				$ret[$i][$cle] = $valeur;
			}
			$i++;
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
	$tab_designations = $ret;
} else {
	// Sans filtre
	$ret = array();
	$i = 0;

	$sql = "SELECT p.rowid, ref, label, tva_tx, p.fk_product_type";
	if (!empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql .= ", ps.reel";
	$sql .= " FROM ".MAIN_DB_PREFIX."product as p";
	if (!empty($conf->stock->enabled) && !empty($conf_fkentrepot)) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_stock as ps ON p.rowid = ps.fk_product AND ps.fk_entrepot = '".$conf_fkentrepot."'";
	$sql .= " WHERE p.entity IN (".getEntity('product').")";
	$sql .= " AND p.tosell = 1";
	if (!$conf->global->CASHDESK_SERVICES) $sql .= " AND p.fk_product_type = 0";
	$sql .= " ORDER BY p.label";

	dol_syslog($sql);
	$resql = $db->query($sql);
	if ($resql)
	{
		$nbr_enreg = $db->num_rows($resql);

		while ($i < $conf_taille_listes && $tab = $db->fetch_array($resql))
		{
			foreach ($tab as $cle => $valeur)
			{
				$ret[$i][$cle] = $valeur;
			}
			$i++;
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
	}
	$tab_designations = $ret;
}

//$nbr_enreg = count($tab_designations);

if ($nbr_enreg > 1)
{
	if ($nbr_enreg > $conf_taille_listes)
	{
		$top_liste_produits = '----- '.$conf_taille_listes.' '.$langs->transnoentitiesnoconv("CashDeskProducts").' '.$langs->trans("CashDeskOn").' '.$nbr_enreg.' -----';
	}
	else
	{
		$top_liste_produits = '----- '.$nbr_enreg.' '.$langs->transnoentitiesnoconv("CashDeskProducts").' '.$langs->trans("CashDeskOn").' '.$nbr_enreg.' -----';
	}
}
elseif ($nbr_enreg == 1)
{
	$top_liste_produits = '----- 1 '.$langs->transnoentitiesnoconv("ProductFound").' -----';
}
else
{
	$top_liste_produits = '----- '.$langs->transnoentitiesnoconv("NoProductFound").' -----';
}


// Recuperation des taux de tva
global $mysoc;

$ret = array();
$i = 0;

// Reinitialisation du mode de paiement, en cas de retour aux achats apres validation
$obj_facturation->getSetPaymentMode('RESET');
$obj_facturation->montantEncaisse('RESET');
$obj_facturation->montantRendu('RESET');
$obj_facturation->paiementLe('RESET');


// Affichage des templates
require 'tpl/facturation1.tpl.php';
