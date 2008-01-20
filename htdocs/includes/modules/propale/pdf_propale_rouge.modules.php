<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 */

/**
        \file       htdocs/includes/modules/propale/pdf_propale_rouge.modules.php
        \ingroup    propale
        \brief      Fichier de la classe permettant de générer les propales au modèle Rouge
        \version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT."/propal.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/propale/modules_propale.php");


/**
        \class      pdf_propale_rouge
        \brief      Classe permettant de générer les propales au modèle Rouge
*/

class pdf_propale_rouge extends ModelePDFPropales
{
	var $emetteur;	// Objet societe qui emet


	/**	\brief      Constructeur
	    \param	    db	    handler accès base de donnée
	*/
	function pdf_propale_rouge($db=0)
    {
        global $conf,$langs,$mysoc;

        $this->db = $db;
        $this->name = "rouge";
        $this->description = "Modèle de propale simple";

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur,$this->page_hauteur);

        $this->error = "";
        
        // Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si on trouve pas
    }


  /**	\brief      Renvoi dernière erreur
        \return     string      Dernière erreur
  */
  function pdferror()
  {
      return $this->error;
  }


	/**
	    \brief      Fonction générant la propale sur le disque
	    \param	    propale			Objet propal
		\param		outputlangs		Lang object for output language
		\return	    int     		1=ok, 0=ko
	*/
	function write_file($propale,$outputlangs='')
	{
		global $user,$conf,$langs;

		if ($conf->propal->dir_output)
		{
			// Définition de l'objet $propal (pour compatibilite ascendante)
        	if (! is_object($propale))
        	{
	            $id = $propale;
	            $propale = new Propal($this->db,"",$id);
	            $ret=$propale->fetch($id);
			}

			// Définition de $dir et $file
			if ($propale->specimen)
			{
				$dir = $conf->propal->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$propref = sanitize_string($propale->ref);
				$dir = $conf->propal->dir_output . "/" . $propref;
				$file = $dir . "/" . $propref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
	           // Protection et encryption du pdf
               if ($conf->global->PDF_SECURITY_ENCRYPTION)
               {
					$pdf=new FPDI_Protection('P','mm',$this->format);
     	           $pdfrights = array('print'); // Ne permet que l'impression du document
    	           $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
     	           $pdfownerpass = NULL; // Mot de passe du propriétaire, créé aléatoirement si pas défini
     	           $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
               }
			   else
			   {
                   $pdf=new FPDI('P','mm',$this->format);
				}

				$pdf->Open();
				$pdf->AddPage();

				$pdf->SetTitle($propale->ref);
				$pdf->SetSubject($langs->transnoentities("CommercialProposal"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($user->fullname);

				$this->_pagehead($pdf, $propale);

				$tab_top = 100;
				$tab_height = 140;
	
				$pdf->SetFillColor(220,220,220);

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

					$pdf->SetXY (30, $curY );

					$pdf->MultiCell(100, 5, $propale->lignes[$i]->desc, 0, 'J', 0);

					$nexY = $pdf->GetY();

					$pdf->SetXY (10, $curY );

					$pdf->MultiCell(20, 5, $propale->lignes[$i]->ref, 0, 'C');

					$pdf->SetXY (133, $curY );
					$pdf->MultiCell(10, 5, $propale->lignes[$i]->tva_tx, 0, 'C');

					$pdf->SetXY (145, $curY );
					$pdf->MultiCell(10, 5, $propale->lignes[$i]->qty, 0, 'C');

					$pdf->SetXY (156, $curY );
					$pdf->MultiCell(18, 5, price($propale->lignes[$i]->price), 0, 'R', 0);

					$pdf->SetXY (174, $curY );
					$total = price($propale->lignes[$i]->total_ht);
					$pdf->MultiCell(26, 5, $total, 0, 'R', 0);

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
				$tab2_top = 241;
				$tab2_lh = 7;
				$tab2_height = $tab2_lh * 4;

				$pdf->SetFont('Arial','', 11);

				$pdf->Rect(132, $tab2_top, 68, $tab2_height);

				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*3), 200, $tab2_top + $tab2_height - ($tab2_lh*3) );
				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*2), 200, $tab2_top + $tab2_height - ($tab2_lh*2) );
				$pdf->line(132, $tab2_top + $tab2_height - $tab2_lh, 200, $tab2_top + $tab2_height - $tab2_lh );

				$pdf->line(174, $tab2_top, 174, $tab2_top + $tab2_height);

				$pdf->SetXY (132, $tab2_top + 0);
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("TotalHT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + $tab2_lh);
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("Discount"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + $tab2_lh*2);
				$pdf->MultiCell(42, $tab2_lh, "Total HT après remise", 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + $tab2_lh*3);
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("TotalVAT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + ($tab2_lh*4));
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("TotalTTC"), 1, 'R', 1);

				$pdf->SetXY (174, $tab2_top + 0);
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_ht + $propale->remise), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + $tab2_lh);
				$pdf->MultiCell(26, $tab2_lh, price($propale->remise), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + $tab2_lh*2);
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_ht), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + $tab2_lh*3);
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_tva), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + ($tab2_lh*4));
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_ttc), 1, 'R', 1);


				if (defined("PROP_PDF_MESSAGE") && PROP_PDF_MESSAGE)
				{
					$pdf->SetXY (10, $tab2_top + 2);
					$pdf->SetFont('Arial','',10);
					$pdf->MultiCell(120, 5, PROP_PDF_MESSAGE);
				}

				$pdf->Output($file);
				return 1;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","PROPALE_OUTPUTDIR");
			return 0;
		}

	}


	function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
    {
        global $langs,$conf;
        $langs->load("main");
        $langs->load("bills");

      $pdf->SetFont('Arial','',11);

      $pdf->Text(30,$tab_top + 5,$langs->transnoentities("Designation"));

      $pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
      $pdf->Text(134,$tab_top + 5,$langs->transnoentities("VAT"));

      $pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
      $pdf->Text(147,$tab_top + 5,$langs->transnoentities("Qty"));

      $pdf->line(156, $tab_top, 156, $tab_top + $tab_height);
      $pdf->Text(160,$tab_top + 5,$langs->transnoentities("PriceU"));

      $pdf->line(174, $tab_top, 174, $tab_top + $tab_height);
      $pdf->Text(187,$tab_top + 5,$langs->transnoentities("Total"));

      //      $pdf->Rect(10, $tab_top, 190, $nexY - $tab_top);
      $pdf->Rect(10, $tab_top, 190, $tab_height);


      $pdf->SetTextColor(0,0,0);
      $pdf->SetFont('Arial','',10);
      $titre = $langs->transnoentities("AmountInCurrency",$langs->transnoentities("Currency".$conf->monnaie));
      $pdf->Text(200 - $pdf->GetStringWidth($titre), 98, $titre);

    }

  function _pagehead(&$pdf, $propale)
    {
      $pdf->SetXY(10,5);
      if (defined("FAC_PDF_INTITULE"))
	{
	  $pdf->SetTextColor(0,0,200);
	  $pdf->SetFont('Arial','B',14);
	  $pdf->MultiCell(76, 8, FAC_PDF_INTITULE, 0, 'L');
	}

      $pdf->SetTextColor(70,70,170);
      if (defined("FAC_PDF_ADRESSE"))
	{
	  $pdf->SetFont('Arial','',12);
	  $pdf->MultiCell(76, 5, FAC_PDF_ADRESSE);
	}
      if (defined("FAC_PDF_TEL"))
	{
	  $pdf->SetFont('Arial','',10);
	  $pdf->MultiCell(76, 5, "Tél : ".FAC_PDF_TEL);
	}
      if (defined("MAIN_INFO_SIREN"))
	{
	  $pdf->SetFont('Arial','',10);
	  $pdf->MultiCell(76, 5, "SIREN : ".MAIN_INFO_SIREN);
	}

      if (defined("FAC_PDF_INTITULE2"))
	{
	  $pdf->SetXY(100,5);
	  $pdf->SetFont('Arial','B',14);
	  $pdf->SetTextColor(0,0,200);
	  $pdf->MultiCell(100, 10, FAC_PDF_INTITULE2, '' , 'R');
	}
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
      $pdf->Text(11, 88, "Date : " . dolibarr_print_date($propale->date,'day'));
      $pdf->Text(11, 94, "Proposition commerciale : ".$propale->ref);


    }

}

?>
