<?php
/* Copyright (C) 2006-2011	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2006		Rodolphe Quiedeville	<rodolphe@quiedeville.org>
 * Copyright (C) 2007		Patrick Raguin			<patrick.raguin@gmail.com>
 * Copyright (C) 2010-2012	Regis Houssin			<regis.houssin@inodbox.com>
 * Copyright (C) 2010		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel		<christophe.battarel@altairis.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/doc.lib.php
 *	\brief      Set of functions used for ODT generation
 *	\ingroup    core
 */


/**
 *  Return line description translated in outputlangs and encoded into UTF8
 *
 *  @param  Line		$line                Current line number (0 = first line, 1 = second line, ...)
 *  @param  Translate	$outputlangs         Object langs for output
 *  @param  int			$hideref             Hide reference
 *  @param  int			$hidedesc            Hide description
 *  @param  int			$issupplierline      Is it a line for a supplier object ?
 *  @return string       				     String with line
 */
function doc_getlinedesc($line,$outputlangs,$hideref=0,$hidedesc=0,$issupplierline=0)
{
	global $db, $conf, $langs;

	$idprod=$line->fk_product;
	$label=(! empty($line->label)?$line->label:(! empty($line->libelle)?$line->libelle:''));
	$desc=(! empty($line->desc)?$line->desc:(! empty($line->description)?$line->description:''));
	$ref_supplier=(! empty($line->ref_supplier)?$line->ref_supplier:(! empty($line->ref_fourn)?$line->ref_fourn:''));    // TODO Not yet saved for supplier invoices, only supplier orders
	$note=(! empty($line->note)?$line->note:'');

	if ($issupplierline) $prodser = new ProductFournisseur($db);
	else $prodser = new Product($db);

	if ($idprod)
	{
		$prodser->fetch($idprod);
		// If a predefined product and multilang and on other lang, we renamed label with label translated
		if (! empty($conf->global->MAIN_MULTILANGS) && ($outputlangs->defaultlang != $langs->defaultlang))
		{
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["label"]) && $label == $prodser->label)     $label=$prodser->multilangs[$outputlangs->defaultlang]["label"];
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["description"]) && $desc == $prodser->description) $desc=$prodser->multilangs[$outputlangs->defaultlang]["description"];
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["note"]) && $note == $prodser->note)        $note=$prodser->multilangs[$outputlangs->defaultlang]["note"];
		}
	}

	// Description short of product line
	$libelleproduitservice=$label;

	// Description long of product line
	if ($desc && ($desc != $label))
	{
		if ($desc == '(CREDIT_NOTE)' && $line->fk_remise_except)
		{
			$discount=new DiscountAbsolute($db);
			$discount->fetch($line->fk_remise_except);
			$sourceref=!empty($discount->discount_type)?$discount->ref_invoive_supplier_source:$discount->ref_facture_source;
			$libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromCreditNote",$sourceref);
		}
		elseif ($desc == '(DEPOSIT)' && $line->fk_remise_except)
		{
		    $discount=new DiscountAbsolute($db);
		    $discount->fetch($line->fk_remise_except);
		    $sourceref=!empty($discount->discount_type)?$discount->ref_invoive_supplier_source:$discount->ref_facture_source;
		    $libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromDeposit",$sourceref);
		    // Add date of deposit
		    if (! empty($conf->global->INVOICE_ADD_DEPOSIT_DATE)) $libelleproduitservice.=' ('.dol_print_date($discount->datec,'day','',$outputlangs).')';
		}
		elseif ($desc == '(EXCESS RECEIVED)' && $line->fk_remise_except)
		{
			$discount=new DiscountAbsolute($db);
			$discount->fetch($line->fk_remise_except);
			$libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromExcessReceived",$discount->ref_facture_source);
		}
		elseif ($desc == '(EXCESS PAID)' && $line->fk_remise_except)
		{
			$discount=new DiscountAbsolute($db);
			$discount->fetch($line->fk_remise_except);
			$libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromExcessPaid",$discount->ref_invoice_supplier_source);
		}
		else
		{
			if ($idprod)
			{
				if (empty($hidedesc)) $libelleproduitservice=dol_concatdesc($libelleproduitservice, $desc);
			}
			else
			{
				$libelleproduitservice=dol_concatdesc($libelleproduitservice, $desc);
			}
		}
	}

	// If line linked to a product
	if ($idprod)
	{
		// On ajoute la ref
		if ($prodser->ref)
		{
			$prefix_prodserv = "";
			$ref_prodserv = "";
			if (! empty($conf->global->PRODUCT_ADD_TYPE_IN_DOCUMENTS))   // In standard mode, we do not show this
			{
				if ($prodser->isService())
				{
					$prefix_prodserv = $outputlangs->transnoentitiesnoconv("Service")." ";
				}
				else
				{
					$prefix_prodserv = $outputlangs->transnoentitiesnoconv("Product")." ";
				}
			}

			if (empty($hideref))
			{
				if ($issupplierline) $ref_prodserv = $prodser->ref.' ('.$outputlangs->trans("SupplierRef").' '.$ref_supplier.')';   // Show local ref and supplier ref
				else $ref_prodserv = $prodser->ref; // Show local ref only

				$ref_prodserv .= " - ";
			}

			$libelleproduitservice=$prefix_prodserv.$ref_prodserv.$libelleproduitservice;
		}
	}

	if (! empty($line->date_start) || ! empty($line->date_end))
	{
		$format='day';
		// Show duration if exists
		if ($line->date_start && $line->date_end)
		{
			$period='('.$outputlangs->transnoentitiesnoconv('DateFromTo',dol_print_date($line->date_start, $format, false, $outputlangs),dol_print_date($line->date_end, $format, false, $outputlangs)).')';
		}
		if ($line->date_start && ! $line->date_end)
		{
			$period='('.$outputlangs->transnoentitiesnoconv('DateFrom',dol_print_date($line->date_start, $format, false, $outputlangs)).')';
		}
		if (! $line->date_start && $line->date_end)
		{
			$period='('.$outputlangs->transnoentitiesnoconv('DateUntil',dol_print_date($line->date_end, $format, false, $outputlangs)).')';
		}
		//print '>'.$outputlangs->charset_output.','.$period;
		$libelleproduitservice=dol_concatdesc($libelleproduitservice, $period);
		//print $libelleproduitservice;
	}

	return $libelleproduitservice;
}

