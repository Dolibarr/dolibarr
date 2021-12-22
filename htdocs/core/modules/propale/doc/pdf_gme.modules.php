<?php
/* Copyright (C) 2004-2014 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2008      Raphael Bertrand     <raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2015 Juanjo Menent        <jmenent@2byte.es>
 * Copyright (C) 2012      Christophe Battarel  <christophe.battarel@altairis.fr>
 * Copyright (C) 2012      Cedric Salvador      <csalvador@gpcsolutions.fr>
 * Copyright (C) 2015      Marcos García        <marcosgdf@gmail.com>
 * Copyright (C) 2017-2018 Ferran Marcet        <fmarcet@2byte.es>
 * Copyright (C) 2018      Frédéric France      <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/modules/propale/doc/pdf_gme.modules.php
 *	\ingroup    propale
 *	\brief      Fichier de la classe permettant de generer les propales au modele Azur avec :
					Sous-total => si <h et qty = 0 ;
 					Commentaire => si <div> et qty = 0 => commentaire
 */
require_once DOL_DOCUMENT_ROOT.'/core/modules/propale/modules_propale.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';


/**
 *	Class to generate PDF proposal Azur
 */
class pdf_gme extends ModelePDFPropales
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
     * @var string Save the name of generated file as the main doc when generating a doc with this template
     */
	public $update_main_doc_field;

	/**
     * @var string document type
     */
    public $type;

    /**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
	public $phpmin = array(5, 5);

	/**
     * Dolibarr version of the loaded document
     * @var string
     */
	public $version = 'dolibarr';

	/**
     * @var int page_largeur
     */
    public $page_largeur;

	/**
     * @var int page_hauteur
     */
    public $page_hauteur;

	/**
     * @var array format
     */
    public $format;

	/**
     * @var int marge_gauche
     */
	public $marge_gauche;

	/**
     * @var int marge_droite
     */
	public $marge_droite;

	/**
     * @var int marge_haute
     */
	public $marge_haute;

	/**
     * @var int marge_basse
     */
	public $marge_basse;

	/**
	 * Issuer
	 * @var Societe object that emits
	 */
	public $emetteur;

	/**
	 * @var Image Page de Garde
	 */
	public $urlImageGme;

	/**
	 * @var Image Header
	 */
	public $urlImageGmeHeader;

	/**
	 * @var Logo mail
	 */
	public $urlPictoMail;

	/**
	 * @var Logo phone
	 */
	public $urlPictoPhone;

	public $contentLeftMargin;
	public $contentFontSize;
	public $titleFontSize;
	public $subTitleFontSize;
	public $footerContent;
	public $user;
	public $contentHeaderCustomerMarginLeft;
	public $contentHeaderCustomerMarginTop;
	public $tableFontSize;
	public $tableSubTotalsFontSize;

	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf,$langs,$mysoc, $user;

		//Variable Image
		$this->urlImageGmeHeader = DOL_DATA_ROOT.'/images/gme_header.png';
		$this->urlImageGme = DOL_DATA_ROOT.'/images/gme.jpg';
		$this->urlPictoMail = DOL_DATA_ROOT.'/images/mail.png';
		$this->urlPictoPhone = DOL_DATA_ROOT.'/images/phone.png';

		// Global vars for the GME doc.
		$this->contentLeftMargin = 20;
		$this->contentFontSize = 10;
		$this->titleFontSize = 14;
		$this->subTitleFontSize = 11;
		$this->footerContent = 'G.M.Electronics SRL - Rue de Termonde, 140 - 1083 Ganshoren - www.gmelectronics.be – info@gmelectronics.be BE0426.751.795';
		$this->contentHeaderCustomerMarginLeft = 118;
		$this->contentHeaderCustomerMarginTop = 18;
		$this->marge_basse = 40;
		$this->marge_haute = 55;
		$this->tableFontSize = 8;
		$this->tableSubTotalsFontSize = 12;

		// Translations
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "gme";
		$this->description = $langs->trans('DocModelAzurDescription');
		$this->update_main_doc_field = 1;		// Save the name of generated file as the main doc when generating a doc with this template

		// Dimension page
		$this->type = 'pdf';
		$formatarray=pdf_getFormat();
		$this->page_largeur = $formatarray['width'];
		$this->page_hauteur = $formatarray['height'];
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=isset($conf->global->MAIN_PDF_MARGIN_LEFT)?$conf->global->MAIN_PDF_MARGIN_LEFT+10:20;
		$this->marge_droite=isset($conf->global->MAIN_PDF_MARGIN_RIGHT)?$conf->global->MAIN_PDF_MARGIN_RIGHT+10:20;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_modereg = 1;                 // Affiche mode reglement
		$this->option_condreg = 1;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 1;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 0;                // Affiche si il y a eu escompte
		$this->option_credit_note = 0;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   //Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang, -2);    // By default, if was not defined

		// Define position of columns
		$this->posxdesc=$this->marge_gauche+1;
		if($conf->global->PRODUCT_USE_UNITS)
		{
			$this->posxtva=99;
			$this->posxup=113;
			$this->posxqty=129;
			$this->posxunit=141;
		}
		else
		{
			$this->posxtva=108;
			$this->posxup=121;
			$this->posxqty=139;
			$this->posxunit=152;
		}
		$this->posxdiscount=152;
		$this->postotalht=164;
		if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) || ! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN)) $this->posxtva=$this->posxup;
		$this->posxpicture=$this->posxtva - (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?20:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH);	// width of images
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$this->posxpicture-=20;
			$this->posxtva-=20;
			$this->posxup-=20;
			$this->posxqty-=20;
			$this->posxunit-=20;
			$this->posxdiscount-=20;
			$this->postotalht-=20;
		}

		$this->tva=array();
		$this->localtax1=array();
		$this->localtax2=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps

	/**
	 *  Function to build pdf onto disk
	 *
	 * @param Object $object Object to generate
	 * @param Translate $outputlangs Lang output object
	 * @param $posY
	 * @param string $srctemplatepath Full path of source filename for generator using a template file
	 * @param int $hidedetails Do not show line details
	 * @param int $hidedesc Do not show desc
	 * @param int $hideref Do not show ref
	 * @return     int                            1=OK, 0=KO
	 */
	public function write_file($object, $outputlangs, $posY, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
        // phpcs:enable
		global $user,$langs,$conf,$mysoc,$db,$hookmanager,$nblignes;

		//extrafields in a object
		$extrafields = new ExtraFields($db);
		$extralabels=$extrafields->fetch_name_optionals_label($object->table_element);


		if (! is_object($outputlangs)) $outputlangs=$langs;
		// For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
		if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

		// Load traductions files requiredby by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills", "propal", "products"));

		$nblignes = count($object->lines);

		// Loop on each lines to detect if there is at least one image to show
		$realpatharray=array();
		if (! empty($conf->global->MAIN_GENERATE_PROPOSALS_WITH_PICTURE))
		{
			$objphoto = new Product($this->db);

			for ($i = 0 ; $i < $nblignes ; $i++)
			{
				if (empty($object->lines[$i]->fk_product)) continue;

				$objphoto->fetch($object->lines[$i]->fk_product);
                //var_dump($objphoto->ref);exit;
				if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
				{
					$pdir[0] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product') . $objphoto->id ."/photos/";
					$pdir[1] = get_exdir(0, 0, 0, 0, $objphoto, 'product') . dol_sanitizeFileName($objphoto->ref).'/';
				}
				else
				{
					$pdir[0] = get_exdir(0, 0, 0, 0, $objphoto, 'product') . dol_sanitizeFileName($objphoto->ref).'/';				// default
					$pdir[1] = get_exdir($objphoto->id, 2, 0, 0, $objphoto, 'product') . $objphoto->id ."/photos/";	// alternative
				}

				$arephoto = false;
				foreach ($pdir as $midir)
				{
					if (! $arephoto)
					{
						$dir = $conf->product->dir_output.'/'.$midir;

						foreach ($objphoto->liste_photos($dir, 1) as $key => $obj)
						{
							if (empty($conf->global->CAT_HIGH_QUALITY_IMAGES))		// If CAT_HIGH_QUALITY_IMAGES not defined, we use thumb if defined and then original photo
							{
								if ($obj['photo_vignette'])
								{
									$filename= $obj['photo_vignette'];
								}
								else
								{
									$filename=$obj['photo'];
								}
							}
							else
							{
								$filename=$obj['photo'];
							}

							$realpath = $dir.$filename;
							$arephoto = true;
						}
					}
				}

				if ($realpath && $arephoto) $realpatharray[$i]=$realpath;
			}
		}

		if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

		if ($conf->propal->multidir_output[$conf->entity])
		{
			$object->fetch_thirdparty();

			$deja_regle = 0;

			// Definition of $dir and $file
			if ($object->specimen)
			{
				$dir = $conf->propal->multidir_output[$conf->entity];
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->propal->multidir_output[$object->entity] . "/" . $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
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
				$reshook=$hookmanager->executeHooks('beforePDFCreation', $parameters, $object, $action);    // Note that $action and $object may have been modified by some hooks

				// Create pdf instance
                $pdf=pdf_getInstance($this->format);
                $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
	            $pdf->SetAutoPageBreak(1, 0);

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
				$pdf->SetDrawColor(128, 128, 128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("PdfCommercialProposalTitle"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfCommercialProposalTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
				if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

				// Positionne $this->atleastonediscount si on a au moins une remise
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					if ($object->lines[$i]->remise_percent)
					{
						$this->atleastonediscount++;
					}
				}
				if (empty($this->atleastonediscount))
				{
				    $delta = ($this->postotalht - $this->posxdiscount);
				    $this->posxpicture+=$delta;
				    $this->posxtva+=$delta;
				    $this->posxup+=$delta;
				    $this->posxqty+=$delta;
				    $this->posxunit+=$delta;
				    $this->posxdiscount+=$delta;
				    // post of fields after are not modified, stay at same position
				}

				//Page de garde
				$pdf->AddPage();
				if (! empty($tplidx)) $pdf->useTemplate($tplidx);
				$pagenb++;

				$this->pageGarde($pdf, $object, $outputlangs, $rowid, $extralabels);

				//A. Conditions de l'offre
				$pdf->AddPage();
				$pdf->SetAutoPageBreak(1,$this->marge_basse);

				$this->conditionOffre($pdf,$object, $outputlangs);

				$newPage = $pdf->getPage() + 1;

				//B. Détails de l'offre
				$pdf->AddPage();
				$pdf->SetFont('Helvetica','B',$this->titleFontSize);
				$pdf->SetTextColor(153,204,102);
				$pdf->Text(20,$this->marge_haute,"B. Détails de l'offre");

				//Reset Font&Color
				$pdf->SetFont('Helvetica','',$this->tableFontSize);
				$pdf->SetTextColor(64,64,64);

				$tab_top = $this->marge_haute + 10;
				$tab_top_newpage = $this->marge_haute;

                $heightforinfotot = 30;	// Height reserved to output the info and total part
                $heightforsignature = empty($conf->global->PROPAL_DISABLE_SIGNATURE)?(pdfGetHeightForHtmlContent($pdf, $outputlangs->transnoentities("ProposalCustomerSignature"))-40):0;
                $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse;	// Height reserved to output the footer (value include bottom margin)
				if ($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS >0) $heightforfooter+= 20;

				// Incoterm
				if ($conf->incoterm->enabled)
				{
					$desc_incoterms = $object->getIncotermsForPDF();
					if ($desc_incoterms)
					{
						$tab_top -= 2;

						$pdf->SetFont('', '', $default_font_size - 1);
						$pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top-1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
						$nexY = $pdf->GetY();
						$height_incoterms=$nexY-$tab_top;

						// Rect prend une longueur en 3eme param
						$pdf->SetDrawColor(192, 192, 192);
						$pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_incoterms+1);

						$tab_top = $nexY+6;
					}
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				$numTitre = 0;
				$posTitre = array();
				$pageTitre = array();

				// Loop on each lines
				for ($i = 0; $i < $nblignes; $i++)
				{
					$curY = $nexY;
					$pdf->SetFont('', '', $this->tableFontSize);   // Into loop to work with multipage
					$pdf->SetTextColor(64,64,64);

					// Special logic to create titles and comments
					if(substr($object->lines[$i]->description, 0, 2) == '<h')
					{
						$isTitre = 1;
						$numTitre++;
						$posTitre[$numTitre] = $curY + 10;
						$pageTitre[$numTitre] = $pdf->getPage();
						$curY = $curY + 10;
					}
					elseif(substr($object->lines[$i]->description, 0, 5) == '<div>')
					{
						$isComment = 1;
					}
					else
					{
						$isTitre = 0;
						$isComment = 0;
					}

					// Define size of image if we need it
					$imglinesize=array();
					if (! empty($realpatharray[$i])) $imglinesize=pdf_getSizeForImage($realpatharray[$i]);

					$pdf->setTopMargin($tab_top_newpage);
					$pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforsignature+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
					$pageposbefore=$pdf->getPage();

					$showpricebeforepagebreak=1;
					$posYAfterImage=0;
					$posYAfterDescription=0;

					// We start with Photo of product line
					if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur-($heightforfooter+$heightforfreetext+$heightforsignature+$heightforinfotot)))	// If photo too high, we moved completely on new page
					{
						$pdf->AddPage('', '', true);
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$this->_pagehead($pdf, $object, 0, $outputlangs);
						$pdf->setPage($pageposbefore+1);

						$curY = $tab_top_newpage;
						$showpricebeforepagebreak=0;
					}

					if (isset($imglinesize['width']) && isset($imglinesize['height']))
					{
						$curX = $this->posxpicture-1;
						$pdf->Image($realpatharray[$i], $curX + (($this->posxtva-$this->posxpicture-$imglinesize['width'])/2), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);	// Use 300 dpi
						// $pdf->Image does not increase value return by getY, so we save it manually
						$posYAfterImage=$curY+$imglinesize['height'];
					}

					// Description of product line
					$curX = $this->posxdesc-1;

					$pdf->startTransaction();
					pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture-$curX, 3, $curX, $curY, $hideref, $hidedesc);
					$pageposafter=$pdf->getPage();
					if ($pageposafter > $pageposbefore)	// There is a pagebreak
					{
						$pdf->rollbackTransaction(true);
						$pageposafter=$pageposbefore;
						//print $pageposafter.'-'.$pageposbefore;exit;
						$pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
						pdf_writelinedesc($pdf, $object, $i, $outputlangs, $this->posxpicture-$curX, 3, $curX, $curY, $hideref, $hidedesc);

						$pageposafter=$pdf->getPage();
						$posyafter=$pdf->GetY();
						//var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
						if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforsignature+$heightforinfotot)))	// There is no space left for total+free text
						{
							if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
							{
								$pdf->AddPage('', '', true);
								if (! empty($tplidx)) $pdf->useTemplate($tplidx);
								$this->_pagehead($pdf, $object, 0, $outputlangs);
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
					$posYAfterDescription=$pdf->GetY();

					$nexY = $pdf->GetY();
					$pageposafter=$pdf->getPage();

					$pdf->setPage($pageposbefore);
					$pdf->setTopMargin($this->marge_haute);
					$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

					// We suppose that a too long description or photo were moved completely on next page
					if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
						$pdf->setPage($pageposafter); $curY = $tab_top_newpage;
					}

					$pdf->SetFont('', '', $this->tableFontSize);

					// VAT Rate
					if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN) && empty($isTitre) && empty($isComment))
					{
						$vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxtva-5, $curY);
						$pdf->MultiCell($this->posxup-$this->posxtva+4, 3, $vat_rate, 0, 'R');
					}


					// Unit price before discount
					if (empty($isTitre) && empty($isComment))
					{
						$up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxup, $curY);
						$pdf->MultiCell($this->posxqty-$this->posxup-0.8, 3, $up_excl_tax, 0, 'R', 0);
					}

					// Quantity
					if (empty($isTitre) && empty($isComment))
					{
						$qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->posxqty, $curY);
						$pdf->MultiCell($this->posxunit-$this->posxqty-0.8, 4, $qty, 0, 'R');  // Enough for 6 chars
					}

					// Unit
					if($conf->global->PRODUCT_USE_UNITS && empty($isTitre) && empty($isComment))
					{
						$unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
						$pdf->SetXY($this->posxunit, $curY);
						$pdf->MultiCell($this->posxdiscount-$this->posxunit-0.8, 4, $unit, 0, 'L');
					}

					// Discount on line
					$pdf->SetXY($this->posxdiscount, $curY);
					if ($object->lines[$i]->remise_percent && empty($isTitre) && empty($isComment))
					{
						$pdf->SetXY($this->posxdiscount-2, $curY);
						$remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
						$pdf->MultiCell($this->postotalht-$this->posxdiscount+2, 3, $remise_percent, 0, 'R');
					}

					// Total HT line
					if (empty($isTitre) && empty($isComment))
					{
						$total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
						$pdf->SetXY($this->postotalht, $curY);
						$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalht, 3, $total_excl_tax.'€', 0, 'R', 0);
					}

					//Tableau sous total pour titres
					if(empty($isTitre)){
						$ssTotalTitre[$numTitre] += $object->lines[$i]->total_ht;
					}

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
						$localtaxtmp_array=getLocalTaxesFromRate($vatrate, 0, $object->thirdparty, $mysoc);
						$localtax1_type = $localtaxtmp_array[0];
						$localtax2_type = $localtaxtmp_array[2];
					}

				    // retrieve global local tax
					if ($localtax1_type && $localtax1ligne != 0)
						$this->localtax1[$localtax1_type][$localtax1_rate]+=$localtax1ligne;
					if ($localtax2_type && $localtax2ligne != 0)
						$this->localtax2[$localtax2_type][$localtax2_rate]+=$localtax2ligne;

					if (($object->lines[$i]->info_bits & 0x01) == 0x01) $vatrate.='*';
					if (! isset($this->tva[$vatrate]))				$this->tva[$vatrate]=0;
					$this->tva[$vatrate] += $tvaligne;

					if ($posYAfterImage > $posYAfterDescription) $nexY=$posYAfterImage;


					// Add line
					if (! empty($conf->global->MAIN_PDF_DASH_BETWEEN_LINES) && $i < ($nblignes - 1) && !empty($isTitre) && $i>0)
					{
						$pdf->setPage($pageposafter);
						$pdf->SetLineStyle(array('dash'=>'1,1','color'=>array(80,80,80)));
						//$pdf->SetDrawColor(190,190,200);
						$pdf->line($this->marge_gauche, $curY + 6, $this->page_largeur - $this->marge_droite, $curY + 6);
						$pdf->SetLineStyle(array('dash'=>0));
					}

					$nexY+=2;    // Passe espace entre les lignes

					// Detect if some page were added automatically and output _tableheader for past pages
					while ($pagenb < $pageposafter)
					{
						$pdf->setPage($pagenb);
						if ($pagenb == $newPage)
						{
							$this->_tableheader($pdf, $tab_top, $outputlangs, $object->multicurrency_code);
						}
						elseif ($pagenb > $newPage)
						{
							$this->_tableheader($pdf, $tab_top_newpage, $outputlangs, $object->multicurrency_code);
						}
						$pagenb++;
						$pdf->setPage($pagenb);
						$pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
						$this->_pagefoot($pdf, $object, $outputlangs, 1);
						$this->_pagehead($pdf, $object, 0, $outputlangs);
					}
					if (isset($object->lines[$i+1]->pagebreak) && $object->lines[$i+1]->pagebreak)
					{
						if ($pagenb == $newPage)
						{
							$this->_tableheader($pdf, $tab_top,  $outputlangs, $object->multicurrency_code);
						}
						elseif ($pagenb > $newPage)
						{
							$this->_tableheader($pdf, $tab_top_newpage, $outputlangs, $object->multicurrency_code);
						}
						$this->_pagefoot($pdf, $object, $outputlangs, 1);

						// New page
						$pdf->AddPage();
						if (! empty($tplidx)) $pdf->useTemplate($tplidx);
						$pagenb++;
						$this->_pagehead($pdf, $object, 0, $outputlangs);
					}
				}

				/*
				* show sous total value (in front of title)
				*/
				if($numTitre){
					$lastPage = $pdf->getPage();
				}

				for ($i = 1; $i <= $numTitre; $i++)
				{
						$total_excl_tax = price($ssTotalTitre[$i], 0, $outputlangs);
						$pdf->setPage($pageTitre[$i]);
						$pdf->SetFont('','B',$this->tableSubTotalsFontSize);
						$pdf->SetXY($this->postotalht, $posTitre[$i]);
						$pdf->MultiCell($this->page_largeur-$this->marge_droite-$this->postotalht, 3, $total_excl_tax.' €', 0, 'R', 0);
				}

				if($numTitre){
					$pdf->setPage($lastPage);
				}

				/*
				* sous le tableau
				*/

				// Show square
				if ($pagenb == $newPage)
				{
					$this->_tableheader($pdf, $tab_top, $outputlangs, $object->multicurrency_code);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter + 1;
				}
				elseif ($pagenb > $newPage)
				{
					$this->_tableheader($pdf, $tab_top_newpage, $outputlangs, $object->multicurrency_code);
					$bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforsignature - $heightforfooter + 1;
				}

				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				//C. Total de l'offre (Récapitulatif des totaux)
				$pdf->AddPage();
				$this->_pagehead($pdf, $object, 0, $outputlangs);

				$this->totOffre($pdf, $object, $outputlangs);

				//D. Validation de l'offre
				$this->validOffre($pdf, $object, $outputlangs);

				// Pied de page
				$this->_pagefoot($pdf, $object, $outputlangs);
				if (method_exists($pdf, 'AliasNbPages')) $pdf->AliasNbPages();

				//If propal merge product PDF is active
				if (!empty($conf->global->PRODUIT_PDF_MERGE_PROPAL))
				{
					require_once DOL_DOCUMENT_ROOT.'/product/class/propalmergepdfproduct.class.php';

					$already_merged = array ();
					foreach ($object->lines as $line) {
						if (! empty($line->fk_product) && ! (in_array($line->fk_product, $already_merged))) {
							// Find the desire PDF
							$filetomerge = new Propalmergepdfproduct($this->db);

							if ($conf->global->MAIN_MULTILANGS) {
								$filetomerge->fetch_by_product($line->fk_product, $outputlangs->defaultlang);
							} else {
								$filetomerge->fetch_by_product($line->fk_product);
							}

							$already_merged[] = $line->fk_product;

							$product = new Product($this->db);
							$product->fetch($line->fk_product);

							if ($product->entity!=$conf->entity) {
								$entity_product_file=$product->entity;
							} else {
								$entity_product_file=$conf->entity;
							}

							// If PDF is selected and file is not empty
							if (count($filetomerge->lines) > 0) {
								foreach ($filetomerge->lines as $linefile) {
									if (! empty($linefile->id) && ! empty($linefile->file_name)) {


										if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
										{
											if (! empty($conf->product->enabled)) {
												$filetomerge_dir = $conf->product->multidir_output[$entity_product_file] . '/' . get_exdir($product->id, 2, 0, 0, $product, 'product') . $product->id ."/photos";
											} elseif (! empty($conf->service->enabled)) {
												$filetomerge_dir = $conf->service->multidir_output[$entity_product_file] . '/' . get_exdir($product->id, 2, 0, 0, $product, 'product') . $product->id ."/photos";
											}
										}
										else
										{
											if (! empty($conf->product->enabled)) {
												$filetomerge_dir = $conf->product->multidir_output[$entity_product_file] . '/' . get_exdir(0, 0, 0, 0, $product, 'product') . dol_sanitizeFileName($product->ref);
											} elseif (! empty($conf->service->enabled)) {
												$filetomerge_dir = $conf->service->multidir_output[$entity_product_file] . '/' . get_exdir(0, 0, 0, 0, $product, 'product') . dol_sanitizeFileName($product->ref);
											}
										}

										dol_syslog(get_class($this) . ':: upload_dir=' . $filetomerge_dir, LOG_DEBUG);

										$infile = $filetomerge_dir . '/' . $linefile->file_name;
										if (file_exists($infile) && is_readable($infile)) {
											$pagecount = $pdf->setSourceFile($infile);
											for($i = 1; $i <= $pagecount; $i ++) {
												$tplIdx = $pdf->importPage($i);
												if ($tplIdx!==false) {
													$s = $pdf->getTemplatesize($tplIdx);
													$pdf->AddPage($s['h'] > $s['w'] ? 'P' : 'L');
													$pdf->useTemplate($tplIdx);
												} else {
													setEventMessages(null, array($infile.' cannot be added, probably protected PDF'), 'warnings');
												}
											}
										}
									}
								}
							}
						}
					}
				}

				$pdf->Close();

				$pdf->Output($file, 'F');

				//Add pdfgeneration hook
				$hookmanager->initHooks(array('pdfgeneration'));
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				global $action;
				$reshook=$hookmanager->executeHooks('afterPDFCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
				if ($reshook < 0)
				{
				    $this->error = $hookmanager->error;
				    $this->errors = $hookmanager->errors;
				}

				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				$this->result = array('fullpath'=>$file);

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->trans("ErrorCanNotCreateDir", $dir);
				return 0;
			}
		}
		else
		{
			$this->error=$langs->trans("ErrorConstantNotDefined", "PROP_OUTPUTDIR");
			return 0;
		}
	}

	/**
	 *   Show table for lines
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		string		$tab_top		Top position of table
	 *   @param		string		$tab_height		Height of table (rectangle)
	 *   @param		int			$nexY			Y (not used)
	 *   @param		Translate	$outputlangs	Langs object
	 *   @param		int			$hidetop		1=Hide top bar of array and title, 0=Hide nothing, -1=Hide only title
	 *   @param		int			$hidebottom		Hide bottom bar of array
	 *   @param		string		$currency		Currency code
	 *   @return	void
	 */
	private function _tableheader(&$pdf, $tab_top, $outputlangs,  $currency = '')
	{
		global $conf;
		$currency = !empty($currency) ? $currency : $conf->currency;

		// Amount in (at tab_top - 1)
		$pdf->SetTextColor(64,64,64);
		$pdf->SetFont('Helvetica', '', 10);

		$titre = $outputlangs->transnoentities("AmountInCurrency", $outputlangs->transnoentitiesnoconv("Currency".$currency));
		$pdf->SetXY($this->page_largeur - $this->marge_droite - ($pdf->GetStringWidth($titre) + 6), 258);
		$pdf->SetFontSize(6);
		$pdf->MultiCell(0, 0,$titre,0,'R');
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, 5, 'F', null, ['230', '230', '230']);

		$pdf->SetDrawColor(128, 128, 128);
		$pdf->SetFont('', '', $this->tableFontSize);

		$pdf->line($this->marge_gauche, $tab_top + 5, $this->page_largeur-$this->marge_droite, $tab_top + 5);	// line prend une position y en 2eme param et 4eme param

		$pdf->SetXY($this->posxdesc-1, $tab_top + 1);
		$pdf->MultiCell(108, 2, $outputlangs->transnoentities("Designation"), '', 'L');

		$pdf->SetXY($this->posxtva-3, $tab_top + 1);
		$pdf->MultiCell($this->posxup-$this->posxtva+3, 2, $outputlangs->transnoentities("VAT"), '', 'C');

		$pdf->SetXY($this->posxup-1, $tab_top + 1);
		$pdf->MultiCell($this->posxqty-$this->posxup-1, 2, $outputlangs->transnoentities("PriceUHT"), '', 'C');

		$pdf->SetXY($this->posxqty-1, $tab_top + 1);
		$pdf->MultiCell($this->posxunit-$this->posxqty-1, 2, $outputlangs->transnoentities("Qty"), '', 'C');

		if($conf->global->PRODUCT_USE_UNITS)
		{
			$pdf->SetXY($this->posxunit - 1, $tab_top + 1);
			$pdf->MultiCell($this->posxdiscount - $this->posxunit - 1, 2, $outputlangs->transnoentities("Unit"), '',
				'C');
		}

		if ($this->atleastonediscount)
		{
			$pdf->SetXY($this->posxdiscount-1, $tab_top + 1);
			$pdf->MultiCell($this->postotalht-$this->posxdiscount+1, 2, $outputlangs->transnoentities("ReductionShort"), '', 'C');
		}

		$pdf->SetXY($this->postotalht-3, $tab_top + 1);
		$pdf->MultiCell(30, 2, $outputlangs->transnoentities("TotalHT"), '', 'C');
	}

	/**
	 *  Show top header of page.
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  int	    	$showaddress    0=no, 1=yes
	 *  @param  Translate	$outputlangs	Object lang for output
	 *  @return	int			$top_shift
	 */
	private function _pagehead(&$pdf, $object, $showaddress, $outputlangs)
	{
		// Load translation files required by page
		$outputlangs->loadLangs(array("main", "propal", "companies", "bills"));

		// Header picture
		$pdf->Image($this->urlImageGmeHeader,0,0,210,50);

		//Données
		$pdf->SetFont('','I',7);
		$pdf->MultiCell(0,4,$object->thirdparty->name,0,'L',0,1,$this->contentHeaderCustomerMarginLeft + 30,$this->contentHeaderCustomerMarginTop);
		$pdf->MultiCell(0,4,$object->thirdparty->code_client,0,'L',0,1,$this->contentHeaderCustomerMarginLeft + 30,$pdf->GetY());
		$pdf->MultiCell(0,4,$object->ref,0,'L',0,1,$this->contentHeaderCustomerMarginLeft + 30,$pdf->GetY());
		$pdf->MultiCell (0,4,dol_print_date($object->date,"day",
				false,$outputlangs,true),0,'L',0,1,$this->contentHeaderCustomerMarginLeft + 30,$pdf->GetY());

		$pdf->writeHTMLCell(50,4,$this->contentHeaderCustomerMarginLeft + 30,$pdf->GetY(),$object->thirdparty->address.'<br/>'
			.$object->thirdparty->zip.' '.$object->thirdparty->town,0,1);

		$yTVA = $pdf->GetY();
		$pdf->MultiCell(0,4,$object->thirdparty->tva_intra,0,'L',0,1,$this->contentHeaderCustomerMarginLeft + 30, $yTVA);

		//Titre
		$pdf->SetFont('','B',7);
		$pdf->MultiCell(40,4,$outputlangs->transnoentities('Lastname').' : ',0,'L',0,1,$this->contentHeaderCustomerMarginLeft,$this->contentHeaderCustomerMarginTop);
		$pdf->MultiCell(40,4,$outputlangs->transnoentities('Customer').' n° :',0,'L',0,1,$this->contentHeaderCustomerMarginLeft,$pdf->GetY());
		$pdf->MultiCell(40,4,$outputlangs->transnoentities('ContactDefault_propal').' n° :',0,'L',0,1,$this->contentHeaderCustomerMarginLeft,$pdf->GetY());
		$pdf->MultiCell(40,4,$outputlangs->transnoentities('DatePropal').' :',0,'L',0,1,$this->contentHeaderCustomerMarginLeft,$pdf->GetY());
		$pdf->MultiCell(40,4,$outputlangs->transnoentities('Address').' : ',0,'L',0,1,$this->contentHeaderCustomerMarginLeft,$pdf->GetY());
		$pdf->MultiCell(40,4,$outputlangs->transnoentities('VATIntra').' : ',0,'L',0,1,$this->contentHeaderCustomerMarginLeft,$yTVA);
	}

	/**
	 *  Page de Garde
	 *  tdo, stdo, ref
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @param  Object		$rowid
	 *  @param  Object		$extralabels
	 *  @return	void
	 */
	private function pageGarde(&$pdf, $object, $outputlangs, $rowid, $extralabels)
	{
		$pdf->Image($this->urlImageGme,0,0,210,190);

		//get extrafields
		$object->fetch($rowid);
		$object->fetch_optionals($rowid,$extralabels);

		$pdf->SetFont('Helvetica','B',$this->titleFontSize);
		$pdf->SetTextColor(153,204,102);
		$pdf->MultiCell (180,3, $outputlangs->convToOutputCharset($object->array_options ['options_tdo']),0,'R',0,1,0, 240);

		$pdf->SetFont('Helvetica','',$this->subTitleFontSize);
		$pdf->SetTextColor(64,64,64);
		$pdf->MultiCell (180,3, $outputlangs->convToOutputCharset($object->array_options ['options_stdo']),0,'R',0,1,0,$pdf->GetY()+3);
		$pdf->MultiCell (180,3, $outputlangs->convToOutputCharset($object->ref),0,'R',0,1,0,$pdf->GetY()+3);
	}

	/**
	 *  Condition de l'offre
	 *  ddo,dded,cdp,cg,ddl
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @return	void
	 */
	private function conditionOffre(&$pdf, $object, $outputlangs){

		$ddo = dol_htmlentitiesbr($outputlangs->convToOutputCharset($object->array_options ['options_ddo'])) ;
		$vdo = dol_print_date($object->fin_validite,"day",false,$outputlangs,true);
		$dded = dol_htmlentitiesbr($outputlangs->convToOutputCharset($object->array_options ['options_dded']));
		$cdp = $outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code);
		$cg = dol_htmlentitiesbr($outputlangs->convToOutputCharset($object->array_options ['options_cg']));
		if (!empty($object->delivery_date)){
			$ddl = dol_print_date($object->delivery_date, "daytext", false, $outputlangs, true);
		} else {
			$ddl = $outputlangs->transnoentities("AvailabilityType".$object->availability_code);
		}


		$contenu = array($ddo, $dded, $vdo, $cdp, $cg, $ddl);

		$titre = array("Descriptif de l'offre",
			"Descriptif des services disponibles",
			"Validité de l'offre",
			"Conditions de paiement",
			"Conditions générales",
			"Délais de livraison");


		//Titre
		$pdf->SetFont('Helvetica','B',$this->titleFontSize);
		$pdf->SetTextColor(153,204,102);
		$pdf->Text($this->contentLeftMargin,$this->marge_haute,"A. Conditions de l'offre");

		$pdf->SetY($this->marge_haute+5);

		$j = 1;

		for ($i=0; $i<6; $i++) {
			if(!empty($contenu[$i])){
				$pdf->SetFont('Helvetica','B',$this->subTitleFontSize);
				$pdf->SetTextColor(153,204,102);
				$pdf->Text(20,$pdf->GetY()+5,$j.'. '.$titre[$i]);

				$pdf->SetFont('Helvetica','',$this->contentFontSize);
				$pdf->SetTextColor(64,64,64);
				$pdf->writeHTMLCell(0,5,$this->contentLeftMargin,$pdf->GetY()+10,'<span style="text-align:justify;">'.$contenu[$i].'</span>',0,2);

				$j++;
			}
		}
	}

	/**
	 *  Total de l'offre
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @return	void
	 */
	private function totOffre(&$pdf, $object, $outputlangs){
		global $langs;

		//Couleur de fond cellule
		$pdf->SetFillColor(153,204,102);

		$pdf->SetFont('Helvetica','B',$this->titleFontSize);
		$pdf->SetTextColor(153,204,102);
		$pdf->Text(20,$this->marge_haute,"C. Total de l'offre");

		//remtotal
		if($outputlangs->convToOutputCharset($object->array_options ['options_remtotal']) != '')
		{
			$pdf->SetFont('Helvetica','',$this->contentFontSize);
			$pdf->SetTextColor(64,64,64);
			$pdf->writeHTMLCell(0,5,20,$pdf->GetY()+10,$outputlangs->convToOutputCharset($object->array_options ['options_remtotal']),0,1);
		}
		else
		{
			$pdf->writeHTMLCell(0,5,20,$pdf->GetY(),'',0,1);
		}

		//Tableau récap totaux
		$pdf->SetFont('Helvetica','B',$this->contentFontSize);
		$pdf->SetTextColor(255,255,255);
		$pdf->MultiCell (170,7,"Récapitulatif des totaux",1,'C',1,1,20,$pdf->GetY()+5,true, 0, false, true, 7, 'M');

		//tot HT
		$pdf->SetFont('Helvetica','',$this->contentFontSize);
		$pdf->SetTextColor(64,64,64);
		$pdf->MultiCell (85,7,$outputlangs->transnoentities('TotalHT'),1,'L',0,0,20, $pdf->GetY(),true, 0, false, true, 7, 'M');
		$pdf->MultiCell (85,7, number_format($object->total_ht,2,',',' ').'€',1,'R',0,1,105,$pdf->GetY(),true, 0, false, true, 7, 'M');

		//tva
		$pdf->MultiCell (85,7,$outputlangs->transnoentities('TotalVAT'),1,'L',0,0,20,$pdf->GetY(),true, 0, false, true, 7, 'M');
		//tva obtenu en faisant une soustraction (tot_vat-tot_ht)
		$pdf->MultiCell (85,7, number_format(($object->total_ttc-$object->total_ht),2,',',' ').'€',1,'R',0,1,105,$pdf->GetY(),true, 0, false, true, 7, 'M');

		//tot ttc
		$pdf->SetFont('Helvetica','B',$this->titleFontSize);
		$pdf->SetTextColor(153,204,102);
		$pdf->MultiCell (85,7,$outputlangs->transnoentities('TotalTTC'),1,'L',0,0,20,$pdf->GetY(),true, 0, false, true, 7, 'M');
		$pdf->MultiCell (85,7, number_format($object->total_ttc,2,',',' ').'€',1,'R',0,1,105,$pdf->GetY(),true, 0, false, true, 7, 'M');

	}

	/**
	 *  Validation de l'offre
	 *
	 *  @param	PDF			$pdf     		Object PDF
	 *  @param  Object		$object     	Object to show
	 *  @return	void
	 */
	private function validOffre(&$pdf, $object, $outputlangs)
	{
		global $user;

		$pdf->Text(20,$pdf->GetY()+5,"D. Validation de l'offre");

		//Reset Font&Color
		$pdf->SetFont('Helvetica','',$this->contentFontSize);
		$pdf->SetTextColor(64,64,64);

		//vdl
		$pdf->writeHTMLCell(0,7,20,$pdf->GetY()+10,$outputlangs->convToOutputCharset($object->array_options ['options_vdl']),0,1);

		$Y = $pdf->GetY();

		//Client
		$pdf->MultiCell (85,7,'Pour '.$object->thirdparty->name,0,'L',0,1,20,$pdf->GetY());
		$pdf->MultiCell (85,7,$object->contact->firstname.' '.$object->contact->lastname,0,'L',0,1,20,$pdf->GetY());
		$pdf->MultiCell (85,7,'En sa qualité de '.$object->contact->poste,0,'L',0,1,20,$pdf->GetY());
		$pdf->MultiCell (85,7,$outputlangs->transnoentities('DateOfSignature'),0,'L',0,0,20,$pdf->GetY());

		//GME
		$pdf->MultiCell (85,7,'Pour G.M.Electronics',0,'R',0,1,105,$Y);
		$pdf->MultiCell (85,7,$user->firstname.' '.$user->lastname,0,'R',0,1,105,$pdf->GetY());
		$pdf->MultiCell (85,7,'Responsable Commercial',0,'R',0,1,105,$pdf->GetY());
		$pdf->MultiCell (85,7,$outputlangs->transnoentities('DateOfSignature'),0,'R',0,1,105,$pdf->GetY());
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
	private function _pagefoot(&$pdf, $object, $outputlangs, $hidefreetext = 0)
	{
		global $conf, $user;

		$user->fetch($object->user_author_id);

		if($user->photo != '') {
			$pdf->Image($conf->user->dir_output.'/'.get_exdir($user->id, 2, 0, 1, $user, 'user').'/'.$user->photo,90,263,22,22);
		}

		$pdf->SetFont('Helvetica','', 9);
		$pdf->SetTextColor(64,64,64);
		$pdf->SetDrawColor(153,204,102);

		$pdf->MultiCell(0,4,$outputlangs->transnoentities('DemandReasonTypeSRC_COMM'),'B','L',0,1,120,265);
		$pdf->MultiCell(60,4,$user->lastname.' '.$user->firstname,0,'L',0,1,120,$pdf->GetY()+1);

		$pdf->Image($this->urlPictoMail,121,$pdf->GetY()+1,4,4);
		$pdf->MultiCell(60,4,' '.$user->email,0,'L',0,1,125,$pdf->GetY()+1);

		$pdf->Image($this->urlPictoPhone,121,$pdf->GetY()+1,4,4);
		$pdf->MultiCell(60,4,' '.$user->office_phone,0,'L',0,1,125,$pdf->GetY()+1);

		//Donnée GME + num Page
		$pdf->SetFontSize(7);
		$pdf->MultiCell(0,2,$this->footerContent,0,'C',0,0,20,288);
		$pdf->MultiCell(177,2, $pdf->PageNo().'/'.$pdf->getAliasNbPages(),0,'R',0,0,20,288);
	}
}
