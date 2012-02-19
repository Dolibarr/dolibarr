<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2010-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/lib/pdf.lib.php
 *	\brief      Set of functions used for PDF generation
 *	\ingroup    core
 */


/**
 *      Return array with format properties of default PDF format
 *
 *      @return     array		Array('width'=>w,'height'=>h,'unit'=>u);
 */
function pdf_getFormat()
{
	global $conf,$db;

	// Default value if setup was not done and/or entry into c_paper_format not defined
	$width=210; $height=297; $unit='mm';

	if (empty($conf->global->MAIN_PDF_FORMAT))
	{
		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$pdfformat=dol_getDefaultFormat();
	}
	else $pdfformat=$conf->global->MAIN_PDF_FORMAT;

	$sql="SELECT code, label, width, height, unit FROM ".MAIN_DB_PREFIX."c_paper_format";
	$sql.=" WHERE code = '".$pdfformat."'";
	$resql=$db->query($sql);
	if ($resql)
	{
		$obj=$db->fetch_object($resql);
		if ($obj)
		{
			$width=(int) $obj->width;
			$height=(int) $obj->height;
			$unit=$obj->unit;
		}
	}

	//print "pdfformat=".$pdfformat." width=".$width." height=".$height." unit=".$unit;
	return array('width'=>$width,'height'=>$height,'unit'=>$unit);
}

/**
 *      Return a PDF instance object. We create a FPDI instance that instanciate TCPDF.
 *
 *      @param	string		$format         Array(width,height). Keep empty to use default setup.
 *      @param	string		$metric         Unit of format ('mm')
 *      @param  string		$pagetype       'P' or 'l'
 *      @return TPDF							PDF object
 */
function pdf_getInstance($format='',$metric='mm',$pagetype='P')
{
	global $conf;

	require_once(TCPDF_PATH.'tcpdf.php');
	// We need to instantiate fpdi object (instead of tcpdf) to use merging features. But we can disable it.
	if (empty($conf->global->MAIN_DISABLE_FPDI)) require_once(FPDI_PATH.'fpdi.php');

	//$arrayformat=pdf_getFormat();
	//$format=array($arrayformat['width'],$arrayformat['height']);
	//$metric=$arrayformat['unit'];

	// Protection et encryption du pdf
	if ($conf->global->PDF_SECURITY_ENCRYPTION)
	{
		/* Permission supported by TCPDF
		 - print : Print the document;
		 - modify : Modify the contents of the document by operations other than those controlled by 'fill-forms', 'extract' and 'assemble';
		 - copy : Copy or otherwise extract text and graphics from the document;
		 - annot-forms : Add or modify text annotations, fill in interactive form fields, and, if 'modify' is also set, create or modify interactive form fields (including signature fields);
		 - fill-forms : Fill in existing interactive form fields (including signature fields), even if 'annot-forms' is not specified;
		 - extract : Extract text and graphics (in support of accessibility to users with disabilities or for other purposes);
		 - assemble : Assemble the document (insert, rotate, or delete pages and create bookmarks or thumbnail images), even if 'modify' is not set;
		 - print-high : Print the document to a representation from which a faithful digital copy of the PDF content could be generated. When this is not set, printing is limited to a low-level representation of the appearance, possibly of degraded quality.
		 - owner : (inverted logic - only for public-key) when set permits change of encryption and enables all other permissions.
		 */
		if (! empty($conf->global->MAIN_USE_FPDF))
		{
			require_once(FPDI_PATH.'fpdi_protection.php');
			$pdf = new FPDI_Protection($pagetype,$metric,$format);
			// For FPDF, we specify permission we want to open
			$pdfrights = array('print');
		}
		else
		{
			if (class_exists('FPDI')) $pdf = new FPDI($pagetype,$metric,$format);
			else $pdf = new TCPDF($pagetype,$metric,$format);
			// For TCPDF, we specify permission we want to block
			$pdfrights = array('modify','copy');
		}
		$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
		$pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
		$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
	}
	else
	{
		if (class_exists('FPDI')) $pdf = new FPDI($pagetype,$metric,$format);
		else $pdf = new TCPDF($pagetype,$metric,$format);
	}
	return $pdf;
}

/**
 *      Return font name to use for PDF generation
 *
 *      @param	Translate	$outputlangs    Output langs object
 *      @return string          			Name of font to use
 */
function pdf_getPDFFont($outputlangs)
{
	$font='Helvetica'; // By default, for FPDI or ISO language on TCPDF
	if (class_exists('TCPDF'))  // If TCPDF on, we can use an UTF8 one like DejaVuSans if required (slower)
	{
		if ($outputlangs->trans('FONTFORPDF')!='FONTFORPDF')
		{
			$font=$outputlangs->trans('FONTFORPDF');
		}
	}
	return $font;
}

/**
 *      Return font size to use for PDF generation
 *
 *      @param	Translate	$outputlangs     Output langs object
 *      @return int				             Size of font to use
 */
function pdf_getPDFFontSize($outputlangs)
{
	$size=10;                   // By default, for FPDI or ISO language on TCPDF
	if (class_exists('TCPDF'))  // If TCPDF on, we can use an UTF8 one like DejaVuSans if required (slower)
	{
		if ($outputlangs->trans('FONTSIZEFORPDF')!='FONTSIZEFORPDF')
		{
			$size = (int) $outputlangs->trans('FONTSIZEFORPDF');
		}
	}
	return $size;
}


/**
 *   	Return a string with full address formated
 *
 * 		@param	Translate	$outputlangs		Output langs object
 *   	@param  Societe		$sourcecompany		Source company object
 *   	@param  Societe		$targetcompany		Target company object
 *      @param  Contact		$targetcontact		Target contact object
 * 		@param	int			$usecontact			Use contact instead of company
 * 		@param	int			$mode				Address type
 * 		@param	Societe		$deliverycompany	Delivery company object
 * 		@return	string							String with full address
 */
