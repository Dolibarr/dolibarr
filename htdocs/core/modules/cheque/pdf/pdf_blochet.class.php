<?php
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/cheque/pdf/pdf_blochet.class.php
 *	\ingroup    banque
 *	\brief      File to build cheque deposit receipts
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/cheque/pdf/modules_chequereceipts.php';


/**
 *	Class of file to build cheque deposit receipts
 */
class BordereauChequeBlochet extends ModeleChequeReceipts
{
    var $error='';

	var $emetteur;	// Objet societe qui emet

	/**
	 *	Constructor
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "blochet";

		$this->tab_top = 60;

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=20;
		$this->marge_haute=10;
		$this->marge_basse=10;

        // Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini

        // Defini position des colonnes
        $this->line_height = 5;
		$this->line_per_page = 40;
		$this->tab_height = 200;	//$this->line_height * $this->line_per_page;
	}

	/**
	 *	Fonction generant le rapport sur le disque
	 *
	 *	@param	string		$_dir			Directory
	 *	@param	string		$number			Number
	 *	@param	Translate	$outputlangs	Lang output object
     *	@return	int     					1=ok, 0=ko
	 */
	function write_file($_dir, $number, $outputlangs)
	{
		global $user,$conf,$langs;

        if (! is_object($outputlangs)) $outputlangs=$langs;
        // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
        $sav_charset_output=$outputlangs->charset_output;
        if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
        $outputlangs->load("compta");

		$dir = $_dir . "/".get_exdir($number,2,1).$number;

		if (! is_dir($dir))
		{
			$result=dol_mkdir($dir);

			if ($result < 0)
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return -1;
			}
		}

		$_file = $dir . "/bordereau-".$number.".pdf";

		// Create PDF instance
        $pdf=pdf_getInstance($this->format);
        $heightforinfotot = 50;	// Height reserved to output the info and total part
        $heightforfooter = 25;	// Height reserved to output the footer (value include bottom margin)
        $pdf->SetAutoPageBreak(1,0);

        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

		$pdf->Open();
		$pagenb=0;
		$pdf->SetDrawColor(128,128,128);

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
		$this->_pagefoot($pdf,'',$outputlangs);
		$pdf->AliasNbPages();

		$pdf->Close();

		$pdf->Output($_file,'F');
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($_file, octdec($conf->global->MAIN_UMASK));

