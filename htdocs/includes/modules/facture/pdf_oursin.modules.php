<?php
/* Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
/* Copyright (C) 2005 Sylvain SCATTOLINI   <sylvain@s-infoservices.com>
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

/*!	\file htdocs/includes/modules/facture/pdf_oursin.modules.php
  \ingroup    facture
  \brief      Fichier de la classe permettant de générer les factures au modèle oursin
  \author	   Sylvain SCATTOLINI basé sur un modèle de Laurent Destailleur
  \version    $Revision$
*/


/*!	\class pdf_oursin
  \brief  Classe permettant de générer les factures au modèle oursin
*/


class pdf_oursin extends ModelePDFFactures
{
  var $marges=array("g"=>10,"h"=>5,"d"=>10,"b"=>15);

    
  /*!		\brief  Constructeur
    \param	db		handler accès base de donnée
  */
  function pdf_oursin($db)
  {
    $this->db = $db;
    $this->description = "Modèle de facture complet (basé sur crabe, gère l'option fiscale de facturation TVA, le choix du mode de règlement à afficher, les remises, le nom du projet, la reference propal, logo...)";
    $this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
    $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
    $this->option_modereg = 1;                 // Gere choix mode règlement FACTURE_CHQ_NUMBER, FACTURE_RIB_NUMBER
    $this->option_codeproduitservice = 1;      // Affiche code produit-service FACTURE_CODEPRODUITSERVICE
    $this->option_tvaintra = 1;                // Affiche tva intra MAIN_INFO_TVAINTRA
    $this->option_capital = 1;                 // Affiche capital MAIN_INFO_CAPITAL
    if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise') 
      $this->franchise=1;
  }


