<?php
/* Copyright (C) 2004-2006 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis.houssin@cap-networks.com>
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
       	\file       htdocs/livraison/mods/pdf/pdf_typhon.modules.php
		\ingroup    livraison
		\brief      Fichier de la classe permettant de générer les bons de livraison au modèle Typhon
		\author	    Laurent Destailleur
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/livraison/mods/modules_livraison.php");
require_once(DOL_DOCUMENT_ROOT."/livraison/livraison.class.php");


/**
	    \class      pdf_typhon
		\brief      Classe permettant de générer les bons de livraison au modèle Typhon
*/

class pdf_jungle extends ModelePDFDeliveryOrder
{
    
    /**
			\brief      Constructeur
    		\param	    db		Handler accès base de donnée
    */
    function pdf_jungle($db)
    {
        global $conf,$langs;

        $this->db = $db;
        $this->name = "jungle";
        $this->description = "Modèle de bon de livraison complet (logo...)";

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur,$this->page_hauteur);
        $this->marge_gauche=10;
        $this->marge_droite=10;
        $this->marge_haute=10;
        $this->marge_basse=10;
 
        $this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Gere choix mode règlement FACTURE_CHQ_NUMBER, FACTURE_RIB_NUMBER
        $this->option_codeproduitservice = 1;      // Affiche code produit-service
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
        $this->posxref=$this->marge_gauche+1;
        $this->posxdesc=30;
        $this->posxtva=121;
        $this->posxup=132;
        $this->posxqty=177;
        $this->posxdiscount=162;
        $this->postotalht=177;
       
