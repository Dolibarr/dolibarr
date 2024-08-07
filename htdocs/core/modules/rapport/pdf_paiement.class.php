<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2015-2018 Charlene BENKE  	<charlie@patas-monkey.com>
 * Copyright (C) 2020      Maxime DEMAREST <maxime@indelog.fr>
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
 *	\file       htdocs/core/modules/rapport/pdf_paiement.class.php
 *	\ingroup    banque
 *	\brief      File to build payment reports
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Class to manage reporting of payments
 */
class pdf_paiement extends CommonDocGenerator
{
	public $tab_top;

	public $line_height;

	public $line_per_page;

	public $tab_height;

	public $posxdate;

	public $posxpaymenttype;
	public $posxinvoice;
	public $posxbankaccount;
	public $posxinvoiceamount;
	public $posxpaymentamount;

	public $doc_type;

	/**
	 * @var int
	 */
	public $year;

	/**
	 * @var int
	 */
	public $month;

	/**
	 *  Constructor
	 *
	 *  @param      DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $conf;

		// Load translation files required by the page
		$langs->loadLangs(array("bills", "compta", "main"));

		$this->db = $db;
		$this->description = $langs->transnoentities("ListOfCustomerPayments");

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

		$this->tab_top = 30;

		$this->line_height = 5;
		$this->line_per_page = 40;
		$this->tab_height = $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->tab_top - 5; // must be > $this->line_height * $this->line_per_page and < $this->page_hauteur - $this->marge_haute - $this->marge_basse - $this->tab_top - 5;

		$this->posxdate = $this->marge_gauche + 2;
		$this->posxpaymenttype = 42;
		$this->posxinvoice = 82;
		$this->posxbankaccount = 110;
		$this->posxinvoiceamount = 132;
		$this->posxpaymentamount = 162;
		if ($this->page_largeur < 210) { // To work with US executive format
			$this->line_per_page = 35;
			$this->posxpaymenttype -= 10;
			$this->posxinvoice -= 0;
			$this->posxinvoiceamount -= 10;
			$this->posxpaymentamount -= 20;
		}
		// which type of document will be generated: clients (client) or providers (fourn) invoices
		$this->doc_type = "client";
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Fonction generant la rapport sur le disque
	 *
	 *	@param	string	$_dir			repertoire
	 *	@param	int		$month			mois du rapport
	 *	@param	int		$year			annee du rapport
	 *	@param	string	$outputlangs	Lang output object
	 *	@return	int						Return integer <0 if KO, >0 if OK
	 */
	public function write_file($_dir, $month, $year, $outputlangs)
	{
		// phpcs:enable
		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		global $conf, $hookmanager, $langs, $user;

		$socid = 0;
		if ($user->socid) {
			$socid = $user->socid;
		}

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (getDolGlobalString('MAIN_USE_FPDF')) {
			$outputlangs->charset_output = 'ISO-8859-1';
		}

		$this->month = $month;
		$this->year = $year;
		$dir = $_dir.'/'.$year;

		if (!is_dir($dir)) {
			$result = dol_mkdir($dir);
			if ($result < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		$month = sprintf("%02d", $month);
		$year = sprintf("%04d", $year);

		$file = $dir."/payments-".$year."-".$month.".pdf";
		switch ($this->doc_type) {
			case "client":
				$file = $dir."/payments-".$year."-".$month.".pdf";
				break;
			case "fourn":
				$file = $dir."/supplier_payments-".$year."-".$month.".pdf";
				break;
		}


		// Add pdfgeneration hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters = array('file' => $file, 'object' => $this, 'outputlangs' => $outputlangs);
		global $action;
		$reshook = $hookmanager->executeHooks('beforePDFCreation', $parameters, $this, $action); // Note that $action and $this may have been modified by some hooks

		$pdf = pdf_getInstance($this->format);
		$default_font_size = pdf_getPDFFontSize($outputlangs); // Must be after pdf_getInstance

		if (class_exists('TCPDF')) {
			$pdf->setPrintHeader(false);
			$pdf->setPrintFooter(false);
		}
		$pdf->SetFont(pdf_getPDFFont($outputlangs));

		$num = 0;
		$lines = array();

		// count number of lines of payment
		$sql = "SELECT p.rowid as prowid";
		switch ($this->doc_type) {
			case "client":
				$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p";
				break;
			case "fourn":
				$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p";
				break;
		}
		$sql .= " WHERE p.datep BETWEEN '".$this->db->idate(dol_get_first_day($year, $month))."' AND '".$this->db->idate(dol_get_last_day($year, $month))."'";
		$sql .= " AND p.entity = ".$conf->entity;
		$result = $this->db->query($sql);
		if ($result) {
			$numpaiement = $this->db->num_rows($result);
		}

		// number of bill
		switch ($this->doc_type) {
			case "client":
				$sql = "SELECT p.datep as dp, f.ref";
				$sql .= ", c.code as paiement_code, p.num_paiement as num_payment";
				$sql .= ", p.amount as paiement_amount, f.total_ttc as facture_amount";
				$sql .= ", pf.amount as pf_amount";
				if (isModEnabled("bank")) {
					$sql .= ", ba.ref as bankaccount";
				}
				$sql .= ", p.rowid as prowid";
				$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_paiement = c.id";
				$sql .= ", ".MAIN_DB_PREFIX."facture as f,";
				$sql .= " ".MAIN_DB_PREFIX."paiement_facture as pf,";
				if (isModEnabled("bank")) {
					$sql .= " ".MAIN_DB_PREFIX."bank as b, ".MAIN_DB_PREFIX."bank_account as ba,";
				}
				$sql .= " ".MAIN_DB_PREFIX."societe as s";
				if (!$user->hasRight('societe', 'client', 'voir')) {
					$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
				}
				$sql .= " WHERE f.fk_soc = s.rowid AND pf.fk_facture = f.rowid AND pf.fk_paiement = p.rowid";
				if (isModEnabled("bank")) {
					$sql .= " AND p.fk_bank = b.rowid AND b.fk_account = ba.rowid ";
				}
				$sql .= " AND f.entity IN (".getEntity('invoice').")";
				$sql .= " AND p.datep BETWEEN '".$this->db->idate(dol_get_first_day($year, $month))."' AND '".$this->db->idate(dol_get_last_day($year, $month))."'";
				if (!$user->hasRight('societe', 'client', 'voir')) {
					$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
				}
				if (!empty($socid)) {
					$sql .= " AND s.rowid = ".((int) $socid);
				}
				// If global param PAYMENTS_REPORT_GROUP_BY_MOD is set, payment are ordered by paiement_code
				if (getDolGlobalString('PAYMENTS_REPORT_GROUP_BY_MOD')) {
					$sql .= " ORDER BY paiement_code ASC, p.datep ASC, pf.fk_paiement ASC";
				} else {
					$sql .= " ORDER BY p.datep ASC, pf.fk_paiement ASC";
				}
				break;
			case "fourn":
				$sql = "SELECT p.datep as dp, f.ref as ref";
				$sql .= ", c.code as paiement_code, p.num_paiement as num_payment";
				$sql .= ", p.amount as paiement_amount, f.total_ttc as facture_amount";
				$sql .= ", pf.amount as pf_amount";
				if (isModEnabled("bank")) {
					$sql .= ", ba.ref as bankaccount";
				}
				$sql .= ", p.rowid as prowid";
				$sql .= " FROM ".MAIN_DB_PREFIX."paiementfourn as p LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as c ON p.fk_paiement = c.id";
				$sql .= ", ".MAIN_DB_PREFIX."facture_fourn as f,";
				$sql .= " ".MAIN_DB_PREFIX."paiementfourn_facturefourn as pf,";
				if (isModEnabled("bank")) {
					$sql .= " ".MAIN_DB_PREFIX."bank as b, ".MAIN_DB_PREFIX."bank_account as ba,";
				}
				$sql .= " ".MAIN_DB_PREFIX."societe as s";
				if (!$user->hasRight('societe', 'client', 'voir')) {
					$sql .= ", ".MAIN_DB_PREFIX."societe_commerciaux as sc";
				}
				$sql .= " WHERE f.fk_soc = s.rowid AND pf.fk_facturefourn = f.rowid AND pf.fk_paiementfourn = p.rowid";
				if (isModEnabled("bank")) {
					$sql .= " AND p.fk_bank = b.rowid AND b.fk_account = ba.rowid ";
				}
				$sql .= " AND f.entity IN (".getEntity('invoice').")";
				$sql .= " AND p.datep BETWEEN '".$this->db->idate(dol_get_first_day($year, $month))."' AND '".$this->db->idate(dol_get_last_day($year, $month))."'";
				if (!$user->hasRight('societe', 'client', 'voir')) {
					$sql .= " AND s.rowid = sc.fk_soc AND sc.fk_user = ".((int) $user->id);
				}
				if (!empty($socid)) {
					$sql .= " AND s.rowid = ".((int) $socid);
				}
				// If global param PAYMENTS_FOURN_REPORT_GROUP_BY_MOD is set, payment fourn are ordered by paiement_code
				if (getDolGlobalString('PAYMENTS_FOURN_REPORT_GROUP_BY_MOD')) {
					$sql .= " ORDER BY paiement_code ASC, p.datep ASC, pf.fk_paiementfourn ASC";
				} else {
					$sql .= " ORDER BY p.datep ASC, pf.fk_paiementfourn ASC";
				}
				break;
		}

		dol_syslog(get_class($this)."::write_file", LOG_DEBUG);
		$result = $this->db->query($sql);
		if ($result) {
			$num = $this->db->num_rows($result);
			$i = 0;

			while ($i < $num) {
				$objp = $this->db->fetch_object($result);

				$lines[$i][0] = $objp->ref;
				$lines[$i][1] = dol_print_date($this->db->jdate($objp->dp), "day", false, $outputlangs, true);
				$lines[$i][2] = $langs->transnoentities("PaymentTypeShort".$objp->paiement_code);
				$lines[$i][3] = $objp->num_payment;
				$lines[$i][4] = price($objp->paiement_amount);
				$lines[$i][5] = price($objp->facture_amount);
				$lines[$i][6] = price($objp->pf_amount);
				$lines[$i][7] = $objp->prowid;
				$lines[$i][8] = $objp->bankaccount;
				$lines[$i][9] = $objp->paiement_amount;
				$i++;
			}
		} else {
			dol_print_error($this->db);
		}

		$pages = intval(($num + $numpaiement) / $this->line_per_page);

		if ((($num + $numpaiement) % $this->line_per_page) > 0) {
			$pages++;
		}

		if ($pages == 0) {
			// force to build at least one page if report has no line
			$pages = 1;
		}

		$pdf->Open();
		$pagenb = 0;
		$pdf->SetDrawColor(128, 128, 128);

		$pdf->SetTitle($outputlangs->transnoentities("Payments"));
		$pdf->SetSubject($outputlangs->transnoentities("Payments"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		//$pdf->SetKeyWords();
		if (getDolGlobalString('MAIN_DISABLE_PDF_COMPRESSION')) {
			$pdf->SetCompression(false);
		}

		// @phan-suppress-next-line PhanPluginSuspiciousParamOrder
		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite); // Left, Top, Right
		$pdf->SetAutoPageBreak(1, 0);

		// New page
		$pdf->AddPage();
		$pagenb++;
		$this->_pagehead($pdf, $pagenb, 1, $outputlangs);
		$pdf->SetFont('', '', 9);
		$pdf->MultiCell(0, 3, ''); // Set interline to 3
		$pdf->SetTextColor(0, 0, 0);


		$this->Body($pdf, 1, $lines, $outputlangs);

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
		$parameters = array('file' => $file, 'object' => $this, 'outputlangs' => $outputlangs);
		global $action;
		$reshook = $hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0) {
			$this->error = $hookmanager->error;
			$this->errors = $hookmanager->errors;
		}

		dolChmod($file);

		$this->result = array('fullpath' => $file);

		return 1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  int			$page	     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	float|int                   Return topshift value
	 */
	protected function _pagehead(&$pdf, $page, $showaddress, $outputlangs)
	{
		// phpcs:enable

		// Do not add the BACKGROUND as this is a report
		//pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$title = getDolGlobalString('MAIN_INFO_SOCIETE_NOM');
		switch ($this->doc_type) {
			case "client":
				$title .= ' - '.$outputlangs->transnoentities("ListOfCustomerPayments");
				break;
			case "fourn":
				$title .= ' - '.$outputlangs->transnoentities("ListOfSupplierPayments");
				break;
		}
		$title .= ' - '.dol_print_date(dol_mktime(0, 0, 0, $this->month, 1, $this->year), "%B %Y", false, $outputlangs, true);
		$pdf->SetFont('', 'B', $default_font_size + 1);
		$pdf->SetXY($this->marge_gauche, 10);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 2, $title, 0, 'C');

		$pdf->SetFont('', '', $default_font_size);

		$pdf->SetXY($this->posxdate, 16);
		$pdf->MultiCell(80, 2, $outputlangs->transnoentities("DateBuild")." : ".dol_print_date(time(), "day", false, $outputlangs, true), 0, 'L');

		$pdf->SetXY($this->posxdate + 100, 16);
		$pdf->MultiCell(80, 2, $outputlangs->transnoentities("Page")." : ".$page, 0, 'R');


		// Title line
		$pdf->SetXY($this->posxdate, $this->tab_top + 2);
		$pdf->MultiCell($this->posxpaymenttype - $this->posxdate, 2, 'Date');

		$pdf->line($this->posxpaymenttype - 1, $this->tab_top, $this->posxpaymenttype - 1, $this->tab_top + $this->tab_height + 10);
		$pdf->SetXY($this->posxpaymenttype, $this->tab_top + 2);
		$pdf->MultiCell($this->posxinvoice - $this->posxpaymenttype, 2, $outputlangs->transnoentities("PaymentMode"), 0, 'L');

		$pdf->line($this->posxinvoice - 1, $this->tab_top, $this->posxinvoice - 1, $this->tab_top + $this->tab_height + 10);
		$pdf->SetXY($this->posxinvoice, $this->tab_top + 2);
		$pdf->MultiCell($this->posxbankaccount - $this->posxinvoice, 2, $outputlangs->transnoentities("Invoice"), 0, 'L');

		$pdf->line($this->posxbankaccount - 1, $this->tab_top, $this->posxbankaccount - 1, $this->tab_top + $this->tab_height + 10);
		$pdf->SetXY($this->posxbankaccount, $this->tab_top + 2);
		$pdf->MultiCell($this->posxinvoiceamount - $this->posxbankaccount, 2, $outputlangs->transnoentities("BankAccount"), 0, 'L');


		$pdf->line($this->posxinvoiceamount - 1, $this->tab_top, $this->posxinvoiceamount - 1, $this->tab_top + $this->tab_height + 10);
		$pdf->SetXY($this->posxinvoiceamount, $this->tab_top + 2);
		$pdf->MultiCell($this->posxpaymentamount - $this->posxinvoiceamount - 1, 2, $outputlangs->transnoentities("AmountInvoice"), 0, 'R');

		$pdf->line($this->posxpaymentamount - 1, $this->tab_top, $this->posxpaymentamount - 1, $this->tab_top + $this->tab_height + 10);
		$pdf->SetXY($this->posxpaymentamount, $this->tab_top + 2);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxpaymentamount - 1, 2, $outputlangs->transnoentities("AmountPayment"), 0, 'R');

		$pdf->line($this->marge_gauche, $this->tab_top + 10, $this->page_largeur - $this->marge_droite, $this->tab_top + 10);

		$pdf->Rect($this->marge_gauche, $this->tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $this->tab_height + 10);

		return 0;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Output body
	 *
	 *	@param	TCPDF		$pdf			PDF object
	 *	@param	string		$page			Page
	 *	@param	array		$lines			Array of lines
	 *	@param	Translate	$outputlangs	Object langs
	 *	@return	void
	 */
	public function Body(&$pdf, $page, $lines, $outputlangs)
	{
		// phpcs:enable
		global $langs, $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('', '', $default_font_size - 1);
		$oldprowid = 0;
		$total_page = 0;
		$total = 0;
		$pdf->SetFillColor(220, 220, 220);
		$yp = 0;
		$numlines = count($lines);
		if (($this->doc_type == 'client' && getDolGlobalString('PAYMENTS_REPORT_GROUP_BY_MOD')) || ($this->doc_type == 'fourn' && getDolGlobalString('PAYMENTS_FOURN_REPORT_GROUP_BY_MOD'))) {
			$mod = $lines[0][2];
			$total_mod = 0;
		}
		for ($j = 0; $j < $numlines; $j++) {
			$i = $j;
			if ($yp > $this->tab_height - 5) {
				$page++;
				$pdf->AddPage();
				$this->_pagehead($pdf, $page, 0, $outputlangs);
				$pdf->SetFont('', '', $default_font_size - 1);
				$yp = 0;
			}
			if ($oldprowid != $lines[$j][7]) {
				if ($yp > $this->tab_height - 15) {
					$pdf->SetFillColor(255, 255, 255);
					$pdf->Rect($this->marge_gauche + 1, $this->tab_top + 10 + $yp, $this->posxpaymentamount - $this->marge_droite - 3, $this->line_height, 'F', array(), array());
					$pdf->line($this->marge_gauche, $this->tab_top + 10 + $yp, $this->page_largeur - $this->marge_droite, $this->tab_top + 10 + $yp, array('dash' => 1));
					$pdf->line($this->marge_gauche, $this->tab_top + 15 + $yp, $this->page_largeur - $this->marge_droite, $this->tab_top + 15 + $yp);
					$pdf->SetFont('', 'B', $default_font_size - 1);
					$pdf->SetXY($this->posxdate - 1, $this->tab_top + 10 + $yp);
					$pdf->MultiCell($this->posxpaymentamount - 2 - $this->marge_droite, $this->line_height, $langs->transnoentities('SubTotal')." : ", 0, 'R', 1);
					$pdf->SetXY($this->posxpaymentamount - 1, $this->tab_top + 10 + $yp);
					$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxpaymentamount + 1, $this->line_height, price($total_page), 0, 'R', 1);
					$pdf->SetFont('', '', $default_font_size - 1);
					$pdf->SetFillColor(220, 220, 220);
					$page++;
					$pdf->AddPage();
					$this->_pagehead($pdf, $page, 0, $outputlangs);
					$pdf->SetFont('', '', $default_font_size - 1);
					$yp = 0;
					$total += $total_page;
					$total_page = 0;
				}

				$pdf->SetXY($this->posxdate - 1, $this->tab_top + 10 + $yp);
				$pdf->MultiCell($this->posxpaymenttype - $this->posxdate + 1, $this->line_height, $lines[$j][1], 0, 'L', 1);

				$pdf->SetXY($this->posxpaymenttype, $this->tab_top + 10 + $yp);
				$pdf->MultiCell($this->posxinvoiceamount - $this->posxpaymenttype, $this->line_height, $lines[$j][2].' '.$lines[$j][3], 0, 'L', 1);

				$pdf->SetXY($this->posxinvoiceamount, $this->tab_top + 10 + $yp);
				$pdf->MultiCell($this->posxpaymentamount - $this->posxinvoiceamount, $this->line_height, '', 0, 'R', 1);

				$pdf->SetXY($this->posxpaymentamount, $this->tab_top + 10 + $yp);
				$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxpaymentamount, $this->line_height, $lines[$j][4], 0, 'R', 1);
				$yp += 5;
				$total_page += $lines[$j][9];
				if (($this->doc_type == 'client' && getDolGlobalString('PAYMENTS_REPORT_GROUP_BY_MOD')) || ($this->doc_type == 'fourn' && getDolGlobalString('PAYMENTS_FOURN_REPORT_GROUP_BY_MOD'))) {
					$total_mod += $lines[$j][9];
				}
			}

			// Invoice number
			$pdf->SetXY($this->posxinvoice, $this->tab_top + 10 + $yp);
			$pdf->MultiCell($this->posxinvoice - $this->posxbankaccount, $this->line_height, $lines[$j][0], 0, 'L', 0);

			// BankAccount
			$pdf->SetXY($this->posxbankaccount, $this->tab_top + 10 + $yp);
			$pdf->MultiCell($this->posxbankaccount - $this->posxdate, $this->line_height, $lines[$j][8], 0, 'L', 0);

			// Invoice amount
			$pdf->SetXY($this->posxinvoiceamount, $this->tab_top + 10 + $yp);
			$pdf->MultiCell($this->posxpaymentamount - $this->posxinvoiceamount - 1, $this->line_height, $lines[$j][5], 0, 'R', 0);

			// Payment amount
			$pdf->SetXY($this->posxpaymentamount, $this->tab_top + 10 + $yp);
			$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxpaymentamount, $this->line_height, $lines[$j][6], 0, 'R', 0);
			$yp += 5;

			if ($oldprowid != $lines[$j][7]) {
				$oldprowid = $lines[$j][7];
			}

			// Add line to add total by payment mode if mode reglement for nex line change
			if ((($this->doc_type == 'client' && getDolGlobalString('PAYMENTS_REPORT_GROUP_BY_MOD')) || ($this->doc_type == 'fourn' && getDolGlobalString('PAYMENTS_FOURN_REPORT_GROUP_BY_MOD'))) && ($mod != $lines[$j + 1][2])) {
				$pdf->SetFillColor(245, 245, 245);
				$pdf->Rect($this->marge_gauche + 1, $this->tab_top + 10 + $yp, $this->posxpaymentamount - $this->marge_droite - 3, $this->line_height, 'F', array(), array());
				$pdf->line($this->marge_gauche, $this->tab_top + 10 + $yp, $this->page_largeur - $this->marge_droite, $this->tab_top + 10 + $yp, array('dash' => 1));
				$pdf->line($this->marge_gauche, $this->tab_top + 15 + $yp, $this->page_largeur - $this->marge_droite, $this->tab_top + 15 + $yp);
				$pdf->SetXY($this->posxdate - 1, $this->tab_top + 10 + $yp);
				$pdf->SetFont('', 'I', $default_font_size - 1);
				$pdf->MultiCell($this->posxpaymentamount - 2 - $this->marge_droite, $this->line_height, $langs->transnoentities('Total').' '.$mod." : ", 0, 'R', 1);
				$pdf->SetXY($this->posxpaymentamount - 1, $this->tab_top + 10 + $yp);
				$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxpaymentamount + 1, $this->line_height, price($total_mod), 0, 'R', 1);
				$pdf->SetFont('', '', $default_font_size - 1);
				$mod = $lines[$j + 1][2];
				$total_mod = 0;
				$yp += 5;
				if ($yp > $this->tab_height - 5) {
					$page++;
					$pdf->AddPage();
					$this->_pagehead($pdf, $page, 0, $outputlangs);
					$pdf->SetFont('', '', $default_font_size - 1);
					$yp = 0;
				}
				$pdf->SetFillColor(220, 220, 220);
			}
		}
		$total += $total_page;
		$pdf->SetFillColor(255, 255, 255);
		$pdf->Rect($this->marge_gauche + 1, $this->tab_top + 10 + $yp, $this->posxpaymentamount - $this->marge_droite - 3, $this->line_height, 'F', array(), array());
		$pdf->line($this->marge_gauche, $this->tab_top + 10 + $yp, $this->page_largeur - $this->marge_droite, $this->tab_top + 10 + $yp, array('dash' => 1));
		$pdf->line($this->marge_gauche, $this->tab_top + 15 + $yp, $this->page_largeur - $this->marge_droite, $this->tab_top + 15 + $yp);
		$pdf->SetXY($this->posxdate - 1, $this->tab_top + 10 + $yp);
		$pdf->SetFont('', 'B');
		$pdf->MultiCell($this->posxpaymentamount - 2 - $this->marge_droite, $this->line_height, $langs->transnoentities('Total')." : ", 0, 'R', 1);
		$pdf->SetXY($this->posxpaymentamount - 1, $this->tab_top + 10 + $yp);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxpaymentamount + 1, $this->line_height, price($total), 0, 'R', 1);
		$pdf->SetFillColor(220, 220, 220);
	}
}
