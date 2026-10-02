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
 *	\file       htdocs/core/modules/dons/pdf_cerfafr_11580.modules.php
 *	\ingroup    don
 *	\brief      File of class to generate the French tax receipt for donations of private individuals (form 2041-RD, Cerfa 11580*05)
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/dons/modules_don.php';
require_once DOL_DOCUMENT_ROOT.'/don/class/don.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functionsnumtoword.lib.php';


/**
 *	Class to generate the French tax receipt for donations of private individuals,
 *	articles 200 and 978 of the French general tax code (form 2041-RD, Cerfa 11580*05).
 *	The receipt is a legal document that exists only in French, so it is always generated in French.
 */
class pdf_cerfafr_11580 extends ModeleDon
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * @var float	Height reserved at the bottom of the page (value include bottom margin)
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
		$this->name = "cerfafr_11580";
		$this->description = $langs->trans('DonationsReceiptModel').' - fr_FR - Cerfa 11580*05 (2041-RD)';

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
		$this->option_multilang = 0; // The form exists only in French

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


	/**
	 *  Return the categories of beneficiary organizations listed on the form, with their legal label.
	 *  Categories of group 'oig' are the sub-cases of "general interest work or organisation".
	 *
	 *  @return	array<string,array{label:string,group:string}>		Categories, by code
	 */
	public static function getOrganismTypes()
	{
		return array(
			'oig_association' => array('group' => 'oig', 'label' => "Association loi 1901"),
			'oig_rup' => array('group' => 'oig', 'label' => "Association ou fondation reconnue d'utilité publique par décret en date du %s publié au Journal officiel du %s ou association située dans le département de la Moselle, du Bas-Rhin ou du Haut-Rhin dont la mission a été reconnue d'utilité publique par arrêté en date du %s"),
			'oig_university_foundation' => array('group' => 'oig', 'label' => "Fondation universitaire ou fondation partenariale mentionnées respectivement aux articles L.719-12 et L.719-13 du code de l'éducation"),
			'oig_company_foundation' => array('group' => 'oig', 'label' => "Fondation d'entreprise"),
			'oig_museum' => array('group' => 'oig', 'label' => "Musée de France"),
			'oig_food_aid' => array('group' => 'oig', 'label' => "Organisme sans but lucratif fournissant gratuitement une aide alimentaire ou des soins médicaux à des personnes en difficultés ou favorisant leur logement"),
			'oig_forest' => array('group' => 'oig', 'label' => "Communes, syndicats intercommunaux ou mixtes de gestion forestière, groupements syndicaux forestiers visés au f ter du 1 de l'article 200 du CGI"),
			'oig_other' => array('group' => 'oig', 'label' => "Autres (précisez) : %s"),
			'alsace_moselle_worship' => array('group' => '', 'label' => "Association cultuelle et établissement public reconnus d'Alsace-Moselle"),
			'endowment_fund' => array('group' => '', 'label' => "Fonds de dotation"),
			'press_pluralism' => array('group' => '', 'label' => "Association d'intérêt général exerçant des actions concrètes en faveur du pluralisme de la presse, par la prise de participations minoritaires, l'octroi de subventions ou encore de prêts bonifiés à des entreprises de presse"),
			'higher_education' => array('group' => '', 'label' => "Etablissement d'enseignement supérieur ou d'enseignement artistique public ou privé, d'intérêt général, à but non lucratif"),
			'consular_school' => array('group' => '', 'label' => "Etablissement d'enseignement supérieur consulaire prévu à l'article L.711-17 du code de commerce"),
			'sme_support' => array('group' => '', 'label' => "Organisme agréé ayant pour objectif exclusif d'accorder des aides financières ou de fournir des prestations d'accompagnement à des petites et moyennes entreprises"),
			'performing_arts' => array('group' => '', 'label' => "Organisme public ou privé dont la gestion est désintéressée et qui a pour activité principale la présentation au public d'oeuvres dramatiques, lyriques, musicales, chorégraphiques, cinématographiques, audiovisuelles et de cirque ou l'organisation d'expositions d'art contemporain"),
			'heritage_foundation' => array('group' => '', 'label' => "Fondation du patrimoine ou fondation ou association reconnue d'utilité publique qui subventionnent des travaux sur des monuments historiques dans le cadre de conventions prévues à l'article L. 143-2-1 et L 143-15 du code du patrimoine. Le cas échéant, date de l'agrément par le ministre chargé du budget : %s"),
			'cultural_property' => array('group' => '', 'label' => "Organisme ayant pour objet la sauvegarde, contre les effets d'un conflit armé, des biens culturels mentionnés à l'article 1er de la Convention du 14 mai 1954 pour la protection des biens culturels en cas de conflit armé"),
			'research' => array('group' => '', 'label' => "Etablissement de recherche public ou privé, d'intérêt général, à but non lucratif"),
			'insertion_company' => array('group' => '', 'label' => "Entreprise d'insertion ou entreprise de travail temporaire d'insertion (articles L. 5132-5 et L. 5132-6 du code du travail)"),
			'intermediate_association' => array('group' => '', 'label' => "Association intermédiaire (article L.5132-7 du code du travail)"),
			'insertion_workshop' => array('group' => '', 'label' => "Ateliers et chantiers d'insertion (article L.5132-15 du code du travail)"),
			'adapted_company' => array('group' => '', 'label' => "Entreprises adaptées (article L.5213-13 du code du travail)"),
			'anr' => array('group' => '', 'label' => "Agence nationale de la recherche (ANR)"),
			'geiq' => array('group' => '', 'label' => "Groupement d'employeurs pour l'insertion et la qualification mentionné à l'article L.1253-1 du code du travail"),
			'business_creation' => array('group' => '', 'label' => "Association reconnue d'utilité publique de financement et d'accompagnement de la création et de la reprise d'entreprises"),
			'eu_organism' => array('group' => '', 'label' => "Organisme établi dans un Etat membre de l'Union européenne autre que la France (ou en Norvège, Islande ou Liechtenstein) poursuivant des objectifs et présentant des caractéristiques similaires aux organismes précités. Le cas échéant, date de l'agrément %s"),
		);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Write the donation receipt to a PDF file on disk
	 *
	 *  @param	Don			$don			Donation object
	 *  @param	Translate	$outputlangs	Lang object for output language (not used, the form exists only in French)
	 *  @param	string		$currency		Currency code
	 *  @return	int<-1,1>					>0 if OK, <=0 if KO
	 */
	public function write_file($don, $outputlangs, $currency = '')
	{
		// phpcs:enable
		global $user, $conf, $langs, $hookmanager;

		// The form exists only in French
		$outputlangs = new Translate("", $conf);
		$outputlangs->setDefaultLang('fr_FR');
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "donations"));

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

		$donor = $this->getDonorInfos($don, $outputlangs);

		// The form 11580*05 is only for private individuals, companies must receive the form 16216*01
		$this->warnings = array();
		if (empty($don->specimen) && $this->isCompanyDonor($donor)) {
			$this->warnings[] = $langs->trans("DonationCerfa11580ForPrivateIndividualsOnly");
		}
		if ($conf->currency != 'EUR') {
			dol_syslog(get_class($this)."::write_file The form expects amounts in euros, but the main currency is ".$conf->currency, LOG_WARNING);
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
		$this->heightforfooter = $this->marge_basse + 6;
		$pdf->setAutoPageBreak(true, $this->heightforfooter);

		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputlangs));

		$pdf->Open();
		$pdf->SetDrawColor(0, 0, 0);

		$pdf->SetTitle($outputlangs->convToOutputCharset("Reçu fiscal 2041-RD ".$don->ref));
		$pdf->SetSubject($outputlangs->convToOutputCharset("Reçu des dons et versements effectués par les particuliers"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$pdf->SetKeyWords($outputlangs->convToOutputCharset($don->ref)." 2041-RD Cerfa 11580*05");
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
			$pdf->SetCompression(false);
		}

		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right

		$pdf->AddPage();

		$posy = $this->_pagehead($pdf, $don, $outputlangs);
		$posy = $this->showBeneficiary($pdf, $posy + 4, $outputlangs);
		$posy = $this->showDonor($pdf, $donor, $posy + 4, $outputlangs);
		$posy = $this->showDonation($pdf, $don, $posy + 4, $outputlangs);
		$this->showFootnotes($pdf, $posy + 4, $outputlangs);

		// Page numbers, only when the receipt needs several pages
		$nbpages = $pdf->getNumPages();
		if ($nbpages > 1) {
			for ($i = 1; $i <= $nbpages; $i++) {
				$pdf->setPage($i);
				$pdf->setAutoPageBreak(false, 0);
				$pdf->SetFont('', '', 7);
				$pdf->SetXY($this->marge_gauche, $this->page_hauteur - $this->marge_basse - 3);
				$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 3, $i.' / '.$nbpages, 0, 'R');
			}
		}

		$pdf->Close();

		$pdf->Output($file, 'F');

		// Add pdfgeneration hook
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters = array('file' => $file, 'object' => $don, 'outputlangs' => $outputlangs);
		global $action;
		$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		$this->warnings = array_merge($this->warnings, $hookmanager->warnings);
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
	 *  Return if the donor looks like a company rather than a private individual
	 *
	 *  @param	array{company:string,lastname:string,firstname:string,name:string,address:string,zip:string,town:string,country_code:string,country:string,idprof1:string,thirdparty:?Societe}	$donor	Identity of the donor
	 *  @return	bool
	 */
	protected function isCompanyDonor($donor)
	{
		if (is_object($donor['thirdparty'])) {
			if (!empty($donor['thirdparty']->typent_code)) {
				return ($donor['thirdparty']->typent_code != 'TE_PRIVATE');
			}
			return ($donor['idprof1'] !== '');
		}

		return ($donor['company'] !== '' && $donor['lastname'] === '' && $donor['firstname'] === '');
	}


	/**
	 *  Return a date stored as YYYY-MM-DD by the setup page in the format of the form
	 *
	 *  @param	string	$value		Date stored in setup
	 *  @return	string				Date formatted as dd/mm/yyyy, or dots to fill by hand
	 */
	protected function formatSetupDate($value)
	{
		if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $reg)) {
			return $reg[3].'/'.$reg[2].'/'.$reg[1];
		}
		return '....../....../......';
	}


	/**
	 *  Return the label of a category of organization, with its dates completed from setup
	 *
	 *  @param	string	$code		Code of the category
	 *  @return	string				Label
	 */
	protected function getOrganismTypeLabel($code)
	{
		$types = self::getOrganismTypes();
		if (empty($types[$code])) {
			return '';
		}

		$label = $types[$code]['label'];
		if ($code == 'oig_rup') {
			$label = sprintf($label, $this->formatSetupDate(getDolGlobalString('DONATION_CERFA_RUP_DECREE_DATE')), $this->formatSetupDate(getDolGlobalString('DONATION_CERFA_RUP_JO_DATE')), $this->formatSetupDate(getDolGlobalString('DONATION_CERFA_RUP_ORDER_DATE')));
		} elseif ($code == 'oig_other') {
			$label = sprintf($label, getDolGlobalString('DONATION_CERFA_ORGANISM_OTHER', '....................'));
		} elseif ($code == 'heritage_foundation' || $code == 'eu_organism') {
			$label = sprintf($label, $this->formatSetupDate(getDolGlobalString('DONATION_CERFA_APPROVAL_DATE')));
		}

		return $label;
	}


	/**
	 *  Show a check box followed by its label
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	float		$posx			X position
	 *  @param	float		$posy			Y position
	 *  @param	float		$width			Width of the box and its label
	 *  @param	bool		$checked		True to tick the box
	 *  @param	string		$label			Label
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the label
	 */
	protected function checkbox(&$pdf, $posx, $posy, $width, $checked, $label, $outputlangs)
	{
		$size = 2.8;
		$pdf->Rect($posx, $posy + 0.6, $size, $size);
		if ($checked) {
			$pdf->Line($posx + 0.4, $posy + 1, $posx + $size - 0.4, $posy + 0.2 + $size);
			$pdf->Line($posx + 0.4, $posy + 0.2 + $size, $posx + $size - 0.4, $posy + 1);
		}
		$pdf->SetXY($posx + $size + 1.5, $posy);
		$pdf->MultiCell($width - $size - 1.5, 4, $outputlangs->convToOutputCharset($label), 0, 'L');

		return $pdf->GetY();
	}


	/**
	 *  Show a row of check boxes
	 *
	 *  @param	TCPDF					$pdf			Object PDF
	 *  @param	float					$posy			Y position
	 *  @param	array<string,string>	$boxes			Labels of boxes, by code
	 *  @param	string					$checkedcode	Code of the ticked box
	 *  @param	Translate				$outputlangs	Object lang for output
	 *  @param	int						$nbcolumns		Number of boxes per line
	 *  @return	float									Y position after the row
	 */
	protected function checkboxRow(&$pdf, $posy, $boxes, $checkedcode, $outputlangs, $nbcolumns = 0)
	{
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		if ($nbcolumns <= 0) {
			$nbcolumns = count($boxes);
		}
		$widthbox = $width / $nbcolumns;

		$i = 0;
		$maxy = $posy;
		foreach ($boxes as $code => $label) {
			if ($i > 0 && $i % $nbcolumns == 0) {
				$posy = $maxy + 1;
			}
			$posx = $this->marge_gauche + ($i % $nbcolumns) * $widthbox;
			$maxy = max($maxy, $this->checkbox($pdf, $posx, $posy, $widthbox - 2, ($code === $checkedcode), $label, $outputlangs));
			$i++;
		}

		return $maxy;
	}


	/**
	 *  Show the title bar of a section of the form
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	float		$posy			Y position
	 *  @param	string		$title			Title
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the title
	 */
	protected function sectionTitle(&$pdf, $posy, $title, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

		$pdf->SetFillColor(220, 228, 240);
		$pdf->SetTextColor(0, 0, 100);
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell($width, 7, $outputlangs->convToOutputCharset($title), 1, 'C', true, 1, '', '', true, 0, false, true, 7, 'M');
		$pdf->SetTextColor(0, 0, 0);

		return $posy + 8;
	}


	/**
	 *  Show a field of the form: a label in bold followed by its value
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	float		$posx			X position
	 *  @param	float		$posy			Y position
	 *  @param	float		$width			Width
	 *  @param	string		$label			Label
	 *  @param	string		$value			Value
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the field
	 */
	protected function showField(&$pdf, $posx, $posy, $width, $label, $value, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetXY($posx, $posy);
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 100);
		$widthlabel = $pdf->GetStringWidth($outputlangs->convToOutputCharset($label)) + 4; // Include the padding of the cell
		$pdf->MultiCell($widthlabel, 4, $outputlangs->convToOutputCharset($label), 0, 'L');
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx + $widthlabel, $posy);
		$pdf->MultiCell($width - $widthlabel, 4, $outputlangs->convToOutputCharset($value), 0, 'L');

		return max($posy + 5, $pdf->GetY() + 1);
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page: title of the form, number of the form and number of the receipt
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	Don			$don			Donation object
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the header
	 */
	protected function _pagehead(&$pdf, $don, $outputlangs)
	{
		// phpcs:enable
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$posy = $this->marge_haute;

		// Logo of the beneficiary
		if ($this->emetteur->logo) {
			$logodir = $conf->mycompany->dir_output;
			if (!empty($conf->mycompany->multidir_output[$don->entity ?? $conf->entity])) {
				$logodir = $conf->mycompany->multidir_output[$don->entity ?? $conf->entity];
			}
			$logo = $logodir.'/logos/thumbs/'.$this->emetteur->logo_small;
			if (getDolGlobalInt('MAIN_PDF_USE_LARGE_LOGO')) {
				$logo = $logodir.'/logos/'.$this->emetteur->logo;
			}
			if (is_readable($logo)) {
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, 16); // width=0 (auto)
			}
		}

		// Title of the form
		$pdf->SetTextColor(0, 0, 100);
		$pdf->SetFont('', 'B', $default_font_size + 2);
		$pdf->SetXY($this->marge_gauche + 45, $posy);
		$pdf->MultiCell($this->page_largeur - $this->marge_gauche - $this->marge_droite - 90, 5, $outputlangs->convToOutputCharset("Reçu des dons et versements effectués par les particuliers au titre des articles 200 et 978 du code général des impôts"), 0, 'C');
		$posyafter = $pdf->GetY();

		// Number of the form
		$posx = $this->page_largeur - $this->marge_droite - 40;
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell(40, 4, '2041-RD', 0, 'R');
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY($posx, $posy + 4);
		$pdf->MultiCell(40, 4, $outputlangs->convToOutputCharset('N° 11580*05'), 0, 'R');

		// Number of the receipt
		$pdf->SetTextColor(0, 0, 0);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx, $posy + 10);
		$pdf->MultiCell(40, 4, $outputlangs->convToOutputCharset("Numéro d'ordre du reçu"), 0, 'R');
		$pdf->Rect($posx, $posy + 14.5, 40, 6);
		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->SetXY($posx, $posy + 15.5);
		$pdf->MultiCell(40, 4, $outputlangs->convToOutputCharset((string) $don->ref), 0, 'C');

		return max($posyafter, $posy + 21);
	}


	/**
	 *  Show the block of the beneficiary organization
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	float		$posy			Y position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the block
	 */
	protected function showBeneficiary(&$pdf, $posy, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$posx = $this->marge_gauche;

		$posy = $this->sectionTitle($pdf, $posy, "Organisme bénéficiaire des dons et versements", $outputlangs);

		$ids = array();
		if (!empty($this->emetteur->idprof1)) {
			$ids[] = 'SIREN '.$this->emetteur->idprof1;
		}
		if (getDolGlobalString('DONATION_ORGANISM_RNA')) {
			$ids[] = 'RNA '.getDolGlobalString('DONATION_ORGANISM_RNA');
		}
		$country = '';
		if (!empty($this->emetteur->country_code)) {
			$country = $outputlangs->transnoentitiesnoconv("Country".$this->emetteur->country_code);
		}

		$posy = $this->showField($pdf, $posx, $posy, $width, "Nom ou dénomination :", (string) $this->emetteur->name, $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width, "Numéro SIREN ou RNA :", implode(' - ', $ids), $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width, "Adresse :", str_replace(array("\r\n", "\n"), ', ', (string) $this->emetteur->address), $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width / 3, "Code postal :", (string) $this->emetteur->zip, $outputlangs) - 5;
		$posy = $this->showField($pdf, $posx + $width / 3, $posy, $width * 2 / 3, "Commune :", (string) $this->emetteur->town, $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width, "Pays :", $country, $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width, "Objet :", (string) $this->emetteur->socialobject, $outputlangs);

		// Category of the organization
		$types = self::getOrganismTypes();
		$selectedtype = getDolGlobalString('DONATION_CERFA_ORGANISM_TYPE');
		$generalinterest = "Œuvre ou organisme d'intérêt général ayant un caractère philanthropique, éducatif, scientifique, social, humanitaire, sportif, familial, culturel ou concourant à la mise en valeur du patrimoine artistique, à la défense de l'environnement naturel ou à la diffusion de la culture, de la langue et des connaissances scientifiques françaises :";

		$pdf->SetFont('', '', $default_font_size - 2);
		if (!empty($types[$selectedtype])) {
			// The form allows to only give the information about the organization instead of ticking a box in the full list
			if ($types[$selectedtype]['group'] == 'oig') {
				$posy = $this->checkbox($pdf, $posx, $posy, $width, true, $generalinterest, $outputlangs) + 0.5;
				$posx += 6;
			}
			$posy = $this->checkbox($pdf, $posx, $posy, $width - ($posx - $this->marge_gauche), true, $this->getOrganismTypeLabel($selectedtype), $outputlangs);
		} else {
			// No category in setup: the full list is printed, to tick by hand
			$pdf->SetXY($posx, $posy);
			$pdf->MultiCell($width, 4, $outputlangs->convToOutputCharset("Cochez la case concernée :"), 0, 'L');
			$posy = $this->checkbox($pdf, $posx, $pdf->GetY() + 0.5, $width, false, $generalinterest, $outputlangs) + 0.5;
			foreach ($types as $code => $type) {
				if ($type['group'] == 'oig') {
					$posy = $this->checkbox($pdf, $posx + 6, $posy, $width - 6, false, $this->getOrganismTypeLabel($code), $outputlangs) + 0.5;
				}
			}
			foreach ($types as $code => $type) {
				if ($type['group'] != 'oig') {
					$posy = $this->checkbox($pdf, $posx, $posy, $width, false, $this->getOrganismTypeLabel($code), $outputlangs) + 0.5;
				}
			}
		}

		return $posy;
	}


	/**
	 *  Show the block of the donor
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	array{company:string,lastname:string,firstname:string,name:string,address:string,zip:string,town:string,country_code:string,country:string,idprof1:string,thirdparty:?Societe}	$donor	Identity of the donor
	 *  @param	float		$posy			Y position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the block
	 */
	protected function showDonor(&$pdf, $donor, $posy, $outputlangs)
	{
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$posx = $this->marge_gauche;

		// Keep the block on one page
		if ($posy + 36 > $this->page_hauteur - $this->heightforfooter) {
			$pdf->AddPage();
			$posy = $this->marge_haute;
		}

		$posy = $this->sectionTitle($pdf, $posy, "Donateur", $outputlangs);

		$lastname = $donor['lastname'];
		if ($lastname === '' && $donor['firstname'] === '') {
			$lastname = $donor['company'];	// A third party holds a single name field
		}

		$posy = $this->showField($pdf, $posx, $posy, $width / 2, "Nom :", $lastname, $outputlangs) - 5;
		$posy = $this->showField($pdf, $posx + $width / 2, $posy, $width / 2, "Prénoms :", $donor['firstname'], $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width, "Adresse :", str_replace(array("\r\n", "\n"), ', ', $donor['address']), $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width / 3, "Code postal :", $donor['zip'], $outputlangs) - 5;
		$posy = $this->showField($pdf, $posx + $width / 3, $posy, $width * 2 / 3, "Commune :", $donor['town'], $outputlangs);
		$posy = $this->showField($pdf, $posx, $posy, $width, "Pays :", $donor['country'], $outputlangs);

		return $posy;
	}


	/**
	 *  Show the block of the donation: amount, date, certification, form, nature and payment mode, date and signature
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	Don			$don			Donation object
	 *  @param	float		$posy			Y position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the block
	 */
	protected function showDonation(&$pdf, $don, $posy, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;
		$posx = $this->marge_gauche;

		// Keep the block on one page
		if ($posy + 85 > $this->page_hauteur - $this->heightforfooter) {
			$pdf->AddPage();
			$posy = $this->marge_haute;
		}

		$pdf->SetTextColor(0, 0, 0);

		// Amount
		$amount = (float) price2num($don->amount, 'MT');
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($width, 4, $outputlangs->convToOutputCharset("Le bénéficiaire reconnaît avoir reçu des dons et versements ouvrant droit à réduction d'impôt d'un montant de (1) :"), 0, 'L');
		$posy = $pdf->GetY() + 1;
		$pdf->Rect($posx, $posy, 40, 7);
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->SetXY($posx, $posy + 1);
		$pdf->MultiCell(38, 5, price($amount, 0, $outputlangs, 1, -1, 'MT'), 0, 'R');
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx + 42, $posy + 1.5);
		$pdf->MultiCell(15, 4, 'Euros', 0, 'L');
		$amountinletters = dol_convertToWord($amount, $outputlangs, 'euros', true);
		$pdf->SetXY($posx + 60, $posy + 1.5);
		$pdf->MultiCell($width - 60, 4, $outputlangs->convToOutputCharset("Somme en toutes lettres : ".($amountinletters ? $amountinletters : '')), 0, 'L');
		$posy = max($posy + 9, $pdf->GetY() + 1);

		$posy = $this->showField($pdf, $posx, $posy, $width, "Date du versement ou du don :", dol_print_date($don->date, 'day', false, $outputlangs, true), $outputlangs) + 2;

		// Certification
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posx, $posy);
		$pdf->MultiCell($width, 4, $outputlangs->convToOutputCharset("Le bénéficiaire certifie sur l'honneur que les dons et versements qu'il reçoit ouvrent droit à la réduction d'impôt prévue à l'article (2) :"), 0, 'L');
		$posy = $pdf->GetY() + 1;
		$articles = array('200' => '200 du CGI', '978' => '978 du CGI');
		$maxy = $posy;
		$i = 0;
		foreach ($articles as $article => $label) {
			$maxy = max($maxy, $this->checkbox($pdf, $posx + 10 + $i * ($width / 2), $posy, $width / 2 - 10, getDolGlobalInt('DONATION_ART'.$article) > 0, $label, $outputlangs));
			$i++;
		}
		$posy = $maxy + 2;

		// Form of the donation
		$posy = $this->showField($pdf, $posx, $posy, $width, "Forme du don :", '', $outputlangs) - 1;
		$pdf->SetFont('', '', $default_font_size - 1);
		$forms = array('authentic' => "Acte authentique", 'private' => "Acte sous seing privé", 'manual' => "Déclaration de don manuel", 'other' => "Autres");
		$posy = $this->checkboxRow($pdf, $posy, $forms, getDolGlobalString('DONATION_CERFA_FORM'), $outputlangs) + 2;

		// Nature of the donation
		$posy = $this->showField($pdf, $posx, $posy, $width, "Nature du don (3) :", '', $outputlangs) - 1;
		$pdf->SetFont('', '', $default_font_size - 1);
		$nature = getDolGlobalString('DONATION_CERFA_NATURE', 'cash');
		$natures = array(
			'cash' => "Numéraire",
			'shares' => "Titres de sociétés cotés",
			'income' => "Abandon exprès de revenus ou de produits",
			'expenses' => "Frais engagés par les bénévoles, dont ils renoncent expressément au remboursement",
			'other' => "Autres (précisez) (4) : ....................",
		);
		$posy = $this->checkboxRow($pdf, $posy, $natures, $nature, $outputlangs, 3) + 2;

		// Payment mode, for a donation in cash
		$paymentmode = '';
		if ($nature == 'cash') {
			if ($don->mode_reglement_code == 'LIQ') {
				$paymentmode = 'cash';
			} elseif ($don->mode_reglement_code == 'CHQ') {
				$paymentmode = 'cheque';
			} elseif (in_array($don->mode_reglement_code, array('VIR', 'PRE', 'CB'))) {
				$paymentmode = 'transfer';
			}
		}
		$posy = $this->showField($pdf, $posx, $posy, $width, "En cas de don en numéraire, mode de versement du don :", '', $outputlangs) - 1;
		$pdf->SetFont('', '', $default_font_size - 1);
		$paymentmodes = array('cash' => "Remise d'espèces", 'cheque' => "Chèque", 'transfer' => "Virement, prélèvement, carte bancaire");
		$posy = $this->checkboxRow($pdf, $posy, $paymentmodes, $paymentmode, $outputlangs, 3) + 3;

		// Date and signature
		$widthbox = 70;
		$posxbox = $this->page_largeur - $this->marge_droite - $widthbox;
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 100);
		$pdf->SetXY($posxbox, $posy);
		$pdf->MultiCell($widthbox, 4, "Date et signature", 0, 'C');
		$pdf->SetTextColor(0, 0, 0);
		$pdf->Rect($posxbox, $posy + 5, $widthbox, 25);
		$pdf->SetFont('', '', $default_font_size - 1);
		$pdf->SetXY($posxbox + 2, $posy + 6);
		$pdf->MultiCell($widthbox - 4, 4, dol_print_date(dol_now(), 'day', false, $outputlangs, true), 0, 'L');

		return $posy + 30;
	}


	/**
	 *  Show the legal notes of the form
	 *
	 *  @param	TCPDF		$pdf			Object PDF
	 *  @param	float		$posy			Y position
	 *  @param	Translate	$outputlangs	Object lang for output
	 *  @return	float						Y position after the notes
	 */
	protected function showFootnotes(&$pdf, $posy, $outputlangs)
	{
		$width = $this->page_largeur - $this->marge_gauche - $this->marge_droite;

		$notes = "(1) Pour les dons de titres de sociétés cotées et les dons en nature, mentionnez la valeur du don.\n";
		$notes .= "(2) L'organisme bénéficiaire peut cocher une ou plusieurs cases, étant entendu que la fraction du montant donné qui ouvre droit pour son auteur à la réduction d'IFI prévue à l'article 978 du CGI ne peut ouvrir droit à la réduction d'IR prévue à l'article 200 du CGI et inversement. ";
		$notes .= "En application de l'article L. 80 C du livre des procédures fiscales, il peut demander à l'administration s'il relève de l'une des catégories d'organismes mentionnées à l'article 200 du code général des impôts. ";
		$notes .= "Il est rappelé que le fait de délivrer sciemment des documents permettant à un contribuable d'obtenir indûment une réduction d'impôt entraîne l'application de l'amende prévue à l'article 1740 A du code général des impôts.\n";
		$notes .= "(3) La réduction d'IFI ne s'applique qu'aux dons en numéraire et aux dons en pleine propriété de titres de sociétés cotées.
";
		$notes .= "(4) Exemple : dons en nature.";

		$pdf->SetFont('', '', 6.5);
		$pdf->SetTextColor(60, 60, 60);
		$heightnotes = $pdf->getStringHeight($width, $outputlangs->convToOutputCharset($notes));
		// Notes are printed at the bottom of the last page
		$posy = max($posy, $this->page_hauteur - $this->heightforfooter - $heightnotes);
		$pdf->Line($this->marge_gauche, $posy - 1, $this->marge_gauche + 50, $posy - 1);
		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell($width, 3, $outputlangs->convToOutputCharset($notes), 0, 'L');
		$pdf->SetTextColor(0, 0, 0);

		return $pdf->GetY();
	}
}
