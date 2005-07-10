<?php
/* Copyright (C) 2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

require_once DOL_DOCUMENT_ROOT."/expedition/mods/pdf/ModelePdfExpedition.class.php";


Class pdf_expedition_rouget extends ModelePdfExpedition
{

  function pdf_expedition_rouget($db=0)
    { 
      $this->db = $db;
      $this->name = "rouget";
      $this->description = "Modèle simple.";
    }

 function Header()
    {
      $this->rect(5, 5, 200, 30);

      $this->Code39(8, 8, $this->expe->ref);

      $this->SetFont('Arial','', 14);
      $this->Text(105, 12, "Bordereau d'expédition : ".$this->expe->ref);
      $this->Text(105, 18, "Date : " . strftime("%d %b %Y", $this->expe->date));
      $this->Text(105, 24, "Page : ". $this->PageNo() ."/{nb}", 0);


      $this->rect(5, 40, 200, 250);

      $this->tableau_top = 40;

      $this->SetFont('Arial','', 10);
      $a = $this->tableau_top + 5;
      $this->Text(10, $a, "Produit");
      $this->Text(166, $a, "Quantitée");
      $this->Text(166, $a+4, "Commandée");
      $this->Text(190, $a, "Livrée");

    }

  function generate(&$objExpe, $filename)
    {
      $this->expe = $objExpe;

      $this->pdf = new pdf_expedition();
      $this->pdf->expe = &$this->expe;

      $this->pdf->Open();
      $this->pdf->AliasNbPages();
      $this->pdf->AddPage();
      
      $this->pdf->SetTitle($objExpe->ref);
      $this->pdf->SetSubject("Proposition commerciale");
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
