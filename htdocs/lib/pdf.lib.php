<?php
/* Copyright (C) 2006-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2010-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
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
 *      Return a PDF instance object. We create a FPDI instance that instanciate TCPDF (or FPDF if MAIN_USE_FPDF is on)
 *      @param      format          Array(width,height)
 *      @param      metric          Unit of format ('mm')
 *      @param      pagetype        'P' or 'l'
 *      @return     PDF object
 */
function pdf_getInstance($format,$metric='mm',$pagetype='P')
{
    global $conf;

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
        if ($conf->global->MAIN_USE_FPDF)
        {
            $pdf = new FPDI_Protection($pagetype,$metric,$format);
            // For FPDF, we specify permission we want to open
            $pdfrights = array('print');
        }
        else
        {
            $pdf = new FPDI($pagetype,$metric,$format);
            // For TCPDF, we specify permission we want to block
            $pdfrights = array('modify','copy');
        }
        $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
        $pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
        $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
    }
    else
    {
        $pdf=new FPDI($pagetype,$metric,$format);
    }
    return $pdf;
}

/**
 *      Return font name to use for PDF generation
 *      @param      outputlangs     Output langs object
 *      @return     string          Name of font to use
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
 *      @param      outputlangs     Output langs object
 *      @return     int             Size of font to use
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
 *      Return a formated address (part address/zip/town/state) according to country rules
 *      @param      outputlangs     Output langs object
 *      @param      object          A company or contact object
 *      @return     string          Formated string
 */
function pdf_format_address($outputlangs,$object)
{
    $ret='';
    $countriesusingstate=array('US','IN');

    // Address
    $ret .= $outputlangs->convToOutputCharset($object->address);
    // Zip/Town/State
    if (in_array($object->pays_code,array('US')))   // US: town, state, zip
    {
        $ret .= ($ret ? "\n" : '' ).$outputlangs->convToOutputCharset($object->ville);
        if ($object->departement && in_array($object->pays_code,$countriesusingstate))
        {
            $ret.=", ".$outputlangs->convToOutputCharset($object->departement);
        }
        if ($object->cp) $ret .= ', '.$outputlangs->convToOutputCharset($object->cp);
    }
    else                                        // Other: zip town, state
    {
        $ret .= ($ret ? "\n" : '' ).$outputlangs->convToOutputCharset($object->cp);
        $ret .= ' '.$outputlangs->convToOutputCharset($object->ville);
        if ($object->departement && in_array($object->pays_code,$countriesusingstate))
        {
            $ret.=", ".$outputlangs->convToOutputCharset($object->departement);
        }
    }

    return $ret;
}


/**
 *   	Return a string with full address formated
 * 		@param		outputlangs		Output langs object
 *   	@param      sourcecompany	Source company object
 *   	@param      targetcompany	Target company object
 *      @param      targetcontact	Target contact object
 * 		@param		usecontact		Use contact instead of company
 * 		@param		mode			Address type
 * 		@param		deliverycompany	Delivery company object
 * 		@return		string			String with full address
 */
function pdf_build_address($outputlangs,$sourcecompany,$targetcompany='',$targetcontact='',$usecontact=0,$mode='source',$deliverycompany='')
{
    global $conf;

    $stringaddress = '';

    if ($mode == 'source' && ! is_object($sourcecompany)) return -1;
    if ($mode == 'target' && ! is_object($targetcompany)) return -1;
    if ($mode == 'delivery' && ! is_object($deliverycompany)) return -1;	// TODO Because delivery is a "target" address, it must in parameter targetcompany and mode must be 'target'

    if ($sourcecompany->departement_id && empty($sourcecompany->departement)) $sourcecompany->departement=getState($sourcecompany->departement_id);
    if ($targetcompany->departement_id && empty($targetcompany->departement)) $targetcompany->departement=getState($targetcompany->departement_id);

    if ($mode == 'source')
    {
        $stringaddress .= ($stringaddress ? "\n" : '' ).pdf_format_address($outputlangs,$sourcecompany)."\n";

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
            $stringaddress .= ($stringaddress ? "\n" : '' ).pdf_format_address($outputlangs,$targetcontact)."\n";
            // Country
            if ($targetcontact->pays_code && $targetcontact->pays_code != $sourcecompany->pays_code) $stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->pays_code))."\n";
        }
        else
        {
            $stringaddress .= ($stringaddress ? "\n" : '' ).pdf_format_address($outputlangs,$targetcompany)."\n";
            // Country
            if ($targetcompany->pays_code && $targetcompany->pays_code != $sourcecompany->pays_code) $stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->pays_code))."\n";
        }

        // Intra VAT
        if ($targetcompany->tva_intra) $stringaddress.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);

        // Professionnal Ids
        if ($conf->global->MAIN_PROFID1_IN_ADDRESS)
        {
            $tmp=$outputlangs->transcountrynoentities("ProfId1",$targetcompany->pays_code);
            if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
            $stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
        }
        if ($conf->global->MAIN_PROFID2_IN_ADDRESS)
        {
            $tmp=$outputlangs->transcountrynoentities("ProfId2",$targetcompany->pays_code);
            if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
            $stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
        }
        if ($conf->global->MAIN_PROFID3_IN_ADDRESS)
        {
            $tmp=$outputlangs->transcountrynoentities("ProfId3",$targetcompany->pays_code);
            if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
            $stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
        }
        if ($conf->global->MAIN_PROFID4_IN_ADDRESS)
        {
            $tmp=$outputlangs->transcountrynoentities("ProfId4",$targetcompany->pays_code);
            if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
            $stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof4);
        }
    }

	if ($mode == 'delivery')	// deprecated
    {
        $stringaddress .= ($stringaddress ? "\n" : '' ).pdf_format_address($outputlangs,$deliverycompany)."\n";

        // TODO Tel and fax are not zip fields. Must be output outside of this function.
        // Tel
        if ($deliverycompany->phone) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($deliverycompany->phone);
        // Fax
        if ($deliverycompany->fax) $stringaddress .= ($stringaddress ? ($deliverycompany->phone ? " - " : "\n") : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($deliverycompany->fax);
    }

    return $stringaddress;
}


