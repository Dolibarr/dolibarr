<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Florian Henry		<florian.henry@ope-concept.pro>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018-2024  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2023      	Gauthier VERDOL     <gauthier.verdol@atm-consulting.fr>
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
 *	\file       htdocs/core/modules/project/task/doc/doc_generic_task_odt.modules.php
 *	\ingroup    project
 *	\brief      File of class to build ODT documents for third parties
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
require_once DOL_DOCUMENT_ROOT.'/core/modules/project/task/modules_task.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/project.class.php';
require_once DOL_DOCUMENT_ROOT.'/projet/class/task.class.php';
require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';
require_once DOL_DOCUMENT_ROOT.'/user/class/user.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/date.lib.php';
if (isModEnabled("propal")) {
	require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
}
if (isModEnabled('invoice')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
}
if (isModEnabled('invoice')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
}
if (isModEnabled('order')) {
	require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
}
if (isModEnabled("supplier_invoice")) {
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
}
if (isModEnabled("supplier_order")) {
	require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
}
if (isModEnabled('contract')) {
	require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
}
if (isModEnabled('intervention')) {
	require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
}
if (isModEnabled('deplacement')) {
	require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
}
if (isModEnabled('agenda')) {
	require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';
}


/**
 *	Class to build documents using ODF templates generator
 */
