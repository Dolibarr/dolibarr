<?php
/* Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017-2018 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018-2024  Frédéric France      <frederic.france@free.fr>
 * Copyright (C) 2019      Pierre Ardoin      	<mapiolca@me.com>
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
 *	\file       htdocs/core/modules/propale/doc/pdf_azur.modules.php
 *	\ingroup    propale
 *	\brief      File of Class to generate PDF proposal with Azur template
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to generate PDF proposal Azur
 */
class pdf_azur extends ModelePDFPropales
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
	 * @var int	Save the name of generated file as the main doc when generating a doc with this template
	 */
	public $update_main_doc_field;

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
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "azur";
		$this->description = $langs->trans('DocModelAzurDescription');
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

		$this->option_logo = 1; // Display logo
		$this->option_tva = 1; // Manage the vat option FACTURE_TVAOPTION
		$this->option_modereg = 1; // Display payment mode
		$this->option_condreg = 1; // Display payment terms
		$this->option_multilang = 1; // Available in several languages
		$this->option_escompte = 0; // Displays if there has been a discount
		$this->option_credit_note = 0; // Support credit notes
		$this->option_freetext = 1; // Support add of a personalised text
		$this->option_draft_watermark = 1; // Support add of a watermark on drafts
		$this->watermark = '';

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1;
		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			$this->posxtva = 101;
			$this->posxup = 118;
			$this->posxqty = 135;
			$this->posxunit = 151;
		} else {
			$this->posxtva = 106;
			$this->posxup = 122;
			$this->posxqty = 145;
			$this->posxunit = 162;
		}
		$this->posxdiscount = 162;
		$this->postotalht = 174;
		if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') || getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN')) {
			$this->posxtva = $this->posxup;
		}
		$this->posxpicture = $this->posxtva - getDolGlobalInt('MAIN_DOCUMENTS_WITH_PICTURE_WIDTH', 20); // width of images
		if ($this->page_largeur < 210) { // To work with US executive format
			$this->posxpicture -= 20;
			$this->posxtva -= 20;
			$this->posxup -= 20;
			$this->posxqty -= 20;
			$this->posxunit -= 20;
			$this->posxdiscount -= 20;
			$this->postotalht -= 20;
		}

		$this->tva = array();
		$this->tva_array = array();
		$this->localtax1 = array();
		$this->localtax2 = array();
		$this->atleastoneratenotnull = 0;
		$this->atleastonediscount = 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Propal		$object				Object to generate
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
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		dol_syslog("write_file outputlangs->defaultlang=".(is_object($outputlangs) ? $outputlangs->defaultlang : 'null'));

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load translation files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "propal", "products"));

		global $outputlangsbis;
		$outputlangsbis = null;
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs(array("main", "dict", "companies", "bills", "products", "propal"));
		}

		//  Show Draft Watermark
		if ($object->statut == $object::STATUS_DRAFT && getDolGlobalString('PROPALE_DRAFT_WATERMARK')) {
			$this->watermark = getDolGlobalString('PROPALE_DRAFT_WATERMARK');
		}

		$nblines = count($object->lines);

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray = array();
		$this->atleastonephoto = false;
		if (getDolGlobalString('MAIN_GENERATE_PROPOSALS_WITH_PICTURE')) {
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
				$realpath = '';
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

		if (count($realpatharray) == 0) {
			$this->posxpicture = $this->posxtva;
		}

		if ($conf->propal->multidir_output[$conf->entity]) {
			$object->fetch_thirdparty();

			$deja_regle = 0;

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->propal->multidir_output[$conf->entity];
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->propal->multidir_output[$object->entity]."/".$objectref;
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

				// Set nblines with the new content of lines after hook
				$nblines = count($object->lines);
				//$nbpayments = count($object->getListOfPayments());

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1, 0);

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
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfCommercialProposalTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfCommercialProposalTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
					$pdf->SetCompression(false);
				}

				// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

				// Set $this->atleastonediscount if you have at least one discount
				for ($i = 0; $i < $nblines; $i++) {
					if ($object->lines[$i]->remise_percent) {
						$this->atleastonediscount++;
					}
				}
				if (empty($this->atleastonediscount)) {
					$delta = ($this->postotalht - $this->posxdiscount);
					$this->posxpicture += $delta;
					$this->posxtva += $delta;
					$this->posxup += $delta;
					$this->posxqty += $delta;
					$this->posxunit += $delta;
					$this->posxdiscount += $delta;
					// post of fields after are not modified, stay at same position
				}

				// New page
				$pdf->AddPage();
				if (!empty($tplidx)) {
					$pdf->useTemplate($tplidx);
				}
				$pagenb++;

				$heightforinfotot = 40; // Height reserved to output the info and total part
				$heightforsignature = !getDolGlobalString('PROPAL_DISABLE_SIGNATURE') ? (pdfGetHeightForHtmlContent($pdf, $outputlangs->transnoentities("ProposalCustomerSignature")) + 10) : 0;
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS')) {
					$heightforfooter += 6;
				}
				//print $heightforinfotot + $heightforsignature + $heightforfreetext + $heightforfooter;exit;

				$top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs, $outputlangsbis);
				$pdf->SetFont('', '', $default_font_size - 1);
				$pdf->MultiCell(0, 3, ''); // Set interline to 3
				$pdf->SetTextColor(0, 0, 0);


				$tab_top = 90 + $top_shift;
				$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 42 + $top_shift : 10);

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

				// Displays notes
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
				if (getDolGlobalString('MAIN_ADD_CREATOR_IN_NOTE') && $object->user_author_id > 0) {
					$tmpuser = new User($this->db);
					$tmpuser->fetch($object->user_author_id);


					$creator_info = $langs->trans("CaseFollowedBy").' '.$tmpuser->getFullName($langs);
					if ($tmpuser->email) {
						$creator_info .= ',  '.$langs->trans("EMail").': '.$tmpuser->email;
					}
					if ($tmpuser->office_phone) {
						$creator_info .= ', '.$langs->trans("Phone").': '.$tmpuser->office_phone;
					}

					$notetoshow = dol_concatdesc($notetoshow, $creator_info);
				}

				if ($notetoshow) {
					$tab_top -= 2;

					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top - 1, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1);

					$tab_top = $nexY + 6;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

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
					$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					$showpricebeforepagebreak = 1;
					$posYAfterImage = 0;
					$posYAfterDescription = 0;

					// We start with Photo of product line
					if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot))) {	// If photo too high, we moved completely on new page
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
						$pdf->Image($realpatharray[$i], $curX + (($this->posxtva - $this->posxpicture - $imglinesize['width']) / 2), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300); // Use 300 dpi
						// $pdf->Image does not increase value return by getY, so we save it manually
						$posYAfterImage = $curY + $imglinesize['height'];
					}

					// Description of product line
					$curX = $this->posxdesc - 1;

					$pdf->startTransaction();
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
						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot))) {	// There is no space left for total+free text
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

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // On repositionne la police par default

					// VAT Rate
					if (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') && !getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN')) {
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxtva - 5, $curY);
						$pdf->MultiCell($this->posxup - $this->posxtva + 4, 3, $vat_rate, 0, 'R');
					}

					// Unit price before discount
					$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posxup, $curY);
					$pdf->MultiCell($this->posxqty - $this->posxup - 0.8, 3, $up_excl_tax, 0, 'R', 0);

					// Quantity
					$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posxqty, $curY);
					$pdf->MultiCell($this->posxunit - $this->posxqty - 0.8, 4, $qty, 0, 'R'); // Enough for 6 chars

					// Unit
					if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
						$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxunit, $curY);
						$pdf->MultiCell($this->posxdiscount - $this->posxunit - 0.8, 4, $unit, 0, 'L');
					}

					// Discount on line
					$pdf->SetXY($this->posxdiscount, $curY);
					if ($object->lines[$i]->remise_percent) {
						$pdf->SetXY($this->posxdiscount - 2, $curY);
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->postotalht - $this->posxdiscount + 2, 3, $remise_percent, 0, 'R');
					}

					// Total HT line
					$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->postotalht, $curY);
					$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->postotalht, 3, $total_excl_tax, 0, 'R', 0);

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					if (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) {
						$tvaligne = $object->lines[$i]->multicurrency_total_tva;
					} else {
						$tvaligne = $object->lines[$i]->total_tva;
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
					$this->tva[$vatrate] += $tvaligne;
					$vatcode = $object->lines[$i]->vat_src_code;
					if (empty($this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'])) {
						$this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] = 0;
					}
					$this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')] = array('vatrate' => $vatrate, 'vatcode' => $vatcode, 'amount' => $this->tva_array[$vatrate.($vatcode ? ' ('.$vatcode.')' : '')]['amount'] + $tvaligne);

					if ($posYAfterImage > $posYAfterDescription) {
						$nexY = $posYAfterImage;
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
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
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
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
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
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter + 1;
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter + 1;
				}

				// Affiche zone infos
				$posy = $this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				// Affiche zone totaux
				$posy = $this->_tableau_tot($pdf, $object, 0, $bottomlasttab, $outputlangs);

				// Affiche zone versements
				/*
				if ($deja_regle || $amount_credit_notes_included || $amount_deposits_included)
				{
					$posy=$this->_tableau_versements($pdf, $object, $posy, $outputlangs);
				}
				*/

				// Customer signature area
				if (!getDolGlobalString('PROPAL_DISABLE_SIGNATURE')) {
					$posy = $this->_signature_area($pdf, $object, $posy, $outputlangs);
				}

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();  // @phan-suppress-current-line PhanUndeclaredMethod
				}

				//If propal merge product PDF is active
				if (getDolGlobalString('PRODUIT_PDF_MERGE_PROPAL')) {
					require_once DOL_DOCUMENT_ROOT.'/product/class/propalmergepdfproduct.class.php';

					$already_merged = array();
					foreach ($object->lines as $line) {
						if (!empty($line->fk_product) && !(in_array($line->fk_product, $already_merged))) {
							// Find the desired PDF
							$filetomerge = new Propalmergepdfproduct($this->db);

							if (getDolGlobalInt('MAIN_MULTILANGS')) {
								$filetomerge->fetch_by_product($line->fk_product, $outputlangs->defaultlang);
							} else {
								$filetomerge->fetch_by_product($line->fk_product);
							}

							$already_merged[] = $line->fk_product;

							$product = new Product($this->db);
							$product->fetch($line->fk_product);

							if ($product->entity != $conf->entity) {
								$entity_product_file = $product->entity;
							} else {
								$entity_product_file = $conf->entity;
							}

							// If PDF is selected and file is not empty
							if (count($filetomerge->lines) > 0) {
								foreach ($filetomerge->lines as $linefile) {
									if (!empty($linefile->id) && !empty($linefile->file_name)) {
										if (getDolGlobalInt('PRODUCT_USE_OLD_PATH_FOR_PHOTO')) {
											if (isModEnabled("product")) {
												$filetomerge_dir = $conf->product->multidir_output[$entity_product_file].'/'.get_exdir($product->id, 2, 0, 0, $product, 'product').$product->id."/photos";
											} elseif (isModEnabled("service")) {
												$filetomerge_dir = $conf->service->multidir_output[$entity_product_file].'/'.get_exdir($product->id, 2, 0, 0, $product, 'product').$product->id."/photos";
											}
										} else {
											if (isModEnabled("product")) {
												$filetomerge_dir = $conf->product->multidir_output[$entity_product_file].'/'.get_exdir(0, 0, 0, 0, $product, 'product');
											} elseif (isModEnabled("service")) {
												$filetomerge_dir = $conf->service->multidir_output[$entity_product_file].'/'.get_exdir(0, 0, 0, 0, $product, 'product');
											}
										}

										dol_syslog(get_class($this).':: upload_dir='.$filetomerge_dir, LOG_DEBUG);

										$infile = $filetomerge_dir.'/'.$linefile->file_name;
										if (file_exists($infile) && is_readable($infile)) {
											$pagecount = $pdf->setSourceFile($infile);
											for ($i = 1; $i <= $pagecount; $i++) {
												$tplIdx = $pdf->importPage($i);
												if ($tplIdx !== false) {
													$s = $pdf->getTemplatesize($tplIdx);
													$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
													$pdf->useTemplate($tplIdx);
												} else {
													setEventMessages(null, array($infile.' cannot be added, probably protected PDF'), 'warnings');
												}
											}
										}
									}
								}
							}
						}
					}
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				//Add pdfgeneration hook
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
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "PROP_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show payments table
	 *
	 *  @param	TCPDF		$pdf            Object PDF
	 *  @param  Propal		$object         Object proposal
	 *  @param  int			$posy           Position y in PDF
	 *  @param  Translate	$outputlangs    Object langs for output
	 *  @return int             			Return integer <0 if KO, >0 if OK
	 */
	protected function _tableau_versements(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		Propal		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	int
	 */
	protected function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		global $conf, $mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', '', $default_font_size - 1);

		$diffsizetitle = (!getDolGlobalString('PDF_DIFFSIZE_TITLE') ? 3 : $conf->global->PDF_DIFFSIZE_TITLE);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && empty($mysoc->tva_assuj)) {
			$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy = $pdf->GetY() + 4;
		}

		$posxval = 52;
		if (getDolGlobalString('MAIN_PDF_DELIVERY_DATE_TEXT')) {
			$displaydate = "daytext";
		} else {
			$displaydate = "day";
		}

		// Show shipping date
		if (!empty($object->delivery_date)) {
			$outputlangs->load("sendings");
			$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("DateDeliveryPlanned").':';
			$pdf->MultiCell(80, 4, $titre, 0, 'L');
			$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
			$pdf->SetXY($posxval, $posy);
			$dlp = dol_print_date($object->delivery_date, $displaydate, false, $outputlangs, true);
			$pdf->MultiCell(80, 4, $dlp, 0, 'L');

			$posy = $pdf->GetY() + 1;
		} elseif ($object->availability_code || $object->availability) {    // Show availability conditions
			$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("AvailabilityPeriod").':';
			$pdf->MultiCell(80, 4, $titre, 0, 'L');
			$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
			$pdf->SetXY($posxval, $posy);
			$lib_availability = ($outputlangs->transnoentities("AvailabilityType".$object->availability_code) != 'AvailabilityType'.$object->availability_code) ? $outputlangs->transnoentities("AvailabilityType".$object->availability_code) : $outputlangs->convToOutputCharset($object->availability);
			$lib_availability = str_replace('\n', "\n", $lib_availability);
			$pdf->MultiCell(80, 4, $lib_availability, 0, 'L');

			$posy = $pdf->GetY() + 1;
		}

		// Show delivery mode
		if (!getDolGlobalString('PROPOSAL_PDF_HIDE_DELIVERYMODE') && $object->shipping_method_id > 0) {
			$outputlangs->load("sendings");

			$shipping_method_id = $object->shipping_method_id;
			if (getDolGlobalString('SOCIETE_ASK_FOR_SHIPPING_METHOD') && !empty($this->emetteur->shipping_method_id)) {
				$shipping_method_id = $this->emetteur->shipping_method_id;
			}
			$shipping_method_code = dol_getIdFromCode($this->db, (string) $shipping_method_id, 'c_shipment_mode', 'rowid', 'code');
			$shipping_method_label = dol_getIdFromCode($this->db, (string) $shipping_method_id, 'c_shipment_mode', 'rowid', 'libelle');

			$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("SendingMethod").':';
			$pdf->MultiCell(43, 4, $titre, 0, 'L');

			$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
			$pdf->SetXY($posxval, $posy);
			$lib_condition_paiement = ($outputlangs->transnoentities("SendingMethod".strtoupper($shipping_method_code)) != "SendingMethod".strtoupper($shipping_method_code)) ? $outputlangs->trans("SendingMethod".strtoupper($shipping_method_code)) : $shipping_method_label;
			$lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);
			$pdf->MultiCell(67, 4, $lib_condition_paiement, 0, 'L');

			$posy = $pdf->GetY() + 1;
		}

		// Show payments conditions
		if (!getDolGlobalString('PROPOSAL_PDF_HIDE_PAYMENTTERM') && $object->cond_reglement_code) {
			$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("PaymentConditions").':';
			$pdf->MultiCell(43, 4, $titre, 0, 'L');

			$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
			$pdf->SetXY($posxval, $posy);
			$lib_condition_paiement = $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) != 'PaymentCondition'.$object->cond_reglement_code ? $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code) : $outputlangs->convToOutputCharset($object->cond_reglement_doc ? $object->cond_reglement_doc : $object->cond_reglement_label);
			$lib_condition_paiement = str_replace('\n', "\n", $lib_condition_paiement);
			if ($object->deposit_percent > 0) {
				$lib_condition_paiement = str_replace('__DEPOSIT_PERCENT__', $object->deposit_percent, $lib_condition_paiement);
			}
			$pdf->MultiCell(67, 4, $lib_condition_paiement, 0, 'L');

			$posy = $pdf->GetY() + 3;
		}

		if (!getDolGlobalString('PROPOSAL_PDF_HIDE_PAYMENTMODE')) {
			// Show payment mode
			if ($object->mode_reglement_code
			&& $object->mode_reglement_code != 'CHQ'
			&& $object->mode_reglement_code != 'VIR') {
				$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
				$pdf->SetXY($this->marge_gauche, $posy);
				$titre = $outputlangs->transnoentities("PaymentMode").':';
				$pdf->MultiCell(80, 5, $titre, 0, 'L');
				$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
				$pdf->SetXY($posxval, $posy);
				$lib_mode_reg = $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) != 'PaymentType'.$object->mode_reglement_code ? $outputlangs->transnoentities("PaymentType".$object->mode_reglement_code) : $outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell(80, 5, $lib_mode_reg, 0, 'L');

				$posy = $pdf->GetY() + 2;
			}

			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ') {
				// Si mode reglement non force ou si force a CHQ
				if (getDolGlobalInt('FACTURE_CHQ_NUMBER')) {
					if (getDolGlobalInt('FACTURE_CHQ_NUMBER') > 0) {
						$account = new Account($this->db);
						$account->fetch(getDolGlobalInt('FACTURE_CHQ_NUMBER'));

						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $account->owner_name), 0, 'L', 0);
						$posy = $pdf->GetY() + 1;

						if (!getDolGlobalString('MAIN_PDF_HIDE_CHQ_ADDRESS')) {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($account->owner_address), 0, 'L', 0);
							$posy = $pdf->GetY() + 2;
						}
					}
					if ($conf->global->FACTURE_CHQ_NUMBER == -1) {
						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('', 'B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo', $this->emetteur->name), 0, 'L', 0);
						$posy = $pdf->GetY() + 1;

						if (!getDolGlobalString('MAIN_PDF_HIDE_CHQ_ADDRESS')) {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('', '', $default_font_size - $diffsizetitle);
							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
							$posy = $pdf->GetY() + 2;
						}
					}
				}
			}

			// If payment mode not forced or forced to VIR, show payment with BAN
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR') {
				if (!empty($object->fk_account) || !empty($object->fk_bank) || getDolGlobalInt('FACTURE_RIB_NUMBER')) {
					$bankid = (empty($object->fk_account) ? $conf->global->FACTURE_RIB_NUMBER : $object->fk_account);
					if (!empty($object->fk_bank)) {
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

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show total to pay
	 *
	 *	@param	TCPDF		$pdf            Object PDF
	 *	@param  Propal		$object         Object propal
	 *	@param  int			$deja_regle     Amount already paid
	 *	@param	int			$posy			Start position
	 *	@param	Translate	$outputlangs	Object langs
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *	@return int							Position for continuation
	 */
	protected function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs, $outputlangsbis = null)
	{
		// phpcs:enable
		global $conf, $mysoc;
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

		// Total HT
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($col1x, $tab2_top);
		$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

		$total_ht = ((isModEnabled("multicurrency") && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ht : $object->total_ht);
		$pdf->SetXY($col2x, $tab2_top);
		$pdf->MultiCell($largcol2, $tab2_hl, price($total_ht + (!empty($object->remise) ? $object->remise : 0), 0, $outputlangs), 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248, 248, 248);

		$total_ttc = (isModEnabled("multicurrency") && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;

		$this->atleastoneratenotnull = 0;
		if (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT')) {
			$tvaisnull = (!empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000']));
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
							$totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code).' ';

							if (getDolGlobalString('PDF_LOCALTAX1_LABEL_IS_CODE_OR_RATE') == 'nocodenorate') {
								$totalvat .= $tvacompl;
							} else {
								$totalvat .= vatrate(abs((float) $tvakey), 1).$tvacompl;
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
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code).' ';

							if (getDolGlobalString('PDF_LOCALTAX2_LABEL_IS_CODE_OR_RATE') == 'nocodenorate') {
								$totalvat .= $tvacompl;
							} else {
								$totalvat .= vatrate(abs((float) $tvakey), 1).$tvacompl;
							}

							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$total_localtax = ((isModEnabled("multicurrency") && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? price2num($tvaval * $object->multicurrency_tx, 'MT') : $tvaval);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($total_localtax, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}

				// VAT
				foreach ($this->tva_array as $tvakey => $tvaval) {
					if ($tvakey != 0) {    // On affiche pas taux 0
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
							$totalvat = $outputlangs->transcountrynoentities("TotalLT1", $mysoc->country_code).' ';

							if (getDolGlobalString('PDF_LOCALTAX1_LABEL_IS_CODE_OR_RATE') == 'nocodenorate') {
								$totalvat .= $tvacompl;
							} else {
								$totalvat .= vatrate(abs((float) $tvakey), 1).$tvacompl;
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
							$totalvat = $outputlangs->transcountrynoentities("TotalLT2", $mysoc->country_code).' ';

							if (getDolGlobalString('PDF_LOCALTAX2_LABEL_IS_CODE_OR_RATE') == 'nocodenorate') {
								$totalvat .= $tvacompl;
							} else {
								$totalvat .= vatrate(abs((float) $tvakey), 1).$tvacompl;
							}

							$pdf->MultiCell($col2x - $col1x, $tab2_hl, $totalvat, 0, 'L', 1);

							$total_localtax = ((isModEnabled("multicurrency") && isset($object->multicurrency_tx) && $object->multicurrency_tx != 1) ? price2num($tvaval * $object->multicurrency_tx, 'MT') : $tvaval);

							$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
							$pdf->MultiCell($largcol2, $tab2_hl, price($total_localtax, 0, $outputlangs), 0, 'R', 1);
						}
					}
				}
				//}

				// Total TTC
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFillColor(224, 224, 224);
				$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($total_ttc, 0, $outputlangs), $useborder, 'R', 1);
			}
		}

		$pdf->SetTextColor(0, 0, 0);

		$resteapayer = 0;
		/*
		$resteapayer = $object->total_ttc - $deja_regle;
		if (!empty($object->paye)) $resteapayer=0;
		*/

		if ($deja_regle > 0) {
			$index++;

			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPaid"), 0, 'L', 0);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle, 0, $outputlangs), 0, 'R', 0);

			/*
			if ($object->close_code == 'discount_vat') {
				$index++;
				$pdf->SetFillColor(255,255,255);

				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle, 0, $outputlangs), $useborder, 'R', 1);

				$resteapayer=0;
			}
			*/

			$index++;
			$pdf->SetTextColor(0, 0, 60);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x - $col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetTextColor(0, 0, 0);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
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
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	protected function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop = 0, $hidebottom = 0, $currency = '')
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
			$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top - 4);
			$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

			//$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
			if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
				$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, 5, 'F', null, explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
			}
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $hidetop, $hidebottom); // Rect takes a length in 3rd parameter and 4th parameter

		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5); // line takes a position y in 2nd parameter and 4th parameter

			$pdf->SetXY($this->posxdesc - 1, $tab_top + 1);
			$pdf->MultiCell(108, 2, $outputlangs->transnoentities("Designation"), '', 'L');
		}

		if (getDolGlobalString('MAIN_GENERATE_PROPOSALS_WITH_PICTURE')) {
			$pdf->line($this->posxpicture - 1, $tab_top, $this->posxpicture - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				//$pdf->SetXY($this->posxpicture-1, $tab_top+1);
				//$pdf->MultiCell($this->posxtva-$this->posxpicture-1,2, $outputlangs->transnoentities("Photo"),'','C');
			}
		}

		if (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT') && !getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN')) {
			$pdf->line($this->posxtva - 1, $tab_top, $this->posxtva - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				// Not do -3 and +3 instead of -1 -1 to have more space for text 'Sales tax'
				$pdf->SetXY($this->posxtva - 3, $tab_top + 1);
				$pdf->MultiCell($this->posxup - $this->posxtva + 3, 2, $outputlangs->transnoentities("VAT"), '', 'C');
			}
		}

		$pdf->line($this->posxup - 1, $tab_top, $this->posxup - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxup - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxqty - $this->posxup - 1, 2, $outputlangs->transnoentities("PriceUHT"), '', 'C');
		}

		$pdf->line($this->posxqty - 1, $tab_top, $this->posxqty - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxqty - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxunit - $this->posxqty - 1, 2, $outputlangs->transnoentities("Qty"), '', 'C');
		}

		if (getDolGlobalInt('PRODUCT_USE_UNITS')) {
			$pdf->line($this->posxunit - 1, $tab_top, $this->posxunit - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxunit - 1, $tab_top + 1);
				$pdf->MultiCell(
					$this->posxdiscount - $this->posxunit - 1,
					2,
					$outputlangs->transnoentities("Unit"),
					'',
					'C'
				);
			}
		}

		$pdf->line($this->posxdiscount - 1, $tab_top, $this->posxdiscount - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			if ($this->atleastonediscount) {
				$pdf->SetXY($this->posxdiscount - 1, $tab_top + 1);
				$pdf->MultiCell($this->postotalht - $this->posxdiscount + 1, 2, $outputlangs->transnoentities("ReductionShort"), '', 'C');
			}
		}
		if ($this->atleastonediscount) {
			$pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
		}
		if (empty($hidetop)) {
			$pdf->SetXY($this->postotalht - 1, $tab_top + 1);
			$pdf->MultiCell(30, 2, $outputlangs->transnoentities("TotalHTShort"), '', 'C');
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Propal		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param  Translate	$outputlangsbis	Object lang for output bis
	 *  @return	float|int                   Return topshift value
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $outputlangsbis = null)
	{
		global $conf, $langs;

		$ltrdirection = 'L';
		if ($outputlangs->trans("DIRECTION") == 'rtl') {
			$ltrdirection = 'R';
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "propal", "companies", "bills"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 100;

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
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound", $logo), 0, 'L');
					$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			} else {
				$text = $this->emetteur->name;
				$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, $ltrdirection);
			}
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("PdfCommercialProposalTitle");
		$title .= ' '.$outputlangs->convToOutputCharset($object->ref);
		if ($object->statut == $object::STATUS_DRAFT) {
			$pdf->SetTextColor(128, 0, 0);
			$title .= ' - '.$outputlangs->transnoentities("NotValidated");
		}
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('', 'B', $default_font_size);

		/*
		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref), '', 'R');
		*/

		$posy += 3;
		$pdf->SetFont('', '', $default_font_size - 2);

		if ($object->ref_client) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("RefCustomer")." : ".$outputlangs->convToOutputCharset($object->ref_client), '', 'R');
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

		if (getDolGlobalString('MAIN_PDF_DATE_TEXT')) {
			$displaydate = "daytext";
		} else {
			$displaydate = "day";
		}

		//$posy += 4;
		$posy = $pdf->getY();
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("DatePropal")." : ".dol_print_date($object->date, $displaydate, false, $outputlangs, true), '', 'R');

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("DateEndPropal")." : ".dol_print_date($object->fin_validite, $displaydate, false, $outputlangs, true), '', 'R');

		if (!getDolGlobalString('MAIN_PDF_HIDE_CUSTOMER_CODE') && $object->thirdparty->code_client) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
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
				$pdf->MultiCell(100, 3, $outputlangs->trans("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
			}
		}

		$posy += 2;

		$top_shift = 0;
		// Show list of linked objects
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY()) {
			$top_shift = $pdf->getY() - $current_y;
		}

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = '';
			// Add internal contact of object if defined
			$arrayidcontact = $object->getIdContact('internal', 'SALESREPFOLL');
			if (count($arrayidcontact) > 0) {
				$object->fetch_user($arrayidcontact[0]);
				$labelbeforecontactname = ($outputlangs->transnoentities("FromContactName") != 'FromContactName' ? $outputlangs->transnoentities("FromContactName") : $outputlangs->transnoentities("Name"));
				$carac_emetteur .= ($carac_emetteur ? "\n" : '').$labelbeforecontactname." ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_PHONE_AFTER_USER_CONTACT') || getDolGlobalInt('PDF_SHOW_EMAIL_AFTER_USER_CONTACT')) ? ' (' : '';
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_PHONE_AFTER_USER_CONTACT') && !empty($object->user->office_phone)) ? $object->user->office_phone : '';
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_PHONE_AFTER_USER_CONTACT') && getDolGlobalInt('PDF_SHOW_EMAIL_AFTER_USER_CONTACT')) ? ', ' : '';
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_EMAIL_AFTER_USER_CONTACT') && !empty($object->user->email)) ? $object->user->email : '';
				$carac_emetteur .= (getDolGlobalInt('PDF_SHOW_PHONE_AFTER_USER_CONTACT') || getDolGlobalInt('PDF_SHOW_EMAIL_AFTER_USER_CONTACT')) ? ')' : '';
				$carac_emetteur .= "\n";
			}

			$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = 42 + $top_shift;
			$posx = $this->marge_gauche;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->page_largeur - $this->marge_droite - 80;
			}
			$hautcadre = 40;

			// Show sender frame
			if (!getDolGlobalString('MAIN_PDF_NO_SENDER_FRAME')) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx, $posy - 5);
				$pdf->MultiCell(80, 5, $outputlangs->transnoentities("BillFrom"), 0, $ltrdirection);
				$pdf->SetXY($posx, $posy);
				$pdf->SetFillColor(230, 230, 230);
				$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
				$pdf->SetTextColor(0, 0, 60);
			}

			// Show company name
			if (!getDolGlobalString('MAIN_PDF_HIDE_SENDER_NAME')) {
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, $ltrdirection);
				$posy = $pdf->getY();
			}

			// Show sender information
			$pdf->SetXY($posx + 2, $posy);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, $ltrdirection);


			// If CUSTOMER contact defined, we use it
			$usecontact = false;
			$arrayidcontact = $object->getIdContact('external', 'CUSTOMER');
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

			$mode =  'target';
			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, ($usecontact ? $object->contact : ''), ($usecontact ? 1 : 0), $mode, $object);

			// Show recipient
			$widthrecbox = 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84; // To work with US executive format
			}
			$posy = 42 + $top_shift;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->marge_gauche;
			}

			// Show recipient frame
			if (!getDolGlobalString('MAIN_PDF_NO_RECIPENT_FRAME')) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx + 2, $posy - 5);
				$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo"), 0, $ltrdirection);
				$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
			}

			// Show recipient name
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, $ltrdirection);

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, $ltrdirection);
		}

		$pdf->SetTextColor(0, 0, 0);

		return $top_shift;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Propal		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		return pdf_pagefoot($pdf, $outputlangs, 'PROPOSAL_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur, $this->watermark);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Show area for the customer to sign
	 *
	 *	@param	TCPDF		$pdf            Object PDF
	 *	@param  Propal		$object         Object invoice
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Object langs
	 *	@return int							Position pour suite
	 */
	protected function _signature_area(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$tab_top = $posy + 4;
		$tab_hl = 4;

		$posx = 120;
		$largcol = ($this->page_largeur - $this->marge_droite - $posx);

		// Total HT
		$pdf->SetFillColor(255, 255, 255);
		$pdf->SetXY($posx, $tab_top);
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->MultiCell($largcol, $tab_hl, $outputlangs->transnoentities("ProposalCustomerSignature"), 0, 'L', 1);

		$pdf->SetXY($posx, $tab_top + $tab_hl);
		$pdf->MultiCell($largcol, $tab_hl * 3, '', 1, 'R');
		if (getDolGlobalString('MAIN_PDF_PROPAL_USE_ELECTRONIC_SIGNING')) {
			// Can be retrieve with getSignatureAppearanceArray()
			// Can be also detected by putting the mouse over the area when using evince pdf reader
			$pdf->addEmptySignatureAppearance($posx, $tab_top + $tab_hl, $largcol, $tab_hl * 3);
		}

		return ($tab_hl * 7);
	}
}
