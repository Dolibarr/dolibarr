<?php
/* Copyright (C) 2004-2014 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2014 Regis Houssin         <regis.houssin@inodbox.com>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2008      Chiptronik
 * Copyright (C) 2011-2021 Philippe Grand        <philippe.grand@atoo-net.com>
 * Copyright (C) 2015      Marcos García         <marcosgdf@gmail.com>
 * Copyright (C) 2020      John BOTELLA
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>

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
 *	\file       htdocs/core/modules/delivery/doc/pdf_storm.modules.php
 *	\ingroup    livraison
 *	\brief      File of class to manage receiving receipts with template Storm
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/delivery/modules_delivery.php';
require_once DOL_DOCUMENT_ROOT.'/delivery/class/delivery.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to build Delivery Order documents with storm model
 */
class pdf_storm extends ModelePDFDeliveryOrder
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
	public $version = 'dolibarr';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills", "sendings", "companies"));

		$this->db = $db;
		$this->name = "Storm";
		$this->description = $langs->trans("DocumentModelStorm");
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

		$this->option_logo = 1; // Display logo FAC_PDF_LOGO
		$this->option_tva = 1; // Manage the vat option FACTURE_TVAOPTION

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param      Delivery	$object				Object to generate
	 *  @param      Translate	$outputlangs		Lang output object
	 *  @param      string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param      int			$hidedetails		Do not show line details
	 *  @param      int			$hidedesc			Do not show desc
	 *  @param      int			$hideref			Do not show ref
	 *  @return     int             			1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "products", "sendings", "deliveries"));

		if ($conf->expedition->dir_output) {
			$object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->expedition->dir_output."/receipt";
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expedition->dir_output."/receipt/".$objectref;
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

				$nblines = count($object->lines);


				// Loop on each lines to detect if there is at least one image to show
				$realpatharray = array();
				$this->atleastonephoto = false;
				if (getDolGlobalString('MAIN_GENERATE_DELIVERY_WITH_PICTURE')) {
					$objphoto = new Product($this->db);

					for ($i = 0; $i < $nblines; $i++) {
						if (empty($object->lines[$i]->fk_product)) {
							continue;
						}

						$objphoto->fetch($object->lines[$i]->fk_product);
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
								$dir = $conf->product->dir_output.'/'.$midir;

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


				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$heightforinfotot = 30; // Height reserved to output the info and total part
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
				if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/' . getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
					$tplidx = $pdf->importPage(1);
				}

				// We get the shipment that is the origin of delivery receipt
				$expedition = new Expedition($this->db);
				$result = $expedition->fetch($object->origin_id);
				// Now we get the order that is origin of shipment
				$commande = new Commande($this->db);
				if ($expedition->origin == 'commande') {
					$commande->fetch($expedition->origin_id);
				}
				$object->commande = $commande; // We set order of shipment onto delivery.
				$object->commande->loadExpeditions();


				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("DeliveryOrder"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("DeliveryOrder"));
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
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 90;
				$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 : 10);

				$tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

				$this->posxdesc = $this->marge_gauche + 1;

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

				// display note
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;

				// Extrafields in note
				$extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
				if (!empty($extranote)) {
					$notetoshow = dol_concatdesc($notetoshow, $extranote);
				}

				if (!empty($notetoshow)) {
					$tab_top = 88 + $height_incoterms;

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY + 6;
				} else {
					$height_note = 0;
				}

				// Use new auto column system
				$this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

				// Table simulation to know the height of the title line
				$pdf->startTransaction();
				$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, 0);
				$pdf->rollbackTransaction(true);

				$iniY = $tab_top + $this->tabTitleHeight + 2;
				$curY = $tab_top + $this->tabTitleHeight + 2;
				$nexY = $tab_top + $this->tabTitleHeight + 2;

				// Loop on each lines
				for ($i = 0; $i < $nblines; $i++) {
					// Fetch optionals
					if (empty($object->lines[$i]->array_options)) {
						$object->lines[$i]->fetch_optionals();
					}

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

					// Description of product line
					$curX = $this->posxdesc - 1;

					$showpricebeforepagebreak = 1;

					$posYAfterImage = 0;
					$posYAfterDescription = 0;
					if ($this->getColumnStatus('photo')) {
						// We start with Photo of product line
						if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// If photo too high, we moved completely on new page
							$pdf->AddPage('', '', true);
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							//if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) $this->_pagehead($pdf, $object, 0, $outputlangs);
							$pdf->setPage($pageposbefore + 1);

							$curY = $tab_top_newpage;

							// Allows data in the first page if description is long enough to break in multiples pages
							if (getDolGlobalString('MAIN_PDF_DATA_ON_FIRST_PAGE')) {
								$showpricebeforepagebreak = 1;
							} else {
								$showpricebeforepagebreak = 0;
							}
						}


						if (!empty($this->cols['photo']) && isset($imglinesize['width']) && isset($imglinesize['height'])) {
							$pdf->Image($realpatharray[$i], $this->getColumnContentXStart('photo'), $curY + 1, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300); // Use 300 dpi
							// $pdf->Image does not increase value return by getY, so we save it manually
							$posYAfterImage = $curY + $imglinesize['height'];
						}
					}


					// Description of product line
					if ($this->getColumnStatus('desc')) {
						$pdf->startTransaction();
						pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->getColumnContentWidth('desc'), 3, $this->getColumnContentXStart('desc'), $curY, $hideref, $hidedesc);
						$pageposafter = $pdf->getPage();
						if ($pageposafter > $pageposbefore) {    // There is a pagebreak
							$pdf->rollbackTransaction(true);
							$pageposafter = $pageposbefore;
							//print $pageposafter.'-'.$pageposbefore;exit;
							$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
							pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->getColumnContentWidth('desc'), 4, $this->getColumnContentXStart('desc'), $curY, $hideref, $hidedesc);
							$posyafter = $pdf->GetY();
							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {    // There is no space left for total+free text
								if ($i == ($nblines - 1)) {    // No more lines, and no space left to show total, so we create a new page
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
					}

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // On repositionne la police par default


					// Quantity
					if ($this->getColumnStatus('qty_shipped')) {
						$this->printStdColumnContent($pdf, $curY, 'qty_shipped', $object->lines[$i]->qty_shipped);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Remaining to ship
					if ($this->getColumnStatus('qty_remaining')) {
						$qtyRemaining = $object->lines[$i]->qty_asked - $object->commande->expeditions[$object->lines[$i]->fk_origin_line];
						$this->printStdColumnContent($pdf, $curY, 'qty_remaining', $qtyRemaining);
						$nexY = max($pdf->GetY(), $nexY);
					}

					$nexY = max($nexY, $posYAfterImage);

					// Extrafields
					if (!empty($object->lines[$i]->array_options)) {
						foreach ($object->lines[$i]->array_options as $extrafieldColKey => $extrafieldValue) {
							if ($this->getColumnStatus($extrafieldColKey)) {
								$extrafieldValue = $this->getExtrafieldContent($object->lines[$i], $extrafieldColKey, $outputlangs);
								$this->printStdColumnContent($pdf, $curY, $extrafieldColKey, $extrafieldValue);
								$nexY = max($pdf->GetY(), $nexY);
							}
						}
					}

					// Add line
					if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY + 1, $this->page_largeur - $this->marge_droite, $nexY + 1);
						$pdf->SetLineStyle(array('dash' => 0));
					}

					$nexY += 2; // Add space between lines

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

				// Affiche zone infos
				$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);

				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
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
		}

		$this->error = $langs->transnoentities("ErrorConstantNotDefined", "LIVRAISON_OUTPUTDIR");
		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		Delivery	$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	protected function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		global $conf, $mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', '', $default_font_size);
		$pdf->SetXY($this->marge_gauche, $posy);

		$larg_sign = ($this->page_largeur - $this->marge_gauche - $this->marge_droite) / 3;
		$pdf->Rect($this->marge_gauche, $posy + 1, $larg_sign, 25);
		$pdf->SetXY($this->marge_gauche + 2, $posy + 2);
		$pdf->MultiCell($larg_sign, 2, $outputlangs->trans("For").' '.$outputlangs->convToOutputCharset($mysoc->name).":", '', 'L');

		$pdf->Rect(2 * $larg_sign + $this->marge_gauche, $posy + 1, $larg_sign, 25);
		$pdf->SetXY(2 * $larg_sign + $this->marge_gauche + 2, $posy + 2);
		$pdf->MultiCell($larg_sign, 2, $outputlangs->trans("ForCustomer").':', '', 'L');
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		float|int	$tab_top		Top position of table
	 *   @param		float|int	$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0)
	{
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

		if (empty($hidetop)) {
			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 5, 'F', null, explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
			}
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter


		$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);

		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top + $this->tabTitleHeight, $this->page_largeur - $this->marge_droite, $tab_top + $this->tabTitleHeight); // line takes a position y in 2nd parameter and 4th parameter
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Delivery	$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	float|int                   Return topshift value
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $langs;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		if ($object->statut == 0 && getDolGlobalString('COMMANDE_DRAFT_WATERMARK')) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', getDolGlobalString('COMMANDE_DRAFT_WATERMARK'));
		}

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - 100;

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
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		} else {
			$pdf->MultiCell(100, 4, $this->emetteur->name, 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("DeliveryOrder")." ".$outputlangs->convToOutputCharset($object->ref), '', 'R');

		$pdf->SetFont('', '', $default_font_size + 2);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		if ($object->date_valid) {
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : ".dol_print_date($object->date_delivery, "%d %b %Y", false, $outputlangs, true), '', 'R');
		} else {
			$pdf->SetTextColor(255, 0, 0);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DeliveryNotValidated"), '', 'R');
			$pdf->SetTextColor(0, 0, 60);
		}

		if ($object->thirdparty->code_client) {
			$posy += 5;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}

		$pdf->SetTextColor(0, 0, 60);

		$posy += 2;

		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = 42;
			$posx = $this->marge_gauche;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->page_largeur - $this->marge_droite - 80;
			}
			$hautcadre = 40;

			// Show sender frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell(80, 5, $outputlangs->transnoentities("BillFrom"), 0, 'L');
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(230, 230, 230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);

			// Show sender name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy = $pdf->getY();

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');

			// Client destinataire
			$posy = 42;
			$posx = 102;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->marge_gauche;
			}
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell(80, 5, $outputlangs->transnoentities("DeliveryAddress"), 0, 'L');

			// If SHIPPING contact defined on order, we use it
			$usecontact = false;
			$arrayidcontact = $object->commande->getIdContact('external', 'SHIPPING');
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

			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), $usecontact, 'target', $object);

			// Show recipient
			$widthrecbox = 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84; // To work with US executive format
			}
			$posy = 42;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->marge_gauche;
			}

			// Show recipient frame
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - 2);
			$pdf->SetXY($posx + 2, $posy - 5);
			//$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":",0,'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$pdf->SetTextColor(0, 0, 60);

		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param	TCPDF		$pdf     			PDF
	 *  @param	Delivery	$object				Object to show
	 *  @param	Translate	$outputlangs		Object lang for output
	 *  @param	int			$hidefreetext		1=Hide free text
	 *  @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		return pdf_pagefoot($pdf, $outputlangs, 'DELIVERY_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}



	/**
	 *   	Define Array Column Field
	 *
	 *   	@param	Delivery	   $object    	    common object
	 *   	@param	Translate	   $outputlangs     langs
	 *      @param	int			   $hidedetails		Do not show line details
	 *      @param	int			   $hidedesc		Do not show desc
	 *      @param	int			   $hideref			Do not show ref
	 *      @return	void
	 */
	public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $hookmanager;

		// Default field style for content
		$this->defaultContentsFieldsStyle = array(
			'align' => 'R', // R,C,L
			'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
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
			),
		);

		$rank = $rank + 10;
		$this->cols['photo'] = array(
			'rank' => $rank,
			'width' => getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH', 20), // in mm
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

		if (getDolGlobalString('MAIN_GENERATE_DELIVERY_WITH_PICTURE') && !empty($this->atleastonephoto)) {
			$this->cols['photo']['status'] = true;
		}


		$rank = $rank + 10;
		$this->cols['Comments'] = array(
			'rank' => $rank,
			'width' => 50, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Comments'
			),
			'border-left' => true, // add left line separator
		);

		//      $rank = $rank + 10;
		//      $this->cols['weight'] = array(
		//          'rank' => $rank,
		//          'width' => 30, // in mm
		//          'status' => false,
		//          'title' => array(
		//              'textkey' => 'WeightVolShort'
		//          ),
		//          'border-left' => true, // add left line separator
		//      );

		$rank = $rank + 10;
		$this->cols['qty_shipped'] = array(
			'rank' => $rank,
			'width' => 20, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'QtyShippedShort'
			),
			'border-left' => true, // add left line separator
		);

		$rank = $rank + 10;
		$this->cols['qty_remaining'] = array(
			'rank' => $rank,
			'width' => 20, // in mm
			'status' => 1,
			'title' => array(
				'textkey' => 'KeepToShipShort'
			),
			'border-left' => true, // add left line separator
		);


		// Add extrafields cols
		if (!empty($object->lines)) {
			$line = reset($object->lines);
			$this->defineColumnExtrafield($line, $outputlangs, $hidedetails);
		}

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
}
