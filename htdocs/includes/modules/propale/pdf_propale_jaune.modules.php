<?PHP
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
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

/*!	\file htdocs/includes/modules/propale/pdf_propale_jaune.modules.php
        \ingroup    propale
		\brief      Fichier de la classe permettant de générer les propales au modèle Jaune
		\version    $Revision$
*/


/*!	\class pdf_propale_jaune
		\brief  Classe permettant de générer les propales au modèle Jaune
*/

class pdf_propale_jaune extends ModelePDFPropales
{

    /*!		\brief  Constructeur
    		\param	db		handler accès base de donnée
    */
  function pdf_propale_jaune($db=0)
    { 
      $this->db = $db;
      $this->name = "Jaune";
      $this->description = "Modèle de proposition Jaune";
    }

    /*!
    		\brief  Fonction générant la propale sur le disque
    		\param	id		id de la propale à générer
    */
  function write_pdf_file($id)
    {
      global $user;
      $propale = new Propal($this->db,"",$id);
      if ($propale->fetch($id))
	{

	  if (defined("PROPALE_OUTPUTDIR"))
	    {
	      $dir = PROPALE_OUTPUTDIR . "/" . $propale->ref ;
	      umask(0);
	      if (! file_exists($dir))
		{
		  mkdir($dir, 0755);
		}
	    }
	  else
	    {
	      print "PROPALE_OUTPUTDIR non définit !";
	    }
	  
	  $file = $dir . "/" . $propale->ref . ".pdf";
	  
	  if (file_exists($dir))
	    {

	      $pdf=new FPDF('P','mm','A4');
	      $pdf->Open();

	      $pdf->SetTitle($fac->ref);
	      $pdf->SetSubject("Proposition commerciale");
	      $pdf->SetCreator("Dolibarr ".DOL_VERSION);
	      $pdf->SetAuthor($user->fullname);

	      $pdf->AddPage();
	      
	      $this->_pagehead($pdf, $propale);

	      /*
	       */
	      $tab_top = 100;
	      $tab_height = 150;
	      /*
	       *
	       */  
	      
	      $pdf->SetFillColor(242,239,119);

	      $pdf->SetTextColor(0,0,0);
	      $pdf->SetFont('Arial','', 10);

	      $pdf->SetXY (10, $tab_top + 10 );

	      $iniY = $pdf->GetY();
	      $curY = $pdf->GetY();
	      $nexY = $pdf->GetY();
	      $nblignes = sizeof($propale->lignes);

	      for ($i = 0 ; $i < $nblignes ; $i++)
		{
		  $curY = $nexY;
		  $total = price($propale->lignes[$i]->price * $propale->lignes[$i]->qty);

		  $pdf->SetXY (30, $curY );
		  $pdf->MultiCell(102, 5, $propale->lignes[$i]->desc, 0, 'J', 0);

		  $nexY = $pdf->GetY();
		 
		  $pdf->SetXY (10, $curY );
		  $pdf->MultiCell(20, 5, $propale->lignes[$i]->ref, 0, 'C', 0);

		  $pdf->SetXY (132, $curY );		  
		  $pdf->MultiCell(12, 5, $propale->lignes[$i]->tva_tx, 0, 'C', 0);
		  
		  $pdf->SetXY (144, $curY );
		  $pdf->MultiCell(10, 5, $propale->lignes[$i]->qty, 0, 'C', 0);

		  $pdf->SetXY (154, $curY );
		  $pdf->MultiCell(22, 5, price($propale->lignes[$i]->price), 0, 'R', 0);
	      
		  $pdf->SetXY (176, $curY );
		  $pdf->MultiCell(24, 5, $total, 0, 'R', 0);
		  
		  $pdf->line(10, $curY, 200, $curY );

		  if ($nexY > 240 && $i < $nblignes - 1)
		    {
		      $this->_tableau($pdf, $tab_top, $tab_height, $nexY);
		      $pdf->AddPage();
		      $nexY = $iniY;
		      $this->_pagehead($pdf, $propale);
		      $pdf->SetTextColor(0,0,0);
		      $pdf->SetFont('Arial','', 10);
		    }
		}
	      
	      $this->_tableau($pdf, $tab_top, $tab_height, $nexY);
	      /*
	       *
	       */
	      $tab2_top = 254;
	      $tab2_lh = 7;
	      $tab2_height = $tab2_lh * 3;

	      $pdf->SetFont('Arial','', 11);
	      
	      $pdf->Rect(132, $tab2_top, 68, $tab2_height);
	      
	      $pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*3), 200, $tab2_top + $tab2_height - ($tab2_lh*3) );
	      $pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*2), 200, $tab2_top + $tab2_height - ($tab2_lh*2) );
	      $pdf->line(132, $tab2_top + $tab2_height - $tab2_lh, 200, $tab2_top + $tab2_height - $tab2_lh );
	      
	      $pdf->line(174, $tab2_top, 174, $tab2_top + $tab2_height);
	      
	      $pdf->SetXY (132, $tab2_top + 0);
	      $pdf->MultiCell(42, $tab2_lh, "Total HT", 0, 'R', 0);
	      
	      $pdf->SetXY (132, $tab2_top + $tab2_lh);
	      $pdf->MultiCell(42, $tab2_lh, "Total TVA", 0, 'R', 0);
	      
	      $pdf->SetXY (132, $tab2_top + ($tab2_lh*2));
	      $pdf->MultiCell(42, $tab2_lh, "Total TTC", 1, 'R', 1);
	      
	      $pdf->SetXY (174, $tab2_top + 0);
	      $pdf->MultiCell(26, $tab2_lh, price($propale->total_ht), 0, 'R', 0);
	      
	      $pdf->SetXY (174, $tab2_top + $tab2_lh);
	      $pdf->MultiCell(26, $tab2_lh, price($propale->total_tva), 0, 'R', 0);
	      
	      $pdf->SetXY (174, $tab2_top + ($tab2_lh*2));
	      $pdf->MultiCell(26, $tab2_lh, price($propale->total_ttc), 1, 'R', 1);
	      
	      /*
	       *
	       */
	      	      
	      $pdf->Output($file);
	  return 1;
	    }
	}
    }

  function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
    {

      $pdf->SetFont('Arial','',11);

      $pdf->SetXY(10,$tab_top);
      $pdf->MultiCell(20,10,'Réf',0,'C',1);

      $pdf->SetXY(30,$tab_top);
      $pdf->MultiCell(102,10,'Désignation',0,'L',1);
      
      $pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
      $pdf->SetXY(132,$tab_top);
      $pdf->MultiCell(12, 10,'TVA',0,'C',1);
      
      $pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
      $pdf->SetXY(144,$tab_top);
      $pdf->MultiCell(10,10,'Qté',0,'C',1);
      
      $pdf->line(154, $tab_top, 154, $tab_top + $tab_height);
      $pdf->SetXY(154,$tab_top);
      $pdf->MultiCell(22,10,'P.U.',0,'R',1);
      
      $pdf->line(176, $tab_top, 176, $tab_top + $tab_height);
      $pdf->SetXY(176,$tab_top);
      $pdf->MultiCell(24,10,'Total',0,'R',1);
      
      $pdf->Rect(10, $tab_top, 190, $tab_height);

      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',10);
      $titre = "Montants en euros";
      $pdf->Text(10,280, $titre);
    }

  function _pagehead(&$pdf, $propale)
    {
      $pdf->SetXY(12,42);
      if (defined("FAC_PDF_INTITULE"))
	{
	  $pdf->SetTextColor(0,0,200);
	  $pdf->SetFont('Arial','B',14);
	  $pdf->MultiCell(76, 8, FAC_PDF_INTITULE, 0, 'L');
	}
      
      $pdf->SetTextColor(70,70,170);
      if (defined("FAC_PDF_ADRESSE"))
	{
	  $pdf->SetX(12);
	  $pdf->SetFont('Arial','',12);
	  $pdf->MultiCell(76, 5, FAC_PDF_ADRESSE);
	}
      if (defined("FAC_PDF_TEL"))
	{
	  $pdf->SetX(12);
	  $pdf->SetFont('Arial','',10);
	  $pdf->MultiCell(76, 5, "Tél : ".FAC_PDF_TEL);
	}  
      if (defined("FAC_PDF_SIREN"))
	{
	  $pdf->SetX(12);
	  $pdf->SetFont('Arial','',10);
	  $pdf->MultiCell(76, 5, "SIREN : ".FAC_PDF_SIREN);
	}  
      $pdf->rect(10, 40, 80, 40);      

      $pdf->SetXY(10,5);
      $pdf->SetFont('Arial','B',16);
      $pdf->SetTextColor(0,0,200);
      $pdf->MultiCell(200, 20, "PROPOSITION COMMERCIALE", '' , 'C');

      /*
       * Adresse Client
       */
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','B',12);
      $propale->fetch_client();
      $pdf->SetXY(102,42);
      $pdf->MultiCell(96,5, $propale->client->nom);
      $pdf->SetFont('Arial','B',11);
      $pdf->SetXY(102,47);
      $pdf->MultiCell(96,5, $propale->client->adresse . "\n" . $propale->client->cp . " " . $propale->client->ville);
      $pdf->rect(100, 40, 100, 40);
            
      $pdf->SetTextColor(200,0,0);
      $pdf->SetFont('Arial','B',12);

      $pdf->rect(10, 90, 100, 10);
      $pdf->rect(110, 90, 90, 10);

      $pdf->SetXY(10,90);
      $pdf->MultiCell(110, 10, "Numéro : ".$propale->ref);
      $pdf->SetXY(110,90);
      $pdf->MultiCell(100, 10, "Date : " . strftime("%d %B %Y", $propale->date));            
    }
}
?>
