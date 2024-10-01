<?php
/* Copyright (C) 2004-2009  Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2007  Yannick Warnier     <ywarnier@beeznest.org>
 * Copyright (C) 2011	    Regis Houssin       <regis.houssin@inodbox.com>
 * Copyright (C) 2012-2017  Juanjo Menent       <jmenent@2byte.es>
 * Copyright (C) 2012       Cédric Salvador     <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos García       <marcosgdf@gmail.com>
 * Copyright (C) 2021-2022  Open-Dsi            <support@open-dsi.fr>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *      \file       htdocs/core/lib/tax.lib.php
 *      \ingroup    tax
 *      \brief      Library for tax module
 */


/**
 * Prepare array with list of tabs
 *
 * @param   ChargeSociales	$object		Object related to tabs
 * @return	array<array{0:string,1:string,2:string}>	Array of tabs to show
 */
function tax_prepare_head(ChargeSociales $object)
{
	global $db, $langs, $conf, $user;

	$h = 0;
	$head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans('SocialContribution');
	$head[$h][2] = 'card';
	$h++;

	// Show more tabs from modules
	// Entries must be declared in modules descriptor with line
	// $this->tabs = array('entity:+tabname:Title:@mymodule:/mymodule/mypage.php?id=__ID__');   to add new tab
	// $this->tabs = array('entity:-tabname);   												to remove a tab
	complete_head_from_modules($conf, $langs, $object, $head, $h, 'tax');

	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
	require_once DOL_DOCUMENT_ROOT.'/core/class/link.class.php';
	$upload_dir = $conf->tax->dir_output."/".dol_sanitizeFileName($object->ref);
	$nbFiles = count(dol_dir_list($upload_dir, 'files', 0, '', '(\.meta|_preview.*\.png)$'));
	$nbLinks = Link::count($db, $object->element, $object->id);
	$head[$h][0] = DOL_URL_ROOT.'/compta/sociales/document.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Documents");
	if (($nbFiles + $nbLinks) > 0) {
		$head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	}
	$head[$h][2] = 'documents';
	$h++;


	$nbNote = 0;
	if (!empty($object->note_private)) {
		$nbNote++;
	}
	if (!empty($object->note_public)) {
		$nbNote++;
	}
	$head[$h][0] = DOL_URL_ROOT.'/compta/sociales/note.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Notes');
	if ($nbNote > 0) {
		$head[$h][1] .= (!getDolGlobalString('MAIN_OPTIMIZEFORTEXTBROWSER') ? '<span class="badge marginleftonlyshort">'.$nbNote.'</span>' : '');
	}
	$head[$h][2] = 'note';
	$h++;


	$head[$h][0] = DOL_URL_ROOT.'/compta/sociales/info.php?id='.$object->id;
	$head[$h][1] = $langs->trans("Info");
	$head[$h][2] = 'info';
	$h++;


	complete_head_from_modules($conf, $langs, $object, $head, $h, 'tax', 'remove');

	return $head;
}


/**
 *  Look for collectable VAT clients in the chosen year (and month)
 *
 *  @param	string		 $type          	Tax type, either 'vat', 'localtax1' or 'localtax2'
 *  @param	DoliDB		 $db          	Database handle
 *  @param  int			 $y           	Year
 *  @param  int|''		 $date_start  	Start date
 *  @param  int|''		 $date_end    	End date
 *  @param  int			 $modetax     	Not used
 *  @param  'sell'|'buy' $direction     'sell' or 'buy'
 *  @param  int			 $m				Month
 *  @param  int			 $q           	Quarter
 *  @return array|int               	Array with details of VATs (per third parties), -1 if no accountancy module, -2 if not yet developed, -3 if error
 */
