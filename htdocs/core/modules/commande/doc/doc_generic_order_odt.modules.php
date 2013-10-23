<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
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
 *	\file       htdocs/core/modules/commande/doc/doc_generic_order_odt.modules.php
 *	\ingroup    commande
 *	\brief      File of class to build ODT documents for third parties
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/commande/modules_commande.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class doc_generic_order_odt extends ModelePDFCommandes
{
	var $emetteur;	// Objet societe qui emet

	var $phpmin = array(5,2,0);	// Minimum version of PHP required by module
	var $version = 'dolibarr';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	function __construct($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("companies");

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'COMMANDE_ADDON_PDF_ODT_PATH';	// Name of constant that is used to save list of directories to scan

		// Dimension page pour format A4
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=0;
		$this->marge_droite=0;
		$this->marge_haute=0;
		$this->marge_basse=0;

		$this->option_logo = 1;                    // Affiche logo
		$this->option_tva = 0;                     // Gere option tva COMMANDE_TVAOPTION
		$this->option_modereg = 0;                 // Affiche mode reglement
		$this->option_condreg = 0;                 // Affiche conditions reglement
		$this->option_codeproduitservice = 0;      // Affiche code produit-service
		$this->option_multilang = 1;               // Dispo en plusieurs langues
		$this->option_escompte = 0;                // Affiche si il y a eu escompte
		$this->option_credit_note = 0;             // Support credit notes
		$this->option_freetext = 1;				   // Support add of a personalised text
		$this->option_draft_watermark = 0;		   // Support add of a watermark on drafts

		// Recupere emetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->country_code) $this->emetteur->country_code=substr($langs->defaultlang,-2);    // By default if not defined
	}


	/**
	 * Define array with couple substitution key => substitution value
	 *
	 * @param   Object			$object             Main object to use as data source
	 * @param   Translate		$outputlangs        Lang object to use for output
	 * @return	array								Array of substitution
	 */
	function get_substitutionarray_object($object,$outputlangs)
	{
		global $conf;

		$resarray=array(
		'object_id'=>$object->id,
		'object_ref'=>$object->ref,
		'object_ref_ext'=>$object->ref_ext,
		'object_ref_customer'=>$object->ref_client,
        'object_hour'=>dol_print_date($object->date,'hour'),
		'object_date'=>dol_print_date($object->date,'day'),
		'object_date_delivery'=>dol_print_date($object->date_livraison,'dayhour'),
		'object_date_creation'=>dol_print_date($object->date_creation,'day'),
		'object_date_modification'=>(! empty($object->date_modification)?dol_print_date($object->date_modification,'day'):''),
		'object_date_validation'=>(! empty($object->date_validation)?dol_print_date($object->date_validation,'dayhour'):''),
		'object_date_delivery_planed'=>(! empty($object->date_livraison)?dol_print_date($object->date_livraison,'day'):''),
		'object_date_close'=>dol_print_date($object->date_cloture,'dayhour'),
		'object_payment_mode_code'=>$object->mode_reglement_code,
		'object_payment_mode'=>($outputlangs->transnoentitiesnoconv('PaymentType'.$object->mode_reglement_code)!='PaymentType'.$object->mode_reglement_code?$outputlangs->transnoentitiesnoconv('PaymentType'.$object->mode_reglement_code):$object->mode_reglement),
		'object_payment_term_code'=>$object->cond_reglement_code,
		'object_payment_term'=>($outputlangs->transnoentitiesnoconv('PaymentCondition'.$object->cond_reglement_code)!='PaymentCondition'.$object->cond_reglement_code?$outputlangs->transnoentitiesnoconv('PaymentCondition'.$object->cond_reglement_code):$object->cond_reglement),

		'object_total_ht_locale'=>price($object->total_ht, 0, $outputlangs),
		'object_total_vat_locale'=>price($object->total_tva, 0, $outputlangs),
		'object_total_localtax1_locale'=>price($object->total_localtax1, 0, $outputlangs),
		'object_total_localtax2_locale'=>price($object->total_localtax2, 0, $outputlangs),
		'object_total_ttc_locale'=>price($object->total_ttc, 0, $outputlangs),
		'object_total_discount_ht_locale' => price($object->getTotalDiscount(), 0, $outputlangs),
		'object_total_ht'=>price2num($object->total_ht),
		'object_total_vat'=>price2num($object->total_tva),
		'object_total_localtax1'=>price2num($object->total_localtax1),
		'object_total_localtax2'=>price2num($object->total_localtax2),
		'object_total_ttc'=>price2num($object->total_ttc),
		'object_total_discount_ht' => price2num($object->getTotalDiscount()),

		'object_vatrate'=>vatrate($object->tva),
		'object_note_private'=>$object->note,
		'object_note'=>$object->note_public,
		);

		// Add vat by rates
		foreach ($object->lines as $line)
		{
			if (empty($resarray['object_total_vat_'.$line->tva_tx])) $resarray['object_total_vat_'.$line->tva_tx]=0;
			$resarray['object_total_vat_'.$line->tva_tx]+=$line->total_tva;
		}

		return $resarray;
	}

	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$line				Array of lines
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	function get_substitutionarray_lines($line,$outputlangs)
	{
		global $conf;

		return array(
		'line_fulldesc'=>doc_getlinedesc($line,$outputlangs),
		'line_product_ref'=>$line->product_ref,
		'line_product_label'=>$line->product_label,
		'line_desc'=>$line->desc,
		'line_vatrate'=>vatrate($line->tva_tx,true,$line->info_bits),
		'line_up'=>price($line->subprice, 0, $outputlangs),
		'line_qty'=>$line->qty,
		'line_discount_percent'=>($line->remise_percent?$line->remise_percent.'%':''),
		'line_price_ht'=>price($line->total_ht, 0, $outputlangs),
		'line_price_ttc'=>price($line->total_ttc, 0, $outputlangs),
		'line_price_vat'=>price($line->total_tva, 0, $outputlangs),
		'line_date_start'=>$line->date_start,
		'line_date_end'=>$line->date_end
		);
	}

	/**
	 *	Return description of a module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *	@return string       			Description
	 */
	function info($langs)
	{
		global $conf,$langs;

		$langs->load("companies");
		$langs->load("errors");

		$form = new Form($this->db);

		$texte = $this->description.".<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte.= '<input type="hidden" name="param1" value="COMMANDE_ADDON_PDF_ODT_PATH">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte.= '<tr><td>';
		$texttitle=$langs->trans("ListOfDirectories");
		$listofdir=explode(',',preg_replace('/[\r\n]+/',',',trim($conf->global->COMMANDE_ADDON_PDF_ODT_PATH)));
		$listoffiles=array();
		foreach($listofdir as $key=>$tmpdir)
		{
			$tmpdir=trim($tmpdir);
			$tmpdir=preg_replace('/DOL_DATA_ROOT/',DOL_DATA_ROOT,$tmpdir);
			if (! $tmpdir) {
				unset($listofdir[$key]); continue;
			}
			if (! is_dir($tmpdir)) $texttitle.=img_warning($langs->trans("ErrorDirNotFound",$tmpdir),0);
			else
			{
				$tmpfiles=dol_dir_list($tmpdir,'files',0,'\.odt');
				if (count($tmpfiles)) $listoffiles=array_merge($listoffiles,$tmpfiles);
			}
		}
		$texthelp=$langs->trans("ListOfDirectoriesForModelGenODT");
		// Add list of substitution keys
		$texthelp.='<br>'.$langs->trans("FollowingSubstitutionKeysCanBeUsed").'<br>';
		$texthelp.=$langs->transnoentitiesnoconv("FullListOnOnlineDocumentation");    // This contains an url, we don't modify it

		$texte.= $form->textwithpicto($texttitle,$texthelp,1,'help','',1);
		$texte.= '<table><tr><td>';
		$texte.= '<textarea class="flat" cols="60" name="value1">';
		$texte.=$conf->global->COMMANDE_ADDON_PDF_ODT_PATH;
		$texte.= '</textarea>';
		$texte.= '</td>';
		$texte.= '<td align="center">&nbsp; ';
		$texte.= '<input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button">';
		$texte.= '</td>';
		$texte.= '</tr>';
		$texte.= '</table>';

		// Scan directories
		if (count($listofdir)) $texte.=$langs->trans("NumberOfModelFilesFound").': <b>'.count($listoffiles).'</b>';

		$texte.= '</td>';


		$texte.= '<td valign="top" rowspan="2">';
		$texte.= $langs->trans("ExampleOfDirectoriesForModelGen");
		$texte.= '</td>';
		$texte.= '</tr>';

		/*$texte.= '<tr>';
		 $texte.= '<td align="center">';
		$texte.= '<input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button">';
		$texte.= '</td>';
		$texte.= '</tr>';*/

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
	}

	/**
	 *	Function to build a document on disk using the generic odt module.
	 *
	 *	@param		Commande	$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath,$hidedetails=0,$hidedesc=0,$hideref=0)
	{
		global $user,$langs,$conf,$mysoc,$hookmanager;

		if (empty($srctemplatepath))
		{
			dol_syslog("doc_generic_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if (! is_object($hookmanager))
		{
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager=new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));
		global $action;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		$sav_charset_output=$outputlangs->charset_output;
		$outputlangs->charset_output='UTF-8';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("bills");

		if ($conf->commande->dir_output)
		{
			// If $object is id instead of object
			if (! is_object($object))
			{
				$id = $object;
				$object = new Commande($this->db);
				$result=$object->fetch($id);
				if ($result < 0)
				{
					dol_print_error($this->db,$object->error);
					return -1;
				}
			}

			$dir = $conf->commande->dir_output;
			$objectref = dol_sanitizeFileName($object->ref);
			if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
			$file = $dir . "/" . $objectref . ".odt";

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
				//print "srctemplatepath=".$srctemplatepath;	// Src filename
				$newfile=basename($srctemplatepath);
				$newfiletmp=preg_replace('/\.od(t|s)/i','',$newfile);
				$newfiletmp=preg_replace('/template_/i','',$newfiletmp);
				$newfiletmp=preg_replace('/modele_/i','',$newfiletmp);
				$newfiletmp=$objectref.'_'.$newfiletmp;
				//$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now(),'%Y%m%d%H%M%S').'.odt';
				// Get extension (ods or odt)
				$newfileformat=substr($newfile, strrpos($newfile, '.')+1);
				if ( ! empty($conf->global->MAIN_DOC_USE_TIMING))
				{
					$filename=$newfiletmp.'.'.dol_print_date(dol_now(),'%Y%m%d%H%M%S').'.'.$newfileformat;
				}
				else
				{
					$filename=$newfiletmp.'.'.$newfileformat;
				}
				$file=$dir.'/'.$filename;
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;

				dol_mkdir($conf->commande->dir_temp);


				// If BILLING contact defined on invoice, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','BILLING');
				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				if (! empty($usecontact))
				{
					// On peut utiliser le nom de la societe du contact
					if (! empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT)) $socobject = $object->contact;
					else $socobject = $object->client;
				}
				else
				{
					$socobject=$object->client;
				}

				// Make substitution
				$substitutionarray=array(
				'__FROM_NAME__' => $this->emetteur->nom,
				'__FROM_EMAIL__' => $this->emetteur->email,
				'__TOTAL_TTC__' => $object->total_ttc,
				'__TOTAL_HT__' => $object->total_ht,
				'__TOTAL_VAT__' => $object->total_vat
				);
				complete_substitutions_array($substitutionarray, $langs, $object);
				// Call the ODTSubstitution hook
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$substitutionarray);
				$reshook=$hookmanager->executeHooks('ODTSubstitution',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				// Line of free text
				$newfreetext='';
				$paramfreetext='COMMANDE_FREE_TEXT';
				if (! empty($conf->global->$paramfreetext))
				{
					$newfreetext=make_substitutions($conf->global->$paramfreetext,$substitutionarray);
				}

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				try {
					$odfHandler = new odf(
						$srctemplatepath,
						array(
						'PATH_TO_TMP'	  => $conf->commande->dir_temp,
						'ZIP_PROXY'		  => 'PclZipProxy',	// PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
						'DELIMITER_LEFT'  => '{',
						'DELIMITER_RIGHT' => '}'
						)
					);
				}
				catch(Exception $e)
				{
					$this->error=$e->getMessage();
					return -1;
				}
				// After construction $odfHandler->contentXml contains content and
				// [!-- BEGIN row.lines --]*[!-- END row.lines --] has been replaced by
				// [!-- BEGIN lines --]*[!-- END lines --]
				//print html_entity_decode($odfHandler->__toString());
				//print exit;


				// Make substitutions into odt of freetext
				try {
					$odfHandler->setVars('free_text', $newfreetext, true, 'UTF-8');
				}
				catch(OdfException $e)
				{
				}

				// Make substitutions into odt of user info
				$tmparray=$this->get_substitutionarray_user($user,$outputlangs);
				//var_dump($tmparray); exit;
				foreach($tmparray as $key=>$value)
				{
					try {
						if (preg_match('/logo$/',$key)) // Image
						{
							//var_dump($value);exit;
							if (file_exists($value)) $odfHandler->setImage($key, $value);
							else $odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
						}
						else    // Text
						{
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					}
					catch(OdfException $e)
					{
					}
				}
				// Make substitutions into odt of mysoc
				$tmparray=$this->get_substitutionarray_mysoc($mysoc,$outputlangs);
				//var_dump($tmparray); exit;
				foreach($tmparray as $key=>$value)
				{
					try {
						if (preg_match('/logo$/',$key))	// Image
						{
							//var_dump($value);exit;
							if (file_exists($value)) $odfHandler->setImage($key, $value);
							else $odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
						}
						else	// Text
						{
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					}
					catch(OdfException $e)
					{
					}
				}
				// Make substitutions into odt of thirdparty
				$tmparray=$this->get_substitutionarray_thirdparty($socobject,$outputlangs);
				foreach($tmparray as $key=>$value)
				{
					try {
						if (preg_match('/logo$/',$key))	// Image
						{
							if (file_exists($value)) $odfHandler->setImage($key, $value);
							else $odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
						}
						else	// Text
						{
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					}
					catch(OdfException $e)
					{
					}
				}
				// Replace tags of object + external modules
				$tmparray=$this->get_substitutionarray_object($object,$outputlangs);
				complete_substitutions_array($tmparray, $outputlangs, $object);
				// Call the ODTSubstitution hook
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
				$reshook=$hookmanager->executeHooks('ODTSubstitution',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
				foreach($tmparray as $key=>$value)
				{
					try {
						if (preg_match('/logo$/',$key)) // Image
						{
							if (file_exists($value)) $odfHandler->setImage($key, $value);
							else $odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
						}
						else    // Text
						{
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					}
					catch(OdfException $e)
					{
					}
				}
				// Replace tags of lines
				try
				{
					$listlines = $odfHandler->setSegment('lines');
					foreach ($object->lines as $line)
					{
						$tmparray=$this->get_substitutionarray_lines($line,$outputlangs);
						complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
						// Call the ODTSubstitutionLine hook
						$parameters=array('odfHandler'=>&$odfHandler,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray,'line'=>$line);
						$reshook=$hookmanager->executeHooks('ODTSubstitutionLine',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks
						foreach($tmparray as $key => $val)
						{
							try
							{
								$listlines->setVars($key, $val, true, 'UTF-8');
							}
							catch(OdfException $e)
							{
							}
							catch(SegmentException $e)
							{
							}
						}
						$listlines->merge();
					}
					$odfHandler->mergeSegment($listlines);
				}
				catch(OdfException $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -1;
				}

				// Replace labels translated
				$tmparray=$outputlangs->get_translations_for_substitutions();
				foreach($tmparray as $key=>$value)
				{
					try {
						$odfHandler->setVars($key, $value, true, 'UTF-8');
					}
					catch(OdfException $e)
					{
					}
				}

				// Call the beforeODTSave hook
				$parameters=array('odfHandler'=>&$odfHandler,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs);
				$reshook=$hookmanager->executeHooks('beforeODTSave',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				// Write new file
				if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
					try {
						$odfHandler->exportAsAttachedPDF($file);
					}catch (Exception $e){
						$this->error=$e->getMessage();
						return -1;
					}
				}
				else {
					try {
					$odfHandler->saveToDisk($file);
					}catch (Exception $e){
						$this->error=$e->getMessage();
						return -1;
					}
				}

				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$odfHandler=null;	// Destroy object

				return 1;   // Success
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return -1;
			}
		}

		return -1;
	}

}

?>
