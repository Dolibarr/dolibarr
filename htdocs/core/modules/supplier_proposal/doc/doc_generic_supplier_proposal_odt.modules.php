<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
 * Copyright (C) 2018       Frédéric France     <frederic.france@netlogic.fr>
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
 *	\file       htdocs/core/modules/propale/doc/doc_generic_proposal_odt.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build ODT documents for third parties
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/supplier_proposal/modules_supplier_proposal.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/company.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/doc.lib.php';


/**
 *	Class to build documents using ODF templates generator
 */
class doc_generic_supplier_proposal_odt extends ModelePDFSupplierProposal
{
	/**
	 * Issuer
	 * @var Societe
	 */
	public $emetteur;

	/**
	 * @var array Minimum version of PHP required by module.
	 * e.g.: PHP ≥ 5.6 = array(5, 6)
	 */
	public $phpmin = array(5, 6);

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
		global $conf, $langs, $mysoc;

		// Load translation files required by the page
		$langs->loadLangs(array("main", "companies"));

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'SUPPLIER_PROPOSAL_ADDON_PDF_ODT_PATH'; // Name of constant that is used to save list of directories to scan

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
		$this->option_tva = 0; // Manage the vat option PROPALE_TVAOPTION
		$this->option_modereg = 0; // Display payment mode
		$this->option_condreg = 0; // Display payment terms
		$this->option_codeproduitservice = 0; // Display product-service code
		$this->option_multilang = 1; // Available in several languages
		$this->option_escompte = 0; // Displays if there has been a discount
		$this->option_credit_note = 0; // Support credit notes
		$this->option_freetext = 1; // Support add of a personalised text
		$this->option_draft_watermark = 0; // Support add of a watermark on drafts

		// Recupere emetteur
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
		$langs->loadLangs(array('companies', 'errors'));

		$form = new Form($this->db);

		$texte = $this->description.".<br>\n";
		$texte .= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte .= '<input type="hidden" name="token" value="'.newToken().'">';
		$texte .= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte .= '<input type="hidden" name="param1" value="SUPPLIER_PROPOSAL_ADDON_PDF_ODT_PATH">';
		if ($conf->global->MAIN_SUPPLIER_PROPOSAL_CHOOSE_ODT_DOCUMENT > 0) {
			$texte .= '<input type="hidden" name="param2" value="SUPPLIER_PROPOSAL_ADDON_PDF_ODT_DEFAULT">';
			$texte .= '<input type="hidden" name="param3" value="SUPPLIER_PROPOSAL_ADDON_PDF_ODT_TOBILL">';
			$texte .= '<input type="hidden" name="param4" value="SUPPLIER_PROPOSAL_ADDON_PDF_ODT_CLOSED">';
		}
		$texte .= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte .= '<tr><td>';
		$texttitle = $langs->trans("ListOfDirectories");
		$listofdir = explode(',', preg_replace('/[\r\n]+/', ',', trim($conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_PATH)));
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
		// Add list of substitution keys
		$texthelp .= '<br>'.$langs->trans("FollowingSubstitutionKeysCanBeUsed").'<br>';
		$texthelp .= $langs->transnoentitiesnoconv("FullListOnOnlineDocumentation"); // This contains an url, we don't modify it

		$texte .= $form->textwithpicto($texttitle, $texthelp, 1, 'help', '', 1);
		$texte .= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$texte .= '<textarea class="flat" cols="60" name="value1">';
		$texte .= $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_PATH;
		$texte .= '</textarea>';
		$texte .= '</div><div style="display: inline-block; vertical-align: middle;">';
		$texte .= '<input type="submit" class="button small" value="'.$langs->trans("Modify").'" name="Button">';
		$texte .= '<br></div></div>';

		// Scan directories
		$nbofiles = count($listoffiles);
		if (!empty($conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_PATH)) {
			$texte .= $langs->trans("NumberOfModelFilesFound").': <b>';
			//$texte.=$nbofiles?'<a id="a_'.get_class($this).'" href="#">':'';
			$texte .= count($listoffiles);
			//$texte.=$nbofiles?'</a>':'';
			$texte .= '</b>';
		}

