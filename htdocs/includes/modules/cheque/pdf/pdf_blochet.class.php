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
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/compta/paiement/cheque/pdf/pdf_blochet.class.php
 *	\ingroup    banque
 *	\brief      Fichier de la classe permettant de generer les bordereau de remise de cheque
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 *	\class      BordereauChequeBlochet
 *	\brief      Classe permettant de g�n�rer les bordereau de remise de cheque
 */
class BordereauChequeBlochet extends FPDF
{
    var $error='';

	var $emetteur;	// Objet societe qui emet

	/**
	 *	\brief  Constructeur
	 */
	function BordereauChequeBlochet($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "blochet";

		$this->tab_top = 60;

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
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
		$this->line_per_page = 25;
		$this->tab_height = 200;	//$this->line_height * $this->line_per_page;
	}

	/**
	 *	\brief  	Fonction generant le rapport sur le disque
	 *	\param		_dir			Directory
	 *	\param		number			Number
	 *	\param		outputlangs		Lang output object
     *	\return	    int     		1=ok, 0=ko
	 */
	function write_file($_dir, $number, $outputlangs)
	{
		global $user,$conf,$langs;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");

		$dir = $_dir . "/".get_exdir($number,2,1).$number;

		if (! is_dir($dir))
		{
			$result=create_exdir($dir);

			if ($result < 0)
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return -1;
			}
		}

		$month = sprintf("%02d",$month);
		$year = sprintf("%04d",$year);
		$_file = $dir . "/bordereau-".$number.".pdf";

		// Protection et encryption du pdf
		if ($conf->global->PDF_SECURITY_ENCRYPTION)
		{
			$pdf = new FPDI_Protection('P','mm','A4');
			$pdfrights = array('print'); // Ne permet que l'impression du document
			$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
			$pdfownerpass = NULL; // Mot de passe du propri�taire, cr�� al�atoirement si pas d�fini
			$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
		}
		else
		{
			$pdf=new FPDI('P','mm',$this->format);
		}

		$pdf->Open();
		$pagenb=0;
		$pdf->SetDrawColor(128,128,128);

		$pdf->SetDrawColor(128,128,128);
		$pdf->SetTitle($outputlangs->convToOutputCharset($fac->ref));
		$pdf->SetSubject($outputlangs->transnoentities("CheckReceipt"));
		$pdf->SetCreator("Dolibarr ".DOL_VERSION);
		$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
		$pdf->SetKeyWords($outputlangs->transnoentities("CheckReceipt")." ".$number);
		if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

		$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
		$pdf->SetAutoPageBreak(1,0);


		$pages = intval($lignes / $this->line_per_page);

		if (($lignes % $this->line_per_page)>0)
		{
			$pages++;
		}

		if ($pages == 0)
		{
			// force to build at least one page if report has no lines
			$pages = 1;
		}

		$pdf->AddPage();

		$this->Header($pdf, 1, $pages, $outputlangs);

		$this->Body($pdf, 1, $outputlangs);

		// Pied de page
		$this->_pagefoot($pdf,'',$outputlangs);
		$pdf->AliasNbPages();

		$pdf->Close();

		$pdf->Output($_file);
		if (! empty($conf->global->MAIN_UMASK))
			@chmod($file, octdec($conf->global->MAIN_UMASK));

