<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@inodbox.com>
 * Copyright (C) 2011-2019 Philippe Grand	    <philippe.grand@atoo-net.com>
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
 *  \file       htdocs/core/modules/fichinter/modules_fichinter.php
 *  \ingroup    ficheinter
 *  \brief      File that contains parent class for PDF interventions models
 *   			and parent class for interventions numbering models
 */

require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/core/class/commonnumrefgenerator.class.php';


/**
 *	Parent class to manage intervention document templates
 */
abstract class ModelePDFFicheinter extends CommonDocGenerator
{
	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param  DoliDB  	$db                 Database handler
	 *  @param  int<0,max>	$maxfilenamelength  Max length of value to show
	 *  @return string[]|int<-1,0>				List of templates
	 */
	public static function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		$type = 'ficheinter';
		$list = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$list = getListOfModels($db, $type, $maxfilenamelength);

		return $list;
	}


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Function to build pdf onto disk
	 *
	 *  @param		Fichinter		$object				Object to generate
	 *  @param		Translate		$outputlangs		Lang output object
	 *  @param		string			$srctemplatepath	Full path of source filename for generator using a template file
	 *  @param		int<0,1>		$hidedetails		Do not show line details
	 *  @param		int<0,1>		$hidedesc			Do not show desc
	 *  @param		int<0,1>		$hideref			Do not show ref
	 *  @return		int<0,1>							1=OK, 0=KO
	 */
	abstract public function write_file($object, $outputlangs, $srctemplatepath = '', $hidedetails = 0, $hidedesc = 0, $hideref = 0);
}


/**
 *  Parent class numbering models of intervention sheet references
 */
abstract class ModeleNumRefFicheinter extends CommonNumRefGenerator
{
	/**
	 * 	Return next free value
	 *
	 *  @param	Societe|string		$objsoc     Object thirdparty
	 *  @param  Fichinter|string	$object		Object we need next value for
	 *	@return string|int<-1,0>    			Next value if OK, <=0 if KO
	 */
	abstract public function getNextValue($objsoc = '', $object = '');
}


// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
/**
 *  Create an intervention document on disk using template defined into FICHEINTER_ADDON_PDF
 *
 *  @param	DoliDB		$db  			object base de donnee
 *  @param	Object		$object			Object fichinter
 *  @param	string		$modele			force le modele a utiliser ('' par default)
 *  @param	Translate	$outputlangs	object lang a utiliser pour traduction
 *  @param  int			$hidedetails    Hide details of lines
 *  @param  int			$hidedesc       Hide description
 *  @param  int			$hideref        Hide ref
 *  @return int         				0 if KO, 1 if OK
 */
function fichinter_create($db, $object, $modele, $outputlangs, $hidedetails = 0, $hidedesc = 0, $hideref = 0)
{
	// phpcs:enable
	global $conf, $langs;
	$langs->load("ficheinter");

	$error = 0;

	$srctemplatepath = '';

	// Positionne modele sur le nom du modele de fichinter a utiliser
	if (!dol_strlen($modele)) {
		if (getDolGlobalString('FICHEINTER_ADDON_PDF')) {
			$modele = getDolGlobalString('FICHEINTER_ADDON_PDF');
		} else {
			$modele = 'soleil';
		}
	}

	// If selected modele is a filename template (then $modele="modelname:filename")
	$tmp = explode(':', $modele, 2);
	if (!empty($tmp[1])) {
		$modele = $tmp[0];
		$srctemplatepath = $tmp[1];
	}

	// Search template files
	$file = '';
	$classname = '';
	$dirmodels = array('/');
	if (is_array($conf->modules_parts['models'])) {
		$dirmodels = array_merge($dirmodels, $conf->modules_parts['models']);
	}
	foreach ($dirmodels as $reldir) {
		foreach (array('doc', 'pdf') as $prefix) {
			$file = $prefix."_".$modele.".modules.php";

			// Get the location of the module and verify it exists
			$file = dol_buildpath($reldir."core/modules/fichinter/doc/".$file, 0);
			if (file_exists($file)) {
				$classname = $prefix.'_'.$modele;
				break;
			}
		}
		if ($classname !== '') {
			break;
		}
	}

	// Charge le modele
	if ($classname !== '') {
		require_once $file;

		$obj = new $classname($db);

		'@phan-var-force ModelePDFFicheinter $obj';

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output = $outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0) {
			$outputlangs->charset_output = $sav_charset_output;

			// We delete old preview
			require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($object);

			return 1;
		} else {
			$outputlangs->charset_output = $sav_charset_output;
			dol_print_error($db, "fichinter_pdf_create Error: ".$obj->error);
			return 0;
		}
	} else {
		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists", $file);
		return 0;
	}
}
