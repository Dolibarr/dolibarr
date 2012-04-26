<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011      Juanjo Menent	    <jmenent@2byte.es>
 * Copyright (C) 2011-2012 Philippe Grand       <philippe.grand@atoo-net.com>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 * or see http://www.gnu.org/
 */

/**
 *  \file       htdocs/core/modules/expedition/modules_expedition.php
 *  \ingroup    expedition
 *  \brief      File of class to manage expedition numbering
 */
 require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");

 /**
 *  \class      ModelePdfExpedition
 *  \brief      Parent class of sending receipts models
 */
abstract class ModelePdfExpedition extends CommonDocGenerator
{
    var $error='';


	/**
	 *  Return list of active generation modules
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  string	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='shipping';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
	}
}


/**
 *  \class      ModelNumRefExpedition
 *  \brief      Classe mere des modeles de numerotation des references d expedition
 */
abstract class ModelNumRefExpedition
{
	var $error='';

	/** Return if a module can be used or not
	 *
	 *  @return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 *	Return default description of numbering model
	 *
	 *	@return     string      text description
	 */
	function info()
	{
		global $langs;
		$langs->load("sendings");
		return $langs->trans("NoDescription");
	}

	/**
	 *	Return numbering example
	 *
	 *	@return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("sendings");
		return $langs->trans("NoExample");
	}

	/**
	 *	Test if existing numbers make problems with numbering
	 *
	 *	@return     boolean     false if conflit, true if ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**
	 *	Return next value
	 *
	 *	@param	Societe		$objsoc     Third party object
	 *	@param	Object		$shipment	Shipment object
	 *	@return	string					Value
	 */
	function getNextValue($objsoc, $shipment)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 *	Return numbering version module
	 *
	 *	@return     string      Value
	 */
	function getVersion()
	{
		global $langs;
		$langs->load("admin");

		if ($this->version == 'development') return $langs->trans("VersionDevelopment");
		if ($this->version == 'experimental') return $langs->trans("VersionExperimental");
		if ($this->version == 'dolibarr') return DOL_VERSION;
		return $langs->trans("NotAvailable");
	}
}

/**
 * 	Cree un bon d'expedition sur disque
 *
 * 	@param	DoliDB		$db  			Objet base de donnee
 * 	@param	Object		$object			Object expedition
 * 	@param	string		$modele			Force le modele a utiliser ('' to not force)
 * 	@param	Translate	$outputlangs	Objet lang a utiliser pour traduction
 *  @return int             			<=0 if KO, >0 if OK
 */
function expedition_pdf_create($db, $object, $modele, $outputlangs)
{
	global $conf,$langs;

	$langs->load("sendings");

	$error=0;

	$srctemplatepath='';

	// Positionne le modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
	    if (! empty($conf->global->EXPEDITION_ADDON_PDF))
	    {
	        $modele = $conf->global->EXPEDITION_ADDON_PDF;
	    }
	    else
	    {
	        $modele = 'rouget';
	    }
	}

	// If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template files
	$file=''; $classname=''; $filefound=0;
	$dirmodels=array('/');
	if (is_array($conf->modules_parts['models'])) $dirmodels=array_merge($dirmodels,$conf->modules_parts['models']);
	foreach($dirmodels as $reldir)
	{
    	foreach(array('doc','pdf') as $prefix)
    	{
    	    $file = $prefix."_expedition_".$modele.".modules.php";

    		// On verifie l'emplacement du modele
	        $file=dol_buildpath($reldir."core/modules/expedition/doc/".$file,0);
    		if (file_exists($file))
    		{
    			$filefound=1;
    			$classname=$prefix.'_expedition_'.$modele;
    			break;
    		}
    	}
    	if ($filefound) break;
    }

	// Charge le modele
	if ($filefound)
	{
	    require_once($file);

		$obj = new $classname($db);

		$result=$object->fetch_origin();

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs, $srctemplatepath) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// we delete preview files
        	//require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
			//dol_delete_preview($object);
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans expedition_pdf_create");
			dol_print_error($db,$obj->error);
			return 0;
		}
	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
    }
}
?>