        $this->atleastoneratenotnull=0;
        $this->atleastonediscount=0;
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
    		\brief      Fonction générant la commande sur le disque
    		\param	    id	        Id de la commande à générer
    		\return	    int         1=ok, 0=ko
            \remarks    Variables utilisées
    		\remarks    MAIN_INFO_SOCIETE_NOM
    		\remarks    MAIN_INFO_ADRESSE
    		\remarks    MAIN_INFO_CP
    		\remarks    MAIN_INFO_VILLE
    		\remarks    MAIN_INFO_TEL
    		\remarks    MAIN_INFO_FAX
    		\remarks    MAIN_INFO_WEB
    		\remarks    MAIN_INFO_SIRET
    		\remarks    MAIN_INFO_SIREN
    		\remarks    MAIN_INFO_RCS
    		\remarks    MAIN_INFO_CAPITAL
    		\remarks    MAIN_INFO_TVAINTRA
            \remarks    MAIN_INFO_LOGO
    */
    function write_pdf_file($id)
    {
        global $user,$langs,$conf;

        $langs->load("main");
        $langs->load("bills");
        $langs->load("products");
        $langs->load("deliveries");
        $langs->load("orders");

        if ($conf->livraison->dir_output)
        {
            $delivery = new Livraison($this->db);
            $delivery->fetch($id);
            $delivery->id = $id;
            $lignesdelivery = $delivery->fetch_lignes();
            
            $nblignes = sizeof($lignesdelivery);

			      $deliveryref = sanitize_string($delivery->ref);
			      $dir = $conf->livraison->dir_output . "/" . $deliveryref;
			      $file = $dir . "/" . $deliveryref . ".pdf";

            if (! file_exists($dir))
            {
                if (create_exdir($dir) < 0)
                {
					          $this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
                    return 0;
                }
				
            }

            if (file_exists($dir))
            {
                // Initialisation document vierge
                $pdf=new FPDI_Protection('P','mm',$this->format);
                               		
		           // Protection et encryption du pdf
               if ($conf->global->PDF_SECURITY_ENCRYPTION)
               {
     	           $pdfrights = array('print'); // Ne permet que l'impression du document
    	           $pdfuserpass = ''; // Mot de passe pour l'utilisateur final
     	           $pdfownerpass = NULL; // Mot de passe du propriétaire, créé aléatoirement si pas défini
     	           $pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
               }

                $pdf->Open();
                $pdf->AddPage();

                $pdf->SetDrawColor(128,128,128);

                $pdf->SetTitle($delivery->ref);
                $pdf->SetSubject($langs->transnoentities("DeliveryOrder"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1,0);
/*
                // Positionne $this->atleastonediscount si on a au moins une remise
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    if ($delivery->lignes[$i]->remise_percent)
                    {
                        $this->atleastonediscount++;
                    }
                }
*/
                $this->_pagehead($pdf, $delivery);

                $pagenb = 1;
                $tab_top = 100;
                $tab_top_newpage = 50;
                $tab_height = 160;
                $tab_height_one = 130;
                $tab_height_newpage = 210;

                $iniY = $tab_top + 8;
                $curY = $tab_top + 8;
                $nexY = $tab_top + 8;

                // Boucle sur les lignes
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    $curY = $nexY;

                    // Description de la ligne produit
                    $libelleproduitservice=$lignesdelivery[$i]->label;
                    if ($lignesdelivery[$i]->description&&$lignesdelivery[$i]->description!=$lignesdelivery[$i]->label)
                    {
                        if ($libelleproduitservice) $libelleproduitservice.="\n";
                        $libelleproduitservice.=$lignesdelivery[$i]->description;
                    }
                    // Si ligne associée à un code produit
                    if ($lignesdelivery[$i]->fk_product)
                    {
                        $prodser = new Product($this->db);

                        $prodser->fetch($lignesdelivery[$i]->fk_product);
                        $referenceproduitservice = $prodser->ref;

                        // Ajoute description du produit
                        if ($conf->global->FORM_ADD_PROD_DESC && !$conf->global->PRODUIT_CHANGE_PROD_DESC)
                        {
                            if ($lignesdelivery[$i]->product_desc&&$lignesdelivery[$i]->product_desc!=$lignesdelivery[$i]->libelle&&$lignesdelivery[$i]->product_desc!=$lignesdelivery[$i]->desc)
                            {
                                if ($libelleproduitservice) $libelleproduitservice.="\n";
                                $libelleproduitservice.=$lignesdelivery[$i]->product_desc;
                            }
                        }                    
                    }
                    if ($lignesdelivery[$i]->date_start && $lignesdelivery[$i]->date_end)
                    {
                        // Affichage durée si il y en a une
                        $libelleproduitservice.="\n(".$langs->transnoentities("From")." ".dolibarr_print_date($lignesdelivery[$i]->date_start)." ".$langs->transnoentities("to")." ".dolibarr_print_date($lignesdelivery[$i]->date_end).")";
                    }

                    $pdf->SetFont('Arial','', 9);   // Dans boucle pour gérer multi-page

                    $pdf->SetXY ($this->posxref-1, $curY);
                    $pdf->MultiCell(20, 4, $referenceproduitservice, 0, 'J');

                    $pdf->SetXY ($this->posxdesc-1, $curY);
                    $pdf->MultiCell(90, 4, $libelleproduitservice, 0, 'J');

                    $nexY = $pdf->GetY();
/*
                    // TVA
                    $pdf->SetXY ($this->posxtva, $curY);
                    $pdf->MultiCell(10, 4, ($delivery->lignes[$i]->tva_tx < 0 ? '*':'').abs($delivery->lignes[$i]->tva_tx), 0, 'R');

                    // Prix unitaire HT avant remise
                    $pdf->SetXY ($this->posxup, $curY);
                    $pdf->MultiCell(18, 4, price($delivery->lignes[$i]->subprice), 0, 'R', 0);
*/
                    // Quantité
                    $pdf->SetXY ($this->posxqty, $curY);
                    $pdf->MultiCell(10, 4, $lignesdelivery[$i]->qty_livre, 0, 'R');
/*
                    // Remise sur ligne
                    $pdf->SetXY ($this->posxdiscount, $curY);
                    if ($delivery->lignes[$i]->remise_percent)
                    {
                        $pdf->MultiCell(14, 4, $delivery->lignes[$i]->remise_percent."%", 0, 'R');
                    }

                    // Total HT ligne
                    $pdf->SetXY ($this->postotalht, $curY);
                    $total = price($delivery->lignes[$i]->price * $delivery->lignes[$i]->qty);
                    $pdf->MultiCell(23, 4, $total, 0, 'R', 0);

                    // Collecte des totaux par valeur de tva
                    // dans le tableau tva["taux"]=total_tva
					$tvaligne=$delivery->lignes[$i]->price * $delivery->lignes[$i]->qty;
					if ($delivery->remise_percent) $tvaligne-=($tvaligne*$delivery->remise_percent)/100;
					$this->tva[ (string)$delivery->lignes[$i]->tva_tx ] += $tvaligne;
*/
                    $nexY+=2;    // Passe espace entre les lignes

                    if ($nexY > 230 && $i < ($nblignes - 1))
                    {
                        //$this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY);
                        if ($pagenb == 1)
                        {
                          $this->_tableau($pdf, $tab_top, $tab_height, $nexY, $linediscount, $com);
                        }
                        else
                        {
                        	$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $linediscount, $com);
                        }
						            $this->_pagefoot($pdf);
                        
                        // Nouvelle page
                        $pdf->AddPage();
                        $pagenb++;
                        $this->_pagehead($pdf, $delivery, 0);

						            $nexY = $tab_top_newpage + 8;
                        $pdf->SetTextColor(0,0,0);
                        $pdf->SetFont('Arial','', 10);
                    }

                }

                
                // Affiche cadre tableau
                
                $bottomlasttab='';
                
                if ($pagenb == 1)
                {
                    $this->_tableau($pdf, $tab_top, $tab_height_one, $nexY, $linediscount, $com);
                    $bottomlasttab=$tab_top + $tab_height_one + 1;
                }
                else 
                {
                    $this->_tableau($pdf, $tab_top_newpage, $tab_height, $nexY, $linediscount, $com);
                    $bottomlasttab=$tab_top_newpage + $tab_height + 1;
                }
                /*
				$deja_regle = "";

                $posy=$this->_tableau_tot($pdf, $delivery, $deja_regle, $bottomlasttab);
 
                if ($deja_regle) {            
                    $this->_tableau_versements($pdf, $fac, $posy);
                }
*/
                /*
                * Mode de règlement
                */
                /*
                if ((! defined("FACTURE_CHQ_NUMBER") || ! FACTURE_CHQ_NUMBER) && (! defined("FACTURE_RIB_NUMBER") || ! FACTURE_RIB_NUMBER))
                {
                    $pdf->SetXY ($this->marge_gauche, 228);
                    $pdf->SetTextColor(200,0,0);
                    $pdf->SetFont('Arial','B',8);
                    $pdf->MultiCell(90, 3, $langs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
                    $pdf->MultiCell(90, 3, $langs->transnoentities("ErrorCreateBankAccount"),0,'L',0);
                    $pdf->SetTextColor(0,0,0);
                }
*/
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

                        $pdf->SetXY ($this->marge_gauche, 227);
                        $pdf->SetFont('Arial','B',8);
                        $pdf->MultiCell(90, 3, "Règlement par chèque à l'ordre de ".$account->proprio." envoyé à:",0,'L',0);
                        $pdf->SetXY ($this->marge_gauche, 231);
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

                        $this->marges['g']=$this->marge_gauche;
                        
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
                $pdf->SetXY($this->marge_gauche, 217);
                $titre = "Conditions de réglement:";
                $pdf->MultiCell(80, 5, $titre, 0, 'L');
                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(54, 217);
                $pdf->MultiCell(80, 5, $prop->cond_reglement_facture,0,'L');
                */

                /*
                 * Pied de page
                 */
                $this->_pagefoot($pdf);
                $pdf->AliasNbPages();
                
                $pdf->Close();

                $pdf->Output($file);

                return 1;   // Pas d'erreur
            }
            else
            {
                $this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
                return 0;
            }
        }
        else
        {
            $this->error=$langs->transnoentities("ErrorConstantNotDefined","PROP_OUTPUTDIR");
            return 0;
        }
        $this->error=$langs->transnoentities("ErrorUnknown");
        return 0;   // Erreur par defaut
    }

    /*
     *   \brief      Affiche le total à payer
     *   \param      pdf         	Objet PDF
     *   \param      prop         	Objet propale
     *   \param      deja_regle  	Montant deja regle
     *   \return     y              Position pour suite
    */
    function _tableau_tot(&$pdf, $delivery, $deja_regle, $posy)
    {
        global $langs;
        $langs->load("main");
        $langs->load("bills");

        $tab2_top = $posy;
        $tab2_hl = 5;
        $tab2_height = $tab2_hl * 4;
        $pdf->SetFont('Arial','', 9);

        // Affiche la mention TVA non applicable selon option
        $pdf->SetXY ($this->marge_gauche, $tab2_top + 0);
    	if ($this->franchise==1)
      	{
            $pdf->MultiCell(100, $tab2_hl, "* TVA non applicable art-293B du CGI", 0, 'L', 0);
        }

        // Tableau total
        $lltot = 200; $col1x = 120; $col2x = 182; $largcol2 = $lltot - $col2x;

        // Total HT
        $pdf->SetFillColor(256,256,256);
        $pdf->SetXY ($col1x, $tab2_top + 0);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("TotalHT"), 0, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + 0);
        $pdf->MultiCell($largcol2, $tab2_hl, price($delivery->total_ht +$delivery->remise), 0, 'R', 1);

        // Remise globale
        if ($delivery->remise > 0)
        {
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("GlobalDiscount"), 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl);
            $pdf->MultiCell($largcol2, $tab2_hl, "-".$delivery->remise_percent."%", 0, 'R', 1);

            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total HT après remise", 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell($largcol2, $tab2_hl, price($delivery->total_ht), 0, 'R', 0);

            $index = 2;
        }
        else
        {
            $index = 0;
        }

        // Affichage des totaux de TVA par taux (conformément à réglementation)
        $pdf->SetFillColor(248,248,248);
        foreach( $this->tva as $tvakey => $tvaval )
        {
            if ($tvakey)    // On affiche pas taux 0
            {
                $this->atleastoneratenotnull++;
                
                $index++;
            	$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
                $tvacompl = ( (float)$tvakey < 0 ) ? " (".$langs->transnoentities("NonPercuRecuperable").")" : '' ; 
                $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("TotalVAT").' '.abs($tvakey).'%'.$tvacompl, 0, 'L', 1);
    
                $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($largcol2, $tab2_hl, price($tvaval * abs((float)$tvakey) / 100 ), 0, 'R', 1);
            }
        }
        if (! $this->atleastoneratenotnull)
        {
            $index++;
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("TotalVAT"), 0, 'L', 1);
    
            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($delivery->total_tva), 0, 'R', 1);
        }
        
        $useborder=0;
        
        $index++;
        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFillColor(224,224,224);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("TotalTTC"), $useborder, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($delivery->total_ttc), $useborder, 'R', 1);
        $pdf->SetFont('Arial','', 9);
        $pdf->SetTextColor(0,0,0);

        if ($deja_regle > 0)
        {
            $index++;
            
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("AlreadyPayed"), 0, 'L', 0);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle), 0, 'R', 0);

            $index++;
            $pdf->SetTextColor(0,0,60);
            //$pdf->SetFont('Arial','B', 9);
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($delivery->total_ttc - $deja_regle), $useborder, 'R', 1);
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
        global $langs,$conf;
        $langs->load("main");
        $langs->load("bills");
        
        // Montants exprimés en     (en tab_top - 1)
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        //$titre = $langs->transnoentities("AmountInCurrency",$langs->transnoentities("Currency".$conf->monnaie));
        //$pdf->Text($this->page_largeur - $this->marge_droite - $pdf->GetStringWidth($titre), $tab_top-1, $titre);

        $pdf->SetDrawColor(128,128,128);

        // Rect prend une longueur en 3eme param
        $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
        // line prend une position y en 3eme param
        $pdf->line($this->marge_gauche, $tab_top+6, $this->page_largeur-$this->marge_droite, $tab_top+6);

        $pdf->SetFont('Arial','',10);
        
        $pdf->SetXY ($this->posxref-1, $tab_top);
        $pdf->MultiCell(22,6, $langs->transnoentities("Ref"),1,'L',1);

        $pdf->line($this->posxdesc-1, $tab_top, $this->posxdesc-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxdesc-1, $tab_top);
        $pdf->MultiCell(171,6, $langs->transnoentities("Designation"), 1, 'L', 1);
