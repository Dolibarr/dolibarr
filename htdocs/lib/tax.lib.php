<?php
/* Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006-2007 Yannick Warnier      <ywarnier@beeznest.org>
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

/**
 *	    \file       htdocs/lib/tax.lib.php
 *      \ingroup    tax
 *		\brief      Library for tax module
 *		\version    $Id$
 */


/**
 * 	\brief		Look for collectable VAT clients in the chosen year
 *	\param		db			Database handle
 *	\param		y			Year
 *	\param		modetax		0 or 1 (option vat on debit)
 *	\param		direction	'sell' or 'buy'
 *	\return		array		List of customers third parties with vat, -1 if no accountancy module, -2 if not yet developped, -3 if error
 */
function vat_by_thirdparty($db, $y, $modetax, $direction)
{
	global $conf;

   	$list=array();
	//print "xx".$conf->global->MAIN_MODULE_ACCOUNTING;
	//print "xx".$conf->global->MAIN_MODULE_COMPTABILITE;

	if ($direction == 'sell')
	{
		$invoicetable='facture';
		$invoicedettable='facturedet';
		$fk_facture='fk_facture';
		$total_tva='total_tva';
	}
	if ($direction == 'buy')
	{
		$invoicetable='facture_fourn';
		$invoicedettable='facture_fourn_det';
		$fk_facture='fk_facture_fourn';
		$total_tva='tva';
	}

    // Define sql request
	$sql='';
	if ($modetax == 1)
    {
        // If vat paid on due invoices (non draft)
		if ($conf->global->MAIN_MODULE_ACCOUNTING)
		{
	        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stocke dans la table des payments.
	        // Seul le module compta expert peut r�soudre ce probl�me.
	        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
			$sql = 'TODO';
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{
	        $sql = "SELECT s.nom as nom, s.tva_intra as tva_intra,";
			$sql.= " sum(fd.total_ht) as amount, sum(fd.".$total_tva.") as tva,";
			$sql.= " s.tva_assuj as assuj, s.rowid as socid";
	        $sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f, ".MAIN_DB_PREFIX.$invoicedettable." as fd, ".MAIN_DB_PREFIX."societe as s";
	        $sql.= " WHERE ";
	        $sql.= " f.fk_statut in (1,2)";	// Validated or paid (partially or completely)
	        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
	        $sql.= " AND s.rowid = f.fk_soc AND f.rowid = fd.".$fk_facture;
	        $sql.= " GROUP BY s.rowid";
		}
    }
    else
    {
		if ($conf->global->MAIN_MODULE_ACCOUNTING)
		{
			// If vat paid on payments
	        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stock� dans la table des payments.
	        // Seul le module compta expert peut r�soudre ce probl�me.
	        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
			$sql = 'TODO';
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{
	        // Tva sur factures pay�s (should be on payment)
/*	        $sql = "SELECT s.nom as nom, s.tva_intra as tva_intra,";
			$sql.= " sum(fd.total_ht) as amount, sum(".$total_tva.") as tva,";
			$sql.= " s.tva_assuj as assuj, s.rowid as socid";
	        $sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f, ".MAIN_DB_PREFIX.$invoicetable." as fd, ".MAIN_DB_PREFIX."societe as s";
	        $sql.= " WHERE ";
			$sql.= " f.fk_statut in (2)";	// Paid (partially or completely)
			$sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
	        $sql.= " AND s.rowid = f.fk_soc AND f.rowid = fd.".$fk_facture;
			$sql.= " GROUP BY s.rowid";
*/
			$sql = 'TODO';
		}
    }

	if (! $sql) return -1;
	if ($sql && $sql=='TODO') return -2;
	if ($sql && $sql!='TODO')
	{
		dol_syslog("Tax.lib:thirdparty sql=".$sql);
	    $resql = $db->query($sql);
	    if ($resql)
	    {
	    	while($assoc = $db->fetch_array($resql))
			{
	        	$list[] = $assoc;
	    	}
			$db->free($resql);
	    	return $list;
	    }
	    else
	    {
	        dol_print_error($db);
			return -3;
	    }
	}
}


/**
 * 	\brief		Gets VAT to collect for the given year (and given quarter or month)
 *				The function gets the VAT in split results, as the VAT declaration asks
 * 				to report the amounts for different VAT rates as different lines.
 * 				This function also accounts recurrent invoices
 * 	\param		db			Database handler object
 * 	\param		y			Year
 *	\param		q			Period. If 1-4, it's year quarter.
 *	\param		modetax		0 or 1 (option vat on debit)
 *	\param		direction	'sell' (customer invoice) or 'buy' (supplier invoices)
 * 	\param		m			Month
 * 	\return		array		List of quarters with vat
 */
function vat_by_quarter($db, $y, $q=0, $modetax, $direction, $m=0)
{
	global $conf;

	$list=array();

	if ($direction == 'sell')
	{
		$invoicetable='facture';
		$invoicedettable='facturedet';
		$fk_facture='fk_facture';
		$fk_facture2='fk_facture';
		$fk_payment='fk_paiement';
		$total_tva='total_tva';
		$paymenttable='paiement';
		$paymentfacturetable='paiement_facture';
	}
	if ($direction == 'buy')
	{
		$invoicetable='facture_fourn';
		$invoicedettable='facture_fourn_det';
		$fk_facture='fk_facture_fourn';
		$fk_facture2='fk_facturefourn';
		$fk_payment='fk_paiementfourn';
		$total_tva='tva';
		$paymenttable='paiementfourn';
		$paymentfacturetable='paiementfourn_facturefourn';
	}

    // CAS DES BIENS

	// Define sql request
	$sql='';
	if ($modetax == 1)	// Option vat on delivery for goods (payment) and debit invoice for services
    {
		if ($conf->global->MAIN_MODULE_ACCOUNTING)
		{
	        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stocke dans la table des payments.
	        // Seul le module compta expert peut resoudre ce probleme.
	        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
			$sql='TODO';
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{
			// Count on delivery date (use invoice date as delivery is unknown)
	        $sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.tva_taux as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
			$sql.= " d.date_start as date_start, d.date_end as date_end,";
	        $sql.= " f.facnumber as facnum, f.type, f.total_ttc as ftotal_ttc,";
			$sql.= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
			$sql.= " 0 as payment_id, 0 as payment_amount";
			$sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
	        $sql.= " ".MAIN_DB_PREFIX.$invoicedettable." as d" ;
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
	        $sql.= " WHERE ";
	        $sql.= " f.fk_statut in (1,2)";	// Validated or paid (partially or completely)
	        $sql.= " AND f.rowid = d.".$fk_facture;
	        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
	        if ($q) $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
	        if ($m) $sql.= " AND (date_format(f.datef,'%m') > ".($m-1)." AND date_format(f.datef,'%m') <= ".($m).")";
	        $sql.= " AND (d.product_type = 0";								// Limit to products
	        $sql.= " AND d.date_start is null AND d.date_end IS NULL)";		// enhance detection of service
	        $sql.= " ORDER BY d.rowid, d.".$fk_facture;
		}
    }
    else	// Option vat on delivery for goods (payments) and payments for services
    {
		if ($conf->global->MAIN_MODULE_ACCOUNTING)
		{
	        // \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stocke dans la table des payments.
	        // Seul le module compta expert peut resoudre ce probleme.
	        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
			$sql='TODO';
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{
			// Count on delivery date (use invoice date as delivery is unknown)
	        $sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.tva_taux as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
			$sql.= " d.date_start as date_start, d.date_end as date_end,";
	        $sql.= " f.facnumber as facnum, f.type, f.total_ttc as ftotal_ttc,";
			$sql.= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
			$sql.= " 0 as payment_id, 0 as payment_amount";
//			$sql.= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount";
	        $sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
//	        $sql.= " ".MAIN_DB_PREFIX.$paymentfacturetable." as pf,";
//	        $sql.= " ".MAIN_DB_PREFIX.$paymenttable." as pa,";
	        $sql.= " ".MAIN_DB_PREFIX.$invoicedettable." as d" ;
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
	        $sql.= " WHERE ";
			$sql.= " f.fk_statut in (1,2)";	// Validated or paid (partially or completely)
	        $sql.= " AND f.rowid = d.".$fk_facture;
//	        $sql.= " AND pf.".$fk_facture2." = f.rowid";
//	        $sql.= " AND pa.rowid = pf.".$fk_payment;
//	        $sql.= " AND pa.datep >= '".$y."0101000000' AND pa.datep <= '".$y."1231235959'";
//	        $sql.= " AND (date_format(pa.datep,'%m') > ".(($q-1)*3)." AND date_format(pa.datep,'%m') <= ".($q*3).")";
	        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
	        if ($q) $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
	        if ($m) $sql.= " AND (date_format(f.datef,'%m') > ".($m-1)." AND date_format(f.datef,'%m') <= ".($m).")";
	        $sql.= " AND (d.product_type = 0";								// Limit to products
	        $sql.= " AND d.date_start is null AND d.date_end IS NULL)";		// enhance detection of service
			$sql.= " ORDER BY d.rowid, d.".$fk_facture;
		}
    }

    //print $sql.'<br>';
	if (! $sql) return -1;
	if ($sql && $sql=='TODO') return -2;
	if ($sql && $sql!='TODO')
	{
		dol_syslog("Tax.lib.php::vat_by_quarter sql=".$sql);

		$resql = $db->query($sql);
	    if ($resql)
	    {
	    	$rate = -1;
			$oldrowid='';
	    	while($assoc = $db->fetch_array($resql))
	    	{
	    		if (! isset($list[$assoc['rate']]['totalht']))  $list[$assoc['rate']]['totalht']=0;
	    		if (! isset($list[$assoc['rate']]['vat']))      $list[$assoc['rate']]['vat']=0;

				if ($assoc['rowid'] != $oldrowid)		// Si rupture sur d.rowid
				{
					$oldrowid=$assoc['rowid'];
					$list[$assoc['rate']]['totalht']  += $assoc['total_ht'];
					$list[$assoc['rate']]['vat']      += $assoc['total_vat'];
				}
				$list[$assoc['rate']]['dtotal_ttc'][] = $assoc['total_ttc'];
				$list[$assoc['rate']]['dtype'][] = $assoc['dtype'];
				$list[$assoc['rate']]['ddate_start'][] = $db->jdate($assoc['date_start']);
				$list[$assoc['rate']]['ddate_end'][] = $db->jdate($assoc['date_end']);

				$list[$assoc['rate']]['facid'][] = $assoc['facid'];
				$list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
				$list[$assoc['rate']]['type'][] = $assoc['type'];
				$list[$assoc['rate']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
				$list[$assoc['rate']]['descr'][] = $assoc['descr'];

				$list[$assoc['rate']]['totalht_list'][] = $assoc['total_ht'];
				$list[$assoc['rate']]['vat_list'][] = $assoc['total_vat'];

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
	$sql='';
	if ($modetax == 1)	// Option vat on delivery for goods (payment) and debit invoice for services
    {
		if ($conf->global->MAIN_MODULE_ACCOUNTING)
		{
			// Count on invoice date
			// \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stocke dans la table des payments.
	        // Seul le module compta expert peut resoudre ce probleme.
	        // (Il faut quand un payment a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
			$sql='TODO';
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{
			// Count on invoice date
			$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.tva_taux as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
			$sql.= " d.date_start as date_start, d.date_end as date_end,";
	        $sql.= " f.facnumber as facnum, f.type, f.total_ttc as ftotal_ttc,";
			$sql.= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
			$sql.= " 0 as payment_id, 0 as payment_amount";
	        $sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
	        $sql.= " ".MAIN_DB_PREFIX.$invoicedettable." as d" ;
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
	        $sql.= " WHERE ";
	        $sql.= " f.fk_statut in (1,2)";	// Validated or paid (partially or completely)
	        $sql.= " AND f.rowid = d.".$fk_facture;
	        $sql.= " AND f.datef >= '".$y."0101000000' AND f.datef <= '".$y."1231235959'";
	        if ($q) $sql.= " AND (date_format(f.datef,'%m') > ".(($q-1)*3)." AND date_format(f.datef,'%m') <= ".($q*3).")";
	        if ($m) $sql.= " AND (date_format(f.datef,'%m') > ".($m-1)." AND date_format(f.datef,'%m') <= ".($m).")";
	        $sql.= " AND (d.product_type = 1";								// Limit to services
	        $sql.= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)";		// enhance detection of service
	        $sql.= " ORDER BY d.rowid, d.".$fk_facture;
		}
    }
    else	// Option vat on delivery for goods (payments) and payments for services
    {
		if ($conf->global->MAIN_MODULE_ACCOUNTING)
		{
			// Count on payments date
			// \todo a ce jour on se sait pas la compter car le montant tva d'un payment
	        // n'est pas stocke dans la table des payments.
	        // Seul le module compta expert peut resoudre ce probleme.
	        // (Il faut quand un paiement a lieu, stocker en plus du montant du paiement le
	        // detail part tva et part ht).
			$sql='TODO';
		}
		if ($conf->global->MAIN_MODULE_COMPTABILITE)
		{
			// Count on payments date
			$sql = "SELECT d.rowid, d.product_type as dtype, d.".$fk_facture." as facid, d.tva_taux as rate, d.total_ht as total_ht, d.total_ttc as total_ttc, d.".$total_tva." as total_vat, d.description as descr,";
			$sql.= " d.date_start as date_start, d.date_end as date_end,";
	        $sql.= " f.facnumber as facnum, f.type, f.total_ttc as ftotal_ttc,";
			$sql.= " p.rowid as pid, p.ref as pref, p.fk_product_type as ptype,";
			$sql.= " pf.".$fk_payment." as payment_id, pf.amount as payment_amount";
	        $sql.= " FROM ".MAIN_DB_PREFIX.$invoicetable." as f,";
	        $sql.= " ".MAIN_DB_PREFIX.$paymentfacturetable." as pf,";
	        $sql.= " ".MAIN_DB_PREFIX.$paymenttable." as pa,";
	        $sql.= " ".MAIN_DB_PREFIX.$invoicedettable." as d";
			$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."product as p on d.fk_product = p.rowid";
	        $sql.= " WHERE ";
			$sql.= " f.fk_statut in (1,2)";	// Paid (partially or completely)
	        $sql.= " AND f.rowid = d.".$fk_facture;;
	        $sql.= " AND pf.".$fk_facture2." = f.rowid";
	        $sql.= " AND pa.rowid = pf.".$fk_payment;
	        $sql.= " AND pa.datep >= '".$y."0101000000' AND pa.datep <= '".$y."1231235959'";
	        if ($q) $sql.= " AND (date_format(pa.datep,'%m') > ".(($q-1)*3)." AND date_format(pa.datep,'%m') <= ".($q*3).")";
	        if ($m) $sql.= " AND (date_format(pa.datep,'%m') > ".($m-1)." AND date_format(pa.datep,'%m') <= ".($m).")";
	        $sql.= " AND (d.product_type = 1";								// Limit to services
	        $sql.= " OR d.date_start is NOT null OR d.date_end IS NOT NULL)";		// enhance detection of service
			$sql.= " ORDER BY d.rowid, d.".$fk_facture.", pf.rowid";
		}
    }

	if (! $sql) return -1;
	if ($sql && $sql=='TODO') return -2;
	if ($sql && $sql!='TODO')
	{
		dol_syslog("Tax.lib.php::vat_by_quarter sql=".$sql);
	    $resql = $db->query($sql);
	    if ($resql)
	    {
	    	$rate = -1;
			$oldrowid='';
	    	while($assoc = $db->fetch_array($resql))
	    	{
	    		if (! isset($list[$assoc['rate']]['totalht']))  $list[$assoc['rate']]['totalht']=0;
	    		if (! isset($list[$assoc['rate']]['vat']))      $list[$assoc['rate']]['vat']=0;

				if ($assoc['rowid'] != $oldrowid)		// Si rupture sur d.rowid
				{
					$oldrowid=$assoc['rowid'];
					$list[$assoc['rate']]['totalht']  += $assoc['total_ht'];
					$list[$assoc['rate']]['vat']      += $assoc['total_vat'];
				}
				$list[$assoc['rate']]['dtotal_ttc'][] = $assoc['total_ttc'];
				$list[$assoc['rate']]['dtype'][] = $assoc['dtype'];
				$list[$assoc['rate']]['ddate_start'][] = $db->jdate($assoc['date_start']);
				$list[$assoc['rate']]['ddate_end'][] = $db->jdate($assoc['date_end']);

				$list[$assoc['rate']]['facid'][] = $assoc['facid'];
				$list[$assoc['rate']]['facnum'][] = $assoc['facnum'];
				$list[$assoc['rate']]['type'][] = $assoc['type'];
				$list[$assoc['rate']]['ftotal_ttc'][] = $assoc['ftotal_ttc'];
				$list[$assoc['rate']]['descr'][] = $assoc['descr'];

				$list[$assoc['rate']]['totalht_list'][] = $assoc['total_ht'];
				$list[$assoc['rate']]['vat_list'][] = $assoc['total_vat'];

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

	return $list;
}

?>