function pdf_build_address($outputlangs,$sourcecompany,$targetcompany='',$targetcontact='',$usecontact=0,$mode='source',$deliverycompany='')
{
	global $conf;

	$stringaddress = '';

	if ($mode == 'source' && ! is_object($sourcecompany)) return -1;
	if ($mode == 'target' && ! is_object($targetcompany)) return -1;
	if ($mode == 'delivery' && ! is_object($deliverycompany)) return -1;

	if ($sourcecompany->state_id && empty($sourcecompany->departement)) $sourcecompany->departement=getState($sourcecompany->state_id);
	if ($targetcompany->state_id && empty($targetcompany->departement)) $targetcompany->departement=getState($targetcompany->state_id);

	if ($mode == 'source')
	{
		$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($sourcecompany))."\n";

		// Tel
		if ($sourcecompany->tel) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($sourcecompany->tel);
		// Fax
		if ($sourcecompany->fax) $stringaddress .= ($stringaddress ? ($sourcecompany->tel ? " - " : "\n") : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
		// EMail
		if ($sourcecompany->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
		// Web
		if ($sourcecompany->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
	}

	if ($mode == 'target')
	{
		if ($usecontact)
		{
			$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs,1));
			$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcontact))."\n";
			// Country
			if ($targetcontact->country_code && $targetcontact->country_code != $sourcecompany->pays_code) $stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->pays_code))."\n";
		}
		else
		{
			$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany))."\n";
			// Country
			if ($targetcompany->country_code && $targetcompany->country_code != $sourcecompany->pays_code) $stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->pays_code))."\n";
		}

		// Intra VAT
		if ($targetcompany->tva_intra) $stringaddress.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);

		// Professionnal Ids
		if ($conf->global->MAIN_PROFID1_IN_ADDRESS)
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId1",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
		}
		if ($conf->global->MAIN_PROFID2_IN_ADDRESS)
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId2",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
		}
		if ($conf->global->MAIN_PROFID3_IN_ADDRESS)
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId3",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
		}
		if ($conf->global->MAIN_PROFID4_IN_ADDRESS)
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId4",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof4);
		}
	}

	if ($mode == 'delivery')	// for a delivery address (address + phone/fax)
	{
		$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($deliverycompany))."\n";

		// Tel
		if ($deliverycompany->phone) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($deliverycompany->phone);
		// Fax
		if ($deliverycompany->fax) $stringaddress .= ($stringaddress ? ($deliverycompany->phone ? " - " : "\n") : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($deliverycompany->fax);
	}

	return $stringaddress;
}


/**
 *   	Show header of page for PDF generation
 *
 *   	@param      PDF			&$pdf     		Object PDF
 *      @param      Translate	$outputlangs	Object lang for output
 * 		@param		int			$page_height	Height of page
 *      @return	void
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
 *      Add a draft watermark on PDF files
 *
 *      @param	PDF      	&$pdf           Object PDF
 *      @param  Translate	$outputlangs	Object lang
 *      @param  int		    $h		        Height of PDF
 *      @param  int		    $w		        Width of PDF
 *      @param  string	    $unit           Unit of height (mmn, pt, ...)
 *      @param  string		$text           Text to show
 *      @return	void
 */
function pdf_watermark(&$pdf, $outputlangs, $h, $w, $unit, $text)
{
	// Print Draft Watermark
	if ($unit=='pt') $k=1;
	elseif ($unit=='mm') $k=72/25.4;
	elseif ($unit=='cm') $k=72/2.54;
	elseif ($unit=='in') $k=72;

	$watermark_angle=atan($h/$w);
	$watermark_x=5;
	$watermark_y=$h-25; //Set to $this->page_hauteur-50 or less if problems
	$watermark_width=$h;
	$pdf->SetFont('','B',50);
	$pdf->SetTextColor(255,192,203);
	//rotate
	$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$k,($h-$watermark_y)*$k,-$watermark_x*$k,-($h-$watermark_y)*$k));
	//print watermark
	$pdf->SetXY($watermark_x,$watermark_y);
	$pdf->Cell($watermark_width,25,$outputlangs->convToOutputCharset($text),0,2,"C",0);
	//antirotate
	$pdf->_out('Q');
}


/**
 *   	Show bank informations for PDF generation
 *
 *      @param	PDF			&$pdf            Object PDF
 *      @param  Translate	$outputlangs     Object lang
 *      @param  int			$curx            X
 *      @param  int			$cury            Y
 *      @param  Account		$account         Bank account object
 *      @param  int			$onlynumber      Output only number
 *      @return	void
 */
