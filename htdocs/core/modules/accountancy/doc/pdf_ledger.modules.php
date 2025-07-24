<?php
/* Copyright (C) 2005       Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2005-2012  Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012  Regis Houssin				<regis.houssin@inodbox.com>
 * Copyright (C) 2014-2015  Marcos García				<marcosgdf@gmail.com>
 * Copyright (C) 2018-2025  Frédéric France				<frederic.france@free.fr>
 * Copyright (C) 2023 		Charlene Benke				<charlene@patas-monkey.com>
 * Copyright (C) 2024-2025	MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2024	    Nick Fragoulis
 * Copyright (C) 2024		Alexandre Spangaro			<alexandre@inovea-conseil.com>
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
 *	\file       htdocs/core/modules/accountancy/doc/pdf_ledger.modules.php
 *	\ingroup    accountancy
 *	\brief      Class file allowing accountancy ledger template generation
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/accountancy/modules_accountancy.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/accounting.lib.php';

/**
 *	Class to build sending documents with model Espadon
 */
class pdf_ledger extends ModelePdfAccountancy
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

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct(DoliDB $db)
	{
		global $langs, $mysoc;

		$this->name = "ledger";
		$this->description = $langs->trans("PDFAccountancyLedgerDescription");
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
		$this->option_logo = 1; // Display logo
		$this->option_draft_watermark = 1; // Support add of a watermark on drafts
		$this->watermark = '';

		if ($mysoc === null) {
			dol_syslog(get_class($this).'::__construct() Global $mysoc should not be null.'. getCallerInfoString(), LOG_ERR);
			return;
		}

		// Get source company
		$this->emetteur = $mysoc;
		if (empty($this->emetteur->country_code)) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
		}

		$this->tabTitleHeight = 5; // default height

		parent::__construct($db);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Function to build pdf onto disk
	 *
	 * @param BookKeeping $object Object shipping to generate (or id if old method)
	 * @param Translate $outputlangs Lang output object
	 * @param string $srctemplatepath Source template path
	 * @param bool $directDownload Send generated file to browser
	 * @return        int<-1,1>                        1 if OK, <=0 if KO
	 */
	public function write_file(BookKeeping $object, Translate $outputlangs, string $srctemplatepath = '', bool $directDownload = true)
	{
		// phpcs:enable
		global $user, $conf, $langs, $hookmanager;

		$hidedesc = $hidedetails = $hideref = 0;

		$object->fetch_thirdparty();

		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "orders", "companies", "other", "accountancy", "compta"));

		global $outputlangsbis;
		$outputlangsbis = null;
		if (getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE') && $outputlangs->defaultlang != getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE')) {
			$outputlangsbis = new Translate('', $conf);
			$outputlangsbis->setDefaultLang(getDolGlobalString('PDF_USE_ALSO_LANGUAGE_CODE'));
			$outputlangsbis->loadLangs(array("main", "bills", "orders", "products", "dict", "companies", "other", "propal", "deliveries", "sendings", "productbatch", "compta"));
		}

		$nblines = count($object->lines);

		if (!$conf->accounting->multidir_output[$conf->entity]) {
			$this->error = $langs->transnoentities("ErrorAccountancyDirectoryNotDefined");
			return 0;
		}

		// Definition of $dir and $file
		$dir = $conf->accounting->multidir_output[$conf->entity]."/export";
		if ($object->specimen) {
			$file = "{$dir}/SPECIMEN_{$this->name}.pdf";
		} else {
			$expref = dol_sanitizeFileName($object->ref);
			$date = date('YmdHis', dol_now());
			$file = "{$dir}/{$this->name}_{$date}.pdf";
		}

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}

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
		$default_font_size = pdf_getPDFFontSize($outputlangs) - 2;
		$heightforinfotot = 8; // Height reserved to output the info and total part
		$heightforfreetext = getDolGlobalInt('MAIN_PDF_FREETEXT_HEIGHT', 5); // Height reserved to output the free text on last page
		$heightforfooter = $this->marge_basse + 14; // Height reserved to output the footer (value include bottom margin)
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
		$pdf->SetSubject($outputlangs->transnoentities("AccountancyLedger"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("AccountancyLedger"));
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

		$tab_top = 40;	// position of top tab
		$tab_top_newpage = (getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD') ? 10 : $tab_top);

		$tab_height = $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext;

		$this->posxdesc = $this->marge_gauche + 1;

		// Displays notes. Here we are still on code executed only for the first page.
		$notetoshow = empty($object->note_public) ? '' : $object->note_public;

		// Use new auto column system
		$this->prepareArrayColumnField($object, $outputlangs);

		// Table simulation to know the height of the title line
		$pdf->startTransaction();
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->rollbackTransaction(true);


		$curY = $nexY = $tab_top + $this->tabTitleHeight;

		// Loop on each lines
		$pageposbeforeprintlines = $pdf->getPage();
		$pagenb = $pageposbeforeprintlines;

		// Knowing how many month our period covers
		$fromYear = date('Y', $this->fromDate);
		$fromMonth = date('m', $this->fromDate);
		$toYear = date('Y', $this->toDate);
		$toMonth = date('m', $this->toDate);
		$nbMonths = (((int) $toYear - (int) $fromYear) * 12) + ((int) $toMonth - (int) $fromMonth) + 1;
		$datePlusOneMonth = strtotime("-1 month", $this->fromDate);
		$dates = [];
		for ($i = 0; $i  < $nbMonths; $i++) {
			$datePlusOneMonth = strtotime("+1 month", $datePlusOneMonth);
			$dates[$datePlusOneMonth] = dol_print_date($datePlusOneMonth, "%B %Y");
		}

		$account = '';
		$accountDebit = $accountCredit = $totalDebit = $totalCredit = 0;
		for ($i = 0; $i < $nblines; $i++) {
			// Show total line / title line when account has changed
			if (empty($account) || $account != $object->lines[$i]->numero_compte) {
				$accountingAccount = new AccountingAccount($this->db);
				$accountingAccount->fetch(0, $object->lines[$i]->numero_compte);

				// Add the subtotal line
				if (!empty($account)) {
					$this->addTotalLine(
						$pdf,
						$curY,
						$nexY,
						$default_font_size,
						$langs->trans('Total'),
						$tab_top_newpage,
						$accountDebit,
						$accountCredit
					);
				}

				// Add the title line
				$this->addTitleLine(
					$pdf,
					$curY,
					$nexY,
					$default_font_size,
					'piece_num',
					$langs->trans('AccountAccountingShort') . ' ' . length_accountg($accountingAccount->ref) . ' - ' . $accountingAccount->label,
					$tab_top_newpage
				);

				$account = $object->lines[$i]->numero_compte;
				$accountDebit = $accountCredit = 0;
			}

			$accountDebit += $object->lines[$i]->debit;
			$accountCredit += $object->lines[$i]->credit;
			$totalDebit += $object->lines[$i]->debit;
			$totalCredit += $object->lines[$i]->credit;

			$curY = $nexY;
			$pdf->SetFont('', '', $default_font_size - 1); // Into loop to work with multipage
			$pdf->SetTextColor(0, 0, 0);

			$pdf->setTopMargin($tab_top_newpage);
			$pdf->setPageOrientation('', true, $heightforfooter + $heightforfreetext + $heightforinfotot); // The only function to edit the bottom margin of current page to set it.
			$pageposbefore = $pdf->getPage();

			$showpricebeforepagebreak = 1;
			$heightforsignature = 0;

			// Column used for testing page change
			// No check on column status, this column is mandatory
			$pdf->startTransaction();

			$this->printStdColumnContent($pdf, $curY, 'label', $object->lines[$i]->label_operation);

			$pageposafter = $pdf->getPage();
			if ($pageposafter > $pageposbefore) {	// There is a pagebreak
				$pdf->rollbackTransaction(true);

				$pdf->AddPage('', '', true);
				$pdf->setPage($pageposafter);
				$curY = $tab_top_newpage + $this->tabTitleHeight;
				$this->printStdColumnContent($pdf, $curY, 'label', $object->lines[$i]->label_operation);

				$pageposafter = $pdf->getPage();
				$posyafter = $pdf->GetY();
				//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
				if ($posyafter > ($this->page_hauteur - ($heightforfooter + $heightforfreetext + $heightforsignature + $heightforinfotot))) {	// There is no space left for total+free text
					if ($i == ($nblines - 1)) {	// No more lines, and no space left to show total, so we create a new page
						$pdf->AddPage('', '', true);
						if (!empty($tplidx)) {
							$pdf->useTemplate($tplidx);
						}
						//if (!getDolGlobalInt('MAIN_PDF_DONOTREPEAT_HEAD')) $this->_pagehead($pdf, $object, 0, $outputlangs);
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
			$nexY = max($pdf->GetY(), $nexY);

			$nexY = $pdf->GetY();
			$pageposafter = $pdf->getPage();

			$pdf->setPage($pageposbefore);
			$pdf->setTopMargin($this->marge_haute);
			$pdf->setPageOrientation('', true, 0); // The only function to edit the bottom margin of current page to set it.

			// We suppose that a too long description is moved completely on next page
			if ($pageposafter > $pageposbefore) {
				$pdf->setPage($pageposafter);
				$curY = $tab_top_newpage + $this->tabTitleHeight;
			}

			$pdf->SetFont('', '', $default_font_size - 1); // We reposition the default font

			// # of line
			if ($this->getColumnStatus('position')) {
				$this->printStdColumnContent($pdf, $curY, 'position', (string) ($i + 1));
			}

			if ($this->getColumnStatus('date')) {
				$this->printStdColumnContent($pdf, $curY, 'date', dol_print_date($object->lines[$i]->doc_date, 'day'));
				$nexY = max($pdf->GetY(), $nexY);
			}

			if ($this->getColumnStatus('journal')) {
				$this->printStdColumnContent($pdf, $curY, 'journal', $object->lines[$i]->code_journal);
				$nexY = max($pdf->GetY(), $nexY);
			}

			if ($this->getColumnStatus('piece_num')) {
				$this->printStdColumnContent($pdf, $curY, 'piece_num', (string) $object->lines[$i]->piece_num);
				$nexY = max($pdf->GetY(), $nexY);
			}

			if ($this->getColumnStatus('lettering_code')) {
				$this->printStdColumnContent($pdf, $curY, 'lettering_code', $object->lines[$i]->lettering_code ?? '');
				$nexY = max($pdf->GetY(), $nexY);
			}

			if ($this->getColumnStatus('debit')) {
				$this->printStdColumnContent($pdf, $curY, 'debit', price(price2num($object->lines[$i]->debit, 'MT')));
				$nexY = max($pdf->GetY(), $nexY);
			}

			if ($this->getColumnStatus('credit')) {
				$this->printStdColumnContent($pdf, $curY, 'credit', price(price2num($object->lines[$i]->credit, 'MT')));
				$nexY = max($pdf->GetY(), $nexY);
			}

			if ($this->getColumnStatus('balance')) {
				$solde = $object->lines[$i]->credit - $object->lines[$i]->debit;
				$soldeText = price(price2num(abs($solde), 'MT')) . ($solde >= 0 ? ' C' : ' D');
				$this->printStdColumnContent($pdf, $curY, 'balance', $soldeText);
				$nexY = max($pdf->GetY(), $nexY);
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
			$reshook = $hookmanager->executeHooks('printPDFline', $parameters, $this);

			// Add line
			if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES') && $i < ($nblines - 1)) {
				$this->addDashLine($pdf, $pageposafter, $nexY);
			}

			// Detect if some page were added automatically and output _tableau for past pages
			while ($pagenb < $pageposafter) {
				$pdf->setPage($pagenb);
				if ($pagenb == $pageposbeforeprintlines) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 0, 1);
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
			if (isset($object->lines[$i + 1]->pagebreak) && $object->lines[$i + 1]->pagebreak) {  // @phan-suppress-current-line PhanUndeclaredProperty
				if ($pagenb == 1) {
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
				} else {
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 0, 1);
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

		// Add total line for last account
		if (!empty($object->lines)) {
			// Add line
			if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES')) {
				$this->addDashLine($pdf, $pdf->getPage(), $nexY);
			}
			$this->addTotalLine(
				$pdf,
				$curY,
				$nexY,
				$default_font_size,
				$langs->trans('Total'),
				$tab_top_newpage,
				$accountDebit,
				$accountCredit
			);
		}

		// Add grand total line
		if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES')) {
			$this->addDashLine($pdf, $pdf->getPage(), $nexY);
		}
		$this->addTotalLine(
			$pdf,
			$curY,
			$nexY,
			$default_font_size,
			$langs->transnoentities('GrandTotals'),
			$tab_top_newpage,
			$totalDebit,
			$totalCredit,
		);



		// Show square
		if ($pagenb == 1) {
			$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
		} else {
			$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
		}

		// Pagefoot
		$this->_pagefoot($pdf, $object, $outputlangs);
		if (method_exists($pdf, 'AliasNbPages')) {
			$pdf->AliasNbPages();  // @phan-suppress-current-line PhanUndeclaredMethod
		}

		$pdf->Close();

		$pdf->Output($file, $directDownload ? 'D' : 'F');

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
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Show table for lines
	 *
	 * @param	TCPDF		$pdf     		Object PDF
	 * @param	float|int	$tab_top		Top position of table
	 * @param	float|int	$tab_height		Height of table (rectangle)
	 * @param	float		$nexY			Y
	 * @param	Translate	$outputlangs	Langs object
	 * @param	int			$hidetop		Hide top bar of array
	 * @param	int			$hidebottom		Hide bottom bar of array
	 * @param	string		$currency		Currency code
	 * @param	Translate	$outputlangsbis	Langs object bis
	 * @return	void
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
			if (getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')) {
				$pdf->RoundedRect($this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_droite - $this->marge_gauche, $this->tabTitleHeight, $this->corner_radius, '1001', 'F', array(), explode(',', getDolGlobalString('MAIN_PDF_TITLE_BACKGROUND_COLOR')));
			}
		}

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $default_font_size - 1);

		// Output Rect
		$this->printRoundedRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $tab_height, $this->corner_radius, $hidetop, $hidebottom, 'D'); // Rect takes a length in 3rd parameter and 4th parameter

		$pdf->SetFont('', 'B', $default_font_size - 1);
		$this->pdfTabTitles($pdf, $tab_top, $tab_height, $outputlangs, $hidetop);
		$pdf->SetFont('', '', $default_font_size - 1);


		if (empty($hidetop)) {
			$pdf->line($this->marge_gauche, $tab_top + $this->tabTitleHeight, $this->page_largeur - $this->marge_droite, $tab_top + $this->tabTitleHeight); // line takes a position y in 2nd parameter and 4th parameter
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Show top header of page.
	 *
	 * @param	TCPDF		$pdf     		Object PDF
	 * @param  BookKeeping	$object     	Object to show
	 * @param  int<0,1>  	$showaddress    0=no, 1=yes
	 * @param  Translate	$outputlangs	Object lang for output
	 * @return	float|int                   Return topshift value
	 */
	protected function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf, $langs;

		$ltrdirection = 'L';
		if ($outputlangs->trans("DIRECTION") == 'rtl') $ltrdirection = 'R';

		// Load traductions files required by page
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);


		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 110;
		$posy = $this->marge_haute;
		$posx = $this->marge_gauche;
		$hautcadre = 20;
		$widthrecbox = $this->page_largeur - $this->marge_droite - $this->marge_gauche;
		$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

		$posx = $this->page_largeur - $this->marge_droite - $w;
		$nexY = $posy;

		// Name of soc
		$pdf->SetXY($this->marge_gauche + 2, $posy + 2);
		$text = $this->emetteur->name;
		$pdf->MultiCell($w / 3, 4, $outputlangs->convToOutputCharset($text), 0, $ltrdirection);
		$nexY = max($pdf->GetY(), $nexY);

		// Date of document
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($this->marge_gauche + 2, $nexY);
		$pdf->SetTextColor(0, 0, 60);
		$textDateNow = $outputlangs->transnoentities("PrintDate");
		$pdf->MultiCell($w / 3, 3, $textDateNow . " : " . date('d/m/Y', dol_now()), '', 'L');
		$nexY = max($pdf->GetY(), $nexY);

		// Page title
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx - 2, $posy + 2);
		$pdf->SetTextColor(0, 0, 60);
		$title = $outputlangs->transnoentities("PdfLedgerTitle");
		$pdf->MultiCell($w / 3, 3, $title, 0, 'C');
		$nexY = max($pdf->GetY(), $nexY);

		// Date From To
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->SetXY(($posx + ($w / 3) * 2) - 2, $posy + 2);
		$pdf->SetTextColor(0, 0, 60);

		$fromDate = dol_print_date($this->fromDate, 'day');
		$toDate = dol_print_date($this->toDate, 'day');
		$textDate = $outputlangs->transnoentities("From") . " " . $fromDate . " " . $outputlangs->transnoentities("To") . " " . $toDate;
		$pdf->MultiCell($w / 3, 4, $textDate, 0, 'R');
		$nexY = max($pdf->GetY(), $nexY);

		$pdf->SetTextColor(0, 0, 0);
		return $nexY;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Show footer of page. Need this->emetteur object
	 *
	 * @param	TCPDF		$pdf     			PDF
	 * @param	BookKeeping	$object				Object to show
	 * @param	Translate	$outputlangs		Object lang for output
	 * @param	int			$hidefreetext		1=Hide free text
	 * @return	int								Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		return pdf_pagefoot($pdf, $outputlangs, 'SHIPPING_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext, $this->page_largeur, $this->watermark);
	}

	/**
	 * Define Array Column Field
	 *
	 * @param	BookKeeping	   $object    	    common object
	 * @param	Translate	   $outputlangs     langs
	 * @param	int			   $hidedetails		Do not show line details
	 * @param	int			   $hidedesc		Do not show desc
	 * @param	int			   $hideref			Do not show ref
	 * @return	void
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

		$rank = 0; // do not use negative rank
		$this->cols['position'] = [
			'rank' => $rank,
			'width' => 10,
			'status' => (bool) getDolGlobalInt('PDF_ACCOUNTANCY_LEDGER_ADD_POSITION'),
			'title' => [
				'textkey' => '#', // use lang key is useful in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => [0.5, 0.5, 0.5, 0.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'content' => [
				'align' => 'C',
				'padding' => [1, 0.5, 1, 1.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
		];

		$rank += 10; // do not use negative rank
		$this->cols['date'] = [
			'rank' => $rank,
			'width' => 18, // only for desc
			'status' => true,
			'title' => [
				'textkey' => 'Date', // use lang key is useful in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => [0.5, 0.5, 0.5, 0.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'content' => [
				'align' => 'L',
				'padding' => [1, 0.5, 1, 1.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
		];

		$rank += 10;
		$this->cols['journal'] = [
			'rank' => $rank,
			'width' => 15,
			'status' => true,
			'title' => [
				'textkey' => 'Journal', // use lang key is useful in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => [0.5, 0.5, 0.5, 0.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'content' => [
				'align' => 'L',
				'padding' => [1, 0.5, 1, 1.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'border-left' => true, // add left line separator
		];

		$rank += 10;
		$this->cols['piece_num'] = [
			'rank' => $rank,
			'width' => 15,
			'status' => true,
			'title' => [
				'textkey' => 'Piece', // use lang key is useful in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => [0.5, 0.5, 0.5, 0.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'content' => [
				'align' => 'L',
				'padding' => [1, 0.5, 1, 1.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'border-left' => true, // add left line separator
		];

		$rank += 10;
		$this->cols['label'] = [
			'rank' => $rank,
			'width' => false,
			'status' => true,
			'title' => [
				'textkey' => 'Label', // use lang key is useful in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => [0.5, 0.5, 0.5, 0.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'content' => [
				'align' => 'L',
				'padding' => [1, 0.5, 1, 1.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'border-left' => true, // add left line separator
		];


		$rank += 10;
		$this->cols['lettering_code'] = [
			'rank' => $rank,
			'width' => 14,
			'status' => true,
			'title' => [
				'textkey' => 'Lettering', // use lang key is useful in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => [0.5, 0.5, 0.5, 0.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'content' => [
				'align' => 'R',
				'padding' => [1, 0.5, 1, 1.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'border-left' => true, // add left line separator
		];

		$rank += 10;
		$this->cols['debit'] = [
			'rank' => $rank,
			'width' => 15,
			'status' => true,
			'title' => [
				'textkey' => 'Debit', // use lang key is useful in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => [0.5, 0.5, 0.5, 0.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'content' => [
				'align' => 'R',
				'padding' => [1, 0.5, 1, 1.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'border-left' => true, // add left line separator
		];

		$rank += 10;
		$this->cols['credit'] = array(
			'rank' => $rank,
			'width' => 15,
			'status' => true,
			'title' => array(
				'textkey' => 'Credit', // use lang key is useful in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => array(0.5, 0.5, 0.5, 0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'content' => array(
				'align' => 'R',
				'padding' => array(1, 0.5, 1, 1.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			),
			'border-left' => true, // add left line separator
		);

		$rank += 10;
		$this->cols['balance'] = [
			'rank' => $rank,
			'width' => 20,
			'status' => true,
			'title' => [
				'textkey' => 'Balance', // use lang key is useful in somme case with module
				'align' => 'C',
				// 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
				// 'label' => ' ', // the final label
				'padding' => [0.5, 0.5, 0.5, 0.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'content' => [
				'align' => 'R',
				'padding' => [1, 0.5, 1, 1.5], // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
			],
			'border-left' => true, // add left line separator
		];

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

	/**
	 * Add a total line to pdf
	 *
	 * @param TCPDF 			$pdf 				TCPDF object
	 * @param float 			$curY 				Current line Y
	 * @param float 			$nexY 				Next line Y
	 * @param int|float 		$default_font_size 	Default font size
	 * @param string 			$label 				Line label
	 * @param int|float 		$tab_top_newpage	Table top
	 * @param int|float|string 	$debit				Debit
	 * @param int|float|string 	$credit				Credit
	 * @param bool 				$uppercase			Apply uppercase ?
	 * @return void
	 */
	protected function addTotalLine(TCPDF $pdf, &$curY, &$nexY, $default_font_size, string $label, $tab_top_newpage, $debit, $credit, bool $uppercase = true)
	{
		$curY = $nexY;
		$pageposbefore = $pdf->getPage();
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->startTransaction();

		if ($uppercase) {
			$label = mb_strtoupper($label);
		}
		$this->printStdColumnContent($pdf, $curY, 'label', $label);

		$pageposafter = $pdf->getPage();
		if ($pageposafter > $pageposbefore) {    // There is a pagebreak
			$pdf->rollbackTransaction(true);

			$pdf->AddPage('', '', true);
			$pdf->setPage($pageposafter);
			$curY = $tab_top_newpage + $this->tabTitleHeight;
			$this->printStdColumnContent($pdf, $curY, 'label', $label);
		}

		$nexY = $pdf->GetY();

		if ($this->getColumnStatus('debit')) {
			$this->printStdColumnContent($pdf, $curY, 'debit', price(price2num($debit, 'MT')));
			$nexY = max($pdf->GetY(), $nexY);
		}

		if ($this->getColumnStatus('credit')) {
			$this->printStdColumnContent($pdf, $curY, 'credit', price(price2num($credit, 'MT')));
			$nexY = max($pdf->GetY(), $nexY);
		}

		if ($this->getColumnStatus('balance')) {
			$solde = $credit - $debit;
			$soldeText = price(price2num(abs($solde), 'MT')) . ($solde >= 0 ? ' C' : ' D');
			$this->printStdColumnContent($pdf, $curY, 'balance', $soldeText);
			$nexY = max($pdf->GetY(), $nexY);
		}

		if (getDolGlobalString('MAIN_PDF_DASH_BETWEEN_LINES')) {
			$this->addDashLine($pdf, $pageposafter, $nexY);
		}
	}
}
