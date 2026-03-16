<?php

/* Copyright (C) 2024-2025 Your Name <your.email@example.com>
 * Inspired by the standard stock and invoice PDF models
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/salaries/modules_salary.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
if (isModEnabled('project')) {
	require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
}
require_once DOL_DOCUMENT_ROOT.'/compta/paiement/class/paiement.class.php';


/**
 *  Class to build a standard salary slip PDF
 */
class pdf_standard_salary extends ModelePDFSalary
{
	/** @var DoliDB */
	public $db;

	/** @var string */
	public $name;

	/** @var string */
	public $description;

	/** @var string */
	public $type;

	/** @var float */
	public $page_largeur;

	/** @var float */
	public $page_hauteur;

	/** @var array{float, float} */
	public $format;

	/** @var float */
	public $marge_gauche;

	/** @var float */
	public $marge_droite;

	/** @var float */
	public $marge_haute;

	/** @var float */
	public $marge_basse;

	/** @var float */
	public $posxdesc;

	/** @var float */
	public $posxearning;

	/** @var float */
	public $posxdeduction;

	/** @var Societe */
	public $emetteur;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct(\DoliDB $db)
	{
		global $conf, $langs, $mysoc;

		$langs->loadLangs(array("main", "companies", "bills", "salaries", "projects"));

		$this->db = $db;
		$this->name = "standard_salary";
		$this->description = $langs->trans("StandardSalaryDocModel");

		// Page dimensions
		$this->type = 'pdf';
		$formatarray = pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = getDolGlobalInt('MAIN_PDF_MARGIN_LEFT', 10);
		$this->marge_droite = getDolGlobalInt('MAIN_PDF_MARGIN_RIGHT', 10);
		$this->marge_haute = getDolGlobalInt('MAIN_PDF_MARGIN_TOP', 10);
		$this->marge_basse = getDolGlobalInt('MAIN_PDF_MARGIN_BOTTOM', 10);

		// Define position of columns for the table body
		$this->posxdesc      = $this->marge_gauche;
		$this->posxearning   = $this->page_largeur - $this->marge_droite - 80;
		$this->posxdeduction = $this->page_largeur - $this->marge_droite - 40;

		$this->emetteur = $mysoc;
	}


