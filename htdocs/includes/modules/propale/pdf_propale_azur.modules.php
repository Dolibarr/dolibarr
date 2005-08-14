<?php
/* Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/**
       	\file       htdocs/includes/modules/propale/pdf_propale_azur.modules.php
		\ingroup    propale
		\brief      Fichier de la classe permettant de générer les propales au modèle Azur
		\author	    Laurent Destailleur
		\version    $Revision$
*/


/**
	    \class      pdf_propale_azur
		\brief      Classe permettant de générer les propales au modèle Azur
*/

class pdf_propale_azur extends ModelePDFPropales
{
    
    /**
			\brief      Constructeur
    		\param	    db		Handler accès base de donnée
    */
    function pdf_propale_azur($db)
    {
      	global $langs;

        $this->db = $db;
        $this->name = "azur";
        $this->description = "Modèle de propositions commerciales complet (logo...)";
        $this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Gere choix mode règlement FACTURE_CHQ_NUMBER, FACTURE_RIB_NUMBER
        $this->option_codeproduitservice = 1;      // Affiche code produit-service FACTURE_CODEPRODUITSERVICE
        $this->option_tvaintra = 1;                // Affiche tva intra MAIN_INFO_TVAINTRA
        $this->option_capital = 1;                 // Affiche capital MAIN_INFO_CAPITAL
    	if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise') 
      		$this->franchise=1;

        // Recupere code pays
        $this->code_pays=substr($langs->defaultlang,-2);    // Par defaut, pays de la localisation
        $sql  = "SELECT code from ".MAIN_DB_PREFIX."c_pays";
        $sql .= " WHERE rowid = ".MAIN_INFO_SOCIETE_PAYS;
        $result=$this->db->query($sql);
        if ($result) {
            $obj = $this->db->fetch_object($result);
            if ($obj->code) $this->code_pays=$obj->code;
        }
        else {
            dolibarr_print_error($this->db);
        }
        $this->db->free($result);
    }


    /**
    	    \brief      Renvoi dernière erreur
            \return     string      Dernière erreur
    */
    function pdferror() 
    {
      return $this->error;
    }

