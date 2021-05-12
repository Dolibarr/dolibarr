<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
<<<<<<< HEAD
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2011-2012 Philippe Grand	    <philippe.grand@atoo-net.com>
=======
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2019 Philippe Grand	    <philippe.grand@atoo-net.com>
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
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
 *  \file       htdocs/core/modules/fichinter/modules_fichinter.php
 *  \ingroup    ficheinter
<<<<<<< HEAD
 *  \brief      Fichier contenant la classe mere de generation des fiches interventions en PDF
 *   			et la classe mere de numerotation des fiches interventions
=======
 *  \brief      File that contains parent class for PDF interventions models
 *   			and parent class for interventions numbering models
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';


/**
 *	Parent class to manage intervention document templates
 */
abstract class ModelePDFFicheinter extends CommonDocGenerator
{
<<<<<<< HEAD
	var $error='';


=======
	/**
	 * @var string Error code (or message)
	 */
	public $error='';


    // phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	/**
	 *	Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  integer	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
<<<<<<< HEAD
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='ficheinter';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
=======
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
        // phpcs:enable
		global $conf;

		$type='ficheinter';
		$list=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list=getListOfModels($db, $type, $maxfilenamelength);

		return $list;
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}


/**
<<<<<<< HEAD
 *  Classe mere des modeles de numerotation des references de fiches d'intervention
 */
abstract class ModeleNumRefFicheinter
{
	var $error='';
=======
 *  Parent class numbering models of intervention sheet references
 */
abstract class ModeleNumRefFicheinter
{
	/**
	 * @var string Error code (or message)
	 */
	public $error='';
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9

	/**
	 * 	Return if a module can be used or not
	 *
	 * 	@return		boolean     true if module can be used
	 */
<<<<<<< HEAD
	function isEnabled()
=======
	public function isEnabled()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return true;
	}

	/**
<<<<<<< HEAD
	 * 	Renvoi la description par defaut du modele de numerotation
	 *
	 * 	@return     string      Texte descripif
	 */
	function info()
=======
	 * 	Returns the default description of the numbering template
	 *
	 * 	@return     string      Descriptive text
	 */
	public function info()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("ficheinter");
		return $langs->trans("NoDescription");
	}

	/**
<<<<<<< HEAD
	 * 	Renvoi un exemple de numerotation
	 *
	 * 	@return     string      Example
	 */
	function getExample()
=======
	 * 	Return a numbering example
	 *
	 * 	@return     string      Example
	 */
	public function getExample()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("ficheinter");
		return $langs->trans("NoExample");
	}

	/**
<<<<<<< HEAD
	 * 	Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 * 	de conflits qui empechera cette numerotation de fonctionner.
	 *
	 * 	@return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
=======
	 * 	Tests if the numbers already in force in the database do not cause conflicts
	 *  that would prevent this numbering from working.
	 *
	 * 	@return     boolean     false si conflit, true si ok
	 */
	public function canBeActivated()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		return true;
	}

	/**
<<<<<<< HEAD
	 * 	Renvoi prochaine valeur attribuee
	 *
	 * 	@return     string      Valeur
	 */
	function getNextValue()
=======
	 * 	Return the next assigned value
	 *
	 * 	@return     string      Value
	 */
	public function getNextValue()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
<<<<<<< HEAD
	 * 	Renvoi version du module numerotation
	 *
	 * 	@return     string      Valeur
	 */
	function getVersion()
=======
	 * 	Return the version of the numbering module
	 *
	 * 	@return     string      Value
	 */
	public function getVersion()
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
<<<<<<< HEAD
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		if ($this->version) return $this->version;
		return $langs->trans("NotAvailable");
=======
		elseif ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		elseif ($this->version == 'dolibarr') return DOL_VERSION;
		elseif ($this->version) return $this->version;
		else return $langs->trans("NotAvailable");
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	}
}


<<<<<<< HEAD
=======
// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
/**
 *  Create an intervention document on disk using template defined into FICHEINTER_ADDON_PDF
 *
 *  @param	DoliDB		$db  			objet base de donnee
 *  @param	Object		$object			Object fichinter
 *  @param	string		$modele			force le modele a utiliser ('' par defaut)
 *  @param	Translate	$outputlangs	objet lang a utiliser pour traduction
 *  @param  int			$hidedetails    Hide details of lines
 *  @param  int			$hidedesc       Hide description
 *  @param  int			$hideref        Hide ref
 *  @return int         				0 if KO, 1 if OK
 */
<<<<<<< HEAD
function fichinter_create($db, $object, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
{
=======
function fichinter_create($db, $object, $modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
{
    // phpcs:enable
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	global $conf,$langs,$user;
	$langs->load("ficheinter");

	$error=0;

	$srctemplatepath='';

	// Positionne modele sur le nom du modele de fichinter a utiliser
	if (! dol_strlen($modele))
	{
		if (! empty($conf->global->FICHEINTER_ADDON_PDF))
		{
			$modele = $conf->global->FICHEINTER_ADDON_PDF;
		}
		else
		{
			$modele = 'soleil';
		}
	}

	// If selected modele is a filename template (then $modele="modelname:filename")
<<<<<<< HEAD
	$tmp=explode(':',$modele,2);
=======
	$tmp=explode(':', $modele, 2);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array('/');
<<<<<<< HEAD
	if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels,$conf->modules_parts['models']);
=======
	if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels, $conf->modules_parts['models']);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
	foreach($dirmodels as $reldir)
	{
    	foreach(array('doc','pdf') as $prefix)
    	{
    	    $file = $prefix."_".$modele.".modules.php";

    		// On verifie l'emplacement du modele
<<<<<<< HEAD
	        $file=dol_buildpath($reldir."core/modules/fichinter/doc/".$file,0);
=======
	        $file=dol_buildpath($reldir."core/modules/fichinter/doc/".$file, 0);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
    		if (file_exists($file))
    		{
    			$filefound=1;
    			$classname=$prefix.'_'.$modele;
    			break;
    		}
    	}
    	if ($filefound) break;
    }

	// Charge le modele
	if ($filefound)
	{
		require_once $file;

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($object);

			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
<<<<<<< HEAD
			dol_print_error($db,"fichinter_pdf_create Error: ".$obj->error);
=======
			dol_print_error($db, "fichinter_pdf_create Error: ".$obj->error);
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
			return 0;
		}
	}
	else
	{
<<<<<<< HEAD
		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file);
		return 0;
	}
}

=======
		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists", $file);
		return 0;
	}
}
>>>>>>> fed598236c185406f59a504ed57181464c26b1b9
