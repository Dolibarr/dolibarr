<?php
/* Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2008      Chiptronik
 * Copyright (C) 2011-2012 Philippe Grand        <philippe.grand@atoo-net.com>

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
 *	\file       htdocs/core/modules/livraison/pdf/pdf_typhon.modules.php
 *	\ingroup    livraison
 *	\brief      File of class to manage receving receipts with template Typhon
 *	\author	    Laurent Destailleur
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/livraison/modules_livraison.php';
require_once DOL_DOCUMENT_ROOT.'/livraison/class/livraison.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Classe permettant de generer les bons de livraison au modele Typho
 */
class pdf_typhon extends ModelePDFDeliveryOrder
{
	var $emetteur;	// Objet societe qui emet

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");
		$langs->load("sendings");
		$langs->load("companies");

		$this->db = $db;
		$this->name = "typhon";
		$this->description = $langs->trans("DocumentModelTyphon");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT:10;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT:10;
		$this->marge_haute =isset($conf->global->MAIN_PDF_MARGIN_TOP)?$conf->global->MAIN_PDF_MARGIN_TOP:10;
		$this->marge_basse =isset($conf->global->MAIN_PDF_MARGIN_BOTTOM)?$conf->global->MAIN_PDF_MARGIN_BOTTOM:10;

		$this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_codeproduitservice = 1;      // Affiche code produit-service

