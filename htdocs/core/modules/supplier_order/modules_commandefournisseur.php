<?php
/* Copyright (C) 2003-2004 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2005 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2012 Regis Houssin        <regis.houssin@capnetworks.com>
 * Copyright (C) 2006      Andre Cianfarani     <acianfa@free.fr>
 * Copyright (C) 2011-2013 Philippe Grand       <philippe.grand@atoo-net.com>
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
 *		\file       htdocs/core/modules/supplier_order/modules_commandefournisseur.php
 *      \ingroup    commande fournisseur
 *      \brief      File that contains parent class for supplier orders models
 *                  and parent class for supplier orders numbering models
 */
require_once DOL_DOCUMENT_ROOT.'/core/class/commondocgenerator.class.php';
require_once DOL_DOCUMENT_ROOT.'/compta/bank/class/account.class.php';	// requis car utilise par les classes qui heritent


/**
 *	Parent class for supplier orders models
 */
abstract class ModelePDFSuppliersOrders extends CommonDocGenerator
{
	var $error='';


	/**
	 *  Return list of active generation models
	 *
     *  @param	DoliDB	$db     			Database handler
     *  @param  string	$maxfilenamelength  Max length of value to show
     *  @return	array						List of templates
	 */
	static function liste_modeles($db,$maxfilenamelength=0)
	{
		global $conf;

		$type='order_supplier';
		$liste=array();

		include_once DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php';
		$liste=getListOfModels($db,$type,$maxfilenamelength);

		return $liste;
	}

}



/**
 *	Parent Class of numbering models of suppliers orders references
 */
abstract class ModeleNumRefSuppliersOrders
{
	var $error='';

	/**  Return if a model can be used or not
	 *
	 *   @return	boolean     true if model can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**  Returns default description of numbering model
	 *
	 *   @return    string      Description Text
	 */
	function info()
	{
		global $langs;
		$langs->load("orders");
		return $langs->trans("NoDescription");
	}

	/**   Returns a numbering example
	 *
	 *    @return   string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("orders");
		return $langs->trans("NoExample");
	}

	/**  Tests if existing numbers make problems with numbering
	 *
	 *   @return	boolean     false if conflict, true if ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**  Returns next value assigned
	 *
	 *   @return     string      Valeur
	 */
	function getNextValue()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**   Returns version of the numbering model 
	 *
	 *    @return     string      Value
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
 *  Create a document onto disk according to template model.
 *
 *  @param	    DoliDB		$db  			Database handler
 *  @param	    Object		$object			Object supplier order
 *  @param	    string		$modele			Force template to use ('' to not force)
 *  @param		Translate	$outputlangs	Object lang to use for traduction
 *  @param      int			$hidedetails    Hide details of lines
 *  @param      int			$hidedesc       Hide description
 *  @param      int			$hideref        Hide ref
 *  @return     int          				0 if KO, 1 if OK
 */
function supplier_order_pdf_create($db, $object, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0)
{
	global $conf, $user, $langs;
	$langs->load("suppliers");

	$error=0;

	// Increase limit for PDF build
	$err=error_reporting();
	error_reporting(0);
	@set_time_limit(120);
	error_reporting($err);

	$srctemplatepath='';

	// Sets the model on the model name to use
	if (! dol_strlen($modele))
	{
		if (! empty($conf->global->COMMANDE_SUPPLIER_ADDON_PDF))
		{
			$modele = $conf->global->COMMANDE_SUPPLIER_ADDON_PDF;
		}
		else
		{
			$modele = 'muscadet';
		}
	}

	// If selected model is a filename template (then $modele="modelname:filename")
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
			$file = $prefix."_".$modele.".modules.php";

			// We check the model location 
			$file=dol_buildpath($reldir."core/modules/supplier_order/pdf/".$file,0);
			if (file_exists($file))
			{
				$filefound=1;
				$classname=$prefix.'_'.$modele;
				break;
			}
		}
		if ($filefound) break;
	}

	// Load the model
	if ($filefound)
	{
		require_once $file;

		$obj = new $classname($db,$object);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// we delete preview files
        	require_once DOL_DOCUMENT_ROOT.'/core/lib/files.lib.php';
			dol_delete_preview($object);

			// Calls triggers
			include_once DOL_DOCUMENT_ROOT . '/core/class/interfaces.class.php';
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('ORDER_SUPPLIER_BUILDDOC',$object,$user,$langs,$conf);
			if ($result < 0) { 
				$error++; $this->errors=$interface->errors; 
			}
			// End calls triggers

			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_syslog("Erreur dans supplier_order_pdf_create");
			dol_print_error($db,$obj->error);
			return 0;
		}
	}
	else
	{
		if (! $conf->global->COMMANDE_SUPPLIER_ADDON_PDF)
		{
			print $langs->trans("Error")." ".$langs->trans("Error_COMMANDE_SUPPLIER_ADDON_PDF_NotDefined");
		}
		else
		{
			print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file);
		}
		return 0;
	}
}

?>
