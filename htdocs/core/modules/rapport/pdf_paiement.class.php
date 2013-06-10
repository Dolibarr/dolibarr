<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/core/modules/rapport/pdf_paiement.class.php
 *	\ingroup    banque
 *	\brief      File to build payment reports
 */
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';


/**
 *	Classe permettant de generer les rapports de paiement
 */
class pdf_paiement
{
	/**
     *  Constructor
     *
     *  @param      DoliDb		$db      Database handler
	 */
	function __construct($db)
	{
		global $langs;
		$langs->load("bills");
		$langs->load("compta");

		$this->db = $db;
		$this->description = $langs->transnoentities("ListOfCustomerPayments");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->tab_top = 30;

		$this->line_height = 5;
		$this->line_per_page = 25;
		$this->tab_height = 230;	//$this->line_height * $this->line_per_page;

		$this->posxdate=$this->marge_gauche+2;
		$this->posxpaymenttype=42;
		$this->posxinvoice=82;
		$this->posxinvoiceamount=122;
		$this->posxpaymentamount=162;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxpaymenttype-=10;
			$this->posxinvoice-=0;
			$this->posxinvoiceamount-=10;
			$this->posxpaymentamount-=20;
		}

	}


	/**
	 *	Fonction generant la rapport sur le disque
	 *
	 *	@param	string	$_dir			repertoire
	 *	@param	int		$month			mois du rapport
	 *	@param	int		$year			annee du rapport
	 *	@param	string	$outputlangs	Lang output object
	 *	@return	int						<0 if KO, >0 if OK
	 */
	function write_file($_dir, $month, $year, $outputlangs)
	{
		include_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';

		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$this->month=$month;
		$this->year=$year;

		$dir=$_dir.'/'.$year;

		if (! is_dir($dir))
		{
			$result=dol_mkdir($dir);
			if ($result < 0)
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return -1;
			}
		}

		$month = sprintf("%02d",$month);
		$year = sprintf("%04d",$year);
		$file = $dir . "/payments-".$year."-".$month.".pdf";

        $pdf=pdf_getInstance($this->format);
        $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance

        if (class_exists('TCPDF'))
        {
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);
        }
        $pdf->SetFont(pdf_getPDFFont($outputlangs));

        $num=0;
        $lines=array();

		$sql = "SELECT p.datep as dp, f.facnumber";
		//$sql .= ", c.libelle as paiement_type, p.num_paiement";
		$sql.= ", c.code as paiement_code, p.num_paiement";
		$sql.= ", p.amount as paiement_amount, f.total_ttc as facture_amount ";
		$sql.= ", pf.amount as pf_amount ";
		$sql.= ", p.rowid as prowid";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."facture as f,";
		$sql.= " ".MAIN_DB_PREFIX."c_paiement as c, ".MAIN_DB_PREFIX."paiement_facture as pf";
		$sql.= " WHERE pf.fk_facture = f.rowid AND pf.fk_paiement = p.rowid";
		$sql.= " AND f.entity = ".$conf->entity;
		$sql.= " AND p.fk_paiement = c.id ";
		$sql.= " AND p.datep BETWEEN '".$this->db->idate(dol_get_first_day($year,$month))."' AND '".$this->db->idate(dol_get_last_day($year,$month))."'";
		$sql.= " ORDER BY p.datep ASC, pf.fk_paiement ASC";

		dol_syslog(get_class($this)."::write_file sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$num = $this->db->num_rows($result);
			$i = 0;
			$var=True;

			while ($i < $num)
			{
				$objp = $this->db->fetch_object($result);
				$var=!$var;

				$lines[$i][0] = $objp->facnumber;
				$lines[$i][1] = dol_print_date($this->db->jdate($objp->dp),"day",false,$outputlangs,true);
				$lines[$i][2] = $langs->transnoentities("PaymentTypeShort".$objp->paiement_code);
				$lines[$i][3] = $objp->num_paiement;
				$lines[$i][4] = price($objp->paiement_amount);
				$lines[$i][5] = price($objp->facture_amount);
				$lines[$i][6] = price($objp->pf_amount);
				$lines[$i][7] = $objp->prowid;
				$i++;
			}
		}
		else
		{
			dol_print_error($this->db);
		}

		$pages = intval($num / $this->line_per_page);

		if (($lines % $this->line_per_page)>0)
		{
			$pages++;
		}

		if ($pages == 0)
		{
			// force to build at least one page if report has no line
			$pages = 1;
		}

		$pdf->Open();
		$pagenb=0;
		$pdf->SetDrawColor(128,128,128);

		$pdf->SetTitle($outputlangs->transnoentities("Payments"));
		$pdf->SetSubject($outputlangs->transnoentities("Payments"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
		//$pdf->SetKeyWords();
		if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
		$pdf->SetAutoPageBreak(1,0);

		// New page
		$pdf->AddPage();
		$pagenb++;
		$this->_pagehead($pdf, $pages, 1, $outputlangs);
		$pdf->SetFont('','', 9);
		$pdf->MultiCell(0, 3, '');		// Set interline to 3
		$pdf->SetTextColor(0,0,0);


		$this->Body($pdf, 1, $lines, $outputlangs);

		if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

		$pdf->Close();

		$pdf->Output($file,'F');
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));

		return 1;
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			&$pdf     		Object PDF
	 *  @param  int			$page	     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $page, $showaddress, $outputlangs)
	{
		global $langs;

		// Do not add the BACKGROUND as this is a report
		//pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$title=$outputlangs->transnoentities("ListOfCustomerPayments");
		$title.=' - '.dol_print_date(dol_mktime(0,0,0,$this->month,1,$this->year),"%B %Y",false,$outputlangs,true);
		$pdf->SetFont('','B',$default_font_size + 1);
		$pdf->SetXY($this->marge_gauche,10);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->marge_gauche, 2, $title, 0, 'C');

		$pdf->SetFont('','',$default_font_size);

        $pdf->SetXY($this->posxdate, 16);
		$pdf->MultiCell(80, 2, $outputlangs->transnoentities("DateBuild")." : ".dol_print_date(time(),"day",false,$outputlangs,true), 0, 'L');

        $pdf->SetXY($this->posxdate, 22);
		$pdf->MultiCell(80, 2, $outputlangs->transnoentities("Page")." : ".$page, 0, 'L');


		// Title line
        $pdf->SetXY($this->posxdate, $this->tab_top+2);
		$pdf->MultiCell($this->posxpaymenttype - $this->posxdate, 2, 'Date');

		$pdf->line($this->posxpaymenttype - 1, $this->tab_top, $this->posxpaymenttype - 1, $this->tab_top + $this->tab_height + 10);
        $pdf->SetXY($this->posxpaymenttype, $this->tab_top+2);
		$pdf->MultiCell($this->posxinvoice - $this->posxpaymenttype, 2, $outputlangs->transnoentities("PaymentMode"), 0, 'L');

		$pdf->line($this->posxinvoice - 1, $this->tab_top, $this->posxinvoice - 1, $this->tab_top + $this->tab_height + 10);
        $pdf->SetXY($this->posxinvoice, $this->tab_top+2);
		$pdf->MultiCell($this->posxinvoiceamount - $this->posxinvoice, 2, $outputlangs->transnoentities("Invoice"), 0, 'L');

		$pdf->line($this->posxinvoiceamount - 1, $this->tab_top, $this->posxinvoiceamount - 1, $this->tab_top + $this->tab_height + 10);
        $pdf->SetXY($this->posxinvoiceamount, $this->tab_top+2);
		$pdf->MultiCell($this->posxpaymentamount - $this->posxinvoiceamount - 1, 2, $outputlangs->transnoentities("AmountInvoice"), 0, 'R');

		$pdf->line($this->posxpaymentamount - 1, $this->tab_top, $this->posxpaymentamount - 1, $this->tab_top + $this->tab_height + 10);
        $pdf->SetXY($this->posxpaymentamount, $this->tab_top+2);
		$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxpaymentamount - 1, 2, $outputlangs->transnoentities("AmountPayment"), 0, 'R');

		$pdf->line($this->marge_gauche, $this->tab_top + 10, $this->page_largeur - $this->marge_droite, $this->tab_top + 10);

		$pdf->Rect($this->marge_gauche, $this->tab_top, $this->page_largeur - $this->marge_gauche - $this->marge_droite, $this->tab_height + 10);
	}


	/**
	 *	Output body
	 *
	 *	@param	PDF			&$pdf			PDF object
	 *	@param	string		$page			Page
	 *	@param	array		$lines			Array of lines
	 *	@param	Translate	$outputlangs	Object langs
	 *	@return	void
	 */
	function Body(&$pdf, $page, $lines, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('','', $default_font_size - 1);
		$oldprowid = 0;
		$pdf->SetFillColor(220,220,220);
		$yp = 0;
		$numlines=count($lines);
		for ($j = 0 ; $j < $numlines ; $j++)
		{
			$i = $j;
			if ($oldprowid <> $lines[$j][7])
			{
				if ($yp > 200)
				{
					$page++;
					$pdf->AddPage();
					$this->_pagehead($pdf, $page, 0, $outputlangs);
					$pdf->SetFont('','', $default_font_size - 1);
					$yp = 0;
				}

				$pdf->SetXY($this->posxdate - 1, $this->tab_top + 10 + $yp);
				$pdf->MultiCell($this->posxpaymenttype - $this->posxdate + 1, $this->line_height, $lines[$j][1], 0, 'L', 1);

				$pdf->SetXY($this->posxpaymenttype, $this->tab_top + 10 + $yp);
				$pdf->MultiCell($this->posxinvoiceamount - $this->posxpaymenttype, $this->line_height, $lines[$j][2].' '.$lines[$j][3], 0, 'L', 1);

				$pdf->SetXY($this->posxinvoiceamount, $this->tab_top + 10 + $yp);
				$pdf->MultiCell($this->posxpaymentamount- $this->posxinvoiceamount, $this->line_height, '', 0, 'R', 1);

				$pdf->SetXY($this->posxpaymentamount, $this->tab_top + 10 + $yp);
				$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxpaymentamount, $this->line_height, $lines[$j][4], 0, 'R', 1);
				$yp = $yp + 5;
			}

			// Invoice number
			$pdf->SetXY($this->posxinvoice, $this->tab_top + 10 + $yp);
			$pdf->MultiCell($this->posxinvoiceamount - $this->posxdate, $this->line_height, $lines[$j][0], 0, 'L', 0);

			// Invoice amount
			$pdf->SetXY($this->posxinvoiceamount, $this->tab_top + 10 + $yp);
			$pdf->MultiCell($this->posxpaymentamount- $this->posxinvoiceamount - 1, $this->line_height, $lines[$j][5], 0, 'R', 0);

			// Payment amount
			$pdf->SetXY($this->posxpaymentamount, $this->tab_top + 10 + $yp);
			$pdf->MultiCell($this->page_largeur - $this->marge_droite - $this->posxpaymentamount, $this->line_height, $lines[$j][6], 0, 'R', 0);
			$yp = $yp + 5;

			if ($oldprowid <> $lines[$j][7])
			{
				$oldprowid = $lines[$j][7];
			}
		}
	}

}

?>
