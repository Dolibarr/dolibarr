<?php
/* Copyright (C) 2004-2008 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2008 Regis Houssin          <regis.houssin@dolibarr.fr>
 * Copyright (C) 2008 Raphael Bertrand (Resultic) <raphael.bertrand@resultic.fr>
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
 */

/**
       	\file       htdocs/includes/modules/facture/pdf_crabe.modules.php
		\ingroup    facture
		\brief      File of class to generate invoices from crab model
		\author	    Laurent Destailleur
		\version    $Id$
*/

require_once(DOL_DOCUMENT_ROOT."/includes/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
	    \class      pdf_crabe
		\brief      Classe permettant de gï¿½nï¿½rer les factures au modï¿½le Crabe
*/

class pdf_crabe extends ModelePDFFactures
{
	var $emetteur;	// Objet societe qui emet


    /**
     *		\brief  Constructor
     *		\param	db		Database handler
     */
    function pdf_crabe($db)
    {
        global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
        $this->name = "crabe";
        $this->description = $langs->trans('PDFCrabeDescription');
        
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
        $this->option_escompte = 1;                // Affiche si il y a eu escompte
        $this->option_credit_note = 1;             // Gère les avoirs
		$this->option_freetext = 1;					// Support add of a personalised text
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts
		
    	if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise')
      		$this->franchise=1;

        // Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'était pas défini

        // Defini position des colonnes
        $this->posxdesc=$this->marge_gauche+1;
        $this->posxtva=113;
        $this->posxup=126;
        $this->posxqty=145;
        $this->posxdiscount=162;
        $this->postotalht=174;

        $this->tva=array();
        $this->atleastoneratenotnull=0;
        $this->atleastonediscount=0;
    }


    /**
     *		\brief      Fonction générant la facture sur le disque
     *		\param	    fac				Objet facture à générer (ou id si ancienne methode)
	 *		\param		outputlangs		Lang object for output language
     *		\return	    int     		1=ok, 0=ko
     */
    function write_file($fac,$outputlangs)
	{
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output='ISO-8859-1';
		
		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		
		$outputlangs->setPhpLang();

		if ($conf->facture->dir_output)
		{
			// Définition de l'objet $fac (pour compatibilite ascendante)
			if (! is_object($fac))
			{
				$id = $fac;
				$fac = new Facture($this->db,"",$id);
				$ret=$fac->fetch($id);
			}
			
			$deja_regle = $fac->getSommePaiement();
			$amount_credit_not_included = $fac->getSommeCreditNote();

			// Définition de $dir et $file
			if ($fac->specimen)
			{
				$dir = $conf->facture->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$facref = sanitizeFileName($fac->ref);
				$dir = $conf->facture->dir_output . "/" . $facref;
				$file = $dir . "/" . $facref . ".pdf";
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
				$nblignes = sizeof($fac->lignes);

				// Protection et encryption du pdf
				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdf=new FPDI_Protection('P','mm',$this->format);
					$pdfrights = array('print'); // Ne permet que l'impression du document
					$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
					$pdfownerpass = NULL; // Mot de passe du propriétire, crée aléatoirement si pas défini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}
				else
				{
					$pdf=new FPDI('P','mm',$this->format);
				}
				
				$pdf->Open();
				$pdf->AddPage();

				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($fac->ref);
				$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
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

				// Tete de page
				$this->_pagehead($pdf, $fac, 1, $outputlangs);

				$pagenb = 1;
				$tab_top = 90;
				$tab_top_newpage = 50;
				$tab_height = 110;
				$tab_height_newpage = 180;

				// Affiche notes
				if ($fac->note_public)
				{
					$tab_top = 88;

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour gerer multi-page
					$pdf->SetXY ($this->posxdesc-1, $tab_top);
					$pdf->MultiCell(190, 3, $fac->note_public, 0, 'J');
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
					$libelleproduitservice=dol_htmlentitiesbr($fac->lignes[$i]->libelle,1);
					if ($fac->lignes[$i]->desc&&$fac->lignes[$i]->desc!=$fac->lignes[$i]->libelle)
					{
						if ($libelleproduitservice) $libelleproduitservice.="<br>";
						
						if ($fac->lignes[$i]->desc == '(CREDIT_NOTE)' && $fac->lignes[$i]->fk_remise_except)
						{
							$discount=new DiscountAbsolute($this->db);
							$discount->fetch($fac->lignes[$i]->fk_remise_except);
							$libelleproduitservice=dol_htmlentitiesbr($langs->trans("DiscountFromCreditNote",$discount->ref_facture_source),1);
						}
						else
						{
							if ($fac->lignes[$i]->produit_id)
							{
								$libelleproduitservice.=dol_htmlentitiesbr($fac->lignes[$i]->desc,1);
							}
							else
							{
								//$fac->lignes[$i]->desc='€zaaaa';
								//print dol_string_is_good_iso($fac->lignes[$i]->desc);
								//print dol_htmlentitiesbr($fac->lignes[$i]->desc);
								//print exit;
								$libelleproduitservice.=dol_htmlentitiesbr($fac->lignes[$i]->desc,1);
							}
						}
					}
					
					// Si ligne associee a un code produit
					if ($fac->lignes[$i]->produit_id)
					{
						$prodser = new Product($this->db);
						$prodser->fetch($fac->lignes[$i]->produit_id);
						// On ajoute la ref
						if ($prodser->ref)
						{
							$prefix_prodserv = "";
							if($prodser->isservice())
							{
								$prefix_prodserv = $outputlangs->transnoentities("Service")." ";
							}
							else
							{
								$prefix_prodserv = $outputlangs->transnoentities("Product")." ";
							}

							$libelleproduitservice=$prefix_prodserv.$prodser->ref." - ".$libelleproduitservice;
						}
						
					}

					if ($fac->lignes[$i]->date_start && $fac->lignes[$i]->date_end)
					{
						// Affichage duree si il y en a une
						$libelleproduitservice.="<br>".dol_htmlentitiesbr("(".$outputlangs->transnoentities("From")." ".dolibarr_print_date($fac->lignes[$i]->date_start,'',false,$outputlangs)." ".$outputlangs->transnoentities("to")." ".dolibarr_print_date($fac->lignes[$i]->date_end,'',false,$outputlangs).")",1);
					}
					//if ($i==0) { print $libelleproduitservice; exit; }

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour gerer multi-page

					// Description
					$pdf->writeHTMLCell($this->posxtva-$this->posxdesc-1, 3, $this->posxdesc-1, $curY, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 1);

					$pdf->SetFont('Arial','', 9);   // On repositionne la police par defaut
					$nexY = $pdf->GetY();

					// TVA
					$pdf->SetXY ($this->posxtva, $curY);
					$pdf->MultiCell($this->posxup-$this->posxtva-1, 3, vatrate($fac->lignes[$i]->tva_tx,1,$fac->lignes[$i]->info_bits), 0, 'R');

					// Prix unitaire HT avant remise
					$pdf->SetXY ($this->posxup, $curY);
					$pdf->MultiCell($this->posxqty-$this->posxup-1, 3, price($fac->lignes[$i]->subprice), 0, 'R', 0);

					// Quantity
					$pdf->SetXY ($this->posxqty, $curY);
					$pdf->MultiCell($this->posxdiscount-$this->posxqty-1, 3, $fac->lignes[$i]->qty, 0, 'R');	// Enough for 6 chars

					// Remise sur ligne
					$pdf->SetXY ($this->posxdiscount, $curY);
					if ($fac->lignes[$i]->remise_percent)
					{
						$pdf->MultiCell($this->postotalht-$this->posxdiscount-1, 4, dolibarr_print_reduction($fac->lignes[$i]->remise_percent), 0, 'R');
					}

					// Total HT ligne
					$pdf->SetXY ($this->postotalht, $curY);
					$total = price($fac->lignes[$i]->total_ht);
					$pdf->MultiCell(26, 4, $total, 0, 'R', 0);

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					$tvaligne=$fac->lignes[$i]->total_tva;
					if ($fac->remise_percent) $tvaligne-=($tvaligne*$fac->remise_percent)/100;
					$vatrate=(string) $fac->lignes[$i]->tva_tx;
					if ($fac->lignes[$i]->info_bits & 0x01 == 0x01) $vatrate.='*';
					$this->tva[$vatrate] += $tvaligne;

					$nexY+=2;    // Passe espace entre les lignes

					// Cherche nombre de lignes a venir pour savoir si place suffisante
					if ($i < ($nblignes - 1))	// If it's not last line
					{
						//on récupère la description du produit suivant
						$follow_descproduitservice = $fac->lignes[$i+1]->desc;
						//on compte le nombre de ligne afin de vérifier la place disponible (largeur de ligne 52 caracteres)
						$nblineFollowDesc = num_lines($follow_descproduitservice,52)*4;
						// Et si on affiche dates de validite, on ajoute encore une ligne
						if ($fac->lignes[$i]->date_start && $fac->lignes[$i]->date_end)
						{
							$nblineFollowDesc += 4;
						}
					}
					else	// If it's last line
					{
						$nblineFollowDesc = 0;
					}
										
					// test si besoin nouvelle page
					if (($nexY+$nblineFollowDesc) > ($tab_top+$tab_height) && $i < ($nblignes - 1))
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY, $outputlangs);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
						}

						$this->_pagefoot($pdf,$fac,$outputlangs);

						// Nouvelle page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $fac, 0, $outputlangs);

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

				// Affiche zone infos
				$posy=$this->_tableau_info($pdf, $fac, $bottomlasttab, $outputlangs);

				// Affiche zone totaux
				$posy=$this->_tableau_tot($pdf, $fac, $deja_regle, $bottomlasttab, $outputlangs);

				// Affiche zone versements
				if ($deja_regle || $amount_credit_not_included)
				{
					$posy=$this->_tableau_versements($pdf, $fac, $posy, $outputlangs);
				}

				// Pied de page
				$this->_pagefoot($pdf,$fac,$outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);
				if (! empty($conf->global->MAIN_UMASK)) 
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				
				$langs->setPhpLang();	// On restaure langue session
				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				$langs->setPhpLang();	// On restaure langue session
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","FAC_OUTPUTDIR");
			$langs->setPhpLang();	// On restaure langue session
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
		$langs->setPhpLang();	// On restaure langue session
		return 0;   // Erreur par defaut
	}


    /**
     *  \brief      Affiche tableau des versement
     *  \param      pdf     		Objet PDF
     *  \param      fac     		Objet facture
	 *	\param		posy			Position y in PDF
	 *	\param		outputlangs		Object langs for output
	 *	\return 	int				<0 if KO, >0 if OK
     */
    function _tableau_versements(&$pdf, $fac, $posy, $outputlangs)
	{
        $tab3_posx = 120;
        $tab3_top = $posy + 8;
        $tab3_width = 80;
        $tab3_height = 4;

        $pdf->SetFont('Arial','',8);
        $pdf->SetXY ($tab3_posx, $tab3_top - 5);
        $pdf->MultiCell(60, 5, $outputlangs->transnoentities("PaymentsAlreadyDone"), 0, 'L', 0);

        $pdf->Rect($tab3_posx, $tab3_top-1, $tab3_width, $tab3_height);

        $pdf->SetXY ($tab3_posx, $tab3_top-1 );
        $pdf->MultiCell(20, 4, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
        $pdf->SetXY ($tab3_posx+21, $tab3_top-1 );
        $pdf->MultiCell(20, 4, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
        $pdf->SetXY ($tab3_posx+41, $tab3_top-1 );
        $pdf->MultiCell(20, 4, $outputlangs->transnoentities("Type"), 0, 'L', 0);
        $pdf->SetXY ($tab3_posx+60, $tab3_top-1 );
        $pdf->MultiCell(20, 4, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$y=0;

		// Loop on each credit note included
		$sql = "SELECT re.rowid, re.amount_ht, re.amount_tva, re.amount_ttc,";
		$sql.= " re.description, re.fk_facture_source, re.fk_facture_source";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re";
		$sql.= " WHERE fk_facture = ".$fac->id;
		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
            $i=0;
			$invoice=new Facture($this->db);
			while ($i < $num)
			{
				$y+=3;
				$obj = $this->db->fetch_object($resql);

				$invoice->fetch($obj->fk_facture_source);

                $pdf->SetXY ($tab3_posx, $tab3_top+$y );
                $pdf->MultiCell(20, 4,'', 0, 'L', 0);
                $pdf->SetXY ($tab3_posx+21, $tab3_top+$y);
                $pdf->MultiCell(20, 4, price($obj->amount_ttc), 0, 'L', 0);
                $pdf->SetXY ($tab3_posx+41, $tab3_top+$y);
                $pdf->MultiCell(20, 4, $outputlangs->trans("CreditNote"), 0, 'L', 0);
                $pdf->SetXY ($tab3_posx+60, $tab3_top+$y);
                $pdf->MultiCell(20, 4, $invoice->ref, 0, 'L', 0);

                $pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3 );

                $i++;
			}
		}
		else
		{
            $this->error=$this->db->lasterror();
			dolibarr_syslog($this->db,$this->error);
            return -1;
		}

		// Loop on each payment
        $sql = "SELECT ".$this->db->pdate("p.datep")."as date, pf.amount as amount, p.fk_paiement as type, p.num_paiement as num ";
        $sql.= "FROM ".MAIN_DB_PREFIX."paiement as p, ".MAIN_DB_PREFIX."paiement_facture as pf ";
        $sql.= "WHERE pf.fk_paiement = p.rowid and pf.fk_facture = ".$fac->id." ";
        $sql.= "ORDER BY p.datep";
        $resql=$this->db->query($sql);
		if ($resql)
        {
            $pdf->SetFont('Arial','',6);
            $num = $this->db->num_rows($resql);
            $i=0;
            while ($i < $num) {
                $y+=3;
                $row = $this->db->fetch_row($resql);

                $pdf->SetXY ($tab3_posx, $tab3_top+$y );
                $pdf->MultiCell(20, 4, dolibarr_print_date($row[0],'day',false,$outputlangs), 0, 'L', 0);
                $pdf->SetXY ($tab3_posx+21, $tab3_top+$y);
                $pdf->MultiCell(20, 4, price($row[1]), 0, 'L', 0);
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
            $this->error=$this->db->lasterror();
			dolibarr_syslog($this->db,$this->error);
            return -1;
        }

    }


	/**
     *	\brief      Affiche infos divers
     *	\param      pdf             Objet PDF
     *	\param      object          Objet facture
     *	\param		posy			Position depart
     *	\param		outputlangs		Objet langs
     *	\return     y               Position pour suite
     */
    function _tableau_info(&$pdf, $object, $posy, $outputlangs)
    {
        global $conf;

        $pdf->SetFont('Arial','', 9);

        /*
        *	If France, show VAT mention if not applicable
        */
    	if ($this->emetteur->pays_code == 'FR' && $this->franchise == 1)
      	{
	        $pdf->SetFont('Arial','B',8);
	        $pdf->SetXY($this->marge_gauche, $posy);
            $pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

            $posy=$pdf->GetY()+4;
        }

        /*
        *	Conditions de reglements
        */
        if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement))
        {
            $pdf->SetFont('Arial','B',8);
            $pdf->SetXY($this->marge_gauche, $posy);
            $titre = $outputlangs->transnoentities("PaymentConditions").':';
            $pdf->MultiCell(80, 5, $titre, 0, 'L');

            $pdf->SetFont('Arial','',8);
            $pdf->SetXY(50, $posy);
            $lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$object->cond_reglement;
            $pdf->MultiCell(80, 5, $lib_condition_paiement,0,'L');

            $posy=$pdf->GetY()+3;
		}


        if ($object->type != 2)
        {
	        /*
	        *	Check si absence mode reglement
	        */
	        if (empty($object->mode_reglement_code) 
	        	&& ! $conf->global->FACTURE_CHQ_NUMBER
	        	&& ! $conf->global->FACTURE_RIB_NUMBER)
			{
	            $pdf->SetXY($this->marge_gauche, $posy);
	            $pdf->SetTextColor(200,0,0);
	            $pdf->SetFont('Arial','B',8);
	            $pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
	            $pdf->SetTextColor(0,0,0);
	
	            $posy=$pdf->GetY()+1;
	        }
        	
        	// Sown payment mode
        	if ($object->mode_reglement_code
        		 && $object->mode_reglement_code != 'CHQ'
           		 && $object->mode_reglement_code != 'VIR')
           		 {
		            $pdf->SetFont('Arial','B',8);
		            $pdf->SetXY($this->marge_gauche, $posy);
		            $titre = $outputlangs->transnoentities("PaymentMode").':';
		            $pdf->MultiCell(80, 5, $titre, 0, 'L');
		
		            $pdf->SetFont('Arial','',8);
		            $pdf->SetXY(50, $posy);
		            $lib_mode_reg=$outputlangs->transnoentities("PaymentMode".$object->mode_reglement_code)!=('PaymentMode'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentMode".$object->mode_reglement_code):$object->mode_reglement;
		            $pdf->MultiCell(80, 5, $lib_mode_reg,0,'L');
           		 	
		            $posy=$pdf->GetY()+2;
           		 }
	
			/*
	         * Propose mode reglement par CHQ
	         */
	        if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ')
	        {
	        	// Si mode reglement non force ou si force a CHQ
		        if ($conf->global->FACTURE_CHQ_NUMBER)
		        {
		            if ($conf->global->FACTURE_CHQ_NUMBER > 0)
		            {
		                $account = new Account($this->db);
		                $account->fetch($conf->global->FACTURE_CHQ_NUMBER);
		
		                $pdf->SetXY($this->marge_gauche, $posy);
		                $pdf->SetFont('Arial','B',8);
		                $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio).':',0,'L',0);
			            $posy=$pdf->GetY()+1;
		
		                $pdf->SetXY($this->marge_gauche, $posy);
		                $pdf->SetFont('Arial','',8);
		                $pdf->MultiCell(80, 3, $account->adresse_proprio, 0, 'L', 0);
		
			            $posy=$pdf->GetY()+2;
		            }
		            if ($conf->global->FACTURE_CHQ_NUMBER == -1)
		            {
		                $pdf->SetXY($this->marge_gauche, $posy);
		                $pdf->SetFont('Arial','B',8);
		                $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedToShort').' '.$this->emetteur->nom.' '.$outputlangs->transnoentities('SendTo').':',0,'L',0);
			            $posy=$pdf->GetY()+1;
		
		                $pdf->SetXY($this->marge_gauche, $posy);
		                $pdf->SetFont('Arial','',8);
		                $pdf->MultiCell(80, 6, $this->emetteur->adresse_full, 0, 'L', 0);
		
			            $posy=$pdf->GetY()+2;
		            }
		        }
			}
			
	        /*
	         * Si mode reglement non force ou si force a VIR, propose mode reglement par RIB
	         */
	        if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR')
	        {
		        if (! empty($conf->global->FACTURE_RIB_NUMBER))
		        {
	                $account = new Account($this->db);
	                $account->fetch($conf->global->FACTURE_RIB_NUMBER);
	
	                $this->marges['g']=$this->marge_gauche;
	
	                $cury=$posy;
	                $pdf->SetXY ($this->marges['g'], $cury);
	                $pdf->SetFont('Arial','B',8);
	                $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
	                $cury+=4;
	                $pdf->SetFont('Arial','B',6);
	                $pdf->line($this->marges['g']+1, $cury, $this->marges['g']+1, $cury+10 );
	                $pdf->SetXY ($this->marges['g'], $cury);
	                $pdf->MultiCell(18, 3, $outputlangs->transnoentities("BankCode"), 0, 'C', 0);
	                $pdf->line($this->marges['g']+18, $cury, $this->marges['g']+18, $cury+10 );
	                $pdf->SetXY ($this->marges['g']+18, $cury);
	                $pdf->MultiCell(18, 3, $outputlangs->transnoentities("DeskCode"), 0, 'C', 0);
	                $pdf->line($this->marges['g']+36, $cury, $this->marges['g']+36, $cury+10 );
	                $pdf->SetXY ($this->marges['g']+36, $cury);
	                $pdf->MultiCell(24, 3, $outputlangs->transnoentities("BankAccountNumber"), 0, 'C', 0);
	                $pdf->line($this->marges['g']+60, $cury, $this->marges['g']+60, $cury+10 );
	                $pdf->SetXY ($this->marges['g']+60, $cury);
	                $pdf->MultiCell(13, 3, $outputlangs->transnoentities("BankAccountNumberKey"), 0, 'C', 0);
	                $pdf->line($this->marges['g']+73, $cury, $this->marges['g']+73, $cury+10 );
	
	                $pdf->SetFont('Arial','',8);
	                $pdf->SetXY ($this->marges['g'], $cury+5);
	                $pdf->MultiCell(18, 3, $outputlangs->convToOutputCharset($account->code_banque), 0, 'C', 0);
	                $pdf->SetXY ($this->marges['g']+18, $cury+5);
	                $pdf->MultiCell(18, 3, $outputlangs->convToOutputCharset($account->code_guichet), 0, 'C', 0);
	                $pdf->SetXY ($this->marges['g']+36, $cury+5);
	                $pdf->MultiCell(24, 3, $outputlangs->convToOutputCharset($account->number), 0, 'C', 0);
	                $pdf->SetXY ($this->marges['g']+60, $cury+5);
	                $pdf->MultiCell(13, 3, $outputlangs->convToOutputCharset($account->cle_rib), 0, 'C', 0);
	
	                $pdf->SetXY ($this->marges['g'], $cury+12);
	                $pdf->MultiCell(90, 3, $outputlangs->transnoentities("Residence").' : ' . $outputlangs->convToOutputCharset($account->domiciliation), 0, 'L', 0);
	                $pdf->SetXY ($this->marges['g'], $cury+22);
	                $pdf->MultiCell(90, 3, $outputlangs->transnoentities("IbanPrefix").' : ' . $outputlangs->convToOutputCharset($account->iban_prefix), 0, 'L', 0);
	                $pdf->SetXY ($this->marges['g'], $cury+25);
	                $pdf->MultiCell(90, 3, $outputlangs->transnoentities("BIC").' : ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);
	
		            $posy=$pdf->GetY()+2;
		        }
			}
        }
        
        return $posy;
    }


    /**
     *	\brief      Affiche le total a payer
     *	\param      pdf             Objet PDF
     *	\param      object          Objet facture
     *	\param      deja_regle      Montant deja regle
     *	\param		posy			Position depart
     *	\param		outputlangs		Objet langs
     *	\return     y               Position pour suite
     */
    function _tableau_tot(&$pdf, $object, $deja_regle, $posy, $outputlangs)
    {
        $tab2_top = $posy;
        $tab2_hl = 5;
        $tab2_height = $tab2_hl * 4;
        $pdf->SetFont('Arial','', 9);

        // Tableau total
        $lltot = 200; $col1x = 120; $col2x = 182; $largcol2 = $lltot - $col2x;

        // Total HT
        $pdf->SetFillColor(255,255,255);
        $pdf->SetXY ($col1x, $tab2_top + 0);
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + 0);
        $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ht + $object->remise), 0, 'R', 1);

        $index = 0;

        // Affichage des totaux de TVA par taux (conformement a la reglementation)
        $pdf->SetFillColor(248,248,248);

        foreach( $this->tva as $tvakey => $tvaval )
        {
            if ($tvakey)    // On affiche pas taux 0
            {
                $this->atleastoneratenotnull++;

                $index++;
            	$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
				
				$tvacompl='';
				if (eregi('\*',$tvakey))
				{
					$tvakey=eregi_replace('\*','',$tvakey);
					$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")"; 
				}
                $totalvat =$outputlangs->transnoentities("TotalVAT").' ';
				$totalvat.=vatrate($tvakey,1).$tvacompl;
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

                $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
                $pdf->MultiCell($largcol2, $tab2_hl, price($tvaval), 0, 'R', 1);
            }
        }
        if (! $this->atleastoneratenotnull)	// If not vat at all
        {
            $index++;
        	$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalVAT"), 0, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_tva), 0, 'R', 1);
        }

        $useborder=0;

        $index++;
        $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFillColor(224,224,224);
        $text=$outputlangs->transnoentities("TotalTTC");
        if ($object->type == 2) $text=$outputlangs->transnoentities("TotalTTCToYourCredit");
        $pdf->MultiCell($col2x-$col1x, $tab2_hl, $text, $useborder, 'L', 1);

        $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
        $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc), $useborder, 'R', 1);
        $pdf->SetTextColor(0,0,0);

        if ($deja_regle > 0)
        {
            $index++;

            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("AlreadyPayed"), 0, 'L', 0);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle), 0, 'R', 0);

			$resteapayer = $object->total_ttc - $deja_regle;
			if ($object->paye) $resteapayer=0;

			if ($object->close_code == 'discount_vat')
			{
	            $index++;
        		$pdf->SetFillColor(255,255,255);

	            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
	            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOffered"), $useborder, 'L', 1);

	            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
	            $pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle), $useborder, 'R', 1);
				
				$resteapayer=0;
			}

            $index++;
            $pdf->SetTextColor(0,0,60);
	        $pdf->SetFillColor(224,224,224);
            $pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);

            $pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
            $pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer), $useborder, 'R', 1);

			// Fin
            $pdf->SetFont('Arial','', 9);
            $pdf->SetTextColor(0,0,0);
        }

        $index++;
        return ($tab2_top + ($tab2_hl * $index));
    }

    /**
    *   \brief      Affiche la grille des lignes de factures
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
        $pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);

        $pdf->SetFont('Arial','',9);

        $pdf->SetXY ($this->posxdesc-1, $tab_top+2);
        $pdf->MultiCell(108,2, $outputlangs->transnoentities("Designation"),'','L');

        $pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxtva-1, $tab_top+2);
        $pdf->MultiCell($this->posxup-$this->posxtva-1,2, $outputlangs->transnoentities("VAT"),'','C');

        $pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxup-1, $tab_top+2);
        $pdf->MultiCell(18,2, $outputlangs->transnoentities("PriceUHT"),'','C');

        $pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
        $pdf->SetXY ($this->posxqty-1, $tab_top+2);
        $pdf->MultiCell($this->posxdiscount-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');

        $pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
        if ($this->atleastonediscount)
        {
            $pdf->SetXY ($this->posxdiscount-1, $tab_top+2);
            $pdf->MultiCell(14,2, $outputlangs->transnoentities("ReductionShort"),'','C');
        }

        if ($this->atleastonediscount)
        {
            $pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
        }
        $pdf->SetXY ($this->postotalht-1, $tab_top+2);
        $pdf->MultiCell(28,2, $outputlangs->transnoentities("TotalHT"),'','C');

    }

    /**
     *   	\brief      Show header of page
     *      \param      pdf             Object PDF
     *      \param      object          Object invoice
     *      \param      showadress      0=no, 1=yes
     *      \param      outputlang		Object lang for output
     */
    function _pagehead(&$pdf, $object, $showadress=1, $outputlangs)
    {
		global $conf,$langs;

        $outputlangs->load("main");
        $outputlangs->load("bills");
        $outputlangs->load("propal");
        $outputlangs->load("companies");

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->FACTURE_DRAFT_WATERMARK)) )		
		{
			$watermark_angle=atan($this->page_hauteur/$this->page_largeur);
			$watermark_x=5;
			$watermark_y=$this->page_hauteur-25; //Set to $this->page_hauteur-50 or less if problems
			$watermark_width=$this->page_hauteur;
			$pdf->SetFont('Arial','B',50);
			$pdf->SetTextColor(255,192,203);
			//rotate
			$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
			//print watermark
			$pdf->SetXY($watermark_x,$watermark_y);
			$pdf->Cell($watermark_width,25,clean_html($conf->global->FACTURE_DRAFT_WATERMARK),0,2,"C",0);
			//antirotate
			$pdf->_out('Q');
		}
		//Print content
		
        $pdf->SetTextColor(0,0,60);
        $pdf->SetFont('Arial','B',13);

        $posy=$this->marge_haute;

        $pdf->SetXY($this->marge_gauche,$posy);

		// Logo
        $logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo;
        if ($this->emetteur->logo)
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
                $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
            }
        }
        else if (defined("FAC_PDF_INTITULE"))
        {
            $pdf->MultiCell(100, 4, FAC_PDF_INTITULE, 0, 'L');
        }

        $pdf->SetFont('Arial','B',13);
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $title=$outputlangs->transnoentities("Invoice");
        if ($object->type == 1) $title=$outputlangs->transnoentities("InvoiceReplacement");
        if ($object->type == 2) $title=$outputlangs->transnoentities("InvoiceAvoir");
        $pdf->MultiCell(100, 4, $title, '' , 'R');

        $pdf->SetFont('Arial','B',12);

        $posy+=6;
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

        $posy+=1;
        $pdf->SetFont('Arial','',10);

        $facidnext=$object->getIdReplacingInvoice('validated');
		if ($object->type == 0 && $facidnext)
	    {
			$objectreplacing=new Facture($this->db);
			$objectreplacing->fetch($facidnext);

	        $posy+=5;
	        $pdf->SetXY(100,$posy);
	        $pdf->SetTextColor(0,0,60);
	        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($objectreplacing->ref), '', 'R');
		}
        if ($object->type == 1)
	    {
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

	        $posy+=5;
	        $pdf->SetXY(100,$posy);
	        $pdf->SetTextColor(0,0,60);
	        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}
        if ($object->type == 2)
	    {
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

	        $posy+=5;
	        $pdf->SetXY(100,$posy);
	        $pdf->SetTextColor(0,0,60);
	        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}
		
        $posy+=5;
        $pdf->SetXY(100,$posy);
        $pdf->SetTextColor(0,0,60);
        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("DateInvoice")." : " . dolibarr_print_date($object->date,"day",false,$outpulangs), '', 'R');

        if ($object->type != 2)
        {
	        $posy+=5;
	        $pdf->SetXY(100,$posy);
	        $pdf->SetTextColor(0,0,60);
	        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("DateEcheance")." : " . dolibarr_print_date($object->date_lim_reglement,"day",false,$outputlangs), '', 'R');
		}
		
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
            if (defined("FAC_PDF_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM) $pdf->MultiCell(80, 4, FAC_PDF_SOCIETE_NOM, 0, 'L');	// deprecated
            else $pdf->MultiCell(80, 4, $this->emetteur->nom, 0, 'L');

            // Caracteristiques emetteur
            $carac_emetteur = '';
            if (defined("FAC_PDF_ADRESSE") && FAC_PDF_ADRESSE) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).FAC_PDF_ADRESSE;	// deprecated
            else {
                $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->adresse);
                $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->cp).' '.$outputlangs->convToOutputCharset($this->emetteur->ville);
            }
            $carac_emetteur .= "\n";
            // Tel
            if (defined("FAC_PDF_TEL") && FAC_PDF_TEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".FAC_PDF_TEL;
            elseif ($this->emetteur->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($this->emetteur->tel);
            // Fax
            if (defined("FAC_PDF_FAX") && FAC_PDF_FAX) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".FAC_PDF_FAX;
            elseif ($this->emetteur->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($this->emetteur->fax);
            // EMail
    		if (defined("FAC_PDF_MEL") && FAC_PDF_MEL) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".FAC_PDF_MEL;
            elseif ($this->emetteur->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($this->emetteur->email);
            // Web
    		if (defined("FAC_PDF_WWW") && FAC_PDF_WWW) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".FAC_PDF_WWW;
            elseif ($this->emetteur->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($this->emetteur->url);

            $pdf->SetFont('Arial','',9);
            $pdf->SetXY($this->marge_gauche+2,$posy+8);
            $pdf->MultiCell(80,4, $carac_emetteur);

            // Client destinataire
            $posy=42;
            $pdf->SetTextColor(0,0,0);
            $pdf->SetFont('Arial','',8);
            $pdf->SetXY(102,$posy-5);
            $pdf->MultiCell(80,5, $outputlangs->transnoentities("BillTo").":");
            $object->fetch_client();

            // Cadre client destinataire
            $pdf->rect(100, $posy, 100, $hautcadre);

			// If BILLING contact defined on invoice, we use it
			$usecontact=false;
			if ($conf->global->FACTURE_USE_BILL_CONTACT_AS_RECIPIENT)
			{
				$arrayidcontact=$object->getIdContact('external','BILLING');
				if (sizeof($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}
			}
			if ($usecontact)
			{
				// Nom societe
				$pdf->SetXY(102,$posy+3);
				$pdf->SetFont('Arial','B',11);
				// On peut utiliser le nom de la societe du contact facturation
				if ($conf->global->FACTURE_USE_COMPANY_NAME_OF_BILL_CONTACT)
				{
					$socname = $object->contact->socname;
				}
				else
				{
					$socname = $object->client->nom;
				}
				$pdf->MultiCell(96,4, $outputlangs->convToOutputCharset($socname), 0, 'L');
				
				// Nom client
				$carac_client = "\n".$object->contact->getFullName($outputlangs,1,1);

				// Caracteristiques client
				$carac_client.="\n".$outputlangs->convToOutputCharset($object->contact->adresse);
				$carac_client.="\n".$outputlangs->convToOutputCharset($object->contact->cp) . " " . $outputlangs->convToOutputCharset($object->contact->ville)."\n";
				//Pays si different de l'emetteur
				if ($this->emetteur->pays_code != $object->contact->pays_code)
				{
					$carac_client.=dol_entity_decode($object->contact->pays)."\n";
				}
			}
			else
			{
				// Nom client
				$pdf->SetXY(102,$posy+3);
				$pdf->SetFont('Arial','B',11);
				$pdf->MultiCell(96,4, $outputlangs->convToOutputCharset($object->client->nom), 0, 'L');
				
				// Nom du contact facturation si c'est une societe
				$arrayidcontact = $object->getIdContact('external','BILLING');
				if (sizeof($arrayidcontact) > 0)
				{
					$object->fetch_contact($arrayidcontact[0]);
					// On verifie si c'est une societe ou un particulier
					if( !preg_match('#'.$object->contact->getFullName($outputlangs,1).'#isU',$object->client->nom) )
					{
						$carac_client .= "\n".$object->contact->getFullName($outputlangs,1,1);
					}
				}

				// Caracteristiques client
				$carac_client.="\n".$outputlangs->convToOutputCharset($object->client->adresse);
				$carac_client.="\n".$outputlangs->convToOutputCharset($object->client->cp) . " " . $outputlangs->convToOutputCharset($object->client->ville)."\n";

					//Pays si different de l'emetteur
					if ($this->emetteur->pays_code != $object->client->pays_code)
					{
						$carac_client.=dol_entity_decode($object->client->pays)."\n";
					}
			}
			// Numero TVA intracom
			if ($object->client->tva_intra) $carac_client.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($object->client->tva_intra);
		  	$pdf->SetFont('Arial','',9);
			$posy=$pdf->GetY()-9; //Auto Y coord readjust for multiline name
			$pdf->SetXY(102,$posy+6);
			$pdf->MultiCell(86,4, $carac_client);
		}
	}

    /**
     *   	\brief      Show footer of page
     *   	\param      pdf     		Object PDF
     * 		\param		object			Object invoice
     *      \param      outputlang		Object lang for output
     * 		\remarks	Need this->emetteur object
     */
    function _pagefoot(&$pdf,$object,$outputlangs)
    {
		return pdf_pagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
    }

}

?>
