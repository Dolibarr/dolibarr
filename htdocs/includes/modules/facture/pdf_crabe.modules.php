<?PHP
/* Copyright (C) 2004 Laurent Destailleur  <eldy@users.sourceforge.net>
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

/*!	\file pdf_crabe.modules.php
		\brief  Fichier de la classe permettant de générer les factures au modèle Crabe
		\author	Laurent Destailleur
		\version $Revision$
*/


require_once(DOL_DOCUMENT_ROOT."/product.class.php");



/*!	\class pdf_crabe
		\brief  Classe permettant de générer les factures au modèle Crabe
*/

Class pdf_crabe {
    var $error='';
    
    /*!
    		\brief  Constructeur
    		\param	db		handler accès base de donnée
    */
    function pdf_crabe($db)
    {
        $this->db = $db;
        $this->description = "Modèle de facture complet (Gère l'option fiscale de facturation TVA, le choix du mode de règlement à afficher, logo...)";
        $this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
        $this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
        $this->option_modereg = 1;                 // Gere choix mode règlement FACTURE_CHQ_NUMBER, FACTURE_RIB_NUMBER
        $this->option_codeproduitservice = 1;      // Affiche code produit-service FACTURE_CODEPRODUITSERVICE
        $this->option_tvaintra = 1;                // Affiche tva intra MAIN_INFO_TVAINTRA
        $this->option_capital = 1;                 // Affiche capital MAIN_INFO_CAPITAL
    }


    /*!
    		\brief Renvoi le dernier message d'erreur de création de facture
    */
    function error()
    {
        return $this->error;
    }


    /*!
    		\brief  Fonction générant la facture sur le disque
    		\param	facid		id de la facture à générer
    		\return	 int    1=ok, 0=ko
            \remarks Variables utilisées
    		\remarks FAC_OUTPUTDIR
            \remarks FAC_PDF_LOGO
    		\remarks FACTURE_CODEPRODUITSERVICE
    		\remarks FACTURE_CHQ_NUMBER
    		\remarks FACTURE_RIB_NUMBER
    		\remarks FAC_OUTPUTDIR
    		\remarks FAC_PDF_INTITULE
    		\remarks FAC_PDF_INTITULE2
    		\remarks FAC_PDF_SIREN
    		\remarks FAC_PDF_SIRET
    		\remarks FAC_PDF_TEL
    		\remarks FAC_PDF_ADRESSE
    		\remarks MAIN_INFO_RCS
    		\remarks MAIN_INFO_CAPITAL
    		\remarks MAIN_INFO_TVAINTRA
    */
    function write_pdf_file($facid)
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
                    $this->error="Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
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
                $pdf->SetSubject("Facture");
                $pdf->SetCreator("Dolibarr ".DOL_VERSION);
                $pdf->SetAuthor($user->fullname);
                //$pdf->Keywords("Facture");

                $pdf->SetMargins(10, 10, 10);
                $pdf->SetAutoPageBreak(1,0);

                $tab_top = 96;
                $tab_height = 110;

                  if (defined("FAC_PDF_LOGO"))
                  {
                    $pdf->SetXY(10,5);
                    $pdf->Image(FAC_PDF_LOGO, 10, 5, 0, 24, 'PNG');
                  }

                $pdf->SetFillColor(220,220,220);
                $pdf->SetFont('Arial','', 9);
                $pdf->SetXY (10, $tab_top + 10 );

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
                    $pdf->SetXY (11, $curY );
                    if (defined("FACTURE_CODEPRODUITSERVICE") && FACTURE_CODEPRODUITSERVICE) {
                        // Affiche code produit si ligne associée à un code produit

                        $prodser = new Product($this->db);

                        $prodser->fetch($fac->lignes[$i]->produit_id);
                        $codeproduitservice=" (Code produit ".$prodser->ref.")";
                    }
                    if ($fac->lignes[$i]->date_start && $fac->lignes[$i]->date_end) {
                        // Affichage durée si il y en a une
                        $codeproduitservice=" (Du ".dolibarr_print_date($fac->lignes[$i]->date_start)." au ".dolibarr_print_date($fac->lignes[$i]->date_end).")";
                    }
                    $pdf->MultiCell(118, 5, $fac->lignes[$i]->desc."$codeproduitservice", 0, 'J');

                    $nexY = $pdf->GetY();

                    // TVA
                    $pdf->SetXY (121, $curY);
                    $pdf->MultiCell(10, 5, $fac->lignes[$i]->tva_taux, 0, 'C');

                    // Prix unitaire HT
                    $pdf->SetXY (133, $curY);
                    $pdf->MultiCell(16, 5, price($fac->lignes[$i]->price), 0, 'R', 0);

                    // Quantité
                    $pdf->SetXY (151, $curY);
                    $pdf->MultiCell(10, 5, $fac->lignes[$i]->qty, 0, 'R');

                    // Remise sur ligne
                    $pdf->SetXY (163, $curY);
                    if ($fac->lignes[$i]->remise_percent) {
                        $pdf->MultiCell(14, 5, $fac->lignes[$i]->remise_percent."%", 0, 'R');
                    }

                    // Total HT
                    $pdf->SetXY (173, $curY);
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

                $deja_regle = $fac->getSommePaiement();

                $this->_tableau_tot($pdf, $fac, $deja_regle);

                if ($deja_regle) {            
                    $this->_tableau_versements($pdf, $fac);
                }

                /*
                * Mode de règlement
                */
                if ((! defined("FACTURE_CHQ_NUMBER") || ! FACTURE_CHQ_NUMBER) && (! defined("FACTURE_RIB_NUMBER") || ! FACTURE_RIB_NUMBER)) {
                    $pdf->SetXY (10, 228);
                    $pdf->SetTextColor(200,0,0);
                    $pdf->SetFont('Arial','B',8);
                    $pdf->MultiCell(90, 3, "Aucun mode de règlement défini.",0,'L',0);
                    $pdf->MultiCell(90, 3, "Créer un compte bancaire puis aller dans la Configuration du module facture pour définir les modes de règlement.",0,'L',0);
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
                * Conditions de règlements
                */
                $pdf->SetFont('Arial','B',10);
                $pdf->SetXY(10, 217);
                $titre = "Conditions de réglement:";
                $pdf->MultiCell(80, 5, $titre, 0, 'L');
                $pdf->SetFont('Arial','',10);
                $pdf->SetXY(54, 217);
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
                $this->error="Erreur: Le répertoire '$dir' n'existe pas et Dolibarr n'a pu le créer.";
                return 0;
            }
        }
        else
        {
            $this->error="Erreur: FAC_OUTPUTDIR non défini !";
            return 0;
        }
        $this->error="Erreur: Erreur inconnue";
        return 0;   // Erreur par defaut
    }


    /*
    *
    *
    *
    */
    function _tableau_versements(&$pdf, $fac)
    {
        $tab3_posx = 120;
        $tab3_top = 240;
        $tab3_width = 80;
        $tab3_height = 4;

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY ($tab3_posx, $tab3_top - 5);
        $pdf->MultiCell(60, 5, "Versements déjà effectués", 0, 'L', 0);

        $pdf->Rect($tab3_posx, $tab3_top-1, $tab3_width, $tab3_height);

        $pdf->SetXY ($tab3_posx, $tab3_top-1 );
        $pdf->MultiCell(20, 4, "Paiement", 0, 'L', 0);
        $pdf->SetXY ($tab3_posx+21, $tab3_top-1 );
        $pdf->MultiCell(20, 4, "Montant", 0, 'L', 0);
        $pdf->SetXY ($tab3_posx+41, $tab3_top-1 );
        $pdf->MultiCell(20, 4, "Type", 0, 'L', 0);
        $pdf->SetXY ($tab3_posx+60, $tab3_top-1 );
        $pdf->MultiCell(20, 4, "Num", 0, 'L', 0);

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
            	    $oper = 'WWW';
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
            $this->error="Echec requete SQL";
            return 0;
        }

    }


    function _tableau_tot(&$pdf, $fac, $deja_regle)
    {
        $tab2_top = 207;
        $tab2_hl = 5;
        $tab2_height = $tab2_hl * 4;
        $pdf->SetFont('Arial','', 9);

        // Affiche la mention TVA non applicable selon option
        $pdf->SetXY (10, $tab2_top + 0);
        if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise') {
            $pdf->MultiCell(100, $tab2_hl, "* TVA non applicable art-293B du CGI", 0, 'L', 0);
        }

        // Tableau total
        $col1x=120; $col2x=174;
        $pdf->SetXY ($col1x, $tab2_top + 0);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total HT", 0, 'L', 0);

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
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total TVA", 0, 'L', 0);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell(26, $tab2_hl, price($fac->total_tva), 0, 'R', 0);

        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+1));
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Total TTC", 0, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+1));
        $pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc), 0, 'R', 1);

        if ($deja_regle > 0)
        {
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+2));
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Déjà réglé", 0, 'L', 0);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+2));
            $pdf->MultiCell(26, $tab2_hl, price($deja_regle), 0, 'R', 0);

            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+3));
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, "Reste à payer", 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+3));
            $pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc - $deja_regle), 0, 'R', 1);
        }
    }

    /*
    * Grille des lignes de factures
    */
    function _tableau(&$pdf, $tab_top, $tab_height, $nexY)
    {
        $pdf->Rect( 10, $tab_top, 190, $tab_height);
        $pdf->line( 10, $tab_top+8, 200, $tab_top+8 );

        $pdf->SetFont('Arial','',10);

        $pdf->Text(11,$tab_top + 5,'Désignation');

        $pdf->line(120, $tab_top, 120, $tab_top + $tab_height);
        $pdf->Text(122, $tab_top + 5,'TVA');

        $pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
        $pdf->Text(135, $tab_top + 5,'P.U. HT');

        $pdf->line(150, $tab_top, 150, $tab_top + $tab_height);
        $pdf->Text(153, $tab_top + 5,'Qté');

        $pdf->line(162, $tab_top, 162, $tab_top + $tab_height);
        $pdf->Text(163, $tab_top + 5,'Remise');

        $pdf->line(177, $tab_top, 177, $tab_top + $tab_height);
        $pdf->Text(185, $tab_top + 5,'Total HT');

    }

    /*
    *
    *
    */
    function _pagehead(&$pdf, $fac)
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
        $pdf->SetFont('Arial','',12);
        $pdf->SetXY(100,11);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 10, "Date : " . strftime("%d %b %Y", mktime()), '', 'R');

        /*
        * Emetteur
        */
        $posy=42;
        $pdf->SetTextColor(0,0,0);
        $pdf->SetFont('Arial','',8);
        $pdf->SetXY(10,$posy-5);
        $pdf->MultiCell(66,5, "Emetteur:");


        $pdf->SetXY(10,$posy);
        $pdf->SetFillColor(230,230,230);
        $pdf->MultiCell(82, 34, "", 0, 'R', 1);


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
        if (defined("FAC_PDF_SIRET"))
        {
            $pdf->SetFont('Arial','',10);
            $pdf->MultiCell(60, 4, "SIRET : ".FAC_PDF_SIRET);
        }
        elseif (defined("FAC_PDF_SIREN"))
        {
            $pdf->SetFont('Arial','',10);
            $pdf->MultiCell(60, 4, "SIREN : ".FAC_PDF_SIREN);
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

    /*
    *
    *
    */
   function _pagefoot(&$pdf, $fac)
    {
        $footy=13;
        $pdf->SetFont('Arial','',8);

        if (MAIN_INFO_CAPITAL) {
            $pdf->SetY(-$footy);
            $pdf->MultiCell(190, 3,"SARL au Capital de " . MAIN_INFO_CAPITAL." ".MAIN_MONNAIE." - " . MAIN_INFO_RCS." - Identifiant professionnel: " . MAIN_INFO_SIREN , 0, 'C');
            $footy-=3;
        }

        // Affiche le numéro de TVA intracommunautaire
        if (MAIN_INFO_TVAINTRA == 'MAIN_INFO_TVAINTRA') {
            $pdf->SetY(-$footy);
            $pdf->SetTextColor(200,0,0);
            $pdf->SetFont('Arial','B',8);
            $pdf->MultiCell(190, 3, "Numéro de TVA intracommunautaire pas encore configuré.",0,'L',0);
            $pdf->MultiCell(190, 3, "Aller dans la Configuration générale pour le définir ou l'effacer.",0,'L',0);
            $pdf->SetTextColor(0,0,0);
        }
        elseif (MAIN_INFO_TVAINTRA != '') {
            $pdf->SetY(-$footy);
            $pdf->MultiCell(190, 3, "Numéro de TVA intracommunautaire : ".MAIN_INFO_TVAINTRA, 0, 'C');
        }

        $pdf->SetXY(-10,-10);
        $pdf->MultiCell(10, 3, $pdf->PageNo().'/{nb}', 0, 'R');
    }

}

?>
