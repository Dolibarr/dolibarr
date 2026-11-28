<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2014		Marcos García		<marcosgdf@gmail.com>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018-2021  Philippe Grand      <philippe.grand@atoo-net.com>
 * Copyright (C) 2018-2025  Frédéric France     <frederic.france@free.fr>
 * Copyright (C) 2024		MDW							<mdeweerd@users.noreply.github.com>
 * Copyright (C) 2026		Jonathan Lescaut			<jonathan.lescaut@atm-consulting.fr>
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
 *	\file       htdocs/core/class/commonodtgenerator.class.php
 *	\ingroup    core
 *	\brief      Trait shared by the document generators building files from ODT/ODS templates
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';


/**
 *	Trait for document generators using ODT/ODS templates.
 *
 *	write_file() runs the generation steps in a fixed order; a host class overrides only
 *	the steps that differ for its document type.
 *
 * Members expected on the host class (declared by CommonDocGenerator):
 *
 * @property DoliDB $db
 * @property string $name
 * @property string $description
 * @property int $update_main_doc_field
 * @property string $scandir
 * @property string $type
 * @property float $page_largeur
 * @property float $page_hauteur
 * @property array{0:float,1:float} $format
 * @property float $marge_gauche
 * @property float $marge_droite
 * @property float $marge_haute
 * @property float $marge_basse
 * @property int<0,1> $option_logo
 * @property int<0,1> $option_tva
 * @property int<0,1> $option_modereg
 * @property int<0,1> $option_condreg
 * @property int<0,1> $option_multilang
 * @property int<0,1> $option_escompte
 * @property int<0,1> $option_credit_note
 * @property int<0,1> $option_freetext
 * @property int<0,1> $option_draft_watermark
 * @property Societe $emetteur
 * @property string $error
 * @property array{fullpath:string} $result
 * @method array<string,float|string> get_substitutionarray_user(User $user, Translate $outputlangs)
 * @method array<string,mixed> get_substitutionarray_mysoc(Societe $mysoc, Translate $outputlangs)
 * @method array<string,mixed> get_substitutionarray_thirdparty(?Societe $object, Translate $outputlangs, string $array_key = 'company')
 * @method array<string,mixed> get_substitutionarray_contact(Contact $object, Translate $outputlangs, string $array_key = 'object')
 * @method array<string,mixed> get_substitutionarray_other(Translate $outputlangs)
 * @method array<string,mixed> get_substitutionarray_object(CommonObject $object, Translate $outputlangs, string $array_key = 'object')
 * @method array<string,mixed> get_substitutionarray_lines(CommonObjectLine $line, Translate $outputlangs, int $linenumber = 0)
 * @method array<string,mixed> get_substitutionarray_each_var_object(CommonObject &$object, Translate $outputlangs, bool|int $recursive = 1)
 */
trait CommonOdtGenerator
{
	/**
	 * @var string	Class of the business object, instantiated when write_file() receives an id
	 */
	protected $odtObjectClass = '';

	/**
	 * @var string	Module whose output and temporary directories receive the documents, for example 'commande'
	 */
	protected $odtModule = '';

	/**
	 * @var string	Sub-directory of the doctemplates module part, used in template download links
	 */
	protected $odtTemplateSubdir = '';

	/**
	 * @var string	Constant holding the free text printed in {free_text}, '' when the document has none
	 */
	protected $odtFreeTextConst = '';

	/**
	 * @var string	Type code of the external contact used as recipient, '' when the document has none
	 */
	protected $odtExternalContactCode = '';

	/**
	 *	Tell whether the first ODTSubstitution hook, run before the template is opened, is called.
	 *
	 *	@return	bool
	 */
	protected function hasOdtPreSubstitutionHook(): bool
	{
		return $this->odtFreeTextConst !== '';
	}

