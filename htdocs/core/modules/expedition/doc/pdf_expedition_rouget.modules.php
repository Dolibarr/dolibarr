<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin		<regis@dolibarr.fr>
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
 *	\file       htdocs/core/modules/expedition/doc/pdf_expedition_rouget.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier de la classe permettant de generer les bordereaux envoi au modele Rouget
 */

require_once DOL_DOCUMENT_ROOT."/core/modules/expedition/expedition/modules_expedition.php";
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');


/**
 *	\class      pdf_expedition_dorade
 *	\brief      Classe permettant de generer les borderaux envoi au modele Rouget
 */
Class pdf_expedition_rouget extends ModelePdfExpedition
{
	var $emetteur;	// Objet societe qui emet


	/**
	 *	\brief  Constructeur
	 *	\param	db		Database handler
	 */
	function pdf_expedition_rouget($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = "rouget";
		$this->description = $langs->trans("DocumentModelSimple");

		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_logo = 1;

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // By default if not defined

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxqtyordered=120;
		$this->posxqtytoship=160;
	}

	/**
	 *		\brief      Fonction generant le document sur le disque
	 *		\param	    object			Objet expedition a generer (ou id si ancienne methode)
	 *		\param		outputlangs		Lang output object
	 * 	 	\return	    int     		1=ok, 0=ko
	 */
	function write_file(&$object, $outputlangs)
	{
		global $user,$conf,$langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$object->fetch_thirdparty();

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("propal");
		$outputlangs->load("deliveries");
        $outputlangs->load("sendings");

		if ($conf->expedition->dir_output)
		{
			// Definition de $dir et $file
			if ($object->specimen)
			{
				$dir = $conf->expedition->dir_output."/sending";
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$expref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expedition->dir_output."/sending/" . $expref;
				$file = $dir . "/" . $expref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$outputlangs->transnoentities("ErrorCanNotCreateDir",$dir);
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

				$pdf->AliasNbPages();

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Sending"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($fac->ref)." ".$outputlangs->transnoentities("Sending"));
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

				$tab_top = 90;
				$tab_height = 170;

				if (! empty($object->note_public) || ! empty($object->tracking_number))
				{
					$tab_top = 88;

					// Tracking number
					if (! empty($object->tracking_number))
					{
						$object->GetUrlTrackingStatus($object->tracking_number);
						if (! empty($object->tracking_url))
						{
							if ($object->expedition_method_id > 0)
							{
								// Get code using getLabelFromKey
								$code=$outputlangs->getLabelFromKey($this->db,$object->expedition_method_id,'c_shipment_mode','rowid','code');
								$label=$outputlangs->trans("LinkToTrackYourPackage")."<br>";
								$label.=$outputlangs->trans("SendingMethod".strtoupper($code))." :";
								$pdf->SetFont('','B', $default_font_size - 2);
								$pdf->writeHTMLCell(60, 4, $this->posxdesc-1, $tab_top-1, $label." ".$object->tracking_url, 0, 1, false, true, 'L');
							}
						}
					}

					// Affiche notes
					if (! empty($object->note_public))
					{
						$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
						$pdf->SetXY($this->posxdesc-1, $tab_top);
						$pdf->MultiCell(190, 3, $outputlangs->convToOutputCharset($object->note_public), 0, 'L');
					}

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

				$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

				$nexY = $tab_top + 7;

				$num=count($object->lines);
				for ($i = 0; $i < $num; $i++)
				{
					$curY = $nexY;

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page

					// Description de la ligne produit
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,150,3,$this->posxdesc,$curY,0,1);

					$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut
					$nexY = $pdf->GetY();

					$pdf->SetXY($this->posxqtyordered+5, $curY);
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_asked,'','C');

					$pdf->SetXY($this->posxqtytoship+5, $curY);
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_shipped,'','C');

					$nexY+=2;    // Passe espace entre les lignes
				}


				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
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
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","EXP_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->transnoentities("ErrorUnknown");
		return 0;   // Erreur par defaut
	}

	/**
	 *   Build table
	 *   @param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetTextColor(0,0,0);
		$pdf->SetDrawColor(128,128,128);

		// Rect prend une longueur en 3eme param
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
		// line prend une position y en 3eme param
		$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);

		$pdf->SetFont('','',$default_font_size - 1);

		$pdf->SetXY($this->posxdesc-1, $tab_top+1);
		$pdf->MultiCell(108, 2, $outputlangs->trans("Description"), '', 'L');

		$pdf->line($this->posxqtyordered-1, $tab_top, $this->posxqtyordered-1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxqtyordered-1, $tab_top+1);
		$pdf->MultiCell(40,2, $outputlangs->transnoentities("QtyOrdered"),'','C');

		$pdf->line($this->posxqtytoship-1, $tab_top, $this->posxqtytoship-1, $tab_top + $tab_height);
		$pdf->SetXY($this->posxqtytoship-1, $tab_top+1);
		$pdf->MultiCell(40,2, $outputlangs->transnoentities("QtyToShip"),'','C');
	}

	/**
	 *   	Show header of document
	 *
	 *   	@param      pdf     		Object PDF
	 *   	@param      object			Object commercial proposal
	 *      @param      showaddress     0=no, 1=yes
	 *      @param      outputlangs    	Object lang for output
	 */
	function _pagehead(&$pdf, $object, $showaddress=1, $outputlangs)
	{
		global $conf,$langs,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$langs->load("orders");

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->SHIPPING_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->SHIPPING_DRAFT_WATERMARK);
		}

		//Prepare la suite
		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

        $posx=$this->page_largeur-$this->marge_droite-100;
		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
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

		// Show barcode
		if ($conf->barcode->enabled)
		{
			$posx=105;
		}
		else
		{
			$posx=$this->marge_gauche+3;
		}
		//$pdf->Rect($this->marge_gauche, $this->marge_haute, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 30);
		if ($conf->barcode->enabled)
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $object->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}

		$pdf->SetDrawColor(128,128,128);
		if ($conf->barcode->enabled)
		{
			// TODO Build code bar with function writeBarCode of barcode module for sending ref $object->ref
			//$pdf->SetXY($this->marge_gauche+3, $this->marge_haute+3);
			//$pdf->Image($logo,10, 5, 0, 24);
		}


		$posx=100;
		$posy=$this->marge_haute;

		$pdf->SetFont('','B', $default_font_size + 2);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$title=$outputlangs->transnoentities("SendingSheet");
		$pdf->MultiCell(100, 4, $title, '', 'R');
        $posy+=1;

		$pdf->SetFont('','', $default_font_size + 1);

		$posy+=4;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefSending") ." : ".$object->ref, '', 'R');

		//Date Expedition
		$posy+=4;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : ".dol_print_date($object->date_creation,"daytext",false,$outputlangs,true), '', 'R');

		if (! empty($object->client->code_client))
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}


		$pdf->SetFont('','', $default_font_size + 3);
	    $Yoff=25;

	    // Add list of linked orders
	    // TODO possibility to use with other document (business module,...)
	    //$object->load_object_linked();

	    $origin 	= $object->origin;
		$origin_id 	= $object->origin_id;

	    // TODO move to external function
		if ($conf->$origin->enabled)
		{
			$outputlangs->load('orders');

			$classname = ucfirst($origin);
			$linkedobject = new $classname($this->db);
			$result=$linkedobject->fetch($origin_id);
			if ($result >= 0)
			{
				$pdf->SetFont('','', $default_font_size - 2);
				$text=$linkedobject->ref;
				if ($linkedobject->ref_client) $text.=' ('.$linkedobject->ref_client.')';
				$Yoff = $Yoff+8;
				$pdf->SetXY($this->page_largeur - $this->marge_droite - 60,$Yoff);
				$pdf->MultiCell(60, 2, $outputlangs->transnoentities("RefOrder") ." : ".$outputlangs->transnoentities($text), 0, 'R');
				$Yoff = $Yoff+4;
				$pdf->SetXY($this->page_largeur - $this->marge_droite - 60,$Yoff);
				$pdf->MultiCell(60, 2, $outputlangs->transnoentities("Date")." : ".dol_print_date($object->commande->date,"daytext",false,$outputlangs,true), 0, 'R');
			}
		}

		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur='';
		 	// Add internal contact of proposal if defined
			$arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
		 	if (count($arrayidcontact) > 0)
		 	{
		 		$object->fetch_user($arrayidcontact[0]);
		 		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
		 	}

		 	$carac_emetteur .= pdf_build_address($outputlangs,$this->emetteur);

			// Show sender
			$posx=$this->marge_gauche;
			$posy=42;
			$hautcadre=40;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=118;

			// Show sender frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
			$pdf->MultiCell(66,5, $outputlangs->transnoentities("Sender").":", 0, 'L');
			$pdf->SetXY($posx,$posy);
			$pdf->SetFillColor(230,230,230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);

			// Show sender name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFont('','B',$default_font_size);
			$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');

			// Show sender information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy+8);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');


			// If CUSTOMER contact defined, we use it
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
			$posx=100;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
			$pdf->MultiCell(80, 4, $outputlangs->transnoentities("Recipient").":", 0, 'L');
			$pdf->rect($posx, $posy, 100, $hautcadre);
			$pdf->SetTextColor(0,0,0);

			// Show recipient name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell(96,4, $carac_client_name, 0, 'L');

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy+8);
			$pdf->MultiCell(86,4, $carac_client, 0, 'L');
		}

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
		return pdf_pagefoot($pdf,$outputlangs,'SHIPPING_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
	}

}

?>
