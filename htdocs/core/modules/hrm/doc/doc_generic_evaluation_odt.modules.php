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
 *	\file       htdocs/core/modules/hrm/doc/doc_generic_evaluation_odt.modules.php
 *	\ingroup    hrm
 *	\brief      File of class to build ODT documents for evaluations
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/hrm/modules_evaluation.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/evaluation.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/evaluationdet.class.php';
require_once DOL_DOCUMENT_ROOT.'/hrm/class/job.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class doc_generic_evaluation_odt extends ModelePDFEvaluation
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';

	/**
	 * Skill of each line, keyed by line id. It is read by fetchLinesExtra() and is not a field
	 * of EvaluationLine, which only carries fk_skill, so it is kept here rather than set on the
	 * line objects.
	 *
	 * @var array<int,array{skill_label:string,skill_description:string}>
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
		$langs->loadLangs(array("main", "companies", "hrm"));

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->update_main_doc_field = 1; // Save the name of generated file as the main doc when generating a doc with this template
		$this->scandir = 'EVALUATION_ADDON_PDF_ODT_PATH'; // Name of constant that is used to save list of directories to scan

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

		$odtPath = trim(getDolGlobalString('EVALUATION_ADDON_PDF_ODT_PATH'));

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="EVALUATION_ADDON_PDF_ODT_PATH">';
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
				$texte .= ' <a href="'.DOL_URL_ROOT.'/document.php?modulepart=doctemplates&file=evaluations/'.urlencode(basename($file['name'])).'">'.img_picto('', 'listlight').'</a>';
				$texte .= ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?modulepart=doctemplates&keyforuploaddir=EVALUATION_ADDON_PDF_ODT_PATH&action=deletefile&token='.newToken().'&file='.urlencode(basename($file['name'])).'">'.img_picto('', 'delete').'</a>';
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
		$texte .= '<input type="hidden" value="EVALUATION_ADDON_PDF_ODT_PATH" name="keyforuploaddir">';
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
	 *	@param		Evaluation	$object				Object source to build document
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
		$outputlangs->loadLangs(array("main", "dict", "companies", "hrm"));

		if (!empty($conf->hrm->dir_output)) {
			// If $object is id instead of object
			if (!is_object($object)) {
				$id = $object;
				$object = new Evaluation($this->db);
				$result = $object->fetch($id);
				if ($result < 0) {
					dol_print_error($this->db, $object->error);
					return -1;
				}
			}


			$dir = $conf->hrm->dir_output.'/'.$object->element;
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

				dol_mkdir($conf->hrm->dir_temp);
				if (!is_writable($conf->hrm->dir_temp)) {
					$this->error = $langs->transnoentities("ErrorFailedToWriteInTempDirectory", $conf->hrm->dir_temp);
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
				$paramfreetext = 'EVALUATION_FREE_TEXT';
				if (getDolGlobalString($paramfreetext)) {
					$newfreetext = make_substitutions(getDolGlobalString($paramfreetext), $substitutionarray);
				}

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				try {
					$odfHandler = new Odf(
						$srctemplatepath,
						array(
							'PATH_TO_TMP'	  => $conf->hrm->dir_temp,
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

				// Evaluation::fetchLines() fills $object->lines, but a line only carries fk_skill:
				// the skill it is about is read here, in one query for the whole document rather
				// than one per line.
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
	 * Return the substitution array of an evaluation.
	 *
	 * The parent reads $object->date, which an Evaluation does not carry: what it has is
	 * date_eval, the date the interview took place, so object_date would always come out empty.
	 * It is filled with that date, falling back on the date the evaluation was created. The
	 * employee it is about and the job it is held against are named rather than left as foreign
	 * keys, since a review is unreadable without them.
	 *
	 * @param	CommonObject					$object			Evaluation
	 * @param	Translate						$outputlangs	Lang object to use for output
	 * @param	string							$array_key		Prefix of the keys
	 * @return	array<string,mixed>							Array of substitutions
	 */
	public function get_substitutionarray_object($object, $outputlangs, $array_key = 'object')
	{
		// phpcs:enable
		$resarray = parent::get_substitutionarray_object($object, $outputlangs, $array_key);

		if (!$object instanceof Evaluation) {
			return $resarray;
		}

		$date = !empty($object->date_eval) ? $object->date_eval : $object->date_creation;

		$resarray[$array_key.'_date'] = dol_print_date($date, 'day', false, $outputlangs);
		$resarray[$array_key.'_date_rfc'] = dol_print_date($date, 'dayrfc');
		$resarray[$array_key.'_date_eval'] = dol_print_date($object->date_eval, 'day', false, $outputlangs);
		$resarray[$array_key.'_label'] = (string) $object->label;
		$resarray[$array_key.'_description'] = (string) $object->description;
		$resarray[$array_key.'_status'] = $object->getLibStatut(1);

		$employee = '';
		if ($object->fk_user > 0) {
			$tmpuser = new User($this->db);
			if ($tmpuser->fetch($object->fk_user) > 0) {
				$employee = $tmpuser->getFullName($outputlangs);
			}
		}
		$resarray[$array_key.'_employee'] = $employee;

		$job = '';
		if ($object->fk_job > 0) {
			$tmpjob = new Job($this->db);
			if ($tmpjob->fetch($object->fk_job) > 0) {
				$job = (string) $tmpjob->label;
			}
		}
		$resarray[$array_key.'_job'] = $job;

		return $resarray;
	}

	/**
	 * Read the skill of every line of the evaluation, in one query.
	 *
	 * An EvaluationLine carries fk_skill and the two ranks and nothing readable: fetching the
	 * skill line by line would mean one query per line of the document. They are read here into
	 * $linesextra, keyed by line id.
	 *
	 * @param	Evaluation	$object		Evaluation, with its lines already fetched
	 * @return	void
	 */
	private function fetchLinesExtra($object)
	{
		$this->linesextra = array();

		if (empty($object->lines)) {
			return;
		}

		$sql = "SELECT ed.rowid, s.label as skill_label, s.description as skill_description";
		$sql .= " FROM ".MAIN_DB_PREFIX."hrm_evaluationdet as ed";
		$sql .= " LEFT JOIN ".MAIN_DB_PREFIX."hrm_skill as s ON s.rowid = ed.fk_skill";
		$sql .= " WHERE ed.fk_evaluation = ".((int) $object->id);

		$resql = $this->db->query($sql);
		if (!$resql) {
			dol_syslog(get_class($this)."::fetchLinesExtra ".$this->db->lasterror(), LOG_ERR);
			return;
		}
		while ($obj = $this->db->fetch_object($resql)) {
			$this->linesextra[(int) $obj->rowid] = array(
				'skill_label' => (string) $obj->skill_label,
				'skill_description' => (string) $obj->skill_description,
			);
		}
		$this->db->free($resql);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return the substitution array of a line of an evaluation.
	 *
	 * A line of an evaluation has nothing the parent reads: no description, no quantity, no
	 * price. What it holds is a skill, the rank the job requires for it and the rank the employee
	 * was given, plus the difference between the two, which is what the review is read for.
	 *
	 * @param	CommonObjectLine				$line			Line of the evaluation
	 * @param	Translate						$outputlangs	Lang object to use for output
	 * @param	int								$linenumber		Rank of the line
	 * @return	array<string,mixed>							Array of substitutions
	 */
	public function get_substitutionarray_lines($line, $outputlangs, $linenumber = 0)
	{
		// phpcs:enable
		$resarray = parent::get_substitutionarray_lines($line, $outputlangs, $linenumber);

		if (!$line instanceof EvaluationLine) {
			return $resarray;
		}

		$extra = isset($this->linesextra[(int) $line->id]) ? $this->linesextra[(int) $line->id] : array('skill_label' => '', 'skill_description' => '');

		$resarray['line_skill'] = $extra['skill_label'];
		$resarray['line_skill_description'] = $extra['skill_description'];
		$resarray['line_fulldesc'] = $extra['skill_label'];
		$resarray['line_rank_required'] = (int) $line->required_rank;
		$resarray['line_rank_obtained'] = (int) $line->rankorder;
		$resarray['line_rank_delta'] = (int) $line->rankorder - (int) $line->required_rank;

		return $resarray;
	}
}
