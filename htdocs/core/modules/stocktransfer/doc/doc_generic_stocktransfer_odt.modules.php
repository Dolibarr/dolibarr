<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018-2021  Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2018-2026  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
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
* along with this program. If not, see <https://www.gnu.org/licenses/>.
* or see https://www.gnu.org/
*/

/**
 *	\file       htdocs/core/modules/stocktransfer/doc/doc_generic_stocktransfer_odt.modules.php
 *	\ingroup    stock
 *	\brief      File of class to build ODT documents for stock transfers
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/stocktransfer/modules_stocktransfer.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/stocktransfer/class/stocktransfer.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/stocktransfer/class/stocktransferline.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/product/stock/class/entrepot.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class doc_generic_stocktransfer_odt extends ModelePDFStockTransfer
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * Product and warehouses of each line, keyed by line id. They are read by fetchLinesExtra()
	 * and are not fields of StockTransferLine, so they are kept here rather than set on the line
	 * objects.
	 *
	 * @var array<int,array{product_ref:string,product_label:string,warehouse_source:string,warehouse_destination:string}>
	 */
	private $linesextra = array();


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $conf, $langs, $mysoc;

		// Load translation files required by the page
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template
		$this->scandir = 'STOCKTRANSFER_ADDON_PDF_ODT_PATH'; // Name of constant that is used to save list of directories to scan

		// Page size for A4 format
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute = 0;
		$this->marge_basse = 0;

		$this->option_logo = 1; // Display logo
		$this->option_tva = 0; // Manage the vat option EXPEDITION_TVAOPTION
		$this->option_modereg = 0; // Display payment mode
		$this->option_condreg = 0; // Display payment terms
		$this->option_multilang = 1; // Available in several languages
		$this->option_escompte = 0; // Displays if there has been a discount
		$this->option_credit_note = 0; // Support credit notes
		$this->option_freetext = 1; // Support add of a personalised text
		$this->option_draft_watermark = 0; // Support add of a watermark on drafts

		if ($mysoc === null) {
			dol_syslog(get_class($this).'::__construct() Global $mysoc should not be null.'. getCallerInfoString(), LOG_ERR);
			return;
		}

		// Get source company
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default if not defined
		}
	}


	/**
	 *	Return description of a module
	 *
	 *	@param	Translate	$langs      Lang object to use for output
	 *	@return string       			Description
	 */
	public function info($langs)
	{
		global $conf, $langs;

		// Load translation files required by the page
		$langs->loadLangs(array("errors", "companies"));

		$form = new Form($this->db);

		$odtPath = trim(getDolGlobalString('STOCKTRANSFER_ADDON_PDF_ODT_PATH'));

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="STOCKTRANSFER_ADDON_PDF_ODT_PATH">';
		$texte .= '<table class="nobordernopadding centpercent">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', $odtPath));
		$listoffiles = array();
		foreach ($listofdir as $key => $tmpdir) {
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if (!$tmpdir) {
				unset($listofdir[$key]);
				continue;
			}
			if (!is_dir($tmpdir)) {
				$texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), '');
			} else {
				$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)');
				if (count($tmpfiles)) {
					$listoffiles = array_merge($listoffiles, $tmpfiles);
				}
			}
		}
		$texthelp = $langs->trans("ListOfDirectoriesForModelGenODT");
		$texthelp .= '<br><br><span class="opacitymedium">'.$langs->trans("ExampleOfDirectoriesForModelGen").'</span>';
		// Add list of substitution keys
		$texthelp .= '<br>'.$langs->trans("FollowingSubstitutionKeysCanBeUsed").'<br>';
		$texthelp .= $langs->transnoentitiesnoconv("FullListOnOnlineDocumentation"); // This contains an url, we don't modify it

		// Scan directories
		$nbofiles = count($listoffiles);
		if (!empty($odtPath)) {
			$texte .= '<span class="opacitylow">'.$langs->trans("NumberOfModelFilesFound").': <b>';
			//$texte.=$nbofiles?'<a id="a_'.get_class($this).'" href="#">':'';
			$texte .= count($listoffiles);
			//$texte.=$nbofiles?'</a>':'';
			$texte .= '</b></span><br>';
		}
		if ($nbofiles) {
			$texte .= '<div id="div_'.get_class($this).'" class="hiddenx opacitylow">';
			// Show list of found files
			foreach ($listoffiles as $file) {
				$texte .= '- '.$file['name'];
				$texte .= ' <a href="'.DOL_URL_ROOT.'/document.php?modulepart=doctemplates&file=stocktransfers/'.urlencode(basename($file['name'])).'">'.img_picto('', 'listlight').'</a>';
				$texte .= ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?modulepart=doctemplates&keyforuploaddir=STOCKTRANSFER_ADDON_PDF_ODT_PATH&action=deletefile&token='.newToken().'&file='.urlencode(basename($file['name'])).'">'.img_picto('', 'delete').'</a>';
				$texte .= '<br>';
			}
			$texte .= '</div>';
		}

		$texte .= '<br>';
		// Link to show/hide the area to manage directories of ODT models and template files
		$texte .= '<span class="classlink cursorpointer" onclick="javascript: jQuery(\'.div_odtmodelsof'.get_class($this).'\').toggle();">'.img_picto('', 'add').' '.$langs->trans("UploadNewTemplate").'...</span>';
		$texte .= '<div class="div_odtmodelsof'.get_class($this).'" style="display: none; margin-top: 6px;">';
		$texte .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1, 3, $this->name);
		$texte .= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$texte .= '<textarea class="flat textareafordir" spellcheck="false" cols="60" name="value1">';
		$texte .= $odtPath;
		$texte .= '</textarea>';
		$texte .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$texte .= '<input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="'.dol_escape_htmltag($langs->trans("Modify")).'">';
		$texte .= '<br></div></div>';

		// Add input to upload a new template file.
		$texte .= '<div>';
		$maxfilesizearray = getMaxFileSizeArray();
		$maxmin = $maxfilesizearray['maxmin'];
		if ($maxmin > 0) {
			$texte .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
		}
		$texte .= ' <input type="file" name="uploadfile">';
		$texte .= '<input type="hidden" value="STOCKTRANSFER_ADDON_PDF_ODT_PATH" name="keyforuploaddir">';
		$texte .= '<input type="submit" class="button smallpaddingimp reposition" value="'.dol_escape_htmltag($langs->trans("Upload")).'" name="upload">';
		$texte .= '</div>';
		$texte .= '</div>';
		$texte .= '</td>';

		$texte .= '</tr>';

		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build a document on disk using the generic odt module.
	 *
	 *	@param		StockTransfer	$object			Object source to build document
	 *  @param		Translate	$outputlangs		Lang output object
	 *  @param		string		$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int<0,1>	$hidedetails		Do not show line details
	 *  @param		int<0,1>	$hidedesc			Do not show desc
	 *  @param		int<0,1>	$hideref			Do not show ref
	 *  @return		int<-1,1>						1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_generic_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		// Add odtgeneration hook
		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(array('odtgeneration'));
		global $action;

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$sav_charset_output = $outputlangs->charset_output;
		$outputlangs->charset_output = 'UTF-8';

		// Load translation files required by page
		$outputlangs->loadLangs(array("main", "dict", "companies", "bills"));

		if (!empty($conf->stocktransfer->dir_output)) {
			// If $object is id instead of object
			if (!is_object($object)) {
				$id = $object;
				$object = new StockTransfer($this->db);
				$result = $object->fetch($id);
				if ($result < 0) {
					dol_print_error($this->db, $object->error);
					return -1;
				}
			}


			$dir = $conf->stocktransfer->dir_output.'/'.$object->element;
			$objectref = dol_sanitizeFileName($object->ref);
			if (!preg_match('/specimen/i', $objectref)) {
				$dir .= "/".$objectref;
			}
			$file = $dir."/".$objectref.".odt";

			if (!file_exists($dir)) {
				if (dol_mkdir($dir) < 0) {
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return -1;
				}
			}

			if (file_exists($dir)) {
				//print "srctemplatepath=".$srctemplatepath;	// Src filename
				$newfile = basename($srctemplatepath);
				$newfiletmp = preg_replace('/\.od[ts]/i', '', $newfile);
				$newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
				$newfiletmp = preg_replace('/modele_/i', '', $newfiletmp);
				$newfiletmp = $objectref . '_' . $newfiletmp;
				//$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now(),'%Y%m%d%H%M%S').'.odt';
				// Get extension (ods or odt)
				$newfileformat = substr($newfile, strrpos($newfile, '.') + 1);
				if (getDolGlobalString('MAIN_DOC_USE_TIMING')) {
					$format = getDolGlobalString('MAIN_DOC_USE_TIMING');
					if ($format == '1') {
						$format = '%Y%m%d%H%M%S';
					}
					$filename = $newfiletmp . '-' . dol_print_date(dol_now(), $format) . '.' . $newfileformat;
				} else {
					$filename = $newfiletmp . '.' . $newfileformat;
				}
				$file = $dir . '/' . $filename;
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;

				dol_mkdir($conf->stocktransfer->dir_temp);
				if (!is_writable($conf->stocktransfer->dir_temp)) {
					$this->error = $langs->transnoentities("ErrorFailedToWriteInTempDirectory", $conf->stocktransfer->dir_temp);
					dol_syslog('Error in write_file: ' . $this->error, LOG_ERR);
					return -1;
				}

				// Make substitution
				$substitutionarray = array(
					'__FROM_NAME__' => $this->emetteur->name,
					'__FROM_EMAIL__' => $this->emetteur->email,
					'__TOTAL_TTC__' => $object->total_ttc,
					'__TOTAL_HT__' => $object->total_ht,
					'__TOTAL_VAT__' => $object->total_tva
				);
				complete_substitutions_array($substitutionarray, $langs, $object);
				// Call the ODTSubstitution hook
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$substitutionarray);
				$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				// Line of free text
				$newfreetext = '';
				$paramfreetext = 'STOCKTRANSFER_FREE_TEXT';
				if (getDolGlobalString($paramfreetext)) {
					$newfreetext = make_substitutions(getDolGlobalString($paramfreetext), $substitutionarray);
				}

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				try {
					$odfHandler = new Odf(
						$srctemplatepath,
						array(
							'PATH_TO_TMP'	  => $conf->stocktransfer->dir_temp,
							'ZIP_PROXY'		  => getDolGlobalString('MAIN_ODF_ZIP_PROXY', 'PclZipProxy'), // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
							'DELIMITER_LEFT'  => '{',
							'DELIMITER_RIGHT' => '}'
						)
					);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
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
				} catch (OdfException $e) {
					dol_syslog($e->getMessage(), LOG_INFO);
				}

				// retrieve the constant to apply a ratio for image size or set the ratio to 1
				if (getDolGlobalString('MAIN_DOC_ODT_IMAGE_RATIO')) {
					$ratio = (float) getDolGlobalString('MAIN_DOC_ODT_IMAGE_RATIO');
				} else {
					$ratio = 1;
				}

				// Make substitutions into odt of user info
				$tmparray = $this->get_substitutionarray_user($user, $outputlangs);
				foreach ($tmparray as $key => $value) {
					try {
						if (preg_match('/logo$/', $key)) { // Image
							//var_dump($value);exit;
							if (file_exists($value)) {
								$odfHandler->setImage($key, $value, $ratio);
							} else {
								$odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
							}
						} else { // Text
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					} catch (OdfException $e) {
						dol_syslog($e->getMessage(), LOG_INFO);
					}
				}
				// Make substitutions into odt of mysoc
				$tmparray = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
				foreach ($tmparray as $key => $value) {
					try {
						if (preg_match('/logo$/', $key)) {	// Image
							//var_dump($value);exit;
							if (file_exists($value)) {
								$odfHandler->setImage($key, $value, $ratio);
							} else {
								$odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
							}
						} else { // Text
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					} catch (OdfException $e) {
						dol_syslog($e->getMessage(), LOG_INFO);
					}
				}
				// Make substitutions into odt of the thirdparty, when there is one. A stock transfer
				// between two warehouses of the company has no fk_soc, and the substitution array of
				// a thirdparty reads properties that a non-fetched one does not have.
				$object->fetch_thirdparty();
				if ($object->thirdparty instanceof Societe && $object->thirdparty->id > 0) {
					$tmparray = array_merge($tmparray, $this->get_substitutionarray_thirdparty($object->thirdparty, $outputlangs));
				}

				// Replace tags of object + external modules
				$tmparray = array_merge($tmparray, $this->get_substitutionarray_object($object, $outputlangs));
				$tmparray = array_merge($tmparray, $this->get_substitutionarray_other($outputlangs));

				complete_substitutions_array($tmparray, $outputlangs, $object);
				// Call the ODTSubstitution hook
				$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
				$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				foreach ($tmparray as $key => $value) {
					try {
						if (preg_match('/logo$/', $key)) {
							// Image
							if (file_exists($value)) {
								$odfHandler->setImage($key, $value, $ratio);
							} else {
								$odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
							}
						} else {
							// Text
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					} catch (OdfException $e) {
						dol_syslog($e->getMessage(), LOG_INFO);
					}
				}

				// StockTransfer::fetchLines() fills $object->lines, but a line only carries foreign
				// keys: the product and the two warehouses are read here, in one query for the whole
				// document rather than one per line.
				$object->fetchLines();
				$this->fetchLinesExtra($object);

				// Replace tags of lines
				$foundtagforlines = 1;
				try {
					$listlines = $odfHandler->setSegment('lines');
				} catch (OdfExceptionSegmentNotFound $e) {
					// We may arrive here if tags for lines not present into template
					$foundtagforlines = 0;
					dol_syslog($e->getMessage(), LOG_INFO);
				}
				if ($foundtagforlines) {
					$linenumber = 0;
					foreach ($object->lines as $line) {
						$linenumber++;
						$tmparray = $this->get_substitutionarray_lines($line, $outputlangs, $linenumber);
						complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");
						// Call the ODTSubstitutionLine hook
						$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line);
						$reshook = $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
						foreach ($tmparray as $key => $val) {
							try {
								$listlines->setVars($key, $val, true, 'UTF-8');
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							}
						}
						$listlines->merge();
					}
					try {
						$odfHandler->mergeSegment($listlines);
					} catch (OdfException $e) {
						$this->error = $e->getMessage();
						dol_syslog($this->error, LOG_WARNING);
						return -1;
					}
				}

				// Replace labels translated
				$tmparray = $outputlangs->get_translations_for_substitutions();
				foreach ($tmparray as $key => $value) {
					try {
						$odfHandler->setVars($key, $value, true, 'UTF-8');
					} catch (OdfException $e) {
						dol_syslog($e->getMessage(), LOG_INFO);
					}
				}

				// Call the beforeODTSave hook
				$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
				$reshook = $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				// Write new file
				if (getDolGlobalString('MAIN_ODT_AS_PDF')) {
					try {
						$odfHandler->exportAsAttachedPDF($file);
					} catch (Exception $e) {
						$this->error = $e->getMessage();
						dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				} else {
					try {
						$odfHandler->saveToDisk($file);
					} catch (Exception $e) {
						$this->error = $e->getMessage();
						dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				}

				$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
				$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				dolChmod($file);

				$odfHandler = null; // Destroy object

				$this->result = array('fullpath' => $file);

				return 1; // Success
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			}
		}

		return -1;
	}
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return the substitution array of a stock transfer.
	 *
	 * The parent reads $object->date, which a StockTransfer does not carry: what it has is four
	 * dates, the planned and the real one of the departure and of the arrival, so object_date
	 * would always come out empty. It is filled with the planned date of departure, falling back
	 * on the date the transfer was created. The two warehouses and the status are named rather
	 * than left as foreign keys, since a transfer is read by where the goods leave and arrive.
	 *
	 * @param	CommonObject					$object			Stock transfer
	 * @param	Translate						$outputlangs	Lang object to use for output
	 * @param	string							$array_key		Prefix of the keys
	 * @return	array<string,mixed>							Array of substitutions
	 */
	public function get_substitutionarray_object($object, $outputlangs, $array_key = 'object')
	{
		// phpcs:enable
		$resarray = parent::get_substitutionarray_object($object, $outputlangs, $array_key);

		if (!$object instanceof StockTransfer) {
			return $resarray;
		}

		$date = !empty($object->date_prevue_depart) ? $object->date_prevue_depart : $object->date_creation;

		$resarray[$array_key.'_date'] = dol_print_date($date, 'day', false, $outputlangs);
		$resarray[$array_key.'_date_rfc'] = dol_print_date($date, 'dayrfc');
		$resarray[$array_key.'_date_planned_departure'] = dol_print_date($object->date_prevue_depart, 'day', false, $outputlangs);
		$resarray[$array_key.'_date_real_departure'] = dol_print_date($object->date_reelle_depart, 'day', false, $outputlangs);
		$resarray[$array_key.'_date_planned_arrival'] = dol_print_date($object->date_prevue_arrivee, 'day', false, $outputlangs);
		$resarray[$array_key.'_date_real_arrival'] = dol_print_date($object->date_reelle_arrivee, 'day', false, $outputlangs);
		$resarray[$array_key.'_label'] = (string) $object->label;
		$resarray[$array_key.'_description'] = (string) $object->description;
		$resarray[$array_key.'_status'] = $object->getLibStatut(1);

		$resarray[$array_key.'_warehouse_source'] = $this->getWarehouseRef($object->fk_warehouse_source);
		$resarray[$array_key.'_warehouse_destination'] = $this->getWarehouseRef($object->fk_warehouse_destination);

		return $resarray;
	}

	/**
	 * Return the ref of a warehouse, or an empty string when there is none.
	 *
	 * @param	int|string|null		$id		Id of the warehouse
	 * @return	string						Ref of the warehouse
	 */
	private function getWarehouseRef($id)
	{
		if (empty($id)) {
			return '';
		}

		$tmpwarehouse = new Entrepot($this->db);
		if ($tmpwarehouse->fetch((int) $id) > 0) {
			return (string) $tmpwarehouse->ref;
		}

		return '';
	}

	/**
	 * Read the product and the two warehouses of every line of the transfer, in one query.
	 *
	 * A StockTransferLine carries fk_product, fk_warehouse_source and fk_warehouse_destination
	 * and nothing readable: fetching each of them line by line would mean three queries per line
	 * of the document. They are read here into $linesextra, keyed by line id.
	 *
	 * @param	StockTransfer	$object		Stock transfer, with its lines already fetched
	 * @return	void
	 */
	private function fetchLinesExtra($object)
	{
		$this->linesextra = array();

		if (empty($object->lines)) {
			return;
		}

		$sql = "SELECT stl.rowid, p.ref as product_ref, p.label as product_label,";
		$sql .= " es.ref as warehouse_source, ed.ref as warehouse_destination";
		$sql .= " FROM ".MAIN_DB_PREFIX."stocktransfer_stocktransferline as stl";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."product as p ON p.rowid = stl.fk_product";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as es ON es.rowid = stl.fk_warehouse_source";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."entrepot as ed ON ed.rowid = stl.fk_warehouse_destination";
		$sql .= " WHERE stl.fk_stocktransfer = ".((int) $object->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this)."::fetchLinesExtra ".$this->db->lasterror(), LOG_ERR);
			return;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$this->linesextra[(int) $obj->rowid] = array(
				'product_ref' => (string) $obj->product_ref,
				'product_label' => (string) $obj->product_label,
				'warehouse_source' => (string) $obj->warehouse_source,
				'warehouse_destination' => (string) $obj->warehouse_destination,
			);
		}
		$this->db->free($resql);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return the substitution array of a line of a stock transfer.
	 *
	 * A line of a stock transfer has no description and no price for the parent to read: what it
	 * holds is a product, a quantity, optionally a lot, and the two warehouses the goods move
	 * between, which may differ from the ones of the transfer itself. Those are what the document
	 * is written with.
	 *
	 * @param	CommonObjectLine				$line			Line of the transfer
	 * @param	Translate						$outputlangs	Lang object to use for output
	 * @param	int								$linenumber		Rank of the line
	 * @return	array<string,mixed>							Array of substitutions
	 */
	public function get_substitutionarray_lines($line, $outputlangs, $linenumber = 0)
	{
		// phpcs:enable
		$resarray = parent::get_substitutionarray_lines($line, $outputlangs, $linenumber);

		if (!$line instanceof StockTransferLine) {
			return $resarray;
		}

		$extra = isset($this->linesextra[(int) $line->id]) ? $this->linesextra[(int) $line->id] : array('product_ref' => '', 'product_label' => '', 'warehouse_source' => '', 'warehouse_destination' => '');
		$productref = $extra['product_ref'];
		$productlabel = $extra['product_label'];

		$resarray['line_product_ref'] = $productref;
		$resarray['line_product_label'] = $productlabel;
		$resarray['line_fulldesc'] = trim($productref.' '.$productlabel);
		$resarray['line_warehouse_source'] = $extra['warehouse_source'];
		$resarray['line_warehouse_destination'] = $extra['warehouse_destination'];
		$resarray['line_batch'] = isset($line->batch) ? (string) $line->batch : '';
		$resarray['line_qty'] = $line->qty;
		$resarray['line_pmp'] = price2num($line->pmp);

		return $resarray;
	}
}
