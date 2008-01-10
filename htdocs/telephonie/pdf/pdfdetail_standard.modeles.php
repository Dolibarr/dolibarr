<?PHP
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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
 *
 */

/*!
  \file       htdocs/telephonie/pdf/pdfdetail_standard_modeles.pdf
  \ingroup    telephonie
  \brief      Fichier de modèle pdf pour les factures détaillées
  \version    $Revision$
*/

require_once(FPDF_PATH . "fpdf.php");
require_once(FPDFI_PATH . "fpdi_protection.php");

class pdfdetail_standard_modeles extends FPDF {

  var $client_nom;

  /*
   * Header
   */

  function Header()
  {
    $this->SetXY(10,5);
    
    // 400x186

    $logo_file = DOL_DOCUMENT_ROOT."/../documents/logo.jpg";

    if (file_exists($logo_file))
    {
      $this->Image($logo_file, 10, 5, 60, 27.9, 'JPG');
    }

    $this->SetTextColor(0,90,200);
    $this->SetFont('Arial','',10);
    $this->SetXY(11,31);
    $this->MultiCell(89, 4, "Facture détaillée : ".$this->fac->ref);

    $this->SetX(11);
    $this->MultiCell(89, 4, "Ligne : " . $this->ligne);

    $this->SetX(11);

    $libelle = "Du ".strftime("%d/%m/%Y",$this->factel->get_comm_min_date($this->year.$this->month));
    $libelle .= " au ".strftime("%d/%m/%Y",$this->factel->get_comm_max_date($this->year.$this->month));
    $this->MultiCell(89, 4, $libelle, 0);

    $this->SetX(11);
    $this->MultiCell(80, 4, "Page : ". $this->PageNo() ."/{nb}", 0);

    // Clients spéciaux

    if ($this->ligne_ville)
      {
	$this->SetX(11);
	$this->MultiCell(80, 4, "Agence : ". $this->ligne_ville, 0);
      }

    $this->rect(10, 30, 95, 23);
    
    $this->SetTextColor(0,0,0);
    $this->SetFont('Arial','',10);

    $this->SetXY(107, 31);

    $this->MultiCell(66,4, $this->client_nom);

    $this->SetX(107);
    $this->MultiCell(86,4, $this->client_adresse . "\n" . $this->client_cp . " " . $this->client_ville);

    $this->rect(105, 30, 95, 23);

    /*
     * On positionne le curseur pour la liste
     */        
    $this->SetXY(10,$this->tab_top + 6);
    $this->colonne = 1;
    $this->inc = 0;
  }

  /* 
   * Footer
   */

  function Footer()
  {

    if ($this->FirstPage == 1)
      {
	$this->FirstPage = 0;
      }
    else
      {

	$this->SetFont('Arial','',8);
    
	$this->Text(11, $this->tab_top + 3,'Date');
	$this->Text(106, $this->tab_top + 3,'Date');
	
	$w = 33;
	
	$this->Text($w+1, $this->tab_top + 3,'Numéro');
	$this->Text($w+96, $this->tab_top + 3,'Numéro');
	
	$w = 47;
	
	$this->Text($w+1, $this->tab_top + 3,'Destination');
	$this->Text($w+96, $this->tab_top + 3,'Destination');
	
	$w = 86;
	
	$this->Text($w+1, $this->tab_top + 3,'Durée');
	$this->Text($w+96, $this->tab_top + 3,'Durée');
	
	$w = 98;
	
	$this->Text($w+1, $this->tab_top + 3,'HT');
	$this->Text($w+96, $this->tab_top + 3,'HT');
	
	$this->line(10, $this->tab_top + 4, 200, $this->tab_top + 4 );

	/* Ligne Médiane */

	$this->line(105, $this->tab_top, 105, $this->tab_top + $this->tab_height);
	
      }

    $this->Rect(10, $this->tab_top, 190, $this->tab_height);

  }
}
?>
