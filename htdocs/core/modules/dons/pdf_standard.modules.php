<?php
/* Copyright (C) 2026		Philippe Grand			<philippe.grand@atoo-net.com>
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
 */

/**
 *	\file       htdocs/core/modules/dons/pdf_standard.modules.php
 *	\ingroup    don
 *	\brief      File of class to generate a PDF donation receipt with the standard model (any country)
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functionsnumtoword.lib.php';


/**
 *	Class to generate a PDF donation receipt with the standard model.
 *	This receipt is not linked to a national tax form, so it can be used in any country.
 */
class pdf_standard extends ModeleDon
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * @var float	Height reserved to output the footer (value include bottom margin)
	 */
	public $heightforfooter;

	/**
	 *  Constructor
	 *
	 *  @param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		$langs->loadLangs(array("main", "donations"));

		$this->db = $db;
		$this->name = "standard";
		$this->description = $langs->trans('DonationsReceiptModel').' - '.$langs->trans('DocumentModelStandardPDF');

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
		$this->option_multilang = 1; // Available in several languages

		if ($mysoc === null) {
			dol_syslog(get_class($this).'::__construct() Global $mysoc should not be null.'.getCallerInfoString(), LOG_ERR);
			return;
		}

		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
		}
	}


	/**
	 *  Return if a module can be used or not
	 *
	 *  @return	boolean		true if module can be used
	 */
	public function isEnabled()
	{
		return true;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write the donation receipt to a PDF file on disk
	 *
	 *  @param	Don			$don			Donation object
	 *  @param	Translate	$outputlangs	Lang object for output language
	 *  @param	string		$currency		Currency code
	 *  @return	int<-1,1>					>0 if OK, <=0 if KO
	 */
	public function write_file($don, $outputlangs, $currency = '')
	{
		// phpcs:enable
		global $user, $conf, $langs, $hookmanager;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "donations"));

		$currency = !empty($currency) ? $currency : $conf->currency;

		if (empty($conf->don->dir_output)) {
			$this->error = $langs->transnoentities("ErrorConstantNotDefined", "DON_OUTPUTDIR");
			return 0;
		}

		if (!is_object($don)) {
			$id = $don;
			$don = new Don($this->db);
			$don->fetch($id);
		}

		// Definition of $dir and $file
		if (!empty($don->specimen)) {
			$dir = $conf->don->dir_output;
			$file = $dir."/SPECIMEN.pdf";
		} else {
			$donref = dol_sanitizeFileName((string) $don->ref);
			$dir = $conf->don->dir_output."/".$donref;
			$file = $dir."/".$donref.".pdf";
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
		$parameters = array('file' => $file, 'object' => $don, 'outputlangs' => $outputlangs);
		global $action;
		$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $don, $action); // Note that $action and $object may have been modified by some hooks

		$pdf = pdf_getInstance($this->format);
		$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance
		$this->heightforfooter = $this->marge_basse + (!getDolGlobalString('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS') ? 12 : 22); // Height reserved to output the footer (value include bottom margin)
		$pdf->setAutoPageBreak(true, $this->heightforfooter);

		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputlangs));
		// Set path to the background PDF File
		$tplidx = null;
		if (getDolGlobalString('MAIN_ADD_PDF_BACKGROUND')) {
			$pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.getDolGlobalString('MAIN_ADD_PDF_BACKGROUND'));
			$tplidx = $pdf->importPage(1);
		}

		$pdf->Open();
		$pdf->SetDrawColor(128, 128, 128);

		$pdf->SetTitle($outputlangs->convToOutputCharset($outputlangs->transnoentities("DonationReceipt")." ".$don->ref));
		$pdf->SetSubject($outputlangs->transnoentities("DonationReceipt"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$pdf->SetKeyWords($outputlangs->convToOutputCharset($don->ref)." ".$outputlangs->transnoentities("DonationReceipt"));
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
			$pdf->SetCompression(false);
		}

		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

		$pdf->AddPage();
		if (!empty($tplidx)) {
			$pdf->useTemplate($tplidx);
		}

		$donor = $this->getDonorInfos($don, $outputlangs);

		$posy = $this->_pagehead($pdf, $don, $donor, $outputlangs);

		$posy = $this->_tableau_don($pdf, $don, $posy + 8, $outputlangs, $currency);

		// Public note
		$notetoshow = empty($don->note_public) ? '' : (string) $don->note_public;
		if ($notetoshow) {
			$substitutionarray = pdf_getSubstitutionArray($outputlangs, null, $don);
			complete_substitutions_array($substitutionarray, $outputlangs, $don);
			$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);
			$notetoshow = convertBackOfficeMediasLinksToPublicLinks($notetoshow);

			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->writeHTMLCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $this->marge_gauche, $posy + 6, dol_htmlentitiesbr($notetoshow), 0, 1);
			$posy = $pdf->GetY();
		}

		$this->_signature_area($pdf, $don, $posy + 10, $outputlangs);

		// Pagefoot on each page (a long public note may need several pages)
		$nbpages = $pdf->getNumPages();
		for ($i = 1; $i <= $nbpages; $i++) {
			$pdf->setPage($i);
			$pdf->setAutoPageBreak(false, 0); // After setPage() that restores it: the footer is written inside the bottom margin, it must not add a page
			$this->_pagefoot($pdf, $don, $outputlangs);
		}
		if (method_exists($pdf, 'AliasNbPages')) {
			$pdf->AliasNbPages();  // @phan-suppress-current-line PhanUndeclaredMethod
		}

		$pdf->Close();

		$pdf->Output($file, 'F');

		// Add pdfgeneration hook
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters = array('file' => $file, 'object' => $don, 'outputlangs' => $outputlangs);
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

		return 1;
	}


	/**
	 *  Show the amount and the details of the donation
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	Don			$don			Donation object
	 *  @param	float		$posy			Y position to start
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @param	string		$currency		Currency code
	 *  @return	float						Y position after the block
	 */
	protected function _tableau_don(&$pdf, $don, $posy, $outputlangs, $currency)
	{
		// phpcs:enable
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$widthlabel = 60;

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size);
		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell($width, 4, $outputlangs->transnoentities("DonationReceivedAmount"), 0, 'L');
		$posy = $pdf->GetY() + 4;

		// Amount, framed
		$amount = (float) price2num($don->amount, 'MT');
		$pdf->SetFillColor(230, 230, 230);
		$pdf->RoundedRect($this->marge_gauche, $posy, $width, 16, $this->corner_radius, '1234', 'F');
		$pdf->SetFont('', 'B', $default_font_size + 4);
		$pdf->SetXY($this->marge_gauche + 2, $posy + 2);
		$pdf->MultiCell($width - 4, 6, price($amount, 0, $outputlangs, 1, -1, 'MT', $currency), 0, 'C');
		$amountinletters = dol_convertToWord($amount, $outputlangs, $currency, true);
		if ($amountinletters) {
			$pdf->SetFont('', 'I', $default_font_size - 1);
			$pdf->SetXY($this->marge_gauche + 2, $posy + 9);
			$pdf->MultiCell($width - 4, 4, $outputlangs->convToOutputCharset($amountinletters), 0, 'C');
		}
		$posy += 22;

		// Details
		$details = array();
		$details[] = array($outputlangs->transnoentities("DonationDate"), dol_print_date($don->date, 'day', false, $outputlangs, true));
		if (!empty($don->mode_reglement_code)) {
			$details[] = array($outputlangs->transnoentities("PaymentMode"), $outputlangs->transnoentitiesnoconv("PaymentType".$don->mode_reglement_code) != "PaymentType".$don->mode_reglement_code ? $outputlangs->transnoentitiesnoconv("PaymentType".$don->mode_reglement_code) : (string) $don->mode_reglement);
		}
		if (isModEnabled('project') && !empty($don->fk_project) && $don->fetchProject() > 0 && is_object($don->project)) {
			$outputlangs->load("projects");
			$details[] = array($outputlangs->transnoentities("Project"), $don->project->ref.(empty($don->project->title) ? '' : ' - '.$don->project->title));
		}

		$pdf->SetFont('', '', $default_font_size - 1);
		foreach ($details as $detail) {
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell($widthlabel, 5, $detail[0], 0, 'L');
			$pdf->SetXY($this->marge_gauche + $widthlabel, $posy);
			$pdf->MultiCell($width - $widthlabel, 5, $outputlangs->convToOutputCharset($detail[1]), 0, 'L');
			$posy = max($posy + 5, $pdf->GetY());
		}

		return $posy;
	}


	/**
	 *  Show the place, date and signature area of the receipt
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	Don			$don			Donation object
	 *  @param	float		$posy			Y position to start
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the block
	 */
	protected function _signature_area(&$pdf, $don, $posy, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$widthbox = 80;
		$heightbox = 30;

		// Do not cut the signature area between two pages
		if ($posy + 10 + $heightbox > $this->page_hauteur - $this->heightforfooter) {
			$pdf->AddPage();
			$posy = $this->marge_haute;
		}

		$posx = $this->page_largeur - $this->marge_droite - $widthbox;
		$place = (string) $this->emetteur->town;
		$text = ($place ? $place.', ' : '').dol_print_date(dol_now(), 'day', false, $outputlangs, true);

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($widthbox, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		$pdf->SetXY($posx, $posy + 5);
		$pdf->MultiCell($widthbox, 4, $outputlangs->transnoentities("Signature"), 0, 'L');
		$pdf->RoundedRect($posx, $posy + 10, $widthbox, $heightbox, $this->corner_radius, '1234', 'D');

		return $posy + 10 + $heightbox;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	Don			$don			Donation object
	 *  @param	array{company:string,lastname:string,firstname:string,name:string,address:string,zip:string,town:string,country_code:string,country:string,idprof1:string,thirdparty:?Societe}	$donor	Identity of the donor
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the header
	 */
	protected function _pagehead(&$pdf, $don, $donor, $outputlangs)
	{
		// phpcs:enable
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$pdf->SetTextColor(0, 0, 60);
		$pdf->SetFont('', 'B', $default_font_size + 3);

		$w = 100;
		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - $w;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		if ($this->emetteur->logo) {
			$logodir = $conf->mycompany->dir_output;
			if (!empty($conf->mycompany->multidir_output[$don->entity ?? $conf->entity])) {
				$logodir = $conf->mycompany->multidir_output[$don->entity ?? $conf->entity];
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
			$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		}

		// Title, ref and date
		$pdf->SetFont('', 'B', $default_font_size + 3);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell($w, 4, $outputlangs->transnoentities("DonationReceipt"), 0, 'R');

		$posy += 6;
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($w, 4, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset((string) $don->ref), 0, 'R');

		$posy += 5;
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($w, 4, $outputlangs->transnoentities("Date")." : ".dol_print_date(dol_now(), 'day', false, $outputlangs, true), 0, 'R');

		// Beneficiary (the company in Dolibarr)
		$posy = 42;
		$hautcadre = 40;
		$widthrecbox = 90;
		if ($this->page_largeur < 210) {
			$widthrecbox = 84; // To work with US executive format
		}
		$posx = $this->marge_gauche;
		if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
			$posx = $this->page_largeur - $this->marge_droite - 82;
		}

		$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, null, null, 0, 'source', $don);

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($posx, $posy - 5);
		$pdf->MultiCell(82, 5, $outputlangs->transnoentities("DonationRecipient"), 0, 'L');
		$pdf->SetFillColor(230, 230, 230);
		$pdf->RoundedRect($posx, $posy, 82, $hautcadre, $this->corner_radius, '1234', 'F');
		$pdf->SetTextColor(0, 0, 60);

		$pdf->SetXY($posx + 2, $posy + 3);
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		$pdf->SetXY($posx + 2, $pdf->GetY());
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');

		// Donor
		$posx = $this->page_largeur - $this->marge_droite - $widthrecbox;
		if (getDolGlobalString('MAIN_INVERT_SENDER_RECIPIENT')) {
			$posx = $this->marge_gauche;
		}

		$carac_donor = '';
		if ($donor['address'] !== '') {
			$carac_donor .= $donor['address']."\n";
		}
		$carac_donor .= trim($donor['zip'].' '.$donor['town']);
		if ($donor['country_code'] !== '' && $donor['country_code'] != $this->emetteur->country_code) {
			$carac_donor .= "\n".$donor['country'];
		}

		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($posx + 2, $posy - 5);
		$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("Donor"), 0, 'L');
		$pdf->RoundedRect($posx, $posy, $widthrecbox, $hautcadre, $this->corner_radius, '1234', 'D');

		$pdf->SetXY($posx + 2, $posy + 3);
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($donor['name']), 0, 'L');
		$pdf->SetXY($posx + 2, $pdf->GetY());
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->MultiCell($widthrecbox - 2, 4, $outputlangs->convToOutputCharset($carac_donor), 0, 'L');

		$pdf->SetTextColor(0, 0, 0);

		return $posy + $hautcadre;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
	 *
	 *  @param	TCPDF		$pdf			PDF
	 *  @param	Don			$don			Donation object
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @param	int<0,1>	$hidefreetext	1=Hide free text
	 *  @return	int							Return height of bottom margin including footer text
	 */
	protected function _pagefoot(&$pdf, $don, $outputlangs, $hidefreetext = 0)
	{
		// phpcs:enable
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		return pdf_pagefoot($pdf, $outputlangs, 'DONATION_MESSAGE', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $don, $showdetails, $hidefreetext, $this->page_largeur);
	}
}
