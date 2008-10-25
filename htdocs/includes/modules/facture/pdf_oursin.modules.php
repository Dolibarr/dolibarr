<?php
/* Copyright (C) 2004-2007 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005      Sylvain SCATTOLINI   <sylvain@s-infoservices.com>
 * Copyright (C) 2006      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2008 	Raphael Bertrand (Resultic)  <raphael.bertrand@resultic.fr>
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
 \file       htdocs/includes/modules/facture/pdf_oursin.modules.php
 \ingroup    facture
 \brief      Fichier de la classe permettant de g�n�rer les factures au mod�le oursin
 \author	    Sylvain SCATTOLINI bas� sur un mod�le de Laurent Destailleur
 \version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/includes/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");

/**
 \class      pdf_oursin
 \brief      Classe permettant de g�n�rer les factures au mod�le oursin
 */

class pdf_oursin extends ModelePDFFactures
{
	var $emetteur;	// Objet societe qui emet
	var $marges=array("g"=>10,"h"=>5,"d"=>10,"b"=>15);

	/**
	 \brief  Constructeur
	 \param	db		handler acc�s base de donn�e
	 */
	function pdf_oursin($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");
		$langs->load("products");

		$this->db = $db;
		$this->name = "oursin";
		$this->description = $langs->transnoentities('PDFOursinDescription');

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
		$this->option_modereg = 1;                 // Gere choix mode r�glement FACTURE_CHQ_NUMBER, FACTURE_RIB_NUMBER
		$this->option_condreg = 1;                 // Affiche conditions r�glement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 0;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // G�re les avoirs
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		if (defined("FACTURE_TVAOPTION") && FACTURE_TVAOPTION == 'franchise')
		$this->franchise=1;

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
	function write_file($fac,$outputlangs='')
	{
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$outputlangs->charset_output=$outputlangs->character_set_client='ISO-8859-1';
		
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

			$deja_regle = $fac->getSommePaiement();
			$amount_credit_not_included = $fac->getSommeCreditNote();

				
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
				$pdf->AddPage();

				$this->_pagehead($pdf, $fac);

				$pdf->SetTitle($fac->ref);
				$pdf->SetSubject($outputlangs->transnoentities("Invoice"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($user->fullname);

				$pdf->SetMargins(10, 10, 10);
				$pdf->SetAutoPageBreak(1,0);

				$tab_top = $this->marges['h']+90;
				$tab_height = 110;

				$pdf->SetFillColor(220,220,220);
				$pdf->SetFont('Arial','', 9);
				$pdf->SetXY ($this->marges['g'], $tab_top + $this->marges['g'] );

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
					$pdf->SetXY ($this->marges['g']+ 1, $curY );
					if (defined("FACTURE_CODEPRODUITSERVICE") && FACTURE_CODEPRODUITSERVICE) {
						// Affiche code produit si ligne associ�e � un code produit

						$prodser = new Product($this->db);

						$prodser->fetch($fac->lignes[$i]->produit_id);
						if ($prodser->ref) {
							$codeproduitservice=" - ".$outputlangs->transnoentities("ProductCode")." ".$prodser->ref;
						}
					}
					if ($fac->lignes[$i]->date_start && $fac->lignes[$i]->date_end) {
						// Affichage dur�e si il y en a une
						$codeproduitservice.=" (".$outputlangs->transnoentities("From")." ".dolibarr_print_date($fac->lignes[$i]->date_start)." ".$langs->transnoentities("to")." ".dolibarr_print_date($fac->lignes[$i]->date_end).")";
					}
					$pdf->MultiCell(108, 5, $fac->lignes[$i]->desc."$codeproduitservice", 0, 'J');

					$nexY = $pdf->GetY();

					// TVA
					if ($this->franchise!=1)
					{
						$pdf->SetXY ($this->marges['g']+119, $curY);
						$pdf->MultiCell(10, 5, $fac->lignes[$i]->tva_tx, 0, 'C');
					}
					// Prix unitaire HT avant remise
					$pdf->SetXY ($this->marges['g']+132, $curY);
					$pdf->MultiCell(16, 5, price($fac->lignes[$i]->subprice), 0, 'R', 0);

					// Quantit
					$pdf->SetXY ($this->marges['g']+150, $curY);
					$pdf->MultiCell(10, 5, $fac->lignes[$i]->qty, 0, 'R');

					// Remise sur ligne
					$pdf->SetXY ($this->marges['g']+160, $curY);
					if ($fac->lignes[$i]->remise_percent) {
						$pdf->MultiCell(14, 5, $fac->lignes[$i]->remise_percent."%", 0, 'R');
					}

					// Total HT
					$pdf->SetXY ($this->marges['g']+168, $curY);
					$total = price($fac->lignes[$i]->total_ht);
					$pdf->MultiCell(21, 5, $total, 0, 'R', 0);


					if ($nexY > 200 && $i < $nblignes - 1)
					{
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $fac);
						$pdf->AddPage();
						$nexY = $iniY;
						$this->_pagehead($pdf, $fac);
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('Arial','', 10);
					}

				}
				$posy=$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $fac);

				$posy=$this->_tableau_tot($pdf, $fac, $deja_regle);

				// Affiche zone versements
				if ($deja_regle || $amount_credit_not_included)
				{
					$posy=$this->_tableau_versements($pdf, $fac, $posy, $outputlangs);
				}

				// Mode de r�glement
				if ((! defined("FACTURE_CHQ_NUMBER") || ! FACTURE_CHQ_NUMBER) && (! defined("FACTURE_RIB_NUMBER") || ! FACTURE_RIB_NUMBER)) {
					$pdf->SetXY ($this->marges['g'], 228);
					$pdf->SetTextColor(200,0,0);
					$pdf->SetFont('Arial','B',8);
					$pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorNoPaiementModeConfigured"),0,'L',0);
					$pdf->MultiCell(90, 3, $outputlangs->transnoentities("ErrorCreateBankAccount"),0,'L',0);
					$pdf->SetTextColor(0,0,0);
				}

				// Propose mode r�glement par CHQ
				if (defined("FACTURE_CHQ_NUMBER"))
				{
					if (FACTURE_CHQ_NUMBER > 0)
					{
						$account = new Account($this->db);
						$account->fetch(FACTURE_CHQ_NUMBER);

						$pdf->SetXY ($this->marges['g'], 225);
						$pdf->SetFont('Arial','B',8);
						$pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo').' '.$account->proprio.' '.$langs->transnoentities('SendTo').':',0,'L',0);
						$pdf->SetXY ($this->marges['g'], 230);
						$pdf->SetFont('Arial','',8);
						$pdf->MultiCell(80, 3, $account->adresse_proprio, 0, 'L', 0);
					}
				}

				// Propose mode r�glement par RIB
				if (defined("FACTURE_RIB_NUMBER"))
				{
					if (FACTURE_RIB_NUMBER > 0)
					{
						$account = new Account($this->db);
						$account->fetch(FACTURE_RIB_NUMBER);

						$cury=240;
						$pdf->SetXY ($this->marges['g'], $cury);
						$pdf->SetFont('Arial','B',8);
						$pdf->MultiCell(90, 3, $outputlangs->transnoentities('PaymentByTransferOnThisBankAccount').':', 0, 'L', 0);
						$cury=245;
						$pdf->SetFont('Arial','B',6);
						$pdf->line($this->marges['g'], $cury, $this->marges['g'], $cury+10 );
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
						$pdf->MultiCell(18, 3, $account->code_banque, 0, 'C', 0);
						$pdf->SetXY ($this->marges['g']+18, $cury+5);
						$pdf->MultiCell(18, 3, $account->code_guichet, 0, 'C', 0);
						$pdf->SetXY ($this->marges['g']+36, $cury+5);
						$pdf->MultiCell(24, 3, $account->number, 0, 'C', 0);
						$pdf->SetXY ($this->marges['g']+60, $cury+5);
						$pdf->MultiCell(13, 3, $account->cle_rib, 0, 'C', 0);

						$pdf->SetXY ($this->marges['g'], $cury+14);
						$pdf->MultiCell(90, 3, $outputlangs->transnoentities("Residence").' : ' . $account->domiciliation, 0, 'L', 0);
						$pdf->SetXY ($this->marges['g'], $cury+19);
						$pdf->MultiCell(90, 3, $outputlangs->transnoentities("IbanPrefix").' : ' . $account->iban_prefix, 0, 'L', 0);
						$pdf->SetXY ($this->marges['g'], $cury+24);
						$pdf->MultiCell(90, 3, $outputlangs->transnoentities("BIC").' : ' . $account->bic, 0, 'L', 0);
					}
				}

				// Conditions de r�glements
				if ($fac->cond_reglement_code)
				{
					$pdf->SetFont('Arial','B',10);
					$pdf->SetXY($this->marges['g'], 217);
					$titre = $outputlangs->transnoentities("PaymentConditions").':';
					$pdf->MultiCell(80, 5, $titre, 0, 'L');
					$pdf->SetFont('Arial','',10);
					$pdf->SetXY($this->marges['g']+44, 217);
					$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$fac->cond_reglement_code)!=('PaymentCondition'.$fac->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$fac->cond_reglement_code):$fac->cond_reglement;
					$pdf->MultiCell(80, 5, $lib_condition_paiement,0,'L');
				}

