<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018-2019  Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
 * Copyright (C) 2020       Tim Otte		    <otte@meuser.it>
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
 *	\file       htdocs/core/modules/stock/doc/movement/doc_generic_stock_movement_odt.modules.php
 *	\ingroup    stock
 *	\brief      File of class to build ODT documents for stock movements
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/stock/modules_movement.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class doc_generic_stock_movement_odt extends ModelePDFMovement
{
	/**
	 * Issuer
	 * @var Societe
	 */
	public $issuer;

	/**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
	public $phpmin = array(5, 5);

	/**
     * @var string Dolibarr version of the loaded document
     */
	public $version = 'dolibarr';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
    public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Load translation files required by the page
        $langs->loadLangs(array("main","companies","stocks"));

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'STOCK_MOVEMENT_ADDON_PDF_ODT_PATH';	// Name of constant that is used to save list of directories to scan

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

		// Recupere issuer
		$this->issuer=$mysoc;
		if (! $this->issuer->country_code) $this->issuer->country_code=substr($langs->defaultlang, -2);    // By default if not defined
	}


	/**
	 *	Return description of a module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *	@return string       			Description
	 */
    public function info($langs)
	{
		global $conf,$langs;

		// Load translation files required by the page
        $langs->loadLangs(array("errors","companies"));

		$form = new Form($this->db);

		$texte = $this->description.".<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte.= '<input type="hidden" name="param1" value="STOCK_MOVEMENT_ADDON_PDF_ODT_PATH">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte.= '<tr><td>';
		$texttitle=$langs->trans("ListOfDirectories");
		$listofdir=explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->STOCK_MOVEMENT_ADDON_PDF_ODT_PATH)));
		$listoffiles=array();
		foreach($listofdir as $key=>$tmpdir)
		{
			$tmpdir=trim($tmpdir);
			$tmpdir=preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if (! $tmpdir) {
				unset($listofdir[$key]); continue;
			}
			if (! is_dir($tmpdir)) $texttitle.=img_warning($langs->trans("ErrorDirNotFound", $tmpdir), 0);
			else
			{
				$tmpfiles=dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
				if (count($tmpfiles)) $listoffiles=array_merge($listoffiles, $tmpfiles);
			}
		}
		$texthelp=$langs->trans("ListOfDirectoriesForModelGenODT");
		// Add list of substitution keys
		$texthelp.='<br>'.$langs->trans("FollowingSubstitutionKeysCanBeUsed").'<br>';
		$texthelp.=$langs->transnoentitiesnoconv("FullListOnOnlineDocumentation");    // This contains an url, we don't modify it

		$texte.= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1);
		$texte.= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$texte.= '<textarea class="flat" cols="60" name="value1">';
		$texte.=$conf->global->STOCK_MOVEMENT_ADDON_PDF_ODT_PATH;
		$texte.= '</textarea>';
		$texte.= '</div><div style="display: inline-block; vertical-align: middle;">';
		$texte.= '<input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button">';
		$texte.= '<br></div></div>';

		// Scan directories
		$nbofiles=count($listoffiles);
		if (! empty($conf->global->STOCK_MOVEMENT_ADDON_PDF_ODT_PATH))
		{
			$texte.=$langs->trans("NumberOfModelFilesFound").': <b>';
			//$texte.=$nbofiles?'<a id="a_'.get_class($this).'" href="#">':'';
			$texte.=count($listoffiles);
			//$texte.=$nbofiles?'</a>':'';
			$texte.='</b>';
		}

		if ($nbofiles)
		{
   			$texte.='<div id="div_'.get_class($this).'" class="hidden">';
   			foreach($listoffiles as $file)
   			{
                $texte.=$file['name'].'<br>';
   			}
   			$texte.='<div id="div_'.get_class($this).'">';
		}

		$texte.= '</td>';

		$texte.= '<td valign="top" rowspan="2" class="hideonsmartphone">';
		$texte.= $langs->trans("ExampleOfDirectoriesForModelGen");
		$texte.= '</td>';
		$texte.= '</tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 *	@param		Commande	$object				Object source to build document
	 *	@param		Translate	$outputlangs		Lang output object
	 * 	@param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int			$hidedetails		Do not show line details
	 *  @param		int			$hidedesc			Do not show desc
	 *  @param		int			$hideref			Do not show ref
	 *	@return		int         					1 if OK, <=0 if KO
	 */
    public function write_file($object, $outputlangs, $srctemplatepath, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
        // phpcs:enable
		global $user,$langs,$conf,$mysoc,$hookmanager;

		if (empty($srctemplatepath))
		{
			dol_syslog("doc_generic_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		$entrepot = new Entrepot($this->db);
		$entrepot->fetch($object->warehouse_id);

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

		$outputlangs->loadLangs(array("main", "dict", "companies", "bills"));

		if ($conf->stock->movement->dir_output)
		{
            if ($object->specimen)
			{
				$dir = $conf->stock->movement->dir_output;
				$file = $dir . "/SPECIMEN.pdf";
			}
			else
			{
				$objectref = dol_sanitizeFileName($object->ref);
				$dir = $conf->stock->movement->dir_output . '/'. $objectref;
				$file = $dir . "/" . $objectref . ".pdf";
			}

			if (! file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return -1;
				}
			}

			if (file_exists($dir))
			{
				//print "srctemplatepath=".$srctemplatepath;	// Src filename
				$newfile=basename($srctemplatepath);
				$newfiletmp=preg_replace('/\.od(t|s)/i', '', $newfile);
				$newfiletmp=preg_replace('/template_/i', '', $newfiletmp);
				$newfiletmp=preg_replace('/modele_/i', '', $newfiletmp);
				$newfiletmp=$objectref.'_'.$newfiletmp;
				//$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now(),'%Y%m%d%H%M%S').'.odt';
				// Get extension (ods or odt)
				$newfileformat=substr($newfile, strrpos($newfile, '.')+1);
				if ( ! empty($conf->global->MAIN_DOC_USE_TIMING))
				{
				    $format=$conf->global->MAIN_DOC_USE_TIMING;
				    if ($format == '1') $format='%Y%m%d%H%M%S';
					$filename=$newfiletmp.'-'.dol_print_date(dol_now(), $format).'.'.$newfileformat;
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

				dol_mkdir($conf->stock->movement->dir_temp);

				// Make substitution
				$substitutionarray=array(
				'__FROM_NAME__' => $this->issuer->name,
				'__FROM_EMAIL__' => $this->issuer->email
				);
				complete_substitutions_array($substitutionarray, $langs, $entrepot);
				// Call the ODTSubstitution hook
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$substitutionarray);
				$reshook=$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				// Line of free text
				$newfreetext='';
				$paramfreetext='ORDER_FREE_TEXT';
				if (! empty($conf->global->$paramfreetext))
				{
					$newfreetext=make_substitutions($conf->global->$paramfreetext, $substitutionarray);
				}

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				try {
                    $odfHandler = new odf(
						$srctemplatepath,
						array(
						'PATH_TO_TMP'	  => $conf->stock->movement->dir_temp,
						'ZIP_PROXY'		  => 'PclZipProxy',	// PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
						'DELIMITER_LEFT'  => '{',
						'DELIMITER_RIGHT' => '}'
						)
					);
				}
				catch(Exception $e)
				{
					$this->error=$e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
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
                    dol_syslog($e->getMessage(), LOG_INFO);
				}

				// Define substitution array
				$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $entrepot);
				$array_object_from_properties=$this->get_substitutionarray_each_var_object($entrepot, $outputlangs);
				$array_objet=$this->get_substitutionarray_object($entrepot, $outputlangs);
				$array_user=$this->get_substitutionarray_user($user, $outputlangs);
				$array_soc=$this->get_substitutionarray_mysoc($mysoc, $outputlangs);
				$array_other=$this->get_substitutionarray_other($outputlangs);
				// retrieve contact information for use in object as contact_xxx tags
				$array_thirdparty_contact = array();
				if ($usecontact && is_object($contactobject)) $array_thirdparty_contact=$this->get_substitutionarray_contact($contactobject, $outputlangs, 'contact');

				$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_user, $array_soc, $array_objet, $array_other, $array_thirdparty_contact);
				complete_substitutions_array($tmparray, $outputlangs, $entrepot);

				// Call the ODTSubstitution hook
				$parameters=array('odfHandler'=>&$odfHandler,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
				$reshook=$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				foreach($tmparray as $key=>$value)
				{
					try {
						if (preg_match('/logo$/', $key)) // Image
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
                        dol_syslog($e->getMessage(), LOG_INFO);
					}
				}
				// Replace tags of lines
				try
				{
					$foundtagforlines = 1;
					try {
						$listlines = $odfHandler->setSegment('lines');
					}
					catch(OdfException $e)
					{
						// We may arrive here if tags for lines not present into template
						$foundtagforlines = 0;
						dol_syslog($e->getMessage(), LOG_INFO);
					}
					if ($foundtagforlines)
					{
						$lines = $this->get_stock_movement_lines();
						foreach ($lines as $line)
						{
							$tmparray=$this->get_substitutionarray_lines($line, $outputlangs);
							complete_substitutions_array($tmparray, $outputlangs, $entrepot, $line, "completesubstitutionarray_lines");
							// Call the ODTSubstitutionLine hook
							$parameters=array('odfHandler'=>&$odfHandler,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray,'line'=>$line);
							$reshook=$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks
							foreach($tmparray as $key => $val)
							{
								try
								{
									$listlines->setVars($key, $val, true, 'UTF-8');
								}
								catch(OdfException $e)
								{
                                    dol_syslog($e->getMessage(), LOG_INFO);
								}
								catch(SegmentException $e)
								{
                                    dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlines->merge();
						}
						$odfHandler->mergeSegment($listlines);
					}
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
                        dol_syslog($e->getMessage(), LOG_INFO);
					}
				}

				// Call the beforeODTSave hook

				$parameters=array('odfHandler'=>&$odfHandler,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
				$reshook=$hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				// Write new file
				if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
					try {
						$odfHandler->exportAsAttachedPDF($file);
					}catch (Exception $e){
                        $this->error=$e->getMessage();
                        dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				}
				else {
					try {
						$odfHandler->saveToDisk($file);
					} catch (Exception $e) {
                        $this->error=$e->getMessage();
                        dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				}

				$parameters=array('odfHandler'=>&$odfHandler,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
				$reshook=$hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action);    // Note that $action and $object may have been modified by some hooks

				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				$odfHandler=null;	// Destroy object

				$this->result = array('fullpath'=>$file);

				return 1;   // Success
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		return -1;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Define array with couple substitution key => substitution value
	 *
	 * @param   Object			$object             Main object to use as data source
	 * @param   Translate		$outputlangs        Lang object to use for output
	 * @return	array								Array of substitution
	 */
	public function get_substitutionarray_object($object, $outputlangs)
	{
		global $langs;

		$calcproductsunique = $object->nb_different_products();
		$calcproducts = $object->nb_products();

		$substitutionarray = parent::get_substitutionarray_object($object, $outputlangs);
		$substitutionarray['object_description'] = $object->description;
		$substitutionarray['object_nb_products_unique'] = $calcproductsunique['nb'];
		$substitutionarray['object_nb_products_total'] = price2num($calcproducts['nb'], 'MS');

		// Last movement
		$sql_res = $this->db->query("SELECT max(m.datem) as datem FROM " . MAIN_DB_PREFIX . "stock_mouvement as m WHERE m.fk_entrepot = '". $object->id . "'");
		$lastmovementdate = "";
		if ($sql_res)
		{
			$obj = $this->db->fetch_object($sql_res);
			$lastmovementdate = $this->db->jdate($obj->datem);
		}

		$substitutionarray['object_last_movement'] = $lastmovementdate != "" ? dol_print_date($lastmovementdate, 'dayhour') : $langs->transnoentities("None");

		return $substitutionarray;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Load all stock movements from the database
	 *
	 *  @return	array								Return an array of stock movements
	 */
	public function get_stock_movement_lines()
	{
        // phpcs:enable
		global $conf, $hookmanager;

		$idproduct = GETPOST('idproduct', 'int');
		$year = GETPOST("year");
		$month = GETPOST("month");
		$search_ref = GETPOST('search_ref', 'alpha');
		$search_movement = GETPOST("search_movement");
		$search_product_ref = trim(GETPOST("search_product_ref"));
		$search_product = trim(GETPOST("search_product"));
		$search_warehouse = trim(GETPOST("search_warehouse"));
		$search_inventorycode = trim(GETPOST("search_inventorycode"));
		$search_user = trim(GETPOST("search_user"));
		$search_batch = trim(GETPOST("search_batch"));
		$search_qty = trim(GETPOST("search_qty"));
		$search_type_mouvement = GETPOST('search_type_mouvement', 'int');

		$limit = GETPOST('limit', 'int') ?GETPOST('limit', 'int') : $conf->liste_limit;
		$page = GETPOSTISSET('pageplusone') ? (GETPOST('pageplusone') - 1) : GETPOST("page", 'int');
		$sortfield = GETPOST("sortfield", 'alpha');
		$sortorder = GETPOST("sortorder", 'alpha');
		if (empty($page) || $page == -1) { $page = 0; }     // If $page is not defined, or '' or -1
		$offset = $limit * $page;
		if (!$sortfield) $sortfield = "m.datem";
		if (!$sortorder) $sortorder = "DESC";

		$pdluoid = GETPOST('pdluoid', 'int');

		// Initialize technical object to manage hooks of page. Note that conf->hooks_modules contains array of hook context
		$hookmanager->initHooks(array('movementlist'));
		$extrafields = new ExtraFields($this->db);

		// fetch optionals attributes and labels
		$extrafields->fetch_name_optionals_label('movement');
		$search_array_options = $extrafields->getOptionalsFromPost('movement', '', 'search_');

		$productlot = new ProductLot($this->db);
		$productstatic = new Product($this->db);
		$warehousestatic = new Entrepot($this->db);
		$movement = new MouvementStock($this->db);
		$userstatic = new User($this->db);
		$element = 'movement';

		$warehousestatic->fetch($id);

		$sql = "SELECT p.rowid, p.ref as product_ref, p.label as produit, p.tobatch, p.fk_product_type as type, p.entity,";
		$sql .= " e.ref as warehouse_ref, e.rowid as entrepot_id, e.lieu,";
		$sql .= " m.rowid as mid, m.value as qty, m.datem, m.fk_user_author, m.label, m.inventorycode, m.fk_origin, m.origintype,";
		$sql .= " m.batch, m.price,";
		$sql .= " m.type_mouvement,";
		$sql .= " pl.rowid as lotid, pl.eatby, pl.sellby,";
		$sql .= " u.login, u.photo, u.lastname, u.firstname";
		// Add fields from extrafields
		if (!empty($extrafields->attributes[$element]['label'])) {
			foreach ($extrafields->attributes[$element]['label'] as $key => $val) $sql .= ($extrafields->attributes[$element]['type'][$key] != 'separate' ? ", ef.".$key.' as options_'.$key : '');
		}
		// Add fields from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListSelect', $parameters); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		$sql .= " FROM ".MAIN_DB_PREFIX."entrepot as e,";
		$sql .= " ".MAIN_DB_PREFIX."product as p,";
		$sql .= " ".MAIN_DB_PREFIX."stock_mouvement as m";
		if (is_array($extrafields->attributes[$warehousestatic->table_element]['label']) && count($extrafields->attributes[$warehousestatic->table_element]['label'])) $sql .= " LEFT JOIN ".MAIN_DB_PREFIX.$warehousestatic->table_element."_extrafields as ef on (m.rowid = ef.fk_object)";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."user as u ON m.fk_user_author = u.rowid";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product_lot as pl ON m.batch = pl.batch AND m.fk_product = pl.fk_product";
		$sql .= " WHERE m.fk_product = p.rowid";
		if ($msid > 0) $sql .= " AND m.rowid = ".$msid;
		$sql .= " AND m.fk_entrepot = e.rowid";
		$sql .= " AND e.entity IN (".getEntity('stock').")";
		if (empty($conf->global->STOCK_SUPPORTS_SERVICES)) $sql .= " AND p.fk_product_type = 0";
		if ($id > 0) $sql .= " AND e.rowid ='".$id."'";
		if ($month > 0)
		{
			if ($year > 0)
				$sql .= " AND m.datem BETWEEN '".$this->db->idate(dol_get_first_day($year, $month, false))."' AND '".$this->db->idate(dol_get_last_day($year, $month, false))."'";
			else
				$sql .= " AND date_format(m.datem, '%m') = '$month'";
		}
		elseif ($year > 0)
		{
			$sql .= " AND m.datem BETWEEN '".$this->db->idate(dol_get_first_day($year, 1, false))."' AND '".$this->db->idate(dol_get_last_day($year, 12, false))."'";
		}
		if ($idproduct > 0) $sql .= " AND p.rowid = '".$idproduct."'";
		if (!empty($search_ref))			$sql .= natural_search('m.rowid', $search_ref, 1);
		if (!empty($search_movement))		$sql .= natural_search('m.label', $search_movement);
		if (!empty($search_inventorycode))	$sql .= natural_search('m.inventorycode', $search_inventorycode);
		if (!empty($search_product_ref))	$sql .= natural_search('p.ref', $search_product_ref);
		if (!empty($search_product))		$sql .= natural_search('p.label', $search_product);
		if ($search_warehouse > 0)			$sql .= " AND e.rowid = '".$this->db->escape($search_warehouse)."'";
		if (!empty($search_user))			$sql .= natural_search('u.login', $search_user);
		if (!empty($search_batch))			$sql .= natural_search('m.batch', $search_batch);
		if ($search_qty != '')				$sql .= natural_search('m.value', $search_qty, 1);
		if ($search_type_mouvement >= 0)	$sql .= " AND m.type_mouvement = '".$this->db->escape($search_type_mouvement)."'";
		// Add where from extra fields
		include DOL_DOCUMENT_ROOT.'/core/tpl/extrafields_list_search_sql.tpl.php';
		// Add where from hooks
		$parameters = array();
		$reshook = $hookmanager->executeHooks('printFieldListWhere', $parameters); // Note that $action and $object may have been modified by hook
		$sql .= $hookmanager->resPrint;
		$sql .= $this->db->order($sortfield, $sortorder);

		$nbtotalofrecords = '';
		if (empty($conf->global->MAIN_DISABLE_FULL_SCANLIST))
		{
			$result = $this->db->query($sql);
			$nbtotalofrecords = $this->db->num_rows($result);
			if (($page * $limit) > $nbtotalofrecords)	// if total resultset is smaller then paging size (filtering), goto and load page 0
			{
				$page = 0;
				$offset = 0;
			}
		}

		if (empty($search_inventorycode)) $sql .= $this->db->plimit($limit + 1, $offset);

		$resql = $this->db->query($sql);
		$nbtotalofrecords = $this->db->num_rows($result);

		$result = [];
		for ($i=0; $i<$nbtotalofrecords; $i++) {
			array_push($result, $this->db->fetch_object($resql));
		}
		return $result;
	}

    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  Object			$line				Object line
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @param  int				$linenumber			The number of the line for the substitution of "object_line_pos"
	 *  @return	array								Return a substitution array
	 */
	public function get_substitutionarray_lines($line, $outputlangs, $linenumber = 0)
	{
        // phpcs:enable
		global $conf;

		$resarray = array(
			"line_id" => $line->rowid,
			"line_product_ref" => $line->product_ref,
			"line_qty" => abs(intval($line->qty)),
			"line_date" => dol_print_date($line->datem, 'day'),
			"line_label" => $line->label,
			"line_inventory_code" => $line->inventorycode,
			"line_price" => price2num($line->price),
			"line_author" => trim($line->firstname . ' ' . $line->lastname)
		);

		return $resarray;
	}
}
