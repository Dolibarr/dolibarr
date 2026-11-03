<?php
/* Copyright (C) 2010-2012	Laurent Destailleur		<eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent			<jmenent@2byte.es>
 * Copyright (C) 2026		Frédéric France			<frederic.france@free.fr>
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
 *	\file       htdocs/core/modules/holiday/doc/doc_generic_holiday_odt.modules.php
 *	\ingroup    holiday
 *	\brief      File of class to build ODT documents for leave requests
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/holiday/modules_holiday.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT.'/holiday/class/holiday.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';


/**
 *	Class to build documents of leave requests using ODF templates generator
 */
class doc_generic_holiday_odt extends ModelePDFHoliday
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string Version, possible values are: 'development', 'experimental', 'dolibarr', 'dolibarr_deprecated' or a version string like 'x.y.z'''|'development'|'dolibarr'|'experimental'
	 */
	public $version = 'dolibarr';


	/**
	 *	Constructor
	 *
	 *  @param		DoliDB		$db      Database handler
	 */
	public function __construct($db)
	{
		global $langs, $mysoc;

		// Load translation files required by the page
		$langs->loadLangs(array("main", "companies", "holiday"));

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'HOLIDAY_ADDON_PDF_ODT_PATH'; // Name of constant that is used to save list of directories to scan

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
		$this->option_tva = 0; // Manage the vat option
		$this->option_multilang = 1; // Available in several languages
		$this->option_freetext = 0; // Support add of a personalised text
		$this->option_draft_watermark = 0; // Support add of a watermark on drafts

		if ($mysoc === null) {
			dol_syslog(get_class($this).'::__construct() Global $mysoc should not be null.'.getCallerInfoString(), LOG_ERR);
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
		global $langs;

		// Load translation files required by the page
		$langs->loadLangs(array('companies', 'errors'));

		$form = new Form($this->db);

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="HOLIDAY_ADDON_PDF_ODT_PATH">';

		$texte .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim(getDolGlobalString('HOLIDAY_ADDON_PDF_ODT_PATH'))));
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
		if (count($listofdir)) {
			$texte .= '<span class="opacitylow">'.$langs->trans("NumberOfModelFilesFound").': <b>'.count($listoffiles).'</b></span><br>';

			$texte .= '<div id="div_'.get_class($this).'" class="hiddenx opacitylow">';
			// Show list of found files
			foreach ($listoffiles as $file) {
				$texte .= '- '.$file['name'].' <a href="'.DOL_URL_ROOT.'/document.php?modulepart=doctemplates&file=holiday/'.urlencode(basename($file['name'])).'">'.img_picto('', 'listlight').'</a>';
				$texte .= ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?modulepart=doctemplates&keyforuploaddir=HOLIDAY_ADDON_PDF_ODT_PATH&action=deletefile&token='.newToken().'&file='.urlencode(basename($file['name'])).'">'.img_picto('', 'delete').'</a>';
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
		$texte .= getDolGlobalString('HOLIDAY_ADDON_PDF_ODT_PATH');
		$texte .= '</textarea>';
		$texte .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$texte .= '<input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="'.dol_escape_htmltag($langs->trans("Modify")).'">';
		$texte .= '<br></div></div>';
		$texte .= '</div>';

		// Add input to upload a new template file.
		$texte .= '<div class="div_odtmodelsof'.get_class($this).'" style="display: none;">';
		$maxfilesizearray = getMaxFileSizeArray();
		$maxmin = $maxfilesizearray['maxmin'];
		if ($maxmin > 0) {
			$texte .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
		}
		$texte .= ' <input type="file" name="uploadfile">';
		$texte .= '<input type="hidden" value="HOLIDAY_ADDON_PDF_ODT_PATH" name="keyforuploaddir">';
		$texte .= '<input type="submit" class="button smallpaddingimp reposition" value="'.dol_escape_htmltag($langs->trans("Upload")).'" name="upload">';
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
	 *	@param	Holiday		$object				Object source to build document
	 *	@param	Translate	$outputlangs		Lang output object
	 *	@param	string		$srctemplatepath	Full path of source filename for generator using a template file
	 *	@param	int<0,1>	$hidedetails		Do not show line details
	 *	@param	int<0,1>	$hidedesc			Do not show desc
	 *	@param	int<0,1>	$hideref			Do not show ref
	 *	@return	int<-1,1>						1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_generic_holiday_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
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

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "companies", "holiday", "dict"));

		if (empty($conf->holiday->dir_output)) {
			return -1;
		}

		// If $object is id instead of object
		if (!is_object($object)) {
			$id = $object;
			$object = new Holiday($this->db);
			$result = $object->fetch($id);
			if ($result < 0) {
				dol_print_error($this->db, $object->error);
				return -1;
			}
		}

		$dir = $conf->holiday->dir_output;
		$objectref = dol_sanitizeFileName($object->ref);
		if (!preg_match('/specimen/i', $objectref)) {
			$dir .= "/".$objectref;
		}

		if (!file_exists($dir)) {
			if (dol_mkdir($dir) < 0) {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		if (!file_exists($dir)) {
			$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			return -1;
		}

		$newfile = basename($srctemplatepath);
		$newfiletmp = preg_replace('/\.(odt|ods)/i', '', $newfile);
		$newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
		$newfiletmp = preg_replace('/modele_/i', '', $newfiletmp);

		$newfiletmp = $objectref.'_'.$newfiletmp;

		// Get extension (ods or odt)
		$newfileformat = substr($newfile, strrpos($newfile, '.') + 1);
		if (getDolGlobalString('MAIN_DOC_USE_TIMING')) {
			$format = getDolGlobalString('MAIN_DOC_USE_TIMING');
			if ($format == '1') {
				$format = '%Y%m%d%H%M%S';
			}
			$filename = $newfiletmp.'-'.dol_print_date(dol_now(), $format).'.'.$newfileformat;
		} else {
			$filename = $newfiletmp.'.'.$newfileformat;
		}
		$file = $dir.'/'.$filename;

		dol_mkdir($conf->holiday->dir_temp);
		if (!is_writable($conf->holiday->dir_temp)) {
			$this->error = $langs->transnoentities("ErrorFailedToWriteInTempDirectory", $conf->holiday->dir_temp);
			dol_syslog('Error in write_file: '.$this->error, LOG_ERR);
			return -1;
		}

		// Open and load template
		require_once ODTPHP_PATH.'odf.php';
		try {
			$odfHandler = new Odf(
				$srctemplatepath,
				array(
					'PATH_TO_TMP'	  => $conf->holiday->dir_temp,
					'ZIP_PROXY'		  => getDolGlobalString('MAIN_ODF_ZIP_PROXY', 'PclZipProxy'), // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
					'DELIMITER_LEFT'  => '{',
					'DELIMITER_RIGHT' => '}'
				)
			);
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			dol_syslog($e->getMessage(), LOG_WARNING);
			return -1;
		}

		// Make substitutions into odt
		$array_user = $this->get_substitutionarray_user($user, $outputlangs);
		$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
		$array_other = $this->get_substitutionarray_other($outputlangs);
		$array_object = $this->get_substitutionarray_holiday($object, $outputlangs);

		$tmparray = array_merge($array_user, $array_soc, $array_other, $array_object);
		complete_substitutions_array($tmparray, $outputlangs, $object);
		$object->fetch_optionals();
		// Call the ODTSubstitution hook
		$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
		$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

		// retrieve the constant to apply a ratio for image size or set the ratio to 1
		if (getDolGlobalString('MAIN_DOC_ODT_IMAGE_RATIO')) {
			$ratio = (float) getDolGlobalString('MAIN_DOC_ODT_IMAGE_RATIO');
		} else {
			$ratio = 1;
		}

		foreach ($tmparray as $key => $value) {
			try {
				if (preg_match('/logo$/', $key)) { // Image
					if (file_exists($value)) {
						$odfHandler->setImage($key, $value, $ratio);
					} else {
						$odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
					}
				} else { // Text
					$odfHandler->setVars($key, $value, true, 'UTF-8');
				}
			} catch (OdfException $e) {
				dol_syslog($e->getMessage(), LOG_WARNING);
			}
		}

		// Replace labels translated
		$tmparray = $outputlangs->get_translations_for_substitutions();
		foreach ($tmparray as $key => $value) {
			try {
				$odfHandler->setVars($key, $value, true, 'UTF-8');
			} catch (OdfException $e) {
				dol_syslog($e->getMessage(), LOG_WARNING);
			}
		}

		// Call the beforeODTSave hook
		$parameters = array('odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs);
		$reshook = $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

		// Write new file
		if (getDolGlobalString('MAIN_ODT_AS_PDF')) {
			try {
				$odfHandler->exportAsAttachedPDF($file);
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				dol_syslog($e->getMessage(), LOG_WARNING);
				return -1;
			}
		} else {
			try {
				$odfHandler->saveToDisk($file);
			} catch (Exception $e) {
				$this->error = $e->getMessage();
				dol_syslog($e->getMessage(), LOG_WARNING);
				return -1;
			}
		}

		$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

		dolChmod($file);

		$odfHandler = null; // Destroy object

		$this->result = array('fullpath' => $file);

		$outputlangs->charset_output = $sav_charset_output;

		return 1; // Success
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Return the substitution array of a leave request.
	 *
	 * On top of the raw fields of the object, it carries what a leave form is actually written with:
	 * the dates and the number of days as the page shows them, the label of the type of leave, the
	 * status, and the employee and the approver as two separate people. The raw fields alone would
	 * give a template writer timestamps and foreign keys.
	 *
	 * @param	Holiday							$object			Leave request
	 * @param	Translate						$outputlangs	Lang object to use for output
	 * @return	array<string,null|int|float|string>				Array of substitutions
	 */
	public function get_substitutionarray_holiday($object, $outputlangs)
	{
		// phpcs:enable
		$array_object = array();

		// Every scalar field of the object, as object_xxx
		foreach ($object as $key => $value) {
			if (!is_array($value) && !is_object($value)) {
				$array_object['object_'.$key] = $value;
			}
		}

		$employee = new User($this->db);
		if ($object->fk_user > 0) {
			$employee->fetch($object->fk_user);
		}
		$approver = new User($this->db);
		if ($object->fk_validator > 0) {
			$approver->fetch($object->fk_validator);
		}

		foreach (array('employee' => $employee, 'approver' => $approver) as $prefix => $tmpuser) {
			$array_object[$prefix.'_lastname'] = (string) $tmpuser->lastname;
			$array_object[$prefix.'_firstname'] = (string) $tmpuser->firstname;
			$array_object[$prefix.'_fullname'] = $tmpuser->getFullName($outputlangs, 1);
			$array_object[$prefix.'_login'] = (string) $tmpuser->login;
			$array_object[$prefix.'_email'] = (string) $tmpuser->email;
			$array_object[$prefix.'_job'] = (string) $tmpuser->job;
		}

		// A half day leave starts or ends at midday, and the form has to say which half
		$starthalfday = ($object->halfday == -1 || $object->halfday == 2) ? 'Afternoon' : 'Morning';
		$endhalfday = ($object->halfday == 1 || $object->halfday == 2) ? 'Morning' : 'Afternoon';

		$typeleaves = $object->getTypes(1, -1);
		$typelabel = '';
		if (!empty($typeleaves[$object->fk_type])) {
			$typelabel = $typeleaves[$object->fk_type]['code'] ? $outputlangs->trans($typeleaves[$object->fk_type]['code']) : '';
			if (empty($typelabel) || $typelabel == $typeleaves[$object->fk_type]['code']) {
				$typelabel = $typeleaves[$object->fk_type]['label'];
			}
		}

		$array_object['object_ref'] = (string) $object->ref;
		$array_object['object_type_label'] = (string) $typelabel;
		$array_object['object_status_label'] = $object->getLibStatut(1);
		$array_object['object_date_debut'] = dol_print_date($object->date_debut, 'day', 'tzuser', $outputlangs);
		$array_object['object_date_fin'] = dol_print_date($object->date_fin, 'day', 'tzuser', $outputlangs);
		$array_object['object_halfday_start'] = $outputlangs->transnoentities($starthalfday);
		$array_object['object_halfday_end'] = $outputlangs->transnoentities($endhalfday);
		$array_object['object_nb_open_day'] = num_open_day($object->date_debut_gmt, $object->date_fin_gmt, 0, 1, $object->halfday, $employee->country_id, $object->fk_user);
		$array_object['object_description'] = (string) $object->description;
		$array_object['object_date_creation'] = dol_print_date($object->date_creation, 'dayhour', 'tzuser', $outputlangs);
		$array_object['object_date_valid'] = dol_print_date($object->date_valid, 'dayhour', 'tzuser', $outputlangs);
		$array_object['object_date_approval'] = dol_print_date($object->date_approval, 'dayhour', 'tzuser', $outputlangs);
		$array_object['object_date_refuse'] = dol_print_date($object->date_refuse, 'dayhour', 'tzuser', $outputlangs);
		$array_object['object_date_cancel'] = dol_print_date($object->date_cancel, 'dayhour', 'tzuser', $outputlangs);
		$array_object['object_detail_refuse'] = (string) $object->detail_refuse;
		$array_object['object_note_public'] = (string) $object->note_public;
		$array_object['object_note_private'] = (string) $object->note_private;

		return $array_object;
	}
}
