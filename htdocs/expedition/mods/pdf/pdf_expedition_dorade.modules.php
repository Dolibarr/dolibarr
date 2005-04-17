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
 * L'entete du pdf est définit dans pdf_expedition.class.php
 */
require_once DOL_DOCUMENT_ROOT."/expedition/mods/pdf/pdf_expedition.class.php";

Class pdf_expedition_dorade
{

  Function pdf_expedition_dorade($db=0)
    { 
      $this->db = $db;
      $this->name = "dorade";
      $this->description = "Modèle identique au rouget utilisé pour debug uniquement.";
    }

  Function generate(&$objExpe, $filename)
    {
      $this->expe = $objExpe;

      $this->pdf = new pdf_expedition();
      $this->pdf->expe = &$this->expe;

      $this->pdf->Open();
      $this->pdf->AliasNbPages();
      $this->pdf->AddPage();
      
      $this->pdf->SetTitle($objExpe->ref);
      $this->pdf->SetSubject("Bordereau d'expedition");
      $this->pdf->SetCreator("Dolibarr ".DOL_VERSION);
      //$this->pdf->SetAuthor($user->fullname);
      
      /*
       *
       */  
      
      $this->pdf->SetTextColor(0,0,0);
      $this->pdf->SetFont('Arial','', 14);
      
      $this->expe->fetch_lignes();
      
      for ($i = 0 ; $i < sizeof($this->expe->lignes) ; $i++)
	{
	  $a = $this->pdf->tableau_top + 14 + ($i * 7);
	  
	  $this->pdf->Text(8, $a, $this->expe->lignes[$i]->description);
	  
	  $this->pdf->Text(170, $a, $this->expe->lignes[$i]->qty_commande);
	  
	  $this->pdf->Text(194, $a, $this->expe->lignes[$i]->qty_expedition);
	}
      
      $this->pdf->Output($filename);	  
    }
}
?>
