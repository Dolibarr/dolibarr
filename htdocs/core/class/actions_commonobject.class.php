<?php
/* Copyright (C) 2010 Regis Houssin  <regis@dolibarr.fr>
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
 *	\file       htdocs/core/class/actions_commonobject.class.php
 *	\ingroup    core
 *	\brief      Fichier de la classe mere des classes metiers (facture, contrat, propal, commande, etc...)
 *	\version    $Id$
 */


/**
 *	\class 		ActionsCommonObject
 *	\brief 		Classe mere pour heritage des classes metiers
 */

class ActionsCommonObject
{
	var $db;

	// Instantiate hook classe of thirdparty module
	var $hooks=array();

	/**
	 *    Constructeur de la classe
	 *    @param	DB		Handler acces base de donnees
	 */
	function AnctionsCommonObject($DB)
	{
		$this->db = $DB;
	}
	
	/**
	 * 	Return HTML table with origin title list
	 */
	function printOriginTitleList()
	{
		global $langs;

		print '<tr class="liste_titre">';
		print '<td>'.$langs->trans('Ref').'</td>';
		print '<td>'.$langs->trans('Description').'</td>';
		print '<td align="right">'.$langs->trans('VAT').'</td>';
		print '<td align="right">'.$langs->trans('PriceUHT').'</td>';
		print '<td align="right">'.$langs->trans('Qty').'</td>';
		print '<td align="right">'.$langs->trans('ReductionShort').'</td></tr>';
	}
	
	/**
	 * 	Return HTML with list of origin lines
	 */
	function printOriginLinesList($object)
	{
		$num = count($this->object->lines);
		$var = true;
		$i	 = 0;

		foreach ($this->object->lines as $line)
		{
			$var=!$var;

			if ($line->product_type == 9 && ! empty($line->special_code))
			{
				$object->hooks[$line->special_code]->printOriginObjectLine($line,$i);
			}
			else
			{
				$this->printOriginLine($line,$var);
			}

			$i++;
		}
	}

	/**
	 * 	Return HTML with origin line
	 * 	@param		element		Element type
	 * 	@param		id			Element id
	 */
	function printOriginLine($line,$var)
	{
		global $langs,$bc;
		
		$var=!$var;

		$date_start=$line->date_debut_prevue;
		if ($line->date_debut_reel) $date_start=$line->date_debut_reel;
		$date_end=$line->date_fin_prevue;
		if ($line->date_fin_reel) $date_end=$line->date_fin_reel;
		
		print '<tr '.$bc[$var].'><td>';
		if (($line->info_bits & 2) == 2)
		{
			print '<a href="'.DOL_URL_ROOT.'/comm/remx.php?id='.$this->socid.'">';
			print img_object($langs->trans("ShowReduc"),'reduc').' '.$langs->trans("Discount");
			print '</a>';
		}
		else if ($line->fk_product)
		{
			print '<a href="'.DOL_URL_ROOT.'/product/fiche.php?id='.$line->fk_product.'">';
			print ($line->fk_product_type == 1 ? img_object($langs->trans(''),'service') : img_object($langs->trans(''),'product'));
			print ' '.$line->ref.'</a>';
			print $line->label?' - '.$line->label:'';
			// Dates
			if ($date_start || $date_end)
			{
				print_date_range($date_start,$date_end);
			}
		}
		else
		{
			print ($line->product_type == -1 ? '&nbsp;' : ($line->product_type == 1 ? img_object($langs->trans(''),'service') : img_object($langs->trans(''),'product')));
			// Dates
			if ($date_start || $date_end)
			{
				print_date_range($date_start,$date_end);
			}
		}
		print "</td>\n";
		print '<td>';
		if ($line->desc)
		{
			if ($line->desc == '(CREDIT_NOTE)')
			{
				$discount=new DiscountAbsolute($db);
				$discount->fetch($line->fk_remise_except);
				print $langs->transnoentities("DiscountFromCreditNote",$discount->getNomUrl(0));
			}
			elseif ($line->desc == '(DEPOSIT)')
			{
				$discount=new DiscountAbsolute($db);
				$discount->fetch($line->fk_remise_except);
				print $langs->transnoentities("DiscountFromDeposit",$discount->getNomUrl(0));
			}
			else
			{
				print dol_trunc($line->desc,60);
			}
		}
		else
		{
			print '&nbsp;';
		}
		print '</td>';
		print '<td align="right">'.vatrate($line->tva_tx).'%</td>';
		print '<td align="right">'.price($line->subprice).'</td>';
		print '<td align="right">';
		print (($line->info_bits & 2) != 2) ? $line->qty : '&nbsp;';
		print '</td>';
		print '<td align="right">';
		print (($line->info_bits & 2) != 2) ? $line->remise_percent.'%' : '&nbsp;';
		print '</td>';
		print '</tr>';
	}
	
}

?>
