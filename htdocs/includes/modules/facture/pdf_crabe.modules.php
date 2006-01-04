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
       	\file       htdocs/includes/modules/facture/pdf_crabe.modules.php
		\ingroup    facture
		\brief      Fichier de la classe permettant de générer les factures au modèle Crabe
		\author	    Laurent Destailleur
		\version    $Revision$
*/

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");


/**
	    \class      pdf_crabe
		\brief      Classe permettant de générer les factures au modèle Crabe
*/

class pdf_crabe extends ModelePDFFactures
{
    
    /**
    		\brief  Constructeur
    		\param	db		Handler accès base de donnée
    */
    function pdf_crabe($db)
    {
        global $conf,$langs;
        
        $this->db = $db;
        $this->name = "crabe";
		$this->description = "Modèle de facture complet (Gère l'option fiscale de facturation TVA, le choix du mode de règlement à afficher, logo...)";

        // Dimension page pour format A4
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
        $this->posxdesc=$this->marge_gauche+1;
        $this->posxtva=121;
        $this->posxup=132;
        $this->posxqty=151;
        $this->posxdiscount=162;
        $this->postotalht=177;

        $this->atleastoneratenotnull=0;
        $this->atleastonediscount=0;
    }


    /**
    		\brief      Fonction générant la facture sur le disque
    		\param	    id		Id de la facture à générer
    		\return	    int     1=ok, 0=ko
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
    		\remarks    FACTURE_CHQ_NUMBER
    		\remarks    FACTURE_RIB_NUMBER
    */
    function write_pdf_file($id)
    {
        global $user,$langs,$conf;

        $langs->load("main");
        $langs->load("bills");
        $langs->load("products");

        if ($conf->facture->dir_output)
        {
            $fac = new Facture($this->db,"",$id);
            $ret=$fac->fetch($id);
            $nblignes = sizeof($fac->lignes);

			$facref = sanitize_string($fac->ref);
			$dir = $conf->facture->dir_output . "/" . $facref;
			$file = $dir . "/" . $facref . ".pdf";

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
                // Initialisation document vierge
                $pdf=new FPDF('P','mm',$this->format);
                $pdf->Open();
                $pdf->AddPage();

                $pdf->SetDrawColor(128,128,128);

                $pdf->SetTitle($fac->ref);
                $pdf->SetSubject($langs->trans("Bill"));
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($user->fullname);

                $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
                $pdf->SetAutoPageBreak(1,0);

                // Positionne $this->atleastonediscount si on a au moins une remise
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    if ($fac->lignes[$i]->remise_percent)
                    {
                        $this->atleastonediscount++;
                    }
                }

                $this->_pagehead($pdf, $fac);

                $pagenb = 1;
                $tab_top = 90;
                $tab_top_newpage = 50;
                $tab_height = 110;

                $iniY = $tab_top + 8;
                $curY = $tab_top + 8;
                $nexY = $tab_top + 8;

                // Boucle sur les lignes
                for ($i = 0 ; $i < $nblignes ; $i++)
                {
                    $curY = $nexY;

                    // Description de la ligne produit
                    $libelleproduitservice=$fac->lignes[$i]->libelle;
                    if ($fac->lignes[$i]->product_desc&&$fac->lignes[$i]->product_desc!=$fac->lignes[$i]->libelle)
                    {
                        if ($libelleproduitservice) $libelleproduitservice.="\n";
                        $libelleproduitservice.=$fac->lignes[$i]->product_desc;
                    }
                    // Si ligne associée à un code produit
                    if ($fac->lignes[$i]->produit_id)
                    {
                        $prodser = new Product($this->db);

                        $prodser->fetch($fac->lignes[$i]->produit_id);
                        if ($prodser->ref) {
                            $libelleproduitservice=$langs->trans("Product")." ".$prodser->ref." - ".$libelleproduitservice;
                        }

                        // Ajoute description du produit
                        if ($conf->global->FAC_ADD_PROD_DESC)
                        {
                            if ($fac->lignes[$i]->product_desc&&$fac->lignes[$i]->product_desc!=$fac->lignes[$i]->libelle&&$fac->lignes[$i]->product_desc!=$fac->lignes[$i]->desc)
                            {
                                if ($libelleproduitservice) $libelleproduitservice.="\n";
                                $libelleproduitservice.=$fac->lignes[$i]->product_desc;
                            }
                        }                    
                    }

                    if ($fac->lignes[$i]->date_start && $fac->lignes[$i]->date_end) {
                        // Affichage durée si il y en a une
                        $libelleproduitservice.="\n(".$langs->trans("From")." ".dolibarr_print_date($fac->lignes[$i]->date_start)." ".$langs->trans("to")." ".dolibarr_print_date($fac->lignes[$i]->date_end).")";
                    }

                    $pdf->SetFont('Arial','', 9);   // Dans boucle pour gérer multi-page

                    $pdf->SetXY ($this->posxdesc-1, $curY);
                    $pdf->MultiCell(108, 4, $libelleproduitservice, 0, 'J');

                    $nexY = $pdf->GetY();

                    // TVA
                    $pdf->SetXY ($this->posxtva, $curY);
                    $pdf->MultiCell(10, 4, ($fac->lignes[$i]->tva_taux < 0 ? '*':'').abs($fac->lignes[$i]->tva_taux), 0, 'R');

                    // Prix unitaire HT avant remise
                    $pdf->SetXY ($this->posxup, $curY);
                    $pdf->MultiCell(18, 4, price($fac->lignes[$i]->subprice), 0, 'R', 0);

                    // Quantité
                    $pdf->SetXY ($this->posxqty, $curY);
                    $pdf->MultiCell(10, 4, $fac->lignes[$i]->qty, 0, 'R');

                    // Remise sur ligne
                    $pdf->SetXY ($this->posxdiscount, $curY);
                    if ($fac->lignes[$i]->remise_percent)
					{
                        $pdf->MultiCell(14, 4, $fac->lignes[$i]->remise_percent."%", 0, 'R');
                    }

                    // Total HT ligne
                    $pdf->SetXY ($this->postotalht, $curY);
                    $total = price($fac->lignes[$i]->price * $fac->lignes[$i]->qty);
                    $pdf->MultiCell(23, 4, $total, 0, 'R', 0);

                    // Collecte des totaux par valeur de tva
                    // dans le tableau tva["taux"]=total_tva
					$tvaligne=$fac->lignes[$i]->price * $fac->lignes[$i]->qty;
					if ($fac->remise_percent) $tvaligne-=($tvaligne*$fac->remise_percent)/100;
					$this->tva[ (string)$fac->lignes[$i]->tva_taux ] += $tvaligne;

                    $nexY+=2;    // Passe espace entre les lignes

                    if ($nexY > 200 && $i < ($nblignes - 1))
                    {
                        $this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY);
                        $this->_pagefoot($pdf);
                        
                        // Nouvelle page
                        $pdf->AddPage();
                        $pagenb++;
                        $this->_pagehead($pdf, $fac, 0);

                        $nexY = $tab_top_newpage + 8;
                        $pdf->SetTextColor(0,0,0);
                        $pdf->SetFont('Arial','', 10);
                    }

                }
                // Affiche cadre tableau
                if ($pagenb == 1)
                {
                    $this->_tableau($pdf, $tab_top, $tab_height, $nexY);
                    $bottomlasttab=$tab_top + $tab_height + 1;
                }
                else 
                {
                    $this->_tableau($pdf, $tab_top_newpage, $tab_height, $nexY);
                    $bottomlasttab=$tab_top_newpage + $tab_height + 1;
                }
                
