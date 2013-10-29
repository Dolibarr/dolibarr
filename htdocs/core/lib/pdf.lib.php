<?php
/* Copyright (C) 2006-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2007      Patrick Raguin       <patrick.raguin@gmail.com>
 * Copyright (C) 2010-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2010      Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
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
 *	\file       htdocs/core/lib/pdf.lib.php
 *	\brief      Set of functions used for PDF generation
 *	\ingroup    core
 */


/**
 *	Return array with format properties of default PDF format
 *
 *	@param		Translate	$outputlangs		Output lang to use to autodetect output format if setup not done
 *  @return     array							Array('width'=>w,'height'=>h,'unit'=>u);
 */
function pdf_getFormat($outputlangs='')
{
	global $conf,$db;

	// Default value if setup was not done and/or entry into c_paper_format not defined
	$width=210; $height=297; $unit='mm';

	if (empty($conf->global->MAIN_PDF_FORMAT))
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$pdfformat=dol_getDefaultFormat($outputlangs);
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
 *      Return a PDF instance object. We create a FPDI instance that instantiate TCPDF.
 *
 *      @param	string		$format         Array(width,height). Keep empty to use default setup.
 *      @param	string		$metric         Unit of format ('mm')
 *      @param  string		$pagetype       'P' or 'l'
 *      @return TPDF						PDF object
 */
function pdf_getInstance($format='',$metric='mm',$pagetype='P')
{
	global $conf;

	// Define constant for TCPDF
	define('K_TCPDF_EXTERNAL_CONFIG',1);	// this avoid using tcpdf_config file
	define('K_PATH_CACHE', DOL_DATA_ROOT.'/admin/temp/');
	define('K_PATH_URL_CACHE', DOL_DATA_ROOT.'/admin/temp/');
	dol_mkdir(K_PATH_CACHE);
	define('K_BLANK_IMAGE', '_blank.png');
	define('PDF_PAGE_FORMAT', 'A4');
	define('PDF_PAGE_ORIENTATION', 'P');
	define('PDF_CREATOR', 'TCPDF');
	define('PDF_AUTHOR', 'TCPDF');
	define('PDF_HEADER_TITLE', 'TCPDF Example');
	define('PDF_HEADER_STRING', "by Dolibarr ERP CRM");
	define('PDF_UNIT', 'mm');
	define('PDF_MARGIN_HEADER', 5);
	define('PDF_MARGIN_FOOTER', 10);
	define('PDF_MARGIN_TOP', 27);
	define('PDF_MARGIN_BOTTOM', 25);
	define('PDF_MARGIN_LEFT', 15);
	define('PDF_MARGIN_RIGHT', 15);
	define('PDF_FONT_NAME_MAIN', 'helvetica');
	define('PDF_FONT_SIZE_MAIN', 10);
	define('PDF_FONT_NAME_DATA', 'helvetica');
	define('PDF_FONT_SIZE_DATA', 8);
	define('PDF_FONT_MONOSPACED', 'courier');
	define('PDF_IMAGE_SCALE_RATIO', 1.25);
	define('HEAD_MAGNIFICATION', 1.1);
	define('K_CELL_HEIGHT_RATIO', 1.25);
	define('K_TITLE_MAGNIFICATION', 1.3);
	define('K_SMALL_RATIO', 2/3);
	define('K_THAI_TOPCHARS', true);
	define('K_TCPDF_CALLS_IN_HTML', true);
	define('K_TCPDF_THROW_EXCEPTION_ERROR', false);


	if (! empty($conf->global->MAIN_USE_FPDF) && ! empty($conf->global->MAIN_DISABLE_FPDI))
		return "Error MAIN_USE_FPDF and MAIN_DISABLE_FPDI can't be set together";

	// We use by default TCPDF else FPDF
	if (empty($conf->global->MAIN_USE_FPDF)) require_once TCPDF_PATH.'tcpdf.php';
	else require_once FPDF_PATH.'fpdf.php';

	// We need to instantiate fpdi object (instead of tcpdf) to use merging features. But we can disable it.
	if (empty($conf->global->MAIN_DISABLE_FPDI)) require_once FPDI_PATH.'fpdi.php';

	//$arrayformat=pdf_getFormat();
	//$format=array($arrayformat['width'],$arrayformat['height']);
	//$metric=$arrayformat['unit'];

	// Protection and encryption of pdf
	if (empty($conf->global->MAIN_USE_FPDF) && ! empty($conf->global->PDF_SECURITY_ENCRYPTION))
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
		if (class_exists('FPDI')) $pdf = new FPDI($pagetype,$metric,$format);
		else $pdf = new TCPDF($pagetype,$metric,$format);
		// For TCPDF, we specify permission we want to block
		$pdfrights = array('modify','copy');

		$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
		$pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
		$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
	}
	else
	{
		if (class_exists('FPDI')) $pdf = new FPDI($pagetype,$metric,$format);
		else $pdf = new TCPDF($pagetype,$metric,$format);
	}

	// If we use FPDF class, we may need to add method writeHTMLCell
	if (! empty($conf->global->MAIN_USE_FPDF) && ! method_exists($pdf, 'writeHTMLCell'))
	{
		// Declare here a class to overwrite FPDI to add method writeHTMLCell
		/**
		*	This class if a enhanced FPDI class that support method writeHTMLCell
		*/
		class FPDI_DolExtended extends FPDI
        {
			/**
			 * __call
			 *
			 * @param 	string	$method		Method
			 * @param 	mixed	$args		Arguments
			 * @return 	void
			 */
			public function __call($method, $args)
			{
				if (isset($this->$method)) {
					$func = $this->$method;
					$func($args);
				}
			}

			/**
			 * writeHTMLCell
			 *
			 * @param	int		$w				Width
			 * @param 	int		$h				Height
			 * @param 	int		$x				X
			 * @param 	int		$y				Y
			 * @param 	string	$html			Html
			 * @param 	int		$border			Border
			 * @param 	int		$ln				Ln
			 * @param 	boolean	$fill			Fill
			 * @param 	boolean	$reseth			Reseth
			 * @param 	string	$align			Align
			 * @param 	boolean	$autopadding	Autopadding
			 * @return 	void
			 */
			public function writeHTMLCell($w, $h, $x, $y, $html = '', $border = 0, $ln = 0, $fill = false, $reseth = true, $align = '', $autopadding = true)
			{
				$this->SetXY($x,$y);
				$val=str_replace('<br>',"\n",$html);
				//$val=dol_string_nohtmltag($val,false,'ISO-8859-1');
				$val=dol_string_nohtmltag($val,false,'UTF-8');
				$this->MultiCell($w,$h,$val,$border,$align,$fill);
			}
		}

		$pdf2=new FPDI_DolExtended($pagetype,$metric,$format);
		unset($pdf);
		$pdf=$pdf2;
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
	global $conf;

	if (! empty($conf->global->MAIN_PDF_FORCE_FONT)) return $conf->global->MAIN_PDF_FORCE_FONT;

	$font='Helvetica'; // By default, for FPDI, or ISO language on TCPDF
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
 * Return height to use for Logo onot PDF
 *
 * @param	string		$logo		Full path to logo file to use
 * @param	bool		$url		Image with url (true or false)
 * @return	number
 */
function pdf_getHeightForLogo($logo, $url = false)
{
	$height=22; $maxwidth=130;
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$tmp=dol_getImageSize($logo, $url);
	if ($tmp['height'])
	{
		$width=round($height*$tmp['width']/$tmp['height']);
		if ($width > $maxwidth) $height=$height*$maxwidth/$width;
	}
	//print $tmp['width'].' '.$tmp['height'].' '.$width; exit;
	return $height;
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

	if (! empty($sourcecompany->state_id) && empty($sourcecompany->departement)) $sourcecompany->departement=getState($sourcecompany->state_id); //TODO: Deprecated
	if (! empty($sourcecompany->state_id) && empty($sourcecompany->state)) $sourcecompany->state=getState($sourcecompany->state_id);
	if (! empty($targetcompany->state_id) && empty($targetcompany->departement)) $targetcompany->departement=getState($targetcompany->state_id);

	if ($mode == 'source')
	{
		$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($sourcecompany))."\n";

		if (empty($conf->global->MAIN_PDF_DISABLESOURCEDETAILS))
		{
			// Tel
			if ($sourcecompany->phone) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($sourcecompany->phone);
			// Fax
			if ($sourcecompany->fax) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($sourcecompany->fax);
			// EMail
			if ($sourcecompany->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($sourcecompany->email);
			// Web
			if ($sourcecompany->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($sourcecompany->url);
		}
	}

	if ($mode == 'target')
	{
		if ($usecontact)
		{
			$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset($targetcontact->getFullName($outputlangs,1));

			if (!empty($targetcontact->address)) {
				$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcontact))."\n";
			}else {
				$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany))."\n";
			}
			// Country
			if (!empty($targetcontact->country_code) && $targetcontact->country_code != $sourcecompany->country_code) {
				$stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcontact->country_code))."\n";
			}
			else if (empty($targetcontact->country_code) && !empty($targetcompany->country_code) && ($targetcompany->country_code != $sourcecompany->country_code)) {
				$stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code))."\n";
			}


			if (! empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS))
			{
				// Tel
				if ($targetcontact->phone_pro) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($targetcontact->phone_pro);
				// Fax
				if ($targetcontact->fax) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcontact->fax);
				// EMail
				if ($targetcontact->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcontact->email);
				// Web
				if ($targetcontact->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcontact->url);
			}
		}
		else
		{
			$stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->convToOutputCharset(dol_format_address($targetcompany))."\n";
			// Country
			if (!empty($targetcompany->country_code) && $targetcompany->country_code != $sourcecompany->country_code) $stringaddress.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$targetcompany->country_code))."\n";

			if (! empty($conf->global->MAIN_PDF_ADDALSOTARGETDETAILS))
			{
				// Tel
				if ($targetcompany->phone) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($targetcompany->phone);
				// Fax
				if ($targetcompany->fax) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($targetcompany->fax);
				// EMail
				if ($targetcompany->email) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($targetcompany->email);
				// Web
				if ($targetcompany->url) $stringaddress .= ($stringaddress ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($targetcompany->url);
			}
		}

		// Intra VAT
		if (empty($conf->global->MAIN_TVAINTRA_NOT_IN_ADDRESS))
		{
			if ($targetcompany->tva_intra) $stringaddress.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($targetcompany->tva_intra);
		}

		// Professionnal Ids
		if (! empty($conf->global->MAIN_PROFID1_IN_ADDRESS) && ! empty($targetcompany->idprof1))
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId1",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof1);
		}
		if (! empty($conf->global->MAIN_PROFID2_IN_ADDRESS) && ! empty($targetcompany->idprof2))
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId2",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof2);
		}
		if (! empty($conf->global->MAIN_PROFID3_IN_ADDRESS) && ! empty($targetcompany->idprof3))
		{
			$tmp=$outputlangs->transcountrynoentities("ProfId3",$targetcompany->country_code);
			if (preg_match('/\((.+)\)/',$tmp,$reg)) $tmp=$reg[1];
			$stringaddress.="\n".$tmp.': '.$outputlangs->convToOutputCharset($targetcompany->idprof3);
		}
		if (! empty($conf->global->MAIN_PROFID4_IN_ADDRESS) && ! empty($targetcompany->idprof4))
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

	$savx=$pdf->getX(); $savy=$pdf->getY();

	$watermark_angle=atan($h/$w)/2;
	$watermark_x_pos=0;
	$watermark_y_pos=$h/3;
	$watermark_x=$w/2;
	$watermark_y=$h/3;
	$pdf->SetFont('','B',40);
	$pdf->SetTextColor(255,192,203);
	//rotate
	$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$k,($h-$watermark_y)*$k,-$watermark_x*$k,-($h-$watermark_y)*$k));
	//print watermark
	$pdf->SetXY($watermark_x_pos,$watermark_y_pos);
	$pdf->Cell($w-20,25,$outputlangs->convToOutputCharset($text),"",2,"C",0);
	//antirotate
	$pdf->_out('Q');

	$pdf->SetXY($savx,$savy);
}


