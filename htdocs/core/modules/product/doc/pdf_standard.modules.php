<?php
/* Copyright (C) 2017 	Laurent Destailleur <eldy@products.sourceforge.net>
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
 *	\file       htdocs/core/modules/product/doc/pdf_standard.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build PDF documents for products/services
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/product/modules_product.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class pdf_standard extends ModelePDFProduct
{
    /**
     * @var DoliDb Database handler
     */
    public $db;

	/**
     * @var string model name
     */
    public $name;

	/**
     * @var string model description (short text)
     */
    public $description;

	/**
     * @var string document type
     */
    public $type;

	/**
     * @var array() Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.3 = array(5, 3)
     */
	public $phpmin = array(5, 2);

	/**
     * Dolibarr version of the loaded document
     * @public string
     */
	public $version = 'dolibarr';

    public $page_largeur;
    public $page_hauteur;
    public $format;
	public $marge_gauche;
	public $marge_droite;
	public $marge_haute;
	public $marge_basse;

    public $emetteur;	// Objet societe qui emet


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("companies");

		$this->db = $db;
		$this->name = "standard";
		$this->description = $langs->trans("DocumentModelStandardPDF");

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
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_freetext = 0;				   // Support add of a personalised text

		// Recupere emetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default if not defined
	}


	/**
	 *	Function to build a document on disk using the generic odt module.
	 *
	 *	@param		Product		$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath,$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc,$db,$hookmanager;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("orders");
		$outputlangs->load("deliveries");

		$nblignes = count($object->lines);

		if ($conf->produit->dir_output)
		{
		    // Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->produit->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->produit->dir_output . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}

			$productFournisseur = new ProductFournisseur($this->db);
			$supplierprices = $productFournisseur->list_product_fournisseur_price($object->id);
			$object->supplierprices = $supplierprices;

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return -1;
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

				// Create pdf instance
				$pdf=pdf_getInstance($this->format);
				$default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
				$pdf->SetAutoPageBreak(1,0);

				$heightforinfotot = 40;	// Height reserved to output the info and total part
		        $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + 8;	// Height reserved to output the footer (value include bottom margin)
	            if ($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS >0) $heightforfooter+= 6;

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
				$pdf->SetSubject($outputlangs->transnoentities("Order"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("Order")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
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


				$tab_top = 42;
				$tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42:10);
				$tab_height = 130;
				$tab_height_newpage = 150;

				//
				$pdf->SetFont('','B', $default_font_size);
				$pdf->writeHTMLCell(190, 3, $this->marge_gauche, $tab_top, dol_htmlentitiesbr($object->label), 0, 1);
				$nexY = $pdf->GetY();

				$pdf->SetFont('','', $default_font_size);
				$pdf->writeHTMLCell(190, 3, $this->marge_gauche, $nexY, dol_htmlentitiesbr($object->description), 0, 1);
				$nexY = $pdf->GetY();

				$nexY+=5;

				if ($object->weight)
				{
				    $pdf->writeHTMLCell(190, 3, $this->marge_gauche, $nexY, $langs->trans("Weight").': '.dol_htmlentitiesbr($object->weight), 0, 1);
				    $nexY = $pdf->GetY();
				}
				if ($object->weight)
				{
				    $pdf->writeHTMLCell(190, 3, $this->marge_gauche, $nexY, $langs->trans("Length").' x '.$langs->trans("Width").' x '.$langs->trans("Height").': '.($object->length != ''?$object->length:'?').' x '.($object->width != ''?$object->width:'?').' x '.($object->height != ''?$object->height:'?'), 0, 1);
				    $nexY = $pdf->GetY();
				}
				if ($object->surface)
				{
				    $pdf->writeHTMLCell(190, 3, $this->marge_gauche, $nexY, $langs->trans("Area").': '.dol_htmlentitiesbr($object->surface), 0, 1);
				    $nexY = $pdf->GetY();
				}
				if ($object->volume)
				{
    				$pdf->writeHTMLCell(190, 3, $this->marge_gauche, $nexY, $langs->trans("Volume").': '.dol_htmlentitiesbr($object->volume), 0, 1);
	   			    $nexY = $pdf->GetY();
				}


				// Affiche notes
				// TODO There is no public note on product yet
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
					$substitutionarray=pdf_getSubstitutionArray($outputlangs, null, $object);
					complete_substitutions_array($substitutionarray, $outputlangs, $object);
					$notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);

					$tab_top = 88;

					$pdf->SetFont('','', $default_font_size - 1);
					$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
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
				/*
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
					$pdf->SetTextColor(0,0,0);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					// Description of product line
					$curX = $this->posxdesc-1;

					$showpricebeforepagebreak=1;

					$pdf->startTransaction();
					pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxtva-$curX,3,$curX,$curY,$hideref,$hidedesc);
					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->posxtva-$curX,4,$curX,$curY,$hideref,$hidedesc);
						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
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

					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();
					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description is moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('','',  $default_font_size - 1);   // On repositionne la police par defaut

					// VAT Rate
					if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN))
					{
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxtva, $curY);
						$pdf->MultiCell($this->posxup-$this->posxtva-0.8, 3, $vat_rate, 0, 'R');
					}

					// Unit price before discount
					$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posxup, $curY);
					$pdf->MultiCell($this->posxqty-$this->posxup-0.8, 3, $up_excl_tax, 0, 'R', 0);

					// Quantity
					$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->posxqty, $curY);
					// Enough for 6 chars
					if($conf->global->PRODUCT_USE_UNITS)
					{
						$pdf->MultiCell($this->posxunit-$this->posxqty-0.8, 4, $qty, 0, 'R');
					}
					else
					{
						$pdf->MultiCell($this->posxdiscount-$this->posxqty-0.8, 4, $qty, 0, 'R');
					}

					// Unit
					if($conf->global->PRODUCT_USE_UNITS)
					{
						$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($this->posxunit, $curY);
						$pdf->MultiCell($this->posxdiscount-$this->posxunit-0.8, 4, $unit, 0, 'L');
					}

					// Discount on line
					$pdf->SetXY($this->posxdiscount, $curY);
					if ($object->lines[$i]->remise_percent)
					{
						$pdf->SetXY($this->posxdiscount-2, $curY);
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->postotalht-$this->posxdiscount+2, 3, $remise_percent, 0, 'R');
					}

					// Total HT line
					$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
					$pdf->SetXY($this->postotalht, $curY);
					$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalht, 3, $total_excl_tax, 0, 'R', 0);

					// Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
					if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne=$object->lines[$i]->multicurrency_total_tva;
					else $tvaligne=$object->lines[$i]->total_tva;

					$localtax1ligne=$object->lines[$i]->total_localtax1;
					$localtax2ligne=$object->lines[$i]->total_localtax2;
					$localtax1_rate=$object->lines[$i]->localtax1_tx;
					$localtax2_rate=$object->lines[$i]->localtax2_tx;
					$localtax1_type=$object->lines[$i]->localtax1_type;
					$localtax2_type=$object->lines[$i]->localtax2_type;

					if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax1ligne-=($localtax1ligne*$object->remise_percent)/100;
					if ($object->remise_percent) $localtax2ligne-=($localtax2ligne*$object->remise_percent)/100;

					$vatrate=(string) $object->lines[$i]->tva_tx;

					// Retrieve type from database for backward compatibility with old records
					if ((! isset($localtax1_type) || $localtax1_type=='' || ! isset($localtax2_type) || $localtax2_type=='') // if tax type not defined
					&& (! empty($localtax1_rate) || ! empty($localtax2_rate))) // and there is local tax
					{
						$localtaxtmp_array=getLocalTaxesFromRate($vatrate,0,$object->thirdparty,$mysoc);
						$localtax1_type = $localtaxtmp_array[0];
						$localtax2_type = $localtaxtmp_array[2];
					}

				    // retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0)
						$this->localtax1[$localtax1_type][$localtax1_rate]+=$localtax1ligne;
					if ($localtax2_type && $localtax2ligne != 0)
						$this->localtax2[$localtax2_type][$localtax2_rate]+=$localtax2ligne;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
					if (! isset($this->tva[$vatrate])) 				$this->tva[$vatrate]=0;
					$this->tva[$vatrate] += $tvaligne;

					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1))
					{
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(80,80,80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $nexY+1, $this->page_largeur - $this->marge_droite, $nexY+1);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY+=2;    // Passe espace entre les lignes

					// Detect if some page were added automatically and output _tableau for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
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
							$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforfooter, 0, $outputlangs, 0, 1, $object->multicurrency_code);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforfooter, 0, $outputlangs, 1, 1, $object->multicurrency_code);
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
					$this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
				}
                */

				// Affiche zone infos
				//$posy=$this->_tableau_info($pdf, $object, $bottomlasttab, $outputlangs);

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

				$this->result = array('fullpath'=>$file);

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
			$this->error=$langs->trans("ErrorConstantNotDefined","PRODUCT_OUTPUTDIR");
			return 0;
		}
	}


	/**
	 *   Show table for lines
	 *
	 *   @param		TCPDF		$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
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

	    if (empty($hidetop))
	    {
	        $titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$currency));
	        $pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 3), $tab_top-4);
	        $pdf->MultiCell(($pdf->GetStringWidth($titre) + 3), 2, $titre);

	        //$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR='230,230,230';
	        if (! empty($conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR)) $pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_droite-$this->marge_gauche, 5, 'F', null, explode(',',$conf->global->MAIN_PDF_TITLE_BACKGROUND_COLOR));
	    }

	    $pdf->SetDrawColor(128,128,128);
	    $pdf->SetFont('','', $default_font_size - 1);

	    // Output Rect
	    $this->printRect($pdf,$this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height, $hidetop, $hidebottom);	// Rect prend une longueur en 3eme param et 4eme param

	    if (empty($hidetop))
	    {
	        $pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);	// line prend une position y en 2eme param et 4eme param

	        $pdf->SetXY($this->posxdesc-1, $tab_top+1);
	        $pdf->MultiCell(108,2, $outputlangs->transnoentities("Designation"),'','L');
	    }

	    if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN))
	    {
	        $pdf->line($this->posxtva-1, $tab_top, $this->posxtva-1, $tab_top + $tab_height);
	        if (empty($hidetop))
	        {
	            $pdf->SetXY($this->posxtva-3, $tab_top+1);
	            $pdf->MultiCell($this->posxup-$this->posxtva+3,2, $outputlangs->transnoentities("VAT"),'','C');
	        }
	    }

	    $pdf->line($this->posxup-1, $tab_top, $this->posxup-1, $tab_top + $tab_height);
	    if (empty($hidetop))
	    {
	        $pdf->SetXY($this->posxup-1, $tab_top+1);
	        $pdf->MultiCell($this->posxqty-$this->posxup-1,2, $outputlangs->transnoentities("PriceUHT"),'','C');
	    }

	    $pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
	    if (empty($hidetop))
	    {
	        $pdf->SetXY($this->posxqty-1, $tab_top+1);
	        if($conf->global->PRODUCT_USE_UNITS)
	        {
	            $pdf->MultiCell($this->posxunit-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');
	        }
	        else
	        {
	            $pdf->MultiCell($this->posxdiscount-$this->posxqty-1,2, $outputlangs->transnoentities("Qty"),'','C');
	        }
	    }

	    if($conf->global->PRODUCT_USE_UNITS) {
	        $pdf->line($this->posxunit - 1, $tab_top, $this->posxunit - 1, $tab_top + $tab_height);
	        if (empty($hidetop)) {
	            $pdf->SetXY($this->posxunit - 1, $tab_top + 1);
	            $pdf->MultiCell($this->posxdiscount - $this->posxunit - 1, 2, $outputlangs->transnoentities("Unit"), '',
	                'C');
	        }
	    }

	    $pdf->line($this->posxdiscount-1, $tab_top, $this->posxdiscount-1, $tab_top + $tab_height);
	    if (empty($hidetop))
	    {
	        if ($this->atleastonediscount)
	        {
	            $pdf->SetXY($this->posxdiscount-1, $tab_top+1);
	            $pdf->MultiCell($this->postotalht-$this->posxdiscount+1,2, $outputlangs->transnoentities("ReductionShort"),'','C');
	        }
	    }

	    if ($this->atleastonediscount)
	    {
	        $pdf->line($this->postotalht, $tab_top, $this->postotalht, $tab_top + $tab_height);
	    }
	    if (empty($hidetop))
	    {
	        $pdf->SetXY($this->postotalht-1, $tab_top+1);
	        $pdf->MultiCell(30,2, $outputlangs->transnoentities("TotalHT"),'','C');
	    }
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	TCPDF		$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @param	string		$titlekey		Translation key to show as title of document
	 *  @return	void
	 */
	function _pagehead(&$pdf, $object, $showaddress, $outputlangs, $titlekey="")
	{
	    global $conf,$langs,$hookmanager;

	    $outputlangs->load("main");
	    $outputlangs->load("bills");
	    $outputlangs->load("propal");
	    $outputlangs->load("companies");
	    $outputlangs->load("orders");
	    $default_font_size = pdf_getPDFFontSize($outputlangs);

	    if ($object->type == 1) $titlekey='ServiceSheet';
	    else $titlekey='ProductSheet';

	    pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

	    // Show Draft Watermark
	    if($object->statut==0 && (! empty($conf->global->COMMANDE_DRAFT_WATERMARK)) )
	    {
	        pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->COMMANDE_DRAFT_WATERMARK);
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

	    $pdf->SetFont('','B', $default_font_size + 3);
	    $pdf->SetXY($posx,$posy);
	    $pdf->SetTextColor(0,0,60);
	    $title=$outputlangs->transnoentities($titlekey);
	    $pdf->MultiCell(100, 3, $title, '', 'R');

	    $pdf->SetFont('','B',$default_font_size);

	    $posy+=5;
	    $pdf->SetXY($posx,$posy);
	    $pdf->SetTextColor(0,0,60);
	    $pdf->MultiCell(100, 4, $outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref), '', 'R');

	    $posy+=1;
	    $pdf->SetFont('','', $default_font_size - 1);

	    /*if ($object->ref_client)
	    {
	        $posy+=5;
	        $pdf->SetXY($posx,$posy);
	        $pdf->SetTextColor(0,0,60);
	        $pdf->MultiCell(100, 3, $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset($object->ref_client), '', 'R');
	    }*/

	    /*$posy+=4;
	    $pdf->SetXY($posx,$posy);
	    $pdf->SetTextColor(0,0,60);
	    $pdf->MultiCell(100, 3, $outputlangs->transnoentities("OrderDate")." : " . dol_print_date($object->date,"%d %b %Y",false,$outputlangs,true), '', 'R');
	    */

	    // Get contact
	    /*
	    if (!empty($conf->global->DOC_SHOW_FIRST_SALES_REP))
	    {
	        $arrayidcontact=$object->getIdContact('internal','SALESREPFOLL');
	        if (count($arrayidcontact) > 0)
	        {
	            $usertmp=new User($this->db);
	            $usertmp->fetch($arrayidcontact[0]);
	            $posy+=4;
	            $pdf->SetXY($posx,$posy);
	            $pdf->SetTextColor(0,0,60);
	            $pdf->MultiCell(100, 3, $langs->trans("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
	        }
	    }*/

	    $posy+=2;

	    // Show list of linked objects
	    $posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, 100, 3, 'R', $default_font_size);

	    if ($showaddress)
	    {
	        /*
	        // Sender properties
	        $carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty);

	        // Show sender
	        $posy=42;
	        $posx=$this->marge_gauche;
	        if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;
	        $hautcadre=40;

	        // Show sender frame
	        $pdf->SetTextColor(0,0,0);
	        $pdf->SetFont('','', $default_font_size - 2);
	        $pdf->SetXY($posx,$posy-5);
	        $pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
	        $pdf->SetXY($posx,$posy);
	        $pdf->SetFillColor(230,230,230);
	        $pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);
	        $pdf->SetTextColor(0,0,60);

	        // Show sender name
	        $pdf->SetXY($posx+2,$posy+3);
	        $pdf->SetFont('','B', $default_font_size);
	        $pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
	        $posy=$pdf->getY();

	        // Show sender information
	        $pdf->SetXY($posx+2,$posy);
	        $pdf->SetFont('','', $default_font_size - 1);
	        $pdf->MultiCell(80, 4, $carac_emetteur, 0, 'L');
	        */
	    }

	    $pdf->SetTextColor(0,0,0);
	}

	/**
	 *   	Show footer of page. Need this->emetteur object
	 *
	 *   	@param	TCPDF		$pdf     			PDF
	 * 		@param	Object		$object				Object to show
	 *      @param	Translate	$outputlangs		Object lang for output
	 *      @param	int			$hidefreetext		1=Hide free text
	 *      @return	int								Return height of bottom margin including footer text
	 */
	function _pagefoot(&$pdf,$object,$outputlangs,$hidefreetext=0)
	{
	    global $conf;
	    $showdetails=$conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS;
	    return pdf_pagefoot($pdf,$outputlangs,'PRODUCT_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}

}

