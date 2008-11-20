<?php
/* Copyright (C) 2003-2006 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *	\file       htdocs/includes/modules/facture/pdf_huitre.modules.php
 *	\ingroup    facture
 *	\brief      Fichier de la classe permettant de g�n�rer les factures au mod�le Huitre
 *	\author	    Laurent Destailleur
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 *	\class      pdf_huitre
 *	\brief      Classe permettant de g�n�rer les factures au mod�le Huitre
 */
class pdf_huitre extends ModelePDFFactures
{
	var $emetteur;	// Objet societe qui emet


	/**		\brief  Constructeur
	 *		\param	db		handler acc�s base de donn�e
	 */
	function pdf_huitre($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");
		$langs->load("products");


		$this->db = $db;
		$this->name = "huitre";
		$this->description = $langs->transnoentities('PDFHuitreDescription');

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
		$this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 0;                 // Gere choix mode reglement FACTURE_CHQ_NUMBER, FACTURE_RIB_NUMBER
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 0;                // Affiche si il y a eu escompte
		$this->option_credit_note = 0;             // Gere les avoirs
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini
	}


	/**
	 *		\brief      Fonction g�n�rant la facture sur le disque
	 *		\param	    fac				Objet facture � g�n�rer (ou id si ancienne methode)
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
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");

		$outputlangs->setPhpLang();

		if ($conf->facture->dir_output)
		{
			// D�finition de l'objet $fac (pour compatibilite ascendante)
			if (! is_object($fac))
			{
				$id = $fac;
				$fac = new Facture($this->db,"",$id);
				$ret=$fac->fetch($id);
			}

			// D�finition de $dir et $file
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
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					$langs->setPhpLang();	// On restaure langue session
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Protection et encryption du pdf
				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdf=new FPDI_Protection('P','mm','A4');
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
				$pdf->AddPage();

				$this->_pagehead($pdf, $fac, $outputlangs);

				$pdf->SetTitle($fac->ref);
				$pdf->SetSubject($langs->transnoentities("Bill"));
				$pdf->SetCreator("Dolibarr (By ADYTEK)".DOL_VERSION);
				$pdf->SetAuthor($user->fullname);
				$pdf->SetMargins(10, 10, 10);
				$pdf->SetAutoPageBreak(1,0);
				$tab_top = 100;
				$tab_height = 110;

				$pdf->SetFillColor(242,239,119);

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

					$pdf->SetXY (11, $curY );
					$pdf->MultiCell(118, 5, $outputlangs->convToOutputCharset($fac->lignes[$i]->desc), 0, 'J');

					$nexY = $pdf->GetY();

					$pdf->SetXY (133, $curY);
					$pdf->MultiCell(10, 5, $fac->lignes[$i]->tva_tx, 0, 'C');

					$pdf->SetXY (145, $curY);
					$pdf->MultiCell(10, 5, $fac->lignes[$i]->qty, 0, 'C');

					$pdf->SetXY (156, $curY);
					$pdf->MultiCell(18, 5, price($fac->lignes[$i]->price), 0, 'R', 0);

					$pdf->SetXY (174, $curY);
					$total = price($fac->lignes[$i]->total_ht);
					$pdf->MultiCell(26, 5, $total, 0, 'R', 0);

					if ($nexY > 200 && $i < $nblignes - 1)
					{
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
						$pdf->AddPage();
						$nexY = $iniY;
						$this->_pagehead($pdf, $fac, $outputlangs);
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('Arial','', 10);
					}

				}
				$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

				$this->_tableau_tot($pdf, $fac, $outputlangs);

				$this->_tableau_compl($pdf, $fac, $outputlangs);

				/*
				 *
				 */
				if (defined("FACTURE_RIB_NUMBER"))
				{
					if (FACTURE_RIB_NUMBER > 0)
					{
						$account = new Account($this->db);
						$account->fetch(FACTURE_RIB_NUMBER);

						$pdf->SetXY (10, 40);
						$pdf->SetFont('Arial','U',8);
						$pdf->MultiCell(40, 4, $outputlangs->transnoentities("BankDetails"), 0, 'L', 0);
						$pdf->SetFont('Arial','',8);
						$pdf->MultiCell(40, 4, $outputlangs->transnoentities("BankCode").' : ' . $outputlangs->convToOutputCharset($account->code_banque), 0, 'L', 0);
						$pdf->MultiCell(40, 4, $outputlangs->transnoentities("DeskCode").' : ' . $outputlangs->convToOutputCharset($account->code_guichet), 0, 'L', 0);
						$pdf->MultiCell(50, 4, $outputlangs->transnoentities("BankAccountNumber").' : ' . $outputlangs->convToOutputCharset($account->number), 0, 'L', 0);
						$pdf->MultiCell(40, 4, $outputlangs->transnoentities("BankAccountNumberKey").' : ' . $outputlangs->convToOutputCharset($account->cle_rib), 0, 'L', 0);
						$pdf->MultiCell(40, 4, $outputlangs->transnoentities("Residence").' : ' . $outputlangs->convToOutputCharset($account->domiciliation), 0, 'L', 0);
						$pdf->MultiCell(40, 4, $outputlangs->transnoentities("IbanPrefix").' : ' . $outputlangs->convToOutputCharset($account->iban_prefix), 0, 'L', 0);
						$pdf->MultiCell(40, 4, $outputlangs->transnoentities("BIC").' : ' . $outputlangs->convToOutputCharset($account->bic), 0, 'L', 0);
					}
				}