        $outputlangs->charset_output=$sav_charset_output;
	    return 1;   // Pas d'erreur
	}


	/**
	 *	Generate Header
	 *
	 *	@param  PDF			&$pdf        	Pdf object
	 *	@param  int			$page        	Current page number
	 *	@param  int			$pages       	Total number of pages
	 *	@param	Translate	$outputlangs	Object language for output
	 *	@return	void
	 */
	function Header(&$pdf, $page, $pages, $outputlangs)
	{
		global $langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$outputlangs->load("compta");
		$outputlangs->load("banks");

		$title = $outputlangs->transnoentities("CheckReceipt");
		$pdf->SetFont('','B', $default_font_size);
        $pdf->SetXY(10,8);
        $pdf->MultiCell(0,2,$title,0,'L');

		$pdf->SetFont('','', $default_font_size);
        $pdf->SetXY(10,15);
		$pdf->MultiCell(22,2,$outputlangs->transnoentities("Ref"),0,'L');
        $pdf->SetXY(32,15);
		$pdf->SetFont('','', $default_font_size);
        $pdf->MultiCell(60, 2, $outputlangs->convToOutputCharset($this->number), 0, 'L');

		$pdf->SetFont('','', $default_font_size);
        $pdf->SetXY(10,20);
        $pdf->MultiCell(22,2,$outputlangs->transnoentities("Date"),0,'L');
        $pdf->SetXY(32,20);
        $pdf->SetFont('','', $default_font_size);
        $pdf->MultiCell(60, 2, dol_print_date($this->date,"day",false,$outputlangs));

		$pdf->SetFont('','', $default_font_size);
        $pdf->SetXY(10,26);
        $pdf->MultiCell(22,2,$outputlangs->transnoentities("Owner"),0,'L');
		$pdf->SetFont('','', $default_font_size);
        $pdf->SetXY(32,26);
        $pdf->MultiCell(60,2,$outputlangs->convToOutputCharset($this->account->proprio),0,'L');

		$pdf->SetFont('','', $default_font_size);
        $pdf->SetXY(10,32);
        $pdf->MultiCell(0,2,$outputlangs->transnoentities("Account"),0,'L');
        pdf_bank($pdf,$outputlangs,32,32,$this->account,1);

		$pdf->SetFont('','', $default_font_size);
        $pdf->SetXY(114,15);
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

		$pdf->SetFont('','', $default_font_size);
		$pdf->SetXY(10,$posy+1);
		$pdf->MultiCell(40, 2, $outputlangs->transnoentities("NumberOfCheques"), 0, 'L');

		$pdf->SetFont('','B', $default_font_size);
        $pdf->SetXY(57,$posy+1);
        $pdf->MultiCell(40, 2, $this->nbcheque, 0, 'L');

		$pdf->SetFont('','', $default_font_size);
        $pdf->SetXY(148,$posy+1);
		$pdf->MultiCell(40, 2, $langs->trans("Total"));

		$pdf->SetFont('','B', $default_font_size);
		$pdf->SetXY(170, $posy+1);
		$pdf->MultiCell(31, 2, price($this->amount), 0, 'C', 0);

		// Tableau
		$pdf->SetFont('','', $default_font_size - 2);
		$pdf->SetXY(11, $this->tab_top+2);
		$pdf->MultiCell(40,2,$outputlangs->transnoentities("Num"), 0, 'L');
		$pdf->line(40, $this->tab_top, 40, $this->tab_top + $this->tab_height + 10);

		$pdf->SetXY(41, $this->tab_top+2);
        $pdf->MultiCell(40,2,$outputlangs->transnoentities("Bank"), 0, 'L');
		$pdf->line(100, $this->tab_top, 100, $this->tab_top + $this->tab_height + 10);

        $pdf->SetXY(101, $this->tab_top+2);
        $pdf->MultiCell(40,2,$outputlangs->transnoentities("CheckTransmitter"), 0, 'L');
		$pdf->line(180, $this->tab_top, 180, $this->tab_top + $this->tab_height + 10);

		$pdf->SetXY(180, $this->tab_top+2);
		$pdf->MultiCell(20,2,$outputlangs->transnoentities("Amount"), 0, 'R');
		$pdf->line(9, $this->tab_top + 8, 201, $this->tab_top + 8);

		$pdf->Rect(9, $this->tab_top, 192, $this->tab_height + 10);
	}


	/**
	 *	Output array
	 *
	 *	@param	PDF			&$pdf			PDF object
	 *	@param	int			$pagenb			Page nb
	 *	@param	int			$pages			Pages
	 *	@param	Translate	$outputlangs	Object lang
	 *	@return	void
	 */
	function Body(&$pdf, $pagenb, $pages, $outputlangs)
	{
		// x=10 - Num
		// x=30 - Banque
		// x=100 - Emetteur
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetFont('','', $default_font_size - 1);
		$oldprowid = 0;
		$pdf->SetFillColor(220,220,220);
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
			$pdf->MultiCell(70, $this->line_height, dol_trunc($outputlangs->convToOutputCharset($this->lines[$j]->bank_chq),44), 0, 'L', 0);

			$pdf->SetXY(100, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(80, $this->line_height, dol_trunc($outputlangs->convToOutputCharset($this->lines[$j]->emetteur_chq),50), 0, 'L', 0);

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
                $pdf->SetFont('','', $default_font_size - 1);
                $pdf->MultiCell(0, 3, '');      // Set interline to 3
                $pdf->SetTextColor(0,0,0);
			}
		}
	}


	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @return	void
	 */
	function _pagefoot(&$pdf,$object,$outputlangs)
	{
		global $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		//return pdf_pagefoot($pdf,$outputlangs,'BANK_CHEQUERECEIPT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
		$paramfreetext='BANK_CHEQUERECEIPT_FREE_TEXT';
		$marge_basse=$this->marge_basse;
		$marge_gauche=$this->marge_gauche;
		$page_hauteur=$this->page_hauteur;

		// Line of free text
		$line=(! empty($conf->global->$paramfreetext))?$outputlangs->convToOutputCharset($conf->global->$paramfreetext):"";

		$pdf->SetFont('','', $default_font_size - 3);
		$pdf->SetDrawColor(224,224,224);

		// On positionne le debut du bas de page selon nbre de lignes de ce bas de page
		$nbofline=dol_nboflines_bis($line,0,$outputlangs->charset_output);
		//print 'e'.$line.'t'.dol_nboflines($line);exit;
		$posy=$marge_basse + ($nbofline*3);

		if ($line)	// Free text
		{
			$pdf->SetXY($marge_gauche,-$posy);
			$pdf->MultiCell(20000, 3, $line, 0, 'L', 0);	// Use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
			$posy-=($nbofline*3);	// 6 of ligne + 3 of MultiCell
		}

		$pdf->SetY(-$posy);
		$pdf->line($marge_gauche, $page_hauteur-$posy, 200, $page_hauteur-$posy);
		$posy--;

		/*if ($line1)
		{
			$pdf->SetXY($marge_gauche,-$posy);
			$pdf->MultiCell(200, 2, $line1, 0, 'C', 0);
		}

		if ($line2)
		{
			$posy-=3;
			$pdf->SetXY($marge_gauche,-$posy);
			$pdf->MultiCell(200, 2, $line2, 0, 'C', 0);
		}*/

        // Show page nb only on iso languages (so default Helvetica font)
        if (pdf_getPDFFont($outputlangs) == 'Helvetica')
        {
    		$pdf->SetXY(-20,-$posy);
            $pdf->MultiCell(11, 2, $pdf->PageNo().'/'.$pdf->getAliasNbPages(), 0, 'R', 0);
        }
	}

}

?>
