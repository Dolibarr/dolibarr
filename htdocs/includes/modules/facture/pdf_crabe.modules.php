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

Class pdf_crabe {

  Function pdf_crabe($db=0)
    { 
      $this->db = $db;
      $this->description = "Modèle de facture classique (Gère l'option fiscale de facturation TVA et les mode de règlement)";
    }

  Function write_pdf_file($facid)
    {
      global $user;
      $fac = new Facture($this->db,"",$facid);
      $fac->fetch($facid);  

      if (defined("FAC_OUTPUTDIR"))
	{

	  $dir = FAC_OUTPUTDIR . "/" . $fac->ref . "/" ;
	  $file = $dir . $fac->ref . ".pdf";
	  
	  if (! file_exists($dir))
	    {
	      umask(0);
	      if (! mkdir($dir, 0755))
		{
		  print "Impossible de créer $dir !";
		}
	    }
	  
	  if (file_exists($dir))
	    {
	      $pdf=new FPDF('P','mm','A4');
	      $pdf->Open();
	      $pdf->AddPage();

	      $this->_pagehead($pdf, $fac);

	      $pdf->SetTitle($fac->ref);
	      $pdf->SetSubject("Facture");
	      $pdf->SetCreator("Dolibarr ".DOL_VERSION);
	      $pdf->SetAuthor($user->fullname);
	      
	      $tab_top = 96;
	      $tab_height = 110;      	      

	      /*
	       *
	       */  
	      
	      $pdf->SetFillColor(220,220,220);
	      
	      $pdf->SetFont('Arial','', 9);

	      $pdf->SetXY (10, $tab_top + 10 );

	      $iniY = $pdf->GetY();
	      $curY = $pdf->GetY();
	      $nexY = $pdf->GetY();
	      $nblignes = sizeof($fac->lignes);

	      for ($i = 0 ; $i < $nblignes ; $i++)
		{
		  $curY = $nexY;

		  $pdf->SetXY (11, $curY );
		  $pdf->MultiCell(118, 5, $fac->lignes[$i]->desc, 0, 'J');

		  $nexY = $pdf->GetY();
		  
		  $pdf->SetXY (133, $curY);
		  $pdf->MultiCell(10, 5, $fac->lignes[$i]->tva_taux, 0, 'C');
		  
		  $pdf->SetXY (145, $curY);
		  $pdf->MultiCell(10, 5, $fac->lignes[$i]->qty, 0, 'C');
		  
		  $pdf->SetXY (156, $curY);
		  $pdf->MultiCell(18, 5, price($fac->lignes[$i]->price), 0, 'R', 0);
	      
		  $pdf->SetXY (174, $curY);
		  $total = price($fac->lignes[$i]->price * $fac->lignes[$i]->qty);
		  $pdf->MultiCell(26, 5, $total, 0, 'R', 0);

		  if ($nexY > 200 && $i < $nblignes - 1)
		    {
		      $this->_tableau($pdf, $tab_top, $tab_height, $nexY);
		      $pdf->AddPage();
		      $nexY = $iniY;
		      $this->_pagehead($pdf, $fac);
		      $pdf->SetTextColor(0,0,0);
		      $pdf->SetFont('Arial','', 10);
		    }
		  
		}
	      $this->_tableau($pdf, $tab_top, $tab_height, $nexY);

	      $this->_tableau_tot($pdf, $fac);
	  
	      $this->_tableau_compl($pdf, $fac);	      

	      /*
	       * Propose mode règlement par CHQ
	       */
	      if (defined("FACTURE_CHQ_NUMBER"))
		{
		  if (FACTURE_CHQ_NUMBER > 0)
		    {
		      $account = new Account($this->db);
		      $account->fetch(FACTURE_CHQ_NUMBER);

			  $pdf->SetXY (10, 228);  
			  $pdf->SetFont('Arial','B',8);
			  $pdf->MultiCell(90, 3, "Règlement par chèque à l'ordre de ".$account->proprio." envoyé à:",0,'L',0); 
			  $pdf->SetFont('Arial','',8);
			  $pdf->MultiCell(80, 3, $account->adresse_proprio, 0, 'L', 0);
			}
	    }

	      /*
	       * Propose mode règlement par RIB
	       */
	      if (defined("FACTURE_RIB_NUMBER"))
		{
		  if (FACTURE_RIB_NUMBER > 0)
		    {
		      $account = new Account($this->db);
		      $account->fetch(FACTURE_RIB_NUMBER);
		      
		      $pdf->SetXY (10, 241);		  
		      $pdf->SetFont('Arial','B',8);
		      $pdf->MultiCell(90, 3, "Règlement par virement sur le compte ci-dessous:", 0, 'L', 0);
		      $pdf->SetFont('Arial','',8);
		      $pdf->MultiCell(90, 3, "Code banque : " . $account->code_banque, 0, 'L', 0);
		      $pdf->MultiCell(90, 3, "Code guichet : " . $account->code_guichet, 0, 'L', 0);
		      $pdf->MultiCell(90, 3, "Numéro compte : " . $account->number, 0, 'L', 0);
		      $pdf->MultiCell(90, 3, "Clé RIB : " . $account->cle_rib, 0, 'L', 0);
		      $pdf->MultiCell(90, 3, "Domiciliation : " . $account->domiciliation, 0, 'L', 0);
		      $pdf->MultiCell(90, 3, "Prefix IBAN : " . $account->iban_prefix, 0, 'L', 0);
		      $pdf->MultiCell(90, 3, "BIC : " . $account->bic, 0, 'L', 0);
		    }
		}
	      
	      /*
	       *
	       *
	       */
	      
	      $pdf->SetFont('Arial','U',10);
	      $pdf->SetXY(10, 220);
	      $titre = "Conditions de réglement : ".$fac->cond_reglement_facture;
	      $pdf->MultiCell(190, 5, $titre, 0, 'J');
	      
	      $pdf->Close();
	      
	      $pdf->Output($file);

	      return 1;
	    }
	  else
	    {
	      print "Erreur : le répertoire $dir n'existe pas !";
	    }
	}
      else
	{
	  print "FAC_OUTPUTDIR non définit !";
	}
    }
  /*
   *
   *
   *
   */
  Function _tableau_compl(&$pdf, $fac)
    {
      $tab3_top = 245;
      $tab3_height = 18;
      $tab3_width = 68;
      $tab3_posx = 132;
 
      $pdf->Rect($tab3_posx, $tab3_top, $tab3_width, $tab3_height);
      
      $pdf->line($tab3_posx, $tab3_top + 6, $tab3_posx+$tab3_width, $tab3_top + 6 );
      $pdf->line($tab3_posx, $tab3_top + 12, $tab3_posx+$tab3_width, $tab3_top + 12 );
      
      $pdf->line($tab3_posx, $tab3_top, $tab3_posx, $tab3_top + $tab3_height );
      
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY ($tab3_posx, $tab3_top - 6);
      $pdf->MultiCell(60, 6, "Informations complémentaires", 0, 'L', 0);
      $pdf->SetXY ($tab3_posx, $tab3_top );
      $pdf->MultiCell(20, 6, "Réglé le", 0, 'L', 0);
      $pdf->SetXY ($tab3_posx, $tab3_top + 6);
      $pdf->MultiCell(20, 6, "Chèque N°", 0, 'L', 0);
      $pdf->SetXY ($tab3_posx, $tab3_top + 12);
      $pdf->MultiCell(20, 6, "Banque", 0, 'L', 0);
    }

  Function _tableau_tot(&$pdf, $fac)
    {
      $tab2_top = 207;
      $tab2_hl = 5;
      $tab2_height = $tab2_hl * 4;
      $pdf->SetFont('Arial','', 9);
      
      //	      $pdf->Rect(132, $tab2_top, 68, $tab2_height);
      //	      $pdf->line(174, $tab2_top, 174, $tab2_top + $tab2_height);
      
      //	      $pdf->line(132, $tab2_top + $tab2_height - 21, 200, $tab2_top + $tab2_height - 21 );
      //	      $pdf->line(132, $tab2_top + $tab2_height - 14, 200, $tab2_top + $tab2_height - 14 );
      //	      $pdf->line(132, $tab2_top + $tab2_height - 7, 200, $tab2_top + $tab2_height - 7 );
      
      $pdf->SetXY (132, $tab2_top + 0);
      $pdf->MultiCell(42, $tab2_hl, "Total HT", 0, 'R', 0);

	  if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise') {
	  	$pdf->SetXY (10, $tab2_top + 0);
	  	$pdf->MultiCell(100, $tab2_hl, "* TVA non applicable art-293B du CGI", 0, 'L', 0);
	  }

      $pdf->SetXY (174, $tab2_top + 0);
      $pdf->MultiCell(26, $tab2_hl, price($fac->total_ht + $fac->remise), 0, 'R', 0);
      
      if ($fac->remise > 0)
	{
	  $pdf->SetXY (132, $tab2_top + $tab2_hl);
	  $pdf->MultiCell(42, $tab2_hl, "Remise", 0, 'R', 0);
	  
	  $pdf->SetXY (174, $tab2_top + $tab2_hl);
	  $pdf->MultiCell(26, $tab2_hl, price($fac->remise), 0, 'R', 0);
	  
	  $pdf->SetXY (132, $tab2_top + $tab2_hl * 2);
	  $pdf->MultiCell(42, $tab2_hl, "Total HT aprés remise", 0, 'R', 0);
      
	  $pdf->SetXY (174, $tab2_top + $tab2_hl * 2);
	  $pdf->MultiCell(26, $tab2_hl, price($fac->total_ht), 0, 'R', 0);

	  $index = 3;
	}
      else
	{
	  $index = 1;
	}

      $pdf->SetXY (132, $tab2_top + $tab2_hl * $index);
      $pdf->MultiCell(42, $tab2_hl, "*Total TVA", 0, 'R', 0);

      $pdf->SetXY (174, $tab2_top + $tab2_hl * $index);
      $pdf->MultiCell(26, $tab2_hl, price($fac->total_tva), 0, 'R', 0);
            
      $pdf->SetXY (132, $tab2_top + $tab2_hl * ($index+1));
      $pdf->MultiCell(42, $tab2_hl, "Total TTC", 0, 'R', 1);
      
      $pdf->SetXY (174, $tab2_top + $tab2_hl * ($index+1));
      $pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc), 0, 'R', 1);

      $deja_regle = $fac->getSommePaiement();

      if ($deja_regle > 0)
	{
	  $pdf->SetXY (132, $tab2_top + $tab2_hl * ($index+2));
	  $pdf->MultiCell(42, $tab2_hl, "Déjà réglé", 0, 'R', 0);

	  $pdf->SetXY (174, $tab2_top + $tab2_hl * ($index+2));
	  $pdf->MultiCell(26, $tab2_hl, price($deja_regle), 0, 'R', 0);

	  $pdf->SetXY (132, $tab2_top + $tab2_hl * ($index+3));
	  $pdf->MultiCell(42, $tab2_hl, "Reste à payer", 0, 'R', 1);

	  $pdf->SetXY (174, $tab2_top + $tab2_hl * ($index+3));
	  $pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc - $deja_regle), 0, 'R', 1);
	}
    }
  /*
   *
   */
  Function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
    {
      $pdf->SetFont('Arial','',10);
      
      $pdf->Text(11,$tab_top + 5,'Désignation');
      
      $pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
      $pdf->Text(134,$tab_top + 5,'TVA');
      
      $pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
      $pdf->Text(147,$tab_top + 5,'Qté');
      
      $pdf->line(156, $tab_top, 156, $tab_top + $tab_height);
      $pdf->Text(160,$tab_top + 5,'P.U.');
      
      $pdf->line(174, $tab_top, 174, $tab_top + $tab_height);
      $pdf->Text(187,$tab_top + 5,'Total');
      
      $pdf->Rect(10, $tab_top, 190, $tab_height);
      $pdf->line(10, $tab_top + 10, 200, $tab_top + 10 );
    }
  /*
   *
   *
   *
   *
   */
  Function _pagehead(&$pdf, $fac)
    {
      
      $pdf->SetXY(10,5);
      if (defined("FAC_PDF_INTITULE"))
	{
	  $pdf->SetTextColor(0,0,60);
	  $pdf->SetFont('Arial','B',13);
	  $pdf->MultiCell(70, 8, FAC_PDF_INTITULE, 0, 'L');
	}
      
      $pdf->SetFont('Arial','B',13);
	  $pdf->SetXY(100,5);
	  $pdf->SetTextColor(0,0,60);
	  $pdf->MultiCell(100, 10, "Facture no ".$fac->ref, '' , 'R');
      $pdf->SetXY(100,11);
      $pdf->SetTextColor(0,0,60);
      $pdf->MultiCell(100, 10, "Date : " . strftime("%d %b %Y", $fac->date), '', 'R');

 	  /*
       * Emetteur
       */
	  $posy=42; 
	  $pdf->SetTextColor(0,0,0);
	  $pdf->SetFont('Arial','',8);
	  $pdf->SetXY(10,$posy-5);
	  $pdf->MultiCell(66,5, "Emetteur:");

      $pdf->SetXY(10,$posy+4);
    
      if (defined("FAC_PDF_INTITULE2"))
    {
      $pdf->SetTextColor(0,0,60);
      $pdf->SetFont('Arial','B',10);
      $pdf->MultiCell(70, 4, FAC_PDF_INTITULE2, 0, 'L');
    }
      if (defined("FAC_PDF_ADRESSE"))
    {
      $pdf->SetFont('Arial','',10);
      $pdf->MultiCell(80, 4, FAC_PDF_ADRESSE);
    }
      if (defined("FAC_PDF_TEL"))
    {
      $pdf->SetFont('Arial','',10);
      $pdf->MultiCell(40, 4, "Tél : ".FAC_PDF_TEL);
    }
      if (defined("FAC_PDF_SIREN"))
    {
      $pdf->SetFont('Arial','',10);
      $pdf->MultiCell(40, 4, "SIREN : ".FAC_PDF_SIREN);
    }


	  /*
       * Client
       */
	  $posy=42;
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY(102,$posy-5);
	  $pdf->MultiCell(80,5, "Adressé à:");
	  $pdf->SetFont('Arial','B',11);
      $fac->fetch_client();
      $pdf->SetXY(102,$posy+4);
      $pdf->MultiCell(86,4, $fac->client->nom, 0, 'L');
      $pdf->SetFont('Arial','B',10);
      $pdf->SetXY(102,$posy+12);
      $pdf->MultiCell(86,4, $fac->client->adresse . "\n" . $fac->client->cp . " " . $fac->client->ville);
      $pdf->rect(100, $posy, 100, 34);
      
      /*
  	   * 
       */
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',10);
      $titre = "Montants exprimés en euros";
      $pdf->Text(200 - $pdf->GetStringWidth($titre), 94, $titre);
      /*
       */
      
    }
  
}

?>