                $deja_regle = $fac->getSommePaiement();

                $posy=$this->_tableau_tot($pdf, $fac, $deja_regle, $bottomlasttab);

                if ($deja_regle) {            
                    $this->_tableau_versements($pdf, $fac, $posy);
                }

                /*
                 * Mode de règlement
                 */
                if ((! defined("FACTURE_CHQ_NUMBER") || ! FACTURE_CHQ_NUMBER) && (! defined("FACTURE_RIB_NUMBER") || ! FACTURE_RIB_NUMBER))
				{
                    $pdf->SetXY($this->marge_gauche, 228);
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

                        $pdf->SetXY($this->marge_gauche, 227);
                        $pdf->SetFont('Arial','B',8);
                        $pdf->MultiCell(90, 3, "Règlement par chèque à l'ordre de ".$account->proprio." envoyé à:",0,'L',0);
                        $pdf->SetXY($this->marge_gauche, 231);
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

                /*
                 * Conditions de règlements
                 */
                $pdf->SetFont('Arial','B',10);
                $pdf->SetXY($this->marge_gauche, 217);
                $titre = "Conditions de réglement:";
                $pdf->MultiCell(80, 5, $titre, 0, 'L');
                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(54, 217);
                $pdf->MultiCell(80, 5, $fac->cond_reglement_facture,0,'L');


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
    function _tableau_versements(&$pdf, $fac, $posy)
    {
        global $langs;
        $langs->load("main");
        $langs->load("bills");
     
        $tab3_posx = 120;
        $tab3_top = $posy + 8;
        $tab3_width = 80;
        $tab3_height = 4;

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY ($tab3_posx, $tab3_top - 5);
        $pdf->MultiCell(60, 5, $langs->trans("PaymentsAlreadyDone"), 0, 'L', 0);

        $pdf->Rect($tab3_posx, $tab3_top-1, $tab3_width, $tab3_height);

        $pdf->SetXY ($tab3_posx, $tab3_top-1 );
        $pdf->MultiCell(20, 4, $langs->trans("Payment"), 0, 'L', 0);
        $pdf->SetXY ($tab3_posx+21, $tab3_top-1 );
        $pdf->MultiCell(20, 4, $langs->trans("Amount"), 0, 'L', 0);
        $pdf->SetXY ($tab3_posx+41, $tab3_top-1 );
        $pdf->MultiCell(20, 4, $langs->trans("Type"), 0, 'L', 0);
        $pdf->SetXY ($tab3_posx+60, $tab3_top-1 );
        $pdf->MultiCell(20, 4, $langs->trans("Num"), 0, 'L', 0);

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
     *   \param      pdf             Objet PDF
     *   \param      fac             Objet facture
     *   \param      deja_regle      Montant deja regle
     *   \return     y               Position pour suite
     */
    function _tableau_tot(&$pdf, $fac, $deja_regle, $posy)
    {
        global $langs;
        $langs->load("main");
        $langs->load("bills");

        $tab2_top = $posy;
        $tab2_hl = 5;
        $tab2_height = $tab2_hl * 4;
        $pdf->SetFont('Arial','', 9);

        // Affiche la mention TVA non applicable selon option
        $pdf->SetXY($this->marge_gauche, $tab2_top + 0);
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
        $pdf->MultiCell($largcol2, $tab2_hl, price($fac->total_ht + $fac->remise), 0, 'R', 1);

        // Remise globale
        if ($fac->remise > 0)
        {
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("GlobalDiscount"), 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl);
            $pdf->MultiCell($largcol2, $tab2_hl, "-".$fac->remise_percent."%", 0, 'R', 1);

            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total HT après remise", 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * 2);
            $pdf->MultiCell($largcol2, $tab2_hl, price($fac->total_ht), 0, 'R', 1);

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
                $tvacompl = ( (float)$tvakey < 0 ) ? " (".$langs->trans("NonPercuRecuperable").")" : '' ; 
                $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalVAT").' '.abs($tvakey).'%'.$tvacompl, 0, 'L', 1);
    
                $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($largcol2, $tab2_hl, price($tvaval * (float)$tvakey / 100 ), 0, 'R', 1);
            }
        }
        if (! $this->atleastoneratenotnull)
        {
            $index++;
        	$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalVAT"), 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($fac->total_tva), 0, 'R', 1);
        }

        $useborder=0;
        
        $index++;
        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFillColor(224,224,224);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->trans("TotalTTC"), $useborder, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($fac->total_ttc), $useborder, 'R', 1);
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
            $pdf->MultiCell($largcol2, $tab2_hl, price($fac->total_ttc - $deja_regle), $useborder, 'R', 1);
            $pdf->SetFont('Arial','', 9);
            $pdf->SetTextColor(0,0,0);
        }
    
