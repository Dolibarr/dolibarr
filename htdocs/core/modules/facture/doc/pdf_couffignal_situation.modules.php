<?php
/* Copyright (C) 2004-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012	  	Christophe Battarel <christophe.battarel@altairis.fr>
 * Copyright (C) 2012		Cédric Salvador	 <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud  <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015		Marcos García		<marcosgdf@gmail.com>
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
 *	\file		htdocs/core/modules/facture/doc/pdf_crabe.modules.php
 *	\ingroup	facture
 *	\brief	  File of class to generate customers invoices from crabe model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
// TSubtotal
if (isModEnabled('subtotal')) dol_include_once('/subtotal/class/subtotal.class.php');


/**
 *	Class to manage PDF invoice template Crabe
 */
class pdf_couffignal_situation extends ModelePDFFactures
{
	var $db;
	var $name;
	var $description;
	var $type;

	var $phpmin = array(4, 3, 0); // Minimum version of PHP required by module
	var $version = 'dolibarr';

	var $page_width;
	var $page_height;
	var $format;
	var $margin_left;
	var	$margin_right;
	var	$marge_haute;
	var	$margin_bottom;

	var $issuer;	// Object for the issuing company

	/**
	 * @var bool Situation invoice type
	 */
	public $situationinvoice;

	/**
	 * @var float X position for the situation progress column
	 */
	public $posxprogress_current;
	public $posxprogress_prec;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db	  Database handler
	 */
	function __construct($db)
	{
		global $conf, $langs, $mysoc, $object;

		$langs->load("main");
		$langs->load("bills");
		$langs->load("btp@btp");

		$this->db = $db;
		$this->name = "couffignal_situation";
		$this->description = "Facture de situation custom";

		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_width = $formatarray['width'];
		$this->page_height = $formatarray['height'];
		$this->format = array($this->page_width, $this->page_height);
		$this->margin_left = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->margin_right = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute  = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->margin_bottom  = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);
		$this->heightforfooter = 0; // Defined later, once payments are known
		$this->heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
		$this->heightforinfotot = $this->margin_bottom + 20;	// Height reserved to output the footer (value include bottom margin)

		$this->option_logo = 1;					// Affiche logo
		$this->option_tva = 1;					 // Manage option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;				 // Affiche mode reglement
		$this->option_condreg = 1;				 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;	  // Affiche code produit-service
		$this->option_multilang = 1;				// Dispo en plusieurs langues
		$this->option_escompte = 1;				// Affiche si il y a eu escompte
		$this->option_credit_note = 1;			 // Support credit notes
		$this->option_freetext = 1;					// Support add of a personalised text
		$this->option_draft_watermark = 1;			// Support add of a watermark on drafts

		$this->hideref = 1;							// Hide internal reference in invoice lines
		$this->hidedesc = 0;						// Hide description in invoice lines


		// Define various properties
		$this->issuer=$mysoc;
		if (empty($this->issuer->country_code)) $this->issuer->country_code = substr($langs->defaultlang, -2);	// By default, if was not defined
		$this->franchise =! $mysoc->tva_assuj;
		$this->tva = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = false;
		$this->sign = (isset($object->type) && $object->type == 2 && getDolGlobalInt('INVOICE_POSITIVE_CREDIT_NOTE')) ? -1 : 1;

		// Fill $this->atleastonediscount
		if (isset($object->lines)) {
			for ($i = 0 ; $i < count($object->lines) ; $i++) {
				if ($object->lines[$i]->remise_percent) {
					$this->atleastonediscount = true;
					break;
				}
			}
		}


		// Define width of columns -- Width in virtual point, normalized later
		$this->_columns = array(
			'Designation' => array(
				'WidthPt' => 30, 
				'PreName' => '', 
				'NameKey' => 'Designation', 
				'PostName' => '', 
				'TextAlign' => 'L', 
				'PostText' => '', 
				'TitleAlign' => 'L'
			), 
			'VAT' => array(
				'WidthPt' => 5, 
				'PreName' => '', 
				'NameKey' => 'VAT', 
				'PostName' => '', 
				'PostText' => '', 
				'TextAlign' => 'C', 
				'TitleAlign' => 'C'
			), 
			'Qty' => array(
				'WidthPt' => 5, 
				'PreName' => '', 
				'NameKey' => 'Qty', 
				'PostName' => '', 
				'TextAlign' => 'R', 
				'PostText' => '', 
				'TitleAlign' => 'C'
			), 
			'Unit' => array(
				'WidthPt' => 5, 
				'PreName' => '', 
				'NameKey' => 'Unit', 
				'PostName' => '', 
				'TextAlign' => 'R', 
				'PostText' => '', 
				'TitleAlign' => 'C'
			), 
			'PriceUHT' => array(
				'WidthPt' => 10, 
				'PreName' => '', 
				'NameKey' => 'PriceUHT', 
				'PostName' => '', 
				'TextAlign' => 'R', 
				'PostText' => '', 
				'TitleAlign' => 'C'
			), 
			'TotalSituationCompleteHT' => array(
				'WidthPt' => 10, 
				'PreName' => '', 
				'NameKey' => 'Sommes', 
				'PostName' => '', 
				'TextAlign' => 'R', 
				'PostText' => '', 
				'TitleAlign' => 'C'
			), 
			'AvcmtMarg' => array(
				'WidthPt' => 7, 
				'PreName' => '', 
				'NameKey' => 'AvcmtMarg', 
				'PostName' => '', 
				'TextAlign' => 'C', 
				'PostText' => '%', 
				'TitleAlign' => 'C'
			), 
			'AvcmtCumulAct' => array(
				'WidthPt' => 7, 
				'PreName' => '', 
				'NameKey' => 'AvcmtCumulAct', 
				'PostName' => " % Nouveau", 
				'TextAlign' => 'C', 
				'PostText' => '%', 
				'TitleAlign' => 'C'
			), 
			'AvcmtCumulPrec' => array(
				'WidthPt' => 7, 
				'PreName' => '', 
				'NameKey' => 'AvcmtCumulPrec', 
				'PostName' => " % Prédécent", 
				'TextAlign' => 'C', 
				'PostText' => '%', 
				'TitleAlign' => 'C'
			), 
			'Reduction' => array(
				'WidthPt' => 5, 
				'PreName' => '', 
				'NameKey' => 'ReductionShort', 
				'PostName' => '', 
				'TextAlign' => 'R', 
				'PostText' => '', 
				'TitleAlign' => 'C'
			), 
			'TotalHT' => array(
				'WidthPt' => 10, 
				'PreName' => '', 
				'NameKey' => 'TotalHT', 
				'PostName' => '', 
				'TextAlign' => 'R', 
				'PostText' => '', 
				'TitleAlign' => 'C'
			)
		);
		$this->columns = array(); // Indexed array filled by calling $this->compute_columns_size