	/**
	 *	Return the directory where documents of the object are stored.
	 *
	 *	@param	CommonObject	$object		Source object
	 *	@return	string|null					Directory without the object ref, or null if the module has no output directory
	 */
	protected function getOdtOutputDir(CommonObject $object): ?string
	{
		$dir = getMultidirOutput($object, $this->odtModule);

		return (empty($dir) || strpos($dir, 'error-') === 0) ? null : $dir;
	}

	/**
	 *	Return the temporary directory used by the ODT library.
	 *
	 *	@param	CommonObject	$object		Source object
	 *	@return	string|null					Temporary directory, or null if the module has no temporary directory
	 */
	protected function getOdtTempDir(CommonObject $object): ?string
	{
		$dir = getMultidirTemp($object, $this->odtModule);

		return (empty($dir) || strpos($dir, 'error-') === 0) ? null : $dir;
	}

	/**
	 *	Return the directory of the generated file for this object.
	 *
	 *	@param	CommonObject	$object		Source object
	 *	@param	string			$objectref	Sanitized object ref
	 *	@return	string|null					Directory, or null if the module has no output directory
	 */
	protected function getOdtObjectDir(CommonObject $object, string $objectref): ?string
	{
		$dir = $this->getOdtOutputDir($object);
		if ($dir === null || preg_match('/specimen/i', $objectref)) {
			return $dir;
		}

		return $dir.'/'.$objectref;
	}

	/**
	 *	Load the data the substitutions need, before any substitution array is built.
	 *
	 *	@param	CommonObject	$object		Source object, with thirdparty loaded when it has one
	 *	@return	void
	 */
	protected function prepareOdtObject(CommonObject $object): void
	{
	}

	/**
	 *	Return the substitution keys specific to the document type, merged right after the object keys.
	 *
	 *	Values set by complete_substitutions_array() and by the ODTSubstitution hook take precedence.
	 *
	 *	@param	CommonObject	$object			Source object
	 *	@param	Translate		$outputlangs	Output language
	 *	@return	array<string,mixed>				Substitution keys
	 */
	protected function getOdtExtraSubstitutionArray(CommonObject $object, Translate $outputlangs): array
	{
		return [];
	}

	/**
	 *	Change the substitution array after complete_substitutions_array(), before the ODTSubstitution hook.
	 *
	 *	@param	array<string,mixed>	$substitutionArray	Substitution array to complete
	 *	@param	CommonObject		$object				Source object
	 *	@param	Translate			$outputlangs		Output language
	 *	@return	void
	 */
	protected function completeOdtSubstitutionArray(array &$substitutionArray, CommonObject $object, Translate $outputlangs): void
	{
	}

	/**
	 *	Return extra hidden inputs added to the setup form, after param1.
	 *
	 *	@return	string	HTML
	 */
	protected function getOdtSetupExtraHiddenInputs(): string
	{
		return '';
	}

	/**
	 *	Return extra HTML shown in the setup form after the list of template files.
	 *
	 *	@param	Translate	$langs		Language object
	 *	@param	Form		$form		Form helper
	 *	@return	string					HTML
	 */
	protected function getOdtSetupExtraHtml(Translate $langs, Form $form): string
	{
		return '';
	}

