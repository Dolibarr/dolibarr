<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2011 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2012 Regis Houssin        <regis@dolibarr.fr>
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
 *  \file       htdocs/core/modules/fichinter/modules_fichinter.php
 *  \ingroup    ficheinter
 *  \brief      Fichier contenant la classe mere de generation des fiches interventions en PDF
 *   			et la classe mere de numerotation des fiches interventions
 */

require_once(DOL_DOCUMENT_ROOT."/core/class/commondocgenerator.class.php");


/**
 *	\class      ModelePDFFicheinter
 *	\brief      Classe mere des modeles de fiche intervention
 */
abstract class ModelePDFFicheinter extends CommonDocGenerator
{
	var $error='';


	/**
	 *	Return list of active generation modules
	 *
	 *	@param	DoliDB	$db		Database handler
	 */
	function liste_modeles($db)
	{
		global $conf;

		$type='ficheinter';
		$liste=array();

		include_once(DOL_DOCUMENT_ROOT.'/core/lib/functions2.lib.php');
		$liste=getListOfModels($db,$type,'');

		return $liste;
	}
}


/**
 *  \class      ModeleNumRefFicheinter
 *  \brief      Classe mere des modeles de numerotation des references de fiches d'intervention
 */
abstract class ModeleNumRefFicheinter
{
	var $error='';

	/**
	 * 	Return if a module can be used or not
	 * 	@return		boolean     true if module can be used
	 */
	function isEnabled()
	{
		return true;
	}

	/**
	 * 	Renvoi la description par defaut du modele de numerotation
	 * 	@return     string      Texte descripif
	 */
	function info()
	{
		global $langs;
		$langs->load("ficheinter");
		return $langs->trans("NoDescription");
	}

	/**
	 * 	Renvoi un exemple de numerotation
	 * 	@return     string      Example
	 */
	function getExample()
	{
		global $langs;
		$langs->load("ficheinter");
		return $langs->trans("NoExample");
	}

	/**
	 * 	Test si les numeros deja en vigueur dans la base ne provoquent pas de
	 * 	de conflits qui empechera cette numerotation de fonctionner.
	 * 	@return     boolean     false si conflit, true si ok
	 */
	function canBeActivated()
	{
		return true;
	}

	/**
	 * 	Renvoi prochaine valeur attribuee
	 * 	@return     string      Valeur
	 */
	function getNextValue()
	{
		global $langs;
		return $langs->trans("NotAvailable");
	}

	/**
	 * 	Renvoi version du module numerotation
	 * 	@return     string      Valeur
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
 *  Create an intervention document on disk using template defined into FICHEINTER_ADDON_PDF
 *  @param	    db  			objet base de donnee
 *  @param	    object			Object fichinter
 *  @param	    modele			force le modele a utiliser ('' par defaut)
 *  @param		outputlangs		objet lang a utiliser pour traduction
 *  @return     int         	0 si KO, 1 si OK
 */
function fichinter_create($db, $object, $modele='', $outputlangs='')
{
	global $conf,$langs;
	$langs->load("ficheinter");

	$dir = "/core/modules/fichinter/doc/";

	// Positionne modele sur le nom du modele de facture a utiliser
	if (! dol_strlen($modele))
	{
		if ($conf->global->FICHEINTER_ADDON_PDF)
		{
			$modele = $conf->global->FICHEINTER_ADDON_PDF;
		}
		else
		{
			$modele = 'soleil';
		}
	}

	// Charge le modele
	$file = "pdf_".$modele.".modules.php";

	// On verifie l'emplacement du modele
	$file = dol_buildpath($dir.$file);

	if (file_exists($file))
	{
		$classname = "pdf_".$modele;
		require_once($file);

		$obj = new $classname($db);

		dol_syslog("fichinter_create build PDF", LOG_DEBUG);

		// We save charset_output to restore it because write_file can change it if needed for
		// output format that does not support UTF8.
		$sav_charset_output=$outputlangs->charset_output;
		if ($obj->write_file($object,$outputlangs) > 0)
		{
			$outputlangs->charset_output=$sav_charset_output;
			return 1;
		}
		else
		{
			$outputlangs->charset_output=$sav_charset_output;
			dol_print_error($db,$obj->error);
			return 0;
		}
	}
	else
	{
		print $langs->trans("Error")." ".$langs->trans("ErrorFileDoesNotExists",$file);
		return 0;
	}
}

?>