/**
 *  Show bank informations for PDF generation
 *
 *  @param	PDF			&$pdf            		Object PDF
 *  @param  Translate	$outputlangs     		Object lang
 *  @param  int			$curx            		X
 *  @param  int			$cury            		Y
 *  @param  Account		$account         		Bank account object
 *  @param  int			$onlynumber      		Output only number
 *  @param	int			$default_font_size		Default font size
 *  @return	void
 */
function pdf_bank(&$pdf,$outputlangs,$curx,$cury,$account,$onlynumber=0,$default_font_size=10)
{
	global $mysoc, $conf;

	$diffsizetitle=(empty($conf->global->PDF_DIFFSIZE_TITLE)?3:$conf->global->PDF_DIFFSIZE_TITLE);
	$diffsizecontent=(empty($conf->global->PDF_DIFFSIZE_CONTENT)?4:$conf->global->PDF_DIFFSIZE_CONTENT);

	$pdf->SetXY($curx, $cury);

	if (empty($onlynumber))
	{
		$pdf->SetFont('','B',$default_font_size - $diffsizetitle);
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
			$pdf->SetFont('','',$default_font_size - $diffsizecontent);
			$pdf->SetXY($curx, $cury);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
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
				$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_banque), 0, 'C', 0);
				$pdf->SetXY($curx, $cury+1);
				$curx+=$tmplength;
				$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankCode"), 0, 'C', 0);
				if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
			}
			if ($val == 'desk')
			{
				// Desk
				$tmplength=18;
				$pdf->SetXY($curx, $cury+5);
				$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->code_guichet), 0, 'C', 0);
				$pdf->SetXY($curx, $cury+1);
				$curx+=$tmplength;
				$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("DeskCode"), 0, 'C', 0);
				if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
			}
			if ($val == 'number')
			{
				// Number
				$tmplength=24;
				$pdf->SetXY($curx, $cury+5);
				$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->number), 0, 'C', 0);
				$pdf->SetXY($curx, $cury+1);
				$curx+=$tmplength;
				$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumber"), 0, 'C', 0);
				if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
			}
			if ($val == 'key')
			{
				// Key
				$tmplength=13;
				$pdf->SetXY($curx, $cury+5);
				$pdf->SetFont('','',$default_font_size - 3);$pdf->MultiCell($tmplength, 3, $outputlangs->convToOutputCharset($account->cle_rib), 0, 'C', 0);
				$pdf->SetXY($curx, $cury+1);
				$curx+=$tmplength;
				$pdf->SetFont('','B',$default_font_size - 4);$pdf->MultiCell($tmplength, 3, $outputlangs->transnoentities("BankAccountNumberKey"), 0, 'C', 0);
				if (empty($onlynumber)) $pdf->line($curx, $cury+1, $curx, $cury+8);
			}
		}

		$curx=$savcurx;
		$cury+=10;
	}
	else
	{
		$pdf->SetFont('','B',$default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Bank").': ' . $outputlangs->convToOutputCharset($account->bank), 0, 'L', 0);
		$cury+=3;

		$pdf->SetFont('','B',$default_font_size - $diffsizecontent);
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("BankAccountNumber").': ' . $outputlangs->convToOutputCharset($account->number), 0, 'L', 0);
		$cury+=3;

		if ($diffsizecontent <= 2) $cury+=1;
	}

	// Use correct name of bank id according to country
	$ibankey="IBANNumber";
	$bickey="BICNumber";
	if ($account->getCountryCode() == 'IN') $ibankey="IFSC";
	if ($account->getCountryCode() == 'IN') $bickey="SWIFT";

	$pdf->SetFont('','',$default_font_size - $diffsizecontent);

	if (empty($onlynumber) && ! empty($account->domiciliation))
	{
		$pdf->SetXY($curx, $cury);
		$val=$outputlangs->transnoentities("Residence").': ' . $outputlangs->convToOutputCharset($account->domiciliation);
		$pdf->MultiCell(100, 3, $val, 0, 'L', 0);
		//$nboflines=dol_nboflines_bis($val,120);
		//$cury+=($nboflines*3)+2;
		$tmpy=$pdf->getStringHeight(100, $val);
		$cury+=$tmpy;
	}
	else if (! $usedetailedbban) $cury+=1;

	if (! empty($account->iban))
	{
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($ibankey).': ' . $outputlangs->convToOutputCharset($account->iban), 0, 'L', 0);
		$cury+=3;
	}

	if (! empty($account->bic))
	{
		$pdf->SetXY($curx, $cury);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities($bickey).': ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);
	}

	return $pdf->getY();
}