    /**
    		\brief      Fonction générant la propale sur le disque
    		\param	    id	        Id de la propale à générer
    		\return	    int         1=ok, 0=ko
            \remarks    Variables utilisées
    		\remarks    MAIN_INFO_SOCIETE_NOM
    		\remarks    MAIN_INFO_SIRET
    		\remarks    MAIN_INFO_SIREN
    		\remarks    MAIN_INFO_RCS
    		\remarks    MAIN_INFO_CAPITAL
    		\remarks    MAIN_INFO_TVAINTRA
            \remarks    FAC_PDF_LOGO
    		\remarks    FACTURE_CODEPRODUITSERVICE
    		\remarks    FACTURE_CHQ_NUMBER
    		\remarks    FACTURE_RIB_NUMBER
    		\remarks    FAC_PDF_INTITULE
    		\remarks    FAC_PDF_TEL
    		\remarks    FAC_PDF_ADRESSE
    */
    function write_pdf_file($id)
    {
        global $user,$langs,$conf;

        $langs->load("main");
        $langs->load("bills");
        $langs->load("products");
        $langs->load("propal");

        if ($conf->propal->dir_output)
        {
            $prop = new Propal($this->db,"",$id);
            $prop->fetch($id);

			$propref = sanitize_string($prop->ref);
			$dir = $conf->propal->dir_output . "/" . $propref;
			$file = $dir . "/" . $propref . ".pdf";

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
                // Initialisation propale vierge
                $pdf=new FPDF('P','mm','A4');
                $pdf->Open();
                $pdf->AddPage();

                $this->_pagehead($pdf, $prop);

                $pdf->SetTitle($prop->ref);
                $pdf->SetSubject($langs->trans("Bill"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins(10, 10, 10);
                $pdf->SetAutoPageBreak(1,0);

                $tab_top = 96;
                $tab_height = 110;

                $pdf->SetFillColor(220,220,220);
                $pdf->SetFont('Arial','', 9);
                $pdf->SetXY (10, $tab_top + 10 );

                $iniY = $pdf->GetY();
                $curY = $pdf->GetY();
                $nexY = $pdf->GetY();
                $nblignes = sizeof($prop->lignes);

                // Boucle sur les lignes
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    $curY = $nexY;

                    // Description produit
                    $libelleproduitservice=$prop->lignes[$i]->libelle;
                    if ($prop->lignes[$i]->desc&&$prop->lignes[$i]->desc!=$prop->lignes[$i]->libelle)
                    {
                        if ($libelleproduitservice) $libelleproduitservice.="\n";
                        $libelleproduitservice.=$prop->lignes[$i]->desc;
                    }
                    $pdf->SetXY (11, $curY );

                    if ($conf->global->PROPALE_CODEPRODUITSERVICE && $prop->lignes[$i]->product_id)
                    {
                        // Affiche code produit si ligne associée à un code produit
                        $prodser = new Product($this->db);

                        $prodser->fetch($prop->lignes[$i]->product_id);
                        if ($prodser->ref) {
                            $libelleproduitservice=$langs->trans("ProductCode")." ".$prodser->ref." - ".$libelleproduitservice;
                        }
                    }
                    if ($prop->lignes[$i]->date_start && $prop->lignes[$i]->date_end) {
                        // Affichage durée si il y en a une
                        $libelleproduitservice.="\n(".$langs->trans("From")." ".dolibarr_print_date($prop->lignes[$i]->date_start)." ".$langs->trans("to")." ".dolibarr_print_date($prop->lignes[$i]->date_end).")";
                    }
                    $pdf->MultiCell(108, 5, $libelleproduitservice, 0, 'J');

                    $nexY = $pdf->GetY();

                    // TVA
                    $pdf->SetXY (121, $curY);
                    $pdf->MultiCell(10, 5, $prop->lignes[$i]->tva_tx, 0, 'C');

                    // Prix unitaire HT avant remise
                    $pdf->SetXY (133, $curY);
                    $pdf->MultiCell(16, 5, price($prop->lignes[$i]->subprice), 0, 'R', 0);

                    // Quantité
                    $pdf->SetXY (151, $curY);
                    $pdf->MultiCell(10, 5, $prop->lignes[$i]->qty, 0, 'R');

                    // Remise sur ligne
                    $pdf->SetXY (163, $curY);
                    if ($prop->lignes[$i]->remise_percent) {
                        $pdf->MultiCell(14, 5, $prop->lignes[$i]->remise_percent."%", 0, 'R');
                    }

                    // Total HT
                    $pdf->SetXY (173, $curY);
                    $total = price($prop->lignes[$i]->price * $prop->lignes[$i]->qty);
                    $pdf->MultiCell(26, 5, $total, 0, 'R', 0);


                    if ($nexY > 200 && $i < $nblignes - 1)
                    {
                        $this->_tableau($pdf, $tab_top, $tab_height, $nexY);
                        $pdf->AddPage();
                        $nexY = $iniY;
                        $this->_pagehead($pdf, $prop);
                        $pdf->SetTextColor(0,0,0);
                        $pdf->SetFont('Arial','', 10);
                    }

                }
                $this->_tableau($pdf, $tab_top, $tab_height, $nexY);

                $this->_tableau_tot($pdf, $prop, "");

                /*
                * Mode de règlement
                */
                if ((! defined("FACTURE_CHQ_NUMBER") || ! FACTURE_CHQ_NUMBER) && (! defined("FACTURE_RIB_NUMBER") || ! FACTURE_RIB_NUMBER)) {
                    $pdf->SetXY (10, 228);
                    $pdf->SetTextColor(200,0,0);
                    $pdf->SetFont('Arial','B',8);
                    $pdf->MultiCell(90, 3, $langs->trans("ErrorNoPaiementModeConfigured"),0,'L',0);
                    $pdf->MultiCell(90, 3, $langs->trans("ErrorCreateBankAccount"),0,'L',0);
                    $pdf->SetTextColor(0,0,0);
                }

                /*
                * Propose mode règlement par CHQ
                */
                /*
                if (defined("FACTURE_CHQ_NUMBER"))
                {
                    if (FACTURE_CHQ_NUMBER > 0)
                    {
                        $account = new Account($this->db);
                        $account->fetch(FACTURE_CHQ_NUMBER);

                        $pdf->SetXY (10, 227);
                        $pdf->SetFont('Arial','B',8);
                        $pdf->MultiCell(90, 3, "Règlement par chèque à l'ordre de ".$account->proprio." envoyé à:",0,'L',0);
                        $pdf->SetXY (10, 231);
                        $pdf->SetFont('Arial','',8);
                        $pdf->MultiCell(80, 3, $account->adresse_proprio, 0, 'L', 0);
                    }
                }
                */
                
                /*
                * Propose mode règlement par RIB
                */
                /*
                if (defined("FACTURE_RIB_NUMBER"))
                {
                    if (FACTURE_RIB_NUMBER > 0)
                    {
                        $account = new Account($this->db);
                        $account->fetch(FACTURE_RIB_NUMBER);

                        $this->marges['g']=10;
                        
                        $cury=242;
                        $pdf->SetXY ($this->marges['g'], $cury);
                        $pdf->SetFont('Arial','B',8);
                        $pdf->MultiCell(90, 3, "Règlement par virement sur le compte bancaire suivant:", 0, 'L', 0);
                        $cury+=4;
                        $pdf->SetFont('Arial','B',6);
                        $pdf->line($this->marges['g']+1, $cury, $this->marges['g']+1, $cury+10 );
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
         
                        $pdf->SetXY ($this->marges['g'], $cury+12);
                        $pdf->MultiCell(90, 3, "Domiciliation : " . $account->domiciliation, 0, 'L', 0);
                        $pdf->SetXY ($this->marges['g'], $cury+22);
                        $pdf->MultiCell(90, 3, "Prefix IBAN : " . $account->iban_prefix, 0, 'L', 0);
                        $pdf->SetXY ($this->marges['g'], $cury+25);
                        $pdf->MultiCell(90, 3, "BIC : " . $account->bic, 0, 'L', 0);

                    }
                }
                */
                
                /*
                 * Conditions de règlements
                 */
                /* Pour l'instant les conditions de règlement ne sont pas gérées sur les propales */
                /*
                $pdf->SetFont('Arial','B',10);
                $pdf->SetXY(10, 217);
                $titre = "Conditions de réglement:";
                $pdf->MultiCell(80, 5, $titre, 0, 'L');
                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(54, 217);
                $pdf->MultiCell(80, 5, $prop->cond_reglement_facture,0,'L');
                */

                /*
                 * Pied de page
                 */
                $this->_pagefoot($pdf, $prop);
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
            $this->error=$langs->trans("ErrorConstantNotDefined","PROP_OUTPUTDIR");
            return 0;
        }
        $this->error=$langs->trans("ErrorUnknown");
        return 0;   // Erreur par defaut
    }

    /*
    *   \brief      Affiche le total à payer
    *   \param      pdf         objet PDF
    *   \param      fac         objet propale
    *   \param      deja_regle  montant deja regle
    */
    function _tableau_tot(&$pdf, $prop, $deja_regle)
    {
        global $langs;
        $langs->load("main");
        $langs->load("bills");

        $tab2_top = 207;
        $tab2_hl = 5;
        $tab2_height = $tab2_hl * 4;
        $pdf->SetFont('Arial','', 9);

        // Affiche la mention TVA non applicable selon option
        $pdf->SetXY (10, $tab2_top + 0);
    	if ($this->franchise==1)
      	{
            $pdf->MultiCell(100, $tab2_hl, "* TVA non applicable art-293B du CGI", 0, 'L', 0);
        }

        // Tableau total
        $col1x=120; $col2x=174;
        $pdf->SetXY ($col1x, $tab2_top + 0);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalHT"), 0, 'L', 0);

        $pdf->SetXY ($col2x, $tab2_top + 0);
        $pdf->MultiCell(26, $tab2_hl, price($prop->total_ht + $prop->remise), 0, 'R', 0);

        if ($prop->remise > 0)
        {
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("GlobalDiscount"), 0, 'L', 0);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl);
            $pdf->MultiCell(26, $tab2_hl, "-".$prop->remise_percent."%", 0, 'R', 0);

            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total HT après remise", 0, 'L', 0);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell(26, $tab2_hl, price($prop->total_ht), 0, 'R', 0);

            $index = 3;
        }
        else
        {
            $index = 1;
        }

        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalVAT"), 0, 'L', 0);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell(26, $tab2_hl, price($prop->total_tva), 0, 'R', 0);

        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+1));
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Arial','B', 9);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalTTC"), 0, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+1));
        $pdf->MultiCell(26, $tab2_hl, price($prop->total_ttc), 0, 'R', 1);
        $pdf->SetFont('Arial','', 9);
        $pdf->SetTextColor(0,0,0);

        if ($deja_regle > 0)
        {
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+2));
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("AlreadyPayed"), 0, 'L', 0);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+2));
            $pdf->MultiCell(26, $tab2_hl, price($deja_regle), 0, 'R', 0);

            $pdf->SetTextColor(0,0,60);
            $pdf->SetFont('Arial','B', 9);
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+3));
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("RemainderToPay"), 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+3));
            $pdf->MultiCell(26, $tab2_hl, price($prop->total_ttc - $deja_regle), 0, 'R', 1);
            $pdf->SetFont('Arial','', 9);
            $pdf->SetTextColor(0,0,0);
        }
    }

    /*
    *   \brief      Affiche la grille des lignes de propales
    *   \param      pdf     objet PDF
    */
    function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
    {
        global $langs;
        $langs->load("main");
        $langs->load("bills");
        
        $pdf->Rect( 10, $tab_top, 190, $tab_height);
        $pdf->line( 10, $tab_top+8, 200, $tab_top+8 );

        $pdf->SetFont('Arial','',10);

        $pdf->Text(12,$tab_top + 5, $langs->trans("Label"));

        $pdf->line(120, $tab_top, 120, $tab_top + $tab_height);
        $pdf->Text(122, $tab_top + 5, $langs->trans("VAT"));

        $pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
        $pdf->Text(135, $tab_top + 5,$langs->trans("PriceUHT"));

        $pdf->line(150, $tab_top, 150, $tab_top + $tab_height);
        $pdf->Text(153, $tab_top + 5, $langs->trans("Qty"));

        $pdf->line(162, $tab_top, 162, $tab_top + $tab_height);
        $pdf->Text(163, $tab_top + 5,$langs->trans("Discount"));

        $pdf->line(177, $tab_top, 177, $tab_top + $tab_height);
        $pdf->Text(185, $tab_top + 5, $langs->trans("TotalHT"));

    }

    /*
    *   \brief      Affiche en-tête propale
    *   \param      pdf     objet PDF
    *   \param      fac     objet propale
    */
    function _pagehead(&$pdf, $prop)
    {
        global $conf;
        global $langs;
        $langs->load("main");
        $langs->load("bills");
        $langs->load("propal");
        $langs->load("companies");
        
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Arial','B',13);

        $pdf->SetXY(10,6);

		// Logo
        if (defined("FAC_PDF_LOGO") && FAC_PDF_LOGO)
        {
            if (file_exists(FAC_PDF_LOGO)) {
                $pdf->Image(FAC_PDF_LOGO, 10, 5, 0, 24, 'PNG');
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

        $pdf->SetFont('Arial','B',13);
        $pdf->SetXY(100,5);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 10, $langs->trans("Proposal")." ".$prop->ref, '' , 'R');
        $pdf->SetFont('Arial','',12);
        $pdf->SetXY(100,11);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 10, $langs->trans("Date")." : " . dolibarr_print_date($prop->date,"%d %b %Y"), '', 'R');

        /*
        * Emetteur
        */
        $posy=42;
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(10,$posy-5);
        $pdf->MultiCell(66,5, $langs->trans("BillFrom").":");


        $pdf->SetXY(10,$posy);
        $pdf->SetFillColor(230,230,230);
        $pdf->MultiCell(82, 34, "", 0, 'R', 1);


        $pdf->SetXY(10,$posy+4);

        // Nom emetteur
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Arial','B',11);
        if (defined("FAC_PDF_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM)  // Prioritaire sur MAIN_INFO_SOCIETE_NOM
        {
        $pdf->MultiCell(80, 4, FAC_PDF_SOCIETE_NOM, 0, 'L');
          }
        else                                                        // Par defaut
          {
        $pdf->MultiCell(80, 4, MAIN_INFO_SOCIETE_NOM, 0, 'L');
        }

        // Caractéristiques emetteur
        $pdf->SetFont('Arial','',9);
        if (defined("FAC_PDF_ADRESSE"))
        {
            $pdf->MultiCell(80, 4, FAC_PDF_ADRESSE);
        }
        if (defined("FAC_PDF_TEL") && FAC_PDF_TEL)
        {
            $pdf->MultiCell(80, 4, $langs->trans("Phone").": ".FAC_PDF_TEL);
        }
        if (defined("FAC_PDF_FAX") && FAC_PDF_FAX)
        {
            $pdf->MultiCell(80, 4, $langs->trans("Fax").": ".FAC_PDF_FAX);
        }
		if (defined("FAC_PDF_MEL") && FAC_PDF_MEL)
		{
			$pdf->MultiCell(80, 4, $langs->trans("Email").": ".FAC_PDF_MEL);
		}
		if (defined("FAC_PDF_WWW") && FAC_PDF_WWW)
		{
			$pdf->MultiCell(80, 4, $langs->trans("Web").": ".FAC_PDF_WWW);
        }

        $pdf->SetFont('Arial','',7);
        if (defined("MAIN_INFO_SIREN") && MAIN_INFO_SIREN)
        {
            $pdf->MultiCell(80, 4, $langs->transcountry("ProfId1",$this->code_pays).": ".MAIN_INFO_SIREN);
        }
        elseif (defined("MAIN_INFO_SIRET") && MAIN_INFO_SIRET)
        {
            $pdf->MultiCell(80, 4, $langs->transcountry("ProfId2",$this->code_pays).": ".MAIN_INFO_SIRET);
        }


        /*
        * Client
        */
        $posy=42;
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(102,$posy-5);
        $pdf->MultiCell(80,5, $langs->trans("BillTo").":");
		$prop->fetch_client();
		// Nom client
        $pdf->SetXY(102,$posy+4);
        $pdf->SetFont('Arial','B',11);
        $pdf->MultiCell(86,4, $prop->client->nom, 0, 'L');

		// Caractéristiques client
        $pdf->SetFont('Arial','B',9);
        $pdf->SetXY(102,$posy+12);
        $pdf->MultiCell(86,4, $prop->client->adresse . "\n" . $prop->client->cp . " " . $prop->client->ville);
        $pdf->rect(100, $posy, 100, 34);

        /*
        *
        */
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',10);
        $titre = $langs->trans("AmountInCurrency")." ".$langs->trans("Currency".$conf->monnaie);
        $pdf->Text(200 - $pdf->GetStringWidth($titre), 94, $titre);
        /*
        */

    }

    /*
    *   \brief      Affiche le pied de page de la propale
    *   \param      pdf     objet PDF
    *   \param      fac     objet propale
    */
   function _pagefoot(&$pdf, $prop)
    {
        global $langs, $conf;
        $langs->load("main");
        $langs->load("bills");
        $langs->load("companies");
        
        $footy=13;
        $pdf->SetFont('Arial','',8);

        if (defined(MAIN_INFO_CAPITAL)) {
            $pdf->SetY(-$footy);
            $ligne="SARL au Capital de " . MAIN_INFO_CAPITAL." ".$conf->monnaie;
            if (defined(MAIN_INFO_SIREN) && MAIN_INFO_SIREN) {
                $ligne.=" - ".$langs->transcountry("ProfId2",$this->code_pays).": ".MAIN_INFO_SIREN;
            }
            if (defined(MAIN_INFO_RCS) && MAIN_INFO_RCS) {
                $ligne.=" - ".$langs->transcountry("ProfId3",$this->code_pays).": ".MAIN_INFO_RCS;
            }
            $pdf->MultiCell(190, 3, $ligne, 0, 'C');
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

        $pdf->SetXY(-10,-10);
        $pdf->MultiCell(10, 3, $pdf->PageNo().'/{nb}', 0, 'R');
    }

}

?>