/**
 *   	Show header of page for PDF generation
 *   	@param      pdf     		Object PDF
 *      @param      outputlang		Object lang for output
 * 		@param		page_height
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
 *      @param      pdf             Object PDF
 *      @param      outputlangs     Object lang
 *      @param      height          Height of PDF
 *      @param      width           Width of PDF
 *      @param      unit            Unit of height (mmn, pt, ...)
 *      @param      text            Text to show
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
 *      @param      pdf             Object PDF
 *      @param      outputlangs     Object lang
 *      @param      curx            X
 *      @param      cury            Y
 *      @param      account         Bank account object
 *      @param      onlynumber      Output only number
 */
function pdf_bank(&$pdf,$outputlangs,$curx,$cury,$account,$onlynumber=0)
{
    global $mysoc, $conf;

    $pdf->SetXY ($curx, $cury);

    if (empty($onlynumber))
    {
        $pdf->SetFont('','B',8);
        $pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
        $cury+=4;
    }

    $outputlangs->load("banks");

    // Get format of bank id according to country of $account
    $usedetailedbban=$account->useDetailedBBAN();

    if ($usedetailedbban)
    {
        $savcurx=$curx;

        if (empty($onlynumber))
        {
            $pdf->SetFont('','',6);
            $pdf->SetXY ($curx, $cury);
            $pdf->MultiCell(90, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
            $cury+=3;
        }

        if (empty($onlynumber)) $pdf->line($curx+1, $cury+1, $curx+1, $cury+8 );

        $fieldstoshow=array('bank','desk','number','key');
        if ($conf->global->BANK_SHOW_ORDER_OPTION==1) $fieldstoshow=array('bank','desk','key','number');

        foreach ($fieldstoshow as $val)
        {
            if ($val == 'bank')
            {
                // Bank code
                $tmplength=18;
                $pdf->SetXY ($curx, $cury+5);
                $pdf->SetFont('','',8);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_banque), 0, 'C', 0);
                $pdf->SetXY ($curx, $cury+1);
                $curx+=$tmplength;
                $pdf->SetFont('','B',6);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankCode"), 0, 'C', 0);
                if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8 );
            }
            if ($val == 'desk')
            {
                // Desk
                $tmplength=18;
                $pdf->SetXY ($curx, $cury+5);
                $pdf->SetFont('','',8);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_guichet), 0, 'C', 0);
                $pdf->SetXY ($curx, $cury+1);
                $curx+=$tmplength;
                $pdf->SetFont('','B',6);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("DeskCode"), 0, 'C', 0);
                if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8 );
            }
            if ($val == 'number')
            {
                // Number
                $tmplength=24;
                $pdf->SetXY ($curx, $cury+5);
                $pdf->SetFont('','',8);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->number), 0, 'C', 0);
                $pdf->SetXY ($curx, $cury+1);
                $curx+=$tmplength;
                $pdf->SetFont('','B',6);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumber"), 0, 'C', 0);
                if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8 );
            }
            if ($val == 'key')
            {
                // Key
                $tmplength=13;
                $pdf->SetXY ($curx, $cury+5);
                $pdf->SetFont('','',8);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->cle_rib), 0, 'C', 0);
                $pdf->SetXY ($curx, $cury+1);
                $curx+=$tmplength;
                $pdf->SetFont('','B',6);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumberKey"), 0, 'C', 0);
                if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8 );
            }
        }

        $curx=$savcurx;
    }
    else
    {
        $pdf->SetFont('','B',6);
        $pdf->SetXY ($curx, $cury);
        $pdf->MultiCell(90, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
        $cury+=3;

        $pdf->SetFont('','B',6);
        $pdf->SetXY ($curx, $cury);
        $pdf->MultiCell(90, 3, $outputlangs->transnoentities("BankAccountNumber").': ' . $outputlangs->convToOutputCharset($account->number), 0, 'L', 0);
        $cury-=9;
    }
    $pdf->SetXY ($curx, $cury+1);

    // Use correct name of bank id according to country
    $ibankey="IBANNumber";
    $bickey="BICNumber";
    if ($account->getCountryCode() == 'IN') $ibankey="IFSC";
    if ($account->getCountryCode() == 'IN') $bickey="SWIFT";

    $pdf->SetFont('','',6);

    if (empty($onlynumber))
    {
        $cury+=10;
        $pdf->SetXY ($curx, $cury);
        $pdf->MultiCell(90, 3, $outputlangs->transnoentities("Residence").': ' . $outputlangs->convToOutputCharset($account->domiciliation), 0, 'L', 0);
    }

    $pdf->SetXY ($curx, $cury+9);
    $pdf->MultiCell(90, 3, $outputlangs->transnoentities($ibankey).': ' . $outputlangs->convToOutputCharset($account->iban), 0, 'L', 0);
    $pdf->SetXY ($curx, $cury+12);
    $pdf->MultiCell(90, 3, $outputlangs->transnoentities($bickey).': ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);

    return $pdf->getY();
}


/**
 *   	Show footer of page for PDF generation
 *   	@param      pdf     		The PDF factory
 *      @param      outputlang		Object lang for output
 * 		@param		paramfreetext	Constant name of free text
 * 		@param		fromcompany		Object company
 * 		@param		marge_basse		Margin bottom
 * 		@param		marge_gauche	Margin left
 * 		@param		page_hauteur	Page height
 * 		@param		object			Object shown in PDF
 * 		@param		showdetails		Show company details
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

        $newfreetext=make_substitutions($conf->global->$paramfreetext,$substitutionarray,$outputlangs,$object);
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
        $line3.=($line3?" - ":"").$outputlangs->transnoentities("CapitalOf",$fromcompany->capital)." ".$outputlangs->transnoentities("Currency".$conf->monnaie);
    }
    // Prof Id 1
    if ($fromcompany->idprof1 && ($fromcompany->pays_code != 'FR' || ! $fromcompany->idprof2))
    {
        $field=$outputlangs->transcountrynoentities("ProfId1",$fromcompany->pays_code);
        if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
        $line3.=($line3?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof1);
    }
    // Prof Id 2
    if ($fromcompany->idprof2)
    {
        $field=$outputlangs->transcountrynoentities("ProfId2",$fromcompany->pays_code);
        if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
        $line3.=($line3?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof2);
    }

    // Line 4 of company infos
    $line4="";
    // Prof Id 3
    if ($fromcompany->idprof3)
    {
        $field=$outputlangs->transcountrynoentities("ProfId3",$fromcompany->pays_code);
        if (preg_match('/\((.*)\)/i',$field,$reg)) $field=$reg[1];
        $line4.=($line4?" - ":"").$field.": ".$outputlangs->convToOutputCharset($fromcompany->idprof3);
    }
    // Prof Id 4
    if ($fromcompany->idprof4)
    {
        $field=$outputlangs->transcountrynoentities("ProfId4",$fromcompany->pays_code);
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
        $pdf->MultiCell(11, 2, $pdf->PageNo().'/{nb}', 0, 'R', 0);
    }
}


/**
 *	Output line description into PDF
 *  @param      pdf                 PDF object
 *	@param		object				Object
 *	@param		i					Current line number
 *  @param    	outputlang			Object lang for output
 *  @param      w
 *  @param      h
 *  @param      posx
 *  @param      posy
 *  @param    	hideref       		Hide reference
 *  @param      hidedesc            Hide description
 * 	@param		issupplierline		Is it a line for a supplier object ?
 */
function pdf_writelinedesc(&$pdf,$object,$i,$outputlangs,$w,$h,$posx,$posy,$hideref=0,$hidedesc=0,$issupplierline=0)
{
    global $db, $conf, $langs;

    if (!empty($object->hooks) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code))
    {
        return $object->hooks[$object->lines[$i]->special_code]->pdf_writelinedesc($pdf,$object,$i,$outputlangs,$w,$h,$posx,$posy);
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
 *  Return line description translated in outputlangs and encoded in UTF8
 *  @param      object              Object
 *  @param      i                   Current line number
 *  @param      outputlang          Object lang for output
 *  @param      hideref             Hide reference
 *  @param      hidedesc            Hide description
 *  @param      issupplierline      Is it a line for a supplier object ?
 *  @return     string              String with line
 */
function pdf_getlinedesc($object,$i,$outputlangs,$hideref=0,$hidedesc=0,$issupplierline=0)
{
    global $db, $conf, $langs;

    $idprod=$object->lines[$i]->fk_product;
    $label=$object->lines[$i]->label; if (empty($label))  $label=$object->lines[$i]->libelle;
    $desc=$object->lines[$i]->desc; if (empty($desc))   $desc=$object->lines[$i]->description;
    $ref_supplier=$object->lines[$i]->ref_supplier; if (empty($ref_supplier))   $ref_supplier=$object->lines[$i]->ref_fourn;    // TODO Not yeld saved for supplier invoices, only supplier orders
    $note=$object->lines[$i]->note;

    if ($issupplierline) $prodser = new ProductFournisseur($db);
    else $prodser = new Product($db);

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
        if ($libelleproduitservice && !$hidedesc) $libelleproduitservice.="\n";

        if ($desc == '(CREDIT_NOTE)' && $object->lines[$i]->fk_remise_except)
        {
            $discount=new DiscountAbsolute($db);
            $discount->fetch($object->lines[$i]->fk_remise_except);
            $libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromCreditNote",$discount->ref_facture_source);
        }
        else
        {
            if ($idprod)
            {
                if (!$hidedesc) $libelleproduitservice.=$desc;
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

            if (!$hideref)
            {
                if ($issupplierline) $ref_prodserv = $prodser->ref.' ('.$outputlangs->trans("SupplierRef").' '.$ref_supplier.')';   // Show local ref and supplier ref
                else $ref_prodserv = $prodser->ref; // Show local ref only

                $ref_prodserv .= " - ";
            }

            $libelleproduitservice=$prefix_prodserv.$ref_prodserv.$libelleproduitservice;
        }
    }

    $libelleproduitservice=dol_htmlentitiesbr($libelleproduitservice,1);

    if ($object->lines[$i]->date_start || $object->lines[$i]->date_end)
    {
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
        $libelleproduitservice.="<br>".dol_htmlentitiesbr($period,1);
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

    return $libelleproduitservice;
}


/**
 *	Return line ref
 *	@param		object				Object
 *	@param		$i					Current line number
 *  @param    	outputlang			Object lang for output
 */
function pdf_getlineref($object,$i,$outputlangs)
{
    if (!empty($object->hooks) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code))
    {
        // TODO add hook function
    }
    else
    {
        return dol_htmlentitiesbr($object->lines[$i]->ref);
    }
}

/**
 *	Return line vat rate
 *	@param		object				Object
 *	@param		$i					Current line number
 *  @param    	outputlang			Object lang for output
 */
function pdf_getlinevatrate($object,$i,$outputlangs)
{
    if (!empty($object->hooks) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code))
    {
        // TODO add hook function
    }
    else
    {
        return vatrate($object->lines[$i]->tva_tx,1,$object->lines[$i]->info_bits);
    }
}

/**
 *	Return line unit price excluding tax
 *	@param		object				Object
 *	@param		$i					Current line number
 *  @param    	outputlang			Object lang for output
 */
function pdf_getlineupexcltax($object,$i,$outputlangs)
{
    if (!empty($object->hooks) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code))
    {
        // TODO add hook function
    }
    else
    {
        return price($object->lines[$i]->subprice);
    }
}

