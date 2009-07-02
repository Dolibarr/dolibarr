<?php
/* Copyright (C) 2006-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin <patrick.raguin@gmail.com>
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
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/lib/pdf.lib.php
 *	\brief      Set of functions used for PDF generation
 *	\ingroup    core
 *	\version    $Id$
 */


/**
 *   	\brief      Show header of page for PDF generation
 *   	\param      pdf     		Object PDF
 *      \param      outputlang		Object lang for output
 * 		\param		page_height
 */
function pdf_pagehead(&$pdf,$outputlangs,$page_height)
{
	global $conf;

	// Add a background image on document
	if (! empty($conf->global->MAIN_USE_BACKGROUND_ON_PDF))
	{
		$pdf->Image($conf->mycompany->dir_output.'/logos/'.$conf->global->MAIN_USE_BACKGROUND_ON_PDF, 0, 0, 0, $page_height);
	}
}


/**
 *   	\brief      Show bank informations for PDF generation
 */
function pdf_bank(&$pdf,$outputlangs,$curx,$cury,$account)
{
	$pdf->SetXY ($curx, $cury);
	$pdf->SetFont('Arial','B',8);
	$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
	$cury+=4;

	$usedetailedbban=$account->useDetailedBBAN();

	if ($usedetailedbban)
	{
		$pdf->SetFont('Arial','B',6);
		$pdf->line($curx+1, $cury, $curx+1, $cury+10 );
		$pdf->SetXY ($curx, $cury);
		$pdf->MultiCell(18, 3, $outputlangs->transnoentities("BankCode"), 0, 'C', 0);
		$pdf->line($curx+18, $cury, $curx+18, $cury+10 );
		$pdf->SetXY ($curx+18, $cury);
		$pdf->MultiCell(18, 3, $outputlangs->transnoentities("DeskCode"), 0, 'C', 0);
		$pdf->line($curx+36, $cury, $curx+36, $cury+10 );
		$pdf->SetXY ($curx+36, $cury);
		$pdf->MultiCell(24, 3, $outputlangs->transnoentities("BankAccountNumber"), 0, 'C', 0);
		$pdf->line($curx+60, $cury, $curx+60, $cury+10 );
		$pdf->SetXY ($curx+60, $cury);
		$pdf->MultiCell(13, 3, $outputlangs->transnoentities("BankAccountNumberKey"), 0, 'C', 0);
		$pdf->line($curx+73, $cury, $curx+73, $cury+10 );

		$pdf->SetFont('Arial','',8);
		$pdf->SetXY ($curx, $cury+6);
		$pdf->MultiCell(18, 3, $outputlangs->convToOutputCharset($account->code_banque), 0, 'C', 0);
		$pdf->SetXY ($curx+18, $cury+6);
		$pdf->MultiCell(18, 3, $outputlangs->convToOutputCharset($account->code_guichet), 0, 'C', 0);
		$pdf->SetXY ($curx+36, $cury+6);
		$pdf->MultiCell(24, 3, $outputlangs->convToOutputCharset($account->number), 0, 'C', 0);
		$pdf->SetXY ($curx+60, $cury+6);
		$pdf->MultiCell(13, 3, $outputlangs->convToOutputCharset($account->cle_rib), 0, 'C', 0);
	}
	else
	{
		$pdf->SetFont('Arial','B',6);
		$pdf->SetXY ($curx, $cury);
		$pdf->MultiCell(90, 3, $outputlangs->transnoentities("BankAccountNumber").': ' . $outputlangs->convToOutputCharset($account->number), 0, 'L', 0);
		$cury-=9;
	}

	$pdf->SetXY ($curx, $cury+12);
	$pdf->MultiCell(90, 3, $outputlangs->transnoentities("Residence").': ' . $outputlangs->convToOutputCharset($account->domiciliation), 0, 'L', 0);
	$pdf->SetXY ($curx, $cury+22);
	$pdf->MultiCell(90, 3, $outputlangs->transnoentities("IBANNumber").': ' . $outputlangs->convToOutputCharset($account->iban), 0, 'L', 0);
	$pdf->SetXY ($curx, $cury+25);
	$pdf->MultiCell(90, 3, $outputlangs->transnoentities("BICNumber").': ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);

	return $pdf->getY();
}


/**
 *   	\brief      Show footer of page for PDF generation
 *   	\param      pdf     		Object PDF
 *      \param      outputlang		Object lang for output
 * 		\param		paramfreetext	Constant name of free text
 * 		\param		fromcompany		Object company
 * 		\param		marge_basse
 * 		\param		marge_gauche
 * 		\param		page_hauteur
 */
function pdf_pagefoot(&$pdf,$outputlangs,$paramfreetext,$fromcompany,$marge_basse,$marge_gauche,$page_hauteur)
{
	global $conf;

	$outputlangs->load("dict");

	// Line of free text
	$ligne=(! empty($conf->global->$paramfreetext))?$outputlangs->convToOutputCharset($conf->global->$paramfreetext):"";

	// First line of company infos

	// Juridical status
	$ligne1="";
	if ($fromcompany->forme_juridique_code)
	{
		$ligne1.=($ligne1?" - ":"").$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
	}
	// Capital
	if ($fromcompany->capital)
	{
		$ligne1.=($ligne1?" - ":"").$outputlangs->transnoentities("CapitalOf",$fromcompany->capital)." ".$outputlangs->transnoentities("Currency".$conf->monnaie);
	}
	// Prof Id 1
	if ($fromcompany->profid1 && ($fromcompany->pays_code != 'FR' || ! $fromcompany->profid2))
	{
		$field=$outputlangs->transcountrynoentities("ProfId1",$fromcompany->pays_code);
		if (eregi('\((.*)\)',$field,$reg)) $field=$reg[1];
		$ligne1.=($ligne1?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->profid1);
	}
	// Prof Id 2
	if ($fromcompany->profid2)
	{
		$field=$outputlangs->transcountrynoentities("ProfId2",$fromcompany->pays_code);
		if (eregi('\((.*)\)',$field,$reg)) $field=$reg[1];
		$ligne1.=($ligne1?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->profid2);
	}

	// Second line of company infos
	$ligne2="";
	// Prof Id 3
	if ($fromcompany->profid3)
	{
		$field=$outputlangs->transcountrynoentities("ProfId3",$fromcompany->pays_code);
		if (eregi('\((.*)\)',$field,$reg)) $field=$reg[1];
		$ligne2.=($ligne2?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->profid3);
	}
	// Prof Id 4
	if ($fromcompany->profid4)
	{
		$field=$outputlangs->transcountrynoentities("ProfId4",$fromcompany->pays_code);
		if (eregi('\((.*)\)',$field,$reg)) $field=$reg[1];
		$ligne2.=($ligne2?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->profid4);
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '')
	{
		$ligne2.=($ligne2?" - ":"").$outputlangs->transnoentities("VATIntraShort").": ".$outputlangs->convToOutputCharset($fromcompany->tva_intra);
	}

	$pdf->SetFont('Arial','',7);
	$pdf->SetDrawColor(224,224,224);

	// On positionne le debut du bas de page selon nbre de lignes de ce bas de page
	$nbofligne=dol_nboflines_bis($ligne);
	//print 'e'.$ligne.'t'.dol_nboflines($ligne);exit;
	$posy=$marge_basse + ($nbofligne*3) + ($ligne1?3:0) + ($ligne2?3:0);

	if ($ligne)	// Free text
	{
		$pdf->SetXY($marge_gauche,-$posy);
		$pdf->MultiCell(20000, 3, $ligne, 0, 'L', 0);	// Use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
		$posy-=($nbofligne*3);	// 6 of ligne + 3 of MultiCell
	}

	$pdf->SetY(-$posy);
	$pdf->line($marge_gauche, $page_hauteur-$posy, 200, $page_hauteur-$posy);
	$posy--;

	if ($ligne1)
	{
		$pdf->SetXY($marge_gauche,-$posy);
		$pdf->MultiCell(200, 2, $ligne1, 0, 'C', 0);
	}

	if ($ligne2)
	{
		$posy-=3;
		$pdf->SetXY($marge_gauche,-$posy);
		$pdf->MultiCell(200, 2, $ligne2, 0, 'C', 0);
	}

	$pdf->SetXY(-20,-$posy);
	$pdf->MultiCell(11, 2, $pdf->PageNo().'/{nb}', 0, 'R', 0);
}


/**
 *   	\brief      Return line description translated in outputlangs and encoded in UTF8
 *		\param		line			    Line to format
 *    \param    outputlang		Object lang for output
 *    \param    showref       Show reference
 */
function pdf_getlinedesc($line,$outputlangs,$showref=1)
{
	global $db, $conf, $langs;

	$label=$line->label;
	$desc=$line->desc;
	$note=$line->note;
	$idprod=$line->fk_product;

	if (empty($label))  $label=$line->libelle;
	if (empty($desc))   $desc=$line->description;
	if (empty($idprod)) $idprod=$line->produit_id;

	$prodser = new Product($db);
	if ($idprod)
	{
		$prodser->fetch($idprod);
		// If a predefined product and multilang and on other lang, we renamed label with label translated
		if ($conf->global->MAIN_MULTILANGS && ($outputlangs->defaultlang != $langs->defaultlang))
		{
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["libelle"]))     $label=$prodser->multilangs[$outputlangs->defaultlang]["libelle"];
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["description"])) $desc=$prodser->multilangs[$outputlangs->defaultlang]["description"];
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["note"]))        $note=$prodser->multilangs[$outputlangs->defaultlang]["note"];
		}
	}


	// Description short of product line
	$libelleproduitservice=$label;

	// Description long of product line
	if ($desc && ($desc != $label))
	{
		if ($libelleproduitservice) $libelleproduitservice.="\n";

		if ($desc == '(CREDIT_NOTE)' && $line->fk_remise_except)
		{
			$discount=new DiscountAbsolute($db);
			$discount->fetch($line->fk_remise_except);
			$libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromCreditNote",$discount->ref_facture_source);
		}
		else
		{
			if ($idprod)
			{
				$libelleproduitservice.=$desc;
			}
			else
			{
				$libelleproduitservice.=$desc;
			}
		}
	}

	// Si ligne associee a un code produit
	if ($idprod)
	{
		// On ajoute la ref
		if ($prodser->ref)
		{
			$prefix_prodserv = "";
			$ref_prodserv = "";
			if ($conf->global->PRODUCT_ADD_TYPE_IN_DOCUMENTS)	// In standard mode, we do not show this
			{
				if($prodser->isservice())
				{
					$prefix_prodserv = $outputlangs->transnoentitiesnoconv("Service")." ";
				}
				else
				{
					$prefix_prodserv = $outputlangs->transnoentitiesnoconv("Product")." ";
				}
			}

			if ($showref) $ref_prodserv = $prodser->ref." - ";

			$libelleproduitservice=$prefix_prodserv.$ref_prodserv.$libelleproduitservice;
		}
	}
	$libelleproduitservice=dol_htmlentitiesbr($libelleproduitservice,1);

	if ($line->date_start || $line->date_end)
	{
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
		$libelleproduitservice.="<br>".dol_htmlentitiesbr($period,1);
		//print $libelleproduitservice;
	}
	return $libelleproduitservice;
}
?>