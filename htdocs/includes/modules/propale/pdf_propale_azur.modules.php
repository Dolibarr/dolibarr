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
*/

/**
       	\file       htdocs/includes/modules/propale/pdf_propale_azur.modules.php
		\ingroup    propale
		\brief      Fichier de la classe permettant de générer les propales au modèle Azur
		\author	    Laurent Destailleur
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT."/product.class.php");


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
        global $conf,$langs;

        $this->db = $db;
        $this->name = "azur";
        $this->description = "Modèle de propositions commerciales complet (logo...)";
        $this->format="A4";
 
        $this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Gere choix mode règlement FACTURE_CHQ_NUMBER, FACTURE_RIB_NUMBER
        $this->option_codeproduitservice = 1;      // Affiche code produit-service
        $this->option_tvaintra = 1;                // Affiche tva intra MAIN_INFO_TVAINTRA
        $this->option_capital = 1;                 // Affiche capital MAIN_INFO_CAPITAL
    	if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise') 
      		$this->franchise=1;

        // Recupere code pays de l'emmetteur
        $this->emetteur->code_pays=substr($langs->defaultlang,-2);    // Par defaut, si on trouve pas
        $sql  = "SELECT code from ".MAIN_DB_PREFIX."c_pays";
        $sql .= " WHERE rowid = '".$conf->global->MAIN_INFO_SOCIETE_PAYS."'";
        $result=$this->db->query($sql);
        if ($result) {
            $obj = $this->db->fetch_object($result);
            if ($obj->code) $this->emetteur->code_pays=$obj->code;
        }
        else {
            dolibarr_print_error($this->db);
        }
        $this->db->free($result);

        $this->tva=array();

        // Defini position des colonnes
        $this->posxdesc=11;
        $this->posxtva=121;
        $this->posxup=132;
        $this->posxqty=151;
        $this->posxdiscount=162;
        $this->postotalht=177;
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

                $pdf->SetDrawColor(128,128,128);

                $pdf->SetTitle($prop->ref);
                $pdf->SetSubject($langs->trans("Bill"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins(10, 10, 10);
                $pdf->SetAutoPageBreak(1,0);

                $this->_pagehead($pdf, $prop);

                $tab_top = 96;
                $tab_height = 110;

                $pdf->SetFont('Arial','', 9);
 
                $iniY = $tab_top + 8;
                $curY = $tab_top + 8;
                $nexY = $tab_top + 8;
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

                    if ($prop->lignes[$i]->product_id)
                    {
                        // Affiche code produit si ligne associée à un code produit
                        $prodser = new Product($this->db);

                        $prodser->fetch($prop->lignes[$i]->product_id);
                        if ($prodser->ref)
                        {
                            $libelleproduitservice=$langs->trans("Product")." ".$prodser->ref." - ".$libelleproduitservice;
                        }
                    }
                    if ($prop->lignes[$i]->date_start && $prop->lignes[$i]->date_end)
                    {
                        // Affichage durée si il y en a une
                        $libelleproduitservice.="\n(".$langs->trans("From")." ".dolibarr_print_date($prop->lignes[$i]->date_start)." ".$langs->trans("to")." ".dolibarr_print_date($prop->lignes[$i]->date_end).")";
                    }

                    $pdf->SetXY ($this->posxdesc-1, $curY);
                    $pdf->MultiCell(108, 4, $libelleproduitservice, 0, 'J');

                    $nexY = $pdf->GetY();

                    // TVA
                    $pdf->SetXY ($this->posxtva, $curY);
                    $pdf->MultiCell(10, 4, ($prop->lignes[$i]->tva_tx < 0 ? '*':'').abs($prop->lignes[$i]->tva_tx), 0, 'R');

                    // Prix unitaire HT avant remise
                    $pdf->SetXY ($this->posxup, $curY);
                    $pdf->MultiCell(18, 4, price($prop->lignes[$i]->subprice), 0, 'R', 0);

                    // Quantité
                    $pdf->SetXY ($this->posxqty, $curY);
                    $pdf->MultiCell(10, 4, $prop->lignes[$i]->qty, 0, 'R');

                    // Remise sur ligne
                    $pdf->SetXY ($this->posxdiscount, $curY);
                    if ($prop->lignes[$i]->remise_percent)
                    {
                        $pdf->MultiCell(14, 4, $prop->lignes[$i]->remise_percent."%", 0, 'R');
                    }

                    // Total HT ligne
                    $pdf->SetXY ($this->postotalht, $curY);
                    $total = price($prop->lignes[$i]->price * $prop->lignes[$i]->qty);
                    $pdf->MultiCell(23, 4, $total, 0, 'R', 0);

                    // Collecte des totaux par valeur de tva
                    // dans le tableau tva["taux"]=total_tva
					$tvaligne=$prop->lignes[$i]->price * $prop->lignes[$i]->qty;
					if ($prop->remise_percent) $tvaligne-=($tvaligne*$prop->remise_percent)/100;
					$this->tva[ (string)$prop->lignes[$i]->tva_tx ] += $tvaligne;

                    $nexY+=2;    // Passe espace entre les lignes

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

                $posy=$this->_tableau_tot($pdf, $prop, "");

                /*
                * Mode de règlement
                */
                if ((! defined("FACTURE_CHQ_NUMBER") || ! FACTURE_CHQ_NUMBER) && (! defined("FACTURE_RIB_NUMBER") || ! FACTURE_RIB_NUMBER))
                {
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
     *   \param      pdf         	Objet PDF
     *   \param      prop         	Objet propale
     *   \param      deja_regle  	Montant deja regle
     *   \return     y              Position pour suite
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
        $lltot = 200; $col1x = 120; $col2x = 182; $largcol2 = $lltot - $col2x;

        // Total HT
        $pdf->SetFillColor(256,256,256);
        $pdf->SetXY ($col1x, $tab2_top + 0);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalHT"), 0, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + 0);
        $pdf->MultiCell($largcol2, $tab2_hl, price($prop->total_ht + $prop->remise), 0, 'R', 1);

        // Remise globale
        if ($prop->remise > 0)
        {
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("GlobalDiscount"), 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl);
            $pdf->MultiCell($largcol2, $tab2_hl, "-".$prop->remise_percent."%", 0, 'R', 1);

            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total HT après remise", 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell($largcol2, $tab2_hl, price($prop->total_ht), 0, 'R', 0);

            $index = 2;
        }
        else
        {
            $index = 0;
        }

        // Affichage des totaux de TVA par taux (conformément à réglementation)
        $atleastoneratenotnull=0;
        $pdf->SetFillColor(248,248,248);
        foreach( $this->tva as $tvakey => $tvaval )
        {
            if ($tvakey)    // On affiche pas taux 0
            {
                $atleastoneratenotnull++;
                
                $index++;
            	$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
                $tvacompl = ( (float)$tvakey < 0 ) ? " (".$langs->trans("NonPercuRecuperable").")" : '' ; 
                $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalVAT").' '.abs($tvakey).'%'.$tvacompl, 0, 'L', 1);
    
                $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($largcol2, $tab2_hl, price($tvaval * abs((float)$tvakey) / 100 ), 0, 'R', 1);
            }
        }
        if (! $atleastoneratenotnull)
        {
            $index++;
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalVAT"), 0, 'L', 1);
    
            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($prop->total_tva), 0, 'R', 1);
        }
        
        $useborder=0;
        
        $index++;
        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFillColor(224,224,224);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalTTC"), $useborder, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($prop->total_ttc), $useborder, 'R', 1);
        $pdf->SetFont('Arial','', 9);
        $pdf->SetTextColor(0,0,0);

        if ($deja_regle > 0)
        {
            $index++;
            
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("AlreadyPayed"), 0, 'L', 0);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle), 0, 'R', 0);

            $index++;
            $pdf->SetTextColor(0,0,60);
            //$pdf->SetFont('Arial','B', 9);
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("RemainderToPay"), $useborder, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($prop->total_ttc - $deja_regle), $useborder, 'R', 1);
            $pdf->SetFont('Arial','', 9);
            $pdf->SetTextColor(0,0,0);
        }
    
        $index++;
        return ($tab2_top + ($tab2_hl * $index));
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
        $pdf->line( 10, $tab_top+6, 200, $tab_top+6 );

        $pdf->SetFont('Arial','',10);

        $pdf->SetXY ($this->posxdesc-1, $tab_top+2);
        $pdf->MultiCell(108,2, $langs->trans("Designation"),'','L');

        $pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxtva-1, $tab_top+2);
        $pdf->MultiCell(12,2, $langs->trans("VAT"),'','C');

        $pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxup-1, $tab_top+2);
        $pdf->MultiCell(18,2, $langs->trans("PriceUHT"),'','C');

        $pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxqty-1, $tab_top+2);
        $pdf->MultiCell(11,2, $langs->trans("Qty"),'','C');

        $pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxdiscount-1, $tab_top+2);
        $pdf->MultiCell(16,2, $langs->trans("Discount"),'','C');

        $pdf->line($this->postotalht-1, $tab_top, $this->postotalht-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->postotalht-1, $tab_top+2);
        $pdf->MultiCell(23,2, $langs->trans("TotalHT"),'','C');

    }

    /*
    *   \brief      Affiche en-tête propale
    *   \param      pdf     objet PDF
    *   \param      fac     objet propale
    */
    function _pagehead(&$pdf, $prop)
    {
        global $langs,$conf;

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
            if (file_exists(FAC_PDF_LOGO))
            {
                $pdf->Image(FAC_PDF_LOGO, 10, 5, 0, 24);
            }
            else
            {
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

        // Emetteur
        $posy=42;
        $hautcadre=40;
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(10,$posy-5);
        $pdf->MultiCell(66,5, $langs->trans("BillFrom").":");


        $pdf->SetXY(10,$posy);
        $pdf->SetFillColor(230,230,230);
        $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);


        $pdf->SetXY(10,$posy+3);

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
        $pdf->MultiCell(80, 4, "\n");
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

        // Client destinataire
        $posy=42;
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(102,$posy-5);
        $pdf->MultiCell(80,5, $langs->trans("BillTo").":");
		$prop->fetch_client();

        // Cadre client destinataire
        $pdf->rect(100, $posy, 100, $hautcadre);

		// Nom client
        $pdf->SetXY(102,$posy+3);
        $pdf->SetFont('Arial','B',11);
        $pdf->MultiCell(106,4, $prop->client->nom, 0, 'L');

		// Caractéristiques client
        $carac_client=$prop->client->adresse;
        $carac_client.="\n".$prop->client->cp . " " . $prop->client->ville."\n";
		if ($prop->client->tva_intra) $carac_client.="\n".$langs->trans("VATIntraShort").': '.$prop->client->tva_intra;
        $pdf->SetFont('Arial','',9);
        $pdf->SetXY(102,$posy+7);
        $pdf->MultiCell(86,4, $carac_client);

        // Montants exprimés en
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',10);
        $titre = $langs->trans("AmountInCurrency",$langs->trans("Currency".$conf->monnaie));
        $pdf->Text(200 - $pdf->GetStringWidth($titre), 94, $titre);

    }

    /*
    *   \brief      Affiche le pied de page de la propale
    *   \param      pdf     objet PDF
    *   \param      prop    objet propale
    */
   function _pagefoot(&$pdf, $prop)
    {
        global $langs, $conf;
        $langs->load("main");
        $langs->load("bills");
        $langs->load("companies");
        
        $html=new Form($this->db);
        
        $footy=14;
        $pdf->SetY(-$footy);
        $pdf->SetDrawColor(224,224,224);
        $pdf->line(10, 282, 200, 282);
        
        $footy=13;
        $pdf->SetFont('Arial','',8);

        // Premiere ligne d'info réglementaires
        $ligne="";
        if ($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE)
        {
            $ligne.=($ligne?" - ":"").$html->forme_juridique_name($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
        }
        if ($conf->global->MAIN_INFO_CAPITAL)
        {
            $ligne.=($ligne?" - ":"").$langs->trans("CapitalOf",$conf->global->MAIN_INFO_CAPITAL)." ".$langs->trans("Currency".$conf->monnaie);
        }
        if ($conf->global->MAIN_INFO_SIRET)
        {
            $ligne.=($ligne?" - ":"").$langs->transcountry("ProfId2",$this->emetteur->code_pays).": ".$conf->global->MAIN_INFO_SIRET;
        }
        if ($conf->global->MAIN_INFO_SIREN && (! $conf->global->MAIN_INFO_SIRET || $this->emetteur->code_pays != 'FR'))
        {
            $ligne.=($ligne?" - ":"").$langs->transcountry("ProfId1",$this->emetteur->code_pays).": ".$conf->global->MAIN_INFO_SIREN;
        }
        if ($conf->global->MAIN_INFO_APE)
        {
            $ligne.=($ligne?" - ":"").$langs->transcountry("ProfId3",$this->emetteur->code_pays).": ".MAIN_INFO_APE;
        }

        if ($ligne)
        {
            $pdf->SetXY(8,-$footy);
            $pdf->MultiCell(200, 2, $ligne, 0, 'C', 0);
        }

        // Deuxieme ligne d'info réglementaires
        $ligne="";
        if ($conf->global->MAIN_INFO_RCS)
        {
            $ligne.=($ligne?" - ":"").$langs->transcountry("ProfId4",$this->emetteur->code_pays).": ".$conf->global->MAIN_INFO_RCS;
        }
        if ($conf->global->MAIN_INFO_TVAINTRA != '')
        {
            $ligne.=($ligne?" - ":"").$langs->trans("VATIntraShort").": ".$conf->global->MAIN_INFO_TVAINTRA;
        }

        if ($ligne)
        {
            $footy-=3;
            $pdf->SetXY(8,-$footy);
            $pdf->MultiCell(200, 2, $ligne , 0, 'C', 0);
        }
        
        $pdf->SetXY(-20,-$footy);
        $pdf->MultiCell(10, 2, $pdf->PageNo().'/{nb}', 0, 'R', 0);
    }

}

?>