/**
 *	Return line quantity
 *	@param		object				Object
 *	@param		$i					Current line number
 *  @param    	outputlang			Object lang for output
 */
function pdf_getlineqty($object,$i,$outputlangs)
{
    if ($object->lines[$i]->special_code != 3)
    {
        if (!empty($object->hooks) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code))
        {
            // TODO add hook function
        }
        else
        {
            return $object->lines[$i]->qty;
        }
    }
}

/**
 *	Return line remise percent
 *	@param		object				Object
 *	@param		$i					Current line number
 *  @param    	outputlang			Object lang for output
 */
function pdf_getlineremisepercent($object,$i,$outputlangs)
{
    include_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
    if ($object->lines[$i]->special_code != 3)
    {
        if (!empty($object->hooks) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code))
        {
            // TODO add hook function
        }
        else
        {
            return dol_print_reduction($object->lines[$i]->remise_percent,$outputlangs);
        }
    }
}

/**
 *	Return line total excluding tax
 *	@param		object				Object
 *	@param		$i					Current line number
 *  @param    	outputlang			Object lang for output
 */
function pdf_getlinetotalexcltax($object,$i,$outputlangs)
{
    if ($object->lines[$i]->special_code == 3)
    {
        return $outputlangs->transnoentities("Option");
    }
    else
    {
        if (!empty($object->hooks) && $object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code))
        {
            // TODO add hook function
        }
        else
        {
            return price($object->lines[$i]->total_ht);
        }
    }
}

?>