		$this->franchise=!$mysoc->tva_assuj;

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxcomm=120;
		//$this->posxtva=111;
		$this->posxup=132;
		$this->posxqty=168;
		$this->posxdiscount=162;
		$this->postotalht=177;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxcomm-=20;
			//$this->posxtva-=20;
			$this->posxup-=20;
			$this->posxqty-=20;
			$this->posxdiscount-=20;
			$this->postotalht-=20;
		}

		$this->tva=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}


	/**
	 *	Fonction generant le bon de livraison sur le disque
	 *
	 *	@param	Object		$object   		Object livraison a generer
	 *	@param	Translate	$outputlangs	Lang output object
	 *	@return	int         				1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs)
	{
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("deliveries");
		$outputlangs->load("sendings");

		if ($conf->expedition->dir_output."/receipt")
		{
			$object->fetch_thirdparty();

			$nblines = count($object->lines);

			$objectref = dol_sanitizeFileName($object->ref);
			$dir = $conf->expedition->dir_output."/receipt";
			if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
			$file = $dir . "/" . $objectref . ".pdf";

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
                $pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
                $heightforinfotot = 50;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
                $pdf->SetAutoPageBreak(1,0);

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

				// Complete object by loading several other informations
				$expedition=new Expedition($this->db);
				$result = $expedition->fetch($object->expedition_id);

				$commande = new Commande($this->db);
				if ($expedition->origin == 'commande')
				{
					$commande->fetch($expedition->origin_id);
				}
				$object->commande=$commande;


				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("DeliveryOrder"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("DeliveryOrder"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				/*
				 // Positionne $this->atleastonediscount si on a au moins une remise
				 for ($i = 0 ; $i < $nblignes ; $i++)
				 {
				 if ($object->lines[$i]->remise_percent)
				 {
				 $this->atleastonediscount++;
				 }
				 }
				 */

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 90;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				// Affiche notes
				if (! empty($object->note_public))
				{
					$tab_top = 88;

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($object->note_public), 0, 1);
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

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				// Loop on each lines
				for ($i = 0 ; $i < $nblines ; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColor(0,0,0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					// Description of product line
					$curX = $this->posxdesc-1;
                    pdf_writelinedesc($pdf,$object,$i,$outputlangs,108,3,$curX,$curY);

					$nexY = $pdf->GetY();
                    $pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

					$nexY = $pdf->GetY()+4;

					/*
					 // TVA
					 $pdf->SetXY($this->posxtva, $curY);
					 $pdf->MultiCell(10, 4, ($object->lines[$i]->tva_tx < 0 ? '*':'').abs($object->lines[$i]->tva_tx), 0, 'R');

					 // Prix unitaire HT avant remise
					 $pdf->SetXY($this->posxup, $curY);
					 $pdf->MultiCell(20, 4, price($object->lines[$i]->subprice), 0, 'R', 0);
					 */
					// Quantity
					$pdf->SetXY($this->posxqty, $curY);
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_shipped, 0, 'R');
					/*
					 // Remise sur ligne
					 $pdf->SetXY($this->posxdiscount, $curY);
					 if ($object->lines[$i]->remise_percent)
					 {
					 $pdf->MultiCell(14, 3, $object->lines[$i]->remise_percent."%", 0, 'R');
					 }

					 // Total HT ligne
					 $pdf->SetXY($this->postotalht, $curY);
					 $total = price($object->lines[$i]->price * $object->lines[$i]->qty);
					 $pdf->MultiCell(23, 3, $total, 0, 'R', 0);

					 // Collecte des totaux par valeur de tva
					 // dans le tableau tva["taux"]=total_tva
					 $tvaligne=$object->lines[$i]->price * $object->lines[$i]->qty;
					 if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
					 $this->tva[ (string) $object->lines[$i]->tva_tx ] += $tvaligne;
					 */
					$nexY+=2;    // Passe espace entre les lignes

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
					}
					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1);
						}
						$this->_pagefoot($pdf,$object,$outputlangs,1);
						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfooter + 1;
				}

				/*
				 * Pied de page
				 */
				$this->_pagefoot($pdf,$object,$outputlangs);

				// Check product remaining to be delivered
				// TODO doit etre modifie
				//$waitingDelivery = $object->getRemainingDelivered();
				$waitingDelivery='';

				if (is_array($waitingDelivery) & !empty($waitingDelivery))
				{
					$pdf->AddPage('P', 'A4');

					$this->_pagehead($pdf, $object, 1, $outputlangs);
					$pdf-> SetY(90);

					$w=array(40,100,50);
					$header=array($outputlangs->transnoentities('Reference'),
								  $outputlangs->transnoentities('Label'),
								  $outputlangs->transnoentities('Qty')
								  );

    				// Header
    				$num = count($header);
   					for($i = 0; $i < $num; $i++)
   					{
   						$pdf->Cell($w[$i],7,$header[$i],1,0,'C');
   					}

			    	$pdf->Ln();

			    	// Data
					foreach($waitingDelivery as $value)
					{
						$pdf->Cell($w[0], 6, $value['ref'], 1, 0, 'L');
						$pdf->Cell($w[1], 6, $value['label'], 1, 0, 'L');
						$pdf->Cell($w[2], 6, $value['qty'], 1, 1, 'R');

						if ($pdf->GetY() > 250)
						{
							$this->_pagefoot($pdf,$object,$outputlangs,1);

							$pdf->AddPage('P', 'A4');

							$pdf->SetFont('','', $default_font_size - 1);
							$this->_pagehead($pdf, $object, 0, $outputlangs);

							$pdf-> SetY(40);

							$num = count($header);
							for($i = 0; $i < $num; $i++)
							{
								$pdf->Cell($w[$i],7,$header[$i],1,0,'C');
							}

							$pdf->Ln();
						}
					}

					$this->_pagefoot($pdf,$object,$outputlangs);

				}

				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');
				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}

		$this->error=$langs->transnoentities("ErrorConstantNotDefined","LIVRAISON_OUTPUTDIR");
		return 0;
	}


	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			&$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		Hide top bar of array
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0)
	{
		global $conf,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetDrawColor(128,128,128);

		// Rect prend une longueur en 3eme param
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
		// line prend une position y en 3eme param
		$pdf->line($this->marge_gauche, $tab_top+6, $this->page_largeur-$this->marge_droite, $tab_top+6);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','', $default_font_size - 1);

		$pdf->SetXY($this->posxdesc-1, $tab_top+1);
		$pdf->MultiCell(80,2, $outputlangs->transnoentities("Designation"),'','L');

		// Modif SEB pour avoir une col en plus pour les commentaires clients
		$pdf->line($this->posxcomm, $tab_top, $this->posxcomm, $tab_top + $tab_height);
		$pdf->SetXY($this->posxcomm, $tab_top+1);
		$pdf->MultiCell(80,2, $outputlangs->transnoentities("Comments"),'','L');

		// Qty
		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxqty-1, $tab_top+1);
		$pdf->MultiCell(30, 2, $outputlangs->transnoentities("QtyShipped"),'','R');

		// Modif Seb cadres signatures
		$pdf->SetFont('','', $default_font_size);
		$larg_sign = ($this->page_largeur-$this->marge_gauche-$this->marge_droite)/3;
		$pdf->Rect($this->marge_gauche, ($tab_top + $tab_height + 3), $larg_sign, 25);
		$pdf->SetXY($this->marge_gauche + 2, $tab_top + $tab_height + 5);
		$pdf->MultiCell($larg_sign,2, $outputlangs->trans("For").' '.$outputlangs->convToOutputCharset($mysoc->name).":",'','L');

		$pdf->Rect(2*$larg_sign+$this->marge_gauche, ($tab_top + $tab_height + 3), $larg_sign, 25);
		$pdf->SetXY(2*$larg_sign+$this->marge_gauche + 2, $tab_top + $tab_height + 5);
		$pdf->MultiCell($larg_sign,2, $outputlangs->trans("ForCustomer").':','','L');

	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			&$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $langs,$conf,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

        $posx=$this->page_largeur-$this->marge_droite-100;
		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo)
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
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else $pdf->MultiCell(100, 4, $this->emetteur->name, 0, 'L');

		$pdf->SetFont('','B', $default_font_size + 2);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DeliveryOrder")." ".$outputlangs->convToOutputCharset($object->ref), '', 'R');

		$pdf->SetFont('','',$default_font_size + 2);

		$posy+=5;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		if ($object->date_valid)
		{
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : " . dol_print_date(($object->date_delivery?$object->date_delivery:$date->valid),"%d %b %Y",false,$outputlangs,true), '', 'R');
		}
		else
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DeliveryNotValidated"), '', 'R');
			$pdf->SetTextColor(0,0,60);
		}

		if ($object->client->code_client)
		{
			$posy+=5;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}

		$pdf->SetTextColor(0,0,60);

		// Add origin linked objects
		// TODO extend to other objects
	    $object->fetchObjectLinked('','',$object->id,'delivery');

	    if (! empty($object->linkedObjects))
		{
			$outputlangs->load('orders');

			foreach($object->linkedObjects as $elementtype => $objects)
			{
				$object->fetchObjectLinked('','',$objects[0]->id,$objects[0]->element);

				foreach($object->linkedObjects as $elementtype => $objects)
				{
					$num=count($objects);
					for ($i=0;$i<$num;$i++)
					{
						$order=new Commande($this->db);
						$result=$order->fetch($objects[$i]->id);
						if ($result >= 0)
						{
							$posy+=5;
							$pdf->SetXY($posx,$posy);
							$pdf->SetFont('','', $default_font_size - 1);
							$text=$order->ref;
							if ($order->ref_client) $text.=' ('.$order->ref_client.')';
							$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), '', 'R');
						}
					}
				}
			}
		}

		if ($showaddress)
		{
			// Emetteur
			$posy=42;
			$hautcadre=40;
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche,$posy-5);
			$pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":");


			$pdf->SetXY($this->marge_gauche,$posy);
			$pdf->SetFillColor(230,230,230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);


			$pdf->SetXY($this->marge_gauche+2,$posy+3);

			// Nom emetteur
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFont('','B',$default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');

			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs,$this->emetteur);

			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($this->marge_gauche+2,$posy+9);
			$pdf->MultiCell(80, 3, $carac_emetteur, 0, 'L');

			// Client destinataire
			$posy=42;
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY(102,$posy-5);
			$pdf->MultiCell(80,5, $outputlangs->transnoentities("DeliveryAddress").":", 0, 'L');

			// If SHIPPING contact defined on invoice, we use it
			$usecontact=false;
			$arrayidcontact=$object->commande->getIdContact('external','SHIPPING');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

			// Recipient name
			if (! empty($usecontact))
			{
				// On peut utiliser le nom de la societe du contact
				if (! empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) $socname = $object->contact->socname;
				else $socname = $object->client->nom;
				$carac_client_name=$outputlangs->convToOutputCharset($socname);
			}
			else
			{
				$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);
			}

			$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->client,$object->contact,$usecontact,'target');

			// Show recipient
			$widthrecbox=100;
			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
			$posy=42;
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
			//if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx+2,$posy-5);
			//$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":",0,'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $carac_client_name, 0, 'L');

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy+8);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	void
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		return pdf_pagefoot($pdf,$outputlangs,'DELIVERY_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,0,$hidefreetext);
	}

}

?>