function pdf_bank(&$pdf,$outputlangs,$curx,$cury,$account,$onlynumber=0)
{
	global $mysoc, $conf;

	$pdf->SetXY($curx, $cury);

	if (empty($onlynumber))
	{
		$pdf->SetFont('','B',8);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
		$cury+=4;
	}

	$outputlangs->load("banks");

	// Get format of bank account according to its country
	$usedetailedbban=$account->useDetailedBBAN();

	//$onlynumber=0; $usedetailedbban=0; // For tests
	if ($usedetailedbban)
	{
		$savcurx=$curx;

		if (empty($onlynumber))
		{
			$pdf->SetFont('','',6);
			$pdf->SetXY($curx, $cury);
			$pdf->MultiCell(90, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
			$cury+=3;
		}

		if (empty($onlynumber)) $pdf->line($curx+1, $cury+1, $curx+1, $cury+8);

		if ($usedetailedbban == 1)
		{
			$fieldstoshow=array('bank','desk','number','key');
			if ($conf->global->BANK_SHOW_ORDER_OPTION==1) $fieldstoshow=array('bank','desk','key','number');
		}
		else if ($usedetailedbban == 2)
		{
			$fieldstoshow=array('bank','number');
		}
		else dol_print_error('','Value returned by function useDetailedBBAN not managed');

		foreach ($fieldstoshow as $val)
		{
			if ($val == 'bank')
			{
				// Bank code
				$tmplength=18;
				$pdf->SetXY($curx, $cury+5);
				$pdf->SetFont('','',8);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_banque), 0, 'C', 0);
				$pdf->SetXY($curx, $cury+1);
				$curx+=$tmplength;
				$pdf->SetFont('','B',6);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankCode"), 0, 'C', 0);
				if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
			}
			if ($val == 'desk')
			{
				// Desk
				$tmplength=18;
				$pdf->SetXY($curx, $cury+5);
				$pdf->SetFont('','',8);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_guichet), 0, 'C', 0);
				$pdf->SetXY($curx, $cury+1);
				$curx+=$tmplength;
				$pdf->SetFont('','B',6);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("DeskCode"), 0, 'C', 0);
				if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
			}
			if ($val == 'number')
			{
				// Number
				$tmplength=24;
				$pdf->SetXY($curx, $cury+5);
				$pdf->SetFont('','',8);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->number), 0, 'C', 0);
				$pdf->SetXY($curx, $cury+1);
				$curx+=$tmplength;
				$pdf->SetFont('','B',6);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumber"), 0, 'C', 0);
				if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
			}
			if ($val == 'key')
			{
				// Key
				$tmplength=13;
				$pdf->SetXY($curx, $cury+5);
				$pdf->SetFont('','',8);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->cle_rib), 0, 'C', 0);
				$pdf->SetXY($curx, $cury+1);
				$curx+=$tmplength;
				$pdf->SetFont('','B',6);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumberKey"), 0, 'C', 0);
				if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
			}
		}

		$curx=$savcurx;
		$cury+=10;
	}
	else
	{
		$pdf->SetFont('','B',6);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(90, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
		$cury+=3;

		$pdf->SetFont('','B',6);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(90, 3, $outputlangs->transnoentities("BankAccountNumber").': ' . $outputlangs->convToOutputCharset($account->number), 0, 'L', 0);
		$cury+=3;
	}

	// Use correct name of bank id according to country
	$ibankey="IBANNumber";
	$bickey="BICNumber";
	if ($account->getCountryCode() == 'IN') $ibankey="IFSC";
	if ($account->getCountryCode() == 'IN') $bickey="SWIFT";

	$pdf->SetFont('','',6);

	if (empty($onlynumber) && ! empty($account->domiciliation))
	{
		$pdf->SetXY($curx, $cury);
		$val=$outputlangs->transnoentities("Residence").': ' . $outputlangs->convToOutputCharset($account->domiciliation);
		$pdf->MultiCell(90, 3, $val, 0, 'L', 0);
		$nboflines=dol_nboflines_bis($val,120);
		//print $nboflines;exit;
		$cury+=($nboflines*2)+2;
	}
	else if (! $usedetailedbban) $cury+=1;

	$pdf->SetXY($curx, $cury);
	$pdf->MultiCell(90, 3, $outputlangs->transnoentities($ibankey).': ' . $outputlangs->convToOutputCharset($account->iban), 0, 'L', 0);
	$pdf->SetXY($curx, $cury+3);
	$pdf->MultiCell(90, 3, $outputlangs->transnoentities($bickey).': ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);

	return $pdf->getY();
}


/**
 *  Show footer of page for PDF generation
 *
 *	@param	PDF			&$pdf     		The PDF factory
 *  @param  Translate	$outputlangs	Object lang for output
 * 	@param	string		$paramfreetext	Constant name of free text
 * 	@param	Societe		$fromcompany	Object company
 * 	@param	int			$marge_basse	Margin bottom
 * 	@param	int			$marge_gauche	Margin left
 * 	@param	int			$page_hauteur	Page height
 * 	@param	Object		$object			Object shown in PDF
 * 	@param	int			$showdetails	Show company details
 * 	@return	void
 */
function pdf_pagefoot(&$pdf,$outputlangs,$paramfreetext,$fromcompany,$marge_basse,$marge_gauche,$page_hauteur,$object,$showdetails=0)
{
	global $conf,$user;

	$outputlangs->load("dict");
	$line='';

	// Line of free text
	if (! empty($conf->global->$paramfreetext))
	{
		// Make substitution
		$substitutionarray=array(
			'__FROM_NAME__' => $fromcompany->nom,
			'__FROM_EMAIL__' => $fromcompany->email,
			'__TOTAL_TTC__' => $object->total_ttc,
			'__TOTAL_HT__' => $object->total_ht,
			'__TOTAL_VAT__' => $object->total_vat
		);
		complete_substitutions_array($substitutionarray,$outputlangs,$object);
		$newfreetext=make_substitutions($conf->global->$paramfreetext,$substitutionarray);
		$line.=$outputlangs->convToOutputCharset($newfreetext);
	}

	// First line of company infos

	if ($showdetails)
	{
		$line1="";
		// Company name
		if ($fromcompany->name)
		{
			$line1.=($line1?" - ":"").$outputlangs->transnoentities("RegisteredOffice").": ".$fromcompany->name;
		}
		// Address
		if ($fromcompany->address)
		{
			$line1.=($line1?" - ":"").$fromcompany->address;
		}
		// Zip code
		if ($fromcompany->zip)
		{
			$line1.=($line1?" - ":"").$fromcompany->zip;
		}
		// Town
		if ($fromcompany->town)
		{
			$line1.=($line1?" ":"").$fromcompany->town;
		}
		// Phone
		if ($fromcompany->phone)
		{
			$line1.=($line1?" - ":"").$outputlangs->transnoentities("Phone").": ".$fromcompany->phone;
		}
		// Fax
		if ($fromcompany->fax)
		{
			$line1.=($line1?" - ":"").$outputlangs->transnoentities("Fax").": ".$fromcompany->fax;
		}

		$line2="";
		// URL
		if ($fromcompany->url)
		{
			$line2.=($line2?" - ":"").$fromcompany->url;
		}
		// Email
		if ($fromcompany->email)
		{
			$line2.=($line2?" - ":"").$fromcompany->email;
		}
	}

	// Line 3 of company infos
	$line3="";
	// Juridical status
	if ($fromcompany->forme_juridique_code)
	{
		$line3.=($line3?" - ":"").$outputlangs->convToOutputCharset(getFormeJuridiqueLabel($fromcompany->forme_juridique_code));
	}
	// Capital
	if ($fromcompany->capital)
	{
		$line3.=($line3?" - ":"").$outputlangs->transnoentities("CapitalOf",$fromcompany->capital)." ".$outputlangs->transnoentities("Currency".$conf->currency);
	}
	// Prof Id 1
	if ($fromcompany->idprof1 && ($fromcompany->country_code != 'FR' || ! $fromcompany->idprof2))
	{
		$field=$outputlangs->transcountrynoentities("ProfId1",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line3.=($line3?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof1);
	}
	// Prof Id 2
	if ($fromcompany->idprof2)
	{
		$field=$outputlangs->transcountrynoentities("ProfId2",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line3.=($line3?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof2);
	}

	// Line 4 of company infos
	$line4="";
	// Prof Id 3
	if ($fromcompany->idprof3)
	{
		$field=$outputlangs->transcountrynoentities("ProfId3",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line4.=($line4?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof3);
	}
	// Prof Id 4
	if ($fromcompany->idprof4)
	{
		$field=$outputlangs->transcountrynoentities("ProfId4",$fromcompany->country_code);
		if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
		$line4.=($line4?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof4);
	}
	// IntraCommunautary VAT
	if ($fromcompany->tva_intra != '')
	{
		$line4.=($line4?" - ":"").$outputlangs->transnoentities("VATIntraShort").": ".$outputlangs->convToOutputCharset($fromcompany->tva_intra);
	}

	$pdf->SetFont('','',7);
	$pdf->SetDrawColor(224,224,224);

	// On positionne le debut du bas de page selon nbre de lignes de ce bas de page
	$nbofline=dol_nboflines_bis($line,0,$outputlangs->charset_output);
	//print 'nbofline='.$nbofline; exit;
	//print 'e'.$line.'t'.dol_nboflines($line);exit;
	$posy=$marge_basse + ($nbofline*3) + ($line1?3:0) + ($line2?3:0) + ($line3?3:0) + ($line4?3:0);

	if ($line)	// Free text
	{
		$pdf->SetXY($marge_gauche,-$posy);
		$width=20000; $align='L';	// By default, ask a manual break: We use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
		if ($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT) { $width=200; $align='C'; }
		$pdf->MultiCell($width, 3, $line, 0, $align, 0);
		$posy-=($nbofline*3);	// 6 of ligne + 3 of MultiCell
	}

	$pdf->SetY(-$posy);
	$pdf->line($marge_gauche, $page_hauteur-$posy, 200, $page_hauteur-$posy);
	$posy--;

	if ($line1)
	{
		$pdf->SetFont('','B',7);
		$pdf->SetXY($marge_gauche,-$posy);
		$pdf->MultiCell(200, 2, $line1, 0, 'C', 0);
		$posy-=3;
		$pdf->SetFont('','',7);
	}

	if ($line2)
	{
		$pdf->SetFont('','B',7);
		$pdf->SetXY($marge_gauche,-$posy);
		$pdf->MultiCell(200, 2, $line2, 0, 'C', 0);
		$posy-=3;
		$pdf->SetFont('','',7);
	}

	if ($line3)
	{
		$pdf->SetXY($marge_gauche,-$posy);
		$pdf->MultiCell(200, 2, $line3, 0, 'C', 0);
	}

	if ($line4)
	{
		$posy-=3;
		$pdf->SetXY($marge_gauche,-$posy);
		$pdf->MultiCell(200, 2, $line4, 0, 'C', 0);
	}

	// Show page nb only on iso languages (so default Helvetica font)
	if (pdf_getPDFFont($outputlangs) == 'Helvetica')
	{
		$pdf->SetXY(-20,-$posy);
		$pdf->MultiCell(11, 2, $pdf->PageNo().'/'.$pdf->getAliasNbPages(), 0, 'R', 0);
		//print 'xxx'.$pdf->getAliasNbPages().'-'.$pdf->getAliasNumPage();exit;
	}
}


/**
 *	Output line description into PDF
 *
 *  @param  PDF				&$pdf               PDF object
 *	@param	Object			$object				Object
 *	@param	int				$i					Current line number
 *  @param  Translate		$outputlangs		Object lang for output
 *  @param  int				$w					Width
 *  @param  int				$h					Height
 *  @param  int				$posx				Pos x
 *  @param  int				$posy				Pos y
 *  @param  int				$hideref       		Hide reference
 *  @param  int				$hidedesc            Hide description
 * 	@param	int				$issupplierline		Is it a line for a supplier object ?
 * 	@param	HookManager		$hookmanager		Instance of HookManager
 * 	@return	void
 */
function pdf_writelinedesc(&$pdf,$object,$i,$outputlangs,$w,$h,$posx,$posy,$hideref=0,$hidedesc=0,$issupplierline=0,$hookmanager=false)
{
	global $db, $conf, $langs;

	if (is_object($hookmanager) && ( ($object->lines[$i]->product_type == 9 && ! empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		$parameters = array('pdf'=>$pdf,'i'=>$i,'outputlangs'=>$outputlangs,'w'=>$w,'h'=>$h,'posx'=>$posx,'posy'=>$posy,'hideref'=>$hideref,'hidedesc'=>$hidedesc,'issupplierline'=>$issupplierline,'special_code'=>$special_code);
		$action='';
		$reshook=$hookmanager->executeHooks('pdf_writelinedesc',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	}
	else
	{
		$labelproductservice=pdf_getlinedesc($object,$i,$outputlangs,$hideref,$hidedesc,$issupplierline);
		// Description
		$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1);

		return $labelproductservice;
	}
}

/**
 *  Return line description translated in outputlangs and encoded into htmlentities
 *
 *  @param  Object		$object              Object
 *  @param  int			$i                   Current line number (0 = first line, 1 = second line, ...)
 *  @param  Translate	$outputlangs         Object langs for output
 *  @param  int			$hideref             Hide reference
 *  @param  int			$hidedesc            Hide description
 *  @param  int			$issupplierline      Is it a line for a supplier object ?
 *  @return string       				     String with line
 */
function pdf_getlinedesc($object,$i,$outputlangs,$hideref=0,$hidedesc=0,$issupplierline=0)
{
	global $db, $conf, $langs;

	$idprod=$object->lines[$i]->fk_product;
	$label=$object->lines[$i]->label; if (empty($label))  $label=$object->lines[$i]->libelle;
	$desc=$object->lines[$i]->desc; if (empty($desc))   $desc=$object->lines[$i]->description;
	$ref_supplier=$object->lines[$i]->ref_supplier; if (empty($ref_supplier))   $ref_supplier=$object->lines[$i]->ref_fourn;    // TODO Not yet saved for supplier invoices, only supplier orders
	$note=$object->lines[$i]->note;

	if ($issupplierline) $prodser = new ProductFournisseur($db);
	else $prodser = new Product($db);

	if ($idprod)
	{
		$prodser->fetch($idprod);
		// If a predefined product and multilang and on other lang, we renamed label with label translated
		if ($conf->global->MAIN_MULTILANGS && ($outputlangs->defaultlang != $langs->defaultlang))
		{
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["libelle"]) && $label == $prodser->label)     $label=$prodser->multilangs[$outputlangs->defaultlang]["libelle"];
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["description"]) && $desc == $prodser->description) $desc=$prodser->multilangs[$outputlangs->defaultlang]["description"];
			if (! empty($prodser->multilangs[$outputlangs->defaultlang]["note"]) && $note == $prodser->note)        $note=$prodser->multilangs[$outputlangs->defaultlang]["note"];
		}
	}

	// Description short of product line
	$libelleproduitservice=$label;

	// Description long of product line
	if ($desc && ($desc != $label))
	{
		if ( $libelleproduitservice && empty($hidedesc) ) $libelleproduitservice.="\n";

		if ($desc == '(CREDIT_NOTE)' && $object->lines[$i]->fk_remise_except)
		{
			$discount=new DiscountAbsolute($db);
			$discount->fetch($object->lines[$i]->fk_remise_except);
			$libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromCreditNote",$discount->ref_facture_source);
		}
		elseif ($desc == '(DEPOSIT)' && $object->lines[$i]->fk_remise_except)
		{
		    $discount=new DiscountAbsolute($db);
		    $discount->fetch($object->lines[$i]->fk_remise_except);
		    $libelleproduitservice=$outputlangs->transnoentities("DiscountFromDeposit",$discount->ref_facture_source);
		    // Add date of deposit
		    if (! empty($conf->global->INVOICE_ADD_DEPOSIT_DATE)) echo ' ('.dol_print_date($discount->datec,'day','',$outputlangs).')';
		}
		else
		{
			if ($idprod)
			{
				if ( empty($hidedesc) ) $libelleproduitservice.=$desc;
			}
			else
			{
				$libelleproduitservice.=$desc;
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
			if ($conf->global->PRODUCT_ADD_TYPE_IN_DOCUMENTS)   // In standard mode, we do not show this
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

			if ( empty($hideref) )
			{
				if ($issupplierline) $ref_prodserv = $prodser->ref.' ('.$outputlangs->trans("SupplierRef").' '.$ref_supplier.')';   // Show local ref and supplier ref
				else $ref_prodserv = $prodser->ref; // Show local ref only

				$ref_prodserv .= " - ";
			}

			$libelleproduitservice=$prefix_prodserv.$ref_prodserv.$libelleproduitservice;
		}
	}

	if ($object->lines[$i]->date_start || $object->lines[$i]->date_end)
	{
		$format='day';
		// Show duration if exists
		if ($object->lines[$i]->date_start && $object->lines[$i]->date_end)
		{
			$period='('.$outputlangs->transnoentitiesnoconv('DateFromTo',dol_print_date($object->lines[$i]->date_start, $format, false, $outputlangs),dol_print_date($object->lines[$i]->date_end, $format, false, $outputlangs)).')';
		}
		if ($object->lines[$i]->date_start && ! $object->lines[$i]->date_end)
		{
			$period='('.$outputlangs->transnoentitiesnoconv('DateFrom',dol_print_date($object->lines[$i]->date_start, $format, false, $outputlangs)).')';
		}
		if (! $object->lines[$i]->date_start && $object->lines[$i]->date_end)
		{
			$period='('.$outputlangs->transnoentitiesnoconv('DateUntil',dol_print_date($object->lines[$i]->date_end, $format, false, $outputlangs)).')';
		}
		//print '>'.$outputlangs->charset_output.','.$period;
		$libelleproduitservice.="\n".$period;
		//print $libelleproduitservice;
	}

	// Note that we used here current custom and origin country code.
	/* Fix, this must be done when saving line
	if (! empty($prodser->customcode) || ! empty($prodser->country_code))
	{
	//var_dump($prodser);exit;
	$tmptxt='(';
	if (! empty($prodser->customcode)) $tmptxt.=$langs->transnoentitiesnoconv("CustomCode").': '.$prodser->customcode;
	if (! empty($prodser->customcode) && ! empty($prodser->country_code)) $tmptxt.=' - ';
	if (! empty($prodser->country_code)) $tmptxt.=$langs->transnoentitiesnoconv("CountryOrigin").': '.getCountry($prodser->country_code,0,$db,$outputlangs,0);
	$tmptxt.=')';
	$libelleproduitservice.="<br>".$tmptxt;
	}*/

	// Now we convert \n into br
	$libelleproduitservice=dol_htmlentitiesbr($libelleproduitservice,1);

	return $libelleproduitservice;
}

/**
 *	Return line num
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlinenum($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
	if (! empty($object->hooks) && ( ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		// TODO add hook function
	}
	else
	{
		return dol_htmlentitiesbr($object->lines[$i]->num);
	}
}


/**
 *	Return line product ref
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlineref($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
	if (! empty($object->hooks) && ( ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		// TODO add hook function
	}
	else
	{
		return dol_htmlentitiesbr($object->lines[$i]->product_ref);
	}
}

/**
 *	Return line ref_supplier
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlineref_supplier($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
	if (! empty($object->hooks) && ( ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		// TODO add hook function
	}
	else
	{
		return dol_htmlentitiesbr($object->lines[$i]->ref_supplier);
	}
}

/**
 *	Return line vat rate
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlinevatrate($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
	if (is_object($hookmanager) && ( ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
		$action='';
		return $hookmanager->executeHooks('pdf_getlinevatrate',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	}
	else
	{
		if (empty($hidedetails) || $hidedetails > 1) return vatrate($object->lines[$i]->tva_tx,1,$object->lines[$i]->info_bits,1);
	}
}

/**
 *	Return line unit price excluding tax
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlineupexcltax($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
    global $conf;

    $sign=1;
    if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

    if (is_object($hookmanager) && ( ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
		$action='';
		return $hookmanager->executeHooks('pdf_getlineupexcltax',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	}
	else
	{
		if (empty($hidedetails) || $hidedetails > 1) return price($sign * $object->lines[$i]->subprice);
	}
}

/**
 *	Return line unit price including tax
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Tranlate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide value (0 = no,	1 = yes, 2 = just special lines)
 *  @return	void
 */
function pdf_getlineupwithtax($object,$i,$outputlangs,$hidedetails=0)
{
    if (! empty($object->hooks) && ( ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
    {
        $special_code = $object->lines[$i]->special_code;
    	if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
    	foreach($object->hooks as $hook)
    	{
    		if (method_exists($hook['modules'][$special_code],'pdf_getlineupwithtax')) return $hook['modules'][$special_code]->pdf_getlineupwithtax($object,$i,$outputlangs,$hidedetails);
		}
    }
    else
    {
        if (empty($hidedetails) || $hidedetails > 1) return price(($object->lines[$i]->subprice) + ($object->lines[$i]->subprice)*($object->lines[$i]->tva_tx)/100);
    }
}

/**
 *	Return line quantity
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 *  @return	void
 */
function pdf_getlineqty($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
	if ($object->lines[$i]->special_code != 3)
	{
		if (is_object($hookmanager) && (( $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
		{
			$special_code = $object->lines[$i]->special_code;
			if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action='';
			return $hookmanager->executeHooks('pdf_getlineqty',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		}
		else
		{
			if (empty($hidedetails) || $hidedetails > 1) return $object->lines[$i]->qty;
		}
	}
}

/**
 *	Return line quantity asked
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlineqty_asked($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
	if ($object->lines[$i]->special_code != 3)
	{
		if (is_object($hookmanager) && (( $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
		{
			$special_code = $object->lines[$i]->special_code;
			if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action='';
			return $hookmanager->executeHooks('pdf_getlineqty_asked',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		}
		else
		{
			if (empty($hidedetails) || $hidedetails > 1) return $object->lines[$i]->qty_asked;
		}
	}
}

/**
 *	Return line quantity shipped
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlineqty_shipped($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
	if ($object->lines[$i]->special_code != 3)
	{
		if (is_object($hookmanager) && (( $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
		{
			$special_code = $object->lines[$i]->special_code;
			if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action='';
			return $hookmanager->executeHooks('pdf_getlineqty_shipped',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		}
		else
		{
			if (empty($hidedetails) || $hidedetails > 1) return $object->lines[$i]->qty_shipped;
		}
	}
}

/**
 *	Return line keep to ship quantity
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlineqty_keeptoship($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
	if ($object->lines[$i]->special_code != 3)
	{
		if (is_object($hookmanager) && (( $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
		{
			$special_code = $object->lines[$i]->special_code;
			if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action='';
			return $hookmanager->executeHooks('pdf_getlineqty_keeptoship',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		}
		else
		{
			if (empty($hidedetails) || $hidedetails > 1) return ($object->lines[$i]->qty_asked - $object->lines[$i]->qty_shipped);
		}
	}
}

/**
 *	Return line remise percent
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlineremisepercent($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
	include_once(DOL_DOCUMENT_ROOT."/core/lib/functions2.lib.php");

	if ($object->lines[$i]->special_code != 3)
	{
		if (is_object($hookmanager) && ( ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
		{
			$special_code = $object->lines[$i]->special_code;
			if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action='';
			return $hookmanager->executeHooks('pdf_getlineremisepercent',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		}
		else
		{
			if (empty($hidedetails) || $hidedetails > 1) return dol_print_reduction($object->lines[$i]->remise_percent,$outputlangs);
		}
	}
}

/**
 *	Return line total excluding tax
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getlinetotalexcltax($object,$i,$outputlangs,$hidedetails=0,$hookmanager=false)
{
    global $conf;

    $sign=1;
    if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

	if ($object->lines[$i]->special_code == 3)
	{
		return $outputlangs->transnoentities("Option");
	}
	else
	{
		if (is_object($hookmanager) && ( ($object->lines[$i]->product_type == 9 && ! empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
		{
			$special_code = $object->lines[$i]->special_code;
			if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action='';
			return $hookmanager->executeHooks('pdf_getlinetotalexcltax',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		}
		else
		{
			if (empty($hidedetails) || $hidedetails > 1) return price($sign * $object->lines[$i]->total_ht);
		}
	}
}

/**
 *	Return line total including tax
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param 	Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
 *  @return	void
 */
function pdf_getlinetotalwithtax($object,$i,$outputlangs,$hidedetails=0)
{
    if ($object->lines[$i]->special_code == 3)
    {
        return $outputlangs->transnoentities("Option");
    }
    else
    {
        if (! empty($object->hooks) && ( ($object->lines[$i]->product_type == 9 && ! empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
        {
        	$special_code = $object->lines[$i]->special_code;
        	if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
        	foreach($object->hooks as $hook)
	    	{
	    		if (method_exists($hook['modules'][$special_code],'pdf_getlinetotalwithtax')) return $hook['modules'][$special_code]->pdf_getlinetotalwithtax($object,$i,$outputlangs,$hidedetails);
			}
        }
        else
        {
            if (empty($hidedetails) || $hidedetails > 1) return
				price(($object->lines[$i]->total_ht) + ($object->lines[$i]->total_ht)*($object->lines[$i]->tva_tx)/100);
        }
    }
}

/**
 *	Return total quantity of products and/or services
 *
 *	@param	Object		$object				Object
 *	@param	string		$type				Type
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	HookManager	$hookmanager		Hook manager instance
 * 	@return	void
 */
function pdf_getTotalQty($object,$type,$outputlangs,$hookmanager=false)
{
	$total=0;
	$nblignes=count($object->lines);

	// Loop on each lines
	for ($i = 0 ; $i < $nblignes ; $i++)
	{
		if ($object->lines[$i]->special_code != 3)
		{
			if ($type=='all')
			{
				$total += $object->lines[$i]->qty;
			}
			else if ($type==9 && ! empty($object->hooks) && ( ($object->lines[$i]->product_type == 9 && ! empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
			{
				$special_code = $object->lines[$i]->special_code;
				if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
				// TODO add hook function
			}
			else if ($type==0 && $object->lines[$i]->product_type == 0)
			{
				$total += $object->lines[$i]->qty;
			}
			else if ($type==1 && $object->lines[$i]->product_type == 1)
			{
				$total += $object->lines[$i]->qty;
			}
		}
	}

	return $total;
}


/**
 *	Convert a currency code into its symbol
 *
 *  @param      PDF		&$pdf          		PDF object
 *  @param		string	$currency_code		Currency code
 *  @return		string						Currency symbol encoded into UTF8
 */
function pdf_getCurrencySymbol(&$pdf, $currency_code)
{
	switch ($currency_code) {
		case "ALL":
			$currency_sign = " ".$pdf->unichr(76).$pdf->unichr(101).$pdf->unichr(107);
			break;
		case "AFN":
			$currency_sign = " ".$pdf->unichr(1547);
			break;
		case "ARS":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "AWG":
			$currency_sign = " ".$pdf->unichr(402);
			break;
		case "AUD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "AZN":
			$currency_sign = " ".$pdf->unichr(1084).$pdf->unichr(1072).$pdf->unichr(1085);
			break;
		case "BSD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "BBD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "BYR":
			$currency_sign = " ".$pdf->unichr(112).$pdf->unichr(46);
			break;
		case "BZD":
			$currency_sign = " ".$pdf->unichr(66).$pdf->unichr(90).$pdf->unichr(36);
			break;
		case "BMD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "BOB":
			$currency_sign = " ".$pdf->unichr(36).$pdf->unichr(98);
			break;
		case "BAM":
			$currency_sign = " ".$pdf->unichr(75).$pdf->unichr(77);
			break;
		case "BWP":
			$currency_sign = " ".$pdf->unichr(80);
			break;
		case "BGN":
			$currency_sign = " ".$pdf->unichr(1083).$pdf->unichr(1074);
			break;
		case "BRL":
			$currency_sign = " ".$pdf->unichr(82).$pdf->unichr(36);
			break;
		case "BND":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "KHR":
			$currency_sign = " ".$pdf->unichr(6107);
			break;
		case "CAD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "KYD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "CLP":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "CNY":
			$currency_sign = " ".$pdf->unichr(165);
			break;
		case "COP":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "CRC":
			$currency_sign = " ".$pdf->unichr(8353);
			break;
		case "HRK":
			$currency_sign = " ".$pdf->unichr(107).$pdf->unichr(110);
			break;
		case "CUP":
			$currency_sign = " ".$pdf->unichr(8369);
			break;
		case "CZK":
			$currency_sign = " ".$pdf->unichr(75).$pdf->unichr(269);
			break;
		case "DKK":
			$currency_sign = " ".$pdf->unichr(107).$pdf->unichr(114);
			break;
		case "DOP":
			$currency_sign = " ".$pdf->unichr(82).$pdf->unichr(68).$pdf->unichr(36);
			break;
		case "XCD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "EGP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "SVC":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "EEK":
			$currency_sign = " ".$pdf->unichr(107).$pdf->unichr(114);
			break;
		case "EUR":
			$currency_sign = " ".$pdf->unichr(8364);
			break;
		case "FKP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "FJD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "GHC":
			$currency_sign = " ".$pdf->unichr(162);
			break;
		case "GIP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "GTQ":
			$currency_sign = " ".$pdf->unichr(81);
			break;
		case "GGP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "GYD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "HNL":
			$currency_sign = " ".$pdf->unichr(76);
			break;
		case "HKD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "HUF":
			$currency_sign = " ".$pdf->unichr(70).$pdf->unichr(116);
			break;
		case "ISK":
			$currency_sign = " ".$pdf->unichr(107).$pdf->unichr(114);
			break;
		case "INR":
			$currency_sign = " ".$pdf->unichr(8377);
			break;
		case "IDR":
			$currency_sign = " ".$pdf->unichr(82).$pdf->unichr(112);
			break;
		case "IRR":
			$currency_sign = " ".$pdf->unichr(65020);
			break;
		case "IMP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "ILS":
			$currency_sign = " ".$pdf->unichr(8362);
			break;
		case "JMD":
			$currency_sign = " ".$pdf->unichr(74).$pdf->unichr(36);
			break;
		case "JPY":
			$currency_sign = " ".$pdf->unichr(165);
			break;
		case "JEP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "KZT":
			$currency_sign = " ".$pdf->unichr(1083).$pdf->unichr(1074);
			break;
		case "KPW":
			$currency_sign = " ".$pdf->unichr(8361);
			break;
		case "KRW":
			$currency_sign = " ".$pdf->unichr(8361);
			break;
		case "KGS":
			$currency_sign = " ".$pdf->unichr(1083).$pdf->unichr(1074);
			break;
		case "LAK":
			$currency_sign = " ".$pdf->unichr(8365);
			break;
		case "LVL":
			$currency_sign = " ".$pdf->unichr(76).$pdf->unichr(115);
			break;
		case "LBP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "LRD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "LTL":
			$currency_sign = " ".$pdf->unichr(76).$pdf->unichr(116);
			break;
		case "MKD":
			$currency_sign = " ".$pdf->unichr(1076).$pdf->unichr(1077).$pdf->unichr(1085);
			break;
		case "MYR":
			$currency_sign = " ".$pdf->unichr(82).$pdf->unichr(77);
			break;
		case "MUR":
			$currency_sign = " ".$pdf->unichr(8360);
			break;
		case "MXN":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "MNT":
			$currency_sign = " ".$pdf->unichr(8366);
			break;
		case "MZN":
			$currency_sign = " ".$pdf->unichr(77).$pdf->unichr(84);
			break;
		case "NAD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "NPR":
			$currency_sign = " ".$pdf->unichr(8360);
			break;
		case "ANG":
			$currency_sign = " ".$pdf->unichr(402);
			break;
		case "NZD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "NIO":
			$currency_sign = " ".$pdf->unichr(67).$pdf->unichr(36);
			break;
		case "NGN":
			$currency_sign = " ".$pdf->unichr(8358);
			break;
		case "NOK":
			$currency_sign = " ".$pdf->unichr(107).$pdf->unichr(114);
			break;
		case "OMR":
			$currency_sign = " ".$pdf->unichr(65020);
			break;
		case "PKR":
			$currency_sign = " ".$pdf->unichr(8360);
			break;
		case "PAB":
			$currency_sign = " ".$pdf->unichr(66).$pdf->unichr(47).$pdf->unichr(46);
			break;
		case "PYG":
			$currency_sign = " ".$pdf->unichr(71).$pdf->unichr(115);
			break;
		case "PEN":
			$currency_sign = " ".$pdf->unichr(83).$pdf->unichr(47).$pdf->unichr(46);
			break;
		case "PHP":
			$currency_sign = " ".$pdf->unichr(8369);
			break;
		case "PLN":
			$currency_sign = " ".$pdf->unichr(122).$pdf->unichr(322);
			break;
		case "QAR":
			$currency_sign = " ".$pdf->unichr(65020);
			break;
		case "RON":
			$currency_sign = " ".$pdf->unichr(108).$pdf->unichr(101).$pdf->unichr(105);
			break;
		case "RUB":
			$currency_sign = " ".$pdf->unichr(1088).$pdf->unichr(1091).$pdf->unichr(1073);
			break;
		case "SHP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "SAR":
			$currency_sign = " ".$pdf->unichr(65020);
			break;
		case "RSD":
			$currency_sign = " ".$pdf->unichr(1044).$pdf->unichr(1080).$pdf->unichr(1085).$pdf->unichr(46);
			break;
		case "SCR":
			$currency_sign = " ".$pdf->unichr(8360);
			break;
		case "SGD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "SBD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "SOS":
			$currency_sign = " ".$pdf->unichr(83);
			break;
		case "ZAR":
			$currency_sign = " ".$pdf->unichr(82);
			break;
		case "LKR":
			$currency_sign = " ".$pdf->unichr(8360);
			break;
		case "SEK":
			$currency_sign = " ".$pdf->unichr(107).$pdf->unichr(114);
			break;
		case "CHF":
			$currency_sign = " ".$pdf->unichr(67).$pdf->unichr(72).$pdf->unichr(70);
			break;
		case "SRD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "SYP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "TWD":
			$currency_sign = " ".$pdf->unichr(78).$pdf->unichr(84).$pdf->unichr(36);
			break;
		case "THB":
			$currency_sign = " ".$pdf->unichr(3647);
			break;
		case "TTD":
			$currency_sign = " ".$pdf->unichr(84).$pdf->unichr(84).$pdf->unichr(36);
			break;
		case "TRY":
			$currency_sign = " ".$pdf->unichr(84).$pdf->unichr(76);
			break;
		case "TRL":
			$currency_sign = " ".$pdf->unichr(8356);
			break;
		case "TVD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "UAH":
			$currency_sign = " ".$pdf->unichr(8372);
			break;
		case "GBP":
			$currency_sign = " ".$pdf->unichr(163);
			break;
		case "USD":
			$currency_sign = " ".$pdf->unichr(36);
			break;
		case "UYU":
			$currency_sign = " ".$pdf->unichr(36).$pdf->unichr(85);
			break;
		case "UZS":
			$currency_sign = " ".$pdf->unichr(1083).$pdf->unichr(1074);
			break;
		case "VEF":
			$currency_sign = " ".$pdf->unichr(66).$pdf->unichr(115);
			break;
		case "VND":
			$currency_sign = " ".$pdf->unichr(8363);
			break;
		case "YER":
			$currency_sign = " ".$pdf->unichr(65020);
			break;
		case "ZWD":
			$currency_sign = " ".$pdf->unichr(90).$pdf->unichr(36);
			break;
		default:
			$currency_sign = " ".$currency_code;
			break;
	}
	return $currency_sign;
}

?>
