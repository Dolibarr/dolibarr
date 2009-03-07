<?PHP
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *      \file       	cron/functions_cron.lib.php
 *      \ingroup    	core
 *      \brief      	Functions for miscellanous cron tasks
 *		\version		$Id$
 */



/**
 * 		\deprecated		Function no more used
 */
function batch_fournisseur_updateturnover($year)
{
	global $conf, $user, $db, $langs;

	$fournisseurs = array();
	$fournisseurs_ca_achat = array();
	$products = array();
	$real_products = array();


	$sql  = "SELECT fk_soc, date_format(datef,'%Y'),sum(total_ht) ";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture_fourn";
	$sql .= " GROUP BY fk_soc, date_format(datef,'%Y') ";
	$resql = $db->query($sql) ;

	if ($resql)
	{
		while ($row = $db->fetch_row($resql))
		{
			$fournisseurs_ca_achat[$row[0]][$row[1]] = $row[2];
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
		return -1;
	}

	/*
	 *
	 *
	 */
	$sql  = "SELECT fk_product,fk_soc ";
	$sql .= " FROM ".MAIN_DB_PREFIX."product_fournisseur";

	$resql = $db->query($sql) ;

	if ($resql)
	{
		while ($row = $db->fetch_row($resql))
		{
			$products[$row[0]] = $row[1];
		}
		$db->free($resql);
	}
	else
	{
		dol_print_error($db);
		return -1;
	}

	/*
	 * Recuperation des id produits en lieu et place
	 * des id de sous-produits
	 */
	foreach($products as $key => $value)
	{
		$sql = "SELECT fk_product ";
		$sql.= " FROM ".MAIN_DB_PREFIX."product_subproduct";
		$sql.= " WHERE fk_product_subproduct ='".$key."';";
			
		$resql = $db->query($sql) ;

		if ($resql)
		{
			if ($db->num_rows($resql) > 0)
			{
				$row = $db->fetch_row($resql);
				$real_products[$row[0]] = $value;
			}
			else
			{
				$real_products[$key] = $value;
			}
			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
			return -1;
		}
	}

	/*
	 * Recuperation des id produits en lieu et place
	 * des id de sous-produits
	 */
	$ca_products = array();
	$ca_fourns = array();
	foreach($real_products as $key => $value)
	{
		$sql = "SELECT sum(fd.total_ht) ";
		$sql.= " FROM ".MAIN_DB_PREFIX."facturedet as fd, ".MAIN_DB_PREFIX."facture as f";
		$sql.= " WHERE fk_product ='".$key."'";
		$sql.= " AND f.rowid = fd.fk_facture";
		$sql .=" AND date_format(f.datef,'%Y') = '".$year."';";
			
		$resql = $db->query($sql) ;

		if ($resql)
		{

			$row = $db->fetch_row($resql);
			$ca_products[$key] = $row[0];
			$ca_fourns[$value] += $row[0];

			$db->free($resql);
		}
		else
		{
			dol_print_error($db);
			return -1;
		}
	}
	/*
	 * Mets a jour la table fournisseur
	 *
	 */
	foreach($ca_fourns as $key => $value)
	{
		$sqld = "DELETE FROM ".MAIN_DB_PREFIX."fournisseur_ca";
		$sqld .= " WHERE year = ".$year." AND fk_societe=".$key;
		$resqld = $db->query($sqld);
		if (! $resqld)
		{
			dol_print_error($db);
			return -1;
		}

		$sqli = "INSERT INTO ".MAIN_DB_PREFIX."fournisseur_ca";
		$sqli .= " VALUES ($key,".$db->idate(mktime()).",$year,'".price2num($value)."'";
		$sqli.=  ",'". $fournisseurs_ca_achat[$key][$year] ."');";
		$resqli = $db->query($sqli);
		if (! $resqli)
		{
			dol_print_error($db);
			return -1;
		}
	}

	/*
	 * Mets a jour la table produit
	 */
	foreach($ca_products as $key => $value)
	{
		$sqld = "DELETE FROM ".MAIN_DB_PREFIX."product_ca";
		$sqld .= " WHERE year = ".$year." AND fk_product=".$key;
		$resqld = $db->query($sqld);
		if (! $resqld)
		{
			dol_print_error($db);
			return -1;
		}

		$sqli = "INSERT INTO ".MAIN_DB_PREFIX."product_ca(fk_product, date_calcul, year, ca_genere)";
		$sqli .= " VALUES (".$key.",".$db->idate(mktime()).",".$year.",'".price2num($value)."')";
		$resqli = $db->query($sqli);
		if (! $resqli)
		{
			dol_print_error($db);
			return -1;
		}
	}

	return 1;
}



/**
 * 		\brief			Update table facture_stats
 * 		\deprecated		Function no more used
 */
function batch_fournisseur_statsinvoice()
{
	global $conf, $user, $db, $langs;

	$now=gmmktime();

	$sql = "SELECT paye, count(*)";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture";
	$sql .= " GROUP BY paye";

	$resql = $db->query($sql);

	if ($resql)
	{
		while ($row = $db->fetch_row($resql))
		{
			$sqli = "INSERT INTO ".MAIN_DB_PREFIX."facture_stats";
			$sqli .= " VALUES (".$db->idate(mktime()).",".$db->idate($now).",'paye $row[0]',$row[1])";

			$resqli = $db->query($sqli);
		}
		$db->free($resql);
	}

	$sql = "SELECT paye, sum(total)";
	$sql .= " FROM ".MAIN_DB_PREFIX."facture";
	$sql .= " GROUP BY paye";

	$resql = $db->query($sql);

	if ($resql)
	{
		while ($row = $db->fetch_row($resql))
		{
			$sqli = "INSERT INTO ".MAIN_DB_PREFIX."facture_stats";
			$sqli .= " VALUES (".$db->idate(mktime()).",".$db->idate($now).",'total $row[0]','$row[1]')";

			$resqli = $db->query($sqli);
		}
		$db->free($resql);
	}

	return 1;
}
?>