/* Ne semble pas requis par la réglementation
        $index++;
        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($col2x-$col1x+$largcol2, $tab2_hl, $langs->trans('DispenseMontantLettres'), 0, 'L' );
*/
        $index++;
        return ($tab2_top + ($tab2_hl * $index));
    }

    /*
     *   \brief      Affiche la grille des lignes de factures
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
        $titre = $langs->trans("AmountInCurrency",$langs->trans("Currency".$conf->monnaie));
        $pdf->Text($this->page_largeur - $this->marge_droite - $pdf->GetStringWidth($titre), $tab_top-1, $titre);

        $pdf->SetDrawColor(128,128,128);

        // Rect prend une longueur en 3eme param
        $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
        // line prend une position y en 3eme param
        $pdf->line($this->marge_gauche, $tab_top+6, $this->page_largeur-$this->marge_droite, $tab_top+6);

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
        if ($this->atleastonediscount)
        {
            $pdf->SetXY ($this->posxdiscount-1, $tab_top+2);
            $pdf->MultiCell(16,2, $langs->trans("Discount"),'','C');
        }
        
        if ($this->atleastonediscount)
        {
            $pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
        }
        $pdf->SetXY ($this->postotalht-1, $tab_top+2);
        $pdf->MultiCell(23,2, $langs->trans("TotalHT"),'','C');

    }

    /*
     *      \brief      Affiche en-tête facture
     *      \param      pdf             Objet PDF
     *      \param      fac             Objet facture
     *      \param      showadress      0=non, 1=oui
     */
    function _pagehead(&$pdf, $fac, $showadress=1)
    {
        global $langs,$conf,$mysoc;
        
        $langs->load("main");
        $langs->load("bills");
        $langs->load("propal");
        $langs->load("companies");
        
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Arial','B',13);

        $posy=$this->marge_haute;

        $pdf->SetXY($this->marge_gauche,$posy);

		// Logo
        $logo=$mysoc->logo;
        if (defined("FAC_PDF_LOGO") && FAC_PDF_LOGO) $logo=DOL_DATA_ROOT.FAC_PDF_LOGO;
        if ($logo)
        {
            if (is_readable($logo))
			{
                $pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
            }
            else
			{
                $pdf->SetTextColor(200,0,0);
                $pdf->SetFont('Arial','B',8);
                $pdf->MultiCell(100, 3, $langs->trans("ErrorLogoFileNotFound",$logo), 0, 'L');
                $pdf->MultiCell(100, 3, $langs->trans("ErrorGoToModuleSetup"), 0, 'L');
            }
        }
        else if (defined("FAC_PDF_INTITULE"))
        {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }

        $pdf->SetFont('Arial','B',13);
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 4, $langs->trans("Bill")." ".$fac->ref, '' , 'R');
        $pdf->SetFont('Arial','',12);
        
        $posy+=6;
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 4, $langs->trans("Date")." : " . dolibarr_print_date($fac->date,"%d %b %Y"), '', 'R');

        if ($showadress)
        {
            // Emetteur
            $posy=42;
            $hautcadre=40;
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Arial','',8);
            $pdf->SetXY($this->marge_gauche,$posy-5);
            $pdf->MultiCell(66,5, $langs->trans("BillFrom").":");
    
    
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
            if (defined("FAC_PDF_TEL") && FAC_PDF_TEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->trans("Phone").": ".FAC_PDF_TEL;
            elseif ($mysoc->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->trans("Phone").": ".$mysoc->tel;
            // Fax
            if (defined("FAC_PDF_FAX") && FAC_PDF_FAX) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->trans("Fax").": ".FAC_PDF_FAX;
            elseif ($mysoc->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->trans("Fax").": ".$mysoc->fax;
            // EMail
    		if (defined("FAC_PDF_MEL") && FAC_PDF_MEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->trans("Email").": ".FAC_PDF_MEL;
            elseif ($mysoc->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->trans("Email").": ".$mysoc->email;
            // Web
    		if (defined("FAC_PDF_WWW") && FAC_PDF_WWW) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->trans("Web").": ".FAC_PDF_WWW;
            elseif ($mysoc->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$langs->trans("Web").": ".$mysoc->url;
    
            $pdf->SetFont('Arial','',9);
            $pdf->SetXY($this->marge_gauche+2,$posy+8);
            $pdf->MultiCell(80,4, $carac_emetteur);
    
            // Client destinataire
            $posy=42;
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Arial','',8);
            $pdf->SetXY(102,$posy-5);
            $pdf->MultiCell(80,5, $langs->trans("BillTo").":");
            $fac->fetch_client();
            // Cadre client destinataire
            $pdf->rect(100, $posy, 100, $hautcadre);
    
            // Nom client
            $pdf->SetXY(102,$posy+3);
            $pdf->SetFont('Arial','B',11);
            $pdf->MultiCell(106,4, $fac->client->nom, 0, 'L');
    
            // Caractéristiques client
            $carac_client=$fac->client->adresse;
            $carac_client.="\n".$fac->client->cp . " " . $fac->client->ville."\n";
            if ($fac->client->tva_intra) $carac_client.="\n".$langs->trans("VATIntraShort").': '.$fac->client->tva_intra;
            $pdf->SetFont('Arial','',9);
            $pdf->SetXY(102,$posy+8);
            $pdf->MultiCell(86,4, $carac_client);
        }
        
    }

    /*
     *   \brief      Affiche le pied de page
     *   \param      pdf     objet PDF
     */
    function _pagefoot(&$pdf)
    {
        global $langs, $conf;
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
            $ligne1.=($ligne1?" - ":"").$langs->trans("CapitalOf",$conf->global->MAIN_INFO_CAPITAL)." ".$langs->trans("Currency".$conf->monnaie);
        }
        if ($conf->global->MAIN_INFO_SIRET)
        {
            $ligne1.=($ligne1?" - ":"").$langs->transcountry("ProfId2",$this->emetteur->code_pays).": ".$conf->global->MAIN_INFO_SIRET;
        }
        if ($conf->global->MAIN_INFO_SIREN && (! $conf->global->MAIN_INFO_SIRET || $this->emetteur->code_pays != 'FR'))
        {
            $ligne1.=($ligne1?" - ":"").$langs->transcountry("ProfId1",$this->emetteur->code_pays).": ".$conf->global->MAIN_INFO_SIREN;
        }
        if ($conf->global->MAIN_INFO_APE)
        {
            $ligne1.=($ligne1?" - ":"").$langs->transcountry("ProfId3",$this->emetteur->code_pays).": ".MAIN_INFO_APE;
        }

        // Deuxieme ligne d'info réglementaires
        $ligne2="";
        if ($conf->global->MAIN_INFO_RCS)
        {
            $ligne2.=($ligne2?" - ":"").$langs->transcountry("ProfId4",$this->emetteur->code_pays).": ".$conf->global->MAIN_INFO_RCS;
        }
        if ($conf->global->MAIN_INFO_TVAINTRA != '')
        {
            $ligne2.=($ligne2?" - ":"").$langs->trans("VATIntraShort").": ".$conf->global->MAIN_INFO_TVAINTRA;
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
