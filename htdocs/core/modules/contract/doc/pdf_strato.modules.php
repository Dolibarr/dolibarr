<?php
/* Copyright (C) 2003		Rodolphe Quiedeville		<rodolphe@quiedeville.org>
 * Copyright (C) 2004-2010	Laurent Destailleur			<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin				<regis.houssin@capnetworks.com>
 * Copyright (C) 2008		Raphael Bertrand (Resultic)	<raphael.bertrand@resultic.fr>
 * Copyright (C) 2011		Fabrice CHERRIER
 * Copyright (C) 2013-2018  Philippe Grand	            <philippe.grand@atoo-net.com>
 * Copyright (C) 2015       Marcos García               <marcosgdf@gmail.com>
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
 *	\file       htdocs/core/modules/contract/doc/pdf_strato.modules.php
 *	\ingroup    ficheinter
 *	\brief      Strato contracts template class file
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/contract/modules_contract.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


/**
 *	Class to build contracts documents with model Strato
 */
class pdf_strato extends ModelePDFContract
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
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * Recipient
	 * @var Societe
	 */
	public $recipient;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$this->db = $db;
		$this->name = 'strato';
		$this->description = $langs->trans("StandardContractsTemplate");

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
		$this->option_tva = 0;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 0;                 // Affiche mode reglement
		$this->option_condreg = 0;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 0;               // Dispo en plusieurs langues
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if not defined

		// Define position of columns
		$this->posxdesc=$this->marge_gauche+1;
	}

	/**
     *  Function to build pdf onto disk
     *
     *  @param		CommonObject	$object				Id of object to generate
     *  @param		object			$outputlangs		Lang output object
     *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int				$hidedetails		Do not show line details
     *  @param		int				$hidedesc			Do not show desc
     *  @param		int				$hideref			Do not show ref
     *  @return		int									1=OK, 0=KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$hookmanager,$mysoc;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';
		
		// Translations
		$outputlangs->loadLangs(array("main", "dict", "companies", "contracts"));

		if ($conf->contrat->dir_output)
		{
            $object->fetch_thirdparty();

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->contrat->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->contrat->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}

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

                $pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
                $heightforinfotot = 50;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
	            if ($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS >0) $heightforfooter+= 6;
                $pdf->SetAutoPageBreak(1,0);

                if (class_exists('TCPDF'))
                {
                    $pdf->setPrintHeader(false);
                    $pdf->setPrintFooter(false);
                }
                $pdf->SetFont(pdf_getPDFFont($outputlangs));
                // Set path to the background PDF File
                if (! empty($conf->global->MAIN_ADD_PDF_BACKGROUND))
                {
                    $pagecount = $pdf->setSourceFile($conf->mycompany->dir_output.'/'.$conf->global->MAIN_ADD_PDF_BACKGROUND);
                    $tplidx = $pdf->importPage(1);
                }

				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("ContractCard"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("ContractCard")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
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

				$tab_top = 90;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);

				// Affiche notes
				if (! empty($object->note_public))
				{
					$tab_top -= 2;

					$pdf->SetFont('','', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top-1, dol_htmlentitiesbr($object->note_public), 0, 1);
					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192,192,192);
					$pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

					$tab_top = $nexY+6;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 2;

				$pdf->SetXY($this->marge_gauche, $tab_top);

				$pdf->MultiCell(0, 2, '');		// Set interline to 3. Then writeMultiCell must use 3 also.

				$nblines = count($object->lines);

				// Loop on each lines
				for ($i = 0; $i < $nblines; $i++)
				{
					$objectligne = $object->lines[$i];

					$valide = $objectligne->id ? 1 : 0;

					if ($valide > 0 || $object->specimen)
					{
						$curX = $this->posxdesc-1;
						$curY = $nexY;
						$pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
						$pdf->SetTextColor(0,0,0);

						$pdf->setTopMargin($tab_top_newpage);
						$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
						$pageposbefore=$pdf->getPage();

						// Description of product line

						if ($objectligne->date_ouverture_prevue) {
							$datei = dol_print_date($objectligne->date_ouverture_prevue,'day',false,$outputlangs,true);
						} else {
							$datei = $langs->trans("Unknown");
						}

						if ($objectligne->date_fin_validite) {
							$durationi = convertSecondToTime($objectligne->date_fin_validite - $objectligne->date_ouverture_prevue, 'allwithouthour');
							$datee = dol_print_date($objectligne->date_fin_validite,'day',false,$outputlangs,true);
						} else {
							$durationi = $langs->trans("Unknown");
							$datee = $langs->trans("Unknown");
						}

						if ($objectligne->date_ouverture) {
							$daters = dol_print_date($objectligne->date_ouverture,'day',false,$outputlangs,true);
						} else {
							$daters = $langs->trans("Unknown");
						}

						if ($objectligne->date_cloture) {
							$datere = dol_print_date($objectligne->date_cloture,'day',false,$outputlangs,true);
						} else {
							$datere = $langs->trans("Unknown");
						}

						$txtpredefinedservice='';
                        $txtpredefinedservice = $objectligne->product_label;
                        if ($objectligne->product_label)
                        {
                        	$txtpredefinedservice .= ' - ';
                        	$txtpredefinedservice .= $objectligne->product_label;
                        }

						$desc=dol_htmlentitiesbr($objectligne->desc,1);   // Desc (not empty for free lines)
						$txt='';
						$txt.=$outputlangs->transnoentities("Quantity").' : <strong>'.$objectligne->qty.'</strong> - '.$outputlangs->transnoentities("UnitPrice").' : <strong>'.price($objectligne->subprice).'</strong>';   // Desc (not empty for free lines)
						$txt.='<br>';
						$txt.=$outputlangs->transnoentities("DateStartPlannedShort")." : <strong>".$datei."</strong> - ".$outputlangs->transnoentities("DateEndPlanned")." : <strong>".$datee.'</strong>';
						$txt.='<br>';
                        $txt.=$outputlangs->transnoentities("DateStartRealShort")." : <strong>".$daters.'</strong>';
						if ($objectligne->date_cloture) $txt.=" - ".$outputlangs->transnoentities("DateEndRealShort")." : '<strong>'".$datere.'</strong>';

						$pdf->startTransaction();
						$pdf->writeHTMLCell(0, 0, $curX, $curY, dol_concatdesc($txtpredefinedservice, dol_concatdesc($txt, $desc)), 0, 1, 0);
						$pageposafter=$pdf->getPage();
						if ($pageposafter > $pageposbefore)	// There is a pagebreak
						{
							$pdf->rollbackTransaction(true);
							$pageposafter=$pageposbefore;
							//print $pageposafter.'-'.$pageposbefore;exit;
							$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
							$pdf->writeHTMLCell(0, 0, $curX, $curY, dol_concatdesc($txtpredefinedservice, dol_concatdesc($txt, $desc)), 0, 1, 0);
							$pageposafter=$pdf->getPage();
							$posyafter=$pdf->GetY();
							
							if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
							{
								if ($i == ($nblines-1))	// No more lines, and no space left to show total, so we create a new page
								{
									$pdf->AddPage('','',true);
									if (! empty($tplidx)) $pdf->useTemplate($tplidx);
									if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
									$pdf->setPage($pageposafter+1);
								}
							}
							else
							{
								// We found a page break
								$showpricebeforepagebreak=0;
							}
						}
						else	// No pagebreak
						{
							$pdf->commitTransaction();
						}

						$nexY = $pdf->GetY() + 2;
						$pageposafter=$pdf->getPage();
						
						$pdf->setPage($pageposbefore);
						$pdf->setTopMargin($this->marge_haute);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

						// We suppose that a too long description is moved completely on next page
						if ($pageposafter > $pageposbefore) {
							$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
						}

						$pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

						// Detect if some page were added automatically and output _tableau for past pages
						while ($pagenb < $pageposafter)
						{
							$pdf->setPage($pagenb);
							if ($pagenb == 1)
							{
								$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext, 0, $outputlangs, 0, 1);
							}
							else
							{
								$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter - $heightforfreetext, 0, $outputlangs, 1, 1);
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
								$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter - $heightforfreetext, 0, $outputlangs, 0, 1);
							}
							else
							{
								$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter - $heightforfreetext, 0, $outputlangs, 1, 1);
							}
							$this->_pagefoot($pdf,$object,$outputlangs,1);
							// New page
							$pdf->AddPage();
							if (! empty($tplidx)) $pdf->useTemplate($tplidx);
							$pagenb++;
						}
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$this->_tab_signature($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, $outputlangs);
					$bottomlasttab=$this->page_hauteur - $heightforfooter - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0);
					$this->_tab_signature($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, $outputlangs);
					$bottomlasttab=$this->page_hauteur - $heightforfooter - $heightforfooter + 1;
				}
				
				$this->_pagefoot($pdf,$object,$outputlangs);
				if (method_exists($pdf,'AliasNbPages')) $pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file,'F');

				// Add pdfgeneration hook
				if (! is_object($hookmanager))
				{
					include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
					$hookmanager=new HookManager($this->db);
				}
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath'=>$file);

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
			$this->error=$langs->trans("ErrorConstantNotDefined","CONTRACT_OUTPUTDIR");
			return 0;
		}
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
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
		global $conf;

		// Force to disable hidetop and hidebottom
		$hidebottom=0;
		if ($hidetop) $hidetop=-1;

		$default_font_size = pdf_getPDFFontSize($outputlangs);
/*
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
*/

		// Output Rect
		$this->printRect($pdf, $this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height+3);	// Rect prend une longueur en 3eme param et 4eme param
	}

	function _tab_signature(&$pdf, $tab_top, $tab_height, $outputlangs) {
		$pdf->SetDrawColor(128,128,128);
		$posmiddle = $this->marge_gauche + round(($this->page_largeur - $this->marge_gauche - $this->marge_droite)/2);
		$posy = $tab_top + $tab_height + 3 + 3;

		$pdf->SetXY($this->marge_gauche, $posy);
		$pdf->MultiCell($posmiddle - $this->marge_gauche - 5, 5, $outputlangs->transnoentities("ContactNameAndSignature", $this->emetteur->name),0,'L',0);

		$pdf->SetXY($this->marge_gauche, $posy + 5);
		$pdf->MultiCell($posmiddle - $this->marge_gauche - 5, 20, '', 1);

		$pdf->SetXY($posmiddle + 5, $posy);
		$pdf->MultiCell($this->page_largeur-$this->marge_droite - $posmiddle - 5, 5, $outputlangs->transnoentities("ContactNameAndSignature", $this->recipient->name),0,'L',0);

		$pdf->SetXY($posmiddle + 5, $posy + 5);
		$pdf->MultiCell($this->page_largeur-$this->marge_droite - $posmiddle - 5, 20, '', 1);
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  CommonObject		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		global $conf,$langs;
		
		$default_font_size = pdf_getPDFFontSize($outputlangs);
		
		// Translations
		$outputlangs->loadLangs(array("main", "dict", "contract", "companies"));

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		//Affiche le filigrane brouillon - Print Draft Watermark
		if($object->statut==0 && (! empty($conf->global->CONTRACT_DRAFT_WATERMARK)) )
		{
			pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->CONTRACT_DRAFT_WATERMARK);
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
			    $height=pdf_getHeightForLogo($logo);
			    $pdf->Image($logo, $this->marge_gauche, $posy, 0, $height);	// width=0 (auto)
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
		$title=$outputlangs->transnoentities("ContractCard");
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
		$pdf->MultiCell(100, 3, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date_contrat,"day",false,$outputlangs,true), '', 'R');

		if ($object->thirdparty->code_client)
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
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

			$carac_emetteur .= pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

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
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy);
			$pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');


			// If CUSTOMER contact defined, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external','CUSTOMER');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

			$this->recipient = $object->thirdparty;

			//Recipient name
			// On peut utiliser le nom de la societe du contact
			if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$this->recipient->name = pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$carac_client=pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, (isset($object->contact)?$object->contact:''), $usecontact, 'target', $object);

			// Show recipient
			$widthrecbox=100;
			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
			$posy=42;
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx+2,$posy-5);
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);
			$pdf->SetTextColor(0,0,0);

			// Show recipient name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 4, $this->recipient->name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
     *
	 *   	@param	PDF			$pdf     			PDF
	 * 		@param	CommonObject		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	integer
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
		global $conf;
		$showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
		return pdf_pagefoot($pdf,$outputlangs,'CONTRACT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}

}