				// Pied de page
				$this->_pagefoot($pdf, $fac, $outputlangs);
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
		$tab3_posx = $this->marges['g']+110;
		$tab3_top = $this->marges['h']+235;
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
			$this->error=$outputlangs->trans("ErrorSQL")." sql=".$sql;
			dolibarr_syslog($this->db,$this->error, LOG_ERR);
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
			while ($i < $num)
			{
				$y+=3;
				$row = $this->db->fetch_row($resql);

				$pdf->SetXY ($tab3_posx, $tab3_top+$y );
				$pdf->MultiCell(20, 4, dolibarr_print_date($row[0],'day'), 0, 'L', 0);
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
			$this->error=$outputlangs->trans("ErrorSQL")." sql=".$sql;
			dolibarr_syslog($this->db,$this->error, LOG_ERR);
			return -1;
		}

	}

	/*
	 *   \brief      Affiche le total � payer
	 *   \param      pdf         objet PDF
	 *   \param      fac         objet facture
	 *   \param      deja_regle  montant deja regle
	 */
	function _tableau_tot(&$pdf, $fac, $deja_regle)
	{
		global $langs;
		$langs->load("main");
		$langs->load("bills");

		$tab2_top = $this->marges['h']+202;
		$tab2_hl = 5;
		$tab2_height = $tab2_hl * 4;
		$pdf->SetFont('Arial','', 9);

		$pdf->SetXY ($this->marges['g'], $tab2_top + 0);

		/*
		 *	If France, show VAT mention if not applicable
		 */
		if ($this->emetteur->pays_code == 'FR' && $this->franchise == 1)
		{
			$pdf->MultiCell(100, $tab2_hl, $langs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);
		}

		// Tableau total
		$col1x=$this->marges['g']+110; $col2x=$this->marges['g']+164;
		$pdf->SetXY ($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("TotalHT"), 0, 'L', 0);

		$pdf->SetXY ($col2x, $tab2_top + 0);
		$pdf->MultiCell(26, $tab2_hl, price($fac->total_ht + $fac->remise), 0, 'R', 0);

		$index = 1;

		$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("TotalVAT"), 0, 'L', 0);

		$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * $index);
		$pdf->MultiCell(26, $tab2_hl, price($fac->total_tva), 0, 'R', 0);

		$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+1));
		$pdf->SetTextColor(22,137,210);
		$pdf->SetFont('Arial','B', 11);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("TotalTTC"), 0, 'L', 0);

		$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+1));
		$pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc), 0, 'R', 0);
		$pdf->SetTextColor(0,0,0);

		if ($deja_regle > 0)
		{
			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+2));
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("AlreadyPayed"), 0, 'L', 0);

			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+2));
			$pdf->MultiCell(26, $tab2_hl, price($deja_regle), 0, 'R', 0);

			$pdf->SetXY ($col1x, $tab2_top + $tab2_hl * ($index+3));
			$pdf->SetTextColor(22,137,210);
			$pdf->SetFont('Arial','B', 11);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $langs->transnoentities("RemainderToPay"), 0, 'L', 0);

			$pdf->SetXY ($col2x, $tab2_top + $tab2_hl * ($index+3));
			$pdf->MultiCell(26, $tab2_hl, price($fac->total_ttc - $deja_regle), 0, 'R', 0);
			$pdf->SetTextColor(0,0,0);
		}
	}

	/*
	 *   \brief      Affiche la grille des lignes de factures
	 *   \param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $fac)
	{
		global $langs;
		$langs->load("main");
		$langs->load("bills");

		$pdf->line( $this->marges['g'], $tab_top+8, 210-$this->marges['d'], $tab_top+8 );
		$pdf->line( $this->marges['g'], $tab_top + $tab_height, 210-$this->marges['d'], $tab_top + $tab_height );

		$pdf->SetFont('Arial','B',10);

		$pdf->Text($this->marges['g']+2,$tab_top + 5, $langs->transnoentities("Designation"));
		if ($this->franchise!=1) $pdf->Text($this->marges['g']+120, $tab_top + 5, $langs->transnoentities("VAT"));
		$pdf->Text($this->marges['g']+135, $tab_top + 5,$langs->transnoentities("PriceUHT"));
		$pdf->Text($this->marges['g']+153, $tab_top + 5, $langs->transnoentities("Qty"));

		$nblignes = sizeof($fac->lignes);
		$rem=0;
		for ($i = 0 ; $i < $nblignes ; $i++)
		if ($fac->lignes[$i]->remise_percent)
		{
	  $rem=1;
		}
		if ($rem==1)
		{
			$pdf->Text($this->marges['g']+163, $tab_top + 5,'Rem.');
		}
		$pdf->Text($this->marges['g']+175, $tab_top + 5, $langs->transnoentities("TotalHT"));
	}

	/*
	 *   \brief      Affiche en-t�te facture
	 *   \param      pdf     objet PDF
	 *   \param      fac     objet facture
	 */
	function _pagehead(&$pdf, $fac)
	{
		global $langs,$conf;
		$langs->load("main");
		$langs->load("bills");
		$langs->load("propal");
		$langs->load("companies");

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
			$pdf->Cell($watermark_width,25,clean_html($conf->global->FACTURE_DRAFT_WATERMARK),0,2,"C",0);
			//antirotate
			$pdf->_out('Q');
		}
		//Print content

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('Arial','B',13);

		$pdf->SetXY($this->marges['g'],6);

		// Logo
		$logo=$conf->societe->dir_logos.'/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
	  {
	  	$taille=getimagesize($logo);
	  	$longueur=$taille[0]/2.835;
	  	$pdf->Image($logo, $this->marges['g'], $this->marges['h'], 0, 24);
	  }
	  else
	  {
	  	$pdf->SetTextColor(200,0,0);
	  	$pdf->SetFont('Arial','B',8);
	  	$pdf->MultiCell(80, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
	  	$pdf->MultiCell(80, 3, $langs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
	  }
		}
		else if (defined("FAC_PDF_INTITULE"))
		{
			$pdf->MultiCell(80, 6, FAC_PDF_INTITULE, 0, 'L');
		}


		/*
		 * Emetteur
		 */
		$posy=$this->marges['h']+24;
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);
		$pdf->SetXY($this->marges['g'],$posy-5);


		$pdf->SetXY($this->marges['g'],$posy);
		$pdf->SetFillColor(255,255,255);
		$pdf->MultiCell(82, 34, "", 0, 'R', 1);


		$pdf->SetXY($this->marges['g'],$posy+4);

		// Nom emetteur
		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('Arial','B',12);
		if (defined("FAC_PDF_SOCIETE_NOM") && FAC_PDF_SOCIETE_NOM)  // Prioritaire sur MAIN_INFO_SOCIETE_NOM
		{
			$pdf->MultiCell(80, 4, FAC_PDF_SOCIETE_NOM, 0, 'L');
		}
		else                                                        // Par defaut
		{
			$pdf->MultiCell(80, 4, MAIN_INFO_SOCIETE_NOM, 0, 'L');
		}

		// Caract�ristiques emetteur
		$pdf->SetFont('Arial','',9);
		if (defined("FAC_PDF_ADRESSE"))
		{
			$pdf->MultiCell(80, 4, FAC_PDF_ADRESSE);
		}
		if (defined("FAC_PDF_TEL") && FAC_PDF_TEL)
		{
			$pdf->MultiCell(80, 4, $langs->transnoentities("Phone").": ".FAC_PDF_TEL);
		}
		if (defined("FAC_PDF_MEL") && FAC_PDF_MEL)
		{
			$pdf->MultiCell(80, 4, $langs->transnoentities("Email").": ".FAC_PDF_MEL);
		}
		if (defined("FAC_PDF_WWW") && FAC_PDF_WWW)
		{
			$pdf->MultiCell(80, 4, $langs->transnoentities("Web").": ".FAC_PDF_WWW);
		}

		/*
		 * Client
		 */
		$posy=45;
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);
		$pdf->SetXY($this->marges['g']+100,$posy-5);
		$pdf->SetFont('Arial','B',11);
		$fac->fetch_client();
		$pdf->SetXY($this->marges['g']+100,$posy+4);
		$pdf->MultiCell(86,4, $fac->client->nom, 0, 'L');
		$pdf->SetFont('Arial','B',10);
		$pdf->SetXY($this->marges['g']+100,$posy+12);
		$pdf->MultiCell(86,4, $fac->client->adresse . "\n\n" . $fac->client->cp . " " . $fac->client->ville);

		/*
		 * ref facture
		 */
		$posy=65;
		$pdf->SetFont('Arial','B',13);
		$pdf->SetXY($this->marges['g'],$posy);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(100, 10, $langs->transnoentities("Bill").' '.$langs->transnoentities("Of").' '.dolibarr_print_date($fac->date,"%d %B %Y"), '' , 'L');
		$pdf->SetFont('Arial','B',11);
		$pdf->SetXY($this->marges['g'],$posy+6);
		$pdf->SetTextColor(22,137,210);
		$pdf->MultiCell(100, 10, $langs->transnoentities("RefBill")." : " . $fac->ref, '', 'L');
		$pdf->SetTextColor(0,0,0);

		/*
		 * ref projet
		 */
		if ($fac->projetid > 0)
		{
			$projet = New Project($fac->db);
			$projet->fetch($fac->projetid);
			$pdf->SetFont('Arial','',9);
			$pdf->MultiCell(60, 4, $langs->transnoentities("Project")." : ".$projet->title);
		}

		/*
		 * ref propal
		 */
		$sql = "SELECT ".$fac->db->pdate("p.datep")." as dp, p.ref, p.rowid as propalid";
		$sql .= " FROM ".MAIN_DB_PREFIX."propal as p, ".MAIN_DB_PREFIX."fa_pr as fp WHERE fp.fk_propal = p.rowid AND fp.fk_facture = $fac->id";
		$result = $fac->db->query($sql);
		if ($result)
		{
			$objp = $fac->db->fetch_object();
			$pdf->SetFont('Arial','',9);
			$pdf->MultiCell(60, 4, $langs->transnoentities("RefProposal")." : ".$objp->ref);
		}

		/*
		 * monnaie
		 */
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',10);
		$titre = $langs->transnoentities("AmountInCurrency",$langs->transnoentities("Currency".$conf->monnaie));
		$pdf->Text(200 - $pdf->GetStringWidth($titre), 94, $titre);
		/*
		 */

	}

	/*
	 *   \brief      Affiche le pied de page de la facture
	 *   \param      pdf     objet PDF
	 *   \param      fac     objet facture
	 */
	function _pagefoot(&$pdf, $fac, $outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'FACTURE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur);
	}

}

?>
