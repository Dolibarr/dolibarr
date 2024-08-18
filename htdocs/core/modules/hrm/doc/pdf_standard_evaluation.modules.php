<?php
/* Copyright (C) 2015       Laurent Destailleur     <eldy@users.sourceforge.net>
 * Copyright (C) 2015       Alexandre Spangaro      <aspangaro@open-dsi.fr>
 * Copyright (C) 2016-2023  Philippe Grand          <philippe.grand@atoo-net.com>
 * Copyright (C) 2018-2024  Frédéric France         <frederic.france@free.fr>
 * Copyright (C) 2018       Francis Appels          <francis.appels@z-application.com>
 * Copyright (C) 2019       Markus Welters          <markus@welters.de>
 * Copyright (C) 2019       Rafael Ingenleuf        <ingenleuf@welters.de>
 * Copyright (C) 2020       Marc Guenneugues        <marc.guenneugues@simicar.fr>
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
 *	\file       htdocs/core/modules/hrm/doc/pdf_standard_evaluation.modules.php
 *	\ingroup    hrm
 *	\brief      File of class to generate evaluation report from standard model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/hrm/modules_evaluation.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

/**
 *	Class to generate Evaluation Pdf based on standard model
 */
class pdf_standard_evaluation extends ModelePDFEvaluation
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
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	public $posxpiece;
	public $posxskill;
	public $posxrankemp;
	public $posxrequiredrank;
	public $posxresult;
	public $postotalht;
	public $posxnotes;


	/**
	 *  Constructor
	 *
	 *  @param      DoliDB      $db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc, $user;
		// Translations
		$langs->loadLangs(array("main", "hrm"));

		$this->db = $db;
		$this->name = "standard";
		$this->description = $langs->trans('PDFStandardHrmEvaluation');
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

		$this->option_logo = 1; // Display logo
		$this->option_draft_watermark = 1; // Support add of a watermark on drafts

		// Get source company
		$this->emetteur = $mysoc;

		// Define position of columns
		$this->posxnotes = $this->marge_gauche + 1;

		$this->posxpiece = $this->marge_gauche + 1;
		$this->posxskill = $this->marge_gauche + 8;
		$this->posxrankemp = 129;
		$this->posxrequiredrank = 157;
		$this->posxresult = 185;

		if ($this->page_largeur < 210) { // To work with US executive format
			$this->posxrankemp -= 20;
			$this->posxrequiredrank -= 20;
			$this->posxresult -= 20;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Evaluation		$object				Object to generate
	 *  @param		Translate		$outputlangs		Lang output object
	 *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int<0,1>		$hidedetails		Do not show line details
	 *  @param		int<0,1>		$hidedesc			Do not show desc
	 *  @param		int<0,1>		$hideref			Do not show ref
	 *  @return		int<0,1>							1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $db, $hookmanager, $nblines;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "hrm"));

		$nblines = count($object->lines);

		if ($conf->hrm->dir_output) {
			// Definition of $dir and $file
			if ($object->specimen) {
				//$dir = $conf->hrm->dir_output;
				$dir = $conf->hrm->multidir_output[isset($object->entity) ? $object->entity : 1].'/evaluation';
				$file = $dir."/SPECIMEN.pdf";
			} else {
				$objectref = dol_sanitizeFileName($object->ref);
				//$dir = $conf->hrm->dir_output."/".$objectref;
				$dir = $conf->hrm->multidir_output[isset($object->entity) ? $object->entity : 1].'/evaluation'."/".$objectref;
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
				// Set nblines with the new command lines content after hook
				$nblines = count($object->lines);

				// Create pdf instance
				$pdf = pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
				$heightforinfotot = 0; // Height reserved to output the info and total part
				$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
				$heightforfooter = $this->marge_basse + 12; // Height reserved to output the footer (value include bottom margin)
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

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Evaluation"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Evaluation"));
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

				$tab_top = 65;
				$tab_top_newpage = (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 35 : 10);

				$tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

				// Show notes
				if (!empty($object->note_public)) {
					$tab_top = 65;

					$pdf->SetFont('', 'B', $default_font_size);
					$pdf->MultiCell(190, 4, $outputlangs->transnoentities("Notes") . ":", 0, 'L', 0, 0, 12, $tab_top);
					$tab_top += 4;
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxnotes + 1, $tab_top + 1, dol_htmlentitiesbr($object->note_public), 0, 1);
					$nexY = $pdf->GetY();
					$height_note = $nexY - $tab_top;

					// Rect takes a length in 3rd parameter
					$pdf->SetDrawColor(192, 192, 192);
					$pdf->Rect($this->marge_gauche, $tab_top - 1 - 4, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $height_note + 1 + 6);

					$tab_height -= $height_note;
					$tab_top = $nexY + 6;
				} else {
					$height_note = 0;
				}

				$iniY = $tab_top + 7;
				$nexY = $tab_top + 7;

				$pdf->setTopMargin($tab_top_newpage);
				// Loop on each lines
				$i = 0;
				while ($i < $nblines) {
					$pdf->SetFont('', '', $default_font_size - 2); // Into loop to work with multipage
					$pdf->SetTextColor(0, 0, 0);

					if (empty($showmorebeforepagebreak) && ($i !== ($nblines - 1))) {
						$pdf->setPageOrientation('', 1, $heightforfooter); // The only function to edit the bottom margin of current page to set it.
					} else {
						$pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
					}

					$pdf->setTopMargin($tab_top_newpage);

					$pageposbefore = $pdf->getPage();
					$curY = $nexY;
					$pdf->startTransaction();

					$this->printLine($pdf, $object, $i, $curY, $default_font_size, $outputlangs, $hidedetails);



					$pageposafter = $pdf->getPage();
					if ($pageposafter > $pageposbefore) {
						// There is a pagebreak
						$pdf->rollbackTransaction(true);

						$pageposafter = $pageposbefore;
						if (empty($showmorebeforepagebreak)) {
							$pdf->AddPage('', '', true);
							if (!empty($tplidx)) {
								$pdf->useTemplate($tplidx);
							}
							if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) {
								$this->_pagehead($pdf, $object, 0, $outputlangs);
							}
							$pdf->setPage($pageposafter + 1);
							$showmorebeforepagebreak = 1;
							$nexY = $tab_top_newpage;
							$nexY += ($pdf->getFontSize() * 1.3); // Add space between lines
							$pdf->SetFont('', '', $default_font_size - 2); // Into loop to work with multipage
							$pdf->SetTextColor(0, 0, 0);

							$pdf->setTopMargin($tab_top_newpage);
							continue;
						} else {
							$pdf->setPageOrientation('', 1, $heightforfooter);
							$showmorebeforepagebreak = 0;
						}

						$this->printLine($pdf, $object, $i, $curY, $default_font_size, $outputlangs, $hidedetails);
						$pageposafter = $pdf->getPage();
						$posyafter = $pdf->GetY();
						if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforinfotot))) {
							// There is no space left for total+free text
							if ($i == ($nblines - 1)) {
								// No more lines, and no space left to show total, so we create a new page
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
								$showmorebeforepagebreak = 1;
							} else {
								$showmorebeforepagebreak = 0;
							}
						}
					} else { // No pagebreak
						$pdf->commitTransaction();
					}
					$i++;

					//nexY
					$nexY = $pdf->GetY();
					$pdf->line($this->marge_gauche, $nexY + 2, $this->page_largeur - $this->marge_droite, $nexY + 2);
					$pageposafter = $pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.


					$nexY += ($pdf->getFontSize() * 1.3); // Add space between lines

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter) {
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0); // The only function to edit the bottom margin of current page to set it.
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

				$pdf->SetFont('', '', 10);


				// Page footer
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) {
					$pdf->AliasNbPages();
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
				$this->error = $langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		} else {
			$this->error = $langs->trans("ErrorConstantNotDefined", "HRM_OUTPUTDIR");
			return 0;
		}
	}

	/**
	 * @param   TCPDF       	$pdf                Object PDF
	 * @param	Evaluation		$object             Object to show
	 * @param   int         	$linenumber         line number
	 * @param   int         	$curY               current y position
	 * @param   int         	$default_font_size  default font size
	 * @param   Translate   	$outputlangs        Object lang for output
	 * @param	int				$hidedetails		Hide details (0=no, 1=yes, 2=just special lines)
	 * @return  void
	 */
	protected function printLine(&$pdf, $object, $linenumber, $curY, $default_font_size, $outputlangs, $hidedetails = 0)
	{
		global $conf;
		$objectligne = $object->lines[$linenumber];
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 0);

		// Result
		$pdf->SetXY($this->posxresult - 1, $curY);

		if ($objectligne->rankorder > $objectligne->required_rank) {
			// Teal Green
			$pdf->SetFillColor(0, 109, 91);
		} elseif ($objectligne->rankorder == $objectligne->required_rank) {
			// Seafoam Green
			$pdf->SetFillColor(159, 226, 191);
		} elseif ($objectligne->rankorder < $objectligne->required_rank) {
			// red
			$pdf->SetFillColor(205, 92, 92);
		}
		if ($objectligne->rankorder == 0 || $objectligne->required_rank == 0) {
			// No fill color
			$pdf->SetFillColor(255, 255, 255);
		}
		$result = (($objectligne->required_rank != 0 && $objectligne->rankorder != 0) ? $objectligne->rankorder . "/" . $objectligne->required_rank : "-");
		$pdf->MultiCell($this->posxresult - 210 - 0.8 - 4, 4, $result, 0, 'C', 1);


		// required Rank
		$pdf->SetXY($this->posxrequiredrank, $curY);
		$pdf->MultiCell($this->posxresult - $this->posxrequiredrank - 0.8, 4, (($objectligne->required_rank != 0 && $objectligne->rankorder != 0) ? $objectligne->required_rank : "-"), 0, 'C');

		// Rank Employee
		$pdf->SetXY($this->posxrankemp, $curY);
		$pdf->MultiCell($this->posxrequiredrank - $this->posxrankemp - 0.8, 4, (($objectligne->rankorder != 0) ? $objectligne->rankorder : "-"), 0, 'C');

		// Skill
		$skill = new Skill($this->db);
		$skill->fetch($objectligne->fk_skill);
		$pdf->SetXY($this->posxskill, $curY);
		$comment = $skill->label;

		if (!empty($skill->description)) {
			$comment .= '<br>' . $outputlangs->trans("Description").': '.$skill->description;
		}
		$pdf->writeHTMLCell($this->posxrankemp - $this->posxskill - 0.8, 4, $this->posxskill - 1, $curY, $comment, 0, 1);



		// Line num
		$pdf->SetXY($this->posxpiece, $curY);
		$pdf->writeHTMLCell($this->posxskill - $this->posxpiece - 0.8, 3, $this->posxpiece - 1, $curY, $linenumber + 1, 0, 1, 0, 0, 'C');
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF			$pdf     		Object PDF
	 *  @param  Evaluation		$object     	Object to show
	 *  @param  int	    		$showaddress    0=no, 1=yes
	 *  @param  Translate		$outputlangs	Object lang for output
	 *  @return	float|int                   	Return topshift value
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		// global $conf, $langs, $hookmanager;
		global $user, $langs, $conf, $mysoc, $db, $hookmanager;

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "trips", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);


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
			$text = $this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('', 'B', $default_font_size + 4);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $posx, 6, $outputlangs->transnoentities("Eval"), 0, 'R');

		$pdf->SetFont('', '', $default_font_size - 1);

		// Ref complete
		$posy += 8;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $posx, 3, $outputlangs->transnoentities("Ref")." : ".$object->ref, '', 'R');

		// Date evaluation
		$posy += 5;
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $posx, 3, $outputlangs->transnoentities("DateEval")." : ".dol_print_date($object->date_eval, "day", false, $outputlangs), '', 'R');


		if ($showaddress) {
			// Sender properties
			$carac_emetteur = '';

			// employee information
			$employee = new User($this->db);
			$employee->fetch($object->fk_user);
			$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities('Employee').' : '.$outputlangs->convToOutputCharset(ucfirst($employee->firstname) . ' ' . strtoupper($employee->lastname));

			// Position
			include_once DOL_DOCUMENT_ROOT.'/hrm/class/job.class.php';
			$job = new Job($db);
			$job->fetch($object->fk_job);
			$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities('JobProfile').' : '.$outputlangs->convToOutputCharset($job->label);

			/*$carac_emetteur .= "\n";
			if ($object->description) {
				$carac_emetteur .= ($carac_emetteur ? "\n" : '').$outputlangs->transnoentities("Phone")." : ".$outputlangs->convToOutputCharset($object->description);
			}*/


			// Show sender
			$posy = 40;
			$posx = $this->marge_gauche;
			$hautcadre = 20;
			if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
				$posx = 118;
			}

			// Show sender frame
			/*$pdf->SetTextColor(0, 0, 0);
			$pdf->SetFont('', 'B', $default_font_size - 2);
			$pdf->SetXY($posx, $posy - 5);
			$pdf->MultiCell(190, 5, $outputlangs->transnoentities("Information"), '', 'L');*/
			$pdf->SetXY($posx, $posy);
			$pdf->SetFillColor(224, 224, 224);
			$pdf->MultiCell(190, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0, 0, 60);

			// Show sender information
			$pdf->SetXY($posx + 2, $posy + 3);
			$pdf->SetFont('', 'B', $default_font_size);
			$pdf->MultiCell(190, 4, $outputlangs->convToOutputCharset($object->label), 0, 'L');
			$pdf->SetXY($posx + 2, $posy + 8);
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(190, 4, $carac_emetteur, 0, 'L');
		}

		return 0;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show table for lines
	 *
	 *   @param     TCPDF		$pdf     		Object PDF
	 *   @param		int			$tab_top		Tab top
	 *   @param		int			$tab_height		Tab height
	 *   @param		int			$nexY			next y
	 *   @param		Translate	$outputlangs	Output langs
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

		$pdf->SetDrawColor(128, 128, 128);

		// Rect takes a length in 3rd parameter
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height);
		// line prend une position y en 3eme param
		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur - $this->marge_droite, $tab_top + 5);
		}

		$pdf->SetFont('', '', 8);

		// Line no
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxpiece - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxskill - $this->posxpiece - 0.8, 1, '', '', 'C');
		}

		// Skill
		$pdf->line($this->posxskill - 1, $tab_top, $this->posxskill - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxskill - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxrankemp - $this->posxskill - 0.8, 1, $outputlangs->transnoentities("Skill"), '', 'L');
		}

		// Employee Rank
		if (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT')) {
			$pdf->line($this->posxrankemp - 1, $tab_top, $this->posxrankemp - 1, $tab_top + $tab_height);
			if (empty($hidetop)) {
				$pdf->SetXY($this->posxrankemp - 0.8, $tab_top + 1);
				$pdf->MultiCell($this->posxrequiredrank - $this->posxrankemp - 1, 2, $outputlangs->transnoentities("EmployeeRankShort"), '', 'C');
			}
		}

		// Required Rank
		$pdf->line($this->posxrequiredrank - 1, $tab_top, $this->posxrequiredrank - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxrequiredrank - 0.8, $tab_top + 1);
			$pdf->MultiCell($this->posxresult - $this->posxrequiredrank - 1, 2, $outputlangs->transnoentities("RequiredRankShort"), '', 'C');
		}

		// Result
		$pdf->line($this->posxresult - 1, $tab_top, $this->posxresult - 1, $tab_top + $tab_height);
		if (empty($hidetop)) {
			$pdf->SetXY($this->posxresult - 0.8, $tab_top + 1);
			$pdf->MultiCell($this->postotalht - $this->posxresult - 1, 2, $outputlangs->transnoentities("Result"), '', 'C');
		}

		$pdf->SetTextColor(0, 0, 0);
	}



	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param  TCPDF			$pdf     			PDF
	 *  @param  Evaluation		$object				Object to show
	 *  @param  Translate		$outputlangs		Object lang for output
	 *  @param  int				$hidefreetext		1=Hide free text
	 *  @return int									Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		return pdf_pagefoot($pdf, $outputlangs, '', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
