<?php
/* Copyright (C) 2003 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	\file htdocs/includes/modules/facture/pdf_adytek.modules.php
		\ingroup    facture
		\brief      Fichier de la classe permettant de générer les factures au modèle Adytek
		\author	    Laurent Destailleur
		\version    $Revision$
*/


/*!	\class pdf_adytek
		\brief  Classe permettant de générer les factures au modèle Adytek
*/

class pdf_adytek extends ModelePDFFactures {

  function pdf_adytek($db=0)
    {
      $this->db = $db;
      $this->description = "Modèle de facture avec remise et infos réglement à la mode de chez nous";
    }

   function write_pdf_file($facid)
    {
      global $user;
      $fac = new Facture($this->db,"",$facid);
      $fac->fetch($facid);

      if (defined("FAC_OUTPUTDIR"))
	{

			$forbidden_chars=array("/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
			$facref = str_replace($forbidden_chars,"_",$fac->ref);
			$dir = FAC_OUTPUTDIR . "/" . $facref . "/" ;
			$file = $dir . $facref . ".pdf";

	  if (! file_exists($dir))
	    {
	      umask(0);
	      if (! mkdir($dir, 0755))
		{
                    $this->error="Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
                    return 0;
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
	      $pdf->SetCreator("ADYTEK Dolibarr ".DOL_VERSION);
	      $pdf->SetAuthor($user->fullname);
              $pdf->SetMargins(10, 10, 10);
              $pdf->SetAutoPageBreak(1,0);
	      $tab_top = 100;
	      $tab_height = 110;

	      $pdf->SetFillColor(242,239,119);

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
	       *
	       */
	      if (defined("FACTURE_RIB_NUMBER"))
		{
		  if (FACTURE_RIB_NUMBER > 0)
		    {
		      $account = new Account($this->db);
		      $account->fetch(FACTURE_RIB_NUMBER);

		      $pdf->SetXY (10, 40);
		      $pdf->SetFont('Arial','U',8);
		      $pdf->MultiCell(40, 4, "Coordonnées bancaire", 0, 'L', 0);
		      $pdf->SetFont('Arial','',8);
		      $pdf->MultiCell(40, 4, "Code banque : " . $account->code_banque, 0, 'L', 0);
		      $pdf->MultiCell(40, 4, "Code guichet : " . $account->code_guichet, 0, 'L', 0);
		      $pdf->MultiCell(50, 4, "Numéro compte : " . $account->number, 0, 'L', 0);
		      $pdf->MultiCell(40, 4, "Clé RIB : " . $account->cle_rib, 0, 'L', 0);
		      $pdf->MultiCell(40, 4, "Domiciliation : " . $account->domiciliation, 0, 'L', 0);
		      $pdf->MultiCell(40, 4, "Prefix IBAN : " . $account->iban_prefix, 0, 'L', 0);
		      $pdf->MultiCell(40, 4, "BIC : " . $account->bic, 0, 'L', 0);
		    }
		}

	      /*
	       *
	       *
	       */

//        if ( $fac->note >0 )
//        {
        $pdf->SetFont('Arial','',7);
	      $pdf->SetXY(10, 211);
                 $note = "Note : ".$fac->note;
                 $pdf->MultiCell(110, 3, $note, 0, 'J');
//     }

	      $pdf->SetFont('Arial','U',11);
	      $pdf->SetXY(10, 225);
	      $titre = "Conditions de réglement : ".$fac->cond_reglement_facture;
	      $pdf->MultiCell(190, 5, $titre, 0, 'J');

	      $pdf->SetFont('Arial','',6);
	      $pdf->SetXY(10, 265);
	      $pdf->MultiCell(90, 2, "Par application de la loi 80.335 du 12/05/80", 0, 'J');
	      $pdf->SetXY(10, 267);
	      $pdf->MultiCell(90, 2, "les marchandises demeurent la propriété du", 0, 'J');
	      $pdf->SetXY(10, 269);
	      $pdf->MultiCell(90, 2, "vendeur jusqu'à complet encaissement de ", 0, 'J');
	      $pdf->SetXY(10, 271);
	      $pdf->MultiCell(90, 2, "leurs prix.", 0, 'J');

	      $pdf->SetFont('Arial','',7);
	      $pdf->SetXY(85, 271);
	      $pdf->MultiCell(90, 3, "TVA acquittée sur les débits.", 0, 'J');

              $this->_pagefoot($pdf, $fac);
              $pdf->AliasNbPages();
    //----
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFillColor(242,239,119);
    
      $pdf->SetLineWidth(0.5);


//      $this->RoundedRect(100, 40, 100, 40, 3, 'DF');
   //----

/*J'ai ajouté une constante dans dolibarr : FAC_CAPITAL_EURO pour le capital de la societe

     */

	      $pdf->Close();

	      $pdf->Output($file);

	      return 1;
	    }
	  else
	    {
                    $this->error="Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
                    return 0;
	    }
	}
      else
	{
            $this->error="Erreur: FAC_OUTPUTDIR non défini !";
            return 0;
	}
    }
  /*
   *
   *
   *
   */
    function RoundedRect($x, $y, $w, $h,$r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' or $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2f %.2f m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2f %.2f l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2f %.2f l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2f %.2f l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2f %.2f l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }
///////////////////////////////
  function _tableau_compl(&$pdf, $fac)
    {
      $tab3_top = 240;
      $tab3_height = 18;
      $tab3_width = 60;
      
      $pdf->Rect(10, $tab3_top, $tab3_width, $tab3_height);

      $pdf->line(10, $tab3_top + 6, $tab3_width+10, $tab3_top + 6 );
      $pdf->line(10, $tab3_top + 12, $tab3_width+10, $tab3_top + 12 );
      
      $pdf->line(40, $tab3_top, 40, $tab3_top + $tab3_height );
      
      $pdf->SetFont('Arial','',8);
      $pdf->SetXY (10, $tab3_top - 6);
      $pdf->MultiCell(60, 6, "Informations complémentaires", 0, 'L', 0);
      $pdf->SetXY (10, $tab3_top );
      $pdf->MultiCell(20, 6, "Réglé le", 0, 'L', 0);
      $pdf->SetXY (10, $tab3_top + 6);
      $pdf->MultiCell(60, 6, "Chèque/Virement N°", 0, 'L', 0);
      $pdf->SetXY (10, $tab3_top + 12);
      $pdf->MultiCell(20, 6, "Banque", 0, 'L', 0);
    }

  function _tableau_tot(&$pdf, $fac)
    {
      $tab2_top = 212;
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
      $pdf->MultiCell(42, $tab2_hl, "Total TVA", 0, 'R', 0);

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
  function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
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
   function _pagefoot(&$pdf, $fac)
{
    $pdf->SetFont('Arial','I',8);
    // FAC_PDF_ADRESSE  FAC_PDF_TEL  FAC_PDF_SIREN

    $pdf->SetFont('Arial','',8);
    $pdf->SetY(-13);
    $pdf->MultiCell(190, 3, FAC_PDF_INTITULE . " - SARL au Capital de " . FAC_CAPITAL_EURO." - " . FAC_PDF_RCS." " . FAC_PDF_SIREN , 0, 'C');
    $pdf->SetY(-10);
    $pdf->MultiCell(190, 3, "N° TVA Intracommunautaire : " . FAC_PDF_TVA_INTRA  , 0, 'C');
    $pdf->SetXY(-10,-10);
    $pdf->MultiCell(10, 3, $pdf->PageNo().'/{nb}', 0, 'R');

}

  function _pagehead(&$pdf, $fac)
    {
      $tab4_top = 60;
      $tab4_hl = 6;
      $tab4_sl = 4;
      $ligne = 2;

        if (defined("FAC_PDF_LOGO") && FAC_PDF_LOGO)
        {
            if (file_exists(FAC_PDF_LOGO)) {
                $pdf->SetXY(10,5);
                $pdf->Image(FAC_PDF_LOGO, 10, 5,45.0, 25.0, 'PNG');
            }
            else {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('Arial','B',8);
                $pdf->MultiCell(80, 3, "Logo file '".FAC_PDF_LOGO."' was not found", 0, 'L');
                $pdf->MultiCell(80, 3, "Go to setup to change path.", 0, 'L');
            }
        }
        else if (defined("FAC_PDF_INTITULE"))
        {
            $pdf->MultiCell(80, 6, FAC_PDF_INTITULE, 0, 'L');
        }

      $pdf->SetDrawColor(192,192,192);
      $pdf->line(9, 5, 200, 5 );
      $pdf->line(9, 30, 200, 30 );

      $pdf->SetFont('Arial','B',7);
      $pdf->SetTextColor(128,128,128);

      if (defined("FAC_PDF_ADRESSE"))
      {
      	$pdf->SetXY( $tab4_top , $tab4_hl );
        $pdf->MultiCell(40, 3, FAC_PDF_ADRESSE, '' , 'L');
      }
      $pdf->SetFont('Arial','',7);
      if (defined("FAC_PDF_TEL"))
      {
      	$pdf->SetXY( $tab4_top , $tab4_hl + 2*$tab4_sl );
        $pdf->MultiCell(40, 3, "Téléphone : " . FAC_PDF_TEL, '' , 'L');
      }
      if (defined("FAC_PDF_FAX"))
      {
      	$pdf->SetXY( $tab4_top , $tab4_hl + 3*$tab4_sl );
        $pdf->MultiCell(40, 3, "Télécopie : " . FAC_PDF_FAX, '' , 'L');
      }
      if (defined("FAC_PDF_MEL"))
      {
      	$pdf->SetXY( $tab4_top , $tab4_hl + 4*$tab4_sl );
        $pdf->MultiCell(40, 3, "E-mail : " . FAC_PDF_MEL, '' , 'L');
      }
      if (defined("FAC_PDF_WWW"))
      {
      	$pdf->SetXY( $tab4_top , $tab4_hl + 5*$tab4_sl );
        $pdf->MultiCell(40, 3, "Internet : " . FAC_PDF_WWW, '' , 'L');
      }
      $pdf->SetTextColor(70,70,170);

      if (defined("FAC_PDF_INTITULE2"))
	{
	  $pdf->SetXY(10,30);
	  $pdf->SetFont('Arial','',7);
	  $pdf->SetTextColor(0,0,200);
	  $pdf->MultiCell(45, 5, FAC_PDF_INTITULE2, '' , 'C');
	}
       /*
       * Definition du document
       */
      $pdf->SetXY(10,50);
      $pdf->SetFont('Arial','B',16);
      $pdf->SetTextColor(0,0,200);
      $pdf->MultiCell(50, 8, "FACTURE", '' , 'C');
     /*
       * Adresse Client
       */
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFillColor(242,239,119);
    
//      $this->RoundedRect(100, 40, 100, 40, 3, 'F');
      $pdf->rect(100, 40, 100, 40, 'F');
      $pdf->SetFont('Arial','B',12);
      $fac->fetch_client();
      $pdf->SetXY(102,42);
      $pdf->MultiCell(96,5, $fac->client->nom, 0, 'C');
      $pdf->SetFont('Arial','B',11);
      $pdf->SetXY(102,50);
      $pdf->MultiCell(96,5, $fac->client->adresse . "\n\n" . $fac->client->cp . " " . $fac->client->ville ,  0, 'C');




      $pdf->SetTextColor(200,0,0);
      $pdf->SetFont('Arial','B',14);
      $pdf->Text(11, 88, "Date");
      $pdf->Text(35, 88, ": " . strftime("%d %b %Y", $fac->date));
      $pdf->Text(11, 94, "Numéro");
      $pdf->Text(35, 94, ": ".$fac->ref);
      /*
       */
      $pdf->line(200, 94, 205, 94 );
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',10);
      $titre = "Montants exprimés en euros";
      $pdf->Text(200 - $pdf->GetStringWidth($titre), 98, $titre);
      /*
       */

    }

 //insertion de la variable FAC_PDF_INTITULE  FAC_PDF_MEL  FAC_PDF_WWW   FAC_PDF_LOGO  FAC_CAPITAL_EURO  FAC_PDF_TVA_INTRA    FAC_PDF_RCS

}

?>
