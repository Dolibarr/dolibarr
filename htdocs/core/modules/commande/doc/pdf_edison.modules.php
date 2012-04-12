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
 *	\file       htdocs/core/modules/commande/pdf_edison.modules.php
 *	\ingroup    commande
 *	\brief      Fichier de la classe permettant de generer les commandes au modele Edison
 */

require_once(DOL_DOCUMENT_ROOT ."/core/modules/commande/modules_commande.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');

/**
 *	Classe permettant de generer les commandes au modele Edison
 */
class pdf_edison extends ModelePDFCommandes
{
	var $emetteur;	// Objet societe qui emet

	/**
	 * 	Constructor
     *
	 *	@param		DoliDb	$db		Database access handler
	 */
	function __construct($db=0)
	{
        global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");

        $this->db = $db;
		$this->name = "edison";
		$this->description = $langs->trans('PDFEdisonDescription');

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_multilang = 0;               // Dispo en plusieurs langues
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxtva=111;
		$this->posxup=126;
		$this->posxqty=145;
		$this->posxdiscount=162;
		$this->postotalht=174;

		$this->tva=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}


	/**
     *  Function to build pdf onto disk
     *
     *  @param		int		$object				Id of object to generate
     *  @param		object	$outputlangs		Lang output object
     *  @param		string	$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int		$hidedetails		Do not show line details
     *  @param		int		$hidedesc			Do not show desc
     *  @param		int		$hideref			Do not show ref
     *  @param		object	$hookmanager		Hookmanager object
     *  @return     int             			1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0,$hookmanager=false)
	{
		global $user,$conf,$langs,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
        $outputlangs->load("orders");

		if ($conf->commande->dir_output)
		{
			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->commande->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->commande->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				$nblignes = count($object->lines);

				$pdf=pdf_getInstance($this->format);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (empty($conf->global->MAIN_DISABLE_FPDI) && ! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Order"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Order"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs, $hookmanager);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);


				$tab_top = 100;
				$tab_height = 140;

				$pdf->SetFillColor(220,220,220);

				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('','', $default_font_size - 1);

				$pdf->SetXY(10, $tab_top + 10);

				$iniY = $pdf->GetY();
				$curY = $pdf->GetY();
				$nexY = $pdf->GetY();
				$nblignes = count($object->lines);

				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page

					// Description of product line
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,100,3,30,$curY,1,$hidedesc,0,$hookmanager);

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut
					$nexY = $pdf->GetY();

					$ref = pdf_getlineref($object, $i, $outputlangs, $hidedetails, $hookmanager);
					$pdf->SetXY(10, $curY);
					$pdf->MultiCell(20, 3, $ref, 0, 'C');

					$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails, $hookmanager);
					$pdf->SetXY(133, $curY);
					$pdf->MultiCell(12, 3, $vat_rate, 0, 'C');

					$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails, $hookmanager);
					$pdf->SetXY(145, $curY);
					$pdf->MultiCell(10, 3, $qty, 0, 'C');

					$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails, $hookmanager);
					$pdf->SetXY(156, $curY);
					$pdf->MultiCell(18, 3, $up_excl_tax, 0, 'R', 0);

					$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails, $hookmanager);
					$pdf->SetXY(174, $curY);
					$pdf->MultiCell(26, 3, $total_excl_tax, 0, 'R', 0);

					$nexY+=2;    // Passe espace entre les lignes

					// cherche nombre de lignes a venir pour savoir si place suffisante
					if ($i < ($nblignes - 1) && empty($hidedesc))	// If it's not last line
					{
						//on recupere la description du produit suivant
						$follow_descproduitservice = $object->lines[$i+1]->desc;
						//on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
						$nblineFollowDesc = (dol_nboflines_bis($follow_descproduitservice,52,$outputlangs->charset_output)*4);
					}
					else	// If it's last line
					{
						$nblineFollowDesc = 0;
					}

					if ((($nexY+$nblineFollowDesc) > ($tab_top+$tab_height) && $i < ($nblignes - 1)) || (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak))
					{
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

						// New page
						$pdf->AddPage();
				        if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						$this->_pagehead($pdf, $object, 0, $outputlangs, $hookmanager);
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

				// Affiche zone totaux
				$tab2_top = 241;
				$tab2_lh = 4;

				$pdf->SetFont('','', $default_font_size);

				$pdf->SetXY(132, $tab2_top + 0);
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("TotalHT"), 0, 'R', 0);

				$pdf->SetXY(132, $tab2_top + $tab2_lh);
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("TotalVAT"), 0, 'R', 0);

				$pdf->SetXY(132, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(42, $tab2_lh, $langs->transnoentities("TotalTTC"), 1, 'R', 1);

				$pdf->SetXY(174, $tab2_top + 0);
				$pdf->MultiCell(26, $tab2_lh, price($object->total_ht), 0, 'R', 0);

				$pdf->SetXY(174, $tab2_top + $tab2_lh);
				$pdf->MultiCell(26, $tab2_lh, price($object->total_tva), 0, 'R', 0);

				$pdf->SetXY(174, $tab2_top + ($tab2_lh*2));
				$pdf->MultiCell(26, $tab2_lh, price($object->total_ttc), 1, 'R', 1);

				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');

				// Actions on extra fields (by external module or standard code)
				if (! is_object($hookmanager))
				{
					include_once(DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php');
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				{
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

				return 1;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","COMMANDE_OUTPUTDIR");
			return 0;
		}

		$this->error=$langs->transnoentities("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		PDF			&$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
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

        // Show payments conditions
		if ($object->cond_reglement_code || $object->cond_reglement)
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
	                $pdf->SetFont('','B', $default_font_size - 2);
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

		return $posy;
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			&$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $langs,$conf;
		$langs->load("main");
		$langs->load("bills");
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('','', $default_font_size - 1);

        $pdf->SetXY(30,$tab_top + 2);
        $pdf->MultiCell(20,4,$outputlangs->transnoentities("Designation"),0,'L');

        if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
        {
            $pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
            $pdf->SetXY(133,$tab_top + 2);
            $pdf->MultiCell(12,4,$outputlangs->transnoentities("VAT"),'','C');
        }

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
        $pdf->SetXY(145,$tab_top + 2);
        $pdf->MultiCell(12,4,$outputlangs->transnoentities("Qty"),'','C');

		$pdf->line(156, $tab_top, 156, $tab_top + $tab_height);
        $pdf->SetXY(157,$tab_top + 2);
        $pdf->MultiCell(16,4,$outputlangs->transnoentities("PriceUHT"),'','C');

		$pdf->line(174, $tab_top, 174, $tab_top + $tab_height);
        $pdf->SetXY(175,$tab_top + 2);
        $pdf->MultiCell(30,4,$outputlangs->transnoentities("TotalHT"),'','C');

		//      $pdf->Rect(10, $tab_top, 190, $nexY - $tab_top);
		$pdf->Rect(10, $tab_top, 190, $tab_height);

		$pdf->line(10, $tab_top + 8, 200, $tab_top + 8);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','', $default_font_size-1);
		$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->currency));
		$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-4);
		$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);
	}


	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			&$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	object		$hookmanager	Hookmanager object
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs ,$hookmanager)
	{
		global $conf,$langs,$mysoc;
		$langs->load("orders");
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->COMMANDE_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->COMMANDE_DRAFT_WATERMARK);
		}


		$posy=$this->marge_haute;
		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
			    $height=pdf_getHeightForLogo($logo);
			    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		if ($showaddress)
		{
    		$posy = 40;
			$posx=$this->marge_gauche;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
			$hautcadre=40;

    		$pdf->SetXY($posx,$posy+3);

    		// Sender name
    		$pdf->SetTextColor(0,0,60);
    		$pdf->SetFont('','B', $default_font_size);
    		$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');

    		// Sender properties
    		$carac_emetteur = pdf_build_address($outputlangs,$this->emetteur);

    		$pdf->SetFont('','', $default_font_size - 1);
    		$pdf->SetXY($posx,$posy+7);
    		$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');

    		// Client destinataire
    		$client = new Societe($this->db);
    		$client->fetch($object->socid);
    		$object->client = $client;

    		// If CUSTOMER contact defined on invoice, we use it
    		$usecontact=false;
    		$arrayidcontact=$object->getIdContact('external','CUSTOMER');
    		if (count($arrayidcontact) > 0)
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
    		$posy=42;
    		$posx=$this->page_largeur-$this->marge_droite-100;
    		if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

    		// Show recipient frame
    		$pdf->SetTextColor(0,0,0);
    		$pdf->SetFont('','', $default_font_size - 2);
    		$pdf->SetXY($posx+2,$posy-5);
    		$pdf->MultiCell(80,5, $outputlangs->transnoentities("BillTo").":",0,'L');
    		$pdf->Rect($posx, $posy, 100, $hautcadre);

    		// Show recipient name
    		$pdf->SetXY($posx+2,$posy+3);
    		$pdf->SetFont('','B', $default_font_size);
    		$pdf->MultiCell(96,4, $carac_client_name, 0, 'L');

    		// Show recipient information
    		$pdf->SetFont('','', $default_font_size - 1);
    		$pdf->SetXY($posx+2,$posy+4+(dol_nboflines_bis($carac_client_name,50)*4));
    		$pdf->MultiCell(86,4, $carac_client, 0, 'L');
		}

		$curY = 80;
		$posy=$curY;

		// Date - order
		$pdf->SetTextColor(200,0,0);
		$pdf->SetFont('','B', $default_font_size + 1);
		$pdf->SetXY(11, $posy);
		$posy+=6;
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date,'day',false,$outputlangs), 0, 'L');
        $pdf->SetXY(11, $posy);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Order")." ".$outputlangs->convToOutputCharset($object->ref), 0, 'L');


		$posy+=1;

		// Show list of linked objects
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'L', $default_font_size, $hookmanager);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @return	void
	 */
	function _pagefoot(&$pdf,$object,$outputlangs)
    {
		return pdf_pagefoot($pdf,$outputlangs,'COMMANDE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
    }
}

?>
