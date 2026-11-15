<?php
/* Copyright (C) 2004-2014	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand			<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014	Juanjo Menent				<jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel			<christophe.battarel@altairis.fr>
 * Copyright (C) 2012		Cédric Salvador				<csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014	Raphaël Doursenaud			<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015		Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2017		Ferran Marcet				<fmarcet@2byte.es>
 * Copyright (C) 2018-2025	Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
 * Copyright (C) 2026		Laurent Magnin				<laurent.magnin@evarisk.com>
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
 *	\file       htdocs/core/modules/bom/doc/pdf_aurore.modules.php
 *	\ingroup    bom
 *	\brief      File of class to generate BOM document from Aurore template
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/bom/modules_bom.php';
require_once DOL_DOCUMENT_ROOT.'/bom/class/bom.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to manage PDF template aurore
 */
class pdf_aurore extends ModelePDFBom
{
	/**
	 * @var DoliDB Database handler
	 */
	public $db;

	/**
	 * @var int The environment ID when using a multicompany module
	 */
	public $entity;

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
	 * @var array{0:int,1:int} Minimum version of PHP required by module.
	 * e.g.: PHP >= 7.0 = array(7, 0)
	 */
	public $phpmin = array(7, 0);

	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * @var int		Position
	 */
	public $position = 50;

	/**
	 * Issuer
	 * @var Societe Object that emits
	 */
	public $emetteur;

	/**
	 * @var array<string,array{rank:int,width:float|int|false,status:bool|int<0,1>,border-left?:bool,title:array{textkey:string,label?:string,align?:string,padding?:array{0:float|int,1:float|int,2:float|int,3:float|int}},content?:array{align?:string,padding?:array{0:float|int,1:float|int,2:float|int,3:float|int}}}>	Array of document table columns
	 */
	public $cols;


	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills", "products", "mrp"));

		$this->db = $db;
		$this->name = "aurore";
		$this->description = $langs->trans('DocumentModelStandardPDF');
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
		$this->corner_radius = getDolGlobalInt('MAIN_PDF_FRAME_CORNER_RADIUS', 0);

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1; // used for notes and other stuff

		$this->tabTitleHeight = 5; // default height

		if ($mysoc === null) {
			dol_syslog(get_class($this).'::__construct() Global $mysoc should not be null.'.getCallerInfoString(), LOG_ERR);
			return;
		}

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = dol_substr($langs->defaultlang, -2); // By default, if was not defined
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build and write pdf to disk
	 *
	 *  @param	BOM			$object				Source object to generate document from
	 *  @param	Translate	$outputlangs		Lang output object
	 *  @param	string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param	int<0,1>	$hidedetails		Do not show line details
	 *  @param	int<0,1>	$hidedesc			Do not show desc
	 *  @param	int<0,1>	$hideref			Do not show ref
	 *  @return	int<-1,1>						1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager, $nblines;

		dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalInt('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by the page
		$langfiles = array("main", "bills", "products", "dict", "companies", "mrp", "stocks");
		$outputlangs->loadLangs($langfiles);

		// Show Draft Watermark
		$draftwatermark = getDolGlobalString('BOM_DRAFT_WATERMARK');
		if ($draftwatermark && $object->status == $object::STATUS_DRAFT) {
			$this->watermark = $draftwatermark;
		}

		global $outputlangsbis;
		$outputlangsbis = null;
		$langcodebis = getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE');
		if ($langcodebis && $outputlangs->defaultlang != $langcodebis) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang($langcodebis);
			$outputlangsbis->loadLangs($langfiles);
		}

		$hidetop = getDolGlobalInt('MAIN_PDF_DISABLE_COL_HEAD_TITLE');