	/**
	 * Function to build the salary slip document
	 *
	 * @param Salary     $object             Object source to build document
	 * @param Translate  $outputlangs         Lang output object
	 * @param string     $srctemplatepath     Full path of source filename
	 * @param int        $hidedetails         Hide details
	 * @param int        $hidedesc            Hide description
	 * @param int        $hideref             Hide reference
	 * @return int                            1 if OK, <=0 if KO
	 */
	public function writeFile($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		global $conf, $langs;

		$outputlangs = is_object($outputlangs) ? $outputlangs : $langs;
		if (getDolGlobalString('MAIN_USE_FPDF')) $outputlangs->charset_output = 'ISO-8859-1';

		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "salaries"));

		if (!$conf->salaries->dir_output) {
			$this->error = $langs->trans("ErrorConstantNotDefined", "SALARIES_OUTPUTDIR");
			return 0;
		}

		$objref = dol_sanitizeFileName($object->ref);
		$dir = $conf->salaries->dir_output . "/salary/" . $objref;
		$file = $dir . "/" . $objref . ".pdf";

		if (!dol_is_dir($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (is_writable($dir)) {
			$pdf = pdf_getInstance($this->format);
			$pdf->setAutoPageBreak(true, 0);

			if (class_exists('TCPDF')) {
				$pdf->setPrintHeader(false);
				$pdf->setPrintFooter(false);
			}
			$pdf->SetFont(pdf_getPDFFont($outputlangs));
			$pdf->Open();

			$pdf->AddPage();
			$this->_pagehead($pdf, $object, $outputlangs);
			$this->body($pdf, $object, $outputlangs);
			$this->_pagefoot($pdf, $object, $outputlangs);

			$pdf->Close();
			$pdf->Output($file, 'F');
			dolChmod($file);

			return 1;
		} else {
			$this->error = $langs->trans("ErrorDirIsNotWritable", $dir);
			return 0;
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 * Show top header of page.
	 *
	 * @param TCPDF     $pdf          Object PDF
	 * @param Salary    $object       Object to show
	 * @param Translate $outputlangs  Object lang for output
	 * @param int       $showaddress  Show address
	 * @return void
	 */
	protected function _pagehead(&$pdf, $object, $outputlangs, $showaddress = 1)
	{
		global $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf, $outputlangs, $this->page_hauteur);

		$posy = $this->marge_haute;
		$posx = $this->page_largeur - $this->marge_droite - 100;

		$pdf->SetXY($this->marge_gauche, $posy);

		// Logo
		$logo = $conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo && is_readable($logo)) {
			$height = pdf_getHeightForLogo($logo);
			$pdf->Image($logo, $this->marge_gauche, $posy, 0, $height); // width=0 (auto)
		} else {
			$pdf->SetFont('', 'B', $default_font_size + 2);
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		}
		$yafterleft = $pdf->GetY();

		// Company Address
		$pdf->SetFont('', '', $default_font_size);
		$pdf->SetXY($this->marge_gauche, $yafterleft + 1);
		pdf_build_address($outputlangs, $this->emetteur, null, 'target');
		$yafterleft = $pdf->GetY();


		// Document Title
		$pdf->SetFont('', 'B', $default_font_size + 4);
		$pdf->SetXY($posx, $posy);
		$pdf->SetTextColor(0, 0, 60);
		$pdf->MultiCell(100, 5, $outputlangs->transnoentities("SalarySlip") . ' ' . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy = $pdf->GetY() + 8;

		// Employee Information
		$employee = new User($this->db);

		$fk_user = 0;
		if (!empty($object->fk_user)) {
			$fk_user = $object->fk_user;
		} elseif (!empty($object->fk_user_author)) {
			$fk_user = $object->fk_user_author;
		}

		if ($fk_user > 0 && $employee->fetch($fk_user) > 0) {
			$pdf->SetFont('', '', $default_font_size);
			$pdf->SetXY($posx, $posy);

			$text = $employee->getFullName($outputlangs) . "\n";

			if (!empty($employee->job)) {
				$text .= $employee->job . "\n";
			}
			if (!empty($employee->address)) {
				$text .= $employee->address . "\n";
			}
			if (!empty($employee->zip) || !empty($employee->town)) {
				$text .= trim($employee->zip . ' ' . $employee->town) . "\n";
			}
			if (!empty($employee->email)) {
				$text .= $employee->email;
			}
			$yafterright = $pdf->GetY();
		} else {
			$yafterright = $posy;
		}


		// Move cursor to below the taller of the two columns
		$pdf->SetY(max($yafterleft, $yafterright) + 8);

		// Info box
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetTextColor(0, 0, 0);

		// Build info array
		$info_array = [];

		// Add employee information to info box
		if (!empty($employee->id)) {
			$info_array[$outputlangs->transnoentitiesnoconv("Employee")]
				= $employee->getFullName($outputlangs);

			if (!empty($employee->job)) {
				$info_array[$outputlangs->transnoentitiesnoconv("Job")]
					= $employee->job;
			}

			if (!empty($employee->email)) {
				$info_array[$outputlangs->transnoentitiesnoconv("Email")]
					= $employee->email;
			}
		}

		$info_array[$outputlangs->transnoentitiesnoconv("PayPeriod")] = dol_print_date($object->datesp, 'day') . " - " . dol_print_date($object->dateep, 'day');
		$payment_date = $object->datesp;
		$info_array[$outputlangs->transnoentitiesnoconv("DatePayment")] = dol_print_date($payment_date, 'day');

		if ($object->fk_typepayment && !empty($object->type_payment_code)) {
			$info_array[$outputlangs->transnoentitiesnoconv("PaymentMode")] = $outputlangs->trans("PaymentType".$object->type_payment_code);
		}

		if (!empty($object->num_payment)) {
			$info_array[$outputlangs->transnoentitiesnoconv("Numero")] = $object->num_payment;
		}
		if (isModEnabled('project') && !empty($object->fk_project)) {
			$project = new Project($this->db);
			if ($project->fetch($object->fk_project) > 0) {
				$info_array[$outputlangs->transnoentitiesnoconv("Project")] = $project->ref;
			}
		}

		// Print info box
		$col1_width = 40;
		foreach ($info_array as $label => $value) {
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->Cell($col1_width, 5, $label . " :", 0, 0, 'L');
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->MultiCell(0, 5, $value, 0, 'L');
		}
		$pdf->Ln(5);
	}


	/**
	 * Show the lines of the salary slip
	 *
	 * @param TCPDF     $pdf         PDF object
	 * @param object    $object      Salary object
	 * @param Translate $outputlangs Language object
	 * @return void
	 */
	protected function body(&$pdf, $object, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$tab_top = $pdf->GetY();

		$this->tableauHeader($pdf, $tab_top, $outputlangs);
		$curY = $tab_top + 7;

		// --- EARNINGS ---
		$pdf->SetFont('', '', $default_font_size);
		$pdf->SetXY($this->posxdesc, $curY);
		$pdf->MultiCell($this->posxearning - $this->posxdesc, 5, $outputlangs->convToOutputCharset($object->label));
		$y_after_label = $pdf->GetY();

		$pdf->SetXY($this->posxearning, $curY);
		$pdf->MultiCell($this->posxdeduction - $this->posxearning, 5, price($object->amount, 0, $outputlangs, 1), 0, 'R');

		$gross_pay = $object->amount;

		// --- DEDUCTIONS ---
		// This section is ready for when the salary object supports deductions.
		$total_deductions = 0.00;

		// --- PUBLIC NOTE ---
		if (!empty($object->note_public)) {
			$pdf->SetY($y_after_label + 5);
			$pdf->SetFont('', 'B', $default_font_size - 1);
			$pdf->Cell(0, 5, $outputlangs->transnoentitiesnoconv("Note"), 0, 1, 'L');
			$pdf->SetFont('', '', $default_font_size - 1);
			$pdf->writeHTMLCell($this->page_largeur - $this->marge_gauche - $this->marge_droite, 5, $this->marge_gauche, $pdf->GetY(), $object->note_public, 'B', 1);
		}

		// --- TOTALS ---
		$net_pay = $gross_pay - $total_deductions;
		$totals_y_pos = $this->page_hauteur - $this->marge_basse - 30;
		$pdf->SetY(max($pdf->GetY() + 5, $totals_y_pos));

		$pdf->SetFont('', '', $default_font_size);
		$pdf->SetX($this->posxearning - 30);
		$pdf->Cell(40, 5, $outputlangs->transnoentitiesnoconv("TotalEarnings") . " :", 0, 0, 'R');
		$pdf->Cell(30, 5, price($gross_pay, 0, $outputlangs, 1), 0, 1, 'R');

		$pdf->SetX($this->posxearning - 30);
		$pdf->Cell(40, 5, $outputlangs->transnoentitiesnoconv("TotalDeductions") . " :", 0, 0, 'R');
		$pdf->Cell(30, 5, price($total_deductions, 0, $outputlangs, 1), 0, 1, 'R');

		$pdf->SetLineStyle(array('width' => 0.3, 'color' => array(0, 0, 0)));
		$pdf->Line($this->posxearning - 30, $pdf->GetY(), $this->page_largeur - $this->marge_droite, $pdf->GetY());
		$pdf->Ln(1);

		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->SetX($this->posxearning - 30);
		$pdf->Cell(40, 6, $outputlangs->transnoentitiesnoconv("NetPaid") . " :", 0, 0, 'R');
		$pdf->Cell(30, 6, price($net_pay, 0, $outputlangs, 1), 0, 1, 'R');
	}


	/**
	 *   Show table header
	 *
	 *   @param     TCPDF		$pdf     		Object PDF
	 *   @param     float		$tab_top		Top position of table
	 *   @param     Translate	$outputlangs	Langs object
	 *   @return    void
	 */
	protected function tableauHeader(&$pdf, $tab_top, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetXY($this->posxdesc, $tab_top);
		$pdf->SetFont('', 'B', $default_font_size - 1);
		$pdf->SetFillColor(240, 240, 240);
		$pdf->SetTextColor(0, 0, 0);

		$pdf->Cell($this->posxearning - $this->posxdesc, 7, $outputlangs->transnoentitiesnoconv("Description"), 0, 0, 'L', true);
		$pdf->Cell($this->posxdeduction - $this->posxearning, 7, $outputlangs->transnoentitiesnoconv("Earnings"), 0, 0, 'R', true);
		$pdf->Cell($this->page_largeur - $this->marge_droite - $this->posxdeduction, 7, $outputlangs->transnoentitiesnoconv("Deductions"), 0, 1, 'R', true);

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->Line($this->marge_gauche, $tab_top + 7, $this->page_largeur - $this->marge_droite, $tab_top + 7);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *   Show footer of page.
	 *
	 *   @param	TCPDF		$pdf     			PDF object
	 * 	 @param	object		$object				Object to show
	 *   @param	Translate	$outputlangs		Object lang for output
	 *   @return	void
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs)
	{
		$showdetails = getDolGlobalInt('MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS', 0);
		pdf_pagefoot($pdf, $outputlangs, 'SALARY_FREE_TEXT', $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails);
	}
}