		// Drop columns keys according to config
		if (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') && getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN')) {unset($this->_columns['VAT']);}
		if (!getDolGlobalInt('PRODUCT_USE_UNITS')) {unset($this->_columns['Unit']);}
		if (!$this->atleastonediscount) {unset($this->_columns['Reduction']);}
	}

	/**
	 *  Function to compute size of the columns
	 * 	Populate attribute columns in $this with 2nd dimension width & start
	 *
	 *  @return	 int		 					1=OK, 0=KO
	 */
	function compute_columns_size()
	{
		// Get scaling values
		$this->columns = array(); // In case of refresh, reset the columns
		$total_width_pt = array_sum(array_column($this->_columns, 'WidthPt'));
		$total_width_mm = $this->page_width - $this->margin_right - $this->margin_left;
		
		// Populate and normalize
		foreach ($this->_columns as $key => $properties) {
			$properties["ColName"] = $key;
			$properties["Start"] = 0;
			$properties["Width"] = $properties["WidthPt"] / $total_width_pt * $total_width_mm;
			unset($properties["WidthPt"]);
			array_push($this->columns, $properties);
		}
		for ($i=0; $i < count($this->columns); $i++) {
			if ($i == 0) {
				$this->columns[$i]['Start'] = $this->margin_left;
			} else {
				$this->columns[$i]['Start'] = $this->columns[$i-1]['Start'] + $this->columns[$i-1]['Width'];
			}
		}

		return 1;
	}


	/**
	 *  Function to print a description line
	 *
	 *  @param  TCPDF			$pdf					PDF object
	 *	@param	Object			$object				Current Invoice Object
	 *	@param	int				$i					Current line number
	 *  @param  Translate		$outputlangs		Object lang for output
	 *  @param  int				$w					Width
	 *  @param  int				$h					Height
	 *  @param  int				$posx				Pos x
	 *  @param  int				$posy				Pos y
	 *  @param  int				$this->hideref				Hide reference
	 *  @param  int				$this->hidedesc			Hide description
	 * 	@param	int				$issupplierline		Is it a line for a supplier object ?
	 *  @return int		 					1=OK, 0=KO
	 */
	function custom_pdf_writelinedesc(&$pdf, $object, $i, $outputlangs, $w, $h, $posx, $posy, $hideref = 0, $hidedesc = 0, $issupplierline = 0)
	{
		/** pdf.lib.php pdf_writelinedesc without hooks, and managing Sutotal **/
		$fill = 0;

		/* Manage SubTotal lines */
		if (class_exists('TSubtotal') && TSubtotal::isTitle($object->lines[$i])) {	
				$labelproductservice = $object->lines[$i]->label;
				// Clean the first numbers in the label
				$a = preg_split('/\s+/', $labelproductservice);
				array_shift($a);
				$labelproductservice = '<b>' . implode(' ', $a) . '</b>';
				$pdf->SetFillColor(233, 233, 233);
				$posy += $h;
				$fill = 1;
		} elseif (class_exists('TSubtotal') && TSubtotal::isSubtotal($object->lines[$i])) {
			$labelproductservice = "<b>Total :</b>";
		} else {
			$labelproductservice = pdf_getlinedesc($object, $i, $outputlangs, $hideref, $hidedesc, $issupplierline);
		}

		// Fix bug of some HTML editors that replace links <img src="http://localhostgit/viewimage.php?modulepart=medias&file=image/efd.png" into <img src="http://localhostgit/viewimage.php?modulepart=medias&amp;file=image/efd.png"
		// We make the reverse, so PDF generation has the real URL.
		$nbrep = 0;
		$labelproductservice = preg_replace('/(<img[^>]*src=")([^"]*)(&amp;)([^"]*")/', '\1\2&\4', $labelproductservice, -1, $nbrep);

		if (getDolGlobalString('MARGIN_TOP_ZERO_UL')) {
			$pdf->setListIndentWidth(5);
			$TMarginList = ['ul' => [['h'=>0.1, ], ['h'=>0.1, ]], 'li' => [['h'=>0.1, ], ], ];
			$pdf->setHtmlVSpace($TMarginList);
		}

		$pdf->writeHTMLCell($w, $h, $posx, $posy, $outputlangs->convToOutputCharset($labelproductservice), 0, 1, $fill, true, 'J', true);

		return 1;
	}


	/**
	 *  Prepare array with the values per ColName
	 *
	 *  @param		Object Facture		$object				Object to generate
	 *  @param 		int 				$i 					Index of current line
	 *  @param		Translate			$outputlangs		Lang output object
	 *  @param		int					$hidedetails		Do not show line details
	 *  @return	 array		 							The array resulting, with the values to print
	 */
	function get_values_for_line($object, $i, $outputlangs, $hidedetails = 0)
	{
		global $user, $langs, $conf, $db, $hookmanager;

		/* Get Compute values */
		$l = $object->lines[$i];
		// Price Situation Complete
		$complete_price = $this->calcul_price_total($l->qty, $l->subprice, $l->remise_percent, $l->tva_tx, $l->localtax1_tx, $l->localtax2_tx, 0, 'HT', $l->info_bits, $l->product_type)[0];
		// Progress
		$prev_prog_global = $object->lines[$i]->get_prev_progress($object->id);
		$cumulated_progress = (float)str_replace('%', '', pdf_getlineprogress($object, $i, $outputlangs, $hidedetails));
		$marg_prog = $cumulated_progress - $prev_prog_global;
		// Total price
		$total_HT = $marg_prog == 0 ? price(0) : pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
		
		/* Fill Array */					
		$values = array(
			'Designation' => '', 
			'VAT' => pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails), 
			'Qty' => pdf_getlineqty($object, $i, $outputlangs, $hidedetails), 
			'PriceUHT' => pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails), 
			'TotalSituationCompleteHT' => price($complete_price), 
			'AvcmtMarg' => round($cumulated_progress - $prev_prog_global, 2), 
			'AvcmtCumulAct' => round($cumulated_progress, 2), 
			'AvcmtCumulPrec' => round($prev_prog_global, 2), 
			'Reduction' => pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails), 
			'TotalHT' => price(round( (float) price2num($total_HT), 2))
		);
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) { $values['Unit'] = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager); }
		// TODO: is the call to Main useful ?

		/* Manage special lines */
		// Support lines attributes
		if (!empty($l->do_not_display_qty)) {$values['Qty'] = '';}

		// TODO Manage this case	if (empty($object->lines[$i]->is_line_paiement)) {$values['Qty'] = '';}
		
		// Support modSubtotal lines
		if (class_exists('TSubtotal')) {
			if (TSubtotal::isTitle($object->lines[$i]) || TSubtotal::isSubtotal($object->lines[$i]) || TSubtotal::isFreeText($object->lines[$i])) {	
				$values = array(
					'Designation' => '', 
					'Unit' => '', 
					'VAT' => '', 
					'Qty' => '', 
					'PriceUHT' => '', 
					'TotalSituationCompleteHT' => '', 
					'AvcmtMarg' => '', 
					'AvcmtCumulAct' => '', 
					'AvcmtCumulPrec' => '', 
					'Reduction' => '', 
					'TotalHT' => '', 
				);
			}
			if (TSubtotal::isSubtotal($object->lines[$i])) {
				$sum = 0;
				for ($j=1; $j <= $i; $j++) {
					// Sum all the values in lines before the current total, until we reach a Title
					$l = $object->lines[$i-$j];
					if (TSubtotal::isTitle($l)) { break; }
					// Compute price an sum it
					$prev_prog_global = $l->get_prev_progress($object->id);
					$cumulated_progress = (float)str_replace('%', '', pdf_getlineprogress($object, $i-$j, $outputlangs, $hidedetails));
					$marg_prog = $cumulated_progress - $prev_prog_global;
					$total_HT = $marg_prog == 0 ? price(0) : pdf_getlinetotalexcltax($object, $i-$j, $outputlangs, $hidedetails);
					$sum += price2num($total_HT);
				}
				$values['TotalHT'] = price(round($sum, 2));
			}
		}

		return $values;
	}


	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Object		$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$this->hidedesc			Do not show desc
	 *  @param		int			$this->hideref			Do not show ref
	 *  @return	 int		 					1=OK, 0=KO
	 */
	function write_file($object, $outputlangs, $srctemplatepath='', $hidedetails=0, $hidedesc=0, $hideref=0)
	{
		global $user, $langs, $conf, $mysoc, $db, $hookmanager;

		/**** Warning if not situation invoice ****/
		if (empty($object) || $object->type != Facture::TYPE_SITUATION) {
			setEventMessage($langs->trans('BtpWarningsObjectIsNotASituation'), 'warnings');
			return 1;
		}

		/*** Lang init ***/
		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalInt('MAIN_USE_FPDF')) $outputlangs->charset_output='ISO-8859-1';
		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");

		/**** Prepare file ****/
		if ($conf->facture->dir_output) {
			/**** Prepare / Init ****/

			// Object init
			$object->fetch_thirdparty();
			$deja_regle = $object->getSommePaiement();
			$amount_credit_notes_included = $object->getSumCreditNotesUsed();
			$amount_deposits_included = $object->getSumDepositsUsed();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->facture->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->facture->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}
			if (! file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			/* File exists, we create PDF */
			if (file_exists($dir)) {
				// Pdfgeneration hook
				if (! is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);	// Note that $action and $object may have been modified by some hooks

				// Set nblignes with the new facture lines content after hook
				$nblignes = count($object->lines);
				$nbpayments = count($object->getListOfPayments());

				// Create pdf instance
				$pdf=pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);
				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont('', '', $default_font_size);

				// Set path to the background PDF File
				if (!getDolGlobalInt('MAIN_DISABLE_FPDI') && getDolGlobalInt('MAIN_ADD_PDF_BACKGROUND')) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/' . getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
					$tplidx = $pdf->importPage(1);
				}

				// Initialize PDF
				$pdf->Open();
				$pdf->SetDrawColor(128, 128, 128);
				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Invoice")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (getDolGlobalInt('MAIN_DISABLE_PDF_COMPRESSION')) $pdf->SetCompression(false);
				$pdf->SetMargins($this->margin_left, $this->marge_haute, $this->margin_right);	// Left, Top, Right
				$this->heightforfooter = 30 + (4*$nbpayments); // Height reserved to output the info and total part and payment part
				
				/*** 1ere Page BTP ***/
				// Create page and init
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);

				// Print head
				$tab_top = $this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0, 0, 0);
				$tab_top += 5;
				
				// Footer
				$this->_pagefoot($pdf, $object, $outputlangs, 1);
				
				// Recap table
				$bottomlasttab = $this->_tableauBtp($pdf, $object, $tab_top, 0, $outputlangs, 0, 0, $object->multicurrency_code);
				
				// Notes
				$tab_top = $bottomlasttab + 5 + 25; // TODO : Clean the 25 (probably removed somewhere else)
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if (getDolGlobalInt('MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE')) {
					// Get first sale rep
					if (is_object($object->thirdparty)) {
						$salereparray = $object->thirdparty->getSalesRepresentatives($user);
						$salerepobj = new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (!empty($salerepobj->signature)) $notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
					}
				}
				if ($notetoshow) {
					$note_width = $this->page_width - $this->margin_left - $this->margin_right;
					
					// Write text over Rect
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->SetXY($this->margin_left, $tab_top - 5);
					$pdf->MultiCell(0, 2, $outputlangs->transnoentities("Notes").' :', '', 'L');
					
					// Write text
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell($note_width - 2, 3, $this->margin_left + 1, $tab_top + 1, dol_htmlentitiesbr($notetoshow), 0, 1);
					$height_note = $pdf->GetY() - $tab_top;

					// Draw rectangle
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->margin_left, $tab_top, $note_width, $height_note + 2);
				}

				/*** Page 2 & following ***/

				// Change orientation
				$pdf->AddPage();
				$pdf->setPage(2);
				$this->page_width = 297;
				$this->page_height = 210;
				$pdf->setPageOrientation('L', 1, $this->heightforfooter+$this->heightforfreetext+$this->heightforinfotot);

				// Initialize new page
				$tab_top = $this->_pagehead($pdf, $object, 0, $outputlangs, FALSE);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0, 0, 0);
				$tab_top += 15;

				/*// Incoterm
				$height_incoterms = 0;
				if (isModEnabled('incoterm')) {
					$desc_incoterms = $object->getIncotermsForPDF();
					if ($desc_incoterms) {
						$tab_top = 40;

						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->margin_left + 1, $tab_top + 1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
						$nexY = $pdf->GetY();
						$height_incoterms = $nexY-$tab_top;

						$pdf->SetDrawColor(192, 192, 192);
						$pdf->Rect($this->margin_left, $tab_top, $this->page_width - $this->margin_left - $this->margin_right, $height_incoterms + 1);

						$tab_top = $nexY + 6;
					}
				}*/

				// Main table
				$this->_main_table($pdf, $object, $bottomlasttab, $outputlangs, $tab_top, 2, $hidedetails);
				
				// Affiche zone infos
				$posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Affiche zone totaux
				$posy = $this->_tableau_tot($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

				// Affiche zone versements
				if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included) {
					$posy = $this->setNewPage($posy, $pdf, $object, $outputlangs, 170);
					$posy=$this->_tableau_versements($pdf, $object, $posy, $outputlangs);
				}

				// Footer
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				// Close and generate
				$pdf->Close();
				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);	// Note that $action and $object may have been modified by some hooks

				if (!empty(getDolGlobalString('MAIN_UMASK')))
				@chmod($file, octdec(getDolGlobalString('MAIN_UMASK')));
				return 1;	// No error
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}
		else {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
			return 0;
		}
		
		$this->error = $langs->transnoentities("ErrorUnknown");
		return 0;	// Default error
	}


	/**
	 *  Show payments table
	 *
	 *  @param	PDF			$pdf			Object PDF
	 *  @param  Object		$object		 Object invoice
	 *  @param  int			$posy			Position y in PDF
	 *  @param  Translate	$outputlangs	Object langs for output
	 *  @return int			 			<0 if KO, >0 if OK
	 */
	function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$this->sign=1;
		if ($object->type == 2 && getDolGlobalInt('INVOICE_POSITIVE_CREDIT_NOTE')) $this->sign=-1;

		$tab3_posx = 120;
		$tab3_top = $posy + 8;
		$tab3_width = 80;
		$tab3_height = 4;
		if ($this->page_width < 210) // To work with US executive format
		{
			$tab3_posx -= 20;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$title=$outputlangs->transnoentities("PaymentsAlreadyDone");
		if ($object->type == 2) $title=$outputlangs->transnoentities("PaymentsBackAlreadyDone");

		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetXY($tab3_posx, $tab3_top - 4);
		$pdf->MultiCell(60, 3, $title, 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top, $tab3_posx+$tab3_width, $tab3_top);

		$pdf->SetFont('', '', $default_font_size - 4);
		$pdf->SetXY($tab3_posx, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+21, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+40, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+58, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top-1+$tab3_height, $tab3_posx+$tab3_width, $tab3_top-1+$tab3_height);

		$y=0;

		$pdf->SetFont('', '', $default_font_size - 4);


		// Loop on each deposits and credit notes included
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc, ";
		$sql.= " re.description, re.fk_facture_source, ";
		$sql.= " f.type, f.datef";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re, ".MAIN_DB_PREFIX ."facture as f";
		$sql.= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = ".$object->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			$invoice=new Facture($this->db);
			while ($i < $num)
			{
				$y+=3;
				$obj = $this->db->fetch_object($resql);

				if ($obj->type == 2) $text=$outputlangs->trans("CreditNote");
				elseif ($obj->type == 3) $text=$outputlangs->trans("Deposit");
				else $text=$outputlangs->trans("UnknownType");

				$invoice->fetch($obj->fk_facture_source);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($obj->datef, 'day', false, $outputlangs, true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($obj->amount_ttc, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $text, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		// Loop on each payment
		// TODO Call getListOfPaymentsgetListOfPayments instead of hard coded sql
		$sql = "SELECT p.datep as date, p.fk_paiement, p.num_paiement as num, pf.amount as amount, ";
		$sql.= " cp.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON p.fk_paiement = cp.id";
		$sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = ".$object->id;
		//$sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = 1";
		$sql.= " ORDER BY p.datep";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$y+=3;
				$row = $this->db->fetch_object($resql);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date), 'day', false, $outputlangs, true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($this->sign * $row->amount, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

	}


	/**
	 *	Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *	@param		PDF			$pdf	 		Object PDF
	 *	@param		Object		$object			Object to show
	 *	@param		int			$posy			Y
	 *	@param		Translate	$outputlangs	Langs object
	 *	@return	void
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', '', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->issuer->country_code == 'FR' && $this->franchise == 1)
		{
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->margin_left, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		$posxval=52;

		// Show payments conditions
		if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement))
		{
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->margin_left, $posy);
			$titre = $outputlangs->transnoentities("PaymentConditions").':';
			$pdf->MultiCell(80, 4, $titre, 0, 'L');

			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
			$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement=str_replace('\n', "\n", $lib_condition_paiement);
			$pdf->MultiCell(80, 4, $lib_condition_paiement, 0, 'L');

			$posy=$pdf->GetY()+3;
		}

		if ($object->type != 2)
		{
			// Check a payment mode is defined
			if (empty($object->mode_reglement_code)
			&& !getDolGlobalString('FACTURE_CHQ_NUMBER')
			&& !getDolGlobalString('FACTURE_RIB_NUMBER'))
			{
				$this->error = $outputlangs->transnoentities("ErrorNoPaiementModeConfigured");
			}
			// Avoid having any valid PDF with setup that is not complete
			elseif (($object->mode_reglement_code == 'CHQ' && !getDolGlobalString('FACTURE_CHQ_NUMBER') && empty($object->fk_account) && empty($object->fk_bank))
				|| ($object->mode_reglement_code == 'VIR' && !getDolGlobalString('FACTURE_RIB_NUMBER') && empty($object->fk_account) && empty($object->fk_bank)))
			{
				$outputlangs->load("errors");

				$pdf->SetXY($this->margin_left, $posy);
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$this->error = $outputlangs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup", $object->mode_reglement_code);
				$pdf->MultiCell(80, 3, $this->error, 0, 'L', 0);
				$pdf->SetTextColor(0, 0, 0);

				$posy=$pdf->GetY()+1;
			}

			// Show payment mode
			if ($object->mode_reglement_code
			&& $object->mode_reglement_code != 'CHQ'
			&& $object->mode_reglement_code != 'VIR')
			{
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->SetXY($this->margin_left, $posy);
				$titre = $outputlangs->transnoentities("PaymentMode").':';
				$pdf->MultiCell(80, 5, $titre, 0, 'L');

				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxval, $posy);
				$lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell(80, 5, $lib_mode_reg, 0, 'L');

				$posy=$pdf->GetY()+2;
			}

			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ')
			{
				// Si mode reglement non force ou si force a CHQ
				if (getDolGlobalString('FACTURE_CHQ_NUMBER'))
				{
					$diffsizetitle=getDolGlobalInt('PDF_DIFFSIZE_TITLE', 3);

					if (getDolGlobalInt('FACTURE_CHQ_NUMBER') > 0)
					{
						$account = new Account($this->db);
						$account->fetch(getDolGlobalInt('FACTURE_CHQ_NUMBER'));

						$pdf->SetXY($this->margin_left, $posy);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $account->proprio), 0, 'L', 0);
						$posy=$pdf->GetY()+1;

						if (!getDolGlobalInt('MAIN_PDF_HIDE_CHQ_ADDRESS'))
						{
							$pdf->SetXY($this->margin_left, $posy);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($account->owner_address), 0, 'L', 0);
							$posy=$pdf->GetY()+2;
						}
					}
					if (getDolGlobalInt('FACTURE_CHQ_NUMBER') == -1)
					{
						$pdf->SetXY($this->margin_left, $posy);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $this->issuer->name), 0, 'L', 0);
						$posy=$pdf->GetY()+1;

						if (!getDolGlobalInt('MAIN_PDF_HIDE_CHQ_ADDRESS'))
						{
							$pdf->SetXY($this->margin_left, $posy);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($this->issuer->getFullAddress()), 0, 'L', 0);
							$posy=$pdf->GetY()+2;
						}
					}
				}
			}

			// If payment mode not forced or forced to VIR, show payment with BAN
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR')
			{
				if (! empty($object->fk_account) || ! empty($object->fk_bank) || getDolGlobalString('FACTURE_RIB_NUMBER'))
				{
					$bankid=(empty($object->fk_account) ? getDolGlobalString('FACTURE_RIB_NUMBER') : $object->fk_account);
					if (! empty($object->fk_bank)) $bankid=$object->fk_bank;	// For backward compatibility when object->fk_account is forced with object->fk_bank
					$account = new Account($this->db);
					$account->fetch($bankid);

					$curx=$this->margin_left;
					$cury=$posy;

					$posy=pdf_bank($pdf, $outputlangs, $curx, $cury, $account, 0, $default_font_size);

					$posy+=2;
				}
			}
		}

		return $posy;
	}

	/**
	 *	Show main detailed lines
	 *
	 *	@param	PDF			$pdf			(ref) Object PDF
	 *	@param  Facture		$object		 Object invoice
	 *	@param	Translate	$outputlangs	Objet langs
	 *  @param	int			$tab_top		Top position y of the table
	 *  @param	int			$bottomlasttab	(ref) Bottom position y of the lasttable
	 *
	 *	@return int							OK=1; NOK=0
	 */
	function _main_table(&$pdf, $object, &$bottomlasttab, $outputlangs, $tab_top, $hidedetails) {
		// Initialize position tracking variables
		$curY = $tab_top;
		$nexY = $tab_top;
		$nblignes = count($object->lines);
		$hidetop = 0;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Loop on each lines
		$this->compute_columns_size(); // Refresh columns infos

		// Store every page tab_height for clean grid display
		$tab_height_in_page = array();

		for ($i = 0; $i < $nblignes; $i++) {
			/***** Manage taxes *****/
			$taxes = $this->get_taxes($object, $this->sign);
			$this->tva = $taxes["tva"];
			$this->localtax1 = $taxes["localtax1"];
			$this->localtax2 = $taxes["localtax2"];

			/**** Support for special endlines (internal) ****/
			if ($object->lines[$i]->special_code == 10050172) {
				// Only ensure proper VAT management
				continue;
			}

			/***** Initialize for line *****/
			$curX = $this->columns[0]['Start'];
			$pdf->SetFont('', '', $default_font_size - 1);	// Into loop to work with multipage
			$pdf->SetTextColor(0, 0, 0);
			$origin_page = $pdf->getPage();

			/***** Description *****/
			// Support for bold lines
			if(!empty($object->lines[$i]->is_bold) && $object->lines[$i]->is_bold) {
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', 'B', $default_font_size - 1);
			}

			// Trick values for Subtotal lines
			$pdf->page_width = $this->page_width;
			$pdf->margin_right = $this->margin_right;
			foreach ($this->columns as $col) {
				if ($col['ColName'] == 'TotalHT') {
					$pdf->postotalht = $col['Start'];
					break;
				}
			}
			$pdf->postotalht = $this->page_width - $this->margin_right - $this->margin_left;

			// Try to write, testing if pagebreak
			$origin_page = $pdf->getPage();
			$pdf->startTransaction();
			$this->custom_pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->columns[0]['Width'], 3, $curX, $curY, $this->hideref, $this->hidedesc);
			$nexY = $pdf->GetY();
			$can_be_last_page = $nexY < ($this->page_height - ($this->heightforfooter + $this->heightforfreetext + $this->heightforinfotot));
			$can_be_on_normal_page = $nexY < ($this->page_height - $this->heightforfooter);

			if (!$can_be_on_normal_page) {
				// Add the needed page
				$pdf->rollbackTransaction(true);
				$tab_height_in_page[$origin_page] = $curY - $tab_top;
				$curY = $this->setNewPage($nexY, $pdf, $object, $outputlangs, $this->page_height - $this->heightforfooter);
				$pdf->SetFont('', '', $default_font_size - 1);
				$curY = $tab_top;
				$this->custom_pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->columns[0]['Width'], 3, $curX, $curY, $this->hideref, $this->hidedesc);
			}
			if (!$can_be_last_page) {
				$pdf->setPageOrientation('L', 1, $this->heightforfooter);	// The only function to edit the bottom margin of current page to set it.
			}
			
			$pdf->commitTransaction();

			// As page may have changed
			$nexY = $pdf->GetY();
			$pdf->setTopMargin($this->marge_haute);
			$pdf->setPageOrientation('L', 1, 0);	// The only function to edit the bottom margin of current page to set it.


			/***** Write all columns values *****/
			// Get values
			$values = $this->get_values_for_line($object, $i, $outputlangs, $hidedetails);
			// Print
			$pdf->SetFont('', '', $default_font_size - 1);
			foreach ($this->columns as $col) {
				// Skip Description
				if ($col['ColName'] == "Designation") continue;
				$pdf->SetXY($col['Start'] + 0.5, $curY);
				$txt = $values[$col['ColName']];
				if ($txt != '') { $txt .= $col['PostText'];}
				$pdf->MultiCell($col['Width'] - 1, 3, $txt, 0, $col['TextAlign']);
			}

			/***** Add intern dashed line *****/
			// If not the last line
			if (getDolGlobalInt('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblignes - 1)) {
				$pdf->SetLineStyle(array('dash'=>'1, 1', 'color' => array(80, 80, 80)));
				$pdf->line($this->margin_left, $nexY + 1, $this->page_width - $this->margin_right, $nexY + 1);
				$pdf->SetLineStyle(array('dash'=>0));
			}
			$nexY += 2;	// Passe espace entre les lignes

			$curY = $nexY;
		}

		/*** Last page ***/
		// If the last line was to large to be on the last page but small enough to fit on a normal page, we need to add the final page (whose table will be empty)
		if (!$can_be_last_page && $can_be_on_normal_page) {
			$tab_height_in_page[$origin_page] = $curY;
			$pdf->AddPage();
			if (!empty($tplidx)) $pdf->useTemplate($tplidx);
			if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) $this->_pagehead($pdf, $object, 0, $outputlangs);
		}
		$pdf->setPageOrientation('L', 1, ($this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter));	// The only function to edit the bottom margin of current page to set it.

		
		/***** Grid for all pages but the last one *****/
		$nb_page = $pdf->getPage();
		for ($p=2; $p < $nb_page; $p++) {
			$pdf->setPage($p);
			$this->_grid_and_title($pdf, $tab_top, $tab_height_in_page[$p], 0, $outputlangs, 0, 1, $object->multicurrency_code);
		}

		/* Grid for last page */
		$pdf->setPage($nb_page);
		$this->_grid_and_title($pdf, $tab_top, $this->page_height - $tab_top - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
		$bottomlasttab = $this->page_height - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter + 1;
	}

	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			$pdf			Object PDF
	 *	@param  Facture		$object		 Object invoice
	 *	@param  int			$deja_regle	 Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf, $mysoc;

		$this->sign=1;
		if ($object->type == 2 && getDolGlobalInt('INVOICE_POSITIVE_CREDIT_NOTE')) $this->sign=-1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('', '', $default_font_size - 1);

		// Tableau total
		$col1x = 120; $col2x = 190;
		if ($this->page_width < 210) {$col2x-=20;}// To work with US executive format
		$largcol2 = ($this->page_width - $this->margin_right - $col2x);

		$useborder = 0;

		// Pourcentage global d'avancement
		$totalFacture = 0;
		$totalAvancement = 0;
		// TODO AMA -- Remplacer avec un appel au global progress over invoice.
		foreach ($object->lines as $line) {
			if(!class_exists('TSubtotal') || !TSubtotal::isModSubtotalLine($line)){
				$divider = $line->situation_percent > 0 ? $line->situation_percent / 100  : 1;
				$totalFacture += $line->total_ht /  $divider;
				$totalAvancement+=$line->total_ht;
			}
		}
		if (!empty($totalFacture)) $avancementGlobal = $totalAvancement / $totalFacture * 100;
		else $avancementGlobal = 0;

		// Get previous situation
		if (empty($object->tab_previous_situation_invoice)) $object->fetchPreviousNextSituationInvoice();
		$TPreviousInvoice = $object->tab_previous_situation_invoice;

		// Compute amount to be paid
		$total_a_payer = 0;
		foreach ($TPreviousInvoice as &$fac) {
			$total_a_payer += $fac->total_ht;
		}
		$total_a_payer += $object->total_ht;

		if (empty($avancementGlobal)) {
			$total_a_payer = 0;
		} else {
			$total_a_payer = $total_a_payer * 100 / $avancementGlobal;
		}

		// Todo : Fix incorrect amount later on
		/*if(!empty($TPreviousInvoice)){
			$pdf->setY($tab2_top);
			$posy = $pdf->GetY();

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->SetXY($col1x, $posy);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("BtpTotalProgress", $avancementGlobal), 0, 'L', 1);

			$pdf->SetXY($col2x, $posy);
			$pdf->MultiCell($largcol2, $tab2_hl, price($total_a_payer*$avancementGlobal/100, 0, $outputlangs), 0, 'R', 1);
			$pdf->SetFont('', '', $default_font_size - 2);

			$posy += $tab2_hl;

			$last_invoice = end($TPreviousInvoice);
			$posy = $this->setNewPage($posy, $pdf, $object, $outputlangs, 180);

			// Cumul TVA précédent
			$pdf->SetFillColor(255, 255, 255);
			$pdf->SetXY($col1x, $posy);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("PDFCrabeBtpTitle", $last_invoice->situation_counter).' '.$outputlangs->transnoentities("TotalHTCum"), 0, 'L', 1);

			$pdf->SetXY($col2x, $posy);
			foreach ($TPreviousInvoice as $prev_invoice) {
				$total_cumulated += $prev_invoice->total_ht;
			}
			$pdf->MultiCell($largcol2, $tab2_hl, ' - '.price($total_cumulated, 0, $outputlangs), 0, 'R', 1);

			$posy += $tab2_hl;
			$pdf->setY($posy);
			$posy = $this->setNewPage($posy,  $pdf, $object, $outputlangs);
			$tab2_top = $posy;
		}*/

		$special_endline = $object->marginal_special_lines($outputlangs);

		// Total HT
		$index = 1;
		$posy += $tab2_hl;
		$tab2_top = $this->setNewPage($posy, $pdf, $object, $outputlangs);
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);
		$total_ht = (isModEnabled('multicurrency') && $object->multicurrency_tx != 1 ? $object->multicurrency_total_ht : $object->total_ht);
		foreach ($special_endline as $i => $line) {
			$total_ht -= $line['amountHT'];
		}
		$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($largcol2, $tab2_hl, price($this->sign * ($total_ht + (! empty($object->remise)?$object->remise:0)), 0, $outputlangs), 0, 'R', 1);

		$total_ttc = (isModEnabled('multicurrency') && $object->multiccurency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;
		
		// Retenue de prorata
		if ($object->prorata_discount > 0) {
			$index++;
			$tab2_top = $this->setNewPage($tab2_top, $pdf, $object, $outputlangs);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFillColor(255, 255, 255);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("ProrataRetain"), $useborder, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price(round(-$this->sign * $object->prorata_discount, 2), 0, $outputlangs), $useborder, 'R', 1);
			$total_ttc -= $object->prorata_discount;
		}

		foreach ($special_endline as $i => $line) {
			$index++;
			$tab2_top = $this->setNewPage($tab2_top, $pdf, $object, $outputlangs);
			//$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFillColor(255, 255, 255);
			//$pdf->MultiCell($col2x-$col1x, $tab2_hl, $line['name'] . ' HT', $useborder, 'L', 1);
			$pdf->writeHTMLCell($col2x-$col1x, $tab2_hl, $col1x, $tab2_top + $tab2_hl * $index, $line['name'] . ' HT (TVA ' . $line['TVA'] . ')', 0, 1, 0, true, 'J', true);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price(round($this->sign * $line['amountHT'], 2), 0, $outputlangs), $useborder, 'R', 1);
		}

		// Show VAT by rates and total
		$pdf->SetFillColor(248, 248, 248);

		$this->atleastoneratenotnull=0;
		if (!getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT')) {
			$tvaisnull = ((! empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL') && $tvaisnull) {
				// Nothing to do
			} else {
				// VAT
				foreach($this->tva as $tvakey => $tvaval) {
					if ($tvakey != 0) {	// On affiche pas taux 0
						$this->atleastoneratenotnull++;
						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl='';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey=str_replace('*', '', $tvakey);
							$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat =$outputlangs->transnoentities("TotalVAT").' ';
						$totalvat.=vatrate($tvakey, 1).$tvacompl;
						$tab2_top = $this->setNewPage($tab2_top, $pdf, $object, $outputlangs);
						$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$tva_prorata = ((float) $tvaval) * ($object->prorata_rate / 100);
						$tvaval = $tvaval - $tva_prorata;
						$pdf->MultiCell($largcol2, $tab2_hl, price(round($tvaval, 2), 0, $outputlangs), 0, 'R', 1);
						$total_ttc -= $tva_prorata;
					}
				}

				// Revenue stamp
				if (price2num($object->revenuestamp) != 0) {
					$index++;
					$tab2_top = $this->setNewPage($tab2_top, $pdf, $object, $outputlangs);
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RevenueStamp"), $useborder, 'L', 1);

					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($this->sign * $object->revenuestamp), $useborder, 'R', 1);
				}

				// Total TTC
				$index++;
				$tab2_top = $this->setNewPage($tab2_top, $pdf, $object, $outputlangs);
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFillColor(224, 224, 224);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($this->sign * round($total_ttc, 2), 0, $outputlangs), $useborder, 'R', 1);
			}
		}

		$pdf->SetTextColor(0, 0, 0);

		$creditnoteamount=$object->getSumCreditNotesUsed();
		$depositsamount=$object->getSumDepositsUsed();
		//print "x".$creditnoteamount."-".$depositsamount;exit;
		$resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if ($object->paye) $resteapayer=0;

		// Already paid + Deposits
		if ($deja_regle > 0) {
			$index++;
			$tab2_top = $this->setNewPage($tab2_top, $pdf, $object, $outputlangs);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount, 0, $outputlangs), 0, 'R', 0);
		}

		// Credit note
		if ($creditnoteamount) {
			$index++;
			$tab2_top = $this->setNewPage($tab2_top, $pdf, $object, $outputlangs);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount, 0, $outputlangs), 0, 'R', 0);
		}

		// Escompte
		if ($object->close_code == Facture::CLOSECODE_DISCOUNTVAT) {
			$index++;
			$pdf->SetFillColor(255, 255, 255);
			$tab2_top =  $this->setNewPage($tab2_top, $pdf, $object, $outputlangs);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs), $useborder, 'R', 1);

			$resteapayer=0;
		}
			
		if ($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0) {
			// To pay
			$index++;
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(224, 224, 224);
			$tab2_top = $this->setNewPage($tab2_top, $pdf, $object, $outputlangs, 164);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price(round($resteapayer, 2), 0, $outputlangs), $useborder, 'R', 1);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor(0, 0, 0);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	/**
	 *	Show table for lines, full size except margins
	 *
	 *	@param		PDF			$pdf	 		Object PDF
	 *	@param		string		$tab_top		Top position of table
	 *	@param		string		$tab_height		Height of table (rectangle)
	 *	@param		int			$nexY			Y (not used)
	 *	@param		Translate	$outputlangs	Langs object
	 *	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *	@param		int			$hidebottom		Hide bottom bar of array
	 *	@param		string		$currency		Currency code
	 *	@return	void
	 */
	function _grid_and_title(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom=0, $currency='')
	{
		global $conf, $object;

		// We don't start at tab_top but 8 upper for the header line
		$tab_top_incl_header = $tab_top - 8;
		$tab_height += 8;

		// Refresh values in $this->columns
		$this->compute_columns_size();

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		/*** Amount in Currency ***/
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);
		if (empty($hidetop)) {
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency" . $currency));
			$pdf->SetXY($this->page_width - $this->margin_right - ($pdf->GetStringWidth($titre) + 3), $tab_top_incl_header - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);
		}
		
		/** Support for background Color Check if not elsewhere **/
		/*if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR'))
			$pdf->Rect($this->margin_left, $tab_top_incl_header, $this->page_width-$this->margin_right-$this->margin_left, 5, 'F', null, explode(', ', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));*/

		/*** Grid ***/
		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);
		$this->printRect($pdf, $this->margin_left, $tab_top_incl_header, $this->page_width - $this->margin_left - $this->margin_right, $tab_height, $hidetop, $hidebottom);
		foreach ($this->columns as $col) {
			$pdf->line($col['Start'], $tab_top_incl_header, $col['Start'], $tab_top_incl_header + $tab_height);
		}

		/*** Title line ***/
		if (empty($hidetop)) {
			foreach ($this->columns as $col) {
				$pdf->SetXY($col['Start'] + 0.5, $tab_top_incl_header);
				$pdf->MultiCell($col['Width'] - 1, 2, $col['PreName'] . $outputlangs->transnoentities($col['NameKey']) . $col['PostName'] , '', $col['TitleAlign']);
			}
			$pdf->line($this->margin_left, $tab_top, $this->page_width - $this->margin_right, $tab_top);
		}
	}

	/**
	 *	Show table BTP
	 *
	 *	@param		PDF			$pdf	 		Object PDF
	 *	@param		string		$tab_top		Top position of table
	 *	@param		string		$tab_height		Height of table (rectangle)
	 *	@param		int			$nexY			Y (not used)
	 *	@param		Translate	$outputlangs	Langs object
	 *	@param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *	@param		int			$hidebottom		Hide bottom bar of array
	 *	@param		string		$currency		Currency code
	 *	@return	void
	 */

	function _tableauBtp(&$pdf, $object, $tab_top, $nexY, $outputlangs, $hidetop=0, $hidebottom=0, $currency='')
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;
		$currency = !empty($currency) ? $currency : $conf->currency;
		
		// Access Data Situation
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		if (!empty($object)) $this->recap_lines = $this->_getDataSituation($object, $outputlangs, $default_font_size);

		// TODO à mettre dans le construct
		$this->posx_new_cumul = 95;
		$col_width = 35;
		$this->posx_cumul_anterieur = $this->posx_new_cumul + $col_width;
		$this->posx_month = $this->posx_cumul_anterieur + $col_width;


		// Amount in currency
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);
		
		if (empty($hidetop)) {
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($this->page_width - $this->margin_right - ($pdf->GetStringWidth($titre) + 3), $tab_top-4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			$width = $this->page_width-$this->margin_left-$this->margin_right-83;

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230, 230, 230';
			if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) 			{
				$pdf->Rect($this->posx_new_cumul-1, $tab_top, $width, 5, 'F', null, explode(', ', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
				$pdf->Rect($this->margin_left, $tab_top+92.5, $this->page_width-$this->margin_left-$this->margin_right, 5, 'F', null, explode(', ', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
			}
		}
		
		/* COUF Custom table */
		$rect_width = $this->page_width - $this->margin_left - $this->margin_right;
		$lines = array(
			$outputlangs->transnoentities("Marché") . ' : '. $outputlangs->convToOutputCharset($object->projet->title) . " (" . $object->projet->ref . ")", 
			$outputlangs->transnoentities("AdressMarche") . ' : '. $outputlangs->convToOutputCharset($object->projet->array_options['options_adresseduchantier']), 
			$outputlangs->transnoentities("SituationSerieTotal") . ' : '. price($object->getLastSituationCompletePrice()) . ' HT', 
		);
		if (!empty($object->projet->array_options['options_referencechantier'])) {
			array_unshift($lines, $outputlangs->transnoentities("RefMarche") . ' : '. $outputlangs->convToOutputCharset($object->projet->array_options['options_referencechantier']));
		}
		$h = 3;
		$pdf->SetFont('', 'B', $default_font_size - 1);
		foreach ($lines as $idx => $label) {
			$pdf->SetXY($this->margin_left + 2, $tab_top + $h);
			$pdf->MultiCell($rect_width - 4, 2, $label, '', 'L');
			$h += 4;
		}		
		$this->printRectBtp($pdf, $this->margin_left, $tab_top, $rect_width, $h + 3, $hidetop, $hidebottom);
		$tab_top += ($h+5);



		/****** Main Table on 1st page *****/
		/** Table Data **/
		$recap_columns = array(
			array(
				'name' => 'NewCumul', 
				'title' => $outputlangs->transnoentities("BtpNewCumul"), 
				'startX' => $this->posx_new_cumul-1, 
			), 
			array(
				'name' => 'PrevCumul', 
				'title' => $outputlangs->transnoentities("BtpAnteCumul"), 
				'startX' => $this->posx_cumul_anterieur-1, 
			), 
			array(
				'name' => 'Situation', 
				'title' => $outputlangs->transnoentities("Situation") . ' '. $object->situation_counter, 
				'startX' => $this->posx_month-1, 
			)
		);

		/** Display **/
		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Position variables
		$padding = 14;
		$inner_width = $col_width - $padding;
		$posy = $tab_top + 5;

		// Print lines
		$pdf->line($this->posx_new_cumul-1, 	$posy, $this->page_width - $this->margin_right, $posy);
		foreach ($this->recap_lines as $k => $line) {
			$posy += $line['spaceBefore'];
			// Names
			$pdf->SetFont('', $line['fontWeight'], $line['fontSize']);
			$pdf->writeHTMLCell(80, 2, $this->margin_left+2, $posy, $line['name'], 0, 1, 0, true, 'L', true);
			// Values
			foreach ($recap_columns as $c => $column) {
				$pdf->SetXY($column['startX'] + $padding/2, $posy);
				$pdf->MultiCell($inner_width, 2, $line['values'][$column['name']], '', $line['align']);
			}
			$posy += 4;
			$posy += $line['spaceAfter'];
			if ($line['Hline']) {
				$pdf->line(
					$line['Hline'] == 'full' ? $this->margin_left : $this->posx_new_cumul-1, 
					$posy, 
					$this->page_width - $this->margin_right, 
					$posy
				);
			}
		}

		// Skeleton (border, vert. lines and titles)
		$this->printRectBtp($pdf, $this->margin_left, $tab_top, $this->page_width - $this->margin_left - $this->margin_right, $posy - $tab_top, $hidetop, $hidebottom);
		foreach ($recap_columns as $k => $column) {
			$pdf->line($column['startX'], $tab_top, $column['startX'], $posy);
		}
		foreach ($recap_columns as $k => $column) {
			$pdf->SetXY($column['startX'], $tab_top + 0.5);
			$pdf->MultiCell(35, 2, $column['title'], '', 'C');
		}

		return $posy;
	}

	function _getDataSituation($object, $outputlangs, $default_font_size) {
		/* TODO - Put this function + get_taxes in Facture object, with caching mechanism */

		// Gather previous situation data
		$object->fetchPreviousNextSituationInvoice();
		$TPreviousInvoice = $object->tab_previous_situation_invoice;
		$facDerniereSituation = end($TPreviousInvoice);

		// Temp vars
		$cumul_anterieur_ht = $retenue_garantie = 0;
		$retenue_garantie_anterieure = 0;
		
		// Go over previous invoices
		$situation_series_vat = array();
		if (!empty($TPreviousInvoice)) {
			foreach ($TPreviousInvoice as $fac) {
				$cumul_anterieur_ht += $fac->total_ht;
				$retenue_garantie_anterieure += $fac->total_ttc * ($fac->array_options['options_retenue_garantie'] ?? 0) / 100;
				$situation_series_vat[] = $this->get_taxes($fac, $this->sign)['tva'];
			}
		}
		$nouveau_cumul = $cumul_anterieur_ht + $object->total_ht;

		// Manage VAT
		$nouveau_tva_marginal = $this->get_taxes($object, $this->sign, true)['tva'];
		$cumul_anterieur_tva = array();
		$nouveau_cumul_tva = $nouveau_tva_marginal;
		foreach ($situation_series_vat as $idx => $vat_tab) {
			foreach ($vat_tab as $rate => $value_for_rate) {
				// Manage cumul before this invoice
			 	if (!isset($cumul_anterieur_tva[$rate])) 	$cumul_anterieur_tva[$rate] = 0.0;
				$cumul_anterieur_tva[$rate] += $value_for_rate;
				// Manage cumul inc. this invoice
				if (!isset($nouveau_cumul_tva[$rate])) 	$nouveau_cumul_tva[$rate] = 0.0;
				$nouveau_cumul_tva[$rate] += $value_for_rate;
			}
		}

		// Prepare lines for recap table
		$travaux_total = $object->totalExeptSpecialLines();
		$travaux_cum_total = $facDerniereSituation ? $facDerniereSituation->totalExeptSpecialLines() : 0;
		$recap_lines = array();
		$recap_lines[] = array(
			'name' => $outputlangs->transnoentities("Travaux"), 
			'spaceBefore' => 4, 
			'spaceAfter' => 4, 
			'Hline' => true, 
			'align' => 'R', 
			'fontWeight' => 'B', 
			'fontSize' => $default_font_size - 1, 
			'values' => array(
				'NewCumul' => price(round($travaux_total, 2)), 
				'PrevCumul' => price(round($travaux_cum_total, 2)), 
				'Situation' => price(round($travaux_total - $travaux_cum_total, 2)), 
			), 
		);

		$j = count($recap_lines);

		// Merge/Cumul special_lines
		$cumulated_lines_previous = $facDerniereSituation ? $facDerniereSituation->getExtractSpecialLines($outputlangs) : array();
		$cumulated_lines_current = $object->getExtractSpecialLines($outputlangs);
		foreach ($cumulated_lines_current as $idx => $line) {
			$name = $line['name'];
			$total = (float) $line['amountHT'];
			$prev_cum_price = array_key_exists($idx, $cumulated_lines_previous) ? $cumulated_lines_previous[$idx]['amountHT'] : 0;
			$recap_lines[] = array(
				'name' => $outputlangs->convToOutputCharset($name), 
				'spaceBefore' => 0, 
				'spaceAfter' => 0, 
				'Hline' => false, 
				'align' => 'R', 
				'fontWeight' => '', 
				'fontSize' => $default_font_size - 1, 
				'values' => array(
					'NewCumul' => price(round($total, 2)), 
					'PrevCumul' => price(round($prev_cum_price, 2)), 
					'Situation' => price(round($total - $prev_cum_price, 2)), 
				), 
			);
		}
		$recap_lines[$j]['spaceBefore'] = 4;

		$recap_lines[] = array(
				'name' => $outputlangs->transnoentities("TotalHT"), 
				'spaceBefore' => 0, 
				'spaceAfter' => 4, 
				'Hline' => false, 
				'align' => 'R', 
				'fontWeight' => 'B', 
				'fontSize' => $default_font_size - 1, 
				'values' => array(
					'NewCumul' => price(round($nouveau_cumul, 2)), 
					'PrevCumul' => price(round($cumul_anterieur_ht, 2)), 
					'Situation' => price(round($object->total_ht, 2)), 
				), 
			);

		foreach($nouveau_cumul_tva as $tvarate => $tvaval) {
			if ((float)$tvarate != 0) {
				$prev_cumul_vat = array_key_exists($tvarate, $cumul_anterieur_tva) ? $cumul_anterieur_tva[$tvarate] : 0;
				$marginal_vat = array_key_exists($tvarate, $nouveau_tva_marginal) ? $nouveau_tva_marginal[$tvarate] : 0;
				$recap_lines[] = array(
					'name' => $outputlangs->transnoentities("VAT") . ' ' . explode('.', $tvarate)[0] . '%', 
					'spaceBefore' => 0, 
					'spaceAfter' => 0, 
					'Hline' => false, 
					'align' => 'R', 
					'fontWeight' => '', 
					'fontSize' => $default_font_size - 1, 
					'values' => array(
						'NewCumul' => price(round($tvaval, 2)), 
						'PrevCumul' => price(round($prev_cumul_vat, 2)), 
						'Situation' => price(round($marginal_vat, 2)), 
					), 
				);
			}
		}
		$recap_lines[] = array(
				'name' => $outputlangs->transnoentities("TotalTTC"), 
				'spaceBefore' => 0, 
				'spaceAfter' => 4, 
				'Hline' => 'full', 
				'align' => 'R', 
				'fontWeight' => 'B', 
				'fontSize' => $default_font_size - 1, 
				'values' => array(
					'NewCumul' => price(round($nouveau_cumul + array_sum($nouveau_cumul_tva), 2)), 
					'PrevCumul' => price(round($cumul_anterieur_ht + array_sum($cumul_anterieur_tva), 2)), 
					'Situation' => price(round($object->total_ht + $object->total_tva, 2)), 
				), 
			);
		$recap_lines[] = array(
				'name' => $outputlangs->transnoentities("BtpTotalSituationTTC"), 
				'spaceBefore' => 3, 
				'spaceAfter' => 0, 
				'Hline' => false, 
				'align' => 'C', 
				'fontWeight' => 'B', 
				'fontSize' => $default_font_size - 1, 
				'values' => array(
					'NewCumul' => price(round($nouveau_cumul + array_sum($nouveau_cumul_tva), 2)), 
					'PrevCumul' => price(round($cumul_anterieur_ht + array_sum($cumul_anterieur_tva), 2)), 
					'Situation' => price(round($object->total_ht + $object->total_tva, 2)), 
				), 
			);
		$recap_lines[] = array(
				'name' => '('.$outputlangs->transnoentities("SituationInvoiceProgressColTitle").')', 
				'spaceBefore' => 0, 
				'spaceAfter' => 3, 
				'Hline' => false, 
				'align' => 'C', 
				'fontWeight' => '', 
				'fontSize' => $default_font_size - 2, 
				'values' => array(
					'NewCumul' => '('. round($object->computeGlobalProgress()) . '%)', 
					'PrevCumul' => '('. round($object->computeGlobalProgress() - $object->computeMarginalProgress()) . '%)', 
					'Situation' => '('. round($object->computeMarginalProgress()) . '%)', 
				), 
			);

		return $recap_lines;

	}

	function calcul_price_total($qty, $pu, $remise_percent_ligne, $txtva, $uselocaltax1_rate, $uselocaltax2_rate, $remise_percent_global, $price_base_type, $info_bits, $type, $seller = '', $localtaxes_array='')
	{
		global $conf, $mysoc, $db;

		$result=array();

		// Clean parameters
		if (empty($txtva)) $txtva=0;
		if (empty($seller) || ! is_object($seller))
		{
			dol_syslog("calcul_price_total Warning: function is called with parameter seller that is missing", LOG_WARNING);
			if (!is_object($mysoc))	// mysoc may be not defined (during migration process)
			{
				$mysoc=new Societe($db);
				$mysoc->setMysoc($conf);
			}
			$seller=$mysoc;	// If sell is done to a customer, $seller is not provided, we use $mysoc
			//var_dump($seller->country_id);exit;
		}
		if (empty($localtaxes_array) || ! is_array($localtaxes_array))
		{
			dol_syslog("calcul_price_total Warning: function is called with parameter localtaxes_array that is missing", LOG_WARNING);
		}
		// Too verbose. Enable for debug only
		//dol_syslog("calcul_price_total qty=".$qty." pu=".$pu." remiserpercent_ligne=".$remise_percent_ligne." txtva=".$txtva." uselocaltax1_rate=".$uselocaltax1_rate." uselocaltax2_rate=".$uselocaltax2_rate);

		$countryid=$seller->country_id;
		if ($uselocaltax1_rate < 0) $uselocaltax1_rate=$seller->localtax1_assuj;
		if ($uselocaltax2_rate < 0) $uselocaltax2_rate=$seller->localtax2_assuj;

		// Now we search localtaxes information ourself (rates and types).
		$localtax1_type=0;
		$localtax2_type=0;

		if (is_array($localtaxes_array))
		{
			$localtax1_type = $localtaxes_array[0];
			$localtax1_rate = $localtaxes_array[1];
			$localtax2_type = $localtaxes_array[2];
			$localtax2_rate = $localtaxes_array[3];
		}
		else
		{
			$sql = "SELECT taux, localtax1, localtax2, localtax1_type, localtax2_type";
			$sql.= " FROM ".MAIN_DB_PREFIX."c_tva as cv";
			$sql.= " WHERE cv.taux = ".$txtva;
			$sql.= " AND cv.fk_pays = ".$countryid;
			dol_syslog("calcul_price_total search vat information", LOG_DEBUG);
			$resql = $db->query($sql);
			if ($resql)
			{
				$obj = $db->fetch_object($resql);
				if ($obj)
				{
					$localtax1_rate=$obj->localtax1;
					$localtax2_rate=$obj->localtax2;
					$localtax1_type=$obj->localtax1_type;
					$localtax2_type=$obj->localtax2_type;
					//var_dump($localtax1_rate.' '.$localtax2_rate.' '.$localtax1_type.' '.$localtax2_type);exit;
				}
			}
			else dol_print_error($db);
		}
		// initialize total (may be HT or TTC depending on price_base_type)
		$tot_sans_remise = $pu * $qty;
		$tot_avec_remise_ligne = $tot_sans_remise		* (1 - ($remise_percent_ligne / 100));
		$tot_avec_remise		= $tot_avec_remise_ligne * (1 - ($remise_percent_global / 100));

		// initialize result array
		for ($i=0; $i <= 15; $i++) $result[$i] = 0;

		// if there's some localtax including vat, we calculate localtaxes (we will add later)

		//If input unit price is 'HT', we need to have the totals with main VAT for a correct calculation
		if ($price_base_type != 'TTC')
		{
			$tot_sans_remise_wt = price2num($tot_sans_remise * (1 + ($txtva / 100)), 'MU');
			$tot_avec_remise_wt = price2num($tot_avec_remise * (1 + ($txtva / 100)), 'MU');
			$pu_wt = price2num($pu * (1 + ($txtva / 100)), 'MU');
		}
		else
		{
			$tot_sans_remise_wt = $tot_sans_remise;
			$tot_avec_remise_wt = $tot_avec_remise;
			$pu_wt = $pu;
		}

		//print 'rr'.$price_base_type.'-'.$txtva.'-'.$tot_sans_remise_wt."-".$pu_wt."-".$uselocaltax1_rate."-".$localtax1_rate."-".$localtax1_type."\n";

		$localtaxes = array(0, 0, 0);
		$apply_tax = false;
	  	switch($localtax1_type) {
		  case '2':	 // localtax on product or service
			$apply_tax = true;
			break;
		  case '4':	 // localtax on product
			if ($type == 0) $apply_tax = true;
			break;
		  case '6':	 // localtax on service
			if ($type == 1) $apply_tax = true;
			break;
		}
		if ($uselocaltax1_rate && $apply_tax) {
	  		$result[14] = price2num(($tot_sans_remise_wt * (1 + ( $localtax1_rate / 100))) - $tot_sans_remise_wt, 'MT');
	  		$localtaxes[0] += $result[14];

	  		$result[9] = price2num(($tot_avec_remise_wt * (1 + ( $localtax1_rate / 100))) - $tot_avec_remise_wt, 'MT');
	  		$localtaxes[1] += $result[9];

	  		$result[11] = price2num(($pu_wt * (1 + ( $localtax1_rate / 100))) - $pu_wt, 'MU');
	  		$localtaxes[2] += $result[11];
		}

		$apply_tax = false;
	  	switch($localtax2_type) {
		  case '2':	 // localtax on product or service
			$apply_tax = true;
			break;
		  case '4':	 // localtax on product
			if ($type == 0) $apply_tax = true;
			break;
		  case '6':	 // localtax on service
			if ($type == 1) $apply_tax = true;
			break;
		}
		if ($uselocaltax2_rate && $apply_tax) {
	  		$result[15] = price2num(($tot_sans_remise_wt * (1 + ( $localtax2_rate / 100))) - $tot_sans_remise_wt, 'MT');
	  		$localtaxes[0] += $result[15];

	  		$result[10] = price2num(($tot_avec_remise_wt * (1 + ( $localtax2_rate / 100))) - $tot_avec_remise_wt, 'MT');
	  		$localtaxes[1] += $result[10];

	  		$result[12] = price2num(($pu_wt * (1 + ( $localtax2_rate / 100))) - $pu_wt, 'MU');
	  		$localtaxes[2] += $result[12];
		}

		//dol_syslog("price.lib::calcul_price_total $qty, $pu, $remise_percent_ligne, $txtva, $price_base_type $info_bits");
		if ($price_base_type == 'HT')
		{
			// We work to define prices using the price without tax
			$result[6] = price2num($tot_sans_remise, 'MT');
			$result[8] = price2num($tot_sans_remise * (1 + ( (($info_bits & 1)?0:$txtva) / 100)) + $localtaxes[0], 'MT');	// If VAT "NPR" or not
			$result8bis= price2num($tot_sans_remise * (1 + ( $txtva / 100)) + $localtaxes[0], 'MT');	// If non "non NPR" VAT
			$result[7] = price2num($result8bis - ($result[6] + $localtaxes[0]), 'MT');

			$result[0] = price2num($tot_avec_remise, 'MT');
			$result[2] = price2num($tot_avec_remise * (1 + ( (($info_bits & 1)?0:$txtva) / 100)) + $localtaxes[1], 'MT');	// If VAT "NPR" or not
			$result2bis= price2num($tot_avec_remise * (1 + ( $txtva / 100)) + $localtaxes[1], 'MT');	// If non "non NPR" VAT
			$result[1] = price2num($result2bis - ($result[0] + $localtaxes[1]), 'MT');	// Total VAT = TTC - (HT + localtax)

			$result[3] = price2num($pu, 'MU');
			$result[5] = price2num($pu * (1 + ( (($info_bits & 1)?0:$txtva) / 100)) + $localtaxes[2], 'MU');	// If VAT "NPR" or not
			$result5bis= price2num($pu * (1 + ($txtva / 100)) + $localtaxes[2], 'MU');	// If non "non NPR" VAT
			$result[4] = price2num($result5bis - ($result[3] + $localtaxes[2]), 'MU');
		}
		else
		{
			// We work to define prices using the price with tax
			$result[8] = price2num($tot_sans_remise + $localtaxes[0], 'MT');
			$result[6] = price2num($tot_sans_remise / (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MT');	// If VAT "NPR" or not
			$result6bis= price2num($tot_sans_remise / (1 + ($txtva / 100)), 'MT');	// If non "non NPR" VAT
			$result[7] = price2num($result[8] - ($result6bis + $localtaxes[0]), 'MT');

			$result[2] = price2num($tot_avec_remise + $localtaxes[1], 'MT');
			$result[0] = price2num($tot_avec_remise / (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MT');	// If VAT "NPR" or not
			$result0bis= price2num($tot_avec_remise / (1 + ($txtva / 100)), 'MT');	// If non "non NPR" VAT
			$result[1] = price2num($result[2] - ($result0bis + $localtaxes[1]), 'MT');	// Total VAT = TTC - (HT + localtax)

			$result[5] = price2num($pu + $localtaxes[2], 'MU');
			$result[3] = price2num($pu / (1 + ((($info_bits & 1)?0:$txtva) / 100)), 'MU');	// If VAT "NPR" or not
			$result3bis= price2num($pu / (1 + ($txtva / 100)), 'MU');	// If non "non NPR" VAT
			$result[4] = price2num($result[5] - ($result3bis + $localtaxes[2]), 'MU');
		}

		// if there's some localtax without vat, we calculate localtaxes (we will add them at end)

		//If input unit price is 'TTC', we need to have the totals without main VAT for a correct calculation
		if ($price_base_type == 'TTC')
		{
			$tot_sans_remise= price2num($tot_sans_remise / (1 + ($txtva / 100)), 'MU');
			$tot_avec_remise= price2num($tot_avec_remise / (1 + ($txtva / 100)), 'MU');
			$pu = price2num($pu / (1 + ($txtva / 100)), 'MU');
		}

		$apply_tax = false;
		switch($localtax1_type) {
		  case '1':	 // localtax on product or service
			$apply_tax = true;
			break;
		  case '3':	 // localtax on product
			if ($type == 0) $apply_tax = true;
			break;
		  case '5':	 // localtax on service
			if ($type == 1) $apply_tax = true;
			break;
		}
		if ($uselocaltax1_rate && $apply_tax) {
	  		$result[14] = price2num(($tot_sans_remise * (1 + ( $localtax1_rate / 100))) - $tot_sans_remise, 'MT');	// amount tax1 for total_ht_without_discount
	  		$result[8] += $result[14];																				// total_ttc_without_discount + tax1

	  		$result[9] = price2num(($tot_avec_remise * (1 + ( $localtax1_rate / 100))) - $tot_avec_remise, 'MT');	// amount tax1 for total_ht
	  		$result[2] += $result[9];																				// total_ttc + tax1

	  		$result[11] = price2num(($pu * (1 + ( $localtax1_rate / 100))) - $pu, 'MU');							// amount tax1 for pu_ht
	  		$result[5] += $result[11];																				// pu_ht + tax1
		}

		$apply_tax = false;
	  	switch($localtax2_type) {
		  case '1':	 // localtax on product or service
			$apply_tax = true;
			break;
		  case '3':	 // localtax on product
			if ($type == 0) $apply_tax = true;
			break;
		  case '5':	 // localtax on service
			if ($type == 1) $apply_tax = true;
			break;
		}
		if ($uselocaltax2_rate && $apply_tax) {
	  		$result[15] = price2num(($tot_sans_remise * (1 + ( $localtax2_rate / 100))) - $tot_sans_remise, 'MT');	// amount tax2 for total_ht_without_discount
	  		$result[8] += $result[15];																				// total_ttc_without_discount + tax2

	  		$result[10] = price2num(($tot_avec_remise * (1 + ( $localtax2_rate / 100))) - $tot_avec_remise, 'MT');	// amount tax2 for total_ht
	  		$result[2] += $result[10];																				// total_ttc + tax2

	  		$result[12] = price2num(($pu * (1 + ( $localtax2_rate / 100))) - $pu, 'MU');							// amount tax2 for pu_ht
	  		$result[5] += $result[12];																				// pu_ht + tax2
		}

		// If rounding is not using base 10 (rare)
		$roundingRule=getDolGlobalInt('MAIN_ROUNDING_RULE_TOT');
		if ($roundingRule > 0) {
			if ($price_base_type == 'HT') {
				$result[0]  = round($result[0] / $roundingRule, 0) * $roundingRule;
				$result[1]  = round($result[1] / $roundingRule, 0) * $roundingRule;
				$result[2]  = price2num($result[0]+$result[1], 'MT');
				$result[9]  = round($result[9] / $roundingRule, 0) * $roundingRule;
				$result[10] = round($result[10] / $roundingRule, 0) * $roundingRule;
			} else {
				$result[1]  = round($result[1] / $roundingRule, 0) * $roundingRule;
				$result[2]  = round($result[2] / $roundingRule, 0) * $roundingRule;
				$result[0]  = price2num($result[2]-$result[0], 'MT');
				$result[9]  = round($result[9] / $roundingRule, 0) * $roundingRule;
				$result[10] = round($result[10] / $roundingRule, 0) * $roundingRule;
			}
		}

		// initialize result array
		//for ($i=0; $i <= 15; $i++) $result[$i] = (float) $result[$i];

		dol_syslog('Price.lib::calcul_price_total MAIN_ROUNDING_RULE_TOT=' . getDolGlobalInt('MAIN_ROUNDING_RULE_TOT').' pu='.$pu.' qty='.$qty.' price_base_type='.$price_base_type.' total_ht='.$result[0].'-total_vat='.$result[1].'-total_ttc='.$result[2]);

		return $result;
	}

	/**
	 * Show one line in the References part
	 * 	@param	PDF			$pdf	 		Object PDF
	 *  @param  int			$w	 			Width of writing cell
	 *  @param  int			$posx	 		Position in x
	 *  @param  int			$posy	 		Position in y
	 *  @param  str			$label			Text to show
	 *  @param  int			$fontSize	 	Font size in pt
	 *	@param  str			$fontWeight		Font Weight ('' or 'B')
	 *  @param  int			$spaceBefore	Space, in point, to add before line
	 *  @param  int			$spaceAfter	 Space, in point, to add after line
	 *
	 *  @return	int 						posY after printing
	 */
	function _display_header_line(&$pdf, $w, $posx, $posy, $label, $fontSize, $fontWeight = '', $spaceBefore = 3, $spaceAfter = 0) {
		$pdf->SetFont('', $fontWeight, $fontSize);
		$posy += $spaceBefore;
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($w, 4, $label, '', 'R');
		$posy += $spaceAfter;
		return $posy;
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf	 		Object PDF
	 *  @param  Object		$object	 	Object to show
	 *  @param  int			$showaddress	0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  boolean  $showLinkedObject
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $showLinkedObject = TRUE)
	{
		global $conf, $langs;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("companies");
		$outputlangs->load("btp@btp");

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_height);

		// Show Draft Watermark
		if($object->statut == 0 && (!empty(getDolGlobalString('FACTURE_DRAFT_WATERMARK')))) {
			  pdf_watermark($pdf, $outputlangs, $this->page_height, $this->page_width, 'mm', getDolGlobalString('FACTURE_DRAFT_WATERMARK'));
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
		$posx = $this->page_width-$this->margin_right-$w;

		$pdf->SetXY($this->margin_left, $posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->issuer->logo;
		if ($this->issuer->logo) {
			if (is_readable($logo)) {
				$height = pdf_getHeightForLogo($logo);
				$pdf->Image($logo, $this->margin_left, $posy, 0, $height);	// width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$text = $this->issuer->name;
			$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		/** Manage various lines under Facture ref **/
		$title = $outputlangs->transnoentities("Invoice");
		if ($object->type == 1) $title = $outputlangs->transnoentities("InvoiceReplacement");
		if ($object->type == 2) $title = $outputlangs->transnoentities("InvoiceAvoir");
		if ($object->type == 3) $title = $outputlangs->transnoentities("InvoiceDeposit");
		if ($object->type == 4) $title = $outputlangs->transnoentities("InvoiceProFormat");

		$lines = array(
			'Ref' => array(
				'spaceBefore' => 5, 
				'label' => $title . ' ' . $outputlangs->convToOutputCharset($object->ref), 
				'fontWeight' => 'B', 
				'fontSize' => $default_font_size + 3, 
			), 
			'SituationNbr' => array(
				'spaceBefore' => 5, 
				'label' => $object->situation_final ? $outputlangs->transnoentities("PDFInvoiceDGD") : $outputlangs->transnoentities("PDFCrabeBtpTitle", $object->situation_counter), 
				'spaceAfter' => 1, 
				'fontWeight' => 'B', 
				'fontSize' => $default_font_size, 
			), 
			'DateInvoice' => array(
				'spaceBefore' => 4, 
				'label' => $outputlangs->transnoentities("DateInvoice")." : " . dol_print_date($object->date, "day", false, $outputlangs), 
				'fontSize' => $default_font_size - 2, 
			), 
		);

		if ($object->ref_client) {
			$lines['RefClient'] = array(
				'spaceBefore' => 4, 
				'label' => $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset($object->ref_client), 
				'fontSize' => $default_font_size - 2, 
			);
		}
		$objectidnext = $object->getIdReplacingInvoice('validated');
		if ($object->type == 0 && $objectidnext) {
			$objectreplacing = new Facture($this->db);
			$objectreplacing->fetch($objectidnext);
			$lines['ReplacementBy'] = array(
				'label' => $outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($objectreplacing->ref), 
				'fontSize' => $default_font_size - 2, 
			);
		}
		if ($object->type == 1) 		{
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);
			$lines['ReplacementInvoice'] = array(
				'spaceBefore' => 4, 
				'label' => $outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), 
				'fontSize' => $default_font_size - 2, 
			);
		}
		if ($object->type == 2 && !empty($object->fk_facture_source)) {
			$objectreplaced = new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);
			$lines['CorrectionInvoice'] = array(
				'label' => $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), 
				'fontSize' => $default_font_size - 2, 
			);
		}
		if (getDolGlobalInt('INVOICE_POINTOFTAX_DATE')) {
			$lines['DatePointOfTax'] = array(
				'spaceBefore' => 4, 
				'label' => $outputlangs->transnoentities("DatePointOfTax")." : " . dol_print_date($object->date_pointoftax, "day", false, $outputlangs), 
				'fontSize' => $default_font_size - 2, 
			);
		}
		if ($object->type != 2)	{
			$lines['DateDue'] = array(
				'label' => $outputlangs->transnoentities("DateDue")." : " . dol_print_date($object->date_lim_reglement, "day", false, $outputlangs, true), 
				'fontSize' => $default_font_size - 2, 
			);
		}
		if ($object->thirdparty->code_client) {
			$lines['CustomerCode'] = array(
				'label' => $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->thirdparty->code_client), 
				'fontSize' => $default_font_size - 2, 
			);
		}

		$pdf->SetTextColor(0, 0, 60);
		foreach ($lines as $name => $props) {
			$posy = $this->_display_header_line($pdf, $w, $posx, $posy, ...$props);
		}

		$posy += 1;
		// Record last position for vertical alignment
		$maxY = $posy;


		if ($showaddress) {
			$ref_height = max(42, $maxY + 5);
			// Sender properties
			$carac_issuer = pdf_build_address($outputlangs, $this->issuer, $object->thirdparty);

			// Show sender
			$posy = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 40 : $ref_height;
			$posx = $this->margin_left;
			if (getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT')) $posx = $this->page_width - $this->margin_right - 80;

			$heightBox = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 38 : 40;
			$widthrecbox = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 82;


			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy-5);
			$pdf->MultiCell(66, 5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell($widthrecbox, $heightBox, "", 0, 'R', 1);

			// Show sender name
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetXY($posx+2, $posy+3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox-2, 4, $outputlangs->convToOutputCharset($this->issuer->name), 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetXY($posx+2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox-2, 4, $carac_issuer, 0, 'L');


			// If BILLING contact defined on invoice, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external', 'BILLING');
			if (count($arrayidcontact) > 0) {
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

			//Recipient name
			// On peut utiliser le nom de la societe du contact
			if ($usecontact && getDolGlobalInt('MAIN_USE_COMPANY_NAME_OF_CONTACT')) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);
			$carac_client=pdf_build_address($outputlangs, $this->issuer, $object->thirdparty, ($usecontact?$object->contact:''), $usecontact, 'target', $object);

			// Show recipient
			$widthrecbox=getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 100;
			if ($this->page_width < 210) $widthrecbox=84;	// To work with US executive format
			$posy=getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 40 : $ref_height;
			$posx=$this->page_width-$this->margin_right-$widthrecbox;
			if (getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT')) $posx=$this->margin_left;

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx+2, $posy-5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $heightBox);
			$maxY = max($maxY, $pdf->getY() + $heightBox);

			// Show recipient name
			$pdf->SetXY($posx+2, $posy+3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx+2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
			
		}

		$pdf->SetTextColor(0, 0, 0);
		return $maxY;
	}


	/**
	 *		Show footer of page. Need this->issuer object
	 *
	 *		@param	PDF			$pdf	 			PDF
	 * 		@param	Object		$object				Object to show
	 *	  @param	Translate	$outputlangs		Object lang for output
	 *	  @param	int			$hidefreetext		1=Hide free text
	 *	  @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS');
		return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->issuer, $this->margin_bottom, $this->margin_left, $this->page_height, $object, $showdetails, $hidefreetext);
	}


	/**
	 * Rect pdf
	 *
	 * @param	PDF		$pdf			Object PDF
	 * @param	float	$x				Abscissa of first point
	 * @param	float	$y				Ordinate of first point
	 * @param	float	$l				??
	 * @param	float	$h				??
	 * @param	int		$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 * @param	int		$hidebottom		Hide bottom
	 * @return	void
	 */
	function printRectBtp($pdf, $x, $y, $l, $h, $hidetop = 0, $hidebottom = 0)
	{
		if (empty($hidetop) || $hidetop == -1) $pdf->line($x, $y, $x+$l, $y);
		$pdf->line($x+$l, $y, $x+$l, $y+$h);
		if (empty($hidebottom)) $pdf->line($x+$l, $y+$h, $x, $y+$h);
		$pdf->line($x, $y+$h, $x, $y);
	}

	/**
	 * @param $posy
	 * @param $pdf

	 * @param $object
	 * @param $outputlangs
	 * @return array
	 */
	public function setNewPage($posy, &$pdf, &$object, $outputlangs, $maxY = 168)
	{
		global $conf;

		if ($posy > $maxY) {
			$this->_pagefoot($pdf, $object, $outputlangs, 1);
			$pdf->addPage();
			$pdf->setY($this->marge_haute);
			if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) $posy = $this->_pagehead($pdf, $object, 0, $outputlangs);
			$posy = getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 10 : $posy;
		}

		return $posy;
	}


	/**
	 * @param $object 			Represent the Facture object (invoice) to assess
	 * @param $sign 			The sign of the invoice
	 * 
	 * @return array of 'tva', 'localtax1' and 'localtax2'
	 */
	public function get_taxes($object, $sign)
	{
		$taxes = array(
			"tva" => array(), 
			"localtax1" => array(), 
			"localtax2" => array(), 
		);
		$object->fetch_lines();
		for ($i=0; $i < count($object->lines); $i++) {
			// --- Manage VAT, sorted by VAT rate ---
			// Grab data
			$vatrate = $object->lines[$i]->tva_tx;
			$line_amount_vat = (isModEnabled('multicurrency') && $object->multicurrency_tx != 1) ? $object->lines[$i]->multicurrency_total_tva : $object->lines[$i]->total_tva;
			$prev_progress = $object->lines[$i]->get_prev_progress($object->id);
			if ($object->lines[$i]->situation_percent > 0) {
				$progress = ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent; // TODO - Control, here another formula was used, dividing by $object->lines[$i]->situation_percent
			} else {
				$rogress = 0;
			}

			// Compute VAT
			$tvaligne = $sign * $line_amount_vat * $progress;
			if (($object->lines[$i]->info_bits & 0x01) == 0x01) 		$vatrate .= '*';
			if (!isset($taxes['tva'][$vatrate])) 						$taxes['tva'][$vatrate] = 0.0;
			$taxes['tva'][$vatrate] += $tvaligne;
			
			// --- Manage Local taxes ---
			$localtax1ligne = $object->lines[$i]->total_localtax1;
			$localtax2ligne = $object->lines[$i]->total_localtax2;
			$localtax1_rate = $object->lines[$i]->localtax1_tx;
			$localtax2_rate = $object->lines[$i]->localtax2_tx;
			$localtax1_type = $object->lines[$i]->localtax1_type;
			$localtax2_type = $object->lines[$i]->localtax2_type;

			// Retrieve type from database for backward compatibility with old records
			if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
			&& (! empty($localtax1_rate) || ! empty($localtax2_rate))) { // and there is local tax
				$localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $mysoc);
				$localtax1_type = $localtaxtmp_array[0];
				$localtax2_type = $localtaxtmp_array[2];
			}

			// Retrieve global local tax
			if ($localtax1_type && $localtax1ligne != 0) $taxes['localtax1'][$localtax1_type][$localtax1_rate] += $localtax1ligne;
			if ($localtax2_type && $localtax2ligne != 0) $taxes['localtax2'][$localtax2_type][$localtax2_rate] += $localtax2ligne;
		}
		return $taxes;
	}

}

