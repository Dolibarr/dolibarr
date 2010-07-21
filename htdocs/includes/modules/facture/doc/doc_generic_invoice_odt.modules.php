<?php
/* Copyright (C) 2010 Laurent Destailleur <ely@users.sourceforge.net>

 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 *	\file       htdocs/includes/modules/facture/doc/doc_generic_invoice_odt.modules.php
 *	\ingroup    societe
 *	\brief      File of class to build ODT documents for third parties
 *	\author	    Laurent Destailleur
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/facture/modules_facture.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(DOL_DOCUMENT_ROOT."/lib/files.lib.php");


/**
 *	\class      doc_generic_invoice_odt
 *	\brief      Class to build documents using ODF templates generator
 */
class doc_generic_invoice_odt extends ModelePDFFactures
{
	var $emetteur;	// Objet societe qui emet

	var $phpmin = array(5,2,0);	// Minimum version of PHP required by module


	/**
	 *		\brief  Constructor
	 *		\param	db		Database handler
	 */
	function doc_generic_invoice_odt($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("companies");

		$this->db = $db;
		$this->name = "ODT templates";
		$this->description = $langs->trans("DocumentModelOdt");
		$this->scandir = 'FACTURE_ADDON_PDF_ODT_PATH';	// Name of constant that is used to save list of directories to scan

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

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'etait pas defini
	}


	/**
	 * Define array with couple subtitution key => subtitution value
	 *
	 * @param $mysoc
	 */
	function get_substitutionarray_mysoc($mysoc)
	{
		global $conf;

		if (empty($mysoc->forme_juridique) && ! empty($mysoc->forme_juridique_code))
		{
			$mysoc->forme_juridique=getFormeJuridiqueLabel($mysoc->forme_juridique_code);
		}

		$logotouse=$conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small;

		return array(
			'mycompany_logo'=>$logotouse,
			'mycompany_name'=>$mysoc->name,
			'mycompany_email'=>$mysoc->email,
			'mycompany_phone'=>$mysoc->phone,
			'mycompany_fax'=>$mysoc->fax,
			'mycompany_address'=>$mysoc->address,
			'mycompany_zip'=>$mysoc->zip,
			'mycompany_town'=>$mysoc->town,
			'mycompany_country'=>$mysoc->country,
			'mycompany_web'=>$mysoc->url,
			'mycompany_juridicalstatus'=>$mysoc->forme_juridique,
			'mycompany_capital'=>$mysoc->capital,
			'mycompany_barcode'=>$mysoc->gencode,
			'mycompany_idprof1'=>$mysoc->idprof1,
			'mycompany_idprof2'=>$mysoc->idprof2,
			'mycompany_idprof3'=>$mysoc->idprof3,
			'mycompany_idprof4'=>$mysoc->idprof4,
			'mycompany_vatnumber'=>$mysoc->tva_intra,
			'mycompany_note'=>$mysoc->note
		);
	}


	/**
	 * Define array with couple subtitution key => subtitution value
	 *
	 * @param $object
	 */
	function get_substitutionarray_object($object)
	{
		global $conf;

		return array(
			'company_name'=>$object->name,
			'company_email'=>$object->email,
			'company_phone'=>$object->phone,
			'company_fax'=>$object->fax,
			'company_address'=>$object->address,
			'company_zip'=>$object->zip,
			'company_town'=>$object->town,
			'company_country'=>$object->country,
			'company_web'=>$object->url,
			'company_barcode'=>$object->gencode,
			'company_vatnumber'=>$object->tva_intra,
			'company_customercode'=>$object->code_client,
			'company_suppliercode'=>$object->code_fournisseur,
			'company_customeraccountancycode'=>$object->code_compta,
			'company_supplieraccountancycode'=>$object->code_compta_fournisseur,
			'company_juridicalstatus'=>$object->forme_juridique,
			'company_capital'=>$object->capital,
			'company_idprof1'=>$object->idprof1,
			'company_idprof2'=>$object->idprof2,
			'company_idprof3'=>$object->idprof3,
			'company_idprof4'=>$object->idprof4,
			'company_note'=>$object->note
		);
	}

