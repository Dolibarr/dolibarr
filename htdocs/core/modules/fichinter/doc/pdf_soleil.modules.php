<?php
/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis@dolibarr.fr>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2011		Fabrice CHERRIER
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
 *	\file       htdocs/core/modules/fichinter/doc/pdf_soleil.modules.php
 *	\ingroup    ficheinter
 *	\brief      Fichier de la classe permettant de generer les fiches d'intervention au modele Soleil
 */
require_once(DOL_DOCUMENT_ROOT."/core/modules/fichinter/modules_fichinter.php");
require_once(DOL_DOCUMENT_ROOT."/core/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php');
require_once(DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php');


/**
 *	\class      pdf_soleil
 *	\brief      Class to build interventions documents with model Soleil
 */
class pdf_soleil extends ModelePDFFicheinter
{
	var $db;
	var $name;
	var $description;
	var $type;
	
	var $phpmin = array(4,3,0); // Minimum version of PHP required by module
	var $version = 'dolibarr';
	
	var $page_largeur;
	var $page_hauteur;
	var $format;
	var $marge_gauche;
	var	$marge_droite;
	var	$marge_haute;
	var	$marge_basse;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = 'soleil';
		$this->description = $langs->trans("DocumentModelStandard");

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

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 0;                 // Affiche mode reglement
		$this->option_condreg = 0;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 0;               // Dispo en plusieurs langues
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->code_pays) $this->emetteur->code_pays=substr($langs->defaultlang,-2);    // By default, if not defined

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
	}

	/**
     *  Function to build pdf onto disk
     *
     *  @param		object	$object				Object to generate
     *  @param		object	$outputlangs		Lang output object
     *  @return	    int							1=ok, 0=ko
	 */
	function write_file($object,$outputlangs)
	{
		global $user,$langs,$conf,$mysoc;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("interventions");

		if ($conf->ficheinter->dir_output)
		{
            $object->fetch_thirdparty();

			$objectref = dol_sanitizeFileName($object->ref);
			$dir = $conf->ficheinter->dir_output;
			if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
			$file = $dir . "/" . $objectref . ".pdf";

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$outputlangs->trans("ErrorCanNotCreateDir",$dir);
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
				$pdf->SetSubject($outputlangs->transnoentities("InterventionCard"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("InterventionCard"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetTextColor(0,0,0);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3

				$tab_top = 100;
				$tab_top_middlepage = 50;
				$tab_top_newpage = 50;
				$tab_height = 110;
				$tab_height_newpage = 150;
				$tab_height_middlepage = 200;
				$tab_height_endpage = 170;

				// Affiche notes
				if (! empty($object->note_public))
				{
					$tab_top = 88;

					$pdf->SetFont('','', $default_font_size - 1);   // Dans boucle pour gerer multi-page
					$pdf->SetXY($this->posxdesc-1, $tab_top);
					$pdf->MultiCell(190, 3, $outputlangs->convToOutputCharset($object->note_public), 0, 'L');
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
/*
				$pdf->SetXY($this->marge_gauche, $tab_top);
				$pdf->MultiCell(190,8,$outputlangs->transnoentities("Description"),0,'L',0);
				$pdf->line($this->marge_gauche, $tab_top + 8, $this->page_largeur-$this->marge_droite, $tab_top + 8 );

				$pdf->SetFont('','', $default_font_size - 1);

				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetXY($this->marge_gauche, $tab_top + 8 );
				$text=$object->description;
				if ($object->duree > 0)
				{
				    $totaltime=convertSecondToTime($object->duree,'all',$conf->global->MAIN_DURATION_OF_WORKDAY);
				    $text.=($text?' - ':'').$langs->trans("Total").": ".$totaltime;
				}
				$desc=dol_htmlentitiesbr($text,1);
				//print $outputlangs->convToOutputCharset($desc); exit;

				$pdf->writeHTMLCell(180, 3, 10, $tab_top + 8, $outputlangs->convToOutputCharset($desc), 0, 1);
				$nexY = $pdf->GetY();

				$pdf->line($this->marge_gauche, $nexY, $this->page_largeur-$this->marge_droite, $nexY);

				$pdf->MultiCell(0, 3, '');		// Set interline to 3. Then writeMultiCell must use 3 also.
*/
				$nblines = count($object->lines);

				// Loop on each lines
				for ($i = 0; $i < $nblines; $i++)
				{
					$objectligne = $object->lines[$i];

					$valide = $objectligne->id ? $objectligne->fetch($objectligne->id) : 0;
					if ($valide > 0 || $object->specimen)
					{
						$curY = $nexY;

						$pdf->SetXY($this->marge_gauche, $curY);
						$txt=dol_htmlentitiesbr($outputlangs->transnoentities("Date")." : ".dol_print_date($objectligne->datei,'dayhour',false,$outputlangs,true)." - ".$outputlangs->transnoentities("Duration")." : ".convertSecondToTime($objectligne->duration),1,$outputlangs->charset_output);
						$pdf->writeHTMLCell(0, 3, $this->marge_gauche, $curY, $txt, 0, 1, 0);
						$nexY = $pdf->GetY();

						$pdf->SetXY($this->marge_gauche, $curY + 3);
						$desc = dol_htmlentitiesbr($objectligne->desc,1);
						$pdf->writeHTMLCell(0, 3, $this->marge_gauche, $curY + 3, $desc, 0, 1, 0);
						//$nexY+=dol_nboflines_bis($objectligne->desc,52,$outputlangs->charset_output)*3;

						$nexY+=2;    // Passe espace entre les lignes

						// Cherche nombre de lignes a venir pour savoir si place suffisante
						if ($i < ($nblines - 1) && empty($hidedesc))	// If it's not last line
						{
							//on recupere la description du produit suivant
							$follow_descproduitservice = $objectligne->desc;
							//on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
							$nblineFollowDesc = (dol_nboflines_bis($follow_descproduitservice,52,$outputlangs->charset_output)*3);
						}
						else	// If it's last line
						{
							$nblineFollowDesc = 0;
						}

						// Test if a new page is required
						if ($pagenb == 1)
						{
							$tab_top_in_current_page=$tab_top;
							$tab_height_in_current_page=$tab_height;
						}
						else
						{
							$tab_top_in_current_page=$tab_top_newpage;
							$tab_height_in_current_page=$tab_height_middlepage;
						}
						if (($nexY+$nblineFollowDesc) > ($tab_top_in_current_page+$tab_height_in_current_page) && $i < ($nblines - 1))
						{
							if ($pagenb == 1)
							{
								$this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY, $outputlangs);
							}
							else
							{
								$this->_tableau($pdf, $tab_top_newpage, $tab_height_middlepage, $nexY, $outputlangs);
							}

							$this->_pagefoot($pdf,$object,$outputlangs);

							// New page
							$pdf->AddPage();
							$pagenb++;
							$this->_pagehead($pdf, $object, 0, $outputlangs);
							$pdf->SetFont('','', $default_font_size - 1);
							$pdf->MultiCell(0, 3, '');		// Set interline to 3
							$pdf->SetTextColor(0,0,0);

							$nexY = $tab_top_newpage + 7;
						}
					}
				}
				//$pdf->line(10, $tab_top+$tab_height+3, 200, $tab_top+$tab_height+3);

				// Rectangle for title and all lines
				/*
				$pdf->Rect($this->marge_gauche, $tab_top, ($this->page_largeur-$this->marge_gauche-$this->marge_droite), $tab_height+3);
				$pdf->SetXY($this->marge_gauche, $pdf->GetY() + 20);
				$pdf->MultiCell(60, 5, '', 0, 'J', 0);

				$pdf->SetXY(20,220);
				$pdf->MultiCell(66,5, $outputlangs->transnoentities("NameAndSignatureOfInternalContact"),0,'L',0);

				$pdf->SetXY(20,225);
				$pdf->MultiCell(80,30, '', 1);

				$pdf->SetXY(110,220);
				$pdf->MultiCell(80,5, $outputlangs->transnoentities("NameAndSignatureOfExternalContact"),0,'L',0);

				$pdf->SetXY(110,225);
				$pdf->MultiCell(80,30, '', 1);
*/
				$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

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
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","FICHEINTER_OUTPUTDIR");
			return 0;
		}
		$this->error=$langs->trans("ErrorUnknown");
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
		global $conf;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetXY($this->marge_gauche, $tab_top);
		$pdf->MultiCell(190,8,$outputlangs->transnoentities("Description"),0,'L',0);
		$pdf->line($this->marge_gauche, $tab_top + 8, $this->page_largeur-$this->marge_droite, $tab_top + 8);

		$pdf->SetFont('','', $default_font_size - 1);

		$pdf->MultiCell(0, 3, '');		// Set interline to 3
		$pdf->SetXY($this->marge_gauche, $tab_top + 8);
		$text=$object->description;
		if ($object->duree > 0)
		{
			$totaltime=convertSecondToTime($object->duree,'all',$conf->global->MAIN_DURATION_OF_WORKDAY);
			$text.=($text?' - ':'').$langs->trans("Total").": ".$totaltime;
		}
		$desc=dol_htmlentitiesbr($text,1);
		//print $outputlangs->convToOutputCharset($desc); exit;

		$pdf->writeHTMLCell(180, 3, 10, $tab_top + 8, $outputlangs->convToOutputCharset($desc), 0, 1);
		$nexY = $pdf->GetY();

		$pdf->line($this->marge_gauche, $nexY, $this->page_largeur-$this->marge_droite, $nexY);

		$pdf->MultiCell(0, 3, '');		// Set interline to 3. Then writeMultiCell must use 3 also.

		$pdf->Rect($this->marge_gauche, $tab_top, ($this->page_largeur-$this->marge_gauche-$this->marge_droite), $tab_height+3);
		$pdf->SetXY($this->marge_gauche, $pdf->GetY() + 20);
		$pdf->MultiCell(60, 5, '', 0, 'J', 0);

		$pdf->SetXY(20,220);
		$pdf->MultiCell(66,5, $outputlangs->transnoentities("NameAndSignatureOfInternalContact"),0,'L',0);

		$pdf->SetXY(20,225);
		$pdf->MultiCell(80,30, '', 1);

		$pdf->SetXY(110,220);
		$pdf->MultiCell(80,5, $outputlangs->transnoentities("NameAndSignatureOfExternalContact"),0,'L',0);

		$pdf->SetXY(110,225);
		$pdf->MultiCell(80,30, '', 1);
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
		global $conf,$langs;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("interventions");

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->FICHINTER_DRAFT_WATERMARK)) )
		{
			pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->FICHINTER_DRAFT_WATERMARK);
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
				$pdf->SetFont('','B',$default_font_size - 2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('','B',$default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$title=$outputlangs->transnoentities("InterventionCard");
		$pdf->MultiCell(100, 4, $title, '', 'R');

		$pdf->SetFont('','B',$default_font_size + 2);

		$posy+=5;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

		$posy+=1;
		$pdf->SetFont('','', $default_font_size);

		$posy+=4;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->datec,"day",false,$outputlangs,true), '', 'R');

		if ($object->client->code_client)
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->client->code_client), '', 'R');
		}

		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur='';
			// Add internal contact of proposal if defined
			$arrayidcontact=$object->getIdContact('internal','INTERREPFOLL');
			if (count($arrayidcontact) > 0)
			{
				$object->fetch_user($arrayidcontact[0]);
				$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Name").": ".$outputlangs->convToOutputCharset($object->user->getFullName($outputlangs))."\n";
			}

			$carac_emetteur .= pdf_build_address($outputlangs,$this->emetteur);

			// Show sender
			$posy=42;
			$posx=$this->marge_gauche;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
			$hautcadre=40;

			// Show sender frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
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
			$posx=$this->page_largeur-$this->marge_droite-100;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
			$pdf->rect($posx, $posy, 100, $hautcadre);
			$pdf->SetTextColor(0,0,0);

			// Show recipient name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell(100,4, $carac_client_name, 0, 'L');

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy+8);
			$pdf->MultiCell(100,4, $carac_client, 0, 'L');
		}
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
		return pdf_pagefoot($pdf,$outputlangs,'FICHINTER_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
	}

}

?>
