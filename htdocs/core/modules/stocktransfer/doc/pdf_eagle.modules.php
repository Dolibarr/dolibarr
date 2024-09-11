<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2014-2015 Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2021 		Gauthier VERDOL 	<gauthier.verdol@atm-consulting.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
 *	\file       htdocs/core/modules/stocktransfer/doc/pdf_eagle.modules.php
 *	\ingroup    expedition
 *	\brief      Class file used to generate the dispatch slips for the Eagle model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/stocktransfer/modules_stocktransfer.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/product.lib.php';


/**
 *	Class to build sending documents with model Eagle
 */
class pdf_eagle extends ModelePDFStockTransfer
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
	 * @var string document type
	 */
	public $type;

	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * @var int posx lot
	 */
	public $posxlot;

	/**
	 * @var int posx weightvol
	 */
	public $posxweightvol;

	/**
	 * @var int posx warehousesource
	 */
	public $posxwarehousesource;

	/**
	 * @var int posx warehousedestination
	 */
	public $posxwarehousedestination;

	/**
	 * @var bool        True if at least one line of the StockTransfer object has a batch set.
	 *                  Populated by $pdf_eagle->atLeastOneBatch()
	 * @see atLeastOneBatch()
	 */
	public $atLeastOneBatch;

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs, $mysoc;

		$this->db = $db;
		$this->name = $langs->trans("StockTransferSheet");
		$this->description = $langs->trans("DocumentModelStandardPDF");

		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

		$this->option_logo = 1; // Display logo

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2);
		}

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1;
		$this->posxlot = $this->page_largeur - $this->marge_droite - 135;
		$this->posxweightvol = $this->page_largeur - $this->marge_droite - 115;
		$this->posxqty = $this->page_largeur - $this->marge_droite - 95;
		$this->posxwarehousesource = $this->page_largeur - $this->marge_droite - 70;
		$this->posxwarehousedestination = $this->page_largeur - $this->marge_droite - 35;
		$this->posxpuht = $this->page_largeur - $this->marge_droite;

		/*if (!empty($conf->global->STOCKTRANSFER_PDF_DISPLAY_AMOUNT_HT)) {	// Show also the prices
			$this->posxqty = $this->page_largeur - $this->marge_droite - 118;
			$this->posxwarehousesource = $this->page_largeur - $this->marge_droite - 96;
			$this->posxwarehousedestination = $this->page_largeur - $this->marge_droite - 68;
			$this->posxpuht = $this->page_largeur - $this->marge_droite - 40;
			$this->posxtotalht = $this->page_largeur - $this->marge_droite - 20;
		}*/

		if (getDolGlobalString('STOCKTRANSFER_PDF_HIDE_WEIGHT_AND_VOLUME')) {
			$this->posxweightvol = $this->posxqty;
		}

		$this->posxpicture = $this->posxweightvol - getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH', 20); // width of images
		//var_dump($this->posxpicture, $this->posxweightvol);exit;

		// To work with US executive format
		if ($this->page_largeur < 210) {
			$this->posxqty -= 20;
			$this->posxpicture -= 20;
			$this->posxwarehousesource -= 20;
			$this->posxwarehousedestination -= 20;
		}

		/*if (!empty($conf->global->STOCKTRANSFER_PDF_HIDE_ORDERED)) {
			$this->posxqty += ($this->posxwarehousedestination - $this->posxwarehousesource);
			$this->posxpicture += ($this->posxwarehousedestination - $this->posxwarehousesource);
			$this->posxwarehousesource = $this->posxwarehousedestination;
		}*/
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Function to build pdf onto disk
	 *
	 *	@param		StockTransfer	$object				Object StockTransfer to generate (or id if old method)
	 *	@param		Translate		$outputlangs		Lang output object
	 *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int<0,1>		$hidedetails		Do not show line details
	 *  @param		int<0,1>		$hidedesc			Do not show desc
	 *  @param		int<0,1>		$hideref			Do not show ref
	 *  @return     int<-1,1>      	    				1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $conf, $langs, $hookmanager;

		$object->fetch_thirdparty();

		$this->atLeastOneBatch = $this->atLeastOneBatch($object);

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by page
		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies", "propal", "deliveries", "sendings", "productbatch", "stocks", "stocktransfer@stocktransfer"));

		global $outputlangsbis;
		$outputlangsbis = null;
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs(array("main", "bills", "orders", "products", "dict", "companies", "propal", "deliveries", "sendings", "productbatch"));
		}

		$nblines = is_array($object->lines) ? count($object->lines) : 0;

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		$this->atleastonephoto = false;
		if (getDolGlobalString('MAIN_GENERATE_STOCKTRANSFER_WITH_PICTURE')) {
			$objphoto = new Product($this->db);

			for ($i = 0; $i < $nblines; $i++) {
				if (empty($object->lines[$i]->fk_product)) {
					continue;
				}

				$objphoto = new Product($this->db);
				$objphoto->fetch($object->lines[$i]->fk_product);
				if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
					$pdir = get_exdir($object->lines[$i]->fk_product, 2, 0, 0, $objphoto, 'product').$object->lines[$i]->fk_product."/photos/";
					$dir = $conf->product->dir_output.'/'.$pdir;
				} else {
					$pdir = get_exdir(0, 2, 0, 0, $objphoto, 'product').dol_sanitizeFileName($objphoto->ref).'/';
					$dir = $conf->product->dir_output.'/'.$pdir;
				}

				$realpath = '';

				foreach ($objphoto->liste_photos($dir, 1) as $key => $obj) {
					if (!getDolGlobalInt('CAT_HIGH_QUALITY_IMAGES')) {
						// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
						if ($obj['photo_vignette']) {
							$filename = $obj['photo_vignette'];
						} else {
							$filename = $obj['photo'];
						}
					} else {
						$filename = $obj['photo'];
					}

					$realpath = $dir.$filename;
					$this->atleastonephoto = true;
					break;
				}

				if ($realpath) {
					$realpatharray[$i] = $realpath;
				}
			}
		}

		if (count($realpatharray) == 0) {
			$this->posxpicture = $this->posxweightvol;
		}


		if (!empty($this->atLeastOneBatch)) {
			$this->posxpicture = $this->posxlot;
			if (getDolGlobalString('MAIN_GENERATE_STOCKTRANSFER_WITH_PICTURE')) {
				$this->posxpicture -= getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH', 20);
			} // width of images
		}

		if ($conf->stocktransfer->dir_output) {
			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->stocktransfer->dir_output;
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$stocktransferref = dol_sanitizeFileName($object->ref);
				$dir = $conf->stocktransfer->dir_output.'/'.$object->element."/".$stocktransferref;
				$file = $dir."/".$stocktransferref.".pdf";
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
				$nblines = is_array($object->lines) ? count($object->lines) : 0;

				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);
				$heightforinfotot = 8; // Height reserved to output the info and total part
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS')) {
					$heightforfooter += 6;
				}
				$pdf->SetAutoPageBreak(1, 0);

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (!getDolGlobalString('MAIN_DISABLE_FPDI') && getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/' . getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();  // @phan-suppress-current-line PhanUndeclaredMethod
				}

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("StockTransfer"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("StockTransfer"));
				if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
					$pdf->SetCompression(false);
				}

				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;
				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 90;	// position of top tab
				$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);

				$tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

				$tab_height_newpage = 150;

				// Incoterm
				$height_incoterms = 0;
				if (isModEnabled('incoterm')) {
					$desc_incoterms = $object->getIncotermsForPDF();
					if ($desc_incoterms) {
						$tab_top -= 2;

						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
						$nexY = $pdf->GetY();
						$height_incoterms = $nexY - $tab_top;

						// Rect takes a length in 3rd parameter
						$pdf->SetDrawColor(192, 192, 192);
						$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_incoterms + 1);

						$tab_top = $nexY + 6;
						$height_incoterms += 4;
					}
				}

				// Public note and Tracking code
				if (!empty($object->note_public) || !empty($object->tracking_number)) {
					$tab_top_alt = $tab_top;

					//$tab_top_alt += 1;

					// Tracking number
					if (!empty($object->tracking_number)) {
						$height_trackingnumber = 4;

						$pdf->SetFont('', 'B', $default_font_size - 2);
						$pdf->writeHTMLCell(60, $height_trackingnumber, $this->posxdesc - 1, $tab_top - 1, $outputlangs->transnoentities("TrackingNumber")." : ".$object->tracking_number, 0, 1, false, true, 'L');
						$tab_top_alt = $pdf->GetY();

						$object->getUrlTrackingStatus($object->tracking_number);
						if (!empty($object->tracking_url)) {
							if ($object->shipping_method_id > 0) {
								// Get code using getLabelFromKey
								$code = $outputlangs->getLabelFromKey($this->db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');
								$label = '';
								if ($object->tracking_url != $object->tracking_number) {
									$label .= $outputlangs->trans("LinkToTrackYourPackage")."<br>";
								}
								$label .= $outputlangs->trans("SendingMethod").": ".$outputlangs->trans("SendingMethod".strtoupper($code));
								//var_dump($object->tracking_url != $object->tracking_number);exit;
								if ($object->tracking_url != $object->tracking_number) {
									$label .= " : ";
									$label .= $object->tracking_url;
								}

								$height_trackingnumber += 4;
								$pdf->SetFont('', 'B', $default_font_size - 2);
								$pdf->writeHTMLCell(60, $height_trackingnumber, $this->posxdesc - 1, $tab_top_alt, $label, 0, 1, false, true, 'L');
							}
						}
						$tab_top = $pdf->GetY();
					}

					// Notes
					if (!empty($object->note_public)) {
						$pdf->SetFont('', '', $default_font_size - 1); // Dans boucle pour gerer multi-page
						$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top_alt, dol_htmlentitiesbr($object->note_public), 0, 1);
					}

					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

					$tab_height -= $height_note;
					$tab_top = $nexY + 6;
				} else {
					$height_note = 0;
				}

				// Show barcode
				$height_barcode = 0;
				//$pdf->Rect($this->marge_gauche, $this->marge_haute, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 30);
				if (isModEnabled('barcode') && getDolGlobalString('BARCODE_ON_STOCKTRANSFER_PDF')) {
					require_once DOL_DOCUMENT_ROOT.'/core/modules/barcode/doc/tcpdfbarcode.modules.php';

					$encoding = 'QRCODE';
					$module = new modTcpdfbarcode();
					$barcode_path = '';
					$result = 0;
					if ($module->encodingIsSupported($encoding)) {
						$result = $module->writeBarCode($object->ref, $encoding);

						// get path of qrcode image
						$newcode = $object->ref;
						if (!preg_match('/^\w+$/', $newcode) || dol_strlen($newcode) > 32) {
							$newcode = dol_hash($newcode, 'md5');
						}
						$barcode_path = $conf->barcode->dir_temp . '/barcode_' . $newcode . '_' . $encoding . '.png';
					}

					if ($result > 0) {
						$tab_top -= 2;

						$pdf->Image($barcode_path, $this->marge_gauche, $tab_top, 20, 20);

						$nexY = $pdf->GetY();
						$height_barcode = 20;

						$tab_top += 22;
					} else {
						$this->error = 'Failed to generate barcode';
					}
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				$TCacheEntrepots = array();
				// Loop on each lines
				for ($i = 0; $i < $nblines; $i++) {
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					// Define size of image if we need it
					$imglinesize = array();
					if (!empty($realpatharray[$i])) {
						$imglinesize = pdf_getSizeForImage($realpatharray[$i]);
					}

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					$posYAfterImage = 0;
					$posYAfterDescription = 0;
					$heightforsignature = 0;

					// We start with Photo of product line
					if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// If photo too high, we moved completely on new page
						$pdf->AddPage('', '', true);
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						$pdf->setPage($pageposbefore + 1);

						$curY = $tab_top_newpage;

						// Allows data in the first page if description is long enough to break in multiples pages
						if (getDolGlobalString('MAIN_PDF_DATA_ON_FIRST_PAGE')) {
							$showpricebeforepagebreak = 1;
						} else {
							$showpricebeforepagebreak = 0;
						}
					}

					if (isset($imglinesize['width']) && isset($imglinesize['height'])) {
						$curX = $this->posxpicture - 1;
						$pdf->Image($realpatharray[$i], $curX + (($this->posxqty - $this->posxweightvol - $imglinesize['width']
									+ (getDolGlobalString('STOCKTRANSFER_PDF_HIDE_WEIGHT_AND_VOLUME') ? getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH', 20) : 0)) / 2), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300); // Use 300 dpi
						// $pdf->Image does not increase value return by getY, so we save it manually
						$posYAfterImage = $curY + $imglinesize['height'];
					}

					// Description of product line
					$curX = $this->posxdesc - 1;

					$pdf->startTransaction();
					if (method_exists($object->lines[$i], 'fetch_product')) {
						$object->lines[$i]->fetch_product();
						$object->lines[$i]->label = $object->lines[$i]->product->label;
						$object->lines[$i]->description = $object->lines[$i]->product->description;
						$object->lines[$i]->weight = $object->lines[$i]->product->weight;
						$object->lines[$i]->weight_units = $object->lines[$i]->product->weight_units;
						$object->lines[$i]->length = $object->lines[$i]->product->length;
						$object->lines[$i]->length_units = $object->lines[$i]->product->length_units;
						$object->lines[$i]->surface = $object->lines[$i]->product->surface;
						$object->lines[$i]->surface_units = $object->lines[$i]->product->surface_units;
						$object->lines[$i]->volume = $object->lines[$i]->product->volume;
						$object->lines[$i]->volume_units = $object->lines[$i]->product->volume_units;
						$object->lines[$i]->fk_unit = $object->lines[$i]->product->fk_unit;
						//var_dump($object->lines[$i]);exit;
					}

					pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture - $curX, 3, $curX, $curY, $hideref, $hidedesc);

					$pageposafter = $pdf->getPage();
					if ($pageposafter > $pageposbefore) {	// There is a pagebreak
						$pdf->rollbackTransaction(true);
						$pageposafter = $pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture - $curX, 3, $curX, $curY, $hideref, $hidedesc);

						$pageposafter = $pdf->getPage();
						$posyafter = $pdf->GetY();
						//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// There is no space left for total+free text
							if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
								$pdf->AddPage('', '', true);
								if (!empty($tplidx)) {
									$pdf->useTemplate($tplidx);
								}
								if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
									$this->_pagehead($pdf, $object, 0, $outputlangs);
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

					$nexY = max($pdf->GetY(), $posYAfterImage);
					$pageposafter = $pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

					// Lot / série
					if (isModEnabled('productbatch')) {
						$pdf->SetXY($this->posxlot, $curY);
						$pdf->MultiCell(($this->posxweightvol - $this->posxlot), 3, $object->lines[$i]->batch, '', 'C');
					}

					// weight

					$pdf->SetXY($this->posxweightvol, $curY);
					$weighttxt = '';
					if (empty($object->lines[$i]->fk_product_type) && $object->lines[$i]->weight) {
						$weighttxt = round($object->lines[$i]->weight * $object->lines[$i]->qty, 5).' '.measuringUnitString(0, "weight", $object->lines[$i]->weight_units, 1);
					}
					$voltxt = '';
					if (empty($object->lines[$i]->fk_product_type) && $object->lines[$i]->volume) {
						$voltxt = round($object->lines[$i]->volume * $object->lines[$i]->qty, 5).' '.measuringUnitString(0, "volume", $object->lines[$i]->volume_units ? $object->lines[$i]->volume_units : 0, 1);
					}

					// Weight
					if (!getDolGlobalString('STOCKTRANSFER_PDF_HIDE_WEIGHT_AND_VOLUME')) {
						$pdf->writeHTMLCell($this->posxqty - $this->posxweightvol + 2, 3, $this->posxweightvol - 1, $curY, $weighttxt.(($weighttxt && $voltxt) ? '<br>' : '').$voltxt, 0, 0, false, true, 'C');
						//$pdf->MultiCell(($this->posxqtyordered - $this->posxweightvol), 3, $weighttxt.(($weighttxt && $voltxt)?'<br>':'').$voltxt,'','C');
					}

					// Qty
					$pdf->SetXY($this->posxqty, $curY);
					$pdf->writeHTMLCell($this->posxwarehousesource - $this->posxqty + 2, 3, $this->posxqty - 1, $curY, $object->lines[$i]->qty, 0, 0, false, true, 'C');
					//$pdf->MultiCell(($this->posxwarehousesource - $this->posxqty), 3, $weighttxt.(($weighttxt && $voltxt)?'<br>':'').$voltxt,'','C');

					// Warehouse source
					$wh_source = new Entrepot($this->db);
					if (!empty($TCacheEntrepots[$object->lines[$i]->fk_warehouse_source])) {
						$wh_source = $TCacheEntrepots[$object->lines[$i]->fk_warehouse_source];
					} else {
						$wh_source->fetch($object->lines[$i]->fk_warehouse_source);
						$TCacheEntrepots[$object->lines[$i]->fk_warehouse_source] = $wh_source;
					}
					$pdf->SetXY($this->posxwarehousesource, $curY);
					$pdf->MultiCell(($this->posxwarehousedestination - $this->posxwarehousesource), 3, $wh_source->ref.(!empty($wh_source->lieu) ? ' - '.$wh_source->lieu : ''), '', 'C');

					// Warehouse destination
					$wh_destination = new Entrepot($this->db);
					if (!empty($TCacheEntrepots[$object->lines[$i]->fk_warehouse_destination])) {
						$wh_destination = $TCacheEntrepots[$object->lines[$i]->fk_warehouse_destination];
					} else {
						$wh_destination->fetch($object->lines[$i]->fk_warehouse_destination);
						$TCacheEntrepots[$object->lines[$i]->fk_warehouse_destination] = $wh_destination;
					}
					$pdf->SetXY($this->posxwarehousedestination, $curY);
					$pdf->MultiCell(($this->posxpuht - $this->posxwarehousedestination), 3, $wh_destination->ref.(!empty($wh_destination->lieu) ? ' - '.$wh_destination->lieu : ''), '', 'C');

					if (getDolGlobalString('STOCKTRANSFER_PDF_DISPLAY_AMOUNT_HT')) {
						$pdf->SetXY($this->posxpuht, $curY);
						$pdf->MultiCell(($this->posxtotalht - $this->posxpuht - 1), 3, price($object->lines[$i]->subprice, 0, $outputlangs), '', 'R');

						$pdf->SetXY($this->posxtotalht, $curY);
						$pdf->MultiCell(($this->page_largeur - $this->marge_droite - $this->posxtotalht), 3, price($object->lines[$i]->total_ht, 0, $outputlangs), '', 'R');
					}

					$nexY += 3;
					if ($weighttxt && $voltxt) {
						$nexY += 2;
					}

					// Add line
					if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY - 1, $this->page_largeur - $this->marge_droite, $nexY - 1);
						$pdf->SetLineStyle(array('dash' => 0));
					}

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
					}
					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pagenb++;
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
				}

				// Show square
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

				// Display total area
				$posy = $this->_tableau_tot($pdf, $object, 0, $bottomlasttab, $outputlangs);

				// Pagefoot
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();  // @phan-suppress-current-line PhanUndeclaredMethod
				}

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
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "STOCKTRANSFER_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show total to pay
	 *
	 *	@param	TCPDF			$pdf            Object PDF
	 *	@param  StockTransfer	$object         Object StockTransfer
	 *	@param  int				$deja_regle     Amount already paid
	 *	@param	int				$posy			Start Position
	 *	@param	Translate		$outputlangs	Object langs
	 *	@return int								Position for suite
	 */
	protected function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		// phpcs:enable
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('', 'B', $default_font_size - 1);

		// Total table
		$col1x = $this->posxqty - 50;
		$col2x = $this->posxqty;
		/*if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}*/
		if (!getDolGlobalString('STOCKTRANSFER_PDF_HIDE_ORDERED')) {
			$largcol2 = ($this->posxwarehousesource - $this->posxqty);
		} else {
			$largcol2 = ($this->posxwarehousedestination - $this->posxqty);
		}

		$useborder = 0;
		$index = 0;

		$totalWeight = '';
		$totalVolume = '';
		$totalWeighttoshow = '';
		$totalVolumetoshow = '';

		// Load dim data
		$tmparray = $object->getTotalWeightVolume();
		if (!empty($tmparray)) {
			$totalWeight = $tmparray['weight'];
			$totalVolume = $tmparray['volume'];
		}
		$totalQty = 0;
		if (!empty($object->lines)) {
			foreach ($object->lines as $line) {
				$totalQty += $line->qty;
			}
		}
		// Set trueVolume and volume_units not currently stored into database
		if ($object->trueWidth && $object->trueHeight && $object->trueDepth) {
			$object->trueVolume = price(($object->trueWidth * $object->trueHeight * $object->trueDepth), 0, $outputlangs, 0, 0);
			$object->volume_units = $object->size_units * 3;
		}

		if ($totalWeight != '') {
			$totalWeighttoshow = showDimensionInBestUnit($totalWeight, 0, "weight", $outputlangs);
		}
		if ($totalVolume != '') {
			$totalVolumetoshow = showDimensionInBestUnit($totalVolume, 0, "volume", $outputlangs);
		}
		if (!empty($object->trueWeight)) {
			$totalWeighttoshow = showDimensionInBestUnit($object->trueWeight, $object->weight_units, "weight", $outputlangs);
		}
		if (!empty($object->trueVolume)) {
			$totalVolumetoshow = showDimensionInBestUnit($object->trueVolume, $object->volume_units, "volume", $outputlangs);
		}

		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("Total"), 0, 'L', 1);

		if (!getDolGlobalString('STOCKTRANSFER_PDF_HIDE_ORDERED')) {
			$pdf->SetXY($this->posxqty, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($this->posxwarehousesource - $this->posxqty, $tab2_hl, $totalQty, 0, 'C', 1);
		}

		if (getDolGlobalString('STOCKTRANSFER_PDF_DISPLAY_AMOUNT_HT')) {
			$pdf->SetXY($this->posxpuht, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($this->posxtotalht - $this->posxpuht, $tab2_hl, '', 0, 'C', 1);

			$pdf->SetXY($this->posxtotalht, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxtotalht, $tab2_hl, price($object->total_ht, 0, $outputlangs), 0, 'C', 1);
		}

		if (!getDolGlobalString('STOCKTRANSFER_PDF_HIDE_WEIGHT_AND_VOLUME')) {
			// Total Weight
			if ($totalWeighttoshow) {
				$pdf->SetXY($this->posxweightvol, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell(($this->posxqty - $this->posxweightvol), $tab2_hl, $totalWeighttoshow, 0, 'C', 1);

				$index++;
			}
			if ($totalVolumetoshow) {
				$pdf->SetXY($this->posxweightvol, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell(($this->posxqty - $this->posxweightvol), $tab2_hl, $totalVolumetoshow, 0, 'C', 1);

				$index++;
			}
			if (!$totalWeighttoshow && !$totalVolumetoshow) {
				$index++;
			}
		}

		$pdf->SetTextColor(0, 0, 0);

		return ($tab2_top + ($tab2_hl * $index));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		float|int	$tab_top		Top position of table
	 *   @param		float|int	$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
		// phpcs:enable
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) {
			$hidetop = -1;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Description
		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5);

			$pdf->SetXY($this->posxdesc - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxlot - $this->posxdesc, 2, $outputlangs->transnoentities("Description"), '', 'L');
		}

		if (isModEnabled('productbatch') && $this->atLeastOneBatch) {
			$pdf->line($this->posxlot - 1, $tab_top, $this->posxlot - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxlot, $tab_top + 1);
				$pdf->MultiCell(($this->posxweightvol - $this->posxlot), 2, $outputlangs->transnoentities("Batch"), '', 'C');
			}
		}

		if (!getDolGlobalString('STOCKTRANSFER_PDF_HIDE_WEIGHT_AND_VOLUME')) {
			$pdf->line($this->posxweightvol - 1, $tab_top, $this->posxweightvol - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxweightvol - 1, $tab_top + 1);
				$pdf->MultiCell(($this->posxqty - $this->posxweightvol), 2, $outputlangs->transnoentities("WeightVolShort"), '', 'C');
			}
		}

		$pdf->line($this->posxqty - 1, $tab_top, $this->posxqty - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxqty - 1, $tab_top + 1);
			$pdf->MultiCell(($this->posxwarehousesource - $this->posxqty), 2, $outputlangs->transnoentities("Qty"), '', 'C');
		}

		$pdf->line($this->posxwarehousesource - 1, $tab_top, $this->posxwarehousesource - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxwarehousesource - 1, $tab_top + 1);
			$pdf->MultiCell(($this->posxwarehousedestination - $this->posxwarehousesource), 2, $outputlangs->transnoentities("WarehouseSource"), '', 'C');
		}


		$pdf->line($this->posxwarehousedestination - 1, $tab_top, $this->posxwarehousedestination - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxwarehousedestination - 2.5, $tab_top + 1);
			$pdf->MultiCell(($this->posxpuht - $this->posxwarehousedestination + 4), 2, $outputlangs->transnoentities("WarehouseTarget"), '', 'C');
		}

		/*if (!empty($conf->global->STOCKTRANSFER_PDF_DISPLAY_AMOUNT_HT)) {
			$pdf->line($this->posxpuht - 1, $tab_top, $this->posxpuht - 1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxpuht - 1, $tab_top + 1);
				$pdf->MultiCell(($this->posxtotalht - $this->posxpuht), 2, $outputlangs->transnoentities("PriceUHT"), '', 'C');
			}

			$pdf->line($this->posxtotalht - 1, $tab_top, $this->posxtotalht - 1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxtotalht - 1, $tab_top + 1);
				$pdf->MultiCell(($this->page_largeur - $this->marge_droite - $this->posxtotalht), 2, $outputlangs->transnoentities("TotalHT"), '', 'C');
			}
		}*/
	}

	/**
	 * Used to know if at least one line of Stock Transfer object has a batch set
	 *
	 * @param	StockTransfer	$object	Stock Transfer object
	 * @return	boolean			true if at least one line has batch set, false if not
	 */
	public function atLeastOneBatch($object)
	{
		//$atLeastOneBatch = false;

		if (!isModEnabled('productbatch')) {
			return false;
		}

		if (!empty($object->lines)) {
			foreach ($object->lines as $line) {
				if (!empty($line->batch)) {
					return true;
				}
			}
		}

		return false;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF			$pdf     		Object PDF
	 *  @param  StockTransfer	$object     	Object to show
	 *  @param  int	    		$showaddress    0=no, 1=yes
	 *  @param  Translate		$outputlangs	Object lang for output
	 *  @return	float|int                   	Return topshift value
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		// phpcs:enable
		global $conf, $langs;

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "orders", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == 0 && (getDolGlobalString('STOCKTRANSFER_DRAFT_WATERMARK'))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->SHIPPING_DRAFT_WATERMARK);
		}

		//Prepare next
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo) {
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
			$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetDrawColor(128, 128, 128);

		$posx = $this->page_largeur - $w - $this->marge_droite;
		$posy = $this->marge_haute;

		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("StockTransferSheet").' '.$object->ref;
		$pdf->MultiCell($w, 4, $title, '', 'R');

		$pdf->SetFont('', '', $default_font_size + 1);

		// Date prévue depart
		if (!empty($object->date_prevue_depart)) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 4, $outputlangs->transnoentities("DatePrevueDepart")." : ".dol_print_date($object->date_prevue_depart, "day", false, $outputlangs, true), '', 'R');
		}

		// Date prévue arrivée
		if (!empty($object->date_prevue_arrivee)) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 4, $outputlangs->transnoentities("DatePrevueArrivee")." : ".dol_print_date($object->date_prevue_arrivee, "day", false, $outputlangs, true), '', 'R');
		}

		// Date reelle depart
		if (!empty($object->date_reelle_depart)) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 4, $outputlangs->transnoentities("DateReelleDepart")." : ".dol_print_date($object->date_reelle_depart, "day", false, $outputlangs, true), '', 'R');
		}

		// Date reelle arrivée
		if (!empty($object->date_reelle_arrivee)) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 4, $outputlangs->transnoentities("DateReelleArrivee")." : ".dol_print_date($object->date_reelle_arrivee, "day", false, $outputlangs, true), '', 'R');
		}

		if (!empty($object->thirdparty->code_client)) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}


		$pdf->SetFont('', '', $default_font_size + 3);
		$Yoff = 25;

		// Add list of linked orders
		$origin = $object->origin;
		$origin_id = $object->origin_id;

		// TODO move to external function
		if (isModEnabled($origin)) {     // commonly $origin='commande'
			$outputlangs->load('orders');

			$classname = ucfirst($origin);
			$linkedobject = new $classname($this->db);
			'@phan-var-force CommonObject $linkedobject';
			$result = $linkedobject->fetch($origin_id);
			if ($result >= 0) {
				//$linkedobject->fetchObjectLinked()   Get all linked object to the $linkedobject (commonly order) into $linkedobject->linkedObjects

				$pdf->SetFont('', '', $default_font_size - 2);
				$text = $linkedobject->ref;
				if (isset($linkedobject->ref_client) && !empty($linkedobject->ref_client)) {
					$text .= ' ('.$linkedobject->ref_client.')';
				}
				$Yoff += 8;
				$pdf->SetXY($this->page_largeur - $this->marge_droite - $w, $Yoff);
				$pdf->MultiCell($w, 2, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), 0, 'R');
				$Yoff += 3;
				$pdf->SetXY($this->page_largeur - $this->marge_droite - $w, $Yoff);
				$pdf->MultiCell($w, 2, $outputlangs->transnoentities("OrderDate")." : ".dol_print_date($linkedobject->date, "day", false, $outputlangs, true), 0, 'R');
			}
		}

		$top_shift = 0;

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = '';
			// Add internal contact of origin element if defined
			$arrayidcontact = array();
			$arrayidcontact = $object->getIdContact('external', 'STFROM');

			$usecontact = false;
			if (count($arrayidcontact) > 0) {
				/*$object->fetch_user(reset($arrayidcontact));
				$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";*/
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}

			if ($usecontact) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $this->emetteur;
			}

			if (!empty($thirdparty)) {
				$carac_emetteur_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
			}

			if ($usecontact) {
				$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, $object->contact, 1, 'targetwithdetails', $object);
			} else {
				$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);
			}

			// Show sender
			$posy = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
			$posx = $this->marge_gauche;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->page_largeur - $this->marge_droite - 80;
			}

			$hautcadre = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 38 : 40;
			$widthrecbox = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 82;

			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("Sender").":", 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(255, 255, 255);

			// Show sender name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($carac_emetteur_name), 0, 'L');
			$posy = $pdf->getY();

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox - 2, 4, $carac_emetteur, 0, 'L');


			// If SHIPPING contact defined, we use it
			$usecontact = false;
			$arrayidcontact = $object->getIdContact('external', 'STDEST');
			if (count($arrayidcontact) > 0) {
				$usecontact = true;
				$result = $object->fetch_contact($arrayidcontact[0]);
			}

			//Recipient name
			// On peut utiliser le nom de la societe du contact
			if ($usecontact/* && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)*/) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$carac_client_name = '';
			if (!empty($thirdparty)) {
				$carac_client_name = pdfBuildThirdpartyName($thirdparty, $outputlangs);
			}

			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, (!empty($object->contact) ? $object->contact : null), ($usecontact ? 1 : 0), 'targetwithdetails', $object);

			// Show recipient
			$widthrecbox = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84;	// To work with US executive format
			}
			$posy = getDolGlobalString('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->marge_gauche;
			}

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx + 2, $posy - 5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("Recipient").":", 0, 'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$pdf->SetTextColor(0, 0, 0);

		return $top_shift;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param	TCPDF			$pdf     			PDF
	 *  @param	StockTransfer	$object				Object to show
	 *  @param	Translate		$outputlangs		Object lang for output
	 *  @param	int				$hidefreetext		1=Hide free text
	 *  @return	int									Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		// phpcs:enable
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		return pdf_pagefoot($pdf, $outputlangs, 'STOCKTRANSFER_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
