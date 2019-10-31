<?php
/* Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009-2015 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2016      Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/core/modules/cheque/doc/pdf_blochet.class.php
 *	\ingroup    banque
 *	\brief      File to build cheque deposit receipts
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/cheque/modules_chequereceipts.php';


/**
 *	Class of file to build cheque deposit receipts
 */
class BordereauChequeBlochet extends ModeleChequeReceipts
{
	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	public function __construct($db)
	{
		global $conf,$langs,$mysoc;

		// Load traductions files required by page
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "blochet";

		$this->tab_top = 60;

		// Page size for A4 format
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

        // Retrieves transmitter
        $this->emetteur=$mysoc;
        if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang, -2);    // By default if not defined

        // Define column position
        $this->line_height = 5;
		$this->line_per_page = 40;
		$this->tab_height = 200;	//$this->line_height * $this->line_per_page;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Fonction to generate document on disk
	 *
	 *	@param	RemiseCheque	$object			Object RemiseCheque
	 *	@param	string			$_dir			Directory
	 *	@param	string			$number			Number
	 *	@param	Translate		$outputlangs	Lang output object
     *	@return	int     						1=ok, 0=ko
	 */
	public function write_file($object, $_dir, $number, $outputlangs)
	{
        // phpcs:enable
		global $user,$conf,$langs,$hookmanager;

        if (! is_object($outputlangs)) $outputlangs=$langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        $sav_charset_output=$outputlangs->charset_output;
        if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

        // Load traductions files required by page
		$outputlangs->loadLangs(array("main", "companies", "bills", "products", "compta"));

		$dir = $_dir . "/".get_exdir($number, 0, 1, 0, $object, 'cheque').$number;

		if (! is_dir($dir))
		{
			$result=dol_mkdir($dir);

			if ($result < 0)
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		$file = $dir . "/bordereau-".$number.".pdf";

		// Add pdfgeneration hook
		if (! is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters=array('file'=>$file, 'outputlangs'=>$outputlangs);
		global $action;
		$reshook=$hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

		// Create PDF instance
        $pdf=pdf_getInstance($this->format);
        $heightforinfotot = 50;	// Height reserved to output the info and total part
        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
        $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
        if ($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS >0) $heightforfooter+= 6;
        $pdf->SetAutoPageBreak(1, 0);

        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

		$pdf->Open();
		$pagenb=0;
		$pdf->SetDrawColor(128, 128, 128);

		$pdf->SetTitle($outputlangs->transnoentities("CheckReceipt")." ".$number);
		$pdf->SetSubject($outputlangs->transnoentities("CheckReceipt"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		$pdf->SetKeyWords($outputlangs->transnoentities("CheckReceipt")." ".$number);
		if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

		$nboflines=count($this->lines);

		// Define nb of page
		$pages = intval($nboflines / $this->line_per_page);
		if (($nboflines % $this->line_per_page)>0)
		{
			$pages++;
		}
		if ($pages == 0)
		{
			// force to build at least one page if report has no lines
			$pages = 1;
		}

		$pdf->AddPage();
        $pagenb++;
		$this->Header($pdf, $pagenb, $pages, $outputlangs);

		$this->Body($pdf, $pagenb, $pages, $outputlangs);

		// Pied de page
		$this->_pagefoot($pdf, '', $outputlangs);
		if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

		$pdf->Close();

		$pdf->Output($file, 'F');

		// Add pdfgeneration hook
		if (! is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('pdfgeneration'));
		$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
		global $action;
		$reshook=$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
		if ($reshook < 0)
		{
		    $this->error = $hookmanager->error;
		    $this->errors = $hookmanager->errors;
		}

		if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));

		$this->result = array('fullpath'=>$file);

        $outputlangs->charset_output=$sav_charset_output;
	    return 1;   // No error
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Generate Header
	 *
	 *	@param  PDF			$pdf        	Pdf object
	 *	@param  int			$page        	Current page number
	 *	@param  int			$pages       	Total number of pages
	 *	@param	Translate	$outputlangs	Object language for output
	 *	@return	void
	 */
	public function Header(&$pdf, $page, $pages, $outputlangs)
	{
        // phpcs:enable
		global $langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Load traductions files required by page
		$outputlangs->loadLangs(array("compta", "banks"));

		$title = $outputlangs->transnoentities("CheckReceipt");
		$pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetXY(10, 8);
        $pdf->MultiCell(0, 2, $title, 0, 'L');

		$pdf->SetFont('', '', $default_font_size);
        $pdf->SetXY(10, 15);
		$pdf->MultiCell(22, 2, $outputlangs->transnoentities("Ref"), 0, 'L');
        $pdf->SetXY(32, 15);
		$pdf->SetFont('', '', $default_font_size);
        $pdf->MultiCell(60, 2, $outputlangs->convToOutputCharset($this->ref.($this->ref_ext?" - ".$this->ref_ext:'')), 0, 'L');

		$pdf->SetFont('', '', $default_font_size);
        $pdf->SetXY(10, 20);
        $pdf->MultiCell(22, 2, $outputlangs->transnoentities("Date"), 0, 'L');
        $pdf->SetXY(32, 20);
        $pdf->SetFont('', '', $default_font_size);
        $pdf->MultiCell(60, 2, dol_print_date($this->date, "day", false, $outputlangs));

		$pdf->SetFont('', '', $default_font_size);
        $pdf->SetXY(10, 26);
        $pdf->MultiCell(22, 2, $outputlangs->transnoentities("Owner"), 0, 'L');
		$pdf->SetFont('', '', $default_font_size);
        $pdf->SetXY(32, 26);
        $pdf->MultiCell(80, 2, $outputlangs->convToOutputCharset($this->account->proprio), 0, 'L');

		$pdf->SetFont('', '', $default_font_size);
        $pdf->SetXY(10, 32);
        $pdf->MultiCell(0, 2, $outputlangs->transnoentities("Account"), 0, 'L');
        pdf_bank($pdf, $outputlangs, 32, 32, $this->account, 1);

		$pdf->SetFont('', '', $default_font_size);
        $pdf->SetXY(114, 15);
		$pdf->MultiCell(40, 2, $outputlangs->transnoentities("Signature"), 0, 'L');

        $pdf->Rect(9, 14, 192, 35);
        $pdf->line(9, 19, 112, 19);
        $pdf->line(9, 25, 112, 25);
        //$pdf->line(9, 31, 201, 31);
        $pdf->line(9, 31, 112, 31);

        $pdf->line(30, 14, 30, 49);
        $pdf->line(112, 14, 112, 49);

		// Number of cheques
		$posy=51;
		$pdf->Rect(9, $posy, 192, 6);
		$pdf->line(55, $posy, 55, $posy+6);
		$pdf->line(140, $posy, 140, $posy+6);
		$pdf->line(170, $posy, 170, $posy+6);

		$pdf->SetFont('', '', $default_font_size);
		$pdf->SetXY(10, $posy+1);
		$pdf->MultiCell(40, 2, $outputlangs->transnoentities("NumberOfCheques"), 0, 'L');

		$pdf->SetFont('', 'B', $default_font_size);
        $pdf->SetXY(57, $posy+1);
        $pdf->MultiCell(40, 2, $this->nbcheque, 0, 'L');

		$pdf->SetFont('', '', $default_font_size);
        $pdf->SetXY(148, $posy+1);
		$pdf->MultiCell(40, 2, $langs->trans("Total"));

		$pdf->SetFont('', 'B', $default_font_size);
		$pdf->SetXY(170, $posy+1);
		$pdf->MultiCell(31, 2, price($this->amount), 0, 'C', 0);

		// Tableau
		$pdf->SetFont('', '', $default_font_size - 2);
		$pdf->SetXY(11, $this->tab_top+2);
		$pdf->MultiCell(40, 2, $outputlangs->transnoentities("Num"), 0, 'L');
		$pdf->line(40, $this->tab_top, 40, $this->tab_top + $this->tab_height + 10);

		$pdf->SetXY(41, $this->tab_top+2);
        $pdf->MultiCell(40, 2, $outputlangs->transnoentities("Bank"), 0, 'L');
		$pdf->line(100, $this->tab_top, 100, $this->tab_top + $this->tab_height + 10);

        $pdf->SetXY(101, $this->tab_top+2);
        $pdf->MultiCell(40, 2, $outputlangs->transnoentities("CheckTransmitter"), 0, 'L');
		$pdf->line(180, $this->tab_top, 180, $this->tab_top + $this->tab_height + 10);

		$pdf->SetXY(180, $this->tab_top+2);
		$pdf->MultiCell(20, 2, $outputlangs->transnoentities("Amount"), 0, 'R');
		$pdf->line(9, $this->tab_top + 8, 201, $this->tab_top + 8);

		$pdf->Rect(9, $this->tab_top, 192, $this->tab_height + 10);
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Output array
	 *
	 *	@param	PDF			$pdf			PDF object
	 *	@param	int			$pagenb			Page nb
	 *	@param	int			$pages			Pages
	 *	@param	Translate	$outputlangs	Object lang
	 *	@return	void
	 */
	public function Body(&$pdf, $pagenb, $pages, $outputlangs)
	{
        // phpcs:enable
		// x=10 - Num
		// x=30 - Banque
		// x=100 - Emetteur
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetFont('', '', $default_font_size - 1);
		$oldprowid = 0;
		$pdf->SetFillColor(220, 220, 220);
		$yp = 0;
		$lineinpage=0;
		$num=count($this->lines);
		for ($j = 0; $j < $num; $j++)
		{
		    $lineinpage++;

			$pdf->SetXY(1, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(8, $this->line_height, $j+1, 0, 'R', 0);

			$pdf->SetXY(10, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(30, $this->line_height, $this->lines[$j]->num_chq?$this->lines[$j]->num_chq:'', 0, 'L', 0);

			$pdf->SetXY(40, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(70, $this->line_height, dol_trunc($outputlangs->convToOutputCharset($this->lines[$j]->bank_chq), 44), 0, 'L', 0);

			$pdf->SetXY(100, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(80, $this->line_height, dol_trunc($outputlangs->convToOutputCharset($this->lines[$j]->emetteur_chq), 50), 0, 'L', 0);

			$pdf->SetXY(180, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(20, $this->line_height, price($this->lines[$j]->amount_chq), 0, 'R', 0);

			$yp = $yp + $this->line_height;

			if ($lineinpage >= $this->line_per_page && $j < (count($this->lines)-1))
			{
			    $lineinpage=0; $yp=0;

                // New page
                $pdf->AddPage();
                $pagenb++;
                $this->Header($pdf, $pagenb, $pages, $outputlangs);
                $pdf->SetFont('', '', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');      // Set interline to 3
                $pdf->SetTextColor(0, 0, 0);
			}
		}
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.PublicUnderscore
	/**
	 *  Show footer of page. Need this->emetteur object
     *
	 *  @param	PDF			$pdf     			PDF
	 *  @param	Object		$object				Object to show
	 *  @param	Translate	$outputlangs		Object lang for output
	 *  @param	int			$hidefreetext		1=Hide free text
	 *  @return	void
	 */
	protected function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;

		// Line of free text
		$newfreetext='';
		$paramfreetext='BANK_CHEQUERECEIPT_FREE_TEXT';
		if (! empty($conf->global->$paramfreetext))
		{
		    $newfreetext=make_substitutions($conf->global->$paramfreetext, $substitutionarray);
		}

		return pdf_pagefoot($pdf, $outputlangs, $newfreetext, $this->emetteur, $this->marge_basse, $this->marge_gauche, $this->page_hauteur, $object, $showdetails, $hidefreetext);
	}
}