	/**		\brief      Return description of a module
	 *      \return     string      Description
	 */
	function info($langs)
	{
		global $conf,$langs;

		$langs->load("companies");
		$langs->load("errors");

		$form = new Form($db);

		$texte = $this->description.".<br>\n";
		$texte.= '<form action="'.$_SERVER["PHP_SELF"].'" method="POST">';
		$texte.= '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
		$texte.= '<input type="hidden" name="action" value="setModuleOptions">';
		$texte.= '<input type="hidden" name="param1" value="FACTURE_ADDON_PDF_ODT_PATH">';
		$texte.= '<table class="nobordernopadding" width="100%">';

		// List of directories area
		$texte.= '<tr><td>';
		$texttitle=$langs->trans("ListOfDirectories");
		$listofdir=explode(',',preg_replace('/[\r\n]+/',',',trim($conf->global->FACTURE_ADDON_PDF_ODT_PATH)));
		$listoffiles=array();
		foreach($listofdir as $key=>$tmpdir)
		{
			$tmpdir=trim($tmpdir);
			$tmpdir=preg_replace('/DOL_DATA_ROOT/',DOL_DATA_ROOT,$tmpdir);
			if (! $tmpdir) { unset($listofdir[$key]); continue; }
			if (! is_dir($tmpdir)) $texttitle.=img_warning($langs->trans("ErrorDirNotFound",$tmpdir),0);
			else
			{
				$tmpfiles=dol_dir_list($tmpdir,'files',0,'\.odt');
				if (sizeof($tmpfiles)) $listoffiles=array_merge($listoffiles,$tmpfiles);
			}
		}
		$texthelp=$langs->trans("ListOfDirectoriesForModelGenODT");
		// Add list of substitution keys
		$texthelp.='<br>'.$langs->trans("FollowingSubstitutionKeysCanBeUsed").'<br>';
		$dummy=new Societe($db);
		$tmparray=$this->get_substitutionarray_mysoc($dummy);
		$nb=0;
		foreach($tmparray as $key => $val)
		{
			$texthelp.='{'.$key.'}<br>';
			$nb++;
			if ($nb >= 5) { $texthelp.='...<br>'; break; }
		}
		$tmparray=$this->get_substitutionarray_object($dummy);
		$nb=0;
		foreach($tmparray as $key => $val)
		{
			$texthelp.='{'.$key.'}<br>';
			$nb++;
			if ($nb >= 5) { $texthelp.='...<br>'; break; }
		}
		$texthelp.=$langs->trans("FullListOnOnlineDocumentation");

		$texte.= $form->textwithpicto($texttitle,$texthelp,1,'help');
		//var_dump($listofdir);

		$texte.= '<textarea class="flat" cols="80" name="value1">';
		$texte.=$conf->global->FACTURE_ADDON_PDF_ODT_PATH;
		$texte.= '</textarea>';

		// Scan directories
		if (sizeof($listofdir)) $texte.='<br>'.$langs->trans("NumberOfModelFilesFound").': '.sizeof($listoffiles);

		$texte.= '</td>';


		$texte.= '<td valign="top" rowspan="2">';
		$texte.= $langs->trans("ExampleOfDirectoriesForModelGen");
		$texte.= '</td>';
		$texte.= '</tr>';

		// Example
		$texte.= '<tr><td align="center">';
		$texte.= '<input type="submit" class="button" value="'.$langs->trans("Modify").'" name="Button">';
		$texte.= '</td>';
		$texte.= '</tr>';

		$texte.= '</table>';
		$texte.= '</form>';

		return $texte;
	}

	/**
	 *	\brief      Function to build a document on disk using the generic odt module.
	 *	\param	    object				Object source to build document
	 *	\param		outputlangs			Lang output object
	 * 	\param		$srctemplatepath	Full path of source filename for generator using a template file
	 *	\return	    int         		1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs,$srctemplatepath)
	{
		global $user,$langs,$conf,$mysoc;

		if (empty($srctemplatepath))
		{
			dol_syslog("doc_generic_odt::write_file parameter srctemplatepath empty", LOG_WARNING);
			return -1;
		}

		if (! is_object($outputlangs)) $outputlangs=$langs;
		$sav_charset_output=$outputlangs->charset_output;
		$outputlangs->charset_output='UTF-8';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
		$outputlangs->load("projects");

		if ($conf->societe->dir_output)
		{
			// If $object is id instead of object
			if (! is_object($object))
			{
				$id = $object;
				$object = new Societe($this->db);
				$object->fetch($id);

				if ($result < 0)
				{
					dol_print_error($db,$object->error);
					return -1;
				}
			}

			$objectref = dol_sanitizeFileName($object->id);
			$dir = $conf->societe->dir_output;
			if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
			$file = $dir . "/" . $objectref . ".odt";

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return -1;
				}
			}

			if (file_exists($dir))
			{
				//print "srctemplatepath=".$srctemplatepath;	// Src filename
				$newfile=basename($srctemplatepath);
				$newfiletmp=preg_replace('/\.odt/i','',$newfile);
				$file=$dir.'/'.$newfiletmp.'.'.dol_print_date(dol_now('tzserver'),'%Y%m%d%H%M%S').'.odt';
				//print "newdir=".$dir;
				//print "newfile=".$newfile;
				//print "file=".$file;
				//print "conf->societe->dir_temp=".$conf->societe->dir_temp;

				create_exdir($conf->societe->dir_temp);

				// Open and load template
				require_once(DOL_DOCUMENT_ROOT.'/includes/odtphp/odf.php');
				$odfHandler = new odf($srctemplatepath, array(
						'PATH_TO_TMP'	  => $conf->societe->dir_temp,
						'ZIP_PROXY'		  => 'PclZipProxy',	// PhpZipProxy or PclZipProxy. Got "bad compression method" error when using PhpZipProxy.
						'DELIMITER_LEFT'  => '{',
						'DELIMITER_RIGHT' => '}')
				);

				// Make substitutions
				$tmparray=$this->get_substitutionarray_mysoc($mysoc);
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
				$tmparray=$this->get_substitutionarray_object($object);
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

				// Write new file
				//$result=$odfHandler->exportAsAttachedFile('toto');
				$odfHandler->saveToDisk($file);

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