		if ($nbofiles) {
			$texte .= '<div id="div_'.get_class($this).'" class="hiddenx">';
			// Show list of found files
			foreach ($listoffiles as $file) {
				$texte .= '- '.$file['name'].' <a href="'.DOL_URL_ROOT.'/document.php?modulepart=doctemplates&file=supplier_proposal/'.urlencode(basename($file['name'])).'">'.img_picto('', 'listlight').'</a><br>';
			}
			$texte .= '</div>';

			if ($conf->global->MAIN_SUPPLIER_PROPOSAL_CHOOSE_ODT_DOCUMENT > 0) {
				// Model for creation
				$list = ModelePDFSupplierProposal::liste_modeles($this->db);
				$texte .= '<table width="50%;">';
				$texte .= '<tr>';
				$texte .= '<td width="60%;">'.$langs->trans("DefaultModelSupplierProposalCreate").'</td>';
				$texte .= '<td colspan="">';
				$texte .= $form->selectarray('value2', $list, $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_DEFAULT);
				$texte .= "</td></tr>";

				$texte .= '<tr>';
				$texte .= '<td width="60%;">'.$langs->trans("DefaultModelSupplierProposalToBill").'</td>';
				$texte .= '<td colspan="">';
				$texte .= $form->selectarray('value3', $list, $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_TOBILL);
				$texte .= "</td></tr>";
				$texte .= '<tr>';

				$texte .= '<td width="60%;">'.$langs->trans("DefaultModelSupplierProposalClosed").'</td>';
				$texte .= '<td colspan="">';
				$texte .= $form->selectarray('value4', $list, $conf->global->SUPPLIER_PROPOSAL_ADDON_PDF_ODT_CLOSED);
				$texte .= "</td></tr>";
				$texte .= '</table>';
			}
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
	 *	@param		Propal		$object				Object source to build document
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

		// Load translation files required by the page
		$outputlangs->loadLangs(array("main", "companies", "bills", "dict"));

		if ($conf->supplier_proposal->dir_output) {
			// If $object is id instead of object
			if (!is_object($object)) {
				$id = $object;
				$object = new SupplierProposal($this->db);
				$result = $object->fetch($id);
				if ($result < 0) {
					dol_print_error($this->db, $object->error);
					return -1;
				}
			}

			$object->fetch_thirdparty();

			$dir = $conf->supplier_proposal->dir_output;
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
				$newfiletmp = preg_replace('/\.od(t|s)/i', '', $newfile);
				$newfiletmp = preg_replace('/template_/i', '', $newfiletmp);
				$newfiletmp = preg_replace('/modele_/i', '', $newfiletmp);

				$newfiletmp = $objectref.'_'.$newfiletmp;

				// Get extension (ods or odt)
				$newfileformat = substr($newfile, strrpos($newfile, '.') + 1);
				if (!empty($conf->global->MAIN_DOC_USE_TIMING)) {
					$format = $conf->global->MAIN_DOC_USE_TIMING;
					if ($format == '1') {
						$format = '%Y%m%d%H%M%S';
					}
					$filename = $newfiletmp.'-'.dol_print_date(dol_now(), $format).'.'.$newfileformat;
				} else {
					$filename = $newfiletmp.'.'.$newfileformat;
				}
				$file = $dir.'/'.$filename;
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->propal->dir_temp=".$conf->propal->dir_temp;

				dol_mkdir($conf->supplier_proposal->dir_temp);
				if (!is_writable($conf->supplier_proposal->dir_temp)) {
					$this->error = "Failed to write in temp directory ".$conf->supplier_proposal->dir_temp;
					dol_syslog('Error in write_file: '.$this->error, LOG_ERR);
					return -1;
				}

				// If BILLING contact defined on invoice, we use it
				$usecontact = false;
				$arrayidcontact = $object->getIdContact('external', 'BILLING');
				if (count($arrayidcontact) > 0) {
					$usecontact = true;
					$result = $object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				$contactobject = null;
				if (!empty($usecontact)) {
					// We can use the company of contact instead of thirdparty company
					if ($object->contact->socid != $object->thirdparty->id && (!isset($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT) || !empty($conf->global->MAIN_USE_COMPANY_NAME_OF_CONTACT))) {
						$object->contact->fetch_thirdparty();
						$socobject = $object->contact->thirdparty;
						$contactobject = $object->contact;
					} else {
						$socobject = $object->thirdparty;
						// if we have a CUSTOMER contact and we dont use it as thirdparty recipient we store the contact object for later use
						$contactobject = $object->contact;
					}
				} else {
					$socobject = $object->thirdparty;
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
				$parameters = array('file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$substitutionarray);
				$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				// Line of free text
				$newfreetext = '';
				$paramfreetext = 'SUPPLIER_PROPOSAL_FREE_TEXT';
				if (!empty($conf->global->$paramfreetext)) {
					$newfreetext = make_substitutions($conf->global->$paramfreetext, $substitutionarray);
				}

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				try {
					$odfHandler = new odf(
						$srctemplatepath,
						array(
							'PATH_TO_TMP'	  => $conf->supplier_proposal->dir_temp,
							'ZIP_PROXY'		  => 'PclZipProxy', // PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
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

				// Define substitution array
				$substitutionarray = getCommonSubstitutionArray($outputlangs, 0, null, $object);
				$array_objet = $this->get_substitutionarray_object($object, $outputlangs);
				$array_user = $this->get_substitutionarray_user($user, $outputlangs);
				$array_soc = $this->get_substitutionarray_mysoc($mysoc, $outputlangs);
				$array_thirdparty = $this->get_substitutionarray_thirdparty($socobject, $outputlangs);
				$array_other = $this->get_substitutionarray_other($outputlangs);

				$array_thirdparty_contact = array();
				if ($usecontact && is_object($contactobject)) {
					$array_thirdparty_contact = $this->get_substitutionarray_contact($contactobject, $outputlangs, 'contact');
				}

				$tmparray = array_merge($substitutionarray, $array_user, $array_soc, $array_thirdparty, $array_objet, $array_other, $array_thirdparty_contact);
				complete_substitutions_array($tmparray, $outputlangs, $object);

				// Call the ODTSubstitution hook
				$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
				$reshook = $hookmanager->executeHooks('ODTSubstitution', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				foreach ($tmparray as $key => $value) {
					try {
						if (preg_match('/logo$/', $key)) { // Image
							if (file_exists($value)) {
								$odfHandler->setImage($key, $value);
							} else {
								$odfHandler->setVars($key, 'ErrorFileNotFound', true, 'UTF-8');
							}
						} else // Text
						{
							$odfHandler->setVars($key, $value, true, 'UTF-8');
						}
					} catch (OdfException $e) {
						dol_syslog($e->getMessage(), LOG_INFO);
					}
				}
				// Replace tags of lines
				try {
					$foundtagforlines = 1;
					try {
						$listlines = $odfHandler->setSegment('lines');
					} catch (OdfException $e) {
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
							$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray, 'line'=>$line);
							$reshook = $hookmanager->executeHooks('ODTSubstitutionLine', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks
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
					}
				} catch (OdfException $e) {
					$this->error = $e->getMessage();
					dol_syslog($this->error, LOG_WARNING);
					return -1;
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
				} else {
					try {
						$odfHandler->saveToDisk($file);
					} catch (Exception $e) {
						$this->error = $e->getMessage();
						dol_syslog($e->getMessage(), LOG_INFO);
						return -1;
					}
				}
				$parameters = array('odfHandler'=>&$odfHandler, 'file'=>$file, 'object'=>$object, 'outputlangs'=>$outputlangs, 'substitutionarray'=>&$tmparray);
				$reshook = $hookmanager->executeHooks('afterODTCreation', $parameters, $this, $action); // Note that $action and $object may have been modified by some hooks

				if (!empty($conf->global->MAIN_UMASK)) {
					@chmod($file, octdec($conf->global->MAIN_UMASK));
				}

				$odfHandler = null; // Destroy object

				$this->result = array('fullpath'=>$file);

				return 1; // Success
			} else {
				$this->error = $langs->transnoentities("ErrorCanNotCreateDir", $dir);
				return -1;
			}
		}

		return -1;
	}
}
