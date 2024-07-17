<?php
/* Copyright (C) 2004-2014  Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin           <regis.houssin@inodbox.com>
 * Copyright (C) 2008       Raphael Bertrand        <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014  Juanjo Menent           <jmenent@2byte.es>
 * Copyright (C) 2012       Christophe Battarel     <christophe.battarel@altairis.fr>
 * Copyright (C) 2012       Cédric Salvador         <csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014  Raphaël Doursenaud      <rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015       Marcos Garcia           <marcosgdf@gmail.com>
 * Copyright (C) 2017       Ferran Marcet           <fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2022       Anthony Berton          <anthony.berton@bb2a.fr>
 * Copyright (C) 2022-2024  Alexandre Spangaro      <alexandre@inovea-conseil.com>
 * Copyright (C) 2022-2024  Eric Seigne             <eric.seigne@cap-rel.fr>
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
 * or see https://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/facture/doc/pdf_octopus.modules.php
 *  \ingroup    facture
 *  \brief      File of class to generate customers invoices from octopus model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/price.lib.php';


/**
 *	Class to manage PDF invoice template octopus
 */
class pdf_octopus extends ModelePDFFactures
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var string model name
	 */
	public $name;

	/**
	 * @var string model description (short text)
	 */
	public $description;

	/**
	 * @var int     Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

	/**
	 * @var string document type
	 */
	public $type;

	/**
	 * Dolibarr version of the loaded document
	 * @var string
	 */
	public $version = 'disabled';	// Disabled by default. Enabled in constructor if option INVOICE_USE_SITUATION is 2.

	/**
	 * @var int height for info total
	 */
	public $heightforinfotot;

	/**
	 * @var int height for free text
	 */
	public $heightforfreetext;

	/**
	 * @var int height for footer
	 */
	public $heightforfooter;

	/**
	 * @var int tab_top
	 */
	public $tab_top;

	/**
	 * @var int tab_top_newpage
	 */
	public $tab_top_newpage;

	/**
	 * Issuer
	 * @var Societe Object that emits
	 */
	public $emetteur;

	/**
	 * @var bool Situation invoice type
	 */
	public $situationinvoice;


	/**
	 * @var array<string,array{rank:int,width:float|int,title:array{textkey:string,label:string,align:string,padding:array{0:float,1:float,2:float,3:float}},content:array{align:string,padding:array{0:float,1:float,2:float,3:float}}}>	Array of columns
	 */
	public $cols;

	/**
	 * @var int Category of operation
	 */
	public $categoryOfOperation = -1; // unknown by default

	/**
	 * Situation invoices
	 *
	 * @var array{derniere_situation:Facture,date_derniere_situation:int,current:array}	Data of situation
	 */
	public $TDataSituation;

	/**
	 * @var int posx cumul anterieur
	 */
	public $posx_cumul_anterieur;

	/**
	 * @var int posx new cumul
	 */
	public $posx_new_cumul;

	/**
	 * @var int posx current
	 */
	public $posx_current;

	/**
	 * @var int tabTitleHeight
	 */
	public $tabTitleHeight;

	/**
	 * @var int is rg
	 */
	public $is_rg;

	/**
	 * @var bool franchise
	 */
	public $franchise;

	/**
	 * @var int
	 */
	public $tplidx;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc, $object;

		// for retro compatibility
		if (getDolGlobalString('INVOICE_USE_SITUATION_RETAINED_WARRANTY') && !getDolGlobalString('INVOICE_USE_RETAINED_WARRANTY')) {
			// before it was only for final situation invoice
			$conf->global->INVOICE_USE_RETAINED_WARRANTY = $conf->global->INVOICE_USE_SITUATION_RETAINED_WARRANTY;
			$conf->global->USE_RETAINED_WARRANTY_ONLY_FOR_SITUATION_FINAL = 1;
		}

		// If hidden option INVOICE_USE_SITUATION is set to 2, we can show the invoice situation template
		if (getDolGlobalString('INVOICE_USE_SITUATION') == 2) {
			$this->version = 'dolibarr';
		}

		// Translations
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "octopus";
		$this->description = $langs->trans('PDFOctopusDescription');
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

		$this->posx_cumul_anterieur = 94;
		$this->posx_new_cumul = 130;
		$this->posx_current = 166;

		$this->option_logo = 1; // Display logo
		$this->option_tva = 1; // Manage the vat option FACTURE_TVAOPTION
		$this->option_modereg = 1; // Display payment mode
		$this->option_condreg = 1; // Display payment terms
		$this->option_multilang = 1; // Available in several languages
		$this->option_escompte = 1; // Displays if there has been a discount
		$this->option_credit_note = 1; // Support credit notes
		$this->option_freetext = 1; // Support add of a personalised text
		$this->option_draft_watermark = 1; // Support add of a watermark on drafts
		$this->watermark = '';
		$this->franchise = !$mysoc->tva_assuj; // not used ?

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1; // used for notes and other stuff


		$this->tabTitleHeight = 8; // default height (2 lines due to overtitle)

		//  Use new system for position of columns, view  $this->defineColumnField()

		$this->tva = array();
		$this->tva_array = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
		$this->situationinvoice = true;
		if (!empty($object)) {
			$this->TDataSituation = $this->getDataSituation($object);
		} else {
			dol_syslog("object is empty, do not call getDataSituation...");
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Facture		$object				Object to generate
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *  @return     int         	    			1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies"));

		global $outputlangsbis;
		$outputlangsbis = null;
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs(array("main", "bills", "products", "dict", "companies"));
		}

		if (empty($object) || ($object->type != Facture::TYPE_SITUATION && ($object->type != Facture::TYPE_CREDIT_NOTE &&  !empty($object->situation_cycle_ref)))) {
			setEventMessage($langs->trans('WarningsObjectIsNotASituation'), 'warnings');
			return 1;
		}
		// Show Draft Watermark
		if ($object->status == $object::STATUS_DRAFT && (getDolGlobalString('FACTURE_DRAFT_WATERMARK'))) {
			$this->watermark = getDolGlobalString('FACTURE_DRAFT_WATERMARK');
		}

		$nblines = count($object->lines);

		$hidetop = 0;
		if (getDolGlobalString('MAIN_PDF_DISABLE_COL_HEAD_TITLE')) {
			$hidetop = getDolGlobalString('MAIN_PDF_DISABLE_COL_HEAD_TITLE');
		}

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		$this->atleastonephoto = false;
		if (getDolGlobalString('MAIN_GENERATE_INVOICES_WITH_PICTURE')) {
			$objphoto = new Product($this->db);

			for ($i = 0; $i < $nblines; $i++) {
				if (empty($object->lines[$i]->fk_product)) {
					continue;
				}

				$objphoto->fetch($object->lines[$i]->fk_product);
				//var_dump($objphoto->ref);exit;
				$pdir = array();
				if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
					$pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/";
					$pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
				} else {
					$pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product'); // default
					$pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product').$objphoto->id."/photos/"; // alternative
				}

				$arephoto = false;
				foreach ($pdir as $midir) {
					if (!$arephoto) {
						if ($conf->entity != $objphoto->entity) {
							$dir = $conf->product->multidir_output[$objphoto->entity].'/'.$midir; //Check repertories of current entities
						} else {
							$dir = $conf->product->dir_output.'/'.$midir; //Check repertory of the current product
						}

						foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
							if (!getDolGlobalInt('CAT_HIGH_QUALITY_IMAGES')) {		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
								if ($obj['photo_vignette']) {
									$filename = $obj['photo_vignette'];
								} else {
									$filename = $obj['photo'];
								}
							} else {
								$filename = $obj['photo'];
							}

							$realpath = $dir.$filename;
							$arephoto = true;
							$this->atleastonephoto = true;
						}
					}
				}

				if ($realpath && $arephoto) {
					$realpatharray[$i] = $realpath;
				}
			}
		}

		//if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

		if ($conf->facture->multidir_output[$conf->entity]) {
			$object->fetch_thirdparty();

			$deja_regle = $object->getSommePaiement((isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? 1 : 0);
			$amount_credit_notes_included = $object->getSumCreditNotesUsed((isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? 1 : 0);
			$amount_deposits_included = $object->getSumDepositsUsed((isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? 1 : 0);

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->facture->multidir_output[$conf->entity];
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->facture->multidir_output[$object->entity]."/".$objectref;
				$file = $dir."/".$objectref.".pdf";
			}
			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (file_exists($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Set nblines with the new facture lines content after hook
				$nblines = count($object->lines);
				$nbpayments = count($object->getListOfPayments());
				$nbprevsituation = is_array($object->tab_previous_situation_invoice) ? count($object->tab_previous_situation_invoice) : 0;

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				//$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$default_font_size = 9;
				$pdf->SetAutoPageBreak(1, 0);

				// compute height for situation invoices
				$this->heightforinfotot = 45;	// Height reserved to output the info and total part and payment part
				if (!getDolGlobalString('INVOICE_NO_PAYMENT_DETAILS') && $nbpayments > 0) {
					$this->heightforinfotot += 4 * ($nbpayments + 3);
				}
				if ($nbprevsituation > 0) {
					$this->heightforinfotot += 4 * ($nbprevsituation + 3);
				}
				$this->heightforfreetext= (getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5));	// Height reserved to output the free text on last page
				$this->heightforfooter = $this->marge_basse + (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22);	// Height reserved to output the footer (value include bottom margin)

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Set path to the background PDF File
				if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
					$logodir = $conf->mycompany->dir_output;
					if (!empty($conf->mycompany->multidir_output[$object->entity])) {
						$logodir = $conf->mycompany->multidir_output[$object->entity];
					}
					$pagecount = $pdf->setSourceFile($logodir.'/' . getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
					$this->tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfInvoiceSituationTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($mysoc->name.($user->id > 0 ? ' - '.$outputlangs->convToOutputCharset($user->getFullName($outputlangs)) : ''));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfInvoiceTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
					$pdf->SetCompression(false);
				}

				// Set certificate
				$cert = empty($user->conf->CERTIFICATE_CRT) ? '' : $user->conf->CERTIFICATE_CRT;
				$certprivate = empty($user->conf->CERTIFICATE_CRT_PRIVATE) ? '' : $user->conf->CERTIFICATE_CRT_PRIVATE;
				// If user has no certificate, we try to take the company one
				if (!$cert) {
					$cert = getDolGlobalString('CERTIFICATE_CRT', '');
				}
				if (!$certprivate) {
					$certprivate = getDolGlobalString('CERTIFICATE_CRT_PRIVATE', '');
				}
				// If a certificate is found
				if ($cert) {
					$info = array(
						'Name' => $this->emetteur->name,
						'Location' => getCountry($this->emetteur->country_code, 0),
						'Reason' => 'INVOICE',
						'ContactInfo' => $this->emetteur->email
					);
					$pdf->setSignature($cert, $certprivate, $this->emetteur->name, '', 2, $info);
				}

				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// Set $this->atleastonediscount if you have at least one discount
				// and determine category of operation
				$categoryOfOperation = 0;
				$nbProduct = 0;
				$nbService = 0;
				for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->remise_percent) {
						$this->atleastonediscount++;
					}

					// determine category of operation
					if ($categoryOfOperation < 2) {
						$lineProductType = $object->lines[$i]->product_type;
						if ($lineProductType == Product::TYPE_PRODUCT) {
							$nbProduct++;
						} elseif ($lineProductType == Product::TYPE_SERVICE) {
							$nbService++;
						}
						if ($nbProduct > 0 && $nbService > 0) {
							// mixed products and services
							$categoryOfOperation = 2;
						}
					}
				}
				// determine category of operation
				if ($categoryOfOperation <= 0) {
					// only services
					if ($nbProduct == 0 && $nbService > 0) {
						$categoryOfOperation = 1;
					}
				}
				$this->categoryOfOperation = $categoryOfOperation;

				// New page
				$pdf->AddPage();
				if (!empty($this->tplidx)) {
					$pdf->useTemplate($this->tplidx);
				}
				$pagenb++;

				// Output header (logo, ref and address blocks). This is first call for first page.
				$pagehead = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
				$top_shift = $pagehead['top_shift'];
				$shipp_shift = $pagehead['shipp_shift'];
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				// $pdf->GetY() here can't be used. It is bottom of the second address box but first one may be higher

				// $this->tab_top is y where we must continue content (90 = 42 + 48: 42 is height of logo and ref, 48 is address blocks)
				$this->tab_top = 90 + $top_shift + $shipp_shift;		// top_shift is an addition for linked objects or addons (0 in most cases)
				$this->tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);

				// You can add more thing under header here, if you increase $extra_under_address_shift too.
				$extra_under_address_shift = 0;
				$qrcodestring = '';
				if (getDolGlobalString('INVOICE_ADD_ZATCA_QR_CODE')) {
					$qrcodestring = $object->buildZATCAQRString();
				} elseif (getDolGlobalString('INVOICE_ADD_SWISS_QR_CODE') == '1') {
					$qrcodestring = $object->buildSwitzerlandQRString();
				}
				if ($qrcodestring) {
					$qrcodecolor = array('25', '25', '25');
					// set style for QR-code
					$styleQr = array(
						'border' => false,
						'padding' => 0,
						'fgcolor' => $qrcodecolor,
						'bgcolor' => false, //array(255,255,255)
						'module_width' => 1, // width of a single module in points
						'module_height' => 1 // height of a single module in points
					);
					$pdf->write2DBarcode($qrcodestring, 'QRCODE,M', $this->marge_gauche, $this->tab_top - 5, 25, 25, $styleQr, 'N');
					$extra_under_address_shift += 25;
				}

				// Call hook printUnderHeaderPDFline
				$parameters = array(
					'object' => $object,
					'i' => $i,
					'pdf' => &$pdf,
					'outputlangs' => $outputlangs,
					'hidedetails' => $hidedetails
				);
				$reshook = $hookmanager->executeHooks('printUnderHeaderPDFline', $parameters, $this); // Note that $object may have been modified by hook
				if (!empty($hookmanager->resArray['extra_under_address_shift'])) {
					$extra_under_address_shift += $hookmanager->resArray['extra_under_address_shift'];
				}

				$this->tab_top += $extra_under_address_shift;
				$this->tab_top_newpage += 0;


				// Define height of table for lines (for first page)
				$tab_height = $this->page_hauteur - $this->tab_top - $this->heightforfooter - $this->heightforfreetext - $this->getHeightForQRInvoice(1, $object, $langs);

				$nexY = $this->tab_top - 1;

				// Specific stuff for situations invoices first page
				$tab_top = 90;
				$tab_height = 130;
				$tab_height_newpage = 150;

				$this->_tableFirstPage($pdf, $tab_top, $this->page_hauteur - 100 - $this->heightforfreetext - $this->heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);

				$bottomlasttab=$this->page_hauteur - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter + 1;

				$this->_pagefoot($pdf, $object, $outputlangs, 1);

				$pdf->AddPage();
				$pdf->setPage(2);
				$pagenb++;
				$this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
				$pdf->setTopMargin($this->tab_top_newpage);

				// Incoterm
				$height_incoterms = 0;
				if (isModEnabled('incoterm')) {
					$desc_incoterms = $object->getIncotermsForPDF();
					if ($desc_incoterms) {
						$this->tab_top -= 2;

						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $this->tab_top - 1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
						$nexY = max($pdf->GetY(), $nexY);
						$height_incoterms = $nexY - $this->tab_top;

						// Rect takes a length in 3rd parameter
						$pdf->SetDrawColor(192, 192, 192);
						$pdf->Rect($this->marge_gauche, $this->tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_incoterms + 1);

						$this->tab_top = $nexY + 6;
						$height_incoterms += 4;
					}
				}

				// Displays notes. Here we are still on code eecuted only for the first page.
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				if (getDolGlobalString('MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE')) {
					// Get first sale rep
					if (is_object($object->thirdparty)) {
						$salereparray = $object->thirdparty->getSalesRepresentatives($user);
						$salerepobj = new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (!empty($salerepobj->signature)) {
							$notetoshow = dol_concatdesc($notetoshow, $salerepobj->signature);
						}
					}
				}

				// Extrafields in note
				$extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
				if (!empty($extranote)) {
					$notetoshow = dol_concatdesc($notetoshow, $extranote);
				}

				$pagenb = $pdf->getPage();
				if ($notetoshow) {
					$this->tab_top -= 2;

					$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					$pageposbeforenote = $pagenb;

					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$pdf->startTransaction();

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $this->tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					// Description
					$pageposafternote = $pdf->getPage();
					$posyafter = $pdf->GetY();

					if ($pageposafternote > $pageposbeforenote) {
						$pdf->rollbackTransaction(true);

						// prepare pages to receive notes
						while ($pagenb < $pageposafternote) {
							$pdf->AddPage();
							$pagenb++;
							if (!empty($this->tplidx)) {
								$pdf->useTemplate($this->tplidx);
							}
							if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
								$this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
							}
							$pdf->setTopMargin($this->tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $this->heightforfooter + $this->heightforfreetext);
						}

						// back to start
						$pdf->setPage($pageposbeforenote);
						$pdf->setPageOrientation('', 1, $this->heightforfooter + $this->heightforfreetext);
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $this->tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
						$pageposafternote = $pdf->getPage();

						$posyafter = $pdf->GetY();

						if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $this->heightforfreetext + 20))) {	// There is no space left for total+free text
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							$pdf->setTopMargin($this->tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', 1, $this->heightforfooter + $this->heightforfreetext);
							//$posyafter = $this->tab_top_newpage;
						}


						// apply note frame to previous pages
						$i = $pageposbeforenote;
						while ($i < $pageposafternote) {
							$pdf->setPage($i);


							$pdf->SetDrawColor(128, 128, 128);
							// Draw note frame
							if ($i > $pageposbeforenote) {
								$height_note = $this->page_hauteur - ($this->tab_top_newpage + $this->heightforfooter);
								$pdf->Rect($this->marge_gauche, $this->tab_top_newpage - 1, $tab_width, $height_note + 1);
							} else {
								$height_note = $this->page_hauteur - ($this->tab_top + $this->heightforfooter);
								$pdf->Rect($this->marge_gauche, $this->tab_top - 1, $tab_width, $height_note + 1);
							}

							// Add footer
							$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
							$this->_pagefoot($pdf, $object, $outputlangs, 1);

							$i++;
						}

						// apply note frame to last page
						$pdf->setPage($pageposafternote);
						if (!empty($this->tplidx)) {
							$pdf->useTemplate($this->tplidx);
						}
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							$this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
						}
						$height_note = $posyafter - $this->tab_top_newpage;
						$pdf->Rect($this->marge_gauche, $this->tab_top_newpage - 1, $tab_width, $height_note + 1);
					} else {
						// No pagebreak
						$pdf->commitTransaction();
						$posyafter = $pdf->GetY();
						$height_note = $posyafter - $this->tab_top;
						$pdf->Rect($this->marge_gauche, $this->tab_top - 1, $tab_width, $height_note + 1);


						if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $this->heightforfreetext + 20))) {
							// not enough space, need to add page
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							if (!empty($this->tplidx)) {
								$pdf->useTemplate($this->tplidx);
							}
							if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
								$this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
							}

							$posyafter = $this->tab_top_newpage;
						}
					}

					$tab_height = $tab_height - $height_note;
					$this->tab_top = $posyafter + 6;
				} else {
					$height_note = 0;
				}

				// Use new auto column system
				$this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

				// Table simulation to know the height of the title line (this set this->tableTitleHeight)
				// don't need it in situation invoices
				// $pdf->startTransaction();
				// $this->pdfTabTitles($pdf, $this->tab_top_newpage + ($this->tabTitleHeight/2), $tab_height, $outputlangs, $hidetop);
				// $pdf->rollbackTransaction(true);

				$nexY = $this->tab_top_newpage + $this->tabTitleHeight;

				// Loop on each lines
				$pageposbeforeprintlines = $pdf->getPage();
				$pagenb = $pageposbeforeprintlines;
				for ($i = 0; $i < $nblines; $i++) {
					$posy = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					// Define size of image if we need it
					$imglinesize = array();
					if (!empty($realpatharray[$i])) {
						$imglinesize = pdf_getSizeForImage($realpatharray[$i]);
					}

					$pdf->setTopMargin($this->tab_top_newpage);
					$pdf->setPageOrientation('', 1, $this->heightforfooter + $this->heightforfreetext + $this->heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					$posYAfterImage = 0;
					$posYAfterDescription = 0;

					if ($this->getColumnStatus('photo')) {
						// We start with Photo of product line
						if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($posy + $imglinesize['height']) > ($this->page_hauteur - ($this->heightforfooter + $this->heightforfreetext + $this->heightforinfotot))) {	// If photo too high, we moved completely on new page
							$pdf->AddPage('', '', true);
							if (!empty($this->tplidx)) {
								$pdf->useTemplate($this->tplidx);
							}
							$pdf->setPage($pageposbefore + 1);

							$posy = $this->tab_top_newpage;

							// Allows data in the first page if description is long enough to break in multiples pages
							if (getDolGlobalString('MAIN_PDF_DATA_ON_FIRST_PAGE')) {
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
							}
						}

						if (!empty($this->cols['photo']) && isset($imglinesize['width']) && isset($imglinesize['height'])) {
							$pdf->Image($realpatharray[$i], $this->getColumnContentXStart('photo'), $posy + 1, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300); // Use 300 dpi
							// $pdf->Image does not increase value return by getY, so we save it manually
							$posYAfterImage = $posy + $imglinesize['height'];
						}
					}

					// Description of product line
					if ($this->getColumnStatus('desc')) {
						$pdf->startTransaction();

						$this->printColDescContent($pdf, $posy, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
						$pageposafter = $pdf->getPage();

						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							$pdf->rollbackTransaction(true);
							$pageposafter = $pageposbefore;
							$pdf->setPageOrientation('', 1, $this->heightforfooter); // The only function to edit the bottom margin of current page to set it.

							$this->printColDescContent($pdf, $posy, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);

							$pageposafter = $pdf->getPage();
							$posyafter = $pdf->GetY();
							//var_dump($posyafter); var_dump(($this->page_hauteur - ($this->heightforfooter+$this->heightforfreetext+$this->heightforinfotot))); exit;
							if ($posyafter > ($this->page_hauteur - ($this->heightforfooter + $this->heightforfreetext + $this->heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									if (!empty($this->tplidx)) {
										$pdf->useTemplate($this->tplidx);
									}
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								// We found a page break
								// Allows data in the first page if description is long enough to break in multiples pages
								if (getDolGlobalString('MAIN_PDF_DATA_ON_FIRST_PAGE')) {
									$showpricebeforepagebreak = 1;
								} else {
									$showpricebeforepagebreak = 0;
								}
							}
						} else { // No pagebreak
							$pdf->commitTransaction();
						}
						$posYAfterDescription = $pdf->GetY();
					}

					$nexY = max($pdf->GetY(), $posYAfterImage, $posYAfterDescription);

					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$posy = $this->tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

					// VAT Rate
					if ($this->getColumnStatus('vat')) {
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $posy, 'vat', $vat_rate);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Unit price before discount
					if ($this->getColumnStatus('subprice')) {
						$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $posy, 'subprice', $up_excl_tax);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Quantity
					// Enough for 6 chars
					if ($this->getColumnStatus('qty')) {
						$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $posy, 'qty', $qty);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Situation progress
					if ($this->getColumnStatus('progress')) {
						$progress = pdf_getlineprogress($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $posy, 'progress', $progress);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Unit
					if ($this->getColumnStatus('unit')) {
						$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $posy, 'unit', $unit);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Discount on line
					if ($this->getColumnStatus('discount') && $object->lines[$i]->remise_percent) {
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $posy, 'discount', $remise_percent);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Total excl tax line (HT)
					if ($this->getColumnStatus('totalexcltax')) {
						$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
						$this->printStdColumnContent($pdf, $posy, 'totalexcltax', $total_excl_tax);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Retrieving information from the previous line
					$TInfosLigneSituationPrecedente = $this->getInfosLineLastSituation($object, $object->lines[$i]);

					// Sum
					$columkey = 'btpsomme';
					if ($this->getColumnStatus($columkey)) {
						$printval = price($TInfosLigneSituationPrecedente['total_ht_without_progress'], 0, '', 1, -1, 2);
						$this->printStdColumnContent($pdf, $posy, $columkey, $printval);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Current progress
					$columkey = 'progress_amount';
					if ($this->getColumnStatus($columkey)) {
						$printval = price($object->lines[$i]->total_ht, 0, '', 1, -1, 2);
						$this->printStdColumnContent($pdf, $posy, $columkey, $printval);
						$nexY = max($pdf->GetY(), $nexY);
					}
					// Previous progress line
					$columkey = 'prev_progress';
					if ($this->getColumnStatus($columkey)) {
						$printval = $TInfosLigneSituationPrecedente['progress_prec'].'%';
						$this->printStdColumnContent($pdf, $posy, $columkey, $printval);
						$nexY = max($pdf->GetY(), $nexY);
					}
					// Previous progress amount
					$columkey = 'prev_progress_amount';
					if ($this->getColumnStatus($columkey)) {
						$printval = price($TInfosLigneSituationPrecedente['total_ht'], 0, '', 1, -1, 2);
						$this->printStdColumnContent($pdf, $posy, $columkey, $printval);
						$nexY = max($pdf->GetY(), $nexY);
					}

					$parameters = array(
						'object' => $object,
						'i' => $i,
						'pdf' =>& $pdf,
						'curY' =>& $posy,
						'nexY' =>& $nexY,
						'outputlangs' => $outputlangs,
						'hidedetails' => $hidedetails
					);
					$reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this); // Note that $object may have been modified by hook


					$sign = 1;
					if (isset($object->type) && $object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
						$sign = -1;
					}
					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					$prev_progress = $object->lines[$i]->get_prev_progress($object->id);
					if ($prev_progress > 0 && !empty($object->lines[$i]->situation_percent)) { // Compute progress from previous situation
						if (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) {
							$tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
						} else {
							$tvaligne = $sign * $object->lines[$i]->total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
						}
					} else {
						if (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) {
							$tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva;
						} else {
							$tvaligne = $sign * $object->lines[$i]->total_tva;
						}
					}

					$localtax1ligne = $object->lines[$i]->total_localtax1;
					$localtax2ligne = $object->lines[$i]->total_localtax2;
					$localtax1_rate = $object->lines[$i]->localtax1_tx;
					$localtax2_rate = $object->lines[$i]->localtax2_tx;
					$localtax1_type = $object->lines[$i]->localtax1_type;
					$localtax2_type = $object->lines[$i]->localtax2_type;

					// TODO remise_percent is an obsolete field for object parent
					/*if ($object->remise_percent) {
						$tvaligne -= ($tvaligne * $object->remise_percent) / 100;
					}
					if ($object->remise_percent) {
						$localtax1ligne -= ($localtax1ligne * $object->remise_percent) / 100;
					}
					if ($object->remise_percent) {
						$localtax2ligne -= ($localtax2ligne * $object->remise_percent) / 100;
					}*/

					$vatrate = (string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((!isset($localtax1_type) || $localtax1_type == '' || !isset($localtax2_type) || $localtax2_type == '') // if tax type not defined
						&& (!empty($localtax1_rate) || !empty($localtax2_rate))) { // and there is local tax
						$localtaxtmp_array = getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $mysoc);
						$localtax1_type = isset($localtaxtmp_array[0]) ? $localtaxtmp_array[0] : '';
						$localtax2_type = isset($localtaxtmp_array[2]) ? $localtaxtmp_array[2] : '';
					}

					// retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0) {
						if (empty($this->localtax1[$localtax1_type][$localtax1_rate])) {
							$this->localtax1[$localtax1_type][$localtax1_rate] = $localtax1ligne;
						} else {
							$this->localtax1[$localtax1_type][$localtax1_rate] += $localtax1ligne;
						}
					}
					if ($localtax2_type && $localtax2ligne != 0) {
						if (empty($this->localtax2[$localtax2_type][$localtax2_rate])) {
							$this->localtax2[$localtax2_type][$localtax2_rate] = $localtax2ligne;
						} else {
							$this->localtax2[$localtax2_type][$localtax2_rate] += $localtax2ligne;
						}
					}

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
						$vatrate .= '*';
					}

					// Fill $this->tva and $this->tva_array
					if (!isset($this->tva[$vatrate])) {
						$this->tva[$vatrate] = 0;
					}
					$this->tva[$vatrate] += $tvaligne;	// ->tva is abandoned, we use now ->tva_array that is more complete
					$vatcode = $object->lines[$i]->vat_src_code;
					if (empty($this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'])) {
						$this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] = 0;
					}
					$this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')] = array('vatrate' => $vatrate, 'vatcode' => $vatcode, 'amount' => $this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] + $tvaligne);

					$nexY = max($nexY, $posYAfterImage);

					// Add line
					if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY, $this->page_largeur - $this->marge_droite, $nexY);
						$pdf->SetLineStyle(array('dash' => 0));
					}

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						$tabtop = $this->tab_top;
						$tabhauteur = $this->page_hauteur - $tabtop - $this->heightforfooter;
						if ($pagenb != $pageposbeforeprintlines) {
							$tabtop = $this->tab_top_newpage;
							$tabhauteur = $this->page_hauteur - $tabtop - $this->heightforfooter;
							$hidetop = 1;
						}
						$this->_tableau($pdf, $tabtop, $tabhauteur, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangsbis);

						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							$this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
						}
						if (!empty($this->tplidx)) {
							$pdf->useTemplate($this->tplidx);
						}
					}

					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						$tabtop = $this->tab_top;
						$tabhauteur = $this->page_hauteur - $tabtop - $this->heightforfooter;
						if ($pagenb != $pageposbeforeprintlines) {
							$tabtop = $this->tab_top_newpage;
							$tabhauteur = $this->page_hauteur - $tabtop - $this->heightforfooter;
							$hidetop = 1;
						}
						$this->_tableau($pdf, $tabtop, $tabhauteur, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangsbis);

						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (!empty($this->tplidx)) {
							$pdf->useTemplate($this->tplidx);
						}
						$pagenb++;
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							$this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);
						}
					}
				}

				// Show square
				// special for situation invoices
				$tabtop = $this->tab_top_newpage;
				$tabhauteur = $this->page_hauteur - $tabtop - $this->heightforfooter - $this->heightforinfotot - $this->heightforfreetext;
				$tabTitleHeight = 0;
				$this->_tableau($pdf, $tabtop, $tabhauteur, 0, $outputlangs, $hidetop, 1, $object->multicurrency_code, $outputlangsbis);

				$bottomlasttab = $tabtop + $tabhauteur + $tabTitleHeight + 10;

				// Display infos area
				$posy = $this->drawInfoTable($pdf, $object, $bottomlasttab, $outputlangs, $outputlangsbis);

				// Display total zone
				$posy = $this->drawTotalTable($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs, $outputlangsbis);

				// Display payment area
				if (($deja_regle || $amount_credit_notes_included || $amount_deposits_included) && !getDolGlobalString('INVOICE_NO_PAYMENT_DETAILS')) {
					$posy = $this->drawPaymentsTable($pdf, $object, $posy, $outputlangs);
				}

				// Pagefoot
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				$this->resumeLastPage($pdf, $object, 0, $tab_top, $outputlangs, $outputlangsbis);
				$bottomlasttab=$this->page_hauteur - $this->heightforinfotot - $this->heightforfreetext - $this->heightforfooter + 1;
				$this->_pagefoot($pdf, $object, $outputlangs, 1);

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
				}

				dolChmod($file);

				$this->result = array('fullpath' => $file);

				return 1; // No error
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "FAC_OUTPUTDIR");
			return 0;
		}
	}


	/**
	 *  Show payments table
	 *
	 *  @param	TCPDF		$pdf            Object PDF
	 *  @param  Facture		$object         Object invoice
	 *  @param  int			$posy           Position y in PDF
	 *  @param  Translate	$outputlangs    Object langs for output
	 *  @return int             			Return integer <0 if KO, >0 if OK
	 */
	public function drawPaymentsTable(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$sign = 1;
		if ($object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
			$sign = -1;
		}

		$tab3_posx = 120;
		$tab3_top = $posy + 8;
		$tab3_width = 80;
		$tab3_height = 4;
		if ($this->page_largeur < 210) { // To work with US executive format
			$tab3_posx -= 15;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$title = $outputlangs->transnoentities("PaymentsAlreadyDone");
		if ($object->type == 2) {
			$title = $outputlangs->transnoentities("PaymentsBackAlreadyDone");
		}

		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetXY($tab3_posx, $tab3_top - 4);
		$pdf->MultiCell(60, 3, $title, 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top, $tab3_posx + $tab3_width, $tab3_top);

		$pdf->SetFont('', '', $default_font_size - 4);
		$pdf->SetXY($tab3_posx, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 21, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 40, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx + 58, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top - 1 + $tab3_height, $tab3_posx + $tab3_width, $tab3_top - 1 + $tab3_height);

		$y = 0;

		$pdf->SetFont('', '', $default_font_size - 4);


		// Loop on each discount available (deposits and credit notes and excess of payment included)
		$sql = "SELECT re.rowid, re.amount_ht, re.multicurrency_amount_ht, re.amount_tva, re.multicurrency_amount_tva,  re.amount_ttc, re.multicurrency_amount_ttc,";
		$sql .= " re.description, re.fk_facture_source,";
		$sql .= " f.type, f.datef";
		$sql .= " FROM ".MAIN_DB_PREFIX."societe_remise_except as re, ".MAIN_DB_PREFIX."facture as f";
		$sql .= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = ".((int) $object->id);
		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$invoice = new Facture($this->db);
			while ($i < $num) {
				$y += 3;
				$obj = $this->db->fetch_object($resql);

				if ($obj->type == 2) {
					$text = $outputlangs->transnoentities("CreditNote");
				} elseif ($obj->type == 3) {
					$text = $outputlangs->transnoentities("Deposit");
				} elseif ($obj->type == 0) {
					$text = $outputlangs->transnoentities("ExcessReceived");
				} else {
					$text = $outputlangs->transnoentities("UnknownType");
				}

				$invoice->fetch($obj->fk_facture_source);

				$pdf->SetXY($tab3_posx, $tab3_top + $y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($obj->datef), 'day', false, $outputlangs, true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 21, $tab3_top + $y);
				$pdf->MultiCell(20, 3, price((isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? $obj->multicurrency_amount_ttc : $obj->amount_ttc, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 40, $tab3_top + $y);
				$pdf->MultiCell(20, 3, $text, 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 58, $tab3_top + $y);
				$pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top + $y + 3, $tab3_posx + $tab3_width, $tab3_top + $y + 3);

				$i++;
			}
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}

		// Loop on each payment
		// TODO Call getListOfPaymentsgetListOfPayments instead of hard coded sql
		$sql = "SELECT p.datep as date, p.fk_paiement, p.num_paiement as num, pf.amount as amount, pf.multicurrency_amount,";
		$sql .= " cp.code";
		$sql .= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON p.fk_paiement = cp.id";
		$sql .= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = ".((int) $object->id);
		//$sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = 1";
		$sql .= " ORDER BY p.datep";

		$resql = $this->db->query($sql);
		if ($resql) {
			$num = $this->db->num_rows($resql);
			$i = 0;
			$y += 3;
			$maxY = $y;
			while ($i < $num) {
				$row = $this->db->fetch_object($resql);
				$pdf->SetXY($tab3_posx, $tab3_top + $y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date), 'day', false, $outputlangs, true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 21, $tab3_top + $y);
				$pdf->MultiCell(20, 3, price($sign * ((isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? $row->multicurrency_amount : $row->amount), 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx + 40, $tab3_top + $y);
				$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort".$row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$maxY = max($pdf->GetY() - $tab3_top - 3, $maxY);
				$pdf->SetXY($tab3_posx + 58, $tab3_top + $y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);
				$y = $maxY = max($pdf->GetY() - 3 - $tab3_top, $maxY);
				$pdf->line($tab3_posx, $tab3_top + $y + 3, $tab3_posx + $tab3_width, $tab3_top + $y + 3);
				$y += 3;
				$i++;
			}

			return $tab3_top + $y + 3;
		} else {
			$this->error = $this->db->lasterror();
			return -1;
		}
	}


	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		Facture		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param  	Translate	$outputlangsbis	Object lang for output bis
	 *   @return	int							Pos y
	 */
	protected function drawInfoTable(&$pdf, $object, $posy, $outputlangs, $outputlangsbis)
	{
		global $conf, $mysoc, $hookmanager;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', '', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && empty($mysoc->tva_assuj)) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			if ($mysoc->forme_juridique_code == 92) {
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoiceAsso"), 0, 'L', 0);
			} else {
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);
			}

			$posy = $pdf->GetY() + 4;
		}

		$posxval = 52;	// Position of values of properties shown on left side
		$posxend = 110;	// End of x for text on left side
		if ($this->page_largeur < 210) { // To work with US executive format
			$posxend -= 10;
		}

		// Show payments conditions
		if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement)) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("PaymentConditions").':';
			$pdf->MultiCell($posxval - $this->marge_gauche, 4, $titre, 0, 'L');

			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
			$lib_condition_paiement = ($outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) != 'PaymentCondition'.$object->cond_reglement_code) ? $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc ? $object->cond_reglement_doc : $object->cond_reglement_label);
			$lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);
			$pdf->MultiCell($posxend - $posxval, 4, $lib_condition_paiement, 0, 'L');

			$posy = $pdf->GetY() + 3; // We need spaces for 2 lines payment conditions
		}

		// Show category of operations
		if (getDolGlobalInt('INVOICE_CATEGORY_OF_OPERATION') == 2 && $this->categoryOfOperation >= 0) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$categoryOfOperationTitle = $outputlangs->transnoentities("MentionCategoryOfOperations").' : ';
			$pdf->MultiCell($posxval - $this->marge_gauche, 4, $categoryOfOperationTitle, 0, 'L');

			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
			$categoryOfOperationLabel = $outputlangs->transnoentities("MentionCategoryOfOperations" . $this->categoryOfOperation);
			$pdf->MultiCell($posxend - $posxval, 4, $categoryOfOperationLabel, 0, 'L');

			$posy = $pdf->GetY() + 3; // for 2 lines
		}

		if ($object->type != 2) {
			// Check a payment mode is defined
			if (empty($object->mode_reglement_code)
				&& !getDolGlobalInt('FACTURE_CHQ_NUMBER')
				&& !getDolGlobalInt('FACTURE_RIB_NUMBER')) {
				$this->error = $outputlangs->transnoentities("ErrorNoPaiementModeConfigured");
			} elseif (($object->mode_reglement_code == 'CHQ' && !getDolGlobalInt('FACTURE_CHQ_NUMBER') && empty($object->fk_account) && empty($object->fk_bank))
				|| ($object->mode_reglement_code == 'VIR' && !getDolGlobalInt('FACTURE_RIB_NUMBER') && empty($object->fk_account) && empty($object->fk_bank))) {
				// Avoid having any valid PDF with setup that is not complete
				$outputlangs->load("errors");

				$pdf->SetXY($this->marge_gauche, $posy);
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$this->error = $outputlangs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup", $object->mode_reglement_code);
				$pdf->MultiCell($posxend - $this->marge_gauche, 3, $this->error, 0, 'L', 0);
				$pdf->SetTextColor(0, 0, 0);

				$posy = $pdf->GetY() + 1;
			}

			// Show payment mode
			if (!empty($object->mode_reglement_code)
				&& $object->mode_reglement_code != 'CHQ'
				&& $object->mode_reglement_code != 'VIR') {
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->SetXY($this->marge_gauche, $posy);
				$titre = $outputlangs->transnoentities("PaymentMode").':';
				$pdf->MultiCell($posxend - $this->marge_gauche, 5, $titre, 0, 'L');

				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posxval, $posy);
				$lib_mode_reg = $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) != 'PaymentType'.$object->mode_reglement_code ? $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);

				//#21654: add account number used for the debit
				if ($object->mode_reglement_code == "PRE") {
					require_once DOL_DOCUMENT_ROOT.'/societe/class/companybankaccount.class.php';
					$bac = new CompanyBankAccount($this->db);
					// @phan-suppress-next-line PhanPluginSuspiciousParamPosition
					$bac->fetch(0, $object->thirdparty->id);
					$iban = $bac->iban.(($bac->iban && $bac->bic) ? ' / ' : '').$bac->bic;
					$lib_mode_reg .= ' '.$outputlangs->trans("PaymentTypePREdetails", dol_trunc($iban, 6, 'right', 'UTF-8', 1));
				}

				$pdf->MultiCell($posxend - $posxval, 5, $lib_mode_reg, 0, 'L');

				$posy = $pdf->GetY();
			}

			// Show if Option VAT debit option is on also if transmitter is french
			// Decret n°2099-1299 2022-10-07
			// French mention : "Option pour le paiement de la taxe d'après les débits"
			if ($this->emetteur->country_code == 'FR') {
				if (getDolGlobalInt('TAX_MODE') == 1) {
					$pdf->SetXY($this->marge_gauche, $posy);
					$pdf->writeHTMLCell(80, 5, '', '', $outputlangs->transnoentities("MentionVATDebitOptionIsOn"), 0, 1);

					$posy = $pdf->GetY() + 1;
				}
			}

			// Show online payment link
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CB' || $object->mode_reglement_code == 'VAD') {
				$useonlinepayment = 0;
				if (getDolGlobalString('PDF_SHOW_LINK_TO_ONLINE_PAYMENT')) {
					if (isModEnabled('paypal')) {
						$useonlinepayment++;
					}
					if (isModEnabled('stripe')) {
						$useonlinepayment++;
					}
					if (isModEnabled('paybox')) {
						$useonlinepayment++;
					}
					$parameters = array();
					$action = '';
					$reshook = $hookmanager->executeHooks('doShowOnlinePaymentUrl', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks
					if ($reshook > 0) {
						if (isset($hookmanager->resArray['showonlinepaymenturl'])) {
							$useonlinepayment += $hookmanager->resArray['showonlinepaymenturl'];
						}
					}
				}


				if ($object->statut != Facture::STATUS_DRAFT && $useonlinepayment) {
					require_once DOL_DOCUMENT_ROOT.'/core/lib/payments.lib.php';
					global $langs;

					$langs->loadLangs(array('payment', 'paybox', 'stripe'));
					$servicename = $langs->transnoentities('Online');
					$paiement_url = getOnlinePaymentUrl('', 'invoice', $object->ref, '', '', '');
					$linktopay = $langs->trans("ToOfferALinkForOnlinePayment", $servicename).' <a href="'.$paiement_url.'">'.$outputlangs->transnoentities("ClickHere").'</a>';

					$pdf->SetXY($this->marge_gauche, $posy);
					$pdf->writeHTMLCell($posxend - $this->marge_gauche, 5, '', '', dol_htmlentitiesbr($linktopay), 0, 1);

					$posy = $pdf->GetY() + 1;
				}
			}

			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ') {
				// If payment mode unregulated or payment mode forced to CHQ
				if (getDolGlobalInt('FACTURE_CHQ_NUMBER')) {
					$diffsizetitle = getDolGlobalInt('PDF_DIFFSIZE_TITLE', 3);

					if (getDolGlobalInt('FACTURE_CHQ_NUMBER') > 0) {
						$account = new Account($this->db);
						$account->fetch(getDolGlobalInt('FACTURE_CHQ_NUMBER'));

						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell($posxend - $this->marge_gauche, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $account->proprio), 0, 'L', 0);
						$posy = $pdf->GetY() + 1;

						if (!getDolGlobalString('MAIN_PDF_HIDE_CHQ_ADDRESS')) {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell($posxend - $this->marge_gauche, 3, $outputlangs->convToOutputCharset($account->owner_address), 0, 'L', 0);
							$posy = $pdf->GetY() + 2;
						}
					}
					if (getDolGlobalString('FACTURE_CHQ_NUMBER') == -1) {
						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell($posxend - $this->marge_gauche, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $this->emetteur->name), 0, 'L', 0);
						$posy = $pdf->GetY() + 1;

						if (!getDolGlobalString('MAIN_PDF_HIDE_CHQ_ADDRESS')) {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell($posxend - $this->marge_gauche, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
							$posy = $pdf->GetY() + 2;
						}
					}
				}
			}

			// If payment mode not forced or forced to VIR, show payment with BAN
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR') {
				if ($object->fk_account > 0 || $object->fk_bank > 0 || getDolGlobalInt('FACTURE_RIB_NUMBER')) {
					$bankid = ($object->fk_account <= 0 ? getDolGlobalInt('FACTURE_RIB_NUMBER') : $object->fk_account);
					if ($object->fk_bank > 0) {
						$bankid = $object->fk_bank; // For backward compatibility when object->fk_account is forced with object->fk_bank
					}
					$account = new Account($this->db);
					$account->fetch($bankid);

					$curx = $this->marge_gauche;
					$cury = $posy;

					$posy = pdf_bank($pdf, $outputlangs, $curx, $cury, $account, 0, $default_font_size);

					$posy += 2;
				}
			}
		}

		return $posy;
	}


	/**
	 *  Show total to pay
	 *
	 *  @param	TCPDF		$pdf            Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Amount already paid (in the currency of invoice)
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Object langs
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *	@return int							Position pour suite
	 */
	protected function drawTotalTable(&$pdf, $object, $deja_regle, $posy, $outputlangs, $outputlangsbis)
	{
		global $conf, $mysoc, $hookmanager;

		$sign = 1;
		if ($object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
			$sign = -1;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs(array("main", "dict", "companies", "bills", "products", "propal"));
			$default_font_size--;
		}

		$tab2_top = $posy-4;
		$tab2_hl = 4;
		$pdf->SetFont('', '', $default_font_size - 1);

		// Total table
		$col1x = 120;
		$col2x = 170;
		if ($this->page_largeur < 210) { // To work with US executive format
			$col2x -= 20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

		$useborder = 0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("TotalHT") : ''), 0, 'L', 1);

		$total_ht = ((!empty($conf->multicurrency->enabled) && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ht : $object->total_ht);
		$pdf->SetXY($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($total_ht, 0, $outputlangs), 0, 'R', 1);

		$remise = !empty($object->remise) ? $object->remise : 0;
		if ($remise > 0) {
			$index++;
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("DiscountHT").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("DiscountHT") : ''), 0, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($remise, 0, $outputlangs), 0, 'R', 1);

			$index++;
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHTWithDiscount").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("TotalHTWithDiscount") : ''), 0, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($total_ht - $remise, 0, $outputlangs), 0, 'R', 1);
		}

		// Show VAT by rates and total
		$pdf->SetFillColor(248, 248, 248);

		$total_ttc = (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;

		$this->atleastoneratenotnull = 0;
		if (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT')) {
			$tvaisnull = ((!empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL') && $tvaisnull) {
				// Nothing to do
			} else {
				//Local tax 1 before VAT
				foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('1', '3', '5'))) {
						continue;
					}

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey != 0) {    // On affiche pas taux 0
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}

							$totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code).(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transcountrynoentities("TotalLT1", $mysoc->country_code) : '');
							$totalvat .= ' ';

							if (getDolGlobalString('PDF_LOCALTAX1_LABEL_IS_CODE_OR_RATE') == 'nocodenorate') {
								$totalvat .= $tvacompl;
							} else {
								$totalvat .= vatrate(abs($tvakey), 1).$tvacompl;
							}

							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$total_localtax = ((isModEnabled("multicurrency") && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? price2num($tvaval * $object->multicurrency_tx, 'MT') : $tvaval);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($total_localtax, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}

				//Local tax 2 before VAT
				foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('1', '3', '5'))) {
						continue;
					}

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey != 0) {    // On affiche pas taux 0
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code).(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transcountrynoentities("TotalLT2", $mysoc->country_code) : '');
							$totalvat .= ' ';

							if (getDolGlobalString('PDF_LOCALTAX2_LABEL_IS_CODE_OR_RATE') == 'nocodenorate') {
								$totalvat .= $tvacompl;
							} else {
								$totalvat .= vatrate(abs($tvakey), 1).$tvacompl;
							}

							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$total_localtax = ((isModEnabled("multicurrency") && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? price2num($tvaval * $object->multicurrency_tx, 'MT') : $tvaval);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($total_localtax, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}
				//}

				// VAT
				$tvas = array();
				$nblines = count($object->lines);
				for ($i=0; $i < $nblines; $i++) {
					$tvaligne = $object->lines[$i]->total_tva;
					$vatrate=(string) $object->lines[$i]->tva_tx;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) {
						$vatrate.='*';
					}
					if (! isset($tvas[$vatrate])) {
						$tvas[$vatrate]=0;
					}
					$tvas[$vatrate] += $tvaligne;
				}

				foreach ($tvas as $tvakey => $tvaval) {
					if ($tvakey != 0) {	// On affiche pas taux 0
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl = '';
						if (preg_match('/\*/', $tvakey)) {
							$tvakey = str_replace('*', '', $tvakey);
							$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat = $outputlangs->transcountrynoentities("TotalVAT", $mysoc->country_code).(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transcountrynoentities("TotalVAT", $mysoc->country_code) : '');
						$totalvat .= ' ';
						if (getDolGlobalString('PDF_VAT_LABEL_IS_CODE_OR_RATE') == 'rateonly') {
							$totalvat .= vatrate($tvaval['vatrate'], 1).$tvacompl;
						} elseif (getDolGlobalString('PDF_VAT_LABEL_IS_CODE_OR_RATE') == 'codeonly') {
							$totalvat .= $tvaval['vatcode'].$tvacompl;
						} elseif (getDolGlobalString('PDF_VAT_LABEL_IS_CODE_OR_RATE') == 'nocodenorate') {
							$totalvat .= $tvacompl;
						} else {
							$totalvat .= vatrate($tvaval['vatrate'], 1).($tvaval['vatcode'] ? ' ('.$tvaval['vatcode'].')' : '').$tvacompl;
						}
						$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price(price2num($tvaval['amount'], 'MT'), 0, $outputlangs), 0, 'R', 1);
					}
				}

				//Local tax 1 after VAT
				foreach ($this->localtax1 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('2', '4', '6'))) {
						continue;
					}

					foreach ($localtax_rate as $tvakey => $tvaval) {
						if ($tvakey != 0) {    // On affiche pas taux 0
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code).(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transcountrynoentities("TotalLT1", $mysoc->country_code) : '');
							$totalvat .= ' ';

							if (getDolGlobalString('PDF_LOCALTAX1_LABEL_IS_CODE_OR_RATE') == 'nocodenorate') {
								$totalvat .= $tvacompl;
							} else {
								$totalvat .= vatrate(abs($tvakey), 1).$tvacompl;
							}

							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$total_localtax = ((isModEnabled("multicurrency") && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? price2num($tvaval * $object->multicurrency_tx, 'MT') : $tvaval);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($total_localtax, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}

				//Local tax 2 after VAT
				foreach ($this->localtax2 as $localtax_type => $localtax_rate) {
					if (in_array((string) $localtax_type, array('2', '4', '6'))) {
						continue;
					}

					foreach ($localtax_rate as $tvakey => $tvaval) {
						// retrieve global local tax
						if ($tvakey != 0) {    // On affiche pas taux 0
							//$this->atleastoneratenotnull++;

							$index++;
							$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

							$tvacompl = '';
							if (preg_match('/\*/', $tvakey)) {
								$tvakey = str_replace('*', '', $tvakey);
								$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
							}
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code).(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transcountrynoentities("TotalLT2", $mysoc->country_code) : '');
							$totalvat .= ' ';

							if (getDolGlobalString('PDF_LOCALTAX2_LABEL_IS_CODE_OR_RATE') == 'nocodenorate') {
								$totalvat .= $tvacompl;
							} else {
								$totalvat .= vatrate(abs($tvakey), 1).$tvacompl;
							}

							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$total_localtax = ((isModEnabled("multicurrency") && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? price2num($tvaval * $object->multicurrency_tx, 'MT') : $tvaval);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($total_localtax, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}


				// Revenue stamp
				if (price2num($object->revenuestamp) != 0) {
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RevenueStamp").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("RevenueStamp", $mysoc->country_code) : ''), $useborder, 'L', 1);

					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->revenuestamp), $useborder, 'R', 1);
				}

				// Total TTC
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFillColor(224, 224, 224);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("TotalTTC") : ''), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $total_ttc, 0, $outputlangs), $useborder, 'R', 1);


				// Retained warranty
				if ($object->displayRetainedWarranty()) {
					$pdf->SetTextColor(40, 40, 40);
					$pdf->SetFillColor(255, 255, 255);

					$retainedWarranty = $object->getRetainedWarrantyAmount();
					$billedWithRetainedWarranty = $object->total_ttc - $retainedWarranty;

					// Billed - retained warranty
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("ToPayOn", dol_print_date($object->date_lim_reglement, 'day')), $useborder, 'L', 1);

					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($billedWithRetainedWarranty), $useborder, 'R', 1);

					// retained warranty
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

					$retainedWarrantyToPayOn = $outputlangs->transnoentities("RetainedWarranty").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("RetainedWarranty") : '').' ('.$object->retained_warranty.'%)';
					$retainedWarrantyToPayOn .= !empty($object->retained_warranty_date_limit) ? ' '.$outputlangs->transnoentities("toPayOn", dol_print_date($object->retained_warranty_date_limit, 'day')) : '';

					$pdf->MultiCell($col2x - $col1x, $tab2_hl, $retainedWarrantyToPayOn, $useborder, 'L', 1);
					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($retainedWarranty), $useborder, 'R', 1);
				}
			}
		}

		$pdf->SetTextColor(0, 0, 0);

		$resteapayer = 0;
		/*
		$resteapayer = $object->total_ttc - $deja_regle;
		if (! empty($object->paye)) $resteapayer=0;
		*/

		if ($deja_regle > 0) {
			// Already paid + Deposits
			$index++;

			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("AlreadyPaid") : ''), 0, 'L', 0);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle, 0, $outputlangs), 0, 'R', 0);

			/*
			if ($object->close_code == 'discount_vat')
			{
				$index++;
				$pdf->SetFillColor(255,255,255);

				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle, 0, $outputlangs), $useborder, 'R', 1);

				$resteapayer=0;
			}
			*/

			$index++;
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay").(is_object($outputlangsbis) ? ' / '.$outputlangsbis->transnoentities("RemainderToPay") : ''), $useborder, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor(0, 0, 0);
		}

		$parameters = array('pdf' => &$pdf, 'object' => &$object, 'outputlangs' => $outputlangs, 'index' => &$index);

		$reshook = $hookmanager->executeHooks('afterPDFTotalTable', $parameters, $this); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			$this->error = $hookmanager->error;
			$this->errors = $hookmanager->errors;
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param	DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		return parent::liste_modeles($db, $maxfilenamelength); // TODO: Change the autogenerated stub
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		int 		$tab_top		Top position of table
	 *   @param		int 		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @param		Translate	$outputlangsbis	Langs object bis
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		$hidetop=0;

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		if (empty($hidetop)) {
			// Show category of operations
			if (getDolGlobalInt('INVOICE_CATEGORY_OF_OPERATION') == 1 && $this->categoryOfOperation >= 0) {
				$categoryOfOperations = $outputlangs->transnoentities("MentionCategoryOfOperations") . ' : ' . $outputlangs->transnoentities("MentionCategoryOfOperations" . $this->categoryOfOperation);
				$pdf->SetXY($this->marge_gauche, $tab_top - 4);
				$pdf->MultiCell(($pdf->GetStringWidth($categoryOfOperations)) + 4, 2, $categoryOfOperations);
			}

			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			// MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_droite-$this->marge_gauche, 5, 'F', null, explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
			}
			$tab_top+=4;
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

		// situation invoice
		$pdf->SetFont('', '', $default_font_size - 2);

		foreach ($this->cols as $colKey => $colDef) {
			if (!$this->getColumnStatus($colKey)) {
				continue;
			}
			$xstartpos = (int) ($colDef['xStartPos'] ?? 0);
			//is there any overtitle ?
			if (!empty($colDef['overtitle']) && is_array($colDef['overtitle'])) {
				$overtitle_top = $tab_top - 4;
				$overtitle = $colDef['overtitle']['textkey'] ?? '';
				$textWidth = $colDef['overtitle']['width'] ?? 0;
				$pdf->SetXY($xstartpos + $colDef['overtitle']['padding'][3], $overtitle_top);
				$pdf->MultiCell($textWidth, 2, $overtitle, '', $colDef['overtitle']['align']);
				$pdf->line($xstartpos, $overtitle_top, $xstartpos, $overtitle_top + 4); //left
				$pdf->line($xstartpos, $overtitle_top, $xstartpos + $textWidth, $overtitle_top); //top
				$pdf->line($xstartpos + $textWidth, $overtitle_top, $xstartpos + $textWidth, $overtitle_top + 4); //right
			}

			// get title label
			$colDef['title']['label'] = !empty($colDef['title']['label']) ? $colDef['title']['label'] : $outputlangs->transnoentities($colDef['title']['textkey']);

			// Add column separator
			if (!empty($colDef['border-left'])) {
				$pdf->line($xstartpos, $tab_top, $xstartpos, $tab_top + $tab_height);
			}

			if (empty($hidetop)) {
				$pdf->SetXY($xstartpos + $colDef['title']['padding'][3], $tab_top + $colDef['title']['padding'][0]);

				$textWidth = $colDef['width'] - $colDef['title']['padding'][3] -$colDef['title']['padding'][1];
				$pdf->MultiCell($textWidth, 2, $colDef['title']['label'], '', $colDef['title']['align']);
			}
		}
		$pdf->SetFont('', '', $default_font_size - 1);

		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);	// line prend une position y en 2eme param et 4eme param
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page. This include the logo, ref and address blocks
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Facture		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes (usually set to 1 for first page, and 0 for next pages)
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	array						top shift of linked object lines
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		// phpcs:enable
		global $conf, $langs;

		$ltrdirection = 'L';
		if ($outputlangs->trans("DIRECTION") == 'rtl') {
			$ltrdirection = 'R';
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		if (!getDolGlobalInt('PDF_DISABLE_MYCOMPANY_LOGO')) {
			if ($this->emetteur->logo) {
				$logodir = $conf->mycompany->dir_output;
				if (!empty($conf->mycompany->multidir_output[$object->entity])) {
					$logodir = $conf->mycompany->multidir_output[$object->entity];
				}
				if (!getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
					$logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
				} else {
					$logo = $logodir.'/logos/'.$this->emetteur->logo;
				}
				if (is_readable($logo)) {
					$height = pdf_getHeightForLogo($logo);
					$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
				} else {
					$pdf->SetTextColor(200, 0, 0);
					$pdf->SetFont('', 'B', $default_font_size - 2);
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			} else {
				$text = $this->emetteur->name;
				$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, $ltrdirection);
			}
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$subtitle = "";
		$title = $outputlangs->transnoentities("PdfInvoiceTitle");
		if ($object->type == 1) {
			$title = $outputlangs->transnoentities("InvoiceReplacement");
		}
		if ($object->type == 2) {
			$title = $outputlangs->transnoentities("InvoiceAvoir");
		}
		if ($object->type == 3) {
			$title = $outputlangs->transnoentities("InvoiceDeposit");
		}
		if ($object->type == 4) {
			$title = $outputlangs->transnoentities("InvoiceProForma");
		}
		if ($this->situationinvoice) {
			$title = $outputlangs->transnoentities("PDFInvoiceSituation");
			$subtitle = $outputlangs->transnoentities("PDFSituationTitle", $object->situation_counter);
		}
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
			$title .= ' - ';
			if ($object->type == 0) {
				if ($this->situationinvoice) {
					$title .= $outputlangsbis->transnoentities("PDFInvoiceSituation");
				}
				$title .= $outputlangsbis->transnoentities("PdfInvoiceTitle");
			} elseif ($object->type == 1) {
				$title .= $outputlangsbis->transnoentities("InvoiceReplacement");
			} elseif ($object->type == 2) {
				$title .= $outputlangsbis->transnoentities("InvoiceAvoir");
			} elseif ($object->type == 3) {
				$title .= $outputlangsbis->transnoentities("InvoiceDeposit");
			} elseif ($object->type == 4) {
				$title .= $outputlangsbis->transnoentities("InvoiceProForma");
			}
		}
		$title .= ' '.$outputlangs->convToOutputCharset($object->ref);
		// if ($object->statut == $object::STATUS_DRAFT) {
		// 	$pdf->SetTextColor(128, 0, 0);
		// 	$title .= ' - '.$outputlangs->transnoentities("NotValidated");
		// }

		$pdf->MultiCell($w, 3, $title, '', 'R');
		if (!empty($subtitle)) {
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->SetXY($posx, $posy+5);
			$pdf->MultiCell($w, 6, $subtitle, '', 'R');
			$posy += 2;
		}

		$pdf->SetFont('', 'B', $default_font_size);

		/*
		 $posy += 5;
		 $pdf->SetXY($posx, $posy);
		 $pdf->SetTextColor(0, 0, 60);
		 $textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
		 if ($object->statut == $object::STATUS_DRAFT) {
		 $pdf->SetTextColor(128, 0, 0);
		 $textref .= ' - '.$outputlangs->transnoentities("NotValidated");
		 }
		 $pdf->MultiCell($w, 4, $textref, '', 'R');*/

		$posy += 3;
		$pdf->SetFont('', '', $default_font_size - 2);

		if ($object->ref_customer) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefCustomer")." : ".dol_trunc($outputlangs->convToOutputCharset($object->ref_customer), 65), '', 'R');
		}

		if (getDolGlobalString('PDF_SHOW_PROJECT_TITLE')) {
			$object->fetch_projet();
			if (!empty($object->project->ref)) {
				$posy += 3;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Project")." : ".(empty($object->project->title) ? '' : $object->project->title), '', 'R');
			}
		}

		if (getDolGlobalString('PDF_SHOW_PROJECT')) {
			$object->fetch_projet();
			if (!empty($object->project->ref)) {
				$outputlangs->load("projects");
				$posy += 3;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefProject")." : ".(empty($object->project->ref) ? '' : $object->project->ref), '', 'R');
			}
		}

		$objectidnext = $object->getIdReplacingInvoice('validated');
		if ($object->type == 0 && $objectidnext) {
			$objectreplacing = new Facture($this->db);
			$objectreplacing->fetch($objectidnext);

			$posy += 3;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($objectreplacing->ref), '', 'R');
		}
		if ($object->type == 1) {
			$objectreplaced = new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}
		if ($object->type == 2 && !empty($object->fk_facture_source)) {
			$objectreplaced = new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy += 3;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);

		$title = $outputlangs->transnoentities("DateInvoice");
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
			$title .= ' - '.$outputlangsbis->transnoentities("DateInvoice");
		}
		$pdf->MultiCell($w, 3, $title." : ".dol_print_date($object->date, "day", false, $outputlangs, true), '', 'R');

		if (getDolGlobalString('INVOICE_POINTOFTAX_DATE')) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("DatePointOfTax")." : ".dol_print_date($object->date_pointoftax, "day", false, $outputlangs), '', 'R');
		}

		if ($object->type != 2) {
			$posy += 3;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$title = $outputlangs->transnoentities("DateDue");
			if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
				$title .= ' - '.$outputlangsbis->transnoentities("DateDue");
			}
			$pdf->MultiCell($w, 3, $title." : ".dol_print_date($object->date_lim_reglement, "day", false, $outputlangs, true), '', 'R');
		}

		if (!getDolGlobalString('MAIN_PDF_HIDE_CUSTOMER_CODE') && $object->thirdparty->code_client) {
			$posy += 3;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}

		// Get contact
		if (getDolGlobalString('DOC_SHOW_FIRST_SALES_REP')) {
			$arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
			if (count($arrayidcontact) > 0) {
				$usertmp = new User($this->db);
				$usertmp->fetch($arrayidcontact[0]);
				$posy += 4;
				$pdf->SetXY($posx, $posy);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->MultiCell($w, 3, $langs->transnoentities("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
			}
		}

		$posy += 1;

		$top_shift = 0;
		$shipp_shift = 0;
		// Show list of linked objects
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY()) {
			$top_shift = $pdf->getY() - $current_y;
		}

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
			$posy += $top_shift;
			$posx = $this->marge_gauche;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->page_largeur - $this->marge_droite - 80;
			}

			$hautcadre = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 38 : 40;
			$widthrecbox = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 82;

			// Show sender frame
			if (!getDolGlobalString('MAIN_PDF_NO_SENDER_FRAME')) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx, $posy - 5);
				$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillFrom"), 0, $ltrdirection);
				$pdf->SetXY($posx, $posy);
				$pdf->SetFillColor(230, 230, 230);
				$pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
				$pdf->SetTextColor(0, 0, 60);
			}

			// Show sender name
			if (!getDolGlobalString('MAIN_PDF_HIDE_SENDER_NAME')) {
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, $ltrdirection);
				$posy = $pdf->getY();
			}

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, $ltrdirection);

			// If BILLING contact defined on invoice, we use it
			$usecontact = false;
			$arrayidcontact = $object->getIdContact('external', 'BILLING');
			if (count($arrayidcontact) > 0) {
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}

			// Recipient name
			if ($usecontact && ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || getDolGlobalString('MAIN_USE_COMPANY_NAME_OF_CONTACT')))) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$mode = 'target';
			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, $mode, $object);

			// Show recipient
			$widthrecbox = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84; // To work with US executive format
			}
			$posy = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
			$posy += $top_shift;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->marge_gauche;
			}

			// Show recipient frame
			if (!getDolGlobalString('MAIN_PDF_NO_RECIPENT_FRAME')) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx + 2, $posy - 5);
				$pdf->MultiCell($widthrecbox - 2, 5, $outputlangs->transnoentities("BillTo"), 0, $ltrdirection);
				$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
			}

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
			$pdf->MultiCell($widthrecbox - 2, 2, $carac_client_name, 0, $ltrdirection);

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
			$pdf->MultiCell($widthrecbox - 2, 4, $carac_client, 0, $ltrdirection);

			// Show shipping address
			if (getDolGlobalInt('INVOICE_SHOW_SHIPPING_ADDRESS')) {
				$idaddressshipping = $object->getIdContact('external', 'SHIPPING');

				if (!empty($idaddressshipping)) {
					$contactshipping = $object->fetch_Contact($idaddressshipping[0]);
					$companystatic = new Societe($this->db);
					$companystatic->fetch($object->contact->fk_soc);
					$carac_client_name_shipping = pdfBuildThirdpartyName($object->contact, $outputlangs);
					$carac_client_shipping = pdf_build_address($outputlangs, $this->emetteur, $companystatic, $object->contact, $usecontact, 'target', $object);
				} else {
					$carac_client_name_shipping = pdfBuildThirdpartyName($object->thirdparty, $outputlangs);
					$carac_client_shipping = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'target', $object);
				}
				if (!empty($carac_client_shipping)) {
					$posy += $hautcadre;

					// Show shipping frame
					$pdf->SetXY($posx + 2, $posy - 5);
					$pdf->SetFont('', '', $default_font_size - 2);
					$pdf->MultiCell($widthrecbox, '', $outputlangs->transnoentities('ShippingTo'), 0, 'L', 0);
					$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

					// Show shipping name
					$pdf->SetXY($posx + 2, $posy + 3);
					$pdf->SetFont('', 'B', $default_font_size);
					$pdf->MultiCell($widthrecbox - 2, 2, $carac_client_name_shipping, '', 'L');

					$posy = $pdf->getY();

					// Show shipping information
					$pdf->SetXY($posx + 2, $posy);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->MultiCell($widthrecbox - 2, 2, $carac_client_shipping, '', 'L');
					$shipp_shift += $hautcadre;
				}
			}
		}

		$pdf->SetTextColor(0, 0, 0);

		$pagehead = array('top_shift' => $top_shift, 'shipp_shift' => $shipp_shift);

		return $pagehead;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Facture		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		return pdf_pagefoot($pdf, $outputlangs, 'INVOICE_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur, $this->watermark);
	}

	/**
	 *  Define Array Column Field
	 *
	 *  @param	Facture		   $object    		common object
	 *  @param	Translate	   $outputlangs     langs
	 *  @param	int			   $hidedetails		Do not show line details
	 *  @param	int			   $hidedesc		Do not show desc
	 *  @param	int			   $hideref			Do not show ref
	 *  @return	void
	 */
	public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $hookmanager;

		// Default field style for content
		$this->defaultContentsFieldsStyle = array(
			'align' => 'R', // R,C,L
			'padding' => array(1, 0.5, 1, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		// Default field style for content
		$this->defaultTitlesFieldsStyle = array(
			'align' => 'C', // R,C,L
			'padding' => array(0.5, 0, 0.5, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		/*
		 * For example
		 $this->cols['theColKey'] = array(
		 'rank' => $rank, // int : use for ordering columns
		 'width' => 20, // the column width in mm
		 'title' => array(
		 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
		 'label' => ' ', // the final label : used fore final generated text
		 'align' => 'L', // text alignment :  R,C,L
		 'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		 ),
		 'content' => array(
		 'align' => 'L', // text alignment :  R,C,L
		 'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		 ),
		 );
		 */

		$rank = 0; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Designation', // use lang key is useful in somme case with module
				'align' => 'L',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'L',
				'padding' => array(1, 0.5, 1, 1.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
		);

		// Image of product
		$rank = $rank + 10;
		$this->cols['photo'] = array(
			'rank' => $rank,
			'width' => getDolGlobalString('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH', 20), // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Photo',
				'label' => ' '
			),
			'content' => array(
				'padding' => array(0, 0, 0, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'border-left' => false, // remove left line separator
		);

		if (getDolGlobalString('MAIN_GENERATE_INVOICES_WITH_PICTURE') && !empty($this->atleastonephoto)) {
			$this->cols['photo']['status'] = true;
		}


		$rank = $rank + 10;
		$this->cols['vat'] = array(
			'rank' => $rank,
			'status' => false,
			'width' => 10, // in mm
			'title' => array(
				'textkey' => 'VAT'
			),
			'border-left' => true, // add left line separator
		);

		if (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') && !getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN')) {
			$this->cols['vat']['status'] = true;
		}

		$rank = $rank + 10;
		$this->cols['unit'] = array(
			'rank' => $rank,
			'width' => 11, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Unit'
			),
			'border-left' => true, // add left line separator
		);
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			$this->cols['unit']['status'] = false;
		}

		$rank = $rank + 10;
		$this->cols['subprice'] = array(
			'rank' => $rank,
			'width' => 17, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'PriceUHT'
			),
			'border-left' => true, // add left line separator
		);

		// Adapt dynamically the width of subprice, if text is too long.
		$tmpwidth = 0;
		$nblines = count($object->lines);
		for ($i = 0; $i < $nblines; $i++) {
			$tmpwidth2 = dol_strlen(dol_string_nohtmltag(pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails)));
			$tmpwidth = max($tmpwidth, $tmpwidth2);
		}
		if ($tmpwidth > 10) {
			$this->cols['subprice']['width'] += (2 * ($tmpwidth - 10));
		}

		$rank = $rank + 10;
		$this->cols['qty'] = array(
			'rank' => $rank,
			'width' => 10, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Qty'
			),
			'border-left' => true, // add left line separator
		);
		//situation invoices
		$this->cols['qty']['status'] = true;

		//sum column
		$rank = $rank + 10;
		$this->cols['btpsomme'] = array(
			'rank' => $rank,
			'width' => 18, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Chantier'
			),
			'border-left' => true, // add left line separator
			'overtitle' => array(
				'textkey' => 'Chantier', // use lang key is useful in somme case with module
				'align' => 'C',
				'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
				'width' => 18
			),
		);
		if (!empty($this->TDataSituation['date_derniere_situation'])) {
			$this->cols['btpsomme']['status'] = true;
		}

		$derniere_situation = $this->TDataSituation['derniere_situation'];

		if (empty($derniere_situation)) {
			$derniere_situation = 0;
		}

		// Column 'Previous progression'
		$rank = $rank + 10;
		$this->cols['prev_progress'] = array(
			'rank' => $rank,
			'width' => 10, // in mm
			'status' => false,
			'title' => array(
				'textkey' => $outputlangs->transnoentities('ProgressShort')
			),
			'border-left' => true, // add left line separator
			'overtitle' => array(
				'textkey' => 'S'.$derniere_situation->situation_counter . ' - ' . dol_print_date($derniere_situation->date, "%d/%m/%Y"),
				'align' => 'C',
				'padding' => array(0.5,0.2,0.5,0.2), // Like css 0 => top, 1 => right, 2 => bottom, 3 => left
				'width' => 10+15 //current width + amount cell width
			),
		);
		if ($this->situationinvoice && ! empty($this->TDataSituation['date_derniere_situation'])) {
			$this->cols['prev_progress']['status'] = true;
		}

		// Column 'Previous progression'
		$rank = $rank + 10;
		$this->cols['prev_progress_amount'] = array(
			'rank' => $rank,
			'width' => 15, // in mm
			'status' => false,
			'title' => array(
				'textkey' => $outputlangs->transnoentities('Amount')
			),
			'border-left' => true, // add left line separator
		);
		if ($this->situationinvoice && ! empty($this->TDataSituation['date_derniere_situation'])) {
			$this->cols['prev_progress_amount']['status'] = true;
		}

		// Column 'Current percent progress'
		$rank = $rank + 10;
		$this->cols['progress'] = array(
			'rank' => $rank,
			'width' => 10, // in mm
			'status' => true,
			'title' => array(
				'textkey' => $outputlangs->transnoentities('ProgressShort')
			),
			'border-left' => true, // add left line separator
			'overtitle' => array(
				'textkey' => 'S'.$object->situation_counter . ' - ' . dol_print_date($object->date, "%d/%m/%Y"),
				'align' => 'C',
				'padding' => array(0.5,0.2,0.5,0.2), // Like css 0 => top, 1 => right, 2 => bottom, 3 => left
				'width' => 10+15
			),
		);

		// Column 'Current progress'
		$rank = $rank + 10;
		$this->cols['progress_amount'] = array(
			'rank' => $rank,
			'width' => 15, // in mm
			'status' => true,
			'title' => array(
				'textkey' => $outputlangs->transnoentities('Amount')
			),
			'border-left' => true, // add left line separator
		);
		if ($this->situationinvoice) {
			$this->cols['progress_amount']['status'] = true;
		}

		// FIN BTP SITUATION

		$rank = $rank + 10;
		$this->cols['discount'] = array(
			'rank' => $rank,
			'width' => 10, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'ReductionShort'
			),
			'border-left' => true, // add left line separator
		);
		if ($this->atleastonediscount) {
			$this->cols['discount']['status'] = true;
		}
		$rank = $rank + 10;
		$this->cols['totalexcltax'] = array(
			'rank' => $rank,
			'width' => 18, // in mm
			'status' => true,
			'title' => array(
				'textkey' => $outputlangs->transnoentities('TotalHT')
			),
			'border-left' => true, // add left line separator
		);

		$parameters = array(
			'object' => $object,
			'outputlangs' => $outputlangs,
			'hidedetails' => $hidedetails,
			'hidedesc' => $hidedesc,
			'hideref' => $hideref
		);

		$reshook = $hookmanager->executeHooks('defineColumnField', $parameters, $this); // Note that $object may have been modified by hook
		if ($reshook < 0) {
			setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
		} elseif (empty($reshook)) {
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrderInternal
			$this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
		} else {
			$this->cols = $hookmanager->resArray;
		}
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf	 		Object PDF
	 *   @param		int  		$tab_top		Top position of table
	 *   @param		int 		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	public function _tableFirstPage(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
	{
		global $conf, $object, $db;

		$form = new Form($db);

		$tab_height -= 29; // Réduction de la hauteur global du tableau
		$displayWarranty = $this->displayRetainedWarranty($object);
		if (!$displayWarranty) {
			$tab_height -= 19; // Réduction de la hauteur global du tableau
		}


		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) {
			$hidetop=-1;
		}

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		if (empty($hidetop)) {
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-8);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			$width = $this->page_largeur-$this->marge_gauche-$this->marge_droite-83;

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
				$pdf->Rect($this->posx_cumul_anterieur-1, $tab_top, $width, 5, 'F', null, explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
				$pdf->Rect($this->marge_gauche, $tab_top + 92.5, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 5, 'F', null, explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
			}
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		// KEEPTHIS => Affiche les bords extérieurs
		$this->printRectBtp($pdf, $this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

		$pdf->line($this->posx_cumul_anterieur-1, $tab_top, $this->posx_cumul_anterieur-1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posx_cumul_anterieur-1, $tab_top+0.5);
			$pdf->MultiCell(35, 2, $outputlangs->transnoentities("SituationInvoiceOldCumulation"), '', 'C');
		}

		// PRINT COLUMNS TITLES
		$pdf->line($this->posx_new_cumul-1, $tab_top, $this->posx_new_cumul-1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posx_new_cumul-1, $tab_top+0.5);
			$pdf->MultiCell(35, 2, $outputlangs->transnoentities("SituationInvoiceNewCumulation"), '', 'C');
		}

		$pdf->line($this->posx_current-1, $tab_top, $this->posx_current-1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posx_current-1, $tab_top+0.5);
			$pdf->MultiCell(36, 2, $outputlangs->transnoentities("CurrentSituationTotal", $object->situation_counter), '', 'C');
		}

		// ADD HORIZONTAL LINES
		$pdf->line($this->posx_cumul_anterieur-1, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);

		$pdf->line($this->posx_cumul_anterieur-1, $tab_top+24, $this->page_largeur-$this->marge_droite, $tab_top+24);

		$pdf->line($this->marge_gauche, $tab_top+55, $this->page_largeur-$this->marge_droite, $tab_top+55);

		$pdf->line($this->marge_gauche, $tab_top + 65, $this->page_largeur - $this->marge_droite, $tab_top + 65);

		if ($displayWarranty) {
			$pdf->line($this->marge_gauche, $tab_top+85, $this->page_largeur-$this->marge_droite, $tab_top+85);
		}


		// ADD TEXT INTO CELL
		/********************** Titles ******************************/
		$pdf->SetXY($this->marge_gauche+2, $tab_top+8);
		$pdf->MultiCell(60, 2, $outputlangs->transnoentities("SituationInvoiceMainTask"), '', 'L');

		$pdf->SetXY($this->marge_gauche+2, $tab_top+12);
		$pdf->MultiCell(60, 2, $outputlangs->transnoentities("SituationInvoiceAdditionalTask"), '', 'L');

		$form->load_cache_vatrates("'".$object->thirdparty->country_code."'");

		$i = -8;
		foreach ($form->cache_vatrates as $TVatInfo) {
			$tva_tx_formated = sprintf("%01.3f", (float) $TVatInfo['txtva']);
			// print "<p>Un taux de tva ... $tva_tx_formated :: " . json_encode($this->TDataSituation['current'][$tva_tx_formated]) . "</p>";
			if (empty($this->TDataSituation['current'][$tva_tx_formated])) {
				continue;
			}
			$i += 8;

			$pdf->SetXY($this->marge_gauche+10, $tab_top+24 + $i);
			$pdf->MultiCell(80, 2, $outputlangs->transnoentities("TotalHT").' '.$TVatInfo['label'], '', 'L');

			if (! empty($this->TDataSituation['current'][$tva_tx_formated]['TVA'])) {
				$pdf->SetXY($this->marge_gauche + 10, $tab_top + 28 + $i);
				$pdf->MultiCell(80, 2, $outputlangs->transnoentities("VAT").' '.$TVatInfo['label'], '', 'L');
			} else {
				$i -= 4;
			}
		}

		$pdf->SetXY($this->marge_gauche+2, $tab_top+33+$i);
		$pdf->MultiCell(80, 2, $outputlangs->transnoentities("TotalTTC"), '', 'L');


		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetXY($this->marge_gauche+2, $tab_top+58);
		$pdf->MultiCell(80, 2, $outputlangs->transnoentities("TotalSituationInvoice"), '', 'L');
		$pdf->SetFont('', '', $default_font_size - 2);

		if ($displayWarranty) {
			$pdf->SetXY($this->marge_gauche + 2, $tab_top + 74);
			$pdf->MultiCell(80, 2, $outputlangs->trans("TotalSituationInvoiceWithRetainedWarranty", $object->retained_warranty), '', 'L');
			$nextY = $tab_top+93;
		} else {
			$nextY = $tab_top+74;
		}

		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetXY($this->marge_gauche+2, $nextY);
		$pdf->MultiCell(80, 2, $outputlangs->transnoentities("SituationTotalRayToRest"), '', 'L');
		$pdf->SetFont('', '', $default_font_size - 2);
		/***********************************************************/

		/********************** Data *******************************/
		$TToDisplay = array(
			'cumul_anterieur',
			'nouveau_cumul',
			'current'
		);

		$x = $this->marge_gauche+85;
		// unset($this->TDataSituation['derniere_situation']);
		// print json_encode($object->lines);exit;
		// print json_encode($this->TDataSituation);exit;
		foreach ($TToDisplay as $col) {
			// Travaux principaux
			$pdf->SetXY($x, $tab_top+8);
			$pdf->MultiCell(32, 2, price($this->TDataSituation[$col]['HT'], 0, '', 1, -1, 2), '', 'R');

			// Travaux supplémentaires
			$pdf->SetXY($x, $tab_top+12);
			$pdf->MultiCell(32, 2, price($this->TDataSituation[$col]['travaux_sup'], 0, '', 1, -1, 2), '', 'R');

			$i = -8;
			foreach ($form->cache_vatrates as $TVatInfo) {
				$tva_tx_formated = sprintf("%01.3f", (float) $TVatInfo['txtva']);
				if (empty($this->TDataSituation['current'][$tva_tx_formated])) {
					continue;
				}
				$i += 8;

				// Total HT
				$pdf->SetXY($x, $tab_top+24+$i);
				$pdf->MultiCell(32, 2, price($this->TDataSituation[$col][$tva_tx_formated]['HT'], 0, '', 1, -1, 2), '', 'R');

				// Total TVA
				if (! empty($this->TDataSituation['current'][$tva_tx_formated]['TVA'])) {
					$pdf->SetXY($x, $tab_top + 28 + $i);
					$pdf->MultiCell(32, 2, price($this->TDataSituation[$col][$tva_tx_formated]['TVA'], 0, '', 1, -1, 2), '', 'R');
				} else {
					$i -= 4;
				}
			}

			// Total TTC
			$pdf->SetXY($x, $tab_top+33+$i);
			$pdf->MultiCell(32, 2, price($this->TDataSituation[$col]['TTC'], 0, '', 1, -1, 2), '', 'R');

			// Total situation
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->SetXY($x, $tab_top+58);
			$pdf->MultiCell(32, 2, price($this->TDataSituation[$col]['TTC'], 0, '', 1, -1, 2), '', 'R');
			$pdf->SetFont('', '', $default_font_size - 2);


			if ($displayWarranty) {
				// Retained warranty
				$pdf->SetXY($x, $tab_top+74);
				$pdf->MultiCell(32, 2, price($this->TDataSituation[$col]['retenue_garantie'], 0, '', 1, -1, 2), '', 'R');
				$nextY = $tab_top+93;
			} else {
				$nextY = $tab_top+74;
			}

			// Amount payable incl. VAT
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->SetXY($x, $nextY);
			$pdf->MultiCell(32, 2, price($this->TDataSituation[$col]['total_a_payer'], 0, '', 1, -1, 2), '', 'R');
			$pdf->SetFont('', '', $default_font_size - 2);

			$x+=36;
		}
		/************************************************************/
	}


	/**
	 * Recovers data from situation invoices
	 *
	 * NOTE :
	 * 	Main work: lines on the status invoice that were already present on the previous invoice
	 * 	Additional work: lines on the status invoice that have been added to the previous invoice
	 * 	Example : S1 with l1 (tp), l2 (tp)
	 * 			  S2 with l1 (tp), l2 (tp), l3 (ts)
	 * 			  S3 with l1 (tp), l2 (tp), l3 (tp), l4 (ts)
	 *
	 * @param   Facture $object  Facture
	 *
	 * @return  array
	 *
	 * Details of returned table
	 *
	 * cumul_anterieur: data from previous status invoice
	 * nouveau_cumul: Cumulative data from all invoices up to the current one
	 * current: current status invoice data
	 *
	 */
	public function getDataSituation(&$object)
	{
		global $conf, $db;

		// Fetch previous and next situations invoices.
		// Return all previous and next invoices (both standard and credit notes)
		$object->fetchPreviousNextSituationInvoice();
		/** @var Facture[] $TPreviousInvoices */
		$TPreviousInvoices = $object->tab_previous_situation_invoice;
		unset($object->tab_previous_situation_invoice);

		// liste de toutes les factures précédentes
		// print json_encode($TPreviousInvoices); exit;

		$TPreviousInvoices = array_reverse($TPreviousInvoices);
		$facDerniereSituation = $TPreviousInvoices[0];

		$TDataSituation = array();

		if (! empty($facDerniereSituation)) {
			$TDataSituation['derniere_situation'] = $facDerniereSituation;
			$TDataSituation['date_derniere_situation'] = $facDerniereSituation->date;
		}

		$retenue_garantie = 0;
		$retenue_garantie_anterieure = 0;
		// Init tous les champs à 0
		$TDataSituation['cumul_anterieur'] = array(
			'HT' => 0,	//montant HT normal
			'TVA' => 0,   //montant de la TVA sur le HTnet
			'TTC' => 0,   //montant TTC (HTnet + TVA)
			'retenue_garantie' => 0,
			'travaux_sup' => 0,
			'HTnet' => 0, //montant HT
			'total_a_payer' => 0 //montant "a payer" sur la facture
		);

		//S'il y a des factures de situations précédentes
		if (!empty($TPreviousInvoices)) {
			//calcul des cumuls -- plus necessaire ?
			foreach ($TPreviousInvoices as $i => $previousInvoice) {
				$TDataSituation['cumul_anterieur']['HT'] += $previousInvoice->total_ht;
				// $TDataSituation['cumul_anterieur']['TTC'] += $previousInvoice->total_ttc;
				$TDataSituation['cumul_anterieur']['TVA'] += $previousInvoice->total_tva;

				//lecture de chaque ligne pour
				// 1. recalculer le total_ht pour chaque taux de TVA
				// 2. recalculer la TVA associée à ce montant HT
				// 3. le cas échéant stocker cette information comme travaux_sup si cette ligne n'est pas liée à une ligne de la situation précédente
				foreach ($previousInvoice->lines as $k => $l) {
					$total_ht = floatval($l->total_ht);
					if (empty($total_ht)) {
						continue;
					}

					// Si $prevSituationPercent vaut 0 c'est que la ligne $l est un travail supplémentaire
					$prevSituationPercent = 0;
					$isFirstSituation = false;
					if (!empty($l->fk_prev_id)) {
						$prevSituationPercent = $l->get_prev_progress($previousInvoice->id, true);
					} elseif (! array_key_exists($i+1, $TPreviousInvoices)) {
						$isFirstSituation = true;
					}

					$calc_ht = $l->total_ht;
					//modification du format de TVA, cas particulier des imports ou autres qui peuvent avoir des 20.0000
					$ltvatx = (float) sprintf("%01.3f", $l->tva_tx);

					//1ere ligne
					$amounttva = $calc_ht * ($ltvatx/100);
					if (! isset($TDataSituation['cumul_anterieur'][$ltvatx])) {
						$TDataSituation['cumul_anterieur'][$ltvatx]['HT'] = $calc_ht;
						$TDataSituation['cumul_anterieur'][$ltvatx]['TVA'] = $amounttva;
					} else {
						//lignes suivantes
						$TDataSituation['cumul_anterieur'][$ltvatx]['HT'] += ($calc_ht);
						$TDataSituation['cumul_anterieur'][$ltvatx]['TVA'] += $amounttva;
					}

					//le grand total de TVA
					// $TDataSituation['cumul_anterieur']['TVA'] += $amounttva;

					if (empty($l->fk_prev_id) && ! $isFirstSituation) {
						// TODO: à clarifier, mais pour moi, un facture de situation précédente qui a des progressions à 0% c'est pas logique
						$TDataSituation['cumul_anterieur']['travaux_sup'] += $calc_ht;
					}
				}
			}

			if (! empty($previousInvoice->retained_warranty) && !getDolGlobalString('USE_RETAINED_WARRANTY_ONLY_FOR_SITUATION_FINAL')) {
				$retenue_garantie_anterieure += $previousInvoice->getRetainedWarrantyAmount();
			}

			//les cumuls
			$TDataSituation['cumul_anterieur']['HT'] -= $TDataSituation['cumul_anterieur']['travaux_sup'];
			$TDataSituation['cumul_anterieur']['retenue_garantie'] = $retenue_garantie_anterieure;
			$TDataSituation['cumul_anterieur']['TTC'] = $TDataSituation['cumul_anterieur']['HT'] + $TDataSituation['cumul_anterieur']['TVA'];
			$TDataSituation['cumul_anterieur']['total_a_payer'] = $TDataSituation['cumul_anterieur']['TTC'] - $retenue_garantie_anterieure;
		}

		// print json_encode($facDerniereSituation->lines);exit;
		$TDataSituation['current'] = $this->btpGetInvoiceAmounts($object->id);

		if (! empty($facDerniereSituation->lines)) {
			$TFacLinesKey = array_keys($facDerniereSituation->lines);
			$TObjectLinesKey = array_keys($object->lines);
			$TDiffKey = array_diff($TObjectLinesKey, $TFacLinesKey);

			// print json_encode($TDiffKey);exit;

			foreach ($TDiffKey as $i) {
				if (empty($object->lines[$i]->fk_prev_id)) {
					$TDataSituation['nouveau_cumul']['travaux_sup'] += $object->lines[$i]->total_ht;
					$TDataSituation['current']['travaux_sup'] += $object->lines[$i]->total_ht;
				}
			}
		}

		//Le nouveau cumul = cumul antérieur + current
		$TDataSituation['nouveau_cumul'] = $this->sumSituation($TDataSituation['current'], $TDataSituation['cumul_anterieur']);

		return $TDataSituation;
	}

	/**
	 * Calculates the sum of two arrays, key by key, taking into account nested arrays
	 *
	 * @param   array  $a  [$a description]
	 * @param   array  $b  [$b description]
	 *
	 * @return  array	  [return description]
	 */
	public function sumSituation($a, $b)
	{
		$ret = array();
		if (is_array($a)) {
			foreach ($a as $k => $v) {
				if (is_array($v)) {
					$ret[$k] = $this->sumSituation($v, $b[$k]);
				} else {
					$ret[$k] = $a[$k];
					if (isset($b[$k])) {
						$ret[$k] += $b[$k];
					}
				}
			}
		} else {
			dol_syslog("sumSituation first arg is not an array");
		}

		return $ret;
	}

	/**
	 * Display retained Warranty
	 *
	 * @param   Facture $object  Facture
	 * @return	bool
	 */
	public function displayRetainedWarranty($object)
	{
		if (is_callable(array($object, 'displayRetainedWarranty'))) {
			return $object->displayRetainedWarranty();
		} else {
			// FOR RETROCOMPATIBILITY
			global $conf;

			// TODO : add a flag on invoices to store this conf USE_RETAINED_WARRANTY_ONLY_FOR_SITUATION_FINAL

			// note : we don't need to test USE_RETAINED_WARRANTY_ONLY_FOR_SITUATION because if $object->retained_warranty is not empty it's because it was set when this conf was active

			$displayWarranty = false;
			if (!empty($object->retained_warranty)) {
				$displayWarranty = true;

				if ($object->type == Facture::TYPE_SITUATION && getDolGlobalString('USE_RETAINED_WARRANTY_ONLY_FOR_SITUATION_FINAL')) {
					// Check if this situation invoice is 100% for real
					$displayWarranty = false;
					if (!empty($object->situation_final)) {
						$displayWarranty = true;
					} elseif (!empty($object->lines) && $object->status == Facture::STATUS_DRAFT) {
						// $object->situation_final need validation to be done so this test is need for draft
						$displayWarranty = true;

						foreach ($object->lines as $i => $line) {
							if ($line->product_type < 2 && $line->situation_percent < 100) {
								$displayWarranty = false;
								break;
							}
						}
					}
				}
			}
			return $displayWarranty;
		}
	}

	/**
	 * Get info line of the last situation
	 *
	 * @param  Facture	$object		Object
	 * @param  FactureLigne			$current_line	current line
	 * @return void|array{progress_prec:float,total_ht_without_progress:float,total_ht:float}
	 */
	public function getInfosLineLastSituation(&$object, &$current_line)
	{
		if (empty($object->situation_cycle_ref) || $object->situation_counter <= 1) {
			return;
		}

		$facDerniereSituation = &$this->TDataSituation['derniere_situation'];

		// Find the previous line of the line you are on
		foreach ($facDerniereSituation->lines as $l) {
			if ($l->rowid == $current_line->fk_prev_id) {
				// Recovery of total_ht without taking progress into account (for the "sums" column)
				$ltvatx = (float) sprintf("%01.3f", $l->tva_tx);
				$tabprice = calcul_price_total($l->qty, $l->subprice, $l->remise_percent, $ltvatx, $l->localtax1_tx, $l->localtax2_tx, 0, 'HT', $l->info_bits, $l->product_type);
				$total_ht  = $tabprice[0];
				$total_tva = $tabprice[1];
				$total_ttc = $tabprice[2];
				$total_localtax1 = $tabprice[9];
				$total_localtax2 = $tabprice[10];
				$pu_ht = $tabprice[3];

				return array(
					'progress_prec' => $l->situation_percent,
					'total_ht_without_progress' => $total_ht,
					'total_ht' => $l->total_ht,
				);
			}
		}
	}

	/**
	 * Rect pdf
	 *
	 * @param	TCPDF	$pdf			Object PDF
	 * @param	float	$x				Abscissa of first point
	 * @param	float	$y				Ordinate of first point
	 * @param	float	$l				??
	 * @param	float	$h				??
	 * @param	int		$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 * @param	int		$hidebottom		Hide bottom
	 * @return	void
	 */
	public function printRectBtp(&$pdf, $x, $y, $l, $h, $hidetop = 0, $hidebottom = 0)
	{
		if (empty($hidetop) || $hidetop==-1) {
			$pdf->line($x, $y, $x+$l, $y);
		}
		$pdf->line($x+$l, $y, $x+$l, $y+$h);
		if (empty($hidebottom)) {
			$pdf->line($x+$l, $y+$h, $x, $y+$h);
		}
		$pdf->line($x, $y+$h, $x, $y);
	}


	/**
	 * Get data about invoice
	 *
	 * @param   int  	$id					invoice id
	 * @param   boolean $forceReadFromDB  	set to true if you want to force refresh data from SQL
	 *
	 * @return  array	   [return description]
	 */
	public function btpGetInvoiceAmounts($id, $forceReadFromDB = false)
	{
		global $user,$langs,$conf,$mysoc,$db,$hookmanager,$nblignes;

		$object=new Facture($db);
		$object->fetch($id);

		/* from dolibarr core
		* Fetch previous and next situations invoices.
		* Return all previous and next invoices (both standard and credit notes).
		*/
		$object->fetchPreviousNextSituationInvoice();
		/** @var Facture[] $TPreviousInvoices */
		$TPreviousInvoices = $object->tab_previous_situation_invoice;
		unset($object->tab_previous_situation_invoice);

		$TPreviousInvoices = array_reverse($TPreviousInvoices);
		$facDerniereSituation = $TPreviousInvoices[0];

		$ret = array(
			'HT' => 0,	//montant HT normal
			'HTnet' => 0, //montant HT
			'TVA' => 0,   //montant de la TVA sur le HTnet
			'TTC' => 0,   //montant TTC (HTnet + TVA)
			'retenue_garantie' => 0,
			'travaux_sup' => 0,
			'total_a_payer' => 0 //montant "a payer" sur la facture
		);

		if (! empty($facDerniereSituation)) {
			$ret['derniere_situation'] = $facDerniereSituation;
			$ret['date_derniere_situation'] = $facDerniereSituation->date;
		}

		// Scroll through the lines of the current invoice to retrieve all data
		foreach ($object->lines as $k => $l) {
			$total_ht = floatval($l->total_ht);
			if (empty($total_ht)) {
				continue;
			}

			// Modification of VAT format, special case of imports or others which may have 20.0000
			$ltvatx = (float) sprintf("%01.3f", $l->tva_tx);

			$ret[$ltvatx]['TVA'] += $l->total_tva;
			$ret[$ltvatx]['HT'] += $l->total_ht;
		}

		// Retained warranty
		$retenue_garantie = $object->getRetainedWarrantyAmount();
		if ($retenue_garantie == -1) {
			$retenue_garantie = 0;
		}

		//les cumuls
		$ret['TTC'] = $object->total_ttc;
		$ret['TVA'] = $object->total_tva;
		$ret['HT'] = $object->total_ht - $ret['travaux_sup'];
		$ret['total_a_payer'] = $ret['TTC'] - $retenue_garantie;
		$ret['retenue_garantie'] = $retenue_garantie;

		//Clean up before keep in "cache"
		unset($ret['derniere_situation']->db);
		unset($ret['derniere_situation']->fields);
		unset($ret['derniere_situation']->lines);

		// print "<p>Store to cache $id : " . json_encode($_cache_btpProrataGetInvoiceAmounts[$id]) . "</p>";
		return $ret;
	}


	/**
	 *  Show last page with a resume of all invoices
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Amount already paid (in the currency of invoice)
	 *	@param	int			$posy           Position depart
	 *	@param	Translate	$outputlangs    Object langs
	 *  @param  Translate	$outputlangsbis Object lang for output bis
	 *	@return void
	 */
	public function resumeLastPage(&$pdf, $object, $deja_regle, $posy, $outputlangs, $outputlangsbis)
	{
		global $conf, $mysoc, $hookmanager;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$sign = 1;
		if ($object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
			$sign = -1;
		}

		$pdf->AddPage();
		if (!empty($this->tplidx)) {
			$pdf->useTemplate($this->tplidx);
		}

		$pagehead = $this->_pagehead($pdf, $object, 0, $outputlangs, $outputlangsbis);

		$tab2_top = $this->tab_top_newpage - 4;
		$posy = $tab2_top;
		$posx = $this->marge_gauche;
		$index = 1;
		$outputlangs->load('orders');
		$outputlangs->load('propal');
		$width = 70;
		$width2 = $this->page_largeur - $posx - $width - $this->marge_droite;

		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell(0, 3, ''); // Set interline to 3
		$pdf->SetTextColor(0, 0, 0);
		$pdf->setY($tab2_top);

		if (is_object($outputlangsbis)) {	// When we show 2 languages we need more room for text, so we use a smaller font.
			$pdf->SetFont('', '', $default_font_size - 2);
		} else {
			$pdf->SetFont('', '', $default_font_size - 1);
		}

		if (empty($object->tab_previous_situation_invoice)) {
			$object->fetchPreviousNextSituationInvoice();
		}

		$previousinvoices = count($object->tab_previous_situation_invoice) ? $object->tab_previous_situation_invoice : array();

		$remain_to_pay = 0;

		// Proposal total
		$propals = array();
		$orders = array();

		if (count($previousinvoices)) {
			foreach ($previousinvoices as $invoice) {
				if ($invoice->is_first()) {
					$invoice->fetchObjectLinked();

					$propals = isset($invoice->linkedObjects['propal']) ? $invoice->linkedObjects['propal'] : array();
					$orders = isset($invoice->linkedObjects['commande']) ? $invoice->linkedObjects['commande'] : array();
				}
			}
		} else {
			if ($object->is_first()) {
				$object->fetchObjectLinked();

				$propals = isset($object->linkedObjects['propal']) ? $object->linkedObjects['propal'] : array();
				$orders = isset($object->linkedObjects['commande']) ? $object->linkedObjects['commande'] : array();
			}
		}

		if (count($propals)) {
			$propal = array_pop($propals);

			$total_ht = ($conf->multicurrency->enabled && $propal->mylticurrency_tx != 1) ? $propal->multicurrency_total_ht : $propal->total_ht;
			$remain_to_pay = $total_ht;

			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFont('', '', $default_font_size - 1);

			$label = $outputlangs->transnoentities("SituationInvoiceTotalProposal");
			$pdf->MultiCell($this->page_largeur-($this->marge_droite+$this->marge_gauche), 3, $label, 0, 'L', 0, 1, $posx, $posy+1);

			$amount = price($sign * ($total_ht + (! empty($propal->remise)?$propal->remise:0)));
			$pdf->MultiCell($width2, 3, $amount, 0, 'R', 0, 1, $posx+$width, $posy+1);

			$pdf->SetFont('', '', $default_font_size - 1);

			// Output Rect
			$pdf->SetDrawColor(128, 128, 128);
			$this->printRect($pdf, $posx, $posy, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 6);	// Rect prend une longueur en 3eme param et 4eme param

			$posy += 4;
		} elseif (count($orders)) {
			$order = array_pop($orders);

			$total_ht = ($conf->multicurrency->enabled && $order->mylticurrency_tx != 1 ? $order->multicurrency_total_ht : $order->total_ht);
			$remain_to_pay = $total_ht;
		}

		$useborder=0;
		$index = 0;

		$height = 4;

		$sign = 1;
		if ($object->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
			$sign = -1;
		}
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);


		$pdf->SetXY($posx, $posy);


		$depositsamount=$object->getSumDepositsUsed();
		$deja_regle = $object->getSommePaiement();

		$tot_deja_regle = ($depositsamount + $deja_regle);

		$previousinvoices[] = $object;

		$force_to_zero = false;

		$idinv = 0;//count($previousinvoices);
		while ($idinv < count($previousinvoices)) {
			$invoice = $previousinvoices[$idinv];

			$posy += 7;
			$index = 0;

			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFont('', 'B', $default_font_size + 3);

			$pageposbefore = $pdf->getPage();
			$pdf->startTransaction();

			$pdf->SetXY($posx, $posy);

			$ref = $outputlangs->transnoentities("InvoiceSituation").$outputlangs->convToOutputCharset(" n°".$invoice->situation_counter);

			if ($invoice->situation_final) {
				$ref.= ' - DGD';
				$force_to_zero = true;
			}

			$ref.= ' - '. $invoice->ref;
			$ref.= ' ('.dol_print_date($invoice->date, "%d/%m/%Y", false, $outputlangs).')';
			$pdf->MultiCell($this->page_largeur-($this->marge_droite+$this->marge_gauche), 3, $ref, 0, 'L', 0);

			$pdf->SetFont('', '', $default_font_size - 1);

			$sign = 1;
			if ($invoice->type == 2 && getDolGlobalString('INVOICE_POSITIVE_CREDIT_NOTE')) {
				$sign = -1;
			}

			$posy += 7;
			// Total HT
			$pdf->SetFillColor(255, 255, 255);
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell($width, $height, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

			$total_ht = ($conf->multicurrency->enabled && $invoice->mylticurrency_tx != 1 ? $invoice->multicurrency_total_ht : $invoice->total_ht);
			$pdf->SetXY($posx+$width, $posy);
			$pdf->MultiCell($width2, $height, price($sign * ($total_ht + (!empty($invoice->remise)?$invoice->remise:0)), 0, $outputlangs), 0, 'R', 1);

			$tvas = array();
			$nblines = count($invoice->lines);
			for ($i=0; $i < $nblines; $i++) {
				$tvaligne = $invoice->lines[$i]->total_tva;
				$vatrate = (string) $invoice->lines[$i]->tva_tx;

				if (($invoice->lines[$i]->info_bits & 0x01) == 0x01) {
					$vatrate.='*';
				}
				if (! isset($tvas[$vatrate])) {
					$tvas[$vatrate]=0;
				}
				$tvas[$vatrate] += $tvaligne;
			}

			// Show VAT by rates and total
			$pdf->SetFillColor(248, 248, 248);
			foreach ($tvas as $tvakey => $tvaval) {
				if ($tvakey != 0) {	// On affiche pas taux 0
					$index++;
					$pdf->SetXY($posx, $posy + $height * $index);

					$tvacompl = '';
					if (preg_match('/\*/', $tvakey)) {
						$tvakey = str_replace('*', '', $tvakey);
						$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
					}
					$totalvat = $outputlangs->transcountrynoentities("TotalVAT", $mysoc->country_code).' ';
					$totalvat.= vatrate($tvakey, 1).$tvacompl;
					$pdf->MultiCell($width, $height, $totalvat, 0, 'L', 1);

					$pdf->SetXY($posx+$width, $posy + $height * $index);
					$pdf->MultiCell($width2, $height, price($tvaval, 0, $outputlangs), 0, 'R', 1);
				}
			}

			$index++;

			$total_ht = ($conf->multicurrency->enabled && $invoice->multicurrency_tx != 1) ? $invoice->multicurrency_total_ht : $invoice->total_ht;
			$total_ttc = ($conf->multicurrency->enabled && $invoice->multicurrency_tx != 1) ? $invoice->multicurrency_total_ttc : $invoice->total_ttc;

			// Total TTC
			$pdf->SetXY($posx, $posy + $height * $index);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->MultiCell($width, $height, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);


			$pdf->SetXY($posx+$width, $posy + $height * $index);
			$pdf->MultiCell($width2, $height, price($sign * $total_ttc, 0, $outputlangs), $useborder, 'R', 1);

			$retainedWarrantyRate = (float) ($object->retained_warranty ? price2num($object->retained_warranty) : price2num(getDolGlobalString('INVOICE_SITUATION_DEFAULT_RETAINED_WARRANTY_PERCENT', 0)));

			$total_ht_rg = 0;
			$total_ttc_rg = 0;

			if ($this->is_rg) {
				$index++;

				$pdf->SetXY($posx, $posy + $height * $index);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFillColor(241, 241, 241);
				$pdf->MultiCell($width, $height, $outputlangs->transnoentities("RetainedWarrantyShort", $retainedWarrantyRate), $useborder, 'L', 1);

				$total_ht_rg = (float) price2num(price($total_ht * $retainedWarrantyRate/100), 'MT');
				$total_ttc_rg = (float) price2num(price($total_ttc * $retainedWarrantyRate/100), 'MT');

				$pdf->SetXY($posx+$width, $posy + $height * $index);
				$pdf->MultiCell($width2, $height, price(-$sign * $total_ht_rg, 0, $outputlangs), $useborder, 'R', 1);

				$total_ht_with_rg = $total_ht - $total_ht_rg;
				$total_ttc_with_rg = $total_ttc - $total_ttc_rg;

				$index++;

				// Total TTC
				$pdf->SetXY($posx, $posy + $height * $index);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFillColor(224, 224, 224);
				$pdf->MultiCell($width, $height, $outputlangs->transnoentities("TotalSituationInvoiceWithRetainedWarranty"), $useborder, 'L', 1);

				$pdf->SetXY($posx+$width, $posy + $height * $index);
				$pdf->MultiCell($width2, $height, price($sign * $total_ttc_with_rg, 0, $outputlangs), $useborder, 'R', 1);
			}



			$index++;

			$pdf->SetTextColor(0, 0, 0);

			$creditnoteamount = $invoice->getSumCreditNotesUsed();
			$depositsamount = $invoice->getSumDepositsUsed();
			$deja_regle = $invoice->getSommePaiement();

			$resteapayer = price2num($invoice->total_ttc - $deja_regle - $total_ttc_rg - $creditnoteamount - $depositsamount, 'MT');
			if ($invoice->paye) $resteapayer = 0;

			$y = 0;


			// Already paid + Deposits
			$tot_deja_regle += $deja_regle + $depositsamount;

			$pdf->SetXY($posx, $posy + $height * $index);
			$pdf->MultiCell($width, $height, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
			$pdf->SetXY($posx+$width, $posy + $height * $index);
			$pdf->MultiCell($width2, $height, price($deja_regle + $depositsamount, 0, $outputlangs), 0, 'R', 0);

			// Credit note
			if ($creditnoteamount) {
				$index++;
				$pdf->SetXY($posx, $posy + $height * $index);
				$pdf->MultiCell($width, $height, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
				$pdf->SetXY($posx+$width, $posy + $height * $index);
				$pdf->MultiCell($width2, $height, price($creditnoteamount, 0, $outputlangs), 0, 'R', 0);
			}

			// Escompte
			if ($invoice->close_code == Facture::CLOSECODE_DISCOUNTVAT) {
				$index++;
				$pdf->SetFillColor(255, 255, 255);

				$pdf->SetXY($posx, $posy + $height * $index);
				$pdf->MultiCell($width, $height, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);
				$pdf->SetXY($posx+$width, $posy + $height * $index);
				$pdf->MultiCell($width2, $height, price($invoice->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs), $useborder, 'R', 1);

				$resteapayer=0;
			}

			$index++;
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->SetXY($posx, $posy + $height * $index);
			$pdf->MultiCell($width, $height, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
			$pdf->SetXY($posx+$width, $posy + $height * $index);
			$pdf->MultiCell($width2, $height, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor(0, 0, 0);

			$index++;

			if ($deja_regle > 0) {
				$title=$outputlangs->transnoentities("PaymentsAlreadyDone");
				if ($invoice->type == 2) $title=$outputlangs->transnoentities("PaymentsBackAlreadyDone");

				$pdf->SetFont('', '', $default_font_size - 3);
				$pdf->SetXY($posx, $posy + $height * $index);
				$pdf->MultiCell($width, $height, $title, 0, 'L', 0);

				//$pdf->line($tab3_posx, $tab3_top, $tab3_posx+$tab3_width, $tab3_top);

				$index++;

				$width4 = ($this->page_largeur - $this->marge_droite - $posx)/4;

				$pdf->SetFont('', '', $default_font_size - 4);
				$pdf->SetXY($posx, $posy + $height * $index);
				$pdf->MultiCell($width4, $height-1, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
				$pdf->SetXY($posx+$width4, $posy + $height * $index);
				$pdf->MultiCell($width4, $height-1, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
				$pdf->SetXY($posx+$width4*2, $posy + $height * $index);
				$pdf->MultiCell($width4, $height-1, $outputlangs->transnoentities("Type"), 0, 'L', 0);
				$pdf->SetXY($posx+$width4*3, $posy + $height * $index);
				$pdf->MultiCell($width4, $height-1, $outputlangs->transnoentities("Num"), 0, 'L', 0);

				//$pdf->line($tab3_posx, $tab3_top-1+$tab3_height, $tab3_posx+$tab3_width, $tab3_top-1+$tab3_height);

				$y=$height-1;

				$pdf->SetFont('', '', $default_font_size - 4);
				/** @var Facture $invoice */
				$payments = $invoice->getListOfPayments();

				if (count($payments)) {
					foreach ($payments as $payment) {
						$pdf->SetXY($posx, $posy + $height * $index + $y);
						$pdf->MultiCell($width4, $height-1, dol_print_date($this->db->jdate($payment['date']), 'day', false, $outputlangs, true), 0, 'L', 0);
						$pdf->SetXY($posx+$width4, $posy + $height * $index + $y);
						$pdf->MultiCell($width4, $height-1, price($sign * $payment['amount'], 0, $outputlangs), 0, 'L', 0);
						$pdf->SetXY($posx+$width4*2, $posy + $height * $index + $y);
						$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $payment['type']);

						$pdf->MultiCell($width4, $height-1, $oper, 0, 'L', 0);
						$pdf->SetXY($posx+$width4*3, $posy + $height * $index + $y);
						$pdf->MultiCell($width4, $height-1, $payment['num'], 0, 'L', 0);

						//$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);
						$y += ($height - 1);
					}
				}
			}

			// Output Rect
			$pdf->SetDrawColor(128, 128, 128);
			$this->printRect($pdf, $this->marge_gauche, $posy, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height * $index + $y);
			$posy += $height * $index + $y;

			$pageposafter=$pdf->getPage();
			if ($pageposafter > $pageposbefore) {	// There is a pagebreak
				$pdf->rollbackTransaction(true);

				$pageposafter=$pageposbefore;
				$pdf->AddPage('', '', true);
				if (!empty($this->tplidx)) {
					$pdf->useTemplate($this->tplidx);
				}
				if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) $this->_pagehead($pdf, $object, 0, $outputlangs);
				$pdf->setPage($pageposafter+1);
				$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

				$posy = $this->tab_top_newpage + 1;
			} else {
				$idinv++;
				$remain_to_pay -= ($sign * ($total_ht + (!empty($invoice->remise) ? $invoice->remise : 0)));

				$rem = 0;
				if (count($invoice->lines)) {
					foreach ($invoice->lines as $l) {
						if ($l->fk_remise_except > 0) {
							$discount = new DiscountAbsolute($this->db);
							$result = $discount->fetch($l->fk_remise_except);
							if ($result > 0) {
								$rem += $discount->amount_ht;
							}
						}
					}
				}

				$remain_to_pay -= $rem;

				$pdf->commitTransaction();
			}
		}

		if ($force_to_zero) {
			$remain_to_pay = 0;
		}

		$posy += 10;

		$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($this->marge_gauche, $posy + 1);
		$label = $outputlangs->transnoentities("SituationTotalRayToRest");
		$pdf->MultiCell($this->page_largeur-($this->marge_droite+$this->marge_gauche), 3, $label, 0, 'L', 0);

		$amount = price($remain_to_pay);
		$pdf->MultiCell($width2, 3, $amount, 0, 'R', 0, 1, $posx+$width, $posy+1);

		$pdf->SetDrawColor(128, 128, 128);
		$this->printRect($pdf, $this->marge_gauche, $posy, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 7);
	}
}
