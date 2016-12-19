<?php
/* Copyright (C) 2010-2012 	Laurent Destailleur <eldy@users.sourceforge.net>
 * Copyright (C) 2012		Juanjo Menent		<jmenent@2byte.es>
 * Copyright (C) 2013		Florian Henry		<florian.henry@ope-concept.pro>
 * Copyright (C) 2016		Charlie Benke		<charlie@patas-monkey.com>
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
 *	\file       htdocs/core/modules/project/doc/doc_generic_project_odt.modules.php
 *	\ingroup    project
 *	\brief      File of class to build ODT documents for third parties
 */

require_once DOL_DOCUMENT_ROOT.'/core/modules/project/modules_project.php';
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
if (! empty($conf->propal->enabled))      require_once DOL_DOCUMENT_ROOT.'/comm/propal/class/propal.class.php';
if (! empty($conf->facture->enabled))     require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture.class.php';
if (! empty($conf->facture->enabled))     require_once DOL_DOCUMENT_ROOT.'/compta/facture/class/facture-rec.class.php';
if (! empty($conf->commande->enabled))    require_once DOL_DOCUMENT_ROOT.'/commande/class/commande.class.php';
if (! empty($conf->fournisseur->enabled)) require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.facture.class.php';
if (! empty($conf->fournisseur->enabled)) require_once DOL_DOCUMENT_ROOT.'/fourn/class/fournisseur.commande.class.php';
if (! empty($conf->contrat->enabled))     require_once DOL_DOCUMENT_ROOT.'/contrat/class/contrat.class.php';
if (! empty($conf->ficheinter->enabled))  require_once DOL_DOCUMENT_ROOT.'/fichinter/class/fichinter.class.php';
if (! empty($conf->deplacement->enabled)) require_once DOL_DOCUMENT_ROOT.'/compta/deplacement/class/deplacement.class.php';
if (! empty($conf->agenda->enabled))      require_once DOL_DOCUMENT_ROOT.'/comm/action/class/actioncomm.class.php';


/**
 *	Class to build documents using ODF templates generator
 */