		$dir_output = getMultidirOutput($object);
		if (!empty($dir_output)) {
			$object->fetch_thirdparty();
			if ($object->fk_product > 0) {
				$object->fetch_product();
			}
			if (!$object->specimen) {
				$object->calculateCosts();
			} elseif (empty($object->lines)) {
				// Build specimen lines if none provided
				for ($i = 1; $i <= 3; $i++) {
					$line = new BOMLine($this->db);
					$line->desc = $outputlangs->transnoentities("Description")." ".$i;
					$line->product_ref = 'PROD-0'.$i;
					$line->product_label = $outputlangs->transnoentities("Product")." ".$i;
					$line->product_type = ($i == 3 ? Product::TYPE_SERVICE : Product::TYPE_PRODUCT);
					$line->qty = $i * 2;
					$line->efficiency = 1;
					$line->qty_frozen = 0;
					$line->disable_stock_change = 0;
					$line->total_cost = 10 * $i;
					$object->lines[] = $line;
				}
				$object->total_cost = 60;
				$object->unit_cost = (!empty($object->qty) ? $object->total_cost / $object->qty : $object->total_cost);
			}

			// Enrich BOM lines with product details for PDF line description helper
			if (!empty($object->lines) && is_array($object->lines)) {
				$cacheProducts = array();
				$cacheBoms = array();
				$cacheWorkstations = array();
				foreach ($object->lines as $line) {
					/** @var BOMLine $line */
					if ($line->fk_product > 0) {
						$fk_product = (int) $line->fk_product;
						if (!isset($cacheProducts[$fk_product])) {
							$line->fetch_product();
							$cacheProducts[$fk_product] = is_object($line->product) ? $line->product : null;
						} else {
							$line->product = $cacheProducts[$fk_product];
						}
						if (is_object($line->product)) {
							$line->product_ref = $line->product->ref;
							$line->product_desc = $line->product->description;
							$line->product_label = $line->product->label;
							$line->product_type = $line->product->type;
							$line->product_barcode = $line->product->barcode;
						}
					}
					if (empty($line->desc)) {
						$line->desc = !empty($line->description) ? (string) $line->description : (!empty($line->product_desc) ? (string) $line->product_desc : '');
					}
					if (!empty($line->fk_bom_child) && $line->fk_bom_child > 0) {
						$fk_bom_child = (int) $line->fk_bom_child;
						if (!isset($cacheBoms[$fk_bom_child])) {
							$bomchild = new BOM($this->db);
							$cacheBoms[$fk_bom_child] = ($bomchild->fetch($fk_bom_child) > 0) ? $bomchild : null;
						}
						if (!empty($cacheBoms[$fk_bom_child])) {
							$line->desc = dol_concatdesc((string) $line->desc, $outputlangs->transnoentities("BOM").' : '.$cacheBoms[$fk_bom_child]->ref);
						}
					}
					if (!empty($line->fk_default_workstation) && $line->fk_default_workstation > 0 && isModEnabled('workstation')) {
						require_once DOL_DOCUMENT_ROOT.'/workstation/class/workstation.class.php';
						$fk_workstation = (int) $line->fk_default_workstation;
						if (!isset($cacheWorkstations[$fk_workstation])) {
							$workstation = new Workstation($this->db);
							$cacheWorkstations[$fk_workstation] = ($workstation->fetch($fk_workstation) > 0) ? $workstation : null;
						}
						if (!empty($cacheWorkstations[$fk_workstation])) {
							$ws = $cacheWorkstations[$fk_workstation];
							$line->desc = dol_concatdesc((string) $line->desc, $outputlangs->transnoentities("DefaultWorkstation").' : '.$ws->ref.(!empty($ws->label) ? ' - '.$ws->label : ''));
						}
					}
				}
			}

			$nblines = (is_array($object->lines) ? count($object->lines) : 0);

			$dir = null;
			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $dir_output;
				$file = $dir."/SPECIMEN.pdf"; // When building a SPECIMEN Pdf file, the name must always be "SPECIMEN.pdf"
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $dir_output."/".$objectref;
				$file = $dir."/".$objectref.".pdf";
			}

