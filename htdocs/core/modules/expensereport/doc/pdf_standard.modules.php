<?php
/* Copyright (C) 2015 Laurent Destailleur    <eldy@users.sourceforge.net>
 * Copyright (C) 2015 Alexandre Spangaro     <aspangaro.dolibarr@gmail.com>
 * Copyright (C) 2016 Philippe Grand	 	 <philippe.grand@atoo-net.com>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
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
 *	\file       htdocs/core/modules/expensereport/doc/pdf_standard.modules.php
 *	\ingroup    expensereport
 *	\brief      File of class to generate expense report from standard model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/expensereport/modules_expensereport.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';



/**
 *	Class to generate expense report based on standard model
 */
class pdf_standard extends ModeleExpenseReport
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
		$langs->load("trips");
		$langs->load("projects");

		$this->db = $db;
		$this->name = "";
		$this->description = $langs->trans('PDFStandardExpenseReports');

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

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 0;                // Affiche si il y a eu escompte
		$this->option_credit_note = 0;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Define position of columns
		$this->posxpiece=$this->marge_gauche+1;
		$this->posxcomment=$this->marge_gauche+10;
		$this->posxdate=88;
		$this->posxtype=107;
		$this->posxprojet=120;
		$this->posxtva=136;
		$this->posxup=152;
		$this->posxqty=168;
		$this->postotalttc=178;
        if (empty($conf->projet->enabled)) {
            $this->posxtva-=20;
            $this->posxup-=20;
            $this->posxqty-=20;
            $this->postotalttc-=20;
        }
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxdate-=20;
			$this->posxtype-=20;
			$this->posxprojet-=20;
			$this->posxtva-=20;
			$this->posxup-=20;
			$this->posxqty-=20;
			$this->postotalttc-=20;
		}

		$this->tva=array();
		$this->localtax1=array();
		$this->localtax2=array();
		$this->atleastoneratenotnull=0;
	}


	/**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int             				1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc,$db,$hookmanager;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("trips");
		$outputlangs->load("projects");

		$nblignes = count($object->lines);

		if ($conf->expensereport->dir_output)
		{
			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->expensereport->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->expensereport->dir_output . "/" . $objectref;
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

			if (isset($object->lignes) && ! isset($object->lines)) $object->lines=$object->lignes;

			if (file_exists($dir))
			{
				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('beforePDFCreation',$parameters,$object,$action);    // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
				$pdf=pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				$heightforinfotot = 40;	// Height reserved to output the info and total part
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

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref_number));
				$pdf->SetSubject($outputlangs->transnoentities("Trips"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Trips"));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// New page
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('','', $default_font_size - 1);
				$pdf->MultiCell(0, 3, '');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 95;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?65:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				// Show notes
				$notetoshow=empty($object->note_public)?'':$object->note_public;
				if (! empty($conf->global->MAIN_ADD_SALE_REP_SIGNATURE_IN_NOTE))
				{
					// Get first sale rep
					if (is_object($object->thirdparty))
					{
						$salereparray=$object->thirdparty->getSalesRepresentatives($user);
						$salerepobj=new User($this->db);
						$salerepobj->fetch($salereparray[0]['id']);
						if (! empty($salerepobj->signature)) $notetoshow=dol_concatdesc($notetoshow, $salerepobj->signature);
					}
				}
				if ($notetoshow)
				{
					$tab_top = 95;

					$pdf->SetFont('','', $default_font_size - 1);
               		$pdf->writeHTMLCell(190, 3, $this->posxpiece-1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
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
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$piece_comptable = $i +1;

					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 2);   // Into loop to work with multipage
					$pdf->SetTextColor(0,0,0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					// Description of product line
					$curX = $this->posxcomment-1;

					$showpricebeforepagebreak=1;

					$pdf->SetFont('','', $default_font_size - 1);
					
					// Accountancy piece
					$pdf->SetXY($this->posxpiece, $curY);
					$pdf->writeHTMLCell($this->posxcomment-$this->posxpiece-0.8, 4, $this->posxpiece-1, $curY, $piece_comptable, 0, 1);
					
					// Comments
					$pdf->SetXY($this->posxcomment, $curY);
					$pdf->writeHTMLCell($this->posxdate-$this->posxcomment-0.8, 4, $this->posxcomment-1, $curY, $object->lines[$i]->comments, 0, 1);

					//nexY
					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// Date
					$pdf->SetXY($this->posxdate -1, $curY);
					$pdf->MultiCell($this->posxtype-$this->posxdate-0.8, 4, dol_print_date($object->lines[$i]->date,"day",false,$outputlangs), 0, 'C');

					// Type
					$pdf->SetXY($this->posxtype -1, $curY);
					$pdf->MultiCell($this->posxprojet-$this->posxtype-0.8, 4, dol_trunc($outputlangs->transnoentities($object->lines[$i]->type_fees_code), 12), 0, 'C');

                    // Project
					if (! empty($conf->projet->enabled)) 
					{
                        $pdf->SetFont('','', $default_font_size - 1);
                        $pdf->SetXY($this->posxprojet, $curY);
                        $pdf->MultiCell($this->posxtva-$this->posxprojet-0.8, 4, $object->lines[$i]->projet_ref, 0, 'C');
                    }

					// VAT Rate
					if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
					{
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxtva, $curY);
						$pdf->MultiCell($this->posxup-$this->posxtva-0.8, 4,$vat_rate, 0, 'C');
					}

					// Unit price
					$pdf->SetXY($this->posxup, $curY);
					$pdf->MultiCell($this->posxqty-$this->posxup-0.8, 4,price($object->lines[$i]->value_unit), 0, 'R');

					// Quantity
					$pdf->SetXY($this->posxqty, $curY);
					$pdf->MultiCell($this->postotalttc-$this->posxqty-0.8, 4,$object->lines[$i]->qty, 0, 'R');

					// Total with all taxes
					$pdf->SetXY($this->postotalttc-1, $curY);
					$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalttc, 4, price($object->lines[$i]->total_ttc), 0, 'R');

					// Cherche nombre de lignes a venir pour savoir si place suffisante
					if ($i < ($nblignes - 1))	// If it's not last line
					{
						//on recupere la description du produit suivant
						$follow_comment = $object->lines[$i+1]->comments;
						//on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
						$nblineFollowComment = dol_nboflines_bis($follow_comment,52,$outputlangs->charset_output)*4;
					}
					else	// If it's last line
					{
						$nblineFollowComment = 0;
					}

					$nexY+=4;    // Passe espace entre les lignes

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
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
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
						if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
					}

				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}

				$pdf->SetFont('','', 10);
				
            	// Show total area box
				$posy=$bottomlasttab+5;//$nexY+95;
				$pdf->SetXY(100, $posy);
				$pdf->MultiCell(60, 5, $outputlangs->transnoentities("TotalHT"), 1, 'L');
				$pdf->SetXY(160, $posy);
				$pdf->MultiCell($this->page_largeur - $this->marge_gauche - 160, 5, price($object->total_ht), 1, 'R');
				$pdf->SetFillColor(248,248,248);

				if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
				{
				    // TODO Show vat amout per tax level
					$posy+=5;
					$pdf->SetXY(100, $posy);
					$pdf->SetTextColor(0,0,60);
					$pdf->MultiCell(60, 5, $outputlangs->transnoentities("TotalVAT"), 1,'L');
					$pdf->SetXY(160, $posy);
					$pdf->MultiCell($this->page_largeur - $this->marge_gauche - 160, 5, price($object->total_tva),1, 'R');
				}

				$posy+=5;
				$pdf->SetXY(100, $posy);
				$pdf->SetFont('','B', 10);
				$pdf->SetTextColor(0,0,60);
				$pdf->MultiCell(60, 5, $outputlangs->transnoentities("TotalTTC"), 1,'L');
				$pdf->SetXY(160, $posy);
				$pdf->MultiCell($this->page_largeur - $this->marge_gauche - 160, 5, price($object->total_ttc),1, 'R');

				// Pied de page
				$this->_pagefoot($pdf,$object,$outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');

				// Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined","EXPENSEREPORT_OUTPUTDIR");
			return 0;
		}
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf,$langs,$hookmanager;

		$outputlangs->load("main");
		$outputlangs->load("trips");
		$outputlangs->load("companies");
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		/*
		// ajout du fondu vert en bas de page à droite
		$image_fondue = $conf->mycompany->dir_output.'/fondu_vert_.jpg';
		$pdf->Image($image_fondue,20,107,200,190);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);
		*/

	    // Draft watermark
		if ($object->fk_statut == 0 && ! empty($conf->global->EXPENSEREPORT_DRAFT_WATERMARK))
		{
 			pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->EXPENSEREPORT_DRAFT_WATERMARK);
		}

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

		$posy=$this->marge_haute;
		$posx=$this->page_largeur-$this->marge_droite-100;

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
				$pdf->SetFont('','B', $default_font_size -2);
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
			}
		}
		else
		{
			$text=$this->emetteur->name;
			$pdf->MultiCell(100, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
		}

		$pdf->SetFont('','B', $default_font_size + 4);
		$pdf->SetXY($posx,$posy);
   		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell($this->page_largeur-$this->marge_droite-$posx,6,$langs->trans("ExpenseReport"), 0, 'R');

		$pdf->SetFont('','', $default_font_size -1);

   		// Ref complete
   		$posy+=8;
   		$pdf->SetXY($posx,$posy);
   		$pdf->SetTextColor(0,0,60);
   		$pdf->MultiCell($this->page_largeur-$this->marge_droite-$posx, 3, $outputlangs->transnoentities("Ref")." : " . $object->ref, '', 'R');

   		// Date start period
   		$posy+=5;
   		$pdf->SetXY($posx,$posy);
   		$pdf->SetTextColor(0,0,60);
   		$pdf->MultiCell($this->page_largeur-$this->marge_droite-$posx, 3, $outputlangs->transnoentities("DateStart")." : " . ($object->date_debut>0?dol_print_date($object->date_debut,"day",false,$outputlangs):''), '', 'R');

   		// Date end period
   		$posy+=5;
   		$pdf->SetXY($posx,$posy);
   		$pdf->SetTextColor(0,0,60);
   		$pdf->MultiCell($this->page_largeur-$this->marge_droite-$posx, 3, $outputlangs->transnoentities("DateEnd")." : " . ($object->date_fin>0?dol_print_date($object->date_fin,"day",false,$outputlangs):''), '', 'R');

   		// Status Expense Report
   		$posy+=6;
   		$pdf->SetXY($posx,$posy);
   		$pdf->SetFont('','B', $default_font_size + 2);
   		$pdf->SetTextColor(111,81,124);
		$pdf->MultiCell($this->page_largeur-$this->marge_droite-$posx, 3, $object->getLibStatut(0), '', 'R');
		
		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur = '';
			$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->address);
			$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->zip).' '.$outputlangs->convToOutputCharset($this->emetteur->town);
			$carac_emetteur .= "\n";
			// Phone
			if ($this->emetteur->phone) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone")." : ".$outputlangs->convToOutputCharset($this->emetteur->phone);
			// Fax
			if ($this->emetteur->fax) $carac_emetteur .= ($carac_emetteur ? ($this->emetteur->tel ? " - " : "\n") : '' ).$outputlangs->transnoentities("Fax")." : ".$outputlangs->convToOutputCharset($this->emetteur->fax);
			// EMail
			if ($this->emetteur->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email")." : ".$outputlangs->convToOutputCharset($this->emetteur->email);
			// Web
			if ($this->emetteur->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web")." : ".$outputlangs->convToOutputCharset($this->emetteur->url);

			// Show sender
			$posy=50;
			$posx=$this->marge_gauche;
			$hautcadre=40;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=118;

			// Show sender frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
			$pdf->MultiCell(66,5, $outputlangs->transnoentities("TripSociete")." :",'','L');
			$pdf->SetXY($posx,$posy);
			$pdf->SetFillColor(224,224,224);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0,0,60);

			// Show sender name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');

			// Show sender information
			$pdf->SetXY($posx+2,$posy+8);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');

			// Show recipient
			$posy=50;
			$posx=100;

			// Show recipient frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','B',8);
			$pdf->SetXY($posx,$posy-5);
			$pdf->MultiCell(80,5, $outputlangs->transnoentities("TripNDF")." :", 0, 'L');
			$pdf->rect($posx, $posy, $this->page_largeur - $this->marge_gauche - $posx, $hautcadre);

			// Informations for trip (dates and users workflow)
			if ($object->fk_user_author > 0)
			{
				$userfee=new User($this->db);
				$userfee->fetch($object->fk_user_author); $posy+=3;
				$pdf->SetXY($posx+2,$posy);
				$pdf->SetFont('','',10);
				$pdf->MultiCell(96,4,$outputlangs->transnoentities("AUTHOR")." : ".dolGetFirstLastname($userfee->firstname,$userfee->lastname),0,'L');
				$posy+=5;
				$pdf->SetXY($posx+2,$posy);
				$pdf->MultiCell(96,4,$outputlangs->transnoentities("DateCreation")." : ".dol_print_date($object->date_create,"day",false,$outputlangs),0,'L');
			}

			if ($object->fk_statut==99)
			{
				if ($object->fk_user_refuse > 0)
				{
					$userfee=new User($this->db);
					$userfee->fetch($object->fk_user_refuse); $posy+=6;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("REFUSEUR")." : ".dolGetFirstLastname($userfee->firstname,$userfee->lastname),0,'L');
					$posy+=5;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("MOTIF_REFUS")." : ".$outputlangs->convToOutputCharset($object->detail_refuse),0,'L');
					$posy+=5;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("DATE_REFUS")." : ".dol_print_date($object->date_refuse,"day",false,$outputlangs),0,'L');
				}
			}
			else if($object->fk_statut==4)
			{
				if ($object->fk_user_cancel > 0)
				{
					$userfee=new User($this->db);
					$userfee->fetch($object->fk_user_cancel); $posy+=6;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("CANCEL_USER")." : ".dolGetFirstLastname($userfee->firstname,$userfee->lastname),0,'L');
					$posy+=5;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("MOTIF_CANCEL")." : ".$outputlangs->convToOutputCharset($object->detail_cancel),0,'L');
					$posy+=5;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("DATE_CANCEL")." : ".dol_print_date($object->date_cancel,"day",false,$outputlangs),0,'L');
				}
			}
			else
			{
				if ($object->fk_user_approve > 0)
				{
					$userfee=new User($this->db);
					$userfee->fetch($object->fk_user_approve); $posy+=6;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("VALIDOR")." : ".dolGetFirstLastname($userfee->firstname,$userfee->lastname),0,'L');
					$posy+=5;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("DateApprove")." : ".dol_print_date($object->date_approve,"day",false,$outputlangs),0,'L');
				}
			}

			if($object->fk_statut==6)
			{
				if ($object->fk_user_paid > 0)
				{
					$userfee=new User($this->db);
					$userfee->fetch($object->fk_user_paid); $posy+=6;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("AUTHORPAIEMENT")." : ".dolGetFirstLastname($userfee->firstname,$userfee->lastname),0,'L');
					$posy+=5;
					$pdf->SetXY($posx+2,$posy);
					$pdf->MultiCell(96,4,$outputlangs->transnoentities("DATE_PAIEMENT")." : ".dol_print_date($object->date_paiement,"day",false,$outputlangs),0,'L');
				}
			}
		}

   	}

	/**
	 *   Show table for lines
	 *
	 *   @param     PDF			$pdf     		Object PDF
	 *   @param		int			$tab_top		Tab top
	 *   @param		int			$tab_height		Tab height
	 *   @param		int			$nexY			next y
	 *   @param		Translate	$outputlangs	Output langs
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs, $hidetop=0, $hidebottom=0, $currency='')
	{
		global $conf;
		
		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$currency = !empty($currency) ? $currency : $conf->currency;
		$default_font_size = pdf_getPDFFontSize($outputlangs);

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('','', $default_font_size - 2);
		$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$currency));
		$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 4), $tab_top -4);
		$pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

		$pdf->SetDrawColor(128,128,128);

		// Rect prend une longueur en 3eme param
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
		// line prend une position y en 3eme param
		if (empty($hidetop))
		{
			$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);
		}

		$pdf->SetFont('','',8);

		// Accountancy piece
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxpiece-1, $tab_top+1);
			$pdf->MultiCell($this->posxcomment-$this->posxpiece-1,1,'','','R');
		}

		// Comments
		$pdf->line($this->posxcomment-1, $tab_top, $this->posxcomment-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxcomment-1, $tab_top+1);
			$pdf->MultiCell($this->posxdate-$this->posxcomment-1,1,$outputlangs->transnoentities("Description"),'','L');
		}

		// Date
		$pdf->line($this->posxdate-1, $tab_top, $this->posxdate-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxdate-1, $tab_top+1);
			$pdf->MultiCell($this->posxtype-$this->posxdate-1,2, $outputlangs->transnoentities("Date"),'','C');
		}

		// Type
		$pdf->line($this->posxtype-1, $tab_top, $this->posxtype-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxtype-1, $tab_top+1);
			$pdf->MultiCell($this->posxprojet-$this->posxtype - 1, 2, $outputlangs->transnoentities("Type"), '', 'C');
		}

        if (!empty($conf->projet->enabled)) 
        {
            // Project
            $pdf->line($this->posxprojet - 1, $tab_top, $this->posxprojet - 1, $tab_top + $tab_height);
    		if (empty($hidetop))
    		{
                $pdf->SetXY($this->posxprojet - 1, $tab_top + 1);
                $pdf->MultiCell($this->posxtva - $this->posxprojet - 1, 2, $outputlangs->transnoentities("Project"), '', 'C');
    		}
        }

		// VAT
		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
			$pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
			if (empty($hidetop))
			{
				$pdf->SetXY($this->posxtva-1, $tab_top+1);
				$pdf->MultiCell($this->posxup-$this->posxtva - 1, 2, $outputlangs->transnoentities("VAT"), '', 'C');
			}
		}

        // Unit price
		$pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxup-1, $tab_top+1);
			$pdf->MultiCell($this->posxqty-$this->posxup-1,2, $outputlangs->transnoentities("PriceU"),'','C');
		}

		// Quantity
		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->posxqty-1, $tab_top+1);
			$pdf->MultiCell($this->postotalttc-$this->posxqty - 1,2, $outputlangs->transnoentities("Qty"),'','R');
		}

		// Total with all taxes
		$pdf->line($this->postotalttc, $tab_top, $this->postotalttc, $tab_top + $tab_height);
		if (empty($hidetop))
		{
			$pdf->SetXY($this->postotalttc-1, $tab_top+1);
			$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalttc, 2, $outputlangs->transnoentities("TotalTTC"),'','R');
		}

		$pdf->SetTextColor(0,0,0);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		global $conf;
		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf,$outputlangs,'EXPENSEREPORT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}

}

