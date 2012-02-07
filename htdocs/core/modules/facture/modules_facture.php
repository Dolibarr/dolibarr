<?php
/* Copyright (C) 2003-2005 Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2004      Eric Seigne          <eric.seigne@ryxeo.com>
 * Copyright (C) 2005-2011 Regis Houssin        <regis@dolibarr.fr>
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
 *	\file       htdocs/core/modules/facture/modules_facture.php
 *	\ingroup    facture
 *	\brief      Fichier contenant la classe mere de generation des factures en PDF
 * 				et la classe mere de numerotation des factures
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");
require_once(DOL_DOCUMENT_ROOT."/product/class/product.class.php");
require_once(DOL_DOCUMENT_ROOT."/compta/bank/class/account.class.php");   // Requis car utilise dans les classes qui heritent


/**
 *	\class      ModelePDFFactures
 *	\brief      Classe mere des modeles de facture
 */
abstract class ModelePDFFactures extends CommonDocGenerator
{
	var $error='';

	/**
	 *  Return list of active generation modules
	 * 	@param		$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='invoice';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}
}

/**
 *	\class      ModeleNumRefFactures
 *	\brief      Classe mere des modeles de numerotation des references de facture
 */
abstract class ModeleNumRefFactures
{
	var $error='';

	/**  Return if a module can be used or not
	 *   @return	boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**	 Renvoi la description par defaut du modele de numerotation
	 *   @return    string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoDescription");
	}

	/**  Renvoi un exemple de numerotation
	 *	 @return	string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("bills");
		return $langs->trans("NoExample");
	}

	/**  Test si les numeros deja en vigueur dans la base ne provoquent pas
	 *   de conflits qui empecheraient cette numerotation de fonctionner.
	 *   @return	boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**  Renvoi prochaine valeur attribuee
	 *   @param     objsoc		Objet societe
	 *   @param     facture		Objet facture
	 *   @return    string      Valeur
	 */
	function getNextValue($objsoc,$facture)
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**  Renvoi version du modele de numerotation
	 *   @return    string      Valeur
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
 *  Create a document onto disk according to template module.
 *
 *	@param   	DoliDB		$db  			Database handler
 *	@param   	Object		$object			Object invoice
 *	@param	    string		$modele			Force template to use ('' to not force)
 *	@param		Translate	$outputlangs	objet lang a utiliser pour traduction
 *  @param      int			$hidedetails    Hide details of lines
 *  @param      int			$hidedesc       Hide description
 *  @param      int			$hideref        Hide ref
 *  @param      HookManager	$hookmanager	Hook manager instance
 *	@return  	int        					<0 if KO, >0 if OK
 */
function facture_pdf_create($db, $object, $modele, $outputlangs, $hidedetails=0, $hidedesc=0, $hideref=0, $hookmanager=false)
{
	global $conf,$user,$langs;

	$langs->load("bills");

	$error=0;
	
	// Increase limit for PDF build
    $err=error_reporting();
    error_reporting(0);
    @set_time_limit(120);
    error_reporting($err);

	$dir = "/core/modules/facture/";
    $srctemplatepath='';

	// Positionne le modele sur le nom du modele a utiliser
	if (! dol_strlen($modele))
	{
		if (! empty($conf->global->FACTURE_ADDON_PDF))
		{
			$modele = $conf->global->FACTURE_ADDON_PDF;
		}
		else
		{
			$modele = 'crabe';
		}
	}

    // If selected modele is a filename template (then $modele="modelname:filename")
	$tmp=explode(':',$modele,2);
    if (! empty($tmp[1]))
    {
        $modele=$tmp[0];
        $srctemplatepath=$tmp[1];
    }

	// Search template file
	$file=''; $classname=''; $filefound=0;
	foreach(array('doc','pdf') as $prefix)
	{
        $file = $prefix."_".$modele.".modules.php";

        // On verifie l'emplacement du modele
        $file = dol_buildpath($dir.'doc/'.$file);

        if (file_exists($file))
	    {
	        $filefound=1;
	        $classname=$prefix.'_'.$modele;
	        break;
	    }
	}

	// Charge le modele
	if ($filefound)
	{
		require_once($file);

		$obj = new $classname($db);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object, $outputlangs, $srctemplatepath, $hidedetails, $hidedesc, $hideref, $hookmanager) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;

			// We delete old preview
			require_once(DOL_DOCUMENT_ROOT."/core/lib/files.lib.php");
			dol_delete_preview($object);

			// Success in building document. We build meta file.
			dol_meta_create($object);

			// Appel des triggers
			include_once(DOL_DOCUMENT_ROOT . "/core/class/interfaces.class.php");
			$interface=new Interfaces($db);
			$result=$interface->run_triggers('BILL_BUILDDOC',$object,$user,$langs,$conf);
			if ($result < 0) { $error++; $this->errors=$interface->errors; }
			// Fin appel triggers

			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,"facture_pdf_create Error: ".$obj->error);
			return -1;
		}

	}
	else
	{
		dol_print_error('',$langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file));
		return -1;
	}
}

?>