			if (!dol_is_dir($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return 0;
				}
			}

			if (dol_is_dir($dir)) {
				// Add pdfgeneration hook
				if (!is_object($hookmanager)) {
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager = new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action); // Note that $action and $object may have been modified by some hooks

				// Set nblines with the new lines content after hook
				$nblines = (is_array($object->lines) ? count($object->lines) : 0);

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				'@phan-var-force TCPDI|TCPDF $pdf';
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->setAutoPageBreak(true, 0);

				$heightforinfotot = 30; // Height reserved to output the info and total part
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + (getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));

				// Set path to the background PDF File
				if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
					$logodir = $conf->mycompany->dir_output;
					if (!empty($conf->mycompany->multidir_output[$object->entity ?? $conf->entity])) {
						$logodir = $conf->mycompany->multidir_output[$object->entity ?? $conf->entity];
					}
					$pagecount = $pdf->setSourceFile($logodir.'/'.getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("BillOfMaterials"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getAnonymisableFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("BillOfMaterials").(!empty($object->label) ? " ".$outputlangs->convToOutputCharset((string) $object->label) : ""));
				if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
					$pdf->SetCompression(false);
				}

				// Set certificate
				$cert = getDolUserString('CERTIFICATE_CRT', getDolGlobalString('CERTIFICATE_CRT'));
				$certprivate = getDolUserString('CERTIFICATE_CRT_PRIVATE', getDolGlobalString('CERTIFICATE_CRT_PRIVATE'));
				// If a certificate is found
				if ($cert) {
					$info = array(
						'Name' => $this->emetteur->name,
						'Location' => getCountry($this->emetteur->country_code, ''),
						'Reason' => 'BOM',
						'ContactInfo' => $this->emetteur->email
					);
					$pdf->setSignature($cert, $certprivate, $this->emetteur->name, '', 2, $info);
				}

				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right


				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;

				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$donotrepeathead = getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD');
				$tab_top = 90 + $top_shift;
				$tab_top_newpage = (!$donotrepeathead ? 42 + $top_shift : 10);

				$tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

				$tab_height_newpage = 150;
				if (!$donotrepeathead) {
					$tab_height_newpage -= $top_shift;
				}

				$nexY = $tab_top - 1;

				// Display description and public note
				$notetoshow = empty($object->description) ? '' : (string) $object->description;
				if (!empty($object->note_public)) {
					$notetoshow = dol_concatdesc($notetoshow, (string) $object->note_public);
				}
				// Extrafields in note
				$extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
				if (!empty($extranote)) {
					$notetoshow = dol_concatdesc($notetoshow, $extranote);
				}

				$pagenb = $pdf->getPage();
				if ($notetoshow) {
					$tab_top -= 2;

					$tab_width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
					$pageposbeforenote = $pagenb;

					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$pdf->startTransaction();

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					// Description
					$pageposafternote = $pdf->getPage();
					$posyafter = $pdf->GetY();

					if ($pageposafternote > $pageposbeforenote) {
						$pdf->rollbackTransaction(true);

						// prepare pages to receive notes
						while ($pagenb < $pageposafternote) {
							$pdf->AddPage();
							$pagenb++;
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							if (!$donotrepeathead) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							}
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext);
						}

