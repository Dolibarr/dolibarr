<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2006-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
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
	\file       htdocs/includes/modules/rapport/pdf_paiement.class.php
	\ingroup    banque
	\brief      Fichier de la classe permettant de g�n�rer les rapports de paiement
	\version    $Id$
*/
require_once(DOL_DOCUMENT_ROOT.'/includes/fpdf/fpdfi/fpdi_protection.php');


/**	
	\class      pdf_paiement
	\brief      Classe permettant de g�n�rer les rapports de paiement
*/
class pdf_paiement extends FPDF
{
	/**	
		\brief  Constructeur
		\param	db		handler acc�s base de donn�e
	*/
	function pdf_paiement($db)
	{ 
		global $langs;
		$langs->load("bills");
		
		$this->db = $db;
		$this->description = $langs->transnoentities("ListOfCustomerPayments");
		
		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;
		
		$this->tab_top = 30;
		
		$this->line_height = 5;
		$this->line_per_page = 25;
		$this->tab_height = 230;	//$this->line_height * $this->line_per_page;
		
	}
	
	
	/**
	 *	\brief  Fonction generant le rapport sur le disque
	 *	\param	_dir		repertoire
	 *	\param	month		mois du rapport
	 *	\param	year		annee du rapport
	 *	\param	outputlangs		Lang output object
	 */
	function write_file($_dir, $month, $year, $outputlangs)
	{
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because FPDF expect text to be encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->setPhpLang();
		
		$this->month=$month;
		$this->year=$year;
		
		$dir=$_dir.'/'.$year;
		
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
		$_file = $dir . "/payments-".$month."-".$year.".pdf";
		
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
		
		$sql = "SELECT ".$this->db->pdate("p.datep")." as dp, f.facnumber";
		//$sql .= ", c.libelle as paiement_type, p.num_paiement";
		$sql .= ", c.code as paiement_code, p.num_paiement";
		$sql .= ", p.amount as paiement_amount, f.total_ttc as facture_amount ";
		$sql .= ", pf.amount as pf_amount ";
		$sql .= ", p.rowid as prowid";
		$sql .= " FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."facture as f, ";
		$sql .= MAIN_DB_PREFIX."c_paiement as c, ".MAIN_DB_PREFIX."paiement_facture as pf";
		$sql .= " WHERE pf.fk_facture = f.rowid AND pf.fk_paiement = p.rowid";    
		$sql .= " AND p.fk_paiement = c.id ";
		$sql .= " AND date_format(p.datep, '%Y%m') = " . sprintf("%04d%02d",$year,$month);
		$sql .= " ORDER BY p.datep ASC, pf.fk_paiement ASC";

		dolibarr_syslog("pdf_paiement::write_file sql=".$sql);
		$result = $this->db->query($sql);
		if ($result)
		{
			$lignes = $this->db->num_rows($result);
			$i = 0;
			$var=True;
			
			while ($i < $lignes)
			{
				$objp = $this->db->fetch_object($result);
				$var=!$var;
				
				$lines[$i][0] = $objp->facnumber;
				$lines[$i][1] = dolibarr_print_date($objp->dp,"%d %B %Y",false,$outputlangs);
				//$lines[$i][2] = $objp->paiement_type ;
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
			dolibarr_print_error($this->db);
		}
		
		$pages = intval($lignes / $this->line_per_page);
		
		if (($lignes % $this->line_per_page)>0)
		{
			$pages++;
		}
		
		if ($pages == 0)
		{
			// force to build at least one page if report has no line
			$pages = 1;
		}
		/*
		for ($i = 0 ; $i < $pages ; $i++)
		{
		$pdf->AddPage();
		$this->Header($pdf, $i+1, $pages);
		$this->Body($pdf, $i+1, $lines);
		}
		*/
		
		$pdf->AddPage();
		
		$this->Header($pdf, 1, $pages, $outputlangs);
		
		$this->Body($pdf, 1, $lines, $outputlangs);
		
		$pdf->Output($_file);
		if (! empty($conf->global->MAIN_UMASK)) 
			@chmod($file, octdec($conf->global->MAIN_UMASK));

		$langs->setPhpLang();	// On restaure langue session
		return 1;
	}  

	/**	
	\brief  Generate Header
	\param  pdf pdf object
	\param  page current page number
	\param  pages number of pages
	*/  
	function Header(&$pdf, $page, $pages, $outputlangs)
	{
		global $langs;
		
		$title=$outputlangs->transnoentities("ListOfCustomerPayments");
		$title.=' - '.dolibarr_print_date(dolibarr_mktime(0,0,0,$this->month,1,$this->year),"%B %Y",false,$outputlangs);
		$pdf->SetFont('Arial','B',12);
		$pdf->Text(76, 10, $title);
		
		$pdf->SetFont('Arial','B',12);
		$pdf->Text(11, 16, $outputlangs->transnoentities("Date")." : ".dolibarr_print_date(time(),"day",false,$outputlangs));
		
		$pdf->SetFont('Arial','',12);
		$pdf->Text(11, 22, $outputlangs->transnoentities("Page")." : ".$page);
		
		$pdf->SetFont('Arial','',12);
		
		$pdf->Text(11,$this->tab_top + 6,'Date');
		
		$pdf->line(40, $this->tab_top, 40, $this->tab_top + $this->tab_height + 10);
		$pdf->Text(42, $this->tab_top + 6, $outputlangs->transnoentities("PaymentMode"));
		
		$pdf->line(80, $this->tab_top, 80, $this->tab_top + $this->tab_height + 10);
		$pdf->Text(82, $this->tab_top + 6, $outputlangs->transnoentities("Invoice"));
		
		$pdf->line(120, $this->tab_top, 120, $this->tab_top + $this->tab_height + 10);
		$pdf->Text(122, $this->tab_top + 6, $outputlangs->transnoentities("AmountInvoice"));
		
		$pdf->line(160, $this->tab_top, 160, $this->tab_top + $this->tab_height + 10);
		
		$pdf->SetXY (160, $this->tab_top);
		$pdf->MultiCell(40, 10, $outputlangs->transnoentities("AmountPayment"), 0, 'R');
		
		$pdf->line(10, $this->tab_top + 10, 200, $this->tab_top + 10 );

		$pdf->Rect(9, $this->tab_top, 192, $this->tab_height + 10);
	}


	function Body(&$pdf, $page, $lines, $outputlangs)
	{
		$pdf->SetFont('Arial','', 9);
		$oldprowid = 0;
		$pdf->SetFillColor(220,220,220);
		$yp = 0;
		for ($j = 0 ; $j < sizeof($lines) ; $j++)
		{
			$i = $j;
			if ($oldprowid <> $lines[$j][7])
			{
				if ($yp > 200)
				{
					$page++;
					$pdf->AddPage();
					$this->Header($pdf, $page, $pages);
					$pdf->SetFont('Arial','', 9);
					$yp = 0;
				}
				
				$pdf->SetXY (10, $this->tab_top + 10 + $yp);
				$pdf->MultiCell(30, $this->line_height, $lines[$j][1], 0, 'J', 1);
				
				$pdf->SetXY (40, $this->tab_top + 10 + $yp);
				$pdf->MultiCell(80, $this->line_height, $lines[$j][2].' '.$lines[$j][3], 0, 'J', 1);
				
				$pdf->SetXY (120, $this->tab_top + 10 + $yp);
				$pdf->MultiCell(40, $this->line_height, '', 0, 'J', 1);
				
				$pdf->SetXY (160, $this->tab_top + 10 + $yp);
				$pdf->MultiCell(40, $this->line_height, $lines[$j][4], 0, 'R', 1);
				$yp = $yp + 5;
			}
			
			$pdf->SetXY (80, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(40, $this->line_height, $lines[$j][0], 0, 'J', 0);
			
			$pdf->SetXY (120, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(40, $this->line_height, $lines[$j][5], 0, 'J', 0);
			
			$pdf->SetXY (160, $this->tab_top + 10 + $yp);
			$pdf->MultiCell(40, $this->line_height, $lines[$j][6], 0, 'R', 0);
			$yp = $yp + 5;
			
			if ($oldprowid <> $lines[$j][7])
			{
				$oldprowid = $lines[$j][7];
			}	
		}
	}
	
}

?>
