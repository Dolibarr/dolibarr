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

Class pdf_propale_adytek
{

  function pdf_propale_adytek($db=0)
    {
      $this->db = $db;
      $this->name = "Adytek";
      $this->description = "Modèle de proposition Adytek";
    }

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
	      $pdf->SetCreator("ADYTEK Dolibarr ".DOL_VERSION);
	      $pdf->SetAuthor($user->fullname);


	      $pdf->AddPage();
              $pdf->SetMargins(10, 10, 10);
              $pdf->SetAutoPageBreak(1,0);
	      
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

                  $pdf->SetFont('Arial','', 8);
		  $nexY = $pdf->GetY();

		  $pdf->SetXY (10, $curY );
		  $pdf->MultiCell(20, 5, $propale->lignes[$i]->ref, 0, 'C', 0);
                  $pdf->SetFont('Arial','', 10);
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
	      $pdf->SetFont('Arial','',7);
	      $pdf->SetXY(10, 250);
              $note = "Note : ".$propale->note;
              $pdf->MultiCell(110, 3, $note, 0, 'J');



              $this->_pagefoot($pdf, $propale);
              $pdf->AliasNbPages();

	      $pdf->Close();


	      $pdf->Output($file);

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

    }
   function _pagefoot(&$pdf, $propale)
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

   function _pagehead(&$pdf, $propale)
    {
 //insertion de la variable FAC_PDF_INTITULE        FAC_PDF_MEL           FAC_PDF_WWW           FAC_PDF_LOGO

      $tab4_top = 60;
      $tab4_hl = 6;
      $tab4_sl = 4;
      $ligne = 2;
      if (defined("FAC_PDF_LOGO"))
      {
        $pdf->SetXY(10,5);
        $pdf->Image(FAC_PDF_LOGO, 10, 5,45.0, 25.0, 'PNG');
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
      $pdf->MultiCell(50, 8, "PROPOSITION COMMERCIALE", '' , 'C');

      /*
       * Adresse Client
       */
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFillColor(242,239,119);
      $pdf->rect(100, 40, 100, 40, 'F');
      $pdf->SetFont('Arial','B',12);
      $propale->fetch_client();
      $pdf->SetXY(102,42);
      $pdf->MultiCell(86,5, $propale->client->nom, 0, 'C');
      $pdf->SetFont('Arial','B',11);
      $pdf->SetXY(102,50);
      $pdf->MultiCell(86,5, $propale->client->adresse . "\n\n" . $propale->client->cp . " " . $propale->client->ville,  0, 'C');



      $pdf->SetTextColor(200,0,0);
      $pdf->SetFont('Arial','B',14);
      //$pdf->Text(11, 88, "Date       : " . strftime("%d %b %Y", $propale->date));
      //$pdf->Text(11, 94, "Numéro : ".$propale->ref);
      $pdf->Text(11, 88, "Date");
      $pdf->Text(35, 88, ": " . strftime("%d %b %Y", $propale->date));
      $pdf->Text(11, 94, "Numéro");
      $pdf->Text(35, 94, ": ".$propale->ref);
      /*
       */
      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',10);
      $titre = "Montants exprimés en euros";
      $pdf->Text(200 - $pdf->GetStringWidth($titre), 98, $titre);
      /*
       */

    }

}

?>