						// back to start
						$pdf->setPage($pageposbeforenote);
						$pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext);
						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
						$pageposafternote = $pdf->getPage();

						$posyafter = $pdf->GetY();

						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {	// There is no space left for total+free text
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							$pdf->setTopMargin($tab_top_newpage);
							// The only function to edit the bottom margin of current page to set it.
							$pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext);
						}


						// apply note frame to previous pages
						$i = $pageposbeforenote;
						while ($i < $pageposafternote) {
							$pdf->setPage($i);


							$pdf->SetDrawColor(128, 128, 128);
							// Draw note frame
							if ($i > $pageposbeforenote) {
								$height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
								$pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
							} else {
								$height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
								$pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);
							}

							// Add footer
							$pdf->setPageOrientation('', true, 0); // The only function to edit the bottom margin of current page to set it.
							$this->_pagefoot($pdf, $object, $outputlangs, 1);

							$i++;
						}

						// apply note frame to last page
						$pdf->setPage($pageposafternote);
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						if (!$donotrepeathead) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						$height_note = $posyafter - $tab_top_newpage;
						$pdf->Rect($this->marge_gauche, $tab_top_newpage - 1, $tab_width, $height_note + 1);
					} else {
						// No pagebreak
						$pdf->commitTransaction();
						$posyafter = $pdf->GetY();
						$height_note = $posyafter - $tab_top;
						$pdf->Rect($this->marge_gauche, $tab_top - 1, $tab_width, $height_note + 1);


						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + 20))) {
							// not enough space, need to add page
							$pdf->AddPage('', '', true);
							$pagenb++;
							$pageposafternote++;
							$pdf->setPage($pageposafternote);
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							if (!$donotrepeathead) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							}

							$posyafter = $tab_top_newpage;
						}
					}

					$tab_height -= $height_note;
					$tab_top = $posyafter + 6;
				} else {
					$height_note = 0;
				}

				// Use new auto column system
				$this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

				// Table simulation to know the height of the title line
				$pdf->startTransaction();
				$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
				$pdf->rollbackTransaction(true);

				$nexY = $tab_top + $this->tabTitleHeight;

				// Loop on each lines
				$pageposbeforeprintlines = $pdf->getPage();
				$pagenb = $pageposbeforeprintlines;
				$dataonfirstpage = getDolGlobalInt('MAIN_PDF_DATA_ON_FIRST_PAGE');
				$dashbetweenlines = getDolGlobalInt('MAIN_PDF_DASH_BETWEEN_LINES');
				for ($i = 0; $i < $nblines; $i++) {
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					$posYAfterImage = 0;
					$posYAfterDescription = 0;

					// Description of product line
					if ($this->getColumnStatus('desc')) {
						$pdf->startTransaction();

						$this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);
						$pageposafter = $pdf->getPage();

						if ($pageposafter > $pageposbefore) {	// There is a pagebreak
							$pdf->rollbackTransaction(true);
							$pageposafter = $pageposbefore;
							$pdf->setPageOrientation('', true, $heightforfooter); // The only function to edit the bottom margin of current page to set it.

							$this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);

							$pageposafter = $pdf->getPage();
							$posyafter = $pdf->GetY();

							if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {	// There is no space left for total+free text
								if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
									$pdf->AddPage('', '', true);
									if (!empty($tplidx)) {
										$pdf->useTemplate($tplidx);
									}
									$pdf->setPage($pageposafter + 1);
								}
							} else {
								// We found a page break
								// Allows data in the first page if description is long enough to break in multiples pages
								if ($dataonfirstpage) {
									$showpricebeforepagebreak = 1;
								} else {
									$showpricebeforepagebreak = 0;
								}
							}
						} else {	// No pagebreak
							$pdf->commitTransaction();
						}
						$posYAfterDescription = $pdf->GetY();
					}

					$nexY = max($pdf->GetY(), $posYAfterImage);

					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', true, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

					// Quantity
					if ($this->getColumnStatus('qty')) {
						$qty = (empty($hidedetails) || $hidedetails > 1) ? (string) price2num($object->lines[$i]->qty, 'MS') : '';
						$this->printStdColumnContent($pdf, $curY, 'qty', $qty);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Unit
					if ($this->getColumnStatus('unit')) {
						$unit = '';
						if (empty($hidedetails) || $hidedetails > 1) {
							$tmpunit = $object->lines[$i]->getLabelOfUnit('short', $outputlangs, 1);
							if (is_string($tmpunit)) {
								$unit = $tmpunit;
							}
						}
						$this->printStdColumnContent($pdf, $curY, 'unit', $unit);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Frozen Qty
					if ($this->getColumnStatus('qty_frozen')) {
						$qty_frozen = !empty($object->lines[$i]->qty_frozen) ? yn($object->lines[$i]->qty_frozen) : '';
						$this->printStdColumnContent($pdf, $curY, 'qty_frozen', $qty_frozen);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Disable stock change
					if ($this->getColumnStatus('disable_stock_change')) {
						$disable_stock_change = !empty($object->lines[$i]->disable_stock_change) ? yn($object->lines[$i]->disable_stock_change) : '';
						$this->printStdColumnContent($pdf, $curY, 'disable_stock_change', $disable_stock_change);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Efficiency
					if ($this->getColumnStatus('efficiency')) {
						$efficiency = ($object->lines[$i]->product_type != Product::TYPE_SERVICE || $object->lines[$i]->efficiency != 1) ? (string) price2num($object->lines[$i]->efficiency) : '';
						$this->printStdColumnContent($pdf, $curY, 'efficiency', $efficiency);
						$nexY = max($pdf->GetY(), $nexY);
					}

					// Total cost
					if ($this->getColumnStatus('total_cost')) {
						$total_cost = (empty($hidedetails) ? price($object->lines[$i]->total_cost, 0, $outputlangs) : '');
						$this->printStdColumnContent($pdf, $curY, 'total_cost', $total_cost);
						$nexY = max($pdf->GetY(), $nexY);
					}

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


					$parameters = array(
						'object' => $object,
						'i' => $i,
						'pdf' => & $pdf,
						'curY' => & $curY,
						'nexY' => & $nexY,
						'outputlangs' => $outputlangs,
						'hidedetails' => $hidedetails
					);
					$reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this); // Note that $object may have been modified by hook

					$nexY = max($nexY, $posYAfterImage);

					// Add line
					if ($dashbetweenlines && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
						$pdf->line($this->marge_gauche, $nexY, $this->page_largeur - $this->marge_droite, $nexY);
						$pdf->SetLineStyle(array('dash' => 0));
					}

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == $pageposbeforeprintlines) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, $hidetop, 1, $conf->currency, $outputlangsbis);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $conf->currency, $outputlangsbis);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', true, 0); // The only function to edit the bottom margin of current page to set it.
						if (!$donotrepeathead) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
					}

					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) { // @phan-suppress-current-line PhanUndeclaredProperty
						if ($pagenb == $pageposafter) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, $hidetop, 1, $conf->currency, $outputlangsbis);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $conf->currency, $outputlangsbis);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pagenb++;
						if (!$donotrepeathead) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
					}
				}

				// Show square
				if ($pagenb == $pageposbeforeprintlines) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, $hidetop, 0, $conf->currency, $outputlangsbis);
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $conf->currency, $outputlangsbis);
				}
				$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;

				// Display total zone
				if (empty($hidedetails)) {
					$posy = $this->drawTotalTable($pdf, $object, $bottomlasttab, $outputlangs);
				}

				// Pagefoot
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages(); // @phan-suppress-current-line PhanUndeclaredMethod
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
				global $action;
				$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
				$this->warnings = $hookmanager->warnings;
				if ($reshook < 0) {
					$this->error = $hookmanager->error;
					$this->errors = $hookmanager->errors;
					dolChmod($file);
					return -1;
				}

				dolChmod($file);

				$this->result = array('fullpath' => $file);

				return 1; // No error
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "BOM_OUTPUTDIR");
			return 0;
		}
	}

	/**
	 *	Show total table of BOM
	 *
	 *	@param	TCPDF|TCPDI	$pdf			Object PDF
	 *	@param	BOM			$object			Object BOM
	 *	@param	float		$posy			Position depart
	 *	@param	Translate	$outputlangs	Object langs
	 *	@return	float						Position pour suite
	 */
	protected function drawTotalTable(&$pdf, $object, $posy, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
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

		// Total manufacturing cost
		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFillColor(224, 224, 224);
		$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("ManufacturingCost"), $useborder, 'L', true);
		$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_cost, 0, $outputlangs), $useborder, 'R', true);

		// Unit manufacturing cost
		if (!empty($object->qty) && $object->qty != 1) {
			$index++;
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetFillColor(248, 248, 248);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("ManufacturingUnitCost"), $useborder, 'L', true);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($object->unit_cost, 0, $outputlangs), $useborder, 'R', true);
		}

		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 0);

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation models
	 *
	 *  @param  DoliDB  	$db					Database handler
	 *  @param  int<0,max>	$maxfilenamelength	Max length of value to show
	 *  @return string[]|int<-1,0>				List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		return parent::liste_modeles($db, $maxfilenamelength);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Show table for lines
	 *
	 *	@param	TCPDF|TCPDI	$pdf     		Object PDF
	 *	@param	float		$tab_top		Top position of table
	 *	@param	float		$tab_height		Height of table (rectangle)
	 *	@param	int			$nexY			Y (not used)
	 *	@param	Translate	$outputlangs	Langs object
	 *	@param	int<-1,1>	$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *	@param	int<0,1>	$hidebottom		Hide bottom bar of array
	 *	@param	string		$currency		Currency code
	 *	@param	?Translate	$outputlangsbis	Langs object bis
	 *	@return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '', $outputlangsbis = null)
	{
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom = 0;
		if ($hidetop) {
			$hidetop = -1;
		}

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		if (empty($hidetop)) {
			$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
			if (getDolGlobalInt('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
				$titre .= ' - '.$outputlangsbis->transnoentities("AmountInCurrency", $outputlangsbis->transnoentitiesnoconv("Currency".$currency));
			}

			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			$titlebgcolor = getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR');
			if ($titlebgcolor) {
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->tabTitleHeight, 'F', array(), explode(',', $titlebgcolor));
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
	 *	@param	TCPDF|TCPDI	$pdf     		Object PDF
	 *	@param	BOM			$object     	Object to show
	 *	@param	int<0,1>	$showaddress    0=no, 1=yes
	 *	@param	Translate	$outputlangs	Object lang for output
	 *	@param	?Translate	$outputlangsbis	Object lang for output bis
	 *	@return	float|int                   Return topshift value
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		// phpcs:enable
		global $conf, $langs;

		$ltrdirection = 'L';
		if ($outputlangs->trans("DIRECTION") == 'rtl') {
			$ltrdirection = 'R';
		}

		// Load translation files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies", "mrp", "products", "stocks"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		// Show Draft Watermark
		$draftwatermark = getDolGlobalString('BOM_DRAFT_WATERMARK');
		if ($draftwatermark && $object->status == $object::STATUS_DRAFT) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', dol_escape_htmltag($draftwatermark));
		}

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
				$tmpmultidir = getMultidirOutput($object, 'mycompany');
				if (!empty($tmpmultidir)) {
					$logodir = $tmpmultidir;
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
				$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
			}
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("BillOfMaterials");
		if (getDolGlobalInt('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
			$title .= ' - ';
			$title .= $outputlangsbis->transnoentities("BillOfMaterials");
		}
		$pdf->MultiCell($w, 3, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$textref = $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref);
		if ($object->status == $object::STATUS_DRAFT) {
			$pdf->SetTextColor(128, 0, 0);
			$textref .= ' - '.$outputlangs->transnoentities("NotValidated");
		}
		$pdf->MultiCell($w, 4, $textref, '', 'R');

		$posy += 1;
		$pdf->SetFont('', '', $default_font_size - 2);

		if (!empty($object->label)) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Label")." : ".$outputlangs->convToOutputCharset((string) $object->label), '', 'R');
		}

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$typeLabel = ($object->bomtype == 1) ? $outputlangs->transnoentities("Disassemble") : $outputlangs->transnoentities("Manufacturing");
		$pdf->MultiCell($w, 3, $outputlangs->transnoentities("Type")." : ".$typeLabel, '', 'R');

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);

		$title = $outputlangs->transnoentities("Date");
		if (getDolGlobalInt('PDF_USE_ALSO_LANGUAGE_CODE') && is_object($outputlangsbis)) {
			$title .= ' - '.$outputlangsbis->transnoentities("Date");
		}
		$pdf->MultiCell($w, 3, $title." : ".dol_print_date($object->date_creation, "day", false, $outputlangs, true), '', 'R');

		$posy += 1;

		$top_shift = 0;
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
			$posy = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
			$posy += $top_shift;
			$posx = $this->marge_gauche;
			if (getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->page_largeur - $this->marge_droite - 80;
			}

			$hautcadre = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 38 : 40;
			$widthrecbox = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 82;

			// Show sender frame
			if (!getDolGlobalString('MAIN_PDF_NO_SENDER_FRAME')) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx, $posy - 5);
				$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillFrom").":", 0, $ltrdirection);
				$pdf->SetXY($posx, $posy);
				$pdf->SetFillColor(230, 230, 230);
				$pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', true);
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

			// Show BOM target product and manufacturing parameters box on the right
			$widthrecbox = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 92 : 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84; // To work with US executive format
			}
			$posy = getDolGlobalInt('MAIN_PDF_USE_ISO_LOCATION') ? 40 : 42;
			$posy += $top_shift;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (getDolGlobalInt('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->marge_gauche;
			}

			// Show target product frame
			if (!getDolGlobalString('MAIN_PDF_NO_RECIPENT_FRAME')) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx + 2, $posy - 5);
				$targetTitle = ($object->bomtype == 1) ? $outputlangs->transnoentities("ToObtain") : $outputlangs->transnoentities("ToProduce");
				$pdf->MultiCell($widthrecbox, 5, $targetTitle.":", 0, $ltrdirection);
				$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
			}

			// Show product name/ref
			$prodname = '';
			if (is_object($object->product) && !empty($object->product->ref)) {
				$prodname = $object->product->ref.(!empty($object->product->label) ? ' - '.$object->product->label : '');
			} elseif ($object->specimen) {
				$prodname = 'PROD-SPECIMEN - '.$outputlangs->transnoentities("Product");
			}
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell($widthrecbox - 4, 4, $outputlangs->convToOutputCharset($prodname), 0, $ltrdirection);

			$posy = $pdf->getY() + 1;

			// Show BOM parameters (quantity, efficiency, duration, warehouse)
			$carac_bom = $outputlangs->transnoentities("Quantity")." : ".price2num($object->qty, 'MS');
			if (getDolGlobalInt('PRODUCT_USE_UNITS') && !empty($object->fk_unit)) {
				$tmpobjunit = $object->getLabelOfUnit('short', $outputlangs, 1);
				if (is_string($tmpobjunit) && $tmpobjunit !== '') {
					$carac_bom .= ' '.$tmpobjunit;
				}
			}
			if (!empty($object->efficiency)) {
				$carac_bom .= "\n".$outputlangs->transnoentities("ManufacturingEfficiency")." : ".price2num($object->efficiency);
			}
			if (!empty($object->duration)) {
				$carac_bom .= "\n".$outputlangs->transnoentities("EstimatedDuration")." : ".convertSecondToTime((int) $object->duration, 'allhourmin');
			}
			if (!empty($object->fk_warehouse) && $object->fk_warehouse > 0 && isModEnabled('stock')) {
				require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
				$warehouse = new Entrepot($this->db);
				if ($warehouse->fetch((int) $object->fk_warehouse) > 0) {
					$carac_bom .= "\n".$outputlangs->transnoentities("WarehouseForProduction")." : ".$warehouse->ref;
				}
			}

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell($widthrecbox - 4, 4, $outputlangs->convToOutputCharset($carac_bom), 0, $ltrdirection);
		}

		$pdf->SetTextColor(0, 0, 0);
		return $top_shift;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *	Show footer of page. Need this->emetteur object
	 *
	 *	@param	TCPDI|TCPDF		$pdf     		PDF
	 *	@param	CommonObject	$object			Object to show
	 *	@param	Translate		$outputlangs	Object lang for output
	 *	@param	int<0,1>		$hidefreetext	1=Hide free text
	 *	@return	int<0,1>						Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS');
		return pdf_pagefoot($pdf, $outputlangs, 'BOM_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}

	/**
	 *	Define Array Column Field
	 *
	 *	@param	CommonObject	$object    		common object
	 *	@param	Translate		$outputlangs    langs
	 *	@param	int<0,1>		$hidedetails	Do not show line details
	 *	@param	int<0,1>		$hidedesc		Do not show desc
	 *	@param	int<0,1>		$hideref		Do not show ref
	 *	@return	void
	 */
	public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $hookmanager;

		// Default field style for content
		$this->defaultContentsFieldsStyle = array(
			'align' => 'R', // R,C,L
			'padding' => array(1, 0.5, 1, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		// Default field style for titles
		$this->defaultTitlesFieldsStyle = array(
			'align' => 'C', // R,C,L
			'padding' => array(0.5, 0, 0.5, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		$rank = 0; // do not use negative rank
		$this->cols['desc'] = array(
			'rank' => $rank,
			'width' => false, // only for desc
			'status' => true,
			'title' => array(
				'textkey' => 'Designation',
				'align' => 'L',
				'padding' => array(0.5, 0.5, 0.5, 0.5),
			),
			'content' => array(
				'align' => 'L',
				'padding' => array(1.0, 0.5, 1.0, 1.5),
			),
		);

		$rank += 10;
		$this->cols['qty'] = array(
			'rank' => $rank,
			'width' => 16.0, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Qty'
			),
			'border-left' => true,
		);

		$rank += 10;
		$this->cols['unit'] = array(
			'rank' => $rank,
			'width' => 15.0, // in mm
			'status' => false,
			'title' => array(
				'textkey' => 'Unit'
			),
			'content' => array(
				'align' => 'C',
			),
			'border-left' => true,
		);
		$hasunit = (bool) getDolGlobalInt('PRODUCT_USE_UNITS');
		if (!$hasunit && !empty($object->lines)) {
			foreach ($object->lines as $tmpbomline) {
				if (!empty($tmpbomline->fk_unit)) {
					$hasunit = true;
					break;
				}
			}
		}
		if ($hasunit) {
			$this->cols['unit']['status'] = true;
		}

		$rank += 10;
		$this->cols['qty_frozen'] = array(
			'rank' => $rank,
			'width' => 18.0, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'QtyFrozen'
			),
			'content' => array(
				'align' => 'C',
			),
			'border-left' => true,
		);

		$rank += 10;
		$this->cols['disable_stock_change'] = array(
			'rank' => $rank,
			'width' => 22.0, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'DisableStockChange'
			),
			'content' => array(
				'align' => 'C',
			),
			'border-left' => true,
		);

		$rank += 10;
		$this->cols['efficiency'] = array(
			'rank' => $rank,
			'width' => 20.0, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'ManufacturingEfficiency'
			),
			'border-left' => true,
		);

		$rank += 1000; // add a big offset to be sure is the last col because default extrafield rank is 100
		$this->cols['total_cost'] = array(
			'rank' => $rank,
			'width' => 24.0, // in mm
			'status' => empty($hidedetails),
			'title' => array(
				'textkey' => 'TotalCost'
			),
			'border-left' => true,
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
