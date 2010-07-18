<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008      Raphael Bertrand (Resultic)       <raphael.bertrand@resultic.fr>
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
 * 	\file       htdocs/includes/modules/propale/pdf_propale_jaune.modules.php
 *	\ingroup    propale
 *	\brief      Fichier de la classe permettant de generer les propales au modele Jaune
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT ."/includes/modules/propale/modules_propale.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


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

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si on trouve pas
	}


	/**
	 *	\brief      Fonction generant la propale sur le disque
	 *	\param	    propale			Objet propal
	 *	\param		outputlangs		Lang object for output language
	 *	\return	    int     		1=ok, 0=ko
	 */
	function write_file($propale,$outputlangs)
	{
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because FPDF expect text to be encoded in ISO
		$sav_charset_output=$outputlangs->charset_output;
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("products");

		if ($conf->propale->dir_output)
		{
			// D�finition de l'objet $propal (pour compatibilite ascendante)
			if (! is_object($propale))
			{
				$id = $propale;
				$propale = new Propal($this->db,"",$id);
				$ret=$propale->fetch($id);
			}
			$propale->fetch_client();
			$deja_regle = "";

			// Definition de $dir et $file
			if ($propale->specimen)
			{
				$dir = $conf->propale->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$propref = dol_sanitizeFileName($propale->ref);
				$dir = $conf->propale->dir_output . "/" . $propref;
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
					$pdfownerpass = NULL; // Mot de passe du propri�taire, cr�� al�atoirement si pas d�fini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}
				else
				{
					$pdf=new FPDI('P','mm',$this->format);
				}

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($propale->ref));
				$pdf->SetSubject($outputlangs->transnoentities("CommercialProposal"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($propale->ref)." ".$outputlangs->transnoentities("CommercialProposal"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $propale, 1, $outputlangs);
				$pdf->SetFont('Arial','', 9);
				$pdf->MultiCell(0, 4, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 100;
				$tab_height = 150;

				$pdf->SetFillColor(242,239,119);
				$pdf->SetXY (10, $tab_top + 10 );

				$iniY = $tab_top + 12;
				$curY = $tab_top + 12;
				$nexY = $tab_top + 12;
				$nblignes = sizeof($propale->lignes);

				// Loop on each lines
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;

					// Description de la ligne produit
					$libelleproduitservice=pdf_getlinedesc($propale->lignes[$i],$outputlangs);
					$pdf->SetFont('Arial','', 9);   // Dans boucle pour gerer multi-page

					$pdf->writeHTMLCell(102, 4, 30, $curY, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 1);

					$pdf->SetFont('Arial','', 9);   // On repositionne la police par d�faut
					$nexY = $pdf->GetY();

					$ref=dol_htmlentitiesbr($propale->lignes[$i]->ref);

					$pdf->SetXY (10, $curY );
					$pdf->MultiCell(20, 4, $outputlangs->convToOutputCharset($ref), 0, 'L', 0);

					$pdf->SetXY (132, $curY );
					$pdf->MultiCell(12, 4, vatrate($propale->lignes[$i]->tva_tx,0,$propale->lignes[$i]->info_bits), 0, 'R');

					$pdf->SetXY (144, $curY );
					$pdf->MultiCell(10, 4, price($propale->lignes[$i]->qty), 0, 'R', 0);

					$pdf->SetXY (154, $curY );
					$pdf->MultiCell(22, 4, price($propale->lignes[$i]->price), 0, 'R', 0);

					$pdf->SetXY (176, $curY );
					$pdf->MultiCell(24, 4, price($propale->lignes[$i]->total_ht), 0, 'R', 0);

					//$pdf->line(10, $curY, 200, $curY );

					$nexY+=2;    // Passe espace entre les lignes

					// cherche nombre de lignes a venir pour savoir si place suffisante
					if ($i < ($nblignes - 1))	// If it's not last line
					{
						//on recupere la description du produit suivant
						$follow_descproduitservice = $outputlangs->convToOutputCharset($propale->lignes[$i+1]->desc);
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
						$this->_pagefoot($pdf,$propale,$outputlangs);

						$this->_tableau($pdf, $tab_top, $tab_height, $nexY);

						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $propale, 0, $outputlangs);
						$pdf->SetFont('Arial','', 9);
						$pdf->MultiCell(0, 4, '', 0, 'J');		// Set interline to 3
						$pdf->SetTextColor(0,0,0);

						$nexY = $tab_top + 8;
					}
				}

				$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

				$bottomlasttab=$tab_top + $tab_height + 1;

				// Affiche zone infos
				$this->_tableau_info($pdf, $propale, $bottomlasttab, $outputlangs);

				$tab2_top = 254;
				$tab2_lh = 7;
				$tab2_height = $tab2_lh * 3;

				$pdf->SetFont('Arial','', 10);

				$pdf->Rect(132, $tab2_top, 68, $tab2_height);

				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*3), 200, $tab2_top + $tab2_height - ($tab2_lh*3) );
				$pdf->line(132, $tab2_top + $tab2_height - ($tab2_lh*2), 200, $tab2_top + $tab2_height - ($tab2_lh*2) );
				$pdf->line(132, $tab2_top + $tab2_height - $tab2_lh, 200, $tab2_top + $tab2_height - $tab2_lh );

				$pdf->line(174, $tab2_top, 174, $tab2_top + $tab2_height);

				$pdf->SetXY (132, $tab2_top + 0);
				$pdf->MultiCell(42, $tab2_lh, $outputlangs->transnoentities("TotalHT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + $tab2_lh);
				$pdf->MultiCell(42, $tab2_lh, $outputlangs->transnoentities("TotalVAT"), 0, 'R', 0);

				$pdf->SetXY (132, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(42, $tab2_lh, $outputlangs->transnoentities("TotalTTC"), 1, 'R', 1);

				$pdf->SetXY (174, $tab2_top + 0);
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_ht), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + $tab2_lh);
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_tva), 0, 'R', 0);

				$pdf->SetXY (174, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(26, $tab2_lh, price($propale->total_ttc), 1, 'R', 1);

				// Pied de page
				$this->_pagefoot($pdf,$propale,$outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);
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

		$pdf->SetFont('Arial','', 9);

        // If France, show VAT mention if not applicable
		if ($this->emetteur->pays_code == 'FR' && $this->franchise == 1)
		{
			$pdf->SetFont('Arial','B',8);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

        // Show payments conditions
		if ($object->cond_reglement_code || $object->cond_reglement)
		{
			$pdf->SetFont('Arial','B',8);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("PaymentConditions").':';
			$pdf->MultiCell(80, 4, $titre, 0, 'L');

			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(50, $posy);
			$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
			$pdf->MultiCell(80, 4, $lib_condition_paiement,0,'L');

			$posy=$pdf->GetY()+3;
		}

        // Check a payment mode is defined
        /* Not used with orders
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
        }*/

      	// Show payment mode
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
	                $pdf->SetFont('Arial','B',8);
	                $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio).':',0,'L',0);
		            $posy=$pdf->GetY()+1;

	                $pdf->SetXY($this->marge_gauche, $posy);
	                $pdf->SetFont('Arial','',8);
	                $pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($account->adresse_proprio), 0, 'L', 0);
		            $posy=$pdf->GetY()+2;
	            }
	            if ($conf->global->FACTURE_CHQ_NUMBER == -1)
	            {
	                $pdf->SetXY($this->marge_gauche, $posy);
	                $pdf->SetFont('Arial','B',8);
	                $pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedToShort').' '.$outputlangs->convToOutputCharset($this->emetteur->nom).' '.$outputlangs->transnoentities('SendTo').':',0,'L',0);
		            $posy=$pdf->GetY()+1;

		            $pdf->SetXY($this->marge_gauche, $posy);
	                $pdf->SetFont('Arial','',8);
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

		return $posy;
	}

	/**
	 * Enter description here...
	 *
	 * @param unknown_type $pdf
	 * @param unknown_type $tab_top
	 * @param unknown_type $tab_height
	 * @param unknown_type $nexY
	 * @param unknown_type $outputlangs
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $langs,$conf;
		$langs->load("main");
		$langs->load("bills");

		$pdf->SetFont('Arial','',11);

		$pdf->SetXY(10,$tab_top);
		$pdf->MultiCell(20,10,$outputlangs->transnoentities("Ref"),0,'C',1);

		$pdf->SetXY(30,$tab_top);
		$pdf->MultiCell(102,10,$outputlangs->transnoentities("Designation"),0,'L',1);

		$pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
		$pdf->SetXY(132,$tab_top);
		$pdf->MultiCell(12, 10,$outputlangs->transnoentities("VAT"),0,'C',1);

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
		$pdf->SetXY(144,$tab_top);
		$pdf->MultiCell(10,10,$outputlangs->transnoentities("Qty"),0,'C',1);

		$pdf->line(154, $tab_top, 154, $tab_top + $tab_height);
		$pdf->SetXY(154,$tab_top);
		$pdf->MultiCell(22,10,$outputlangs->transnoentities("PriceU"),0,'R',1);

		$pdf->line(176, $tab_top, 176, $tab_top + $tab_height);
		$pdf->SetXY(176,$tab_top);
		$pdf->MultiCell(24,10,$outputlangs->transnoentities("Total"),0,'R',1);

		$pdf->Rect(10, $tab_top, 190, $tab_height);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',10);
	}


	function _pagehead(&$pdf, $object, $showadress=1, $outputlangs)
	{
		global $conf,$langs;

		pdf_pagehead($pdf,$outputlangs,$pdf->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && ! empty($conf->global->PROPALE_DRAFT_WATERMARK))
		{
			$watermark_angle=deg2rad(55);
			$watermark_x=5;
			$watermark_y=$this->page_hauteur-50;
			$watermark_width=300;
			$pdf->SetFont('Arial','B',50);
			$pdf->SetTextColor(255,192,203);
			//rotate
			$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
			//print watermark
			$pdf->SetXY($watermark_x,$watermark_y);
			$pdf->Cell($watermark_width,25,$outputlangs->convToOutputCharset($conf->global->PROPALE_DRAFT_WATERMARK),0,2,"C",0);
			//antirotate
			$pdf->_out('Q');
		}

		$posy=42;

		$pdf->SetXY($this->marge_gauche+2,$posy);

		// Sender name
		$pdf->SetTextColor(0,0,00);
		$pdf->SetFont('Arial','B',11);
		$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->nom), 0, 'L');

		// Sender properties
		$carac_emetteur='';
	 	// Add internal contact of proposal if defined
		$arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
	 	if (sizeof($arrayidcontact) > 0)
	 	{
	 		$object->fetch_user($arrayidcontact[0]);
	 		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs));
	 	}

	 	$carac_emetteur .= pdf_build_address($outputlangs,$this->emetteur);

		$pdf->SetFont('Arial','',9);
		$pdf->SetXY($this->marge_gauche+2,$posy+4);
		$pdf->MultiCell(80, 4, $carac_emetteur);


		$pdf->rect(10, 40, 80, 40);

		$pdf->SetXY(10,5);
		$pdf->SetFont('Arial','B',16);
		$pdf->SetTextColor(0,0,200);
		$pdf->MultiCell(200, 20, $outputlangs->transnoentities("CommercialProposal"), '' , 'C');

		// Cadre client destinataire
		$pdf->rect(100, 40, 100, 40);

		$pdf->SetTextColor(200,0,0);
		$pdf->SetFont('Arial','B',12);

		$pdf->rect(10, 90, 100, 10);
		$pdf->rect(110, 90, 90, 10);

		$pdf->SetXY(10,90);
		$pdf->MultiCell(110, 10, $outputlangs->transnoentities("Ref")." : ".$outputlangs->convToOutputCharset($object->ref));
		$pdf->SetXY(110,90);
		$pdf->MultiCell(100, 10, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date,'day',false,$outputlangs,true));

		$posy=15;
		$pdf->SetFont('Arial','',10);

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
		$pdf->SetFont('Arial','B',11);
		$pdf->MultiCell(96,4, $outputlangs->convToOutputCharset($carac_client_name), 0, 'L');

		// Show address
		$pdf->SetFont('Arial','',9);
		$posy=$pdf->GetY()-9; //Auto Y coord readjust for multiline name
		$pdf->SetXY(102,$posy+6);
		$pdf->MultiCell(86,4, $carac_client);
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