				/*
				 *
				 *
				 */

				if ( $fac->note_public)
				{
					$pdf->SetFont('Arial','',7);
					$pdf->SetXY(10, 211);
					$note = $outputlangs->transnoentities("Note").' : '.$outputlangs->convToOutputCharset($fac->note_public);
					$pdf->MultiCell(110, 3, $note, 0, 'J');
				}

				$pdf->SetXY(10, 225);

		        // Show payments conditions
		        if ($fac->type != 2 && ($fac->cond_reglement_code || $fac->cond_reglement))
		        {
					$titre = $outputlangs->transnoentities("PaymentConditions").' : ';
					$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$fac->cond_reglement_code)!=('PaymentCondition'.$fac->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$fac->cond_reglement_code):$fac->cond_reglement;
					$titre.=$lib_condition_paiement;
					$pdf->MultiCell(190, 5, $titre, 0, 'J');
		        }
		        
				$this->_pagefoot($pdf, $fac, $outputlangs);
				$pdf->AliasNbPages();
				//----
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFillColor(242,239,119);

				$pdf->SetLineWidth(0.5);


				$pdf->Close();

				$pdf->Output($file);
				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				$langs->setPhpLang();	// On restaure langue session
				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				$langs->setPhpLang();	// On restaure langue session
				return 0;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","FAC_OUTPUTDIR");
			$langs->setPhpLang();	// On restaure langue session
			return 0;
		}
		$this->error=$langs->transnoentities("ErrorUnknown");
		$langs->setPhpLang();	// On restaure langue session
		return 0;   // Erreur par defaut
	}


	/*
	 *
	 *
	 *
	 */
	function RoundedRect($x, $y, $w, $h,$r, $style = '')
	{
		$k = $this->k;
		$hp = $this->h;
		if($style=='F')
		$op='f';
		elseif($style=='FD' or $style=='DF')
		$op='B';
		else
		$op='S';
		$MyArc = 4/3 * (sqrt(2) - 1);
		$this->_out(sprintf('%.2f %.2f m',($x+$r)*$k,($hp-$y)*$k ));
		$xc = $x+$w-$r ;
		$yc = $y+$r;
		$this->_out(sprintf('%.2f %.2f l', $xc*$k,($hp-$y)*$k ));

		$this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
		$xc = $x+$w-$r ;
		$yc = $y+$h-$r;
		$this->_out(sprintf('%.2f %.2f l',($x+$w)*$k,($hp-$yc)*$k));
		$this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
		$xc = $x+$r ;
		$yc = $y+$h-$r;
		$this->_out(sprintf('%.2f %.2f l',$xc*$k,($hp-($y+$h))*$k));
		$this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
		$xc = $x+$r ;
		$yc = $y+$r;
		$this->_out(sprintf('%.2f %.2f l',($x)*$k,($hp-$yc)*$k ));
		$this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
		$this->_out($op);
	}

	function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
	{
		$h = $this->h;
		$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->k, ($h-$y1)*$this->k,
		$x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
	}

	function _tableau_compl(&$pdf, $fac, $outputlangs)
	{
		global $langs;
		$langs->load("main");
		$langs->load("bills");

		$tab3_top = 240;
		$tab3_height = 18;
		$tab3_width = 60;

		$pdf->Rect(10, $tab3_top, $tab3_width, $tab3_height);

		$pdf->line(10, $tab3_top + 6, $tab3_width+10, $tab3_top + 6 );
		$pdf->line(10, $tab3_top + 12, $tab3_width+10, $tab3_top + 12 );

		$pdf->line(40, $tab3_top, 40, $tab3_top + $tab3_height );

		$pdf->SetFont('Arial','',8);
		$pdf->SetXY (10, $tab3_top - 6);
		$pdf->MultiCell(60, 6, $outputlangs->transnoentities("ExtraInfos"), 0, 'L', 0);
		$pdf->SetXY (10, $tab3_top );
		$pdf->MultiCell(20, 6, $outputlangs->transnoentities("RegulatedOn"), 0, 'L', 0);
		$pdf->SetXY (10, $tab3_top + 6);
		$pdf->MultiCell(60, 6, $outputlangs->transnoentities("ChequeOrTransferNumber"), 0, 'L', 0);
		$pdf->SetXY (10, $tab3_top + 12);
		$pdf->MultiCell(20, 6, $outputlangs->transnoentities("Bank"), 0, 'L', 0);
	}

	/*
	 *   \brief      Affiche le total � payer
	 *   \param      pdf         objet PDF
	 *   \param      fac         objet facture
	 */
	function _tableau_tot(&$pdf, $fac, $outputlangs)
	{
		global $langs;
		$langs->load("main");
		$langs->load("bills");

		$tab2_top = 212;
		$tab2_hl = 5;
		$tab2_height = $tab2_hl * 4;
		$pdf->SetFont('Arial','', 9);

		//	      $pdf->Rect(132, $tab2_top, 68, $tab2_height);
		//	      $pdf->line(174, $tab2_top, 174, $tab2_top + $tab2_height);

		//	      $pdf->line(132, $tab2_top + $tab2_height - 21, 200, $tab2_top + $tab2_height - 21 );
		//	      $pdf->line(132, $tab2_top + $tab2_height - 14, 200, $tab2_top + $tab2_height - 14 );
		//	      $pdf->line(132, $tab2_top + $tab2_height - 7, 200, $tab2_top + $tab2_height - 7 );

		$pdf->SetXY (132, $tab2_top + 0);
		$pdf->MultiCell(42, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'R', 0);

		$pdf->SetXY (174, $tab2_top + 0);
		$pdf->MultiCell(26, $tab2_hl, price($fac->total_ht + $fac->remise), 0, 'R', 0);

		$index = 1;

		$pdf->SetXY (132, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell(42, $tab2_hl, $outputlangs->transnoentities("TotalVAT"), 0, 'R', 0);

		$pdf->SetXY (174, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell(26, $tab2_hl, price($fac->total_tva), 0, 'R', 0);

		$pdf->SetXY (132, $tab2_top + $tab2_hl * ($index+1));
		$pdf->MultiCell(42, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), 0, 'R', 1);

		$pdf->SetXY (174, $tab2_top + $tab2_hl * ($index+1));
		$pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc), 0, 'R', 1);

		$deja_regle = $fac->getSommePaiement();

		if ($deja_regle > 0)
		{
			$pdf->SetXY (132, $tab2_top + $tab2_hl * ($index+2));
			$pdf->MultiCell(42, $tab2_hl, $outputlangs->transnoentities("AlreadyPayed"), 0, 'R', 0);

			$pdf->SetXY (174, $tab2_top + $tab2_hl * ($index+2));
			$pdf->MultiCell(26, $tab2_hl, price($deja_regle), 0, 'R', 0);

			$pdf->SetXY (132, $tab2_top + $tab2_hl * ($index+3));
			$pdf->MultiCell(42, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), 0, 'R', 1);

			$pdf->SetXY (174, $tab2_top + $tab2_hl * ($index+3));
			$pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc - $deja_regle), 0, 'R', 1);
		}
	}
	/*
	 *   \brief      Affiche la grille des lignes de factures
	 *   \param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $langs;
		$langs->load("main");
		$langs->load("bills");

		$pdf->SetFont('Arial','',10);

		$pdf->Text(11,$tab_top + 5,$outputlangs->transnoentities("Designation"));

		$pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
		$pdf->Text(134,$tab_top + 5,$outputlangs->transnoentities("VAT"));

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
		$pdf->Text(147,$tab_top + 5,$outputlangs->transnoentities("Qty"));

		$pdf->line(156, $tab_top, 156, $tab_top + $tab_height);
		$pdf->Text(160,$tab_top + 5,$outputlangs->transnoentities("PriceU"));

		$pdf->line(174, $tab_top, 174, $tab_top + $tab_height);
		$pdf->Text(187,$tab_top + 5,$outputlangs->transnoentities("Total"));

		$pdf->Rect(10, $tab_top, 190, $tab_height);
		$pdf->line(10, $tab_top + 10, 200, $tab_top + 10 );
	}

	/*
	 *   \brief      Affiche en-t�te facture
	 *   \param      pdf     objet PDF
	 *   \param      fac     objet facture
	 */
	function _pagehead(&$pdf, $fac, $outputlangs)
	{
		global $conf;

		$outputlangs->load("main");
		$outputlangs->load("bills");
		$outputlangs->load("propal");
		$outputlangs->load("companies");

		$tab4_top = 60;
		$tab4_hl = 6;
		$tab4_sl = 4;
		$ligne = 2;

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($fac->statut==0 && (! empty($conf->global->FACTURE_DRAFT_WATERMARK)) )
		{
			$watermark_angle=atan($this->page_hauteur/$this->page_largeur);
			$watermark_x=5;
			$watermark_y=$this->page_hauteur-50;
			$watermark_width=$this->page_hauteur;
			$pdf->SetFont('Arial','B',50);
			$pdf->SetTextColor(255,192,203);
			//rotate
			$pdf->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm',cos($watermark_angle),sin($watermark_angle),-sin($watermark_angle),cos($watermark_angle),$watermark_x*$pdf->k,($pdf->h-$watermark_y)*$pdf->k,-$watermark_x*$pdf->k,-($pdf->h-$watermark_y)*$pdf->k));
			//print watermark
			$pdf->SetXY($watermark_x,$watermark_y);
			$pdf->Cell($watermark_width,25,$outputlangs->convToOutputCharset($conf->global->FACTURE_DRAFT_WATERMARK),0,2,"C",0);
			//antirotate
			$pdf->_out('Q');
		}
		//Print content
		$pdf->SetXY(10,5);
		$posy=5;
		
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

		$pdf->SetTextColor(0,0,0);
		$pdf->SetDrawColor(192,192,192);
		$pdf->line(9, 5, 200, 5 );
		$pdf->line(9, 30, 200, 30 );

		// Caracteristiques emetteur
		$carac_emetteur = '';
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->adresse);
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->cp).' '.$outputlangs->convToOutputCharset($this->emetteur->ville);
		$carac_emetteur .= "\n";
		// Tel
		if ($this->emetteur->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($this->emetteur->tel);
		// Fax
		if ($this->emetteur->fax) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($this->emetteur->fax);
		// EMail
		if ($this->emetteur->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($this->emetteur->email);
		// Web
		if ($this->emetteur->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($this->emetteur->url);

		$pdf->SetFont('Arial','',9);
		$pdf->SetXY($tab4_top+28,$tab4_hl);
		$pdf->MultiCell(110,3, $carac_emetteur);



		/*
		 * Definition du document
		 */
		$pdf->SetXY(150,16);
		$pdf->SetFont('Arial','B',16);
		$pdf->SetTextColor(0,0,200);
		$pdf->MultiCell(50, 2, strtoupper($outputlangs->transnoentities("Invoice")), '' , 'C');

		/*
		 * Adresse Client
		 */
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFillColor(242,239,119);

		//      $this->RoundedRect(100, 40, 100, 40, 3, 'F');
		$pdf->rect(100, 40, 100, 40, 'F');
		$pdf->SetFont('Arial','B',12);
		$fac->fetch_client();
		$pdf->SetXY(102,42);
		$pdf->MultiCell(96,5, $outputlangs->convToOutputCharset($fac->client->nom), 0, 'C');
		$pdf->SetFont('Arial','B',11);
		$pdf->SetXY(102,$pdf->GetY()+3);
		$pdf->MultiCell(96,5, $outputlangs->convToOutputCharset($fac->client->adresse) . "\n\n" . $outputlangs->convToOutputCharset($fac->client->cp) . " " . $outputlangs->convToOutputCharset($fac->client->ville), 0, 'C');




		$pdf->SetTextColor(200,0,0);
		$pdf->SetFont('Arial','B',14);
		$pdf->Text(11, 88, $outputlangs->transnoentities('Date'));
		$pdf->Text(35, 88, ": " . dolibarr_print_date($fac->date,'day',false,$outputlangs));
		$pdf->Text(11, 94, $outputlangs->transnoentities('Invoice'));
		$pdf->Text(35, 94, ": ".$fac->ref);

		// Montants exprimes en euros
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',10);
		$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentities("Currency".$conf->monnaie));
		$pdf->Text(200 - $pdf->GetStringWidth($titre), 94, $titre);

	}

	/**
	 *   	\brief      Affiche le pied de page de la facture
	 *   	\param      pdf     		object PDF
	 *  	\param      fac     		object invoice
	 * 		\param		outputlangs		object langs
	 */
	function _pagefoot(&$pdf, $fac, $outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
	}

}
?>