		return 1;   // Pas d'erreur
	}


	/**
     *      \brief      Renvoi le dernier message d'erreur de cr�ation de propale
     */
    function pdferror()
    {
        return $this->error;
    }

	/**
	 *	\brief  Generate Header
	 *	\param  pdf pdf object
	 *	\param  page current page number
	 *	\param  pages number of pages
	 */
	function Header(&$pdf, $page, $pages, $outputlangs)
	{
		global $langs;

		$outputlangs->load("compta");
		$outputlangs->load("banks");

		$title = $outputlangs->transnoentities("CheckReceipt");
		$pdf->SetFont('Arial','B',10);
		$pdf->Text(10, 10, $title);

		$pdf->SetFont('Arial','',10);
		$pdf->Text(10, 19, $outputlangs->transnoentities("Numero"));

		$pdf->SetFont('Arial','',10);
		$pdf->Text(10, 27, $outputlangs->transnoentities("Date") );

		$pdf->SetFont('Arial','',10);
		$pdf->Text(10, 35, $outputlangs->transnoentities("Owner"));

		$pdf->SetFont('Arial','B',10);
		$pdf->Text(32, 35, $outputlangs->convToOutputCharset($this->account->proprio));

		$pdf->SetFont('Arial','B',10);
		$pdf->Text(32, 19, $outputlangs->convToOutputCharset($this->number));

		$pdf->SetFont('Arial','B',10);
		$pdf->Text(32, 27, dol_print_date($this->date,"day",false,$outputlangs));


		$pdf->SetFont('Arial','',10);
		$pdf->Text(10, 43, $outputlangs->transnoentities("Account"));

		$pdf->SetFont('Arial','B',10);
		$pdf->Text(32,  43, $outputlangs->convToOutputCharset($this->account->code_banque));
		$pdf->Text(51,  43, $outputlangs->convToOutputCharset($this->account->code_guichet));
		$pdf->Text(68,  43, $outputlangs->convToOutputCharset($this->account->number));
		$pdf->Text(104, 43, $outputlangs->convToOutputCharset($this->account->cle_rib));

		$pdf->SetFont('Arial','',10);
		$pdf->Text(114, 19, $outputlangs->transnoentities("Signature"));

		$pdf->Rect(9, 47, 192, 7);
		$pdf->line(55, 47, 55, 54);
		$pdf->line(140, 47, 140, 54);
		$pdf->line(170, 47, 170, 54);

		$pdf->SetFont('Arial','',10);
		$pdf->Text(10, 52, $outputlangs->transnoentities("NumberOfCheques"));

		$pdf->SetFont('Arial','B',10);
		$pdf->Text(57, 52, $this->nbcheque);

		$pdf->SetFont('Arial','',10);
		$pdf->Text(148, 52, "Total");

		$pdf->SetFont('Arial','B',10);
		$pdf->SetXY (170, 47);
		$pdf->MultiCell(31, 7, price($this->amount), 0, 'C', 0);

		// Tableau
		$pdf->SetFont('Arial','',8);
		$pdf->Text(11,$this->tab_top + 6,$outputlangs->transnoentities("Num"));
		$pdf->line(30, $this->tab_top, 30, $this->tab_top + $this->tab_height + 10);

		$pdf->Text(31,$this->tab_top + 6,$outputlangs->transnoentities("Bank"));
		$pdf->line(100, $this->tab_top, 100, $this->tab_top + $this->tab_height + 10);
		$pdf->Text(101, $this->tab_top + 6, $outputlangs->transnoentities("CheckTransmitter"));

		$pdf->line(180, $this->tab_top, 180, $this->tab_top + $this->tab_height + 10);
		$pdf->SetXY (180, $this->tab_top);
		$pdf->MultiCell(20, 10, $outputlangs->transnoentities("Amount"), 0, 'R');
		$pdf->line(9, $this->tab_top + 10, 201, $this->tab_top + 10 );

		$pdf->Rect(9, $this->tab_top, 192, $this->tab_height + 10);

		$pdf->Rect(9, 14, 192, 31);
		$pdf->line(9, 22, 112, 22);
		$pdf->line(9, 30, 112, 30);
		$pdf->line(9, 38, 112, 38);

		$pdf->line(30, 14, 30, 45);
		$pdf->line(48, 38, 48, 45);
		$pdf->line(66, 38, 66, 45);
		$pdf->line(102, 38, 102, 45);
		$pdf->line(112, 14, 112, 45);

	}


	function Body(&$pdf, $page, $outputlangs)
	{
		// x=10 - Num
		// x=30 - Banque
		// x=100 - Emetteur
		$pdf->SetFont('Arial','', 9);
		$oldprowid = 0;
		$pdf->SetFillColor(220,220,220);
		$yp = 0;
		for ($j = 0 ; $j < sizeof($this->lines) ; $j++)
		{
			$pdf->SetXY (1, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(8, $this->line_height, $j+1, 0, 'R', 0);

			$pdf->SetXY (10, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(20, $this->line_height, $this->lines[$j]->num_chq?$this->lines[$j]->num_chq:'', 0, 'J', 0);

			$pdf->SetXY (30, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(70, $this->line_height, dol_trunc($outputlangs->convToOutputCharset($this->lines[$j]->bank_chq),44), 0, 'J', 0);

			$pdf->SetXY (100, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(80, $this->line_height, dol_trunc($outputlangs->convToOutputCharset($this->lines[$j]->emetteur_chq),50), 0, 'J', 0);

			$pdf->SetXY (180, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(20, $this->line_height, price($this->lines[$j]->amount_chq), 0, 'R', 0);

			$yp = $yp + $this->line_height;
		}
	}


	/**
	 *   	\brief      Show footer of page
	 *   	\param      pdf     		Object PDF
	 * 		\param		object			Object invoice
	 *      \param      outputlang		Object lang for output
	 * 		\remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf,$object,$outputlangs)
	{
		global $conf;

		//return pdf_pagefoot($pdf,$outputlangs,'BANK_CHEQUERECEIPT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
		$paramfreetext='BANK_CHEQUERECEIPT_FREE_TEXT';
		$marge_basse=$this->marge_basse;
		$marge_gauche=$this->marge_gauche;
		$page_hauteur=$this->page_hauteur;

		// Line of free text
		$ligne=(! empty($conf->global->$paramfreetext))?$outputlangs->convToOutputCharset($conf->global->$paramfreetext):"";

		$pdf->SetFont('Arial','',7);
		$pdf->SetDrawColor(224,224,224);

		// On positionne le debut du bas de page selon nbre de lignes de ce bas de page
		$nbofligne=dol_nboflines_bis($ligne);
		//print 'e'.$ligne.'t'.dol_nboflines($ligne);exit;
		$posy=$marge_basse + ($nbofligne*3) + ($ligne1?3:0) + ($ligne2?3:0);

		if ($ligne)	// Free text
		{
			$pdf->SetXY($marge_gauche,-$posy);
			$pdf->MultiCell(20000, 3, $ligne, 0, 'L', 0);	// Use a large value 20000, to not have automatic wrap. This make user understand, he need to add CR on its text.
			$posy-=($nbofligne*3);	// 6 of ligne + 3 of MultiCell
		}

		$pdf->SetY(-$posy);
		$pdf->line($marge_gauche, $page_hauteur-$posy, 200, $page_hauteur-$posy);
		$posy--;

		if ($ligne1)
		{
			$pdf->SetXY($marge_gauche,-$posy);
			$pdf->MultiCell(200, 2, $ligne1, 0, 'C', 0);
		}

		if ($ligne2)
		{
			$posy-=3;
			$pdf->SetXY($marge_gauche,-$posy);
			$pdf->MultiCell(200, 2, $ligne2, 0, 'C', 0);
		}

		$pdf->SetXY(-20,-$posy);
		$pdf->MultiCell(11, 2, $pdf->PageNo().'/{nb}', 0, 'R', 0);
	}

}

?>