	/**
	 *	Initialize the properties shared by all ODT generators. Called by the host constructor.
	 *
	 *	@param	DoliDB	$db				Database handler
	 *	@param	string	$scandir		Name of the constant holding the list of template directories
	 *	@param	string	$objectClass	Class of the business object, for example 'Commande'
	 *	@param	string	$module			Module whose directories receive the documents, for example 'commande'
	 *	@param	string	$templateSubdir	Sub-directory of the doctemplates module part, for example 'orders'
	 *	@return	void
	 */
	protected function initOdtGenerator(DoliDB $db, string $scandir, string $objectClass, string $module, string $templateSubdir): void
	{
		global $langs, $mysoc;

		$langs->loadLangs(["main", "companies"]);

		$this->db = $db;
		$this->odtObjectClass = $objectClass;
		$this->odtModule = $module;
		$this->odtTemplateSubdir = $templateSubdir;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->update_main_doc_field = 1;
		$this->scandir = $scandir;

		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = [$this->page_largeur, $this->page_hauteur];
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute = 0;
		$this->marge_basse = 0;

		$this->option_logo = 1;
		$this->option_tva = 0;
		$this->option_modereg = 0;
		$this->option_condreg = 0;
		$this->option_multilang = 1;
		$this->option_escompte = 0;
		$this->option_credit_note = 0;
		$this->option_freetext = 1;
		$this->option_draft_watermark = 0;

		if ($mysoc === null) {
			dol_syslog(get_class($this).'::__construct() Global $mysoc should not be null.'.getCallerInfoString(), LOG_ERR);
			return;
		}

		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) {
			$this->emetteur->country_code = substr($langs->defaultlang, -2);
		}
	}

	/**
	 *	Return description of the module, with the form to manage template directories and files.
	 *
	 *	@param	Translate	$langs		Lang object to use for output
	 *	@return	string					Description
	 */
	public function info($langs)
	{
		$langs->loadLangs(["errors", "companies"]);

		$form = new Form($this->db);
		$odtPath = trim(getDolGlobalString($this->scandir));

		$texttitle = $langs->trans("ListOfDirectories");
		$listoffiles = [];
		foreach ($this->getOdtTemplateDirs($odtPath) as $tmpdir) {
			if (!is_dir($tmpdir)) {
				$texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), '');
				continue;
			}
			$listoffiles = array_merge($listoffiles, dol_dir_list($tmpdir, 'files', 0, '\.(ods|odt)'));
		}

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST" enctype="multipart/form-data">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="page_y" value="">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="'.dolPrintHTMLForAttribute($this->scandir).'">';
		$texte .= $this->getOdtSetupExtraHiddenInputs();
		$texte .= '<table class="nobordernopadding centpercent">';
		$texte .= '<tr><td>';

		if (!empty($odtPath)) {
			$texte .= '<span class="opacitylow">'.$langs->trans("NumberOfModelFilesFound").': <b>'.count($listoffiles).'</b></span><br>';
		}
		if (count($listoffiles)) {
			$texte .= $this->getOdtTemplateFileListHtml($listoffiles);
			$texte .= $this->getOdtSetupExtraHtml($langs, $form);
		}

		$texte .= '<br>';
		$texte .= $this->getOdtTemplateManagementHtml($langs, $form, $odtPath, $texttitle);
		$texte .= '</td>';
		$texte .= '</tr>';
		$texte .= '</table>';
		$texte .= '</form>';

		return $texte;
	}

	/**
	 *	Return the template directories configured in the scandir constant, with DOL_DATA_ROOT resolved.
	 *
	 *	@param	string		$odtPath	Value of the scandir constant
	 *	@return	string[]				Directories
	 */
	protected function getOdtTemplateDirs(string $odtPath): array
	{
		$dirs = [];
		foreach (explode(',', preg_replace('/[\r\n]+/', ',', $odtPath)) as $tmpdir) {
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, trim($tmpdir));
			if ($tmpdir) {
				$dirs[] = $tmpdir;
			}
		}

		return $dirs;
	}

	/**
	 *	Return the list of found template files, with download and delete links.
	 *
	 *	@param	array<array{name:string}>	$listoffiles	Files returned by dol_dir_list()
	 *	@return	string										HTML
	 */
	protected function getOdtTemplateFileListHtml(array $listoffiles): string
	{
		$html = '<div id="div_'.get_class($this).'" class="hiddenx opacitylow">';
		foreach ($listoffiles as $file) {
			$filename = urlencode(basename($file['name']));
			$html .= '- '.dol_escape_htmltag($file['name']);
			$html .= ' <a href="'.DOL_URL_ROOT.'/document.php?modulepart=doctemplates&file='.$this->odtTemplateSubdir.'/'.$filename.'">'.img_picto('', 'listlight').'</a>';
			$html .= ' &nbsp; <a class="reposition" href="'.$_SERVER["PHP_SELF"].'?modulepart=doctemplates&keyforuploaddir='.urlencode($this->scandir).'&action=deletefile&token='.newToken().'&file='.$filename.'">'.img_picto('', 'delete').'</a>';
			$html .= '<br>';
		}
		$html .= '</div>';

		return $html;
	}

	/**
	 *	Return the collapsible area to edit template directories and upload a template.
	 *
	 *	@param	Translate	$langs		Language object
	 *	@param	Form		$form		Form helper
	 *	@param	string		$odtPath	Value of the scandir constant
	 *	@param	string		$texttitle	Title of the directory list, with warnings on missing directories
	 *	@return	string					HTML
	 */
	protected function getOdtTemplateManagementHtml(Translate $langs, Form $form, string $odtPath, string $texttitle): string
	{
		$texthelp = $langs->trans("ListOfDirectoriesForModelGenODT");
		$texthelp .= '<br><br><span class="opacitymedium">'.$langs->trans("ExampleOfDirectoriesForModelGen").'</span>';
		$texthelp .= '<br>'.$langs->trans("FollowingSubstitutionKeysCanBeUsed").'<br>';
		$texthelp .= $langs->transnoentitiesnoconv("FullListOnOnlineDocumentation"); // Contains an url, must not be escaped

		$html = '<span class="classlink cursorpointer" onclick="javascript: jQuery(\'.div_odtmodelsof'.get_class($this).'\').toggle();">'.img_picto('', 'add').' '.$langs->trans("UploadNewTemplate").'...</span>';
		$html .= '<div class="div_odtmodelsof'.get_class($this).'" style="display: none; margin-top: 6px;">';
		$html .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1, 3, $this->name);
		$html .= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$html .= '<textarea class="flat textareafordir" spellcheck="false" cols="60" name="value1">';
		$html .= dol_escape_htmltag($odtPath, 0, 1);
		$html .= '</textarea>';
		$html .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$html .= '<input type="submit" class="button button-edit reposition smallpaddingimp" name="modify" value="'.dolPrintHTMLForAttribute($langs->trans("Modify")).'">';
		$html .= '<br></div></div>';

		$html .= '<div>';
		$maxfilesizearray = getMaxFileSizeArray();
		$maxmin = $maxfilesizearray['maxmin'];
		if ($maxmin > 0) {
			$html .= '<input type="hidden" name="MAX_FILE_SIZE" value="'.($maxmin * 1024).'">';	// MAX_FILE_SIZE must precede the field type=file
		}
		$html .= ' <input type="file" name="uploadfile">';
		$html .= '<input type="hidden" value="'.dolPrintHTMLForAttribute($this->scandir).'" name="keyforuploaddir">';
		$html .= '<input type="submit" class="button smallpaddingimp reposition" value="'.dolPrintHTMLForAttribute($langs->trans("Upload")).'" name="upload">';
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *	Build a document on disk from an ODT/ODS template.
	 *
	 *	@param		CommonObject|int	$object				Object source to build document, or its id
	 *	@param		Translate			$outputlangs		Lang output object
	 *	@param		string				$srctemplatepath	Full path of source filename for generator using a template file
	 *	@param		int<0,1>			$hidedetails		Do not show line details
	 *	@param		int<0,1>			$hidedesc			Do not show desc
	 *	@param		int<0,1>			$hideref			Do not show ref
	 *	@return		int<-1,1>								1 if OK, <=0 if KO
	 */
	public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0)
	{
		// phpcs:enable
		global $langs, $hookmanager, $action;

		if (empty($srctemplatepath)) {
			dol_syslog(get_class($this)."::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		if (!is_object($hookmanager)) {
			include_once DOL_DOCUMENT_ROOT.'/core/class/hookmanager.class.php';
			$hookmanager = new HookManager($this->db);
		}
		$hookmanager->initHooks(['odtgeneration']);

		if (!is_object($outputlangs)) {
			$outputlangs = $langs;
		}
		$outputlangs->charset_output = 'UTF-8';
		$outputlangs->loadLangs(["main", "dict", "companies", "bills"]);

		$object = $this->loadOdtObject($object);
		if ($object === null) {
			return -1;
		}
		if (method_exists($object, 'fetch_thirdparty')) {
			$object->fetch_thirdparty();
		}
		$this->prepareOdtObject($object);

		$file = $this->prepareOdtOutputFile($object, $srctemplatepath);
		if ($file === null) {
			return -1;
		}

		$tempDir = $this->prepareOdtTempDir($object);
		if ($tempDir === null) {
			return -1;
		}

		list($socobject, $contactobject) = $this->resolveOdtRecipient($object);

		$newfreetext = null;
		if ($this->hasOdtPreSubstitutionHook()) {
			$newfreetext = $this->buildOdtFreeText($this->getOdtPreSubstitutionArray($object, $file, $outputlangs));
		}

		$odfHandler = $this->openOdtTemplate($srctemplatepath, $tempDir);
		if ($odfHandler === null) {
			return -1;
		}

		if ($newfreetext !== null) {
			$this->setOdtVars($odfHandler, ['free_text' => $newfreetext], false);
		}

		$tmparray = $this->buildOdtSubstitutionArray($object, $outputlangs, $socobject, $contactobject);

		$parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray];
		$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action);

		$this->setOdtVars($odfHandler, $tmparray, true);

		if (!$this->setOdtSegments($odfHandler, $object, $outputlangs, $file)) {
			return -1;
		}

		$tmparray = $outputlangs->get_translations_for_substitutions();
		$this->setOdtVars($odfHandler, $tmparray, false);

		$parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray];
		$hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action);

		if (!$this->saveOdtFile($odfHandler, $file)) {
			return -1;
		}

		$parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray];
		$hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action);

		dolChmod($file);

		$odfHandler = null;

		$this->result = ['fullpath' => $file];

		return 1;
	}

	/**
	 *	Return the source object, fetching it when an id is given.
	 *
	 *	@param	CommonObject|int	$object		Object or id
	 *	@return	CommonObject|null				Object, or null on error ($this->error is set)
	 */
	protected function loadOdtObject($object): ?CommonObject
	{
		global $langs;

		if (is_object($object)) {
			return $object;
		}

		$class = $this->odtObjectClass;
		/** @var CommonObject $loaded */
		$loaded = new $class($this->db);
		$result = $loaded->fetch((int) $object);
		if ($result < 0) {
			dol_print_error($this->db, $loaded->error);
			return null;
		}
		if ($result == 0) {
			$this->error = $langs->transnoentities("ErrorRecordNotFound");
			dol_syslog(get_class($this)."::loadOdtObject ".$class." id=".((int) $object)." not found", LOG_WARNING);
			return null;
		}

		return $loaded;
	}

	/**
	 *	Create the output directory and return the full path of the file to generate.
	 *
	 *	@param	CommonObject	$object				Source object
	 *	@param	string			$srctemplatepath	Full path of the template
	 *	@return	string|null							Full path of the file, or null on error ($this->error is set when known)
	 */
	protected function prepareOdtOutputFile(CommonObject $object, string $srctemplatepath): ?string
	{
		global $langs;

		$objectref = dol_sanitizeFileName($object->ref);
		$dir = $this->getOdtObjectDir($object, $objectref);
		if ($dir === null) {
			$langs->load("errors");
			$this->error = $langs->transnoentities("ErrorModuleSetupNotComplete", $this->odtModule);
			dol_syslog(get_class($this)."::prepareOdtOutputFile no output directory for module ".$this->odtModule, LOG_ERR);
			return null;
		}

		if (!file_exists($dir)) {
			dol_mkdir($dir);
		}
		if (!file_exists($dir)) {
			$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
			return null;
		}

		return $dir.'/'.$this->buildOdtFileName($objectref, basename($srctemplatepath));
	}

	/**
	 *	Return the name of the generated file: <ref>_<template name without template_/modele_ prefix>.<ext>
	 *
	 *	@param	string	$objectref		Sanitized object ref
	 *	@param	string	$templateName	Template file name
	 *	@return	string					File name
	 */
	protected function buildOdtFileName(string $objectref, string $templateName): string
	{
		$basename = $this->buildOdtFileBaseName($objectref, $templateName);

		$extension = substr($templateName, strrpos($templateName, '.') + 1);

		if (getDolGlobalString('MAIN_DOC_USE_TIMING')) {
			$format = getDolGlobalString('MAIN_DOC_USE_TIMING');
			if ($format == '1') {
				$format = '%Y%m%d%H%M%S';
			}
			return $basename.'-'.dol_print_date(dol_now(), $format).'.'.$extension;
		}

		return $basename.'.'.$extension;
	}

	/**
	 *	Return the name of the generated file without date and extension: <ref>_<template name without template_/modele_ prefix>
	 *
	 *	@param	string	$objectref		Sanitized object ref
	 *	@param	string	$templateName	Template file name
	 *	@return	string					File base name
	 */
	protected function buildOdtFileBaseName(string $objectref, string $templateName): string
	{
		$basename = preg_replace('/\.(odt|ods)/i', '', $templateName);
		$basename = preg_replace('/template_/i', '', $basename);
		$basename = preg_replace('/modele_/i', '', $basename);

		return $objectref.'_'.$basename;
	}

	/**
	 *	Create the temporary directory and check it is writable.
	 *
	 *	@param	CommonObject	$object		Source object
	 *	@return	string|null					Temporary directory, or null on error ($this->error is set)
	 */
	protected function prepareOdtTempDir(CommonObject $object): ?string
	{
		global $langs;

		$tempDir = (string) $this->getOdtTempDir($object);
		if ($tempDir !== '') {
			dol_mkdir($tempDir);
		}
		if ($tempDir === '' || !is_writable($tempDir)) {
			$this->error = $langs->transnoentities("ErrorFailedToWriteInTempDirectory", $tempDir);
			dol_syslog('Error in write_file: '.$this->error, LOG_ERR);
			return null;
		}

		return $tempDir;
	}

	/**
	 *	Return the recipient thirdparty and, when an external contact is defined, the contact.
	 *
	 *	@param	CommonObject	$object		Source object, with thirdparty loaded when it has one
	 *	@return	array{0:Societe|null,1:Contact|null}	[thirdparty or null, contact or null]
	 */
	protected function resolveOdtRecipient(CommonObject $object): array
	{
		$thirdparty = (isset($object->thirdparty) && is_object($object->thirdparty)) ? $object->thirdparty : null;

		$contactCode = $this->odtExternalContactCode;
		if ($contactCode === '') {
			return [$thirdparty, null];
		}

		$arrayidcontact = $object->getIdContact('external', $contactCode);
		if (!is_array($arrayidcontact) || count($arrayidcontact) == 0) {
			return [$thirdparty, null];
		}

		$object->fetch_contact($arrayidcontact[0]);
		if (!is_object($object->contact)) {
			return [$thirdparty, null];
		}

		if ($thirdparty !== null && $object->contact->socid != $thirdparty->id && getDolGlobalString('MAIN_USE_COMPANY_NAME_OF_CONTACT', '1')) {
			$object->contact->fetch_thirdparty();
			return [$object->contact->thirdparty, $object->contact];
		}

		return [$thirdparty, $object->contact];
	}

	/**
	 *	Return the __KEY__ substitution array used by the free text, after the first ODTSubstitution hook.
	 *
	 *	@param	CommonObject	$object			Source object
	 *	@param	string			$file			Full path of the file to generate
	 *	@param	Translate		$outputlangs	Output language
	 *	@return	array<string,mixed>				Substitution array
	 */
	protected function getOdtPreSubstitutionArray(CommonObject $object, string $file, Translate $outputlangs): array
	{
		global $langs, $hookmanager, $action;

		$substitutionarray = [
			'__FROM_NAME__' => $this->emetteur->name,
			'__FROM_EMAIL__' => $this->emetteur->email,
			'__TOTAL_TTC__' => $object->total_ttc ?? '',
			'__TOTAL_HT__' => $object->total_ht ?? '',
			'__TOTAL_VAT__' => $object->total_tva ?? ''
		];
		complete_substitutions_array($substitutionarray, $langs, $object);

		$parameters = ['file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$substitutionarray];
		$hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action);

		return $substitutionarray;
	}

	/**
	 *	Return the free text of the document, with its __KEY__ substitutions done.
	 *
	 *	@param	array<string,mixed>	$substitutionarray	__KEY__ substitution array
	 *	@return	string									Free text, '' when not configured
	 */
	protected function buildOdtFreeText(array $substitutionarray): string
	{
		$freeText = getDolGlobalString($this->odtFreeTextConst);

		return $freeText === '' ? '' : make_substitutions($freeText, $substitutionarray);
	}

	/**
	 *	Open the template with the ODT library.
	 *
	 *	@param	string	$srctemplatepath	Full path of the template
	 *	@param	string	$tempDir			Temporary directory
	 *	@return	Odf|null					ODT handler, or null on error ($this->error is set)
	 */
	protected function openOdtTemplate(string $srctemplatepath, string $tempDir): ?Odf
	{
		require_once ODTPHP_PATH.'odf.php';

		try {
			return new Odf(
				$srctemplatepath,
				[
					'PATH_TO_TMP'	  => $tempDir,
					'ZIP_PROXY'		  => getDolGlobalString('MAIN_ODF_ZIP_PROXY', 'PclZipProxy'), // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
					'DELIMITER_LEFT'  => '{',
					'DELIMITER_RIGHT' => '}'
				]
			);
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			dol_syslog($e->getMessage(), LOG_INFO);
			return null;
		}
	}

	/**
	 *	Return the substitution array of the document (company, thirdparty, contact, object, user, other).
	 *
	 *	@param	CommonObject	$object			Source object
	 *	@param	Translate		$outputlangs	Output language
	 *	@param	Societe|null	$socobject		Recipient thirdparty
	 *	@param	Contact|null	$contactobject	Recipient contact
	 *	@return	array<string,mixed>				Substitution array
	 */
	protected function buildOdtSubstitutionArray(CommonObject $object, Translate $outputlangs, $socobject, ?Contact $contactobject): array
	{
		global $user, $mysoc;

		$tmparray = array_merge(
			getCommonSubstitutionArray($outputlangs, 0, null, $object),
			$this->get_substitutionarray_each_var_object($object, $outputlangs),
			$this->get_substitutionarray_user($user, $outputlangs),
			$this->get_substitutionarray_mysoc($mysoc, $outputlangs),
			is_object($socobject) ? $this->get_substitutionarray_thirdparty($socobject, $outputlangs) : [],
			$this->get_substitutionarray_object($object, $outputlangs),
			$this->getOdtExtraSubstitutionArray($object, $outputlangs),
			$this->get_substitutionarray_other($outputlangs),
			$contactobject !== null ? $this->get_substitutionarray_contact($contactobject, $outputlangs, 'contact') : []
		);
		complete_substitutions_array($tmparray, $outputlangs, $object);
		$this->completeOdtSubstitutionArray($tmparray, $object, $outputlangs);

		return $tmparray;
	}

	/**
	 *	Replace the tags of the template by the values of the substitution array.
	 *
	 *	@param	Odf					$odfHandler		ODT handler
	 *	@param	array<string,mixed>	$tmparray		Substitution array
	 *	@param	bool				$withImages		True to insert keys ending with "logo" as images
	 *	@return	void
	 */
	protected function setOdtVars(Odf $odfHandler, array $tmparray, bool $withImages): void
	{
		$ratio = getDolGlobalString('MAIN_DOC_ODT_IMAGE_RATIO') ? (float) getDolGlobalString('MAIN_DOC_ODT_IMAGE_RATIO') : 1;

		foreach ($tmparray as $key => $value) {
			try {
				if ($withImages && preg_match('/logo$/', $key)) {
					if (file_exists($value)) {
						$odfHandler->setImage($key, $value, $ratio);
					} else {
						$odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
					}
				} else {
					$odfHandler->setVars($key, $value, true, 'UTF-8');
				}
			} catch (OdfException $e) {
				dol_syslog($e->getMessage(), LOG_INFO);
			}
		}
	}

	/**
	 *	Fill the repeated segments of the template. Override to fill other segments than "lines".
	 *
	 *	@param	Odf				$odfHandler		ODT handler
	 *	@param	CommonObject	$object			Source object
	 *	@param	Translate		$outputlangs	Output language
	 *	@param	string			$file			Full path of the file to generate
	 *	@return	bool							False on error ($this->error is set)
	 */
	protected function setOdtSegments(Odf $odfHandler, CommonObject $object, Translate $outputlangs, string $file): bool
	{
		return $this->setOdtLines($odfHandler, $object, $outputlangs, $file);
	}

	/**
	 *	Fill the "lines" segment of the template with the lines of the object, if the segment is present.
	 *
	 *	@param	Odf				$odfHandler		ODT handler
	 *	@param	CommonObject	$object			Source object
	 *	@param	Translate		$outputlangs	Output language
	 *	@param	string			$file			Full path of the file to generate
	 *	@return	bool							False on error ($this->error is set)
	 */
	protected function setOdtLines(Odf $odfHandler, CommonObject $object, Translate $outputlangs, string $file): bool
	{
		global $hookmanager, $action;

		try {
			$listlines = $odfHandler->setSegment('lines');
		} catch (OdfExceptionSegmentNotFound $e) {
			dol_syslog($e->getMessage(), LOG_INFO);
			return true;
		}

		$linenumber = 0;
		foreach ((is_array($object->lines) ? $object->lines : []) as $line) {
			/** @var CommonObjectLine $line */
			$linenumber++;
			$tmparray = $this->get_substitutionarray_lines($line, $outputlangs, $linenumber);
			complete_substitutions_array($tmparray, $outputlangs, $object, $line, "completesubstitutionarray_lines");

			$parameters = ['odfHandler' => &$odfHandler, 'file' => $file, 'object' => $object, 'outputlangs' => $outputlangs, 'substitutionarray' => &$tmparray, 'line' => $line];
			$hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action);

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
			return false;
		}

		return true;
	}

	/**
	 *	Save the document as ODT/ODS, or as PDF when MAIN_ODT_AS_PDF is set.
	 *
	 *	@param	Odf		$odfHandler		ODT handler
	 *	@param	string	$file			Full path of the file to generate
	 *	@return	bool					False on error ($this->error is set)
	 */
	protected function saveOdtFile(Odf $odfHandler, string $file): bool
	{
		try {
			if (getDolGlobalString('MAIN_ODT_AS_PDF')) {
				$odfHandler->exportAsAttachedPDF($file);
			} else {
				$odfHandler->saveToDisk($file);
			}
		} catch (Exception $e) {
			$this->error = $e->getMessage();
			dol_syslog($e->getMessage(), LOG_INFO);
			return false;
		}

		return true;
	}
}
