<?php
/* Copyright (C) 2004-2014	Laurent Destailleur	<eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012	Regis Houssin		<regis.houssin@inodbox.com>
 * Copyright (C) 2008		Raphael Bertrand		<raphael.bertrand@resultic.fr>
 * Copyright (C) 2010-2014	Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2012		Christophe Battarel	<christophe.battarel@altairis.fr>
 * Copyright (C) 2012		Cédric Salvador		<csalvador@gpcsolutions.fr>
 * Copyright (C) 2012-2014	Raphaël Doursenaud	<rdoursenaud@gpcsolutions.fr>
 * Copyright (C) 2015		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2017		Ferran Marcet		<fmarcet@2byte.es>
 * Copyright (C) 2018       Frédéric France         <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/modules/facture/doc/pdf_sponge.modules.php
 *	\ingroup    facture
 *	\brief      File of class to generate customers invoices from sponge model
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/facture/modules_facture.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';


/**
 *	Class to manage PDF invoice template sponge
 */
class pdf_sponge extends ModelePDFFactures
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
     * @var int 	Save the name of generated file as the main doc when generating a doc with this template
     */
    public $update_main_doc_field;

	/**
     * @var string document type
     */
    public $type;

	/**
     * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.3 = array(5, 3)
     */
	public $phpmin = array(5, 2);

	/**
     * Dolibarr version of the loaded document
     * @public string
     */
	public $version = 'development';

    public $page_largeur;
    public $page_hauteur;
    public $format;
	public $marge_gauche;
	public $marge_droite;
	public $marge_haute;
	public $marge_basse;

	public $emetteur;	// Objet societe qui emet

	/**
	 * @var bool Situation invoice type
	 */
	public $situationinvoice;

	/**
	 * @var float X position for the situation progress column
	 */
	public $posxprogress;


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		// Translations
		$langs->loadLangs(array("main", "bills"));

		$this->db = $db;
		$this->name = "sponge";
		$this->description = $langs->trans('PDFSpongeDescription');
		$this->update_main_doc_field = 1;		// Save the name of generated file as the main doc when generating a doc with this template

		// Dimensiont page
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
		$this->option_escompte = 1;                // Affiche si il y a eu escompte
		$this->option_credit_note = 1;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 1;		   // Support add of a watermark on drafts

		$this->franchise=!$mysoc->tva_assuj;

		// Get source company
		$this->emetteur=$mysoc;
		if (empty($this->emetteur->country_code)) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default, if was not defined

		// Define position of columns
		$this->posxdesc=$this->marge_gauche+1; // used for notes ans other stuff

		//  Use new system for position of columns, view  $this->defineColumnField()

		$this->tva=array();
		$this->localtax1=array();
		$this->localtax2=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
		$this->situationinvoice=false;
	}


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
    /**
     *  Function to build pdf onto disk
     *
     *  @param		Object		$object				Object to generate
     *  @param		Translate	$outputlangs		Lang output object
     *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
     *  @param		int			$hidedetails		Do not show line details
     *  @param		int			$hidedesc			Do not show desc
     *  @param		int			$hideref			Do not show ref
     *  @return     int         	    			1=OK, 0=KO
	 */
	public function write_file($object,$outputlangs,$srctemplatepath='',$hidedetails=0,$hidedesc=0,$hideref=0)
	{
	    // phpcs:enable
	    global $user,$langs,$conf,$mysoc,$db,$hookmanager,$nblignes;

	    if (! is_object($outputlangs)) $outputlangs=$langs;
	    // For backward compatibility with FPDF, force output charset to ISO, because FPDF expect text to be encoded in ISO
	    if (! empty($conf->global->MAIN_USE_FPDF)) $outputlangs->charset_output='ISO-8859-1';

	    // Translations
	    $outputlangs->loadLangs(array("main", "bills", "products", "dict", "companies"));

	    $nblignes = count($object->lines);

	    // Loop on each lines to detect if there is at least one image to show
	    $realpatharray=array();
	    $this->atleastonephoto = false;
	    if (! empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE))
	    {
	        $objphoto = new Product($this->db);

	        for ($i = 0 ; $i < $nblignes ; $i++)
	        {
	            if (empty($object->lines[$i]->fk_product)) continue;

	            $objphoto->fetch($object->lines[$i]->fk_product);
	            //var_dump($objphoto->ref);exit;
	            if (! empty($conf->global->PRODUCT_USE_OLD_PATH_FOR_PHOTO))
	            {
	                $pdir[0] = get_exdir($objphoto->id,2,0,0,$objphoto,'product') . $objphoto->id ."/photos/";
	                $pdir[1] = get_exdir(0,0,0,0,$objphoto,'product') . dol_sanitizeFileName($objphoto->ref).'/';
	            }
	            else
	            {
	                $pdir[0] = get_exdir(0,0,0,0,$objphoto,'product') . dol_sanitizeFileName($objphoto->ref).'/';				// default
	                $pdir[1] = get_exdir($objphoto->id,2,0,0,$objphoto,'product') . $objphoto->id ."/photos/";	// alternative
	            }

	            $arephoto = false;
	            foreach ($pdir as $midir)
	            {
	                if (! $arephoto)
	                {
	                    $dir = $conf->product->dir_output.'/'.$midir;

	                    foreach ($objphoto->liste_photos($dir,1) as $key => $obj)
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
	                        $this->atleastonephoto = true;
	                    }
	                }
	            }

	            if ($realpath && $arephoto) $realpatharray[$i]=$realpath;
	        }
	    }

	    //if (count($realpatharray) == 0) $this->posxpicture=$this->posxtva;

	    if ($conf->facture->dir_output)
	    {
	        $object->fetch_thirdparty();

	        $deja_regle = $object->getSommePaiement(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
	        $amount_credit_notes_included = $object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
	        $amount_deposits_included = $object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);

	        // Definition of $dir and $file
	        if ($object->specimen)
	        {
	            $dir = $conf->facture->dir_output;
	            $file = $dir . "/SPECIMEN.pdf";
	        }
	        else
	        {
	            $objectref = dol_sanitizeFileName($object->ref);
	            $dir = $conf->facture->dir_output . "/" . $objectref;
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

	            // Set nblignes with the new facture lines content after hook
	            $nblignes = count($object->lines);
	            $nbpayments = count($object->getListOfPayments());

	            // Create pdf instance
	            $pdf=pdf_getInstance($this->format);
	            $default_font_size = pdf_getPDFFontSize($outputlangs);	// Must be after pdf_getInstance
	            $pdf->SetAutoPageBreak(1,0);

	            $heightforinfotot = 50+(4*$nbpayments);	// Height reserved to output the info and total part and payment part
	            $heightforfreetext= (isset($conf->global->MAIN_PDF_FREETEXT_HEIGHT)?$conf->global->MAIN_PDF_FREETEXT_HEIGHT:5);	// Height reserved to output the free text on last page
	            $heightforfooter = $this->marge_basse + (empty($conf->global->MAIN_GENERATE_DOCUMENTS_SHOW_FOOT_DETAILS)?12:22);	// Height reserved to output the footer (value include bottom margin)

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
	            $pdf->SetSubject($outputlangs->transnoentities("PdfInvoiceTitle"));
	            $pdf->SetCreator("Dolibarr ".DOL_VERSION);
	            $pdf->SetAuthor($outputlangs->convToOutputCharset($user->getFullName($outputlangs)));
	            $pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("PdfInvoiceTitle")." ".$outputlangs->convToOutputCharset($object->thirdparty->name));
	            if (! empty($conf->global->MAIN_DISABLE_PDF_COMPRESSION)) $pdf->SetCompression(false);

	            $pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right

	            // Does we have at least one line with discount $this->atleastonediscount
	            foreach ($object->lines as $line) {
	               if ($line->remise_percent){
	                    $this->atleastonediscount = true;
	                    break;
	               }
	            }


	            // Situation invoice handling
	            if ($object->situation_cycle_ref)
	            {
	                $this->situationinvoice = true;
	            }

	            // New page
	            $pdf->AddPage();
	            if (! empty($tplidx)) $pdf->useTemplate($tplidx);
	            $pagenb++;

	            $top_shift = $this->_pagehead($pdf, $object, 1, $outputlangs);
	            $pdf->SetFont('','', $default_font_size - 1);
	            $pdf->MultiCell(0, 3, '');		// Set interline to 3
	            $pdf->SetTextColor(0,0,0);

	            $tab_top = 90+$top_shift;
	            $tab_top_newpage = (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)?42+$top_shift:10);
	            $tab_height = 130-$top_shift;
	            $tab_height_newpage = 150;
	            if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $tab_height_newpage -= $top_shift;

	            // Incoterm
	            $height_incoterms = 0;
	            if ($conf->incoterm->enabled)
	            {
	                $desc_incoterms = $object->getIncotermsForPDF();
	                if ($desc_incoterms)
	                {
						$tab_top -= 2;

	                    $pdf->SetFont('','', $default_font_size - 1);
	                    $pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top-1, dol_htmlentitiesbr($desc_incoterms), 0, 1);
	                    $nexY = max($pdf->GetY(),$nexY);
	                    $height_incoterms=$nexY-$tab_top;

	                    // Rect prend une longueur en 3eme param
	                    $pdf->SetDrawColor(192,192,192);
	                    $pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_incoterms+1);

	                    $tab_top = $nexY+6;
	                    $height_incoterms += 4;
	                }
	            }

	            // Affiche notes
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

	            $pagenb = $pdf->getPage();
	            if ($notetoshow)
	            {
					$tab_top -= 2;

	                $tab_width = $this->page_largeur-$this->marge_gauche-$this->marge_droite;
	                $pageposbeforenote = $pagenb;

	                $substitutionarray=pdf_getSubstitutionArray($outputlangs, null, $object);
	                complete_substitutions_array($substitutionarray, $outputlangs, $object);
	                $notetoshow = make_substitutions($notetoshow, $substitutionarray, $outputlangs);


	                $pdf->startTransaction();

	                $pdf->SetFont('','', $default_font_size - 1);
	                $pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
	                // Description
	                $pageposafternote=$pdf->getPage();
	                $posyafter = $pdf->GetY();

	                if($pageposafternote>$pageposbeforenote )
	                {
	                    $pdf->rollbackTransaction(true);

	                    // prepar pages to receive notes
	                    while ($pagenb < $pageposafternote) {
	                        $pdf->AddPage();
	                        $pagenb++;
	                        if (! empty($tplidx)) $pdf->useTemplate($tplidx);
	                        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
	                        // $this->_pagefoot($pdf,$object,$outputlangs,1);
	                        $pdf->setTopMargin($tab_top_newpage);
	                        // The only function to edit the bottom margin of current page to set it.
	                        $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
	                    }

	                    // back to start
	                    $pdf->setPage($pageposbeforenote);
	                    $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
	                    $pdf->SetFont('','', $default_font_size - 1);
	                    $pdf->writeHTMLCell(190, 3, $this->posxdesc-1, $tab_top, dol_htmlentitiesbr($notetoshow), 0, 1);
	                    $pageposafternote=$pdf->getPage();

	                    $posyafter = $pdf->GetY();

	                    if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+20)))	// There is no space left for total+free text
	                    {
	                        $pdf->AddPage('','',true);
	                        $pagenb++;
	                        $pageposafternote++;
	                        $pdf->setPage($pageposafternote);
	                        $pdf->setTopMargin($tab_top_newpage);
	                        // The only function to edit the bottom margin of current page to set it.
	                        $pdf->setPageOrientation('', 1, $heightforfooter + $heightforfreetext);
	                        //$posyafter = $tab_top_newpage;
	                    }


	                    // apply note frame to previus pages
	                    $i = $pageposbeforenote;
	                    while ($i < $pageposafternote) {
	                        $pdf->setPage($i);


	                        $pdf->SetDrawColor(128,128,128);
	                        // Draw note frame
	                        if($i>$pageposbeforenote){
	                            $height_note = $this->page_hauteur - ($tab_top_newpage + $heightforfooter);
	                            $pdf->Rect($this->marge_gauche, $tab_top_newpage-1, $tab_width, $height_note + 1);
	                        }
	                        else{
	                            $height_note = $this->page_hauteur - ($tab_top + $heightforfooter);
	                            $pdf->Rect($this->marge_gauche, $tab_top-1, $tab_width, $height_note + 1);
	                        }

	                        // Add footer
	                        $pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.
	                        $this->_pagefoot($pdf,$object,$outputlangs,1);

	                        $i++;
	                    }

	                    // apply note frame to last page
	                    $pdf->setPage($pageposafternote);
	                    if (! empty($tplidx)) $pdf->useTemplate($tplidx);
	                    if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);
	                    $height_note=$posyafter-$tab_top_newpage;
	                    $pdf->Rect($this->marge_gauche, $tab_top_newpage-1, $tab_width, $height_note+1);
	                }
	                else // No pagebreak
	                {
	                    $pdf->commitTransaction();
	                    $posyafter = $pdf->GetY();
	                    $height_note=$posyafter-$tab_top;
	                    $pdf->Rect($this->marge_gauche, $tab_top-1, $tab_width, $height_note+1);


	                    if($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+20)) )
	                    {
	                        // not enough space, need to add page
	                        $pdf->AddPage('','',true);
	                        $pagenb++;
	                        $pageposafternote++;
	                        $pdf->setPage($pageposafternote);
	                        if (! empty($tplidx)) $pdf->useTemplate($tplidx);
	                        if (empty($conf->global->MAIN_PDF_DONOTREPEAT_HEAD)) $this->_pagehead($pdf, $object, 0, $outputlangs);

	                        $posyafter = $tab_top_newpage;
	                    }
	                }

	                $tab_height = $tab_height - $height_note;
	                $tab_top = $posyafter +6;
	            }
	            else
	            {
	                $height_note=0;
	            }

	            $iniY = $tab_top + 7;
	            $curY = $tab_top + 7;
	            $nexY = $tab_top + 7;

	            // Use new auto collum system
	            $this->prepareArrayColumnField($object,$outputlangs,$hidedetails,$hidedesc,$hideref);

	            // Loop on each lines
	            $pageposbeforeprintlines=$pdf->getPage();
	            $pagenb = $pageposbeforeprintlines;
	            for ($i = 0; $i < $nblignes; $i++)
	            {

	                $curY = $nexY;
	                $pdf->SetFont('','', $default_font_size - 1);   // Into loop to work with multipage
	                $pdf->SetTextColor(0,0,0);

	                // Define size of image if we need it
	                $imglinesize=array();
	                if (! empty($realpatharray[$i])) $imglinesize=pdf_getSizeForImage($realpatharray[$i]);

	                $pdf->setTopMargin($tab_top_newpage);
	                $pdf->setPageOrientation('', 1, $heightforfooter+$heightforfreetext+$heightforinfotot);	// The only function to edit the bottom margin of current page to set it.
	                $pageposbefore=$pdf->getPage();

	                $showpricebeforepagebreak=1;
	                $posYAfterImage=0;
	                $posYAfterDescription=0;

	                if($this->getColumnStatus('photo'))
	                {
    	                // We start with Photo of product line
    	                if (isset($imglinesize['width']) && isset($imglinesize['height']) && ($curY + $imglinesize['height']) > ($this->page_hauteur-($heightforfooter+$heightforfreetext+$heightforinfotot)))	// If photo too high, we moved completely on new page
    	                {
    	                    $pdf->AddPage('','',true);
    	                    if (! empty($tplidx)) $pdf->useTemplate($tplidx);
    	                    $pdf->setPage($pageposbefore+1);

    	                    $curY = $tab_top_newpage;
    	                    $showpricebeforepagebreak=0;
    	                }

    	                if (!empty($this->cols['photo']) && isset($imglinesize['width']) && isset($imglinesize['height']))
    	                {
    	                    $pdf->Image($realpatharray[$i], $this->getColumnContentXStart('photo'), $curY, $imglinesize['width'], $imglinesize['height'], '', '', '', 2, 300);	// Use 300 dpi
    	                    // $pdf->Image does not increase value return by getY, so we save it manually
    	                    $posYAfterImage=$curY+$imglinesize['height'];
    	                }
	                }

	                // Description of product line
	                if ($this->getColumnStatus('desc'))
	                {
    	                $pdf->startTransaction();
    	                pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->getColumnContentWidth('desc'),3,$this->getColumnContentXStart('desc'),$curY,$hideref,$hidedesc);
    	                $pageposafter=$pdf->getPage();
    	                if ($pageposafter > $pageposbefore)	// There is a pagebreak
    	                {
    	                    $pdf->rollbackTransaction(true);
    	                    $pageposafter=$pageposbefore;
    	                    //print $pageposafter.'-'.$pageposbefore;exit;
    	                    $pdf->setPageOrientation('', 1, $heightforfooter);	// The only function to edit the bottom margin of current page to set it.
    	                    pdf_writelinedesc($pdf,$object,$i,$outputlangs,$this->getColumnContentWidth('desc'),3,$this->getColumnContentXStart('desc'),$curY,$hideref,$hidedesc);
    	                    $pageposafter=$pdf->getPage();
    	                    $posyafter=$pdf->GetY();
    	                    //var_dump($posyafter); var_dump(($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot))); exit;
    	                    if ($posyafter > ($this->page_hauteur - ($heightforfooter+$heightforfreetext+$heightforinfotot)))	// There is no space left for total+free text
    	                    {
    	                        if ($i == ($nblignes-1))	// No more lines, and no space left to show total, so we create a new page
    	                        {
    	                            $pdf->AddPage('','',true);
    	                            if (! empty($tplidx)) $pdf->useTemplate($tplidx);
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
	                }

	                $nexY = $pdf->GetY();
	                $pageposafter=$pdf->getPage();
	                $pdf->setPage($pageposbefore);
	                $pdf->setTopMargin($this->marge_haute);
	                $pdf->setPageOrientation('', 1, 0);	// The only function to edit the bottom margin of current page to set it.

	                // We suppose that a too long description or photo were moved completely on next page
	                if ($pageposafter > $pageposbefore && empty($showpricebeforepagebreak)) {
	                    $pdf->setPage($pageposafter); $curY = $tab_top_newpage;
	                }

	                $pdf->SetFont('','', $default_font_size - 1);   // On repositionne la police par defaut

	                // VAT Rate
	                if ($this->getColumnStatus('vat'))
	                {
	                    $vat_rate = pdf_getlinevatrate($object, $i, $outputlangs, $hidedetails);
	                    $this->printStdColumnContent($pdf, $curY, 'vat', $vat_rate);
	                    $nexY = max($pdf->GetY(),$nexY);
	                }

	                // Unit price before discount
	                if ($this->getColumnStatus('subprice'))
	                {
	                    $up_excl_tax = pdf_getlineupexcltax($object, $i, $outputlangs, $hidedetails);
	                    $this->printStdColumnContent($pdf, $curY, 'subprice', $up_excl_tax);
	                    $nexY = max($pdf->GetY(),$nexY);
	                }

	                // Quantity
	                // Enough for 6 chars
	                if ($this->getColumnStatus('qty'))
	                {
	                    $qty = pdf_getlineqty($object, $i, $outputlangs, $hidedetails);
	                    $this->printStdColumnContent($pdf, $curY, 'qty', $qty);
	                    $nexY = max($pdf->GetY(),$nexY);
	                }

	                // Situation progress
	                if ($this->getColumnStatus('progress'))
	                {
	                    $progress = pdf_getlineprogress($object, $i, $outputlangs, $hidedetails);
	                    $this->printStdColumnContent($pdf, $curY, 'progress', $progress);
	                    $nexY = max($pdf->GetY(),$nexY);
	                }

	                // Unit
	                if ($this->getColumnStatus('unit'))
	                {
	                    $unit = pdf_getlineunit($object, $i, $outputlangs, $hidedetails, $hookmanager);
	                    $this->printStdColumnContent($pdf, $curY, 'unit', $unit);
	                    $nexY = max($pdf->GetY(),$nexY);
	                }

	                // Discount on line
	                if ($this->getColumnStatus('discount') && $object->lines[$i]->remise_percent)
	                {
	                    $remise_percent = pdf_getlineremisepercent($object, $i, $outputlangs, $hidedetails);
	                    $this->printStdColumnContent($pdf, $curY, 'discount', $remise_percent);
	                    $nexY = max($pdf->GetY(),$nexY);
	                }

	                // Total HT line
	                if ($this->getColumnStatus('totalexcltax'))
	                {
	                    $total_excl_tax = pdf_getlinetotalexcltax($object, $i, $outputlangs, $hidedetails);
	                    $this->printStdColumnContent($pdf, $curY, 'totalexcltax', $total_excl_tax);
	                    $nexY = max($pdf->GetY(),$nexY);
	                }


	                $parameters=array(
	                    'object' => $object,
	                    'i' => $i,
	                    'pdf' =>& $pdf,
	                    'curY' =>& $curY,
	                    'nexY' =>& $nexY,
	                    'outputlangs' => $outputlangs,
	                    'hidedetails' => $hidedetails
	                );
	                $reshook=$hookmanager->executeHooks('printPDFline',$parameters,$this);    // Note that $object may have been modified by hook



	                $sign=1;
	                if (isset($object->type) && $object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;
	                // Collecte des totaux par valeur de tva dans $this->tva["taux"]=total_tva
	                $prev_progress = $object->lines[$i]->get_prev_progress($object->id);
	                if ($prev_progress > 0 && !empty($object->lines[$i]->situation_percent)) // Compute progress from previous situation
	                {
	                    if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne = $sign * $object->lines[$i]->multicurrency_total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
	                    else $tvaligne = $sign * $object->lines[$i]->total_tva * ($object->lines[$i]->situation_percent - $prev_progress) / $object->lines[$i]->situation_percent;
	                } else {
	                    if ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) $tvaligne= $sign * $object->lines[$i]->multicurrency_total_tva;
	                    else $tvaligne= $sign * $object->lines[$i]->total_tva;
	                }

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
	                    $localtaxtmp_array=getLocalTaxesFromRate($vatrate,0, $object->thirdparty, $mysoc);
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

	                        $nexY = max($nexY,$posYAfterImage);

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
	                            if ($pagenb == $pageposbeforeprintlines)
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
	                            if ($pagenb == $pageposafter)
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
	            if ($pagenb == $pageposbeforeprintlines)
	            {
	                $this->_tableau($pdf, $tab_top, $this->page_hauteur - $tab_top - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 0, 0, $object->multicurrency_code);
	                $bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
	            }
	            else
	            {
	                $this->_tableau($pdf, $tab_top_newpage, $this->page_hauteur - $tab_top_newpage - $heightforinfotot - $heightforfreetext - $heightforfooter, 0, $outputlangs, 1, 0, $object->multicurrency_code);
	                $bottomlasttab=$this->page_hauteur - $heightforinfotot - $heightforfreetext - $heightforfooter + 1;
	            }

	            // Affiche zone infos
	            $posy=$this->drawInfoTable($pdf, $object, $bottomlasttab, $outputlangs);

	            // Affiche zone totaux
	            $posy=$this->drawTotalTable($pdf, $object, $deja_regle, $bottomlasttab, $outputlangs);

	            // Affiche zone versements
	            if (($deja_regle || $amount_credit_notes_included || $amount_deposits_included) && empty($conf->global->INVOICE_NO_PAYMENT_DETAILS))
	            {
	                $posy=$this->drawPaymentsTable($pdf, $object, $posy, $outputlangs);
	            }

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

	                return 1;   // No error
	        }
	        else
	        {
	            $this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
	            return 0;
	        }
	    }
	    else
	    {
	        $this->error=$langs->transnoentities("ErrorConstantNotDefined","FAC_OUTPUTDIR");
	        return 0;
	    }
	}


	/**
	 *  Show payments table
	 *
     *  @param	PDF			$pdf           Object PDF
     *  @param  Object		$object         Object invoice
     *  @param  int			$posy           Position y in PDF
     *  @param  Translate	$outputlangs    Object langs for output
     *  @return int             			<0 if KO, >0 if OK
	 */
	function drawPaymentsTable(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

        $sign=1;
        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

        $tab3_posx = 120;
		$tab3_top = $posy + 8;
		$tab3_width = 80;
		$tab3_height = 4;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$tab3_posx -= 20;
		}

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$title=$outputlangs->transnoentities("PaymentsAlreadyDone");
		if ($object->type == 2) $title=$outputlangs->transnoentities("PaymentsBackAlreadyDone");

		$pdf->SetFont('','', $default_font_size - 3);
		$pdf->SetXY($tab3_posx, $tab3_top - 4);
		$pdf->MultiCell(60, 3, $title, 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top, $tab3_posx+$tab3_width, $tab3_top);

		$pdf->SetFont('','', $default_font_size - 4);
		$pdf->SetXY($tab3_posx, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Payment"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+21, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Amount"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+40, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Type"), 0, 'L', 0);
		$pdf->SetXY($tab3_posx+58, $tab3_top);
		$pdf->MultiCell(20, 3, $outputlangs->transnoentities("Num"), 0, 'L', 0);

		$pdf->line($tab3_posx, $tab3_top-1+$tab3_height, $tab3_posx+$tab3_width, $tab3_top-1+$tab3_height);

		$y=0;

		$pdf->SetFont('','', $default_font_size - 4);


		// Loop on each deposits and credit notes included
		$sql = "SELECT re.rowid, re.amount_ht, re.multicurrency_amount_ht, re.amount_tva, re.multicurrency_amount_tva,  re.amount_ttc, re.multicurrency_amount_ttc,";
		$sql.= " re.description, re.fk_facture_source,";
		$sql.= " f.type, f.datef";
		$sql.= " FROM ".MAIN_DB_PREFIX ."societe_remise_except as re, ".MAIN_DB_PREFIX ."facture as f";
		$sql.= " WHERE re.fk_facture_source = f.rowid AND re.fk_facture = ".$object->id;
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

				if ($obj->type == 2) $text=$outputlangs->trans("CreditNote");
				elseif ($obj->type == 3) $text=$outputlangs->trans("Deposit");
				else $text=$outputlangs->trans("UnknownType");

				$invoice->fetch($obj->fk_facture_source);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($obj->datef,'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $obj->multicurrency_amount_ttc : $obj->amount_ttc, 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $text, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(20, 3, $invoice->ref, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}

		// Loop on each payment
		// TODO Call getListOfPaymentsgetListOfPayments instead of hard coded sql
		$sql = "SELECT p.datep as date, p.fk_paiement, p.num_paiement as num, pf.amount as amount, pf.multicurrency_amount,";
		$sql.= " cp.code";
		$sql.= " FROM ".MAIN_DB_PREFIX."paiement_facture as pf, ".MAIN_DB_PREFIX."paiement as p";
		$sql.= " LEFT JOIN ".MAIN_DB_PREFIX."c_paiement as cp ON p.fk_paiement = cp.id";
		$sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = ".$object->id;
		//$sql.= " WHERE pf.fk_paiement = p.rowid AND pf.fk_facture = 1";
		$sql.= " ORDER BY p.datep";

		$resql=$this->db->query($sql);
		if ($resql)
		{
			$num = $this->db->num_rows($resql);
			$i=0;
			while ($i < $num) {
				$y+=3;
				$row = $this->db->fetch_object($resql);

				$pdf->SetXY($tab3_posx, $tab3_top+$y);
				$pdf->MultiCell(20, 3, dol_print_date($this->db->jdate($row->date),'day',false,$outputlangs,true), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+21, $tab3_top+$y);
				$pdf->MultiCell(20, 3, price($sign * (($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $row->multicurrency_amount : $row->amount), 0, $outputlangs), 0, 'L', 0);
				$pdf->SetXY($tab3_posx+40, $tab3_top+$y);
				$oper = $outputlangs->transnoentitiesnoconv("PaymentTypeShort" . $row->code);

				$pdf->MultiCell(20, 3, $oper, 0, 'L', 0);
				$pdf->SetXY($tab3_posx+58, $tab3_top+$y);
				$pdf->MultiCell(30, 3, $row->num, 0, 'L', 0);

				$pdf->line($tab3_posx, $tab3_top+$y+3, $tab3_posx+$tab3_width, $tab3_top+$y+3);

				$i++;
			}
		}
		else
		{
			$this->error=$this->db->lasterror();
			return -1;
		}
	}


	/**
	 *   Show miscellaneous information (payment mode, payment term, ...)
	 *
	 *   @param		PDF			$pdf     		Object PDF
	 *   @param		Object		$object			Object to show
	 *   @param		int			$posy			Y
	 *   @param		Translate	$outputlangs	Langs object
	 *   @return	void
	 */
	private function drawInfoTable(&$pdf, $object, $posy, $outputlangs)
	{
		global $conf;

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		$pdf->SetFont('','', $default_font_size - 1);

		// If France, show VAT mention if not applicable
		if ($this->emetteur->country_code == 'FR' && $this->franchise == 1)
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$pdf->MultiCell(100, 3, $outputlangs->transnoentities("VATIsNotUsedForInvoice"), 0, 'L', 0);

			$posy=$pdf->GetY()+4;
		}

		$posxval=52;

		// Show payments conditions
		if ($object->type != 2 && ($object->cond_reglement_code || $object->cond_reglement))
		{
			$pdf->SetFont('','B', $default_font_size - 2);
			$pdf->SetXY($this->marge_gauche, $posy);
			$titre = $outputlangs->transnoentities("PaymentConditions").':';
			$pdf->MultiCell(43, 4, $titre, 0, 'L');

			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posxval, $posy);
			$lib_condition_paiement=$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code)!=('PaymentCondition'.$object->cond_reglement_code)?$outputlangs->transnoentities("PaymentCondition".$object->cond_reglement_code):$outputlangs->convToOutputCharset($object->cond_reglement_doc);
			$lib_condition_paiement=str_replace('\n',"\n",$lib_condition_paiement);
			$pdf->MultiCell(67, 4, $lib_condition_paiement,0,'L');

			$posy=$pdf->GetY()+3;
		}

		if ($object->type != 2)
		{
			// Check a payment mode is defined
			if (empty($object->mode_reglement_code)
			&& empty($conf->global->FACTURE_CHQ_NUMBER)
			&& empty($conf->global->FACTURE_RIB_NUMBER))
			{
				$this->error = $outputlangs->transnoentities("ErrorNoPaiementModeConfigured");
			}
			// Avoid having any valid PDF with setup that is not complete
			elseif (($object->mode_reglement_code == 'CHQ' && empty($conf->global->FACTURE_CHQ_NUMBER) && empty($object->fk_account) && empty($object->fk_bank))
				|| ($object->mode_reglement_code == 'VIR' && empty($conf->global->FACTURE_RIB_NUMBER) && empty($object->fk_account) && empty($object->fk_bank)))
			{
				$outputlangs->load("errors");

				$pdf->SetXY($this->marge_gauche, $posy);
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('','B', $default_font_size - 2);
				$this->error = $outputlangs->transnoentities("ErrorPaymentModeDefinedToWithoutSetup",$object->mode_reglement_code);
				$pdf->MultiCell(80, 3, $this->error,0,'L',0);
				$pdf->SetTextColor(0,0,0);

				$posy=$pdf->GetY()+1;
			}

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
				$pdf->SetXY($posxval, $posy);
				$lib_mode_reg=$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code)!=('PaymentType'.$object->mode_reglement_code)?$outputlangs->transnoentities("PaymentType".$object->mode_reglement_code):$outputlangs->convToOutputCharset($object->mode_reglement);
				$pdf->MultiCell(80, 5, $lib_mode_reg,0,'L');

				$posy=$pdf->GetY()+2;
			}

			// Show payment mode CHQ
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'CHQ')
			{
				// Si mode reglement non force ou si force a CHQ
				if (! empty($conf->global->FACTURE_CHQ_NUMBER))
				{
					$diffsizetitle=(empty($conf->global->PDF_DIFFSIZE_TITLE)?3:$conf->global->PDF_DIFFSIZE_TITLE);

					if ($conf->global->FACTURE_CHQ_NUMBER > 0)
					{
						$account = new Account($this->db);
						$account->fetch($conf->global->FACTURE_CHQ_NUMBER);

						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$account->proprio),0,'L',0);
						$posy=$pdf->GetY()+1;

			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
			            {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('','', $default_font_size - $diffsizetitle);
							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($account->owner_address), 0, 'L', 0);
							$posy=$pdf->GetY()+2;
			            }
					}
					if ($conf->global->FACTURE_CHQ_NUMBER == -1)
					{
						$pdf->SetXY($this->marge_gauche, $posy);
						$pdf->SetFont('','B', $default_font_size - $diffsizetitle);
						$pdf->MultiCell(100, 3, $outputlangs->transnoentities('PaymentByChequeOrderedTo',$this->emetteur->name),0,'L',0);
						$posy=$pdf->GetY()+1;

			            if (empty($conf->global->MAIN_PDF_HIDE_CHQ_ADDRESS))
			            {
							$pdf->SetXY($this->marge_gauche, $posy);
							$pdf->SetFont('','', $default_font_size - $diffsizetitle);
							$pdf->MultiCell(100, 3, $outputlangs->convToOutputCharset($this->emetteur->getFullAddress()), 0, 'L', 0);
							$posy=$pdf->GetY()+2;
			            }
					}
				}
			}

			// If payment mode not forced or forced to VIR, show payment with BAN
			if (empty($object->mode_reglement_code) || $object->mode_reglement_code == 'VIR')
			{
				if (! empty($object->fk_account) || ! empty($object->fk_bank) || ! empty($conf->global->FACTURE_RIB_NUMBER))
				{
					$bankid=(empty($object->fk_account)?$conf->global->FACTURE_RIB_NUMBER:$object->fk_account);
					if (! empty($object->fk_bank)) $bankid=$object->fk_bank;   // For backward compatibility when object->fk_account is forced with object->fk_bank
					$account = new Account($this->db);
					$account->fetch($bankid);

					$curx=$this->marge_gauche;
					$cury=$posy;

					$posy=pdf_bank($pdf,$outputlangs,$curx,$cury,$account,0,$default_font_size);

					$posy+=2;
				}
			}
		}

		return $posy;
	}


	/**
	 *	Show total to pay
	 *
	 *	@param	PDF			$pdf           Object PDF
	 *	@param  Facture		$object         Object invoice
	 *	@param  int			$deja_regle     Montant deja regle
	 *	@param	int			$posy			Position depart
	 *	@param	Translate	$outputlangs	Objet langs
	 *	@return int							Position pour suite
	 */
	private function drawTotalTable(&$pdf, $object, $deja_regle, $posy, $outputlangs)
	{
		global $conf,$mysoc;

        $sign=1;
        if ($object->type == 2 && ! empty($conf->global->INVOICE_POSITIVE_CREDIT_NOTE)) $sign=-1;

        $default_font_size = pdf_getPDFFontSize($outputlangs);

		$tab2_top = $posy;
		$tab2_hl = 4;
		$pdf->SetFont('','', $default_font_size - 1);

		// Tableau total
		$col1x = 120; $col2x = 170;
		if ($this->page_largeur < 210) // To work with US executive format
		{
			$col2x-=20;
		}
		$largcol2 = ($this->page_largeur - $this->marge_droite - $col2x);

		$useborder=0;
		$index = 0;

		// Total HT
		$pdf->SetFillColor(255,255,255);
		$pdf->SetXY($col1x, $tab2_top + 0);
		$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalHT"), 0, 'L', 1);

		$total_ht = ($conf->multicurrency->enabled && $object->mylticurrency_tx != 1 ? $object->multicurrency_total_ht : $object->total_ht);
		$pdf->SetXY($col2x, $tab2_top + 0);
		$pdf->MultiCell($largcol2, $tab2_hl, price($sign * ($total_ht + (! empty($object->remise)?$object->remise:0)), 0, $outputlangs), 0, 'R', 1);

		// Show VAT by rates and total
		$pdf->SetFillColor(248,248,248);

		$total_ttc = ($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? $object->multicurrency_total_ttc : $object->total_ttc;

		$this->atleastoneratenotnull=0;
		if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT))
		{
			$tvaisnull=((! empty($this->tva) && count($this->tva) == 1 && isset($this->tva['0.000']) && is_float($this->tva['0.000'])) ? true : false);
			if (! empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_IFNULL) && $tvaisnull)
			{
				// Nothing to do
			}
			else
			{
			    // FIXME amount of vat not supported with multicurrency

				//Local tax 1 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}

								$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					}
	      		//}
				//Local tax 2 before VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('1','3','5'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey!=0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;



								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					}

                //}

				// VAT
				// Situations totals migth be wrong on huge amounts
				if ($object->situation_cycle_ref && $object->situation_counter > 1) {

					$sum_pdf_tva = 0;
					foreach($this->tva as $tvakey => $tvaval){
						$sum_pdf_tva+=$tvaval; // sum VAT amounts to compare to object
					}

					if($sum_pdf_tva!=$object->total_tva) { // apply coef to recover the VAT object amount (the good one)
						$coef_fix_tva = $object->total_tva / $sum_pdf_tva;

						foreach($this->tva as $tvakey => $tvaval) {
							$this->tva[$tvakey]=$tvaval * $coef_fix_tva;
						}
					}
				}

				foreach($this->tva as $tvakey => $tvaval)
				{
					if ($tvakey != 0)    // On affiche pas taux 0
					{
						$this->atleastoneratenotnull++;

						$index++;
						$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

						$tvacompl='';
						if (preg_match('/\*/',$tvakey))
						{
							$tvakey=str_replace('*','',$tvakey);
							$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
						}
						$totalvat =$outputlangs->transcountrynoentities("TotalVAT",$mysoc->country_code).' ';
						$totalvat.=vatrate($tvakey,1).$tvacompl;
						$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

						$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
						$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
					}
				}

				//Local tax 1 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX1_OPTION) && $conf->global->FACTURE_LOCAL_TAX1_OPTION=='localtax1on')
				//{
					foreach( $this->localtax1 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT1",$mysoc->country_code).' ';
								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;

								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);
								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					}
	      		//}
				//Local tax 2 after VAT
				//if (! empty($conf->global->FACTURE_LOCAL_TAX2_OPTION) && $conf->global->FACTURE_LOCAL_TAX2_OPTION=='localtax2on')
				//{
					foreach( $this->localtax2 as $localtax_type => $localtax_rate )
					{
						if (in_array((string) $localtax_type, array('2','4','6'))) continue;

						foreach( $localtax_rate as $tvakey => $tvaval )
						{
						    // retrieve global local tax
							if ($tvakey != 0)    // On affiche pas taux 0
							{
								//$this->atleastoneratenotnull++;

								$index++;
								$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);

								$tvacompl='';
								if (preg_match('/\*/',$tvakey))
								{
									$tvakey=str_replace('*','',$tvakey);
									$tvacompl = " (".$outputlangs->transnoentities("NonPercuRecuperable").")";
								}
								$totalvat = $outputlangs->transcountrynoentities("TotalLT2",$mysoc->country_code).' ';

								$totalvat.=vatrate(abs($tvakey),1).$tvacompl;
								$pdf->MultiCell($col2x-$col1x, $tab2_hl, $totalvat, 0, 'L', 1);

								$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
								$pdf->MultiCell($largcol2, $tab2_hl, price($tvaval, 0, $outputlangs), 0, 'R', 1);
							}
						}
					//}
				}

				// Revenue stamp
				if (price2num($object->revenuestamp) != 0)
				{
					$index++;
					$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RevenueStamp"), $useborder, 'L', 1);

					$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
					$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $object->revenuestamp), $useborder, 'R', 1);
				}

				// Total TTC
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->SetTextColor(0,0,60);
				$pdf->SetFillColor(224,224,224);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("TotalTTC"), $useborder, 'L', 1);

				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($sign * $total_ttc, 0, $outputlangs), $useborder, 'R', 1);
			}
		}

		$pdf->SetTextColor(0,0,0);

		$creditnoteamount=$object->getSumCreditNotesUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
		$depositsamount=$object->getSumDepositsUsed(($conf->multicurrency->enabled && $object->multicurrency_tx != 1) ? 1 : 0);
		//print "x".$creditnoteamount."-".$depositsamount;exit;
		$resteapayer = price2num($total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 'MT');
		if ($object->paye) $resteapayer=0;

		if (($deja_regle > 0 || $creditnoteamount > 0 || $depositsamount > 0) && empty($conf->global->INVOICE_NO_PAYMENT_DETAILS))
		{
			// Already paid + Deposits
			$index++;
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("Paid"), 0, 'L', 0);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($deja_regle + $depositsamount, 0, $outputlangs), 0, 'R', 0);

			// Credit note
			if ($creditnoteamount)
			{
				$index++;
				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("CreditNotes"), 0, 'L', 0);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($creditnoteamount, 0, $outputlangs), 0, 'R', 0);
			}

			// Escompte
			if ($object->close_code == Facture::CLOSECODE_DISCOUNTVAT)
			{
				$index++;
				$pdf->SetFillColor(255,255,255);

				$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("EscompteOfferedShort"), $useborder, 'L', 1);
				$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
				$pdf->MultiCell($largcol2, $tab2_hl, price($object->total_ttc - $deja_regle - $creditnoteamount - $depositsamount, 0, $outputlangs), $useborder, 'R', 1);

				$resteapayer=0;
			}

			$index++;
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFillColor(224,224,224);
			$pdf->SetXY($col1x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($col2x-$col1x, $tab2_hl, $outputlangs->transnoentities("RemainderToPay"), $useborder, 'L', 1);
			$pdf->SetXY($col2x, $tab2_top + $tab2_hl * $index);
			$pdf->MultiCell($largcol2, $tab2_hl, price($resteapayer, 0, $outputlangs), $useborder, 'R', 1);

			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetTextColor(0,0,0);
		}

		$index++;
		return ($tab2_top + ($tab2_hl * $index));
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


		foreach ($this->cols as $colKey => $colDef)
		{
		    if(!$this->getColumnStatus($colKey)) continue;

		    // get title label
		    $colDef['title']['label'] = !empty($colDef['title']['label'])?$colDef['title']['label']:$outputlangs->transnoentities($colDef['title']['textkey']);

		    // Add column separator
		    if(!empty($colDef['border-left'])){
		        $pdf->line($colDef['xStartPos'], $tab_top, $colDef['xStartPos'], $tab_top + $tab_height);
		    }

		    if (empty($hidetop))
		    {
		      $pdf->SetXY($colDef['xStartPos'] + $colDef['title']['padding'][3], $tab_top + $colDef['title']['padding'][0] );

		      $textWidth = $colDef['width'] - $colDef['title']['padding'][3] -$colDef['title']['padding'][1];
		      $pdf->MultiCell($textWidth,2,$colDef['title']['label'],'',$colDef['title']['align']);
		    }
		}

		if (empty($hidetop)){
			$pdf->line($this->marge_gauche, $tab_top+5, $this->page_largeur-$this->marge_droite, $tab_top+5);	// line prend une position y en 2eme param et 4eme param
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
		global $conf, $langs;

		// Translations
		$outputlangs->loadLangs(array("main", "bills", "propal", "companies"));

		$default_font_size = pdf_getPDFFontSize($outputlangs);

		pdf_pagehead($pdf,$outputlangs,$this->page_hauteur);

		// Show Draft Watermark
		if($object->statut==Facture::STATUS_DRAFT && (! empty($conf->global->FACTURE_DRAFT_WATERMARK)) )
        {
		      pdf_watermark($pdf,$outputlangs,$this->page_hauteur,$this->page_largeur,'mm',$conf->global->FACTURE_DRAFT_WATERMARK);
        }

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('','B', $default_font_size + 3);

		$w = 110;

		$posy=$this->marge_haute;
        $posx=$this->page_largeur-$this->marge_droite-$w;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		if (empty($conf->global->PDF_DISABLE_MYCOMPANY_LOGO))
		{
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
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
					$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ErrorGoToGlobalSetup"), 0, 'L');
				}
			}
			else
			{
				$text=$this->emetteur->name;
				$pdf->MultiCell($w, 4, $outputlangs->convToOutputCharset($text), 0, 'L');
			}
		}

		$pdf->SetFont('','B', $default_font_size + 3);
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$title=$outputlangs->transnoentities("PdfInvoiceTitle");
		if ($object->type == 1) $title=$outputlangs->transnoentities("InvoiceReplacement");
		if ($object->type == 2) $title=$outputlangs->transnoentities("InvoiceAvoir");
		if ($object->type == 3) $title=$outputlangs->transnoentities("InvoiceDeposit");
		if ($object->type == 4) $title=$outputlangs->transnoentities("InvoiceProForma");
		if ($this->situationinvoice) $title=$outputlangs->transnoentities("InvoiceSituation");
		$pdf->MultiCell($w, 3, $title, '', 'R');

		$pdf->SetFont('','B',$default_font_size);

		$posy+=5;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$textref=$outputlangs->transnoentities("Ref")." : " . $outputlangs->convToOutputCharset($object->ref);
		if ($object->statut == Facture::STATUS_DRAFT)
		{
			$pdf->SetTextColor(128,0,0);
			$textref.=' - '.$outputlangs->transnoentities("NotValidated");
		}
		$pdf->MultiCell($w, 4, $textref, '', 'R');

		$posy+=1;
		$pdf->SetFont('','', $default_font_size - 2);

		if ($object->ref_client)
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("RefCustomer")." : " . $outputlangs->convToOutputCharset($object->ref_client), '', 'R');
		}

		$objectidnext=$object->getIdReplacingInvoice('validated');
		if ($object->type == 0 && $objectidnext)
		{
			$objectreplacing=new Facture($this->db);
			$objectreplacing->fetch($objectidnext);

			$posy+=3;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementByInvoice").' : '.$outputlangs->convToOutputCharset($objectreplacing->ref), '', 'R');
		}
		if ($object->type == 1)
		{
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("ReplacementInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}
		if ($object->type == 2 && !empty($object->fk_facture_source))
		{
			$objectreplaced=new Facture($this->db);
			$objectreplaced->fetch($object->fk_facture_source);

			$posy+=3;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CorrectionInvoice").' : '.$outputlangs->convToOutputCharset($objectreplaced->ref), '', 'R');
		}

		$posy+=4;
		$pdf->SetXY($posx,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell($w, 3, $outputlangs->transnoentities("DateInvoice")." : " . dol_print_date($object->date,"day",false,$outputlangs), '', 'R');

		if (! empty($conf->global->INVOICE_POINTOFTAX_DATE))
		{
			$posy+=4;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("DatePointOfTax")." : " . dol_print_date($object->date_pointoftax,"day",false,$outputlangs), '', 'R');
		}

		if ($object->type != 2)
		{
			$posy+=3;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("DateDue")." : " . dol_print_date($object->date_lim_reglement,"day",false,$outputlangs,true), '', 'R');
		}

		if ($object->thirdparty->code_client)
		{
			$posy+=3;
			$pdf->SetXY($posx,$posy);
			$pdf->SetTextColor(0,0,60);
			$pdf->MultiCell($w, 3, $outputlangs->transnoentities("CustomerCode")." : " . $outputlangs->transnoentities($object->thirdparty->code_client), '', 'R');
		}

		// Get contact
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
		        $pdf->MultiCell($w, 3, $langs->transnoentities("SalesRepresentative")." : ".$usertmp->getFullName($langs), '', 'R');
		    }
		}

		$posy+=1;

		$top_shift = 0;
		// Show list of linked objects
		$current_y = $pdf->getY();
		$posy = pdf_writeLinkedObjects($pdf, $object, $outputlangs, $posx, $posy, $w, 3, 'R', $default_font_size);
		if ($current_y < $pdf->getY())
		{
			$top_shift = $pdf->getY() - $current_y;
		}

		if ($showaddress)
		{
			// Sender properties
			$carac_emetteur = pdf_build_address($outputlangs, $this->emetteur, $object->thirdparty, '', 0, 'source', $object);

			// Show sender
			$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posy+=$top_shift;
			$posx=$this->marge_gauche;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->page_largeur-$this->marge_droite-80;

			$hautcadre=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 38 : 40;
			$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 82;


			// Show sender frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx,$posy-5);
			$pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":", 0, 'L');
			$pdf->SetXY($posx,$posy);
			$pdf->SetFillColor(230,230,230);
			$pdf->MultiCell($widthrecbox, $hautcadre, "", 0, 'R', 1);
			$pdf->SetTextColor(0,0,60);

			// Show sender name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox-2, 4, $outputlangs->convToOutputCharset($this->emetteur->name), 0, 'L');
			$posy=$pdf->getY();

			// Show sender information
			$pdf->SetXY($posx+2,$posy);
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->MultiCell($widthrecbox-2, 4, $carac_emetteur, 0, 'L');

			// If BILLING contact defined on invoice, we use it
			$usecontact=false;
			$arrayidcontact=$object->getIdContact('external','BILLING');
			if (count($arrayidcontact) > 0)
			{
				$usecontact=true;
				$result=$object->fetch_contact($arrayidcontact[0]);
			}

			//Recipient name
			// On peut utiliser le nom de la societe du contact
			if ($usecontact && !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) {
				$thirdparty = $object->contact;
			} else {
				$thirdparty = $object->thirdparty;
			}

			$carac_client_name= pdfBuildThirdpartyName($thirdparty, $outputlangs);

			$carac_client=pdf_build_address($outputlangs,$this->emetteur,$object->thirdparty,($usecontact?$object->contact:''),$usecontact,'target',$object);

			// Show recipient
			$widthrecbox=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 92 : 100;
			if ($this->page_largeur < 210) $widthrecbox=84;	// To work with US executive format
			$posy=!empty($conf->global->MAIN_PDF_USE_ISO_LOCATION) ? 40 : 42;
			$posy+=$top_shift;
			$posx=$this->page_largeur-$this->marge_droite-$widthrecbox;
			if (! empty($conf->global->MAIN_INVERT_SENDER_RECIPIENT)) $posx=$this->marge_gauche;

			// Show recipient frame
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('','', $default_font_size - 2);
			$pdf->SetXY($posx+2,$posy-5);
			$pdf->MultiCell($widthrecbox, 5, $outputlangs->transnoentities("BillTo").":",0,'L');
			$pdf->Rect($posx, $posy, $widthrecbox, $hautcadre);

			// Show recipient name
			$pdf->SetXY($posx+2,$posy+3);
			$pdf->SetFont('','B', $default_font_size);
			$pdf->MultiCell($widthrecbox, 2, $carac_client_name, 0, 'L');

			$posy = $pdf->getY();

			// Show recipient information
			$pdf->SetFont('','', $default_font_size - 1);
			$pdf->SetXY($posx+2,$posy);
			$pdf->MultiCell($widthrecbox, 4, $carac_client, 0, 'L');
		}

		$pdf->SetTextColor(0,0,0);
		return $top_shift;
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
		return pdf_pagefoot($pdf,$outputlangs,'INVOICE_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object,$showdetails,$hidefreetext);
	}

	/**
	 *   	Define Array Column Field
	 *
	 *   	@param	object			$object    		common object
	 *   	@param	outputlangs		$outputlangs    langs
     *      @param	int			   $hidedetails		Do not show line details
     *      @param	int			   $hidedesc		Do not show desc
     *      @param	int			   $hideref			Do not show ref
	 *      @return	null
	 */
    function defineColumnField($object,$outputlangs,$hidedetails=0,$hidedesc=0,$hideref=0)
    {
	    global $conf, $hookmanager;

	    // Default field style for content
	    $this->defaultContentsFieldsStyle = array(
	        'align' => 'R', // R,C,L
	        'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
	    );

	    // Default field style for content
	    $this->defaultTitlesFieldsStyle = array(
	        'align' => 'C', // R,C,L
	        'padding' => array(0.5,0,0.5,0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
	    );

	    /*
	     * For exemple
	    $this->cols['theColKey'] = array(
	        'rank' => $rank, // int : use for ordering columns
	        'width' => 20, // the column width in mm
	        'title' => array(
	            'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
	            'label' => ' ', // the final label : used fore final generated text
	            'align' => 'L', // text alignement :  R,C,L
	            'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
	        ),
	        'content' => array(
	            'align' => 'L', // text alignement :  R,C,L
	            'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
	        ),
	    );
	    */

	    $rank=0; // do not use negative rank
	    $this->cols['desc'] = array(
	        'rank' => $rank,
	        'width' => false, // only for desc
	        'status' => true,
	        'title' => array(
	            'textkey' => 'Designation', // use lang key is usefull in somme case with module
	            'align' => 'L',
	            // 'textkey' => 'yourLangKey', // if there is no label, yourLangKey will be translated to replace label
	            // 'label' => ' ', // the final label
	            'padding' => array(0.5,0.5,0.5,0.5), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
	        ),
	        'content' => array(
	            'align' => 'L',
	        ),
	    );

	    // PHOTO
        $rank = $rank + 10;
        $this->cols['photo'] = array(
            'rank' => $rank,
            'width' => (empty($conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH)?20:$conf->global->MAIN_DOCUMENTS_WITH_PICTURE_WIDTH), // in mm
            'status' => false,
            'title' => array(
                'textkey' => 'Photo',
                'label' => ' '
            ),
            'content' => array(
                'padding' => array(0,0,0,0), // Like css 0 => top , 1 => right, 2 => bottom, 3 => left
            ),
            'border-left' => false, // remove left line separator
        );

	    if (! empty($conf->global->MAIN_GENERATE_INVOICES_WITH_PICTURE) && !empty($this->atleastonephoto))
	    {
	        $this->cols['photo']['status'] = true;
	    }


	    $rank = $rank + 10;
	    $this->cols['vat'] = array(
	        'rank' => $rank,
	        'status' => false,
	        'width' => 16, // in mm
	        'title' => array(
	            'textkey' => 'VAT'
	        ),
	        'border-left' => true, // add left line separator
	    );

	    if (empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT) && empty($conf->global->MAIN_GENERATE_DOCUMENTS_WITHOUT_VAT_COLUMN))
	    {
	        $this->cols['vat']['status'] = true;
	    }

	    $rank = $rank + 10;
	    $this->cols['subprice'] = array(
	        'rank' => $rank,
	        'width' => 19, // in mm
	        'status' => true,
	        'title' => array(
	            'textkey' => 'PriceUHT'
	        ),
	        'border-left' => true, // add left line separator
	    );

	    $rank = $rank + 10;
	    $this->cols['qty'] = array(
	        'rank' => $rank,
	        'width' => 16, // in mm
	        'status' => true,
	        'title' => array(
	            'textkey' => 'Qty'
	        ),
	        'border-left' => true, // add left line separator
	    );

	    $rank = $rank + 10;
	    $this->cols['progress'] = array(
	        'rank' => $rank,
	        'width' => 19, // in mm
	        'status' => false,
	        'title' => array(
	            'textkey' => 'Progress'
	        ),
	        'border-left' => true, // add left line separator
	    );

	    if($this->situationinvoice)
	    {
	        $this->cols['progress']['status'] = true;
	    }

	    $rank = $rank + 10;
	    $this->cols['unit'] = array(
	        'rank' => $rank,
	        'width' => 11, // in mm
	        'status' => false,
	        'title' => array(
	            'textkey' => 'Unit'
	        ),
	        'border-left' => true, // add left line separator
	    );
	    if($conf->global->PRODUCT_USE_UNITS){
	        $this->cols['unit']['status'] = true;
	    }

	    $rank = $rank + 10;
	    $this->cols['discount'] = array(
	        'rank' => $rank,
	        'width' => 13, // in mm
	        'status' => false,
	        'title' => array(
	            'textkey' => 'ReductionShort'
	        ),
	        'border-left' => true, // add left line separator
	    );
	    if ($this->atleastonediscount){
	        $this->cols['discount']['status'] = true;
	    }

	    $rank = $rank + 10;
	    $this->cols['totalexcltax'] = array(
	        'rank' => $rank,
	        'width' => 26, // in mm
	        'status' => true,
	        'title' => array(
	            'textkey' => 'TotalHT'
	        ),
	        'border-left' => true, // add left line separator
	    );


	    $parameters=array(
	        'object' => $object,
	        'outputlangs' => $outputlangs,
	        'hidedetails' => $hidedetails,
	        'hidedesc' => $hidedesc,
	        'hideref' => $hideref
	    );

	    $reshook=$hookmanager->executeHooks('defineColumnField',$parameters,$this);    // Note that $object may have been modified by hook
	    if ($reshook < 0)
	    {
	        setEventMessages($hookmanager->error, $hookmanager->errors, 'errors');
	    }
	    elseif (empty($reshook))
	    {
	        $this->cols = array_replace($this->cols, $hookmanager->resArray); // array_replace is used to preserve keys
	    }
	    else
	    {
	        $this->cols = $hookmanager->resArray;
	    }
	}
}
