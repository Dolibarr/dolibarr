<?php
/* Copyright (C) 2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 * $Id$
 * $Source$
 */

/**
   \file       htdocs/includes/modules/rapport/pdf_muscadet.class.php
    \ingroup    banque
     \brief      Fichier de la classe permettant de générer les rapports de paiement
      \version    $Revision$
*/

require_once(FPDF_PATH.'fpdf.php');

/**	
   \class      BordereauChequeBlochet
   \brief      Classe permettant de générer les rapports de paiement
*/

class BordereauChequeBlochet
{
  /**	
     \brief  Constructeur
  */
  function BordereauChequeBlochet()
  { 
    global $langs;
    $langs->load("bills");
    
    $this->description = $langs->transnoentities("CheckReceipt");
    
    $this->tab_top = 60;
    
    $this->line_height = 5;
    $this->line_per_page = 25;
    $this->tab_height = 200;	//$this->line_height * $this->line_per_page;    
  }
  /**	
     \brief  Generate Header
     \param  pdf pdf object
     \param  page current page number
     \param  pages number of pages
  */  
  function Header(&$pdf, $page, $pages)
  {
    global $langs;
    
    $title = $this->description;
    $pdf->SetFont('Arial','B',10);
    $pdf->Text(11, 10, $title);

    $pdf->SetFont('Arial','',10);
    $pdf->Text(10, 19, $langs->transnoentities("Numero"));
    
    $pdf->SetFont('Arial','',10);
    $pdf->Text(10, 27, $langs->transnoentities("Date") );
        
    $pdf->SetFont('Arial','',8);
    $pdf->Text(11,$this->tab_top + 6,$langs->transnoentities("Bank"));
        
    $pdf->line(80, $this->tab_top, 80, $this->tab_top + $this->tab_height + 10);
    $pdf->Text(82, $this->tab_top + 6, $langs->transnoentities("CheckTransmitter"));
    
    $pdf->line(180, $this->tab_top, 180, $this->tab_top + $this->tab_height + 10);
   
    $pdf->SetXY (180, $this->tab_top);
    $pdf->MultiCell(20, 10, $langs->transnoentities("Amount"), 0, 'R');
    
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

    $pdf->SetFont('Arial','',10);
    $pdf->Text(10, 35, "Titulaire");
    $pdf->SetFont('Arial','',12);
    $pdf->Text(32, 35, $this->account->proprio);

    $pdf->SetFont('Arial','',12);
    $pdf->Text(32, 19, $this->number);

    $pdf->SetFont('Arial','',12);
    $pdf->Text(32, 27, dolibarr_print_date($this->date,"%d %b %Y"));


    $pdf->SetFont('Arial','',10);
    $pdf->Text(10, 43, "Compte");
    $pdf->SetFont('Arial','',12);
    $pdf->Text(32, 43, $this->account->code_banque);
    $pdf->Text(51, 43, $this->account->code_guichet);
    $pdf->Text(68, 43, $this->account->number);
    $pdf->Text(104, 43, $this->account->cle_rib);

    $pdf->SetFont('Arial','',10);
    $pdf->Text(114, 19, "Signature");

    $pdf->Rect(9, 47, 192, 7);
    $pdf->line(55, 47, 55, 54);
    $pdf->line(140, 47, 140, 54);
    $pdf->line(170, 47, 170, 54);

    $pdf->SetFont('Arial','',10);
    $pdf->Text(10, 52, "Nombre de chèque");
    $pdf->SetFont('Arial','',12);
    $pdf->Text(57, 52, $this->nbcheque);


    $pdf->SetFont('Arial','',10);
    $pdf->Text(148, 52, "Total");
    $pdf->SetFont('Arial','',12);

    $pdf->SetXY (170, 47);
    $pdf->MultiCell(31, 7, price($this->amount), 0, 'C', 0);


    $pdf->SetFont('Arial','',10);
  }
  
  
  function Body(&$pdf, $page)
  {
    $pdf->SetFont('Arial','', 9);
    $oldprowid = 0;
    $pdf->SetFillColor(220,220,220);
    $yp = 0;
    for ($j = 0 ; $j < sizeof($this->lines) ; $j++)
      {
	$pdf->SetXY (1, $this->tab_top + 10 + $yp);
	$pdf->MultiCell(8, $this->line_height, $j+1, 0, 'R', 0);

	$pdf->SetXY (10, $this->tab_top + 10 + $yp);
	$pdf->MultiCell(40, $this->line_height, $this->lines[$j][0], 0, 'J', 0);
	
	$pdf->SetXY (80, $this->tab_top + 10 + $yp);
	$pdf->MultiCell(40, $this->line_height, $this->lines[$j][1], 0, 'J', 0);
	
	$pdf->SetXY (160, $this->tab_top + 10 + $yp);
	$pdf->MultiCell(40, $this->line_height, $this->lines[$j][2], 0, 'R', 0);
	$yp = $yp + 5;	
      }
  }
  /**
     \brief  Fonction générant le rapport sur le disque
     \param	_dir		repertoire
     \param	month		mois du rapport
     \param	year		annee du rapport
  */
  function write_pdf_file($_dir, $number)
  {
    global $langs;
    
    $dir = $_dir . "/".get_exdir($number);
    
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
    $_file = $dir . "bordereau-".$number.".pdf";
    
    $pdf = new FPDF('P','mm','A4');
    $pdf->Open();
    
    
    $pages = intval($lignes / $this->line_per_page);
    
    if (($lignes % $this->line_per_page)>0)
      {
	$pages++;
      }
    
    if ($pages == 0)
      {
	// force à générer au moins une page si le rapport ne contient aucune ligne
	$pages = 1;
      }
    
    $pdf->AddPage();
    
    $this->Header($pdf, 1, $pages);
    
    $this->Body($pdf, 1);
    
    $pdf->Output($_file);
  }  
}

?>