class doc_generic_project_odt extends ModelePDFProjects
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
		$this->scandir = 'PROJECT_ADDON_PDF_ODT_PATH';	// Name of constant that is used to save list of directories to scan

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
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini
	}


	/**
	 * Define array with couple substitution key => substitution value
	 *
	 * @param   Project			$object             Main object to use as data source
	 * @param   Translate		$outputlangs        Lang object to use for output
     * @param   string		    $array_key	        Name of the key for return array
	 * @return	array								Array of substitution
	 */
	function get_substitutionarray_object($object,$outputlangs,$array_key='object')
	{
		global $conf;

		$resarray=array(
		    $array_key.'_id'=>$object->id,
            $array_key.'_ref'=>$object->ref,
            $array_key.'_title'=>$object->title,
            $array_key.'_description'=>$object->description,
            $array_key.'_date_creation'=>dol_print_date($object->date_c,'day'),
            $array_key.'_date_modification'=>dol_print_date($object->date_m,'day'),
            $array_key.'_date_start'=>dol_print_date($object->date_start,'day'),
            $array_key.'_date_end'=>dol_print_date($object->date_end,'day'),
            $array_key.'_note_private'=>$object->note_private,
            $array_key.'_note_public'=>$object->note_public,
            $array_key.'_public'=>$object->public,
            $array_key.'_statut'=>$object->getLibStatut()
		);

		// Retrieve extrafields
		if (is_array($object->array_options) && count($object->array_options))
		{
			$extrafieldkey=$object->element;

			require_once DOL_DOCUMENT_ROOT.'/core/class/extrafields.class.php';
			$extrafields = new ExtraFields($this->db);
			$extralabels = $extrafields->fetch_name_optionals_label($extrafieldkey,true);
			$object->fetch_optionals($object->id,$extralabels);

			$resarray = $this->fill_substitutionarray_with_extrafields($object,$resarray,$extrafields,$array_key,$outputlangs);
		}

		return $resarray;
	}

	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$task				Task Object
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	function get_substitutionarray_tasks($task,$outputlangs)
	{
		global $conf;

		return array(
		'task_ref'=>$task->ref,
		'task_fk_project'=>$task->fk_project,
		'task_projectref'=>$task->projectref,
		'task_projectlabel'=>$task->projectlabel,
		'task_label'=>$task->label,
		'task_description'=>$task->description,
		'task_fk_parent'=>$task->fk_parent,
		'task_duration'=>$task->duration,
		'task_progress'=>$task->progress,
		'task_public'=>$task->public,
		'task_date_start'=>dol_print_date($task->date_start,'day'),
		'task_date_end'=>dol_print_date($task->date_end,'day'),
		'task_note_private'=>$task->note_private,
		'task_note_public'=>$task->note_public
		);
	}

	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$contact			Contact array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	function get_substitutionarray_project_contacts($contact,$outputlangs)
	{
		global $conf;

		return array(
		'projcontacts_id'=>$contact['id'],
		'projcontacts_rowid'=>$contact['rowid'],
		'projcontacts_role'=>$contact['libelle'],
		'projcontacts_lastname'=>$contact['lastname'],
		'projcontacts_firstname'=>$contact['firstname'],
		'projcontacts_fullcivname'=>$contact['fullname'],
		'projcontacts_socname'=>$contact['socname'],
		'projcontacts_email'=>$contact['email']
		);
	}

	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$file				file array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	function get_substitutionarray_project_file($file,$outputlangs)
	{
		global $conf;

		return array(
		'projfile_name'=>$file['name'],
		'projfile_date'=>dol_print_date($file['date'],'day'),
		'projfile_size'=>$file['size']
		);
	}

	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$refdetail			Reference array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	function get_substitutionarray_project_reference($refdetail,$outputlangs)
	{
		global $conf;

		return array(
		'projref_type'=>$refdetail['type'],
		'projref_ref'=>$refdetail['ref'],
		'projref_date'=>dol_print_date($refdetail['date'],'day'),
		'projref_socname'=>$refdetail['socname'],
		'projref_amountht'=>price($refdetail['amountht'],0,$outputlangs),
		'projref_amountttc'=>price($refdetail['amountttc'],0,$outputlangs),
		'projref_status'=>$refdetail['status']
		);
	}

	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$taskressource			Reference array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	function get_substitutionarray_tasksressource($taskressource,$outputlangs)
	{
		global $conf;
		//dol_syslog(get_class($this).'::get_substitutionarray_tasksressource taskressource='.var_export($taskressource,true),LOG_DEBUG);
		return array(
		'taskressource_rowid'=>$taskressource['rowid'],
		'taskressource_role'=>$taskressource['libelle'],
		'taskressource_lastname'=>$taskressource['lastname'],
		'taskressource_firstname'=>$taskressource['firstname'],
		'taskressource_fullcivname'=>$taskressource['fullname'],
		'taskressource_socname'=>$taskressource['socname'],
		'taskressource_email'=>$taskressource['email']
		);
	}

	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  object			$tasktime			times object
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	function get_substitutionarray_taskstime($tasktime,$outputlangs)
	{
		global $conf;

		return array(
		'tasktime_rowid'=>$tasktime['rowid'],
		'tasktime_task_date'=>dol_print_date($tasktime['task_date'],'day'),
		'tasktime_task_duration'=>convertSecondToTime($tasktime['task_duration'],'all'),
		'tasktime_note'=>$tasktime['note'],
		'tasktime_fk_user'=>$tasktime['fk_user'],
		'tasktime_user_name'=>$tasktime['name'],
		'tasktime_user_first'=>$tasktime['firstname'],
		'tasktime_fullcivname'=>$tasktime['fullcivname']
		);
	}

	/**
	 *	Define array with couple substitution key => substitution value
	 *
	 *	@param  array			$file				file array
	 *	@param  Translate		$outputlangs        Lang object to use for output
	 *  @return	array								Return a substitution array
	 */
	function get_substitutionarray_task_file($file,$outputlangs)
	{
		global $conf;

		return array(
		'tasksfile_name'=>$file['name'],
		'tasksfile_date'=>dol_print_date($file['date'],'day'),
		'tasksfile_size'=>$file['size']
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
		$texte.= '<input type="hidden" name="param1" value="PROJECT_ADDON_PDF_ODT_PATH">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte.= '<tr><td>';
		$texttitle=$langs->trans("ListOfDirectories");
		$listofdir=explode(',',preg_replace('/[\r\n]+/',',',trim($conf->global->PROJECT_ADDON_PDF_ODT_PATH)));
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
				$tmpfiles=dol_dir_list($tmpdir,'files',0,'\.(ods|odt)');
				if (count($tmpfiles)) $listoffiles=array_merge($listoffiles,$tmpfiles);
			}
		}
		$texthelp=$langs->trans("ListOfDirectoriesForModelGenODT");
		// Add list of substitution keys
		$texthelp.='<br>'.$langs->trans("FollowingSubstitutionKeysCanBeUsed").'<br>';
		$texthelp.=$langs->transnoentitiesnoconv("FullListOnOnlineDocumentation");    // This contains an url, we don't modify it

		$texte.= $form->textwithpicto($texttitle,$texthelp,1,'help','',1);
		$texte.= '<div><div style="display: inline-block; min-width: 100px; vertical-align: middle;">';
		$texte.= '<textarea class="flat" cols="60" name="value1">';
		$texte.=$conf->global->PROJECT_ADDON_PDF_ODT_PATH;
		$texte.= '</textarea>';
		$texte.= '</div><div style="display: inline-block; vertical-align: middle;">';
		$texte.= '<input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button">';
		$texte.= '<br></div></div>';

		// Scan directories
		$nbofiles=count($listoffiles);
		if (! empty($conf->global->PROJECT_ADDON_PDF_ODT_PATH))
		{
			$texte.=$langs->trans("NumberOfModelFilesFound").': <b>';
			//$texte.=$nbofiles?'<a id="a_'.get_class($this).'" href="#">':'';
			$texte.=$nbofiles;
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

	/**
	 *	Function to build a document on disk using the generic odt module.
	 *
	 *	@param	Project		$object					Object source to build document
	 *	@param	Translate	$outputlangs			Lang output object
	 * 	@param	string		$srctemplatepath	    Full path of source filename for generator using a template file
	 *	@return	int         						1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath)
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
		$outputlangs->load("projects");

		if ($conf->projet->dir_output)
		{
			// If $object is id instead of object
			if (! is_object($object))
			{
				$id = $object;
				$object = new Project($this->db);
				$result=$object->fetch($id);
				if ($result < 0)
				{
					dol_print_error($this->db,$object->error);
					return -1;
				}
			}

			$dir = $conf->projet->dir_output;
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
				    $format=$conf->global->MAIN_DOC_USE_TIMING;
				    if ($format == '1') $format='%Y%m%d%H%M%S';
					$filename=$newfiletmp.'-'.dol_print_date(dol_now(),$format).'.'.$newfileformat;
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

				dol_mkdir($conf->projet->dir_temp);

				// If PROJECTLEADER contact defined on project, we use it
				$usecontact=false;
				$arrayidcontact=$object->getIdContact('external','PROJECTLEADER');
				if (count($arrayidcontact) > 0)
				{
					$usecontact=true;
					$result=$object->fetch_contact($arrayidcontact[0]);
				}

				// Recipient name
				if (! empty($usecontact))
				{
        			// if we have a PROJECTLEADER contact and we dont use it as recipient we store the contact object for later use
        			$contactobject = $object->contact;
				}

				$socobject=$object->thirdparty;

				// Make substitution
				$substitutionarray=array(
				'__FROM_NAME__' => $this->emetteur->name,
				'__FROM_EMAIL__' => $this->emetteur->email,
				);
				complete_substitutions_array($substitutionarray, $langs, $object);
				// Call the ODTSubstitution hook
				$parameters=array('file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
				$reshook=$hookmanager->executeHooks('ODTSubstitution',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

				// Open and load template
				require_once ODTPHP_PATH.'odf.php';
				try {
					$odfHandler = new odf(
						$srctemplatepath,
						array(
						'PATH_TO_TMP'	  => $conf->projet->dir_temp,
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




				// Make substitutions into odt of user info
				$array_user=$this->get_substitutionarray_user($user,$outputlangs);
				$array_soc=$this->get_substitutionarray_mysoc($mysoc,$outputlangs);
				$array_thirdparty=$this->get_substitutionarray_thirdparty($socobject,$outputlangs);
				$array_objet=$this->get_substitutionarray_object($object,$outputlangs);
				$array_other=$this->get_substitutionarray_other($outputlangs);
                // retrieve contact information for use in project as contact_xxx tags
        		$array_project_contact = array();
        		if ($usecontact)
            			$array_project_contact=$this->get_substitutionarray_contact($contactobject,$outputlangs,'contact');

				$tmparray = array_merge($array_user,$array_soc,$array_thirdparty,$array_objet,$array_other,$array_project_contact);
				complete_substitutions_array($tmparray, $outputlangs, $object);
				// Call the ODTSubstitution hook
				$parameters=array('odfHandler'=>&$odfHandler,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
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

				// Replace tags of lines for tasks
				try
				{
					$listlines = $odfHandler->setSegment('tasks');

					$taskstatic = new Task($this->db);

					// Security check
					$socid=0;
					if (!empty($object->fk_soc)) $socid = $object->fk_soc;

					$tasksarray=$taskstatic->getTasksArray(0, 0, $object->id, $socid, 0);


					foreach ($tasksarray as $task)
					{
						$tmparray=$this->get_substitutionarray_tasks($task,$outputlangs);
						//complete_substitutions_array($tmparray, $outputlangs, $object, $task, "completesubstitutionarray_lines");
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

						$taskobj=new Task($this->db);
						$taskobj->fetch($task->id);

						// Replace tags of lines for contacts task
						$sourcearray=array('internal','external');
						$contact_arrray=array();
						foreach ($sourcearray as $source) {
							$contact_temp=$taskobj->liste_contact(-1,$source);
							if ((is_array($contact_temp) && count($contact_temp) > 0))
							{
								$contact_arrray=array_merge($contact_arrray,$contact_temp);
							}
						}
						if ((is_array($contact_arrray) && count($contact_arrray) > 0))
						{
							$listlinestaskres = $listlines->__get('tasksressources');

							foreach ($contact_arrray as $contact)
							{
								if ($contact['source']=='internal') {
									$objectdetail=new User($this->db);
									$objectdetail->fetch($contact['id']);
									$contact['socname']=$mysoc->name;
								} elseif ($contact['source']=='external') {
									$objectdetail=new Contact($this->db);
									$objectdetail->fetch($contact['id']);

									$soc=new Societe($this->db);
									$soc->fetch($contact['socid']);
									$contact['socname']=$soc->name;
								}
								$contact['fullname']=$objectdetail->getFullName($outputlangs,1);

								$tmparray=$this->get_substitutionarray_tasksressource($contact,$outputlangs);

								foreach($tmparray as $key => $val)
								{
									try
									{
										$listlinestaskres->setVars($key, $val, true, 'UTF-8');
									}
									catch(OdfException $e)
									{
									}
									catch(SegmentException $e)
									{
									}
								}
								$listlinestaskres->merge();
							}
						}

						//Time ressources
						$sql = "SELECT t.rowid, t.task_date, t.task_duration, t.fk_user, t.note";
						$sql.= ", u.lastname, u.firstname";
						$sql .= " FROM ".MAIN_DB_PREFIX."projet_task_time as t";
						$sql .= " , ".MAIN_DB_PREFIX."user as u";
						$sql .= " WHERE t.fk_task =".$task->id;
						$sql .= " AND t.fk_user = u.rowid";
						$sql .= " ORDER BY t.task_date DESC";

						$resql = $this->db->query($sql);
						if ($resql)
						{
							$num = $this->db->num_rows($resql);
							$i = 0;
							$tasks = array();
							$listlinestasktime = $listlines->__get('taskstimes');
							while ($i < $num)
							{
								$row = $this->db->fetch_array($resql);
								if (!empty($row['fk_user'])) {
									$objectdetail=new User($this->db);
									$objectdetail->fetch($row['fk_user']);
									$row['fullcivname']=$objectdetail->getFullName($outputlangs,1);
								} else {
									$row['fullcivname']='';
								}

								$tmparray=$this->get_substitutionarray_taskstime($row,$outputlangs);

								foreach($tmparray as $key => $val)
								{
									try
									{
										$listlinestasktime->setVars($key, $val, true, 'UTF-8');
									}
									catch(OdfException $e)
									{
									}
									catch(SegmentException $e)
									{
									}
								}
								$listlinestasktime->merge();
								$i++;
							}
							$this->db->free($resql);
						}


						// Replace tags of project files
						$listtasksfiles = $listlines->__get('tasksfiles');

						$upload_dir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($object->ref).'/'.dol_sanitizeFileName($task->ref);
						$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$','name',SORT_ASC,1);


						foreach ($filearray as $filedetail)
						{
							$tmparray=$this->get_substitutionarray_task_file($filedetail,$outputlangs);
							//dol_syslog(get_class($this).'::main $tmparray'.var_export($tmparray,true));
							foreach($tmparray as $key => $val)
							{
								try
								{
									$listtasksfiles->setVars($key, $val, true, 'UTF-8');
								}
								catch(OdfException $e)
								{
								}
								catch(SegmentException $e)
								{
								}
							}
							$listtasksfiles->merge();
						}
						$listlines->merge();
					}
					$odfHandler->mergeSegment($listlines);
				}
				catch(OdfException $e)
				{
					$ExceptionTrace=$e->getTrace();
					// no segment defined on ODT is not an error
					if($ExceptionTrace[0]['function'] != 'setSegment')
					{
						$this->error=$e->getMessage();
						dol_syslog($this->error, LOG_WARNING);
						return -1;
					}
				}

				// Replace tags of project files
				try
				{
					$listlines = $odfHandler->setSegment('projectfiles');

					$upload_dir = $conf->projet->dir_output.'/'.dol_sanitizeFileName($object->ref);
					$filearray=dol_dir_list($upload_dir,"files",0,'','(\.meta|_preview\.png)$','name',SORT_ASC,1);

					foreach ($filearray as $filedetail)
					{
						//dol_syslog(get_class($this).'::main $filedetail'.var_export($filedetail,true));
						$tmparray=$this->get_substitutionarray_project_file($filedetail,$outputlangs);

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

				// Replace tags of lines for contacts
				$sourcearray=array('internal','external');
				$contact_arrray=array();
				foreach ($sourcearray as $source) {
					$contact_temp=$object->liste_contact(-1,$source);
					if ((is_array($contact_temp) && count($contact_temp) > 0))
					{
						$contact_arrray=array_merge($contact_arrray,$contact_temp);
					}
				}
				if ((is_array($contact_arrray) && count($contact_arrray) > 0))
				{
					try
					{
						$listlines = $odfHandler->setSegment('projectcontacts');

						foreach ($contact_arrray as $contact)
						{
							if ($contact['source']=='internal') {
								$objectdetail=new User($this->db);
								$objectdetail->fetch($contact['id']);
								$contact['socname']=$mysoc->name;
							} elseif ($contact['source']=='external') {
								$objectdetail=new Contact($this->db);
								$objectdetail->fetch($contact['id']);

								$soc=new Societe($this->db);
								$soc->fetch($contact['socid']);
								$contact['socname']=$soc->name;
							}
							$contact['fullname']=$objectdetail->getFullName($outputlangs,1);

							$tmparray=$this->get_substitutionarray_project_contacts($contact,$outputlangs);
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
				}

				//List of referent

				$listofreferent=array(
				'propal'=>array(
				'title'=>"ListProposalsAssociatedProject",
				'class'=>'Propal',
				'table'=>'propal',
				'test'=>$conf->propal->enabled  && $user->rights->propale->lire),
				'order'=>array(
				'title'=>"ListOrdersAssociatedProject",
				'class'=>'Commande',
				'table'=>'commande',
				'test'=>$conf->commande->enabled  && $user->rights->commande->lire),
				'invoice'=>array(
				'title'=>"ListInvoicesAssociatedProject",
				'class'=>'Facture',
				'table'=>'facture',
				'test'=>$conf->facture->enabled && $user->rights->facture->lire),
				'invoice_predefined'=>array(
				'title'=>"ListPredefinedInvoicesAssociatedProject",
				'class'=>'FactureRec',
				'table'=>'facture_rec',
				'test'=>$conf->facture->enabled  && $user->rights->facture->lire),
				'order_supplier'=>array(
				'title'=>"ListSupplierOrdersAssociatedProject",
				'table'=>'commande_fournisseur',
				'class'=>'CommandeFournisseur',
				'test'=>$conf->fournisseur->enabled && $user->rights->fournisseur->commande->lire),
				'invoice_supplier'=>array(
				'title'=>"ListSupplierInvoicesAssociatedProject",
				'table'=>'facture_fourn',
				'class'=>'FactureFournisseur',
				'test'=>$conf->fournisseur->enabled  && $user->rights->fournisseur->facture->lire),
				'contract'=>array(
				'title'=>"ListContractAssociatedProject",
				'class'=>'Contrat',
				'table'=>'contrat',
				'test'=>$conf->contrat->enabled && $user->rights->contrat->lire),
				'intervention'=>array(
				'title'=>"ListFichinterAssociatedProject",
				'class'=>'Fichinter',
				'table'=>'fichinter',
				'disableamount'=>1,
				'test'=>$conf->ficheinter->enabled && $user->rights->ficheinter->lire),
				'trip'=>array(
				'title'=>"ListTripAssociatedProject",
				'class'=>'Deplacement',
				'table'=>'deplacement',
				'disableamount'=>1,
				'test'=>$conf->deplacement->enabled && $user->rights->deplacement->lire),
				'agenda'=>array(
				'title'=>"ListActionsAssociatedProject",
				'class'=>'ActionComm',
				'table'=>'actioncomm',
				'disableamount'=>1,
				'test'=>$conf->agenda->enabled && $user->rights->agenda->allactions->lire)
				);

				//Insert reference
				try
				{
					$listlines = $odfHandler->setSegment('projectrefs');

					foreach ($listofreferent as $keyref => $valueref)
					{
						$title=$valueref['title'];
						$tablename=$valueref['table'];
						$classname=$valueref['class'];
						$qualified=$valueref['test'];
						if ($qualified)
						{
							$elementarray = $object->get_element_list($keyref, $tablename);
							if (count($elementarray)>0 && is_array($elementarray))
							{
								$var=true;
								$total_ht = 0;
								$total_ttc = 0;
								$num=count($elementarray);
								for ($i = 0; $i < $num; $i++)
								{
									$ref_array=array();
									$ref_array['type']=$langs->trans($classname);

									$element = new $classname($this->db);
									$element->fetch($elementarray[$i]);
									$element->fetch_thirdparty();

									//Ref object
									$ref_array['ref']=$element->ref;

									//Date object
									$dateref=$element->date;
									if (empty($dateref)) $dateref=$element->datep;
									if (empty($dateref)) $dateref=$element->date_contrat;
									$ref_array['date']=$dateref;

									//Soc object
									if (is_object($element->thirdparty)) {
										$ref_array['socname']=$element->thirdparty->name;
									} else {
										$ref_array['socname']='';
									}

									//Amount object
									if (empty($valueref['disableamount'])) {
										if (!empty($element->total_ht)) {
											$ref_array['amountht']=$element->total_ht;
											$ref_array['amountttc']=$element->total_ttc;
										}else {
											$ref_array['amountht']=0;
											$ref_array['amountttc']=0;
										}
									}else {
										$ref_array['amountht']='';
										$ref_array['amountttc']='';
									}

									$ref_array['status']=$element->getLibStatut(0);

									$tmparray=$this->get_substitutionarray_project_reference($ref_array,$outputlangs);

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

							}
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
					}
				}

				// Call the beforeODTSave hook
				$parameters=array('odfHandler'=>&$odfHandler,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
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
				$parameters=array('odfHandler'=>&$odfHandler,'file'=>$file,'object'=>$object,'outputlangs'=>$outputlangs,'substitutionarray'=>&$tmparray);
				$reshook=$hookmanager->executeHooks('afterODTCreation',$parameters,$this,$action);    // Note that $action and $object may have been modified by some hooks

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
