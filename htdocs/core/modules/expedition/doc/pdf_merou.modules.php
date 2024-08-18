<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2013      Florian Henry		<florian.henry@open-concept.pro>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024       Frédéric France             <frederic.france@free.fr>
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
 *	\file       htdocs/core/modules/expedition/doc/pdf_merou.modules.php
 *	\ingroup    expedition
 *	\brief      Class file used to generate the dispatch slips for the Merou model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expedition/modules_expedition.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to build sending documents with model Merou
 */
class pdf_merou extends ModelePdfExpedition
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

	public $destinataire;
	public $expediteur;
	public $livreur;

	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs, $mysoc;

		$this->db = $db;
		$this->name = "merou";
		$this->description = $langs->trans("DocumentModelMerou");
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template

		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = round($formatarray['height'] / 2);
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

		$this->option_logo = 1; // Display logo

		// Get source company
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *	@param		Expedition	$object				Object expedition to generate (or id if old method)
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int<0,1>	$hidedetails		Do not show line details
	 *  @param		int<0,1>	$hidedesc			Do not show desc
	 *  @param		int<0,1>	$hideref			Do not show ref
	 *  @return		int<-1,1>						1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $conf, $langs, $mysoc, $hookmanager;

		$object->fetch_thirdparty();

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies", "propal", "deliveries", "sendings", "productbatch"));

		if ($conf->expedition->dir_output) {
			$object->fetch_thirdparty();

			$origin = $object->origin;

			$object->fetch_origin();

			//Create sender
			$this->expediteur = $mysoc;

			//Create recipient
			$idcontact = $object->origin_object->getIdContact('external', 'SHIPPING');
			$this->destinataire = new Contact($this->db);
			if (!empty($idcontact[0])) {
				$this->destinataire->fetch($idcontact[0]);
			}

			//Create deliverer
			$idcontact = $object->origin_object->getIdContact('internal', 'LIVREUR');
			$this->livreur = new User($this->db);
			if (!empty($idcontact[0])) {
				$this->livreur->fetch($idcontact[0]);
			}

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->expedition->dir_output."/sending";
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$expref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expedition->dir_output."/sending/".$expref;
				$file = $dir."/".$expref.".pdf";
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

				$pdf = pdf_getInstance($this->format, 'mm', 'l');
				$default_font_size = pdf_getPDFFontSize($outputlangs);
				$heightforinfotot = 0; // Height reserved to output the info and total part
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

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
				}

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Shipment"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Shipment"));
				if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
					$pdf->SetCompression(false);
				}

				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 3);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 52;
				$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 : 10);

				$tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

				// Display notes
				if (!empty($object->note_public)) {
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->marge_gauche, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);
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


				$pdf->SetFillColor(240, 240, 240);
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetXY(10, $tab_top + 5);

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				$num = count($object->lines);
				// Loop on each lines
				for ($i = 0; $i < $num; $i++) {
					$curY = $nexY;
					$pdf->SetFont('', '', $default_font_size - 3);
					$pdf->SetTextColor(0, 0, 0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					// Description of product line
					$libelleproduitservice = pdf_writelinedesc($pdf, $object, $i, $outputlangs, 90, 3, 50, $curY, 0, 1);

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 3);

					// Check boxes
					$pdf->SetDrawColor(120, 120, 120);
					$pdf->Rect(10 + 3, $curY, 3, 3);
					$pdf->Rect(20 + 3, $curY, 3, 3);

					//Inserting the product reference
					$pdf->SetXY(30, $curY);
					$pdf->SetFont('', 'B', $default_font_size - 3);
					$pdf->MultiCell(24, 3, $outputlangs->convToOutputCharset($object->lines[$i]->ref), 0, 'L', 0);

					$pdf->SetXY(140, $curY);
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_asked, 0, 'C', 0);

					$pdf->SetXY(170, $curY);
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_shipped, 0, 'C', 0);

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
							$this->_tableau($pdf, $tab_top_newpage - 1, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
					}
					if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {
						if ($pagenb == 1) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						} else {
							$this->_tableau($pdf, $tab_top_newpage - 1, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						// New page
						$pdf->AddPage();
						$pagenb++;
					}
				}

				// Show square
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				} else {
					$this->_tableau($pdf, $tab_top_newpage - 1, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

				// Pagefoot
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

				return 1;
			} else {
				$this->error = $outputlangs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $outputlangs->transnoentities("ErrorConstantNotDefined", "EXP_OUTPUTDIR");
			return 0;
		}
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
		global $langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Translations
		$langs->loadLangs(array("main", "bills", "orders"));

		if (empty($hidetop)) {
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY(10, $tab_top);
			$pdf->MultiCell(10, 5, "LS", 0, 'C', 1);
			$pdf->line(20, $tab_top, 20, $tab_top + $tab_height);
			$pdf->SetXY(20, $tab_top);
			$pdf->MultiCell(10, 5, "LR", 0, 'C', 1);
			$pdf->line(30, $tab_top, 30, $tab_top + $tab_height);
			$pdf->SetXY(30, $tab_top);
			$pdf->MultiCell(20, 5, $outputlangs->transnoentities("Ref"), 0, 'C', 1);
			$pdf->SetXY(50, $tab_top);
			$pdf->MultiCell(90, 5, $outputlangs->transnoentities("Description"), 0, 'L', 1);
			$pdf->SetXY(140, $tab_top);
			$pdf->MultiCell(30, 5, $outputlangs->transnoentities("QtyOrdered"), 0, 'C', 1);
			$pdf->SetXY(170, $tab_top);
			$pdf->MultiCell(30, 5, $outputlangs->transnoentities("QtyToShip"), 0, 'C', 1);
		}
		$pdf->Rect(10, $tab_top, 190, $tab_height);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Expedition	$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	void
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetY(-23);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("GoodStatusDeclaration"), 0, 'L');
		$pdf->SetY(-13);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ToAndDate"), 0, 'C');
		$pdf->SetXY(120, -23);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("NameAndSignature"), 0, 'C');

		// Show page nb only on iso languages (so default Helvetica font)
		//if (pdf_getPDFFont($outputlangs) == 'Helvetica')
		//{
		//    $pdf->SetXY(-10,-10);
		//    $pdf->MultiCell(11, 2, $pdf->PageNo().'/'.$pdf->getAliasNbPages(), 0, 'R', 0);
		//}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Expedition	$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	float|int                   Return topshift value
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $langs, $hookmanager;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if ($object->statut == 0 && (getDolGlobalString('SENDING_DRAFT_WATERMARK'))) {
			pdf_watermark($pdf, $outputlangs, $this->page_hauteur, $this->page_largeur, 'mm', $conf->global->SENDING_DRAFT_WATERMARK);
		}

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - 100;

		$Xoff = 90;
		$Yoff = 0;

		$tab4_top = 60;
		$tab4_hl = 6;
		$tab4_sl = 4;
		$line = 2;

		//*********************LOGO****************************
		$pdf->SetXY(11, 7);
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
				$pdf->Image($logo, 10, 5, 0, $height); // width=0 (auto)
			} else {
				$pdf->SetTextColor(200, 0, 0);
				$pdf->SetFont('', 'B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		} else {
			$text = $this->emetteur->name;
			$pdf->MultiCell(70, 3, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		//*********************Entete****************************
		//Document name
		$pdf->SetXY($Xoff, 7);
		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->MultiCell(0, 3, $outputlangs->transnoentities("SendingSheet"), '', 'L'); // Sending sheet
		//Num Expedition
		$Yoff += 7;
		$Xoff = 142;
		//$pdf->Rect($Xoff, $Yoff, 85, 8);
		$pdf->SetXY($Xoff, $Yoff);
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $Xoff, 3, $outputlangs->transnoentities("RefSending").': '.$outputlangs->convToOutputCharset($object->ref), '', 'R');
		//$this->Code39($Xoff+43, $Yoff+1, $object->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);

		$origin = $object->origin;
		$origin_id = $object->origin_id;

		// Add list of linked elements
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size - 1);

		//$this->Code39($Xoff+43, $Yoff+1, $object->commande->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);
		//Definition Location of the Company block
		$Xoff = 110;
		$blSocX = 90;
		$blSocY = 24;
		$blSocW = 50;
		$blSocX2 = $blSocW + $blSocX;

		// Sender name
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', 'B', $default_font_size - 3);
		$pdf->SetXY($blSocX, $blSocY + 1);
		$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		$pdf->SetTextColor(0, 0, 0);

		// Sender properties
		$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetXY($blSocX, $blSocY + 4);
		$pdf->MultiCell(80, 2, $carac_emetteur, 0, 'L');


		if ($object->thirdparty->code_client) {
			$Yoff += 3;
			$posy = $Yoff;
			$pdf->SetXY($Xoff, $posy);
			$pdf->SetTextColor(0, 0, 0);
			$pdf->MultiCell($this->page_largeur - $this->marge_droite - $Xoff, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}

		// Date delivery
		$Yoff += 7;
		$pdf->SetXY($blSocX - 80, $blSocY + 17);

		$pdf->SetFont('', 'B', $default_font_size - 3);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->MultiCell(70, 8, $outputlangs->transnoentities("DateDeliveryPlanned")." : ".dol_print_date($object->date_delivery, 'day', false, $outputlangs, true), '', 'L');

		$pdf->SetXY($blSocX - 80, $blSocY + 20);
		$pdf->SetFont('', 'B', $default_font_size - 3);
		$pdf->SetTextColor(0, 0, 0);
		$pdf->MultiCell(70, 8, $outputlangs->transnoentities("TrackingNumber")." : ".$object->tracking_number, '', 'L');

		// Deliverer
		$pdf->SetXY($blSocX - 80, $blSocY + 23);
		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetTextColor(0, 0, 0);

		if (!empty($object->tracking_number)) {
			$object->getUrlTrackingStatus($object->tracking_number);
			if (!empty($object->tracking_url)) {
				if ($object->shipping_method_id > 0) {
					// Get code using getLabelFromKey
					$code = $outputlangs->getLabelFromKey($this->db, $object->shipping_method_id, 'c_shipment_mode', 'rowid', 'code');

					$label = '';
					$label .= $outputlangs->trans("SendingMethod").": ".$outputlangs->trans("SendingMethod".strtoupper($code));
					//var_dump($object->tracking_url != $object->tracking_number);exit;
					if ($object->tracking_url != $object->tracking_number) {
						$label .= " : ";
						$label .= $object->tracking_url;
					}
					$pdf->SetFont('', 'B', $default_font_size - 3);
					$pdf->writeHTMLCell(50, 8, '', '', $label, '', 'L');
				}
			}
		} else {
			$pdf->MultiCell(50, 8, $outputlangs->transnoentities("Deliverer")." ".$outputlangs->convToOutputCharset($this->livreur->getFullName($outputlangs)), '', 'L');
		}


		// Shipping company (My Company)
		$Yoff = $blSocY;
		$blExpX = $Xoff - 20;
		$blW = 52;
		$Ydef = $Yoff;
		$pdf->Rect($blExpX, $Yoff, $blW, 26);

		$object->fetch_thirdparty();

		// If SHIPPING contact defined on order, we use it
		$usecontact = false;
		$arrayidcontact = $object->origin_object->getIdContact('external', 'SHIPPING');
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

		$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ((!empty($object->contact)) ? $object->contact : null), ($usecontact ? 1 : 0), 'targetwithdetails', $object);

		$blDestX = $blExpX + 55;
		$blW = 54;
		$Yoff = $Ydef + 1;

		$widthrecbox = $blW;

		$top_shift = 0;
		// Show list of linked objects
		/*
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY()) {
			$top_shift = $pdf->getY() - $current_y;
		}
		*/

		// Show Recipient frame
		$pdf->SetFont('', 'B', $default_font_size - 3);
		$pdf->SetXY($blDestX, $Yoff - 4);
		$pdf->MultiCell($blW, 3, $outputlangs->transnoentities("Recipient"), 0, 'L');
		$pdf->Rect($blDestX, $Yoff - 1, $blW, 26);

		// Show recipient name
		$pdf->SetFont('', 'B', $default_font_size - 3);
		$pdf->SetXY($blDestX, $Yoff);
		$pdf->MultiCell($blW, 3, $carac_client_name, 0, 'L');

		$posy = $pdf->getY();

		// Show recipient information
		$pdf->SetFont('', '', $default_font_size - 3);
		$pdf->SetXY($blDestX, $posy);
		$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');

		return $top_shift;
	}
}