/*
        $pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxtva-1, $tab_top+2);
        $pdf->MultiCell(12,2, $langs->transnoentities("VAT"),'','C');

        $pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxup-1, $tab_top+2);
        $pdf->MultiCell(18,2, $langs->transnoentities("PriceUHT"),'','C');
*/
        $pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxqty-1, $tab_top+2);
        $pdf->MultiCell(11,2, $langs->transnoentities("Qty"),'','C');
/*
        $pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
        if ($this->atleastonediscount)
        {
            $pdf->SetXY ($this->posxdiscount-1, $tab_top+2);
            $pdf->MultiCell(16,2, $langs->transnoentities("ReductionShort"),'','C');
        }

        if ($this->atleastonediscount)
        {
            $pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
        }
        $pdf->SetXY ($this->postotalht-1, $tab_top+2);
        $pdf->MultiCell(23,2, $langs->transnoentities("TotalHT"),'','C');
*/
    }

    /*
     *   	\brief      Affiche en-tête propale
     *   	\param      pdf     objet PDF
     *   	\param      fac     objet propale
     *      \param      showadress      0=non, 1=oui
     */
    function _pagehead(&$pdf, $delivery, $showadress=1)
    {
        global $langs,$conf,$mysoc;

        $langs->load("main");
        $langs->load("bills");
        $langs->load("orders");
        $langs->load("companies");
        $langs->load("commercial");
        $langs->load("deliveries");
        
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Arial','B',13);

        $posy=$this->marge_haute;
        
        $pdf->SetXY($this->marge_gauche,$posy);

		// Logo
        $logo=$conf->societe->dir_logos.'/'.$mysoc->logo;
        if ($logo)
        {
            if (is_readable($logo))
            {
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, 12);
            }
            else
            {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('Arial','B',8);
                $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
            }
        }
        else if(defined("MAIN_INFO_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM) 
        {
            $pdf->MultiCell(100, 4, MAIN_INFO_SOCIETE_NOM, 0, 'L');
        }

        $pdf->SetFont('Arial','B',14);
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 4, $langs->transnoentities("DeliveryOrder")." ".$delivery->ref, '' , 'R');
        $pdf->SetFont('Arial','',12);
        
        $posy+=6;
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 4, $langs->transnoentities("Date")." : " . dolibarr_print_date($delivery->date_valid,"%d %B %Y"), '', 'R');

        if ($showadress)
        {
        // Emetteur
        
        $posy=42;
        $hautcadre=40;
        /*
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY($this->marge_gauche,$posy-5);
        $pdf->MultiCell(66,5, $langs->transnoentities("BillFrom").":");


        $pdf->SetXY($this->marge_gauche,$posy);
        $pdf->SetFillColor(230,230,230);
        $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);


        $pdf->SetXY($this->marge_gauche+2,$posy+3);

        // Nom emetteur
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Arial','B',11);
        if (defined("FAC_PDF_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM) $pdf->MultiCell(80, 4, FAC_PDF_SOCIETE_NOM, 0, 'L');
        else $pdf->MultiCell(80, 4, $mysoc->nom, 0, 'L');

        // Caractéristiques emetteur
        $carac_emetteur = '';
        if (defined("FAC_PDF_ADRESSE") && FAC_PDF_ADRESSE) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).FAC_PDF_ADRESSE;
        else {
            $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$mysoc->adresse;
            $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$mysoc->cp.' '.$mysoc->ville;
        }
        $carac_emetteur .= "\n";
        // Tel
        if (defined("FAC_PDF_TEL") && FAC_PDF_TEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Phone").": ".FAC_PDF_TEL;
        elseif ($mysoc->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Phone").": ".$mysoc->tel;
        // Fax
        if (defined("FAC_PDF_FAX") && FAC_PDF_FAX) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Fax").": ".FAC_PDF_FAX;
        elseif ($mysoc->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Fax").": ".$mysoc->fax;
        // EMail
		if (defined("FAC_PDF_MEL") && FAC_PDF_MEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Email").": ".FAC_PDF_MEL;
        elseif ($mysoc->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Email").": ".$mysoc->email;
        // Web
		if (defined("FAC_PDF_WWW") && FAC_PDF_WWW) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Web").": ".FAC_PDF_WWW;
        elseif ($mysoc->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->transnoentities("Web").": ".$mysoc->url;

        $pdf->SetFont('Arial','',9);
        $pdf->SetXY($this->marge_gauche+2,$posy+8);
        $pdf->MultiCell(80,4, $carac_emetteur);
*/
        // Client destinataire
        $posy=32;
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(102,$posy-5);
        $pdf->MultiCell(80,5, $langs->transnoentities("DeliveryAddress").":");
		//
		  $client = new Societe($this->db);
     	$client->fetch($delivery->socid);
		  $delivery->client = $client;
		  $idadresse = $delivery->adresse_livraison_id;
		  $delivery->fetch_adresse_livraison($idadresse);
		//
		
		// Caractéristiques adresse livraison
        
        if ($delivery->deliveryaddress->nom) $adresse_livraison_nom = $delivery->deliveryaddress->nom;
        elseif ($delivery->client->nom) $adresse_livraison_nom = $delivery->client->nom;
        
        $carac_emetteur = '';
        if ($delivery->deliveryaddress->address) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$delivery->deliveryaddress->address;
        elseif ($delivery->client->adresse) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$delivery->client->adresse;
        
        if ($delivery->deliveryaddress->cp) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$delivery->deliveryaddress->cp;
        elseif ($delivery->client->cp) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$delivery->client->cp;
        
        if ($delivery->deliveryaddress->ville) $carac_emetteur .= " ".$delivery->deliveryaddress->ville;
        elseif ($delivery->client->ville) $carac_emetteur .= " ".$delivery->client->ville;
        
        if ($delivery->deliveryaddress->pays) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$delivery->deliveryaddress->pays;
        elseif ($delivery->client->pays) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$delivery->client->pays;
        
        //$carac_emetteur .= "\n";
        $carac_emetteur2 = '';
	      if ($delivery->client->tva_intra) $carac_emetteur2 .= ($carac_emetteur2 ? "\n" : '' ).$langs->transnoentities("VATIntraShort").': '.$delivery->client->tva_intra;
        if ($delivery->contact->firstname || $delivery->contact->name) $carac_emetteur2 .= ($carac_emetteur2 ? "\n" : '' ).$langs->transnoentities("Contact").": ".$delivery->contact->firstname." ".$delivery->contact->name;
        //if ($delivery->contact->name) $carac_emetteur2 .= " ".$delivery->contact->name;
        
        if ($delivery->contact->phone_pro) $carac_emetteur2 .= ($carac_emetteur2 ? "\n" : '' ).$langs->transnoentities("Phone").": ".$delivery->contact->phone_pro;
        elseif ($delivery->contact->phone_mobile) $carac_emetteur2 .= ($carac_emetteur2 ? "\n" : '' ).$langs->transnoentities("Phone").": ".$delivery->contact->phone_mobile;
        elseif ($delivery->client->tel) $carac_emetteur2 .= ($carac_emetteur2 ? "\n" : '' ).$langs->transnoentities("Phone").": ".$delivery->client->tel;
        
        if ($delivery->contact->fax) $carac_emetteur2 .= ($carac_emetteur2 ? "\n" : '' ).$langs->transnoentities("Fax").": ".$delivery->contact->fax;
        elseif ($delivery->client->fax) $carac_emetteur2 .= ($carac_emetteur2 ? "\n" : '' ).$langs->transnoentities("Fax").": ".$delivery->client->fax;
        
        if ($delivery->contact->email) $carac_emetteur2 .= ($carac_emetteur2 ? "\n" : '' ).$langs->transnoentities("Email").": ".$delivery->contact->email; 
		
        // Cadre client destinataire
        $pdf->rect(100, $posy, 100, $hautcadre);

		// Nom client
        $pdf->SetXY(102,$posy+3);
        $pdf->SetFont('Arial','B',11);
        $pdf->MultiCell(106,4, $adresse_livraison_nom, 0, 'L');

		// Caractéristiques client

        $pdf->SetFont('Arial','',9);
        $pdf->SetXY(102,$posy+8);
        $pdf->MultiCell(86,4, $carac_emetteur);
        
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(102,$posy+24);
        $pdf->MultiCell(86,3, $carac_emetteur2);
        
        $pdf->SetDrawColor(128,128,128);
        $pdf->SetFillColor(230,230,230);
        
       // Cadre date de livraison
        $pdf->Rect($this->marge_gauche, 77, 60, 12);
        $pdf->line($this->marge_gauche, 83, 70, 83);
        $pdf->SetFont('Arial','B',9);
        $pdf->SetXY($this->marge_gauche,77);
        $pdf->SetTextColor(0,0,0);
        $pdf->MultiCell($this->marge_gauche+50, 6, $langs->transnoentities("DeliveryDate"), 1, 'C', 1);
        
        if ($delivery->date_valid)
        {
        	$pdf->SetFont('Arial','',9);
        	$pdf->SetXY($this->marge_gauche,85);
          $pdf->SetTextColor(0,0,0);
          $pdf->MultiCell($this->marge_gauche+50, 2, dolibarr_print_date($delivery->date_valid,'%A %d %B %Y'), '', 'C');
        }
  
        // Cadre interlocuteur commerciale
        $pdf->Rect($this->marge_gauche+65, 77, 60, 12);
        $pdf->line($this->marge_gauche+65, 83, 135, 83);
        $pdf->SetFont('Arial','B',9);
        $pdf->SetXY($this->marge_gauche+65,77);
        $pdf->SetTextColor(0,0,0);
        $pdf->MultiCell($this->marge_gauche+50, 6, $langs->transnoentities("CommercialInterlocutor"), 1, 'C', 1);
        
        if ($delivery->user_author_id)
        {
        	$pdf->SetFont('Arial','',9);
        	$pdf->SetXY($this->marge_gauche+65,85);
          $pdf->SetTextColor(0,0,0);
          $pdf->MultiCell($this->marge_gauche+50, 2, $delivery->user_author_firstname.' '.$delivery->user_author_name, '', 'C');
        }
       
        // Code client
        $pdf->Rect($this->marge_gauche+130, 77, 60, 12);
        $pdf->line($this->marge_gauche+130, 83, 200, 83);
        $pdf->SetFont('Arial','B',9);
        $pdf->SetXY($this->marge_gauche+130,77);
        $pdf->SetTextColor(0,0,0);
        $pdf->MultiCell($this->marge_gauche+50, 6, $langs->transnoentities("CustomerCode"), 1, 'C', 1);
        
        if ($delivery->client->code_client)
        {
        	$pdf->SetFont('Arial','',9);
        	$pdf->SetXY($this->marge_gauche+130,85);
          $pdf->SetTextColor(0,0,0);
          $pdf->MultiCell($this->marge_gauche+50, 2, $delivery->client->code_client, '', 'C');
        }
        
        
        $com = new Commande($this->db);
				$com->fetch($delivery->commande_id);
				
        // Référence client et commande client
        if ($com->ref_client)
        {
        	$pdf->SetFont('Arial','B',9);
        	$pdf->SetXY($this->marge_gauche,92);
        	$pdf->SetTextColor(0,0,0);
        	$pdf->MultiCell($this->marge_gauche+70, 2, $langs->transnoentities("RefCustomer")." : ".$com->ref_client, '', 'L');
        }
        
        // Référence commande client
        if ($delivery->commande_id)
        {
        	$pdf->SetFont('Arial','B',9);
        	$pdf->SetXY($this->marge_gauche,96);
        	$pdf->SetTextColor(0,0,0);
				  $pdf->MultiCell($this->marge_gauche+50, 2, $langs->transnoentities("Source")." : ".$langs->transnoentities("Order")." ".$com->ref, '', 'L');
        }
        
        }

    }

    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */
    function _pagefoot(&$pdf)
    {
        global $langs, $conf,$mysoc;
        $langs->load("main");
        $langs->load("bills");
        $langs->load("companies");
        
        $html=new Form($this->db);
        
        // Premiere ligne d'info réglementaires
        $ligne1="";
        if ($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE)
        {
            $ligne1.=($ligne1?" - ":"").$html->forme_juridique_name($conf->global->MAIN_INFO_SOCIETE_FORME_JURIDIQUE);
        }
        if ($conf->global->MAIN_INFO_CAPITAL)
        {
            $ligne1.=($ligne1?" - ":"").$langs->transnoentities("CapitalOf",$conf->global->MAIN_INFO_CAPITAL)." ".$langs->transnoentities("Currency".$conf->monnaie);
        }
        if ($conf->global->MAIN_INFO_SIRET)
        {
            $ligne1.=($ligne1?" - ":"").$langs->transcountry("ProfId2",$this->emetteur->code_pays).": ".$conf->global->MAIN_INFO_SIRET;
        }
        if ($conf->global->MAIN_INFO_SIREN && (! $conf->global->MAIN_INFO_SIRET || $this->emetteur->code_pays != 'FR'))
        {
            $ligne1.=($ligne1?" - ":"").$langs->transcountry("ProfId1",$this->emetteur->code_pays).": ".$conf->global->MAIN_INFO_SIREN;
        }

        // Deuxieme ligne d'info réglementaires
        $ligne2="";
        if ($conf->global->MAIN_INFO_APE)
        {
            $ligne2.=($ligne2?" - ":"").$langs->transcountry("ProfId3",$this->emetteur->code_pays).": ".MAIN_INFO_APE;
        }
        if ($conf->global->MAIN_INFO_RCS)
        {
            $ligne2.=($ligne2?" - ":"").$langs->transcountry("ProfId4",$this->emetteur->code_pays).": ".$conf->global->MAIN_INFO_RCS;
        }
        if ($conf->global->MAIN_INFO_TVAINTRA != '')
        {
            $ligne2.=($ligne2?" - ":"").$langs->transnoentities("VATIntraShort").": ".$conf->global->MAIN_INFO_TVAINTRA;
        }
        

        // Caractéristiques emetteur
        $carac_emetteur = '';
        $carac_emetteur .= ($carac_emetteur ? " - " : '' ).$langs->transnoentities("RegisteredOffice").": ".$mysoc->nom;
        $carac_emetteur .= ($carac_emetteur ? " - " : '' ).$mysoc->adresse;
        $carac_emetteur .= ($carac_emetteur ? " - " : '' ).$mysoc->cp;
        $carac_emetteur .= " ".$mysoc->ville;
        
        // Caractéristique suite
        // Tel
        //$carac_emetteur2 = '';
        $carac_emetteur .= ($carac_emetteur ? " - " : '' ).$langs->transnoentities("Phone").": ".$mysoc->tel;
        // Fax
        $carac_emetteur .= ($carac_emetteur ? " - " : '' ).$langs->transnoentities("Fax").": ".$mysoc->fax;
        // EMail
        //$carac_emetteur3 = '';
        //$carac_emetteur .= ($carac_emetteur ? " - " : '' ).$mysoc->email;
        // Web
        $carac_emetteur4 = '';
        $carac_emetteur4 .= ($carac_emetteur4 ? " - " : '' ).$mysoc->url;
        $carac_emetteur4 .= ($carac_emetteur4 ? " - " : '' ).$mysoc->email;
        
        // Caractéristiques Paris
        $carac_paris = '';
        $carac_paris .= ($carac_paris ? " - " : '' ).$conf->global->PDF_SHOWROOM_PARIS;
        $carac_paris .= ($carac_paris ? " - " : '' ).$conf->global->PDF_ADRESSE_PARIS;
        $carac_paris .= ($carac_paris ? " - " : '' ).$conf->global->PDF_VILLE_PARIS;
        // TEL + FAX
        $carac_paris2 = '';
        $carac_paris2 .= ($carac_paris2 ? " - " : '' ).$langs->transnoentities("Phone").": ".$conf->global->PDF_TEL_PARIS;
        $carac_paris2 .= ($carac_paris2 ? " - " : '' ).$langs->transnoentities("Fax").": ".$conf->global->PDF_FAX_PARIS;
        // EMail
        $carac_paris2 .= ($carac_paris2 ? " - " : '' ).$conf->global->PDF_EMAIL_PARIS;
        
        $carac_paris .= ' '.$carac_paris2;
        


        // On positionne le debut du bas de page selon nbre de lignes de ce bas de page
        $posy=$this->marge_basse + 1 + ($ligne1?3:0) + ($ligne2?3:0);
        $posy2=$this->marge_basse + 10 + ($carac_emetteur?3:0);
        $posy3=$this->marge_basse + 13 + ($carac_paris?3:0);
        $posy4=$this->marge_basse + 7 + ($carac_emetteur4?3:0);

        $pdf->SetY(-$posy3);
        $pdf->SetFont('Times','B',8);
        
        if ($carac_paris)
        {
          $pdf->SetXY($this->marge_gauche,-$posy3);
          $pdf->MultiCell(192,2, $carac_paris, 0, 'C', 0);
        }
/*        
        if ($carac_paris2)
        {
        	$posy3-=3;
          $pdf->SetXY($this->marge_gauche,-$posy3);
          $pdf->MultiCell(80,2, $carac_paris2, 0, 'C', 0);
        }
        
        if ($carac_paris3)
        {
        	$posy3-=3;
          $pdf->SetXY($this->marge_gauche,-$posy3);
          $pdf->MultiCell(80,2, $carac_paris3, 0, 'C', 0);
        }
*/        
        $pdf->SetY(-$posy2);
        $pdf->SetFont('Times','B',8);
        
        if ($carac_emetteur)
        {
          $pdf->SetXY($this->marge_gauche,-$posy2);
          $pdf->MultiCell(192,2, $carac_emetteur, 0, 'C', 0);
        }
/*        
        if ($carac_emetteur2)
        {
        	$posy2-=3;
          $pdf->SetXY($this->marge_gauche,-$posy2);
          $pdf->MultiCell(270,2, $carac_emetteur2, 0, 'C', 0);
        }
        
        if ($carac_emetteur3)
        {
        	$posy2-=3;
          $pdf->SetXY($this->marge_gauche,-$posy2);
          $pdf->MultiCell(270,2, $carac_emetteur3, 0, 'C', 0);
        }
*/        
        $pdf->SetY(-$posy4);
        $pdf->SetFont('Times','B',8);
        
        if ($carac_emetteur4)
        {
          $pdf->SetXY($this->marge_gauche,-$posy4);
          $pdf->MultiCell(200,2, $carac_emetteur4, 0, 'C', 0);
        }

        $pdf->SetY(-$posy);
        $pdf->SetFont('Times','',6);
        $pdf->SetDrawColor(224,224,224);
        $pdf->line($this->marge_gauche, $this->page_hauteur-$posy, 200, $this->page_hauteur-$posy);
        $posy--;
        
        if ($ligne1)
        {
            $pdf->SetXY($this->marge_gauche,-$posy);
            $pdf->MultiCell(200, 2, $ligne1, 0, 'C', 0);
        }

        if ($ligne2)
        {
            $posy-=3;
            $pdf->SetXY($this->marge_gauche,-$posy);
            $pdf->MultiCell(200, 2, $ligne2, 0, 'C', 0);
        }
        
        $pdf->SetFont('Times','',8);
        $pdf->SetXY(-20,-$posy);
        $pdf->MultiCell(10, 2, $pdf->PageNo().'/{nb}', 0, 'R', 0);
    }

}

?>