/**
 *  Show footer of page for PDF generation
 *
 *	@param	PDF			&$pdf     		The PDF factory
 *  @param  Translate	$outputlangs	Object lang for output
 * 	@param	string		$paramfreetext	Constant name of free text
 * 	@param	Societe		$fromcompany	Object company
 * 	@param	int			$marge_basse	Margin bottom we use for the autobreak
 * 	@param	int			$marge_gauche	Margin left (no more used)
 * 	@param	int			$page_hauteur	Page height (no more used)
 * 	@param	Object		$object			Object shown in PDF
 * 	@param	int			$showdetails	Show company details into footer. This param seems to not be used by standard version.
 *  @param	int			$hidefreetext	1=Hide free text, 0=Show free text
 * 	@return	int							Return height of bottom margin including footer text
 */
function pdf_pagefoot(&$pdf,$outputlangs,$paramfreetext,$fromcompany,$marge_basse,$marge_gauche,$page_hauteur,$object,$showdetails=0,$hidefreetext=0)
{
	global $conf,$user;

	$outputlangs->load("dict");
	$line='';

	$dims=$pdf->getPageDimensions();

	// Line of free text
	if (empty($hidefreetext) && ! empty($conf->global->$paramfreetext))
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
	$freetextheight=0;
	if ($line)	// Free text
	{
		$width=20000; $align='L';	// By default, ask a manual break: We use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
		if (! empty($conf->global->MAIN_USE_AUTOWRAP_ON_FREETEXT)) {
			$width=200; $align='C';
		}
		$freetextheight=$pdf->getStringHeight($width,$line);
	}

	$marginwithfooter=$marge_basse + $freetextheight + (! empty($line1)?3:0) + (! empty($line2)?3:0) + (! empty($line3)?3:0) + (! empty($line4)?3:0);
	$posy=$marginwithfooter+0;

	if ($line)	// Free text
	{
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($width, 3, $line, 0, $align, 0);
		$posy-=$freetextheight;
	}

	$pdf->SetY(-$posy);
	$pdf->line($dims['lm'], $dims['hk']-$posy, $dims['wk']-$dims['rm'], $dims['hk']-$posy);
	$posy--;

	if (! empty($line1))
	{
		$pdf->SetFont('','B',7);
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line1, 0, 'C', 0);
		$posy-=3;
		$pdf->SetFont('','',7);
	}

	if (! empty($line2))
	{
		$pdf->SetFont('','B',7);
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line2, 0, 'C', 0);
		$posy-=3;
		$pdf->SetFont('','',7);
	}

	if (! empty($line3))
	{
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line3, 0, 'C', 0);
	}

	if (! empty($line4))
	{
		$posy-=3;
		$pdf->SetXY($dims['lm'],-$posy);
		$pdf->MultiCell($dims['wk']-$dims['rm'], 2, $line4, 0, 'C', 0);
	}

	// Show page nb only on iso languages (so default Helvetica font)
	if (pdf_getPDFFont($outputlangs) == 'Helvetica')
	{
		$pdf->SetXY(-20,-$posy);
		//print 'xxx'.$pdf->PageNo().'-'.$pdf->getAliasNbPages().'-'.$pdf->getAliasNumPage();exit;
		if (empty($conf->global->MAIN_USE_FPDF)) $pdf->MultiCell(11, 2, $pdf->PageNo().'/'.$pdf->getAliasNbPages(), 0, 'R', 0);
		else $pdf->MultiCell(11, 2, $pdf->PageNo().'/{nb}', 0, 'R', 0);
	}

	return $marginwithfooter;
}

