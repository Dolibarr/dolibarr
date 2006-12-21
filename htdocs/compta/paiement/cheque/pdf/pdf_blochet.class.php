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
     \param	db		handler accès base de donnée
  */
  function BordereauChequeBlochet($db=0)
  { 
    global $langs;
    $langs->load("bills");
    
    $this->db = $db;
    $this->description = $langs->transnoentities("CheckReceipt");
    
    $this->tab_top = 30;
    
    $this->line_height = 5;
    $this->line_per_page = 25;
    $this->tab_height = 230;	//$this->line_height * $this->line_per_page;
    
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
    $pdf->SetFont('Arial','B',12);
    $pdf->Text(11, 10, $title);

    $pdf->SetFont('Arial','B',12);
    $pdf->Text(91, 10, $langs->transnoentities("Numero")." : ".$page);
    
    $pdf->SetFont('Arial','B',12);
    $pdf->Text(11, 16, $langs->transnoentities("Date")." : ".dolibarr_print_date(time(),"%d %b %Y"));
    
    $pdf->SetFont('Arial','',12);
    $pdf->Text(91, 16, $langs->transnoentities("Page")." : ".$page);

    
    $pdf->SetFont('Arial','',12);
    
    $pdf->Text(11,$this->tab_top + 6,'Date');
    
    $pdf->line(40, $this->tab_top, 40, $this->tab_top + $this->tab_height + 10);
    $pdf->Text(42, $this->tab_top + 6, $langs->transnoentities("Bank"));
    
    $pdf->line(80, $this->tab_top, 80, $this->tab_top + $this->tab_height + 10);
    $pdf->Text(82, $this->tab_top + 6, $langs->transnoentities("Invoice"));
    
    $pdf->line(120, $this->tab_top, 120, $this->tab_top + $this->tab_height + 10);
    $pdf->Text(122, $this->tab_top + 6, $langs->transnoentities("AmountInvoice"));
    
    $pdf->line(160, $this->tab_top, 160, $this->tab_top + $this->tab_height + 10);
    
    $pdf->SetXY (160, $this->tab_top);
    $pdf->MultiCell(40, 10, $langs->transnoentities("AmountPayment"), 0, 'R');
    
    $pdf->line(10, $this->tab_top + 10, 200, $this->tab_top + 10 );

    $pdf->Rect(9, $this->tab_top, 192, $this->tab_height + 10);
  }
  
  
  function Body(&$pdf, $page, $lines)
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
	    $pdf->MultiCell(30, $this->line_height, $lines[$j][0], 0, 'J', 1);
	    
	    $pdf->SetXY (40, $this->tab_top + 10 + $yp);
	    $pdf->MultiCell(80, $this->line_height, $lines[$j][1].' '.$lines[$j][3], 0, 'J', 1);
	    
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
    
    $sql = "SELECT b.banque, b.emetteur, b.amount ";
    $sql .= " FROM ".MAIN_DB_PREFIX."bank as b, ".MAIN_DB_PREFIX."bank_account as ba ";
    $sql .= " , ".MAIN_DB_PREFIX."bordereau_cheque as bc";
    $sql .= " WHERE b.fk_account = ba.rowid AND b.fk_bordereau = bc.rowid";
    $sql .= " AND bc.number = '$number'";

    $result = $this->db->query($sql);
    
    if ($result)
      {
	$lignes = $this->db->num_rows($result);
	$i = 0;
	$var=True;
	
	while ( $objp = $this->db->fetch_object($result) )
	  {	    
	    $lines[$i][0] = $objp->banque;
	    $lines[$i][1] = $objp->emetteur;
	    $lines[$i][2] = price($objp->amount);

	    $i++;
	  }
      }
    
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
    
    $this->Body($pdf, 1, $lines);
    
    $pdf->Output($_file);
  }  
}

?>
