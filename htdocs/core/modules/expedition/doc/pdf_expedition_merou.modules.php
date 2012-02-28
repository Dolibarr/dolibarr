<?php
/* Copyright (C) 2005      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2005-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/core/modules/expedition/doc/pdf_expedition_merou.modules.php
 *	\ingroup    expedition
 *	\brief      Fichier de la classe permettant de generer les bordereaux envoi au modele Merou
 */

require_once DOL_DOCUMENT_ROOT."/core/modules/expedition/modules_expedition.php";
require_once DOL_DOCUMENT_ROOT."/contact/class/contact.class.php";
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');


/**
 *	\class      pdf_expedition_merou
 *	\brief      Classe permettant de generer les borderaux envoi au modele Merou
 */
Class pdf_expedition_merou extends ModelePdfExpedition
{
	var $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function pdf_expedition_merou($db=0)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = "merou";
		//$this->description = "Modele Merou A5";
		$this->description = $langs->trans("DocumentModelMerou");

		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = round($formatarray['height']/2);
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_logo = 1;                    // Affiche logo

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // By default if not defined
	}


	/**
	 *	Fonction generant le document sur le disque
	 *
	 *	@param	    object			Objet expedition a generer (ou id si ancienne methode)
	 *	@param		outputlangs		Lang output object
	 * 	@return	    int     		1=ok, 0=ko
	 */
	function write_file(&$object, $outputlangs)
	{
		global $user,$conf,$langs,$mysoc;
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
		$outputlangs->load("sendings");
		$outputlangs->load("deliveries");

		//Generation de la fiche
		$this->expe = $object;

		//Verification de la configuration
		if ($conf->expedition->dir_output)
		{
			$object->fetch_thirdparty();

			$origin = $object->origin;

			//Creation de l expediteur
			$this->expediteur = $mysoc;

			//Creation du destinataire
			$idcontact = $object->$origin->getIdContact('external','SHIPPING');
            $this->destinataire = new Contact($this->db);
			if ($idcontact[0]) $this->destinataire->fetch($idcontact[0]);

			//Creation du livreur
			$idcontact = $object->$origin->getIdContact('internal','LIVREUR');
			$this->livreur = new User($this->db);
			if ($idcontact[0]) $this->livreur->fetch($idcontact[0]);


			// Definition de $dir et $file
			if ($object->specimen)
			{
				$dir = $conf->expedition->dir_output."/sending";
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$expref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expedition->dir_output . "/sending/" . $expref;
				$file = $dir . "/" . $expref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$outputlangs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			//Si le dossier existe
			if (file_exists($dir))
			{
                $pdf=pdf_getInstance($this->format,'mm','l');

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

				//Generation de l entete du fichier
				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("Sending"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Sending"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				$pdf->SetFont('','', $default_font_size - 3);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $this->expe, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 3);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				//Initialisation des coordonnees
				$tab_top = 53;
				$tab_height = $this->page_hauteur - 78;
				$pdf->SetFillColor(240,240,240);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('','',  $default_font_size - 3);
				$pdf->SetXY(10, $tab_top + 5);
				$iniY = $pdf->GetY();
				$curY = $pdf->GetY();
				$nexY = $pdf->GetY();
				//Generation du tableau
				$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);

				$nblignes = count($object->lines);

				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					// Description de la ligne produit

					//Creation des cases a cocher
					$pdf->Rect(10+3, $curY+1, 3, 3);
					$pdf->Rect(20+3, $curY+1, 3, 3);
					//Insertion de la reference du produit
					$pdf->SetXY(30, $curY+1 );
					$pdf->SetFont('','B', $default_font_size - 3);
					$pdf->MultiCell(24, 3, $outputlangs->convToOutputCharset($object->lines[$i]->ref), 0, 'L', 0);
					//Insertion du libelle
					$pdf->SetFont('','', $default_font_size - 3);
					$pdf->SetXY(50, $curY+1 );
                    //$libelleproduitservice=pdf_getlinedesc($object->$origin,$i,$outputlangs);
					$libelleproduitservice = pdf_writelinedesc($pdf,$object->$origin,$i,$outputlangs,90,3,50,$curY+1,1);
					//$pdf->writeHTMLCell(90, 3, 50, $curY+1, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 'L', 0);
					//Insertion de la quantite commandee
					$pdf->SetFont('','', $default_font_size - 3);
					$pdf->SetXY(140, $curY+1 );
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_asked, 0, 'C', 0);
					//Insertion de la quantite a envoyer
					$pdf->SetFont('','', $default_font_size - 3);
					$pdf->SetXY(170, $curY+1 );
					$pdf->MultiCell(30, 3, $object->lines[$i]->qty_shipped, 0, 'C', 0);

					//Generation de la page 2
					$curY += (dol_nboflines_bis($libelleproduitservice,0,$outputlangs->charset_output)*3+1);
					$nexY = $curY;
					if ($nexY > ($tab_top+$tab_height-10) && $i < $nblignes - 1)
					{
						$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
						$this->_pagefoot($pdf, $object, $outputlangs);
						$pdf->AliasNbPages();

						$curY = $iniY;

						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $this->expe, 0, $outputlangs);
						$pdf->MultiCell(0, 3, '');		// Set interline to 3
						$pdf->SetTextColor(0,0,0);
						$pdf->SetFont('','', $default_font_size - 3);
					}
				}
				//Insertion du pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);

				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');
                if (! empty($conf->global->MAIN_UMASK))
                    @chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
			else
			{
				$this->error=$outputlangs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$outputlangs->transnoentities("ErrorConstantNotDefined","EXP_OUTPUTDIR");
			return 0;
		}
		$this->error=$outputlangs->transnoentities("ErrorUnknown");
		return 0;   // Erreur par defaut

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
		global $langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$langs->load("main");
		$langs->load("bills");

		$pdf->SetFont('','B', $default_font_size - 2);
		$pdf->SetXY(10,$tab_top);
		$pdf->MultiCell(10,5,"LS",0,'C',1);
		$pdf->line(20, $tab_top, 20, $tab_top + $tab_height);
		$pdf->SetXY(20,$tab_top);
		$pdf->MultiCell(10,5,"LR",0,'C',1);
		$pdf->line(30, $tab_top, 30, $tab_top + $tab_height);
		$pdf->SetXY(30,$tab_top);
		$pdf->MultiCell(20,5,$outputlangs->transnoentities("Ref"),0,'C',1);
		$pdf->SetXY(50,$tab_top);
		$pdf->MultiCell(90,5,$outputlangs->transnoentities("Description"),0,'L',1);
		$pdf->SetXY(140,$tab_top);
		$pdf->MultiCell(30,5,$outputlangs->transnoentities("QtyOrdered"),0,'C',1);
		$pdf->SetXY(170,$tab_top);
		$pdf->MultiCell(30,5,$outputlangs->transnoentities("QtyToShip"),0,'C',1);
		$pdf->Rect(10, $tab_top, 190, $tab_height);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			&$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @return	void
	 */
	function _pagefoot(&$pdf, $object, $outputlangs)
	{
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		$pdf->SetFont('','', $default_font_size - 2);
		$pdf->SetY(-23);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("GoodStatusDeclaration"), 0, 'L');
		$pdf->SetY(-13);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ToAndDate"), 0, 'C');
		$pdf->SetXY(120,-23);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("NameAndSignature"), 0, 'C');

		// Show page nb only on iso languages (so default Helvetica font)
        //if (pdf_getPDFFont($outputlangs) == 'Helvetica')
        //{
    	//    $pdf->SetXY(-10,-10);
        //    $pdf->MultiCell(11, 2, $pdf->PageNo().'/'.$pdf->getAliasNbPages(), 0, 'R', 0);
        //}
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
		global $conf, $langs;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

			//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->SENDING_DRAFT_WATERMARK)) )
		{
            pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->SENDING_DRAFT_WATERMARK);
		}

        $posy=$this->marge_haute;
        $posx=$this->page_largeur-$this->marge_droite-100;

		$Xoff = 90;
		$Yoff = 0;

		$tab4_top = 60;
		$tab4_hl = 6;
		$tab4_sl = 4;
		$line = 2;

		//*********************LOGO****************************
		$pdf->SetXY(11,7);
		$logo=$conf->mycompany->dir_output.'/logos/'.$this->emetteur->logo;
		if ($this->emetteur->logo)
		{
			if (is_readable($logo))
			{
				$pdf->Image($logo,10, 5, 0, 22);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		//*********************Entete****************************
		//Nom du Document
		$pdf->SetXY($Xoff,7);
		$pdf->SetFont('','B', $default_font_size + 2);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 3, $outputlangs->transnoentities("SendingSheet"), '', 'L');	// Bordereau expedition
		//Num Expedition
		$Yoff = $Yoff+7;
		$Xoff = 142;
		//$pdf->Rect($Xoff, $Yoff, 85, 8);
		$pdf->SetXY($Xoff,$Yoff);
		$pdf->SetFont('','', $default_font_size - 2);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(0, 3, $outputlangs->transnoentities("RefSending").': '.$outputlangs->convToOutputCharset($object->ref), '', 'R');
		//$this->Code39($Xoff+43, $Yoff+1, $object->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);

		// Add list of linked elements
		// TODO possibility to use with other elements (business module,...)
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
				$Yoff = $Yoff+4;
				$pdf->SetXY($Xoff,$Yoff);
				$pdf->SetFont('','', $default_font_size - 2);
				$text=$linkedobject->ref;
				if ($linkedobject->ref_client) $text.=' ('.$linkedobject->ref_client.')';
				$pdf->MultiCell(0, 3, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), '', 'R');
			}
		}

		//$this->Code39($Xoff+43, $Yoff+1, $object->commande->ref,$ext = true, $cks = false, $w = 0.4, $h = 4, $wide = true);
		//Definition Emplacement du bloc Societe
		$Xoff = 110;
		$blSocX=90;
		$blSocY=24;
		$blSocW=50;
		$blSocX2=$blSocW+$blSocX;

		// Sender name
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','B', $default_font_size - 3);
		$pdf->SetXY($blSocX,$blSocY+1);
		$pdf->MultiCell(80, 3, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
		$pdf->SetTextColor(0,0,0);

		// Sender properties
		$carac_emetteur = pdf_build_address($outputlangs,$this->emetteur);

		$pdf->SetFont('','', $default_font_size - 3);
		$pdf->SetXY($blSocX,$blSocY+4);
		$pdf->MultiCell(80, 2, $carac_emetteur, 0, 'L');


		if ($object->client->code_client)
		{
			$Yoff+=7;
			$posy=$Yoff;
			$pdf->SetXY(100,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}

		//Date Expedition
		$Yoff = $Yoff+7;
		$pdf->SetXY($blSocX-80,$blSocY+20);
		$pdf->SetFont('','B', $default_font_size - 2);
		$pdf->SetTextColor(0,0,0);
		$pdf->MultiCell(50, 8, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date_delivery,'day',false,$outputlangs,true), '', 'L');

		// Deliverer
		$pdf->SetXY($blSocX-80,$blSocY+23);
		$pdf->SetFont('','', $default_font_size - 2);
		$pdf->SetTextColor(0,0,0);

		if (! empty($object->tracking_number))
		{
			$object->GetUrlTrackingStatus($object->tracking_number);
			if (! empty($object->tracking_url))
			{
				if ($object->expedition_method_id > 0)
				{
					// Get code using getLabelFromKey
					$code=$outputlangs->getLabelFromKey($this->db,$object->expedition_method_id,'c_shipment_mode','rowid','code');
					$label=$outputlangs->trans("SendingMethod".strtoupper($code))." :";
				}
				else
				{
					$label=$outputlangs->transnoentities("Deliverer");
				}

				$pdf->writeHTMLCell(50, 8, '', '', $label." ".$object->tracking_url, '', 'L');
			}
		}
		else
		{
			$pdf->MultiCell(50, 8, $outputlangs->transnoentities("Deliverer")." ".$outputlangs->convToOutputCharset($this->livreur->getFullName($outputlangs)), '', 'L');
		}


		/**********************************/
		//Emplacement Informations Expediteur (My Company)
		/**********************************/
		$Yoff = $blSocY;
		$blExpX=$Xoff-20;
		$blW=52;
		$Ydef = $Yoff;
		$pdf->Rect($blExpX, $Yoff, $blW, 26);

		$object->fetch_thirdparty();

		// If SHIPPING contact defined on order, we use it
		$usecontact=false;
		$arrayidcontact=$object->$origin->getIdContact('external','SHIPPING');
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


		$blDestX=$blExpX+55;
		$blW=50;
		$Yoff = $Ydef +1;

		// Show recipient frame
		$pdf->SetFont('','B', $default_font_size - 3);
		$pdf->SetXY($blDestX,$Yoff-4);
		$pdf->MultiCell($blW,3, $outputlangs->transnoentities("Recipient"), 0, 'L');
		$pdf->Rect($blDestX, $Yoff-1, $blW, 26);

		// Show recipient name
		$pdf->SetFont('','B', $default_font_size - 3);
		$pdf->SetXY($blDestX,$Yoff);
		$pdf->MultiCell($blW,3, $carac_client_name, 0, 'L');

		// Show recipient information
		$pdf->SetFont('','', $default_font_size - 3);
		$pdf->SetXY($blDestX,$Yoff+(dol_nboflines_bis($carac_client_name,50)*4));
		$pdf->MultiCell($blW,2, $carac_client, 0, 'L');
	}
}
?>