/**
 *	Show linked objects for PDF generation
 *
 *	@param	PDF			&$pdf				Object PDF
 *	@param	object		$object				Object
 *	@param  Translate	$outputlangs		Object lang
 *	@param  int			$posx				X
 *	@param  int			$posy				Y
 *	@param	float		$w					Width of cells. If 0, they extend up to the right margin of the page.
 *	@param	float		$h					Cell minimum height. The cell extends automatically if needed.
 *	@param	int			$align				Align
 *	@param	string		$default_font_size	Font size
 *	@return	void
 */
function pdf_writeLinkedObjects(&$pdf,$object,$outputlangs,$posx,$posy,$w,$h,$align,$default_font_size)
{
	$linkedobjects = pdf_getLinkedObjects($object,$outputlangs);
	if (! empty($linkedobjects))
	{
		foreach($linkedobjects as $linkedobject)
		{
			$posy+=3;
			$pdf->SetXY($posx,$posy);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->MultiCell($w, $h, $linkedobject["ref_title"].' : '.$linkedobject["ref_value"], '', $align);

			if (! empty($linkedobject["date_title"]) && ! empty($linkedobject["date_value"]))
			{
				$posy+=3;
				$pdf->SetXY($posx,$posy);
				$pdf->MultiCell($w, $h, $linkedobject["date_title"].' : '.$linkedobject["date_value"], '', $align);
			}
		}
	}

	return $pdf->getY();
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
 * 	@return	void
 */
function pdf_writelinedesc(&$pdf,$object,$i,$outputlangs,$w,$h,$posx,$posy,$hideref=0,$hidedesc=0,$issupplierline=0)
{
	global $db, $conf, $langs, $hookmanager;

	$reshook=0;
	if (is_object($hookmanager) && ( ($object->lines[$i]->product_type == 9 && ! empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		$parameters = array('pdf'=>$pdf,'i'=>$i,'outputlangs'=>$outputlangs,'w'=>$w,'h'=>$h,'posx'=>$posx,'posy'=>$posy,'hideref'=>$hideref,'hidedesc'=>$hidedesc,'issupplierline'=>$issupplierline,'special_code'=>$special_code);
		$action='';
		$reshook=$hookmanager->executeHooks('pdf_writelinedesc',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	}
	if (empty($reshook))
	{
		$labelproductservice=pdf_getlinedesc($object,$i,$outputlangs,$hideref,$hidedesc,$issupplierline);
		// Description
		$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, false, true, 'J',true);
		return $labelproductservice;
	}
}

/**
 *  Return line description translated in outputlangs and encoded into htmlentities and with <br>
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

	$idprod=(! empty($object->lines[$i]->fk_product)?$object->lines[$i]->fk_product:false);
	$label=(! empty($object->lines[$i]->label)?$object->lines[$i]->label:(! empty($object->lines[$i]->product_label)?$object->lines[$i]->product_label:''));
	$desc=(! empty($object->lines[$i]->desc)?$object->lines[$i]->desc:(! empty($object->lines[$i]->description)?$object->lines[$i]->description:''));
	$ref_supplier=(! empty($object->lines[$i]->ref_supplier)?$object->lines[$i]->ref_supplier:(! empty($object->lines[$i]->ref_fourn)?$object->lines[$i]->ref_fourn:''));    // TODO Not yet saved for supplier invoices, only supplier orders
	$note=(! empty($object->lines[$i]->note)?$object->lines[$i]->note:'');

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
	if (! empty($desc) && ($desc != $label))
	{
		if ($libelleproduitservice && empty($hidedesc))
		{
			$libelleproduitservice.='__N__';
		}

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
			$libelleproduitservice=$outputlangs->transnoentitiesnoconv("DiscountFromDeposit",$discount->ref_facture_source);
			// Add date of deposit
			if (! empty($conf->global->INVOICE_ADD_DEPOSIT_DATE)) echo ' ('.dol_print_date($discount->datec,'day','',$outputlangs).')';
		}
		else
		{
			if ($idprod)
			{
				if (empty($hidedesc)) $libelleproduitservice.=$desc;
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
			if (! empty($conf->global->PRODUCT_ADD_TYPE_IN_DOCUMENTS))   // In standard mode, we do not show this
			{
				if ($prodser->isservice())
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
				if ($issupplierline) $ref_prodserv = $prodser->ref.' ('.$outputlangs->transnoentitiesnoconv("SupplierRef").' '.$ref_supplier.')';   // Show local ref and supplier ref
				else $ref_prodserv = $prodser->ref; // Show local ref only

				$ref_prodserv .= " - ";
			}

			$libelleproduitservice=$prefix_prodserv.$ref_prodserv.$libelleproduitservice;
		}
	}

	// Add an additional description for the category products
	if (! empty($conf->global->CATEGORY_ADD_DESC_INTO_DOC) && $idprod && ! empty($conf->categorie->enabled))
	{
		include_once DOL_DOCUMENT_ROOT.'/categories/class/categorie.class.php';
		$categstatic=new Categorie($db);
		// recovering the list of all the categories linked to product
		$tblcateg=$categstatic->containing($idprod,0);
		foreach ($tblcateg as $cate)
		{
			// Adding the descriptions if they are filled
			$desccateg=$cate->add_description;
			if ($desccateg)
				$libelleproduitservice.='__N__'.$desccateg;
		}
	}

	if (! empty($object->lines[$i]->date_start) || ! empty($object->lines[$i]->date_end))
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
		$libelleproduitservice.="__N__".$period;
		//print $libelleproduitservice;
	}

	// Now we convert \n into br
	if (dol_textishtml($libelleproduitservice)) $libelleproduitservice=preg_replace('/__N__/','<br>',$libelleproduitservice);
	else $libelleproduitservice=preg_replace('/__N__/',"\n",$libelleproduitservice);
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
 * 	@return	void
 */
function pdf_getlinenum($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

	if (is_object($hookmanager) && (($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || ! empty($object->lines[$i]->fk_parent_line)))
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
 * 	@return	void
 */
function pdf_getlineref($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

	if (is_object($hookmanager) && (($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || ! empty($object->lines[$i]->fk_parent_line)))
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
 * 	@return	void
 */
function pdf_getlineref_supplier($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

	if (is_object($hookmanager) && ( ($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code) ) || ! empty($object->lines[$i]->fk_parent_line) ) )
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
 * 	@return	void
 */
function pdf_getlinevatrate($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

	if (is_object($hookmanager) && (($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || ! empty($object->lines[$i]->fk_parent_line)))
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
 * 	@return	void
 */
function pdf_getlineupexcltax($object,$i,$outputlangs,$hidedetails=0)
{
	global $conf, $hookmanager;

	$sign=1;
	if (isset($object->type) && $object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

	if (is_object($hookmanager) && (($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || ! empty($object->lines[$i]->fk_parent_line)))
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
		$action='';
		return $hookmanager->executeHooks('pdf_getlineupexcltax',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
	}
	else
	{
		if (empty($hidedetails) || $hidedetails > 1) return price($sign * $object->lines[$i]->subprice, 0, $outputlangs);
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
	global $hookmanager;

	if (is_object($hookmanager) && (($object->lines[$i]->product_type == 9 && !empty($object->lines[$i]->special_code)) || ! empty($object->lines[$i]->fk_parent_line)))
	{
		$special_code = $object->lines[$i]->special_code;
		if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
		foreach($object->hooks as $modules)
		{
			if (method_exists($modules[$special_code],'pdf_getlineupwithtax')) return $modules[$special_code]->pdf_getlineupwithtax($object,$i,$outputlangs,$hidedetails);
		}
	}
	else
	{
		if (empty($hidedetails) || $hidedetails > 1) return price(($object->lines[$i]->subprice) + ($object->lines[$i]->subprice)*($object->lines[$i]->tva_tx)/100, 0, $outputlangs);
	}
}

/**
 *	Return line quantity
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param  Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
 *  @return	void
 */
function pdf_getlineqty($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

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
 * 	@return	void
 */
function pdf_getlineqty_asked($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

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
 * 	@return	void
 */
function pdf_getlineqty_shipped($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

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
 * 	@return	void
 */
function pdf_getlineqty_keeptoship($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

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
 * 	@return	void
 */
function pdf_getlineremisepercent($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

	include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';

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
 * 	@return	string							Return total of line excl tax
 */
function pdf_getlinetotalexcltax($object,$i,$outputlangs,$hidedetails=0)
{
	global $conf, $hookmanager;

	$sign=1;
	if (isset($object->type) && $object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

	if ($object->lines[$i]->special_code == 3)
	{
		return $outputlangs->transnoentities("Option");
	}
	else
	{
		if (is_object($hookmanager) && (($object->lines[$i]->product_type == 9 && ! empty($object->lines[$i]->special_code)) || ! empty($object->lines[$i]->fk_parent_line)))
		{
			$special_code = $object->lines[$i]->special_code;
			if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action='';
			return $hookmanager->executeHooks('pdf_getlinetotalexcltax',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		}
		else
		{
			if (empty($hidedetails) || $hidedetails > 1) return price($sign * $object->lines[$i]->total_ht, 0, $outputlangs);
		}
	}
	return '';
}

/**
 *	Return line total including tax
 *
 *	@param	Object		$object				Object
 *	@param	int			$i					Current line number
 *  @param 	Translate	$outputlangs		Object langs for output
 *  @param	int			$hidedetails		Hide value (0 = no, 1 = yes, 2 = just special lines)
 *  @return	string							Return total of line incl tax
 */
function pdf_getlinetotalwithtax($object,$i,$outputlangs,$hidedetails=0)
{
	global $hookmanager;

	if ($object->lines[$i]->special_code == 3)
	{
		return $outputlangs->transnoentities("Option");
	}
	else
	{
		if (is_object($hookmanager) && (($object->lines[$i]->product_type == 9 && ! empty($object->lines[$i]->special_code)) || ! empty($object->lines[$i]->fk_parent_line)))
		{
			$special_code = $object->lines[$i]->special_code;
			if (! empty($object->lines[$i]->fk_parent_line)) $special_code = $object->getSpecialCode($object->lines[$i]->fk_parent_line);
			$parameters = array('i'=>$i,'outputlangs'=>$outputlangs,'hidedetails'=>$hidedetails,'special_code'=>$special_code);
			$action='';
			return $hookmanager->executeHooks('pdf_getlinetotalwithtax',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		}
		else
		{
			if (empty($hidedetails) || $hidedetails > 1) return price(($object->lines[$i]->total_ht) + ($object->lines[$i]->total_ht)*($object->lines[$i]->tva_tx)/100, 0, $outputlangs);
		}
	}
	return '';
}

/**
 *	Return total quantity of products and/or services
 *
 *	@param	Object		$object				Object
 *	@param	string		$type				Type
 *  @param  Translate	$outputlangs		Object langs for output
 * 	@return	void
 */
function pdf_getTotalQty($object,$type,$outputlangs)
{
	global $hookmanager;

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
			else if ($type==9 && is_object($hookmanager) && (($object->lines[$i]->product_type == 9 && ! empty($object->lines[$i]->special_code)) || ! empty($object->lines[$i]->fk_parent_line)))
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
 * 	Return linked objects
 *
 * 	@param	object		$object			Object
 * 	@param	Translate	$outputlangs	Object lang for output
 * 	@return	void
 */
function pdf_getLinkedObjects($object,$outputlangs)
{
	global $hookmanager;

	$linkedobjects=array();

	$object->fetchObjectLinked();

	foreach($object->linkedObjects as $objecttype => $objects)
	{
		if ($objecttype == 'propal')
		{
			$outputlangs->load('propal');
			$num=count($objects);
			for ($i=0;$i<$num;$i++)
			{
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefProposal");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($objects[$i]->ref);
				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("DatePropal");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($objects[$i]->date,'day','',$outputlangs);
			}
		}
		else if ($objecttype == 'commande')
		{
			$outputlangs->load('orders');
			$num=count($objects);
			for ($i=0;$i<$num;$i++)
			{
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefOrder");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($objects[$i]->ref) . ($objects[$i]->ref_client ? ' ('.$objects[$i]->ref_client.')' : '');
				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("OrderDate");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($objects[$i]->date,'day','',$outputlangs);
			}
		}
		else if ($objecttype == 'contrat')
		{
			$outputlangs->load('contracts');
			$num=count($objects);
			for ($i=0;$i<$num;$i++)
			{
				$linkedobjects[$objecttype]['ref_title'] = $outputlangs->transnoentities("RefContract");
				$linkedobjects[$objecttype]['ref_value'] = $outputlangs->transnoentities($objects[$i]->ref);
				$linkedobjects[$objecttype]['date_title'] = $outputlangs->transnoentities("DateContract");
				$linkedobjects[$objecttype]['date_value'] = dol_print_date($objects[$i]->date_contrat,'day','',$outputlangs);
			}
		}
	}

	// For add external linked objects
	if (is_object($hookmanager))
	{
		$parameters = array('linkedobjects' => $linkedobjects, 'outputlangs'=>$outputlangs);
		$action='';
		$hookmanager->executeHooks('pdf_getLinkedObjects',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks
		if (! empty($hookmanager->resArray)) $linkedobjects = $hookmanager->resArray;
	}

	return $linkedobjects;
}

/**
 * Return dimensions to use for images onto PDF checking that width and height are not higher than
 * maximum (16x32 by default).
 *
 * @param	string		$realpath		Full path to photo file to use
 * @return	array						Height and width to use to output image (in pdf user unit, so mm)
 */
function pdf_getSizeForImage($realpath)
{
	global $conf;

	$maxwidth=(empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?16:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH);
	$maxheight=(empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_HEIGHT)?32:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_HEIGHT);
	include_once DOL_DOCUMENT_ROOT.'/core/lib/images.lib.php';
	$tmp=dol_getImageSize($realpath);
	if ($tmp['height'])
	{
		$width=(int) round($maxheight*$tmp['width']/$tmp['height']);	// I try to use maxheight
		if ($width > $maxwidth)	// Pb with maxheight, so i use maxwidth
		{
			$width=$maxwidth;
			$height=(int) round($maxwidth*$tmp['height']/$tmp['width']);
		}
		else	// No pb with maxheight
		{
			$height=$maxheight;
		}
	}
	return array('width'=>$width,'height'=>$height);
}

?>