function tax_by_thirdparty($type, $db, $y, $date_start, $date_end, $modetax, $direction, $m = 0, $q = 0)
{
	global $conf;

	// If we use date_start and date_end, we must not use $y, $m, $q
	if (($date_start || $date_end) && (!empty($y) || !empty($m) || !empty($q))) {
		dol_print_error(null, 'Bad value of input parameter for tax_by_thirdparty');
	}

	$list = array();
	if ($direction == 'sell') {
		$invoicetable = 'facture';
		$invoicedettable = 'facturedet';
		$fk_facture = 'fk_facture';
		$fk_facture2 = 'fk_facture';
		$fk_payment = 'fk_paiement';
		$total_tva = 'total_tva';
		$paymenttable = 'paiement';
		$paymentfacturetable = 'paiement_facture';
		$invoicefieldref = 'ref';
	} elseif ($direction == 'buy') {
		$invoicetable = 'facture_fourn';
		$invoicedettable = 'facture_fourn_det';
		$fk_facture = 'fk_facture_fourn';
		$fk_facture2 = 'fk_facturefourn';
		$fk_payment = 'fk_paiementfourn';
		$total_tva = 'tva';
		$paymenttable = 'paiementfourn';
		$paymentfacturetable = 'paiementfourn_facturefourn';
		$invoicefieldref = 'ref';
	}

	if (strpos($type, 'localtax') === 0) {
		$f_rate = $type.'_tx';
	} else {
		$f_rate = 'tva_tx';
	}

	$total_localtax1 = 'total_localtax1';
	$total_localtax2 = 'total_localtax2';


	// CAS DES BIENS/PRODUITS

	// Define sql request
	$sql = '';
	if (($direction == 'sell' && getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice')
		|| ($direction == 'buy' && getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'invoice')) {
		// Count on delivery date (use invoice date as delivery is unknown)
		$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
		$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
		$sql .= " d.date_start as date_start, d.date_end as date_end,";
		$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef,";
		$sql .= " s.nom as company_name, s.name_alias as company_alias, s.rowid as company_id, s.client as company_client, s.fournisseur as company_fournisseur, s.email as company_email,";
		$sql .= " s.code_client as company_customer_code, s.code_fournisseur as company_supplier_code,";
		$sql .= " s.code_compta as company_customer_accounting_code, s.code_compta_fournisseur as company_supplier_accounting_code,";
		$sql .= " s.status as company_status, s.tva_intra as company_tva_intra,";
		$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype, p.tosell as pstatus, p.tobuy as pstatusbuy,";
		$sql .= " 0 as payment_id, '' as payment_ref, 0 as payment_amount";
		$sql .= " ,'' as datep";
		$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
		$sql .= " ".MAIN_DB_PREFIX."societe as s,";
		$sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
		$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
		$sql .= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
		if ($direction == 'buy') {
			if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		} else {
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		}
		$sql .= " AND f.rowid = d.".$fk_facture;
		$sql .= " AND s.rowid = f.fk_soc";
		if ($y && $m) {
			$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND f.datef > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = 0"; // Limit to products
		$sql .= " AND d.date_start is null AND d.date_end IS NULL)"; // enhance detection of products
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
		}
		$sql .= " ORDER BY d.rowid, d.".$fk_facture;
	} else {
		// Count on payments date
		$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
		$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
		$sql .= " d.date_start as date_start, d.date_end as date_end,";
		$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef,";
		$sql .= " s.nom as company_name, s.name_alias as company_alias, s.rowid as company_id, s.client as company_client, s.fournisseur as company_fournisseur, s.email as company_email,";
		$sql .= " s.code_client as company_customer_code, s.code_fournisseur as company_supplier_code,";
		$sql .= " s.code_compta as company_customer_accounting_code, s.code_compta_fournisseur as company_supplier_accounting_code,";
		$sql .= " s.status as company_status, s.tva_intra as company_tva_intra,";
		$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype, p.tosell as pstatus, p.tobuy as pstatusbuy,";
		$sql .= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount,";
		$sql .= " pa.datep as datep, pa.ref as payment_ref";
		$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
		$sql .= " ".MAIN_DB_PREFIX.$paymentfacturetable." as pf,";
		$sql .= " ".MAIN_DB_PREFIX.$paymenttable." as pa,";
		$sql .= " ".MAIN_DB_PREFIX."societe as s,";
		$sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
		$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
		$sql .= " AND f.fk_statut in (1,2)"; // Paid (partially or completely)
		if ($direction == 'buy') {
			if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		} else {
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		}
		$sql .= " AND f.rowid = d.".$fk_facture;
		$sql .= " AND s.rowid = f.fk_soc";
		$sql .= " AND pf.".$fk_facture2." = f.rowid";
		$sql .= " AND pa.rowid = pf.".$fk_payment;
		if ($y && $m) {
			$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND pa.datep > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = 0"; // Limit to products
		$sql .= " AND d.date_start is null AND d.date_end IS NULL)"; // enhance detection of products
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
		}
		$sql .= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
	}

	if (!$sql) {
		return -1;
	}
	if ($sql == 'TODO') {
		return -2;
	}
	if ($sql != 'TODO') {
		dol_syslog("Tax.lib.php::tax_by_thirdparty", LOG_DEBUG);

		$resql = $db->query($sql);
		if ($resql) {
			$company_id = -1;
			$oldrowid = '';
			while ($assoc = $db->fetch_array($resql)) {
				if (!isset($list[$assoc['company_id']]['totalht'])) {
					$list[$assoc['company_id']]['totalht'] = 0;
				}
				if (!isset($list[$assoc['company_id']]['vat'])) {
					$list[$assoc['company_id']]['vat'] = 0;
				}
				if (!isset($list[$assoc['company_id']]['localtax1'])) {
					$list[$assoc['company_id']]['localtax1'] = 0;
				}
				if (!isset($list[$assoc['company_id']]['localtax2'])) {
					$list[$assoc['company_id']]['localtax2'] = 0;
				}

				if ($assoc['rowid'] != $oldrowid) {       // Si rupture sur d.rowid
					$oldrowid = $assoc['rowid'];
					$list[$assoc['company_id']]['totalht']  += $assoc['total_ht'];
					$list[$assoc['company_id']]['vat']      += $assoc['total_vat'];
					$list[$assoc['company_id']]['localtax1']      += $assoc['total_localtax1'];
					$list[$assoc['company_id']]['localtax2']      += $assoc['total_localtax2'];
				}

				$list[$assoc['company_id']]['dtotal_ttc'][] = $assoc['total_ttc'];
				$list[$assoc['company_id']]['dtype'][] = $assoc['dtype'];
				$list[$assoc['company_id']]['datef'][] = $db->jdate($assoc['datef']);
				$list[$assoc['company_id']]['datep'][] = $db->jdate($assoc['datep']);

				$list[$assoc['company_id']]['company_name'][] = $assoc['company_name'];
				$list[$assoc['company_id']]['company_id'][] = $assoc['company_id'];
				$list[$assoc['company_id']]['company_alias'][] = $assoc['company_alias'];
				$list[$assoc['company_id']]['company_email'][] = $assoc['company_email'];
				$list[$assoc['company_id']]['company_tva_intra'][] = $assoc['company_tva_intra'];
				$list[$assoc['company_id']]['company_client'][] = $assoc['company_client'];
				$list[$assoc['company_id']]['company_fournisseur'][] = $assoc['company_fournisseur'];
				$list[$assoc['company_id']]['company_customer_code'][] = $assoc['company_customer_code'];
				$list[$assoc['company_id']]['company_supplier_code'][] = $assoc['company_supplier_code'];
				$list[$assoc['company_id']]['company_customer_accounting_code'][] = $assoc['company_customer_accounting_code'];
				$list[$assoc['company_id']]['company_supplier_accounting_code'][] = $assoc['company_supplier_accounting_code'];
				$list[$assoc['company_id']]['company_status'][] = $assoc['company_status'];

				$list[$assoc['company_id']]['drate'][] = $assoc['rate'];
				$list[$assoc['company_id']]['ddate_start'][] = $db->jdate($assoc['date_start']);
				$list[$assoc['company_id']]['ddate_end'][] = $db->jdate($assoc['date_end']);

				$list[$assoc['company_id']]['facid'][] = $assoc['facid'];
				$list[$assoc['company_id']]['facnum'][] = $assoc['facnum'];
				$list[$assoc['company_id']]['type'][] = $assoc['type'];
				$list[$assoc['company_id']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
				$list[$assoc['company_id']]['descr'][] = $assoc['descr'];

				$list[$assoc['company_id']]['totalht_list'][] = $assoc['total_ht'];
				$list[$assoc['company_id']]['vat_list'][] = $assoc['total_vat'];
				$list[$assoc['company_id']]['localtax1_list'][] = $assoc['total_localtax1'];
				$list[$assoc['company_id']]['localtax2_list'][] = $assoc['total_localtax2'];

				$list[$assoc['company_id']]['pid'][] = $assoc['pid'];
				$list[$assoc['company_id']]['pref'][] = $assoc['pref'];
				$list[$assoc['company_id']]['ptype'][] = $assoc['ptype'];

				$list[$assoc['company_id']]['payment_id'][] = $assoc['payment_id'];
				$list[$assoc['company_id']]['payment_amount'][] = $assoc['payment_amount'];

				$company_id = $assoc['company_id'];
			}
		} else {
			dol_print_error($db);
			return -3;
		}
	}


	// CAS DES SERVICES

	// Define sql request
	$sql = '';
	if (($direction == 'sell' && getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice')
		|| ($direction == 'buy' && getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'invoice')) {
		// Count on invoice date
		$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
		$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
		$sql .= " d.date_start as date_start, d.date_end as date_end,";
		$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef,";
		$sql .= " s.nom as company_name, s.name_alias as company_alias, s.rowid as company_id, s.client as company_client, s.fournisseur as company_fournisseur, s.email as company_email,";
		$sql .= " s.code_client as company_customer_code, s.code_fournisseur as company_supplier_code,";
		$sql .= " s.code_compta as company_customer_accounting_code, s.code_compta_fournisseur as company_supplier_accounting_code,";
		$sql .= " s.status as company_status, s.tva_intra as company_tva_intra,";
		$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype, p.tosell as pstatus, p.tobuy as pstatusbuy,";
		$sql .= " 0 as payment_id, '' as payment_ref, 0 as payment_amount";
		$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
		$sql .= " ".MAIN_DB_PREFIX."societe as s,";
		$sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
		$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
		$sql .= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
		if ($direction == 'buy') {
			if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		} else {
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		}
		$sql .= " AND f.rowid = d.".$fk_facture;
		$sql .= " AND s.rowid = f.fk_soc";
		if ($y && $m) {
			$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND f.datef > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = 1"; // Limit to services
		$sql .= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)"; // enhance detection of service
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
		}
		$sql .= " ORDER BY d.rowid, d.".$fk_facture;
	} else {
		// Count on payments date
		$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
		$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
		$sql .= " d.date_start as date_start, d.date_end as date_end,";
		$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef,";
		$sql .= " s.nom as company_name, s.name_alias as company_alias, s.rowid as company_id, s.client as company_client, s.fournisseur as company_fournisseur, s.email as company_email,";
		$sql .= " s.code_client as company_customer_code, s.code_fournisseur as company_supplier_code,";
		$sql .= " s.code_compta as company_customer_accounting_code, s.code_compta_fournisseur as company_supplier_accounting_code,";
		$sql .= " s.status as company_status, s.tva_intra as company_tva_intra,";
		$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype, p.tosell as pstatus, p.tobuy as pstatusbuy,";
		$sql .= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount,";
		$sql .= " pa.datep as datep, pa.ref as payment_ref";
		$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
		$sql .= " ".MAIN_DB_PREFIX.$paymentfacturetable." as pf,";
		$sql .= " ".MAIN_DB_PREFIX.$paymenttable." as pa,";
		$sql .= " ".MAIN_DB_PREFIX."societe as s,";
		$sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
		$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
		$sql .= " AND f.fk_statut in (1,2)"; // Paid (partially or completely)
		if ($direction == 'buy') {
			if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		} else {
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		}
		$sql .= " AND f.rowid = d.".$fk_facture;
		$sql .= " AND s.rowid = f.fk_soc";
		$sql .= " AND pf.".$fk_facture2." = f.rowid";
		$sql .= " AND pa.rowid = pf.".$fk_payment;
		if ($y && $m) {
			$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND pa.datep > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = 1"; // Limit to services
		$sql .= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)"; // enhance detection of service
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
		}
		$sql .= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
	}

	if (!$sql) {
		dol_syslog("Tax.lib.php::tax_by_thirdparty no accountancy module enabled".$sql, LOG_ERR);
		return -1; // -1 = Not accountancy module enabled
	}
	if ($sql == 'TODO') {
		return -2; // -2 = Feature not yet available
	}
	if ($sql != 'TODO') {
		dol_syslog("Tax.lib.php::tax_by_thirdparty", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$company_id = -1;
			$oldrowid = '';
			while ($assoc = $db->fetch_array($resql)) {
				if (!isset($list[$assoc['company_id']]['totalht'])) {
					$list[$assoc['company_id']]['totalht'] = 0;
				}
				if (!isset($list[$assoc['company_id']]['vat'])) {
					$list[$assoc['company_id']]['vat'] = 0;
				}
				if (!isset($list[$assoc['company_id']]['localtax1'])) {
					$list[$assoc['company_id']]['localtax1'] = 0;
				}
				if (!isset($list[$assoc['company_id']]['localtax2'])) {
					$list[$assoc['company_id']]['localtax2'] = 0;
				}

				if ($assoc['rowid'] != $oldrowid) {       // Si rupture sur d.rowid
					$oldrowid = $assoc['rowid'];
					$list[$assoc['company_id']]['totalht']  += $assoc['total_ht'];
					$list[$assoc['company_id']]['vat']      += $assoc['total_vat'];
					$list[$assoc['company_id']]['localtax1']	 += $assoc['total_localtax1'];
					$list[$assoc['company_id']]['localtax2']	 += $assoc['total_localtax2'];
				}
				$list[$assoc['company_id']]['dtotal_ttc'][] = $assoc['total_ttc'];
				$list[$assoc['company_id']]['dtype'][] = $assoc['dtype'];
				$list[$assoc['company_id']]['datef'][] = $db->jdate($assoc['datef']);
				$list[$assoc['company_id']]['datep'][] = $db->jdate($assoc['datep']);

				$list[$assoc['company_id']]['company_name'][] = $assoc['company_name'];
				$list[$assoc['company_id']]['company_id'][] = $assoc['company_id'];
				$list[$assoc['company_id']]['company_alias'][] = $assoc['company_alias'];
				$list[$assoc['company_id']]['company_email'][] = $assoc['company_email'];
				$list[$assoc['company_id']]['company_tva_intra'][] = $assoc['company_tva_intra'];
				$list[$assoc['company_id']]['company_client'][] = $assoc['company_client'];
				$list[$assoc['company_id']]['company_fournisseur'][] = $assoc['company_fournisseur'];
				$list[$assoc['company_id']]['company_customer_code'][] = $assoc['company_customer_code'];
				$list[$assoc['company_id']]['company_supplier_code'][] = $assoc['company_supplier_code'];
				$list[$assoc['company_id']]['company_customer_accounting_code'][] = $assoc['company_customer_accounting_code'];
				$list[$assoc['company_id']]['company_supplier_accounting_code'][] = $assoc['company_supplier_accounting_code'];
				$list[$assoc['company_id']]['company_status'][] = $assoc['company_status'];

				$list[$assoc['company_id']]['drate'][] = $assoc['rate'];
				$list[$assoc['company_id']]['ddate_start'][] = $db->jdate($assoc['date_start']);
				$list[$assoc['company_id']]['ddate_end'][] = $db->jdate($assoc['date_end']);

				$list[$assoc['company_id']]['facid'][] = $assoc['facid'];
				$list[$assoc['company_id']]['facnum'][] = $assoc['facnum'];
				$list[$assoc['company_id']]['type'][] = $assoc['type'];
				$list[$assoc['company_id']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
				$list[$assoc['company_id']]['descr'][] = $assoc['descr'];

				$list[$assoc['company_id']]['totalht_list'][] = $assoc['total_ht'];
				$list[$assoc['company_id']]['vat_list'][] = $assoc['total_vat'];
				$list[$assoc['company_id']]['localtax1_list'][] = $assoc['total_localtax1'];
				$list[$assoc['company_id']]['localtax2_list'][] = $assoc['total_localtax2'];

				$list[$assoc['company_id']]['pid'][] = $assoc['pid'];
				$list[$assoc['company_id']]['pref'][] = $assoc['pref'];
				$list[$assoc['company_id']]['ptype'][] = $assoc['ptype'];

				$list[$assoc['company_id']]['payment_id'][] = $assoc['payment_id'];
				$list[$assoc['company_id']]['payment_ref'][] = $assoc['payment_ref'];
				$list[$assoc['company_id']]['payment_amount'][] = $assoc['payment_amount'];

				$company_id = $assoc['company_id'];
			}
		} else {
			dol_print_error($db);
			return -3;
		}
	}


	// CASE OF EXPENSE REPORT

	if ($direction == 'buy') {		// buy only for expense reports
		// Define sql request
		$sql = '';

		// Count on payments date
		$sql = "SELECT d.rowid, d.product_type as dtype, e.rowid as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.total_tva as total_vat, e.note_private as descr,";
		$sql .= " d.total_localtax1 as total_localtax1, d.total_localtax2 as total_localtax2, ";
		$sql .= " e.date_debut as date_start, e.date_fin as date_end, e.fk_user_author,";
		$sql .= " e.ref as facnum, e.total_ttc as ftotal_ttc, e.date_create, d.fk_c_type_fees as type,";
		$sql .= " p.fk_bank as payment_id, p.amount as payment_amount, p.rowid as pid, e.ref as pref";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as e";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport_det as d ON d.fk_expensereport = e.rowid ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_expensereport as p ON p.fk_expensereport = e.rowid ";
		$sql .= " WHERE e.entity = ".$conf->entity;
		$sql .= " AND e.fk_statut in (6)";
		if ($y && $m) {
			$sql .= " AND p.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND p.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND p.datep > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = -1";
		$sql .= " OR e.date_debut is NOT null OR e.date_fin IS NOT NULL)"; // enhance detection of service
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.total_tva <> 0)";
		}
		$sql .= " ORDER BY e.rowid";

		if (!$sql) {
			dol_syslog("Tax.lib.php::tax_by_thirdparty no accountancy module enabled".$sql, LOG_ERR);
			return -1; // -1 = Not accountancy module enabled
		}
		if ($sql == 'TODO') {
			return -2; // -2 = Feature not yet available
		}
		if ($sql != 'TODO') {
			dol_syslog("Tax.lib.php::tax_by_thirdparty", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql) {
				$company_id = -1;
				$oldrowid = '';
				while ($assoc = $db->fetch_array($resql)) {
					if (!isset($list[$assoc['company_id']]['totalht'])) {
						$list[$assoc['company_id']]['totalht'] = 0;
					}
					if (!isset($list[$assoc['company_id']]['vat'])) {
						$list[$assoc['company_id']]['vat'] = 0;
					}
					if (!isset($list[$assoc['company_id']]['localtax1'])) {
						$list[$assoc['company_id']]['localtax1'] = 0;
					}
					if (!isset($list[$assoc['company_id']]['localtax2'])) {
						$list[$assoc['company_id']]['localtax2'] = 0;
					}

					if ($assoc['rowid'] != $oldrowid) {       // Si rupture sur d.rowid
						$oldrowid = $assoc['rowid'];
						$list[$assoc['company_id']]['totalht'] += $assoc['total_ht'];
						$list[$assoc['company_id']]['vat'] += $assoc['total_vat'];
						$list[$assoc['company_id']]['localtax1']	 += $assoc['total_localtax1'];
						$list[$assoc['company_id']]['localtax2']	 += $assoc['total_localtax2'];
					}

					$list[$assoc['company_id']]['dtotal_ttc'][] = $assoc['total_ttc'];
					$list[$assoc['company_id']]['dtype'][] = 'ExpenseReportPayment';
					$list[$assoc['company_id']]['datef'][] = $assoc['datef'];

					$list[$assoc['company_id']]['company_name'][] = '';
					$list[$assoc['company_id']]['company_id'][] = '';
					$list[$assoc['company_id']]['company_alias'][] = '';
					$list[$assoc['company_id']]['company_email'][] = '';
					$list[$assoc['company_id']]['company_tva_intra'][] = '';
					$list[$assoc['company_id']]['company_client'][] = '';
					$list[$assoc['company_id']]['company_fournisseur'][] = '';
					$list[$assoc['company_id']]['company_customer_code'][] = '';
					$list[$assoc['company_id']]['company_supplier_code'][] = '';
					$list[$assoc['company_id']]['company_customer_accounting_code'][] = '';
					$list[$assoc['company_id']]['company_supplier_accounting_code'][] = '';
					$list[$assoc['company_id']]['company_status'][] = '';

					$list[$assoc['company_id']]['user_id'][] = $assoc['fk_user_author'];
					$list[$assoc['company_id']]['drate'][] = $assoc['rate'];
					$list[$assoc['company_id']]['ddate_start'][] = $db->jdate($assoc['date_start']);
					$list[$assoc['company_id']]['ddate_end'][] = $db->jdate($assoc['date_end']);

					$list[$assoc['company_id']]['facid'][] = $assoc['facid'];
					$list[$assoc['company_id']]['facnum'][] = $assoc['facnum'];
					$list[$assoc['company_id']]['type'][] = $assoc['type'];
					$list[$assoc['company_id']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
					$list[$assoc['company_id']]['descr'][] = $assoc['descr'];

					$list[$assoc['company_id']]['totalht_list'][] = $assoc['total_ht'];
					$list[$assoc['company_id']]['vat_list'][] = $assoc['total_vat'];
					$list[$assoc['company_id']]['localtax1_list'][] = $assoc['total_localtax1'];
					$list[$assoc['company_id']]['localtax2_list'][] = $assoc['total_localtax2'];

					$list[$assoc['company_id']]['pid'][] = $assoc['pid'];
					$list[$assoc['company_id']]['pref'][] = $assoc['pref'];
					$list[$assoc['company_id']]['ptype'][] = 'ExpenseReportPayment';

					$list[$assoc['company_id']]['payment_id'][] = $assoc['payment_id'];
					$list[$assoc['company_id']]['payment_ref'][] = $assoc['payment_ref'];
					$list[$assoc['company_id']]['payment_amount'][] = $assoc['payment_amount'];

					$company_id = $assoc['company_id'];
				}
			} else {
				dol_print_error($db);
				return -3;
			}
		}
	}

	return $list;
}

/**
 *  Gets Tax to collect for the given year (and given quarter or month)
 *  The function gets the Tax in split results, as the Tax declaration asks
 *  to report the amounts for different Tax rates as different lines.
 *
 *  @param	string		 $type          Tax type, either 'vat', 'localtax1' or 'localtax2'
 *  @param	DoliDB		 $db          	Database handler object
 *  @param  int			 $y           	Year
 *  @param  int			 $q           	Quarter
 *  @param  int|''		 $date_start  	Start date
 *  @param  int|''		 $date_end    	End date
 *  @param  int			 $modetax     	Not used
 *  @param  'sell'|'buy' $direction   	'sell' (customer invoice) or 'buy' (supplier invoices)
 *  @param  int			 $m           	Month
 *  @return array|int               	Array with details of VATs (per rate), -1 if no accountancy module, -2 if not yet developed, -3 if error
 */
function tax_by_rate($type, $db, $y, $q, $date_start, $date_end, $modetax, $direction, $m = 0)
{
	global $conf;

	// If we use date_start and date_end, we must not use $y, $m, $q
	if (($date_start || $date_end) && (!empty($y) || !empty($m) || !empty($q))) {
		dol_print_error(null, 'Bad value of input parameter for tax_by_rate');
	}

	$list = array();

	if ($direction == 'sell') {
		$invoicetable = 'facture';
		$invoicedettable = 'facturedet';
		$fk_facture = 'fk_facture';
		$fk_facture2 = 'fk_facture';
		$fk_payment = 'fk_paiement';
		$total_tva = 'total_tva';
		$paymenttable = 'paiement';
		$paymentfacturetable = 'paiement_facture';
		$invoicefieldref = 'ref';
	} else {
		$invoicetable = 'facture_fourn';
		$invoicedettable = 'facture_fourn_det';
		$fk_facture = 'fk_facture_fourn';
		$fk_facture2 = 'fk_facturefourn';
		$fk_payment = 'fk_paiementfourn';
		$total_tva = 'tva';
		$paymenttable = 'paiementfourn';
		$paymentfacturetable = 'paiementfourn_facturefourn';
		$invoicefieldref = 'ref';
	}

	if (strpos($type, 'localtax') === 0) {
		$f_rate = $type.'_tx';
	} else {
		$f_rate = 'tva_tx';
	}

	$total_localtax1 = 'total_localtax1';
	$total_localtax2 = 'total_localtax2';


	// CASE OF PRODUCTS/GOODS

	// Define sql request
	$sql = '';
	if (($direction == 'sell' && getDolGlobalString('TAX_MODE_SELL_PRODUCT') == 'invoice')
		|| ($direction == 'buy' && getDolGlobalString('TAX_MODE_BUY_PRODUCT') == 'invoice')) {
		// Count on delivery date (use invoice date as delivery is unknown)
		$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.vat_src_code as vat_src_code, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
		$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
		$sql .= " d.date_start as date_start, d.date_end as date_end,";
		$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef,";
		$sql .= " s.nom as company_name, s.name_alias as company_alias, s.rowid as company_id, s.client as company_client, s.fournisseur as company_fournisseur, s.email as company_email,";
		$sql .= " s.code_client as company_customer_code, s.code_fournisseur as company_supplier_code,";
		$sql .= " s.code_compta as company_customer_accounting_code, s.code_compta_fournisseur as company_supplier_accounting_code,";
		$sql .= " s.status as company_status, s.tva_intra as company_tva_intra,";
		$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
		$sql .= " 0 as payment_id, '' as payment_ref, 0 as payment_amount,";
		$sql .= " '' as datep";
		$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX.$invoicedettable." as d ON d.".$fk_facture."=f.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
		$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
		$sql .= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
		if ($direction == 'buy') {
			if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		} else {
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		}
		if ($y && $m) {
			$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND f.datef > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = 0"; // Limit to products
		$sql .= " AND d.date_start is null AND d.date_end IS NULL)"; // enhance detection of products
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
		}
		$sql .= " ORDER BY d.rowid, d.".$fk_facture;
	} else {
		// Count on payments date
		$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.vat_src_code as vat_src_code, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
		$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
		$sql .= " d.date_start as date_start, d.date_end as date_end,";
		$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef,";
		$sql .= " s.nom as company_name, s.name_alias as company_alias, s.rowid as company_id, s.client as company_client, s.fournisseur as company_fournisseur, s.email as company_email,";
		$sql .= " s.code_client as company_customer_code, s.code_fournisseur as company_supplier_code,";
		$sql .= " s.code_compta as company_customer_accounting_code, s.code_compta_fournisseur as company_supplier_accounting_code,";
		$sql .= " s.status as company_status, s.tva_intra as company_tva_intra,";
		$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
		$sql .= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount,";
		$sql .= " pa.datep as datep, pa.ref as payment_ref";
		$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX.$paymentfacturetable." as pf ON pf.".$fk_facture2." = f.rowid";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX.$paymenttable." as pa ON pa.rowid = pf.".$fk_payment;
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX.$invoicedettable." as d ON d.".$fk_facture." = f.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
		$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
		$sql .= " AND f.fk_statut in (1,2)"; // Paid (partially or completely)
		if ($direction == 'buy') {
			if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		} else {
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		}
		if ($y && $m) {
			$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND pa.datep > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = 0"; // Limit to products
		$sql .= " AND d.date_start is null AND d.date_end IS NULL)"; // enhance detection of products
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
		}
		$sql .= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
	}

	if (!$sql) {
		return -1;
	}
	if ($sql == 'TODO') {
		return -2;
	}
	if ($sql != 'TODO') {
		dol_syslog("Tax.lib.php::tax_by_rate", LOG_DEBUG);

		$resql = $db->query($sql);
		if ($resql) {
			$rate = -1;
			$oldrowid = '';
			while ($assoc = $db->fetch_array($resql)) {
				$rate_key = $assoc['rate'];
				if ($f_rate == 'tva_tx' && !empty($assoc['vat_src_code']) && !preg_match('/\(/', $rate_key)) {
					$rate_key .= ' (' . $assoc['vat_src_code'] . ')';
				}

				// Code to avoid warnings when array entry not defined
				if (!isset($list[$rate_key]['totalht'])) {
					$list[$rate_key]['totalht'] = 0;
				}
				if (!isset($list[$rate_key]['vat'])) {
					$list[$rate_key]['vat'] = 0;
				}
				if (!isset($list[$rate_key]['localtax1'])) {
					$list[$rate_key]['localtax1'] = 0;
				}
				if (!isset($list[$rate_key]['localtax2'])) {
					$list[$rate_key]['localtax2'] = 0;
				}

				if ($assoc['rowid'] != $oldrowid) {       // Si rupture sur d.rowid
					$oldrowid = $assoc['rowid'];
					$list[$rate_key]['totalht']   += $assoc['total_ht'];
					$list[$rate_key]['vat']       += $assoc['total_vat'];
					$list[$rate_key]['localtax1'] += $assoc['total_localtax1'];
					$list[$rate_key]['localtax2'] += $assoc['total_localtax2'];
				}
				$list[$rate_key]['dtotal_ttc'][] = $assoc['total_ttc'];
				$list[$rate_key]['dtype'][] = $assoc['dtype'];
				$list[$rate_key]['datef'][] = $db->jdate($assoc['datef']);
				$list[$rate_key]['datep'][] = $db->jdate($assoc['datep']);

				$list[$rate_key]['company_name'][] = $assoc['company_name'];
				$list[$rate_key]['company_id'][] = $assoc['company_id'];
				$list[$rate_key]['company_alias'][] = $assoc['company_alias'];
				$list[$rate_key]['company_email'][] = $assoc['company_email'];
				$list[$rate_key]['company_tva_intra'][] = $assoc['company_tva_intra'];
				$list[$rate_key]['company_client'][] = $assoc['company_client'];
				$list[$rate_key]['company_fournisseur'][] = $assoc['company_fournisseur'];
				$list[$rate_key]['company_customer_code'][] = $assoc['company_customer_code'];
				$list[$rate_key]['company_supplier_code'][] = $assoc['company_supplier_code'];
				$list[$rate_key]['company_customer_accounting_code'][] = $assoc['company_customer_accounting_code'];
				$list[$rate_key]['company_supplier_accounting_code'][] = $assoc['company_supplier_accounting_code'];
				$list[$rate_key]['company_status'][] = $assoc['company_status'];

				$list[$rate_key]['ddate_start'][] = $db->jdate($assoc['date_start']);
				$list[$rate_key]['ddate_end'][] = $db->jdate($assoc['date_end']);

				$list[$rate_key]['facid'][] = $assoc['facid'];
				$list[$rate_key]['facnum'][] = $assoc['facnum'];
				$list[$rate_key]['type'][] = $assoc['type'];
				$list[$rate_key]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
				$list[$rate_key]['descr'][] = $assoc['descr'];

				$list[$rate_key]['totalht_list'][] = $assoc['total_ht'];
				$list[$rate_key]['vat_list'][] = $assoc['total_vat'];
				$list[$rate_key]['localtax1_list'][] = $assoc['total_localtax1'];
				$list[$rate_key]['localtax2_list'][] = $assoc['total_localtax2'];

				$list[$rate_key]['pid'][] = $assoc['pid'];
				$list[$rate_key]['pref'][] = $assoc['pref'];
				$list[$rate_key]['ptype'][] = $assoc['ptype'];

				$list[$rate_key]['payment_id'][] = $assoc['payment_id'];
				$list[$rate_key]['payment_ref'][] = $assoc['payment_ref'];
				$list[$rate_key]['payment_amount'][] = $assoc['payment_amount'];

				$rate = $assoc['rate'];
			}
		} else {
			dol_print_error($db);
			return -3;
		}
	}


	// CASE OF SERVICES

	// Define sql request
	$sql = '';
	if (($direction == 'sell' && getDolGlobalString('TAX_MODE_SELL_SERVICE') == 'invoice')
		|| ($direction == 'buy' && getDolGlobalString('TAX_MODE_BUY_SERVICE') == 'invoice')) {
		// Count on invoice date
		$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.vat_src_code as vat_src_code, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
		$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
		$sql .= " d.date_start as date_start, d.date_end as date_end,";
		$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef,";
		$sql .= " s.nom as company_name, s.name_alias as company_alias, s.rowid as company_id, s.client as company_client, s.fournisseur as company_fournisseur, s.email as company_email,";
		$sql .= " s.code_client as company_customer_code, s.code_fournisseur as company_supplier_code,";
		$sql .= " s.code_compta as company_customer_accounting_code, s.code_compta_fournisseur as company_supplier_accounting_code,";
		$sql .= " s.status as company_status, s.tva_intra as company_tva_intra,";
		$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
		$sql .= " 0 as payment_id, '' as payment_ref, 0 as payment_amount";
		$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX.$invoicedettable." as d ON d.".$fk_facture." = f.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
		$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
		$sql .= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
		if ($direction == 'buy') {
			if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		} else {
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		}
		if ($y && $m) {
			$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND f.datef > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = 1"; // Limit to services
		$sql .= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)"; // enhance detection of service
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
		}
		$sql .= " ORDER BY d.rowid, d.".$fk_facture;
	} else {
		// Count on payments date
		$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.vat_src_code as vat_src_code, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
		$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
		$sql .= " d.date_start as date_start, d.date_end as date_end,";
		$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef,";
		$sql .= " s.nom as company_name, s.name_alias as company_alias, s.rowid as company_id, s.client as company_client, s.fournisseur as company_fournisseur, s.email as company_email,";
		$sql .= " s.code_client as company_customer_code, s.code_fournisseur as company_supplier_code,";
		$sql .= " s.code_compta as company_customer_accounting_code, s.code_compta_fournisseur as company_supplier_accounting_code,";
		$sql .= " s.status as company_status, s.tva_intra as company_tva_intra,";
		$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
		$sql .= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount,";
		$sql .= " pa.datep as datep, pa.ref as payment_ref";
		$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX.$paymentfacturetable." as pf ON pf.".$fk_facture2." = f.rowid";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX.$paymenttable." as pa ON pa.rowid = pf.".$fk_payment;
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX."societe as s ON s.rowid = f.fk_soc";
		$sql .= " INNER JOIN ".MAIN_DB_PREFIX.$invoicedettable." as d ON d.".$fk_facture." = f.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
		$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
		$sql .= " AND f.fk_statut in (1,2)"; // Paid (partially or completely)
		if ($direction == 'buy') {
			if (getDolGlobalString('FACTURE_SUPPLIER_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		} else {
			if (getDolGlobalString('FACTURE_DEPOSITS_ARE_JUST_PAYMENTS')) {
				$sql .= " AND f.type IN (0,1,2,5)";
			} else {
				$sql .= " AND f.type IN (0,1,2,3,5)";
			}
		}
		if ($y && $m) {
			$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND pa.datep > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = 1"; // Limit to services
		$sql .= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)"; // enhance detection of service
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
		}
		$sql .= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
	}

	if (!$sql) {
		dol_syslog("Tax.lib.php::tax_by_rate no accountancy module enabled".$sql, LOG_ERR);
		return -1; // -1 = Not accountancy module enabled
	}
	if ($sql == 'TODO') {
		return -2; // -2 = Feature not yet available
	}
	if ($sql != 'TODO') {
		dol_syslog("Tax.lib.php::tax_by_rate", LOG_DEBUG);
		$resql = $db->query($sql);
		if ($resql) {
			$rate = -1;
			$oldrowid = '';
			while ($assoc = $db->fetch_array($resql)) {
				$rate_key = $assoc['rate'];
				if ($f_rate == 'tva_tx' && !empty($assoc['vat_src_code']) && !preg_match('/\(/', $rate_key)) {
					$rate_key .= ' (' . $assoc['vat_src_code'] . ')';
				}

				// Code to avoid warnings when array entry not defined
				if (!isset($list[$rate_key]['totalht'])) {
					$list[$rate_key]['totalht'] = 0;
				}
				if (!isset($list[$rate_key]['vat'])) {
					$list[$rate_key]['vat'] = 0;
				}
				if (!isset($list[$rate_key]['localtax1'])) {
					$list[$rate_key]['localtax1'] = 0;
				}
				if (!isset($list[$rate_key]['localtax2'])) {
					$list[$rate_key]['localtax2'] = 0;
				}

				if ($assoc['rowid'] != $oldrowid) {       // Si rupture sur d.rowid
					$oldrowid = $assoc['rowid'];
					$list[$rate_key]['totalht']   += $assoc['total_ht'];
					$list[$rate_key]['vat']       += $assoc['total_vat'];
					$list[$rate_key]['localtax1'] += $assoc['total_localtax1'];
					$list[$rate_key]['localtax2'] += $assoc['total_localtax2'];
				}
				$list[$rate_key]['dtotal_ttc'][] = $assoc['total_ttc'];
				$list[$rate_key]['dtype'][] = $assoc['dtype'];
				$list[$rate_key]['datef'][] = $db->jdate($assoc['datef']);
				$list[$rate_key]['datep'][] = $db->jdate($assoc['datep']);

				$list[$rate_key]['ddate_start'][] = $db->jdate($assoc['date_start']);
				$list[$rate_key]['ddate_end'][] = $db->jdate($assoc['date_end']);

				$list[$rate_key]['company_name'][] = $assoc['company_name'];
				$list[$rate_key]['company_id'][] = $assoc['company_id'];
				$list[$rate_key]['company_alias'][] = $assoc['company_alias'];
				$list[$rate_key]['company_email'][] = $assoc['company_email'];
				$list[$rate_key]['company_tva_intra'][] = $assoc['company_tva_intra'];
				$list[$rate_key]['company_client'][] = $assoc['company_client'];
				$list[$rate_key]['company_fournisseur'][] = $assoc['company_fournisseur'];
				$list[$rate_key]['company_customer_code'][] = $assoc['company_customer_code'];
				$list[$rate_key]['company_supplier_code'][] = $assoc['company_supplier_code'];
				$list[$rate_key]['company_customer_accounting_code'][] = $assoc['company_customer_accounting_code'];
				$list[$rate_key]['company_supplier_accounting_code'][] = $assoc['company_supplier_accounting_code'];
				$list[$rate_key]['company_status'][] = $assoc['company_status'];

				$list[$rate_key]['facid'][] = $assoc['facid'];
				$list[$rate_key]['facnum'][] = $assoc['facnum'];
				$list[$rate_key]['type'][] = $assoc['type'];
				$list[$rate_key]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
				$list[$rate_key]['descr'][] = $assoc['descr'];

				$list[$rate_key]['totalht_list'][] = $assoc['total_ht'];
				$list[$rate_key]['vat_list'][] = $assoc['total_vat'];
				$list[$rate_key]['localtax1_list'][] = $assoc['total_localtax1'];
				$list[$rate_key]['localtax2_list'][] = $assoc['total_localtax2'];

				$list[$rate_key]['pid'][] = $assoc['pid'];
				$list[$rate_key]['pref'][] = $assoc['pref'];
				$list[$rate_key]['ptype'][] = $assoc['ptype'];

				$list[$rate_key]['payment_id'][] = $assoc['payment_id'];
				$list[$rate_key]['payment_ref'][] = $assoc['payment_ref'];
				$list[$rate_key]['payment_amount'][] = $assoc['payment_amount'];

				$rate = $assoc['rate'];
			}
		} else {
			dol_print_error($db);
			return -3;
		}
	}


	// CASE OF EXPENSE REPORT

	if ($direction == 'buy') {		// buy only for expense reports
		// Define sql request
		$sql = '';

		// Count on payments date
		$sql = "SELECT d.rowid, d.product_type as dtype, e.rowid as facid, d.$f_rate as rate, d.vat_src_code as vat_src_code, d.total_ht as total_ht, d.total_ttc as total_ttc, d.total_tva as total_vat, e.note_private as descr,";
		$sql .= " d.total_localtax1 as total_localtax1, d.total_localtax2 as total_localtax2, ";
		$sql .= " e.date_debut as date_start, e.date_fin as date_end, e.fk_user_author,";
		$sql .= " e.ref as facnum, e.total_ttc as ftotal_ttc, e.date_create, d.fk_c_type_fees as type,";
		$sql .= " p.fk_bank as payment_id, p.amount as payment_amount, p.rowid as pid, e.ref as pref";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as e";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport_det as d ON d.fk_expensereport = e.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_expensereport as p ON p.fk_expensereport = e.rowid";
		$sql .= " WHERE e.entity = ".$conf->entity;
		$sql .= " AND e.fk_statut in (6)";
		if ($y && $m) {
			$sql .= " AND p.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		} elseif ($y) {
			$sql .= " AND p.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) {
			$sql .= " AND p.datep > '".$db->idate(dol_get_first_day($y, (($q - 1) * 3) + 1, false))."'";
			$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, ($q * 3), false))."'";
		}
		if ($date_start && $date_end) {
			$sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		}
		$sql .= " AND (d.product_type = -1";
		$sql .= " OR e.date_debut is NOT null OR e.date_fin IS NOT NULL)"; // enhance detection of service
		if (getDolGlobalString('MAIN_NOT_INCLUDE_ZERO_VAT_IN_REPORTS')) {
			$sql .= " AND (d.".$f_rate." <> 0 OR d.total_tva <> 0)";
		}
		$sql .= " ORDER BY e.rowid";

		if (!$sql) {
			dol_syslog("Tax.lib.php::tax_by_rate no accountancy module enabled".$sql, LOG_ERR);
			return -1; // -1 = Not accountancy module enabled
		}
		if ($sql == 'TODO') {
			return -2; // -2 = Feature not yet available
		}
		if ($sql != 'TODO') {
			dol_syslog("Tax.lib.php::tax_by_rate", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql) {
				$rate = -1;
				$oldrowid = '';
				while ($assoc = $db->fetch_array($resql)) {
					$rate_key = $assoc['rate'];
					if ($f_rate == 'tva_tx' && !empty($assoc['vat_src_code']) && !preg_match('/\(/', $rate_key)) {
						$rate_key .= ' (' . $assoc['vat_src_code'] . ')';
					}

					// Code to avoid warnings when array entry not defined
					if (!isset($list[$rate_key]['totalht'])) {
						$list[$rate_key]['totalht'] = 0;
					}
					if (!isset($list[$rate_key]['vat'])) {
						$list[$rate_key]['vat'] = 0;
					}
					if (!isset($list[$rate_key]['localtax1'])) {
						$list[$rate_key]['localtax1'] = 0;
					}
					if (!isset($list[$rate_key]['localtax2'])) {
						$list[$rate_key]['localtax2'] = 0;
					}

					if ($assoc['rowid'] != $oldrowid) {       // Si rupture sur d.rowid
						$oldrowid = $assoc['rowid'];
						$list[$rate_key]['totalht']   += $assoc['total_ht'];
						$list[$rate_key]['vat'] += $assoc['total_vat'];
						$list[$rate_key]['localtax1'] += $assoc['total_localtax1'];
						$list[$rate_key]['localtax2'] += $assoc['total_localtax2'];
					}

					$list[$rate_key]['dtotal_ttc'][] = $assoc['total_ttc'];
					$list[$rate_key]['dtype'][] = 'ExpenseReportPayment';
					$list[$rate_key]['datef'][] = $assoc['datef'];
					$list[$rate_key]['company_name'][] = '';
					$list[$rate_key]['company_id'][] = '';
					$list[$rate_key]['user_id'][] = $assoc['fk_user_author'];
					$list[$rate_key]['ddate_start'][] = $db->jdate($assoc['date_start']);
					$list[$rate_key]['ddate_end'][] = $db->jdate($assoc['date_end']);

					$list[$rate_key]['facid'][] = $assoc['facid'];
					$list[$rate_key]['facnum'][] = $assoc['facnum'];
					$list[$rate_key]['type'][] = $assoc['type'];
					$list[$rate_key]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
					$list[$rate_key]['descr'][] = $assoc['descr'];

					$list[$rate_key]['totalht_list'][] = $assoc['total_ht'];
					$list[$rate_key]['vat_list'][] = $assoc['total_vat'];
					$list[$rate_key]['localtax1_list'][] = $assoc['total_localtax1'];
					$list[$rate_key]['localtax2_list'][] = $assoc['total_localtax2'];

					$list[$rate_key]['pid'][] = $assoc['pid'];
					$list[$rate_key]['pref'][] = $assoc['pref'];
					$list[$rate_key]['ptype'][] = 'ExpenseReportPayment';

					$list[$rate_key]['payment_id'][] = $assoc['payment_id'];
					$list[$rate_key]['payment_ref'][] = $assoc['payment_ref'];
					$list[$rate_key]['payment_amount'][] = $assoc['payment_amount'];

					$rate = $assoc['rate'];
				}
			} else {
				dol_print_error($db);
				return -3;
			}
		}
	}

	return $list;
}