  /*!
    \brief      Fonction générant la facture sur le disque
    \param	    facid	id de la facture à générer
    \return	    int     1=ok, 0=ko
    \remarks    Variables utilisées
    \remarks    FAC_PDF_LOGO
    \remarks    FACTURE_CODEPRODUITSERVICE
    \remarks    FACTURE_CHQ_NUMBER
    \remarks    FACTURE_RIB_NUMBER
    \remarks    FAC_PDF_INTITULE
    \remarks    FAC_PDF_INTITULE2
    \remarks    FAC_PDF_SIREN
    \remarks    FAC_PDF_SIRET
    \remarks    FAC_PDF_TEL
    \remarks    FAC_PDF_ADRESSE
    \remarks    MAIN_INFO_RCS
    \remarks    MAIN_INFO_CAPITAL
    \remarks    MAIN_INFO_TVAINTRA
  */
  function write_pdf_file($facid)
  {
    global $user,$langs,$conf;

    $langs->load("main");
    $langs->load("bills");
    $langs->load("products");

    if ($conf->facture->dir_output)
      {
	$fac = new Facture($this->db,"",$facid);
	$fac->fetch($facid);

	$forbidden_chars=array("/","\\",":","*","?","\"","<",">","|","[","]",",",";","=");
	$facref = str_replace($forbidden_chars,"_",$fac->ref);
	$dir = $conf->facture->dir_output . "/" . $facref;
	$file = $dir . "/" . $facref . ".pdf";

	if (! file_exists($dir))
	  {
	    umask(0);
	    if (! mkdir($dir, 0755))
	      {
		$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
		return 0;
	      }
	  }

	if (file_exists($dir))
	  {
	    // Initialisation facture vierge
	    $pdf=new FPDF('P','mm','A4');
	    $pdf->Open();
	    $pdf->AddPage();

	    $this->_pagehead($pdf, $fac);

	    $pdf->SetTitle($fac->ref);
	    $pdf->SetSubject($langs->trans("Bill"));
	    $pdf->SetCreator("Dolibarr ".DOL_VERSION);
	    $pdf->SetAuthor($user->fullname);

	    $pdf->SetMargins(10, 10, 10);
	    $pdf->SetAutoPageBreak(1,0);

	    $tab_top = $this->marges['h']+90;
	    $tab_height = 110;

	    $pdf->SetFillColor(220,220,220);
	    $pdf->SetFont('Arial','', 9);
	    $pdf->SetXY ($this->marges['g'], $tab_top + $this->marges['g'] );

	    $iniY = $pdf->GetY();
	    $curY = $pdf->GetY();
	    $nexY = $pdf->GetY();
	    $nblignes = sizeof($fac->lignes);

	    // Boucle sur les lignes de factures
	    for ($i = 0 ; $i < $nblignes ; $i++)
	      {
		$curY = $nexY;

		// Description produit
		$codeproduitservice="";
		$pdf->SetXY ($this->marges['g']+ 1, $curY );
		if (defined("FACTURE_CODEPRODUITSERVICE") && FACTURE_CODEPRODUITSERVICE) {
		  // Affiche code produit si ligne associée à un code produit

		  $prodser = new Product($this->db);

		  $prodser->fetch($fac->lignes[$i]->produit_id);
		  if ($prodser->ref) {
		    $codeproduitservice=" - ".$langs->trans("ProductCode")." ".$prodser->ref;
		  }
		}
		if ($fac->lignes[$i]->date_start && $fac->lignes[$i]->date_end) {
		  // Affichage durée si il y en a une
		  $codeproduitservice.="\n".$langs->trans("From")." ".dolibarr_print_date($fac->lignes[$i]->date_start)." ".$langs->trans("to")." ".dolibarr_print_date($fac->lignes[$i]->date_end);
		}
		$pdf->MultiCell(108, 5, $fac->lignes[$i]->desc."$codeproduitservice", 0, 'J');

		$nexY = $pdf->GetY();

		// TVA
		if ($this->franchise!=1)
		  {
		    $pdf->SetXY ($this->marges['g']+119, $curY);
		    $pdf->MultiCell(10, 5, $fac->lignes[$i]->tva_taux, 0, 'C');
		  }
		// Prix unitaire HT
		$pdf->SetXY ($this->marges['g']+132, $curY);
		$pdf->MultiCell(16, 5, price($fac->lignes[$i]->subprice), 0, 'R', 0);

		// Quantité
		$pdf->SetXY ($this->marges['g']+150, $curY);
		$pdf->MultiCell(10, 5, $fac->lignes[$i]->qty, 0, 'R');

		// Remise sur ligne
		$pdf->SetXY ($this->marges['g']+160, $curY);
		if ($fac->lignes[$i]->remise_percent) {
		  $pdf->MultiCell(14, 5, $fac->lignes[$i]->remise_percent."%", 0, 'R');
		}

		// Total HT
		$pdf->SetXY ($this->marges['g']+168, $curY);
		$total = price($fac->lignes[$i]->price * $fac->lignes[$i]->qty);
		$pdf->MultiCell(21, 5, $total, 0, 'R', 0);


		if ($nexY > 200 && $i < $nblignes - 1)
		  {
		    $this->_tableau($pdf, $tab_top, $tab_height, $nexY, $fac);
		    $pdf->AddPage();
		    $nexY = $iniY;
		    $this->_pagehead($pdf, $fac);
		    $pdf->SetTextColor(0,0,0);
		    $pdf->SetFont('Arial','', 10);
		  }

	      }
	    $this->_tableau($pdf, $tab_top, $tab_height, $nexY, $fac);

	    $deja_regle = $fac->getSommePaiement();

	    $this->_tableau_tot($pdf, $fac, $deja_regle);

	    if ($deja_regle) {            
	      $this->_tableau_versements($pdf, $fac);
	    }

	    /*
	     * Mode de règlement
	     */
	    if ((! defined("FACTURE_CHQ_NUMBER") || ! FACTURE_CHQ_NUMBER) && (! defined("FACTURE_RIB_NUMBER") || ! FACTURE_RIB_NUMBER)) {
	      $pdf->SetXY ($this->marges['g'], 228);
	      $pdf->SetTextColor(200,0,0);
	      $pdf->SetFont('Arial','B',8);
	      $pdf->MultiCell(90, 3, $langs->trans("ErrorNoPaiementModeConfigured"),0,'L',0);
	      $pdf->MultiCell(90, 3, $langs->trans("ErrorCreateBankAccount"),0,'L',0);
	      $pdf->SetTextColor(0,0,0);
	    }

	    /*
	     * Propose mode règlement par CHQ
	     */
	    if (defined("FACTURE_CHQ_NUMBER"))
	      {
		if (FACTURE_CHQ_NUMBER > 0)
		  {
		    $account = new Account($this->db);
		    $account->fetch(FACTURE_CHQ_NUMBER);

		    $pdf->SetXY ($this->marges['g'], 225);
		    $pdf->SetFont('Arial','B',8);
		    $pdf->MultiCell(90, 3, "Règlement par chèque à l'ordre de ".$account->proprio." envoyé à:",0,'L',0);
		    $pdf->SetXY ($this->marges['g'], 230);
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

		    $cury=240;
		    $pdf->SetXY ($this->marges['g'], $cury);
		    $pdf->SetFont('Arial','B',8);
		    $pdf->MultiCell(90, 3, "Règlement par virement sur le compte ci-dessous:", 0, 'L', 0);
		    $cury=245;
		    $pdf->SetFont('Arial','B',6);
		    $pdf->line($this->marges['g'], $cury, $this->marges['g'], $cury+10 );
		    $pdf->SetXY ($this->marges['g'], $cury);
		    $pdf->MultiCell(18, 3, "Code banque", 0, 'C', 0);
		    $pdf->line($this->marges['g']+18, $cury, $this->marges['g']+18, $cury+10 );
		    $pdf->SetXY ($this->marges['g']+18, $cury);
		    $pdf->MultiCell(18, 3, "Code guichet", 0, 'C', 0);
		    $pdf->line($this->marges['g']+36, $cury, $this->marges['g']+36, $cury+10 );
		    $pdf->SetXY ($this->marges['g']+36, $cury);
		    $pdf->MultiCell(24, 3, "Numéro compte", 0, 'C', 0);
		    $pdf->line($this->marges['g']+60, $cury, $this->marges['g']+60, $cury+10 );
		    $pdf->SetXY ($this->marges['g']+60, $cury);
		    $pdf->MultiCell(13, 3, "Clé RIB", 0, 'C', 0);
		    $pdf->line($this->marges['g']+73, $cury, $this->marges['g']+73, $cury+10 );

		    $pdf->SetFont('Arial','',8);
		    $pdf->SetXY ($this->marges['g'], $cury+5);
		    $pdf->MultiCell(18, 3, $account->code_banque, 0, 'C', 0);
		    $pdf->SetXY ($this->marges['g']+18, $cury+5);
		    $pdf->MultiCell(18, 3, $account->code_guichet, 0, 'C', 0);
		    $pdf->SetXY ($this->marges['g']+36, $cury+5);
		    $pdf->MultiCell(24, 3, $account->number, 0, 'C', 0);
		    $pdf->SetXY ($this->marges['g']+60, $cury+5);
		    $pdf->MultiCell(13, 3, $account->cle_rib, 0, 'C', 0);
         
		    $pdf->SetXY ($this->marges['g'], $cury+15);
		    $pdf->MultiCell(90, 3, "Domiciliation : " . $account->domiciliation, 0, 'L', 0);
		    $pdf->SetXY ($this->marges['g'], $cury+25);
		    $pdf->MultiCell(90, 3, "Prefix IBAN : " . $account->iban_prefix, 0, 'L', 0);
		    $pdf->SetXY ($this->marges['g'], $cury+30);
		    $pdf->MultiCell(90, 3, "BIC : " . $account->bic, 0, 'L', 0);
		  }
	      }

	    /*
	     * Conditions de règlements
	     */
	    $pdf->SetFont('Arial','B',10);
	    $pdf->SetXY($this->marges['g'], 217);
	    $titre = "Conditions de réglement:";
	    $pdf->MultiCell(80, 5, $titre, 0, 'L');
	    $pdf->SetFont('Arial','',10);
	    $pdf->SetXY($this->marges['g']+44, 217);
	    $pdf->MultiCell(80, 5, $fac->cond_reglement_facture,0,'L');


	    /*
	     * Pied de page
	     */
	    $this->_pagefoot($pdf, $fac);
	    $pdf->AliasNbPages();
                
	    $pdf->Close();

	    $pdf->Output($file);

	    return 1;   // Pas d'erreur
	  }
	else
	  {
	    $this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
	    return 0;
	  }
      }
    else
      {
	$this->error=$langs->trans("ErrorConstantNotDefined","FAC_OUTPUTDIR");
	return 0;
      }
    $this->error=$langs->trans("ErrorUnknown");
    return 0;   // Erreur par defaut
  }