class doc_generic_task_odt extends ModelePDFTask
{
	/**
	 * Dolibarr version of the loaded document
	 * @var string
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
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'PROJECT_TASK_ADDON_PDF_ODT_PATH'; // Name of constant that is used to save list of directories to scan

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
		$this->option_tva = 0; // Manage the vat option COMMANDE_TVAOPTION
		$this->option_modereg = 0; // Display payment mode
		$this->option_condreg = 0; // Display payment terms
		$this->option_multilang = 0; // Available in several languages
		$this->option_escompte = 0; // Displays if there has been a discount
		$this->option_credit_note = 0; // Support credit notes
		$this->option_freetext = 1; // Support add of a personalised text
		$this->option_draft_watermark = 0; // Support add of a watermark on drafts

		// Get source company
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2); // By default, if was not defined
		}
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 * Define array with couple substitution key => substitution value
	 *
	 * @param   CommonObject	$object             Main object to use as data source
	 * @param   Translate		$outputlangs        Lang object to use for output
	 * @param   string		    $array_key	        Name of the key for return array
	 * @return	array								Array of substitution
	 */
	public function get_substitutionarray_object($object, $outputlangs, $array_key = 'object')
	{
		// phpcs:enable
		global $extrafields;

		if (!$object instanceof Project) {
			dol_syslog("Expected Project object, got ".gettype($object), LOG_ERR);
			return array();
		}

		$resarray = array(
			$array_key.'_id' => $object->id,
			$array_key.'_ref' => $object->ref,
			$array_key.'_title' => $object->title,
			$array_key.'_description' => $object->description,
			$array_key.'_date_creation' => dol_print_date($object->date_c, 'day'),
			$array_key.'_date_modification' => dol_print_date($object->date_m, 'day'),
			$array_key.'_date_start' => dol_print_date($object->date_start, 'day'),
			$array_key.'_date_end' => dol_print_date($object->date_end, 'day'),
			$array_key.'_note_private' => $object->note_private,
			$array_key.'_note_public' => $object->note_public,
			$array_key.'_public' => $object->public,
			$array_key.'_statut' => $object->getLibStatut()
		);

		// Retrieve extrafields
		if (is_array($object->array_options) && count($object->array_options)) {
			$object->fetch_optionals();

			$resarray = $this->fill_substitutionarray_with_extrafields($object, $resarray, $extrafields, $array_key, $outputlangs);
		}

		return $resarray;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  Task			$task				Task Object
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @param  string		    $array_key	        Name of the key for return array
	 *  @return	array								Return a substitution array
	 */
	public function get_substitutionarray_tasks($task, $outputlangs, $array_key = 'task')
	{
		// phpcs:enable
		global $extrafields;

		$resarray = array(
			'task_ref' => $task->ref,
			'task_fk_project' => $task->fk_project,
			'task_projectref' => $task->projectref,
			'task_projectlabel' => $task->projectlabel,
			'task_label' => $task->label,
			'task_description' => $task->description,
			'task_fk_parent' => $task->fk_task_parent,
			'task_duration' => $task->duration_effective,
			'task_duration_formated' => convertSecondToTime($task->duration_effective, 'allhourmin'),
			'task_planned_workload' => $task->planned_workload,
			'task_planned_workload_formated' => convertSecondToTime($task->planned_workload, 'allhourmin'),
			'task_progress' => $task->progress,
			'task_public' => $task->public,
			'task_date_start' => dol_print_date($task->date_start, 'day'),
			'task_date_end' => dol_print_date($task->date_end, 'day'),
			'task_note_private' => $task->note_private,
			'task_note_public' => $task->note_public
		);

		// Retrieve extrafields
		if (is_array($task->array_options) && count($task->array_options)) {
			$task->fetch_optionals();

			$resarray = $this->fill_substitutionarray_with_extrafields($task, $resarray, $extrafields, $array_key, $outputlangs);
		}

		return $resarray;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$contact			Contact array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	public function get_substitutionarray_project_contacts($contact, $outputlangs)
	{
		// phpcs:enable
		return array(
			'projcontacts_id' => $contact['id'],
			'projcontacts_rowid' => $contact['rowid'],
			'projcontacts_role' => $contact['libelle'],
			'projcontacts_lastname' => $contact['lastname'],
			'projcontacts_firstname' => $contact['firstname'],
			'projcontacts_fullcivname' => $contact['fullname'],
			'projcontacts_socname' => $contact['socname'],
			'projcontacts_email' => $contact['email']
		);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$file				file array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	public function get_substitutionarray_project_file($file, $outputlangs)
	{
		// phpcs:enable
		return array(
			'projfile_name' => $file['name'],
			'projfile_date' => dol_print_date($file['date'], 'day'),
			'projfile_size' => $file['size']
		);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$refdetail			Reference array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	public function get_substitutionarray_project_reference($refdetail, $outputlangs)
	{
		// phpcs:enable
		return array(
			'projref_type' => $refdetail['type'],
			'projref_ref' => $refdetail['ref'],
			'projref_date' => dol_print_date($refdetail['date'], 'day'),
			'projref_socname' => $refdetail['socname'],
			'projref_amountht' => price($refdetail['amountht'], 0, $outputlangs),
			'projref_amountttc' => price($refdetail['amountttc'], 0, $outputlangs),
			'projref_status' => $refdetail['status']
		);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$taskresource		Resource array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	public function get_substitutionarray_tasksressource($taskresource, $outputlangs)
	{
		// phpcs:enable

		//dol_syslog(get_class($this).'::get_substitutionarray_tasksressource taskressource='.var_export($taskressource,true),LOG_DEBUG);
		return array(
			'taskressource_rowid' => $taskresource['rowid'],
			'taskressource_role' => $taskresource['libelle'],
			'taskressource_lastname' => $taskresource['lastname'],
			'taskressource_firstname' => $taskresource['firstname'],
			'taskressource_fullcivname' => $taskresource['fullname'],
			'taskressource_socname' => $taskresource['socname'],
			'taskressource_email' => $taskresource['email']
		);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$tasktime			times object
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	public function get_substitutionarray_taskstime($tasktime, $outputlangs)
	{
		// phpcs:enable
		global $conf;

		return array(
			'tasktime_rowid' => $tasktime['rowid'],
			'tasktime_task_date' => dol_print_date($tasktime['task_date'], 'day'),
			'tasktime_task_duration' => convertSecondToTime($tasktime['task_duration'], 'all'),
			'tasktime_note' => $tasktime['note'],
			'tasktime_fk_user' => $tasktime['fk_user'],
			'tasktime_user_name' => $tasktime['lastname'],
			'tasktime_user_first' => $tasktime['firstname'],
			'tasktime_fullcivname' => $tasktime['fullcivname']
		);
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$file				file array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	public function get_substitutionarray_task_file($file, $outputlangs)
	{
		// phpcs:enable
		return array(
			'tasksfile_name' => $file['name'],
			'tasksfile_date' => dol_print_date($file['date'], 'day'),
			'tasksfile_size' => $file['size']
		);
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

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="PROJECT_TASK_ADDON_PDF_ODT_PATH">';
		$texte .= '<table class="nobordernopadding centpercent">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->PROJECT_TASK_ADDON_PDF_ODT_PATH)));
		$listoffiles = array();
		foreach ($listofdir as $key => $tmpdir) {
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if (!$tmpdir) {
				unset($listofdir[$key]);
				continue;
			}
			if (!is_dir($tmpdir)) {
				$texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), 0);
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

		$texte .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1, 3, $this->name);
		$texte .= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$texte .= '<textarea class="flat" cols="60" name="value1">';
		$texte .= getDolGlobalString('PROJECT_TASK_ADDON_PDF_ODT_PATH');
		$texte .= '</textarea>';
		$texte .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$texte .= '<input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="'.dol_escape_htmltag($langs->trans("Modify")).'">';
		$texte .= '<br></div></div>';

		// Scan directories
		$nbofiles = count($listoffiles);
		if (getDolGlobalString('PROJECT_TASK_ADDON_PDF_ODT_PATH')) {
			$texte .= $langs->trans("NumberOfModelFilesFound").': <b>';
			//$texte.=$nbofiles?'<a id="a_'.get_class($this).'" href="#">':'';
			$texte .= $nbofiles;
			//$texte.=$nbofiles?'</a>':'';
			$texte .= '</b>';
		}

		if ($nbofiles) {
			$texte .= '<div id="div_'.get_class($this).'" class="hiddenx">';
			// Show list of found files
			foreach ($listoffiles as $file) {
				$texte .= '- '.$file['name'].' <a href="'.DOL_URL_ROOT.'/document.php?modulepart=doctemplates&file=tasks/'.urlencode(basename($file['name'])).'">'.img_picto('', 'listlight').'</a>';
				$texte .= ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?modulepart=doctemplates&keyforuploaddir=PROJECT_TASK_ADDON_PDF_ODT_PATH&action=deletefile&token='.newToken().'&file='.urlencode(basename($file['name'])).'">'.img_picto('', 'delete').'</a>';
				$texte .= '<br>';
			}
			$texte .= '</div>';
		}
		// Add input to upload a new template file.
		$texte .= '<div>'.$langs->trans("UploadNewTemplate");
		$maxfilesizearray = getMaxFileSizeArray();
		$maxmin = $maxfilesizearray['maxmin'];
		if ($maxmin > 0) {
			$texte .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
		}
		$texte .= ' <input type="file" name="uploadfile">';
		$texte .= '<input type="hidden" value="PROJECT_TASK_ADDON_PDF_ODT_PATH" name="keyforuploaddir">';
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
	 *	Function to build a document on disk using the generic odt module.
	 *
	 *	@param	Commande	$object					Object source to build document
	 *	@param	Translate	$outputlangs			Lang output object
	 * 	@param	string		$srctemplatepath	    Full path of source filename for generator using a template file
	 *	@return	int         						1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath)
	{
		// phpcs:enable
		global $user, $langs, $conf, $mysoc, $hookmanager;

		if (empty($srctemplatepath)) {
			dol_syslog("doc_generic_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$sav_charset_output = $outputlangs->charset_output;
		$outputlangs->charset_output = 'UTF-8';

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "dict", "companies", "projects"));

		if ($conf->project->dir_output) {
			// If $object is id instead of object
			if (!is_object($object)) {
				$id = $object;
				$object = new Task($this->db);
				$result = $object->fetch($id);
				if ($result < 0) {
					dol_print_error($this->db, $object->error);
					return -1;
				}
			}
			$project = new Project($this->db);
			$project->fetch($object->fk_project);
			$project->fetch_thirdparty();

			$dir = $conf->project->dir_output."/".$project->ref."/";
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
				$newfiletmp = preg_replace('/\.(ods|odt)/i', '', $newfile);
				$newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
				$newfiletmp = preg_replace('/modele_/i', '', $newfiletmp);
				$newfiletmp = $objectref . '_' . $newfiletmp;
				//$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now(),'%Y%m%d%H%M%S').'.odt';
				$file = $dir . '/' . $newfiletmp . '.odt';
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;

				dol_mkdir($conf->project->dir_temp);
				if (!is_writable($conf->project->dir_temp)) {
					$this->error = $langs->transnoentities("ErrorFailedToWriteInTempDirectory", $conf->project->dir_temp);
					dol_syslog('Error in write_file: ' . $this->error, LOG_ERR);
					return -1;
				}

				$socobject = $project->thirdparty;

				// Make substitution
				$substitutionarray = array(
					'__FROM_NAME__' => $this->emetteur->name,
					'__FROM_EMAIL__' => $this->emetteur->email,
				);
				complete_substitutions_array($substitutionarray, $langs, $object);
				// Call the ODTSubstitution hook
				$tmparray = array();
				$action = '';
				$parameters = array('file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray);
				$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				try {
					$odfHandler = new Odf(
						$srctemplatepath,
						array(
							'PATH_TO_TMP'	  => $conf->project->dir_temp,
							'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
							'DELIMITER_LEFT'  => '{',
							'DELIMITER_RIGHT' => '}'
						)
					);
				} catch (Exception $e) {
					$this->error = $e->getMessage();
					return -1;
				}
				// After construction $odfHandler->contentXml contains content and
				// [!-- BEGIN row.lines --]*[!-- END row.lines --] has been replaced by
				// [!-- BEGIN lines --]*[!-- END lines --]
				//print html_entity_decode($odfHandler->__toString());
				//print exit;


				// Define substitution array
				$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
				$array_object_from_properties = $this->get_substitutionarray_each_var_object($object, $outputlangs);
				$array_objet = $this->get_substitutionarray_object($project, $outputlangs);
				$array_user = $this->get_substitutionarray_user($user, $outputlangs);
				$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
				$array_thirdparty = $this->get_substitutionarray_thirdparty($socobject, $outputlangs);
				$array_other = $this->get_substitutionarray_other($outputlangs);

				$tmparray = array_merge($substitutionarray, $array_object_from_properties, $array_user, $array_soc, $array_thirdparty, $array_objet, $array_other);
				complete_substitutions_array($tmparray, $outputlangs, $object);

				foreach ($tmparray as $key => $value) {
					try {
						if (preg_match('/logo$/', $key)) { // Image
							if (file_exists($value)) {
								$odfHandler->setImage($key, $value);
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

				// Replace tags of lines for tasks
				try {
					// Security check
					$socid = 0;
					if (!empty($project->fk_soc)) {
						$socid = $project->fk_soc;
					}

					$tmparray = $this->get_substitutionarray_tasks($object, $outputlangs);
					complete_substitutions_array($tmparray, $outputlangs, $object);
					foreach ($tmparray as $key => $val) {
						try {
							$odfHandler->setVars($key, $val, true, 'UTF-8');
						} catch (OdfException $e) {
							dol_syslog($e->getMessage(), LOG_INFO);
						}
					}

					// Replace tags of lines for contacts task
					$sourcearray = array('internal', 'external');
					$contact_arrray = array();
					foreach ($sourcearray as $source) {
						$contact_temp = $object->liste_contact(-1, $source);
						if ((is_array($contact_temp) && count($contact_temp) > 0)) {
							$contact_arrray = array_merge($contact_arrray, $contact_temp);
						}
					}
					// Check for segment
					$foundtagforlines = 1;
					try {
						$listlinestaskres = $odfHandler->setSegment('tasksressources');
					} catch (OdfExceptionSegmentNotFound $e) {
						// We may arrive here if tags for lines not present into template
						$foundtagforlines = 0;
						dol_syslog($e->getMessage(), LOG_INFO);
					}
					if ($foundtagforlines && (is_array($contact_arrray) && count($contact_arrray) > 0)) {
						foreach ($contact_arrray as $contact) {
							if ($contact['source'] == 'internal') {
								$objectdetail = new User($this->db);
								$objectdetail->fetch($contact['id']);
								$contact['socname'] = $mysoc->name;
							} elseif ($contact['source'] == 'external') {
								$objectdetail = new Contact($this->db);
								$objectdetail->fetch($contact['id']);

								$soc = new Societe($this->db);
								$soc->fetch($contact['socid']);
								$contact['socname'] = $soc->name;
							}
							$contact['fullname'] = $objectdetail->getFullName($outputlangs, 1);

							$tmparray = $this->get_substitutionarray_tasksressource($contact, $outputlangs);

							foreach ($tmparray as $key => $val) {
								try {
									$listlinestaskres->setVars($key, $val, true, 'UTF-8');
								} catch (OdfException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlinestaskres->merge();
						}
						$odfHandler->mergeSegment($listlinestaskres);
					}

					// Time resources
					$sql = "SELECT t.rowid, t.element_date as task_date, t.element_duration as task_duration, t.fk_user, t.note";
					$sql .= ", u.lastname, u.firstname";
					$sql .= " FROM ".MAIN_DB_PREFIX."element_time as t";
					$sql .= " , ".MAIN_DB_PREFIX."user as u";
					$sql .= " WHERE t.fk_element =".((int) $object->id);
					$sql .= " AND t.elementtype = 'task'";
					$sql .= " AND t.fk_user = u.rowid";
					$sql .= " ORDER BY t.element_date DESC";

					$resql = $this->db->query($sql);
					if ($resql) {
						$num = $this->db->num_rows($resql);
						$i = 0;
						$tasks = array();
						$listlinestasktime = $odfHandler->setSegment('taskstimes');
						while ($i < $num) {
							$row = $this->db->fetch_array($resql);
							if (!empty($row['fk_user'])) {
								$objectdetail = new User($this->db);
								$objectdetail->fetch($row['fk_user']);
								// TODO Use a cache to aoid fetch for same user
								$row['fullcivname'] = $objectdetail->getFullName($outputlangs, 1);
							} else {
								$row['fullcivname'] = '';
							}

							$tmparray = $this->get_substitutionarray_taskstime($row, $outputlangs);

							foreach ($tmparray as $key => $val) {
								try {
									$listlinestasktime->setVars($key, $val, true, 'UTF-8');
								} catch (OdfException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								} catch (SegmentException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlinestasktime->merge();
							$i++;
						}
						$this->db->free($resql);

						$odfHandler->mergeSegment($listlinestasktime);
					}


					// Replace tags of project files
					$listtasksfiles = $odfHandler->setSegment('tasksfiles');

					$upload_dir = $conf->project->dir_output.'/'.dol_sanitizeFileName($project->ref).'/'.dol_sanitizeFileName($object->ref);
					$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 1);


					foreach ($filearray as $filedetail) {
						$tmparray = $this->get_substitutionarray_task_file($filedetail, $outputlangs);
						//dol_syslog(get_class($this).'::main $tmparray'.var_export($tmparray,true));
						foreach ($tmparray as $key => $val) {
							try {
								$listtasksfiles->setVars($key, $val, true, 'UTF-8');
							} catch (OdfException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							}
						}
						$listtasksfiles->merge();
					}
					//$listlines->merge();

					$odfHandler->mergeSegment($listtasksfiles);
				} catch (OdfException $e) {
					$this->error = $e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -1;
				}


				// Replace tags of project files
				try {
					$listlines = $odfHandler->setSegment('projectfiles');

					$upload_dir = $conf->project->dir_output.'/'.dol_sanitizeFileName($object->ref);
					$filearray = dol_dir_list($upload_dir, "files", 0, '', '(\.meta|_preview.*\.png)$', 'name', SORT_ASC, 1);


					foreach ($filearray as $filedetail) {
						//dol_syslog(get_class($this).'::main $filedetail'.var_export($filedetail,true));
						$tmparray = $this->get_substitutionarray_project_file($filedetail, $outputlangs);

						foreach ($tmparray as $key => $val) {
							try {
								$listlines->setVars($key, $val, true, 'UTF-8');
							} catch (OdfException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							} catch (SegmentException $e) {
								dol_syslog($e->getMessage(), LOG_INFO);
							}
						}
						$listlines->merge();
					}
					$odfHandler->mergeSegment($listlines);
				} catch (OdfException $e) {
					$this->error = $e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -1;
				}

				// Replace tags of lines for contacts
				$sourcearray = array('internal', 'external');
				$contact_arrray = array();
				foreach ($sourcearray as $source) {
					$contact_temp = $project->liste_contact(-1, $source);
					if ((is_array($contact_temp) && count($contact_temp) > 0)) {
						$contact_arrray = array_merge($contact_arrray, $contact_temp);
					}
				}
				// Check for segment
				$foundtagforlines = 1;
				try {
					$listlines = $odfHandler->setSegment('projectcontacts');
				} catch (OdfExceptionSegmentNotFound $e) {
					// We may arrive here if tags for lines not present into template
					$foundtagforlines = 0;
					dol_syslog($e->getMessage(), LOG_INFO);
				}
				if ($foundtagforlines && (is_array($contact_arrray) && count($contact_arrray) > 0)) {
					try {
						foreach ($contact_arrray as $contact) {
							if ($contact['source'] == 'internal') {
								$objectdetail = new User($this->db);
								$objectdetail->fetch($contact['id']);
								$contact['socname'] = $mysoc->name;
							} elseif ($contact['source'] == 'external') {
								$objectdetail = new Contact($this->db);
								$objectdetail->fetch($contact['id']);

								$soc = new Societe($this->db);
								$soc->fetch($contact['socid']);
								$contact['socname'] = $soc->name;
							}
							$contact['fullname'] = $objectdetail->getFullName($outputlangs, 1);

							$tmparray = $this->get_substitutionarray_project_contacts($contact, $outputlangs);

							foreach ($tmparray as $key => $val) {
								try {
									$listlines->setVars($key, $val, true, 'UTF-8');
								} catch (SegmentException $e) {
									dol_syslog($e->getMessage(), LOG_INFO);
								}
							}
							$listlines->merge();
						}
						$odfHandler->mergeSegment($listlines);
					} catch (OdfException $e) {
						$this->error = $e->getMessage();
						dol_syslog($this->error, LOG_WARNING);
						return -1;
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
				return -1;
			}
		}

		return -1;
	}
}
