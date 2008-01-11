<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
       	\file       htdocs/fourn/commande/modules/pdf_muscadet.modules.php
		\ingroup    fournisseur
		\brief      Fichier de la classe permettant de générer les commandes fournisseurs au modèle Muscadet
		\author	    Regis Houssin
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT."/fourn/commande/modules/modules_commandefournisseur.php");
require_once(DOL_DOCUMENT_ROOT."/fourn/fournisseur.commande.class.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
	    \class      pdf_muscadet
		\brief      Classe permettant de générer les commandes fournisseurs au modèle Muscadet
*/

class pdf_muscadet extends ModelePDFSuppliersOrders
{
    
    /**
			\brief      Constructeur
    		\param	    db		Handler accès base de donnée
    */
    function pdf_muscadet($db)
    {
        global $conf,$langs,$mysoc;

		    $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
        $this->name = "muscadet";
        $this->description = "Modèle de commandes fournisseur complet (logo...)";

        // Dimension page pour format A4
        $this->type = 'pdf';
        $this->page_largeur = 210;
        $this->page_hauteur = 297;
        $this->format = array($this->page_largeur,$this->page_hauteur);
        $this->marge_gauche=10;
        $this->marge_droite=10;
        $this->marge_haute=10;
        $this->marge_basse=10;
 
        $this->option_logo = 1;                    // Affiche logo
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Affiche mode règlement
        $this->option_condreg = 1;                 // Affiche conditions règlement
        $this->option_codeproduitservice = 1;      // Affiche code produit-service
        $this->option_multilang = 1;               // Dispo en plusieurs langues

    	if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise') 
      		$this->franchise=1;

        // Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'était pas défini

        // Defini position des colonnes
        $this->posxdesc=$this->marge_gauche+1;
        $this->posxtva=121;
        $this->posxup=132;
        $this->posxqty=151;
        $this->posxdiscount=162;
        $this->postotalht=177;
       
        $this->tva=array();
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
    */
    function write_file($com,$outputlangs='')
    {
        global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
        $outputlangs->load("main");
        $outputlangs->load("companies");
        $outputlangs->load("bills");
        $outputlangs->load("products");
        $outputlangs->load("orders");
        $outputlangs->load("dict");

		$outputlangs->setPhpLang();

        if ($conf->fournisseur->commande->dir_output)
        {
			// Définition de l'objet $com (pour compatibilite ascendante)
	    	if (! is_object($com))
	    	{
	            $id = $com;
	            $com = new CommandeFournisseur($this->db);
	            $ret=$com->fetch($id);
			}
            $deja_regle = "";

			// Définition de $dir et $file
			if ($com->specimen)
			{
				$dir = $conf->fournisseur->commande->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$comref = sanitize_string($com->ref);
				$dir = $conf->fournisseur->commande->dir_output . "/" . $comref;
				$file = $dir . "/" . $comref . ".pdf";
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
				$nblignes = sizeof($com->lignes);

                		
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

                $pdf->SetDrawColor(128,128,128);

                $pdf->SetTitle($com->ref);
                $pdf->SetSubject($outputlangs->transnoentities("Order"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1,0);

                // Positionne $this->atleastonediscount si on a au moins une remise
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    if ($com->lignes[$i]->remise_percent)
                    {
                        $this->atleastonediscount++;
                    }
                }

                $this->_pagehead($pdf, $com, 1, $outputlangs);

                $pagenb = 1;
                $tab_top = 90;
                $tab_top_newpage = 50;
                $tab_height = 110;
                $tab_height_newpage = 180;
                
                // Affiche notes
                if ($com->note_public)
                {
	                $tab_top = 88;

	                $pdf->SetFont('Arial','', 9);   // Dans boucle pour gérer multi-page
	                $pdf->SetXY ($this->posxdesc-1, $tab_top);
	                $pdf->MultiCell(190, 3, $com->note_public, 0, 'J');
	                $nexY = $pdf->GetY();
	                $height_note=$nexY-$tab_top;
	                
	                // Rect prend une longueur en 3eme param
	                $pdf->SetDrawColor(192,192,192);
	                $pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

	                $tab_height = $tab_height - $height_note;
                	$tab_top = $nexY+6;
               	}
               	else
               	{
               		$height_note=0;
               	}

                $iniY = $tab_top + 8;
                $curY = $tab_top + 8;
                $nexY = $tab_top + 8;

                // Boucle sur les lignes
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    $curY = $nexY;

                    // Description de la ligne produit
                    $libelleproduitservice=dol_htmlentities($com->lignes[$i]->libelle);
                    if ($com->lignes[$i]->desc&&$com->lignes[$i]->desc!=$com->lignes[$i]->libelle)
                    {
                        if ($libelleproduitservice) $libelleproduitservice.="\n";
                        $libelleproduitservice.=dol_htmlentities($com->lignes[$i]->desc);
                    }
                    // Si ligne associée à un code produit
                    if ($com->lignes[$i]->fk_product)
                    {
                    	$libelleproduitservice=$outputlangs->transnoentities("Product")." ".$com->lignes[$i]->ref_fourn." - ".$libelleproduitservice;                    
                    }
                    if ($com->lignes[$i]->date_start && $com->lignes[$i]->date_end)
                    {
                        // Affichage durée si il y en a une
                        $libelleproduitservice.=dol_htmlentities("\n(".$outputlangs->transnoentities("From")." ".dolibarr_print_date($com->lignes[$i]->date_start)." ".$outputlangs->transnoentities("to")." ".dolibarr_print_date($com->lignes[$i]->date_end).")");
                    }

                    $pdf->SetFont('Arial','', 9);   // Dans boucle pour gérer multi-page

                    $pdf->writeHTMLCell(108, 4, $this->posxdesc-1, $curY, $libelleproduitservice, 0, 1);
                    
                    $pdf->SetFont('Arial','', 9);   // On repositionne la police par défaut

                    $nexY = $pdf->GetY();

                    // TVA
                    $pdf->SetXY ($this->posxtva, $curY);
                    $pdf->MultiCell(10, 4, ($com->lignes[$i]->tva_tx < 0 ? '*':'').abs($com->lignes[$i]->tva_tx), 0, 'R');

                    // Prix unitaire HT avant remise
                    $pdf->SetXY ($this->posxup, $curY);
                    $pdf->MultiCell(18, 4, price($com->lignes[$i]->subprice), 0, 'R', 0);

                    // Quantité
                    $pdf->SetXY ($this->posxqty, $curY);
                    $pdf->MultiCell(10, 4, $com->lignes[$i]->qty, 0, 'R');

                    // Remise sur ligne
                    $pdf->SetXY ($this->posxdiscount, $curY);
                    if ($com->lignes[$i]->remise_percent)
                    {
                        $pdf->MultiCell(14, 4, $com->lignes[$i]->remise_percent."%", 0, 'R');
                    }

                    // Total HT ligne
                    $pdf->SetXY ($this->postotalht, $curY);
                    $total = price($com->lignes[$i]->price * $com->lignes[$i]->qty);
                    $pdf->MultiCell(23, 4, $total, 0, 'R', 0);

                    // Collecte des totaux par valeur de tva
                    // dans le tableau tva["taux"]=total_tva
                    $tvaligne=$com->lignes[$i]->price * $com->lignes[$i]->qty;
                    if ($com->remise_percent) $tvaligne-=($tvaligne*$com->remise_percent)/100;
                    $this->tva[ (string)$com->lignes[$i]->tva_tx ] += $tvaligne;

                    $nexY+=2;    // Passe espace entre les lignes

                    if ($nexY > 200 && $i < ($nblignes - 1))
                    {
                    	$this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY, $outputlangs);
                    	$this->_pagefoot($pdf, $outputlangs);
                    	
                    	// Nouvelle page
                    	$pdf->AddPage();
                    	$pagenb++;
                    	$this->_pagehead($pdf, $com, 0, $outputlangs);
                    	
                    	$nexY = $tab_top_newpage + 8;
                    	$pdf->SetTextColor(0,0,0);
                    	$pdf->SetFont('Arial','', 10);
                    }

                }
                // Affiche cadre tableau
                if ($pagenb == 1)
                {
                	$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
                    $bottomlasttab=$tab_top + $tab_height + 1;
                }
                else 
                {
                    $this->_tableau($pdf, $tab_top_newpage, $tab_height, $nexY, $outputlangs);
                    $bottomlasttab=$tab_top_newpage + $tab_height + 1;
                }
                
                $deja_regle = "";

                $posy=$this->_tableau_tot($pdf, $com, $deja_regle, $bottomlasttab, $outputlangs);
 
                if ($deja_regle) {            
                    $this->_tableau_versements($pdf, $fac, $posy);
                }

                /*
                * Mode de règlement
                */
                if ((! defined("FACTURE_CHQ_NUMBER") || ! FACTURE_CHQ_NUMBER) && (! defined("FACTURE_RIB_NUMBER") || ! FACTURE_RIB_NUMBER))
                {
                    $pdf->SetXY ($this->marge_gauche, 228);
                    $pdf->SetTextColor(200,0,0);
                    $pdf->SetFont('Arial','B',8);
                    $pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
                    $pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorCreateBankAccount"),0,'L',0);
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
                $this->_pagefoot($pdf, $outputlangs);
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
    function _tableau_tot(&$pdf, $com, $deja_regle, $posy, $outputlangs)
    {
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
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + 0);
        $pdf->MultiCell($largcol2, $tab2_hl, price($com->total_ht +$com->remise), 0, 'R', 1);

        // Remise globale
        if ($com->remise > 0)
        {
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("GlobalDiscount"), 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl);
            $pdf->MultiCell($largcol2, $tab2_hl, "-".$com->remise_percent."%", 0, 'R', 1);

            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total HT après remise", 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell($largcol2, $tab2_hl, price($com->total_ht), 0, 'R', 0);

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
                $tvacompl = ( (float)$tvakey < 0 ) ? " (".$outputlangs->transnoentities("NonPercuRecuperable").")" : '' ; 
                $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalVAT").' '.abs($tvakey).'%'.$tvacompl, 0, 'L', 1);
    
                $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($largcol2, $tab2_hl, price($tvaval * abs((float)$tvakey) / 100 ), 0, 'R', 1);
            }
        }
        if (! $this->atleastoneratenotnull)
        {
            $index++;
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalVAT"), 0, 'L', 1);
    
            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($com->total_tva), 0, 'R', 1);
        }
        
        $useborder=0;
        
        $index++;
        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFillColor(224,224,224);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($com->total_ttc), $useborder, 'R', 1);
        $pdf->SetFont('Arial','', 9);
        $pdf->SetTextColor(0,0,0);

        if ($deja_regle > 0)
        {
            $index++;
            
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPayed"), 0, 'L', 0);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle), 0, 'R', 0);

            $index++;
            $pdf->SetTextColor(0,0,60);
            //$pdf->SetFont('Arial','B', 9);
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($com->total_ttc - $deja_regle), $useborder, 'R', 1);
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
    function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
    {
        global $conf;
   
        // Montants exprimés en     (en tab_top - 1)
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentities("Currency".$conf->monnaie));
        $pdf->Text($this->page_largeur - $this->marge_droite - $pdf->GetStringWidth($titre), $tab_top-1, $titre);

        $pdf->SetDrawColor(128,128,128);

        // Rect prend une longueur en 3eme param
        $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
        // line prend une position y en 3eme param
        $pdf->line($this->marge_gauche, $tab_top+6, $this->page_largeur-$this->marge_droite, $tab_top+6);

        $pdf->SetFont('Arial','',10);

        $pdf->SetXY ($this->posxdesc-1, $tab_top+2);
        $pdf->MultiCell(108,2, $outputlangs->transnoentities("Designation"),'','L');

        $pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxtva-1, $tab_top+2);
        $pdf->MultiCell(12,2, $outputlangs->transnoentities("VAT"),'','C');

        $pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxup-1, $tab_top+2);
        $pdf->MultiCell(18,2, $outputlangs->transnoentities("PriceUHT"),'','C');

        $pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxqty-1, $tab_top+2);
        $pdf->MultiCell(11,2, $outputlangs->transnoentities("Qty"),'','C');

        $pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
        if ($this->atleastonediscount)
        {
            $pdf->SetXY ($this->posxdiscount-1, $tab_top+2);
            $pdf->MultiCell(16,2, $outputlangs->transnoentities("ReductionShort"),'','C');
        }

        if ($this->atleastonediscount)
        {
            $pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
        }
        $pdf->SetXY ($this->postotalht-1, $tab_top+2);
        $pdf->MultiCell(23,2, $outputlangs->transnoentities("TotalHT"),'','C');

    }

    /*
     *   	\brief      Affiche en-tête propale
     *   	\param      pdf     objet PDF
     *   	\param      fac     objet propale
     *      \param      showadress      0=non, 1=oui
     */
    function _pagehead(&$pdf, $com, $showadress=1, $outputlangs)
    {
        global $langs,$conf,$mysoc;

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("orders");
        $outputlangs->load("companies");
        
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Arial','B',13);

        $posy=$this->marge_haute;
        
        $pdf->SetXY($this->marge_gauche,$posy);

		// Logo
        $logo=$conf->societe->dir_logos.'/'.$mysoc->logo;
        if ($mysoc->logo)
        {
            if (is_readable($logo))
            {
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
            }
            else
            {
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('Arial','B',8);
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
            }
        }
        else if(defined("MAIN_INFO_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM) 
        {
            $pdf->MultiCell(100, 4, MAIN_INFO_SOCIETE_NOM, 0, 'L');
        }

        $pdf->SetFont('Arial','B',13);
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Order")." ".$com->ref, '' , 'R');
        $pdf->SetFont('Arial','',12);
        
        $posy+=6;
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : " . dolibarr_print_date($com->date,"day"), '', 'R');

        if ($showadress)
        {
        // Emetteur
        $posy=42;
        $hautcadre=40;
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY($this->marge_gauche,$posy-5);
        $pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":");


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
        if (defined("FAC_PDF_TEL") && FAC_PDF_TEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".FAC_PDF_TEL;
        elseif ($mysoc->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$mysoc->tel;
        // Fax
        if (defined("FAC_PDF_FAX") && FAC_PDF_FAX) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".FAC_PDF_FAX;
        elseif ($mysoc->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$mysoc->fax;
        // EMail
		if (defined("FAC_PDF_MEL") && FAC_PDF_MEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".FAC_PDF_MEL;
        elseif ($mysoc->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$mysoc->email;
        // Web
		if (defined("FAC_PDF_WWW") && FAC_PDF_WWW) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".FAC_PDF_WWW;
        elseif ($mysoc->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$mysoc->url;

        $pdf->SetFont('Arial','',9);
        $pdf->SetXY($this->marge_gauche+2,$posy+8);
        $pdf->MultiCell(80,4, $carac_emetteur);

        // Client destinataire
        $posy=42;
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(100,$posy-5);
        $pdf->MultiCell(80,5, $outputlangs->transnoentities("BillTo").":");
		//
		$client = new Societe($this->db);
     	$client->fetch($com->socid);
		$com->client = $client;
		// 
		
        // Cadre client destinataire
        $pdf->rect(100, $posy, 100, $hautcadre);

		// Nom client
        $pdf->SetXY(102,$posy+3);
        $pdf->SetFont('Arial','B',11);
        $pdf->MultiCell(106,4, $com->client->nom, 0, 'L');

		// Caractéristiques client
        $carac_client=$com->client->adresse;
        $carac_client.="\n".$com->client->cp . " " . $com->client->ville."\n";
		if ($com->client->tva_intra) $carac_client.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$com->client->tva_intra;
        $pdf->SetFont('Arial','',9);
        $pdf->SetXY(102,$posy+8);
        $pdf->MultiCell(86,4, $carac_client);
        }

    }

    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */
    function _pagefoot(&$pdf, $outputlangs)
    {
        global $conf;
        
        $outputlangs->load("dict");
        
        // Premiere ligne d'info réglementaires
        $ligne1="";
        if ($this->emetteur->forme_juridique_code)
        {
            $ligne1.=($ligne1?" - ":"").getFormeJuridiqueLabel($this->emetteur->forme_juridique_code);
        }
        if ($this->emetteur->capital)
        {
            $ligne1.=($ligne1?" - ":"").$outputlangs->transnoentities("CapitalOf",$this->emetteur->capital)." ".$outputlangs->transnoentities("Currency".$conf->monnaie);
        }
        if ($this->emetteur->profid2)
        {
            $ligne1.=($ligne1?" - ":"").$outputlangs->transcountry("ProfId2",$this->emetteur->pays_code).": ".$this->emetteur->profid2;
        }
        if ($this->emetteur->profid1 && (! $this->emetteur->profid2 || $this->emetteur->pays_code != 'FR'))
        {
            $ligne1.=($ligne1?" - ":"").$outputlangs->transcountry("ProfId1",$this->emetteur->pays_code).": ".$this->emetteur->profid1;
        }

        // Deuxieme ligne d'info réglementaires
        $ligne2="";
        if ($this->emetteur->profid3)
        {
            $ligne2.=($ligne2?" - ":"").$outputlangs->transcountry("ProfId3",$this->emetteur->pays_code).": ".$this->emetteur->profid3;
        }
        if ($this->emetteur->profid4)
        {
            $ligne2.=($ligne2?" - ":"").$outputlangs->transcountry("ProfId4",$this->emetteur->pays_code).": ".$this->emetteur->profid4;
        }
        if ($this->emetteur->tva_intra != '')
        {
            $ligne2.=($ligne2?" - ":"").$outputlangs->transnoentities("VATIntraShort").": ".$this->emetteur->tva_intra;
        }

        $pdf->SetFont('Arial','',8);
        $pdf->SetDrawColor(224,224,224);

        // On positionne le debut du bas de page selon nbre de lignes de ce bas de page
        $posy=$this->marge_basse + 1 + ($ligne1?3:0) + ($ligne2?3:0);

        $pdf->SetY(-$posy);
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
        
        $pdf->SetXY(-20,-$posy);
        $pdf->MultiCell(10, 2, $pdf->PageNo().'/{nb}', 0, 'R', 0);
    }

}

?>
