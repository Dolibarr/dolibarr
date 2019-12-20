<?php
/* Copyright (C) 2010-2011 Laurent Destailleur <ely@users.sourceforge.net>
 * Copyright (C) 2016	   Charlie Benke	   <charlie@patas-monkey.com>
 * Copyright (C) 2018      Frédéric France     <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/modules/societe/doc/doc_generic_odt.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build ODT documents for third parties
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/societe/modules_societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/societe/class/societe.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class doc_generic_odt extends ModeleThirdPartyDoc
{
	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
     * @var array Minimum version of PHP required by module.
     * e.g.: PHP ≥ 5.5 = array(5, 5)
     */
	public $phpmin = array(5, 5);


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
		$this->scandir = 'COMPANY_ADDON_PDF_ODT_PATH'; // Name of constant that is used to save list of directories to scan

		// Page size for A4 format
		$this->type = 'odt';
		$this->page_largeur = 0;
		$this->page_hauteur = 0;
		$this->format = array($this->page_largeur, $this->page_hauteur);
		$this->marge_gauche = 0;
		$this->marge_droite = 0;
		$this->marge_haute = 0;
		$this->marge_basse = 0;

		$this->option_logo = 1; // Affiche logo

		// Retrieves transmitter
		$this->emetteur = $mysoc;
		if (!$this->emetteur->country_code) $this->emetteur->country_code = substr($langs->defaultlang, -2); // Par defaut, si n'etait pas defini
	}


	/**
	 * Return description of a module
	 *
	 * @param	Translate	$langs		Object language
	 * @return	string      			Description
	 */
	public function info($langs)
	{
		global $conf, $langs;

		// Load traductions files required by page
		$langs->loadLangs(array("companies", "errors"));

		$form = new Form($this->db);

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="COMPANY_ADDON_PDF_ODT_PATH">';
		$texte .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->COMPANY_ADDON_PDF_ODT_PATH)));
		$listoffiles = array();
		foreach ($listofdir as $key=>$tmpdir)
		{
			$tmpdir = trim($tmpdir);
			$tmpdir = preg_replace('/DOL_DATA_ROOT/', DOL_DATA_ROOT, $tmpdir);
			if (!$tmpdir) { unset($listofdir[$key]); continue; }
			if (!is_dir($tmpdir)) $texttitle .= img_warning($langs->trans("ErrorDirNotFound", $tmpdir), 0);
			else
			{
				$tmpfiles = dol_dir_list($tmpdir, 'files', 0, '\.od(s|t)$', '', 'name', SORT_ASC, 0, true); // Disable hook for the moment
				if (count($tmpfiles)) $listoffiles = array_merge($listoffiles, $tmpfiles);
			}
		}
		$texthelp = $langs->trans("ListOfDirectoriesForModelGenODT");
		// Add list of substitution keys
		$texthelp .= '<br>'.$langs->trans("FollowingSubstitutionKeysCanBeUsed").'<br>';
		$texthelp .= $langs->transnoentitiesnoconv("FullListOnOnlineDocumentation"); // This contains an url, we don't modify it

		$texte .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1);
        $texte .= '<table><tr><td>';
		$texte .= '<textarea class="flat" cols="60" name="value1">';
		$texte .= $conf->global->COMPANY_ADDON_PDF_ODT_PATH;
		$texte .= '</textarea>';
        $texte .= '</td>';
        $texte .= '<td class="center">&nbsp; ';
        $texte .= '<input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button">';
        $texte .= '</td>';
        $texte .= '</tr>';
        $texte .= '</table>';

		// Scan directories
		$nbofiles = count($listoffiles);
		if (!empty($conf->global->COMPANY_ADDON_PDF_ODT_PATH))
		{
			$texte .= $langs->trans("NumberOfModelFilesFound").': <b>';
			//$texte.=$nbofiles?'<a id="a_'.get_class($this).'" href="#">':'';
			$texte .= $nbofiles;
			//$texte.=$nbofiles?'</a>':'';
			$texte .= '</b>';
		}

		if ($nbofiles)
		{
   			$texte .= '<div id="div_'.get_class($this).'" class="hidden">';
   			foreach ($listoffiles as $file)
   			{
                $texte .= $file['name'].'<br>';
   			}
   			$texte .= '</div>';
		}

		$texte .= '</td>';

		$texte .= '<td rowspan="2" class="tdtop hideonsmartphone">';
		$texte .= $langs->trans("ExampleOfDirectoriesForModelGen");
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
	 *	@param		Societe		$object				Object source to build document
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
        global $user, $langs, $conf, $mysoc, $hookmanager;

		if (empty($srctemplatepath))
		{
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

		if (!is_object($outputlangs)) $outputlangs = $langs;
		$sav_charset_output = $outputlangs->charset_output;
		$outputlangs->charset_output = 'UTF-8';

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "dict", "companies", "projects"));

		if ($conf->societe->multidir_output[$object->entity])
		{
			$dir = $conf->societe->multidir_output[$object->entity];
			$objectref = dol_sanitizeFileName($object->id);
			if (!preg_match('/specimen/i', $objectref)) $dir .= "/".$objectref;

			if (!file_exists($dir))
			{
				if (dol_mkdir($dir) < 0)
				{
					$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
					return -1;
				}
			}

			if (file_exists($dir))
			{
				//print "srctemplatepath=".$srctemplatepath;	// Src filename
				$newfile = basename($srctemplatepath);
				$newfiletmp = preg_replace('/\.od(s|t)/i', '', $newfile);
				$newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
				$newfiletmp = preg_replace('/modele_/i', '', $newfiletmp);
				// Get extension (ods or odt)
				$newfileformat = substr($newfile, strrpos($newfile, '.') + 1);
				if (!empty($conf->global->MAIN_DOC_USE_OBJECT_THIRDPARTY_NAME))
				{
				    $newfiletmp = dol_sanitizeFileName(dol_string_nospecial($object->name)).'-'.$newfiletmp;
				}
				if (!empty($conf->global->MAIN_DOC_USE_TIMING))
				{
				    $format = $conf->global->MAIN_DOC_USE_TIMING;
				    if ($format == '1') $format = '%Y%m%d%H%M%S';
					$filename = $newfiletmp.'-'.dol_print_date(dol_now(), $format).'.'.$newfileformat;
				}
				else
				{
					$filename = $newfiletmp.'.'.$newfileformat;
				}
				$file = $dir.'/'.$filename;
				$object->builddoc_filename = $filename; // For triggers
				//print "newfileformat=".$newfileformat;
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;
				//exit;

				dol_mkdir($conf->societe->multidir_temp[$object->entity]);

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				try {
                    $odfHandler = new odf(
					    $srctemplatepath,
					    array(
	    					'PATH_TO_TMP'	  => $conf->societe->multidir_temp[$object->entity],
	    					'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
	    					'DELIMITER_LEFT'  => '{',
	    					'DELIMITER_RIGHT' => '}'
						)
					);
				}
				catch (Exception $e)
				{
					$this->error = $e->getMessage();
					dol_syslog($e->getMessage(), LOG_INFO);
					return -1;
				}
				//print $odfHandler->__toString()."\n";

                // Replace tags of lines for contacts
                $contact_arrray = array();

                $sql = "SELECT p.rowid";
                $sql .= " FROM ".MAIN_DB_PREFIX."socpeople as p";
                $sql .= " WHERE p.fk_soc = ".$object->id;

                $result = $this->db->query($sql);
                $num = $this->db->num_rows($result);

                if ($num)
                {
                    require_once DOL_DOCUMENT_ROOT.'/contact/class/contact.class.php';

                	$i = 0;
                	$contactstatic = new Contact($this->db);

                	while ($i < $num)
                	{
                		$obj = $this->db->fetch_object($result);

                		$contact_arrray[$i] = $obj->rowid;
                		$i++;
                	}
                }
                if ((is_array($contact_arrray) && count($contact_arrray) > 0))
                {
                	try
                	{
                		$listlines = $odfHandler->setSegment('companycontacts');

                		foreach ($contact_arrray as $array_key => $contact_id)
                		{
                			$res_contact = $contactstatic->fetch($contact_id);
                			$tmparray = $this->get_substitutionarray_contact($contactstatic, $outputlangs, 'contact');
                			foreach ($tmparray as $key => $val)
                			{
                				try
                				{
                					$listlines->setVars($key, $val, true, 'UTF-8');
                				}
                				catch (OdfException $e)
                				{
									dol_syslog($e->getMessage(), LOG_INFO);
                				}
                				catch (SegmentException $e)
                				{
									dol_syslog($e->getMessage(), LOG_INFO);
                				}
                			}
                			$listlines->merge();
                		}
                		$odfHandler->mergeSegment($listlines);
                	}
                	catch (OdfException $e)
                	{
                		$this->error = $e->getMessage();
                		dol_syslog($this->error, LOG_WARNING);
                		//return -1;
                	}
                }

                // Make substitutions into odt
                $array_user = $this->get_substitutionarray_user($user, $outputlangs);
                $array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
                $array_thirdparty = $this->get_substitutionarray_thirdparty($object, $outputlangs);
                $array_other = $this->get_substitutionarray_other($outputlangs);

                $tmparray = array_merge($array_user, $array_soc, $array_thirdparty, $array_other);
                complete_substitutions_array($tmparray, $outputlangs, $object);

                // Call the ODTSubstitution hook
                $parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
				$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

                // Replace variables into document
				foreach ($tmparray as $key=>$value)
				{
					try {
						if (preg_match('/logo$/', $key))	// Image
						{
							if (file_exists($value)) $odfHandler->setImage($key, $value);
							else $odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
						}
						else	// Text
						{
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					}
					catch (OdfException $e)
					{
						// setVars failed, probably because key not found
                        dol_syslog($e->getMessage(), LOG_INFO);
					}
				}

				// Replace labels translated
				$tmparray = $outputlangs->get_translations_for_substitutions();
				foreach ($tmparray as $key=>$value)
				{
					try {
						$odfHandler->setVars($key, $value, true, 'UTF-8');
					}
					catch (OdfException $e)
					{
                        dol_syslog($e->getMessage(), LOG_INFO);
					}
				}

				// Call the beforeODTSave hook
				$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
				$reshook = $hookmanager->executeHooks('beforeODTSave', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				// Write new file
				if (!empty($conf->global->MAIN_ODT_AS_PDF)) {
					try {
						$odfHandler->exportAsAttachedPDF($file);
					} catch (Exception $e) {
						$this->error = $e->getMessage();
                        dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				}
				else {
				    try {
                        $odfHandler->creator = $user->getFullName($outputlangs);
                        $odfHandler->title = $object->builddoc_filename;
                        $odfHandler->subject = $object->builddoc_filename;

                        if (!empty($conf->global->ODT_ADD_DOLIBARR_ID)) {
                            $odfHandler->userdefined['dol_id'] = $object->id;
                            $odfHandler->userdefined['dol_element'] = $object->element;
                        }

                        $odfHandler->saveToDisk($file);
					} catch (Exception $e) {
						$this->error = $e->getMessage();
                        dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				}
				$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
				$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				if (!empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				$odfHandler = null; // Destroy object

				$this->result = array('fullpath'=>$file);

				return 1; // Success
			}
			else
			{
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		$this->error = 'UnknownError';
		return -1;
	}
}
