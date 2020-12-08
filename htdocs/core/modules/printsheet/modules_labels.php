<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2009 Regis Houssin        <regis.houssin@inodbox.com>
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
 *	\file       htdocs/core/modules/printsheet/modules_labels.php
 *	\ingroup    member
 *	\brief      File of parent class of document generator for members labels sheets.
 */

require_once DOL_DOCUMENT_ROOT.'/core/lib/pdf.lib.php';
require_once DOL_DOCUMENT_ROOT.'/product/class/product.class.php';


/**
 *  Parent class of document generator for address sheet.
 */
class ModelePDFLabels
{
	/**
	 * @var string Error code (or message)
	 */
	public $error = '';


	// phpcs:disable PEAR.NamingConventions.ValidFunctionName.ScopeNotCamelCaps
	/**
	 *  Return list of active generation modules
	 *
	 *  @param  DoliDB	$db     			Database handler
	 *  @param  integer	$maxfilenamelength  Max length of value to show
	 *  @return	array						List of templates
	 */
	public function liste_modeles($db, $maxfilenamelength = 0)
	{
		// phpcs:enable
		global $conf;

		$type = 'members_labels';
		$liste = array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste = getListOfModels($db, $type, $maxfilenamelength);

		return $liste;
	}
}


// phpcs:disable PEAR.NamingConventions.ValidFunctionName.NotCamelCaps
/**
 *  Create a document onto disk according to template module.
 *
 *	@param  DoliDB		$db					Database handler
 *	@param  array		$arrayofrecords		Array of records
 *	@param	string		$modele				Force le modele a utiliser ('' to not force)
 *	@param	Translate	$outputlangs		Objet lang a utiliser pour traduction
 *	@param	string		$outputdir			Output directory
 *  @param  string      $template           pdf generenate document class to use default 'standardlabel'
 *  @param  string      $filename           Short file name of PDF output file
 *	@return int        						<0 if KO, >0 if OK
 */
function doc_label_pdf_create($db, $arrayofrecords, $modele, $outputlangs, $outputdir = '', $template = 'standardlabel', $filename = 'tmp_address_sheet.pdf')
{
	// phpcs:enable
	global $conf, $langs;
	$langs->load("members");

	$error = 0;

	// Increase limit for PDF build
	$err = error_reporting();
	error_reporting(0);
	@set_time_limit(120);
	error_reporting($err);

	$code = '';
	$srctemplatepath = '';

	// Positionne le modele sur le nom du modele a utiliser
	if (!dol_strlen($modele))
	{
		if (!empty($conf->global->ADHERENT_ETIQUETTE_TYPE))
		{
			$code = $conf->global->ADHERENT_ETIQUETTE_TYPE;
		} else {
			$code = $modele;
		}
	} else $code = $modele;

	// If selected modele is a filename template (then $modele="modelname:filename")
	$tmp = explode(':', $template, 2);
	if (!empty($tmp[1]))
	{
		$template = $tmp[0];
		$srctemplatepath = $tmp[1];
	} else $srctemplatepath = $code;

	dol_syslog("modele=".$modele." outputdir=".$outputdir." template=".$template." code=".$code." srctemplatepath=".$srctemplatepath." filename=".$filename, LOG_DEBUG);

	// Search template files
	$file = ''; $classname = ''; $filefound = 0;
	$dirmodels = array('/');
	if (is_array($conf->modules_parts['models'])) $dirmodels = array_merge($dirmodels, $conf->modules_parts['models']);
	foreach ($dirmodels as $reldir)
	{
		foreach (array('doc', 'pdf') as $prefix)
		{
			$file = $prefix."_".$template.".class.php";

			// On verifie l'emplacement du modele
			$file = dol_buildpath($reldir."core/modules/printsheet/doc/".$file, 0);
			if (file_exists($file))
			{
				$filefound = 1;
				$classname = $prefix.'_'.$template;
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
		$sav_charset_output = $outputlangs->charset_output;
		if ($obj->write_file($arrayofrecords, $outputlangs, $srctemplatepath, $outputdir, $filename) > 0)
		{
			$outputlangs->charset_output = $sav_charset_output;
			return 1;
		} else {
			$outputlangs->charset_output = $sav_charset_output;
			dol_print_error($db, "doc_label_pdf_create Error: ".$obj->error);
			return -1;
		}
	} else {
		dol_print_error('', $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists", $file));
		return -1;
	}
}
