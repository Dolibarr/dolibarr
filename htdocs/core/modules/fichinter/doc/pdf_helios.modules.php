<?php
/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2026		Frédéric France				<frederic.france@free.fr>
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
 *	\file       htdocs/core/modules/fichinter/doc/pdf_helios.modules.php
 *	\ingroup    ficheinter
 *	\brief      Class to build intervention documents with model Helios (column based, supports the subtotals module)
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/fichinter/modules_fichinter.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';


/**
 *	Class to build intervention documents with model Helios.
 *
 *	Same layout family as "soleil" but built on the auto-column system
 *	(defineColumnField / printColDescContent / printStdColumnContent), so it
 *	renders the subtotals module lines through pdf_render_subtotals().
 */
class pdf_helios extends ModelePDFFicheinter
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
	 * @var string Dolibarr version of the loaded document
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

		$this->db = $db;
		$this->name = 'helios';
		$this->description = $langs->trans("DocumentModelStandardPDF");
		$this->update_main_doc_field = 1;

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
		$this->corner_radius = getDolGlobalInt('MAIN_PDF_FRAME_CORNER_RADIUS', 0);
		$this->option_logo = 1; // Display logo
		$this->option_tva = 0;
		$this->option_modereg = 0;
		$this->option_condreg = 0;
		$this->option_multilang = 1; // Available in several languages
		$this->option_draft_watermark = 1; // Support add of a watermark on drafts
		$this->watermark = '';

		$this->tabTitleHeight = 5; // default height

		// Define position of columns
		$this->posxdesc = $this->marge_gauche + 1;

		if ($mysoc === null) {
			dol_syslog(get_class($this).'::__construct() Global $mysoc should not be null.'.getCallerInfoString(), LOG_ERR);
			return;
		}

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if not defined
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Fichinter		$object				Object to generate
	 *  @param		Translate		$outputlangs		Lang output object
	 *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int<0,1>		$hidedetails		Do not show line details
	 *  @param		int<0,1>		$hidedesc			Do not show desc
	 *  @param		int<0,1>		$hideref			Do not show ref
	 *  @return		int<-1,1>							1=OK,<=0 => KO
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

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "interventions", "dict", "companies", "compta"));

		// Show Draft Watermark
		if ($object->status == $object::STATUS_DRAFT && (getDolGlobalString('FICHINTER_DRAFT_WATERMARK'))) {
			$this->watermark = getDolGlobalString('FICHINTER_DRAFT_WATERMARK');
		}

		if ($conf->ficheinter->dir_output) {
			$object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen) {
				$dir = $conf->ficheinter->dir_output;
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->ficheinter->dir_output."/".$objectref;
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

				$nblines = is_array($object->lines) ? count($object->lines) : 0;

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$heightforinfotot = 40; // Height reserved to output the total line + the name/signature boxes
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 8; // Height reserved to output the footer (value include bottom margin)
				if (getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS')) {
					$heightforfooter += 6;
				}
				$pdf->setAutoPageBreak(true, 0);

				if (class_exists('TCPDF')) {
					$pdf->setPrintHeader(false);
					$pdf->setPrintFooter(false);
				}
				$pdf->SetFont(pdf_getPDFFont($outputlangs));
				// Set path to the background PDF File
				if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
					$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
					$tplidx = $pdf->importPage(1);
				}

				$pdf->Open();
				$pagenb = 0;
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("InterventionCard"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getAnonymisableFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("InterventionCard"));
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
				$pdf->SetTextColor(0, 0, 0);

				$tab_top = 80 + $this->marge_haute;
				$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 32 + $this->marge_haute : $this->marge_haute);

				$tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

				// Display notes
				$notetoshow = empty($object->note_public) ? '' : $object->note_public;
				$extranote = $this->getExtrafieldsInHtml($object, $outputlangs);
				if (!empty($extranote)) {
					$notetoshow = dol_concatdesc($notetoshow, $extranote);
				}
				if ($notetoshow) {
					$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions((string) $notetoshow, $substitutionarray, $outputlangs);
					$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

					$tab_top -= 2;

					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc - 1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->RoundedRect($this->marge_gauche, $tab_top - 1, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 2, $this->corner_radius, '1234', 'D');

					$tab_height -= $height_note;
					$tab_top = $nexY + 6;
				} else {
					$height_note = 0;
				}

				// Use new auto column system
				$this->prepareArrayColumnField($object, $outputlangs, $hidedetails, $hidedesc, $hideref);

				// Table simulation to know the height of the title line
				$pdf->startTransaction();
				$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs);
				$pdf->rollbackTransaction(true);

				$nexY = $tab_top + $this->tabTitleHeight;

				// Loop on each lines
				$pageposbeforeprintlines = $pdf->getPage();
				$pagenb = $pageposbeforeprintlines;
				for ($i = 0; $i < $nblines; $i++) {
					$objectligne = $object->lines[$i];

					$curY = $nexY;

					$issubtotalline = (defined('SUBTOTALS_SPECIAL_CODE') && $objectligne->special_code == SUBTOTALS_SPECIAL_CODE);
					$sub_options = is_array($objectligne->extraparams) ? ($objectligne->extraparams["subtotal"] ?? array()) : array();

					// Fichinter::fetch_lines() already stores the subtotal depth in ->qty, but be defensive if the
					// line was built elsewhere: for fichinter the depth lives in the duree/duration field.
					if ($issubtotalline && empty($objectligne->qty)) {
						$objectligne->qty = (int) $objectligne->duration;
					}

					if (($curY + 6) > ($this->page_hauteur - $heightforfooter)
						|| (isset($sub_options['titleforcepagebreak']) && !($pdf->getNumPages() == 1 && $curY == $tab_top + $this->tabTitleHeight))) {
						$pdf->AddPage();
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						$pdf->setPage($pdf->getNumPages());
						$nexY = $curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					$pageposbefore = $pdf->getPage();

					// Description of line
					if ($this->getColumnStatus('desc')) {
						if ($issubtotalline) {
							$bg_color = colorStringToArray(getDolGlobalString("SUBTOTAL_BACK_COLOR_LEVEL_".abs((int) $objectligne->qty), 'ffffff'));
							pdf_render_subtotals($pdf, $this, $curY, $object, $i, $outputlangs, $hideref, $hidedesc, $bg_color, true, true);
						} else {
							$pdf->startTransaction();

							$this->printColDescContent($pdf, $curY, 'desc', $object, $i, $outputlangs, $hideref, $hidedesc);

							$pageposafter = $pdf->getPage();
							if ($pageposafter > $pageposbefore) {	// There is a pagebreak
								$pdf->rollbackTransaction(true);

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
								}
							} else { // No pagebreak
								$pdf->commitTransaction();
							}
						}
					}

					$nexY = $pdf->GetY();
					$pageposafter = $pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', true, 0); // The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore) {
						$pdf->setPage($pageposafter);
						$curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

					// # of line
					if ($this->getColumnStatus('position')) {
						$this->printStdColumnContent($pdf, $curY, 'position', (string) ($i + 1));
					}

					// Date and duration are not printed for the subtotals module lines
					if (!$issubtotalline) {
						if ($this->getColumnStatus('date')) {
							$dateformat = getDolGlobalString('FICHINTER_DATE_WITHOUT_HOUR') ? 'day' : 'dayhour';
							$this->printStdColumnContent($pdf, $curY, 'date', dol_print_date($objectligne->datei, $dateformat, false, $outputlangs, true));
						}
						if ($this->getColumnStatus('duration') && $objectligne->duration > 0) {
							$this->printStdColumnContent($pdf, $curY, 'duration', convertSecondToTime((int) $objectligne->duration));
						}

						// Extrafields
						if (!empty($objectligne->array_options)) {
							foreach ($objectligne->array_options as $extrafieldColKey => $extrafieldValue) {
								if ($this->getColumnStatus($extrafieldColKey)) {
									$extrafieldValue = $this->getExtrafieldContent($objectligne, $extrafieldColKey, $outputlangs);
									$this->printStdColumnContent($pdf, $curY, $extrafieldColKey, $extrafieldValue);
								}
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
					$hookmanager->executeHooks('printPDFline', $parameters, $this);

					// Add a line separator between lines
					if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblines - 1)) {
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash' => '1,1', 'color' => array(80, 80, 80)));
						$pdf->line($this->marge_gauche, $nexY, $this->page_largeur - $this->marge_droite, $nexY);
						$pdf->SetLineStyle(array('dash' => 0));
					}

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						if ($pagenb == $pageposbeforeprintlines) {
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						} else {
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', true, 0); // The only function to edit the bottom margin of current page to set it.
						if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
							$this->_pagehead($pdf, $object, 0, $outputlangs);
						}
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
					}
				}

				// Show square
				if ($pagenb == $pageposbeforeprintlines) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab = $this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

				// Display total area (total duration of the intervention)
				$this->_tableau_tot($pdf, $object, $bottomlasttab, $outputlangs);

				// Signatures + Pagefoot
				$this->_tableau_signature($pdf, $object, $bottomlasttab + 6, $outputlangs);

				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();  // @phan-suppress-current-line PhanUndeclaredMethod
				}

				$pdf->Close();
				$pdf->Output($file, 'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
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

				return 1;
			} else {
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "FICHEINTER_OUTPUTDIR");
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show total line (total duration of the intervention)
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param	Fichinter	$object			Object to show
	 *  @param	float		$posy			Y start position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position for the suite
	 */
	protected function _tableau_tot(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 0);

		if ($this->getColumnStatus('desc')) {
			$this->printStdColumnContent($pdf, $posy, 'desc', $outputlangs->transnoentities("TotalDuration"));
		}
		if ($this->getColumnStatus('duration') && !empty($object->duration)) {
			$totaltime = convertSecondToTime((int) $object->duration, 'all', getDolGlobalInt('MAIN_DURATION_OF_WORKDAY', 28800));
			$this->printStdColumnContent($pdf, $posy, 'duration', $totaltime);
		}

		$pdf->SetFont('', '', $default_font_size - 1);

		return $posy + 4;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Show name / signature boxes for the internal and external contacts
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param	Fichinter	$object			Object to show
	 *  @param	float		$posy			Y start position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	protected function _tableau_signature(&$pdf, $object, $posy, $outputlangs)
	{
		// phpcs:enable
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 0);

		$employee_name = '';
		$arrayidcontact = $object->getIdContact('internal', 'INTERVENING');
		if (count($arrayidcontact) > 0) {
			$object->fetch_user($arrayidcontact[0]);
			$employee_name = $object->user->getFullName($outputlangs);
		}

		$boxwidth = ($this->page_largeur - $this->marge_gauche - $this->marge_droite - 10) / 2;

		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell($boxwidth, 5, $outputlangs->transnoentities("NameAndSignatureOfInternalContact"), 0, 'L', false);
		$pdf->SetXY($this->marge_gauche, $posy + 5);
		$pdf->MultiCell($boxwidth, 20, $employee_name, 1, 'L');

		$pdf->SetXY($this->marge_gauche + $boxwidth + 10, $posy);
		$pdf->MultiCell($boxwidth, 5, $outputlangs->transnoentities("NameAndSignatureOfExternalContact"), 0, 'L', false);
		$pdf->SetXY($this->marge_gauche + $boxwidth + 10, $posy + 5);
		$pdf->MultiCell($boxwidth, 20, '', 1);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		float|int	$tab_top		Top position of table
	 *   @param		float|int	$tab_height		Height of table (rectangle)
	 *   @param		float		$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
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

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);

		if (empty($hidetop) && getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
			$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->tabTitleHeight, $this->corner_radius, '1001', 'F', array(), explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRoundedRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $this->corner_radius, $hidetop, $hidebottom, 'D');

		$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);

		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top + $this->tabTitleHeight, $this->page_largeur - $this->marge_droite, $tab_top + $this->tabTitleHeight);
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Fichinter	$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	float|int                   Return topshift value
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $langs;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "interventions"));

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$posx = $this->page_largeur - $this->marge_droite - 100;
		$posy = $this->marge_haute;

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
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("InterventionCard"), '', 'R');

		$pdf->SetFont('', 'B', $default_font_size + 2);

		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy += 1;
		$pdf->SetFont('', '', $default_font_size);

		$posy += 4;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Date")." : ".dol_print_date($object->datec, "day", false, $outputlangs, true), '', 'R');

		if (!empty($object->ref_client)) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("RefCustomer")." : ".dol_trunc($outputlangs->convToOutputCharset($object->ref_client), 65), '', 'R');
		}

		if (!getDolGlobalString('MAIN_PDF_HIDE_CUSTOMER_CODE') && $object->thirdparty->code_client) {
			$posy += 4;
			$pdf->SetXY($posx, $posy);
			$pdf->SetTextColor(0, 0, 60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : ".$outputlangs->transnoentities((string) $object->thirdparty->code_client), '', 'R');
		}

		if ($showaddress) {
			// Sender properties
			$carac_emetteur = '';
			$arrayidcontact = $object->getIdContact('internal', 'INTERREPFOLL');
			if (count($arrayidcontact) > 0) {
				$object->fetch_user($arrayidcontact[0]);
				$labelbeforecontactname = ($outputlangs->transnoentities("FromContactName") != 'FromContactName' ? $outputlangs->transnoentities("FromContactName") : $outputlangs->transnoentities("Name"));
				$carac_emetteur .= $labelbeforecontactname.": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
			}

			$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy = 32 + $this->marge_haute;
			$posx = $this->marge_gauche;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->page_largeur - $this->marge_droite - 80;
			}
			$hautcadre = 40;

			// Show sender frame
			if (!getDolGlobalString('MAIN_PDF_NO_SENDER_FRAME')) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx, $posy);
				$pdf->SetFillColor(230, 230, 230);
				$pdf->RoundedRect($posx, $posy, 82, $hautcadre, $this->corner_radius, '1234', 'F');
			}

			// Show sender name
			if (!getDolGlobalString('MAIN_PDF_HIDE_SENDER_NAME')) {
				$pdf->SetXY($posx + 2, $posy + 3);
				$pdf->SetTextColor(0, 0, 60);
				$pdf->SetFont('', 'B', $default_font_size);
				$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
				$posy = $pdf->getY();
			}

			// Show sender information
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->SetXY($posx + 2, $posy);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');

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

			$carac_client = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, (isset($object->contact) ? $object->contact : ''), ($usecontact ? 1 : 0), 'target', $object);

			// Show recipient
			$widthrecbox = 100;
			if ($this->page_largeur < 210) {
				$widthrecbox = 84; // To work with US executive format
			}
			$posy = 32 + $this->marge_haute;
			$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = $this->marge_gauche;
			}

			// Show recipient frame
			if (!getDolGlobalString('MAIN_PDF_NO_RECIPENT_FRAME')) {
				$pdf->SetTextColor(0, 0, 0);
				$pdf->SetFont('', '', $default_font_size - 2);
				$pdf->SetXY($posx + 2, $posy - 5);
				$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, $this->corner_radius, '1234', 'D');
				$pdf->SetTextColor(0, 0, 0);
			}

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

		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Fichinter	$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		return pdf_pagefoot($pdf, $outputlangs, 'FICHINTER_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur, $this->watermark);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Define Array Column Field
	 *
	 *  @param	CommonObject	$object			common object
	 *  @param	Translate		$outputlangs	langs
	 *  @param	int<0,1>	$hidedetails	Do not show line details
	 *  @param	int<0,1>	$hidedesc		Do not show desc
	 *  @param	int<0,1>	$hideref		Do not show ref
	 *  @return	void
	 */
	public function defineColumnField($object, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $hookmanager;

		// Default field style for content
		$this->defaultContentsFieldsStyle = array(
			'align' => 'R', // R,C,L
			'padding' => array(1, 0.5, 1, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		// Default field style for title
		$this->defaultTitlesFieldsStyle = array(
			'align' => 'C', // R,C,L
			'padding' => array(0.5, 0, 0.5, 0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
		);

		$rank = 0; // do not use negative rank
		$this->cols['position'] = array(
			'rank' => $rank,
			'width' => 10,
			'status' => getDolGlobalInt('PDF_ADD_POSITION') ? true : false,
			'title' => array(
				'textkey' => '#',
				'align' => 'C',
				'padding' => array(0.5, 0.5, 0.5, 0.5),
			),
			'content' => array(
				'align' => 'C',
				'padding' => array(1, 0.5, 1, 1.5),
			),
		);

		$rank += 10;
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
				'padding' => array(1, 0.5, 1, 1.5),
			),
		);

		$rank += 10;
		$this->cols['date'] = array(
			'rank' => $rank,
			'width' => 40, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Date',
			),
			'border-left' => true,
			'content' => array(
				'align' => 'C',
			),
		);

		$rank += 10;
		$this->cols['duration'] = array(
			'rank' => $rank,
			'width' => 25, // in mm
			'status' => true,
			'title' => array(
				'textkey' => 'Duration',
			),
			'border-left' => true,
			'content' => array(
				'align' => 'C',
			),
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