  /*
   *   \brief      Affiche tableau des versement
   *   \param      pdf     objet PDF
   *   \param      fac     objet facture
   */
  function _tableau_versements(&$pdf, $fac)
  {
    global $langs;
    $langs->load("main");
    $langs->load("bills");
        
    $tab3_posx = $this->marges['g']+110;
    $tab3_top = $this->marges['h']+235;
    $tab3_width = 80;
    $tab3_height = 4;

    $pdf->SetFont('Arial','',8);
    $pdf->SetXY ($tab3_posx, $tab3_top - 5);
    $pdf->MultiCell(60, 5, "Versements déjà effectués", 0, 'L', 0);

    $pdf->Rect($tab3_posx, $tab3_top-1, $tab3_width, $tab3_height);

    $pdf->SetXY ($tab3_posx, $tab3_top-1 );
    $pdf->MultiCell(20, 4, $langs->trans("Payment"), 0, 'L', 0);
    $pdf->SetXY ($tab3_posx+21, $tab3_top-1 );
    $pdf->MultiCell(20, 4, $langs->trans("Amount"), 0, 'L', 0);
    $pdf->SetXY ($tab3_posx+41, $tab3_top-1 );
    $pdf->MultiCell(20, 4, $langs->trans("Type"), 0, 'L', 0);
    $pdf->SetXY ($tab3_posx+60, $tab3_top-1 );
    $pdf->MultiCell(20, 4, $langs->trans("Ref"), 0, 'L', 0);

    $sql = "SELECT ".$this->db->pdate("p.datep")."as date, p.amount as amount, p.fk_paiement as type, p.num_paiement as num ";
    $sql.= "FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."paiement_facture as pf ";
    $sql.= "WHERE pf.fk_paiement = p.rowid and pf.fk_facture = ".$fac->id." ";
    $sql.= "ORDER BY p.datep";
    if ($this->db->query($sql))
      {
	$pdf->SetFont('Arial','',6);
	$num = $this->db->num_rows();
	$i=0; $y=0;
	while ($i < $num) {
	  $y+=3;
	  $row = $this->db->fetch_row();
    
	  $pdf->SetXY ($tab3_posx, $tab3_top+$y );
	  $pdf->MultiCell(20, 4, strftime("%d/%m/%y",$row[0]), 0, 'L', 0);
	  $pdf->SetXY ($tab3_posx+21, $tab3_top+$y);
	  $pdf->MultiCell(20, 4, $row[1], 0, 'L', 0);
	  $pdf->SetXY ($tab3_posx+41, $tab3_top+$y);
	  switch ($row[2])
	    {
	    case 1:
	      $oper = 'TIP';
	      break;
	    case 2:
	      $oper = 'VIR';
	      break;
	    case 3:
	      $oper = 'PRE';
	      break;
	    case 4:
	      $oper = 'LIQ';
	      break;
	    case 5:
	      $oper = 'VAD';
	      break;
	    case 6:
	      $oper = 'CB';
	      break;
	    case 7:
	      $oper = 'CHQ';
	      break;
	    }
	  $pdf->MultiCell(20, 4, $oper, 0, 'L', 0);
	  $pdf->SetXY ($tab3_posx+60, $tab3_top+$y);
	  $pdf->MultiCell(20, 4, $row[3], 0, 'L', 0);
    
	  $pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3 );

	  $i++;
	}
      }
    else
      {
	$this->error=$langs->trans("ErrorSQL")." $sql";
	return 0;
      }

  }

  /*
   *   \brief      Affiche le total à payer
   *   \param      pdf         objet PDF
   *   \param      fac         objet facture
   *   \param      deja_regle  montant deja regle
   */
  function _tableau_tot(&$pdf, $fac, $deja_regle)
  {
    global $langs;
    $langs->load("main");
    $langs->load("bills");

    $tab2_top = $this->marges['h']+202;
    $tab2_hl = 5;
    $tab2_height = $tab2_hl * 4;
    $pdf->SetFont('Arial','', 9);

    // Affiche la mention TVA non applicable selon option
    $pdf->SetXY ($this->marges['g'], $tab2_top + 0);
    if ($this->franchise==1)
      {
	$pdf->MultiCell(100, $tab2_hl, "* TVA non applicable art-293B du CGI", 0, 'L', 0);
      }

    // Tableau total
    $col1x=$this->marges['g']+110; $col2x=$this->marges['g']+164;
    $pdf->SetXY ($col1x, $tab2_top + 0);
    $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalHT"), 0, 'L', 0);

    $pdf->SetXY ($col2x, $tab2_top + 0);
    $pdf->MultiCell(26, $tab2_hl, price($fac->total_ht + $fac->remise), 0, 'R', 0);

    if ($fac->remise > 0)
      {
	$pdf->SetXY ($col1x, $tab2_top + $tab2_hl);
	$pdf->MultiCell($col2x-$col1x, $tab2_hl, "Remise globale", 0, 'L', 0);

	$pdf->SetXY ($col2x, $tab2_top + $tab2_hl);
	$pdf->MultiCell(26, $tab2_hl, "-".$fac->remise_percent."%", 0, 'R', 0);

	$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * 2);
	$pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total HT après remise", 0, 'L', 0);

	$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * 2);
	$pdf->MultiCell(26, $tab2_hl, price($fac->total_ht), 0, 'R', 0);

	$index = 3;
      }
    else
      {
	$index = 1;
      }

    $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
    $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalVAT"), 0, 'L', 0);

    $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
    $pdf->MultiCell(26, $tab2_hl, price($fac->total_tva), 0, 'R', 0);

    $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+1));
    $pdf->SetTextColor(22,137,210);
    $pdf->SetFont('Arial','B', 11);
    $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalTTC"), 0, 'L', 0);

    $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+1));
    $pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc), 0, 'R', 0);
    $pdf->SetTextColor(0,0,0);

    if ($deja_regle > 0)
      {
	$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+2));
	$pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("AlreadyPayed"), 0, 'L', 0);

	$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+2));
	$pdf->MultiCell(26, $tab2_hl, price($deja_regle), 0, 'R', 0);

	$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+3));
	$pdf->SetTextColor(22,137,210);
	$pdf->SetFont('Arial','B', 11);
	$pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("RemainderToPay"), 0, 'L', 0);

	$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+3));
	$pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc - $deja_regle), 0, 'R', 0);
	$pdf->SetTextColor(0,0,0);
      }
  }

  /*
   *   \brief      Affiche la grille des lignes de factures
   *   \param      pdf     objet PDF
   */
  function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $fac)
  {
    global $langs;
    $langs->load("main");
    $langs->load("bills");
        
    $pdf->line( $this->marges['g'], $tab_top+8, 210-$this->marges['d'], $tab_top+8 );
    $pdf->line( $this->marges['g'], $tab_top + $tab_height, 210-$this->marges['d'], $tab_top + $tab_height );

    $pdf->SetFont('Arial','B',10);

    $pdf->Text($this->marges['g']+2,$tab_top + 5, $langs->trans("Label"));
    if ($this->franchise!=1) $pdf->Text($this->marges['g']+120, $tab_top + 5, $langs->trans("VAT"));
    $pdf->Text($this->marges['g']+135, $tab_top + 5,'P.U. HT');
    $pdf->Text($this->marges['g']+153, $tab_top + 5, $langs->trans("Qté"));

    $nblignes = sizeof($fac->lignes);
    $rem=0;
    for ($i = 0 ; $i < $nblignes ; $i++)
      if ($fac->lignes[$i]->remise_percent) 
	{ 
	  $rem=1;
	}
    if ($rem==1)
      {
	$pdf->Text($this->marges['g']+163, $tab_top + 5,'Rem.');
      }
    $pdf->Text($this->marges['g']+175, $tab_top + 5, $langs->trans("TotalHT"));
  }

  /*
   *   \brief      Affiche en-tête facture
   *   \param      pdf     objet PDF
   *   \param      fac     objet facture
   */
  function _pagehead(&$pdf, $fac)
  {
    global $conf;
    global $langs;
    $langs->load("main");
    $langs->load("bills");
        
    $pdf->SetTextColor(0,0,60);
    $pdf->SetFont('Arial','B',13);

    $pdf->SetXY($this->marges['g'],6);

    /*
     * Logo
     */
    if (defined("FAC_PDF_LOGO") && FAC_PDF_LOGO)
      {
	if (file_exists(FAC_PDF_LOGO)) 
	  {
	    $taille=getimagesize(FAC_PDF_LOGO);
	    $longueur=$taille[0]/2.835;
	    $pdf->Image(FAC_PDF_LOGO, $this->marges['g'], $this->marges['h'], 0, 24, 'PNG');
	  }
	else {
	  $pdf->SetTextColor(200,0,0);
	  $pdf->SetFont('Arial','B',8);
	  $pdf->MultiCell(80, 3, $langs->trans("ErrorLogoFileNotFound",FAC_PDF_LOGO), 0, 'L');
	  $pdf->MultiCell(80, 3, $langs->trans("ErrorGoToModuleSetup"), 0, 'L');
	}
      }
    else if (defined("FAC_PDF_INTITULE"))
      {
	$pdf->MultiCell(80, 6, FAC_PDF_INTITULE, 0, 'L');
      }


    /*
     * Emetteur
     */
    $posy=$this->marges['h']+24;
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',8);
    $pdf->SetXY($this->marges['g'],$posy-5);
 

    $pdf->SetXY($this->marges['g'],$posy);
    $pdf->SetFillColor(255,255,255);
    $pdf->MultiCell(82, 34, "", 0, 'R', 1);


    $pdf->SetXY($this->marges['g'],$posy+4);

    if (defined("FAC_PDF_INTITULE2"))
      {
	$pdf->SetTextColor(0,0,60);
	$pdf->SetFont('Arial','B',12);
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
	$pdf->MultiCell(40, 4, "Tél. : ".FAC_PDF_TEL);
      }
    if (defined("FAC_PDF_MEL"))
      {
	$pdf->SetFont('Arial','',10);
	$pdf->MultiCell(60, 4, "émail : ".FAC_PDF_MEL);
      }
    if (defined("FAC_PDF_WWW"))
      {
	$pdf->SetFont('Arial','',10);
	$pdf->MultiCell(60, 4, FAC_PDF_WWW);
      }
    if (defined("FAC_PDF_SIRET"))
      {
	$pdf->SetFont('Arial','',7);
	$pdf->MultiCell(60, 4, "SIRET : ".FAC_PDF_SIRET);
      }
    elseif (defined("FAC_PDF_SIREN"))
      {
	$pdf->SetFont('Arial','',7);
	$pdf->MultiCell(60, 4, "SIREN : ".FAC_PDF_SIREN);
      }


    /*
     * Client
     */
    $posy=45;
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',8);
    $pdf->SetXY($this->marges['g']+100,$posy-5);
    $pdf->SetFont('Arial','B',11);
    $fac->fetch_client();
    $pdf->SetXY($this->marges['g']+100,$posy+4);
    $pdf->MultiCell(86,4, $fac->client->nom, 0, 'L');
    $pdf->SetFont('Arial','B',10);
    $pdf->SetXY($this->marges['g']+100,$posy+12);
    $pdf->MultiCell(86,4, $fac->client->adresse . "\n\n" . $fac->client->cp . " " . $fac->client->ville);

    /*
     * ref facture
     */
    $posy=65;
    $pdf->SetFont('Arial','B',13);
    $pdf->SetXY($this->marges['g'],$posy);
    $pdf->SetTextColor(0,0,0);
    $pdf->MultiCell(100, 10, $langs->trans("Bill")." du ".dolibarr_print_date($fac->date,"%d %B %Y"), '' , 'L');
    $pdf->SetFont('Arial','B',11);
    $pdf->SetXY($this->marges['g'],$posy+6);
    $pdf->SetTextColor(22,137,210);
    $pdf->MultiCell(100, 10, "N° : " . $fac->ref, '', 'L');
    $pdf->SetTextColor(0,0,0);

    /*
     * ref projet
     */
    if ($fac->projetid > 0)
      {
	$projet = New Project($fac->db);
	$projet->fetch($fac->projetid);
	$pdf->SetFont('Arial','',9);
	$pdf->MultiCell(60, 4, "Projet : ".$projet->title);
      }
    	    
    /*
     * ref propal
     */
    $sql = "SELECT ".$fac->db->pdate("p.datep")." as dp, p.ref, p.rowid as propalid";
    $sql .= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."fa_pr as fp WHERE fp.fk_propal = p.rowid AND fp.fk_facture = $fac->id";
    $result = $fac->db->query($sql);
    if ($result)
      {
	$objp = $fac->db->fetch_object();
	$pdf->SetFont('Arial','',9);
	$pdf->MultiCell(60, 4, "Réf. interne : ".$objp->ref);
      }  
 
    /*
     * monnaie
     */
    $pdf->SetTextColor(0,0,0);
    $pdf->SetFont('Arial','',10);
    $titre = $langs->trans("AmountInCurrency")." ".$conf->monnaie;
    $pdf->Text(200 - $pdf->GetStringWidth($titre), 94, $titre);
    /*
     */

  }

  /*
   *   \brief      Affiche le pied de page de la facture
   *   \param      pdf     objet PDF
   *   \param      fac     objet facture
   */
  function _pagefoot(&$pdf, $fac)
  {
    global $langs, $conf;
    $langs->load("main");
    $langs->load("bills");
    $langs->load("companies");
        
    $footy=13;
    $pdf->SetFont('Arial','',8);

    if (MAIN_INFO_CAPITAL) {
      $pdf->SetY(-$footy);
      $pdf->MultiCell(190, 3,"SARL au Capital de " . MAIN_INFO_CAPITAL." ".$conf->monnaie." - " . MAIN_INFO_RCS." - Identifiant professionnel: " . MAIN_INFO_SIREN , 0, 'C');
      $footy-=3;
    }

    // Affiche le numéro de TVA intracommunautaire
    if (MAIN_INFO_TVAINTRA == 'MAIN_INFO_TVAINTRA') {
      $pdf->SetY(-$footy);
      $pdf->SetTextColor(200,0,0);
      $pdf->SetFont('Arial','B',8);
      $pdf->MultiCell(190, 3, $langs->trans("ErrorVATIntraNotConfigured"),0,'L',0);
      $pdf->MultiCell(190, 3, $langs->trans("ErrorGoToGlobalSetup"),0,'L',0);
      $pdf->SetTextColor(0,0,0);
    }
    elseif (MAIN_INFO_TVAINTRA != '') {
      $pdf->SetY(-$footy);
      $pdf->MultiCell(190, 3,  $langs->trans("TVAIntra")." : ".MAIN_INFO_TVAINTRA, 0, 'C');
    }

    $pdf->SetXY(-15,-15);
    $pdf->MultiCell(10, 3, $pdf->PageNo().'/{nb}', 0, 'R');
  }

}

?>
