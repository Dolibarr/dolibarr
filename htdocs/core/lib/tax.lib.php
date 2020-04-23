<?php
/* Copyright (C) 2004-2009 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2006-2007 Yannick Warnier		<ywarnier@beeznest.org>
 * Copyright (C) 2011	   Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2012-2017 Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012      Cédric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014 Raphaël Doursenaud   <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
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
 * @return  array						Array of tabs to show
 */
function tax_prepare_head(ChargeSociales $object)
{
    global $db, $langs, $conf, $user;

    $h = 0;
    $head = array();

	$head[$h][0] = DOL_URL_ROOT.'/compta/sociales/card.php?id='.$object->id;
	$head[$h][1] = $langs->trans('Card');
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
	if (($nbFiles + $nbLinks) > 0) $head[$h][1] .= '<span class="badge marginleftonlyshort">'.($nbFiles + $nbLinks).'</span>';
	$head[$h][2] = 'documents';
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
 *  @param	string	$type          	Tax type, either 'vat', 'localtax1' or 'localtax2'
 *  @param	DoliDB	$db          	Database handle
 *  @param  int		$y           	Year
 *  @param  string	$date_start  	Start date
 *  @param  string	$date_end    	End date
 *  @param  int		$modetax     	Not used
 *  @param  string	$direction   	'sell' or 'buy'
 *  @param  int		$m				Month
 *  @param  int		$q           	Quarter
 *  @return array|int               Array with details of VATs (per third parties), -1 if no accountancy module, -2 if not yet developped, -3 if error
 */
function tax_by_thirdparty($type, $db, $y, $date_start, $date_end, $modetax, $direction, $m = 0, $q = 0)
{
    global $conf;

    // If we use date_start and date_end, we must not use $y, $m, $q
    if (($date_start || $date_end) && (!empty($y) || !empty($m) || !empty($q)))
    {
    	dol_print_error('', 'Bad value of input parameter for tax_by_rate');
    }

    $list = array();
    if ($direction == 'sell')
    {
    	$invoicetable = 'facture';
    	$invoicedettable = 'facturedet';
    	$fk_facture = 'fk_facture';
    	$fk_facture2 = 'fk_facture';
    	$fk_payment = 'fk_paiement';
    	$total_tva = 'total_tva';
    	$paymenttable = 'paiement';
    	$paymentfacturetable = 'paiement_facture';
    	$invoicefieldref = 'ref';
    }
    elseif ($direction == 'buy')
    {
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
    if (($direction == 'sell' && $conf->global->TAX_MODE_SELL_PRODUCT == 'invoice')
    	|| ($direction == 'buy' && $conf->global->TAX_MODE_BUY_PRODUCT == 'invoice'))
    {
    	// Count on delivery date (use invoice date as delivery is unknown)
    	$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
    	$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
    	$sql .= " d.date_start as date_start, d.date_end as date_end,";
    	$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
    	$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
    	$sql .= " 0 as payment_id, 0 as payment_amount";
    	$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
    	$sql .= " ".MAIN_DB_PREFIX."societe as s,";
    	$sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
    	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
    	$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
    	$sql .= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
    	if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql .= " AND f.type IN (0,1,2,5)";
    	else $sql .= " AND f.type IN (0,1,2,3,5)";
    	$sql .= " AND f.rowid = d.".$fk_facture;
    	$sql .= " AND s.rowid = f.fk_soc";
    	if ($y && $m)
    	{
    		$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
    		$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
    	}
    	elseif ($y)
    	{
    		$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
    		$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
    	}
    	if ($q) $sql .= " AND (date_format(f.datef,'%m') > ".(($q - 1) * 3)." AND date_format(f.datef,'%m') <= ".($q * 3).")";
    	if ($date_start && $date_end) $sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    	$sql .= " AND (d.product_type = 0"; // Limit to products
    	$sql .= " AND d.date_start is null AND d.date_end IS NULL)"; // enhance detection of products
    	if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
    	$sql .= " ORDER BY d.rowid, d.".$fk_facture;
    }
    else
    {
    	// Count on payments date
    	$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
    	$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
    	$sql .= " d.date_start as date_start, d.date_end as date_end,";
    	$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
    	$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
    	$sql .= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount,";
    	$sql .= " pa.datep as datep";
    	$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
    	$sql .= " ".MAIN_DB_PREFIX.$paymentfacturetable." as pf,";
    	$sql .= " ".MAIN_DB_PREFIX.$paymenttable." as pa,";
    	$sql .= " ".MAIN_DB_PREFIX."societe as s,";
    	$sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
    	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
    	$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
    	$sql .= " AND f.fk_statut in (1,2)"; // Paid (partially or completely)
    	if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql .= " AND f.type IN (0,1,2,5)";
    	else $sql .= " AND f.type IN (0,1,2,3,5)";
    	$sql .= " AND f.rowid = d.".$fk_facture;
    	$sql .= " AND s.rowid = f.fk_soc";
    	$sql .= " AND pf.".$fk_facture2." = f.rowid";
    	$sql .= " AND pa.rowid = pf.".$fk_payment;
    	if ($y && $m)
    	{
    		$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
    		$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
    	}
    	elseif ($y)
    	{
    		$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
    		$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
    	}
    	if ($q) $sql .= " AND (date_format(pa.datep,'%m') > ".(($q - 1) * 3)." AND date_format(pa.datep,'%m') <= ".($q * 3).")";
    	if ($date_start && $date_end) $sql .= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
    	$sql .= " AND (d.product_type = 0"; // Limit to products
    	$sql .= " AND d.date_start is null AND d.date_end IS NULL)"; // enhance detection of products
    	if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
    	$sql .= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
    }

    if (!$sql) return -1;
    if ($sql == 'TODO') return -2;
    if ($sql != 'TODO')
    {
    	dol_syslog("Tax.lib.php::tax_by_thirdparty", LOG_DEBUG);

    	$resql = $db->query($sql);
    	if ($resql)
    	{
    		$company_id = -1;
    		$oldrowid = '';
    		while ($assoc = $db->fetch_array($resql))
    		{
    			if (!isset($list[$assoc['company_id']]['totalht']))  $list[$assoc['company_id']]['totalht'] = 0;
    			if (!isset($list[$assoc['company_id']]['vat']))      $list[$assoc['company_id']]['vat'] = 0;
    			if (!isset($list[$assoc['company_id']]['localtax1']))      $list[$assoc['company_id']]['localtax1'] = 0;
    			if (!isset($list[$assoc['company_id']]['localtax2']))      $list[$assoc['company_id']]['localtax2'] = 0;

    			if ($assoc['rowid'] != $oldrowid)       // Si rupture sur d.rowid
    			{
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
    	}
    	else
    	{
    		dol_print_error($db);
    		return -3;
    	}
    }


    // CAS DES SERVICES

    // Define sql request
    $sql = '';
    if (($direction == 'sell' && $conf->global->TAX_MODE_SELL_SERVICE == 'invoice')
    	|| ($direction == 'buy' && $conf->global->TAX_MODE_BUY_SERVICE == 'invoice'))
    {
    	// Count on invoice date
    	$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
    	$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
    	$sql .= " d.date_start as date_start, d.date_end as date_end,";
    	$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
    	$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
    	$sql .= " 0 as payment_id, 0 as payment_amount";
    	$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
    	$sql .= " ".MAIN_DB_PREFIX."societe as s,";
    	$sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
    	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
    	$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
    	$sql .= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
    	if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql .= " AND f.type IN (0,1,2,5)";
    	else $sql .= " AND f.type IN (0,1,2,3,5)";
    	$sql .= " AND f.rowid = d.".$fk_facture;
    	$sql .= " AND s.rowid = f.fk_soc";
    	if ($y && $m)
    	{
    		$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
    		$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
    	}
    	elseif ($y)
    	{
    		$sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
    		$sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
    	}
    	if ($q) $sql .= " AND (date_format(f.datef,'%m') > ".(($q - 1) * 3)." AND date_format(f.datef,'%m') <= ".($q * 3).")";
    	if ($date_start && $date_end) $sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
    	$sql .= " AND (d.product_type = 1"; // Limit to services
    	$sql .= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)"; // enhance detection of service
    	if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
    	$sql .= " ORDER BY d.rowid, d.".$fk_facture;
    }
    else
    {
    	// Count on payments date
    	$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
    	$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
    	$sql .= " d.date_start as date_start, d.date_end as date_end,";
    	$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
    	$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
    	$sql .= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount,";
    	$sql .= " pa.datep as datep";
    	$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
    	$sql .= " ".MAIN_DB_PREFIX.$paymentfacturetable." as pf,";
    	$sql .= " ".MAIN_DB_PREFIX.$paymenttable." as pa,";
    	$sql .= " ".MAIN_DB_PREFIX."societe as s,";
    	$sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
    	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
    	$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
    	$sql .= " AND f.fk_statut in (1,2)"; // Paid (partially or completely)
    	if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql .= " AND f.type IN (0,1,2,5)";
    	else $sql .= " AND f.type IN (0,1,2,3,5)";
    	$sql .= " AND f.rowid = d.".$fk_facture;
    	$sql .= " AND s.rowid = f.fk_soc";
    	$sql .= " AND pf.".$fk_facture2." = f.rowid";
    	$sql .= " AND pa.rowid = pf.".$fk_payment;
    	if ($y && $m)
    	{
    		$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
    		$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
    	}
    	elseif ($y)
    	{
    		$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
    		$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
    	}
    	if ($q) $sql .= " AND (date_format(pa.datep,'%m') > ".(($q - 1) * 3)." AND date_format(pa.datep,'%m') <= ".($q * 3).")";
    	if ($date_start && $date_end) $sql .= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
    	$sql .= " AND (d.product_type = 1"; // Limit to services
    	$sql .= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)"; // enhance detection of service
    	if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
    	$sql .= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
    }

    if (!$sql)
    {
    	dol_syslog("Tax.lib.php::tax_by_rate no accountancy module enabled".$sql, LOG_ERR);
    	return -1; // -1 = Not accountancy module enabled
    }
    if ($sql == 'TODO') return -2; // -2 = Feature not yet available
    if ($sql != 'TODO')
    {
    	dol_syslog("Tax.lib.php::tax_by_rate", LOG_DEBUG);
    	$resql = $db->query($sql);
    	if ($resql)
    	{
    		$company_id = -1;
    		$oldrowid = '';
    		while ($assoc = $db->fetch_array($resql))
    		{
    			if (!isset($list[$assoc['company_id']]['totalht']))  $list[$assoc['company_id']]['totalht'] = 0;
    			if (!isset($list[$assoc['company_id']]['vat']))      $list[$assoc['company_id']]['vat'] = 0;
    			if (!isset($list[$assoc['company_id']]['localtax1']))      $list[$assoc['company_id']]['localtax1'] = 0;
    			if (!isset($list[$assoc['company_id']]['localtax2']))      $list[$assoc['company_id']]['localtax2'] = 0;

    			if ($assoc['rowid'] != $oldrowid)       // Si rupture sur d.rowid
    			{
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
    	}
    	else
    	{
    		dol_print_error($db);
    		return -3;
    	}
    }


    // CASE OF EXPENSE REPORT

    if ($direction == 'buy')		// buy only for expense reports
    {
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
    	if ($y && $m)
    	{
    		$sql .= " AND p.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
    		$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
    	}
    	elseif ($y)
    	{
    		$sql .= " AND p.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
    		$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
    	}
    	if ($q) $sql .= " AND (date_format(p.datep,'%m') > ".(($q - 1) * 3)." AND date_format(p.datep,'%m') <= ".($q * 3).")";
    	if ($date_start && $date_end) $sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
    	$sql .= " AND (d.product_type = -1";
    	$sql .= " OR e.date_debut is NOT null OR e.date_fin IS NOT NULL)"; // enhance detection of service
    	if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.total_tva <> 0)";
    	$sql .= " ORDER BY e.rowid";

    	if (!$sql)
    	{
    		dol_syslog("Tax.lib.php::tax_by_rate no accountancy module enabled".$sql, LOG_ERR);
    		return -1; // -1 = Not accountancy module enabled
    	}
    	if ($sql == 'TODO') return -2; // -2 = Feature not yet available
    	if ($sql != 'TODO')
    	{
    		dol_syslog("Tax.lib.php::tax_by_rate", LOG_DEBUG);
    		$resql = $db->query($sql);
    		if ($resql)
    		{
    			$company_id = -1;
    			$oldrowid = '';
    			while ($assoc = $db->fetch_array($resql))
    			{
    				if (!isset($list[$assoc['company_id']]['totalht']))  $list[$assoc['company_id']]['totalht'] = 0;
    				if (!isset($list[$assoc['company_id']]['vat']))      $list[$assoc['company_id']]['vat'] = 0;
    				if (!isset($list[$assoc['company_id']]['localtax1']))      $list[$assoc['company_id']]['localtax1'] = 0;
    				if (!isset($list[$assoc['company_id']]['localtax2']))      $list[$assoc['company_id']]['localtax2'] = 0;

    				if ($assoc['rowid'] != $oldrowid)       // Si rupture sur d.rowid
    				{
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
    				$list[$assoc['company_id']]['payment_amount'][] = $assoc['payment_amount'];

    				$company_id = $assoc['company_id'];
    			}
    		}
    		else
    		{
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
 *  @param	string	$type          	Tax type, either 'vat', 'localtax1' or 'localtax2'
 *  @param	DoliDB	$db          	Database handler object
 *  @param  int		$y           	Year
 *  @param  int		$q           	Quarter
 *  @param  string	$date_start  	Start date
 *  @param  string	$date_end    	End date
 *  @param  int		$modetax     	Not used
 *  @param  int		$direction   	'sell' (customer invoice) or 'buy' (supplier invoices)
 *  @param  int		$m           	Month
 *  @return array|int               Array with details of VATs (per rate), -1 if no accountancy module, -2 if not yet developped, -3 if error
 */
function tax_by_rate($type, $db, $y, $q, $date_start, $date_end, $modetax, $direction, $m = 0)
{
    global $conf;

    // If we use date_start and date_end, we must not use $y, $m, $q
    if (($date_start || $date_end) && (!empty($y) || !empty($m) || !empty($q)))
    {
    	dol_print_error('', 'Bad value of input parameter for tax_by_rate');
    }

    $list = array();

    if ($direction == 'sell')
    {
        $invoicetable = 'facture';
        $invoicedettable = 'facturedet';
        $fk_facture = 'fk_facture';
        $fk_facture2 = 'fk_facture';
        $fk_payment = 'fk_paiement';
        $total_tva = 'total_tva';
        $paymenttable = 'paiement';
        $paymentfacturetable = 'paiement_facture';
        $invoicefieldref = 'ref';
    }
    else
    {
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
    if (($direction == 'sell' && $conf->global->TAX_MODE_SELL_PRODUCT == 'invoice')
    	|| ($direction == 'buy' && $conf->global->TAX_MODE_BUY_PRODUCT == 'invoice'))
    {
        // Count on delivery date (use invoice date as delivery is unknown)
        $sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
        $sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
        $sql .= " d.date_start as date_start, d.date_end as date_end,";
        $sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
        $sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
        $sql .= " 0 as payment_id, 0 as payment_amount";
        $sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
        $sql .= " ".MAIN_DB_PREFIX."societe as s,";
        $sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
        $sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
        $sql .= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
        if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql .= " AND f.type IN (0,1,2,5)";
        else $sql .= " AND f.type IN (0,1,2,3,5)";
        $sql .= " AND f.rowid = d.".$fk_facture;
        $sql .= " AND s.rowid = f.fk_soc";
        if ($y && $m)
        {
            $sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
            $sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
        }
        elseif ($y)
        {
            $sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
            $sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
        }
        if ($q) $sql .= " AND (date_format(f.datef,'%m') > ".(($q - 1) * 3)." AND date_format(f.datef,'%m') <= ".($q * 3).")";
        if ($date_start && $date_end) $sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
        $sql .= " AND (d.product_type = 0"; // Limit to products
        $sql .= " AND d.date_start is null AND d.date_end IS NULL)"; // enhance detection of products
        if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
        $sql .= " ORDER BY d.rowid, d.".$fk_facture;
    }
    else
    {
    	// Count on payments date
    	$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
    	$sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
    	$sql .= " d.date_start as date_start, d.date_end as date_end,";
    	$sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
    	$sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
    	$sql .= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount,";
    	$sql .= " pa.datep as datep";
    	$sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
    	$sql .= " ".MAIN_DB_PREFIX.$paymentfacturetable." as pf,";
    	$sql .= " ".MAIN_DB_PREFIX.$paymenttable." as pa,";
    	$sql .= " ".MAIN_DB_PREFIX."societe as s,";
    	$sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
    	$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
    	$sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
    	$sql .= " AND f.fk_statut in (1,2)"; // Paid (partially or completely)
    	if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql .= " AND f.type IN (0,1,2,5)";
    	else $sql .= " AND f.type IN (0,1,2,3,5)";
    	$sql .= " AND f.rowid = d.".$fk_facture;
    	$sql .= " AND s.rowid = f.fk_soc";
    	$sql .= " AND pf.".$fk_facture2." = f.rowid";
    	$sql .= " AND pa.rowid = pf.".$fk_payment;
    	if ($y && $m)
    	{
    		$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
    		$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
    	}
    	elseif ($y)
    	{
    		$sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
    		$sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
    	}
    	if ($q) $sql .= " AND (date_format(pa.datep,'%m') > ".(($q - 1) * 3)." AND date_format(pa.datep,'%m') <= ".($q * 3).")";
    	if ($date_start && $date_end) $sql .= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
    	$sql .= " AND (d.product_type = 0"; // Limit to products
    	$sql .= " AND d.date_start is null AND d.date_end IS NULL)"; // enhance detection of products
    	if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
    	$sql .= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
    }

    if (!$sql) return -1;
    if ($sql == 'TODO') return -2;
    if ($sql != 'TODO')
    {
        dol_syslog("Tax.lib.php::tax_by_rate", LOG_DEBUG);

        $resql = $db->query($sql);
        if ($resql)
        {
            $rate = -1;
            $oldrowid = '';
            while ($assoc = $db->fetch_array($resql))
            {
            	// Code to avoid warnings when array entry not defined
            	if (!isset($list[$assoc['rate']]['totalht']))   $list[$assoc['rate']]['totalht'] = 0;
                if (!isset($list[$assoc['rate']]['vat']))       $list[$assoc['rate']]['vat'] = 0;
                if (!isset($list[$assoc['rate']]['localtax1'])) $list[$assoc['rate']]['localtax1'] = 0;
                if (!isset($list[$assoc['rate']]['localtax2'])) $list[$assoc['rate']]['localtax2'] = 0;

                if ($assoc['rowid'] != $oldrowid)       // Si rupture sur d.rowid
                {
                    $oldrowid = $assoc['rowid'];
                    $list[$assoc['rate']]['totalht']   += $assoc['total_ht'];
                    $list[$assoc['rate']]['vat']       += $assoc['total_vat'];
                    $list[$assoc['rate']]['localtax1'] += $assoc['total_localtax1'];
                    $list[$assoc['rate']]['localtax2'] += $assoc['total_localtax2'];
                }
                $list[$assoc['rate']]['dtotal_ttc'][] = $assoc['total_ttc'];
                $list[$assoc['rate']]['dtype'][] = $assoc['dtype'];
                $list[$assoc['rate']]['datef'][] = $db->jdate($assoc['datef']);
                $list[$assoc['rate']]['datep'][] = $db->jdate($assoc['datep']);
                $list[$assoc['rate']]['company_name'][] = $assoc['company_name'];
                $list[$assoc['rate']]['company_id'][] = $assoc['company_id'];
                $list[$assoc['rate']]['ddate_start'][] = $db->jdate($assoc['date_start']);
                $list[$assoc['rate']]['ddate_end'][] = $db->jdate($assoc['date_end']);

                $list[$assoc['rate']]['facid'][] = $assoc['facid'];
                $list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
                $list[$assoc['rate']]['type'][] = $assoc['type'];
                $list[$assoc['rate']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
                $list[$assoc['rate']]['descr'][] = $assoc['descr'];

                $list[$assoc['rate']]['totalht_list'][] = $assoc['total_ht'];
                $list[$assoc['rate']]['vat_list'][] = $assoc['total_vat'];
                $list[$assoc['rate']]['localtax1_list'][] = $assoc['total_localtax1'];
                $list[$assoc['rate']]['localtax2_list'][] = $assoc['total_localtax2'];

                $list[$assoc['rate']]['pid'][] = $assoc['pid'];
                $list[$assoc['rate']]['pref'][] = $assoc['pref'];
                $list[$assoc['rate']]['ptype'][] = $assoc['ptype'];

                $list[$assoc['rate']]['payment_id'][] = $assoc['payment_id'];
                $list[$assoc['rate']]['payment_amount'][] = $assoc['payment_amount'];

                $rate = $assoc['rate'];
            }
        }
        else
        {
            dol_print_error($db);
            return -3;
        }
    }


    // CAS DES SERVICES

    // Define sql request
    $sql = '';
    if (($direction == 'sell' && $conf->global->TAX_MODE_SELL_SERVICE == 'invoice')
    	|| ($direction == 'buy' && $conf->global->TAX_MODE_BUY_SERVICE == 'invoice'))
    {
        // Count on invoice date
        $sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
        $sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
        $sql .= " d.date_start as date_start, d.date_end as date_end,";
        $sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
        $sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
        $sql .= " 0 as payment_id, 0 as payment_amount";
        $sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
        $sql .= " ".MAIN_DB_PREFIX."societe as s,";
        $sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
        $sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
        $sql .= " AND f.fk_statut in (1,2)"; // Validated or paid (partially or completely)
        if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql .= " AND f.type IN (0,1,2,5)";
		else $sql .= " AND f.type IN (0,1,2,3,5)";
        $sql .= " AND f.rowid = d.".$fk_facture;
        $sql .= " AND s.rowid = f.fk_soc";
        if ($y && $m)
        {
            $sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
            $sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
        }
        elseif ($y)
        {
            $sql .= " AND f.datef >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
            $sql .= " AND f.datef <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
        }
        if ($q) $sql .= " AND (date_format(f.datef,'%m') > ".(($q - 1) * 3)." AND date_format(f.datef,'%m') <= ".($q * 3).")";
        if ($date_start && $date_end) $sql .= " AND f.datef >= '".$db->idate($date_start)."' AND f.datef <= '".$db->idate($date_end)."'";
        $sql .= " AND (d.product_type = 1"; // Limit to services
        $sql .= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)"; // enhance detection of service
        if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
        $sql .= " ORDER BY d.rowid, d.".$fk_facture;
    }
    else
    {
        // Count on payments date
        $sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
        $sql .= " d.".$total_localtax1." as total_localtax1, d.".$total_localtax2." as total_localtax2, ";
        $sql .= " d.date_start as date_start, d.date_end as date_end,";
        $sql .= " f.".$invoicefieldref." as facnum, f.type, f.total_ttc as ftotal_ttc, f.datef, s.nom as company_name, s.rowid as company_id,";
        $sql .= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
        $sql .= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount,";
        $sql .= " pa.datep as datep";
        $sql .= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
        $sql .= " ".MAIN_DB_PREFIX.$paymentfacturetable." as pf,";
        $sql .= " ".MAIN_DB_PREFIX.$paymenttable." as pa,";
        $sql .= " ".MAIN_DB_PREFIX."societe as s,";
        $sql .= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
        $sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
        $sql .= " WHERE f.entity IN (".getEntity($invoicetable).")";
        $sql .= " AND f.fk_statut in (1,2)"; // Paid (partially or completely)
        if (!empty($conf->global->FACTURE_DEPOSITS_ARE_JUST_PAYMENTS)) $sql .= " AND f.type IN (0,1,2,5)";
		else $sql .= " AND f.type IN (0,1,2,3,5)";
        $sql .= " AND f.rowid = d.".$fk_facture;
        $sql .= " AND s.rowid = f.fk_soc";
        $sql .= " AND pf.".$fk_facture2." = f.rowid";
        $sql .= " AND pa.rowid = pf.".$fk_payment;
        if ($y && $m)
        {
            $sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
            $sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
        }
        elseif ($y)
        {
            $sql .= " AND pa.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
            $sql .= " AND pa.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
        }
        if ($q) $sql .= " AND (date_format(pa.datep,'%m') > ".(($q - 1) * 3)." AND date_format(pa.datep,'%m') <= ".($q * 3).")";
        if ($date_start && $date_end) $sql .= " AND pa.datep >= '".$db->idate($date_start)."' AND pa.datep <= '".$db->idate($date_end)."'";
        $sql .= " AND (d.product_type = 1"; // Limit to services
        $sql .= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)"; // enhance detection of service
        if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.".$total_tva." <> 0)";
        $sql .= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
    }

    if (!$sql)
    {
        dol_syslog("Tax.lib.php::tax_by_rate no accountancy module enabled".$sql, LOG_ERR);
        return -1; // -1 = Not accountancy module enabled
    }
    if ($sql == 'TODO') return -2; // -2 = Feature not yet available
    if ($sql != 'TODO')
    {
        dol_syslog("Tax.lib.php::tax_by_rate", LOG_DEBUG);
        $resql = $db->query($sql);
        if ($resql)
        {
            $rate = -1;
            $oldrowid = '';
            while ($assoc = $db->fetch_array($resql))
            {
            	// Code to avoid warnings when array entry not defined
            	if (!isset($list[$assoc['rate']]['totalht']))   $list[$assoc['rate']]['totalht'] = 0;
                if (!isset($list[$assoc['rate']]['vat']))       $list[$assoc['rate']]['vat'] = 0;
				if (!isset($list[$assoc['rate']]['localtax1'])) $list[$assoc['rate']]['localtax1'] = 0;
                if (!isset($list[$assoc['rate']]['localtax2'])) $list[$assoc['rate']]['localtax2'] = 0;

                if ($assoc['rowid'] != $oldrowid)       // Si rupture sur d.rowid
                {
                    $oldrowid = $assoc['rowid'];
                    $list[$assoc['rate']]['totalht']   += $assoc['total_ht'];
                    $list[$assoc['rate']]['vat']       += $assoc['total_vat'];
                    $list[$assoc['rate']]['localtax1'] += $assoc['total_localtax1'];
                    $list[$assoc['rate']]['localtax2'] += $assoc['total_localtax2'];
                }
                $list[$assoc['rate']]['dtotal_ttc'][] = $assoc['total_ttc'];
                $list[$assoc['rate']]['dtype'][] = $assoc['dtype'];
                $list[$assoc['rate']]['datef'][] = $db->jdate($assoc['datef']);
                $list[$assoc['rate']]['datep'][] = $db->jdate($assoc['datep']);
                $list[$assoc['rate']]['company_name'][] = $assoc['company_name'];
                $list[$assoc['rate']]['company_id'][] = $assoc['company_id'];
                $list[$assoc['rate']]['ddate_start'][] = $db->jdate($assoc['date_start']);
                $list[$assoc['rate']]['ddate_end'][] = $db->jdate($assoc['date_end']);

                $list[$assoc['rate']]['facid'][] = $assoc['facid'];
                $list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
                $list[$assoc['rate']]['type'][] = $assoc['type'];
                $list[$assoc['rate']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
                $list[$assoc['rate']]['descr'][] = $assoc['descr'];

                $list[$assoc['rate']]['totalht_list'][] = $assoc['total_ht'];
                $list[$assoc['rate']]['vat_list'][] = $assoc['total_vat'];
                $list[$assoc['rate']]['localtax1_list'][] = $assoc['total_localtax1'];
                $list[$assoc['rate']]['localtax2_list'][] = $assoc['total_localtax2'];

                $list[$assoc['rate']]['pid'][] = $assoc['pid'];
                $list[$assoc['rate']]['pref'][] = $assoc['pref'];
                $list[$assoc['rate']]['ptype'][] = $assoc['ptype'];

                $list[$assoc['rate']]['payment_id'][] = $assoc['payment_id'];
                $list[$assoc['rate']]['payment_amount'][] = $assoc['payment_amount'];

                $rate = $assoc['rate'];
            }
        }
        else
        {
            dol_print_error($db);
            return -3;
        }
    }


    // CASE OF EXPENSE REPORT

	if ($direction == 'buy')		// buy only for expense reports
	{
		// Define sql request
		$sql = '';

		// Count on payments date
		$sql = "SELECT d.rowid, d.product_type as dtype, e.rowid as facid, d.$f_rate as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.total_tva as total_vat, e.note_private as descr,";
		$sql .= " d.total_localtax1 as total_localtax1, d.total_localtax2 as total_localtax2, ";
		$sql .= " e.date_debut as date_start, e.date_fin as date_end, e.fk_user_author,";
		$sql .= " e.ref as facnum, e.total_ttc as ftotal_ttc, e.date_create, d.fk_c_type_fees as type,";
		$sql .= " p.fk_bank as payment_id, p.amount as payment_amount, p.rowid as pid, e.ref as pref";
		$sql .= " FROM ".MAIN_DB_PREFIX."expensereport as e ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."expensereport_det as d ON d.fk_expensereport = e.rowid ";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."payment_expensereport as p ON p.fk_expensereport = e.rowid ";
		$sql .= " WHERE e.entity = ".$conf->entity;
		$sql .= " AND e.fk_statut in (6)";
		if ($y && $m)
		{
			$sql .= " AND p.datep >= '".$db->idate(dol_get_first_day($y, $m, false))."'";
			$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, $m, false))."'";
		}
		elseif ($y)
		{
			$sql .= " AND p.datep >= '".$db->idate(dol_get_first_day($y, 1, false))."'";
			$sql .= " AND p.datep <= '".$db->idate(dol_get_last_day($y, 12, false))."'";
		}
		if ($q) $sql .= " AND (date_format(p.datep,'%m') > ".(($q - 1) * 3)." AND date_format(p.datep,'%m') <= ".($q * 3).")";
		if ($date_start && $date_end) $sql .= " AND p.datep >= '".$db->idate($date_start)."' AND p.datep <= '".$db->idate($date_end)."'";
		$sql .= " AND (d.product_type = -1";
		$sql .= " OR e.date_debut is NOT null OR e.date_fin IS NOT NULL)"; // enhance detection of service
		if (empty($conf->global->MAIN_INCLUDE_ZERO_VAT_IN_REPORTS)) $sql .= " AND (d.".$f_rate." <> 0 OR d.total_tva <> 0)";
		$sql .= " ORDER BY e.rowid";

		if (!$sql)
		{
			dol_syslog("Tax.lib.php::tax_by_rate no accountancy module enabled".$sql, LOG_ERR);
			return -1; // -1 = Not accountancy module enabled
		}
		if ($sql == 'TODO') return -2; // -2 = Feature not yet available
		if ($sql != 'TODO')
		{
			dol_syslog("Tax.lib.php::tax_by_rate", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql)
			{
				$rate = -1;
				$oldrowid = '';
				while ($assoc = $db->fetch_array($resql))
				{
					// Code to avoid warnings when array entry not defined
					if (!isset($list[$assoc['rate']]['totalht']))   $list[$assoc['rate']]['totalht'] = 0;
					if (!isset($list[$assoc['rate']]['vat']))       $list[$assoc['rate']]['vat'] = 0;
					if (!isset($list[$assoc['rate']]['localtax1'])) $list[$assoc['rate']]['localtax1'] = 0;
					if (!isset($list[$assoc['rate']]['localtax2'])) $list[$assoc['rate']]['localtax2'] = 0;

					if ($assoc['rowid'] != $oldrowid)       // Si rupture sur d.rowid
					{
						$oldrowid = $assoc['rowid'];
						$list[$assoc['rate']]['totalht']   += $assoc['total_ht'];
                        $list[$assoc['rate']]['vat'] += $assoc['total_vat'];
						$list[$assoc['rate']]['localtax1'] += $assoc['total_localtax1'];
						$list[$assoc['rate']]['localtax2'] += $assoc['total_localtax2'];
					}

					$list[$assoc['rate']]['dtotal_ttc'][] = $assoc['total_ttc'];
					$list[$assoc['rate']]['dtype'][] = 'ExpenseReportPayment';
					$list[$assoc['rate']]['datef'][] = $assoc['datef'];
					$list[$assoc['rate']]['company_name'][] = '';
					$list[$assoc['rate']]['company_id'][] = '';
					$list[$assoc['rate']]['user_id'][] = $assoc['fk_user_author'];
					$list[$assoc['rate']]['ddate_start'][] = $db->jdate($assoc['date_start']);
					$list[$assoc['rate']]['ddate_end'][] = $db->jdate($assoc['date_end']);

					$list[$assoc['rate']]['facid'][] = $assoc['facid'];
					$list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
					$list[$assoc['rate']]['type'][] = $assoc['type'];
					$list[$assoc['rate']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
					$list[$assoc['rate']]['descr'][] = $assoc['descr'];

					$list[$assoc['rate']]['totalht_list'][] = $assoc['total_ht'];
					$list[$assoc['rate']]['vat_list'][] = $assoc['total_vat'];
					$list[$assoc['rate']]['localtax1_list'][] = $assoc['total_localtax1'];
					$list[$assoc['rate']]['localtax2_list'][] = $assoc['total_localtax2'];

					$list[$assoc['rate']]['pid'][] = $assoc['pid'];
					$list[$assoc['rate']]['pref'][] = $assoc['pref'];
					$list[$assoc['rate']]['ptype'][] = 'ExpenseReportPayment';

					$list[$assoc['rate']]['payment_id'][] = $assoc['payment_id'];
					$list[$assoc['rate']]['payment_amount'][] = $assoc['payment_amount'];

					$rate = $assoc['rate'];
				}
			}
			else
			{
				dol_print_error($db);
				return -3;
			}
		}
	}

	return $list;
}
