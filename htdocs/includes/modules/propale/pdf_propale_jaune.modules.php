<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 * 	\file       htdocs/includes/modules/propale/pdf_propale_jaune.modules.php
 *	\ingroup    propale
 *	\brief      Fichier de la classe permettant de generer les propales au modele Jaune
 *	\version    $Id: pdf_propale_jaune.modules.php,v 1.117 2011/07/31 23:28:16 eldy Exp $
 */

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/modules_propale.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/lib/pdf.lib.php');


/**
 * 	    \class      pdf_propale_jaune
 *		\brief      Classe permettant de generer les propales au modele Jaune
 */
class pdf_propale_jaune extends ModelePDFPropales
{
	var $emetteur;	// Objet societe qui emet


	/**
	 * 		\brief  Constructeur
	 *		\param	db		Database access handler
	 */
	function pdf_propale_jaune($db=0)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");

		$this->db = $db;
		$this->name = "jaune";
		$this->description = $langs->trans('DocModelJauneDescription');

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		$this->error = "";

		// Recupere emetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si on ne trouve pas
	}


	/**
     *  Function to build pdf onto disk
     *  @param      object          Id of object to generate
     *  @param      outputlangs     Lang output object
     *  @param      srctemplatepath Full path of source filename for generator using a template file
     *  @param      hidedetails     Do not show line details
     *  @param      hidedesc        Do not show desc
     *  @param      hideref         Do not show ref
     *  @return     int             1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		$sav_charset_output=$outputlangs->charset_output;
		if (!class_exists('TCPDF')) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("products");

		if ($conf->propale->dir_output)
		{
			$object->fetch_thirdparty();
			$deja_regle = "";

			// Definition de $dir et $file
			if ($object->specimen)
			{
				$dir = $conf->propale->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->propale->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
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
                $pdf=pdf_getInstance($this->format);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("CommercialProposal"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("CommercialProposal"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 100;
				$tab_height = 130;

				$pdf->SetFillColor(242,239,119);
				$pdf->SetXY (10, $tab_top + 10 );

				$iniY = $tab_top + 12;
				$curY = $tab_top + 12;
				$nexY = $tab_top + 12;
				$nblignes = sizeof($object->lines);

				// Loop on each lines
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;

                    $pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page

                    // Description de la ligne produit
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,102,4,30,$curY,1,$hidedesc);

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut
					$nexY = $pdf->GetY();

					$ref = pdf_getlineref($object, $i, $outputlangs);
					$pdf->SetXY (10, $curY );
					$pdf->MultiCell(20, 4, $ref, 0, 'L', 0);

					$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY (132, $curY );
					$pdf->MultiCell(12, 4, $vat_rate, 0, 'R');

					$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY (144, $curY );
					$pdf->MultiCell(10, 4, $qty, 0, 'R', 0);

					$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY (154, $curY );
					$pdf->MultiCell(22, 4, $up_excl_tax, 0, 'R', 0);

					$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY (176, $curY );
					$pdf->MultiCell(24, 4, $total_excl_tax, 0, 'R', 0);

					//$pdf->line(10, $curY, 200, $curY );

					$nexY+=2;    // Passe espace entre les lignes

					// cherche nombre de lignes a venir pour savoir si place suffisante
					if ($i < ($nblignes - 1))	// If it's not last line
					{
						//on recupere la description du produit suivant
						$follow_descproduitservice = $outputlangs->convToOutputCharset($object->lines[$i+1]->desc);
						//on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
						$nblineFollowDesc = (dol_nboflines_bis($follow_descproduitservice,52,$outputlangs->charset_output)*4);
					}
					else	// If it's last line
					{
						$nblineFollowDesc = 0;
					}

					// test si besoin nouvelle page
					if (($nexY+$nblineFollowDesc) > ($tab_top+$tab_height) && $i < ($nblignes - 1))
					{
						$this->_pagefoot($pdf,$object,$outputlangs);

						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $object, 0, $outputlangs);
						$pdf->SetFont('','', $default_font_size - 1);
						$pdf->MultiCell(0, 3, '');		// Set interline to 3
						$pdf->SetTextColor(0,0,0);

						$nexY = $tab_top + 8;
					}
				}

				$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

				$bottomlasttab=$tab_top + $tab_height + 1;

				// Affiche zone infos
				$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

				$tab2_top = $tab_top + $tab_height;
				$tab2_lh = 4;

				$pdf->SetFont('','', $default_font_size);

				$pdf->SetXY (132, $tab2_top + 0);
				$pdf->MultiCell(42, $tab2_lh, $outputlangs->transnoentities("TotalHT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + $tab2_lh);
				$pdf->MultiCell(42, $tab2_lh, $outputlangs->transnoentities("TotalVAT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(42, $tab2_lh, $outputlangs->transnoentities("TotalTTC"), 1, 'R', 1);

				$pdf->SetXY (174, $tab2_top + 0);
				$pdf->MultiCell(26, $tab2_lh, price($object->total_ht), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + $tab2_lh);
				$pdf->MultiCell(26, $tab2_lh, price($object->total_tva), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(26, $tab2_lh, price($object->total_ttc), 1, 'R', 1);

				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');
				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
		}
	}

	/**
	 *	\brief      Affiche infos divers
	 *	\param      pdf             Objet PDF
	 *	\param      object          Objet commande
	 *	\param		posy			Position depart
	 *	\param		outputlangs		Objet langs
	 *	\return     y               Position pour suite
	 */
	function _tableau_info(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('','', $default_font_size - 1);

        // If France, show VAT mention if not applicable
		if ($this->emetteur->pays_code == 'FR' && $this->franchise == 1)
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		// Show availability conditions
		if ($object->type != 2 && ($object->availability_code || $object->availability))
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("AvailabilityPeriod").':';
			$pdf->MultiCell(80, 4, $titre, 0, 'L');
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY(82, $posy);
			$lib_availability=$outputlangs->transnoentities("AvailabilityPeriod".$object->availability_code)!=('AvailabilityPeriod'.$object->availability_code)?$outputlangs->transnoentities("AvailabilityPeriod".$object->availability_code):$outputlangs->convToOutputCharset($object->availability);
			$lib_availability=str_replace('\n',"\n",$lib_availability);
			$pdf->MultiCell(80, 4, $lib_availability,0,'L');

			$posy=$pdf->GetY()+1;
		}

        // Show payments conditions
		if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement))
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("PaymentConditions").':';
			$pdf->MultiCell(80, 4, $titre, 0, 'L');

			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY(52, $posy);
			$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
			$pdf->MultiCell(80, 4, $lib_condition_paiement,0,'L');

			$posy=$pdf->GetY()+3;
		}

		if ($object->type != 2)
		{
	        // Check a payment mode is defined
	        /* Not used with orders
	        if (empty($object->mode_reglement_code)
	        	&& ! $conf->global->FACTURE_CHQ_NUMBER
	        	&& ! $conf->global->FACTURE_RIB_NUMBER)
			{
	            $pdf->SetXY($this->marge_gauche, $posy);
	            $pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
	            $pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
	            $pdf->SetTextColor(0,0,0);

	            $posy=$pdf->GetY()+1;
	        }*/

	      	// Show payment mode
	        if ($object->mode_reglement_code
	        	 && $object->mode_reglement_code != 'CHQ'
	           	 && $object->mode_reglement_code != 'VIR')
	           	 {
		            $pdf->SetFont('','B', $default_font_size - 2);
		            $pdf->SetXY($this->marge_gauche, $posy);
		            $titre = $outputlangs->transnoentities("PaymentMode").':';
		            $pdf->MultiCell(80, 5, $titre, 0, 'L');

		            $pdf->SetFont('','', $default_font_size - 2);
		            $pdf->SetXY(50, $posy);
		            //print "xxx".$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code);exit;
		            $lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);
		            $pdf->MultiCell(80, 5, $lib_mode_reg,0,'L');

		            $posy=$pdf->GetY()+2;
	           	 }

			// Show payment mode CHQ
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
		                $pdf->SetFont('','B', $default_font_size - 2);
		                $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio).':',0,'L',0);
			            $posy=$pdf->GetY()+1;

		                $pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','', $default_font_size - 2);
		                $pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($account->adresse_proprio), 0, 'L', 0);
			            $posy=$pdf->GetY()+2;
		            }
		            if ($conf->global->FACTURE_CHQ_NUMBER == -1)
		            {
		                $pdf->SetXY($this->marge_gauche, $posy);
		                $pdf->SetFont('','B',$default_font_size - 2);
		                $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedToShort').' '.$outputlangs->convToOutputCharset($this->emetteur->name).' '.$outputlangs->transnoentities('SendTo').':',0,'L',0);
			            $posy=$pdf->GetY()+1;

			            $pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','', $default_font_size - 2);
		                $pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
			            $posy=$pdf->GetY()+2;
		            }
		        }
			}

	        // If payment mode not forced or forced to VIR, show payment with BAN
	        /* Not enough space
	        if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR')
	        {
		        if (! empty($conf->global->FACTURE_RIB_NUMBER))
		        {
	                $account = new Account($this->db);
	                $account->fetch($conf->global->FACTURE_RIB_NUMBER);

	                $curx=$this->marge_gauche;
	                $cury=$posy;

	                $posy=pdf_bank($pdf,$outputlangs,$curx,$cury,$account);

	                $posy+=2;
		        }
			}
			*/
		}

		return $posy;
	}

	/**
	 * Enter description here...
	 *
	 * @param $pdf
	 * @param $tab_top
	 * @param $tab_height
	 * @param $nexY
	 * @param $outputlangs
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $langs,$conf;
		$langs->load("main");
		$langs->load("bills");

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Montants exprimes en     (en tab_top - 1)
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','', $default_font_size - 2);
		$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->monnaie));
		$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-4);
		$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

		$pdf->SetFont('','', $default_font_size - 1);

		$haut=6;

		$pdf->SetXY(10,$tab_top);
		$pdf->MultiCell(20,$haut,$outputlangs->transnoentities("Ref"),0,'L',1);

		$pdf->SetXY(30,$tab_top);
		$pdf->MultiCell(102,$haut,$outputlangs->transnoentities("Designation"),0,'L',1);

		$pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
		$pdf->SetXY(132,$tab_top);
		$pdf->MultiCell(12, $haut,$outputlangs->transnoentities("VAT"),0,'C',1);

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
		$pdf->SetXY(144,$tab_top);
		$pdf->MultiCell(10,$haut,$outputlangs->transnoentities("Qty"),0,'C',1);

		$pdf->line(154, $tab_top, 154, $tab_top + $tab_height);
		$pdf->SetXY(154,$tab_top);
		$pdf->MultiCell(22,$haut,$outputlangs->transnoentities("PriceU"),0,'R',1);

		$pdf->line(176, $tab_top, 176, $tab_top + $tab_height);
		$pdf->SetXY(176,$tab_top);
		$pdf->MultiCell(24,$haut,$outputlangs->transnoentities("Total"),0,'R',1);

		$pdf->Rect(10, $tab_top, 190, $tab_height);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','', $default_font_size);
	}


    /**
     *      Show header of document
     *      @param      pdf             Object PDF
     *      @param      object          Object commercial proposal
     *      @param      showaddress     0=no, 1=yes
     *      @param      outputlangs     Object lang for output
     */
	function _pagehead(&$pdf, $object, $showadress=1, $outputlangs)
	{
		global $conf,$langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && ! empty($conf->global->PROPALE_DRAFT_WATERMARK))
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->PROPALE_DRAFT_WATERMARK);
		}

		$posy=42;

		$pdf->SetXY($this->marge_gauche+2,$posy);

		// Sender name
		$pdf->SetTextColor(0,0,00);
		$pdf->SetFont('','B', $default_font_size);
		$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');

		// Sender properties
		$carac_emetteur='';
	 	// Add internal contact of proposal if defined
		$arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
	 	if (sizeof($arrayidcontact) > 0)
	 	{
	 		$object->fetch_user($arrayidcontact[0]);
	 		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
	 	}

	 	$carac_emetteur .= pdf_build_address($outputlangs,$this->emetteur);

		$pdf->SetFont('','', $default_font_size - 1);
		$pdf->SetXY($this->marge_gauche+2,$posy+4);
		$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');


		$pdf->rect(10, 40, 80, 40);

		$pdf->SetXY(10,5);
		$pdf->SetFont('','B', $default_font_size + 6);
		$pdf->SetTextColor(0,0,200);
		$pdf->MultiCell(200, 20, $outputlangs->transnoentities("CommercialProposal"), '' , 'C');

		// Cadre client destinataire
		$pdf->rect(100, 40, 100, 40);

		$pdf->SetTextColor(200,0,0);
		$pdf->SetFont('','B', $default_font_size + 2);

		$pdf->rect(10, 90, 100, 10);
		$pdf->rect(110, 90, 90, 10);

		$pdf->SetXY(10,90);
		$pdf->MultiCell(110, 10, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref), 0, 'L');
		$pdf->SetXY(110,90);
		$pdf->MultiCell(100, 10, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date,'day',false,$outputlangs,true), 0, 'L');

		$posy=15;
		$pdf->SetFont('','', $default_font_size);

		$posy+=5;
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		if ($object->ref_client)
		{
			$posy+=5;
			$pdf->SetXY(100,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset($object->ref_client), '', 'R');
		}

		$posy+=5;
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("DateEndPropal")." : " . dol_print_date($object->fin_validite,"day",false,$outputlangs,true), '', 'R');

		if ($object->client->code_client)
		{
			$posy+=5;
			$pdf->SetXY(100,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}

		$posy=39;

		$pdf->SetTextColor(0,0,0);

		// If CUSTOMER contact defined, we use it
		$usecontact=false;
		$arrayidcontact=$object->getIdContact('external','CUSTOMER');
		if (sizeof($arrayidcontact) > 0)
		{
			$usecontact=true;
			$result=$object->fetch_contact($arrayidcontact[0]);
		}

		// Recipient name
		if (! empty($usecontact))
		{
			// On peut utiliser le nom de la societe du contact
			if ($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) $socname = $object->contact->socname;
			else $socname = $object->client->nom;
			$carac_client_name=$outputlangs->convToOutputCharset($socname);
		}
		else
		{
			$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);
		}

		$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,$object->contact,$usecontact,'target');

		// Show recipient
		$pdf->SetXY(102,$posy+3);
		$pdf->SetFont('','B', $default_font_size);
		$pdf->MultiCell(96,4, $carac_client_name, 0, 'L');

		// Show address
		$pdf->SetFont('','', $default_font_size - 1);
		$pdf->SetXY(102,$posy+8);
		$pdf->MultiCell(86,4, $carac_client, 0, 'L');
	}

	/**
	 *   	\brief      Show footer of page
	 *   	\param      pdf     		PDF factory
	 * 		\param		object			Object invoice
	 *      \param      outputlangs		Object lang for output
	 * 		\remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf,$object,$outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'PROPALE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
	}

}
?>
